<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CapacitacionesRequeridasController extends Controller
{
    /** Normaliza: minúsculas, sin acentos, sin signos, colapsa espacios */
    private function normalize(?string $value): string
    {
        $v = trim((string) $value);
        if ($v === '') return '';
        $v = mb_strtolower($v, 'UTF-8');
        $x = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);
        if ($x !== false) $v = $x;
        $v = preg_replace('/[^a-z0-9 ]+/i', ' ', $v);
        $v = preg_replace('/\s+/u', ' ', $v);
        return trim($v);
    }

    /** Extrae año desde fecha_recibida (string o date) tolerante a "12 de marzo de 2025", etc. */
    private function extractYear($fecha): ?int
{
    if (empty($fecha)) return null;
    if ($fecha instanceof \DateTimeInterface) return (int)$fecha->format('Y');
    $s = trim((string)$fecha);
    if ($s === '') return null;

    $lower = mb_strtolower($s, 'UTF-8');
    $map = [
        'enero'=>'01','febrero'=>'02','marzo'=>'03','abril'=>'04','mayo'=>'05','junio'=>'06',
        'julio'=>'07','agosto'=>'08','septiembre'=>'09','setiembre'=>'09','octubre'=>'10','noviembre'=>'11','diciembre'=>'12'
    ];
    foreach ($map as $t=>$n) { $lower = preg_replace('/\b'.$t.'\b/u', $n, $lower); }
    $lower = preg_replace('/\bde\b/u', ' ', $lower);
    $lower = preg_replace('/a\.?m\.?|p\.?m\.?/iu', ' ', $lower);
    $lower = preg_replace('/\s+/u', ' ', $lower);

    if (preg_match('/\b(19|20)\d{2}\b/', $lower, $m)) return (int)$m[0];
    if (preg_match('/\b(19|20)\d{2}\b/', $s, $m2)) return (int)$m2[0];
    return null;
}

