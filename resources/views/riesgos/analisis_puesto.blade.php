{{-- resources/views/riesgos/analisis_puesto.blade.php --}}
@extends('layouts.riesgos')

@section('title', 'Analisis Integral de Riesgos')

@section('content')
@php
    $toneByTipo = [
        'MECANICO'          => 'border-stone-200 bg-stone-50',
        'ELECTRICO'         => 'border-sky-200 bg-sky-50',
        'FUEGO Y EXPLOSION' => 'border-amber-200 bg-amber-50',
        'QUIMICOS'          => 'border-emerald-200 bg-emerald-50',
        'ERGONOMICO'        => 'border-indigo-200 bg-indigo-50',
        'PSICOSOCIAL'       => 'border-rose-200 bg-rose-50',
        'FISICO'            => 'border-cyan-200 bg-cyan-50',
        'BIOLOGICO'         => 'border-lime-200 bg-lime-50',
    ];
    $totalSi = $totales['si'] ?? 0;
    $totalNo = $totales['no'] ?? 0;
    $totalRiesgos = max(1, $totalSi + $totalNo);

    $quimicosGrouped = [];
    foreach (($quimicos['rows'] ?? []) as $row) {
        $quimicosGrouped[$row['quimico']->nombre_comercial] = $row;
    }
@endphp

