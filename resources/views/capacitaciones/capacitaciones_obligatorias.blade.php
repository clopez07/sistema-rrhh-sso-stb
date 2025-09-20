@extends('layouts.capacitacion')

@section('title', 'Capacitaciones obligatorias por puesto')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Capacitaciones obligatorias</h1>

    <form method="GET" action="{{ route('capacitaciones.capacitaciones.obligatorias') }}" class="grid gap-4 grid-cols-1 md:grid-cols-4 items-end bg-white p-4 rounded-xl shadow">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Puesto (Matriz)</label>
            <select name="ptm" class="w-full rounded-lg border-gray-300" required>
                <option value="" hidden>&mdash; Selecciona un puesto matriz &mdash;</option>
                @foreach ($ptms as $p)
                    <option value="{{ $p->id_puesto_trabajo_matriz }}" {{ $ptmId == $p->id_puesto_trabajo_matriz ? 'selected' : '' }}>
                        {{ $p->puesto_trabajo_matriz }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">A&ntilde;o</label>
            <select name="anio" class="w-full rounded-lg border-gray-300" required>
                @foreach ($years as $y)
                    <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3">
            <button class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Buscar
            </button>
            @if($ptmId)
            <a href="{{ route('capacitaciones.capacitaciones.obligatorias') }}" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                Limpiar
            </a>
            @endif
        </div>
    </form>

    @if($ptmId)
        <div class="mt-6 space-y-6">
            @if(!empty($estadoPorCap))
                <div class="bg-white rounded-xl shadow overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Capacitaci&oacute;n</th>
                                <th class="px-4 py-3 text-left font-semibold">Recibieron</th>
                                <th class="px-4 py-3 text-left font-semibold">Pendientes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($estadoPorCap as $infoCap)
                                @php
                                    $recibidasCap = $infoCap['recibidas'] ?? [];
                                    $pendientesCap = $infoCap['pendientes'] ?? [];
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 font-medium">{{ $infoCap['cap']->capacitacion ?? 'Capacitaci&oacute;n' }}</td>
                                    <td class="px-4 py-3">
                                        @if(empty($recibidasCap))
                                            <span class="text-xs text-gray-500">Sin registros</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($recibidasCap as $registro)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 px-2 py-0.5">
                                                        {{ $registro['empleado']->nombre_completo }}
                                                        @if(!empty($registro['fecha']))
                                                            <span class="text-gray-500 text-[10px]">({{ $registro['fecha'] }})</span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(empty($pendientesCap))
                                            <span class="text-xs text-gray-500">Sin pendientes</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($pendientesCap as $registro)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 text-red-700 px-2 py-0.5">
                                                        {{ $registro['empleado']->nombre_completo }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
