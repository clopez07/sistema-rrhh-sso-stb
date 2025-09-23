<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamosResumenController extends Controller
{
    // Tabla de 12 meses para un año (vacía si no hay filas guardadas)
    public function index(Request $request)
    {
        $anio = (int)($request->query('anio', date('Y')));

        // Trae lo guardado para ese año y lo coloca por mes
        $guardados = DB::table('resumen_mensual')
            ->where('anio', $anio)
            ->get()
            ->keyBy('mes');

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[$m] = $guardados->get($m);
        }

        // Totales anuales
        $totales = DB::table('resumen_mensual')
            ->selectRaw('
                COALESCE(SUM(capital),0)          as capital,
                COALESCE(SUM(interes),0)          as interes,
                COALESCE(SUM(planilla_total),0)   as planilla_total,
                COALESCE(SUM(extras_total),0)     as extras_total,
                COALESCE(SUM(depositos_total),0)  as depositos_total,
                COALESCE(SUM(total_mensual),0)    as total_mensual
            ')
            ->where('anio', $anio)
            ->first();

        return view('prestamos.resumen_mensual_guardado', compact('anio', 'meses', 'totales'));
    }

    public function store(Request $request)
{
    $request->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date',
        'mes'   => 'required|integer|min:1|max:12',
        'anio'  => 'required|integer|min:2000|max:2100',
    ]);

    $desde = $request->input('desde');
    $hasta = $request->input('hasta');
    if ($desde > $hasta) { [$desde, $hasta] = [$hasta, $desde]; }

    $mes  = (int)$request->input('mes');
    $anio = (int)$request->input('anio');

    // === SOLO cuotas pagadas ===
    $payload = $this->calcularPayload($desde, $hasta);
    $payload['rango_inicio'] = $desde;
    $payload['rango_fin']    = $hasta;

    DB::table('resumen_mensual')->updateOrInsert(
        ['anio' => $anio, 'mes' => $mes],
        $payload
    );

    return redirect()
        ->route('prestamos.resumen.index', ['anio' => $anio])
        ->with('success', 'Resumen guardado para '.$this->mesLabel($mes).'-'.$anio);
}

/** Recalcula un mes usando su rango guardado (o un rango nuevo si se envía). */
public function recalcular(Request $request)
{
    $request->validate([
        'anio' => 'required|integer|min:2000|max:2100',
        'mes'  => 'required|integer|min:1|max:12',
        'desde' => 'nullable|date',
        'hasta' => 'nullable|date',
    ]);

    $anio = (int)$request->input('anio');
    $mes  = (int)$request->input('mes');

    $row = DB::table('resumen_mensual')->where(['anio'=>$anio,'mes'=>$mes])->first();
    if (!$row) {
        return back()->with('error', 'No existe resumen guardado para ese mes.');
    }

    // Usa rango guardado, salvo que se envíe uno nuevo
    $desde = $request->input('desde', $row->rango_inicio);
    $hasta = $request->input('hasta', $row->rango_fin);
    if ($desde > $hasta) { [$desde, $hasta] = [$hasta, $desde]; }
    
    $payload = $this->calcularPayload($desde, $hasta);
    $payload['rango_inicio'] = $desde;
    $payload['rango_fin']    = $hasta;

    DB::table('resumen_mensual')
        ->where(['anio'=>$anio,'mes'=>$mes])
        ->update($payload);

    return redirect()
        ->route('prestamos.resumen.index', ['anio' => $anio])
        ->with('success', 'Resumen recalculado para '.$this->mesLabel($mes).'-'.$anio);
}

/** Elimina el resumen del mes seleccionado. */
public function destroy(Request $request)
{
    $request->validate([
        'anio' => 'required|integer|min:2000|max:2100',
        'mes'  => 'required|integer|min:1|max:12',
    ]);

    $anio = (int)$request->input('anio');
    $mes  = (int)$request->input('mes');

    DB::table('resumen_mensual')->where(['anio'=>$anio,'mes'=>$mes])->delete();

    return redirect()
        ->route('prestamos.resumen.index', ['anio' => $anio])
        ->with('success', 'Resumen eliminado para '.$this->mesLabel($mes).'-'.$anio);
}

