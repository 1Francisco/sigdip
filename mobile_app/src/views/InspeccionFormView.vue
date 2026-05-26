<template>
  <div class="app-container">
    <header class="app-header">
      <div>
        <h1>📝 Nuevo Dictamen</h1>
        <div class="subtitle">Prueba de Tuberculina</div>
      </div>
      <button @click="$router.push('/dashboard')" style="background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;">✕</button>
    </header>

    <main class="app-content">
      <!-- Paso 1: Datos generales -->
      <div class="card">
        <div class="card-title" style="margin-bottom: 16px;">📋 Datos Generales</div>

        <div class="form-group">
          <label class="form-label">Folio del Dictamen</label>
          <input v-model="form.folio" type="text" class="form-input" placeholder="Ej: DICT-2026-001" />
        </div>

        <div class="form-group">
          <label class="form-label">Predio / Rancho</label>
          <select v-model="form.predio_id" class="form-input form-select">
            <option value="">-- Seleccionar Predio --</option>
            <option v-for="p in predios" :key="p.id" :value="p.id">
              {{ p.nombre }} ({{ p.productor?.nombre || 'Sin productor' }})
            </option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Fecha de la Prueba</label>
          <input v-model="form.fecha" type="date" class="form-input" />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Fecha Inyección</label>
            <input v-model="form.fecha_inyeccion" type="date" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Hora Inyección</label>
            <input v-model="form.hora_inyeccion" type="time" class="form-input" />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Fecha Lectura</label>
            <input v-model="form.fecha_lectura" type="date" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Hora Lectura</label>
            <input v-model="form.hora_lectura" type="time" class="form-input" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Tipo de Prueba</label>
          <select v-model="form.tipo_prueba" class="form-input form-select">
            <option value="P.P.C.">P.P.C. (Pliegue Caudal)</option>
            <option value="P.C.C.">P.C.C. (Cervical Comparativa)</option>
            <option value="Otro">Otro</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Motivo de la Prueba</label>
          <select v-model="form.motivo_prueba" class="form-input form-select">
            <option value="Movilización">Movilización</option>
            <option value="Vigilancia Epidemiológica">Vigilancia Epidemiológica</option>
            <option value="Rastreo">Rastreo</option>
            <option value="Diagnóstico">Diagnóstico</option>
          </select>
        </div>
      </div>

      <!-- Paso 2: Animales escaneados -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">🐄 Animales ({{ form.animales.length }})</div>
          <button class="btn btn-accent" style="width: auto; padding: 8px 16px; font-size: 0.8rem;" @click="$router.push('/scan')">
            📷 Escanear
          </button>
        </div>

        <div v-if="form.animales.length === 0" class="empty-state">
          <div class="icon">🏷️</div>
          <p>No hay animales registrados.<br>Escanea los aretes o agrégalos manualmente.</p>
        </div>

        <div v-else>
          <div
            v-for="(animal, idx) in form.animales"
            :key="idx"
            class="animal-row"
            :class="{ positivo: animal.resultado === 'Positivo' }"
          >
            <span class="arete">{{ animal.identificador }}</span>
            <select v-model="animal.resultado" style="padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; border: 1px solid #ccc;">
              <option value="Negativo">✅ Neg</option>
              <option value="Positivo">🔴 Pos</option>
              <option value="Sospechoso">🟡 Sosp</option>
            </select>
            <button @click="form.animales.splice(idx, 1)" style="background:none;border:none;cursor:pointer;color:var(--color-danger);">🗑️</button>
          </div>
        </div>

        <!-- Agregar manual rápido -->
        <div style="display: flex; gap: 8px; margin-top: 12px;">
          <input v-model="quickArete" type="text" class="form-input" placeholder="Arete manual..." style="flex: 1;" @keyup.enter="addQuickAnimal" />
          <button class="btn btn-outline" style="width: auto; padding: 10px 16px;" @click="addQuickAnimal">+</button>
        </div>
      </div>

      <!-- Observaciones -->
      <div class="card">
        <div class="card-title" style="margin-bottom: 12px;">📝 Observaciones</div>
        <textarea v-model="form.observaciones" class="form-input" rows="3" placeholder="Observaciones generales del dictamen..."></textarea>
      </div>

      <!-- Botones de acción -->
      <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
        <button class="btn btn-primary btn-lg" @click="saveInspeccion('sincronizado')">
          ✅ Finalizar Dictamen
        </button>
        <button class="btn btn-outline" @click="saveInspeccion('borrador')">
          💾 Guardar como Borrador
        </button>
      </div>
    </main>

    <nav class="bottom-nav">
      <a @click.prevent="$router.push('/dashboard')">
        <span class="nav-icon">🏠</span>
        Inicio
      </a>
      <a @click.prevent="$router.push('/scan')">
        <span class="nav-icon">📷</span>
        Escanear
      </a>
      <a class="active" @click.prevent>
        <span class="nav-icon">📝</span>
        Dictamen
      </a>
      <a @click.prevent="$router.push('/sync')">
        <span class="nav-icon">🔄</span>
        Sync
      </a>
    </nav>
  </div>
