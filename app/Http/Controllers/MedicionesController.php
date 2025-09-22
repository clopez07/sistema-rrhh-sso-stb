<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use App\Exports\ReporteIluminacionExport;
use App\Exports\ReporteRuidoExport;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MedicionesController extends Controller
{

    private function xlTitle(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\*\\?\\:\\[\\]]/', ' ', $name);
        $name = trim($name);
        if ($name === '') $name = 'Hoja';
        return mb_substr($name, 0, 31);
    }

    private function ensureSheet(Spreadsheet $book, string $wantedName, Worksheet $tpl): Worksheet
    {
        $title = $this->xlTitle($wantedName);

        // Si ya existe, úsala
        if ($sheet = $book->getSheetByName($title)) {
            return $sheet;
        }

        // Si no existe, generar un título único (Nombre, Nombre (2), ...)
        $base = $title;
        $i = 2;
        while ($book->getSheetByName($title)) {
            $title = $this->xlTitle(mb_substr($base, 0, 28) . " ($i)");
            $i++;
        }

        // Clonar la plantilla y agregarla
        $new = clone $tpl;
        $new->setTitle($title);      // fijar título antes de addSheet()
        $book->addSheet($new);

        return $new;
    }

    public function captureBatch(Request $request)
{
    $localizaciones = DB::table('localizacion')
        ->select('id_localizacion','localizacion')
        ->orderBy('localizacion')
        ->get();

    // Puestos con su localización para filtrar en la vista
    $puestos = DB::table('puesto_trabajo_matriz')
        ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz')
        ->orderBy('puesto_trabajo_matriz')
        ->get();

    // Estado por localizacion respecto al año actual (si ya hay datos capturados)
    $year = now()->year;

    // Contadores por localizacion para iluminacion y ruido en el año actual
    $luxCounts = DB::table('mediciones_iluminacion')
        ->select('id_localizacion', DB::raw('COUNT(*) as c'))
        ->whereRaw('YEAR(COALESCE(fecha_realizacion_inicio, fecha_realizacion_final)) = ?', [$year])
        ->groupBy('id_localizacion')
        ->pluck('c', 'id_localizacion');

    $ruidoCounts = DB::table('mediciones_ruido')
        ->select('id_localizacion', DB::raw('COUNT(*) as c'))
        ->whereRaw('YEAR(COALESCE(fecha_realizacion_inicio, fecha_realizacion_final)) = ?', [$year])
        ->groupBy('id_localizacion')
        ->pluck('c', 'id_localizacion');

    $locStatus = $localizaciones->map(function ($lo) use ($luxCounts, $ruidoCounts) {
        $lx = (int)($luxCounts[$lo->id_localizacion] ?? 0);
        $rd = (int)($ruidoCounts[$lo->id_localizacion] ?? 0);
        return (object) [
            'id'        => $lo->id_localizacion,
            'nombre'    => $lo->localizacion,
            'cnt_lux'   => $lx,
            'cnt_ruido' => $rd,
            'has'       => ($lx + $rd) > 0,
        ];
    });

    return view('mediciones.captura_batch', [
        'localizaciones' => $localizaciones,
        'puestos'        => $puestos,
        'locStatus'      => $locStatus,
        'year'           => $year,
    ]);
}

