<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportePrestamosController extends Controller
{
    public function index(Request $request)
    {
        [$cuotas, $totales, $periodo] = $this->getData($request);
        return view('reportes.prestamos.julio', compact('cuotas', 'totales', 'periodo'));
    }

    public function export(Request $request)
    {
        [$cuotas, $totales, $periodo] = $this->getData($request);

        $html = view('reportes.prestamos.julio', [
            'cuotas'  => $cuotas,
            'totales' => $totales,
            'periodo' => $periodo,
            'export'  => true,   // flag para ocultar botones/inputs en export
        ])->render();

        $filename = sprintf('reporte_prestamos_%d_%02d.xls', $periodo['anio'], $periodo['mes']);

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Prepara datos del reporte (lista + totales) para Julio del año indicado.
     * Soporta ?month=7&year=2025; por defecto month=7 (Julio) y year=actual.
     */
    private function getData(Request $request)
    {
        $mes  = (int) $request->input('month', 7);
        $anio = (int) $request->input('year', now()->year);

        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fin    = (clone $inicio)->endOfMonth()->endOfDay();

        // Lista de cuotas de TODO el mes (todas las planillas)
        $cuotas = DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'hc.id_prestamo', '=', 'p.id_prestamo')
            ->leftJoin('planilla as pl', 'p.id_planilla', '=', 'pl.id_planilla') // left por si hay préstamos sin planilla
            ->join('empleado as e', 'p.id_empleado', '=', 'e.id_empleado')
            ->whereBetween('hc.fecha_programada', [$inicio->toDateString(), $fin->toDateString()])
            ->select(
                'pl.planilla as codigo_planilla',
                'p.num_prestamo',
                'e.codigo_empleado',
                'e.nombre_completo',
                'hc.num_cuota',
                'pl.planilla',
                'hc.fecha_programada',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.cuota_mensual',
                'hc.cuota_quincenal',
                'hc.saldo_pagado',
                'hc.saldo_restante',
                'hc.interes_pagado',
                'hc.interes_restante',
                'hc.ajuste',
                'hc.motivo',
                'hc.fecha_pago_real',
                'hc.pagado',
                'hc.observaciones'
            )
            ->orderBy('hc.fecha_programada')
            ->orderBy('pl.planilla')
            ->orderBy('p.num_prestamo')
            ->orderBy('hc.num_cuota')
            ->get();

        // Totales del mes
        $totales = DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'hc.id_prestamo', '=', 'p.id_prestamo')
            ->leftJoin('planilla as pl', 'p.id_planilla', '=', 'pl.id_planilla')
            ->whereBetween('hc.fecha_programada', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw('
                COALESCE(SUM(hc.abono_capital),0)    as abono_capital,
                COALESCE(SUM(hc.abono_intereses),0)  as abono_intereses,
                COALESCE(SUM(hc.cuota_mensual),0)    as cuota_mensual,
                COALESCE(SUM(hc.cuota_quincenal),0)  as cuota_quincenal,
                COALESCE(SUM(hc.saldo_pagado),0)     as saldo_pagado,
                COALESCE(SUM(hc.saldo_restante),0)   as saldo_restante,
                COALESCE(SUM(hc.interes_pagado),0)   as interes_pagado,
                COALESCE(SUM(hc.interes_restante),0) as interes_restante,
                COUNT(*)                              as total_cuotas
            ')
            ->first();

        $periodo = [
            'mes'    => $mes,
            'anio'   => $anio,
            'inicio' => $inicio->toDateString(),
            'fin'    => $fin->toDateString(),
        ];

        return [$cuotas, $totales, $periodo];
    }
}
