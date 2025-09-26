{{-- resources/views/riesgos/analisis_puesto.blade.php --}}
@extends('layouts.riesgos')

@section('title', 'Analisis Integral de Riesgos')

@section('content')
@php
    $colorByTipo = [
        'MECANICO'          => 'from-orange-500 to-yellow-500',
        'ELECTRICO'         => 'from-sky-500 to-blue-600',
        'FUEGO Y EXPLOSION' => 'from-amber-500 to-red-500',
        'QUIMICOS'          => 'from-emerald-500 to-teal-500',
        'ERGONOMICO'        => 'from-purple-500 to-indigo-500',
        'PSICOSOCIAL'       => 'from-pink-500 to-rose-500',
        'FISICO'            => 'from-cyan-500 to-sky-500',
        'BIOLOGICO'         => 'from-lime-500 to-green-500',
    ];
    $totalSi = $totales['si'] ?? 0;
    $totalNo = $totales['no'] ?? 0;
    $totalRiesgos = $totalSi + $totalNo;
@endphp

<div class="max-w-7xl mx-auto space-y-6 p-6">

  <section class="rounded-3xl bg-gradient-to-r from-sky-700 to-indigo-700 text-white shadow-lg">
    <div class="p-6 md:p-8">
      <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">Analisis de Riesgos por Puesto</h1>
          <p class="text-sm text-white/80">Visualiza de forma integral los riesgos, medidas y controles asignados a cada puesto de trabajo.</p>
        </div>
        <form method="GET" action="{{ route('riesgos.analisis') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div>
            <label for="puesto" class="block text-xs uppercase tracking-wide text-white/70">Puesto</label>
            <select id="puesto" name="puesto" class="mt-1 w-72 rounded-xl border-0 bg-white/15 px-3 py-2 text-sm text-white placeholder-white/60 focus:border-white focus:ring-white">
              <option value="">Selecciona un puesto</option>
              @foreach($puestos as $p)
                <option value="{{ $p->id_puesto_trabajo_matriz }}" @selected($puestoId == $p->id_puesto_trabajo_matriz)>
                  {{ $p->puesto_trabajo_matriz }}
                </option>
              @endforeach
            </select>
          </div>
          <button class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10.5 3.75a6.75 6.75 0 015.306 10.98l4.232 4.232a.75.75 0 11-1.06 1.06l-4.232-4.232A6.75 6.75 0 1110.5 3.75zm0 1.5a5.25 5.25 0 100 10.5 5.25 5.25 0 000-10.5z"/></svg>
            Consultar
          </button>
        </form>
      </div>

      @if($puestoDetalle)
        <div class="mt-8 grid gap-4 md:grid-cols-4">
          <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
            <p class="text-xs uppercase tracking-wide text-white/70">Puesto</p>
            <p class="mt-1 text-sm font-semibold">{{ $puestoDetalle->puesto_trabajo_matriz }}</p>
          </div>
          <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
            <p class="text-xs uppercase tracking-wide text-white/70">Empleados</p>
            <p class="mt-1 text-2xl font-bold">{{ $puestoDetalle->num_empleados ?: 'No definido' }}</p>
          </div>
          <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
            <p class="text-xs uppercase tracking-wide text-white/70">Riesgos en Si</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-2xl font-bold">{{ $totalSi }}</span>
              <span class="text-xs text-white/70">de {{ $totalRiesgos }}</span>
            </div>
          </div>
          <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
            <p class="text-xs uppercase tracking-wide text-white/70">Riesgos en No</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-2xl font-bold">{{ $totalNo }}</span>
              <span class="text-xs text-white/70">evaluaciones negativas</span>
            </div>
          </div>
        </div>
      @endif
    </div>
  </section>

  @if(!$puestoId)
    <div class="rounded-3xl border border-dashed border-amber-300 bg-amber-50 px-5 py-6 text-amber-700">
      Selecciona un puesto para generar el informe.
    </div>
  @else

    @if($puestoDetalle && $puestoDetalle->descripcion_general)
      <section class="rounded-3xl border border-slate-200 bg-white/80 shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Descripcion general del puesto</h2>
        </div>
        <div class="px-6 py-5">
          <p class="text-sm leading-relaxed text-slate-700">{{ $puestoDetalle->descripcion_general }}</p>
        </div>
      </section>
    @endif

    @if(!empty($actividades))
      <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center gap-2">
          <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4.5h18m-16.5 0v14a1.5 1.5 0 001.5 1.5h13.5m-7.5-10.5H21m-9 4.5H21"/></svg>
          </span>
          <h2 class="text-lg font-semibold text-slate-800">Actividades diarias del puesto</h2>
        </div>
        <div class="px-6 py-5">
          <div class="flex flex-wrap gap-2">
            @foreach($actividades as $actividad)
              <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-medium text-emerald-700 border border-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4"/><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ $actividad }}
              </span>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    {{-- Riesgos por categoria --}}
    <section class="space-y-5">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-800">Riesgos identificados</h2>
      </div>

      @forelse($riesgosPorTipo as $grupo)
        @php
            $tipoKey = strtoupper($grupo['tipo']);
            $gradient = $colorByTipo[$tipoKey] ?? 'from-slate-500 to-slate-700';
        @endphp
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r {{ $gradient }} px-6 py-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-white/95">{{ $grupo['tipo'] }}</h3>
          </div>
          <div class="space-y-4 px-6 py-5">
            @forelse($grupo['riesgos'] as $riesgo)
              <article class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 transition hover:border-sky-300 hover:bg-sky-50/60">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <span class="text-base font-semibold text-slate-900">{{ $riesgo['nombre'] }}</span>
                      @if($riesgo['observaciones'])
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700">Obs</span>
                      @endif
                    </div>
                    @if($riesgo['observaciones'])
                      <p class="text-sm text-slate-600">{{ $riesgo['observaciones'] }}</p>
                    @endif

                    @if($riesgo['es_si'])
                      <div class="grid gap-4 md:grid-cols-2">
                        @foreach([
                          'epp' => 'EPP requeridos',
                          'capacitacion' => 'Capacitaciones',
                          'senalizacion' => 'Senalizacion',
                          'otras' => 'Otras medidas'
                        ] as $clave => $label)
                          @if(!empty($riesgo['medidas'][$clave]))
                            <div class="rounded-2xl bg-white px-4 py-3 border border-slate-200">
                              <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                              <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                @foreach($riesgo['medidas'][$clave] as $item)
                                  <li class="flex items-start gap-2">
                                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                    <span>{{ $item }}</span>
                                  </li>
                                @endforeach
                              </ul>
                            </div>
                          @endif
                        @endforeach
                      </div>
                    @endif
                  </div>

                  <div class="flex items-center md:flex-col md:items-end md:justify-start">
                    @if($riesgo['es_si'])
                      <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                        Si
                      </span>
                    @elseif($riesgo['es_no'])
                      <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        No
                      </span>
                    @else
                      <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Sin dato
                      </span>
                    @endif
                  </div>
                </div>
              </article>
            @empty
              <p class="text-sm text-slate-500">No hay riesgos registrados en esta categoria.</p>
            @endforelse
          </div>
        </div>
      @empty
        <div class="rounded-3xl border border-slate-200 bg-white px-5 py-6 text-sm text-slate-500">No se encontraron riesgos para este puesto.</div>
      @endforelse
    </section>

    {{-- Detalle fisico --}}
    <section class="space-y-4">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 4.5l-.867 1.5M15 4.5l.867 1.5M4.5 9h15"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-800">Detalle de riesgo fisico</h2>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-cyan-500 to-blue-500 px-6 py-3 text-white">
          <h3 class="text-sm font-semibold uppercase tracking-wide">Esfuerzo fisico</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-900 text-white">
              <tr>
                <th class="p-3 text-left">Tipo</th>
                <th class="p-3 text-left">Descripcion</th>
                <th class="p-3 text-left">Equipo</th>
                <th class="p-3 text-left">Duracion</th>
                <th class="p-3 text-left">Distancia</th>
                <th class="p-3 text-left">Frecuencia</th>
                <th class="p-3 text-left">Peso</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse($fisico['cargas'] as $fila)
                <tr class="odd:bg-white even:bg-slate-50">
                  <td class="p-3 font-semibold text-slate-900">{{ $fila['tipo'] }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['descripcion'] ?? '-' }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['equipo'] ?? '-' }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['duracion'] ?? '-' }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['distancia'] ?? '-' }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['frecuencia'] ?? '-' }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['peso'] ?? '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="p-5 text-center text-sm text-slate-500">Sin registros de esfuerzo fisico.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        @foreach([
          'visual' => ['titulo' => 'Esfuerzo visual', 'color' => 'from-purple-500 to-indigo-500', 'rows' => $fisico['visual'], 'campos' => ['tipo' => 'Tipo', 'tiempo' => 'Exposicion']],
          'ruido'  => ['titulo' => 'Exposicion a ruido', 'color' => 'from-orange-500 to-red-500', 'rows' => $fisico['ruido'], 'campos' => ['descripcion' => 'Descripcion', 'duracion' => 'Duracion']],
          'termico'=> ['titulo' => 'Estres termico', 'color' => 'from-emerald-500 to-teal-500', 'rows' => $fisico['termico'], 'campos' => ['descripcion' => 'Descripcion', 'duracion' => 'Duracion']]
        ] as $info)
          <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-r {{ $info['color'] }} px-5 py-3 text-white">
              <h3 class="text-sm font-semibold uppercase tracking-wide">{{ $info['titulo'] }}</h3>
            </div>
            <div class="p-5 space-y-3">
              @forelse($info['rows'] as $item)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                  @foreach($info['campos'] as $campo => $label)
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mb-2 text-sm font-medium text-slate-800">{{ $item[$campo] ?? '-' }}</p>
                  @endforeach
                </div>
              @empty
                <p class="text-sm text-slate-500">Sin registros.</p>
              @endforelse
            </div>
          </div>
        @endforeach
      </div>
    </section>

    {{-- Detalle quimico --}}
    <section class="space-y-4">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v18M5 9h14"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-800">Detalle de riesgo quimico</h2>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-rose-500 to-fuchsia-600 px-6 py-3 text-white">
          <h3 class="text-sm font-semibold uppercase tracking-wide">Sustancias y exposicion</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-900 text-white">
              <tr>
                <th class="p-3 text-left">Quimico</th>
                <th class="p-3 text-left">Uso</th>
                <th class="p-3 text-left">Exposicion</th>
                <th class="p-3 text-left">Frecuencia</th>
                <th class="p-3 text-left">Duracion</th>
                <th class="p-3 text-center">Ninguno</th>
                <th class="p-3 text-center">Particulas</th>
                <th class="p-3 text-center">Corrosivas</th>
                <th class="p-3 text-center">Toxicas</th>
                <th class="p-3 text-center">Irritantes</th>
                <th class="p-3 text-center">Salud</th>
                <th class="p-3 text-center">Inflamabilidad</th>
                <th class="p-3 text-center">Reactividad</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse($quimicos['rows'] as $fila)
                <tr class="odd:bg-white even:bg-slate-50">
                  <td class="p-3 text-slate-900">
                    <p class="font-semibold">{{ $fila['quimico']->nombre_comercial }}</p>
                    @if($fila['quimico']->proveedor)
                      <p class="text-xs text-slate-500">Proveedor: {{ $fila['quimico']->proveedor }}</p>
                    @endif
                  </td>
                  <td class="p-3 text-slate-700">{{ $fila['quimico']->uso ?? '-' }}</td>
                  <td class="p-3 text-slate-700">{{ $fila['exposicion'] ?? '-' }}</td>
                  <td class="p-3 text-center text-slate-700">{{ $fila['frecuencia'] ?? '-' }}</td>
                  <td class="p-3 text-center text-slate-700">{{ $fila['duracion_exposicion'] ?? '-' }}</td>
                  <td class="p-3 text-center">{!! ($fila['ninguno'] ?? 0) ? '&#10003;' : '&ndash;' !!}</td>
                  <td class="p-3 text-center">{!! ($fila['particulas_polvo'] ?? 0) ? '&#10003;' : '&ndash;' !!}</td>
                  <td class="p-3 text-center">{!! ($fila['sustancias_corrosivas'] ?? 0) ? '&#10003;' : '&ndash;' !!}</td>
                  <td class="p-3 text-center">{!! ($fila['sustancias_toxicas'] ?? 0) ? '&#10003;' : '&ndash;' !!}</td>
                  <td class="p-3 text-center">{!! ($fila['sustancias_irritantes'] ?? 0) ? '&#10003;' : '&ndash;' !!}</td>
                  <td class="p-3 text-center text-slate-700">{{ $fila['salud'] ?? '-' }}</td>
                  <td class="p-3 text-center text-slate-700">{{ $fila['inflamabilidad'] ?? '-' }}</td>
                  <td class="p-3 text-center text-slate-700">{{ $fila['reactividad'] ?? '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="13" class="p-5 text-center text-sm text-slate-500">Sin quimicos registrados para este puesto.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>

    {{-- Resumen de medidas --}}
    <section class="space-y-4">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 20.25c4.556 0 8.25-3.694 8.25-8.25S16.556 3.75 12 3.75 3.75 7.444 3.75 12s3.694 8.25 8.25 8.25z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l1.5 1.5L15 9"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-800">Resumen de medidas requeridas</h2>
      </div>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
          'epp' => ['titulo' => 'Equipo de Proteccion Personal', 'color' => 'bg-sky-50 border-sky-200 text-sky-700'],
          'capacitacion' => ['titulo' => 'Capacitaciones', 'color' => 'bg-purple-50 border-purple-200 text-purple-700'],
          'senalizacion' => ['titulo' => 'Senalizacion', 'color' => 'bg-amber-50 border-amber-200 text-amber-700'],
          'otras' => ['titulo' => 'Otras medidas', 'color' => 'bg-emerald-50 border-emerald-200 text-emerald-700']
        ] as $clave => $meta)
          <div class="rounded-3xl border {{ $meta['color'] }} p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-800">{{ $meta['titulo'] }}</p>
            @if(!empty($resumenMedidas[$clave]))
              <ul class="mt-3 space-y-1 text-sm text-slate-700">
                @foreach($resumenMedidas[$clave] as $item)
                  <li class="flex items-start gap-2">
                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    <span>{{ $item }}</span>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="mt-3 text-sm italic text-slate-500">No registra requerimientos.</p>
            @endif
          </div>
        @endforeach
      </div>
    </section>

    {{-- Medidas de control --}}
    <section class="space-y-4">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-800">Medidas de control del puesto</h2>
      </div>
      @if($medidasControl)
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div class="grid gap-4 border-b border-slate-200 px-6 py-4 md:grid-cols-3">
            <div>
              <p class="text-xs uppercase tracking-wide text-slate-500">Probabilidad</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ $medidasControl->probabilidad ?? 'No definida' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-slate-500">Consecuencia</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ $medidasControl->consecuencia ?? 'No definida' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-slate-500">Nivel de riesgo</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ $medidasControl->nivel_riesgo ?? 'No definido' }}</p>
            </div>
          </div>
          <div class="grid gap-4 px-6 py-5 md:grid-cols-2">
            @foreach([
              'eliminacion' => 'Eliminacion',
              'sustitucion' => 'Sustitucion',
              'aislar' => 'Aislar',
              'control_ingenieria' => 'Control de ingenieria',
              'control_administrativo' => 'Control administrativo'
            ] as $campo => $label)
              <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-sm text-slate-700">{{ $medidasControl->{$campo} ?: 'Sin registrar' }}</p>
              </div>
            @endforeach
          </div>
        </div>
      @else
        <div class="rounded-3xl border border-slate-200 bg-white px-5 py-6 text-sm text-slate-500">No hay medidas de control registradas para este puesto.</div>
      @endif
    </section>

  @endif
</div>
@endsection