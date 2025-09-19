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

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(46);

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

        $locNames = DB::table('localizacion')->pluck('localizacion', 'id_localizacion');

        $records = DB::table('mediciones_iluminacion as m')
            ->leftJoin('puesto_trabajo_matriz as p', 'p.id_puesto_trabajo_matriz', '=', 'm.id_puesto_trabajo_matriz')
            ->select(
                'm.id',
                'm.id_localizacion',
                'm.punto_medicion',
                'p.puesto_trabajo_matriz as puesto',
                'm.promedio',
                'm.limites_aceptables',
                'm.acciones_correctivas'
            )
            ->when($this->locId, fn($q) => $q->where('m.id_localizacion', $this->locId))
            ->when($this->year, fn($q) => $q->whereRaw('YEAR(COALESCE(m.fecha_realizacion_inicio, m.fecha_realizacion_final)) = ?', [$this->year]))
            ->orderBy('m.id_localizacion')
            ->orderBy('m.punto_medicion')
            ->orderBy('m.id')
            ->get();

        $groups = $records->groupBy('id_localizacion');

        $hasRows = false;
        foreach ($locNames as $locId => $locName) {
            $rows = $groups->get($locId);
            if (!$rows || $rows->isEmpty()) {
                continue;
            }

            $hasRows = true;
            $row++;
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", Str::upper($locName));
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($centerBold);
            $sheet->getStyle("A{$row}:F{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($colorHeader);
            $sheet->getRowDimension($row)->setRowHeight(20);

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

            $n = 1;
            foreach ($rows as $data) {
                $row++;
                $sheet->setCellValue("A{$row}", $n++);
                $sheet->setCellValue("B{$row}", $data->punto_medicion ?: '');
                $sheet->setCellValue("C{$row}", $data->puesto ?: '');
                $sheet->setCellValue("D{$row}", $this->fmt($data->promedio));
                $sheet->setCellValue("E{$row}", $this->fmt($data->limites_aceptables, 0));
                $sheet->setCellValue("F{$row}", $data->acciones_correctivas ?: '');

                $sheet->getStyle("A{$row}")->applyFromArray($centerBold);
                $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($left);
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($right);
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($thinBorders);
                $sheet->getRowDimension($row)->setRowHeight(18);
            }
        }

        if (!$hasRows) {
            $row++;
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", 'Sin mediciones de iluminacion para los filtros seleccionados.');
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($centerBold);
        }

        return $spreadsheet;
    }

    private function fmt($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, $decimals);
    }
}
