<?php

namespace App\Http\Controllers;

use App\Models\Capacitacion;
use App\Models\PuestoCapacitacion;
use App\Models\PuestoTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatrizPuestosCapacitacionController extends Controller
{
        public function index(Request $request)
    {
        $buscarPuesto = $request->string('puesto')->toString();
        $buscarCap    = $request->string('capacitacion')->toString();

        $puestos = PuestoTrabajo::query()
            ->when($buscarPuesto, fn($q) => $q->where('puesto_trabajo_matriz', 'like', "%{$buscarPuesto}%"))
            ->where('estado', 1)
            ->orderBy('puesto_trabajo_matriz')
            ->get(['id_puesto_trabajo_matriz','puesto_trabajo_matriz']);

        $caps = DB::table('capacitacion as c')
            ->join('capacitacion_instructor as ci', 'ci.id_capacitacion', '=', 'c.id_capacitacion')
            ->when($buscarCap, fn($q) => $q->where('c.capacitacion', 'like', "%{$buscarCap}%"))
            ->select('c.id_capacitacion','c.capacitacion')
            ->distinct()
            ->orderBy('c.capacitacion')
            ->get();

        $capIds = $caps->pluck('id_capacitacion');

        $pivot = DB::table('puestos_capacitacion')
            ->select('id_puesto_trabajo_matriz','id_capacitacion')
            ->whereIn('id_capacitacion', $capIds)
            ->get()
            ->groupBy('id_puesto_trabajo_matriz')
            ->map(fn($rows) => collect($rows)->pluck('id_capacitacion')->flip()->map(fn() => true)->all());

        return view('capacitaciones.puestos_capacitacion',
            compact('puestos','caps','pivot','buscarPuesto','buscarCap'));
    }

    public function store(Request $request)
    {
        $matrix = $request->input('matrix', []);

        // IDs presentes en el formulario para limitar inserts/deletes
        $puestosIds = array_map('intval', (array) $request->input('puesto_ids', []));
        $capIds = array_map('intval', (array) $request->input('cap_ids', []));

        DB::transaction(function() use ($matrix, $puestosIds, $capIds) {
            $actual = PuestoCapacitacion::query()
                ->when(!empty($puestosIds), fn($q) => $q->whereIn('id_puesto_trabajo_matriz', $puestosIds))
                ->when(!empty($capIds), fn($q) => $q->whereIn('id_capacitacion', $capIds))
                ->get(['id_puesto_trabajo_matriz','id_capacitacion'])
                ->map(fn($r) => $r->id_puesto_trabajo_matriz.'-'.$r->id_capacitacion)
                ->toArray();

            $seleccionados = [];
            foreach ($matrix as $idPuesto => $cols) {
                foreach (array_keys($cols) as $idCap) {
                    $seleccionados[] = $idPuesto.'-'.$idCap;
                }
            }

            $toInsert = array_diff($seleccionados, $actual);
            $toDelete = array_diff($actual, $seleccionados);

            if (!empty($toInsert)) {
                $rows = [];
                foreach ($toInsert as $pair) {
                    [$p,$c] = explode('-', $pair);
                    $rows[] = ['id_puesto_trabajo_matriz' => (int)$p, 'id_capacitacion' => (int)$c];
                }
                PuestoCapacitacion::query()->insertOrIgnore($rows);
            }

            if (!empty($toDelete)) {
                foreach ($toDelete as $pair) {
                    [$p,$c] = explode('-', $pair);
                    PuestoCapacitacion::query()
                        ->where('id_puesto_trabajo_matriz', (int)$p)
                        ->where('id_capacitacion', (int)$c)
                        ->delete();
                }
            }
        });

        return redirect()->route('capacitaciones.puestoscapacitacion')
            ->with('ok', 'Matriz de capacitaciones actualizada con éxito!');
    }
}
