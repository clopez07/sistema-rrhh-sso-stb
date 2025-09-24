@extends('layouts.organigrama')

@section('title', 'Administrar Matriz de Puestos')

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
            <span class="text-sm font-medium text-black">Matriz de Puestos</span>
        </div>
        </li>
    </ol>
    </nav>
    <br> 
<!-- SELECT + BOTONES -->
<div class="flex gap-4 items-center mb-6">
    <select id="select-departamento" class="border p-2">
        <option value="">-- Selecciona un departamento --</option>
        @foreach ($matrizpuestos->pluck('departamento')->unique() as $dep)
            <option value="{{ $dep }}">{{ $dep }}</option>
        @endforeach
    </select>
    <button id="btn-organigrama" class="px-4 py-2 bg-blue-500 text-white rounded">
        Ver Organigrama por Departamento
    </button>
    <button id="btn-operativo" class="px-4 py-2 bg-purple-500 text-white rounded">
        Organigrama Operativo
    </button>
    <button id="btn-admin" class="px-4 py-2 bg-orange-500 text-white rounded">
        Organigrama Administrativo
    </button>
    <span id="btn-ga-ep" style="display:none"></span>
</div>

<!-- Contenedor donde se dibuja el organigrama -->
<div id="organigrama-container" style="display:none; margin-top:20px;">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold">Organigrama</h2>
        <div id="legend" class="flex flex-wrap gap-2 text-xs"></div>
        <button id="btn-descargar" class="px-4 py-2 bg-green-500 text-white rounded">
            Descargar Excel
        </button>
    </div>
    <div id="chart-wrapper">
        <div id="chart_div"></div>
    </div>
