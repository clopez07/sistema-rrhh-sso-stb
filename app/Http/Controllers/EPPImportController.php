<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Empleado;
use App\Models\EntregaEPP;
use App\Models\EPP;
use Illuminate\Support\Facades\DB;

class EPPImportController extends Controller
{
    public function showImportForm()
    {
        return view('controlentrega.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getSheetByName('EPP');

        if (!$worksheet) {
            return back()->with('error', 'No se encontró la hoja "EPP".');
        }

        $rows = $worksheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            $fecha_excel = trim($row[0] ?? '');
            $nombre_empleado_excel = trim($row[2] ?? '');
            $materiales_excel = trim($row[6] ?? '');

            if (!$fecha_excel || !$nombre_empleado_excel || !$materiales_excel) {
                continue;
            }

            $empleado = Empleado::where('nombre_completo', $nombre_empleado_excel)->first();
            if (!$empleado) {
                continue;
            }

            $lista_materiales = explode(',', $materiales_excel);

            foreach ($lista_materiales as $material) {
                $material = trim($material);
                if (!$material) {
                    continue;
                }

                $epp = EPP::where('equipo', $material)->first();
                if (!$epp) {
                    continue;
                }

                EntregaEPP::firstOrCreate([
                    'id_empleado' => $empleado->id_empleado,
                    'id_epp' => $epp->id_epp,
                    'fecha_entrega_epp' => $fecha_excel,
                ]);
            }
        }

        return back()->with('success', 'Datos importados correctamente.');
    }
}