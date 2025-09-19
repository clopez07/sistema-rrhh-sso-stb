<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Préstamos - Julio {{ $periodo['anio'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>

    @if(empty($export))
        <meta name="viewport" content="width=device-width, initial-scale=1">
    @endif
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        h1, h2, h3 { margin: 0.2rem 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #aaa; padding: 4px 6px; vertical-align: top; }
        thead th { background: #eee; }
        .nowrap { white-space: nowrap; }
        .right { text-align: right; }
        .center { text-align: center; }
        .controls { margin: 12px 0; display: flex; gap: 8px; align-items: center; }
        .muted { color: #666; font-size: 11px; }
        .totals { margin: 12px 0; }
        .totals td:first-child { font-weight: bold; background: #f6f6f6; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <h2>Reporte de Préstamos — Julio {{ $periodo['anio'] }}</h2>
    <div class="muted">Periodo: {{ $periodo['inicio'] }} al {{ $periodo['fin'] }} | Total de cuotas: {{ $totales->total_cuotas ?? 0 }}</div>

    @if(empty($export))
        <div class="controls no-print">
            <form method="GET" action="{{ route('reportes.prestamos.julio') }}">
                <label>
                    Año:
                    <input type="number" name="year" value="{{ request('year', $periodo['anio']) }}" min="2000" max="2100">
                </label>
                <input type="hidden" name="month" value="7">
                <button type="submit">Filtrar</button>
            </form>

            <a class="no-print"
               href="{{ route('reportes.prestamos.julio.descargar', ['year' => request('year', $periodo['anio']), 'month' => 7]) }}">
                Descargar Excel
            </a>
        </div>
    @endif

    {{-- Totales del mes --}}
    <table class="totals">
        <tbody>
        <tr>
            <td>Abono a capital</td>
            <td class="right">{{ number_format($totales->abono_capital ?? 0, 2, '.', ',') }}</td>
            <td>Abono a intereses</td>
            <td class="right">{{ number_format($totales->abono_intereses ?? 0, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td>Cuota mensual</td>
            <td class="right">{{ number_format($totales->cuota_mensual ?? 0, 2, '.', ',') }}</td>
            <td>Cuota quincenal</td>
            <td class="right">{{ number_format($totales->cuota_quincenal ?? 0, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td>Saldo pagado</td>
            <td class="right">{{ number_format($totales->saldo_pagado ?? 0, 2, '.', ',') }}</td>
            <td>Saldo restante</td>
            <td class="right">{{ number_format($totales->saldo_restante ?? 0, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td>Interés pagado</td>
            <td class="right">{{ number_format($totales->interes_pagado ?? 0, 2, '.', ',') }}</td>
            <td>Interés restante</td>
            <td class="right">{{ number_format($totales->interes_restante ?? 0, 2, '.', ',') }}</td>
        </tr>
        </tbody>
    </table>

    {{-- Detalle de cuotas del mes (todas las planillas) --}}
    <table>
        <thead>
        <tr>
            <th>Planilla</th>
            <th>Num. Préstamo</th>
            <th>Código Empleado</th>
            <th>Empleado</th>
            <th>No. Cuota</th>
            <th>Planilla</th>
            <th>Fecha Programada</th>
            <th>Abono Capital</th>
            <th>Abono Intereses</th>
            <th>Cuota Mensual</th>
            <th>Cuota Quincenal</th>
            <th>Saldo Pagado</th>
            <th>Saldo Restante</th>
            <th>Interés Pagado</th>
            <th>Interés Restante</th>
            <th>Ajuste</th>
            <th>Motivo</th>
            <th>Fecha Pago Real</th>
            <th>Pagado</th>
            <th>Observaciones</th>
        </tr>
        </thead>
        <tbody>
            @php
                $colores = [
                    '#FFFF00',
                    '#F4B084',
                    '#A9D08E',
                    '#B4C6E7'
                ];

                $planillaColores = [];
                $colorIndex = 0;
            @endphp

        @forelse($cuotas as $c)
            @php
                if (!isset($planillaColores[$c->planilla])) {
                    $planillaColores[$c->planilla] = $colores[$colorIndex % count($colores)];
                    $colorIndex++;
                }
                $rowColor = $planillaColores[$c->planilla];
            @endphp
            <tr class="{{ $rowColor }}">
                <td>{{ $c->codigo_planilla ?? '' }}</td>
                <td class="nowrap">{{ $c->num_prestamo }}</td>
                <td class="nowrap">{{ $c->codigo_empleado }}</td>
                <td>{{ $c->nombre_completo }}</td>
                <td class="nowrap">{{ $c->num_cuota }}</td>
                <td class="nowrap">{{ $c->planilla }}</td>
                <td class="nowrap">{{ $c->fecha_programada }}</td>

                <td class="right">{{ number_format($c->abono_capital, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->abono_intereses, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->cuota_mensual, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->cuota_quincenal, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->saldo_pagado, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->saldo_restante, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->interes_pagado, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($c->interes_restante, 2, '.', ',') }}</td>

                <td class="center">{{ (int)$c->ajuste === 1 ? 'Sí' : 'No' }}</td>
                <td>{{ $c->motivo }}</td>
                <td class="nowrap">{{ $c->fecha_pago_real }}</td>
                <td class="center">{{ (int)$c->pagado === 1 ? 'Sí' : 'No' }}</td>
                <td>{{ $c->observaciones }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="19" class="center">No hay cuotas programadas en este periodo.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</body>
</html>