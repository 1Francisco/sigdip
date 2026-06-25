import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router/index.js';
import './assets/css/main.css';

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}

const app = createApp(App);
const pinia = createPinia();
if (window.Cypress && window.__initialPiniaState) {
  pinia.state.value = window.__initialPiniaState;
}
app.use(pinia);
app.use(router);
app.mount('#app');

if (window.Cypress) {
  window.__pinia = pinia;
  import('./stores/inspeccion.js').then(({ useInspeccionStore }) => {
    window.__inspeccionStore = useInspeccionStore(pinia);
  });
}
