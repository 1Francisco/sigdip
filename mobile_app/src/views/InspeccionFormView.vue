<template>
  <AppLayout>
      <div class="container-form-wrapper">
        <!-- Title Block -->
        <div class="welcome-header mb-4 text-start">
          <h2 class="h4 fw-bold mb-1 text-dark">Registro de Dictamen</h2>
          <p class="text-secondary small mb-0">Complete todos los campos del formato oficial</p>
        </div>

        <!-- Accordion Form Structure -->
        <div class="accordion-container">
          
          <!-- SECCIÓN I: PROPIETARIO -->
          <div class="accordion-item shadow-sm mb-3">
            <button class="accordion-header-btn" @click="toggleSection(1)" :class="{ collapsed: activeSection !== 1 }">
              <div class="d-flex align-items-center gap-2 flex-grow-1">
                <div class="accordion-icon-box text-primary">
                  <i class="bi bi-person-vcard-fill fs-5"></i>
                </div>
                <span class="header-title">I: PROPIETARIO</span>
              </div>
              
              <!-- Completion Badge Pill -->
              <div v-if="!isSection1Complete" class="accordion-badge-pill danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Faltan Datos</span>
              </div>
              <div v-else class="accordion-badge-pill success">
                <i class="bi bi-check-circle-fill"></i>
                <span>Listo</span>
              </div>

              <i class="bi bi-chevron-down arrow-icon" :class="{ rotate: activeSection === 1 }"></i>
            </button>
            
            <div class="accordion-body-content" :class="{ show: activeSection === 1 }">
              <div class="form-group mb-3">
                <label class="form-label fw-bold text-dark fs-7-5 text-uppercase">Productor (Persona)</label>
                <select v-model="form.predio_id" class="form-select form-control" @change="onPredioSelect" :disabled="!!$route.params.predioId || !!$route.query.inspeccion_id || !!$route.query.visita_id" required>
                  <option value="">Seleccione un predio...</option>
                  <option v-for="p in predios" :key="p.id" :value="p.id">
                    {{ p.productor?.nombre }} {{ p.productor?.apellido_paterno }} ({{ p.nombre }})
                  </option>
                </select>
              </div>
              
              <div class="row g-3">
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">APELLIDO PATERNO</label>
                  <input type="text" :value="selectedProductor?.apellido_paterno || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">APELLIDO MATERNO</label>
                  <input type="text" :value="selectedProductor?.apellido_materno || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">NOMBRE(S)</label>
                  <input type="text" :value="selectedProductor?.nombre || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">TELÉFONO</label>
                  <input type="text" :value="selectedProductor?.telefono || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">DOMICILIO</label>
                  <input type="text" :value="selectedProductor?.domicilio || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">MUNICIPIO</label>
                  <input type="text" :value="selectedProductor?.municipio || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">LOCALIDAD</label>
                  <input type="text" :value="selectedProductor?.localidad || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">ESTADO</label>
                  <input type="text" :value="selectedProductor?.estado || 'NAYARIT'" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">CORREO ELECTRÓNICO</label>
                  <input type="text" :value="selectedProductor?.email || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-semibold">Fecha Dictamen</label>
                  <input type="date" v-model="form.fecha" class="form-control bg-light" disabled style="pointer-events: none;" tabindex="-1" required>
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Folio Dictamen</label>
                  <input type="text" v-model="userFolio" class="form-control fw-bold text-primary" placeholder="Opcional (Ej. TB-2026-1234)">
                </div>
              </div>
            </div>
          </div>

          <!-- SECCIÓN II: UNIDAD DE PRODUCCIÓN -->
          <div class="accordion-item shadow-sm mb-3">
            <button class="accordion-header-btn" @click="toggleSection(2)" :class="{ collapsed: activeSection !== 2 }">
              <div class="d-flex align-items-center gap-2 flex-grow-1">
                <div class="accordion-icon-box text-info">
                  <i class="bi bi-geo-alt-fill fs-5"></i>
                </div>
                <span class="header-title">II: UNIDAD DE PRODUCCIÓN</span>
              </div>

              <!-- Completion Badge Pill -->
              <div v-if="!isSection2Complete" class="accordion-badge-pill danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Faltan Datos</span>
              </div>
              <div v-else class="accordion-badge-pill success">
                <i class="bi bi-check-circle-fill"></i>
                <span>Listo</span>
              </div>

              <i class="bi bi-chevron-down arrow-icon" :class="{ rotate: activeSection === 2 }"></i>
            </button>
            
            <div class="accordion-body-content" :class="{ show: activeSection === 2 }">
              <div class="row g-3">
                <div class="col-md-6 col-sm-12">
                  <label class="form-label small text-muted">NOMBRE DE LA UNIDAD O PREDIO</label>
                  <input type="text" :value="selectedPredio?.nombre_rancho || selectedPredio?.nombre || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-6 col-sm-12">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small text-muted mb-0">COORDENADAS GPS</label>
                    <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none fw-bold gps-btn" @click="obtenerCoordenadasGPS">
                      <i class="bi bi-geo-alt-fill"></i> Obtener GPS Nativo
                    </button>
                  </div>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted small px-2">Lat</span>
                    <input type="text" v-model="form.latitud" class="form-control bg-light" placeholder="No definida" readonly style="pointer-events: none;" tabindex="-1">
                    <span class="input-group-text bg-light text-muted small px-2">Lon</span>
                    <input type="text" v-model="form.longitud" class="form-control bg-light" placeholder="No definida" readonly style="pointer-events: none;" tabindex="-1">
                  </div>
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label small text-muted">CLAVE UPP / PSG</label>
                  <input type="text" :value="selectedPredio?.clave_unidad_produccion || selectedPredio?.upp || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label small text-muted">DOMICILIO</label>
                  <input type="text" :value="selectedPredio?.domicilio || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">MUNICIPIO</label>
                  <input type="text" :value="selectedPredio?.municipio || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">LOCALIDAD/POBLACIÓN</label>
                  <input type="text" :value="selectedPredio?.localidad || ''" class="form-control bg-light" disabled tabindex="-1">
                </div>
                <div class="col-md-4 col-sm-12">
                  <label class="form-label small text-muted">ESTADO</label>
                  <input type="text" :value="selectedPredio?.estado || 'NAYARIT'" class="form-control bg-light" disabled tabindex="-1">
                </div>
              </div>
            </div>
          </div>

          <!-- SECCIÓN III: DE LA PRUEBA Y CENSO -->
          <div class="accordion-item shadow-sm mb-3">
            <button class="accordion-header-btn" @click="toggleSection(3)" :class="{ collapsed: activeSection !== 3 }">
              <div class="d-flex align-items-center gap-2 flex-grow-1">
                <!-- No icon for Section 3 as per mockup -->
                <span class="header-title">III: DATOS DE LA PRUEBA</span>
              </div>

              <!-- Completion Badge Pill -->
              <div v-if="!isSection3Complete" class="accordion-badge-pill danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Faltan Datos</span>
              </div>
              <div v-else class="accordion-badge-pill success">
                <i class="bi bi-check-circle-fill"></i>
                <span>Listo</span>
              </div>

              <i class="bi bi-chevron-down arrow-icon" :class="{ rotate: activeSection === 3 }"></i>
            </button>
            
            <div class="accordion-body-content" :class="{ show: activeSection === 3 }">
              <div class="row g-3">
                <!-- Datos de la prueba -->
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark fs-7-5 text-uppercase">TIPO DE PRUEBA REALIZADA</label>
                  <select v-model="form.tipo_prueba" class="form-select form-control" @change="onTipoPruebaChange">
                    <option value="PPC">Prueba de Pliegue Caudal (PPC)</option>
                    <option value="PCC">Prueba Cervical Comparativa (PCC)</option>
                  </select>
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Motivo de la Prueba</label>
                  <select v-model="form.motivo_prueba" class="form-select form-control" required>
                    <option value="">Seleccione un motivo...</option>
                    <option value="Seguimiento">Seguimiento</option>
                    <option value="Movilización">Movilización</option>
                    <option value="Barrido">Barrido</option>
                    <option value="Buffer">Buffer</option>
                  </select>
                </div>
                
                <!-- Inyección Combined Row -->
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold text-teal d-flex align-items-center gap-1.5" style="color: #0d9488;">
                    <i class="bi bi-pin-angle-fill"></i> Inyección
                  </label>
                  <div class="combined-datetime-input">
                    <div class="date-part">
                      <input type="date" v-model="form.fecha_inyeccion" class="datetime-input-field" @change="calcularFechaLectura" required>
                      <i class="bi bi-calendar datetime-icon"></i>
                    </div>
                    <div class="time-part">
                      <input type="time" v-model="form.hora_inyeccion" class="datetime-input-field" required>
                      <i class="bi bi-clock datetime-icon"></i>
                    </div>
                  </div>
                </div>

                <!-- Lectura Combined Row -->
                <div class="col-12 col-md-6">
                  <label class="form-label fw-bold text-danger d-flex align-items-center gap-1.5" style="color: #ef4444;">
                    <i class="bi bi-eye-fill"></i> Lectura
                  </label>
                  <div class="combined-datetime-input">
                    <div class="date-part">
                      <input type="date" v-model="form.fecha_lectura" class="datetime-input-field" readonly style="pointer-events: none;" tabindex="-1" required>
                      <i class="bi bi-calendar datetime-icon"></i>
                    </div>
                    <div class="time-part">
                      <input type="time" v-model="form.hora_lectura" class="datetime-input-field" required>
                      <i class="bi bi-clock datetime-icon"></i>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Función Zootécnica</label>
                  <select v-model="form.funcion_zootecnica" class="form-select form-control">
                    <option value="Carne">Carne</option>
                    <option value="Leche">Leche</option>
                    <option value="Mixto">Mixto</option>
                  </select>
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Fecha Expiración (Vigencia)</label>
                  <div class="date-input-wrapper">
                    <input type="date" v-model="form.vigencia_fecha" class="form-control form-control-date">
                    <i class="bi bi-calendar date-icon"></i>
                  </div>
                </div>

                <!-- Campos Avanzados para PCC (Siempre visibles, deshabilitados si es PPC) -->
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Fecha Prueba Anterior</label>
                  <div class="date-input-wrapper">
                    <input type="date" v-model="form.fecha_prueba_anterior" class="form-control form-control-date" :disabled="form.tipo_prueba === 'PPC'">
                    <i class="bi bi-calendar date-icon" :class="{ 'text-muted': form.tipo_prueba === 'PPC' }"></i>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Dictamen Anterior No.</label>
                  <input type="text" v-model="form.dictamen_anterior_no" class="form-control" :disabled="form.tipo_prueba === 'PPC'">
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Exención No.</label>
                  <input type="text" v-model="form.exencion_no" class="form-control" :disabled="form.tipo_prueba === 'PPC'">
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Fecha Exención</label>
                  <div class="date-input-wrapper">
                    <input type="date" v-model="form.exencion_fecha" class="form-control form-control-date" :disabled="form.tipo_prueba === 'PPC'">
                    <i class="bi bi-calendar date-icon" :class="{ 'text-muted': form.tipo_prueba === 'PPC' }"></i>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Constancia Hato Libre No.</label>
                  <input type="text" v-model="form.hato_libre_no" class="form-control" :disabled="form.tipo_prueba === 'PPC'">
                </div>
                <div class="col-md-6 col-sm-12">
                  <label class="form-label fw-bold text-dark">Fecha Hato Libre</label>
                  <div class="date-input-wrapper">
                    <input type="date" v-model="form.hato_libre_fecha" class="form-control form-control-date" :disabled="form.tipo_prueba === 'PPC'">
                    <i class="bi bi-calendar date-icon" :class="{ 'text-muted': form.tipo_prueba === 'PPC' }"></i>
                  </div>
                </div>

                <!-- Censo Ganadero -->
                <div class="col-12 mt-4">
                  <div class="fw-bold border-bottom pb-1 text-uppercase text-dark mb-3" style="font-size: 0.85rem; letter-spacing: 0.5px;">CENSO GANADERO (Población Actual)</div>
                </div>
                <div class="col-12">
                  <div class="row g-3">
                    <div class="col-6">
                      <label class="form-label text-dark mb-1">Sementales</label>
                      <input type="number" :value="form.sementales" class="form-control bg-light text-center" readonly tabindex="-1">
                    </div>
                    <div class="col-6">
                      <label class="form-label text-dark mb-1">Vacas</label>
                      <input type="number" :value="form.vacas" class="form-control bg-light text-center" readonly tabindex="-1">
                    </div>
                  </div>
                  <div class="row g-3 mt-1">
                    <div class="col-4">
                      <label class="form-label text-dark mb-1">Vaquillas</label>
                      <input type="number" :value="form.vaquillas" class="form-control bg-light text-center" readonly tabindex="-1">
                    </div>
                    <div class="col-4">
                      <label class="form-label text-dark mb-1">Becerras</label>
                      <input type="number" :value="form.becerras" class="form-control bg-light text-center" readonly tabindex="-1">
                    </div>
                    <div class="col-4">
                      <label class="form-label text-dark mb-1">Becerros</label>
                      <input type="number" :value="form.becerros" class="form-control bg-light text-center" readonly tabindex="-1">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- SECCIÓN IV: RESULTADOS INDIVIDUALES -->
          <div class="accordion-item shadow-sm mb-3">
            <button class="accordion-header-btn" @click="toggleSection(4)" :class="{ collapsed: activeSection !== 4 }">
              <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap">
                <div class="accordion-icon-box text-warning d-none d-sm-flex">
                  <i class="bi bi-list-task fs-5"></i>
                </div>
                <span class="header-title" style="color: #2563eb; font-weight: 800;">IV: RESULTADOS INDIVIDUALES</span>
                
                <!-- Red pill "por definir" -->
                <span v-if="sinDefinirResultadoCount > 0" class="badge-por-definir ms-1">
                  {{ sinDefinirResultadoCount }} por definir
                </span>
                
                <!-- Green pill "Completo" -->
                <span v-if="isSection4Complete" class="badge-completo-outline ms-1">
                  <i class="bi bi-check-circle-fill text-success"></i> Completo
                </span>
              </div>

              <i class="bi bi-chevron-down arrow-icon" :class="{ rotate: activeSection === 4 }"></i>
            </button>
            
            <div class="accordion-body-content p-0" :class="{ show: activeSection === 4 }">
              
              <!-- Warning Banner for Blocked Results -->
              <div v-if="!puedoEditarResultados() && form.fecha_lectura" class="results-blocked-banner p-3 m-3 d-flex align-items-start gap-2.5">
                <div class="banner-icon-box">
                  <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                </div>
                <div class="banner-text-box text-start">
                  <div class="fw-bold text-dark fs-7-5">⚠️ Sección de resultados bloqueada</div>
                  <div class="text-secondary small mt-0.5" style="line-height: 1.35;">La captura de resultados de la prueba se habilitará el día de la lectura: <strong class="text-dark">{{ form.fecha_lectura.split('-').reverse().join('/') }}</strong>.</div>
                </div>
              </div>

              <div class="indicators-row-container p-3 border-bottom bg-light">
                <!-- # Total -->
                <span class="custom-indicator-badge badge-blue">
                  <i class="bi bi-hash"></i> Total: {{ totalRows }}
                </span>
                <!-- Con Arete -->
                <span class="custom-indicator-badge badge-green">
                  <i class="bi bi-check-circle-fill"></i> Con Arete: {{ conAreteCount }}
                </span>
                <!-- SA sin definir -->
                <span class="custom-indicator-badge badge-yellow">
                  <i class="bi bi-exclamation-triangle-fill"></i> SA sin definir: {{ saSinDefinirCount }}
                </span>
                <!-- SA definidos -->
                <span class="custom-indicator-badge badge-cyan">
                  <i class="bi bi-tag-fill"></i> SA definidos: {{ saDefinidosCount }}
                </span>
                <!-- Sin definir resultado -->
                <span class="custom-indicator-badge badge-red">
                  <i class="bi bi-slash-circle-fill"></i> Sin definir resultado: {{ sinDefinirResultadoCount }}
                </span>
                
                <!-- Añadir Animal -->
                <button type="button" class="custom-indicator-btn btn-add-animal" @click="addEmptyAnimal">
                  <i class="bi bi-plus-lg"></i> Añadir Animal
                </button>
              </div>

              <!-- Buscador de aretes -->
              <div v-if="form.animales.length > 0" class="search-animals-container p-3 border-bottom bg-white">
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                  <input 
                    v-model="_searchInput" 
                    type="text" 
                    class="form-control border-start-0" 
                    placeholder="Buscar arete por número (escribe para filtrar)..."
                    @input="onSearchInput"
                  />
                  <button v-if="_searchInput" class="btn btn-outline-secondary py-0 px-2.5" type="button" @click="clearAnimalSearch">✕</button>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="form.animales.length === 0" class="empty-state bg-white p-4 text-center">
                <div class="empty-icon text-muted mb-2"><i class="bi bi-tag fs-2"></i></div>
                <p class="mb-0 fw-semibold text-secondary">No hay animales registrados</p>
                <p class="text-muted small mb-3">Agrega de forma manual o escaneando los códigos QR/SINIIGA.</p>
                <button class="btn btn-accent w-auto px-4 py-2" @click="addEmptyAnimal">
                  <i class="bi bi-camera"></i> Escanear Arete
                </button>
              </div>

              <!-- Tabla responsiva (Desktop) -->
              <div v-else class="table-responsive d-none d-lg-block scrollable-animals-container">
                <!-- Modo Lectura: Arete, Resultado y Observaciones Primero (Compacto) -->
                <table v-if="puedoEditarResultados()" class="table table-bordered align-middle mb-0">
                  <thead class="bg-light text-center small fw-bold">
                    <tr>
                      <th style="width: 200px;">Identificación (Arete)</th>
                      <th style="width: 160px;">Resultado</th>
                      <th>Observaciones</th>
                      <th style="width: 40px;"></th>
                    </tr>
                  </thead>
                    <tbody>
                    <tr v-for="(animal, localIdx) in animalesLectura" :key="animal" :class="{ 'positivo-row': animal.resultado === 'Positivo', 'lectura-row-agregado': animal.agregado_en_lectura }">
                      <td>
                        <div class="input-group">
                          <input type="text" v-model="animal.identificador" class="form-control form-control-sm text-uppercase" placeholder="SINIIGA o SA" required :readonly="!animal.agregado_en_lectura" :style="{ background: animal.agregado_en_lectura ? '#fff' : '#f1f5f9' }" @change="onIdentificadorChange(animal)" />
                          <button class="btn btn-primary py-0 px-2" type="button" @click="scanSingleAnimal(animal)" title="Escanear" disabled>
                            <i class="bi bi-camera"></i>
                          </button>
                          <span v-if="animal.agregado_en_lectura" class="badge bg-warning text-dark ms-1 align-self-center" style="font-size: 0.6rem; white-space: nowrap;" title="Este animal fue agregado el día de la lectura, no estaba presente en la inyección.">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo en lectura
                          </span>
                        </div>
                      </td>
                      <td>
                        <select v-model="animal.resultado" class="form-select form-control-sm fw-bold" :class="getResultadoClass(animal.resultado)">
                          <option value="Pendiente" class="text-secondary">Pendiente</option>
                          <option value="No Aplica" class="text-secondary">No Aplica</option>
                          <option value="Negativo" class="text-success">Negativo</option>
                          <option value="Positivo" class="text-danger">Positivo</option>
                          <option value="Sospechoso" class="text-warning">Sospechoso</option>
                        </select>
                      </td>
                      <td>
                        <input type="text" v-model="animal.observaciones" class="form-control form-control-sm" placeholder="Detalles..." />
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-link text-danger p-0" @click="removeAnimal(animal)" title="Eliminar">
                          <i class="bi bi-trash fs-5"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Desktop: animales sin inyección -->
                <div v-if="puedoEditarResultados() && animalesSinInyeccion.length > 0" class="mt-2 border rounded p-2 bg-light">
                  <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                    <i class="bi bi-info-circle"></i>
                    <strong>{{ animalesSinInyeccion.length }}</strong>
                    {{ animalesSinInyeccion.length === 1 ? 'animal no recibi\u00f3 inyecci\u00f3n' : 'animales no recibieron inyecci\u00f3n' }}
                    (menores de 6 meses)
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <span v-for="animal in animalesSinInyeccion" :key="animal" class="badge bg-secondary text-white small px-2 py-1">
                      <i class="bi bi-slash-circle me-1"></i>
                      #{{ getOriginalAnimalIndex(animal) + 1 }}
                      {{ animal.identificador || 'SIN ARETE' }}
                      ({{ animal.edad_meses || '?' }} meses)
                    </span>
                  </div>
                </div>

                <!-- Modo Inyección: Formulario Completo -->
                <table v-else class="table table-bordered align-middle mb-0">
                  <thead class="bg-light text-center small fw-bold">
                    <tr>
                      <th style="width: 180px;">Identificación (Arete)</th>
                      <th v-if="mostrarColumnaTipoArete" style="width: 110px;">Tipo Arete</th>
                      <th style="width: 100px;">Edad (m)</th>
                      <th style="width: 120px;">Raza</th>
                      <th style="width: 80px;">Sexo</th>
                      <th style="width: 70px;">Fierro</th>
                      <th style="width: 100px;">Estado</th>
                      <th>Observaciones</th>
                      <th style="width: 40px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(animal, localIdx) in filteredAnimales" :key="animal" :class="{ 'positivo-row': animal.resultado === 'Positivo' }">
                      <td>
                        <div class="input-group">
                          <input type="text" v-model="animal.identificador" class="form-control form-control-sm text-uppercase" placeholder="SINIIGA o SA" required @change="onIdentificadorChange(animal)" />
                          <button class="btn btn-primary py-0 px-2" type="button" @click="scanSingleAnimal(animal)" title="Escanear">
                            <i class="bi bi-camera"></i>
                          </button>
                        </div>
                      </td>
                      <td v-if="mostrarColumnaTipoArete">
                        <select v-if="animal.identificador && animal.identificador.trim() && (isSA(animal.identificador) || animal.en_base_datos === false)" v-model="animal.tipo_arete" class="form-select form-control-sm text-center" style="min-width: 90px;" :disabled="!animal.identificador || !animal.identificador.trim()" required>
                          <option value="IN">IN</option>
                          <option value="RA">RA</option>
                        </select>
                        <span v-else></span>
                      </td>
                      <td>
                        <input type="number" v-model.number="animal.edad_meses" class="form-control form-control-sm" placeholder="Meses" min="0" @input="onEdadChange(animal)" />
                      </td>
                      <td>
                        <input type="text" v-model="animal.raza" class="form-control form-control-sm" placeholder="Raza" />
                      </td>
                      <td>
                        <select v-model="animal.sexo" class="form-select form-control-sm text-center">
                          <option value="H">H</option>
                          <option value="M">M</option>
                        </select>
                      </td>
                      <td class="text-center">
                        <input type="checkbox" v-model="animal.fierro" class="form-check-input" true-value="Si" false-value="No" />
                      </td>
                      <td>
                        <span v-if="animal.resultado === 'No Aplica'" class="badge bg-secondary text-white fw-normal px-2 py-1" style="font-size: 0.75rem;">
                          <i class="bi bi-slash-circle me-1"></i> No Aplica
                        </span>
                        <span v-else class="badge bg-light text-secondary fw-normal px-2 py-1" style="font-size: 0.75rem;">
                          <i class="bi bi-hourglass-split me-1"></i> Pendiente
                        </span>
                      </td>
                      <td>
                        <input type="text" v-model="animal.observaciones" class="form-control form-control-sm" placeholder="Detalles..." />
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-link text-danger p-0" @click="removeAnimal(animal)" title="Eliminar">
                          <i class="bi bi-trash fs-5"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Vista Móvil para Lectura: Tabla estilo profesional para médicos -->
              <div v-if="puedoEditarResultados() && form.animales.length > 0" class="d-block d-lg-none scrollable-animals-container-mobile">
                <!-- Encabezado fijo de la tabla -->
                <div class="lectura-table-header">
                  <i class="bi bi-clipboard-check-fill me-1"></i> LECTURA DE RESULTADOS · {{ animalesLectura.length }} animales
                  <i v-if="animalesSinInyeccion.length > 0" class="bi bi-info-circle text-warning ms-2 small"></i><span v-if="animalesSinInyeccion.length > 0" class="text-warning small ms-1">{{ animalesSinInyeccion.length }} sin inyecci&oacute;n</span>
                </div>

                <!-- Filas de animales -->
                <div 
                  v-for="(animal, localIdx) in animalesLectura" 
                  :key="animal" 
                  class="lectura-table-row"
                   :class="{ 
                    'lectura-row-positivo': animal.resultado === 'Positivo',
                    'lectura-row-sospechoso': animal.resultado === 'Sospechoso',
                    'lectura-row-negativo': animal.resultado === 'Negativo',
                    'lectura-row-agregado': animal.agregado_en_lectura,
                    'lectura-row-zebra': localIdx % 2 === 1
                  }"
                >
                  <!-- Línea 1: Número + Arete + Cámara -->
                  <div class="lectura-line1">
                    <span class="lectura-num-badge">{{ getOriginalAnimalIndex(animal) + 1 }}</span>
                    <span v-if="animal.agregado_en_lectura" class="badge bg-warning text-dark flex-shrink-0" style="font-size: 0.65rem; padding: 2px 6px; line-height: 1;" title="Agregado el día de la lectura">+</span>
                    <input 
                      type="text" 
                      v-model="animal.identificador" 
                      class="form-control form-control-sm text-uppercase lectura-input-arete flex-grow-1" 
                      placeholder="SINIIGA o SA" 
                      required 
                      :readonly="!animal.agregado_en_lectura"
                      :style="{ background: animal.agregado_en_lectura ? '#fff' : '#f1f5f9' }"
                      @change="onIdentificadorChange(animal)" 
                    />
                    <button class="btn btn-primary btn-sm lectura-btn-scan" type="button" @click="scanSingleAnimal(animal)" title="Escanear" disabled>
                      <i class="bi bi-camera-fill"></i>
                    </button>
                  </div>

                  <!-- Línea 2: Resultado + Observaciones + Eliminar -->
                  <div class="lectura-line2">
                    <select v-model="animal.resultado" class="form-select form-select-sm fw-bold lectura-select-result" :class="getResultadoClass(animal.resultado)">
                      <option value="Pendiente">Pendiente</option>
                      <option value="No Aplica">No Aplica</option>
                      <option value="Negativo">Negativo</option>
                      <option value="Positivo">Positivo</option>
                      <option value="Sospechoso">Sospechoso</option>
                    </select>
                    <input type="text" v-model="animal.observaciones" class="form-control form-control-sm lectura-input-obs flex-grow-1" placeholder="Observaciones..." />
                    <button type="button" class="btn btn-outline-danger btn-sm lectura-btn-delete" @click="removeAnimal(animal)" title="Eliminar">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>

                <!-- Footer con conteo de resultados -->
                <div class="lectura-table-footer">
                  <span><i class="bi bi-check-circle-fill text-success me-1"></i>Neg: {{ form.animales.filter(a => a.resultado === 'Negativo').length }}</span>
                  <span><i class="bi bi-plus-circle-fill text-danger me-1"></i>Pos: {{ form.animales.filter(a => a.resultado === 'Positivo').length }}</span>
                  <span><i class="bi bi-question-circle-fill text-warning me-1"></i>Sosp: {{ form.animales.filter(a => a.resultado === 'Sospechoso').length }}</span>
                  <span><i class="bi bi-dash-circle text-secondary me-1"></i>Pend: {{ form.animales.filter(a => !a.resultado || a.resultado === 'Pendiente').length }}</span>
                </div>

                <!-- Animales sin inyección (colapsable móvil) -->
                <div v-if="animalesSinInyeccion.length > 0" class="mt-2">
                  <div class="lectura-table-header" style="background: #92400e; cursor: pointer; justify-content: space-between;" @click="showSinInyeccion = !showSinInyeccion">
                    <span><i class="bi bi-exclamation-triangle-fill me-1"></i>SIN INYECCIÓN · {{ animalesSinInyeccion.length }} {{ animalesSinInyeccion.length === 1 ? 'animal' : 'animales' }}</span>
                    <i class="bi" :class="showSinInyeccion ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                  </div>
                  <div v-if="showSinInyeccion">
                    <div v-for="(animal, localIdx) in animalesSinInyeccion" :key="animal" class="lectura-table-row" :class="{ 'lectura-row-zebra': localIdx % 2 === 1 }">
                      <div class="lectura-line1">
                        <span class="lectura-num-badge">{{ getOriginalAnimalIndex(animal) + 1 }}</span>
                        <input type="text" v-model="animal.identificador" class="form-control form-control-sm text-uppercase lectura-input-arete flex-grow-1" placeholder="SINIIGA o SA" required :readonly="!animal.agregado_en_lectura" :style="{ background: animal.agregado_en_lectura ? '#fff' : '#f1f5f9' }" @change="onIdentificadorChange(animal)" />
                      </div>
                      <div class="lectura-line2">
                        <select v-model="animal.resultado" class="form-select form-select-sm fw-bold lectura-select-result" :class="getResultadoClass(animal.resultado)">
                          <option value="No Aplica">No Aplica</option>
                          <option value="Pendiente">Pendiente</option>
                          <option value="Negativo">Negativo</option>
                          <option value="Positivo">Positivo</option>
                          <option value="Sospechoso">Sospechoso</option>
                        </select>
                        <input type="text" v-model="animal.observaciones" class="form-control form-control-sm lectura-input-obs flex-grow-1" placeholder="Observaciones..." />
                        <button type="button" class="btn btn-outline-danger btn-sm lectura-btn-delete" @click="removeAnimal(animal)" title="Eliminar">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modo Inyección: Cards Completos con Censo Ganadero -->
              <div v-else-if="form.animales.length > 0" class="d-block d-lg-none px-3 py-2 bg-light-card-container scrollable-animals-container-mobile">
                <div 
                  v-for="(animal, localIdx) in filteredAnimales" 
                  :key="animal" 
                  class="animal-mobile-card mb-3 position-relative"
                  :class="{ 'positivo-card': animal.resultado === 'Positivo' }"
                >
                  <!-- Card Header: Animal Number & Delete -->
                  <div class="card-header-custom d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom">
                    <span class="fw-bold text-slate-700 fs-7-5">ANIMAL #{{ getOriginalAnimalIndex(animal) + 1 }}</span>
                    <button type="button" class="btn btn-link text-danger p-0 d-flex align-items-center gap-1 text-decoration-none fs-7-5" @click="removeAnimal(animal)">
                      <i class="bi bi-trash"></i> Eliminar
                    </button>
                  </div>

                  <!-- Fields Grid -->
                  <div class="row g-2.5">
                    <!-- Identificador -->
                    <div class="col-12">
                      <label class="form-label-custom">IDENTIFICADOR (ARETE)</label>
                      <div class="d-flex gap-2">
                        <input 
                          type="text" 
                          v-model="animal.identificador" 
                          class="form-control form-control-custom text-uppercase flex-grow-1" 
                          :class="{ 'is-valid-custom': animal.identificador && animal.identificador.trim() && animal.en_base_datos }" 
                          placeholder="SINIIGA o SA" 
                          required 
                          @change="onIdentificadorChange(animal)" 
                        />
                        <button class="btn-camera-prominent" type="button" @click="scanSingleAnimal(animal)" title="Escanear Arete">
                          <i class="bi bi-camera-fill"></i> Escanear
                        </button>
                      </div>
                    </div>

                    <!-- Tipo Arete (conditional) -->
                    <div class="col-12" v-if="animal.identificador && animal.identificador.trim() && (isSA(animal.identificador) || animal.en_base_datos === false)">
                      <label class="form-label-custom">TIPO ARETE</label>
                      <select v-model="animal.tipo_arete" class="form-select form-control-custom" :disabled="!animal.identificador || !animal.identificador.trim()" required>
                        <option value="IN">IN</option>
                        <option value="RA">RA</option>
                      </select>
                    </div>

                    <!-- Edad (Meses) & Raza (Side by Side) -->
                    <div class="col-6">
                      <label class="form-label-custom">EDAD (M)</label>
                      <input type="number" v-model.number="animal.edad_meses" class="form-control form-control-custom" placeholder="Meses" min="0" @input="onEdadChange(animal)" />
                    </div>
                    <div class="col-6">
                      <label class="form-label-custom">RAZA</label>
                      <input type="text" v-model="animal.raza" class="form-control form-control-custom" placeholder="Raza" />
                    </div>

                    <!-- Sexo & Fierro (Side by Side) -->
                    <div class="col-6">
                      <label class="form-label-custom">SEXO</label>
                      <select v-model="animal.sexo" class="form-select form-control-custom text-center">
                        <option value="H">H</option>
                        <option value="M">M</option>
                      </select>
                    </div>
                    <div class="col-6 d-flex align-items-center justify-content-between px-2">
                      <label class="form-label-custom mb-0">FIERRO</label>
                      <div class="form-check form-switch pt-1">
                        <input type="checkbox" v-model="animal.fierro" class="form-check-input custom-switch-scale" true-value="Si" false-value="No" />
                      </div>
                    </div>

                    <!-- Resultado (Solo lectura en Inyección) -->
                    <div class="col-12">
                      <label class="form-label-custom">ESTADO</label>
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-secondary fw-normal px-3 py-2 w-100 text-start" style="font-size: 0.82rem; border: 1px solid #e2e8f0;">
                          <i class="bi bi-hourglass-split me-1 text-muted"></i> Pendiente (se captura en lectura)
                        </span>
                      </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-12">
                      <label class="form-label-custom">OBSERVACIONES</label>
                      <input type="text" v-model="animal.observaciones" class="form-control form-control-custom" placeholder="Detalles..." />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load more button/indicator for performance pagination -->
              <div v-if="hasMoreAnimals" class="p-3 text-center border-top bg-light">
                <div class="text-secondary small mb-2">Mostrando {{ filteredAnimales.length }} de {{ totalMatchedAnimalsCount }} animales coincidentes</div>
                <button type="button" class="btn btn-sm btn-primary px-4 py-2" @click="loadMoreAnimals" style="background: #2563eb;">
                  <i class="bi bi-plus-circle"></i> Cargar más animales
                </button>
              </div>
              <div v-else-if="animalSearchQuery && totalMatchedAnimalsCount > 0" class="p-2.5 text-center text-secondary small bg-light-subtle border-top">
                Fin de resultados. {{ totalMatchedAnimalsCount }} animales encontrados.
              </div>

              <!-- Agregar rápido por input inferior -->
              <div class="p-3 border-top bg-light" v-if="form.animales.length > 0">
                <div class="d-flex gap-2">
                  <input v-model="quickArete" type="text" class="form-control form-control-sm text-uppercase" placeholder="Registrar arete manualmente" @keyup.enter="addQuickAnimal" />
                  <button type="button" class="btn btn-outline w-auto px-3 py-1.5" @click="addQuickAnimal">+</button>
                </div>
              </div>
            </div>
          </div>

        </div>


        <InspeccionActions
          :finalizar-bloqueado="esBotonFinalizarBloqueado"
          :en-fase-lectura="inyeccionConfirmada"
          :inyeccion-confirmada="inyeccionConfirmada"
          @save="saveInspeccion"
          @add-animal="addEmptyAnimal"
        />

        <div class="d-lg-none" style="height: 80px;"></div>
      </div>
  </AppLayout>

  <ScannerModal
    v-if="scannerActive"
    @scanned="onScannerScanned"
    @close="scannerActive = false; activeScanIndex = -1;"
  />
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import ScannerModal from '../components/ScannerModal.vue';
import InspeccionActions from '../components/InspeccionActions.vue';
import api from '../services/api.js';
import { Geolocation } from '@capacitor/geolocation';
import db from '../services/db.js';
import backgroundSync from '../services/backgroundSync.js';
import { useInspeccionStore } from '../stores/inspeccion.js';

