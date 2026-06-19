<template>
  <div class="app-container bg-light-page">
    <header class="form-view-header shadow-sm">
      <div class="d-flex align-items-center gap-2 gap-sm-3 header-left-container">
        <button class="btn-back-circle shadow-sm" @click="handleCancel">
          <i class="bi bi-arrow-left fs-5"></i>
        </button>
        <div class="text-start title-text-wrapper">
          <h1 class="h5 fw-bold mb-0 text-dark header-title">
            {{ isEditing ? 'Editar Unidad de Producción' : 'Registrar Unidad de Producción' }}
          </h1>
          <div class="subtitle text-secondary small d-none d-sm-block">
            Configure la ubicación y datos técnicos del predio
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
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white create-predio-card">
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
            <div class="text-start">
              <div class="fw-bold fs-4 text-dark">
                {{ isEditing ? 'Editar Unidad de Producción' : 'Registrar Unidad de Producción' }}
              </div>
              <div class="text-secondary small">Configure la ubicación y datos técnicos del predio</div>
            </div>
            <span class="badge rounded-pill px-3 py-2" :class="isOnline ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
              {{ isOnline ? 'Conectado' : 'Offline' }}
            </span>
          </div>

          <div v-if="errorMsg" class="alert alert-danger border-0 shadow-sm rounded-4">
            {{ errorMsg }}
          </div>

          <div v-if="successMsg" class="alert alert-success border-0 shadow-sm rounded-4">
            {{ successMsg }}
          </div>

          <form @submit.prevent="savePredio">
            <div class="row g-3 text-start">
              <div class="col-12">
                <div class="form-group-custom">
                  <label class="form-label-custom">Nombre del Rancho / Predio</label>
                  <input
                    v-model="form.nombre_rancho"
                    type="text"
                    class="form-control-custom"
                    placeholder="Ej. El Salto del Aguacate 1"
                    required
                  >
                </div>
              </div>

              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-3 flex-wrap">
                  <label class="form-label-custom mb-0">Productor Responsable</label>
                  <button type="button" class="link-button" @click="openProductorModal">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo Productor
                  </button>
                </div>
                <select v-model="form.productor_id" class="form-control-custom" :disabled="isEditing" required>
                  <option value="">Seleccione un productor...</option>
                  <option
                    v-for="p in productores"
                    :key="p.id"
                    :value="String(p.id)"
                  >
                    {{ formatProductorName(p) }}
                  </option>
                </select>
                <div v-if="selectedProductorName" class="form-text text-primary small mt-2">
                  <i class="bi bi-info-circle me-1"></i>
                  Asignando rancho a este productor: <strong>{{ selectedProductorName }}</strong>
                </div>
              </div>

              <div class="col-12">
                <div class="form-group-custom">
                  <label class="form-label-custom">Clave de Unidad de Producción (UPP)</label>
                  <input
                    v-model="form.clave_unidad_produccion"
                    type="text"
                    class="form-control-custom font-mono"
                    placeholder="Ej. 180104330002"
                    required
                  >
                </div>
              </div>

              <div class="col-12 mb-1">
                <button
                  type="button"
                  class="gps-button"
                  :disabled="gpsLoading"
                  @click="detectLocation"
                >
                  <i class="bi bi-geo-alt-fill me-2"></i>
                  {{ gpsLoading ? 'Detectando ubicación...' : 'Detectar Ubicación Actual (GPS)' }}
                </button>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">Latitud</label>
                  <input
                    v-model="form.latitud"
                    type="text"
                    class="form-control-custom font-mono"
                    placeholder="Ej. 21.948694"
                  >
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">Longitud</label>
                  <input
                    v-model="form.longitud"
                    type="text"
                    class="form-control-custom font-mono"
                    placeholder="Ej. -105.298320"
                  >
                </div>
              </div>

              <div class="col-12">
                <div class="form-group-custom">
                  <label class="form-label-custom">Domicilio del Predio</label>
                  <input
                    v-model="form.domicilio"
                    type="text"
                    class="form-control-custom"
                    placeholder="Ej. A 2 km sobre el arroyo"
                  >
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">Municipio</label>
                  <input
                    v-model="form.municipio"
                    type="text"
                    class="form-control-custom"
                    placeholder="Rosamorada"
                  >
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom">
                  <label class="form-label-custom">Localidad / Población</label>
                  <input
                    v-model="form.localidad"
                    type="text"
                    class="form-control-custom"
                    placeholder="San Juan Corapan"
                    required
                  >
                </div>
              </div>
            </div>

            <div v-if="isEditing" class="producer-edit-box mt-2">
              <div class="producer-edit-title">
                <i class="bi bi-person-plus-fill me-2"></i>
                Datos del Productor Responsable
              </div>
              <div class="producer-edit-subtitle">
                Completa o ajusta la información del productor asociado a esta unidad de producción.
              </div>

              <div class="row g-3 text-start mt-1">
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Nombre(s)</label>
                    <input v-model="productorForm.nombre" type="text" class="form-control-custom" placeholder="Ej. Pepito">
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Apellido Paterno</label>
                    <input v-model="productorForm.apellido_paterno" type="text" class="form-control-custom" placeholder="Ej. Tejeda">
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Apellido Materno</label>
                    <input v-model="productorForm.apellido_materno" type="text" class="form-control-custom" placeholder="Ej. Figueroa">
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">CURP</label>
                    <input v-model="productorForm.curp" type="text" class="form-control-custom font-mono" maxlength="18" style="text-transform: uppercase;">
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">UPP (Productor)</label>
                    <input v-model="productorForm.upp" type="text" class="form-control-custom" placeholder="Clave UPP Personal">
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Domicilio Completo</label>
                    <input v-model="productorForm.domicilio" type="text" class="form-control-custom" placeholder="Calle, Número, Colonia">
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Municipio</label>
                    <input v-model="productorForm.municipio" type="text" class="form-control-custom">
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Localidad</label>
                    <input v-model="productorForm.localidad" type="text" class="form-control-custom">
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Estado</label>
                    <input v-model="productorForm.estado" type="text" class="form-control-custom bg-light" readonly tabindex="-1">
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Teléfono</label>
                    <input v-model="productorForm.telefono" type="tel" class="form-control-custom" placeholder="Ej. 3111129405">
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Correo Electrónico</label>
                    <input v-model="productorForm.email" type="email" class="form-control-custom" placeholder="ejemplo@correo.com">
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top border-slate-100">
              <button type="button" class="btn-cancel-custom rounded-pill px-4" @click="handleCancel">
                Cancelar
              </button>
              <button type="submit" class="btn-submit-custom rounded-pill px-4" :disabled="saving">
                {{ saving ? 'Guardando...' : (isEditing ? 'Actualizar Predio' : 'Guardar Predio') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>

    <!-- MODAL REGISTRAR NUEVO PRODUCTOR (POPUP DENTRO DE NUEVO PREDIO) -->
    <div v-if="showProductorModal" class="modal-overlay" @click.self="showProductorModal = false">
      <div class="modal-card" style="max-width: 800px; width: 95%;">
        <div class="modal-header text-white border-0 py-3" style="background: #2563eb; color: white !important;">
          <span class="modal-title text-white fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-person-plus-fill fs-5"></i> Registrar Nuevo Productor
          </span>
          <button class="btn-close-modal border-0 bg-transparent text-white d-flex align-items-center justify-content-center" @click="showProductorModal = false" style="color: white !important; font-size: 1.1rem; width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 50%;">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body p-4 text-start">
          <div class="alert bg-info-subtle border-0 shadow-sm rounded-4 mb-4 text-info-emphasis d-flex align-items-center gap-2" style="font-size: 0.85rem; background-color: #e0f2fe !important; color: #0369a1 !important;">
            <i class="bi bi-info-circle-fill"></i>
            <span>Complete los datos básicos para registrar al productor y seleccionarlo automáticamente.</span>
          </div>

          <div v-if="modalErrorMsg" class="alert alert-danger border-0 shadow-sm rounded-4 mb-3">
            {{ modalErrorMsg }}
          </div>

          <form @submit.prevent="saveProductorAjax">
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Nombre(s) <span class="text-danger">*</span></label>
                  <input v-model="newProductor.nombre" type="text" class="form-control-custom" placeholder="Ej. Juan" required>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Apellido Paterno <span class="text-danger">*</span></label>
                  <input v-model="newProductor.apellido_paterno" type="text" class="form-control-custom" placeholder="Ej. Pérez" required>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Apellido Materno</label>
                  <input v-model="newProductor.apellido_materno" type="text" class="form-control-custom" placeholder="Ej. Gómez">
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">CURP</label>
                  <input v-model="newProductor.curp" type="text" class="form-control-custom font-mono" placeholder="18 caracteres" maxlength="18" style="text-transform: uppercase;">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">UPP (Productor)</label>
                  <input v-model="newProductor.upp" type="text" class="form-control-custom" placeholder="Clave UPP Personal">
                </div>
              </div>

              <div class="col-12">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Domicilio Completo</label>
                  <input v-model="newProductor.domicilio" type="text" class="form-control-custom" placeholder="Calle, Número, Colonia">
                </div>
              </div>

              <div class="col-12 col-md-4">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Municipio</label>
                  <input v-model="newProductor.municipio" type="text" class="form-control-custom" placeholder="Ej. Rosamorada">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Localidad</label>
                  <input v-model="newProductor.localidad" type="text" class="form-control-custom" placeholder="Ej. San Juan">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Estado</label>
                  <input v-model="newProductor.estado" type="text" class="form-control-custom bg-light" placeholder="Nayarit">
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Teléfono</label>
                  <input v-model="newProductor.telefono" type="tel" class="form-control-custom" placeholder="Ej. 3111129405">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="form-group-custom mb-0">
                  <label class="form-label-custom">Correo Electrónico</label>
                  <input v-model="newProductor.email" type="email" class="form-control-custom" placeholder="ejemplo@correo.com">
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer border-0 p-3 d-flex justify-content-center gap-3 bg-white" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
          <button type="button" class="btn-cancel-custom rounded-pill px-4 py-2 fw-bold" @click="showProductorModal = false" style="background-color: #f1f5f9; color: #475569; border: none;">
            Cancelar
          </button>
          <button type="button" class="btn-submit-custom rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2" :disabled="modalSaving" @click="saveProductorAjax" style="background-color: #2563eb; color: white;">
            <i class="bi bi-box-arrow-in-down" v-if="!modalSaving"></i>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-else></span>
            {{ modalSaving ? 'Guardando...' : 'Guardar y Seleccionar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Geolocation } from '@capacitor/geolocation';
import { Network } from '@capacitor/network';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'PredioCreateView',
  data() {
    return {
      userName: '',
      isOnline: true,
      networkListener: null,
      saving: false,
      gpsLoading: false,
      errorMsg: '',
      successMsg: '',
      productores: [],
      predioId: null,
      productorIdOriginal: null,
      showProductorModal: false,
      modalSaving: false,
      modalErrorMsg: '',
      newProductor: {
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
        email: ''
      },
      form: {
        nombre_rancho: '',
        productor_id: '',
        clave_unidad_produccion: '',
        latitud: '',
        longitud: '',
        domicilio: '',
        municipio: '',
        localidad: 'General'
      },
      productorForm: {
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
        email: ''
      }
    };
  },
  computed: {
    isEditing() {
      return !!this.predioId;
    },
    selectedProductorName() {
      const productor = this.productores.find(p => String(p.id) === String(this.form.productor_id));
      if (!productor) return '';
      return this.formatProductorName(productor);
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.predioId = this.$route.params.id || this.$route.query.predio_id || null;
    await this.loadProductores();
    this.prefillFromQuery();
    await this.loadPredioIfEditing();

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
    async loadProductores() {
      try {
        const res = await api.getProductores();
        this.productores = res.data || [];
      } catch (e) {
        this.productores = (await db.getPredios())
          .map(predio => predio.productor)
          .filter(Boolean);
      }
    },
    prefillFromQuery() {
      const productorId = this.$route.query.productor_id;
      if (productorId) {
        this.form.productor_id = String(productorId);
      }
    },
    async loadPredioIfEditing() {
      if (!this.predioId) return;

      try {
        const predios = await api.getPredios();
        const predio = (predios.data || []).find(item => String(item.id) === String(this.predioId));
        if (!predio) return;

        this.form.nombre_rancho = predio.nombre_rancho || predio.nombre || '';
        this.form.productor_id = String(predio.productor_id || predio.productor?.id || '');
        this.form.clave_unidad_produccion = predio.clave_unidad_produccion || predio.upp || '';
        this.form.latitud = predio.latitud || '';
        this.form.longitud = predio.longitud || '';
        this.form.domicilio = predio.domicilio || '';
        this.form.municipio = predio.municipio || 'General';
        this.form.localidad = predio.localidad || 'General';
        this.productorIdOriginal = predio.productor_id || predio.productor?.id || null;
        this.fillProductorForm(predio.productor);
      } catch (e) {
        const predios = await db.getPredios();
        const predio = predios.find(item => String(item.id) === String(this.predioId));
        if (!predio) return;

        this.form.nombre_rancho = predio.nombre_rancho || predio.nombre || '';
        this.form.productor_id = String(predio.productor_id || predio.productor?.id || '');
        this.form.clave_unidad_produccion = predio.clave_unidad_produccion || predio.upp || '';
        this.form.latitud = predio.latitud || '';
        this.form.longitud = predio.longitud || '';
        this.form.domicilio = predio.domicilio || '';
        this.form.municipio = predio.municipio || 'General';
        this.form.localidad = predio.localidad || 'General';
        this.productorIdOriginal = predio.productor_id || predio.productor?.id || null;
        this.fillProductorForm(predio.productor);
      }
    },
    fillProductorForm(productor) {
      if (!productor) return;
      this.productorForm.nombre = productor.nombre || '';
      this.productorForm.apellido_paterno = productor.apellido_paterno || '';
      this.productorForm.apellido_materno = productor.apellido_materno || '';
      this.productorForm.curp = productor.curp || '';
      this.productorForm.upp = productor.upp || '';
      this.productorForm.domicilio = productor.domicilio || '';
      this.productorForm.municipio = productor.municipio || '';
      this.productorForm.localidad = productor.localidad || '';
      this.productorForm.estado = productor.estado || 'Nayarit';
      this.productorForm.telefono = productor.telefono || '';
      this.productorForm.email = productor.email || '';
    },
    formatProductorName(productor) {
      return [
        productor?.nombre,
        productor?.apellido_paterno,
        productor?.apellido_materno
      ].filter(Boolean).join(' ');
    },
    async detectLocation() {
      this.gpsLoading = true;
      this.errorMsg = '';

      try {
        const coords = await Geolocation.getCurrentPosition({ enableHighAccuracy: true });
        this.form.latitud = coords.coords.latitude.toFixed(6);
        this.form.longitud = coords.coords.longitude.toFixed(6);
      } catch (error) {
        if (navigator.geolocation) {
          await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
              (position) => {
                this.form.latitud = position.coords.latitude.toFixed(6);
                this.form.longitud = position.coords.longitude.toFixed(6);
                resolve();
              },
              reject,
              { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
          }).catch(() => {
            throw error;
          });
        } else {
          throw error;
        }
      } finally {
        this.gpsLoading = false;
      }
    },
    openProductorModal() {
      this.modalErrorMsg = '';
      this.newProductor = {
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
        email: ''
      };
      this.showProductorModal = true;
    },
    async saveProductorAjax() {
      if (!this.newProductor.nombre.trim() || !this.newProductor.apellido_paterno.trim()) {
        this.modalErrorMsg = 'Por favor complete los campos obligatorios (*).';
        return;
      }

      this.modalErrorMsg = '';

      // Validar CURP si se proporciona
      if (this.newProductor.curp.trim()) {
        const curpVal = this.newProductor.curp.trim().toUpperCase();
        const curpRegex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/;
        if (curpVal.length !== 18 || !curpRegex.test(curpVal)) {
          this.modalErrorMsg = 'La CURP debe tener exactamente 18 caracteres y un formato válido (Ej: AAAA111111HXXYYY01).';
          return;
        }
      }

      // Validar duplicados locales en IndexedDB
      try {
        const prediosLocales = await db.getPredios();
        
        if (this.newProductor.curp.trim()) {
          const curpVal = this.newProductor.curp.trim().toUpperCase();
          const duplicateCurp = prediosLocales.some(p => p.productor && p.productor.curp && p.productor.curp.toUpperCase() === curpVal);
          if (duplicateCurp) {
            this.modalErrorMsg = 'Ya existe un productor registrado con esta CURP localmente.';
            return;
          }
        }

        if (this.newProductor.upp.trim()) {
          const duplicateUpp = prediosLocales.some(p => p.productor && p.productor.upp && p.productor.upp === this.newProductor.upp.trim());
          if (duplicateUpp) {
            this.modalErrorMsg = 'Ya existe un productor registrado con esta UPP localmente.';
            return;
          }
        }
      } catch (dbErr) {
        console.warn('Error al verificar duplicados locales en modal:', dbErr);
      }

      this.modalSaving = true;

      const body = {
        nombre: this.newProductor.nombre.trim(),
        apellido_paterno: this.newProductor.apellido_paterno.trim(),
        apellido_materno: this.newProductor.apellido_materno.trim(),
        curp: this.newProductor.curp.trim().toUpperCase(),
        upp: this.newProductor.upp.trim(),
        domicilio: this.newProductor.domicilio.trim(),
        municipio: this.newProductor.municipio.trim(),
        localidad: this.newProductor.localidad.trim(),
        estado: this.newProductor.estado.trim() || 'Nayarit',
        telefono: this.newProductor.telefono.trim(),
        email: this.newProductor.email.trim()
      };

      try {
        let serverProductor = null;
        let createdOnServer = false;

        // 1. Si está conectado, guardar en el servidor Laravel central
        if (this.isOnline) {
          try {
            const res = await api.storeProductor(body);
            if (res && res.success) {
              serverProductor = res.productor;
              createdOnServer = true;
            } else if (res && res.id) {
              serverProductor = res;
              createdOnServer = true;
            }
          } catch (apiErr) {
            console.warn('Error al guardar productor en la API, usando guardado local:', apiErr);
            this.modalErrorMsg = 'Error en el servidor: ' + (apiErr.message || 'Inténtalo de nuevo.');
            this.modalSaving = false;
            return;
          }
        }

        // 2. Guardar en base de datos local (IndexedDB)
        const finalProductorId = createdOnServer && serverProductor ? serverProductor.id : `OFFLINE_PROD_${Date.now()}`;
        const finalProductor = {
          id: finalProductorId,
          ...body
        };

        // Agregar a la lista local de productores
        this.productores.push(finalProductor);

        // Seleccionar automáticamente al productor recién creado
        this.form.productor_id = String(finalProductorId);

        // Cerrar modal y limpiar formulario
        this.showProductorModal = false;
        this.newProductor = {
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
          email: ''
        };
        
        alert(createdOnServer 
          ? '✅ ¡Productor registrado y seleccionado con éxito!' 
          : '✅ ¡Productor guardado localmente (Offline) y seleccionado!');
      } catch (err) {
        console.error('Error al registrar productor:', err);
        this.modalErrorMsg = err.message || 'No se pudo guardar el productor.';
      } finally {
        this.modalSaving = false;
      }
    },
    handleCancel() {
      this.$router.push('/predios');
    },
    async savePredio() {
      // Validaciones básicas de campos requeridos
      if (!this.form.nombre_rancho.trim() || !this.form.clave_unidad_produccion.trim() || !this.form.localidad.trim()) {
        this.errorMsg = '⚠️ Por favor complete todos los campos obligatorios del Rancho (Nombre, UPP y Localidad).';
        return;
      }

      if (!this.form.productor_id) {
        this.errorMsg = '⚠️ Por favor seleccione un productor para este Rancho.';
        return;
      }

      // Validaciones de rangos de coordenadas
      const lat = this.form.latitud ? parseFloat(this.form.latitud) : null;
      const lng = this.form.longitud ? parseFloat(this.form.longitud) : null;

      if (this.form.latitud && (isNaN(lat) || lat < -90 || lat > 90)) {
        this.errorMsg = '⚠️ La latitud debe ser un número entre -90 y 90.';
        return;
      }

      if (this.form.longitud && (isNaN(lng) || lng < -180 || lng > 180)) {
        this.errorMsg = '⚠️ La longitud debe ser un número entre -180 y 180.';
        return;
      }

      // Validar CURP del productor si se está editando
      if (this.isEditing) {
        if (!this.productorForm.nombre.trim() || !this.productorForm.apellido_paterno.trim()) {
          this.errorMsg = '⚠️ Por favor complete los campos obligatorios del Productor (Nombre y Apellido Paterno).';
          return;
        }

        if (this.productorForm.curp.trim()) {
          const curpVal = this.productorForm.curp.trim().toUpperCase();
          const curpRegex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/;
          if (curpVal.length !== 18 || !curpRegex.test(curpVal)) {
            this.errorMsg = '⚠️ La CURP del productor debe tener exactamente 18 caracteres y un formato válido.';
            return;
          }
        }
      }

      // Validar duplicidad local
      try {
        const prediosLocales = await db.getPredios();
        
        // Duplicidad de UPP del predio
        const duplicatePredioUpp = prediosLocales.some(p => p.upp && p.upp === this.form.clave_unidad_produccion.trim() && String(p.id) !== String(this.predioId));
        if (duplicatePredioUpp) {
          this.errorMsg = '⚠️ Ya existe un Rancho/Predio registrado con esta UPP localmente.';
          return;
        }

        // Si se está editando el productor también
        if (this.isEditing && this.productorIdOriginal) {
          const prodCurp = this.productorForm.curp.trim().toUpperCase();
          const prodUpp = this.productorForm.upp.trim();

          if (prodCurp) {
            const duplicateCurp = prediosLocales.some(p => p.productor && p.productor.curp && p.productor.curp.toUpperCase() === prodCurp && String(p.productor.id) !== String(this.productorIdOriginal));
            if (duplicateCurp) {
              this.errorMsg = '⚠️ Ya existe un productor registrado con esta CURP localmente.';
              return;
            }
          }

          if (prodUpp) {
            const duplicateUpp = prediosLocales.some(p => p.productor && p.productor.upp && p.productor.upp === prodUpp && String(p.productor.id) !== String(this.productorIdOriginal));
            if (duplicateUpp) {
              this.errorMsg = '⚠️ Ya existe un productor registrado con esta UPP localmente.';
              return;
            }
          }
        }
      } catch (dbErr) {
        console.warn('Error al verificar duplicados locales en savePredio:', dbErr);
      }

      this.saving = true;
      this.errorMsg = '';
      this.successMsg = '';

      const body = {
        nombre_rancho: this.form.nombre_rancho.trim(),
        clave_unidad_produccion: this.form.clave_unidad_produccion.trim(),
        productor_id: this.form.productor_id,
        latitud: lat,
        longitud: lng,
        domicilio: this.form.domicilio.trim(),
        municipio: this.form.municipio.trim() || 'General',
        localidad: this.form.localidad.trim()
      };

      const productorBody = this.isEditing ? {
        nombre: this.productorForm.nombre.trim(),
        apellido_paterno: this.productorForm.apellido_paterno.trim(),
        apellido_materno: this.productorForm.apellido_materno.trim(),
        curp: this.productorForm.curp.trim().toUpperCase(),
        upp: this.productorForm.upp.trim(),
        domicilio: this.productorForm.domicilio.trim(),
        municipio: this.productorForm.municipio.trim(),
        localidad: this.productorForm.localidad.trim(),
        estado: this.productorForm.estado.trim(),
        telefono: this.productorForm.telefono.trim(),
        email: this.productorForm.email.trim()
      } : null;

      try {
        let predio = null;
        if (this.isOnline) {
          const res = this.predioId
            ? await api.updateRancho(this.predioId, body)
            : await api.storeRancho(body);
          predio = res.predio || null;

          if (this.isEditing && this.productorIdOriginal) {
            await api.updateProductor(this.productorIdOriginal, productorBody);
          }
        }

        const predios = await db.getPredios();
        const productor = this.productores.find(p => String(p.id) === String(this.form.productor_id)) || null;

        const localPredio = predio || {
          id: this.predioId || `OFFLINE_PREDIO_${Date.now()}`,
          nombre: body.nombre_rancho,
          nombre_rancho: body.nombre_rancho,
          clave_unidad_produccion: body.clave_unidad_produccion,
          upp: body.clave_unidad_produccion,
          latitud: body.latitud,
          longitud: body.longitud,
          domicilio: body.domicilio,
          municipio: body.municipio,
          localidad: body.localidad,
          productor_id: body.productor_id,
          productor
        };

        if (this.isEditing) {
          localPredio.productor = {
            id: this.productorIdOriginal || localPredio.productor?.id || body.productor_id,
            nombre: this.productorForm.nombre.trim() || productor?.nombre || '',
            apellido_paterno: this.productorForm.apellido_paterno.trim() || productor?.apellido_paterno || '',
            apellido_materno: this.productorForm.apellido_materno.trim() || productor?.apellido_materno || '',
            curp: this.productorForm.curp.trim().toUpperCase() || productor?.curp || '',
            upp: this.productorForm.upp.trim() || productor?.upp || '',
            domicilio: this.productorForm.domicilio.trim() || productor?.domicilio || '',
            municipio: this.productorForm.municipio.trim() || productor?.municipio || '',
            localidad: this.productorForm.localidad.trim() || productor?.localidad || '',
            estado: this.productorForm.estado.trim() || productor?.estado || 'Nayarit',
            telefono: this.productorForm.telefono.trim() || productor?.telefono || '',
            email: this.productorForm.email.trim() || productor?.email || ''
          };
        }

        if (!localPredio.productor && productor) {
          localPredio.productor = productor;
        }

        const predioIndex = predios.findIndex(item => String(item.id) === String(localPredio.id));
        if (predioIndex >= 0) {
          predios[predioIndex] = localPredio;
        } else {
          predios.push(localPredio);
        }
        await db.savePredios(predios);

        this.successMsg = this.predioId
          ? (this.isOnline
            ? `Rancho "${body.nombre_rancho}" actualizado con éxito.`
            : `Rancho "${body.nombre_rancho}" actualizado localmente; se sincronizará al conectar.`)
          : (this.isOnline
            ? `Rancho "${body.nombre_rancho}" registrado con éxito.`
            : `Rancho "${body.nombre_rancho}" guardado localmente; se sincronizará al conectar.`);

        setTimeout(() => {
          this.$router.push('/predios');
        }, 700);
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo guardar el predio.';
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

.gps-button {
  width: 100%;
  border: 2px solid #2563eb;
  background: white;
  color: #2563eb;
  font-weight: 700;
  padding: 11px 16px;
  border-radius: 10px;
}

.gps-button:disabled {
  opacity: 0.7;
}

.link-button {
  border: none;
  background: transparent;
  color: #2563eb;
  font-weight: 700;
  padding: 0;
}

.btn-submit-custom,
.btn-cancel-custom {
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

.create-predio-card {
  max-width: 960px;
  margin: 0 auto;
}

/* Modal styles replicated from ProductoresView for consistency */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 16px;
}

.modal-card {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slide-up {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
}

.modal-title {
  font-weight: 700;
  font-size: 1.1rem;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-close-modal {
  background: #f1f5f9;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-close-modal:hover {
  background-color: #e2e8f0;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex-grow: 1;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: #f8fafc;
}
</style>
