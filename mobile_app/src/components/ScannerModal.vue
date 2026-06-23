<template>
  <div class="scanner-modal-overlay">
    <div class="scanner-modal-content">
      <div class="scanner-modal-header">
        <h5 class="m-0"><i class="bi bi-qr-code-scan me-2"></i> Escanear Arete</h5>
        <button type="button" class="btn-close-scanner" @click="handleClose">✕</button>
      </div>
      <div class="scanner-modal-body">
        <div v-if="permissionDenied" class="text-center py-4">
          <div style="font-size: 3rem; margin-bottom: 8px;">📷</div>
          <h5 class="fw-bold text-danger mb-2">Permiso de cámara denegado</h5>
          <p class="text-muted small mb-3 px-3">Para escanear aretes, la app necesita acceso a la cámara. Presiona "Reintentar" para solicitarlo de nuevo.</p>
          <button class="btn btn-primary w-100 mb-2" :disabled="retrying" @click="retryPermission">
            <span v-if="retrying" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <i v-else class="bi bi-camera me-2"></i>
            {{ retrying ? 'Solicitando...' : 'Reintentar' }}
          </button>
          <button class="btn btn-outline-secondary w-100" @click="handleClose">
            <i class="bi bi-x-lg me-2"></i> Cancelar
          </button>
        </div>
        <template v-else>
          <div id="form-reader" class="scanner-preview-box"></div>
          <p class="scanner-instruction-text mt-2 mb-0">Apunta la cámara al código de barras del arete.</p>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { Html5Qrcode } from 'html5-qrcode';
import { Camera } from '@capacitor/camera';

export default {
  name: 'ScannerModal',
  emits: ['scanned', 'close'],
  data() {
    return {
      html5QrCode: null,
      permissionDenied: false,
      retrying: false
    };
  },
  mounted() {
    this.$nextTick(() => this.start());
  },
  beforeUnmount() {
    this.stop();
  },
  methods: {
    async start() {
      this.permissionDenied = false;
      try {
        const status = await Camera.checkPermissions();
        if (status.camera === 'granted') {
          this.initCamera();
          return;
        } else if (status.camera === 'prompt' || status.camera === 'prompt-with-rationale') {
          const reqStatus = await Camera.requestPermissions({ permissions: ['camera'] });
          if (reqStatus.camera === 'granted') {
            this.initCamera();
            return;
          }
        }
        if (status.camera === 'denied') {
          this.permissionDenied = true;
          return;
        }
      } catch (e) {
        console.warn("Camera checkPermissions/requestPermissions not available:", e);
      }
      // Web fallback: try starting camera directly
      this.initCamera();
    },
    async initCamera() {
      this.permissionDenied = false;
      this.$nextTick(async () => {
        try {
          this.html5QrCode = new Html5Qrcode("form-reader");
          const config = {
            fps: 10,
            qrbox: { width: 260, height: 160 }
          };
          await this.html5QrCode.start(
            { facingMode: "environment" },
            config,
            this.onScanSuccess
          );
        } catch (err) {
          console.error("Error starting camera scanner:", err);
          this.permissionDenied = true;
          if (this.html5QrCode) {
            try {
              await this.html5QrCode.clear();
            } catch (e) {}
            this.html5QrCode = null;
          }
        }
      });
    },
    async requestCameraPermission() {
      this.retrying = true;
      try {
        let status = await Camera.checkPermissions();
        if (status.camera !== 'granted') {
          status = await Camera.requestPermissions({ permissions: ['camera'] });
        }
        if (status.camera === 'granted') {
          this.retrying = false;
          this.permissionDenied = false;
          return true;
        }
        this.retrying = false;
        this.permissionDenied = true;
        return false;
      } catch (e) {
        console.warn("Plugin no disponible, usando getUserMedia:", e);
      }
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        stream.getTracks().forEach(track => track.stop());
        this.retrying = false;
        this.permissionDenied = false;
        return true;
      } catch (err) {
        console.error("Camera permission denied:", err);
        this.retrying = false;
        this.permissionDenied = true;
        return false;
      }
    },
    async retryPermission() {
      const ok = await this.requestCameraPermission();
      if (!ok) return;
      this.initCamera();
    },
    async stop() {
      if (this.html5QrCode) {
        if (this.html5QrCode.isScanning) {
          try {
            await this.html5QrCode.stop();
          } catch (err) {
            console.error("Error stopping scanner:", err);
          }
        }
        this.html5QrCode = null;
      }
    },
    onScanSuccess(decodedText) {
      this.$emit('scanned', decodedText);
      this.stop();
    },
    handleClose() {
      this.stop();
      this.$emit('close');
    }
  }
};
</script>

<style scoped>
.scanner-modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 16px;
  backdrop-filter: blur(4px);
}

.scanner-modal-content {
  background: white;
  width: 100%;
  max-width: 450px;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
}

.scanner-modal-header {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #2563eb;
  color: white;
}

.btn-close-scanner {
  background: transparent;
  border: none;
  color: white;
  font-size: 1.25rem;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.scanner-modal-body {
  padding: 20px;
  text-align: center;
  background-color: #f8fafc;
}

.scanner-preview-box {
  width: 100%;
  aspect-ratio: 4/3;
  border-radius: 12px;
  overflow: hidden;
  background-color: black;
  border: 2px solid #e2e8f0;
}

.scanner-instruction-text {
  font-size: 0.82rem;
  color: #64748b;
}
</style>
