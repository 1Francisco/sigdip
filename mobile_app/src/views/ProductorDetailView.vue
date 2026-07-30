<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando productor...</div>

      <template v-if="productor">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">{{ nombreCompleto }}</h2>
              <p class="text-secondary small mb-0">CURP: {{ productor.curp || 'N/A' }}</p>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">{{ productor.predios_count || 0 }} predios</span>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-person me-2"></i>Información General</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">CURP</div>
              <div class="fw-semibold">{{ productor.curp || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">UPP</div>
              <div class="fw-semibold">{{ productor.upp || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Teléfono</div>
              <div class="fw-semibold">{{ productor.telefono || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Email</div>
              <div class="fw-semibold">{{ productor.email || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Municipio</div>
              <div class="fw-semibold">{{ productor.municipio || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Localidad</div>
              <div class="fw-semibold">{{ productor.localidad || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Estado</div>
              <div class="fw-semibold">{{ productor.estado || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Domicilio</div>
              <div class="fw-semibold">{{ productor.domicilio || '—' }}</div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-house-door me-2"></i>Predios / Ranchos</div>
          <div v-if="!predios.length" class="text-muted">Sin predios registrados.</div>
          <div v-else class="list-group list-group-flush">
            <div v-for="predio in predios" :key="predio.id" class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
              <div class="text-start">
                <div class="fw-semibold">{{ predio.nombre_rancho }}</div>
                <div class="text-secondary small">{{ predio.clave_unidad_produccion || 'Sin CUP' }} · {{ predio.municipio }}</div>
              </div>
              <button class="btn btn-sm btn-outline-primary rounded-pill" @click="$router.push(`/predios/${predio.id}`)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-people me-2"></i>Productores del mismo Hato ({{ vinculados.length }})</span>
            <span v-if="productor.clave" class="badge bg-primary text-white rounded-pill px-3 py-1 small">Hato: {{ productor.clave }}</span>
          </div>
          <div v-if="!vinculados.length" class="text-muted">No hay otros productores vinculados a este hato.</div>
          <div v-else class="list-group list-group-flush text-start">
            <div v-for="vinc in vinculados" :key="vinc.id" class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
              <div>
                <div class="fw-semibold">{{ vinc.nombre_completo }}</div>
                <div class="text-secondary small">UPP: {{ vinc.upp || '—' }} · Tel: {{ vinc.telefono || '—' }}</div>
              </div>
              <button class="btn btn-sm btn-outline-primary rounded-pill" @click="goToProductor(vinc.id)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mb-5 flex-wrap">
          <button class="btn btn-primary rounded-pill px-4" @click="$router.push(`/productores/editar/${productor.id}`)">
            <i class="bi bi-pencil me-1"></i> Editar
          </button>
          <button v-if="productor.clave" class="btn btn-outline-primary rounded-pill px-4" @click="$router.push(`/productores/${productor.id}/gestionar-hato`)">
            <i class="bi bi-people me-1"></i> Gestionar Hato
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/productores')">
            Volver
          </button>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import api from '../services/api.js';

export default {
  name: 'ProductorDetailView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      errorMsg: '',
      productor: null
    };
  },
  computed: {
    nombreCompleto() {
      if (!this.productor) return '';
      return [this.productor.nombre, this.productor.apellido_paterno, this.productor.apellido_materno].filter(Boolean).join(' ');
    },
    predios() {
      return this.productor?.predios || [];
    },
    vinculados() {
      return this.productor?.vinculados || [];
    }
  },
  watch: {
    '$route.params.id': {
      handler: 'loadDetail',
      immediate: true
    }
  },
  methods: {
    async loadDetail() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.getProductor(this.$route.params.id);
        this.productor = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el productor.';
      } finally {
        this.loading = false;
      }
    },
    goToProductor(id) {
      this.$router.push(`/productores/${id}`);
    }
  }
};
</script>
