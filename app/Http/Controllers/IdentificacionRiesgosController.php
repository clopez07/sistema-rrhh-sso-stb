<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class IdentificacionRiesgosController extends Controller
{
    /**
     * Normaliza valores triestado (SI/NO/NA) provenientes de la BD.
     */
    private function normalizeTriValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'SI' : 'NO';
        }

        if (is_numeric($value)) {
            $num = (int) $value;

            return match ($num) {
                1 => 'SI',
                2 => 'NA',
                default => 'NO',
            };
        }

        $s = strtoupper(trim((string) $value));
        if ($s === '') {
            return null;
        }

        $s = str_replace(
            ["\u{00C1}", "\u{00C9}", "\u{00CD}", "\u{00D3}", "\u{00DA}"],
            ['A', 'E', 'I', 'O', 'U'],
            $s
        );

        $normalized = preg_replace('/[.\-\/\s]/', '', $s);

        if (in_array($normalized, ['SI', 'S', 'YES', 'TRUE', 'VERDADERO'], true)) {
            return 'SI';
        }

        if (in_array($normalized, ['NO', 'N', 'FALSE', 'FALSO'], true)) {
            return 'NO';
        }

        if (in_array($normalized, ['NA', 'NOAPLICA'], true)) {
            return 'NA';
        }

        return null;
    }

    public function index()
    {
        $puestos = DB::table('puesto_trabajo_matriz as p')
            ->leftJoin('departamento as d', 'p.id_departamento', '=', 'd.id_departamento')
            ->leftJoin('identificacion_riesgos as ir', 'ir.id_puesto_trabajo_matriz', '=', 'p.id_puesto_trabajo_matriz')
            ->select(
                'p.id_puesto_trabajo_matriz',
                'p.puesto_trabajo_matriz',
                'p.id_departamento',
                'p.num_empleados',
                'p.descripcion_general',
                'p.actividades_diarias',
                'p.objetivo_puesto',
                'p.estado',
                'd.departamento',
                DB::raw('MAX(CASE WHEN ir.id_identificacion_riesgos IS NULL THEN 0 ELSE 1 END) AS tiene_ident')
            )
            ->groupBy(
                'p.id_puesto_trabajo_matriz',
                'p.puesto_trabajo_matriz',
                'p.id_departamento',
                'p.num_empleados',
                'p.descripcion_general',
                'p.actividades_diarias',
                'p.objetivo_puesto',
                'p.estado',
                'd.departamento'
            )
            ->get();

        $quimicos = DB::table('quimico')->select('*')->get();
        $probabilidad = DB::table('probabilidad')->select('*')->get();
        $consecuencia = DB::table('consecuencia')->select('*')->get();

        $valoracionTabla = DB::table('valoracion_riesgo as v')
            ->join('nivel_riesgo as n','n.id_nivel_riesgo','=','v.id_nivel_riesgo')
            ->select('v.id_probabilidad','v.id_consecuencia','v.id_nivel_riesgo','n.nivel_riesgo')
            ->get();

        return view(
            'riesgos.identificacion-guardar',
            compact('puestos', 'quimicos' ,'probabilidad', 'consecuencia', 'valoracionTabla')
        );
    }

    public function store(Request $r)
    {
        // ===== 0) Validación mínima y normalización =====
        $r->validate([
            'id_puesto_trabajo_matriz' => ['required','integer','exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz'],
            'ptm_num_empleados'        => ['nullable','integer','min:0'],
            'ptm_descripcion_general'  => ['nullable','string','max:1000'],
            'ptm_actividades_diarias'  => ['nullable','string','max:1000'],
            'ptm_objetivo_puesto'      => ['nullable','string','max:1000'],
        ], [], [
            'id_puesto_trabajo_matriz' => 'Puesto de Trabajo',
        ]);

        $idPuesto = (int) $r->input('id_puesto_trabajo_matriz');

        // --- Normaliza filas (visual/ruido/termico) y elimina vacías ---
        $visual = collect($r->input('visual', []))
            ->map(fn ($row) => [
                'tipo'   => trim($row['tipo']   ?? ''),
                'tiempo' => trim($row['tiempo'] ?? ''),
            ])
            ->filter(fn ($row) => $row['tipo'] !== '' || $row['tiempo'] !== '')
            ->values();

        $ruido = collect($r->input('ruido', []))
            ->map(fn ($row) => [
                'desc'     => trim($row['desc']     ?? ''),
                'duracion' => trim($row['duracion'] ?? ''),
            ])
            ->filter(fn ($row) => $row['desc'] !== '' || $row['duracion'] !== '')
            ->values();

        $termico = collect($r->input('termico', []))
            ->map(fn ($row) => [
                'desc'     => trim($row['desc']     ?? ''),
                'duracion' => trim($row['duracion'] ?? ''),
            ])
            ->filter(fn ($row) => $row['desc'] !== '' || $row['duracion'] !== '')
            ->values();

        // Helpers varios
        $cb  = fn($arr, $key) => in_array($key, $arr ?? []) ? 1 : 0; // checkboxes
        $yn = function (array $arr, $key): int {
        // Normaliza: trim, minúsculas (incluye "sí"), quita espacios y signos
        $s = mb_strtolower(trim((string)($arr[$key] ?? '')), 'UTF-8');
        $s = str_replace(['/', '.', ' '], '', $s); // "n/a", "n.a", "n a" => "na"

        return match ($s) {
            'si', 'sí', 's', '1' => 1,  // sí
            'n/a'                  => 2,  // n/a
            default               => 0,  // no u otros
        };
    };
        $rad = fn($arr, $i)   => $arr[$i]['estado'] ?? null;
        $obs = fn($arr, $i)   => $arr[$i]['obs']    ?? null;

        $inst    = $r->input('instalaciones', []);
        $maq     = $r->input('maq', []);
        $emer    = $r->input('emer', []);
        $ergo    = $r->input('ergo', []);
        $elecSel = collect($r->input('elec_verif', []))
            ->map(fn($v) => $v !== null ? strtoupper(trim($v)) : null)
            ->all();
        $caidaSel = collect($r->input('caida', []))
            ->map(fn($v) => $v !== null ? strtoupper(trim($v)) : null)
            ->all();
        $post    = $r->input('posturas', []);
        $fuego   = $r->input('riesgo_fuego', []);
        $alt     = $r->input('alturas', []);
        $quimReq = $r->input('quimicos', []);

        // ===== 1) Datos “maestros” para identificacion_riesgos =====
        // Nota: dejamos fuera los campos "de filas" (tipo/tiempo/desc) de visual/ruido/termico
        // porque ahora viven en sus propias tablas. Conservamos las mediciones (lux/dB/temp).
        $identData = [
            'id_puesto_trabajo_matriz'   => $idPuesto,

            // --- ESFUERZO FISICO ---
            'tipo_esfuerzo_cargar'       => 'Cargar',
            'descripcion_carga_cargar'   => $r->input('fisico_cargar_desc'),
            'equipo_apoyo_cargar'        => $r->input('fisico_cargar_equipo'),
            'duracion_carga_cargar'      => $r->input('fisico_cargar_duracion'),
            'distancia_carga_cargar'     => $r->input('fisico_cargar_distancia'),
            'epp_cargar'                 => $r->input('fisico_cargar_epp'),
            'frecuencia_carga_cargar'    => $r->input('fisico_cargar_frecuencia'),
            'peso_cargar'                => $r->input('fisico_cargar_peso'),

            'tipo_esfuerzo_halar'        => 'Halar',
            'descripcion_carga_halar'    => $r->input('fisico_halar_desc'),
            'equipo_apoyo_halar'         => $r->input('fisico_halar_equipo'),
            'duracion_carga_halar'       => $r->input('fisico_halar_duracion'),
            'distancia_carga_halar'      => $r->input('fisico_halar_distancia'),
            'epp_halar'                  => $r->input('fisico_halar_epp'),
            'frecuencia_carga_halar'     => $r->input('fisico_halar_frecuencia'),
            'peso_halar'                 => $r->input('fisico_halar_peso'),

            'tipo_esfuerzo_empujar'      => 'Empujar',
            'descripcion_carga_empujar'  => $r->input('fisico_empujar_desc'),
            'equipo_apoyo_empujar'       => $r->input('fisico_empujar_equipo'),
            'duracion_carga_empujar'     => $r->input('fisico_empujar_duracion'),
            'distancia_carga_empujar'    => $r->input('fisico_empujar_distancia'),
            'epp_empujar'                => $r->input('fisico_empujar_epp'),
            'frecuencia_carga_empujar'   => $r->input('fisico_empujar_frecuencia'),
            'peso_empujar'               => $r->input('fisico_empujar_peso'),

            'tipo_esfuerzo_sujetar'      => 'Sujetar',
            'descripcion_carga_sujetar'  => $r->input('fisico_sujetar_desc'),
            'equipo_apoyo_sujetar'       => $r->input('fisico_sujetar_equipo'),
            'duracion_carga_sujetar'     => $r->input('fisico_sujetar_duracion'),
            'distancia_carga_sujetar'    => $r->input('fisico_sujetar_distancia'),
            'epp_sujetar'                => $r->input('fisico_sujetar_epp'),
            'frecuencia_carga_sujetar'   => $r->input('fisico_sujetar_frecuencia'),
            'peso_sujetar'               => $r->input('fisico_sujetar_peso'),

            // --- MEDICIONES (se guardan aquí) ---
            'nivel_mediciones_visual'       => $r->input('visual_lux'),
            'nivel_mediciones_ruido'        => $r->input('ruido_db'),
            'nivel_mediciones_temperatura'  => $r->input('termico_temp'),

            // --- INSTALACIONES ---
            'paredes_muros_losas_trabes'     => $rad($inst,0),
            'paredes_muros_losas_trabes_obs' => $obs($inst,0),
            'pisos'                           => $rad($inst,1),
            'pisos_obs'                       => $obs($inst,1),
            'techos'                          => $rad($inst,2),
            'techos_obs'                      => $obs($inst,2),
            'puertas_ventanas'                => $rad($inst,3),
            'puertas_ventanas_obs'            => $obs($inst,3),
            'escaleras_rampas'                => $rad($inst,4),
            'escaleras_rampas_obs'            => $obs($inst,4),
            'anaqueles_estanterias'           => $rad($inst,5),
            'anaqueles_estanterias_obs'       => $obs($inst,5),

            // --- MAQUINARIA ---
            'maquinaria_equipos'           => $rad($maq,0),
            'mantenimiento_preventivo'     => $rad($maq,1),
            'mantenimiento_correctivo'     => $rad($maq,2),
            'resguardos_guardas'           => $rad($maq,3),
            'conexiones_electricas'        => $rad($maq,4),
            'inspecciones_maquinaria'      => $rad($maq,5),
            'paros_emergencia'             => $rad($maq,6),
            'entrenamiento_maquinaria'     => $rad($maq,7),
            'epp_correspondiente'          => $rad($maq,8),
            'estado_herramientas'          => $rad($maq,9),
            'inspecciones_herramientas'    => $rad($maq,10),
            'almacenamiento_herramientas'  => $rad($maq,11),

            'maquinaria_equipos_obs'           => $obs($maq,0),
            'mantenimiento_preventivo_obs'     => $obs($maq,1),
            'mantenimiento_correctivo_obs'     => $obs($maq,2),
            'resguardos_guardas_obs'           => $obs($maq,3),
            'conexiones_electricas_obs'        => $obs($maq,4),
            'inspecciones_maquinaria_obs'      => $obs($maq,5),
            'paros_emergencia_obs'             => $obs($maq,6),
            'entrenamiento_maquinaria_obs'     => $obs($maq,7),
            'epp_correspondiente_obs'          => $obs($maq,8),
            'estado_herramientas_obs'          => $obs($maq,9),
            'inspecciones_herramientas_obs'    => $obs($maq,10),
            'almacenamiento_herramientas_obs'  => $obs($maq,11),

            // --- EMERGENCIA ---
            'rutas_evacuacion'        => $rad($emer,0),
            'extintores_mangueras'    => $rad($emer,1),
            'camillas'                => $rad($emer,2),
            'botiquin'                => $rad($emer,3),
            'simulacros'              => $rad($emer,4),
            'plan_evacuacion'         => $rad($emer,5),
            'actuacion_emergencia'    => $rad($emer,6),
            'alarmas_emergencia'      => $rad($emer,7),
            'alarmas_humo'            => $rad($emer,8),
            'lamparas_emergencia'     => $rad($emer,9),

            'rutas_evacuacion_obs'      => $obs($emer,0),
            'extintores_mangueras_obs'  => $obs($emer,1),
            'camillas_obs'              => $obs($emer,2),
            'botiquin_obs'              => $obs($emer,3),
            'simulacros_obs'            => $obs($emer,4),
            'plan_evacuacion_obs'       => $obs($emer,5),
            'actuacion_emergencia_obs'  => $obs($emer,6),
            'alarmas_emergencia_obs'    => $obs($emer,7),
            'alarmas_humo_obs'          => $obs($emer,8),
            'lamparas_emergencia_obs'   => $obs($emer,9),

            // --- FUEGO/EXPLOSIÓN ---
            'sustancias_inflamables'        => $yn($fuego,'inflamables_area'),
            'ventilacion_natural'           => $yn($fuego,'ventilacion_extraccion'),
            'limpiezas_regulares'           => $yn($fuego,'limpieza_regulares'),
            'senalización_de_riesgos'       => $yn($fuego,'senalizacion_riesgo'),
            'fuentes_calor'                 => $yn($fuego,'focos_ignicion'),
            'maquinaria_friccion'           => $yn($fuego,'riesgo_electrico_friccion'),
            'trasiego_liquidos'             => $yn($fuego,'trasiego_combustibles'),
            'cilindros_presion'             => $yn($fuego,'cilindros_alta_presion'),
            'derrames_sustancias'           => $yn($fuego,'derrames_combustibles'),

            // --- ERGONÓMICO ---
            'movimientos_repetitivos' => $ergo[0]['resp'] ?? null,
            'posturas_forzadas'       => $ergo[1]['resp'] ?? null,
            'suficiente_espacio'      => $ergo[2]['resp'] ?? null,
            'elevacion_brazos'        => $ergo[3]['resp'] ?? null,
            'giros_muneca'            => $ergo[4]['resp'] ?? null,
            'inclinacion_espalda'     => $ergo[5]['resp'] ?? null,
            'herramienta_constante'   => $ergo[6]['resp'] ?? null,
            'herramienta_vibracion'   => $ergo[7]['resp'] ?? null,

            'movimientos_repetitivos_obs' => $ergo[0]['obs'] ?? null,
            'posturas_forzadas_obs'       => $ergo[1]['obs'] ?? null,
            'suficiente_espacio_obs'      => $ergo[2]['obs'] ?? null,
            'elevacion_brazos_obs'        => $ergo[3]['obs'] ?? null,
            'giros_muneca_obs'            => $ergo[4]['obs'] ?? null,
            'inclinacion_espalda_obs'     => $ergo[5]['obs'] ?? null,
            'herramienta_constante_obs'   => $ergo[6]['obs'] ?? null,
            'herramienta_vibracion_obs'   => $ergo[7]['obs'] ?? null,

            // --- POSTURAS ---
            'agachado'       => $cb($post,'agachado'),
            'rodillas'       => $cb($post,'de_rodillas'),
            'volteado'       => $cb($post,'volteado'),
            'parado'         => $cb($post,'parado'),
            'sentado'        => $cb($post,'sentado'),
            'arrastrandose'  => $cb($post,'arrastrandose'),
            'subiendo'       => $cb($post,'subiendo'),
            'balanceandose'  => $cb($post,'balanceandose'),
            'corriendo'      => $cb($post,'corriendo'),
            'empujando'      => $cb($post,'empujando'),
            'halando'        => $cb($post,'halando'),
            'girando'        => $cb($post,'girando'),

            // --- ALTURAS ---
            'altura'           => $alt['altura'] ?? null,
            'medios_anclaje'   => $alt['anclaje_seguro'] ?? null,
            'aviso_altura'     => $alt['aviso_trabajo_altura'] ?? null,
            'hoja_trabajo'     => $alt['firma_trabajo_seguro'] ?? null,

            // --- ELÉCTRICO ---
            'senalizacion_delimitacion'   => $r->input('elec_senalizacion'),
            'capacitacion_certificacion'  => $r->input('elec_capacitacion'),
            'alta_tension'                => $r->input('elec_alta_tension'),
            'trabajo_seguro_electrico'    => $r->input('elec_hoja_trabajo_seguro'),
            'zonas_estatica'              => $r->input('elec_estatica'),
            'ausencia_tension'            => $r->input('elec_ausencia_tension'),
            'bloqueo_tarjetas'            => $r->input('elec_bloqueo'),
            'aviso_trabajo_electrico'     => $r->input('elec_aviso'),
            'cables_ordenados'              => Arr::get($elecSel, 'cables_ordenados'),
            'tomacorrientes'                => Arr::get($elecSel, 'toma_lejos_humedad'),
            'cajas_interruptores'           => Arr::get($elecSel, 'cajas_rotuladas_cerradas'),
            'extensiones'                   => Arr::get($elecSel, 'buenas_condiciones'),
            'cables_aislamiento'            => Arr::get($elecSel, 'aislamiento_ok'),
            'senalizacion_riesgo_electrico' => Arr::get($elecSel, 'senalizacion_riesgo'),
            'observaciones_electrico'       => $r->input('elec_observaciones'),

            // --- CAÍDA MISMO NIVEL ---
            'pisos_adecuado'            => $caidaSel['pisos_adecuados']        ?? null,
            'vias_libres'               => $caidaSel['vias_libres']            ?? null,
            'rampas_identificados'      => $caidaSel['rampas_identificadas']   ?? null,
            'gradas_barandas'           => $caidaSel['gradas_barandas']        ?? null,
            'sistemas_antideslizante'   => $caidaSel['antideslizantes']        ?? null,
            'prevencion_piso_resbaloso' => $caidaSel['senalizacion_piso_resbaloso'] ?? null,
            'observaciones_caida_nivel' => $r->input('caida_observaciones'),

            // --- Firmas y fechas ---
            'evaluacion_realizada'        => $r->input('eval_realizada_por'),
            'fecha_evaluacion_realizada'  => $r->input('eval_realizada_fecha'),
            'evaluacion_revisada'         => $r->input('eval_revisada_por'),
            'fecha_evaluacion_revisada'   => $r->input('eval_revisada_fecha'),
            'fecha_proxima_evaluaci'      => $r->input('fecha_proxima_evaluacion'),
        ];

        // ===== 2) Catalogo de riesgos (para validar nombres) =====
        $normalize = function (?string $s) {
            $s = trim(mb_strtolower($s ?? '', 'UTF-8'));
            $s = preg_replace('/\s+/', ' ', $s);
            $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            return $s;
        };

        $catalogo = DB::table('riesgo')->select('id_riesgo','nombre_riesgo')->get();
        $riesgoMap = [];
        $riesgoIdsValidos = [];
        foreach ($catalogo as $ri) {
            $rid = (int)$ri->id_riesgo;
            $riesgoMap[$normalize($ri->nombre_riesgo)] = $rid;
            $riesgoIdsValidos[$rid] = true;
        }

        // Detectar índices r{n}_*
        $indices = [];
        foreach (array_keys($r->all()) as $k) {
            if (preg_match('/^r(\d+)_/', $k, $m)) $indices[(int)$m[1]] = true;
        }
        $indices = array_keys($indices);
        sort($indices);

        // Validar riesgos del form
        $riesgosForm = $r->input('riesgos', []);
        $missing = [];
        foreach ($indices as $i) {
            $idFromForm = data_get($riesgosForm, "$i.id_riesgo");
            $idFromForm = ($idFromForm !== null && $idFromForm !== '') ? (int)$idFromForm : null;
            if ($idFromForm && isset($riesgoIdsValidos[$idFromForm])) continue;
            $nombre = data_get($riesgosForm, "$i.nombre") ?? $r->input("r{$i}_riesgo_nombre");
            if ($nombre && isset($riesgoMap[$normalize($nombre)])) continue;
            if ($nombre) $missing[] = $nombre;
        }
        if ($missing) {
            return back()
                ->withErrors(['riesgos' => 'Riesgos no encontrados en catálogo: '.implode(', ', array_unique($missing))])
                ->withInput();
        }

        // ===== 3) Transacción única: PTM + Ident + Químicos + NUEVAS TABLAS + Riesgos =====
        DB::beginTransaction();
        try {
            // 3.0 Actualizar PTM
            DB::table('puesto_trabajo_matriz')
                ->where('id_puesto_trabajo_matriz', $idPuesto)
                ->update([
                    'num_empleados'       => $r->input('ptm_num_empleados'),
                    'descripcion_general' => $r->input('ptm_descripcion_general'),
                    'actividades_diarias' => $r->input('ptm_actividades_diarias'),
                    'objetivo_puesto'     => $r->input('ptm_objetivo_puesto'),
                ]);

            // 3.1 Identificación (upsert)
            DB::table('identificacion_riesgos')->updateOrInsert(
                ['id_puesto_trabajo_matriz' => $idPuesto],
                $identData
            );

            // 3.2 Químicos (reset + insert)
            DB::table('quimico_puesto')->where('id_puesto_trabajo_matriz', $idPuesto)->delete();

            $toInsert = [];
            foreach ($quimReq as $qr) {
                $qid = Arr::get($qr, 'id_quimico');
                if ($qid === null || $qid === '') continue;
                $toInsert[] = [
                    'id_quimico'               => (int)$qid,
                    'id_puesto_trabajo_matriz' => $idPuesto,
                    'duracion_exposicion'      => Arr::get($qr, 'duracion'),
                    'frecuencia'               => Arr::get($qr, 'frecuencia'),
                ];
            }
            if ($toInsert) {
                $allQ = DB::table('quimico')->whereIn('id_quimico', array_column($toInsert, 'id_quimico'))->pluck('id_quimico')->all();
                $faltantes = array_diff(array_column($toInsert, 'id_quimico'), $allQ);
                if ($faltantes) {
                    throw new \RuntimeException('PASO 2 (químicos): id_quimico inexistente: '.implode(', ', $faltantes));
                }
                DB::table('quimico_puesto')->insert($toInsert);
            }

            // 3.3 NUEVAS TABLAS: Visual / Ruido / Térmico
            // Limpia actuales
            DB::table('ident_esfuerzo_visual')->where('id_puesto_trabajo_matriz', $idPuesto)->delete();
            DB::table('ident_exposicion_ruido')->where('id_puesto_trabajo_matriz', $idPuesto)->delete();
            DB::table('ident_estres_termico')->where('id_puesto_trabajo_matriz', $idPuesto)->delete();

            // Inserta nuevas filas si las hay
            if ($visual->isNotEmpty()) {
                DB::table('ident_esfuerzo_visual')->insert(
                    $visual->map(fn ($row) => [
                        'id_puesto_trabajo_matriz' => $idPuesto,
                        'tipo_esfuerzo_visual'     => $row['tipo'],
                        'tiempo_exposicion'        => $row['tiempo'],
                    ])->all()
                );
            }
            if ($ruido->isNotEmpty()) {
                DB::table('ident_exposicion_ruido')->insert(
                    $ruido->map(fn ($row) => [
                        'id_puesto_trabajo_matriz' => $idPuesto,
                        'descripcion_ruido'        => $row['desc'],
                        'duracion_exposicion'      => $row['duracion'],
                    ])->all()
                );
            }
            if ($termico->isNotEmpty()) {
                DB::table('ident_estres_termico')->insert(
                    $termico->map(fn ($row) => [
                        'id_puesto_trabajo_matriz'   => $idPuesto,
                        'descripcion_stress_termico' => $row['desc'],
                        'duracion_exposicion'        => $row['duracion'],
                    ])->all()
                );
            }

            // 3.4 Riesgo_valor (SI/NO + obs)
            foreach ($indices as $i) {
                $riesgoId = null;
                $idFromForm = data_get($riesgosForm, "$i.id_riesgo");
                $idFromForm = ($idFromForm !== null && $idFromForm !== '') ? (int)$idFromForm : null;
                if ($idFromForm && isset($riesgoIdsValidos[$idFromForm])) {
                    $riesgoId = $idFromForm;
                } else {
                    $nombre   = data_get($riesgosForm, "$i.nombre") ?? $r->input("r{$i}_riesgo_nombre");
                    if ($nombre) $riesgoId = $riesgoMap[$normalize($nombre)] ?? null;
                }
                if (!$riesgoId) continue;

                $valor = $r->input("r{$i}_aplica");
                $valor = $valor !== null ? strtoupper($valor) : null; // 'SI'/'NO'/null
                $obsRv = $r->input("r{$i}_obs");

                DB::table('riesgo_valor')->updateOrInsert(
                    [
                        'id_puesto_trabajo_matriz' => $idPuesto,
                        'id_riesgo'                => $riesgoId,
                    ],
                    [
                        'valor'         => $valor,
                        'observaciones' => $obsRv ?: null,
                    ]
                );
            }

            // 3.5 Evaluacion_riesgo (prob, cons, nivel) si los 3 IDs vienen
            for ($k = 0; $k < count($indices); $k++) { // o foreach, cualquiera
                $i = $indices[$k];

                $riesgoId = null;
                $idFromForm = data_get($riesgosForm, "$i.id_riesgo");
                $idFromForm = ($idFromForm !== null && $idFromForm !== '') ? (int)$idFromForm : null;
                if ($idFromForm && isset($riesgoIdsValidos[$idFromForm])) {
                    $riesgoId = $idFromForm;
                } else {
                    $nombre   = data_get($riesgosForm, "$i.nombre") ?? $r->input("r{$i}_riesgo_nombre");
                    if ($nombre) $riesgoId = $riesgoMap[$normalize($nombre)] ?? null;
                }
                if (!$riesgoId) continue;

                $probId  = $r->input("r{$i}_id_probabilidad");
                $consId  = $r->input("r{$i}_id_consecuencia");
                $nivelId = $r->input("r{$i}_id_nivel_riesgo");

                $probId  = ($probId  !== null && $probId  !== '') ? (int)$probId  : null;
                $consId  = ($consId  !== null && $consId  !== '') ? (int)$consId  : null;
                $nivelId = ($nivelId !== null && $nivelId !== '') ? (int)$nivelId : null;

                if ($probId && $consId && $nivelId) {
                    DB::table('evaluacion_riesgo')->updateOrInsert(
                        [
                            'id_puesto_trabajo_matriz' => $idPuesto,
                            'id_riesgo'                => $riesgoId,
                        ],
                        [
                            'id_probabilidad' => $probId,
                            'id_consecuencia' => $consId,
                            'id_nivel_riesgo' => $nivelId,
                        ]
                    );
                }
            }

            // 3.X Reglas automáticas -> riesgo_valor (colocar antes de DB::commit())
            {
                // Normalizador simple para comparar
                $norm = function ($s) {
                    $t = strtoupper(trim((string)$s));
                    $t = str_replace(['/', '.', ' '], '', $t); // "n/a" => "NA"
                    return $t;
                };
                $esNA       = fn($s) => $norm($s) === 'NA';
                $esNinguno  = fn($s) => $norm($s) === 'NINGUNO';

                // ===== Regla 1: Esfuerzo físico -> riesgo 30
                $descFisico = [
                    $r->input('fisico_cargar_desc'),
                    $r->input('fisico_halar_desc'),
                    $r->input('fisico_empujar_desc'),
                    $r->input('fisico_sujetar_desc'),
                ];
                $hayEsfuerzoFisico = collect($descFisico)->contains(function ($v) use ($esNA) {
                    if ($v === null) return false;
                    $t = trim((string)$v);
                    if ($t === '') return false;
                    return !$esNA($t); // distinto de "N/A" (NA, N/A, n.a., etc.)
                });

                // ===== Regla 2: Visual -> riesgo 40 (si existe fila con tipo != "Ninguno")
                // $visual y $ruido ya vienen normalizados como Collections arriba
                $hayVisual = $visual->contains(function ($row) use ($esNinguno) {
                    $tipo = (string)($row['tipo'] ?? '');
                    if (trim($tipo) === '') return false;
                    return !$esNinguno($tipo);
                });

                // ===== Regla 3: Ruido -> riesgo 37 (si existe fila con desc != "Ninguno")
                $hayRuido = $ruido->contains(function ($row) use ($esNinguno) {
                    $desc = (string)($row['desc'] ?? '');
                    if (trim($desc) === '') return false;
                    return !$esNinguno($desc);
                });

                // Aplica SI/NO según condición actual (también en ediciones)
                $rules = [
                    30 => $hayEsfuerzoFisico, // esfuerzo físico
                    40 => $hayVisual,         // esfuerzo visual
                    37 => $hayRuido,          // ruido
                ];

                foreach ($rules as $riskId => $has) {
                    DB::table('riesgo_valor')->updateOrInsert(
                        [
                            'id_puesto_trabajo_matriz' => $idPuesto,
                            'id_riesgo'                => $riskId,
                        ],
                        [
                            'valor'         => $has ? 'SI' : 'NO',
                            'observaciones' => null,
                        ]
                    );
                }
            }

            DB::commit();
            return back()->with('success', 'Se guardó correctamente (Puesto #'.$idPuesto.')');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['save' => $e->getMessage()])->withInput();
        }
    }

