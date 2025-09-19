<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InicioCapacitaciones extends Controller
{

public function Capacitaciones()
{
    $totalCapacitaciones = DB::table('capacitacion')->count();
    $totalEmpleados = DB::table('empleado')
        ->where('estado', 1)
        ->count();
    $totalInstructores = DB::table('instructor')->count();
    $totalAsistencias = DB::table('asistencia_capacitacion')->count();

    $mayoresAsistentes = DB::table('asistencia_capacitacion as ac')
        ->join('empleado as e', 'ac.id_empleado', '=', 'e.id_empleado')
        ->join('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
        ->select(
            'e.nombre_completo',
            'pt.puesto_trabajo',
            DB::raw("COUNT(*) as total_capacitaciones")
        )
        ->groupBy('e.id_empleado')
        ->orderByDesc('total_capacitaciones')
        ->limit(5)
        ->get();

    $ultimasCapacitaciones = DB::table('asistencia_capacitacion as ac')
        ->join('capacitacion_instructor as ci', 'ac.id_capacitacion_instructor', '=', 'ci.id_capacitacion_instructor')
        ->join('capacitacion as c', 'ci.id_capacitacion', '=', 'c.id_capacitacion')
        ->select('c.capacitacion', 'ac.fecha_recibida')
        ->orderByDesc('ac.fecha_recibida')
        ->limit(5)
        ->get();

    $totalHorasCapacitaciones = DB::table('asistencia_capacitacion as ac')
    ->join('capacitacion_instructor as ci', 'ac.id_capacitacion_instructor', '=', 'ci.id_capacitacion_instructor')
    ->select(DB::raw('SUM(ci.duracion) / 60 as total_horas'))
    ->first()
    ->total_horas;

    return view('capacitaciones.iniciocapacitaciones', compact(
        'totalCapacitaciones',
        'totalEmpleados',
        'totalInstructores',
        'totalAsistencias',
        'mayoresAsistentes',
        'ultimasCapacitaciones',
        'totalHorasCapacitaciones'
    ));
    }
}