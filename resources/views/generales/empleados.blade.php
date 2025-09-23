<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>INICIO</title>
        
        <!-- Tailwind CSS (vía CDN) -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Flowbite (JS) -->
        <script src="https://unpkg.com/flowbite@2.3.0/dist/flowbite.min.js"></script>
        
        <!-- BootsTrap -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] flex p-4 sm:p-6 lg:p-10 min-h-screen flex-col">
    
        <nav class="bg-white border border-gray-400 rounded-md">
            <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl p-4">
                <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img src="{{ asset('img/logo.PNG') }}" alt="Logo" class="h-12 w-auto mr-3">
                    <span class="self-center text-2xl font-semibold whitespace-nowrap">Service And Trading Bussines - Empleados</span>
                </a>
                <div class="flex items-center space-x-6 rtl:space-x-reverse">
                    <!-- Modal toggle -->
                    <button data-modal-target="select-modal" data-modal-toggle="select-modal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" type="button">
                    Cambiar de Módulo
                    </button>
                </div>

                <!-- Main modal -->
                <div id="select-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                    <div class="relative p-4 w-full max-w-md max-h-full">
                        <!-- Modal content -->
                        <div class="relative bg-white rounded-lg shadow-sm">
                            <!-- Modal header -->
                            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Cambiar de Módulo
                                </h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm h-8 w-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="select-modal">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                    </svg>
                                    <span class="sr-only">Close modal</span>
                                </button>
                            </div>
                            <!-- Modal body -->
                            <div class="p-4 md:p-5">
                                <ul class="space-y-4 mb-4">
                                    <li>
                                        <a href="/verificacion">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Análisis de Riesgos</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/epp">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Entrega de EPP</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/Capacitaciones">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Asistencia a Capacitaciones</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/empleadosprestamo">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Prestamos</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/matrizpuestos">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Organigrama</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        </nav>
    <br>

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
            <span class="text-sm font-medium text-black">Empleados</span>
        </div>
        </li>
    </ol>
    </nav>

    <br>    
