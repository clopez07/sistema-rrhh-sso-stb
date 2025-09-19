<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EppRequeridosController extends Controller
{
    public function index(Request $request)
    {
        $buscarPuesto = trim((string)$request->input('puesto', ''));
        $buscarEpp    = trim((string)$request->input('epp', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        // 1) Puestos QUE TIENEN EPP OBLIGATORIOS (solo esos aparecen)
        $puestos = DB::table('puesto_trabajo as pt')
            ->select('pt.id_puesto_trabajo', 'pt.puesto_trabajo', 'pt.departamento')
            ->where(function ($q) {
                $q->where('pt.estado', 1)->orWhereNull('pt.estado');
            })
            ->whereExists(function ($q) {
                $q->from('puestos_epp as pe')
                  ->whereColumn('pe.id_puesto_trabajo', 'pt.id_puesto_trabajo');
            })
            ->when($buscarPuesto !== '', function ($q) use ($buscarPuesto) {
                $q->where(function ($w) use ($buscarPuesto) {
                    $w->where('pt.puesto_trabajo', 'like', "%{$buscarPuesto}%")
                      ->orWhere('pt.departamento', 'like', "%{$buscarPuesto}%");
                });
            })
            ->orderBy('pt.departamento')
            ->orderBy('pt.puesto_trabajo')
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
            ]);
        }

        $puestoIds = $puestos->pluck('id_puesto_trabajo')->all();

        // 2) Empleados activos por puesto (requeridos = uno por empleado)
        $totEmpleados = DB::table('empleado as emp')
            ->select('emp.id_puesto_trabajo', DB::raw('COUNT(*) as n'))
            ->whereIn('emp.id_puesto_trabajo', $puestoIds)
            ->where(function ($q) {
                $q->where('emp.estado', 1)->orWhereNull('emp.estado');
            })
            ->groupBy('emp.id_puesto_trabajo')
            ->pluck('n', 'id_puesto_trabajo'); // mapa puesto_id => total empleados

        // 3) Columnas EPP = EPP obligatorios en estos puestos (filtrables por texto)
        $epps = DB::table('puestos_epp as pe')
            ->join('epp as e', 'e.id_epp', '=', 'pe.id_epp')
            ->whereIn('pe.id_puesto_trabajo', $puestoIds)
            ->when($buscarEpp !== '', function ($q) use ($buscarEpp) {
                $q->where(function ($w) use ($buscarEpp) {
                    $w->where('e.equipo', 'like', "%{$buscarEpp}%")
                      ->orWhere('e.codigo', 'like', "%{$buscarEpp}%");
                });
            })
            ->distinct()
            ->orderBy('e.equipo')
            ->get(['e.id_epp', 'e.equipo', 'e.codigo']);

        if ($epps->isEmpty()) {
            return view('epp.matriz_requeridos', [
                'years'         => $years,
                'anio'          => $anio,
                'buscarPuesto'  => $buscarPuesto,
                'buscarEpp'     => $buscarEpp,
                'puestos'       => $puestos,
                'epps'          => collect(),
                'pivot'         => [],
                'totales'       => [],
                'totEmpleados'  => $totEmpleados->toArray(),
                'deptOrder'     => [],
                'deptPuestos'   => [],
                'deptEmp'       => [],
                'deptPivot'     => [],
                'deptTotals'    => [],
                'deptGrand'     => ['req'=>0,'ent'=>0,'pend'=>0],
            ]);
        }

        $eppIds = $epps->pluck('id_epp')->all();

        // 4) Mapa de OBLIGATORIEDAD: solo estos combos (puesto,epp) llevan contador
        $oblig = DB::table('puestos_epp as pe')
            ->whereIn('pe.id_puesto_trabajo', $puestoIds)
            ->whereIn('pe.id_epp', $eppIds)
            ->get(['pe.id_puesto_trabajo','pe.id_epp']);

        $isOblig = [];
        foreach ($oblig as $o) {
            $isOblig[$o->id_puesto_trabajo][$o->id_epp] = true;
        }

        // 5) ENTREGADOS por (puesto,epp) en el año (empleados distintos del puesto)
        $entregados = DB::table('asignacion_epp as asig')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'asig.id_empleado')
            ->select('emp.id_puesto_trabajo as puesto_id', 'asig.id_epp', DB::raw('COUNT(DISTINCT asig.id_empleado) as cnt'))
            ->whereIn('emp.id_puesto_trabajo', $puestoIds)
            ->whereIn('asig.id_epp', $eppIds)
            ->whereYear('asig.fecha_entrega_epp', $anio)
            ->groupBy('emp.id_puesto_trabajo', 'asig.id_epp')
            ->get();

        $idxEnt = [];
        foreach ($entregados as $r) {
            $idxEnt[$r->puesto_id][$r->id_epp] = (int)$r->cnt;
        }

        // 6) PIVOT por puesto y TOTALES por EPP (solo combos obligatorios)
        $pivot   = [];
        $totales = [];
        foreach ($epps as $e) {
            $totales[$e->id_epp] = ['req'=>0, 'ent'=>0, 'pend'=>0];
        }

        foreach ($puestos as $p) {
            $row = [];
            $reqPuesto = (int)($totEmpleados[$p->id_puesto_trabajo] ?? 0);

            foreach ($epps as $e) {
                if (empty($isOblig[$p->id_puesto_trabajo][$e->id_epp])) {
                    $row[$e->id_epp] = null; // NO obligatorio => celda en blanco
                    continue;
                }
                $ent  = (int)($idxEnt[$p->id_puesto_trabajo][$e->id_epp] ?? 0);
                $req  = $reqPuesto;                 // 1 por empleado
                $pend = max(0, $req - $ent);

                $row[$e->id_epp] = ['req'=>$req,'ent'=>$ent,'pend'=>$pend];

                // Totales por EPP (solo obligatorios)
                $totales[$e->id_epp]['req']  += $req;
                $totales[$e->id_epp]['ent']  += $ent;
                $totales[$e->id_epp]['pend'] += $pend;
            }

            $pivot[$p->id_puesto_trabajo] = $row;
        }

        // 7) AGRUPACIÓN PARA LA TABLA DETALLADA: Departamento -> (Subtotal + Puestos)
        $deptPuestos = [];   // dept => [puestos...]
        $deptEmp     = [];   // dept => total empleados (suma de puestos)
        $deptPivot   = [];   // dept => [epp_id => ['req','ent','pend']]

        foreach ($puestos as $p) {
            $dep = $p->departamento ?: 'Sin departamento';

            // lista de puestos por depto
            $deptPuestos[$dep] = $deptPuestos[$dep] ?? [];
            $deptPuestos[$dep][] = $p;

            // total empleados por depto
            $deptEmp[$dep] = ($deptEmp[$dep] ?? 0) + (int)($totEmpleados[$p->id_puesto_trabajo] ?? 0);

            // acumular por EPP en el depto (solo celdas obligatorias)
            foreach ($epps as $e) {
                $cell = $pivot[$p->id_puesto_trabajo][$e->id_epp] ?? null;
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

        // 8) === RESUMEN POR DEPARTAMENTO (para la tabla de arriba) ===
        $deptTotals = [];           // dept => ['req','ent','pend']
        foreach ($deptOrder as $dep) {
            $deptTotals[$dep] = ['req'=>0,'ent'=>0,'pend'=>0];
            if (!empty($deptPivot[$dep])) {
                foreach ($deptPivot[$dep] as $eppId => $vals) {
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
            'totEmpleados'  => $totEmpleados->toArray(),

            // Para la misma tabla con agrupación
            'deptOrder'     => $deptOrder,
            'deptPuestos'   => $deptPuestos,
            'deptEmp'       => $deptEmp,
            'deptPivot'     => $deptPivot,

            // Para el resumen superior
            'deptTotals'    => $deptTotals,
            'deptGrand'     => $deptGrand,
        ]);
    }
}
