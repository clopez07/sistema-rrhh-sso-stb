<?php

namespace App\Http\Controllers;

use App\Models\EPP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MatrizPuestosEppController extends Controller
{
    public function index(Request $request)
    {
        $buscarPuesto = trim((string) $request->input('puesto', ''));
        $buscarEpp    = trim((string) $request->input('epp', ''));

        $puestosQuery = DB::table('puesto_trabajo_matriz as ptm')
            ->leftJoin('departamento as d', 'ptm.id_departamento', '=', 'd.id_departamento')
            ->select('ptm.id_puesto_trabajo_matriz', 'ptm.puesto_trabajo_matriz', 'd.departamento')
            ->where('ptm.estado', 1);

        if ($buscarPuesto !== '') {
            $needle = mb_strtolower($buscarPuesto, 'UTF-8');
            $puestosQuery->whereRaw('LOWER(ptm.puesto_trabajo_matriz) LIKE ?', ["%{$needle}%"]);
        }

        $puestos = $puestosQuery
            ->orderBy('ptm.puesto_trabajo_matriz')
            ->get();

        $epps = EPP::query()
            ->when($buscarEpp !== '', function ($q) use ($buscarEpp) {
                $needle = mb_strtolower($buscarEpp, 'UTF-8');
                $q->whereRaw('LOWER(equipo) LIKE ?', ["%{$needle}%"]);
            })
            ->orderBy('equipo')
            ->get(['id_epp', 'equipo', 'codigo']);


$hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
$hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

$selectPuesto = 'id_puesto_trabajo as id_puesto';
if ($hasMatrixColumn && $hasLegacyColumn) {
    $selectPuesto = 'COALESCE(id_puesto_trabajo_matriz, id_puesto_trabajo) as id_puesto';
} elseif ($hasMatrixColumn) {
    $selectPuesto = 'id_puesto_trabajo_matriz as id_puesto';
} elseif ($hasLegacyColumn) {
    $selectPuesto = 'id_puesto_trabajo as id_puesto';
}

$pivotPairs = DB::table('puestos_epp')
    ->select([
        DB::raw($selectPuesto),
        'id_epp',
    ])
    ->get();

        $pivot = $pivotPairs->groupBy('id_puesto')->map(function ($rows) {
            return collect($rows)->pluck('id_epp')->flip()->map(fn () => true)->all();
        })->all();

        return view('epp.puestos_epp', compact('puestos', 'epps', 'pivot', 'buscarPuesto', 'buscarEpp'));
    }

    public function store(Request $request)
    {
        $matrix          = (array) $request->input('matrix', []);
        $puestosVisibles = array_values(array_unique(array_map('intval', (array) $request->input('puestos_visibles', []))));
        $eppsVisibles    = array_values(array_unique(array_map('intval', (array) $request->input('epps_visible', []))));

        $hasMatrixColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo_matriz');
        $hasLegacyColumn = Schema::hasColumn('puestos_epp', 'id_puesto_trabajo');

        DB::transaction(function () use ($matrix, $puestosVisibles, $eppsVisibles, $hasMatrixColumn, $hasLegacyColumn) {
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


$actualQuery = DB::table('puestos_epp');
if ($hasMatrixColumn) {
    if (!empty($puestosVisibles)) {
        $actualQuery->whereIn('id_puesto_trabajo_matriz', $puestosVisibles);
    }
} elseif ($hasLegacyColumn && !empty($puestosVisibles)) {
    $actualQuery->whereIn('id_puesto_trabajo', $puestosVisibles);
}
if (!empty($eppsVisibles)) {
    $actualQuery->whereIn('id_epp', $eppsVisibles);
}

$selectPuestoActual = 'id_puesto_trabajo as id_puesto';
if ($hasMatrixColumn && $hasLegacyColumn) {
    $selectPuestoActual = 'COALESCE(id_puesto_trabajo_matriz, id_puesto_trabajo) as id_puesto';
} elseif ($hasMatrixColumn) {
    $selectPuestoActual = 'id_puesto_trabajo_matriz as id_puesto';
} elseif ($hasLegacyColumn) {
    $selectPuestoActual = 'id_puesto_trabajo as id_puesto';
}

$actual = $actualQuery
    ->select([
        DB::raw($selectPuestoActual),
        'id_epp',
    ])
    ->get()
    ->map(fn ($row) => $row->id_puesto.'-'.$row->id_epp)
    ->toArray();

            $toInsert = array_diff($seleccionados, $actual);
            $toDelete = array_diff($actual, $seleccionados);


if (!empty($toInsert)) {
    $rows = [];
    foreach ($toInsert as $pair) {
        [$p, $e] = explode('-', $pair);
        $row = ['id_epp' => (int) $e];
        if ($hasMatrixColumn) {
            $row['id_puesto_trabajo_matriz'] = (int) $p;
            if ($hasLegacyColumn) {
                $row['id_puesto_trabajo'] = (int) $p;
            }
        } elseif ($hasLegacyColumn) {
            $row['id_puesto_trabajo'] = (int) $p;
        }
        $rows[] = $row;
    }
    DB::table('puestos_epp')->insertOrIgnore($rows);
}

if (!empty($toDelete)) {
    foreach ($toDelete as $pair) {
        [$p, $e] = explode('-', $pair);
        $delete = DB::table('puestos_epp')->where('id_epp', (int) $e);
        if ($hasMatrixColumn) {
            $delete->where(function ($query) use ($p, $hasLegacyColumn) {
                $query->where('id_puesto_trabajo_matriz', (int) $p);
                if ($hasLegacyColumn) {
                    $query->orWhere('id_puesto_trabajo', (int) $p);
                }
            });
        } elseif ($hasLegacyColumn) {
            $delete->where('id_puesto_trabajo', (int) $p);
        }
        $delete->delete();
    }
}
        });

        return redirect()->route('epp.puestos_epp')
            ->with('ok', 'Matriz actualizada con exito!');
    }
}
