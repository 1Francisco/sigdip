<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
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
              <div class="fw-semibold">{{ arete.sacrificio ? 'Sí' : 'No' }}</div>
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
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/aretes-censo')">
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
      errorMsg: '',
      arete: null
    };
  },
  async mounted() {
    await this.loadDetail();
  },
  methods: {
    async loadDetail() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.getAreteCenso(this.$route.params.id);
        this.arete = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el arete.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
