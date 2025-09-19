<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>INICIO</title>
         <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

        <!-- Scripts -->
        <!-- Bootstrap CSS desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap JS y dependencias desde CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Si necesitas jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        
        <!-- Tailwind CSS (vía CDN) -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Flowbite (JS) -->
        <script src="https://unpkg.com/flowbite@2.3.0/dist/flowbite.min.js"></script>
        
        <!-- BootsTrap -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] flex p-4 sm:p-6 lg:p-10 items-center justify-center min-h-screen flex-col">
    
    <!-- Navbar -->
    <nav class="w-full bg-white border border-gray-300 rounded-md px-6 py-4 shadow-sm">
        <div class="container mx-auto flex items-center justify-between">
            <!-- Sección izquierda: Logo + nombre -->
            <div class="flex items-center">
                <img src="{{ asset('img/logo.PNG') }}" alt="Logo" class="h-12 w-auto mr-3">
                <span class="text-2xl font-semibold text-gray-800">Service and Trading Bussines</span>
            </div>

            <!-- Sección derecha: Cerrar sesión -->
            <div class="nav-item dropdown">
                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                    {{ Auth::user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="{{ route('logout') }}"
                        onclick="event.preventDefault();
                            document.getElementById('logout-form').submit();">
                                {{ __('Cerrar Sesion') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </nav>


    <div class="w-full text-center my-8">
        <h2 class="text-2xl font-semibold text-gray-900">
            Datos Generales
        </h2>
        <div class="mt-2 w-24 h-1 mx-auto bg-blue-600 rounded-full"></div>
    </div>    

    <!-- CARDS PARA CRUD DATOS GENERALES -->
    <div class="flex space-x-4">

        <!-- CARDS PARA EMPLEADOS -->
        <a href="/empleado" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow-sm md:flex-row md:max-w-xl hover:bg-gray-100">
            <img class="object-cover w-full rounded-t-lg h-96 md:h-auto md:w-48 md:rounded-none md:rounded-s-lg" src="{{ asset('img/empleados.JPG') }}" alt="">
            <div class="flex flex-col justify-between p-4 leading-normal">
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900">Lista de Empleados</h5>
                <p class="mb-3 font-normal text-gray-700">Gestiona el registro, edición y control de los empleados de la empresa, asignando puestos de Trabajo.</p>
            </div>
        </a>

        <!-- CARDS PARA PUESTOS DE TRABAJO -->
        <a href="/puestossistemas" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow-sm md:flex-row md:max-w-xl hover:bg-gray-100">
            <img class="object-cover w-full rounded-t-lg h-96 md:h-auto md:w-48 md:rounded-none md:rounded-s-lg" src="{{ asset('img/puestos.JPG') }}" alt="">
            <div class="flex flex-col justify-between p-4 leading-normal">
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900">Puestos de Trabajo Sistema</h5>
                <p class="mb-3 font-normal text-gray-700">Crea y gestiona los distintos puestos dentro de la empresa, define departamentos, ubicación y área del puesto.</p>
            </div>
        </a>
    </div>

    <div class="row g-4">

    <!-- Columna izquierda (ocupa 2/3): Título + 2 cards -->
    <div class="col-12 col-md-8">
        <div class="w-full text-center my-8">
        <h2 class="text-2xl font-semibold text-gray-900">Análisis de Riesgos</h2>
        <div class="mt-2 w-24 h-1 mx-auto bg-blue-600 rounded-full"></div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-4">
        <!-- Card: Análisis de Riesgos por Puesto de Trabajo (SIN CAMBIOS) -->
        <div class="col">
            <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm">
            <a href="/verificacion">
                <img class="rounded-t-lg" src="{{ asset('img/riesgos.JPG') }}" alt="" />
                <div class="p-5">
                <h5 class="mb-2 text-2xl font-bold tracking-tight">Análisis de Riesgos por Puesto de Trabajo</h5>
                <p class="mb-3 font-normal text-gray-700">
                    Permite identificar, registrar y clasificar los riesgos a los que están expuestos los empleados según su puesto de trabajo.
                </p>
                </div>
            </a>
            </div>
        </div>

        <!-- Card: Medición de Ruido e Iluminación (SIN CAMBIOS) -->
        <div class="col">
            <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm">
            <a href="/localizacion">
                <img class="rounded-t-lg" src="{{ asset('img/mediciones.JPG') }}" alt="" />
                <div class="p-5">
                <h5 class="mb-2 text-2xl font-bold tracking-tight">Medición de Ruido e Iluminación</h5>
                <p class="mb-3 font-normal text-gray-700">Registrar mediciones por año y generar un informe anual con totales y comparaciones por año.</p>
                </div>
            </a>
            </div>
        </div>
        </div>
    </div>

    <!-- Columna derecha (ocupa 1/3): Título + 1 card Capacitaciones -->
    <div class="col-12 col-md-4">
        <div class="w-full text-center my-8">
        <h2 class="text-2xl font-semibold text-gray-900">Capacitaciones</h2>
        <div class="mt-2 w-24 h-1 mx-auto bg-blue-600 rounded-full"></div>
        </div>

        <div class="row g-4">
        <!-- Card: Capacitaciones (SIN CAMBIOS) -->
        <div class="col">
            <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm">
            <a href="/Capacitaciones">
                <img class="rounded-t-lg" src="{{ asset('img/capacitaciones.PNG') }}" alt="" />
                <div class="p-5">
                <h5 class="mb-2 text-2xl font-bold tracking-tight">Registro de Asistencia a Capacitaciones</h5>
                <p class="mb-3 font-normal text-gray-700">
                    Registra la asistencia del personal a las diferentes capacitaciones impartidas. Asigna capacitaciones a los empleados.
                </p>
                </div>
            </a>
            </div>
        </div>
        </div>
    </div>
    </div>
    
    <!-- CONTENEDOR DE LAS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-4">

    <!-- Columna 1: Entrega de EPP -->
    <div>
        <div class="w-full text-center my-8">
        <h2 class="text-2xl font-semibold text-gray-900">Entrega de EPP</h2>
        <div class="mt-2 w-24 h-1 mx-auto bg-blue-600 rounded-full"></div>
        </div>
        <div class="flex-1 max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm">
        <a href="/epp">
            <img class="rounded-t-lg" src="{{ asset('img/epp.JPG') }}" alt="" />
            <div class="p-5">
            <h5 class="mb-2 text-2xl font-bold tracking-tight">Asignación de Equipo de Protección Personal</h5>
            <p class="mb-3 font-normal text-gray-700">
                Gestiona y documenta la entrega de EPP a los empleados. Permite llevar un historial detallado por trabajador.
            </p>
            </div>
        </a>
        </div>
    </div>

    <!-- Columna 2: Préstamos -->
    <div>
        <div class="w-full text-center my-8">
        <h2 class="text-2xl font-semibold text-gray-900">Préstamos</h2>
        <div class="mt-2 w-24 h-1 mx-auto bg-blue-600 rounded-full"></div>
        </div>
        <div class="flex-1 max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm">
        <a href="/empleadosprestamo">
            <img class="rounded-t-lg" src="{{ asset('img/prestamos.JPG') }}" alt="Préstamos" />
            <div class="p-5">
            <h5 class="mb-2 text-2xl font-bold tracking-tight">Control de Préstamos</h5>
            <p class="mb-3 font-normal text-gray-700">
                Administra los préstamos otorgados a los empleados. Registra el monto, cuotas, tasa de interés, fechas de pago y saldos pendientes.
            </p>
            </div>
        </a>
        </div>
    </div>

    <!-- Columna 3: Organigrama -->
    <div>
        <div class="w-full text-center my-8">
        <h2 class="text-2xl font-semibold text-gray-900">Organigrama</h2>
        <div class="mt-2 w-24 h-1 mx-auto bg-blue-600 rounded-full"></div>
        </div>
        <div class="flex-1 max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm">
        <a href="/matrizpuestos">
            <img class="rounded-t-lg" src="{{ asset('img/organigrama.JPG') }}" alt="Organigrama" />
            <div class="p-5">
            <h5 class="mb-2 text-2xl font-bold tracking-tight">Organigrama</h5>
            <p class="mb-3 font-normal text-gray-700">
                Representación gráfica de la estructura organizativa de la empresa y las relaciones de dependencia entre los distintos roles dentro del grupo.
            </p>
            </div>
        </a>
        </div>
    </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-wh/28A+4+RgPvYyqSRkFegJwCeMCn4m1BM5/1+Yl/0uKCU+5yr3phUZJf2o24RRA" crossorigin="anonymous"></script>
    <!-- En tu layout, justo antes de </body> -->
    <footer class="site-footer">
    SISTEMA DE RECURSOS HUMANOS/SALUD Y SEGURIDAD OCUPACIONAL · VERSIÓN 1 · 2025
    </footer>

<!-- En el <head> o tu CSS -->
<style>
  .site-footer{
    position: fixed; left: 0; right: 0; bottom: 0;
    background: #EDEDED; color: #000; text-align: center;
    padding: 8px 12px; font: 600 12px/1.2 Arial, Helvetica, sans-serif;
    box-shadow: 0 -1px 4px rgba(0,0,0,.08); letter-spacing:.2px;
  }
  /* para que no tape contenido si hay scroll largo */
  body{ padding-bottom: 40px; }
  /* impresión */
  @media print{
    body{ padding-bottom: 0; }
    .site-footer{ position: fixed; bottom: 0; }
  }
</style>

</body>
</html>