</div>

    <div class="flex justify-between items-center w-full mb-4">
    <!-- Botones a la izquierda -->
    <div class="inline-flex rounded-md shadow-xs" role="group">
        <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
            <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
            </svg>
            Agregar
        </button>
   <!-- Main modal -->
    <div id="create-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-md md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-4xl max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Ingresar nuevo puesto a la matriz
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{ route('matrizpuestos.storematrizpuestos') }}" method="POST" class="p-4 md:p-5">
                    @csrf 
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <label for="id_puesto_trabajo" class="block mb-2 text-sm font-medium text-gray-900">Puesto de Trabajo</label>
                            <select name="id_puesto_trabajo" id="id_puesto_trabajo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione un puesto de Trabajo</option>
                                @foreach($puestostrabajo as $puesto)
                                    <option value="{{ $puesto->id_puesto_trabajo_matriz }}">{{ $puesto->puesto_trabajo_matriz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="id_nivel_jerarquico" class="block mb-2 text-sm font-medium text-gray-900">Nivel Jerarquico</label>
                            <select name="id_nivel_jerarquico" id="id_nivel_jerarquico" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione un nivel</option>
                                @foreach($niveles as $nivel)
                                    <option value="{{ $nivel->id_nivel_jerarquico }}">{{ $nivel->nivel_jerarquico }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="id_puesto_superior" class="block mb-2 text-sm font-medium text-gray-900">Puesto Superior</label>
                            <select name="id_puesto_superior" id="id_puesto_superior" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione el puesto de Trabajo Superior</option>
                                @foreach($puestostrabajo as $puesto)
                                    <option value="{{ $puesto->id_puesto_trabajo_matriz }}">{{ $puesto->puesto_trabajo_matriz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Puestos de Trabajo Subordinados</label>
                            <input type="text" id="buscar-capacitacion" placeholder="Buscar"
                                class="w-full mb-2 p-2 text-sm border border-gray-300 rounded-lg" />
                            <div class="flex gap-2">
                                <!-- Lista izquierda -->
                                <select name="id_puesto" id="id_puesto" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-48 p-2.5" multiple>
                                    @foreach($puestostrabajo as $puesto)
                                        <option value="{{ $puesto->id_puesto_trabajo_matriz }}">{{ $puesto->puesto_trabajo_matriz }}</option>
                                    @endforeach
                                </select>

                                <!-- Botones -->
                                <div class="flex flex-col justify-center gap-1">
                                    <button type="button" 
                                        onclick="mover('id_puesto', 'subordinado')" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&gt;</button>
                                    <button type="button" 
                                        onclick="mover('subordinado', 'id_puesto')" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">&lt;</button>
                                </div>

                                <!-- Lista derecha -->
                                <select name="subordinado[]" id="subordinado" multiple
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5"></select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                        Guardar
                    </button>
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
                filtrarSelect("buscar-capacitacion", "id_puesto");
                </script>
    </div> 


                <form id="importForm" action="{{ route('matrizpuestos.importar') }}" method="POST" enctype="multipart/form-data" style="display: none;">
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

        <a href="{{ route('matrizpuestos.exportar') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-e-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
            <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/>
            </svg>
            Exportar
            </a>
    </div>

<br>
<!-- Botones a la derecha -->
<div class="inline-flex rounded-md shadow-xs" role="group">
        <a href="/niveles" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-l-lg hover:bg-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-500">
        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>
        </svg>
             Ver Niveles Jerarquicos
        </a>
    </div>
</div>

<form action="{{ route('matrizpuestos') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
        <div class="relative w-full">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar por nombre o nivel jerárquico"
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

    <div class="relative overflow-x-auto overflow-y-auto max-h-[70vh] shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left text-gray-500 border-separate border-spacing-0">
            <thead class="sticky top-0 z-20 bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Item
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nivel Jerarquico
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Num Nivel
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Superior Inmediato
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Subordinado
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Objetivo del Puesto
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matrizpuestos as $matrizdepuestos)
                @php
                    $nivToken = strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string)($matrizdepuestos->nivel_jerarquico ?? '')));
                    $nivelMap = [
                        'DIRECTIVO' => 1,
                        'GERENCIAL' => 2,
                        'ESTRATEGICO' => 3,
                        'OPERATIVO' => 4,
                        'COORDINACION' => 5,
                        'APOYOAUXILIAR' => 6,
                        'EJECUCION' => 7,
                    ];
                    $nivelOrder = $nivelMap[$nivToken] ?? 999;
                @endphp
                <tr class="border-b border-gray-200 niv-{{ $nivToken }}">
                    <td class="px-6 py-4 text-center">
                        {{ $loop->iteration }}
                  </td>
                    <td class="px-6 py-4">
                        {{ $matrizdepuestos->puesto_actual }}
                    </td>
                    <td class="px-6 py-4" data-order="{{ $nivelOrder }}">
                        {{ $matrizdepuestos->nivel_jerarquico }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $matrizdepuestos->num_nivel }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $matrizdepuestos->puesto_superior }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $matrizdepuestos->puestos_subordinados }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $matrizdepuestos->objetivo_puesto }}
                    </td>
                    <td class="flex items-center px-6 py-4">

                        <button data-modal-target="edit-modal-{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" data-modal-toggle="edit-modal-{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" href="#" class="font-medium text-blue-600 hover:underline">Editar</button>

                        <!-- Modal Editar -->
                        <div id="edit-modal-{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-4xl max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <!-- Header -->
                                    <div class="flex items-center justify-between p-4 border-b rounded-t border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900">Editar puesto en la matriz</h3>
                                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto flex justify-center items-center" data-modal-toggle="edit-modal-{{ $matrizdepuestos->id_puesto_trabajo_matriz }}">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Body -->
                                    <form action="{{ route('matrizpuestos.updatematrizpuestos', $matrizdepuestos->id_puesto_trabajo_matriz) }}" method="POST" class="p-4 md:p-5">
                                        @csrf
                                        @method('PUT')

                                        @php
                                            $subsSeleccionados = array_filter(array_map('trim', explode(',', $matrizdepuestos->puestos_subordinados ?? '')));
                                            $nombrePuestoActual = $matrizdepuestos->puesto_actual ?? $matrizdepuestos->puesto_trabajo_matriz ?? '';
                                        @endphp

                                        <div class="grid gap-4 mb-4 grid-cols-2">
                                            <!-- Puesto (solo lectura) -->
                                            <div class="col-span-2">
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Puesto de Trabajo</label>
                                                <input type="text" value="{{ $nombrePuestoActual }}" disabled class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                                <input type="hidden" name="id_puesto_trabajo" value="{{ $matrizdepuestos->id_puesto_trabajo_matriz }}">
                                            </div>

                                            <!-- Nivel jerárquico -->
                                            <div>
                                                <label for="id_nivel_jerarquico_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" class="block mb-2 text-sm font-medium text-gray-900">Nivel Jerarquico</label>
                                                <select name="id_nivel_jerarquico" id="id_nivel_jerarquico_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                                    <option value="" disabled>Seleccione un nivel</option>
                                                    @foreach($niveles as $nivel)
                                                        <option value="{{ $nivel->id_nivel_jerarquico }}" {{ ($nivel->nivel_jerarquico ?? '') == ($matrizdepuestos->nivel_jerarquico ?? '') ? 'selected' : '' }}>
                                                            {{ $nivel->nivel_jerarquico }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Puesto Superior -->
                                            <div>
                                                <label for="id_puesto_superior_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" class="block mb-2 text-sm font-medium text-gray-900">Puesto Superior</label>
                                                <select name="id_puesto_superior" id="id_puesto_superior_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                                    <option value="" {{ empty($matrizdepuestos->puesto_superior) ? 'selected' : '' }}>Sin superior</option>
                                                    @foreach($puestostrabajo as $puesto)
                                                        @php $nombrePuesto = $puesto->puesto_trabajo_matriz ?? ''; @endphp
                                                        <option value="{{ $puesto->id_puesto_trabajo_matriz }}" {{ $nombrePuesto == ($matrizdepuestos->puesto_superior ?? '') ? 'selected' : '' }}>
                                                            {{ $puesto->puesto_trabajo_matriz }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Subordinados: doble lista -->
                                            <div class="col-span-2">
                                                <label class="block mb-2 text-sm font-medium text-gray-900">Seleccionar Subordinados</label>
                                                <div class="grid grid-cols-3 gap-2 items-center">
                                                    <!-- Lista izquierda -->
                                                    <select id="sub_izq_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full h-48 p-2.5" multiple>
                                                        @foreach($puestostrabajo as $puesto)
                                                            @php $n = $puesto->puesto_trabajo_matriz ?? ''; @endphp
                                                            @if($n !== $nombrePuestoActual && !in_array($n, $subsSeleccionados))
                                                                <option value="{{ $puesto->id_puesto_trabajo_matriz }}">{{ $n }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>

                                                    <div class="flex flex-col gap-2 justify-center items-center">
                                                        <button type="button" onclick="mover('sub_izq_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}','sub_der_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}')" class="px-3 py-1 border rounded">&gt;</button>
                                                        <button type="button" onclick="moverTodos('sub_izq_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}','sub_der_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}')" class="px-3 py-1 border rounded">&gt;&gt;</button>
                                                        <button type="button" onclick="mover('sub_der_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}','sub_izq_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}')" class="px-3 py-1 border rounded">&lt;</button>
                                                        <button type="button" onclick="moverTodos('sub_der_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}','sub_izq_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}')" class="px-3 py-1 border rounded">&lt;&lt;</button>
                                                    </div>

                                                    <!-- Lista derecha -->
                                                    <select name="subordinado[]" id="sub_der_{{ $matrizdepuestos->id_puesto_trabajo_matriz }}" multiple class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full h-48 p-2.5">
                                                        @foreach($puestostrabajo as $puesto)
                                                            @php $n = $puesto->puesto_trabajo_matriz ?? ''; @endphp
                                                            @if(in_array($n, $subsSeleccionados))
                                                                <option value="{{ $puesto->id_puesto_trabajo_matriz }}">{{ $n }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="mt-2 text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-5 py-2.5">Guardar cambios</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="font-medium text-red-600 hover:underline ms-3 js-delete-mpz" data-id="{{ $matrizdepuestos->id_puesto_trabajo_matriz }}">Eliminar</button>

                        <div id="popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal">
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
                                        <form>
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
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
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS y CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    
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
<!-- ExcelJS para exportar a Excel -->
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<!-- Treant.js + dependencias -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/treant-js/1.0/Treant.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/treant-js/1.0/Treant.min.js"></script>

<!-- html2canvas para capturar y descargar -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
const puestosData = @json($matrizpuestos);
const empleadosPorPuesto = @json($empleadosPorPuesto ?? []);
window.empleadosPorPuesto = empleadosPorPuesto;
// Índice normalizado para mapear puesto -> empleados sin depender de tildes/caso/espacios
const _empIndex = (() => {
  const m = new Map();
  try {
    Object.entries(empleadosPorPuesto || {}).forEach(([k, v]) => {
      const key = (k||'').toString().trim().toUpperCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/\s+/g,' ');
      m.set(key, Array.isArray(v) ? v : (v ? [v] : []));
    });
  } catch (e) { /* noop */ }
  return m;
})();
function empleadosDePuesto(nombrePuesto){
  const key = (nombrePuesto||'').toString().trim().toUpperCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ');
  return _empIndex.get(key) || [];
}

/* ====== Niveles ====== */
const mapNumToNivel = {
  '1':'DIRECTIVO','2':'GERENCIAL','3':'ESTRATEGICO',
  '4':'OPERATIVO','5':'COORDINACION','6':'APOYO/AUXILIAR','7':'EJECUCION'
};
const levelOrder = {
  'DIRECTIVO':1,'GERENCIAL':2,'ESTRATEGICO':3,
  'OPERATIVO':4,'COORDINACION':5,'APOYO/AUXILIAR':6,'EJECUCION':7
};
const levelColors = {
  'DIRECTIVO':'#305496','GERENCIAL':'#8EA9DB','ESTRATEGICO':'#B4C6E7',
  'OPERATIVO':'#D9E1F2','COORDINACION':'#F2F2F2','APOYO/AUXILIAR':'#D9D9D9','EJECUCION':'#AEAAAA'
};

/* ====== Utils ====== */
function normalize(s){
  return (s??'').toString().trim().toUpperCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'');
}
function nivelCanonico(n){
  const v = normalize(n);
  if (/^[1-7]$/.test(v)) return mapNumToNivel[v];
  if (v.includes('APOYO')) return 'APOYO/AUXILIAR';
  return v || '';
}
function nivelOrden(n){ return levelOrder[n] ?? 999; }
function nivelColor(n){ return levelColors[n] || '#999'; }
function nivelClass(n){ return normalize(n).replace(/[^\w]/g,''); } // sin espacios ni '/'

function escapeHtml(t){
  return (t??'').toString()
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

/* ====== Leyenda ====== */
function renderLegend(){
  const legend = document.getElementById('legend');
  if(!legend) return;
  legend.innerHTML = '';
  Object.keys(levelOrder).sort((a,b)=>levelOrder[a]-levelOrder[b]).forEach(nivel=>{
    const chip = document.createElement('div');
    chip.className = 'flex items-center gap-1 px-2 py-1 rounded';
    chip.style.background = '#f5f5f5';
    chip.innerHTML = `
      <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:${nivelColor(nivel)}"></span>
      <span>${nivel}</span>
    `;
    legend.appendChild(chip);
  });
}

/* ====== Helper: índice por puesto_actual ====== */
const indexByPuesto = (() => {
  const m = new Map();
  puestosData.forEach(r => m.set(r.puesto_actual, r));
  return m;
})();

/* ====== Tomar filas del departamento + todos sus ancestros reales ====== */
function getDeptRowsIncludingAncestors(dep){
  const depNorm = normalize(dep);
  // Caso especial: al elegir Gestión Ambiental, unir con Eficiencia y Productividad
  if (depNorm === normalize('Gestión Ambiental')) {
    return getRowsForDepartmentsIncludingAncestors(['Gestión Ambiental','Eficiencia y Productividad']);
  }
  // Caso especial: al elegir Recursos Humanos, unir con Enfermería
  if (depNorm === normalize('Recursos Humanos RRHH')) {
    return getRowsForDepartmentsIncludingAncestors(['Recursos Humanos RRHH','Enfermería']);
  }
  const selected = puestosData.filter(p => normalize(p.departamento) === depNorm);

  const included = new Map(); // key: puesto_actual
  const visiting = new Set();

  function addChain(row){
    if(!row || included.has(row.puesto_actual)) return;
    included.set(row.puesto_actual, row);

    // subir por la cadena de mando usando los datos reales
    let parentKey = row.puesto_superior;
    while (parentKey && indexByPuesto.has(parentKey) && !included.has(parentKey)) {
      if (visiting.has(parentKey)) break; // evita ciclos raros
      visiting.add(parentKey);
      const parentRow = indexByPuesto.get(parentKey);
      included.set(parentRow.puesto_actual, parentRow);
      parentKey = parentRow.puesto_superior;
    }
  }

  selected.forEach(addChain);
  return Array.from(included.values());
}

// Versión RAW: obtener filas de 1 departamento + ancestros, sin casos especiales
function getDeptRowsIncludingAncestorsRaw(dep){
  const depNorm = normalize(dep);
  const selected = puestosData.filter(p => normalize(p.departamento) === depNorm);

  const included = new Map();
  const visiting = new Set();

  function addChain(row){
    if(!row || included.has(row.puesto_actual)) return;
    included.set(row.puesto_actual, row);
    let parentKey = row.puesto_superior;
    while (parentKey && indexByPuesto.has(parentKey) && !included.has(parentKey)) {
      if (visiting.has(parentKey)) break;
      visiting.add(parentKey);
      const parentRow = indexByPuesto.get(parentKey);
      included.set(parentRow.puesto_actual, parentRow);
      parentKey = parentRow.puesto_superior;
    }
  }

  selected.forEach(addChain);
  return Array.from(included.values());
}

// Varias dependencias a la vez (une filas + ancestros y quita duplicados)
function getRowsForDepartmentsIncludingAncestors(deps){
  const included = new Map();
  (deps || []).forEach(d => {
    getDeptRowsIncludingAncestorsRaw(d).forEach(r => {
      included.set(r.puesto_actual, r);
    });
  });
  return Array.from(included.values());
}

/* ====== Construcción del árbol con alineación por nivel ====== */
function buildTree(rows, opts = {}) {
  const map = {};
  const roots = [];

  // 1) Crear todos los nodos
  rows.forEach(p => {
    const nivel = nivelCanonico(p.nivel_jerarquico);
    const nEmp = (p.num_empleados != null && p.num_empleados !== '') ? p.num_empleados : 0;
    let titleText = `${nEmp} Empleado(s)`;
    if (opts.withEmployees) {
      const k = normalize(p.puesto_actual).replace(/\s+/g,' ');
      const list = empleadosPorPuesto[k] || [];
      if (list.length) titleText = `${titleText} — ${list.join(', ')}`;
    }
    map[p.puesto_actual] = {
      text: { name: escapeHtml(p.puesto_actual), title: escapeHtml(titleText) },
      HTMLclass: `node ${nivelClass(nivel)}`,
      nivel: nivel,
      order: nivelOrden(nivel),
      children: []
    };
    // Si es el organigrama operativo, forzar 3 líneas: Puesto, num empleados, Nombres
    if (opts.withEmployees) {
      const list = empleadosDePuesto(p.puesto_actual);
      const nombresHtml = (list && list.length)
        ? list.map(n => `<div>${escapeHtml(n)}</div>`).join('')
        : '';
      const inner = `
        <div class="node-lines">
          <div>${escapeHtml(p.puesto_actual)}</div>
          <div>${nEmp} Empleado(s)</div>
          <div>${nombresHtml}</div>
        </div>
      `;
      map[p.puesto_actual].innerHTML = inner;
      // Usamos innerHTML para el contenido; remove text para evitar duplicado
      delete map[p.puesto_actual].text;
    }
  });

  // 2) Enlazar padre -> hijo o marcar raíces (solo entre filas incluidas)
  rows.forEach(p => {
    const parent = p.puesto_superior;
    const childNode = map[p.puesto_actual];
    if (parent && map[parent]) {
      map[parent].children.push(childNode);
    } else {
      roots.push(childNode);
    }
  });

  // 3) Si hay varias raíces, nodo virtual superior
  let root;
  if (roots.length === 1) {
    root = roots[0];
  } else {
    const minOrder = Math.min(...roots.map(r => r.order ?? 999));
    root = {
      text: { name: "Organigrama" },
      HTMLclass: "node ROOT",
      nivel: "ROOT",
      order: minOrder - 1,
      children: roots
    };
  }

  // 4) Ordenar hijos por nivel y nombre
  function sortChildren(node){
    if (!node.children || !node.children.length) return;
    node.children.sort((a,b) => (a.order - b.order) || ((a.text && a.text.name) || '').localeCompare((b.text && b.text.name) || ''));
    node.children.forEach(sortChildren);
  }
  sortChildren(root);

  // 5) Insertar "spacers" para forzar una fila por nivel (EJECUCION al final)
  function padEdge(parent, child){
    const gap = (child.order ?? 999) - (parent.order ?? 0);
    if (gap <= 1) return child;

    let firstSpacer = null;
    let prev = null;
    for (let ord = (parent.order ?? 0) + 1; ord < child.order; ord++){
      const sp = {
        text: { name: "" },
        HTMLclass: "node spacer",
        nivel: "SPACER",
        order: ord,
        children: []
      };
      if (!firstSpacer) firstSpacer = sp;
      if (prev) prev.children.push(sp);
      prev = sp;
    }
    prev.children.push(child);
    return firstSpacer;
  }

  function padTree(node){
    if (!node.children || !node.children.length) return;
    const newChildren = [];
    for (const child of node.children){
      const padded = padEdge(node, child);
      newChildren.push(padded);
      padTree(child); // seguir desde el hijo real
    }
    node.children = newChildren;
  }
  padTree(root);

  return root;
}

/* ====== Render con Treant ====== */
function dibujarOrganigramaDesde(rows, titulo, opts = {}){
  if(!rows.length){ alert('No hay puestos para el criterio seleccionado.'); return; }

  const chart_config = {
    chart: {
      container: "#chart_div",
      connectors: { type: 'step' },
      node: { HTMLclass: "node" }
    },
    nodeStructure: buildTree(rows, opts)
  };

  document.getElementById('chart_div').innerHTML = '';
  new Treant(chart_config);

  renderLegend();
  const h2 = document.querySelector('#organigrama-container h2');
  if (h2) h2.innerText = titulo;
  document.getElementById('organigrama-container').style.display = 'block';
}

/* ====== Canonicalizador de departamento (para incluir RRHH y variantes) ====== */
function depCanonico(dep){
  const n = normalize(dep);
  const noPunct = n.replace(/[^\w]/g,''); // quita puntos, barras, espacios
  if (noPunct === 'RRHH' || noPunct.startsWith('RRHH')) return 'RECURSOS HUMANOS';
  if (n.includes('RECURSO') && n.includes('HUMAN')) return 'RECURSOS HUMANOS';
  if (n.includes('TALENTO') && n.includes('HUMAN')) return 'RECURSOS HUMANOS';
  if (n.includes('CAPITAL') && n.includes('HUMAN')) return 'RECURSOS HUMANOS';
  return n;
}

/* ====== Botones ====== */
document.getElementById('btn-organigrama').addEventListener('click', function(){
  const dep = document.getElementById('select-departamento').value;
  if(!dep){ alert('Selecciona un departamento.'); return; }
  const rows = getDeptRowsIncludingAncestors(dep); // 🔹 usa datos reales + ancestros
  dibujarOrganigramaDesde(rows, `Organigrama: ${dep}`);
});

document.getElementById('btn-operativo').addEventListener('click', function(){
  const valid = new Set(['1','2','3','4','5','DIRECTIVO','GERENCIAL','ESTRATEGICO','OPERATIVO','COORDINACION']);
  const rows = puestosData.filter(
    p => valid.has(String(p.nivel_jerarquico)) || valid.has(normalize(p.nivel_jerarquico))
  );
  dibujarOrganigramaDesde(rows, 'Organigrama Operativo', { withEmployees: true });
});

document.getElementById('btn-admin').addEventListener('click', function(){
  // ➕ Incluye ADMINISTRACION, GERENCIA y RECURSOS HUMANOS (todas sus variantes)
  const target = new Set(['ADMINISTRACION','GERENCIA','RECURSOS HUMANOS']);
  const rows = puestosData.filter(p => target.has(depCanonico(p.departamento)));
  dibujarOrganigramaDesde(rows, 'Organigrama Administrativo');
});

// Botón especial: Gestión Ambiental + Eficiencia y Productividad
document.getElementById('btn-ga-ep').addEventListener('click', function(){
  const rows = getRowsForDepartmentsIncludingAncestors([
    'Gestión Ambiental',
    'Eficiencia y Productividad'
  ]);
  dibujarOrganigramaDesde(rows, 'Organigrama: Gestión Ambiental + Eficiencia y Productividad');
});


// Util: dataURL -> Uint8Array
function dataURLtoUint8Array(dataURL) {
  const base64 = dataURL.split(',')[1];
  const binary = atob(base64);
  const len = binary.length;
  const bytes = new Uint8Array(len);
  for (let i = 0; i < len; i++) bytes[i] = binary.charCodeAt(i);
  return bytes;
}


function loadScript(src) {
  return new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = src;
    s.onload = resolve;
    s.onerror = () => reject(new Error('No se pudo cargar: ' + src));
    document.head.appendChild(s);
  });
}

// Util: cargar una URL (mismo dominio) como base64
async function urlToBase64(url){
  const res = await fetch(url, { cache: 'no-store' });
  if(!res.ok) throw new Error('No se pudo cargar recurso: ' + url);
  const blob = await res.blob();
  return await new Promise((resolve, reject)=>{
    const fr = new FileReader();
    fr.onload = () => resolve(fr.result);
    fr.onerror = reject;
    fr.readAsDataURL(blob);
  });
}

// Convierte "#RRGGBB" -> "FFRRGGBB" (ARGB)
function hexToARGB(hex){ return 'FF' + hex.replace('#','').toUpperCase(); }

/**
 * Leyenda de niveles “alta”.
 * @param {Worksheet} sheet
 * @param {number} startCol  Columna inicial (1 = A)
 * @param {number} startRow  Fila inicial (1 = fila 1)
 * @param {object}  opts     { rowsPerLevel, gap, rowHeight }
 */
function addNivelesLegend(sheet, startCol, startRow, opts = {}){
  const rowsPerLevel = opts.rowsPerLevel ?? 5; // ← cuántas filas por nivel
  const gap          = opts.gap ?? 0;          // filas en blanco entre niveles
  const rowHeight    = opts.rowHeight ?? 16;

  // Título
  sheet.mergeCells(startRow, startCol, startRow, startCol + 3);
  const title = sheet.getCell(startRow, startCol);
  title.value = 'NIVELES JERÁRQUICOS';
  title.font = { bold:true, color:{argb:'FFFFFFFF'} };
  title.alignment = { horizontal:'center', vertical:'middle' };
  title.fill = { type:'pattern', pattern:'solid', fgColor:{argb: hexToARGB('#305496')} };
  sheet.getRow(startRow).height = 22;

  // Anchos de columnas
  sheet.getColumn(startCol).width     = 4;   // tira de color
  sheet.getColumn(startCol+1).width   = 6;   // cuadro del número
  sheet.getColumn(startCol+2).width   = 18;  // etiqueta
  sheet.getColumn(startCol+3).width   = 4;   // margen derecha

  const thin = { style:'thin', color:{argb:'FF9CA3AF'} };
  const borderAll = { top:thin, left:thin, right:thin, bottom:thin };

  const levels = [
    {n:1, name:'DIRECTIVO',      color:'#305496'},
    {n:2, name:'GERENCIAL',      color:'#8EA9DB'},
    {n:3, name:'ESTRATÉGICO',    color:'#B4C6E7'},
    {n:4, name:'OPERATIVO',      color:'#D9E1F2'},
    {n:5, name:'COORDINACIÓN',   color:'#F2F2F2'},
    {n:6, name:'APOYO/AUXILIAR', color:'#D9D9D9'},
    {n:7, name:'EJECUCIÓN',      color:'#AEAAAA'},
  ];

  let r = startRow + 1;
  for (const lvl of levels){
    const rEnd = r + rowsPerLevel - 1;

    // Tira vertical con color del nivel
    sheet.mergeCells(r, startCol, rEnd, startCol);
    const strip = sheet.getCell(r, startCol);
    strip.fill   = { type:'pattern', pattern:'solid', fgColor:{argb: hexToARGB(lvl.color)} };
    strip.border = borderAll;

    // Cuadro del número
    sheet.mergeCells(r, startCol+1, rEnd, startCol+1);
    const num = sheet.getCell(r, startCol+1);
    num.value       = String(lvl.n);
    num.font        = { bold:true, color:{argb:'FFFFFFFF'} };
    num.alignment   = { horizontal:'center', vertical:'middle' };
    num.fill        = { type:'pattern', pattern:'solid', fgColor:{argb: hexToARGB('#2F5597')} };
    num.border      = borderAll;

    // Etiqueta
    sheet.mergeCells(r, startCol+2, rEnd, startCol+3);
    const lab = sheet.getCell(r, startCol+2);
    lab.value      = lvl.name;
    lab.font       = { bold:true };
    lab.alignment  = { horizontal:'left', vertical:'middle' };
    lab.border     = borderAll;

    // Altura de cada fila que compone el bloque
    for (let rr = r; rr <= rEnd; rr++) sheet.getRow(rr).height = rowHeight;

    r = rEnd + 1 + gap; // siguiente bloque
  }
}

// ---- DESCARGA A EXCEL ----
document.getElementById('btn-descargar').addEventListener('click', async function () {
  try {
    const chartElement = document.getElementById('chart_div');
    if (!chartElement || !chartElement.childElementCount) {
      alert('Primero genera el organigrama antes de descargar.');
      return;
    }

    // Canvas de alta resolución (y tolerante a CORS)
    const canvas = await html2canvas(chartElement, {
      backgroundColor: "#ffffff",
      scale: 2,
      useCORS: true,
      allowTaint: true
    });

    // Imagen del organigrama en base64
    const dataUrl = canvas.toDataURL('image/png');
    const outW = canvas.width;
    const outH = canvas.height;

    if (!window.ExcelJS) throw new Error('ExcelJS no está disponible');
    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('Organigrama');

    // Configurar columnas (ancho homogéneo para centrar elementos)
    const COLS = 22;
    for (let c=1; c<=COLS; c++) sheet.getColumn(c).width = 4.5;

    const titulo = (document.querySelector('#organigrama-container h2')?.innerText || 'Organigrama').trim();
    // Determinar etiqueta (OPERATIVO/ADMINISTRATIVO/otro)
    let etiqueta = '';
    const tUpper = titulo.toUpperCase();
    if (tUpper.includes('OPERATIVO')) etiqueta = 'OPERATIVO';
    else if (tUpper.includes('ADMINISTRATIVO')) etiqueta = 'ADMINISTRATIVO';
    else etiqueta = titulo.replace(/^Organigrama:?\s*/i,'').toUpperCase();

    // Encabezado: filas 2-5 centradas, logo a la izquierda
    // Logo (si existe)
    try {
      const logoB64 = await urlToBase64('/img/logo.png');
      const logoId = workbook.addImage({ base64: logoB64, extension: 'png' });
      sheet.addImage(logoId, { tl: { col: 17, row: 1 }, ext: { width: 180, height: 80 } });
    } catch(e) { /* si no hay logo, continuar */ }

    // Líneas de encabezado
    const headerRanges = ['T2:AL2','T3:AL3','T4:AL4','T5:AL5'];
    headerRanges.forEach(r => sheet.mergeCells(r));
    sheet.getCell('T2').value = 'SERVICE AND TRADING BUSINESS S.A. DE C.V.';
    sheet.getCell('T3').value = 'PROCESO DE RECURSOS HUMANOS/ HUMAN RESOURCES PROCESS';
    sheet.getCell('T4').value = 'ORGANIGRAMA Y MATRIZ DE PUESTO/ ORGANIZATIONAL CHART AND PLACE MATRIX';
    sheet.getCell('T5').value = etiqueta;
    ['T2','T3','T4','T5'].forEach((addr, i) => {
      const cell = sheet.getCell(addr);
      cell.alignment = { horizontal: 'center', vertical: 'middle' };
      cell.font = { bold: true, size: i === 4 ? 12 : 12 };
    });
    sheet.getCell('T5').font = { bold: true, color: { argb: 'FFFF0000' }, size: 12 };

    // Espacio antes del organigrama
    sheet.getRow(6).height = 6;

    // Insertar imagen del organigrama a partir de fila 7
    const chartId = workbook.addImage({ base64: dataUrl, extension: 'png' });
    // Centrar aproximadamente: iniciar en columna 2 (B) y fila 7
    sheet.addImage(chartId, { tl: { col: 1, row: 6 }, ext: { width: outW, height: outH } });
    // === Leyenda de niveles a la derecha del organigrama ===
    // Estimamos columnas ocupadas por la imagen para colocar la leyenda “al lado”.
    // Colócala a la derecha del organigrama
    const PX_PER_COL = 34;
    const legendStartCol = Math.ceil(1 + (outW / PX_PER_COL)) + 3;
    const legendStartRow = 2;

    // Ahora alta: 5 filas por nivel, 1 de espacio entre niveles
    addNivelesLegend(sheet,  157, 10, {
      rowsPerLevel: 10,
      gap: 1,
      rowHeight: 16
    });

    // Calcular fila de inicio del pie según alto de la imagen
    const approxRows = Math.ceil(outH / 18); // aproximación de px a filas
    const footStart = 7 + approxRows + 2;

    // Pie de página simulado en celdas
    sheet.getCell(`C${footStart}`).value = '1 Copia Archivo';
    sheet.getCell(`C${footStart+1}`).value = '1 Copia sistema';
    sheet.getCell(`C${footStart}`).font = { color: { argb: 'FF1F4E79' }, bold: true };
    sheet.getCell(`C${footStart+1}`).font = { color: { argb: 'FF1F4E79' }, bold: true };

    sheet.getCell(`L${footStart+2}`).value = '8 VERSION 2025';
    sheet.getCell(`L${footStart+2}`).alignment = { horizontal: 'center' };
    sheet.mergeCells(`K${footStart+2}:M${footStart+2}`);

    sheet.getCell(`S${footStart+2}`).value = 'STB/RRHH/001';
    sheet.mergeCells(`S${footStart+2}:U${footStart+2}`);
    sheet.getCell(`S${footStart+2}`).alignment = { horizontal: 'left' };

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `organigrama_${titulo.replace(/\s+/g,'_')}.xlsx`;
    a.click();
    URL.revokeObjectURL(a.href);
  } catch (err) {
    console.error('Error generando Excel:', err);
    alert('No se pudo generar el Excel. Revisa la consola.');
  }
});
</script>
<script>
  // Inicializa DataTables para ordenar por nivel usando data-order
  $(function(){
    const $tbl = $('#tabla-matrizpuestos');
    if ($tbl.length && $.fn.DataTable) {
      $tbl.DataTable({
        paging: false,
        info: false,
        searching: false,
        order: [[1, 'asc']], // Columna Nivel Jerarquico
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
      });
    }
  });
</script>

<!-- Eliminación con confirmación (sin modal) -->
<form id="mpz-delete-form" method="POST" style="display:none">
    @csrf
    @method('DELETE')
    <!-- action se define por JS -->
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit"></button>
    </form>
<script>
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-delete-mpz');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    if (!id) return;
    if (!confirm('¿Está seguro de eliminar este puesto de la matriz?\nSe eliminarán sus relaciones registradas.')) return;
    const form = document.getElementById('mpz-delete-form');
    form.action = '/matrizpuestos/' + encodeURIComponent(id);
    form.submit();
  });
