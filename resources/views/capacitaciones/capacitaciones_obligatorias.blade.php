
@extends('layouts.capacitacion')

@section('title', 'Capacitaciones obligatorias por puesto (comparación)')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Capacitaciones obligatorias</h1>

    <form method="GET" action="{{ route('capacitaciones.capacitaciones.obligatorias') }}" class="grid gap-4 grid-cols-1 md:grid-cols-4 items-end bg-white p-4 rounded-xl shadow">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Puesto (Matriz)</label>
            <select name="ptm" class="w-full rounded-lg border-gray-300" required>
                <option value="" hidden>— Selecciona un puesto matriz —</option>
                @foreach ($ptms as $p)
                    <option value="{{ $p->id_puesto_trabajo_matriz }}" {{ $ptmId == $p->id_puesto_trabajo_matriz ? 'selected' : '' }}>
                        {{ $p->puesto_trabajo_matriz }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
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

    {{-- Paso 2: seleccionar empleado equivalente --}}
    @if($ptmId)
        <div class="mt-6 bg-white rounded-xl shadow">
            <div class="px-4 py-3 border-b font-medium">
                Empleados de puestos equivalentes
            </div>

            @if($empleados->isEmpty())
                <div class="p-4 text-sm text-gray-600">
                    No hay <em>comparación de puestos</em> configurada o no hay empleados activos para este puesto matriz.
                </div>
            @else
                <form method="GET" action="{{ route('capacitaciones.capacitaciones.obligatorias') }}" class="p-4">
                    <input type="hidden" name="ptm" value="{{ $ptmId }}">
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empleado</label>
                    <div class="flex gap-3 items-end">
                        <select name="empleado" class="w-full md:w-1/2 rounded-lg border-gray-300" required>
                            <option value="" hidden>— Selecciona un empleado —</option>
                            @foreach ($empleados as $e)
                                <option value="{{ $e->id_empleado }}" {{ $empleadoId == $e->id_empleado ? 'selected' : '' }}>
                                    {{ $e->nombre_completo }} @if($e->codigo_empleado) — {{ $e->codigo_empleado }} @endif
                                </option>
                            @endforeach
                        </select>
                        <button class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                            Ver capacitaciones
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    {{-- Paso 3: matriz del empleado vs capacitaciones obligatorias --}}
    @if($ptmId && $empleadoId)
        <div class="mt-6 space-y-4">
            @if($capsObligatorias->isEmpty())
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    No hay capacitaciones obligatorias configuradas para este puesto matriz.
                </div>
            @else
                <div class="text-sm text-gray-600">
                    <strong>{{ $capsObligatorias->count() }}</strong> capacitaciones obligatorias. Año: <strong>{{ $anio }}</strong>.
                </div>

                <div class="overflow-x-auto bg-white rounded-xl shadow">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Capacitación</th>
                                <th class="px-4 py-3 text-left font-semibold">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($matriz as $row)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $row['cap']->capacitacion }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($row['estado'] === 'OK')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-800">
                                                Recibida @if($row['fecha']) ({{ $row['fecha'] }}) @endif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-800">
                                                Pendiente
                                            </span>
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
