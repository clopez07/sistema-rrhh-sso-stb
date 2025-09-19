@extends('layouts.epp')

@section('title', 'Tipo de Protección por EPP')

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
            <span class="text-sm font-medium text-black">Consultas</span>
        </div>
        </li>
    </ol>
    </nav>
    <br> 

    <form method="GET" action="{{ route('epp.consultas') }}" class="mb-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <input list="nombre" name="nombre"  class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todos los Empleados">
        <datalist id="nombre" name="nombre">

            @foreach($empleadosConEpp as $empleado)
                <option value="{{ $empleado->nombre_completo }}">{{ $empleado->nombre_completo }}</option>
            @endforeach
        </datalist>

        <input list="puesto" name="puesto"  class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todos los Puestos de Trabajo">
        <datalist id="puesto" name="puesto">
            @foreach($puestos as $pst)
                <option value="{{ $pst }}" {{ request('puesto') == $pst ? 'selected' : '' }}>{{ $pst }}</option>
            @endforeach
        </datalist>

        <input list="fecha" name="fecha"  class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todas las Fechas">
        <datalist id="fecha" name="fecha">
            @foreach($fechas as $f)
                <option value="{{ $f }}" {{ request('fecha') == $f ? 'selected' : '' }}>{{ $f }}</option>
            @endforeach
        </datalist>

        <input list="equipo" name="equipo"  class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todos los EPP">
        <datalist id="equipo" name="equipo">
            @foreach($equipos as $eq)
                <option value="{{ $eq }}" {{ request('equipo') == $eq ? 'selected' : '' }}>{{ $eq }}</option>
            @endforeach
        </datalist>

        <div class="sm:col-span-4 flex justify-end gap-2">
            <button type="submit" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm shadow">
                Buscar
            </button>

            <a href="{{ route('epp.consultas.imprimir', request()->all()) }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm shadow">
                Descargar Excel
            </a>
        </div>
    </form>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Nombre Completo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Puesto de Trabajo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Departamento
                    </th>
                    <th scope="col" class="px-6 py-3">
                        EPP
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($equipo as $equipos)
                    <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $equipos->nombre_completo }}</td>
                        <td class="px-6 py-4">{{ $equipos->puesto_trabajo }}</td>
                        <td class="px-6 py-4">{{ $equipos->departamento }}</td>
                        <td class="px-6 py-4">{{ $equipos->epp }}</td>
                        <td class="px-6 py-4">{{ $equipos->fecha_entrega_epp }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection
