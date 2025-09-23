@extends('layouts.prestamos')

@section('title', 'Control de Prestamos')

@section('content')
    <!-- Breadcrumb -->
    <nav class="flex px-5 py-3 text-gray-700 bg-blue-100 rounded-lg" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <!-- Ícono de inicio -->
            <svg class="w-4 h-4 mr-2 text-black" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a1 1 0 00-.707.293l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-3h2v3a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7A1 1 0 0010 2z" />
            </svg>
            Inicio
        </a>
        </li>
        <!-- Separador con flechita -->
        <li>
        <div class="flex items-center">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-black">Resumen General de Prestamos</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

    <div class="flex items-center justify-between w-full gap-3">
        <form action="{{ route('empleadosprestamo') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
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

    <a
    href="{{ route('empleadosprestamo.export', ['search' => request('search')]) }}"
    class="ml-3 inline-flex items-center px-4 py-2 rounded-lg text-white"
    style="background:#00B0F0;"
    >
    Descargar Excel
    </a>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Número de Prestamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Código de Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nombre Completo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Capital
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Intereses
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Total a Pagar
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Total Capital Pagado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Total Intereses Pagado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Saldo a Capital
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Saldo a Intereses
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha Inicio Prestamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha Final Prestamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estado del Prestamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                    Acciones
                    </th>
                </tr>
            </thead>
            <tbody>
            @foreach ($empleadosprestamo as $empleadosprestamos)
                @php
                    $estado = strtolower(trim($empleadosprestamos->estado_prestamo ?? ''));
                    $isActivo = $estado === 'activo';

                    // Color base para toda la fila
                    $rowClass = $isActivo ? 'bg-green-200' : 'bg-red-200';

                    // Color acento (más oscuro) para las celdas que antes estaban en amarillo
                    $accentClass = $isActivo ? 'bg-green-200' : 'bg-red-200';
                @endphp

                <tr class="border-b border-gray-200 {{ $rowClass }}">
                    <td class="px-6 py-4 font-semibold">
                        {{ $empleadosprestamos->num_prestamo }}
                    </td>
                    <td class="px-6 py-4 font-medium">
                        {{ $empleadosprestamos->codigo_empleado }}
                    </td>
                    <td class="px-6 py-4 font-medium">
                        {{ $empleadosprestamos->nombre_completo }}
                    </td>
                    <td class="px-6 py-4 font-medium" style="background-color: #77D2E6;">
                        {{ $empleadosprestamos->monto }}
                    </td>
                    <td class="px-6 py-4 font-medium" style="background-color: #77D2E6;">
                        {{ $empleadosprestamos->total_intereses }}
                    </td>
                    <td class="px-6 py-4 font-medium" style="background-color: #77D2E6;">
                        {{ $empleadosprestamos->total_pagado }}
                    </td>

                    {{-- Estas cuatro celdas antes eran bg-yellow-100: ahora usan el acento dinámico --}}
                    <td class="px-6 py-4 font-medium" style="background-color: #E1BBED;">
                        {{ $empleadosprestamos->total_capital_pagado }}
                    </td>
                    <td class="px-6 py-4 font-medium" style="background-color: #E1BBED;">
                        {{ $empleadosprestamos->total_intereses_pagados }}
                    </td>
                    <td class="px-6 py-4 font-medium" style="background-color: #E1BBED;">
                        {{ $empleadosprestamos->saldo_capital_pendiente }}
                    </td>
                    <td class="px-6 py-4 font-medium" style="background-color: #E1BBED;">
                        {{ $empleadosprestamos->saldo_intereses_pendiente }}
                    </td>

                    <td class="px-6 py-4 font-medium" style="background-color: #E8C47D;">
                        {{ $empleadosprestamos->fecha_deposito_prestamo }}
                    </td>

                    {{-- Puedes dejarlo fijo o hacerlo coherente con el estado; aquí lo hago dinámico también --}}
                    <td class="px-6 py-4 font-medium" style="background-color: #E8C47D;">
                        {{ $empleadosprestamos->fecha_final }}
                    </td>

                    {{-- Estado del préstamo (opcional: un pelín más oscuro para remarcar) --}}
                    <td class="px-6 py-4 font-semibold">
                        {{ $empleadosprestamos->estado_prestamo }}
                    </td>
                    <td class="px-6 py-4">
                    <button
                        type="button"
                        class="btn-detalle inline-flex items-center px-3 py-1.5 rounded-lg text-white"
                        style="background:#00B0F0;"
                        data-url="{{ route('prestamos.detalle', $empleadosprestamos->id_prestamo) }}"
                    >
                        Ver detalles
                    </button>
                    </td>
                </tr>
            @endforeach
            </tbody>

        </table>
        {{ $empleadosprestamo->links() }}
    </div>

    <!-- Modal Detalle Préstamo -->
    <div id="modal-detalle" class="fixed inset-0 z-50 hidden">
    <!-- backdrop -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- card -->
    <div class="absolute inset-0 flex items-start md:items-center justify-center p-4 md:p-6">
        <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="px-5 py-3 border-b flex items-center justify-between" style="background:#00B0F0;">
            <h3 class="text-white font-semibold">Detalle del Préstamo</h3>
            <button id="btn-cerrar-modal" class="text-white/90 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="p-5 space-y-5">
            <!-- Contadores -->
            <div id="detalle-contadores" class="grid grid-cols-2 gap-3">
            <!-- Rellena JS -->
            </div>

            <!-- Tabla historial -->
            <div class="relative border rounded-xl">
            <div class="overflow-x-auto max-h-[60vh] md:max-h-[70vh] overflow-y-auto" id="modal-table-wrap">
                <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs uppercase bg-gray-50">
                <tr>
                    <th class="px-4 py-2 sticky top-0 bg-gray-50 z-10">Número de cuota</th>
                    <th class="px-4 py-2 sticky top-0 bg-gray-50 z-10">Fecha de pago</th>
                    <th class="px-4 py-2 sticky top-0 bg-gray-50 z-10">Cuota quincenal</th>
                    <th class="px-4 py-2 sticky top-0 bg-gray-50 z-10">Estado</th>
                    <th class="px-4 py-2 sticky top-0 bg-gray-50 z-10">Observaciones</th>
                </tr>
                </thead>
                <tbody id="detalle-tbody">
                <tr id="detalle-loading">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Cargando...</td>
                </tr>
                </tbody>
            </table>
        </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 bg-gray-50 border-t text-right">
            <button id="btn-cerrar-modal-2" class="inline-flex items-center px-4 py-2 rounded-lg border hover:bg-gray-100">
            Cerrar
            </button>
        </div>
        </div>
    </div>
    </div>

    <script>
