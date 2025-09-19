<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Impresión Entregas EPP - {{  $fecha }}</title> 
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
  .exact-wrap { max-width:210mm; margin:0 auto; }
  .exact-header { text-align:center; line-height:1.25; margin-bottom:6px; }
  .exact-line { border-bottom:1px solid #000; height:1px; width:100%; }
  .exact-tag { text-align:right; font-weight:700; margin:6px 0 8px; }
  .exact-row { display:flex; gap:8px; align-items:flex-end; margin:8px 0; font-size:12px; }
  .exact-label { white-space:nowrap; font-weight:700; }
  .exact-fill { display:inline-block; min-width:280px; border-bottom:1px dotted #000; line-height:1.6; }
  .exact-fill.sm { min-width:64px; }
  .exact-text { font-size:15px; text-align:justify; line-height:1.45; }
  .exact-list { margin:8px 0 0 18px; font-size:15px; }
  .exact-sign { margin-top:28px; text-align:center; }
  .exact-sign .line { border-top:1px solid #000; height:70px; margin-top:28px; }
  .exact-footer { display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; margin-top:10px; font-size:11px; }
  .page-break { page-break-after: always; }
  @media print { .page-break{ page-break-after:always; } }

  :root{
    --brand:#44B3E1;
    --text:#111827;
    --muted:#4b5563;
    --border:#e5e7eb;
  }

  @page { size: A4; margin: 12mm; }
  html,body{ margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; color:var(--text); }
  *, *::before, *::after{ box-sizing: border-box; }

  /* Toolbar */
  .toolbar{
    position: sticky; top:0; z-index:5;
    background:#fff; padding:10px 12px; border-bottom:1px solid var(--border);
    display:flex; gap:8px; justify-content:flex-end;
  }
  .btn{ background:var(--brand); color:#fff; border:1px solid var(--brand); padding:8px 12px; border-radius:8px; cursor:pointer; }
  .btn.secondary{ background:#f3f4f6; color:#111; border-color:#d1d5db; }

  /* Hoja/Sección */
  .wrap{ max-width:210mm; margin:0 auto; padding:10mm 10mm 16mm; }
  .sheet{
    background:#fff; padding:14mm; border:1px solid var(--border); border-radius:8px;
    margin-bottom:12mm;
  }

  /* Encabezado corporativo */
  .header{
    display:grid; grid-template-columns: 110px 1fr; gap:16px; align-items:center;
    margin-bottom:12px; border-bottom:3px solid var(--brand); padding-bottom:10px;
  }
  .logo{ width:110px; height:110px; object-fit:contain;}
  .company{ font-weight:bold; font-size:15px; line-height:1.2; }
  .process{ font-size:12px; color:var(--muted); }

  /* Títulos formato */
  .title{
    text-align:center; margin:14px 0 10px 0; padding:10px 12px;
    border:1px solid var(--brand);
    background:linear-gradient(0deg, rgba(0,176,240,.08), rgba(0,176,240,.08));
    font-weight:bold; letter-spacing:.2px;
  }
  .tag{ float:right; font-size:12px; color:#0369a1; font-weight:bold; }

  /* Layout campos */
  .row{ display:flex; gap:8px; margin:6px 0; font-size:12px; }
  .label{ font-weight:700; color:var(--muted); min-width:150px; }
  .field{ border-bottom:1px dotted var(--muted); min-height:22px; padding-bottom:2px; flex:1; }

  /* Texto */
  .paragraph{ text-align:justify; margin-top:10px; line-height:1.45; font-size:13px; }
  .muted{ color:var(--muted); }

  /* Caja resalte */
  .box{ border:1px solid var(--brand); padding:10px; border-radius:6px; background:#f8fdff; }

  /* Tablas */
  table{ width:100%; border-collapse:collapse; margin-top:8px; }
  th{ background:var(--brand); color:#000; text-transform:uppercase; text-align:left; }
  th, td{ border:1px solid var(--border); padding:8px; font-size:12px; vertical-align:top; }

  /* Firmas */
  .firmas{ display:flex; gap:18px; margin-top:24px; }
  .firma{ height:70px; border-top:1px solid #000; margin-top:28px; text-align:center; padding-top:4px; flex:1; }

  /* Print */
  @media print{
    .toolbar{ display:none !important; }
    .wrap{ padding:0; }
    .sheet{ border:none; border-radius:0; page-break-after: always; }
    body{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }

  .exact-header-abs{ position:relative; margin-bottom:6px; min-height:100px; }
  .exact-logo{ position:absolute; left:0; top:0; width:100px; height:100px; object-fit:contain; }

  :root{ --logo-w: 100px; --logo-h: 100px; }
  .header-flex{
    display:flex; align-items:flex-start; column-gap:14px; margin-bottom:6px;
  }
  .header-flex .logo{ width:var(--logo-w); height:var(--logo-h); object-fit:contain; }
  .header-flex .center{ flex:1; text-align:center; line-height:1.25; }
  .header-flex .spacer{ width:var(--logo-w); height:var(--logo-h); visibility:hidden; }

  .exact-line-row{ display:flex; align-items:flex-end; gap:8px; margin:8px 0 2px; font-size:12px; }
  .exact-line-row .prefix{ white-space:nowrap; font-weight:700; }
  .exact-line-row .line{ flex:1; height:16px; border-bottom:1px dotted #000; position:relative; }
  .exact-line-row .line .line-value{
    position:absolute; left:6px; bottom:2px; padding:0 4px; background:#fff; font-weight:700;
  }
  .line-caption{ font-size:12px; text-align:center; margin-top:4px; font-style:italic; }
  .signature-paragraph{ margin-top:14px; font-size:14px; }
  .blank{ display:inline-block; border-bottom:1px solid #000; height:0.95em; vertical-align:baseline; }
  .blank.day{ width:70px; } .blank.month{ width:80px; }
  .signature-center{ margin-top:60px; text-align:center; }
  .signature-line{ width:300px; margin:0 auto 8px; border-top:1px solid #000; height:0; }
  .signature-caption{ font-weight:700; }

  /* Pie exacto */
  .exact-footer{
    display:grid; grid-template-columns: 1fr auto; align-items:end; margin-top:40px; color:#000; font-size:13px;
  }
  .exact-footer .left  { justify-self:start; text-align:left; line-height:1.45; }
  .exact-footer .right { justify-self:end;   text-align:right; }
  .exact-footer .left .version{ font-size:12px; margin-top:4px; margin-left:70px; }

  /* GRUPAL horizontal */
  @page grupal-land { size: A4 landscape; margin: 8mm; }
  .sheet.grupal { page: grupal-land; }
  .sheet.grupal.exact-wrap{ max-width: none !important; }
  @media screen{ .sheet.grupal{ max-width: 297mm; } }
  .sheet.grupal{ display:flex; flex-direction:column; min-height: 194mm; padding: 8mm; box-sizing:border-box; }
  .sheet.grupal .exact-footer{ margin-top:auto; }
  .sheet.grupal .exact-footer{
    display:grid; grid-template-columns: 1fr auto 1fr; align-items:end; margin-top:10px; color:#000; font-size:13px;
  }
  .sheet.grupal .exact-footer .left{ justify-self:start; text-align:left; line-height:1.45; }
  .sheet.grupal .exact-footer .center{ grid-column:2; justify-self:center; text-align:center; }
  .grupal .row{ gap:16px; margin:3px 0; font-size:11px; }
  .grupal .label{ min-width:150px; font-weight:700; color:#000; }
  .grupal .field{ min-height:16px; border-bottom:1px solid #000; }

  .table-grupal{ width:100%; table-layout:fixed; border-collapse:collapse; margin-top:6px; }
  .table-grupal th{ background:#00B0F0; color:#000; text-transform:uppercase; font-weight:700; text-align:center; }
  :root{ --gr-row-h: 8mm; }
  .table-grupal th, .table-grupal td{ border:1px solid #000; padding:6px 8px; font-size:11px; line-height:1.2; vertical-align:top; }
  .table-grupal tbody tr{ height: var(--gr-row-h); }
  .table-grupal td:nth-child(1),
  .table-grupal td:nth-child(4),
  .table-grupal td:nth-child(5){ text-align:center; }

  @media print{
    .wrap{ max-width:none !important; padding:0 !important; }
    .sheet.grupal{ zoom:1; break-inside:avoid; page-break-inside:avoid; }
    body{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }

  /* CARTUCHOS horizontal */
  @page cartuchos-land { size: A4 landscape; margin: 8mm; }
  .sheet.cartuchos{
    page: cartuchos-land;
    max-width:none !important;
    display:flex; flex-direction:column; min-height:194mm; padding:8mm; box-sizing:border-box; break-inside:avoid;
  }
  @media screen{ .sheet.cartuchos{ max-width:297mm; } }

  :root{ --cart-row-h: 16mm; }
  .table-cartuchos{ width:100%; table-layout:fixed; border-collapse:collapse; }
  .table-cartuchos th{ background:#D9D9D9; color:#000; text-align:center; }
  .table-cartuchos th, .table-cartuchos td{ border:1px solid #000; padding:4px 6px; font-size:11px; }
  .table-cartuchos tbody tr{ height:var(--cart-row-h); }
  .sheet.cartuchos .exact-footer{ margin-top:auto; }

  /* Print cleanup */
  @media print{
    .sheet:first-of-type{ break-before:auto; page-break-before:auto; }
    .sheet:first-child{ margin-top:0 !important; }
    .toolbar, .no-print { display:none !important; }
    .wrap{ padding:0 !important; margin:0 !important; }
    .sheet{ page-break-after:always; break-inside:avoid; page-break-inside:avoid; }
  }

  /* ===== Modal de Observaciones ===== */
  .modal-backdrop{
    position:fixed; inset:0; background:rgba(0,0,0,.45);
    display:none; align-items:center; justify-content:center; z-index:9999;
  }
  .modal{
    width:min(940px, 92vw); max-height:85vh; overflow:auto;
    background:#fff; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.2);
    padding:14px 16px;
  }
  .modal-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
  .modal-header h3{ margin:0; }
  .modal-header .close{ background:#f3f4f6; border:1px solid #e5e7eb; border-radius:8px; padding:6px 10px; cursor:pointer; }
  .modal-body{ padding:6px 2px 10px; }
  .comentarios-form .row{
    display:grid; grid-template-columns: 1fr 1fr; gap:10px; align-items:start;
    border-bottom:1px solid #e5e7eb; padding:10px 0;
  }
  .comentarios-form .row .meta{ font-size:12.5px; color:#4b5563; }
  .comentarios-form textarea{
    width:100%; min-height:70px; resize:vertical;
    border:1px solid #d1d5db; border-radius:8px; padding:8px;
    font-family:inherit; font-size:13px;
  }
  .modal-footer{ display:flex; gap:8px; justify-content:flex-end; padding-top:8px; }
</style>
</head>
<body>

<div class="toolbar">
  <button class="btn" onclick="window.print()">Imprimir</button>
  <button class="btn secondary" onclick="window.close()">Cerrar</button>
  @if(isset($cartuchos) && $cartuchos->count())
    <button id="btnComentarios" class="btn secondary">Agregar comentarios</button>
  @endif
</div>

<div class="wrap">

{{-- ===================== INDIVIDUAL ===================== --}}
@php
  $empleadosOrdenados = collect($empleados ?? [])
      ->sortBy(fn($e) => mb_strtoupper($e->nombre_completo ?? '', 'UTF-8'))
      ->values();

  $fObj = \Carbon\Carbon::parse($fecha)->locale('es');
  $diaNumero  = (int) $fObj->isoFormat('D');
  $mesNombre  = $fObj->isoFormat('MMMM');
  $anioNumero = $fObj->year;
@endphp

@foreach($empleados as $emp)
<section class="sheet exact-wrap">

  <div class="header-flex">
    <img class="logo" src="{{ $logoUrl ?? asset('img/logo.png') }}" alt="Logo" />
    <div class="center">
      <div class="exact-header" style="font-weight:700;">SERVICE AND TRADING BUSINESS S.A. DE C.V.</div>
      <div class="exact-header" style="font-weight:700;">PROCESO DE SALUD Y SEGURIDAD /PROCESS OCCUPATIONAL HEALTH AND SAFETY</div>
      <div class="exact-header" style="margin-top:6px; font-weight:700;">ENTREGA DE EQUIPO PROTECCION PERSONAL/ DELIVERY OF EQUIPMENT PERSONAL PROTECTION</div>
    </div>
    <div class="spacer" aria-hidden="true"></div>
  </div>

  <br>
  <div class="exact-tag" style="text-align:center; font-size:10px;">CONTROL DE ENTREGA</div>
  <br>

  <div class="exact-line-row">
    <div class="prefix">Yo,</div>
    <div class="line">
      @if(!empty($emp->nombre_completo))
        <span class="line-value">{{ mb_strtoupper($emp->nombre_completo, 'UTF-8') }}</span>
      @endif
    </div>
  </div>
  <div class="line-caption">Persona Responsable</div>

  <div class="exact-line-row" style="margin-top:14px;">
    <div class="line">
      @if(!empty($emp->puesto_trabajo))
        <span class="line-value">{{ mb_strtoupper($emp->puesto_trabajo, 'UTF-8') }}</span>
      @endif
    </div>
  </div>
  <div class="line-caption">Puesto/Área de Trabajo</div>

  <p class="exact-text" style="margin-top:14px;">
    Acepto haber recibido implementos de Equipo de Protección Personal previamente solicitado y detallado que consta en un
    <span class="epp-list">
      @php $items = array_filter(array_map('trim', explode(',', $emp->epp_lista ?? ''))); @endphp
      @foreach($items as $k => $it)<u><strong>{{ $it }}</strong></u>@if($k < count($items)-1), @endif @endforeach
    </span>
    y me comprometo a utilizarlo en el Área/Puesto de Trabajo en el que me encuentro asignado, así como cuidarlo y
    de haber recibido las instrucciones para su correcto uso.
  </p>

  <div style="font-size:15px; font-weight:700; margin-top:10px;">Aceptando el compromiso que se le solicita de:</div>
  <ul class="exact-list">
    <li>Utilizar este equipo durante la jornada de trabajo en las tareas y/o en las áreas cuya obligatoriedad de uso se me haya indicado o se encuentre señalizada.</li>
    <li>Consultar cualquier duda sobre su correcta utilización, cuidando de su perfecto estado y conservación.</li>
    <li>Solicitar un nuevo equipo en caso de pérdida o deterioro de este.</li>
    <li>Revisar el estado correcto del EPP al momento de la entrega.</li>
  </ul>

  <br>
  Se firma la presente a los
  <span class="blank day">{{ $diaNumero }}</span>
  días del mes
  <span class="blank month">{{ $mesNombre }}</span>
  del año
  {{ $anioNumero }}.
  <br><br><br>

  <div class="signature-center">
    <div class="signature-line"></div>
    <div class="signature-caption">Firma del Solicitante</div>
  </div>

  <div class="exact-footer">
    <div class="left">
      1 Copia Archivo<br>
      1 Copia sistema
      <div class="version">3 VERSION 2025</div>
    </div>
    <div class="center">STB/SSO/R004</div>
  </div>
</section>
@endforeach

{{-- ===================== GRUPAL (A4 horizontal) ===================== --}}
@foreach($porPuesto as $puesto => $lista)
@php
  $grupo = collect($lista)->values();
  $departamentoGrupo = $departamentosPorPuesto[$puesto] ?? ($grupo->first()->departamento ?? '');
  $perPage = 6;
  $paginas = $grupo->chunk($perPage);
@endphp

@foreach($paginas as $pageIndex => $chunk) 
<section class="sheet exact-wrap grupal">
  <div class="header-flex" style="margin-bottom:10px;">
    <img class="logo" src="{{ $logoUrl ?? asset('img/logo.png') }}" alt="Logo" />
    <div class="center">
      <div class="exact-header" style="font-weight:700;">SERVICE  AND  TRADING  BUSINESS S.A DE C.V</div>
      <div class="exact-header" style="font-weight:700;">PROCESO DE SALUD Y SEGURIDAD/ PROCESS OCCUPATIONAL HEALTH AND SAFETY</div>
      <div class="exact-header" style="margin-top:6px; font-weight:700;">REGISTRO DE ASIGNACION DE EQUIPO DE PROTECCION POR AREA/ REGISTRATION OF ALLOCATION OF PROTECTION EQUIPMENT BY AREA</div>
    </div>
    <div class="spacer" aria-hidden="true"></div>
  </div>

  <div class="row"><div class="label">1. Datos Generales:</div></div>
  <div class="row">
    <div style="flex:1; display:flex; gap:8px;">
      <div class="label">Área de Trabajo:</div>
      <div class="field">{{ mb_strtoupper($puesto,'UTF-8') }}</div>
    </div>
  </div>
  <div class="row">
    <div style="flex:1; display:flex; gap:8px;">
      <div class="label">No. de Empleados por área:</div>
      <div class="field">{{ $grupo->count() }}</div>
    </div>
    <div style="flex:1; display:flex; gap:8px;">
      <div class="label" style="text-align:right;">Departamento:</div>
      <div class="field" style="flex:1;">{{ mb_strtoupper($departamentoGrupo, 'UTF-8') }}</div>
    </div>
  </div>

  <table class="table-grupal">
    <colgroup>
      <col style="width:3%"><col style="width:25%"><col style="width:10%">
      <col style="width:8%"><col style="width:15%"><col style="width:25%"><col style="width:14%">
    </colgroup>
    <thead>
      <tr>
        <th>No.</th>
        <th>Equipo de Protección Personal Entregado</th>
        <th>Tipo de Protección</th>
        <th>Cantidad</th>
        <th>Fecha de Entrega</th>
        <th>Nombre del Empleado</th>
        <th>Firma del Empleado</th>
      </tr>
    </thead>
    <tbody>
      @for($i=0; $i < $perPage; $i++)
        @php  $fila = $chunk->values()->get($i); $num = ($pageIndex * $perPage) + $i + 1; @endphp 
        <tr>
          <td style="text-align:center;">{{ $num }}</td>
          <td style="text-align:center;">{{ $fila->epp_lista ?? '' }}</td>
          <td style="text-align:center;">{{ $fila->tipo_lista ?? '' }}</td>
          <td style="text-align:center;">{{ $fila->cantidad ?? '' }}</td>
          <td style="text-align:center;">{{ $fila->fecha ?? '' }}</td>
          <td style="text-align:center;">{{ $fila->nombre_completo ?? '' }}</td>
          <td></td>
        </tr>
      @endfor
    </tbody>
  </table>

  <div class="row" style="margin-top:10px;">
    <div class="label">2. Asignación del Equipo de Protección Personal</div>
  </div>
  <div class="row" style="margin-top:-4px;">
    <div class="label">* Tipos de Protección:</div>
    <div class="field" style="border:none;">
      Cabeza / Oído / Ojos / Cara / Respiratoria / Manos y Muñeca / Pies / Cuerpo / Otros.
    </div>
  </div>

  <div class="firmas" style="margin-top:0px;">
    <div class="firma">Realizado Por</div>
    <div class="firma">Verificado Por</div>
  </div>

  <div class="exact-footer">
    <div class="left">
      1 Copia Archivo<br>
      1 Copia sistema
      <div class="version">1 VERSION 2016</div>
    </div>
    <div class="center">STB/SSO/R009</div>
  </div>
</section>
@endforeach
@endforeach
</div>

{{-- ===================== CARTUCHOS (A4 horizontal) ===================== --}}
@if(isset($cartuchos) && $cartuchos->count())
<section class="sheet exact-wrap cartuchos">

  <div class="header-flex" style="margin-bottom:10px;">
    <img class="logo" src="{{ $logoUrl ?? asset('img/logo.png') }}" alt="Logo" />
    <div class="center">
      <div class="exact-header" style="font-weight:700;">SERVICE  AND  TRADING  BUSINESS S.A DE C.V</div>
      <div class="exact-header" style="font-weight:700;">PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL/ HEALTH AND OCCUPATIONAL SAFETY PROCESS</div>
      <div class="exact-header" style="margin-top:6px; font-weight:700;">
        CONTROL DE CAMBIO DE CARTUCHOS QUÍMICOS DE MASCARILLAS/ CONTROL CHANGE OF CHEMICAL CARTRIDGES OF MASKS
      </div>
    </div>
    <div class="spacer" aria-hidden="true"></div>
  </div>

  @php
    $filasPagina = 4;
    $minFilas = max($cartuchos->count(), $filasPagina);
  @endphp

  <div class="table-wrap">
    <table class="table-cartuchos" style="margin-top:10px;">
      <thead>
        <tr>
          <th>Tipo de Cartucho</th>
          <th>Fecha de Entrega</th>
          <th>Tiempo de Cartucho</th>
          <th>Fecha de Cambio</th>
          <th>Área</th>
          <th>Observaciones</th>
          <th>Nombre Empleado</th>
          <th>Firma de empleado</th>
        </tr>
      </thead>
      <tbody>
        @for($i=0; $i < $minFilas; $i++)
          @php $c = $cartuchos[$i] ?? null; @endphp
          <tr>
            <td>{{ $c->tipo ?? '' }}</td>
            <td style="text-align:center;">{{ $c->fecha_entrega ?? '' }}</td>
            <td style="text-align:center;">{{ $c->tiempo ?? '' }}</td>
            <td style="text-align:center;">{{ $c->fecha_cambio ?? '' }}</td>
            <td>{{ $c->area ?? '' }}</td>
            <td class="obs-cell">{{ $c->observaciones ?? '' }}</td>
            <td>{{ $c->empleado ?? '' }}</td>
            <td></td>
          </tr>
        @endfor
      </tbody>
    </table>
  </div>

  <div style="margin-top:100px;">
    Revisado por<span style="display:inline-block;border-bottom:1px solid #000;min-width:340px;margin-left:8px;">&nbsp;</span>
  </div>

  <div class="exact-footer footer-cartuchos">
    <div class="left">
      1 Copia Archivo<br>
      1 Copia Sistema
      <div class="version">2 VERSION 2019</div>
    </div>
    <div class="center">STB/SSO/R043</div>
  </div>
</section>
@endif

{{-- ========= Modal Observaciones ========= --}}
<div id="comentariosModal" class="modal-backdrop">
  <div class="modal">
    <div class="modal-header">
      <h3>Agregar comentarios (Observaciones)</h3>
      <button class="close" id="closeComentarios" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
      <p class="muted" style="margin-top:-6px;">Solo aparecen filas con datos.</p>
      <div id="comentariosForm" class="comentarios-form"></div>
    </div>
    <div class="modal-footer">
      <button class="btn secondary" id="btnCancelarComentarios">Cancelar</button>
      <button class="btn secondary" id="btnAplicarComentarios">Aplicar</button>
      <button class="btn" id="btnAplicarImprimir">Aplicar e imprimir</button>
    </div>
  </div>
</div>

<script>
(function(){
  const hasCartuchos = !!document.querySelector('.sheet.cartuchos .table-cartuchos');
  const btnOpen = document.getElementById('btnComentarios');

  // Si no hay cartuchos, auto-imprime como siempre
  if (!hasCartuchos) { setTimeout(function(){ window.print(); }, 350); }

  if (!hasCartuchos || !btnOpen) return;

  const modal      = document.getElementById('comentariosModal');
  const formWrap   = document.getElementById('comentariosForm');
  const btnClose   = document.getElementById('closeComentarios');
  const btnCancel  = document.getElementById('btnCancelarComentarios');
  const btnApply   = document.getElementById('btnAplicarComentarios');
  const btnApplyPr = document.getElementById('btnAplicarImprimir');

  function openModal(){
    const rows = document.querySelectorAll('.sheet.cartuchos .table-cartuchos tbody tr');
    formWrap.innerHTML = '';
    rows.forEach((tr, idx) => {
      const tipo     = (tr.cells[0]?.innerText || '').trim();
      const fEnt     = (tr.cells[1]?.innerText || '').trim();
      const tiempo   = (tr.cells[2]?.innerText || '').trim();
      const fCamb    = (tr.cells[3]?.innerText || '').trim();
      const area     = (tr.cells[4]?.innerText || '').trim();
      const empleado = (tr.cells[6]?.innerText || '').trim();
      const currentObs = (tr.cells[5]?.innerText || '').trim();

      // Mostrar solo filas con algún dato real
      if (!tipo && !empleado && !fCamb) return;

      const row = document.createElement('div');
      row.className = 'row';
      row.innerHTML = `
        <div class="meta">
          <div><strong>Empleado:</strong> ${empleado || '—'}</div>
          <div><strong>Cartucho:</strong> ${tipo || '—'}</div>
          <div><strong>Área:</strong> ${area || '—'}</div>
          <div><strong>Últ. Entrega:</strong> ${fEnt || '—'} &nbsp; | &nbsp; <strong>Cambio:</strong> ${fCamb || '—'}</div>
          <div><strong>Tiempo:</strong> ${tiempo || '—'}</div>
        </div>
        <div>
          <textarea data-index="${idx}" placeholder="Escribe observaciones (opcional)">${currentObs}</textarea>
        </div>
      `;
      formWrap.appendChild(row);
    });

    modal.style.display = 'flex';
  }

  function closeModal(){ modal.style.display = 'none'; }

  function aplicarComentarios(printAfter){
    const rows = document.querySelectorAll('.sheet.cartuchos .table-cartuchos tbody tr');
    const areas = formWrap.querySelectorAll('textarea[data-index]');
    areas.forEach(t => {
      const i = parseInt(t.dataset.index, 10);
      const tr = rows[i];
      if (!tr) return;
      if (tr.cells[5]) tr.cells[5].innerText = t.value.trim();
    });
    closeModal();
    if (printAfter) window.print();
  }

  btnOpen.addEventListener('click', openModal);
  btnClose.addEventListener('click', closeModal);
  btnCancel.addEventListener('click', closeModal);
  btnApply.addEventListener('click', function(){ aplicarComentarios(false); });
  btnApplyPr.addEventListener('click', function(){ aplicarComentarios(true); });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && modal.style.display !== 'none') { closeModal(); }
  });
})();
</script>
</body>
</html>
