@extends('layouts.prestamos')

@section('title', 'Tabla de Amortización - Préstamos')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-light text-gray-900">Tabla de Amortización de Préstamos</h1>
            <p class="text-gray-500">Gestión de amortizaciones</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-6 flex flex-wrap gap-4">
        <!-- Campo de búsqueda -->
        <div class="flex-1 min-w-64">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="searchInput" 
                       placeholder="Buscar por empleado, cédula o número de préstamo..."
                       class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <!-- Filtro por empleado -->
        <div class="flex-1 min-w-48">
            <form method="GET" action="{{ route('prestamos.amortizacion') }}">
                <select class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                        name="id_empleado" onchange="this.form.submit()">
                    <option value="">Seleccionar empleado...</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id_empleado }}" 
                                {{ request('id_empleado') == $empleado->id_empleado ? 'selected' : '' }}>
                            {{ $empleado->nombre_completo }} ({{ $empleado->codigo_empleado }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <!-- Filtro por estado -->
        <div class="flex-1 min-w-40">
            <form method="GET" action="{{ route('prestamos.amortizacion') }}">
                <input type="hidden" name="id_empleado" value="{{ request('id_empleado') }}">
                <select class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                        name="estado" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="Activo" {{ request('estado') == 'Activo' ? 'selected' : '' }}>Activo</option>
                    <option value="Pagado" {{ request('estado') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                    <option value="Cancelado" {{ request('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </form>
        </div>
        @if(request()->hasAny(['id_empleado', 'empleado', 'estado']))
        <div>
            <a href="{{ route('prestamos.amortizacion') }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                Limpiar Filtros
            </a>
        </div>
        @endif
    </div>

    <!-- Tabla Minimalista -->
    <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="w-full" id="prestamosTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Préstamo #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota Mensual</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Plazo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tasa %</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Depósito</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Progreso</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($prestamos as $prestamo)
                    @php
                        $cuotasPagadas = $prestamo->historialCuotas->where('pagado', true)->count();
                        $totalCuotas = $prestamo->plazo_meses;
                        $progreso = $totalCuotas > 0 ? round(($cuotasPagadas / $totalCuotas) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">#{{ $prestamo->num_prestamo }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $prestamo->empleado->nombre_completo ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $prestamo->empleado->identidad ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-green-600">{{ number_format($prestamo->monto, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($prestamo->cuota_capital, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-center text-gray-900">{{ $prestamo->plazo_meses }} meses</td>
                        <td class="px-4 py-3 text-sm text-center text-blue-600">{{ $prestamo->porcentaje_interes }}%</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($prestamo->fecha_deposito_prestamo)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if($prestamo->estado_prestamo == 'Activo')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $prestamo->estado_prestamo }}
                                </span>
                            @elseif($prestamo->estado_prestamo == 'Pagado')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $prestamo->estado_prestamo }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $prestamo->estado_prestamo }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2 relative">
                                    <div class="h-2 rounded-full absolute top-0 left-0 {{ $progreso < 25 ? 'bg-red-500' : ($progreso < 50 ? 'bg-yellow-500' : ($progreso < 75 ? 'bg-blue-500' : 'bg-green-500')) }}" 
                                         data-progress="{{ $progreso }}">
                                    </div>
                                </div>
                                <span class="text-xs text-gray-600">{{ $progreso }}%</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">{{ $cuotasPagadas }}/{{ $totalCuotas }} cuotas</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('prestamos.detalle.amortizacion', $prestamo->id_prestamo) }}" 
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded hover:bg-blue-200 transition-colors duration-200">
                                    Ver
                                </a>
                                <a href="{{ route('prestamos.exportar.amortizacion', $prestamo->id_prestamo) }}" 
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-600 bg-green-100 rounded hover:bg-green-200 transition-colors duration-200">
                                    Excel
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                            No se encontraron préstamos
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($prestamos->hasPages())
    <div class="flex items-center justify-between mt-6">
        <div class="text-sm text-gray-700">
            Mostrando {{ $prestamos->firstItem() ?? 0 }} - {{ $prestamos->lastItem() ?? 0 }} 
            de {{ $prestamos->total() }} préstamos
        </div>
        <div>
            {{ $prestamos->links() }}
        </div>
    </div>
    @endif

    <!-- Resumen Minimalista -->
    @if($prestamos->count() > 0)
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center">
            <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Total Préstamos</div>
            <div class="text-2xl font-light text-gray-900">{{ $prestamos->total() }}</div>
        </div>
        <div class="text-center">
            <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Préstamos Activos</div>
            <div class="text-2xl font-light text-green-600">{{ $prestamos->where('estado_prestamo', 'Activo')->count() }}</div>
        </div>
        <div class="text-center">
            <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Monto Total</div>
            <div class="text-2xl font-light text-gray-900">{{ number_format($prestamos->sum('monto'), 2) }}</div>
        </div>
        <div class="text-center">
            <div class="text-sm text-gray-500 uppercase tracking-wider mb-1">Promedio Cuota</div>
            <div class="text-2xl font-light text-blue-600">{{ number_format($prestamos->avg('cuota_capital'), 2) }}</div>
        </div>
    </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('prestamosTable');
    const tableRows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    // Función de búsqueda en tiempo real
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleRows = 0;

        Array.from(tableRows).forEach(function(row) {
            // Evitar filtrar la fila de "No se encontraron préstamos"
            if (row.children.length === 1 && row.children[0].colSpan > 1) {
                return;
            }

            const prestamoNum = row.children[0] ? row.children[0].textContent.toLowerCase() : '';
            const empleado = row.children[1] ? row.children[1].textContent.toLowerCase() : '';
            const cedula = row.children[2] ? row.children[2].textContent.toLowerCase() : '';
            const estado = row.children[8] ? row.children[8].textContent.toLowerCase() : '';

            const matchFound = prestamoNum.includes(searchTerm) || 
                              empleado.includes(searchTerm) || 
                              cedula.includes(searchTerm) || 
                              estado.includes(searchTerm);

            if (matchFound || searchTerm === '') {
                row.style.display = '';
                row.style.opacity = '1';
                visibleRows++;
                
                // Highlight del texto encontrado
                if (searchTerm !== '') {
                    highlightText(row, searchTerm);
                } else {
                    removeHighlight(row);
                }
            } else {
                row.style.display = 'none';
                row.style.opacity = '0.5';
            }
        });

        // Mostrar mensaje si no hay resultados
        updateEmptyMessage(visibleRows === 0 && searchTerm !== '');
        
        // Actualizar contador de resultados
        updateResultsCounter(visibleRows, Array.from(tableRows).length);
    });

    function highlightText(row, searchTerm) {
        const cells = [row.children[0], row.children[1], row.children[2], row.children[8]];
        cells.forEach(cell => {
            if (cell && cell.textContent) {
                const originalText = cell.getAttribute('data-original') || cell.textContent;
                cell.setAttribute('data-original', originalText);
                const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                cell.innerHTML = originalText.replace(regex, '<mark class="bg-yellow-200 text-yellow-900 px-1 rounded">$1</mark>');
            }
        });
    }

    function removeHighlight(row) {
        const cells = [row.children[0], row.children[1], row.children[2], row.children[8]];
        cells.forEach(cell => {
            if (cell && cell.getAttribute('data-original')) {
                cell.textContent = cell.getAttribute('data-original');
            }
        });
    }

    function updateResultsCounter(visible, total) {
        let counter = document.getElementById('resultsCounter');
        if (!counter) {
            counter = document.createElement('div');
            counter.id = 'resultsCounter';
            counter.className = 'text-sm text-gray-500 mb-2';
            table.parentNode.insertBefore(counter, table);
        }
        
        if (searchInput.value.trim() !== '') {
            counter.textContent = `Mostrando ${visible} de ${total} préstamos`;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }

    function updateEmptyMessage(show) {
        const tbody = table.getElementsByTagName('tbody')[0];
        let emptyRow = tbody.querySelector('.empty-search-row');
        
        if (show && !emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-search-row';
            emptyRow.innerHTML = `
                <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex items-center justify-center">
                        <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        No se encontraron préstamos que coincidan con la búsqueda
                    </div>
                </td>
            `;
            tbody.appendChild(emptyRow);
        } else if (!show && emptyRow) {
            emptyRow.remove();
        }
    }

    // Funcionalidad adicional: limpiar búsqueda con Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });

    // Placeholder dinámico
    const placeholderTexts = [
        'Buscar por empleado, cédula o número de préstamo...',
        'Ej: Juan López, 0801-1990-12345, #848...',
        'Escribe para filtrar los resultados...'
    ];
    
    let placeholderIndex = 0;
    setInterval(() => {
        if (searchInput !== document.activeElement && searchInput.value === '') {
            searchInput.placeholder = placeholderTexts[placeholderIndex];
            placeholderIndex = (placeholderIndex + 1) % placeholderTexts.length;
        }
    }, 3000);

    // Aplicar ancho a las barras de progreso
    const progressBars = document.querySelectorAll('[data-progress]');
    progressBars.forEach(function(bar) {
        const progress = bar.getAttribute('data-progress');
        bar.style.width = progress + '%';
    });
});
</script>

@endsection