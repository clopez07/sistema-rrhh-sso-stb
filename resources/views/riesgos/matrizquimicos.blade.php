
@extends('layouts.riesgos')

@section('title', 'Matriz de Análisis de Riesgo Químico')

@section('content')
<style>
    .tabla-wrap{
      overflow-x: auto;    /* scroll horizontal */
      overflow-y: auto;    /* scroll vertical */
      max-height: 70vh;    /* altura visible; ajusta 60–80vh si quieres */
      border: 1px solid #e5e7eb;   /* opcional */
      border-radius: 8px;          /* opcional */
      background: #fff;            /* importante para sticky */
  }

  .tabla-matriz{
      width: max-content;
      table-layout: auto;
      border-collapse: collapse;
      font-size: 12px;
  }
  .tabla-matriz th, .tabla-matriz td{
      border:1px solid #ccc;
      padding:6px;
      vertical-align: middle;
      text-align: center;
      word-break: break-word;
      background-clip: padding-box;
  }

  /* alturas fijas para calcular el offset sticky */
  :root{
      --h1: 36px; /* fila 1: departamento */
      --h2: 36px; /* fila 2: # empleados */
      --h3: 36px; /* fila 3: puesto */
  }
  thead tr.row-depto th{ position: sticky; top:0;     z-index: 3; background:#00B050; font-weight:700; text-transform:uppercase; height: var(--h1); }
  thead tr.row-num   th{ position: sticky; top:var(--h1); z-index: 2; background:#ACB9CA; font-weight:600; height: var(--h2); }
  thead tr.row-puesto th{ position: sticky; top:calc(var(--h1) + var(--h2)); z-index: 1; background:#ACB9CA; font-weight:600; height: var(--h3); }

  /* columnas fijas de la izquierda */
  .col-n{ width: 40px; }
  .col-name{ width: 320px; text-align:left; }
  .col-desc{ width: 420px; text-align:left; }
  .col-medidas{ width: 460px; text-align:left; }

  /* columnas dinámicas (puestos) */
  .th-puesto{ white-space: nowrap; min-width: 140px; } /* << evita “letra por letra” */
  .th-num{ white-space: nowrap; }

  .tag-nivel{
      display:inline-block; min-width:28px; padding:4px 6px; border-radius:4px; font-weight:700;
  }
  .ellipsis{
      overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; white-space:normal;
  }
  .legend{ font-size:11px; margin:8px 0 16px; }
  .legend span{ display:inline-block; margin-right:12px; }
  .legend .swatch{ display:inline-block; width:14px; height:14px; border:1px solid #999; vertical-align:middle; margin-right:4px; }

  /* el contenedor crea contexto de apilado */
.tabla-wrap{ position: relative; }

/* anchuras de las 2 columnas fijas */
:root{
  --w-n: 40px;         /* ancho de la col N° (tu .col-n ya usa 40px) */
  --w-name: 320px;     /* ancho de la col NOMBRE (tu .col-name ya usa 320px) */
}

/* TH fijos (con rowspan=3) */
.sticky-left-n-th{
  position: sticky; left: 0; top: 0;
  z-index: 120 !important;     /* por encima de las barras verdes/grises del thead */
  background: #fff;
  box-shadow: 2px 0 0 #e5e7eb; /* separador opcional */
}
.sticky-left-name-th{
  position: sticky; left: var(--w-n); top: 0;
  z-index: 119 !important;     /* debajo del N°, encima del resto del thead */
  background: #fff;
  box-shadow: 2px 0 0 #e5e7eb;
}

/* TD fijos (cuerpo): quedan por debajo del header para que éste los tape al subir */
.sticky-left-n-td{
  position: sticky; left: 0;
  z-index: 2; background: #fff;
  box-shadow: 2px 0 0 #e5e7eb;
}
.sticky-left-name-td{
  position: sticky; left: var(--w-n);
  z-index: 1; background: #fff;
  box-shadow: 2px 0 0 #e5e7eb;
}
</style>

    <div class="flex justify-end mb-3 gap-2 print:hidden">
        <a href="{{ route('quimicos.matriz.export') }}"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm shadow">
        Descargar Excel
        </a>
    </div>

<div class="mb-4">
    {{-- Encabezado con logo + títulos alineados --}}
<div class="flex items-center justify-center gap-4 mb-4 print:mb-2">
    <img
        src="{{ asset('img/logo.PNG') }}"  {{-- cambia la ruta si la tienes en otro lado --}}
        alt="Service and Trading Business"
        class="h-16 w-auto object-contain print:h-14"
    />
    <div class="text-center leading-tight">
        <h1 class="text-xl font-bold">MATRIZ DE ANÁLISIS DE RIESGO QUÍMICOS</h1>
        <p class="text-sm">PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS</p>
        <p class="text-sm">MATRIZ DE ANALISIS DE RIESGO / RISK ANALYSIS MATRIX</p>
    </div>
</div>


    {{-- Leyenda de colores --}}
    @php
        $legend = [
            'MA' => 'Muy Alto',
            'A'  => 'Alto',
            'M'  => 'Medio',
            'B'  => 'Bajo',
            'I'  => 'Irrelevante',
        ];
    @endphp
    <div class="legend text-center">
        @foreach ($legend as $code => $label)
            <span><i class="swatch" style="background: {{ $colorMap[$code] ?? '#fff' }}"></i> <b>{{ $code }}</b> = {{ $label }}</span>
        @endforeach
    </div>
</div>

<div class="tabla-wrap">
<table class="tabla-matriz">
    <thead>
    {{-- Fila 1: Departamentos --}}
    <tr class="row-depto">
        <th class="col-n sticky-left-n-th" rowspan="3">N°</th>
        <th class="col-name sticky-left-name-th" rowspan="3">RIESGO (NOMBRE DEL QUÍMICO)</th>
        <th class="col-desc" rowspan="3">DESCRIPCIÓN</th>
        @foreach ($headers as $dep => $puestos)
        <th colspan="{{ count($puestos) }}">{{ $dep }}</th>
        @endforeach
        <th class="col-medidas" rowspan="3">MEDIDAS DE PREVENCIÓN Y CORRECCIÓN</th>
    </tr>

    {{-- Fila 2: Número de Empleados --}}
    <tr class="row-num">
        @foreach ($headers as $dep => $puestos)
            @foreach ($puestos as $p)
                <th class="th-num">{{ $p['num'] }}</th>
            @endforeach
        @endforeach
    </tr>

    {{-- Fila 3: Puestos --}}
    <tr class="row-puesto">
        @foreach ($headers as $dep => $puestos)
            @foreach ($puestos as $p)
                <th class="th-puesto">{{ $p['puesto'] }}</th>
            @endforeach
        @endforeach
    </tr>
    </thead>

    <tbody>
        @php $i = 1; @endphp

        @foreach ($rows as $r)
            @php
                // Acceso por arreglo
                $qName    = $r['RIESGO (NOMBRE DEL QUIMICO)'] ?? $r['RIESGO (NOMBRE DEL QUÍMICO)'] ?? '';
                $qDesc    = $r['DESCRIPCION'] ?? '';
                $qMedidas = $r['MEDIDAS DE PREVENCION Y CORRECCION'] ?? '';
            @endphp
            @if (strtoupper(trim($qName)) === 'NINGUNO')
                @continue
            @endif

            <tr>
                <td class="col-n sticky-left-n-td">{{ $i++ }}</td>
                <td class="col-name sticky-left-name-td"><strong>{{ $qName }}</strong></td>
                <td class="col-desc">{{ $qDesc }}</td>

                {{-- Celdas de evaluación por puesto (I/B/M/A/MA) con color --}}
                @foreach ($headers as $dep => $puestos)
                    @foreach ($puestos as $p)
                        @php
                            $orig = trim((string)($r[$p['key']] ?? ''));
                            $code = $orig === '' ? 'I' : $orig; // por defecto: I
                            $bg   = $colorMap[$code] ?? 'transparent';
                        @endphp
                        <td title="{{ $code }}" style="background: {{ $bg }}">
                            <span class="tag-nivel" style="background: {{ $bg }}">{{ $code }}</span>
                        </td>
                    @endforeach
                @endforeach

                <td class="col-medidas">
                    <div class="ellipsis" title="{{ $qMedidas }}">{{ $qMedidas }}</div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
@endsection