</template>

<script>
import db from '../services/db.js';
import backgroundSync from '../services/backgroundSync.js';

export default {
  name: 'InspeccionFormView',
  data() {
    return {
      predios: [],
      quickArete: '',
      form: {
        folio: '',
        predio_id: '',
        fecha: new Date().toISOString().split('T')[0],
        fecha_inyeccion: '',
        hora_inyeccion: '',
        fecha_lectura: '',
        hora_lectura: '',
        tipo_prueba: 'P.P.C.',
        motivo_prueba: 'Movilización',
        funcion_zootecnica: '',
        observaciones: '',
        animales: []
      }
    };
  },
  async mounted() {
    this.predios = await db.getPredios();

    // Si viene de una visita específica
    const predioId = this.$route.params.predioId;
    if (predioId) {
      this.form.predio_id = parseInt(predioId);
    }

    // Si viene del escáner con animales
    const saved = sessionStorage.getItem('scanned_animals');
    if (saved) {
      this.form.animales = JSON.parse(saved);
      sessionStorage.removeItem('scanned_animals');
    }

    // Generar folio automático
    this.form.folio = 'SIGDIP-' + Date.now().toString(36).toUpperCase();

    // Visita ID si viene de la ruta
    const visitaId = this.$route.query.visita_id;
    if (visitaId) {
      this.form.visita_id = parseInt(visitaId);
    }
  },
  methods: {
    addQuickAnimal() {
      if (!this.quickArete.trim()) return;
      if (this.form.animales.find(a => a.identificador === this.quickArete.trim())) {
        alert('Este arete ya fue agregado');
        return;
      }
      this.form.animales.push({
        identificador: this.quickArete.trim(),
        raza: '',
        sexo: 'Macho',
        edad_meses: null,
        fierro: '',
        resultado: 'Negativo',
        observaciones: ''
      });
      this.quickArete = '';
    },

    async saveInspeccion(estado) {
      if (!this.form.folio || !this.form.predio_id) {
        alert('El folio y el predio son obligatorios.');
        return;
      }

      if (estado === 'sincronizado' && this.form.animales.length === 0) {
        alert('Debes agregar al menos un animal para finalizar el dictamen.');
        return;
      }

      this.form.estado = estado;

      await db.saveInspeccion({ ...this.form });

      if (estado === 'sincronizado') {
        alert('✅ Dictamen finalizado y guardado localmente. Se sincronizará automáticamente al detectar internet.');
        backgroundSync.syncIfConnected().catch(e => console.error(e));
      } else {
        alert('💾 Borrador guardado. Puedes editarlo después.');
      }

      this.$router.push('/dashboard');
    }
  }
};
</script>
