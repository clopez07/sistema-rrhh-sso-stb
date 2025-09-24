<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuimicosPorPuestoController extends Controller
{
    public function index(Request $request)
    {
        $puestoId      = $request->integer('puesto');
        $buscarQuimico = trim((string) $request->input('quimico', ''));

        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz')
            ->where(function ($q) {
                $q->where('estado', 1)->orWhereNull('estado');
            })
            ->orderBy('puesto_trabajo_matriz')
            ->get();

        $quimicos  = collect();
        $exposPorQuimico = [];
        $rows = [];

        $medidas = ['epp'=>[], 'capacitacion'=>[], 'senalizacion'=>[], 'otras'=>[]];

        if ($puestoId) {
            $puesto = DB::table('puesto_trabajo_matriz')
                ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz','num_empleados','descripcion_general')
                ->where('id_puesto_trabajo_matriz', $puestoId)
                ->first();

            $quimicos = DB::table('quimico_puesto as qp')
                ->join('quimico as q', 'q.id_quimico', '=', 'qp.id_quimico')
                ->where('qp.id_puesto_trabajo_matriz', $puestoId)
                ->when($buscarQuimico !== '', function ($q) use ($buscarQuimico) {
                    $q->where('q.nombre_comercial', 'like', "%{$buscarQuimico}%");
                })
                ->orderBy('q.nombre_comercial')
                ->get([
                    'q.id_quimico',
                    'q.nombre_comercial',
                    'q.uso',
                    'q.proveedor',
                    'q.salud',
                    'q.inflamabilidad',
                    'q.reactividad',
                    'q.ninguno',
                    'q.particulas_polvo',
                    'q.sustancias_corrosivas',
                    'q.sustancias_toxicas',
                    'q.sustancias_irritantes',
                    'qp.frecuencia',
                    'qp.duracion_exposicion'
                ]);

            if ($quimicos->count()) {
                $ids = $quimicos->pluck('id_quimico')->all();
                $exp = DB::table('quimico_tipo_exposicion as qte')
                    ->join('tipo_exposicion as te','te.id_tipo_exposicion','=','qte.id_tipo_exposicion')
                    ->whereIn('qte.id_quimico', $ids)
                    ->orderBy('te.tipo_exposicion')
                    ->get(['qte.id_quimico','te.tipo_exposicion']);
                foreach ($exp as $r) {
                    $exposPorQuimico[$r->id_quimico][] = $r->tipo_exposicion;
                }
            }

            foreach ($quimicos as $q) {
                $rows[] = [
                    'puesto'             => $puesto?->puesto_trabajo_matriz,
                    'num_empleados'      => $puesto?->num_empleados,
                    'descripcion_general'=> $puesto?->descripcion_general,
                    'quimico'            => $q,
                    'exposicion'         => isset($exposPorQuimico[$q->id_quimico]) ? implode(', ', $exposPorQuimico[$q->id_quimico]) : null,
                    'frecuencia'         => $q->frecuencia ?? null,
                    'duracion_exposicion'=> $q->duracion_exposicion ?? null,
                    'ninguno'            => $q->ninguno ?? null,
                    'particulas_polvo'   => $q->particulas_polvo ?? null,
                    'sustancias_corrosivas' => $q->sustancias_corrosivas ?? null,
                    'sustancias_toxicas' => $q->sustancias_toxicas ?? null,
                    'sustancias_irritantes' => $q->sustancias_irritantes ?? null,
                    'salud'              => $q->salud ?? null,
                    'inflamabilidad'     => $q->inflamabilidad ?? null,
                    'reactividad'        => $q->reactividad ?? null,
                ];
            }

            // Medidas (EPP, Capacitaciones, Señalización, Otras) iguales a Verificación
            $idsSi = DB::table('riesgo_valor')
                ->where('id_puesto_trabajo_matriz', $puestoId)
                ->whereIn(DB::raw('LOWER(TRIM(valor))'), ['si','sí','1'])
                ->pluck('id_riesgo')->all();

            if (!empty($idsSi)) {
                $epp = DB::table('medidas_riesgo_puesto as mrp')
                    ->join('epp as e', 'e.id_epp', '=', 'mrp.id_epp')
                    ->whereIn('mrp.id_riesgo', $idsSi)
                    ->select(DB::raw("COALESCE(NULLIF(TRIM(e.equipo),''), CONCAT_WS(' ', NULLIF(TRIM(e.marca),''), NULLIF(TRIM(e.codigo),''))) as nombre"))
                    ->pluck('nombre')->unique()->values()->all();

                $cap = DB::table('medidas_riesgo_puesto as mrp')
                    ->join('capacitacion as c', 'c.id_capacitacion', '=', 'mrp.id_capacitacion')
                    ->whereIn('mrp.id_riesgo', $idsSi)
                    ->select(DB::raw("COALESCE(NULLIF(TRIM(c.capacitacion),''), CONCAT('Capacitacion #', c.id_capacitacion)) as nombre"))
                    ->pluck('nombre')->unique()->values()->all();

                $sen = DB::table('medidas_riesgo_puesto as mrp')
                    ->join('senalizacion as s', 's.id_senalizacion', '=', 'mrp.id_senalizacion')
                    ->whereIn('mrp.id_riesgo', $idsSi)
                    ->select(DB::raw("COALESCE(NULLIF(TRIM(s.senalizacion),''), CONCAT('Señalización #', s.id_senalizacion)) as nombre"))
                    ->pluck('nombre')->unique()->values()->all();

                $otr = DB::table('medidas_riesgo_puesto as mrp')
                    ->join('otras_medidas as o', 'o.id_otras_medidas', '=', 'mrp.id_otras_medidas')
                    ->whereIn('mrp.id_riesgo', $idsSi)
                    ->select(DB::raw("COALESCE(NULLIF(TRIM(o.otras_medidas),''), CONCAT('Medida #', o.id_otras_medidas)) as nombre"))
                    ->pluck('nombre')->unique()->values()->all();

                $medidas = [
                    'epp'          => $epp,
                    'capacitacion' => $cap,
                    'senalizacion' => $sen,
                    'otras'        => $otr,
                ];
            }
        }

        return view('riesgos.quimicos_por_puesto', [
            'puestos'        => $puestos,
            'puestoId'       => $puestoId,
            'buscarQuimico'  => $buscarQuimico,
            'rows'           => $rows,
            'medidas'        => $medidas,
        ]);
    }
}
