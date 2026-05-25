/**
 * Servicio de almacenamiento offline usando IndexedDB (via localForage)
 * Maneja los catálogos descargados y las inspecciones creadas sin internet.
 */

import localforage from 'localforage';

// Instancia para catálogos (predios, visitas)
const catalogStore = localforage.createInstance({
  name: 'sigdip_mobile',
  storeName: 'catalogos',
  description: 'Catálogos descargados del servidor para uso offline'
});

// Instancia para inspecciones pendientes de sincronizar
const inspeccionStore = localforage.createInstance({
  name: 'sigdip_mobile',
  storeName: 'inspecciones_pendientes',
  description: 'Dictámenes creados offline que esperan sincronización'
});

export default {
  // ====== CATÁLOGOS ======
  async savePredios(predios) {
    await catalogStore.setItem('predios', predios);
  },

  async getPredios() {
    return (await catalogStore.getItem('predios')) || [];
  },

  async saveVisitas(visitas) {
    await catalogStore.setItem('visitas', visitas);
  },

  async getVisitas() {
    return (await catalogStore.getItem('visitas')) || [];
  },

  async getLastSync() {
    return await catalogStore.getItem('last_sync');
  },

  async setLastSync() {
    await catalogStore.setItem('last_sync', new Date().toISOString());
  },

  // ====== INSPECCIONES OFFLINE ======
  async saveInspeccion(inspeccion) {
    const lista = await this.getInspeccionesPendientes();
    // Usar el folio como ID único
    const idx = lista.findIndex(i => i.folio === inspeccion.folio);
    if (idx >= 0) {
      lista[idx] = inspeccion; // Actualizar existente
    } else {
      lista.push(inspeccion); // Agregar nueva
    }
    await inspeccionStore.setItem('lista', lista);
  },

  async getInspeccionesPendientes() {
    return (await inspeccionStore.getItem('lista')) || [];
  },

  async removeInspeccion(folio) {
    const lista = await this.getInspeccionesPendientes();
    const filtrado = lista.filter(i => i.folio !== folio);
    await inspeccionStore.setItem('lista', filtrado);
  },

  async clearInspeccionesSincronizadas(foliosSincronizados) {
    const lista = await this.getInspeccionesPendientes();
    const restantes = lista.filter(i => !foliosSincronizados.includes(i.folio));
    await inspeccionStore.setItem('lista', restantes);
  },

  async countPendientes() {
    const lista = await this.getInspeccionesPendientes();
    return lista.length;
  },

  // ====== LIMPIEZA ======
  async clearAll() {
    await catalogStore.clear();
    await inspeccionStore.clear();
  }
};
