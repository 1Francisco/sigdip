<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert alert-success shadow-sm rounded-4 border-0">{{ successMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando arete...</div>

      <template v-if="arete">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">Arete {{ arete.numero_arete }}</h2>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-upc-scan me-2"></i>Información del Arete</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Número de Arete</div>
              <div class="fw-semibold">{{ arete.numero_arete || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Raza</div>
              <div class="fw-semibold">{{ arete.raza || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Sexo</div>
              <div class="fw-semibold">{{ arete.sexo || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Fecha de Nacimiento</div>
              <div class="fw-semibold">{{ arete.fecha_nacimiento || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Edad (meses)</div>
              <div class="fw-semibold">{{ arete.edad_meses != null ? arete.edad_meses : '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Sacrificio</div>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge rounded-pill px-3 py-2" :class="arete.sacrificio ? 'bg-danger' : 'bg-secondary'">
                  {{ arete.sacrificio ? 'Sacrificado' : 'Activo' }}
                </span>
                <button
                  class="btn btn-sm rounded-pill px-3"
                  :class="arete.sacrificio ? 'btn-outline-success' : 'btn-outline-danger'"
                  :disabled="toggling"
                  @click="toggleSacrificio"
                >
                  <span v-if="toggling" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else :class="arete.sacrificio ? 'bi-arrow-counterclockwise' : 'bi-x-circle'" class="me-1"></i>
                  {{ arete.sacrificio ? 'Reactivar' : 'Sacrificar' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-person me-2"></i>Productor</div>
          <div v-if="arete.productor" class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Nombre</div>
              <div class="fw-semibold">{{ arete.productor.nombre }} {{ arete.productor.apellido_paterno }}</div>
            </div>
          </div>
          <div v-else class="text-muted">Sin productor asignado.</div>
          <button v-if="arete.productor" class="btn btn-sm btn-outline-primary rounded-pill mt-3" @click="$router.push(`/productores/${arete.productor.id}`)">
            <i class="bi bi-eye me-1"></i> Ver productor
          </button>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-house-door me-2"></i>Predio</div>
          <div v-if="arete.predio" class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Rancho</div>
              <div class="fw-semibold">{{ arete.predio.nombre_rancho || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Municipio</div>
              <div class="fw-semibold">{{ arete.predio.municipio || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Clave Unidad Producción</div>
              <div class="fw-semibold">{{ arete.predio.clave_unidad_produccion || '—' }}</div>
            </div>
          </div>
          <div v-else class="text-muted">Sin predio asignado.</div>
          <button v-if="arete.predio" class="btn btn-sm btn-outline-primary rounded-pill mt-3" @click="$router.push(`/predios/${arete.predio.id}`)">
            <i class="bi bi-eye me-1"></i> Ver predio
          </button>
        </div>

        <div class="d-flex gap-2 mb-5">
          <button class="btn btn-primary rounded-pill px-4" @click="$router.push(`/aretes-censo/editar/${arete.id}`)">
            <i class="bi bi-pencil me-1"></i> Editar
          </button>
           <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/predios')">
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
  name: 'AreteCensoDetailView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      toggling: false,
      errorMsg: '',
      successMsg: '',
      arete: null
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    if (!user || !user.roles || !user.roles.includes('Administrador')) {
      alert('Solo administradores pueden ver aretes del censo.');
      this.$router.push('/dashboard');
      return;
    }
    await this.loadDetail();
  },
  methods: {
    async loadDetail() {
      this.loading = true;
      this.errorMsg = '';
      this.successMsg = '';
      try {
        const res = await api.getAreteCenso(this.$route.params.id);
        this.arete = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el arete.';
      } finally {
        this.loading = false;
      }
    },
    async toggleSacrificio() {
      this.toggling = true;
      this.errorMsg = '';
      this.successMsg = '';
      try {
        const newVal = !this.arete.sacrificio;
        await api.updateAreteCenso(this.arete.id, { sacrificio: newVal });
        this.arete.sacrificio = newVal;
        this.successMsg = newVal ? 'Arete marcado como sacrificado.' : 'Arete removido de sacrificio.';
      } catch (e) {
        this.errorMsg = e.message || 'Error al cambiar estado de sacrificio.';
      } finally {
        this.toggling = false;
      }
    }
  }
};
</script>
