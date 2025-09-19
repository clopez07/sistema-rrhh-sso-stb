@extends('layouts.riesgos')

@section('title', 'MATRIZ DE RIESGOS')

@section('content')
@php
    // ===== Helpers =====
    $get = function($row, $key) {
        if (is_array($row))   return $row[$key] ?? null;
        if (is_object($row))  return $row->$key ?? null;
        return null;
    };

    // Evitar "undefined variable" si por algo no vienen
    $optsProb    = $optsProb    ?? collect();
    $optsCons    = $optsCons    ?? collect();
    $optsNivel   = $optsNivel   ?? collect();
    $optsCtrlIng = $optsCtrlIng ?? collect();
    $optsCtrlAdm = $optsCtrlAdm ?? collect();

    // Detectar columnas fijas vs dinámicas TIPO|NOMBRE
    $categorias    = [];  // $categorias[CAT] = ['riesgos'=>[], 'medidas'=>[]]
    $columnasFijas = [];

    $ES_MEDIDA = function($n) {
        $n = strtoupper($n);
        return in_array($n, [
            'EPP REQUERIDO',
            'CAPACITACIONES REQUERIDAS',
            'SEÑALIZACIÓN REQUERIDA',
            'OTRAS MEDIDAS REQUERIDAS',
        ], true);
    };

    if (!empty($matriz)) {
        $firstRow = (array) $matriz[0];
        foreach (array_keys($firstRow) as $columna) {
            if (str_contains($columna, '|')) {
                [$cat, $nombre] = explode('|', $columna, 2);
                $categorias[$cat] ??= ['riesgos'=>[], 'medidas'=>[]];
                if ($ES_MEDIDA($nombre)) {
                    $categorias[$cat]['medidas'][] = $nombre;
                } else {
                    $categorias[$cat]['riesgos'][] = $nombre;
                }
            } else {
                $columnasFijas[] = $columna;
            }
        }
    }

    // Orden bonito para las medidas-resumen
    $ORDEN_MEDIDAS = [
        'EPP REQUERIDO',
        'CAPACITACIONES REQUERIDAS',
        'SEÑALIZACIÓN REQUERIDA',
        'OTRAS MEDIDAS REQUERIDAS',
    ];

    // Primera columna a mostrar
    $firstColKey = 'puesto_trabajo_matriz';

    // De las fijas, omitimos el id y la primera (nombre)
    $restoFijas = array_values(array_filter(
        $columnasFijas,
        fn($c) => !in_array($c, ['id_puesto_trabajo_matriz', $firstColKey], true)
    ));

    // Colores por categoría
    $colores = [
        'MECANICO'              => ['bg-orange-500', 'bg-orange-200'],
        'ELECTRICO'             => ['bg-blue-500', 'bg-blue-200'],
        'FUEGO Y EXPLOSION'     => ['bg-yellow-500', 'bg-yellow-200'],
        'QUIMICOS'              => ['bg-gray-500', 'bg-gray-200'],
        'BIOLOGICO'             => ['bg-sky-500', 'bg-sky-200'],
        'ERGONOMICO'            => ['bg-slate-500', 'bg-slate-200'],
        'FENOMENOS AMBIENTALES' => ['bg-green-600', 'bg-green-300'],
        'FISICO'                => ['bg-rose-400', 'bg-rose-200'],
        'LOCATIVO'              => ['bg-red-600', 'bg-red-300'],
        'PSICOSOCIALES'         => ['bg-neutral-800', 'bg-neutral-500'],
    ];

    // Colores por nivel (para badge)
    // Nota: los nombres pueden venir como "Riesgo Alto", "Riesgo muy Alto", etc.
    $colorNivel = [
        'Riesgo muy Alto'    => '#ff0000',
        'Riesgo Alto'        => '#be5014',
        'Riesgo Medio'       => '#ffc000',
        'Riesgo Bajo'        => '#ffff00',
        'Riesgo Irrelevante' => '#92d050',
    ];

    // Map "TIPO|RIESGO" -> id_riesgo para data-attrs
    $mapRiesgos = $mapRiesgos ?? [];
@endphp

<div class="flex justify-end mb-3 gap-2 print:hidden">
  <a href="{{ route('matrizriesgos.export') }}"
     class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm shadow">
     Descargar Excel
  </a>
