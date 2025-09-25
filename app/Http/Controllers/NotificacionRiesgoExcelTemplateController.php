<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class NotificacionRiesgoExcelTemplateController extends Controller
{
    public function index(Request $request)
    {
        // No renderizamos directamente el layout para evitar variables faltantes.
        // Redirigimos a la pantalla principal de riesgos desde donde se abre el modal.
        if (url()->previous()) {
            return redirect()->back();
        }
        return redirect()->route('riesgos.identificacion-riesgos');
    }

    public function export(Request $request)
    {
        $request->validate([
            'ptm_id' => 'required|integer|exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz',
        ]);

        // === Datos base
        $ptmId = (int)$request->ptm_id;
        $ptm = DB::table('puesto_trabajo_matriz')
            ->where('id_puesto_trabajo_matriz', $ptmId)
            ->first(['id_puesto_trabajo_matriz','puesto_trabajo_matriz']);
        if (!$ptm) abort(404, 'Puesto no encontrado');

$targetName = trim((string) $ptm->puesto_trabajo_matriz);

$empleados = DB::table('empleado as e')
    ->leftJoin('puesto_trabajo_matriz as pm', 'pm.id_puesto_trabajo_matriz', '=', 'e.id_puesto_trabajo_matriz')
    ->leftJoin('puesto_trabajo as ps', 'ps.id_puesto_trabajo', '=', 'e.id_puesto_trabajo')
    ->where('e.estado', 1)
    // Si tiene matriz (no NULL/0) comparamos contra pm.puesto_trabajo_matriz;
    // si no, comparamos contra el nombre del puesto del sistema (ps.puesto_trabajo).
    ->whereRaw(
        'TRIM(UPPER(CASE 
            WHEN e.id_puesto_trabajo_matriz IS NOT NULL AND e.id_puesto_trabajo_matriz <> 0
                THEN pm.puesto_trabajo_matriz
            ELSE ps.puesto_trabajo
        END)) = TRIM(UPPER(?))',
        [$targetName]
    )
    ->orderBy('e.nombre_completo')
    ->get(['e.nombre_completo','e.identidad']);

        // === Cargar plantilla desde storage/app/public
        // Asegúrate de tener el symlink: php artisan storage:link
        $templateRelPath = 'formato_notificacion_riesgos_puesto.xlsx';
        $templatePath = Storage::disk('public')->path($templateRelPath);

        if (!file_exists($templatePath)) {
            abort(500, "No se encontró la plantilla en storage/app/public/{$templateRelPath}");
        }

        $spreadsheet = IOFactory::load($templatePath);
        // Solo insertar el puesto en C13 y descargar, dejando el resto en blanco
        $sheet = $spreadsheet->getActiveSheet();
        $this->writePuesto($sheet, $ptm->puesto_trabajo_matriz);
        // Reforzar encabezado si la plantilla lo pierde al guardar (textboxes no soportados)
        // Solo escribir si están vacíos para no sobreescribir encabezados válidos
        $h1 = trim((string)$sheet->getCell('C1')->getValue());
        $h2 = trim((string)$sheet->getCell('C2')->getValue());
        $h3 = trim((string)$sheet->getCell('C3')->getValue());
        if ($h1 === '') { $sheet->setCellValue('C1', 'SERVICE AND TRADING BUSINESS S.A. DE C.V.'); }
        if ($h2 === '') { $sheet->setCellValue('C2', 'PROCESO SALUD Y SEGURIDAD OCUPACIONAL/ HEALTH AND OCCUPATIONAL SAFETY PROCESS'); }
        if ($h3 === '') { $sheet->setCellValue('C3', 'NOTIFICACION DE RIESGO/ NOTIFICATION OF RISK'); }

        // Paginación: 15 empleados por hoja (B16 nombre, C16 identidad)
        $perPage = 15;
        $chunks  = $empleados->chunk($perPage);
        if ($chunks->isEmpty()) { $chunks = collect([collect()]); }

        // Clonar hojas adicionales conservando el formato de la primera
        $base = $spreadsheet->getSheet(0);
        for ($i = 1; $i < $chunks->count(); $i++) {
            $clone = $base->copy();
            $clone->setTitle('Notificación '.($i+1));
            $spreadsheet->addSheet($clone);
        }

        // Rellenar cada hoja
        foreach ($chunks as $index => $chunk) {
            /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sh */
            $sh = $spreadsheet->getSheet($index);
            // Asegurar puesto en C13 y encabezados visibles
            $this->writePuesto($sh, $ptm->puesto_trabajo_matriz);
            $eh1 = trim((string)$sh->getCell('C1')->getValue());
            $eh2 = trim((string)$sh->getCell('C2')->getValue());
            $eh3 = trim((string)$sh->getCell('C3')->getValue());
            if ($eh1 === '') { $sh->setCellValue('C1', 'SERVICE AND TRADING BUSINESS S.A. DE C.V.'); }
            if ($eh2 === '') { $sh->setCellValue('C2', 'PROCESO SALUD Y SEGURIDAD OCUPACIONAL/ HEALTH AND OCCUPATIONAL SAFETY PROCESS'); }
            if ($eh3 === '') { $sh->setCellValue('C3', 'NOTIFICACION DE RIESGO/ NOTIFICATION OF RISK'); }

            // Limpiar 15 filas (solo columnas B y C)
            for ($r = 16; $r < 16 + $perPage; $r++) {
                $sh->setCellValue('B'.$r, '');
                $sh->setCellValueExplicit('C'.$r, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            // Escribir chunk
            $row = 16;
            foreach ($chunk as $emp) {
                $sh->setCellValue('B'.$row, (string)($emp->nombre_completo ?? ''));
                $sh->setCellValueExplicit('C'.$row, (string)($emp->identidad ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $row++;
                if ($row >= 16 + $perPage) break;
            }
            $sh->setTitle($index === 0 ? 'Notificación 1' : ('Notificación '.($index+1)));
        }
        $safeName = preg_replace('/[^\\w\\-]+/u', '_', $ptm->puesto_trabajo_matriz);
        $filename = "Notificacion_Riesgos_{$safeName}_" . Carbon::now()->format('Ymd') . ".xlsx";
        try {
            $tmp = storage_path('app/'.uniqid('notif_puesto_', true).'.xlsx');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tmp);
            while (ob_get_level() > 0) { @ob_end_clean(); }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo generar el archivo de Excel: '.$e->getMessage());
        }
        return response()->download($tmp, $filename, [
            'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'                    => 'no-cache',
            'Content-Transfer-Encoding' => 'binary',
        ])->deleteFileAfterSend(true);
        // Tomamos la PRIMERA hoja como plantilla base para clonar si hay varias páginas
        $baseSheet = $spreadsheet->getSheet(0);

        // Parámetros del template
        $perPage = 15; // asumiendo 15 filas dibujadas para firmas
        $fecha = Carbon::now()->format('d/m/Y');

        // Vamos a dividir empleados en chunks de 15.
        $chunks = $empleados->chunk($perPage);
        if ($chunks->isEmpty()) {
            $chunks = collect([collect()]); // al menos una página vacía
        }

        // Rellenar la primera hoja y clonar para el resto
        $totalSheetsNeeded = $chunks->count();
        for ($i = 1; $i < $totalSheetsNeeded; $i++) {
            $clone = $baseSheet->copy();
            $clone->setTitle('Notificación ' . ($i+1));
            $spreadsheet->addSheet($clone);
        }

        // Rellenar cada hoja con su chunk
        foreach ($chunks as $index => $chunk) {
            /** @var Worksheet $sheet */
            $sheet = $spreadsheet->getSheet($index);

        // --- 1) Escribir Puesto en la celda C13 (según solicitud)
        $this->writePuesto($sheet, $ptm->puesto_trabajo_matriz);

            // --- 2) Escribir fecha si existe un named range "FECHA" o una celda que contenga "Fecha:"
            $this->writeFecha($sheet, $fecha);

            // --- 3) Localizar cabecera de la tabla "N°, Nombre, Identidad, Firma"
            [$headerRow, $colA, $colB, $colC, $colD] = $this->findHeader($sheet);

            $startRow = $headerRow + 1;
            $n = 1;

            // Borrar/limpiar las 15 filas destino (para no arrastrar datos previos)
            for ($r = $startRow; $r < $startRow + $perPage; $r++) {
                $sheet->setCellValueExplicit("A{$r}", '', DataType::TYPE_STRING);
                $sheet->setCellValue("B{$r}", '');
                $sheet->setCellValueExplicit("C{$r}", '', DataType::TYPE_STRING);
                $sheet->setCellValue("D{$r}", '');
            }

            // Rellenar chunk
            foreach ($chunk as $emp) {
                $sheet->setCellValue("A{$startRow}", $n);
                $sheet->setCellValue("B{$startRow}", $emp->nombre_completo ?? '');

                // Identidad como TEXTO para conservar formato
                $sheet->setCellValueExplicit("C{$startRow}", (string)($emp->identidad ?? ''), DataType::TYPE_STRING);

                // Firma queda en blanco (celda D)
                $n++;
                $startRow++;
            }

            // Completar filas restantes hasta 15 con numeración
            while (($startRow - $headerRow - 1) < $perPage) {
                $sheet->setCellValue("A{$startRow}", $n);
                $sheet->setCellValue("B{$startRow}", '');
                $sheet->setCellValueExplicit("C{$startRow}", '', DataType::TYPE_STRING);
                $sheet->setCellValue("D{$startRow}", '');
                $n++; $startRow++;
            }

            // Renombrar hoja (opcional, útil si vas a imprimir)
            $sheet->setTitle($index === 0 ? 'Notificación 1' : ('Notificación ' . ($index+1)));
        }

        // Descargar
        $safeName = preg_replace('/[^\w\-]+/u', '_', $ptm->puesto_trabajo_matriz);
        $filename = "Notificacion_Riesgos_{$safeName}_" . Carbon::now()->format('Ymd') . ".xlsx";

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Intenta escribir el puesto:
     *  1) Named range "PUESTO" (ideal).
     *  2) Buscar celda que contenga "PERSONAL NOTIFICADO DE:" y completar ahí mismo.
     *  3) Si falla, usar una heurística (por ejemplo, fila 10, col A) — ajusta si tu plantilla difiere.
     */
    private function writePuesto(Worksheet $sheet, string $puesto): void
    {
        // Escribir directamente en C13
        $sheet->setCellValue('C13', $puesto);
    }

    /**
     * Intenta escribir fecha:
     *  1) Named range "FECHA".
     *  2) Buscar "Fecha:" y poner a su derecha.
     */
    private function writeFecha(Worksheet $sheet, string $fecha): void
    {
        $named = $sheet->getParent()->getNamedRange('FECHA');
        if ($named && $named->getWorksheet()->getCodeName() === $sheet->getCodeName()) {
            $sheet->setCellValue($named->getRange(), $fecha);
            return;
        }

        for ($r = 1; $r <= 50; $r++) {
            for ($c = 1; $c <= 10; $c++) {
                $val = (string)$sheet->getCellByColumnAndRow($c, $r)->getValue();
                if ($val && stripos($val, 'Fecha') !== false) {
                    // Escribir en la celda de al lado (c+1)
                    $sheet->setCellValueByColumnAndRow($c+1, $r, $fecha);
                    return;
                }
            }
        }
        // Si no existe, no pasa nada — la plantilla puede no mostrar fecha.
    }

    /**
     * Encuentra la cabecera de la tabla (N°, Nombre, Identidad, Firma) y devuelve:
     *  [headerRow, colA, colB, colC, colD]
     */
    private function findHeader(Worksheet $sheet): array
    {
        for ($r = 1; $r <= 100; $r++) {
            // Busco en columnas A..H por si la tabla no empieza en A
            for ($c = 1; $c <= 8; $c++) {
                $v1 = (string)$sheet->getCellByColumnAndRow($c, $r)->getValue();
                $v2 = (string)$sheet->getCellByColumnAndRow($c+1, $r)->getValue();
                $v3 = (string)$sheet->getCellByColumnAndRow($c+2, $r)->getValue();
                $v4 = (string)$sheet->getCellByColumnAndRow($c+3, $r)->getValue();

                if ($this->like($v1, ['N°','No','Nº']) &&
                    $this->like($v2, ['Nombre']) &&
                    $this->like($v3, ['Identidad','DNI','Identificación']) &&
                    $this->like($v4, ['Firma']))
                {
                    // Devolver fila y letras de columna
                    $colA = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $colB = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c+1);
                    $colC = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c+2);
                    $colD = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c+3);
                    return [$r, $colA, $colB, $colC, $colD];
                }
            }
        }
        // Si no la encuentra, asumir A..D y cabecera en fila 20 (ajusta si es necesario)
        return [20, 'A','B','C','D'];
    }

    private function like(string $val, array $options): bool
    {
        $val = trim(mb_strtolower($val));
        foreach ($options as $opt) {
            if (mb_strtolower($opt) === $val) return true;
        }
        return false;
    }

    // Devuelve lista de puestos (para el modal)
    public function puestos()
    {
        $puestos = DB::table('puesto_trabajo_matriz')
            ->where('estado', 1)
            ->orderBy('puesto_trabajo_matriz')
            ->get(['id_puesto_trabajo_matriz','puesto_trabajo_matriz']);
        return response()->json($puestos);
    }

    // Lista de empleados activos para el modal de notificación individual
    public function empleados()
    {
        $rows = DB::table('empleado as e')
            ->leftJoin('puesto_trabajo as p', 'p.id_puesto_trabajo', '=', 'e.id_puesto_trabajo')
            ->where('e.estado', 1)
            ->orderBy('e.nombre_completo')
            ->get([
                'e.id_empleado',
                'e.nombre_completo',
                'e.identidad',
                'p.puesto_trabajo',
                'p.departamento',
            ]);

        $out = $rows->map(function($r){
            return [
                'id_empleado'     => $r->id_empleado,
                'nombre_completo' => (string)($r->nombre_completo ?? ''),
                'identidad'       => (string)($r->identidad ?? ''),
                'puesto'          => (string)($r->puesto_trabajo ?? ''),
                'departamento'    => (string)($r->departamento ?? ''),
            ];
        });

        return response()->json($out);
    }

