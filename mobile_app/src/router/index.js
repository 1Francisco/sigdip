import { createRouter, createWebHistory } from 'vue-router';
import api from '../services/api.js';

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/productores',
    name: 'Productores',
    component: () => import('../views/ProductoresView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/productores/nuevo',
    name: 'NuevoProductor',
    component: () => import('../views/ProductoresFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/productores/editar/:id',
    name: 'EditarProductor',
    component: () => import('../views/ProductoresEditView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/scan',
    name: 'Scan',
    component: () => import('../views/ScanView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/sync',
    name: 'Sync',
    component: () => import('../views/SyncView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/visitas',
    name: 'Visitas',
    component: () => import('../views/VisitasView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/visitas/nuevo',
    name: 'NuevaVisita',
    component: () => import('../views/VisitaCreateView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/visitas/editar/:id',
    name: 'EditarVisita',
    component: () => import('../views/VisitaCreateView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/predios',
    name: 'Predios',
    component: () => import('../views/PrediosView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/predios/nuevo',
    name: 'NuevoPredio',
    component: () => import('../views/PredioCreateView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/inspecciones',
    name: 'Inspecciones',
    component: () => import('../views/InspeccionesView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/inspecciones/:id',
    name: 'InspeccionDetail',
    component: () => import('../views/InspeccionDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/inspeccion/:predioId?',
    name: 'Inspeccion',
    component: () => import('../views/InspeccionFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/medicos',
    name: 'Medicos',
    component: () => import('../views/MedicosView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/medicos/nuevo',
    name: 'NuevoMedico',
    component: () => import('../views/MedicoCreateView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/descargas',
    name: 'Descargas',
    component: () => import('../views/DescargasView.vue'),
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