public function storeBatch(Request $request)
{
    // 1) Cabecera: SOLO id_localizacion es obligatoria. Lo demás es opcional.
    $base = $request->validate([
        'id_localizacion'          => ['required','integer','exists:localizacion,id_localizacion'],
        'departamento'             => ['nullable','string','max:500'],
        'nombre_observador'        => ['nullable','string','max:500'],
        'fecha_realizacion_inicio' => ['nullable','date'],
        'fecha_realizacion_final'  => ['nullable','date'],
        // Los instrumentos/serie/marca de ambos tipos son opcionales
        'instrumento_ruido'        => ['nullable','string','max:150'],
        'serie_ruido'              => ['nullable','string','max:200'],
        'marca_ruido'              => ['nullable','string','max:100'],
        'nrr'                      => ['nullable','numeric'],

        'instrumento_lux'          => ['nullable','string','max:150'],
        'serie_lux'                => ['nullable','string','max:200'],
        'marca_lux'                => ['nullable','string','max:100'],
    ]);

    $ruidoP = $request->input('ruido_puntos', []);
    $luxP   = $request->input('iluminacion_puntos', []);

    // 2) Validaciones por tipo (todo opcional salvo el id_puesto en cada fila existente)
    if (!empty($ruidoP)) {
        $request->validate([
            'ruido_puntos'                                  => ['array'],
            'ruido_puntos.*.id_puesto_trabajo_matriz'       => ['required','integer','exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz'],
            'ruido_puntos.*.punto_medicion'                 => ['nullable','string','max:500'],
            'ruido_puntos.*.nivel_maximo'                   => ['nullable','numeric'],
            'ruido_puntos.*.nivel_minimo'                   => ['nullable','numeric'],
            'ruido_puntos.*.nivel_promedio'                 => ['nullable','numeric'],
            'ruido_puntos.*.nre'                            => ['nullable','numeric'],
            'ruido_puntos.*.limites_aceptables'             => ['nullable','numeric'],
            'ruido_puntos.*.observaciones'                  => ['nullable','string'],
        ]);
    }

    if (!empty($luxP)) {
        $request->validate([
            'iluminacion_puntos'                             => ['array'],
            'iluminacion_puntos.*.id_puesto_trabajo_matriz'  => ['required','integer','exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz'],
            'iluminacion_puntos.*.punto_medicion'            => ['nullable','string','max:500'],
            'iluminacion_puntos.*.promedio'                  => ['nullable','numeric'],
            'iluminacion_puntos.*.limites_aceptables'        => ['nullable','numeric'],
            'iluminacion_puntos.*.observaciones'             => ['nullable','string'],
        ]);
    }

    // 3) Inserción (conversión segura a null para vacíos)
    $null = fn($v) => ($v === '' || $v === null) ? null : $v;

    DB::transaction(function () use ($request, $base, $ruidoP, $luxP, $null) {
        $fkLo = (int)$base['id_localizacion'];

        // --- Ruido (cada fila requiere id_puesto_trabajo_matriz, lo demás puede ir null)
        foreach ($ruidoP as $row) {
            DB::table('mediciones_ruido')->insert([
                'departamento'              => $null($base['departamento']          ?? null),
                'fecha_realizacion_inicio'  => $null($base['fecha_realizacion_inicio'] ?? null),
                'fecha_realizacion_final'   => $null($base['fecha_realizacion_final']  ?? null),
                'nombre_observador'         => $null($base['nombre_observador']     ?? null),
                'instrumento'               => $null($request->input('instrumento_ruido')),
                'serie'                     => $null($request->input('serie_ruido')),
                'marca'                     => $null($request->input('marca_ruido')),
                'nrr'                       => $null($request->input('nrr')),
                'id_localizacion'           => $fkLo,
                'punto_medicion'            => $null($row['punto_medicion']         ?? null),
                'id_puesto_trabajo_matriz'  => (int)$row['id_puesto_trabajo_matriz'],
                'nivel_maximo'              => $null($row['nivel_maximo']           ?? null),
                'nivel_minimo'              => $null($row['nivel_minimo']           ?? null),
                'nivel_promedio'            => $null($row['nivel_promedio']         ?? null),
                'nre'                       => $null($row['nre']                     ?? null),
                'limites_aceptables'        => $null($row['limites_aceptables']     ?? null),
                'observaciones'             => $null($row['observaciones']          ?? null),
            ]);
        }

        // --- Iluminación (cada fila requiere id_puesto_trabajo_matriz, lo demás puede ir null)
        foreach ($luxP as $row) {
            DB::table('mediciones_iluminacion')->insert([
                'departamento'              => $null($base['departamento']          ?? null),
                'fecha_realizacion_inicio'  => $null($base['fecha_realizacion_inicio'] ?? null),
                'fecha_realizacion_final'   => $null($base['fecha_realizacion_final']  ?? null),
                'nombre_observador'         => $null($base['nombre_observador']     ?? null),
                'instrumento'               => $null($request->input('instrumento_lux')),
                'serie'                     => $null($request->input('serie_lux')),
                'marca'                     => $null($request->input('marca_lux')),
                'id_localizacion'           => $fkLo,
                'punto_medicion'            => $null($row['punto_medicion']         ?? null),
                'id_puesto_trabajo_matriz'  => (int)$row['id_puesto_trabajo_matriz'],
                'promedio'                  => $null($row['promedio']               ?? null),
                'limites_aceptables'        => $null($row['limites_aceptables']     ?? null),
                'observaciones'             => $null($row['observaciones']          ?? null),
            ]);
        }
    });

    // Nota: ya NO obligamos a que haya al menos un punto. Si no mandan puntos, no inserta nada.
    return redirect()->route('mediciones.captura')->with('ok', 'Mediciones guardadas (datos opcionales permitidos).');
}

public function reporteIluminacion(\Illuminate\Http\Request $request)
{
    $yearInput = $request->input('year');
    $currentYear = (int) now()->year;
    $year = ($yearInput === null || $yearInput === '') ? $currentYear : (int) $yearInput;

    $availableYears = collect(
        \DB::table('mediciones_iluminacion as m')
            ->selectRaw('DISTINCT YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
            ->pluck('y')
    )
        ->filter(fn($v) => !is_null($v))
        ->map(fn($v) => (int) $v)
        ->filter(fn($v) => $v > 0)
        ->unique()
        ->sortDesc()
        ->values();

    if (!$availableYears->contains($year)) {
        $availableYears = $availableYears->concat([$year])->unique()->sortDesc()->values();
    }

    // Todas las localizaciones (para mostrar secciones aunque estén vacías)
    $localizaciones = \DB::table('localizacion')
        ->select('id_localizacion','localizacion')
        ->orderBy('localizacion')
        ->get();

    // Filas (puntos) con puesto — SIN agregaciones
    $filas = \DB::table('mediciones_iluminacion as m')
        ->leftJoin('puesto_trabajo_matriz as p','p.id_puesto_trabajo_matriz','=','m.id_puesto_trabajo_matriz')
        ->select(
            'm.id',
            'm.id_localizacion',
            'm.punto_medicion',
            'm.id_puesto_trabajo_matriz',
            'p.puesto_trabajo_matriz as puesto',
            'm.promedio',
            'm.limites_aceptables',
            'm.acciones_correctivas'
        )
        // filtra por año usando inicio o final (lo que haya)
        ->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) = ?', [$year])
        ->orderBy('m.id_localizacion')
        ->orderBy('m.punto_medicion')
        ->get()
        ->groupBy('id_localizacion')
        ->map(function ($rows) {
            return $rows->sort(function ($a, $b) {
                $cmp = strnatcasecmp((string)($a->punto_medicion ?? ''),(string)($b->punto_medicion ?? ''));
                if ($cmp === 0) {
                    $cmp = strcasecmp((string)($a->puesto ?? ''),(string)($b->puesto ?? ''));
                }
                return $cmp;
            })->values();
        });

    // Ya no calculamos “media por localización” ni “límite por localización”;
    // mostramos los valores de cada fila tal cual vienen en la tabla.
    $puestos = \DB::table('puesto_trabajo_matriz')
        ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz')
        ->orderBy('puesto_trabajo_matriz')
        ->get();

    // ← NUEVO: EM por localización (si hay varias filas del estándar, tomamos el mayor)
        $emByLoc = \DB::table('estandar_iluminacion')
            ->select('id_localizacion', \DB::raw('MAX(em) as em'))
            ->groupBy('id_localizacion')
            ->pluck('em', 'id_localizacion');   // [ id_localizacion => em ]

        // ...tu código para $puestos, etc...

        return view('mediciones.reporte_iluminacion', [
            'year'           => $year,
            'localizaciones' => $localizaciones,
            'grupos'         => $filas,
            'puestos'        => $puestos,
            'years'          => $availableYears,
            'emByLoc'        => $emByLoc,       // ← PASAR A LA VISTA
        ]);
}


