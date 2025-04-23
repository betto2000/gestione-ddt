<template>
    <div class="certify-container">
      <div class="certify-card">
        <div class="logo">
          <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
        </div>

        <h2>Certifica Dispositivo</h2>

        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <p class="info-text">
          Certificando questo dispositivo, non ti verranno più richieste le credenziali di accesso.
          Procedi solo se sei su un dispositivo sicuro e personale.
        </p>

        <div class="action-buttons">
          <button @click="certifyDevice" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Certificazione in corso...' : 'Certifica Dispositivo' }}
          </button>
          <button @click="skipCertification" class="btn btn-secondary">
            Non certificare
          </button>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { mapActions } from 'vuex';

  export default {
    data() {
      return {
        error: null,
        loading: false
      };
    },

    methods: {
      ...mapActions('auth', ['certifyDevice']),

      async certifyAndContinue() {
        this.loading = true;
        this.error = null;

        try {
          const deviceId = localStorage.getItem('device_id');

          if (!deviceId) {
            this.error = 'Impossibile identificare il dispositivo';
            return;
          }

          const response = await this.certifyDevice({ device_id: deviceId });

          if (response.success) {
            this.$router.push({ name: 'scan-qr' });
          } else {
            this.error = 'Errore durante la certificazione del dispositivo';
          }
        } catch (err) {
          this.error = 'Si è verificato un errore. Riprova più tardi.';
          console.error(err);
        } finally {
          this.loading = false;
        }
      },

      skipCertification() {
        this.$router.push({ name: 'scan-qr' });
      }
    }
  };
  </script>

  <style scoped>
  .certify-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    background-color: #f5f5f5;
  }

  .certify-card {
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

  h2 {
    margin-bottom: 20px;
    color: #333;
  }

  .info-text {
    margin-bottom: 30px;
    color: #666;
    line-height: 1.6;
  }

  .action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .btn {
    padding: 12px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  .btn-primary {
    background-color: #3490dc;
    color: white;
    border: none;
  }

  .btn-primary:hover {
    background-color: #2779bd;
  }

  .btn-secondary {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ced4da;
  }

  .btn-secondary:hover {
    background-color: #e2e6ea;
  }

  @media (max-width: 576px) {
    .certify-card {
      padding: 20px;
    }
  }
  </style>
