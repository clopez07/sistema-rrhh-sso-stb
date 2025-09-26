@extends('layouts.prestamos')

@section('title', 'Detalle de Amortización - Préstamo #' . $prestamo->num_prestamo)

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-light text-gray-900">Amortización</h1>
            <p class="text-gray-500">Préstamo #{{ $prestamo->num_prestamo }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('prestamos.amortizacion') }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                ← Volver
            </a>
            <a href="{{ route('prestamos.exportar.amortizacion', $prestamo->id_prestamo) }}" 
               class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 transition-colors duration-200">
                Exportar Excel
            </a>
        </div>
    </div>

    <!-- Información Principal -->
    <div class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Empleado -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Empleado</div>
                <div class="text-lg font-medium text-gray-900">{{ $prestamo->empleado->nombre_completo ?? 'N/A' }}</div>
            </div>
            <!-- Monto -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Monto</div>
                <div class="text-2xl font-light text-gray-900">{{ number_format($prestamo->monto, 2) }}</div>
            </div>
            <!-- Tasa -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Tasa</div>
                <div class="text-2xl font-light text-blue-600">{{ $prestamo->porcentaje_interes }}%</div>
            </div>
            <!-- Plazo -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Plazo</div>
                <div class="text-2xl font-light text-gray-900">{{ $prestamo->plazo_meses }} meses</div>
            </div>
        </div>
        
        <!-- Progreso -->
        @php
            $cuotasPagadas = $prestamo->historialCuotas->where('pagado', true)->count();
            $cuotasPendientes = $prestamo->historialCuotas->where('pagado', false)->count();
            $totalPagado = $prestamo->historialCuotas->where('pagado', true)->sum('saldo_pagado');
            $saldoRestante = $prestamo->monto - $totalPagado;
            $progreso = $prestamo->plazo_meses > 0 ? round(($cuotasPagadas / $prestamo->plazo_meses) * 100, 2) : 0;
        @endphp
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center text-sm">
            <div>
                <span class="text-gray-500">Pagado:</span>
                <span class="ml-1 font-medium">{{ number_format($totalPagado, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500">Pendiente:</span>
                <span class="ml-1 font-medium">{{ number_format($saldoRestante, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500">Cuotas Pagadas:</span>
                <span class="ml-1 font-medium">{{ $cuotasPagadas }}</span>
            </div>
            <div>
                <span class="text-gray-500">Cuotas Pendientes:</span>
                <span class="ml-1 font-medium">{{ $cuotasPendientes }}</span>
            </div>
            <div>
                <span class="text-gray-500">Progreso:</span>
                <span class="ml-1 font-medium">{{ $progreso }}%</span>
            </div>
        </div>
    </div>

    <!-- Tabla Minimalista -->
    <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Capital</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Interés</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($prestamo->historialCuotas->sortBy('num_cuota') as $cuota)
                    <tr class="{{ $cuota->pagado ? 'bg-green-50' : ($cuota->fecha_programada < now() ? 'bg-red-50' : 'bg-white') }} hover:bg-gray-100 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $cuota->num_cuota }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($cuota->fecha_programada)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuota->abono_capital, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuota->abono_intereses, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($cuota->cuota_mensual, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-500">{{ number_format($cuota->saldo_restante, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if($cuota->pagado)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Pagada
                                </span>
                            @elseif($cuota->fecha_programada < now())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Vencida
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr class="font-medium">
                    <td class="px-4 py-3 text-sm text-gray-900" colspan="2">TOTAL</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($prestamo->historialCuotas->sum('abono_capital'), 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($prestamo->historialCuotas->sum('abono_intereses'), 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($prestamo->historialCuotas->sum('cuota_mensual'), 2) }}</td>
                    <td class="px-4 py-3" colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
@endsection