public function updateAccionIluminacion(\Illuminate\Http\Request $request)
{
    $v = $request->validate([
        'id' => ['required','integer','exists:mediciones_iluminacion,id'],
        'acciones_correctivas' => ['nullable','string','max:5000'],
    ]);

    \DB::table('mediciones_iluminacion')
        ->where('id', $v['id'])
        ->update([
            'acciones_correctivas' => $v['acciones_correctivas'] ?? null,
        ]);

    return response()->json(['ok'=>true]);
}

public function reporteRuido(\Illuminate\Http\Request $request)
{
    $yearInput = $request->input('year');
    $currentYear = (int) now()->year;
    $year = ($yearInput === null || $yearInput === '') ? $currentYear : (int) $yearInput;

    $availableYears = collect(
        \DB::table('mediciones_ruido as m')
            ->selectRaw('DISTINCT YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
            ->pluck('y')
    )
        ->filter(fn($v) => !is_null($v))
        ->map(fn($v) => (int) $v)
        ->filter(fn($v) => $v > 0)
        ->unique()
        ->sortDesc()
        ->values();

    if (!$availableYears->contains($year)) {
        $availableYears = $availableYears->concat([$year])->unique()->sortDesc()->values();
    }

    // Todas las localizaciones (secciones aunque estén vacías)
$localizaciones = \DB::table('localizacion')
    ->select('id_localizacion','localizacion')
    ->orderBy('localizacion')
    ->get();

/*
 * Filas (puntos) con puesto + área del puesto.
 * Luego calculamos PROM, NRR y NRE en PHP según tus reglas.
 */
$rawRows = \DB::table('mediciones_ruido as m')
    ->leftJoin('puesto_trabajo_matriz as p','p.id_puesto_trabajo_matriz','=','m.id_puesto_trabajo_matriz')
    ->leftJoin('area as a','a.id_area','=','p.id_area')
    ->select(
        'm.id_mediciones_ruido as id',
        'm.id_localizacion',
        'm.punto_medicion',
        'm.id_puesto_trabajo_matriz',
        'p.puesto_trabajo_matriz as puesto',
        'a.area as area_nombre',
        'm.nivel_maximo',
        'm.nivel_minimo',
        'm.nivel_promedio',     // por compatibilidad; lo recalculamos igual
        'm.limites_aceptables',
        'm.acciones_correctivas'
    )
    ->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) = ?', [$year])
    ->orderBy('m.id_localizacion')
    ->orderBy('m.punto_medicion')
    ->get();

/* Cálculo de columnas derivadas */
$calcRows = $rawRows->map(function ($r) {
    $max = is_numeric($r->nivel_maximo) ? (float)$r->nivel_maximo : null;
    $min = is_numeric($r->nivel_minimo) ? (float)$r->nivel_minimo : null;

    // PROMEDIO = (max + min) / 2 (si alguno falta, intenta usar el promedio guardado)
    if (!is_null($max) && !is_null($min)) {
        $prom = ($max + $min) / 2.0;
    } else {
        $prom = is_numeric($r->nivel_promedio) ? (float)$r->nivel_promedio : null;
    }

    // Límite (default 85)
    $lim = is_numeric($r->limites_aceptables) ? (float)$r->limites_aceptables : 85.0;

    // NRR y NRE
    $nrr = null;   // sólo si excede el límite
    $nre = $prom;  // por defecto, el mismo promedio

    if (!is_null($prom) && $prom > $lim) {
        $nrr = (strcasecmp((string)$r->area_nombre, 'Area Interna') === 0) ? 13.5 : 11.24;
        $nre = $prom - $nrr;
    }

    // Guardar calculados para la vista
    $r->calc_promedio = is_null($prom) ? null : round($prom, 2);
    $r->calc_nrr      = is_null($nrr)  ? null : round($nrr, 2);
    $r->calc_nre      = is_null($nre)  ? null : round($nre, 2);
    $r->lim_final     = $lim; // para mostrar (y formatear) el límite final usado

    return $r;
});

/* Agrupar por localización y ordenar dentro del grupo */
$filas = $calcRows
    ->groupBy('id_localizacion')
    ->map(function ($rows) {
        return $rows->sort(function ($a, $b) {
            $cmp = strnatcasecmp((string)($a->punto_medicion ?? ''),(string)($b->punto_medicion ?? ''));
            if ($cmp === 0) {
                $cmp = strcasecmp((string)($a->puesto ?? ''),(string)($b->puesto ?? ''));
            }
            return $cmp;
        })->values();
    });

$puestos = \DB::table('puesto_trabajo_matriz')
    ->select('id_puesto_trabajo_matriz','puesto_trabajo_matriz')
    ->orderBy('puesto_trabajo_matriz')
    ->get();

return view('mediciones.reporte_ruido', [
    'year'           => $year,
    'localizaciones' => $localizaciones,
    'grupos'         => $filas,
    'puestos'        => $puestos,
    'years'          => $availableYears,
]);
}

