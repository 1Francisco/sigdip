<template>
  <AppLayout>
    <div class="welcome-header mb-4 text-start d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h2 class="h4 fw-bold mb-1">Rendimiento de Médicos</h2>
        <p class="text-secondary small mb-0">Reportes de actividad y productividad en campo</p>
      </div>
      <div v-if="isOnline && !loading" class="d-flex gap-2">
        <button class="btn btn-sm btn-danger rounded-pill px-3 btn-export-all-pdf" @click="exportAllReport('pdf')">
          <i class="bi bi-file-earmark-pdf me-1"></i> Exportar Todo (PDF)
        </button>
        <button class="btn btn-sm btn-success rounded-pill px-3 btn-export-all-excel" @click="exportAllReport('excel')">
          <i class="bi bi-file-earmark-excel me-1"></i> Exportar Todo (Excel)
        </button>
      </div>
    </div>

    <!-- Alerta Offline -->
    <div v-if="!isOnline" class="card border-0 shadow-sm p-4 rounded-4 mb-4 text-center bg-white border-start border-danger border-4">
      <div class="d-flex flex-column align-items-center gap-3">
        <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
          <i class="bi bi-cloud-slash-fill fs-2"></i>
        </div>
        <div>
          <h5 class="fw-bold mb-1 text-danger">Módulo Offline</h5>
          <p class="text-secondary small mb-0">
            La sección de rendimiento requiere conexión a Internet para consolidar las estadísticas en tiempo real desde el servidor central.
          </p>
        </div>
        <button class="btn btn-outline-danger btn-sm rounded-pill px-4 mt-1" @click="checkConnection">
          <i class="bi bi-arrow-repeat me-1"></i> Reintentar Conexión
        </button>
      </div>
    </div>

    <!-- Contenido Principal (Online) -->
    <div v-else>
      <div v-if="loading" class="text-center py-5 text-muted" data-cy="loading">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <p class="small mb-0">Generando reportes de rendimiento...</p>
      </div>

      <div v-else>
        <!-- KPIs Row -->
        <div class="row g-2 mb-4">
          <div class="col-4 col-md-2">
            <div class="card border-0 shadow-sm p-2 text-center h-100 rounded-3">
              <div class="text-primary fw-bold fs-5">{{ kpis.total_inspecciones }}</div>
              <small class="text-muted text-uppercase" style="font-size: 0.6rem; font-weight: 700;">Inspecciones</small>
            </div>
          </div>
          <div class="col-4 col-md-2">
            <div class="card border-0 shadow-sm p-2 text-center h-100 rounded-3">
              <div class="text-success fw-bold fs-5">{{ kpis.total_visitas }}</div>
              <small class="text-muted text-uppercase" style="font-size: 0.6rem; font-weight: 700;">Visitas</small>
            </div>
          </div>
          <div class="col-4 col-md-2">
            <div class="card border-0 shadow-sm p-2 text-center h-100 rounded-3">
              <div class="text-info fw-bold fs-5">{{ kpis.medicos_activos }}</div>
              <small class="text-muted text-uppercase" style="font-size: 0.6rem; font-weight: 700;">Médicos Act.</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-2 text-center h-100 rounded-3">
              <div class="text-dark fw-bold fs-5">{{ kpis.total_animales }}</div>
              <small class="text-muted text-uppercase" style="font-size: 0.6rem; font-weight: 700;">Animales Probados</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-2 text-center h-100 rounded-3">
              <div class="text-danger fw-bold fs-5">{{ kpis.total_reactores }}</div>
              <small class="text-muted text-uppercase" style="font-size: 0.6rem; font-weight: 700;">Reactores</small>
            </div>
          </div>
        </div>

        <!-- Botón Filtros / Limpiar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <button class="btn btn-sm rounded-pill px-3 shadow-sm btn-filter-toggle" :class="showFilters ? 'btn-primary' : 'btn-outline-primary'" @click="showFilters = !showFilters">
            <i class="bi bi-funnel-fill me-1"></i> {{ showFilters ? 'Ocultar Filtros' : 'Mostrar Filtros' }}
          </button>
          <button v-if="hasActiveFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm btn-clear-filters" @click="clearFilters">
            <i class="bi bi-x-lg me-1"></i> Limpiar Filtros
          </button>
        </div>

        <!-- Filtros Panel -->
        <div v-if="showFilters" class="card border-0 shadow-sm mb-4 bg-light-page p-3 border-slate-100 rounded-4 text-start panel-filters">
          <div class="row g-2">
            <div class="col-6 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Año</label>
              <select v-model="filterParams.year" class="form-select form-select-sm select-year">
                <option value="">Todos</option>
                <option v-for="y in filterOptions.years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Estado</label>
              <select v-model="filterParams.estado" class="form-select form-select-sm select-estado">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="sincronizado">Sincronizado</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Zona</label>
              <select v-model="filterParams.zona" class="form-select form-select-sm select-zona">
                <option value="">Todas</option>
                <option value="A">Zona A</option>
                <option value="B">Zona B</option>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Localidad</label>
              <select v-model="filterParams.localidad" class="form-select form-select-sm select-localidad">
                <option value="">Todas</option>
                <option v-for="l in filterOptions.localidades" :key="l" :value="l">{{ l }}</option>
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Médico</label>
              <select v-model="filterParams.medico_id" class="form-select form-select-sm select-medico">
                <option value="">Todos</option>
                <option v-for="m in filterOptions.medicos" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Fecha Desde</label>
              <input type="date" v-model="filterParams.fecha_desde" class="form-control form-control-sm input-fecha-desde">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Fecha Hasta</label>
              <input type="date" v-model="filterParams.fecha_hasta" class="form-control form-control-sm input-fecha-hasta">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end justify-content-end gap-2 mt-2 mt-md-0">
              <button class="btn btn-sm btn-primary rounded-pill px-4 flex-grow-1 flex-md-grow-0 btn-apply-filters" @click="fetchData">
                <i class="bi bi-funnel"></i> Aplicar Filtros
              </button>
            </div>
          </div>
        </div>

        <!-- Pestañas (Tabs) en modo scroll horizontal -->
        <div class="tab-scroller mb-3">
          <ul class="nav nav-pills nav-pills-premium-mobile flex-nowrap" style="overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
            <li class="nav-item">
              <a class="nav-link tab-medicos" :class="{ active: currentTab === 'medicos' }" @click="currentTab = 'medicos'">
                <i class="bi bi-person-badge"></i> Médicos
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link tab-cuarentena" :class="{ active: currentTab === 'cuarentena' }" @click="currentTab = 'cuarentena'">
                <i class="bi bi-shield-exclamation"></i> Cuarentenas
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link tab-mes" :class="{ active: currentTab === 'mes' }" @click="currentTab = 'mes'">
                <i class="bi bi-calendar-month"></i> Mes
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link tab-mensual" :class="{ active: currentTab === 'mensual' }" @click="currentTab = 'mensual'">
                <i class="bi bi-table"></i> Detalle Mensual
              </a>
            </li>
          </ul>
        </div>

        <!-- Contenido de las pestañas -->
        <div class="tab-content">
          <!-- 1. TABS: MÉDICOS -->
          <div v-if="currentTab === 'medicos'" class="pane-medicos">


            <!-- Tabla responsiva de Médicos -->
             <div class="card border-0 shadow-sm rounded-4 mb-4">
               <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                 <span><i class="bi bi-table text-primary me-2"></i> Detalle de Rendimiento</span>
                 <span class="badge bg-primary-soft text-primary rounded-pill font-mono">{{ medicosRendimiento.length }} médicos</span>
               </div>
               <div class="card-body p-0 text-start">
                 <div v-if="medicosRendimiento.length === 0" class="text-center p-5 text-muted">
                   No hay datos registrados de médicos
                 </div>
                 <div v-else class="table-responsive">
                   <table class="table table-hover align-middle mb-0 table-medicos-rendimiento">
                     <thead class="table-light">
                       <tr>
                         <th class="ps-4">Médico</th>
                         <th class="text-center">Inspecciones</th>
                         <th class="text-center">Visitas</th>
                         <th class="text-center">Completadas</th>
                         <th class="text-center">Predios</th>
                         <th class="text-center">Animales</th>
                         <th class="text-center">Anim/Insp</th>
                         <th class="text-center">% Reactores</th>
                         <th class="text-center">% Finalización</th>
                         <th class="text-center">Eficiencia</th>
                         <th>Última actividad</th>
                         <th class="text-center">Detalle</th>
                       </tr>
                     </thead>
                     <tbody>
                       <tr v-for="m in medicosRendimiento" :key="m.id" class="item-medico-row">
                         <td class="ps-4 fw-semibold row-medico-name">{{ m.name }}</td>
                         <td class="text-center">
                           <span class="badge bg-primary rounded-pill row-medico-inspecciones">{{ m.total_inspecciones }}</span>
                         </td>
                         <td class="text-center">{{ m.total_visitas }}</td>
                         <td class="text-center">
                           <span v-if="m.total_visitas > 0" class="badge bg-success rounded-pill">{{ m.visitas_completadas }}</span>
                           <span v-else class="text-muted">—</span>
                         </td>
                         <td class="text-center">{{ m.predios_atendidos }}</td>
                         <td class="text-center">{{ m.total_animales }}</td>
                         <td class="text-center">{{ m.promedio_animales }}</td>
                         <td class="text-center">
                           <span v-if="m.tasa_reactores > 0" class="badge bg-danger rounded-pill">{{ m.tasa_reactores }}%</span>
                           <span v-else class="text-muted">0%</span>
                         </td>
                         <td class="text-center">
                           <div v-if="m.total_visitas > 0" class="d-flex align-items-center justify-content-center gap-1">
                             <div class="progress" style="height: 6px; width: 60px; background-color: #e2e8f0; border-radius: 3px; overflow: hidden;">
                               <div class="progress-bar bg-success" :style="{ width: m.tasa_finalizacion + '%' }"></div>
                             </div>
                             <small class="font-mono">{{ m.tasa_finalizacion }}%</small>
                           </div>
                           <span v-else class="text-muted">—</span>
                         </td>
                         <td class="text-center">
                           <div class="d-flex align-items-center justify-content-center gap-1">
                             <div class="progress" style="height: 8px; width: 60px; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                               <div class="progress-bar" :class="getScoreColorClass(m.eficiencia_score)" :style="{ width: Math.min(m.eficiencia_score, 100) + '%' }"></div>
                             </div>
                             <small class="fw-bold font-mono" :class="getScoreTextColorClass(m.eficiencia_score)">{{ m.eficiencia_score }}</small>
                           </div>
                         </td>
                         <td>
                           <small v-if="m.ultima_inspeccion" class="text-muted font-mono">{{ formatDate(m.ultima_inspeccion) }}</small>
                           <small v-else class="text-muted fst-italic">Sin actividad</small>
                         </td>
                         <td class="text-center">
                           <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-select-medico" :class="{ active: filterParams.medico_id == m.id }" @click="selectMedicoRow(m.id)">
                             <i class="bi bi-eye"></i>
                           </button>
                         </td>
                       </tr>
                     </tbody>
                   </table>
                 </div>
               </div>
             </div>

             <!-- Detalle por médico seleccionado -->
             <div v-if="filterParams.medico_id && detalleMedico" class="card border-0 shadow-sm rounded-4 mb-4 select-medico-details">
               <div class="card-header bg-white fw-bold py-3 text-start border-bottom">
                 <i class="bi bi-list-ul me-2 text-primary"></i> Últimas inspecciones
                 <span class="text-secondary fw-normal"> — {{ getSelectedMedicoName() }}</span>
               </div>
               <div class="card-body p-0 text-start">
                 <div v-if="detalleMedico.length === 0" class="text-center p-5 text-muted">
                   No hay inspecciones registradas para este médico.
                 </div>
                 <div v-else class="table-responsive">
                   <table class="table table-hover align-middle mb-0 table-medico-inspecciones">
                     <thead class="table-light">
                       <tr>
                         <th>Fecha</th>
                         <th>Folio</th>
                         <th>Productor</th>
                         <th>Predio</th>
                         <th>Tipo Prueba</th>
                         <th>Estado</th>
                       </tr>
                     </thead>
                     <tbody>
                       <tr v-for="ins in detalleMedico" :key="ins.id">
                         <td>{{ formatDate(ins.fecha) }}</td>
                         <td>
                           <span v-if="!ins.folio || ins.folio === ins.clave_interna" class="text-muted fst-italic">{{ ins.clave_interna || '—' }}</span>
                           <span v-else>{{ ins.folio }}</span>
                         </td>
                         <td>{{ ins.productor_nombre }}</td>
                         <td>{{ ins.predio_nombre }}</td>
                         <td>{{ ins.tipo_prueba }}</td>
                         <td>
                           <span class="badge" :class="ins.estado === 'sincronizado' ? 'bg-success text-white' : 'bg-warning text-dark'">
                             {{ capitalizeFirst(ins.estado) }}
                           </span>
                         </td>
                       </tr>
                     </tbody>
                   </table>
                 </div>
               </div>
             </div>
          </div>



          <!-- 4. TABS: CUARENTENA -->
          <div v-if="currentTab === 'cuarentena'" class="pane-cuarentena">
            <div class="row g-4">
              <!-- Columna: Definitivas -->
              <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100 p-0 overflow-hidden">
                  <div class="card-header bg-white fw-bold py-3 px-4 d-flex align-items-center mb-0 border-bottom">
                    <span class="badge bg-danger fs-6 me-2">D</span>
                    <span class="text-dark">Definitivas</span>
                    <span class="ms-auto badge bg-secondary rounded-pill">{{ totalCuarentenasD }}</span>
                  </div>
                  <div class="card-body p-0 text-start">
                    <div v-if="cuarentenasD.length === 0" class="text-center text-muted py-5">
                      <i class="bi bi-shield-exclamation display-5 d-block mb-2"></i>
                      <p class="mb-0 small">Sin cuarentenas definitivas</p>
                    </div>
                    <div v-else>
                      <div v-for="grupo in cuarentenasD" :key="grupo.tipo" class="border-bottom p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                          <span class="badge bg-danger fs-6 px-3 py-1">{{ grupo.tipo }}</span>
                          <span class="fw-bold text-dark">{{ grupo.total }} inspecciones</span>
                          <span class="badge ms-auto" :class="getZonaClass(getZonaLetra(grupo.tipo))">Zona {{ getZonaLetra(grupo.tipo) }}</span>
                        </div>
                        <div class="ps-2">
                          <div v-for="det in grupo.detalle" :key="det.clave_cuarentena" class="d-flex align-items-center gap-2 py-1 small">
                            <span class="text-muted" style="min-width: 100px; font-family: monospace;">{{ det.clave_cuarentena }}</span>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 150px; background-color: #e2e8f0; border-radius: 3px; overflow: hidden;">
                              <div class="progress-bar bg-danger" :style="{ width: getProgressWidth(det.total, grupo.total) + '%' }"></div>
                            </div>
                            <span class="fw-semibold text-dark">{{ det.total }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Columna: Precautorias -->
              <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100 p-0 overflow-hidden">
                  <div class="card-header bg-white fw-bold py-3 px-4 d-flex align-items-center mb-0 border-bottom">
                    <span class="badge bg-warning text-dark fs-6 me-2">P</span>
                    <span class="text-dark">Precautorias</span>
                    <span class="ms-auto badge bg-secondary rounded-pill">{{ totalCuarentenasP }}</span>
                  </div>
                  <div class="card-body p-0 text-start">
                    <div v-if="cuarentenasP.length === 0" class="text-center text-muted py-5">
                      <i class="bi bi-shield-exclamation display-5 d-block mb-2"></i>
                      <p class="mb-0 small">Sin cuarentenas precautorias</p>
                    </div>
                    <div v-else>
                      <div v-for="grupo in cuarentenasP" :key="grupo.tipo" class="border-bottom p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                          <span class="badge bg-warning text-dark fs-6 px-3 py-1">{{ grupo.tipo }}</span>
                          <span class="fw-bold text-dark">{{ grupo.total }} inspecciones</span>
                          <span class="badge ms-auto" :class="getZonaClass(getZonaLetra(grupo.tipo))">Zona {{ getZonaLetra(grupo.tipo) }}</span>
                        </div>
                        <div class="ps-2">
                          <div v-for="det in grupo.detalle" :key="det.clave_cuarentena" class="d-flex align-items-center gap-2 py-1 small">
                            <span class="text-muted" style="min-width: 100px; font-family: monospace;">{{ det.clave_cuarentena }}</span>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 150px; background-color: #e2e8f0; border-radius: 3px; overflow: hidden;">
                              <div class="progress-bar bg-warning" :style="{ width: getProgressWidth(det.total, grupo.total) + '%' }"></div>
                            </div>
                            <span class="fw-semibold text-dark">{{ det.total }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fallback Sin Cuarentena -->
            <div v-if="totalSinCuarentena > 0" class="alert alert-secondary border-0 shadow-sm rounded-4 mt-4 mb-0 d-flex align-items-center gap-3 text-start">
              <i class="bi bi-info-circle-fill fs-5 text-secondary"></i>
              <span><strong>{{ totalSinCuarentena }}</strong> inspecciones sin clave de cuarentena asignada.</span>
            </div>
          </div>

          <!-- 5. TABS: MES -->
          <div v-if="currentTab === 'mes'" class="pane-mes">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
              <div class="card-header bg-white fw-bold py-3 text-start">
                <span><i class="bi bi-graph-up me-2 text-primary"></i> Tendencia Mensual</span>
              </div>
              <div class="card-body p-0 text-start">
                <div v-if="meses.length === 0" class="text-center p-5 text-muted">
                  Sin datos mensuales registrados.
                </div>
                <div v-else class="table-responsive">
                  <table class="table table-hover align-middle mb-0 table-tendencia-mensual">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4">Mes</th>
                        <th class="text-center">Inspecciones</th>
                        <th class="text-center">Año Anterior</th>
                        <th class="text-center">Var. Mensual</th>
                        <th class="pe-4 text-center">Var. YoY</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="mes in processedMeses" :key="mes.mes">
                        <td class="ps-4 fw-semibold text-capitalize">
                          {{ capitalizeFirst(formatMonthName(mes.mes)) }} {{ mes.mes.split('-')[0] }}
                        </td>
                        <td class="text-center">
                          <span class="badge bg-primary rounded-pill fs-6">{{ mes.total }}</span>
                        </td>
                        <td class="text-center">
                          <span v-if="mes.total_anterior > 0" class="badge bg-secondary rounded-pill">{{ mes.total_anterior }}</span>
                          <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-center">
                          <span v-if="mes.varPct !== null" class="badge rounded-pill" :class="mes.varPct >= 0 ? 'bg-success' : 'bg-danger'">
                            <i class="bi" :class="mes.varPct >= 0 ? 'bi-arrow-up' : 'bi-arrow-down'"></i>
                            {{ mes.varPct >= 0 ? '+' : '' }}{{ mes.varPct }}%
                          </span>
                          <span v-else class="text-muted">—</span>
                        </td>
                        <td class="pe-4 text-center">
                          <span v-if="mes.yoy !== null" class="badge rounded-pill" :class="mes.yoy >= 0 ? 'bg-success' : 'bg-danger'">
                            <i class="bi" :class="mes.yoy >= 0 ? 'bi-arrow-up' : 'bi-arrow-down'"></i>
                            {{ mes.yoy >= 0 ? '+' : '' }}{{ mes.yoy }}%
                          </span>
                          <span v-else class="text-muted">—</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- 6. TABS: DETALLE MENSUAL -->
          <div v-if="currentTab === 'mensual'" class="pane-mensual">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
              <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-month text-primary me-2"></i> Detalle Mensual ({{ selectedYear }})</span>
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-danger rounded-pill px-3 btn-export-mensual-pdf" @click="exportMensualReport('pdf')">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
                  </button>
                  <button class="btn btn-sm btn-success rounded-pill px-3 btn-export-mensual-excel" @click="exportMensualReport('excel')">
                    <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
                  </button>
                </div>
              </div>
              <div class="card-body p-0 text-start">
                <div v-if="mensualRows.length === 0" class="text-center p-5 text-muted">
                  No hay registros de rendimiento mensual para el año {{ selectedYear }}
                </div>
                <div v-else class="table-responsive">
                  <table class="table table-hover align-middle mb-0 table-mensual">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4 col-medico">Médico</th>
                        <th class="col-mes">Mes</th>
                        <th class="text-center col-num">Inspecciones</th>
                        <th class="text-center col-num">PPC</th>
                        <th class="text-center col-num">PCC</th>
                        <th class="text-center col-num">Predios</th>
                        <th class="text-center col-num">Visitas</th>
                        <th class="text-center col-num">Animales</th>
                        <th class="text-center col-num">Reactores</th>
                        <th class="text-center col-num">React. PPC</th>
                        <th class="text-center col-num">React. PCC</th>
                        <th class="text-center pe-4 col-download">Descargar</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in mensualRows" :key="row.veterinario_id + '|' + row.mes" class="item-mensual-row">
                        <td class="ps-4 fw-semibold row-medico-nombre">{{ row.medico_nombre }}</td>
                        <td class="row-mes-nombre">{{ capitalizeFirst(formatMonthNameOnly(row.mes)) }}</td>
                        <td class="text-center">
                          <span class="badge bg-primary rounded-pill row-total-inspecciones">{{ row.total_inspecciones }}</span>
                        </td>
                        <td class="text-center">{{ row.ppc }}</td>
                        <td class="text-center">{{ row.pcc }}</td>
                        <td class="text-center">{{ row.predios }}</td>
                        <td class="text-center">{{ row.total_visitas }}</td>
                        <td class="text-center">{{ row.total_animales }}</td>
                        <td class="text-center">
                          <span v-if="row.total_reactores > 0" class="badge bg-danger rounded-pill">{{ row.total_reactores }}</span>
                          <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="row.reactores_ppc > 0" class="badge bg-danger rounded-pill">{{ row.reactores_ppc }}</span>
                          <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="row.reactores_pcc > 0" class="badge bg-danger rounded-pill">{{ row.reactores_pcc }}</span>
                          <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-center pe-4 col-download">
                          <button class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF este mes" @click="downloadRowFile(row, 'pdf')">
                            <i class="bi bi-file-earmark-pdf"></i>
                          </button>
                          <button class="btn btn-sm px-1 py-0 text-success" title="Descargar Excel este mes" @click="downloadRowFile(row, 'excel')">
                            <i class="bi bi-file-earmark-excel"></i>
                          </button>
                        </td>
                      </tr>
                      <!-- Fila de Totales Generales -->
                      <tr class="table-primary fw-bold row-total-general">
                        <td class="ps-4">Total General</td>
                        <td class="text-muted fst-italic">——</td>
                        <td class="text-center">{{ totalInspeccionesMensual }}</td>
                        <td class="text-center">{{ totalPPCMensual }}</td>
                        <td class="text-center">{{ totalPCCMensual }}</td>
                        <td class="text-center">{{ totalPrediosMensual }}</td>
                        <td class="text-center">{{ totalVisitasMensual }}</td>
                        <td class="text-center">{{ totalAnimalesMensual }}</td>
                        <td class="text-center">
                          <span v-if="totalReactoresMensual > 0" class="badge bg-danger rounded-pill">{{ totalReactoresMensual }}</span>
                          <span v-else>0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="totalReactoresPPCMensual > 0" class="badge bg-danger rounded-pill">{{ totalReactoresPPCMensual }}</span>
                          <span v-else>0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="totalReactoresPCCMensual > 0" class="badge bg-danger rounded-pill">{{ totalReactoresPCCMensual }}</span>
                          <span v-else>0</span>
                        </td>
                        <td class="text-center pe-4"></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import ChartCard from '../components/ChartCard.vue';
import api from '../services/api.js';
import { CONFIG } from '../config.js';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import { LocalNotifications } from '@capacitor/local-notifications';

export default {
  name: 'RendimientoView',
  components: {
    AppLayout,
    ChartCard
  },
  data() {
    return {
      loading: true,
      isOnline: true,
      showFilters: false,
      currentTab: 'medicos',
      kpis: {
        total_inspecciones: 0,
        total_visitas: 0,
        medicos_activos: 0,
        total_animales: 0,
        total_reactores: 0
      },
      medicosRendimiento: [],
      actividades: [],
      nombresPruebas: {},
      zonas: [],
      cuarentenasD: [],
      cuarentenasP: [],
      totalSinCuarentena: 0,
      meses: [],
      mensualRows: [],
      selectedYear: null,
      detalleMedico: null,
      filterOptions: {
        years: [],
        localidades: [],
        medicos: []
      },
      filterParams: {
        year: '',
        fecha_desde: '',
        fecha_hasta: '',
        estado: '',
        zona: '',
        localidad: '',
        medico_id: ''
      }
    };
  },
  computed: {
    hasActiveFilters() {
      return Object.values(this.filterParams).some(val => val !== '');
    },
    // Chart Medicos
    chartMedicosLabels() {
      return this.medicosRendimiento.map(m => m.name);
    },
    chartMedicosDatasets() {
      return [
        {
          label: 'Inspecciones',
          data: this.medicosRendimiento.map(m => m.total_inspecciones),
          backgroundColor: '#2563eb',
          borderRadius: 6
        }
      ];
    },
    // Chart Actividades
    hasActividadData() {
      return this.actividades.some(a => a.total > 0);
    },
    chartActividadesLabels() {
      return ['PPC', 'PCC'];
    },
    chartActividadesDatasets() {
      const ppc = this.actividades.find(a => a.tipo === 'PPC')?.total || 0;
      const pcc = this.actividades.find(a => a.tipo === 'PCC')?.total || 0;
      return [
        {
          data: [ppc, pcc],
          backgroundColor: ['#2563eb', '#10b981']
        }
      ];
    },
    // Chart Zonas
    chartZonasLabels() {
      return this.zonas.map(z => 'Zona ' + z.zona);
    },
    chartZonasDatasets() {
      return [
        {
          data: this.zonas.map(z => z.total),
          backgroundColor: ['#2563eb', '#f59e0b', '#ef4444', '#10b981']
        }
      ];
    },
    // Chart Meses (Trend)
    chartMesesLabels() {
      return this.meses.map(m => this.formatMonthName(m.mes));
    },
    chartMesesDatasets() {
      return [
        {
          label: 'Año Seleccionado',
          data: this.meses.map(m => m.total),
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.1)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Año Anterior',
          data: this.meses.map(m => m.total_anterior),
          borderColor: '#94a3b8',
          backgroundColor: 'transparent',
          borderDash: [5, 5],
          tension: 0.3
        }
      ];
    },
    totalInspeccionesMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_inspecciones || 0), 0);
    },
    totalPPCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.ppc || 0), 0);
    },
    totalPCCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.pcc || 0), 0);
    },
    totalPrediosMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.predios || 0), 0);
    },
    totalVisitasMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_visitas || 0), 0);
    },
    totalAnimalesMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_animales || 0), 0);
    },
    totalReactoresMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_reactores || 0), 0);
    },
    totalReactoresPPCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.reactores_ppc || 0), 0);
    },
    totalReactoresPCCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.reactores_pcc || 0), 0);
    },
    totalCuarentenasD() {
      return this.cuarentenasD.reduce((sum, item) => sum + (item.total || 0), 0);
    },
    totalCuarentenasP() {
      return this.cuarentenasP.reduce((sum, item) => sum + (item.total || 0), 0);
    },
    processedMeses() {
      let prevTotal = null;
      return this.meses.map(mes => {
        let varPct = null;
        if (prevTotal !== null && prevTotal > 0) {
          varPct = Math.round(((mes.total - prevTotal) / prevTotal * 100) * 10) / 10;
        }
        prevTotal = mes.total;
        
        let yoy = null;
        if (mes.total_anterior > 0) {
          yoy = Math.round(((mes.total - mes.total_anterior) / mes.total_anterior * 100) * 10) / 10;
        }
        
        return {
          ...mes,
          varPct,
          yoy
        };
      });
    }
  },
  created() {
    this.isOnline = navigator.onLine;
  },
  mounted() {
    if (this.isOnline) {
      this.fetchData();
    }
  },
  methods: {
    checkConnection() {
      this.isOnline = navigator.onLine;
      if (this.isOnline) {
        this.fetchData();
      }
    },
    async fetchData() {
      this.loading = true;
      try {
        const response = await api.getRendimiento(this.filterParams);
        if (response && response.success) {
          this.kpis = response.kpis;
          this.medicosRendimiento = response.medicosRendimiento;
          this.actividades = response.actividades;
          this.nombresPruebas = response.nombresPruebas;
          this.zonas = response.zonas;
          this.cuarentenasD = response.cuarentenasD;
          this.cuarentenasP = response.cuarentenasP;
          this.totalSinCuarentena = response.totalSinCuarentena;
          this.meses = response.meses;
          this.mensualRows = response.mensualRows;
          this.selectedYear = response.selectedYear;
          this.detalleMedico = response.detalleMedico;

          // Cargar filtros solo una vez para evitar sobrescribir selecciones
          if (this.filterOptions.years.length === 0) {
            this.filterOptions.years = response.filters.years;
            this.filterOptions.localidades = response.filters.localidades;
            this.filterOptions.medicos = response.filters.medicos;
          }
        }
      } catch (e) {
        console.error('Error fetching rendimiento data:', e);
      } finally {
        this.loading = false;
      }
    },
    clearFilters() {
      this.filterParams = {
        year: '',
        fecha_desde: '',
        fecha_hasta: '',
        estado: '',
        zona: '',
        localidad: '',
        medico_id: ''
      };
      this.detalleMedico = null;
      this.fetchData();
    },
    formatMonthName(mesStr) {
      if (!mesStr) return '';
      const parts = mesStr.split('-');
      if (parts.length < 2) return mesStr;
      const [year, month] = parts;
      const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
      const mIndex = parseInt(month, 10) - 1;
      return `${months[mIndex]} ${year}`;
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      if (isNaN(date.getTime())) return dateStr;
      return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },
    getScoreColorClass(score) {
      if (score >= 70) return 'bg-success';
      if (score >= 40) return 'bg-warning';
      return 'bg-danger';
    },
    getScoreTextColorClass(score) {
      if (score >= 70) return 'text-success';
      if (score >= 40) return 'text-warning';
      return 'text-danger';
    },
    formatMonthNameOnly(mesStr) {
      if (!mesStr) return '';
      const parts = mesStr.split('-');
      if (parts.length < 2) return mesStr;
      const month = parseInt(parts[1], 10);
      const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
      return months[month - 1];
    },
    capitalizeFirst(str) {
      if (!str) return '';
      return str.charAt(0).toUpperCase() + str.slice(1);
    },
    async downloadBlob(url, filename) {
      try {
        const token = localStorage.getItem('sigdip_token');
        const response = await fetch(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) {
          throw new Error('Fallo en la descarga');
        }
        const blob = await response.blob();

        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = async () => {
            try {
              const base64data = reader.result.split(',')[1];
              // Guardar en Documentos del dispositivo
              const docResult = await Filesystem.writeFile({
                path: filename,
                data: base64data,
                directory: Directory.Documents,
                recursive: true
              });

              // Intentar guardar en carpeta de Descargas (Android)
              let downloadSaved = false;
              try {
                await Filesystem.writeFile({
                  path: filename,
                  data: base64data,
                  directory: Directory.Downloads,
                  recursive: true
                });
                downloadSaved = true;
              } catch (dlErr) {
                console.warn('Could not save to Downloads folder directly:', dlErr);
              }

              // Programar notificación de descarga completa
              try {
                const permission = await LocalNotifications.checkPermissions();
                if (permission.display !== 'granted') {
                  await LocalNotifications.requestPermissions();
                }
                await LocalNotifications.schedule({
                  notifications: [
                    {
                      title: "Descarga Completa",
                      body: `El archivo "${filename}" se descargó exitosamente. Toca para abrirlo.`,
                      id: Math.floor(Math.random() * 1000000),
                      sound: true,
                      extra: {
                        uri: docResult.uri,
                        filename: filename
                      }
                    }
                  ]
                });
              } catch (notiErr) {
                console.warn('Error scheduling local notification:', notiErr);
              }

              // Mostrar alerta amigable indicando ubicación
              if (downloadSaved) {
                alert(`Descarga completada. El archivo "${filename}" se guardó en la carpeta de Descargas de tu teléfono.`);
              } else {
                alert(`Descarga completada. El archivo "${filename}" se guardó en los Documentos de tu teléfono.`);
              }

              // Intentar abrir el menú de compartir/abrir nativo silenciosamente
              try {
                await Share.share({
                  title: filename,
                  url: docResult.uri,
                  dialogTitle: `Abrir ${filename}`
                });
              } catch (shareErr) {
                console.warn('Error al compartir/abrir archivo automáticamente:', shareErr);
              }
            } catch (writeErr) {
              console.error('Error writing file locally:', writeErr);
              alert('Error al guardar el archivo en el teléfono: ' + writeErr.message);
            }
          };
        } else {
          // Fallback para web tradicional (escritorio y navegador móvil)
          const blobUrl = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = blobUrl;
          link.setAttribute('download', filename);
          document.body.appendChild(link);
          link.click();
          link.remove();
          window.URL.revokeObjectURL(blobUrl);
        }
      } catch (e) {
        console.error('Error downloading report:', e);
        alert('Error al descargar el reporte. Verifique la conexión.');
      }
    },
    downloadRowFile(row, type) {
      const parts = row.mes.split('-');
      const firstDay = `${parts[0]}-${parts[1]}-01`;
      const year = parseInt(parts[0], 10);
      const month = parseInt(parts[1], 10);
      const lastDayDate = new Date(year, month, 0);
      const lastDay = `${parts[0]}-${parts[1]}-${String(lastDayDate.getDate()).padStart(2, '0')}`;
      
      const filename = `rendimiento_${row.medico_nombre}_${row.mes}.${type === 'pdf' ? 'pdf' : 'xlsx'}`;
      const url = `${CONFIG.API_BASE_URL}/reportes/rendimiento/${type}?medico_id=${row.veterinario_id}&fecha_desde=${firstDay}&fecha_hasta=${lastDay}`;
      
      this.downloadBlob(url, filename);
    },
    exportMensualReport(type) {
      const year = this.filterParams.year || this.selectedYear;
      const medicoId = this.filterParams.medico_id || '';
      const zona = this.filterParams.zona || '';
      const estado = this.filterParams.estado || '';
      
      const filename = `rendimiento_mensual_${year}.${type === 'pdf' ? 'pdf' : 'xlsx'}`;
      const url = `${CONFIG.API_BASE_URL}/reportes/rendimiento/mensual/${type}?year=${year}&medico_id=${medicoId}&zona=${zona}&estado=${estado}`;
      
      this.downloadBlob(url, filename);
    },
    exportAllReport(type) {
      const queryParams = new URLSearchParams();
      Object.entries(this.filterParams).forEach(([key, val]) => {
        if (val !== undefined && val !== null && val !== '') {
          queryParams.append(key, val);
        }
      });
      const queryStr = queryParams.toString();
      const filename = `rendimiento_consolidado.${type === 'pdf' ? 'pdf' : 'xlsx'}`;
      const url = `${CONFIG.API_BASE_URL}/reportes/rendimiento/${type}${queryStr ? `?${queryStr}` : ''}`;
      
      this.downloadBlob(url, filename);
    },
    getZonaLetra(tipo) {
      if (!tipo) return '';
      return tipo.substring(0, 1);
    },
    getZonaClass(letra) {
      return letra === 'A' ? 'bg-info text-white' : 'bg-warning text-dark';
    },
    getProgressWidth(totalDetalle, totalGrupo) {
      if (!totalGrupo) return 0;
      return Math.round((totalDetalle / totalGrupo) * 100);
    },
    selectMedicoRow(id) {
      if (this.filterParams.medico_id == id) {
        this.filterParams.medico_id = '';
      } else {
        this.filterParams.medico_id = id;
      }
      this.fetchData();
    },
    getSelectedMedicoName() {
      const match = this.filterOptions.medicos.find(m => m.id == this.filterParams.medico_id);
      return match ? match.name : '';
    }
  }
};
</script>

