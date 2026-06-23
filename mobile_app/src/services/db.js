/**
 * Servicio de almacenamiento offline usando IndexedDB (via localForage)
 * Maneja los catálogos descargados y las inspecciones creadas sin internet.
 */

import localforage from 'localforage';

// Instancia para catálogos (predios, visitas, productores, medicos)
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

// Instancia para visitas pendientes de sincronizar
const visitaStore = localforage.createInstance({
  name: 'sigdip_mobile',
  storeName: 'visitas_pendientes',
  description: 'Visitas creadas offline que esperan sincronización'
});

// Función auxiliar para desvincular proxies reactivos de Vue antes de guardar en IndexedDB
function clean(obj) {
  return obj ? JSON.parse(JSON.stringify(obj)) : obj;
}

export default {
  // ====== CATÁLOGOS ======
  async savePredios(predios) {
    await catalogStore.setItem('predios', clean(predios));
  },

  async getPredios() {
    return (await catalogStore.getItem('predios')) || [];
  },

  async saveProductores(productores) {
    await catalogStore.setItem('productores', clean(productores));
  },

  async getProductores() {
    return (await catalogStore.getItem('productores')) || [];
  },

  async saveMedicos(medicos) {
    await catalogStore.setItem('medicos', clean(medicos));
  },

  async getMedicos() {
    return (await catalogStore.getItem('medicos')) || [];
  },

  async saveVisitas(visitas) {
    await catalogStore.setItem('visitas', clean(visitas));
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
      lista[idx] = clean(inspeccion); // Actualizar existente
    } else {
      lista.push(clean(inspeccion)); // Agregar nueva
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

  // ====== VISITAS OFFLINE ======
  async saveVisitaPendiente(visita) {
    const lista = await this.getVisitasPendientes();
    const idx = lista.findIndex(v => v.codigo === visita.codigo);
    if (idx >= 0) {
      lista[idx] = clean(visita);
    } else {
      lista.push(clean(visita));
    }
    await visitaStore.setItem('lista', lista);
  },

  async saveVisitasPendientes(visitas) {
    await visitaStore.setItem('lista', clean(visitas));
  },

  async getVisitasPendientes() {
    return (await visitaStore.getItem('lista')) || [];
  },

  async removeVisitaPendiente(codigo) {
    const lista = await this.getVisitasPendientes();
    const filtrado = lista.filter(v => v.codigo !== codigo);
    await visitaStore.setItem('lista', filtrado);
  },

  async clearVisitasSincronizadas(codigosSincronizados) {
    const lista = await this.getVisitasPendientes();
    const restantes = lista.filter(v => !codigosSincronizados.includes(v.codigo));
    await visitaStore.setItem('lista', restantes);
  },

  async countVisitasPendientes() {
    const lista = await this.getVisitasPendientes();
    return lista.length;
  },

  // ====== DASHBOARD CACHE ======
  async saveDashboardData(data) {
    await catalogStore.setItem('dashboard_data', clean(data));
  },

  async getDashboardData() {
    return await catalogStore.getItem('dashboard_data');
  },

  // ====== ANIMALES OFFLINE CACHE ======
  async saveAnimales(animales) {
    await catalogStore.setItem('animales', clean(animales));
  },

  async getAnimales() {
    return (await catalogStore.getItem('animales')) || [];
  },

  // ====== ARETES CENSO OFFLINE CACHE ======
  async saveAretesCenso(aretes) {
    await catalogStore.setItem('aretes_censo', clean(aretes));
  },

  async getAretesCenso() {
    return (await catalogStore.getItem('aretes_censo')) || [];
  },

  // ====== LIMPIEZA ======
  async clearAll() {
    await catalogStore.clear();
    await inspeccionStore.clear();
    await visitaStore.clear();
  }
};
