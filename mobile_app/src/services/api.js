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

export default {
  // Autenticación
  async login(email, password) {
    const data = await request('POST', '/login', { email, password });
    if (data.token) {
      localStorage.setItem('sigdip_token', data.token);
      localStorage.setItem('sigdip_user', JSON.stringify(data.user));
    }
    return data;
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
