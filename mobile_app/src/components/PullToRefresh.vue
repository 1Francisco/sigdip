<template>
  <div
    class="ptr-container"
    @touchstart="onTouchStart"
    @touchmove="onTouchMove"
    @touchend="onTouchEnd"
    @touchcancel="onTouchEnd"
  >
    <div class="ptr-indicator" :class="{ visible: pulling || refreshing }">
      <div v-if="refreshing" class="ptr-spinner"></div>
      <div v-else class="ptr-arrow" :style="{ transform: `rotate(${pullDistance * 1.8}deg)` }">
        <i class="bi bi-arrow-down"></i>
      </div>
    </div>
    <div class="ptr-content" :class="{ 'ptr-pulled': pulling || refreshing }" :style="pullStyle">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: 'PullToRefresh',
  props: {
    loading: { type: Boolean, default: false }
  },
  emits: ['refresh'],
  data() {
    return {
      startY: 0,
      currentY: 0,
      pulling: false,
      refreshing: false,
      threshold: 80
    };
  },
  computed: {
    pullDistance() {
      if (this.refreshing) return this.threshold;
      if (!this.pulling) return 0;
      return Math.max(0, Math.min(this.currentY - this.startY, this.threshold * 1.5));
    },
    pullStyle() {
      if (this.refreshing) return { transform: `translateY(${this.threshold}px)` };
      if (!this.pulling) return {};
      const distance = Math.max(0, this.currentY - this.startY);
      const eased = Math.min(distance * 0.4, this.threshold);
      return { transform: `translateY(${eased}px)`, transition: 'none' };
    }
  },
  watch: {
    loading(val) {
      if (!val && this.refreshing) {
        this.refreshing = false;
        this.pulling = false;
        this.startY = 0;
        this.currentY = 0;
      }
    }
  },
  methods: {
    onTouchStart(e) {
      if (this.refreshing) return;
      if (window.scrollY > 0) return;
      this.startY = e.touches[0].clientY;
      this.currentY = this.startY;
      this.pulling = true;
    },
    onTouchMove(e) {
      if (!this.pulling || this.refreshing) return;
      this.currentY = e.touches[0].clientY;
      const diff = this.currentY - this.startY;
      if (diff <= 0) {
        this.pulling = false;
      }
    },
    onTouchEnd() {
      if (!this.pulling || this.refreshing) return;
      if (this.pullDistance >= this.threshold) {
        this.refreshing = true;
        this.$emit('refresh');
      }
      this.pulling = false;
    }
  }
};
</script>

<style scoped>
.ptr-container {
  position: relative;
}

.ptr-indicator {
  position: absolute;
  top: -60px;
  left: 0;
  right: 0;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2563eb;
  font-size: 1.2rem;
  transition: top 0.3s ease;
  z-index: 5;
  pointer-events: none;
}

.ptr-indicator.visible {
  top: 0;
  pointer-events: auto;
}

.ptr-arrow {
  transition: transform 0.2s ease;
}

.ptr-spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: ptr-spin 0.6s linear infinite;
}

@keyframes ptr-spin {
  to { transform: rotate(360deg); }
}

.ptr-content {
  transition: transform 0.3s ease;
  will-change: transform;
}

.ptr-content.ptr-pulled {
  transition: transform 0.25s ease;
}
</style>