export default {
  name: 'InspeccionFormView',
  components: { AppLayout, ScannerModal, InspeccionActions },
  created() {
    this.inspeccionStore = useInspeccionStore();
  },
  data() {
    return {
      predios: [],
      visitas: [],
      selectedPredio: null,
      selectedProductor: null,
      quickArete: '',
      _searchInput: '',
      _searchTimer: null,
      animalSearchQuery: '',
      visibleAnimalsLimit: 30,
      showSinInyeccion: false,
      activeSection: 1, // Control de acordeón abierto
      isAdmin: false,
      userName: '',
      isOnline: true,
      originalFolio: '',
      scannerActive: false,
      activeScanIndex: -1,
      form: {
        folio: '',
        clave_interna: '',
        predio_id: '',
        fecha: new Date().toISOString().split('T')[0],
        fecha_inyeccion: new Date().toISOString().split('T')[0],
        hora_inyeccion: new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }),
        fecha_lectura: '',
        hora_lectura: new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }),
        tipo_prueba: 'PPC',
        motivo_prueba: '',
        funcion_zootecnica: 'Carne',
        latitud: '',
        longitud: '',
        observaciones: '',
        // Campos avanzados
        fecha_prueba_anterior: '',
        dictamen_anterior_no: '',
        exencion_no: '',
        exencion_fecha: '',
        hato_libre_no: '',
        hato_libre_fecha: '',
        vigencia_fecha: '',
        // Censo
        sementales: 0,
        vacas: 0,
        vaquillas: 0,
        becerras: 0,
        becerros: 0,
        animales: []
      }
    };
  },
  computed: {
    _animalesIdx() {
      return this.form.animales.map(a => ({
        animal: a,
        _idUpper: (a.identificador || '').toUpperCase()
      }));
    },
    filteredAnimales() {
      const q = this.animalSearchQuery.trim().toUpperCase();
      const idx = this._animalesIdx;
      if (!q) {
        return idx.slice(0, this.visibleAnimalsLimit).map(i => i.animal);
      }
      return idx
        .filter(i => i._idUpper.includes(q))
        .slice(0, this.visibleAnimalsLimit)
        .map(i => i.animal);
    },
    animalesLectura() {
      return this.filteredAnimales.filter(a => a.resultado !== 'No Aplica');
    },
    animalesSinInyeccion() {
      return this.form.animales.filter(a => a.resultado === 'No Aplica');
    },
    hasMoreAnimals() {
      const q = this.animalSearchQuery.trim().toUpperCase();
      const idx = this._animalesIdx;
      const totalMatches = q 
        ? idx.filter(i => i._idUpper.includes(q)).length
        : idx.length;
      return totalMatches > this.visibleAnimalsLimit;
    },
    totalMatchedAnimalsCount() {
      const q = this.animalSearchQuery.trim().toUpperCase();
      const idx = this._animalesIdx;
      return q 
        ? idx.filter(i => i._idUpper.includes(q)).length
        : idx.length;
    },
    totalRows() {
      return this.form.animales.length;
    },
    mostrarColumnaTipoArete() {
      return this.form.animales.some(a => {
        const id = (a.identificador || '').trim();
        return id !== '' && (this.isSA(id) || a.en_base_datos === false);
      });
    },
    conAreteCount() {
      return this.form.animales.filter(a => {
        const id = (a.identificador || '').trim().toUpperCase();
        return id !== '' && id !== 'SA' && id !== 'S/A';
      }).length;
    },
    saSinDefinirCount() {
      return this.form.animales.filter(a => {
        const id = (a.identificador || '').trim().toUpperCase();
        return (id === 'SA' || id === 'S/A') && (!a.tipo_arete || a.tipo_arete === 'SINIIGA');
      }).length;
    },
    saDefinidosCount() {
      return this.form.animales.filter(a => {
        const id = (a.identificador || '').trim().toUpperCase();
        return (id === 'SA' || id === 'S/A') && (a.tipo_arete === 'IN' || a.tipo_arete === 'RA');
      }).length;
    },
    sinDefinirResultadoCount() {
      return this.form.animales.filter(a => !a.resultado || a.resultado === 'Pendiente').length;
    },
    inyeccionConfirmada() {
      if (!this.form.visita_id) return true;
      const visita = this.visitas.find(v => String(v.id) === String(this.form.visita_id));
      if (!visita) return true;
      return visita.inyeccion === true;
    },
    isSection1Complete() {
      return !!(this.form.predio_id && this.form.fecha);
    },
    isSection2Complete() {
      return !!this.form.predio_id;
    },
    isSection3Complete() {
      return !!(this.form.motivo_prueba && this.form.fecha_inyeccion && this.form.hora_inyeccion && this.form.fecha_lectura && this.form.hora_lectura);
    },
    isSection4Complete() {
      return this.form.animales.length > 0 && this.form.animales.every(a => a.identificador && a.identificador.trim());
    },
    esBotonFinalizarBloqueado() {
      if (!this.form.visita_id) return false;
      const visita = this.visitas.find(v => String(v.id) === String(this.form.visita_id));
      if (!visita || !visita.fecha_programada) return false;
      
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);
      
      const fechaProg = new Date(visita.fecha_programada);
      fechaProg.setHours(0, 0, 0, 0);
      
      const inyeccionConf = visita.inyeccion === true;
      
      if (!inyeccionConf) {
        return hoy.getTime() < fechaProg.getTime();
      }
      
      return !this.puedoEditarResultados();
    },
    userFolio: {
      get() {
        if (!this.form.folio || this.form.folio === this.form.clave_interna) {
          return '';
        }
        return this.form.folio;
      },
      set(val) {
        if (!val || !val.trim()) {
          // Si el usuario borra el folio, restaurar la clave interna si era el valor original
          if (this.originalFolio && this.originalFolio === this.form.clave_interna) {
            this.form.folio = this.originalFolio;
          } else {
            this.form.folio = '';
          }
        } else {
          this.form.folio = val;
        }
      }
    }
  },
  watch: {
    'form.animales': {
      deep: true,
      handler(newAnimals) {
        let sementales = 0;
        let vacas = 0;
        let vaquillas = 0;
        let becerras = 0;
        let becerros = 0;

        (newAnimals || []).forEach(animal => {
          // En lectura, excluir del censo los animales sin inyección
          if (this.puedoEditarResultados() && animal.resultado === 'No Aplica') {
            return;
          }
          const edadRaw = animal.edad_meses;
          if (edadRaw === null || edadRaw === undefined || edadRaw === '') {
            return;
          }
          const edad = parseInt(edadRaw) || 0;
          const sexo = (animal.sexo || '').toLowerCase();

          if (sexo === 'macho' || sexo === 'm') {
            if (edad >= 12) {
              sementales++;
            } else {
              becerros++;
            }
          } else if (sexo === 'hembra' || sexo === 'h') {
            if (edad < 12) {
              becerras++;
            } else if (edad >= 12 && edad < 24) {
              vaquillas++;
            } else {
              vacas++;
            }
          }
        });

        this.form.sementales = sementales;
        this.form.vacas = vacas;
        this.form.vaquillas = vaquillas;
        this.form.becerras = becerras;
        this.form.becerros = becerros;
      }
    },
    'form.fecha_inyeccion'(newVal) {
      this.calcularFechaLectura();
    }
  },
  async mounted() {
    // Cargar datos del usuario
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

    // Cargar predios de base local
    this.predios = await db.getPredios();
    this.visitas = await db.getVisitas();

    this._onWindowOnline = () => this.isOnline = true;
    this._onWindowOffline = () => this.isOnline = false;
    window.addEventListener('online', this._onWindowOnline);
    window.addEventListener('offline', this._onWindowOffline);

    // Si viene un dictamen existente desde el índice o el detalle
    const inspeccionId = this.$route.params.id || this.$route.query.inspeccion_id;
    if (inspeccionId) {
      try {
        const res = await api.getInspeccion(inspeccionId);
        const data = res.data || {};
        this.cargarDictamenData(data);
        this.activeSection = 4;
        if (this.form.predio_id) {
          await this.onPredioSelect();
        }
      } catch (e) {
        console.warn('No se pudo cargar el dictamen existente desde la API, buscando localmente:', e);
        // Fallback local
        const pendientes = await db.getInspeccionesPendientes();
        const localInsp = pendientes.find(i => String(i.id) === String(inspeccionId) || i.folio === inspeccionId);
        if (localInsp) {
          this.form = { ...localInsp };
          this.originalFolio = localInsp.folio || '';
          if (this.form.predio_id) {
            await this.onPredioSelect();
          }
          this.activeSection = 4;
          alert('💾 Dictamen local cargado.');
        }
      }
    }

    // Comprobar parámetros de ruta
    const predioId = this.$route.params.predioId;
    if (predioId && !inspeccionId) {
      this.form.predio_id = parseInt(predioId);
      await this.onPredioSelect();
    }

    // ── Restaurar borrador si venimos del escáner individual ──
    const draftData = this.inspeccionStore.inspeccionDraft;
    if (draftData) {
      // Restore the full form state from the draft
      Object.assign(this.form, draftData);
      this.originalFolio = draftData.folio || '';
      this.inspeccionStore.clearInspeccionDraft();

      // Check if we have a single-scan result
      const targetIdx = this.inspeccionStore.scanTargetIndex;
      const singleArete = this.inspeccionStore.scannedSingleArete;

      if (targetIdx !== null && singleArete) {
        const idx = targetIdx;
        if (this.form.animales && this.form.animales[idx]) {
          this.form.animales[idx].identificador = singleArete;
          // Try to look up arete data from the API
          this.onIdentificadorChange(this.form.animales[idx]);
        }
      }

      // Clean up store keys
      this.inspeccionStore.clearScanTargetIndex();
      this.inspeccionStore.clearScannedSingleArete();

      // Re-select predio to restore computed state
      if (this.form.predio_id) {
        await this.onPredioSelect();
      }

      this.activeSection = 4; // Go to results section
    }

    // Comprobar si vienen animales del escáner batch en sesión
    const savedAnimals = this.inspeccionStore.scannedAnimals;
    if (savedAnimals && savedAnimals.length > 0 && !inspeccionId && !draftData) {
      this.form.animales = savedAnimals;
      this.inspeccionStore.clearScannedAnimals();
      this.activeSection = 4; // Ir directo a la sección de resultados
    }

    // Si viene ID de visita
    const visitaId = this.$route.query.visita_id;
    if (visitaId && !inspeccionId && !draftData) {
      this.form.visita_id = parseInt(visitaId);
      
      const visita = this.visitas.find(v => String(v.id) === String(visitaId));
      if (visita) {
        if (visita.predio_id) {
          this.form.predio_id = visita.predio_id;
          await this.onPredioSelect();
        }

        // Si no se cargó un borrador local en onPredioSelect (por ejemplo, form.folio sigue siendo la clave_interna),
        // y la visita tiene inspección en el servidor, intentar cargarla
        if ((!this.form.folio || this.form.folio === this.form.clave_interna) && visita.inspeccion?.id) {
          try {
            const res = await api.getInspeccion(visita.inspeccion.id);
            const data = res.data || {};
            this.cargarDictamenData(data);
            if (this.form.predio_id) {
              await this.onPredioSelect();
            }
            this.activeSection = 4;
            alert('💾 Dictamen recuperado del servidor para continuar.');
          } catch (err) {
            console.warn('No se pudo cargar el dictamen del servidor para esta visita:', err);
          }
        }
      }
    }

    // Calcular la fecha de lectura inicial
    this.calcularFechaLectura();

    // No pre-generate folio to let it be visually optional in the form
  },
  beforeUnmount() {
    window.removeEventListener('online', this._onWindowOnline);
    window.removeEventListener('offline', this._onWindowOffline);
  },
  methods: {
    cargarDictamenData(data) {
      this.form.folio = data.folio || this.form.folio;
      this.originalFolio = data.folio || '';
      this.form.clave_interna = data.clave_interna || this.form.clave_interna;
      this.form.predio_id = data.predio_id || '';
      this.form.fecha = data.fecha || this.form.fecha;
      this.form.fecha_inyeccion = data.fecha_inyeccion || this.form.fecha_inyeccion;
      this.form.hora_inyeccion = data.hora_inyeccion || this.form.hora_inyeccion;
      this.form.fecha_lectura = data.fecha_lectura || this.form.fecha_lectura;
      this.form.hora_lectura = data.hora_lectura || this.form.hora_lectura;
      this.form.tipo_prueba = data.tipo_prueba || this.form.tipo_prueba;
      this.form.motivo_prueba = data.motivo_prueba || this.form.motivo_prueba;
      this.form.funcion_zootecnica = data.funcion_zootecnica || this.form.funcion_zootecnica;
      this.form.latitud = data.predio?.latitud || data.latitud || this.form.latitud;
      this.form.longitud = data.predio?.longitud || data.longitud || this.form.longitud;
      this.form.observaciones = data.observaciones || this.form.observaciones;
      this.form.vigencia_fecha = data.vigencia_fecha || this.form.vigencia_fecha;
      this.form.sementales = data.sementales ?? 0;
      this.form.vacas = data.vacas ?? 0;
      this.form.vaquillas = data.vaquillas ?? 0;
      this.form.becerras = data.becerras ?? 0;
      this.form.becerros = data.becerros ?? 0;
      this.form.visita_id = data.visita_id || this.form.visita_id;
      this.form.fecha_prueba_anterior = data.fecha_prueba_anterior || '';
      this.form.dictamen_anterior_no = data.dictamen_anterior_no || '';
      this.form.exencion_no = data.exencion_no || '';
      this.form.exencion_fecha = data.exencion_fecha || '';
      this.form.hato_libre_no = data.hato_libre_no || '';
      this.form.hato_libre_fecha = data.hato_libre_fecha || '';
      this.form.estado = data.estado || this.form.estado;

      if (data.predio) {
        this.selectedPredio = data.predio;
        if (data.predio.productor) {
          this.selectedProductor = data.predio.productor;
        }
      }

      if (Array.isArray(data.detalles)) {
        this.form.animales = data.detalles.map(detalle => ({
          identificador: detalle.animal?.numero_arete_siniiga || '',
          tipo_arete: detalle.tipo_arete || 'SINIIGA',
          edad_meses: detalle.edad_meses ?? detalle.animal?.edad ?? null,
          raza: detalle.raza || detalle.animal?.raza || '',
          sexo: (detalle.sexo === 'Hembra' || detalle.sexo === 'H') ? 'H' : 'M',
          fierro: detalle.fierro || 'Si',
          resultado: detalle.resultado_prueba || 'Pendiente',
          observaciones: detalle.observaciones_animal || '',
          en_base_datos: (detalle.tipo_arete && detalle.tipo_arete !== 'SINIIGA') ? false : true,
          agregado_en_lectura: detalle.agregado_en_lectura || false
        }));
      } else if (Array.isArray(data.animales)) {
        this.form.animales = data.animales.map(a => ({
          identificador: a.identificador || '',
          tipo_arete: a.tipo_arete || 'SINIIGA',
          edad_meses: a.edad_meses ?? null,
          raza: a.raza || '',
          sexo: ['H', 'Hembra'].includes(a.sexo) ? 'H' : 'M',
          fierro: a.fierro || 'Si',
          resultado: a.resultado || 'Pendiente',
          observaciones: a.observaciones || '',
          en_base_datos: a.en_base_datos !== undefined ? a.en_base_datos : false,
          agregado_en_lectura: a.agregado_en_lectura || false
        }));
      }
    },
    toggleSection(num) {
      this.activeSection = this.activeSection === num ? null : num;
    },
    async onPredioSelect() {
      if (!this.form.predio_id) {
        this.selectedPredio = null;
        this.selectedProductor = null;
        return;
      }
      const pred = this.predios.find(p => p.id === this.form.predio_id);
      if (pred) {
        this.selectedPredio = pred;
        this.selectedProductor = pred.productor;
        
        // Cargar coordenadas si el predio ya las tiene
        if (pred.latitud) this.form.latitud = pred.latitud;
        if (pred.longitud) this.form.longitud = pred.longitud;

        // Generar clave_interna = INICIALES-RANDOM-DDMMYYYY
        if (pred.productor_id || (pred.productor && pred.productor.id)) {
          const prodId = pred.productor_id || pred.productor.id;
          const productor = pred.productor || this.productores.find(p => p.id === prodId);
          let iniciales = '';
          if (productor) {
            const nombre = (productor.nombre || '').trim();
            const apeP = (productor.apellido_paterno || '').trim();
            const apeM = (productor.apellido_materno || '').trim();
            // Primera letra de cada palabra del nombre
            nombre.split(/\s+/).forEach(p => {
              if (p) iniciales += p.charAt(0).toUpperCase();
            });
            if (apeP) iniciales += apeP.charAt(0).toUpperCase();
            if (apeM) iniciales += apeM.charAt(0).toUpperCase();
          }
          const rand = Math.random().toString(36).substring(2, 7).toUpperCase();
          const ahora = new Date();
          const dd = String(ahora.getDate()).padStart(2, '0');
          const mm = String(ahora.getMonth() + 1).padStart(2, '0');
          const yyyy = ahora.getFullYear();
          this.form.clave_interna = `${iniciales}-${rand}-${dd}${mm}${yyyy}`;
        }
      }

      // Buscar si hay un borrador guardado para este predio por clave_interna
      if (!this.$route.query.inspeccion_id && this.form.clave_interna) {
        const listas = await db.getInspeccionesPendientes();
        const borradorExistente = listas.find(i => 
          String(i.predio_id) === String(this.form.predio_id) && 
          i.estado === 'borrador'
        );

        if (borradorExistente && borradorExistente.folio !== this.form.folio) {
          this.cargarDictamenData(borradorExistente);
          this.form.clave_interna = borradorExistente.clave_interna || this.form.clave_interna;
          if (Array.isArray(borradorExistente.animales) && borradorExistente.animales.length > 0) {
            this.activeSection = 4;
          }
          alert('💾 Se detectó un borrador existente para este productor hoy. Se ha cargado automáticamente para evitar duplicados.');
        } else {
          // Usar clave_interna como folio inicial (oculto del usuario)
          if (!this.form.folio || this.form.folio === this.form.clave_interna || this.form.folio === '') {
            this.form.folio = this.form.clave_interna;
            this.originalFolio = this.form.folio;
          }
        }
      }
    },
    onTipoPruebaChange() {
      // Si cambia de PPC a PCC y no tiene censo anterior
      if (this.form.tipo_prueba === 'PCC') {
        alert('ℹ️ Se han habilitado los campos avanzados de SENASICA para la Prueba Cervical Comparativa.');
      }
    },
    calcularFechaLectura() {
      if (!this.form.fecha_inyeccion) return;
      const fInyeccion = new Date(this.form.fecha_inyeccion);
      // La lectura es 72 horas después de la inyección (3 días exactos)
      fInyeccion.setDate(fInyeccion.getDate() + 3);
      this.form.fecha_lectura = fInyeccion.toISOString().split('T')[0];
    },
    puedoEditarResultados() {
      if (!this.form.fecha_lectura) return false;
      const hoy = new Date().toISOString().split('T')[0];
      return hoy >= this.form.fecha_lectura;
    },
    async obtenerCoordenadasGPS() {
      try {
        let permStatus = await Geolocation.checkPermissions();
        
        if (permStatus.location === 'prompt' || permStatus.location === 'prompt-with-rationale') {
          permStatus = await Geolocation.requestPermissions();
        }
        
        if (permStatus.location === 'denied') {
          const retry = confirm("⚠️ El permiso de ubicación (GPS) está desactivado o denegado.\n\n¿Deseas intentar solicitar el permiso de nuevo?");
          if (retry) {
            permStatus = await Geolocation.requestPermissions();
          }
        }
        
        if (permStatus.location !== 'granted') {
          alert("❌ Permiso de ubicación no concedido. Debes otorgar el permiso en la configuración de la app o dispositivo para capturar coordenadas GPS.");
          return;
        }

        const coordinates = await Geolocation.getCurrentPosition({
          enableHighAccuracy: true,
          timeout: 12000
        });
        this.form.latitud = coordinates.coords.latitude.toFixed(6);
        this.form.longitud = coordinates.coords.longitude.toFixed(6);
        alert(`📍 Coordenadas capturadas con éxito:\nLatitud: ${this.form.latitud}\nLongitud: ${this.form.longitud}`);
      } catch (e) {
        console.error("Error al obtener ubicación Capacitor:", e);
        // Fallback for browser geolocation
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            (position) => {
              this.form.latitud = position.coords.latitude.toFixed(6);
              this.form.longitud = position.coords.longitude.toFixed(6);
              alert(`📍 Coordenadas capturadas con éxito (Navegador):\nLatitud: ${this.form.latitud}\nLongitud: ${this.form.longitud}`);
            },
            (err) => {
              console.error("Error de ubicación en navegador:", err);
              alert('⚠️ No se pudo obtener la localización del dispositivo. Por favor ingresa las coordenadas manualmente o verifica que el GPS esté encendido.');
            },
            { enableHighAccuracy: true, timeout: 10000 }
          );
        } else {
          alert('⚠️ Geolocalización no soportada en este dispositivo.');
        }
      }
    },
    addEmptyAnimal() {
      this.clearAnimalSearch();
      this.form.animales.push({
        identificador: '',
        tipo_arete: 'IN',
        edad_meses: null,
        raza: '',
        sexo: 'H',
        fierro: 'Si',
        resultado: 'Pendiente',
        observaciones: '',
        en_base_datos: false,
        agregado_en_lectura: this.puedoEditarResultados()
      });
      const newIndex = this.form.animales.length - 1;
      this.activeScanIndex = newIndex;
      this.scannerActive = true;
    },
    addQuickAnimal() {
      if (!this.quickArete.trim()) return;
      
      const exists = this.form.animales.some(a => a.identificador.toUpperCase() === this.quickArete.trim().toUpperCase());
      if (exists) {
        alert('⚠️ Este arete ya fue registrado en el dictamen.');
        return;
      }

      this.clearAnimalSearch();
      this.form.animales.push({
        identificador: this.quickArete.trim().toUpperCase(),
        tipo_arete: 'IN',
        edad_meses: null,
        raza: '',
        sexo: 'H',
        fierro: 'Si',
        resultado: 'Pendiente',
        observaciones: '',
        en_base_datos: false,
        agregado_en_lectura: this.puedoEditarResultados()
      });

      this.quickArete = '';
    },
    removeAnimal(animal) {
      const index = this.form.animales.indexOf(animal);
      if (index > -1) {
        this.form.animales.splice(index, 1);
      }
    },
    onEdadChange(animal) {
      const edad = parseInt(animal.edad_meses) || 0;
      if (edad > 0 && edad < 6) {
        animal.resultado = 'No Aplica';
      } else if (animal.resultado === 'No Aplica') {
        animal.resultado = 'Pendiente';
      }
    },
    getResultadoClass(res) {
      if (res === 'Negativo') return 'text-success bg-success-subtle';
      if (res === 'Positivo') return 'text-danger bg-danger-subtle';
      if (res === 'Sospechoso') return 'text-warning bg-warning-subtle';
      return 'text-secondary bg-light';
    },
    scanSingleAnimal(animal) {
      const index = this.form.animales.indexOf(animal);
      if (index > -1) {
        this.activeScanIndex = index;
        this.scannerActive = true;
      }
    },
    onSearchInput() {
      clearTimeout(this._searchTimer);
      this._searchTimer = setTimeout(() => {
        this.animalSearchQuery = this._searchInput;
        this.visibleAnimalsLimit = Math.max(this.visibleAnimalsLimit, 30);
      }, 250);
    },
    clearAnimalSearch() {
      this._searchInput = '';
      this.animalSearchQuery = '';
      this.visibleAnimalsLimit = 30;
      clearTimeout(this._searchTimer);
    },
    loadMoreAnimals() {
      this.visibleAnimalsLimit += 30;
    },
    getOriginalAnimalIndex(animal) {
      return this.form.animales.indexOf(animal);
    },
    onScannerScanned(decodedText) {
      if (this.activeScanIndex !== -1 && this.form.animales[this.activeScanIndex]) {
        this.form.animales[this.activeScanIndex].identificador = decodedText.trim().toUpperCase();
        this.onIdentificadorChange(this.form.animales[this.activeScanIndex]);
      }
      this.scannerActive = false;
      this.activeScanIndex = -1;
    },
    isSA(val) {
      if (!val) return false;
      const cleanVal = val.trim().toUpperCase();
      return cleanVal === 'SA' || cleanVal === 'S/A';
    },
    async onIdentificadorChange(animal) {
      if (!animal.identificador) return;
      const numero = animal.identificador.trim();
      
      // Auto-detectar SA
      if (numero.toUpperCase() === 'SA' || numero.toUpperCase() === 'S/A') {
        animal.identificador = 'SA';
        animal.tipo_arete = 'IN'; // default
        animal.en_base_datos = false;
        return;
      }
      
      // Si tiene longitud >= 5, intentar buscar datos
      if (numero.length >= 5) {
        try {
          const res = await api.buscarArete(numero);
          if (res.success && res.data) {
            const data = res.data;
            animal.en_base_datos = true;
            
            // Establecer edad en base a la fecha de nacimiento, o fallback a edad_meses
            if (data.fecha_nacimiento) {
              const birthDate = new Date(data.fecha_nacimiento);
              if (!isNaN(birthDate.getTime())) {
                const today = new Date();
                let months = (today.getFullYear() - birthDate.getFullYear()) * 12;
                months -= birthDate.getMonth();
                months += today.getMonth();
                if (today.getDate() < birthDate.getDate()) {
                  months--;
                }
                animal.edad_meses = Math.max(0, months);
              } else if (data.edad_meses !== undefined && data.edad_meses !== null) {
                animal.edad_meses = data.edad_meses;
              }
            } else if (data.edad_meses !== undefined && data.edad_meses !== null) {
              animal.edad_meses = data.edad_meses;
            }
            
            if (data.raza) {
              animal.raza = data.raza;
            }
            if (data.sexo) {
              const s = data.sexo.charAt(0).toUpperCase();
              animal.sexo = (s === 'H' || s === 'F') ? 'H' : 'M';
            }
          } else {
            animal.en_base_datos = false;
          }
        } catch (e) {
          console.warn('No se pudo encontrar datos del arete:', e);
          animal.en_base_datos = false;
        }
      } else {
        animal.en_base_datos = false;
      }
    },
    async saveInspeccion(estado) {
      if (!this.form.predio_id) {
        alert('⚠️ Por favor seleccione el productor y predio del dictamen.');
        this.activeSection = 1;
        return;
      }

      if (!this.form.fecha) {
        alert('⚠️ La fecha del dictamen es obligatoria.');
        this.activeSection = 1;
        return;
      }

      let saveEstado = estado;
      
      // Si el usuario presiona "Finalizar" (sincronizado) pero aún no es la fecha de lectura:
      // se le advierte y se guarda como borrador con la inyección completada.
      if (saveEstado === 'sincronizado' && !this.inyeccionConfirmada) {
        const confirmar = confirm("Fase de Inyección: El dictamen se guardará como BORRADOR local y la visita se marcará con inyección realizada.\n\nPodrá ingresar los resultados en la fase de lectura (72 horas después).\n\n¿Desea continuar?");
        if (!confirmar) return;
        saveEstado = 'borrador';
      }

      if (saveEstado === 'sincronizado') {
        if (!this.form.fecha_inyeccion || !this.form.hora_inyeccion || !this.form.fecha_lectura || !this.form.hora_lectura) {
          alert('⚠️ Las fechas y horas de inyección y lectura son obligatorias para finalizar el dictamen.');
          this.activeSection = 3;
          return;
        }

        if (this.form.animales.length === 0) {
          alert('⚠️ Debe capturar al menos un animal con su resultado individual para finalizar el dictamen.');
          this.activeSection = 4;
          return;
        }

        const animalesActivos = this.puedoEditarResultados() ? this.form.animales.filter(a => a.resultado !== 'No Aplica') : this.form.animales;
        if (animalesActivos.length === 0 && saveEstado === 'sincronizado') {
          alert('⚠️ No hay animales que hayan recibido inyecci\u00f3n para finalizar el dictamen. Registre al menos un animal con inyecci\u00f3n o guarde como borrador.');
          this.activeSection = 4;
          return;
        }

        const hasEmptyAretes = this.form.animales.some(a => !a.identificador || !a.identificador.trim());
        if (hasEmptyAretes) {
          alert('⚠️ Hay animales en la lista con número de arete vacío. Rellene los campos o elimine las filas vacías.');
          this.activeSection = 4;
          return;
        }

        const hasPendientes = this.form.animales.filter(a => a.resultado !== 'No Aplica').some(a => !a.resultado || a.resultado === 'Pendiente');
        if (hasPendientes && this.puedoEditarResultados()) {
          alert('⚠️ Todos los animales (excepto los menores de 6 meses sin inyecci\u00f3n) deben tener un resultado asignado (Negativo, Positivo o Sospechoso) para poder finalizar el dictamen.');
          this.activeSection = 4;
          return;
        }

        // Reglas temporales:
        // 1. Inyección no antes de la visita
        if (this.form.visita_id) {
          const visita = this.visitas.find(v => String(v.id) === String(this.form.visita_id));
          if (visita && visita.fecha_programada) {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            const fechaProg = new Date(visita.fecha_programada);
            fechaProg.setHours(0, 0, 0, 0);
            
            if (hoy.getTime() < fechaProg.getTime()) {
              const formattedDate = fechaProg.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
              alert(`⚠️ No se puede finalizar la inyección antes de la fecha programada de la visita (${formattedDate}).`);
              return;
            }
          }
        }

        // 2. Dictamen no antes de la lectura
        if (this.form.fecha_lectura) {
          const hoy = new Date();
          hoy.setHours(0, 0, 0, 0);
          
          const fechaLectura = new Date(this.form.fecha_lectura);
          fechaLectura.setHours(0, 0, 0, 0);
          
          if (hoy.getTime() < fechaLectura.getTime()) {
            const formattedLectura = fechaLectura.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
            alert(`⚠️ No se puede finalizar el dictamen antes de la fecha programada de la lectura (${formattedLectura}).`);
            return;
          }
        }
      }

      // Validar conteo de animales: suma de categorías vs lista individual
      const animalesParaConteo = this.puedoEditarResultados() ? this.form.animales.filter(a => a.resultado !== 'No Aplica') : this.form.animales;
      if (animalesParaConteo.length > 0) {
        const sumaConteo = (this.form.sementales || 0) + (this.form.vacas || 0) + (this.form.vaquillas || 0) + (this.form.becerras || 0) + (this.form.becerros || 0);
        if (sumaConteo !== animalesParaConteo.length) {
          const dif = animalesParaConteo.length - sumaConteo;
          const advertencia =
            `⚠️ Diferencia en el conteo de animales:\n\n` +
            `➡️ Animales en lista individual: ${animalesParaConteo.length}\n` +
            `➡️ Suma de categorías (Sementales+Vacas+Vaquillas+Becerras+Becerros): ${sumaConteo}\n` +
            `➡️ Diferencia: ${dif > 0 ? `${dif} animales sin categoría asignada` : `${Math.abs(dif)} de más en categorías`}\n\n` +
            `Los animales sin edad registrada no se contabilizan en las categorías.` +
            (dif > 0 ? ` Revise que todos los animales tengan edad y sexo.` : '');
          if (saveEstado === 'sincronizado') {
            if (!confirm(advertencia + '\n\n¿Desea continuar de todas formas?')) return;
          } else {
            console.warn(advertencia);
          }
        }
      }

      // Generar folio automático si quedó vacío al guardar
      if (!this.form.folio || !this.form.folio.trim()) {
        this.form.folio = this.form.clave_interna || `TEMP-${Date.now()}-${Math.floor(1000 + Math.random() * 9000)}`;
      }

      let conflictFound = false;
      if (this.isOnline && (saveEstado === 'sincronizado' || (estado === 'sincronizado' && saveEstado === 'borrador'))) {
        try {
          const checkRes = await api.getInspecciones({ folio: this.form.folio });
          if (checkRes.success && checkRes.data && checkRes.data.length > 0) {
            const basicServer = checkRes.data[0];
            const detailRes = await api.getInspeccion(basicServer.id);
            const serverInsp = detailRes.data || {};
            const serverAnimals = serverInsp.detalles || [];
            const localAnimals = this.form.animales || [];
            
            const serverAretes = serverAnimals.map(a => (a.animal?.numero_arete_siniiga || a.identificador || '').trim().toUpperCase()).filter(Boolean);
            const localAretes = localAnimals.map(a => (a.identificador || '').trim().toUpperCase()).filter(Boolean);
            
            const isMetadataDifferent = 
              this.form.fecha !== serverInsp.fecha ||
              this.form.tipo_prueba !== serverInsp.tipo_prueba ||
              this.form.motivo_prueba !== serverInsp.motivo_prueba ||
              this.form.funcion_zootecnica !== serverInsp.funcion_zootecnica ||
              (this.form.sementales || 0) !== (serverInsp.sementales || 0) ||
              (this.form.vacas || 0) !== (serverInsp.vacas || 0) ||
              (this.form.vaquillas || 0) !== (serverInsp.vaquillas || 0) ||
              (this.form.becerras || 0) !== (serverInsp.becerras || 0) ||
              (this.form.becerros || 0) !== (serverInsp.becerros || 0);

            const areIdentical = !isMetadataDifferent && 
                                 serverAretes.length === localAretes.length && 
                                 serverAretes.every(val => localAretes.includes(val)) &&
                                 localAretes.every(val => serverAretes.includes(val));
            
            if (!areIdentical) {
              conflictFound = true;
            }
          }
        } catch (e) {
          console.warn("Error pre-verificando conflicto con servidor:", e);
        }
      }
      
      this.form.estado = saveEstado;

      try {
        // Remover el folio original de IndexedDB si el folio cambió
        if (this.originalFolio && this.originalFolio !== this.form.folio) {
          await db.removeInspeccion(this.originalFolio);
        }

        await db.saveInspeccion({ ...this.form });
        
        this.originalFolio = this.form.folio;

        // Actualizar visita local en IndexedDB si corresponde
        if (this.form.visita_id) {
          const localVisitas = await db.getVisitas();
          const vIdx = localVisitas.findIndex(v => String(v.id) === String(this.form.visita_id));
          if (vIdx >= 0) {
            localVisitas[vIdx].inyeccion = true;
            localVisitas[vIdx].estado = 'completada';
            await db.saveVisitas(localVisitas);
            this.visitas = localVisitas;
          }

          // También actualizar en visitas pendientes (offline)
          let visitasPendientes = await db.getVisitasPendientes();
          const vpIdx = visitasPendientes.findIndex(v => String(v.id || v.codigo) === String(this.form.visita_id));
          if (vpIdx >= 0) {
            visitasPendientes[vpIdx].inyeccion = true;
            visitasPendientes[vpIdx].estado = 'completada';
            await db.saveVisitasPendientes(visitasPendientes);
          }
        }

        if (conflictFound) {
          alert(`⚠️ Conflicto Detectado: El dictamen con folio "${this.form.folio}" ya existe en el servidor con datos diferentes.\n\nSe ha guardado localmente en tu dispositivo. Te redirigiremos al Centro de Sincronización para comparar las diferencias y elegir la versión correcta.`);
          this.$router.push({ path: '/sync', query: { check_folio: this.form.folio } });
          return;
        }

        if (estado === 'sincronizado' && saveEstado === 'borrador') {
          alert('🎉 Fase de Inyección registrada con éxito.\nEl dictamen se guardó como borrador local y la visita se marcó con inyección realizada. Se sincronizará automáticamente al detectar conexión.');
          backgroundSync.syncIfConnected().catch(e => console.error(e));
        } else if (saveEstado === 'sincronizado') {
          alert('🎉 Dictamen finalizado con éxito.\nQueda almacenado de manera local y seguro en tu dispositivo. Se sincronizará automáticamente cuando tengas conexión.');
          backgroundSync.syncIfConnected().catch(e => console.error(e));
        } else {
          alert('💾 Borrador guardado exitosamente. Podrás continuar editándolo en el panel.');
        }

        this.$router.push('/dashboard');
      } catch (err) {
        alert('❌ Error al guardar el dictamen localmente: ' + err.message);
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

.bg-light-page {
  background-color: #f8fafc !important;
}

.main-content {
  background-color: #f8fafc !important;
}

/* Header Grid Layout */
.mobile-header {
  min-height: 72px;
  height: auto;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  background: #fff;
  padding: 12px 1rem;
  position: sticky;
  top: 0;
  z-index: 50;
  border-bottom: 1px solid #eef2f7;
}

.header-left {
  display: flex;
  justify-content: flex-start;
  align-items: center;
}

.header-right {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
}

.header-back-btn {
  width: 42px;
  height: 42px;
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #111827;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.header-back-btn:active {
  background-color: #f1f5f9;
}

.brand-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #2563eb;
  font-size: 1.1rem;
}

.brand-icon,
.sidebar-logo {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

/* Connectivity Pill Badge */
.connectivity-badge-pill {
  width: 44px;
  height: 28px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  border: 1px solid transparent;
}

.connectivity-badge-pill.online {
  background-color: #d1fae5;
  color: #10b981;
  border-color: #a7f3d0;
}

.connectivity-badge-pill.offline {
  background-color: #fee2e2;
  color: #ef4444;
  border-color: #fca5a5;
}

.avatar-circle {
  width: 34px;
  height: 34px;
  border: 1.5px solid #cbd5e1;
  background: transparent;
  color: #2563eb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 50%;
  font-size: 1.1rem;
  transition: all 0.2s ease;
}

.avatar-circle:active {
  background-color: #f1f5f9;
}

/* Sidebar Styles */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.38);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  z-index: 90;
}

