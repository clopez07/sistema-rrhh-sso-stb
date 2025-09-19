<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\PuestoTrabajo;
use App\Models\Departamento;
use App\Models\Localizacion;
use App\Models\Area;

class PuestoImportController extends Controller
{
    public function showImportForm()
    {
        return view('puestos.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getSheetByName('PUESTOS_ANALISIS_DE_RIESGOS');

        if (!$worksheet) {
            return back()->with('error', 'No se encontro la hoja "PUESTOS_ANALISIS_DE_RIESGOS".');
        }

        $rows = $worksheet->toArray();

        $procesados = [];
        $creados = 0; $actualizados = 0; $desactivados = 0; $omitidos = 0;

        $val = function ($v) {
            $s = trim((string)($v ?? ''));
            return ($s === '.' ? '' : $s);
        };
        $parseEstado = function ($v) {
            $s = trim(mb_strtolower((string)($v ?? ''), 'UTF-8'));
            if (in_array($s, ['organigrama','2'])) return 2;
            if (in_array($s, ['activo', 'activa', '1', 'si', 'sí'])) return 1;
            if (in_array($s, ['inactivo', 'inactiva', '0', 'no'])) return 0;
            return 1;
        };

        foreach (array_slice($rows, 1) as $row) {
            $puestoNombre       = $val($row[1] ?? '');
            $departamentoNombre = $val($row[2] ?? '');
            $localizacionNombre = $val($row[3] ?? '');
            $areaNombre         = $val($row[4] ?? '');
            $numEmpleados       = (int)($row[5] ?? 0);
            $descripcionGeneral = $val($row[6] ?? '');
            $actividadesDiarias = $val($row[7] ?? '');
            $objetivoPuesto     = $val($row[8] ?? '');
            $estadoExcel        = $parseEstado($row[9] ?? '');

            if (!$puestoNombre || !$departamentoNombre || !$localizacionNombre || !$areaNombre) {
                $omitidos++;
                continue;
            }

            $departamento = Departamento::where('departamento', $departamentoNombre)->first();
            $localizacion = Localizacion::where('localizacion', $localizacionNombre)->first();
            $area         = Area::where('area', $areaNombre)->first();

            if (!$departamento || !$localizacion || !$area) {
                $omitidos++;
                continue;
            }

            $procesados[] = $puestoNombre;

            $puesto = PuestoTrabajo::where('puesto_trabajo_matriz', $puestoNombre)->first();

            $data = [
                'puesto_trabajo_matriz' => $puestoNombre,
                'id_departamento'       => $departamento->id_departamento,
                'id_localizacion'       => $localizacion->id_localizacion,
                'id_area'               => $area->id_area,
                'num_empleados'         => $numEmpleados,
                'descripcion_general'   => $descripcionGeneral,
                'actividades_diarias'   => $actividadesDiarias,
                'objetivo_puesto'       => $objetivoPuesto,
                'estado'                => $estadoExcel,
            ];

            if ($puesto) {
                $puesto->fill($data);
                if ($puesto->isDirty()) { $puesto->save(); $actualizados++; }
            } else {
                PuestoTrabajo::create($data);
                $creados++;
            }
        }

        if (!empty($procesados)) {
            $desactivados = PuestoTrabajo::whereNotIn('puesto_trabajo_matriz', $procesados)
                ->update(['estado' => 0]);
        }

        return back()->with('success', "Importacion completada. Creados: $creados, Actualizados: $actualizados, Desactivados: $desactivados, Omitidos: $omitidos.");
    }
}
