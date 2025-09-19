@extends('layouts.riesgos')
@section('content')

<style>
  .table-scroll::-webkit-scrollbar{height:8px;width:8px}
  .table-scroll::-webkit-scrollbar-thumb{background:#c7c7d1;border-radius:8px}
</style>

<div class="p-6">
  @if(session('ok'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
  @endif

  <h2 class="text-xl font-semibold text-gray-800 mb-4">Químicos por Puesto de Trabajo</h2>

  <div class="flex flex-wrap items-end gap-3 mb-5">
    <div>
      <label class="block text-sm font-medium text-gray-600">Puesto</label>
      <select class="mt-1 w-72 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" name="puesto" form="filters">
        <option value="">Selecciona un puesto…</option>
        @foreach($puestos as $p)
          <option value="{{ $p->id_puesto_trabajo_matriz }}" @selected($puestoId == $p->id_puesto_trabajo_matriz)>
            {{ $p->puesto_trabajo_matriz }}
          </option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-600">Buscar químico</label>
      <input type="text" name="quimico" form="filters" value="{{ $buscarQuimico }}" class="mt-1 w-64 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nombre comercial…">
    </div>
    <form id="filters" method="GET" action="{{ route('riesgos.quimicos.puesto') }}">
      <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white px-4 py-2 shadow hover:bg-indigo-700">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M10.5 3.75a6.75 6.75 0 015.306 10.98l4.232 4.232a.75.75 0 11-1.06 1.06l-4.232-4.232A6.75 6.75 0 1110.5 3.75zm0 1.5a5.25 5.25 0 100 10.5 5.25 5.25 0 000-10.5z"/></svg>
        Consultar
      </button>
    </form>
  </div>

  @if(!$puestoId)
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3">Selecciona un puesto para ver sus químicos asociados.</div>
  @else
    <div class="relative max-h-[70vh] overflow-auto border rounded-2xl shadow-sm table-scroll">
      <table class="min-w-full text-sm">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[240px]">Puesto</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[140px] border-l border-indigo-500"># Empleados</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[240px] border-l border-indigo-500">Químico</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[200px] border-l border-indigo-500">Tipo de Exposición</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Frecuencia</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Duración de Exposición</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Ninguno</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Partíulas de polvo, humos, gases y vapores</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Sustancias corrosivas</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Sustancias Tóxicas</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Sustancias irritantes o alergizantes</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Salud</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Inflamabilidad</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[160px] border-l border-indigo-500">Reactividad</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50/40">
              <td class="p-3 text-gray-800">
                <div class="font-medium">{{ $r['puesto'] }}</div>
              </td>
              <td class="p-3 text-center border-l align-top">
                {{ $r['num_empleados'] ?? '—' }}
              </td>
              <td class="p-3 border-l align-top">
                <div class="text-[12px] text-gray-700">{{ $r['descripcion_general'] }}</div>
              </td>
              <td class="p-3 border-l align-top">
                <div class="font-medium">{{ $r['quimico']->nombre_comercial }}</div>
                @if($r['quimico']->uso)
                  <div class="text-[11px] text-gray-500">Uso: {{ $r['quimico']->uso }}</div>
                @endif
              </td>
              <td class="p-3 border-l align-top">
                {{ $r['exposicion'] ?? '—' }}
              </td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
              <td class="p-3 text-center border-l align-top text-gray-400">—</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-6 text-center text-gray-500">No se encontraron registros con los filtros aplicados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection
