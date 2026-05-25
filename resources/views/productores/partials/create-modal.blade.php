<!-- Modal para registro rápido de productor -->
<div class="modal fade" id="modalNuevoProductor" tabindex="-1" aria-labelledby="modalNuevoProductorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="modalNuevoProductorLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Registrar Nuevo Productor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formNuevoProductorAjax">
                    @csrf
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 small">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Complete los datos básicos para registrar al productor y seleccionarlo automáticamente.
                    </div>
                    
                    <div id="ajaxErrors" class="alert alert-danger d-none rounded-4 border-0 shadow-sm">
                        <ul class="mb-0 small" id="ajaxErrorsList"></ul>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
                            <input type="text" name="apellido_paterno" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CURP</label>
                            <input type="text" name="curp" class="form-control" maxlength="18">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">UPP (Productor)</label>
                            <input type="text" name="upp" class="form-control" placeholder="Clave UPP Personal">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Domicilio Completo</label>
                            <input type="text" name="domicilio" class="form-control" placeholder="Calle, Número, Colonia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="municipio" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Localidad</label>
                            <input type="text" name="localidad" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado</label>
                            <input type="text" name="estado" class="form-control" value="Nayarit">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" id="btnGuardarProductorAjax">
                    <i class="bi bi-save me-1"></i> Guardar y Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnGuardar = document.getElementById('btnGuardarProductorAjax');
    const form = document.getElementById('formNuevoProductorAjax');
    const ajaxErrors = document.getElementById('ajaxErrors');
    const ajaxErrorsList = document.getElementById('ajaxErrorsList');

    btnGuardar.addEventListener('click', async function() {
        // Reset state
        ajaxErrors.classList.add('d-none');
        ajaxErrorsList.innerHTML = '';
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        try {
            const formData = new FormData(form);
            const response = await fetch('{{ route("productores.store.ajax") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                // Success! 
                // 1. Add to TomSelect
                const tomSelect = document.getElementById('productor_id').tomselect;
                tomSelect.addOption({
                    id: data.id,
                    nombre: data.nombre,
                    apellido_paterno: data.apellido_paterno,
                    upp: data.upp || '',
                    curp: data.curp || '',
                    localidad: data.localidad || '',
                    municipio: data.municipio || '',
                    text: `${data.nombre} ${data.apellido_paterno} ${data.apellido_materno || ''}`.trim()
                });
                
                // 2. Select it
                tomSelect.setValue(data.id);

                // 3. Close modal and reset
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoProductor'));
                modal.hide();
                form.reset();
                
                // Show success message if you want
                // alert('Productor registrado y seleccionado correctamente');
            } else {
                // Validation errors
                ajaxErrors.classList.remove('d-none');
                if (data.errors) {
                    Object.values(data.errors).forEach(errArray => {
                        errArray.forEach(err => {
                            const li = document.createElement('li');
                            li.textContent = err;
                            ajaxErrorsList.appendChild(li);
                        });
                    });
                } else {
                    const li = document.createElement('li');
                    li.textContent = data.message || 'Error al guardar el productor';
                    ajaxErrorsList.appendChild(li);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            ajaxErrors.classList.remove('d-none');
            const li = document.createElement('li');
            li.textContent = 'Error de conexión. Intente de nuevo.';
            ajaxErrorsList.appendChild(li);
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-save me-1"></i> Guardar y Seleccionar';
        }
    });
});
</script>
