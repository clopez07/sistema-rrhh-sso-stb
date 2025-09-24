@extends('layouts.capacitacion')

@section('title', 'Consultas de Capacitaciones')

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
        <li class="inline-flex items-center">
        <a href="/Capacitaciones" class="inline-flex items-center text-sm font-medium text-black hover:text-blue-900">
            <!-- Ícono de inicio -->
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            Registro de Asistencia a Capacitaciones
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

    <form method="GET" action="{{ route('capacitaciones.consultas') }}" class="mb-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <input list="nombre" name="nombre"  class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todos los Empleados">
        <datalist id="nombre" name="nombre">
            <option value="">Todos los Empleados</option>
            @foreach($empleadosConCap as $empleado)
                <option value="{{ $empleado->nombre_completo }}">{{ $empleado->nombre_completo }}</option>
            @endforeach
        </datalist>
    
        <input list="puesto" name="puesto"  class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todos los Puestos">
        <datalist id="puesto" name="puesto">
            @foreach($puesto as $pst)
                <option value="{{ $pst }}" {{ request('puesto') == $pst ? 'selected' : '' }}>{{ $pst }}</option>
            @endforeach
        </datalist>
        
        <input list="capacitacion" name="capacitacion" class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todas las Capacitaciones">
        <datalist id="capacitacion">
            <option value="">Todas las Capacitaciones</option>
            @foreach($capacitacion as $capacitaciones)
                <option value="{{ $capacitaciones }}">{{ $capacitaciones }}</option>
            @endforeach
        </datalist>
    
        <input list="fecha" name="fecha" class="w-full border border-gray-300 rounded-lg p-2 text-sm" placeholder="Todas las Fechas">
        <datalist id="fecha">
            <option value="">Todas las Fechas</option>
            @foreach($fecha as $fechas)
                <option value="{{ $fechas }}">{{ $fechas }}</option>
            @endforeach
        </datalist>
        
        <div class="sm:col-span-4 flex justify-end gap-2">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm shadow">
                Buscar
            </button>
            <a href="{{ route('capacitaciones.imprimir', request()->all()) }}"
            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm shadow">
                Descargar Excel
            </a>
        </div>
    </form>

    <div class="relative overflow-x-auto overflow-y-auto max-h-[70vh] shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left text-gray-500 border-separate border-spacing-0">
            <thead class="sticky top-0 z-20 bg-gray-50">
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
                        Capacitación
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Instructor
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Fecha
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($consulta as $consultas)
                    <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $consultas->nombre_completo }}</td>
                        <td class="px-6 py-4">{{ $consultas->puesto_trabajo }}</td>
                        <td class="px-6 py-4">{{ $consultas->departamento }}</td>
                        <td class="px-6 py-4">{{ $consultas->capacitacion }}</td>
                        <td class="px-6 py-4">{{ $consultas->instructor }}</td>
                        <td class="px-6 py-4">{{ $consultas->fecha_recibida }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $consulta->links() }}
    </div>

@endsection
