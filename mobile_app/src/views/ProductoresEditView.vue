<template>
  <div class="app-container bg-light-page">
    <header class="form-view-header shadow-sm">
      <div class="d-flex align-items-center gap-2 gap-sm-3 header-left-container">
        <button class="btn-back-circle shadow-sm" @click="handleCancel">
          <i class="bi bi-arrow-left fs-5"></i>
        </button>
        <div class="text-start title-text-wrapper">
          <h1 class="h5 fw-bold mb-0 text-dark header-title">Editar Productor</h1>
          <div class="subtitle text-secondary small d-none d-sm-block">
            Actualice la información del productor: {{ form.nombre || '...' }}
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-1 gap-sm-2 header-badges">
        <span class="web-connectivity-pill shadow-sm">
          <span class="dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
          <span class="d-none d-sm-inline">{{ isOnline ? 'Conectado' : 'Offline' }}</span>
        </span>
        <span class="web-role-pill shadow-sm d-none d-md-flex">
          <i class="bi bi-person-fill text-primary"></i>
          {{ userName }}
        </span>
      </div>
    </header>

    <main class="app-content main-content p-4">
      <div class="container-form-wrapper">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white edit-productor-card">
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
            <div class="text-start">
              <div class="fw-bold fs-4 text-dark">Editar Productor</div>
              <div class="text-secondary small">Actualice la información del productor y su rancho</div>
            </div>
            <span class="badge rounded-pill px-3 py-2" :class="isOnline ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
              {{ isOnline ? 'Conectado' : 'Offline' }}
            </span>
          </div>

          <div v-if="errorMsg" class="alert alert-danger border-0 shadow-sm rounded-4">{{ errorMsg }}</div>

          <form @submit.prevent="processFinalSave">
            <div class="row g-3 text-start">
              <div class="col-12 col-md-4">
                <div class="form-group-custom">
                  <label class="form-label-custom">Nombre(s)</label>
                  <input v-model="form.nombre" type="text" class="form-control-custom" required>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom">
                  <label class="form-label-custom">Apellido Paterno</label>
                  <input v-model="form.apellido_paterno" type="text" class="form-control-custom" required>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom">
                  <label class="form-label-custom">Apellido Materno</label>
                  <input v-model="form.apellido_materno" type="text" class="form-control-custom">
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">CURP</label>
                  <input v-model="form.curp" type="text" class="form-control-custom font-mono" maxlength="18" style="text-transform: uppercase;" required>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">UPP (Unidad de Producción Pecuaria)</label>
                  <input v-model="form.upp" type="text" class="form-control-custom" required>
                </div>
              </div>

              <div class="col-12">
                <div class="form-group-custom">
                  <label class="form-label-custom">Domicilio Completo</label>
                  <input v-model="form.domicilio" type="text" class="form-control-custom" placeholder="Domicilio Conocido">
                </div>
              </div>

              <div class="col-12 col-md-4">
                <div class="form-group-custom">
                  <label class="form-label-custom">Municipio</label>
                  <input v-model="form.municipio" type="text" class="form-control-custom" placeholder="General">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom">
                  <label class="form-label-custom">Localidad</label>
                  <input v-model="form.localidad" type="text" class="form-control-custom" placeholder="General">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom">
                  <label class="form-label-custom">Estado</label>
                  <input v-model="form.estado" type="text" class="form-control-custom bg-light" readonly tabindex="-1">
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">Teléfono</label>
                  <input v-model="form.telefono" type="tel" class="form-control-custom" placeholder="Ej: 3111129405">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">Correo Electrónico</label>
                  <input v-model="form.email" type="email" class="form-control-custom" placeholder="ejemplo@correo.com">
                </div>
              </div>

              <!-- Clave | Zona / Sector (Solo Administradores) -->
              <template v-if="isAdmin">
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Clave</label>
                    <input 
                      :value="form.clave" 
                      type="text" 
                      class="form-control-custom text-uppercase" 
                      placeholder="Ej. BD-123421"
                      @input="e => { form.clave = e.target.value.toUpperCase(); onClaveInput(); }"
                    >
                    <div class="small text-muted mt-1" style="font-size: 0.72rem;">Debe iniciar con AD, AP, BD o BP.</div>
                    
                    <!-- Resultados de clave autocomplete (real-time como en la web) -->
                    <div v-if="resultadosClave.length > 0" class="mt-2 p-2 bg-light rounded border text-start" style="max-height: 180px; overflow-y: auto;">
                      <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">Productores con esta clave:</div>
                      <div class="list-group list-group-flush">
                        <div 
                          v-for="p in resultadosClave" 
                          :key="p.id" 
                          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small border rounded mb-1" 
                          style="cursor: pointer; background: #fff; font-size: 0.78rem;"
                          @click="selectClave(p.clave)"
                        >
                          <div>
                            <span class="badge bg-secondary rounded-pill me-2" style="font-size: 0.7rem;">{{ p.clave }}</span>
                            <span class="fw-bold">{{ p.nombre }} {{ p.apellido_paterno }}</span>
                          </div>
                          <span class="text-secondary small">{{ p.tipo_actividad || 'General' }}</span>
                        </div>
                      </div>
                    </div>
                    <div v-else-if="form.clave && isSearchingClave" class="small text-muted mt-1 text-start" style="font-size: 0.75rem;">
                      <span class="spinner-border spinner-border-sm me-1" role="status" style="width: 0.75rem; height: 0.75rem;"></span> Buscando claves...
                    </div>
                    <div v-else-if="form.clave && noClaveFound" class="small text-warning mt-1 text-start" style="font-size: 0.75rem;">
                      <i class="bi bi-exclamation-triangle-fill"></i> Clave nueva (no asignada a ningún productor en el servidor).
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Zona / Sector</label>
                    <select v-model="form.zona" class="form-select form-control-custom bg-light">
                      <option value="">-- Sin Zona --</option>
                      <option value="A">Sector A</option>
                      <option value="B">Sector B</option>
                    </select>
                  </div>
                </div>
              </template>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 pt-3 border-top border-slate-100">
              <button 
                type="button" 
                class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold text-start btn-sm" 
                style="font-size: 0.82rem;"
                @click="$router.push(`/productores/${productorId}/gestionar-hato`)"
              >
                <i class="bi bi-people-fill me-1"></i> Gestionar Hato (Clave: {{ form.clave || 'S/C' }})
              </button>
              <div class="d-flex gap-2">
                <button type="button" class="btn-cancel-custom rounded-pill px-4" @click="handleCancel">Cancelar</button>
                <button type="submit" class="btn-submit-custom rounded-pill px-4" :disabled="saving">
                  {{ saving ? 'Actualizando...' : 'Actualizar Productor' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import db from '../services/db.js';
import api from '../services/api.js';

export default {
  name: 'ProductoresEditView',
  data() {
    return {
      userName: '',
      isOnline: true,
      isAdmin: false,
      networkListener: null,
      saving: false,
      errorMsg: '',
      productorId: null,
      
      // Clave autocomplete states
      resultadosClave: [],
      isSearchingClave: false,
      noClaveFound: false,
      claveDebounce: null,
      
      form: {
        nombre: '',
        apellido_paterno: '',
        apellido_materno: '',
        curp: '',
        upp: '',
        domicilio: '',
        municipio: '',
        localidad: '',
        estado: 'Nayarit',
        telefono: '',
        email: '',
        clave: '',
        zona: '',
        nombre_rancho: '',
        clave_unidad_produccion: '',
        predio_localidad: '',
        predio_municipio: '',
        predio_domicilio: '',
        latitud: '',
        longitud: '',
        apellido_materno: '',
        curp: '',
        upp: '',
        domicilio: '',
        municipio: '',
        localidad: '',
        estado: 'Nayarit',
        telefono: '',
        email: '',
        clave: '',
        zona: '',
        nombre_rancho: '',
        clave_unidad_produccion: '',
        predio_localidad: '',
        predio_municipio: '',
        predio_domicilio: '',
        latitud: '',
        longitud: ''
      }
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.productorId = this.$route.params.id;

    await this.loadProductorDataForEdit();

    try {
      const status = await Network.getStatus();
      this.isOnline = status.connected;
      this.networkListener = await Network.addListener('networkStatusChange', (status) => {
        this.isOnline = status.connected;
      });
    } catch (e) {
      this.isOnline = navigator.onLine;
    }
  },
  beforeUnmount() {
    if (this.networkListener) {
      this.networkListener.remove();
    }
  },
  methods: {
    async loadProductorDataForEdit() {
      this.errorMsg = '';
      try {
        const res = await api.getProductor(this.productorId);
        const productor = res.data || {};
        const predio = productor.predios?.[0] || null;

        this.form.nombre = productor.nombre || '';
        this.form.apellido_paterno = productor.apellido_paterno || '';
        this.form.apellido_materno = productor.apellido_materno || '';
        this.form.curp = productor.curp || '';
        this.form.upp = productor.upp || '';
        this.form.domicilio = productor.domicilio || '';
        this.form.municipio = productor.municipio || '';
        this.form.localidad = productor.localidad || '';
        this.form.estado = productor.estado || 'Nayarit';
        this.form.telefono = productor.telefono || '';
        this.form.email = productor.email || '';
        this.form.clave = productor.clave || '';
        this.form.zona = productor.zona || '';
        this.form.nombre_rancho = predio?.nombre_rancho || predio?.nombre || '';
        this.form.clave_unidad_produccion = predio?.clave_unidad_produccion || predio?.upp || '';
        this.form.predio_localidad = predio?.localidad || '';
        this.form.predio_municipio = predio?.municipio || '';
        this.form.predio_domicilio = predio?.domicilio || '';
        this.form.latitud = predio?.latitud || '';
        this.form.longitud = predio?.longitud || '';
      } catch (e) {
        const predios = await db.getPredios();
        const predioAsociado = predios.find(p => p.productor && String(p.productor.id) === String(this.productorId));
        if (predioAsociado && predioAsociado.productor) {
          const prod = predioAsociado.productor;
          this.form.nombre = prod.nombre || '';
          this.form.apellido_paterno = prod.apellido_paterno || '';
          this.form.apellido_materno = prod.apellido_materno || '';
          this.form.curp = prod.curp || '';
          this.form.upp = prod.upp || '';
          this.form.domicilio = prod.domicilio || '';
          this.form.municipio = prod.municipio || '';
          this.form.localidad = prod.localidad || '';
          this.form.estado = prod.estado || 'Nayarit';
          this.form.telefono = prod.telefono || '';
          this.form.email = prod.email || '';
          this.form.clave = prod.clave || '';
          this.form.zona = prod.zona || '';
          this.form.nombre_rancho = predioAsociado.nombre || predioAsociado.nombre_rancho || '';
          this.form.clave_unidad_produccion = predioAsociado.upp || predioAsociado.clave_unidad_produccion || '';
          this.form.predio_localidad = predioAsociado.localidad || '';
          this.form.predio_municipio = predioAsociado.municipio || '';
          this.form.predio_domicilio = predioAsociado.domicilio || '';
          this.form.latitud = predioAsociado.latitud || '';
          this.form.longitud = predioAsociado.longitud || '';
        } else {
          this.errorMsg = 'No se pudo cargar el productor.';
        }
      }
    },
    handleCancel() {
      this.$router.push('/productores');
    },
    autoSelectZona() {
      if (!this.form.clave) return;
      const first = this.form.clave.toUpperCase()[0];
      if (first === 'A' || first === 'B') this.form.zona = first;
    },

    onClaveInput() {
      this.autoSelectZona();
      
      clearTimeout(this.claveDebounce);
      const val = (this.form.clave || '').trim();
      if (val.length < 1) {
        this.resultadosClave = [];
        this.noClaveFound = false;
        this.isSearchingClave = false;
        return;
      }

      this.isSearchingClave = true;
      this.noClaveFound = false;

      this.claveDebounce = setTimeout(async () => {
        try {
          let matches = [];
          if (this.isOnline) {
            try {
              matches = await api.searchProductorPorClave(val);
            } catch (err) {
              console.warn('Error fetching producers by key from API:', err);
            }
          }
          
          const localProds = await db.getProductores();
          const localMatches = localProds.filter(p => p.clave && p.clave.toUpperCase().startsWith(val.toUpperCase()));
          
          const seen = new Set(matches.map(m => String(m.id)));
          localMatches.forEach(lp => {
            if (!seen.has(String(lp.id))) {
              matches.push(lp);
            }
          });

          this.resultadosClave = matches.slice(0, 15);
          this.noClaveFound = matches.length === 0;
        } catch (e) {
          console.error(e);
        } finally {
          this.isSearchingClave = false;
        }
      }, 300);
    },

    selectClave(clave) {
      this.form.clave = clave;
      this.resultadosClave = [];
      this.noClaveFound = false;
      this.autoSelectZona();
    },
    async processFinalSave() {
      this.saving = true;
      this.errorMsg = '';

      const body = {
        nombre: this.form.nombre.trim(),
        apellido_paterno: this.form.apellido_paterno.trim(),
        apellido_materno: this.form.apellido_materno.trim(),
        curp: this.form.curp.toUpperCase().trim(),
        upp: this.form.upp.trim(),
        domicilio: this.form.domicilio.trim(),
        municipio: this.form.municipio.trim(),
        localidad: this.form.localidad.trim(),
        estado: this.form.estado.trim(),
        telefono: this.form.telefono.trim(),
        email: this.form.email.trim(),
        clave: this.isAdmin ? (this.form.clave || '').toUpperCase().trim() : null,
        zona: this.isAdmin ? (this.form.zona || null) : null,
      };

      try {
        if (this.isOnline) {
          await api.updateProductor(this.productorId, body);
        }

        const predios = await db.getPredios();
        predios.forEach(p => {
          if (p.productor && String(p.productor.id) === String(this.productorId)) {
            p.productor.nombre = body.nombre;
            p.productor.apellido_paterno = body.apellido_paterno;
            p.productor.apellido_materno = body.apellido_materno;
            p.productor.curp = body.curp;
            p.productor.upp = body.upp;
            p.productor.domicilio = body.domicilio;
            p.productor.municipio = body.municipio;
            p.productor.localidad = body.localidad;
            p.productor.estado = body.estado;
            p.productor.telefono = body.telefono;
            p.productor.email = body.email;
            p.productor.clave = body.clave;
            p.productor.zona = body.zona;
            if (p.nombre !== 'Sin Rancho' && this.form.nombre_rancho.trim()) {
              p.nombre = this.form.nombre_rancho.trim();
              p.upp = this.form.clave_unidad_produccion.trim();
              p.localidad = this.form.predio_localidad.trim();
              p.municipio = this.form.predio_municipio.trim();
              p.domicilio = this.form.predio_domicilio.trim();
              p.latitud = this.form.latitud;
              p.longitud = this.form.longitud;
            }
          }
        });

        await db.savePredios(predios);

        // También actualizar en la store dedicada de productores
        const productores = await db.getProductores();
        const prodIdx = productores.findIndex(p => String(p.id) === String(this.productorId));
        if (prodIdx >= 0) {
          const fullNombre = [body.nombre, body.apellido_paterno, body.apellido_materno]
            .filter(Boolean).join(' ').trim();
          productores[prodIdx].nombre = fullNombre;
          productores[prodIdx].nombreRaw = body.nombre;
          productores[prodIdx].apellido_paterno = body.apellido_paterno;
          productores[prodIdx].apellido_materno = body.apellido_materno;
          productores[prodIdx].curp = body.curp;
          productores[prodIdx].upp = body.upp;
          productores[prodIdx].domicilio = body.domicilio;
          productores[prodIdx].municipio = body.municipio;
          productores[prodIdx].localidad = body.localidad;
          productores[prodIdx].estado = body.estado;
          productores[prodIdx].telefono = body.telefono;
          productores[prodIdx].email = body.email;
          productores[prodIdx].clave = body.clave;
          productores[prodIdx].zona = body.zona;
          await db.saveProductores(productores);
        }

        this.$router.push('/productores');
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo actualizar el productor.';
      } finally {
        this.saving = false;
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  min-height: 100dvh;
}

.btn-back-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #334155;
}

.form-view-header {
  background: white;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  gap: 12px;
}

.header-left-container {
  min-width: 0;
  flex: 1;
}

.title-text-wrapper {
  min-width: 0;
}

.header-title {
  font-size: 1.1rem;
  font-weight: 700 !important;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin: 0;
}

.form-group-custom {
  margin-bottom: 16px;
}

.form-label-custom {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
}

.form-control-custom {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.95rem;
  color: #1e293b;
  background: white;
}

.btn-submit-custom, .btn-cancel-custom {
  border: none;
  font-weight: 600;
  font-size: 0.88rem;
  padding: 11px 24px;
}

.btn-submit-custom {
  background: #2563eb;
  color: #fff;
}

.btn-cancel-custom {
  background: #fff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.edit-productor-card {
  max-width: 960px;
  margin: 0 auto;
}
</style>
