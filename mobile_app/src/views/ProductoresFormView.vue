<template>
  <div class="app-container bg-light-page">
    
    <!-- Header (Premium Web Style Clone) -->
    <header class="form-view-header shadow-sm">
      <div class="d-flex align-items-center gap-2 gap-sm-3 header-left-container">
        <button class="btn-back-circle shadow-sm" @click="handleCancel">
          <i class="bi bi-arrow-left fs-5"></i>
        </button>
        <div class="text-start title-text-wrapper">
          <h1 class="h5 fw-bold mb-0 text-dark header-title">Registrar Productor</h1>
          <div class="subtitle text-secondary small d-none d-sm-block">Complete los datos según el formato oficial de SENASICA</div>
        </div>
      </div>
      
      <!-- Right Side Web Badges Cloned -->
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
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
          
          <!-- STEPPER INDICATOR (Cloned from Web Screenshot) -->
          <div class="stepper-container">
            <!-- Paso 1 Circle -->
            <div class="step-circle" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
              <span v-if="currentStep > 1"><i class="bi bi-check-lg"></i></span>
              <span v-else>1</span>
            </div>
            <!-- Connecting Line -->
            <div class="step-line"></div>
            <!-- Paso 2 Circle -->
            <div class="step-circle" :class="{ active: currentStep === 2 }">2</div>
          </div>

          <!-- ========================================== -->
          <!-- STEP 1: INFORMACIÓN DEL PRODUCTOR        -->
          <!-- ========================================== -->
          <div v-if="currentStep === 1" class="animate-fade-in">
            <div class="d-flex align-items-center gap-2 mb-4">
              <span class="text-primary fs-4"><i class="bi bi-person-circle"></i></span>
              <h4 class="h5 fw-bold mb-0 text-dark">Paso 1: Información del Productor</h4>
            </div>

            <form @submit.prevent="nextStep">
              <div class="row g-3">
                <!-- Nombre(s) | Apellido Paterno | Apellido Materno -->
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Nombre(s) *</label>
                    <input v-model="form.nombre" type="text" class="form-control-custom" placeholder="Ej: Pepito" required>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Apellido Paterno *</label>
                    <input v-model="form.apellido_paterno" type="text" class="form-control-custom" placeholder="Ej: Tejeda" required>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Apellido Materno</label>
                    <input v-model="form.apellido_materno" type="text" class="form-control-custom" placeholder="Ej: Figueroa">
                  </div>
                </div>

                <!-- CURP | UPP (Productor) -->
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">CURP *</label>
                    <input 
                      v-model="form.curp" 
                      type="text" 
                      class="form-control-custom font-mono" 
                      placeholder="18 caracteres" 
                      maxlength="18"
                      style="text-transform: uppercase;"
                      required
                    >
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">UPP (Productor) *</label>
                    <input v-model="form.upp" type="text" class="form-control-custom" placeholder="Ej: 57625285" required>
                  </div>
                </div>

                <!-- Domicilio Completo -->
                <div class="col-12">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Domicilio Completo</label>
                    <input v-model="form.domicilio" type="text" class="form-control-custom" placeholder="Calle, Número, Colonia">
                  </div>
                </div>

                <!-- Municipio | Localidad | Estado -->
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Municipio</label>
                    <input v-model="form.municipio" type="text" class="form-control-custom" placeholder="Ej: Tepic">
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Localidad</label>
                    <input v-model="form.localidad" type="text" class="form-control-custom" placeholder="Ej: Tepic">
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="form-group-custom">
                    <label class="form-label-custom text-muted">Estado</label>
                    <input v-model="form.estado" type="text" class="form-control-custom bg-light text-slate-500" placeholder="Nayarit" readonly tabindex="-1">
                  </div>
                </div>

                <!-- Teléfono | Correo Electrónico -->
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Teléfono</label>
                    <input v-model="form.telefono" type="tel" class="form-control-custom" placeholder="Ej: 3111129405">
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Correo Electrónico</label>
                    <input v-model="form.email" type="email" class="form-control-custom" placeholder="Ej: ejemplo@correo.com">
                  </div>
                </div>

                <!-- Clave de Cuarentena | Zona / Sector (Solo Administradores) -->
                <template v-if="isAdmin">
                  <div class="col-12 col-md-6">
                    <div class="form-group-custom">
                      <label class="form-label-custom">Clave de Cuarentena</label>
                      <input 
                        v-model="form.clave_cuarentena" 
                        type="text" 
                        class="form-control-custom text-uppercase" 
                        placeholder="Ej. BD-123421"
                        @input="autoSelectZona"
                      >
                      <div class="small text-muted mt-1" style="font-size: 0.72rem;">Debe iniciar con AD, AP, BD o BP.</div>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="form-group-custom">
                      <label class="form-label-custom">Zona / Sector</label>
                      <select v-model="form.zona" class="form-select form-control-custom">
                        <option value="">-- Sin Zona --</option>
                        <option value="A">Sector A</option>
                        <option value="B">Sector B</option>
                      </select>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Action Buttons Paso 1 -->
              <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top border-slate-100">
                <button type="button" class="btn-cancel-custom rounded-pill px-4" @click="handleCancel">Cancelar</button>
                <button type="submit" class="btn-submit-custom rounded-pill px-4">Continuar al Paso 2 <i class="bi bi-arrow-right ms-1"></i></button>
              </div>
            </form>
          </div>

          <!-- ========================================== -->
          <!-- STEP 2: DATOS DEL PREDIO (RANCHO)          -->
          <!-- ========================================== -->
          <div v-if="currentStep === 2" class="animate-fade-in">
            
            <!-- Info Alert banner (Cloned from Web Screenshot) -->
            <div class="alert alert-info-custom d-flex align-items-center gap-2 text-start p-3 rounded-3 mb-4">
              <i class="bi bi-info-circle-fill text-info fs-5 animate-pulse-soft"></i>
              <span class="small-desc text-info" style="font-size: 0.85rem;">Si el productor no tiene un predio aún, puede hacer clic en <strong>"Solo registrar productor"</strong>.</span>
            </div>

            <!-- Title (Green Web Clone style) -->
            <div class="d-flex align-items-center gap-2 mb-4">
              <span class="text-success fs-4"><i class="bi bi-house-door"></i></span>
              <h4 class="h5 fw-bold mb-0 text-success">Paso 2: Información del Rancho / Predio</h4>
            </div>

            <!-- Predio Form Fields -->
            <form @submit.prevent="saveWithPredio">
              <div class="row g-3 text-start">
                
                <!-- Nombre del Rancho (Full Width) -->
                <div class="col-12">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Nombre del Rancho</label>
                    <input v-model="form.nombre_rancho" type="text" class="form-control-custom" placeholder="Ej. El Mirador" required>
                  </div>
                </div>

                <!-- Clave UPP del Predio (Full Width) -->
                <div class="col-12">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Clave UPP del Predio</label>
                    <input v-model="form.clave_unidad_produccion" type="text" class="form-control-custom" placeholder="Ej. 180104330002" required>
                  </div>
                </div>

                <!-- GPS Native Detection Button (Cloned from Web style) -->
                <div class="col-12">
                  <button type="button" class="btn-gps-detect w-100 py-2.5" @click="detectGPS">
                    <i class="bi bi-geo-alt-fill text-primary"></i> Detectar Ubicación Actual (GPS)
                  </button>
                </div>

                <!-- Latitud | Longitud (Two equal columns) -->
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Latitud</label>
                    <input v-model="form.latitud" type="text" class="form-control-custom font-mono bg-light" placeholder="Ej. 21.948694">
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Longitud</label>
                    <input v-model="form.longitud" type="text" class="form-control-custom font-mono bg-light" placeholder="Ej. -105.298320">
                  </div>
                </div>

                <!-- Domicilio del Predio (Full Width) -->
                <div class="col-12">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Domicilio del Predio</label>
                    <input v-model="form.predio_domicilio" type="text" class="form-control-custom" placeholder="Ej. A 2 km sobre el arroyo">
                  </div>
                </div>

                <!-- Municipio del Predio | Localidad del Predio -->
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Municipio del Predio</label>
                    <input v-model="form.predio_municipio" type="text" class="form-control-custom" placeholder="Ingresa municipio">
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Localidad del Predio</label>
                    <input v-model="form.predio_localidad" type="text" class="form-control-custom" placeholder="Ingresa localidad">
                  </div>
                </div>
              </div>

              <!-- Navigation Back Button -->
              <div class="d-flex justify-content-start mt-4 mb-3">
                <button type="button" class="btn-cancel-custom rounded-pill px-4" @click="currentStep = 1">
                  ← Anterior
                </button>
              </div>

              <!-- Twin Action Save Row (Cloned layout from Web Screenshot) -->
              <div class="card-footer-custom mt-4">
                <button type="button" class="btn-secondary-custom rounded-pill px-4" @click="saveWithoutPredio">
                  No tiene predio (Solo Productor)
                </button>
                <button type="submit" class="btn-submit-green rounded-pill px-4 d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-check-circle-fill animate-pulse-soft"></i> Finalizar y Guardar Todo
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import { Geolocation } from '@capacitor/geolocation';
import db from '../services/db.js';
import api from '../services/api.js';

