<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Conditional;

class MatrizQuimicosController extends Controller
{
    public function index(Request $request)
    {
        $result = DB::select('CALL sp_matriz_quimicos()');

        $rows = collect($result)->map(fn($r) => (array) $r);

        $positionKeys = [];
        if ($rows->isNotEmpty()) {
            $first = $rows->first();
            $positionKeys = array_values(array_filter(array_keys($first), function ($k) {
                return str_contains($k, '||'); // PHP 8
            }));
        } else {
            $puestos = DB::table('puesto_trabajo_matriz as ptm')
                ->leftJoin('departamento as d', 'd.id_departamento', '=', 'ptm.id_departamento')
                ->where(function ($q) {
                    $q->whereNull('ptm.estado')->orWhere('ptm.estado', '<>', 0);
                })
                ->orderBy('d.departamento')
                ->orderBy('ptm.puesto_trabajo_matriz')
                ->get(['d.departamento', 'ptm.puesto_trabajo_matriz', 'ptm.num_empleados']);

            $positionKeys = $puestos->map(function ($p) {
                return ($p->departamento ?? 'SIN DEPTO') . '||' . $p->puesto_trabajo_matriz . '||' . ($p->num_empleados ?? 0);
            })->all();
        }

        $headers = [];
        foreach ($positionKeys as $key) {
            [$dep, $puesto, $num] = explode('||', $key);
            $headers[$dep][] = [
                'key'    => $key,
                'puesto' => $puesto,
                'num'    => (int) $num,
            ];
        }

        $colorMap = [
            'MA' => '#ff0000',
            'A'  => '#be5014',
            'M'  => '#ffc000',
            'B'  => '#ffff00',
            'I'  => '#92d050',
        ];

        return view('riesgos.matrizquimicos', [
            'rows'         => $rows,
            'headers'      => $headers,
            'positionKeys' => $positionKeys,
            'colorMap'     => $colorMap,
        ]);
    }

    public function exportExcel(Request $request)
{
    $result = DB::select('CALL sp_matriz_quimicos()');
    $rows = collect($result)->map(fn($r) => (array) $r);

    $positionKeys = [];
    if ($rows->isNotEmpty()) {
        $first = $rows->first();
        foreach (array_keys($first) as $k) {
            if (strpos($k, '||') !== false) {
                $positionKeys[] = $k;
            }
        }
    } else {
        $puestos = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'd.id_departamento', '=', 'ptm.id_departamento')
            ->where(function ($q) {
                $q->whereNull('ptm.estado')->orWhere('ptm.estado', '<>', 0);
            })
            ->orderBy('d.departamento')
            ->orderBy('ptm.puesto_trabajo_matriz')
            ->get(['d.departamento', 'ptm.puesto_trabajo_matriz', 'ptm.num_empleados']);
        $positionKeys = $puestos->map(function ($p) {
            return ($p->departamento ?? 'SIN DEPTO').'||'.$p->puesto_trabajo_matriz.'||'.($p->num_empleados ?? 0);
        })->all();
    }

    $headers = [];
    foreach ($positionKeys as $key) {
        [$dep, $puesto, $num] = explode('||', $key);
        $headers[$dep][] = ['key' => $key, 'puesto' => $puesto, 'num' => (int) $num];
    }

    $colorMap = [
        'MA' => 'FF0000',
        'A'  => 'BE5014',
        'M'  => 'FFC000',
        'B'  => 'FFFF00',
        'I'  => '92D050',
    ];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Matriz Químicos');

    $sheet->getPageSetup()
        ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
        ->setPaperSize(PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1)
        ->setFitToHeight(0);

    $sheet->getPageMargins()->setTop(0.4)->setRight(0.3)->setLeft(0.3)->setBottom(0.4);

    $logoPath = public_path('img/logo.PNG');
    if (file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setPath($logoPath);
        $drawing->setHeight(64);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(2);
        $drawing->setWorksheet($sheet);
    }

    $fixedBefore = 3;
    $fixedAfter  = 1;
    $totalCols   = $fixedBefore + count($positionKeys) + $fixedAfter;
    $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

