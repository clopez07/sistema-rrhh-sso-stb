<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuimicosImportController extends Controller
{
    const ACTIVO   = 1;
    const INACTIVO = 0;

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());

        $sheet = $spreadsheet->getSheetByName('QUIMICOS');
        if (!$sheet) {
            return back()->withErrors(['excel_file' => 'No se encontró la hoja "QUIMICOS".']);
        }

        $rows = $sheet->toArray(null, false, false, false);

        $niveles = DB::table('nivel_riesgo')
            ->select('id_nivel_riesgo','nivel_riesgo')
            ->get()
            ->reduce(function($carry, $n){
                $carry[self::norm($n->nivel_riesgo)] = $n->id_nivel_riesgo;
                return $carry;
            }, []);

        $start = null;
        foreach ($rows as $i => $r) {
            if (is_numeric($r[0] ?? null) && self::strOrNull($r[1] ?? null)) { $start = $i; break; }
        }
        if ($start === null) {
            return back()->withErrors(['excel_file' => 'No se detectaron filas de datos en "QUIMICOS".']);
        }

        $existentes = DB::table('quimico')->get();
        $mapExist = [];
        foreach ($existentes as $q) {
            $key = self::norm($q->nombre_comercial);
            $mapExist[$key] = $q;
        }

        $insertados = 0;
        $actualizados = 0;
        $sinCambios = 0;
        $desactivados = 0;
        $duplicados = 0;

        $vistos = [];

        $toInsert = [];
        $chunkSize = 300;

        DB::transaction(function () use (
            $rows, $start, $niveles, &$mapExist, &$vistos, &$toInsert, $chunkSize,
            &$insertados, &$actualizados, &$sinCambios, &$duplicados
        ) {
            foreach (array_slice($rows, $start) as $row) {
                $nombre = self::strOrNull($row[1] ?? null);
                if (!$nombre) continue;

                $key = self::norm($nombre);

                if (isset($vistos[$key])) { $duplicados++; continue; }
                $vistos[$key] = true;

                $payload = self::payloadFromRow($row, $niveles);

                $payload['estado'] = self::ACTIVO;

                if (isset($mapExist[$key])) {
                    $existing = $mapExist[$key];

                    $existingSubset = [];
                    foreach ($payload as $k => $v) {
                        $existingSubset[$k] = $existing->$k ?? null;
                    }

                    $dirty = self::diffAssocLoose($payload, $existingSubset);

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
                } else {
                    // Nuevo → insertar con estado ACTIVO
                    $toInsert[] = $payload;
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
        });

        // Desactivar los que no vinieron en el Excel
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
        return back()->with('status', $msg);
    }

    /* ================= Helpers ================= */

    private static function payloadFromRow(array $row, array $niveles): array
    {
        $nivelTxt = self::strOrNull($row[27] ?? null);
        $idNivel = $nivelTxt ? ($niveles[self::norm($nivelTxt)] ?? null) : null;

        return [
            'nombre_comercial'        => self::strOrNull($row[1]  ?? null),
            'uso'                     => self::strOrNull($row[2]  ?? null),
            'proveedor'               => self::strOrNull($row[3]  ?? null),
            'concentracion'           => self::strOrNull($row[4]  ?? null),
            'composicion_quimica'     => self::strOrNull($row[5]  ?? null),
            'estado_fisico'           => self::strOrNull($row[6]  ?? null),
            'msds'                    => self::strOrNull($row[7]  ?? null),
            'salud'                   => self::intOrZero($row[8]  ?? null),
            'inflamabilidad'          => self::intOrZero($row[9]  ?? null),
            'reactividad'             => self::intOrZero($row[10] ?? null),
            'nocivo'                  => self::flag($row[11] ?? null),
            'corrosivo'               => self::flag($row[12] ?? null),
            'inflamable'              => self::flag($row[13] ?? null),
            'peligro_salud'           => self::flag($row[14] ?? null),
            'oxidante'                => self::flag($row[15] ?? null),
            'peligro_medio_ambiente'  => self::flag($row[16] ?? null),
            'toxico'                  => self::flag($row[17] ?? null),
            'gas_presion'             => self::flag($row[18] ?? null),
            'explosivo'               => self::flag($row[19] ?? null),
            'descripcion'             => self::strOrNull($row[20] ?? null),
            'ninguno'                 => self::flag($row[21] ?? null),
            'particulas_polvo'        => self::flag($row[22] ?? null),
            'sustancias_corrosivas'   => self::flag($row[23] ?? null),
            'sustancias_toxicas'      => self::flag($row[24] ?? null),
            'sustancias_irritantes'   => self::flag($row[25] ?? null),
            'id_nivel_riesgo'         => $idNivel,
            'medidas_pre_correc'      => self::strOrNull($row[28] ?? null),
            // 'estado' lo fija el llamador (ACTIVO al venir en Excel)
        ];
    }

    private static function norm(?string $v): string
    {
        $v = mb_strtolower(trim((string)$v), 'UTF-8');
        $v = preg_replace('/\s+/u', ' ', $v);
        $trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n','Ü'=>'u'];
        return strtr($v, $trans);
    }

    private static function strOrNull($v): ?string
    {
        $s = is_null($v) ? null : trim((string)$v);
        if ($s === '' || $s === '-' || mb_strtolower($s,'UTF-8') === 'nan') return null;
        return $s;
    }

    private static function intOrZero($v): int
    {
        if (is_numeric($v)) return (int)$v;
        $s = mb_strtolower(trim((string)$v), 'UTF-8');
        if ($s === 'nan' || $s === '' || $s === '-') return 0;
        if (ctype_digit($s)) return (int)$s;
        return 0;
    }

    private static function flag($v): int
    {
        if (is_null($v)) return 0;
        $s = mb_strtolower(trim((string)$v), 'UTF-8');
        $trues = ['x','si','sí','1','true','verdadero','sí'];
        return in_array($s, $trues, true) ? 1 : (is_numeric($s) && (int)$s > 0 ? 1 : 0);
    }

    // Devuelve solo los campos que realmente cambian (normalizando tipos y vacíos)
    private static function diffAssocLoose(array $new, array $old): array
    {
        $dirty = [];
        foreach ($new as $k => $vNew) {
            $vOld = $old[$k] ?? null;

            if (is_string($vNew)) $vNew = (trim($vNew) === '') ? null : $vNew;
            if (is_string($vOld)) $vOld = (trim($vOld) === '') ? null : $vOld;

            // ints
            if (is_int($vNew) || is_numeric($vNew)) {
                $vNew = is_null($vNew) ? null : (int)$vNew;
                $vOld = is_null($vOld) ? null : (int)$vOld;
            }

            if ($vNew !== $vOld) {
                $dirty[$k] = $new[$k];
            }
        }
        return $dirty;
    }
}
