<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\PuestosSistema;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class EmpleadoImportController extends Controller
{
    public function showImportForm()
    {
        return view('empleados.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');

        $reader = IOFactory::createReaderForFile($file->getPathname());
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getPathname());

        $worksheet = $spreadsheet->getSheetByName('EMPLEADOS') ?? $spreadsheet->getSheetByName('Empleados');

        if (!$worksheet) {
            return back()->with('error', 'No se encontró la hoja "EMPLEADOS/Empleados".');
        }

        $rows = $worksheet->toArray();
        $codigosExcel = [];

        DB::transaction(function () use ($rows, &$codigosExcel) {

            foreach (array_slice($rows, 1) as $row) {
                $nombreCompleto      = trim($row[1] ?? '');
                $identidad           = trim($row[2] ?? '');
                $codigoEmpleado      = trim($row[3] ?? '');
                $puestoNombre        = trim($row[4] ?? '');
                $departamentoNombre  = trim($row[5] ?? '');
                $estadoEmpleadoRaw   = trim($row[6] ?? '');

                if ($codigoEmpleado !== '') {
                    $codigosExcel[] = $codigoEmpleado;
                }

                if (!$nombreCompleto || !$identidad || !$codigoEmpleado || !$puestoNombre) {
                    continue;
                }

                $estado = 1;
                if ($estadoEmpleadoRaw !== '') {
                    $v = mb_strtolower($estadoEmpleadoRaw, 'UTF-8');
                    if (in_array($v, ['si','sí','1','yes','y','activo','activa'], true)) {
                        $estado = 1;
                    } elseif (in_array($v, ['no','0','n','inactive','inactivo','inactiva'], true)) {
                        $estado = 0;
                    }
                }

                $departamentoId = $departamentoNombre !== '' ? $departamentoNombre : null;

                $puesto = PuestosSistema::firstOrCreate(
                    [
                        'puesto_trabajo' => $puestoNombre,
                        'departamento'   => $departamentoId,
                    ],
                    [
                        'num_empleados' => 0,
                        'estado'        => 1,
                    ]
                );

                $empleado = Empleado::where('codigo_empleado', $codigoEmpleado)->first();

                if (!$empleado) {
                    Empleado::create([
                        'nombre_completo'   => $nombreCompleto,
                        'identidad'         => $identidad,
                        'codigo_empleado'   => $codigoEmpleado,
                        'id_puesto_trabajo' => $puesto->id_puesto_trabajo,
                        'estado'            => $estado,
                    ]);
                } else {
                    if ($empleado->estado != $estado) {
                        $empleado->update(['estado' => $estado]);
                    }

                    if ((int)$estado === 1 && (int)$empleado->id_puesto_trabajo !== (int)$puesto->id_puesto_trabajo) {
                        $empleado->update(['id_puesto_trabajo' => $puesto->id_puesto_trabajo]);
                    }
                }
            }

            $codigosExcel = array_values(array_unique(array_filter($codigosExcel)));
            if (count($codigosExcel) > 0) {
                Empleado::whereNotIn('codigo_empleado', $codigosExcel)->update(['estado' => 0]);
            }
        });

        DB::statement('CALL actualizar_estado_puestos()');

        return back()->with('success', 'Empleados importados correctamente.');
    }
}
