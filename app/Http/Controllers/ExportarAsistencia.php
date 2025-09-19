<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as SheetDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Carbon\Carbon;

class ExportarAsistencia extends Controller
{
    // Normaliza cadenas como "14/08/2025", "14-08-2025", "Del 15/07/2025 al 18/07/2025" a 'Y-m-d'.
    private function parseFechaVarcharToYmd($s): ?string
    {
        if (!is_string($s) || trim($s) === '') return null;
        $text = trim($s);

        // Captura dd/mm/yyyy o dd-mm-yyyy o dd.mm.yyyy (una o dos fechas)
        if (preg_match_all('/(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})/u', $text, $all, PREG_SET_ORDER)) {
            $dates = [];
            foreach ($all as $m) {
                $d  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $y  = $m[3];
                try {
                    $dt = Carbon::createFromFormat('d/m/Y', "$d/$mo/$y");
                    if ($dt !== false) $dates[] = $dt->toDateString();
                } catch (\Throwable $e) { /* ignore */ }
            }
            if (count($dates) >= 2) return $dates[1]; // preferir fin de rango
            if (count($dates) >= 1) return $dates[0];
        }

        // Formato ISO o variantes Y-m-d / Y/m/d
        if (preg_match('/(\d{4})[\/-](\d{2})[\/-](\d{2})/', $text, $m)) {
            $y = $m[1]; $mo = $m[2]; $d = $m[3];
            try { return Carbon::createFromFormat('Y-m-d', "$y-$mo-$d")->toDateString(); } catch (\Throwable $e) {}
        }

        // Último recurso: intentar parseo libre
        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
    private function tempCopyFromZip(string $zipPath): string
    {
        $zipPath = str_replace('\\', '/', $zipPath);
        $data = @file_get_contents($zipPath);
        if ($data === false) {
            throw new \RuntimeException("No se pudo leer la imagen: $zipPath");
        }
        $ext = pathinfo($zipPath, PATHINFO_EXTENSION) ?: 'png';
        $tmp = tempnam(sys_get_temp_dir(), 'phpss_img_') . '.' . $ext;
        file_put_contents($tmp, $data);
        return $tmp;
    }

    private function cloneDrawingsWithOffset(Worksheet $src, Worksheet $dst, int $offsetRows, array &$tempFiles): void
    {
        foreach ($src->getDrawingCollection() as $drawing) {
            if ($drawing instanceof SheetDrawing) {
                $new = new SheetDrawing();
                $new->setName($drawing->getName());
                $new->setDescription($drawing->getDescription());
                $new->setHeight($drawing->getHeight());
                $new->setWidth($drawing->getWidth());
                $new->setOffsetX($drawing->getOffsetX());
                $new->setOffsetY($drawing->getOffsetY());
                $new->setResizeProportional($drawing->getResizeProportional());

                $path = $drawing->getPath();
                if (str_starts_with($path, 'zip://')) {
                    $tmp = $this->tempCopyFromZip($path);
                    $tempFiles[] = $tmp;
                    $new->setPath($tmp);
                } else {
                    $new->setPath($path);
                }

                [$col, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());
                $new->setCoordinates($col . ($row + $offsetRows));
                $new->setWorksheet($dst);
            } elseif ($drawing instanceof MemoryDrawing) {
                $new = new MemoryDrawing();
                $new->setName($drawing->getName());
                $new->setDescription($drawing->getDescription());
                $new->setImageResource($drawing->getImageResource());
                $new->setRenderingFunction($drawing->getRenderingFunction());
                $new->setMimeType($drawing->getMimeType());
                $new->setHeight($drawing->getHeight());
                $new->setWidth($drawing->getWidth());
                $new->setOffsetX($drawing->getOffsetX());
                $new->setOffsetY($drawing->getOffsetY());

                [$col, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());
                $new->setCoordinates($col . ($row + $offsetRows));
                $new->setWorksheet($dst);
            }
        }
    }

    private function buildDrawingBlueprints(Worksheet $sheet): array
    {
        $bps = [];
        foreach ($sheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof SheetDrawing) {
                $bps[] = [
                    'type' => 'sheet',
                    'name' => $drawing->getName(),
                    'desc' => $drawing->getDescription(),
                    'path' => $drawing->getPath(),
                    'coords' => $drawing->getCoordinates(),
                    'ox' => $drawing->getOffsetX(),
                    'oy' => $drawing->getOffsetY(),
                    'w'  => $drawing->getWidth(),
                    'h'  => $drawing->getHeight(),
                    'rp' => $drawing->getResizeProportional(),
                ];
            } elseif ($drawing instanceof MemoryDrawing) {
                $bps[] = [
                    'type' => 'memory',
                    'name' => $drawing->getName(),
                    'desc' => $drawing->getDescription(),
                    'coords' => $drawing->getCoordinates(),
                    'ox' => $drawing->getOffsetX(),
                    'oy' => $drawing->getOffsetY(),
                    'w'  => $drawing->getWidth(),
                    'h'  => $drawing->getHeight(),
                    'rf' => $drawing->getRenderingFunction(),
                    'mt' => $drawing->getMimeType(),
                    'ir' => $drawing->getImageResource(),
                ];
            }
        }
        return $bps;
    }