</div>

{{-- Encabezado con logo + títulos --}}
<div class="flex items-center justify-center gap-4 mb-4 print:mb-2">
    <img src="{{ asset('img/logo.PNG') }}" alt="Service and Trading Business"
         class="h-16 w-auto object-contain print:h-14" />
    <div class="text-center leading-tight">
        <h1 class="text-xl font-bold">MATRIZ DE IDENTIFICACIÓN DE RIESGOS POR PUESTO DE TRABAJO</h1>
        <p class="text-sm">PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS</p>
        <p class="text-sm">MATRIZ DE IDENTIFICACION DE PELIGROS Y EVALAUCION DE RIESGOS / HAZARD IDENTIFICATION AND RISK ASSESSMENT MATRIX</p>
    </div>
</div>

<style>
  .tabla-wrap{
    overflow-x: auto; overflow-y: auto; max-height: 70vh;
    border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;
    position: relative;
  }
  .tabla-matriz{ width: max-content; table-layout: auto; }
  :root{ --h1: 40px; --h2: 40px; --w-first-col: 320px; }
  thead tr.row-categorias th{ position: sticky; top: 0;        z-index: 100; height: var(--h1); }
  thead tr.row-riesgos   th{ position: sticky; top: var(--h1); z-index: 90;  height: var(--h2); }
  .first-col-width{ width: var(--w-first-col); min-width: var(--w-first-col); text-align: left; }
  .sticky-left-first-th{
    position: sticky; left: 0; top: 0; z-index: 130 !important;
    background:#fff; box-shadow: 2px 0 0 #e5e7eb;
  }
  .sticky-left-first-td{
    position: sticky; left: 0; z-index: 60;
    background:#fff; box-shadow: 2px 0 0 #e5e7eb;
  }
  .th-riesgo{ white-space: nowrap; min-width: 140px; }
  .mc-input{ @apply border border-gray-300 rounded-md px-2 py-1 text-sm w-full; }
</style>

