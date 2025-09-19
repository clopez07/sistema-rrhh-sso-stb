@extends('layouts.riesgos')

@section('title', 'Señalizaciones')

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
        <li>
        <div class="flex items-center">
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-black">Resumen de Estándares</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

<div class="flex justify-between items-center mb-4">
        <!-- Botón Agregar -->
        <div class="inline-flex rounded-md shadow-xs" role="group">
            <a href="/estandarilu" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Éstandar de Iluminación
            </a>
            <a href="/estandarruido" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Éstandar de Ruido
            </a>
            <a href="/estandartemperatura" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Éstandar de Temperatura
            </a>
        </div>

    <form action="{{ route('estandares') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
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
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estándar de Temperatura
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estándar de Ruido
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Estándar de Iluminación
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($estandares as $estandare)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $estandare->localizacion }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandare->puesto_trabajo_matriz }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandare->rango_temperatura }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandare->nivel_ruido }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $estandare->em }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $estandares->links() }}
    </div>

@endsection