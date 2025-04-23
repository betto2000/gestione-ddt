// resources/js/components/Ddt/ScanQr.vue

<template>
  <div class="scan-container">
    <div class="scan-card">
      <div class="logo">
        <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
      </div>

      <h2>SCANSIONA QR CODE</h2>

      <div class="camera-container">
        <div class="qr-border">
          <button @click="startScan" class="camera-button">
            <i class="fas fa-camera"></i>
          </button>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger mt-3">
        {{ error }}
      </div>

      <!-- Componente per la scansione della fotocamera -->
      <div v-if="scanning" class="scanner-overlay">
        <div class="scanner-container">
          <div class="scanner-header">
            <h3>Inquadra il QR Code</h3>
            <button @click="stopScan" class="close-btn">&times;</button>
          </div>

          <div class="scanner-viewport">
            <video ref="video" class="scanner-video"></video>
            <canvas ref="canvas" class="scanner-canvas"></canvas>
            <div class="scanner-frame"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import jsQR from 'jsqr';

export default {
  data() {
    return {
      scanning: false,
      error: null,
      video: null,
      canvas: null,
      canvasCtx: null,
      animationFrame: null,
      stream: null,
      loading: false,
      debugQrCode: '',
      isDevEnvironment: process.env.NODE_ENV === 'development'
    };
  },

  methods: {
    ...mapActions('ddt', ['fetchDdtByQrCode']),

    startScan() {
      console.log("Avvio scansione");
      this.scanning = true;
      this.error = null;

      this.$nextTick(() => {
        this.initCamera();
      });
    },

    stopScan() {
      console.log("Stop scansione");
      this.scanning = false;

      if (this.animationFrame) {
        cancelAnimationFrame(this.animationFrame);
        this.animationFrame = null;
      }

      if (this.stream) {
        this.stream.getTracks().forEach(track => track.stop());
        this.stream = null;
      }
    },

    async initCamera() {
      console.log("Inizializzazione fotocamera");
      try {
        this.video = this.$refs.video;
        this.canvas = this.$refs.canvas;
        this.canvasCtx = this.canvas.getContext('2d');

        // Accesso alla fotocamera
        this.stream = await navigator.mediaDevices.getUserMedia({
          video: {
            facingMode: 'environment',
            width: { ideal: 1280 },
            height: { ideal: 720 }
          }
        });

        this.video.srcObject = this.stream;
        this.video.setAttribute('playsinline', true);

        // Attendi che il video sia pronto
        this.video.onloadedmetadata = () => {
          console.log("Video pronto per la riproduzione");
          this.video.play();

          // Imposta le dimensioni del canvas in base al video
          this.canvas.width = this.video.videoWidth;
          this.canvas.height = this.video.videoHeight;

          console.log("Dimensioni canvas:", this.canvas.width, "x", this.canvas.height);

          // Avvia la scansione
          this.scanQrCode();
        };
      } catch (err) {
        console.error("Errore accesso fotocamera:", err);
        this.error = 'Impossibile accedere alla fotocamera. Verifica i permessi.';
        this.scanning = false;
      }
    },

    scanQrCode() {
      if (!this.scanning) {
        console.log("Scansione interrotta");
        return;
      }

      // Verifica se il video è attivo e pronto
      if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
        // Disegna il frame del video sul canvas
        this.canvasCtx.drawImage(
          this.video,
          0,
          0,
          this.canvas.width,
          this.canvas.height
        );

        // Ottieni i dati dell'immagine dal canvas
        const imageData = this.canvasCtx.getImageData(
          0,
          0,
          this.canvas.width,
          this.canvas.height
        );

        // Usa jsQR per decodificare il QR code
        const code = jsQR(
          imageData.data,
          imageData.width,
          imageData.height,
          {
            inversionAttempts: "dontInvert"
          }
        );

        // Se viene trovato un QR code
        if (code) {
          console.log("QR Code rilevato:", code.data);
          this.handleQrCode(code.data);
          return;
        }
      }

      // Continua la scansione
      this.animationFrame = requestAnimationFrame(() => this.scanQrCode());
    },

    async handleQrCode(qrData) {
      console.log("Gestione QR code:", qrData);
      this.stopScan();

      if (!qrData) {
        this.error = 'QR Code non valido o vuoto';
        return;
      }

      try {
        this.loading = true;

        console.log("Invio richiesta al server con qr_code:", qrData);
        const response = await this.fetchDdtByQrCode({ qr_code: qrData });
        console.log("Risposta server:", response);

        if (response.success) {
          console.log("Documento trovato, navigazione a document-detail");
          console.log("SaleDocId:", response.document.SaleDocId);
          console.log("Dettagli:", response.details);

          const firstDetailLine = response.details && response.details.length > 0
            ? response.details[0].Line
            : null;

          console.log("Prima linea dettaglio:", firstDetailLine);

          // Naviga alla pagina dei dettagli del documento
          this.$router.push({
            name: 'document-detail',
            params: {
              saleDocId: response.document.SaleDocId,
              lineId: firstDetailLine
            }
          });
        } else {
          console.error("Errore recupero documento:", response.message);
          this.error = response.message || 'Documento non trovato';
        }
      } catch (err) {
        console.error("Eccezione durante la gestione del QR code:", err);
        this.error = 'Errore durante la lettura del QR Code';
      } finally {
        this.loading = false;
      }
    },
  }
};
</script>

<style scoped>
.scan-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
  background-color: #f5f5f5;
}

.scan-card {
  width: 100%;
  max-width: 400px;
  padding: 30px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.logo {
  margin-bottom: 20px;
}

.logo img {
  max-width: 180px;
  height: auto;
}

.camera-container {
  margin: 30px auto;
}

.qr-border {
  width: 200px;
  height: 200px;
  margin: 0 auto;
  border: 2px dashed #3490dc;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.camera-button {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: #3490dc;
  color: white;
  border: none;
  font-size: 30px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.camera-button:hover {
  background-color: #2779bd;
}

.scanner-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  z-index: 1000;
  display: flex;
  justify-content: center;
  align-items: center;
}

.scanner-container {
  width: 90%;
  max-width: 500px;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
}

.scanner-header {
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f8f9fa;
}

.scanner-header h3 {
  margin: 0;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.scanner-viewport {
  position: relative;
  height: 300px;
}

.scanner-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.scanner-canvas {
  display: none;
}

.scanner-frame {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 200px;
  height: 200px;
  border: 2px solid #3490dc;
  box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.5);
}

.alert {
  margin-top: 20px;
  padding: 10px;
  border-radius: 4px;
  background-color: #f8d7da;
  color: #721c24;
  text-align: left;
}

.debug-section {
  margin-top: 20px;
  padding: 15px;
  border: 1px dashed #ccc;
  border-radius: 4px;
}

.debug-section h3 {
  margin-top: 0;
  margin-bottom: 10px;
  font-size: 16px;
  color: #666;
}

.debug-input {
  width: 100%;
  padding: 8px;
  margin-bottom: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.debug-button {
  background-color: #6c757d;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}

.debug-button:hover {
  background-color: #5a6268;
}
</style>
