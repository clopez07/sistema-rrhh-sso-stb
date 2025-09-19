@extends('layouts.capacitacion')

@section('title', 'Administrar Asistencia a Capacitaciones')

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
            <span class="text-sm font-medium text-black">Registro de Asistencia a Capacitaciones</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

<div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm">
    <ul class="hidden text-sm font-medium text-center text-gray-500 divide-x divide-gray-200 rounded-lg sm:flex rtl:divide-x-reverse" id="fullWidthTab" data-tabs-toggle="#fullWidthTabContent" role="tablist">
        <li class="w-full">
            <div id="stats-tab" data-tabs-target="#stats" role="tab" aria-controls="stats" aria-selected="true" class="inline-block w-full p-4 rounded-ss-lg bg-gray-50 text-xl font-bold leading-none text-gray-900">Datos Importantes</div>
        </li>
    </ul>
    <div id="fullWidthTabContent" class="border-t border-gray-200">
        <div class="hidden p-4 bg-white rounded-lg md:p-8" id="stats" role="tabpanel" aria-labelledby="stats-tab">
            <dl class="grid max-w-screen-xl grid-cols-2 gap-8 p-4 mx-auto text-gray-900 sm:grid-cols-3 xl:grid-cols-5 sm:p-8">
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold">{{ $totalCapacitaciones }}</dt>
                    <dd class="text-gray-500">Capacitaciones Registradas</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold">{{ $totalEmpleados }}</dt>
                    <dd class="text-gray-500">Personal Activo</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold">{{ $totalInstructores }}</dt>
                    <dd class="text-gray-500">Instructores Registrados</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold">{{ $totalAsistencias }}</dt>
                    <dd class="text-gray-500">Asistencias Registradas</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold"> {{ $totalHorasCapacitaciones }} </dt>
                    <dd class="text-gray-500">Horas Hombre</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<br>

<div class="flex flex-col md:flex-row gap-6">
    <!-- Tarjeta 1: Mayores Asistentes -->
    <div class="w-full md:w-1/2 p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h5 class="text-xl font-bold leading-none text-gray-900">Mayores Asistentes a Capacitaciones</h5>
        </div>
        <div class="flow-root">
            <ul role="list" class="divide-y divide-gray-200">
                <!-- Lista de asistentes -->
                @foreach($mayoresAsistentes as $asistente)
                <li class="py-3 sm:py-4">
                    <div class="flex items-center">
                        <div class="flex-1 min-w-0 ms-4">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $asistente->nombre_completo }}</p>
                            <p class="text-sm text-gray-500 truncate">{{ $asistente->puesto_trabajo }}</p>
                        </div>
                        <div class="inline-flex items-center text-base font-semibold text-gray-900">
                            {{ $asistente->total_capacitaciones }} Capacitaciones
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Tarjeta 2: Últimas Capacitaciones -->
    <div class="w-full md:w-1/2 p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
        <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900">Últimas Capacitaciones Ingresadas</h5>
        <p class="mb-3 font-normal text-gray-700">
            Aquí puedes revisar las capacitaciones más recientes registradas en el sistema.
        </p>
        <ul class="mt-4 list-disc list-inside text-gray-700">
            @foreach($ultimasCapacitaciones as $cap)
                <li>{{ $cap->capacitacion }} {{ $cap->fecha_recibida }}</li>
            @endforeach
        </ul>
        <br>
        <a href="/capacitacion" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
            Ver todas
            <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
            </svg>
        </a>
    </div>
</div>

@endsection