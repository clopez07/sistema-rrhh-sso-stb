@extends('layouts.epp')

@section('title', 'EPP obligatorios por puesto')

@section('content')

    <nav class="flex px-5 py-3 text-gray-700 bg-blue-100 rounded-lg" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <svg class="w-4 h-4 mr-2 text-black" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a1 1 0 00-.707.293l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-3h2v3a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7A1 1 0 0010 2z" />
            </svg>
            Inicio
        </a>
        </li>
        <li class="inline-flex items-center">
        <a href="/epp" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            Registro de Entrega de EPP
        </a>
        </li>
        <li>
        <div class="flex items-center">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-black">Ver EPP obligatorios</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">EPP obligatorios por puesto</h1>

    <form id="epp-oblig-form" method="GET" action="{{ route('riesgos.epp.obligatorios') }}" class="grid gap-4 grid-cols-1 md:grid-cols-3 items-end bg-white p-4 rounded-xl shadow">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Puesto de trabajo</label>
            @php
              $__labelSel = $puestoSeleccionado->label ?? '';
            @endphp
            <input type="text" id="puesto-input" list="puestos-list" class="w-full rounded-lg border-gray-300" placeholder="Escribe para buscar..." autocomplete="off" value="{{ $__labelSel }}">
            <datalist id="puestos-list">
                @foreach ($puestos as $p)
                    <option data-token="{{ $p->token }}" value="{{ $p->label }}"></option>
                @endforeach
            </datalist>
            <input type="hidden" name="puesto" id="puesto-id" value="{{ $puestoToken }}">
            <p id="puesto-help" class="text-xs text-gray-500 mt-1">Escribe y selecciona una opcion de la lista.</p>
            <p id="puesto-error" class="text-xs text-red-600 mt-1 hidden">Selecciona un puesto valido de la lista.</p>
            <script>
              document.addEventListener('DOMContentLoaded', function(){
                const input  = document.getElementById('puesto-input');
                const hidden = document.getElementById('puesto-id');
                const list   = document.getElementById('puestos-list');
                const err    = document.getElementById('puesto-error');
                function syncHidden(){
                  const val = (input.value || '').trim();
                  let matched = null;
                  for (const opt of list.options) {
                    if (opt.value === val) { matched = opt; break; }
                  }
                  hidden.value = matched ? (matched.dataset.token || '') : ''; // vacio si no coincide
                  if (err) err.classList.add('hidden');
                }
                input.addEventListener('input', syncHidden);
                input.addEventListener('change', syncHidden);
                const form = document.getElementById('epp-oblig-form');
                form.addEventListener('submit', function(e){
                  syncHidden();
                  if (!hidden.value) {
                    e.preventDefault();
                    if (err) err.classList.remove('hidden');
                    input.focus();
                  }
                });
              });
            </script>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
            <select name="anio" class="w-full rounded-lg border-gray-300" required>
                @foreach ($years as $y)
                    <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3">
            <button class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Buscar
            </button>
            @if($puestoToken)
            <a href="{{ route('riesgos.epp.obligatorios') }}" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                Limpiar
            </a>
            <button type="button" onclick="window.print()" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
                Imprimir
            </button>
            @endif
        </div>
    </form>

    @if($puestoToken)
        <div class="mt-6 space-y-4">
            @if(empty($matriz))
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    No hay EPP obligatorios configurados para este puesto.
                </div>
            @else
                <div class="text-sm text-gray-600">
                    <strong>{{ count($matriz) }}</strong> EPP obligatorios encontrados. Empleados en el puesto: <strong>{{ $empleados->count() }}</strong>. Año: <strong>{{ $anio }}</strong>.
                </div>

                <div class="overflow-x-auto bg-white rounded-xl shadow">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">EPP</th>
                                <th class="px-4 py-3 text-left font-semibold">Entregados</th>
                                <th class="px-4 py-3 text-left font-semibold">Pendientes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($matriz as $row)
                                <tr>
                                    <td class="align-top px-4 py-3 w-1/4">
                                        <div class="font-medium">{{ $row['epp']->equipo }}</div>
                                        <div class="text-xs text-gray-500">
                                            @if($row['epp']->codigo) Código: {{ $row['epp']->codigo }} @endif
                                            @if($row['epp']->marca) · Marca: {{ $row['epp']->marca }} @endif
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Total empleados: {{ $row['total_emp'] }} · Con entrega: {{ count($row['asignados']) }} · Sin entrega: {{ count($row['pendientes']) }}
                                        </div>
                                    </td>

                                    <td class="align-top px-4 py-3">
    @if (count($row['asignados']))
        <div class="flex flex-wrap gap-2">
            @foreach ($row['asignados'] as $item)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-800">
                    {{ $item['empleado']->nombre_completo }}
                    @if(!empty($item['fecha']))
                        <span class="ml-1 text-xs opacity-80">({{ $item['fecha'] }})</span>
                    @endif
                </span>
            @endforeach
        </div>
    @else
        <span class="text-gray-400">—</span>
    @endif
</td>


                                    <td class="align-top px-4 py-3">
                                        @if (count($row['pendientes']))
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($row['pendientes'] as $emp)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-800">
                                                        {{ $emp->nombre_completo }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

