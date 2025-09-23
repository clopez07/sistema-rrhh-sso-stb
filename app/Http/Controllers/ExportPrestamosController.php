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

        // === DETALLE ===
        // Regla:
        //  - PRODUCCIÓN: QI/QII = MIN(fecha_prod_en_rango) y MAX(fecha_prod_en_rango), ignorando 15 y fin de mes y cualquier fecha intermedia.
        //  - OTRAS planillas: QI si day<=15, QII si day>15.
        $sqlDetalle = <<<SQL
WITH cuotas_raw AS (
    SELECT
        p.id_prestamo,
        pl.planilla AS `PLANILLA`,
        e.identidad AS `IDENTIDAD`,
        e.codigo_empleado AS `CODIGO DE EMPLEADO`,
        e.nombre_completo AS `NOMBRE`,
        p.fecha_deposito_prestamo AS `FECHA PRESTAMO`,
        p.num_prestamo AS `# PRESTAMO`,
        p.monto AS `MONTO`,
        p.total_intereses AS `INTERES`,
        p.cuota_capital AS `CAPITAL MENSUAL PAGADO`,
        p.observaciones AS `OBSERVACIONES`,

        hc.id_historial_cuotas,
        hc.fecha_programada,
        hc.abono_intereses,
        hc.abono_capital,
        hc.cuota_mensual,
        hc.cuota_quincenal,
        hc.saldo_pagado,
        hc.saldo_restante,
        hc.interes_pagado,
        hc.interes_restante,

        CASE WHEN hc.fecha_programada BETWEEN :fecha_inicio AND :fecha_final THEN 1 ELSE 0 END AS in_range,
        CASE WHEN UPPER(pl.planilla) LIKE '%PRODUCCION%' THEN 1 ELSE 0 END AS is_prod
    FROM prestamo p
    JOIN empleado e ON e.id_empleado = p.id_empleado
    LEFT JOIN planilla pl ON pl.id_planilla = p.id_planilla
    LEFT JOIN historial_cuotas hc ON hc.id_prestamo = p.id_prestamo
    WHERE (hc.id_historial_cuotas IS NULL OR COALESCE(hc.observaciones, '') NOT LIKE '%Cancelado con refinanciamiento%')
),
prod_dates AS (
    SELECT DISTINCT cr.fecha_programada AS fecha_q
    FROM cuotas_raw cr
    WHERE cr.in_range = 1
      AND cr.is_prod = 1
      AND cr.fecha_programada IS NOT NULL
      AND DAY(cr.fecha_programada) <> 15
      AND DAY(cr.fecha_programada) <> DAY(LAST_DAY(cr.fecha_programada))
),
qslots AS (
    SELECT fecha_q, DENSE_RANK() OVER (ORDER BY fecha_q ASC) AS qn
    FROM (
        SELECT MIN(fecha_q) AS fecha_q FROM prod_dates
        UNION ALL
        SELECT MAX(fecha_q) AS fecha_q FROM prod_dates
    ) mm
),
cuotas AS (
    SELECT
        cr.*,
        CASE
            WHEN cr.is_prod = 1 THEN qs.qn
            ELSE CASE
                WHEN cr.in_range = 1 AND DAY(cr.fecha_programada) <= 15 THEN 1
                WHEN cr.in_range = 1 AND DAY(cr.fecha_programada) >  15 THEN 2
            END
        END AS qn,

        ROW_NUMBER() OVER (
            PARTITION BY cr.id_prestamo
            ORDER BY CASE WHEN cr.in_range = 1 THEN 0 ELSE 1 END,
                     cr.fecha_programada ASC, cr.id_historial_cuotas ASC
        ) AS rn_range_asc_global,

        ROW_NUMBER() OVER (
            PARTITION BY cr.id_prestamo
            ORDER BY cr.fecha_programada DESC, cr.id_historial_cuotas DESC
        ) AS rn_desc_global,

        CASE WHEN COALESCE(cr.saldo_pagado,0) > 0 OR COALESCE(cr.interes_pagado,0) > 0 THEN 1 ELSE 0 END AS has_progress,

        CASE WHEN COALESCE(cr.abono_capital,0) > 0 OR COALESCE(cr.abono_intereses,0) > 0 THEN 1 ELSE 0 END AS is_pagada,

        ROW_NUMBER() OVER (
            PARTITION BY cr.id_prestamo
            ORDER BY CASE WHEN COALESCE(cr.saldo_pagado,0) > 0 OR COALESCE(cr.interes_pagado,0) > 0 THEN 0 ELSE 1 END,
                     cr.fecha_programada DESC, cr.id_historial_cuotas DESC
        ) AS rn_ult_pagada

    FROM cuotas_raw cr
    LEFT JOIN qslots qs ON qs.fecha_q = cr.fecha_programada
),
res AS (
    SELECT
        `PLANILLA`,
        `IDENTIDAD`,
        `CODIGO DE EMPLEADO`,
        `NOMBRE`,
        `FECHA PRESTAMO`,
        `# PRESTAMO`,
        `MONTO`,
        `INTERES`,

        /* Basado SOLO en cuotas pagadas del rango */
        SUM(CASE WHEN in_range = 1 THEN COALESCE(abono_capital,0) ELSE 0 END) AS `CAPITAL MENSUAL PAGADO`,
        SUM(CASE WHEN in_range = 1 THEN COALESCE(abono_intereses,0) ELSE 0 END) AS `INTERES MENSUAL`,
        SUM(CASE WHEN in_range = 1 THEN COALESCE(abono_capital,0) + COALESCE(abono_intereses,0) ELSE 0 END) AS `CUOTA MENSUAL`,

        MAX(CASE WHEN in_range = 1 AND qn = 1 THEN cuota_quincenal END) AS `QUINCENA I`,
        MAX(CASE WHEN in_range = 1 AND qn = 1 THEN fecha_programada END)  AS `FECHA I`,
        MAX(CASE WHEN in_range = 1 AND qn = 2 THEN cuota_quincenal END) AS `QUINCENA II`,
        MAX(CASE WHEN in_range = 1 AND qn = 2 THEN fecha_programada END)  AS `FECHA II`,
        MAX(CASE WHEN in_range = 1 AND qn = 3 THEN cuota_quincenal END) AS `QUINCENA III`,
        MAX(CASE WHEN in_range = 1 AND qn = 3 THEN fecha_programada END)  AS `FECHA III`,

        COALESCE(MAX(CASE WHEN rn_ult_pagada = 1 THEN saldo_pagado END), 0) AS `CAPITAL PAGADO`,
        COALESCE(MAX(CASE WHEN rn_ult_pagada = 1 THEN saldo_restante END),
                 MAX(CASE WHEN rn_desc_global = 1 THEN saldo_restante END),
                 MAX(`MONTO`)) AS `CAPITAL PENDIENTE`,
        COALESCE(MAX(CASE WHEN rn_ult_pagada = 1 THEN interes_pagado END), 0) AS `INTERESES PAGADOS`,
        COALESCE(MAX(CASE WHEN rn_ult_pagada = 1 THEN interes_restante END), MAX(`INTERES`), 0) AS `INTERESES PENDIENTES`,

        MAX(`OBSERVACIONES`) AS `OBSERVACIONES`
    FROM cuotas
    GROUP BY
        `PLANILLA`,`IDENTIDAD`,`CODIGO DE EMPLEADO`,`NOMBRE`,`FECHA PRESTAMO`,`# PRESTAMO`,`MONTO`,`INTERES`
    HAVING SUM(in_range) > 0
)
SELECT
    `PLANILLA`, `IDENTIDAD`, `CODIGO DE EMPLEADO`, `NOMBRE`, `FECHA PRESTAMO`, `# PRESTAMO`,
    `MONTO`, `INTERES`,
    `CAPITAL MENSUAL PAGADO`, `INTERES MENSUAL`, `CUOTA MENSUAL`,
    `QUINCENA I`, `FECHA I`, `QUINCENA II`, `FECHA II`,
    `CAPITAL PAGADO`, `CAPITAL PENDIENTE`, `INTERESES PAGADOS`, `INTERESES PENDIENTES`, `OBSERVACIONES`,
    `QUINCENA III`, `FECHA III`