<div class="max-w-7xl mx-auto space-y-6 p-6">

  {{-- Encabezado resumen --}}
  <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="px-6 py-6 md:px-8">
      <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-semibold text-slate-900">Analisis de Riesgos por Puesto</h1>
          <p class="text-sm text-slate-600">Consulta la evaluacion integral de riesgos, medidas y controles asociados al puesto seleccionado.</p>
        </div>
        <form method="GET" action="{{ route('riesgos.analisis') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div>
            <label for="puesto" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Puesto</label>
            <select id="puesto" name="puesto" class="mt-1 w-72 rounded-xl border-slate-300 text-sm focus:border-slate-600 focus:ring-slate-600">
              <option value="">Selecciona un puesto</option>
              @foreach($puestos as $p)
                <option value="{{ $p->id_puesto_trabajo_matriz }}" @selected($puestoId == $p->id_puesto_trabajo_matriz)>
                  {{ $p->puesto_trabajo_matriz }}
                </option>
              @endforeach
            </select>
          </div>
          <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10.5 3.75a6.75 6.75 0 015.306 10.98l4.232 4.232a.75.75 0 11-1.06 1.06l-4.232-4.232A6.75 6.75 0 1110.5 3.75zm0 1.5a5.25 5.25 0 100 10.5 5.25 5.25 0 000-10.5z"/></svg>
            Consultar
          </button>
        </form>
      </div>

      @if($puestoDetalle)
        <div class="mt-8 grid gap-4 md:grid-cols-4">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Puesto</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $puestoDetalle->puesto_trabajo_matriz }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Numero de empleados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $puestoDetalle->num_empleados ?: 'No definido' }}</p>
          </div>
          <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs uppercase tracking-wide text-emerald-600">Riesgos "Si"</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ $totalSi }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Riesgos "No"</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800">{{ $totalNo }}</p>
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
      <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
          <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripcion general</h2>
        </div>
        <div class="px-6 py-5">
          <p class="text-sm leading-relaxed text-slate-700">{{ $puestoDetalle->descripcion_general }}</p>
        </div>
      </section>
    @endif

    @if(!empty($actividades))
      <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
          <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actividades diarias</h2>
        </div>
        <div class="px-6 py-5">
          <div class="flex flex-wrap gap-2">
            @foreach($actividades as $actividad)
              <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-1.5 text-sm font-medium text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4"/><path stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" fill="none"/></svg>
                {{ $actividad }}
              </span>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    {{-- Riesgos agrupados --}}
    <section class="space-y-6">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-slate-50 text-slate-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-900">Riesgos identificados</h2>
      </div>

      @forelse($riesgosPorTipo as $grupo)
        @php
            $tipoKey = strtoupper($grupo['tipo']);
            $contextTone = $toneByTipo[$tipoKey] ?? 'border-slate-200 bg-slate-50';
            $hasQuimico = $tipoKey === 'QUIMICOS';
            $hasFisico  = $tipoKey === 'FISICO';
        @endphp
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">{{ $grupo['tipo'] }}</h3>
              <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-0.5 text-xs text-slate-600">
                {{ count($grupo['riesgos']) }} riesgos evaluados
              </span>
            </div>
          </div>

          <div class="space-y-5 px-6 py-6">
            <div class="grid gap-4 lg:grid-cols-2">
              @forelse($grupo['riesgos'] as $riesgo)
                <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 hover:border-slate-300">
                  <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                      <p class="text-sm font-semibold text-slate-900">{{ $riesgo['nombre'] }}</p>
                      @if($riesgo['observaciones'])
                        <p class="text-sm text-slate-600">{{ $riesgo['observaciones'] }}</p>
                      @endif
                    </div>
                    @if($riesgo['es_si'])
                      <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                        Riesgo presente
                      </span>
                    @elseif($riesgo['es_no'])
                      <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        No aplica
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Sin dato
                      </span>
                    @endif
                  </div>

                  @if($riesgo['es_si'])
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                      @foreach([
                        'epp' => 'EPP requeridos',
                        'capacitacion' => 'Capacitaciones',
                        'senalizacion' => 'Senalizacion',
                        'otras' => 'Otras medidas'
                      ] as $clave => $label)
                        @if(!empty($riesgo['medidas'][$clave]))
                          <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                            <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                            <ul class="mt-1 space-y-1 text-sm text-slate-700">
                              @foreach($riesgo['medidas'][$clave] as $item)
                                <li class="flex items-start gap-2">
                                  <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                  <span>{{ $item }}</span>
                                </li>
                              @endforeach
                            </ul>
                          </div>
                        @endif
                      @endforeach
                    </div>
                  @endif
                </article>
              @empty
                <p class="text-sm text-slate-500">No hay riesgos registrados en esta categoria.</p>
              @endforelse
            </div>

            {{-- Bloques de detalle segun tipo --}}
            @if($hasFisico)
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Detalle de exposiciones fisicas</h4>
                <div class="mt-3 space-y-4">
                  <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Esfuerzo fisico</p>
                    <div class="mt-2 overflow-x-auto">
                      <table class="min-w-full text-sm">
                        <thead class="bg-white text-slate-600">
                          <tr>
                            <th class="p-2 text-left">Tipo</th>
                            <th class="p-2 text-left">Descripcion</th>
                            <th class="p-2 text-left">Equipo</th>
                            <th class="p-2 text-left">Duracion</th>
                            <th class="p-2 text-left">Distancia</th>
                            <th class="p-2 text-left">Frecuencia</th>
                            <th class="p-2 text-left">Peso</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                          @forelse($fisico['cargas'] as $fila)
                            <tr class="odd:bg-white even:bg-slate-100/80">
                              <td class="p-2 font-medium text-slate-800">{{ $fila['tipo'] }}</td>
                              <td class="p-2 text-slate-700">{{ $fila['descripcion'] ?? '-' }}</td>
                              <td class="p-2 text-slate-700">{{ $fila['equipo'] ?? '-' }}</td>
                              <td class="p-2 text-slate-700">{{ $fila['duracion'] ?? '-' }}</td>
                              <td class="p-2 text-slate-700">{{ $fila['distancia'] ?? '-' }}</td>
                              <td class="p-2 text-slate-700">{{ $fila['frecuencia'] ?? '-' }}</td>
                              <td class="p-2 text-slate-700">{{ $fila['peso'] ?? '-' }}</td>
                            </tr>
                          @empty
                            <tr>
                              <td colspan="7" class="p-3 text-center text-sm text-slate-500">Sin registros de esfuerzo fisico.</td>
                            </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="grid gap-3 md:grid-cols-3">
                    @foreach([
                      'visual' => ['titulo' => 'Esfuerzo visual', 'rows' => $fisico['visual'], 'campos' => ['tipo' => 'Tipo', 'tiempo' => 'Exposicion']],
                      'ruido'  => ['titulo' => 'Exposicion a ruido', 'rows' => $fisico['ruido'], 'campos' => ['descripcion' => 'Descripcion', 'duracion' => 'Duracion']],
                      'termico'=> ['titulo' => 'Estres termico', 'rows' => $fisico['termico'], 'campos' => ['descripcion' => 'Descripcion', 'duracion' => 'Duracion']]
                    ] as $info)
                      <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ $info['titulo'] }}</p>
                        <div class="mt-2 space-y-2">
                          @forelse($info['rows'] as $item)
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                              @foreach($info['campos'] as $campo => $label)
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
                                <p class="text-sm font-medium text-slate-800">{{ $item[$campo] ?? '-' }}</p>
                              @endforeach
                            </div>
                          @empty
                            <p class="text-sm text-slate-500">Sin registros.</p>
                          @endforelse
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif

            @if($hasQuimico)
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Detalle de sustancias y exposicion</h4>
                <div class="mt-3 space-y-3">
                  @if(empty($quimicosGrouped))
                    <p class="text-sm text-slate-500">Sin sustancias registradas para este puesto.</p>
                  @else
                    @foreach($quimicosGrouped as $nombre => $row)
                      <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                          <div>
                            <p class="text-sm font-semibold text-emerald-800">{{ $nombre }}</p>
                            @if($row['quimico']->uso)
                              <p class="text-xs text-emerald-700">Uso: {{ $row['quimico']->uso }}</p>
                            @endif
                            @if($row['quimico']->proveedor)
                              <p class="text-xs text-emerald-700">Proveedor: {{ $row['quimico']->proveedor }}</p>
                            @endif
                          </div>
                          <div class="flex flex-wrap gap-2 md:justify-end">
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-0.5 text-xs text-emerald-700">Frecuencia: {{ $row['frecuencia'] ?? '-' }}</span>
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-0.5 text-xs text-emerald-700">Duracion: {{ $row['duracion_exposicion'] ?? '-' }}</span>
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-0.5 text-xs text-emerald-700">Salud: {{ $row['salud'] ?? '-' }}</span>
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-0.5 text-xs text-emerald-700">Inflamabilidad: {{ $row['inflamabilidad'] ?? '-' }}</span>
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-0.5 text-xs text-emerald-700">Reactividad: {{ $row['reactividad'] ?? '-' }}</span>
                          </div>
                        </div>

                        @if(!empty($row['exposicion']))
                          <p class="mt-3 text-sm text-emerald-800"><span class="font-semibold">Tipo de exposicion:</span> {{ $row['exposicion'] }}</p>
                        @endif

                        <div class="mt-3 grid gap-2 text-xs text-emerald-800 md:grid-cols-5">
                          <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-center">
                            Ninguno<br><span class="text-lg font-semibold">{!! ($row['ninguno'] ?? 0) ? '&#10003;' : '&ndash;' !!}</span>
                          </div>
                          <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-center">
                            Particulas<br><span class="text-lg font-semibold">{!! ($row['particulas_polvo'] ?? 0) ? '&#10003;' : '&ndash;' !!}</span>
                          </div>
                          <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-center">
                            Corrosivas<br><span class="text-lg font-semibold">{!! ($row['sustancias_corrosivas'] ?? 0) ? '&#10003;' : '&ndash;' !!}</span>
                          </div>
                          <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-center">
                            Toxicas<br><span class="text-lg font-semibold">{!! ($row['sustancias_toxicas'] ?? 0) ? '&#10003;' : '&ndash;' !!}</span>
                          </div>
                          <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-center">
                            Irritantes<br><span class="text-lg font-semibold">{!! ($row['sustancias_irritantes'] ?? 0) ? '&#10003;' : '&ndash;' !!}</span>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  @endif
                </div>
              </div>
            @endif
          </div>
        </div>
      @empty
        <div class="rounded-3xl border border-slate-200 bg-white px-5 py-6 text-sm text-slate-500">No se encontraron riesgos para este puesto.</div>
      @endforelse
    </section>

    {{-- Resumen de medidas --}}
    <section class="space-y-4">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-slate-50 text-slate-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 20.25c4.556 0 8.25-3.694 8.25-8.25S16.556 3.75 12 3.75 3.75 7.444 3.75 12s3.694 8.25 8.25 8.25z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l1.5 1.5L15 9"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-900">Medidas requeridas por el puesto</h2>
      </div>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
          'epp' => 'Equipo de proteccion personal',
          'capacitacion' => 'Capacitaciones',
          'senalizacion' => 'Senalizacion',
          'otras' => 'Otras medidas'
        ] as $clave => $titulo)
          <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-800">{{ $titulo }}</p>
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
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-slate-50 text-slate-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <h2 class="text-xl font-semibold text-slate-900">Medidas de control</h2>
      </div>
      @if($medidasControl)
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div class="grid gap-4 border-b border-slate-200 px-6 py-4 md:grid-cols-3">
            <div>
              <p ina="text-xs uppercase tracking-wide text-slate-500">Probabilidad</p>
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