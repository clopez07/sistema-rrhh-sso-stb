@extends('layouts.epp')
@section('title', 'Matriz de EPP requeridos por puesto (anual)')

@section('content')
<style>
  .table-scroll::-webkit-scrollbar{height:8px;width:8px}
  .table-scroll::-webkit-scrollbar-thumb{background:#c7c7d1;border-radius:8px}
  .cell-badge{display:inline-block;padding:.15rem .4rem;border-radius:.5rem;font-weight:600;font-size:.75rem}
  .cell-ok{background:#dcfce7;color:#166534}
  .cell-warn{background:#fee2e2;color:#991b1b}
</style>

<div class="p-6">
  <div class="flex items-end gap-3 mb-4">
    <div>
      <label class="block text-sm font-medium text-gray-600">Buscar puesto (matriz)</label>
      <input type="text" name="puesto" form="filters" value="{{ $buscarPuesto }}" class="mt-1 w-56 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nombre del puesto...">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-600">Buscar EPP</label>
      <input type="text" name="epp" form="filters" value="{{ $buscarEpp }}" class="mt-1 w-56 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Equipo o código...">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-600">Año</label>
      <select name="anio" form="filters" class="mt-1 w-32 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        @foreach ($years as $y)
          <option value="{{ $y }}" @selected($anio==$y)>{{ $y }}</option>
        @endforeach
      </select>
    </div>

    <form id="filters" method="GET" action="{{ route('epp.requeridos') }}">
      <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white px-4 py-2 shadow hover:bg-indigo-700">
        Filtrar
      </button>
    </form>

    <button id="expandCols" class="ml-auto rounded-xl border px-3 py-2 text-sm hover:bg-gray-50">Expandir columnas</button>
  </div>

  @if (!empty($deptOrder))
    <div class="mb-6">
      <h2 class="text-lg font-semibold mb-2">Resumen por departamento ({{ $anio }})</h2>
      <div class="overflow-x-auto bg-white rounded-2xl shadow">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-800 text-white">
            <tr>
              <th class="px-4 py-3 text-left">Departamento</th>
              <th class="px-4 py-3 text-right">Requeridos</th>
              <th class="px-4 py-3 text-right">Entregados</th>
              <th class="px-4 py-3 text-right">Pendientes</th>
              <th class="px-4 py-3 text-right">% Avance</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @foreach ($deptOrder as $dep)
              @php
                $t = $deptTotals[$dep] ?? ['req'=>0,'ent'=>0,'pend'=>0];
                $pct = ($t['req'] ?? 0) > 0 ? round(($t['ent']*100)/max(1,$t['req']), 1) : 0;
              @endphp
              <tr class="odd:bg-white even:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $dep }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($t['req']) }}</td>
                <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ number_format($t['ent']) }}</td>
                <td class="px-4 py-3 text-right text-red-700 font-semibold">{{ number_format($t['pend']) }}</td>
                <td class="px-4 py-3 text-right">{{ $pct }}%</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="bg-slate-100 font-semibold">
            @php
              $pg = $deptGrand ?? ['req'=>0,'ent'=>0,'pend'=>0];
              $pgPct = ($pg['req'] ?? 0) > 0 ? round(($pg['ent']*100)/max(1,$pg['req']), 1) : 0;
            @endphp
            <tr>
              <td class="px-4 py-3 text-right">TOTAL</td>
              <td class="px-4 py-3 text-right">{{ number_format($pg['req']) }}</td>
              <td class="px-4 py-3 text-right text-emerald-700">{{ number_format($pg['ent']) }}</td>
              <td class="px-4 py-3 text-right text-red-700">{{ number_format($pg['pend']) }}</td>
              <td class="px-4 py-3 text-right">{{ $pgPct }}%</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  @endif

  @if (empty($deptOrder))
    <div class="rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-900 p-4">
      No hay puestos con EPP obligatorios que coincidan con tu filtro.
    </div>
  @else
    <div class="relative max-h-[70vh] overflow-auto border rounded-2xl shadow-sm table-scroll">
      <table class="min-w-full text-sm">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="sticky top-0 left-0 z-40 p-3 text-left bg-indigo-600">Departamento</th>
            <th class="sticky top-0 z-30 p-3 text-left min-w-[200px] border-l border-indigo-500 bg-indigo-600">Puesto de trabajo</th>
            <th class="sticky top-0 z-30 p-3 text-center min-w-[120px] border-l border-indigo-500 bg-indigo-600">Total Empleados</th>
            @foreach($epps as $col)
              <th class="sticky top-0 z-30 p-3 text-center min-w-[180px] border-l border-indigo-500 bg-indigo-600">
                <div class="flex flex-col items-center gap-1">
                  <span class="font-semibold leading-tight">{{ $col->equipo }}</span>
                  <span class="text-[11px] opacity-90">{{ $col->codigo }}</span>
                  <span class="text-[11px] opacity-90">(Entregados / Requeridos)</span>
                </div>
              </th>
            @endforeach
          </tr>
        </thead>

        <tbody>
          @foreach($deptOrder as $dep)
            @php
              $empDept = $deptEmp[$dep] ?? 0;
              $dt = $deptTotals[$dep] ?? ['req'=>0,'ent'=>0,'pend'=>0];
            @endphp
            <tr class="bg-slate-100 font-semibold">
              <td class="sticky left-0 z-20 p-3 text-gray-900 border-r">{{ $dep }}</td>
              <td class="p-3 text-left border-l">
                Subtotal departamento
                <div class="mt-1 text-xs flex flex-wrap gap-2">
                  <span class="cell-badge cell-ok">Ent./Req.: {{ $dt['ent'] }} / {{ $dt['req'] }}</span>
                  <span class="cell-badge cell-warn">Pend./Req.: {{ $dt['pend'] }} / {{ $dt['req'] }}</span>
                </div>
              </td>
              <td class="p-3 text-center border-l">{{ $empDept }}</td>
              @foreach($epps as $col)
                @php $dcell = $deptPivot[$dep][$col->id_epp] ?? null; @endphp
                <td class="p-2 text-center align-middle border-l">
                  @if (is_null($dcell) || ($dcell['req'] ?? 0) === 0)
                    &nbsp;
                  @else
                    <div class="cell-badge {{ ($dcell['pend'] ?? 0) > 0 ? 'cell-warn' : 'cell-ok' }}">
                      Pend.: {{ $dcell['pend'] }} / {{ $dcell['req'] }}
                    </div>
                  @endif
                </td>
              @endforeach
            </tr>

            @foreach(($deptPuestos[$dep] ?? []) as $item)
              @php
                $row = $item['row'];
                $rowId = $item['id'];
                $reqPuesto = $totEmpleados[$rowId] ?? 0;
              @endphp
              <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50/40">
                <td class="sticky left-0 z-10 p-3 text-gray-500 border-r">&nbsp;</td>
                <td class="p-3 text-left border-l font-medium text-gray-800">{{ $row->puesto_trabajo_matriz_matriz }}</td>
                <td class="p-3 text-center border-l font-semibold">{{ $reqPuesto }}</td>
                @foreach($epps as $col)
                  @php $cell = $pivot[$rowId][$col->id_epp] ?? null; @endphp
                  <td class="p-2 text-center align-middle border-l">
                    @if (is_null($cell))
                      &nbsp;
                    @else
                      @php $ok = $cell['pend'] === 0 && $cell['req'] > 0; @endphp
                      <div class="cell-badge {{ $ok ? 'cell-ok' : 'cell-warn' }}">
                        {{ $cell['ent'] }} / {{ $cell['req'] }}
                      </div>
                      <div class="text-xs mt-1 {{ $cell['pend']>0 ? 'text-red-700' : 'text-green-700' }}">
                        Pend.: {{ $cell['pend'] }}
                      </div>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          @endforeach

          <tr class="bg-gray-200 font-semibold">
            <td class="p-3 text-right" colspan="3">TOTAL GENERAL</td>
            @foreach($epps as $col)
              @php $t = $totales[$col->id_epp] ?? ['req'=>0,'ent'=>0,'pend'=>0]; @endphp
              <td class="p-2 text-center border-l">
                <div>{{ $t['ent'] }} / {{ $t['req'] }}</div>
                <div class="text-xs">Pend.: {{ $t['pend'] }}</div>
              </td>
            @endforeach
          </tr>
        </tbody>
      </table>
    </div>
  @endif
</div>

<script>
  document.getElementById('expandCols')?.addEventListener('click', () => {
    document.querySelectorAll('thead th, tbody td').forEach(c => c.classList.toggle('min-w-[180px]'));
  });
</script>
@endsection
