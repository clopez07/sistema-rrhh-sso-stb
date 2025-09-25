<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;    
// ====== 0) Imports necesarios arriba del archivo ======
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Conditional; 
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RiesgosController extends Controller
{
    public function matriz()
    {
        // 1) Matriz
        $result = DB::select('CALL generar_matriz_riesgos()');
        $matriz = collect($result)->map(fn ($r) => (array) $r);

        // 2) Map "TIPO|RIESGO" -> id_riesgo
        $riesgos = DB::table('riesgo as r')
            ->join('tipo_riesgo as t','t.id_tipo_riesgo','=','r.id_tipo_riesgo')
            ->select('r.id_riesgo','r.nombre_riesgo','t.tipo_riesgo')->get();
        $mapRiesgos = [];
        foreach ($riesgos as $ri) $mapRiesgos[$ri->tipo_riesgo.'|'.$ri->nombre_riesgo] = (int)$ri->id_riesgo;

        // 3) Catálogos (solo nombre para UI, id para map)
        $optsProb  = DB::table('probabilidad')->select('id_probabilidad as id','probabilidad as nombre')->orderBy('id')->get();
        $optsCons  = DB::table('consecuencia')->select('id_consecuencia as id','consecuencia as nombre')->orderBy('id')->get();
        $optsNivel = DB::table('nivel_riesgo')->select('id_nivel_riesgo as id','nivel_riesgo as nombre')->orderBy('id')->get();
        // Sugerencias de controles (distintos ya guardados)
        $optsCtrlIng = DB::table('medidas_control')
            ->selectRaw('DISTINCT TRIM(control_ingenieria) as nombre')
            ->whereNotNull('control_ingenieria')
            ->whereRaw("TRIM(control_ingenieria) <> ''")
            ->orderBy('nombre')
            ->get();
        $optsCtrlAdm = DB::table('medidas_control')
            ->selectRaw('DISTINCT TRIM(control_administrativo) as nombre')
            ->whereNotNull('control_administrativo')
            ->whereRaw("TRIM(control_administrativo) <> ''")
            ->orderBy('nombre')
            ->get();

        // 4) Valoración prob×cons → nivel (para cálculo inmediato en UI)
        $vr = DB::table('valoracion_riesgo')->select('id_probabilidad','id_consecuencia','id_nivel_riesgo')->get();
        $vrMatrix = [];
        foreach ($vr as $v) $vrMatrix[$v->id_probabilidad.'-'.$v->id_consecuencia] = (int)$v->id_nivel_riesgo;

        // 5) num_empleados por puesto
        $empleados = DB::table('puesto_trabajo_matriz')
            ->pluck('num_empleados','id_puesto_trabajo_matriz'); // [puesto_id => num]

        // 6) Resumen global por puesto (toma solo riesgos en “Sí”)
        $resumen = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('riesgo_valor as rv', 'rv.id_puesto_trabajo_matriz','=','ptm.id_puesto_trabajo_matriz')
            ->leftJoin('medidas_riesgo_puesto as mrp','mrp.id_riesgo','=','rv.id_riesgo')
            ->leftJoin('epp','epp.id_epp','=','mrp.id_epp')
            ->leftJoin('capacitacion as c','c.id_capacitacion','=','mrp.id_capacitacion')
            ->leftJoin('senalizacion as s','s.id_senalizacion','=','mrp.id_senalizacion')
            ->leftJoin('otras_medidas as o','o.id_otras_medidas','=','mrp.id_otras_medidas')
            ->whereIn(DB::raw('LOWER(rv.valor)'), ['si','sí','sÍ','si.','sí.'])
            ->groupBy('ptm.id_puesto_trabajo_matriz')
            ->selectRaw('ptm.id_puesto_trabajo_matriz,
                        GROUP_CONCAT(DISTINCT epp.equipo ORDER BY epp.equipo SEPARATOR ", ") AS epp,
                        GROUP_CONCAT(DISTINCT c.capacitacion ORDER BY c.capacitacion SEPARATOR ", ") AS caps,
                        GROUP_CONCAT(DISTINCT s.senalizacion ORDER BY s.senalizacion SEPARATOR ", ") AS senal,
                        GROUP_CONCAT(DISTINCT o.otras_medidas ORDER BY o.otras_medidas SEPARATOR ", ") AS otras')
            ->get()->keyBy('id_puesto_trabajo_matriz');

        // 7) Medidas por puesto (para precargar en la vista)
        $medidasByPuesto = DB::table('medidas_control')->get()->keyBy('id_puesto_trabajo_matriz');

        return view('riesgos.matrizriesgos', compact(
            'matriz','mapRiesgos',
            'optsProb','optsCons','optsNivel',
            'optsCtrlIng','optsCtrlAdm',
            'vrMatrix','empleados','resumen',
            'medidasByPuesto'
        ));
    }

    // Devuelve lo guardado para un puesto (general por puesto)
    public function getMedida(Request $r)
    {
        $puesto = (int) $r->query('puesto_id');

        $row = DB::table('medidas_control')
            ->where('id_puesto_trabajo_matriz', $puesto)
            ->first();

        return response()->json($row ?: []);
    }

    public function saveMedida(Request $r)
{
    $data = $r->validate([
        'id_puesto_trabajo_matriz'  => 'required|integer',
        'id_probabilidad'           => 'nullable|integer',
        'id_consecuencia'           => 'nullable|integer',
        'id_nivel_riesgo'           => 'nullable|integer', // recalculated later if not provided
        'eliminacion'               => 'nullable|string|max:500',
        'sustitucion'               => 'nullable|string|max:500',
        'aislar'                    => 'nullable|string|max:500',
        'control_ingenieria'        => 'nullable|string|max:1000',
        'control_administrativo'    => 'nullable|string|max:1000',
    ]);

    // Server side fallback so level is always derived
    if (empty($data['id_nivel_riesgo']) && !empty($data['id_probabilidad']) && !empty($data['id_consecuencia'])) {
        $nivel = DB::table('valoracion_riesgo')
            ->where('id_probabilidad', $data['id_probabilidad'])
            ->where('id_consecuencia', $data['id_consecuencia'])
            ->value('id_nivel_riesgo');
        if ($nivel) {
            $data['id_nivel_riesgo'] = (int) $nivel;
        }
    }

    $puestoId = (int) $data['id_puesto_trabajo_matriz'];
    $updates = $data;
    unset($updates['id_puesto_trabajo_matriz']);

    $payload = array_filter($updates, fn ($v) => $v !== null);

    DB::transaction(function () use ($puestoId, $payload) {
        $existingIds = DB::table('medidas_control')
            ->where('id_puesto_trabajo_matriz', $puestoId)
            ->orderBy('id_medidas_control')
            ->pluck('id_medidas_control');

        if ($existingIds->count() > 1) {
            DB::table('medidas_control')
                ->whereIn('id_medidas_control', $existingIds->slice(1)->all())
                ->delete();
        }

        DB::table('medidas_control')->updateOrInsert(
            ['id_puesto_trabajo_matriz' => $puestoId],
            $payload
        );
    });

    return response()->json(['ok' => true]);
}

    public function exportMatrizIdentificacionExcel()
    {
     // 1) Datos = los mismos de la vista
    $result = DB::select('CALL generar_matriz_riesgos()');
    $rows = collect($result)->map(fn($r) => (array) $r);

    if ($rows->isEmpty()) {
        // matriz vacía para evitar errores
        $rows = collect([['puesto_trabajo_matriz' => '']]);
    }

    // 2) Detectar columnas fijas y categorías/risgos (igual que el Blade)
    $firstRow = (array) $rows->first();
    $columnasFijas = [];
    $categorias = []; // $categorias[CAT] = [riesgo1, riesgo2, ...]

    foreach (array_keys($firstRow) as $col) {
        if (str_contains($col, '|')) {
            [$cat, $riesgo] = explode('|', $col, 2);
            $categorias[$cat][] = $riesgo;
        } else {
            $columnasFijas[] = $col;
        }
    }

    // primera fija = título pegado a la izquierda en la vista
    // Forzamos la primera columna igual que en la vista y omitimos el ID
    $firstCol = 'puesto_trabajo_matriz';
    $restoFijas = array_values(array_filter($columnasFijas, fn($c) => !in_array($c, ['id_puesto_trabajo_matriz', $firstCol], true)));
    $fixedCount = 1 + count($restoFijas); // primera + resto

    // 3) Colores de cabecera (como tu Blade: Tailwind aprox)
    $catColor = [
        'MECANICO'               => ['F97316', 'FED7AA'],
        'ELECTRICO'              => ['3B82F6', 'BFDBFE'],
        'FUEGO Y EXPLOSION'      => ['EAB308', 'FEF08A'],
        'QUIMICOS'               => ['6B7280', 'E5E7EB'],
        'BIOLOGICO'              => ['0EA5E9', 'BAE6FD'],
        'ERGONOMICO'             => ['475569', 'CBD5E1'],
        'FENOMENOS AMBIENTALES'  => ['16A34A', '86EFAC'],
        'FISICO'                 => ['FB7185', 'FECDD3'],
        'LOCATIVO'               => ['DC2626', 'FCA5A5'],
        'PSICOSOCIALES'          => ['171717', '737373'],
    ];
    $defaultCat = ['9CA3AF', 'E5E7EB']; // gris por defecto

    // 4) Libro/Hoja
    $ss = new Spreadsheet();
    $sh = $ss->getActiveSheet();
    $sh->setTitle('Matriz de Riesgos');

    $sh->getPageSetup()
       ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
       ->setPaperSize(PageSetup::PAPERSIZE_A4)
       ->setFitToWidth(1)->setFitToHeight(0);
    $sh->getPageMargins()->setTop(0.4)->setRight(0.3)->setLeft(0.3)->setBottom(0.4);

    // 5) Logo + títulos
    $logoPath = public_path('img/logo.PNG');
    if (file_exists($logoPath)) {
        $d = new Drawing();
        $d->setPath($logoPath);
        $d->setHeight(60);
        $d->setCoordinates('A1');
        $d->setOffsetX(2);
        $d->setWorksheet($sh);
    }

    $numDyn = array_sum(array_map('count', $categorias));
    // Columnas adicionales al final: 1 (personas) + 3 (evaluación) + 9 (controles/resúmenes) = 13
    $EXTRA_COLS = 13;
    $totalCols = $fixedCount + $numDyn + $EXTRA_COLS;
    $lastCol = Coordinate::stringFromColumnIndex($totalCols);

    // Títulos
    $sh->mergeCells("C1:{$lastCol}1");
    $sh->mergeCells("C2:{$lastCol}2");
    $sh->mergeCells("C3:{$lastCol}3");
    $sh->setCellValue('C1', 'MATRIZ DE IDENTIFICACIÓN DE RIESGOS POR PUESTO DE TRABAJO');
    $sh->setCellValue('C2', 'PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS');
    $sh->setCellValue('C3', 'MATRIZ DE IDENTIFICACION DE PELIGROS Y EVALAUCION DE RIESGOS / HAZARD IDENTIFICATION AND RISK ASSESSMENT MATRIX');

    $sh->getStyle("C1:C3")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $sh->getStyle("C1")->getFont()->setBold(true)->setSize(14);
    $sh->getStyle("C2:C3")->getFont()->setSize(10);

    // 6) Encabezado de 2 filas (categorías + riesgos)
    $start     = 6;
    $rowCat    = $start;      // fila 1
    $rowRiesgo = $start + 1;  // fila 2
    $rowData   = $start + 2;  // datos

    // 6.1 primera fija con rowspan=2
    $colIdx = 1;
    $colA = Coordinate::stringFromColumnIndex($colIdx++);
    $sh->mergeCells("{$colA}{$rowCat}:{$colA}{$rowRiesgo}");
    $sh->setCellValue("{$colA}{$rowCat}", strtoupper(str_replace('_', ' ', $firstCol)));

    // resto de fijas con rowspan=2
    foreach ($restoFijas as $fx) {
        $col = Coordinate::stringFromColumnIndex($colIdx++);
        $sh->mergeCells("{$col}{$rowCat}:{$col}{$rowRiesgo}");
        $sh->setCellValue("{$col}{$rowCat}", strtoupper(str_replace('_', ' ', $fx)));
    }

    // estilos fijas
    $leftLast = Coordinate::stringFromColumnIndex($fixedCount);
    $sh->getStyle("A{$rowCat}:{$leftLast}{$rowRiesgo}")
       ->applyFromArray([
           'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'E5E7EB']],
           'font'=>['bold'=>true],
           'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
       ]);

    // 6.2 categorías + riesgos
    foreach ($categorias as $cat => $riesgos) {
        $span = count($riesgos);
        $colStart = Coordinate::stringFromColumnIndex($colIdx);
        $colEnd   = Coordinate::stringFromColumnIndex($colIdx + $span - 1);

        // categoría (merge)
        $sh->mergeCells("{$colStart}{$rowCat}:{$colEnd}{$rowCat}");
        $sh->setCellValue("{$colStart}{$rowCat}", $cat);

        // riesgos
        foreach ($riesgos as $r) {
            $col = Coordinate::stringFromColumnIndex($colIdx++);
            $sh->setCellValue("{$col}{$rowRiesgo}", $r);
        }

        // colores de cabecera para este bloque
        [$c1, $c2] = $catColor[$cat] ?? $defaultCat;

        $sh->getStyle("{$colStart}{$rowCat}:{$colEnd}{$rowCat}")
           ->applyFromArray([
               'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$c1]],
               'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
               'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
           ]);
        $sh->getStyle("{$colStart}{$rowRiesgo}:{$colEnd}{$rowRiesgo}")
           ->applyFromArray([
               'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$c2]],
               'font'=>['bold'=>true],
               'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
           ]);
    }

    // ---- Encabezados extras (como en la vista) ----
    // Personas expuestas (rowspan=2)
    $col = Coordinate::stringFromColumnIndex($colIdx++);
    $sh->mergeCells("{$col}{$rowCat}:{$col}{$rowRiesgo}");
    $sh->setCellValue("{$col}{$rowCat}", 'NO. DE PERSONAS EXPUESTAS');

    // Grupo Evaluación (3 columnas)
    $colStartEval = Coordinate::stringFromColumnIndex($colIdx);
    $colEndEval   = Coordinate::stringFromColumnIndex($colIdx + 3 - 1);
    $sh->mergeCells("{$colStartEval}{$rowCat}:{$colEndEval}{$rowCat}");
    $sh->setCellValue("{$colStartEval}{$rowCat}", 'EVALUACIÓN DE RIESGOS');
    $subEval = ['PROBABILIDAD','CONSECUENCIA','NIVEL'];
    foreach ($subEval as $se) {
        $col = Coordinate::stringFromColumnIndex($colIdx++);
        $sh->setCellValue("{$col}{$rowRiesgo}", $se);
    }

    // Grupo Control (9 columnas)
    $colStartCtrl = Coordinate::stringFromColumnIndex($colIdx);
    $colEndCtrl   = Coordinate::stringFromColumnIndex($colIdx + 9 - 1);
    $sh->mergeCells("{$colStartCtrl}{$rowCat}:{$colEndCtrl}{$rowCat}");
    $sh->setCellValue("{$colStartCtrl}{$rowCat}", 'CONTROL DE RIESGOS');
    $subCtrl = [
        'ELIMINACIÓN','SUSTITUCIÓN','AISLAR',
        'CONTROL DE INGENIERÍA','CONTROL ADMINISTRATIVO',
        'EPP REQUERIDO','CAPACITACIONES REQUERIDAS','SEÑALIZACIÓN REQUERIDA','OTRAS MEDIDAS REQUERIDAS',
    ];
    foreach ($subCtrl as $sc) {
        $col = Coordinate::stringFromColumnIndex($colIdx++);
        $sh->setCellValue("{$col}{$rowRiesgo}", $sc);
    }

    // Estilos ligeros para los extras
    $extraStart = Coordinate::stringFromColumnIndex($fixedCount + $numDyn + 1);
    $sh->getStyle("{$extraStart}{$rowCat}:{$lastCol}{$rowRiesgo}")
       ->applyFromArray([
          'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F3F4F6']],
          'font'=>['bold'=>true],
          'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
          'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'D1D5DB']]],
       ]);

    // 7) Datos
    $r = $rowData;

    // Mapas/consultas para llenar columnas extra
    $empleadosExp = DB::table('puesto_trabajo_matriz')->pluck('num_empleados','id_puesto_trabajo_matriz');
    $medidasMap = DB::table('puesto_trabajo_matriz as ptm')
        ->leftJoin('medidas_control as mc','mc.id_puesto_trabajo_matriz','=','ptm.id_puesto_trabajo_matriz')
        ->leftJoin('probabilidad as pr','pr.id_probabilidad','=','mc.id_probabilidad')
        ->leftJoin('consecuencia as co','co.id_consecuencia','=','mc.id_consecuencia')
        ->leftJoin('nivel_riesgo as nr','nr.id_nivel_riesgo','=','mc.id_nivel_riesgo')
        ->select([
            'ptm.id_puesto_trabajo_matriz as id',
            'pr.probabilidad as probabilidad',
            'co.consecuencia as consecuencia',
            'nr.nivel_riesgo as nivel',
            'mc.eliminacion','mc.sustitucion','mc.aislar','mc.control_ingenieria','mc.control_administrativo',
        ])->get()->keyBy('id');
    $resumenRows = DB::table('puesto_trabajo_matriz as ptm')
        ->leftJoin('riesgo_valor as rv', 'rv.id_puesto_trabajo_matriz','=','ptm.id_puesto_trabajo_matriz')
        ->leftJoin('medidas_riesgo_puesto as mrp','mrp.id_riesgo','=','rv.id_riesgo')
        ->leftJoin('epp','epp.id_epp','=','mrp.id_epp')
        ->leftJoin('capacitacion as c','c.id_capacitacion','=','mrp.id_capacitacion')
        ->leftJoin('senalizacion as s','s.id_senalizacion','=','mrp.id_senalizacion')
        ->leftJoin('otras_medidas as o','o.id_otras_medidas','=','mrp.id_otras_medidas')
        ->whereIn(DB::raw('LOWER(rv.valor)'), ['si','sí','sÍ','si.','sí.'])
        ->groupBy('ptm.id_puesto_trabajo_matriz')
        ->selectRaw('ptm.id_puesto_trabajo_matriz,
                    GROUP_CONCAT(DISTINCT epp.equipo ORDER BY epp.equipo SEPARATOR ", ") AS epp,
                    GROUP_CONCAT(DISTINCT c.capacitacion ORDER BY c.capacitacion SEPARATOR ", ") AS caps,
                    GROUP_CONCAT(DISTINCT s.senalizacion ORDER BY s.senalizacion SEPARATOR ", ") AS senal,
                    GROUP_CONCAT(DISTINCT o.otras_medidas ORDER BY o.otras_medidas SEPARATOR ", ") AS otras')
        ->get()->keyBy('id_puesto_trabajo_matriz');
    foreach ($rows as $row) {
        // primera fija
        $colIdx = 1;
        $col = Coordinate::stringFromColumnIndex($colIdx++);
        $sh->setCellValue("{$col}{$r}", $row[$firstCol] ?? '');

        // resto de fijas
        foreach ($restoFijas as $fx) {
            $col = Coordinate::stringFromColumnIndex($colIdx++);
            $sh->setCellValue("{$col}{$r}", $row[$fx] ?? '');
        }

        // dinámicas por categoría|riesgo
        foreach ($categorias as $cat => $riesgos) {
            foreach ($riesgos as $riesgo) {
                $k = $cat.'|'.$riesgo;
                $col = Coordinate::stringFromColumnIndex($colIdx++);
                $sh->setCellValue("{$col}{$r}", $row[$k] ?? '');
                $sh->getStyle("{$col}{$r}")->getAlignment()
                   ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                   ->setVertical(Alignment::VERTICAL_CENTER);
            }
        }

        // ---- Columnas extra (Personas + Evaluación + Controles) ----
        $puestoId = (int)($row['id_puesto_trabajo_matriz'] ?? 0);
        // Personas
        $col = Coordinate::stringFromColumnIndex($fixedCount + $numDyn + 1);
        $sh->setCellValue("{$col}{$r}", $empleadosExp[$puestoId] ?? '');
        $idx = $fixedCount + $numDyn + 2;
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", ($medidasMap[$puestoId]->probabilidad ?? ''));
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", ($medidasMap[$puestoId]->consecuencia ?? ''));
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", ($medidasMap[$puestoId]->nivel ?? ''));
        // Controles + Resúmenes
        $m = $medidasMap[$puestoId] ?? null;
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", $m->eliminacion ?? '');
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", $m->sustitucion ?? '');
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", $m->aislar ?? '');
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", $m->control_ingenieria ?? '');
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", $m->control_administrativo ?? '');
        $res = $resumenRows[$puestoId] ?? null;
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", trim($res->epp ?? ''));
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", trim($res->caps ?? ''));
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", trim($res->senal ?? ''));
        $col = Coordinate::stringFromColumnIndex($idx++); $sh->setCellValue("{$col}{$r}", trim($res->otras ?? ''));

        // bordes + alto de fila
        $sh->getStyle("A{$r}:{$lastCol}{$r}")
           ->applyFromArray([
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
               'alignment'=>['vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           ]);
        $sh->getRowDimension($r)->setRowHeight(24);
        $r++;
    }

    // 8) Anchos y congelar encabezado + primera columna
    $sh->getColumnDimension('A')->setWidth(48);  // puesto
    // resto fijas (si hay)
    $ci = 2;
    foreach ($restoFijas as $_) {
        $sh->getColumnDimension(Coordinate::stringFromColumnIndex($ci++))->setWidth(18);
    }
    for (; $ci <= $totalCols; $ci++) {
        $sh->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setWidth(16);
    }

    // Borde a toda la tabla (encabezados + datos)
    $tableTopLeft  = 'A'.$rowCat;
    $tableBottomRight = $lastCol.($r-1);
    $sh->getStyle("{$tableTopLeft}:{$tableBottomRight}")
       ->applyFromArray([
           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
       ]);

    // Freeze: 1 columna fija + 2 filas de encabezado
    $freezeCol = Coordinate::stringFromColumnIndex(2); // después de la 1ª fija
    $sh->freezePane($freezeCol.$rowData);

    // 8.b) Pie de página "escrito" en el Excel (celdas)
$footerRow = $r + 2; // deja una fila en blanco; ajusta si lo quieres más cerca
$third     = max(1, intdiv($totalCols, 3));

$leftStart   = 'A';
$leftEnd     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($third);
$centerStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($third + 1);
$centerEnd   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max($third * 2, $third + 1));
$rightStart  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(min($third * 2 + 1, $totalCols));
$rightEnd    = $lastCol;

// Merges (2 líneas a la izquierda; 1 línea centrada y 1 a la derecha)
$sh->mergeCells("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}");
$sh->mergeCells("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1));
$sh->mergeCells("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1));
$sh->mergeCells("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1));

// Textos
$sh->setCellValue("{$leftStart}{$footerRow}",       '1 Copia Archivo');
$sh->setCellValue("{$leftStart}".($footerRow+1),    '1 Copia Sistema');
$sh->setCellValue("{$centerStart}".($footerRow+1),  '2 VERSION 2025');
$sh->setCellValue("{$rightStart}".($footerRow+1),   'STB/SSO/R054');

// Estilos base
$sh->getStyle("A{$footerRow}:{$lastCol}".($footerRow+1))
   ->getFont()->setName('Arial')->setSize(9);
$sh->getRowDimension($footerRow)->setRowHeight(16);
$sh->getRowDimension($footerRow+1)->setRowHeight(16);

// Alineaciones
$sh->getStyle("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}")
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
$sh->getStyle("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1))
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
$sh->getStyle("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1))
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sh->getStyle("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1))
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

// (Opcional) una línea suave arriba del pie para separarlo visualmente
$sh->getStyle("A".($footerRow-1).":{$lastCol}".($footerRow-1))
   ->applyFromArray([
     'borders'=>['top'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                         'color'=>['rgb'=>'DDDDDD']]]
   ]);

// (Opcional) que el pie esté siempre visible al final cuando hay filtros
// -> nada extra; queda fijo como filas normales al final de la tabla.

    // 9) Descargar
    $file = 'Matriz_Identificacion_'.date('Ymd_His').'.xlsx';
    $writer = new Xlsx($ss);
    if (ob_get_length()) { ob_end_clean(); }
    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $file, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

// ------------------------------------------------------------------------------------------------------

    public function tipoexposicion(Request $request)
    {
        $tipoexposicion = DB::table('tipo_exposicion as te')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('te.tipo_exposicion', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);

        return view('riesgos.tipoexposicion', compact('tipoexposicion'));
    }

        public function storetipoexposicion(Request $request)
    {

        DB::table('tipo_exposicion')->insert([
            'tipo_exposicion' => $request->input('tipo_exposicion'),
        ]);

        return redirect()->back()->with('success', 'tipo_exposicion registrado correctamente.');
    }

    public function updatetipoexposicion(Request $request, $id)
    {

        DB::table('tipo_exposicion')
            ->where('id_tipo_exposicion', $id)
            ->update([
                'tipo_exposicion' => $request->input('tipo_exposicion'),
            ]);

        return redirect()->back()->with('success', 'tipo_exposicion actualizado correctamente');
    }

    public function destroytipoexposicion($id)
    {
        DB::table('tipo_exposicion')->where('id_tipo_exposicion', $id)->delete();
        return redirect()->back()->with('success', 'tipo_exposicion eliminado correctamente');
    }

// ------------------------------------------------------------------------------------------------------

    public function quimicos(Request $request)
    {
        DB::statement('SET SESSION group_concat_max_len = 1000000');

        $search = trim((string) $request->search);

        $quimicos = DB::table('quimico as q')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('q.nombre_comercial', 'like', "%{$search}%")
                      ->orWhere('q.uso', 'like', "%{$search}%")
                      ->orWhere('q.proveedor', 'like', "%{$search}%")
                      ->orWhereExists(function ($ex) use ($search) {
                          $ex->from('quimico_tipo_exposicion as qte')
                             ->join('tipo_exposicion as te','te.id_tipo_exposicion','=','qte.id_tipo_exposicion')
                             ->whereColumn('qte.id_quimico','q.id_quimico')
                             ->where('te.tipo_exposicion','like', "%{$search}%");
                      });
                });
            })
            ->select('q.*')
            ->selectSub(function ($sq) {
                $sq->from('nivel_riesgo as nr')
                ->select('nr.nivel_riesgo')
                ->whereColumn('nr.id_nivel_riesgo', 'q.id_nivel_riesgo')
                ->limit(1);
            }, 'nivel_riesgo')
            ->selectSub(function ($sq) {
                $sq->from('quimico_tipo_exposicion as qte2')
                ->join('tipo_exposicion as te2','te2.id_tipo_exposicion','=','qte2.id_tipo_exposicion')
                ->selectRaw('GROUP_CONCAT(DISTINCT te2.tipo_exposicion ORDER BY te2.tipo_exposicion SEPARATOR ", ")')
                ->whereColumn('qte2.id_quimico','q.id_quimico');
            }, 'tipos_exposicion')
            ->orderBy('q.nombre_comercial')
            ->paginate(10)
            ->appends(['search' => $request->search]);

        // Selects para formularios
        $tipoexposicion = DB::table('tipo_exposicion')
            ->select('id_tipo_exposicion','tipo_exposicion')->orderBy('tipo_exposicion')->get();
        $probabilidades = DB::table('probabilidad')
            ->select('id_probabilidad','probabilidad')->orderBy('id_probabilidad')->get();
        $consecuencias  = DB::table('consecuencia')
            ->select('id_consecuencia','consecuencia')->orderBy('id_consecuencia')->get();

        // ids de tipo_exposicion por químico (para multiselect pre-seleccionado)
        $teIdsByQuimico = DB::table('quimico_tipo_exposicion')
            ->selectRaw('id_quimico, GROUP_CONCAT(id_tipo_exposicion) AS ids')
            ->groupBy('id_quimico')
            ->pluck('ids', 'id_quimico');

        // ==== PAREJA CANÓNICA (prob, cons) POR NIVEL (sin guardar en BD) ====
        // 1) niveles presentes en esta página
        $niveles = $quimicos->pluck('id_nivel_riesgo')->filter()->unique()->values();

        // 2) para cada nivel, elegimos una combinación estable (menor prob y luego menor cons)
        $pairsByLevel = DB::table('valoracion_riesgo')
            ->whereIn('id_nivel_riesgo', $niveles)
            ->orderBy('id_nivel_riesgo')
            ->orderBy('id_probabilidad')
            ->orderBy('id_consecuencia')
            ->get()
            ->groupBy('id_nivel_riesgo')
            ->map(function($rows){
                $r = $rows->first();
                return ['prob' => $r->id_probabilidad, 'cons' => $r->id_consecuencia];
            });

        // 3) mapa químico -> pareja (prob, cons) según su nivel (o null si no tiene nivel)
        $pairByQuimico = [];
        foreach ($quimicos as $q) {
            $pairByQuimico[$q->id_quimico] = $pairsByLevel[$q->id_nivel_riesgo] ?? ['prob'=>null,'cons'=>null];
        }

        // Mapa Prob/Cons -> etiqueta de nivel (para mostrar en el input readonly)
        $valRows = DB::table('valoracion_riesgo as v')
            ->join('nivel_riesgo as n','n.id_nivel_riesgo','=','v.id_nivel_riesgo')
            ->get(['v.id_probabilidad','v.id_consecuencia','v.id_nivel_riesgo','n.nivel_riesgo']);
        $valMap = [];
        foreach($valRows as $vr){
            $valMap[$vr->id_probabilidad.'-'.$vr->id_consecuencia] = ['id'=>$vr->id_nivel_riesgo, 'label'=>$vr->nivel_riesgo];
        }

        return view('riesgos.quimicos', compact(
            'quimicos','tipoexposicion','probabilidades','consecuencias',
            'teIdsByQuimico','pairByQuimico','valMap'
        ));
    }

    public function storequimicos(Request $request)
    {
        // Derivar nivel RESULTANTE desde la combinación elegida
        $idProb  = $request->input('id_probabilidad');
        $idCons  = $request->input('id_consecuencia');
        $idNivel = null;
        if ($idProb && $idCons) {
            $idNivel = DB::table('valoracion_riesgo')
                ->where('id_probabilidad', $idProb)
                ->where('id_consecuencia', $idCons)
                ->value('id_nivel_riesgo');
        }

        // Normalizar checkboxes a 0/1 (los inputs envían "Si" cuando están marcados)
        $checkKeys = [
            'ninguno','particulas_polvo','sustancias_corrosivas','sustancias_toxicas','sustancias_irritantes',
            'nocivo','corrosivo','inflamable','peligro_salud','oxidante','peligro_medio_ambiente','toxico','gas_presion','explosivo',
        ];
        $flags = [];
        foreach ($checkKeys as $k) { $flags[$k] = $request->has($k) ? 1 : 0; }

        $idQuimico = DB::table('quimico')->insertGetId([
            'nombre_comercial'       => $request->input('nombre_comercial'),
            'uso'                    => $request->input('uso'),
            'proveedor'              => $request->input('proveedor'),
            'concentracion'          => $request->input('concentracion'),
            'composicion_quimica'    => $request->input('composicion_quimica'),
            'estado_fisico'          => $request->input('estado_fisico'),
            'msds'                   => $request->input('msds'),
            'salud'                  => $request->input('salud'),
            'inflamabilidad'         => $request->input('inflamabilidad'),
            'reactividad'            => $request->input('reactividad'),
            'ninguno'                => $flags['ninguno'],
            'particulas_polvo'       => $flags['particulas_polvo'],
            'sustancias_corrosivas'  => $flags['sustancias_corrosivas'],
            'sustancias_toxicas'     => $flags['sustancias_toxicas'],
            'sustancias_irritantes'  => $flags['sustancias_irritantes'],
            'nocivo'                 => $flags['nocivo'],
            'corrosivo'              => $flags['corrosivo'],
            'inflamable'             => $flags['inflamable'],
            'peligro_salud'          => $flags['peligro_salud'],
            'oxidante'               => $flags['oxidante'],
            'peligro_medio_ambiente' => $flags['peligro_medio_ambiente'],
            'toxico'                 => $flags['toxico'],
            'gas_presion'            => $flags['gas_presion'],
            'explosivo'              => $flags['explosivo'],
            'descripcion'            => $request->input('descripcion'),
            'id_nivel_riesgo'        => $idNivel,              // << SOLO guardamos el nivel
            'medidas_pre_correc'     => $request->input('medidas_pre_correc'),
        ]);

        // pivot tipo_exposicion
        $tipos = (array) $request->input('tipo_exposicion', []);
        if (!empty($tipos)) {
            $rows = [];
            foreach ($tipos as $idTipo) {
                $rows[] = ['id_quimico' => $idQuimico, 'id_tipo_exposicion' => (int) $idTipo];
            }
            DB::table('quimico_tipo_exposicion')->insert($rows);
        }

        return redirect()->back()->with('success', 'Registrado correctamente.');
    }

    public function updatequimicos(Request $request, $id)
    {
        // Recalcular nivel RESULTANTE desde la combinación elegida
        $idProb  = $request->input('id_probabilidad');
        $idCons  = $request->input('id_consecuencia');
        $idNivel = null;
        if ($idProb && $idCons) {
            $idNivel = DB::table('valoracion_riesgo')
                ->where('id_probabilidad', $idProb)
                ->where('id_consecuencia', $idCons)
                ->value('id_nivel_riesgo');
        }

        // Normalizar checkboxes a 0/1 en update
        $checkKeys = [
            'ninguno','particulas_polvo','sustancias_corrosivas','sustancias_toxicas','sustancias_irritantes',
            'nocivo','corrosivo','inflamable','peligro_salud','oxidante','peligro_medio_ambiente','toxico','gas_presion','explosivo',
        ];
        $flags = [];
        foreach ($checkKeys as $k) { $flags[$k] = $request->has($k) ? 1 : 0; }

        DB::table('quimico')->where('id_quimico', $id)->update([
            'nombre_comercial'       => $request->input('nombre_comercial'),
            'uso'                    => $request->input('uso'),
            'proveedor'              => $request->input('proveedor'),
            'concentracion'          => $request->input('concentracion'),
            'composicion_quimica'    => $request->input('composicion_quimica'),
            'estado_fisico'          => $request->input('estado_fisico'),
            'msds'                   => $request->input('msds'),
            'salud'                  => $request->input('salud'),
            'inflamabilidad'         => $request->input('inflamabilidad'),
            'reactividad'            => $request->input('reactividad'),
            'ninguno'                => $flags['ninguno'],
            'particulas_polvo'       => $flags['particulas_polvo'],
            'sustancias_corrosivas'  => $flags['sustancias_corrosivas'],
            'sustancias_toxicas'     => $flags['sustancias_toxicas'],
            'sustancias_irritantes'  => $flags['sustancias_irritantes'],
            'nocivo'                 => $flags['nocivo'],
            'corrosivo'              => $flags['corrosivo'],
            'inflamable'             => $flags['inflamable'],
            'peligro_salud'          => $flags['peligro_salud'],
            'oxidante'               => $flags['oxidante'],
            'peligro_medio_ambiente' => $flags['peligro_medio_ambiente'],
            'toxico'                 => $flags['toxico'],
            'gas_presion'            => $flags['gas_presion'],
            'explosivo'              => $flags['explosivo'],
            'descripcion'            => $request->input('descripcion'),
            'id_nivel_riesgo'        => $idNivel,              // << SOLO guardamos el nivel
            'medidas_pre_correc'     => $request->input('medidas_pre_correc'),
        ]);

        // actualizar pivot
        DB::table('quimico_tipo_exposicion')->where('id_quimico', $id)->delete();
        $tipos = (array) $request->input('tipo_exposicion', []);
        if (!empty($tipos)) {
            $rows = [];
            foreach ($tipos as $idTipo) {
                $rows[] = ['id_quimico' => $id, 'id_tipo_exposicion' => (int) $idTipo];
            }
            DB::table('quimico_tipo_exposicion')->insert($rows);
        }

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroyquimicos($id)
    {
        DB::table('quimico')->where('id_quimico', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

//---------------------------------------------------------------------------------------
    public function quimicotipoexposicion(Request $request)
    {
        $quimicotipoexposicion = DB::table('quimico_tipo_exposicion as qte')
        ->join('quimico as q', 'qte.id_quimico', '=', 'q.id_quimico')
        ->join('tipo_exposicion as tp', 'qte.id_tipo_exposicion', '=', 'tp.id_tipo_exposicion')
        ->select(
            'q.id_quimico',
            'q.nombre_comercial',
            'q.uso',
            'q.estado_fisico',
            'q.salud',
            'q.inflamabilidad',
            'q.reactividad',
            'q.ninguno',
            'q.particulas_polvo',
            'q.sustancias_corrosivas',
            'q.sustancias_toxicas',
            'q.sustancias_irritantes',
            DB::raw("GROUP_CONCAT(tp.tipo_exposicion ORDER BY tp.tipo_exposicion SEPARATOR ', ') as tipos_exposicion")
        )
        ->when($request->search, function ($query, $search) {
            return $query->where('tp.tipo_exposicion', 'like', "%{$search}%")
                        ->orWhere('q.nombre_comercial', 'like', "%{$search}%");
        })
        ->groupBy(
            'q.id_quimico',
            'q.nombre_comercial',
            'q.uso',
            'q.estado_fisico',
            'q.salud',
            'q.inflamabilidad',
            'q.reactividad',
            'q.ninguno',
            'q.particulas_polvo',
            'q.sustancias_corrosivas',
            'q.sustancias_toxicas',
            'q.sustancias_irritantes'
        )
        ->paginate(10)
        ->appends(['search' => $request->search]);
        $quimicos = DB::table('quimico as qui')
        ->select('*')->get();
        $tipoexposicion = DB::table('tipo_exposicion as te')
        ->select('*')->get();
        return view('riesgos.quimicotipoexposicion', compact('quimicotipoexposicion', 'quimicos', 'tipoexposicion'));
    }

    public function storequimicotipoexposicion(Request $request)
    {
        foreach ($request->capacitaciones as $idTipoExposicion) {
            DB::table('quimico_tipo_exposicion')->insert([
                'id_quimico' => $request->id_quimico,
                'id_tipo_exposicion' => $idTipoExposicion,
            ]);
        }

        return redirect()->back()->with('success', 'Registrado correctamente.');
    }

    public function destroyquimicotipoexposicion($id)
    {
        DB::table('quimico_tipo_exposicion')
            ->where('id_quimico', $id)
            ->delete();

        return redirect()->back()->with('success', 'Se eliminaron los químicos del puesto correctamente.');
    }

//---------------------------------------------------------------------------------------
    public function quimicospuestos(Request $request)
    {
        $quimicospuestos = DB::table('quimico_puesto as qpu')
            ->join('quimico as q', 'qpu.id_quimico', '=', 'q.id_quimico')
            ->join('puesto_trabajo_matriz as pt', 'qpu.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
            ->select(
                'pt.id_puesto_trabajo_matriz',
                'pt.puesto_trabajo_matriz',
                DB::raw("GROUP_CONCAT(q.nombre_comercial ORDER BY q.nombre_comercial SEPARATOR ', ') as quimicos")
            )
            ->when($request->search, function ($query, $search) {
                return $query->where('pt.puesto_trabajo_matriz', 'like', "%{$search}%")
                            ->orWhere('q.nombre_comercial', 'like', "%{$search}%");
            })
            ->groupBy('pt.id_puesto_trabajo_matriz', 'pt.puesto_trabajo_matriz')
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $quimicospuestos->getCollection()->transform(function ($item) {
            $item->ids_quimicos = DB::table('quimico_puesto')
                ->where('id_puesto_trabajo_matriz', $item->id_puesto_trabajo_matriz)
                ->pluck('id_quimico')
                ->toArray();
            return $item;
        });

        $quimicos = DB::table('quimico as qui')->select('*')->get();
        $puestomatriz = DB::table('puesto_trabajo_matriz as pt')->select('*')->get();

        return view('riesgos.quimicospuestos', compact('quimicospuestos', 'quimicos', 'puestomatriz'));
    }

    public function exportQuimicosPuestosExcel(Request $request)
    {
        $result = DB::table('quimico_puesto as qpu')
            ->join('quimico as q', 'qpu.id_quimico', '=', 'q.id_quimico')
            ->join('puesto_trabajo_matriz as pt', 'qpu.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
            ->select(
                'pt.puesto_trabajo_matriz',
                DB::raw("GROUP_CONCAT(q.nombre_comercial ORDER BY q.nombre_comercial SEPARATOR ', ') as quimicos")
            )
            ->when($request->search, function ($query, $search) {
                return $query->where('pt.puesto_trabajo_matriz', 'like', "%{$search}%");
            })
            ->groupBy('pt.puesto_trabajo_matriz')
            ->orderBy('pt.puesto_trabajo_matriz')
            ->get();

        $rows = collect($result)->map(fn($r) => [
            'PUESTO'   => $r->puesto_trabajo_matriz,
            'QUIMICOS' => $r->quimicos,
        ]);

        // Crear Excel
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Químicos por puesto');

        // Encabezados
        $headers = ['PUESTO','QUIMICOS'];
        $col = 'A';
        foreach ($headers as $h) { $sh->setCellValue($col.'1', $h); $col++; }
        $sh->getStyle('A1:B1')->getFont()->setBold(true);

        // Filas
        $r = 2;
        foreach ($rows as $row) {
            $sh->setCellValue('A'.$r, $row['PUESTO']);
            $sh->setCellValue('B'.$r, $row['QUIMICOS']);
            $r++;
        }

        // Estilos
        $sh->getColumnDimension('A')->setWidth(45);
        $sh->getColumnDimension('B')->setWidth(80);
        $sh->getStyle('B2:B'.($r-1))->getAlignment()->setWrapText(true);

        $fileName = 'Quimicos_por_Puesto.xlsx';
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($ss, 'Xlsx');
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

   public function exportQuimicosExcel(Request $request)
{
    DB::statement('SET SESSION group_concat_max_len = 1000000');

    $search = trim((string) $request->search);

    $result = DB::table('quimico as q')
        ->leftJoin('quimico_tipo_exposicion as qte', 'q.id_quimico', '=', 'qte.id_quimico')
        ->leftJoin('tipo_exposicion as te', 'qte.id_tipo_exposicion', '=', 'te.id_tipo_exposicion')
        ->when($search !== '', function ($q2) use ($search) {
            $q2->where(function ($w) use ($search) {
                $w->where('q.nombre_comercial', 'like', "%{$search}%")
                  ->orWhere('q.uso', 'like', "%{$search}%")
                  ->orWhere('q.proveedor', 'like', "%{$search}%")
                  ->orWhere('te.tipo_exposicion', 'like', "%{$search}%");
            });
        })
        ->select('q.*')
        ->selectSub(function ($sq) {
            $sq->from('nivel_riesgo as nr')
               ->select('nr.nivel_riesgo')
               ->whereColumn('nr.id_nivel_riesgo', 'q.id_nivel_riesgo')
               ->limit(1);
        }, 'nivel_riesgo')
        ->selectSub(function ($sq) {
            $sq->from('quimico_tipo_exposicion as qte2')
               ->join('tipo_exposicion as te2', 'te2.id_tipo_exposicion', '=', 'qte2.id_tipo_exposicion')
               ->selectRaw("GROUP_CONCAT(DISTINCT te2.tipo_exposicion ORDER BY te2.tipo_exposicion SEPARATOR ', ')")
               ->whereColumn('qte2.id_quimico', 'q.id_quimico');
        }, 'tipos_exposicion')
        ->distinct()
        ->orderBy('q.nombre_comercial')
        ->get();

    $ss = new Spreadsheet();
    $sh = $ss->getActiveSheet();
    $sh->setTitle('Inventario de Quimicos');

    $sh->getPageSetup()
        ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
        ->setPaperSize(PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1)->setFitToHeight(0);
    $sh->getPageMargins()->setTop(0.4)->setRight(0.3)->setLeft(0.3)->setBottom(0.4);

    $logoPath = public_path('img/logo.PNG');
    if (file_exists($logoPath)) {
        $d = new Drawing();
        $d->setPath($logoPath);
        $d->setHeight(60);
        $d->setCoordinates('A1');
        $d->setOffsetX(2);
        $d->setWorksheet($sh);
    }

    $cols = [
        ['key' => 'nombre_comercial', 'title' => 'NOMBRE COMERCIAL'],
        ['key' => 'uso', 'title' => 'USO'],
        ['key' => 'proveedor', 'title' => 'PROVEEDOR'],
        ['key' => 'concentracion', 'title' => 'CONCENTRACION'],
        ['key' => 'composicion_quimica', 'title' => 'COMPOSICIÓN QUÍMICA'],
        ['key' => 'estado_fisico', 'title' => 'ESTADO FÍSICO'],
        ['key' => 'msds', 'title' => 'MSDS'],

        ['key' => 'salud', 'title' => 'SALUD', 'group' => 'GRADO DE PELIGROSIDAD', 'sub_bg' => '0070C0', 'sub_font_white' => true],
        ['key' => 'inflamabilidad', 'title' => 'INFLAMABILIDAD', 'group' => 'GRADO DE PELIGROSIDAD', 'sub_bg' => 'FF0000', 'sub_font_white' => true],
        ['key' => 'reactividad', 'title' => 'REACTIVIDAD', 'group' => 'GRADO DE PELIGROSIDAD', 'sub_bg' => 'FFFF00'],

        ['key' => 'nocivo', 'title' => 'NOCIVO O IRRITANTE', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/nocivo.png'],
        ['key' => 'corrosivo', 'title' => 'CORROSIVO', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/corrosivo.png'],
        ['key' => 'inflamable', 'title' => 'INFLAMABLE', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/inflamable.png'],
        ['key' => 'peligro_salud', 'title' => 'PELIGRO GRAVE A LA SALUD', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/peligrosalud.png'],
        ['key' => 'oxidante', 'title' => 'OXIDANTE', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/oxidante.png'],
        ['key' => 'peligro_medio_ambiente', 'title' => 'PELIGRO PARA EL MEDIO AMBIENTE', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/medioambiente.png'],
        ['key' => 'toxico', 'title' => 'TÓXICO', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/toxico.png'],
        ['key' => 'gas_presion', 'title' => 'GAS A PRESIÓN', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/gas.png'],
        ['key' => 'explosivo', 'title' => 'EXPLOSIVO', 'group' => 'RIESGOS ESPECÍFICOS', 'img' => 'img/explosivo.png'],

        ['key' => 'tipos_exposicion', 'title' => 'Tipo de Exposición'],

        ['key' => 'ninguno', 'title' => 'Ninguno', 'group' => 'RIESGO QUÍMICO', 'rq' => true],
        ['key' => 'particulas_polvo', 'title' => 'Partículas de polvo, humos, gases y vapores', 'group' => 'RIESGO QUÍMICO', 'rq' => true],
        ['key' => 'sustancias_corrosivas', 'title' => 'Sustancias corrosivas', 'group' => 'RIESGO QUÍMICO', 'rq' => true],
        ['key' => 'sustancias_toxicas', 'title' => 'Sustancias Tóxicas', 'group' => 'RIESGO QUÍMICO', 'rq' => true],
        ['key' => 'sustancias_irritantes', 'title' => 'Sustancias irritantes o alergizantes', 'group' => 'RIESGO QUÍMICO', 'rq' => true],

        ['key' => 'nivel_riesgo', 'title' => 'Nivel de Riesgo Químico'],
        ['key' => 'medidas_pre_correc', 'title' => 'Medidas de Prevención y Correción'],
    ];

    $totalCols = count($cols);
    $lastCol = Coordinate::stringFromColumnIndex($totalCols);

    // Títulos
    $sh->mergeCells("C1:{$lastCol}1");
    $sh->mergeCells("C2:{$lastCol}2");
    $sh->mergeCells("C3:{$lastCol}3");
    $sh->setCellValue('C1', 'SERVICE AND TRADING BUSINESS S.A. DE C.V.');
    $sh->setCellValue('C2', 'PROCESO SALUD Y SEGURIDAD OCUPACIONAL / HEALTH AND SAFETY PROCESS');
    $sh->setCellValue('C3', 'INVENTARIO DE QUIMICOS / INVENTORY OF CHEMICALS');
    $sh->getStyle('C1')->getFont()->setBold(true)->setSize(14);
    $sh->getStyle('C1:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

    // Encabezado (grupos, títulos e iconos)
    $start   = 6;
    $rowTop  = $start;        // fila de agrupación
    $rowHead = $start + 1;    // títulos
    $rowIcon = $start + 2;    // pictogramas
    $rowData = $start + 3;    // primera fila de datos

    $colIdx = 1;
    $currentGroup = null;
    $currentGroupStart = null;
    foreach ($cols as $c) {
        $col = Coordinate::stringFromColumnIndex($colIdx);
        $title = $c['title'];
        $hasGroup = isset($c['group']);

        if ($hasGroup) {
            if ($currentGroup === null) {
                $currentGroup = $c['group'];
                $currentGroupStart = $colIdx;
            } elseif ($currentGroup !== $c['group']) {
                $startCol = Coordinate::stringFromColumnIndex($currentGroupStart);
                $endCol   = Coordinate::stringFromColumnIndex($colIdx - 1);
                $sh->mergeCells("{$startCol}{$rowTop}:{$endCol}{$rowTop}");
                $sh->setCellValue("{$startCol}{$rowTop}", $currentGroup);
                $currentGroup = $c['group'];
                $currentGroupStart = $colIdx;
            }
        } else {
            if ($currentGroup !== null) {
                $startCol = Coordinate::stringFromColumnIndex($currentGroupStart);
                $endCol   = Coordinate::stringFromColumnIndex($colIdx - 1);
                $sh->mergeCells("{$startCol}{$rowTop}:{$endCol}{$rowTop}");
                $sh->setCellValue("{$startCol}{$rowTop}", $currentGroup);
                $currentGroup = null;
            }
            // Columnas sin grupo: el título ocupa (head+icon)
            $sh->mergeCells("{$col}{$rowHead}:{$col}{$rowIcon}");
        }

        // Título de columna
        $sh->setCellValue("{$col}{$rowHead}", $title);
        $colIdx++;
    }
    if ($currentGroup !== null) {
        $startCol = Coordinate::stringFromColumnIndex($currentGroupStart);
        $endCol   = Coordinate::stringFromColumnIndex($colIdx - 1);
        $sh->mergeCells("{$startCol}{$rowTop}:{$endCol}{$rowTop}");
        $sh->setCellValue("{$startCol}{$rowTop}", $currentGroup);
    }

    $headerBg = 'D6DCE4';
    $sh->getStyle("A{$rowTop}:{$lastCol}{$rowTop}")
        ->applyFromArray([
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$headerBg]],
            'font'=>['bold'=>true],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
        ]);
    $sh->getStyle("A{$rowHead}:{$lastCol}{$rowHead}")
        ->applyFromArray([
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FFFFFF']],
            'font'=>['bold'=>true],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
        ]);
    $sh->getStyle("A{$rowIcon}:{$lastCol}{$rowIcon}")
        ->applyFromArray([
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FFFFFF']],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
        ]);

    // Colores por sub-encabezado y merges extra
    $colIdx = 1;
    foreach ($cols as $c) {
        $col = Coordinate::stringFromColumnIndex($colIdx);

        if (isset($c['sub_bg'])) {
            $sh->getStyle("{$col}{$rowHead}")
               ->applyFromArray([
                   'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$c['sub_bg']]],
                   'font'=>[ 'bold'=>true, 'color'=>['rgb'=>(($c['sub_font_white']??false)?'FFFFFF':'000000')] ],
               ]);
        }
        if (isset($c['group']) && strpos($c['group'], 'RIESGOS ESPEC') === 0) {
            $sh->getStyle("{$col}{$rowHead}")
               ->applyFromArray([
                   'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'00B0F0']],
                   'font'=>['bold'=>true, 'color'=>['rgb'=>'000000']],
               ]);
        }
        if (($c['rq'] ?? false) === true) {
            $sh->getStyle("{$col}{$rowHead}")
               ->applyFromArray([
                   'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F4B084']],
               ]);
            // también colorear la fila de iconos para mantener bloque
            $sh->getStyle("{$col}{$rowIcon}")
               ->applyFromArray([
                   'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F4B084']],
               ]);
        }
        // Columnas con grupo pero sin pictograma -> merge head+icon
        if (!isset($c['img']) && isset($c['group'])) {
            $sh->mergeCells("{$col}{$rowHead}:{$col}{$rowIcon}");
        }

        $colIdx++;
    }

    // Alturas
    $sh->getRowDimension($rowHead)->setRowHeight(28);
    $sh->getRowDimension($rowIcon)->setRowHeight(44);

    // Insertar pictogramas
    $colIdx = 1;
    foreach ($cols as $c) {
        if (isset($c['img'])) {
            $path = public_path($c['img']);
            if (file_exists($path)) {
                $d = new Drawing();
                $d->setPath($path);
                $d->setHeight(36);
                $d->setCoordinates(Coordinate::stringFromColumnIndex($colIdx) . $rowIcon);
                $d->setOffsetY(4);
                $d->setWorksheet($sh);
            }
        }
        $colIdx++;
    }

    // Datos
    $r = $rowData;
    foreach ($result as $row) {
        $colIdx = 1;
        foreach ($cols as $c) {
            $key = $c['key'];
            $val = $row->$key ?? '';
            $cell = Coordinate::stringFromColumnIndex($colIdx) . $r;
            $sh->setCellValue($cell, $val);
            $colIdx++;
        }
        $sh->getStyle('A'.$r.':'.$lastCol.$r)
           ->applyFromArray([
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
               'alignment'=>['vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           ]);
        $sh->getRowDimension($r)->setRowHeight(22);
        $r++;
    }

    // Anchos
    $widths = [26, 22, 22, 16, 28, 16, 20];
    for ($i=1; $i<=$totalCols; $i++) {
        $w = $widths[$i-1] ?? 14;
        $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($w);
    }
    // Centrar desde la col 8 en adelante
    $centerCols = range(8, $totalCols);
    foreach ($centerCols as $ci) {
        $col = Coordinate::stringFromColumnIndex($ci);
        $sh->getStyle($col.$rowData.':'.$col.($r-1))
           ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Freeze
    $sh->freezePane('A'.($rowData));

    // ===== Pie "en texto" al final (igual al anterior) =====
    $footerRow = $r + 2; // deja una fila en blanco
    $third = max(1, intdiv($totalCols, 3));

    $leftStart   = 'A';
    $leftEnd     = Coordinate::stringFromColumnIndex($third);
    $centerStart = Coordinate::stringFromColumnIndex($third + 1);
    $centerEnd   = Coordinate::stringFromColumnIndex(max($third * 2, $third + 1));
    $rightStart  = Coordinate::stringFromColumnIndex(min($third * 2 + 1, $totalCols));
    $rightEnd    = $lastCol;

    // Merges de pie
    $sh->mergeCells("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}");
    $sh->mergeCells("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1));
    $sh->mergeCells("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1));
    $sh->mergeCells("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1));

    // Textos del pie
    $sh->setCellValue("{$leftStart}{$footerRow}",       '1 Copia Archivo');
    $sh->setCellValue("{$leftStart}".($footerRow+1),    '1 Copia Sistema');
    $sh->setCellValue("{$centerStart}".($footerRow+1),  '2 VERSION 2025');
    $sh->setCellValue("{$rightStart}".($footerRow+1),   'STB/SSO/R054');

    // Estilos del pie
    $sh->getStyle("A{$footerRow}:{$lastCol}".($footerRow+1))
       ->getFont()->setName('Arial')->setSize(9);
    $sh->getRowDimension($footerRow)->setRowHeight(16);
    $sh->getRowDimension($footerRow+1)->setRowHeight(16);

    $sh->getStyle("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}")
       ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sh->getStyle("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1))
       ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sh->getStyle("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1))
       ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sh->getStyle("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1))
       ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Línea suave encima del pie
    $sh->getStyle("A".($footerRow-1).":{$lastCol}".($footerRow-1))
       ->applyFromArray([
           'borders'=>['top'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                               'color'=>['rgb'=>'DDDDDD']]]
       ]);

    // Descargar
    $fileName = 'Inventario_Quimicos_'.date('Ymd_His').'.xlsx';
    $writer = new Xlsx($ss);
    if (ob_get_length()) { ob_end_clean(); }
    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}


    public function storequimicospuestos(Request $request)
    {
        foreach ($request->capacitaciones as $id_quimico) {
            DB::table('quimico_puesto')->insert([
                'id_quimico' => $id_quimico,
                'id_puesto_trabajo_matriz' => $request->id_puesto_trabajo_matriz,
            ]);
        }

        return redirect()->back()->with('success', 'Registrado correctamente.');
    }

    public function updatequimicospuestos(Request $request, $id)
    {
        $request->validate([
            'capacitaciones' => 'required|array',
        ]);

        DB::table('quimico_puesto')
            ->where('id_puesto_trabajo_matriz', $id)
            ->delete();

        foreach ($request->capacitaciones as $id_quimico) {
            DB::table('quimico_puesto')->insert([
                'id_quimico' => $id_quimico,
                'id_puesto_trabajo_matriz' => $id,
            ]);
        }

        return redirect()->back()->with('success', 'Actualizado correctamente.');
    }

    public function destroyquimicospuestos($id)
    {
        DB::table('quimico_puesto')
            ->where('id_puesto_trabajo_matriz', $id)
            ->delete();

        return redirect()->back()->with('success', 'Se eliminaron los químicos del puesto correctamente.');
    }

// ----------------------------------------------------------------------------------------------------
    public function senalizacion(Request $request)
    {
        $senalizacion = DB::table('senalizacion as s')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('s.senalizacion', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);

        return view('riesgos.senalizacion', compact('senalizacion'));
    }

    public function storesenalizacion(Request $request)
    {
        DB::table('senalizacion')->insert([
            'senalizacion' => $request->input('senalizacion'),
        ]);

        return redirect()->back()->with('success', 'senalizacion registrado correctamente.');
    }

    public function updatesenalizacion(Request $request, $id)
    {
        DB::table('senalizacion')
            ->where('id_senalizacion', $id)
            ->update([
                'senalizacion' => $request->input('senalizacion'),
            ]);

        return redirect()->back()->with('success', 'senalizacion actualizado correctamente');
    }

    public function destroysenalizacion($id)
    {
        DB::table('senalizacion')->where('id_senalizacion', $id)->delete();
        return redirect()->back()->with('success', 'senalizacion eliminado correctamente');
    }

// ----------------------------------------------------------------------------------------------------
    public function otras(Request $request)
    {
        $otras = DB::table('otras_medidas as s')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('s.otras_medidas', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);

        return view('riesgos.otras', compact('otras'));
    }

    public function storeotras(Request $request)
    {
        DB::table('otras_medidas')->insert([
            'otras_medidas' => $request->input('otras_medidas'),
        ]);

        return redirect()->back()->with('success', 'otras_medidas registrado correctamente.');
    }

    public function updateotras(Request $request, $id)
    {
        DB::table('otras_medidas')
            ->where('id_otras_medidas', $id)
            ->update([
                'otras_medidas' => $request->input('otras_medidas'),
            ]);

        return redirect()->back()->with('success', 'otras_medidas actualizado correctamente');
    }

    public function destroyotras($id)
    {
        DB::table('otras_medidas')->where('id_otras_medidas', $id)->delete();
        return redirect()->back()->with('success', 'otras_medidas eliminado correctamente');
    }

// -----------------------------------------------------------------------------------------------  
public function medidasriesgo(Request $request)
{
    // Evitar que se corte el GROUP_CONCAT si hay listas largas
    DB::statement('SET SESSION group_concat_max_len = 1000000');

    $medidasriesgo = DB::table('medidas_riesgo_puesto as mrp')
        ->join('riesgo as ri', 'mrp.id_riesgo', '=', 'ri.id_riesgo')
        ->leftJoin('area as a', 'mrp.id_area', '=', 'a.id_area')
        ->leftJoin('epp as e', 'mrp.id_epp', '=', 'e.id_epp')
        ->leftJoin('capacitacion as ca', 'mrp.id_capacitacion', '=', 'ca.id_capacitacion')
        ->leftJoin('senalizacion as se', 'mrp.id_senalizacion', '=', 'se.id_senalizacion')
        ->leftJoin('otras_medidas as om', 'mrp.id_otras_medidas', '=', 'om.id_otras_medidas')
        ->when($request->filled('search'), function ($q) use ($request) {
            $s = trim($request->search);
            $q->where('ri.nombre_riesgo', 'like', "%{$s}%");
        })
        ->groupBy('ri.id_riesgo', 'ri.nombre_riesgo', 'a.id_area', 'a.area')
        ->select(
            'ri.id_riesgo',
            'ri.nombre_riesgo',
            'a.id_area',
            'a.area',
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT e.equipo ORDER BY e.equipo SEPARATOR ', '), '') AS epps"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT ca.capacitacion ORDER BY ca.capacitacion SEPARATOR ', '), '') AS capacitaciones"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT se.senalizacion ORDER BY se.senalizacion SEPARATOR ', '), '') AS senalizaciones"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT om.otras_medidas ORDER BY om.otras_medidas SEPARATOR ', '), '') AS otras_medidas"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT e.id_epp SEPARATOR ','), '') AS epp_ids"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT ca.id_capacitacion SEPARATOR ','), '') AS cap_ids"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT se.id_senalizacion SEPARATOR ','), '') AS sen_ids"),
            DB::raw("COALESCE(GROUP_CONCAT(DISTINCT om.id_otras_medidas SEPARATOR ','), '') AS otras_ids")
        )
        ->orderBy('ri.nombre_riesgo')
        ->paginate(10)
        ->appends($request->only('search'));

        $riesgos = DB::table('riesgo as a')
        ->select('*')->get();
        $areas = DB::table('area as b')
        ->select('*')->get();
        $epps = DB::table('epp as c')
        ->select('*')->get();
        $capacitaciones = DB::table('capacitacion as d')
        ->select('*')->get();
        $senalizaciones = DB::table('senalizacion as e')
        ->select('*')->get();
        $otras = DB::table('otras_medidas as f')
        ->select('*')->get();
    return view('riesgos.medidasriesgo', compact('medidasriesgo', 'riesgos', 'areas', 'epps', 'capacitaciones', 'senalizaciones', 'otras'));
}

public function storemedidasriesgo(Request $request)
{
    $request->validate([
        'id_riesgo'       => 'required|integer',
        'id_area'         => 'nullable|integer',
        'epp'             => 'array',
        'capacitaciones'  => 'array',
        'senalizaciones'  => 'array',
        'otras'           => 'array',
    ]);

    $idRiesgo = $request->id_riesgo;
    $idArea   = $request->id_area;

    // Insertar combinaciones
    if ($request->epp) {
        foreach ($request->epp as $idEpp) {
            DB::table('medidas_riesgo_puesto')->insert([
                'id_riesgo'       => $idRiesgo,
                'id_area'         => $idArea,
                'id_epp'          => $idEpp,
            ]);
        }
    }

    if ($request->capacitaciones) {
        foreach ($request->capacitaciones as $idCap) {
            DB::table('medidas_riesgo_puesto')->insert([
                'id_riesgo'       => $idRiesgo,
                'id_area'         => $idArea,
                'id_capacitacion' => $idCap,
            ]);
        }
    }

    if ($request->senalizaciones) {
        foreach ($request->senalizaciones as $idSen) {
            DB::table('medidas_riesgo_puesto')->insert([
                'id_riesgo'       => $idRiesgo,
                'id_area'         => $idArea,
                'id_senalizacion' => $idSen,
            ]);
        }
    }

    if ($request->otras) {
        foreach ($request->otras as $idO) {
            DB::table('medidas_riesgo_puesto')->insert([
                'id_riesgo'        => $idRiesgo,
                'id_area'          => $idArea,
                'id_otras_medidas' => $idO,
            ]);
        }
    }

    return redirect()->back()->with('success', 'Medidas asignadas correctamente');
}

// ------------------------------------------------------------------------------------------------------------

public function updatemedidasriesgo(Request $request, $id_riesgo, $id_area)
{
    $request->validate([
        'epp'             => 'array',
        'capacitaciones'  => 'array',
        'senalizaciones'  => 'array',
        'otras'           => 'array',
    ]);

    // Normalizar id_area (0 => null)
    if ($id_area === '0' || $id_area === 0) { $id_area = null; }

    DB::transaction(function () use ($request, $id_riesgo, $id_area) {
        // Eliminar todas las combinaciones existentes para este riesgo (todas las áreas)
        $q = DB::table('medidas_riesgo_puesto')->where('id_riesgo', $id_riesgo);
        if (is_null($id_area)) {
            $q->whereNull('id_area');
        } else {
            $q->where('id_area', $id_area);
        }
        $q->delete();

        // Insertar nuevas combinaciones sin área específica (nula)
        if ($request->epp) {
            foreach ($request->epp as $idEpp) {
                DB::table('medidas_riesgo_puesto')->insert([
                    'id_riesgo'       => $id_riesgo,
                    'id_area'         => $id_area,
                    'id_epp'          => $idEpp,
                ]);
            }
        }
        if ($request->capacitaciones) {
            foreach ($request->capacitaciones as $idCap) {
                DB::table('medidas_riesgo_puesto')->insert([
                    'id_riesgo'       => $id_riesgo,
                    'id_area'         => $id_area,
                    'id_capacitacion' => $idCap,
                ]);
            }
        }
        if ($request->senalizaciones) {
            foreach ($request->senalizaciones as $idSen) {
                DB::table('medidas_riesgo_puesto')->insert([
                    'id_riesgo'       => $id_riesgo,
                    'id_area'         => $id_area,
                    'id_senalizacion' => $idSen,
                ]);
            }
        }
        if ($request->otras) {
            foreach ($request->otras as $idO) {
                DB::table('medidas_riesgo_puesto')->insert([
                    'id_riesgo'        => $id_riesgo,
                    'id_area'          => $id_area,
                    'id_otras_medidas' => $idO,
                ]);
            }
        }
    });

    return redirect()->back()->with('success', 'Medidas actualizadas correctamente');
}

public function destroymedidasriesgo($id_riesgo, $id_area)
{
    if ($id_area === '0' || $id_area === 0) { $id_area = null; }
    $q = DB::table('medidas_riesgo_puesto')->where('id_riesgo', $id_riesgo);
    if (is_null($id_area)) {
        $q->whereNull('id_area');
    } else {
        $q->where('id_area', $id_area);
    }
    $q->delete();
    return redirect()->back()->with('success', 'Medidas eliminadas correctamente');
}

// ------------------------------------------------------------------------------------------------------------

    public function estandarilu(Request $request)
    {
        $estandarilu = DB::table('estandar_iluminacion as ei')
            ->leftjoin('localizacion as l', 'ei.id_localizacion', '=', 'l.id_localizacion')
            ->select('ei.*', 'l.*')
            ->when($request->search, function ($query, $search) {
                            return $query->where('l.localizacion', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $localizacion = DB::table('localizacion as loca')->select('*')->get();

        return view('riesgos.estandariluminacion', compact('estandarilu', 'localizacion'));
    }

    public function storeestandarilu(Request $request)
    {
        DB::table('estandar_iluminacion')->insert([
            'id_localizacion' => $request->input('id_localizacion'),
            'em' => $request->input('em'),
            'ugr' => $request->input('ugr'),
            'ra' => $request->input('ra'),
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->back()->with('success', 'Registrado correctamente.');
    }

    public function updateestandarilu(Request $request, $id)
    {
        DB::table('estandar_iluminacion')
            ->where('id_estandar_iluminacion', $id)
            ->update([
                'id_localizacion' => $request->input('id_localizacion'),
                'em' => $request->input('em'),
                'ugr' => $request->input('ugr'),
                'ra' => $request->input('ra'),
                'observaciones' => $request->input('observaciones'),
            ]);

        return redirect()->back()->with('success', 'Actualizado correctamente.');
    }

    public function destroyestandarilu($id)
    {
        DB::table('estandar_iluminacion')
            ->where('id_estandar_iluminacion', $id)
            ->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente.');
    }

// ------------------------------------------------------------------------------------------------------------

    public function estandarruido(Request $request)
    {
        $estandarruido = DB::table('estandar_ruido as ei')
            ->join('localizacion as l', 'ei.id_localizacion', '=', 'l.id_localizacion')
            ->select('ei.*', 'l.*')
            ->when($request->search, function ($query, $search) {
                            return $query->where('l.localizacion', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $localizacion = DB::table('localizacion as loca')->select('*')->get();

        return view('riesgos.estandarruido', compact('estandarruido', 'localizacion'));
    }

    public function storeestandarruido(Request $request)
    {
        DB::table('estandar_ruido')->insert([
            'id_localizacion' => $request->input('id_localizacion'),
            'nivel_ruido' => $request->input('nivel_ruido'),
            'tiempo_max_exposicion' => $request->input('tiempo_max_exposicion'),
        ]);

        return redirect()->back()->with('success', 'Registrado correctamente.');
    }

    // Actualiza un estándar de ruido
    public function updateestandarruido(Request $request, $id)
    {
        $data = $request->validate([
            'id_localizacion' => 'required|integer',
            'nivel_ruido' => 'required|string|max:255',
            'tiempo_max_exposicion' => 'required|string|max:255',
        ]);

        DB::table('estandar_ruido')
            ->where('id_estandar_ruido', $id)
            ->update($data);

        return redirect()->back()->with('ok', 'Estándar de ruido actualizado correctamente');
    }

    // Elimina un estándar de ruido
    public function destroyestandarruido($id)
    {
        DB::table('estandar_ruido')
            ->where('id_estandar_ruido', $id)
            ->delete();

        return redirect()->back()->with('ok', 'Estándar de ruido eliminado correctamente');
    }

    
        
// ------------------------------------------------------------------------------------------------------------

    public function estandartemperatura(Request $request)
    {
        $estandartemperatura = DB::table('estandar_temperatura as ei')
            ->join('localizacion as l', 'ei.id_localizacion', '=', 'l.id_localizacion')
            ->select('ei.*', 'l.*')
            ->when($request->search, function ($query, $search) {
                            return $query->where('l.localizacion', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $localizacion = DB::table('localizacion as loca')->select('*')->get();

        return view('riesgos.estandartemperatura', compact('estandartemperatura', 'localizacion'));
    }

    public function storeestandartemperatura(Request $request)
    {
        DB::table('estandar_temperatura')->insert([
            'id_localizacion' => $request->input('id_localizacion'),
            'rango_temperatura' => $request->input('rango_temperatura'),
        ]);

        return redirect()->back()->with('success', 'Registrado correctamente.');
    }

    // Actualiza un estándar de temperatura
    public function updateestandartemperatura(Request $request, $id)
    {
        $data = $request->validate([
            'id_localizacion' => 'required|integer',
            'rango_temperatura' => 'required|string|max:255',
        ]);

        DB::table('estandar_temperatura')
            ->where('id_estandar_temperatura', $id)
            ->update($data);

        return redirect()->back()->with('ok', 'Estándar de temperatura actualizado correctamente');
    }

    // Elimina un estándar de temperatura
    public function destroyestandartemperatura($id)
    {
        DB::table('estandar_temperatura')
            ->where('id_estandar_temperatura', $id)
            ->delete();

        return redirect()->back()->with('ok', 'Estándar de temperatura eliminado correctamente');
    }


    
// ------------------------------------------------------------------------------------------------------------
        public function estandares(Request $request)
    {
        $estandares = DB::table('estandar_iluminacion as ei')
            ->join('localizacion as l', 'ei.id_localizacion', '=', 'l.id_localizacion')
            ->join('puesto_trabajo_matriz as ptm', 'ei.id_puesto_trabajo_matriz', '=', 'ptm.id_puesto_trabajo_matriz')
            ->join('estandar_ruido as er', 'er.id_localizacion', '=', 'l.id_localizacion')
            ->join('estandar_temperatura as et', 'l.id_localizacion', '=', 'et.id_localizacion')
            ->select('ei.*', 'l.*', 'ptm.*', 'er.*', 'et.*')
            ->when($request->search, function ($query, $search) {
                return $query->where('ptm.puesto_trabajo_matriz', 'like', "%{$search}%")
                            ->orWhere('l.localizacion', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $localizacion = DB::table('localizacion as loca')->select('*')->get();
        $puestomatriz = DB::table('puesto_trabajo_matriz as pt')->select('*')->get();

        return view('riesgos.estandares', compact('estandares', 'localizacion', 'puestomatriz'));
    }

// ----------------------------------------------------------------------------------------------------
    public function tiporiesgo(Request $request)
    {
        $tiporiesgo = DB::table('tipo_riesgo as s')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('s.tipo_riesgo', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);

        return view('riesgos.tiporiesgo', compact('tiporiesgo'));
    }

    public function storetiporiesgo(Request $request)
    {
        DB::table('tipo_riesgo')->insert([
            'tipo_riesgo' => $request->input('tipo_riesgo'),
        ]);

        return redirect()->back()->with('success', 'registrado correctamente.');
    }

    public function updatetiporiesgo(Request $request, $id)
    {
        DB::table('tipo_riesgo')
            ->where('id_tipo_riesgo', $id)
            ->update([
                'tipo_riesgo' => $request->input('tipo_riesgo'),
            ]);

        return redirect()->back()->with('success', 'actualizado correctamente');
    }

    public function destroytiporiesgo($id)
    {
        DB::table('tipo_riesgo')->where('id_tipo_riesgo', $id)->delete();
        return redirect()->back()->with('success', 'eliminado correctamente');
    }

// ----------------------------------------------------------------------------------------------------
    public function riesgo(Request $request)
    {
        $riesgo = DB::table('riesgo as s')
        ->join('tipo_riesgo as tp', 's.id_tipo_riesgo', '=', 'tp.id_tipo_riesgo')
        ->select('s.*', 'tp.*')
        ->when($request->search, function ($query, $search) {
            return $query->where('s.nombre_riesgo', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        $tiporiesgo = DB::table('tipo_riesgo as loca')->select('*')->get();

        return view('riesgos.riesgos', compact('riesgo', 'tiporiesgo'));
    }

    public function storeriesgo(Request $request)
    {
        DB::table('riesgo')->insert([
            'id_tipo_riesgo' => $request->input('id_tipo_riesgo'),
            'nombre_riesgo' => $request->input('nombre_riesgo'),
        ]);

        return redirect()->back()->with('success', 'registrado correctamente.');
    }

    public function updateriesgo(Request $request, $id)
    {
        DB::table('riesgo')
            ->where('id_riesgo', $id)
            ->update([
                'id_tipo_riesgo' => $request->input('id_tipo_riesgo'),
                'nombre_riesgo' => $request->input('nombre_riesgo'),
            ]);

        return redirect()->back()->with('success', 'actualizado correctamente');
    }

    public function destroyriesgo($id)
    {
        DB::table('riesgo')->where('id_riesgo', $id)->delete();
        return redirect()->back()->with('success', 'eliminado correctamente');
    }

    // ----------------------------------------------------------------------------------------------------
    public function riesgopuesto(Request $request)
    {
        $riesgopuesto = DB::table('riesgo_valor as s')
        ->join('riesgo as r', 's.id_riesgo', '=', 'r.id_riesgo')
        ->join('puesto_trabajo_matriz as pt', 's.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
        ->select('s.*', 'pt.*', 'r.*')
        ->when($request->search, function ($query, $search) {
            return $query->where('r.nombre_riesgo', 'like', "%{$search}%")
                        ->Orwhere('pt.puesto_trabajo_matriz', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        $riesgo = DB::table('riesgo as ri')->select('*')->get();
        $puestos = DB::table('puesto_trabajo_matriz as lo')->select('*')->get();

        return view('riesgos.riesgopuesto', compact('riesgopuesto', 'riesgo', 'puestos'));
    }

    public function storeriesgopuesto(Request $request)
    {
        DB::table('riesgo_valor')->insert([
            'id_puesto_trabajo_matriz' => $request->input('id_puesto_trabajo_matriz'),
            'id_riesgo' => $request->input('id_riesgo'),
            'valor' => $request->input('valor'),
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->back()->with('success', 'registrado correctamente.');
    }

    public function updateriesgopuesto(Request $request, $id)
    {
        DB::table('riesgo_valor')
            ->where('id_riesgo_valor', $id)
            ->update([
                'id_puesto_trabajo_matriz' => $request->input('id_puesto_trabajo_matriz'),
                'id_riesgo' => $request->input('id_riesgo'),
                'valor' => $request->input('valor'),
                'observaciones' => $request->input('observaciones'),
            ]);

        return redirect()->back()->with('success', 'actualizado correctamente');
    }

    public function destroyriesgopuesto($id)
    {
        DB::table('riesgo_valor')->where('id_riesgo_valor', $id)->delete();
        return redirect()->back()->with('success', 'eliminado correctamente');
    }

// ----------------------------------------------------------------------------------------------------
    public function detallesriesgo(Request $request)
    {
        $detallesderiesgo = DB::table('detalles_riesgo as s')
            ->join('puesto_trabajo_matriz as pt', 's.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
            ->select('s.*', 'pt.*')
            ->when($request->search, function ($query, $search) {
                return $query->where('s.detalles_riesgo', 'like', "%{$search}%")
                            ->Orwhere('pt.puesto_trabajo_matriz', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        $tiporiesgo = DB::table('tipo_riesgo as ri')->select('*')->get();
        $puestos = DB::table('puesto_trabajo_matriz as lo')->select('*')->get();

        return view('riesgos.detallesriesgo', compact('detallesderiesgo', 'tiporiesgo', 'puestos'));
    }

    public function storedetallesriesgo(Request $request)
    {
        DB::table('detalles_riesgo')->insert([
            'detalles_riesgo' => $request->input('detalles_riesgo'),
            'id_puesto_trabajo_matriz' => $request->input('id_puesto_trabajo_matriz'),
            'id_tipo_riesgo' => $request->input('id_tipo_riesgo'),
            'valor' => $request->input('valor'),
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->back()->with('success', 'registrado correctamente.');
    }

    public function updatedetallesriesgo(Request $request, $id)
    {
        DB::table('detalles_riesgo')
            ->where('id_detalles_riesgo', $id)
            ->update([
                'detalles_riesgo' => $request->input('detalles_riesgo'),
                'id_puesto_trabajo_matriz' => $request->input('id_puesto_trabajo_matriz'),
                'id_tipo_riesgo' => $request->input('id_tipo_riesgo'),
                'valor' => $request->input('valor'),
                'observaciones' => $request->input('observaciones'),
            ]);

        return redirect()->back()->with('success', 'actualizado correctamente');
    }

    public function destroydetallesriesgo($id)
    {
        DB::table('detalles_riesgo')->where('id_detalles_riesgo', $id)->delete();
        return redirect()->back()->with('success', 'eliminado correctamente');
    }

// ----------------------------------------------------------------------------------------------------
    public function informacionriesgo(Request $request)
    {
        $informacionriesgo = DB::table('detalles_riesgo as dr')
            ->join('puesto_trabajo_matriz as ptm', 'dr.id_puesto_trabajo_matriz', '=', 'ptm.id_puesto_trabajo_matriz')
            ->join('tipo_riesgo as tr', 'dr.id_tipo_riesgo', '=', 'tr.id_tipo_riesgo')
            ->join('riesgo_valor as rv', 'ptm.id_puesto_trabajo_matriz', '=', 'rv.id_puesto_trabajo_matriz')
            ->join('riesgo as r', 'rv.id_riesgo', '=', 'r.id_riesgo')
            ->select('tr.tipo_riesgo', 'r.nombre_riesgo', 'dr.detalles_riesgo')
            ->orderBy('tr.tipo_riesgo', 'asc')
            ->when($request->search, function ($query, $search) {
                return $query->where('r.tipo_riesgo', 'like', "%{$search}%")
                            ->orWhere('dr.detalles_riesgo', 'like', "%{$search}%")
                            ->orWhere('r.nombre_riesgo', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);

        return view('riesgos.informacionriesgo', compact('informacionriesgo'));
    }

//----------------------------------------------------------------------------------------
        public function valoracionriesgo(Request $request)
    {
        $valoracionriesgo = DB::table('valoracion_riesgo as vr')
            ->join('probabilidad as p', 'vr.id_probabilidad', '=', 'p.id_probabilidad')
            ->join('consecuencia as c', 'vr.id_consecuencia', '=', 'c.id_consecuencia')
            ->join('nivel_riesgo as nr', 'vr.id_nivel_riesgo', '=', 'nr.id_nivel_riesgo')
            ->select('id_valoracion', 'p.probabilidad', 'c.consecuencia', 'nr.nivel_riesgo')
            ->get();
            $probabilidad = DB::table('probabilidad as s')
            ->select('*')->get();
            $consecuencia = DB::table('consecuencia as s')
            ->select('*')->get();
            $nivel_riesgo = DB::table('nivel_riesgo as s')
            ->select('*')->get();

        return view('riesgos.valoracionriesgo', compact('valoracionriesgo', 'probabilidad', 'consecuencia', 'nivel_riesgo'));
    }

        public function storevaloracionriesgo(Request $request)
    {

        DB::table('valoracion_riesgo')->insert([
            'id_probabilidad' => $request->input('id_probabilidad'),
            'id_consecuencia' => $request->input('id_consecuencia'),
            'id_nivel_riesgo' => $request->input('id_nivel_riesgo'),
        ]);

        return redirect()->back()->with('success', 'registrado correctamente.');
    }

    public function destroyvaloracionriesgo($id)
    {
        DB::table('valoracion_riesgo')->where('id_valoracion', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    //----------------------------------------------------------------------------------------
        public function evaluacionriesgos(Request $request)
    {
        $evaluacionriesgos = DB::table('evaluacion_riesgo as vr')
            ->join('puesto_trabajo_matriz as ptm', 'vr.id_puesto_trabajo_matriz', '=', 'ptm.id_puesto_trabajo_matriz')
            ->join('riesgo as ri', 'vr.id_riesgo', '=', 'ri.id_riesgo')
            ->join('probabilidad as p', 'vr.id_probabilidad', '=', 'p.id_probabilidad')
            ->join('consecuencia as c', 'vr.id_consecuencia', '=', 'c.id_consecuencia')
            ->join('nivel_riesgo as nr', 'vr.id_nivel_riesgo', '=', 'nr.id_nivel_riesgo')
            ->select('id_evaluacion_riesgo', 'ptm.puesto_trabajo_matriz', 'ri.nombre_riesgo', 'p.probabilidad', 'c.consecuencia', 'nr.nivel_riesgo')
            ->when($request->search, function ($query, $search) {
                return $query->where('ptm.puesto_trabajo_matriz', 'like', "%{$search}%")
                            ->orWhere('ri.nombre_riesgo', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->appends(['search' => $request->search]);
            $probabilidad = DB::table('probabilidad as s')
            ->select('*')->get();
            $consecuencia = DB::table('consecuencia as s')
            ->select('*')->get();
            $nivel_riesgo = DB::table('nivel_riesgo as s')
            ->select('*')->get();
            $puestos = DB::table('puesto_trabajo_matriz as s')
            ->select('*')->get();
            $riesgos = DB::table('riesgo as s')
            ->select('*')->get();

        $valoracionTabla = DB::table('valoracion_riesgo as vr')
            ->join('nivel_riesgo as nr', 'vr.id_nivel_riesgo', '=', 'nr.id_nivel_riesgo')
            ->select('vr.id_probabilidad','vr.id_consecuencia','vr.id_nivel_riesgo','nr.nivel_riesgo')
            ->get();

        return view('riesgos.evaluacionriesgos', compact('evaluacionriesgos', 'probabilidad', 'consecuencia', 'nivel_riesgo', 'puestos', 'riesgos', 'valoracionTabla'));
    }

    public function storeevaluacionriesgos(Request $request)
    {

        $data = $request->validate([
        'id_puesto_trabajo_matriz' => 'required|integer|exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz',
        'id_riesgo'                => 'required|integer|exists:riesgo,id_riesgo',
        'id_probabilidad'          => 'required|integer|exists:probabilidad,id_probabilidad',
        'id_consecuencia'          => 'required|integer|exists:consecuencia,id_consecuencia',
        ]);

        // 2) Buscar el nivel de riesgo en la tabla valoracion_riesgo
        $idNivelRiesgo = DB::table('valoracion_riesgo')
            ->where('id_probabilidad', $data['id_probabilidad'])
            ->where('id_consecuencia', $data['id_consecuencia'])
            ->value('id_nivel_riesgo');

        if (!$idNivelRiesgo) {
            return back()
                ->withErrors(['id_probabilidad' => 'No existe una valoración para esa combinación de probabilidad y consecuencia.'])
                ->withInput();
        }

        // 3) Insertar o actualizar (evita duplicados por puesto + riesgo)
        DB::transaction(function () use ($data, $idNivelRiesgo) {
            DB::table('evaluacion_riesgo')->updateOrInsert(
                [
                    'id_puesto_trabajo_matriz' => $data['id_puesto_trabajo_matriz'],
                    'id_riesgo'                => $data['id_riesgo'],
                ],
                [
                    'id_probabilidad' => $data['id_probabilidad'],
                    'id_consecuencia' => $data['id_consecuencia'],
                    'id_nivel_riesgo' => $idNivelRiesgo,
                ]
            );
        });

        return redirect()->back()->with('success', 'Evaluación registrada correctamente.');
    }


    public function destroyevaluacionriesgos($id)
    {
        DB::table('evaluacion_riesgo')->where('id_evaluacion_riesgo', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    public function updateevaluacionriesgos(Request $request, $id)
    {
        $data = $request->validate([
            'id_probabilidad' => 'required|integer|exists:probabilidad,id_probabilidad',
            'id_consecuencia' => 'required|integer|exists:consecuencia,id_consecuencia',
        ]);

        $idNivelRiesgo = DB::table('valoracion_riesgo')
            ->where('id_probabilidad', $data['id_probabilidad'])
            ->where('id_consecuencia', $data['id_consecuencia'])
            ->value('id_nivel_riesgo');

        if (!$idNivelRiesgo) {
            return back()
                ->withErrors(['id_probabilidad' => 'No existe una valoración para esa combinación.'])
                ->withInput();
        }

        DB::table('evaluacion_riesgo')
            ->where('id_evaluacion_riesgo', (int)$id)
            ->update([
                'id_probabilidad' => $data['id_probabilidad'],
                'id_consecuencia' => $data['id_consecuencia'],
                'id_nivel_riesgo' => $idNivelRiesgo,
            ]);

        return redirect()->back()->with('success', 'Evaluación actualizada correctamente.');
    }


    // -------------------------------------------------------------------------------------

    public function matrizEvaluacion()
    {
        $matriz = DB::select('CALL generar_matriz_evaluacion_riesgos_transpuesta()');

        // Filtrar columnas dinámicas para mostrar solo puestos con estado=1
        try {
            $puestosActivos = DB::table('puesto_trabajo_matriz')
                ->where('estado', 1)
                ->pluck('puesto_trabajo_matriz')
                ->map(fn($s) => mb_strtoupper(trim((string)$s), 'UTF-8'))
                ->toArray();

            if (!empty($matriz)) {
                $first = (array) $matriz[0];
                $keysToRemove = [];
                foreach (array_keys($first) as $col) {
                    if (strpos($col, '||') !== false) {
                        [$dep, $puesto, $num] = explode('||', $col, 3);
                        $puestoNorm = mb_strtoupper(trim((string)$puesto), 'UTF-8');
                        if (!in_array($puestoNorm, $puestosActivos, true)) {
                            $keysToRemove[] = $col;
                        }
                    }
                }
                if ($keysToRemove) {
                    foreach ($matriz as $row) {
                        foreach ($keysToRemove as $k) {
                            if (property_exists($row, $k)) { unset($row->$k); }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Si falla, continuamos sin filtrar para no romper la vista
        }

        // Enriquecer con columna MEDIDAS desde tabla riesgo.medidas (varchar 5000)
        try {
            $nombres = collect($matriz)->map(function ($r) {
                $obj = (array)$r;
                return (string)($obj['RIESGO'] ?? '');
            })->filter()->unique()->values();

            if ($nombres->isNotEmpty()) {
                $map = DB::table('riesgo')
                    ->whereIn('nombre_riesgo', $nombres->all())
                    ->pluck('medidas', 'nombre_riesgo');

                foreach ($matriz as $row) {
                    $nombre = (string)((array)$row)['RIESGO'] ?? '';
                    $medidas = (string)($map[$nombre] ?? '');
                    // agregar propiedad dinamica al stdClass
                    $row->MEDIDAS = $medidas;
                }
            }
        } catch (\Throwable $e) {
            // fallback silencioso si falla
        }

        return view('riesgos.evaluacion_riesgos', compact('matriz'));
    }

public function exportMatrizEvaluacionExcel()
{
    // ====== 1) Datos ======
    $result = \DB::select('CALL generar_matriz_evaluacion_riesgos_transpuesta()');
    $rows = collect($result)->map(fn($r) => (array) $r);
    if ($rows->isEmpty()) {
        $rows = collect([['N°'=> '', 'RIESGO'=>'', 'DESCRIPCION'=>'']]);
    }

    // ====== 1.1 Set de combos ACTIVOS (DEP||PUESTO) con estado = 1 ======
    // Si algo falla, no filtramos (se exporta como antes).
    try {
    $activosSet = \DB::table('puesto_trabajo_matriz as ptm')
        ->join('departamento as d', 'd.id_departamento', '=', 'ptm.id_departamento')
        ->where('ptm.estado', 1) // solo puestos activos de la MATRIZ
        ->selectRaw('
            UPPER(TRIM(COALESCE(d.departamento, "SIN DEPTO"))) as dep,
            UPPER(TRIM(ptm.puesto_trabajo_matriz))             as pto
        ')
        ->distinct()
        ->get()
        ->map(fn($r) => "{$r->dep}||{$r->pto}")
        ->flip(); // convierte a "set" (keys)
} catch (\Throwable $e) {
    $activosSet = collect(); // vacío = no filtra
}

    // ====== 2) Detectar columnas fijas y puestos por departamento ======
    $first = (array) $rows->first();
    $fixed = [];          // N°, RIESGO, DESCRIPCION
    $headers = [];        // $headers[DEPTO] = [ ['key'=>..., 'puesto'=>..., 'num'=>...], ... ]

    foreach (array_keys($first) as $col) {
        if (strpos($col, '||') !== false) {
            [$dep, $puesto, $num] = explode('||', $col);
            $dep    = trim($dep);
            $puesto = trim($puesto);
            $num    = (int) $num;

            // ====== (CAMBIO #1) Filtrar por estado=1 ======
            $token = mb_strtoupper("{$dep}||{$puesto}", 'UTF-8');
            if ($activosSet->isEmpty() || $activosSet->has($token)) {
                $headers[$dep][] = ['key'=>$col, 'puesto'=>$puesto, 'num'=>$num];
            }
        } else {
            $fixed[] = $col;
        }
    }

    // Orden deseado de fijas
    $ordenDeseado = ['N°','RIESGO','DESCRIPCION'];
    $fixed = array_values(array_intersect($ordenDeseado, $fixed));

    // ====== 3) Colores de nivel de riesgo ======
    $colorNivel = [
        'RIESGO MUY ALTO'    => 'FF0000',
        'RIESGO ALTO'        => 'BE5014',
        'RIESGO MEDIO'       => 'FFC000',
        'RIESGO BAJO'        => 'FFFF00',
        'RIESGO IRRELEVANTE' => '92D050',
    ];

    // ====== 4) Crear libro/hoja ======
    $ss = new Spreadsheet();
    $sh = $ss->getActiveSheet();
    $sh->setTitle('Matriz de Riesgos');

    $sh->getPageSetup()
       ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
       ->setPaperSize(PageSetup::PAPERSIZE_A4)
       ->setFitToWidth(1)->setFitToHeight(0);
    $sh->getPageMargins()->setTop(0.4)->setRight(0.3)->setLeft(0.3)->setBottom(0.4);

    // ====== 5) Logo + Títulos ======
    $logo = public_path('img/logo.PNG');
    if (file_exists($logo)) {
        $d = new Drawing();
        $d->setPath($logo);
        $d->setHeight(60);
        $d->setCoordinates('A1');
        $d->setOffsetX(2);
        $d->setWorksheet($sh);
    }

    // Total dinámicos (ya filtrados)
    $totalDyn = array_sum(array_map('count', $headers));
    // +1 por MEDIDAS y +3 por CUMPLIMIENTO
    $totalCols = count($fixed) + $totalDyn + 4;
    $lastCol = Coordinate::stringFromColumnIndex($totalCols);

    // Títulos
    $sh->mergeCells("C1:{$lastCol}1");
    $sh->mergeCells("C2:{$lastCol}2");
    $sh->mergeCells("C3:{$lastCol}3");
    $sh->setCellValue('C1', 'MATRIZ DE EVALUACION DE RIESGOS POR PUESTO DE TRABAJO');
    $sh->setCellValue('C2', 'PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS');
    $sh->setCellValue('C3', 'MATRIZ DE ANALISIS DE RIESGO / RISK ANALYSIS MATRIX');
    $sh->getStyle("C1:C3")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $sh->getStyle("C1")->getFont()->setBold(true)->setSize(14);
    $sh->getStyle("C2:C3")->getFont()->setSize(10);

    // ====== 6) Encabezado triple ======
    $start = 6;
    $rowAreas  = $start;
    $rowLabels = $start + 1;
    $rowNums   = $start + 2;
    $rowData   = $start + 3;

    // Bloque "Áreas de Trabajo" sobre RIESGO + DESCRIPCION
    if (in_array('RIESGO', $fixed) && in_array('DESCRIPCION', $fixed)) {
        $sh->mergeCells("B{$rowAreas}:C{$rowAreas}");
        $sh->setCellValue("B{$rowAreas}", 'Áreas de Trabajo');
    }

    // Etiquetas fijas
    if (in_array('N°', $fixed)) {
        $sh->mergeCells("A{$rowLabels}:A{$rowNums}");
        $sh->setCellValue("A{$rowLabels}", 'N°');
    }
    if (in_array('RIESGO', $fixed))      $sh->setCellValue("B{$rowLabels}", 'RIESGO');
    if (in_array('DESCRIPCION', $fixed)) $sh->setCellValue("C{$rowLabels}", 'DESCRIPCION');

    $sh->mergeCells("B{$rowNums}:C{$rowNums}");
    $sh->setCellValue("B{$rowNums}", 'Número de Empleados');

    // Estilos fijas
    $sh->getStyle("B{$rowAreas}:C{$rowAreas}")
       ->applyFromArray([
           'fill' => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['rgb'=>'E5E7EB']],
           'font' => ['bold'=>true],
           'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
       ]);
    $sh->getStyle("A{$rowLabels}:C{$rowNums}")
       ->applyFromArray([
           'fill' => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['rgb'=>'E5E7EB']],
           'font' => ['bold'=>true],
           'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
       ]);

    // Departamentos + Puestos + # Empleados
    $colIdx = 4; // D
    foreach ($headers as $dep => $puestos) {
        if (empty($puestos)) continue; // <- si todos inactivos, no dibujar bloque

        $span = count($puestos);
        $colStart = Coordinate::stringFromColumnIndex($colIdx);
        $colEnd   = Coordinate::stringFromColumnIndex($colIdx + $span - 1);

        // Departamento
        $sh->mergeCells("{$colStart}{$rowAreas}:{$colEnd}{$rowAreas}");
        $sh->setCellValue("{$colStart}{$rowAreas}", $dep);
        $sh->getStyle("{$colStart}{$rowAreas}:{$colEnd}{$rowAreas}")
           ->applyFromArray([
               'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'4F46E5']],
               'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
               'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
           ]);

        // Puestos
        foreach ($puestos as $p) {
            $col = Coordinate::stringFromColumnIndex($colIdx++);
            $sh->setCellValue("{$col}{$rowLabels}", $p['puesto']);
            $sh->getStyle("{$col}{$rowLabels}")
               ->applyFromArray([
                   'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'ACB9CA']],
                   'font'=>['bold'=>true],
                   'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                   'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
               ]);
        }

        // Números
        $i = 0;
        foreach ($puestos as $p) {
            $col = Coordinate::stringFromColumnIndex($colIdx - $span + $i);
            $sh->setCellValue("{$col}{$rowNums}", $p['num']);
            $i++;
        }
        $sh->getStyle("{$colStart}{$rowNums}:{$colEnd}{$rowNums}")
           ->applyFromArray([
               'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'E8EDF4']],
               'font'=>['bold'=>true],
               'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
           ]);
    }

    // Columna MEDIDAS
    $colMedidasIdx = count($fixed) + $totalDyn + 1;
    $colMedidas = Coordinate::stringFromColumnIndex($colMedidasIdx);
    $sh->mergeCells("{$colMedidas}{$rowAreas}:{$colMedidas}{$rowNums}");
    $sh->setCellValue("{$colMedidas}{$rowAreas}", 'MEDIDAS DE PREVENCION Y CORRECCION');
    $sh->getStyle("{$colMedidas}{$rowAreas}:{$colMedidas}{$rowNums}")
       ->applyFromArray([
           'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'E5E7EB']],
           'font'=>['bold'=>true],
           'alignment'=>['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
       ]);

    // Bloque CUMPLIMIENTO
    $compCols = ['TOTAL','PARCIAL','NO CUMPLE'];
    $compStartIdx = $colMedidasIdx + 1;
    $compEndIdx   = $compStartIdx + count($compCols) - 1;
    $compStartCol = Coordinate::stringFromColumnIndex($compStartIdx);
    $compEndCol   = Coordinate::stringFromColumnIndex($compEndIdx);

    $sh->mergeCells("{$compStartCol}{$rowAreas}:{$compEndCol}{$rowAreas}");
    $sh->setCellValue("{$compStartCol}{$rowAreas}", 'CUMPLIMIENTO');
    $sh->getStyle("{$compStartCol}{$rowAreas}:{$compEndCol}{$rowAreas}")
       ->applyFromArray([
           'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'0B5DBB']],
           'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
           'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
       ]);

    for ($k = 0; $k < count($compCols); $k++) {
        $col = Coordinate::stringFromColumnIndex($compStartIdx + $k);
        $sh->mergeCells("{$col}{$rowLabels}:{$col}{$rowNums}");
        $sh->setCellValue("{$col}{$rowLabels}", $compCols[$k]);
        $sh->getStyle("{$col}{$rowLabels}:{$col}{$rowNums}")
           ->applyFromArray([
               'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1976D2']],
               'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
               'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]],
           ]);
    }

    // ====== 7) Datos ======
   // ====== MEDIDAS por riesgo desde medidas_riesgo_puesto ======
// ====== MEDIDAS por riesgo desde medidas_riesgo_puesto ======
try {
    \DB::statement('SET SESSION group_concat_max_len = 1000000');

    $medidasRows = \DB::table('medidas_riesgo_puesto as mrp')
        ->join('riesgo as r', 'r.id_riesgo', '=', 'mrp.id_riesgo')
        ->leftJoin('area as a', 'a.id_area', '=', 'mrp.id_area')
        ->leftJoin('epp as e', 'e.id_epp', '=', 'mrp.id_epp')
        ->leftJoin('capacitacion as c', 'c.id_capacitacion', '=', 'mrp.id_capacitacion')
        ->leftJoin('senalizacion as s', 's.id_senalizacion', '=', 'mrp.id_senalizacion')
        ->leftJoin('otras_medidas as o', 'o.id_otras_medidas', '=', 'mrp.id_otras_medidas')
        ->selectRaw("
            r.nombre_riesgo as riesgo,
            GROUP_CONCAT(DISTINCT NULLIF(a.area, '')          ORDER BY a.area          SEPARATOR ', ') as areas,
            GROUP_CONCAT(DISTINCT NULLIF(e.equipo, '')        ORDER BY e.equipo        SEPARATOR ', ') as epps,
            GROUP_CONCAT(DISTINCT NULLIF(c.capacitacion, '')  ORDER BY c.capacitacion  SEPARATOR ', ') as caps,
            GROUP_CONCAT(DISTINCT NULLIF(s.senalizacion, '')  ORDER BY s.senalizacion  SEPARATOR ', ') as sens,
            GROUP_CONCAT(DISTINCT NULLIF(o.otras_medidas, '') ORDER BY o.otras_medidas SEPARATOR ', ') as otras
        ")
        ->groupBy('r.id_riesgo','r.nombre_riesgo')
        ->get();

    $medidasMap = [];
    foreach ($medidasRows as $m) {
        $partes = [];
        if (!empty($m->areas)) $partes[] = 'Áreas: ' . $m->areas;
        if (!empty($m->epps))  $partes[] = 'EPP: ' . $m->epps;
        if (!empty($m->caps))  $partes[] = 'Capacitaciones: ' . $m->caps;
        if (!empty($m->sens))  $partes[] = 'Señalización: ' . $m->sens;
        if (!empty($m->otras)) $partes[] = 'Otras: ' . $m->otras;

        $texto = implode("\n• ", $partes);
        if ($texto !== '') $texto = '• ' . $texto;
        $medidasMap[mb_strtoupper(trim($m->riesgo), 'UTF-8')] = $texto;
    }
} catch (\Throwable $e) {
    $medidasMap = [];
}


    $r = $rowData;
    $cont = 1; // <<< contador para la columna N°
    foreach ($rows as $row) {
        // Fijas
        // Fijas (N° = contador)
        $colIdx = 1;
        foreach ($fixed as $fx) {
            $col = Coordinate::stringFromColumnIndex($colIdx++);
            $value = ($fx === 'N°')
                ? $cont                      // contador 1..n
                : ($row[$fx] ?? '');
            $sh->setCellValue("{$col}{$r}", $value);
        }

        // MEDIDAS
        $med = '';
$riesgoKey = mb_strtoupper(trim((string)($row['RIESGO'] ?? '')), 'UTF-8');
$med = (string)($medidasMap[$riesgoKey] ?? '');
$col = Coordinate::stringFromColumnIndex($colMedidasIdx);
$sh->setCellValue("{$col}{$r}", $med);
$sh->getStyle("{$col}{$r}")
    ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);



        // Dinámicas (solo las que quedaron en $headers => solo activos)
        foreach ($headers as $dep => $puestos) {
            foreach ($puestos as $p) {
                $key = $p['key'];
                $val = $row[$key] ?? '';
                $col = Coordinate::stringFromColumnIndex($colIdx++);
                $sh->setCellValue("{$col}{$r}", $val);

                $norm = is_string($val) ? mb_strtoupper(trim($val), 'UTF-8') : (string)$val;
                if (isset($colorNivel[$norm])) {
                    $sh->getStyle("{$col}{$r}")->getFill()
                       ->setFillType(Fill::FILL_SOLID)
                       ->getStartColor()->setRGB($colorNivel[$norm]);
                    $sh->getStyle("{$col}{$r}")->getFont()->setBold(true);
                }
            }
        }

        // Bloque Cumplimiento
        for ($k = 0; $k < count($compCols); $k++) {
            $col  = Coordinate::stringFromColumnIndex($compStartIdx + $k);
            $cell = "{$col}{$r}";
            $default = ($compCols[$k] === 'TOTAL') ? 'X' : '';
            $sh->setCellValue($cell, $default);

            $dv = $sh->getCell($cell)->getDataValidation();
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

            $sh->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ====== (CAMBIO #2) NO pintamos en verde aquí.
            // El color lo aplicará el formato condicional más abajo.
        }

        // Bordes/altura
        $sh->getStyle("A{$r}:{$lastCol}{$r}")
           ->applyFromArray([
               'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
               'alignment'=>['vertical'=>Alignment::VERTICAL_TOP,'wrapText'=>true],
           ]);
        $sh->getRowDimension($r)->setRowHeight(42);
        $cont++; // siguiente número para la columna N°
        $r++;
    }

    // ====== 8) Anchos y freeze ======
    $sh->getColumnDimension('A')->setWidth(6);
    $sh->getColumnDimension('B')->setWidth(40);
    $sh->getColumnDimension('C')->setWidth(64);
    for ($i = 4; $i <= ($colMedidasIdx - 1); $i++) {
        $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(18);
    }
    $sh->getColumnDimension(Coordinate::stringFromColumnIndex($colMedidasIdx))->setWidth(64);
    for ($k = 0; $k < count($compCols); $k++) {
        $sh->getColumnDimension(Coordinate::stringFromColumnIndex($compStartIdx + $k))->setWidth(10);
    }

    // --- Pie de página “escrito” EN CELDAS (no header/footer) ---
$footerRow = $r + 2; // deja una fila en blanco; ajústalo si lo quieres más cerca
$third     = max(1, intdiv($totalCols, 3));

$leftStart   = 'A';
$leftEnd     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($third);
$centerStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($third + 1);
$centerEnd   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max($third * 2, $third + 1));
$rightStart  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(min($third * 2 + 1, $totalCols));
$rightEnd    = $lastCol;

// Merges (1ª línea izquierda; 2ª línea con izquierda, centro y derecha)
$sh->mergeCells("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}");
$sh->mergeCells("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1));
$sh->mergeCells("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1));
$sh->mergeCells("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1));

// Textos
$sh->setCellValue("{$leftStart}{$footerRow}",       '1 Copia Archivo');
$sh->setCellValue("{$leftStart}".($footerRow+1),    '1 Copia Sistema');
$sh->setCellValue("{$centerStart}".($footerRow+1),  '2 VERSION 2025');
$sh->setCellValue("{$rightStart}".($footerRow+1),   'STB/SSO/R054');

// Estilos base (Arial 9) y alturas
$sh->getStyle("A{$footerRow}:{$lastCol}".($footerRow+1))
   ->getFont()->setName('Arial')->setSize(9);
$sh->getRowDimension($footerRow)->setRowHeight(16);
$sh->getRowDimension($footerRow+1)->setRowHeight(16);

// Alineaciones
$sh->getStyle("{$leftStart}{$footerRow}:{$leftEnd}{$footerRow}")
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
$sh->getStyle("{$leftStart}".($footerRow+1).":{$leftEnd}".($footerRow+1))
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
$sh->getStyle("{$centerStart}".($footerRow+1).":{$centerEnd}".($footerRow+1))
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sh->getStyle("{$rightStart}".($footerRow+1).":{$rightEnd}".($footerRow+1))
   ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

// (Opcional) una línea suave arriba del pie para separarlo visualmente
$sh->getStyle("A".($footerRow-1).":{$lastCol}".($footerRow-1))
   ->applyFromArray([
     'borders'=>['top'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                         'color'=>['rgb'=>'DDDDDD']]]
   ]);

    // Formatos condicionales
    $firstDataRow = $rowData;
    $lastDataRow  = $r - 1;

    // (a) Aviso si no hay exactamente una "X"
    for ($rr = $firstDataRow; $rr <= $lastDataRow; $rr++) {
        $rangeRow = Coordinate::stringFromColumnIndex($compStartIdx).$rr.':'.
                    Coordinate::stringFromColumnIndex($compEndIdx).$rr;
        $cf = new Conditional();
        $cf->setConditionType(Conditional::CONDITION_EXPRESSION);
        $cf->addCondition("=COUNTIF(\$".
            Coordinate::stringFromColumnIndex($compStartIdx)."{$rr}:\$".
            Coordinate::stringFromColumnIndex($compEndIdx)."{$rr},\"X\")<>1");
        $cf->getStyle()->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FF9900']]],
        ]);
        $existing = $sh->getStyle($rangeRow)->getConditionalStyles();
        $existing[] = $cf;
        $sh->getStyle($rangeRow)->setConditionalStyles($existing);
    }

    // (b) Colorear cada columna cuando hay "X"
    $colColor = ['TOTAL'=>'00B050','PARCIAL'=>'FFC000','NO CUMPLE'=>'FF0000'];
    for ($k = 0; $k < count($compCols); $k++) {
        $colLetter = Coordinate::stringFromColumnIndex($compStartIdx + $k);
        $rangeCol  = $colLetter . $firstDataRow . ':' . $colLetter . $lastDataRow;

        $cond = new Conditional();
        $cond->setConditionType(Conditional::CONDITION_CELLIS);
        $cond->setOperatorType(Conditional::OPERATOR_EQUAL);
        $cond->addCondition('"X"');
        $rgb = $colColor[$compCols[$k]] ?? 'FFFFFF';
        $cond->getStyle()->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $existing = $sh->getStyle($rangeCol)->getConditionalStyles();
        $existing[] = $cond;
        $sh->getStyle($rangeCol)->setConditionalStyles($existing);
    }

    // Congelar
    $sh->freezePane('D'.($rowData));

    // ====== 9) Descargar ======
    $file = 'Matriz_Evaluacion_Riesgos_'.date('Ymd_His').'.xlsx';
    $writer = new Xlsx($ss);
    if (ob_get_length()) { ob_end_clean(); }
    return response()->streamDownload(fn() => $writer->save('php://output'), $file, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}









    public function index2(Request $request)
    {
        // Listas para los selects
        $puestos = DB::table('puesto_trabajo_matriz')
            ->select('id_puesto_trabajo_matriz as id', 'puesto_trabajo_matriz as nombre')
            ->orderBy('puesto_trabajo_matriz')->get();

        $riesgos = DB::table('riesgo')
            ->select('id_riesgo as id', 'nombre_riesgo as nombre')
            ->orderBy('nombre_riesgo')->get();

        $byPuesto = collect();
        $byRiesgo = collect();
        $puestoSel = null;
        $riesgoSel = null;

        // -------- Consulta: por Puesto --------
        if ($request->filled('puesto_id')) {
            $puestoId  = (int) $request->get('puesto_id');
            $puestoSel = DB::table('puesto_trabajo_matriz')
                ->where('id_puesto_trabajo_matriz', $puestoId)
                ->value('puesto_trabajo_matriz');

            $byPuesto = DB::table('riesgo as r')
                ->leftJoin('tipo_riesgo as tr', 'r.id_tipo_riesgo', '=', 'tr.id_tipo_riesgo')
                ->leftJoin('riesgo_valor as rv', function ($join) use ($puestoId) {
                    $join->on('rv.id_riesgo', '=', 'r.id_riesgo')
                         ->where('rv.id_puesto_trabajo_matriz', '=', $puestoId);
                })
                ->leftJoin('evaluacion_riesgo as er', function ($join) use ($puestoId) {
                    $join->on('er.id_riesgo', '=', 'r.id_riesgo')
                         ->where('er.id_puesto_trabajo_matriz', '=', $puestoId);
                })
                ->leftJoin('probabilidad as prob', 'er.id_probabilidad', '=', 'prob.id_probabilidad')
                ->leftJoin('consecuencia as cons', 'er.id_consecuencia', '=', 'cons.id_consecuencia')
                ->leftJoin('nivel_riesgo as nr', 'er.id_nivel_riesgo', '=', 'nr.id_nivel_riesgo')
                ->where(function ($q) {
                    $q->whereNotNull('rv.id_riesgo')
                      ->orWhereNotNull('er.id_riesgo');
                })
                ->orderBy('tr.tipo_riesgo')
                ->orderBy('r.nombre_riesgo')
                ->get([
                    'tr.tipo_riesgo',
                    'r.id_riesgo',
                    'r.nombre_riesgo',
                    'rv.valor',
                    'rv.observaciones',
                    'prob.probabilidad',
                    'cons.consecuencia',
                    'nr.nivel_riesgo',
                ]);
        }

        // -------- Consulta: por Riesgo --------
        if ($request->filled('riesgo_id')) {
            $riesgoId  = (int) $request->get('riesgo_id');
            $riesgoSel = DB::table('riesgo')->where('id_riesgo', $riesgoId)->value('nombre_riesgo');

            $byRiesgo = DB::table('puesto_trabajo_matriz as pt')
                ->leftJoin('riesgo_valor as rv', function ($join) use ($riesgoId) {
                    $join->on('rv.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
                         ->where('rv.id_riesgo', '=', $riesgoId);
                })
                ->leftJoin('evaluacion_riesgo as er', function ($join) use ($riesgoId) {
                    $join->on('er.id_puesto_trabajo_matriz', '=', 'pt.id_puesto_trabajo_matriz')
                         ->where('er.id_riesgo', '=', $riesgoId);
                })
                ->leftJoin('probabilidad as prob', 'er.id_probabilidad', '=', 'prob.id_probabilidad')
                ->leftJoin('consecuencia as cons', 'er.id_consecuencia', '=', 'cons.id_consecuencia')
                ->leftJoin('nivel_riesgo as nr', 'er.id_nivel_riesgo', '=', 'nr.id_nivel_riesgo')
                ->where(function ($q) {
                    $q->whereNotNull('rv.id_riesgo')
                      ->orWhereNotNull('er.id_riesgo');
                })
                ->orderBy('pt.puesto_trabajo_matriz')
                ->get([
                    'pt.id_puesto_trabajo_matriz',
                    'pt.puesto_trabajo_matriz',
                    'rv.valor',
                    'rv.observaciones',
                    'prob.probabilidad',
                    'cons.consecuencia',
                    'nr.nivel_riesgo',
                ]);
        }

        return view('riesgos.inicioriesgos', compact(
            'puestos', 'riesgos',
            'byPuesto', 'byRiesgo',
            'puestoSel', 'riesgoSel'
        ));
    }

}
