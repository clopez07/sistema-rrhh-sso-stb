<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportPrestamosController extends Controller
{
    public function export()
{
    // Parámetros de periodo (opcional: ?anio=2025&mes=8)
    $anio = intval(request()->input('anio', date('Y')));
    $mes  = intval(request()->input('mes',  date('n')));

    // Rango de fechas del modal
    $fechaInicio = request()->input('fecha_inicio', date('Y-m-01'));
    $fechaFinal  = request()->input('fecha_final',  date('Y-m-d'));
    if ($fechaInicio > $fechaFinal) {
        [$fechaInicio, $fechaFinal] = [$fechaFinal, $fechaInicio];
    }

    // === CTE DETALLE (filas por préstamo y mes) ===
    $sqlDetalle = <<<SQL
    WITH cuotas_raw AS (
        SELECT
            p.id_prestamo,
            pl.planilla                             AS PLANILLA,
            e.identidad                             AS IDENTIDAD,
            e.codigo_empleado                       AS `CODIGO DE EMPLEADO`,
            e.nombre_completo                       AS NOMBRE,
            p.fecha_deposito_prestamo               AS `FECHA PRESTAMO`,
            p.num_prestamo                          AS `# PRESTAMO`,
            p.monto                                 AS MONTO,
            p.total_intereses                       AS INTERES,
            p.cuota_capital                         AS `CAPITAL MENSUAL PAGADO`,
            p.observaciones                         AS OBSERVACIONES,

            hc.id_historial_cuotas,
            hc.fecha_programada,
            hc.abono_intereses,
            hc.cuota_mensual,
            hc.cuota_quincenal,
            hc.saldo_pagado,
            hc.saldo_restante,
            hc.interes_pagado,
            hc.interes_restante,

            CASE WHEN hc.fecha_programada BETWEEN :fecha_inicio AND :fecha_final THEN 1 ELSE 0 END AS in_range
        FROM prestamo p
        JOIN empleado e         ON e.id_empleado = p.id_empleado
        LEFT JOIN planilla pl   ON pl.id_planilla = p.id_planilla
        LEFT JOIN historial_cuotas hc ON hc.id_prestamo = p.id_prestamo
        WHERE COALESCE(e.estado, 1) NOT IN (0, 2)
        AND COALESCE(p.estado_prestamo, 1) <> 2
        AND (hc.id_historial_cuotas IS NULL
             OR COALESCE(hc.observaciones, '') NOT LIKE '%Cancelado con refinanciamiento%')
    ),
    cuotas AS (
        SELECT
            cr.*,
            -- 1) Ordenamos primero las que caen dentro del rango y les damos un índice 1,2,3...
            ROW_NUMBER() OVER (
                PARTITION BY cr.id_prestamo
                ORDER BY CASE WHEN cr.in_range = 1 THEN 0 ELSE 1 END,
                        cr.fecha_programada ASC, cr.id_historial_cuotas ASC
            ) AS rn_range_asc_global,
            -- 2) Último estado del préstamo (saldo/intereses) en general
            ROW_NUMBER() OVER (
                PARTITION BY cr.id_prestamo
                ORDER BY cr.fecha_programada DESC, cr.id_historial_cuotas DESC
            ) AS rn_desc_global
        FROM cuotas_raw cr
    ),
    res AS (
        SELECT
            PLANILLA,
            IDENTIDAD,
            `CODIGO DE EMPLEADO`,
            NOMBRE,
            `FECHA PRESTAMO`,
            `# PRESTAMO`,
            MONTO,
            INTERES,

            MAX(`CAPITAL MENSUAL PAGADO`)                                          AS `CAPITAL MENSUAL PAGADO`,
            SUM(CASE WHEN in_range = 1 THEN abono_intereses END)                   AS `INTERES MENSUAL`,
            MAX(CASE WHEN in_range = 1 THEN cuota_mensual END)                     AS `CUOTA MENSUAL`,

            MAX(CASE WHEN in_range = 1 AND rn_range_asc_global = 1 THEN cuota_quincenal END)  AS `QUINCENA I`,
            MAX(CASE WHEN in_range = 1 AND rn_range_asc_global = 1 THEN fecha_programada END) AS `FECHA I`,
            MAX(CASE WHEN in_range = 1 AND rn_range_asc_global = 2 THEN cuota_quincenal END)  AS `QUINCENA II`,
            MAX(CASE WHEN in_range = 1 AND rn_range_asc_global = 2 THEN fecha_programada END) AS `FECHA II`,
            MAX(CASE WHEN in_range = 1 AND rn_range_asc_global = 3 THEN cuota_quincenal END)  AS `QUINCENA III`,
            MAX(CASE WHEN in_range = 1 AND rn_range_asc_global = 3 THEN fecha_programada END) AS `FECHA III`,

            MAX(CASE WHEN rn_desc_global = 1 THEN saldo_pagado END)                 AS `SALDO PAGADO`,
            MAX(CASE WHEN rn_desc_global = 1 THEN saldo_restante END)               AS `SALDO RESTANTE`,
            MAX(CASE WHEN rn_desc_global = 1 THEN interes_pagado END)               AS `INTERESES PAGADOS`,
            MAX(CASE WHEN rn_desc_global = 1 THEN interes_restante END)             AS `INTERESES RESTANTES`,
            MAX(OBSERVACIONES)                                                      AS `OBSERVACIONES`
        FROM cuotas
        GROUP BY
            PLANILLA, IDENTIDAD, `CODIGO DE EMPLEADO`, NOMBRE,
            `FECHA PRESTAMO`, `# PRESTAMO`, MONTO, INTERES
        HAVING SUM(in_range) > 0
    )
    SELECT
        PLANILLA,
        IDENTIDAD,
        `CODIGO DE EMPLEADO`,
        NOMBRE,
        `FECHA PRESTAMO`,
        `# PRESTAMO`,
        MONTO,
        INTERES,
        `CAPITAL MENSUAL PAGADO`,
        `INTERES MENSUAL`,
        `CUOTA MENSUAL`,
        `QUINCENA I`,
        `FECHA I`,
        `QUINCENA II`,
        `FECHA II`,
        `SALDO PAGADO`,
        `SALDO RESTANTE`,
        `INTERESES PAGADOS`,
        `INTERESES RESTANTES`,
        `OBSERVACIONES`,
        `QUINCENA III`,
        `FECHA III`
    FROM res
    UNION ALL
    SELECT
        'TOTAL'                                   AS PLANILLA,
        NULL                                       AS IDENTIDAD,
        NULL                                       AS `CODIGO DE EMPLEADO`,
        NULL                                       AS NOMBRE,
        NULL                                       AS `FECHA PRESTAMO`,
        NULL                                       AS `# PRESTAMO`,
        SUM(MONTO)                                 AS MONTO,
        SUM(INTERES)                               AS INTERES,
        SUM(`CAPITAL MENSUAL PAGADO`)              AS `CAPITAL MENSUAL PAGADO`,
        SUM(`INTERES MENSUAL`)                     AS `INTERES MENSUAL`,
        SUM(`CUOTA MENSUAL`)                       AS `CUOTA MENSUAL`,
        SUM(`QUINCENA I`)                          AS `QUINCENA I`,
        NULL                                       AS `FECHA I`,
        SUM(`QUINCENA II`)                         AS `QUINCENA II`,
        NULL                                       AS `FECHA II`,
        SUM(`SALDO PAGADO`)                        AS `SALDO PAGADO`,
        SUM(`SALDO RESTANTE`)                      AS `SALDO RESTANTE`,
        SUM(`INTERESES PAGADOS`)                   AS `INTERESES PAGADOS`,
        SUM(`INTERESES RESTANTES`)                 AS `INTERESES RESTANTES`,
        NULL                                       AS `OBSERVACIONES`,
        SUM(`QUINCENA III`)                        AS `QUINCENA III`,
        NULL                                       AS `FECHA III`
    FROM res
    ORDER BY
    (`# PRESTAMO` IS NULL),  -- pone la fila TOTAL (NULL) al final
    `# PRESTAMO` ASC,
    NOMBRE ASC;
    SQL;


    // === CTE TOTALES POR PLANILLA (para la tabla al final del template) ===
    $sqlPlanillas = <<<SQL