FROM res
UNION ALL
SELECT
    'TOTAL', NULL, NULL, NULL, NULL, NULL,
    SUM(`MONTO`), SUM(`INTERES`),
    SUM(`CAPITAL MENSUAL PAGADO`), SUM(`INTERES MENSUAL`), SUM(`CUOTA MENSUAL`),
    SUM(`QUINCENA I`), NULL, SUM(`QUINCENA II`), NULL,
    SUM(`CAPITAL PAGADO`), SUM(`CAPITAL PENDIENTE`), SUM(`INTERESES PAGADOS`), SUM(`INTERESES PENDIENTES`),
    NULL,
    SUM(`QUINCENA III`), NULL
FROM res
ORDER BY (`# PRESTAMO` IS NULL), `# PRESTAMO` ASC, `NOMBRE` ASC;
SQL;


        // === TOTALES POR PLANILLA (tabla secundaria) ===
        $sqlPlanillas = <<<SQL
WITH cuotas_raw AS (
    SELECT
        p.id_prestamo,
        pl.planilla AS `PLANILLA`,
        p.monto,
        hc.cuota_quincenal,
        hc.id_historial_cuotas,
        hc.fecha_programada,
        CASE WHEN hc.fecha_programada BETWEEN :fecha_inicio AND :fecha_final THEN 1 ELSE 0 END AS in_range,
        CASE WHEN UPPER(pl.planilla) LIKE '%PRODUCCION%' THEN 1 ELSE 0 END AS is_prod
    FROM prestamo p
    JOIN empleado e ON e.id_empleado = p.id_empleado
    LEFT JOIN planilla pl ON pl.id_planilla = p.id_planilla
    LEFT JOIN historial_cuotas hc ON hc.id_prestamo = p.id_prestamo
    WHERE (hc.id_historial_cuotas IS NULL OR COALESCE(hc.observaciones, '') NOT LIKE '%Cancelado con refinanciamiento%')
),
prod_dates AS (
    SELECT DISTINCT cr.fecha_programada AS fecha_q
    FROM cuotas_raw cr
    WHERE cr.in_range = 1
      AND cr.is_prod = 1
      AND cr.fecha_programada IS NOT NULL
      AND DAY(cr.fecha_programada) <> 15
      AND DAY(cr.fecha_programada) <> DAY(LAST_DAY(cr.fecha_programada))
),
qslots AS (
    SELECT fecha_q, DENSE_RANK() OVER (ORDER BY fecha_q ASC) AS qn
    FROM (
        SELECT MIN(fecha_q) AS fecha_q FROM prod_dates
        UNION ALL
        SELECT MAX(fecha_q) AS fecha_q FROM prod_dates
    ) mm
),
cuotas_in AS (
    SELECT
        cr.`PLANILLA`, cr.id_prestamo, cr.monto,
        CASE
            WHEN cr.is_prod = 1 THEN qs.qn
            ELSE CASE
                WHEN cr.in_range = 1 AND DAY(cr.fecha_programada) <= 15 THEN 1
                WHEN cr.in_range = 1 AND DAY(cr.fecha_programada) >  15 THEN 2
            END
        END AS qn,
        cr.cuota_quincenal
    FROM cuotas_raw cr
    LEFT JOIN qslots qs ON qs.fecha_q = cr.fecha_programada
    WHERE cr.in_range = 1
),
res AS (
    SELECT
        `PLANILLA`, id_prestamo, MAX(monto) AS monto,
        MAX(CASE WHEN qn = 1 THEN cuota_quincenal END) AS `QUINCENA I`,
        MAX(CASE WHEN qn = 2 THEN cuota_quincenal END) AS `QUINCENA II`
    FROM cuotas_in
    GROUP BY `PLANILLA`, id_prestamo
)
SELECT
    COALESCE(`PLANILLA`, 'SIN PLANILLA') AS `PLANILLA`,
    SUM(monto) AS `SUMA_MONTO`,
    SUM(`QUINCENA I`) AS `SUMA_QUINCENA_I`,
    SUM(`QUINCENA II`) AS `SUMA_QUINCENA_II`
