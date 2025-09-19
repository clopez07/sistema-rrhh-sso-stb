<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PuestoTrabajoMatrizController extends Controller
{
    public function index()
    {
        $puestos = DB::table('puesto_trabajo_matriz as p')
        ->leftJoin('departamento as d', 'p.id_departamento', '=', 'd.id_departamento')
            ->select(
                'p.id_puesto_trabajo_matriz',
                'p.puesto_trabajo_matriz',
                'p.id_departamento',
                'p.id_localizacion',
                'p.id_area',
                'p.num_empleados',
                'p.descripcion_general',
                'p.actividades_diarias',
                'p.objetivo_puesto',
                'p.estado',
                'd.departamento'
            )
            ->get();

            $quimicos = DB::table('quimico')
            ->select(
                '*'
            )
            ->get();

            $probabilidad = DB::table('probabilidad')
            ->select(
                '*'
            )
            ->get();

            $consecuencia = DB::table('consecuencia')
            ->select(
                '*'
            )
            ->get();

        $valoracionTabla = DB::table('valoracion_riesgo as v')
            ->join('nivel_riesgo as n','n.id_nivel_riesgo','=','v.id_nivel_riesgo')
            ->select('v.id_probabilidad','v.id_consecuencia','v.id_nivel_riesgo','n.nivel_riesgo')
            ->get();

        return view(
            'riesgos.identificacion-guardar',
            compact('puestos', 'quimicos' ,'probabilidad', 'consecuencia', 'valoracionTabla')
        );
    }
}
