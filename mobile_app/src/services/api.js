/**
 * Servicio de comunicación con la API de Laravel (SIGDIP Backend)
 */

import { CONFIG } from '../config.js';

const API_BASE = CONFIG.API_BASE_URL;
const FETCH_TIMEOUT = 20000;
const FETCH_TIMEOUT_QUICK = 8000;

// Cache de conectividad real para evitar repetir checks
let _lastConnCheck = { result: null, time: 0 };
const CONN_CHECK_TTL = 10000; // 10 segundos de cache

function getToken() {
  return localStorage.getItem('sigdip_token');
}

/**
 * Verificación rápida de conectividad REAL con el servidor.
 * navigator.onLine solo detecta si hay WiFi/datos, no si el servidor responde.
 * Retorna true si el servidor respondió dentro de 5 segundos.
 */
async function checkRealConnectivity() {
  // Si no hay red física, no intentar
  if (!navigator.onLine) return false;

  // Usar cache para no saturar con checks repetidos
  const now = Date.now();
  if (now - _lastConnCheck.time < CONN_CHECK_TTL && _lastConnCheck.result !== null) {
    return _lastConnCheck.result;
  }

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 5000);
  try {
    // Un HEAD request liviano al endpoint base de la API
    await fetch(`${API_BASE}/user`, {
      method: 'HEAD',
      signal: controller.signal,
      headers: {
        'Authorization': `Bearer ${getToken() || ''}`,
        'Accept': 'application/json'
      }
    });
    clearTimeout(timeoutId);
    _lastConnCheck = { result: true, time: now };
    return true;
  } catch (e) {
    clearTimeout(timeoutId);
    _lastConnCheck = { result: false, time: now };
    return false;
  }
}

/** Invalida el cache de conectividad (llamar cuando cambia el estado de red) */
function invalidateConnectivityCache() {
  _lastConnCheck = { result: null, time: 0 };
}

async function request(method, endpoint, body = null) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), FETCH_TIMEOUT);

  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  const token = getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const options = { method, headers, signal: controller.signal };
  if (body) {
    options.body = JSON.stringify(body);
  }

  try {
    const response = await fetch(`${API_BASE}${endpoint}`, options);
    clearTimeout(timeoutId);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `Error ${response.status}`);
    }

    return data;
  } catch (err) {
    clearTimeout(timeoutId);
    throw err;
  }
}

