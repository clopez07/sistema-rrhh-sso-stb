@extends('layouts.prestamos')

@section('title', 'Información de Prestamos')

@section('content')

    <nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
            </svg>
            Inicio
        </a>
        </li>
        <li aria-current="page">
        <div class="flex items-center">
            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
            </svg>
            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2">Prestamos</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>   
    
    @if (session('error'))
  <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
    {{ session('error') }}
  </div>
@endif

@if (session('success'))
  <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-800">
    {{ session('success') }}
  </div>
@endif

@if ($errors->any())
  <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-800">
    <ul class="list-disc ms-5">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="flex items-center justify-between w-full mb-4">
    <!-- Botones a la izquierda -->
    <div class="inline-flex rounded-md shadow-xs" role="group">
            <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 12h14m-7 7V5" />
                </svg>
                Agregar
            </button>

<!-- MODAL: Nuevo Préstamo (mismos campos; estilos del modal que enviaste) -->
<div id="create-modal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50
            justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="relative p-4 w-full max-w-6xl max-h-full">
    <!-- Contenido del modal -->
    <div class="relative bg-white rounded-lg shadow-sm">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Ingresar nuevo préstamo</h3>
        <button type="button"
                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg
                       text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                data-modal-toggle="create-modal" aria-label="Cerrar">
          <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <form class="p-4 md:p-5" action="{{ route('infoprestamo.storeprestamo') }}" method="POST">
        @csrf

  <!-- Checkbox refinanciamiento -->
<label class="inline-flex items-center gap-2 text-sm font-medium text-gray-900">
  <input type="checkbox" id="es_refinanciamiento" name="es_refinanciamiento"
         class="w-4 h-4 text-blue-700 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
  Préstamo con Refinanciamiento
</label>

<!-- Sección: Intereses a cancelar (queda oculta hasta marcar refinanciamiento) -->
<div id="refi_int_section" class="mt-3 hidden">
  <div class="text-sm font-medium text-gray-900 mb-2">Intereses a cancelar</div>

  <div class="flex flex-wrap items-center gap-6">
    <label class="inline-flex items-center gap-2">
      <input type="radio" id="refi_int_tipo_todos" name="refi_int_tipo" value="todos" checked>
      Totales
    </label>

    <label class="inline-flex items-center gap-2">
      <input type="radio" id="refi_int_tipo_parcial" name="refi_int_tipo" value="parcial">
      Parciales
    </label>

    <label class="inline-flex items-center gap-2">
      <input type="radio" id="refi_int_tipo_ninguno" name="refi_int_tipo" value="ninguno">
      Ninguno
    </label>

    <div class="flex items-center gap-2">
      <span class="text-sm text-gray-600">Monto (si es parcial)</span>
      <input type="text" id="refi_int_monto" name="refi_int_monto"
             placeholder="Ej. 1250.75"
             class="border rounded px-3 py-2 w-40">
    </div>
  </div>

  <p class="text-xs text-gray-500 mt-1">
    Este ajuste solo impacta la última cuota del préstamo <strong>cancelado</strong>.
  </p>
</div>

  <!-- Datos principales -->
  <div class="grid gap-4 mb-4 grid-cols-1 md:grid-cols-3">
    <!-- Número préstamo -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Número Préstamo</label>
      <input type="text" name="numero_prestamo"
             class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>

<!-- Código empleado (con datalist) -->
<div class="col-span-1">
  <label class="block mb-2 text-sm font-medium text-gray-900">Código empleado</label>

  <input
    type="text"
    id="codigo_empleado"
    list="empleados_list"
    placeholder="Seleccione un Código"
    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
           focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
    required
    autocomplete="off"
  >

  <datalist id="empleados_list">
    @foreach($empleados as $empleado)
      <option
        value="{{ $empleado->codigo_empleado }}"
        data-id="{{ $empleado->id_empleado }}"
        data-nombre="{{ $empleado->nombre_completo }}"
      ></option>
    @endforeach
  </datalist>

  <!-- Se envía al backend -->
  <input type="hidden" name="id_empleado" id="id_empleado">
</div>

<!-- Nombre empleado (solo visual) -->
<div class="col-span-1">
  <label class="block mb-2 text-sm font-medium text-gray-900">Nombre empleado</label>
  <input
    type="text"
    id="nombre_empleado"
    name="nombre_empleado"
    readonly
    class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg
           focus:ring-0 focus:border-gray-300 block w-full p-2.5"
  />