WITH cuotas_raw AS (
    SELECT
        p.id_prestamo,
        pl.planilla AS PLANILLA,
        p.monto,
        hc.cuota_quincenal,
        hc.id_historial_cuotas,
        hc.fecha_programada,
        EXTRACT(YEAR  FROM hc.fecha_programada) AS anio,
        EXTRACT(MONTH FROM hc.fecha_programada) AS mes,
        CASE WHEN hc.fecha_programada BETWEEN :fecha_inicio AND :fecha_final THEN 1 ELSE 0 END AS in_range
    FROM prestamo p
    JOIN empleado e       ON e.id_empleado = p.id_empleado
    LEFT JOIN planilla pl ON pl.id_planilla = p.id_planilla
    LEFT JOIN historial_cuotas hc ON hc.id_prestamo = p.id_prestamo
    WHERE COALESCE(e.estado, 1) NOT IN (0, 2)
      AND COALESCE(p.estado_prestamo, 1) <> 2
      AND (hc.id_historial_cuotas IS NULL
           OR COALESCE(hc.observaciones, '') NOT LIKE '%Cancelado con refinanciamiento%')
),
cuotas AS (
    SELECT
        cr.*,
        ROW_NUMBER() OVER (
            PARTITION BY cr.id_prestamo, cr.anio, cr.mes
            ORDER BY cr.fecha_programada ASC, cr.id_historial_cuotas ASC
        ) AS rn_asc,
        ROW_NUMBER() OVER (
            PARTITION BY cr.id_prestamo, cr.anio, cr.mes
            ORDER BY CASE WHEN cr.in_range = 1 THEN 0 ELSE 1 END,
                     cr.fecha_programada ASC, cr.id_historial_cuotas ASC
        ) AS rn_range_asc
    FROM cuotas_raw cr
),
res AS (
    SELECT
        PLANILLA,
        monto,
        MAX(CASE WHEN in_range = 1 AND rn_range_asc = 1 THEN cuota_quincenal END) AS `QUINCENA I`,
        MAX(CASE WHEN in_range = 1 AND rn_range_asc = 2 THEN cuota_quincenal END) AS `QUINCENA II`,
        anio,
        mes
    FROM cuotas
    GROUP BY PLANILLA, id_prestamo, monto, anio, mes
    HAVING SUM(in_range) > 0
)
SELECT
    COALESCE(PLANILLA, 'SIN PLANILLA') AS PLANILLA,
    SUM(monto)         AS SUMA_MONTO,
    SUM(`QUINCENA I`)  AS SUMA_QUINCENA_I,
    SUM(`QUINCENA II`) AS SUMA_QUINCENA_II
