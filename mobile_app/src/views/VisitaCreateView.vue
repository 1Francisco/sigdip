<template>
  <AppLayout>
      <div class="container-form-wrapper">
        <!-- Title Block -->
        <div class="welcome-header mb-4 text-start">
          <h2 class="h4 fw-bold mb-1 text-dark">{{ isEdit ? 'Editar Visita Programada' : 'Nueva Visita Programada' }}</h2>
          <p class="text-secondary small mb-0">Asigne una fecha y un veterinario a un predio</p>
        </div>

        <!-- Alerts -->
        <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0 text-start">{{ errorMsg }}</div>
        <div v-if="successMsg" class="alert alert-success shadow-sm rounded-4 border-0 text-start">{{ successMsg }}</div>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-start">
          <form @submit.prevent="saveVisita">
            
            <!-- Productor (Persona) -->
            <div class="form-group-custom">
              <label class="form-label-custom">Productor (Persona)</label>
              <select 
                v-model="selectedProductorId" 
                class="form-control-custom"
                @change="onProductorChange"
                required
              >
                <option value="">Seleccione un productor...</option>
                <option v-for="prod in productores" :key="prod.id" :value="prod.id">
                  {{ prod.nombre }} {{ prod.apellido_paterno }} {{ prod.apellido_materno }}
                </option>
              </select>
            </div>

            <!-- Predio (Rancho a Visitar) -->
            <div class="form-group-custom">
              <label class="form-label-custom">Predio (Rancho a Visitar)</label>
              <select 
                v-model="form.predio_id" 
                class="form-control-custom"
                :disabled="!selectedProductorId"
                required
              >
                <option value="" v-if="!selectedProductorId">Primero seleccione un productor...</option>
                <option value="" v-else>Seleccione un predio...</option>
                <option v-for="pred in filteredPredios" :key="pred.id" :value="pred.id">
                  {{ pred.nombre_rancho || pred.nombre }} ({{ pred.localidad }}, {{ pred.municipio }})
                </option>
              </select>
            </div>

            <!-- Fecha Programada -->
            <div class="form-group-custom">
              <label class="form-label-custom">Fecha Programada</label>
              <div class="date-input-wrapper">
                <input 
                  v-model="form.fecha_programada" 
                  type="date" 
                  class="form-control-custom form-control-date" 
                  required
                >
                <i class="bi bi-calendar date-icon"></i>
              </div>
            </div>

            <!-- Médico Veterinario Asignado -->
            <div class="form-group-custom">
              <label class="form-label-custom">Médico Veterinario Asignado</label>
              <select 
                v-model="form.veterinario_id" 
                class="form-control-custom"
                :disabled="!isAdmin"
                required
              >
                <option value="">Seleccione un médico...</option>
                <option v-for="med in medicos" :key="med.id" :value="med.id">
                  {{ med.name }}
                </option>
              </select>
            </div>

            <!-- Notas u Objetivos de la Visita -->
            <div class="form-group-custom">
              <label class="form-label-custom">Notas u Objetivos de la Visita</label>
              <textarea 
                v-model="form.observaciones" 
                class="form-control-custom" 
                rows="4" 
                placeholder="Ej. Prueba de tuberculosis en lote de 50 cabezas..."
              ></textarea>
            </div>

            <!-- Buttons Row -->
            <div class="d-flex align-items-center gap-3 mt-4">
              <button 
                type="button" 
                class="btn-cancel-custom flex-grow-1" 
                @click="$router.push('/visitas')"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                class="btn-save-custom flex-grow-1" 
                :disabled="saving"
              >
                <span>{{ saving ? 'Programando...' : (isEdit ? 'Guardar Datos' : 'Programar Visita') }}</span>
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
import db from '../services/db.js';