</div>

<script>
(() => {
  const inputCodigo = document.getElementById('codigo_empleado');
  const list        = document.getElementById('empleados_list');
  const hiddenId    = document.getElementById('id_empleado');
  const inputNombre = document.getElementById('nombre_empleado');

  function syncFromInput() {
    const val = inputCodigo.value.trim();
    const opt = Array.from(list.options).find(o => o.value === val);

    if (opt) {
      hiddenId.value    = opt.dataset.id || '';
      inputNombre.value = opt.dataset.nombre || '';
      inputCodigo.setCustomValidity('');
    } else {
      hiddenId.value    = '';
      inputNombre.value = '';
      inputCodigo.setCustomValidity('Seleccione un código válido de la lista');
    }
  }

  inputCodigo.addEventListener('change', syncFromInput);
  inputCodigo.addEventListener('blur', syncFromInput);
  inputCodigo.addEventListener('input', () => {
    hiddenId.value    = '';
    inputNombre.value = '';
    inputCodigo.setCustomValidity('');
  });

  const form = inputCodigo.closest('form');
  if (form) {
    form.addEventListener('submit', (e) => {
      syncFromInput();
      if (!hiddenId.value) e.preventDefault();
    });
  }
})();
</script>
    <!-- Monto prestado -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Monto prestado</label>
      <input type="number" step="0.01" name="monto_prestado"
             class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>

    <!-- Porcentaje interés -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Porcentaje Interés</label>
      <div class="relative">
      <input type="number" step="0.01" name="porcentaje_interes"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                      focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10"/>
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-500">%</span>
      </div>
    </div>

        <!-- Total de intereses -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Total de intereses</label>
      <input type="number" step="0.01" name="total_intereses"
             class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>

        <!-- Cuota mensual -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Capital mensual</label>
      <input type="number" step="0.01" name="cuota_mensual"
             class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>

    <div id="solo-interes-box-main" class="col-span-1 md:col-span-2 hidden bg-blue-50 border border-blue-200 rounded-lg p-3">
      <span class="block text-sm font-semibold text-blue-900 mb-2">Plan de pago solo intereses</span>
      <div class="flex flex-wrap items-center gap-4 text-sm text-blue-900">
        <label class="inline-flex items-center gap-2">
          <input type="radio" name="solo_interes_opcion_main" value="total" class="text-blue-600 focus:ring-blue-500" checked>
          Pagar en cuotas intereses totales
        </label>
        <label class="inline-flex items-center gap-2">
          <input type="radio" name="solo_interes_opcion_main" value="parcial" class="text-blue-600 focus:ring-blue-500">
          Pagar en cuotas intereses parciales
        </label>
      </div>
      <div id="solo-interes-parcial-main" class="mt-3 hidden">
        <label class="block text-sm font-medium text-gray-900">Monto parcial a distribuir</label>
        <input type="number" step="0.01" min="0" id="solo_interes_parcial_monto_main" class="mt-1 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Ej. 1500.00">
      </div>
      <p id="solo-interes-resumen-main" class="mt-3 text-xs text-blue-800">Ingresá el plazo y el total de intereses para mostrar el cálculo.</p>
    </div>
    <input type="hidden" name="solo_interes_modo" id="solo_interes_modo_main">
    <input type="hidden" name="solo_interes_monto" id="solo_interes_monto_main">

    <!-- Plazo del préstamo -->
    <div class="col-span-1">
    <label class="block mb-2 text-sm font-medium text-gray-900">Plazo del Préstamo</label>
    <input type="number" name="plazo_prestamo"
            step="0.1" min="0" inputmode="decimal" placeholder="Ej. 30.5"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>

        <!-- Planilla -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Planilla</label>
      <select name="planilla"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                     focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
        <option value="">Seleccione…</option>
        @foreach($planilla as $planillas)
                                    <option value="{{ $planillas->id_planilla }}">{{ $planillas->planilla }}</option>
                                @endforeach
      </select>
    </div>

    <!-- Fecha aprobación -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Fecha aprobación</label>
      <input type="date" name="fecha_aprobacion"
             class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>

    <!-- Fecha primera cuota -->
    <div class="col-span-1">
      <label class="block mb-2 text-sm font-medium text-gray-900">Fecha primera cuota</label>
      <input type="date" name="fecha_primera_cuota"
             class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"/>
    </div>
    <input type="hidden" name="estado_prestamo" value="1" />
