<template>
  <AppLayout>
      <div class="container-form-wrapper">
        <div class="welcome-header mb-4 text-start">
          <h2 class="h4 fw-bold mb-1 text-dark">{{ isEdit ? 'Editar Animal' : 'Registrar Animal' }}</h2>
          <p class="text-secondary small mb-0">{{ isEdit ? 'Modificar datos del animal' : 'Dar de alta un nuevo animal en el sistema' }}</p>
        </div>

        <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0 text-start">{{ errorMsg }}</div>
        <div v-if="successMsg" class="alert alert-success shadow-sm rounded-4 border-0 text-start">{{ successMsg }}</div>

        <div v-if="loadingData" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-2 text-muted small">{{ isEdit ? 'Cargando datos del animal...' : 'Cargando...' }}</p>
        </div>

        <div v-else class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-start">
          <form @submit.prevent="saveAnimal">
            <div class="form-group-custom">
              <label class="form-label-custom">Número Arete SINIIGA</label>
              <input
                v-model="form.numero_arete_siniiga"
                type="text"
                class="form-control-custom"
                placeholder="Ej. MX-123-456-789"
                required
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Raza</label>
              <input
                v-model="form.raza"
                type="text"
                class="form-control-custom"
                placeholder="Ej. Angus, Brangus, etc."
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Sexo</label>
              <select
                v-model="form.sexo"
                class="form-control-custom"
              >
                <option value="">Seleccionar...</option>
                <option value="Macho">Macho</option>
                <option value="Hembra">Hembra</option>
              </select>
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Edad (años)</label>
              <input
                v-model="form.edad"
                type="number"
                class="form-control-custom"
                placeholder="0"
                min="0"
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Predio</label>
              <select
                v-model="form.predio_id"
                class="form-control-custom"
                required
              >
                <option value="">Seleccionar predio...</option>
                <option
                  v-for="predio in predios"
                  :key="predio.id"
                  :value="predio.id"
                >
                  {{ predio.nombre_rancho || predio.nombre }} ({{ predio.clave_unidad_produccion || predio.id }})
                </option>
              </select>
            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
              <button
                type="button"
                class="btn-cancel-custom flex-grow-1"
                @click="$router.push('/animales')"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="btn-save-custom flex-grow-1"
                :disabled="saving"
              >
                <i class="bi bi-box-arrow-in-down"></i>
                <span>{{ saving ? 'Guardando...' : (isEdit ? 'Actualizar Animal' : 'Guardar Animal') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import api from '../services/api.js';

export default {
  name: 'AnimalFormView',
  components: { AppLayout },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      saving: false,
      loadingData: true,
      isEdit: false,
      form: {
        numero_arete_siniiga: '',
        raza: '',
        sexo: '',
        edad: '',
        predio_id: ''
      },
      predios: [],
      successMsg: '',
      errorMsg: '',
      animalId: null
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;
    this.animalId = this.$route.params.id;
    this.isEdit = !!this.animalId;

    if (!this.isAdmin) {
      alert('Acceso restringido. Solo administradores pueden gestionar animales.');
      this.$router.push('/dashboard');
      return;
    }

    await this.loadPredios();

    if (this.isEdit) {
      if (!this.animalId) {
        this.errorMsg = 'ID de animal no válido.';
        this.loadingData = false;
        return;
      }
      await this.loadAnimal();
    } else {
      this.loadingData = false;
    }

    this._onWindowOnline = () => this.isOnline = true;
    this._onWindowOffline = () => this.isOnline = false;
    window.addEventListener('online', this._onWindowOnline);
    window.addEventListener('offline', this._onWindowOffline);
  },
  beforeUnmount() {
    window.removeEventListener('online', this._onWindowOnline);
    window.removeEventListener('offline', this._onWindowOffline);
  },
  methods: {
    async loadPredios() {
      try {
        const res = await api.getPredios();
        this.predios = res.data || [];
      } catch (e) {
        this.errorMsg = e.message || 'No se pudieron cargar los predios.';
      }
    },
    async loadAnimal() {
      this.loadingData = true;
      this.errorMsg = '';
      try {
        const res = await api.getAnimal(this.animalId);
        if (res.data) {
          this.form.numero_arete_siniiga = res.data.numero_arete_siniiga || '';
          this.form.raza = res.data.raza || '';
          this.form.sexo = res.data.sexo || '';
          this.form.edad = res.data.edad ?? '';
          this.form.predio_id = res.data.predio_id || '';
        }
      } catch (e) {
        this.errorMsg = e.message || 'No se pudieron cargar los datos del animal.';
      } finally {
        this.loadingData = false;
      }
    },
    async saveAnimal() {
      this.saving = true;
      this.errorMsg = '';
      this.successMsg = '';

      try {
        if (this.isEdit) {
          await api.updateAnimal(this.animalId, {
            numero_arete_siniiga: this.form.numero_arete_siniiga,
            raza: this.form.raza,
            sexo: this.form.sexo,
            edad: this.form.edad,
            predio_id: this.form.predio_id
          });
        } else {
          await api.createAnimal({
            numero_arete_siniiga: this.form.numero_arete_siniiga,
            raza: this.form.raza,
            sexo: this.form.sexo,
            edad: this.form.edad,
            predio_id: this.form.predio_id
          });
        }
        this.successMsg = this.isEdit ? 'Animal actualizado correctamente.' : 'Animal registrado correctamente.';
        setTimeout(() => {
          this.$router.push('/animales');
        }, 1500);
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo guardar el animal.';
        this.saving = false;
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.bg-light-page {
  background-color: #f8fafc !important;
}

.main-content {
  background-color: #f8fafc !important;
}

.mobile-header {
  min-height: 72px;
  height: auto;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  background: #fff;
  padding: 12px 1rem;
  position: sticky;
  top: 0;
  z-index: 50;
  border-bottom: 1px solid #eef2f7;
}

.header-left {
  display: flex;
  justify-content: flex-start;
  align-items: center;
}

.header-right {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
}

.header-back-btn {
  width: 42px;
  height: 42px;
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #111827;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.header-back-btn:active {
  background-color: #f1f5f9;
}

.brand-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #2563eb;
  font-size: 1.1rem;
}

.brand-icon,
.sidebar-logo {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.connectivity-badge-pill {
  width: 44px;
  height: 28px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  border: 1px solid transparent;
}

.connectivity-badge-pill.online {
  background-color: #d1fae5;
  color: #10b981;
  border-color: #a7f3d0;
}

.connectivity-badge-pill.offline {
  background-color: #fee2e2;
  color: #ef4444;
  border-color: #fca5a5;
}

.avatar-circle {
  width: 34px;
  height: 34px;
  border: 1.5px solid #cbd5e1;
  background: transparent;
  color: #2563eb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 50%;
  font-size: 1.1rem;
  transition: all 0.2s ease;
}

.avatar-circle:active {
  background-color: #f1f5f9;
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.38);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  z-index: 90;
}

.sidebar-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 270px;
  height: 100dvh;
  background: #fff;
  z-index: 100;
  transform: translateX(-100%);
  transition: transform 0.22s ease;
  box-shadow: 20px 0 40px rgba(15, 23, 42, 0.15);
  display: flex;
  flex-direction: column;
}

.sidebar.active {
  transform: translateX(0);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 1rem 1rem 0.8rem;
  font-weight: 800;
  color: #2563eb;
  border-bottom: 1px solid #eef2f7;
}

.btn-close-sidebar {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #334155;
}

.nav {
  padding: 0.8rem 0.4rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.nav-link {
  padding: 0.85rem 1rem;
  border-radius: 0.8rem;
  color: #334155;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  text-decoration: none;
}

.nav-link.active {
  background: #2563eb;
  color: white;
}

.logout-btn {
  margin-top: auto;
}

.container-form-wrapper {
  max-width: 600px;
  margin: 0 auto;
  width: 100%;
}

.welcome-header {
  padding: 1.25rem 0 0.5rem 0;
}

.card-outer-mobile-flat {
  background: white !important;
  border-radius: 24px !important;
  padding: 1.75rem !important;
  border: 1px solid rgba(226, 232, 240, 0.8) !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.03), 0 8px 10px -6px rgba(0, 0, 0, 0.03) !important;
}

.form-group-custom {
  margin-bottom: 1.5rem;
}

.form-label-custom {
  display: block;
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.form-control-custom {
  width: 100%;
  padding: 14px 18px;
  border: 1.5px solid #cbd5e1;
  border-radius: 14px;
  font-size: 0.95rem;
  color: #1e293b;
  background: white;
  outline: none;
  transition: all 0.2s ease;
}

.form-control-custom:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
}

.form-group-custom:focus-within .form-label-custom {
  color: #2563eb;
}

.btn-save-custom,
.btn-cancel-custom {
  border: none;
  border-radius: 16px;
  padding: 14px 20px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-save-custom {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-save-custom:hover:not(:disabled) {
  background: #1d4ed8;
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.btn-save-custom:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel-custom {
  background: #f8fafc;
  color: #1e293b;
  border: 1.5px solid #cbd5e1;
}

.btn-cancel-custom:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

@media (min-width: 992px) {
  .mobile-header,
  .bottom-nav {
    display: none;
  }

  .main-content {
    margin-left: 270px;
    padding: 2.5rem !important;
    min-height: 100dvh;
  }

  .welcome-header {
    padding: 0;
    margin-bottom: 2rem;
  }
}

@media (max-width: 991.98px) {
  .sidebar {
    display: flex;
  }

  .main-content {
    padding: 1rem;
    padding-bottom: 90px;
  }

  .card-outer-mobile-flat {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
  }
}
</style>
