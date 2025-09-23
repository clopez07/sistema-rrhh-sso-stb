<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrestamoRenunciaController extends Controller
{
    // Pantalla de búsqueda + vista previa de un préstamo
    public function form(Request $request)
{
    $q           = trim((string) $request->input('q', ''));
    $idPrestamo  = (int) $request->input('id_prestamo', 0);

    // 1) SIEMPRE poblar datalist sin filtrar por q
    $prestamosList = DB::table('prestamo as p')
        ->join('empleado as e', 'e.id_empleado', '=', 'p.id_empleado')
        ->leftJoin('planilla as pl', 'pl.id_planilla', '=', 'p.id_planilla')
        ->select(
            'p.id_prestamo','p.num_prestamo',
            'e.nombre_completo','e.codigo_empleado'
        )
        ->orderBy('p.estado_prestamo','desc')
        ->orderBy('p.id_prestamo','desc')
        ->limit(500)
        ->get();

    // 2) Cargar selección si viene id_prestamo (o si quieres, intentar detectar por q)
    $seleccion = null; $historial = []; $resumen = null;

    if ($idPrestamo > 0) {
        $seleccion = DB::table('prestamo as p')
            ->join('empleado as e','e.id_empleado','=','p.id_empleado')
            ->leftJoin('planilla as pl','pl.id_planilla','=','p.id_planilla')
            ->select('p.*','e.nombre_completo','e.codigo_empleado','e.identidad','pl.planilla')
            ->where('p.id_prestamo', $idPrestamo)
            ->first();

        if ($seleccion) {
            $historial = DB::table('historial_cuotas')
                ->where('id_prestamo', $idPrestamo)
                ->orderBy('fecha_programada')
                ->orderBy('id_historial_cuotas')
                ->get();

            $resumen = $this->getResumenPendiente($idPrestamo);
        }
    }

    return view('prestamos.renuncia', [
        'prestamosList' => $prestamosList,   // ⬅️ usa este nombre en la vista
        'seleccion'     => $seleccion,
        'historial'     => $historial,
        'resumen'       => $resumen,
        'q'             => $q,
        'id_prestamo'   => $idPrestamo,
    ]);
}

    // Confirma: borra cuotas no pagadas e inserta una única cuota final
    public function confirmar(Request $request)
    {
        $request->validate([
            'id_prestamo'   => ['required','integer','min:1'],
            'origen'        => ['required','in:prestaciones,liquidacion'],
            'fecha_final'   => ['nullable','date'],
            'cap_final'     => ['required','numeric','min:0'],
            'int_final'     => ['required','numeric','min:0'],
            'cerrar'        => ['nullable','boolean'],
        ]);

        $idPrestamo = (int) $request->input('id_prestamo');
        $origen     = $request->input('origen'); // prestaciones | liquidacion
        $fechaFinal = $request->input('fecha_final') ?: Carbon::now()->toDateString();
        $capFinal   = round((float)$request->input('cap_final'), 2);
        $intFinal   = round((float)$request->input('int_final'), 2);
        $cerrar     = (bool)$request->boolean('cerrar');

        DB::transaction(function () use ($idPrestamo, $origen, $fechaFinal, $capFinal, $intFinal, $cerrar) {
            // Lock del préstamo
            $p = DB::table('prestamo')->where('id_prestamo', $idPrestamo)->lockForUpdate()->first();
            if (!$p) abort(404, 'Préstamo no encontrado');

            // Recalcular pendientes por seguridad
            $pend = DB::table('historial_cuotas')
                ->where('id_prestamo', $idPrestamo)
                ->where('pagado', 0)
                ->selectRaw('COALESCE(SUM(abono_capital),0) AS cap, COALESCE(SUM(abono_intereses),0) AS inte, COUNT(*) AS n')
                ->first();

            // Borramos cuotas no pagadas
            DB::table('historial_cuotas')
                ->where('id_prestamo', $idPrestamo)
                ->where('pagado', 0)
                ->delete();

            // Sumar pagadas previas (para acumulados)
            $prev = DB::table('historial_cuotas')
                ->where('id_prestamo', $idPrestamo)
                ->where('pagado', 1)
                ->selectRaw('COALESCE(SUM(abono_capital),0) AS cap, COALESCE(SUM(abono_intereses),0) AS inte')
                ->first();

            $sumCapPagPrev = round((float)($prev->cap ?? 0), 2);
            $sumIntPagPrev = round((float)($prev->inte ?? 0), 2);

            // num_cuota siguiente
            $nextNum = (int)(DB::table('historial_cuotas')
                ->where('id_prestamo', $idPrestamo)
                ->max('num_cuota') ?? 0) + 1;

            $cuotaFinal = round($capFinal + $intFinal, 2);
            $obs = 'Pagar de '.($origen === 'prestaciones' ? 'Prestaciones' : 'Liquidación');

            // ¿se marca pagado o se deja pendiente?
            $pagadoFlag = $cerrar ? 1 : 0;
            $motivo     = 'RENUNCIA';

            DB::table('historial_cuotas')->insert([
                'id_prestamo'      => $idPrestamo,
                'num_cuota'        => $nextNum,
                'fecha_programada' => $fechaFinal,
                'abono_capital'    => $capFinal,
                'abono_intereses'  => $intFinal,
                'cuota_mensual'    => $cuotaFinal,
                'cuota_quincenal'  => $cuotaFinal,
                'saldo_pagado'     => $sumCapPagPrev + ($pagadoFlag ? $capFinal : 0),
                'saldo_restante'   => $pagadoFlag ? 0.00 : max(0.00, ($p->monto - $sumCapPagPrev - $capFinal)),
                'interes_pagado'   => $sumIntPagPrev + ($pagadoFlag ? $intFinal : 0),
                'interes_restante' => $pagadoFlag ? 0.00 : max(0.00, ($p->total_intereses - $sumIntPagPrev - $intFinal)),
                'ajuste'           => 0,
                'fecha_pago_real'  => $pagadoFlag ? $fechaFinal : null,
                'motivo'           => $motivo,
                'pagado'           => $pagadoFlag,
                'observaciones'    => $obs,
            ]);

            // Si se cierra, marcar préstamo inactivo y anotar observación
            if ($cerrar) {
                $obsPrestamo = "Cancelado por renuncia | ".$obs." | Cap: L ".number_format($capFinal,2,'.','')
                             ." | Int: L ".number_format($intFinal,2,'.','');
                DB::table('prestamo')
                    ->where('id_prestamo', $idPrestamo)
                    ->update([
                        'estado_prestamo' => 0,
                        'observaciones'   => DB::raw("CONCAT(COALESCE(observaciones,''),' | ".addslashes($obsPrestamo)."')"),
                    ]);
            } else {
                // Si no se cierra, agregar solo observación:
                $obsPrestamo = "Renuncia: pendiente de cobrar ".$obs
                             ." (Cap L ".number_format($capFinal,2,'.','')
                             .", Int L ".number_format($intFinal,2,'.','').")";
                DB::table('prestamo')
                    ->where('id_prestamo', $idPrestamo)
                    ->update([
                        'observaciones' => DB::raw("CONCAT(COALESCE(observaciones,''),' | ".addslashes($obsPrestamo)."')"),
                    ]);
            }
        });

        return redirect()
            ->route('prestamos.renuncia.form', ['id_prestamo' => $idPrestamo])
            ->with('success', 'Se generó la cuota final por renuncia correctamente.');
    }

    private function getResumenPendiente(int $idPrestamo): array
    {
        $unpaid = DB::table('historial_cuotas')
            ->where('id_prestamo', $idPrestamo)
            ->where('pagado', 0)
            ->selectRaw('COALESCE(SUM(abono_capital),0) AS cap, COALESCE(SUM(abono_intereses),0) AS inte, COUNT(*) AS n')
            ->first();

        $paid = DB::table('historial_cuotas')
            ->where('id_prestamo', $idPrestamo)
            ->where('pagado', 1)
            ->selectRaw('COALESCE(SUM(abono_capital),0) AS cap, COALESCE(SUM(abono_intereses),0) AS inte')
            ->first();

        return [
            'pend_cap'   => round((float)($unpaid->cap ?? 0), 2),
            'pend_int'   => round((float)($unpaid->inte ?? 0), 2),
            'pend_cuotas'=> (int)($unpaid->n ?? 0),
            'pag_cap'    => round((float)($paid->cap ?? 0), 2),
            'pag_int'    => round((float)($paid->inte ?? 0), 2),
        ];
    }
}