<div class="flex justify-between items-center mb-4">
    <div class="inline-flex rounded-md shadow-xs" role="group">
    <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
        <svg class="w-6 h-6 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
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
                        Ingresar Nuevo Empleado
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{ route('empleado.store') }}" method="POST" class="p-4 md:p-5">
                    @csrf
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <label for="nombre_completo" class="block mb-2 text-sm font-medium text-gray-900">Nombre Completo del Empleado</label>
                            <input type="text" name="nombre_completo" id="nombre_completo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                        </div>
                        <div class="col-span-2">
                            <label for="codigo_empleado" class="block mb-2 text-sm font-medium text-gray-900">Código del Empleado</label>
                            <input type="text" name="codigo_empleado" id="codigo_empleado" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                        </div>
                        <div class="col-span-2">
                            <label for="identidad" class="block mb-2 text-sm font-medium text-gray-900">No. Identidad Empleado</label>
                            <input type="text" name="identidad" id="identidad" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                        </div>
                        <div class="col-span-2">
                            <label for="id_puesto_trabajo" class="block mb-2 text-sm font-medium text-gray-900">Puesto de Trabajo</label>
                            <select name="id_puesto_trabajo" id="id_puesto_trabajo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione el puesto de Trabajo</option>
                                @foreach($puestos as $puesto)
                                    <option value="{{ $puesto->id_puesto_trabajo }}">{{ $puesto->puesto_trabajo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="id_puesto_trabajo_matriz" class="block mb-2 text-sm font-medium text-gray-900">Puesto (Matriz)</label>
                            <select name="id_puesto_trabajo_matriz" id="id_puesto_trabajo_matriz" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione el puesto matriz</option>
                                @foreach($puestosMatriz as $puestoMatriz)
                                    <option value="{{ $puestoMatriz->id_puesto_trabajo_matriz }}">{{ $puestoMatriz->puesto_trabajo_matriz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="estado" class="block mb-2 text-sm font-medium text-gray-900">Estado</label>
                            <select name="estado" id="estado" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                <option value="1" selected>Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Guardar
                    </button>
                </form>
            </div>
        </div>
    </div> 

    <form id="importForm" action="{{ route('empleados.import') }}" method="POST" enctype="multipart/form-data" style="display: none;">
        @csrf
        <input type="file" id="excelFileInput" name="excel_file" accept=".xls,.xlsx,.xlsm" onchange="document.getElementById('importForm').submit();">
    </form>
    <button type="button" 
        onclick="document.getElementById('excelFileInput').click();" 
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-4m5-13v4a1 1 0 0 1-1 1H5m0 6h9m0 0-2-2m2 2-2 2"/>
        </svg>
        Subir Lista de Empleados
    </button>
    </div>

    <form action="{{ route('empleados') }}" method="GET" class="flex flex-wrap gap-3 items-center w-full max-w-xl bg-white">
        <div class="relative flex-1 min-w-[220px]">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar..."
                oninput="this.form.submit()" {{-- aquí está la magia --}}
                class="pl-10 pr-10 py-2 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                </svg>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <label for="estado_filter" class="text-sm text-gray-700">Estado</label>
            <select name="estado_filter" id="estado_filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5" onchange="this.form.submit()">
                <option value="todos" @selected(($estadoFilter ?? 'todos') === 'todos')>Todos</option>
                <option value="activos" @selected(($estadoFilter ?? 'todos') === 'activos')>Activos</option>
                <option value="inactivos" @selected(($estadoFilter ?? 'todos') === 'inactivos')>Inactivos</option>
            </select>
        </div>
    </form>
</div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Nombre Completo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Código Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Identidad del Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Puesto Matriz
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Departamento
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
                @foreach ($empleados as $empleado)
                <tr class="border-b border-gray-200 {{ $empleado->estado === 'Inactivo' ? 'bg-red-100' : '' }}">
                    <td class="px-6 py-4">
                        {{ $empleado->nombre_completo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $empleado->codigo_empleado }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $empleado->identidad }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $empleado->puesto_trabajo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $empleado->puesto_trabajo_matriz ?? 'Sin asignar' }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $empleado->departamento }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $empleado->estado }}
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <button data-modal-target="edit-modal-{{ $empleado->id_empleado }}" data-modal-toggle="edit-modal-{{ $empleado->id_empleado }}" href="#" class="font-medium text-blue-600 hover:underline">Editar</button>

                        <!-- Modal editar empleado -->
                        <div id="edit-modal-{{ $empleado->id_empleado }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900">Editar Empleado</h3>
                                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="edit-modal-{{ $empleado->id_empleado }}">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <form action="{{ route('empleado.update', $empleado->id_empleado) }}" method="POST" class="p-4 md:p-5">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid gap-4 mb-4">
                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Nombre Completo</label>
                                                <input type="text" name="nombre_completo" value="{{ $empleado->nombre_completo }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                            </div>
                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Código Empleado</label>
                                                <input type="text" name="codigo_empleado" value="{{ $empleado->codigo_empleado }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                            </div>
                                            <div>
                                                <label for="identidad" class="block mb-2 text-sm font-medium text-gray-900">No. Identidad Empleado</label>
                                                <input type="text" name="identidad" value="{{ $empleado->identidad }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                            </div>
                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Puesto de Trabajo</label>
                                                <select name="id_puesto_trabajo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                                    @foreach($puestos as $puesto)
                                                        <option value="{{ $puesto->id_puesto_trabajo }}" @if($empleado->id_puesto_trabajo == $puesto->id_puesto_trabajo) selected @endif>
                                                            {{ $puesto->puesto_trabajo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Puesto (Matriz)</label>
                                                <input list="puestos-matriz-list-{{ $empleado->id_empleado }}" id="input-puesto-matriz-{{ $empleado->id_empleado }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ $empleado->puesto_trabajo_matriz ?? '' }}">
                                                <input type="hidden" name="id_puesto_trabajo_matriz" id="hidden-puesto-matriz-{{ $empleado->id_empleado }}" value="{{ $empleado->id_puesto_trabajo_matriz }}">
                                                <datalist id="puestos-matriz-list-{{ $empleado->id_empleado }}">
                                                    <option value="" label="Sin Asignar"></option>
                                                    @foreach($puestosMatriz as $puestoMatriz)
                                                        <option value="{{ $puestoMatriz->puesto_trabajo_matriz }}" data-id="{{ $puestoMatriz->id_puesto_trabajo_matriz }}"></option>
                                                    @endforeach
                                                </datalist>
                                            </div>
                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Estado</label>
                                                <select name="estado" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                                    <option value="1" @if($empleado->estado === 'Activo') selected @endif>Activo</option>
                                                    <option value="2" @if($empleado->estado !== 'Activo') selected @endif>Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                            Guardar cambios
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <button data-modal-target="popup-modal-{{ $empleado->id_empleado }}" data-modal-toggle="popup-modal-{{ $empleado->id_empleado }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $empleado->id_empleado }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="popup-modal-{{ $empleado->id_empleado }}">
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

                                        <form action="{{ route('empleados.destroy', $empleado->id_empleado) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal-{{ $empleado->id_empleado }}" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
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
        {{ $empleados->links() }}
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-wh/28A+4+RgPvYyqSRkFegJwCeMCn4m1BM5/1+Yl/0uKCU+5yr3phUZJf2o24RRA" crossorigin="anonymous"></script>
    <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(function() {
    $('[id^="edit-modal-"]').each(function() {
        var modal = $(this);
        var empleadoId = modal.attr('id').replace('edit-modal-', '');
        var input = modal.find('#input-puesto-matriz-' + empleadoId);
        var hidden = modal.find('#hidden-puesto-matriz-' + empleadoId);
        var datalist = modal.find('#puestos-matriz-list-' + empleadoId)[0];
        modal.find('form').on('submit', function(e) {
            var value = input.val();
            var id = '';
            if (value === '' || value === 'Sin Asignar') {
                id = '';
            } else {
                for (var i = 0; i < datalist.options.length; i++) {
                    if (datalist.options[i].value === value) {
                        id = datalist.options[i].getAttribute('data-id');
                        break;
                    }
                }
            }
            hidden.val(id);
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-wh/28A+4+RgPvYyqSRkFegJwCeMCn4m1BM5/1+Yl/0uKCU+5yr3phUZJf2o24RRA" crossorigin="anonymous"></script>
    </body>
</html>
