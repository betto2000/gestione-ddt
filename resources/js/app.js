import './bootstrap';
import './register-service-worker';
import Vue from 'vue';
import App from './components/App.vue';
import router from './router';
import store from './store';
import axios from 'axios';

axios.defaults.baseURL = '/api';

// Configurazione CSRF token
function setupCSRFToken() {
  const token = document.querySelector('meta[name="csrf-token"]');
  if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
  } else {
    console.error('CSRF token not found');
  }
}

// Configura il token CSRF all'avvio
setupCSRFToken();

const deviceId = localStorage.getItem('device_id');
const deviceToken = localStorage.getItem('device_token');

if (deviceId && deviceToken) {
  axios.defaults.headers.common['Device-ID'] = deviceId;
  axios.defaults.headers.common['Device-Token'] = deviceToken;
}

axios.interceptors.request.use(config => {
    // Forza il token CSRF su ogni richiesta
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
      config.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
    }

    // Assicurati che device headers siano sempre presenti se disponibili
    const currentDeviceId = localStorage.getItem('device_id');
    const currentDeviceToken = localStorage.getItem('device_token');

    if (currentDeviceId) {
      config.headers['Device-ID'] = currentDeviceId;
    }
    if (currentDeviceToken) {
      config.headers['Device-Token'] = currentDeviceToken;
    }

    return config;
}, error => {
    return Promise.reject(error);
});

// Interceptor per token scaduti o non validi
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && (error.response.status === 401 || error.response.status === 403)) {
        // Se è un errore CSRF, prova a ricaricare il token
      if (error.response.data.message && error.response.data.message.includes('CSRF')) {
        console.log('CSRF token mismatch, tentativo di refresh...');
        // In questo caso potresti voler ricaricare la pagina o fare un refresh del token
        window.location.reload();
        return;
      }
      store.dispatch('auth/logout').then(() => {
        router.push('/login');
      });
    }
    return Promise.reject(error);
  }
);

// Middleware di navigazione
router.beforeEach((to, from, next) => {
    // Check per autorizzazione
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const isAuthenticated = store.getters['auth/isAuthenticated'];

    // Verifica se il dispositivo è già certificato (da localStorage o state)
    const deviceId = localStorage.getItem('device_id');
    const token = localStorage.getItem('token');
    const isDeviceCertified = store.getters['auth/isDeviceCertified'];

    // Se sono presenti token e device_id, verifica l'autenticazione
    if (deviceId && token && !isAuthenticated) {
      store.dispatch('auth/checkAuth');
    }

    if (requiresAuth && !isAuthenticated) {
      next('/login');
    } else if (to.path === '/login' && isAuthenticated) {
      next('/scan');
    } else {
      next();
    }
  });


// Componente principale
Vue.component('app', App);

new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App)
});
