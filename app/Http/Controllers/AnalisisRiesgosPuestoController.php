<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisisRiesgosPuestoController extends Controller
{
    public function index(Request $request)
    {
        $puestoId = (int) $request->query('puesto');

        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz', 'puesto_trabajo_matriz')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '!=', 0);
            })
            ->orderBy('puesto_trabajo_matriz')
            ->get();

        $puestoDetalle = null;
        $riesgosPorTipo = [];
        $resumenMedidas = [
            'epp'          => [],
            'capacitacion' => [],
            'senalizacion' => [],
            'otras'        => [],
        ];
        $totales = ['si' => 0, 'no' => 0];
        $fisico = ['cargas' => [], 'visual' => [], 'ruido' => [], 'termico' => []];
        $quimicos = ['rows' => []];
        $medidasControl = null;
        $actividades = [];

        if ($puestoId > 0) {
            $puestoDetalle = DB::table('puesto_trabajo_matriz')
                ->select(
                    'id_puesto_trabajo_matriz',
                    'puesto_trabajo_matriz',
                    'num_empleados',
                    'descripcion_general',
                    'actividades_diarias'
                )
                ->where('id_puesto_trabajo_matriz', $puestoId)
                ->first();

            $riesgos = DB::table('riesgo as r')
                ->join('tipo_riesgo as tr', 'tr.id_tipo_riesgo', '=', 'r.id_tipo_riesgo')
                ->leftJoin('riesgo_valor as rv', function ($join) use ($puestoId) {
                    $join->on('rv.id_riesgo', '=', 'r.id_riesgo')
                         ->where('rv.id_puesto_trabajo_matriz', '=', $puestoId);
                })
                ->select([
                    'r.id_riesgo',
                    'r.nombre_riesgo',
                    'tr.id_tipo_riesgo',
                    'tr.tipo_riesgo',
                    DB::raw("COALESCE(NULLIF(TRIM(rv.valor), ''), 'No') as valor"),
                    'rv.observaciones',
                ])
                ->orderBy('tr.tipo_riesgo')
                ->orderBy('r.nombre_riesgo')
                ->get();

            $riesgoIds = $riesgos->pluck('id_riesgo')->filter()->unique()->values()->all();

            $medidasPorRiesgo = !empty($riesgoIds)
                ? $this->fetchMedidasPorRiesgo($riesgoIds)
                : [];

            foreach ($riesgos as $row) {
                $tipo = (string) ($row->tipo_riesgo ?? 'SIN TIPO');

                if (!isset($riesgosPorTipo[$tipo])) {
                    $riesgosPorTipo[$tipo] = [
                        'tipo'    => $tipo,
                        'riesgos' => [],
                    ];
                }

                $valor = (string) $row->valor;
                $esSi = $this->esValorSi($valor);
                $esNo = $this->esValorNo($valor);

                if ($esSi) {
                    $totales['si']++;
                } elseif ($esNo) {
                    $totales['no']++;
                }

                $medidas = $medidasPorRiesgo[(int) $row->id_riesgo] ?? [
                    'epp'          => [],
                    'capacitacion' => [],
                    'senalizacion' => [],
                    'otras'        => [],
                ];

                if ($esSi) {
                    foreach ($medidas as $categoria => $lista) {
                        if (!empty($lista)) {
                            $resumenMedidas[$categoria] = $this->mergeUnique($resumenMedidas[$categoria], $lista);
                        }
                    }
                }

                $riesgosPorTipo[$tipo]['riesgos'][] = [
                    'id'            => (int) $row->id_riesgo,
                    'nombre'        => (string) $row->nombre_riesgo,
                    'valor'         => $valor,
                    'es_si'         => $esSi,
                    'es_no'         => $esNo,
                    'observaciones' => $row->observaciones,
                    'medidas'       => $medidas,
                ];
            }

            $riesgosPorTipo = collect($riesgosPorTipo)
                ->sortBy(fn ($grupo) => $grupo['tipo'])
                ->map(function ($grupo) {
                    $grupo['riesgos'] = collect($grupo['riesgos'])
                        ->sortBy('nombre')
                        ->values()
                        ->all();
                    return $grupo;
                })
                ->values()
                ->all();

            $fisico = $this->fetchFisico($puestoId);
            $quimicos = $this->fetchQuimicos($puestoId);
            $actividades = $this->parseActividades($puestoDetalle->actividades_diarias ?? null);

            $medidasControl = DB::table('medidas_control as mc')
                ->leftJoin('probabilidad as p', 'p.id_probabilidad', '=', 'mc.id_probabilidad')
                ->leftJoin('consecuencia as c', 'c.id_consecuencia', '=', 'mc.id_consecuencia')
                ->leftJoin('nivel_riesgo as nr', 'nr.id_nivel_riesgo', '=', 'mc.id_nivel_riesgo')
                ->where('mc.id_puesto_trabajo_matriz', $puestoId)
                ->select([
                    'mc.id_medidas_control',
                    'mc.eliminacion',
                    'mc.sustitucion',
                    'mc.aislar',
                    'mc.control_ingenieria',
                    'mc.control_administrativo',
                    'p.probabilidad',
                    'c.consecuencia',
                    'nr.nivel_riesgo',
                ])
                ->first();
        }

        return view('riesgos.analisis_puesto', [
            'puestos'        => $puestos,
            'puestoId'       => $puestoId,
            'puestoDetalle'  => $puestoDetalle,
            'riesgosPorTipo' => $riesgosPorTipo,
            'resumenMedidas' => $resumenMedidas,
            'totales'        => $totales,
            'fisico'         => $fisico,
            'quimicos'       => $quimicos,
            'medidasControl' => $medidasControl,
            'actividades'    => $actividades,
        ]);
    }

    private function fetchMedidasPorRiesgo(array $riesgoIds): array
    {
        $rows = DB::table('medidas_riesgo_puesto as mrp')
            ->whereIn('mrp.id_riesgo', $riesgoIds)
            ->leftJoin('epp as e', 'e.id_epp', '=', 'mrp.id_epp')
            ->leftJoin('capacitacion as c', 'c.id_capacitacion', '=', 'mrp.id_capacitacion')
            ->leftJoin('senalizacion as s', 's.id_senalizacion', '=', 'mrp.id_senalizacion')
            ->leftJoin('otras_medidas as o', 'o.id_otras_medidas', '=', 'mrp.id_otras_medidas')
            ->select([
                'mrp.id_riesgo',
                'mrp.id_epp',
                'mrp.id_capacitacion',
                'mrp.id_senalizacion',
                'mrp.id_otras_medidas',
                'e.equipo as epp_equipo',
                'e.marca as epp_marca',
                'e.codigo as epp_codigo',
                'c.capacitacion as capacitacion_nombre',
                's.senalizacion as senalizacion_nombre',
                'o.otras_medidas as otras_nombre',
            ])
            ->orderBy('mrp.id_riesgo')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $id = (int) $row->id_riesgo;

            if (!isset($map[$id])) {
                $map[$id] = [
                    'epp'          => [],
                    'capacitacion' => [],
                    'senalizacion' => [],
                    'otras'        => [],
                ];
            }

            if ($row->id_epp) {
                $map[$id]['epp'][] = $this->formatEppNombre($row);
            }
            if ($row->id_capacitacion) {
                $map[$id]['capacitacion'][] = $this->formatTextoCatalogo($row->capacitacion_nombre, 'Capacitacion #'.$row->id_capacitacion);
            }
            if ($row->id_senalizacion) {
                $map[$id]['senalizacion'][] = $this->formatTextoCatalogo($row->senalizacion_nombre, 'Senalizacion #'.$row->id_senalizacion);
            }
            if ($row->id_otras_medidas) {
                $map[$id]['otras'][] = $this->formatTextoCatalogo($row->otras_nombre, 'Medida #'.$row->id_otras_medidas);
            }
        }

        foreach ($map as $id => $grupo) {
            foreach ($grupo as $tipo => $lista) {
                $map[$id][$tipo] = $this->uniqueStrings($lista);
            }
        }

        return $map;
    }

    private function fetchFisico(int $puestoId): array
    {
        $result = [
            'cargas'  => [],
            'visual'  => [],
            'ruido'   => [],
            'termico' => [],
        ];

        $ir = DB::table('identificacion_riesgos as ir')
            ->join('puesto_trabajo_matriz as p', 'p.id_puesto_trabajo_matriz', '=', 'ir.id_puesto_trabajo_matriz')
            ->where('ir.id_puesto_trabajo_matriz', $puestoId)
            ->select('ir.*', 'p.puesto_trabajo_matriz as puesto', 'p.num_empleados')
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

            foreach ($map as $fallback => $fields) {
                $row = [
                    'tipo'       => $ir->{$fields['tipo']} ?: $fallback,
                    'descripcion'=> $ir->{$fields['desc']} ?: null,
                    'equipo'     => $ir->{$fields['equipo']} ?: null,
                    'duracion'   => $ir->{$fields['duracion']} ?: null,
                    'distancia'  => $ir->{$fields['distancia']} ?: null,
                    'frecuencia' => $ir->{$fields['frecuencia']} ?: null,
                    'peso'       => $ir->{$fields['peso']} ?: null,
                ];

                if ($this->filaTieneDatos($row)) {
                    $result['cargas'][] = $row;
                }
            }
        }

        $result['visual'] = DB::table('ident_esfuerzo_visual as ev')
            ->where('ev.id_puesto_trabajo_matriz', $puestoId)
            ->orderByDesc('ev.id_ident_esfuerzo_visual')
            ->get([
                'ev.tipo_esfuerzo_visual as tipo',
                'ev.tiempo_exposicion as tiempo',
            ])
            ->map(fn ($r) => [
                'tipo'   => $r->tipo,
                'tiempo' => $r->tiempo,
            ])
            ->all();

        $result['ruido'] = DB::table('ident_exposicion_ruido as er')
            ->where('er.id_puesto_trabajo_matriz', $puestoId)
            ->orderByDesc('er.id_ident_exposicion_ruido')
            ->get([
                'er.descripcion_ruido as descripcion',
                'er.duracion_exposicion as duracion',
            ])
            ->map(fn ($r) => [
                'descripcion' => $r->descripcion,
                'duracion'    => $r->duracion,
            ])
            ->all();

        $result['termico'] = DB::table('ident_estres_termico as et')
            ->where('et.id_puesto_trabajo_matriz', $puestoId)
            ->orderByDesc('et.id_ident_estres_termico')
            ->get([
                'et.descripcion_stress_termico as descripcion',
                'et.duracion_exposicion as duracion',
            ])
            ->map(fn ($r) => [
                'descripcion' => $r->descripcion,
                'duracion'    => $r->duracion,
            ])
            ->all();

        return $result;
    }

    private function fetchQuimicos(int $puestoId): array
    {
        $rows = [];

        $puesto = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz', 'puesto_trabajo_matriz', 'num_empleados', 'descripcion_general')
            ->where('id_puesto_trabajo_matriz', $puestoId)
            ->first();

        $quimicos = DB::table('quimico_puesto as qp')
            ->join('quimico as q', 'q.id_quimico', '=', 'qp.id_quimico')
            ->where('qp.id_puesto_trabajo_matriz', $puestoId)
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
                'qp.duracion_exposicion',
            ]);

        $exposiciones = [];
        if ($quimicos->count()) {
            $exp = DB::table('quimico_tipo_exposicion as qte')
                ->join('tipo_exposicion as te', 'te.id_tipo_exposicion', '=', 'qte.id_tipo_exposicion')
                ->whereIn('qte.id_quimico', $quimicos->pluck('id_quimico')->all())
                ->orderBy('te.tipo_exposicion')
                ->get(['qte.id_quimico', 'te.tipo_exposicion']);

            foreach ($exp as $row) {
                $exposiciones[$row->id_quimico][] = $row->tipo_exposicion;
            }
        }

        foreach ($quimicos as $q) {
            $rows[] = [
                'puesto'             => $puesto?->puesto_trabajo_matriz,
                'num_empleados'      => $puesto?->num_empleados,
                'descripcion_general'=> $puesto?->descripcion_general,
                'quimico'            => $q,
                'exposicion'         => isset($exposiciones[$q->id_quimico]) ? implode(', ', $exposiciones[$q->id_quimico]) : null,
                'frecuencia'         => $q->frecuencia,
                'duracion_exposicion'=> $q->duracion_exposicion,
                'ninguno'            => $q->ninguno,
                'particulas_polvo'   => $q->particulas_polvo,
                'sustancias_corrosivas' => $q->sustancias_corrosivas,
                'sustancias_toxicas' => $q->sustancias_toxicas,
                'sustancias_irritantes' => $q->sustancias_irritantes,
                'salud'              => $q->salud,
                'inflamabilidad'     => $q->inflamabilidad,
                'reactividad'        => $q->reactividad,
            ];
        }

        return ['rows' => $rows];
    }

    private function formatEppNombre(object $row): string
    {
        $equipo = trim((string) ($row->epp_equipo ?? ''));
        if ($equipo !== '') {
            return $equipo;
        }

        $parts = array_filter([
            trim((string) ($row->epp_marca ?? '')),
            trim((string) ($row->epp_codigo ?? '')),
        ]);

        if (!empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return 'EPP #'.$row->id_epp;
    }

    private function formatTextoCatalogo(?string $valor, string $fallback): string
    {
        $texto = trim((string) ($valor ?? ''));
        return $texto !== '' ? $texto : $fallback;
    }

    private function uniqueStrings(array $items): array
    {
        $items = array_map(fn ($v) => trim((string) $v), $items);
        $items = array_filter($items, fn ($v) => $v !== '');
        return array_values(array_unique($items));
    }

    private function mergeUnique(array $base, array $adds): array
    {
        return $this->uniqueStrings(array_merge($base, $adds));
    }

    private function parseActividades(?string $texto): array
    {
        if ($texto === null) {
            return [];
        }

        $normalized = str_replace(["\r", "\t"], "\n", $texto);
        $parts = preg_split('/[\n;,]+/', $normalized) ?: [];

        $parts = array_map(fn ($v) => trim($v), $parts);
        $parts = array_filter($parts, fn ($v) => $v !== '');

        return array_values($parts);
    }

    private function filaTieneDatos(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function esValorSi(string $valor): bool
    {
        $v = mb_strtolower(trim($valor));
        $v = strtr($v, [
            "\u{00ed}" => 'i',
            "\u{00ec}" => 'i',
            "\u{00ef}" => 'i',
            "\u{00ee}" => 'i',
            "\u{fffd}" => 'i',
        ]);
        $v = rtrim($v, '.');

        return $v === '1' || str_starts_with($v, 'si');
    }

    private function esValorNo(string $valor): bool
    {
        $v = mb_strtolower(trim($valor));
        $v = rtrim($v, '.');

        return $v === '0' || str_starts_with($v, 'no');
    }
}