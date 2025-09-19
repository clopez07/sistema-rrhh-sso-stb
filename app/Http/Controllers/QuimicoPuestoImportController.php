<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuimicoPuestoImportController extends Controller
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
        $ws = $ss->getSheetByName('QUIMICOS_POR_PUESTO')
           ?? $ss->getSheetByName('QUIMICOS POR PUESTO')
           ?? $ss->getActiveSheet();

        if (!$ws) {
            return back()->withErrors(['excel_file' => 'No pude abrir la hoja con los químicos por puesto.']);
        }

        $rows = $ws->toArray(null, true, true, true);
        if (empty($rows)) {
            return back()->withErrors(['excel_file' => 'La hoja está vacía.']);
        }

        $headerRowIndex = null; $puestoCol = null; $quimicosCol = null;
        foreach ($rows as $i => $cols) {
            foreach ($cols as $L => $val) {
                $v = $normalize((string)$val);
                if ($puestoCol === null && str_contains($v, 'PUESTO DE TRABAJO')) {
                    $puestoCol = $L; $headerRowIndex = (int)$i;
                }
                if ($quimicosCol === null && ($v === 'QUIMICOS' || $v === 'QUIMICOS' || $v === 'QUÍMICOS')) {
                    $quimicosCol = $L; $headerRowIndex = (int)$i;
                }
            }
        }

        if (!$puestoCol || !$quimicosCol || !$headerRowIndex) {
            return back()->withErrors(['excel_file' => 'No encontré las columnas "PUESTO DE TRABAJO" y/o "QUIMICOS".']);
        }

        $dataStart = $headerRowIndex + 1;

        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz')
            ->get()
            ->mapWithKeys(fn($r)=>[$normalize($r->puesto_trabajo_matriz)=>(int)$r->id_puesto_trabajo_matriz])
            ->toArray();

        $quimicos = DB::table('quimico')
            ->select('id_quimico','nombre_comercial')
            ->get()
            ->mapWithKeys(fn($r)=>[$normalize($r->nombre_comercial)=>(int)$r->id_quimico])
            ->toArray();

        if (isset($quimicos['NINGUNO'])) {
            $quimicos['NINGUN']  = $quimicos['NINGUNO'];
            $quimicos['NINGUNA'] = $quimicos['NINGUNO'];
        }

        $splitList = function (string $txt) use ($normalize) {
            $parts = preg_split('/[,;\r\n\|\t]+/u', $txt) ?: [];
            $out = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $out[$normalize($p)] = $p;
            }
            return $out;
        };

        $inserted = 0; $deleted = 0;
        $warnings = [];

        $presentByPuesto = [];
        $touchedPuesto   = [];

        DB::beginTransaction();
        try {
            for ($r = $dataStart; $r <= count($rows); $r++) {
                $puestoName = trim((string)($rows[$r][$puestoCol] ?? ''));
                if ($puestoName === '' || strtoupper($puestoName) === 'N/A') continue;

                $puestoId = $puestos[$normalize($puestoName)] ?? null;
                if (!$puestoId) { $warnings[] = "Puesto no encontrado: \"$puestoName\" (fila $r)"; continue; }

                $cell = (string)($rows[$r][$quimicosCol] ?? '');
                if ($cell === '') continue;

                $tokens = $splitList($cell);

                if (!isset($presentByPuesto[$puestoId])) $presentByPuesto[$puestoId] = [];
                if (!isset($touchedPuesto[$puestoId]))   $touchedPuesto[$puestoId]   = false;

                foreach ($tokens as $norm => $original) {
                    $idQ = $quimicos[$norm] ?? null;

                    if (!$idQ) {
                        $warnings[] = "Químico no encontrado en 'quimico': \"$original\" (puesto \"$puestoName\", fila $r)";
                        continue;
                    }

                    $affected = DB::table('quimico_puesto')->insertOrIgnore([
                        'id_quimico'               => $idQ,
                        'id_puesto_trabajo_matriz' => $puestoId,
                    ]);
                    $inserted += (int)$affected;

                    $presentByPuesto[$puestoId][$idQ] = true;
                    $touchedPuesto[$puestoId] = true;
                }
            }

            if ($doDelete && $presentByPuesto) {
                foreach ($presentByPuesto as $puestoId => $map) {
                    if (empty($touchedPuesto[$puestoId])) {
                        continue;
                    }

                    $idsPresentes = array_map('intval', array_keys($map));

                    if (empty($idsPresentes)) {
                        $deleted += DB::table('quimico_puesto')
                            ->where('id_puesto_trabajo_matriz', $puestoId)
                            ->delete();
                    } else {
                        $deleted += DB::table('quimico_puesto')
                            ->where('id_puesto_trabajo_matriz', $puestoId)
                            ->whereNotIn('id_quimico', $idsPresentes)
                            ->delete();
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['excel_file' => 'Error importando: '.$e->getMessage()]);
        }

        $msg = "Importación OK. Nuevas relaciones: $inserted";
        if ($doDelete) $msg .= " | Eliminadas: $deleted";
        if ($warnings) {
            $msg .= ' | Avisos: '.implode(' | ', array_slice($warnings, 0, 25));
            if (count($warnings) > 25) $msg .= ' ...';
        }

        return back()->with('status', $msg);
    }
}
