<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EvaluacionRiesgoImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'excel_file'     => 'required|file|mimes:xls,xlsx,xlsm,csv',
            'delete_missing' => 'nullable|boolean',
        ]);

        $file     = $request->file('excel_file');
        $doDelete = (bool) $request->boolean('delete_missing');

        $normalize = function (?string $s) {
            if ($s === null) return '';
            $s = trim($s);
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return mb_strtoupper($s);
        };

        $reader = IOFactory::createReaderForFile($file->getPathName());
        $reader->setReadDataOnly(true);
        $ss = $reader->load($file->getPathName());
        $ws = $ss->getSheetByName('EVALUACION_DE_RIESGOS_MASIVO');

        if (!$ws) {
            return back()->withErrors(['excel_file' => 'La hoja "EVALUACION_DE_RIESGOS_MASIVO" no existe en el archivo.']);
        }

        $rows = $ws->toArray(null, true, true, true);
        if (empty($rows)) {
            return back()->withErrors(['excel_file' => 'La hoja está vacía.']);
        }

        $headerRowIndex = null; $puestoCol = null;
        foreach ($rows as $i => $cols) {
            foreach ($cols as $L => $val) {
                if (strpos($normalize((string)$val), 'PUESTO DE TRABAJO') !== false) {
                    $headerRowIndex = (int)$i;
                    $puestoCol = $L;
                    break 2;
                }
            }
        }
        if (!$headerRowIndex || !$puestoCol) {
            return back()->withErrors(['excel_file' => 'No encontré la columna "PUESTO DE TRABAJO".']);
        }

        $riskTitleRow = max(1, $headerRowIndex - 1);
        $subHeaderRow = $headerRowIndex;

        $letters   = array_keys($rows[$subHeaderRow]);
        $puestoIdx = array_search($puestoCol, $letters, true);

        $riskMap = [];
        for ($i = $puestoIdx + 1; $i < count($letters); $i++) {
            $L1 = $letters[$i];
            $L2 = $letters[$i + 1] ?? null;

            $h1 = isset($rows[$subHeaderRow][$L1]) ? $normalize((string)$rows[$subHeaderRow][$L1]) : '';
            $h2 = $L2 ? $normalize((string)($rows[$subHeaderRow][$L2] ?? '')) : '';

            if ($h1 === 'PROBABILIDAD' && $L2 && $h2 === 'CONSECUENCIA') {
                $riskName = (string)($rows[$riskTitleRow][$L1] ?? '');
                $riskMap[$L1] = [
                    'riesgo'      => trim($riskName),
                    'riesgo_norm' => $normalize($riskName),
                    'prob_col'    => $L1,
                    'cons_col'    => $L2,
                ];
                $i++;
            }
        }
        if (!$riskMap) {
            return back()->withErrors(['excel_file' => 'No identifiqué pares PROBABILIDAD/CONSECUENCIA.']);
        }

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

        $probMap = DB::table('probabilidad')
            ->select('id_probabilidad', 'probabilidad')
            ->get()
            ->mapWithKeys(fn($r) => [$normalize($r->probabilidad) => (int)$r->id_probabilidad])
            ->toArray();

        $consMap = DB::table('consecuencia')
            ->select('id_consecuencia', 'consecuencia')
            ->get()
            ->mapWithKeys(fn($r) => [$normalize($r->consecuencia) => (int)$r->id_consecuencia])
            ->toArray();

        $nivelMap = [];
        $vals = DB::table('valoracion_riesgo')
            ->select('id_probabilidad', 'id_consecuencia', 'id_nivel_riesgo')
            ->get();
        foreach ($vals as $v) {
            $nivelMap[(int)$v->id_probabilidad][(int)$v->id_consecuencia] = (int)$v->id_nivel_riesgo;
        }

        $inserted = 0; $updated = 0; $deleted = 0;
        $warnings = [];
        $puestosImportados = [];

        DB::beginTransaction();
        try {
            $dataStart = $subHeaderRow + 1;

            for ($r = $dataStart; $r <= count($rows); $r++) {
                $puestoName = trim((string)($rows[$r][$puestoCol] ?? ''));
                if ($puestoName === '' || strtoupper($puestoName) === 'N/A') continue;

                $puestoId = $puestos[$normalize($puestoName)] ?? null;
                if (!$puestoId) { $warnings[] = "Puesto no encontrado: \"$puestoName\" (fila $r)"; continue; }

                if (!isset($puestosImportados[$puestoId])) $puestosImportados[$puestoId] = [];

                foreach ($riskMap as $m) {
                    $probTxt = trim((string)($rows[$r][$m['prob_col']] ?? ''));
                    $consTxt = trim((string)($rows[$r][$m['cons_col']] ?? ''));

                    if ($probTxt === '' && $consTxt === '') continue;

                    $idRiesgo = $riesgos[$m['riesgo_norm']] ?? null;
                    if (!$idRiesgo) { $warnings[] = "Riesgo no encontrado: \"{$m['riesgo']}\" (fila $r)"; continue; }

                    $idProb = $probMap[$normalize($probTxt)] ?? null;
                    $idCons = $consMap[$normalize($consTxt)] ?? null;

                    if (!$idProb || !$idCons) {
                        $warnings[] = "Prob/Cons no mapeada(s) (P:$probTxt, C:$consTxt) en \"$puestoName\" → \"{$m['riesgo']}\" (fila $r)";
                        continue;
                    }

                    $idNivel = $nivelMap[$idProb][$idCons] ?? null;
                    if (!$idNivel) {
                        $warnings[] = "Sin valoración para Prob:$probTxt / Cons:$consTxt (puesto \"$puestoName\", riesgo \"{$m['riesgo']}\")";
                        continue;
                    }

                    $puestosImportados[$puestoId][$idRiesgo] = true;

                    $existing = DB::table('evaluacion_riesgo')
                        ->where('id_puesto_trabajo_matriz', $puestoId)
                        ->where('id_riesgo', $idRiesgo)
                        ->first(['id_evaluacion_riesgo','id_probabilidad','id_consecuencia','id_nivel_riesgo']);

                    if ($existing) {
                        if ((int)$existing->id_probabilidad !== $idProb
                         || (int)$existing->id_consecuencia !== $idCons
                         || (int)$existing->id_nivel_riesgo !== $idNivel) {
                            DB::table('evaluacion_riesgo')
                                ->where('id_evaluacion_riesgo', $existing->id_evaluacion_riesgo)
                                ->update([
                                    'id_probabilidad' => $idProb,
                                    'id_consecuencia' => $idCons,
                                    'id_nivel_riesgo' => $idNivel,
                                ]);
                            $updated++;
                        }
                    } else {
                        DB::table('evaluacion_riesgo')->insert([
                            'id_puesto_trabajo_matriz' => $puestoId,
                            'id_riesgo'                => $idRiesgo,
                            'id_probabilidad'          => $idProb,
                            'id_consecuencia'          => $idCons,
                            'id_nivel_riesgo'          => $idNivel,
                        ]);
                        $inserted++;
                    }
                }
            }

            if ($doDelete && $puestosImportados) {
                foreach ($puestosImportados as $puestoId => $mapRiesgos) {
                    $idsPresentes = array_map('intval', array_keys($mapRiesgos));
                    if (empty($idsPresentes)) continue;

                    $deleted += DB::table('evaluacion_riesgo')
                        ->where('id_puesto_trabajo_matriz', $puestoId)
                        ->whereNotIn('id_riesgo', $idsPresentes)
                        ->delete();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['excel_file' => 'Error importando: '.$e->getMessage()]);
        }

        $msg = "Importación OK. Insertados: $inserted | Actualizados: $updated";
        if ($doDelete) $msg .= " | Eliminados: $deleted";
        if ($warnings) {
            $msg .= ' | Avisos: '.implode(' | ', array_slice($warnings, 0, 25));
            if (count($warnings) > 25) $msg .= ' ...';
        }

        return back()->with('status', $msg);
    }
}
