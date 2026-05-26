import { Network } from '@capacitor/network';
import { LocalNotifications } from '@capacitor/local-notifications';
import api from './api.js';
import db from './db.js';

let isSyncing = false;

export default {
  /**
   * Inicializa los listeners nativos del sistema operativo.
   */
  async init() {
    console.log('[BackgroundSync] Inicializando listeners nativos...');

    // 1. Escuchar cambios de conexión (Offline -> Online)
    Network.addListener('networkStatusChange', async (status) => {
      console.log('[BackgroundSync] Cambio de red detectado:', status);
      if (status.connected) {
        await this.syncIfConnected();
      }
    });

    // 2. Ejecutar una verificación inicial al arrancar
    const status = await Network.getStatus();
    if (status.connected) {
      await this.syncIfConnected();
    }
  },

  /**
   * Ejecuta la sincronización masiva si hay conexión y datos pendientes.
   */
  async syncIfConnected() {
    if (isSyncing) {
      console.log('[BackgroundSync] Sincronización en curso. Omitiendo duplicado.');
      return;
    }

    const status = await Network.getStatus();
    if (!status.connected) {
      console.log('[BackgroundSync] Dispositivo sin conexión. Omitiendo sincronización.');
      return;
    }

    // Verificar si el usuario está autenticado en la app móvil
    if (!api.isAuthenticated()) {
      console.log('[BackgroundSync] Usuario no autenticado. Omitiendo sincronización.');
      return;
    }

    const pendientesCount = await db.countPendientes();
    if (pendientesCount === 0) {
      console.log('[BackgroundSync] No hay dictámenes pendientes por subir.');
      return;
    }

    isSyncing = true;
    console.log(`[BackgroundSync] Iniciando sincronización de ${pendientesCount} dictámenes...`);

    try {
      const inspecciones = await db.getInspeccionesPendientes();
      
      // Subir al backend de Laravel
      const res = await api.uploadInspecciones(inspecciones);

      if (res.status === 'success' && res.procesados && res.procesados.length > 0) {
        // Limpiar los dictámenes subidos exitosamente de la base de datos IndexedDB local
        await db.clearInspeccionesSincronizadas(res.procesados);
        
        console.log(`[BackgroundSync] Sincronizados exitosamente: ${res.procesados.length} dictámenes.`);

        // Disparar evento global para que las pantallas Vue recarguen sus datos inmediatamente si están activas
        window.dispatchEvent(new CustomEvent('sigdip-sync-complete', {
          detail: { procesados: res.procesados.length, errores: res.errores ? res.errores.length : 0 }
        }));

        // Enviar notificación push local nativa
        await this.sendLocalNotification(res.procesados.length);
      }
    } catch (error) {
      console.error('[BackgroundSync] Error crítico en la sincronización automática de fondo:', error);
    } finally {
      isSyncing = false;
    }
  },

  /**
   * Envía una notificación nativa local a la barra de estado de Android/iOS.
   */
  async sendLocalNotification(count) {
    try {
      // Verificar si tenemos permisos antes de enviar
      const permission = await LocalNotifications.checkPermissions();
      if (permission.display !== 'granted') {
        const req = await LocalNotifications.requestPermissions();
        if (req.display !== 'granted') {
          console.warn('[BackgroundSync] Permisos de notificación denegados.');
          return;
        }
      }

      await LocalNotifications.schedule({
        notifications: [
          {
            title: "🔄 Sincronización Automática",
            body: `Se subieron con éxito ${count} dictámenes pecuarios pendientes al servidor del CEFPPENAY.`,
            id: 1,
            sound: true,
            smallIcon: "res://ic_stat_sync", // Icono nativo para Android
            actionTypeId: "OPEN_DASHBOARD"
          }
        ]
      });
    } catch (e) {
      console.error('[BackgroundSync] Error al enviar notificación local:', e);
    }
  }
};
