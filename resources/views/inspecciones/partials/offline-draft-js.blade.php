<script>
    (function() {
        const form = document.getElementById('formInspeccion');
        if (!form) return;

        const draftKey = 'sigdip:borrador:' + window.location.pathname + window.location.search;
        const originalSaveAsDraft = window.saveAsDraft;

        function serializeForm() {
            const data = {};
            const formData = new FormData(form);
            formData.forEach((value, key) => {
                if (Object.prototype.hasOwnProperty.call(data, key)) {
                    if (!Array.isArray(data[key])) data[key] = [data[key]];
                    data[key].push(value);
                } else {
                    data[key] = value;
                }
            });
            return data;
        }

        function payloadToFormData(payload) {
            const formData = new FormData();
            Object.entries(payload || {}).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach(item => formData.append(key, item));
                } else {
                    formData.append(key, value);
                }
            });
            return formData;
        }

        function writeLocalDraft(status, inyeccionVal) {
            const payload = serializeForm();
            payload.estado = 'borrador';
            payload.inyeccion_realizada = inyeccionVal || '0';

            const record = {
                status,
                payload,
                action: form.action,
                method: form.method || 'POST',
                savedAt: new Date().toISOString(),
                url: window.location.href,
            };

            localStorage.setItem(draftKey, JSON.stringify(record));
            return record;
        }

        function readLocalDraft() {
            try {
                return JSON.parse(localStorage.getItem(draftKey) || 'null');
            } catch (error) {
                return null;
            }
        }

        function setDraftButtonsDisabled(disabled) {
            document.querySelectorAll('button[onclick^="saveAsDraft"]').forEach(button => {
                button.disabled = disabled;
            });
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showDraftNotice(type, message) {
            let notice = document.getElementById('offline-draft-notice');
            if (!notice) {
                notice = document.createElement('div');
                notice.id = 'offline-draft-notice';
                notice.className = 'alert shadow-sm mb-3';
                form.parentElement.insertBefore(notice, form);
            }

            const alertClass = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
            notice.className = 'alert ' + alertClass + ' shadow-sm mb-3 d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between';
            notice.innerHTML = `
                <div><i class="bi bi-info-circle-fill me-1"></i>${escapeHtml(message)}</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-dark" id="retry-local-draft">Reintentar</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="restore-local-draft">Restaurar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="dismiss-local-draft">Ocultar</button>
                </div>
            `;

            const retryBtn = document.getElementById('retry-local-draft');
            const restoreBtn = document.getElementById('restore-local-draft');
            const dismissBtn = document.getElementById('dismiss-local-draft');
            if (retryBtn) retryBtn.onclick = () => retryPendingDraft();
            if (restoreBtn) restoreBtn.onclick = () => restorePendingDraft();
            if (dismissBtn) dismissBtn.onclick = () => notice.classList.add('d-none');
        }

        function ensureAnimalRows(payload) {
            const indexes = Object.keys(payload || {})
                .map(key => {
                    const match = key.match(/^animales\[(\d+)\]/);
                    return match ? parseInt(match[1], 10) : null;
                })
                .filter(index => Number.isInteger(index));

            if (!indexes.length || typeof window.addAnimal !== 'function') return;

            const maxIndex = Math.max(...indexes);
            const tbody = document.querySelector('#tablaAnimales tbody');
            if (!tbody) return;

            while (tbody.querySelectorAll('tr').length <= maxIndex) {
                window.addAnimal();
            }
        }

        function restorePayloadToForm(payload) {
            ensureAnimalRows(payload);

            Object.entries(payload || {}).forEach(([name, value]) => {
                const values = Array.isArray(value) ? value : [value];
                const fields = Array.from(form.elements).filter(element => element.name === name);
                fields.forEach(field => {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        field.checked = values.includes(field.value);
                    } else {
                        field.value = values[0] ?? '';
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });

            if (typeof window.actualizarCenso === 'function') window.actualizarCenso();
            if (typeof window.validateSections === 'function') window.validateSections();
        }

        function restorePendingDraft() {
            const record = readLocalDraft();
            if (!record || !record.payload) {
                showDraftNotice('warning', 'No encontré un respaldo local pendiente para restaurar.');
                return;
            }

            restorePayloadToForm(record.payload);
            showDraftNotice('warning', 'Restauré el respaldo local en pantalla. Revisa los datos y presiona "Reintentar" o "Guardar Borrador" cuando tengas conexión.');
        }

        async function sendDraft(record) {
            const response = await fetch(record.action, {
                method: 'POST',
                body: payloadToFormData(record.payload),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const contentType = response.headers.get('content-type') || '';
            const body = contentType.includes('application/json') ? await response.json() : {};

            if (!response.ok || body.success === false) {
                if (response.status === 422 && body.errors) {
                    const firstError = Object.values(body.errors).flat()[0];
                    throw new Error(firstError || 'Hay campos pendientes por revisar.');
                }
                throw new Error(body.message || 'No se pudo guardar el borrador en la base de datos.');
            }

            return body;
        }

        async function retryPendingDraft() {
            const record = readLocalDraft();
            if (!record || !record.payload) {
                showDraftNotice('warning', 'No encontré un respaldo local pendiente para este formulario.');
                return;
            }

            setDraftButtonsDisabled(true);
            try {
                const result = await sendDraft(record);
                writeLocalDraft('sincronizado', record.payload.inyeccion_realizada || '0');
                showDraftNotice('success', 'Borrador guardado en la base de datos. Ya no hay cambios pendientes.');
                setTimeout(() => {
                    window.location.href = result.redirect || '{{ route('inspecciones.index') }}';
                }, 700);
            } catch (error) {
                const pending = readLocalDraft();
                if (pending) {
                    pending.status = 'pendiente';
                    pending.lastError = error.message;
                    localStorage.setItem(draftKey, JSON.stringify(pending));
                }
                showDraftNotice('danger', 'Tienes datos guardados solo en este equipo. Cuando tengas conexión, usa "Reintentar". Detalle: ' + error.message);
            } finally {
                setDraftButtonsDisabled(false);
            }
        }

        window.saveAsDraft = async function(inyeccionVal = '0') {
            const estadoInput = document.getElementById('form_estado');
            const inyeccionInput = document.getElementById('inyeccion_realizada');
            if (estadoInput) estadoInput.value = 'borrador';
            if (inyeccionInput) inyeccionInput.value = inyeccionVal;

            const requiredElements = form.querySelectorAll('[required]');
            requiredElements.forEach(el => {
                if (el.name !== 'predio_id' && el.name !== 'folio') {
                    el.removeAttribute('required');
                }
            });

            let record;
            try {
                record = writeLocalDraft('pendiente', inyeccionVal);
            } catch (error) {
                alert('No se pudo crear el respaldo local. Hay datos sin guardar; revisa el almacenamiento del navegador antes de cerrar esta pantalla.');
                if (typeof originalSaveAsDraft === 'function') originalSaveAsDraft(inyeccionVal);
                return;
            }

            setDraftButtonsDisabled(true);
            showDraftNotice('warning', 'Respaldo local creado. Intentando guardar el borrador en la base de datos...');

            try {
                const result = await sendDraft(record);
                writeLocalDraft('sincronizado', inyeccionVal);
                showDraftNotice('success', 'Borrador guardado en local y en la base de datos.');
                setTimeout(() => {
                    window.location.href = result.redirect || '{{ route('inspecciones.index') }}';
                }, 500);
            } catch (error) {
                const pending = readLocalDraft();
                if (pending) {
                    pending.status = 'pendiente';
                    pending.lastError = error.message;
                    localStorage.setItem(draftKey, JSON.stringify(pending));
                }
                showDraftNotice('danger', 'No se pudo guardar en la base de datos. El borrador quedó guardado localmente en este equipo y está pendiente de sincronizar. Detalle: ' + error.message);
                alert('Tienes datos sin guardar en la base de datos. Se guardaron localmente; cuando tengas conexión o se resuelva el problema, presiona "Reintentar" o vuelve a Guardar Borrador.');
            } finally {
                setDraftButtonsDisabled(false);
            }
        };

        window.addEventListener('beforeunload', function(event) {
            const record = readLocalDraft();
            if (record && record.status === 'pendiente') {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const record = readLocalDraft();
            if (record && record.status === 'pendiente') {
                const savedAt = record.savedAt ? new Date(record.savedAt).toLocaleString() : 'recientemente';
                showDraftNotice('warning', 'Hay un borrador guardado localmente pendiente de sincronizar desde ' + savedAt + '.');
            }
        });
    })();
</script>