</div>

<!-- Depositos + Cobros extraordinarios (lado a lado) -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6" style="background-color: #f9f9f9; padding: 15px; border-radius: 8px;">

  <!-- ===================== DEPÓSITOS ===================== -->
  <div class="space-y-3">
    <label class="block mb-2 text-sm font-medium text-gray-900">Depósitos directos</label>

    <div class="flex items-center gap-2">
      <input id="depositos_si" type="checkbox" name="depositos_si" value="1"
             class="w-4 h-4 border-gray-300 rounded"
             onclick="toggleDepositos(this.checked)">
      <label for="depositos_si" class="text-sm text-gray-700">Aplicar depósitos directos</label>
    </div>

    <div id="depositos_box" class="space-y-3 hidden">
      <div class="flex items-center gap-4">
        <label class="inline-flex items-center gap-2">
          <input type="radio" name="deposito_unico" value="1" class="w-4 h-4"
                 onclick="setDepositoModo('unico')">
          <span class="text-sm text-gray-700">Depósito único</span>
        </label>
        <label class="inline-flex items-center gap-2">
          <input type="radio" name="deposito_varios" value="1" class="w-4 h-4"
                 onclick="setDepositoModo('varios')">
          <span class="text-sm text-gray-700">Varios depósitos (mensuales)</span>
        </label>
      </div>

      <!-- Monto depósito único -->
      <div id="deposito_unico_box" class="hidden">
        <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Monto del depósito único</label>
        <input type="number" step="0.01" min="0" name="deposito_unico_monto"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                      focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
               placeholder="0.00">
      </div>
      <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Fecha del depósito único</label>
        <input type="date" step="0.01" min="0" name="deposito_unico_fecha"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                      focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
               placeholder="0.00">
      </div>
      </div>

      <!-- Varios depósitos -->
      <div id="deposito_varios_box" class="grid grid-cols-2 gap-3 hidden">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900">Cantidad Total en Depósitos</label>
          <input type="number" step="0.01" min="0" name="depositos_total"
                 class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                        focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
                 placeholder="Ej. 82896.88">
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900">Fecha primer depósito</label>
          <input type="date" step="0.01" min="0" name="depositos_fecha_inicio"
                 class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                        focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
                 placeholder="0.00">
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900">Plazo (meses)</label>
          <input type="number" step="0.01" min="0" name="depositos_plazo"
                 class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                        focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
                 placeholder="Ej. 6">
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900">Monto por depósito</label>
          <input type="number" step="0.01" min="0" name="depositos_cuota"
                 class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                        focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"
                 placeholder="0.00">
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== COBROS EXTRAORDINARIOS (MÚLTIPLES) ===================== -->
  <div class="space-y-3">
    <label class="block mb-2 text-sm font-medium text-gray-900">Cobros extraordinarios</label>

    <div class="flex items-center gap-2">
      <input id="cobro_extraordinario" type="checkbox" name="cobro_extraordinario" value="1"
             class="w-4 h-4 border-gray-300 rounded"
             onclick="toggleExtras(this.checked)">
      <label for="cobro_extraordinario" class="text-sm text-gray-700">
        Aplicar cobros extraordinarios (puedes agregar varios por tipo)
      </label>
    </div>

    <div id="extras_box" class="hidden">
      <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left">Tipo</th>
              <th class="px-3 py-2 text-left">Periodo / Nota</th>
              <th class="px-3 py-2 text-left">Monto</th>
              <th class="px-3 py-2"></th>
            </tr>
          </thead>
          <tbody id="extras-multi-body">
            <!-- Fila inicial -->
            <tr>
              <td class="px-3 py-2">
                <select name="extras_multi[0][tipo]" class="w-full border-gray-300 rounded-lg p-2.5">
                  <option value="" selected disabled>Seleccione</option>
                  <option value="decimo">Décimo</option>
                  <option value="aguinaldo">Aguinaldo</option>
                  <option value="prestaciones">Prestaciones</option>
                  <option value="liquidacion">Liquidación</option>
                </select>
              </td>
              <td class="px-3 py-2">
                <input type="text" name="extras_multi[0][periodo]" placeholder="Ej. 2025"
                       class="w-full border-gray-300 rounded-lg p-2.5">
              </td>
              <td class="px-3 py-2">
                <input type="number" step="0.01" min="0" name="extras_multi[0][monto]" placeholder="0.00"
                       class="w-full border-gray-300 rounded-lg p-2.5">
              </td>
              <td class="px-3 py-2">
                <button type="button" class="px-3 py-2 rounded-lg border" onclick="removeExtraRow(this)">Quitar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-2">
        <button type="button" class="px-4 py-2 rounded-lg bg-gray-100 border" onclick="addExtraRow()">
          + Agregar otro cobro
        </button>
      </div>
    </div>
  </div>

