<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir EPP</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header img {
            height: 60px; /* ajusta el tamaño del logo */
            margin-right: 15px;
        }
        .header .company-name {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        h2 { 
            text-align: center; 
            margin: 0;
            margin-bottom: 20px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 6px; 
            text-align: left; 
        }
        th { 
            background: #f2f2f2; 
        }
    </style>
</head>
<body onload="window.print()">

    <!-- ENCABEZADO -->
    <div class="header">
        <img src="{{ asset('img/logo.PNG') }}" alt="Logo Empresa">
        <div class="company-name">SERVICE AND TRAIDING BUSINESS</div>
    </div>

    <h2>Registro de Entrega de EPP</h2>
    <h3>Filtros Aplicados</h3>

    <!-- FILTROS APLICADOS -->
    @if(request()->anyFilled(['nombre','puesto','fecha','equipo']))
        <div style="margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; background: #f9f9f9; font-size: 13px;">
            <strong>Filtros aplicados:</strong>
            <ul style="margin: 5px 0; padding-left: 20px;">
                @if(request('nombre'))
                    <li><strong>Nombre:</strong> {{ request('nombre') }}</li>
                @endif
                @if(request('puesto'))
                    <li><strong>Puesto de Trabajo:</strong> {{ request('puesto') }}</li>
                @endif
                @if(request('fecha'))
                    <li><strong>Fecha:</strong> {{ request('fecha') }}</li>
                @endif
                @if(request('equipo'))
                    <li><strong>Equipo:</strong> {{ request('equipo') }}</li>
                @endif
            </ul>
        </div>
    @endif

        <table>
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Puesto de Trabajo</th>
                <th>Departamento</th>
                <th>EPP</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($equipos as $e)
                <tr>
                    <td>{{ $e->nombre_completo }}</td>
                    <td>{{ $e->puesto_trabajo }}</td>
                    <td>{{ $e->departamento }}</td>
                    <td>{{ $e->epp }}</td>
                    <td>{{ $e->fecha_entrega_epp }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>