<style scoped>
.tab-scroller {
  margin-left: -1rem;
  margin-right: -1rem;
  padding-left: 1rem;
  padding-right: 1rem;
}
.nav-pills-premium-mobile {
  background: #f1f5f9;
  padding: 4px;
  border-radius: 12px;
  display: flex;
  width: max-content;
}
.nav-pills-premium-mobile .nav-link {
  color: #64748b;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.8rem;
  transition: all 0.2s ease;
}
.nav-pills-premium-mobile .nav-link.active {
  color: #2563eb;
  background: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}
.alert {
  border-radius: 16px;
}
.list-group-item {
  border-color: #f1f5f9;
}
.progress {
  box-shadow: none;
}
.table-responsive {
  display: block;
  width: 100%;
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch;
}
.table {
  width: 100%;
  margin-bottom: 1rem;
  color: #212529;
  vertical-align: top;
  border-collapse: collapse;
}
.table th,
.table td {
  padding: 0.75rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
.table-light {
  background-color: #f8fafc;
  color: #1e293b;
}
.table-primary {
  background-color: #eff6ff !important;
  color: #1e3a8a !important;
}
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}
.table-mensual {
  table-layout: fixed;
  min-width: 1200px; /* Asegura scroll horizontal en pantallas móviles */
}
.table-mensual th,
.table-mensual td { overflow: hidden; text-overflow: ellipsis; font-size: 0.85rem; }
.table-mensual .col-medico { width: 22%; min-width: 170px; }
.table-mensual .col-mes { width: 14%; min-width: 110px; }
.table-mensual .col-num { width: 10%; }
.table-mensual .col-download { width: 12%; min-width: 100px; white-space: nowrap; }
.table-medicos-rendimiento {
  min-width: 1250px;
}
.table-medico-inspecciones {
  min-width: 800px;
}
.table-tendencia-mensual {
  min-width: 800px;
}
</style>