export default {
  name: 'ProductoresFormView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      networkListener: null,
      
      // Step workflow state
      currentStep: 1,
      mode: 'create', // 'create' | 'edit'
      productorId: null,

      // Two-step logic
      registrarPredio: false,

      // Form bindings
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
        clave_cuarentena: '',
        zona: '',

        // Predio optional data
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
    // 1. Cargar datos del usuario autenticado
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');

    // 2. Determinar si es Crear o Editar según la ruta
    if (this.$route.params.id) {
      this.mode = 'edit';
      this.productorId = this.$route.params.id;
      await this.loadProductorDataForEdit();
    } else {
      this.mode = 'create';
      this.registrarPredio = false;
    }

    // 3. Inicializar estado de conectividad nativa
    try {
      const status = await Network.getStatus();
      this.isOnline = status.connected;

      this.networkListener = await Network.addListener('networkStatusChange', (status) => {
        this.isOnline = status.connected;
      });
    } catch (e) {
      console.warn('Network API no disponible, usando fallback local.', e);
      this.isOnline = navigator.onLine;
      window.addEventListener('online', () => this.isOnline = true);
      window.addEventListener('offline', () => this.isOnline = false);
    }
  },
  beforeUnmount() {
    if (this.networkListener) {
      this.networkListener.remove();
    }
  },
  methods: {
    // Carga productor existente para pre-rellenar campos en edición
    async loadProductorDataForEdit() {
      try {
        const predios = await db.getPredios();
        
        // Buscar el primer predio que tenga este productor
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
          this.form.clave_cuarentena = prod.clave_cuarentena || '';
          this.form.zona = prod.zona || '';

          // Si es edición, también permitimos editar su predio directamente
          this.form.nombre_rancho = predioAsociado.nombre !== 'Sin Rancho' ? predioAsociado.nombre : '';
          this.form.clave_unidad_produccion = predioAsociado.nombre !== 'Sin Rancho' ? predioAsociado.upp : '';
          this.form.predio_localidad = predioAsociado.localidad || '';
          this.form.predio_municipio = predioAsociado.municipio || '';
          this.form.predio_domicilio = predioAsociado.domicilio || '';
          this.form.latitud = predioAsociado.latitud || '';
          this.form.longitud = predioAsociado.longitud || '';
        }
      } catch (err) {
        console.error('Error cargando productor para editar:', err);
      }
    },

    // Detectar ubicación actual por GPS nativo o navegador
    async detectGPS() {
      try {
        const coordinates = await Geolocation.getCurrentPosition();
        if (coordinates && coordinates.coords) {
          this.form.latitud = coordinates.coords.latitude.toFixed(6);
          this.form.longitud = coordinates.coords.longitude.toFixed(6);
          alert('✅ Ubicación GPS nativa obtenida con éxito.');
        }
      } catch (err) {
        console.warn('GPS nativo no disponible, intentando Geolocation de navegador:', err);
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            (position) => {
              this.form.latitud = position.coords.latitude.toFixed(6);
              this.form.longitud = position.coords.longitude.toFixed(6);
              alert('✅ Ubicación GPS del navegador obtenida con éxito.');
            },
            (geoErr) => {
              console.error('Error de geolocalización de navegador:', geoErr);
              alert('❌ No se pudo obtener la ubicación GPS. Habilita los permisos de ubicación.');
            }
          );
        } else {
          alert('❌ La geolocalización no está soportada por este dispositivo.');
        }
      }
    },

    nextStep() {
      if (!this.form.nombre.trim() || !this.form.apellido_paterno.trim() || !this.form.curp.trim() || !this.form.upp.trim()) {
        alert('⚠️ Por favor completa los campos requeridos (*) en el Paso 1.');
        return;
      }

      const curpVal = this.form.curp.trim().toUpperCase();
      const curpRegex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/;
      if (curpVal.length !== 18 || !curpRegex.test(curpVal)) {
        alert('⚠️ La CURP debe tener exactamente 18 caracteres y un formato válido (Ej: AAAA111111HXXYYY01).');
        return;
      }

      this.currentStep = 2;
    },

    handleCancel() {
      this.$router.push('/productores');
    },

    autoSelectZona() {
      if (!this.form.clave_cuarentena) return;
      const first = this.form.clave_cuarentena.toUpperCase()[0];
      this.form.zona = (first === 'A' || first === 'B') ? first : '';
    },

    // Guardar con Rancho asociado
    async saveWithPredio() {
      if (!this.form.nombre_rancho.trim() || !this.form.clave_unidad_produccion.trim()) {
        alert('⚠️ Por favor completa los campos requeridos del Rancho (*).');
        return;
      }
      this.registrarPredio = true;
      await this.processFinalSave();
    },

    // Guardar SIN Rancho asociado
    async saveWithoutPredio() {
      this.registrarPredio = false;
      await this.processFinalSave();
    },

    // PROCESAR GUARDADO FINAL (API SI ONLINE, FALLBACK INDEXEDDB)
    async processFinalSave() {
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
        clave_cuarentena: this.isAdmin ? (this.form.clave_cuarentena || '').toUpperCase().trim() : null,
        zona: this.isAdmin ? (this.form.zona || null) : null,

        // Banderas en 2 pasos de la web
        registrar_predio: this.mode === 'create' && this.registrarPredio ? 1 : 0,
        nombre_rancho: this.mode === 'create' && this.registrarPredio ? this.form.nombre_rancho.trim() : null,
        clave_unidad_produccion: this.mode === 'create' && this.registrarPredio ? this.form.clave_unidad_produccion.trim() : null,
        predio_localidad: this.mode === 'create' && this.registrarPredio ? (this.form.predio_localidad.trim() || 'General') : 'General',
        predio_municipio: this.mode === 'create' && this.registrarPredio ? (this.form.predio_municipio.trim() || 'General') : 'General',
        predio_domicilio: this.mode === 'create' && this.registrarPredio ? this.form.predio_domicilio.trim() : 'Conocido',
        latitud: this.mode === 'create' && this.registrarPredio ? this.form.latitud : null,
        longitud: this.mode === 'create' && this.registrarPredio ? this.form.longitud : null
      };

      // Validar duplicados locales
      try {
        const prediosLocales = await db.getPredios();
        
        const duplicateCurp = prediosLocales.some(p => p.productor && p.productor.curp && p.productor.curp.toUpperCase() === body.curp && String(p.productor.id) !== String(this.productorId));
        if (duplicateCurp) {
          alert('⚠️ Ya existe un productor registrado con esta CURP localmente.');
          return;
        }

        const duplicateUpp = prediosLocales.some(p => p.productor && p.productor.upp && p.productor.upp === body.upp && String(p.productor.id) !== String(this.productorId));
        if (duplicateUpp) {
          alert('⚠️ Ya existe un productor registrado con esta UPP localmente.');
          return;
        }

        if (body.registrar_predio) {
          const duplicatePredioUpp = prediosLocales.some(p => p.upp && p.upp === body.clave_unidad_produccion && String(p.productor_id) !== String(this.productorId));
          if (duplicatePredioUpp) {
            alert('⚠️ Ya existe un Rancho/Predio registrado con esta UPP localmente.');
            return;
          }
        }
      } catch (dbErr) {
        console.warn('Error al verificar duplicados locales:', dbErr);
      }

      try {
        let serverProductor = null;
        let serverRancho = null;
        let createdOnServer = false;

        // 1. Guardar en servidor Laravel central si está online
        if (this.isOnline) {
          try {
            if (this.mode === 'create') {
              const res = await api.storeProductor(body);
              if (res && res.success) {
                serverProductor = res.productor;
                serverRancho = res.predio;
                createdOnServer = true;
              }
            } else {
              const res = await api.updateProductor(this.productorId, body);
              if (res && res.success) {
                serverProductor = res.productor;
                createdOnServer = true;
              }
            }
          } catch (apiErr) {
            console.warn('Error en validación o conexión con servidor Laravel:', apiErr);
            alert('⚠️ Error en el servidor central: ' + (apiErr.message || 'Compruebe duplicados (CURP/UPP).'));
            return;
          }
        }

        // 2. Guardar en base de datos local IndexedDB
        const predios = await db.getPredios();

        if (this.mode === 'create') {
          const finalProductorId = createdOnServer && serverProductor ? serverProductor.id : ('OFFLINE_PROD_' + Date.now());
          
          let newPredio = null;

          if (this.registrarPredio) {
            newPredio = {
              id: createdOnServer && serverRancho ? serverRancho.id : ('OFFLINE_PREDIO_' + Date.now()),
              nombre: body.nombre_rancho,
              upp: body.clave_unidad_produccion,
              localidad: body.predio_localidad,
              municipio: body.predio_municipio,
              domicilio: body.predio_domicilio,
              latitud: body.latitud,
              longitud: body.longitud,
              productor_id: finalProductorId,
              productor: {
                id: finalProductorId,
                nombre: body.nombre,
                apellido_paterno: body.apellido_paterno,
                apellido_materno: body.apellido_materno,
                curp: body.curp,
                upp: body.upp,
                telefono: body.telefono,
                domicilio: body.domicilio,
                municipio: body.municipio,
                localidad: body.localidad,
                estado: body.estado,
                email: body.email,
                clave_cuarentena: body.clave_cuarentena,
                zona: body.zona
              }
            };
          } else {
            // Placeholder sin rancho
            newPredio = {
              id: 'OFFLINE_PREDIO_EMPTY_' + Date.now(),
              nombre: 'Sin Rancho',
              upp: 'N/A',
              localidad: 'General',
              municipio: 'General',
              domicilio: 'Conocido',
              productor_id: finalProductorId,
              productor: {
                id: finalProductorId,
                nombre: body.nombre,
                apellido_paterno: body.apellido_paterno,
                apellido_materno: body.apellido_materno,
                curp: body.curp,
                upp: body.upp,
                telefono: body.telefono,
                domicilio: body.domicilio,
                municipio: body.municipio,
                localidad: body.localidad,
                estado: body.estado,
                email: body.email,
                clave_cuarentena: body.clave_cuarentena,
                zona: body.zona
              }
            };
          }

          predios.push(newPredio);
          await db.savePredios(predios);
          
          alert(newPredio.nombre !== 'Sin Rancho' 
            ? '✅ ¡Productor y Rancho creados exitosamente!'
            : '✅ ¡Productor registrado exitosamente (Sin Rancho)!'
          );
        } else {
          // Edición
          const targetId = String(this.productorId);

          predios.forEach(p => {
            if (p.productor && String(p.productor.id) === targetId) {
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
              p.productor.clave_cuarentena = body.clave_cuarentena;
              p.productor.zona = body.zona;

              // Si actualiza los campos del predio existente
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
          alert('✅ ¡Datos del productor actualizados con éxito!');
        }

        this.$router.push('/productores');
      } catch (err) {
        console.error('Error al guardar productor:', err);
        alert('❌ Error al procesar la solicitud.');
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
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

@media (min-width: 576px) {
  .header-title {
    font-size: 1.25rem;
  }
}

.header-badges {
  flex-shrink: 0;
}

@media (max-width: 576px) {
  .form-view-header {
    padding: 12px 14px;
  }
  .btn-back-circle {
    width: 34px !important;
    height: 34px !important;
  }
  .btn-back-circle i {
    font-size: 0.95rem !important;
  }
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
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.btn-back-circle:active {
  background-color: #f1f5f9;
  transform: scale(0.95);
}

.bg-light-page {
  background-color: #f8fafc;
}

.container-form-wrapper {
  max-width: 900px;
  margin: 0 auto;
  width: 100%;
}

/* Stepper Cloned Web Styles */
.stepper-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 32px;
}

.step-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.95rem;
  border: 2px solid #e2e8f0;
  background: white;
  color: #94a3b8;
  transition: all 0.3s;
}

.step-circle.active {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
}

.step-circle.completed {
  background: #10b981;
  color: white;
  border-color: #10b981;
}

.step-line {
  width: 80px;
  height: 2px;
  background-color: #e2e8f0;
  transition: all 0.3s;
}

.step-line.active {
  background-color: #2563eb;
}

/* Form Styles */
.step-title-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1.5px solid #2563eb;
  padding-bottom: 8px;
  text-align: left;
}

.step-title-wrapper.border-success {
  border-bottom: 1.5px solid #10b981 !important;
}

.step-icon-bullet {
  color: #2563eb;
  font-size: 1.25rem;
}

.form-group-custom {
  margin-bottom: 16px;
  text-align: left;
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
  font-size: 0.9rem;
  color: #1e293b;
  background-color: white;
  transition: all 0.2s ease;
  box-shadow: none !important;
}

.form-control-custom:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08) !important;
}

