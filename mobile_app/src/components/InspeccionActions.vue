<template>
  <div>
    <!-- Botones de Acción para Escritorio -->
    <div class="d-none d-lg-flex align-items-stretch gap-3 mt-4 mb-5">
      <button type="button" class="btn-borrador-card" @click="$emit('save', 'borrador')">
        <i class="bi bi-box-arrow-in-down fs-4"></i>
        <span>Borrador</span>
      </button>

      <button
        type="button"
        class="btn-finalizar-row flex-grow-1"
        :class="{ 'blocked': finalizarBloqueado }"
        :disabled="finalizarBloqueado"
        @click="$emit('save', 'sincronizado')"
      >
        <template v-if="finalizarBloqueado && !inyeccionConfirmada">
          <i class="bi bi-lock-fill"></i> Finalizar Inyección (Bloqueado)
        </template>
        <template v-else-if="finalizarBloqueado && inyeccionConfirmada">
          <i class="bi bi-lock-fill"></i> Finalizar Lectura (Bloqueado)
        </template>
        <template v-else-if="!inyeccionConfirmada">
          <i class="bi bi-check-circle-fill"></i> Finalizar Inyección
        </template>
        <template v-else>
          <i class="bi bi-check-circle"></i> Finalizar
        </template>
      </button>
    </div>

    <!-- Sticky Bottom Actions for Mobile -->
    <div class="mobile-sticky-actions d-flex d-lg-none">
      <button type="button" class="btn-mobile-borrador" @click="$emit('save', 'borrador')">
        <i class="bi bi-box-arrow-in-down fs-4"></i>
        <span>Borrador</span>
      </button>

      <button
        type="button"
        class="btn-mobile-finalizar flex-grow-1"
        :class="{ 'blocked': finalizarBloqueado }"
        :disabled="finalizarBloqueado"
        @click="$emit('save', 'sincronizado')"
      >
        <template v-if="finalizarBloqueado && !inyeccionConfirmada">
          <i class="bi bi-lock-fill"></i> Finalizar Inyección (Bloqueado)
        </template>
        <template v-else-if="finalizarBloqueado && inyeccionConfirmada">
          <i class="bi bi-lock-fill"></i> Finalizar Lectura (Bloqueado)
        </template>
        <template v-else-if="!inyeccionConfirmada">
          <i class="bi bi-check-circle-fill"></i> Finalizar Inyección
        </template>
        <template v-else>
          <i class="bi bi-cloud-arrow-up-fill"></i> Finalizar Dictamen
        </template>
      </button>
    </div>

    <!-- Floating Action Button for Adding Animals on Mobile -->
    <button type="button" class="btn-fab-add d-lg-none" @click="$emit('add-animal')" title="Añadir Animal">
      <i class="bi bi-plus-lg fs-4"></i>
    </button>
  </div>
</template>

<script>
export default {
  name: 'InspeccionActions',
  props: {
    finalizarBloqueado: { type: Boolean, default: false },
    enFaseLectura: { type: Boolean, default: false },
    inyeccionConfirmada: { type: Boolean, default: false }
  },
  emits: ['save', 'add-animal']
};
</script>

<style scoped>
.btn-borrador-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #f8fafc;
  border: 1.5px solid #cbd5e1;
  border-radius: 18px;
  padding: 12px 24px;
  color: #0f172a;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 100px;
}

.btn-borrador-card:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.btn-borrador-card i {
  color: #334155;
}

.btn-finalizar-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 18px;
  padding: 16px 24px;
  font-weight: 700;
  font-size: 1.05rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.btn-finalizar-row:hover {
  background: #1d4ed8;
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.mobile-sticky-actions {
  position: fixed;
  bottom: calc(70px + env(safe-area-inset-bottom, 0px));
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  padding: 12px 16px;
  border-top: 1px solid #eef2f7;
  z-index: 75;
  display: flex;
  gap: 12px;
  box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.05);
}

.btn-mobile-borrador {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: white;
  border: 1.5px solid #cbd5e1;
  border-radius: 14px;
  padding: 8px 16px;
  color: #334155;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
}

.btn-mobile-borrador:active {
  background-color: #f1f5f9;
}

.btn-mobile-finalizar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 14px;
  padding: 12px 20px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
}

.btn-mobile-finalizar.blocked {
  background: #94a3b8 !important;
  color: #f1f5f9 !important;
  box-shadow: none !important;
  cursor: not-allowed;
}

.btn-mobile-finalizar:active:not(.blocked) {
  background: #1d4ed8;
}

.btn-fab-add {
  position: fixed;
  bottom: calc(160px + env(safe-area-inset-bottom, 0px));
  right: 20px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background-color: #2563eb;
  color: white;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
  z-index: 76;
  cursor: pointer;
  transition: transform 0.2s ease, background-color 0.2s ease;
}

.btn-fab-add:active {
  transform: scale(0.9);
  background-color: #1d4ed8;
}
</style>