    // Títulos
    $sheet->mergeCells("C1:{$lastColLetter}1");
    $sheet->mergeCells("C2:{$lastColLetter}2");
    $sheet->mergeCells("C3:{$lastColLetter}3");
    $sheet->setCellValue('C1', 'MATRIZ DE ANÁLISIS DE RIESGO QUÍMICOS');
    $sheet->setCellValue('C2', 'PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS');
    $sheet->setCellValue('C3', 'MATRIZ DE ANALISIS DE RIESGO / RISK ANALYSIS MATRIX');
    $sheet->getStyle("C1:C3")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $sheet->getStyle("C1")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("C2:C3")->getFont()->setSize(10);

    // Encabezado triple
    $startRow = 6;
    $rowDepto = $startRow;
    $rowNums  = $startRow + 1;
    $rowPuest = $startRow + 2;
    $rowData  = $startRow + 3;

    $sheet->mergeCells("A{$rowDepto}:A{$rowPuest}")->setCellValue("A{$rowDepto}", 'N°');
    $sheet->mergeCells("B{$rowDepto}:B{$rowPuest}")->setCellValue("B{$rowDepto}", 'RIESGO (NOMBRE DEL QUÍMICO)');
    $sheet->mergeCells("C{$rowDepto}:C{$rowPuest}")->setCellValue("C{$rowDepto}", 'DESCRIPCIÓN');

    $colIndex = 4;
    foreach ($headers as $dep => $puestos) {
        $span = count($puestos);
        $colStart = Coordinate::stringFromColumnIndex($colIndex);
        $colEnd   = Coordinate::stringFromColumnIndex($colIndex + $span - 1);
        $sheet->mergeCells("{$colStart}{$rowDepto}:{$colEnd}{$rowDepto}");
        $sheet->setCellValue("{$colStart}{$rowDepto}", $dep);
        $colIndex += $span;
    }

    // Medidas
    $colMedidas = Coordinate::stringFromColumnIndex($fixedBefore + count($positionKeys) + 1);
    $sheet->mergeCells("{$colMedidas}{$rowDepto}:{$colMedidas}{$rowPuest}");
    $sheet->setCellValue("{$colMedidas}{$rowDepto}", 'MEDIDAS DE PREVENCIÓN Y CORRECCIÓN');

    // Cumplimiento
    $compCols = ['TOTAL','PARCIAL','NO CUMPLE'];
    $compStartIdx = $fixedBefore + count($positionKeys) + $fixedAfter + 1;
    $compEndIdx   = $compStartIdx + count($compCols) - 1;
    $compStartCol = Coordinate::stringFromColumnIndex($compStartIdx);
    $compEndCol   = Coordinate::stringFromColumnIndex($compEndIdx);
    $lastColLetter = $compEndCol;

    $sheet->mergeCells("{$compStartCol}{$rowDepto}:{$compEndCol}{$rowDepto}");
    $sheet->setCellValue("{$compStartCol}{$rowDepto}", 'CUMPLIMIENTO');

    for ($k = 0; $k < count($compCols); $k++) {
        $col = Coordinate::stringFromColumnIndex($compStartIdx + $k);
        $sheet->mergeCells("{$col}{$rowNums}:{$col}{$rowPuest}");
        $sheet->setCellValue("{$col}{$rowNums}", $compCols[$k]);
    }

