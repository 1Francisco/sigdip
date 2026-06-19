<template>
  <div class="searchable-select" ref="wrapper">
    <div
      class="searchable-select-trigger"
      :class="{ 'is-open': isOpen, 'is-invalid': hasError }"
      @click="toggle"
    >
      <span v-if="selectedLabel" class="selected-label">{{ selectedLabel }}</span>
      <span v-else class="placeholder-text">{{ placeholder }}</span>
      <i class="bi bi-chevron-down chevron" :class="{ rotated: isOpen }"></i>
    </div>
    <div v-if="isOpen" class="searchable-select-dropdown">
      <div class="dropdown-search">
        <i class="bi bi-search search-icon"></i>
        <input
          ref="searchInput"
          v-model="search"
          type="text"
          class="dropdown-search-input"
          :placeholder="searchPlaceholder"
          @input="onSearchInput"
        />
      </div>
      <div class="dropdown-options">
        <div
          v-for="option in filteredOptions"
          :key="option.value"
          class="dropdown-option"
          :class="{ selected: option.value === modelValue }"
          @click="select(option)"
        >
          <i v-if="option.value === modelValue" class="bi bi-check-lg selected-icon"></i>
          <span>{{ option.label }}</span>
        </div>
        <div v-if="filteredOptions.length === 0" class="dropdown-empty">
          Sin resultados
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SearchableSelect',
  props: {
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Seleccionar...' },
    searchPlaceholder: { type: String, default: 'Buscar...' },
    hasError: { type: Boolean, default: false }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      isOpen: false,
      search: ''
    };
  },
  computed: {
    selectedLabel() {
      const opt = this.options.find(o => o.value === this.modelValue);
      return opt ? opt.label : null;
    },
    filteredOptions() {
      if (!this.search) return this.options;
      const q = this.search.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      return this.options.filter(o => {
        const label = o.label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return label.includes(q);
      });
    }
  },
  watch: {
    isOpen(val) {
      if (val) {
        this.$nextTick(() => {
          if (this.$refs.searchInput) {
            this.$refs.searchInput.focus();
            this.$refs.searchInput.select();
          }
        });
      } else {
        this.search = '';
      }
    }
  },
  mounted() {
    document.addEventListener('click', this.onClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onClickOutside);
  },
  methods: {
    toggle() {
      this.isOpen = !this.isOpen;
    },
    select(option) {
      this.$emit('update:modelValue', option.value);
      this.isOpen = false;
    },
    onClickOutside(e) {
      if (this.$refs.wrapper && !this.$refs.wrapper.contains(e.target)) {
        this.isOpen = false;
      }
    },
    onSearchInput() {
      // no-op, computed handles filtering
    }
  }
};
</script>

<style scoped>
.searchable-select {
  position: relative;
  width: 100%;
}

.searchable-select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 8px 12px;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  cursor: pointer;
  min-height: 40px;
  transition: border-color 0.2s;
  font-size: 0.9rem;
}

.searchable-select-trigger:hover {
  border-color: #2563eb;
}

.searchable-select-trigger.is-open {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

.searchable-select-trigger.is-invalid {
  border-color: #dc3545;
}

.selected-label {
  color: #1e293b;
  font-weight: 500;
}

.placeholder-text {
  color: #94a3b8;
}

.chevron {
  transition: transform 0.2s;
  color: #94a3b8;
  font-size: 0.75rem;
}

.chevron.rotated {
  transform: rotate(180deg);
}

.searchable-select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  z-index: 1050;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  max-height: 300px;
  display: flex;
  flex-direction: column;
}

.dropdown-search {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  gap: 8px;
}

.search-icon {
  color: #94a3b8;
  font-size: 0.85rem;
}

.dropdown-search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.85rem;
  color: #1e293b;
  background: transparent;
}

.dropdown-search-input::placeholder {
  color: #94a3b8;
}

.dropdown-options {
  overflow-y: auto;
  max-height: 200px;
  padding: 4px 0;
}

.dropdown-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 0.85rem;
  color: #1e293b;
  transition: background 0.15s;
}

.dropdown-option:hover {
  background: #f1f5f9;
}

.dropdown-option.selected {
  background: #eff6ff;
  color: #2563eb;
  font-weight: 600;
}

.selected-icon {
  color: #2563eb;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.dropdown-empty {
  padding: 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 0.85rem;
}
</style>