.sidebar-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 270px;
  height: 100dvh;
  background: #fff;
  z-index: 100;
  transform: translateX(-100%);
  transition: transform 0.22s ease;
  box-shadow: 20px 0 40px rgba(15, 23, 42, 0.15);
  display: flex;
  flex-direction: column;
}

.sidebar.active {
  transform: translateX(0);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 1rem 1rem 0.8rem;
  font-weight: 800;
  color: #2563eb;
  border-bottom: 1px solid #eef2f7;
}

.btn-close-sidebar {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #334155;
}

.nav {
  padding: 0.8rem 0.4rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.nav-link {
  padding: 0.85rem 1rem;
  border-radius: 0.8rem;
  color: #334155;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  text-decoration: none;
}

.nav-link.active {
  background: #2563eb;
  color: white;
}

.logout-btn {
  margin-top: auto;
}

/* Page Containers */
.container-form-wrapper {
  max-width: 600px;
  margin: 0 auto;
  width: 100%;
}

.welcome-header {
  padding: 1.25rem 0 0.5rem 0;
}

/* Accordion Container */
.accordion-container {
  display: flex;
  flex-direction: column;
}

.accordion-item {
  background: white;
  border-radius: 20px !important;
  border: 1px solid rgba(226, 232, 240, 0.8) !important;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.03) !important;
  overflow: hidden;
}

