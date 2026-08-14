<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando animal...</div>

      <template v-if="animal">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">{{ animal.numero_arete_siniiga }}</h2>
              <p class="text-secondary small mb-0">{{ animal.raza || 'Sin raza' }} · {{ animal.sexo || 'S/E' }}</p>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">{{ animal.edad ?? '—' }} meses</span>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2"></i>Información del Animal</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Número Arete SINIIGA</div>
              <div class="fw-semibold">{{ animal.numero_arete_siniiga }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Raza</div>
              <div class="fw-semibold">{{ animal.raza || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Sexo</div>
              <div class="fw-semibold">{{ animal.sexo || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Edad (meses)</div>
              <div class="fw-semibold">{{ animal.edad ?? '—' }} meses</div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-house-door me-2"></i>Predio</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Nombre del Rancho</div>
              <div class="fw-semibold">{{ animal.predio?.nombre_rancho || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">CUP</div>
              <div class="fw-semibold">{{ animal.predio?.clave_unidad_produccion || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Municipio</div>
              <div class="fw-semibold">{{ animal.predio?.municipio || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Productor</div>
              <div class="fw-semibold" v-if="animal.predio?.productor">
                {{ animal.predio.productor.nombre }} {{ animal.predio.productor.apellido_paterno }}
              </div>
              <div class="fw-semibold" v-else>—</div>
            </div>
          </div>
          <button
            v-if="animal.predio"
            class="btn btn-sm btn-outline-primary rounded-pill mt-3"
            @click="$router.push(`/predios/${animal.predio.id}`)"
          >
            <i class="bi bi-eye me-1"></i> Ver predio
          </button>
        </div>

        <div class="d-flex gap-2 mb-5">
          <button class="btn btn-primary rounded-pill px-4" @click="$router.push(`/animales/editar/${animal.id}`)">
            <i class="bi bi-pencil me-1"></i> Editar
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/animales')">
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
  name: 'AnimalDetailView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      errorMsg: '',
      animal: null
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    if (!user || !user.roles || !user.roles.includes('Administrador')) {
      alert('Solo administradores pueden ver animales.');
      this.$router.push('/dashboard');
      return;
    }
    await this.loadDetail();
  },
  methods: {
    async loadDetail() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.getAnimal(this.$route.params.id);
        this.animal = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el animal.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
