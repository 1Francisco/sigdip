<template>
  <div class="card border-0 shadow-sm h-100 chart-card-wrapper">
    <div class="card-header bg-white border-bottom border-slate-100 d-flex justify-content-between align-items-center py-3">
      <span class="fw-bold text-dark">
        <i :class="iconClass" class="me-2 text-primary"></i> {{ title }}
      </span>
    </div>
    <div class="card-body">
      <div class="chart-canvas-container" :style="{ height: height + 'px' }">
        <canvas :ref="canvasRef"></canvas>
      </div>
      <div v-if="error" class="text-center text-muted small mt-2">{{ error }}</div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  name: 'ChartCard',
  props: {
    title: { type: String, required: true },
    type: { type: String, default: 'bar' },
    labels: { type: Array, default: () => [] },
    datasets: { type: Array, default: () => [] },
    height: { type: Number, default: 250 },
    iconClass: { type: String, default: 'bi bi-bar-chart-fill' },
    options: { type: Object, default: () => ({}) }
  },
  created() {
    this.chartInstance = null;
  },
  data() {
    return {
      error: null
    };
  },
  mounted() {
    this.$nextTick(() => {
      this.createChart();
    });
  },
  beforeUnmount() {
    this.destroyChart();
  },
  watch: {
    labels() {
      this.updateChart();
    },
    datasets() {
      this.updateChart();
    }
  },
  methods: {
    canvasRef(el) {
      this.$canvas = el;
    },
    createChart() {
      if (!this.$canvas || this.labels.length === 0) return;
      if (this.chartInstance) {
        try {
          this.chartInstance.destroy();
        } catch (e) {
          console.warn('Error destroying existing chart instance:', e);
        }
        this.chartInstance = null;
      }
      try {
        const defaultOptions = {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: this.type === 'doughnut' ? { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } : { display: false }
          },
          scales: this.type === 'bar' ? {
            y: { beginAtZero: true, grid: { drawBorder: false, color: '#f1f5f9' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
          } : undefined
        };

        const mergedOptions = this.deepMerge(defaultOptions, this.options);

        this.chartInstance = new Chart(this.$canvas, {
          type: this.type,
          data: { labels: this.labels, datasets: this.datasets },
          options: mergedOptions
        });
        this.error = null;
      } catch (e) {
        console.error('Chart creation error:', e);
        this.error = 'Error al cargar el gráfico';
      }
    },
    updateChart() {
      if (!this.$canvas) return;
      if (this.chartInstance) {
        this.chartInstance.data.labels = this.labels;
        this.chartInstance.data.datasets = this.datasets;
        this.chartInstance.update('none');
      } else {
        this.createChart();
      }
    },
    destroyChart() {
      if (this.chartInstance) {
        this.chartInstance.destroy();
        this.chartInstance = null;
      }
    },
    deepMerge(target, source) {
      const result = JSON.parse(JSON.stringify(target));
      for (const key of Object.keys(source)) {
        if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
          result[key] = this.deepMerge(result[key] || {}, source[key]);
        } else {
          result[key] = source[key];
        }
      }
      return result;
    }
  }
};
</script>

<style scoped>
.chart-card-wrapper {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}
.chart-canvas-container {
  position: relative;
  width: 100%;
}
.chart-canvas-container canvas {
  display: block;
  width: 100% !important;
  height: 100% !important;
}
</style>
