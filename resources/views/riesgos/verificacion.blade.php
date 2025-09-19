{{-- resources/views/riesgos/verificacion.blade.php --}}
@extends('layouts.riesgos')

@section('title', 'Formato de Verificación de Riesgos')

@section('content')

<style>
  @media print {
    @page { margin: 12mm; }
    body { background: #fff !important; }
    header, nav { display: none !important; }
    .print\:shadow-none { box-shadow: none !important; }
    .rounded-2xl, .rounded-xl { border-radius: 8px !important; }
    .border-slate-200 { border-color: #e5e7eb !important; }
  }
</style>

@php
    // Parámetros de estado (vienen del controlador, pero dejamos fallback por si acaso)
    $mode        = $mode        ?? request('mode', 'verificacion');   // verificacion | buscar
    $by          = $by          ?? request('by', 'puesto');           // puesto|riesgo|epp|capacitacion|senalizacion|otras
    $idSeleccion = $idSeleccion ?? (int) request('id', 0);            // id del item buscado (riesgo/epp/...)
    $soloSi      = isset($soloSi) ? (bool)$soloSi : (bool) request()->boolean('solo_si', $mode==='buscar' && $by==='puesto');

    // 1) Colores por tipo
    $colores = [
        'MECÁNICO'           => ['header' => 'from-orange-500 to-orange-600',  'chip' => 'border-orange-500'],
        'ELÉCTRICO'          => ['header' => 'from-blue-500 to-blue-600',      'chip' => 'border-blue-500'],
        'FUEGO Y EXPLOSIÓN'  => ['header' => 'from-yellow-400 to-yellow-500',  'chip' => 'border-yellow-500'],
        'QUÍMICOS'           => ['header' => 'from-emerald-500 to-emerald-600','chip' => 'border-emerald-500'],
        'ERGONÓMICO'         => ['header' => 'from-purple-500 to-purple-600',  'chip' => 'border-purple-500'],
        'PSICOSOCIAL'        => ['header' => 'from-pink-500 to-pink-600',      'chip' => 'border-pink-500'],
        'FÍSICO'             => ['header' => 'from-cyan-500 to-cyan-600',      'chip' => 'border-cyan-500'],
        'BIOLÓGICO'          => ['header' => 'from-lime-500 to-lime-600',      'chip' => 'border-lime-500'],
    ];

    $totalSi = 0;
    $totalNo = 0;
    foreach (($grupos ?? []) as $g) {
        foreach ($g['riesgos'] as $r) {
            $v = mb_strtolower(trim((string)($r['valor'] ?? '')));
            if ($v === 'sí' || $v === 'si' || $v === '1') $totalSi++; else $totalNo++;
        }
    }
@endphp

<div class="max-w-7xl mx-auto space-y-6 print:space-y-4">

    {{-- Encabezado --}}
    <header class="bg-gradient-to-r from-slate-50 to-white rounded-2xl border border-slate-200 p-6 shadow print:shadow-none">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    {{ $mode === 'buscar' ? 'Buscador de Requisitos por Riesgos/Medidas' : 'Verificación de Riesgos por Puesto de Trabajo' }}
                </h1>
                @if($mode === 'verificacion' && !empty($puestos))
                    <p class="text-slate-600 text-sm">
                        Puesto: <span class="font-medium">
                            {{ optional(collect($puestos)->firstWhere('id', $puestoSeleccionado ?? null))['nombre'] ?? '—' }}
                        </span>
                    </p>
                @endif
                @if($mode === 'buscar' && ($by ?? '') !== 'puesto' && !empty($criterioNombre))
                    <p class="text-slate-600 text-sm">
                        Búsqueda por <span class="font-medium uppercase">{{ $by }}</span>: <span class="font-medium">{{ $criterioNombre }}</span>
                    </p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500">Fecha: {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>
    </header>

    {{-- Barra superior de filtros / acciones --}}
    <div class="bg-white/70 backdrop-blur rounded-2xl shadow p-4 sticky top-0 z-10 print:hidden">
        <form id="verificacion-form" method="GET" action="{{ route('riesgos.verificacion') }}" class="grid md:grid-cols-4 gap-3 items-end">
            {{-- Modo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modo</label>
                <select name="mode" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500">
                    <option value="verificacion" {{ $mode==='verificacion' ? 'selected' : '' }}>Verificación</option>
                    <option value="buscar" {{ $mode==='buscar' ? 'selected' : '' }}>Buscar</option>
                </select>
            </div>

            {{-- Buscar por (solo cuando mode=buscar) --}}
            <div class="{{ $mode==='buscar' ? '' : 'opacity-60 pointer-events-none' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por</label>
                <select name="by" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500">
                    @foreach(['puesto'=>'Puesto','riesgo'=>'Riesgo','epp'=>'EPP','capacitacion'=>'Capacitación','senalizacion'=>'Señalización','otras'=>'Otras medidas'] as $k=>$lbl)
                        <option value="{{ $k }}" {{ $by===$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Selector dinámico según "by" --}}
            <div class="{{ ($mode==='verificacion' || ($mode==='buscar' && $by==='puesto')) ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Puesto de trabajo</label>
                <select name="puesto" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500" {{ ($mode==='verificacion' || ($mode==='buscar' && $by==='puesto')) ? '' : 'disabled' }}>
                    @foreach(($puestos ?? []) as $p)
                        <option value="{{ $p['id'] }}" {{ ($puestoSeleccionado ?? null) == $p['id'] ? 'selected' : '' }}>
                            {{ $p['nombre'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="{{ ($mode==='buscar' && $by==='riesgo') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Riesgo</label>
                <select name="id" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500" {{ ($mode==='buscar' && $by==='riesgo') ? '' : 'disabled' }}>
                    @foreach(($riesgosLista ?? []) as $o)
                        <option value="{{ $o['id'] }}" {{ $idSeleccion == $o['id'] ? 'selected' : '' }}>
                            {{ $o['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="{{ ($mode==='buscar' && $by==='epp') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">EPP</label>
                <select name="id" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500" {{ ($mode==='buscar' && $by==='epp') ? '' : 'disabled' }}>
                    @foreach(($eppLista ?? []) as $o)
                        <option value="{{ $o['id'] }}" {{ $idSeleccion == $o['id'] ? 'selected' : '' }}>
                            {{ $o['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="{{ ($mode==='buscar' && $by==='capacitacion') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacitación</label>
                <select name="id" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500" {{ ($mode==='buscar' && $by==='capacitacion') ? '' : 'disabled' }}>
                    @foreach(($capacitacionLista ?? []) as $o)
                        <option value="{{ $o['id'] }}" {{ $idSeleccion == $o['id'] ? 'selected' : '' }}>
                            {{ $o['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="{{ ($mode==='buscar' && $by==='senalizacion') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Señalización</label>
                <select name="id" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500" {{ ($mode==='buscar' && $by==='senalizacion') ? '' : 'disabled' }}>
                    @foreach(($senalizacionLista ?? []) as $o)
                        <option value="{{ $o['id'] }}" {{ $idSeleccion == $o['id'] ? 'selected' : '' }}>
                            {{ $o['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="{{ ($mode==='buscar' && $by==='otras') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Otras medidas</label>
                <select name="id" class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-blue-500" {{ ($mode==='buscar' && $by==='otras') ? '' : 'disabled' }}>
                    @foreach(($otrasLista ?? []) as $o)
                        <option value="{{ $o['id'] }}" {{ $idSeleccion == $o['id'] ? 'selected' : '' }}>
                            {{ $o['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Solo Sí (visible en buscar por puesto) --}}
            <div class="{{ ($mode==='buscar' && $by==='puesto') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="solo_si" value="1" class="rounded border-gray-300" {{ $soloSi ? 'checked' : '' }}>
                    Mostrar solo "Sí"
                </label>
            </div>

            <div class="md:col-span-4 flex gap-2">
                <button class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow">
                    {{ $mode==='buscar' ? 'Buscar' : 'Aplicar' }}
                </button>

                @if($mode!=='buscar')
                <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow">
                    Imprimir
                </button>
                <a href="{{ route('riesgos.verificacion.export', request()->query()) }}"
                   class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow">
                   Descargar Excel
                </a>
                <a href="{{ route('riesgos.verificacion.plan_accion') }}"
                   class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow">
                   Plan de Acción Control de Riesgos
                </a>
                @endif

                @if($mode!=='buscar')
                <div class="ms-auto flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M16.707 5.293a1 1 0 0 1 0 1.414L8.414 15l-5.121-5.121a1 1 0 1 1 1.414-1.414L8.414 12.172l7.293-7.293a1 1 0 0 1 1.414 0Z"/></svg>
                        Sí: {{ $totalSi }}
                    </span>
                    <span class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-300">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 0 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 1 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"/></svg>
                        No: {{ $totalNo }}
                    </span>
                </div>
                @endif
            </div>
        </form>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('verificacion-form');
        const modeSel = form.querySelector('select[name="mode"]');
        const bySel = form.querySelector('select[name="by"]');
        const submitOnChange = () => form.submit();
        modeSel && modeSel.addEventListener('change', submitOnChange);
        bySel && bySel.addEventListener('change', submitOnChange);
      });
    </script>

    {{-- CONTENIDO --}}
    @if($mode === 'verificacion' || ($mode==='buscar' && $by==='puesto'))
        {{-- Tarjetas por Tipo de Riesgo (verificación normal o buscar por puesto) --}}
        @forelse($grupos as $tipo => $grupo)
            @php
                $c = $colores[$tipo] ?? ['header'=>'from-slate-400 to-slate-500','chip'=>'border-slate-400'];
                $siCount = collect($grupo['riesgos'])->filter(fn($r)=>strtolower($r['valor'])==='sí' || strtolower($r['valor'])==='si' || $r['valor']==='1')->count();
                $noCount = max(count($grupo['riesgos']) - $siCount, 0);
            @endphp

            {{-- Si estamos en buscar/puesto con solo_si, ocultar categorías sin "Sí" --}}
            @if(!($mode==='buscar' && $by==='puesto' && $soloSi && $siCount===0))
            <div class="bg-gradient-to-r {{ $c['header'] }} text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold tracking-wide">{{ $tipo }}</h2>

                    <div class="flex items-center gap-2 text-[13px]">
                    <span class="bg-white/20 px-2 py-0.5 rounded-full">Sí: {{ $siCount }}</span>
                    <span class="bg-white/20 px-2 py-0.5 rounded-full">No: {{ $noCount }}</span>

                    @php
                        $__tipo_up   = mb_strtoupper(trim((string)$tipo));
                        $__esFisico  = str_contains($__tipo_up, 'FÍSICO') || str_contains($__tipo_up, 'FISICO');
                        $__esQuimico = str_contains($__tipo_up, 'QUÍM')  || str_contains($__tipo_up, 'QUIM');
                        $__puestoId  = (int) (request('puesto') ?: ($puestoSeleccionado ?? 0));
                    @endphp

                    @if($__esFisico && $__puestoId)
                    <a href="{{ route('riesgos.fisico.puesto', ['puesto' => $__puestoId]) }}"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white">
                        Ver detalles
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"/>
                        </svg>
                    </a>
                    @endif

                    @if($__esQuimico && $__puestoId)
                    <a href="{{ url('/quimicos-por-puesto') }}?puesto={{ $__puestoId }}"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white">
                        Ver químicos
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"/>
                        </svg>
                    </a>
                    @endif

                    </div>
                </div>
                </div>


                <div class="p-6">
                    <div class="grid md:grid-cols-2 gap-3">
                        @foreach($grupo['riesgos'] as $r)
                            @php $esSi = (mb_strtolower($r['valor'])==='sí' || mb_strtolower($r['valor'])==='si' || $r['valor']==='1'); @endphp

                            {{-- En buscar/puesto con solo_si=1, ocultar "No" --}}
                            @if(!($mode==='buscar' && $by==='puesto' && $soloSi && !$esSi))
                                <div class="flex items-center justify-between gap-3 rounded-xl border {{ $c['chip'] }} bg-slate-50/60 px-4 py-3">
                                    <span class="text-slate-800">{{ $r['nombre'] }}</span>
                                    @if($esSi)
                                        <span class="inline-flex items-center gap-1 text-sm px-2.5 py-1 rounded-full bg-green-100 text-green-700 border border-green-200">
                                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M16.707 5.293a1 1 0 0 1 0 1.414L8.414 15l-5.121-5.121a1 1 0 1 1 1.414-1.414L8.414 12.172l7.293-7.293a1 1 0 0 1 1.414 0Z"/></svg>
                                            Sí
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-sm px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-300">
                                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 0 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 1 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"/></svg>
                                            No
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Medidas por categoría (las que calculó el controlador) --}}
                    <div class="mt-6 grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                        @php
                            $med = $grupo['medidas'] ?? ['epp'=>[], 'capacitacion'=>[], 'senalizacion'=>[], 'otras'=>[]];
                            $cards = [
                                ['titulo'=>'EPP','items'=>$med['epp'] ?? []],
                                ['titulo'=>'Capacitación','items'=>$med['capacitacion'] ?? []],
                                ['titulo'=>'Señalización','items'=>$med['senalizacion'] ?? []],
                                ['titulo'=>'Otras medidas','items'=>$med['otras'] ?? []],
                            ];
                        @endphp
                        @foreach($cards as $card)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm print:shadow-none">
                                <h3 class="text-sm font-semibold text-slate-700 mb-3">{{ $card['titulo'] }}</h3>
                                @if(!empty($card['items']))
                                    <ul class="space-y-2 text-sm text-slate-700">
                                        @foreach($card['items'] as $it)
                                            <li class="flex items-start gap-2">
                                                <span class="mt-[6px] inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                <span>{{ $it }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-slate-500 italic">No requiere</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
        @empty
            <div class="bg-white rounded-xl border border-slate-200 p-6 text-slate-600">
                No hay información para mostrar.
            </div>
        @endforelse
            </div>
        </section>

        {{-- Resumen de Medidas del Puesto (EPP, Capacitación, Señalización, Otras) --}}
        @php
            // Unir medidas de todos los grupos mostrados (solo de riesgos marcados como "Sí")
            $allEpp = collect($grupos ?? [])->flatMap(function($g){ return $g['medidas']['epp'] ?? []; })->unique()->values();
            $allCap = collect($grupos ?? [])->flatMap(function($g){ return $g['medidas']['capacitacion'] ?? []; })->unique()->values();
            $allSen = collect($grupos ?? [])->flatMap(function($g){ return $g['medidas']['senalizacion'] ?? []; })->unique()->values();
            $allOtr = collect($grupos ?? [])->flatMap(function($g){ return $g['medidas']['otras'] ?? []; })->unique()->values();
        @endphp

        <section class="break-inside-avoid bg-white rounded-2xl border border-slate-200 shadow overflow-hidden print:shadow-none">
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold tracking-wide">RESUMEN DE MEDIDAS ASOCIADAS AL PUESTO</h2>
                    <div class="flex items-center gap-2 text-[13px]">
                        <span class="bg-white/20 px-2 py-0.5 rounded-full">EPP: {{ $allEpp->count() }}</span>
                        <span class="bg-white/20 px-2 py-0.5 rounded-full">Capacitaciones: {{ $allCap->count() }}</span>
                        <span class="bg-white/20 px-2 py-0.5 rounded-full">Señalización: {{ $allSen->count() }}</span>
                        <span class="bg-white/20 px-2 py-0.5 rounded-full">Otras: {{ $allOtr->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                    @php
                        $cardsResumen = [
                            ['titulo'=>'EPP','items'=>$allEpp],
                            ['titulo'=>'Capacitación','items'=>$allCap],
                            ['titulo'=>'Señalización','items'=>$allSen],
                            ['titulo'=>'Otras medidas','items'=>$allOtr],
                        ];
                    @endphp
                    @foreach($cardsResumen as $card)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm print:shadow-none">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3">{{ $card['titulo'] }}</h3>
                            @if(($card['items'] ?? collect())->count())
                                <ul class="space-y-2 text-sm text-slate-700">
                                    @foreach($card['items'] as $it)
                                        <li class="flex items-start gap-2">
                                            <span class="mt-[6px] inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>{{ $it }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-slate-500 italic">No requiere</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @else
        {{-- Modo BUSCAR por riesgo/epp/capacitacion/senalizacion/otras -> Tabla de resultados --}}
        <section class="bg-white rounded-2xl border border-slate-200 shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">
                    Resultados ({{ count($resultados ?? []) }})
                </h2>
                @if(!empty($criterioNombre))
                    <p class="text-sm text-slate-600">Criterio: <span class="font-medium">{{ $criterioNombre }}</span></p>
                @endif
            </div>
            <div class="p-4 overflow-x-auto">
                <div class="mb-3 flex justify-end">
                    <a href="{{ route('riesgos.verificacion.export', request()->query()) }}"
                       class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow">
                       Descargar Excel
                    </a>
                    <a href="{{ route('riesgos.verificacion.plan_accion') }}"
                       class="ml-2 px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow">
                       Plan de Acción Control de Riesgos
                    </a>
                </div>
                @if(!empty($resultados))
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-slate-600">
                            <tr class="border-b">
                                <th class="py-2 pr-4">Puesto</th>
                                <th class="py-2 pr-4">Riesgos relacionados</th>
                                <th class="py-2 pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-800">
                            @foreach($resultados as $row)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pr-4">{{ $row['puesto'] }}</td>
                                    <td class="py-2 pr-4">
                                        @php $chips = array_filter(array_map('trim', explode(',', $row['riesgos'] ?? ''))); @endphp
                                        @if($chips)
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($chips as $ch)
                                                    <span class="px-2 py-0.5 rounded-full border border-slate-300 text-[12px] bg-slate-50">{{ $ch }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">
                                        <a href="{{ route('riesgos.verificacion', ['mode'=>'verificacion','puesto'=>$row['id']]) }}"
                                           class="text-blue-600 hover:text-blue-800 underline">
                                           Ver verificación
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-slate-600">Sin resultados.</div>
                @endif
            </div>
        </section>
    @endif
</div>

{{-- Estilos de impresión --}}
<style>
@media print {
    .print\:hidden { display: none !important; }
    .break-inside-avoid { break-inside: avoid; page-break-inside: avoid; }
    body { background: #fff !important; }
}
</style>
@endsection