public function updateAccionRuido(\Illuminate\Http\Request $request)
{
    $v = $request->validate([
        'id' => ['required','integer','exists:mediciones_ruido,id_mediciones_ruido'],
        'acciones_correctivas' => ['nullable','string','max:255'],
        '_token' => ['required'],
    ]);

    \DB::table('mediciones_ruido')
        ->where('id_mediciones_ruido', $v['id'])
        ->update([
            'acciones_correctivas' => $v['acciones_correctivas'] ?? null,
        ]);

    return response()->json(['ok'=>true]);
}

// app/Http/Controllers/MedicionesController.php

public function timeline(\Illuminate\Http\Request $request)
{
    $locFilter  = $request->integer('id_localizacion'); // opcional

    $yearFrom = $request->integer('year_from');
    $yearTo   = $request->integer('year_to');
    $yearSingle = $request->integer('year');

    if ($yearSingle && $yearFrom === null && $yearTo === null) {
        $yearFrom = $yearSingle;
        $yearTo   = $yearSingle;
    }

    if ($yearFrom !== null && $yearTo === null) {
        $yearTo = $yearFrom;
    }
    if ($yearTo !== null && $yearFrom === null) {
        $yearFrom = $yearTo;
    }
    if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
        [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
    }

    $yearRange = ($yearFrom !== null && $yearTo !== null) ? [$yearFrom, $yearTo] : null;

    // Catálogos
    $locs = \DB::table('localizacion')
        ->select('id_localizacion','localizacion')
        ->orderBy('localizacion')->get();
    $locNames = $locs->pluck('localizacion','id_localizacion');
    $puestoNames = \DB::table('puesto_trabajo_matriz')
        ->pluck('puesto_trabajo_matriz','id_puesto_trabajo_matriz');

    // Límite de iluminación (EM) por (localización, puesto)
    $limLuxMap = \DB::table('estandar_iluminacion')
        ->select('id_localizacion', \DB::raw('MAX(em) as limite'))
        ->groupBy('id_localizacion')
        ->get()
        ->mapWithKeys(function($r){
            $p = $r->id_puesto_trabajo_matriz ?? 0;
            return [$r->id_localizacion.'|'.$p => $r->limite];
        });

    // Agregados por AÑO + PUNTO + PUESTO (+ loc para agrupar)
    $luxAgg = \DB::table('mediciones_iluminacion as m')
        ->selectRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
        ->addSelect('m.punto_medicion','m.id_puesto_trabajo_matriz','m.id_localizacion')
        ->selectRaw('AVG(m.promedio) as avg_lux, COUNT(*) as cnt_lux')
        ->when($locFilter,  fn($q)=>$q->where('m.id_localizacion',$locFilter))
        ->when($yearRange, fn($q)=>$q->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) BETWEEN ? AND ?', $yearRange))
        ->groupBy('y','m.punto_medicion','m.id_puesto_trabajo_matriz','m.id_localizacion')
        ->get();

    $ruidoAgg = \DB::table('mediciones_ruido as m')
        ->selectRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
        ->addSelect('m.punto_medicion','m.id_puesto_trabajo_matriz','m.id_localizacion')
        ->selectRaw('AVG(m.nivel_promedio) as avg_ruido, COUNT(*) as cnt_ruido')
        ->when($locFilter,  fn($q)=>$q->where('m.id_localizacion',$locFilter))
        ->when($yearRange, fn($q)=>$q->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) BETWEEN ? AND ?', $yearRange))
        ->groupBy('y','m.punto_medicion','m.id_puesto_trabajo_matriz','m.id_localizacion')
        ->get();

    // Años disponibles
    $yearsAll = collect(
        \DB::table('mediciones_iluminacion as m')
          ->when($locFilter, fn($q)=>$q->where('m.id_localizacion',$locFilter))
          ->selectRaw('DISTINCT YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')->pluck('y')
    )->merge(
        \DB::table('mediciones_ruido as m')
          ->when($locFilter, fn($q)=>$q->where('m.id_localizacion',$locFilter))
          ->selectRaw('DISTINCT YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')->pluck('y')
    )->filter()->unique()->sort()->values();
    $years = $yearRange ? collect(range($yearRange[0], $yearRange[1])) : $yearsAll;
    if ($yearRange && $years->isEmpty()) {
        $years = collect(range($yearRange[0], $yearRange[1]));
    }

    // Fusión por fila (loc+punto+puesto) y año
    $rowKey = function($locId, $puestoId, $punto) {
        $p = mb_strtolower(trim($punto ?? ''));
        $pid = $puestoId ?: 0;
        return $locId.'|'.$pid.'|'.$p;
    };
    $cells = [];

    foreach ($luxAgg as $r) {
        $k = $rowKey($r->id_localizacion, $r->id_puesto_trabajo_matriz, $r->punto_medicion);
        $cells[$k]['meta'] = [
            'loc_id'   => $r->id_localizacion,
            'punto'    => $r->punto_medicion,
            'puesto_id'=> $r->id_puesto_trabajo_matriz,
        ];
        $cells[$k]['data'][$r->y]['avg_lux'] = round((float)$r->avg_lux, 2);
        $cells[$k]['data'][$r->y]['cnt_lux'] = (int)$r->cnt_lux;
    }
    foreach ($ruidoAgg as $r) {
        $k = $rowKey($r->id_localizacion, $r->id_puesto_trabajo_matriz, $r->punto_medicion);
        $cells[$k]['meta'] = $cells[$k]['meta'] ?? [
            'loc_id'   => $r->id_localizacion,
            'punto'    => $r->punto_medicion,
            'puesto_id'=> $r->id_puesto_trabajo_matriz,
        ];
        $cells[$k]['data'][$r->y]['avg_ruido'] = round((float)$r->avg_ruido, 2);
        $cells[$k]['data'][$r->y]['cnt_ruido'] = (int)$r->cnt_ruido;
    }

    // Construcción de filas
    $rows = [];
    foreach ($cells as $entry) {
        $meta      = $entry['meta'];
        $locId     = $meta['loc_id'];
        $puestoId  = $meta['puesto_id'] ?: 0;
        $punto     = $meta['punto'] ?: '—';
        $locNom    = $locNames[$locId] ?? '';
        $puestoNom = $puestoNames[$puestoId] ?? '—';

        $nombre = "{$punto} — {$puestoNom}";
        $limKey = $locId.'|'.$puestoId;
        $limLux = $limLuxMap[$limKey] ?? null;

        $cols = [];
        foreach ($years as $y) {
            $d = $entry['data'][$y] ?? [];
            $cols[] = [
                'year'      => $y,
                'avg_lux'   => $d['avg_lux']   ?? null,
                'cnt_lux'   => $d['cnt_lux']   ?? 0,
                'avg_ruido' => $d['avg_ruido'] ?? null,
                'cnt_ruido' => $d['cnt_ruido'] ?? 0,
            ];
        }

        $rows[] = [
            'nombre'   => $nombre,     // Punto — Puesto
            'lim_lux'  => $limLux,
            'columns'  => $cols,
            'loc'      => $locNom,     // Para agrupar visualmente
            'loc_id'   => $locId,
            'punto'    => $punto,
            'puesto'   => $puestoNom,
        ];
    }

    // Orden: Localización > Punto
    usort($rows, function($a,$b){
        $cmp = strcmp($a['loc'] ?? '', $b['loc'] ?? '');
        if ($cmp !== 0) return $cmp;
        return strcmp($a['punto'] ?? '', $b['punto'] ?? '');
    });

    // Agrupar en categorías por localización
    $groups = [];
    foreach ($rows as $r) {
        $id = $r['loc_id'] ?? 0;
        $groups[$id]['loc']   = $r['loc'] ?? '—';
        $groups[$id]['rows'][] = $r;
    }

    // Ordenar por nombre de localización (sin helper)
    $groups = collect($groups)
        ->sortBy(fn($g) => $g['loc'] ?? '', SORT_NATURAL | SORT_FLAG_CASE)
        ->values()
        ->all();
    // Mantener orden por nombre de localización

    return view('mediciones.timeline', [
        'years'          => $years->all(),
        'yearsAll'       => $yearsAll->all(),
        'groups'         => $groups,     // <--- usamos groups en la vista
        'localizaciones' => $locs,
        'locFilter'      => $locFilter,
        'yearFrom'       => $yearFrom,
        'yearTo'         => $yearTo,
    ]);
}