</script>

<style>
/* ====== Colores por nivel (tabla) — editable ======
   Edita estos colores para personalizar el resaltado por nivel en la tabla */
:root {
  --mpz-DIRECTIVO-bg:   #E8EEF8;  --mpz-DIRECTIVO-border:   #305496;  --mpz-DIRECTIVO-text:   #0b1220;
  --mpz-GERENCIAL-bg:   #EFF3FF;  --mpz-GERENCIAL-border:   #8EA9DB;  --mpz-GERENCIAL-text:   #0b1220;
  --mpz-ESTRATEGICO-bg: #F3F6FD;  --mpz-ESTRATEGICO-border: #B4C6E7;  --mpz-ESTRATEGICO-text: #0b1220;
  --mpz-OPERATIVO-bg:   #F6F8FE;  --mpz-OPERATIVO-border:   #D9E1F2;  --mpz-OPERATIVO-text:   #0b1220;
  --mpz-COORDINACION-bg:#F9F9F9;  --mpz-COORDINACION-border:#F2F2F2;  --mpz-COORDINACION-text:#0b1220;
  --mpz-APOYOAUXILIAR-bg:#F2F2F2; --mpz-APOYOAUXILIAR-border:#D9D9D9; --mpz-APOYOAUXILIAR-text:#0b1220;
  --mpz-EJECUCION-bg:   #EFEFEF;  --mpz-EJECUCION-border:   #AEAAAA;  --mpz-EJECUCION-text:   #0b1220;
}