/** -------- Helpers ---------- */
private function calcularPayload(string $desde, string $hasta): array
{
    // PLANILLA
    $planilla = DB::table('historial_cuotas as hc')
        ->where('hc.motivo', 'PLANILLA')
        ->where('hc.pagado', 1)
        ->whereBetween('hc.fecha_programada', [$desde, $hasta])
        ->selectRaw('COALESCE(SUM(hc.abono_capital),0) as capital, COALESCE(SUM(hc.abono_intereses),0) as interes')
        ->first();

    $capitalPlanilla = (float)($planilla->capital ?? 0);
    $interesPlanilla = (float)($planilla->interes ?? 0);
    $planillaTotal   = $capitalPlanilla + $interesPlanilla;

    // DEPOSITOS
    $depositosTotal = (float) DB::table('historial_cuotas as hc')
        ->where('hc.motivo', 'DEPOSITO')
        ->where('hc.pagado', 1)
        ->whereBetween('hc.fecha_programada', [$desde, $hasta])
        ->sum(DB::raw('COALESCE(hc.abono_capital,0)+COALESCE(hc.abono_intereses,0)'));

    $depDetalle = DB::table('historial_cuotas as hc')
        ->join('prestamo as p', 'p.id_prestamo', '=', 'hc.id_prestamo')
        ->join('empleado as e', 'e.id_empleado', '=', 'p.id_empleado')
        ->where('hc.motivo', 'DEPOSITO')
        ->where('hc.pagado', 1)
        ->whereBetween('hc.fecha_programada', [$desde, $hasta])
        ->groupBy('e.id_empleado', 'e.nombre_completo')
        ->selectRaw('e.nombre_completo as empleado, COALESCE(SUM(COALESCE(hc.abono_capital,0)+COALESCE(hc.abono_intereses,0)),0) as monto')
        ->get()
        ->map(fn($r) => ['empleado' => $r->empleado, 'monto' => (float)$r->monto])
        ->values()
        ->all();

    // EXTRAS
    $extrasTotal = (float) DB::table('historial_cuotas as hc')
        ->where('hc.motivo', 'EXTRA')
        ->where('hc.pagado', 1)
        ->whereBetween('hc.fecha_programada', [$desde, $hasta])
        ->sum(DB::raw('COALESCE(hc.abono_capital,0)+COALESCE(hc.abono_intereses,0)'));

    $exDetalle = DB::table('historial_cuotas as hc')
        ->join('prestamo as p', 'p.id_prestamo', '=', 'hc.id_prestamo')
        ->join('empleado as e', 'e.id_empleado', '=', 'p.id_empleado')
        ->where('hc.motivo', 'EXTRA')
        ->where('hc.pagado', 1)
        ->whereBetween('hc.fecha_programada', [$desde, $hasta])
        ->groupBy('e.id_empleado', 'e.nombre_completo', 'hc.observaciones', 'p.causa')
        ->selectRaw('e.nombre_completo as empleado,
                     COALESCE(SUM(COALESCE(hc.abono_capital,0)+COALESCE(hc.abono_intereses,0)),0) as monto,
                     COALESCE(NULLIF(hc.observaciones,""), p.causa) as label')
        ->get()
        ->map(fn($r) => ['empleado' => $r->empleado, 'monto' => (float)$r->monto, 'label' => $r->label])
        ->values()
        ->all();

    $totalMensual = $planillaTotal + $depositosTotal + $extrasTotal;

    return [
        'capital'           => $capitalPlanilla,
        'interes'           => $interesPlanilla,
        'planilla_total'    => $planillaTotal,
        'extras_total'      => $extrasTotal,
        'depositos_total'   => $depositosTotal,
        'total_mensual'     => $totalMensual,
        'extras_detalle'    => json_encode($exDetalle, JSON_UNESCAPED_UNICODE),
        'depositos_detalle' => json_encode($depDetalle, JSON_UNESCAPED_UNICODE),
    ];
}

private function mesLabel(int $m): string
{
    $map = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
    return $map[$m] ?? (string)$m;
}

}