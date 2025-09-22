@extends('layouts.mediciones')
@section('title','Comparativa anual por punto y puesto')

@section('content')
<style>
  :root{ --lux:#00B0F0; --ruido:#ff7a59; --cellw: 240px; }
  .wrap{max-width:1200px;margin:0 auto;padding:1rem;}
  .control{width:100%;padding:.5rem .75rem;border:1px solid #cbd5e1;border-radius:12px;background:#fff;box-sizing:border-box}
  .btn{padding:.55rem .9rem;border-radius:12px;border:1px solid #e5e7eb;background:#00B0F0;color:#fff;cursor:pointer;text-decoration:none}
  .muted{color:#64748b;font-size:.85rem}

  .scroller{overflow:auto;border:1px solid #e5e7eb;border-radius:12px;background:#fff;max-height:72vh;}
  .grid{display:grid;min-width: calc(240px + {{ count($years) }} * var(--cellw));}
  .hdr{position:sticky; top:0; z-index:7; background:#f8fafc; border-bottom:1px solid #e5e7eb;}
  .cell{padding:.6rem .7rem; border-left:1px solid #f1f5f9;}
  .cell:first-child{border-left:none;}
  .loc{position:sticky; left:0; z-index:6; background:#ffffff; min-width:240px; font-weight:700;}
  .loc.hdr{background:#f0f6fb;}
  .year-chip{display:flex;align-items:center;justify-content:center;font-weight:700;color:#334155}

  .cat{ grid-column:1 / -1; background:#e8f3ff; border-top:2px solid #cfe7ff; border-bottom:1px solid #e2e8f0; padding:.5rem .7rem; font-weight:800; color:#0f172a; }

  .card{border:1px solid #e5e7eb; border-radius:12px; padding:.55rem .6rem; background:#fff; min-height:112px; display:flex; flex-direction:column; gap:.5rem;}
  .box{border:1px solid #e5e7eb; border-radius:10px; padding:.45rem .55rem; display:flex; flex-direction:column; gap:.3rem;}
  .lux-box{ background:#eaf7ff; border-color:#cfefff; }
  .ruido-box{ background:#fff1eb; border-color:#ffd9c9; }
  .tags{display:flex; gap:.35rem; flex-wrap:wrap}
  .tag{font-size:.75rem; padding:.1rem .4rem; border-radius:999px; color:#fff}
  .tag.lux{background:var(--lux)} .tag.ruido{background:var(--ruido)}
  .kv{font-size:.9rem; display:flex; justify-content:space-between; gap:.5rem;}
  .k{color:#64748b}
  .v{font-weight:700}

  .sep{ grid-column: 1 / -1; height:1px; background:#e5e7eb; }

  @media (max-width:640px){
    :root{ --cellw: 200px; }
    .wrap{padding:.5rem;}
  }
</style>

<div class="wrap">
  <h1 style="margin:0 0 .35rem 0;font-weight:800;">Comparativa anual por punto y puesto</h1>

  {{-- Filtros --}}
  <form method="get" action="{{ route('mediciones.timeline') }}"
        style="display:flex;gap:.5rem;align-items:end;flex-wrap:wrap;margin-bottom:.75rem">
    <div style="flex:1 1 340px">
      <label class="muted">Filtrar por localización (opcional)</label>
      <input list="dl-localizaciones" id="loc_txt" class="control" placeholder="Escribe y elige…">
      <datalist id="dl-localizaciones">
        @foreach($localizaciones as $lo)
          <option value="{{ $lo->localizacion }}" data-id="{{ $lo->id_localizacion }}"></option>
        @endforeach
      </datalist>
      <input type="hidden" name="id_localizacion" id="id_localizacion" value="{{ $locFilter }}">
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
    <button class="btn" type="submit">Aplicar</button>

    <a class="btn" href="{{ route('mediciones.timeline.excel', request()->query()) }}"
      style="background:#10b981;border-color:#10b981;">
      Descargar Excel
    </a>

    @if($locFilter || $yearFrom || $yearTo)
      <a class="btn" href="{{ route('mediciones.timeline') }}"
        style="background:#64748b;border-color:#64748b;">Quitar filtros</a>
    @endif
  </form>

  <div class="scroller">
    <div class="grid" style="grid-template-columns: 240px repeat({{ count($years) }}, var(--cellw));">
      {{-- Encabezado --}}
      <div class="cell loc hdr">Punto / Puesto</div>
      @foreach($years as $y)
        <div class="cell hdr year-chip">{{ $y }}</div>
      @endforeach

      {{-- Grupos por Localización --}}
      @forelse($groups as $g)
        <div class="cat">Localización: {{ $g['loc'] ?? '—' }}</div>

        @foreach(($g['rows'] ?? []) as $row)
          @php
            $rowNombre = is_array($row) ? ($row['nombre'] ?? '') : ($row->nombre ?? '');
            $rowLimLux = is_array($row) ? ($row['lim_lux'] ?? null) : ($row->lim_lux ?? null);
            $cols      = is_array($row) ? ($row['columns'] ?? [])    : ($row->columns ?? []);
          @endphp

          <div class="cell loc">{{ $rowNombre }}</div>

          @foreach($cols as $col)
            @php
              $avgLux = is_array($col) ? ($col['avg_lux'] ?? null)   : ($col->avg_lux ?? null);
              $cntLux = is_array($col) ? ($col['cnt_lux'] ?? 0)      : ($col->cnt_lux ?? 0);
              $avgRui = is_array($col) ? ($col['avg_ruido'] ?? null) : ($col->avg_ruido ?? null);
              $cntRui = is_array($col) ? ($col['cnt_ruido'] ?? 0)    : ($col->cnt_ruido ?? 0);
            @endphp

            <div class="cell">
              <div class="card">
                @if(!is_null($avgLux))
                  <div class="box lux-box">
                    <div class="tags"><span class="tag lux">Iluminación</span></div>
                    <div class="kv"><span class="k">Media</span><span class="v">{{ number_format($avgLux,0) }} lux</span></div>
                    <div class="kv"><span class="k">Límite</span><span class="v">{{ $rowLimLux ? number_format($rowLimLux,0) : '—' }} lux</span></div>
                    <div class="kv"><span class="k">Puntos</span><span class="v">{{ $cntLux }}</span></div>
                  </div>
                @endif

                @if(!is_null($avgRui))
                  <div class="box ruido-box">
                    <div class="tags"><span class="tag ruido">Ruido</span></div>
                    <div class="kv"><span class="k">Media</span><span class="v">{{ number_format($avgRui,2) }} dBA</span></div>
                    <div class="kv"><span class="k">Límite</span><span class="v">85 dBA</span></div>
                    <div class="kv"><span class="k">Puntos</span><span class="v">{{ $cntRui }}</span></div>
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <div class="sep"></div>
        @endforeach

      @empty
        <div class="cell loc">—</div>
        @foreach($years as $y)
          <div class="cell"><div class="card" style="justify-content:center">Sin datos</div></div>
        @endforeach
      @endforelse
    </div>
  </div>
</div>

<script>
  // datalist -> hidden id
  (function(){
    const input  = document.getElementById('loc_txt');
    const hidden = document.getElementById('id_localizacion');
    const opts   = Array.from(document.querySelectorAll('#dl-localizaciones option'));
    if (hidden.value) {
      const o = opts.find(x => String(x.dataset.id) === String(hidden.value));
      if (o) input.value = o.value;
    }
    function sync(){
      const v = (input.value||'').trim();
      const o = opts.find(x => x.value === v);
      hidden.value = o ? o.dataset.id : '';
    }
    input.addEventListener('change', sync);
    input.addEventListener('blur', sync);
  })();
</script>
@endsection