public function deleteIluminacion(\Illuminate\Http\Request $request)
{
    $v = $request->validate([
        'id' => ['required','integer','exists:mediciones_iluminacion,id'],
        // _token lo verifica el middleware CSRF
    ]);

    \DB::table('mediciones_iluminacion')
        ->where('id', $v['id'])
        ->delete();

    // Para llamadas AJAX
    if ($request->wantsJson()) {
        return response()->json(['ok' => true]);
    }

    // Por si llamaras desde un form normal
    return back()->with('ok', 'Registro eliminado.');
}

public function deleteRuido(\Illuminate\Http\Request $request)
{
    $v = $request->validate([
        'id' => ['required','integer','exists:mediciones_ruido,id_mediciones_ruido'],
    ]);

    \DB::table('mediciones_ruido')
        ->where('id_mediciones_ruido', $v['id'])
        ->delete();

    if ($request->wantsJson()) {
        return response()->json(['ok' => true]);
    }
    return back()->with('ok', 'Registro de ruido eliminado.');
}

public function updateIluminacionRow(\Illuminate\Http\Request $request)
{
    $v = $request->validate([
        'id'                 => ['required','integer','exists:mediciones_iluminacion,id'],
        'punto_medicion'     => ['nullable','string','max:500'],
        'id_puesto_trabajo_matriz' => ['nullable','integer','exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz'],
        'promedio'           => ['nullable','numeric'],
        'limites_aceptables' => ['nullable','numeric'],
    ]);

    $null = fn($x) => ($x === '' ? null : $x);
    $puesto = $request->input('id_puesto_trabajo_matriz');
    $puesto = ($puesto === null || $puesto === '') ? null : (int) $puesto;

    \DB::table('mediciones_iluminacion')
        ->where('id', $v['id'])
        ->update([
            'punto_medicion'            => $null($request->input('punto_medicion')),
            'id_puesto_trabajo_matriz' => $puesto,
            'promedio'                 => $null($request->input('promedio')),
            'limites_aceptables'       => $null($request->input('limites_aceptables')),
        ]);

    return response()->json(['ok' => true]);
}

