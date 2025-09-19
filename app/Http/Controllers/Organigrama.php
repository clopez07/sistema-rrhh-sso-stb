<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;


class Organigrama extends Controller
{
    public function niveles()
    {
         // Traer los nombres de los instructores
        $niveles = DB::select('CALL sp_obtener_niveles');

        // Pasar a la vista
        return view('organigrama.niveles', compact('niveles'));
    }
    
    public function storeniveles(Request $request)
    {
        // Insertar en la base de datos
        DB::table('nivel_jerarquico')->insert([
            'nivel_jerarquico' => $request->input('nivel_jerarquico'),
            'num_nivel' => $request->input('num_nivel'),
        ]);

        return redirect()->back()->with('success', 'Agregado correctamente');
    }

    public function updateniveles(Request $request, $id)
    {
        // Actualizar el departamento
        DB::table('nivel_jerarquico')
            ->where('id_nivel_jerarquico', $id)
            ->update([
                'nivel_jerarquico' => $request->input('nivel_jerarquico'),
                'num_nivel' => $request->input('num_nivel'),
            ]);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroyniveles($id)
    {
        DB::table('nivel_jerarquico')->where('id_nivel_jerarquico', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    public function subordinados()
    {
        // Consulta para traer nombre completo, código de empleado y puesto de trabajo
        $subordinados = DB::select('CALL sp_obtener_subordinados()');
        $puestos = DB::select('CALL sp_obtener_puestos_trabajo()');
        // Pasar a la vista
        return view('organigrama.subordinado', compact('subordinados', 'puestos'));
    }

    public function storesubordinados(Request $request)
    {
        $request->validate([
            'id_puesto_trabajo' => 'required|exists:puesto_trabajo,id_puesto_trabajo',
            'subordinado' => 'required|array',
            'subordinado.*' => 'exists:puesto_trabajo,id_puesto_trabajo'
        ]);
        foreach ($request->subordinado as $idSubordinado) {
            DB::table('puesto_subordinado')->insert([
                'id_puesto_trabajo_matriz' => $request->id_puesto_trabajo,
                'id_puesto_subordinado' => $idSubordinado,
            ]);
        }
        return redirect()->back()->with('success', 'Subordinados asignados correctamente.');
    }

    public function updatesubordinados(Request $request)
    {
        $request->validate([
            'id_puesto_trabajo_matriz' => 'required|exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz',
            'subordinado' => 'required|array',
            'subordinado.*' => 'exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz'
        ]);

        // Eliminar subordinados actuales del puesto
        DB::table('puesto_subordinado')
            ->where('id_puesto_trabajo_matriz', $request->id_puesto_trabajo_matriz)
            ->delete();

        // Insertar nuevos subordinados
        foreach ($request->subordinado as $idSubordinado) {
            DB::table('puesto_subordinado')->insert([
                'id_puesto_trabajo_matriz' => $request->id_puesto_trabajo_matriz,
                'id_puesto_subordinado' => $idSubordinado,
            ]);
        }

        return redirect()->back()->with('success', 'Subordinados actualizados correctamente.');
    }

    public function destroysubordinados($id)
    {
        DB::table('puesto_subordinado')->where('id_subordinado', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

        public function matrizpuestos(Request $request)
{
    $searchRaw = trim($request->query('search', ''));
    $rows = collect(DB::select('CALL sp_obtener_matriz_puestos()'));

    // === Helpers de normalización / nivel canónico ===
    $normalizePlain = function ($s) {
        $s = (string)($s ?? '');
        if ($s === '') return '';
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s; // quita acentos
        $s = preg_replace('/\s+/', ' ', $s);
        return trim(mb_strtolower($s));
    };
    $canonLevel = function ($txt) {
        $t = (string)($txt ?? '');
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t;
        $t = strtoupper(preg_replace('/[^A-Z0-9]+/', '', $t));
        if ($t === '') return '';
        // Numérico 1..7
        if (preg_match('/^[1-7]$/', $t)) {
            $numMap = [1=>'DIRECTIVO',2=>'GERENCIAL',3=>'ESTRATEGICO',4=>'OPERATIVO',5=>'COORDINACION',6=>'APOYOAUXILIAR',7=>'EJECUCION'];
            return $numMap[(int)$t] ?? '';
        }
        // Sinónimos/léxicos
        if (str_contains($t,'GERENCIA'))     return 'GERENCIAL';
        if (str_contains($t,'ESTRATEG'))     return 'ESTRATEGICO';
        if (str_contains($t,'OPERAT'))       return 'OPERATIVO';
        if (str_contains($t,'COORD'))        return 'COORDINACION';
        if (str_contains($t,'APOYO') || str_contains($t,'AUXILIAR')) return 'APOYOAUXILIAR';
        if (str_contains($t,'EJECUC'))       return 'EJECUCION';
        return $t; // ya venía como DIRECTIVO/…
    };

    // === Filtrado por búsqueda (puesto + nivel por texto/alias/número + id y num_nivel) ===
    if ($searchRaw !== '') {
        $q = $normalizePlain($searchRaw);
        $isNumeric = ctype_digit($searchRaw);
        $qCanonLevel = $canonLevel($searchRaw); // puede ser vacío si no aplica

        $matrizpuestos = $rows->filter(function ($r) use ($q, $isNumeric, $qCanonLevel, $canonLevel, $normalizePlain) {

            // Puesto (ambos campos posibles)
            $byPuesto = str_contains($normalizePlain($r->puesto_trabajo_matriz ?? ''), $q)
                     || str_contains($normalizePlain($r->puesto_actual ?? ''), $q);

            // Nivel por texto directo
            $byNivelTexto = str_contains($normalizePlain($r->nivel_jerarquico ?? ''), $q);

            // Nivel por alias/canónico (incluye números 1..7 mapeados)
            $rowCanon = $canonLevel($r->nivel_jerarquico ?? '');
            $byNivelCanon = $qCanonLevel !== '' && $rowCanon === $qCanonLevel;

            // Búsqueda por número: id del puesto o num_nivel
            $byNumero = false;
            if ($isNumeric) {
                $n = (int)$q;
                $idStr = (string)($r->id_puesto_trabajo_matriz ?? '');
                $byId  = ($idStr === (string)$n); // id exacto

                $numNivel = (int)($r->num_nivel ?? 0);
                // También conciliar si el número coincide con el orden del nivel
                $orderMap = [
                    'DIRECTIVO'=>1,'GERENCIAL'=>2,'ESTRATEGICO'=>3,'OPERATIVO'=>4,
                    'COORDINACION'=>5,'APOYOAUXILIAR'=>6,'EJECUCION'=>7
                ];
                $byNumNivel = ($numNivel === $n) || (($orderMap[$rowCanon] ?? 0) === $n);

                $byNumero = $byId || $byNumNivel;
            }

            return $byPuesto || $byNivelTexto || $byNivelCanon || $byNumero;
        })->values();
    } else {
        $matrizpuestos = $rows;
    }

    // === Ordenar por nivel jerárquico para agrupar colores (igual que tenías) ===
    $orderMap = [
        'DIRECTIVO' => 1,
        'GERENCIAL' => 2,
        'ESTRATEGICO' => 3,
        'OPERATIVO' => 4,
        'COORDINACION' => 5,
        'APOYOAUXILIAR' => 6,
        'EJECUCION' => 7,
    ];
    $normalizeKey = function($txt) {
        $txt = (string) $txt;
        $txt = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$txt) ?: $txt;
        $txt = strtoupper($txt);
        return preg_replace('/[^A-Z0-9]+/', '', $txt);
    };
    $matrizpuestos = $matrizpuestos->sort(function($a,$b) use ($orderMap, $normalizeKey) {
        $ka = $normalizeKey($a->nivel_jerarquico ?? '');
        $kb = $normalizeKey($b->nivel_jerarquico ?? '');
        $oa = $orderMap[$ka] ?? 999;
        $ob = $orderMap[$kb] ?? 999;
        if ($oa === $ob) {
            $na = (string)($a->puesto_trabajo_matriz ?? $a->puesto_actual ?? '');
            $nb = (string)($b->puesto_trabajo_matriz ?? $b->puesto_actual ?? '');
            return strnatcasecmp($na, $nb);
        }
        return $oa <=> $ob;
    })->values();

    // === Adjuntar num_empleados si no viene ===
    try {
        $numMap = collect(DB::table('puesto_trabajo_matriz')
            ->pluck('num_empleados', 'id_puesto_trabajo_matriz'));
    } catch (\Throwable $e) {
        $numMap = collect();
    }
    $matrizpuestos = $matrizpuestos->map(function ($r) use ($numMap) {
        if (!isset($r->num_empleados)) {
            $id = $r->id_puesto_trabajo_matriz ?? null;
            $r->num_empleados = (int) ($numMap->get($id) ?? 0);
        }
        return $r;
    });

    $niveles = DB::select('CALL sp_obtener_niveles()');
    $puestostrabajo = DB::select('CALL sp_obtener_puestos_trabajo()');

    // === Empleados por puesto (igual que tenías) ===
    $empleadosPorPuesto = [];
    try {
        $rowsEmp = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as pt', 'pt.id_puesto_trabajo', '=', 'e.id_puesto_trabajo')
            ->select('e.nombre_completo', 'pt.puesto_trabajo')
            ->where(function($q){
                $q->whereNull('e.estado')->orWhere('e.estado', '<>', 0);
            })
            ->get();

        $norm = function ($s) {
            $s = (string)($s ?? '');
            $s = trim($s);
            if ($s === '') return '';
            $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
            $s = strtoupper($s);
            return preg_replace('/\s+/', ' ', $s);
        };

        foreach ($rowsEmp as $re) {
            $k = $norm($re->puesto_trabajo);
            if ($k === '') continue;
            if (!isset($empleadosPorPuesto[$k])) $empleadosPorPuesto[$k] = [];
            $empleadosPorPuesto[$k][] = $re->nombre_completo;
        }
    } catch (\Throwable $e) {
        $empleadosPorPuesto = [];
    }

    return view('organigrama.matrizpuestos', compact('matrizpuestos', 'niveles', 'puestostrabajo', 'empleadosPorPuesto'));
}


public function storematrizpuestos(Request $request)
{
    // Si no hay subordinados, insertamos solo el puesto principal
    if (empty($request->subordinado) || count($request->subordinado) === 0) {
        DB::table('organigrama')->insert([
            'id_puesto_trabajo_matriz' => $request->input('id_puesto_trabajo'),
            'id_nivel_jerarquico' => $request->input('id_nivel_jerarquico'),
            'id_puesto_superior' => $request->input('id_puesto_superior'),
            'id_puesto_subordinado' => null
        ]);
    } else {
        // Insertar una fila por cada subordinado
        foreach ($request->subordinado as $idSubordinado) {
            DB::table('organigrama')->insert([
                'id_puesto_trabajo_matriz' => $request->input('id_puesto_trabajo'),
                'id_nivel_jerarquico' => $request->input('id_nivel_jerarquico'),
                'id_puesto_superior' => $request->input('id_puesto_superior'),
                'id_puesto_subordinado' => $idSubordinado
            ]);
        }
    }

    return redirect()->back()->with('success', 'Agregado correctamente');
}

    public function updatematrizpuestos(Request $request, $id)
    {
        // Limpiar registros actuales para este puesto en la tabla organigrama
        DB::table('organigrama')->where('id_puesto_trabajo_matriz', $id)->delete();

        $idPuesto = $id; // confiar en el parámetro de ruta
        $idNivel = $request->input('id_nivel_jerarquico');
        $idSuperior = $request->input('id_puesto_superior');
        $subs = $request->input('subordinado', []);

        if (empty($subs)) {
            DB::table('organigrama')->insert([
                'id_puesto_trabajo_matriz' => $idPuesto,
                'id_nivel_jerarquico' => $idNivel,
                'id_puesto_superior' => $idSuperior,
                'id_puesto_subordinado' => null,
            ]);
        } else {
            foreach ($subs as $idSub) {
                DB::table('organigrama')->insert([
                    'id_puesto_trabajo_matriz' => $idPuesto,
                    'id_nivel_jerarquico' => $idNivel,
                    'id_puesto_superior' => $idSuperior,
                    'id_puesto_subordinado' => $idSub,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroymatrizpuestos($id)
    {
        DB::table('organigrama')->where('id_puesto_trabajo_matriz', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }


public function exportMatrizpuestos()
{
    // Datos base
    $matriz = DB::select('CALL sp_obtener_matriz_puestos()');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Matriz de Puestos');
    $sheet->getDefaultRowDimension()->setRowHeight(18);

    // Logo (opcional)
    $logoPath = public_path('img/logo.png');
    if (is_file($logoPath)) {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(48);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
    }

    // Títulos
    $sheet->mergeCells('B1:G1');
    $sheet->setCellValue('B1', 'SERVICE AND TRADING BUSINESS S.A.');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B1')->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('B2:G2');
    $sheet->setCellValue('B2', 'PROCESO DE RECURSOS HUMANOS/ HUMAN RESOURCES PROCESS');
    $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B2')->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('B3:G3');
    $sheet->setCellValue('B3', 'ORGANIGRAMA Y MATRIZ DE PUESTO/ ORGANIZATIONAL CHART AND PLACE MATRIX');
    $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B3')->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('G4', 'Descargado: ' . now()->format('d/m/Y H:i'));
    $sheet->getStyle('G4')->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G4')->getFont()->getColor()->setARGB('FF6B7280');

    // ===== Encabezados (A:G) con ITEM y NUM NIVEL =====
    $headerRow = 5;
    $headers = [
        'A' => 'Item',                 // <-- NUEVO
        'B' => 'Puesto de Trabajo',
        'C' => 'Nivel Jerárquico',
        'D' => 'Num Nivel',            // <-- asegurar que salga
        'E' => 'Superior Inmediato',
        'F' => 'Subordinado',
        'G' => 'Objetivo del Puesto',
    ];
    foreach ($headers as $col => $title) {
        $sheet->setCellValue($col . $headerRow, $title);
    }
    $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFF3F4F6'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FFE5E7EB']
            ],
        ],
    ]);
    $sheet->getRowDimension($headerRow)->setRowHeight(22);

    // Orden y mapeo de niveles (sirve también para calcular num_nivel si no viene)
    $nivelMap = [
        'DIRECTIVO'     => 1,
        'GERENCIAL'     => 2,
        'ESTRATEGICO'   => 3,
        'OPERATIVO'     => 4,
        'COORDINACION'  => 5,
        'APOYOAUXILIAR' => 6,
        'EJECUCION'     => 7,
    ];
    $normalize = function ($s) {
        return strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string)($s ?? '')));
    };
    usort($matriz, function ($a, $b) use ($nivelMap, $normalize) {
        $oa = $nivelMap[$normalize($a->nivel_jerarquico ?? '')] ?? 999;
        $ob = $nivelMap[$normalize($b->nivel_jerarquico ?? '')] ?? 999;
        if ($oa !== $ob) return $oa <=> $ob;
        return strcmp((string)($a->puesto_actual ?? ''), (string)($b->puesto_actual ?? ''));
    });

    // ===== Datos =====
    $row = $headerRow + 1;
    $item = 1;

    foreach ($matriz as $registro) {
        // Calcular num_nivel si no viene del SP
        $nivToken  = $normalize($registro->nivel_jerarquico ?? '');
        $numNivel  = isset($registro->num_nivel) && $registro->num_nivel !== ''
                   ? $registro->num_nivel
                   : ($nivelMap[$nivToken] ?? null);

        // Celdas (A:G)
        $sheet->setCellValue("A{$row}", $item);                      // ITEM
        $sheet->setCellValue("B{$row}", $registro->puesto_actual);
        $sheet->setCellValue("C{$row}", $registro->nivel_jerarquico);
        $sheet->setCellValue("D{$row}", $numNivel);                  // NUM NIVEL
        $sheet->setCellValue("E{$row}", $registro->puesto_superior);
        $sheet->setCellValue("F{$row}", $registro->puestos_subordinados);
        $sheet->setCellValue("G{$row}", $registro->objetivo_puesto);

        // Color por nivel (igual a la vista)
        $nivelColors = [
            'DIRECTIVO'      => 'FF305496',
            'GERENCIAL'      => 'FF8EA9DB',
            'ESTRATEGICO'    => 'FFB4C6E7',
            'OPERATIVO'      => 'FFD9E1F2',
            'COORDINACION'   => 'FFF2F2F2',
            'APOYOAUXILIAR'  => 'FFD9D9D9',
            'EJECUCION'      => 'FFAEAAAA',
        ];
        $fillColor = $nivelColors[$nivToken] ?? null;
        if ($fillColor) {
            $rowRange = "A{$row}:G{$row}";
            $sheet->getStyle($rowRange)->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB($fillColor);
            $sheet->getStyle($rowRange)->getBorders()->getLeft()
                  ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)
                  ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($fillColor));
        } elseif ($row % 2 === 0) {
            $sheet->getStyle("A{$row}:G{$row}")->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFF9FAFB');
        }

        $item++;
        $row++;
    }

    $lastRow = max($row - 1, $headerRow);
    $sheet->getStyle("A{$headerRow}:G{$lastRow}")
        ->getBorders()->getAllBorders()
        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
        ->getColor()->setARGB('FFE5E7EB');

        // === Pie de página al final de la tabla ===
// === Pie de página al final de la tabla (layout como la imagen) ===
$footerTop = $lastRow + 2;     // fila "1 Copia Archivo"
$footerRow = $footerTop + 1;   // fila "1 Copia sistema"
$verRow    = $footerRow + 1;   // una fila abajo de "1 Copia sistema"

$blue = new \PhpOffice\PhpSpreadsheet\Style\Color('FF1F4E79');

// Izquierda (dos líneas)
$sheet->setCellValue("A{$footerTop}", '1 Copia Archivo');
$sheet->setCellValue("A{$footerRow}", '1 Copia sistema');
$sheet->getStyle("A{$footerTop}:A{$footerRow}");

// Centro (una fila abajo que “1 Copia sistema”, en la segunda columna)
$sheet->mergeCells("B{$verRow}:E{$verRow}");
$sheet->setCellValue("B{$verRow}", '8 VERSION 2025');
$sheet->getStyle("B{$verRow}");
$sheet->getStyle("B{$verRow}")->getAlignment()
      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

// Misma fila que “8 VERSION 2025”, a la derecha
$sheet->mergeCells("F{$verRow}:G{$verRow}");
$sheet->setCellValue("F{$verRow}", 'STB/RRHH/001');
$sheet->getStyle("F{$verRow}")->getAlignment()
      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

// (Opcional) Línea separadora arriba del bloque
$sepRange = "A".($footerTop-1).":G".($footerTop-1);
$sheet->getStyle($sepRange)->getBorders()->getTop()
      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
      ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF9CA3AF'));

    // Anchos de columna (incluye ITEM)
    $sheet->getColumnDimension('A')->setWidth(8);   // Item
    $sheet->getColumnDimension('B')->setWidth(30);  // Puesto de Trabajo
    $sheet->getColumnDimension('C')->setWidth(18);  // Nivel Jerárquico
    $sheet->getColumnDimension('D')->setWidth(10);  // Num Nivel
    $sheet->getColumnDimension('E')->setWidth(28);  // Superior Inmediato
    $sheet->getColumnDimension('F')->setWidth(28);  // Subordinado
    $sheet->getColumnDimension('G')->setWidth(45);  // Objetivo del Puesto

    // Alineaciones y wrap
    $bodyStart = $headerRow + 1;
    $sheet->getStyle("A{$bodyStart}:G{$lastRow}")->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
    foreach (['B','E','F','G'] as $col) {
        $sheet->getStyle($col . $bodyStart . ':' . $col . $lastRow)
              ->getAlignment()->setWrapText(true);
    }
    // Congelar encabezado
    $sheet->freezePane('A' . ($headerRow + 1));

    $fileName = 'matrizpuestos_' . date('Ymd_His') . '.xlsx';
$writer   = new Xlsx($spreadsheet);

// Evitar bytes “fantasma” que corrompen el archivo
if (function_exists('ini_set')) {
    @ini_set('zlib.output_compression', 'Off');
}
while (ob_get_level() > 0) { @ob_end_clean(); } // limpia todos los buffers

return response()->stream(function () use ($writer) {
    // Por si acaso algún middleware escribió algo
    if (ob_get_length()) { @ob_end_clean(); }
    $writer->save('php://output');
}, 200, [
    'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'Content-Disposition'       => "attachment; filename=\"{$fileName}\"",
    'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
    'Pragma'                    => 'public',
    'Content-Transfer-Encoding' => 'binary',
]);
}