.accordion-header-btn {
  width: 100%;
  padding: 18px 20px;
  background: white;
  border: none;
  font-weight: 800;
  font-size: 0.98rem;
  color: #0f172a;
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-align: left;
  cursor: pointer;
  gap: 8px;
  transition: background-color 0.2s ease;
}

.accordion-header-btn:not(.collapsed) {
  background-color: white !important;
  border-bottom: 1px solid #f1f5f9;
}

.accordion-icon-box {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}

.header-title {
  font-weight: 800;
  color: #0f172a;
  font-size: 0.95rem;
  line-height: 1.25;
  text-transform: uppercase;
}

/* Completion Badges */
.accordion-badge-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 700;
  margin-right: 4px;
  border: 1.5px solid transparent;
  white-space: nowrap;
}

.accordion-badge-pill.danger {
  background-color: #fee2e2;
  color: #ef4444;
  border-color: #fee2e2;
}

.accordion-badge-pill.success {
  background-color: #d1fae5;
  color: #10b981;
  border-color: #d1fae5;
}

.arrow-icon {
  font-size: 1.1rem;
  transition: transform 0.2s ease;
  color: #94a3b8;
  flex-shrink: 0;
}

.arrow-icon.rotate {
  transform: rotate(180deg);
  color: #0f172a;
}

