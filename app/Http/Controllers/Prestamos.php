<?php

namespace App\Http\Controllers;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as SheetDrawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Prestamos extends Controller
{
public function empleadosprestamo(Request $request)
{
    $search = trim((string) $request->input('search', ''));

    $empleadosprestamo = DB::table('empleado_prestamo as ep')
        ->join('empleado as emp', 'ep.id_empleado', '=', 'emp.id_empleado')
        ->join('prestamo as p', 'ep.id_prestamo', '=', 'p.id_prestamo')
        ->select(
            'p.id_prestamo',  
            'emp.codigo_empleado',
            'emp.nombre_completo',
            'p.num_prestamo',
            'p.monto',
            'p.total_intereses',

            // Total a pagar (capital + intereses)
            DB::raw('(p.monto + p.total_intereses) as total_pagado'),

            // ⬇️ Fecha de inicio: primera cuota programada (fallback a fecha de depósito original)
            DB::raw("COALESCE((
                SELECT MIN(hc.fecha_programada)
                FROM historial_cuotas hc
                WHERE hc.id_prestamo = p.id_prestamo
            ), p.fecha_deposito_prestamo) as fecha_deposito_prestamo"),

            // Fecha final: última cuota programada
            DB::raw("(
                SELECT MAX(hc.fecha_programada) 
                FROM historial_cuotas hc 
                WHERE hc.id_prestamo = p.id_prestamo
            ) as fecha_final"),

            DB::raw("CASE WHEN p.estado_prestamo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado_prestamo"),

            // Totales con el último registro pagado
            DB::raw("COALESCE((
                SELECT hc.saldo_pagado
                FROM historial_cuotas hc
                WHERE hc.id_prestamo = p.id_prestamo
                  AND hc.id_historial_cuotas = (
                      SELECT MAX(hc2.id_historial_cuotas)
                      FROM historial_cuotas hc2
                      WHERE hc2.id_prestamo = p.id_prestamo
                        AND hc2.pagado = 1
                  )
                LIMIT 1
            ), 0) as total_capital_pagado"),

