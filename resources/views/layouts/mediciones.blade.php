<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Mediciones · Ruido e Iluminación')</title>

  <!-- Tailwind config: define brand BEFORE tailwind -->
  <script>
    window.tailwind = window.tailwind || {};
    tailwind.config = {
      theme: { extend: { colors: { brand: '#00B0F0' } } }
    }
  </script>
  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Flowbite (JS) -->
  <script src="https://unpkg.com/flowbite@2.3.0/dist/flowbite.min.js" defer></script>

  <!-- Bootstrap (opcional si lo usas en tablas/formularios) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

  <style>
    html, body { height: 100%; }
    body {
      font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji';
    }
    .scrollbar-thin::-webkit-scrollbar{width:8px}
    .scrollbar-thin::-webkit-scrollbar-thumb{background:#c7c7d1;border-radius:8px}

    /* Transiciones suaves */
    #app-sidebar { transition: transform 200ms ease; }
    .app-main { transition: margin 200ms ease; }

    /* Ocultar sidebar en escritorio (sm+) cuando html tiene .sidebar-hidden */
    @media (min-width: 640px) {
      .sidebar-hidden #app-sidebar { transform: translateX(-100%) !important; }
      .sidebar-hidden .app-main { margin-left: 0 !important; }
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-800">
  <!-- Topbar -->
  <header class="fixed top-0 z-50 w-full bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-screen-xl mx-auto flex items-center justify-between px-4 py-3">
      <div class="flex items-center gap-2">
        <!-- Hamburger (mobile) -->
        <button
          class="sm:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-300 hover:bg-slate-100"
          type="button"
          data-drawer-target="app-sidebar"
          data-drawer-toggle="app-sidebar"
          aria-controls="app-sidebar"
          aria-label="Abrir menú">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
          </svg>
        </button>

        <a href="/" class="flex items-center gap-3">
          <img src="{{ asset('img/logo.PNG') }}" alt="Logo" class="h-10 w-auto">
          <div class="leading-tight">
            <p class="text-sm text-slate-500">Sistema de Recursos Humanos</p>
            <p class="text-lg font-semibold">Resultados de Mediciones · Ruido e Iluminación</p>
          </div>
        </a>
      </div>

      <div class="flex items-center gap-2">
        <!-- Toggle ocultar/mostrar sidebar (escritorio) -->
        <button id="sidebarToggle"
                class="hidden sm:inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-300 hover:bg-slate-100"
                type="button" aria-label="Ocultar/Mostrar menú lateral" title="Ocultar/Mostrar menú">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 5.25v13.5m4.5-13.5h12v13.5h-12m4.5-6.75H8.25"/>
          </svg>
        </button>

        <button
          data-modal-target="select-modal"
          data-modal-toggle="select-modal"
          class="hidden sm:inline-block text-white bg-brand hover:brightness-95 focus:ring-4 focus:outline-none focus:ring-brand/30 font-medium rounded-lg text-sm px-4 py-2">
          Cambiar de Módulo
        </button>
      </div>
    </div>
  </header>

  <!-- Sidebar (drawer en móvil, fijo en escritorio) -->
  <aside id="app-sidebar"
         class="fixed top-0 left-0 z-40 w-72 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 bg-white border-r border-slate-200">
    <div class="h-full flex flex-col">
      <!-- Encabezado del menú -->
      <div class="px-4 py-3 border-b border-slate-200 bg-gradient-to-r from-brand/10 to-transparent">
        <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h18M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 11h6m-6 4h6"/>
          </svg>
          Mediciones · Menú
        </span>
      </div>

      <!-- NAV -->
      <nav class="flex-1 overflow-y-auto scrollbar-thin px-2 py-3">
        @php
          $activeLink = function($pattern){
            return request()->is($pattern)
              ? 'bg-brand/10 text-brand border border-brand/30'
              : 'text-slate-700 hover:bg-slate-100 border border-transparent';
          };
        @endphp
        <ul class="space-y-1">
          <li>
            <a href="/estandarilu" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('estandarilu*') }}">
              <!-- book -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5v14a2 2 0 002 2h12V5a2 2 0 00-2-2H6a2 2 0 00-2 2z"/>
              </svg>
              <span class="text-sm font-medium">Estándares</span>
            </a>
          </li>

          <li>
            <a href="/localizacion" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('localizacion*') }}">
              <!-- map pin -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11.5a3 3 0 100-6 3 3 0 000 6z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 10.5c0 7.5-7.5 10.5-7.5 10.5S4.5 18 4.5 10.5a7.5 7.5 0 1115 0z"/>
              </svg>
              <span class="text-sm font-medium">Localizaciones</span>
            </a>
          </li>

          <li>
            <a href="{{ route('mediciones.captura') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('mediciones/captura*') }}">
              <!-- clipboard -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 4h6M9 8h6M7 4h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
              </svg>
              <span class="text-sm font-medium">Hoja de Campo</span>
            </a>
          </li>

          <li class="mt-4 px-2 text-xs font-semibold tracking-wide text-slate-500">Resultados</li>

          <li>
            <a href="/mediciones/iluminacion/reporte" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('mediciones/iluminacion/reporte*') }}">
              <!-- sun -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.95 6.95l-1.41-1.41M6.46 6.46L5.05 5.05m12.02 0l-1.41 1.41M6.46 17.54l-1.41 1.41"/>
                <circle cx="12" cy="12" r="3" stroke-width="1.5"/>
              </svg>
              <span class="text-sm font-medium">Resultados · Iluminación</span>
            </a>
          </li>

          <li>
            <a href="/mediciones/ruido/reporte/accion" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('mediciones/ruido/reporte/accion*') }}">
              <!-- speaker -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7l-5 5 5 5V7zM13 9a4 4 0 010 6M16 7a7 7 0 010 10"/>
              </svg>
              <span class="text-sm font-medium">Resultados · Ruido</span>
            </a>
          </li>

          <li class="mt-4 px-2 text-xs font-semibold tracking-wide text-slate-500">Análisis</li>

          <li>
            <a href="/mediciones/timeline" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('mediciones/timeline*') }}">
              <!-- timeline -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h10M4 18h7"/>
              </svg>
              <span class="text-sm font-medium">Línea de Tiempo</span>
            </a>
          </li>

          <li>
            <a href="/mediciones/graficas" class="group flex items-center gap-3 rounded-xl px-3 py-2 {{ $activeLink('mediciones/graficas*') }}">
              <!-- chart -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 19V5m0 0l5 5m0 0l4-4m0 0l7 7"/>
              </svg>
              <span class="text-sm font-medium">Gráficas de Tendencias</span>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Botón Cambiar de Módulo (visible también en mobile) -->
      <div class="p-3 border-t border-slate-200">
        <button
          data-modal-target="select-modal"
          data-modal-toggle="select-modal"
          class="w-full inline-flex items-center justify-center gap-2 text-white bg-brand hover:brightness-95 focus:ring-4 focus:outline-none focus:ring-brand/30 font-medium rounded-lg text-sm px-4 py-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7 7-7M14 5l7 7-7 7"/>
          </svg>
          Cambiar de Módulo
        </button>
      </div>
    </div>
  </aside>

  <!-- Contenido principal -->
  <div id="app-main" class="app-main sm:ml-72 pt-20">
    <main class="max-w-screen-xl mx-auto p-6">
      @yield('content')
    </main>
  </div>

  <!-- MODAL: Cambiar de Módulo -->
  <div id="select-modal" tabindex="-1" aria-hidden="true"
       class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
      <div class="relative bg-white rounded-lg shadow-sm">
        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Cambiar de Módulo</h3>
          <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm h-8 w-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="select-modal" aria-label="Cerrar">
            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
          </button>
        </div>
        <div class="p-4 md:p-5">
          <ul class="space-y-3">
            <li>
              <a href="/verificacion" class="flex items-center justify-between w-full p-4 border border-slate-200 rounded-lg hover:bg-slate-50">
                <div class="font-semibold">Análisis de Riesgos</div>
                <svg class="w-4 h-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                </svg>
              </a>
            </li>
            <li>
              <a href="/Capacitaciones" class="flex items-center justify-between w-full p-4 border border-slate-200 rounded-lg hover:bg-slate-50">
                <div class="font-semibold">Asistencia a Capacitaciones</div>
                <svg class="w-4 h-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                </svg>
              </a>
            </li>
            <li>
              <a href="/empleadosprestamo" class="flex items-center justify-between w-full p-4 border border-slate-200 rounded-lg hover:bg-slate-50">
                <div class="font-semibold">Préstamos</div>
                <svg class="w-4 h-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                </svg>
              </a>
            </li>
            <li>
              <a href="/matrizpuestos" class="flex items-center justify-between w-full p-4 border border-slate-200 rounded-lg hover:bg-slate-50">
                <div class="font-semibold">Organigrama</div>
                <svg class="w-4 h-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                </svg>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- JS: toggle + persistencia -->
  <script>
    (function () {
      const root = document.documentElement;      // clase se aplica en <html>
      const KEY = 'sidebarHidden';                // misma preferencia entre módulos
      const btn = document.getElementById('sidebarToggle');

      const saved = localStorage.getItem(KEY);
      if (saved === '1') root.classList.add('sidebar-hidden');

      btn?.addEventListener('click', () => {
        const hidden = root.classList.toggle('sidebar-hidden');
        localStorage.setItem(KEY, hidden ? '1' : '0');
      });
    })();
  </script>
</body>
</html>
