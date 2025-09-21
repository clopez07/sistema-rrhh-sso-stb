@extends('layouts.prestamos')

@section('title', 'Control de Prestamos')

@section('content')
    <!-- Breadcrumb -->
    <nav class="flex px-5 py-3 text-gray-700 bg-blue-100 rounded-lg" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <!-- Ãcono de inicio -->
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
            <span class="text-sm font-medium text-black">Historial de Cuotas</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

       @if (session('ajustes_msg'))
  <div class="p-3 mb-4 rounded bg-green-50 text-green-800 border border-green-200">
    {{ session('ajustes_msg') }}
  </div>
@endif

@if (session('error'))
  <div class="p-3 mb-4 rounded bg-red-50 text-red-800 border border-red-200">
    {{ session('error') }}
  </div>
@endif

<form action="{{ route('prestamos.ajustes.import') }}" method="post" enctype="multipart/form-data" class="space-y-3">
  @csrf
  <input type="file" name="archivo" accept=".xlsx,.xls" required class="block">
  <button class="px-4 py-2 bg-blue-600 text-white rounded">Importar ajustes</button>
</form>

            <form action="{{ route('cuotas') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
        <div class="relative w-full">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar..."
                oninput="this.form.submit()" {{-- aquÃ­ estÃ¡ la magia --}}
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

    @if (session('success'))
        <div class="mt-3 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-3 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table id="tablaCUOTA" class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        No. Cuota
                    </th>
                    <th scope="col" class="px-6 py-3">
                        No. Préstamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nombre del Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha de Prestamo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha Programada
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Abono a Capital
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Abono a Intereses
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Cuota Mensual
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Cuota Quincenal
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Saldo Pagado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Saldo Restante
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Interés Pagado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Interés Restante
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3">Observaciones</th>
                    <th scope="col" class="px-6 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($cuotas as $cuota)
                @php
                    // Normaliza el valor de pagado por si viene como string/bool/int
                    $isPagado = (string)($cuota->pagado ?? '') === '1' || (int)($cuota->pagado ?? 0) === 1;

                    // Color de la fila: rojo suave si estÃ¡ pagado; sin color si no
                    $rowClass = $isPagado ? 'bg-red-50' : 'bg-white';

                    // Texto a mostrar en la celda "Pagado"
                    $pagadoText = $isPagado ? 'PAGADO' : 'PENDIENTE';

                    // Acento para la celda "Pagado" cuando es SÃ­ (mÃ¡s oscuro)
                    $pagadoCellClass = $isPagado ? 'bg-red-200 text-red-900 font-semibold' : '';
                @endphp

                <tr class="{{ $rowClass }} border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4 bg-blue-100 font-semibold">
                        {{ $cuota->num_cuota }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->num_prestamo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->nombre_completo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->fecha_deposito_prestamo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->fecha_programada }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->abono_capital }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->abono_intereses }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->cuota_mensual }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->cuota_quincenal }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->saldo_pagado }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->saldo_restante }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->interes_pagado }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $cuota->interes_restante }}
                    </td>

                    {{-- Pagado: muestra "SÃ­"/"No" y aplica acento rojo cuando es SÃ­ --}}
                    <td class="px-6 py-4 {{ $pagadoCellClass }}">
                        {{ $pagadoText }}
                    </td>

                    <td class="px-6 py-4 {{ (stripos($cuota->observaciones ?? '', 'cobro extraordinario') !== false) ? 'bg-yellow-100 text-yellow-900 font-medium' : ((stripos($cuota->observaciones ?? '', 'depÃ³sito') !== false || stripos($cuota->observaciones ?? '', 'deposito') !== false) ? 'bg-green-100 text-green-900 font-medium' : '') }}">
                        {{ $cuota->observaciones }}
                    </td>
                    <td class="px-6 py-4">
                        <button type="button"
                                data-modal-target="edit-cuota-{{ $cuota->id_historial_cuotas }}"
                                data-modal-toggle="edit-cuota-{{ $cuota->id_historial_cuotas }}"
                                class="text-blue-600 hover:underline">Editar</button>

                        <div id="edit-cuota-{{ $cuota->id_historial_cuotas }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900">Editar cuota</h3>
                                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="edit-cuota-{{ $cuota->id_historial_cuotas }}">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                            <span class="sr-only">Close modal</span>
                                        </button>
                                    </div>
                                    <form action="{{ route('cuotas.update', $cuota->id_historial_cuotas) }}" method="POST" class="p-4 md:p-5">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid gap-4 mb-4 grid-cols-2">
                                            <div class="col-span-2">
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Nombre</label>
                                                <input type="text" value="{{ $cuota->nombre_completo }}" class="w-full bg-gray-100 border border-gray-200 text-gray-700 text-sm rounded-lg p-2.5" disabled>
                                            </div>
                                            <div class="col-span-2">
                                                <label class="block mb-2 text-sm font-medium text-gray-900">No. Prestamo</label>
                                                <input type="text" value="{{ $cuota->num_prestamo }}" class="w-full bg-gray-100 border border-gray-200 text-gray-700 text-sm rounded-lg p-2.5" disabled>
                                            </div>
                                            <div>
                                                <label for="num_cuota_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">No. Cuota</label>
                                                <input type="number" id="num_cuota_{{ $cuota->id_historial_cuotas }}" name="num_cuota" value="{{ old('num_cuota', $cuota->num_cuota) }}" min="0" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" required>
                                            </div>
                                            <div>
                                                <label for="fecha_programada_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Fecha programada</label>
                                                <input type="date" id="fecha_programada_{{ $cuota->id_historial_cuotas }}" name="fecha_programada" value="{{ old('fecha_programada', $cuota->fecha_programada ? \Illuminate\Support\Carbon::parse($cuota->fecha_programada)->format('Y-m-d') : '') }}" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" required>
                                            </div>
                                            <div>
                                                <label for="abono_capital_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Abono a capital</label>
                                                <input type="number" step="0.01" min="0" id="abono_capital_{{ $cuota->id_historial_cuotas }}" name="abono_capital" value="{{ old('abono_capital', $cuota->abono_capital) }}" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" required>
                                            </div>
                                            <div>
                                                <label for="abono_intereses_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Abono a intereses</label>
                                                <input type="number" step="0.01" min="0" id="abono_intereses_{{ $cuota->id_historial_cuotas }}" name="abono_intereses" value="{{ old('abono_intereses', $cuota->abono_intereses) }}" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" required>
                                            </div>
                                            <div>
                                                <label for="cuota_mensual_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Cuota mensual</label>
                                                <input type="number" step="0.01" min="0" id="cuota_mensual_{{ $cuota->id_historial_cuotas }}" name="cuota_mensual" value="{{ old('cuota_mensual', $cuota->cuota_mensual) }}" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" required>
                                            </div>
                                            <div>
                                                <label for="cuota_quincenal_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Cuota quincenal</label>
                                                <input type="number" step="0.01" min="0" id="cuota_quincenal_{{ $cuota->id_historial_cuotas }}" name="cuota_quincenal" value="{{ old('cuota_quincenal', $cuota->cuota_quincenal) }}" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" required>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="observaciones_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Observaciones</label>
                                                <textarea id="observaciones_{{ $cuota->id_historial_cuotas }}" name="observaciones" rows="3" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">{{ old('observaciones', $cuota->observaciones) }}</textarea>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="estado_{{ $cuota->id_historial_cuotas }}" class="block mb-2 text-sm font-medium text-gray-900">Estado</label>
                                                <select id="estado_{{ $cuota->id_historial_cuotas }}" name="estado" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                                                    <option value="0" {{ (int)($cuota->pagado ?? 0) === 0 ? 'selected' : '' }}>PENDIENTE</option>
                                                    <option value="1" {{ (int)($cuota->pagado ?? 0) === 1 ? 'selected' : '' }}>PAGADO</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Guardar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $cuotas->links() }}
    </div>
@endsection
