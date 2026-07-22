<template>
  <AppLayout>
    <div class="container-form-wrapper">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando predio...</div>

      <template v-if="predio">
        <div class="welcome-header mb-3 text-start">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <h2 class="h4 fw-bold mb-0 text-dark">{{ predio.nombre_rancho }}</h2>
              <p class="text-secondary small mb-0">{{ predio.clave_unidad_produccion || 'Sin CUP' }}</p>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2"></i>Información del Predio</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <div class="text-muted small">Productor</div>
              <div class="fw-semibold">
                <a v-if="predio.productor" class="text-primary text-decoration-none" @click.prevent="$router.push(`/productores/${predio.productor.id}`)">
                  {{ predio.productor.nombre }} {{ predio.productor.apellido_paterno }}
                </a>
                <span v-else>—</span>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">CUP</div>
              <div class="fw-semibold">{{ predio.clave_unidad_produccion || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Municipio</div>
              <div class="fw-semibold">{{ predio.municipio || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Localidad</div>
              <div class="fw-semibold">{{ predio.localidad || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Domicilio</div>
              <div class="fw-semibold">{{ predio.domicilio || '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small">Coordenadas</div>
              <div class="fw-semibold">{{ predio.latitud && predio.longitud ? `${predio.latitud}, ${predio.longitud}` : '—' }}</div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-bold text-dark"><i class="bi bi bi-gender-ambiguous me-2"></i>Animales</div>
            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="$router.push('/animales')">
              <i class="bi bi-eye me-1"></i> Ver todos
            </button>
          </div>
          <div class="input-group mb-3">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input v-model="searchAnimal" type="text" class="form-control border-start-0" placeholder="Buscar por arete o raza...">
          </div>
          <div v-if="!filteredAnimales.length" class="text-muted">{{ animales.length ? 'Sin resultados de búsqueda.' : 'Sin animales registrados.' }}</div>
          <div v-else class="table-responsive">
            <table class="table table-sm table-borderless align-middle mb-0">
              <thead>
                <tr class="text-muted small">
                  <th>Arete</th>
                  <th>Raza</th>
                  <th>Sexo</th>
                  <th>Edad</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="animal in filteredAnimales" :key="animal.id" class="clickable-row" @click="$router.push(`/animales/${animal.id}`)">
                  <td class="fw-semibold">{{ animal.numero_arete_siniiga }}</td>
                  <td>{{ animal.raza || '—' }}</td>
                  <td>{{ animal.sexo || '—' }}</td>
                  <td>{{ animal.edad != null ? `${animal.edad} meses` : '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-flex gap-2 mb-5">
          <button class="btn btn-primary rounded-pill px-4" @click="$router.push(`/predios/editar/${predio.id}`)">
            <i class="bi bi-pencil me-1"></i> Editar
          </button>
          <button class="btn btn-outline-primary rounded-pill px-4" @click="$router.push(`/inspeccion/${predio.id}`)">
            <i class="bi bi-file-earmark-plus me-1"></i> Nuevo Dictamen
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
  name: 'PredioDetailView',
  components: { AppLayout },
  data() {
    return {
      loading: false,
      errorMsg: '',
      predio: null,
      searchAnimal: ''
    };
  },
  computed: {
    animales() {
      return this.predio?.animales || [];
    },
    filteredAnimales() {
      if (!this.searchAnimal) return this.animales;
      const q = this.searchAnimal.toLowerCase();
      return this.animales.filter(a =>
        (a.numero_arete_siniiga && a.numero_arete_siniiga.toLowerCase().includes(q)) ||
        (a.raza && a.raza.toLowerCase().includes(q))
      );
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
        const res = await api.getPredio(this.$route.params.id);
        this.predio = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el predio.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.clickable-row {
  cursor: pointer;
  transition: background-color 0.15s;
}
.clickable-row:hover {
  background-color: #f1f5f9;
}
</style>
