@extends('layouts.riesgos')

@section('title', 'MATRIZ DE EVALUACION DE RIESGOS')

@section('content')
@php
    $departamentos = [];
    $ordenColumnas = [];

    if (count($matriz) > 0) {
        foreach (array_keys((array)$matriz[0]) as $col) {
            if (in_array($col, ['N°','RIESGO','DESCRIPCION'])) continue;

            $parts = explode('||', $col);
            $dep    = $parts[0] ?? 'SIN DEPTO';
            $puesto = $parts[1] ?? 'Puesto';
            $num    = (int)($parts[2] ?? 0);

            $departamentos[$dep][] = [$puesto, $num, $col];
            $ordenColumnas[] = $col;
        }
    }

    $mapNivelColor = [
        'Riesgo Muy Alto'     => '#ff0000',
        'Riesgo Alto'         => '#be5014',
        'Riesgo Medio'        => '#ffc000',
        'Riesgo Bajo'         => '#ffff00',
        'Riesgo Irrelevante'  => '#92d050',
        '5' => '#ff0000','4' => '#be5014','3' => '#ffc000','2' => '#ffff00','1' => '#92d050',
    ];

    // === MEDIDAS POR RIESGO (para pintar la última columna) ===
       // Asegurar que no se corte el GROUP_CONCAT en listas largas
    \DB::statement('SET SESSION group_concat_max_len = 1000000');

    try {
        $medidasRows = \DB::table('medidas_riesgo_puesto as mrp')
            ->join('riesgo as r', 'r.id_riesgo', '=', 'mrp.id_riesgo')
            ->leftJoin('area as a', 'a.id_area', '=', 'mrp.id_area')
            ->leftJoin('epp as e', 'e.id_epp', '=', 'mrp.id_epp')
            ->leftJoin('capacitacion as c', 'c.id_capacitacion', '=', 'mrp.id_capacitacion')
            ->leftJoin('senalizacion as s', 's.id_senalizacion', '=', 'mrp.id_senalizacion')
            ->leftJoin('otras_medidas as o', 'o.id_otras_medidas', '=', 'mrp.id_otras_medidas')
            ->selectRaw("
                r.nombre_riesgo as riesgo,
                GROUP_CONCAT(DISTINCT NULLIF(a.area, '')          ORDER BY a.area          SEPARATOR ', ') as areas,
                GROUP_CONCAT(DISTINCT NULLIF(e.equipo, '')        ORDER BY e.equipo        SEPARATOR ', ') as epps,
                GROUP_CONCAT(DISTINCT NULLIF(c.capacitacion, '')  ORDER BY c.capacitacion  SEPARATOR ', ') as caps,
                GROUP_CONCAT(DISTINCT NULLIF(s.senalizacion, '')  ORDER BY s.senalizacion  SEPARATOR ', ') as sens,
                GROUP_CONCAT(DISTINCT NULLIF(o.otras_medidas, '') ORDER BY o.otras_medidas SEPARATOR ', ') as otras
            ")
            ->groupBy('r.id_riesgo','r.nombre_riesgo')
            ->get();

        $medidasMap = [];
        foreach ($medidasRows as $m) {
            $partes = [];
            if (!empty($m->areas)) $partes[] = 'Áreas: ' . $m->areas;
            if (!empty($m->epps))  $partes[] = 'EPP: ' . $m->epps;
            if (!empty($m->caps))  $partes[] = 'Capacitaciones: ' . $m->caps;
            if (!empty($m->sens))  $partes[] = 'Señalización: ' . $m->sens;
            if (!empty($m->otras)) $partes[] = 'Otras: ' . $m->otras;

            $texto = implode("\n• ", $partes);
            if ($texto !== '') $texto = '• ' . $texto;
            $medidasMap[mb_strtoupper(trim($m->riesgo), 'UTF-8')] = $texto;
        }
    } catch (\Throwable $e) {
        $medidasMap = [];
    }
@endphp

<div class="flex justify-end mb-3 gap-2 print:hidden">
  <a href="{{ route('evaluacionriesgos.export') }}"
     class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm shadow">
     Descargar Excel
  </a>
</div>

{{-- Encabezado con logo + títulos alineados --}}
<div class="flex items-center justify-center gap-4 mb-4 print:mb-2">
    <img src="{{ asset('img/logo.PNG') }}" alt="Service and Trading Business"
         class="h-16 w-auto object-contain print:h-14" />
    <div class="text-center leading-tight">
        <h1 class="text-xl font-bold">MATRIZ DE EVALUACION DE RIESGOS POR PUESTO DE TRABAJO</h1>
        <p class="text-sm">PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS</p>
        <p class="text-sm">MATRIZ DE ANALISIS DE RIESGO / RISK ANALYSIS MATRIX</p>
    </div>
</div>

<style>
  /* Contenedor con scroll y contexto de apilado */
  .tabla-wrap{
  overflow-x:auto; overflow-y:auto; max-height:70vh;
  position: relative;                 /* contexto de apilado */
  border:1px solid #e5e7eb; border-radius:8px; background:#fff;
}

  .tabla-matriz{ width:max-content; table-layout:auto; border-collapse:collapse; font-size:12px; }
  .tabla-matriz th, .tabla-matriz td{
    border:1px solid #d1d5db; padding:6px; text-align:center; vertical-align:middle;
    word-break:break-word; background-clip:padding-box;
  }

  /* Alturas */
  :root{
    --h1: 40px;  /* fila 1: Departamentos */
    --h2: 40px;  /* fila 2: RIESGO + Puestos */
    --h3: 36px;  /* fila 3: # Empleados */
    --w-ries: 320px;  /* ancho col RIESGO (por si lo usas en otros sitios) */
  }

  /* header pegajoso (igual que ya tienes) */
  thead tr.row-areas   th{ position: sticky; top:0;                           z-index:100; height:var(--h1); }
  thead tr.row-puestos th{ position: sticky; top:var(--h1);                   z-index: 90; height:var(--h2); }
  thead tr.row-numeros th{ position: sticky; top:calc(var(--h1) + var(--h2)); z-index: 80; height:var(--h3); }

  /* Anchos de las 2 primeras columnas */
  .col-ries { width: 320px; text-align: left; }
  .col-desc { width: 420px; text-align: left; }

  /* RIESGO (TH con rowspan=3) fijo a la izquierda y por ENCIMA de la franja azul */
  .sticky-left-ries-th{
    position: sticky;
    left: 0;
    top: 0;                       /* alinea con la fila 1 del thead */
    z-index: 120 !important;      /* > 100 para que no lo tape el header morado */
    background: #fff;
    box-shadow: 2px 0 0 #e5e7eb;  /* separador opcional */
  }

  /* Celdas del cuerpo de la columna RIESGO: por debajo del header (se ocultan al subir) */
  .sticky-left-ries-td{
    position: sticky;
    left: 0;
    z-index: 20;                  /* menor que el header */
    background: #fff;
    box-shadow: 2px 0 0 #e5e7eb;
  }

  /* PRIMERA COLUMNA DE PUESTOS fija: se ubica justo después de RIESGO (no contamos DESCRIPCION porque no es sticky) */
  .sticky-first-puesto-th{ position:sticky; left: var(--w-ries); z-index:70; background:#fff; }
  .sticky-first-puesto-td{ position:sticky; left: var(--w-ries); z-index:45; background:#fff; }

  @media print{
    .tabla-wrap{ overflow:visible; max-height:none; }
    thead th, .sticky-left-ries-th, .sticky-left-ries-td, .sticky-first-puesto-th, .sticky-first-puesto-td{ position:static !important; box-shadow:none !important; }
  }
</style>

<div class="tabla-wrap">
  <table class="tabla-matriz w-full text-xs">
<thead>
  @php
    // Para saltar cualquier clave tipo "MEDIDAS" que se haya colado en $departamentos
    $esMedidas = fn($d) => preg_match('/^medidas(\s+de.*)?$/i', trim((string)$d));
  @endphp

  {{-- Fila 1: barra superior (Departamentos) --}}
  <tr class="row-areas">
    <th rowspan="3" class="px-2 py-1 bg-blue-500 col-ries sticky-left-ries-th">RIESGO</th>

    <th class="px-2 py-2 text-left font-bold bg-blue-500">Áreas de Trabajo</th>

    @foreach($departamentos as $dep => $puestos)
      @continue($esMedidas($dep))
      <th colspan="{{ count($puestos) }}" class="px-2 py-2 text-center font-bold bg-blue-500 text-gray-900">
        {{ $dep }}
      </th>
    @endforeach

    {{-- Columna final de Medidas (fuera de los grupos) --}}
    <th rowspan="3" class="px-2 py-1 bg-blue-500 text-left min-w-[420px]">
      MEDIDAS DE PREVENCIÓN Y CORRECCIÓN
    </th>
  </tr>

  {{-- Fila 2: DESCRIPCIÓN + Puestos --}}
  <tr class="row-puestos">
    <th class="px-2 py-1 bg-blue-300 col-desc">PUESTOS DE TRABAJO</th>
    @foreach($departamentos as $dep => $puestos)
      @continue($esMedidas($dep))
      @foreach($puestos as [$puesto, $num, $key])
        <th class="px-2 py-1 bg-blue-300 text-center whitespace-nowrap min-w-[140px]">
          {{ $puesto }}
        </th>
      @endforeach
    @endforeach
  </tr>

  {{-- Fila 3: Número de Empleados --}}
  <tr class="row-numeros">
    <th class="px-2 py-1 bg-blue-200 text-left">Número de Empleados</th>
    @foreach($departamentos as $dep => $puestos)
      @continue($esMedidas($dep))
      @foreach($puestos as [$puesto, $num, $key])
        <th class="px-2 py-1 text-center bg-blue-200">{{ $num }}</th>
      @endforeach
    @endforeach
  </tr>
</thead>

<tbody>
  @foreach($matriz as $fila)
    <tr>
      <td class="px-2 py-1 text-left col-ries sticky-left-ries-td">{{ $fila->{'RIESGO'} }}</td>
      <td class="px-2 py-1 text-left col-desc">{{ $fila->{'DESCRIPCION'} }}</td>

      @foreach($departamentos as $dep => $puestos)
        @continue($esMedidas($dep))
        @foreach($puestos as [$puesto, $num, $key])
          @php
            $val    = $fila->$key ?? null;
            $valKey = is_string($val) ? trim($val) : $val;
            $color  = $mapNivelColor[$valKey] ?? $mapNivelColor[(string)$valKey] ?? '#ffffff';
          @endphp
          <td class="px-2 py-1 text-center" style="background-color: {{ $color }}">
            {{ $val ?? '—' }}
          </td>
        @endforeach
      @endforeach

      @php
        $riesgoKey = mb_strtoupper(trim((string)($fila->{'RIESGO'} ?? '')), 'UTF-8');
        $medTxt = $medidasMap[$riesgoKey] ?? '';
      @endphp
      <td class="px-2 py-1 text-left align-top">
        {!! nl2br(e($medTxt)) !!}
      </td>
    </tr>
  @endforeach
</tbody>
  </table>
</div>
@endsection
