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

        $estandar = DB::table('estandar_iluminacion as ei')
    ->join('localizacion as lo', 'ei.id_localizacion', '=', 'lo.id_localizacion')
    ->join('puesto_trabajo_matriz as ptm', 'ptm.id_localizacion', '=', 'lo.id_localizacion')
    ->join('identificacion_riesgos as ir', 'ptm.id_puesto_trabajo_matriz', '=', 'ir.id_puesto_trabajo_matriz')
    ->where('ir.id_puesto_trabajo_matriz', $puestoId)
    ->select('ptm.puesto_trabajo_matriz', 'lo.localizacion', 'ei.em', 'ir.tipo_esfuerzo_visual', 'ir.nivel_mediciones_visual', 'ir.tiempo_exposicion_visual')
    ->get();

        // Catalogo de puestos para el selector
        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz', 'puesto_trabajo_matriz', 'num_empleados')
            ->orderBy('puesto_trabajo_matriz')
            ->get();

        $rows = [];

        if ($puestoId) {
            // Toma el último registro de identificacion_riesgos para ese puesto (ajusta si quieres "todos")
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
                // Mapeo de columnas → filas del reporte
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

                    // Filtro de búsqueda libre (en cualquiera de las columnas visibles)
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

        $extra = null;

        if ($puestoId) {
            $extra = DB::table('identificacion_riesgos')
                ->where('id_puesto_trabajo_matriz', $puestoId)
                ->select([
                    'tipo_esfuerzo_visual',
                    'tiempo_exposicion_visual',
                    'descripcion_ruido',
                    'tiempo_exposicion_ruido',
                    'descripcion_temperatura',
                    'tiempo_exposicion_temperatura',
                ])
                ->first();
        }

        return view('riesgos.fisico_por_puesto', [
            // lo que ya mandas:
            'puestos'   => $puestos,
            'puestoId'  => $puestoId,
            'buscar'    => $q,
            'rows'      => $rows,
            // nuevo:
            'extra'     => $extra,
        ]);
    }
}
