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

  <h2 class="text-xl font-semibold text-gray-800 mb-4">Riesgo Físico por Puesto de Trabajo</h2>

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
      <label class="block text-sm font-medium text-gray-600">Buscar</label>
      <input type="text" name="q" form="filters" value="{{ $buscar }}" class="mt-1 w-64 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Tipo, descripción, equipo, etc.">
    </div>
    <form id="filters" method="GET" action="{{ route('riesgos.fisico.puesto') }}">
      <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white px-4 py-2 shadow hover:bg-indigo-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10.5 3.75a6.75 6.75 0 015.306 10.98l4.232 4.232a.75.75 0 11-1.06 1.06l-4.232-4.232A6.75 6.75 0 1110.5 3.75zm0 1.5a5.25 5.25 0 100 10.5 5.25 5.25 0 000-10.5z"/></svg>
        Consultar
      </button>
    </form>
  </div>

  @if(!$puestoId)
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3">Selecciona un puesto para ver su esfuerzo físico asociado.</div>
  @else

    {{-- ===== ESFUERZO FÍSICO (CARGAR/HALAR/EMPUJAR/SUJETAR) ===== --}}
    <div class="relative max-h-[70vh] overflow-auto border rounded-2xl shadow-sm table-scroll">
      <div class="bg-indigo-200 text-black px-4 py-2 font-semibold text-center uppercase">
        Esfuerzo Físico
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[160px] border-l border-indigo-500">Tipo de Esfuerzo</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[220px] border-l border-indigo-500">Descripción de Carga</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[200px] border-l border-indigo-500">Equipo de Apoyo</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[160px] border-l border-indigo-500">Duración</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[160px] border-l border-indigo-500">Distancia</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[160px] border-l border-indigo-500">Frecuencia</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[140px] border-l border-indigo-500">Peso aprox.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50/40">
              <td class="p-3 border-l align-top">
                <div class="font-medium">{{ $r['tipo'] ?? '—' }}</div>
              </td>
              <td class="p-3 border-l align-top">{{ $r['descripcion'] ?? '—' }}</td>
              <td class="p-3 border-l align-top">{{ $r['equipo'] ?? '—' }}</td>
              <td class="p-3 border-l align-top">{{ $r['duracion'] ?? '—' }}</td>
              <td class="p-3 border-l align-top">{{ $r['distancia'] ?? '—' }}</td>
              <td class="p-3 border-l align-top">{{ $r['frecuencia'] ?? '—' }}</td>
              <td class="p-3 border-l align-top">{{ $r['peso'] ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="p-6 text-center text-gray-500">No se encontraron registros con los filtros aplicados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ===== ESFUERZO VISUAL (ident_esfuerzo_visual) ===== --}}
    <div class="mt-6 border rounded-2xl overflow-hidden shadow-sm">
      <div class="bg-indigo-200 text-black px-4 py-2 font-semibold text-center uppercase">
        Esfuerzo Visual
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="p-3 text-left min-w-[240px]">Tipo de esfuerzo</th>
            <th class="p-3 text-left min-w-[200px] border-l">Tiempo de exposición</th>
          </tr>
        </thead>
        <tbody>
          @forelse($visualRows as $v)
            <tr class="odd:bg-white even:bg-gray-50">
              <td class="p-3">{{ $v->tipo ?? '—' }}</td>
              <td class="p-3 border-l">{{ $v->tiempo ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="2" class="p-6 text-center text-gray-500">Sin registros de esfuerzo visual.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ===== EXPOSICIÓN A RUIDO (ident_exposicion_ruido) ===== --}}
    <div class="mt-6 border rounded-2xl overflow-hidden shadow-sm">
      <div class="bg-indigo-200 text-black px-4 py-2 font-semibold text-center uppercase">
        Exposición a Ruido
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="p-3 text-left min-w-[260px]">Descripción de Ruido</th>
            <th class="p-3 text-left min-w-[200px] border-l">Duración de exposición</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ruidoRows as $r)
            <tr class="odd:bg-white even:bg-gray-50">
              <td class="p-3">{{ $r->descripcion ?? '—' }}</td>
              <td class="p-3 border-l">{{ $r->duracion ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="2" class="p-6 text-center text-gray-500">Sin registros de exposición a ruido.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ===== ESTRÉS TÉRMICO (ident_estres_termico) ===== --}}
    <div class="mt-6 border rounded-2xl overflow-hidden shadow-sm">
      <div class="bg-indigo-200 text-black px-4 py-2 font-semibold text-center uppercase">
        Exposición Stress Térmico
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="p-3 text-left min-w-[260px]">Descripción de Stress térmico</th>
            <th class="p-3 text-left min-w-[200px] border-l">Duración de exposición</th>
          </tr>
        </thead>
        <tbody>
          @forelse($termicoRows as $t)
            <tr class="odd:bg-white even:bg-gray-50">
              <td class="p-3">{{ $t->descripcion ?? '—' }}</td>
              <td class="p-3 border-l">{{ $t->duracion ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="2" class="p-6 text-center text-gray-500">Sin registros de estrés térmico.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  @endif
</div>

@endsection