.accordion-body-content {
  display: none;
  padding: 20px;
  background: white;
  border-top: 1px solid #f1f5f9;
}

.accordion-body-content.show {
  display: block;
}

.gps-btn {
  font-size: 0.78rem;
  color: #2563eb;
}

/* Card Outer Flat Mobile and Rounded Form */
.card-outer-mobile-flat {
  background: white !important;
  border-radius: 20px !important;
  padding: 1.75rem !important;
  border: 1px solid rgba(226, 232, 240, 0.8) !important;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.03) !important;
}

.fs-7-5 {
  font-size: 0.78rem !important;
}



/* Tablas Responsivas para Aretes Móviles */
.table-mobile-cards {
  width: 100%;
  border-collapse: collapse;
}

.small-badge {
  font-size: 0.72rem;
  padding: 5px 8px;
}

.positivo-row {
  background-color: rgba(239, 68, 68, 0.05);
}

.positivo-row td::before {
  color: var(--color-danger) !important;
}

/* Mobile responsive styles */
@media (max-width: 991.98px) {
  .d-none-mobile {
    display: none !important;
  }
  
  .table-mobile-cards tr {
    display: block;
    margin: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: white;
    padding: 12px;
    box-shadow: var(--shadow-sm);
    position: relative;
  }
  
  .table-mobile-cards tr::before {
    content: "";
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--color-primary);
    border-top-left-radius: 16px;
    border-bottom-left-radius: 16px;
  }
  
  .table-mobile-cards tr.positivo-row::before {
    background: var(--color-danger);
  }

  .table-mobile-cards td {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    border: none !important;
    padding: 6px 0 !important;
    text-align: left;
    gap: 4px;
  }
  
  .table-mobile-cards td::before {
    content: attr(data-label);
    font-weight: 700;
    color: var(--text-secondary);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: block;
  }

  .table-mobile-cards td.fierro-cell {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
  
  .table-mobile-cards td.fierro-cell::before {
    display: inline-block;
  }
  
  .table-mobile-cards td.fierro-cell input {
    width: auto !important;
  }

  .table-mobile-cards td.remove-btn-cell {
    border-top: 1px solid #f1f5f9 !important;
    margin-top: 8px;
    padding-top: 8px !important;
    flex-direction: row;
    justify-content: center;
  }

  .table-mobile-cards td.remove-btn-cell button {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
    font-size: 0.85rem;
  }
  
  .table-mobile-cards td.remove-btn-cell button::after {
    content: " Eliminar Animal";
  }
}

