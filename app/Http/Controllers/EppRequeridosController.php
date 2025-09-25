<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EppRequeridosController extends Controller
{
    /** Normaliza: minúsculas, sin acentos, colapsa espacios, quita puntuación */
    private function normalize(?string $value): string
    {
        $v = trim((string) $value);
        if ($v === '') return '';
        $v = mb_strtolower($v, 'UTF-8');
        $x = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);
        if ($x !== false) $v = $x;
        $v = preg_replace('/[^a-z0-9 ]+/i', ' ', $v);
        $v = preg_replace('/\s+/u', ' ', $v);
        return trim($v);
    }

    public function index(Request $request)
    {
        $buscarPuesto = trim((string)$request->input('puesto', ''));
        $buscarEpp    = trim((string)$request->input('epp', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        // 0) Catálogo de puestos matriz (solo activos) + depto
        $allPtm = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento', 'ptm.estado')
            ->where(function ($q) {
                $q->where('ptm.estado', 1)->orWhereNull('ptm.estado');
            })
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

        // Índices de mapeo: id → fila, nombre normalizado → id
        $allPtmById   = [];
        $nameToMatrix = [];
        foreach ($allPtm as $row) {
            $id = (int)$row->id_puesto_trabajo_matriz;
            $allPtmById[$id] = $row;
            $nameToMatrix[$this->normalize($row->puesto_trabajo_matriz)] = $id;
        }

        // 1) EMPLEADOS ACTIVOS → prioriza id_puesto_trabajo_matriz; fallback por nombre legacy
        $empleadosRaw = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                'e.id_empleado','e.nombre_completo','e.codigo_empleado','e.identidad',
                'e.id_puesto_trabajo','e.id_puesto_trabajo_matriz','e.estado',
                'pt.puesto_trabajo as legacy_nombre'
            )
            ->where(function ($q) { $q->where('e.estado', 1)->orWhereNull('e.estado'); })
            ->get();

        $legacyIdToMatrix   = [];   // cache: id_puesto_trabajo → id_matriz
        $empleadosPorMatrix = [];   // conteo por id_matriz
        foreach ($empleadosRaw as $emp) {
            $matrixId = $emp->id_puesto_trabajo_matriz ? (int)$emp->id_puesto_trabajo_matriz : null;

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

        // 2) Filtrar puestos a mostrar (solo los que tienen empleados mapeados)
        $matrixIdsUsed = array_keys($empleadosPorMatrix);
        if ($buscarPuesto !== '') {
            $needle = $this->normalize($buscarPuesto);
            $matrixIdsUsed = array_values(array_filter(
                $matrixIdsUsed,
                fn($id) => str_contains($this->normalize($allPtmById[$id]->puesto_trabajo_matriz ?? ''), $needle)
            ));
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

        // rowById + totEmpleados para la vista
        $rowById      = [];
        $totEmpleados = [];
        foreach ($puestos as $p) {
            $rid = (int)$p->id_puesto_trabajo_matriz;
            $rowById[$rid]      = $p;
            $totEmpleados[$rid] = (int)($empleadosPorMatrix[$rid] ?? 0);
        }

        // 3) EPP obligatorios por puesto (soporta matriz y legacy)
        $hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
        $hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

        $peQuery = DB::table('puestos_epp as pe')
            ->leftJoin('epp as e', 'e.id_epp', '=', 'pe.id_epp');
        if ($hasLegacyColumn) {
            $peQuery->leftJoin('puesto_trabajo as pt', 'pe.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $peRows = $peQuery->select([
                $hasMatrixColumn ? 'pe.id_puesto_trabajo_matriz' : DB::raw('NULL as id_puesto_trabajo_matriz'),
                $hasLegacyColumn ? 'pe.id_puesto_trabajo'        : DB::raw('NULL as id_puesto_trabajo'),
                $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
                'e.id_epp', 'e.equipo', 'e.codigo',
            ])->get();

        $eppsDict = [];
        $isOblig  = []; // [id_matriz][id_epp] = true
        foreach ($peRows as $r) {
            $matrixId = null;

            if ($hasMatrixColumn && $r->id_puesto_trabajo_matriz) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif ($hasLegacyColumn) {
                if ($r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                    $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
                } elseif ($r->legacy_nombre) {
                    $norm = $this->normalize($r->legacy_nombre);
                    if ($norm !== '' && isset($nameToMatrix[$norm])) {
                        $matrixId = $nameToMatrix[$norm];
                        if ($r->id_puesto_trabajo) {
                            $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                        }
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

        // Filtro por texto (EPP) y orden
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

        // 4) ENTREGADOS (AÑO) por puesto_matriz/epp (empleados distintos)
        $entregadosQuery = DB::table('asignacion_epp as asig')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'asig.id_empleado');

        if ($hasLegacyColumn) {
            $entregadosQuery->leftJoin('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $entregadosQuery
            ->whereIn('asig.id_epp', $eppIds)
            ->whereYear('asig.fecha_entrega_epp', $anio)
            ->where(function ($q) { $q->where('emp.estado', 1)->orWhereNull('emp.estado'); });

        // Select + group by robusto (evita ONLY_FULL_GROUP_BY)
        $selects = [
            'emp.id_puesto_trabajo_matriz',
            'asig.id_epp',
            DB::raw('COUNT(DISTINCT asig.id_empleado) as cnt'),
        ];
        $groups = ['emp.id_puesto_trabajo_matriz', 'asig.id_epp'];

        if ($hasLegacyColumn) {
            $selects[] = 'emp.id_puesto_trabajo';
            $selects[] = DB::raw('pt.puesto_trabajo as legacy_nombre');
            $groups[]  = 'emp.id_puesto_trabajo';
            $groups[]  = 'pt.puesto_trabajo';
        }

        $entregadosRows = $entregadosQuery->select($selects)->groupBy($groups)->get();

        // Index entregados por id_matriz
        $idxEnt = [];
        foreach ($entregadosRows as $r) {
            $matrixId = null;

            if ($r->id_puesto_trabajo_matriz) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif (isset($r->id_puesto_trabajo)) {
                if ($r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                    $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
                } elseif (!empty($r->legacy_nombre)) {
                    $norm = $this->normalize($r->legacy_nombre);
                    if ($norm !== '' && isset($nameToMatrix[$norm])) {
                        $matrixId = $nameToMatrix[$norm];
                        if ($r->id_puesto_trabajo) {
                            $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                        }
                    }
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

        // 6) Subtotales por DEPARTAMENTO
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

    public function detalle(Request $request)
{
    $eppId  = (int) $request->input('epp_id', 0);
    $rango  = (string) $request->input('rango', 'anio'); // anio | 12m | todo
    $anio   = (int) ($request->input('anio') ?: date('Y'));

    // ==== Catálogo de EPP para el selector ====
    $eppsAll = DB::table('epp')->select('id_epp','equipo','codigo')->orderBy('equipo')->get();

    // Si no hay EPP elegido, renderiza solo el filtro
    if ($eppId <= 0) {
        return view('epp.detalle_obligatorio', [
            'eppsAll' => $eppsAll, 'eppId' => $eppId,
            'rango' => $rango, 'anio' => $anio,
            'resumen' => ['total'=>0,'entregados'=>0,'pendientes'=>0,'avance'=>0],
            'entregados' => collect(), 'pendientes' => collect(),
        ]);
    }

    // ==== Puestos matriz activos (para nombres/deptos + mapeo) ====
    $allPtm = DB::table('puesto_trabajo_matriz as ptm')
        ->leftJoin('departamento as d','ptm.id_departamento','=','d.id_departamento')
        ->select('ptm.id_puesto_trabajo_matriz','ptm.puesto_trabajo_matriz','d.departamento','ptm.estado')
        ->where(function($q){ $q->where('ptm.estado',1)->orWhereNull('ptm.estado'); })
        ->get();

    if ($allPtm->isEmpty()) {
        return view('epp.detalle_obligatorio', [
            'eppsAll'=>$eppsAll,'eppId'=>$eppId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'entregados'=>0,'pendientes'=>0,'avance'=>0],
            'entregados'=>collect(),'pendientes'=>collect(),
        ]);
    }

    $allPtmById = [];
    $nameToMatrix = [];
    foreach ($allPtm as $r) {
        $id = (int)$r->id_puesto_trabajo_matriz;
        $allPtmById[$id] = $r;
        $nameToMatrix[$this->normalize($r->puesto_trabajo_matriz)] = $id;
    }

    // ==== Empleados activos + mapeo al puesto matriz (prioriza id matriz; fallback legacy por nombre) ====
    $empRows = DB::table('empleado as e')
        ->leftJoin('puesto_trabajo as pt','e.id_puesto_trabajo','=','pt.id_puesto_trabajo')
        ->select('e.id_empleado','e.codigo_empleado','e.nombre_completo','e.estado',
                 'e.id_puesto_trabajo_matriz','e.id_puesto_trabajo','pt.puesto_trabajo as legacy_nombre')
        ->where(function($q){ $q->where('e.estado',1)->orWhereNull('e.estado'); })
        ->get();

    $legacyIdToMatrix = [];
    $empleadoInfo = []; // id_empleado => ['emp'=>obj,'matrixId'=>int]
    foreach ($empRows as $e) {
        $matrixId = $e->id_puesto_trabajo_matriz ? (int)$e->id_puesto_trabajo_matriz : null;
        if (!$matrixId && $e->legacy_nombre) {
            $norm = $this->normalize($e->legacy_nombre);
            if ($norm !== '' && isset($nameToMatrix[$norm])) {
                $matrixId = $nameToMatrix[$norm];
                if ($e->id_puesto_trabajo) $legacyIdToMatrix[$e->id_puesto_trabajo] = $matrixId;
            }
        }
        if ($matrixId && isset($allPtmById[$matrixId])) {
            $empleadoInfo[$e->id_empleado] = ['emp'=>$e,'matrixId'=>$matrixId];
        }
    }

    if (empty($empleadoInfo)) {
        return view('epp.detalle_obligatorio', [
            'eppsAll'=>$eppsAll,'eppId'=>$eppId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'entregados'=>0,'pendientes'=>0,'avance'=>0],
            'entregados'=>collect(),'pendientes'=>collect(),
        ]);
    }

    // ==== Puestos donde el EPP es obligatorio (soporta matriz y legacy en puestos_epp) ====
    $hasMatrixColumn = \Schema::hasColumn('puestos_epp','id_puesto_trabajo_matriz');
    $hasLegacyColumn = \Schema::hasColumn('puestos_epp','id_puesto_trabajo');

    $peQ = DB::table('puestos_epp as pe')->where('pe.id_epp',$eppId);
    if ($hasLegacyColumn) $peQ->leftJoin('puesto_trabajo as pt','pe.id_puesto_trabajo','=','pt.id_puesto_trabajo');

    $peRows = $peQ->select([
        $hasMatrixColumn ? 'pe.id_puesto_trabajo_matriz' : DB::raw('NULL as id_puesto_trabajo_matriz'),
        $hasLegacyColumn ? 'pe.id_puesto_trabajo'        : DB::raw('NULL as id_puesto_trabajo'),
        $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
    ])->get();

    $obligMatrixIds = [];
    foreach ($peRows as $r) {
        $matrixId = null;
        if ($hasMatrixColumn && $r->id_puesto_trabajo_matriz) {
            $matrixId = (int)$r->id_puesto_trabajo_matriz;
        } elseif ($hasLegacyColumn) {
            if ($r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
            } elseif ($r->legacy_nombre) {
                $norm = $this->normalize($r->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($r->id_puesto_trabajo) $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                }
            }
        }
        if ($matrixId && isset($allPtmById[$matrixId])) {
            $obligMatrixIds[$matrixId] = true;
        }
    }

    if (empty($obligMatrixIds)) {
        return view('epp.detalle_obligatorio', [
            'eppsAll'=>$eppsAll,'eppId'=>$eppId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'entregados'=>0,'pendientes'=>0,'avance'=>0],
            'entregados'=>collect(),'pendientes'=>collect(),
        ]);
    }

    // ==== Empleados que DEBEN recibir este EPP (porque su puesto matriz lo exige) ====
    $obligEmployees = [];
    foreach ($empleadoInfo as $empId => $info) {
        if (isset($obligMatrixIds[$info['matrixId']])) {
            $obligEmployees[$empId] = $info;
        }
    }

    if (empty($obligEmployees)) {
        return view('epp.detalle_obligatorio', [
            'eppsAll'=>$eppsAll,'eppId'=>$eppId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'entregados'=>0,'pendientes'=>0,'avance'=>0],
            'entregados'=>collect(),'pendientes'=>collect(),
        ]);
    }

    $empIds = array_keys($obligEmployees);

    // ==== Entregas del EPP para esos empleados (según rango) ====
    $asigQ = DB::table('asignacion_epp')
        ->where('id_epp', $eppId)
        ->whereIn('id_empleado', $empIds);

    if ($rango === 'anio') {
        $asigQ->whereYear('fecha_entrega_epp', $anio);
    } elseif ($rango === '12m') {
        $desde = date('Y-m-d', strtotime('-12 months'));
        $hoy   = date('Y-m-d');
        $asigQ->whereBetween('fecha_entrega_epp', [$desde, $hoy]);
    } // 'todo' => sin filtro

    $entregas = $asigQ
        ->select('id_empleado', DB::raw('MAX(fecha_entrega_epp) as ultima_entrega'))
        ->groupBy('id_empleado')
        ->get()
        ->keyBy('id_empleado');

    // ==== Construir listas ====
    $entregados = [];
    $pendientes = [];

    foreach ($obligEmployees as $empId => $info) {
        $emp = $info['emp'];
        $ptm = $allPtmById[$info['matrixId']] ?? null;

        $row = (object)[
            'id_empleado'     => $emp->id_empleado,
            'codigo_empleado' => $emp->codigo_empleado,
            'nombre_completo' => $emp->nombre_completo,
            'puesto'          => $ptm->puesto_trabajo_matriz ?? '',
            'departamento'    => $ptm->departamento ?? '',
            'ultima_entrega'  => null,
        ];

        if (isset($entregas[$empId])) {
            $row->ultima_entrega = $entregas[$empId]->ultima_entrega;
            $entregados[] = $row;
        } else {
            $pendientes[] = $row;
        }
    }

    // Orden: por depto/puesto/nombre
    $sortFn = fn($a,$b)=> strnatcasecmp($a->departamento.' '.$a->puesto.' '.$a->nombre_completo, $b->departamento.' '.$b->puesto.' '.$b->nombre_completo);
    usort($entregados, $sortFn);
    usort($pendientes, $sortFn);

    // Resumen
    $total = count($obligEmployees);
    $con   = count($entregados);
    $sin   = count($pendientes);
    $avance = $total > 0 ? round(($con * 100) / $total, 1) : 0;

    return view('epp.detalle_obligatorio', [
        'eppsAll' => $eppsAll,
        'eppId'   => $eppId,
        'rango'   => $rango,
        'anio'    => $anio,
        'resumen' => ['total'=>$total,'entregados'=>$con,'pendientes'=>$sin,'avance'=>$avance],
        'entregados' => collect($entregados),
        'pendientes' => collect($pendientes),
    ]);
}

}
