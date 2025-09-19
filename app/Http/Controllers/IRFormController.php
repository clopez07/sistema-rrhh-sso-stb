<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IRFormController extends Controller
{
    /**
     * Guarda el formulario "Identificación de Riesgo por Puesto de Trabajo"
     * en las tablas: tipo_riesgo, riesgo_valor, detalles_riesgo.
     *
     * Acepta el id del puesto por:
     *  - input hidden:  id_puesto_trabajo_matriz
     *  - o parámetro de ruta opcional $ptmId (si configuras la ruta así: /identificacion-riesgo/guardar/{ptm?})
     */
    public function create(Request $request, ?int $ptmId = null)
    {
        // Resolver ID del puesto (requerido por tu esquema)
        $ptm = $ptmId ?? (int) $request->input('id_puesto_trabajo_matriz');
        if (!$ptm) {
            throw ValidationException::withMessages([
                'id_puesto_trabajo_matriz' => 'Falta el ID del puesto de trabajo matriz.',
            ]);
        }

        DB::transaction(function () use ($request, $ptm) {

            /* ============================================================
             * 1) DATOS GENERALES  -> detalles_riesgo (JSON)
             * ============================================================ */
            $dg = [
                'Departamento' => $request->input('Departamento'),
                'Puesto de Trabajo Analizado' => $request->input('Puesto de Trabajo Analizado'),
                'N° de empleados por puesto de trabajo' => $request->input('N° de empleados por puesto de trabajo'),
                'Descripción General de la labor' => $request->input('Descripción General de la labor'),
                'ACTIVIDADES DIARIAS' => $request->input('ACTIVIDADES DIARIAS'),
            ];

            DB::table('detalles_riesgo')->insert([
                'detalles_riesgo'            => 'DATOS GENERALES DEL PUESTO',
                'id_puesto_trabajo_matriz'   => $ptm,
                'tipo_riesgo'                => 'DATOS GENERALES',
                'valor'                      => null,
                'observaciones'              => json_encode($dg, JSON_UNESCAPED_UNICODE),
            ]);

            /* ============================================================
             * 2) ESFUERZO FÍSICO -> detalles_riesgo (una fila por: Cargar, Halar, Empujar, Sujetar)
             * En tu HTML los names son:
             *   - ef_equipo[]  (APARECE DOS VECES por fila: col3 y col6)
             *   - ef_duracion[]  ef_frecuencia[]  ef_peso[]  ef_capacitacion[]
             *   - La columna "Descripción de Carga" NO tiene name (no llega al backend).
             * Reconstrucción: por fila i, equipo_apoyo = ef_equipo[2*i], epp_utilizado = ef_equipo[2*i+1]
             * ============================================================ */
            $tiposEsfuerzo = ['CARGAR', 'HALAR', 'EMPUJAR', 'SUJETAR'];

            $ef_equipo        = $request->input('ef_equipo', []);       // len = 8 (2 por cada una de las 4 filas)
            $ef_duracion      = $request->input('ef_duracion', []);     // len = 4
            $ef_frecuencia    = $request->input('ef_frecuencia', []);   // len = 4
            $ef_peso          = $request->input('ef_peso', []);         // len = 4
            $ef_capacitacion  = $request->input('ef_capacitacion', []); // len = 4

            for ($i = 0; $i < count($tiposEsfuerzo); $i++) {
                $equipo_apoyo = $ef_equipo[$i * 2]     ?? null; // col3
                $epp_utilizado= $ef_equipo[$i * 2 + 1] ?? null; // col6

                $payload = [
                    // 'descripcion_carga'   => null, // no llega al backend por falta de name en el HTML
                    'equipo_apoyo'        => $equipo_apoyo,
                    'duracion_distancia'  => $ef_duracion[$i]     ?? null,
                    'frecuencia'          => $ef_frecuencia[$i]   ?? null,
                    'epp_utilizado'       => $epp_utilizado,
                    'peso_aproximado'     => $ef_peso[$i]         ?? null,
                    'capacitacion'        => $ef_capacitacion[$i] ?? null,
                ];

                DB::table('detalles_riesgo')->insert([
                    'detalles_riesgo'            => $tiposEsfuerzo[$i],  // CARGAR/HALAR/EMPUJAR/SUJETAR
                    'id_puesto_trabajo_matriz'   => $ptm,
                    'tipo_riesgo'                => 'ESFUERZO FISICO',
                    'valor'                      => $ef_peso[$i] ?? null, // opcional: guardo el peso como valor
                    'observaciones'              => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);
            }

            /* ============================================================
             * 3) CHECKLISTS -> detalles_riesgo
             * En el HTML: instalaciones[i] / maquinaria[i] / emergencia[i] = "Adecuado|No adecuado|N/A"
             * Observaciones vienen como inputs name="obs" sin indexar -> no llegan de forma distinguible por fila.
             * Se guardan con observaciones = null para cada item.
             * ============================================================ */

            // 3.1 Condiciones de instalaciones
            $instItems = [
                'Paredes, muros, losas y trabes',
                'Pisos',
                'Techos',
                'Puertas y Ventanas',
                'Escaleras y rampas',
                'Anaqueles y estantería',
            ];
            $instVals = $request->input('instalaciones', []); // valores por índice

            foreach ($instItems as $i => $item) {
                $val = $instVals[$i] ?? null;
                if ($val !== null) {
                    DB::table('detalles_riesgo')->insert([
                        'detalles_riesgo'            => $item,
                        'id_puesto_trabajo_matriz'   => $ptm,
                        'tipo_riesgo'                => 'CONDICIONES DE INSTALACIONES',
                        'valor'                      => mb_strtoupper($val),
                        'observaciones'              => null, // no viene indexado
                    ]);
                }
            }

            // 3.2 Maquinaria, equipo y herramientas
            $maqItems = [
                'Estado de Maquinaria y Equipo',
                'Se ejecuta mantenimiento preventivo',
                'Se ejecuta mantenimiento correctivo',
                'Estado resguardos y guardas',
                'Estado de herramientas',
                'Se realizan inspecciones de Herramientas',
                'Almacenamiento Correcto de Herramientas',
            ];
            $maqVals = $request->input('maquinaria', []);

            foreach ($maqItems as $i => $item) {
                $val = $maqVals[$i] ?? null;
                if ($val !== null) {
                    DB::table('detalles_riesgo')->insert([
                        'detalles_riesgo'            => $item,
                        'id_puesto_trabajo_matriz'   => $ptm,
                        'tipo_riesgo'                => 'MAQUINARIA, EQUIPO Y HERRAMIENTAS',
                        'valor'                      => mb_strtoupper($val),
                        'observaciones'              => null,
                    ]);
                }
            }

            // 3.3 Equipos y servicios de emergencia
            $emItems = [
                'Señalización rutas de evacuación y salidas de emergencia y punto reunión',
                'Ubicación de Extintores o mangueras de incendios',
                'Ubicación de Camillas y elementos de primeros auxilios',
                'Ubicación de Botiquín',
                'Realización de Simulacros',
                'Socialización Plan de evacuación',
                'Capacitación sobre actuación en caso de emergencia y uso de extintor.',
                'Ubicación de alarmas aviso emergencia',
                'Ubicación de alarmas de humo',
                'Ubicación de lámparas de emergencia',
            ];
            $emVals = $request->input('emergencia', []);

            foreach ($emItems as $i => $item) {
                $val = $emVals[$i] ?? null;
                if ($val !== null) {
                    DB::table('detalles_riesgo')->insert([
                        'detalles_riesgo'            => $item,
                        'id_puesto_trabajo_matriz'   => $ptm,
                        'tipo_riesgo'                => 'EQUIPOS Y SERVICIOS DE EMERGENCIA',
                        'valor'                      => mb_strtoupper($val),
                        'observaciones'              => null,
                    ]);
                }
            }

            /* ============================================================
             * 4) TABLA DE IDENTIFICACIÓN DE RIESGO -> riesgo_valor
             * En el HTML: tir[TIPO][idx][aplica] = SI|NO
             * Se asegura la existencia en tipo_riesgo. Guardamos 'riesgo' como (idx + 1) para evitar 0.
             * ============================================================ */
            $tir = $request->input('tir', []); // ['MECANICO'=> [0=>['aplica'=>'SI'], ...], 'ELECTRICO'=> ... ]

            // cachear tipos existentes
            $tiposMap = DB::table('tipo_riesgo')->pluck('id_tipo_riesgo', 'tipo_riesgo'); // ['MECANICO'=>1, ...]

            foreach ($tir as $tipo => $filas) {
                $tipoKey = mb_strtoupper(trim($tipo)); // MECANICO, ELECTRICO, FUEGO Y EXPLOSION, QUIMICO

                // Asegurar existencia del tipo en cat. tipo_riesgo
                if (!isset($tiposMap[$tipoKey])) {
                    $tiposMap[$tipoKey] = DB::table('tipo_riesgo')->insertGetId(['tipo_riesgo' => $tipoKey]);
                }
                $idTipo = $tiposMap[$tipoKey];

                foreach ($filas as $idx => $data) {
                    $aplica = isset($data['aplica']) ? mb_strtoupper($data['aplica']) : null;
                    $riesgoN = ((int) $idx) + 1; // 1..N (evitar 0)

                    // Upsert por (puesto, tipo, riesgoN)
                    DB::table('riesgo_valor')->updateOrInsert(
                        [
                            'id_puesto_trabajo_matriz' => $ptm,
                            'id_tipo_riesgo'           => $idTipo,
                            'riesgo'                   => $riesgoN,
                        ],
                        [
                            'valor'         => $aplica,   // 'SI' | 'NO' | null
                            'observaciones' => null,      // el HTML no trae una obs indexada por fila
                        ]
                    );
                }
            }
        });

        return back()->with('ok', 'Formulario guardado correctamente.');
    }
}
