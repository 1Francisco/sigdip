<template>
  <div class="app-container">
    <header class="app-header">
      <div>
        <h1>📷 Escáner SINIIGA</h1>
        <div class="subtitle">Escanea el código de barras del arete</div>
      </div>
      <button @click="$router.push('/dashboard')" style="background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;">✕</button>
    </header>

    <main class="app-content">
      <!-- Vista de la cámara (simulación en web) -->
      <div class="scanner-viewport">
        <div class="scanner-overlay">
          <div class="scanner-frame">
            <div class="corner tl"></div>
            <div class="corner tr"></div>
            <div class="corner bl"></div>
            <div class="corner br"></div>
            <div class="scanner-line"></div>
          </div>
          <p class="scanner-hint">Apunta al código de barras del arete</p>
        </div>
        <!-- En dispositivos nativos, la cámara se inyectaría aquí -->
        <div class="camera-placeholder">
          <span>📷</span>
          <p>Cámara del dispositivo</p>
        </div>
      </div>

      <!-- Input manual como fallback -->
      <div class="card" style="margin-top: 16px;">
        <div class="card-title" style="margin-bottom: 12px;">✏️ Ingreso Manual</div>
        <p style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 12px;">
          Si el escáner no logra leer el código, escríbelo a mano aquí:
        </p>
        <div class="form-group">
          <input
            v-model="manualCode"
            type="text"
            class="form-input"
            placeholder="Ej: 09-1234-5678-0"
            @keyup.enter="addAnimal"
          />
        </div>
        <button class="btn btn-accent" @click="addAnimal" :disabled="!manualCode">
          <span class="btn-icon">➕</span> Agregar Animal
        </button>
      </div>

      <!-- Lista de aretes escaneados -->
      <div v-if="scannedAnimals.length" style="margin-top: 16px;">
        <div class="section-title">Aretes Escaneados ({{ scannedAnimals.length }})</div>
        <div
          v-for="(animal, idx) in scannedAnimals"
          :key="idx"
          class="animal-row"
          :class="{ positivo: animal.resultado === 'Positivo' }"
        >
          <span class="arete">🏷️ {{ animal.identificador }}</span>
          <select v-model="animal.resultado" style="padding: 6px; border-radius: 8px; border: 1px solid #ccc; font-size: 0.8rem;">
            <option value="Negativo">✅ Negativo</option>
            <option value="Positivo">🔴 Positivo</option>
            <option value="Sospechoso">🟡 Sospechoso</option>
          </select>
          <button @click="removeAnimal(idx)" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--color-danger);">🗑️</button>
        </div>
      </div>

      <!-- Botón para ir a formulario completo con estos animales -->
      <div v-if="scannedAnimals.length" style="margin-top: 20px;">
        <button class="btn btn-primary btn-lg" @click="goToForm">
          📝 Continuar al Dictamen ({{ scannedAnimals.length }} animales)
        </button>
      </div>
    </main>

    <nav class="bottom-nav">
      <a @click.prevent="$router.push('/dashboard')">
        <span class="nav-icon">🏠</span>
        Inicio
      </a>
      <a class="active" @click.prevent>
        <span class="nav-icon">📷</span>
        Escanear
      </a>
      <a @click.prevent="$router.push('/inspeccion')">
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
export default {
  name: 'ScanView',
  data() {
    return {
      manualCode: '',
      scannedAnimals: []
    };
  },
  mounted() {
    // Recuperar animales previos si volvemos de otra pantalla
    const saved = sessionStorage.getItem('scanned_animals');
    if (saved) this.scannedAnimals = JSON.parse(saved);
  },
  methods: {
    addAnimal() {
      if (!this.manualCode.trim()) return;

      // Verificar que no esté repetido
      if (this.scannedAnimals.find(a => a.identificador === this.manualCode.trim())) {
        alert('Este arete ya fue escaneado');
        return;
      }

      this.scannedAnimals.push({
        identificador: this.manualCode.trim(),
        raza: '',
        sexo: 'Macho',
        edad_meses: null,
        fierro: '',
        resultado: 'Negativo',
        observaciones: ''
      });

      this.manualCode = '';
      this.saveToSession();
    },
    removeAnimal(idx) {
      this.scannedAnimals.splice(idx, 1);
      this.saveToSession();
    },
    saveToSession() {
      sessionStorage.setItem('scanned_animals', JSON.stringify(this.scannedAnimals));
    },
    goToForm() {
      this.saveToSession();
      this.$router.push('/inspeccion');
    }
  }
};
</script>

<style scoped>
.scanner-viewport {
  width: 100%;
  height: 240px;
  background: #111;
  border-radius: 16px;
  overflow: hidden;
  position: relative;
}

.camera-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #555;
  gap: 8px;
}

.camera-placeholder span {
  font-size: 3rem;
  opacity: 0.5;
}

.camera-placeholder p {
  font-size: 0.75rem;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2;
  pointer-events: none;
}

.scanner-frame {
  width: 220px;
  height: 100px;
  position: relative;
}

.corner {
  position: absolute;
  width: 24px;
  height: 24px;
  border-color: #FF6F00;
  border-style: solid;
}

.corner.tl { top: 0; left: 0; border-width: 3px 0 0 3px; }
.corner.tr { top: 0; right: 0; border-width: 3px 3px 0 0; }
.corner.bl { bottom: 0; left: 0; border-width: 0 0 3px 3px; }
.corner.br { bottom: 0; right: 0; border-width: 0 3px 3px 0; }

.scanner-line {
  position: absolute;
  top: 50%;
  left: 10%;
  width: 80%;
  height: 2px;
  background: #FF6F00;
  animation: scanMove 2s linear infinite;
}

@keyframes scanMove {
  0%, 100% { top: 20%; }
  50% { top: 80%; }
}

.scanner-hint {
  color: rgba(255,255,255,0.7);
  font-size: 0.75rem;
  margin-top: 16px;
}
</style>
