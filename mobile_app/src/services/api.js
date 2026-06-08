/**
 * Servicio de comunicación con la API de Laravel (SIGDIP Backend)
 */

import { CONFIG } from '../config.js';

const API_BASE = CONFIG.API_BASE_URL; 

function getToken() {
  return localStorage.getItem('sigdip_token');
}

async function request(method, endpoint, body = null) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  const token = getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const options = { method, headers };
  if (body) {
    options.body = JSON.stringify(body);
  }

  const response = await fetch(`${API_BASE}${endpoint}`, options);
  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || `Error ${response.status}`);
  }

  return data;
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
  
  // Fallback hash algorithm (e.g. djb2 / custom simple hash)
  let hash = 5381;
  for (let i = 0; i < password.length; i++) {
    hash = (hash * 33) ^ password.charCodeAt(i);
  }
  return 'fallback_' + (hash >>> 0).toString(16);
}

export default {
  // Autenticación
  async login(email, password) {
    let onlineFailed = false;
    let errorToThrow = null;

    try {
      const data = await request('POST', '/login', { email, password });
      if (data.token) {
        localStorage.setItem('sigdip_token', data.token);
        localStorage.setItem('sigdip_user', JSON.stringify(data.user));
        
        // Cache credentials for offline login
        try {
          const passwordHash = await hashPassword(password);
          const offlineData = {
            email: email.toLowerCase().trim(),
            hash: passwordHash,
            user: data.user,
            token: data.token
          };
          localStorage.setItem('sigdip_offline_credentials', JSON.stringify(offlineData));
        } catch (e) {
          console.error('Error caching offline credentials:', e);
        }
      }
      return data;
    } catch (err) {
      onlineFailed = true;
      errorToThrow = err;
    }

    if (onlineFailed) {
      // Try offline login fallback
      const cached = localStorage.getItem('sigdip_offline_credentials');
      if (cached) {
        try {
          const offlineData = JSON.parse(cached);
          const passwordHash = await hashPassword(password);
          
          if (email.toLowerCase().trim() === offlineData.email && passwordHash === offlineData.hash) {
            // Restore session
            localStorage.setItem('sigdip_token', offlineData.token);
            localStorage.setItem('sigdip_user', JSON.stringify(offlineData.user));
            return {
              success: true,
              token: offlineData.token,
              user: offlineData.user,
              offline: true // Mark as offline login
            };
          } else {
            throw new Error('Contraseña o correo incorrectos (Modo Offline)');
          }
        } catch (e) {
          console.error('Error checking offline credentials:', e);
          throw e;
        }
      }
      // If offline login failed or no cached credentials, throw a friendly error
      throw new Error('⚠️ Sin conexión a internet y no hay credenciales locales guardadas. Debes iniciar sesión con internet al menos una vez en este dispositivo.');
    }
  },

  async logout() {
    try {
      await request('POST', '/logout');
    } finally {
      localStorage.removeItem('sigdip_token');
      localStorage.removeItem('sigdip_user');
    }
  },

  async getUser() {
    return await request('GET', '/user');
  },

  // Sincronización
  async downloadCatalogos() {
    return await request('GET', '/sync/catalogos');
  },

  async uploadInspecciones(inspecciones) {
    return await request('POST', '/sync/inspecciones', { inspecciones });
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

  async getInspecciones(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await request('GET', `/inspecciones${query ? `?${query}` : ''}`);
  },

  async getInspeccion(id) {
    return await request('GET', `/inspecciones/${id}`);
  },

  async updateInspeccion(id, inspeccion) {
    return await request('PATCH', `/inspecciones/${id}`, inspeccion);
  },

  async getInspectionPdf(id) {
    return await requestBlob(`/inspecciones/${id}/pdf`);
  },

  async getSábanaExcel() {
    return await requestBlob('/reportes/sábana-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
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

  async getMedicos() {
    return await request('GET', '/medicos');
  },

  async storeMedico(medico) {
    return await request('POST', '/medicos', medico);
  },

  async deleteMedico(id) {
    return await request('DELETE', `/medicos/${id}`);
  },

  async buscarArete(numero) {
    return await request('GET', `/censo/buscar-arete/${numero}`);
  },

  // Helpers
  isAuthenticated() {
    return !!getToken();
  },

  getCurrentUser() {
    const user = localStorage.getItem('sigdip_user');
    return user ? JSON.parse(user) : null;
  }
};
