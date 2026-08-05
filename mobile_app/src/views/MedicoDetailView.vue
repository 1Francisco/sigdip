<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando médico...</div>

      <template v-if="medico">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">{{ medico.name }}</h2>
              <p class="text-secondary small mb-0">{{ medico.email }}</p>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">{{ asignados.length }} productores</span>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-person-badge me-2"></i>Información General</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Zona</div>
              <div class="fw-semibold">{{ medico.zona || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Actividad</div>
              <div class="fw-semibold">{{ medico.actividad || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Correo de acceso</div>
              <div class="fw-semibold">{{ medico.email || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Fecha de registro</div>
              <div class="fw-semibold">{{ medico.created_at || '—' }}</div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-bold text-dark"><i class="bi bi-people me-2"></i>Productores Asignados ({{ asignados.length }})</div>
            <button class="btn btn-sm btn-outline-success rounded-pill px-3" @click="$router.push(`/medicos/asignar/${medico.id}`)">
              <i class="bi bi-person-plus me-1"></i> Asignar
            </button>
          </div>
          <div v-if="!asignados.length" class="text-muted">Sin productores asignados.</div>
          <div v-else class="list-group list-group-flush">
            <div v-for="p in asignados" :key="p.id" class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
              <div class="text-start">
                <div class="fw-semibold">{{ p.nombre }} {{ p.apellido_paterno }} {{ p.apellido_materno }}</div>
                <div class="text-secondary small">{{ p.upp || 'Sin UPP' }} · {{ p.predios_count || 0 }} predios</div>
              </div>
              <button class="btn btn-sm btn-outline-primary rounded-pill" @click="$router.push(`/productores/${p.id}`)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mb-5 flex-wrap">
          <button class="btn btn-primary rounded-pill px-4" @click="$router.push(`/medicos/editar/${medico.id}`)">
            <i class="bi bi-pencil me-1"></i> Editar
          </button>
          <button class="btn btn-outline-danger rounded-pill px-4" @click="deleteMedico">
            <i class="bi bi-trash me-1"></i> Eliminar
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/medicos')">
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
  name: 'MedicoDetailView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      errorMsg: '',
      medico: null,
      asignados: []
    };
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
        const [medicoRes, asignadosRes] = await Promise.all([
          api.getMedico(this.$route.params.id),
          api.getProductoresAsignables(this.$route.params.id)
        ]);
        this.medico = medicoRes.data;
        this.asignados = asignadosRes.asignados || [];
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el médico.';
      } finally {
        this.loading = false;
      }
    },
    async deleteMedico() {
      if (!this.medico) return;
      if (!confirm(`¿Estás seguro de eliminar al médico "${this.medico.name}"? No podrá volver a iniciar sesión.`)) return;
      this.errorMsg = '';
      try {
        await api.deleteMedico(this.medico.id);
        this.$router.push('/medicos');
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo eliminar al médico.';
      }
    }
  }
};
</script>
