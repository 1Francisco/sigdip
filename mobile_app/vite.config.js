import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
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