    /**
     * Importa registros de la matriz de puestos desde un archivo Excel.
     */
    public function importMatrizpuestos(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();

        $rows = $worksheet->toArray();

        // Preparar mapas para evitar muchas consultas por fila
        $normalize = function ($s) {
            $s = (string)($s ?? '');
            $s = trim($s);
            if ($s === '') return '';
            $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
            return strtoupper(preg_replace('/\s+/', ' ', $s));
        };
        $niveles = DB::table('nivel_jerarquico')->get(['id_nivel_jerarquico','nivel_jerarquico']);
        $mapNivel = [];
        foreach ($niveles as $n) { $mapNivel[$normalize($n->nivel_jerarquico)] = (int)$n->id_nivel_jerarquico; }
        $puestos = DB::table('puesto_trabajo_matriz')->get(['id_puesto_trabajo_matriz','puesto_trabajo_matriz']);
        $mapPuesto = [];
        foreach ($puestos as $p) { $mapPuesto[$normalize($p->puesto_trabajo_matriz)] = (int)$p->id_puesto_trabajo_matriz; }
        DB::beginTransaction();

        // Asumimos que la primera fila contiene los encabezados
        foreach (array_slice($rows, 6) as $row) {
            // Ajusta las posiciones según tu formato de Excel
            $puestoActual    = trim($row[0] ?? '');
            $nivel           = trim($row[1] ?? '');
            $superior        = trim($row[3] ?? '');
            $subordinadosStr = trim($row[4] ?? '');

            if (!$puestoActual || !$nivel) {
                continue; // omitir filas vacías o incompletas
            }

            // Buscar las IDs correspondientes
            $puestoId  = $mapPuesto[$normalize($puestoActual)] ?? null;
            $nivelId   = $mapNivel[$normalize($nivel)] ?? null;
            $superiorId = $superior !== '' ? ($mapPuesto[$normalize($superior)] ?? null) : null;

            if (!$puestoId || !$nivelId) {
                continue; // no existe el puesto o nivel
            }

            // Dividir subordinados por coma (si los hay)
            $subIds = [];
            if ($subordinadosStr) {
                $nombresSub = array_map('trim', explode(',', $subordinadosStr));
                foreach ($nombresSub as $nombreSub) {
                    $subRow = DB::table('puesto_trabajo_matriz')
                        ->where('puesto_trabajo_matriz', $nombreSub)
                        ->first();
                    if ($subRow) {
                        $subIds[] = $subRow->id_puesto_trabajo_matriz;
                    }
                }
            }

            // Estado actual en la BD para ese puesto
            $actualRows = DB::table('organigrama')
                ->where('id_puesto_trabajo_matriz', $puestoId)
                ->get(['id_nivel_jerarquico','id_puesto_superior','id_puesto_subordinado']);

            $nivelActual = $actualRows->first()->id_nivel_jerarquico ?? null;
            $superiorActual = $actualRows->first()->id_puesto_superior ?? null;
            $subsActual = [];
            foreach ($actualRows as $r) {
                if (!is_null($r->id_puesto_subordinado)) $subsActual[(int)$r->id_puesto_subordinado] = true;
            }

            // Actualizar nivel/superior si cambiaron
            if ($nivelActual !== $nivelId || (int)$superiorActual !== (int)$superiorId) {
                DB::table('organigrama')
                    ->where('id_puesto_trabajo_matriz', $puestoId)
                    ->update([
                        'id_nivel_jerarquico' => $nivelId,
                        'id_puesto_superior'  => $superiorId,
                    ]);
            }

            // Agregar subordinados nuevos
            if ($subIds) {
                foreach ($subIds as $subId) {
                    if (!isset($subsActual[$subId])) {
                        DB::table('organigrama')->insert([
                            'id_puesto_trabajo_matriz' => $puestoId,
                            'id_nivel_jerarquico'     => $nivelId,
                            'id_puesto_superior'      => $superiorId,
                            'id_puesto_subordinado'   => $subId,
                        ]);
                    }
                }
                // Eliminar subordinados que ya no están
                foreach (array_keys($subsActual) as $sid) {
                    if (!in_array($sid, $subIds, true)) {
                        DB::table('organigrama')
                            ->where('id_puesto_trabajo_matriz', $puestoId)
                            ->where('id_puesto_subordinado', $sid)
                            ->delete();
                    }
                }
                // Si ahora hay subordinados, eliminar placeholders
                DB::table('organigrama')
                    ->where('id_puesto_trabajo_matriz', $puestoId)
                    ->whereNull('id_puesto_subordinado')
                    ->delete();
            } else {
                // Sin subordinados: asegurar un único placeholder actualizado
                $existsPH = DB::table('organigrama')
                    ->where('id_puesto_trabajo_matriz', $puestoId)
                    ->whereNull('id_puesto_subordinado')
                    ->exists();
                if (!$existsPH) {
                    DB::table('organigrama')->insert([
                        'id_puesto_trabajo_matriz' => $puestoId,
                        'id_nivel_jerarquico'     => $nivelId,
                        'id_puesto_superior'      => $superiorId,
                        'id_puesto_subordinado'   => null,
                    ]);
                } else {
                    DB::table('organigrama')
                        ->where('id_puesto_trabajo_matriz', $puestoId)
                        ->whereNull('id_puesto_subordinado')
                        ->update([
                            'id_nivel_jerarquico' => $nivelId,
                            'id_puesto_superior'  => $superiorId,
                        ]);
                }
                // Limpiar duplicados de placeholders si hubiera más de uno
                $phCount = DB::table('organigrama')
                    ->where('id_puesto_trabajo_matriz', $puestoId)
                    ->whereNull('id_puesto_subordinado')
                    ->count();
                if ($phCount > 1) {
                    DB::table('organigrama')
                        ->where('id_puesto_trabajo_matriz', $puestoId)
                        ->whereNull('id_puesto_subordinado')
                        ->delete();
                    DB::table('organigrama')->insert([
                        'id_puesto_trabajo_matriz' => $puestoId,
                        'id_nivel_jerarquico'     => $nivelId,
                        'id_puesto_superior'      => $superiorId,
                        'id_puesto_subordinado'   => null,
                    ]);
                }
            }
        }
        DB::commit();

        return back()->with('success', 'Datos importados correctamente (sin duplicados y sincronizados).');
    }   
}
