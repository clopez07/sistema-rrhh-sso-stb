<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DetallesRiesgoImportController extends Controller
{
    private const SHEET_NAME = 'DATOS_FORMATOS_MATRIZ';
    private const PUESTO_HEADER_NEEDLE = 'puesto de trabajo analizado';

    public function showImportForm()
    {
        return view('detalles_riesgo.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm,csv',
        ]);

        $file = $request->file('excel_file');

        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME) ?? $spreadsheet->getSheet(0);

        $rows = $sheet->toArray(null, true, true, true);
        if (empty($rows)) {
            return back()->withErrors(['excel_file' => 'La hoja está vacía.']);
        }

        $headerRow = $rows[1] ?? [];
        $normalize = function (?string $s) {
            $s = (string) $s;
            $s = preg_replace('/\s+/u', ' ', trim(str_replace(["\r", "\n"], ' ', $s)));
            return $s;
        };

        $puestoCol = null;
        $headers = [];
        $seen = [];
        foreach ($headerRow as $colLetter => $rawHeader) {
            $h = $normalize($rawHeader);
            if ($h === '') continue;

            $headers[$colLetter] = $h;

            $needle = $normalize(self::PUESTO_HEADER_NEEDLE);
            if ($puestoCol === null && mb_stripos($h, $needle) !== false) {
                $puestoCol = $colLetter;
            }
        }

        if (!$puestoCol) {
            return back()->withErrors(['excel_file' => 'No se encontró la columna "Puesto de trabajo analizado" en el encabezado.']);
        }

        foreach ($headers as $col => $name) {
            if (!isset($seen[$name])) {
                $seen[$name] = [$col];
            } else {
                $seen[$name][] = $col;
            }
        }
        foreach ($seen as $name => $cols) {
            if (count($cols) > 1) {
                foreach ($cols as $col) {
                    $headers[$col] = $name . ' (' . $col . ')';
                }
            }
        }

        $omit = [
            $puestoCol,
        ];
        foreach ($headers as $col => $name) {
            if (preg_match('/^no\.?$/i', $name)) {
                $omit[] = $col;
            }
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        DB::beginTransaction();
        try {
            $rowCount = count($rows);
            for ($r = 2; $r <= $rowCount; $r++) {
                $row = $rows[$r] ?? [];
                if (!$row) continue;

                $puestoRaw = $normalize($row[$puestoCol] ?? '');
                if ($puestoRaw === '') {
                    $skipped++;
                    continue;
                }

                $idPuesto = DB::table('puesto_trabajo_matriz')
                    ->whereRaw('LOWER(TRIM(puesto_trabajo_matriz)) = ?', [mb_strtolower(trim($puestoRaw))])
                    ->value('id_puesto_trabajo_matriz');

                if (!$idPuesto) {
                    $skipped++;
                    continue;
                }

                foreach ($headers as $col => $encabezado) {
                    if (in_array($col, $omit, true)) continue;

                    $cell = $row[$col] ?? null;
                    $valor = is_null($cell) ? null : trim((string) $cell);

                    $valor = trim((string)$valor);

                    if ($valor === '') {
                        $valor = null;
                    }

                    $detalles = mb_substr($encabezado, 0, 1000);
                    $valorCut = is_null($valor) ? null : mb_substr($valor, 0, 50);

                    $affected = DB::table('detalles_riesgo')->updateOrInsert(
                        [
                            'id_puesto_trabajo_matriz' => $idPuesto,
                            'detalles_riesgo'          => $detalles,
                        ],
                        [
                            'valor'          => $valorCut,
                            'observaciones'  => null,
                        ]
                    );

                    $exists = DB::table('detalles_riesgo')
                        ->where('id_puesto_trabajo_matriz', $idPuesto)
                        ->where('detalles_riesgo', $detalles)
                        ->exists();

                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['excel_file' => 'Error al importar: ' . $e->getMessage()]);
        }

        return back()->with('status', "Importación completada. Filas procesadas: " . ($rowCount - 1) . " (omitidas por falta de puesto: {$skipped}).");
    }
}
