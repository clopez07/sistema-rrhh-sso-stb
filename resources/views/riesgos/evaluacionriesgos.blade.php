@extends('layouts.riesgos')

@section('title', 'Señalizaciones')

@section('content')

@php
    // Déjalos ARRIBA, fuera del foreach
    $mapNivelColor = [
        'Riesgo Muy Alto'    => '#ff0000',
        'Riesgo Alto'        => '#be5014',
        'Riesgo Medio'       => '#ffc000',
        'Riesgo Bajo'        => '#ffff00',
        'Riesgo Irrelevante' => '#92d050',
        '5' => '#ff0000', '4' => '#be5014', '3' => '#ffc000', '2' => '#ffff00', '1' => '#92d050',
    ];

    $keys = [
        // con y sin el prefijo "Riesgo"
        'RIESGO MUY ALTO' => 'Riesgo Muy Alto', 'MUY ALTO' => 'Riesgo Muy Alto',
        'RIESGO ALTO'     => 'Riesgo Alto',     'ALTO'     => 'Riesgo Alto',
        'RIESGO MEDIO'    => 'Riesgo Medio',    'MEDIO'    => 'Riesgo Medio',
        'RIESGO BAJO'     => 'Riesgo Bajo',     'BAJO'     => 'Riesgo Bajo',
        'RIESGO IRRELEVANTE' => 'Riesgo Irrelevante', 'IRRELEVANTE' => 'Riesgo Irrelevante',
        '5' => '5','4' => '4','3' => '3','2' => '2','1' => '1',
    ];
@endphp

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
        <li>
        <div class="flex items-center">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-black">Evaluación de Riesgos</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

<div class="flex justify-between items-center mb-4">
        <!-- Botón Agregar -->
        <div class="inline-flex rounded-md shadow-xs" role="group">
            <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 12h14m-7 7V5" />
                </svg>
                Agregar
            </button>
            <div class="flex items-center gap-4">
    <button type="button"
        onclick="
            document.getElementById('deleteMissingHidden2').value =
                document.getElementById('deleteMissingCheckbox2').checked ? 1 : 0;
            document.getElementById('excelFileInput2').click();
        "
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 hover:bg-gray-900 focus:ring-2 focus:ring-gray-500">
        <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 19V5m0 14-4-4m4 4 4-4"/>
        </svg>
        Importar evaluación
    </button>

    <label class="inline-flex items-center gap-2 cursor-pointer" style="margin-right: 10px;">
        <input type="checkbox" id="deleteMissingCheckbox2"
               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <span class="text-sm text-gray-700">Eliminar lo que no viene en el excel</span>
    </label>
</div>

<form id="importForm2" action="{{ route('evaluacion_riesgos.import') }}" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="hidden" name="delete_missing" id="deleteMissingHidden2" value="0">
    <input type="file" id="excelFileInput2" name="excel_file" accept=".xls,.xlsx,.xlsm"
           onchange="document.getElementById('importForm2').submit();">