<div class="tabla-wrap">
  <table class="tabla-matriz table-auto border-collapse border border-gray-400 text-sm">
    <thead>
      {{-- Fila 1: Categorías --}}
      <tr class="row-categorias">
        <th rowspan="2"
            class="border border-gray-400 px-2 py-1 text-center bg-gray-200 sticky-left-first-th first-col-width">
          {{ strtoupper(str_replace('_',' ', $firstColKey)) }}
        </th>

        {{-- Resto fijas --}}
        @foreach($restoFijas as $columna)
          <th rowspan="2" class="border border-gray-400 px-2 py-1 text-center bg-gray-200">
            {{ strtoupper(str_replace('_',' ', $columna)) }}
          </th>
        @endforeach

        {{-- Categorías (riesgos + resúmenes) --}}
        @foreach($categorias as $categoria => $grupo)
          @php
            $riesgosCat = $grupo['riesgos'] ?? [];
            $medidasCat = $grupo['medidas'] ?? [];
            $colspan    = count($riesgosCat) + count($medidasCat);
            $color      = $colores[$categoria][0] ?? 'bg-gray-400';
          @endphp
          <th colspan="{{ $colspan }}"
              class="border border-gray-400 px-2 py-1 text-center font-bold text-white {{ $color }}">
              {{ $categoria }}
          </th>
        @endforeach

        {{-- Personas expuestas --}}
        <th rowspan="2" class="border border-gray-400 px-2 py-1 text-center font-bold bg-gray-100">
          NO. DE PERSONAS EXPUESTAS
        </th>

        {{-- Grupo Evaluación --}}
        <th colspan="3" class="border border-gray-400 px-2 py-1 text-center font-bold text-white bg-slate-600">
          EVALUACIÓN DE RIESGOS
          <div class="text-xs font-normal">SEGURIDAD OCUPACIONAL</div>
        </th>

        {{-- Grupo Control --}}
        <th colspan="9" class="border border-gray-400 px-2 py-1 text-center font-bold text-white bg-slate-700">
          CONTROL DE RIESGOS
          <div class="text-xs font-normal">TIPO DE MEDIDAS DE CONTROL</div>
        </th>
      </tr>

      {{-- Fila 2: Subcolumnas --}}
      <tr class="row-riesgos">
        @foreach($categorias as $categoria => $grupo)
          @php
            $riesgosCat  = $grupo['riesgos'] ?? [];
            $medidasCat  = $grupo['medidas'] ?? [];
            $colorRiesgo = $colores[$categoria][1] ?? 'bg-gray-200';
          @endphp

          {{-- Peligros --}}
          @foreach($riesgosCat as $riesgo)
            <th class="border border-gray-400 px-2 py-1 text-center {{ $colorRiesgo }} th-riesgo">
              {{ $riesgo }}
            </th>
          @endforeach

          {{-- Resumen medidas --}}
          @foreach($ORDEN_MEDIDAS as $med)
            @if(in_array($med, $medidasCat, true))
              <th class="border border-gray-400 px-2 py-1 text-center bg-gray-100 th-riesgo">
                {{ $med }}
              </th>
            @endif
          @endforeach
        @endforeach

        {{-- Evaluación --}}
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">PROBABILIDAD</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">CONSECUENCIAS</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">NIVEL RIESGO</th>

        {{-- Control --}}
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">ELIMINACIÓN</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">SUSTITUCIÓN</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">AISLAR</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">CONTROL DE INGENIERÍA</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">CONTROLES ADMINISTRATIVO</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">EPP REQUERIDO</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">CAPACITACIONES REQUERIDAS</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">SEÑALIZACIÓN REQUERIDA</th>
        <th class="border border-gray-400 px-2 py-1 text-center bg-gray-200 th-riesgo">OTRAS MEDIDAS REQUERIDAS</th>
      </tr>
    </thead>

    <tbody>
      @foreach($matriz as $fila)
        @php $row = (array) $fila; @endphp
        <tr data-puesto-id="{{ $row['id_puesto_trabajo_matriz'] ?? $fila->id_puesto_trabajo_matriz }}">
          {{-- Primera columna --}}
          <td class="border border-gray-400 px-2 py-1 sticky-left-first-td first-col-width">
            {{ $get($fila, $firstColKey) }}
          </td>

          {{-- Resto fijas --}}
          @foreach($restoFijas as $columna)
            <td class="border border-gray-400 px-2 py-1 text-center">
              {{ $get($fila, $columna) }}
            </td>
          @endforeach

          {{-- Por categoría: peligros + resúmenes --}}
          @foreach($categorias as $categoria => $grupo)
            @php
              $riesgosCat = $grupo['riesgos'] ?? [];
              $medidasCat = $grupo['medidas'] ?? [];
            @endphp

            {{-- Peligros (Sí/No) --}}
            @foreach($riesgosCat as $riesgo)
              @php $key = $categoria.'|'.$riesgo; @endphp
              <td class="border border-gray-400 px-2 py-1 text-center cursor-pointer risk-cell"
                  data-colkey="{{ $key }}"
                  data-riesgo-id="{{ $mapRiesgos[$key] ?? '' }}">
                {{ $row[$key] ?? '' }}
              </td>
            @endforeach

            {{-- Resúmenes (texto) --}}
            @foreach($ORDEN_MEDIDAS as $med)
              @if(in_array($med, $medidasCat, true))
                @php $k = $categoria.'|'.$med; @endphp
                <td class="border border-gray-400 px-2 py-1">
                  {{ trim($row[$k] ?? '') ?: 'No Requiere' }}
                </td>
              @endif
            @endforeach
          @endforeach

          {{-- Personas expuestas (num_empleados) --}}
          @php $puestoId = (is_array($fila)? $fila['id_puesto_trabajo_matriz'] : $fila->id_puesto_trabajo_matriz); @endphp
          <td class="border border-gray-400 px-2 py-1 text-center">
            {{ $empleados[$puestoId] ?? 0 }}
          </td>

          {{-- Prob/Cons (datalist por nombre) y Nivel (badge + bg celda) --}}
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-44" placeholder="Selecciona..." list="dl-prob" data-field="id_probabilidad">
          </td>
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-44" placeholder="Selecciona..." list="dl-cons" data-field="id_consecuencia">
          </td>
          <td class="border border-gray-400 px-2 py-1 text-center" data-nivel-td>
            <div class="flex items-center gap-2 justify-center">
              <span class="nivel-pill hidden rounded px-2 py-1 text-xs font-semibold"></span>
            </div>
          </td>

          {{-- ELIMINACIÓN / SUSTITUCIÓN / AISLAR --}}
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-28" list="dl-si-na" data-field="eliminacion">
          </td>
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-28" list="dl-si-na" data-field="sustitucion">
          </td>
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-28" list="dl-si-na" data-field="aislar">
          </td>

          {{-- CONTROL INGENIERÍA --}}
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-64" list="dl-ctrl-ing2" data-field="id_control_ingenieria" placeholder="Selecciona...">
          </td>

          {{-- CONTROL ADMINISTRATIVO --}}
          <td class="border border-gray-400 px-2 py-1 text-center">
            <input class="mc-input w-64" list="dl-ctrl-adm2" data-field="id_control_administrativo" placeholder="Selecciona...">
          </td>

          {{-- Resumen global (concat de todos los riesgos "Sí") --}}
          <td class="border border-gray-400 px-2 py-1">
            {{ trim($resumen[$puestoId]->epp  ?? '') ?: 'No Requiere' }}
          </td>
          <td class="border border-gray-400 px-2 py-1">
            {{ trim($resumen[$puestoId]->caps ?? '') ?: 'No Requiere' }}
          </td>
          <td class="border border-gray-400 px-2 py-1">
            {{ trim($resumen[$puestoId]->senal ?? '') ?: 'No Requiere' }}
          </td>
          <td class="border border-gray-400 px-2 py-1">
            {{ trim($resumen[$puestoId]->otras ?? '') ?: 'No Requiere' }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- ======== DATALISTS (globales) ======== --}}
