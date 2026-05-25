<template>
  <div class="login-screen">
    <div class="login-hero">
      <div class="login-logo">🐄</div>
      <h1 class="login-title">SIGDIP</h1>
      <p class="login-subtitle">Sistema Integral de Gestión y Digitalización<br>de Inspecciones Pecuarias</p>
    </div>

    <div class="login-form-wrapper">
      <div v-if="errorMsg" class="login-error">
        <span>⚠️</span> {{ errorMsg }}
      </div>

      <div class="form-group">
        <label class="form-label">Correo Electrónico</label>
        <input
          v-model="email"
          type="email"
          class="form-input"
          placeholder="medico@sigdip.com"
          @keyup.enter="doLogin"
        />
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <div style="position: relative;">
          <input
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            class="form-input"
            placeholder="••••••••"
            style="padding-right: 50px;"
            @keyup.enter="doLogin"
          />
          <button 
            type="button" 
            @click="showPassword = !showPassword" 
            style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; z-index: 10;"
          >
            <span v-if="showPassword">👁️</span>
            <span v-else>🙈</span>
          </button>
        </div>
      </div>

      <button class="btn btn-primary btn-lg" @click="doLogin" :disabled="loading">
        <span v-if="loading" class="loader"></span>
        <span v-else>🔐 Ingresar al Sistema</span>
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
        await api.login(this.email, this.password);
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
.login-screen {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(160deg, #0D3B12 0%, #1B5E20 40%, #2E7D32 100%);
}

.login-hero {
  flex: 0 0 auto;
  text-align: center;
  padding: 60px 24px 32px;
  color: #fff;
}

.login-logo {
  font-size: 4rem;
  margin-bottom: 12px;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.login-title {
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: 4px;
  margin-bottom: 8px;
}

.login-subtitle {
  font-size: 0.75rem;
  opacity: 0.8;
  line-height: 1.4;
}

.login-form-wrapper {
  flex: 1;
  background: #fff;
  border-radius: 28px 28px 0 0;
  padding: 32px 24px;
  box-shadow: 0 -8px 32px rgba(0,0,0,0.15);
}

.login-error {
  background: #FFEBEE;
  color: #D32F2F;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.login-footer {
  text-align: center;
  font-size: 0.65rem;
  color: #9CA3AF;
  margin-top: 24px;
  line-height: 1.4;
}
</style>