public function fetch(int $id)
{
    // Puesto + nombre de departamento
    $puesto = DB::table('puesto_trabajo_matriz as p')
        ->leftJoin('departamento as d', 'p.id_departamento', '=', 'd.id_departamento')
        ->where('p.id_puesto_trabajo_matriz', $id)
        ->select('p.*', 'd.departamento')
        ->first();

    if (!$puesto) {
        return response()->json(['ok' => false, 'error' => 'Puesto no encontrado'], 404);
    }

    // Identificación (puede no existir si nunca se guardó)
    $ident = DB::table('identificacion_riesgos')
        ->where('id_puesto_trabajo_matriz', $id)
        ->first();

    // === NUEVO: listas dinámicas ===
    $visualRows = DB::table('ident_esfuerzo_visual')
        ->where('id_puesto_trabajo_matriz', $id)
        ->selectRaw('tipo_esfuerzo_visual as tipo, tiempo_exposicion as tiempo')
        ->get();

    $ruidoRows = DB::table('ident_exposicion_ruido')
        ->where('id_puesto_trabajo_matriz', $id)
        ->selectRaw('descripcion_ruido as "desc", duracion_exposicion as duracion')
        ->get();

    $termicoRows = DB::table('ident_estres_termico')
        ->where('id_puesto_trabajo_matriz', $id)
        ->selectRaw('descripcion_stress_termico as "desc", duracion_exposicion as duracion')
        ->get();

    // Químicos
    $quimicos = DB::table('quimico_puesto as qp')
        ->join('quimico as q', 'q.id_quimico', '=', 'qp.id_quimico')
        ->where('qp.id_puesto_trabajo_matriz', $id)
        ->selectRaw('qp.id_quimico, q.nombre_comercial as nombre, qp.duracion_exposicion as duracion, qp.frecuencia')
        ->get();

    // Riesgo_valor (SI/NO + obs)
    $riesgoValor = DB::table('riesgo_valor as rv')
        ->join('riesgo as r', 'r.id_riesgo', '=', 'rv.id_riesgo')
        ->where('rv.id_puesto_trabajo_matriz', $id)
        ->selectRaw('rv.id_riesgo, r.nombre_riesgo as nombre, rv.valor, rv.observaciones as obs')
        ->get()
        ->mapWithKeys(function ($row) {
            return [
                (int)$row->id_riesgo => [
                    'valor'  => $row->valor ? strtolower($row->valor) : null, // "si"|"no"|null
                    'obs'    => $row->obs,
                    'nombre' => $row->nombre,
                ]
            ];
        });

    // Evaluación de riesgo
    $eval = DB::table('evaluacion_riesgo as e')
        ->leftJoin('probabilidad as p', 'p.id_probabilidad', '=', 'e.id_probabilidad')
        ->leftJoin('consecuencia as c', 'c.id_consecuencia', '=', 'e.id_consecuencia')
        ->leftJoin('nivel_riesgo as n', 'n.id_nivel_riesgo', '=', 'e.id_nivel_riesgo')
        ->where('e.id_puesto_trabajo_matriz', $id)
        ->selectRaw('e.id_riesgo, e.id_probabilidad, COALESCE(p.probabilidad,"") as prob_label,
                     e.id_consecuencia, COALESCE(c.consecuencia,"") as cons_label,
                     e.id_nivel_riesgo, COALESCE(n.nivel_riesgo,"") as nivel_label')
        ->get()
        ->mapWithKeys(function ($row) {
            return [
                (int)$row->id_riesgo => [
                    'id_probabilidad' => (int)$row->id_probabilidad,
                    'prob_label'      => $row->prob_label,
                    'id_consecuencia' => (int)$row->id_consecuencia,
                    'cons_label'      => $row->cons_label,
                    'id_nivel_riesgo' => (int)$row->id_nivel_riesgo,
                    'nivel_label'     => $row->nivel_label,
                ]
            ];
        });

    $inv = function ($v) {
        if ($v === null) return '';
        if ((string)$v === '1') return 'si';
        if ((string)$v === '0') return 'no';
        if ((string)$v === '2') return 'N/A';
        return '';
    };

    $payload = [
        'ok'     => true,
        'puesto' => [
            'departamento'            => $puesto->departamento ?? '',
            'ptm_num_empleados'       => $puesto->num_empleados ?? null,
            'ptm_descripcion_general' => $puesto->descripcion_general ?? '',
            'ptm_actividades_diarias' => $puesto->actividades_diarias ?? '',
            'ptm_objetivo_puesto'     => $puesto->objetivo_puesto ?? '',
        ],

        // Ident maestro (para físico/alturas/ergonómico/firma/etc.)
        'ident'  => $ident ? [
            'fisico' => [
                'cargar'  => [
                    'desc'      => $ident->descripcion_carga_cargar ?? '',
                    'equipo'    => $ident->equipo_apoyo_cargar ?? '',
                    'duracion'  => $ident->duracion_carga_cargar ?? '',
                    'distancia' => $ident->distancia_carga_cargar ?? '',
                    'epp'       => $ident->epp_cargar ?? '',
                    'frecuencia'=> $ident->frecuencia_carga_cargar ?? '',
                    'peso'      => $ident->peso_cargar ?? '',
                ],
                'halar'   => [
                    'desc'      => $ident->descripcion_carga_halar ?? '',
                    'equipo'    => $ident->equipo_apoyo_halar ?? '',
                    'duracion'  => $ident->duracion_carga_halar ?? '',
                    'distancia' => $ident->distancia_carga_halar ?? '',
                    'epp'       => $ident->epp_halar ?? '',
                    'frecuencia'=> $ident->frecuencia_carga_halar ?? '',
                    'peso'      => $ident->peso_halar ?? '',
                ],
                'empujar' => [
                    'desc'      => $ident->descripcion_carga_empujar ?? '',
                    'equipo'    => $ident->equipo_apoyo_empujar ?? '',
                    'duracion'  => $ident->duracion_carga_empujar ?? '',
                    'distancia' => $ident->distancia_carga_empujar ?? '',
                    'epp'       => $ident->epp_empujar ?? '',
                    'frecuencia'=> $ident->frecuencia_carga_empujar ?? '',
                    'peso'      => $ident->peso_empujar ?? '',
                ],
                'sujetar' => [
                    'desc'      => $ident->descripcion_carga_sujetar ?? '',
                    'equipo'    => $ident->equipo_apoyo_sujetar ?? '',
                    'duracion'  => $ident->duracion_carga_sujetar ?? '',
                    'distancia' => $ident->distancia_carga_sujetar ?? '',
                    'epp'       => $ident->epp_sujetar ?? '',
                    'frecuencia'=> $ident->frecuencia_carga_sujetar ?? '',
                    'peso'      => $ident->peso_sujetar ?? '',
                ],
            ],
            // OJO: visual/ruido/térmico *en arrays* van abajo (visual_rows/ruido_rows/termico_rows)
            'instalaciones' => [
                ['estado'=>$ident->paredes_muros_losas_trabes ?? '', 'obs'=>$ident->paredes_muros_losas_trabes_obs ?? ''],
                ['estado'=>$ident->pisos ?? '', 'obs'=>$ident->pisos_obs ?? ''],
                ['estado'=>$ident->techos ?? '', 'obs'=>$ident->techos_obs ?? ''],
                ['estado'=>$ident->puertas_ventanas ?? '', 'obs'=>$ident->puertas_ventanas_obs ?? ''],
                ['estado'=>$ident->escaleras_rampas ?? '', 'obs'=>$ident->escaleras_rampas_obs ?? ''],
                ['estado'=>$ident->anaqueles_estanterias ?? '', 'obs'=>$ident->anaqueles_estanterias_obs ?? ''],
            ],
            'maq' => [
                ['estado'=>$ident->maquinaria_equipos ?? '', 'obs'=>$ident->maquinaria_equipos_obs ?? ''],
                ['estado'=>$ident->mantenimiento_preventivo ?? '', 'obs'=>$ident->mantenimiento_preventivo_obs ?? ''],
                ['estado'=>$ident->mantenimiento_correctivo ?? '', 'obs'=>$ident->mantenimiento_correctivo_obs ?? ''],
                ['estado'=>$ident->resguardos_guardas ?? '', 'obs'=>$ident->resguardos_guardas_obs ?? ''],
                ['estado'=>$ident->conexiones_electricas ?? '', 'obs'=>$ident->conexiones_electricas_obs ?? ''],
                ['estado'=>$ident->inspecciones_maquinaria ?? '', 'obs'=>$ident->inspecciones_maquinaria_obs ?? ''],
                ['estado'=>$ident->paros_emergencia ?? '', 'obs'=>$ident->paros_emergencia_obs ?? ''],
                ['estado'=>$ident->entrenamiento_maquinaria ?? '', 'obs'=>$ident->entrenamiento_maquinaria_obs ?? ''],
                ['estado'=>$ident->epp_correspondiente ?? '', 'obs'=>$ident->epp_correspondiente_obs ?? ''],
                ['estado'=>$ident->estado_herramientas ?? '', 'obs'=>$ident->estado_herramientas_obs ?? ''],
                ['estado'=>$ident->inspecciones_herramientas ?? '', 'obs'=>$ident->inspecciones_herramientas_obs ?? ''],
                ['estado'=>$ident->almacenamiento_herramientas ?? '', 'obs'=>$ident->almacenamiento_herramientas_obs ?? ''],
            ],
            'emer' => [
                ['estado'=>$ident->rutas_evacuacion ?? '', 'obs'=>$ident->rutas_evacuacion_obs ?? ''],
                ['estado'=>$ident->extintores_mangueras ?? '', 'obs'=>$ident->extintores_mangueras_obs ?? ''],
                ['estado'=>$ident->camillas ?? '', 'obs'=>$ident->camillas_obs ?? ''],
                ['estado'=>$ident->botiquin ?? '', 'obs'=>$ident->botiquin_obs ?? ''],
                ['estado'=>$ident->simulacros ?? '', 'obs'=>$ident->simulacros_obs ?? ''],
                ['estado'=>$ident->plan_evacuacion ?? '', 'obs'=>$ident->plan_evacuacion_obs ?? ''],
                ['estado'=>$ident->actuacion_emergencia ?? '', 'obs'=>$ident->actuacion_emergencia_obs ?? ''],
                ['estado'=>$ident->alarmas_emergencia ?? '', 'obs'=>$ident->alarmas_emergencia_obs ?? ''],
                ['estado'=>$ident->alarmas_humo ?? '', 'obs'=>$ident->alarmas_humo_obs ?? ''],
                ['estado'=>$ident->lamparas_emergencia ?? '', 'obs'=>$ident->lamparas_emergencia_obs ?? ''],
            ],
            'fuego' => [
                'inflamables_area'          => $inv($ident->sustancias_inflamables),
                'ventilacion_extraccion'    => $inv($ident->ventilacion_natural),
                'limpieza_regulares'        => $inv($ident->limpiezas_regulares),
                'senalizacion_riesgo'       => $inv($ident->senalizacion_de_riesgos ?? $ident->senalización_de_riesgos ?? null),
                'focos_ignicion'            => $inv($ident->fuentes_calor),
                'riesgo_electrico_friccion' => $inv($ident->maquinaria_friccion),
                'trasiego_combustibles'     => $inv($ident->trasiego_liquidos),
                'cilindros_alta_presion'    => $inv($ident->cilindros_presion),
                'derrames_combustibles'     => $inv($ident->derrames_sustancias),
            ],
            'ergo' => [
                ['resp'=>$ident->movimientos_repetitivos ?? null, 'obs'=>$ident->movimientos_repetitivos_obs ?? ''],
                ['resp'=>$ident->posturas_forzadas ?? null,       'obs'=>$ident->posturas_forzadas_obs ?? ''],
                ['resp'=>$ident->suficiente_espacio ?? null,      'obs'=>$ident->suficiente_espacio_obs ?? ''],
                ['resp'=>$ident->elevacion_brazos ?? null,        'obs'=>$ident->elevacion_brazos_obs ?? ''],
                ['resp'=>$ident->giros_muneca ?? null,            'obs'=>$ident->giros_muneca_obs ?? ''],
                ['resp'=>$ident->inclinacion_espalda ?? null,     'obs'=>$ident->inclinacion_espalda_obs ?? ''],
                ['resp'=>$ident->herramienta_constante ?? null,   'obs'=>$ident->herramienta_constante_obs ?? ''],
                ['resp'=>$ident->herramienta_vibracion ?? null,   'obs'=>$ident->herramienta_vibracion_obs ?? ''],
            ],
            'posturas' => [
                'agachado'        => (int)($ident->agachado ?? 0) === 1,
                'de_rodillas'     => (int)($ident->rodillas ?? 0) === 1,
                'volteado'        => (int)($ident->volteado ?? 0) === 1,
                'parado'          => (int)($ident->parado ?? 0) === 1,
                'sentado'         => (int)($ident->sentado ?? 0) === 1,
                'arrastrandose'   => (int)($ident->arrastrandose ?? 0) === 1,
                'subiendo'        => (int)($ident->subiendo ?? 0) === 1,
                'balanceandose'   => (int)($ident->balanceandose ?? 0) === 1,
                'corriendo'       => (int)($ident->corriendo ?? 0) === 1,
                'empujando'       => (int)($ident->empujando ?? 0) === 1,
                'halando'         => (int)($ident->halando ?? 0) === 1,
                'girando'         => (int)($ident->girando ?? 0) === 1,
            ],
            'alturas' => [
                'altura'               => $ident->altura ?? '',
                'anclaje_seguro'       => $ident->medios_anclaje ?? '',
                'aviso_trabajo_altura' => $ident->aviso_altura ?? '',
                'firma_trabajo_seguro' => $ident->hoja_trabajo ?? '',
            ],
            'elec_select' => [
                'elec_senalizacion'        => $ident->senalizacion_delimitacion ?? '',
                'elec_capacitacion'        => $ident->capacitacion_certificacion ?? '',
                'elec_alta_tension'        => $ident->alta_tension ?? '',
                'elec_hoja_trabajo_seguro' => $ident->trabajo_seguro_electrico ?? '',
                'elec_estatica'            => $ident->zonas_estatica ?? '',
                'elec_ausencia_tension'    => $ident->ausencia_tension ?? '',
                'elec_bloqueo'             => $ident->bloqueo_tarjetas ?? '',
                'elec_aviso'               => $ident->aviso_trabajo_electrico ?? '',
                'elec_observaciones'       => $ident->observaciones_electrico ?? '',
            ],
            'elec_chk' => [
                'cables_ordenados'         => $this->normalizeTriValue($ident->cables_ordenados ?? null),
                'toma_lejos_humedad'       => $this->normalizeTriValue($ident->tomacorrientes ?? null),
                'cajas_rotuladas_cerradas' => $this->normalizeTriValue($ident->cajas_interruptores ?? null),
                'buenas_condiciones'       => $this->normalizeTriValue($ident->extensiones ?? null),
                'aislamiento_ok'           => $this->normalizeTriValue($ident->cables_aislamiento ?? null),
                'senalizacion_riesgo'      => $this->normalizeTriValue($ident->senalizacion_riesgo_electrico ?? null),
            ],
            'caida' => [
                'pisos_adecuados'             => $this->normalizeTriValue($ident->pisos_adecuado ?? null),
                'vias_libres'                 => $this->normalizeTriValue($ident->vias_libres ?? null),
                'rampas_identificadas'        => $this->normalizeTriValue($ident->rampas_identificados ?? null),
                'gradas_barandas'             => $this->normalizeTriValue($ident->gradas_barandas ?? null),
                'antideslizantes'             => $this->normalizeTriValue($ident->sistemas_antideslizante ?? null),
                'senalizacion_piso_resbaloso' => $this->normalizeTriValue($ident->prevencion_piso_resbaloso ?? null),
                'obs'                         => $ident->observaciones_caida_nivel ?? '',
            ],
            'firmas' => [
                'eval_realizada_por'      => $ident->evaluacion_realizada ?? '',
                'eval_realizada_fecha'    => $ident->fecha_evaluacion_realizada ?? null,
                'eval_revisada_por'       => $ident->evaluacion_revisada ?? '',
                'eval_revisada_fecha'     => $ident->fecha_evaluacion_revisada ?? null,
                'fecha_proxima_evaluacion'=> $ident->fecha_proxima_evaluaci ?? null,
            ],
        ] : null,

        // === DEVUELVE TAMBIÉN LAS LISTAS DINÁMICAS ===
        'visual_rows'  => $visualRows,
        'ruido_rows'   => $ruidoRows,
        'termico_rows' => $termicoRows,

        'quimicos'          => $quimicos,
        'riesgo_valor'      => $riesgoValor,
        'evaluacion_riesgo' => $eval,
    ];

    return response()->json($payload);
}

}
