<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\HistorialCuota;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrestamosSimuladorController extends Controller
{
    /**
     * Mostrar simulador de préstamos
     */
    public function simulador()
    {
        $empleados = Empleado::select('id_empleado', 'nombre_completo', 'codigo_empleado', 'identidad')
            ->orderBy('nombre_completo')
            ->get();
        
        return view('prestamos.simulador', compact('empleados'));
    }

    /**
     * Calcular tabla de amortización para el simulador
     */
    public function calcularSimulacion(Request $request)
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleado,id_empleado',
            'monto' => 'required|numeric|min:0.01',
            'tasa_interes' => 'required|numeric|min:0|max:100',
            'plazo_meses' => 'required|integer|min:1|max:360',
            'fecha_primer_pago' => 'required|date',
        ]);

        $empleado = Empleado::find($request->id_empleado);
        $monto = $request->monto;
        $tasaAnual = $request->tasa_interes / 100;
        $tasaMensual = $tasaAnual / 12;
        $plazoMeses = $request->plazo_meses;
        $fechaPrimerPago = Carbon::parse($request->fecha_primer_pago);

        // Calcular cuota mensual usando la fórmula de amortización francesa
        if ($tasaMensual > 0) {
            $cuotaMensual = $monto * ($tasaMensual * pow(1 + $tasaMensual, $plazoMeses)) / (pow(1 + $tasaMensual, $plazoMeses) - 1);
        } else {
            $cuotaMensual = $monto / $plazoMeses; // Sin interés
        }

        // Generar tabla de amortización
        $tablaAmortizacion = [];
        $saldoRestante = $monto;
        $totalIntereses = 0;
        
        for ($i = 1; $i <= $plazoMeses; $i++) {
            $fechaCuota = $fechaPrimerPago->copy()->addMonths($i - 1);
            $interesesCuota = $saldoRestante * $tasaMensual;
            $capitalCuota = $cuotaMensual - $interesesCuota;
            
            // Ajuste para la última cuota
            if ($i === $plazoMeses && $saldoRestante - $capitalCuota != 0) {
                $capitalCuota = $saldoRestante;
                $cuotaMensual = $capitalCuota + $interesesCuota;
            }
            
            $saldoRestante -= $capitalCuota;
            $totalIntereses += $interesesCuota;
            
            $tablaAmortizacion[] = [
                'numero_cuota' => $i,
                'fecha_pago' => $fechaCuota->format('Y-m-d'),
                'saldo_inicial' => round($saldoRestante + $capitalCuota, 2),
                'cuota_mensual' => round($cuotaMensual, 2),
                'abono_capital' => round($capitalCuota, 2),
                'abono_intereses' => round($interesesCuota, 2),
                'saldo_restante' => round(max(0, $saldoRestante), 2),
            ];
        }

        $resumenPrestamo = [
            'empleado' => $empleado,
            'monto_prestamo' => $monto,
            'tasa_interes' => $request->tasa_interes,
            'plazo_meses' => $plazoMeses,
            'cuota_mensual' => round($cuotaMensual, 2),
            'total_intereses' => round($totalIntereses, 2),
            'total_a_pagar' => round($monto + $totalIntereses, 2),
            'fecha_primer_pago' => $fechaPrimerPago->format('Y-m-d'),
        ];

        return response()->json([
            'success' => true,
            'resumen' => $resumenPrestamo,
            'tabla_amortizacion' => $tablaAmortizacion
        ]);
    }

    /**
     * Registrar préstamo desde el simulador
     */
    public function registrarPrestamo(Request $request)
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleado,id_empleado',
            'monto' => 'required|numeric|min:0.01',
            'tasa_interes' => 'required|numeric|min:0|max:100',
            'plazo_meses' => 'required|integer|min:1|max:360',
            'fecha_primer_pago' => 'required|date',
            'observaciones' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Obtener el siguiente número de préstamo
            $ultimoNumero = Prestamo::max('num_prestamo') ?? 0;
            $numeroNuevoPrestamo = $ultimoNumero + 1;

            $monto = $request->monto;
            $tasaAnual = $request->tasa_interes;
            $tasaMensual = $tasaAnual / 12 / 100;
            $plazoMeses = $request->plazo_meses;
            $fechaPrimerPago = Carbon::parse($request->fecha_primer_pago);

            // Calcular cuota mensual
            if ($tasaMensual > 0) {
                $cuotaMensual = $monto * ($tasaMensual * pow(1 + $tasaMensual, $plazoMeses)) / (pow(1 + $tasaMensual, $plazoMeses) - 1);
            } else {
                $cuotaMensual = $monto / $plazoMeses;
            }

            $totalIntereses = ($cuotaMensual * $plazoMeses) - $monto;

            // Crear el préstamo
            $prestamo = Prestamo::create([
                'num_prestamo' => $numeroNuevoPrestamo,
                'id_empleado' => $request->id_empleado,
                'monto' => $monto,
                'cuota_capital' => round($cuotaMensual, 2),
                'porcentaje_interes' => $tasaAnual,
                'total_intereses' => round($totalIntereses, 2),
                'cobro_extraordinario' => 0,
                'causa' => 'Préstamo generado desde simulador',
                'plazo_meses' => $plazoMeses,
                'fecha_deposito_prestamo' => now()->format('Y-m-d'),
                'fecha_primera_cuota' => $fechaPrimerPago->format('Y-m-d'),
                'estado_prestamo' => 'Activo',
                'observaciones' => $request->observaciones ?? 'Préstamo registrado desde el simulador',
            ]);

            // Generar historial de cuotas
            $this->generarHistorialCuotas($prestamo);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Préstamo registrado exitosamente',
                'prestamo_id' => $prestamo->id_prestamo,
                'numero_prestamo' => $numeroNuevoPrestamo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar préstamo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el préstamo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar tabla de amortización de préstamos existentes
     */
    public function tablaAmortizacion(Request $request)
    {
        $query = Prestamo::with(['empleado', 'historialCuotas'])
            ->select('prestamo.*');

        // Filtros
        if ($request->filled('id_empleado')) {
            $query->where('id_empleado', $request->id_empleado);
        }

        if ($request->filled('empleado')) {
            $query->whereHas('empleado', function ($q) use ($request) {
                $q->where('nombre_completo', 'LIKE', "%{$request->empleado}%")
                  ->orWhere('codigo_empleado', 'LIKE', "%{$request->empleado}%")
                  ->orWhere('identidad', 'LIKE', "%{$request->empleado}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado_prestamo', $request->estado);
        }

        // Obtener todos los empleados para el selector
        $empleados = Empleado::select('id_empleado', 'nombre_completo', 'codigo_empleado')
            ->orderBy('nombre_completo')
            ->get();

        $prestamos = $query->orderBy('fecha_deposito_prestamo', 'desc')
                          ->paginate(10);

        return view('prestamos.tabla-amortizacion', compact('prestamos', 'empleados'));
    }

    /**
     * Ver detalle de amortización de un préstamo específico
     */
    public function verAmortizacion($id)
    {
        $prestamo = Prestamo::with(['empleado', 'historialCuotas' => function($query) {
            $query->orderBy('num_cuota');
        }])->findOrFail($id);

        return view('prestamos.detalle-amortizacion', compact('prestamo'));
    }

        /**
     * Generar vista previa de amortización antes de guardar el préstamo
     */
    public function vistaPrevia(Request $request)
    {
        try {
            Log::info('Vista previa solicitada', $request->all());
            
            $request->validate([
                'id_empleado' => 'required|exists:empleado,id_empleado',
                'monto' => 'required|numeric|min:0.01',
                'tasa_interes' => 'required|numeric|min:0|max:100',
                'plazo_meses' => 'required|integer|min:1|max:360',
                'fecha_primer_pago' => 'required|date',
            ]);

            $empleado = Empleado::find($request->id_empleado);
            $monto = $request->monto;
            $tasaMensual = $request->tasa_interes / 100; // La tasa ya viene mensual, solo convertir a decimal
            $plazoMeses = $request->plazo_meses;
            $fechaPrimerPago = Carbon::parse($request->fecha_primer_pago);

        // Usar amortización de capital constante (no francesa)
        $capitalConstante = $monto / $plazoMeses; // Capital fijo por cuota

        // Generar tabla de amortización
        $cuotas = [];
        $saldoCapital = $monto;
        $fecha = $fechaPrimerPago->copy();
        $totalCapital = 0;
        $totalIntereses = 0;

        for ($i = 1; $i <= $plazoMeses; $i++) {
            // Capital constante por cuota
            $capitalPeriodo = $capitalConstante;
            
            // Interés se calcula sobre el saldo restante
            $interesPeriodo = $saldoCapital * $tasaMensual;
            
            // Cuota total = capital + interés
            $cuotaTotal = $capitalPeriodo + $interesPeriodo;
            
            // Actualizar saldo
            $saldoCapital -= $capitalPeriodo;
            $totalCapital += $capitalPeriodo;
            $totalIntereses += $interesPeriodo;

            $cuotas[] = [
                'numero' => $i,
                'fecha' => $fecha->format('Y-m-d'),
                'capital' => round($capitalPeriodo, 2),
                'interes' => round($interesPeriodo, 2),
                'cuota' => round($cuotaTotal, 2),
                'saldo' => round(max(0, $saldoCapital), 2),
                'tasa_mensual' => $request->tasa_interes // Mostrar la tasa tal como se ingresó
            ];

            $fecha->addMonth();
        }

        // Calcular porcentajes correctamente
        $porcentajeInteresTotal = ($totalIntereses / $monto) * 100;
        $porcentajeInteresMensual = $porcentajeInteresTotal / $plazoMeses;

        $resumen = [
            'empleado' => $empleado->nombre_completo,
            'monto_prestado' => $monto,
            'tasa_mensual_ingresada' => $request->tasa_interes, // La tasa tal como se ingresó
            'plazo_meses' => $plazoMeses,
            'plazo_trimestres' => ceil($plazoMeses / 3),
            'cuota_capital_constante' => round($capitalConstante, 2),
            'total_capital' => round($totalCapital, 2),
            'total_intereses' => round($totalIntereses, 2),
            'total_a_pagar' => round($totalCapital + $totalIntereses, 2),
            'porcentaje_interes_total' => round($porcentajeInteresTotal, 2),
            'porcentaje_interes_mensual' => round($porcentajeInteresMensual, 2)
        ];

        return response()->json([
            'success' => true,
            'resumen' => $resumen,
            'cuotas' => $cuotas
        ]);
        
        } catch (\Exception $e) {
            Log::error('Error en vista previa', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar la vista previa: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generar historial de cuotas para un préstamo
     */
    private function generarHistorialCuotas(Prestamo $prestamo)
    {
        $monto = $prestamo->monto;
        $tasaMensual = $prestamo->porcentaje_interes / 12 / 100;
        $plazoMeses = $prestamo->plazo_meses;
        $fechaPrimerPago = Carbon::parse($prestamo->fecha_primera_cuota);

        // Calcular cuota mensual
        if ($tasaMensual > 0) {
            $cuotaMensual = $monto * ($tasaMensual * pow(1 + $tasaMensual, $plazoMeses)) / (pow(1 + $tasaMensual, $plazoMeses) - 1);
        } else {
            $cuotaMensual = $monto / $plazoMeses;
        }

        $saldoRestante = $monto;
        
        for ($i = 1; $i <= $plazoMeses; $i++) {
            $fechaCuota = $fechaPrimerPago->copy()->addMonths($i - 1);
            $interesesCuota = $saldoRestante * $tasaMensual;
            $capitalCuota = $cuotaMensual - $interesesCuota;
            
            // Ajuste para la última cuota
            if ($i === $plazoMeses && $saldoRestante - $capitalCuota != 0) {
                $capitalCuota = $saldoRestante;
                $cuotaMensual = $capitalCuota + $interesesCuota;
            }
            
            $saldoRestante -= $capitalCuota;
            
            HistorialCuota::create([
                'id_prestamo' => $prestamo->id_prestamo,
                'num_cuota' => $i,
                'fecha_programada' => $fechaCuota->format('Y-m-d'),
                'abono_capital' => round($capitalCuota, 2),
                'abono_intereses' => round($interesesCuota, 2),
                'cuota_mensual' => round($cuotaMensual, 2),
                'cuota_quincenal' => round($cuotaMensual / 2, 2),
                'saldo_restante' => round(max(0, $saldoRestante), 2),
                'pagado' => false,
                'ajuste' => false,
            ]);
        }
    }
}
