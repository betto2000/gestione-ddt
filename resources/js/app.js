import './bootstrap';
import './register-service-worker';
import Vue from 'vue';
import App from './components/App.vue';
import router from './router';
import store from './store';
import axios from 'axios';

axios.defaults.baseURL = '/api';

const deviceId = localStorage.getItem('device_id');
const deviceToken = localStorage.getItem('device_token');

if (deviceId && deviceToken) {
  axios.defaults.headers.common['Device-ID'] = deviceId;
  axios.defaults.headers.common['Device-Token'] = deviceToken;
}

axios.interceptors.request.use(config => {
    // Forza il token CSRF su ogni richiesta
    config.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content || '';
    return config;
});

// Interceptor per token scaduti o non validi
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && (error.response.status === 401 || error.response.status === 403)) {
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
