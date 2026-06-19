<template>
  <AppLayout>
      <div class="container-form-wrapper">
        <!-- Title Block -->
        <div class="welcome-header mb-4 text-start">
          <h2 class="h4 fw-bold mb-1 text-dark">Registrar Médico Verificador</h2>
          <p class="text-secondary small mb-0">Dar de alta a un nuevo elemento para la aplicación móvil</p>
        </div>

        <!-- Alerts -->
        <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0 text-start">{{ errorMsg }}</div>
        <div v-if="successMsg" class="alert alert-success shadow-sm rounded-4 border-0 text-start">{{ successMsg }}</div>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-start">
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
              <label class="form-label-custom">Contraseña</label>
              <input 
                v-model="form.password" 
                type="password" 
                class="form-control-custom" 
                placeholder="Mínimo 8 caracteres" 
                required 
                minlength="8"
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Confirmar Contraseña</label>
              <input 
                v-model="form.password_confirmation" 
                type="password" 
                class="form-control-custom" 
                placeholder="Mínimo 8 caracteres" 
                required 
                minlength="8"
              >
            </div>

            <!-- Buttons Row -->
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
                <span>{{ saving ? 'Guardando...' : 'Guardar Médico' }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Information Box -->
        <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-start border-info-box">
          <div class="d-flex align-items-center gap-2 text-primary mb-3">
            <i class="bi bi-info-circle fs-5"></i>
            <h5 class="mb-0 fw-bold fs-5">Información Importante</h5>
          </div>
          <p class="text-secondary small mb-3">
            Al crear esta cuenta, el usuario tendrá el rol automático de <strong>Médico de Campo</strong>.
          </p>
          <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
            <li class="d-flex align-items-start gap-2 text-secondary small">
              <i class="bi bi-check2-circle text-success fs-5 flex-shrink-0"></i>
              <span>Podrá iniciar sesión en la Aplicación Móvil.</span>
            </li>
            <li class="d-flex align-items-start gap-2 text-secondary small">
              <i class="bi bi-check2-circle text-success fs-5 flex-shrink-0"></i>
              <span>Podrá sincronizar datos y crear inspecciones offline.</span>
            </li>
          </ul>
        </div>
      </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import api from '../services/api.js';

export default {
  name: 'MedicoCreateView',
  components: { AppLayout },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      saving: false,
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      successMsg: '',
      errorMsg: ''
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

    // Solo permitir acceso a Administradores
    if (!this.isAdmin) {
      alert('Acceso restringido. Solo administradores pueden registrar personal médico.');
      this.$router.push('/dashboard');
      return;
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
    async saveMedico() {
      this.saving = true;
      this.errorMsg = '';
      this.successMsg = '';

      if (this.form.password !== this.form.password_confirmation) {
        this.errorMsg = 'Las contraseñas no coinciden.';
        this.saving = false;
        return;
      }

      try {
        await api.storeMedico({
          name: this.form.name,
          email: this.form.email,
          password: this.form.password
        });
        this.successMsg = 'Médico Verificador registrado correctamente.';
        setTimeout(() => {
          this.$router.push('/medicos');
        }, 1500);
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo registrar al médico.';
        this.saving = false;
      }
    }
  }
};
</script>

<style scoped>
/* App Layout Structure */
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

/* Header Grid Layout */
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

/* Connectivity Pill Badge */
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

/* Sidebar Styles */
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

/* Page Containers */
.container-form-wrapper {
  max-width: 600px;
  margin: 0 auto;
  width: 100%;
}

.welcome-header {
  padding: 1.25rem 0 0.5rem 0;
}

/* Card Outer Flat Mobile and Rounded Form */
.card-outer-mobile-flat {
  background: white !important;
  border-radius: 24px !important;
  padding: 1.75rem !important;
  border: 1px solid rgba(226, 232, 240, 0.8) !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.03), 0 8px 10px -6px rgba(0, 0, 0, 0.03) !important;
}

/* Form Styling */
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

/* Custom Buttons */
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

/* Info Box */
.border-info-box {
  border: 1.5px solid #eff6ff !important;
  border-radius: 24px !important;
}

.border-info-box h5 {
  color: #2563eb;
  font-weight: 700 !important;
}

.border-info-box p {
  font-size: 0.95rem;
  color: #475569;
  line-height: 1.5;
}

.border-info-box ul li {
  font-size: 0.92rem;
  color: #475569;
}

.border-info-box ul li i {
  color: #10b981 !important; /* Green check circle */
}



/* Responsive configurations */
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
}
</style>
