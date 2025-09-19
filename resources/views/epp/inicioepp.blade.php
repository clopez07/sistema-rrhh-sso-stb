@extends('layouts.epp')

@section('title', 'Administrar Entrega de EPP')

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
            <span class="text-sm font-medium text-black">Registro de Entrega de EPP</span>
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
                    <dt class="mb-2 text-3xl font-extrabold"> {{ $empleadosConEPP }} </dt>
                    <dd class="text-gray-500">Empleados con EPP</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold"> {{ $eppMesActual }} </dt>
                    <dd class="text-gray-500">EPP Entregados mes actual</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold"> {{ $totalEPP }} </dt>
                    <dd class="text-gray-500">EPP Registrados</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold"> {{ $totalAsignaciones }} </dt>
                    <dd class="text-gray-500">Entregas Registradas</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <dt class="mb-2 text-3xl font-extrabold"> {{ $totalEntregados }} </dt>
                    <dd class="text-gray-500">EPP entregados</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<br>

<!-- Buscador por EPP, Año y Persona -->
<div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ url('/epp') }}" class="flex flex-col md:flex-row gap-3 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-600">EPP</label>
            <select name="epp_id" class="mt-1 w-64 rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600">
                <option value="">Todos</option>
                @foreach(($listaEpp ?? collect()) as $opt)
                    <option value="{{ $opt->id_epp }}" @selected(isset($eppId) && (int)$eppId === (int)$opt->id_epp)>
                        {{ $opt->equipo }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600">Año</label>
            <select name="anio" class="mt-1 w-40 rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600">
                <option value="">Todos</option>
                @foreach(($anios ?? collect()) as $y)
                    <option value="{{ $y }}" @selected(isset($anio) && (string)$anio === (string)$y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600">Persona</label>
            <input type="text" name="persona" value="{{ $persona ?? '' }}" placeholder="Nombre de persona"
                   class="mt-1 w-64 rounded-lg border-gray-300 focus:border-blue-600 focus:ring-blue-600" />
        </div>
        <div>
            <button class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white px-4 py-2 shadow hover:bg-blue-700">
                Buscar
            </button>
        </div>
        @if(request()->has('epp_id') || request()->has('anio'))
        <a class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white px-4 py-2 shadow hover:bg-blue-700" type="Button" href="{{ url('/epp') }}" class="text-sm text-gray-600 hover:underline">Limpiar</a>
        @endif
    </form>
</div>

@if(empty($eppId))
<div class="flow-root">
    <h5 class="text-xl font-bold leading-none text-gray-900">Cantidades de EPP entregados</h5>
    <br>
    <ul role="list" class="grid grid-cols-1 md:grid-cols-2 divide-y divide-gray-200">
        @foreach($cantidadesEPP as $epp)
        <li class="py-3 sm:py-4">
            <div class="flex items-center">
                <div class="flex-1 min-w-0 ms-4">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $epp->equipo }}</p>
                    <p class="text-sm text-gray-500 truncate">Cantidad Entregada</p>
                </div>
                <div class="inline-flex items-center text-base font-semibold text-gray-900">
                    {{ $epp->cantidad_entregada }}
                </div>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@endif

@if(($anio ?? null) && empty($eppId))
<div class="flow-root mt-8">
    <h5 class="text-xl font-bold leading-none text-gray-900">EPP entregados en {{ $anio }}</h5>
    <br>
    @if(($cantidadesPorAnio ?? collect())->isEmpty())
        <p class="text-gray-500">No hay entregas registradas para {{ $anio }}.</p>
    @else
    <ul role="list" class="grid grid-cols-1 md:grid-cols-2 divide-y divide-gray-200">
        @foreach($cantidadesPorAnio as $row)
        <li class="py-3 sm:py-4">
            <div class="flex items-center">
                <div class="flex-1 min-w-0 ms-4">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $row->equipo }}</p>
                    <p class="text-sm text-gray-500 truncate">Cantidad Entregada {{ $anio }}</p>
                </div>
                <div class="inline-flex items-center text-base font-semibold text-gray-900">
                    {{ $row->cantidad_entregada }}
                </div>
            </div>
        </li>
        @endforeach
    </ul>
    @endif
</div>
@endif

@if(!empty($eppId))
<div class="flow-root mt-8">
    <h5 class="text-xl font-bold leading-none text-gray-900">
        Personas con entrega de {{ $eppNombre ?? 'EPP' }} @if(!empty($anio))en {{ $anio }}@endif
    </h5>
    <br>
    @if(($entregasPorEpp ?? collect())->isEmpty())
        <p class="text-gray-500">No se encontraron entregas para los filtros seleccionados.</p>
    @else
    <ul role="list" class="divide-y divide-gray-200">
        @foreach($entregasPorEpp as $r)
        <li class="py-3 sm:py-4">
            <div class="flex items-center">
                <div class="flex-1 min-w-0 ms-4">
                    <p class="text-sm font-medium text-gray-900">{{ $r->nombre_completo }}</p>
                    <p class="text-xs text-gray-500">Fecha de entrega: {{ $r->fecha_entrega_epp }}</p>
                </div>
                <span class="text-sm text-gray-700">{{ $r->epp }}</span>
            </div>
        </li>
        @endforeach
    </ul>
    @endif
</div>
@endif


@endsection
