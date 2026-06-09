<template>
  <router-view />
</template>

<script>
import api from './services/api.js';
import backgroundSync from './services/backgroundSync.js';
import { LocalNotifications } from '@capacitor/local-notifications';

export default {
  name: 'App',
  async mounted() {
    // 1. Asegurar que la sesión actual esté cacheada para login offline
    api.ensureOfflineCache();

    // 2. Inicializar la sincronización de fondo nativa (escuchar redes y arranques)
    try {
      await backgroundSync.init();
    } catch (e) {
      console.error('Error al inicializar el servicio de sincronización automática:', e);
    }

    // 3. Solicitar permisos de notificación amigablemente al arrancar la app
    try {
      const permission = await LocalNotifications.checkPermissions();
      if (permission.display !== 'granted') {
        await LocalNotifications.requestPermissions();
      }
    } catch (e) {
      console.warn('Notificaciones locales no soportadas en este entorno:', e);
    }
  }
};
</script>