(function(){
  const modal = document.getElementById('modal-detalle');
  const btnClose1 = document.getElementById('btn-cerrar-modal');
  const btnClose2 = document.getElementById('btn-cerrar-modal-2');
  const tbody = document.getElementById('detalle-tbody');
  const conts = document.getElementById('detalle-contadores');

  const money = (n) => Number(n ?? 0).toLocaleString('es-HN', {minimumFractionDigits:2, maximumFractionDigits:2});
  const fmtDate = (d) => {
    if(!d) return '—';
    const dt = new Date(d + 'T00:00:00');
    if (isNaN(dt)) return d;
    const dd = String(dt.getDate()).padStart(2,'0');
    const mm = String(dt.getMonth()+1).padStart(2,'0');
    const yy = dt.getFullYear();
    return `${dd}/${mm}/${yy}`;
  };

  const close = () => modal.classList.add('hidden');
  btnClose1.addEventListener('click', close);
  btnClose2.addEventListener('click', close);
  modal.addEventListener('click', (e)=>{
    if(e.target === modal || e.target.classList.contains('bg-black/40')) close();
  });

  const renderContadores = ({pagadas, pendientes}) => {
    conts.innerHTML = '';
    const items = [
      { label: 'Cuotas pagadas', value: pagadas, cls: 'bg-green-50 border-green-200 text-green-700' },
      { label: 'Cuotas pendientes', value: pendientes, cls: 'bg-yellow-50 border-yellow-200 text-yellow-800' },
    ];
    items.forEach(it => {
      const card = document.createElement('div');
      card.className = `rounded-xl border p-3 ${it.cls}`;
      card.innerHTML = `
        <div class="text-xs">${it.label}</div>
        <div class="text-2xl font-bold">${it.value}</div>
      `;
      conts.appendChild(card);
    });
  };

  const renderTabla = (rows) => {
    tbody.innerHTML = '';
    if (!rows || rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin historial de cuotas.</td></tr>`;
      return;
    }

    rows.forEach(c => {
      const estado = Number(c.pagado) === 1 ? 'Pagada' : 'Pendiente';
      const estadoCls = Number(c.pagado) === 1 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800';
      const tr = document.createElement('tr');
      tr.className = 'border-b';
      tr.innerHTML = `
        <td class="px-4 py-2">${c.num_cuota ?? ''}</td>
        <td class="px-4 py-2">${fmtDate(c.fecha_programada)}</td>
        <td class="px-4 py-2">L. ${money(c.cuota_quincenal)}</td>
        <td class="px-4 py-2">
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${estadoCls}">
            ${estado}
          </span>
        </td>
        <td class="px-4 py-2">${c.observaciones ?? '—'}</td>
      `;
      tbody.appendChild(tr);
    });
  };

  const openWith = async (url) => {
    conts.innerHTML = '';
    tbody.innerHTML = `<tr id="detalle-loading"><td colspan="5" class="px-4 py-6 text-center text-gray-500">Cargando...</td></tr>`;
    modal.classList.remove('hidden');

    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
      if (!res.ok) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-red-600">No se pudo cargar el detalle (${res.status}).</td></tr>`;
        return;
      }
      const data = await res.json();
      if (!data.ok) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-red-600">${data.msg || 'Error'}</td></tr>`;
        return;
      }
      renderContadores(data.resumen);
      renderTabla(data.cuotas);
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-red-600">Error de red.</td></tr>`;
    }
  };

  // Delegación de eventos: botones .btn-detalle
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-detalle');
    if (!btn) return;
    const url = btn.getAttribute('data-url');
    if (url) openWith(url);
  });
})();
</script>

@endsection