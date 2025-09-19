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
                    <span class="self-center text-2xl font-semibold whitespace-nowrap">Análisis de Riesgos por Puesto de Trabajo</span>
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
                                        <a href="/empleadosprestamo">
                                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 hover:bg-gray-100">                           
                                            <div class="block">
                                                <div class="w-full text-lg font-semibold">Control de Prestamos</div>
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
                            <button id="datos-button" type="button" class="flex items-center justify-between w-full py-2 px-3 font-medium text-gray-900 border-b border-gray-100 md:w-auto hover:bg-gray-50 md:hover:bg-transparent md:border-0 md:hover:text-blue-600 md:p-0">Preparación
                            <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                            </button>
                        </li>
                         <li>
                            <button id="formatos-button" type="button" class="flex items-center justify-between w-full py-2 px-3 font-medium text-gray-900 border-b border-gray-100 md:w-auto hover:bg-gray-50 md:hover:bg-transparent md:border-0 md:hover:text-blue-600 md:p-0">Identificación de Riesgos 
                            <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                            </button>
                        </li>
                        <li>
                            <a href="#"
                                data-modal-target="modal-evaluacion-riesgos"
                                data-modal-toggle="modal-evaluacion-riesgos"
                                class="block p-3 rounded-lg hover:bg-gray-50" >
                                <div class="font-semibold">Evaluación de Riesgos</div>
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="block p-3 rounded-lg hover:bg-gray-50" onclick="window.location='{{ route('riesgos.verificacion.plan_accion') }}'">
                                <div class="font-semibold">Plan de Acción Control de Riesgos</div>
                            </a>
                        </li>
                         <li>
                            <button id="matriz-button" type="button" class="flex items-center justify-between w-full py-2 px-3 font-medium text-gray-900 border-b border-gray-100 md:w-auto hover:bg-gray-50 md:hover:bg-transparent md:border-0 md:hover:text-blue-600 md:p-0">Matrices/Resumen de Riesgos
                            <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                            </button>
                        </li>
                        <li>
                            <button id="notificacion-button" type="button" class="flex items-center justify-between w-full py-2 px-3 font-medium text-gray-900 border-b border-gray-100 md:w-auto hover:bg-gray-50 md:hover:bg-transparent md:border-0 md:hover:text-blue-600 md:p-0">Notificación de Riesgos
                            <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                            </button>
                        </li>
                        <li>
                            <a href="/verificacion"
                                class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Ver Información</div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- LISTA DESPLEGABLE PARA PUESTOS DE TRABAJO -->
            <div id="datos-dropdown" class="hidden mt-1 bg-white border-gray-200 shadow-xs border-y">
                <div class="grid max-w-screen-xl px-4 py-5 mx-auto text-gray-900 sm:grid-cols-2 md:grid-cols-3 md:px-6">
                    <ul aria-labelledby="datos-button">
                        <li>
                            <a href="/puestos" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Puestos de Trabajo (Análisis de Riesgos)</div>
                                <span class="text-sm text-gray-500">Agrega Puestos de Trabajo, departamentos, localizaciones, areas y otros.</span>
                            </a>
                        </li>
                        <li>
                            <a href="/quimicos" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Inventario de Quimicos</div>
                                <span class="text-sm text-gray-500">Agrega y asigna Químicos a cada puesto de Trabajo.</span>
                            </a>
                        </li>
                        </ul>
                        <ul aria-labelledby="datos-button">
                        <li>
                            <a href="/medidasriesgo" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Asignación de Medidas de prevención por riesgo</div>
                                <span class="text-sm text-gray-500">Agrega y asigna EPP, capacitaciones, señalizaciones y otras medidas a cada riesgo.</span>
                            </a>
                        </li>
                        </ul>
                </div>
            </div>

            <!-- LISTA DESPLEGABLE PARA EVALUACION DE RIESGOS -->
            <div id="formatos-dropdown" class="hidden mt-1 bg-white border-gray-200 shadow-xs border-y dark:bg-gray-800">
                <div class="grid max-w-screen-xl px-4 py-5 mx-auto text-gray-900 sm:grid-cols-2 md:grid-cols-3 md:px-6">
                    <ul aria-labelledby="formatos-button">
                        <li>
                            <a href="#" class="block p-3 rounded-lg hover:bg-gray-50" data-modal-target="modal-identificacion-riesgos" data-modal-toggle="modal-identificacion-riesgos">
                                <div class="font-semibold">Formato Identificación de Riesgos</div>
                                <span class="text-sm text-gray-500">Descarga formato de Identificación de Riesgos por Puesto de Trabajo.</span>
                            </a>
                        </li>
                        <li>
                            <a href="/identificacion-riesgos" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Hoja de Campo de Identificación de Riesgos</div>
                                <span class="text-sm text-gray-500">Ingresa datos de Identificación de Riesgos por Puesto de Trabajo.</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- LISTA DESPLEGABLE PARA ESTANDARES -->
            <div id="matriz-dropdown" class="hidden mt-1 bg-white border-gray-200 shadow-xs border-y">
                <div class="grid max-w-screen-xl px-4 py-5 mx-auto text-gray-900 sm:grid-cols-2 md:grid-cols-3 md:px-6">
                    <ul aria-labelledby="matriz-button">
                        <li>
                            <a href="/matrizriesgos" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Matriz de Identificacion de Riesgos por puesto de trabajo</div>
                                <span class="text-sm text-gray-500">Permite registrar y organizar los diferentes riesgos asociados a cada puesto de trabajo, clasificándolos según su tipo y naturaleza. 
                                    Es la base para reconocer qué peligros existen en la empresa.</span>
                            </a>
                        </li>
                        <li>
                            <a href="/evaluacion" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Matriz de Evaluación de Riesgos por puesto de trabajo</div>
                                <span class="text-sm text-gray-500">Analizar los riesgos identificados, valorando probabilidad y consecuencia para determinar el nivel de riesgo (bajo, medio, alto, etc.). Su objetivo es priorizar cuáles requieren atención inmediata.</span>
                            </a>
                        </li>
                        <li>
                            <a href="/matriz-quimicos" class="block p-3 rounded-lg hover:bg-gray-50">
                                <div class="font-semibold">Matriz de Análisis de Riesgo Químico</div>
                                <span class="text-sm text-gray-500">Relaciona, para cada puesto, los agentes químicos usados o presentes.</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- LISTA DESPLEGABLE PARA ESTANDARES -->
            <div id="notificacion-dropdown" class="hidden mt-1 bg-white border-gray-200 shadow-xs border-y">
                <div class="grid max-w-screen-xl px-4 py-5 mx-auto text-gray-900 sm:grid-cols-2 md:grid-cols-3 md:px-6">
                    <ul aria-labelledby="notificacion-button">
                        <li>
                            <a href="#" class="block p-3 rounded-lg hover:bg-gray-50"
                               data-modal-target="modal-notificacion-empleado" data-modal-toggle="modal-notificacion-empleado">
                                <div class="font-semibold">Formato Notificación de Riesgos por Empleado</div>
                                <span class="text-sm text-gray-500">Descarga formato individual por empleado.</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="block p-3 rounded-lg hover:bg-gray-50"
                               data-modal-target="modal-notificacion-puesto" data-modal-toggle="modal-notificacion-puesto">
                                <div class="font-semibold">Formato Notificación de Riesgos por Puesto de Trabajo</div>
                                <span class="text-sm text-gray-500">Descarga listado para un puesto.</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <script>
            document.querySelectorAll("button[id$='-button']").forEach(button => {
                button.addEventListener("click", () => {
                    const dropdownId = button.id.replace('-button', '-dropdown');
                    const dropdown = document.getElementById(dropdownId);

                    // Oculta todos los dropdowns
                    document.querySelectorAll("div[id$='-dropdown']").forEach(div => {
                        if (div !== dropdown) div.classList.add('hidden');
                    });

                    // Alterna el actual
                    dropdown.classList.toggle('hidden');
                });
            });
        </script>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="max-w-screen-xl mx-auto p-6">
            @yield('content')
        </main>


        <!-- MODAL: Descargar Formato Evaluación de Riesgos -->