FROM res
GROUP BY COALESCE(`PLANILLA`, 'SIN PLANILLA')
ORDER BY `PLANILLA`
SQL;


        // Ejecutar consultas
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

        // Rango de fechas en C4
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
        $endRow = $START_ROW + $dataCount - 1;

        if ($dataCount > 1) {
            $sheet->insertNewRowBefore($START_ROW + 1, $dataCount - 1);
        }

        // Copiar estilos (hasta W)
        $tplRange  = "A{$START_ROW}:W{$START_ROW}";
        $fullRange = "A{$START_ROW}:W{$endRow}";
        $sheet->duplicateStyle($sheet->getStyle($tplRange), $fullRange);

        // Altura filas
        $tplHeight = $sheet->getRowDimension($START_ROW)->getRowHeight();
        for ($r = $START_ROW + 1; $r <= $endRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight($tplHeight);
        }

        // Mapeo columnas
        $map = [
            'PLANILLA' => 'B',
            'IDENTIDAD' => 'C',
            'CODIGO DE EMPLEADO' => 'D',
            'NOMBRE' => 'E',
            'FECHA PRESTAMO' => 'F',
            '# PRESTAMO' => 'G',
            'MONTO' => 'H',
            'INTERES' => 'I',
            'CAPITAL MENSUAL PAGADO' => 'J',
            'INTERES MENSUAL' => 'K',
            'CUOTA MENSUAL' => 'L',
            'QUINCENA I' => 'M',
            'FECHA I' => 'N',
            'QUINCENA II' => 'O',
            'FECHA II' => 'P',
            'QUINCENA III' => 'Q',
            'FECHA III' => 'R',
            'CAPITAL PAGADO' => 'S',
            'CAPITAL PENDIENTE' => 'T',
            'INTERESES PAGADOS' => 'U',
            'INTERESES PENDIENTES' => 'V',
            'OBSERVACIONES' => 'W',
        ];

        // === NUEVO: helper para truncar a 2 decimales sin redondear ===
        $trunc2 = function($val) {
            if ($val === null || $val === '') return null;
            $s = str_replace([',',' '], '', (string)$val);
            $neg = false;
            if (strlen($s) && $s[0] === '-') { $neg = true; $s = substr($s, 1); }
            if ($s === '' || $s === '.') return 0.0;
            if (strpos($s, '.') === false) { $int = $s; $dec = '00'; }
            else { [$int, $dec] = explode('.', $s, 2); $dec = substr($dec, 0, 2); $dec = str_pad($dec, 2, '0'); }
            $out = ($neg ? '-' : '') . ($int === '' ? '0' : $int) . '.' . $dec;
            return (float)$out;
        };

        // Campos numéricos a truncar en el detalle
        $numericKeys = [
            'MONTO','INTERES','CAPITAL MENSUAL PAGADO','INTERES MENSUAL','CUOTA MENSUAL',
            'QUINCENA I','QUINCENA II','QUINCENA III',
            'CAPITAL PAGADO','CAPITAL PENDIENTE','INTERESES PAGADOS','INTERESES PENDIENTES'
        ];

        // Volcado del detalle
        $row = $START_ROW;
        foreach ($detalle as $d) {
            foreach ($map as $key => $col) {
                $val = $d[$key] ?? null;

                // Columnas de fecha
                if (in_array($col, ['F','N','P','R']) && !empty($val)) {
                    try {
                        $dt = new \DateTime(is_string($val) ? $val : (string)$val);
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dt);
                        $sheet->setCellValue($col.$row, $excelDate);
                    } catch (\Exception $e) {
                        $sheet->setCellValue($col.$row, $val);
                    }
                } else {
                    // Truncar numéricos a 2 decimales sin redondear
                    if (in_array($key, $numericKeys, true) && $val !== null && $val !== '') {
                        $val = $trunc2($val);
                    }
                    $sheet->setCellValue($col.$row, $val);
                }
            }
            $row++;
        }

        // Números (formato visual Excel)
        foreach (['H','I','J','K','L','M','O','Q','S','T','U','V'] as $c) {
            $sheet->getStyle("{$c}{$START_ROW}:{$c}{$endRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Fechas
        foreach (['F','N','P','R'] as $c) {
            $sheet->getStyle("{$c}{$START_ROW}:{$c}{$endRow}")
                ->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }

        // Observaciones
        $sheet->getStyle("W{$START_ROW}:W{$endRow}")
            ->getAlignment()->setWrapText(true);

        // Normalizador planilla
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

        // Localizar tabla de planillas (N/O/P)
        $findPlanillasHeaderRow = function($sheet) {
            for ($r = 1; $r <= 2000; $r++) {
                $N = $sheet->getCell("N{$r}")->getValue();
                $O = $sheet->getCell("O{$r}")->getValue();
                $P = $sheet->getCell("P{$r}")->getValue();
                if (is_string($N) && is_string($O) && is_string($P)) {
                    if (mb_strtoupper(trim($N)) === 'MONTO'
                        && mb_strtoupper(trim($O)) === 'QUINCENA I'
                        && mb_strtoupper(trim($P)) === 'QUINCENA II') {
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

        // Mover tabla de planillas 2 filas después del detalle
        $desiredHeaderRow = $endRow + 2;
        if ($headerRow < $desiredHeaderRow) {
            $rowsToInsert = $desiredHeaderRow - $headerRow;
            $sheet->insertNewRowBefore($headerRow, $rowsToInsert);
            $headerRow += $rowsToInsert;
        }

        $firstDataRow = $headerRow + 1;

        // Última fila con nombre de planilla en E
        $lastDataRow = $firstDataRow;
        for ($r = $firstDataRow; $r <= 2000; $r++) {
            $dVal = $sheet->getCell("E{$r}")->getValue();
            if ($dVal === null || $dVal === '') {
                $dNext = $sheet->getCell("E".($r+1))->getValue();
                if ($dNext === null || $dNext === '') break;
            }
            $lastDataRow = $r;
        }

        // Índice de totales por planilla
        $totalesPorPlanilla = [];
        foreach ($planillas as $p) {
            $key = $norm($p['PLANILLA'] ?? 'SIN PLANILLA');
            $totalesPorPlanilla[$key] = [
                'monto' => (float)($p['SUMA_MONTO'] ?? 0),
                'q1'    => (float)($p['SUMA_QUINCENA_I'] ?? 0),
                'q2'    => (float)($p['SUMA_QUINCENA_II'] ?? 0),
            ];
        }

        // Escribir totales N/O/P (truncados)
        for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
            $nombrePlanilla = $sheet->getCell("E{$r}")->getValue();
            if (!is_string($nombrePlanilla)) continue;
            $key = $norm($nombrePlanilla);
            if (in_array($key, ['TOTAL', 'TOTALES'], true)) continue;

            if (isset($totalesPorPlanilla[$key])) {
                $sheet->setCellValue("N{$r}", $trunc2($totalesPorPlanilla[$key]['monto']));
                $sheet->setCellValue("O{$r}", $trunc2($totalesPorPlanilla[$key]['q1']));
                $sheet->setCellValue("P{$r}", $trunc2($totalesPorPlanilla[$key]['q2']));
            } else {
                $sheet->setCellValue("N{$r}", 0);
                $sheet->setCellValue("O{$r}", 0);
                $sheet->setCellValue("P{$r}", 0);
            }
        }

        $sheet->getStyle("N{$firstDataRow}:P{$lastDataRow}")
              ->getNumberFormat()->setFormatCode('#,##0.00');

        // Descargar
        $fileName = sprintf('Reporte_Prestamos_%04d-%02d.xlsx', $anio, $mes);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        if (function_exists('ini_set')) {
            @ini_set('zlib.output_compression', 'Off');
        }
        while (ob_get_level() > 0) { @ob_end_clean(); }

        $tmp = tempnam(sys_get_temp_dir(), 'prestamos_');
        $writer->save($tmp);

        return response()
            ->download($tmp, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
                'Pragma' => 'public',
                'Content-Transfer-Encoding' => 'binary',
            ])
            ->deleteFileAfterSend(true);
    }
}
