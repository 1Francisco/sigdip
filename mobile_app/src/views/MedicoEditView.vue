<template>
  <AppLayout>
      <div class="container-form-wrapper">
        <div class="welcome-header mb-4 text-start">
          <h2 class="h4 fw-bold mb-1 text-dark">Editar Médico Verificador</h2>
          <p class="text-secondary small mb-0">Modificar datos del elemento médico</p>
        </div>

        <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0 text-start">{{ errorMsg }}</div>
        <div v-if="successMsg" class="alert alert-success shadow-sm rounded-4 border-0 text-start">{{ successMsg }}</div>

        <div v-if="loadingData" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-2 text-muted small">Cargando datos del médico...</p>
        </div>

        <div v-else class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-start">
          <form @submit.prevent="saveMedico">
            <div class="form-group-custom">
              <label class="form-label-custom">Nombre Completo</label>
              <input 
                v-model="form.name" 
                type="text" 
                class="form-control-custom" 
                placeholder="Ej. Dr. Juan Pérez" 
                required
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Correo Electrónico (Para iniciar sesión)</label>
              <input 
                v-model="form.email" 
                type="email" 
                class="form-control-custom" 
                placeholder="juan.perez@sigdip.com" 
                required
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Nueva Contraseña <small class="text-muted">(dejar en blanco para mantener la actual)</small></label>
              <input 
                v-model="form.password" 
                type="password" 
                class="form-control-custom" 
                placeholder="Mínimo 8 caracteres" 
                minlength="8"
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Confirmar Nueva Contraseña</label>
              <input 
                v-model="form.password_confirmation" 
                type="password" 
                class="form-control-custom" 
                placeholder="Repetir contraseña" 
                minlength="8"
              >
            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
              <button 
                type="button" 
                class="btn-cancel-custom flex-grow-1" 
                @click="$router.push('/medicos')"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                class="btn-save-custom flex-grow-1" 
                :disabled="saving"
              >
                <i class="bi bi-box-arrow-in-down"></i>
                <span>{{ saving ? 'Guardando...' : 'Actualizar Médico' }}</span>
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
  name: 'MedicoEditView',
  components: { AppLayout },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      saving: false,
      loadingData: true,
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      successMsg: '',
      errorMsg: '',
      medicoId: null
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;
    this.medicoId = this.$route.params.id;

    if (!this.isAdmin) {
      alert('Acceso restringido. Solo administradores pueden gestionar personal médico.');
      this.$router.push('/dashboard');
      return;
    }

    if (!this.medicoId) {
      this.errorMsg = 'ID de médico no válido.';
      this.loadingData = false;
      return;
    }

    await this.loadMedico();

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
    async loadMedico() {
      this.loadingData = true;
      this.errorMsg = '';
      try {
        const res = await api.getMedico(this.medicoId);
        if (res.data) {
          this.form.name = res.data.name;
          this.form.email = res.data.email;
        }
      } catch (e) {
        this.errorMsg = e.message || 'No se pudieron cargar los datos del médico.';
      } finally {
        this.loadingData = false;
      }
    },
    async saveMedico() {
      this.saving = true;
      this.errorMsg = '';
      this.successMsg = '';

      if (this.form.password || this.form.password_confirmation) {
        if (this.form.password !== this.form.password_confirmation) {
          this.errorMsg = 'Las contraseñas no coinciden.';
          this.saving = false;
          return;
        }
        if (this.form.password.length < 8) {
          this.errorMsg = 'La contraseña debe tener al menos 8 caracteres.';
          this.saving = false;
          return;
        }
      }

      try {
        const payload = {
          name: this.form.name,
          email: this.form.email,
        };
        if (this.form.password) {
          payload.password = this.form.password;
        }
        await api.updateMedico(this.medicoId, payload);
        this.successMsg = 'Médico actualizado correctamente.';
        setTimeout(() => {
          this.$router.push('/medicos');
        }, 1500);
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo actualizar al médico.';
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

.form-group-custom {
  margin-bottom: 16px;
}

.form-label-custom {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: #334155;
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

.btn-cancel-custom {
  padding: 12px 24px;
  border: 1.5px solid #cbd5e1;
  background: #fff;
  color: #334155;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel-custom:active {
  background-color: #f1f5f9;
}

.btn-save-custom {
  padding: 12px 24px;
  border: none;
  background: #2563eb;
  color: white;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-save-custom:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-save-custom:active:not(:disabled) {
  background-color: #1d4ed8;
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

  .card-outer-mobile-flat {
    max-width: 600px;
    margin: 0 auto;
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