<div id="modal-evaluacion-riesgos" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50
            justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="relative p-4 w-full max-w-md max-h-full mx-auto">
    <div class="relative bg-white rounded-lg shadow-sm">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
          Descargar Formato — Evaluación de Riesgos
        </h3>
        <button type="button"
                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900
                       rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                data-modal-hide="modal-evaluacion-riesgos" aria-label="Cerrar">
          <svg class="w-3 h-3" viewBox="0 0 14 14" fill="none">
            <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <form id="form-export-evaluacion" action="{{ route('evaluacion.riesgos.export') }}" method="POST" class="p-4 md:p-5">
        @csrf
        <label for="ptm-select" class="block mb-2 text-sm font-medium text-gray-900">
          Selecciona el Puesto de Trabajo
        </label>
        <select id="ptm-select" name="ptm_id"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-1 focus:ring-blue-500"
                required>
          <option value="">Cargando puestos...</option>
        </select>

        <div class="mt-5 flex justify-end gap-2">
          <button type="button" data-modal-hide="modal-evaluacion-riesgos"
                  class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">
            Cancelar
          </button>
          <button type="submit"
                  class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
            Descargar Excel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalId = 'modal-evaluacion-riesgos';
  const select  = document.getElementById('ptm-select');
  const form    = document.getElementById('form-export-evaluacion');
  // Iframe oculto para descargar sin bloquear la vista
  let dlFrame = document.getElementById('download-frame-riesgos');
  if (!dlFrame) {
    dlFrame = document.createElement('iframe');
    dlFrame.id = 'download-frame-riesgos';
    dlFrame.name = 'download-frame-riesgos';
    dlFrame.style.display = 'none';
    document.body.appendChild(dlFrame);
  }
  let loaded    = false;

  function loadPuestos() {
    if (loaded) return;
    fetch('{{ route('evaluacion.riesgos.puestos') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r => r.json())
      .then(rows => {
        select.innerHTML = '<option value="">Seleccione...</option>';
        rows.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id_puesto_trabajo_matriz;
          opt.textContent = r.puesto_trabajo_matriz;
          select.appendChild(opt);
        });
        loaded = true;
      })
      .catch(() => {
        select.innerHTML = '<option value="">Error cargando puestos</option>';
      });
  }

  // Cargar opciones cuando el modal se va a abrir (Flowbite data-modal-toggle)
  document.querySelectorAll('[data-modal-target="'+modalId+'"]').forEach(trigger => {
    trigger.addEventListener('click', () => {
      loadPuestos();
      // ajustar acción del formulario si el trigger lo especifica
      const act = trigger.getAttribute('data-export-action');
      if (act) { form.setAttribute('action', act); }
    });
  });

  select.addEventListener('change', () => {
    // No auto-submit; el usuario confirmará con el botón de Descargar
  });

  // También cierra el modal al enviar con el botón
  form.addEventListener('submit', () => {
    document.getElementById(modalId).classList.add('hidden');
  });
  // Handler adicional: enviar al iframe, cerrar y recargar
  form.addEventListener('submit', (e) => {
    form.setAttribute('target', 'download-frame-riesgos');
    const closeBtn = document.querySelector(`[data-modal-hide="${modalId}"]`);
    if (closeBtn) closeBtn.click();
    setTimeout(() => {
      document.querySelectorAll('div[modal-backdrop], .modal-backdrop').forEach(el => el.remove());
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
    }, 100);
    setTimeout(() => { window.location.reload(); }, 1200);
  });
});
</script>