public function exportEmpleado(Request $request)
{
    \Log::info('[Notif Emp] IN', ['all' => $request->all(), 'method' => $request->method()]);

    $request->validate([
        'ptm_id' => 'required|integer|exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz',
    ]);

    $ptmId = (int)$request->input('ptm_id');

    // === PTM
    $ptm = DB::table('puesto_trabajo_matriz')
        ->where('id_puesto_trabajo_matriz', $ptmId)
        ->first(['puesto_trabajo_matriz']);
    if (!$ptm) {
        \Log::warning('[Notif Emp] PTM no encontrado', ['ptm_id' => $ptmId]);
        return back()->with('error', 'Puesto no encontrado');
    }
    $targetName = trim((string)$ptm->puesto_trabajo_matriz);

    // === Empleados activos cuyo PUESTO EFECTIVO coincide con el PTM (por NOMBRE)
    $empleadosPTM = DB::table('empleado as e')
        ->leftJoin('puesto_trabajo_matriz as pm', 'pm.id_puesto_trabajo_matriz', '=', 'e.id_puesto_trabajo_matriz')
        ->leftJoin('puesto_trabajo as ps', 'ps.id_puesto_trabajo', '=', 'e.id_puesto_trabajo')
        ->where('e.estado', 1)
        ->whereRaw(
            'TRIM(UPPER(CASE 
                WHEN e.id_puesto_trabajo_matriz IS NOT NULL AND e.id_puesto_trabajo_matriz <> 0
                    THEN pm.puesto_trabajo_matriz
                ELSE ps.puesto_trabajo
            END)) = TRIM(UPPER(?))',
            [$targetName]
        )
        ->orderBy('e.nombre_completo')
        ->get(['e.nombre_completo']);

    $nombreParaPlantilla = $empleadosPTM->count() === 1 ? (string)($empleadosPTM->first()->nombre_completo ?? '') : '';
    \Log::info('[Notif Emp] Empleados match', [
        'count' => $empleadosPTM->count(),
        'nombreElegido' => $nombreParaPlantilla,
    ]);

    // Departamento (intento inferir desde puesto del sistema si hay alguien en ese PTM)
    $departamento = DB::table('empleado as e')
        ->leftJoin('puesto_trabajo as ps', 'ps.id_puesto_trabajo', '=', 'e.id_puesto_trabajo')
        ->where('e.estado', 1)
        ->whereRaw(
            'TRIM(UPPER(CASE 
                WHEN e.id_puesto_trabajo_matriz IS NOT NULL AND e.id_puesto_trabajo_matriz <> 0
                    THEN (SELECT ptm2.puesto_trabajo_matriz FROM puesto_trabajo_matriz ptm2 WHERE ptm2.id_puesto_trabajo_matriz = e.id_puesto_trabajo_matriz)
                ELSE ps.puesto_trabajo
            END)) = TRIM(UPPER(?))',
            [$targetName]
        )
        ->value('ps.departamento') ?? '';
    \Log::info('[Notif Emp] Dep inferido', ['departamento' => $departamento]);

    // === MEDIDAS por riesgo (filtradas por PTM) - subconsulta con builder
    $siValues = ['si', 'sí', 'si.', 'sí.'];

    $medidasQuery = DB::table('medidas_riesgo_puesto as mrp')
        ->join('riesgo_valor as rv2', function ($join) use ($ptmId) {
            $join->on('rv2.id_riesgo', '=', 'mrp.id_riesgo')
                ->where('rv2.id_puesto_trabajo_matriz', '=', $ptmId);
        })
        ->leftJoin('epp', 'epp.id_epp', '=', 'mrp.id_epp')
        ->leftJoin('capacitacion as c', 'c.id_capacitacion', '=', 'mrp.id_capacitacion')
        ->leftJoin('senalizacion as s', 's.id_senalizacion', '=', 'mrp.id_senalizacion')
        ->leftJoin('otras_medidas as o', 'o.id_otras_medidas', '=', 'mrp.id_otras_medidas')
        ->whereIn(DB::raw('LOWER(TRIM(rv2.valor))'), $siValues)
        ->groupBy('mrp.id_riesgo')
        ->selectRaw(
            "mrp.id_riesgo,
            GROUP_CONCAT(DISTINCT epp.equipo ORDER BY epp.equipo SEPARATOR ', ') AS epps,
            GROUP_CONCAT(DISTINCT c.capacitacion ORDER BY c.capacitacion SEPARATOR ', ') AS caps,
            GROUP_CONCAT(DISTINCT s.senalizacion ORDER BY s.senalizacion SEPARATOR ', ') AS senal,
            GROUP_CONCAT(DISTINCT o.otras_medidas ORDER BY o.otras_medidas SEPARATOR ', ') AS otras"
        );

    // === RIESGOS 'SI' del PTM
    $riesgos = DB::table('riesgo_valor as rv')
        ->join('riesgo as r', 'r.id_riesgo', '=', 'rv.id_riesgo')
        ->join('tipo_riesgo as tr', 'tr.id_tipo_riesgo', '=', 'r.id_tipo_riesgo')
        ->leftJoinSub($medidasQuery, 'm', 'm.id_riesgo', '=', 'r.id_riesgo')
        ->leftJoin('evaluacion_riesgo as er', function ($join) use ($ptmId) {
            $join->on('er.id_riesgo', '=', 'rv.id_riesgo')
                ->where('er.id_puesto_trabajo_matriz', '=', $ptmId);
        })
        ->leftJoin('consecuencia as cons', 'cons.id_consecuencia', '=', 'er.id_consecuencia')
        ->where('rv.id_puesto_trabajo_matriz', $ptmId)
        ->whereIn(DB::raw('LOWER(TRIM(rv.valor))'), $siValues)
        ->orderBy('tr.tipo_riesgo')
        ->orderBy('r.nombre_riesgo')
        ->get([
            'r.nombre_riesgo',
            DB::raw("COALESCE(NULLIF(TRIM(cons.consecuencia), ''), NULLIF(TRIM(r.efecto_salud), ''), '') as efecto_salud"),
            DB::raw("COALESCE(NULLIF(TRIM(CONCAT_WS(' | ', NULLIF(m.epps,''), NULLIF(m.caps,''), NULLIF(m.senal,''), NULLIF(m.otras,''))), ''), 'No Requiere') as medidas_requeridas"),
        ]);

    \Log::info('[Notif Emp] Riesgos encontrados', ['count' => $riesgos->count()]);

    if (method_exists($riesgos, 'unique')) {
        $riesgos = $riesgos->unique(fn($x) => $x->nombre_riesgo)->values();
    }

    // === Plantilla
    $templateRelPath = 'formato_notificacion_riesgos_empleado.xlsx';
    $templatePath = Storage::disk('public')->path($templateRelPath);
    if (!file_exists($templatePath)) {
        \Log::error('[Notif Emp] Plantilla NO existe', ['path' => $templatePath]);
        return back()->with('error', 'No se encontró la plantilla: '.$templateRelPath);
    }

    try {
        $spreadsheet = IOFactory::load($templatePath);
    } catch (\Throwable $e) {
        \Log::error('[Notif Emp] Error abriendo plantilla', ['e' => $e->getMessage()]);
        return back()->with('error', 'No se pudo abrir la plantilla: '.$e->getMessage());
    }

    $sheet = $spreadsheet->getActiveSheet();
    // Escribir celdas
    $sheet->setCellValue('A31', $nombreParaPlantilla);
    $sheet->setCellValue('B7',  (string)$ptm->puesto_trabajo_matriz);
    $sheet->setCellValue('B8',  (string)$departamento);

    // Limpiar áreas (tabla)
    $maxRows = 27;
    for ($r = 10; $r <= $maxRows; $r++) {
        $sheet->setCellValue('A'.$r, '');
        $sheet->setCellValue('C'.$r, '');
    }
    for ($r = 20; $r <= $maxRows; $r++) {
        $sheet->setCellValue('B'.$r, '');
    }

    // Insertar riesgos
    $rowRisk = 10; $rowMed = 10; $rowEffect = 20;
    foreach ($riesgos as $r) {
        if ($rowRisk > $maxRows || $rowMed > $maxRows || $rowEffect > $maxRows) break;
        $sheet->setCellValue('A'.$rowRisk,   (string)($r->nombre_riesgo ?? ''));
        $sheet->setCellValue('C'.$rowMed,    (string)($r->medidas_requeridas ?? ''));
        $rowRisk++; $rowMed++; $rowEffect++;
    }

    // Enviar
    $safeName = preg_replace('/[^\w\-]+/u', '_', (string)$ptm->puesto_trabajo_matriz);
    $filename = 'Notificacion_Riesgos_Empleado_'.$safeName.'_'.date('Ymd_His').'.xlsx';

    // Limpia buffers para no corromper descarga
    while (ob_get_level() > 0) { @ob_end_clean(); }

    \Log::info('[Notif Emp] STREAM OUT', ['filename' => $filename]);

    return response()->streamDownload(function() use ($spreadsheet) {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
    }, $filename, [
        'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
        'Pragma'                    => 'no-cache',
        'Content-Transfer-Encoding' => 'binary',
    ]);
}



}