FROM res
GROUP BY COALESCE(PLANILLA, 'SIN PLANILLA')
ORDER BY PLANILLA
SQL;

    // Ejecutar consultas con parámetros
    $bindings = ['fecha_inicio' => $fechaInicio, 'fecha_final' => $fechaFinal];
    $detalle   = DB::select($sqlDetalle,   $bindings);
    $planillas = DB::select($sqlPlanillas, $bindings);

    // Normalizar a arrays
    $detalle   = array_map(fn($r) => (array)$r, $detalle);
    $planillas = array_map(fn($r) => (array)$r, $planillas);

    // Cargar template
    $templatePath = storage_path('app/public/Formato Reporte Prestamos.xlsx');
    if (!is_file($templatePath)) {
        abort(404, 'No se encontró el template');
    }

    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getSheetByName('REPORTE') ?? $spreadsheet->getActiveSheet();

    // Colocar rango de fechas seleccionado en C4
    try {
        $fiTxt = (new \DateTime($fechaInicio))->format('d/m/Y');
        $ffTxt = (new \DateTime($fechaFinal))->format('d/m/Y');
        $sheet->setCellValue('C4', $fiTxt.' - '.$ffTxt);
    } catch (\Exception $e) {
        $sheet->setCellValue('C4', $fechaInicio.' - '.$fechaFinal);
    }

    // Inserción de filas para detalle
    $START_ROW = 7;
    $dataCount = max(1, count($detalle));
    $endRow    = $START_ROW + $dataCount - 1;

    if ($dataCount > 1) {
        $sheet->insertNewRowBefore($START_ROW + 1, $dataCount - 1);
    }

    // Copiar estilo de la fila plantilla
    $tplRange  = "A{$START_ROW}:U{$START_ROW}";
    $fullRange = "A{$START_ROW}:U{$endRow}";
    $sheet->duplicateStyle($sheet->getStyle($tplRange), $fullRange);

    // Altura filas
    $tplHeight = $sheet->getRowDimension($START_ROW)->getRowHeight();
    for ($r = $START_ROW + 1; $r <= $endRow; $r++) {
        $sheet->getRowDimension($r)->setRowHeight($tplHeight);
    }

    // Mapeo de columnas del Excel
    $map = [
        'PLANILLA'               => 'B',
        'IDENTIDAD'              => 'C',
        'CODIGO DE EMPLEADO'     => 'D',
        'NOMBRE'                 => 'E',
        'FECHA PRESTAMO'         => 'F', // fecha
        '# PRESTAMO'             => 'G',
        'MONTO'                  => 'H',
        'INTERES'                => 'I',
        'CAPITAL MENSUAL PAGADO' => 'J',
        'INTERES MENSUAL'        => 'K',
        'CUOTA MENSUAL'          => 'L',
        'QUINCENA I'             => 'M',
        'FECHA I'                => 'N', // fecha
        'QUINCENA II'            => 'O',
        'FECHA II'               => 'P', // fecha
        'QUINCENA III'           => 'Q',
        'FECHA III'              => 'R', // fecha
        'SALDO PAGADO'           => 'S',
        'SALDO RESTANTE'         => 'T',
        'INTERESES PAGADOS'      => 'U',
        'INTERESES RESTANTES'    => 'V',
        'OBSERVACIONES'          => 'W',
    ];

    // Volcar datos
    $row = $START_ROW;
    foreach ($detalle as $d) {
        foreach ($map as $key => $col) {
            $val = $d[$key] ?? null;

            // Fechas: F (fecha préstamo), N (fecha I), P (fecha II)
            // En tu loop de volcado:
            if (in_array($col, ['F','N','P','R']) && !empty($val)) {
                try {
                    $dt = new \DateTime(is_string($val) ? $val : (string)$val);
                    $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dt);
                    $sheet->setCellValue($col.$row, $excelDate);
                } catch (\Exception $e) {
                    $sheet->setCellValue($col.$row, $val);
                }
            } else {
                $sheet->setCellValue($col.$row, $val);
            }
        }
        $row++;
    }

    // Números
    $numericCols = ['H','I','J','K','L','M','O','Q','S','T','U','V'];
    foreach ($numericCols as $c) {
        $sheet->getStyle("{$c}{$START_ROW}:{$c}{$endRow}")
            ->getNumberFormat()->setFormatCode('#,##0.00');
    }

    // Fechas: F (Préstamo), N (I), P (II), R (III)
    foreach (['F','N','P','R'] as $c) {
        $sheet->getStyle("{$c}{$START_ROW}:{$c}{$endRow}")
            ->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    }

    // Observaciones multilínea (ahora está en W)
    $sheet->getStyle("W{$START_ROW}:W{$endRow}")
        ->getAlignment()->setWrapText(true);

    // Normalizador para nombres de planilla
    $norm = function (?string $s) {
        if ($s === null) return '';
        $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t === false) $t = $s;
        $t = mb_strtoupper($t, 'UTF-8');
        $t = str_replace([':', ';', '.', ',', '–', '—', '-', '_'], ' ', $t);
        $t = preg_replace('/\s+/u', ' ', $t);
        $t = preg_replace('/[^A-Z0-9 ]/u', '', $t);
        $t = trim($t);
        return $t;
    };

    // Ubicar encabezado de la tabla de planillas (N=MONTO, O=QUINCENA I, P=QUINCENA II)
    $findPlanillasHeaderRow = function($sheet) {
        for ($r = 1; $r <= 2000; $r++) {
            $L = $sheet->getCell("N{$r}")->getValue();
            $M = $sheet->getCell("O{$r}")->getValue();
            $N = $sheet->getCell("P{$r}")->getValue();
            if (is_string($L) && is_string($M) && is_string($N)) {
                if (mb_strtoupper(trim($L)) === 'MONTO'
                    && mb_strtoupper(trim($M)) === 'QUINCENA I'
                    && mb_strtoupper(trim($N)) === 'QUINCENA II') {
                    return $r;
                }
            }
        }
        return null;
    };

    $headerRow = $findPlanillasHeaderRow($sheet);
    if ($headerRow === null) {
        abort(500, 'No se encontró el encabezado de la tabla de planillas (N=MONTO, O=QUINCENA I, P=QUINCENA II). Revisa el template.');
    }

    // Mover la tabla de planillas 2 filas después del detalle
    $desiredHeaderRow = $endRow + 2;
    if ($headerRow < $desiredHeaderRow) {
        $rowsToInsert = $desiredHeaderRow - $headerRow;
        $sheet->insertNewRowBefore($headerRow, $rowsToInsert);
        $headerRow += $rowsToInsert;
    }

    $firstDataRow = $headerRow + 1;

    // Determinar último renglón de esa tabla viendo si hay nombre de planilla en E
    $lastDataRow = $firstDataRow;
    for ($r = $firstDataRow; $r <= 2000; $r++) {
        $dVal = $sheet->getCell("E{$r}")->getValue();
        if ($dVal === null || $dVal === '') {
            $dNext = $sheet->getCell("E".($r+1))->getValue();
            if ($dNext === null || $dNext === '') {
                break;
            }
        }
        $lastDataRow = $r;
    }

    // Índice con totales por planilla
    $totalesPorPlanilla = [];
    foreach ($planillas as $p) {
        $key = $norm($p['PLANILLA'] ?? 'SIN PLANILLA');
        $totalesPorPlanilla[$key] = [
            'monto' => (float)($p['SUMA_MONTO'] ?? 0),
            'q1'    => (float)($p['SUMA_QUINCENA_I'] ?? 0),
            'q2'    => (float)($p['SUMA_QUINCENA_II'] ?? 0),
        ];
    }

    // Escribir totales en columnas N/O/P de la tabla de planillas
    for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
        $nombrePlanilla = $sheet->getCell("E{$r}")->getValue();
        if (!is_string($nombrePlanilla)) continue;

        $key = $norm($nombrePlanilla);
        if (in_array($key, ['TOTAL', 'TOTALES'], true)) continue;

        if (isset($totalesPorPlanilla[$key])) {
            $sheet->setCellValue("N{$r}", $totalesPorPlanilla[$key]['monto']);
            $sheet->setCellValue("O{$r}", $totalesPorPlanilla[$key]['q1']);
            $sheet->setCellValue("P{$r}", $totalesPorPlanilla[$key]['q2']);
        } else {
            $sheet->setCellValue("N{$r}", 0);
            $sheet->setCellValue("O{$r}", 0);
            $sheet->setCellValue("P{$r}", 0);
        }
    }

    $sheet->getStyle("N{$firstDataRow}:P{$lastDataRow}")
        ->getNumberFormat()->setFormatCode('#,##0.00');

// Descargar (robusto): escribir a un archivo temporal y luego servirlo
$fileName = sprintf('Reporte_Prestamos_%04d-%02d.xlsx', $anio, $mes);
$writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');

// Limpia todos los buffers de salida y desactiva la compresión de zlib
if (function_exists('ini_set')) { @ini_set('zlib.output_compression', 'Off'); }
while (ob_get_level() > 0) { @ob_end_clean(); }

// Guarda a /tmp y devuélvelo como descarga
$tmp = tempnam(sys_get_temp_dir(), 'prestamos_');
$writer->save($tmp);

return response()
    ->download($tmp, $fileName, [
        'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
        'Pragma'              => 'public',
        'Content-Transfer-Encoding' => 'binary',
    ])
    ->deleteFileAfterSend(true);
}

}
