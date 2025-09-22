<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapacitacionesRequeridasController extends Controller
{
    /** Normaliza textos para empatar por nombre cuando falta el id matriz */
    private function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') return '';
        $value = mb_strtolower($value, 'UTF-8');
        return preg_replace('/\s+/u', ' ', $value);
    }

    public function index(Request $request)
    {
        $buscarPuesto = trim((string)$request->input('puesto', ''));
        $buscarCap    = trim((string)$request->input('cap', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        /* ================== 1) PUESTOS MATRIZ (con departamento) ================== */
        $puestosQuery = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento');

        if ($buscarPuesto !== '') {
            $needle = $this->normalize($buscarPuesto);
            $puestosQuery->whereRaw('LOWER(ptm.puesto_trabajo_matriz) LIKE ?', ["%{$needle}%"]);
        }

        $puestos = $puestosQuery->orderBy('ptm.puesto_trabajo_matriz')->get();
        if ($puestos->isEmpty()) {
            return view('capacitaciones.matriz_requeridos', [
                'years' => $years, 'anio' => $anio, 'buscarPuesto' => $buscarPuesto, 'buscarCap' => $buscarCap,
                'puestos' => collect(), 'caps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        // índices auxiliares
        $rowById = [];
        $nameToMatrix = [];
        foreach ($puestos as $row) {
            $rowId = (int)$row->id_puesto_trabajo_matriz;
            $rowById[$rowId] = $row;
            $nameToMatrix[$this->normalize($row->puesto_trabajo_matriz)] = $rowId;
        }

        /* ================== 2) EMPLEADOS ACTIVOS → mapear a puesto matriz ================== */
        $empleadosRaw = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                'e.id_empleado','e.nombre_completo','e.codigo_empleado','e.identidad',
                'e.id_puesto_trabajo','e.id_puesto_trabajo_matriz',
                'pt.puesto_trabajo as legacy_nombre'
            )
            ->where(function ($q) { $q->where('e.estado', 1)->orWhereNull('e.estado'); })
            ->get();

        $legacyIdToMatrix = [];
        $empleadosPorMatrix = [];
        foreach ($rowById as $rowId => $_) $empleadosPorMatrix[$rowId] = [];

        foreach ($empleadosRaw as $emp) {
            $matrixId = $emp->id_puesto_trabajo_matriz ? (int)$emp->id_puesto_trabajo_matriz : null;

            if (!$matrixId && $emp->id_puesto_trabajo && isset($legacyIdToMatrix[$emp->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$emp->id_puesto_trabajo];
            }
            if (!$matrixId && $emp->legacy_nombre) {
                $norm = $this->normalize($emp->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($emp->id_puesto_trabajo) $legacyIdToMatrix[$emp->id_puesto_trabajo] = $matrixId;
                }
            }
            if ($matrixId && isset($empleadosPorMatrix[$matrixId])) {
                $empleadosPorMatrix[$matrixId][] = $emp;
            }
        }

        $totEmpleados = [];
        foreach ($empleadosPorMatrix as $matrixId => $lista) $totEmpleados[$matrixId] = count($lista);

        /* ================== 3) COLUMNAS: Capacitaciones obligatorias por puesto ================== */
        $pcQuery = DB::table('puestos_capacitacion as pc')
            ->join('capacitacion as c', 'c.id_capacitacion', '=', 'pc.id_capacitacion')
            ->whereIn('pc.id_puesto_trabajo_matriz', array_keys($rowById));

        if ($buscarCap !== '') {
            $needle = $this->normalize($buscarCap);
            $pcQuery->whereRaw('LOWER(c.capacitacion) LIKE ?', ["%{$needle}%"]);
        }

        $pcRows = $pcQuery->get(['pc.id_puesto_trabajo_matriz','c.id_capacitacion','c.capacitacion']);

        $capsDict = [];            // id_capacitacion => obj
        $isOblig  = [];            // [puesto_matriz][cap_id] = true
        foreach ($pcRows as $r) {
            $pid = (int)$r->id_puesto_trabajo_matriz;
            if (!isset($rowById[$pid])) continue;
            $cid = (int)$r->id_capacitacion;
            $isOblig[$pid][$cid] = true;
            if (!isset($capsDict[$cid])) {
                $capsDict[$cid] = (object)[
                    'id_capacitacion' => $cid,
                    'capacitacion'    => $r->capacitacion,
                ];
            }
        }

        if (empty($capsDict)) {
            return view('capacitaciones.matriz_requeridos', [
                'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
                'puestos'=>$puestos,'caps'=>collect(),'pivot'=>[],'totales'=>[],
                'totEmpleados'=>$totEmpleados,'deptOrder'=>[],'deptPuestos'=>[],'deptEmp'=>[],
                'deptPivot'=>[],'deptTotals'=>[],'deptGrand'=>['req'=>0,'ent'=>0,'pend'=>0],'rowById'=>$rowById,
            ]);
        }

        $caps = collect(array_values($capsDict))
                    ->sort(fn($a,$b)=>strnatcasecmp($a->capacitacion,$b->capacitacion))
                    ->values();
        $capIds = $caps->pluck('id_capacitacion')->all();

        /* ================== 4) RECIBIDAS por puesto_matriz/cap en el año ================== */
        // Parser robusto para fecha_recibida (VARCHAR)
        $raw   = "LOWER(TRIM(ac.fecha_recibida))";
        $clean = "REPLACE(REPLACE(REPLACE(REPLACE($raw,'a. m.',''),'p. m.',''),'a.m.',''),'p.m.','')";
        $clean = "REPLACE(REPLACE($clean, ',', ''), '  ', ' ')";
        $firstToken = "SUBSTRING_INDEX($clean, ' ', 1)";
        $isoToken   = "SUBSTRING_INDEX($clean, 't', 1)";
        $parsed = "COALESCE(
            STR_TO_DATE($clean, '%Y-%m-%d'),
            STR_TO_DATE($clean, '%Y/%m/%d'),
            STR_TO_DATE($clean, '%d/%m/%Y'),
            STR_TO_DATE($clean, '%d-%m-%Y'),
            STR_TO_DATE($clean, '%m/%d/%Y'),
            STR_TO_DATE($clean, '%m-%d-%Y'),
            STR_TO_DATE($clean, '%d.%m.%Y'),
            STR_TO_DATE($firstToken, '%Y-%m-%d'),
            STR_TO_DATE($firstToken, '%d/%m/%Y'),
            STR_TO_DATE($firstToken, '%m/%d/%Y'),
            STR_TO_DATE($firstToken, '%d-%m-%Y'),
            STR_TO_DATE($isoToken,   '%Y-%m-%d')
        )";

        // Con capacitacion_instructor → obtener id_capacitacion
        $recibidasRows = DB::table('asistencia_capacitacion as ac')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'ac.id_empleado')
            ->leftJoin('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->join('capacitacion_instructor as ci', 'ci.id_capacitacion_instructor', '=', 'ac.id_capacitacion_instructor')
            ->select([
                'emp.id_puesto_trabajo_matriz',
                'emp.id_puesto_trabajo',
                DB::raw('pt.puesto_trabajo as legacy_nombre'),
                'ci.id_capacitacion',
                DB::raw('COUNT(DISTINCT ac.id_empleado) as cnt'),
            ])
            ->whereIn('ci.id_capacitacion', $capIds)
            ->whereRaw("YEAR($parsed) = ?", [$anio])
            ->groupBy('emp.id_puesto_trabajo_matriz','emp.id_puesto_trabajo','pt.puesto_trabajo','ci.id_capacitacion')
            ->get();

        /*  --- Si NO tuvieras 'capacitacion_instructor', usa esto en su lugar:
        $recibidasRows = DB::table('asistencia_capacitacion as ac')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'ac.id_empleado')
            ->leftJoin('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select([
                'emp.id_puesto_trabajo_matriz',
                'emp.id_puesto_trabajo',
                DB::raw('pt.puesto_trabajo as legacy_nombre'),
                DB::raw('ac.id_capacitacion_instructor as id_capacitacion'),
                DB::raw('COUNT(DISTINCT ac.id_empleado) as cnt'),
            ])
            ->whereIn('ac.id_capacitacion_instructor', $capIds)
            ->whereRaw("YEAR($parsed) = ?", [$anio])
            ->groupBy('emp.id_puesto_trabajo_matriz','emp.id_puesto_trabajo','pt.puesto_trabajo','ac.id_capacitacion_instructor')
            ->get();
        */

        // Mapear a puesto matriz (igual que arriba)
        $idxEnt = []; // [puesto_matriz][cap_id] => cnt
        foreach ($recibidasRows as $r) {
            $matrixId = null;
            if ($r->id_puesto_trabajo_matriz) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif ($r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
            } elseif ($r->legacy_nombre) {
                $norm = $this->normalize($r->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($r->id_puesto_trabajo) $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                }
            }
            if (!$matrixId || !isset($rowById[$matrixId])) continue;

            $capId = (int)$r->id_capacitacion;
            $idxEnt[$matrixId][$capId] = (int)$r->cnt;
        }

        /* ================== 5) PIVOT por puesto; TOTALES por capacitación ================== */
        $pivot   = [];
        $totales = []; // por cap (columna)
        foreach ($caps as $c) $totales[$c->id_capacitacion] = ['req'=>0,'ent'=>0,'pend'=>0];

        foreach ($puestos as $p) {
            $rowId = (int)$p->id_puesto_trabajo_matriz;
            $row = [];
            $reqPuesto = $totEmpleados[$rowId] ?? 0;

            foreach ($caps as $c) {
                if (empty($isOblig[$rowId][$c->id_capacitacion])) {  // no obligatorio → celda en blanco
                    $row[$c->id_capacitacion] = null;
                    continue;
                }
                $ent  = (int)($idxEnt[$rowId][$c->id_capacitacion] ?? 0);
                $req  = $reqPuesto;
                $pend = max(0, $req - $ent);

                $row[$c->id_capacitacion] = ['req'=>$req,'ent'=>$ent,'pend'=>$pend];

                $totales[$c->id_capacitacion]['req']  += $req;
                $totales[$c->id_capacitacion]['ent']  += $ent;
                $totales[$c->id_capacitacion]['pend'] += $pend;
            }

            $pivot[$rowId] = $row;
        }

        /* ================== 6) AGRUPACIÓN por departamento para subtotales y resumen ================== */
        $deptPuestos = [];   // dept => [['id'=>rowId, 'row'=>obj], ...]
        $deptEmp     = [];   // dept => total empleados
        $deptPivot   = [];   // dept => [cap_id => ['req','ent','pend']]

        foreach ($puestos as $p) {
            $dep = $p->departamento ?: 'Sin departamento';
            $rowId = (int)$p->id_puesto_trabajo_matriz;

            $deptPuestos[$dep] = $deptPuestos[$dep] ?? [];
            $deptPuestos[$dep][] = ['id' => $rowId, 'row' => $p];

            $deptEmp[$dep] = ($deptEmp[$dep] ?? 0) + ($totEmpleados[$rowId] ?? 0);

            foreach ($caps as $c) {
                $cell = $pivot[$rowId][$c->id_capacitacion] ?? null;
                if (is_null($cell)) continue;
                if (!isset($deptPivot[$dep][$c->id_capacitacion])) {
                    $deptPivot[$dep][$c->id_capacitacion] = ['req'=>0,'ent'=>0,'pend'=>0];
                }
                $deptPivot[$dep][$c->id_capacitacion]['req']  += $cell['req'];
                $deptPivot[$dep][$c->id_capacitacion]['ent']  += $cell['ent'];
                $deptPivot[$dep][$c->id_capacitacion]['pend'] += $cell['pend'];
            }
        }

        $deptOrder = array_keys($deptPuestos);
        sort($deptOrder, SORT_NATURAL | SORT_FLAG_CASE);

        // Resumen por depto y gran total
        $deptTotals = [];
        foreach ($deptOrder as $dep) {
            $deptTotals[$dep] = ['req'=>0,'ent'=>0,'pend'=>0];
            if (!empty($deptPivot[$dep])) {
                foreach ($deptPivot[$dep] as $vals) {
                    $deptTotals[$dep]['req']  += $vals['req'];
                    $deptTotals[$dep]['ent']  += $vals['ent'];
                    $deptTotals[$dep]['pend'] += $vals['pend'];
                }
            }
        }
        $deptGrand = ['req'=>0,'ent'=>0,'pend'=>0];
        foreach ($deptTotals as $t) {
            $deptGrand['req']  += $t['req'];
            $deptGrand['ent']  += $t['ent'];
            $deptGrand['pend'] += $t['pend'];
        }

        return view('capacitaciones.matriz_requeridos', [
            'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
            'puestos'=>$puestos,'caps'=>$caps,'pivot'=>$pivot,'totales'=>$totales,'totEmpleados'=>$totEmpleados,
            'deptOrder'=>$deptOrder,'deptPuestos'=>$deptPuestos,'deptEmp'=>$deptEmp,
            'deptPivot'=>$deptPivot,'deptTotals'=>$deptTotals,'deptGrand'=>$deptGrand,'rowById'=>$rowById,
        ]);
    }
}
