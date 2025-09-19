<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\CapacitacionInstructor;
use App\Models\Capacitacion;
use App\Models\AsignacionesCapacitacion;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class AsistenciaImportController extends Controller
{
    public function showImportForm()
    {
        return view('asistencia.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getSheetByName('CAPACITACIONES_MASIVAS');

        if (!$worksheet) {
            return back()->with('error', 'No se encontró la hoja "CAPACITACIONES_MASIVAS".');
        }

        $rows = $worksheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            $nombre_empleado_excel = trim($row[1] ?? '');
            $nombre_capacitacion = trim($row[2] ?? '');
            $nombre_instructor_excel = trim($row[3] ?? '');
            $fecha_recibida = trim($row[4] ?? '');

            if (!$nombre_empleado_excel || !$nombre_capacitacion || !$nombre_instructor_excel || !$fecha_recibida) {
                continue;
            }

            $empleado = Empleado::where('nombre_completo', $nombre_empleado_excel)->first();
            if (!$empleado) {
                continue;
            }

            $capacitacion = Capacitacion::where('capacitacion', $nombre_capacitacion)->first();
            if (!$capacitacion) {
                continue;
            }

            $capacitacionInstructor = CapacitacionInstructor::where('id_capacitacion', $capacitacion->id_capacitacion)
                ->with('instructor')
                ->first();

            if (!$capacitacionInstructor) {
                continue;
            }

            $instructor_temporal = null;
            if (strtolower(trim($capacitacionInstructor->instructor->instructor)) !== strtolower($nombre_instructor_excel)) {
                $instructor_temporal = $nombre_instructor_excel;
            }

            $existe = AsignacionesCapacitacion::where('id_empleado', $empleado->id_empleado)
                ->where('id_capacitacion_instructor', $capacitacionInstructor->id_capacitacion_instructor)
                ->where('fecha_recibida', $fecha_recibida)
                ->exists();

            if (!$existe) {
                AsignacionesCapacitacion::create([
                    'id_empleado' => $empleado->id_empleado,
                    'id_capacitacion_instructor' => $capacitacionInstructor->id_capacitacion_instructor,
                    'instructor_temporal' => $instructor_temporal,
                    'fecha_recibida' => $fecha_recibida,
                ]);
            }
        }

        return back()->with('success', 'Datos importados correctamente.');
    }
}