    private function cloneDrawingsFromBlueprints(array $bps, Worksheet $dst, int $offsetRows, array &$tempFiles): void
    {
        foreach ($bps as $bp) {
            [$col, $row] = Coordinate::coordinateFromString($bp['coords']);
            $target = $col . ($row + $offsetRows);
            if ($bp['type'] === 'sheet') {
                $new = new SheetDrawing();
                $new->setName($bp['name']);
                $new->setDescription($bp['desc']);
                $new->setHeight($bp['h']);
                $new->setWidth($bp['w']);
                $new->setOffsetX($bp['ox']);
                $new->setOffsetY($bp['oy']);
                $new->setResizeProportional($bp['rp']);

                $path = $bp['path'];
                if (is_string($path) && str_starts_with($path, 'zip://')) {
                    $tmp = $this->tempCopyFromZip($path);
                    $tempFiles[] = $tmp;
                    $new->setPath($tmp);
                } else {
                    $new->setPath($path);
                }
                $new->setCoordinates($target);
                $new->setWorksheet($dst);
            } elseif ($bp['type'] === 'memory') {
                $new = new MemoryDrawing();
                $new->setName($bp['name']);
                $new->setDescription($bp['desc']);
                $new->setImageResource($bp['ir']);
                $new->setRenderingFunction($bp['rf']);
                $new->setMimeType($bp['mt']);
                $new->setHeight($bp['h']);
                $new->setWidth($bp['w']);
                $new->setOffsetX($bp['ox']);
                $new->setOffsetY($bp['oy']);
                $new->setCoordinates($target);
                $new->setWorksheet($dst);
            }
        }
    }

    private function computeBlockBottom(Worksheet $sheet, string $highestCol, int $filaInicio, int $filasPorBloque): int
    {

    return $filaInicio + $filasPorBloque - 1;
    }