<!-- MODAL: Descargar Formato Identificación de Riesgos -->
<div id="modal-identificacion-riesgos" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="relative p-4 w-full max-w-md max-h-full mx-auto">
    <div class="relative bg-white rounded-lg shadow-sm">
      <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Descargar Formato — Identificación de Riesgos</h3>
        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="modal-identificacion-riesgos" aria-label="Cerrar">
          <svg class="w-3 h-3" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2"/></svg>
        </button>
      </div>
      <form id="form-export-identificacion" action="{{ route('identificacion.riesgos.export') }}" method="POST" class="p-4 md:p-5">
        @csrf
        <label for="ptm-select-ident" class="block mb-2 text-sm font-medium text-gray-900">Selecciona el Puesto de Trabajo</label>
        <select id="ptm-select-ident" name="ptm_id" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-1 focus:ring-blue-500" required>
          <option value="">Cargando puestos...</option>
        </select>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" data-modal-hide="modal-identificacion-riesgos" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Descargar Excel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalId2 = 'modal-identificacion-riesgos';
  const select2  = document.getElementById('ptm-select-ident');
  const form2    = document.getElementById('form-export-identificacion');
  let loaded2    = false;

  function loadPuestos2() {
    if (loaded2) return;
    fetch('{{ route('evaluacion.riesgos.puestos') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r => r.json())
      .then(rows => {
        select2.innerHTML = '<option value="">Seleccione...</option>';
        rows.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id_puesto_trabajo_matriz;
          opt.textContent = r.puesto_trabajo_matriz;
          select2.appendChild(opt);
        });
        loaded2 = true;
      })
      .catch(() => { select2.innerHTML = '<option value="">Error cargando puestos</option>'; });
  }

  document.querySelectorAll('[data-modal-target="'+modalId2+'"]').forEach(trigger => {
    trigger.addEventListener('click', loadPuestos2);
  });

  // Reutiliza iframe oculto global
  let dlFrame = document.getElementById('download-frame-riesgos');
  if (!dlFrame) {
    dlFrame = document.createElement('iframe');
    dlFrame.id = 'download-frame-riesgos';
    dlFrame.name = 'download-frame-riesgos';
    dlFrame.style.display = 'none';
    document.body.appendChild(dlFrame);
  }

  form2.addEventListener('submit', (e) => {
    form2.setAttribute('target', 'download-frame-riesgos');
    const closeBtn = document.querySelector(`[data-modal-hide="${modalId2}"]`);
    if (closeBtn) closeBtn.click();
    setTimeout(() => {
      document.querySelectorAll('div[modal-backdrop], .modal-backdrop').forEach(el => el.remove());
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
    }, 100);
    setTimeout(() => { window.location.reload(); }, 1200);
  });
});
</script>



