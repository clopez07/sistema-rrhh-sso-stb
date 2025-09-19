<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AjustesPrestamosController extends Controller
{
public function importExcel(Request $request)
{
    $request->validate([
        'archivo' => ['required','file','mimes:xlsx,xls'],
    ]);

    // 1) Cargar Excel en modo datos
    $path   = $request->file('archivo')->getRealPath();
    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($path);

    // 2) Elegir la hoja que realmente tiene "Código" y "Deducción"
    [$sheet, $hdrRow, $colCodigo, $colNombre, $colDeduccion] = $this->pickSheetWithHeaders($spreadsheet);
    if (!$sheet || !$hdrRow || !$colCodigo || !$colDeduccion) {
        return back()->with('error', 'No encontré una hoja con encabezados "Código" y "Deducción".');
    }
    $sheetName = $sheet->getTitle();

    // 3) Fechas A7/B7 tomadas de esta hoja
    $rawInicio = $sheet->getCell('A7')->getValue();
    $rawFin    = $sheet->getCell('B7')->getValue();
    $inicio = $this->normalizeExcelDate($rawInicio);
    $fin    = $this->normalizeExcelDate($rawFin);
    if (!$inicio || !$fin) {
        return back()->with('error', "No pude leer A7/B7 como fechas en la hoja '{$sheetName}'.");
    }

    // 4) Recorrer filas robustamente
    $escaneadas=0; $ok=0; $noMatch=0; $sinEmpleado=0; $saltadas=0; $parciales=0;
    $ejemplos = [];
    $emptyStreak = 0;

    for ($row = $hdrRow + 1; $row <= ($hdrRow + 5000) && $emptyStreak < 30; $row++) {
        $codigoAddr = Coordinate::stringFromColumnIndex($colCodigo).$row;
        $deducAddr  = Coordinate::stringFromColumnIndex($colDeduccion).$row;

        // Lee crudo/calculado sin formateo
        $codigoRaw = $this->readCellSafe($sheet, $codigoAddr, false);
        $deducRaw  = $this->readCellSafe($sheet, $deducAddr, true);

        $codigo    = trim((string)$codigoRaw);
        $deduccion = $this->toFloat($deducRaw); // normaliza "L. 2,075.83" => 2075.83

        // Contar filas vacías para cortar
        if ($codigo === '' && ($deducRaw === null || $deduccion <= 0)) {
            $emptyStreak++;
            continue;
        }
        $emptyStreak = 0; // hay algo en la fila

        // Estadística
        $escaneadas++;
        if (count($ejemplos) < 5) {
            $ejemplos[] = "{$codigo} | raw='{$deducRaw}' → norm={$deduccion}";
        }

        // Reglas de “saltada”
        if ($codigo === '') { $saltadas++; continue; }
        if ($deduccion <= 0) { $saltadas++; continue; }

        // Buscar empleado (tolerando ceros a la izquierda)
        $empleado = DB::table('empleado')->where('codigo_empleado', $codigo)->first();
        if (!$empleado && ctype_digit($codigo)) {
            $empleado = DB::table('empleado')->where('codigo_empleado', ltrim($codigo,'0'))->first();
        }
        if (!$empleado) { $sinEmpleado++; continue; }

        // Cuotas candidatas en rango, no pagadas, tipo planilla
        $cuotas = DB::table('historial_cuotas as hc')
            ->join('prestamo as p','p.id_prestamo','=','hc.id_prestamo')
            ->where('p.id_empleado', $empleado->id_empleado)
            ->whereBetween('hc.fecha_programada', [$inicio->toDateString(), $fin->toDateString()])
            ->where('hc.pagado', 0)
            ->where(function($q){
                $q->whereNull('hc.motivo')
                  ->orWhere('hc.motivo', '')
                  ->orWhere('hc.motivo', 'PLANILLA')
                  ->orWhere('hc.motivo', 'Planilla');
            })
            ->select(
                'hc.id_historial_cuotas',
                'hc.id_prestamo',
                'hc.num_cuota',
                'hc.cuota_quincenal',
                'hc.cuota_mensual',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.fecha_programada'
            )
            ->orderBy('hc.fecha_programada')
            ->get();

        // Intentar match/ajuste por monto
        $procesada = false;

        foreach ($cuotas as $c) {
            $montoEvento = !is_null($c->cuota_quincenal) ? (float)$c->cuota_quincenal
                        : (!is_null($c->cuota_mensual)   ? (float)$c->cuota_mensual
                        : ((float)$c->abono_capital + (float)$c->abono_intereses));

            // 4.1) Coincide ≈ a 2 decimales
            if ($this->moneyEq2($montoEvento, $deduccion)) {
                DB::table('historial_cuotas')
                    ->where('id_historial_cuotas', $c->id_historial_cuotas)
                    ->update([
                        'pagado'          => 1,
                        'fecha_pago_real' => $fin->toDateString(),
                        'observaciones'   => DB::raw(
                            "CONCAT(COALESCE(observaciones,''),' | Pagada por planilla (ajuste {$inicio->format('Y-m-d')} a {$fin->format('Y-m-d')})')"
                        ),
                    ]);
                $ok++; $procesada = true; break;
            }

            // 4.2) Pago PARCIAL: deducción MENOR al evento
            if ($this->roundMoney($deduccion) + 0.005 < $this->roundMoney($montoEvento)) {
                $diff = $this->roundMoney($montoEvento - $deduccion); // diferencia a trasladar

                // Desglose pagado en la cuota actual (prioriza intereses)
                $origCap = (float)$c->abono_capital;
                $origInt = (float)$c->abono_intereses;

                if ($origCap + $origInt <= 0) {
                    // Si no había desglose, todo a capital
                    $paidInt = 0.0;
                    $paidCap = $deduccion;
                } else {
                    $paidInt = min($origInt, $deduccion);
                    $paidCap = max(0.0, $deduccion - $paidInt);
                }

                // Campo de monto a actualizar en la fila actual
                $montoFieldActual = !is_null($c->cuota_quincenal) ? 'cuota_quincenal'
                                  : (!is_null($c->cuota_mensual)   ? 'cuota_mensual' : null);

                // 1) Actualizar cuota ACTUAL como pagada por el monto real
                $updateActual = [
                    'pagado'            => 1,
                    'fecha_pago_real'   => $fin->toDateString(),
                    'ajuste'            => 1,
                    'abono_capital'     => $this->roundMoney($paidCap),
                    'abono_intereses'   => $this->roundMoney($paidInt),
                    'observaciones'     => DB::raw(
                        "CONCAT(COALESCE(observaciones,''),' | Pago parcial: L ".number_format($deduccion,2,'.','')." (faltó L ".number_format($diff,2,'.','').") trasladado a próxima cuota')"
                    ),
                ];
                if ($montoFieldActual) {
                    $updateActual[$montoFieldActual] = $this->roundMoney($deduccion);
                }

                DB::table('historial_cuotas')
                    ->where('id_historial_cuotas', $c->id_historial_cuotas)
                    ->update($updateActual);

                // 2) Buscar la SIGUIENTE cuota no pagada del mismo préstamo
                $next = DB::table('historial_cuotas')
                    ->where('id_prestamo', $c->id_prestamo)
                    ->where('pagado', 0)
                    ->where('fecha_programada', '>', $c->fecha_programada)
                    ->orderBy('fecha_programada')
                    ->first();

                if ($next) {
                    $nextMonto = (float)($next->cuota_quincenal ?? $next->cuota_mensual ?? 0.0);
                    $nextMontoField = !is_null($next->cuota_quincenal) ? 'cuota_quincenal'
                                    : (!is_null($next->cuota_mensual)   ? 'cuota_mensual' : 'cuota_quincenal');

                    $newNextMonto = $this->roundMoney($nextMonto + $diff);
                    $newNextCap   = $this->roundMoney(((float)$next->abono_capital) + $diff); // diferencia a CAPITAL

                    DB::table('historial_cuotas')
                        ->where('id_historial_cuotas', $next->id_historial_cuotas)
                        ->update([
                            $nextMontoField     => $newNextMonto,
                            'abono_capital'     => $newNextCap,
                            'ajuste'            => 1,
                            'observaciones'     => DB::raw(
                                "CONCAT(COALESCE(observaciones,''),' | Ajuste: +L ".number_format($diff,2,'.','')." por parcial de cuota ID {$c->id_historial_cuotas}')"
                            ),
                        ]);
                } else {
                    // 3) Si no hay siguiente cuota, crear una nueva fila de AJUSTE
                    $nextNum = (int)(DB::table('historial_cuotas')
                        ->where('id_prestamo', $c->id_prestamo)
                        ->max('num_cuota') ?? 0) + 1;

                    $fechaAjuste = Carbon::parse($c->fecha_programada)->addDay()->toDateString();

                    DB::table('historial_cuotas')->insert([
                        'id_prestamo'      => $c->id_prestamo,
                        'num_cuota'        => $nextNum,
                        'fecha_programada' => $fechaAjuste,
                        'abono_capital'    => $this->roundMoney($diff),
                        'abono_intereses'  => 0.00,
                        'cuota_mensual'    => $this->roundMoney($diff),
                        'cuota_quincenal'  => $this->roundMoney($diff),
                        'saldo_pagado'     => null,
                        'saldo_restante'   => null,
                        'interes_pagado'   => null,
                        'interes_restante' => null,
                        'ajuste'           => 1,
                        'motivo'           => 'AJUSTE',
                        'fecha_pago_real'  => null,
                        'pagado'           => 0,
                        'observaciones'    => 'Ajuste por traslado de diferencia de cuota anterior (pago parcial)',
                    ]);
                }

                $ok++; $parciales++; $procesada = true; break;
            }

            // Si la deducción es mayor al evento, por ahora no se consume varias cuotas (comportamiento actual).
            // Si quieres el modo "greedy", lo implemento aparte.
        }

        if (!$procesada) { $noMatch++; }
    }

    $msg = "| Rango {$inicio->format('Y-m-d')} a {$fin->format('Y-m-d')} ".
           "| Filas leídas={$escaneadas}, Pagos Totales ={$ok} (Pagos Parciales={$parciales})".
           "| Sin coincidencia={$noMatch}, Sin empleado={$sinEmpleado}, Saltadas={$saltadas}";

    \Log::info('[AJUSTES PRESTAMOS] '.$msg);
    return back()->with('ajustes_msg', $msg);
}

private function pickSheetWithHeaders($spreadsheet): array
{
    // Recorre todas las hojas hasta encontrar una con "Código" y "Deducción"
    foreach ($spreadsheet->getWorksheetIterator() as $ws) {
        [$hdrRow, $colCodigo, $colNombre, $colDeduccion] = $this->detectHeaders($ws);
        if ($hdrRow && $colCodigo && $colDeduccion) {
            return [$ws, $hdrRow, $colCodigo, $colNombre, $colDeduccion];
        }
    }
    // Fallback: activa + detecta
    $ws = $spreadsheet->getActiveSheet();
    [$hdrRow, $colCodigo, $colNombre, $colDeduccion] = $this->detectHeaders($ws);
    return [$ws, $hdrRow, $colCodigo, $colNombre, $colDeduccion];
}

private function readCellSafe($sheet, string $addr, bool $preferCalc = true) {
    try {
        $cell = $sheet->getCell($addr);
        if ($preferCalc) {
            $v = $cell->getCalculatedValue();
            if ($v !== null && $v !== '') return $v;
        }
        return $cell->getValue();
    } catch (\Throwable $e) {
        try { return $sheet->getCell($addr)->getValue(); } catch (\Throwable $e2) { return null; }
    }
}

private function toFloat($v): float
{
    if ($v === null || $v === '') return 0.0;
    if (is_numeric($v)) return (float)$v;
    if (!is_string($v)) return 0.0;

    // 1) Limpieza básica
    $s = trim($v);
    // quita NBSP y espacios
    $s = preg_replace('/\x{00A0}|\s/u', '', $s);
    // bandera negativo
    $neg = str_contains($s, '-');
    // conserva solo dígitos, puntos y comas (fuera letras/símbolos como "L.", "$", etc.)
    $s = preg_replace('/[^0-9\.,]/u', '', $s);
    if ($s === '') return 0.0;

    // 2) Ubica el ÚLTIMO separador (decimal real)
    $lastDot   = strrpos($s, '.');
    $lastComma = strrpos($s, ',');
    $lastSepPos = max($lastDot !== false ? $lastDot : -1, $lastComma !== false ? $lastComma : -1);

    // 3) Reconstrucción robusta
    if ($lastSepPos === -1) {
        // sin separadores: solo dígitos
        $digits = preg_replace('/\D/', '', $s);
        $num = $digits === '' ? 0.0 : (float)$digits;
        return $neg ? -$num : $num;
    }

    // dígitos después del último separador = cantidad de decimales
    $decPart   = substr($s, $lastSepPos + 1);
    $decDigits = preg_replace('/\D/', '', $decPart);
    $decCount  = strlen($decDigits);

    // todos los dígitos del string
    $allDigits = preg_replace('/\D/', '', $s);
    if ($allDigits === '') return 0.0;

    $num = $decCount > 0
        ? (float)$allDigits / (10 ** $decCount)
        : (float)$allDigits;

    return $neg ? -$num : $num;
}

private function roundMoney(float $v): float { return round($v + 1e-9, 2); }
private function moneyEq2($a, $b): bool { return abs($this->roundMoney($a) - $this->roundMoney($b)) < 0.005; }

private function normalizeExcelDate($val): ?\Carbon\Carbon
{
    if ($val instanceof \DateTimeInterface) return \Carbon\Carbon::instance($val);
    if (is_numeric($val)) {
        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val);
        return \Carbon\Carbon::instance($date);
    }
    try { return \Carbon\Carbon::parse($val); } catch (\Throwable $e) { return null; }
}

private function detectHeaders($sheet): array
{
    for ($r=1; $r<=30; $r++) {
        $map = [];
        for ($c=1; $c<=20; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $v  = $sheet->getCell($colLetter.$r)->getValue();
            $vv = is_string($v) ? mb_strtolower(trim($v)) : '';
            if ($vv === 'codigo' || $vv === 'código' || $vv === 'codigo empleado' || $vv === 'código empleado') { $map['codigo'] = $c; }
            if (str_contains($vv, 'deduc')) { $map['deduccion'] = $c; }
            if (str_contains($vv, 'nombre')) { $map['nombre'] = $c; }
        }
        if (isset($map['codigo']) && isset($map['deduccion'])) {
            return [$r, $map['codigo'], $map['nombre'] ?? null, $map['deduccion']];
        }
    }
    return [null, null, null, null];
}

}