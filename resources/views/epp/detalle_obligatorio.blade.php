@extends('layouts.epp')
@section('title', 'Detalle de EPP obligatorio por empleado')

@section('content')
<style>
  .table-scroll::-webkit-scrollbar{height:8px;width:8px}
  .table-scroll::-webkit-scrollbar-thumb{background:#c7c7d1;border-radius:8px}
  .badge{display:inline-block;padding:.2rem .5rem;border-radius:.5rem;font-weight:600;font-size:.75rem}
  .b-ok{background:#dcfce7;color:#166534}.b-warn{background:#fee2e2;color:#991b1b}
</style>

<div class="p-6 space-y-6">
  <form method="GET" action="{{ route('epp.detalle') }}" class="flex flex-wrap items-end gap-3">
    <div>
      <label class="block text-sm font-medium text-gray-600">EPP</label>
      <select name="epp_id" class="mt-1 w-80 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Selecciona un EPP --</option>
        @foreach($eppsAll as $e)
          <option value="{{ $e->id_epp }}" @selected((int)$eppId===(int)$e->id_epp)>
            {{ $e->equipo }}
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-600">Rango</label>
      <select name="rango" class="mt-1 w-44 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="anio" @selected($rango==='anio')>Año actual</option>
        <option value="12m"  @selected($rango==='12m')>Últimos 12 meses</option>
        <option value="todo" @selected($rango==='todo')>Todo el historial</option>
      </select>
    </div>

    <div x-data class="{{ $rango==='anio' ? '' : 'opacity-60' }}">
      <label class="block text-sm font-medium text-gray-600">Año</label>
      <select name="anio" class="mt-1 w-32 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" {{ $rango==='anio' ? '' : 'disabled' }}>
        @for($y = date('Y'); $y >= date('Y')-10; $y--)
          <option value="{{ $y }}" @selected((int)$anio===(int)$y)>{{ $y }}</option>
        @endfor
      </select>
    </div>

    <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white px-4 py-2 shadow hover:bg-indigo-700">
      Aplicar filtros
    </button>
  </form>

  {{-- Resumen --}}
  <div class="grid md:grid-cols-4 gap-4">
    <div class="rounded-2xl bg-white shadow p-4">
      <div class="text-sm text-gray-500">Total obligatorios</div>
      <div class="text-2xl font-bold">{{ number_format($resumen['total'] ?? 0) }}</div>
    </div>
    <div class="rounded-2xl bg-white shadow p-4">
      <div class="text-sm text-gray-500">Entregados</div>
      <div class="text-2xl font-bold text-emerald-700">{{ number_format($resumen['entregados'] ?? 0) }}</div>
    </div>
    <div class="rounded-2xl bg-white shadow p-4">
      <div class="text-sm text-gray-500">Pendientes</div>
      <div class="text-2xl font-bold text-red-700">{{ number_format($resumen['pendientes'] ?? 0) }}</div>
    </div>
    <div class="rounded-2xl bg-white shadow p-4">
      <div class="text-sm text-gray-500">% Avance</div>
      <div class="text-2xl font-bold">{{ number_format($resumen['avance'] ?? 0,1) }}%</div>
    </div>
  </div>

  @if((int)($resumen['total'] ?? 0) === 0)
    <div class="rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-900 p-4">
      Selecciona un EPP para ver el detalle (o no hay puestos que lo requieran).
    </div>
  @else
    <div class="grid lg:grid-cols-2 gap-6">
      {{-- ENTREGADOS --}}
      <div class="rounded-2xl bg-white shadow overflow-hidden">
        <div class="px-4 py-3 bg-emerald-600 text-white font-semibold">Con entrega</div>
        <div class="max-h-[60vh] overflow-auto table-scroll">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-3 text-left">Empleado</th>
                <th class="p-3 text-left">Código</th>
                <th class="p-3 text-left">Departamento</th>
                <th class="p-3 text-left">Puesto</th>
                <th class="p-3 text-left">Última entrega</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              @forelse($entregados as $r)
                <tr class="odd:bg-white even:bg-gray-50">
                  <td class="p-3 font-medium">{{ $r->nombre_completo }}</td>
                  <td class="p-3">{{ $r->codigo_empleado }}</td>
                  <td class="p-3">{{ $r->departamento }}</td>
                  <td class="p-3">{{ $r->puesto }}</td>
                  <td class="p-3">{{ $r->ultima_entrega }}</td>
                </tr>
              @empty
                <tr><td class="p-4 text-gray-500" colspan="5">No hay entregas en el rango seleccionado.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- PENDIENTES --}}
      <div class="rounded-2xl bg-white shadow overflow-hidden">
        <div class="px-4 py-3 bg-red-600 text-white font-semibold">Pendientes</div>
        <div class="max-h-[60vh] overflow-auto table-scroll">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-3 text-left">Empleado</th>
                <th class="p-3 text-left">Código</th>
                <th class="p-3 text-left">Departamento</th>
                <th class="p-3 text-left">Puesto</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              @forelse($pendientes as $r)
                <tr class="odd:bg-white even:bg-gray-50">
                  <td class="p-3 font-medium">{{ $r->nombre_completo }}</td>
                  <td class="p-3">{{ $r->codigo_empleado }}</td>
                  <td class="p-3">{{ $r->departamento }}</td>
                  <td class="p-3">{{ $r->puesto }}</td>
                </tr>
              @empty
                <tr><td class="p-4 text-gray-500" colspan="4">¡Todo entregado en el rango seleccionado! 🎉</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection
