<template>
  <div v-if="visible" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div class="toast show align-items-center border-0 shadow-lg" :class="toastClass" role="alert">
      <div class="d-flex">
        <div class="toast-body fw-semibold d-flex align-items-center gap-2">
          <i class="bi" :class="iconClass"></i>
          {{ message }}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="hide"></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Toast',
  data() {
    return {
      visible: false,
      message: '',
      type: 'success',
      timer: null,
    };
  },
  computed: {
    toastClass() {
      return {
        'bg-success text-white': this.type === 'success',
        'bg-danger text-white': this.type === 'error',
        'bg-warning text-dark': this.type === 'warning',
        'bg-info text-white': this.type === 'info',
      };
    },
    iconClass() {
      return {
        'bi-check-circle-fill': this.type === 'success',
        'bi-exclamation-circle-fill': this.type === 'error',
        'bi-exclamation-triangle-fill': this.type === 'warning',
        'bi-info-circle-fill': this.type === 'info',
      };
    },
  },
  methods: {
    show(msg, type = 'success', duration = 3000) {
      this.message = msg;
      this.type = type;
      this.visible = true;
      if (this.timer) clearTimeout(this.timer);
      if (duration > 0) {
        this.timer = setTimeout(() => this.hide(), duration);
      }
    },
    hide() {
      this.visible = false;
      this.message = '';
      if (this.timer) clearTimeout(this.timer);
    },
  },
};
</script>