/* Custom Select and Input Styling inside Accordion to match Mockup */
.accordion-body-content select.form-control,
.accordion-body-content select.form-select {
  appearance: none;
  background-color: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 12px 45px 12px 16px;
  font-size: 0.95rem;
  color: #1e293b;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 18px center;
  background-size: 16px;
  outline: none;
  transition: all 0.2s ease;
  width: 100%;
}

.accordion-body-content select.form-control:focus,
.accordion-body-content select.form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.accordion-body-content input.form-control {
  background-color: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 12px 16px;
  font-size: 0.95rem;
  color: #1e293b;
  outline: none;
  transition: all 0.2s ease;
}

.accordion-body-content input.form-control:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.accordion-body-content input.form-control:disabled,
.accordion-body-content select.form-control:disabled,
.accordion-body-content select.form-select:disabled {
  background-color: #f1f5f9 !important;
  color: #94a3b8;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

/* Combined Datetime Inputs */
.combined-datetime-input {
  display: flex;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  background: white;
  width: 100%;
}

.combined-datetime-input .date-part,
.combined-datetime-input .time-part {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
}

.combined-datetime-input .time-part {
  border-left: 1.5px solid #e2e8f0;
}

.combined-datetime-input .readonly-part {
  background-color: #f8fafc;
}

.combined-datetime-input .datetime-input-field {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  padding: 12px 42px 12px 16px;
  font-size: 0.95rem;
  color: #1e293b;
}

.combined-datetime-input .datetime-icon {
  position: absolute;
  right: 16px;
  pointer-events: none;
  font-size: 1.1rem;
  color: #64748b;
}

.combined-datetime-input .datetime-input-field::-webkit-calendar-picker-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: auto;
  height: auto;
  color: transparent;
  background: transparent;
  cursor: pointer;
}

