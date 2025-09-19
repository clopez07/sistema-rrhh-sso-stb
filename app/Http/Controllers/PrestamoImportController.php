<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Prestamo;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PrestamoImportController extends Controller
{
    public function showImportForm()
    {
        return view('prestamos.import');
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

        $worksheet = $spreadsheet->getSheetByName('PRESTAMOS') ?? $spreadsheet->getSheetByName('Prestamos');

        if (!$worksheet) {
            return back()->with('error', 'No se encontró la hoja "PRESTAMOS/Prestamos".');
        }

        $rows = $worksheet->toArray();
        $numPrestamosExcel = [];

        DB::transaction(function () use ($rows, &$numPrestamosExcel) {

            foreach (array_slice($rows, 1) as $row) {
                $numPrestamo           = trim($row[0] ?? '');
                $codigoEmpleado        = trim($row[1] ?? '');
                $monto                 = trim($row[3] ?? 0);
                $cuotaCapital          = trim($row[4] ?? 0);
                $porcentajeInteres     = trim($row[5] ?? null);
                $totalIntereses        = trim($row[6] ?? 0);
                $cobroExtraordinario = trim($row[7] ?? null);
                $cobroExtraordinario = $cobroExtraordinario === '' ? null : floatval($cobroExtraordinario);

                $causa                 = trim($row[8] ?? null);
                $plazoMeses            = trim($row[9] ?? 0);
                $fechaDeposito         = trim($row[10] ?? null);
                $fechaPrimeraCuota     = trim($row[11] ?? null);
       
                if ($fechaDeposito !== '' && is_numeric($fechaDeposito)) {
                    $fechaDeposito = ExcelDate::excelToDateTimeObject($fechaDeposito)->format('Y-m-d');
                } else {
                    $fechaDeposito = null;
                }

                if ($fechaPrimeraCuota !== '' && is_numeric($fechaPrimeraCuota)) {
                    $fechaPrimeraCuota = ExcelDate::excelToDateTimeObject($fechaPrimeraCuota)->format('Y-m-d');
                } else {
                    $fechaPrimeraCuota = null;
                }

                $codigoPlanilla = trim($row[12] ?? null);
                $idPlanilla = null;

                if ($codigoPlanilla) {
                    $planilla = DB::table('planilla')->where('planilla', $codigoPlanilla)->first();
                    if ($planilla) {
                        $idPlanilla = $planilla->id_planilla;
                    }
                }
                $observaciones         = trim($row[13] ?? null);

                if (!$numPrestamo || !$codigoEmpleado) {
                    continue;
                }

                $numPrestamosExcel[] = $numPrestamo;

                $empleado = Empleado::where('codigo_empleado', $codigoEmpleado)->first();
                if (!$empleado) {
                    continue;
                }

                $estadoPrestamo = 1;

                $prestamo = Prestamo::where('num_prestamo', $numPrestamo)->first();

                if (!$prestamo) {
                    Prestamo::create([
                        'num_prestamo'           => $numPrestamo,
                        'id_empleado'            => $empleado->id_empleado,
                        'monto'                  => $monto,
                        'cuota_capital'          => $cuotaCapital,
                        'porcentaje_interes'     => $porcentajeInteres,
                        'total_intereses'        => $totalIntereses,
                        'cobro_extraordinario'   => $cobroExtraordinario,
                        'causa'                  => $causa,
                        'plazo_meses'            => $plazoMeses,
                        'fecha_deposito_prestamo'=> $fechaDeposito,
                        'fecha_primera_cuota'    => $fechaPrimeraCuota,
                        'id_planilla'            => $idPlanilla,
                        'estado_prestamo'        => $estadoPrestamo,
                        'observaciones'          => $observaciones,
                    ]);
                }
            }

            $numPrestamosExcel = array_values(array_unique(array_filter($numPrestamosExcel)));
            if (count($numPrestamosExcel) > 0) {
                Prestamo::whereNotIn('num_prestamo', $numPrestamosExcel)
                        ->update(['estado_prestamo' => 0]);
            }
        });
        return back()->with('success', 'Préstamos importados correctamente.');
    }
}