@php
    $stats = $ajustePlan['counts'] ?? [];
    $cancelParams = array_filter([
        'fecha_inicio' => $filtros['fecha_inicio'] ?? null,
        'fecha_fin' => $filtros['fecha_fin'] ?? null,
        'estado' => $filtros['estado'] ?? null,
        'search' => $filtros['search'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="mt-8 space-y-6">
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Previsualización de ajustes</h2>
                <p class="text-sm text-gray-600">
                    Rango analizado: <strong>{{ $ajustePlan['inicio'] ?? '?' }}</strong> al
                    <strong>{{ $ajustePlan['fin'] ?? '?' }}</strong>
                </p>
                <p class="text-xs text-gray-500">Revisa los cambios antes de aplicarlos.</p>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('prestamos.ajustes.commit') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="token" value="{{ $ajusteToken }}">
                    <input type="hidden" name="redirect_route" value="cuotas.rango">
                    <input type="hidden" name="redirect_fecha_inicio" value="{{ $filtros['fecha_inicio'] ?? '' }}">
                    <input type="hidden" name="redirect_fecha_fin" value="{{ $filtros['fecha_fin'] ?? '' }}">
                    <input type="hidden" name="redirect_estado" value="{{ $filtros['estado'] ?? 'todas' }}">
                    <input type="hidden" name="redirect_search" value="{{ $filtros['search'] ?? '' }}">
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                        Aplicar ajustes
                    </button>
                </form>
                <a href="{{ route('cuotas.rango', $cancelParams) }}" class="text-sm text-gray-600 hover:text-gray-900">
                    Cancelar
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="p-4 bg-white border border-gray-200 rounded shadow-sm">
            <p class="text-xs uppercase text-gray-500">Pagos completos</p>
            <p class="text-lg font-semibold text-emerald-600">{{ $stats['pagos_completos'] ?? 0 }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded shadow-sm">
            <p class="text-xs uppercase text-gray-500">Pagos parciales</p>
            <p class="text-lg font-semibold text-amber-600">{{ $stats['pagos_parciales'] ?? 0 }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded shadow-sm">
            <p class="text-xs uppercase text-gray-500">Sin deducción</p>
            <p class="text-lg font-semibold text-red-600">{{ $stats['sin_pago'] ?? 0 }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Empleado</th>
                        <th class="px-4 py-3">Préstamo</th>
                        <th class="px-4 py-3">Cuota</th>
                        <th class="px-4 py-3">Acción</th>
                        <th class="px-4 py-3 text-right">Monto cuota</th>
                        <th class="px-4 py-3 text-right">Pagado</th>
                        <th class="px-4 py-3 text-right">Diferencia</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Impacto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ajustePlan['actions'] ?? [] as $action)
                        @php
                            $hasUpdates = !empty($action['updates']);
                            $rowClass = $hasUpdates ? 'bg-white' : 'bg-gray-50';
                            $expected = $action['expected'] !== null ? number_format($action['expected'], 2, '.', ',') : '—';
                            $paid = $action['pagado'] !== null ? number_format($action['pagado'], 2, '.', ',') : '—';
                            $diff = $action['diferencia'] !== null ? number_format($action['diferencia'], 2, '.', ',') : '—';
                            $cuotaInfo = $action['cuota'] ? 'No. ' . $action['cuota']['num'] . ' · ' . $action['cuota']['fecha'] : '—';
                            $prestamoInfo = $action['prestamo'] ? ($action['prestamo']['numero'] ?? ('ID ' . $action['prestamo']['id'])) : '—';
                        @endphp
                        <tr class="{{ $rowClass }} border-b border-gray-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $action['employee']['nombre'] ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $action['employee']['codigo'] ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $prestamoInfo }}</td>
                            <td class="px-4 py-3">{{ $cuotaInfo }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $hasUpdates ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' }}">
                                    {{ ucfirst(str_replace('_', ' ', $action['type'] ?? '')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $expected }}</td>
                            <td class="px-4 py-3 text-right">{{ $paid }}</td>
                            <td class="px-4 py-3 text-right">{{ $diff }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $action['notes'] ?? '' }}</td>
                            <td class="px-4 py-3">
                                @if ($hasUpdates)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Se aplicará</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600">Informativo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-4 text-center text-sm text-gray-500">No se detectaron movimientos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @if (!empty($ajustePlan['sin_empleado_detalle']))
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700">Códigos sin empleado ({{ count($ajustePlan['sin_empleado_detalle']) }})</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($ajustePlan['sin_empleado_detalle'] as $item)
                        <li>Fila {{ $item['fila'] }} · Código {{ $item['codigo'] }} · Monto {{ number_format($item['deduccion'], 2, '.', ',') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($ajustePlan['sin_coincidencia_detalle']))
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700">Deducciones sin coincidencia ({{ count($ajustePlan['sin_coincidencia_detalle']) }})</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($ajustePlan['sin_coincidencia_detalle'] as $item)
                        <li>Fila {{ $item['fila'] }} · Código {{ $item['codigo'] }} · Monto {{ number_format($item['deduccion'], 2, '.', ',') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if (!empty($ajustePlan['ejemplos']))
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700">Muestras del Excel</h3>
            <ul class="mt-2 space-y-1 text-xs text-gray-500">
                @foreach ($ajustePlan['ejemplos'] as $ejemplo)
                    <li>{{ $ejemplo }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
