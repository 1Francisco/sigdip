<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando hato...</div>

      <template v-if="productor">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">Gestión de Hato</h2>
              <p class="text-secondary small mb-0" v-if="productor.clave">
                Clave: <code class="fw-bold">{{ productor.clave }}</code>
              </p>
              <p v-else class="text-muted small mb-0">Sin clave asignada</p>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">{{ vinculados.length }} miembros</span>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3">
            <i class="bi bi-people me-2"></i>Miembros del Hato
          </div>
          <div v-if="!vinculados.length" class="text-muted text-center py-3">
            <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
            No hay otros productores en este hato.
          </div>
          <div v-else class="list-group list-group-flush">
            <div v-for="vinc in vinculados" :key="vinc.id" class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
              <div class="text-start">
                <div class="fw-semibold">{{ vinc.nombre_completo }}</div>
                <div class="text-secondary small">UPP: {{ vinc.upp || '—' }} · Tel: {{ vinc.telefono || '—' }}</div>
              </div>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary rounded-pill" @click="$router.push(`/productores/${vinc.id}`)">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger rounded-pill" @click="desvincular(vinc)" title="Quitar del hato">
                  <i class="bi bi-person-x"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4" v-if="productor.clave">
          <div class="fw-bold text-dark mb-3">
            <i class="bi bi-link-45deg me-2"></i>Vincular existente
          </div>
          <p class="small text-muted mb-3">
            Asigna la clave <code>{{ productor.clave }}</code> a otro productor existente:
          </p>
          <div class="mb-3">
            <input
              v-model="searchTerm"
              type="text"
              class="form-control-custom mb-2"
              placeholder="Buscar productor por nombre o clave…"
              @input="onSearchInput"
            >
            <div v-if="isSearching" class="text-muted small">Buscando…</div>
            <div v-if="searchResults.length > 0" class="list-group mb-2">
              <button
                v-for="item in searchResults"
                :key="item.id"
                class="list-group-item list-group-item-action text-start d-flex justify-content-between align-items-center"
                @click="selectProductor(item)"
              >
                <div>
                  <strong>{{ item.nombre }} {{ item.apellido_paterno }} {{ item.apellido_materno }}</strong>
                  <div class="small text-muted">
                    <span v-if="item.clave" class="badge badge-clave rounded-pill me-1">{{ item.clave }}</span>
                    {{ item.upp || '—' }}
                  </div>
                </div>
                <span class="badge bg-primary rounded-pill" v-if="item.clave === productor.clave">Ya en el hato</span>
              </button>
            </div>
            <div v-if="searchTerm.length >= 2 && !isSearching && searchResults.length === 0" class="text-muted small">Sin resultados</div>
          </div>
          <button
            class="btn btn-outline-primary rounded-pill w-100 fw-bold"
            :disabled="!selectedProductor || saving"
            @click="vincularProductor"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-plus-circle me-1"></i>
            {{ saving ? 'Vinculando...' : 'Agregar al hato' }}
          </button>
          <div v-if="successMsg" class="alert alert-success border-0 shadow-sm rounded-4 mt-3 mb-0 small">{{ successMsg }}</div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4" v-if="productor.clave">
          <div class="fw-bold text-dark mb-3">
            <i class="bi bi-person-plus me-2"></i>Agregar nuevos
          </div>
          <p class="small text-muted mb-3">Registra nuevos productores en este hato:</p>
          <button class="btn btn-primary rounded-pill w-100 fw-bold" @click="$router.push(`/productores/nuevo?prefill_from_productor_id=${productor.id}`)">
            <i class="bi bi-people-fill me-1"></i> Agregar nuevo productor
          </button>
        </div>

        <div v-if="!productor.clave" class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="text-center text-muted py-3">
            <i class="bi bi-exclamation-triangle fs-2 d-block mb-2 text-warning"></i>
            <p>Este productor no tiene una clave asignada. Para gestionar el hato, primero asigne una clave desde el formulario de edición.</p>
            <button class="btn btn-primary rounded-pill" @click="$router.push(`/productores/editar/${productor.id}`)">
              <i class="bi bi-pencil me-1"></i> Editar Productor
            </button>
          </div>
        </div>

        <div class="d-flex gap-2 mb-5">
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push(`/productores/${productor.id}`)">
            <i class="bi bi-arrow-left me-1"></i> Volver
          </button>
        </div>
      </template>

      <div v-if="showHatoWarningModal" class="modal-overlay" @click.self="showHatoWarningModal = false">
        <div class="modal-card" style="max-width: 480px;">
          <div class="modal-header text-white border-0 py-3" style="background: #dc2626;">
            <span class="modal-title fw-bold d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle-fill"></i> Productor ya asignado
            </span>
            <button class="btn-close-modal border-0 bg-transparent text-white d-flex align-items-center justify-content-center" @click="showHatoWarningModal = false" style="font-size: 1.1rem; width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 50%;">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="modal-body p-4 text-start" v-if="warningProductor">
            <p class="mb-2">
              El productor <strong>{{ warningProductor.nombre }} {{ warningProductor.apellido_paterno }}{{ warningProductor.apellido_materno ? ' ' + warningProductor.apellido_materno : '' }}</strong>
              ya pertenece a otro hato (Clave: <code class="fw-bold">{{ warningProductor.clave }}</code>).
            </p>
            <p class="text-muted small mb-0">Primero debes quitarlo de ese hato antes de poder agregarlo al actual.</p>
          </div>
          <div class="modal-footer border-0 p-3 d-flex justify-content-center gap-3 bg-white">
            <button type="button" class="btn-cancel-custom flex-grow-1" @click="showHatoWarningModal = false">
              Cerrar
            </button>
            <button type="button" class="btn-submit-custom flex-grow-1" @click="irAGestionarHato">
              <i class="bi bi-gear me-1"></i> Gestionar Hato Actual
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'GestionarHatoView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      saving: false,
      errorMsg: '',
      successMsg: '',
      productorId: null,
      productor: null,
      searchTerm: '',
      searchResults: [],
      isSearching: false,
      selectedProductor: null,
      showHatoWarningModal: false,
      warningProductor: null,
    };
  },
  computed: {
    vinculados() {
      return this.productor?.vinculados || [];
    },
  },
  watch: {
    '$route.params.id': {
      handler: 'loadData',
      immediate: true,
    },
  },
  methods: {
    async loadData() {
      this.loading = true;
      this.errorMsg = '';
      this.productorId = this.$route.params.id;
      try {
        const res = await api.getProductor(this.productorId);
        this.productor = res.data || null;
      } catch (e) {
        const cachedProductores = await db.getProductores();
        const cached = cachedProductores.find(p => String(p.id) === String(this.productorId));
        if (cached) {
          this.productor = cached;
        } else {
          const predios = await db.getPredios();
          const predio = predios.find(p => p.productor && String(p.productor.id) === String(this.productorId));
          if (predio?.productor) {
            this.productor = predio.productor;
          } else {
            this.errorMsg = 'No se pudo cargar la información del productor.';
          }
        }
      } finally {
        this.loading = false;
      }
    },
    async onSearchInput() {
      const q = this.searchTerm.trim();
      if (q.length < 1) {
        this.searchResults = [];
        this.selectedProductor = null;
        return;
      }
      this.isSearching = true;
      this.selectedProductor = null;
      try {
        const res = await api.searchProductor(q);
        this.searchResults = (Array.isArray(res) ? res : []).filter(p => String(p.id) !== String(this.productorId) && p.clave !== this.productor.clave);
      } catch (e) {
        console.warn('Error searching productor:', e.message);
        this.searchResults = [];
      } finally {
        this.isSearching = false;
      }
    },
    selectProductor(item) {
      this.selectedProductor = item;
      this.searchTerm = `${item.nombre} ${item.apellido_paterno}${item.apellido_materno ? ' ' + item.apellido_materno : ''}`;
      this.searchResults = [];
    },
    async vincularProductor() {
      if (!this.selectedProductor) return;

      if (this.selectedProductor.clave && this.selectedProductor.clave !== this.productor.clave) {
        this.warningProductor = this.selectedProductor;
        this.showHatoWarningModal = true;
        return;
      }

      this.saving = true;
      this.successMsg = '';
      this.errorMsg = '';
      try {
        await api.vincularProductorAHato(this.productorId, this.selectedProductor.id);
        this.successMsg = 'Productor vinculado al hato exitosamente.';
        this.selectedProductor = null;
        this.searchTerm = '';
        await this.loadData();
      } catch (e) {
        this.errorMsg = e.message || 'Error al vincular productor al hato.';
      } finally {
        this.saving = false;
      }
    },
    irAGestionarHato() {
      if (!this.warningProductor) return;
      const id = this.warningProductor.id;
      this.showHatoWarningModal = false;
      this.warningProductor = null;
      this.selectedProductor = null;
      this.searchTerm = '';
      this.$router.push(`/productores/${id}/gestionar-hato`);
    },
    async desvincular(vinc) {
      if (!confirm(`¿Seguro que deseas quitar a ${vinc.nombre_completo} del hato?`)) return;
      this.errorMsg = '';
      this.successMsg = '';
      try {
        await api.desvincularProductorDeHato(vinc.id);
        this.successMsg = 'Productor quitado del hato con éxito.';
        await this.loadData();
      } catch (e) {
        this.errorMsg = e.message || 'Error al quitar al productor del hato.';
      }
    },
  },
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 16px;
}

