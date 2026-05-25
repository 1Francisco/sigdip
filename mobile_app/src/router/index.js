import { createRouter, createWebHistory } from 'vue-router';
import api from '../services/api.js';

import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import ScanView from '../views/ScanView.vue';
import SyncView from '../views/SyncView.vue';
import InspeccionFormView from '../views/InspeccionFormView.vue';

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardView,
    meta: { requiresAuth: true }
  },
  {
    path: '/scan',
    name: 'Scan',
    component: ScanView,
    meta: { requiresAuth: true }
  },
  {
    path: '/sync',
    name: 'Sync',
    component: SyncView,
    meta: { requiresAuth: true }
  },
  {
    path: '/inspeccion/:predioId?',
    name: 'Inspeccion',
    component: InspeccionFormView,
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Guard de autenticación
router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth && !api.isAuthenticated()) {
    next('/login');
  } else if (to.name === 'Login' && api.isAuthenticated()) {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;