/* Asigna variables por clase de nivel normalizada (sin espacios ni acentos) */
tr.niv-DIRECTIVO     { --mpz-bg: var(--mpz-DIRECTIVO-bg);     --mpz-border: var(--mpz-DIRECTIVO-border);     --mpz-text: var(--mpz-DIRECTIVO-text); }
tr.niv-GERENCIAL     { --mpz-bg: var(--mpz-GERENCIAL-bg);     --mpz-border: var(--mpz-GERENCIAL-border);     --mpz-text: var(--mpz-GERENCIAL-text); }
tr.niv-ESTRATEGICO   { --mpz-bg: var(--mpz-ESTRATEGICO-bg);   --mpz-border: var(--mpz-ESTRATEGICO-border);   --mpz-text: var(--mpz-ESTRATEGICO-text); }
tr.niv-OPERATIVO     { --mpz-bg: var(--mpz-OPERATIVO-bg);     --mpz-border: var(--mpz-OPERATIVO-border);     --mpz-text: var(--mpz-OPERATIVO-text); }
tr.niv-COORDINACION  { --mpz-bg: var(--mpz-COORDINACION-bg);  --mpz-border: var(--mpz-COORDINACION-border);  --mpz-text: var(--mpz-COORDINACION-text); }
tr.niv-APOYOAUXILIAR { --mpz-bg: var(--mpz-APOYOAUXILIAR-bg); --mpz-border: var(--mpz-APOYOAUXILIAR-border); --mpz-text: var(--mpz-APOYOAUXILIAR-text); }
tr.niv-EJECUCION     { --mpz-bg: var(--mpz-EJECUCION-bg);     --mpz-border: var(--mpz-EJECUCION-border);     --mpz-text: var(--mpz-EJECUCION-text); }