.font-mono {
  font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
}

/* Action Buttons Styles */
.btn-submit-custom {
  background-color: #2563eb;
  color: white;
  font-weight: 600;
  font-size: 0.88rem;
  padding: 11px 24px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.btn-submit-custom:hover {
  background-color: #1d4ed8;
}

.btn-submit-custom:active {
  transform: scale(0.97);
}

.btn-submit-green {
  background-color: #10b981;
  color: white;
  font-weight: 600;
  font-size: 0.88rem;
  padding: 11px 24px;
  border-radius: 50px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.btn-submit-green:hover {
  background-color: #059669;
}

.btn-submit-green:active {
  transform: scale(0.97);
}

.btn-cancel-custom {
  background-color: #ffffff;
  color: #475569;
  font-weight: 600;
  font-size: 0.88rem;
  padding: 11px 24px;
  border-radius: 50px;
  border: 1px solid #cbd5e1;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-cancel-custom:hover {
  background-color: #f1f5f9;
}

.btn-cancel-custom:active {
  transform: scale(0.97);
}

.btn-secondary-custom {
  background-color: #ffffff;
  color: #334155;
  font-weight: 600;
  font-size: 0.88rem;
  padding: 11px 24px;
  border-radius: 50px;
  border: 1px solid #cbd5e1;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary-custom:hover {
  background-color: #f8fafc;
}

.btn-gps-detect {
  border: 1.5px solid #2563eb;
  background-color: white;
  color: #2563eb;
  font-weight: 600;
  padding: 10px;
  border-radius: 10px; /* Matching the input fields */
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-gps-detect:active {
  background-color: #eff6ff;
}

.btn-gps-detect:hover {
  background-color: #f0f7ff;
  border-color: #1d4ed8;
}

/* Alert info styles */
.alert-info-custom {
  background-color: #e0f2fe;
  border: 1px solid #bae6fd;
  color: #0369a1;
  border-radius: 12px;
}

/* Micro-interaction: Focus matching label */
.form-group-custom:focus-within .form-label-custom {
  color: #2563eb !important;
}

/* Bleed card footer style from SENASICA web platform */
.card-footer-custom {
  margin-left: -1.5rem;
  margin-right: -1.5rem;
  margin-bottom: -1.5rem;
  padding: 1.25rem 1.5rem;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

@media (max-width: 576px) {
  .card-footer-custom {
    flex-direction: column;
    gap: 12px;
    padding: 1.25rem 1rem;
    margin-left: -1rem;
    margin-right: -1rem;
    margin-bottom: -1rem;
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
  }
  .card-footer-custom button {
    width: 100%;
  }
}

/* Smooth Premium animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes pulseSoft {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.85;
    transform: scale(0.98);
  }
}

.animate-pulse-soft {
  animation: pulseSoft 3s ease-in-out infinite;
}

/* Connectivity Badges */
.web-connectivity-pill {
  background-color: #d1fae5;
  color: #065f46;
  font-weight: 600;
  font-size: 0.78rem;
  padding: 6px 12px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #a7f3d0;
}

.web-connectivity-pill .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.web-role-pill {
  background-color: white;
  color: #1e293b;
  font-weight: 600;
  font-size: 0.78rem;
  padding: 6px 14px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.bg-success {
  background-color: #10b981;
}

.bg-danger {
  background-color: #ef4444;
}

.gap-2-5 {
  gap: 10px !important;
}

.fs-6-5 {
  font-size: 0.85rem !important;
}

.me-1-5 {
  margin-right: 6px !important;
}

.bg-light-subtle {
  background-color: #f8fafc !important;
}

.border-slate-100 {
  border-color: #f1f5f9 !important;
}
</style>
