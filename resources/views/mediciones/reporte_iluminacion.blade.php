@extends('layouts.mediciones')
@section('title','Resumen de mediciones de Iluminación')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .sheet{ background:#fff; padding:1rem; }
  .tbl { width:100%; border-collapse:collapse; }
  .tbl th,.tbl td{ border:1px solid #333; padding:.45rem .5rem; font-size:.9rem; }
  .tbl th{ font-weight:700; }
  .brand { background:#00B0F0; color:#fff; }
  .brand-dark { background:#0088bc; color:#fff; }
  .section-title{
    background:#00B0F0; color:#fff; font-weight:700;
    text-transform:uppercase; padding:.45rem .5rem; border:1px solid #333;
  }
  .small { font-size:.8rem; color:#555; }
  .accion-input{ width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:.3rem .5rem; box-sizing:border-box; }
  .ok-badge{ font-size:.75rem; color:#16a34a; margin-left:.25rem; display:none; }
  .puesto-display{ display:block; border:1px solid #e5e7eb; border-radius:8px; padding:.3rem .5rem; background:#f8fafc; color:#334155; white-space:normal; }
  .puesto-select{ width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:.35rem .5rem; box-sizing:border-box; }
</style>

<div class="sheet">

<a class="btn" href="{{ route('mediciones.export.iluminacion', request()->only('id_localizacion','year')) }}">
  Descargar Excel (Iluminación)
</a>
<form method="GET" action="{{ route('mediciones.iluminacion.reporte') }}"
      style="margin:0 0 1rem 0; display:flex; align-items:flex-end; gap:.5rem; flex-wrap:wrap;">
    <div>
      <label for="year-ilum" style="font-weight:600;">Año:</label>
      <select id="year-ilum" name="year"
              style="margin-left:.35rem; padding:.35rem .5rem; border:1px solid #cbd5e1; border-radius:8px;">
        @foreach($years as $y)
          <option value="{{ $y }}" @selected((int)$y === (int)$year)>{{ $y }}</option>
        @endforeach
      </select>
    </div>
    @php $persistIlum = request()->except('year'); @endphp
    @foreach($persistIlum as $key => $val)
      @if(is_array($val))
        @foreach($val as $k => $v)
          <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
        @endforeach
      @else
        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
      @endif
    @endforeach
    <button type="submit" class="btn" style="padding:.35rem .75rem;">Aplicar</button>
  </form>

  {{-- Encabezado como tu plantilla --}}
  <div style="text-align:center; margin-bottom:10px;">
    <div style="font-weight:700;">SERVICE AND TRADING BUSINESS S.A. DE C.V.</div>
    <div>PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS</div>
    <div style="font-weight:700;">RESUMEN DE MEDICIONES DE RUIDO E ILUMINACION / SUMMARY OF NOISE AND LIGHTING MEASUREMENTS</div>
    @if($year)<div class="small">Año: {{ $year }}</div>@endif
  </div>

  @foreach($localizaciones as $loc)
@php
  $rows = $grupos->get($loc->id_localizacion) ?? collect();
@endphp

    {{-- Franja de localización --}}
    <div class="section-title">{{ strtoupper($loc->localizacion) }}</div>

    @if($rows->count() > 0)
      <table class="tbl" style="margin-bottom:1.25rem;">
        <thead>
          <tr class="brand">
            <th style="width:50px;">No.</th>
            <th>ZONA MEDICION</th>
            <th style="width:300px;">PUESTO DE TRABAJO</th>
            <th colspan="2" style="width:280px; text-align:center;">NIVEL ILUMINACION</th>
            <th style="width:220px;">ACCIONES CORRECTIVAS</th>
          </tr>
          <tr class="brand-dark">
            <th></th>
            <th></th>
            <th></th>
            <th style="text-align:center;">MEDIA</th>
            <th style="text-align:center;">LIMITES ACEPTABLES</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @php $n=1; @endphp
          @foreach($rows as $r)
            <tr data-row-id="{{ $r->id }}">
              <td style="text-align:center;">{{ $n++ }}</td>
              <td>{{ $r->punto_medicion }}</td>
              <td data-puesto-id="{{ $r->id_puesto_trabajo_matriz ?? '' }}">
                <span class="puesto-display">{{ $r->puesto }}</span>
              </td>

              <td style="text-align:right;">
              {{ $r->promedio === null ? '' : $r->promedio }}
            </td>
            <td style="text-align:right;">
              {{ $r->limites_aceptables === null ? '' : $r->limites_aceptables }}
            </td>
              {{-- Acciones correctivas (editable) --}}
              <td>
                <div style="display:flex; align-items:center; gap:.25rem; flex-wrap:wrap;">
                  <input type="text"
                        class="accion-input"
                        value="{{ $r->acciones_correctivas }}"
                        placeholder="Agregar/editar acción…" />
                  <span class="ok-badge">✓ guardado</span>

                  {{-- Editar / Guardar --}}
                  <button type="button"
                          class="edit-row-lux"
                          title="Editar fila"
                          style="border:1px solid #2563eb;background:#dbeafe;color:#1d4ed8;padding:.25rem .5rem;border-radius:.375rem;cursor:pointer;">
                    Editar
                  </button>

                  {{-- Cancelar (se muestra solo en modo edición) --}}
                  <button type="button"
                          class="cancel-row-lux"
                          title="Cancelar edición"
                          style="display:none;border:1px solid #64748b;background:#f1f5f9;color:#334155;padding:.25rem .5rem;border-radius:.375rem;cursor:pointer;">
                    Cancelar
                  </button>

                  {{-- Eliminar (el que ya tenías) --}}
                  <button type="button"
                          class="del-row"
                          title="Eliminar registro"
                          style="border:1px solid #e11d48;background:#fee2e2;color:#991b1b;padding:.25rem .5rem;border-radius:.375rem;cursor:pointer;">
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      {{-- Sin filas: solo franja --}}
      <div style="height:.5rem;"></div>
    @endif
  @endforeach
</div>

{{-- Guardado inline de acciones correctivas --}}
<script>
(function(){
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const puestosMap = Object.assign({}, @json($puestos->pluck('puesto_trabajo_matriz','id_puesto_trabajo_matriz')));
  const esc = (val = '') => String(val)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
  const toStr = (val) => (val === null || val === undefined) ? '' : String(val);
  const puestosEntries = Object.entries(puestosMap);
  const renderPuestoSelect = (selected, fallback = '') => {
    const current = toStr(selected);
    const opts = ['<option value="">-- Selecciona --</option>'];
    let hasCurrent = false;
    for (const [id,label] of puestosEntries) {
      const idStr = toStr(id);
      const sel = idStr === current ? ' selected' : '';
      if (sel) hasCurrent = true;
      opts.push(`<option value="${esc(idStr)}"${sel}>${esc(label)}</option>`);
    }
    if (current && !hasCurrent) {
      opts.splice(1, 0, `<option value="${esc(current)}" selected>${esc(fallback || current)}</option>`);
    }
    return `<select class="puesto-select" data-k="id_puesto_trabajo_matriz">${opts.join('')}</select>`;
  };
  const setPuestoDisplay = (td, id, fallback = '') => {
    const val = toStr(id);
    const label = Object.prototype.hasOwnProperty.call(puestosMap, val) ? puestosMap[val] : (fallback || '');
    td.dataset.puestoId = val;
    td.innerHTML = `<span class="puesto-display">${esc(label)}</span>`;
  };
  const selectedLabel = (select) => {
    if (!select) return '';
    const option = select.options[select.selectedIndex];
    return option ? option.text.trim() : '';
  };

  // Guardar al salir del input (blur) o con Enter
  document.addEventListener('keydown', function(e){
    if (e.key === 'Enter' && e.target.classList.contains('accion-input')) {
      e.preventDefault();
      e.target.blur();
    }
  });

  document.addEventListener('blur', async function(e){
    if (!e.target.classList.contains('accion-input')) return;

    const tr   = e.target.closest('tr');
    const id   = tr?.getAttribute('data-row-id');
    const val  = e.target.value.trim();
    const okUi = tr.querySelector('.ok-badge');

    if (!id) { alert('Fila sin ID de medición.'); return; }

    const body = new URLSearchParams();
    body.append('_token', token);
    body.append('id', id);
    body.append('acciones_correctivas', val);

    try {
      const resp = await fetch("{{ route('mediciones.iluminacion.accion') }}", {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body
      });

      if (!resp.ok) {
        const textResp = await resp.text();
        console.error('Error HTTP', resp.status, textResp);
        alert('No se pudo guardar (HTTP ' + resp.status + ').');
        return;
      }

      const data = await resp.json();
      if (data.ok) {
        if (okUi) { okUi.style.display = 'inline'; setTimeout(()=> okUi.style.display='none', 1200); }
      } else {
        alert('No se pudo guardar.');
      }
    } catch (err) {
      console.error(err);
      alert('Error de red al guardar.');
    }
  }, true);

  // Click en "Eliminar"
  document.addEventListener('click', async function(e){
    if (!e.target.classList.contains('del-row')) return;

    const btn = e.target;
    const tr  = btn.closest('tr');
    const id  = tr?.getAttribute('data-row-id');
    if (!id) { alert('Fila sin ID de medición.'); return; }

    if (!confirm('¿Eliminar este registro de iluminación? Esta acción no se puede deshacer.')) return;

    const body = new URLSearchParams();
    body.append('_token', token);
    body.append('id', id);

    try {
      const resp = await fetch("{{ route('mediciones.iluminacion.delete') }}", {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body
      });

      if (!resp.ok) {
        const textResp = await resp.text();
        console.error('Error HTTP', resp.status, textResp);
        alert('No se pudo eliminar (HTTP ' + resp.status + ').');
        return;
      }

      const data = await resp.json();
      if (data.ok) {
        const tbody = tr.parentElement;
        tr.remove();

        if (tbody) {
          let i = 1;
          tbody.querySelectorAll('tr').forEach(row => {
            const first = row.querySelector('td:first-child');
            if (first) first.textContent = i++;
          });
        }
      } else {
        alert('No se pudo eliminar.');
      }
    } catch (err) {
      console.error(err);
      alert('Error de red al eliminar.');
    }
  });

  // Toggle edición Iluminación
  document.addEventListener('click', async function(e){
    if (e.target.classList.contains('edit-row-lux')) {
      const btn = e.target;
      const tr  = btn.closest('tr');
      const id  = tr?.getAttribute('data-row-id');
      const cancelBtn = tr.querySelector('.cancel-row-lux');
      const tds = tr.querySelectorAll('td');
      const tdPunto = tds[1], tdPuesto = tds[2], tdProm = tds[3], tdLim = tds[4];

      if (btn.dataset.mode !== 'save') {
        btn.dataset.mode = 'save';
        btn.textContent  = 'Guardar';
        if (cancelBtn) cancelBtn.style.display = 'inline-block';

        tdPunto.dataset.orig = tdPunto.textContent.trim();
        tdPuesto.dataset.orig = tdPuesto.textContent.trim();
        tdPuesto.dataset.origId = tdPuesto.dataset.puestoId ?? '';
        tdProm.dataset.orig  = tdProm.textContent.trim();
        tdLim.dataset.orig   = tdLim.textContent.trim();

        tdPunto.innerHTML = `<input class="edit-input" data-k="punto_medicion" style="width:100%;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tdPunto.dataset.orig)}">`;
        const currentPuesto = tdPuesto.dataset.origId ?? '';
        tdPuesto.innerHTML = renderPuestoSelect(currentPuesto, tdPuesto.dataset.orig);
        tdProm.innerHTML  = `<input class="edit-input" data-k="promedio" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tdProm.dataset.orig)}">`;
        tdLim.innerHTML   = `<input class="edit-input" data-k="limites_aceptables" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tdLim.dataset.orig)}">`;
        return;
      }

      const getVal = (k) => {
        const el = tr.querySelector(`[data-k="${k}"]`);
        if (!el) return '';
        return (el.value ?? '').trim();
      };

      const payload = new URLSearchParams();
      payload.append('_token', token);
      payload.append('id', id);
      payload.append('punto_medicion', getVal('punto_medicion'));
      payload.append('id_puesto_trabajo_matriz', getVal('id_puesto_trabajo_matriz'));
      payload.append('promedio', getVal('promedio'));
      payload.append('limites_aceptables', getVal('limites_aceptables'));

      try {
        const resp = await fetch("{{ route('mediciones.iluminacion.update') }}", {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: payload,
          credentials: 'same-origin'
        });
        if (!resp.ok) { alert('No se pudo guardar (HTTP '+resp.status+').'); return; }
        const data = await resp.json();
        if (!data.ok) { alert('No se pudo guardar.'); return; }

        const puestoField = tr.querySelector('[data-k="id_puesto_trabajo_matriz"]');
        const newPuestoId = getVal('id_puesto_trabajo_matriz');
        const newPuestoLabel = selectedLabel(puestoField);

        tdPunto.textContent = getVal('punto_medicion');
        setPuestoDisplay(tdPuesto, newPuestoId, newPuestoLabel);
        tdPuesto.dataset.orig = newPuestoLabel;
        tdPuesto.dataset.origId = newPuestoId;
        tdProm.textContent  = getVal('promedio');
        tdLim.textContent   = getVal('limites_aceptables');

        btn.dataset.mode = '';
        btn.textContent  = 'Editar';
        if (cancelBtn) cancelBtn.style.display = 'none';
      } catch (err) {
        console.error(err); alert('Error de red al guardar.');
      }
    }

    if (e.target.classList.contains('cancel-row-lux')) {
      const tr = e.target.closest('tr');
      const tds = tr.querySelectorAll('td');
      const tdPunto = tds[1], tdPuesto = tds[2], tdProm = tds[3], tdLim = tds[4];
      const editBtn = tr.querySelector('.edit-row-lux');

      tdPunto.textContent = tdPunto.dataset.orig ?? tdPunto.textContent;
      setPuestoDisplay(tdPuesto, tdPuesto.dataset.origId ?? '', tdPuesto.dataset.orig ?? '');
      tdProm.textContent  = tdProm.dataset.orig  ?? tdProm.textContent;
      tdLim.textContent   = tdLim.dataset.orig   ?? tdLim.textContent;

      editBtn.dataset.mode = '';
      editBtn.textContent  = 'Editar';
      e.target.style.display = 'none';
    }
  });
})();
</script>

@endsection