</div>

<script>
  // ====== DEPÓSITOS ======
  function toggleDepositos(on) {
    const box = document.getElementById('depositos_box');
    box.classList.toggle('hidden', !on);
    if (!on) {
      // limpiar modo
      document.getElementsByName('deposito_unico')[0].checked = false;
      document.getElementsByName('deposito_varios')[0].checked = false;
      document.getElementById('deposito_unico_box').classList.add('hidden');
      document.getElementById('deposito_varios_box').classList.add('hidden');
    }
  }
  function setDepositoModo(modo) {
    const unico = document.getElementById('deposito_unico_box');
    const varios = document.getElementById('deposito_varios_box');
    if (modo === 'unico') {
      // marcar radio lógico (mutuamente excluyente)
      document.getElementsByName('deposito_unico')[0].checked = true;
      document.getElementsByName('deposito_varios')[0].checked = false;
      unico.classList.remove('hidden');
      varios.classList.add('hidden');
    } else {
      document.getElementsByName('deposito_unico')[0].checked = false;
      document.getElementsByName('deposito_varios')[0].checked = true;
      unico.classList.add('hidden');
      varios.classList.remove('hidden');
    }
  }

  // ====== EXTRAS MÚLTIPLES ======
  function toggleExtras(on) {
    document.getElementById('extras_box').classList.toggle('hidden', !on);
  }

  let extraIdx = 1;
  function addExtraRow() {
    const tbody = document.getElementById('extras-multi-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-2">
        <select name="extras_multi[${extraIdx}][tipo]" class="w-full border-gray-300 rounded-lg p-2.5">
          <option value="" selected disabled>Seleccione</option>
          <option value="decimo">Décimo</option>
          <option value="aguinaldo">Aguinaldo</option>
          <option value="prestaciones">Prestaciones</option>
          <option value="liquidacion">Liquidación</option>
        </select>
      </td>
      <td class="px-3 py-2">
        <input type="text" name="extras_multi[${extraIdx}][periodo]" placeholder="Ej. 2026"
               class="w-full border-gray-300 rounded-lg p-2.5">
      </td>
      <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" name="extras_multi[${extraIdx}][monto]" placeholder="0.00"
               class="w-full border-gray-300 rounded-lg p-2.5">
      </td>
      <td class="px-3 py-2">
        <button type="button" class="px-3 py-2 rounded-lg border" onclick="removeExtraRow(this)">Quitar</button>
      </td>
    `;
    tbody.appendChild(tr);
    extraIdx++;
  }
  function removeExtraRow(btn) {
    const tr = btn.closest('tr');
    if (tr && document.querySelectorAll('#extras-multi-body tr').length > 1) {
      tr.remove();
    }
  }
</script>

  <!-- Footer -->
  <div class="mt-6 flex items-center justify-end gap-3">
    <button type="button"
            class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4
                   focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5"
            data-modal-toggle="modal-nuevo-prestamo">
      Cancelar
    </button>
    <button type="submit"
            class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4
                   focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
      Guardar
    </button>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const radios = document.querySelectorAll('input[name="refi_int_tipo"]');
  const monto  = document.getElementById('refi_int_monto');

  function toggleMonto() {
    const val = document.querySelector('input[name="refi_int_tipo"]:checked')?.value;
    const enable = (val === 'parcial');
    monto.disabled = !enable;
    monto.required = enable;             // opcional: que sea obligatorio solo en “parcial”
    monto.classList.toggle('opacity-50', !enable); // si usas Tailwind para “grisar”
    if (!enable) monto.value = '';       // opcional: limpia cuando no es parcial
  }

  radios.forEach(r => r.addEventListener('change', toggleMonto));
  toggleMonto(); // estado inicial al cargar
});
</script>


<!-- Toggle logic -->
<script>
  // util: habilita/deshabilita targets según el estado del checkbox
  function bindEnableOnCheck(checkboxId, targetSelectors) {
    const cb = document.getElementById(checkboxId);
    const targets = targetSelectors.flatMap(sel => Array.from(document.querySelectorAll(sel)));
    function apply() {
      const enabled = cb.checked;
      targets.forEach(el => {
        el.disabled = !enabled;
        el.classList.toggle('cursor-not-allowed', !enabled);
        el.classList.toggle('bg-gray-100', !enabled);
      });
    }
    cb.addEventListener('change', apply);
    apply(); // estado inicial
  }

  // Refinanciamiento → habilita monto
  bindEnableOnCheck('es_refinanciamiento', ['#monto_refinanciamiento']);

  // Cobro extraordinario → habilita campos del fieldset (excepto el propio checkbox)
  bindEnableOnCheck('chk_cobro_extra', [
    '#fs-cobro-extra input:not(#chk_cobro_extra)',
    '#fs-cobro-extra textarea',
    '#fs-cobro-extra select'
  ]);

  // Depósitos → habilita todos los campos de ese fieldset (excepto el propio checkbox)
  bindEnableOnCheck('chk_depositos', [
    '#fs-depositos input:not(#chk_depositos)',
    '#fs-depositos textarea',
    '#fs-depositos select'
  ]);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const chkRefi  = document.getElementById('es_refinanciamiento');
  const section  = document.getElementById('refi_int_section');
  const radios   = document.querySelectorAll('input[name="refi_int_tipo"]');
  const monto    = document.getElementById('refi_int_monto');

  function toggleMonto() {
    const val = document.querySelector('input[name="refi_int_tipo"]:checked')?.value;
    const enable = (val === 'parcial') && !section.classList.contains('hidden');
    monto.disabled = !enable;
    monto.required = enable;                 // opcional
    monto.classList.toggle('opacity-50', !enable);
    if (!enable) monto.value = '';           // opcional: limpiar cuando no aplica
  }

  function setSectionEnabled(on) {
    // mostrar/ocultar
    section.classList.toggle('hidden', !on);
    // habilitar/deshabilitar todos los controles internos
    section.querySelectorAll('input, select, textarea, button').forEach(el => {
      if (el.type !== 'hidden') el.disabled = !on;
    });
    // ajustar el campo monto según radio
    toggleMonto();
  }

  // eventos
  chkRefi.addEventListener('change', () => setSectionEnabled(chkRefi.checked));
  radios.forEach(r => r.addEventListener('change', toggleMonto));

  // estado inicial (respeta lo que venga del servidor si hubo validación)
  setSectionEnabled(chkRefi.checked);
});
</script>

    </div>
  </div>
</div>


<!-- MODAL ANTIGUO (dejar comentado por si acaso) -->
    <div id="create-modal2" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Ingresar Nuevo préstamo
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{ route('infoprestamo.storeprestamo') }}" method="POST" class="p-4 md:p-5">
                    @csrf
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <label for="num_prestamo" class="block mb-2 text-sm font-medium text-gray-900">Número de Préstamo</label>
                            <input type="text" name="num_prestamo" id="num_prestamo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div class="col-span-2">
                            <label for="id_empleado" class="block mb-2 text-sm font-medium text-gray-900">Código de Empleado</label>
                            <select name="id_empleado" id="id_empleado" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                                    focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione un Código</option>
                                @foreach($empleados as $empleado)
                                    <option value="{{ $empleado->id_empleado }}" data-nombre="{{ $empleado->nombre_completo }}">
                                        {{ $empleado->codigo_empleado }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label for="nombre_empleado" class="block mb-2 text-sm font-medium text-gray-900">Nombre del Empleado</label>
                            <input type="text" id="nombre_empleado" 
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg 
                                    block w-full p-2.5 cursor-not-allowed" 
                                readonly>
                        </div>

                        <script>
                            document.getElementById('id_empleado').addEventListener('change', function () {
                                let nombre = this.options[this.selectedIndex].getAttribute('data-nombre');
                                document.getElementById('nombre_empleado').value = nombre ?? '';
                            });
                        </script>

                        <div class="col-span-2">
                            <label for="monto" class="block mb-2 text-sm font-medium text-gray-900">Monto Prestado</label>
                            <input type="text" name="monto" id="monto" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div class="col-span-2">
                            <label for="cuota_capital" class="block mb-2 text-sm font-medium text-gray-900">Cuota Mensual</label>
                            <input type="text" name="cuota_capital" id="cuota_capital" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div id="solo-interes-box-modal" class="col-span-2 hidden bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <span class="block text-sm font-semibold text-blue-900 mb-2">Plan de pago solo intereses</span>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-blue-900">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="solo_interes_opcion_modal" value="total" class="text-blue-600 focus:ring-blue-500" checked>
                                    Pagar en cuotas intereses totales
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="solo_interes_opcion_modal" value="parcial" class="text-blue-600 focus:ring-blue-500">
                                    Pagar en cuotas intereses parciales
                                </label>
                            </div>
                            <div id="solo-interes-parcial-modal" class="mt-3 hidden">
                                <label class="block text-sm font-medium text-gray-900">Monto parcial a distribuir</label>
                                <input type="number" step="0.01" min="0" id="solo_interes_parcial_monto_modal" class="mt-1 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Ej. 1500.00">
                            </div>
                            <p id="solo-interes-resumen-modal" class="mt-3 text-xs text-blue-800">Ingresá el plazo y el total de intereses para mostrar el cálculo.</p>
                        </div>
                        <input type="hidden" name="solo_interes_modo" id="solo_interes_modo_modal">
                        <input type="hidden" name="solo_interes_monto" id="solo_interes_monto_modal">

                        <div class="col-span-2">
                            <label for="porcentaje_interes" class="block mb-2 text-sm font-medium text-gray-900">Porcentaje de Interés</label>
                            <input type="text" name="porcentaje_interes" id="porcentaje_interes" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div class="col-span-2">
                            <label for="total_intereses" class="block mb-2 text-sm font-medium text-gray-900">Total de Intereses</label>
                            <input type="text" name="total_intereses" id="total_intereses" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div class="col-span-2">
                            <label for="cobro_extraordinario" class="block mb-2 text-sm font-medium text-gray-900">Cobro Extraordinario</label>
                            <input type="text" name="cobro_extraordinario" id="cobro_extraordinario" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label for="causa" class="block mb-2 text-sm font-medium text-gray-900">Causa del Cobro Extraordinario</label>
                            <input type="text" name="causa" id="causa" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label for="plazo_meses" class="block mb-2 text-sm font-medium text-gray-900">Plazo del Préstamo (Meses)</label>
                            <input type="text" name="plazo_meses" id="plazo_meses" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div class="col-span-2">
                            <label for="fecha_deposito_prestamo" class="block mb-2 text-sm font-medium text-gray-900">Fecha de Aprobación del Préstamo</label>
                            <input type="date" name="fecha_deposito_prestamo" id="fecha_deposito_prestamo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                        </div>
                        <div class="col-span-2">
                            <input type="hidden" name="estado_prestamo" value="1">
                        </div>
                        <div class="col-span-2">
                            <label for="fecha_primera_cuota" class="block mb-2 text-sm font-medium text-gray-900">Fecha de cobro de la primera cuota</label>
                            <input type="date" name="fecha_primera_cuota" id="fecha_primera_cuota" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label for="id_planilla" class="block mb-2 text-sm font-medium text-gray-900">Seleccione la planilla</label>
                            <select name="id_planilla" id="id_planilla" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione una planilla</option>
                                @foreach($planilla as $planillas)
                                    <option value="{{ $planillas->id_planilla }}">{{ $planillas->planilla }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="observaciones" class="block text-gray-700 font-semibold mb-2">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                        Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
 
    
    <!-- Botón subir lista -->
        <form id="importForm" action="{{ route('infoprestamo.import') }}" method="POST" enctype="multipart/form-data"
            style="display: none;">
            @csrf
            <input type="file" id="excelFileInput" name="excel_file" accept=".xls,.xlsx,.xlsm"
                onchange="document.getElementById('importForm').submit();">
        </form>
        <button type="button" onclick="document.getElementById('excelFileInput').click();"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 12V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-4m5-13v4a1 1 0 0 1-1 1H5m0 6h9m0 0-2-2m2 2-2 2" />
            </svg>
            Subir Lista de Prestamos Anteriores
        </button>
   </div>

            <form action="{{ route('infoprestamo') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
        <div class="relative w-full">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar..."
                oninput="this.form.submit()" {{-- aquí está la magia --}}
                class="pl-10 pr-10 py-2 w-full border border-gray-300 rounded-l-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                </svg>
            </div>
        </div>
    </form>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Número del Préstamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Código del Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nombre del Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Monto Prestado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Cuota Mensual
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Porcentaje de Interés
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Total de Intereses
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Cobro Extraordinario
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Causa
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Plazo del préstamo (Meses)
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha depósito del Préstamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha de primera cuota
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Planilla
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prestamo as $prestamos)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4 bg-blue-100 font-semibold">
                        {{ $prestamos->num_prestamo }}
                    </td>    
                    <td class="px-6 py-4">
                        {{ $prestamos->codigo_empleado }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->nombre_completo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->monto }}
                    </td>
                     <td class="px-6 py-4">
                        {{ $prestamos->cuota_capital }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->porcentaje_interes }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->total_intereses }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->cobro_extraordinario }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->causa }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->plazo_meses }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->fecha_deposito_prestamo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->fecha_primera_cuota }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->planilla }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $prestamos->estado_prestamo }}
                    </td>
                    <td>
                        <!-- Botón Eliminar -->
<button type="button" class="btn btn-danger" onclick="mostrarModalEliminar({{ $prestamos->id_prestamo }})">
  Eliminar Préstamo
</button>

<!-- Modal de Confirmación -->
<div id="modalEliminarPrestamo" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:9999;">
  <div style="background:#fff; max-width:400px; margin:10% auto; padding:24px; border-radius:8px; box-shadow:0 2px 12px #0002;">
    <h3>¿Estás seguro de eliminar el préstamo?</h3>
    <p>Esta acción no se puede deshacer.</p>
    <input type="password" id="passEliminarPrestamo" placeholder="Contraseña" class="form-control" />
    <div id="errorEliminarPrestamo" style="color:red; margin-top:8px; display:none;"></div>
    <div style="margin-top:16px;">
      <button onclick="confirmarEliminarPrestamo()" class="btn btn-danger">Eliminar</button>
      <button onclick="cerrarModalEliminar()" class="btn btn-secondary">Cancelar</button>
    </div>
    <form id="formEliminarPrestamo" method="POST" action="{{ route('prestamo.eliminar') }}" style="display:none;">
      @csrf
      <input type="hidden" name="id_prestamo" id="inputIdPrestamoEliminar" value="">
    </form>
  </div>
</div>

<script>
let idPrestamoEliminar = null;
function mostrarModalEliminar(id) {
  idPrestamoEliminar = id;
  document.getElementById('modalEliminarPrestamo').style.display = 'block';
  document.getElementById('inputIdPrestamoEliminar').value = id;
  document.getElementById('passEliminarPrestamo').value = '';
  document.getElementById('errorEliminarPrestamo').style.display = 'none';
}
function cerrarModalEliminar() {
  document.getElementById('modalEliminarPrestamo').style.display = 'none';
}
function confirmarEliminarPrestamo() {
  const pass = document.getElementById('passEliminarPrestamo').value;
  if (pass !== 'STBprestamos') {
    document.getElementById('errorEliminarPrestamo').innerText = 'Contraseña incorrecta';
    document.getElementById('errorEliminarPrestamo').style.display = 'block';
    return;
  }
  document.getElementById('formEliminarPrestamo').submit();
}
  function setupSoloInteresFeature(config) {
    const box = document.getElementById(config.boxId);
    if (!box) { return; }
    const form = box.closest('form');
    if (!form) { return; }

    const capitalInput = form.querySelector(config.capitalSelector);
    const totalInput = form.querySelector(config.totalSelector);
    const plazoInput = form.querySelector(config.plazoSelector);
    const radios = box.querySelectorAll('input[name="' + config.radioName + '"]');
    const parcialWrap = document.getElementById(config.parcialWrapId);
    const parcialInput = document.getElementById(config.parcialInputId);
    const resumen = document.getElementById(config.resumenId);
    const hiddenModo = document.getElementById(config.hiddenModoId);
    const hiddenMonto = document.getElementById(config.hiddenMontoId);

    if (!capitalInput || !totalInput || !plazoInput || !hiddenModo || !hiddenMonto) { return; }

    const ensureMode = function () {
      let selected = null;
      radios.forEach(function (radio) {
        if (radio.checked) { selected = radio.value; }
      });
      if (!selected && radios.length > 0) {
        radios[0].checked = true;
        selected = radios[0].value;
      }
      return selected || 'total';
    };

    const update = function () {
      const capitalValue = parseFloat(capitalInput.value);
      const show = !Number.isNaN(capitalValue) && Math.abs(capitalValue) < 1e-6;
      box.classList.toggle('hidden', !show);

      if (!show) {
        hiddenModo.value = '';
        hiddenMonto.value = '';
        if (parcialWrap) { parcialWrap.classList.add('hidden'); }
        if (parcialInput) {
          parcialInput.value = '';
          parcialInput.required = false;
        }
        if (resumen) {
          resumen.textContent = 'Ingresá el plazo y el total de intereses para mostrar el cálculo.';
        }
        return;
      }

      const modo = ensureMode();
      const totalIntereses = parseFloat(totalInput.value);
      const plazo = parseFloat(plazoInput.value);
      let base = 0;

      if (parcialWrap) {
        const isParcial = modo === 'parcial';
        parcialWrap.classList.toggle('hidden', !isParcial);
        if (parcialInput) { parcialInput.required = isParcial; }
      }

      if (modo === 'parcial' && parcialInput) {
        const parcialVal = parseFloat(parcialInput.value);
        base = !Number.isNaN(parcialVal) ? Math.max(0, parcialVal) : 0;
      } else {
        base = !Number.isNaN(totalIntereses) ? Math.max(0, totalIntereses) : 0;
      }

      hiddenModo.value = modo;
      hiddenMonto.value = base > 0 ? base.toFixed(2) : '';

      if (resumen) {
        if (base > 0 && !Number.isNaN(plazo) && plazo > 0) {
          const interesMensual = base / plazo;
          const interesQuincenal = interesMensual / 2;
          resumen.textContent = 'Interés mensual estimado: L ' + interesMensual.toFixed(2) + ' | Quincenal aprox.: L ' + interesQuincenal.toFixed(2);
        } else if (base > 0) {
          resumen.textContent = 'Indicá el plazo del préstamo para calcular el interés mensual.';
        } else {
          resumen.textContent = 'Ingresá un monto para distribuir los intereses.';
        }
      }
    };

    const listeners = [
      { el: capitalInput, evt: 'input' },
      { el: totalInput, evt: 'input' },
      { el: plazoInput, evt: 'input' },
      { el: parcialInput, evt: 'input' },
    ];

    radios.forEach(function (radio) {
      radio.addEventListener('change', update);
    });

    listeners.forEach(function (listener) {
      if (listener.el) { listener.el.addEventListener(listener.evt, update); }
    });

    form.addEventListener('submit', update);

    update();
  }

  setupSoloInteresFeature({
    boxId: 'solo-interes-box-main',
    radioName: 'solo_interes_opcion_main',
    parcialWrapId: 'solo-interes-parcial-main',
    parcialInputId: 'solo_interes_parcial_monto_main',
    resumenId: 'solo-interes-resumen-main',
    hiddenModoId: 'solo_interes_modo_main',
    hiddenMontoId: 'solo_interes_monto_main',
    capitalSelector: 'input[name="cuota_mensual"]',
    totalSelector: 'input[name="total_intereses"]',
    plazoSelector: 'input[name="plazo_prestamo"]',
  });

  setupSoloInteresFeature({
    boxId: 'solo-interes-box-modal',
    radioName: 'solo_interes_opcion_modal',
    parcialWrapId: 'solo-interes-parcial-modal',
    parcialInputId: 'solo_interes_parcial_monto_modal',
    resumenId: 'solo-interes-resumen-modal',
    hiddenModoId: 'solo_interes_modo_modal',
    hiddenMontoId: 'solo_interes_monto_modal',
    capitalSelector: 'input[name="cuota_capital"]',
    totalSelector: 'input[name="total_intereses"]',
    plazoSelector: 'input[name="plazo_meses"]',
  });

</script>
            </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $prestamo->links() }}
    </div>
@endsection
