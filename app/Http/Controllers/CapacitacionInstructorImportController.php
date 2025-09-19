<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\CapacitacionInstructor;
use App\Models\capacitacion as Capacitacion;
use App\Models\instructor as Instructor;

class CapacitacionInstructorImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,xlsm',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());

        $worksheet = $spreadsheet->getSheetByName('LISTA_DE_CAPACITACIONES');
        if (!$worksheet) {
            $worksheet = $spreadsheet->getSheet(0);
        }

        $rows = $worksheet->toArray();

        $creados = 0; $actualizados = 0; $omitidos = 0;

        $norm = fn($v) => trim((string)($v ?? ''));

        $toMinutes = function($v) {
            $s = strtolower(trim((string)$v));
            if ($s === '') return 0;
            if (is_numeric($s)) return (int)$s;
            if (preg_match('/(\d+)\s*h/', $s, $m)) {
                return (int)$m[1] * 60;
            }
            if (preg_match('/(\d+)\s*hora/', $s, $m)) {
                return (int)$m[1] * 60;
            }
            if (preg_match('/(\d+)\s*min/', $s, $m)) {
                return (int)$m[1];
            }
            if (preg_match('/(\d+)\s*hora.*media/', $s, $m)) {
                return ((int)$m[1]) * 60 + 30;
            }
            if (preg_match('/(\d+)/', $s, $m)) {
                $n = (int)$m[1];
                if (str_contains($s, 'hora')) return $n * 60;
                return $n;
            }
            return 0;
        };

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $row) {
                $capName = $norm($row[1] ?? '');
                $insName = $norm($row[2] ?? '');
                $duracion = $toMinutes($row[3] ?? '');

                if ($capName === '' || $insName === '') { $omitidos++; continue; }

                $cap = Capacitacion::firstOrCreate(['capacitacion' => $capName]);
                $ins = Instructor::firstOrCreate(['instructor' => $insName]);

                $ci = CapacitacionInstructor::where('id_capacitacion', $cap->id_capacitacion)
                    ->where('id_instructor', $ins->id_instructor)
                    ->first();

                $data = [
                    'id_capacitacion' => $cap->id_capacitacion,
                    'id_instructor'   => $ins->id_instructor,
                    'duracion'        => (int)$duracion,
                ];

                if ($ci) {
                    $ci->fill($data);
                    if ($ci->isDirty()) { $ci->save(); $actualizados++; }
                } else {
                    CapacitacionInstructor::create($data);
                    $creados++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }

        return back()->with('success', "Importación completada. Creados: $creados, Actualizados: $actualizados, Omitidos: $omitidos.");
    }
}

