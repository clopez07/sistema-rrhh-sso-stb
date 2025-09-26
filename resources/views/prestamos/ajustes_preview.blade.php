@extends('layouts.prestamos')

@section('title', 'Previsualizaci�n de ajustes')

@section('content')
    <nav class="flex px-5 py-3 text-gray-700 bg-blue-100 rounded-lg" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('cuotas') }}" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
                    <svg class="w-4 h-4 mr-2 text-black" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 00-.707.293l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-3h2v3a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7A1 1 0 0010 2z" />
                    </svg>
                    Historial de cuotas
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium text-black">Previsualizaci�n de ajustes</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="mt-5 bg-white border border-gray-200 rounded-lg shadow-sm p-4">
        <h1 class="text-xl font-semibold text-gray-800">Previsualizaci�n de ajustes</h1>
        <p class="mt-1 text-sm text-gray-600">
            Rango analizado: <strong>{{ $plan['inicio'] }}</strong> al <strong>{{ $plan['fin'] }}</strong> &middot;
            Hoja: <strong>{{ $plan['sheet'] }}</strong>
        </p>
        <p class="mt-1 text-xs text-gray-500">Revisa la informaci�n y, si todo luce correcto, confirma los cambios.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-3 mt-5">
        <div class="p-4 bg-white border border-gray-200 rounded shadow-sm">
            <p class="text-xs uppercase text-gray-500">Pagos completos</p>
            <p class="text-lg font-semibold text-emerald-600">{{ $plan['counts']['pagos_completos'] ?? 0 }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded shadow-sm">
            <p class="text-xs uppercase text-gray-500">Pagos parciales</p>
            <p class="text-lg font-semibold text-amber-600">{{ $plan['counts']['pagos_parciales'] ?? 0 }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded shadow-sm">
            <p class="text-xs uppercase text-gray-500">Sin deducci�n</p>
            <p class="text-lg font-semibold text-red-600">{{ $plan['counts']['sin_pago'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <form action="{{ route('prestamos.ajustes.commit') }}" method="POST" class="flex items-center gap-2">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                Aplicar ajustes
            </button>
        </form>
        <a href="{{ route('cuotas') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar y volver</a>
    </div>

    <div class="mt-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-3">Detalle de acciones</h2>
        <div class="relative overflow-x-auto shadow-sm border border-gray-200 rounded-lg">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Empleado</th>
                        <th class="px-4 py-3">Pr�stamo</th>
                        <th class="px-4 py-3">Cuota</th>
                        <th class="px-4 py-3">Acci�n</th>
                        <th class="px-4 py-3 text-right">Monto cuota</th>
                        <th class="px-4 py-3 text-right">Pagado</th>
                        <th class="px-4 py-3 text-right">Diferencia</th>
                        <th class="px-4 py-3">Descripci�n</th>
                        <th class="px-4 py-3">Impacto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plan['actions'] as $action)
                        @php
                            $hasUpdates = !empty($action['updates']);
                            $rowColor = $hasUpdates ? 'bg-white' : 'bg-gray-50';
                            $expected = $action['expected'] !== null ? number_format($action['expected'], 2, '.', ',') : '�';
                            $paid = $action['pagado'] !== null ? number_format($action['pagado'], 2, '.', ',') : '�';
                            $diff = $action['diferencia'] !== null ? number_format($action['diferencia'], 2, '.', ',') : '�';
                            $cuotaInfo = $action['cuota'] ? 'No. ' . $action['cuota']['num'] . ' � ' . $action['cuota']['fecha'] : '�';
                            $prestamoInfo = $action['prestamo'] ? ($action['prestamo']['numero'] ?? ('ID ' . $action['prestamo']['id'])) : '�';
                        @endphp
                        <tr class="{{ $rowColor }} border-b border-gray-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $action['employee']['nombre'] ?? '�' }}</div>
                                <div class="text-xs text-gray-500">{{ $action['employee']['codigo'] ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $prestamoInfo }}</td>
                            <td class="px-4 py-3">{{ $cuotaInfo }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $hasUpdates ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' }}">
                                    {{ ucfirst(str_replace('_', ' ', $action['type'])) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $expected }}</td>
                            <td class="px-4 py-3 text-right">{{ $paid }}</td>
                            <td class="px-4 py-3 text-right">{{ $diff }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $action['notes'] }}</td>
                            <td class="px-4 py-3">
                                @if ($hasUpdates)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Se aplicar�</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600">Informativo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-4 text-center text-sm text-gray-500">No se detectaron movimientos para este archivo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        @if (!empty($plan['sin_empleado_detalle']))
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700">C�digos sin empleado ({{ count($plan['sin_empleado_detalle']) }})</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($plan['sin_empleado_detalle'] as $item)
                        <li>
                            Fila {{ $item['fila'] }} � C�digo {{ $item['codigo'] }} � Monto {{ number_format($item['deduccion'], 2, '.', ',') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($plan['sin_coincidencia_detalle']))
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700">Deducciones sin coincidencia ({{ count($plan['sin_coincidencia_detalle']) }})</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($plan['sin_coincidencia_detalle'] as $item)
                        <li>
                            Fila {{ $item['fila'] }} � C�digo {{ $item['codigo'] }} � Monto {{ number_format($item['deduccion'], 2, '.', ',') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if (!empty($plan['ejemplos']))
        <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700">Muestras del Excel</h3>
            <ul class="mt-2 space-y-1 text-xs text-gray-500">
                @foreach ($plan['ejemplos'] as $ejemplo)
                    <li>{{ $ejemplo }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
