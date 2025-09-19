<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IdentificacionRiesgosExportController extends Controller
{

    public function export(Request $request)
    {
        $request->validate([
            'ptm_id' => 'required']
        );
        $ptmId = $request->input('ptm_id');

        // Encabezado del puesto desde puesto_trabajo_matriz + departamento
        $pt = DB::table('puesto_trabajo_matriz as pt')
        ->leftJoin('departamento as d', 'd.id_departamento', '=', 'pt.id_departamento')
        ->leftJoin('identificacion_riesgos as ir', 'ir.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
        ->leftJoin('localizacion as lo', 'lo.id_localizacion', '=', 'pt.id_localizacion')
        // Si puede haber varias filas por localización en estandar_iluminacion, usa un subselect 1:1
        //->leftJoin(DB::raw('(SELECT id_localizacion, MAX(em) AS em 
                           // FROM estandar_iluminacion 
                           // GROUP BY id_localizacion) ei'),
                //'ei.id_localizacion', '=', 'lo.id_localizacion')
        ->where('pt.id_puesto_trabajo_matriz', $ptmId)
        ->orderByDesc('ir.id_identificacion_riesgos')   // último registro de identificación
        ->select(
            'pt.puesto_trabajo_matriz as puesto',
            'pt.num_empleados',
            'd.departamento',
            'pt.descripcion_general',
            'pt.actividades_diarias',
            //'lo.localizacion',
            'ei.em as lux_estandar',   // <-- estándar de iluminación
            'ir.*'                     // todo lo de identificacion_riesgos (niveles de medición, tiempos, etc.)
        )
        ->first();

        if (!$pt) {
            return back()->with('error', 'No se encontró el puesto en "puesto_trabajo_matriz".');
        }

        $header = [
            'departamento' => $pt->departamento ?? '',
            'puesto'       => $pt->puesto ?? '',
            'empleados'    => $pt->num_empleados ?? '',
            'descripcion'    => $pt->descripcion_general ?? '',
            'actividades'    => $pt->actividades_diarias ?? '',
            'descargar'    => $pt->descripcion_carga_cargar ?? '',
            'deshalar'    => $pt->descripcion_carga_halar ?? '',
            'desempujar'    => $pt->descripcion_carga_empujar ?? '',
            'dessujetar'    => $pt->descripcion_carga_sujetar ?? '',
            'equicargar'    => $pt->equipo_apoyo_cargar ?? '',
            'equihalar'    => $pt->equipo_apoyo_halar ?? '',
            'equiempujar'    => $pt->equipo_apoyo_empujar ?? '',
            'equisujetar'    => $pt->equipo_apoyo_sujetar ?? '',
            'ducargar'    => $pt->duracion_carga_cargar ?? '',
            'duhalar'    => $pt->duracion_carga_halar ?? '',
            'duempujar'    => $pt->duracion_carga_empujar ?? '',
            'dusujetar'    => $pt->duracion_carga_sujetar ?? '',
            'dicargar'    => $pt->distancia_carga_cargar ?? '',
            'dihalar'    => $pt->distancia_carga_halar ?? '',
            'diempujar'    => $pt->distancia_carga_empujar ?? '',
            'disujetar'    => $pt->distancia_carga_sujetar ?? '',
            'eppcargar'    => $pt->epp_cargar ?? '',
            'epphalar'    => $pt->epp_halar ?? '',
            'eppempujar'    => $pt->epp_empujar ?? '',
            'eppsujetar'    => $pt->epp_sujetar ?? '',
            'frecargar'    => $pt->frecuencia_carga_cargar ?? '',
            'frehalar'    => $pt->frecuencia_carga_halar ?? '',
            'freempujar'    => $pt->frecuencia_carga_empujar ?? '',
            'fresujetar'    => $pt->frecuencia_carga_sujetar ?? '',
            'pesocargar'    => $pt->peso_cargar ?? '',
            'pesohalar'    => $pt->peso_halar ?? '',
            'pesoempujar'    => $pt->peso_empujar ?? '',
            'pesosujetar'    => $pt->peso_sujetar ?? '',
        ];

        // Abrir plantilla de Identificación de Riesgos
        $tplPath = storage_path('app/public/formato_identificacion_riesgos.xlsx');
        if (!is_file($tplPath)) {
            return back()->with('error', 'No se encontró la plantilla formato_identificacion_riesgos.xlsx');
        }
        try {
            $spreadsheet = IOFactory::load($tplPath);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo abrir la plantilla de Excel: '.$e->getMessage());
        }
        $sheet = $spreadsheet->getActiveSheet();

        // Escribir encabezados solicitados
        // E8 = departamento, E9 = puesto, E10 = número de empleados
        $sheet->setCellValue('E8',  (string)($header['departamento'] ?? ''));
        $sheet->setCellValue('E9',  (string)($header['puesto'] ?? ''));
        $sheet->setCellValue('E10', (string)($header['empleados'] ?? ''));
        $sheet->setCellValue('E11',  (string)($header['descripcion'] ?? ''));
        $sheet->setCellValue('A13', (string)($header['actividades'] ?? ''));
        $sheet->setCellValue('B16', (string)($header['descargar'] ?? ''));
        $sheet->setCellValue('B17', (string)($header['deshalar'] ?? ''));
        $sheet->setCellValue('B18', (string)($header['desempujar'] ?? ''));
        $sheet->setCellValue('B19', (string)($header['dessujetar'] ?? ''));
        $sheet->setCellValue('D16', (string)($header['equicargar'] ?? ''));
        $sheet->setCellValue('D17', (string)($header['equihalar'] ?? ''));
        $sheet->setCellValue('D18', (string)($header['equiempujar'] ?? ''));
        $sheet->setCellValue('D19', (string)($header['equisujetar'] ?? ''));
        $sheet->setCellValue('F16', (string)($header['ducargar'] ?? ''));
        $sheet->setCellValue('F17', (string)($header['duhalar'] ?? ''));
        $sheet->setCellValue('F18', (string)($header['duempujar'] ?? ''));
        $sheet->setCellValue('F19', (string)($header['dusujetar'] ?? ''));
        $sheet->setCellValue('G16', (string)($header['dicargar'] ?? ''));
        $sheet->setCellValue('G17', (string)($header['dihalar'] ?? ''));
        $sheet->setCellValue('G18', (string)($header['diempujar'] ?? ''));
        $sheet->setCellValue('G19', (string)($header['disujetar'] ?? ''));
        $sheet->setCellValue('I16', (string)($header['eppcargar'] ?? ''));
        $sheet->setCellValue('I17', (string)($header['epphalar'] ?? ''));
        $sheet->setCellValue('I18', (string)($header['eppempujar'] ?? ''));
        $sheet->setCellValue('I19', (string)($header['eppsujetar'] ?? ''));
        $sheet->setCellValue('H16', (string)($header['frecargar'] ?? ''));
        $sheet->setCellValue('H17', (string)($header['frehalar'] ?? ''));
        $sheet->setCellValue('H18', (string)($header['freempujar'] ?? ''));
        $sheet->setCellValue('H19', (string)($header['fresujetar'] ?? ''));
        $sheet->setCellValue('K16', (string)($header['pesocargar'] ?? ''));
        $sheet->setCellValue('K17', (string)($header['pesohalar'] ?? ''));
        $sheet->setCellValue('K18', (string)($header['pesoempujar'] ?? ''));
        $sheet->setCellValue('K19', (string)($header['pesosujetar'] ?? ''));
        
        // ESFUERZO VISUAL (fila 22)
        $sheet->setCellValue('A22', (string)($pt->tipo_esfuerzo_visual ?? ''));     // Tipo de esfuerzo
        $sheet->setCellValue('D22', (string)($pt->lux_estandar ?? ''));             // Nivel de iluminación estándar de área
        $sheet->setCellValue('G22', (string)($pt->nivel_mediciones_visual ?? ''));  // Nivel de lux medido en área
        $sheet->setCellValue('J22', (string)($pt->tiempo_exposicion_visual ?? '')); // Tiempo de exposición

        // EXPOSICIÓN A RUIDO (fila 25)
        $sheet->setCellValue('A25', (string)($pt->descripcion_ruido ?? ''));
        $sheet->setCellValue('D25', (string)($pt->nivel_mediciones_ruido ?? ''));
        $sheet->setCellValue('F25', (string)($pt->tiempo_exposicion_ruido ?? ''));
        $sheet->setCellValue('H25', ''); // EPP usado (no existe campo; déjalo vacío o agrega uno)

        // EXPOSICIÓN STRESS TÉRMICO (fila 28)
        $sheet->setCellValue('A28', (string)($pt->descripcion_temperatura ?? ''));
        $sheet->setCellValue('D28', (string)($pt->nivel_mediciones_temperatura ?? ''));
        $sheet->setCellValue('F28', (string)($pt->tiempo_exposicion_temperatura ?? ''));
        $sheet->setCellValue('H28', ''); // EPP usado (igual que arriba)

        // ====== CONDICIONES DE INSTALACIONES ======
        $obsCol = 'H'; // <--- cambia a I/J si tu plantilla lo tiene en otra columna

        // Mapa fila -> (valor enum, observación)
        $instRows = [
            34 => ['val' => $pt->paredes_muros_losas_trabes ?? null, 'obs' => $pt->paredes_muros_losas_trabes_obs ?? ''],
            35 => ['val' => $pt->pisos ?? null,                          'obs' => $pt->pisos_obs ?? ''],
            36 => ['val' => $pt->techos ?? null,                         'obs' => $pt->techos_obs ?? ''],
            37 => ['val' => $pt->puertas_ventanas ?? null,               'obs' => $pt->puertas_ventanas_obs ?? ''],
            38 => ['val' => $pt->escaleras_rampas ?? null,               'obs' => $pt->escaleras_rampas_obs ?? ''],
            39 => ['val' => $pt->anaqueles_estanterias ?? null,          'obs' => $pt->anaqueles_estanterias_obs ?? ''],
        ];

        // A -> Adecuado (E), NA -> No adecuado (F), N/A -> N/A (G)
        $colByEnum = ['A' => 'E', 'NA' => 'F', 'N/A' => 'G'];

        foreach ($instRows as $row => $it) {
            $enum = is_string($it['val']) ? strtoupper($it['val']) : '';
            $col  = $colByEnum[$enum] ?? null;

            // limpia por si acaso (no es estrictamente necesario si la plantilla viene vacía)
            $sheet->setCellValue("E{$row}", '');
            $sheet->setCellValue("F{$row}", '');
            $sheet->setCellValue("G{$row}", '');

            if ($col) {
                $sheet->setCellValue("{$col}{$row}", 'X');
            }

            // Observaciones
            $sheet->setCellValue("{$obsCol}{$row}", (string)($it['obs'] ?? ''));
        }
        // ====== MAQUINARIA, EQUIPO Y HERRAMIENTAS ======
        // En tu plantilla: G = Adecuado, H = No adecuado, I = N/A, K = Observaciones
        $maqObsCol = 'J';
        $maqColByEnum = ['A' => 'G', 'NA' => 'H', 'N/A' => 'I'];

        // fila => [valor enum, observación]
        $maqRows = [
            46 => ['val' => $pt->maquinaria_equipos ?? null,          'obs' => $pt->maquinaria_equipos_obs ?? ''],
            47 => ['val' => $pt->mantenimiento_preventivo ?? null,     'obs' => $pt->mantenimiento_preventivo_obs ?? ''],
            48 => ['val' => $pt->mantenimiento_correctivo ?? null,     'obs' => $pt->mantenimiento_correctivo_obs ?? ''],
            49 => ['val' => $pt->resguardos_guardas ?? null,           'obs' => $pt->resguardos_guardas_obs ?? ''],
            50 => ['val' => $pt->conexiones_electricas ?? null,        'obs' => $pt->conexiones_electricas_obs ?? ''],
            51 => ['val' => $pt->inspecciones_maquinaria ?? null,      'obs' => $pt->inspecciones_maquinaria_obs ?? ''],
            52 => ['val' => $pt->paros_emergencia ?? null,             'obs' => $pt->paros_emergencia_obs ?? ''],
            53 => ['val' => $pt->entrenamiento_maquinaria ?? null,     'obs' => $pt->entrenamiento_maquinaria_obs ?? ''],
            54 => ['val' => $pt->epp_correspondiente ?? null,          'obs' => $pt->epp_correspondiente_obs ?? ''],
            55 => ['val' => $pt->estado_herramientas ?? null,          'obs' => $pt->estado_herramientas_obs ?? ''],
            56 => ['val' => $pt->inspecciones_herramientas ?? null,    'obs' => $pt->inspecciones_herramientas_obs ?? ''],
            57 => ['val' => $pt->almacenamiento_herramientas ?? null,  'obs' => $pt->almacenamiento_herramientas_obs ?? ''],
        ];

        foreach ($maqRows as $row => $it) {
            $enum = is_string($it['val']) ? strtoupper($it['val']) : '';
            $col  = $maqColByEnum[$enum] ?? null;

            // limpia por si acaso
            $sheet->setCellValue("G{$row}", '');
            $sheet->setCellValue("H{$row}", '');
            $sheet->setCellValue("I{$row}", '');

            if ($col) {
                $sheet->setCellValue("{$col}{$row}", 'X');
            }

            $sheet->setCellValue("{$maqObsCol}{$row}", (string)($it['obs'] ?? ''));
        }
        // ====== EQUIPOS Y SERVICIOS DE EMERGENCIA ======
        $emerObsCol   = 'J';
        $emerColByEnum = ['A' => 'G', 'NA' => 'H', 'N/A' => 'I'];

        // fila => [valor enum, observación]
        $emerRows = [
            60 => ['val' => $pt->rutas_evacuacion ?? null,         'obs' => $pt->rutas_evacuacion_obs ?? ''],
            61 => ['val' => $pt->extintores_mangueras ?? null,      'obs' => $pt->extintores_mangueras_obs ?? ''],
            62 => ['val' => $pt->camillas ?? null,                  'obs' => $pt->camillas_obs ?? ''],
            63 => ['val' => $pt->botiquin ?? null,                  'obs' => $pt->botiquin_obs ?? ''],
            64 => ['val' => $pt->simulacros ?? null,                'obs' => $pt->simulacros_obs ?? ''],
            65 => ['val' => $pt->plan_evacuacion ?? null,           'obs' => $pt->plan_evacuacion_obs ?? ''],
            66 => ['val' => $pt->actuacion_emergencia ?? null,      'obs' => $pt->actuacion_emergencia_obs ?? ''],
            67 => ['val' => $pt->alarmas_emergencia ?? null,        'obs' => $pt->alarmas_emergencia_obs ?? ''],
            68 => ['val' => $pt->alarmas_humo ?? null,              'obs' => $pt->alarmas_humo_obs ?? ''],
            69 => ['val' => $pt->lamparas_emergencia ?? null,       'obs' => $pt->lamparas_emergencia_obs ?? ''],
        ];

        foreach ($emerRows as $row => $it) {
            $enum = is_string($it['val']) ? strtoupper($it['val']) : '';
            $col  = $emerColByEnum[$enum] ?? null;

            // Limpia por si ya hubiera marcas
            $sheet->setCellValue("F{$row}", '');
            $sheet->setCellValue("G{$row}", '');
            $sheet->setCellValue("I{$row}", '');

            if ($col) {
                $sheet->setCellValue("{$col}{$row}", 'X');
            }
            $sheet->setCellValue("{$emerObsCol}{$row}", (string)($it['obs'] ?? ''));
        }
        // ====== RIESGO DE FUEGO O EXPLOSIÓN ======
        // Filas 71–73, columnas B/G/K como áreas de captura bajo cada encabezado
        $senalRiesgos = data_get($pt, 'senalización_de_riesgos', data_get($pt, 'señalización_de_riesgos'));

        // Fila 71
        $sheet->setCellValue('D71', (string)($pt->sustancias_inflamables ?? '')); // Hay sustancias inflamables/combustible/material explosivo
        $sheet->setCellValue('H71', (string)($pt->ventilacion_natural ?? ''));    // Ventilación natural o extracción suficiente
        $sheet->setCellValue('L71', (string)($pt->limpiezas_regulares ?? ''));    // Se realizan limpiezas regulares

        // Fila 72
        $sheet->setCellValue('D72', (string)($senalRiesgos ?? ''));               // Señalización de riesgo inflamable/explosión
        $sheet->setCellValue('H72', (string)($pt->fuentes_calor ?? ''));           // Fuentes de calor/focos de ignición cercanos
        $sheet->setCellValue('L72', (string)($pt->maquinaria_friccion ?? ''));     // Maquinaria/equipo con riesgo eléctrico o fricción

        // Fila 73
        $sheet->setCellValue('D73', (string)($pt->trasiego_liquidos ?? ''));       // Trasiego de líquidos combustibles/inflamables
        $sheet->setCellValue('H73', (string)($pt->cilindros_presion ?? ''));       // Cilindros de alta presión y su resguardo
        $sheet->setCellValue('L73', (string)($pt->derrames_sustancias ?? ''));     // Derrames de sustancias combustibles/inflamables

        // ====== POSICIONES / MOVIMIENTOS ERGONÓMICOS ======
        $markErgo = function ($value, $row) use ($sheet) {
            $v = strtoupper((string)$value);
            $sheet->setCellValue("F{$row}", ($v === 'SI') ? 'X' : '');
            $sheet->setCellValue("G{$row}", ($v === 'NO') ? 'X' : '');
            $sheet->setCellValue("H{$row}", ($v === 'NA' || $v === 'N/A') ? 'X' : '');
        };

        $ergonomico = [
            76 => ['movimientos_repetitivos', 'movimientos_repetitivos_obs'],
            77 => ['posturas_forzadas',       'posturas_forzadas_obs'],
            78 => ['suficiente_espacio',      'suficiente_espacio_obs'],
            79 => ['elevacion_brazos',        'elevacion_brazos_obs'],
            80 => ['giros_muneca',            'giros_muneca_obs'],
            81 => ['inclinacion_espalda',     'inclinacion_espalda_obs'],
            82 => ['herramienta_constante',   'herramienta_constante_obs'],
            83 => ['herramienta_vibracion',   'herramienta_vibracion_obs'],
        ];

        foreach ($ergonomico as $row => [$campo, $campoObs]) {
            $markErgo($pt->{$campo} ?? null, $row);
            $sheet->setCellValue("I{$row}", (string)($pt->{$campoObs} ?? ''));
        }

        // ===== POSTURAS =====
        // Fila 85
        $sheet->setCellValue('B85', !empty($pt->agachado)       ? 'X' : '');
        $sheet->setCellValue('D85', !empty($pt->rodillas)       ? 'X' : '');
        $sheet->setCellValue('F85', !empty($pt->volteado)       ? 'X' : '');
        $sheet->setCellValue('H85', !empty($pt->parado)         ? 'X' : '');
        $sheet->setCellValue('J85', !empty($pt->sentado)        ? 'X' : '');
        $sheet->setCellValue('L85', !empty($pt->arrastrandose)  ? 'X' : '');
        // Fila 86
        $sheet->setCellValue('B86', !empty($pt->subiendo)       ? 'X' : '');
        $sheet->setCellValue('D86', !empty($pt->balanceandose)  ? 'X' : '');
        $sheet->setCellValue('F86', !empty($pt->corriendo)      ? 'X' : '');
        $sheet->setCellValue('H86', !empty($pt->empujando)      ? 'X' : '');
        $sheet->setCellValue('J86', !empty($pt->halando)        ? 'X' : '');
        $sheet->setCellValue('L86', !empty($pt->girando)        ? 'X' : '');

        // ===== TRABAJO EN ALTURAS =====
        $sheet->setCellValue('B95', (string)($pt->altura ?? ''));
        $sheet->setCellValue('F95', (string)($pt->medios_anclaje ?? ''));

        $sheet->setCellValue('B96', (string)($pt->epp_correspondiente ?? ''));      // A / NA / N/A
        $sheet->setCellValue('F96', (string)($pt->epp_correspondiente_obs ?? ''));   // "EPP utilizado" (texto libre si lo tienes)

        $sheet->setCellValue('B97', (string)($pt->senalizacion_delimitacion ?? ''));
        $sheet->setCellValue('F97', (string)($pt->capacitacion_certificacion ?? ''));

        $sheet->setCellValue('B98', (string)($pt->aviso_altura ?? ''));
        $sheet->setCellValue('F98', (string)($pt->hoja_trabajo ?? ''));

        /* =========================
        TRABAJO CON ELECTRICIDAD / RIESGO ELÉCTRICO
        ========================= */

        // Filas 100–102 (texto libre)
        $sheet->setCellValue('D100', (string)($pt->senalizacion_delimitacion ?? '')); // Señalización y delimitación
        $sheet->setCellValue('H100', (string)($pt->capacitacion_certificacion ?? '')); // Capacitación / certificación
        $sheet->setCellValue('L100', (string)($pt->alta_tension ?? ''));              // Hay alta tensión…

        $sheet->setCellValue('D101', (string)($pt->hoja_trabajo ?? ''));              // Firma Hoja de Trabajo Seguro
        $sheet->setCellValue('H101', (string)($pt->epp_correspondiente_obs ?? ''));   // EPP utilizado (texto)
        $sheet->setCellValue('L101', (string)($pt->zonas_estatica ?? ''));            // Zonas de electricidad estática

        $sheet->setCellValue('D102', (string)($pt->bloqueo_tarjetas ?? ''));          // Sistema de bloqueo con tarjeta/candado
        $sheet->setCellValue('H102', (string)($pt->aviso_trabajo_electrico ?? ''));   // Aviso de trabajo eléctrico
        $sheet->setCellValue('L102', (string)($pt->ausencia_tension ?? ''));          // Verificación de ausencia de tensión

        // Fila 104 (checks)
        $sheet->setCellValue('B104', !empty($pt->cables_ordenados)           ? 'X' : '');
        $sheet->setCellValue('D104', !empty($pt->tomacorrientes)             ? 'X' : '');
        $sheet->setCellValue('F104', !empty($pt->cajas_interruptores)        ? 'X' : '');
        $sheet->setCellValue('H104', !empty($pt->extensiones)                ? 'X' : '');
        $sheet->setCellValue('J104', !empty($pt->cables_aislamiento)         ? 'X' : '');
        $sheet->setCellValue('L104', !empty($pt->senalizacion_riesgo_electrico) ? 'X' : '');

        // Fila 105 (observaciones)
        $sheet->setCellValue('B105', (string)($pt->observaciones_electrico ?? ''));   // Observaciones eléctricas


        /* =========================
        RIESGO DE CAÍDA MISMO NIVEL
        ========================= */
        // Fila 108 (checks)
        $sheet->setCellValue('B108', !empty($pt->pisos_adecuado)            ? 'X' : '');
        $sheet->setCellValue('D108', !empty($pt->vias_libres)               ? 'X' : '');
        $sheet->setCellValue('F108', !empty($pt->rampas_identificados)      ? 'X' : '');
        $sheet->setCellValue('H108', !empty($pt->gradas_barandas)           ? 'X' : '');
        $sheet->setCellValue('J108', !empty($pt->sistemas_antideslizante)   ? 'X' : '');
        $sheet->setCellValue('L108', !empty($pt->prevencion_piso_resbaloso) ? 'X' : '');

        // Fila 109 (observaciones)
        $sheet->setCellValue('B109', (string)($pt->observaciones_caida_nivel ?? ''));

        /* ------------------------------------------------------------------
        TABLA: Identificación de Riesgo (marca SI/NO/N/A por fila)
        Compara automáticamente el texto de la columna PELIGRO (col. C)
        con riesgo.nombre_riesgo de riesgo_valor.
        -------------------------------------------------------------------*/

        // 1) Traer riesgos del puesto
        $riesgos = DB::table('riesgo_valor as rv')
            ->join('riesgo as r', 'r.id_riesgo', '=', 'rv.id_riesgo')
            ->where('rv.id_puesto_trabajo_matriz', $ptmId)
            ->select('r.nombre_riesgo as nombre', 'rv.valor', 'rv.observaciones')
            ->get();

        // 2) Normalizar para comparar (mayúsculas, sin acentos, sin dobles espacios)
        $norm = function (?string $s) {
            $s = (string) $s;
            $s = trim($s);
            if ($s === '') return '';
            $s = mb_strtoupper($s, 'UTF-8');
            $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N']);
            $s = preg_replace('/[^A-Z0-9\/\s]/u', '', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return $s;
        };

        // 3) Agregar por nombre (si hay varios del mismo nombre, SI > NO > N/A)
        $agg = [];
        foreach ($riesgos as $r) {
            $k = $norm($r->nombre);
            if (!isset($agg[$k])) $agg[$k] = ['valor' => null, 'obs' => []];

            $v = mb_strtoupper(trim((string)$r->valor), 'UTF-8'); // SI/NO/N/A (u otros)
            if ($v === 'SI' || $agg[$k]['valor'] === null) {
                $agg[$k]['valor'] = $v;
            } elseif ($v === 'NO' && $agg[$k]['valor'] !== 'SI') {
                $agg[$k]['valor'] = 'NO';
            } elseif (($v === 'N/A' || $v === 'NA') && $agg[$k]['valor'] === null) {
                $agg[$k]['valor'] = 'N/A';
            }
            if (!empty($r->observaciones)) $agg[$k]['obs'][] = (string)$r->observaciones;
        }

        // 4) Buscador flexible: exacto o “contiene” en ambos sentidos
        $find = function (string $label) use ($norm, $agg) {
            $L = $norm($label);
            if ($L === '') return null;

            if (isset($agg[$L])) return $agg[$L]; // exacto

            foreach ($agg as $k => $dat) {        // contiene / contenido
                if (str_contains($k, $L) || str_contains($L, $k)) return $dat;
            }
            return null;
        };

        // 5) Recorrer filas de la tabla (PELIGRO en col. C, marcar SI(H)/NO(I)/N/A(J), Observaciones en K)
        // Ajusta el rango según tu plantilla; aquí cubro MECÁNICO+ELÉCTRICO (117–127)
        $bloques = [
            ['desde' => 118, 'hasta' => 165],
            // si tu plantilla tiene más filas de esta tabla, añade más bloques aquí:
            // ['desde' => 128, 'hasta' => 140],
        ];

        foreach ($bloques as $b) {
            for ($row = $b['desde']; $row <= $b['hasta']; $row++) {
                $peligro = (string)$sheet->getCell("B{$row}")->getValue(); // texto en PELIGRO
                if (trim($peligro) === '') continue;

                $m = $find($peligro);
                if (!$m) continue; // no hay coincidencia en BD

                $val = $m['valor'];
                if ($val === 'SI') {
                    $sheet->setCellValue("H{$row}", 'X');
                } elseif ($val === 'NO') {
                    $sheet->setCellValue("I{$row}", 'X');
                } 
                if (!empty($m['obs'])) {
                    $sheet->setCellValue("J{$row}", implode('; ', array_unique($m['obs'])));
                }
            }
        }

        // Descargar
        $filename = 'identificacion_riesgos_'.$ptmId.'_'.date('Ymd_His').'.xlsx';
        try {
            $tmp = storage_path('app/'.uniqid('identificacion_', true).'.xlsx');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tmp);
            if (!is_file($tmp) || filesize($tmp) < 500) {
                $writer->save($tmp);
            }
            while (ob_get_level() > 0) { @ob_end_clean(); }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo generar el archivo de Excel: '.$e->getMessage());
        }

        return response()->download($tmp, $filename, [
            'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'                    => 'no-cache',
            'Content-Transfer-Encoding' => 'binary',
        ])->deleteFileAfterSend(true);
    }
}