            DB::raw("COALESCE((
                SELECT hc.interes_pagado
                FROM historial_cuotas hc
                WHERE hc.id_prestamo = p.id_prestamo
                  AND hc.id_historial_cuotas = (
                      SELECT MAX(hc2.id_historial_cuotas)
                      FROM historial_cuotas hc2
                      WHERE hc2.id_prestamo = p.id_prestamo
                        AND hc2.pagado = 1
                  )
                LIMIT 1
            ), 0) as total_intereses_pagados"),

            DB::raw("COALESCE((
                SELECT hc.saldo_restante
                FROM historial_cuotas hc
                WHERE hc.id_prestamo = p.id_prestamo
                  AND hc.id_historial_cuotas = (
                      SELECT MAX(hc2.id_historial_cuotas)
                      FROM historial_cuotas hc2
                      WHERE hc2.id_prestamo = p.id_prestamo
                        AND hc2.pagado = 1
                  )
                LIMIT 1
            ), p.monto) as saldo_capital_pendiente"),

            DB::raw("COALESCE((
                SELECT hc.interes_restante
                FROM historial_cuotas hc
                WHERE hc.id_prestamo = p.id_prestamo
                  AND hc.id_historial_cuotas = (
                      SELECT MAX(hc2.id_historial_cuotas)
                      FROM historial_cuotas hc2
                      WHERE hc2.id_prestamo = p.id_prestamo
                        AND hc2.pagado = 1
                  )
                LIMIT 1
            ), p.total_intereses) as saldo_intereses_pendiente")
        )
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('emp.nombre_completo', 'like', "%{$search}%")
                  ->orWhere('emp.codigo_empleado', 'like', "%{$search}%")
                  ->orWhere('p.num_prestamo', 'like', "%{$search}%");
            });
        })
        ->orderBy('emp.nombre_completo')
        ->paginate(10)
        ->appends(['search' => $search]);

    return view('prestamos.empleadosprestamo', compact('empleadosprestamo'));
}

 public function exportEmpleadosPrestamo(Request $request)
    {
        $search = trim((string)$request->input('search', ''));

        // === MISMA CONSULTA QUE LA VISTA ===
        $rows = DB::table('empleado_prestamo as ep')
            ->join('empleado as emp', 'ep.id_empleado', '=', 'emp.id_empleado')
            ->join('prestamo as p', 'ep.id_prestamo', '=', 'p.id_prestamo')
            ->select(
                'p.num_prestamo',
                'emp.codigo_empleado',
                'emp.nombre_completo',
                'p.monto',
                'p.total_intereses',

                DB::raw('(p.monto + p.total_intereses) as total_pagado'),

                // Fecha inicio: primera cuota programada (fallback a fecha depósito)
                DB::raw("COALESCE((
                    SELECT MIN(hc.fecha_programada)
                    FROM historial_cuotas hc
                    WHERE hc.id_prestamo = p.id_prestamo
                ), p.fecha_deposito_prestamo) as fecha_inicio_prestamo"),

                // Fecha final: última cuota programada
                DB::raw("(
                    SELECT MAX(hc.fecha_programada)
                    FROM historial_cuotas hc
                    WHERE hc.id_prestamo = p.id_prestamo
                ) as fecha_final_prestamo"),

                DB::raw("CASE WHEN p.estado_prestamo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado_prestamo"),

                DB::raw("COALESCE((
                    SELECT hc.saldo_pagado
                    FROM historial_cuotas hc
                    WHERE hc.id_prestamo = p.id_prestamo
                      AND hc.id_historial_cuotas = (
                          SELECT MAX(hc2.id_historial_cuotas)
                          FROM historial_cuotas hc2
                          WHERE hc2.id_prestamo = p.id_prestamo
                            AND hc2.pagado = 1
                      )
                    LIMIT 1
                ), 0) as total_capital_pagado"),

                DB::raw("COALESCE((
                    SELECT hc.interes_pagado
                    FROM historial_cuotas hc
                    WHERE hc.id_prestamo = p.id_prestamo
                      AND hc.id_historial_cuotas = (
                          SELECT MAX(hc2.id_historial_cuotas)
                          FROM historial_cuotas hc2
                          WHERE hc2.id_prestamo = p.id_prestamo
                            AND hc2.pagado = 1
                      )
                    LIMIT 1
                ), 0) as total_intereses_pagados"),

                DB::raw("COALESCE((
                    SELECT hc.saldo_restante
                    FROM historial_cuotas hc
                    WHERE hc.id_prestamo = p.id_prestamo
                      AND hc.id_historial_cuotas = (
                          SELECT MAX(hc2.id_historial_cuotas)
                          FROM historial_cuotas hc2
                          WHERE hc2.id_prestamo = p.id_prestamo
                            AND hc2.pagado = 1
                      )
                    LIMIT 1
                ), p.monto) as saldo_capital_pendiente"),

                DB::raw("COALESCE((
                    SELECT hc.interes_restante
                    FROM historial_cuotas hc
                    WHERE hc.id_prestamo = p.id_prestamo
                      AND hc.id_historial_cuotas = (
                          SELECT MAX(hc2.id_historial_cuotas)
                          FROM historial_cuotas hc2
                          WHERE hc2.id_prestamo = p.id_prestamo
                            AND hc2.pagado = 1
                      )
                    LIMIT 1
                ), p.total_intereses) as saldo_intereses_pendiente")
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('emp.nombre_completo', 'like', "%{$search}%")
                      ->orWhere('emp.codigo_empleado', 'like', "%{$search}%")
                      ->orWhere('p.num_prestamo', 'like', "%{$search}%");
                });
            })
            ->orderBy('emp.nombre_completo')
            ->get();

        // === CONSTRUCCIÓN DEL EXCEL ===
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prestamos por Empleado');

        // Colores (coinciden con tu vista)
        $headerBG   = 'F9FAFB'; // bg-gray-50
        $cyanBG     = '77D2E6'; // celdas Capital/Intereses/Total a Pagar
        $purpleBG   = 'E1BBED'; // totales y saldos
        $amberBG    = 'E8C47D'; // fechas
        $greenRow   = 'BBF7D0'; // bg-green-200
        $redRow     = 'FECACA'; // bg-red-200
        $borderRGB  = '000000';

        // Título con logo
        $logoPath = public_path('img/logo.PNG'); // ajusta si tu ruta es otra
        if (is_file($logoPath)) {
            $drawing = new SheetDrawing();
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(52);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($sheet);
        }

        // Títulos
        $sheet->mergeCells('B1:M1');
        $sheet->setCellValue('B1', 'SERVICE AND TRADING BUSINESS'); // si quieres "TRAIDING", cambia aquí
        $sheet->mergeCells('B2:M2');
        $sheet->setCellValue('B2', 'RESUMEN DE PRESTAMOS POR EMPLEADO');

        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('B1:B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados
        $headers = [
            'Número de Prestamo',
            'Código de Empleado',
            'Nombre Completo',
            'Capital',
            'Intereses',
            'Total a Pagar',
            'Total Capital Pagado',
            'Total Intereses Pagado',
            'Saldo a Capital',
            'Saldo a Intereses',
            'Fecha Inicio Prestamo',
            'Fecha Final Prestamo',
            'Estado del Prestamo',
        ];
        $headerRow = 4;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        // Estilo encabezados
        $sheet->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderRGB]]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerBG]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        // Datos
        $dataStart = $headerRow + 1;
        $r = $dataStart;

        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", $row->num_prestamo);
            $sheet->setCellValue("B{$r}", $row->codigo_empleado);
            $sheet->setCellValue("C{$r}", $row->nombre_completo);

            $sheet->setCellValue("D{$r}", (float) $row->monto);
            $sheet->setCellValue("E{$r}", (float) $row->total_intereses);
            $sheet->setCellValue("F{$r}", (float) $row->total_pagado);

            $sheet->setCellValue("G{$r}", (float) $row->total_capital_pagado);
            $sheet->setCellValue("H{$r}", (float) $row->total_intereses_pagados);
            $sheet->setCellValue("I{$r}", (float) $row->saldo_capital_pendiente);
            $sheet->setCellValue("J{$r}", (float) $row->saldo_intereses_pendiente);

            // Fechas a serial Excel si vienen como 'Y-m-d'
            $kDate = \DateTime::createFromFormat('Y-m-d', (string)$row->fecha_inicio_prestamo);
            $lDate = \DateTime::createFromFormat('Y-m-d', (string)$row->fecha_final_prestamo);

            if ($kDate) {
                $sheet->setCellValue("K{$r}", ExcelDate::PHPToExcel($kDate));
            } else {
                $sheet->setCellValue("K{$r}", (string)$row->fecha_inicio_prestamo);
            }
            if ($lDate) {
                $sheet->setCellValue("L{$r}", ExcelDate::PHPToExcel($lDate));
            } else {
                $sheet->setCellValue("L{$r}", (string)$row->fecha_final_prestamo);
            }

            $sheet->setCellValue("M{$r}", $row->estado_prestamo);

            // === Estilos de fila (igual que la vista) ===
            $isActivo = strtolower(trim($row->estado_prestamo ?? '')) === 'activo';
            $rowBG = $isActivo ? $greenRow : $redRow;

            // Color base a toda la fila
            $sheet->getStyle("A{$r}:M{$r}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBG]],
            ]);

            // Overrides por columna (como en tu vista):
            // D,E,F -> cyan
            $sheet->getStyle("D{$r}:F{$r}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $cyanBG]],
            ]);
            // G,H,I,J -> purple
            $sheet->getStyle("G{$r}:J{$r}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $purpleBG]],
            ]);
            // K,L -> amber
            $sheet->getStyle("K{$r}:L{$r}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $amberBG]],
            ]);

            // Bordes de la fila
            $sheet->getStyle("A{$r}:M{$r}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderRGB]]],
            ]);

            $r++;
        }

        $lastRow = max($dataStart, $r - 1);

        // Formatos numéricos y fechas
        if ($lastRow >= $dataStart) {
            $sheet->getStyle("D{$dataStart}:J{$lastRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K{$dataStart}:L{$lastRow}")
                ->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle("A{$dataStart}:A{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$dataStart}:M{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // AutoFilter, congelar encabezado y auto-size
        $sheet->setAutoFilter("A{$headerRow}:M{$lastRow}");
        $sheet->freezePane("A" . ($headerRow + 1));

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Impresión
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.25)->setLeft(0.25)->setBottom(0.5);

        // Descargar
        $filename = 'resumen_prestamos_' . now()->format('Ymd_His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            // Limpia buffers para evitar corrupción del archivo
            if (ob_get_length() > 0) { @ob_end_clean(); }
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function cuotas(Request $request)
    {
        $cuotas = DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'hc.id_prestamo', '=', 'p.id_prestamo')
            ->join('planilla as pla', 'p.id_planilla', '=', 'pla.id_planilla')
            ->join('empleado as emp', 'p.id_empleado', '=', 'emp.id_empleado')
            ->select(
                'hc.id_historial_cuotas',
                'p.id_prestamo',
                'p.num_prestamo',
                'p.fecha_deposito_prestamo',
                'hc.fecha_pago_real',
                'emp.nombre_completo',
                'emp.identidad',
                'emp.codigo_empleado',
                'hc.num_cuota',
                'hc.fecha_programada',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.cuota_mensual',
                'hc.cuota_quincenal',
                'hc.saldo_pagado',
                'hc.saldo_restante',
                'hc.interes_pagado',
                'hc.interes_restante',
                'hc.pagado',
                'hc.observaciones',
                'p.id_planilla',
            )
            ->when($request->search, function ($query, $search) {
                return $query->where('emp.nombre_completo', 'like', "%{$search}%")
                            ->orWhere('p.id_prestamo', 'like', "%{$search}%")
                            ->orWhere('emp.codigo_empleado', 'like', "%{$search}%")
                            ->orWhere('hc.num_cuota', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $planilla = DB::select('CALL sp_obtener_planilla()');
        return view('prestamos.cuotas', compact('cuotas', 'planilla'));
    }

    public function cuotasEspeciales(Request $request)
    {
        $cuotas = DB::table('historial_cuotas as hc')
            ->join('prestamo as p', 'hc.id_prestamo', '=', 'p.id_prestamo')
            ->join('planilla as pla', 'p.id_planilla', '=', 'pla.id_planilla')
            ->join('empleado as emp', 'p.id_empleado', '=', 'emp.id_empleado')
            ->select(
                'hc.id_historial_cuotas',
                'p.id_prestamo',
                'p.num_prestamo',
                'p.fecha_deposito_prestamo',
                'hc.fecha_pago_real',
                'emp.nombre_completo',
                'emp.identidad',
                'emp.codigo_empleado',
                'hc.num_cuota',
                'hc.fecha_programada',
                'hc.abono_capital',
                'hc.abono_intereses',
                'hc.cuota_mensual',
                'hc.cuota_quincenal',
                'hc.saldo_pagado',
                'hc.saldo_restante',
                'hc.interes_pagado',
                'hc.interes_restante',
                'hc.pagado',
                'hc.observaciones',
            )
            ->where(function($q){
                $q->whereNotNull('hc.observaciones')
                  ->where(function($qq){
                      $qq->where('hc.observaciones','like','%cobro extraordinario%')
                         ->orWhere('hc.observaciones','like','%depósito%')
                         ->orWhere('hc.observaciones','like','%deposito%');
                  });
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('emp.nombre_completo', 'like', "%{$search}%")
                            ->orWhere('p.id_prestamo', 'like', "%{$search}%")
                            ->orWhere('emp.codigo_empleado', 'like', "%{$search}%")
                            ->orWhere('hc.num_cuota', 'like', "%{$search}%");
            })
            ->orderBy('hc.fecha_programada','asc')
            ->paginate(10)
            ->appends(['search' => $request->search]);

        // Reutilizamos la misma vista de cuotas (tiene resaltados y acciones)
        return view('prestamos.cuotas', compact('cuotas'));
    }

    public function importAjustesCuotas(Request $request)
    {
        $request->validate([
            'archivo' => 'nullable|file|mimes:xlsx,xls,xlsm',
        ]);

        $path = null;
        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->getRealPath();
        } else {
            $default = storage_path('app/public/PARA IMPORTAR EL SISTEMA.xlsx');
            if (!is_file($default)) {
                return back()->with('error', 'No se proporcionó archivo y no existe el archivo por defecto en storage.');
            }
            $path = $default;
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el archivo de Excel: '.$e->getMessage());
        }

        $sheet = $spreadsheet->getSheetByName('AJUSTES_CUOTAS');
        if (!$sheet) {
            return back()->with('error', 'No se encontró la hoja "AJUSTES_CUOTAS" en el archivo.');
        }

        $rawInicio = $sheet->getCell('B12')->getValue();
        $rawFin    = $sheet->getCell('B13')->getValue();

        $toCarbon = function ($val) {
            if ($val === null || $val === '') return null;
            if (is_numeric($val)) {
                try {
                    $phpDate = ExcelDate::excelToDateTimeObject($val);
                    return Carbon::instance($phpDate)->startOfDay();
                } catch (\Throwable $e) {}
            }
            try { return Carbon::parse($val)->startOfDay(); } catch (\Throwable $e) { return null; }
        };

        $inicio = $toCarbon($rawInicio);
        $fin    = $toCarbon($rawFin);
        if (!$inicio || !$fin) {
            return back()->with('error', 'Fechas inválidas en B12/B13.');
        }

        $startRow = 15;
        $lastRow  = $sheet->getHighestRow();

        $totalProcesados = 0;
        $totalAjustados  = 0;

        DB::beginTransaction();
        try {
            for ($r = $startRow; $r <= $lastRow; $r++) {
                $codigo = trim((string)$sheet->getCell("A{$r}")->getValue());
                if ($codigo === '') { continue; }

                $rawDed = $sheet->getCell("C{$r}")->getCalculatedValue();
                $deduccion = is_numeric($rawDed)
                    ? (float)$rawDed
                    : (float)str_replace([','], [''], (string)$rawDed);

                $cuotas = DB::table('historial_cuotas as hc')
                    ->join('prestamo as p', 'hc.id_prestamo', '=', 'p.id_prestamo')
                    ->join('empleado as e', 'p.id_empleado', '=', 'e.id_empleado')
                    ->where('e.codigo_empleado', $codigo)
                    ->whereBetween('hc.fecha_programada', [$inicio->toDateString(), $fin->toDateString()])
                    ->select('hc.*', 'p.id_prestamo')
                    ->orderBy('hc.fecha_programada', 'asc')
                    ->orderBy('hc.id_historial_cuotas', 'asc')
                    ->get();

                if ($cuotas->isEmpty()) { continue; }

                $totalProcesados++;

                if ((float)$deduccion == 0) {
                    foreach ($cuotas as $cuota) {

                        if ((float)($cuota->cuota_quincenal ?? 0) == 0 && (float)($cuota->cuota_mensual ?? 0) == 0) {
                            continue;
                        }

                        $idPrestamo = $cuota->id_prestamo;
                        $fechaOrig  = Carbon::parse($cuota->fecha_programada)->toDateString();
                        $numOrig    = (int)($cuota->num_cuota ?? 0);

                        $nextSchedule = function (Carbon $date) {
                            $d = $date->copy()->startOfDay();
                            $end = $d->copy()->endOfMonth()->startOfDay();
                            $day = (int)$d->day;
                            if ($day < 15) {
                                return $d->copy()->setDay(15);
                            }
                            if ($day === 15) {
                                return $end;
                            }
                            if ($day < (int)$end->day) {
                                return $end;
                            }

                            return $d->copy()->addMonthNoOverflow()->startOfMonth()->addDays(14);
                        };

                        $posteriores = DB::table('historial_cuotas')
                            ->where('id_prestamo', $idPrestamo)
                            ->where('fecha_programada', '>=', $fechaOrig)
                            ->orderBy('fecha_programada', 'asc')
                            ->orderBy('id_historial_cuotas', 'asc')
                            ->get();

                        $proximaFecha = $nextSchedule(Carbon::parse($fechaOrig));

                        foreach ($posteriores as $row) {
                            DB::table('historial_cuotas')
                                ->where('id_historial_cuotas', $row->id_historial_cuotas)
                                ->update([
                                    'fecha_programada' => $proximaFecha->toDateString(),
                                    'num_cuota'        => DB::raw('num_cuota + 1'),
                                ]);

                            $proximaFecha = $nextSchedule($proximaFecha);
                        }

                        $prev = DB::table('historial_cuotas')
                            ->where('id_prestamo', $idPrestamo)
                            ->where('fecha_programada', '<', $fechaOrig)
                            ->orderBy('fecha_programada', 'desc')
                            ->orderBy('id_historial_cuotas', 'desc')
                            ->first();

                        $saldo_pagado       = $prev->saldo_pagado       ?? ($cuota->saldo_pagado       ?? 0);
                        $saldo_restante     = $prev->saldo_restante     ?? ($cuota->saldo_restante     ?? 0);
                        $interes_pagado     = $prev->interes_pagado     ?? ($cuota->interes_pagado     ?? 0);
                        $interes_restante   = $prev->interes_restante   ?? ($cuota->interes_restante   ?? 0);

                        DB::table('historial_cuotas')->insert([
                            'id_prestamo'      => $idPrestamo,
                            'num_cuota'        => $numOrig,
                            'fecha_programada' => $fechaOrig,
                            'abono_capital'    => 0,
                            'abono_intereses'  => 0,
                            'cuota_mensual'    => 0,
                            'cuota_quincenal'  => 0,
                            'saldo_pagado'     => $saldo_pagado,
                            'saldo_restante'   => $saldo_restante,
                            'interes_pagado'   => $interes_pagado,
                            'interes_restante' => $interes_restante,
                            'pagado'           => 0,
                            'ajuste'           => 1,
                            'motivo'           => 'Ajuste por no cobro (AJUSTES_CUOTAS)',
                        ]);

                        $totalAjustados++;
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error procesando ajustes: '.$e->getMessage());
        }

        return back()->with('success', "Ajuste completado. Empleados procesados: {$totalProcesados}. Cuotas ajustadas: {$totalAjustados}.");
    }

    public function updateCuota(Request $request, $id)
    {
        $request->validate([
            'num_cuota' => 'required|integer|min:0',
            'fecha_programada' => 'required|date',
            'abono_capital' => 'required|numeric|min:0',
            'abono_intereses' => 'required|numeric|min:0',
            'cuota_mensual' => 'required|numeric|min:0',
            'cuota_quincenal' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:0,1',
        ]);

        DB::table('historial_cuotas')
            ->where('id_historial_cuotas', (int) $id)
            ->update([
                'num_cuota' => (int) $request->input('num_cuota'),
                'fecha_programada' => $request->input('fecha_programada'),
                'abono_capital' => $request->input('abono_capital'),
                'abono_intereses' => $request->input('abono_intereses'),
                'cuota_mensual' => $request->input('cuota_mensual'),
                'cuota_quincenal' => $request->input('cuota_quincenal'),
                'observaciones' => $request->input('observaciones'),
                'pagado' => (int) $request->input('estado'),
            ]);

        return redirect()->back()->with('success', 'Cuota actualizada correctamente');
    }
    public function prestamo(Request $request)
    {
        $prestamo = DB::table('prestamo as p')
            ->join('empleado as e', 'p.id_empleado', '=', 'e.id_empleado')
            ->join('planilla as pla', 'p.id_planilla', '=', 'pla.id_planilla')
            ->select(
                'p.id_prestamo',
                'e.id_empleado',
                'e.codigo_empleado',
                'e.nombre_completo',
                'p.num_prestamo',
                'p.monto',
                'p.cuota_capital',
                'p.porcentaje_interes',
                'p.total_intereses',
                DB::raw("IFNULL(p.cobro_extraordinario, 'No') as cobro_extraordinario"),
                DB::raw("IF(p.cobro_extraordinario IS NULL, 'N/A', p.causa) as causa"),
                'p.plazo_meses',
                'p.fecha_deposito_prestamo',
                DB::raw("IF(pla.planilla <> 'PRODUCCION', 'N/A', DATE_FORMAT(p.fecha_primera_cuota, '%Y-%m-%d')) as fecha_primera_cuota"),
                'pla.id_planilla',
                'pla.planilla',
                DB::raw("CASE p.estado_prestamo 
                            WHEN 0 THEN 'Pagado' 
                            WHEN 1 THEN 'En curso' 
                            ELSE 'Desconocido' 
                        END as estado_prestamo")
            )
            ->when($request->search, function ($query, $search) {
                return $query->where('e.nombre_completo', 'like', "%{$search}%")
                            ->orWhere('e.codigo_empleado', 'like', "%{$search}%")
                            ->orWhere('pla.planilla', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);
        $empleados = DB::select('CALL sp_obtener_empleados()');
        $planilla = DB::select('CALL sp_obtener_planilla()');
        return view('prestamos.prestamos', compact('prestamo', 'empleados', 'planilla'));
    }


    public function storeprestamo(Request $request)
    {
        DB::table('prestamo')->insert([
            'num_prestamo' => $request->input('num_prestamo'),
            'id_empleado' => $request->input('id_empleado'),
            'monto' => $request->input('monto'),
            'cuota_capital' => $request->input('cuota_capital'),
            'porcentaje_interes' => $request->input('porcentaje_interes'),
            'total_intereses' => $request->input('total_intereses'),
            'cobro_extraordinario' => $request->input('cobro_extraordinario'),
            'causa' => $request->input('causa'),
            'plazo_meses' => $request->input('plazo_meses'),
            'fecha_deposito_prestamo' => $request->input('fecha_deposito_prestamo'),
            'fecha_primera_cuota' => $request->input('fecha_primera_cuota'),
            'id_planilla' => $request->input('id_planilla'),
            'estado_prestamo' => $request->input('estado_prestamo'),
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->back()->with('success', 'Préstamo registrado correctamente');
    }

    public function eliminarPrestamo(Request $request)
    {
        $id = (int)$request->input('id_prestamo');
        // Elimina el préstamo y sus cuotas
        DB::table('prestamo')->where('id_prestamo', $id)->delete();
        return redirect()->back()->with('success', 'Préstamo eliminado correctamente');
    }

    public function detallePrestamo(int $id)
    {
        $prestamo = DB::table('prestamo as p')
            ->leftJoin('empleado as e', 'p.id_empleado', '=', 'e.id_empleado')
            ->select(
                'p.id_prestamo',
                'p.num_prestamo',
                'p.monto',
                'p.total_intereses',
                'p.fecha_deposito_prestamo',
                'p.estado_prestamo',
                'e.codigo_empleado',
                'e.nombre_completo'
            )
            ->where('p.id_prestamo', $id)
            ->first();

        if (!$prestamo) {
            return response()->json(['ok' => false, 'msg' => 'Préstamo no encontrado'], 404);
        }

        $cuotas = DB::table('historial_cuotas')
        ->where('id_prestamo', $id)
        ->orderBy('num_cuota')
        ->select(
            'num_cuota',
            'fecha_programada',
            DB::raw('COALESCE(cuota_quincenal, cuota_mensual, 0) as cuota_quincenal'),
            'pagado',
            'observaciones'
        )
        ->get();

        if ($cuotas->isEmpty()) {
            return response()->json([
                'ok' => true,
                'resumen' => [
                    'pagadas'    => 0,
                    'pendientes' => 0,
                    'totales'    => 0,
                ],
                'cuotas' => [],
            ]);
        }

        $totales    = $cuotas->count();
        $pagadas    = $cuotas->where('pagado', 1)->count();
        $pendientes = $totales - $pagadas;

        return response()->json([
            'ok' => true,
            'resumen' => [
                'pagadas'    => $pagadas,
                'pendientes' => $pendientes,
                'totales'    => $totales,
            ],
            'cuotas' => $cuotas,
        ]);
    }
}