<!-- MODAL: Notificación de Riesgos por Empleado -->
<div id="modal-notificacion-empleado" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="relative p-4 w-full max-w-md max-h-full mx-auto">
    <div class="relative bg-white rounded-lg shadow-sm">
      <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Descargar Formato — Notificación por Empleado</h3>
        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="modal-notificacion-empleado" aria-label="Cerrar">
          <svg class="w-3 h-3" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2"/></svg>
        </button>
      </div>
      <form id="form-export-notif-empleado" action="{{ route('notificacion.excel.empleado.export') }}" method="POST" class="p-4 md:p-5">
        @csrf
        <label for="empleado-select" class="block mb-2 text-sm font-medium text-gray-900">Selecciona el Empleado</label>
        <select id="empleado-select" name="id_empleado" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-1 focus:ring-blue-500" required>
          <option value="">Cargando empleados...</option>
        </select>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" data-modal-hide="modal-notificacion-empleado" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Descargar Excel</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalEmpId = 'modal-notificacion-empleado';
  const empSelect  = document.getElementById('empleado-select');
  const empForm    = document.getElementById('form-export-notif-empleado');
  let loadedEmp    = false;

  function loadEmpleados() {
    if (loadedEmp) return;
    fetch('{{ route('notificacion.excel.empleados') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r => r.json())
      .then(rows => {
        empSelect.innerHTML = '<option value="">Seleccione...</option>';
        rows.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id_empleado;
          const puesto = r.puesto ? ` — ${r.puesto}` : '';
          const depto  = r.departamento ? ` (${r.departamento})` : '';
          opt.textContent = `${r.nombre_completo}${puesto}${depto}`;
          empSelect.appendChild(opt);
        });
        loadedEmp = true;
      })
      .catch(() => { empSelect.innerHTML = '<option value="">Error cargando empleados</option>'; });
  }

  document.querySelectorAll('[data-modal-target="'+modalEmpId+'"]').forEach(trigger => {
    trigger.addEventListener('click', loadEmpleados);
  });

  // Reutiliza el iframe oculto global
  let dlFrame = document.getElementById('download-frame-riesgos');
  if (!dlFrame) {
    dlFrame = document.createElement('iframe');
    dlFrame.id = 'download-frame-riesgos';
    dlFrame.name = 'download-frame-riesgos';
    dlFrame.style.display = 'none';
    document.body.appendChild(dlFrame);
  }

  empForm.addEventListener('submit', (e) => {
    empForm.setAttribute('target', 'download-frame-riesgos');
    const closeBtn = document.querySelector(`[data-modal-hide="${modalEmpId}"]`);
    if (closeBtn) closeBtn.click();
    setTimeout(() => {
      document.querySelectorAll('div[modal-backdrop], .modal-backdrop').forEach(el => el.remove());
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
    }, 100);
  });
});
</script>