    // Estilo encabezados
    $headerRange = "A{$rowDepto}:{$lastColLetter}{$rowPuest}";
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
            'wrapText'   => true,
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
        ],
    ]);

    // Fila de departamentos
    $sheet->getStyle("A{$rowDepto}:{$lastColLetter}{$rowDepto}")
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00B050');
    $sheet->getStyle("A{$rowDepto}:{$lastColLetter}{$rowDepto}")
          ->getFont()->getColor()->setRGB('FFFFFF');

    // Fila # empleados + fila puestos
    $sheet->getStyle("A{$rowNums}:{$lastColLetter}{$rowNums}")
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ACB9CA');
    $sheet->getStyle("A{$rowPuest}:{$lastColLetter}{$rowPuest}")
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ACB9CA');

    // Encabezado CUMPLIMIENTO (azul) y sub-encabezados (azul)
    $sheet->getStyle("{$compStartCol}{$rowDepto}:{$compEndCol}{$rowDepto}")
          ->applyFromArray([
              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B5DBB']],
              'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
              'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
              'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
          ]);
    $sheet->getStyle("{$compStartCol}{$rowNums}:{$compEndCol}{$rowPuest}")
          ->applyFromArray([
              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
              'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
              'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
              'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
          ]);

    // Números de empleados
    $colIndex = 4;
    foreach ($headers as $dep => $puestos) {
        foreach ($puestos as $p) {
            $col = Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValue("{$col}{$rowNums}", $p['num']);
        }
    }

    // Puestos (vertical)
    $colIndex = 4;
    foreach ($headers as $dep => $puestos) {
        foreach ($puestos as $p) {
            $col  = Coordinate::stringFromColumnIndex($colIndex++);
            $cell = "{$col}{$rowPuest}";
            $sheet->setCellValue($cell, $p['puesto']);
            $sheet->getStyle($cell)->getAlignment()
                ->setTextRotation(90)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_BOTTOM)
                ->setWrapText(true);
            $sheet->getColumnDimension($col)->setWidth(4.5);
        }
    }
    $sheet->getRowDimension($rowPuest)->setRowHeight(140);

    // Datos
    $r = $rowData;
    $rowNum = 1;
    foreach ($rows as $row) {
        $sheet->setCellValueExplicit("A{$r}", $rowNum, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValue("B{$r}", $row['RIESGO (NOMBRE DEL QUIMICO)'] ?? $row['RIESGO (NOMBRE DEL QUÍMICO)'] ?? '');
        $sheet->setCellValue("C{$r}", $row['DESCRIPCION'] ?? '');

        // Códigos por puesto (MA/A/M/B/I)
        $colIndex = 4;
        foreach ($headers as $dep => $puestos) {
            foreach ($puestos as $p) {
                $code = $row[$p['key']] ?? '';
                $code = is_string($code) ? trim($code) : (string)$code;
                if ($code === '') { $code = 'I'; }
                $code = strtoupper($code);
                $col  = Coordinate::stringFromColumnIndex($colIndex++);
                if ($code !== '') {
                    $sheet->setCellValue("{$col}{$r}", $code);
                    if (isset($colorMap[$code])) {
                        $sheet->getStyle("{$col}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB($colorMap[$code]);
                    }
                    $sheet->getStyle("{$col}{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        // Medidas
        $sheet->setCellValue("{$colMedidas}{$r}", $row['MEDIDAS DE PREVENCION Y CORRECCION'] ?? '');

        // CUMPLIMIENTO: Validación y SIN FONDO VERDE
        for ($k = 0; $k < count($compCols); $k++) {
            $col  = Coordinate::stringFromColumnIndex($compStartIdx + $k);
            $cell = "{$col}{$r}";

            $default = ($compCols[$k] === 'TOTAL') ? 'X' : '';
            $sheet->setCellValue($cell, $default);

            $dv = $sheet->getCell($cell)->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_STOP);
            $dv->setAllowBlank(true);
            $dv->setShowDropDown(true);
            $dv->setShowInputMessage(true);
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Selección inválida');
            $dv->setError('Solo use "X" o deje en blanco.');
            $dv->setPromptTitle('Cumplimiento');
            $dv->setPrompt('Seleccione "X" en una sola columna.');
            $dv->setFormula1('"X"');

            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');

            // Mantiene fondo neutro; el color vendrá solo del formato condicional
        }

        // Estilo filas
        $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(36);

        $rowNum++; $r++;
    }

    // Anchos
    $sheet->getColumnDimension('A')->setWidth(4);
    $sheet->getColumnDimension('B')->setWidth(38);
    $sheet->getColumnDimension('C')->setWidth(58);
    $colIndex = 4;
    foreach ($positionKeys as $_) {
        $col = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->getColumnDimension($col)->setWidth(8);
    }
    $sheet->getColumnDimension($colMedidas)->setWidth(60);
    for ($k = 0; $k < count($compCols); $k++) {
        $col = Coordinate::stringFromColumnIndex($compStartIdx + $k);
        $sheet->getColumnDimension($col)->setWidth(10);
    }

    // Freeze
    $sheet->freezePane('D'.$rowData);

    // Formato condicional: aviso si ≠ 1 "X"
    $firstDataRow = $rowData;
    $lastDataRow  = $r - 1;

    for ($rr = $firstDataRow; $rr <= $lastDataRow; $rr++) {
        $rangeRow = "{$compStartCol}{$rr}:{$compEndCol}{$rr}";
        $cf = new Conditional();
        $cf->setConditionType(Conditional::CONDITION_EXPRESSION);
        $cf->addCondition("=COUNTIF(\${$compStartCol}{$rr}:\${$compEndCol}{$rr},\"X\")<>1");
        $cf->getStyle()->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FF9900']]],
        ]);
        $existing = $sheet->getStyle($rangeRow)->getConditionalStyles();
        $existing[] = $cf;
        $sheet->getStyle($rangeRow)->setConditionalStyles($existing);
    }

    // Formato condicional por columna:
    // TOTAL: SIN fondo (solo negrita/centrado)
    // PARCIAL: ámbar; NO CUMPLE: rojo
    for ($k = 0; $k < count($compCols); $k++) {
        $label     = $compCols[$k];
        $colLetter = Coordinate::stringFromColumnIndex($compStartIdx + $k);
        $rangeCol  = "{$colLetter}{$firstDataRow}:{$colLetter}{$lastDataRow}";

        $cond = new Conditional();
        $cond->setConditionType(Conditional::CONDITION_CELLIS);
        $cond->setOperatorType(Conditional::OPERATOR_EQUAL);
        $cond->addCondition('"X"');

        $style = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        if ($label === 'TOTAL') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B050']];
            $style['font']['color'] = ['rgb' => 'FFFFFF'];
        } elseif ($label === 'PARCIAL') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']];
        } elseif ($label === 'NO CUMPLE') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']];
        }
        // Nota: TOTAL queda verde solo con la X gracias al condicional

        $cond->getStyle()->applyFromArray($style);

        $existing = $sheet->getStyle($rangeCol)->getConditionalStyles();
        $existing[] = $cond;
        $sheet->getStyle($rangeCol)->setConditionalStyles($existing);
    }

    // ===== Pie de página en texto (abajo) =====
    $footerRow = $r + 2; // deja una fila en blanco
    $totalColsFooter = $compEndIdx; // índice numérico de la última columna
    $third = max(1, intdiv($totalColsFooter, 3));

    $leftStart   = 'A';
    $leftEnd     = Coordinate::stringFromColumnIndex($third);
    $centerStart = Coordinate::stringFromColumnIndex($third + 1);
    $centerEnd   = Coordinate::stringFromColumnIndex(max($third * 2, $third + 1));
    $rightStart  = Coordinate::stringFromColumnIndex(min($third * 2 + 1, $totalColsFooter));
    $rightEnd    = $lastColLetter;

    // Merges del pie
    $sheet->mergeCells("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}");
    $sheet->mergeCells("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1));
    $sheet->mergeCells("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1));
    $sheet->mergeCells("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1));

    // Textos del pie
    $sheet->setCellValue("{$leftStart}{$footerRow}",       '1 Copia Archivo');
    $sheet->setCellValue("{$leftStart}".($footerRow+1),    '1 Copia Sistema');
    $sheet->setCellValue("{$centerStart}".($footerRow+1),  '2 VERSION 2025');
    $sheet->setCellValue("{$rightStart}".($footerRow+1),   'STB/SSO/R054');

    // Estilos del pie
    $sheet->getStyle("A{$footerRow}:{$lastColLetter}".($footerRow+1))
          ->getFont()->setName('Arial')->setSize(9);
    $sheet->getRowDimension($footerRow)->setRowHeight(16);
    $sheet->getRowDimension($footerRow+1)->setRowHeight(16);

    $sheet->getStyle("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}")
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1))
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1))
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1))
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Línea suave encima del pie
    $sheet->getStyle("A".($footerRow-1).":{$lastColLetter}".($footerRow-1))
          ->applyFromArray([
              'borders'=>['top'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                  'color'=>['rgb'=>'DDDDDD']]]
          ]);

    // Descargar
    $fileName = 'Matriz_Quimicos_'.date('Ymd_His').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    if (ob_get_length()) { ob_end_clean(); }
    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}


}