/* Date input custom styles */
.date-input-wrapper {
  position: relative;
  width: 100%;
}

.form-control-date {
  padding-right: 48px;
  background-image: none;
}

.date-icon {
  position: absolute;
  right: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  font-size: 1.15rem;
  pointer-events: none;
}

.form-control-date::-webkit-calendar-picker-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: auto;
  height: auto;
  color: transparent;
  background: transparent;
  cursor: pointer;
}

/* Responsive configurations */
@media (min-width: 992px) {
  .mobile-header,
  .bottom-nav {
    display: none;
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

@media (max-width: 991.98px) {
  .sidebar {
    display: flex;
  }
  
  .main-content {
    padding: 1rem;
    padding-bottom: calc(160px + env(safe-area-inset-bottom, 0px));
  }
}

/* --- ESTILOS ADICIONALES PARA APARTADO 4 MÓVIL Y RESPONSIVO --- */

/* Badge por definir */
.badge-por-definir {
  background-color: #fee2e2;
  color: #ef4444;
  border: 1px solid #fca5a5;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.78rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
}

/* Badge completo */
.badge-completo-outline {
  background-color: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.78rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Results Blocked Banner */
.results-blocked-banner {
  background-color: #fffbeb;
  border: 1.5px dashed #f59e0b;
  border-radius: 16px;
}

.banner-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Animal Mobile Card */
.animal-mobile-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 4px 8px rgba(15, 23, 42, 0.02);
  transition: border-color 0.2s ease;
}

.animal-mobile-card.positivo-card {
  border-color: #fca5a5;
  background-color: #fffdfd;
}

.card-header-custom {
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

/* Custom labels and inputs for card */
.form-label-custom {
  font-size: 0.72rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  margin-bottom: 4px;
  display: block;
}

.input-group-custom {
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid #e2e8f0;
  background: white;
}

.input-group-custom .form-control-custom {
  border: none !important;
  box-shadow: none !important;
}

.form-control-custom {
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 0.9rem;
  outline: none;
  background-color: white;
  width: 100%;
}

.form-control-custom:focus {
  border-color: #3b82f6;
}

/* Valid custom check */
.is-valid-custom {
  border-color: #10b981 !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2310b981' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M5 13l4 4L19 7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 16px;
  padding-right: 36px;
}

.btn-camera-custom {
  border: none;
  background-color: #2563eb;
  color: white;
  padding: 0 16px;
  cursor: pointer;
}

.btn-camera-custom:active {
  background-color: #1d4ed8;
}

.custom-switch-scale {
  transform: scale(1.2);
  cursor: pointer;
}

/* Contenedor de Indicadores */
.indicators-row-container {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  width: 100%;
  box-sizing: border-box;
}

/* Badges e indicadores */
.custom-indicator-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
}

/* Botón Añadir Animal en la fila de indicadores */
.custom-indicator-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s ease, transform 0.1s ease;
}

.custom-indicator-btn:active {
  transform: scale(0.96);
  opacity: 0.9;
}

/* Colores correspondientes a la maqueta */
.badge-blue {
  background-color: #2563eb;
  color: white;
}

.badge-green {
  background-color: #107c41;
  color: white;
}

.badge-yellow {
  background-color: #f59e0b;
  color: #451a03;
}

.badge-cyan {
  background-color: #06b6d4;
  color: white;
}

.badge-red {
  background-color: #ef4444;
  color: white;
}

.btn-add-animal {
  background-color: #2563eb;
  color: white;
  box-shadow: 0 4px 6px rgba(37, 99, 235, 0.15);
}

.btn-camera-prominent {
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 0 16px;
  font-size: 0.8rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
  transition: background-color 0.2s ease, transform 0.1s ease;
  height: 45px; /* Aligns nicely with the input height */
}

.btn-camera-prominent:active {
  background-color: #1d4ed8;
  transform: scale(0.96);
}

/* Scrollable containers for Section IV */
.scrollable-animals-container {
  max-height: 480px;
  overflow-y: auto;
  border-bottom: 1px solid #e2e8f0;
}

.scrollable-animals-container-mobile {
  max-height: 520px;
  overflow-y: auto;
  border-bottom: 1px solid #e2e8f0;
}

/* Elegant scrollbar styling for premium UI */
.scrollable-animals-container::-webkit-scrollbar,
.scrollable-animals-container-mobile::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.scrollable-animals-container::-webkit-scrollbar-track,
.scrollable-animals-container-mobile::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 4px;
}

