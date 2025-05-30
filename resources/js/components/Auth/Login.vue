<template>
  <div class="login-container">
    <div class="login-card">
      <div class="logo">
        <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
      </div>

      <h2>Accesso</h2>

      <div v-if="error" class="alert alert-danger">
        {{ error }}
      </div>

      <form @submit.prevent="login">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            class="form-control"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-container">
            <input
              :type="showPassword ? 'text' : 'password'"
              id="password"
              v-model="form.password"
              class="form-control password-input"
              required
              :disabled="loading"
            />
            <button
              type="button"
              class="password-toggle"
              @click="togglePasswordVisibility"
              :disabled="loading"
            >
              <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
        </div>

        <div class="form-group form-check">
          <input
            type="checkbox"
            id="certify"
            v-model="form.certifyDevice"
            class="form-check-input"
            :disabled="loading"
          />
          <label class="form-check-label" for="certify">
            Certifica questo dispositivo come sicuro
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          <span v-if="loading" class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            Accesso in corso...
          </span>
          <span v-else>
            Accedi
          </span>
        </button>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      form: {
        email: '',
        password: '',
        certifyDevice: false
      },
      error: null,
      loading: false,
      showPassword: false
    };
  },

  created() {
    // Verifica se il dispositivo è già certificato
    this.checkDeviceCertification();
  },

  methods: {
    togglePasswordVisibility() {
      this.showPassword = !this.showPassword;
    },

    async checkDeviceCertification() {
      const deviceId = localStorage.getItem('device_id');
      const token = localStorage.getItem('device_token');

      if (!deviceId || !token) {
        return;
      }

      try {
        const response = await axios.get('/check-device', {
          headers: {
            'Device-ID': deviceId,
            'Device-Token': token
          }
        });

        if (response.data.authenticated) {
          // Imposta headers per future richieste
          axios.defaults.headers.common['Device-ID'] = deviceId;
          axios.defaults.headers.common['Device-Token'] = token;

          // Aggiorna lo stato dell'applicazione
          this.$store.commit('auth/SET_USER', response.data.user);
          this.$store.commit('auth/SET_AUTHENTICATED', true);

          // Reindirizza
          this.$router.push('/scan');
        }
      } catch (error) {
        console.error('Errore verifica dispositivo:', error);
      }
    },

    async login() {
      this.loading = true;
      this.error = null;

      try {
        const deviceId = this.generateDeviceId();

        const response = await axios.post('/login', {
          email: this.form.email,
          password: this.form.password,
          device_id: deviceId,
          certify_device: this.form.certifyDevice
        });

        if (response.data.token) {
          // Salva i dati del dispositivo
          localStorage.setItem('device_id', deviceId);
          localStorage.setItem('device_token', response.data.token);

          // Imposta headers per future richieste
          axios.defaults.headers.common['Device-ID'] = deviceId;
          axios.defaults.headers.common['Device-Token'] = response.data.token;

          // Aggiorna lo stato dell'applicazione
          this.$store.commit('auth/SET_USER', response.data.user);
          this.$store.commit('auth/SET_AUTHENTICATED', true);

          // Reindirizza
          this.$router.push('/scan');
        }
      } catch (error) {
        console.error('Errore login:', error);
        this.error = 'Credenziali non valide';
      } finally {
        this.loading = false;
      }
    },

    generateDeviceId() {
      let deviceId = localStorage.getItem('device_id');

      if (!deviceId) {
        deviceId = 'device_' + Math.random().toString(36).substring(2, 15);
      }

      return deviceId;
    }
  }
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
  background-color: #f5f5f5;
}

.login-card {
  width: 100%;
  max-width: 400px;
  padding: 30px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.logo {
  text-align: center;
  margin-bottom: 20px;
}

.logo img {
  max-width: 180px;
  height: auto;
}

h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  transition: border-color 0.15s ease-in-out;
}

.form-control:focus {
  outline: none;
  border-color: #80bdff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.form-control:disabled {
  background-color: #f8f9fa;
  opacity: 0.65;
}

/* Styling per il container della password */
.password-container {
  position: relative;
}

.password-input {
  padding-right: 45px; /* Spazio per il pulsante dell'occhio */
}

.password-toggle {
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 45px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.3s;
}

.password-toggle:hover:not(:disabled) {
  color: #3490dc;
}

.password-toggle:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.form-check {
  display: flex;
  align-items: center;
  margin-top: 20px;
}

.form-check-input {
  margin-right: 8px;
}

.form-check-label {
  cursor: pointer;
  font-size: 14px;
}

.btn {
  display: block;
  width: 100%;
  padding: 12px;
  font-size: 16px;
  font-weight: 600;
  text-align: center;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background-color: #3490dc;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2779bd;
}

.btn-primary:disabled {
  background-color: #a0c5e8;
  cursor: not-allowed;
}

/* Styling per il contenuto di caricamento */
.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.loading-content i {
  font-size: 14px;
}

/* Animazione di rotazione per l'icona di caricamento */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.fa-spin {
  animation: spin 1s linear infinite;
}

/* Alert styling */
.alert {
  padding: 12px 16px;
  margin-bottom: 15px;
  border-radius: 4px;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
}

/* Responsive adjustments */
@media (max-width: 576px) {
  .login-card {
    padding: 20px;
  }

  .form-control {
    font-size: 14px;
    padding: 10px;
  }

  .password-input {
    padding-right: 40px;
  }

  .password-toggle {
    width: 40px;
  }
}
</style>
