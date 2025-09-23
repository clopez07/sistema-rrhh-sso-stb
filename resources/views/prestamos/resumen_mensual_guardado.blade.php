@extends('layouts.prestamos')
@section('title','Resumen mensual (guardado)')

@section('content')
<div class="p-4 space-y-4">

  {{-- Selector de año y formulario de guardado --}}
  <div class="flex flex-wrap items-end gap-3">
    <form method="GET" action="{{ route('prestamos.resumen.index') }}" class="flex items-end gap-2">
      <div>
        <label class="block text-xs text-gray-600">Año</label>
        <input type="number" name="anio" value="{{ $anio }}" class="border rounded-lg px-3 py-2 w-28" min="2000" max="2100">
      </div>
      <button class="px-4 py-2 rounded-lg text-white" style="background:#00B0F0">Ver</button>
    </form>

    <form method="POST" action="{{ route('prestamos.resumen.store') }}" class="flex flex-wrap items-end gap-2 ml-auto">
      @csrf
      <input type="hidden" name="anio" value="{{ $anio }}">
      <div>
        <label class="block text-xs text-gray-600">Rango: Desde</label>
        <input type="date" name="desde" class="border rounded-lg px-3 py-2" required>
      </div>
      <div>
        <label class="block text-xs text-gray-600">Hasta</label>
        <input type="date" name="hasta" class="border rounded-lg px-3 py-2" required>
      </div>
      <div>
        <label class="block text-xs text-gray-600">Mes a guardar</label>
        <select name="mes" class="border rounded-lg px-3 py-2" required>
          @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}">{{ sprintf('%02d',$m) }} - {{ ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$m-1] }}</option>
          @endfor
        </select>
      </div>
      <button class="px-4 py-2 rounded-lg text-white" style="background:#00B0F0">Guardar resumen</button>
    </form>
  </div>

  @if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-green-800">
      {{ session('success') }}
    </div>
  @endif

  {{-- Tabla por meses --}}
  <div class="rounded-xl border overflow-hidden">
    <div class="px-4 py-3 font-semibold text-white" style="background:#00B0F0">
      RESUMEN MENSUAL - ANUAL {{ $anio }}
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left text-gray-700">
        <thead class="text-xs uppercase bg-gray-50">
          <tr>
            <th class="px-3 py-2">MES</th>
            <th class="px-3 py-2">CAPITAL</th>
            <th class="px-3 py-2">INTERES</th>
            <th class="px-3 py-2">TOTAL MENSUAL PLANILLA</th>
            <th class="px-3 py-2">PRESTACIONES/ DÉCIMO/ AGUINALDO</th>
            <th class="px-3 py-2">DEPOSITOS</th>
            <th class="px-3 py-2">TOTAL MENSUAL</th>
            <th class="px-3 py-2">ACCIONES</th>
          </tr>
        </thead>
        <tbody>
          @php
            $mesN = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
          @endphp

          @for($m=1;$m<=12;$m++)
            @php
              $r = $meses[$m];
              $label = ($mesN[$m] ?? sprintf('%02d',$m)).'-'.substr((string)$anio, -2);
              $capital   = $r->capital ?? 0;
              $interes   = $r->interes ?? 0;
              $planilla  = $r->planilla_total ?? 0;
              $extras    = $r->extras_total ?? 0;
              $deps      = $r->depositos_total ?? 0;
              $total     = $r->total_mensual ?? 0;
              $exd       = isset($r->extras_detalle) ? json_decode($r->extras_detalle, true) : [];
              $dpd       = isset($r->depositos_detalle) ? json_decode($r->depositos_detalle, true) : [];
            @endphp
            <tr class="border-b">
              <td class="px-3 py-2">{{ $label }}</td>
              <td class="px-3 py-2">L {{ number_format($capital,2) }}</td>
              <td class="px-3 py-2">L {{ number_format($interes,2) }}</td>
              <td class="px-3 py-2">L {{ number_format($planilla,2) }}</td>

              <td class="px-3 py-2">
                <div class="flex items-start gap-2">
                  <span>L {{ number_format($extras,2) }}</span>
                  @if($exd && count($exd))
                    <button type="button"
                      class="text-xs px-2 py-0.5 rounded border hover:bg-gray-100"
                      data-toggle="#ex-{{ $anio }}-{{ $m }}">ver</button>
                  @endif
                </div>
                @if($exd && count($exd))
                  <div id="ex-{{ $anio }}-{{ $m }}" class="hidden mt-2 bg-orange-50 border border-orange-200 rounded-lg p-2">
                    <ul class="space-y-1 text-xs">
                      @foreach($exd as $it)
                        <li>• {{ $it['empleado'] }} — L {{ number_format($it['monto'] ?? 0,2) }}
                          @if(!empty($it['label'])) <span class="text-gray-500">({{ $it['label'] }})</span>@endif
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              </td>

              <td class="px-3 py-2">
                <div class="flex items-start gap-2">
                  <span>L {{ number_format($deps,2) }}</span>
                  @if($dpd && count($dpd))
                    <button type="button"
                      class="text-xs px-2 py-0.5 rounded border hover:bg-gray-100"
                      data-toggle="#dp-{{ $anio }}-{{ $m }}">ver</button>
                  @endif
                </div>
                @if($dpd && count($dpd))
                  <div id="dp-{{ $anio }}-{{ $m }}" class="hidden mt-2 bg-blue-50 border border-blue-200 rounded-lg p-2">
                    <ul class="space-y-1 text-xs">
                      @foreach($dpd as $it)
                        <li>• {{ $it['empleado'] }} — L {{ number_format($it['monto'] ?? 0,2) }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              </td>

              <td class="px-3 py-2 font-semibold" style="background:#D7F0D0">
                L {{ number_format($total,2) }}
              </td>
              <td class="px-3 py-2">
  @if($r)
    <div class="flex gap-2">
      <form method="POST" action="{{ route('prestamos.resumen.recalcular') }}">
        @csrf
        <input type="hidden" name="anio" value="{{ $anio }}">
        <input type="hidden" name="mes" value="{{ $m }}">
        <button class="text-xs px-2 py-1 rounded border text-green-700 border-green-300 hover:bg-green-50">
          Recalcular
        </button>
      </form>
      <form method="POST" action="{{ route('prestamos.resumen.eliminar') }}"
            onsubmit="return confirm('¿Eliminar {{ $mesN[$m] ?? $m }}-{{ $anio }}?')">
        @csrf
        <input type="hidden" name="anio" value="{{ $anio }}">
        <input type="hidden" name="mes" value="{{ $m }}">
        <button class="text-xs px-2 py-1 rounded border text-red-700 border-red-300 hover:bg-red-50">
          Eliminar
        </button>
      </form>
    </div>
  @else
    <span class="text-xs text-gray-400">—</span>
  @endif
</td>
            </tr>
          @endfor

          {{-- Totales anuales de lo guardado --}}
          <tr class="font-bold" style="background:#E6F4FF">
            <td class="px-3 py-2">TOTAL ANUAL</td>
            <td class="px-3 py-2">L {{ number_format($totales->capital ?? 0,2) }}</td>
            <td class="px-3 py-2">L {{ number_format($totales->interes ?? 0,2) }}</td>
            <td class="px-3 py-2">L {{ number_format($totales->planilla_total ?? 0,2) }}</td>
            <td class="px-3 py-2">L {{ number_format($totales->extras_total ?? 0,2) }}</td>
            <td class="px-3 py-2">L {{ number_format($totales->depositos_total ?? 0,2) }}</td>
            <td class="px-3 py-2">L {{ number_format($totales->total_mensual ?? 0,2) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  // Mostrar/ocultar detalle por mes
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-toggle]');
    if(!btn) return;
    const sel = btn.getAttribute('data-toggle');
    const el = document.querySelector(sel);
    if(el) el.classList.toggle('hidden');
  });
</script>
@endsection
