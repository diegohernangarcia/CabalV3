<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Panel Principal y Tablero de Operaciones
 * Entidad 782 - Cabal
 */

// Intentar consultar métricas de la base de datos de manera segura y resiliente
$db_online = false;
$stat_beneficiarios = 0;
$stat_movimientos = 0;
$stat_tarjetas = 0;
$stat_lote = 0;

try {
    $pdo_test = new PDO(
        'mysql:host=localhost;dbname=cabal;charset=utf8',
        'pucho',
        '78900',
        array(
            PDO::ATTR_TIMEOUT => 2,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );
    $stat_beneficiarios = (int)$pdo_test->query("SELECT COUNT(*) FROM beneficiario")->fetchColumn();
    $stat_movimientos   = (int)$pdo_test->query("SELECT COUNT(*) FROM movimientos")->fetchColumn();
    $stat_tarjetas      = (int)$pdo_test->query("SELECT COUNT(*) FROM MasterTarjetas")->fetchColumn();
    $stat_lote          = (int)$pdo_test->query("SELECT COUNT(*) FROM acreditar2")->fetchColumn();
    $db_online = true;
} catch (Exception $e) {
    $db_online = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CABAL FSR — Tablero de Operaciones | SeNAF Río Negro</title>
    <meta name="description" content="Sistema de gestión, altas y conciliación de tarjetas sociales Cabal - Familia Rionegrina Solidaria">
    <link rel="icon" href="img/favicon.png">
    <!-- Hojas de Estilo del Nuevo Tablero -->
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Gestor de Modo Oscuro / Claro -->
    <script src="js/theme.js"></script>
</head>
<body>

<!-- =======================================================================
     BARRA DE NAVEGACIÓN INSTITUCIONAL
     ======================================================================= -->
<header class="top-nav">
    <div class="top-nav-inner">
        <a href="index.php" class="brand-group" id="brand-link">
            <div class="brand-logo-wrap">
                <img src="img/cabal.png" alt="Logo Cabal" onerror="this.src='img/favicon.png'">
            </div>
            <div class="brand-text">
                <h1>
                    CABAL FSR
                    <span class="brand-badge">Entidad 782</span>
                </h1>
                <p>Familia Rionegrina Solidaria • SeNAF Río Negro</p>
            </div>
        </a>

        <div class="nav-actions">
            <button type="button" class="theme-toggle-btn" id="btn-theme-toggle" title="Cambiar a Modo Claro" aria-label="Cambiar a Modo Claro">
                <svg class="sun-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
                <svg class="moon-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <span class="theme-toggle-label">Cambiar a Modo Claro</span>
            </button>

            <div class="system-pill" id="status-pill">
                <span class="status-dot <?php echo $db_online ? '' : 'offline'; ?>"></span>
                <span><?php echo $db_online ? 'Base de Datos Conectada' : 'BD Desconectada'; ?></span>
            </div>

            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer" class="btn-nav-action primary" id="btn-portal-cabal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                Portal Cabal
            </a>

            <a href="ayuda.html" target="_blank" class="btn-nav-action" id="btn-ayuda">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Guía Operativa
            </a>

            <a href="docs/ARQUITECTURA.md" target="_blank" class="btn-nav-action" id="btn-docs">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                Docs
            </a>
        </div>
    </div>
</header>

<!-- =======================================================================
     CONTENEDOR PRINCIPAL
     ======================================================================= -->
<main class="dashboard-container">

    <!-- HERO & BUSCADOR RÁPIDO DIRECTO -->
    <section class="dashboard-hero">
        <div class="hero-content">
            <h2>Centro de Operaciones y Conciliación Bancaria</h2>
            <p>
                Administración de padrón de beneficiarios, generación de lotes de acreditación mensual <b>NOVCAR.782</b>, altas de tarjetas <b>ALTAUSUCBPRE</b> y auditoría de consumos.
            </p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="AcreditarSaldos2026.php" class="btn-nav-action primary" style="padding: 10px 18px; font-size: 0.9rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <polyline points="19 12 12 19 5 12"></polyline>
                    </svg>
                    Acreditar Saldos 2026
                </a>
                <a href="GenerarCargaCredito2026.php" class="btn-nav-action" style="padding: 10px 18px; font-size: 0.9rem;">
                    Generar Lote NOVCAR
                </a>
            </div>
        </div>

        <!-- BUSCADOR RÁPIDO DE BENEFICIARIOS POR DNI -->
        <div class="hero-quick-search">
            <label for="dni-quick-search">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Consulta Rápida de Beneficiario
            </label>
            <form action="ConsultaBenefiario2.php" method="POST" class="search-form-inline" id="quick-search-form">
                <div class="search-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input 
                        type="text" 
                        name="dni" 
                        id="dni-quick-search" 
                        placeholder="Ingresar número de DNI (7 u 8 dígitos)..." 
                        required 
                        minlength="7" 
                        maxlength="8" 
                        pattern="[0-9]{7,8}"
                    >
                </div>
                <button type="submit" class="btn-search" id="btn-quick-search">
                    Consultar
                </button>
            </form>
            <small style="display: block; margin-top: 8px; color: #94a3b8; font-size: 0.75rem;">
                Muestra cuenta, número de tarjeta enmascarado y domicilio oficial.
            </small>
        </div>
    </section>

    <!-- TARJETAS DE MÉTRICAS KPI -->
    <section class="stats-grid" id="stats-section">
        <a href="ExportarExcelBeneficiarios.php" class="stat-card" id="card-stat-beneficiarios" title="Clic para exportar padrón">
            <div class="stat-info">
                <div class="stat-label">Beneficiarios Activos</div>
                <div class="stat-value"><?php echo number_format($stat_beneficiarios, 0, ',', '.'); ?></div>
                <div class="stat-desc">Titulares registrados en padrón</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
        </a>

        <a href="reporte_tarjetas.php" class="stat-card emerald" id="card-stat-tarjetas" title="Ver catálogo de plásticos">
            <div class="stat-info">
                <div class="stat-label">Plásticos Emitidos</div>
                <div class="stat-value"><?php echo number_format($stat_tarjetas, 0, ',', '.'); ?></div>
                <div class="stat-desc">Tarjetas en maestro Cabal (MAESPLA)</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
            </div>
        </a>

        <a href="ReporteMovimientos.php" class="stat-card purple" id="card-stat-movimientos" title="Ver liquidaciones mensuales">
            <div class="stat-info">
                <div class="stat-label">Movimientos Registrados</div>
                <div class="stat-value"><?php echo number_format($stat_movimientos, 0, ',', '.'); ?></div>
                <div class="stat-desc">Transacciones y consumos conciliados</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </div>
        </a>

        <a href="GenerarCargaCredito2026.php" class="stat-card amber" id="card-stat-lote" title="Revisar lote de acreditación en tabla acreditar2">
            <div class="stat-info">
                <div class="stat-label">Lote Acreditación Actual</div>
                <div class="stat-value"><?php echo number_format($stat_lote, 0, ',', '.'); ?></div>
                <div class="stat-desc">Registros pendientes en acreditar2</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
            </div>
        </a>
    </section>

    <!-- FLUJO OPERATIVO MENSUAL (WORKFLOW STEPPER) -->
    <section class="workflow-section">
        <div class="section-header">
            <div>
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-600);">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Circuito Mensual de Intercambio Cabal
                </h3>
                <p>Guía secuencial paso a paso para el procesamiento de lotes con el banco</p>
            </div>
            <a href="ayuda.html" target="_blank" style="font-size: 0.82rem; font-weight: 600; color: var(--primary-600); text-decoration: none;">
                Ver manual completo &rarr;
            </a>
        </div>

        <div class="workflow-steps-grid">
            <div class="step-card">
                <div class="step-num-badge">1</div>
                <h4>Altas de Tarjetas</h4>
                <p>Carga de planilla Excel y emisión del lote de 128 bytes para Cabal.</p>
                <div class="step-links">
                    <a href="SubirArchivo.html" class="step-link-item">&bull; 1. Subir Altas.xlsx</a>
                    <a href="GenerarNovedadAltas.php" class="step-link-item">&bull; 2. Generar ALTAUSUCBPRE</a>
                    <a href="ProcesaNovedadesAltas.php" class="step-link-item">&bull; 3. Procesar MovAltas (Resp.)</a>
                </div>
            </div>

            <div class="step-card">
                <div class="step-num-badge">2</div>
                <h4>Acreditación de Saldo</h4>
                <p>Procesamiento de montos, verificación y generación del archivo NOVCAR.</p>
                <div class="step-links">
                    <a href="SubirArchivo.html" class="step-link-item">&bull; 1. Subir Acreditaciones.xlsx</a>
                    <!-- <a href="AcreditarSaldos2026.php" class="step-link-item">&bull; 2. Procesar Excel 2026</a> -->
                    <a href="GenerarCargaCredito2026.php" class="step-link-item">&bull; 2. Generar NOVCAR.782</a>
                    <a href="VerificarNovcar.php" class="step-link-item">&bull; 3. Verificar Lote NOVCAR</a>
                    <a href="ProcesarCarga.php" class="step-link-item">&bull; 4. Verificar Respuesta Carga</a>
                </div>
            </div>

            <div class="step-card">
                <div class="step-num-badge">3</div>
                <h4>Respuestas y Débitos</h4>
                <p>Carga de confirmaciones bancarias y descarga de fondos remanentes.</p>
                <div class="step-links">
                    <a href="SubirArchivo.html" class="step-link-item">&bull; 1. Subir Respuesta Cargas</a>
                    <a href="ProcesarCarga.php" class="step-link-item">&bull; 2. Procesar RespuestaCargas.txt</a>
                    <a href="cereo_upload.php" class="step-link-item">&bull; 3. Generar NOVTRAN.782</a>
                </div>
            </div>

            <div class="step-card">
                <div class="step-num-badge">4</div>
                <h4>Cierre y Movimientos</h4>
                <p>Liquidación mensual de consumos al cierre de cada mes.</p>
                <div class="step-links">
                    <a href="ProcesarMovimientos.html" class="step-link-item">&bull; 1. Procesar Movimientos.txt</a>
                    <a href="ReporteMovimientos.php" class="step-link-item">&bull; 2. Reporte Mensual General</a>
                    <a href="ReporteMovimientosPorNombre.php" class="step-link-item">&bull; 3. Reporte por Beneficiario</a>
                </div>
            </div>
        </div>
    </section>

    <!-- GRILLA BENTO DE MÓDULOS -->
    <section class="modules-grid" id="modules-section">

        <!-- MÓDULO 1: BENEFICIARIOS -->
        <article class="module-box" id="mod-beneficiarios">
            <div class="module-box-header">
                <div class="module-title-group">
                    <div class="module-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div>
                        <h4>Módulo Beneficiarios</h4>
                        <p>Padrón de titulares y novedades de alta</p>
                    </div>
                </div>
                <span class="module-tag">Padrón</span>
            </div>
            <div class="module-box-body">
                <a href="SubirArchivo.html" class="action-row">
                    <div class="action-left">
                        <span class="action-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Cargar Archivo de Altas</h5>
                            <p>Sube planilla Altas.xlsx al servidor</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="CargaAltasDeExcelaBD.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Procesar Excel de Altas</h5>
                            <p>Vuelca los datos del Excel a la tabla altas</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="GenerarNovedadAltas.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Generar ALTAUSUCBPRE.782</h5>
                            <p>Genera el lote fijo de 128 bytes por registro</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="V2/Altas.php" class="action-row featured">
                    <div class="action-left">
                        <span class="action-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Conversor Altas V2 (Descarga Directa)</h5>
                            <p>Genera y descarga .782 con CRLF directo sin Windows</p>
                        </div>
                    </div>
                    <span class="badge-tag accent">Recomendado</span>
                </a>

                <a href="ProcesaNovedadesAltas.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Procesar MovAltas.txt (Devolución)</h5>
                            <p>Asigna cuentas y números de plástico recibidos de Cabal</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="ExportarExcelBeneficiarios.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Exportar Padrón a Excel</h5>
                            <p>Descarga la lista completa con tarjetas enmascaradas</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>
            </div>
        </article>

        <!-- MÓDULO 2: ACREDITACIONES DE SALDO -->
        <article class="module-box" id="mod-acreditaciones">
            <div class="module-box-header">
                <div class="module-title-group">
                    <div class="module-icon-wrap" style="background: var(--emerald-50); color: var(--emerald-700);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <h4>Acreditaciones de Saldo</h4>
                        <p>Cargas de crédito y generación de NOVCAR</p>
                    </div>
                </div>
                <span class="module-tag" style="background: var(--emerald-50); color: var(--emerald-700);">Crédito</span>
            </div>
            <div class="module-box-body">
                <a href="SubirArchivo.html" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--emerald-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Cargar Archivo de Acreditaciones</h5>
                            <p>Sube Acreditaciones.xlsx provisto por Suministros</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <!-- Oculto a solicitud: Procesar Excel (Versión 2026)
                <a href="AcreditarSaldos2026.php" class="action-row featured" style="display: none;">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--emerald-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Procesar Excel (Versión 2026)</h5>
                            <p>Cruza CUIL, calcula montos y carga en acreditar2</p>
                        </div>
                    </div>
                    <span class="badge-tag success">Activo 2026</span>
                </a>
                -->

                <a href="GenerarCargaCredito2026.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--emerald-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Generar NOVCAR.782 (2026)</h5>
                            <p>Escribe el lote bancario de carga en Novcar/novcar.782</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="VerificarNovcar.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--emerald-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Verificar Lote NOVCAR</h5>
                            <p>Auditoría visual de posiciones y montos previo al envío</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="SubirArchivo.html" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--emerald-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Subir Respuesta de Cargas</h5>
                            <p>Carga el archivo 782_RCAR devuelto por el banco</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="ProcesarCarga.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--emerald-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Verificar Respuesta Carga</h5>
                            <p>Auditoría y conciliación de devoluciones bancarias (RespuestaCargas.txt)</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>
            </div>
        </article>

        <!-- MÓDULO 3: DESCARGAS DE SALDO (DÉBITO) -->
        <article class="module-box" id="mod-descargas">
            <div class="module-box-header">
                <div class="module-title-group">
                    <div class="module-icon-wrap" style="background: var(--amber-50); color: var(--amber-600);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="19" x2="12" y2="5"></line>
                            <polyline points="5 12 12 19 19 12"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h4>Descarga de Saldos</h4>
                        <p>Débito de fondos y generación de NOVTRAN</p>
                    </div>
                </div>
                <span class="module-tag" style="background: var(--amber-50); color: var(--amber-600);">Débito</span>
            </div>
            <div class="module-box-body">
                <a href="cereo_upload.php" class="action-row featured">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--amber-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Generador Oficial NOVTRAN.782</h5>
                            <p>Procesa CSV de débitos/cargas con descarga directa</p>
                        </div>
                    </div>
                    <span class="badge-tag accent">Estándar Cabal</span>
                </a>

                <a href="ProcesarDescargaSaldos.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--amber-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Procesar DescargaSaldos.xlsx</h5>
                            <p>Procesa planilla de débitos desde la carpeta archivos</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="generar_novdes.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--amber-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Generador NOVDES Alternativo</h5>
                            <p>Generación alternativa de novedades de descarga</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>
            </div>
        </article>

        <!-- MÓDULO 4: MOVIMIENTOS & AUDITORÍA -->
        <article class="module-box" id="mod-movimientos">
            <div class="module-box-header">
                <div class="module-title-group">
                    <div class="module-icon-wrap" style="background: var(--purple-50); color: var(--purple-600);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <div>
                        <h4>Movimientos & Auditoría</h4>
                        <p>Consumos mensuales y conciliación de cuentas</p>
                    </div>
                </div>
                <span class="module-tag" style="background: var(--purple-50); color: var(--purple-600);">Auditoría</span>
            </div>
            <div class="module-box-body">
                <a href="ProcesarMovimientos.html" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--purple-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Procesar Movimientos.txt</h5>
                            <p>Importa liquidación mensual de Cabal a la base de datos</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="ReporteMovimientos.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--purple-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Reporte Mensual Consolidado</h5>
                            <p>Consulta de movimientos agrupados por mes y año</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="ReporteMovimientosPorNombre.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--purple-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Reporte por Beneficiario</h5>
                            <p>Historial individual de compras y extracciones</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="ConsultarBeneficiario.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: var(--purple-600);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Ficha Individual por DNI</h5>
                            <p>Búsqueda y visualización de beneficiario para impresión</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>
            </div>
        </article>

        <!-- MÓDULO 5: MAESTRO DE PLÁSTICOS -->
        <article class="module-box" id="mod-plasticos">
            <div class="module-box-header">
                <div class="module-title-group">
                    <div class="module-icon-wrap" style="background: #e0f2fe; color: #0369a1;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <h4>Maestro de Plásticos</h4>
                        <p>Inventario de plásticos emitidos y vencimientos</p>
                    </div>
                </div>
                <span class="module-tag" style="background: #e0f2fe; color: #0369a1;">MAESPLA</span>
            </div>
            <div class="module-box-body">
                <a href="ProcesarMaestroPlasticosArchivo.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: #0284c7;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Procesar Archivo MAESPLA.txt</h5>
                            <p>Carga el maestro de tarjetas emitidas a la base de datos</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="reporte_tarjetas.php" class="action-row featured">
                    <div class="action-left">
                        <span class="action-icon" style="color: #0284c7;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Reporte Master Tarjetas</h5>
                            <p>Filtros por archivo, nombre y exportación Excel</p>
                        </div>
                    </div>
                    <span class="badge-tag accent">Reporte Pro</span>
                </a>

                <a href="ReporteAltaBeneficiarios.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: #0284c7;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Reporte Beneficiarios General</h5>
                            <p>Resumen tabular de altas y estado de registros</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>
            </div>
        </article>

        <!-- MÓDULO 6: HERRAMIENTAS & ACCESOS DIRECTOS -->
        <article class="module-box" id="mod-herramientas">
            <div class="module-box-header">
                <div class="module-title-group">
                    <div class="module-icon-wrap" style="background: #f1f5f9; color: #475569;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4>Herramientas & Utilidades</h4>
                        <p>Gestión de archivos y accesos directos</p>
                    </div>
                </div>
                <span class="module-tag" style="background: #f1f5f9; color: #475569;">Sistema</span>
            </div>
            <div class="module-box-body">
                <a href="SubirArchivo.html" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: #475569;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Subida General de Archivos</h5>
                            <p>Gestor manual de archivos de entrada</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="index_menu_original.php" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: #475569;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Menú Clásico Desplegable</h5>
                            <p>Acceso a la vista anterior con barra de menús</p>
                        </div>
                    </div>
                    <span class="badge-tag">Histórico</span>
                </a>

                <a href="ayuda.html" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: #475569;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Manual Operativo Cabal</h5>
                            <p>Procedimiento de intercambio y generación de lotes</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>

                <a href="docs/PENDIENTES_Y_BACKLOG.md" class="action-row">
                    <div class="action-left">
                        <span class="action-icon" style="color: #475569;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </span>
                        <div class="action-texts">
                            <h5>Auditoría & Backlog de Desarrollo</h5>
                            <p>Informe técnico de seguridad y roadmap del sistema</p>
                        </div>
                    </div>
                    <span class="action-arrow">&rarr;</span>
                </a>
            </div>
        </article>
    </section>

</main>

<!-- =======================================================================
     FOOTER INSTITUCIONAL
     ======================================================================= -->
<footer class="footer-main">
    <div class="footer-inner">
        <div>
            <strong>Sistema CABAL FSR</strong> — Secretaría de Niñez, Adolescencia y Familia (SeNAF)
            <div style="font-size: 0.75rem; margin-top: 4px; color: #64748b;">
                Gobierno de la Provincia de Río Negro • Entidad Financiera 782 • Sucursal 000
            </div>
        </div>
        <div class="footer-links">
            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer">Plataforma Cabal</a>
            <a href="ayuda.html" target="_blank">Manual de Usuario</a>
            <a href="docs/ARQUITECTURA.md" target="_blank">Arquitectura</a>
            <a href="GEMINI.md" target="_blank">Normas Técnicas</a>
        </div>
    </div>
</footer>

</body>
</html>