/** Intenta normalizar a Y-m-d para comparar rangos (12m). Si no puede, retorna null. */
private function normalizeToDate($fecha): ?string
{
    if (empty($fecha)) return null;
    if ($fecha instanceof \DateTimeInterface) return $fecha->format('Y-m-d');
    $s = trim((string)$fecha);
    if ($s === '') return null;

    $x = mb_strtolower($s, 'UTF-8');
    $map = [
        'enero'=>'01','febrero'=>'02','marzo'=>'03','abril'=>'04','mayo'=>'05','junio'=>'06',
        'julio'=>'07','agosto'=>'08','septiembre'=>'09','setiembre'=>'09','octubre'=>'10','noviembre'=>'11','diciembre'=>'12'
    ];
    foreach ($map as $t=>$n) { $x = preg_replace('/\b'.$t.'\b/u', $n, $x); }
    $x = preg_replace('/\bde\b/u', ' ', $x);
    $x = str_replace(['/','.'], '-', $x);
    $x = preg_replace('/[^\d\- ]+/', ' ', $x);
    $x = preg_replace('/\s+/', ' ', $x);

    // yyyy-mm-dd
    if (preg_match('/\b(19|20)\d{2}\b[^\d]{1,3}(\d{1,2})[^\d]{1,3}(\d{1,2})/', $x, $m)) {
        $Y=(int)$m[0]; $M=(int)$m[2]; $D=(int)$m[3];
        if (checkdate($M, $D, $Y)) return sprintf('%04d-%02d-%02d', $Y,$M,$D);
    }
    // dd-mm-yyyy
    if (preg_match('/(\d{1,2})[^\d]{1,3}(\d{1,2})[^\d]{1,3}\b((?:19|20)\d{2})\b/', $x, $m)) {
        $D=(int)$m[1]; $M=(int)$m[2]; $Y=(int)$m[3];
        if (checkdate($M, $D, $Y)) return sprintf('%04d-%02d-%02d', $Y,$M,$D);
    }
    // yyyy-mm (asume día 01)
    if (preg_match('/\b((?:19|20)\d{2})[^\d]{1,3}(\d{1,2})\b/', $x, $m)) {
        $Y=(int)$m[1]; $M=(int)$m[2]; if ($M>=1 && $M<=12) return sprintf('%04d-%02d-01', $Y,$M);
    }
    // fallback: yyyy-01-01
    $Y = $this->extractYear($s);
    if ($Y) return sprintf('%04d-01-01', $Y);
    return null;
}

    public function index(Request $request)
    {
        $buscarPuesto = trim((string)$request->input('puesto', ''));
        $buscarCap    = trim((string)$request->input('cap', ''));
        $anio         = (int)($request->input('anio') ?: date('Y'));

        $years = range((int)date('Y'), (int)date('Y') - 10);

        /* ============================================================
         * 0) Puestos matriz activos + depto (para nombres/mapeo)
         * ============================================================ */
        $allPtm = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento', 'ptm.estado')
            ->where(function ($q) { $q->where('ptm.estado', 1)->orWhereNull('ptm.estado'); })
            ->get();

        if ($allPtm->isEmpty()) {
            return view('capacitaciones.matriz_requeridos', [
                'years' => $years, 'anio' => $anio,
                'buscarPuesto' => $buscarPuesto, 'buscarCap' => $buscarCap,
                'puestos' => collect(), 'caps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        // Índices para mapeo
        $allPtmById   = [];
        $nameToMatrix = [];
        foreach ($allPtm as $row) {
            $id = (int)$row->id_puesto_trabajo_matriz;
            $allPtmById[$id] = $row;
            $nameToMatrix[$this->normalize($row->puesto_trabajo_matriz)] = $id;
        }

        /* ============================================================
         * 1) EMPLEADOS ACTIVOS → a qué puesto MATRIZ pertenecen
         *    prioridad: id_puesto_trabajo_matriz
         *    fallback : legacy por nombre
         * ============================================================ */
        $empleadosRaw = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                'e.id_empleado','e.nombre_completo','e.codigo_empleado','e.identidad',
                'e.id_puesto_trabajo','e.id_puesto_trabajo_matriz','e.estado',
                'pt.puesto_trabajo as legacy_nombre'
            )
            ->where(function ($q) { $q->where('e.estado', 1)->orWhereNull('e.estado'); })
            ->get();

        $legacyIdToMatrix   = []; // cache legacy id → id matriz
        $empleadosPorMatrix = [];

        foreach ($empleadosRaw as $emp) {
            $matrixId = $emp->id_puesto_trabajo_matriz ? (int)$emp->id_puesto_trabajo_matriz : null;

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
        }

        // Puestos a mostrar (solo con empleados), con filtro textual opcional
        $matrixIdsUsed = array_keys($empleadosPorMatrix);
        if ($buscarPuesto !== '') {
            $needle = $this->normalize($buscarPuesto);
            $matrixIdsUsed = array_values(array_filter(
                $matrixIdsUsed,
                fn($id) => str_contains($this->normalize($allPtmById[$id]->puesto_trabajo_matriz ?? ''), $needle)
            ));
        }

        $puestos = collect($matrixIdsUsed)
            ->map(fn($id) => $allPtmById[$id])
            ->sort(fn($a,$b)=>strnatcasecmp($a->puesto_trabajo_matriz, $b->puesto_trabajo_matriz))
            ->values();

        if ($puestos->isEmpty()) {
            return view('capacitaciones.matriz_requeridos', [
                'years' => $years, 'anio' => $anio,
                'buscarPuesto' => $buscarPuesto, 'buscarCap' => $buscarCap,
                'puestos' => collect(), 'caps' => collect(),
                'pivot' => [], 'totales' => [], 'totEmpleados' => [],
                'deptOrder' => [], 'deptPuestos' => [], 'deptEmp' => [], 'deptPivot' => [],
                'deptTotals' => [], 'deptGrand' => ['req'=>0,'ent'=>0,'pend'=>0],
                'rowById' => [],
            ]);
        }

        $rowById      = [];
        $totEmpleados = [];
        foreach ($puestos as $p) {
            $rid = (int)$p->id_puesto_trabajo_matriz;
            $rowById[$rid]      = $p;
            $totEmpleados[$rid] = (int)($empleadosPorMatrix[$rid] ?? 0);
        }

        /* ============================================================
         * 2) CAPACITACIONES obligatorias por puesto (matriz/legacy)
         * ============================================================ */
        $hasMatrixColumn = Schema::hasColumn('puestos_capacitacion', 'id_puesto_trabajo_matriz');
        $hasLegacyColumn = Schema::hasColumn('puestos_capacitacion', 'id_puesto_trabajo');

        $pcQuery = DB::table('puestos_capacitacion as pc')
            ->join('capacitacion as c', 'c.id_capacitacion', '=', 'pc.id_capacitacion');

        if ($hasLegacyColumn) {
            $pcQuery->leftJoin('puesto_trabajo as pt', 'pc.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo');
        }

        $pcRows = $pcQuery->select([
                $hasMatrixColumn ? 'pc.id_puesto_trabajo_matriz' : DB::raw('NULL as id_puesto_trabajo_matriz'),
                $hasLegacyColumn ? 'pc.id_puesto_trabajo'        : DB::raw('NULL as id_puesto_trabajo'),
                $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
                'c.id_capacitacion','c.capacitacion'
            ])->get();

        $capsDict = [];
        $isOblig  = []; // [id_matriz][id_cap] = true
        foreach ($pcRows as $r) {
            $matrixId = null;

            if ($hasMatrixColumn && $r->id_puesto_trabajo_matriz) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif ($hasLegacyColumn) {
                if ($r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                    $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
                } elseif ($r->legacy_nombre) {
                    $norm = $this->normalize($r->legacy_nombre);
                    if ($norm !== '' && isset($nameToMatrix[$norm])) {
                        $matrixId = $nameToMatrix[$norm];
                        if ($r->id_puesto_trabajo) {
                            $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                        }
                    }
                }
            }

            if (!$matrixId || !isset($rowById[$matrixId])) continue;

            $cid = (int)$r->id_capacitacion;
            $isOblig[$matrixId][$cid] = true;

            if (!isset($capsDict[$cid])) {
                $capsDict[$cid] = (object)['id_capacitacion'=>$cid,'capacitacion'=>$r->capacitacion];
            }
        }

        if (empty($capsDict)) {
            return view('capacitaciones.matriz_requeridos', [
                'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
                'puestos'=>$puestos,'caps'=>collect(),'pivot'=>[],'totales'=>[],'totEmpleados'=>$totEmpleados,
                'deptOrder'=>[],'deptPuestos'=>[],'deptEmp'=>[],'deptPivot'=>[],'deptTotals'=>[],
                'deptGrand'=>['req'=>0,'ent'=>0,'pend'=>0],'rowById'=>$rowById,
            ]);
        }

        // Filtro textual de capacitaciones (en PHP con normalización)
        $caps = collect(array_values($capsDict))
            ->filter(function($c) use ($buscarCap){
                if ($buscarCap==='') return true;
                $needle = $this->normalize($buscarCap);
                return str_contains($this->normalize($c->capacitacion ?? ''), $needle);
            })
            ->sort(fn($a,$b)=>strnatcasecmp($a->capacitacion,$b->capacitacion))
            ->values();

        if ($caps->isEmpty()) {
            return view('capacitaciones.matriz_requeridos', [
                'years'=>$years,'anio'=>$anio,'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
                'puestos'=>$puestos,'caps'=>collect(),'pivot'=>[],'totales'=>[],'totEmpleados'=>$totEmpleados,
                'deptOrder'=>[],'deptPuestos'=>[],'deptEmp'=>[],'deptPivot'=>[],'deptTotals'=>[],
                'deptGrand'=>['req'=>0,'ent'=>0,'pend'=>0],'rowById'=>$rowById,
            ]);
        }

        $capIds = $caps->pluck('id_capacitacion')->all();

        /* ============================================================
         * 3) RECIBIDAS (asistencias) del año por empleado, con joins
         *    - una sola query
         *    - luego filtramos por año con extractYear()
         * ============================================================ */
        $asisRows = DB::table('asistencia_capacitacion as ac')
            ->join('capacitacion_instructor as ci', 'ci.id_capacitacion_instructor', '=', 'ac.id_capacitacion_instructor')
            ->join('empleado as emp', 'emp.id_empleado', '=', 'ac.id_empleado')
            ->leftJoin('puesto_trabajo as pt', 'pt.id_puesto_trabajo', '=', 'emp.id_puesto_trabajo')
            ->select([
                'ac.fecha_recibida',
                'ac.id_empleado',
                'ci.id_capacitacion',
                'emp.id_puesto_trabajo_matriz',
                'emp.id_puesto_trabajo',
                DB::raw('pt.puesto_trabajo as legacy_nombre'),
                'emp.estado as emp_estado',
            ])
            ->whereIn('ci.id_capacitacion', $capIds)
            ->where(function ($q) { $q->where('emp.estado', 1)->orWhereNull('emp.estado'); })
            ->get();

        // Agrupar recibidas por [matrixId][capId] → set de empleados (para COUNT DISTINCT)
        $idxEnt = []; // [matrixId][capId] = count empleados distintos
        foreach ($asisRows as $r) {
            $year = $this->extractYear($r->fecha_recibida);
            if ($year !== $anio) continue;

            // mapear a id matriz (prioridad matriz → legacy nombre)
            $matrixId = null;
            if (!empty($r->id_puesto_trabajo_matriz)) {
                $matrixId = (int)$r->id_puesto_trabajo_matriz;
            } elseif (!empty($r->id_puesto_trabajo) && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
            } elseif (!empty($r->legacy_nombre)) {
                $norm = $this->normalize($r->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if (!empty($r->id_puesto_trabajo)) {
                        $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                    }
                }
            }

            if (!$matrixId || !isset($rowById[$matrixId])) continue;

            $capId = (int)$r->id_capacitacion;
            $empId = (int)$r->id_empleado;

            $idxEnt[$matrixId][$capId] = $idxEnt[$matrixId][$capId] ?? [];
            $idxEnt[$matrixId][$capId][$empId] = true; // set
        }

        // Reducir sets a contadores
        foreach ($idxEnt as $m => $byCap) {
            foreach ($byCap as $c => $setEmp) {
                $idxEnt[$m][$c] = count($setEmp);
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
            $row   = [];
            $reqPuesto = $totEmpleados[$rowId] ?? 0;

            foreach ($caps as $c) {
                if (empty($isOblig[$rowId][$c->id_capacitacion])) {
                    $row[$c->id_capacitacion] = null; // no obligatorio → en blanco
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
         * 5) Subtotales por DEPARTAMENTO + resumen
         * ============================================================ */
        $deptPuestos = [];
        $deptEmp     = [];
        $deptPivot   = [];

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
            'years'=>$years,'anio'=>$anio,
            'buscarPuesto'=>$buscarPuesto,'buscarCap'=>$buscarCap,
            'puestos'=>$puestos,'caps'=>$caps,'pivot'=>$pivot,'totales'=>$totales,
            'totEmpleados'=>$totEmpleados,
            'deptOrder'=>$deptOrder,'deptPuestos'=>$deptPuestos,'deptEmp'=>$deptEmp,
            'deptPivot'=>$deptPivot,'deptTotals'=>$deptTotals,'deptGrand'=>$deptGrand,
            'rowById'=>$rowById,
        ]);
    }

    public function detalle(Request $request)
{
    $capId = (int) $request->input('cap_id', 0);
    $rango = (string) $request->input('rango', 'anio'); // anio | 12m | todo
    $anio  = (int) ($request->input('anio') ?: date('Y'));

    // === catálogo de capacitaciones para el selector ===
    $capsAll = DB::table('capacitacion')->select('id_capacitacion','capacitacion')->orderBy('capacitacion')->get();

    if ($capId <= 0) {
        return view('capacitaciones.detalle_obligatorio', [
            'capsAll'=>$capsAll,'capId'=>$capId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'con'=>0,'pend'=>0,'avance'=>0],
            'con'=>collect(),'pend'=>collect(),
        ]);
    }

    // === puestos matriz activos (para nombres/depto + mapeo) ===
    $allPtm = DB::table('puesto_trabajo_matriz as ptm')
        ->leftJoin('departamento as d', 'ptm.id_departamento','=','d.id_departamento')
        ->select('ptm.id_puesto_trabajo_matriz','ptm.puesto_trabajo_matriz','d.departamento','ptm.estado')
        ->where(function($q){ $q->where('ptm.estado',1)->orWhereNull('ptm.estado'); })
        ->get();

    if ($allPtm->isEmpty()) {
        return view('capacitaciones.detalle_obligatorio', [
            'capsAll'=>$capsAll,'capId'=>$capId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'con'=>0,'pend'=>0,'avance'=>0],
            'con'=>collect(),'pend'=>collect(),
        ]);
    }

    $allPtmById = [];
    $nameToMatrix = [];
    foreach ($allPtm as $r) {
        $id = (int)$r->id_puesto_trabajo_matriz;
        $allPtmById[$id] = $r;
        $nameToMatrix[$this->normalize($r->puesto_trabajo_matriz)] = $id;
    }

    // === empleados activos + mapeo a puesto matriz (prioriza id matriz; fallback legacy por nombre) ===
    $empRows = DB::table('empleado as e')
        ->leftJoin('puesto_trabajo as pt','e.id_puesto_trabajo','=','pt.id_puesto_trabajo')
        ->select('e.id_empleado','e.codigo_empleado','e.nombre_completo','e.estado',
                 'e.id_puesto_trabajo_matriz','e.id_puesto_trabajo','pt.puesto_trabajo as legacy_nombre')
        ->where(function($q){ $q->where('e.estado',1)->orWhereNull('e.estado'); })
        ->get();

    $legacyIdToMatrix = [];
    $empleadoInfo = []; // id_empleado => ['emp'=>obj,'matrixId'=>int]
    foreach ($empRows as $e) {
        $matrixId = $e->id_puesto_trabajo_matriz ? (int)$e->id_puesto_trabajo_matriz : null;
        if (!$matrixId && $e->legacy_nombre) {
            $norm = $this->normalize($e->legacy_nombre);
            if ($norm !== '' && isset($nameToMatrix[$norm])) {
                $matrixId = $nameToMatrix[$norm];
                if ($e->id_puesto_trabajo) $legacyIdToMatrix[$e->id_puesto_trabajo] = $matrixId;
            }
        }
        if ($matrixId && isset($allPtmById[$matrixId])) {
            $empleadoInfo[$e->id_empleado] = ['emp'=>$e, 'matrixId'=>$matrixId];
        }
    }

    if (empty($empleadoInfo)) {
        return view('capacitaciones.detalle_obligatorio', [
            'capsAll'=>$capsAll,'capId'=>$capId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'con'=>0,'pend'=>0,'avance'=>0],
            'con'=>collect(),'pend'=>collect(),
        ]);
    }

    // === puestos donde la capacitación es OBLIGATORIA (matriz y/o legacy) ===
    $hasMatrixColumn = Schema::hasColumn('puestos_capacitacion', 'id_puesto_trabajo_matriz');
    $hasLegacyColumn = Schema::hasColumn('puestos_capacitacion', 'id_puesto_trabajo');

    $pcQ = DB::table('puestos_capacitacion as pc')->where('pc.id_capacitacion', $capId);
    if ($hasLegacyColumn) $pcQ->leftJoin('puesto_trabajo as pt','pc.id_puesto_trabajo','=','pt.id_puesto_trabajo');

    $pcRows = $pcQ->select([
        $hasMatrixColumn ? 'pc.id_puesto_trabajo_matriz' : DB::raw('NULL as id_puesto_trabajo_matriz'),
        $hasLegacyColumn ? 'pc.id_puesto_trabajo'        : DB::raw('NULL as id_puesto_trabajo'),
        $hasLegacyColumn ? 'pt.puesto_trabajo as legacy_nombre' : DB::raw('NULL as legacy_nombre'),
    ])->get();

    $obligMatrixIds = [];
    foreach ($pcRows as $r) {
        $matrixId = null;
        if ($hasMatrixColumn && $r->id_puesto_trabajo_matriz) {
            $matrixId = (int)$r->id_puesto_trabajo_matriz;
        } elseif ($hasLegacyColumn) {
            if ($r->id_puesto_trabajo && isset($legacyIdToMatrix[$r->id_puesto_trabajo])) {
                $matrixId = $legacyIdToMatrix[$r->id_puesto_trabajo];
            } elseif ($r->legacy_nombre) {
                $norm = $this->normalize($r->legacy_nombre);
                if ($norm !== '' && isset($nameToMatrix[$norm])) {
                    $matrixId = $nameToMatrix[$norm];
                    if ($r->id_puesto_trabajo) $legacyIdToMatrix[$r->id_puesto_trabajo] = $matrixId;
                }
            }
        }
        if ($matrixId && isset($allPtmById[$matrixId])) {
            $obligMatrixIds[$matrixId] = true;
        }
    }

    if (empty($obligMatrixIds)) {
        return view('capacitaciones.detalle_obligatorio', [
            'capsAll'=>$capsAll,'capId'=>$capId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'con'=>0,'pend'=>0,'avance'=>0],
            'con'=>collect(),'pend'=>collect(),
        ]);
    }

    // === empleados que DEBEN recibir esta capacitación (porque su puesto la exige)
    $obligEmployees = [];
    foreach ($empleadoInfo as $empId => $info) {
        if (isset($obligMatrixIds[$info['matrixId']])) {
            $obligEmployees[$empId] = $info;
        }
    }

    if (empty($obligEmployees)) {
        return view('capacitaciones.detalle_obligatorio', [
            'capsAll'=>$capsAll,'capId'=>$capId,'rango'=>$rango,'anio'=>$anio,
            'resumen'=>['total'=>0,'con'=>0,'pend'=>0,'avance'=>0],
            'con'=>collect(),'pend'=>collect(),
        ]);
    }

    $empIds = array_keys($obligEmployees);

    // === asistencias del capId para esos empleados (según rango)
    $asisRows = DB::table('asistencia_capacitacion as ac')
        ->join('capacitacion_instructor as ci','ci.id_capacitacion_instructor','=','ac.id_capacitacion_instructor')
        ->where('ci.id_capacitacion', $capId)
        ->whereIn('ac.id_empleado', $empIds)
        ->select('ac.id_empleado','ac.fecha_recibida')
        ->get();

    $hoy = date('Y-m-d');
    $desde12m = date('Y-m-d', strtotime('-12 months'));

    // Reducimos a última fecha por empleado dentro del rango
    $ultimaAsis = []; // emp_id => 'Y-m-d' (string) o fecha original si no se pudo normalizar
    foreach ($asisRows as $r) {
        $ok = false;
        $fNorm = $this->normalizeToDate($r->fecha_recibida);
        if ($rango === 'anio') {
            $yr = $this->extractYear($r->fecha_recibida);
            $ok = ($yr === $anio);
        } elseif ($rango === '12m') {
            // si pudimos normalizar, comparamos por fecha; si no, caemos por año aproximado (año actual o anterior)
            if ($fNorm) {
                $ok = ($fNorm >= $desde12m && $fNorm <= $hoy);
            } else {
                $yr = $this->extractYear($r->fecha_recibida);
                $ok = ($yr !== null && in_array($yr, [ (int)date('Y'), (int)date('Y')-1 ], true));
            }
        } else { // 'todo'
            $ok = true;
        }
        if (!$ok) continue;

        // conservar la más reciente (si ambas normalizadas, comparamos; si no, preferimos la que tenga fNorm)
        if (!isset($ultimaAsis[$r->id_empleado])) {
            $ultimaAsis[$r->id_empleado] = $fNorm ?: (string)$r->fecha_recibida;
        } else {
            $prev = $ultimaAsis[$r->id_empleado];
            if ($fNorm && preg_match('/^\d{4}-\d{2}-\d{2}$/', $prev)) {
                if ($fNorm > $prev) $ultimaAsis[$r->id_empleado] = $fNorm;
            } elseif ($fNorm && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $prev)) {
                // preferimos normalizada
                $ultimaAsis[$r->id_empleado] = $fNorm;
            }
            // si ninguna normaliza, lo dejamos como está (no determinamos orden)
        }
    }

    // === construir listas
    $con = [];
    $pend = [];
    foreach ($obligEmployees as $empId => $info) {
        $emp = $info['emp'];
        $ptm = $allPtmById[$info['matrixId']] ?? null;

        $row = (object)[
            'id_empleado'     => $emp->id_empleado,
            'codigo_empleado' => $emp->codigo_empleado,
            'nombre_completo' => $emp->nombre_completo,
            'puesto'          => $ptm->puesto_trabajo_matriz ?? '',
            'departamento'    => $ptm->departamento ?? '',
            'ultima_asistencia'=> $ultimaAsis[$empId] ?? null,
        ];

        if (isset($ultimaAsis[$empId])) $con[] = $row; else $pend[] = $row;
    }

    $sortFn = fn($a,$b)=> strnatcasecmp($a->departamento.' '.$a->puesto.' '.$a->nombre_completo, $b->departamento.' '.$b->puesto.' '.$b->nombre_completo);
    usort($con, $sortFn);
    usort($pend, $sortFn);

    $total  = count($obligEmployees);
    $nCon   = count($con);
    $nPend  = count($pend);
    $avance = $total > 0 ? round(($nCon * 100) / $total, 1) : 0;

    return view('capacitaciones.detalle_obligatorio', [
        'capsAll' => $capsAll,
        'capId'   => $capId,
        'rango'   => $rango,
        'anio'    => $anio,
        'resumen' => ['total'=>$total,'con'=>$nCon,'pend'=>$nPend,'avance'=>$avance],
        'con'     => collect($con),
        'pend'    => collect($pend),
    ]);
}
}
