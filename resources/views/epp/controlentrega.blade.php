@extends('layouts.epp')

@section('title', 'Control de Entrega de EPP')

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
            <span class="text-sm font-medium text-black">Control de Entrega</span>
        </div>
        </li>
    </ol>
    </nav>
    <br> 
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 border border-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 border border-red-200">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 border border-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="inline-flex rounded-md shadow-xs" role="group">
    <button data-modal-target="crud-modal" data-modal-toggle="crud-modal" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
        <svg class="w-6 h-6 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
        </svg>
         Agregar
    </button>


    <!-- Modal de agregar nueva capacitacion -->
    <div id="crud-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-3xl max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Agregar Asistencia
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="crud-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                 <form action="{{ route('controlentrega.store') }}" method="POST" class="p-4 md:p-5 space-y-6">
                    @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Seleccione los nombres de los empleados</label>
                    <input type="text" id="buscar-empleado" placeholder="Buscar empleado"
                                class="w-full mb-2 p-2 text-sm border border-gray-300 rounded-lg" />

                    <div class="flex gap-2">
                    <!-- Lista izquierda -->
                    <select id="id_empleado" multiple 
                    class="w-[45%] h-40 border border-gray-300 rounded-lg p-1 text-sm">
                        @foreach($empleados as $empleado)
                            <option value="{{ $empleado->id_empleado }}">
                                {{ $empleado->nombre_completo }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Botones -->
                    <div class="flex flex-col justify-center gap-1">
                        <button type="button" onclick="mover('id_empleado', 'empleados-seleccionados')" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&gt;</button>
                        <button type="button" onclick="mover('empleados-seleccionados', 'id_empleado')" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&lt;</button>
                        <button type="button" onclick="moverTodos('id_empleado', 'empleados-seleccionados')" class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-sm">Todos &gt;&gt;</button>
                        <button type="button" onclick="moverTodos('empleados-seleccionados', 'id_empleado')" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm">&lt;&lt; Todos</button>
                    </div>

                    <!-- Lista derecha -->
                    <select name="empleados[]" id="empleados-seleccionados" multiple class="w-1/2 h-40 border border-gray-300 rounded-lg p-1 text-sm"></select>
                    </div>
                </div>

                <!-- Capacitaciones -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Seleccione el EPP</label>
                    <input type="text" id="buscar-capacitacion" placeholder="Buscar EPP"
                                class="w-full mb-2 p-2 text-sm border border-gray-300 rounded-lg" />

                    <div class="flex gap-2">
                    <!-- Lista izquierda -->
                    <select id="id_epp" multiple class="w-1/2 h-40 border border-gray-300 rounded-lg p-1 text-sm">
                        @foreach($equipo as $equipos)
                            <option value="{{ $equipos->id_epp }}">
                                {{ $equipos->equipo }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Botones -->
                    <div class="flex flex-col justify-center gap-1">
                        <button type="button" onclick="mover('id_epp', 'capacitaciones-seleccionadas')" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&gt;</button>
                        <button type="button" onclick="mover('capacitaciones-seleccionadas', 'id_epp')" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&lt;</button>
                    </div>

                    <!-- Lista derecha -->
                    <select name="epp[]" id="capacitaciones-seleccionadas" multiple class="w-1/2 h-40 border border-gray-300 rounded-lg p-1 text-sm"></select>
                    </div>
                </div>

                <!-- Datos adicionales -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                    <label for="fecha" class="block text-sm font-medium text-gray-900">Fecha de entrega</label>
                    <input type="date" id="fecha" name="fecha" class="w-full p-2 text-sm border border-gray-300 rounded-lg" />
                    </div>
                </div>

                <!-- Botón guardar -->
                <div class="text-end">
                    <button type="submit" formtarget="_blank" data-modal-hide="crud-modal" class="bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg text-sm px-5 py-2.5">
                    GUARDAR
                    </button>
                </div>
                </form>
            </div>
        </div>

        <script>
            function filtrarSelect(inputId, selectId) {
                const input = document.getElementById(inputId);
                const select = document.getElementById(selectId);
                
                input.addEventListener("input", function() {
                    const filtro = this.value.toLowerCase();

                    // Recorre todas las opciones
                    for (let option of select.options) {
                        const texto = option.text.toLowerCase();
                        option.style.display = texto.includes(filtro) ? "" : "none";
                    }
                });
            }

            // Enlazar búsquedas
            filtrarSelect("buscar-empleado", "id_empleado");
            filtrarSelect("buscar-capacitacion", "id_epp");
        </script>
    </div> 

        <!-- Botón subir lista -->
        <form id="importForm" action="{{ route('controlentrega.import') }}" method="POST" enctype="multipart/form-data"
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
            Subir Lista de Entrega
        </button>
    </div>
    
    <!-- Modal para seleccionar puesto de trabajo -->
    <div id="modal-exportar-epp" tabindex="-1" aria-hidden="true" 
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 
        justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">

        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-4 border-b rounded-t">
                    <h3 class="text-lg font-semibold">Seleccionar Puesto de Trabajo</h3>
                    <button type="button" class="text-gray-400 hover:bg-gray-200 rounded-lg w-8 h-8" 
                        data-modal-toggle="modal-exportar-epp">
                        ✖
                    </button>
                </div>
                <form method="GET" action="{{ route('epp.export') }}" class="p-4 space-y-4">
                    <label for="puesto" class="block text-sm font-medium">Puesto de trabajo</label>
                    <select id="puesto" name="puesto" required 
                        class="w-full border border-gray-300 rounded-lg p-2">
                        @foreach($puestos as $puesto)
                            <option value="{{ $puesto->id_puesto_trabajo }}">{{ $puesto->puesto_trabajo }}</option>
                        @endforeach
                    </select>
                    <div class="text-right">
                        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg">
                            Exportar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="flex justify-end mb-4">
        <form action="{{ route('controlentrega') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
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
                        Nombre del Empleado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Departamento
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Equipo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha de Entrega
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($controlentrega as $controlentregas)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $controlentregas->nombre_completo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $controlentregas->puesto_trabajo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $controlentregas->departamento }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $controlentregas->equipo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $controlentregas->fecha_entrega_epp }}
                    </td>
                    <td class="flex items-center px-6 py-4">

                        <button data-modal-target="popup-modal-{{ $controlentregas->id_asignacion_epp }}" data-modal-toggle="popup-modal-{{ $controlentregas->id_asignacion_epp }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $controlentregas->id_asignacion_epp }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal-{{ $controlentregas->id_asignacion_epp }}">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                        </svg>
                                        <span class="sr-only">Close modal</span>
                                    </button>
                                    <div class="p-4 md:p-5 text-center">
                                        <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                        <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">¿Está seguro de eliminar este item?</h3>
                                        <form action="{{ route('controlentrega.destroy', $controlentregas->id_asignacion_epp) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal-{{ $controlentregas->id_asignacion_epp }}" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
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
            {{ $controlentrega->links() }}
    </div>

    
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
