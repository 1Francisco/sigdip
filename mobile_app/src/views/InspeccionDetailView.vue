<template>
  <div class="app-container bg-light">
    <header class="app-header shadow-sm">
      <div class="d-flex align-items-center gap-2">
        <button class="btn-back" @click="$router.push('/inspecciones')">
          <i class="bi bi-arrow-left fs-4 text-white"></i>
        </button>
        <div>
          <h1>Vista del Dictamen</h1>
          <div class="subtitle">{{ inspeccion?.clave_interna || 'Sin folio' }}</div>
        </div>
      </div>
      <button class="btn-close-form" @click="$router.push('/inspecciones')">
        <i class="bi bi-x-lg text-white"></i>
      </button>
    </header>

    <main class="app-content main-content">
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0">{{ errorMsg }}</div>
      <div v-if="loading" class="text-center text-muted py-5">Cargando dictamen...</div>

      <template v-if="inspeccion">
        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="text-start">
              <div class="fw-bold fs-4 text-dark">{{ inspeccion?.clave_interna || 'Sin Folio (Borrador)' }}</div>
              <div class="text-secondary small">
                {{ inspeccion.predio?.nombre_rancho }} · {{ inspeccion.predio?.localidad }}
              </div>
              <div class="text-secondary small">
                MVZ. {{ inspeccion.veterinario?.name }} · {{ formatDate(inspeccion.fecha) }}
              </div>
            </div>
            <div class="d-flex flex-column gap-2">
              <span class="badge rounded-pill px-3 py-2" :class="inspeccion.estado === 'borrador' ? 'bg-warning text-dark' : 'bg-success text-white'">
                {{ inspeccion.estado === 'borrador' ? 'Borrador' : 'Finalizado' }}
              </span>
              <button class="btn btn-danger btn-sm rounded-pill px-3" @click="openPdf">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
              </button>
            </div>
          </div>
          <div class="mt-4 d-flex gap-2 flex-wrap">
            <button v-if="inspeccion.estado === 'borrador'" class="btn btn-primary rounded-pill px-3" @click="continueInspection">
              Continuar edición
            </button>
            <button class="btn btn-outline-secondary rounded-pill px-3" @click="$router.push('/inspecciones')">
              Volver
            </button>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3">Datos Generales</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-4"><div class="text-muted small">Tipo de prueba</div><div class="fw-semibold">{{ inspeccion.tipo_prueba || '-' }}</div></div>
            <div class="col-12 col-md-4"><div class="text-muted small">Motivo</div><div class="fw-semibold">{{ inspeccion.motivo_prueba || '-' }}</div></div>
            <div class="col-12 col-md-4"><div class="text-muted small">Función zootécnica</div><div class="fw-semibold">{{ inspeccion.funcion_zootecnica || '-' }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">F. Inyección</div><div class="fw-semibold">{{ formatDate(inspeccion.fecha_inyeccion) }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">H. Inyección</div><div class="fw-semibold">{{ inspeccion.hora_inyeccion || '-' }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">F. Lectura</div><div class="fw-semibold">{{ formatDate(inspeccion.fecha_lectura) }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">H. Lectura</div><div class="fw-semibold">{{ inspeccion.hora_lectura || '-' }}</div></div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
          <div class="fw-bold text-dark mb-3">Censo Ganadero</div>
          <div class="row g-3 text-start">
            <div class="col-6 col-md-2"><div class="text-muted small">Sementales</div><div class="fw-semibold">{{ inspeccion.sementales || 0 }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Vacas</div><div class="fw-semibold">{{ inspeccion.vacas || 0 }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Vaquillas</div><div class="fw-semibold">{{ inspeccion.vaquillas || 0 }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Becerras</div><div class="fw-semibold">{{ inspeccion.becerras || 0 }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Becerros</div><div class="fw-semibold">{{ inspeccion.becerros || 0 }}</div></div>
          </div>
        </div>

        <div class="card shadow-sm border-0 p-4 rounded-4 mb-5">
          <div class="fw-bold text-dark mb-3">Animales</div>
          <div v-if="!inspeccion.detalles || inspeccion.detalles.length === 0" class="text-muted">No hay animales registrados.</div>
          <div v-else class="list-group list-group-flush">
            <div v-for="detalle in inspeccion.detalles" :key="detalle.id" class="list-group-item px-0">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div class="text-start">
                  <div class="fw-bold">{{ detalle.animal?.numero_arete_siniiga || 'Sin arete' }}</div>
                  <div class="text-secondary small">
                    Raza: {{ detalle.raza || detalle.animal?.raza || '-' }} · Sexo: {{ detalle.sexo || detalle.animal?.sexo || '-' }}
                  </div>
                  <div class="text-secondary small">
                    Resultado: <span class="fw-semibold">{{ detalle.resultado_prueba }}</span>
                  </div>
                  <div class="text-secondary small" v-if="detalle.observaciones_animal">Obs: {{ detalle.observaciones_animal }}</div>
                  <div class="text-secondary small" v-if="detalle.motivo_no_aplica"><i class="bi bi-info-circle me-1"></i>{{ detalle.motivo_no_aplica }}</div>
                </div>
                <span class="badge rounded-pill" :class="resultadoClass(detalle.resultado_prueba)">
                  {{ detalle.resultado_prueba }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script>
import api from '../services/api.js';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

export default {
  name: 'InspeccionDetailView',
  data() {
    return {
      loading: false,
      errorMsg: '',
      inspeccion: null
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
        const res = await api.getInspeccion(this.$route.params.id);
        this.inspeccion = res.data;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar el dictamen.';
      } finally {
        this.loading = false;
      }
    },
    folioLabel(folio) {
      if (!folio || folio === this.inspeccion?.clave_interna) return this.inspeccion?.clave_interna || 'Sin Folio (Borrador)';
      return folio;
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
    resultadoClass(resultado) {
      if (resultado === 'Positivo') return 'bg-danger text-white';
      if (resultado === 'Sospechoso') return 'bg-warning text-dark';
      return 'bg-success text-white';
    },
    continueInspection() {
      this.$router.push(`/inspeccion/${this.inspeccion.predio_id}?inspeccion_id=${this.inspeccion.id}${this.inspeccion.visita_id ? `&visita_id=${this.inspeccion.visita_id}` : ''}`);
    },
    async openPdf() {
      this.errorMsg = '';
      try {
        const blob = await api.getInspectionPdf(this.inspeccion.id);
        const fileName = `dictamen_${this.inspeccion.clave_interna || this.inspeccion.id}_${new Date().getDate()}-${new Date().getMonth() + 1}-${new Date().getFullYear()}.pdf`;

        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = async () => {
            try {
              const base64data = reader.result.split(',')[1];
              const result = await Filesystem.writeFile({
                path: fileName,
                data: base64data,
                directory: Directory.Documents,
                recursive: true
              });
              try {
                await Share.share({
                  title: fileName,
                  url: result.uri,
                  dialogTitle: 'Abrir / Compartir PDF'
                });
              } catch (shareErr) {
                // User may cancel share dialog
              }
            } catch (err) {
              console.error('Error saving PDF native:', err);
              this.errorMsg = 'No se pudo guardar el PDF: ' + err.message;
            }
          };
        } else {
          const url = URL.createObjectURL(blob);
          window.open(url, '_blank');
          setTimeout(() => URL.revokeObjectURL(url), 10000);
        }
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo abrir el PDF.';
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  min-height: 100dvh;
}

.btn-back, .btn-close-form {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.btn-close-form {
  margin-left: auto;
}
</style>
