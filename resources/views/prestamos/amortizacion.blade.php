@extends('layouts.prestamos')

@section('title', 'Tabla de Amortización')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-light text-gray-900">Tabla de Amortización</h1>
            <p class="text-gray-500">Préstamo #{{ $prestamo->num_prestamo }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('empleadosprestamo') }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                ← Volver
            </a>
            @if($cuotas->count() > 0)
            <button onclick="showRegenerateModal()" 
                    class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700 transition-colors duration-200">
                Regenerar
            </button>
            @endif
            <button onclick="window.print()" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 transition-colors duration-200">
                Imprimir
            </button>
        </div>
    </div>

    <!-- Información Principal -->
    <div class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-6">
            <!-- Empleado -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Empleado</div>
                <div class="text-lg font-medium text-gray-900">{{ $prestamo->nombre_completo }}</div>
                <div class="text-sm text-gray-400">{{ $prestamo->identidad }}</div>
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
            <!-- Progreso -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Progreso</div>
                <div class="text-2xl font-light text-green-600">{{ $resumen['total_cuotas'] > 0 ? round(($resumen['cuotas_pagadas'] / $resumen['total_cuotas']) * 100, 1) : 0 }}%</div>
            </div>
            <!-- Estado -->
            <div class="text-center">
                <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Estado</div>
                <div class="text-lg font-medium {{ $prestamo->estado_prestamo == 1 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $prestamo->estado_prestamo == 1 ? 'Activo' : 'Inactivo' }}
                </div>
            </div>
        </div>
        
        <!-- Resumen -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 text-center text-sm">
            <div>
                <span class="text-gray-500">Cuotas Pagadas:</span>
                <span class="ml-1 font-medium">{{ $resumen['cuotas_pagadas'] }}</span>
            </div>
            <div>
                <span class="text-gray-500">Cuotas Pendientes:</span>
                <span class="ml-1 font-medium">{{ $resumen['cuotas_pendientes'] }}</span>
            </div>
            <div>
                <span class="text-gray-500">Capital Pagado:</span>
                <span class="ml-1 font-medium">{{ number_format($resumen['capital_pagado'], 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500">Saldo Capital:</span>
                <span class="ml-1 font-medium">{{ number_format($resumen['saldo_capital'], 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500">Intereses Pagados:</span>
                <span class="ml-1 font-medium">{{ number_format($resumen['intereses_pagados'], 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500">Saldo Intereses:</span>
                <span class="ml-1 font-medium">{{ number_format($resumen['saldo_intereses'], 2) }}</span>
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
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota Mensual</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota Quincenal</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Pagado</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Restante</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Obs.</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($cuotas as $cuota)
                    @php
                        $isPagado = (bool)$cuota->pagado;
                    @endphp
                    <tr class="{{ $isPagado ? 'bg-green-50' : 'bg-white' }} hover:bg-gray-100 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $cuota->num_cuota }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($cuota->fecha_programada)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuota->abono_capital ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuota->abono_intereses ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($cuota->cuota_mensual ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($cuota->cuota_quincenal ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">{{ number_format($cuota->saldo_pagado ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-500">{{ number_format($cuota->saldo_restante ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if($isPagado)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Pagada
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ $cuota->observaciones ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                            No se han generado cuotas para este préstamo
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($cuotas->count() > 0)
            <tfoot class="bg-gray-50">
                <tr class="font-medium">
                    <td class="px-4 py-3 text-sm text-gray-900" colspan="2">TOTAL</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuotas->sum('abono_capital'), 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuotas->sum('abono_intereses'), 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuotas->sum('cuota_mensual'), 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($cuotas->sum('cuota_quincenal'), 2) }}</td>
                    <td class="px-4 py-3" colspan="4"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

</div>

<!-- Modal de confirmación para regenerar -->
<div id="regenerateModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center mb-4">
                <div class="bg-yellow-100 rounded-full p-2 mr-3">
                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Confirmar Regeneración</h3>
            </div>
            <div class="mb-6">
                <p class="text-gray-600">
                    ¿Está seguro de que desea regenerar la tabla de amortización? 
                    Esta acción eliminará todas las cuotas <strong>no pagadas</strong> y generará nuevas cuotas basadas en los datos actuales del préstamo.
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Las cuotas ya pagadas no se verán afectadas.
                    </p>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="hideRegenerateModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <form id="regenerateForm" method="POST" action="{{ route('prestamos.amortizacion.regenerar', $prestamo->id_prestamo) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        Regenerar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showRegenerateModal() {
    document.getElementById('regenerateModal').classList.remove('hidden');
}

function hideRegenerateModal() {
    document.getElementById('regenerateModal').classList.add('hidden');
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideRegenerateModal();
    }
});
</script>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $estadoClass }}">
                                    <i class="{{ $estadoIcon }} mr-1"></i>
                                    {{ $estadoText }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-sm max-w-xs truncate" title="{{ $cuota->observaciones }}">
                                {{ $cuota->observaciones ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                No se han generado cuotas para este préstamo
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        @if($cuotas->count() > 0)
        <div class="mt-6 border-t pt-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="bg-gray-50 p-3 rounded">
                    <span class="font-medium text-gray-600">Total Capital Programado:</span>
                    <span class="font-bold text-gray-800 ml-2">L {{ number_format($cuotas->sum('abono_capital'), 2) }}</span>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <span class="font-medium text-gray-600">Total Intereses Programados:</span>
                    <span class="font-bold text-gray-800 ml-2">L {{ number_format($cuotas->sum('abono_intereses'), 2) }}</span>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <span class="font-medium text-gray-600">Total Programado:</span>
                    <span class="font-bold text-gray-800 ml-2">L {{ number_format($cuotas->sum('cuota_quincenal'), 2) }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Información de impresión -->
    <div class="print-only hidden text-center text-xs text-gray-500 mt-4">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} - Sistema RRHH STB</p>
    </div>
</div>

<!-- Modal de confirmación para regenerar -->
<div id="regenerateModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center mb-4">
                <div class="bg-yellow-100 rounded-full p-2 mr-3">
                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Confirmar Regeneración</h3>
            </div>
            <div class="mb-6">
                <p class="text-gray-600">
                    ¿Está seguro de que desea regenerar la tabla de amortización? 
                    Esta acción eliminará todas las cuotas <strong>no pagadas</strong> y generará nuevas cuotas basadas en los datos actuales del préstamo.
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Las cuotas ya pagadas no se verán afectadas.
                    </p>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="hideRegenerateModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <form id="regenerateForm" method="POST" action="{{ route('prestamos.amortizacion.regenerar', $prestamo->id_prestamo) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-sync-alt mr-1"></i>Regenerar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para mejorar la experiencia de impresión
    window.addEventListener('beforeprint', function() {
        document.title = 'Amortización Préstamo {{ $prestamo->num_prestamo }} - {{ $prestamo->nombre_completo }}';
    });
    
    window.addEventListener('afterprint', function() {
        document.title = 'Tabla de Amortización';
    });
});

function showRegenerateModal() {
    document.getElementById('regenerateModal').classList.remove('hidden');
}

function hideRegenerateModal() {
    document.getElementById('regenerateModal').classList.add('hidden');
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideRegenerateModal();
    }
});
</script>

@endsection