<datalist id="dl-prob">
  @foreach($optsProb as $o) <option value="{{ $o->nombre }}"></option> @endforeach
</datalist>
<datalist id="dl-cons">
  @foreach($optsCons as $o) <option value="{{ $o->nombre }}"></option> @endforeach
</datalist>
<datalist id="dl-ctrl-adm2">
  <option value="Examenes medico ocupacionales"></option>
  <option value="Capacitación"></option>
  <option value="Aplicación de procedimientos seguro como (permiso de trabajo seguro)"></option>
  <option value="Pautas activas"></option>
  <option value="Rotacion de personal en el puesto de trabajo"></option>
  <option value="Utilizar ayuda mecanica para tareas"></option>
  <option value="Establecer peso limite del material a manipular"></option>
  <option value="Identificar el personal que esta en un riesgo especifico (quimicos, peso, ruido, frio, etc.)"></option>
  <option value="Realizar programacion de inspecciones de seguridad"></option>
    <option value="Implementar programa de orden y aseo en las areas de trabajo"></option>
  <option value="Establecer procedimientos de almacenamiento seguro  de quimicos en bodegas"></option>
  <option value="Establecer procedimientos de almacenamiento seguro  de combustibles y lubricantes en bodega"></option>
  <option value="Plan de seguimiento de gestio de condiciones insegurar identificados en inspecciones"></option>
  <option value="Establecer programas de mantenimiento preventivos de maquinaria, infraestructura, equipos, etc."></option>
  <option value="Implementar area de circulacion de personas y maquinas señalizadas, demarcadas y despejadas"></option>
  <option value="Conformacion de brigadas de emergencias"></option>
  <option value="Extintores suficientes de extintores"></option>
    <option value="Almacenamiento seguro de quimicos"></option>
  <option value="Realizar simulacros de evacuacion"></option>
  <option value="Realizar procedimiento de plan de contigencia"></option>
  <option value="Rotulacion, señalizacion  y hojas de seguridad de quimicos y material inflamable"></option>
  <option value="Instalacion de kit antiderrames"></option>
  <option value="Demarcar y señalizar areas de trabajo"></option>
  <option value="Inspeccion de elementos de proteccion personal y sistema de proteccion contra caidas"></option>
  <option value="Contar procedimiento operativos para atencion y rescate en alturas"></option>
    <option value="Entrenamiento especifico y correspondiente  para la realizacion de tareas y adaptacion de cambios en proceso"></option>
  <option value="Actualizacion de programa de plan de contigencias y emergencias"></option>
  <option value="Ninguna"></option>
