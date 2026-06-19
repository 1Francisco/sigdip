import { defineStore } from 'pinia';

export const useInspeccionStore = defineStore('inspeccion', {
  state: () => ({
    scannedAnimals: [],
    scannedSingleArete: null,
    scanTargetIndex: null,
    inspeccionDraft: null,
  }),

  actions: {
    setScannedAnimals(animals) {
      this.scannedAnimals = animals;
    },

    addScannedAnimal(animal) {
      this.scannedAnimals.push(animal);
    },

    removeScannedAnimal(index) {
      this.scannedAnimals.splice(index, 1);
    },

    clearScannedAnimals() {
      this.scannedAnimals = [];
    },

    setScannedSingleArete(arete) {
      this.scannedSingleArete = arete;
    },

    clearScannedSingleArete() {
      this.scannedSingleArete = null;
    },

    setScanTargetIndex(index) {
      this.scanTargetIndex = index;
    },

    clearScanTargetIndex() {
      this.scanTargetIndex = null;
    },

    setInspeccionDraft(draft) {
      this.inspeccionDraft = draft;
    },

    clearInspeccionDraft() {
      this.inspeccionDraft = null;
    },

    clearAll() {
      this.scannedAnimals = [];
      this.scannedSingleArete = null;
      this.scanTargetIndex = null;
      this.inspeccionDraft = null;
    },
  },
});
