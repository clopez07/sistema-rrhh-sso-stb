<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AsignacionEPP;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EPP extends Controller
{
    public function equipo(Request $request)
    {
        $equipo = DB::table('epp as e')
        ->join('tipo_proteccion as tp', 'e.id_tipo_proteccion', '=', 'tp.id_tipo_proteccion')
        ->select(
            'e.id_epp',
            'e.equipo',
            'e.codigo',
            'e.marca',
            'tp.id_tipo_proteccion',
            'tp.tipo_proteccion'
        )
        ->when($request->search, function ($query, $search) {
            return $query->where('e.equipo', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        $tipoproteccion = DB::select('CALL sp_obtener_tipo_proteccion()');

        return view('epp.equipo', compact('equipo', 'tipoproteccion'));
    }

    public function storeequipo(Request $request)
    {
        DB::table('epp')->insert([
            'equipo' => $request->input('equipo'),
            'codigo' => $request->input('codigo'),
            'marca' => $request->input('marca'),
            'id_tipo_proteccion' => $request->input('id_tipo_proteccion'),
        ]);

        return redirect()->back()->with('success', 'Agregado correctamente');
    }

        public function updateequipo(Request $request, $id)
    {
        DB::table('epp')
            ->where('id_epp', $id)
            ->update([
                'equipo' => $request->input('equipo'),
                'codigo' => $request->input('codigo'),
                'marca' => $request->input('marca'),
                'id_tipo_proteccion' => $request->input('id_tipo_proteccion'),
            ]);

        return redirect()->back()->with('success', 'capacitacion actualizado correctamente');
    }

        public function destroyequipo($id)
    {
        DB::table('epp')->where('id_epp', $id)->delete();
        return redirect()->back()->with('success', 'eliminado correctamente');
    }

    public function tipoproteccion(Request $request)
    {
        $tipoproteccion = DB::table('tipo_proteccion as tp')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('tp.tipo_proteccion', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);

        return view('epp.tipoproteccion', compact('tipoproteccion'));
    }

    public function storetipo(Request $request)
    {
        $request->validate([
            'tipo_proteccion' => 'required|string|max:50',
        ]);

        DB::table('tipo_proteccion')->insert([
            'tipo_proteccion' => $request->input('tipo_proteccion'),
        ]);

        return redirect()->back()->with('success', 'registrado correctamente.');
    }

    public function updatetipo(Request $request, $id)
    {
        $request->validate([
            'tipo_proteccion' => 'required|string|max:500',
        ]);

        DB::table('tipo_proteccion')
            ->where('id_tipo_proteccion', $id)
            ->update([
                'tipo_proteccion' => $request->input('tipo_proteccion'),
            ]);

        return redirect()->back()->with('success', 'actualizado correctamente');
    }

    public function destroytipo($id)
    {
        DB::table('tipo_proteccion')->where('id_tipo_proteccion', $id)->delete();
        return redirect()->back()->with('success', 'eliminado correctamente');
    }

        public function controlentrega(Request $request)
    {
        $controlentrega = DB::table('asignacion_epp as ae')
        ->join('epp as ep', 'ae.id_epp', '=', 'ep.id_epp')
        ->join('empleado as em', 'ae.id_empleado', '=', 'em.id_empleado')
        ->join('puesto_trabajo as pt', 'em.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
        ->select(
            'ae.id_asignacion_epp',
            'em.nombre_completo',
            'pt.puesto_trabajo',
            'pt.departamento',
            'ep.equipo',
            'ae.fecha_entrega_epp'
        )
        ->when($request->search, function ($query, $search) {
            return $query->where('em.nombre_completo', 'like', "%{$search}%")
                        ->orWhere('pt.puesto_trabajo', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        $equipo = DB::select('CALL sp_obtener_epp()');
        $empleados = DB::select('CALL sp_obtener_empleados()');
        $puestos = DB::select('CALL sp_obtener_puestos_trabajo_sistema()');

        return view('epp.controlentrega', compact('controlentrega', 'equipo', 'empleados', 'puestos'));
    }

public function store(Request $request)
{
    $request->validate([
        'empleados' => 'required|array',
        'epp'       => 'required|array',
        'fecha'     => 'required',
    ]);

    // ── IDs únicos
    $empleadoIds = array_values(array_unique(array_map('intval', $request->empleados)));
    $eppIds      = array_values(array_unique(array_map('intval', $request->epp)));

    // ── Normalizar fecha a ISO (YYYY-MM-DD) tolerando varios formatos
    $rawFecha = trim((string)$request->fecha);
    $rawFecha = str_replace(['(', ')'], '', $rawFecha);
    $fechaISO = null;
    foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $fmt) {
        if (Carbon::hasFormat($rawFecha, $fmt)) {
            try { $fechaISO = Carbon::createFromFormat($fmt, $rawFecha)->format('Y-m-d'); break; } catch (\Exception $e) {}
        }
    }
    if (!$fechaISO) {
        try { $fechaISO = Carbon::parse($rawFecha)->format('Y-m-d'); }
        catch (\Exception $e) {
            return back()->withErrors(['fecha' => 'Formato de fecha inválido. Usa dd/mm/aaaa o yyyy-mm-dd'])->withInput();
        }
    }
    $fechaVista  = Carbon::createFromFormat('Y-m-d', $fechaISO)->format('d/m/Y');

    // ── 1) Insertar asignaciones evitando duplicados (empleado+epp+fecha)
    // Buscar combinaciones existentes para esa fecha
    $existentes = DB::table('asignacion_epp')
        ->whereDate('fecha_entrega_epp', $fechaISO)
        ->whereIn('id_empleado', $empleadoIds)
        ->whereIn('id_epp', $eppIds)
        ->select('id_empleado', 'id_epp')
        ->get()
        ->map(fn($r) => $r->id_empleado.'|'.$r->id_epp)
        ->all();
    $ya = array_flip($existentes); // set para búsqueda O(1)

    $rows = [];
    foreach ($empleadoIds as $idEmpleado) {
        foreach ($eppIds as $idEpp) {
            $k = $idEmpleado.'|'.$idEpp;
            if (!isset($ya[$k])) {
                $rows[] = [
                    'id_empleado'       => $idEmpleado,
                    'id_epp'            => $idEpp,
                    'fecha_entrega_epp' => $fechaISO,
                ];
            }
        }
    }

    $insertados = 0;
    if (!empty($rows)) {
        DB::table('asignacion_epp')->insert($rows);
        $insertados = count($rows);
    }
    $totalSolicitados = count($empleadoIds) * count($eppIds);
    $omitidos = max(0, $totalSolicitados - $insertados);
    // Guardar mensaje para mostrarse en /controlentrega
    session()->flash('success', "Insertados: $insertados. Omitidos por duplicado: $omitidos.");

    // ── 2) Consultar filas recién insertadas (incluye id_epp)
    $rows = DB::table('asignacion_epp as aepp')
        ->join('empleado as emp', 'aepp.id_empleado', '=', 'emp.id_empleado')
        ->join('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
        ->join('epp as epp', 'aepp.id_epp', '=', 'epp.id_epp')
        ->leftJoin('tipo_proteccion as tp', 'epp.id_tipo_proteccion', '=', 'tp.id_tipo_proteccion')
        ->whereIn('aepp.id_empleado', $empleadoIds)
        ->whereIn('aepp.id_epp', $eppIds)
        ->whereDate('aepp.fecha_entrega_epp', $fechaISO)
        ->select(
            'emp.id_empleado',
            'emp.nombre_completo',
            'pt.puesto_trabajo',
            'pt.departamento',
            'epp.id_epp',                     // ← importante para buscar entrega previa
            'epp.equipo as epp',
            'tp.tipo_proteccion as tipo_proteccion',
            'aepp.fecha_entrega_epp as fecha'
        )
        ->orderBy('pt.puesto_trabajo')
        ->orderBy('emp.nombre_completo')
        ->orderBy('aepp.fecha_entrega_epp')
        ->get();

    // ── 3) Separar cartuchos especiales
    $norm = fn($s) => Str::of($s ?? '')->lower()->ascii()->squish();
    $targets = collect([
        'Filtro para amoniaco 757-N ABEK1 para mascarilla Climax',
        'Filtro para vapor mascarilla PETRUL',
        'Filtro para vapor mascarilla 3M',
    ])->map($norm);

    $esCartucho = function ($nombre) use ($norm, $targets) {
        return $targets->contains($norm($nombre));
    };

    $rowsCartuchos = $rows->filter(fn($r) => $esCartucho($r->epp))->values();
    $rowsNormales  = $rows->reject(fn($r) => $esCartucho($r->epp))->values();

    // ── 4) INDIVIDUAL y GRUPAL usando SOLO filas normales
    $empleados = $rowsNormales->groupBy('id_empleado')->map(function ($g) {
        return (object)[
            'id_empleado'     => $g->first()->id_empleado,
            'nombre_completo' => $g->first()->nombre_completo,
            'puesto_trabajo'  => $g->first()->puesto_trabajo,
            'departamento'    => $g->first()->departamento,
            // Evitar repetir EPPs si ya existían para la misma fecha
            'epp_lista'       => $g->unique('id_epp')->pluck('epp')->values()->implode(', '),
        ];
    })->values();

    $porPuesto = [];
    $departamentosPorPuesto = [];
    foreach ($rowsNormales->groupBy('puesto_trabajo') as $puestoNombre => $gPuesto) {
        $departamentosPorPuesto[$puestoNombre] = $gPuesto->first()->departamento;

        $porPuesto[$puestoNombre] = $gPuesto->groupBy('id_empleado')->map(function ($gEmp) use ($fechaVista) {
            $epps   = $gEmp->unique('id_epp')->pluck('epp')->values();
            $tipos  = $gEmp->pluck('tipo_proteccion')->filter()->unique()->values();
            $cant   = $epps->count() > 1 ? '1 C/U' : '1';

            return (object)[
                'nombre_completo' => $gEmp->first()->nombre_completo,
                'epp_lista'       => $epps->implode(', '),
                'tipo_lista'      => $tipos->implode(', '),
                'cantidad'        => $cant,
                'fecha'           => $gEmp->first()->fecha ? Carbon::parse($gEmp->first()->fecha)->format('d/m/Y') : $fechaVista,
                'departamento'    => $gEmp->first()->departamento,
            ];
        })->values();
    }

    // ── 5) Fechas previas por (empleado, epp) ANTES de la fecha actual
    $prevFechas = DB::table('asignacion_epp as a')
        ->whereIn('a.id_empleado', $empleadoIds)
        ->whereIn('a.id_epp', $eppIds)
        ->where('a.fecha_entrega_epp', '<', $fechaISO)
        ->select('a.id_empleado', 'a.id_epp', DB::raw('MAX(a.fecha_entrega_epp) as fecha_prev'))
        ->groupBy('a.id_empleado', 'a.id_epp')
        ->get()
        ->keyBy(fn($r) => $r->id_empleado.'|'.$r->id_epp);

    // Helper para formatear “X meses Y días”
    $fmtMesesDias = function (?Carbon $desde, Carbon $hasta) {
        if (!$desde) return '';
        $iv   = $desde->diff($hasta);           // DateInterval
        $mes  = $iv->y * 12 + $iv->m;           // pasar años a meses
        $dia  = $iv->d;

        $parts = [];
        if ($mes > 0)  { $parts[] = $mes.' '.($mes === 1 ? 'mes'  : 'meses'); }
        if ($dia > 0 || empty($parts)) { $parts[] = $dia.' '.($dia === 1 ? 'día' : 'días'); }
        return implode(' ', $parts);
    };

    // ── 6) CARTUCHOS: dataset para el nuevo formato
    $cartuchos = $rowsCartuchos->map(function ($r) use ($prevFechas, $fechaISO, $fmtMesesDias) {
        $key          = $r->id_empleado.'|'.$r->id_epp;
        $prev         = $prevFechas[$key]->fecha_prev ?? null;

        $fechaEntrega = $prev ? Carbon::parse($prev) : null;         // Fecha de Entrega (anterior)
        $fechaCambio  = Carbon::parse($fechaISO);                     // Fecha de Cambio (actual)
        $tiempo       = $fechaEntrega ? $fmtMesesDias($fechaEntrega, $fechaCambio) : '';

        return (object)[
            'tipo'           => $r->epp,
            'fecha_entrega'  => $fechaEntrega ? $fechaEntrega->format('d/m/Y') : '',
            'tiempo'         => $tiempo,                               // «X meses Y días»
            'fecha_cambio'   => $fechaCambio->format('d/m/Y'),
            'area'           => $r->departamento ?: $r->puesto_trabajo,
            'observaciones'  => '',
            'empleado'       => $r->nombre_completo,
        ];
    })->values();

    // ── 7) Render
    return view('epp.print', [
        'empleados'              => $empleados,
        'porPuesto'              => $porPuesto,
        'departamentosPorPuesto' => $departamentosPorPuesto,
        'fecha'                  => $fechaISO,    // ISO para que Carbon::parse en la vista no falle
        'cartuchos'              => $cartuchos,
    ]);
}
    private function fechaLargaEs(string $ymd): string
    {
        $dt = Carbon::parse($ymd);
        $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        return $dt->format('j') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
    }

    public function destroy($id)
    {
        DB::table('asignacion_epp')->where('id_asignacion_epp', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    public function consulta(Request $request)
    {
        $nombre = $request->input('nombre');
        $puesto = $request->input('puesto');
        $fecha = $request->input('fecha');
        $equipo = $request->input('equipo');

        $query = DB::table('asignacion_epp as aepp')
            ->join('empleado as emp', 'aepp.id_empleado', '=', 'emp.id_empleado')
            ->join('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->join('epp as epp', 'aepp.id_epp', '=', 'epp.id_epp')
            ->select(
                'emp.nombre_completo',
                'pt.puesto_trabajo',
                'pt.departamento',
                'epp.equipo as epp',
                'aepp.fecha_entrega_epp'
            );

        if ($nombre) {
            $query->where('emp.nombre_completo', $nombre);
        }

        if ($puesto) {
            $query->where('pt.puesto_trabajo', $puesto);
        }

        if ($fecha) {
            $query->where('aepp.fecha_entrega_epp', $fecha);
        }
        
        if ($equipo) {
            $query->where('epp.equipo', $equipo);
        }

        $equipo = $query->get();

        $empleadosConEpp = DB::table('asignacion_epp as aepp')
        ->join('empleado as emp', 'aepp.id_empleado', '=', 'emp.id_empleado')
        ->select(
            'emp.id_empleado',
            'emp.nombre_completo'
        )
        ->distinct()
        ->get();
        $puestos = DB::table('puesto_trabajo')->pluck('puesto_trabajo');
        $fechas = DB::table('asignacion_epp')->distinct()->pluck('fecha_entrega_epp');
        $equipos = DB::table('epp')->pluck('equipo');

        return view('epp.consultas', compact('equipo', 'puestos', 'fechas', 'equipos', 'empleadosConEpp'));
    }

    // Exporta a Excel los resultados filtrados en /consultas (EPP)
public function imprimirConsultas(Request $request)
{
    $nombre = $request->input('nombre');
    $puesto = $request->input('puesto');
    $fecha  = $request->input('fecha');
    $equipo = $request->input('equipo');

    $query = DB::table('asignacion_epp as aepp')
        ->join('empleado as emp', 'aepp.id_empleado', '=', 'emp.id_empleado')
        ->join('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
        ->join('epp as epp', 'aepp.id_epp', '=', 'epp.id_epp')
        ->select(
            'emp.nombre_completo',
            'pt.puesto_trabajo',
            'pt.departamento',
            'epp.equipo as epp',
            'aepp.fecha_entrega_epp'
        );

    if ($nombre) $query->where('emp.nombre_completo', $nombre);
    if ($puesto) $query->where('pt.puesto_trabajo', $puesto);
    if ($fecha)  $query->where('aepp.fecha_entrega_epp', $fecha);
    if ($equipo) $query->where('epp.equipo', $equipo);

    $resultados = $query->orderBy('pt.puesto_trabajo')
                        ->orderBy('emp.nombre_completo')
                        ->orderBy('aepp.fecha_entrega_epp')
                        ->get();

    // ===== Excel =====
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Consultas EPP');

    // Logo
    foreach ([public_path('img/logo.PNG'), public_path('img/logo.png'), public_path('logo.png'), public_path('logo.jpg')] as $logoPath) {
        if (is_file($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(60);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
            break;
        }
    }

    // Encabezado
    $empresa = 'SERVICE AND TRIDING BUSINESS';
    $descripcion = 'LISTADO DE BUSQUEDA DE EPP';

    $sheet->mergeCells('B1:F2');
    $sheet->setCellValue('B1', $empresa);
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('B3:F3');
    $sheet->setCellValue('B3', $descripcion);
    $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

    // Encabezados
    $headerRow = 5;
    $headers = ['Nombre Completo','Puesto de Trabajo','Departamento','EPP','Fecha'];
    foreach ($headers as $i => $h) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
        $sheet->setCellValue($col.$headerRow, $h);
    }
    $headerRange = "A{$headerRow}:E{$headerRow}";
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
    $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Datos
    $row = $headerRow + 1;
    foreach ($resultados as $item) {
        $sheet->setCellValue("A{$row}", $item->nombre_completo);
        $sheet->setCellValue("B{$row}", $item->puesto_trabajo);
        $sheet->setCellValue("C{$row}", $item->departamento);
        $sheet->setCellValue("D{$row}", $item->epp);

        // Guardar fecha como fecha Excel
        if (!empty($item->fecha_entrega_epp)) {
            try {
                $dt = new \DateTime($item->fecha_entrega_epp);
                $sheet->setCellValue("E{$row}", ExcelDate::PHPToExcel($dt));
            } catch (\Throwable $e) {
                $sheet->setCellValue("E{$row}", $item->fecha_entrega_epp);
            }
        } else {
            $sheet->setCellValue("E{$row}", '');
        }

        $row++;
    }

    // Bordes y formato de fecha
    $lastRow = $row - 1;
    if ($lastRow >= $headerRow) {
        $sheet->getStyle("A{$headerRow}:E{$lastRow}")
              ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
              ->getColor()->setARGB('FF9CA3AF');

        $sheet->getStyle("E".($headerRow+1).":E{$lastRow}")
              ->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    }

    // Auto ancho
    foreach (range('A','E') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

    // ===== Descarga robusta (sin streamDownload) =====
    $filename = 'consultas_epp_'.date('Ymd_His').'.xlsx';
    $writer   = new Xlsx($spreadsheet);

    // Limpia buffers/compresión para evitar bytes extra
    if (function_exists('ini_set')) { @ini_set('zlib.output_compression', 'Off'); }
    while (ob_get_level() > 0) { @ob_end_clean(); }

    // Escribe a archivo temporal y descárgalo
    $tmp = tempnam(sys_get_temp_dir(), 'epp_');
    $writer->save($tmp);

    return response()->download($tmp, $filename, [
            'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'                    => 'public',
            'Content-Transfer-Encoding' => 'binary',
        ])->deleteFileAfterSend(true);
}


    public function imprimir(Request $request)
    {
        $nombre = $request->input('nombre');
        $puesto = $request->input('puesto');
        $fecha  = $request->input('fecha');
        $equipo = $request->input('equipo');

        $q = DB::table('asignacion_epp as aepp')
            ->join('empleado as emp', 'aepp.id_empleado', '=', 'emp.id_empleado')
            ->join('puesto_trabajo as pt', 'emp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->join('epp as epp', 'aepp.id_epp', '=', 'epp.id_epp')
            ->leftJoin('tipo_proteccion as tp', 'epp.id_tipo_proteccion', '=', 'tp.id_tipo_proteccion')
            ->select(
                'emp.id_empleado',
                'emp.nombre_completo',
                'pt.puesto_trabajo',
                'pt.departamento',
                'epp.equipo as epp',
                'tp.tipo_proteccion as tipo_proteccion',
                'aepp.fecha_entrega_epp as fecha'
            );

        if ($nombre) $q->where('emp.nombre_completo', $nombre);
        if ($puesto) $q->where('pt.puesto_trabajo', $puesto);
        if ($fecha)  $q->whereDate('aepp.fecha_entrega_epp', $fecha);
        if ($equipo) $q->where('epp.equipo', $equipo);

        $rows = $q->orderBy('pt.puesto_trabajo')
                ->orderBy('emp.nombre_completo')
                ->orderBy('aepp.fecha_entrega_epp')
                ->get();

        if ($rows->isEmpty()) {
            return back()->with('error', 'No se encontraron registros.');
        }

        $fechaImpresion = $fecha ?: now()->toDateString();

        $empleados = $rows->groupBy('id_empleado')->map(function ($g) {
            return (object)[
                'id_empleado'     => $g->first()->id_empleado,
                'nombre_completo' => $g->first()->nombre_completo,
                'puesto_trabajo'  => $g->first()->puesto_trabajo,
                'departamento'    => $g->first()->departamento,
                'epp_lista'       => $g->pluck('epp')->values()->implode(', '),
            ];
        })->values();

        $porPuesto = [];
        $departamentosPorPuesto = [];

        foreach ($rows->groupBy('puesto_trabajo') as $puestoNombre => $gPuesto) {
            $departamentosPorPuesto[$puestoNombre] = $gPuesto->first()->departamento;

            $porPuesto[$puestoNombre] = $gPuesto->groupBy('id_empleado')->map(function ($gEmp) use ($fechaImpresion) {
                $epps   = $gEmp->pluck('epp')->values();
                $tipos  = $gEmp->pluck('tipo_proteccion')->filter()->unique()->values();
                $cantidad = $epps->count() > 1 ? '1 C/U' : '1';

                return (object)[
                    'nombre_completo' => $gEmp->first()->nombre_completo,
                    'epp_lista'       => $epps->implode(', '),
                    'tipo_lista'      => $tipos->implode(', '),
                    'cantidad'        => $cantidad,
                    'fecha'           => $gEmp->first()->fecha ?: $fechaImpresion,
                    'departamento'    => $gEmp->first()->departamento,
                ];
            })->values();
        }

        return view('epp.imprimir', [
            'empleados'              => $empleados,
            'porPuesto'              => $porPuesto,
            'departamentosPorPuesto' => $departamentosPorPuesto,
            'fecha'                  => $fechaImpresion,
        ]);
    }

     public function exportFormatoEpp(Request $request)
    {
        $idPuesto = $request->get('puesto');

        $datos = DB::table('asignacion_epp as ae')
            ->join('epp as ep', 'ae.id_epp', '=', 'ep.id_epp')
            ->join('tipo_proteccion as tp', 'ep.id_tipo_proteccion', '=', 'tp.id_tipo_proteccion')
            ->join('empleado as em', 'ae.id_empleado', '=', 'em.id_empleado')
            ->join('puesto_trabajo as pt', 'em.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->select(
                DB::raw('nombre_completo'),
                'pt.puesto_trabajo',
                'pt.departamento',
                'ep.equipo',
                'tp.tipo_proteccion',
                'ae.fecha_entrega_epp'
            )
            ->where('pt.id_puesto_trabajo', $idPuesto)
            ->orderBy('ae.fecha_entrega_epp')
            ->get();

        if ($datos->isEmpty()) {
            return back()->with('error', 'No hay datos para este puesto.');
        }

        $spreadsheet = IOFactory::load(storage_path('app/public/formato epp.xlsx'));
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('C6', $datos[0]->puesto_trabajo);
        $sheet->setCellValue('F7', $datos[0]->departamento);

        $totalEmpleadosPorPuesto = DB::table('empleado')
            ->where('id_puesto_trabajo', $idPuesto)
            ->count();

        $sheet->setCellValue('C7', $totalEmpleadosPorPuesto);

        $fila = 10;
        foreach ($datos->groupBy('nombre_completo') as $empleado => $registros) {
            $equipos = $registros->pluck('equipo')->join(', ');
            $tiposProteccion = $registros->pluck('tipo_proteccion')->unique()->join(', ');
            $cantidad = $registros->count() > 1 ? '1CU' : '1';

            $sheet->setCellValue("B{$fila}", $equipos);
            $sheet->setCellValue("C{$fila}", $tiposProteccion);
            $sheet->setCellValue("D{$fila}", $cantidad);
            $sheet->setCellValue("E{$fila}", $registros[0]->fecha_entrega_epp);
            $sheet->setCellValue("F{$fila}", $empleado);
            $fila++;
        }

        $fileName = 'Formato_EPP_' . str_replace(' ', '_', $datos[0]->puesto_trabajo) . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/public/' . $fileName);
        $writer->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