export default {
  name: 'VisitaCreateView',
  components: { AppLayout },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      saving: false,
      isEdit: false,
      currentId: null,
      productores: [],
      predios: [],
      medicos: [],
      selectedProductorId: '',
      form: {
        codigo: null,
        predio_id: '',
        fecha_programada: '',
        veterinario_id: '',
        observaciones: ''
      },
      successMsg: '',
      errorMsg: ''
    };
  },
  computed: {
    filteredPredios() {
      if (!this.selectedProductorId) return [];
      return this.predios.filter(p => p.productor && String(p.productor.id) === String(this.selectedProductorId));
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

    if (!this.isAdmin && user) {
      this.form.veterinario_id = user.id;
    }

    await this.loadAllData();

    // Check if we are in Edit Mode
    if (this.$route.params.id) {
      this.isEdit = true;
      this.currentId = this.$route.params.id;
      await this.loadVisitaForEdit();
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
    async loadAllData() {
      try {
        const [resPredios, resProductores, resMedicos] = await Promise.all([
          api.getPredios(),
          api.getProductores(),
          api.getMedicos()
        ]);
        this.predios = resPredios.data || [];
        this.productores = resProductores.data || [];
        this.medicos = resMedicos.data || [];

        // Save local copy for offline support
        await Promise.all([
          db.savePredios(this.predios),
          db.saveProductores(this.productores),
          db.saveMedicos(this.medicos)
        ]);
      } catch (e) {
        console.warn('Error fetching data from API. Loading from local indexedDB.', e);
        this.predios = await db.getPredios();
        this.productores = await db.getProductores();
        this.medicos = await db.getMedicos();

        // Fallback: extraer productores únicos desde los predios cacheados
        if ((!this.productores || this.productores.length === 0) && this.predios.length > 0) {
          const map = new Map();
          this.predios.forEach(p => {
            if (p.productor && p.productor.id) {
              map.set(String(p.productor.id), p.productor);
            }
          });
          this.productores = Array.from(map.values());
        }
      }
    },
    async loadVisitaForEdit() {
      try {
        const res = await api.getVisita(this.currentId);
        const visita = res.data || {};
        this.form.predio_id = visita.predio_id || '';
        this.form.fecha_programada = visita.fecha_programada || '';
        this.form.veterinario_id = visita.veterinario_id || '';
        this.form.observaciones = visita.observaciones || '';
        this.form.codigo = visita.codigo || null;
        
        // Find productor id from selected predio
        const pred = this.predios.find(p => p.id === visita.predio_id);
        if (pred && pred.productor) {
          this.selectedProductorId = pred.productor.id;
        }
      } catch (e) {
        console.error('Error loading visita for edit:', e);
        this.errorMsg = 'No se pudo cargar la visita para editar.';
      }
    },
    onProductorChange() {
      // Clear selected predio if producer changes
      this.form.predio_id = '';
    },
    async saveVisita() {
      if (!this.form.predio_id || !this.form.fecha_programada || !this.form.veterinario_id) {
        this.errorMsg = 'Por favor complete todos los campos obligatorios.';
        return;
      }

      if (!this.isEdit) {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        const fechaProg = new Date(this.form.fecha_programada);
        fechaProg.setHours(0, 0, 0, 0);

        if (fechaProg.getTime() < hoy.getTime()) {
          this.errorMsg = 'La fecha programada no puede ser anterior a la fecha de hoy.';
          return;
        }
      }

      this.saving = true;
      this.errorMsg = '';
      this.successMsg = '';

      try {
        if (this.isEdit) {
          await api.updateVisita(this.currentId, {
            predio_id: this.form.predio_id,
            fecha_programada: this.form.fecha_programada,
            veterinario_id: this.form.veterinario_id,
            observaciones: this.form.observaciones,
            codigo: this.form.codigo || null
          });
          this.successMsg = 'Visita programada actualizada con éxito.';
        } else {
          // Generate unique code based on doctor name and date
          const user = api.getCurrentUser();
          const vet = this.medicos.find(m => String(m.id) === String(this.form.veterinario_id)) || user;
          const vetName = vet ? (vet.name || 'VET') : 'VET';
          
          // Sanitize veterinarian name to uppercase, letters/numbers and underscores only
          const cleanName = vetName
            .toUpperCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^A-Z0-9]/g, "_")
            .replace(/_+/g, "_")
            .replace(/(^_|_$)/g, "");

          // Format date as YYYYMMDD
          const dateStr = this.form.fecha_programada.replace(/-/g, "");

          // Short random string to ensure uniqueness
          const randSuffix = Math.random().toString(36).substring(2, 6).toUpperCase();

          let generatedCodigo = `V-${cleanName}-${dateStr}-${randSuffix}`;

          if (this.isOnline) {
            // Check for duplicate codigo on server
            const check = await api.checkVisitaCodigo(generatedCodigo);
            if (check.exists) {
              const msg =
                `El código ${generatedCodigo} ya está registrado en el servidor.\n\n` +
                `Fecha: ${check.visita?.fecha_programada || '—'}\n` +
                `Predio: ${check.visita?.predio?.nombre_rancho || '—'}\n` +
                `Médico: ${check.visita?.veterinario?.name || '—'}\n\n` +
                `¿Desea crear la visita de todas formas? Se generará un código nuevo.`;
              if (!confirm(msg)) {
                this.saving = false;
                return;
              }
              // Regenerate with a new random suffix to avoid collision
              const newRand = Math.random().toString(36).substring(2, 8).toUpperCase();
              generatedCodigo = `V-${cleanName}-${dateStr}-${newRand}`;
            }

            await api.createVisita({
              codigo: generatedCodigo,
              predio_id: this.form.predio_id,
              fecha_programada: this.form.fecha_programada,
              veterinario_id: this.form.veterinario_id,
              observaciones: this.form.observaciones
            });
            this.successMsg = `Visita programada con éxito.\nCódigo: ${generatedCodigo}`;
          } else {
            // Offline: save locally for later sync
            await db.saveVisitaPendiente({
              codigo: generatedCodigo,
              predio_id: this.form.predio_id,
              fecha_programada: this.form.fecha_programada,
              veterinario_id: this.form.veterinario_id,
              observaciones: this.form.observaciones,
              _pendiente: true,
              _created_at: new Date().toISOString()
            });
            this.successMsg = `Visita guardada localmente.\nSe sincronizará cuando haya conexión.\nCódigo: ${generatedCodigo}`;
          }
        }

        setTimeout(() => {
          this.$router.push('/visitas');
        }, 1500);
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo programar la visita.';
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
  height: 28px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px;
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

.connectivity-badge-pill .dot-indicator {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
}

.avatar-circle {
  width: 34px;
  height: 34px;
  border: 1.5px solid #2563eb;
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

.welcome-header h2 {
  font-size: 1.7rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.welcome-header p {
  font-size: 0.95rem;
  color: #64748b;
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
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  font-size: 0.95rem;
  color: #1e293b;
  background: white;
  outline: none;
  transition: all 0.2s ease;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 18px center;
  background-size: 16px;
  padding-right: 45px;
}

.form-control-custom:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
}

.form-control-custom:disabled {
  background-color: #f1f5f9 !important;
  color: #94a3b8 !important;
  border-color: #e2e8f0 !important;
  cursor: not-allowed;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
}

.form-group-custom:focus-within .form-label-custom {
  color: #2563eb;
}

/* Date input custom styles */
.date-input-wrapper {
  position: relative;
  width: 100%;
}

.form-control-date {
  padding-right: 48px;
  background-image: none; /* Remove chevron icon for date pickers */
}

.date-icon {
  position: absolute;
  right: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: #0f172a;
  font-size: 1.25rem;
  pointer-events: none;
}

.form-control-date::-webkit-calendar-picker-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: auto;
  height: auto;
  color: transparent;
  background: transparent;
  cursor: pointer;
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
}

.btn-save-custom:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-save-custom:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel-custom {
  background: #f1f5f9;
  color: #0f172a;
  border: none;
}

.btn-cancel-custom:hover {
  background: #e2e8f0;
}

/* Bottom Nav bar */

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
