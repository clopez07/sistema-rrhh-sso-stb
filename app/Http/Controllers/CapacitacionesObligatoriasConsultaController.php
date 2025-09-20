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

    $empleados           = collect();
    $capsObligatorias    = collect();
    $matriz              = [];
    $recibidasLista      = collect();
    $pendientesLista     = collect();
    $matrizPorEmpleado   = [];
    $resumenPorEmpleado  = [];
    $empleadosCount      = 0;
    $autoEmpleado        = false;
    $ptmNombre           = null;
    $resumenGeneral      = [
        'ok'        => 0,
        'pendiente' => 0,
        'total'     => 0,
    ];
    $estadoPorCap        = [];

    $matchPtmIdByNombre = function (?string $nombre) {
        if (!is_string($nombre)) {
            return null;
        }
        $needle = mb_strtolower(trim($nombre));
        if ($needle === '') {
            return null;
        }

        return DB::table('puesto_trabajo_matriz')
            ->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(TRIM(puesto_trabajo_matriz)) = ?', [$needle])
                  ->orWhereRaw('LOWER(puesto_trabajo_matriz) LIKE ?', [$needle . '%'])
                  ->orWhereRaw('LOWER(puesto_trabajo_matriz) LIKE ?', ['%' . $needle . '%']);
            })
            ->orderBy('puesto_trabajo_matriz')
            ->value('id_puesto_trabajo_matriz');
    };

    // Autoseleccion de PTM priorizando los datos del empleado/puesto
    $autoPtm = false;
    if (!$ptmId && $puestoId) {
        $ptmIdFromEmpleado = DB::table('empleado')
            ->where('id_puesto_trabajo', $puestoId)
            ->whereNotNull('id_puesto_trabajo_matriz')
            ->value('id_puesto_trabajo_matriz');

        if ($ptmIdFromEmpleado) {
            $ptmId = (int) $ptmIdFromEmpleado;
            $autoPtm = true;
        } else {
            $puestoNombre = DB::table('puesto_trabajo')
                ->where('id_puesto_trabajo', $puestoId)
                ->value('puesto_trabajo');
            $match = $matchPtmIdByNombre($puestoNombre);
            if ($match) {
                $ptmId = (int) $match;
                $autoPtm = true;
            }
        }
    }

    if (!$ptmId && $empleadoId) {
        $empleadoDatos = DB::table('empleado')
            ->select('id_puesto_trabajo_matriz', 'id_puesto_trabajo')
            ->where('id_empleado', $empleadoId)
            ->first();

        if ($empleadoDatos) {
            if (!empty($empleadoDatos->id_puesto_trabajo_matriz)) {
                $ptmId = (int) $empleadoDatos->id_puesto_trabajo_matriz;
                $autoPtm = true;
            } elseif (!empty($empleadoDatos->id_puesto_trabajo)) {
                $puestoNombre = DB::table('puesto_trabajo')
                    ->where('id_puesto_trabajo', $empleadoDatos->id_puesto_trabajo)
                    ->value('puesto_trabajo');
                $match = $matchPtmIdByNombre($puestoNombre);
                if ($match) {
                    $ptmId = (int) $match;
                    $autoPtm = true;
                }
            }
        }
    }

    if ($ptmId) {
        $ptmNombre = DB::table('puesto_trabajo_matriz')
            ->where('id_puesto_trabajo_matriz', $ptmId)
            ->value('puesto_trabajo_matriz');

        $empleados = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select('e.id_empleado','e.nombre_completo','e.codigo_empleado','e.identidad','e.id_puesto_trabajo','e.id_puesto_trabajo_matriz')
            ->where(function ($q) use ($ptmId, $ptmNombre) {
                $q->where('e.id_puesto_trabajo_matriz', $ptmId);

                if ($ptmNombre) {
                    $needle = mb_strtolower(trim($ptmNombre));
                    if ($needle !== '') {
                        $q->orWhere(function ($inner) use ($needle) {
                            $inner->whereNull('e.id_puesto_trabajo_matriz')
                                  ->where(function ($byName) use ($needle) {
                                      $byName->whereRaw('LOWER(TRIM(pt.puesto_trabajo)) = ?', [$needle])
                                             ->orWhereRaw('LOWER(pt.puesto_trabajo) LIKE ?', [$needle . '%'])
                                             ->orWhereRaw('LOWER(pt.puesto_trabajo) LIKE ?', ['%' . $needle . '%']);
                                  });
                        });
                    }
                }
            })
            ->where(function ($q) {
                $q->where('e.estado', 1)->orWhereNull('e.estado');
            })
            ->orderBy('e.nombre_completo')
            ->get();

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

        foreach ($capsObligatorias as $cap) {
            $cid = (int) $cap->id_capacitacion;
            if (!isset($estadoPorCap[$cid])) {
                $estadoPorCap[$cid] = [
                    'cap'        => $cap,
                    'recibidas' => [],
                    'pendientes'=> [],
                ];
            }
        }

        if ($empleadoId && $capsObligatorias->count()) {
            $rows = DB::table('asistencia_capacitacion as ac')
                ->join('capacitacion_instructor as ci', 'ci.id_capacitacion_instructor', '=', 'ac.id_capacitacion_instructor')
                ->where('ac.id_empleado', $empleadoId)
                ->get(['ci.id_capacitacion','ac.fecha_recibida']);

            $recibidas = [];
            foreach ($rows as $r) {
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

                $idc = $r->id_capacitacion;
                if (!isset($recibidas[$idc]) || $candidata > $recibidas[$idc]) {
                    $recibidas[$idc] = $candidata;
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

        if ($capsObligatorias->count() && $empleadosCount > 0) {
            $idsEmp = $empleados->pluck('id_empleado')->all();
            $rowsAll = DB::table('asistencia_capacitacion as ac')
                ->join('capacitacion_instructor as ci', 'ci.id_capacitacion_instructor', '=', 'ac.id_capacitacion_instructor')
                ->whereIn('ac.id_empleado', $idsEmp)
                ->get(['ac.id_empleado','ci.id_capacitacion','ac.fecha_recibida']);

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
                if (!isset($recibidasPorEmp[$eid])) {
                    $recibidasPorEmp[$eid] = [];
                }
                if (!isset($recibidasPorEmp[$eid][$cid]) || $candidata > $recibidasPorEmp[$eid][$cid]) {
                    $recibidasPorEmp[$eid][$cid] = $candidata;
                }
            }

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

                    $cid = (int) $cap->id_capacitacion;
                    if (!isset($estadoPorCap[$cid])) {
                        $estadoPorCap[$cid] = [
                            'cap'        => $cap,
                            'recibidas' => [],
                            'pendientes'=> [],
                        ];
                    }
                    $registroEmpleado = [
                        'empleado' => $emp,
                        'fecha'    => $fecha,
                    ];

                    if ($fecha) {
                        $ok++;
                        $estadoPorCap[$cid]['recibidas'][] = $registroEmpleado;
                    } else {
                        $pend++;
                        $estadoPorCap[$cid]['pendientes'][] = $registroEmpleado;
                    }
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
                $resumenGeneral['ok'] += $ok;
                $resumenGeneral['pendiente'] += $pend;
                $resumenGeneral['total'] += ($ok + $pend);
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
        'resumenGeneral'   => $resumenGeneral,
        'estadoPorCap'     => $estadoPorCap,
        'ptmNombre'        => $ptmNombre,
        'recibidasLista'   => $recibidasLista,
        'pendientesLista'  => $pendientesLista,
        'debugCounts'      => [
            'empleados'    => $empleadosCount,
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

