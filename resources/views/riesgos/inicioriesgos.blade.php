@extends('layouts.riesgos')

@section('title', 'MATRIZ DE RIESGOS')

@section('content')

    <!-- Breadcrumb -->
    <nav class="flex px-5 py-3 text-gray-700 bg-blue-100 rounded-lg" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <!-- Ícono de inicio -->
            <svg class="w-4 h-4 mr-2 text-black" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a1 1 0 00-.707.293l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-3h2v3a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7A1 1 0 0010 2z" />
            </svg>
            Inicio
        </a>
        </li>
        <!-- Separador con flechita -->
        <li>
        <div class="flex items-center">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-black">Consultas</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Consultas de Riesgos</h1>
            <p class="mt-1 text-sm text-gray-600">Busca por puesto de trabajo o por riesgo y visualiza los detalles.</p>
        </div>
    </div>

    {{-- Barra de búsqueda --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Card: Buscar por Puesto --}}
        <div class="bg-white rounded-2xl shadow p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Por Puesto de Trabajo</h2>
            </div>

            <form action="{{ route('Riesgos') }}" method="GET" class="mt-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Puesto</label>
                    <select name="puesto_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione…</option>
                        @foreach($puestos as $p)
                            <option value="{{ $p->id }}" @selected(request('puesto_id') == $p->id)>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Buscar
                    </button>
                    <a href="{{ route('Riesgos') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Limpiar</a>
                </div>
            </form>

            {{-- Resultados por Puesto --}}
            @if(request('puesto_id'))
                <div class="mt-6">
                    <h3 class="text-sm text-gray-500 mb-2">
                        Resultados para: <span class="font-medium text-gray-900">{{ $puestoSel }}</span>
                        <span class="ml-2 inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $byPuesto->count() }} riesgos</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-gray-700">
                                    <th class="px-3 py-2 text-left font-semibold">Tipo</th>
                                    <th class="px-3 py-2 text-left font-semibold">Riesgo</th>
                                    <th class="px-3 py-2 text-left font-semibold">Valor</th>
                                    <th class="px-3 py-2 text-left font-semibold">Prob.</th>
                                    <th class="px-3 py-2 text-left font-semibold">Cons.</th>
                                    <th class="px-3 py-2 text-left font-semibold">Nivel</th>
                                    <th class="px-3 py-2 text-left font-semibold">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @php
                                  $nivelClass = function($s) {
                                      $k = strtoupper($s ?? '');
                                      return match(true) {
                                          str_contains($k,'BAJO')        => 'bg-green-100 text-green-800',
                                          str_contains($k,'MED') ||
                                          str_contains($k,'MOD')          => 'bg-yellow-100 text-yellow-800',
                                          str_contains($k,'ALTO')         => 'bg-orange-100 text-orange-800',
                                          str_contains($k,'MUY') ||
                                          str_contains($k,'CRIT')         => 'bg-red-100 text-red-800',
                                          default                         => 'bg-gray-100 text-gray-700',
                                      };
                                  };
                                  $valorClass = fn($v) => (strtoupper($v ?? '') === 'SI' || strtoupper($v ?? '') === 'SÍ')
                                        ? 'bg-blue-100 text-blue-800'
                                        : ((strtoupper($v ?? '') === 'NO') ? 'bg-gray-100 text-gray-700' : 'bg-purple-100 text-purple-800');
                                @endphp
                                @forelse($byPuesto as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600">{{ $row->tipo_riesgo ?? '—' }}</td>
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $row->nombre_riesgo }}</td>
                                        <td class="px-3 py-2">
                                            @if($row->valor !== null)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $valorClass($row->valor) }}">
                                                {{ $row->valor }}
                                            </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $row->probabilidad ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $row->consecuencia ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            @if($row->nivel_riesgo)
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $nivelClass($row->nivel_riesgo) }}">
                                                    {{ $row->nivel_riesgo }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 max-w-[24rem]">
                                            <div class="truncate" title="{{ $row->observaciones }}">{{ $row->observaciones ?? '—' }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500">Sin datos para este puesto.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Card: Buscar por Riesgo --}}
        <div class="bg-white rounded-2xl shadow p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Por Riesgo</h2>
            </div>

            <form action="{{ route('Riesgos') }}" method="GET" class="mt-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Riesgo</label>
                    <select name="riesgo_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione…</option>
                        @foreach($riesgos as $r)
                            <option value="{{ $r->id }}" @selected(request('riesgo_id') == $r->id)>{{ $r->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Buscar
                    </button>
                    <a href="{{ route('Riesgos') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Limpiar</a>
                </div>
            </form>

            {{-- Resultados por Riesgo --}}
            @if(request('riesgo_id'))
                <div class="mt-6">
                    <h3 class="text-sm text-gray-500 mb-2">
                        Resultados para: <span class="font-medium text-gray-900">{{ $riesgoSel }}</span>
                        <span class="ml-2 inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $byRiesgo->count() }} puestos</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-gray-700">
                                    <th class="px-3 py-2 text-left font-semibold">Puesto</th>
                                    <th class="px-3 py-2 text-left font-semibold">Valor</th>
                                    <th class="px-3 py-2 text-left font-semibold">Prob.</th>
                                    <th class="px-3 py-2 text-left font-semibold">Cons.</th>
                                    <th class="px-3 py-2 text-left font-semibold">Nivel</th>
                                    <th class="px-3 py-2 text-left font-semibold">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @php
                                  $nivelClass = function($s) {
                                      $k = strtoupper($s ?? '');
                                      return match(true) {
                                          str_contains($k,'BAJO')        => 'bg-green-100 text-green-800',
                                          str_contains($k,'MED') ||
                                          str_contains($k,'MOD')          => 'bg-yellow-100 text-yellow-800',
                                          str_contains($k,'ALTO')         => 'bg-orange-100 text-orange-800',
                                          str_contains($k,'MUY') ||
                                          str_contains($k,'CRIT')         => 'bg-red-100 text-red-800',
                                          default                         => 'bg-gray-100 text-gray-700',
                                      };
                                  };
                                  $valorClass = fn($v) => (strtoupper($v ?? '') === 'SI' || strtoupper($v ?? '') === 'SÍ')
                                        ? 'bg-blue-100 text-blue-800'
                                        : ((strtoupper($v ?? '') === 'NO') ? 'bg-gray-100 text-gray-700' : 'bg-purple-100 text-purple-800');
                                @endphp

                                @forelse($byRiesgo as $row)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $row->puesto_trabajo_matriz }}</td>
                                        <td class="px-3 py-2">
                                            @if($row->valor !== null)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $valorClass($row->valor) }}">
                                                {{ $row->valor }}
                                            </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $row->probabilidad ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $row->consecuencia ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            @if($row->nivel_riesgo)
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $nivelClass($row->nivel_riesgo) }}">
                                                    {{ $row->nivel_riesgo }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 max-w-[24rem]">
                                            <div class="truncate" title="{{ $row->observaciones }}">{{ $row->observaciones ?? '—' }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">Sin datos para este riesgo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
