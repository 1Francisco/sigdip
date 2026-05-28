<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dictamen SENASICA - {{ empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-') ? 'Borrador sin Folio' : $inspeccion->folio }}</title>
    <style>
        @page { 
            margin: 0.3cm 0.5cm 4.8cm 0.5cm; 
        }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #000; font-size: 7px; line-height: 1; }
        
        #footer { position: fixed; bottom: -4.7cm; left: 0; right: 0; text-align: center; font-size: 6px; color: #555; }
        #signatures-footer { position: fixed; bottom: -4.5cm; left: 0; right: 0; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .logo-box { width: 25%; text-align: center; vertical-align: middle; }
        .title-box { width: 50%; text-align: center; font-weight: bold; }
        
        .main-title { font-size: 10px; margin-bottom: 1px; }
        .sub-title { font-size: 8px; font-weight: normal; }
        
        .folio-box { border: 1px solid #000; padding: 2px; text-align: center; width: 100px; float: right; margin-bottom: 2px; }
        .folio-label { font-size: 7px; display: block; font-weight: bold; }
        .folio-number { font-size: 13px; color: #d32f2f; font-weight: bold; }

        .section-header { background-color: #d1e9f9; border: 1px solid #000; border-bottom: none; padding: 1px 4px; font-weight: bold; font-size: 8px; margin-top: 2px; }
        
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data-table td { border: 1px solid #000; padding: 1px 3px; vertical-align: top; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; height: 18px; }
        .field-label { font-size: 5px; color: #444; display: block; text-transform: uppercase; font-weight: bold; }
        .field-value { font-size: 8px; font-weight: bold; display: block; margin-top: 1px; }

        .section-iii { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .section-iii td { border: 1px solid #000; vertical-align: top; padding: 0; }
        
        .inner-cell { padding: 1px 2px; }
        .bg-grey { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        
        .grid-results { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .grid-results th { border: 1px solid #000; background-color: #e3f2fd; padding: 1px; font-size: 5.5px; }
        .grid-results td { border: 1px solid #000; padding: 1px; text-align: center; font-size: 7px; height: 12px; }

        .signature-section { margin-top: 5px; width: 100%; }
        .signature-box { width: 45%; text-align: center; float: left; }
        .qr-box { width: 45%; text-align: right; float: right; }
        
        .signature-line { border-top: 1px solid #000; width: 180px; margin: 25px auto 2px auto; padding-top: 2px; font-size: 6px; font-weight: bold; }
        .disclaimer { font-size: 5.5px; color: #777; margin-top: 5px; text-align: justify; line-height: 1; clear: both; }

        .watermark { position: fixed; top: 40%; left: 25%; font-size: 80px; color: rgba(0,0,0,0.05); transform: rotate(-45deg); z-index: -1000; font-weight: bold; }
    </style>
</head>
<body>

    <div class="watermark">ORIGINAL</div>

    <div id="footer">
        DICTAMEN DE PRUEBA DE TUBERCULINA - SIGDIP v1.0 | Página <span class="page-number"></span> de <span class="page-count"></span> | ID: {{ $inspeccion->id }}
    </div>

    <div id="signatures-footer">
    <table style="width: 140px; float: right; margin-top: 10px; border-collapse: collapse; text-align: center; border: 1px solid #000; font-family: sans-serif;">
        <tr>
            <td colspan="3" style="font-size: 7px; font-weight: bold; padding: 3px; border-bottom: 1px solid #000; background-color: #f2f2f2;">
                LA PRESENTE PRUEBA EXPIRA
            </td>
        </tr>
        <tr>
            <td style="width: 33%; font-size: 10px; font-weight: bold; padding: 4px; border-right: 1px solid #000; border-bottom: 1px solid #000;">
                {{ $inspeccion->vigencia_fecha ? \Carbon\Carbon::parse($inspeccion->vigencia_fecha)->format('d') : '&nbsp;' }}
            </td>
            <td style="width: 33%; font-size: 10px; font-weight: bold; padding: 4px; border-right: 1px solid #000; border-bottom: 1px solid #000;">
                {{ $inspeccion->vigencia_fecha ? \Carbon\Carbon::parse($inspeccion->vigencia_fecha)->format('m') : '&nbsp;' }}
            </td>
            <td style="width: 34%; font-size: 10px; font-weight: bold; padding: 4px; border-bottom: 1px solid #000;">
                {{ $inspeccion->vigencia_fecha ? \Carbon\Carbon::parse($inspeccion->vigencia_fecha)->format('Y') : '&nbsp;' }}
            </td>
        </tr>
        <tr>
            <td style="font-size: 6px; border-right: 1px solid #000;">DÍA</td>
            <td style="font-size: 6px; border-right: 1px solid #000;">MES</td>
            <td style="font-size: 6px;">AÑO</td>
        </tr>
    </table>
    <div style="clear: both;"></div>
    <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
        <tr>
            <!-- Columna Izquierda -->
            <td style="width: 35%; vertical-align: bottom; text-align: left; padding-right: 5px;">
                <div style="height: 15px; border-bottom: 1px solid #000;"></div>
                <div style="font-size: 6px; margin-top: 2px;">NOMBRE DEL MVZ RESPONSABLE AUTORIZADO</div>
                
                <div style="border-top: 1px solid #000; margin-top: 35px; text-align: center; font-size: 6px;">FIRMA</div>
                
                <table style="width: 100%; margin-top: 10px;">
                    <tr>
                        <td style="width: 25%; font-size: 6px;">CLAVE</td>
                        <td style="width: 75%; border-bottom: 1px solid #000; text-align: center; font-weight: bold; font-size: 9px;">&nbsp;</td>
                    </tr>
                </table>
                <table style="width: 100%; margin-top: 4px;">
                    <tr>
                        <td style="width: 25%; font-size: 6px;">VIGENCIA</td>
                        <td style="width: 75%;">
                            <table style="width: 100%; text-align: center; font-size: 8px;">
                                <tr>
                                    <td style="border-bottom: 1px solid #000; width: 33%;">&nbsp;</td>
                                    <td style="border-bottom: 1px solid #000; width: 33%;">&nbsp;</td>
                                    <td style="border-bottom: 1px solid #000; width: 34%;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 5px;">DÍA</td>
                                    <td style="font-size: 5px;">MES</td>
                                    <td style="font-size: 5px;">AÑO</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            
            <!-- Columna Central (Sello) -->
            <td style="width: 30%; vertical-align: bottom; text-align: center;">
                <div style="height: 50px;"></div>
                <div style="font-size: 6px; font-weight: bold; text-align: center;">
                    SELLO Y FIRMA ORIGINAL DEL MVZ<br>RESPONSABLE AUTORIZADO
                </div>
            </td>
            
            <!-- Columna Derecha -->
            <td style="width: 35%; vertical-align: bottom; text-align: right; padding-left: 5px;">
                <table style="width: 100%; text-align: center;">
                    <tr>
                        <td style="width: 48%; padding: 0 2px;">
                            <!-- Línea de Nombre -->
                            <div style="height: 15px; border-bottom: 1px solid #000;"></div>
                            <div style="font-size: 6px; margin-top: 2px;">NOMBRE DEL MVZ OFICIAL</div>
                            
                            <!-- Línea de Firma -->
                            <div style="height: 25px; border-bottom: 1px solid #000;"></div>
                            <div style="font-size: 6px; margin-top: 2px;">FIRMA</div>
                        </td>
                        <td style="width: 4%;"></td>
                        <td style="width: 48%; padding: 0 2px;">
                            <!-- Línea de Nombre -->
                            <div style="height: 15px; border-bottom: 1px solid #000; position: relative;">
                                <div style="position: absolute; bottom: 2px; width: 100%; text-align: center;">&nbsp;</div>
                            </div>
                            <div style="font-size: 6px; margin-top: 2px;">NOMBRE DEL PROPIETARIO</div>
                            
                            <!-- Línea de Firma -->
                            <div style="height: 25px; border-bottom: 1px solid #000;"></div>
                            <div style="font-size: 6px; margin-top: 2px;">FIRMA</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 7px; font-weight: bold; margin-top: 15px;">
        HOJA COMPLEMENTARIA VALIDA SOLO CON LA PRESENTACIÓN DEL DICTAMEN QUE SEÑALA EL FOLIO ORIGINAL
    </div>

    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="color: #d32f2f; font-size: 7px; font-style: italic; vertical-align: bottom;">SEGUNDA COPIA COORDINACION ESTATAL DE CAMPAÑAS ZOOSANITARIAS</td>
            <td style="text-align: right; font-size: 7px; font-weight: bold; vertical-align: bottom;">ESTE DOCUMENTO PERDERÁ VALIDEZ OFICIAL, SI PRESENTA TACHADURAS O ENMENDADURAS<br><span style="font-size: 5px; color: #777; font-weight: normal;">FORMATO-TB-HC-DICT-V1-2023</span></td>
        </tr>
    </table>
    </div>

    @php
        // Preparar la estructura de páginas y paginación
        $allDetalles = $inspeccion->detalles->values();
        $total = $allDetalles->count();

        $pages = [];
        
        // Página 1: capacidad de 30 animales (15 filas de 2 columnas)
        $page1Rows = 15;
        $page1Limit = $page1Rows * 2;
        $page1Details = $allDetalles->slice(0, $page1Limit)->values();
        
        $pages[] = [
            'number' => 1,
            'rows' => $page1Rows,
            'details' => $page1Details,
            'left' => $page1Details->slice(0, $page1Rows)->values(),
            'right' => $page1Details->slice($page1Rows)->values(),
            'start_num' => 1,
        ];

        // Páginas complementarias siguientes: capacidad de 50 animales (25 filas de 2 columnas por página)
        $pageNextRows = 25;
        $pageNextLimit = $pageNextRows * 2;
        $currentIndex = $page1Limit;
        $pageNum = 2;

        while ($currentIndex < $total) {
            $pageDetails = $allDetalles->slice($currentIndex, $pageNextLimit)->values();
            $pages[] = [
                'number' => $pageNum,
                'rows' => $pageNextRows,
                'details' => $pageDetails,
                'left' => $pageDetails->slice(0, $pageNextRows)->values(),
                'right' => $pageDetails->slice($pageNextRows)->values(),
                'start_num' => $currentIndex + 1,
            ];
            
            $currentIndex += $pageNextLimit;
            $pageNum++;
        }
    @endphp

    @foreach($pages as $pageIdx => $page)
        @if($pageIdx > 0)
            <div style="page-break-before: always;"></div>
        @endif

        @if($page['number'] == 1)
            <!-- ================= HOJA 1 ================= -->
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 25%; text-align: center; vertical-align: top; padding: 5px;">
                        <div style="font-weight:bold; font-size:4px; border:1px solid #000; border-radius:50%; width:30px; height:30px; margin:0 auto; line-height:30px; color:#555;">ESCUDO</div>
                        <div style="font-size:6px; font-weight:bold; margin-top:5px;">SECRETARÍA DE<br>AGRICULTURA Y<br>DESARROLLO RURAL</div>
                    </td>
                    <td style="width: 75%; vertical-align: top; padding: 0;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 5px 0;">
                                    <div style="font-size:10px; font-weight:bold; letter-spacing:0.5px;">SERVICIO NACIONAL DE SANIDAD,<br>INOCUIDAD Y CALIDAD AGROALIMENTARIA</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 65%; text-align: right; vertical-align: bottom; padding-right: 10px; padding-bottom: 5px; border-right: 1px solid #000;">
                                    <div style="font-size:9px;">DIRECCIÓN GENERAL DE<br>SALUD ANIMAL</div>
                                </td>
                                <td style="width: 35%; text-align: center; vertical-align: middle; padding-bottom: 5px;">
                                    <div style="font-weight:bold; font-size:14px; letter-spacing:1px;">SENASICA</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: center; padding: 4px; font-size: 9px; font-weight: bold; letter-spacing:0.5px;">
                CAMPAÑA NACIONAL CONTRA LA TUBERCULOSIS BOVINA
            </div>
            <div style="text-align: center; padding: 4px; font-size: 8px; font-weight: bold;">
                DICTAMEN DE LA PRUEBA DE TUBERCULINA
            </div>

            <table style="width: 100%; margin-bottom: 5px; border-collapse: collapse;">
                <tr>
                    <td style="width: 55%;"></td>
                    <td style="width: 30%; text-align: right; vertical-align: bottom;">
                        <span style="font-weight: bold; font-size: 10px; margin-right: 5px;">FOLIO</span>
                        <div style="border: 1px solid #000; border-radius: 6px; padding: 2px 10px; display: inline-block; vertical-align: middle;">
                            <span style="font-weight: bold; font-size: 15px; color: #d32f2f; letter-spacing:1px;">{{ empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-') ? '' : $inspeccion->folio }}</span>
                        </div>
                    </td>
                    <td style="width: 15%; padding-left: 5px; vertical-align: bottom;">
                        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                            <tr><td style="font-size: 5px; font-weight: bold; text-align: center; border-bottom: 1px solid #000; padding: 2px;">PÁGINA Nº.</td></tr>
                            <tr><td style="font-size: 8px; font-weight: bold; text-align: center; padding: 3px;">1 &nbsp;&nbsp;&nbsp;&nbsp; DE &nbsp;&nbsp;&nbsp;&nbsp; {{ count($pages) }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="section-header">I DATOS DEL PROPIETARIO</div>
            <table class="data-table">
                <tr>
                    <td style="width: 25%"><span class="field-label">Apellido Paterno</span><span class="field-value">{{ $inspeccion->predio->productor->apellido_paterno }}</span></td>
                    <td style="width: 25%"><span class="field-label">Apellido Materno</span><span class="field-value">{{ $inspeccion->predio->productor->apellido_materno }}</span></td>
                    <td style="width: 25%"><span class="field-label">Nombre(s)</span><span class="field-value">{{ $inspeccion->predio->productor->nombre }}</span></td>
                    <td style="width: 25%"><span class="field-label">Teléfono</span><span class="field-value">{{ $inspeccion->predio->productor->telefono }}</span></td>
                </tr>
                <tr>
                    <td colspan="2"><span class="field-label">Domicilio</span><span class="field-value">{{ $inspeccion->predio->productor->domicilio }}</span></td>
                    <td><span class="field-label">Municipio</span><span class="field-value">{{ $inspeccion->predio->productor->municipio }}</span></td>
                    <td><span class="field-label">Localidad / Población</span><span class="field-value"></span></td>
                </tr>
                <tr>
                    <td colspan="2"><span class="field-label">Estado</span><span class="field-value">{{ $inspeccion->predio->productor->estado ?? 'NAYARIT' }}</span></td>
                    <td colspan="2"><span class="field-label">Correo Electrónico</span><span class="field-value">{{ $inspeccion->predio->productor->email ?? 'S/D' }}</span></td>
                </tr>
            </table>

            <div class="section-header">II UNIDAD DE PRODUCCIÓN (UPP) / PREDIO</div>
            <table class="data-table">
                <tr>
                    <td rowspan="2" style="width: 35%;"><span class="field-label">Nombre de la unidad de producción o predio</span><span class="field-value">{{ $inspeccion->predio->nombre_rancho }}</span></td>
                    <td colspan="2" style="width: 35%; padding: 0; text-align: center; border-bottom: 1px solid #000; height: 8px;">
                        <span style="font-size: 5px; font-weight: bold; display: block; padding: 1px;">UBICACIÓN GEOGRÁFICA (GRADOS DECIMALES)</span>
                    </td>
                    <td rowspan="2" style="width: 30%;"><span class="field-label">Clave de la Unidad de Producción (UPP) o PSG</span><span class="field-value">{{ $inspeccion->predio->clave_unidad_produccion }}</span></td>
                </tr>
                <tr>
                    <td style="width: 17.5%; border-top: none;"><span class="field-label">LATITUD</span><span class="field-value">{{ $inspeccion->predio->latitud }}</span></td>
                    <td style="width: 17.5%; border-top: none;"><span class="field-label">LONGITUD</span><span class="field-value">{{ $inspeccion->predio->longitud }}</span></td>
                </tr>
                <tr>
                    <td><span class="field-label">Domicilio</span><span class="field-value">{{ $inspeccion->predio->domicilio ?? 'DOMICILIO CONOCIDO' }}</span></td>
                    <td><span class="field-label">Municipio</span><span class="field-value">{{ $inspeccion->predio->municipio }}</span></td>
                    <td><span class="field-label">Localidad / Población</span><span class="field-value">{{ $inspeccion->predio->localidad }}</span></td>
                    <td><span class="field-label">Estado</span><span class="field-value">NAYARIT</span></td>
                </tr>
            </table>

            <div class="section-header">III DE LA PRUEBA</div>
            @php
                $tipoPruebaLabel = match ($inspeccion->tipo_prueba) {
                    'PCC', 'P.C.C.' => 'Prueba Cervical Comparativa (PCC)',
                    'PPC', 'P.P.C.' => 'Prueba de Pliegue Caudal (PPC)',
                    default => $inspeccion->tipo_prueba,
                };
            @endphp
            <table class="section-iii">
                <tr>
                    <td style="width: 25%;">
                        <div class="inner-cell bg-grey text-center" style="border-bottom:1px solid #000; padding-bottom:1px;">
                            <span class="field-label" style="display:inline; font-size:5px;">FECHA PRUEBA ANTERIOR (EN SU CASO)</span>
                            <table style="width:100%; text-align:center; margin-top:2px;">
                                <tr>
                                    <td style="border:none; font-size:10px; font-weight:bold;">{{ $inspeccion->fecha_prueba_anterior ? \Carbon\Carbon::parse($inspeccion->fecha_prueba_anterior)->format('d') : '--' }}</td>
                                    <td style="border:none; font-size:10px; font-weight:bold; border-left:1px solid #000;">{{ $inspeccion->fecha_prueba_anterior ? \Carbon\Carbon::parse($inspeccion->fecha_prueba_anterior)->format('m') : '--' }}</td>
                                    <td style="border:none; font-size:10px; font-weight:bold; border-left:1px solid #000;">{{ $inspeccion->fecha_prueba_anterior ? \Carbon\Carbon::parse($inspeccion->fecha_prueba_anterior)->format('Y') : '----' }}</td>
                                </tr>
                                <tr>
                                    <td style="border:none; font-size:5px; border-top:1px solid #000;">DIA</td>
                                    <td style="border:none; font-size:5px; border-top:1px solid #000; border-left:1px solid #000;">MES</td>
                                    <td style="border:none; font-size:5px; border-top:1px solid #000; border-left:1px solid #000;">AÑO</td>
                                </tr>
                            </table>
                        </div>
                        <div class="inner-cell bg-grey text-center" style="border-bottom:1px solid #000; padding:2px;">
                            <span class="field-label" style="display:block; font-size:6px;">DICTAMEN TB ANTERIOR</span>
                            <div style="text-align:left; font-size:8px; margin-top:2px;">
                                No. <span style="font-weight:bold; border-bottom:1px solid #000; padding:0 10px;">{{ $inspeccion->dictamen_anterior_no ?? '                ' }}</span>
                            </div>
                        </div>
                        <div class="inner-cell bg-grey" style="padding:2px;">
                            <span class="field-label" style="display:block; text-align:center; font-size:6px; border-bottom:1px solid #000; margin-bottom:2px;">MOTIVO DE LA PRESENTE PRUEBA</span>
                            <div style="font-size:6px; font-weight:bold; text-transform:uppercase; text-align:center;">{{ $inspeccion->motivo_prueba }}</div>
                        </div>
                    </td>
                    
                    <td style="width: 25%;">
                        <div class="inner-cell text-center" style="border-bottom:1px solid #000; height:35px; padding-top:2px;">
                            <span class="field-label" style="font-size:6px;">TIPO DE PRUEBA REALIZADA</span>
                            <span class="field-value" style="font-size:12px; margin-top:6px;">{{ $tipoPruebaLabel }}</span>
                        </div>
                        <div class="inner-cell" style="border-bottom:1px solid #000; padding:2px; height:45px;">
                            <span class="field-label" style="font-size:6px;">TOTAL DE HATO A<br>CONSTATAR O PARTIDA<br>A MOVILIZAR</span>
                            <div style="font-size:5px; text-align:left; margin-top:2px;">No. ANIMALES</div>
                            <div style="text-align:center; font-size:12px; font-weight:bold; border-bottom:1px solid #000; margin: 0 10px;">
                                {{ $total }}
                            </div>
                        </div>
                        <div class="inner-cell" style="padding:2px;">
                            <span class="field-label" style="font-size:6px;">FUNCIÓN ZOOTECNICA</span>
                            <table style="width:100%; font-size:6px; margin-top:2px;">
                                <tr><td style="border:none;">LECHE</td><td style="border:none; text-align:right;"><div style="width:10px; height:10px; border:1px solid #000; display:inline-block; text-align:center;">{{ $inspeccion->funcion_zootecnica == 'Leche' ? 'X' : '' }}</div></td></tr>
                                <tr><td style="border:none;">CARNE</td><td style="border:none; text-align:right;"><div style="width:10px; height:10px; border:1px solid #000; display:inline-block; text-align:center;">{{ $inspeccion->funcion_zootecnica == 'Carne' ? 'X' : '' }}</div></td></tr>
                                <tr><td style="border:none;">MIXTO</td><td style="border:none; text-align:right;"><div style="width:10px; height:10px; border:1px solid #000; display:inline-block; text-align:center;">{{ $inspeccion->funcion_zootecnica == 'Mixto' ? 'X' : '' }}</div></td></tr>
                            </table>
                        </div>
                    </td>

                    <td style="width: 25%;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr><td colspan="2" class="bg-grey text-center fw-bold" style="font-size:7px; border:none; border-bottom:1px solid #000; padding:4px; height: 15px; vertical-align: middle;">RESUMEN DE RESULTADOS</td></tr>
                            <tr style="height: 33px;">
                                <td class="" style="border:none; border-right:1px solid #000; border-bottom:1px solid #000; font-size:7px; padding:4px; text-align:center; vertical-align: middle;">NEGATIVOS</td>
                                <td class="text-center field-value" style="border:none; border-bottom:1px solid #000; font-size:11px; padding:4px; vertical-align: middle;">{{ $inspeccion->detalles->where('resultado_prueba', 'Negativo')->count() }}</td>
                            </tr>
                            <tr style="height: 33px;">
                                <td class="" style="border:none; border-right:1px solid #000; border-bottom:1px solid #000; font-size:7px; padding:4px; text-align:center; vertical-align: middle;">SOSPECHOSOS</td>
                                <td class="text-center field-value" style="border:none; border-bottom:1px solid #000; font-size:11px; padding:4px; vertical-align: middle;">{{ $inspeccion->detalles->where('resultado_prueba', 'Sospechoso')->count() }}</td>
                            </tr>
                            <tr style="height: 33px;">
                                <td class="" style="border:none; border-right:1px solid #000; border-bottom:1px solid #000; font-size:7px; padding:4px; text-align:center; vertical-align: middle;">POSITIVOS</td>
                                <td class="text-center field-value" style="border:none; border-bottom:1px solid #000; font-size:11px; padding:4px; vertical-align: middle;">{{ $inspeccion->detalles->where('resultado_prueba', 'Positivo')->count() }}</td>
                            </tr>
                            <tr style="height: 33px;">
                                <td class="" style="border:none; border-right:1px solid #000; font-size:7px; padding:4px; text-align:center; font-weight:bold; vertical-align: middle;">TOTAL</td>
                                <td class="text-center field-value" style="border:none; font-size:11px; padding:4px; vertical-align: middle;">{{ $total }}</td>
                            </tr>
                        </table>
                    </td>

                    <td style="width: 25%;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr style="height: 37px;">
                                <td class="bg-grey text-center" style="font-size:6px; border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:30%; padding:4px; vertical-align: middle;">INYECCIÓN</td>
                                <td class="text-center" style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:4px; vertical-align: middle;">
                                    <div style="font-size:9px; font-weight:bold;">{{ $inspeccion->fecha_inyeccion ? \Carbon\Carbon::parse($inspeccion->fecha_inyeccion)->format('d/m/Y') : '---' }}</div>
                                    <div style="font-size:5px;">FECHA</div>
                                </td>
                                <td class="text-center" style="border:none; border-bottom:1px solid #000; padding:4px; vertical-align: middle;">
                                    <div style="font-size:9px; font-weight:bold;">{{ $inspeccion->hora_inyeccion ?? '---' }}</div>
                                    <div style="font-size:5px;">HORA</div>
                                </td>
                            </tr>
                            <tr style="height: 37px;">
                                <td class="bg-grey text-center" style="font-size:6px; border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:4px; vertical-align: middle;">LECTURA</td>
                                <td class="text-center" style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:4px; vertical-align: middle;">
                                    <div style="font-size:9px; font-weight:bold;">{{ $inspeccion->fecha_lectura ? \Carbon\Carbon::parse($inspeccion->fecha_lectura)->format('d/m/Y') : '---' }}</div>
                                    <div style="font-size:5px;">FECHA</div>
                                </td>
                                <td class="text-center" style="border:none; border-bottom:1px solid #000; padding:4px; vertical-align: middle;">
                                    <div style="font-size:9px; font-weight:bold;">{{ $inspeccion->hora_lectura ?? '---' }}</div>
                                    <div style="font-size:5px;">HORA</div>
                                </td>
                            </tr>
                            <tr style="height: 37px;">
                                <td class="bg-grey text-center" style="font-size:6px; border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:4px; vertical-align: middle;">EXENCIÓN DE<br>PRUEBA</td>
                                <td colspan="2" style="border:none; border-bottom:1px solid #000; padding:4px; vertical-align: middle;">
                                    <div class="bg-grey text-center" style="font-size:6px; border-bottom:1px solid #000; margin:-4px -4px 4px -4px; padding:2px;">CONSTANCIA DE HATO LIBRE</div>
                                    <div style="font-size:6px; text-align: left; padding-left: 2px;">No. {{ $inspeccion->hato_libre_no }}</div>
                                    <div style="font-size:6px; margin-top:2px; text-align: left; padding-left: 2px;">FECHA {{ $inspeccion->hato_libre_fecha ? \Carbon\Carbon::parse($inspeccion->hato_libre_fecha)->format('d/m/Y') : '' }}</div>
                                </td>
                            </tr>
                            <tr style="height: 37px;">
                                <td class="bg-grey text-center" style="font-size:6px; border:none; border-right:1px solid #000; padding:4px; vertical-align: middle;">VIGENCIA</td>
                                <td colspan="2" style="border:none; padding:4px; vertical-align: middle;">
                                    <div class="bg-grey text-center" style="font-size:6px; border-bottom:1px solid #000; margin:-4px -4px 4px -4px; padding:2px;">LA PRESENTE PRUEBA EXPIRA</div>
                                        <table style="width:100%; text-align:center;">
                                        <tr>
                                            <td style="border:none; font-size:9px; font-weight:bold;">{{ $inspeccion->vigencia_fecha ? \Carbon\Carbon::parse($inspeccion->vigencia_fecha)->format('d') : '--' }}</td>
                                            <td style="border:none; font-size:9px; font-weight:bold; border-left:1px solid #000;">{{ $inspeccion->vigencia_fecha ? \Carbon\Carbon::parse($inspeccion->vigencia_fecha)->format('m') : '--' }}</td>
                                            <td style="border:none; font-size:9px; font-weight:bold; border-left:1px solid #000;">{{ $inspeccion->vigencia_fecha ? \Carbon\Carbon::parse($inspeccion->vigencia_fecha)->format('Y') : '----' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="border:none; font-size:5px; border-top:1px solid #000;">DIA</td>
                                            <td style="border:none; font-size:5px; border-top:1px solid #000; border-left:1px solid #000;">MES</td>
                                            <td style="border:none; font-size:5px; border-top:1px solid #000; border-left:1px solid #000;">AÑO</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="section-header" style="margin-top:5px;">IV RESULTADOS</div>
            <table class="grid-results">
                <thead>
                    <tr>
                        <th style="width:3%;">No.</th>
                        <th style="width:3%;">1</th>
                        <th style="width:16%;">IDENTIFICACIÓN OFICIAL/SINIIGA</th>
                        <th style="width:7%;">EDAD<br>(meses)</th>
                        <th style="width:9%;">RAZA</th>
                        <th style="width:5%;">SEXO</th>
                        <th style="width:5%;">FIERRO</th>
                        <th style="width:6%;">N/S/P/E</th>
                        <th style="width:3%;">No.</th>
                        <th style="width:3%;">1</th>
                        <th style="width:16%;">IDENTIFICACIÓN OFICIAL/SINIIGA</th>
                        <th style="width:7%;">EDAD<br>(meses)</th>
                        <th style="width:9%;">RAZA</th>
                        <th style="width:5%;">SEXO</th>
                        <th style="width:5%;">FIERRO</th>
                        <th style="width:6%;">N/S/P/E</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $left = $page['left'];
                        $right = $page['right'];
                        $maxRows = $page['rows'];
                        $perColumn = $page['rows'];
                        $start_num = $page['start_num'];
                    @endphp
                    @for($i = 0; $i < $maxRows; $i++)
                    <tr>
                        <!-- Columna Izquierda -->
                        @if(isset($left[$i]))
                            @php $det = $left[$i]; @endphp
                            <td>{{ $start_num + $i }}</td>
                            <td></td>
                            <td class="field-value" style="font-size:10px;">{{ $det->animal->numero_arete_siniiga }}</td>
                            <td>{{ $det->edad_meses }}</td>
                            <td>{{ $det->raza }}</td>
                            <td style="font-weight:bold;">{{ substr($det->sexo, 0, 1) }}</td>
                            <td>{{ $det->fierro ?? '---' }}</td>
                            <td style="font-weight:bold; color:{{ in_array($det->resultado_prueba, ['Negativo', 'Exento']) ? '#000' : 'red' }};">{{ in_array($det->resultado_prueba, ['Negativo', 'Positivo', 'Sospechoso', 'Exento']) ? substr($det->resultado_prueba, 0, 1) : '' }}</td>
                        @else
                            <td>{{ $start_num + $i }}</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        @endif

                        <!-- Columna Derecha -->
                        @if(isset($right[$i]))
                            @php $det = $right[$i]; @endphp
                            <td>{{ $start_num + $perColumn + $i }}</td>
                            <td></td>
                            <td class="field-value" style="font-size:10px;">{{ $det->animal->numero_arete_siniiga }}</td>
                            <td>{{ $det->edad_meses }}</td>
                            <td>{{ $det->raza }}</td>
                            <td style="font-weight:bold;">{{ substr($det->sexo, 0, 1) }}</td>
                            <td>{{ $det->fierro ?? '---' }}</td>
                            <td style="font-weight:bold; color:{{ in_array($det->resultado_prueba, ['Negativo', 'Exento']) ? '#000' : 'red' }};">{{ in_array($det->resultado_prueba, ['Negativo', 'Positivo', 'Sospechoso', 'Exento']) ? substr($det->resultado_prueba, 0, 1) : '' }}</td>
                        @else
                            <td>{{ $start_num + $perColumn + $i }}</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        @endif
                    </tr>
                    @endfor
                </tbody>
            </table>

            <div style="font-size:6px; margin-top:2px;">
                ( RI ) REIDENTIFICADO &nbsp;&nbsp;&nbsp;&nbsp; ( IN ) INCREMENTO NATURAL &nbsp;&nbsp;&nbsp;&nbsp; ( IC ) INCREMENTO POR COMPRA
            </div>

        @else
            <!-- ================= HOJAS COMPLEMENTARIAS (PÁGINA 2+) ================= -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
                <tr>
                    <td style="width: 20%; text-align: center; vertical-align: top; padding: 2px;">
                        <div style="font-weight:bold; font-size:3px; border:1px solid #000; border-radius:50%; width:20px; height:20px; margin:0 auto; line-height:20px; color:#555;">ESCUDO</div>
                        <div style="font-size:5px; font-weight:bold; margin-top:2px;">SECRETARÍA DE AGRICULTURA</div>
                    </td>
                    <td style="width: 80%; vertical-align: middle; text-align: center;">
                        <div style="font-size:8px; font-weight:bold;">SERVICIO NACIONAL DE SANIDAD, INOCUIDAD Y CALIDAD AGROALIMENTARIA</div>
                        <div style="font-size:7px; font-weight:bold; margin-top:2px; color: #555;">CAMPAÑA NACIONAL CONTRA LA TUBERCULOSIS BOVINA - HOJA COMPLEMENTARIA</div>
                    </td>
                </tr>
            </table>
            
            <table style="width: 100%; margin-bottom: 3px; border-collapse: collapse; border-bottom: 1px solid #000;">
                <tr>
                    <td style="width: 50%; font-size: 8px; font-weight: bold; padding: 2px 0;">
                        PROPIETARIO: <span style="font-weight: normal;">{{ $inspeccion->predio->productor->nombre }} {{ $inspeccion->predio->productor->apellido_paterno }} {{ $inspeccion->predio->productor->apellido_materno }}</span>
                    </td>
                    <td style="width: 25%; font-size: 8px; font-weight: bold; padding: 2px 0;">
                        UPP: <span style="font-weight: normal;">{{ $inspeccion->predio->clave_unidad_produccion }}</span>
                    </td>
                    <td style="width: 25%; text-align: right; vertical-align: middle; padding: 2px 0;">
                        <span style="font-weight: bold; font-size: 8px; margin-right: 5px;">FOLIO:</span>
                        <span style="font-weight: bold; font-size: 11px; color: #d32f2f; letter-spacing:0.5px;">{{ empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-') ? '' : $inspeccion->folio }}</span>
                    </td>
                </tr>
            </table>

            <div class="section-header" style="margin-top:2px;">IV RESULTADOS (HOJA COMPLEMENTARIA - PÁGINA {{ $page['number'] }} DE {{ count($pages) }})</div>
            <table class="grid-results" style="margin-top: 2px;">
                <thead>
                    <tr>
                        <th style="width:3%;">No.</th>
                        <th style="width:3%;">1</th>
                        <th style="width:16%;">IDENTIFICACIÓN OFICIAL/SINIIGA</th>
                        <th style="width:7%;">EDAD<br>(meses)</th>
                        <th style="width:9%;">RAZA</th>
                        <th style="width:5%;">SEXO</th>
                        <th style="width:5%;">FIERRO</th>
                        <th style="width:6%;">N/S/P/E</th>
                        <th style="width:3%;">No.</th>
                        <th style="width:3%;">1</th>
                        <th style="width:16%;">IDENTIFICACIÓN OFICIAL/SINIIGA</th>
                        <th style="width:7%;">EDAD<br>(meses)</th>
                        <th style="width:9%;">RAZA</th>
                        <th style="width:5%;">SEXO</th>
                        <th style="width:5%;">FIERRO</th>
                        <th style="width:6%;">N/S/P/E</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $left = $page['left'];
                        $right = $page['right'];
                        $maxRows = $page['rows'];
                        $perColumn = $page['rows'];
                        $start_num = $page['start_num'];
                    @endphp
                    @for($i = 0; $i < $maxRows; $i++)
                    <tr>
                        <!-- Columna Izquierda -->
                        @if(isset($left[$i]))
                            @php $det = $left[$i]; @endphp
                            <td>{{ $start_num + $i }}</td>
                            <td></td>
                            <td class="field-value" style="font-size:10px;">{{ $det->animal->numero_arete_siniiga }}</td>
                            <td>{{ $det->edad_meses }}</td>
                            <td>{{ $det->raza }}</td>
                            <td style="font-weight:bold;">{{ substr($det->sexo, 0, 1) }}</td>
                            <td>{{ $det->fierro ?? '---' }}</td>
                            <td style="font-weight:bold; color:{{ in_array($det->resultado_prueba, ['Negativo', 'Exento']) ? '#000' : 'red' }};">{{ in_array($det->resultado_prueba, ['Negativo', 'Positivo', 'Sospechoso', 'Exento']) ? substr($det->resultado_prueba, 0, 1) : '' }}</td>
                        @else
                            <td>{{ $start_num + $i }}</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        @endif

                        <!-- Columna Derecha -->
                        @if(isset($right[$i]))
                            @php $det = $right[$i]; @endphp
                            <td>{{ $start_num + $perColumn + $i }}</td>
                            <td></td>
                            <td class="field-value" style="font-size:10px;">{{ $det->animal->numero_arete_siniiga }}</td>
                            <td>{{ $det->edad_meses }}</td>
                            <td>{{ $det->raza }}</td>
                            <td style="font-weight:bold;">{{ substr($det->sexo, 0, 1) }}</td>
                            <td>{{ $det->fierro ?? '---' }}</td>
                            <td style="font-weight:bold; color:{{ in_array($det->resultado_prueba, ['Negativo', 'Exento']) ? '#000' : 'red' }};">{{ in_array($det->resultado_prueba, ['Negativo', 'Positivo', 'Sospechoso', 'Exento']) ? substr($det->resultado_prueba, 0, 1) : '' }}</td>
                        @else
                            <td>{{ $start_num + $perColumn + $i }}</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        @endif
                    </tr>
                    @endfor
                </tbody>
            </table>

            <div style="font-size:6px; margin-top:2px;">
                ( RI ) REIDENTIFICADO &nbsp;&nbsp;&nbsp;&nbsp; ( IN ) INCREMENTO NATURAL &nbsp;&nbsp;&nbsp;&nbsp; ( IC ) INCREMENTO POR COMPRA
            </div>
        @endif
    @endforeach

</body>
</html>
