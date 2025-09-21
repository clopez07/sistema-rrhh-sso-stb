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
        if ($value === '') {
            return '';
        }
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);
        return (string) $value;
    }

    public function index(Request $request)
    {
        $buscarPuesto = trim((string)$request->input('puesto', ''));
        $buscarEpp    = trim((string)$request->input('epp', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        $puestosQuery = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento');

        if ($buscarPuesto !== '') {
            $needle = $this->normalize($buscarPuesto);
            $puestosQuery->whereRaw('LOWER(ptm.puesto_trabajo_matriz) LIKE ?', ["%{$needle}%"]);
        }

        $puestos = $puestosQuery
            ->orderBy('ptm.puesto_trabajo_matriz')
            ->get();

        if ($puestos->isEmpty()) {
            return view('epp.matriz_requeridos', [
                'years'         => $years,
                'anio'          => $anio,
                'buscarPuesto'  => $buscarPuesto,
                'buscarEpp'     => $buscarEpp,
                'puestos'       => collect(),
                'epps'          => collect(),
                'pivot'         => [],
                'totales'       => [],
                'totEmpleados'  => [],
                'deptOrder'     => [],
                'deptPuestos'   => [],
                'deptEmp'       => [],
                'deptPivot'     => [],
                'deptTotals'    => [],
                'deptGrand'     => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById'       => [],
            ]);
        }

        $rowById = [];
        $nameToMatrix = [];
        foreach ($puestos as $row) {
            $rowId = (int) $row->id_puesto_trabajo_matriz;
            $rowById[$rowId] = $row;
            $normalized = $this->normalize($row->puesto_trabajo_matriz);
            if ($normalized !== '') {
                $nameToMatrix[$normalized] = $rowId;
            }
        }

        $empleadosRaw = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                'e.id_empleado',
                'e.nombre_completo',
                'e.codigo_empleado',
                'e.identidad',
                'e.id_puesto_trabajo',
                'e.id_puesto_trabajo_matriz',
                'pt.puesto_trabajo as legacy_nombre'
            )
            ->where(function ($q) {
                $q->where('e.estado', 1)->orWhereNull('e.estado');
            })
            ->get();

        $legacyIdToMatrix = [];
        $empleadosPorMatrix = [];
        foreach ($rowById as $rowId => $row) {
            $empleadosPorMatrix[$rowId] = [];
        }

        foreach ($empleadosRaw as $emp) {
            $matrixId = $emp->id_puesto_trabajo_matriz ? (int)$emp->id_puesto_trabajo_matriz : null;

            if (!$matrixId && $emp->id_puesto_trabajo && isset($legacyIdToMatrix[$emp->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$emp->id_puesto_trabajo];
            }

            if (!$matrixId && $emp->legacy_nombre) {
                $norm = $this->normalize($emp->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($emp->id_puesto_trabajo) {
                        $legacyIdToMatrix[$emp->id_puesto_trabajo] = $matrixId;
                    }
                }
            }

            if ($matrixId && isset($empleadosPorMatrix[$matrixId])) {
                $empleadosPorMatrix[$matrixId][] = $emp;
            }
        }

        $totEmpleados = [];
        foreach ($empleadosPorMatrix as $matrixId => $lista) {
            $totEmpleados[$matrixId] = count($lista);
        }

        $hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
        $hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

        $peQuery = DB::table('puestos_epp as pe')
            ->leftJoin('epp as e', 'e.id_epp', '=', 'pe.id_epp');

        if ($hasLegacyColumn) {
            $peQuery->leftJoin('puesto_trabajo as pt', 'pe.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $peSelect = [
            'pe.id_puesto_trabajo_matriz',
            $hasLegacyColumn ? 'pe.id_puesto_trabajo' : DB::raw('NULL as id_puesto_trabajo'),
            $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
            'e.id_epp',
            'e.equipo',
            'e.codigo',
        ];

        $peRows = $peQuery
            ->select($peSelect)
            ->get();

        $eppsDict = [];
        $isOblig = [];

        foreach ($peRows as $row) {
            $matrixId = null;
            if ($hasMatrixColumn && $row->id_puesto_trabajo_matriz) {
                $matrixId = (int) $row->id_puesto_trabajo_matriz;
            } elseif ($hasLegacyColumn && $row->id_puesto_trabajo && isset($legacyIdToMatrix[$row->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$row->id_puesto_trabajo];
            } elseif ($hasLegacyColumn && $row->legacy_nombre) {
                $norm = $this->normalize($row->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($row->id_puesto_trabajo) {
                        $legacyIdToMatrix[$row->id_puesto_trabajo] = $matrixId;
                    }
                }
            }

            if (!$matrixId || !isset($rowById[$matrixId])) {
                continue;
            }

            $eppId = (int) $row->id_epp;
            $isOblig[$matrixId][$eppId] = true;

            if (!isset($eppsDict[$eppId])) {
                $eppsDict[$eppId] = (object) [
                    'id_epp' => $eppId,
                    'equipo' => $row->equipo,
                    'codigo' => $row->codigo,
                ];
            }
        }

        if (empty($eppsDict)) {
            return view('epp.matriz_requeridos', [
                'years'         => $years,
                'anio'          => $anio,
                'buscarPuesto'  => $buscarPuesto,
                'buscarEpp'     => $buscarEpp,
                'puestos'       => $puestos,
                'epps'          => collect(),
                'pivot'         => [],
                'totales'       => [],
                'totEmpleados'  => $totEmpleados,
                'deptOrder'     => [],
                'deptPuestos'   => [],
                'deptEmp'       => [],
                'deptPivot'     => [],
                'deptTotals'    => [],
                'deptGrand'     => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById'       => $rowById,
            ]);
        }

        $epps = collect(array_values($eppsDict))
            ->sort(function ($a, $b) {
                return strnatcasecmp($a->equipo, $b->equipo);
            })
            ->values();

        $eppIds = $epps->pluck('id_epp')->all();

        $entregadosQuery = DB::table('asignacion_epp as asig')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'asig.id_empleado');

        if ($hasLegacyColumn) {
            $entregadosQuery->leftJoin('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $entregadosSelect = [
            'emp.id_puesto_trabajo_matriz',
            $hasLegacyColumn ? 'emp.id_puesto_trabajo' : DB::raw('NULL as id_puesto_trabajo'),
            $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
            'asig.id_epp',
            DB::raw('COUNT(DISTINCT asig.id_empleado) as cnt'),
        ];

        $entregadosGroup = [
            'emp.id_puesto_trabajo_matriz',
            'asig.id_epp',
        ];

        if ($hasLegacyColumn) {
            $entregadosGroup[] = 'emp.id_puesto_trabajo';
            $entregadosGroup[] = 'pt.puesto_trabajo';
        }

        $entregadosRows = $entregadosQuery
            ->select($entregadosSelect)
            ->whereIn('asig.id_epp', $eppIds)
            ->whereYear('asig.fecha_entrega_epp', $anio)
            ->groupBy($entregadosGroup)
            ->get();

        $idxEnt = [];
        foreach ($entregadosRows as $row) {
            $matrixId = null;
            if ($row->id_puesto_trabajo_matriz) {
                $matrixId = (int) $row->id_puesto_trabajo_matriz;
            } elseif ($row->id_puesto_trabajo && isset($legacyIdToMatrix[$row->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$row->id_puesto_trabajo];
            } elseif ($row->legacy_nombre) {
                $norm = $this->normalize($row->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($row->id_puesto_trabajo) {
                        $legacyIdToMatrix[$row->id_puesto_trabajo] = $matrixId;
                    }
                }
            }

            if (!$matrixId || !isset($rowById[$matrixId])) {
                continue;
            }

            $eppId = (int) $row->id_epp;
            $idxEnt[$matrixId][$eppId] = (int) $row->cnt;
        }

        $pivot   = [];
        $totales = [];
        foreach ($epps as $e) {
            $totales[$e->id_epp] = ['req'=>0, 'ent'=>0, 'pend'=>0];
        }

        foreach ($puestos as $p) {
            $rowId = (int) $p->id_puesto_trabajo_matriz;
            $row   = [];
            $reqPuesto = $totEmpleados[$rowId] ?? 0;

            foreach ($epps as $e) {
                if (empty($isOblig[$rowId][$e->id_epp])) {
                    $row[$e->id_epp] = null;
                    continue;
                }
                $ent  = (int)($idxEnt[$rowId][$e->id_epp] ?? 0);
                $req  = $reqPuesto;
                $pend = max(0, $req - $ent);

                $row[$e->id_epp] = ['req'=>$req,'ent'=>$ent,'pend'=>$pend];

                $totales[$e->id_epp]['req']  += $req;
                $totales[$e->id_epp]['ent']  += $ent;
                $totales[$e->id_epp]['pend'] += $pend;
            }

            $pivot[$rowId] = $row;
        }

        $deptPuestos = [];
        $deptEmp     = [];
        $deptPivot   = [];

        foreach ($puestos as $p) {
            $rowId = (int) $p->id_puesto_trabajo_matriz;
            $dep = $p->departamento ?: 'Sin departamento';

            $deptPuestos[$dep] = $deptPuestos[$dep] ?? [];
            $deptPuestos[$dep][] = ['id' => $rowId, 'row' => $p];

            $deptEmp[$dep] = ($deptEmp[$dep] ?? 0) + ($totEmpleados[$rowId] ?? 0);

            foreach ($epps as $e) {
                $cell = $pivot[$rowId][$e->id_epp] ?? null;
                if (is_null($cell)) {
                    continue;
                }
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
            'years'         => $years,
            'anio'          => $anio,
            'buscarPuesto'  => $buscarPuesto,
            'buscarEpp'     => $buscarEpp,
            'puestos'       => $puestos,
            'epps'          => $epps,
            'pivot'         => $pivot,
            'totales'       => $totales,
            'totEmpleados'  => $totEmpleados,
            'deptOrder'     => $deptOrder,
            'deptPuestos'   => $deptPuestos,
            'deptEmp'       => $deptEmp,
            'deptPivot'     => $deptPivot,
            'deptTotals'    => $deptTotals,
            'deptGrand'     => $deptGrand,
            'rowById'       => $rowById,
        ]);
    }
}

