<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiesgosReportController extends Controller
{
    public function fisicoPorPuesto(Request $r)
    {
        $puestoId = (int) $r->query('puesto');
        $q        = trim((string) $r->query('q', ''));

        // Catálogo de puestos para el selector
        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz', 'puesto_trabajo_matriz', 'num_empleados')
            ->orderBy('puesto_trabajo_matriz')
            ->get();

        $rows = [];

        if ($puestoId) {
            // Último registro de identificación para mapear "Esfuerzo Físico" (cargar/halar/empujar/sujetar)
            $ir = DB::table('identificacion_riesgos AS ir')
                ->join('puesto_trabajo_matriz AS p', 'p.id_puesto_trabajo_matriz', '=', 'ir.id_puesto_trabajo_matriz')
                ->where('ir.id_puesto_trabajo_matriz', $puestoId)
                ->select(
                    'ir.*',
                    'p.puesto_trabajo_matriz AS puesto',
                    'p.num_empleados'
                )
                ->orderByDesc('ir.id_identificacion_riesgos')
                ->first();

            if ($ir) {
                $map = [
                    'Cargar' => [
                        'tipo'       => 'tipo_esfuerzo_cargar',
                        'desc'       => 'descripcion_carga_cargar',
                        'equipo'     => 'equipo_apoyo_cargar',
                        'duracion'   => 'duracion_carga_cargar',
                        'distancia'  => 'distancia_carga_cargar',
                        'frecuencia' => 'frecuencia_carga_cargar',
                        'peso'       => 'peso_cargar',
                    ],
                    'Halar' => [
                        'tipo'       => 'tipo_esfuerzo_halar',
                        'desc'       => 'descripcion_carga_halar',
                        'equipo'     => 'equipo_apoyo_halar',
                        'duracion'   => 'duracion_carga_halar',
                        'distancia'  => 'distancia_carga_halar',
                        'frecuencia' => 'frecuencia_carga_halar',
                        'peso'       => 'peso_halar',
                    ],
                    'Empujar' => [
                        'tipo'       => 'tipo_esfuerzo_empujar',
                        'desc'       => 'descripcion_carga_empujar',
                        'equipo'     => 'equipo_apoyo_empujar',
                        'duracion'   => 'duracion_carga_empujar',
                        'distancia'  => 'distancia_carga_empujar',
                        'frecuencia' => 'frecuencia_carga_empujar',
                        'peso'       => 'peso_empujar',
                    ],
                    'Sujetar' => [
                        'tipo'       => 'tipo_esfuerzo_sujetar',
                        'desc'       => 'descripcion_carga_sujetar',
                        'equipo'     => 'equipo_apoyo_sujetar',
                        'duracion'   => 'duracion_carga_sujetar',
                        'distancia'  => 'distancia_carga_sujetar',
                        'frecuencia' => 'frecuencia_carga_sujetar',
                        'peso'       => 'peso_sujetar',
                    ],
                ];

                foreach ($map as $fallbackLabel => $f) {
                    $row = [
                        'puesto'        => $ir->puesto,
                        'num_empleados' => $ir->num_empleados,
                        'tipo'          => $ir->{$f['tipo']}       ?: $fallbackLabel,
                        'descripcion'   => $ir->{$f['desc']}       ?: null,
                        'equipo'        => $ir->{$f['equipo']}     ?: null,
                        'duracion'      => $ir->{$f['duracion']}   ?: null,
                        'distancia'     => $ir->{$f['distancia']}  ?: null,
                        'frecuencia'    => $ir->{$f['frecuencia']} ?: null,
                        'peso'          => $ir->{$f['peso']}       ?: null,
                    ];

                    if ($q !== '') {
                        $hay = false;
                        foreach (['tipo','descripcion','equipo','duracion','distancia','frecuencia','peso'] as $k) {
                            if ($row[$k] && stripos($row[$k], $q) !== false) { $hay = true; break; }
                        }
                        if (!$hay) { continue; }
                    }

                    $rows[] = $row;
                }
            }
        }

        // ====== NUEVO: Tablas separadas por puesto ======
        $visualRows  = collect();
        $ruidoRows   = collect();
        $termicoRows = collect();

        if ($puestoId) {
            // ident_esfuerzo_visual
            $visualRows = DB::table('ident_esfuerzo_visual AS ev')
                ->where('ev.id_puesto_trabajo_matriz', $puestoId)
                ->when($q !== '', function ($qb) use ($q) {
                    $qb->where(function ($w) use ($q) {
                        $w->where('ev.tipo_esfuerzo_visual', 'like', "%{$q}%")
                          ->orWhere('ev.tiempo_exposicion', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('ev.id_ident_esfuerzo_visual')
                ->get([
                    'ev.tipo_esfuerzo_visual AS tipo',
                    'ev.tiempo_exposicion AS tiempo',
                ]);

            // ident_exposicion_ruido
            $ruidoRows = DB::table('ident_exposicion_ruido AS er')
                ->where('er.id_puesto_trabajo_matriz', $puestoId)
                ->when($q !== '', function ($qb) use ($q) {
                    $qb->where(function ($w) use ($q) {
                        $w->where('er.descripcion_ruido', 'like', "%{$q}%")
                          ->orWhere('er.duracion_exposicion', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('er.id_ident_exposicion_ruido')
                ->get([
                    'er.descripcion_ruido AS descripcion',
                    'er.duracion_exposicion AS duracion',
                ]);

            // ident_estres_termico
            $termicoRows = DB::table('ident_estres_termico AS et')
                ->where('et.id_puesto_trabajo_matriz', $puestoId)
                ->when($q !== '', function ($qb) use ($q) {
                    $qb->where(function ($w) use ($q) {
                        $w->where('et.descripcion_stress_termico', 'like', "%{$q}%")
                          ->orWhere('et.duracion_exposicion', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('et.id_ident_estres_termico')
                ->get([
                    'et.descripcion_stress_termico AS descripcion',
                    'et.duracion_exposicion AS duracion',
                ]);
        }

        return view('riesgos.fisico_por_puesto', [
            'puestos'     => $puestos,
            'puestoId'    => $puestoId,
            'buscar'      => $q,
            'rows'        => $rows,
            // nuevos datasets
            'visualRows'  => $visualRows,
            'ruidoRows'   => $ruidoRows,
            'termicoRows' => $termicoRows,
        ]);
    }
}
