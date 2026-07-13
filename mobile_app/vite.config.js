import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': 'http://127.0.0.1:8000',
    },
  },
  optimizeDeps: {
    include: [
      '@capacitor/app',
      '@capacitor/filesystem',
      '@capacitor/camera',
      '@capacitor/core',
      'html5-qrcode',
      'localforage',
      'pinia',
      'vue',
      'vue-router'
    ]
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return;

          if (id.includes('@capacitor')) return 'capacitor';
          if (id.includes('vue')) return 'vue';
          if (id.includes('vue-router')) return 'router';
          if (id.includes('localforage')) return 'storage';
          if (id.includes('chart.js')) return 'charts';
        }
      }
    }
  }
});
