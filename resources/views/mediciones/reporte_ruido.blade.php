@extends('layouts.mediciones')
@section('title','Resumen de mediciones de Ruido')

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
  .readonly-input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:.3rem .5rem; box-sizing:border-box;
                   background:#f8fafc; color:#334155; }
  .ok-badge{ font-size:.75rem; color:#16a34a; margin-left:.25rem; display:none; }
  .puesto-display{ display:block; border:1px solid #e5e7eb; border-radius:8px; padding:.3rem .5rem; background:#f8fafc; color:#334155; }
  .puesto-select{ width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:.35rem .5rem; box-sizing:border-box; }
</style>

<div class="sheet">

  <a class="btn" href="{{ route('mediciones.export.ruido', request()->only('id_localizacion','year')) }}"
     style="background:#64748b;border-color:#64748b;">
    Descargar Excel (Ruido)
  </a>
  <form method="GET" action="{{ route('mediciones.ruido.reporte') }}"
        style="margin:0 0 1rem 0; display:flex; align-items:flex-end; gap:.5rem; flex-wrap:wrap;">
    <div>
      <label for="year-ruido" style="font-weight:600;">Año:</label>
      <select id="year-ruido" name="year"
              style="margin-left:.35rem; padding:.35rem .5rem; border:1px solid #cbd5e1; border-radius:8px;">
        @foreach($years as $y)
          <option value="{{ $y }}" @selected((int)$y === (int)$year)>{{ $y }}</option>
        @endforeach
      </select>
    </div>
    @php $persistRuido = request()->except('year'); @endphp
    @foreach($persistRuido as $key => $val)
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

  <div style="text-align:center; margin-bottom:10px;">
    <div style="font-weight:700;">SERVICE AND TRADING BUSINESS S.A. DE C.V.</div>
    <div>PROCESO DE SALUD Y SEGURIDAD OCUPACIONAL / OCCUPATIONAL HEALTH AND SAFETY PROCESS</div>
    <div style="font-weight:700;">RESUMEN DE MEDICIONES DE RUIDO E ILUMINACION / SUMMARY OF NOISE AND LIGHTING MEASUREMENTS</div>
    @if($year)<div class="small">Año: {{ $year }}</div>@endif
  </div>

  @foreach($localizaciones as $loc)
    @php $rows = $grupos->get($loc->id_localizacion) ?? collect(); @endphp

    {{-- Franja con el nombre de la localización --}}
    <div class="section-title">{{ strtoupper($loc->localizacion) }}</div>

    @if($rows->count() > 0)
      <table class="tbl" style="margin-bottom:1.25rem;">
        <thead>
          <tr class="brand">
            <th style="width:50px;">No.</th>
            <th>ZONA MEDICION</th>
            <th>PUESTO DE TRABAJO</th>
            <th style="width:130px; text-align:center;" colspan="3">NIVEL DE RUIDO</th>
            <th style="width:80px; text-align:center;">NRR</th>
            <th style="width:80px; text-align:center;">NRE</th>
            <th style="width:120px; text-align:center;">LIMITES ACEPTABLES</th>
            <th style="width:220px;">ACCIONES CORRECTIVAS</th>
          </tr>
          <tr class="brand-dark">
            <th></th>
            <th></th>
            <th></th>
            <th style="text-align:center;">MAXIMO</th>
            <th style="text-align:center;">MINIMO</th>
            <th style="text-align:center;">PROMEDIO</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @php $n=1; $fmt = fn($v) => is_null($v) ? '' : number_format((float)$v, 2); @endphp
          @foreach($rows as $r)
            @php $lim = is_null($r->limites_aceptables) ? '85' : number_format((float)$r->limites_aceptables, 0); @endphp
            <tr data-row-id="{{ $r->id }}">
              <td style="text-align:center;">{{ $n++ }}</td>

              {{-- ZONA MEDICION (punto) --}}
              <td>{{ $r->punto_medicion }}</td>

              {{-- PUESTO DE TRABAJO --}}
              <td data-puesto-id="{{ $r->id_puesto_trabajo_matriz ?? '' }}">
                <span class="puesto-display">{{ $r->puesto }}</span>
              </td>

              {{-- Niveles --}}
              <td style="text-align:right;">{{ $fmt($r->nivel_maximo) }}</td>
              <td style="text-align:right;">{{ $fmt($r->nivel_minimo) }}</td>
              <td style="text-align:right;">{{ $fmt($r->nivel_promedio) }}</td>
              <td style="text-align:right;">{{ $fmt($r->nrr) }}</td>
              <td style="text-align:right;">{{ $fmt($r->nre) }}</td>
              <td style="text-align:right;">{{ $lim }}</td>

              {{-- Acciones correctivas + botones --}}
              <td>
                <div style="display:flex; align-items:center; gap:.25rem; flex-wrap:wrap;">
                  <input type="text"
                         class="accion-input"
                         value="{{ $r->acciones_correctivas }}"
                         placeholder="Agregar/editar acción…"/>
                  <span class="ok-badge">✓ guardado</span>

                  <button type="button"
                          class="edit-row-ruido"
                          title="Editar fila"
                          style="border:1px solid #2563eb;background:#dbeafe;color:#1d4ed8;padding:.25rem .5rem;border-radius:.375rem;cursor:pointer;">
                    Editar
                  </button>

                  <button type="button"
                          class="cancel-row-ruido"
                          title="Cancelar edición"
                          style="display:none;border:1px solid #64748b;background:#f1f5f9;color:#334155;padding:.25rem .5rem;border-radius:.375rem;cursor:pointer;">
                    Cancelar
                  </button>

                  <button type="button"
                          class="del-row-ruido"
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
      <div style="height:.5rem;"></div>
    @endif
  @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // CSRF seguro (usa <meta> si existe; si no, inserta el token desde Blade)
  const meta = document.querySelector('meta[name="csrf-token"]');
  const CSRF = (meta && meta.getAttribute('content')) || @json(csrf_token());

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
  const f2 = (v) => (v === '' || v === null || isNaN(v)) ? '' : (parseFloat(v).toFixed(2));
  const colMap = { punto:1, puesto:2, max:3, min:4, prom:5, nrr:6, nre:7, lim:8 };
  const numericCols = [colMap.max, colMap.min, colMap.prom, colMap.nrr, colMap.nre, colMap.lim];

  // Evita que Enter haga submit cuando editas acciones correctivas
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.classList.contains('accion-input')) {
      e.preventDefault();
      e.stopPropagation();
      e.target.blur(); // dispara guardado por blur
    }
  }, true);

  // ===== Guardar acciones correctivas (on blur) =====
  document.addEventListener('blur', async (e) => {
    if (!e.target.classList.contains('accion-input')) return;

    const tr   = e.target.closest('tr');
    const id   = tr?.getAttribute('data-row-id');
    const val  = e.target.value.trim();
    const okUi = tr.querySelector('.ok-badge');
    if (!id) return alert('Fila sin ID de medición.');

    const body = new URLSearchParams();
    body.append('_token', CSRF);
    body.append('id', id);
    body.append('acciones_correctivas', val);

    try {
      const resp = await fetch("{{ route('mediciones.ruido.accion') }}", {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body,
        credentials: 'same-origin'
      });
      if (!resp.ok) return alert('No se pudo guardar (HTTP ' + resp.status + ').');
      const data = await resp.json();
      if (data.ok && okUi) { okUi.style.display='inline'; setTimeout(()=> okUi.style.display='none', 1200); }
    } catch (err) {
      console.error(err); alert('Error de red al guardar.');
    }
  }, true);

  // ===== Eliminar fila =====
  document.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('del-row-ruido')) return;

    const tr  = e.target.closest('tr');
    const id  = tr?.getAttribute('data-row-id');
    if (!id) return alert('Fila sin ID de medición.');
    if (!confirm('¿Eliminar este registro de ruido?')) return;

    const body = new URLSearchParams();
    body.append('_token', CSRF);
    body.append('id', id);

    try {
      const resp = await fetch("{{ route('mediciones.ruido.delete') }}", {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body,
        credentials: 'same-origin'
      });
      if (!resp.ok) return alert('No se pudo eliminar (HTTP ' + resp.status + ').');
      const data = await resp.json();
      if (data.ok) {
        const tbody = tr.parentElement; tr.remove();
        if (tbody) { let i=1; tbody.querySelectorAll('tr').forEach(r => {
          const c = r.querySelector('td:first-child'); if (c) c.textContent = i++;
        });}
      } else alert('No se pudo eliminar.');
    } catch (err) {
      console.error(err); alert('Error de red al eliminar.');
    }
  });

  // ===== Edición de valores (incluye puesto) =====
  document.addEventListener('click', async (e) => {
    // Mapa de columnas: 0 No., 1 punto, 2 puesto, 3 max, 4 min, 5 prom, 6 nrr, 7 nre, 8 limite, 9 acciones

    if (e.target.classList.contains('edit-row-ruido')) {
      const btn = e.target;
      const tr  = btn.closest('tr');
      const id  = tr?.getAttribute('data-row-id');
      const cancelBtn = tr.querySelector('.cancel-row-ruido');
      const tds = tr.querySelectorAll('td');
      if (!id) return alert('Fila sin ID de medición.');

      const tdPunto = tds[colMap.punto];
      const tdPuesto = tds[colMap.puesto];

      if (btn.dataset.mode !== 'save') {
        btn.dataset.mode = 'save';
        btn.textContent  = 'Guardar';
        if (cancelBtn) cancelBtn.style.display = 'inline-block';

        tdPunto.dataset.orig = tdPunto.textContent.trim();
        tdPuesto.dataset.orig = tdPuesto.textContent.trim();
        tdPuesto.dataset.origId = tdPuesto.dataset.puestoId ?? '';
        numericCols.forEach(i => { tds[i].dataset.orig = tds[i].textContent.trim(); });

        tdPunto.innerHTML = `<input class="edit-input-r" data-k="punto_medicion" style="width:100%;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tdPunto.dataset.orig)}">`;
        const currentPuesto = tdPuesto.dataset.origId ?? '';
        tdPuesto.innerHTML = renderPuestoSelect(currentPuesto, tdPuesto.dataset.orig);
        tds[colMap.max].innerHTML   = `<input class="edit-input-r" data-k="nivel_maximo" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tds[colMap.max].dataset.orig ?? '')}">`;
        tds[colMap.min].innerHTML   = `<input class="edit-input-r" data-k="nivel_minimo" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tds[colMap.min].dataset.orig ?? '')}">`;
        tds[colMap.prom].innerHTML  = `<input class="edit-input-r" data-k="nivel_promedio" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tds[colMap.prom].dataset.orig ?? '')}">`;
        tds[colMap.nrr].innerHTML   = `<input class="edit-input-r" data-k="nrr" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tds[colMap.nrr].dataset.orig ?? '')}">`;
        tds[colMap.nre].innerHTML   = `<input class="edit-input-r" data-k="nre" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tds[colMap.nre].dataset.orig ?? '')}">`;
        tds[colMap.lim].innerHTML   = `<input class="edit-input-r" data-k="limites_aceptables" type="number" step="0.01" style="width:100%;text-align:right;padding:.25rem;border:1px solid #cbd5e1;border-radius:8px;" value="${esc(tds[colMap.lim].dataset.orig ?? '')}">`;
        return;
      }

      const getVal = (k) => {
        const el = tr.querySelector(`[data-k="${k}"]`);
        if (!el) return '';
        return (el.value ?? '').trim();
      };

      const payload = new URLSearchParams();
      payload.append('_token', CSRF);
      payload.append('id', id);
      payload.append('punto_medicion', getVal('punto_medicion'));
      payload.append('id_puesto_trabajo_matriz', getVal('id_puesto_trabajo_matriz'));
      payload.append('nivel_maximo', getVal('nivel_maximo'));
      payload.append('nivel_minimo', getVal('nivel_minimo'));
      payload.append('nivel_promedio', getVal('nivel_promedio'));
      payload.append('nrr', getVal('nrr'));
      payload.append('nre', getVal('nre'));
      payload.append('limites_aceptables', getVal('limites_aceptables'));

      try {
        const resp = await fetch("{{ route('mediciones.ruido.update') }}", {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
          body: payload,
          credentials: 'same-origin'
        });
        if (!resp.ok) return alert('No se pudo guardar (HTTP '+resp.status+').');
        const data = await resp.json();
        if (!data.ok) return alert('No se pudo guardar.');

        const puestoField = tr.querySelector('[data-k="id_puesto_trabajo_matriz"]');
        const newPuestoId = getVal('id_puesto_trabajo_matriz');
        const newPuestoLabel = selectedLabel(puestoField);

        tdPunto.textContent = getVal('punto_medicion');
        setPuestoDisplay(tdPuesto, newPuestoId, newPuestoLabel);
        tdPuesto.dataset.orig = newPuestoLabel;
        tdPuesto.dataset.origId = newPuestoId;
        tds[colMap.max].textContent   = f2(getVal('nivel_maximo'));
        tds[colMap.min].textContent   = f2(getVal('nivel_minimo'));
        tds[colMap.prom].textContent  = f2(getVal('nivel_promedio'));
        tds[colMap.nrr].textContent   = f2(getVal('nrr'));
        tds[colMap.nre].textContent   = f2(getVal('nre'));
        tds[colMap.lim].textContent   = getVal('limites_aceptables');

        btn.dataset.mode = '';
        btn.textContent  = 'Editar';
        if (cancelBtn) cancelBtn.style.display = 'none';
      } catch (err) {
        console.error(err); alert('Error de red al guardar.');
      }
    }

    if (e.target.classList.contains('cancel-row-ruido')) {
      const tr  = e.target.closest('tr');
      const tds = tr.querySelectorAll('td');
      const editBtn = tr.querySelector('.edit-row-ruido');
      const tdPunto = tds[colMap.punto];
      const tdPuesto = tds[colMap.puesto];

      tdPunto.textContent = tdPunto.dataset.orig ?? tdPunto.textContent;
      setPuestoDisplay(tdPuesto, tdPuesto.dataset.origId ?? '', tdPuesto.dataset.orig ?? '');
      numericCols.forEach(i => {
        const cell = tds[i];
        if (cell && cell.dataset.orig !== undefined) {
          cell.textContent = cell.dataset.orig;
        }
      });

      editBtn.dataset.mode = '';
      editBtn.textContent  = 'Editar';
      e.target.style.display = 'none';
    }
  });
});
</script>
@endsection
