<?php

namespace App\Http\Controllers;

use App\Models\EPP;
use App\Models\PuestoEpp;
use App\Models\PuestosSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatrizPuestosEppController extends Controller
{
    public function index(Request $request)
    {
        $buscarPuesto = $request->string('puesto')->toString();
        $buscarEpp    = $request->string('epp')->toString();

        $puestos = PuestosSistema::query()
            ->when($buscarPuesto, fn ($q) => $q->where('puesto_trabajo', 'like', "%{$buscarPuesto}%"))
            ->where('estado', 1)
            ->orderBy('puesto_trabajo')
            ->get(['id_puesto_trabajo', 'puesto_trabajo', 'departamento']);

        $epps = EPP::query()
            ->when($buscarEpp, fn ($q) => $q->where('equipo', 'like', "%{$buscarEpp}%"))
            ->orderBy('equipo')
            ->get(['id_epp', 'equipo', 'codigo']);

        $pivot = DB::table('puestos_epp')
            ->select('id_puesto_trabajo', 'id_epp')
            ->get()
            ->groupBy('id_puesto_trabajo')
            ->map(function ($rows) {
                return collect($rows)->pluck('id_epp')->flip()->map(fn () => true)->all();
            });

        return view('epp.puestos_epp', compact('puestos', 'epps', 'pivot', 'buscarPuesto', 'buscarEpp'));
    }

    public function store(Request $request)
    {
        $matrix          = (array) $request->input('matrix', []);
        $puestosVisibles = array_values(array_unique(array_map('intval', (array) $request->input('puestos_visibles', []))));
        $eppsVisibles    = array_values(array_unique(array_map('intval', (array) $request->input('epps_visible', []))));

        DB::transaction(function () use ($matrix, $puestosVisibles, $eppsVisibles) {
            $seleccionados = [];
            foreach ($matrix as $idPuesto => $cols) {
                $idP = (int) $idPuesto;
                foreach (array_keys((array) $cols) as $idEpp) {
                    $idE = (int) $idEpp;
                    if ((empty($puestosVisibles) || in_array($idP, $puestosVisibles, true)) &&
                        (empty($eppsVisibles) || in_array($idE, $eppsVisibles, true))) {
                        $seleccionados[] = $idP.'-'.$idE;
                    }
                }
            }

            $actualQuery = PuestoEpp::query();
            if (!empty($puestosVisibles)) {
                $actualQuery->whereIn('id_puesto_trabajo', $puestosVisibles);
            }
            if (!empty($eppsVisibles)) {
                $actualQuery->whereIn('id_epp', $eppsVisibles);
            }
            $actual = $actualQuery
                ->get(['id_puesto_trabajo', 'id_epp'])
                ->map(fn ($r) => $r->id_puesto_trabajo.'-'.$r->id_epp)
                ->toArray();

            $toInsert = array_diff($seleccionados, $actual);
            $toDelete = array_diff($actual, $seleccionados);

            if (!empty($toInsert)) {
                $rows = [];
                foreach ($toInsert as $pair) {
                    [$p, $e] = explode('-', $pair);
                    $rows[] = [
                        'id_puesto_trabajo' => (int) $p,
                        'id_epp'            => (int) $e,
                    ];
                }
                PuestoEpp::query()->insertOrIgnore($rows);
            }

            if (!empty($toDelete)) {
                foreach ($toDelete as $pair) {
                    [$p, $e] = explode('-', $pair);
                    PuestoEpp::query()
                        ->where('id_puesto_trabajo', (int) $p)
                        ->where('id_epp', (int) $e)
                        ->delete();
                }
            }
        });

        return redirect()->route('epp.puestos_epp')
            ->with('ok', '¡Matriz actualizada con éxito!');
    }
}