</datalist>
<datalist id="dl-ctrl-ing2">
  <option value="Aislar fuentes generadores de ruido">Aislar fuentes generadores de ruido</option>
  <option value="Mediciones de iluminacion">Mediciones de iluminacion</option>
  <option value="Mayor iluminacion natural">Mayor iluminacion natural</option>
  <option value="Mayor iluminacion artificial">Mayor iluminacion artificial</option>
  <option value="Fuentes de Luz libres de obstaculos">Fuentes de Luz libres de obstaculos</option>
  <option value="Diseño ergonomico de Puesto de trabajo">Diseño ergonomico de Puesto de trabajo</option>
  <option value="Altura de mesa de trabajo">Altura de mesa de trabajo</option>
  <option value="Instalacion de descanza pies">Instalacion de descanza pies</option>
  <option value="Reduccion o rediseño de la carga">Reduccion o rediseño de la carga</option>
  <option value="Mantenimiento Oportuna de infraestructura">Mantenimiento Oportuna de infraestructura</option>
  <option value="Rediseño de areas de trabajo">Rediseño de areas de trabajo</option>
  <option value="Aislamiento de fuentes de ignicion de material inflamable">Aislamiento de fuentes de ignicion de material inflamable</option>
  <option value="Control de fuentes de calor">Control de fuentes de calor</option>
  <option value="Mantenimiento de ductor, tanques, mangueras y accesorios de gas y liquidos inflamables">Mantenimiento de ductor, tanques, mangueras y accesorios de gas y liquidos inflamables</option>
  <option value="Ventilacion suficiente (artificial o natural)">Ventilacion suficiente (artificial o natural)</option>
  <option value="Utilizar ayuda mecanica en la labor">Utilizar ayuda mecanica en la labor</option>
  <option value="Mantener superficies lisos, sin obstaculos y sin irregularidades">Mantener superficies lisos, sin obstaculos y sin irregularidades</option>
  <option value="Ninguna">Ninguna</option>
</datalist>
<datalist id="dl-si-na">
  <option value="N/A">N/A</option>
  <option value="Eliminar el Riesgo">Eliminar el Riesgo</option>
  <option value="Aislar el Riesgo">Aislar el Riesgo</option>
  <option value="Sustituir el Riesgo">Sustituir el Riesgo</option>
</datalist>

{{-- Safelist Tailwind para clases dinámicas --}}
<div class="hidden
  bg-red-600 bg-orange-500 bg-yellow-300 bg-green-300 bg-green-500
  bg-red-100 bg-orange-100 bg-yellow-100 bg-green-100 text-white"></div>

