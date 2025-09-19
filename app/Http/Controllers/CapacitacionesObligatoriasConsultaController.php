<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CapacitacionesObligatoriasConsultaController extends Controller
{
public function index(Request $request)
{
    $ptmId      = $request->integer('ptm');
    $empleadoId = $request->integer('empleado');
    $puestoId   = $request->integer('puesto') ?? $request->integer('id_puesto');
    $anio       = $request->integer('anio') ?: intval(date('Y'));

    $ptms  = DB::table('puesto_trabajo_matriz')
                ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz')
                ->orderBy('puesto_trabajo_matriz')->get();
    $years = range(intval(date('Y')), intval(date('Y')) - 10);

    $puestosEquivalentes = [];
    $empleados           = collect();
    $capsObligatorias    = collect();
    $matriz              = [];
    $recibidasLista      = collect();
    $pendientesLista     = collect();
    $matrizPorEmpleado   = [];
    $resumenPorEmpleado  = [];
    $empleadosCount      = 0;
    $autoEmpleado        = false;

    // Autoselección de PTM por comparación si viene un puesto del sistema o un empleado
    $autoPtm = false;
    if (!$ptmId && $puestoId) {
        $cands = DB::table('comparacion_puestos')
            ->where('id_puesto_trabajo', $puestoId)
            ->distinct()
            ->pluck('id_puesto_trabajo_matriz');
        if ($cands->count() === 1) {
            $ptmId = (int)$cands->first();
            $autoPtm = true;
        }
    }

    if (!$ptmId && $empleadoId) {
        $puestoDelEmpleado = DB::table('empleado')->where('id_empleado', $empleadoId)->value('id_puesto_trabajo');
        if ($puestoDelEmpleado) {
            $cands = DB::table('comparacion_puestos')
                ->where('id_puesto_trabajo', $puestoDelEmpleado)
                ->distinct()
                ->pluck('id_puesto_trabajo_matriz');
            if ($cands->count() === 1) {
                $ptmId = (int)$cands->first();
                $autoPtm = true;
            }
        }
    }

    if ($ptmId) {
        $puestosEquivalentes = DB::table('comparacion_puestos')
            ->where('id_puesto_trabajo_matriz', $ptmId)
            ->pluck('id_puesto_trabajo')
            ->all();

        if (empty($puestosEquivalentes)) {
            $ptmNombre = DB::table('puesto_trabajo_matriz')
                ->where('id_puesto_trabajo_matriz', $ptmId)
                ->value('puesto_trabajo_matriz');

            if ($ptmNombre) {
                $needle = mb_strtolower(trim($ptmNombre));

                $puestosMatch = DB::table('puesto_trabajo')
                    ->where(function ($q) use ($needle) {
                        $q->whereRaw('LOWER(TRIM(puesto_trabajo)) = ?', [$needle])
                          ->orWhereRaw('LOWER(puesto_trabajo) LIKE ?', [$needle . '%'])
                          ->orWhereRaw('LOWER(puesto_trabajo) LIKE ?', ['%' . $needle . '%']);
                    })
                    ->pluck('id_puesto_trabajo')
                    ->all();

                if (!empty($puestosMatch)) {
                    $puestosEquivalentes = $puestosMatch;
                }
            }
        }

        if (!empty($puestosEquivalentes)) {
            $empleados = DB::table('empleado')
                ->select('id_empleado','nombre_completo','codigo_empleado','identidad','id_puesto_trabajo')
                ->whereIn('id_puesto_trabajo', $puestosEquivalentes)
                ->where(function ($q) {
                    $q->where('estado', 1)->orWhereNull('estado');
                })
                ->orderBy('nombre_completo')
                ->get();
        }

        $empleadosCount = $empleados->count();
        if ($empleadosCount === 1 && !$empleadoId) {
            $empleadoId   = $empleados->first()->id_empleado;
            $autoEmpleado = true;
        }

        $capsObligatorias = DB::table('puestos_capacitacion as pc')
            ->join('capacitacion as c', 'c.id_capacitacion', '=', 'pc.id_capacitacion')
            ->where('pc.id_puesto_trabajo_matriz', $ptmId)
            ->distinct()
            ->orderBy('c.capacitacion')
            ->get(['c.id_capacitacion','c.capacitacion']);

        if ($empleadoId && $capsObligatorias->count()) {
            // Traemos todas las asistencias en crudo y parseamos fechas varchar en PHP
            $rows = DB::table('asistencia_capacitacion as ac')
                ->join('capacitacion_instructor as ci', 'ci.id_capacitacion_instructor', '=', 'ac.id_capacitacion_instructor')
                ->where('ac.id_empleado', $empleadoId)
                ->get(['ci.id_capacitacion','ac.fecha_recibida']);

            // Por cada capacitación tomamos la fecha más reciente que caiga en el año solicitado
            $recibidas = [];
            foreach ($rows as $r) {
                [$ini, $fin] = $this->parseFechaVarcharToDates($r->fecha_recibida);
                // Regla: usar fin si existe; de lo contrario, inicio
                $candidata = $fin ?: $ini;
                if (!$candidata) { continue; }

                $yCand = (int)substr($candidata, 0, 4);
                if ($yCand !== (int)$anio) {
                    // Si fin no es del año, probar con inicio
                    if ($ini && (int)substr($ini, 0, 4) === (int)$anio) {
                        $candidata = $ini;
                    } else {
                        continue;
                    }
                }

                $idc = $r->id_capacitacion;
                if (!isset($recibidas[$idc]) || $candidata > $recibidas[$idc]) {
                    $recibidas[$idc] = $candidata; // formato 'Y-m-d'
                }
            }

            foreach ($capsObligatorias as $cap) {
                $fecha  = $recibidas[$cap->id_capacitacion] ?? null;
                $estado = $fecha ? 'OK' : 'PENDIENTE';

                $fila = [
                    'cap'    => $cap,
                    'estado' => $estado,
                    'fecha'  => $fecha,
                ];
                $matriz[] = $fila;

                if ($fecha) {
                    $recibidasLista->push($fila);
                } else {
                    $pendientesLista->push($fila);
                }
            }
        }

        // Bloque: calcular matriz para TODOS los empleados si hay varios
        if ($capsObligatorias->count() && $empleadosCount > 0) {
            $idsEmp = $empleados->pluck('id_empleado')->all();
            // Traer todas las asistencias de todos los empleados del grupo
            $rowsAll = DB::table('asistencia_capacitacion as ac')
                ->join('capacitacion_instructor as ci', 'ci.id_capacitacion_instructor', '=', 'ac.id_capacitacion_instructor')
                ->whereIn('ac.id_empleado', $idsEmp)
                ->get(['ac.id_empleado','ci.id_capacitacion','ac.fecha_recibida']);

            // Mapear por empleado y capacitación su última fecha válida del año
            $recibidasPorEmp = [];
            foreach ($rowsAll as $r) {
                [$ini, $fin] = $this->parseFechaVarcharToDates($r->fecha_recibida);
                $candidata = $fin ?: $ini;
                if (!$candidata) { continue; }

                $yCand = (int)substr($candidata, 0, 4);
                if ($yCand !== (int)$anio) {
                    if ($ini && (int)substr($ini, 0, 4) === (int)$anio) {
                        $candidata = $ini;
                    } else {
                        continue;
                    }
                }

                $eid = (int)$r->id_empleado;
                $cid = (int)$r->id_capacitacion;
                if (!isset($recibidasPorEmp[$eid])) $recibidasPorEmp[$eid] = [];
                if (!isset($recibidasPorEmp[$eid][$cid]) || $candidata > $recibidasPorEmp[$eid][$cid]) {
                    $recibidasPorEmp[$eid][$cid] = $candidata;
                }
            }

            // Armar matriz y resumen por empleado
            foreach ($empleados as $emp) {
                $eid = (int)$emp->id_empleado;
                $filas = [];
                $ok = 0; $pend = 0;
                foreach ($capsObligatorias as $cap) {
                    $cid   = (int)$cap->id_capacitacion;
                    $fecha = $recibidasPorEmp[$eid][$cid] ?? null;
                    $estado = $fecha ? 'OK' : 'PENDIENTE';
                    $filas[] = [
                        'cap'    => $cap,
                        'estado' => $estado,
                        'fecha'  => $fecha,
                    ];
                    if ($fecha) $ok++; else $pend++;
                }
                $matrizPorEmpleado[$eid] = [
                    'empleado' => $emp,
                    'filas'    => $filas,
                ];
                $resumenPorEmpleado[$eid] = [
                    'ok'        => $ok,
                    'pendiente' => $pend,
                    'total'     => $ok + $pend,
                ];
            }
        }
    }

    return view('capacitaciones.capacitaciones_obligatorias', [
        'ptms'             => $ptms,
        'years'            => $years,
        'ptmId'            => $ptmId,
        'empleadoId'       => $empleadoId,
        'autoPtm'          => $autoPtm,
        'anio'             => $anio,
        'empleados'        => $empleados,
        'empleadosCount'   => $empleadosCount,
        'autoEmpleado'     => $autoEmpleado,
        'capsObligatorias' => $capsObligatorias,
        'matriz'           => $matriz,
        'matrizPorEmpleado'=> $matrizPorEmpleado,
        'resumenPorEmpleado'=> $resumenPorEmpleado,
        'recibidasLista'   => $recibidasLista,
        'pendientesLista'  => $pendientesLista,
        'debugCounts'      => [
            'equivalentes' => is_array($puestosEquivalentes) ? count($puestosEquivalentes) : 0,
            'obligatorias' => $capsObligatorias->count(),
        ],
    ]);
}

    /**
     * Parsea un varchar de fecha y retorna [inicio, fin] en formato 'Y-m-d'.
     * Acepta:
     *  - "03/07/2025" o "3-7-2025"
     *  - "Del 15/07/2025 al 18/07/2025" (cualquier texto, extrae las dos primeras fechas)
     */
    private function parseFechaVarcharToDates($s): array
    {
        $start = null; $end = null;
        if (!is_string($s) || trim($s) === '') return [null, null];
        $text = trim($s);

        // Extrae todas las fechas dd/mm/yyyy o dd-mm-yyyy del texto
        if (preg_match_all('/(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/u', $text, $all, PREG_SET_ORDER)) {
            $dates = [];
            foreach ($all as $m) {
                $d  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $y  = $m[3];
                try {
                    $dt = Carbon::createFromFormat('d/m/Y', "$d/$mo/$y");
                    if ($dt !== false) {
                        $dates[] = $dt->toDateString();
                    }
                } catch (\Throwable $e) { /* ignore */ }
            }
            if (count($dates) >= 1) $start = $dates[0];
            if (count($dates) >= 2) $end   = $dates[1];
        } else {
            // Último recurso: intentar parseo genérico
            try {
                $dt = Carbon::parse($text);
                $start = $dt->toDateString();
            } catch (\Throwable $e) { /* ignore */ }
        }

        return [$start, $end];
    }
}
