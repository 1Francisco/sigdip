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

  /**
   * Incremental upsert: merge incoming records into existing array.
   * New records are appended; existing records (matched by id) are updated.
   */
  async upsertPredios(incoming) {
    if (!incoming || incoming.length === 0) return;
    const existing = await this.getPredios();
    const map = new Map(existing.map(p => [String(p.id), p]));
    for (const item of incoming) {
      map.set(String(item.id), item);
    }
    await this.savePredios(Array.from(map.values()));
  },

  async upsertProductores(incoming) {
    if (!incoming || incoming.length === 0) return;
    const existing = await this.getProductores();
    const map = new Map(existing.map(p => [String(p.id), p]));
    for (const item of incoming) {
      map.set(String(item.id), item);
    }
    await this.saveProductores(Array.from(map.values()));
  },

  async upsertVisitas(incoming) {
    if (!incoming || incoming.length === 0) return;
    const existing = await this.getVisitas();
    const map = new Map(existing.map(v => [String(v.id), v]));
    for (const item of incoming) {
      map.set(String(item.id), item);
    }
    await this.saveVisitas(Array.from(map.values()));
  },

  // ====== INSPECCIONES OFFLINE ======
  _inspeccionKey(inspeccion) {
    return inspeccion.clave_interna || inspeccion.folio || `temp_${Date.now()}`;
  },
  async saveInspeccion(inspeccion) {
    const lista = await this.getInspeccionesPendientes();
    const key = this._inspeccionKey(inspeccion);
    const idx = lista.findIndex(i => this._inspeccionKey(i) === key);
    if (idx >= 0) {
      lista[idx] = clean(inspeccion);
    } else {
      lista.push(clean(inspeccion));
    }
    await inspeccionStore.setItem('lista', lista);
  },

  async getInspeccionesPendientes() {
    return (await inspeccionStore.getItem('lista')) || [];
  },

  async removeInspeccion(key) {
    const lista = await this.getInspeccionesPendientes();
    const filtrado = lista.filter(i => this._inspeccionKey(i) !== key);
    await inspeccionStore.setItem('lista', filtrado);
  },

  async clearInspeccionesSincronizadas(keysSincronizados) {
    const lista = await this.getInspeccionesPendientes();
    const restantes = lista.filter(i => !keysSincronizados.includes(this._inspeccionKey(i)));
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

  async saveDashboardKpis(kpis) {
    await catalogStore.setItem('dashboard_kpis', clean(kpis));
  },

  async getDashboardKpis() {
    return await catalogStore.getItem('dashboard_kpis');
  },

  async saveMedicosRanking(ranking) {
    await catalogStore.setItem('medicos_ranking', clean(ranking));
  },

  async getMedicosRanking() {
    return await catalogStore.getItem('medicos_ranking') || [];
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

  // ====== OFFLINE PENDING HELPERS ======
  async getProductoresPendientes() {
    const all = await this.getProductores();
    return all.filter(p => String(p.id).startsWith('OFFLINE_PROD_'));
  },

  async getPrediosPendientes() {
    const all = await this.getPredios();
    return all.filter(p => String(p.id).startsWith('OFFLINE_PREDIO_'));
  },

  async remapProductorId(oldId, newId) {
    // 1. Remapear en store de productores
    const productores = await this.getProductores();
    const prodIdx = productores.findIndex(p => String(p.id) === String(oldId));
    if (prodIdx >= 0) {
      productores[prodIdx].id = newId;
      await this.saveProductores(productores);
    }

    // 2. Remapear en predios (productor_id)
    const predios = await this.getPredios();
    let changed = false;
    predios.forEach(p => {
      if (String(p.productor_id) === String(oldId)) {
        p.productor_id = newId;
        changed = true;
      }
      if (p.productor && String(p.productor.id) === String(oldId)) {
        p.productor.id = newId;
        changed = true;
      }
    });
    if (changed) await this.savePredios(predios);
  },

  async remapPredioId(oldId, newId) {
    // 1. Remapear en store de predios
    const predios = await this.getPredios();
    const predIdx = predios.findIndex(p => String(p.id) === String(oldId));
    if (predIdx >= 0) {
      predios[predIdx].id = newId;
      await this.savePredios(predios);
    }

    // 2. Remapear en inspecciones pendientes
    const inspecciones = await this.getInspeccionesPendientes();
    let inspChanged = false;
    inspecciones.forEach(i => {
      if (String(i.predio_id) === String(oldId)) {
        i.predio_id = newId;
        inspChanged = true;
      }
    });
    if (inspChanged) await inspeccionStore.setItem('lista', clean(inspecciones));

    // 3. Remapear en visitas pendientes
    const visitas = await this.getVisitasPendientes();
    let visChanged = false;
    visitas.forEach(v => {
      if (String(v.predio_id) === String(oldId)) {
        v.predio_id = newId;
        visChanged = true;
      }
    });
    if (visChanged) await visitaStore.setItem('lista', clean(visitas));
  },

  // ====== LIMPIEZA ======
  async clearAll() {
    await catalogStore.clear();
    await inspeccionStore.clear();
    await visitaStore.clear();
  }
};
