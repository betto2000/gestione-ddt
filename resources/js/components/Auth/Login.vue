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
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            class="form-control"
            required
          />
        </div>

        <div class="form-group form-check">
          <input
            type="checkbox"
            id="certify"
            v-model="form.certifyDevice"
            class="form-check-input"
          />
          <label class="form-check-label" for="certify">
            Certifica questo dispositivo come sicuro
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? 'Accesso in corso...' : 'Accedi' }}
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
      }
    };
  },

  created() {
    // Controlla se il dispositivo è già certificato
    this.checkDeviceCertification();
  },

  methods: {
    generateDeviceId() {
      // Genera un ID dispositivo univoco o recuperalo se già esiste
      let deviceId = localStorage.getItem('device_id');

      if (!deviceId) {
        // Crea un identificatore unico
        deviceId = 'device_' + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('device_id', deviceId);
      }

      return deviceId;
    },

    async checkDeviceCertification() {
      const deviceId = localStorage.getItem('device_id');
      const token = localStorage.getItem('device_token');

      if (!deviceId || !token) {
        return;
      }

      try {
        // Controlla se il dispositivo è già certificato
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
        console.error('Errore nel controllo del dispositivo:', error);
      }
    },

    async login() {
      try {
        const deviceId = this.generateDeviceId();

        const response = await axios.post('/login', {
          email: this.form.email,
          password: this.form.password,
          device_id: deviceId,
          certify_device: this.form.certifyDevice
        });

        if (response.data.token) {
          // Salva i dati nel localStorage
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
      }
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

.form-check {
  margin-top: 20px;
}

.btn-primary {
  background-color: #3490dc;
  color: white;
  border: none;
  padding: 12px;
  font-size: 16px;
  transition: background-color 0.3s;
}

.btn-primary:hover {
  background-color: #2779bd;
}

.btn-block {
  display: block;
  width: 100%;
}

@media (max-width: 576px) {
  .login-card {
    padding: 20px;
  }
}
</style>