</form>

                <a href="/valoracionriesgo" type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-l-lg hover:bg-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z"
                            clip-rule="evenodd" />
                    </svg>
                    Ver Valoración de Riesgos
                </a>
        </div>
        

    <!-- Main modal -->
    <div id="create-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Ingresar Valoración del Riesgo
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{ route('evaluacionriesgos.storeevaluacionriesgos') }}" method="POST" class="p-4 md:p-5">
                    @csrf  
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <label for="id_puesto_trabajo_matriz" class="block mb-2 text-sm font-medium text-gray-900">Puesto de Trabajo</label>
                            <select name="id_puesto_trabajo_matriz" id="id_puesto_trabajo_matriz" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione un Puesto de Trabajo</option>
                                    @foreach($puestos as $puestosmatriz)
                                        <option value="{{ $puestosmatriz->id_puesto_trabajo_matriz }}">{{ $puestosmatriz->puesto_trabajo_matriz }}</option>
                                    @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="id_riesgo" class="block mb-2 text-sm font-medium text-gray-900">Puesto de Trabajo</label>
                            <select name="id_riesgo" id="id_riesgo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione un Puesto de Trabajo</option>
                                    @foreach($riesgos as $riesgo)
                                        <option value="{{ $riesgo->id_riesgo }}">{{ $riesgo->nombre_riesgo }}</option>
                                    @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="id_probabilidad" class="block mb-2 text-sm font-medium text-gray-900">Probabilidad</label>
                            <select name="id_probabilidad" id="id_probabilidad" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione la probabilidad</option>
                                @foreach($probabilidad as $probabilidads)
                                    <option value="{{ $probabilidads->id_probabilidad }}">{{ $probabilidads->probabilidad }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="id_consecuencia" class="block mb-2 text-sm font-medium text-gray-900">Consecuencia</label>
                            <select name="id_consecuencia" id="id_consecuencia" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                                <option value="" disabled selected>Seleccione la consecuencia</option>
                                @foreach($consecuencia as $consecuencias)
                                    <option value="{{ $consecuencias->id_consecuencia }}">{{ $consecuencias->consecuencia }}</option>
                                @endforeach
                            </select>
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

        <form action="{{ route('evaluacionriesgos') }}" method="GET"
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
        <table id="tablaCapacitaciones" class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Riesgo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Probabilidad
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Consecuencia
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nivel de Riesgo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($evaluacionriesgos as $valoracionriesgos)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $valoracionriesgos->puesto_trabajo_matriz }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $valoracionriesgos->nombre_riesgo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $valoracionriesgos->probabilidad }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $valoracionriesgos->consecuencia }}
                    </td>
                    @php
                        $nivelRaw   = (string) ($valoracionriesgos->nivel_riesgo ?? '');
                        $nivelTrim  = trim($nivelRaw);
                        $nivelUpper = mb_strtoupper($nivelTrim, 'UTF-8'); // mejor para acentos

                        // Normaliza a una clave válida de tu mapa
                        $clave = $keys[$nivelUpper] ?? $nivelTrim;

                        $bg  = $mapNivelColor[$clave] ?? '#e5e7eb'; // fallback gris si no matchea
                        $txt = in_array($bg, ['#ff0000', '#be5014'], true) ? '#ffffff' : '#000000';
                    @endphp

                    <td class="px-6 py-4 font-semibold" style="background-color: {{ $bg }}; color: {{ $txt }};">
                        {{ $valoracionriesgos->nivel_riesgo }}
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <button data-modal-target="edit-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" data-modal-toggle="edit-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" class="font-medium text-blue-600 hover:underline">Editar</button>
                        
                        <button data-modal-target="popup-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" data-modal-toggle="popup-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="popup-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}">
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
                                        <form action="{{ route('evaluacionriesgos.destroyevaluacionriesgos', $valoracionriesgos->id_evaluacion_riesgo) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
                                                No, cancelar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div id="edit-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between p-4 border-b rounded-t">
                                        <h3 class="text-lg font-semibold">Editar evaluación</h3>
                                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center" data-modal-hide="edit-modal-{{ $valoracionriesgos->id_evaluacion_riesgo }}">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                            <span class="sr-only">Close modal</span>
                                        </button>
                                    </div>
                                    <form method="POST" action="{{ route('evaluacionriesgos.updateevaluacionriesgos', $valoracionriesgos->id_evaluacion_riesgo) }}" class="p-4 space-y-4" id="form-edit-{{ $valoracionriesgos->id_evaluacion_riesgo }}">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Puesto de trabajo</label>
                                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50" value="{{ $valoracionriesgos->puesto_trabajo_matriz }}" disabled>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Riesgo</label>
                                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50" value="{{ $valoracionriesgos->nombre_riesgo }}" disabled>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Probabilidad</label>
                                            <select name="id_probabilidad" class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" data-prob="{{ $valoracionriesgos->id_evaluacion_riesgo }}">
                                                @foreach($probabilidad as $p)
                                                    <option value="{{ $p->id_probabilidad }}">{{ $p->probabilidad }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Consecuencia</label>
                                            <select name="id_consecuencia" class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" data-cons="{{ $valoracionriesgos->id_evaluacion_riesgo }}">
                                                @foreach($consecuencia as $c)
                                                    <option value="{{ $c->id_consecuencia }}">{{ $c->consecuencia }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Nivel de Riesgo</label>
                                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50" value="" data-nivel="{{ $valoracionriesgos->id_evaluacion_riesgo }}" readonly>
                                        </div>
                                        <div class="pt-2">
                                            <button class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Guardar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $evaluacionriesgos->links() }}
    </div>

<script>
// Mapa de valoraciones: [{id_probabilidad, id_consecuencia, id_nivel_riesgo, nivel_riesgo}]
const VALORACIONES = @json($valoracionTabla ?? []);
function findNivel(probId, consId){
    const v = VALORACIONES.find(x => String(x.id_probabilidad)===String(probId) && String(x.id_consecuencia)===String(consId));
    return v ? v.nivel_riesgo : '';
}
// Preseleccionar valores actuales y mostrar nivel al abrir modal
document.querySelectorAll('[id^="edit-modal-"]').forEach(modal => {
    const id = modal.id.replace('edit-modal-','');
    const selP = modal.querySelector('select[name="id_probabilidad"]');
    const selC = modal.querySelector('select[name="id_consecuencia"]');
    const outN = modal.querySelector('input[data-nivel]');
    // intentar deducir valores actuales desde la fila mostrada
    const row = document.querySelector(`button[data-modal-target="${modal.id}"]`)?.closest('tr');
    if(row){
        const probText = row.children[2]?.innerText.trim();
        const consText = row.children[3]?.innerText.trim();
        if(probText){
            const opt = Array.from(selP.options).find(o=>o.text.trim()===probText);
            if(opt) selP.value = opt.value;
        }
        if(consText){
            const opt = Array.from(selC.options).find(o=>o.text.trim()===consText);
            if(opt) selC.value = opt.value;
        }
        outN.value = findNivel(selP.value, selC.value);
    }
    const upd = () => { outN.value = findNivel(selP.value, selC.value); };
    selP.addEventListener('change', upd);
    selC.addEventListener('change', upd);
});
</script>

@endsection
