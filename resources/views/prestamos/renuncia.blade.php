@extends('layouts.prestamos')

@section('content')
<div class="max-w-6xl mx-auto p-4 md:p-6">
  <h1 class="text-xl font-semibold mb-4">Renuncia de empleado - Generar cuota final</h1>

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2">
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
      {{ session('error') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
      <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Buscador --}}
  <form method="GET" action="{{ route('prestamos.renuncia.form') }}" class="mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
    <div class="md:col-span-3">
      <label class="block text-sm font-medium text-gray-700 mb-1">Buscar (nombre, código o # préstamo)</label>
      <input type="text" name="q" value="{{ $q ?? '' }}" list="prestamos_list"
       placeholder="Ej. Juan Pérez, 00123, 4509"
       class="w-full border rounded-lg px-3 py-2">

        <datalist id="prestamos_list">
          @foreach($prestamosList as $p)
            <option
              value="{{ $p->nombre_completo }} (#{{ $p->num_prestamo }})"
              data-id="{{ $p->id_prestamo }}">
            </option>
          @endforeach
        </datalist>
      <input type="hidden" name="id_prestamo" id="id_prestamo" value="{{ $id_prestamo ?? '' }}">
      <p class="text-xs text-gray-500 mt-1">Sugerencias muestran nombre y # de préstamo. Se toma el préstamo activo más reciente.</p>
    </div>
    <div class="md:col-span-2 flex items-end">
      <button class="px-4 py-2 rounded-lg bg-blue-700 text-white">Buscar</button>
    </div>
        <div class="md:col-span-2 flex items-end">
    <button type="button" id="btn-limpiar" class="px-4 py-2 rounded-lg bg-blue-700 text-white">
  Limpiar búsqueda
</button>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('input[list="prestamos_list"]');
  const list  = document.getElementById('prestamos_list');
  const hid   = document.getElementById('id_prestamo');
  const btnClear = document.getElementById('btn-limpiar');

  function syncId() {
    const val = input.value.trim();
    const opt = Array.from(list.options).find(o => o.value === val);
    if (opt) {
      hid.value = opt.dataset.id || '';
    } else {
      hid.value = '';
    }
  }

  input.addEventListener('change', syncId);
  input.addEventListener('blur', syncId);
  input.addEventListener('input', () => { hid.value = ''; });

  // Para volver a ver todas las sugerencias rápido
  if (btnClear) {
    btnClear.addEventListener('click', () => {
      input.value = '';
      hid.value = '';
      input.focus();
    });
  }
});
</script>
  </form>

  @if($seleccion)
  {{-- Resumen préstamo --}}
  <div class="bg-white border rounded-lg p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
      <div>
        <div class="text-gray-500">Empleado</div>
        <div class="font-medium">{{ $seleccion->nombre_completo }} ({{ $seleccion->codigo_empleado }})</div>
      </div>
      <div>
        <div class="text-gray-500"># Préstamo</div>
        <div class="font-medium">#{{ $seleccion->num_prestamo }}</div>
      </div>
      <div>
        <div class="text-gray-500">Planilla</div>
        <div class="font-medium">{{ $seleccion->planilla ?? '—' }}</div>
      </div>

      <div>
        <div class="text-gray-500">Monto</div>
        <div class="font-medium">L {{ number_format($seleccion->monto,2) }}</div>
      </div>
      <div>
        <div class="text-gray-500">Intereses</div>
        <div class="font-medium">L {{ number_format($seleccion->total_intereses,2) }}</div>
      </div>
      <div>
        <div class="text-gray-500">Estado</div>
        <div class="font-medium">{{ (int)$seleccion->estado_prestamo === 1 ? 'Activo' : 'Inactivo' }}</div>
      </div>
    </div>
  </div>

  {{-- Editor de cuota final --}}
  <form method="POST" action="{{ route('prestamos.renuncia.confirmar') }}" class="bg-white border rounded-lg p-4 mb-6">
    @csrf
    <input type="hidden" name="id_prestamo" value="{{ $seleccion->id_prestamo }}">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Capital pendiente (editable)</label>
        <input type="number" step="0.01" name="cap_final"
               value="{{ number_format($resumen['pend_cap'] ?? 0, 2, '.', '') }}"
               class="w-full border rounded-lg px-3 py-2">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Intereses pendientes (editable)</label>
        <input type="number" step="0.01" name="int_final"
               value="{{ number_format($resumen['pend_int'] ?? 0, 2, '.', '') }}"
               class="w-full border rounded-lg px-3 py-2">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cobrar de</label>
        <select name="origen" class="w-full border rounded-lg px-3 py-2">
          <option value="prestaciones">Prestaciones</option>
          <option value="liquidacion">Liquidación</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de la cuota final</label>
        <input type="date" name="fecha_final" value="{{ date('Y-m-d') }}"
               class="w-full border rounded-lg px-3 py-2">
      </div>

      <div class="md:col-span-2 flex items-center gap-2">
        <input id="cerrar" type="checkbox" name="cerrar" value="1" class="w-4 h-4">
        <label for="cerrar" class="text-sm text-gray-700">
          Marcar como pagado y <strong>cerrar préstamo</strong> (deja saldo/intereses restantes en 0)
        </label>
      </div>
    </div>

    <div class="mt-4 flex justify-end gap-2">
      <button type="submit" class="px-4 py-2 rounded-lg bg-blue-700 text-white">
        Generar cuota final por renuncia
      </button>
    </div>

    @if($resumen)
      <p class="mt-3 text-xs text-gray-500">
        Cuotas pendientes detectadas: <strong>{{ $resumen['pend_cuotas'] }}</strong>.
        Al confirmar se borrarán y se creará una única cuota final con los montos indicados arriba.
      </p>
    @endif
  </form>

  {{-- Historial --}}
  <div class="bg-white border rounded-lg p-4">
    <h2 class="font-semibold mb-3">Historial de cuotas</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left">#</th>
            <th class="px-3 py-2 text-left">Fecha</th>
            <th class="px-3 py-2 text-left">Capital</th>
            <th class="px-3 py-2 text-left">Interés</th>
            <th class="px-3 py-2 text-left">Total</th>
            <th class="px-3 py-2 text-left">Pagado</th>
            <th class="px-3 py-2 text-left">Motivo</th>
            <th class="px-3 py-2 text-left">Obs</th>
          </tr>
        </thead>
        <tbody>
          @forelse($historial as $h)
            <tr class="border-b">
              <td class="px-3 py-2">{{ $h->num_cuota }}</td>
              <td class="px-3 py-2">{{ $h->fecha_programada }}</td>
              <td class="px-3 py-2">L {{ number_format($h->abono_capital,2) }}</td>
              <td class="px-3 py-2">L {{ number_format($h->abono_intereses,2) }}</td>
              <td class="px-3 py-2">L {{ number_format($h->cuota_quincenal,2) }}</td>
              <td class="px-3 py-2">{{ $h->pagado ? 'Sí' : 'No' }}</td>
              <td class="px-3 py-2">{{ $h->motivo }}</td>
              <td class="px-3 py-2">{{ $h->observaciones }}</td>
            </tr>
          @empty
            <tr><td class="px-3 py-2 text-gray-500" colspan="8">Sin cuotas registradas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>

{{-- JS: toma el id_prestamo del datalist si se selecciona una opción completa --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('input[list="prestamos_list"]');
  const list  = document.getElementById('prestamos_list');
  const hid   = document.getElementById('id_prestamo');

  function syncId() {
    const val = input.value.trim();
    const opt = Array.from(list.options).find(o => o.value === val);
    if (opt) { hid.value = opt.dataset.id || ''; }
  }
  input.addEventListener('change', syncId);
  input.addEventListener('blur', syncId);
});
</script>
@endsection