    private function unmergeRange(Worksheet $sheet, int $top, int $bottom, string $leftCol, string $rightCol): void
    {
        $toRemove = [];
        foreach ($sheet->getMergeCells() as $merge) {
            [$s, $e] = [Coordinate::rangeBoundaries($merge)[0], Coordinate::rangeBoundaries($merge)[1]];
            $sc = Coordinate::stringFromColumnIndex($s[0]);
            $ec = Coordinate::stringFromColumnIndex($e[0]);
            $sr = $s[1]; $er = $e[1];

            if (!($er < $top || $sr > $bottom || $ec < $leftCol || $sc > $rightCol)) {
                $toRemove[] = $merge;
            }
        }
        foreach ($toRemove as $m) { $sheet->unmergeCells($m); }
    }

private function uniqueSheetTitle(\PhpOffice\PhpSpreadsheet\Spreadsheet $wb, string $base = 'Registro ', int $startNum = 1): string
{
    $maxLen = 31; // límite Excel
    $existing = array_map(fn($s) => $s->getTitle(), $wb->getAllSheets());

    $n = $startNum;
    do {
        $title = $base . $n;
        if (strlen($title) > $maxLen) {
            $title = substr($title, 0, $maxLen);
        }
        $n++;
    } while (in_array($title, $existing, true));

    return $title;
}

public function exportCapacitacion($idEmpleado)
{
    // === Parámetros del formato ===
    $FILAS_POR_BLOQUE = 11; // filas de la tabla por hoja
    $FILA_INICIO      = 12; // primera fila de la tabla
    $CAB_NOMBRE_ROW   = 8;  // fila donde va el nombre (B y G)
    $CAB_PUESTO_ROW   = 9;  // fila donde va el puesto/depto (B y G)
    $HIGHEST_COL      = 'G';
    $PRINT_BOTTOM     = 28; // última fila del área de impresión

    // === 1) Cargar plantilla ===
    $templatePath = storage_path('app/public/formato_capacitaciones.xlsx');
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $reader->setIncludeCharts(true); // si tu plantilla tiene gráficos
    $spreadsheet  = $reader->load($templatePath);

    // Hoja base de la plantilla
    $templateSheet = $spreadsheet->getSheet(0);

    // === 2) Datos ===
    $regs = DB::table('asistencia_capacitacion as ac')
        ->join('empleado as e', 'ac.id_empleado', '=', 'e.id_empleado')
        ->join('puesto_trabajo as pt', 'e.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
        ->join('capacitacion_instructor as ci', 'ac.id_capacitacion_instructor', '=', 'ci.id_capacitacion_instructor')
        ->join('capacitacion as c', 'ci.id_capacitacion', '=', 'c.id_capacitacion')
        ->leftJoin('instructor as i', 'ci.id_instructor', '=', 'i.id_instructor')
        ->where('e.id_empleado', $idEmpleado)
        ->select(
            'e.nombre_completo',
            'e.codigo_empleado',
            'pt.puesto_trabajo',
            'pt.departamento',
            'ac.fecha_recibida',
            DB::raw('COALESCE(ac.instructor_temporal, i.instructor) as instructor'),
            'c.capacitacion'
        )
        ->get();

    if ($regs->isEmpty()) {
        return back()->with('error', 'No se encontraron registros.');
    }

    $info = (object)[
        'nombre'       => $regs[0]->nombre_completo,
        'puesto'       => $regs[0]->puesto_trabajo,
        'codigo'       => $regs[0]->codigo_empleado,
        'departamento' => $regs[0]->departamento,
    ];

    // Normalizar fecha a Y-m-d y ordenar
    $regs = $regs->map(function ($r) {
        $r->fecha_ymd = $this->parseFechaVarcharToYmd($r->fecha_recibida);
        return $r;
    })->sortBy(function ($r) {
        return $r->fecha_ymd ?? '9999-12-31';
    })->values();

    // === 3) Partir en chunks de 11 y crear una hoja por chunk ===
    $chunks = $regs->chunk($FILAS_POR_BLOQUE)->values();

    foreach ($chunks as $idx => $chunk) {
    if ($idx === 0) {
        // usa la hoja de la plantilla y renómbrala a algo único
        $sheet = $templateSheet;
        $sheet->setTitle($this->uniqueSheetTitle($spreadsheet, 'Registro ', 1));
    } else {
        // clona SIEMPRE desde la plantilla base (no desde una ya renombrada)
        $sheet = clone $templateSheet;

        // asigna un título único ANTES de agregarla al workbook
        $newTitle = $this->uniqueSheetTitle($spreadsheet, 'Registro ', $idx + 1);
        $sheet->setTitle($newTitle);

        // ahora sí, agregar
        $spreadsheet->addSheet($sheet);
    }

    // ---- desde aquí tu relleno normal ----
    $sheet->setCellValue('B' . $CAB_NOMBRE_ROW, $info->nombre);
    $sheet->setCellValue('B' . $CAB_PUESTO_ROW, $info->puesto);
    $sheet->setCellValue('G' . $CAB_NOMBRE_ROW, $info->codigo);
    $sheet->setCellValue('G' . $CAB_PUESTO_ROW, $info->departamento);

    // Limpiar 11 filas (A..G)
    for ($i = 0; $i < $FILAS_POR_BLOQUE; $i++) {
        $row = $FILA_INICIO + $i;
        foreach (['A','B','C','D','E','F','G'] as $col) {
            $sheet->setCellValue($col.$row, null);
        }
    }

    // Rellenar
    $fila = $FILA_INICIO;
    foreach ($chunk as $cap) {
        $fechaOut = $cap->fecha_ymd ? \Carbon\Carbon::createFromFormat('Y-m-d', $cap->fecha_ymd)->format('d/m/Y') : null;
        $sheet->setCellValue("A{$fila}", $fechaOut);
        $sheet->setCellValue("B{$fila}", $cap->instructor);
        $sheet->setCellValue("D{$fila}", $cap->capacitacion);
        $fila++;
    }

    // Config de impresión (si aplica)
    $sheet->getPageSetup()->setPrintArea("A1:{$HIGHEST_COL}{$PRINT_BOTTOM}");
    $sheet->getPageSetup()->setFitToPage(false);
    $sheet->getPageSetup()->setScale(100);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    $sheet->getPageSetup()->setHorizontalCentered(true);
    $sheet->setSelectedCell('A1');
}


    // Dejar activa la primera hoja
    $spreadsheet->setActiveSheetIndex(0);

    // === 4) Enviar por stream (evita archivos corruptos) ===
    $safeName = preg_replace('/\s+/', '_', $info->nombre);
    $fileName = "Registro_Capacitaciones_{$safeName}.xlsx";

    // Limpia buffers para no mezclar bytes ajenos con el ZIP del XLSX
    while (ob_get_level() > 0) { ob_end_clean(); }

    return response()->streamDownload(function () use ($spreadsheet) {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets(); // liberación de memoria
    }, $fileName, [
        'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        'Pragma'        => 'public',
    ]);
}

}