public function updateRuidoRow(\Illuminate\Http\Request $request)
{
    $v = $request->validate([
        'id'                 => ['required','integer','exists:mediciones_ruido,id_mediciones_ruido'],
        'punto_medicion'     => ['nullable','string','max:500'],
        'id_puesto_trabajo_matriz' => ['nullable','integer','exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz'],
        'nivel_maximo'       => ['nullable','numeric'],
        'nivel_minimo'       => ['nullable','numeric'],
        'nivel_promedio'     => ['nullable','numeric'],
        'nrr'                => ['nullable','numeric'],
        'nre'                => ['nullable','numeric'],
        'limites_aceptables' => ['nullable','numeric'],
    ]);

    $null = fn($x) => ($x === '' ? null : $x);
    $puesto = $request->input('id_puesto_trabajo_matriz');
    $puesto = ($puesto === null || $puesto === '') ? null : (int) $puesto;

    \DB::table('mediciones_ruido')
        ->where('id_mediciones_ruido', $v['id'])
        ->update([
            'punto_medicion'            => $null($request->input('punto_medicion')),
            'id_puesto_trabajo_matriz' => $puesto,
            'nivel_maximo'             => $null($request->input('nivel_maximo')),
            'nivel_minimo'             => $null($request->input('nivel_minimo')),
            'nivel_promedio'           => $null($request->input('nivel_promedio')),
            'nrr'                      => $null($request->input('nrr')),
            'nre'                      => $null($request->input('nre')),
            'limites_aceptables'       => $null($request->input('limites_aceptables')),
        ]);

    return response()->json(['ok' => true]);
}