<!-- MODAL: Notificación de Riesgos por Puesto -->
<div id="modal-notificacion-puesto" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
  <div class="relative p-4 w-full max-w-md max-h-full mx-auto">
    <div class="relative bg-white rounded-lg shadow-sm">
      <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Descargar Formato — Notificación por Puesto</h3>
        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="modal-notificacion-puesto" aria-label="Cerrar">
          <svg class="w-3 h-3" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2"/></svg>
        </button>
      </div>
      <form id="form-export-notif-puesto" action="{{ route('notificacion.excel.export') }}" method="POST" class="p-4 md:p-5">
        @csrf
        <label for="ptm-select-notif" class="block mb-2 text-sm font-medium text-gray-900">Selecciona el Puesto de Trabajo</label>
        <select id="ptm-select-notif" name="ptm_id" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-1 focus:ring-blue-500" required>
          <option value="">Cargando puestos...</option>
        </select>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" data-modal-hide="modal-notificacion-puesto" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Descargar Excel</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalNPId = 'modal-notificacion-puesto';
  const selectNP  = document.getElementById('ptm-select-notif');
  const formNP    = document.getElementById('form-export-notif-puesto');
  let loadedNP    = false;

  function loadPuestosNotif() {
    if (loadedNP) return;
    fetch('{{ route('notificacion.excel.puestos') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r => r.json())
      .then(rows => {
        selectNP.innerHTML = '<option value="">Seleccione...</option>';
        rows.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id_puesto_trabajo_matriz;
          opt.textContent = r.puesto_trabajo_matriz;
          selectNP.appendChild(opt);
        });
        loadedNP = true;
      })
      .catch(() => { selectNP.innerHTML = '<option value="">Error cargando puestos</option>'; });
  }

  document.querySelectorAll('[data-modal-target="'+modalNPId+'"]').forEach(trigger => {
    trigger.addEventListener('click', loadPuestosNotif);
  });

  // Reutiliza iframe oculto global
  let dlFrame = document.getElementById('download-frame-riesgos');
  if (!dlFrame) {
    dlFrame = document.createElement('iframe');
    dlFrame.id = 'download-frame-riesgos';
    dlFrame.name = 'download-frame-riesgos';
    dlFrame.style.display = 'none';
    document.body.appendChild(dlFrame);
  }

  formNP.addEventListener('submit', (e) => {
    formNP.setAttribute('target', 'download-frame-riesgos');
    const closeBtn = document.querySelector(`[data-modal-hide="${modalNPId}"]`);
    if (closeBtn) closeBtn.click();
    setTimeout(() => {
      document.querySelectorAll('div[modal-backdrop], .modal-backdrop').forEach(el => el.remove());
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
    }, 100);
  });
});
</script>

    </body>
</html>
