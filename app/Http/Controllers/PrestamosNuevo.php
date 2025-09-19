<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrestamosNuevo extends Controller
{
    public function storeprestamo(Request $request)
    {
        $numPrestamo = $request->input('num_prestamo') ?? $request->input('numero_prestamo');
        $idEmpleado  = $request->input('id_empleado');

        // ===== Montos principales con decimales =====
        $monto       = $this->parseMoney($request->input('monto') ?? $request->input('monto_prestado'));
        $cuotaCap    = $this->parseMoney($request->input('cuota_capital') ?? $request->input('cuota_mensual'));
        $porcInteres = (float)$this->parseMoney($request->input('porcentaje_interes')); // por si viene "2,5"
        $totalInt    = $this->parseMoney($request->input('total_intereses'));

        // Plazo del préstamo (YA admite decimales)
        $plazoMeses  = (float)($request->input('plazo_meses') ?? $request->input('plazo_prestamo'));

        $fAprob      = $request->input('fecha_deposito_prestamo') ?? $request->input('fecha_aprobacion');
        $fPrimera    = $request->input('fecha_primera_cuota');
        $idPlanilla  = (int)($request->input('id_planilla') ?? $request->input('planilla'));
        $estado      = (int)($request->input('estado_prestamo') ?? 1);
        $observ      = trim((string)$request->input('observaciones'));

        $esRefi       = $request->boolean('es_refinanciamiento');
        $refiIntTipo  = $request->input('refi_int_tipo', 'todos');   // 'todos' | 'parcial' | 'ninguno'
        $refiIntMonto = $this->parseMoney($request->input('refi_int_monto') ?? 0);
        $chkExtra    = $request->boolean('cobro_extraordinario');

        // ===== EXTRAS (marcadores simples) =====
        $extrasDetallados = [];
        if ($chkExtra) {
            $def = [
                ['flag' => 'cobro_decimo',       'monto' => 'monto_decimo',       'label' => 'Décimo'],
                ['flag' => 'cobro_aguinaldo',    'monto' => 'monto_aguinaldo',    'label' => 'Aguinaldo'],
                ['flag' => 'cobro_prestaciones', 'monto' => 'monto_prestaciones', 'label' => 'Prestaciones'],
                ['flag' => 'cobro_liquidacion',  'monto' => 'monto_liquidacion',  'label' => 'Liquidación'],
            ];
            foreach ($def as $d) {
                if ($request->boolean($d['flag'])) {
                    $val = $this->parseMoney($request->input($d['monto']) ?? 0);
                    if ($val > 0) {
                        $extrasDetallados[] = ['label' => $d['label'], 'monto' => $val];
                    }
                }
            }
        }

        // ===== EXTRAS (múltiples por tipo, con fecha) =====
        $extrasMulti = $request->input('extras_multi', []);
        if (is_array($extrasMulti)) {
            foreach ($extrasMulti as $row) {
                $tipo    = trim((string)($row['tipo'] ?? ''));
                $periodo = trim((string)($row['periodo'] ?? ''));
                $montoX  = $this->parseMoney($row['monto'] ?? 0);
                $fechaX  = $row['fecha'] ?? null;

                if ($montoX > 0 && $tipo !== '') {
                    $base  = match ($tipo) {
                        'decimo'       => 'Décimo',
                        'aguinaldo'    => 'Aguinaldo',
                        'prestaciones' => 'Prestaciones',
                        'liquidacion'  => 'Liquidación',
                        default        => ucfirst($tipo),
                    };
                    $label = $periodo !== '' ? ($base.' '.$periodo) : $base;

                    $fechaNorm = null;
                    if (!empty($fechaX)) {
                        try { $fechaNorm = Carbon::parse($fechaX)->toDateString(); } catch (\Throwable $e) { $fechaNorm = null; }
                    }

                    $extrasDetallados[] = [
                        'label' => $label,
                        'monto' => $montoX,
                        'fecha' => $fechaNorm, // opcional
                    ];
                }
            }
        }

        // ==========================================================
        // ==========   DEPÓSITOS DIRECTOS (con decimales) ==========
        // ==========================================================
        $depSi      = $request->boolean('depositos_si');
        $depUnico   = $depSi && $request->boolean('deposito_unico');
        $depVarios  = $depSi && $request->boolean('deposito_varios');

        // Único
        $depUnicoMonto = $this->parseMoney($request->input('deposito_unico_monto') ?? 0);
        $depUnicoFecha = $request->input('deposito_unico_fecha');

        // Varios (mensuales)
        $depTotal    = $this->parseMoney($request->input('depositos_total') ?? 0);
        $depPlazo    = (float)($request->input('depositos_plazo') ?? 0);     // AHORA puede ser decimal (p.ej. 3.5)
        $depCuota    = $this->parseMoney($request->input('depositos_cuota') ?? 0);
        $depFechaIni = $request->input('depositos_fecha_inicio');

        // Completar combinaciones faltantes
        if ($depVarios && $depSi) {
            if ($depTotal <= 0 && $depCuota > 0 && $depPlazo > 0) {
                $depTotal = $depCuota * $depPlazo;
            } elseif ($depCuota <= 0 && $depTotal > 0 && $depPlazo > 0) {
                $depCuota = $depTotal / max(1, $depPlazo);
            } elseif ($depPlazo <= 0 && $depTotal > 0 && $depCuota > 0) {
                $depPlazo = (float)($depTotal / max(0.01, $depCuota)); // ahora float exacto
            }
        }

        // Config para calendario
        $depositosCfg = null;
        if ($depSi) {
            if ($depUnico && $depUnicoMonto > 0) {
                $depositosCfg = [
                    'modo'         => 'unico',
                    'total'        => $depUnicoMonto,
                    'cuota'        => $depUnicoMonto,
                    'plazo'        => 1.0,
                    'fecha_inicio' => $depUnicoFecha ?: ($fAprob ?: Carbon::now()->toDateString()),
                ];
            } elseif ($depVarios && $depPlazo > 0 && $depCuota > 0 && $depTotal > 0) {
                $depositosCfg = [
                    'modo'         => 'varios',
                    'total'        => $depTotal,
                    'cuota'        => $depCuota,
                    'plazo'        => (float)$depPlazo, // decimal
                    'fecha_inicio' => $depFechaIni ?: ($fAprob ?: Carbon::now()->toDateString()),
                ];
            }
        }
        // ==========================================================

        // ===== TOTAL Y CAUSA DE EXTRAS =====
        $extraTotal = array_sum(array_map(fn($e) => (float)$e['monto'], $extrasDetallados));
        $extraCausa = array_map(fn($e) => $e['label'], $extrasDetallados);
        $causa      = $extraTotal > 0 ? ('Cobro extraordinario: '.implode(', ', $extraCausa)) : null;

        // ===== INSERT DEL PRÉSTAMO =====
        $prestamoId = DB::table('prestamo')->insertGetId([
            'num_prestamo'            => $numPrestamo,
            'id_empleado'             => $idEmpleado,
            'monto'                   => $monto,
            'cuota_capital'           => $cuotaCap,
            'porcentaje_interes'      => $porcInteres,
            'total_intereses'         => $totalInt,
            'cobro_extraordinario'    => ($extraTotal > 0 ? $extraTotal : null),
            'causa'                   => $causa,
            'plazo_meses'             => $plazoMeses, // ya permite decimales
            'fecha_deposito_prestamo' => $fAprob,
            'fecha_primera_cuota'     => $fPrimera,
            'id_planilla'             => $idPlanilla,
            'estado_prestamo'         => $estado,
            'observaciones'           => $observ ?: null,
        ]);

        // ===== REFINANCIAMIENTO (si aplica) =====
        if ($esRefi && $idEmpleado) {
            $this->aplicarRefinanciamiento(
                (int)$prestamoId,
                (int)$idEmpleado,
                $fAprob ?: Carbon::now()->toDateString(),
                $refiIntTipo,
                (float)$refiIntMonto
            );
        }

        // ===== CALENDARIO =====
        try {
            DB::beginTransaction();

            DB::table('historial_cuotas')->where('id_prestamo', $prestamoId)->delete();

            $frecuencia = DB::table('planilla')
                ->where('id_planilla', $idPlanilla)
                ->value('frecuencia_pago');
            $frecuencia = strtolower(trim((string)$frecuencia));

            $this->generarCalendarioConDepositosYExtrasV2(
                (int)$prestamoId,
                (float)$monto,
                (float)$cuotaCap,
                (float)$totalInt,
                (float)$plazoMeses,
                $fAprob,
                $fPrimera,
                $frecuencia,
                $depositosCfg,
                $extrasDetallados
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al programar calendario: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Préstamo registrado correctamente');
    }

    /**
     * Genera calendario mezclando Planilla + Depósitos (único/varios) + Extras.
     * - Resta (depósitos + extras) del capital que irá a planilla.
     * - Último depósito se ajusta si el remanente es menor.
     * - En la misma fecha: DEPOSITO -> PLANILLA -> EXTRA.
     * - El "plazo" de depósitos puede ser DECIMAL (ej. 3.5).
     */
    private function generarCalendarioConDepositosYExtrasV2(
        int $idPrestamo,
        float $monto,
        float $cuotaCapitalMensual,
        float $totalIntereses,
        float $plazoMeses,
        ?string $fechaDeposito,
        ?string $fechaPrimera,
        string $frecuencia,
        ?array $depositosCfg = null,     // ['modo'=>'unico|varios','total','cuota','plazo (float)','fecha_inicio']
        array $extrasDetallados = []     // ['label','monto','fecha'?]
    ): void {
        // === 1) Fechas/base ===
        $fechaInicio = $fechaPrimera ?: $fechaDeposito ?: Carbon::now()->toDateString();
        $fIni = Carbon::parse($fechaInicio)->startOfDay();

        $frecuencia = strtolower(trim($frecuencia));
        $factorPeriodo = match ($frecuencia) {
            'mensual'   => 1.0,
            'quincenal' => 2.0,
            'bisemanal' => 2.0,
            'semanal'   => 4.0,
            default     => 2.0,
        };

        $interesMensual = $plazoMeses > 0 ? ($totalIntereses / $plazoMeses) : 0.0;
        $baseCapPeriodo = $factorPeriodo > 0 ? ($cuotaCapitalMensual / $factorPeriodo) : $cuotaCapitalMensual;

        // === 2) Depósitos (eventos) y total para restar del capital planilla ===
        $depTotal   = 0.0;
        $depEventos = [];

        if ($depositosCfg && ($depositosCfg['modo'] ?? '') === 'unico') {
            $depTotal = max(0.0, (float)$depositosCfg['total']);
            $fec      = $depositosCfg['fecha_inicio'] ?? $fechaDeposito ?? $fechaInicio;
            $depEventos[] = [
                'tipo'  => 'deposito',
                'fecha' => Carbon::parse($fec)->toDateString(),
                'monto' => $depTotal,
            ];
        } elseif ($depositosCfg && ($depositosCfg['modo'] ?? '') === 'varios') {
            $depTotal = max(0.0, (float)$depositosCfg['total']);
            $cuotaDep = max(0.0, (float)$depositosCfg['cuota']);
            $plazoDep = max(0.0, (float)$depositosCfg['plazo']); // DECIMAL
            $fec0     = $depositosCfg['fecha_inicio'] ?? $fechaDeposito ?? $fechaInicio;

            $rest   = $depTotal;
            $fd     = Carbon::parse($fec0)->startOfDay();
            $nSlots = max(1, (int)ceil($plazoDep)); // ej. 3.5 -> 4 depósitos

            for ($i=1; $i <= $nSlots && $rest > 0.0; $i++) {
                $m = min($cuotaDep > 0 ? $cuotaDep : $rest, $rest);
                $depEventos[] = [
                    'tipo'  => 'deposito',
                    'fecha' => $fd->toDateString(),
                    'monto' => $m,
                ];
                $rest = $rest - $m;
                $fd   = $fd->copy()->addMonthNoOverflow();
            }
        }

        // === 3) Extras (permiten decimales) =====
        $sumaExtras = 0.0;
        $extrasEventos = [];
        foreach ($extrasDetallados as $ex) {
            $m = (float)($ex['monto'] ?? 0);
            if ($m <= 0) continue;
            $sumaExtras += $m;
            $fec = $ex['fecha'] ?? Carbon::parse($fechaInicio)->endOfMonth()->toDateString();
            $extrasEventos[] = [
                'tipo'  => 'extra',
                'fecha' => Carbon::parse($fec)->toDateString(),
                'label' => $ex['label'] ?? null,
                'monto' => $m,
            ];
        }

        // === 4) Capital que irá a planilla ===
        $capPlanillaBase = max(0.0, $monto - $depTotal - $sumaExtras);

        // === 5) Número de cuotas de planilla ===
        $maxCuotasPlanilla = max(0, (int)ceil($plazoMeses * $factorPeriodo));
        $nPlan = ($baseCapPeriodo > 0.0 && $capPlanillaBase > 0.0)
               ? (int)ceil($capPlanillaBase / $baseCapPeriodo)
               : 0;
        if ($maxCuotasPlanilla > 0) $nPlan = min($nPlan, $maxCuotasPlanilla);

        $totalInteresesPlan = ($nPlan > 0) ? $totalIntereses : 0.0;

        // === 6) Fechas de planilla ===
        $fechasPlanilla = [];
        if ($nPlan > 0) {
            if ($frecuencia === 'quincenal') {
                $f = $fIni->copy();
                for ($i=1; $i <= $nPlan; $i++) {
                    if ($f->day <= 15) { $f = $f->copy()->startOfMonth()->addDays(14); }
                    else { $f = $f->copy()->endOfMonth(); }
                    $fechasPlanilla[] = $f->toDateString();
                    if ($f->day === 15) { $f = $f->copy()->endOfMonth(); }
                    else { $f = $f->copy()->addMonthNoOverflow()->startOfMonth()->addDays(14); }
                }
            } elseif ($frecuencia === 'mensual') {
                $f = $fIni->copy();
                for ($i=1; $i <= $nPlan; $i++) { $fechasPlanilla[] = $f->toDateString(); $f = $f->copy()->addMonthNoOverflow(); }
            } elseif ($frecuencia === 'semanal') {
                $f = $fIni->copy();
                for ($i=1; $i <= $nPlan; $i++) { $fechasPlanilla[] = $f->toDateString(); $f = $f->copy()->addDays(7); }
            } else { // bisemanal 14 días
                $f = $fIni->copy();
                for ($i=1; $i <= $nPlan; $i++) { $fechasPlanilla[] = $f->toDateString(); $f = $f->copy()->addDays(14); }
            }
        }

        // === 7) Construir y ordenar eventos ===
        $eventos = [];
        foreach ($depEventos as $d)      $eventos[] = $d;                               // depósitos
        foreach ($fechasPlanilla as $d)  $eventos[] = ['tipo'=>'planilla','fecha'=>$d];
        foreach ($extrasEventos as $e)   $eventos[] = $e;

        usort($eventos, function ($a, $b) {
            if ($a['fecha'] === $b['fecha']) {
                $rank = ['deposito'=>0,'planilla'=>1,'extra'=>2];
                return ($rank[$a['tipo']] ?? 9) <=> ($rank[$b['tipo']] ?? 9);
            }
            return strcmp($a['fecha'], $b['fecha']);
        });

        // === 8) Insertar ===
        $epsilon = 1e-9;

        $saldoCapRestTotal = $monto;
        $saldoIntRestTotal = $totalInteresesPlan;
        $capPlanRest       = $capPlanillaBase;

        $intUnit  = ($nPlan > 0) ? ($totalInteresesPlan / $nPlan) : 0.0;
        $intResid = ($nPlan > 0) ? ($totalInteresesPlan - ($intUnit * ($nPlan - 1))) : 0.0;

        $saldoCapPag = 0.0;
        $saldoIntPag = 0.0;
        $num = 1; $planProcesadas = 0;

        foreach ($eventos as $ev) {
            if ($saldoCapRestTotal <= $epsilon && $saldoIntRestTotal <= $epsilon) break;

            $fecha = $ev['fecha'];
            $abCap = 0.0; $abInt = 0.0; $cuotaPeriodo = 0.0; $obs = null; $motivo = null;

            if ($ev['tipo'] === 'planilla') {
                if ($planProcesadas < $nPlan - 1) {
                    $abCap = min($baseCapPeriodo, $capPlanRest);
                    $abInt = min($intUnit,       $saldoIntRestTotal);
                } else {
                    $abCap = $capPlanRest;
                    $abInt = $saldoIntRestTotal;
                }
                $abCap = max(0.0, min($abCap, $saldoCapRestTotal));
                $abInt = max(0.0, min($abInt, $saldoIntRestTotal));

                $capPlanRest       = max(0.0, $capPlanRest - $abCap);
                $saldoIntRestTotal = max(0.0, $saldoIntRestTotal - $abInt);
                $motivo = 'PLANILLA';
                $planProcesadas++;

            } elseif ($ev['tipo'] === 'deposito') {
                $montoDep = (float)($ev['monto'] ?? 0.0);
                $abCap    = min($montoDep, $saldoCapRestTotal); // todo a capital
                $abInt    = 0.0;
                $obs      = 'Depósito directo a cuenta';
                $motivo   = 'DEPOSITO';

            } else { // extra
                $m = (float)($ev['monto'] ?? 0.0);
                $abCap  = min($m, $saldoCapRestTotal);
                $abInt  = 0.0;
                $obs    = 'Cobro extraordinario'.(!empty($ev['label']) ? (': '.$ev['label']) : '');
                $motivo = 'EXTRA';
            }

            $cuotaPeriodo = $abCap + $abInt;

            $saldoCapPag      += $abCap;
            $saldoIntPag      += $abInt;
            $saldoCapRestTotal = max(0.0, $saldoCapRestTotal - $abCap);

            DB::table('historial_cuotas')->insert([
                'id_prestamo'      => $idPrestamo,
                'num_cuota'        => $num,
                'fecha_programada' => $fecha,
                'abono_capital'    => $abCap,
                'abono_intereses'  => $abInt,
                'cuota_mensual'    => $cuotaCapitalMensual + $interesMensual, // referencial
                'cuota_quincenal'  => $cuotaPeriodo,
                'saldo_pagado'     => $saldoCapPag,
                'saldo_restante'   => $saldoCapRestTotal,
                'interes_pagado'   => $saldoIntPag,
                'interes_restante' => $saldoIntRestTotal,
                'ajuste'           => 0,
                'motivo'           => $motivo,
                'fecha_pago_real'  => null,
                'pagado'           => 0,
                'observaciones'    => $obs,
            ]);

            $num++;
        }
    }

    private function obtenerSaldoCapitalPendienteEmpleado(int $idEmpleado): float
    {
        $prestamo = DB::table('prestamo as p')
            ->where('p.id_empleado', $idEmpleado)
            ->where('p.estado_prestamo', 1)
            ->orderByDesc('p.id_prestamo')
            ->first();
        if (!$prestamo) return 0.0;

        $row = DB::table('historial_cuotas as hc')
            ->where('hc.id_prestamo', $prestamo->id_prestamo)
            ->where('hc.pagado', 1)
            ->orderByDesc('hc.id_historial_cuotas')
            ->first();

        if ($row && isset($row->saldo_restante)) { return (float)$row->saldo_restante; }
        return (float)$prestamo->monto;
    }

 private function aplicarRefinanciamiento(
    int $idPrestamoNuevo,
    int $idEmpleado,
    ?string $fechaPago = null,
    string $intTipo = 'todos',   // 'todos' | 'parcial' | 'ninguno'
    float $intMonto = 0.0        // usado si $intTipo === 'parcial'
): void
{
    DB::transaction(function () use ($idPrestamoNuevo, $idEmpleado, $fechaPago, $intTipo, $intMonto) {
        $fechaPago = $fechaPago ?: Carbon::now()->toDateString();

        // 1) Préstamo anterior activo (distinto del nuevo)
        $old = DB::table('prestamo')
            ->where('id_empleado', $idEmpleado)
            ->where('estado_prestamo', 1)
            ->where('id_prestamo', '<>', $idPrestamoNuevo)
            ->orderByDesc('fecha_deposito_prestamo')
            ->lockForUpdate()
            ->first();
        if (!$old) return;

        // 2) Sumas pendientes de cuotas NO pagadas
        $unpaid = DB::table('historial_cuotas')
            ->where('id_prestamo', $old->id_prestamo)
            ->where('pagado', 0)
            ->selectRaw('COALESCE(SUM(abono_capital),0) AS cap, COALESCE(SUM(abono_intereses),0) AS inte')
            ->first();

        $sumCapPend = round((float)($unpaid->cap ?? 0), 2);
        $sumIntPend = round((float)($unpaid->inte ?? 0), 2);
        if (($sumCapPend + $sumIntPend) <= 0) return;

        // 3) Intereses ya pagados (para el acumulado)
        $paid = DB::table('historial_cuotas')
            ->where('id_prestamo', $old->id_prestamo)
            ->where('pagado', 1)
            ->selectRaw('COALESCE(SUM(abono_capital),0) AS cap, COALESCE(SUM(abono_intereses),0) AS inte')
            ->first();
        $sumCapPagPrev = round((float)($paid->cap ?? 0), 2);
        $sumIntPagPrev = round((float)($paid->inte ?? 0), 2);

        // 4) Cuánto interés se paga en esta "cuota final"
        $intToPay = match ($intTipo) {
            'ninguno' => 0.0,
            'parcial' => max(0.0, min($sumIntPend, round($intMonto, 2))),
            default   => $sumIntPend, // 'todos'
        };
        $intTrasladado = round($sumIntPend - $intToPay, 2); // SOLO informativo

        // 5) Borrar cuotas pendientes
        DB::table('historial_cuotas')
            ->where('id_prestamo', $old->id_prestamo)
            ->where('pagado', 0)
            ->delete();

        // 6) Insertar la cuota única pagada
        $nextNum = (int)(DB::table('historial_cuotas')
            ->where('id_prestamo', $old->id_prestamo)
            ->max('num_cuota') ?? 0) + 1;

        $abCapFinal = $sumCapPend;
        $abIntFinal = $intToPay;
        $cuotaFinal = round($abCapFinal + $abIntFinal, 2);

        // Texto para observaciones (según selección)
        if ($intTipo === 'todos') {
            $obsFila = 'Cancelado con refinanciamiento | Intereses pagados: L '.number_format($abIntFinal, 2, '.', '');
        } elseif ($intTipo === 'parcial') {
            $obsFila = 'Cancelado con refinanciamiento | Intereses pagados parcialmente: L '
                     . number_format($abIntFinal, 2, '.', '')
                     . ' | Resto L '.number_format($intTrasladado, 2, '.', '')
                     . ' pagado en próximo préstamo';
        } else { // ninguno
            $obsFila = 'Cancelado con refinanciamiento | Intereses no pagados: L '
                     . number_format($sumIntPend, 2, '.', '')
                     . ' (pagados en próximo préstamo)';
        }

        DB::table('historial_cuotas')->insert([
            'id_prestamo'      => $old->id_prestamo,
            'num_cuota'        => $nextNum,
            'fecha_programada' => $fechaPago,
            'abono_capital'    => $abCapFinal,
            'abono_intereses'  => $abIntFinal,                                  // ← lo que realmente se paga
            'cuota_mensual'    => $cuotaFinal,
            'cuota_quincenal'  => $cuotaFinal,
            'saldo_pagado'     => round($sumCapPagPrev + $abCapFinal, 2),
            'saldo_restante'   => 0.00,
            'interes_pagado'   => round($sumIntPagPrev + $abIntFinal, 2),       // ← acumulado correcto
            'interes_restante' => 0.00,
            'ajuste'           => 0,
            'fecha_pago_real'  => $fechaPago,
            'motivo'           => 'REFINANCIAMIENTO',
            'pagado'           => 1,
            'observaciones'    => $obsFila,
        ]);

        // 7) Cerrar préstamo anterior (concatenando observación clara)
        $obsPrestamo = 'Cancelado con refinanciamiento (nuevo #'.$idPrestamoNuevo.')'
                     . ' | Cap: L '.number_format($abCapFinal, 2, '.', '');
        if ($intTipo === 'todos') {
            $obsPrestamo .= ' | Int pagados: L '.number_format($abIntFinal, 2, '.', '');
        } elseif ($intTipo === 'parcial') {
            $obsPrestamo .= ' | Int pagados parcial: L '.number_format($abIntFinal, 2, '.', '')
                          . ' | Resto L '.number_format($intTrasladado, 2, '.', '')
                          . ' pagado en próximo préstamo';
        } else {
            $obsPrestamo .= ' | Int no pagados: L '.number_format($sumIntPend, 2, '.', '')
                          . ' (pagados en próximo préstamo)';
        }

        DB::table('prestamo')
            ->where('id_prestamo', $old->id_prestamo)
            ->update([
                'estado_prestamo' => 0,
                'observaciones'   => DB::raw(
                    "CONCAT(COALESCE(observaciones,''),' | ".addslashes($obsPrestamo)."')"
                ),
            ]);
    });
}


    // ====== Helper: normaliza strings tipo "1.234,56", "L. 2,075.83", "$1,200" ======
    private function parseMoney($v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float)$v;
        if (!is_string($v)) return 0.0;

        $s = trim($v);
        $s = preg_replace('/\x{00A0}|\s/u', '', $s);      // quita espacios y NBSP
        $neg = str_contains($s, '-');                    // bandera negativo
        $s = preg_replace('/[^0-9\.,]/u', '', $s);       // deja solo dígitos, . y ,

        if ($s === '') return 0.0;

        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        $lastSep   = max($lastDot !== false ? $lastDot : -1, $lastComma !== false ? $lastComma : -1);

        if ($lastSep === -1) {
            $digits = preg_replace('/\D/', '', $s);
            $num = $digits === '' ? 0.0 : (float)$digits;
            return $neg ? -$num : $num;
        }

        $decPart   = substr($s, $lastSep + 1);
        $decDigits = preg_replace('/\D/', '', $decPart);
        $decCount  = strlen($decDigits);

        $allDigits = preg_replace('/\D/', '', $s);
        if ($allDigits === '') return 0.0;

        $num = $decCount > 0
            ? (float)$allDigits / (10 ** $decCount)
            : (float)$allDigits;

        return $neg ? -$num : $num;
    }
}