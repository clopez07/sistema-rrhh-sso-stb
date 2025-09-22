@extends('layouts.capacitacion')
@section('content')
<div class="p-6">
    @if(session('ok'))
        <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('ok') }}</div>
    @endif

    <div class="flex items-end gap-3 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-600">Buscar Puesto</label>
            <input type="text" name="puesto" form="filters" value="{{ $buscarPuesto }}" class="mt-1 w-56 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nombre del puesto..." list="puestos-list">
            <datalist id="puestos-list">
                @foreach($puestos as $p)
                    <option value="{{ $p->puesto_trabajo_matriz }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600">Buscar Capacitación</label>
            <input type="text" name="capacitacion" form="filters" value="{{ $buscarCap }}" class="mt-1 w-56 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Tema...">
        </div>
        <form id="filters" method="GET" action="{{ route('capacitaciones.puestoscapacitacion') }}">
            <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white px-4 py-2 shadow hover:bg-indigo-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M10.5 3.75a6.75 6.75 0 015.306 10.98l4.232 4.232a.75.75 0 11-1.06 1.06l-4.232-4.232A6.75 6.75 0 1110.5 3.75zm0 1.5a5.25 5.25 0 100 10.5 5.25 5.25 0 000-10.5z"/></svg>
                Filtrar
            </button>
        </form>
        <button id="expandCols" class="ml-auto rounded-xl border px-3 py-2 text-sm hover:bg-gray-50">Expandir columnas</button>
    </div>

    <form method="POST" action="{{ route('capacitaciones.puestoscapacitacion.store') }}" id="matrizForm">
        @csrf
        <div class="relative max-h-[85vh] overflow-auto border rounded-2xl shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-500 text-white">
                    <tr>
                        {{-- ESQUINA: sticky arriba + izquierda (z mayor) --}}
                        <th class="sticky top-0 left-0 z-40 p-3 text-left bg-indigo-500">
                        Puesto de trabajo
                        </th>

                        {{-- Cada título de columna --}}
                        @foreach($caps as $col)
                        <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500 bg-indigo-500">
                            <input type="hidden" name="cap_ids[]" value="{{ $col->id_capacitacion }}">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-semibold leading-tight">{{ $col->capacitacion }}</span>
                                <label class="text-[11px] inline-flex items-center gap-1 mt-1">
                                    <input type="checkbox" class="col-toggle rounded" data-col="{{ $col->id_capacitacion }}">
                                    <span>Todo</span>
                                </label>
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($puestos as $row)
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50/40">
                            <td class="sticky left-0 z-20 bg-white p-3 font-medium text-gray-800 border-r">
                                <input type="hidden" name="puesto_ids[]" value="{{ $row->id_puesto_trabajo_matriz }}">
                                {{ $row->puesto_trabajo_matriz }}
                            </td>
                            @foreach($caps as $col)
                                @php
                                    $checked = isset($pivot[$row->id_puesto_trabajo_matriz]) && isset($pivot[$row->id_puesto_trabajo_matriz][$col->id_capacitacion]);
                                @endphp
                                <td class="p-2 text-center align-middle border-l">
                                    <input type="checkbox"
                                        name="matrix[{{ $row->id_puesto_trabajo_matriz }}][{{ $col->id_capacitacion }}]"
                                        value="1"
                                        class="row-{{ $row->id_puesto_trabajo_matriz }} col-{{ $col->id_capacitacion }} rounded w-5 h-5 cursor-pointer focus:ring-indigo-500"
                                        @checked($checked)
                                    />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 text-white px-5 py-2.5 shadow hover:bg-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 2.25a.75.75 0 01.75.75V12h8.25a.75.75 0 010 1.5H12.75v8.25a.75.75 0 01-1.5 0V13.5H3a.75.75 0 010-1.5h8.25V3a.75.75 0 01.75-.75z"/></svg>
                Guardar cambios
            </button>
            <button type="button" id="marcarTodo" class="rounded-2xl border px-4 py-2 hover:bg-gray-50">Marcar todo</button>
            <button type="button" id="desmarcarTodo" class="rounded-2xl border px-4 py-2 hover:bg-gray-50">Desmarcar todo</button>
        </div>
    </form>
</div>

<script>
for (const colToggle of document.querySelectorAll('.col-toggle')) {
    colToggle.addEventListener('change', (e) => {
        const col = e.target.getAttribute('data-col');
        document.querySelectorAll('.col-' + col).forEach(ch => ch.checked = e.target.checked);
    });
}
document.getElementById('marcarTodo').addEventListener('click', () => {
    document.querySelectorAll('input[type="checkbox"][name^="matrix"]').forEach(ch => ch.checked = true);
});
document.getElementById('desmarcarTodo').addEventListener('click', () => {
    document.querySelectorAll('input[type="checkbox"][name^="matrix"]').forEach(ch => ch.checked = false);
});
const expandCols = document.getElementById('expandCols');
expandCols?.addEventListener('click', () => {
    document.querySelectorAll('thead th, tbody td').forEach(c => c.classList.toggle('min-w-[200px]'));
});
</script>
@endsection