async function requestBlob(endpoint, acceptType = 'application/pdf') {
  const headers = {
    'Accept': acceptType,
  };

  const token = getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${endpoint}`, { headers });
  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new Error(data.message || `Error ${response.status}`);
  }

  return await response.blob();
}

async function hashPassword(password) {
  if (typeof crypto !== 'undefined' && crypto.subtle) {
    try {
      const msgBuffer = new TextEncoder().encode(password);
      const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    } catch (e) {
      console.warn('Web Crypto API failed, using fallback hash:', e);
    }
  }

  // Fallback hash algorithm (djb2)
  let hash = 5381;
  for (let i = 0; i < password.length; i++) {
    hash = (hash * 33) ^ password.charCodeAt(i);
  }
  return 'fallback_' + (hash >>> 0).toString(16);
}

function saveOfflineCredentials(email, passwordHash, user, token) {
  try {
    const offlineData = {
      email: email.toLowerCase().trim(),
      hash: passwordHash,
      user: user,
      token: token
    };
    localStorage.setItem('sigdip_offline_credentials', JSON.stringify(offlineData));
  } catch (e) {
    console.error('Error caching offline credentials:', e);
  }
}

function getOfflineCredentials() {
  const cached = localStorage.getItem('sigdip_offline_credentials');
  return cached ? JSON.parse(cached) : null;
}

async function tryOfflineLogin(email, password) {
  const offlineData = getOfflineCredentials();
  if (!offlineData) {
    throw new Error('Sin conexión a internet y no hay credenciales locales guardadas. Debes iniciar sesión con internet al menos una vez en este dispositivo.');
  }

  const passwordHash = await hashPassword(password);

  if (email.toLowerCase().trim() !== offlineData.email || passwordHash !== offlineData.hash) {
    throw new Error('Contraseña o correo incorrectos (Modo Offline)');
  }

  localStorage.setItem('sigdip_token', offlineData.token);
  localStorage.setItem('sigdip_user', JSON.stringify(offlineData.user));

  return {
    success: true,
    token: offlineData.token,
    user: offlineData.user,
    offline: true
  };
}

export default {
  // Autenticación
  async login(email, password) {
    // Si el dispositivo indica que está offline, saltar la petición de red
    if (!navigator.onLine) {
      return await tryOfflineLogin(email, password);
    }

    try {
      const data = await request('POST', '/login', { email, password });
      if (data.token) {
        localStorage.setItem('sigdip_token', data.token);
        localStorage.setItem('sigdip_user', JSON.stringify(data.user));

        const passwordHash = await hashPassword(password);
        saveOfflineCredentials(email, passwordHash, data.user, data.token);
      }
      return data;
    } catch (err) {
      // Si falló por red (timeout/offline), intentar con credenciales locales
      if (err.name === 'AbortError' || err instanceof TypeError) {
        return await tryOfflineLogin(email, password);
      }
      // Otro tipo de error (credenciales inválidas, servidor caído, etc.)
      // Si hay credenciales offline, intentar de todas formas
      if (getOfflineCredentials()) {
        try {
          return await tryOfflineLogin(email, password);
        } catch (offlineErr) {
          // Si offline también falla, lanzar el error original del servidor
          throw new Error(err.message || 'Credenciales incorrectas');
        }
      }
      throw err;
    }
  },

  async logout() {
    try {
      await request('POST', '/logout');
    } catch {
      // Ignorar errores de red al cerrar sesión
    } finally {
      localStorage.removeItem('sigdip_token');
      localStorage.removeItem('sigdip_user');
      // Mantener sigdip_offline_credentials para permitir re-login offline
    }
  },

  /**
   * Guarda o actualiza las credenciales offline con la sesión actual.
   * Útil para llamar cuando la app se pone en segundo plano o se detecta pérdida de conexión.
   */
  async cacheCurrentSession(password) {
    const token = getToken();
    const user = this.getCurrentUser();
    if (!token || !user) return;

    if (password) {
      const passwordHash = await hashPassword(password);
      saveOfflineCredentials(user.email, passwordHash, user, token);
    } else {
      const offlineData = getOfflineCredentials();
      if (offlineData) {
        // Actualizar token y usuario sin cambiar el hash de la contraseña
        offlineData.token = token;
        offlineData.user = user;
        localStorage.setItem('sigdip_offline_credentials', JSON.stringify(offlineData));
      }
    }
  },

  /**
   * Al iniciar la app con una sesión activa, actualiza las credenciales offline
   * con el token y usuario actuales (solo si ya existe un caché previo).
   * Si no hay caché previo con hash de contraseña, no se crea uno nuevo
   * (el usuario debe hacer login online al menos una vez para guardar el hash).
   */
  ensureOfflineCache() {
    const token = getToken();
    const user = this.getCurrentUser();
    if (!token || !user) return;

    const offlineData = getOfflineCredentials();
    if (offlineData) {
      // Actualizar token y usuario por si cambiaron
      offlineData.token = token;
      offlineData.user = user;
      localStorage.setItem('sigdip_offline_credentials', JSON.stringify(offlineData));
    }
  },

  async getUser() {
    return await request('GET', '/user');
  },

  // Sincronización
  async downloadCatalogos(params = {}) {
    const query = Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '';
    return await request('GET', '/sync/catalogos' + query);
  },

  async uploadInspecciones(inspecciones) {
    return await request('POST', '/sync/inspecciones', { inspecciones });
  },

  async uploadVisitas(visitas) {
    return await request('POST', '/sync/visitas', { visitas });
  },

  async uploadProductores(productores) {
    return await request('POST', '/sync/productores', { productores });
  },

  async uploadPredios(predios) {
    return await request('POST', '/sync/predios', { predios });
  },

  async getDashboardStats() {
    return await request('GET', '/dashboard/stats');
  },

  async getProductores() {
    return await request('GET', '/productores');
  },

  async getProductor(id) {
    return await request('GET', `/productores/${id}`);
  },

  async searchProductor(q) {
    return await request('GET', `/productores/buscar?q=${encodeURIComponent(q)}`);
  },

  async searchProductorPorClave(clave) {
    return await request('GET', `/productores/buscar-por-clave?clave=${encodeURIComponent(clave)}`);
  },

  async previewClaveProductor(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await request('GET', `/productores/preview-clave${query ? `?${query}` : ''}`);
  },

  async vincularProductorAHato(productorId, productorAAsignarId) {
    return await request('POST', `/productores/${productorId}/vincular-a-hato`, {
      productor_id: productorAAsignarId
    });
  },

  async desvincularProductorDeHato(productorId) {
    return await request('POST', `/productores/${productorId}/desvincular-de-hato`);
  },

  async getPredios() {
    return await request('GET', '/predios');
  },

  async getVisitas(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await request('GET', `/visitas${query ? `?${query}` : ''}`);
  },

  async getVisita(id) {
    return await request('GET', `/visitas/${id}`);
  },

  async checkVisitaCodigo(codigo) {
    try {
      return await request('GET', `/visitas/check-codigo/${encodeURIComponent(codigo)}`);
    } catch (e) {
      return { exists: false };
    }
  },

  async getVisitaByCodigo(codigo) {
    try {
      return await request('GET', `/visitas/by-codigo/${encodeURIComponent(codigo)}`);
    } catch (e) {
      return { exists: false };
    }
  },

  async createVisita(visita) {
    return await request('POST', '/visitas', visita);
  },

  async updateVisita(id, visita) {
    return await request('PUT', `/visitas/${id}`, visita);
  },

  async updateVisitaEstado(id, estado) {
    return await request('PATCH', `/visitas/${id}/estado`, { estado });
  },

  async reprogramarVisita(id, fecha_programada) {
    return await request('PATCH', `/visitas/${id}/reprogramar`, { fecha_programada });
  },

  async deleteVisita(id) {
    return await request('DELETE', `/visitas/${id}`);
  },

  async getInspecciones(params = {}) {
    if (!params.perPage) params.perPage = 500;
    const query = new URLSearchParams(params).toString();
    const res = await request('GET', `/inspecciones${query ? `?${query}` : ''}`);
    // Handle paginated API response — extract data array for backward compat
    if (res && res.pagination) {
      return { data: res.data };
    }
    return res;
  },

  async getInspeccion(id) {
    return await request('GET', `/inspecciones/${id}`);
  },

  async updateInspeccion(id, inspeccion) {
    return await request('PATCH', `/inspecciones/${id}`, inspeccion);
  },

  async deleteInspeccion(id) {
    return await request('DELETE', `/inspecciones/${id}`);
  },

  async getInspectionPdf(id) {
    return await requestBlob(`/inspecciones/${id}/pdf`);
  },

  async uploadDictamenComite(id, formData) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), FETCH_TIMEOUT);

    const headers = {
      'Accept': 'application/json',
    };

    const token = getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${API_BASE}/inspecciones/${id}/upload-dictamen-comite`, {
      method: 'POST',
      headers,
      body: formData,
      signal: controller.signal
    });
    clearTimeout(timeoutId);

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || `Error ${response.status}`);
    }

    return data;
  },

  async getDictamenComite(id) {
    return await requestBlob(`/inspecciones/${id}/download-dictamen-comite`);
  },

  async deleteDictamenComite(id) {
    return await request('DELETE', `/inspecciones/${id}/delete-dictamen-comite`);
  },

  async getSábanaExcel(params = {}) {
    const query = Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '';
    return await requestBlob('/reportes/sábana-excel' + query, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  },

  async getSabanaPdf(params = {}) {
    const query = Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '';
    return await requestBlob('/reportes/sabana-excel/pdf' + query, 'application/pdf');
  },

  async getSabanaData(params = {}) {
    const query = Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '';
    return await request('GET', '/reportes/sabana-excel/data' + query);
  },

  // Gestión de Productores y Predios
  async storeProductor(productor) {
    return await request('POST', '/productores', productor);
  },

  async updateProductor(id, productor) {
    return await request('PUT', `/productores/${id}`, productor);
  },

  async storeRancho(rancho) {
    return await request('POST', '/predios', rancho);
  },

  async updateRancho(id, rancho) {
    return await request('PUT', `/predios/${id}`, rancho);
  },

  async deleteProductor(id) {
    return await request('DELETE', `/productores/${id}`);
  },

  async deletePredio(id) {
    return await request('DELETE', `/predios/${id}`);
  },

  async updateCoordenadas(id, latitud, longitud) {
    return await request('POST', `/predios/${id}/coordenadas`, { latitud, longitud });
  },

  async getPredio(id) {
    return await request('GET', `/predios/${id}`);
  },

  async getMedicos() {
    return await request('GET', '/medicos');
  },

  async getMedico(id) {
    return await request('GET', `/medicos/${id}`);
  },

  async storeMedico(medico) {
    return await request('POST', '/medicos', medico);
  },

  async updateMedico(id, medico) {
    return await request('PUT', `/medicos/${id}`, medico);
  },

  async deleteMedico(id) {
    return await request('DELETE', `/medicos/${id}`);
  },

  // Asignación de Productores a Médicos
  async getProductoresAsignables(usuarioId) {
    return await request('GET', `/usuarios/${usuarioId}/productores-asignables`);
  },

  async asignarProductores(usuarioId, productorIds) {
    return await request('POST', `/usuarios/${usuarioId}/asignar-productores`, { productor_ids: productorIds });
  },

  async desasignarProductor(usuarioId, productorId) {
    return await request('POST', `/usuarios/${usuarioId}/desasignar-productor/${productorId}`);
  },

  async getAnimales(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await request('GET', `/animales${query ? `?${query}` : ''}`);
  },

  async getAnimal(id) {
    return await request('GET', `/animales/${id}`);
  },

  async createAnimal(animal) {
    return await request('POST', '/animales', animal);
  },

  async updateAnimal(id, animal) {
    return await request('PUT', `/animales/${id}`, animal);
  },

  async deleteAnimal(id) {
    return await request('DELETE', `/animales/${id}`);
  },

  async getAretesCenso(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await request('GET', `/aretes-censo${query ? `?${query}` : ''}`);
  },

  async getAreteCenso(id) {
    return await request('GET', `/aretes-censo/${id}`);
  },

  async createAreteCenso(data) {
    return await request('POST', '/aretes-censo', data);
  },

  async updateAreteCenso(id, data) {
    return await request('PUT', `/aretes-censo/${id}`, data);
  },

  async deleteAreteCenso(id) {
    return await request('DELETE', `/aretes-censo/${id}`);
  },

  async buscarArete(numero) {
    return await request('GET', `/censo/buscar-arete/${numero}`);
  },

  async getRendimiento(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await request('GET', `/reportes/rendimiento${query ? `?${query}` : ''}`);
  },

  // Helpers
  isAuthenticated() {
    return !!getToken();
  },

  getCurrentUser() {
    const user = localStorage.getItem('sigdip_user');
    return user ? JSON.parse(user) : null;
  },

  /**
   * Verifica si el servidor SIGDIP está realmente accesible (no solo WiFi conectado).
   * Usa cache de 10 segundos para evitar requests repetidos.
   */
  checkRealConnectivity,

  /**
   * Invalida el cache de conectividad. Llamar cuando el estado de red cambia.
   */
  invalidateConnectivityCache
};
