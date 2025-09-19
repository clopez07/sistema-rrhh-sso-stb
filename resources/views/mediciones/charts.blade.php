@extends('layouts.mediciones')
@section('title','Gráficas anuales por punto/puesto')

@section('content')
<style>
  :root{ --lux:#00B0F0; --ruido:#ff7a59; }
  .wrap{max-width:1200px;margin:0 auto;padding:1rem;}
  .control{width:100%;padding:.5rem .75rem;border:1px solid #cbd5e1;border-radius:12px;background:#fff;box-sizing:border-box}
  .btn{padding:.55rem .9rem;border-radius:12px;border:1px solid #e5e7eb;background:#00B0F0;color:#fff;cursor:pointer;text-decoration:none}
  .btn.ghost{background:#fff;color:#0f172a;border-color:#e5e7eb}
  .muted{color:#64748b;font-size:.85rem}
  .panel{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:1rem;margin-top:.75rem}
  .chartbox{height:420px}
  .note{background:#f8fafc;border:1px dashed #cbd5e1;padding:.75rem;border-radius:10px;margin-top:.75rem}
  .row-actions{display:flex;gap:.5rem;align-items:center;justify-content:flex-end;margin:.35rem 0 1rem 0}
</style>

<div class="wrap">
  <h1 style="margin:0 0 .35rem 0;font-weight:800;">Gráficas anuales por punto/puesto</h1>

  {{-- Filtros --}}
  <form method="get" action="{{ route('mediciones.charts') }}"
        style="display:flex;gap:.5rem;align-items:end;flex-wrap:wrap;margin-bottom:.75rem">
    <div style="flex:1 1 340px">
      <label class="muted">Localización</label>
      <input list="dl-localizaciones" id="loc_txt" class="control" placeholder="Escribe y elige…">
      <datalist id="dl-localizaciones">
        @foreach($localizaciones as $lo)
          <option value="{{ $lo->localizacion }}" data-id="{{ $lo->id_localizacion }}"></option>
        @endforeach
      </datalist>
      <input type="hidden" name="id_localizacion" id="id_localizacion" value="{{ $locId ?: '' }}">
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;flex:0 0 auto">
      <div style="flex:0 0 150px">
        <label class="muted">Año desde</label>
        <input list="dl-years" name="year_from" id="year_from" class="control" placeholder="Inicial"
               value="{{ $yearFrom ?? '' }}">
      </div>
      <div style="flex:0 0 150px">
        <label class="muted">Año hasta</label>
        <input list="dl-years" name="year_to" id="year_to" class="control" placeholder="Final"
               value="{{ $yearTo ?? '' }}">
      </div>
      <datalist id="dl-years">
        @foreach($yearsAll as $y)
          <option value="{{ $y }}"></option>
        @endforeach
      </datalist>
    </div>
    <button class="btn" type="submit">Ver gráficas</button>
    @if($locId || $yearFrom || $yearTo)
      <a class="btn" href="{{ route('mediciones.charts') }}"
         style="background:#64748b;border-color:#64748b;">Limpiar</a>
    @endif
  </form>

  @if(!$locId)
    <div class="note">
      Selecciona una <strong>localización</strong> para ver las líneas de cada <em>Punto / Puesto</em>
      en Iluminación (lux) y Ruido (dBA).
    </div>
  @else
    <div class="panel">
      <div class="muted" style="margin-bottom:.35rem">Localización seleccionada:</div>
      <h2 style="margin:.1rem 0 1rem 0">{{ $locName }}</h2>

      {{-- ===== Iluminación ===== --}}
      <div class="row-actions">
        <button type="button" class="btn ghost" id="dlLux">Descargar Imagen</button>
      </div>
      <h3 style="margin:0 0 .5rem 0;">Iluminación (lux) — promedio anual por Punto/Puesto</h3>
      <div class="chartbox"><canvas id="chartLux"></canvas></div>

      {{-- ===== Ruido ===== --}}
      <div class="row-actions" style="margin-top:1.25rem">
        <button type="button" class="btn ghost" id="dlRuido">Descargar Imagen</button>
      </div>
      <h3 style="margin:0 0 .5rem 0;">Ruido (dBA) — promedio anual por Punto/Puesto</h3>
      <div class="chartbox"><canvas id="chartRuido"></canvas></div>

      <div class="note" style="margin-top:1rem">
        Consejo: si hay demasiadas series, usa el filtro por <strong>Año</strong> o pulsa los elementos de la
        leyenda para ocultar/mostrar líneas específicas.
      </div>
    </div>
  @endif
</div>

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  // datalist -> hidden id
  const input  = document.getElementById('loc_txt');
  const hidden = document.getElementById('id_localizacion');
  const opts   = Array.from(document.querySelectorAll('#dl-localizaciones option'));
  if (hidden && hidden.value) {
    const o = opts.find(x => String(x.dataset.id) === String(hidden.value));
    if (o) input.value = o.value;
  }
  function sync(){
    const v = (input.value||'').trim();
    const o = opts.find(x => x.value === v);
    hidden.value = o ? o.dataset.id : '';
  }
  if (input) { input.addEventListener('change', sync); input.addEventListener('blur', sync); }

  // === Gráficas ===
  @if($locId)
    const YEARS        = @json($years);
    const SERIES_LUX   = @json($seriesLux);
    const SERIES_RUIDO = @json($seriesRuido);
    const LOC_NAME     = @json($locName);

    // Paleta HSL legible
    function color(i, alpha=1){
      const h = (i*57) % 360;
      return `hsla(${h}, 70%, 45%, ${alpha})`;
    }
    function toDatasets(series){
      return series.map((s, i) => ({
        label: s.label,
        data: s.data,
        borderColor: color(i, 1),
        backgroundColor: color(i, .15),
        pointRadius: 2,
        tension: .25,
        spanGaps: true,
      }));
    }

    const baseOpts = {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        tooltip: { mode: 'index', intersect: false }
      },
      interaction: { mode: 'nearest', axis: 'x', intersect: false },
      scales: {
        x: { title: { display:true, text: 'Año' } },
        y: { type:'linear', position:'left' }
      }
    };

    // Iluminación
    const luxCtx = document.getElementById('chartLux').getContext('2d');
    const chartLux = new Chart(luxCtx, {
      type: 'line',
      data: { labels: YEARS, datasets: toDatasets(SERIES_LUX) },
      options: Object.assign({}, baseOpts, {
        scales: Object.assign({}, baseOpts.scales, {
          y: { type:'linear', position:'left', title:{ display:true, text:'Lux' } }
        })
      })
    });

    // Ruido
    const ruiCtx = document.getElementById('chartRuido').getContext('2d');
    const chartRuido = new Chart(ruiCtx, {
      type: 'line',
      data: { labels: YEARS, datasets: toDatasets(SERIES_RUIDO) },
      options: Object.assign({}, baseOpts, {
        scales: Object.assign({}, baseOpts.scales, {
          y: { type:'linear', position:'left', title:{ display:true, text:'dBA' } }
        })
      })
    });

    // === Descargar como PNG con fondo blanco ===
    function downloadChartPNG(canvas, filename){
      const exportCanvas = document.createElement('canvas');
      exportCanvas.width  = canvas.width;
      exportCanvas.height = canvas.height;
      const ctx = exportCanvas.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
      ctx.drawImage(canvas, 0, 0);

      const url = exportCanvas.toDataURL('image/png');
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
    }

    document.getElementById('dlLux').addEventListener('click', () => {
      const canvas = document.getElementById('chartLux');
      const fname = `Grafica_${(LOC_NAME||'Localizacion').replace(/[/\\?%*:|"<>]/g,'-')}_Lux.png`;
      downloadChartPNG(canvas, fname);
    });

    document.getElementById('dlRuido').addEventListener('click', () => {
      const canvas = document.getElementById('chartRuido');
      const fname = `Grafica_${(LOC_NAME||'Localizacion').replace(/[/\\?%*:|"<>]/g,'-')}_Ruido.png`;
      downloadChartPNG(canvas, fname);
    });
  @endif
})();
</script>
@endsection