.scrollable-animals-container::-webkit-scrollbar-thumb,
.scrollable-animals-container-mobile::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.scrollable-animals-container::-webkit-scrollbar-thumb:hover,
.scrollable-animals-container-mobile::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* ── Lectura Table: Professional layout for doctors ── */
.lectura-table-header {
  display: flex;
  align-items: center;
  gap: 0;
  background: #1e293b;
  color: #f8fafc;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 10px 14px;
  position: sticky;
  top: 0;
  z-index: 5;
}

.lectura-table-row {
  border-bottom: 1.5px solid #e2e8f0;
  background: #ffffff;
  padding: 10px 8px;
  transition: background-color 0.15s ease;
}

.lectura-row-zebra {
  background: #f8fafc;
}

.lectura-row-positivo {
  background: #fef2f2 !important;
  border-left: 4px solid #ef4444;
}
.lectura-row-sospechoso {
  background: #fffbeb !important;
  border-left: 4px solid #f59e0b;
}
.lectura-row-negativo {
  border-left: 4px solid #10b981;
}

.lectura-row-agregado {
  background: #fff9c4 !important;
  border-left: 4px solid #fdd835;
}

/* Línea 1: # + Arete + Cámara */
.lectura-line1 {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 6px;
}

/* Línea 2: Resultado + Obs + Eliminar */
.lectura-line2 {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-left: 0;
}

.lectura-num-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 26px;
  height: 26px;
  border-radius: 8px;
  background: #e2e8f0;
  color: #334155;
  font-size: 0.75rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  flex-shrink: 0;
}

.lectura-input-arete {
  font-size: 0.82rem !important;
  padding: 6px 8px !important;
  border-radius: 8px !important;
  border: 1.5px solid #cbd5e1 !important;
  background: #f8fafc !important;
  font-weight: 600;
  letter-spacing: 0.5px;
}
.lectura-input-arete:focus {
  border-color: #2563eb !important;
  background: #fff !important;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12) !important;
}

.lectura-btn-scan {
  border-radius: 6px !important;
  padding: 0 !important;
  font-size: 0.75rem !important;
  line-height: 1 !important;
  height: 28px;
  width: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.lectura-select-result {
  font-size: 0.72rem !important;
  padding: 0 20px 0 6px !important;
  height: 30px !important;
  min-width: 85px;
  max-width: 110px;
  width: auto;
  border-radius: 6px !important;
  border: 1.5px solid #cbd5e1 !important;
  flex: 0 0 auto;
}

.lectura-input-obs {
  font-size: 0.75rem !important;
  padding: 5px 8px !important;
  border-radius: 6px !important;
  border: 1px solid #e2e8f0 !important;
  background: #f8fafc !important;
  color: #64748b;
  min-width: 80px;
  flex: 1 1 auto;
}
.lectura-input-obs:focus {
  background: #fff !important;
  border-color: #94a3b8 !important;
}

.lectura-btn-delete {
  border-radius: 6px !important;
  padding: 0 !important;
  font-size: 0.75rem !important;
  line-height: 1 !important;
  height: 28px;                  /* Alto del botón */
  width: 28px;                   /* Ancho exacto del botón (mismo que el alto) */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.lectura-table-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 10px 14px;
  background: #f1f5f9;
  border-top: 1.5px solid #e2e8f0;
  font-size: 0.75rem;
  font-weight: 700;
  color: #334155;
}
</style>

