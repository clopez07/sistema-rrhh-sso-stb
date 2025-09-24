<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EppRequeridosController extends Controller
{
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
        $buscarEpp    = trim((string)$request->input('epp', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        // 0) Traer TODOS los puestos matriz (para nombres/deptos y mapeo por nombre si falta el id)
        $allPtm = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento')
            ->get();

        if ($allPtm->isEmpty()) {
            return view('epp.matriz_requeridos', [
                'years' => $years, 'anio' => $anio,
                'buscarPuesto' => $buscarPuesto, 'buscarEpp' => $buscarEpp,
                'puestos' => collect(), 'epps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        // Índices de mapeo
        $allPtmById   = [];
        $nameToMatrix = [];
        foreach ($allPtm as $row) {
            $id = (int)$row->id_puesto_trabajo_matriz;
            $allPtmById[$id] = $row;
            $nameToMatrix[$this->normalize($row->puesto_trabajo_matriz)] = $id;
        }

        // 1) EMPLEADOS ACTIVOS → PRIORIDAD a e.id_puesto_trabajo_matriz; fallback por legacy (nombre)
        $empleadosRaw = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                'e.id_empleado','e.nombre_completo','e.codigo_empleado','e.identidad',
                'e.id_puesto_trabajo','e.id_puesto_trabajo_matriz',
                'pt.puesto_trabajo as legacy_nombre'
            )
            ->where(function ($q) { $q->where('e.estado', 1)->orWhereNull('e.estado'); })
            ->get();

        $legacyIdToMatrix = [];   // cache: id_puesto_trabajo → id_matriz
        $empleadosPorMatrix = []; // conteo por id_matriz
        foreach ($empleadosRaw as $emp) {
            // PRIORIDAD: id_puesto_trabajo_matriz
            $matrixId = $emp->id_puesto_trabajo_matriz ? (int)$emp->id_puesto_trabajo_matriz : null;

            // Fallback 1: ya mapeado ese id_puesto_trabajo a un id_matriz
            if (!$matrixId && $emp->id_puesto_trabajo && isset($legacyIdToMatrix[$emp->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$emp->id_puesto_trabajo];
            }

            // Fallback 2: por nombre legacy -> nombre de puesto matriz
            if (!$matrixId && $emp->legacy_nombre) {
                $norm = $this->normalize($emp->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($emp->id_puesto_trabajo) {
                        $legacyIdToMatrix[$emp->id_puesto_trabajo] = $matrixId;
                    }
                }
            }

            if ($matrixId && isset($allPtmById[$matrixId])) {
                $empleadosPorMatrix[$matrixId] = ($empleadosPorMatrix[$matrixId] ?? 0) + 1;
            }
        }

        // 2) Filtrar/ordenar PUESTOS a mostrar (opcional filtro por texto)
        $matrixIdsUsed = array_keys($empleadosPorMatrix);
        if ($buscarPuesto !== '') {
            $needle = $this->normalize($buscarPuesto);
            $matrixIdsUsed = array_values(array_filter($matrixIdsUsed, function($id) use ($allPtmById, $needle) {
                return str_contains($this->normalize($allPtmById[$id]->puesto_trabajo_matriz ?? ''), $needle);
            }));
        }

        $puestos = collect($matrixIdsUsed)
            ->map(fn($id) => $allPtmById[$id])
            ->sort(fn($a,$b)=>strnatcasecmp($a->puesto_trabajo_matriz, $b->puesto_trabajo_matriz))
            ->values();

        if ($puestos->isEmpty()) {
            return view('epp.matriz_requeridos', [
                'years' => $years, 'anio' => $anio,
                'buscarPuesto' => $buscarPuesto, 'buscarEpp' => $buscarEpp,
                'puestos' => collect(), 'epps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        // rowById + totEmpleados para lo visible
        $rowById      = [];
        $totEmpleados = [];
        foreach ($puestos as $p) {
            $rid = (int)$p->id_puesto_trabajo_matriz;
            $rowById[$rid]      = $p;
            $totEmpleados[$rid] = (int)($empleadosPorMatrix[$rid] ?? 0);
        }

        // 3) COLUMNAS EPP obligatorios para esos puestos (soporta pe.id_puesto_trabajo_matriz o legacy)
        $hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
        $hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

        $peQuery = DB::table('puestos_epp as pe')
            ->leftJoin('epp as e', 'e.id_epp', '=', 'pe.id_epp');
        if ($hasLegacyColumn) {
            $peQuery->leftJoin('puesto_trabajo as pt', 'pe.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $peRows = $peQuery->select([
                $hasMatrixColumn ? 'pe.id_puesto_trabajo_matriz' : DB::raw('NULL as id_puesto_trabajo_matriz'),
                $hasLegacyColumn ? 'pe.id_puesto_trabajo' : DB::raw('NULL as id_puesto_trabajo'),
                $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
                'e.id_epp', 'e.equipo', 'e.codigo',
            ])->get();

        $eppsDict = [];
        $isOblig  = []; // [id_matriz][id_epp] = true
        foreach ($peRows as $r) {
            // Resolver a id_matriz: si la tabla ya lo trae, úsalo; si no, mapear por legacy
            $matrixId = null;
            if ($hasMatrixColumn && $r->id_puesto_trabajo_matriz) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif ($hasLegacyColumn && $r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
            } elseif ($hasLegacyColumn && $r->legacy_nombre) {
                $norm = $this->normalize($r->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($r->id_puesto_trabajo) {
                        $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                    }
                }
            }
            if (!$matrixId || !isset($rowById[$matrixId])) continue;

            $eppId = (int)$r->id_epp;
            $isOblig[$matrixId][$eppId] = true;
            if (!isset($eppsDict[$eppId])) {
                $eppsDict[$eppId] = (object)['id_epp'=>$eppId,'equipo'=>$r->equipo,'codigo'=>$r->codigo];
            }
        }

        if (empty($eppsDict)) {
            return view('epp.matriz_requeridos', [
                'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarEpp'=>$buscarEpp,
                'puestos'=>$puestos,'epps'=>collect(),'pivot'=>[],'totales'=>[],'totEmpleados'=>$totEmpleados,
                'deptOrder'=>[],'deptPuestos'=>[],'deptEmp'=>[],'deptPivot'=>[],'deptTotals'=>[],
                'deptGrand'=>['req'=>0,'ent'=>0,'pend'=>0],'rowById'=>$rowById,
            ]);
        }

        $epps = collect(array_values($eppsDict))
            ->filter(function($e) use ($buscarEpp){
                if ($buscarEpp==='') return true;
                $needle = $this->normalize($buscarEpp);
                return str_contains($this->normalize($e->equipo ?? ''), $needle)
                    || str_contains($this->normalize($e->codigo ?? ''), $needle);
            })
            ->sort(fn($a,$b)=>strnatcasecmp($a->equipo, $b->equipo))
            ->values();

        if ($epps->isEmpty()) {
            return view('epp.matriz_requeridos', [
                'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarEpp'=>$buscarEpp,
                'puestos'=>$puestos,'epps'=>collect(),'pivot'=>[],'totales'=>[],'totEmpleados'=>$totEmpleados,
                'deptOrder'=>[],'deptPuestos'=>[],'deptEmp'=>[],'deptPivot'=>[],'deptTotals'=>[],
                'deptGrand'=>['req'=>0,'ent'=>0,'pend'=>0],'rowById'=>$rowById,
            ]);
        }

        $eppIds = $epps->pluck('id_epp')->all();

        // 4) ENTREGADOS (AÑO) por puesto_matriz/epp (empleados distintos) — fecha DATE
        $entregadosQuery = DB::table('asignacion_epp as asig')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'asig.id_empleado');
        if ($hasLegacyColumn) {
            $entregadosQuery->leftJoin('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $entregadosRows = $entregadosQuery
            ->select([
                'emp.id_puesto_trabajo_matriz',
                $hasLegacyColumn ? 'emp.id_puesto_trabajo' : DB::raw('NULL as id_puesto_trabajo'),
                $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
                'asig.id_epp',
                DB::raw('COUNT(DISTINCT asig.id_empleado) as cnt'),
            ])
            ->whereIn('asig.id_epp', $eppIds)
            ->whereYear('asig.fecha_entrega_epp', $anio)   // ← DATE directo
            ->groupBy('emp.id_puesto_trabajo_matriz','asig.id_epp',
                      $hasLegacyColumn ? 'emp.id_puesto_trabajo' : DB::raw('emp.id_puesto_trabajo_matriz'),
                      $hasLegacyColumn ? 'pt.puesto_trabajo'     : DB::raw('emp.id_puesto_trabajo_matriz'))
            ->get();

        // Index entregados por id_matriz
        $idxEnt = [];
        foreach ($entregadosRows as $r) {
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

            $idxEnt[$matrixId][(int)$r->id_epp] = (int)$r->cnt;
        }

        // 5) Construir PIVOT por puesto y TOTALES por EPP
        $pivot   = [];
        $totales = [];
        foreach ($epps as $e) $totales[$e->id_epp] = ['req'=>0,'ent'=>0,'pend'=>0];

        foreach ($puestos as $p) {
            $rowId = (int)$p->id_puesto_trabajo_matriz;
            $row   = [];
            $reqPuesto = $totEmpleados[$rowId] ?? 0;

            foreach ($epps as $e) {
                if (empty($isOblig[$rowId][$e->id_epp])) {
                    $row[$e->id_epp] = null; // NO obligatorio → celda en blanco
                    continue;
                }
                $ent  = (int)($idxEnt[$rowId][$e->id_epp] ?? 0);
                $req  = $reqPuesto;                 // 1 por empleado
                $pend = max(0, $req - $ent);

                $row[$e->id_epp] = ['req'=>$req,'ent'=>$ent,'pend'=>$pend];

                $totales[$e->id_epp]['req']  += $req;
                $totales[$e->id_epp]['ent']  += $ent;
                $totales[$e->id_epp]['pend'] += $pend;
            }
            $pivot[$rowId] = $row;
        }

        // 6) Subtotales por DEPARTAMENTO (más resumen superior)
        $deptPuestos = [];
        $deptEmp     = [];
        $deptPivot   = [];

        foreach ($puestos as $p) {
            $dep   = $p->departamento ?: 'Sin departamento';
            $rowId = (int)$p->id_puesto_trabajo_matriz;

            $deptPuestos[$dep] = $deptPuestos[$dep] ?? [];
            $deptPuestos[$dep][] = ['id' => $rowId, 'row' => $p];

            $deptEmp[$dep] = ($deptEmp[$dep] ?? 0) + ($totEmpleados[$rowId] ?? 0);

            foreach ($epps as $e) {
                $cell = $pivot[$rowId][$e->id_epp] ?? null;
                if (is_null($cell)) continue;

                if (!isset($deptPivot[$dep][$e->id_epp])) {
                    $deptPivot[$dep][$e->id_epp] = ['req'=>0,'ent'=>0,'pend'=>0];
                }
                $deptPivot[$dep][$e->id_epp]['req']  += $cell['req'];
                $deptPivot[$dep][$e->id_epp]['ent']  += $cell['ent'];
                $deptPivot[$dep][$e->id_epp]['pend'] += $cell['pend'];
            }
        }

        $deptOrder = array_keys($deptPuestos);
        sort($deptOrder, SORT_NATURAL | SORT_FLAG_CASE);

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

        return view('epp.matriz_requeridos', [
            'years'=>$years,'anio'=>$anio,
            'buscarPuesto'=>$buscarPuesto,'buscarEpp'=>$buscarEpp,
            'puestos'=>$puestos,'epps'=>$epps,'pivot'=>$pivot,'totales'=>$totales,
            'totEmpleados'=>$totEmpleados,
            'deptOrder'=>$deptOrder,'deptPuestos'=>$deptPuestos,'deptEmp'=>$deptEmp,
            'deptPivot'=>$deptPivot,'deptTotals'=>$deptTotals,'deptGrand'=>$deptGrand,
            'rowById'=>$rowById,
        ]);
    }
}
