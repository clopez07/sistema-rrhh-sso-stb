<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuimicosImportController extends Controller
{
    const ACTIVO   = 1;
    const INACTIVO = 0;

    // Catálogo de niveles
    const NIVEL_RIESGO_TABLE   = 'nivel_riesgo';
    const NIVEL_RIESGO_IDCOL   = 'id_nivel_riesgo';
    const NIVEL_RIESGO_NAMECOL = 'nivel_riesgo';

    // Catálogo de Tipos de Exposición
    const TIPO_EXP_TABLE   = 'tipo_exposicion';
    const TIPO_EXP_IDCOL   = 'id_tipo_exposicion';
    const TIPO_EXP_NAMECOL = 'tipo_exposicion';

    // Pivot Químico <-> Tipo de Exposición
    const QXEXP_TABLE   = 'quimico_tipo_exposicion';
    const QXEXP_QCOL    = 'id_quimico';
    const QXEXP_EXPCOL  = 'id_tipo_exposicion';

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());

        $sheet = $spreadsheet->getSheetByName('INVENTARIO_DE_QUIMICOS');
        if (!$sheet) {
            return back()->withErrors(['excel_file' => 'No se encontró la hoja "INVENTARIO_DE_QUIMICOS".']);
        }

        $rows = $sheet->toArray(null, false, false, false);

        // Detectar encabezados y primera fila de datos
        [$start, $headerIdx, $colIdx] = $this->detectHeaderAndStart($rows);
        if ($start === null) {
            return back()->withErrors(['excel_file' => 'No se detectaron filas de datos en "INVENTARIO_DE_QUIMICOS".']);
        }

        // Catálogo de Niveles
        $niveles = DB::table(self::NIVEL_RIESGO_TABLE)
            ->select(self::NIVEL_RIESGO_IDCOL.' as id', self::NIVEL_RIESGO_NAMECOL.' as nombre')
            ->get()
            ->reduce(function($carry, $n){
                $carry[$this->norm($n->nombre)] = (int)$n->id;
                return $carry;
            }, []);

        // Catálogo de Tipos de Exposición
        $exposCatalog = DB::table(self::TIPO_EXP_TABLE)
            ->select(self::TIPO_EXP_IDCOL.' as id', self::TIPO_EXP_NAMECOL.' as nombre')
            ->get()
            ->reduce(function($carry, $e){
                $carry[$this->norm($e->nombre)] = (int)$e->id;
                return $carry;
            }, []);

        // Existentes por nombre normalizado
        $existentes = DB::table('quimico')->get();
        $mapExist = [];
        foreach ($existentes as $q) {
            $key = $this->norm($q->nombre_comercial);
            $mapExist[$key] = $q;
        }

        // Contadores
        $insertados = 0; $actualizados = 0; $sinCambios = 0; $desactivados = 0; $duplicados = 0;
        $vistos = [];
        $toInsert = []; $namesInserted = [];
        $syncExpos = []; $pendingNewExpos = [];
        $chunkSize = 300;
        $expAdd = 0; $expDel = 0; $expUnknown = [];

        DB::transaction(function () use (
            $rows, $start, $colIdx, $niveles, $exposCatalog, &$mapExist, &$vistos,
            &$toInsert, &$namesInserted, &$syncExpos, &$pendingNewExpos, $chunkSize,
            &$insertados, &$actualizados, &$sinCambios, &$duplicados, &$expUnknown
        ) {
            foreach (array_slice($rows, $start) as $row) {
                $nombre = $this->cellStr($row, $colIdx, 'nombre_comercial');
                if (!$nombre) continue;

                $key = $this->norm($nombre);
                if (isset($vistos[$key])) { $duplicados++; continue; }
                $vistos[$key] = true;

                // Nivel de riesgo
                $nivelTxt = $this->cellStr($row, $colIdx, 'nivel_riesgo');
                $idNivel  = $nivelTxt ? ($niveles[$this->norm($nivelTxt)] ?? null) : null;

                // Payload principal
                $payload  = $this->payloadFromRow($row, $colIdx, $idNivel);
                $payload['estado'] = self::ACTIVO;

                // Exposiciones desde UNA columna (texto)
                $exposTxt = $this->cellStr($row, $colIdx, 'tipo_exposicion'); // "Inhalación, Dérmico / Ocular"
                $exposIds = $this->parseExposList($exposTxt, $exposCatalog, $expUnknown);

                if (isset($mapExist[$key])) {
                    // UPDATE
                    $existing = $mapExist[$key];

                    $existingSubset = [];
                    foreach ($payload as $k => $v) {
                        $existingSubset[$k] = $existing->$k ?? null;
                    }
                    $dirty = $this->diffAssocLoose($payload, $existingSubset);

                    if (empty($dirty)) {
                        if ((int)($existing->estado ?? 0) !== self::ACTIVO) {
                            DB::table('quimico')->where('id_quimico', $existing->id_quimico)
                                ->update(['estado' => self::ACTIVO]);
                            $actualizados++;
                        } else {
                            $sinCambios++;
                        }
                    } else {
                        DB::table('quimico')->where('id_quimico', $existing->id_quimico)->update($dirty);
                        $actualizados++;
                    }

                    // Programar sync de exposiciones
                    $syncExpos[(int)$existing->id_quimico] = $exposIds;

                } else {
                    // INSERT
                    $toInsert[] = $payload;
                    $namesInserted[] = $payload['nombre_comercial'];
                    $pendingNewExpos[$key] = $exposIds;

                    if (count($toInsert) >= $chunkSize) {
                        DB::table('quimico')->insert($toInsert);
                        $insertados += count($toInsert);
                        $toInsert = [];
                    }
                }
            }

            if (!empty($toInsert)) {
                DB::table('quimico')->insert($toInsert);
                $insertados += count($toInsert);
                $toInsert = [];
            }

            // IDs de nuevos para sincronizar expos pivot
            if (!empty($namesInserted)) {
                $newRecords = DB::table('quimico')
                    ->whereIn('nombre_comercial', $namesInserted)
                    ->get(['id_quimico', 'nombre_comercial']);

                foreach ($newRecords as $rec) {
                    $k = $this->norm($rec->nombre_comercial);
                    if (isset($pendingNewExpos[$k])) {
                        $syncExpos[(int)$rec->id_quimico] = $pendingNewExpos[$k];
                    }
                }
            }

            // Sincroniza pivot y devuelve totales de add/del
            $res = $this->syncExposicionesPivot($syncExpos);
            // no necesitamos por fuera, pero si quisieras mostrarlos:
            // $expAdd += $res['added']; $expDel += $res['removed'];
        });

        // Desactivar los que no vinieron
        DB::transaction(function () use ($mapExist, $vistos, &$desactivados) {
            foreach ($mapExist as $key => $q) {
                if (!isset($vistos[$key])) {
                    if ((int)($q->estado ?? 1) !== self::INACTIVO) {
                        DB::table('quimico')->where('id_quimico', $q->id_quimico)
                            ->update(['estado' => self::INACTIVO]);
                        $desactivados++;
                    }
                }
            }
        });

        $msg = "Importación completada. Insertados: {$insertados}, Actualizados: {$actualizados}, Sin cambios: {$sinCambios}, Desactivados: {$desactivados}, Duplicados en Excel: {$duplicados}.";
        if (!empty($expUnknown)) {
            $ej = implode(', ', array_slice(array_values(array_unique($expUnknown)), 0, 6));
            $msg .= " (Ojo: tipos de exposición no encontrados en catálogo: {$ej}".(count($expUnknown)>6?'...':'').")";
        }
        return back()->with('status', $msg);
    }

    /* ================= Helpers ================= */

    private function detectHeaderAndStart(array $rows): array
    {
        $headerIdx = null; $start = null; $colIdx = [];

        // buscar fila con "NOMBRE_COMERCIAL"
        foreach ($rows as $i => $r) {
            foreach ($r as $cell) {
                if ($cell && $this->norm((string)$cell) === 'nombre_comercial') {
                    $headerIdx = $i; break 2;
                }
            }
        }

        if ($headerIdx === null) {
            // Modo legacy por índices
            foreach ($rows as $i => $r) {
                if (is_numeric($r[0] ?? null) && $this->strOrNull($r[1] ?? null)) { $start = $i; break; }
            }
            $colIdx = [
                'nombre_comercial'       => 1,
                'uso'                    => 2,
                'proveedor'              => 3,
                'concentracion'          => 4,
                'composicion_quimica'    => 5,
                'estado_fisico'          => 6,
                'msds'                   => 7,
                'salud'                  => 8,
                'inflamabilidad'         => 9,
                'reactividad'            => 10,
                'nocivo'                 => 11,
                'corrosivo'              => 12,
                'inflamable'             => 13,
                'peligro_salud'          => 14,
                'oxidante'               => 15,
                'peligro_medio_ambiente' => 16,
                'toxico'                 => 17,
                'gas_presion'            => 18,
                'explosivo'              => 19,
                'descripcion'            => 20,
                'ninguno'                => 21,
                'particulas_polvo'       => 22,
                'sustancias_corrosivas'  => 23,
                'sustancias_toxicas'     => 24,
                'sustancias_irritantes'  => 25,
                'tipo_exposicion'        => 26, // <— ajusta si aplica
                'nivel_riesgo'           => 27,
                'medidas_pre_correc'     => 28,
            ];
            return [$start, $headerIdx, $colIdx];
        }

        // Construir mapa por header
        $headers = $rows[$headerIdx];
        foreach ($headers as $idx => $h) {
            if ($h === null || $h === '') continue;
            $norm = $this->norm((string)$h);
            $colIdx[$norm] = $idx;
        }

        // Alias -> canónicos
        $aliases = [
            'nombre_comercial'       => ['nombre_comercial','nombre','producto','quimico','químico'],
            'uso'                    => ['uso'],
            'proveedor'              => ['proveedor','fabricante'],
            'concentracion'          => ['concentracion','concentración'],
            'composicion_quimica'    => ['composicion_quimica','composición_química','composicion','composición'],
            'estado_fisico'          => ['estado_fisico','estado_físico','estado'],
            'msds'                   => ['msds','ficha','ficha_msds','hoja_de_seguridad'],
            'salud'                  => ['salud','riesgo_salud'],
            'inflamabilidad'         => ['inflamabilidad'],
            'reactividad'            => ['reactividad'],
            'nocivo'                 => ['nocivo'],
            'corrosivo'              => ['corrosivo'],
            'inflamable'             => ['inflamable'],
            'peligro_salud'          => ['peligro_salud'],
            'oxidante'               => ['oxidante'],
            'peligro_medio_ambiente' => ['peligro_medio_ambiente','medio_ambiente','ambiental'],
            'toxico'                 => ['toxico','tóxico'],
            'gas_presion'            => ['gas_presion','gas_a_presion','gas a presion','gas a presión'],
            'explosivo'              => ['explosivo'],
            'descripcion'            => ['descripcion','descripción','detalle'],
            'ninguno'                => ['ninguno','sin_riesgo','sin riesgo'],
            'particulas_polvo'       => ['particulas_polvo','partículas_polvo','polvo','particulas','partículas'],
            'sustancias_corrosivas'  => ['sustancias_corrosivas'],
            'sustancias_toxicas'     => ['sustancias_toxicas','sustancias_tóxicas'],
            'sustancias_irritantes'  => ['sustancias_irritantes'],
            'nivel_riesgo'           => ['nivel_riesgo','nivel de riesgo'],
            'medidas_pre_correc'     => ['medidas_pre_correc','medidas_preventivas','medidas_correctivas','medidas'],
            'tipo_exposicion'        => ['tipo_exposicion','tipo de exposicion','tipo de exposición','via de exposicion','vía de exposición','exposicion','exposición'],
        ];

        $resolved = [];
        foreach ($aliases as $canonical => $cands) {
            foreach ($cands as $cand) {
                $k = $this->norm($cand);
                if (isset($colIdx[$k])) { $resolved[$canonical] = $colIdx[$k]; break; }
            }
        }

        // primera fila de datos
        $start = null;
        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $nombre = $this->cellStr($rows[$i], $resolved, 'nombre_comercial');
            if ($nombre) { $start = $i; break; }
        }

        return [$start, $headerIdx, $resolved];
    }

    private function payloadFromRow(array $row, array $colIdx, ?int $idNivel): array
    {
        return [
            'nombre_comercial'        => $this->cellStr($row, $colIdx, 'nombre_comercial'),
            'uso'                     => $this->cellStr($row, $colIdx, 'uso'),
            'proveedor'               => $this->cellStr($row, $colIdx, 'proveedor'),
            'concentracion'           => $this->cellStr($row, $colIdx, 'concentracion'),
            'composicion_quimica'     => $this->cellStr($row, $colIdx, 'composicion_quimica'),
            'estado_fisico'           => $this->cellStr($row, $colIdx, 'estado_fisico'),
            'msds'                    => $this->cellStr($row, $colIdx, 'msds'),
            'salud'                   => $this->cellInt($row, $colIdx, 'salud'),
            'inflamabilidad'          => $this->cellInt($row, $colIdx, 'inflamabilidad'),
            'reactividad'             => $this->cellInt($row, $colIdx, 'reactividad'),
            'nocivo'                  => $this->cellFlag($row, $colIdx, 'nocivo'),
            'corrosivo'               => $this->cellFlag($row, $colIdx, 'corrosivo'),
            'inflamable'              => $this->cellFlag($row, $colIdx, 'inflamable'),
            'peligro_salud'           => $this->cellFlag($row, $colIdx, 'peligro_salud'),
            'oxidante'                => $this->cellFlag($row, $colIdx, 'oxidante'),
            'peligro_medio_ambiente'  => $this->cellFlag($row, $colIdx, 'peligro_medio_ambiente'),
            'toxico'                  => $this->cellFlag($row, $colIdx, 'toxico'),
            'gas_presion'             => $this->cellFlag($row, $colIdx, 'gas_presion'),
            'explosivo'               => $this->cellFlag($row, $colIdx, 'explosivo'),
            'descripcion'             => $this->cellStr($row, $colIdx, 'descripcion'),
            'ninguno'                 => $this->cellFlag($row, $colIdx, 'ninguno'),
            'particulas_polvo'        => $this->cellFlag($row, $colIdx, 'particulas_polvo'),
            'sustancias_corrosivas'   => $this->cellFlag($row, $colIdx, 'sustancias_corrosivas'),
            'sustancias_toxicas'      => $this->cellFlag($row, $colIdx, 'sustancias_toxicas'),
            'sustancias_irritantes'   => $this->cellFlag($row, $colIdx, 'sustancias_irritantes'),
            'id_nivel_riesgo'         => $idNivel,
            'medidas_pre_correc'      => $this->cellStr($row, $colIdx, 'medidas_pre_correc'),
        ];
    }

    /** Parsea "Inhalación, Dérmico / Ocular" -> [id1, id2, id3]; acumula desconocidos en $unknown */
        private function parseExposList(?string $txt, array $exposCatalog, array &$unknown = null): array
    {
        $unknown ??= [];
        if (!$txt) return [];

        // Normaliza conectores y saltos de línea a comas
        $s = (string)$txt;
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = preg_replace('/\s*(y|and)\s*/iu', ',', $s); // … y …
        $s = str_replace(["\n", ";", "|", "/"], ",", $s);

        $parts = array_filter(array_map('trim', explode(",", $s)), fn($v) => $v !== '');
        $ids = [];

        foreach ($parts as $p) {
            // intentos de match (normalizados)
            $raw = trim($p);
            $k   = $this->norm($raw);

            // 1) match directo contra catálogo
            if (isset($exposCatalog[$k])) { $ids[$exposCatalog[$k]] = true; continue; }

            // 2) si empieza con "contacto ", probar sin el prefijo
            if (str_starts_with($k, 'contacto ')) {
                $k2 = substr($k, 9);
                if (isset($exposCatalog[$k2])) { $ids[$exposCatalog[$k2]] = true; continue; }
            }

            // 3) tolerar singular/plural de "via(s) respiratoria(s)"
            $k3 = str_replace(['via respiratoria', 'vias respiratoria', 'via respiratorias'], 'vias respiratorias', $k);
            if (isset($exposCatalog[$k3])) { $ids[$exposCatalog[$k3]] = true; continue; }

            // 4) último intento: quitar dobles espacios
            $k4 = preg_replace('/\s+/', ' ', $k);
            if (isset($exposCatalog[$k4])) { $ids[$exposCatalog[$k4]] = true; continue; }

            // no encontrado → reportarlo
            $unknown[] = $raw;
        }

        return array_values(array_map('intval', array_keys($ids)));
    }

    /** Sincroniza pivot quimico <-> exposiciones; devuelve ['added'=>n,'removed'=>n] */
    private function syncExposicionesPivot(array $syncExpos): array
    {
        $added = 0; $removed = 0;
        if (empty($syncExpos)) return ['added'=>0,'removed'=>0];

        foreach ($syncExpos as $idQuimico => $newSet) {
            $curr = DB::table(self::QXEXP_TABLE)
                ->where(self::QXEXP_QCOL, $idQuimico)
                ->pluck(self::QXEXP_EXPCOL)
                ->map(fn($v) => (int)$v)
                ->all();

            $currSet = array_flip($curr);
            $newSet  = array_values(array_unique(array_map('intval', $newSet)));

            $toAdd = [];
            foreach ($newSet as $eid) if (!isset($currSet[$eid])) $toAdd[] = $eid;

            $toDel = [];
            foreach ($curr as $eid) if (!in_array($eid, $newSet, true)) $toDel[] = $eid;

            if (!empty($toAdd)) {
                $rows = array_map(fn($eid) => [
                    self::QXEXP_QCOL   => $idQuimico,
                    self::QXEXP_EXPCOL => $eid
                ], $toAdd);
                DB::table(self::QXEXP_TABLE)->insert($rows);
                $added += count($toAdd);
            }
            if (!empty($toDel)) {
                DB::table(self::QXEXP_TABLE)
                    ->where(self::QXEXP_QCOL, $idQuimico)
                    ->whereIn(self::QXEXP_EXPCOL, $toDel)
                    ->delete();
                $removed += count($toDel);
            }
        }

        return ['added'=>$added,'removed'=>$removed];
        }

    /* ==== utilidades de celdas/normalización/flags/diffs ==== */

    private function cellStr(array $row, array $colIdx, string $name): ?string
    {
        $idx = $colIdx[$name] ?? null;
        $v = $idx === null ? null : ($row[$idx] ?? null);
        return $this->strOrNull($v);
    }
    private function cellInt(array $row, array $colIdx, string $name): int
    {
        $idx = $colIdx[$name] ?? null;
        $v = $idx === null ? null : ($row[$idx] ?? null);
        return $this->intOrZero($v);
    }
    private function cellFlag(array $row, array $colIdx, string $name): int
    {
        $idx = $colIdx[$name] ?? null;
        $v = $idx === null ? null : ($row[$idx] ?? null);
        return $this->flag($v);
    }

    private function norm(?string $v): string
    {
        $v = mb_strtolower(trim((string)$v), 'UTF-8');
        $v = preg_replace('/\s+/u', ' ', $v);
        $trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n','Ü'=>'u'];
        return strtr($v, $trans);
    }

    private function strOrNull($v): ?string
    {
        $s = is_null($v) ? null : trim((string)$v);
        if ($s === '' || $s === '-' || mb_strtolower($s,'UTF-8') === 'nan') return null;
        return $s;
    }

    private function intOrZero($v): int
    {
        if (is_numeric($v)) return (int)$v;
        $s = mb_strtolower(trim((string)$v), 'UTF-8');
        if ($s === 'nan' || $s === '' || $s === '-') return 0;
        if (ctype_digit($s)) return (int)$s;
        return 0;
    }

    private function flag($v): int
    {
        if (is_null($v)) return 0;
        $s = mb_strtolower(trim((string)$v), 'UTF-8');
        $trues = ['x','si','sí','1','true','verdadero','sí','y','yes'];
        return in_array($s, $trues, true) ? 1 : (is_numeric($s) && (int)$s > 0 ? 1 : 0);
    }

    private function diffAssocLoose(array $new, array $old): array
    {
        $dirty = [];
        foreach ($new as $k => $vNew) {
            $vOld = $old[$k] ?? null;

            if (is_string($vNew)) $vNew = (trim($vNew) === '') ? null : $vNew;
            if (is_string($vOld)) $vOld = (trim($vOld) === '') ? null : $vOld;

            if (is_int($vNew) || is_numeric($vNew)) {
                $vNew = is_null($vNew) ? null : (int)$vNew;
                $vOld = is_null($vOld) ? null : (int)$vOld;
            }

            if ($vNew !== $vOld) $dirty[$k] = $new[$k];
        }
        return $dirty;
    }
}
