<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Generales extends Controller
{

    public function empleado(Request $request)
    {
    $empleados = DB::table('empleado as e')
        ->join('puesto_trabajo as p', 'e.id_puesto_trabajo', '=', 'p.id_puesto_trabajo')
        ->select(
            'e.id_empleado',
            'e.nombre_completo',
            'e.codigo_empleado',
            'e.identidad',
            'e.id_puesto_trabajo',
            'p.puesto_trabajo',
            'p.departamento',
            DB::raw("CASE WHEN e.estado = 1 THEN 'Activo' ELSE 'Inactivo' END as estado")
        )
        ->when($request->search, function ($query, $search) {
            return $query->where('e.nombre_completo', 'like', "%{$search}%")
                         ->orWhere('e.codigo_empleado', 'like', "%{$search}%")
                         ->orWhere('p.puesto_trabajo', 'like', "%{$search}%");
        })
        ->orderBy('e.nombre_completo', 'asc')
        ->paginate(10)
        ->appends(['search' => $request->search]);

    $puestos = DB::select('CALL sp_obtener_puestos_trabajo_sistema()');

    return view('generales.empleados', compact('empleados', 'puestos'));
    }

    public function store(Request $request)
    {
        DB::table('empleado')->insert([
            'nombre_completo' => $request->input('nombre_completo'),
            'identidad' => $request->input('identidad'),
            'codigo_empleado' => $request->input('codigo_empleado'),
            'id_puesto_trabajo' => $request->input('id_puesto_trabajo'),
            'estado' => 1,
        ]);

        return redirect()->back()->with('success', 'Empleado agregado correctamente');
    }

    public function update(Request $request, $id)
    {
        $data = [
            'nombre_completo'   => $request->input('nombre_completo'),
            'identidad'         => $request->input('identidad'),
            'codigo_empleado'   => $request->input('codigo_empleado'),
            'id_puesto_trabajo' => $request->input('id_puesto_trabajo'),
        ];
        if ($request->has('estado')) {
            $data['estado'] = (int)$request->input('estado');
        }

        DB::table('empleado')
            ->where('id_empleado', $id)
            ->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroy($id)
    {
        DB::table('empleado')->where('id_empleado', $id)->delete();
        return redirect()->back()->with('success', 'Empleado eliminado correctamente');
    }

    public function puestos(Request $request)
    {
        $puestos = DB::table('puesto_trabajo_matriz as p')
        ->leftJoin('departamento as d', 'p.id_departamento', '=', 'd.id_departamento')
        ->leftJoin('area as a', 'p.id_area', '=', 'a.id_area')
        ->select('p.*', 'd.*', 'a.*')
        ->when($request->search, function ($query, $search) {
            return $query->where('p.puesto_trabajo_matriz', 'like', "%{$search}%")
                         ->orWhere('d.departamento', 'like', "%{$search}%");
        })
        ->orderBy('p.puesto_trabajo_matriz', 'asc')
        ->paginate(10)
        ->appends(['search' => $request->search]);
        $departamento = DB::select('CALL sp_obtener_departamento()');
        $area = DB::select('CALL sp_obtener_area()');
        return view('generales.puestos', compact('puestos', 'departamento', 'area'));
    }

    public function storepuestos(Request $request)
    {
        $request->validate([
            'puesto_trabajo'   => 'required|string|max:255',
            'id_departamento'  => 'required|integer',
            'id_area'          => 'required|integer',
            'num_empleados'    => 'required|numeric',
            'estado'           => 'required|in:1,2',
        ]);
        DB::table('puesto_trabajo_matriz')->insert([
            'puesto_trabajo_matriz' => $request->input('puesto_trabajo'),
            'id_departamento' => $request->input('id_departamento'),
            'id_area' => $request->input('id_area'),
            'num_empleados' => $request->input('num_empleados'),
            'descripcion_general' => $request->input('descripcion_general'),
            'actividades_diarias' => $request->input('actividades_diarias'),
            'objetivo_puesto' => $request->input('objetivo_puesto'),
            'estado' => (int)$request->input('estado'),
        ]);

        return redirect()->back()->with('success', 'Agregado correctamente');
    }

    public function updatepuestos(Request $request, $id)
    {
        $request->validate([
            'puesto_trabajo'   => 'required|string|max:255',
            'id_departamento'  => 'required|integer',
            'id_area'          => 'required|integer',
            'num_empleados'    => 'required|numeric',
            'estado'           => 'required|in:1,2',
        ]);
        DB::table('puesto_trabajo_matriz')
            ->where('id_puesto_trabajo_matriz', $id)
            ->update([
                'puesto_trabajo_matriz' => $request->input('puesto_trabajo'),
                'id_departamento' => $request->input('id_departamento'),
                'id_area' => $request->input('id_area'),
                'num_empleados' => $request->input('num_empleados'),
                'descripcion_general' => $request->input('descripcion_general'),
                'actividades_diarias' => $request->input('actividades_diarias'),
                'objetivo_puesto' => $request->input('objetivo_puesto'),
                'estado' => (int)$request->input('estado'),
            ]);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroypuestos($id)
    {
        DB::table('puesto_trabajo_matriz')->where('id_puesto_trabajo_matriz', $id)->delete();
        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    public function departamento(Request $request)
    {
        $departamento = DB::table('departamento as d')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('d.departamento', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        
        return view('generales.departamento', compact('departamento'));
    }

    public function storedepartamento(Request $request)
    {
        $request->validate([
            'departamento' => 'required|string|max:100',
        ]);

        DB::table('departamento')->insert([
            'departamento' => $request->input('departamento'),
        ]);

        return redirect()->back()->with('success', 'Departamento registrado correctamente.');
    }

    public function updatedepartamento(Request $request, $id)
    {
        $request->validate([
            'departamento' => 'required|string|max:100',
        ]);

        DB::table('departamento')
            ->where('id_departamento', $id)
            ->update([
                'departamento' => $request->input('departamento'),
            ]);

        return redirect()->back()->with('success', 'Departamento actualizado correctamente');
    }

    public function destroydepartamento($id)
    {
        DB::table('departamento')->where('id_departamento', $id)->delete();
        return redirect()->back()->with('success', 'Departamento eliminado correctamente');
    }

    public function localizacion(Request $request)
    {
        $localizacion = DB::table('localizacion as l')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('l.localizacion', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        return view('generales.localizacion', compact('localizacion'));
    }

        public function storelocalizacion(Request $request)
    {
        $request->validate([
            'localizacion' => 'required|string|max:100',
        ]);

        // Normalizar y evitar duplicados (insensible a mayúsculas/espacios)
        $nombre = trim((string)$request->input('localizacion'));
        $norm   = mb_strtolower(preg_replace('/\s+/u', ' ', $nombre), 'UTF-8');

        $existe = DB::table('localizacion')
            ->whereRaw('LOWER(TRIM(localizacion)) = ?', [$norm])
            ->exists();
        if ($existe) {
            return back()->withErrors(['localizacion' => 'La localización ya existe.'])->withInput();
        }

        DB::table('localizacion')->insert([
            'localizacion' => $nombre,
        ]);

        return redirect()->back()->with('success', 'Localización registrada correctamente.');
    }

    public function updatelocalizacion(Request $request, $id)
    {
        $request->validate([
            'localizacion' => 'required|string|max:100',
        ]);

        $nombre = trim((string)$request->input('localizacion'));
        $norm   = mb_strtolower(preg_replace('/\s+/u', ' ', $nombre), 'UTF-8');

        $existe = DB::table('localizacion')
            ->whereRaw('LOWER(TRIM(localizacion)) = ?', [$norm])
            ->where('id_localizacion', '!=', $id)
            ->exists();
        if ($existe) {
            return back()->withErrors(['localizacion' => 'Ya existe otra localización con ese nombre.'])->withInput();
        }

        DB::table('localizacion')
            ->where('id_localizacion', $id)
            ->update([
                'localizacion' => $nombre,
            ]);

        return redirect()->back()->with('success', 'Localización actualizada correctamente');
    }

    public function destroylocalizacion($id)
    {
        DB::table('localizacion')->where('id_localizacion', $id)->delete();
        return redirect()->back()->with('success', 'Localizacion eliminado correctamente');
    }

    public function area(Request $request)
    {
        $area = DB::table('area as a')
        ->select('*')
        ->when($request->search, function ($query, $search) {
            return $query->where('a.area', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        return view('generales.area', compact('area'));
    }

    public function storearea(Request $request)
    {
        $request->validate([
            'area' => 'required|string|max:50',
        ]);

        DB::table('area')->insert([
            'area' => $request->input('area'),
        ]);

        return redirect()->back()->with('success', 'area registrado correctamente.');
    }

    public function updatearea(Request $request, $id)
    {

        $request->validate([
            'area' => 'required|string|max:100',
        ]);

        DB::table('area')
            ->where('id_area', $id)
            ->update([
                'area' => $request->input('area'),
            ]);

        return redirect()->back()->with('success', 'area actualizado correctamente');
    }

    public function destroyarea($id)
    {
        DB::table('area')->where('id_area', $id)->delete();
        return redirect()->back()->with('success', 'area eliminado correctamente');
    }

        public function puestossistema(Request $request)
    {
        $puestossistema = DB::table('puesto_trabajo as p')
        ->select('p.id_puesto_trabajo',
                'p.puesto_trabajo',
                'p.departamento',
            DB::raw("CASE WHEN p.estado = 1 THEN 'Activo' ELSE 'Inactivo' END as estado")
        )
        ->when($request->search, function ($query, $search) {
            return $query->where('p.departamento', 'like', "%{$search}%")
                         ->orWhere('p.puesto_trabajo', 'like', "%{$search}%");
        })
        ->paginate(10)
        ->appends(['search' => $request->search]);
        return view('generales.puestosistema', compact('puestossistema'));
    }
}