{{-- ======== JS: selección de riesgo + autoguardado + colores de nivel ======== --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
(() => {
  // Toast minimalista para feedback de guardado
  const toastHost = document.createElement('div');
  toastHost.style.position = 'fixed';
  toastHost.style.top = '16px';
  toastHost.style.right = '16px';
  toastHost.style.zIndex = '9999';
  document.addEventListener('DOMContentLoaded', ()=>document.body.appendChild(toastHost));
  function showToast(msg, type='info'){
    const el = document.createElement('div');
    el.textContent = msg;
    el.style.marginTop = '8px';
    el.style.padding = '10px 14px';
    el.style.borderRadius = '8px';
    el.style.fontSize = '13px';
    el.style.boxShadow = '0 2px 8px rgba(0,0,0,.15)';
    el.style.color = '#111';
    el.style.background = type==='success' ? '#D1FAE5' : (type==='error' ? '#FEE2E2' : '#E5E7EB');
    el.style.border = '1px solid ' + (type==='success' ? '#10B981' : (type==='error' ? '#EF4444' : '#9CA3AF'));
    toastHost.appendChild(el);
    setTimeout(()=>{ el.style.transition='opacity .4s'; el.style.opacity='0'; setTimeout(()=>el.remove(), 400); }, 1500);
  }
  // Catálogos para nombre<->id
  const PROB = @json($optsProb);       // [{id, nombre}]
  const CONS = @json($optsCons);
  const NIVL = @json($optsNivel);      // para mostrar badge si hace falta
  const VR   = @json($vrMatrix);       // {"probId-consId": nivelId}
  const MAP  = @json($mapRiesgos ?? []); // "TIPO|RIESGO" => id_riesgo
  const PRE  = @json($medidasByPuesto ?? new \stdClass()); // { puestoId: {..campos..} }

  const probNameToId = Object.fromEntries(PROB.map(o => [o.nombre, o.id]));
  const consNameToId = Object.fromEntries(CONS.map(o => [o.nombre, o.id]));
  const nivelIdToName= Object.fromEntries(NIVL.map(o => [o.id, o.nombre]));

  // Normalizar mapa de colores desde PHP
  const colorNivelRaw = @json($colorNivel);
  const NORM = s => (s||'').toString().trim().toUpperCase().replace(/^RIESGO\s+/, '');
  const colorNivelByName = {};
  Object.entries(colorNivelRaw).forEach(([k,v]) => colorNivelByName[NORM(k)] = v);

  // Fondos suaves por nivel (para la <td>)
  const nivelBgByName = {
    'MUY ALTO'   : 'bg-red-100',
    'ALTO'       : 'bg-orange-100',
    'MEDIO'      : 'bg-yellow-100',
    'BAJO'       : 'bg-green-100',
    'IRRELEVANTE': 'bg-green-100'
  };
  const knownBg = Object.values(nivelBgByName);

  function nivelClass(name){
    if (!name) return '';
    return colorNivelByName[NORM(name)] || '';
  }

  function getNivel(probId, consId){
    const nid = VR[`${probId}-${consId}`] || null;
    return { id: nid, nombre: nid ? (nivelIdToName[nid] || '') : '' };
  }

  // Aplica datos de un registro a una fila
  function setRowFromRecord(tr, data){
    if (!tr || !data) return;
    const set = (sel, val) => { const el = tr.querySelector(sel); if(el) el.value = val ?? ''; };
    const inpProb = tr.querySelector('input[data-field="id_probabilidad"]');
    const inpCons = tr.querySelector('input[data-field="id_consecuencia"]');
    if (inpProb) inpProb.value = data.id_probabilidad ? (PROB.find(x=>+x.id===+data.id_probabilidad)?.nombre || '') : '';
    if (inpCons) inpCons.value = data.id_consecuencia ? (CONS.find(x=>+x.id===+data.id_consecuencia)?.nombre || '') : '';
    set('input[data-field="eliminacion"]',               data.eliminacion);
    set('input[data-field="sustitucion"]',               data.sustitucion);
    set('input[data-field="aislar"]',                    data.aislar);
    set('input[data-field="id_control_ingenieria"]',     data.control_ingenieria);
    set('input[data-field="id_control_administrativo"]', data.control_administrativo);
    const pId = data.id_probabilidad || null;
    const cId = data.id_consecuencia || null;
    paintNivel(tr, pId, cId);
  }

  // Precarga al entrar/recargar la página (por puesto)
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('tr[data-puesto-id]').forEach(tr => {
      const puestoId = +tr.dataset.puestoId;
      const rec = PRE && puestoId ? PRE[puestoId] : null;
      if (rec) { setRowFromRecord(tr, rec); tr.dataset.loaded = '1'; }
    });
  });

  // Carga perezosa al enfocar (si no estuvo precargado)
  document.addEventListener('focusin', async (e) => {
    if (!e.target.classList || !e.target.classList.contains('mc-input')) return;
    const tr = e.target.closest('tr');
    if (!tr || tr.dataset.loaded === '1') return;
    const puestoId = +tr.dataset.puestoId;
    if (!puestoId) return;
    // Si ya tenemos PRE, úsalo; si no, consulta
    const local = PRE && PRE[puestoId];
    if (local) { setRowFromRecord(tr, local); tr.dataset.loaded='1'; return; }
    try{
      const q = new URLSearchParams({ puesto_id: puestoId });
      const res = await fetch(`{{ route('riesgos.medida.get') }}?${q}`);
      const data = res.ok ? await res.json() : {};
      setRowFromRecord(tr, data||{});
      tr.dataset.loaded = '1';
    }catch(err){ console.warn('prefill error', err); }
  });

  function paintNivel(tr, probId, consId){
    const pill = tr.querySelector('.nivel-pill');
    const td   = tr.querySelector('td[data-nivel-td]');
    if (!pill || !td) return;

    // calcular
    const {id, nombre} = (probId && consId) ? getNivel(probId, consId) : {id:null, nombre:''};

    // badge
    const cls = nivelClass(nombre);
    pill.textContent = nombre || '';
    pill.className = 'nivel-pill ' + (nombre ? ('rounded px-2 py-1 text-xs font-semibold ' + (cls || 'bg-gray-200')) : 'hidden rounded px-2 py-1 text-xs font-semibold');

    // fondo <td>
    knownBg.forEach(b => td.classList.remove(b));
    const bg = nivelBgByName[NORM(nombre)] || '';
    if (nombre && bg) td.classList.add(bg);

    // guardar id en dataset (por si se necesita)
    pill.dataset.nivelId = id || '';
  }

  // Guardar (debounce y en blur/change)
  const debounce = (fn, ms=500)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
  const debouncedSave = debounce((tr)=>saveRow(tr), 500);

  async function saveRow(tr){
    const puestoId = +tr.dataset.puestoId;
    if (!puestoId) return;

    // mapear nombres -> ids
    const pName = tr.querySelector('input[data-field="id_probabilidad"]').value.trim();
    const cName = tr.querySelector('input[data-field="id_consecuencia"]').value.trim();
    const pId = probNameToId[pName] || null;
    const cId = consNameToId[cName] || null;

    // nivel calculado
    const {id: nivelId} = (pId && cId) ? getNivel(pId, cId) : {id:null};

    const payload = {
      id_puesto_trabajo_matriz: puestoId,
      id_probabilidad: pId,
      id_consecuencia: cId,
      id_nivel_riesgo: nivelId,
      eliminacion:             tr.querySelector('input[data-field="eliminacion"]').value || null,
      sustitucion:             tr.querySelector('input[data-field="sustitucion"]').value || null,
      aislar:                  tr.querySelector('input[data-field="aislar"]').value || null,
      control_ingenieria:      tr.querySelector('input[data-field="id_control_ingenieria"]').value || null,
      control_administrativo:  tr.querySelector('input[data-field="id_control_administrativo"]').value || null,
    };

    try {
      const res = await fetch(`{{ route('riesgos.medida.save') }}`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      tr.style.backgroundColor = '#f0fff4';
      setTimeout(()=>tr.style.backgroundColor='',250);
      showToast('Guardado', 'success');
    } catch (err) {
      tr.style.backgroundColor = '#fff1f2';
      setTimeout(()=>tr.style.backgroundColor='',400);
      showToast('No se pudo guardar', 'error');
      console.error('Save error', err);
    }
  }

  // listeners
  document.addEventListener('input', e => {
    if (!e.target.classList.contains('mc-input')) return;
    const tr = e.target.closest('tr');
    // si cambia Prob/Cons, recalculamos y pintamos
    if (e.target.dataset.field === 'id_probabilidad' || e.target.dataset.field === 'id_consecuencia') {
      const pId = probNameToId[(tr.querySelector('input[data-field="id_probabilidad"]').value||'').trim()] || null;
      const cId = consNameToId[(tr.querySelector('input[data-field="id_consecuencia"]').value||'').trim()] || null;
      paintNivel(tr, pId, cId);
    }
    debouncedSave(tr);
  });

  document.addEventListener('change', e => {
    if (!e.target.classList.contains('mc-input')) return;
    const tr = e.target.closest('tr');
    if (e.target.dataset.field === 'id_probabilidad' || e.target.dataset.field === 'id_consecuencia') {
      const pId = probNameToId[(tr.querySelector('input[data-field="id_probabilidad"]').value||'').trim()] || null;
      const cId = consNameToId[(tr.querySelector('input[data-field="id_consecuencia"]').value||'').trim()] || null;
      paintNivel(tr, pId, cId);
    }
    saveRow(tr);
  });

  document.addEventListener('blur', e => {
    if (!e.target.classList || !e.target.classList.contains('mc-input')) return;
    saveRow(e.target.closest('tr'));
  }, true);

})();
</script>
@endsection
