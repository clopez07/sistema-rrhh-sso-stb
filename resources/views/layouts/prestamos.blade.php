<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title></title>

        <!-- Tailwind CSS (vía CDN) -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Flowbite (JS) -->
        <script src="https://unpkg.com/flowbite@2.3.0/dist/flowbite.min.js"></script>
        
        <!-- BootsTrap -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    </head>
    <body>
        <nav class="bg-white border border-gray-400 rounded-md">
            <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl p-4">
                <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img src="{{ asset('img/logo.PNG') }}" alt="Logo" class="h-12 w-auto mr-3">
                    <span class="self-center text-2xl font-semibold whitespace-nowrap">Control de Prestamos</span>
                </a>
                <div class="flex items-center space-x-6 rtl:space-x-reverse">
                    <!-- Modal toggle -->
                    <button data-modal-target="select-modal" data-modal-toggle="select-modal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" type="button">
                    Cambiar de Módulo
                    </button>
                </div>

                <!-- Main modal -->
                <div id="select-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                    <div class="relative p-4 w-full max-w-md max-h-full">
                        <!-- Modal content -->
                        <div class="relative bg-white rounded-lg shadow-sm">
                            <!-- Modal header -->
                            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Cambiar de Módulo
                                </h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm h-8 w-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="select-modal">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                    </svg>
                                    <span class="sr-only">Close modal</span>
                                </button>
                            </div>
                            <!-- Modal body -->
                            <div class="p-4 md:p-5">
                                <ul class="space-y-4 mb-4">
                                    <li>
                                        <a href="/verificacion">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Análisis de Riesgos</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/epp">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Entrega de EPP</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/Capacitaciones">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Asistencia a Capacitaciones</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/matrizpuestos">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Organigrama</div>
                                            </div>
                                            <svg class="w-4 h-4 ms-3 rtl:rotate-180 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/></svg>
                                        </label>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        </nav>
        
        <nav class="bg-gray-50">
            <div class="max-w-screen-xl px-4 py-3 mx-auto">
                <div class="flex items-center">
                    <ul class="flex flex-row font-medium mt-0 space-x-8 rtl:space-x-reverse text-sm">
                        <li>
                            <a href="/empleadosprestamo" class="{{ request()->is('empleadosprestamo*') ? 'text-blue-600 underline font-semibold' : 'text-gray-900 hover:underline' }} text-gray-900 hover:underline">Resumen por Empleado</a>
                        </li>
                        <li>
                            <a href="/infoprestamo" class="{{ request()->is('infoprestamo*') ? 'text-blue-600 underline font-semibold' : 'text-gray-900 hover:underline' }} text-gray-900 hover:underline">Prestamos</a>
                        </li>
                        <li>
                            <a href="/cuotas" class="{{ request()->is('cuotas*') ? 'text-blue-600 underline font-semibold' : 'text-gray-900 hover:underline' }} text-gray-900 hover:underline">Historial / Cuotas</a>
                        </li>
                        <li>
                            <a href="/cuotas-especiales" class="{{ request()->is('cuotas-especiales*') ? 'text-blue-600 underline font-semibold' : 'text-gray-900 hover:underline' }} text-gray-900 hover:underline">Depósitos y cobros extraordinarios</a>
                        </li>
                        <li>
                            <a href="#" class="{{ request()->is('#*') ? 'text-blue-600 underline font-semibold' : 'text-gray-900 hover:underline' }} text-gray-900 hover:underline">Resumen Mensual</a>
                        </li>
                          <a id="open-reporte-prestamos"
                            class="{{ request()->is('#*') ? 'text-blue-600 underline font-semibold' : 'text-gray-900 hover:underline' }} text-gray-900 hover:underline">
                            Descargar Planilla de Deducciones de Prestamos ...
                        </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        {{-- Modal --}}
<div id="modal-reporte-prestamos"
     class="fixed inset-0 z-50 hidden">
  <!-- Fondo -->
  <div class="absolute inset-0 bg-black/30" data-close></div>

  <!-- Contenido -->
  <div class="relative mx-auto my-10 w-full max-w-md rounded-xl bg-white shadow-lg">
    <div class="p-5 border-b border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900">Rango de fechas</h3>
      <p class="text-sm text-gray-500">Selecciona el rango para la planilla de deducciones.</p>
    </div>

    <form id="form-reporte-prestamos" method="GET" action="{{ route('prestamos.reporte.excel') }}" class="p-5 space-y-4">
      <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Fecha Inicio</label>
        <input type="date" name="fecha_inicio" required
               value="{{ old('fecha_inicio', date('Y-m-01')) }}"
               class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 focus:ring-primary-600 focus:border-primary-600"/>
      </div>

      <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Fecha Final</label>
        <input type="date" name="fecha_final" required
               value="{{ old('fecha_final', date('Y-m-d')) }}"
               class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 focus:ring-primary-600 focus:border-primary-600"/>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" data-close
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50">
          Cancelar
        </button>
        <button type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
          Descargar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Iframe oculto para realizar descargas sin bloquear la vista -->
<iframe id="download-frame" name="download-frame" style="display:none;width:0;height:0;border:0;"></iframe>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.getElementById('open-reporte-prestamos');
  const modal = document.getElementById('modal-reporte-prestamos');
  const form = document.getElementById('form-reporte-prestamos');
  const dlFrame = document.getElementById('download-frame');

  function openModal(e) {
    if (e) e.preventDefault(); // evita navegar al href
    modal.classList.remove('hidden');
    modal.classList.add('flex', 'items-center', 'justify-center');
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex', 'items-center', 'justify-center');
  }

  openBtn?.addEventListener('click', openModal);

  // Cerrar al hacer click en fondo o en botones con data-close
  modal.querySelectorAll('[data-close]').forEach(el => {
    el.addEventListener('click', closeModal);
  });

  // Cerrar con ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // Interceptar envío del formulario para descargar vía iframe y recargar la página
  form?.addEventListener('submit', (e) => {
    e.preventDefault();

    const fd = new FormData(form);
    const params = new URLSearchParams(fd).toString();
    const url = form.getAttribute('action') + (params ? ('?' + params) : '');

    // Disparar descarga sin navegar, usando iframe oculto
    if (dlFrame) {
      dlFrame.src = url;
    } else {
      // Fallback: abrir en nueva pestaña
      window.open(url, '_blank');
    }

    // Cerrar modal y recargar después de un breve tiempo
    closeModal();
    setTimeout(() => { window.location.reload(); }, 1200);
  });
});
</script>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="max-w-screen-xl mx-auto p-6">
            @yield('content')
        </main>
    </body>
</html>
