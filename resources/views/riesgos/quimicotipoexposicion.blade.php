@extends('layouts.riesgos')

@section('title', 'Administrar Tipos de exposiciones por quimicos')

@section('content')
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
        <li class="inline-flex items-center">
        <a href="/quimicos" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <!-- Ícono de inicio -->
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            Inventario de Químicos
        </a>
        </li>
        <!-- Separador con flechita -->
        <li>
        <div class="flex items-center">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-black">Tipos de Exposición por Químicos</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

    <div class="flex justify-between items-center w-full mb-4">
        <!-- Botones a la izquierda -->
        <div class="flex items-center gap-2">
            <!-- Botón agregar -->
            <div class="inline-flex rounded-md shadow-xs" role="group">
                <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                    <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14m-7 7V5" />
                    </svg>
                    Agregar
                </button>
            </div>
            <!-- Main modal -->
            <div id="create-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-md max-h-full">
                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg shadow-sm">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Asignar Tipos de Exposición a Quimicos
                            </h3>
                            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                            </button>
                        </div>
                        <!-- Modal body -->
                        <form action="{{ route('quimicotipoexposicion.storequimicotipoexposicion') }}" method="POST" class="p-4 md:p-5">
                            @csrf 
                            <div class="grid gap-4 mb-4 grid-cols-2">
                                <div class="col-span-2">
                                    <label for="id_quimico" class="block mb-2 text-sm font-medium text-gray-900">Químicos</label>
                                    <select name="id_quimico" id="id_quimico" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                        <option value="" disabled selected>Seleccione un Químico</option>
                                        @foreach($quimicos as $quimico)
                                            <option value="{{ $quimico->id_quimico }}">{{ $quimico->nombre_comercial }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                                <!-- Capacitaciones -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-900 mb-2">
                                        Seleccione el tipo de Exposición
                                    </label>
                                    <div class="flex gap-2">
                                        <!-- Lista izquierda -->
                                        <select id="id_tipo_exposicion" multiple 
                                            class="w-1/2 h-40 border border-gray-300 rounded-lg p-1 text-sm">
                                            @foreach($tipoexposicion as $tipoexposicione)
                                            <option value="{{ $tipoexposicione->id_tipo_exposicion }}">{{ $tipoexposicione->tipo_exposicion }}</option>
                                            @endforeach
                                        </select>

                                        <!-- Botones -->
                                        <div class="flex flex-col justify-center gap-1">
                                            <button type="button" 
                                                onclick="mover('id_tipo_exposicion', 'capacitaciones-seleccionadas')" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&gt;</button>
                                            <button type="button" 
                                                onclick="mover('capacitaciones-seleccionadas', 'id_tipo_exposicion')" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&lt;</button>
                                        </div>

                                        <!-- Lista derecha -->
                                        <select name="capacitaciones[]" id="capacitaciones-seleccionadas" multiple required 
                                            class="w-1/2 h-40 border border-gray-300 rounded-lg p-1 text-sm"></select>
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
        </div>

        <!-- Buscador a la derecha -->
        <form action="{{ route('quimicotipoexposicion') }}" method="GET"
            class="relative w-full max-w-sm bg-white flex items-center">
            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar..."
                    oninput="this.form.submit()"
                    class="pl-10 pr-10 py-2 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" />
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
                        Nombre Comercial
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Uso
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estado Físico
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Salud
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Inflamabilidad
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Reactividad
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Ninguno
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Partículas de Polvo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Sustancias Corrosivas
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Sustancias Tóxicas
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Sustancias Irritantes
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Tipos de Exposición
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quimicotipoexposicion as $quimicotipoexposicione)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->nombre_comercial }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->uso }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->estado_fisico }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->salud }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->inflamabilidad }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->reactividad }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->ninguno }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->particulas_polvo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->sustancias_corrosivas }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->sustancias_toxicas }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->sustancias_irritantes }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimicotipoexposicione->tipos_exposicion }}
                    </td>
                    <td class="flex items-center px-6 py-4">
                    
                        <button data-modal-target="popup-modal-{{ $quimicotipoexposicione->id_quimico }}" data-modal-toggle="popup-modal-{{ $quimicotipoexposicione->id_quimico }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $quimicotipoexposicione->id_quimico }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal-{{ $quimicotipoexposicione->id_quimico }}">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                        </svg>
                                        <span class="sr-only">Close modal</span>
                                    </button>
                                    <div class="p-4 md:p-5 text-center">
                                        <svg class="mx-auto mb-4 text-gray-400 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                        <h3 class="mb-5 text-lg font-normal text-gray-500">¿Está seguro de eliminar este item?</h3>
                                        <form action="{{ route('quimicotipoexposicion.destroyquimicotipoexposicion', $quimicotipoexposicione->id_quimico) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                                                No, cancelar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $quimicotipoexposicion->links() }}
    </div>

    <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Script de mover opciones -->
    <script>
    function mover(origenId, destinoId) {
        const origen = document.getElementById(origenId);
        const destino = document.getElementById(destinoId);
        Array.from(origen.selectedOptions).forEach(opt => destino.appendChild(opt));
    }

    function moverTodos(origenId, destinoId) {
        const origen = document.getElementById(origenId);
        const destino = document.getElementById(destinoId);
        Array.from(origen.options).forEach(opt => destino.appendChild(opt));
    }
    </script>
@endsection