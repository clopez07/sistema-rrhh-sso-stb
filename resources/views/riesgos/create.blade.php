<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>IDENTIFICACIÓN DE RIESGO POR PUESTO DE TRABAJO/IDENTIFICATION OF RISK BY WORK POSITION</title>
<style>
:root{--primary:#44B3E1;--line:#e5e7eb;--ink:#0f172a;--muted:#475569;--bg:#f8fafc;}
*{box-sizing:border-box}body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;background:var(--bg);color:var(--ink)}
.wrap{max-width:1050px;margin:24px auto;padding:0 16px}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 10px 20px rgba(2,6,23,.06);overflow:hidden}
.header{padding:16px 20px;border-bottom:1px solid var(--line)}
.header h1{margin:0;font-size:18px}
.header small{color:var(--muted)}
.section-title{background:var(--primary);color:#fff;padding:8px 14px;font-weight:700}
.grid{display:grid;gap:12px;padding:14px}
@media(min-width:860px){.grid.cols-2{grid-template-columns:repeat(2,1fr)}.grid.cols-3{grid-template-columns:repeat(3,1fr)}}
label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
input[type=text],input[type=number],input[type=date],textarea,select{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff}
textarea{min-height:68px}
.table{width:100%;border-collapse:separate;border-spacing:0;margin:0;border:1px solid var(--line);border-radius:12px;overflow:hidden}
.table th{background:var(--primary);color:#fff;border-right:1px solid rgba(255,255,255,.25);padding:8px 10px;text-align:left;font-size:12px}
.table th:last-child{border-right:0}
.table td{border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:6px 8px;font-size:13px;vertical-align:top;background:#fff}
.table td:last-child{border-right:0}
.scroll{overflow:auto;padding:12px}
.group-cap{background:#eef6fb;padding:6px 10px;font-weight:700;border-left:4px solid var(--primary)}
.actions{display:flex;gap:10px;justify-content:flex-end;padding:12px;border-top:1px solid var(--line)}
.btn{appearance:none;border:1px solid transparent;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
.btn.primary{background:var(--primary);color:#fff}.btn.ghost{background:#fff;border-color:var(--line)}
.alert{margin:12px;border:1px solid #22c55e;background:#ecfdf5;padding:10px 12px;border-radius:8px}
.errors{margin:12px;border:1px solid #ef4444;background:#fef2f2;padding:10px 12px;border-radius:8px}
.errors ul{margin:0;padding-left:18px}
@media print{.actions{display:none}.wrap{max-width:unset;margin:0;padding:0}.card{border:0;box-shadow:none;border-radius:0}}@page{size:A4;margin:10mm}
</style>
</head>
<body>
  <div class="wrap">
    <form class="card" action="{{ route('identificacion-riesgo.store') }}" method="post">
  @csrf
  <input type="hidden" name="id_puesto_trabajo_matriz" value="{{ request('ptm') }}">

      <div class="header">
        <div style="display:flex;gap:12px;align-items:center;justify-content:space-between">
          <div>
            <div style="font-weight:700">SERVICE AND TRADING BUSINESS S.A. DE C.V.</div>
            <div style="color:var(--muted);font-size:13px">PROCESO SALUD Y SEGURIDAD OCUPACIONAL/HEALTH AND OCCUPATIONAL SAFETY PROCESS</div>
          </div>
          <div style="font-weight:800">IDENTIFICACIÓN DE RIESGO POR PUESTO DE TRABAJO/IDENTIFICATION OF RISK BY WORK POSITION</div>
        </div>
      </div>

      @if (session('ok'))
        <div class="alert">{{ session('ok') }}</div>
      @endif
      @if ($errors->any())
        <div class="errors">
          <strong>Corrige lo siguiente:</strong>
          <ul>
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="section-title">DATOS GENERALES DEL PUESTO</div>
      <div class="grid cols-2">
        <div><label>Departamento</label><input type="text" name="Departamento" value="{{ old('Departamento') }}"/></div>
        <div><label>Puesto de Trabajo Analizado</label><input type="text" name="Puesto de Trabajo Analizado" value="{{ old('Puesto de Trabajo Analizado') }}"/></div>
        <div><label>N° de empleados por puesto de trabajo</label><input type="number" name="N° de empleados por puesto de trabajo" value="{{ old('N° de empleados por puesto de trabajo') }}"/></div>
        <div><label>Descripción General de la labor</label><textarea name="Descripción General de la labor">{{ old('Descripción General de la labor') }}</textarea></div>
        <div><label>ACTIVIDADES DIARIAS</label><textarea name="ACTIVIDADES DIARIAS">{{ old('ACTIVIDADES DIARIAS') }}</textarea></div>
      </div>

      <div class="section-title">ESFUERZO FISICO</div>
      <div class="scroll"><table class="table"><thead><tr><th>Tipos de esfuerzo</th><th>Descripción de Carga</th><th>Equipo de apoyo</th><th>Duración y distancia de carga</th><th>Frecuencia</th><th>EPP utilizado</th><th>Peso aproximado</th><th>Capacitación</th></tr></thead><tbody>
        @php $ef_filas = ['Cargar','Halar','Empujar','Sujetar']; @endphp
        @foreach($ef_filas as $i => $rotulo)
        <tr>
          <td><input type="text" readonly value="{{ $rotulo }}" style="background:#f8fafc"/></td>
          <td><input type="text" /></td>
          <td><input type="text" name="ef_equipo[]" value="{{ old('ef_equipo.'.($i*2)) }}"/></td>
          <td><input type="text" name="ef_duracion[]" value="{{ old('ef_duracion.'.$i) }}"/></td>
          <td><input type="text" name="ef_frecuencia[]" value="{{ old('ef_frecuencia.'.$i) }}"/></td>
          <td><input type="text" name="ef_equipo[]" value="{{ old('ef_equipo.'.($i*2+1)) }}"/></td>
          <td><input type="number" step="any" name="ef_peso[]" value="{{ old('ef_peso.'.$i) }}"/></td>
          <td><input type="text" name="ef_capacitacion[]" value="{{ old('ef_capacitacion.'.$i) }}"/></td>
        </tr>
        @endforeach
      </tbody></table></div>

      <div class="section-title">CONDICIONES DE INSTALACIONES</div>
      <div class="scroll"><table class="table"><thead><tr><th>Descripción del elemento</th><th>Adecuado</th><th>No adecuado</th><th>N/A</th><th>Observaciones</th></tr></thead><tbody>
        @php $instItems = [
          'Paredes, muros, losas y trabes',
          'Pisos',
          'Techos',
          'Puertas y Ventanas',
          'Escaleras y rampas',
          'Anaqueles y estantería',
        ]; @endphp
        @foreach($instItems as $i => $item)
        <tr>
          <td>{{ $item }}</td>
          <td style="text-align:center"><input type="radio" name="instalaciones[{{ $i }}]" value="Adecuado" {{ old('instalaciones.'.$i)=='Adecuado'?'checked':'' }} /></td>
          <td style="text-align:center"><input type="radio" name="instalaciones[{{ $i }}]" value="No adecuado" {{ old('instalaciones.'.$i)=='No adecuado'?'checked':'' }} /></td>
          <td style="text-align:center"><input type="radio" name="instalaciones[{{ $i }}]" value="N/A" {{ old('instalaciones.'.$i)=='N/A'?'checked':'' }} /></td>
          <td><input type="text" name="obs"/></td>
        </tr>
        @endforeach
      </tbody></table></div>

      <div class="section-title">MAQUINARIA, EQUIPO Y HERRAMIENTAS</div>
      <div class="scroll"><table class="table"><thead><tr><th>Descripción del elemento</th><th>Adecuado</th><th>No adecuado</th><th>N/A</th><th>Observaciones</th></tr></thead><tbody>
        @php $maqItems = [
          'Estado de Maquinaria y Equipo',
          'Se ejecuta mantenimiento preventivo',
          'Se ejecuta mantenimiento correctivo',
          'Estado resguardos y guardas',
          'Estado de herramientas',
          'Se realizan inspecciones de Herramientas',
          'Almacenamiento Correcto de Herramientas',
        ]; @endphp
        @foreach($maqItems as $i => $item)
        <tr>
          <td>{{ $item }}</td>
          <td style="text-align:center"><input type="radio" name="maquinaria[{{ $i }}]" value="Adecuado" {{ old('maquinaria.'.$i)=='Adecuado'?'checked':'' }} /></td>
          <td style="text-align:center"><input type="radio" name="maquinaria[{{ $i }}]" value="No adecuado" {{ old('maquinaria.'.$i)=='No adecuado'?'checked':'' }} /></td>
          <td style="text-align:center"><input type="radio" name="maquinaria[{{ $i }}]" value="N/A" {{ old('maquinaria.'.$i)=='N/A'?'checked':'' }} /></td>
          <td><input type="text" name="obs"/></td>
        </tr>
        @endforeach
      </tbody></table></div>

      <div class="section-title">EQUIPOS Y SERVICIOS DE EMERGENCIA</div>
      <div class="scroll"><table class="table"><thead><tr><th>Descripción de elemento</th><th>Adecuado</th><th>No adecuado</th><th>N/A</th><th>Observaciones</th></tr></thead><tbody>
        @php $emItems = [
          'Señalización rutas de evacuación y salidas de emergencia y punto reunión',
          'Ubicación de Extintores o mangueras de incendios',
          'Ubicación de Camillas y elementos de primeros auxilios',
          'Ubicación de Botiquín',
          'Realización de Simulacros',
          'Socialización Plan de evacuación',
          'Capacitación sobre actuación en caso de emergencia y uso de extintor.',
          'Ubicación de alarmas aviso emergencia',
          'Ubicación de alarmas de humo',
          'Ubicación de lámparas de emergencia',
        ]; @endphp
        @foreach($emItems as $i => $item)
        <tr>
          <td>{{ $item }}</td>
          <td style="text-align:center"><input type="radio" name="emergencia[{{ $i }}]" value="Adecuado" {{ old('emergencia.'.$i)=='Adecuado'?'checked':'' }} /></td>
          <td style="text-align:center"><input type="radio" name="emergencia[{{ $i }}]" value="No adecuado" {{ old('emergencia.'.$i)=='No adecuado'?'checked':'' }} /></td>
          <td style="text-align:center"><input type="radio" name="emergencia[{{ $i }}]" value="N/A" {{ old('emergencia.'.$i)=='N/A'?'checked':'' }} /></td>
          <td><input type="text" name="obs"/></td>
        </tr>
        @endforeach
      </tbody></table></div>

      <div class="section-title">TABLA DE IDENTIFICACIÓN DE RIESGO</div>
      <div class="scroll"><table class="table"><thead><tr><th>TIPO</th><th>PELIGRO</th><th>RIESGO</th><th>CONSECUENCIA</th><th>APLICA: SI</th><th>APLICA: NO</th><th>OBSERVACIONES</th></tr></thead><tbody>
        @php
        $riesgosTabla = [
          'MECANICO' => [
            ['Caída a desnivel','Caída al mismo nivel','Fractura /contusiones'],
            ['Trabajo en altura','Caída a distinto nivel','Muerte/Fractura/Contusiones'],
            ['Objetos suspendidos','Caída de Objetos','Muerte/Fractura/Contusiones'],
            ['Objetos en movimiento y fijos','Choque contra objetos','Traumatismo /fractura/contusiones'],
            ['Equipo, Herramientas u Objetos punzocortantes','Golpes o cortes','Heridas/cortes/amputaciones'],
            ['Proyección de fragmento o partículas','Impacto de fragmento sobre las personas','Golpes/ Fracturas/ Contusiones'],
            ['Maquinaria, Equipo o herramientas en movimiento','Atrapamiento por o entre objetos','Amputaciones, fracturas/heridas/contusiones'],
          ],
          'ELECTRICO' => [
            ['Alta o Media tensión (cargas eléctricas)','Contacto eléctrico alta/Media tensión','Quemaduras/Muerte'],
            ['Baja tensión (Cargas eléctricas)','Contacto eléctrico Baja tensión','Quemaduras/Muerte'],
            ['Electricidad estática','Descarga eléctrica estática-incendio, explosión','Quemaduras/Muerte'],
          ],
          'FUEGO Y EXPLOSION' => [
            ['Líquidos, gases o material combustibles o inflamables','Explosión /Incendio','Quemaduras/Muerte'],
            ['Cilindros alta presión','Explosión','Quemaduras/Muerte'],
          ],
          'QUIMICO' => [
            ['Partículas de polvo, humos, gases y vapores','Inhalación','Neumoconiosis'],
            ['Sustancias corrosivas','Ingestión/ contacto con la piel/ contacto con los ojos','Quemaduras/Muerte'],
            ['Sustancias toxicas','ingestión','Muerte'],
            ['Sustancias irritantes o alergizantes','Contacto en la piel/ Contacto con los ojos','Irritación'],
          ],
        ];
        @endphp

        @foreach($riesgosTabla as $tipo => $filas)
          @foreach($filas as $idx => $r)
          <tr>
            <td>{{ $loop->first ? $tipo : '' }}</td>
            <td>{{ $r[0] }}</td>
            <td>{{ $r[1] }}</td>
            <td>{{ $r[2] }}</td>
            <td style="text-align:center"><input type="radio" name="tir[{{ $tipo }}][{{ $idx }}][aplica]" value="SI" {{ old('tir.'.$tipo.'.'.$idx.'.aplica')=='SI'?'checked':'' }}></td>
            <td style="text-align:center"><input type="radio" name="tir[{{ $tipo }}][{{ $idx }}][aplica]" value="NO" {{ old('tir.'.$tipo.'.'.$idx.'.aplica')=='NO'?'checked':'' }}></td>
            <td><input type="text" name="obs"/></td>
          </tr>
          @endforeach
        @endforeach
      </tbody></table></div>

      <div class="actions"><button type="reset" class="btn ghost">Limpiar</button><button type="submit" class="btn primary">Guardar</button></div>
    </form>
  </div>
</body>
</html>