public function charts(\Illuminate\Http\Request $request)
{
    $locId = $request->integer('id_localizacion');   // requerido para no saturar

    $yearFrom = $request->integer('year_from');
    $yearTo   = $request->integer('year_to');
    $yearSingle = $request->integer('year');

    if ($yearSingle && $yearFrom === null && $yearTo === null) {
        $yearFrom = $yearSingle;
        $yearTo   = $yearSingle;
    }

    if ($yearFrom !== null && $yearTo === null) {
        $yearTo = $yearFrom;
    }
    if ($yearTo !== null && $yearFrom === null) {
        $yearFrom = $yearTo;
    }
    if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
        [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
    }

    $yearRange = ($yearFrom !== null && $yearTo !== null) ? [$yearFrom, $yearTo] : null;

    // Catálogos
    $locs = \DB::table('localizacion')
        ->select('id_localizacion','localizacion')
        ->orderBy('localizacion')->get();
    $locNames = $locs->pluck('localizacion','id_localizacion');

    $puestoNames = \DB::table('puesto_trabajo_matriz')
        ->pluck('puesto_trabajo_matriz','id_puesto_trabajo_matriz');

    // Años disponibles (para el datalist del filtro)
    $yearsAll = collect(
        \DB::table('mediciones_iluminacion as m')
          ->when($locId, fn($q)=>$q->where('m.id_localizacion',$locId))
          ->selectRaw('DISTINCT YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
          ->pluck('y')
    )->merge(
        \DB::table('mediciones_ruido as m')
          ->when($locId, fn($q)=>$q->where('m.id_localizacion',$locId))
          ->selectRaw('DISTINCT YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
          ->pluck('y')
    )->filter()->unique()->sort()->values();

    // Si no hay localización seleccionada, no armamos series para evitar 1000 líneas
    $seriesLux = $seriesRuido = [];
    $years = $yearRange ? range($yearRange[0], $yearRange[1]) : $yearsAll->all();

    if ($locId) {
        // Agregados por AÑO + PUNTO + PUESTO dentro de la localización
        $luxAgg = \DB::table('mediciones_iluminacion as m')
            ->selectRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
            ->addSelect('m.punto_medicion','m.id_puesto_trabajo_matriz')
            ->selectRaw('AVG(m.promedio) as avg_lux')
            ->where('m.id_localizacion',$locId)
            ->when($yearRange, fn($q)=>$q->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) BETWEEN ? AND ?', $yearRange))
            ->groupBy('y','m.punto_medicion','m.id_puesto_trabajo_matriz')
            ->get();

        $ruidoAgg = \DB::table('mediciones_ruido as m')
            ->selectRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) as y')
            ->addSelect('m.punto_medicion','m.id_puesto_trabajo_matriz')
            ->selectRaw('AVG(m.nivel_promedio) as avg_ruido')
            ->where('m.id_localizacion',$locId)
            ->when($yearRange, fn($q)=>$q->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) BETWEEN ? AND ?', $yearRange))
            ->groupBy('y','m.punto_medicion','m.id_puesto_trabajo_matriz')
            ->get();

        // Etiquetas de años visibles (si filtran por año, solo ese; si no, todos)
        $years = $yearRange ? range($yearRange[0], $yearRange[1]) : $yearsAll->all();

        // Mapear por fila (punto+puesto) y año
        $key = fn($punto,$puestoId) => mb_strtolower(trim($punto ?? '')).'|'.($puestoId ?: 0);
        $rows = []; // key => ['label'=>..., 'data'=>[y=>valor]]
        foreach ($luxAgg as $r) {
            $k = $key($r->punto_medicion, $r->id_puesto_trabajo_matriz);
            $label = ($r->punto_medicion ?: '—').' — '.($puestoNames[$r->id_puesto_trabajo_matriz ?? 0] ?? '—');
            $rows[$k]['label'] = $label;
            $rows[$k]['lux'][$r->y] = round((float)$r->avg_lux,2);
        }
        foreach ($ruidoAgg as $r) {
            $k = $key($r->punto_medicion, $r->id_puesto_trabajo_matriz);
            $label = ($r->punto_medicion ?: '—').' — '.($puestoNames[$r->id_puesto_trabajo_matriz ?? 0] ?? '—');
            $rows[$k]['label'] = $label;
            $rows[$k]['ruido'][$r->y] = round((float)$r->avg_ruido,2);
        }

        // Construir series (alineadas a la lista de years)
        foreach ($rows as $r) {
            $dataLux   = []; $hasLux = false;
            $dataRuido = []; $hasRui = false;
            foreach ($years as $y) {
                $vL = $r['lux'][$y]   ?? null; $dataLux[]   = $vL; $hasLux = $hasLux || $vL !== null;
                $vR = $r['ruido'][$y] ?? null; $dataRuido[] = $vR; $hasRui = $hasRui || $vR !== null;
            }
            if ($hasLux)   $seriesLux[]   = ['label'=>$r['label'], 'data'=>$dataLux];
            if ($hasRui)   $seriesRuido[] = ['label'=>$r['label'], 'data'=>$dataRuido];
        }

        // Ordenar series por nombre para que sean consistentes
        usort($seriesLux,   fn($a,$b)=>strcasecmp($a['label'],$b['label']));
        usort($seriesRuido, fn($a,$b)=>strcasecmp($a['label'],$b['label']));
    }

    return view('mediciones.charts', [
        'localizaciones' => $locs,
        'locId'          => $locId,
        'locName'        => $locId ? ($locNames[$locId] ?? '') : '',
        'years'          => $years,
        'yearsAll'       => $yearsAll->all(),
        'yearFrom'       => $yearFrom,
        'yearTo'         => $yearTo,
        'seriesLux'      => $seriesLux,
        'seriesRuido'    => $seriesRuido,
    ]);
}

public function exportIluminacion(Request $request)
{
    $year  = $request->integer('year');
    $locId = $request->integer('id_localizacion');
    $fname = 'reporte_iluminacion' . ($locId ? "_loc{$locId}" : '') . ($year ? "_{$year}" : '') . '.xlsx';

    $spreadsheet = (new ReporteIluminacionExport($year, $locId))->build();

    $tempDir = storage_path('app/temp_exports');
    if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
        abort(500, 'No se pudo preparar el directorio temporal.');
    }

    $tmpFile = tempnam($tempDir, 'xls_');
    if ($tmpFile === false) {
        abort(500, 'No se pudo crear el archivo temporal.');
    }
    @unlink($tmpFile);
    $tmpPath = $tmpFile . '.xlsx';

    $writer = new Xlsx($spreadsheet);
    $writer->save($tmpPath);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    // 🔒 Evitar corrupción por compresión/buffers
    if (function_exists('ini_set')) { @ini_set('zlib.output_compression', 'Off'); }
    while (ob_get_level() > 0) { @ob_end_clean(); }

    // (opcional) sanity check
    if (!is_file($tmpPath) || filesize($tmpPath) < 100) {
        @unlink($tmpPath);
        abort(500, 'El archivo no se generó correctamente.');
    }

    return response()->download($tmpPath, $fname, [
        'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
        'Pragma'                    => 'public',
        'Content-Transfer-Encoding' => 'binary',
    ])->deleteFileAfterSend(true);
}

    public function exportRuido(Request $request)
{
    $year  = $request->integer('year');
    $locId = $request->integer('id_localizacion');
    $fname = 'reporte_ruido' . ($locId ? "_loc{$locId}" : '') . ($year ? "_{$year}" : '') . '.xlsx';

    $spreadsheet = (new ReporteRuidoExport($year, $locId))->build();

    $tempDir = storage_path('app/temp_exports');
    if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
        abort(500, 'No se pudo preparar el directorio temporal.');
    }

    $tmpFile = tempnam($tempDir, 'xls_');
    if ($tmpFile === false) {
        abort(500, 'No se pudo crear el archivo temporal.');
    }
    @unlink($tmpFile);
    $tmpPath = $tmpFile . '.xlsx';

    $writer = new Xlsx($spreadsheet);
    $writer->save($tmpPath);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    // 🔒 Evitar corrupción por compresión/buffers
    if (function_exists('ini_set')) { @ini_set('zlib.output_compression', 'Off'); }
    while (ob_get_level() > 0) { @ob_end_clean(); }

    if (!is_file($tmpPath) || filesize($tmpPath) < 100) {
        @unlink($tmpPath);
        abort(500, 'El archivo no se generó correctamente.');
    }

    return response()->download($tmpPath, $fname, [
        'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
        'Pragma'                    => 'public',
        'Content-Transfer-Encoding' => 'binary',
    ])->deleteFileAfterSend(true);
}