/* Aplicación general: fondo suave, borde izquierdo y color de texto opcional */
/* Fondo igual al color de la línea izquierda */
tbody tr[class*="niv-"] { background-color: var(--mpz-border, inherit) !important; color: var(--mpz-text, inherit) !important; }
tbody tr[class*="niv-"] td { background-color: var(--mpz-border, inherit) !important; color: var(--mpz-text, inherit) !important; }
tbody tr[class*="niv-"] td:first-child {
  border-left: 6px solid var(--mpz-border, transparent);
}
/* Estilos base de nodos */
.node {
  border-radius: 10px;
  padding: 8px;
  text-align: center;
  font-size: 14px;
  font-weight: bold;
  box-shadow: 0 1px 2px rgba(0,0,0,.08);
}

/* Colores por nivel */
.DIRECTIVO { background:#305496; color:#fff; }
.GERENCIAL { background:#8EA9DB; color:#000; }
.ESTRATEGICO { background:#B4C6E7; color:#000; }
.OPERATIVO { background:#D9E1F2; color:#000; }
.COORDINACION { background:#F2F2F2; color:#000; }
.APOYOAUXILIAR { background:#D9D9D9; color:#000; }
.EJECUCION { background:#AEAAAA; color:#000; }

/* Nodos espaciadores invisibles (para alinear niveles) */
.node.spacer {
  background: transparent !important;
  color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  width: 24px;
  height: 12px;
  padding: 0;
}

/* Contenedor con scroll para ver organigramas grandes */
#chart-wrapper {
  width: 100%;
  min-height: 600px;
  overflow: auto; /* scroll horizontal y vertical */
  border: 1px solid #ccc;
  padding: 10px;
}
#chart_div {
  display: inline-block;
  min-width: 100%;
  min-height: 600px;
}
</style>
@endsection