.modal-card {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slide-up {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-close-modal {
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-close-modal:hover {
  background-color: rgba(255,255,255,0.25);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex-grow: 1;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-cancel-custom,
.btn-submit-custom {
  border: none;
  font-weight: 700;
  font-size: 1.05rem;
  padding: 14px 20px;
  min-height: 52px;
  border-radius: 50px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel-custom {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-cancel-custom:active {
  background: #e2e8f0;
}

.btn-submit-custom {
  background: #dc2626;
  color: #fff;
}

.btn-submit-custom:active {
  background: #b91c1c;
}

.btn-outline-primary,
.btn-outline-danger,
.btn-outline-secondary {
  background: #ffffff;
  border: 1px solid transparent;
  color: inherit;
  transition: all 0.2s ease;
}

.btn-outline-primary {
  border-color: #bfdbfe;
  color: #2563eb;
}

.btn-outline-primary:hover {
  background: #eff6ff;
  border-color: #2563eb;
}

.btn-outline-danger {
  border-color: #fecaca;
  color: #dc2626;
}

.btn-outline-danger:hover {
  background: #fef2f2;
  border-color: #dc2626;
}

.btn-outline-secondary {
  border-color: #e2e8f0;
  color: #64748b;
}

.btn-outline-secondary:hover {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-sm {
  padding: 6px 10px;
  font-size: 0.85rem;
}

.btn-sm.btn-outline-primary,
.btn-sm.btn-outline-danger {
  width: 34px;
  height: 34px;
  padding: 0;
  border-radius: 50px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.bg-primary {
  background-color: #2563eb !important;
  color: #fff !important;
}

.badge-clave {
  background-color: #334155 !important;
  color: #fff !important;
  font-size: 0.75rem;
  font-weight: 600;
}
</style>
