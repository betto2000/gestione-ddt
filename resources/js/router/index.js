// resources/js/router/index.js

import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '../store';

// Componenti
import Login from '../components/Auth/Login.vue';
import ScanQr from '../components/Ddt/ScanQr.vue';
import DocumentDetail from '../components/Ddt/DocumentDetail.vue';
import Confirmation from '../components/Ddt/Confirmation.vue';
import SuccessPage from '../components/Ddt/SuccessPage.vue';
import ItemsList from '../components/Ddt/ItemList.vue';

// Evita avvisi di navigazione duplicata
const originalPush = VueRouter.prototype.push;
VueRouter.prototype.push = function push(location) {
  return originalPush.call(this, location).catch(err => {
    if (err.name !== 'NavigationDuplicated') throw err;
  });
};

Vue.use(VueRouter);

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/scan',
    name: 'scan-qr',
    component: ScanQr,
    meta: { requiresAuth: false } // Temporaneamente impostato a false per test
  },
  {
    path: '/documents/:saleDocId/details/:lineId?',
    name: 'document-detail',
    component: DocumentDetail,
    meta: { requiresAuth: false } // Temporaneamente impostato a false per test
  },
  {
    path: '/documents/:saleDocId/summary',
    name: 'document-summary',
    component: Confirmation,
    meta: { requiresAuth: false } // Temporaneamente impostato a false per test
  },
  {
    path: '/success',
    name: 'confirmation-success',
    component: SuccessPage,
    meta: { requiresAuth: false } // Temporaneamente impostato a false per test
  },
  {
    path: '/documents/:saleDocId/packages',
    name: 'items-list',
    component: ItemsList,  // Assicurati che il componente sia importato correttamente
    meta: { requiresAuth: false }  // Usa true in produzione
  },
  {
    path: '*',
    redirect: '/'
  }
];

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
});

// Gestione della navigazione e autenticazione
router.beforeEach((to, from, next) => {
  console.log(`Navigazione da ${from.path} a ${to.path}`);

  // Check per autorizzazione
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const isAuthenticated = store.getters['auth/isAuthenticated'];

  console.log(`requiresAuth: ${requiresAuth}, isAuthenticated: ${isAuthenticated}`);

  // Verifica se il dispositivo è già certificato (da localStorage o state)
  const deviceId = localStorage.getItem('device_id');
  const token = localStorage.getItem('token');

  console.log(`deviceId esiste: ${!!deviceId}, token esiste: ${!!token}`);

  // Se sono presenti token e device_id, verifica l'autenticazione
  if (deviceId && token && !isAuthenticated) {
    console.log("Verifica autenticazione con token esistente");
    store.dispatch('auth/checkAuth');
  }

  if (requiresAuth && !isAuthenticated) {
    console.log("Reindirizzamento a login - autenticazione richiesta");
    next('/login');
  } else if (to.path === '/login' && isAuthenticated) {
    console.log("Reindirizzamento a scan-qr - già autenticato");
    next('/scan');
  } else {
    console.log("Navigazione permessa");
    next();
  }
});

export default router;
