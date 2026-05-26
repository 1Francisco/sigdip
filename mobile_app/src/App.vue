<template>
  <router-view />
</template>

<script>
import backgroundSync from './services/backgroundSync.js';
import { LocalNotifications } from '@capacitor/local-notifications';

export default {
  name: 'App',
  async mounted() {
    // 1. Inicializar la sincronización de fondo nativa (escuchar redes y arranques)
    try {
      await backgroundSync.init();
    } catch (e) {
      console.error('Error al inicializar el servicio de sincronización automática:', e);
    }

    // 2. Solicitar permisos de notificación amigablemente al arrancar la app
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
