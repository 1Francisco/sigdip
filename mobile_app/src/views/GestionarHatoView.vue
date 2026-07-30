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
              <button class="btn btn-sm btn-outline-primary rounded-pill" @click="$router.push(`/productores/${vinc.id}`)">
                <i class="bi bi-eye"></i>
              </button>
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
                    <span v-if="item.clave" class="badge bg-secondary rounded-pill me-1">{{ item.clave }}</span>
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
        const predios = await db.getPredios();
        const predio = predios.find(p => p.productor && String(p.productor.id) === String(this.productorId));
        if (predio?.productor) {
          this.productor = predio.productor;
        } else {
          this.errorMsg = 'No se pudo cargar la información del productor.';
        }
      } finally {
        this.loading = false;
      }
    },
    async onSearchInput() {
      const q = this.searchTerm.trim();
      if (q.length < 2) {
        this.searchResults = [];
        this.selectedProductor = null;
        return;
      }
      this.isSearching = true;
      this.selectedProductor = null;
      try {
        const res = await api.searchProductor(q);
        this.searchResults = (Array.isArray(res) ? res : []).filter(p => String(p.id) !== String(this.productorId));
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
  },
};
</script>

<style scoped>
</style>
