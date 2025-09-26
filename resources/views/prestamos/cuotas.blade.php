@extends('layouts.prestamos')

@section('title', 'Control de Prestamos')

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
            <span class="text-sm font-medium text-black">Historial de Cuotas</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

       @if (session('ajustes_msg'))
  <div class="p-3 mb-4 rounded bg-green-50 text-green-800 border border-green-200">
    {{ session('ajustes_msg') }}
  </div>
@endif

@if (session('error'))
  <div class="p-3 mb-4 rounded bg-red-50 text-red-800 border border-red-200">
    {{ session('error') }}
  </div>
@endif

<form action="{{ route('prestamos.ajustes.import') }}" method="post" enctype="multipart/form-data" class="space-y-3">
  @csrf
  <input type="file" name="archivo" accept=".xlsx,.xls" required class="block">
  <button class="px-4 py-2 bg-blue-600 text-white rounded">Importar ajustes</button>
</form>

    <div class="bg-white p-4 rounded-lg shadow-sm border">
        <form action="{{ Request::url() }}" method="GET" class="flex items-center gap-2">
            <div class="relative flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar por nombre, código, ID préstamo o número de cuota..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                    </svg>
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                Buscar
            </button>
            @if(request('search'))
                <a href="{{ Request::url() }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 text-sm font-medium">
                    Limpiar
                </a>
            @endif
        </form>
        
        @if(request('search'))
            <div class="mt-2 text-sm text-blue-600">
                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                Buscando: "{{ request('search') }}" - {{ $cuotas->total() }} resultado(s) encontrado(s)
            </div>
        @endif
    </div>
    <div class="mt-3">
        <a href="{{ route('cuotas.rango') }}" class="inline-flex items-center gap-2 text-sm text-blue-700 hover:text-blue-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h13M8 6h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            </svg>
            Ver cuotas por rango de fechas
        </a>
    </div>

    @if (session('success'))
        <div class="mt-3 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-3 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    @include('prestamos.partials.cuotas-table', ['cuotas' => $cuotas])

@endsection
