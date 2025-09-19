@extends('layouts.generales')

@section('content')
<div class="mb-4 flex items-center justify-between">
  <h1 class="text-2xl font-bold">Comparación de Puestos</h1>
  <a href="{{ url('/puestossistemas') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2">Volver a Puestos</a>
  </div>

@if(session('success'))
  <div class="mb-3 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-red-800">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow p-4 mb-4">
  <form method="GET" class="flex gap-3 items-end">
    <div>
      <label class="block text-sm text-gray-600">Buscar</label>
      <input type="text" name="search" value="{{ $search }}" class="mt-1 rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" placeholder="Puesto matriz o sistema..."/>
    </div>
    <button class="rounded-lg bg-blue-600 text-white px-4 py-2">Filtrar</button>
  </form>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6">
  <h2 class="font-semibold mb-3">Agregar comparación</h2>
  <form method="POST" action="{{ route('comparacion_puestos.store') }}" class="grid md:grid-cols-3 gap-3">
    @csrf
    <div>
      <label class="block text-sm text-gray-600">Puesto (Matriz)</label>
      <input type="text" id="buscar-puestos-matriz" class="mt-1 mb-2 w-full rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" placeholder="Buscar puesto de la matriz..." autocomplete="off">
      <select id="select-puestos-matriz" name="id_puesto_trabajo_matriz[]" multiple size="8" class="w-full rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" required>
        @foreach($puestosMatriz as $pm)
        <option value="{{ $pm->id_puesto_trabajo_matriz }}">{{ $pm->puesto_trabajo_matriz }}</option>
        @endforeach
      </select>
      <p class="mt-1 text-xs text-gray-500">Use Ctrl/Cmd o Shift para seleccionar varios.</p>
    </div>
    <div>
      <label class="block text-sm text-gray-600">Puesto (Sistema)</label>
      <select name="id_puesto_trabajo" class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" required>
        <option value="" hidden>Seleccione...</option>
        @foreach($puestosSistema as $ps)
        <option value="{{ $ps->id_puesto_trabajo }}">{{ $ps->puesto_trabajo }} @if($ps->departamento) - {{ $ps->departamento }} @endif</option>
        @endforeach
      </select>
    </div>
    <div class="flex items-end">
      <button class="rounded-lg bg-emerald-600 text-white px-4 py-2">Agregar</button>
    </div>
  </form>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="px-4 py-3 text-left">Puesto (Matriz)</th>
        <th class="px-4 py-3 text-left">Puesto (Sistema)</th>
        <th class="px-4 py-3 text-left">Departamento</th>
        <th class="px-4 py-3 text-left">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($comparaciones as $row)
      <tr>
        <td class="px-4 py-3">{{ $row->puesto_trabajo_matriz }}</td>
        <td class="px-4 py-3">{{ $row->puesto_trabajo }}</td>
        <td class="px-4 py-3">{{ $row->departamento }}</td>
        <td class="px-4 py-3">
            <button class="text-blue-600 hover:underline"
                    data-modal-target="edit-{{ $row->id_comparacion_puestos }}"
                    data-modal-toggle="edit-{{ $row->id_comparacion_puestos }}">Editar</button>
            <form method="POST" action="{{ route('comparacion_puestos.destroy', $row->id_comparacion_puestos) }}" class="inline" onsubmit="return confirm('¿Eliminar comparación?');">
              @csrf @method('DELETE')
              <button class="text-red-600 hover:underline ms-3">Eliminar</button>
            </form>

            <div id="edit-{{ $row->id_comparacion_puestos }}" tabindex="-1" aria-hidden="true"
                 class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
              <div class="relative w-full max-w-md max-h-full mx-auto">
                <div class="relative bg-white rounded-lg shadow">
                  <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="font-semibold">Editar comparación</h3>
                    <button type="button" class="text-gray-400 hover:bg-gray-100 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center"
                            data-modal-target="edit-{{ $row->id_comparacion_puestos }}"
                            data-modal-toggle="edit-{{ $row->id_comparacion_puestos }}">
                      <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                    </button>
                  </div>
                  <form method="POST" action="{{ route('comparacion_puestos.update', $row->id_comparacion_puestos) }}" class="p-4 grid gap-3">
                    @csrf @method('PUT')
                    <div>
                      <label class="block text-sm text-gray-600">Puesto (Matriz)</label>
                      <select name="id_puesto_trabajo_matriz" class="mt-1 w-full rounded-lg border-gray-300">
                        @foreach($puestosMatriz as $pm)
                        <option value="{{ $pm->id_puesto_trabajo_matriz }}" @selected($pm->id_puesto_trabajo_matriz==$row->id_puesto_trabajo_matriz)>{{ $pm->puesto_trabajo_matriz }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm text-gray-600">Puesto (Sistema)</label>
                      <select name="id_puesto_trabajo" class="mt-1 w-full rounded-lg border-gray-300">
                        @foreach($puestosSistema as $ps)
                        <option value="{{ $ps->id_puesto_trabajo }}" @selected($ps->id_puesto_trabajo==$row->id_puesto_trabajo)>{{ $ps->puesto_trabajo }} @if($ps->departamento) - {{ $ps->departamento }} @endif</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="pt-2">
                      <button class="rounded-lg bg-blue-600 text-white px-4 py-2">Guardar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin registros</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="p-3">{{ $comparaciones->links() }}</div>
</div>
<script>
  (function() {
    const input = document.getElementById('buscar-puestos-matriz');
    const select = document.getElementById('select-puestos-matriz');
    if (!input || !select) return;
    const normaliza = (s) => (s || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    input.addEventListener('input', function() {
      const q = normaliza(this.value);
      const opts = Array.from(select.options);
      opts.forEach(opt => {
        // Mantener visibles los seleccionados aunque no coincidan
        const texto = normaliza(opt.text);
        const match = q === '' || texto.includes(q);
        opt.hidden = !match && !opt.selected;
      });
    });
  })();
</script>
@endsection
