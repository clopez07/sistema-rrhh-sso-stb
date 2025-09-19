@extends('layouts.riesgos')

@section('title', 'Puestos de Trabajo para Matriz de Riesgos')

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
            <span class="text-sm font-medium text-black">Puestos de trabajo</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

    <div class="flex justify-between items-center w-full mb-4">
        <!-- Botones a la izquierda -->
        <div class="inline-flex rounded-md shadow-xs" role="group">
            <!-- Botón Agregar -->
            <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button" 
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500">
                <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                </svg>
                Agregar
            </button>
            <!-- Main modal -->
            <div id="create-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-md max-h-full">
                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg shadow-sm">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Ingresar Nuevo Puesto de Trabajo
                            </h3>
                            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                            </button>
                        </div>
                        <!-- Modal body -->
                        <form action="{{ route('puestos.storepuestos') }}" method="POST" class="p-4 md:p-5">
                            @csrf
                            <div class="grid gap-4 mb-4 grid-cols-2">
                                <!-- Estado requerido: Analisis de Riesgo (1) u Organigrama (2) -->
                                <div class="col-span-2">
                                    <span class="block text-gray-700 font-semibold mb-2">(obligatorio)</span>
                                    <div class="flex items-center gap-6">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" id="chk_analisis" class="h-4 w-4 border-gray-300 rounded">
                                            <span>Análisis de Riesgo</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" id="chk_organigrama" class="h-4 w-4 border-gray-300 rounded">
                                            <span>Organigrama</span>
                                        </label>
                                    </div>
                                    <input type="hidden" name="estado" id="estado_val" value="">
                                    <p id="estado_help" class="mt-1 text-sm text-red-600 hidden">Debes seleccionar una opción.</p>
                                </div>
                                <div class="col-span-2">
                                    <label for="puesto_trabajo" class="block mb-2 text-sm font-medium text-gray-900">Nombre del Puesto</label>
                                    <input type="text" name="puesto_trabajo" id="puesto_trabajo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                                </div>
                                <div class="col-span-2">
                                    <label for="id_departamento" class="block mb-2 text-sm font-medium text-gray-900">Departamento</label>
                                    <select name="id_departamento" id="id_departamento" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                        <option value="" disabled selected>Seleccione el departamento</option>
                                        @foreach($departamento as $departamentos)
                                            <option value="{{ $departamentos->id_departamento }}">{{ $departamentos->departamento }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label for="id_area" class="block mb-2 text-sm font-medium text-gray-900">Área</label>
                                    <select name="id_area" id="id_area" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                        <option value="" disabled selected>Seleccione el área</option>
                                        @foreach($area as $areas)
                                            <option value="{{ $areas->id_area }}">{{ $areas->area }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label for="num_empleados" class="block mb-2 text-sm font-medium text-gray-900">Número de Empleados</label>
                                    <input type="text" name="num_empleados" id="num_empleados" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                                </div>
                                <div class="col-span-2">
                                    <label for="descripcion_general" class="block text-gray-700 font-semibold mb-2">Descripción General del Puesto</label>
                                    <textarea id="descripcion_general" name="descripcion_general" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    ></textarea>
                                </div>
                                <div class="col-span-2">
                                    <label for="actividades_diarias" class="block text-gray-700 font-semibold mb-2">Actividades Diarias</label>
                                    <textarea id="actividades_diarias" name="actividades_diarias" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    ></textarea>
                                </div>
                                <div class="col-span-2">
                                    <label for="objetivo_puesto" class="block text-gray-700 font-semibold mb-2">Objetivo del Puesto</label>
                                    <textarea id="objetivo_puesto" name="objetivo_puesto" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
            <form id="importForm" action="{{ route('puestos.import') }}" method="POST" enctype="multipart/form-data" style="display: none;">
                @csrf
                <input type="file" id="excelFileInput" name="excel_file" accept=".xls,.xlsx,.xlsm" onchange="document.getElementById('importForm').submit();">
            </form>
            <button type="button" onclick="document.getElementById('excelFileInput').click();" 
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500">
                <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/>
                </svg>
                Importar
            </button>

            <!-- Botón Exportar -->
            <button type="button" 
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-e-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500">
                <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/>
                </svg>
                Exportar
            </button>
        </div>

        <!-- Botones a la derecha -->
        <div class="inline-flex rounded-md shadow-xs space-x-2" role="group">
            <a href="/departamento" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-lg hover:bg-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-500">
                Ver Departamento
            </a>
            <a href="/area" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-lg hover:bg-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-500">
                Ver Área
            </a>
        </div>
    </div>

    <!-- Barra de búsqueda abajo a la derecha -->
    <div class="flex justify-end mt-2">
        <form action="{{ route('puestos') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
            <div class="relative w-full">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar..."
                    oninput="this.form.submit()"
                    class="pl-10 pr-10 py-2 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
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
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 puestos-table">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Departamento
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Área
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Número de Empleados
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Descripción General
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Objetivo del Puesto
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Actividades Diarias
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($puestos as $puesto)
                @php
                    $estadoVal = (int) ($puesto->estado ?? 1);
                    $estadoClass = $estadoVal === 0 ? 'st-inactivo' : ($estadoVal === 2 ? 'st-organigrama' : '');
                @endphp
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50 {{ $estadoClass }}">
                    <td class="px-6 py-4">
                        {{ $puesto->puesto_trabajo_matriz }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $puesto->departamento }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $puesto->area }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $puesto->num_empleados }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $puesto->descripcion_general }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $puesto->objetivo_puesto }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $puesto->actividades_diarias }}
                    </td>
                    <td class="px-6 py-4">
                        @switch($estadoVal)
                            @case(0)
                                Inactivo
                                @break
                            @case(2)
                                Organigrama
                                @break
                            @default
                                Análisis de Riesgo
                        @endswitch
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <button data-modal-target="edit-modal-{{ $puesto->id_puesto_trabajo_matriz }}" data-modal-toggle="edit-modal-{{ $puesto->id_puesto_trabajo_matriz }}" href="#" class="font-medium text-blue-600 hover:underline">Editar</button>

                        <!-- Main modal -->
                        <div id="edit-modal-{{ $puesto->id_puesto_trabajo_matriz }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <!-- Modal content -->
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <!-- Modal header -->
                                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            Editar Puesto de Trabajo
                                        </h3>
                                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="edit-modal-{{ $puesto->id_puesto_trabajo_matriz }}">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                        </svg>
                                        <span class="sr-only">Close modal</span>
                                        </button>
                                    </div>
                                    <!-- Modal body -->
                                    <form action="{{ route('puestos.updatepuestos', $puesto->id_puesto_trabajo_matriz) }}" method="POST" class="p-4 md:p-5">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid gap-4 mb-4 grid-cols-2">
                                        <!-- Estado requerido: Analisis de Riesgo (1) u Organigrama (2) -->
                                            <div class="col-span-2">
                                                <span class="block text-gray-700 font-semibold mb-2">(obligatorio)</span>
                                                <div class="flex items-center gap-6">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" id="chk_analisis_{{ $puesto->id_puesto_trabajo_matriz }}" class="h-4 w-4 border-gray-300 rounded" {{ (int)($puesto->estado ?? 1) === 1 ? 'checked' : '' }}>
                                                        <span>Análisis de Riesgo</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" id="chk_organigrama_{{ $puesto->id_puesto_trabajo_matriz }}" class="h-4 w-4 border-gray-300 rounded" {{ (int)($puesto->estado ?? 1) === 2 ? 'checked' : '' }}>
                                                        <span>Organigrama</span>
                                                    </label>
                                                </div>
                                                <input type="hidden" name="estado" id="estado_val_{{ $puesto->id_puesto_trabajo_matriz }}" value="{{ (int)($puesto->estado ?? 1) === 2 ? '2' : '1' }}">
                                                <p id="estado_help_{{ $puesto->id_puesto_trabajo_matriz }}" class="mt-1 text-sm text-red-600 hidden">Debes seleccionar una opción.</p>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="puesto_trabajo" class="block mb-2 text-sm font-medium text-gray-900">Nombre del Puesto</label>
                                                <input type="text" name="puesto_trabajo" id="puesto_trabajo" value="{{ $puesto->puesto_trabajo_matriz }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                                            </div>
                                            <div class="col-span-2">
                                                <label for="id_departamento" class="block mb-2 text-sm font-medium text-gray-900">Departamento</label>
                                                <select name="id_departamento" id="id_departamento" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                                    @foreach($departamento as $departamentos)
                                                        <option value="{{ $departamentos->id_departamento}}" @if($puesto->id_departamento == $departamentos->id_departamento) selected @endif>
                                                            {{ $departamentos->departamento }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="id_area" class="block mb-2 text-sm font-medium text-gray-900">Área</label>
                                                <select name="id_area" id="id_area" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                                    <option value="" disabled selected>Seleccione el área</option>
                                                    @foreach($area as $areas)
                                                        <option value="{{ $areas->id_area}}" @if($puesto->id_area == $areas->id_area) selected @endif>
                                                            {{ $areas->area }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="num_empleados" class="block mb-2 text-sm font-medium text-gray-900">Número de Empleados</label>
                                                <input type="text" name="num_empleados" id="num_empleados" value="{{ $puesto->num_empleados }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                                            </div>
                                            <div class="col-span-2">
                                                <label for="descripcion_general" class="block text-gray-700 font-semibold mb-2">Descripción General del Puesto</label>
                                                <textarea id="descripcion_general" name="descripcion_general" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $puesto->descripcion_general }}</textarea>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="actividades_diarias" class="block text-gray-700 font-semibold mb-2">Actividades Diarias</label>
                                                <textarea id="actividades_diarias" name="actividades_diarias" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $puesto->actividades_diarias }}</textarea>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="objetivo_puesto" class="block text-gray-700 font-semibold mb-2">Objetivo del Puesto</label>
                                                <textarea id="objetivo_puesto" name="objetivo_puesto" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $puesto->objetivo_puesto }}</textarea>
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

                        <button data-modal-target="popup-modal-{{ $puesto->id_puesto_trabajo_matriz }}" data-modal-toggle="popup-modal-{{ $puesto->id_puesto_trabajo_matriz }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $puesto->id_puesto_trabajo_matriz }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal-{{ $puesto->id_puesto_trabajo_matriz }}">
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
                                        <form action="{{ route('puestos.destroypuestos', $puesto->id_puesto_trabajo_matriz) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal-{{ $puesto->id_puesto_trabajo_matriz }}" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
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
        {{ $puestos->links() }}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-wh/28A+4+RgPvYyqSRkFegJwCeMCn4m1BM5/1+Yl/0uKCU+5yr3phUZJf2o24RRA" crossorigin="anonymous"></script>
    
    <style>
      /* Ancho mínimo por columna para evitar textos demasiado altos */
      table.puestos-table { table-layout: auto; }
      table.puestos-table th, table.puestos-table td { vertical-align: top; }

      /* 1: Puesto de Trabajo */
      table.puestos-table th:nth-child(1),
      table.puestos-table td:nth-child(1) { min-width: 220px; }

      /* 2: Departamento */
      table.puestos-table th:nth-child(2),
      table.puestos-table td:nth-child(2) { min-width: 160px; }

      /* 3: Localización/Ubicación */
      table.puestos-table th:nth-child(3),
      table.puestos-table td:nth-child(3) { min-width: 180px; }

      /* 4: Área */
      table.puestos-table th:nth-child(4),
      table.puestos-table td:nth-child(4) { min-width: 140px; }

      /* 5: Número de Empleados */
      table.puestos-table th:nth-child(5),
      table.puestos-table td:nth-child(5) { min-width: 120px; text-align: center; }

      /* 6: Descripción General */
      table.puestos-table th:nth-child(6),
      table.puestos-table td:nth-child(6) { min-width: 360px; }

      /* 7: Objetivo del Puesto */
      table.puestos-table th:nth-child(7),
      table.puestos-table td:nth-child(7) { min-width: 320px; }

      /* 8: Actividades Diarias */
      table.puestos-table th:nth-child(8),
      table.puestos-table td:nth-child(8) { min-width: 420px; }

      /* 9: Estado */
      table.puestos-table th:nth-child(9),
      table.puestos-table td:nth-child(9) { min-width: 110px; }

      /* 10: Acciones */
      table.puestos-table th:nth-child(10),
      table.puestos-table td:nth-child(10) { min-width: 140px; }

      /* Estados visuales: 0 rojo, 2 morado, 1 sin color */
      tbody tr.st-inactivo, tbody tr.st-inactivo td { background-color: #fee2e2 !important; color: #991b1b !important; }
      tbody tr.st-organigrama, tbody tr.st-organigrama td { background-color: #ede9fe !important; color: #5b21b6 !important; }

      /* Mantener saltos de línea existentes y permitir buen wrap */
      table.puestos-table td { white-space: pre-line; }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const chkAnalisis = document.getElementById('chk_analisis');
        const chkOrg = document.getElementById('chk_organigrama');
        const estadoVal = document.getElementById('estado_val');
        const estadoHelp = document.getElementById('estado_help');
        const createForm = document.querySelector('#create-modal form[action$="{{ route('puestos.storepuestos') }}"]') || document.querySelector('#create-modal form');

        function updateEstado(from) {
          if (from === 'analisis') {
            if (chkAnalisis.checked) {
              chkOrg.checked = false;
              estadoVal.value = '1';
            } else if (!chkOrg.checked) {
              estadoVal.value = '';
            }
          } else if (from === 'org') {
            if (chkOrg.checked) {
              chkAnalisis.checked = false;
              estadoVal.value = '2';
            } else if (!chkAnalisis.checked) {
              estadoVal.value = '';
            }
          }
          if (estadoVal.value) {
            estadoHelp.classList.add('hidden');
          }
        }

        if (chkAnalisis && chkOrg) {
          chkAnalisis.addEventListener('change', () => updateEstado('analisis'));
          chkOrg.addEventListener('change', () => updateEstado('org'));
        }

        if (createForm) {
          createForm.addEventListener('submit', (e) => {
            if (!estadoVal.value) {
              e.preventDefault();
              estadoHelp.classList.remove('hidden');
            }
          });
        }

        // Para cada modal de edición, preparar el comportamiento de estado
        document.querySelectorAll('[id^="edit-modal-"]').forEach(modal => {
          const id = modal.id.replace('edit-modal-','');
          const a = document.getElementById('chk_analisis_'+id);
          const o = document.getElementById('chk_organigrama_'+id);
          const val = document.getElementById('estado_val_'+id);
          const help = document.getElementById('estado_help_'+id);
          const form = modal.querySelector('form');
          if (!a || !o || !val || !form) return;

          function upd(which){
            if (which==='a'){
              if (a.checked){ o.checked=false; val.value='1'; } else if (!o.checked){ val.value=''; }
            } else {
              if (o.checked){ a.checked=false; val.value='2'; } else if (!a.checked){ val.value=''; }
            }
            if (val.value) help && help.classList.add('hidden');
          }
          a.addEventListener('change', ()=>upd('a'));
          o.addEventListener('change', ()=>upd('o'));
          form.addEventListener('submit', e=>{
            if (!val.value){ e.preventDefault(); help && help.classList.remove('hidden'); }
          });
        });
      });
    </script>

@endsection
