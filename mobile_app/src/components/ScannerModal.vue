<template>
  <div class="scanner-modal-overlay">
    <div class="scanner-modal-content">
      <div class="scanner-modal-header">
        <h5 class="m-0"><i class="bi bi-qr-code-scan me-2"></i> Escanear Arete</h5>
        <button type="button" class="btn-close-scanner" @click="handleClose">✕</button>
      </div>
      <div class="scanner-modal-body">
        <div id="form-reader" class="scanner-preview-box"></div>
        <p class="scanner-instruction-text mt-2 mb-0">Apunta la cámara al código de barras del arete.</p>
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
      html5QrCode: null
    };
  },
  mounted() {
    this.$nextTick(() => this.start());
  },
  beforeUnmount() {
    this.stop();
  },
  methods: {
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
    async start() {
      const hasPermission = await this.checkAndRequestCameraPermission();
      if (!hasPermission) {
        alert("❌ Permiso de cámara no concedido. Por favor, habilita el permiso de cámara en la configuración de tu dispositivo o navegador para usar el escáner de aretes.");
        this.$emit('close');
        return;
      }

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
        alert("⚠️ No se pudo iniciar la cámara. Verifique los permisos de su dispositivo.");
        this.$emit('close');
      }
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
