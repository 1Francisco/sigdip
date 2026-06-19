<template>
  <div class="app-container">
    <header class="app-header">
      <div>
        <h1>📷 Escáner SINIIGA</h1>
        <div class="subtitle" v-if="isSingleMode">Escanea el arete para el animal #{{ singleIndex + 1 }}</div>
        <div class="subtitle" v-else>Escanea el código de barras del arete</div>
      </div>
      <button @click="goBack" style="background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;">✕</button>
    </header>

    <main class="app-content">
      <!-- Vista de la cámara (HTML5 QrCode Stream) -->
      <div class="scanner-viewport">
        <div id="scanner-reader" style="width: 100%; height: 100%; background: black;"></div>
      </div>

      <!-- Input manual como fallback -->
      <div class="card" style="margin-top: 16px;">
        <div class="card-title" style="margin-bottom: 12px;">✏️ Ingreso Manual</div>
        <p style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 12px;">
          <template v-if="isSingleMode">
            Escribe el arete del animal y presiona "Confirmar":
          </template>
          <template v-else>
            Si el escáner no logra leer el código, escríbelo a mano aquí:
          </template>
        </p>
        <div class="form-group">
          <input
            v-model="manualCode"
            type="text"
            class="form-input"
            placeholder="Ej: 09-1234-5678-0"
            @keyup.enter="isSingleMode ? confirmSingle() : addAnimal()"
          />
        </div>

        <!-- Single-scan mode: confirm button -->
        <button v-if="isSingleMode" class="btn btn-accent" @click="confirmSingle" :disabled="!manualCode">
          <span class="btn-icon">✅</span> Confirmar Arete
        </button>

        <!-- Batch mode: add button -->
        <button v-else class="btn btn-accent" @click="addAnimal" :disabled="!manualCode">
          <span class="btn-icon">➕</span> Agregar Animal
        </button>
      </div>

      <!-- Lista de aretes escaneados (solo en modo batch) -->
      <template v-if="!isSingleMode">
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
              <option value="Pendiente">⏳ Pendiente</option>
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
      </template>
    </main>

    <nav class="bottom-nav">
      <a class="bottom-nav-link" :class="{ active: $route.path === '/dashboard' }" @click.prevent="$router.push('/dashboard')">
        <i class="bi" :class="$route.path === '/dashboard' ? 'bi-grid-1x2-fill' : 'bi-grid-1x2'"></i>
        <span>Inicio</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path.startsWith('/productores') }" @click.prevent="$router.push('/productores')">
        <i class="bi" :class="$route.path.startsWith('/productores') ? 'bi-people-fill' : 'bi-people'"></i>
        <span>Productores</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path.startsWith('/predios') }" @click.prevent="$router.push('/predios')">
        <i class="bi" :class="$route.path.startsWith('/predios') ? 'bi-house-door-fill' : 'bi-house-door'"></i>
        <span>Predios</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path.startsWith('/inspeccione') || $route.path.startsWith('/inspeccion') }" @click.prevent="$router.push('/inspecciones')">
        <i class="bi" :class="($route.path.startsWith('/inspeccione') || $route.path.startsWith('/inspeccion')) ? 'bi-clipboard-check-fill' : 'bi-clipboard-check'"></i>
        <span>Dictámenes</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path === '/sync' || $route.path === '/scan' }" @click.prevent="$router.push('/sync')">
        <i class="bi bi-arrow-repeat"></i>
        <span>Sincronizar</span>
      </a>
    </nav>
  </div>
</template>

<script>
import { Html5Qrcode } from 'html5-qrcode';
import { Camera } from '@capacitor/camera';
import { useInspeccionStore } from '../stores/inspeccion.js';

export default {
  name: 'ScanView',
  created() {
    this.inspeccionStore = useInspeccionStore();
  },
  data() {
    return {
      manualCode: '',
      scannedAnimals: [],
      isSingleMode: false,
      singleIndex: -1,
      html5QrCode: null
    };
  },
  async mounted() {
    // Check if we're in single-scan mode (coming from a specific animal row)
    const targetIndex = this.inspeccionStore.scanTargetIndex;
    if (targetIndex !== null) {
      this.isSingleMode = true;
      this.singleIndex = targetIndex;
    } else {
      // Batch mode: recover previously scanned animals if any
      if (this.inspeccionStore.scannedAnimals.length) this.scannedAnimals = [...this.inspeccionStore.scannedAnimals];
    }
    
    // Check and request camera permission first
    const hasPermission = await this.checkAndRequestCameraPermission();
    if (hasPermission) {
      this.startCamera();
    } else {
      alert("❌ Permiso de cámara no concedido. Por favor, habilita el permiso de cámara en la configuración de tu dispositivo o navegador para usar el escáner.");
      this.goBack();
    }
  },
  beforeUnmount() {
    this.stopCamera();
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
        sexo: 'H',
        edad_meses: null,
        fierro: 'Si',
        resultado: 'Pendiente',
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
      this.inspeccionStore.setScannedAnimals(this.scannedAnimals);
    },
    confirmSingle() {
      if (!this.manualCode.trim()) return;
      // Save the single scanned arete and go back to the form
      this.inspeccionStore.setScannedSingleArete(this.manualCode.trim().toUpperCase());
      // scan_target_index and inspeccion_draft remain in the store for the form to read
      this.$router.push('/inspeccion');
    },
    goToForm() {
      this.saveToSession();
      this.$router.push('/inspeccion');
    },
    goBack() {
      if (this.isSingleMode) {
        // Clean up single-scan store data on cancel
        this.inspeccionStore.clearScanTargetIndex();
        this.inspeccionStore.clearScannedSingleArete();
        // Keep inspeccionDraft so the form restores its state
        this.$router.push('/inspeccion');
      } else {
        this.$router.push('/dashboard');
      }
    },
    async checkAndRequestCameraPermission() {
      try {
        let status = await Camera.checkPermissions();
        if (status.camera === 'prompt' || status.camera === 'prompt-with-rationale') {
          status = await Camera.requestPermissions({ permissions: ['camera'] });
        }
        return status.camera === 'granted';
      } catch (e) {
        console.warn("Permisos nativos de cámara no soportados, usando fallback de navegador:", e);
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true });
          stream.getTracks().forEach(track => track.stop());
          return true;
        } catch (err) {
          console.error("Browser camera permission denied:", err);
          return false;
        }
      }
    },
    async startCamera() {
      this.$nextTick(async () => {
        try {
          this.html5QrCode = new Html5Qrcode("scanner-reader");
          const config = {
            fps: 10,
            qrbox: { width: 250, height: 150 }
          };
          await this.html5QrCode.start(
            { facingMode: "environment" },
            config,
            this.onScanSuccessCallback
          );
        } catch (err) {
          console.error("Error starting camera in ScanView:", err);
        }
      });
    },
    onScanSuccessCallback(decodedText) {
      this.manualCode = decodedText.trim().toUpperCase();
      if (this.isSingleMode) {
        this.confirmSingle();
      } else {
        this.addAnimal();
      }
    },
    async stopCamera() {
      if (this.html5QrCode) {
        if (this.html5QrCode.isScanning) {
          try {
            await this.html5QrCode.stop();
          } catch (e) {
            console.error("Error stopping camera in ScanView:", e);
          }
        }
        this.html5QrCode = null;
      }
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
