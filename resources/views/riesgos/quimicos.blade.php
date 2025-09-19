@extends('layouts.riesgos')

@section('title', 'Inventario de Quimicos')

@section('content')
@php
    // DÃ©jalos ARRIBA, fuera del foreach
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
            <!-- Ãcono de inicio -->
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
            <span class="text-sm font-medium text-black">Inventario de Quí­micos</span>
        </div>
        </li>
    </ol>
    </nav>
    <br>

    <div class="flex justify-between items-center mb-4">
        <!-- Grupo: Agregar + Importar -->
        <div class="flex items-center gap-2">
            <!-- BotÃ³n Agregar -->
            <div class="inline-flex rounded-md shadow-xs" role="group">
                <button data-modal-target="create-modal" data-modal-toggle="create-modal" type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-transparent border border-gray-900 rounded-s-lg hover:bg-gray-900 focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900">
                    <svg class="w-6 h-6 text-gray-800 mr-1" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14m-7 7V5" />
                    </svg>
                    Agregar
                </button>
            </div>

            <!-- Main modal -->
            <div id="create-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-4xl max-h-full">
                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg shadow-sm">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Ingresar Quí­mico</h3>
                            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="create-modal">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Close modal</span>
                            </button>
                        </div>

                            <!-- Modal body -->
                            <form action="{{ route('quimicos.storequimicos') }}" method="POST" class="p-4 md:p-5">
                                @csrf  
                                <div class="grid gap-4 mb-4 grid-cols-1 md:grid-cols-2">
                                    <!-- Text Inputs -->
                                    <div>
                                        <label for="nombre_comercial" class="block mb-2 text-sm font-medium text-gray-900">Nombre Comercial</label>
                                        <input type="text" name="nombre_comercial" id="nombre_comercial" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="uso" class="block mb-2 text-sm font-medium text-gray-900">Uso</label>
                                        <input type="text" name="uso" id="uso" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="proveedor" class="block mb-2 text-sm font-medium text-gray-900">Proveedor</label>
                                        <input type="text" name="proveedor" id="proveedor" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="concentracion" class="block mb-2 text-sm font-medium text-gray-900">Concentración</label>
                                        <input type="text" name="concentracion" id="concentracion" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="composicion_quimica" class="block mb-2 text-sm font-medium text-gray-900">Composición Química</label>
                                        <input type="text" name="composicion_quimica" id="composicion_quimica" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="estado_fisico" class="block mb-2 text-sm font-medium text-gray-900">Estado Físico</label>
                                        <input type="text" name="estado_fisico" id="estado_fisico" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="msds" class="block mb-2 text-sm font-medium text-gray-900">MSDS</label>
                                        <input type="text" name="msds" id="msds" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div class="col-span-2">
                                    <p class="mb-2 font-medium text-gray-900">Grado de Peligrosidad de los Quí­micos</p>
                                    <div>
                                        <label for="salud" class="block mb-2 text-sm font-medium text-gray-900">Salud</label>
                                        <input type="text" name="salud" id="salud" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="inflamabilidad" class="block mb-2 text-sm font-medium text-gray-900">Inflamabilidad</label>
                                        <input type="text" name="inflamabilidad" id="inflamabilidad" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                    <div>
                                        <label for="reactividad" class="block mb-2 text-sm font-medium text-gray-900">Reactividad</label>
                                        <input type="text" name="reactividad" id="reactividad" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full" required>
                                    </div>
                                </div>

                                <!-- Tipo de ExposiciÃ³n, ValoraciÃ³n, Medidas/DescripciÃ³n -->
                                @php
                                  $__tipoExpos = (isset($tipoexposicion) && count($tipoexposicion)) ? $tipoexposicion : \DB::table('tipo_exposicion')->orderBy('tipo_exposicion')->get();
                                  $__probs = (isset($probabilidades) && count($probabilidades)) ? $probabilidades : \DB::table('probabilidad')->orderBy('id_probabilidad')->get();
                                  $__cons = (isset($consecuencias) && count($consecuencias)) ? $consecuencias : \DB::table('consecuencia')->orderBy('id_consecuencia')->get();
                                  $__valRows = \DB::table('valoracion_riesgo as v')->join('nivel_riesgo as n','n.id_nivel_riesgo','=','v.id_nivel_riesgo')->get(['v.id_probabilidad','v.id_consecuencia','v.id_nivel_riesgo','n.nivel_riesgo']);
                                  $__valMap = [];
                                  foreach($__valRows as $vr){ $__valMap[$vr->id_probabilidad.'-'.$vr->id_consecuencia] = ['id'=>$vr->id_nivel_riesgo,'label'=>$vr->nivel_riesgo]; }
                                @endphp
                                <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Tipos de exposición</label>
                                    <select name="tipo_exposicion[]" multiple size="6" class="w-full border border-gray-300 rounded-lg p-2.5">
                                      @foreach($__tipoExpos as $te)
                                        <option value="{{ $te->id_tipo_exposicion }}">{{ $te->tipo_exposicion }}</option>
                                      @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Mantén Ctrl para seleccionar varias.</p>
                                  </div>
                                  <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Valoración de riesgo</label>
                                    <div class="grid grid-cols-1 gap-2">
                                      <select name="id_probabilidad" id="vr_prob_create" class="border border-gray-300 rounded-lg p-2.5">
                                        <option value="">Probabilidad</option>
                                        @foreach($__probs as $p)
                                          <option value="{{ $p->id_probabilidad }}">{{ $p->probabilidad ?? $p->id_probabilidad }}</option>
                                        @endforeach
                                      </select>
                                      <select name="id_consecuencia" id="vr_cons_create" class="border border-gray-300 rounded-lg p-2.5">
                                        <option value="">Consecuencia</option>
                                        @foreach($__cons as $c)
                                          <option value="{{ $c->id_consecuencia }}">{{ $c->consecuencia ?? $c->id_consecuencia }}</option>
                                        @endforeach
                                      </select>
                                      <input type="hidden" name="id_nivel_riesgo" id="vr_nivel_id_create">
                                      <input type="text" id="vr_nivel_label_create" class="bg-gray-100 border border-gray-300 rounded-lg p-2.5 w-full" placeholder="Nivel resultante" readonly>
                                    </div>
                                  </div>
                                </div>
                                <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <div>
                                    <label for="medidas_pre_correc" class="block mb-2 text-sm font-medium text-gray-900">Medidas de prevención y corrección</label>
                                    <textarea name="medidas_pre_correc" id="medidas_pre_correc" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
                                  </div>
                                  <div>
                                    <label for="descripcion" class="block mb-2 text-sm font-medium text-gray-900">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
                                  </div>
                                </div>

                                <!-- Checkboxes -->
                                <div class="col-span-2">
                                    <p class="mb-2 font-medium text-gray-900">Riesgo Químico (marque las que apliquen)</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="ninguno" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Ninguno
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="particulas_polvo" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Partí­culas de polvo, humos, gases y vapores
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="sustancias_corrosivas" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Sustancias Corrosivas
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="sustancias_toxicas" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Sustancias Tóxicas
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="sustancias_irritantes" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Sustancias irritantes o alergizantes
                                        </label>
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <p class="mb-2 font-medium text-gray-900">Peligros Especí­ficos (marque las que apliquen)</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="nocivo" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                                Nocivo o Irritante
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="corrosivo" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Corrosivo
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="inflamable" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Inflamable
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="peligro_salud" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Peligro grave a la Salud
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="oxidante" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                                Oxidante o Comburente
                                            </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="peligro_medio_ambiente" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                                Peligro para el medio Ambiente
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="toxico" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                                Tóxico
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="gas_presion" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                                Gas a Presión
                                            </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="explosivo" value="Si" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            Explosivo
                                        </label>
                                    </div>
                                </div>                         

                                <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                    <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BotÃ³n Importar -->
            <form id="importForm" action="{{ route('quimicos.import') }}" method="POST" enctype="multipart/form-data" style="display: none;">
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
            <a href="{{ route('quimicos.export', ['search' => request('search')]) }}" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-700 hover:bg-green-700 focus:z-10 focus:ring-2 focus:ring-green-500">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 3a1 1 0 011-1h4a1 1 0 010 2H6.414l1.293 1.293A1 1 0 016 6.414L4.707 5.121V7a1 1 0 11-2 0V4a1 1 0 011-1zM17 17a1 1 0 01-1 1h-4a1 1 0 110-2h1.586l-1.293-1.293a1 1 0 111.414-1.414L15 14.586V13a1 1 0 112 0v3z"/>
                </svg>
                Exportar Excel
            </a>
                        <!-- Botones de navegaciÃ³n -->
            <div class="inline-flex rounded-md shadow-xs" role="group">
                <a href="/tipoexposicion" type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-l-lg hover:bg-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z"
                            clip-rule="evenodd" />
                    </svg>
                    Tipos de exposición
                </a>
            </div>
            <!-- Botones de navegaciÃ³n -->
            <div class="inline-flex rounded-md shadow-xs" role="group">
                <a href="/quimicospuestos" type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-l-lg hover:bg-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z"
                            clip-rule="evenodd" />
                    </svg>
                    Ver Quimicos por Puesto de Trabajo
                </a>
            </div>
        </div>

        <form action="{{ route('quimicos') }}" method="GET" class="relative w-full max-w-sm bg-white flex items-center">
            <div class="relative w-full">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar..."
                    oninput="this.form.submit()" {{-- aquÃ­ estÃ¡ la magia --}}
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

    <div class="flex items-center justify-center gap-4 mb-4 print:mb-2">
        <img src="{{ asset('img/logo.PNG') }}" alt="Service and Trading Business"
            class="h-16 w-auto object-contain print:h-14" />
        <div class="text-center leading-tight">
            <h1 class="text-xl font-bold">SERVICE AND TRADING BUSINESS S.A. DE C.V.</h1>
            <p class="text-sm">PROCESO SALUD Y SEGURIDAD OCUPACIONAL/HEALTH AND SAFETY PROCESS </p>
            <p class="text-sm">INVENTARIO DE QUIMICOS/ INVENTORY OF CHEMICALS</p>
        </div>
    </div>
    <p class="text-sm font-bold" style="text-align: left;">INVENTARIO GENERAL</p>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">NOMBRE COMERCIAL</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">USO</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">PROVEEDOR</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">CONCENTRACION</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">COMPOSICIÓN QUÍMICA</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">ESTADO FÍSICO</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">MSDS</th>
                    <th colspan="3" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold; text-align: center;">GRADO DE PELIGROSIDAD</th>
                    <th colspan="9" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold; text-align: center;">RIESGOS ESPECÍFICOS</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">Tipo de Exposición</th>
                    <th colspan="5" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold; text-align: center;">RIESGO QUÍMICO</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">Nivel de Riesgo Químico</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">Medidas de Prevención y Correción</th>
                    <th rowspan="2" class="px-6 py-3" style="background:#D6DCE4; font-weight:bold;">Acción</th>
                </tr>
                <tr>
                    <th scope="col" class="px-6 py-3" style="background:#0070C0; font-weight:bold;">
                        SALUD
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#FF0000; font-weight:bold;">
                        INFLAMABILIDAD
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#FFFF00; font-weight:bold;">
                        REACTIVIDAD
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        NOCIVO O IRRITANTE
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/nocivo.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        CORROSIVO
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/corrosivo.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        INFLAMABLE
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/inflamable.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        PELIGRO GRAVE A LA SALUD
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/peligrosalud.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        OXIDANTE O CURBURENTE
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/oxidante.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        PELIGRO PARA EL MEDIO AMBIENTE
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/medioambiente.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        TOXICO
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/toxico.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        GAS A PRESIÓN
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/gas.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="bg-[#00B0F0] p-0 align-bottom">
                    <div class="flex flex-col h-full">
                        <div class="bg-[#00B0F0] font-bold text-center uppercase text-[12px] leading-tight px-2 py-1">
                        EXPLOSIVO
                        </div>
                        <div class="flex items-center justify-center bg-white py-2">
                        <img src="{{ asset('img/explosivo.png') }}" alt="Explosivo" class="w-12 h-12 object-contain">
                        </div>
                    </div>
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#F4B084; font-weight:bold;">
                        Ninguno
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#F4B084; font-weight:bold;">
                        Partíulas de polvo, humos, gases y vapores
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#F4B084; font-weight:bold;">
                        Sustancias corrosivas
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#F4B084; font-weight:bold;">
                        Sustancias Tóxicas
                    </th>
                    <th scope="col" class="px-6 py-3" style="background:#F4B084; font-weight:bold;">
                        Sustancias irritantes o alergizantes
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quimicos as $quimico)
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-6 py-4">
                        {{ $quimico->nombre_comercial }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->uso }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->proveedor }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->concentracion }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->composicion_quimica }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->estado_fisico }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->msds }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->salud }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->inflamabilidad }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->reactividad }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->nocivo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->corrosivo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->inflamable }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->peligro_salud }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->oxidante }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->peligro_medio_ambiente }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->toxico }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->gas_presion }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->explosivo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->tipos_exposicion }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->ninguno }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->particulas_polvo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->sustancias_corrosivas }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->sustancias_toxicas }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->sustancias_irritantes }}
                    </td>
                     @php


                        $nivelRaw   = (string) ($quimico->nivel_riesgo ?? $quimico->id_nivel_riesgo ?? '');

                        $nivelTrim  = trim($nivelRaw);
                        $nivelUpper = mb_strtoupper($nivelTrim, 'UTF-8');
                        $clave = $keys[$nivelUpper] ?? $nivelTrim;

                        $bg  = $mapNivelColor[$clave] ?? '#e5e7eb';
                        $txt = in_array($bg, ['#ff0000', '#be5014'], true) ? '#ffffff' : '#000000';
                    @endphp
                    <td class="px-6 py-4 font-semibold" style="background-color: {{ $bg }}; color: {{ $txt }};">
                        {{ $quimico->nivel_riesgo }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $quimico->medidas_pre_correc }}
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <button data-modal-target="edit-modal-{{ $quimico->id_quimico }}" data-modal-toggle="edit-modal-{{ $quimico->id_quimico }}" href="#" class="font-medium text-blue-600 hover:underline">Editar</button>

                        @php
                        // ids de tipos preseleccionados
                        $selTipos = array_filter(explode(',', $teIdsByQuimico[$quimico->id_quimico] ?? ''), 'strlen');
                        // pareja canÃ³nica (prob, cons) para el nivel actual del quÃ­mico
                        $pair = $pairByQuimico[$quimico->id_quimico] ?? ['prob'=>null,'cons'=>null];
                        @endphp

                        <div id="edit-modal-{{ $quimico->id_quimico }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                        <div class="relative p-4 w-full max-w-4xl max-h-full">
                            <div class="relative bg-white rounded-lg shadow-sm">
                            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Ingresar / Editar Químico</h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="edit-modal-{{ $quimico->id_quimico }}">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                                    <span class="sr-only">Close modal</span>
                                </button>
                            </div>

                            <form action="{{ route('quimicos.updatequimicos', $quimico->id_quimico) }}" method="POST" class="p-4 md:p-5">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-4 mb-4 grid-cols-1 md:grid-cols-2">
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Nombre Comercial</label>
                                    <input type="text" name="nombre_comercial" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('nombre_comercial', $quimico->nombre_comercial) }}" required>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Uso</label>
                                    <input type="text" name="uso" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('uso', $quimico->uso) }}" required>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Proveedor</label>
                                    <input type="text" name="proveedor" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('proveedor', $quimico->proveedor) }}" required>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Concentración</label>
                                    <input type="text" name="concentracion" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('concentracion', $quimico->concentracion) }}" required>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Composición Quí­mica</label>
                                    <input type="text" name="composicion_quimica" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('composicion_quimica', $quimico->composicion_quimica) }}" required>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Estado Físico</label>
                                    <input type="text" name="estado_fisico" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('estado_fisico', $quimico->estado_fisico) }}" required>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">MSDS</label>
                                    <input type="text" name="msds" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('msds', $quimico->msds) }}" required>
                                    </div>

                                    <div class="col-span-2">
                                    <p class="mb-2 font-medium text-gray-900">Grado de Peligrosidad de los Quí­micos</p>
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-900">Salud</label>
                                        <input type="number" min="0" max="4" step="1" name="salud" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('salud', $quimico->salud) }}" required>
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-900">Inflamabilidad</label>
                                        <input type="number" min="0" max="4" step="1" name="inflamabilidad" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('inflamabilidad', $quimico->inflamabilidad) }}" required>
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-900">Reactividad</label>
                                        <input type="number" min="0" max="4" step="1" name="reactividad" class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full"
                                            value="{{ old('reactividad', $quimico->reactividad) }}" required>
                                    </div>
                                    </div>
                                </div>

                                <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Tipos de exposición</label>
                                    <select name="tipo_exposicion[]" multiple size="6" class="w-full border border-gray-300 rounded-lg p-2.5">
                                        @foreach($tipoexposicion as $te)
                                        <option value="{{ $te->id_tipo_exposicion }}"
                                            {{ in_array((string)$te->id_tipo_exposicion, $selTipos, true) ? 'selected' : '' }}>
                                            {{ $te->tipo_exposicion }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Mantén Ctrl para seleccionar varias.</p>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Valoración de riesgo</label>
                                    <div class="grid grid-cols-1 gap-2">
                                    <select name="id_probabilidad" class="border rounded-lg p-2.5">
                                        <option value="">Probabilidad</option>
                                        @foreach($probabilidades as $p)
                                        <option value="{{ $p->id_probabilidad }}"
                                            {{ (old('id_probabilidad', $pair['prob']) == $p->id_probabilidad) ? 'selected' : '' }}>
                                            {{ $p->probabilidad ?? $p->id_probabilidad }}
                                        </option>
                                        @endforeach
                                    </select>

                                    <select name="id_consecuencia" class="border rounded-lg p-2.5">
                                        <option value="">Consecuencia</option>
                                        @foreach($consecuencias as $c)
                                        <option value="{{ $c->id_consecuencia }}"
                                            {{ (old('id_consecuencia', $pair['cons']) == $c->id_consecuencia) ? 'selected' : '' }}>
                                            {{ $c->consecuencia ?? $c->id_consecuencia }}
                                        </option>
                                        @endforeach
                                    </select>

                                    <input type="text"
                                            id="vr_nivel_label_edit_{{ $quimico->id_quimico }}"
                                            class="bg-gray-100 border rounded-lg p-2.5 w-full"
                                            value="{{ $quimico->nivel_riesgo }}"
                                            placeholder="Nivel resultante" readonly>
                                    </div>
                                    </div>
                                </div>

                                {{-- Textareas --}}
                                <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Medidas de prevención y corrección</label>
                                    <textarea name="medidas_pre_correc" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5">{{ old('medidas_pre_correc', $quimico->medidas_pre_correc) }}</textarea>
                                    </div>
                                    <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Descripción</label>
                                    <textarea name="descripcion" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5">{{ old('descripcion', $quimico->descripcion) }}</textarea>
                                    </div>
                                </div>

                                {{-- Checkboxes (marcados si 'Si') --}}
                                <div class="col-span-2">
                                    <p class="mb-2 font-medium text-gray-900">Riesgo Quí­mico</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    @php
                                        $ck = function($v) use ($quimico) {
                                            $val = old($v, $quimico->$v ?? 0);
                                            return ($val === 'Si' || $val === 1 || $val === '1' || $val === true) ? 'checked' : '';
                                        };
                                    @endphp
                                    <label class="flex items-center gap-2"><input type="checkbox" name="ninguno"               value="Si" class="w-4 h-4" {{ $ck('ninguno') }}>Ninguno</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="particulas_polvo"     value="Si" class="w-4 h-4" {{ $ck('particulas_polvo') }}>PartÃ­culas de polvo...</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="sustancias_corrosivas" value="Si" class="w-4 h-4" {{ $ck('sustancias_corrosivas') }}>Sustancias Corrosivas</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="sustancias_toxicas"    value="Si" class="w-4 h-4" {{ $ck('sustancias_toxicas') }}>Sustancias TÃ³xicas</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="sustancias_irritantes" value="Si" class="w-4 h-4" {{ $ck('sustancias_irritantes') }}>Sustancias irritantes...</label>
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <p class="mb-2 font-medium text-gray-900">Peligros Especí­ficos</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <label class="flex items-center gap-2"><input type="checkbox" name="nocivo"                 value="Si" class="w-4 h-4" {{ $ck('nocivo') }}>Nocivo o Irritante</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="corrosivo"              value="Si" class="w-4 h-4" {{ $ck('corrosivo') }}>Corrosivo</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="inflamable"             value="Si" class="w-4 h-4" {{ $ck('inflamable') }}>Inflamable</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="peligro_salud"          value="Si" class="w-4 h-4" {{ $ck('peligro_salud') }}>Peligro grave a la Salud</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="oxidante"               value="Si" class="w-4 h-4" {{ $ck('oxidante') }}>Oxidante o Comburente</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="peligro_medio_ambiente" value="Si" class="w-4 h-4" {{ $ck('peligro_medio_ambiente') }}>Medio Ambiente</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="toxico"                 value="Si" class="w-4 h-4" {{ $ck('toxico') }}>TÃ³xico</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="gas_presion"            value="Si" class="w-4 h-4" {{ $ck('gas_presion') }}>Gas a PresiÃ³n</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="explosivo"              value="Si" class="w-4 h-4" {{ $ck('explosivo') }}>Explosivo</label>
                                    </div>
                                </div>

                                <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                    Guardar
                                </button>
                                </form>
                                    
                                </div>
                            </div>
                        </div>
                        </div>

                        <button data-modal-target="popup-modal-{{ $quimico->id_quimico }}" data-modal-toggle="popup-modal-{{ $quimico->id_quimico }}" class="font-medium text-red-600 hover:underline ms-3">Eliminar</button>

                        <div id="popup-modal-{{ $quimico->id_quimico }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal-{{ $quimico->id_quimico }}">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                        </svg>
                                        <span class="sr-only">Close modal</span>
                                    </button>
                                    <div class="p-4 md:p-5 text-center">
                                        <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                        <h3 class="mb-5 text-lg font-normal text-gray-500">¿Está seguro de eliminar este item?</h3>
                                        <form action="{{ route('quimicos.destroyquimicos', $quimico->id_quimico) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                                                Sí­, estoy seguro
                                            </button>
                                            <button data-modal-hide="popup-modal-{{ $quimico->id_quimico }}" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100">
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
        {{ $quimicos->links() }}
    </div>
    <script>

    document.addEventListener('DOMContentLoaded', function () {
    // 1) Normalizar 0â€“4
    document.querySelectorAll('input[name="salud"], input[name="inflamabilidad"], input[name="reactividad"]').forEach(function (el) {
        try { el.setAttribute('type','number'); } catch(e) {}
        el.min = '0'; el.max = '4'; el.step = '1';
        el.placeholder = el.placeholder || '0-4';
    });

    // 2) Estado fÃ­sico: datalist
    const dlId = 'dl-estado-fisico';
    if (!document.getElementById(dlId)) {
        const dl = document.createElement('datalist');
        dl.id = dlId;
        ['SÃ³lido','LÃ­quido','Gas'].forEach(v=>{ const o=document.createElement('option'); o.value=v; dl.appendChild(o); });
        document.body.appendChild(dl);
    }
    document.querySelectorAll('input#estado_fisico, input[name="estado_fisico"]').forEach(function (el) {
        el.setAttribute('list', dlId);
    });

    // 3) MSDS: botÃ³n Abrir
    document.querySelectorAll('input[name="msds"]').forEach(function (el) {
        el.type = 'url';
        if (el.dataset.enhanced === '1') return;
        const a = document.createElement('a');
        a.textContent = 'Abrir';
        a.href = '#';
        a.className = 'msds-open inline-flex items-center px-3 py-2 rounded-lg border text-sm text-blue-700 border-blue-300 hover:bg-blue-50 ml-2';
        a.addEventListener('click', function (e) { e.preventDefault(); if (el.value) window.open(el.value, '_blank'); });
        el.insertAdjacentElement('afterend', a);
        el.dataset.enhanced = '1';
    });

    // 4) â€œNingunoâ€ deshabilita el resto del form
    document.querySelectorAll('form').forEach(function (form) {
        const ninguno = form.querySelector('input[type="checkbox"][name="ninguno"]');
        if (!ninguno) return;
        const others = Array.from(form.querySelectorAll('input[type="checkbox"]')).filter(c=>c.name!=='ninguno');
        function syncNone() {
        if (ninguno.checked) {
            others.forEach(c=>{ c.checked=false; c.disabled=true; });
        } else {
            others.forEach(c=>{ c.disabled=false; });
        }
        }
        ninguno.addEventListener('change', syncNone);
        syncNone();
    });

    // 5) Nivel visible (create + edit)
    try {
        const VAL_MAP = @json($valMap ?? []); // <<--- AQUI el cambio de $__valMap a $valMap
        function syncNivel(probSel, consSel, outId, outLabel){
        const k = (probSel?.value || '') + '-' + (consSel?.value || '');
        const v = VAL_MAP[k];
        if (!v) {
            if (outId) outId.value = '';
            if (outLabel) outLabel.value = '';
            return;
        }
        if (outId) outId.value = v.id;
        if (outLabel) outLabel.value = v.label;
        }

        document.querySelectorAll('form').forEach(function(form){
        const prob = form.querySelector('select[name="id_probabilidad"]');
        const cons = form.querySelector('select[name="id_consecuencia"]');
        const outId = form.querySelector('input[name="id_nivel_riesgo"]'); // puede no existir en Edit
        const outLabel = form.querySelector('input[placeholder="Nivel resultante"]') || form.querySelector('input[readonly]');

        if (prob && cons) {
            prob.addEventListener('change', ()=>syncNivel(prob,cons,outId,outLabel));
            cons.addEventListener('change', ()=>syncNivel(prob,cons,outId,outLabel));
            syncNivel(prob, cons, outId, outLabel); // inicializa al abrir
        }
        });
    } catch(e) {}
    });
    </script>
@endsection


