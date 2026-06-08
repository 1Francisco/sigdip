<template>
  <div class="login-screen">
    <div class="bg-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
    </div>

    <div class="login-container">
      <div class="logo-area">
        <img src="/icon_png.png" alt="SIGDIP" class="brand-logo" />
        <h1>SIGDIP</h1>
        <p>Sistema Integral de Gestión y Dictamen</p>
      </div>

      <div v-if="errorMsg" class="error-message">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ errorMsg }}
      </div>

      <div class="form-group">
        <label for="email">Correo Electrónico</label>
        <input
          id="email"
          v-model="email"
          type="email"
          class="form-control"
          placeholder="admin@sigdip.com"
          @keyup.enter="doLogin"
          required
          autofocus
        />
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div style="position: relative;">
          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            class="form-control"
            placeholder="••••••••"
            style="padding-right: 50px;"
            @keyup.enter="doLogin"
            required
          />
          <button 
            type="button" 
            @click="showPassword = !showPassword" 
            style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #4c956c; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; z-index: 10;"
          >
            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </button>
        </div>
      </div>

      <button class="btn-login" @click="doLogin" :disabled="loading">
        <span v-if="loading" class="loader"></span>
        <span v-else>Ingresar al Panel</span>
      </button>

      <p class="login-footer">Comité Estatal para el Fomento y Protección Pecuaria<br>del Estado de Nayarit</p>
    </div>
  </div>
</template>

<script>
import api from '../services/api.js';

export default {
  name: 'LoginView',
  data() {
    return {
      email: '',
      password: '',
      loading: false,
      errorMsg: '',
      showPassword: false
    };
  },
  methods: {
    async doLogin() {
      if (!this.email || !this.password) {
        this.errorMsg = 'Ingresa tu correo y contraseña';
        return;
      }
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.login(this.email, this.password);
        if (res && res.offline) {
          alert('🔑 Inicio de sesión local (Modo Offline). Podrás trabajar en campo sin internet y los datos se sincronizarán al recuperar la conexión.');
        }
        this.$router.push('/dashboard');
      } catch (err) {
        this.errorMsg = err.message || 'Credenciales incorrectas';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');

.login-screen {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #eef2f3 0%, #8e9eab 100%);
  position: relative;
  padding: 20px 10px;
  font-family: 'Outfit', sans-serif;
  overflow: hidden;
  box-sizing: border-box;
}

/* Contenedor de figuras de fondo */
.bg-shapes {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

/* Dynamic Background Shapes */
.shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
  animation: float 10s infinite ease-in-out alternate;
}

.shape-1 {
  width: 300px;
  height: 300px;
  background: rgba(44, 110, 73, 0.4);
  top: -50px;
  left: -50px;
  animation-delay: 0s;
}

.shape-2 {
  width: 400px;
  height: 400px;
  background: rgba(214, 140, 69, 0.3);
  bottom: -100px;
  right: -50px;
  animation-delay: 2s;
}

@keyframes float {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(20px, 30px) scale(1.1); }
}

.login-container {
  position: relative;
  z-index: 1;
  width: 95%;
  max-width: 400px;
  padding: 35px 25px;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 24px;
  box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
  text-align: left;
}

.logo-area {
  text-align: center;
  margin-bottom: 25px;
}

.brand-logo {
  width: 72px;
  height: 72px;
  object-fit: contain;
  margin-bottom: 12px;
}

.logo-area h1 {
  color: #2c6e49; /* Deep Green from login.blade.php */
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin: 0 0 5px 0;
}

.logo-area p {
  color: #555;
  font-size: 14px;
  font-weight: 300;
  margin: 0;
}

.form-group {
  margin-bottom: 20px;
  position: relative;
  text-align: left;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #2b2d42;
  font-size: 14px;
  font-weight: 600;
}

.form-control {
  width: 100%;
  padding: 14px 20px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  font-size: 15px;
  color: #2b2d42;
  transition: all 0.3s ease;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  background: #fff;
  border-color: #4c956c;
  box-shadow: 0 0 0 4px rgba(76, 149, 108, 0.1);
}

.btn-login {
  width: 100%;
  padding: 15px;
  background: #2c6e49;
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 20px rgba(44, 110, 73, 0.2);
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-login:hover {
  background: #4c956c;
  transform: translateY(-2px);
  box-shadow: 0 15px 25px rgba(44, 110, 73, 0.3);
}

.btn-login:active {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.error-message {
  background: #fee2e2;
  color: #dc2626;
  padding: 12px 15px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 20px;
  border-left: 4px solid #dc2626;
  display: flex;
  align-items: center;
  gap: 8px;
  text-align: left;
}

.login-footer {
  text-align: center;
  font-size: 0.65rem;
  color: #9CA3AF;
  margin-top: 24px;
  margin-bottom: 0;
  line-height: 1.4;
}

.loader {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
