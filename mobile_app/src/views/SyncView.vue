<template>
  <AppLayout>
      <!-- Welcome Header -->
      <div class="welcome-header d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="welcome-title text-primary fs-3 fw-bold mb-1">Sincronización</h1>
          <p class="welcome-subtitle text-secondary">Gestión de datos offline y catálogos locales</p>
        </div>
      </div>
      <!-- Status de conexión dinámico (Clon de la web) -->
      <div 
        class="card shadow-sm border-0 mb-4 p-4 rounded-4" 
        :style="{ borderLeft: isOnline ? '4px solid var(--color-success)' : '4px solid var(--color-danger)' }"
      >
        <div class="d-flex align-items-center gap-3">
          <div 
            class="rounded-circle d-flex align-items-center justify-content-center"
            style="width: 48px; height: 48px;"
            :class="isOnline ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
          >
            <i class="bi fs-3" :class="isOnline ? 'bi-cloud-check-fill' : 'bi-cloud-slash-fill'"></i>
          </div>
          <div>
            <div class="fw-bold fs-6 text-primary">{{ isOnline ? 'Dispositivo Conectado' : 'Modo Sin Conexión (Offline)' }}</div>
            <div class="text-secondary small">
              {{ isOnline ? 'La sincronización y descarga están habilitadas y listas.' : 'Trabajarás con tus catálogos locales y los datos se encolarán.' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-primary bg-primary-soft">
            <i class="bi bi-house-fill"></i>
          </div>
          <div class="stat-value">{{ prediosCount }}</div>
          <div class="stat-label">Ranchos</div>
        </div>

        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-info bg-info-subtle">
            <i class="bi bi-calendar-event-fill"></i>
          </div>
          <div class="stat-value">{{ visitasCount }}</div>
          <div class="stat-label">Visitas</div>
        </div>

        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-warning bg-warning-subtle">
            <i class="bi bi-cloud-arrow-up-fill"></i>
          </div>
          <div class="stat-value" :class="{ 'text-danger fw-bold': pendientes > 0 }">{{ pendientes }}</div>
          <div class="stat-label">Por subir</div>
        </div>

        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-secondary bg-light">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-value small-value">{{ lastSyncText }}</div>
          <div class="stat-label">Último Sync</div>
        </div>
      </div>

      <!-- Botones de sincronización -->
      <div class="action-sync-buttons mb-4 d-flex flex-column gap-2">
        <button 
          class="btn btn-primary btn-lg w-100 shadow-sm d-flex align-items-center justify-content-center gap-2" 
          @click="downloadData" 
          :disabled="downloading || !isOnline"
        >
          <span v-if="downloading" class="loader"></span>
          <span v-else class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-arrow-down-fill"></i> Descargar Catálogos del Día
          </span>
        </button>
        <div v-if="downloading && progressMsg" class="text-primary small text-center mt-1 fw-semibold">{{ progressMsg }}</div>

        <!-- Botón subir -->
        <button 
          class="btn btn-accent btn-lg w-100 shadow-sm d-flex align-items-center justify-content-center gap-2" 
          @click="uploadData" 
          :disabled="uploading || !isOnline || pendientes === 0"
        >
          <span v-if="uploading" class="loader"></span>
          <span v-else class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-arrow-up-fill"></i> Subir Dictámenes Pendientes ({{ pendientes }})
          </span>
        </button>
      </div>

      <!-- Resultados / Mensajes -->
      <div v-if="resultado" class="card shadow-sm border-0 border-start border-success border-4 p-4 rounded-4 mt-3 bg-white text-start">
        <div class="d-flex align-items-center gap-2 mb-1 text-success fw-bold">
          <i class="bi bi-check-circle-fill"></i> Sincronización Completada
        </div>
        <div class="text-secondary small">{{ resultado }}</div>
      </div>

      <div v-if="errorMsg" class="card shadow-sm border-0 border-start border-danger border-4 p-4 rounded-4 mt-3 bg-white text-start">
        <div class="d-flex align-items-center gap-2 mb-1 text-danger fw-bold">
          <i class="bi bi-exclamation-octagon-fill"></i> Error en Operación
        </div>
        <div class="text-secondary small">{{ errorMsg }}</div>
      </div>

      <!-- Tab System to View Data -->
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mt-4 card-outer-mobile-flat text-start">
        <ul class="nav nav-tabs border-bottom mb-3" style="display: flex; gap: 1rem; padding-bottom: 0.5rem; margin-bottom: 1rem;">
          <li class="nav-item" style="list-style: none;">
            <button 
              class="btn btn-sm btn-link p-0 fw-bold text-decoration-none" 
              :class="activeTab === 'locales' ? 'text-primary' : 'text-secondary'" 
              @click="activeTab = 'locales'"
            >
              Dictámenes Pendientes ({{ localInspecciones.length }})
            </button>
          </li>
          <li class="nav-item" style="list-style: none;">
            <button 
              class="btn btn-sm btn-link p-0 fw-bold text-decoration-none" 
              :class="activeTab === 'visitas' ? 'text-primary' : 'text-secondary'" 
              @click="activeTab = 'visitas'"
            >
              Visitas Pendientes ({{ localVisitas.length }})
            </button>
          </li>

        </ul>

        <!-- Local Pending Inspections List -->
        <div v-if="activeTab === 'locales'">
          <div v-if="localInspecciones.length === 0" class="text-center py-4 text-muted small">
            No hay dictámenes pendientes de sincronizar en este dispositivo.
          </div>
          <div v-else class="list-group list-group-flush">
            <div 
              v-for="insp in localInspecciones" 
              :key="insp.folio" 
              class="list-group-item d-flex align-items-center justify-content-between py-3 border-bottom"
            >
              <div>
                <div class="fw-bold text-dark fs-6">{{ insp.folio }}</div>
                <small class="text-secondary d-block">Fecha: {{ insp.fecha }} · Animales: {{ insp.animales?.length || 0 }}</small>
                <small class="text-secondary d-block" v-if="insp.visita_id">Visita ID: {{ insp.visita_id }}</small>
              </div>
              <div class="d-flex gap-2">
                <button 
                  class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5 px-2.5 py-1.5"
                  @click="compareInspection(insp)"
                  :disabled="comparing || uploading"
                >
                  <span v-if="comparing" class="spinner-border spinner-border-sm me-1" role="status" style="width: 0.85rem; height: 0.85rem;"></span>
                  <i v-else class="bi bi-arrow-left-right"></i> {{ isOnline ? 'Ver Cambios' : 'Ver Detalles' }}
                </button>
                <button 
                  class="btn btn-primary btn-sm d-flex align-items-center gap-1.5 px-3 py-1-5" 
                  style="background: #2563eb;" 
                  @click="uploadSingleInspection(insp)"
                  :disabled="uploading || !isOnline"
                >
                  <i class="bi bi-cloud-arrow-up-fill"></i> Subir
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Local Pending Visits List -->
        <div v-if="activeTab === 'visitas'">
          <div v-if="localVisitas.length === 0" class="text-center py-4 text-muted small">
            No hay visitas pendientes de sincronizar en este dispositivo.
          </div>
          <div v-else class="list-group list-group-flush">
            <div 
              v-for="vis in localVisitas" 
              :key="vis.codigo" 
              class="list-group-item d-flex align-items-center justify-content-between py-3 border-bottom"
            >
              <div>
                <div class="fw-bold text-dark fs-6">{{ vis.codigo }}</div>
                <small class="text-secondary d-block">Fecha: {{ vis.fecha_programada }} · Predio ID: {{ vis.predio_id }}</small>
                <small class="text-secondary d-block" v-if="vis.observaciones">Obs: {{ vis.observaciones }}</small>
              </div>
              <div class="d-flex gap-2">
                <button 
                  class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5 px-2.5 py-1.5"
                  @click="compararVisita(vis)"
                  :disabled="comparingVisita || uploading"
                >
                  <span v-if="comparingVisita" class="spinner-border spinner-border-sm me-1" role="status" style="width: 0.85rem; height: 0.85rem;"></span>
                  <i v-else class="bi bi-arrow-left-right"></i> {{ isOnline ? 'Ver Cambios' : 'Ver Datos' }}
                </button>
                <button 
                  class="btn btn-primary btn-sm d-flex align-items-center gap-1.5 px-3 py-1-5" 
                  style="background: #2563eb;" 
                  @click="uploadSingleVisita(vis)"
                  :disabled="uploading || !isOnline"
                >
                  <i class="bi bi-cloud-arrow-up-fill"></i> Subir
                </button>
              </div>
            </div>
          </div>
        </div>


      </div>

      <!-- Conflict Resolution Modal Backdrop -->
      <div v-if="showConflictModal" class="modal-overlay" @click="resolveConflict('keep_server')"></div>

      <!-- Conflict Resolution Modal -->
      <div v-if="showConflictModal" class="modal-card shadow-lg p-4 rounded-4 bg-white text-start">
        <div class="d-flex align-items-center gap-2 mb-3 text-warning fw-bold fs-5">
          <i class="bi bi-exclamation-triangle-fill fs-4"></i> Conflicto de Sincronización Detectado
        </div>

        <p class="text-secondary small mb-3">
          El dictamen con folio <strong class="text-dark">{{ conflictData.inspeccion.folio }}</strong> ya existe en el servidor, pero tiene una lista de animales diferente.
        </p>

        <!-- Comparison Table -->
        <div class="comparison-container mb-4">
          <div class="comparison-column">
            <div class="column-header text-primary">Local (Dispositivo)</div>
            <div class="column-stat">Total: <strong>{{ conflictData.localAnimals.length }}</strong> animales</div>
            <div class="column-list">
              <div v-for="a in conflictData.localAnimals" :key="a.identificador" class="column-list-item">
                Arete: <strong>{{ a.identificador }}</strong> ({{ a.resultado }})
              </div>
            </div>
          </div>

          <div class="comparison-column">
            <div class="column-header text-success">Servidor (CEFPPENAY)</div>
            <div class="column-stat">Total: <strong>{{ conflictData.serverAnimals.length }}</strong> animales</div>
            <div class="column-list">
              <div v-for="a in conflictData.serverAnimals" :key="a.id" class="column-list-item">
                Arete: <strong>{{ a.animal?.numero_arete_siniiga || a.identificador }}</strong> ({{ a.resultado_prueba || a.resultado }})
              </div>
            </div>
          </div>
        </div>

        <div class="modal-instructions mb-4 text-muted small">
          Elige qué acción deseas tomar. La opción <strong>Combinar</strong> unificará los aretes de ambas listas sin duplicados para no perder ningún registro.
        </div>

        <!-- Buttons -->
        <div class="d-flex flex-column gap-2">
          <button class="btn btn-accent w-100 py-2.5 fw-bold" @click="resolveConflict('merge')">
            <i class="bi bi-shuffle"></i> Combinar y Subir (Fusionar ambos listados)
          </button>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-danger flex-grow-1 py-2 fw-semibold" @click="resolveConflict('overwrite')">
              Sobrescribir Servidor
            </button>
            <button class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold" @click="resolveConflict('keep_server')">
              Conservar Servidor
            </button>
          </div>
        </div>
      </div>

      <!-- Version Control Modal Backdrop -->
      <div v-if="showVersionControlModal" class="modal-overlay" @click="showVersionControlModal = false"></div>

      <!-- Version Control Modal -->
      <div v-if="showVersionControlModal" class="modal-card shadow-lg p-4 rounded-4 bg-white text-start" style="max-width: 750px;">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
          <div class="d-flex align-items-center gap-2 text-primary fw-bold fs-5">
            <i class="bi bi-git fs-4"></i> Control de Versiones: {{ versionControlData.localInsp.folio }}
          </div>
          <button type="button" class="btn-close-scanner" style="color: #64748b;" @click="showVersionControlModal = false">✕</button>
        </div>

        <!-- Offline Warning -->
        <div v-if="!isOnline" class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-3" style="border-radius: 10px; font-size: 0.85rem; background-color: #fffbeb; border: 1px solid #fef3c7; color: #b45309;">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span><strong>Modo Offline:</strong> Mostrando solo datos locales. Conéctate a internet para ver la comparación con el servidor.</span>
        </div>

        <div class="row g-3 mb-4">
          <!-- Local Column info -->
          <div class="col-md-6">
            <div class="p-3 rounded-3 bg-light" style="border: 1.5px solid #e2e8f0; height: 100%;">
              <div class="fw-bold text-primary mb-2 d-flex align-items-center gap-1.5 fs-7-5 text-uppercase">
                <span class="badge bg-primary">Local</span> Datos a subir
              </div>
              <div class="small text-secondary mb-1"><strong>Fecha:</strong> {{ versionControlData.localMeta.fecha }}</div>
              <div class="small text-secondary mb-1"><strong>Tipo de Prueba:</strong> {{ versionControlData.localMeta.tipo_prueba }}</div>
              <div class="small text-secondary mb-1"><strong>Motivo:</strong> {{ versionControlData.localMeta.motivo_prueba || 'No definido' }}</div>
              <div class="small text-secondary mb-1"><strong>Zootécnica:</strong> {{ versionControlData.localMeta.funcion_zootecnica }}</div>
              <div class="mt-2 pt-2 border-top">
                <span class="fw-semibold small text-dark d-block mb-1">Censo Ganadero Local:</span>
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                  <span class="badge font-monospace" style="background-color: #64748b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">Sem: {{ versionControlData.localMeta.sementales }}</span>
                  <span class="badge font-monospace" style="background-color: #64748b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">Vac: {{ versionControlData.localMeta.vacas }}</span>
                  <span class="badge font-monospace" style="background-color: #64748b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">Vaq: {{ versionControlData.localMeta.vaquillas }}</span>
                  <span class="badge font-monospace" style="background-color: #64748b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">BecF: {{ versionControlData.localMeta.becerras }}</span>
                  <span class="badge font-monospace" style="background-color: #64748b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">BecM: {{ versionControlData.localMeta.becerros }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Server Column info (only if online and exists) -->
          <div class="col-md-6">
            <div v-if="versionControlData.serverInsp" class="p-3 rounded-3" style="background: #f0fdf4; border: 1.5px solid #bbf7d0; height: 100%;">
              <div class="fw-bold text-success mb-2 d-flex align-items-center gap-1.5 fs-7-5 text-uppercase">
                <span class="badge bg-success" style="color: #ffffff;">Servidor</span> Versión guardada
              </div>
              <div class="small text-secondary mb-1"><strong>Fecha:</strong> {{ versionControlData.serverMeta.fecha }}</div>
              <div class="small text-secondary mb-1"><strong>Tipo de Prueba:</strong> {{ versionControlData.serverMeta.tipo_prueba }}</div>
              <div class="small text-secondary mb-1"><strong>Motivo:</strong> {{ versionControlData.serverMeta.motivo_prueba || 'No definido' }}</div>
              <div class="small text-secondary mb-1"><strong>Zootécnica:</strong> {{ versionControlData.serverMeta.funcion_zootecnica }}</div>
              <div class="mt-2 pt-2 border-top">
                <span class="fw-semibold small text-dark d-block mb-1">Censo Ganadero en Servidor:</span>
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                  <span class="badge font-monospace" style="background-color: #1e293b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">Sem: {{ versionControlData.serverMeta.sementales }}</span>
                  <span class="badge font-monospace" style="background-color: #1e293b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">Vac: {{ versionControlData.serverMeta.vacas }}</span>
                  <span class="badge font-monospace" style="background-color: #1e293b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">Vaq: {{ versionControlData.serverMeta.vaquillas }}</span>
                  <span class="badge font-monospace" style="background-color: #1e293b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">BecF: {{ versionControlData.serverMeta.becerras }}</span>
                  <span class="badge font-monospace" style="background-color: #1e293b; color: #ffffff; padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; margin-right: 4px; display: inline-block;">BecM: {{ versionControlData.serverMeta.becerros }}</span>
                </div>
              </div>
            </div>
            <div v-else class="p-3 rounded-3 d-flex flex-column align-items-center justify-content-center text-center" style="background: #f8fafc; border: 1.5px dashed #cbd5e1; height: 100%;">
              <i class="bi bi-cloud-plus fs-2 text-muted mb-2"></i>
              <div class="fw-bold text-slate-700 fs-7-5 text-uppercase">Dictamen Nuevo</div>
              <div class="text-secondary small mt-1">Este folio no se encuentra registrado en el servidor. Se cargará como un nuevo registro.</div>
            </div>
          </div>
        </div>

        <!-- Diff / Changes List -->
        <div class="fw-bold text-dark fs-7-5 text-uppercase mb-2">Detalle de Cambios (Aretes)</div>
        
        <div class="version-diff-container mb-4" style="min-height: 140px; max-height: 250px; flex-shrink: 0; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: white; padding: 12px;">
          
          <!-- Case 1: Offline or New inspection (just list all local animals) -->
          <template v-if="!versionControlData.serverInsp">
            <div v-for="a in versionControlData.localInsp.animales" :key="a.identificador" class="d-flex align-items-center justify-content-between py-2 border-bottom last-border-none">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle small-badge" style="font-size: 0.7rem; padding: 3px 6px;">+ NUEVO</span>
                <strong class="text-dark">{{ a.identificador }}</strong>
              </div>
              <div class="small text-secondary">
                Edad: {{ a.edad_meses || '?' }}m · Raza: {{ a.raza || 'S/R' }} · Sexo: {{ a.sexo }} · Result: <strong :class="getResultadoClass(a.resultado)">{{ a.resultado }}</strong>
              </div>
            </div>
          </template>

          <!-- Case 2: Online & Exist (Show exact differences) -->
          <template v-else>
            <div v-if="versionControlData.diff.added.length === 0 && versionControlData.diff.removed.length === 0 && versionControlData.diff.modified.length === 0" class="text-center py-4 text-muted small">
              <i class="bi bi-check-all fs-4 text-success d-block mb-1"></i>
              No hay discrepancias en la lista de animales. Las versiones son idénticas.
            </div>

            <!-- Added Locally -->
            <div v-for="item in versionControlData.diff.added" :key="'add-'+item.arete" class="d-flex align-items-center justify-content-between py-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success small-badge" style="font-size: 0.65rem; padding: 3px 6px;">+ AGREGADO</span>
                <strong class="text-dark">{{ item.arete }}</strong>
              </div>
              <div class="small text-secondary">
                Local: {{ item.local.resultado }} ({{ item.local.edad_meses || '?' }}m, {{ item.local.sexo }})
              </div>
            </div>

            <!-- Removed Locally -->
            <div v-for="item in versionControlData.diff.removed" :key="'rem-'+item.arete" class="d-flex align-items-center justify-content-between py-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger small-badge" style="font-size: 0.65rem; padding: 3px 6px;">- ELIMINADO</span>
                <strong class="text-dark">{{ item.arete }}</strong>
              </div>
              <div class="small text-secondary">
                Servidor: {{ item.server.resultado_prueba || item.server.resultado }}
              </div>
            </div>

            <!-- Modified Locally -->
            <div v-for="item in versionControlData.diff.modified" :key="'mod-'+item.arete" class="d-flex flex-column py-2 border-bottom">
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-warning text-dark small-badge" style="font-size: 0.65rem; padding: 3px 6px;">~ MODIFICADO</span>
                  <strong class="text-dark">{{ item.arete }}</strong>
                </div>
                <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                  Local: {{ item.local.resultado }} · Servidor: {{ item.server.resultado_prueba || item.server.resultado }}
                </div>
              </div>
              <div class="mt-1 ps-4 text-warning small fw-semibold" style="font-size: 0.72rem; line-height: 1.25;">
                <div v-for="det in item.details" :key="det">• {{ det }}</div>
              </div>
            </div>

            <!-- Identical Count Summary -->
            <div v-if="versionControlData.diff.identical.length > 0" class="text-center pt-2 text-muted small" style="font-size: 0.75rem;">
              {{ versionControlData.diff.identical.length }} animales sin cambios (idénticos).
            </div>
          </template>

        </div>

        <!-- Modal Actions -->
        <div class="w-100">
          <!-- Caso 1: El dictamen NO existe en el servidor (dictamen nuevo) -->
          <div v-if="!versionControlData.serverInsp" class="d-flex gap-2 w-100">
            <button class="btn btn-outline-secondary flex-grow-1 py-2.5 fw-semibold" @click="showVersionControlModal = false">
              Cerrar
            </button>
            <button 
              v-if="isOnline"
              class="btn btn-primary flex-grow-1 py-2.5 fw-bold" 
              style="background: #2563eb;" 
              @click="uploadFromVersionControl(versionControlData.localInsp)"
              :disabled="uploading"
            >
              <i class="bi bi-cloud-arrow-up-fill"></i> Subir Dictamen Ahora
            </button>
          </div>

          <!-- Caso 2: El dictamen SI existe en el servidor -->
          <div v-else class="w-100">
            <!-- Caso 2A: Tienen discrepancias (conflicto) -->
            <div v-if="versionControlData.diff.added.length > 0 || versionControlData.diff.removed.length > 0 || versionControlData.diff.modified.length > 0">
              <div class="alert alert-info py-2 px-3 mb-3 small text-secondary" style="border-radius: 10px; background-color: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1;">
                <i class="bi bi-info-circle-fill me-1"></i>
                Se detectaron discrepancias con el servidor. Por favor selecciona cuál versión es la correcta para resolver el conflicto:
              </div>
              
              <div class="d-flex flex-column gap-2">
                <button class="btn btn-accent w-100 py-2.5 fw-bold" @click="resolveConflict('merge')" :disabled="uploading">
                  <i class="bi bi-shuffle"></i> Combinar y Subir (Fusionar ambos listados)
                </button>
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-danger flex-grow-1 py-2 fw-semibold" @click="resolveConflict('overwrite')" :disabled="uploading">
                    Sobrescribir Servidor
                  </button>
                  <button class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold" @click="resolveConflict('keep_server')" :disabled="uploading">
                    Conservar Servidor
                  </button>
                </div>
                <button class="btn btn-link text-secondary text-decoration-none py-1 mt-1 small" @click="showVersionControlModal = false" :disabled="uploading">
                  Cancelar y Cerrar
                </button>
              </div>
            </div>

            <!-- Caso 2B: Son idénticos -->
            <div v-else class="d-flex gap-2 w-100">
              <button class="btn btn-outline-secondary flex-grow-1 py-2.5 fw-semibold" @click="showVersionControlModal = false">
                Cerrar (Dictamen Idéntico)
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Visita Detail Modal -->
      <div v-if="verVisita" class="modal-overlay" @click="verVisita = null"></div>
      <div v-if="verVisita" class="modal-card shadow-lg p-4 rounded-4 bg-white text-start" style="max-width: 520px;">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
          <div class="d-flex align-items-center gap-2 text-primary fw-bold fs-5">
            <i class="bi bi-calendar-event fs-4"></i> Visita: {{ verVisita.codigo }}
          </div>
          <button type="button" class="btn-close-scanner" style="color: #64748b;" @click="verVisita = null">✕</button>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <div class="small text-secondary fw-semibold text-uppercase">Fecha Programada</div>
            <div class="fw-bold text-dark">{{ verVisita.fecha_programada }}</div>
          </div>
          <div class="col-6">
            <div class="small text-secondary fw-semibold text-uppercase">Predio ID</div>
            <div class="fw-bold text-dark">{{ verVisita.predio_id }}</div>
          </div>
          <div class="col-6">
            <div class="small text-secondary fw-semibold text-uppercase">Veterinario ID</div>
            <div class="fw-bold text-dark">{{ verVisita.veterinario_id || '—' }}</div>
          </div>
          <div class="col-6">
            <div class="small text-secondary fw-semibold text-uppercase">Estado</div>
            <span class="badge bg-warning text-dark">Pendiente de subir</span>
          </div>
          <div class="col-12" v-if="verVisita.observaciones">
            <div class="small text-secondary fw-semibold text-uppercase">Observaciones</div>
            <div class="text-dark">{{ verVisita.observaciones }}</div>
          </div>
          <div class="col-12">
            <div class="small text-secondary fw-semibold text-uppercase">Creada Localmente</div>
            <div class="text-dark">{{ verVisita._created_at ? new Date(verVisita._created_at).toLocaleString('es-MX') : '—' }}</div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-4">
          <button class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold" @click="verVisita = null">Cerrar</button>
          <button 
            v-if="isOnline"
            class="btn btn-primary flex-grow-1 py-2 fw-bold" 
            style="background: #2563eb;" 
            @click="uploadSingleVisita(verVisita); verVisita = null"
            :disabled="uploading"
          >
            <i class="bi bi-cloud-arrow-up-fill"></i> Subir Ahora
          </button>
        </div>
      </div>

      <!-- Visita Comparison Modal -->
      <div v-if="showVisitComparisonModal" class="modal-overlay" @click="showVisitComparisonModal = false"></div>
      <div v-if="showVisitComparisonModal && visitComparison" class="modal-card shadow-lg p-4 rounded-4 bg-white text-start" style="max-width: 560px;">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
          <div class="d-flex align-items-center gap-2 text-primary fw-bold fs-5">
            <i class="bi bi-calendar-event fs-4"></i> Visita: {{ visitComparison.local.codigo }}
          </div>
          <button type="button" class="btn-close-scanner" style="color: #64748b;" @click="showVisitComparisonModal = false">✕</button>
        </div>

        <div v-if="!visitComparison.existsOnServer" class="py-3 text-center">
          <div class="mb-3"><i class="bi bi-cloud-plus fs-1 text-primary"></i></div>
          <p class="fw-semibold mb-1">Solo existe localmente</p>
          <p class="small text-muted mb-3">Esta visita no se encuentra en el servidor. Puedes subirla para sincronizarla.</p>
          <button class="btn btn-primary w-100 py-2 fw-bold" style="background: #2563eb;" @click="uploadSingleVisita(visitComparison.local); showVisitComparisonModal = false" :disabled="uploading">
            <i class="bi bi-cloud-arrow-up-fill"></i> Subir Ahora
          </button>
        </div>

        <div v-else>
          <p class="small text-muted mb-3">
            <i class="bi bi-info-circle me-1"></i> Comparando datos locales vs servidor.
          </p>
          <div class="comparison-container mb-3">
            <div class="comparison-column">
              <div class="column-header text-primary">
                <i class="bi bi-phone me-1"></i> Local
              </div>
              <div class="column-stat">{{ visitComparison.localMeta?.updated || 'Desconocido' }}</div>
              <div class="column-list">
                <div v-for="(val, key) in visitComparison.fieldsLocal" :key="'l-'+key" class="column-list-item">
                  <small class="text-secondary text-uppercase fw-semibold">{{ key }}</small>
                  <div class="fw-semibold" :class="visitComparison.diffs[key] ? 'text-warning' : ''">{{ val || '—' }}</div>
                </div>
              </div>
            </div>
            <div class="comparison-column">
              <div class="column-header text-success">
                <i class="bi bi-cloud me-1"></i> Servidor
              </div>
              <div class="column-stat">{{ visitComparison.serverMeta?.updated || 'Sincronizado' }}</div>
              <div class="column-list">
                <div v-for="(val, key) in visitComparison.fieldsServer" :key="'s-'+key" class="column-list-item">
                  <small class="text-secondary text-uppercase fw-semibold">{{ key }}</small>
                  <div class="fw-semibold" :class="visitComparison.diffs[key] ? 'text-warning' : ''">{{ val || '—' }}</div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="visitComparison.hasDiffs" class="alert alert-warning py-2 px-3 small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Hay <strong>{{ Object.keys(visitComparison.diffs).length }}</strong> campo(s) diferente(s). Elige qué versión conservar.
          </div>
          <div v-else class="alert alert-success py-2 px-3 small mb-3">
            <i class="bi bi-check-circle me-1"></i>
            Los datos coinciden con el servidor. Se eliminará la copia local.
          </div>
          <div class="modal-instructions small text-muted mb-3 border rounded p-2 bg-light">
            <strong>Acciones disponibles:</strong>
            <ul class="mb-0 ps-3 mt-1">
              <li><strong>Subir Local</strong> — sobrescribe los datos en el servidor con la versión local.</li>
              <li><strong>Mantener Servidor</strong> — descarta los cambios locales y conserva lo que está en el servidor.</li>
            </ul>
          </div>
          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold" @click="showVisitComparisonModal = false">
              Cancelar
            </button>
            <button
              class="btn btn-success flex-grow-1 py-2 fw-bold"
              @click="resolverVisita('keep_server')"
              :disabled="uploading"
            >
              <i class="bi bi-server me-1"></i> Mantener Servidor
            </button>
            <button
              class="btn btn-primary flex-grow-1 py-2 fw-bold"
              style="background: #2563eb;"
              @click="resolverVisita('overwrite')"
              :disabled="uploading"
            >
              <i class="bi bi-cloud-arrow-up-fill me-1"></i> Subir Local
            </button>
          </div>
        </div>
      </div>

      <!-- Borrar datos locales -->
      <div class="text-center mt-5 mb-4">
        <p class="text-muted small px-3">
          Solo utiliza este botón en caso de problemas técnicos extremos. Al limpiar datos locales se borrarán los ranchos cacheados y borradores locales.
        </p>
        <button class="btn btn-danger w-auto px-4 py-2 mt-1 shadow-sm" @click="clearLocalData">
          <i class="bi bi-trash-fill me-1"></i> Limpiar Caché Local
        </button>
      </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'SyncView',
  components: { AppLayout },
  data() {
    return {
      isAdmin: false,
      isOnline: navigator.onLine,
      prediosCount: 0,
      visitasCount: 0,
      pendientes: 0,
      lastSyncText: 'Nunca',
      downloading: false,
      progressMsg: '',
      uploading: false,
      resultado: '',
      errorMsg: '',
      localInspecciones: [],
      localVisitas: [],
      localProductoresPendientes: [],
      localPrediosPendientes: [],
      activeTab: 'locales',
      verVisita: null,
      visitComparison: null,
      showVisitComparisonModal: false,
      comparingVisita: false,

      showConflictModal: false,
      conflictData: {
        inspeccion: {},
        serverInspId: null,
        localAnimals: [],
        serverAnimals: [],
        serverAretes: [],
        localAretes: []
      },
      conflictResolver: null,
      comparing: false,
      showVersionControlModal: false,
      versionControlData: {
        localInsp: {},
        serverInsp: null,
        diff: { added: [], removed: [], modified: [], identical: [] },
        localMeta: {},
        serverMeta: {}
      }
    };
  },
  async mounted() {
    this._onWindowOnline = () => this.isOnline = true;
    this._onWindowOffline = () => this.isOnline = false;
    window.addEventListener('online', this._onWindowOnline);
    window.addEventListener('offline', this._onWindowOffline);

    // Escuchar el evento de sincronización de fondo para actualizar las estadísticas en tiempo real
    this._syncListener = async (e) => {
      await this.refreshStats();
      this.resultado = `Sincronizados ${e.detail.procesados} dictámenes automáticamente en segundo plano.`;
    };
    window.addEventListener('sigdip-sync-complete', this._syncListener);

    const user = api.getCurrentUser();
    this.isAdmin = user?.roles && user.roles.includes('Administrador');

    await this.refreshStats();

    // Autocomparar folio si viene de redirección por conflicto
    const checkFolio = this.$route.query.check_folio;
    if (checkFolio) {
      const targetInsp = this.localInspecciones.find(i => i.folio === checkFolio);
      if (targetInsp) {
        this.compareInspection(targetInsp);
      }
    }
  },
  unmounted() {
    if (this._syncListener) {
      window.removeEventListener('sigdip-sync-complete', this._syncListener);
    }
    if (this._onWindowOnline) window.removeEventListener('online', this._onWindowOnline);
    if (this._onWindowOffline) window.removeEventListener('offline', this._onWindowOffline);
  },
  methods: {
    async refreshStats() {
      const predios = await db.getPredios();
      this.prediosCount = predios.length;
      const visitas = await db.getVisitas();
      this.visitasCount = visitas.length;
      this.localInspecciones = await db.getInspeccionesPendientes();
      this.localVisitas = await db.getVisitasPendientes();
      this.localProductoresPendientes = await db.getProductoresPendientes();
      this.localPrediosPendientes = await db.getPrediosPendientes();
      this.pendientes = this.localInspecciones.length + this.localVisitas.length
        + this.localProductoresPendientes.length + this.localPrediosPendientes.length;
      const sync = await db.getLastSync();
      if (sync) {
        this.lastSyncText = new Date(sync).toLocaleDateString('es-MX', {
          day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
      }
    },

    async downloadData() {
      this.downloading = true;
      this.progressMsg = 'Iniciando descarga...';
      this.resultado = '';
      this.errorMsg = '';
      try {
        const lastSync = await db.getLastSync();
        const isIncremental = !!lastSync;

        if (isIncremental) {
          this.progressMsg = 'Buscando cambios desde la última sincronización...';
        }

        let allPredios = [];
        let allProductores = [];
        let allVisitas = [];
        let medicos = [];
        let page = 1;

        while (true) {
          const params = { page, per_page: 500 };
          if (isIncremental) {
            params.since = lastSync;
            delete params.per_page; // incremental no necesita paginación
            delete params.page;
          }
          this.progressMsg = isIncremental
            ? 'Sincronizando cambios...'
            : `Descargando página ${page}...`;

          const res = await api.downloadCatalogos(params);

          allPredios.push(...(res.data.predios || []));
          allProductores.push(...(res.data.productores || []));
          allVisitas.push(...(res.data.visitas || []));
          if (page === 1 || !lastSync) {
            medicos = res.data.medicos || [];
          }

          const pag = res.pagination;
          if (!pag) break;

          const prediosDone = !pag.predios || page >= pag.predios.last_page;
          const productoresDone = !pag.productores || page >= pag.productores.last_page;
          const visitasDone = !pag.visitas || page >= pag.visitas.last_page;

          if (prediosDone && productoresDone && visitasDone) break;
          page++;
        }

        if (isIncremental) {
          await db.upsertPredios(allPredios);
          await db.upsertProductores(allProductores);
          await db.upsertVisitas(allVisitas);
          if (medicos.length > 0) {
            await db.saveMedicos(medicos);
          }
        } else {
          await db.savePredios(allPredios);
          await db.saveProductores(allProductores);
          await db.saveMedicos(medicos);
          await db.saveVisitas(allVisitas);
        }

        await db.setLastSync();

        if (isIncremental) {
          const partes = [];
          if (allPredios.length > 0) partes.push(`${allPredios.length} ranchos`);
          if (allProductores.length > 0) partes.push(`${allProductores.length} productores`);
          if (allVisitas.length > 0) partes.push(`${allVisitas.length} visitas`);
          if (partes.length === 0) {
            this.resultado = 'Sin cambios nuevos. Todos tus datos locales están actualizados.';
          } else {
            this.resultado = `Cambios sincronizados: ${partes.join(', ')}.`;
          }
        } else {
          this.resultado = `Descargados ${allPredios.length} ranchos, ${allProductores.length} productores, ${medicos.length} médicos y ${allVisitas.length} visitas.`;
        }
        await this.refreshStats();
      } catch (err) {
        this.errorMsg = err.message || 'No se pudo establecer conexión con el servidor de CEFPPENAY.';
      } finally {
        this.downloading = false;
        this.progressMsg = '';
      }
    },

    async uploadData() {
      this.uploading = true;
      this.resultado = '';
      this.errorMsg = '';
      try {
        const inspecciones = await db.getInspeccionesPendientes();
        const visitas = await db.getVisitasPendientes();
        const productoresPend = await db.getProductoresPendientes();
        const prediosPend = await db.getPrediosPendientes();
        let totalSincronizados = 0;
        let totalErrores = 0;

        // 1. Subir productores offline (necesitan subirse primero para tener IDs reales)
        if (productoresPend.length > 0) {
          try {
            const res = await api.uploadProductores(productoresPend);
            if (res?.success && res.productores) {
              for (const mapping of res.productores) {
                await db.remapProductorId(mapping.offline_id, mapping.new_id);
                totalSincronizados++;
              }
            }
          } catch (err) {
            console.error('Error al subir productores offline:', err);
            totalErrores += productoresPend.length;
          }
        }

        // 2. Subir predios offline (dependen de IDs reales de productores)
        if (prediosPend.length > 0) {
          try {
            const res = await api.uploadPredios(prediosPend);
            if (res?.success && res.predios) {
              for (const mapping of res.predios) {
                await db.remapPredioId(mapping.offline_id, mapping.new_id);
                totalSincronizados++;
              }
            }
          } catch (err) {
            console.error('Error al subir predios offline:', err);
            totalErrores += prediosPend.length;
          }
        }
        
        for (const insp of inspecciones) {
          try {
            const syncRes = await this.syncInspection(insp);
            if (syncRes && syncRes.status === 'success') {
              totalSincronizados++;
            }
          } catch (err) {
            console.error(`Error al sincronizar dictamen ${insp.folio}:`, err);
            totalErrores++;
          }
        }

        for (const vis of visitas) {
          try {
            const res = await api.uploadVisitas([vis]);
            if (res.procesados && res.procesados.length) {
              await db.removeVisitaPendiente(vis.codigo);
              totalSincronizados++;
            } else {
              const errText = res.errores?.[0]?.error || 'Error al subir visita.';
              console.error(`Error al subir visita ${vis.codigo}:`, errText);
              totalErrores++;
            }
          } catch (err) {
            console.error(`Error al subir visita ${vis.codigo}:`, err);
            totalErrores++;
          }
        }
        
        await this.refreshStats();
        
        if (totalSincronizados > 0) {
          this.resultado = `Sincronizados ${totalSincronizados} elementos exitosamente.`;
        }
        if (totalErrores > 0) {
          this.errorMsg = `Hubo errores al sincronizar ${totalErrores} elementos.`;
        }
      } catch (err) {
        this.errorMsg = err.message || 'Error al conectar con la base de datos central.';
      } finally {
        this.uploading = false;
      }
    },

    async uploadSingleInspection(inspeccion) {
      this.uploading = true;
      this.resultado = '';
      this.errorMsg = '';
      try {
        const syncRes = await this.syncInspection(inspeccion);
        if (syncRes && syncRes.status === 'success') {
          this.resultado = `Dictamen ${inspeccion.folio} sincronizado con éxito.`;
          await this.refreshStats();
        }
      } catch (err) {
        this.errorMsg = `Error al subir dictamen ${inspeccion.folio}: ${err.message}`;
      } finally {
        this.uploading = false;
      }
    },

    async uploadSingleVisita(visita) {
      this.uploading = true;
      this.resultado = '';
      this.errorMsg = '';
      try {
        const res = await api.uploadVisitas([visita]);
        if (res.procesados && res.procesados.length) {
          await db.removeVisitaPendiente(visita.codigo);
          this.resultado = `Visita ${visita.codigo} sincronizada con éxito.`;
          await this.refreshStats();
        } else {
          const errText = res.errores?.[0]?.error || 'Error al subir visita.';
          this.errorMsg = `Error al subir visita ${visita.codigo}: ${errText}`;
        }
      } catch (err) {
        this.errorMsg = `Error al subir visita ${visita.codigo}: ${err.message}`;
      } finally {
        this.uploading = false;
      }
    },

    async syncInspection(inspeccion) {
      try {
        let serverInsp = null;
        try {
          const checkRes = await api.getInspecciones({ folio: inspeccion.folio });
          if (checkRes.success && checkRes.data && checkRes.data.length > 0) {
            serverInsp = checkRes.data[0];
          }
        } catch (err) {
          console.warn("No se pudo pre-verificar existencia en servidor:", err);
        }
        
        if (serverInsp) {
          // Ya existe en el servidor. Descargar detalles completos para comparar animales
          const detailRes = await api.getInspeccion(serverInsp.id);
          const serverDetail = detailRes.data || {};
          const serverAnimals = serverDetail.detalles || [];
          const localAnimals = inspeccion.animales || [];
          
          const serverAretes = serverAnimals.map(a => (a.animal?.numero_arete_siniiga || '').trim().toUpperCase()).filter(Boolean);
          const localAretes = localAnimals.map(a => (a.identificador || '').trim().toUpperCase()).filter(Boolean);
          
          const areIdentical = serverAretes.length === localAretes.length && 
                               serverAretes.every(val => localAretes.includes(val)) &&
                               localAretes.every(val => serverAretes.includes(val));
                               
          if (!areIdentical) {
            // Abrir modal de conflicto
            this.conflictData = {
              inspeccion: inspeccion,
              serverInspId: serverInsp.id,
              localAnimals: localAnimals,
              serverAnimals: serverAnimals,
              serverAretes: serverAretes,
              localAretes: localAretes
            };
            this.showConflictModal = true;
            
            // Pausar y esperar resolución del usuario
            return new Promise((resolve) => {
              this.conflictResolver = resolve;
            });
          } else {
            // Idéntico, solo limpiar local
            await db.clearInspeccionesSincronizadas([inspeccion.folio]);
            return { status: 'success', message: 'Ya sincronizado.' };
          }
        } else {
          // No existe, subir directo
          const res = await api.uploadInspecciones([inspeccion]);
          if (res.procesados && res.procesados.length) {
            await db.clearInspeccionesSincronizadas([inspeccion.folio]);
            return { status: 'success' };
          } else {
            const errText = res.errores?.[0]?.error || 'Error al subir dictamen.';
            throw new Error(errText);
          }
        }
      } catch (e) {
        throw e;
      }
    },

    async resolveConflict(option) {
      this.showConflictModal = false;
      this.showVersionControlModal = false;
      const { inspeccion, localAnimals, serverAnimals } = this.conflictData;
      const resolve = this.conflictResolver;
      
      this.uploading = true;
      this.resultado = '';
      this.errorMsg = '';
      
      try {
        if (option === 'keep_server') {
          // Descartar local
          await db.clearInspeccionesSincronizadas([inspeccion.folio]);
          if (resolve) {
            resolve({ status: 'success', action: 'keep_server' });
          } else {
            this.resultado = `Dictamen ${inspeccion.folio} resuelto conservando la versión del servidor.`;
            await this.refreshStats();
          }
        } else if (option === 'overwrite') {
          // Sobrescribir
          const res = await api.uploadInspecciones([inspeccion]);
          if (res.procesados && res.procesados.length) {
            await db.clearInspeccionesSincronizadas([inspeccion.folio]);
            if (resolve) {
              resolve({ status: 'success', action: 'overwrite' });
            } else {
              this.resultado = `Dictamen ${inspeccion.folio} sobrescrito en el servidor con éxito.`;
              await this.refreshStats();
            }
          } else {
            throw new Error(res.errores?.[0]?.error || 'Error de validación al sobrescribir.');
          }
        } else if (option === 'merge') {
          // Combinar
          const mergedAnimals = [];
          const addedAretes = new Set();
          
          localAnimals.forEach(la => {
            const arete = (la.identificador || '').trim().toUpperCase();
            if (arete) {
              mergedAnimals.push({ ...la });
              addedAretes.add(arete);
            }
          });
          
          serverAnimals.forEach(sa => {
            const arete = (sa.animal?.numero_arete_siniiga || sa.identificador || '').trim().toUpperCase();
            if (arete && !addedAretes.has(arete)) {
              mergedAnimals.push({
                identificador: arete,
                tipo_arete: sa.tipo_arete || 'SINIIGA',
                edad_meses: sa.edad_meses ?? sa.animal?.edad ?? null,
                raza: sa.raza || sa.animal?.raza || '',
                sexo: (sa.sexo === 'Hembra' || sa.sexo === 'H') ? 'H' : 'M',
                fierro: sa.fierro || 'Si',
                resultado: sa.resultado_prueba || 'Negativo',
                observaciones: sa.observaciones_animal || '',
                en_base_datos: true
              });
              addedAretes.add(arete);
            }
          });
          
          // Recalcular censo
          let sementales = 0; let vacas = 0; let vaquillas = 0; let becerras = 0; let becerros = 0;
          mergedAnimals.forEach(a => {
            const edad = parseInt(a.edad_meses) || 0;
            const sexo = (a.sexo || '').toLowerCase();
            if (sexo === 'macho' || sexo === 'm') {
              if (edad >= 12) sementales++; else becerros++;
            } else {
              if (edad < 12) becerras++; else if (edad >= 12 && edad < 24) vaquillas++; else vacas++;
            }
          });
          
          const mergedInspection = {
            ...inspeccion,
            animales: mergedAnimals,
            sementales,
            vacas,
            vaquillas,
            becerras,
            becerros
          };
          
          const res = await api.uploadInspecciones([mergedInspection]);
          if (res.procesados && res.procesados.length) {
            await db.clearInspeccionesSincronizadas([inspeccion.folio]);
            if (resolve) {
              resolve({ status: 'success', action: 'merge' });
            } else {
              this.resultado = `Dictamen ${inspeccion.folio} fusionado y sincronizado con éxito.`;
              await this.refreshStats();
            }
          } else {
            throw new Error(res.errores?.[0]?.error || 'Error de validación al fusionar.');
          }
        }
      } catch (err) {
        alert(`Error al resolver conflicto: ${err.message}`);
        if (resolve) {
          resolve(Promise.reject(err));
        } else {
          this.errorMsg = `Error al resolver conflicto: ${err.message}`;
        }
      } finally {
        this.uploading = false;
      }
    },

    async clearLocalData() {
      if (confirm('ATENCIÓN: ¿Seguro que deseas borrar todos los datos locales?\nLos dictámenes creados en offline que NO estén sincronizados se perderán permanentemente.')) {
        await db.clearAll();
        await this.refreshStats();
        this.resultado = 'Caché y catálogos locales eliminados con éxito.';
      }
    },
    async compareInspection(insp) {
      this.comparing = true;
      this.errorMsg = '';
      this.resultado = '';
      
      try {
        const localAnimals = insp.animales || [];
        const localMeta = {
          fecha: insp.fecha,
          tipo_prueba: insp.tipo_prueba,
          motivo_prueba: insp.motivo_prueba,
          funcion_zootecnica: insp.funcion_zootecnica,
          sementales: insp.sementales || 0,
          vacas: insp.vacas || 0,
          vaquillas: insp.vaquillas || 0,
          becerras: insp.becerras || 0,
          becerros: insp.becerros || 0
        };

        let serverInsp = null;
        let serverAnimals = [];
        let serverMeta = {};
        let diff = { added: [], removed: [], modified: [], identical: [] };

        if (this.isOnline) {
          const checkRes = await api.getInspecciones({ folio: insp.folio });
          if (checkRes.success && checkRes.data && checkRes.data.length > 0) {
            const basicServer = checkRes.data[0];
            const detailRes = await api.getInspeccion(basicServer.id);
            serverInsp = detailRes.data || {};
            serverAnimals = serverInsp.detalles || [];
            serverMeta = {
              fecha: serverInsp.fecha,
              tipo_prueba: serverInsp.tipo_prueba,
              motivo_prueba: serverInsp.motivo_prueba,
              funcion_zootecnica: serverInsp.funcion_zootecnica,
              sementales: serverInsp.sementales || 0,
              vacas: serverInsp.vacas || 0,
              vaquillas: serverInsp.vaquillas || 0,
              becerras: serverInsp.becerras || 0,
              becerros: serverInsp.becerros || 0
            };

            // Diffing
            const localMap = new Map(localAnimals.map(a => [(a.identificador || '').trim().toUpperCase(), a]));
            const serverMap = new Map(serverAnimals.map(sa => [
              (sa.animal?.numero_arete_siniiga || sa.identificador || '').trim().toUpperCase(), 
              sa
            ]));

            // Local additions and edits
            localMap.forEach((localAnimal, arete) => {
              if (!serverMap.has(arete)) {
                diff.added.push({
                  arete,
                  local: localAnimal
                });
              } else {
                const serverAnimal = serverMap.get(arete);
                const serverRes = serverAnimal.resultado_prueba || serverAnimal.resultado;
                const isDifferent = localAnimal.resultado !== serverRes ||
                                    parseInt(localAnimal.edad_meses) !== parseInt(serverAnimal.edad_meses ?? serverAnimal.animal?.edad) ||
                                    (localAnimal.sexo || '').toUpperCase().charAt(0) !== (serverAnimal.sexo || '').toUpperCase().charAt(0);
                
                const diffItem = {
                  arete,
                  local: localAnimal,
                  server: serverAnimal,
                  details: []
                };

                if (localAnimal.resultado !== serverRes) {
                  diffItem.details.push(`Resultado: Local '${localAnimal.resultado}' vs Servidor '${serverRes || 'Pendiente'}'`);
                }
                if (parseInt(localAnimal.edad_meses) !== parseInt(serverAnimal.edad_meses ?? serverAnimal.animal?.edad)) {
                  diffItem.details.push(`Edad: Local ${localAnimal.edad_meses}m vs Servidor ${serverAnimal.edad_meses ?? serverAnimal.animal?.edad}m`);
                }
                const lSex = (localAnimal.sexo || 'H').toUpperCase().charAt(0);
                const sSex = (serverAnimal.sexo || 'H').toUpperCase().charAt(0);
                if (lSex !== sSex) {
                  diffItem.details.push(`Sexo: Local '${lSex}' vs Servidor '${sSex}'`);
                }

                if (isDifferent) {
                  diff.modified.push(diffItem);
                } else {
                  diff.identical.push({ arete, local: localAnimal, server: serverAnimal });
                }
              }
            });

            // Server-only items (removed locally)
            serverMap.forEach((serverAnimal, arete) => {
              if (!localMap.has(arete)) {
                diff.removed.push({
                  arete,
                  server: serverAnimal
                });
              }
            });
          }
        }

        if (serverInsp) {
          const serverAretes = serverAnimals.map(sa => (sa.animal?.numero_arete_siniiga || sa.identificador || '').trim().toUpperCase()).filter(Boolean);
          const localAretes = localAnimals.map(a => (a.identificador || '').trim().toUpperCase()).filter(Boolean);
          this.conflictData = {
            inspeccion: insp,
            serverInspId: serverInsp.id,
            localAnimals: localAnimals,
            serverAnimals: serverAnimals,
            serverAretes: serverAretes,
            localAretes: localAretes
          };
          this.conflictResolver = null;
        }

        this.versionControlData = {
          localInsp: insp,
          serverInsp,
          diff,
          localMeta,
          serverMeta
        };
        this.showVersionControlModal = true;
      } catch (err) {
        this.errorMsg = `Error al comparar versiones: ${err.message}`;
      } finally {
        this.comparing = false;
      }
    },
    async uploadFromVersionControl(inspeccion) {
      this.showVersionControlModal = false;
      await this.uploadSingleInspection(inspeccion);
    },
    getResultadoClass(res) {
      if (res === 'Negativo') return 'text-success';
      if (res === 'Positivo') return 'text-danger';
      if (res === 'Sospechoso') return 'text-warning';
      return 'text-secondary';
    },
    async compararVisita(visita) {
      if (!this.isOnline) {
        this.verVisita = visita;
        return;
      }
      this.comparingVisita = true;
      try {
        const res = await api.getVisitaByCodigo(visita.codigo);
        if (!res.exists) {
          this.visitComparison = { local: visita, existsOnServer: false, fieldsLocal: {}, fieldsServer: {}, diffs: {}, hasDiffs: false };
        } else {
          const server = res.data;
          const fieldsLocal = {
            'Código': visita.codigo,
            'Fecha Programada': visita.fecha_programada,
            'Predio ID': visita.predio_id?.toString(),
            'Veterinario ID': visita.veterinario_id?.toString() || '—',
            'Observaciones': visita.observaciones || '—',
            'Estado': visita.estado || 'pendiente',
          };
          const fieldsServer = {
            'Código': server.codigo,
            'Fecha Programada': server.fecha_programada,
            'Predio ID': server.predio_id?.toString(),
            'Veterinario ID': server.veterinario_id?.toString() || '—',
            'Observaciones': server.observaciones || '—',
            'Estado': server.estado,
          };
          const fieldKeys = Object.keys(fieldsLocal);
          const diffs = {};
          fieldKeys.forEach(k => {
            const lv = (fieldsLocal[k] || '').toString().trim();
            const sv = (fieldsServer[k] || '').toString().trim();
            if (lv !== sv) diffs[k] = { local: fieldsLocal[k], server: fieldsServer[k] };
          });
          this.visitComparison = {
            local: visita,
            server,
            existsOnServer: true,
            fieldsLocal,
            fieldsServer,
            diffs,
            hasDiffs: Object.keys(diffs).length > 0,
            localMeta: { updated: visita._created_at ? new Date(visita._created_at).toLocaleString('es-MX') : 'Desconocido' },
            serverMeta: { updated: server.id ? `ID: ${server.id}` : 'Sincronizado' },
          };
        }
        this.showVisitComparisonModal = true;
      } catch (err) {
        this.errorMsg = `Error al comparar visita: ${err.message}`;
      } finally {
        this.comparingVisita = false;
      }
    },
    async resolverVisita(option) {
      this.showVisitComparisonModal = false;
      const visita = this.visitComparison.local;
      try {
        if (option === 'keep_server') {
          await db.removeVisitaPendiente(visita.codigo);
          this.resultado = `Visita ${visita.codigo}: se mantuvo la versión del servidor.`;
        } else if (option === 'overwrite') {
          const res = await api.uploadVisitas([visita]);
          if (res.procesados && res.procesados.length) {
            await db.removeVisitaPendiente(visita.codigo);
            this.resultado = `Visita ${visita.codigo} sobrescrita y sincronizada.`;
          } else {
            const errText = res.errores?.[0]?.error || 'Error de validación.';
            this.errorMsg = `Error al sobrescribir visita ${visita.codigo}: ${errText}`;
          }
        }
        await this.refreshStats();
      } catch (err) {
        this.errorMsg = `Error al resolver visita: ${err.message}`;
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

@media (min-width: 992px) {
  .mobile-header {
    display: none !important;
  }

  .main-content {
    margin-left: 270px;
    padding: 2.5rem !important;
    min-height: 100dvh;
  }

  .welcome-header {
    padding: 0;
    margin-bottom: 2rem;
  }
}

/* Status Conexión colors */
.bg-success-subtle {
  background-color: #d1fae5 !important;
}

.bg-danger-subtle {
  background-color: #fee2e2 !important;
}

.bg-info-subtle {
  background-color: #e0f2fe !important;
}

.text-info {
  color: var(--color-info) !important;
}

/* Stat Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  border: 1px solid #f1f5f9;
}

.stat-icon-wrapper {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  margin-bottom: 12px;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}

.stat-value.small-value {
  font-size: 0.8rem;
  font-weight: 700;
  word-break: break-all;
  height: 34px;
  display: flex;
  align-items: center;
  text-align: left;
}

.stat-label {
  font-size: 0.68rem;
  color: var(--text-secondary);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  margin-top: 4px;
}

.action-sync-buttons .btn {
  padding: 15px;
  font-size: 0.95rem;
  border-radius: 12px;
  font-weight: 600;
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

/* Modal overlay and card styling */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1050;
  transition: opacity 0.3s ease;
}

.modal-card {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  max-width: 650px;
  max-height: 90vh;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: var(--shadow-xl);
  border-radius: var(--radius-lg);
  z-index: 1060;
  padding: 1.75rem !important;
  display: flex;
  flex-direction: column;
  animation: modal-enter 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modal-enter {
  from {
    opacity: 0;
    transform: translate(-50%, -45%) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

/* Comparison columns */
.comparison-container {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (min-width: 576px) {
  .comparison-container {
    grid-template-columns: 1fr 1fr;
  }
}

.comparison-column {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: var(--radius-md);
  padding: 16px;
  display: flex;
  flex-direction: column;
}

.column-header {
  font-size: 0.9rem;
  font-weight: 700;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.column-stat {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 12px;
}

.column-list {
  max-height: 160px;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: var(--radius-sm);
  padding: 8px;
  font-size: 0.8rem;
}

.column-list-item {
  padding: 6px 8px;
  border-bottom: 1px solid #f1f5f9;
  color: var(--text-primary);
}

.column-list-item:last-child {
  border-bottom: none;
}

.modal-instructions {
  line-height: 1.4;
}

/* Version diff styles */
.version-diff-container::-webkit-scrollbar {
  width: 5px;
}
.version-diff-container::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 4px;
}
.version-diff-container::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.last-border-none:last-child {
  border-bottom: none !important;
}
.bg-primary-soft {
  background-color: rgba(37, 99, 235, 0.1) !important;
}
.small-badge {
  font-size: 0.68rem !important;
  font-weight: 700 !important;
}
</style>
