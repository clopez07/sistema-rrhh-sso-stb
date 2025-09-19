<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InicioEPP extends Controller
{
    public function dashboard(Request $request)
    {
        $empleadosConEPP = DB::table('asignacion_epp')
            ->distinct('id_empleado')
            ->count('id_empleado');

        $eppMesActual = DB::table('asignacion_epp')
            ->whereMonth(
                DB::raw("STR_TO_DATE(REPLACE(fecha_entrega_epp, '/', '-'), '%Y-%m-%d')"),
                now()->month
            )
            ->count();

        $totalEPP = DB::table('epp')->count();

        $totalAsignaciones = DB::table('asignacion_epp')->count();

        $totalEntregados = DB::table('asignacion_epp')->count();

        $cantidadesEPP = DB::table('asignacion_epp as a')
            ->join('epp as e', 'a.id_epp', '=', 'e.id_epp')
            ->select('e.equipo', DB::raw('COUNT(*) as cantidad_entregada'))
            ->groupBy('e.id_epp', 'e.equipo')
            ->orderByDesc('cantidad_entregada')
            ->get();

        $eppId   = $request->query('epp_id');
        $anio    = $request->query('anio');
        $persona = $request->query('persona');

        $listaEpp = DB::table('epp')
            ->orderBy('equipo')
            ->get(['id_epp','equipo']);

        $anios = DB::table('asignacion_epp')
            ->selectRaw("DISTINCT YEAR(STR_TO_DATE(REPLACE(fecha_entrega_epp,'/','-'), '%d-%m-%Y')) as anio")
            ->orderByDesc('anio')
            ->pluck('anio');

        $entregasPorEpp = collect();
        $eppNombre = null;
        if (!empty($eppId)) {
            $entregasQuery = DB::table('asignacion_epp as ae')
                ->join('empleado as emp', 'ae.id_empleado', '=', 'emp.id_empleado')
                ->join('epp as e', 'ae.id_epp', '=', 'e.id_epp')
                ->select(
                    'emp.nombre_completo',
                    'e.equipo as epp',
                    'ae.fecha_entrega_epp'
                )
                ->where('ae.id_epp', $eppId);

            if (!empty($anio)) {
                $entregasQuery->whereRaw(
                    "YEAR(STR_TO_DATE(REPLACE(ae.fecha_entrega_epp,'/','-'), '%d-%m-%Y')) = ?",
                    [$anio]
                );
            }

            if (!empty($persona)) {
                $entregasQuery->where('emp.nombre_completo', 'like', "%{$persona}%");
            }

            $entregasPorEpp = $entregasQuery
                ->orderByRaw("STR_TO_DATE(REPLACE(ae.fecha_entrega_epp,'/','-'), '%d-%m-%Y') DESC")
                ->get();

            $eppNombre = optional($listaEpp->firstWhere('id_epp', (int)$eppId))->equipo;
        }

        $cantidadesPorAnio = collect();
        if (!empty($anio) && empty($eppId)) {
            $cantidadesPorAnio = DB::table('asignacion_epp as a')
                ->join('epp as e', 'a.id_epp', '=', 'e.id_epp')
                ->whereRaw(
                    "YEAR(STR_TO_DATE(REPLACE(a.fecha_entrega_epp,'/','-'), '%d-%m-%Y')) = ?",
                    [$anio]
                )
                ->select('e.equipo', DB::raw('COUNT(*) as cantidad_entregada'))
                ->groupBy('e.id_epp', 'e.equipo')
                ->orderByDesc('cantidad_entregada')
                ->get();
        }

        return view('epp.inicioepp', compact(
            'empleadosConEPP',
            'eppMesActual',
            'totalEPP',
            'totalAsignaciones',
            'totalEntregados',
            'cantidadesEPP',
            'listaEpp',
            'anios',
            'eppId',
            'anio',
            'entregasPorEpp',
            'eppNombre',
            'cantidadesPorAnio',
            'persona'
        ));
    }
}
