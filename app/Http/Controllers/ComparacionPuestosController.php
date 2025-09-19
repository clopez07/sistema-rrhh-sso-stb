<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComparacionPuestosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $comparaciones = DB::table('comparacion_puestos as cp')
            ->leftJoin('puesto_trabajo_matriz as ptm', 'cp.id_puesto_trabajo_matriz', '=', 'ptm.id_puesto_trabajo_matriz')
            ->leftJoin('puesto_trabajo as pt', 'cp.id_puesto_trabajo', '=', 'pt.id_puesto_trabajo')
            ->when($search, function($q) use ($search) {
                $q->where('ptm.puesto_trabajo_matriz', 'like', "%{$search}%")
                  ->orWhere('pt.puesto_trabajo', 'like', "%{$search}%");
            })
            ->select(
                'cp.id_comparacion_puestos',
                'cp.id_puesto_trabajo_matriz',
                'cp.id_puesto_trabajo',
                'ptm.puesto_trabajo_matriz',
                'pt.puesto_trabajo',
                'pt.departamento'
            )
            ->orderBy('ptm.puesto_trabajo_matriz')
            ->paginate(10)
            ->appends(['search' => $search]);

        $puestosMatriz = DB::table('puesto_trabajo_matriz')
            ->where('estado', 1)
            ->orderBy('puesto_trabajo_matriz')
            ->get(['id_puesto_trabajo_matriz','puesto_trabajo_matriz']);

        $puestosSistema = DB::table('puesto_trabajo')
            ->where('estado', 1)
            ->orderBy('puesto_trabajo')
            ->get(['id_puesto_trabajo','puesto_trabajo','departamento']);

        return view('generales.comparacion_puestos', compact('comparaciones','puestosMatriz','puestosSistema','search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_puesto_trabajo'           => 'required|integer|exists:puesto_trabajo,id_puesto_trabajo',
            'id_puesto_trabajo_matriz'    => 'required|array',
            'id_puesto_trabajo_matriz.*'  => 'integer|exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz',
        ]);

        $sistemaId = (int) $validated['id_puesto_trabajo'];
        $matrizIds = array_unique(array_map('intval', $validated['id_puesto_trabajo_matriz']));

        if (empty($matrizIds)) {
            return back()->with('error', 'Seleccione al menos un Puesto (Matriz).');
        }

        $rows = [];
        foreach ($matrizIds as $mid) {
            $rows[] = [
                'id_puesto_trabajo_matriz' => $mid,
                'id_puesto_trabajo'        => $sistemaId,
            ];
        }

        DB::table('comparacion_puestos')->insertOrIgnore($rows);

        return back()->with('success', 'Comparación(es) agregada(s).');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_puesto_trabajo_matriz' => 'required|integer|exists:puesto_trabajo_matriz,id_puesto_trabajo_matriz',
            'id_puesto_trabajo'        => 'required|integer|exists:puesto_trabajo,id_puesto_trabajo',
        ]);

        $exists = DB::table('comparacion_puestos')
            ->where('id_puesto_trabajo_matriz', $data['id_puesto_trabajo_matriz'])
            ->where('id_puesto_trabajo', $data['id_puesto_trabajo'])
            ->where('id_comparacion_puestos', '!=', (int)$id)
            ->exists();
        if ($exists) {
            return back()->with('error', 'Ya existe otra comparación con esos puestos.');
        }

        DB::table('comparacion_puestos')
            ->where('id_comparacion_puestos', (int)$id)
            ->update($data);

        return back()->with('success', 'Comparación actualizada.');
    }

    public function destroy($id)
    {
        DB::table('comparacion_puestos')->where('id_comparacion_puestos', (int)$id)->delete();
        return back()->with('success', 'Comparación eliminada.');
    }
}
