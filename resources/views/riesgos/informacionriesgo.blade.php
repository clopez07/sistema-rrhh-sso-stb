@extends('layouts.riesgos')

@section('title', 'Señalizaciones')

@section('content')
    <nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        <li class="inline-flex items-center">
        <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
            </svg>
            Inicio
        </a>
        </li>
        <li>
        <div class="flex items-center">
            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
            </svg>
            <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2">Matriz de Identificación de Riesgos</a>
        </div>
        </li>
        <li>
        <div class="flex items-center">
            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
            </svg>
            <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2">Estándares de Medición</a>
        </div>
        </li>
        <li aria-current="page">
        <div class="flex items-center">
            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
            </svg>
            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2">Iluminación</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>    

<div class="flex justify-between items-center mb-4">
        <!-- Botón Agregar -->
        <div class="inline-flex rounded-md shadow-xs" role="group">
            <a href="/riesgopuesto" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Riesgos por Puesto
            </a>
            <a href="/detallesriesgo" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver detalles de Riesgos por Puesto
            </a>
            <a href="/riesgo" type="button"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 border border-gray-900 rounded-s-lg hover:bg-blue-800 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                Ver Riesgos y Tipos de Riesgos
            </a>
        </div>

    <form action="{{ route('informacionriesgo') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
        <div class="relative w-full">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Buscar..."
                oninput="this.form.submit()"
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
                        Tipo de Riesgo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nombre de Riesgo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Detalles de Riesgo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Acción
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($informacionriesgo as $informacionriesgos)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $informacionriesgos->tipo_riesgo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $informacionriesgos->nombre_riesgo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $informacionriesgos->detalles_riesgo }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $informacionriesgo->links() }}
    </div>

@endsection