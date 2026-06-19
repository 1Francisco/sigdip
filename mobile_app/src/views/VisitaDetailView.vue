<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando visita...</div>

      <template v-if="visita">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">Visita {{ visita.codigo || `#${visita.id}` }}</h2>
              <p class="text-secondary small mb-0">{{ visita.predio?.nombre_rancho || 'Sin predio' }}</p>
            </div>
            <span class="badge rounded-pill px-3 py-2" :class="estadoBadgeClass">
              {{ estadoLabel }}
            </span>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-calendar-check me-2"></i>Información de la Visita</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Código</div>
              <div class="fw-semibold">{{ visita.codigo || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Estado</div>
              <div class="fw-semibold">{{ estadoLabel }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Fecha Programada</div>
              <div class="fw-semibold">{{ formatDate(visita.fecha_programada) }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Inyección</div>
              <div class="fw-semibold">{{ visita.inyeccion ? 'Sí' : 'No' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Veterinario</div>
              <div class="fw-semibold">{{ visita.veterinario?.name || '—' }}</div>
            </div>
            <div class="col-12">
              <div class="text-muted small">Observaciones</div>
              <div class="fw-semibold">{{ visita.observaciones || '—' }}</div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-house-door me-2"></i>Predio Asignado</div>
          <div v-if="visita.predio" class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Rancho</div>
              <div class="fw-semibold">
                <a class="text-primary text-decoration-none" @click.prevent="$router.push(`/predios/${visita.predio.id}`)">
                  {{ visita.predio.nombre_rancho }}
                </a>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">CUP</div>
              <div class="fw-semibold">{{ visita.predio.clave_unidad_produccion || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Productor</div>
              <div class="fw-semibold">
                <a v-if="visita.predio.productor" class="text-primary text-decoration-none" @click.prevent="$router.push(`/productores/${visita.predio.productor.id}`)">
                  {{ visita.predio.productor.nombre }} {{ visita.predio.productor.apellido_paterno }}
                </a>
                <span v-else>—</span>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Municipio</div>
              <div class="fw-semibold">{{ visita.predio.municipio || '—' }}</div>
            </div>
          </div>
          <div v-else class="text-muted">Sin predio asignado.</div>
        </div>

        <div v-if="visita.inspeccion" class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-clipboard-check me-2"></i>Dictamen Asociado</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Folio</div>
              <div class="fw-semibold">{{ visita.inspeccion.folio || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Estado</div>
              <span class="badge rounded-pill" :class="visita.inspeccion.estado === 'borrador' ? 'bg-warning text-dark' : 'bg-success'">
                {{ visita.inspeccion.estado }}
              </span>
            </div>
            <div class="col-12">
              <button class="btn btn-sm btn-outline-primary rounded-pill mt-2" @click="$router.push(`/inspecciones/${visita.inspeccion.id}`)">
                <i class="bi bi-eye me-1"></i> Ver Dictamen
              </button>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mb-5">
          <button class="btn btn-primary rounded-pill px-4" @click="$router.push(`/visitas/editar/${visita.id}`)">
            <i class="bi bi-pencil me-1"></i> Editar
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/visitas')">
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
  name: 'VisitaDetailView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      errorMsg: '',
      visita: null
    };
  },
  computed: {
    estadoLabel() {
      if (!this.visita) return '';
      const map = { pendiente: 'Pendiente', completada: 'Completada', cancelada: 'Cancelada' };
      return map[this.visita.estado] || this.visita.estado;
    },
    estadoBadgeClass() {
      if (!this.visita) return '';
      if (this.visita.estado === 'pendiente') return 'bg-warning text-dark';
      if (this.visita.estado === 'completada') return 'bg-success text-white';
      if (this.visita.estado === 'cancelada') return 'bg-danger text-white';
      return 'bg-secondary text-white';
    }
  },
  async mounted() {
    await this.loadDetail();
  },
  methods: {
    async loadDetail() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.getVisita(this.$route.params.id);
        this.visita = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar la visita.';
      } finally {
        this.loading = false;
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '—';
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
  }
};
</script>