public function exportIluminacionDesdePlantilla(Request $request)
{
    $year = (int) now()->year;

    $tplPath = storage_path('app/public/medicion_iluminacion.xlsx');
    if (!is_file($tplPath)) {
        abort(404, 'No se encontró la plantilla medicion_iluminacion.xlsx en storage/app/public');
    }
    $spreadsheet = IOFactory::load($tplPath);
    /** @var Worksheet $tplSheet */
    $tplSheet = $spreadsheet->getSheetByName('Plantilla') ?? $spreadsheet->getActiveSheet();

    $headerRow   = 12;
    $firstData   = 13;
    $perPage     = 30;
    $colsRange   = 'A:F';

    $colNo   = 'A';
    $colZona = 'B';
    $colProm = 'C';
    $colLim  = 'D';
    $colAcc  = 'F';

    $emByLoc = DB::table('estandar_iluminacion')
        ->select('id_localizacion', DB::raw('MAX(em) as em'))
        ->groupBy('id_localizacion')
        ->pluck('em', 'id_localizacion');

    $base = DB::table('mediciones_iluminacion')
        ->whereRaw('YEAR(COALESCE(fecha_realizacion_inicio, fecha_realizacion_final)) = ?', [$year])
        ->orderBy('departamento')
        ->orderBy('id_localizacion')
        ->orderBy('punto_medicion');

    $departamentos = (clone $base)->pluck('departamento')->filter()->unique()->values();

    $copyRow = function(Worksheet $sheet, int $srcRow, int $dstRow, string $rangeCols) {
        [$colStart, $colEnd] = explode(':', $rangeCols);
        for ($col = $colStart; $col <= $colEnd; $col++) {
            $src = $col.$srcRow; $dst = $col.$dstRow;
            $cell = $sheet->getCell($src);
            $sheet->setCellValueExplicit($dst, $cell->getValue(), $cell->getDataType());
            $sheet->duplicateStyle($sheet->getStyle($src), $dst);
            $sheet->getColumnDimension($col)->setWidth($sheet->getColumnDimension($col)->getWidth());
        }
        $sheet->getRowDimension($dstRow)->setRowHeight($sheet->getRowDimension($srcRow)->getRowHeight());

        foreach ($sheet->getMergeCells() as $merge) {
            [$mStart, $mEnd] = explode(':', $merge);
            if (preg_match('/([A-Z]+)(\d+)/', $mStart, $m1) && (int)$m1[2] === $srcRow &&
                preg_match('/([A-Z]+)(\d+)/', $mEnd, $m2)) {
                $sheet->mergeCells($m1[1].$dstRow.':'.$m2[1].$dstRow);
            }
        }
    };

    foreach ($departamentos as $dep) {

        // ✅ usar helper como método y nombre correcto de variable
        $sheet = $this->ensureSheet($spreadsheet, $dep, $tplSheet);

        $rows = (clone $base)
            ->where('departamento', $dep)   // <-- $dep (no $dept)
            ->select(
                'departamento',
                'nombre_observador',
                'instrumento',
                'serie',
                'marca',
                'id_localizacion',
                'punto_medicion',
                'promedio',
                'limites_aceptables',
                'acciones_correctivas',
                'fecha_realizacion_inicio',
                'fecha_realizacion_final'
            )
            ->get();

        if ($rows->isEmpty()) { continue; }

        // ---- Encabezado (C4.., B8, D8)
        $minIni = $rows->min('fecha_realizacion_inicio');
        $maxFin = $rows->max('fecha_realizacion_final');

        $sheet->setCellValue('C4', $dep ?? '');
        $sheet->setCellValue('C5', (string) ($rows->first()->nombre_observador ?? ''));

        $rangoFechas = '';
        if ($minIni || $maxFin) {
            $ini = $minIni ? (new \DateTime($minIni))->format('d/m/Y') : '';
            $fin = $maxFin ? (new \DateTime($maxFin))->format('d/m/Y') : '';
            $rangoFechas = trim($ini.' a '.$fin);
        }
        $sheet->setCellValue('C6', $rangoFechas);

        $sheet->setCellValue('C7', (string) ($rows->first()->instrumento ?? ''));
        $sheet->setCellValue('B8', (string) ($rows->first()->serie ?? ''));
        $sheet->setCellValue('D8', (string) ($rows->first()->marca ?? ''));

        // ---- Limpia cuerpo
        for ($r = $firstData; $r <= $firstData + $perPage + 2000; $r++) {
            foreach (['A','B','C','D','E','F'] as $c) { $sheet->setCellValue($c.$r, ''); }
        }

        // ---- Datos paginados
        $i = 0;
        foreach ($rows as $rec) {
            $page      = intdiv($i, $perPage);
            $indexInPg = $i % $perPage;
            $baseRow   = $headerRow + $page * ($perPage + 1);
            $hdrRow    = $baseRow;
            $dstRow    = $baseRow + 1 + $indexInPg;

            if ($indexInPg === 0 && $page > 0) {
                $copyRow($sheet, $headerRow, $hdrRow, $colsRange);
            }
            $copyRow($sheet, $firstData, $dstRow, $colsRange);

            $lim = $rec->limites_aceptables;
            if ($lim === null) {
                $lim = $emByLoc[$rec->id_localizacion] ?? null;
            }

            $sheet->setCellValue($colNo   . $dstRow, $indexInPg + 1);
            $sheet->setCellValue($colZona . $dstRow, (string) ($rec->punto_medicion ?? ''));
            $sheet->setCellValue($colProm . $dstRow, is_null($rec->promedio) ? '' : (float)$rec->promedio);
            $sheet->setCellValue($colLim  . $dstRow, is_null($lim) ? '' : (float)$lim);
            // $sheet->setCellValue($colAcc  . $dstRow, (string) ($rec->acciones_correctivas ?? ''));

            $i++;
        }
    }

    // ❌ Quita la hoja "Plantilla" si quedó en el libro
    if ($tpl = $spreadsheet->getSheetByName('Plantilla')) {
        $idx = $spreadsheet->getIndex($tpl);
        $spreadsheet->removeSheetByIndex($idx);
    }

    while (ob_get_level()) { ob_end_clean(); }
    $filename = "reporte_iluminacion_departamentos_{$year}.xlsx";

    return response()->streamDownload(function () use ($spreadsheet) {
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }, $filename, [
        'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
        'Pragma'                    => 'no-cache',
        'Expires'                   => '0',
        'Content-Transfer-Encoding' => 'binary',
    ]);
}



}
