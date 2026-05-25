/**
 * Servicio de comunicación con la API de Laravel (SIGDIP Backend)
 */

// Configurado para acceso desde dispositivo real en la misma red WiFi
const API_BASE = 'http://192.168.1.91:8000/api'; 

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

  // Helpers
  isAuthenticated() {
    return !!getToken();
  },

  getCurrentUser() {
    const user = localStorage.getItem('sigdip_user');
    return user ? JSON.parse(user) : null;
  }
};
