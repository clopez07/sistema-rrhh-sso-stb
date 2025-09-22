@extends('layouts.mediciones')

@section('title', 'Señalizaciones')

@section('content')
        <nav class="flex px-5 py-3 text-gray-700 bg-blue-100 rounded-lg" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
        <a href="/mediciones/captura" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
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
            <span class="text-sm font-medium text-black">Estándar de Iluminación</span>
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
            <a href="/estandarruido" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Éstandar de Ruido
            </a>
            <a href="/estandartemperatura" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Éstandar de Temperatura
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
                        Ingresar Nombre de la Señalizacion
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{ route('estandarilu.storeestandarilu') }}" method="POST" class="p-4 md:p-5">
                    @csrf  
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <label for="id_localizacion" class="block mb-2 text-sm font-medium text-gray-900">Localización</label>
                            <select name="id_localizacion" id="id_localizacion" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                                <option value="" disabled selected>Seleccione un Puesto de Trabajo</option>
                                @foreach($localizacion as $localizacione)
                                    <option value="{{ $localizacione->id_localizacion }}">{{ $localizacione->localizacion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label for="em" class="block mb-2 text-sm font-medium text-gray-900">em</label>
                            <input type="text" name="em" id="em" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label for="ugr" class="block mb-2 text-sm font-medium text-gray-900">ugr</label>
                            <input type="text" name="ugr" id="ugr" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label for="ra" class="block mb-2 text-sm font-medium text-gray-900">ra</label>
                            <input type="text" name="ra" id="ra" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-2">
                            <label for="observaciones" class="block mb-2 text-sm font-medium text-gray-900">Observaciones</label>
                            <input type="text" name="observaciones" id="observaciones" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
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
    <form action="{{ route('estandarilu') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
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
        <table id="tablaCapacitaciones" class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Localización
                    </th>
                    <th scope="col" class="px-6 py-3">
                        em
                    </th>
                    <th scope="col" class="px-6 py-3">
                        ugr
                    </th>
                    <th scope="col" class="px-6 py-3">
                        ra
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Observaciones
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($estandarilu as $estandarilum)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $estandarilum->localizacion }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandarilum->em }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandarilum->ugr }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandarilum->ra }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandarilum->observaciones }}
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <button data-modal-target="edit-modal-{{ $estandarilum->id_estandar_iluminacion }}" data-modal-toggle="edit-modal-{{ $estandarilum->id_estandar_iluminacion }}" class="font-medium text-blue-600 hover:underline">Editar</button>

                        <!-- Edit modal -->
                        <div id="edit-modal-{{ $estandarilum->id_estandar_iluminacion }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900">Editar Estándar de Iluminación</h3>
                                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="edit-modal-{{ $estandarilum->id_estandar_iluminacion }}">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                            <span class="sr-only">Close modal</span>
                                        </button>
                                    </div>
                                    <form action="{{ route('estandarilu.updateestandarilu', $estandarilum->id_estandar_iluminacion) }}" method="POST" class="p-4 md:p-5">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid gap-4 mb-4 grid-cols-2">
                                            <div class="col-span-2">
                                                <label for="id_localizacion_{{ $estandarilum->id_estandar_iluminacion }}" class="block mb-2 text-sm font-medium text-gray-900">Localización</label>
                                                <select name="id_localizacion" id="id_localizacion_{{ $estandarilum->id_estandar_iluminacion }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                                                    @foreach($localizacion as $localizacione)
                                                        <option value="{{ $localizacione->id_localizacion }}" {{ $localizacione->id_localizacion == $estandarilum->id_localizacion ? 'selected' : '' }}>
                                                            {{ $localizacione->localizacion }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <label for="em_{{ $estandarilum->id_estandar_iluminacion }}" class="block mb-2 text-sm font-medium text-gray-900">em</label>
                                                <input type="text" name="em" id="em_{{ $estandarilum->id_estandar_iluminacion }}" value="{{ $estandarilum->em }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                                            </div>
                                            <div class="col-span-2">
                                                <label for="ugr_{{ $estandarilum->id_estandar_iluminacion }}" class="block mb-2 text-sm font-medium text-gray-900">ugr</label>
                                                <input type="text" name="ugr" id="ugr_{{ $estandarilum->id_estandar_iluminacion }}" value="{{ $estandarilum->ugr }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                                            </div>
                                            <div class="col-span-2">
                                                <label for="ra_{{ $estandarilum->id_estandar_iluminacion }}" class="block mb-2 text-sm font-medium text-gray-900">ra</label>
                                                <input type="text" name="ra" id="ra_{{ $estandarilum->id_estandar_iluminacion }}" value="{{ $estandarilum->ra }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                                            </div>
                                            <div class="col-span-2">
                                                <label for="observaciones_{{ $estandarilum->id_estandar_iluminacion }}" class="block mb-2 text-sm font-medium text-gray-900">Observaciones</label>
                                                <input type="text" name="observaciones" id="observaciones_{{ $estandarilum->id_estandar_iluminacion }}" value="{{ $estandarilum->observaciones }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
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

                        <button data-modal-target="popup-modal-{{ $estandarilum->id_estandar_iluminacion }}" data-modal-toggle="popup-modal-{{ $estandarilum->id_estandar_iluminacion }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $estandarilum->id_estandar_iluminacion }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal-{{ $estandarilum->id_estandar_iluminacion }}">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                        </svg>
                                        <span class="sr-only">Close modal</span>
                                    </button>
                                    <div class="p-4 md:p-5 text-center">
                                        <svg class="mx-auto mb-4 text-gray-400 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                        <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">¿Está seguro de eliminar este item?</h3>
                                        <form action="{{ route('estandarilu.destroyestandarilu', $estandarilum->id_estandar_iluminacion) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal-{{ $estandarilum->id_estandar_iluminacion }}" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
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
        {{ $estandarilu->links() }}
    </div>

@endsection
