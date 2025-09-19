<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MedidasRiesgoImportController extends Controller
{
    private const SHEET_NAME       = 'MEDIDAS_POR_RIESGO';
    private const START_ROW        = 2;

    private const COL_RIESGO       = 'A';
    private const COL_AREA         = 'B';
    private const COL_EPP          = 'C';
    private const COL_CAP          = 'D';
    private const COL_SEN          = 'E';
    private const COL_OTRAS        = 'F';

    private const ALLOW_MULTI      = false;

    private const DEFAULT_TIPO_PROTECCION = 1;
    private const DEFAULT_CODIGO_MARCA    = '';

    public function import(Request $request)
    {
        $request->validate([
            'excel_file'     => 'required|file|mimes:xls,xlsx,xlsm,csv',
            'delete_missing' => 'nullable|boolean',
        ]);

        $file     = $request->file('excel_file');
        $doDelete = (bool)$request->boolean('delete_missing');

        $norm = function (?string $s) {
            $s = (string)$s;
            $s = preg_replace('/\x{FEFF}/u', '', $s);
            $s = str_replace("\xC2\xA0", ' ', $s);
            $s = preg_replace('/[\x{2000}-\x{200D}\x{2060}\x{00AD}]/u', '', $s);
            $s = str_replace(["\r\n","\r"], "\n", $s);
            $s = trim($s);
            $s = preg_replace('/\s+/u', ' ', $s);
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($ascii !== false) $s = $ascii;
            $s = mb_strtoupper($s, 'UTF-8');
            $s = preg_replace('/[^A-Z0-9 ]/u', '', $s);
            return $s;
        };

        $reader = IOFactory::createReaderForFile($file->getPathname());
        $reader->setReadDataOnly(true);
        $ss = $reader->load($file->getPathname());

        $ws = $ss->getSheetByName(self::SHEET_NAME) ?: $ss->getSheet(0);
        if (!$ws) return back()->withErrors(['excel_file' => 'No pude abrir la hoja de Excel.']);

        $rows = $ws->toArray(null, true, true, true);
        if (!$rows) return back()->withErrors(['excel_file' => 'La hoja está vacía.']);

        $riesgos = DB::table('riesgo')->select('id_riesgo','nombre_riesgo')->get()
            ->mapWithKeys(fn($r)=>[$norm($r->nombre_riesgo)=>(int)$r->id_riesgo])->toArray();

        $areas = DB::table('area')->select('id_area','area')->get()
            ->mapWithKeys(fn($r)=>[$norm($r->area)=>(int)$r->id_area])->toArray();

        $epps = DB::table('epp')->select('id_epp','equipo')->get()
            ->mapWithKeys(fn($r)=>[$norm($r->equipo)=>(int)$r->id_epp])->toArray();

        $caps = DB::table('capacitacion')->select('id_capacitacion','capacitacion')->get()
            ->mapWithKeys(fn($r)=>[$norm($r->capacitacion)=>(int)$r->id_capacitacion])->toArray();

        $sens = DB::table('senalizacion')->select('id_senalizacion','senalizacion')->get()
            ->mapWithKeys(fn($r)=>[$norm($r->senalizacion)=>(int)$r->id_senalizacion])->toArray();

        $otras = DB::table('otras_medidas')->select('id_otras_medidas','otras_medidas')->get()
            ->mapWithKeys(fn($r)=>[$norm($r->otras_medidas)=>(int)$r->id_otras_medidas])->toArray();

        $safeInsert = function(string $table, array $payload) {
            try { return DB::table($table)->insertGetId($payload); }
            catch (\Throwable $e) { \Log::warning("No pude crear en {$table}", ['e'=>$e->getMessage(),'payload'=>$payload]); return null; }
        };

        $ensure = function (string $table, string $value) use (&$areas,&$epps,&$caps,&$sens,&$otras,$norm,$safeInsert) {
            $key = $norm($value);
            switch ($table) {
                case 'area':
                    if (!isset($areas[$key])) {
                        $id = $safeInsert('area', ['area'=>$value]);
                        if ($id) $areas[$key] = (int)$id;
                    }
                    return $areas[$key] ?? null;
                case 'epp':
                    if (!isset($epps[$key])) {
                        $id = $safeInsert('epp', [
                            'equipo'             => $value,
                            'codigo'             => self::DEFAULT_CODIGO_MARCA,
                            'marca'              => self::DEFAULT_CODIGO_MARCA,
                            'id_tipo_proteccion' => self::DEFAULT_TIPO_PROTECCION,
                        ]);
                        if ($id) $epps[$key] = (int)$id;
                    }
                    return $epps[$key] ?? null;
                case 'cap':
                    if (!isset($caps[$key])) {
                        $id = $safeInsert('capacitacion', ['capacitacion'=>$value]);
                        if ($id) $caps[$key] = (int)$id;
                    }
                    return $caps[$key] ?? null;
                case 'sen':
                    if (!isset($sens[$key])) {
                        $id = $safeInsert('senalizacion', ['senalizacion'=>$value]);
                        if ($id) $sens[$key] = (int)$id;
                    }
                    return $sens[$key] ?? null;
                case 'otras':
                    if (!isset($otras[$key])) {
                        $id = $safeInsert('otras_medidas', ['otras_medidas'=>$value]);
                        if ($id) $otras[$key] = (int)$id;
                    }
                    return $otras[$key] ?? null;
            }
            return null;
        };

        $cell = function(array $rows, int $r, string $col): string {
            return trim((string)($rows[$r][$col] ?? ''));
        };

        $split = function (string $txt): array {
            if (!self::ALLOW_MULTI) return [$txt];
            if (trim($txt) === '' || strtoupper(trim($txt)) === 'N/A') return [];
            $s = str_replace(["\r\n","\r"], "\n", $txt);
            $s = str_ireplace([' • ', '•', ' / ', '/', ' y ', ' - ', '–', '—'], ',', $s);
            $s = str_replace([";", "|", "\t", "\n"], ",", $s);
            $s = preg_replace('/,\s*,+/', ',', $s);
            $parts = array_map('trim', explode(',', $s));
            return array_values(array_filter($parts, fn($p)=>$p!==''));
        };

        $inserted = 0; $deleted = 0;
        $warnings = [];
        $present = ['epp'=>[], 'cap'=>[], 'sen'=>[], 'otras'=>[]];

        DB::beginTransaction();
        try {
            $lastRow = $ws->getHighestRow();

            for ($r = self::START_ROW; $r <= $lastRow; $r++) {
                $riesgoName = $cell($rows, $r, self::COL_RIESGO);
                if ($riesgoName === '' || strtoupper($riesgoName) === 'N/A') continue;

                $idRiesgo = $riesgos[$norm($riesgoName)] ?? null;
                if (!$idRiesgo) { 
                    $warnings[] = "Riesgo no encontrado: \"$riesgoName\" (fila $r)";
                    \Log::debug('RIESGO SIN MATCH', ['raw'=>$riesgoName,'hex'=>bin2hex($riesgoName),'norm'=>$norm($riesgoName)]);
                    continue; 
                }

                $areaCell = $cell($rows, $r, self::COL_AREA);
                if ($areaCell === '') { $warnings[] = "Área vacía (riesgo \"$riesgoName\", fila $r)"; continue; }
                $areasThisRow = $split($areaCell);

                foreach ($areasThisRow as $areaTxt) {
                    $idArea = $ensure('area', $areaTxt);
                    if (!$idArea) { $warnings[] = "No pude crear/encontrar área \"$areaTxt\" (fila $r)"; continue; }

                    $eppCell = $cell($rows, $r, self::COL_EPP);
                    foreach ($split($eppCell) as $txt) {
                        if ($txt === '' || strtoupper($txt) === 'N/A') continue;
                        $id = $ensure('epp', $txt);
                        if (!$id) { $warnings[] = "No pude crear/encontrar EPP \"$txt\" (fila $r)"; continue; }
                        $present['epp'][$idRiesgo][$idArea][] = $id;

                        DB::table('medidas_riesgo_puesto')->updateOrInsert([
                            'id_riesgo'       => $idRiesgo,
                            'id_area'         => $idArea,
                            'id_epp'          => $id,
                            'id_capacitacion' => null,
                            'id_senalizacion' => null,
                            'id_otras_medidas'=> null,
                        ], []);
                        $inserted++;
                    }

                    $capCell = $cell($rows, $r, self::COL_CAP);
                    foreach ($split($capCell) as $txt) {
                        if ($txt === '' || strtoupper($txt) === 'N/A') continue;
                        $id = $ensure('cap', $txt);
                        if (!$id) { $warnings[] = "No pude crear/encontrar capacitación \"$txt\" (fila $r)"; continue; }
                        $present['cap'][$idRiesgo][$idArea][] = $id;

                        DB::table('medidas_riesgo_puesto')->updateOrInsert([
                            'id_riesgo'       => $idRiesgo,
                            'id_area'         => $idArea,
                            'id_epp'          => null,
                            'id_capacitacion' => $id,
                            'id_senalizacion' => null,
                            'id_otras_medidas'=> null,
                        ], []);
                        $inserted++;
                    }

                    $senCell = $cell($rows, $r, self::COL_SEN);
                    foreach ($split($senCell) as $txt) {
                        if ($txt === '' || strtoupper($txt) === 'N/A') continue;
                        $id = $ensure('sen', $txt);
                        if (!$id) { $warnings[] = "No pude crear/encontrar señal \"$txt\" (fila $r)"; continue; }
                        $present['sen'][$idRiesgo][$idArea][] = $id;

                        DB::table('medidas_riesgo_puesto')->updateOrInsert([
                            'id_riesgo'       => $idRiesgo,
                            'id_area'         => $idArea,
                            'id_epp'          => null,
                            'id_capacitacion' => null,
                            'id_senalizacion' => $id,
                            'id_otras_medidas'=> null,
                        ], []);
                        $inserted++;
                    }

                    $otrasCell = $cell($rows, $r, self::COL_OTRAS);
                    foreach ($split($otrasCell) as $txt) {
                        if ($txt === '' || strtoupper($txt) === 'N/A') continue;
                        $id = $ensure('otras', $txt);
                        if (!$id) { $warnings[] = "No pude crear/encontrar otra medida \"$txt\" (fila $r)"; continue; }
                        $present['otras'][$idRiesgo][$idArea][] = $id;

                        DB::table('medidas_riesgo_puesto')->updateOrInsert([
                            'id_riesgo'       => $idRiesgo,
                            'id_area'         => $idArea,
                            'id_epp'          => null,
                            'id_capacitacion' => null,
                            'id_senalizacion' => null,
                            'id_otras_medidas'=> $id,
                        ], []);
                        $inserted++;
                    }
                }
            }

            if ($doDelete) {
                $del = 0;
                foreach ($present['epp'] as $rid => $areasSet) {
                    foreach ($areasSet as $aid => $ids) {
                        $q = DB::table('medidas_riesgo_puesto')
                            ->where(['id_riesgo'=>$rid,'id_area'=>$aid])
                            ->whereNull('id_capacitacion')->whereNull('id_senalizacion')->whereNull('id_otras_medidas');
                        $del += empty($ids) ? $q->whereNotNull('id_epp')->delete()
                                            : $q->whereNotIn('id_epp',$ids)->delete();
                    }
                }
                foreach ($present['cap'] as $rid => $areasSet) {
                    foreach ($areasSet as $aid => $ids) {
                        $q = DB::table('medidas_riesgo_puesto')
                            ->where(['id_riesgo'=>$rid,'id_area'=>$aid])
                            ->whereNull('id_epp')->whereNull('id_senalizacion')->whereNull('id_otras_medidas');
                        $del += empty($ids) ? $q->whereNotNull('id_capacitacion')->delete()
                                            : $q->whereNotIn('id_capacitacion',$ids)->delete();
                    }
                }
                foreach ($present['sen'] as $rid => $areasSet) {
                    foreach ($areasSet as $aid => $ids) {
                        $q = DB::table('medidas_riesgo_puesto')
                            ->where(['id_riesgo'=>$rid,'id_area'=>$aid])
                            ->whereNull('id_epp')->whereNull('id_capacitacion')->whereNull('id_otras_medidas');
                        $del += empty($ids) ? $q->whereNotNull('id_senalizacion')->delete()
                                            : $q->whereNotIn('id_senalizacion',$ids)->delete();
                    }
                }
                foreach ($present['otras'] as $rid => $areasSet) {
                    foreach ($areasSet as $aid => $ids) {
                        $q = DB::table('medidas_riesgo_puesto')
                            ->where(['id_riesgo'=>$rid,'id_area'=>$aid])
                            ->whereNull('id_epp')->whereNull('id_capacitacion')->whereNull('id_senalizacion');
                        $del += empty($ids) ? $q->whereNotNull('id_otras_medidas')->delete()
                                            : $q->whereNotIn('id_otras_medidas',$ids)->delete();
                    }
                }
                $deleted = $del;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Import medidas error', ['msg'=>$e->getMessage(), 'line'=>$e->getLine()]);
            return back()->withErrors(['excel_file' => 'Error importando: '.$e->getMessage()]);
        }

        $msg = "Importación OK. Operaciones: {$inserted}";
        if (!empty($deleted)) $msg .= " | Faltantes eliminados: {$deleted}";
        if (!empty($warnings)) {
            $msg .= ' | Avisos: '.implode(' | ', array_slice($warnings, 0, 25));
            if (count($warnings) > 25) $msg .= ' ...';
        }
        return back()->with('status', $msg);
    }
}
