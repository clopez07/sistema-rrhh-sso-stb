@extends('layouts.prestamos')

@section('title', 'Simulador de Préstamos')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-calculator"></i>
                        Simulador de Préstamos
                    </h4>
                </div>
                <div class="card-body">
                    <form id="simuladorForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_empleado" class="form-label">
                                        <i class="fas fa-user"></i>
                                        Empleado <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="id_empleado" name="id_empleado" required>
                                        <option value="">Seleccione un empleado...</option>
                                        @foreach($empleados as $empleado)
                                            <option value="{{ $empleado->id_empleado }}">
                                                {{ $empleado->nombre_completo }} - {{ $empleado->identidad }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="monto" class="form-label">
                                        <i class="fas fa-dollar-sign"></i>
                                        Monto del Préstamo <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="monto" name="monto" 
                                               step="0.01" min="1" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="tasa_interes" class="form-label">
                                        <i class="fas fa-percentage"></i>
                                        Tasa de Interés Anual <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="tasa_interes" name="tasa_interes" 
                                               step="0.01" min="0" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="plazo_meses" class="form-label">
                                        <i class="fas fa-calendar-alt"></i>
                                        Plazo en Meses <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="plazo_meses" name="plazo_meses" 
                                           min="1" max="360" required>
                                </div>

                                <div class="mb-3">
                                    <label for="fecha_primer_pago" class="form-label">
                                        <i class="fas fa-calendar"></i>
                                        Fecha del Primer Pago <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="fecha_primer_pago" 
                                           name="fecha_primer_pago" required>
                                </div>

                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">
                                        <i class="fas fa-comment"></i>
                                        Observaciones
                                    </label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" 
                                              rows="3" placeholder="Observaciones adicionales..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="button" id="btnSimular" class="btn btn-success me-2">
                                    <i class="fas fa-play"></i>
                                    Simular Préstamo
                                </button>
                                <button type="button" id="btnLimpiar" class="btn btn-secondary">
                                    <i class="fas fa-broom"></i>
                                    Limpiar Formulario
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Resultados -->
    <div class="row mt-4" id="resultadosPanel" style="display: none;">
        <!-- Resumen del Préstamo -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt"></i>
                        Resumen del Préstamo
                    </h5>
                </div>
                <div class="card-body">
                    <div id="resumenPrestamo"></div>
                    <hr>
                    <div class="d-grid">
                        <button type="button" id="btnRegistrar" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Registrar Préstamo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Amortización -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i>
                        Tabla de Amortización
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-striped table-hover" id="tablaAmortizacion">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>Cuota #</th>
                                    <th>Fecha</th>
                                    <th>Saldo Inicial</th>
                                    <th>Cuota</th>
                                    <th>Capital</th>
                                    <th>Intereses</th>
                                    <th>Saldo Restante</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaAmortizacion">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Registro de Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea registrar este préstamo?</p>
                <div id="datosConfirmacion"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarRegistro">
                    <i class="fas fa-check"></i>
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let datosSimulacion = null;

    // Configurar fecha por defecto (próximo mes)
    const fechaDefault = new Date();
    fechaDefault.setMonth(fechaDefault.getMonth() + 1);
    fechaDefault.setDate(1);
    $('#fecha_primer_pago').val(fechaDefault.toISOString().split('T')[0]);

    // Configurar Select2 para empleados
    $('#id_empleado').select2({
        placeholder: 'Seleccione un empleado...',
        allowClear: true,
        width: '100%'
    });

    // Botón Simular
    $('#btnSimular').click(function() {
        if (!validarFormulario()) return;
        
        const formData = new FormData($('#simuladorForm')[0]);
        
        $.ajax({
            url: '{{ route("prestamos.simular") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    datosSimulacion = response;
                    mostrarResultados(response);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al calcular la simulación'
                });
            }
        });
    });

    // Botón Limpiar
    $('#btnLimpiar').click(function() {
        $('#simuladorForm')[0].reset();
        $('#id_empleado').val('').trigger('change');
        $('#resultadosPanel').hide();
        datosSimulacion = null;
    });

    // Botón Registrar
    $('#btnRegistrar').click(function() {
        if (!datosSimulacion) return;
        
        const empleado = datosSimulacion.resumen.empleado;
        const resumen = datosSimulacion.resumen;
        
        $('#datosConfirmacion').html(`
            <strong>Empleado:</strong> ${empleado.nombre_completo}<br>
            <strong>Monto:</strong> $${numberFormat(resumen.monto_prestamo)}<br>
            <strong>Cuota Mensual:</strong> $${numberFormat(resumen.cuota_mensual)}<br>
            <strong>Plazo:</strong> ${resumen.plazo_meses} meses<br>
            <strong>Total a Pagar:</strong> $${numberFormat(resumen.total_a_pagar)}
        `);
        
        $('#modalConfirmacion').modal('show');
    });

    // Confirmar registro
    $('#btnConfirmarRegistro').click(function() {
        if (!datosSimulacion) return;
        
        const formData = new FormData($('#simuladorForm')[0]);
        
        $.ajax({
            url: '{{ route("prestamos.registrar") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#modalConfirmacion').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: `Préstamo #${response.numero_prestamo} registrado exitosamente`,
                        confirmButtonText: 'Ver Tabla de Amortización'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route("prestamos.amortizacion") }}';
                        }
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al registrar el préstamo'
                });
            }
        });
    });

    function validarFormulario() {
        const requiredFields = ['id_empleado', 'monto', 'tasa_interes', 'plazo_meses', 'fecha_primer_pago'];
        
        for (let field of requiredFields) {
            const value = $(`#${field}`).val();
            if (!value || value.trim() === '') {
                $(`#${field}`).focus();
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo Requerido',
                    text: 'Por favor complete todos los campos obligatorios'
                });
                return false;
            }
        }

        const monto = parseFloat($('#monto').val());
        if (monto <= 0) {
            $('#monto').focus();
            Swal.fire({
                icon: 'warning',
                title: 'Monto Inválido',
                text: 'El monto debe ser mayor a 0'
            });
            return false;
        }

        return true;
    }

    function mostrarResultados(response) {
        const resumen = response.resumen;
        const tabla = response.tabla_amortizacion;

        // Mostrar resumen
        $('#resumenPrestamo').html(`
            <div class="mb-2"><strong>Empleado:</strong><br>${resumen.empleado.nombre_completo}</div>
            <div class="mb-2"><strong>Monto:</strong> $${numberFormat(resumen.monto_prestamo)}</div>
            <div class="mb-2"><strong>Tasa:</strong> ${resumen.tasa_interes}% anual</div>
            <div class="mb-2"><strong>Plazo:</strong> ${resumen.plazo_meses} meses</div>
            <div class="mb-2"><strong>Cuota Mensual:</strong> <span class="text-primary fw-bold">$${numberFormat(resumen.cuota_mensual)}</span></div>
            <div class="mb-2"><strong>Total Intereses:</strong> $${numberFormat(resumen.total_intereses)}</div>
            <div class="mb-2"><strong>Total a Pagar:</strong> <span class="text-danger fw-bold">$${numberFormat(resumen.total_a_pagar)}</span></div>
        `);

        // Llenar tabla de amortización
        const tbody = $('#cuerpoTablaAmortizacion');
        tbody.empty();

        tabla.forEach(cuota => {
            tbody.append(`
                <tr>
                    <td>${cuota.numero_cuota}</td>
                    <td>${formatDate(cuota.fecha_pago)}</td>
                    <td>$${numberFormat(cuota.saldo_inicial)}</td>
                    <td>$${numberFormat(cuota.cuota_mensual)}</td>
                    <td>$${numberFormat(cuota.abono_capital)}</td>
                    <td>$${numberFormat(cuota.abono_intereses)}</td>
                    <td>$${numberFormat(cuota.saldo_restante)}</td>
                </tr>
            `);
        });

        $('#resultadosPanel').show();
        $('html, body').animate({ scrollTop: $('#resultadosPanel').offset().top }, 500);
    }

    function numberFormat(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
    }
});
</script>
@endpush