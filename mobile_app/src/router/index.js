import { createRouter, createWebHashHistory } from 'vue-router';
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
    path: '/productores/:id',
    name: 'ProductorDetail',
    component: () => import('../views/ProductorDetailView.vue'),
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
    path: '/visitas/:id',
    name: 'VisitaDetail',
    component: () => import('../views/VisitaDetailView.vue'),
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
    path: '/predios/editar/:id',
    name: 'EditarPredio',
    component: () => import('../views/PredioCreateView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/predios/:id',
    name: 'PredioDetail',
    component: () => import('../views/PredioDetailView.vue'),
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
    path: '/inspeccion/editar/:id',
    name: 'EditarInspeccion',
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
    path: '/medicos/editar/:id',
    name: 'EditarMedico',
    component: () => import('../views/MedicoEditView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/medicos/asignar/:id',
    name: 'AsignarProductores',
    component: () => import('../views/AsignarProductoresView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/animales',
    name: 'Animales',
    component: () => import('../views/AnimalesListView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/animales/nuevo',
    name: 'NuevoAnimal',
    component: () => import('../views/AnimalFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/animales/editar/:id',
    name: 'EditarAnimal',
    component: () => import('../views/AnimalFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/animales/:id',
    name: 'AnimalDetail',
    component: () => import('../views/AnimalDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/aretes-censo',
    name: 'AretesCenso',
    component: () => import('../views/AretesCensoListView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/aretes-censo/nuevo',
    name: 'NuevoAreteCenso',
    component: () => import('../views/AreteCensoFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/aretes-censo/editar/:id',
    name: 'EditarAreteCenso',
    component: () => import('../views/AreteCensoFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/aretes-censo/:id',
    name: 'AreteCensoDetail',
    component: () => import('../views/AreteCensoDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/descargas',
    name: 'Descargas',
    component: () => import('../views/DescargasView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/reportes/rendimiento',
    name: 'Rendimiento',
    component: () => import('../views/RendimientoView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/reportes/sabana-excel',
    name: 'SabanaExcel',
    component: () => import('../views/SabanaExcelView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
  }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }
    return { top: 0 };
  }
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
