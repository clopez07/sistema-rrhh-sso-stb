<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteIluminacionExport
{
    public function __construct(
        public ?int $year = null,
        public ?int $locId = null
    ) {}

    public function build(): Spreadsheet
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Iluminacion');

    // ---- Colores / estilos ----
    $colorHeader = '00B0F0';
    $colorHeaderDark = '0088BC';
    $colorBorder = '000000';
    $colorWhite = 'FFFFFF';

    $thinBorders = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => $colorBorder],
            ],
        ],
    ];
    $centerBold = [
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ];
    $centerBoldWhite = [
        'font' => ['bold' => true, 'color' => ['rgb' => $colorWhite]],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $colorHeader],
        ],
    ];
    $centerBoldWhiteDark = $centerBoldWhite;
    $centerBoldWhiteDark['fill']['startColor']['rgb'] = $colorHeaderDark;

    $left = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ];
    $right = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_RIGHT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    $center = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    $small = [
        'font' => ['size' => 9],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => false,
        ],
    ];

    // ---- Anchos ----
    $sheet->getColumnDimension('A')->setWidth(6);
    $sheet->getColumnDimension('B')->setWidth(40);
    $sheet->getColumnDimension('C')->setWidth(40);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(46);

    // ---- Encabezado grande ----
    $row = 1;
    $sheet->mergeCells("A{$row}:F{$row}")->setCellValue("A{$row}", 'SERVICE AND TRADING BUSINESS S.A. DE C.V.');
    $row++;
    $sheet->mergeCells("A{$row}:F{$row}")->setCellValue("A{$row}", 'PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS');
    $row++;
    $sheet->mergeCells("A{$row}:F{$row}")->setCellValue("A{$row}", 'RESUMEN DE MEDICIONES DE RUIDO E ILUMINACION / SUMMARY OF NOISE AND LIGHTING MEASUREMENTS');
    $sheet->getStyle('A1:F3')->applyFromArray($centerBold);
    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->getRowDimension(2)->setRowHeight(22);
    $sheet->getRowDimension(3)->setRowHeight(26);

    if ($this->year) {
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}")->setCellValue("A{$row}", 'Anio: ' . $this->year);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($left);
    } else {
        $row++;
    }

    // ---- Datos base ----
    $locNames = DB::table('localizacion')->pluck('localizacion', 'id_localizacion');

    // EM por localización (límite aceptable)
    $emByLoc = DB::table('estandar_iluminacion')
        ->select('id_localizacion', DB::raw('MAX(em) as em'))
        ->groupBy('id_localizacion')
        ->pluck('em', 'id_localizacion');

    $records = DB::table('mediciones_iluminacion as m')
        ->leftJoin('puesto_trabajo_matriz as p', 'p.id_puesto_trabajo_matriz', '=', 'm.id_puesto_trabajo_matriz')
        ->select(
            'm.id',
            'm.id_localizacion',
            'm.punto_medicion',
            'p.puesto_trabajo_matriz as puesto',
            'm.promedio',
            'm.acciones_correctivas'
        )
        ->when($this->locId, fn($q) => $q->where('m.id_localizacion', $this->locId))
        ->when($this->year, fn($q) => $q->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) = ?', [$this->year]))
        ->orderBy('m.id_localizacion')
        ->orderBy('m.punto_medicion')
        ->orderBy('m.id')
        ->get();

    $groups = $records->groupBy('id_localizacion');

    // ---- Secciones por localización ----
    $hasRows = false;
    foreach ($locNames as $locId => $locName) {
        $rowsData = $groups->get($locId);
        if (!$rowsData || $rowsData->isEmpty()) {
            continue;
        }

        $hasRows = true;

        // Título de sección
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", Str::upper($locName));
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($centerBold);
        $sheet->getStyle("A{$row}:F{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colorHeader);
        $sheet->getRowDimension($row)->setRowHeight(20);

        // Encabezados de tabla
        $row++;
        $firstHeaderRow = $row;
        $sheet->setCellValue("A{$row}", 'No.');
        $sheet->setCellValue("B{$row}", 'ZONA MEDICION');
        $sheet->setCellValue("C{$row}", 'PUESTO DE TRABAJO');
        $sheet->setCellValue("D{$row}", 'NIVEL ILUMINACION');
        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->setCellValue("F{$row}", 'ACCIONES CORRECTIVAS');
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($centerBoldWhite);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;
        $sheet->setCellValue("D{$row}", 'MEDIA');
        $sheet->setCellValue("E{$row}", 'LIMITES ACEPTABLES');
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($centerBoldWhiteDark);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $sheet->getStyle('A' . $firstHeaderRow . ":F{$row}")->applyFromArray($thinBorders);

        // Filas de la sección
        $n = 1;
        foreach ($rowsData as $data) {
            $row++;
            $em = $emByLoc[$data->id_localizacion] ?? null;

            $sheet->setCellValue("A{$row}", $n++);
            $sheet->setCellValue("B{$row}", $data->punto_medicion ?: '');
            $sheet->setCellValue("C{$row}", $data->puesto ?: '');
            $sheet->setCellValue("D{$row}", $this->fmt($data->promedio));
            $sheet->setCellValue("E{$row}", $this->fmt($em, 0));   // EM como límite
            $sheet->setCellValue("F{$row}", $data->acciones_correctivas ?: '');

            $sheet->getStyle("A{$row}")->applyFromArray($centerBold);
            $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($left);
            $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($right);
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($thinBorders);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // ---- Fila en blanco entre localizaciones ----
        $row++;
        $sheet->getRowDimension($row)->setRowHeight(8); // pequeño espacio visual
        // (no bordes ni valores)
    }

    if (!$hasRows) {
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'Sin mediciones de iluminacion para los filtros seleccionados.');
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($centerBold);
    }

    $row += 2; // espacio antes del pie

    // Izquierda (dos líneas) en columna B
    $sheet->setCellValue("B{$row}", '1 Copia Archivo');
    $sheet->getStyle("B{$row}")->applyFromArray($small)->applyFromArray($left);
    $row++;

    $sheet->setCellValue("B{$row}", '1 Copia Sistema');
    $sheet->getStyle("B{$row}")->applyFromArray($small)->applyFromArray($left);
    $row++;

    // Misma fila: centro y derecha
    $sheet->setCellValue("B{$row}", 'VERSION 2018');
    $sheet->getStyle("B{$row}")->applyFromArray($small)->applyFromArray($center);

    $sheet->setCellValue("C{$row}", 'STB/SSO/R040');
    $sheet->getStyle("C{$row}")->applyFromArray($small)->applyFromArray($left);

    // (Opcional) altura de las filas del pie
    $sheet->getRowDimension($row-2)->setRowHeight(16);
    $sheet->getRowDimension($row-1)->setRowHeight(16);
    $sheet->getRowDimension($row  )->setRowHeight(16);

    return $spreadsheet;
}

    private function fmt($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') return '';
        if (!is_numeric($value)) return (string)$value;
        return number_format((float)$value, $decimals);
    }
}
