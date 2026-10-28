<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .manual-section-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .manual-section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-light);
        }
        .manual-badge-num {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: #ffffff;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .manual-section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--navy-800);
            margin: 0;
        }
        .steps-list {
            padding-left: 20px;
            margin: 0;
        }
        .steps-list li {
            padding: 8px 0;
            line-height: 1.5;
            color: var(--text-main);
            font-size: 0.95rem;
        }
        .steps-list li strong {
            color: var(--navy-800);
        }
        .manual-img-wrap {
            margin-top: 16px;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border-light);
            max-width: 100%;
        }
        .manual-img-wrap img {
            max-width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body>

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
            <a href="index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>
            <a href="ayuda.html" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Guía Rápida
            </a>
            <a href="videos.html" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                </svg>
                Videos
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1000px; padding-top: 28px;">

    <!-- HERO HEADER -->
    <div class="modern-card" style="padding: 28px 32px; margin-bottom: 28px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
        <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0 0 8px 0; color: #ffffff;">
            📘 Manual Operativo y Flujo de Procesos Cabal FSR
        </h2>
        <p style="font-size: 0.92rem; color: #94a3b8; margin: 0;">
            Procedimientos detallados para la generación, transmisión, conciliación y carga de lotes bancarios bajo la Entidad 782.
        </p>
    </div>

    <!-- SECCIÓN 1: GENERAR ALTAS -->
    <div class="manual-section-card">
        <div class="manual-section-header">
            <span class="manual-badge-num">1</span>
            <h3 class="manual-section-title">Generar Altas Nuevas (ALTAUSUCBPRE.782)</h3>
        </div>
        <ol class="steps-list">
            <li>Subir el archivo <code>Altas.xlsx</code> desde el módulo de recepción.</li>
            <li>En el menú superior, acceder a <strong>Cargar Altas Excel</strong>.</li>
            <li>El archivo debe respetar las columnas requeridas: <em>Apellido, Nombre, DomicilioCalle, DomicilioNumero, DomicilioPiso, DomicilioDepartamento, Localidad, Provincia, Sexo, CodigoPostal, TelefonoCaracteristica, TelefonoNumero, EstadoCivil, FechaNacimiento, DocumentoTipo, DocumentoNumero, DocumentoProvincia, CUIT</em>.</li>
            <li>Se respetan estrictamente los 128 bytes por registro estipulados en el <strong>Anexo 1 - Novedades de Altas de Tarjetas Precargadas</strong> del manual Cabal.</li>
            <li>Ingresar a <strong>Generar ALTAUSUCBPRE</strong> y utilizar el botón de descarga directa para obtener el archivo con saltos de línea CRLF (Windows).</li>
            <li>Subir el lote a la plataforma Cabal como <code>ALTAUSUCBPRE.782</code>.</li>
            <li>La plataforma de Cabal responderá con el archivo de devolución <code>782_out_altas_tpc_AAAAMMDD_...</code>.</li>
        </ol>
    </div>

    <!-- SECCIÓN 2: PROCESAR NOV ALTAS -->
    <div class="manual-section-card">
        <div class="manual-section-header">
            <span class="manual-badge-num">2</span>
            <h3 class="manual-section-title">Procesar Novedades de Altas (MovAltas.txt)</h3>
        </div>
        <ol class="steps-list">
            <li>En este archivo de devolución se encuentran los números de cuenta y plástico asignados.</li>
            <li>Descargar desde la plataforma de Cabal el archivo <code>782_out_altas_tpc_AAAAMMDD_...</code>.</li>
            <li>Renombrar el archivo a <code>MovAltas.txt</code> y subirlo al servidor.</li>
            <li>Ingresar a <strong>Procesar Nov. Altas</strong> para incorporar las cuentas y tarjetas al padrón de la base de datos.</li>
            <li>Verificar en el catálogo o exportar el padrón actualizado a formato Excel.</li>
        </ol>
    </div>

    <!-- SECCIÓN 3: ACREDITACIONES Y NOVCAR -->
    <div class="manual-section-card">
        <div class="manual-section-header">
            <span class="manual-badge-num">3</span>
            <h3 class="manual-section-title">Acreditación de Saldos y Lote NOVCAR.782</h3>
        </div>
        <ol class="steps-list">
            <li>Generar o recibir el archivo <code>Acreditaciones.xlsx</code> desde Suministros/SeNAF.</li>
            <li>Columnas requeridas: <em>ID, Apellido, Nombre, Localidad, DNI, CUIT, Monto</em>. No debe incluir filas de sumatoria al pie.</li>
            <li>Subir el archivo al servidor mediante el módulo de subida.</li>
            <li>Ingresar a <strong>Acreditar Saldos</strong> para auditar y vaciar/recargar la tabla de acreditación.</li>
            <li>Ingresar a <strong>Generar NOVCAR</strong> para obtener el archivo batch posicional de 80 bytes por registro (Código Operación 6339).</li>
            <li>Descargar directamente el archivo con codificación ANSI y CRLF para subirlo a la plataforma Cabal con el nombre <code>NOVCAR.782</code>.</li>
        </ol>
        <div class="manual-img-wrap">
            <img src="./img/acreditacion2.png" alt="Formato Planilla Acreditaciones" onerror="this.style.display='none'">
        </div>
    </div>

    <!-- SECCIÓN 4: MOVIMIENTOS -->
    <div class="manual-section-card">
        <div class="manual-section-header">
            <span class="manual-badge-num">4</span>
            <h3 class="manual-section-title">Procesar Movimientos Mensuales de Cuentas</h3>
        </div>
        <ol class="steps-list">
            <li>Descargar desde la plataforma Cabal el extracto mensual (ejemplo: <code>782_Nacion_AAAAMMDD_...txt</code>).</li>
            <li>Este archivo se genera habitualmente el último día hábil de cada mes.</li>
            <li>Renombrar el archivo a <code>Movimientos.txt</code> y subirlo al servidor.</li>
            <li>Acceder a <strong>Procesar Movimientos</strong> y seleccionar el período correspondiente.</li>
            <li>El sistema valida si el período ya fue importado para evitar registros duplicados.</li>
            <li>Consultar los movimientos en pantalla o emitir resúmenes individuales por DNI o titular.</li>
        </ol>
    </div>

    <!-- SECCIÓN 5: RESPUESTA DE CARGAS -->
    <div class="manual-section-card">
        <div class="manual-section-header">
            <span class="manual-badge-num">5</span>
            <h3 class="manual-section-title">Procesar Respuesta de Acreditaciones (RCAR)</h3>
        </div>
        <ol class="steps-list">
            <li>Descargar desde la plataforma Cabal el archivo <code>782_RCAR_AAAAMMDD_...txt</code> generado tras procesar el lote NOVCAR.</li>
            <li>Renombrar a <code>RespuestaCargas.txt</code> y subirlo al servidor.</li>
            <li>Acceder a <strong>Procesar Respuesta Carga Crédito</strong> para conciliar créditos aprobados y verificar motivos de rechazo si los hubiera.</li>
        </ol>
    </div>

</main>

<a href="javascript:history.back()" class="btn-modern-flotante" title="Volver atrás">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver
</a>

</body>
</html>