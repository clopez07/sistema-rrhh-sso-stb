<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapacitacionesRequeridasController extends Controller
{
    /** Normaliza textos para empatar por nombre cuando falta el id matriz */
    private function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') return '';
        $value = mb_strtolower($value, 'UTF-8');
        return preg_replace('/\s+/u', ' ', $value);
    }

    public function index(Request $request)
    {
        $buscarPuesto = trim((string)$request->input('puesto', ''));
        $buscarCap    = trim((string)$request->input('cap', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        /* ============================================================
         * 0) Traer TODOS los puestos matriz (con departamento) para:
         *    - obtener nombres/deptos
         *    - mapear por nombre cuando falte el id matriz en empleado
         * ============================================================ */
        $allPtm = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento')
            ->get();

        if ($allPtm->isEmpty()) {
            return view('capacitaciones.matriz_requeridos', [
                'years' => $years, 'anio' => $anio, 'buscarPuesto' => $buscarPuesto, 'buscarCap' => $buscarCap,
                'puestos' => collect(), 'caps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        // Índices para mapeo por id y por nombre normalizado
        $allPtmById   = [];
        $nameToMatrix = [];
        foreach ($allPtm as $row) {
            $rowId = (int)$row->id_puesto_trabajo_matriz;
            $allPtmById[$rowId] = $row;
            $nameToMatrix[$this->normalize($row->puesto_trabajo_matriz)] = $rowId;
        }

        /* ============================================================
         * 1) EMPLEADOS ACTIVOS → definir a qué puesto MATRIZ pertenecen
         *    prioridad: id_puesto_trabajo_matriz
         *    fallback : id_puesto_trabajo -> nombre -> id_matriz
         * ============================================================ */
        $empleadosRaw = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                'e.id_empleado','e.nombre_completo','e.codigo_empleado','e.identidad',
                'e.id_puesto_trabajo','e.id_puesto_trabajo_matriz',
                'pt.puesto_trabajo as legacy_nombre'
            )
            ->where(function ($q) { $q->where('e.estado', 1)->orWhereNull('e.estado'); })
            ->get();

        // Mapeo por id_puesto_trabajo -> id_puesto_trabajo_matriz (si logramos deducirlo por nombre)
        $legacyIdToMatrix = [];

        // Conteo de empleados por puesto matriz (solo puestos con personal aparecerán)
        $empleadosPorMatrix = [];

        foreach ($empleadosRaw as $emp) {
            $matrixId = $emp->id_puesto_trabajo_matriz ? (int)$emp->id_puesto_trabajo_matriz : null;

            // fallback: a partir del id de puesto legacy, si ya lo dedujimos antes
            if (!$matrixId && $emp->id_puesto_trabajo && isset($legacyIdToMatrix[$emp->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$emp->id_puesto_trabajo];
            }
            // fallback: por nombre de puesto legacy → nombre de puesto matriz
            if (!$matrixId && $emp->legacy_nombre) {
                $norm = $this->normalize($emp->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($emp->id_puesto_trabajo) {
                        $legacyIdToMatrix[$emp->id_puesto_trabajo] = $matrixId;
                    }
                }
            }

            if ($matrixId && isset($allPtmById[$matrixId])) {
                $empleadosPorMatrix[$matrixId] = ($empleadosPorMatrix[$matrixId] ?? 0) + 1;
            }
            // Si no logramos mapear a un puesto matriz, se ignora para la matriz (no hay obligatorias sin id_matriz).
        }

        // Filtrar puestos a mostrar: SOLO los que tienen empleados
        $matrixIdsUsed = array_keys($empleadosPorMatrix);
        if (!empty($buscarPuesto)) {
            $needle = $this->normalize($buscarPuesto);
            $matrixIdsUsed = array_values(array_filter($matrixIdsUsed, function($id) use ($allPtmById, $needle) {
                return str_contains($this->normalize($allPtmById[$id]->puesto_trabajo_matriz ?? ''), $needle);
            }));
        }

        // Construir la colección $puestos final (ordenada por nombre)
        $puestos = collect($matrixIdsUsed)
            ->map(fn($id) => $allPtmById[$id])
            ->sort(function ($a, $b) {
                return strnatcasecmp($a->puesto_trabajo_matriz, $b->puesto_trabajo_matriz);
            })
            ->values();

        if ($puestos->isEmpty()) {
            return view('capacitaciones.matriz_requeridos', [
                'years' => $years, 'anio' => $anio, 'buscarPuesto' => $buscarPuesto, 'buscarCap' => $buscarCap,
                'puestos' => collect(), 'caps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        // rowById/totEmpleados para lo que realmente se mostrará
        $rowById      = [];
        $totEmpleados = [];
        foreach ($puestos as $p) {
            $rid = (int)$p->id_puesto_trabajo_matriz;
            $rowById[$rid]      = $p;
            $totEmpleados[$rid] = (int)($empleadosPorMatrix[$rid] ?? 0);
        }

        /* ============================================================
         * 2) COLUMNAS: Capacitaciones obligatorias por puesto matriz
         * ============================================================ */
        $pcRows = DB::table('puestos_capacitacion as pc')
            ->join('capacitacion as c', 'c.id_capacitacion', '=', 'pc.id_capacitacion')
            ->whereIn('pc.id_puesto_trabajo_matriz', array_keys($rowById))
            ->when($buscarCap !== '', function ($q) use ($buscarCap) {
                $needle = $this->normalize($buscarCap);
                $q->whereRaw('LOWER(c.capacitacion) LIKE ?', ["%{$needle}%"]);
            })
            ->get(['pc.id_puesto_trabajo_matriz','c.id_capacitacion','c.capacitacion']);

        $capsDict = [];   // id_capacitacion => obj
        $isOblig  = [];   // [puesto_matriz][cap_id] = true
        foreach ($pcRows as $r) {
            $pid = (int)$r->id_puesto_trabajo_matriz;
            if (!isset($rowById[$pid])) continue; // seguridad
            $cid = (int)$r->id_capacitacion;
            $isOblig[$pid][$cid] = true;
            if (!isset($capsDict[$cid])) {
                $capsDict[$cid] = (object)[
                    'id_capacitacion' => $cid,
                    'capacitacion'    => $r->capacitacion,
                ];
            }
        }

        if (empty($capsDict)) {
            return view('capacitaciones.matriz_requeridos', [
                'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
                'puestos'=>$puestos,'caps'=>collect(),'pivot'=>[],'totales'=>[],
                'totEmpleados'=>$totEmpleados,'deptOrder'=>[],'deptPuestos'=>[],'deptEmp'=>[],
                'deptPivot'=>[],'deptTotals'=>[],'deptGrand'=>['req'=>0,'ent'=>0,'pend'=>0],'rowById'=>$rowById,
            ]);
        }

        $caps = collect(array_values($capsDict))
            ->sort(fn($a,$b)=>strnatcasecmp($a->capacitacion,$b->capacitacion))
            ->values();
        $capIds = $caps->pluck('id_capacitacion')->all();


        // ========== PRE-PROCESAMIENTO DE FECHAS CON MESES EN ESPAÑOL ========== //
        $meses = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'setiembre' => '09', 'octubre' => '10',
            'noviembre' => '11', 'diciembre' => '12'
        ];
        $acRows = DB::table('asistencia_capacitacion')->get();
        $fechasParseadas = [];
        foreach ($acRows as $ac) {
            $fecha = mb_strtolower(trim($ac->fecha_recibida ?? ''), 'UTF-8');
            // Reemplaza meses en español por número
            foreach ($meses as $mesTxt => $mesNum) {
                $fecha = preg_replace('/\b'.$mesTxt.'\b/u', $mesNum, $fecha);
            }
            // Elimina palabras extra
            $fecha = preg_replace('/\bde\b/u', '', $fecha);
            $fecha = preg_replace('/[a\.m\.|p\.m\.|a\.m|p\.m]/u', '', $fecha);
            $fecha = preg_replace('/\s+/', ' ', $fecha);
            $fecha = trim($fecha);
            $fechasParseadas[$ac->id_empleado.'_'.$ac->id_capacitacion_instructor] = $fecha;
        }

        // Subquery con fechas parseadas
        $sub = DB::table('asistencia_capacitacion as ac')
            ->select('ac.id_empleado','ac.id_capacitacion_instructor')
            ->selectRaw('CAST(ac.fecha_recibida AS DATE) as date_cast')
            ->selectRaw('ac.fecha_recibida as fecha_str');

        // Query principal usando PHP para filtrar por año
        $recibidasRows = [];
        foreach ($sub->get() as $row) {
            $key = $row->id_empleado.'_'.$row->id_capacitacion_instructor;
            $fecha = $fechasParseadas[$key] ?? $row->fecha_str;
            $anioFecha = null;
            // Intenta extraer año
            if (preg_match('/(\d{4})/', $fecha, $m)) {
                $anioFecha = (int)$m[1];
            }
            if ($anioFecha === $anio) {
                $recibidasRows[] = $row;
            }
        }

        // Agrupa por puesto/capacitacion
        $recibidasAgrupadas = [];
        foreach ($recibidasRows as $r) {
            // Prioridad: id_puesto_trabajo_matriz, si no, mapear por legacy
            $emp = DB::table('empleado')->where('id_empleado', $r->id_empleado)->first();
            $matrixId = null;
            if ($emp && $emp->id_puesto_trabajo_matriz) {
                $matrixId = (int)$emp->id_puesto_trabajo_matriz;
            } elseif ($emp && $emp->id_puesto_trabajo && isset($legacyIdToMatrix[$emp->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$emp->id_puesto_trabajo];
            } elseif ($emp && property_exists($emp, 'legacy_nombre') && $emp->legacy_nombre) {
                $norm = $this->normalize($emp->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($emp->id_puesto_trabajo) $legacyIdToMatrix[$emp->id_puesto_trabajo] = $matrixId;
                }
            }
            if (!$matrixId || !isset($rowById[$matrixId])) continue;

            $capId = DB::table('capacitacion_instructor')->where('id_capacitacion_instructor', $r->id_capacitacion_instructor)->value('id_capacitacion');
            if (!$capId) continue;
            if (!isset($recibidasAgrupadas[$matrixId])) $recibidasAgrupadas[$matrixId] = [];
            if (!isset($recibidasAgrupadas[$matrixId][$capId])) $recibidasAgrupadas[$matrixId][$capId] = [];
            $recibidasAgrupadas[$matrixId][$capId][$r->id_empleado] = true;
        }

        // Índice: [puesto_matriz][id_capacitacion] => recibidas (empleados distintos)
        $idxEnt = [];
        foreach ($recibidasAgrupadas as $matrixId => $capArr) {
            foreach ($capArr as $capId => $empArr) {
                $idxEnt[$matrixId][$capId] = count($empArr);
            }
        }

        // Índice: [puesto_matriz][id_capacitacion] => recibidas (empleados distintos)
        $idxEnt = [];
        foreach ($recibidasRows as $r) {
            // Prioridad: id_puesto_trabajo_matriz, si no, mapear por legacy
            $matrixId = null;
            if (property_exists($r, 'id_puesto_trabajo_matriz') && $r->id_puesto_trabajo_matriz) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif (property_exists($r, 'id_puesto_trabajo') && $r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
            } elseif (property_exists($r, 'legacy_nombre') && $r->legacy_nombre) {
                $norm = $this->normalize($r->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($r->id_puesto_trabajo) $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                }
            }
            if (!$matrixId || !isset($rowById[$matrixId])) continue;

            $capId = (property_exists($r, 'id_capacitacion')) ? (int)$r->id_capacitacion : null;
            if ($capId !== null && property_exists($r, 'cnt')) {
                $idxEnt[$matrixId][$capId] = (int)$r->cnt;
            }
        }

        /* ============================================================
         * 4) PIVOT por puesto; TOTALES por capacitación
         * ============================================================ */
        $pivot   = [];
        $totales = [];
        foreach ($caps as $c) $totales[$c->id_capacitacion] = ['req'=>0,'ent'=>0,'pend'=>0];

        foreach ($puestos as $p) {
            $rowId = (int)$p->id_puesto_trabajo_matriz;
            $row = [];
            $reqPuesto = $totEmpleados[$rowId] ?? 0;

            foreach ($caps as $c) {
                if (empty($isOblig[$rowId][$c->id_capacitacion])) {
                    $row[$c->id_capacitacion] = null; // no obligatorio → celda en blanco
                    continue;
                }
                $ent  = (int)($idxEnt[$rowId][$c->id_capacitacion] ?? 0);
                $req  = $reqPuesto;
                $pend = max(0, $req - $ent);

                $row[$c->id_capacitacion] = ['req'=>$req,'ent'=>$ent,'pend'=>$pend];

                $totales[$c->id_capacitacion]['req']  += $req;
                $totales[$c->id_capacitacion]['ent']  += $ent;
                $totales[$c->id_capacitacion]['pend'] += $pend;
            }

            $pivot[$rowId] = $row;
        }

        /* ============================================================
         * 5) AGRUPACIÓN por DEPARTAMENTO (subtotales y resumen)
         * ============================================================ */
        $deptPuestos = [];   // dept => [['id'=>rowId, 'row'=>obj], ...]
        $deptEmp     = [];   // dept => total empleados
        $deptPivot   = [];   // dept => [cap_id => ['req','ent','pend']]

        foreach ($puestos as $p) {
            $dep   = $p->departamento ?: 'Sin departamento';
            $rowId = (int)$p->id_puesto_trabajo_matriz;

            $deptPuestos[$dep] = $deptPuestos[$dep] ?? [];
            $deptPuestos[$dep][] = ['id' => $rowId, 'row' => $p];

            $deptEmp[$dep] = ($deptEmp[$dep] ?? 0) + ($totEmpleados[$rowId] ?? 0);

            foreach ($caps as $c) {
                $cell = $pivot[$rowId][$c->id_capacitacion] ?? null;
                if (is_null($cell)) continue;
                if (!isset($deptPivot[$dep][$c->id_capacitacion])) {
                    $deptPivot[$dep][$c->id_capacitacion] = ['req'=>0,'ent'=>0,'pend'=>0];
                }
                $deptPivot[$dep][$c->id_capacitacion]['req']  += $cell['req'];
                $deptPivot[$dep][$c->id_capacitacion]['ent']  += $cell['ent'];
                $deptPivot[$dep][$c->id_capacitacion]['pend'] += $cell['pend'];
            }
        }

        $deptOrder = array_keys($deptPuestos);
        sort($deptOrder, SORT_NATURAL | SORT_FLAG_CASE);

        $deptTotals = [];
        foreach ($deptOrder as $dep) {
            $deptTotals[$dep] = ['req'=>0,'ent'=>0,'pend'=>0];
            if (!empty($deptPivot[$dep])) {
                foreach ($deptPivot[$dep] as $vals) {
                    $deptTotals[$dep]['req']  += $vals['req'];
                    $deptTotals[$dep]['ent']  += $vals['ent'];
                    $deptTotals[$dep]['pend'] += $vals['pend'];
                }
            }
        }

        $deptGrand = ['req'=>0,'ent'=>0,'pend'=>0];
        foreach ($deptTotals as $t) {
            $deptGrand['req']  += $t['req'];
            $deptGrand['ent']  += $t['ent'];
            $deptGrand['pend'] += $t['pend'];
        }

        return view('capacitaciones.matriz_requeridos', [
            'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
            'puestos'=>$puestos,'caps'=>$caps,'pivot'=>$pivot,'totales'=>$totales,'totEmpleados'=>$totEmpleados,
            'deptOrder'=>$deptOrder,'deptPuestos'=>$deptPuestos,'deptEmp'=>$deptEmp,
            'deptPivot'=>$deptPivot,'deptTotals'=>$deptTotals,'deptGrand'=>$deptGrand,'rowById'=>$allPtmById,
        ]);
    }
}
