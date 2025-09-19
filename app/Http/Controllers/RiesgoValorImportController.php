<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RiesgoValorImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'excel_file'     => 'required|file|mimes:xls,xlsx,xlsm,csv',
            'delete_missing' => 'nullable|boolean',
        ]);

        $file      = $request->file('excel_file');
        $doDelete  = (bool) $request->boolean('delete_missing');

        // --- helpers ---
        $normalize = function (?string $s) {
            if ($s === null) return '';
            $s = trim($s);
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return mb_strtoupper($s);
        };

        // --- leer excel: hoja DATOS_MATRIZ_RIESGOS ---
        $reader = IOFactory::createReaderForFile($file->getPathName());
        $reader->setReadDataOnly(true);
        $ss = $reader->load($file->getPathName());
        $ws = $ss->getSheetByName('DATOS_MATRIZ_RIESGOS_MASIVO');

        if (!$ws) {
            return back()->withErrors([
                'excel_file' => 'La hoja "DATOS_MATRIZ_RIESGOS_MASIVO" no existe en el archivo.',
            ]);
        }

        $rows = $ws->toArray(null, true, true, true); // A,B,C,... como claves
        if (empty($rows)) {
            return back()->withErrors(['excel_file' => 'La hoja está vacía.']);
        }

        // --- localizar fila de encabezados (donde aparece "Puesto de trabajo analizado") ---
        $headerRowIndex = null;
        $puestoCol = null;

        foreach ($rows as $i => $cols) {
            foreach ($cols as $L => $val) {
                if (strpos($normalize((string)$val), $normalize('Puesto de trabajo analizado')) !== false) {
                    $headerRowIndex = (int)$i;
                    $puestoCol = $L;
                    break 2;
                }
            }
        }

        if (!$headerRowIndex || !$puestoCol) {
            return back()->withErrors(['excel_file' => 'No encontré la columna "Puesto de trabajo analizado".']);
        }

        // doble encabezado: categoría arriba, riesgo abajo
        $catRow  = max(1, $headerRowIndex - 1);
        $riskRow = $headerRowIndex;
        $letters = array_keys($rows[$riskRow]);

        // --- detectar columnas de riesgo y su obs inmediata ---
        $isObs = function ($t) use ($normalize) {
            $t = $normalize((string)$t);
            return str_contains($t, 'OBSERVACION') || str_contains($t, 'OBSERVACIONES');
        };

        $riskMap = []; // 'J' => ['categoria'=>..., 'riesgo'=>..., 'riesgo_norm'=>..., 'obs_col'=>'K']
        $puestoIdx = array_search($puestoCol, $letters, true);

        for ($i = 0; $i < count($letters); $i++) {
            $L = $letters[$i];

            if ($puestoIdx !== null && $i <= $puestoIdx) continue;

            $riskName = isset($rows[$riskRow][$L]) ? trim((string)$rows[$riskRow][$L]) : '';
            if ($riskName === '' || $isObs($riskName)) continue;

            $next   = $letters[$i + 1] ?? null;
            $obsCol = ($next && isset($rows[$riskRow][$next]) && $isObs($rows[$riskRow][$next])) ? $next : null;

            $riskMap[$L] = [
                'categoria'   => isset($rows[$catRow][$L]) ? trim((string)$rows[$catRow][$L]) : '',
                'riesgo'      => $riskName,
                'riesgo_norm' => $normalize($riskName),
                'obs_col'     => $obsCol,
            ];
        }

        if (!$riskMap) {
            return back()->withErrors(['excel_file' => 'No identifiqué columnas de riesgos.']);
        }

        // --- diccionarios en memoria ---
        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz', 'puesto_trabajo_matriz')
            ->get()
            ->mapWithKeys(fn($r) => [$normalize($r->puesto_trabajo_matriz) => (int)$r->id_puesto_trabajo_matriz])
            ->toArray();

        $riesgos = DB::table('riesgo')
            ->select('id_riesgo', 'nombre_riesgo')
            ->get()
            ->mapWithKeys(fn($r) => [$normalize($r->nombre_riesgo) => (int)$r->id_riesgo])
            ->toArray();

        // contadores y estructuras para delete_missing
        $inserted = 0;
        $updated  = 0;
        $deleted  = 0;
        $warnings = [];
        /** @var array<int,array<int,bool>> $puestosImportados  puestoId => [id_riesgo => true] */
        $puestosImportados = [];

        DB::beginTransaction();
        try {
            $dataStart = $headerRowIndex + 1;

            for ($r = $dataStart; $r <= count($rows); $r++) {
                $puestoName = isset($rows[$r][$puestoCol]) ? trim((string)$rows[$r][$puestoCol]) : '';
                if ($puestoName === '' || strtoupper($puestoName) === 'N/A') continue;

                $puestoId = $puestos[$normalize($puestoName)] ?? null;
                if (!$puestoId) {
                    $warnings[] = "Puesto no encontrado: \"$puestoName\" (fila $r)";
                    continue;
                }

                // para borrar faltantes luego
                if (!isset($puestosImportados[$puestoId])) $puestosImportados[$puestoId] = [];

                foreach ($riskMap as $col => $info) {
                    $valor = isset($rows[$r][$col]) ? trim((string)$rows[$r][$col]) : '';
                    $obs   = $info['obs_col'] ? trim((string)($rows[$r][$info['obs_col']] ?? '')) : '';

                    // si no hay nada, no insertamos/actualizamos (pero tampoco lo marcamos como presente)
                    if ($valor === '' && $obs === '') continue;

                    $idRiesgo = $riesgos[$info['riesgo_norm']] ?? null;
                    if (!$idRiesgo) {
                        $warnings[] = "Riesgo no encontrado: \"{$info['riesgo']}\" (col $col, fila $r)";
                        continue;
                    }

                    // marcar presente para este puesto
                    $puestosImportados[$puestoId][$idRiesgo] = true;

                    // --- UPSERT manual para contar insert/update ---
                    $existing = DB::table('riesgo_valor')
                        ->where('id_puesto_trabajo_matriz', $puestoId)
                        ->where('id_riesgo', $idRiesgo)
                        ->first(['id_riesgo_valor', 'valor', 'observaciones']);

                    $newValor = ($valor === '') ? null : $valor;
                    $newObs   = ($obs   === '') ? null : $obs;

                    if ($existing) {
                        $oldValor = $existing->valor;
                        $oldObs   = $existing->observaciones;
                        if ((string)($oldValor ?? '') !== (string)($newValor ?? '') ||
                            (string)($oldObs ?? '')   !== (string)($newObs ?? '')) {
                            DB::table('riesgo_valor')
                                ->where('id_riesgo_valor', $existing->id_riesgo_valor)
                                ->update([
                                    'valor'         => $newValor,
                                    'observaciones' => $newObs,
                                ]);
                            $updated++;
                        }
                        // si no cambió, no contamos
                    } else {
                        DB::table('riesgo_valor')->insert([
                            'id_puesto_trabajo_matriz' => $puestoId,
                            'id_riesgo'                => $idRiesgo,
                            'valor'                    => $newValor,
                            'observaciones'            => $newObs,
                        ]);
                        $inserted++;
                    }
                }
            }

            // --- borrar faltantes (opcional) ---
            if ($doDelete && $puestosImportados) {
                foreach ($puestosImportados as $puestoId => $mapRiesgos) {
                    $idsPresentes = array_map('intval', array_keys($mapRiesgos));
                    if (empty($idsPresentes)) {
                        // si para un puesto no vino ningún riesgo con dato, mejor no borrar nada
                        continue;
                    }
                    // borrar los que no vinieron en el Excel (solo de ese puesto)
                    $deleted += DB::table('riesgo_valor')
                        ->where('id_puesto_trabajo_matriz', $puestoId)
                        ->whereNotIn('id_riesgo', $idsPresentes)
                        ->delete();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['excel_file' => 'Error importando: ' . $e->getMessage()]);
        }

        $msg = "Importación OK. Insertados: $inserted | Actualizados: $updated";
        if ($doDelete) $msg .= " | Eliminados: $deleted";
        if ($warnings) {
            $msg .= ' | Avisos: ' . implode(' | ', array_slice($warnings, 0, 25));
            if (count($warnings) > 25) $msg .= ' ...';
        }

        return back()->with('status', $msg);
    }
}
