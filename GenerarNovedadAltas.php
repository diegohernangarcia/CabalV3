<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Generación de Lote Bancario ALTAUSUCBPRE.782 (Anexo 1 - Entidad 782)
 * Formato Posicional Fijo de 128 bytes por registro (3 registros por titular)
 */

require("BD/UsarGestion.php");
require_once("BD/Funciones.php");
require_once("BD/BD.php");

$sql = 'SELECT * FROM altas ORDER BY id ASC';
$formulario = 1;

$lineas_html = array();
$lineas_txt  = array();
$total_titulares = 0;
$error_dni = false;
$error_dni_msg = '';

foreach ($conn->query($sql) as $row) {
    $LargoFormulario = strlen((string)$formulario);
    if ($LargoFormulario == 1) $NumeroFormulario = "0000" . $formulario;
    if ($LargoFormulario == 2) $NumeroFormulario = "000" . $formulario;
    if ($LargoFormulario == 3) $NumeroFormulario = "00" . $formulario;
    if ($LargoFormulario == 4) $NumeroFormulario = "0" . $formulario;
    if ($LargoFormulario >= 5) $NumeroFormulario = (string)$formulario;

    $Apellido = $row['Apellido'];
    $Nombre = $row['Nombre'];
    $ApellidoNombre = $Apellido . "/" . $Nombre;
    $DomicilioCalle = $row['DomicilioCalle'];
    $DomicilioNumero = $row['DomicilioNumero'];
    $DomicilioPiso = $row['DomicilioPiso'];
    $DomicilioDepartamento = $row['DomicilioDepartamento'];
    $Localidad = $row['Localidad'];
    $Provincia = $row['Provincia'];
    $Sexo = $row['Sexo'];
    $CodigoPostal = $row['CodigoPostal'];
    $TelefonoCaracteristica = $row['TelefonoCaracteristica'];
    $TelefonoNumero = $row['TelefonoNumero'];
    $EstadoCivil = $row['EstadoCivil'];
    $FechaNacimiento = $row['FechaNacimiento'];
    $DocumentoTipo = $row['DocumentoTipo'];
    $DocumentoNumero = $row['DocumentoNumero'];
    $DocumentoProvincia = $row['DocumentoProvincia'];
    $CUIT = $row['CUIT'];

    // Verificación de existencia previa en padrón
    $esta = EstaDNI($DocumentoNumero);
    if ($esta == true) {
        $error_dni = true;
        $error_dni_msg = "El Beneficiario " . $ApellidoNombre . " con DNI " . $DocumentoNumero . " YA se encuentra cargado como Beneficiario en la base de datos.";
        break;
    }

    $total_titulares++;

    // =========================================================================
    // REGISTRO 1: DATOS FILIATORIOS Y DOMICILIO (Exactamente 128 bytes)
    // =========================================================================
    $largo = LargoString($ApellidoNombre);
    $cuantos = 25 - $largo;
    for ($i = 0; $i <= $cuantos - 1; $i++) {
        AgregarEspacios($ApellidoNombre);
    }

    $largo = LargoString($DomicilioCalle);
    $cuantos = 25 - $largo;
    for ($i = 0; $i <= $cuantos - 1; $i++) {
        AgregarEspacios($DomicilioCalle);
    }

    $largo = LargoString($DomicilioNumero);
    $cuantos = 5 - $largo;
    for ($i = 0; $i <= $cuantos - 1; $i++) {
        AgregarEspacios($DomicilioNumero);
    }

    $largo = LargoString($DomicilioPiso);
    $cuantos = 2 - $largo;
    for ($i = 0; $i <= $cuantos - 1; $i++) {
        AgregarEspacios($DomicilioPiso);
    }

    $largo = LargoString($DomicilioDepartamento);
    $cuantos = 2 - $largo;
    for ($i = 0; $i <= $cuantos - 1; $i++) {
        AgregarEspacios($DomicilioDepartamento);
    }

    $largo = LargoString($Localidad);
    $cuantos = 30 - $largo;
    for ($i = 0; $i <= $cuantos - 1; $i++) {
        AgregarEspacios($Localidad);
    }

    $largo = strlen($CodigoPostal);
    if ($largo == 4) {
        $CodigoPostal = "&nbsp;" . $CodigoPostal . "&nbsp;&nbsp;&nbsp;";
    }

    $linea1 = "TE01" . "604386" . "60" . "00000000" . "01" . "782001" . $ApellidoNombre . $DomicilioCalle . $DomicilioNumero . $DomicilioPiso . $DomicilioDepartamento . $Localidad . "16000" . $Sexo . $NumeroFormulario;

    // =========================================================================
    // REGISTRO 2: DOCUMENTO, TELÉFONO Y CUIT (Exactamente 128 bytes)
    // =========================================================================
    CompletarCaracteristica($TelefonoCaracteristica);
    CompletarNumeroTelefono($TelefonoNumero);
    CompletarTipoDocumento($DocumentoTipo);
    CompletarNumeroDocumento($DocumentoNumero);
    CompletarCUIT($CUIT);
    CompletarFechaNacimiento($FechaNacimiento);
    CompletarDocumentoProvincia($DocumentoProvincia);
    $DocumentoProvincia = "00";
    $extras = "00000000000000000000&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    $linea2 = "TE01" . "604386" . "60" . "00000000" . "02" . $CodigoPostal . $TelefonoCaracteristica . $TelefonoNumero . $EstadoCivil . $FechaNacimiento . $DocumentoTipo . $DocumentoNumero . $DocumentoProvincia . $CUIT . $extras . $NumeroFormulario;

    // =========================================================================
    // REGISTRO 3: TIPO DE PRODUCTO Y CONTROL (Exactamente 128 bytes)
    // =========================================================================
    $reserva = "";
    $reserva2 = "";
    for ($i = 0; $i <= 84; $i++) {
        AgregarEspacios($reserva);
    }
    for ($i = 0; $i <= 4; $i++) {
        AgregarEspacios($reserva2);
    }

    $linea3 = "TE01" . "604386" . "60" . "00000000" . "03" . "99" . "P" . "S" . "S" . "&nbsp;" . $reserva . "A" . "1" . $reserva2 . "TPC" . $NumeroFormulario;

    // Guardar para vista HTML idéntica
    $lineas_html[] = $linea1;
    $lineas_html[] = $linea2;
    $lineas_html[] = $linea3;

    // Guardar para copia / descarga pura (.782 estándar Cabal sin &nbsp;)
    $lineas_txt[] = str_replace('&nbsp;', ' ', $linea1);
    $lineas_txt[] = str_replace('&nbsp;', ' ', $linea2);
    $lineas_txt[] = str_replace('&nbsp;', ' ', $linea3);

    $formulario++;
}

// Descarga directa HTTP si se solicita por parámetro ?descargar=1
if (isset($_GET['descargar']) && $_GET['descargar'] == '1' && !$error_dni && count($lineas_txt) > 0) {
    $contenido_archivo = implode("\r\n", $lineas_txt) . "\r\n";
    header('Content-Type: text/plain; charset=ISO-8859-1');
    header('Content-Disposition: attachment; filename="ALTAUSUCBPRE.782"');
    header('Content-Length: ' . strlen($contenido_archivo));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $contenido_archivo;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <meta name="description" content="Generador del archivo de novedades de alta bancaria posicional 128 bytes para Cabal Entidad 782">
    <link rel="icon" href="img/favicon.png">
    <!-- Estilos del Tablero de Operaciones -->
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Gestor de Modo Oscuro / Claro -->
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .batch-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 28px;
        }

        .batch-card-header {
            background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%);
            color: #ffffff;
            padding: 20px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .batch-title-group h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .batch-card-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition-fast);
            border: 1px solid transparent;
        }

        .btn-toolbar.primary {
            background: var(--emerald-600);
            color: #ffffff;
        }

        .btn-toolbar.primary:hover {
            background: var(--emerald-700);
        }

        .btn-toolbar.secondary {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-toolbar.secondary:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .batch-info-strip {
            background: #eff6ff;
            border-bottom: 1px solid #bfdbfe;
            padding: 12px 28px;
            font-size: 0.84rem;
            color: #1e40af;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* ÁREA DE SALIDA DE TEXTO FIJO (PRESERVACIÓN ABSOLUTA DE CARACTERES) */
        .batch-terminal-output {
            background: #0f172a;
            color: #f1f5f9;
            padding: 24px 28px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.82rem;
            line-height: 1.6;
            overflow-x: auto;
            white-space: pre;
            user-select: text;
        }

        .summary-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-stat-box {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            box-shadow: var(--shadow-sm);
        }

        .stat-box-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-box-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--navy-900);
        }

        .stat-box-desc {
            font-size: 0.78rem;
            color: var(--text-light);
            margin-top: 4px;
        }

        .next-steps-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            padding: 24px 28px;
            margin-bottom: 28px;
        }

        .next-steps-card h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--navy-900);
            margin-bottom: 10px;
        }
    </style>
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
            <?php if (!$error_dni && count($lineas_txt) > 0): ?>
            <a href="GenerarNovedadAltas.php?descargar=1" class="btn-nav-action primary" id="btn-top-download">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Descargar ALTAUSUCBPRE.782
            </a>
            <?php endif; ?>

            <a href="CargaAltasDeExcelaBD.php" class="btn-nav-action" id="btn-procesar-excel">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                Planilla Altas
            </a>

            <a href="index.php" class="btn-nav-action" id="btn-inicio">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Tablero
            </a>
        </div>
    </div>
</header>

<!-- =======================================================================
     CONTENEDOR PRINCIPAL
     ======================================================================= -->
<main class="page-container" style="margin-top: 32px;">

    <!-- HERO HEADER DEL MÓDULO -->
    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-content">
            <div class="help-badge-topic">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                Módulo Beneficiarios • Generador de Lote Bancario
            </div>
            <h2>Generar Archivo de Novedades ALTAUSUCBPRE.782</h2>
            <p>
                Lote posicional de <b>128 bytes por registro</b> (3 registros consecutivos <code>01</code>, <code>02</code> y <code>03</code> por cada titular). Estructura requerida por Cabal para el alta y emisión de tarjetas.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <span class="badge-tag accent" style="padding: 8px 14px; font-size: 0.82rem;">
                Entidad 782 • Sucursal 001
            </span>
        </div>
    </section>

    <!-- EN CASO DE ERROR POR DNI YA EXISTENTE -->
    <?php if ($error_dni): ?>
    <section class="status-hero-card" style="border-color: #fecaca; margin-bottom: 28px;">
        <div style="display: flex; align-items: flex-start; gap: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: #991b1b; margin-bottom: 4px;">
                    Proceso Interrumpido: Beneficiario Existente
                </h3>
                <p style="color: #7f1d1d; font-size: 0.95rem; line-height: 1.5;">
                    <?php echo htmlspecialchars($error_dni_msg); ?>
                </p>
                <div style="margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="CargaAltasDeExcelaBD.php" class="btn-nav-action primary" style="padding: 10px 18px;">
                        Revisar Planilla en BD
                    </a>
                    <a href="index.php" class="btn-nav-action" style="padding: 10px 18px;">
                        Volver al Tablero
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- TARJETAS MÉTRICAS DE RESUMEN DEL LOTE -->
    <?php if (!$error_dni): ?>
    <div class="summary-stats-grid">
        <div class="summary-stat-box">
            <div class="stat-box-label">Titulares en Altas</div>
            <div class="stat-box-value"><?php echo number_format($total_titulares, 0, ',', '.'); ?></div>
            <div class="stat-box-desc">Registros procesados desde la tabla altas</div>
        </div>

        <div class="summary-stat-box">
            <div class="stat-box-label">Registros Fijos Generados</div>
            <div class="stat-box-value" style="color: var(--emerald-600);"><?php echo number_format($total_titulares * 3, 0, ',', '.'); ?></div>
            <div class="stat-box-desc">3 líneas fijas por cada titular (01, 02, 03)</div>
        </div>

        <div class="summary-stat-box">
            <div class="stat-box-label">Longitud por Registro</div>
            <div class="stat-box-value" style="color: var(--primary-700);">128 bytes</div>
            <div class="stat-box-desc">Ancho fijo reglamentario Cabal</div>
        </div>

        <div class="summary-stat-box">
            <div class="stat-box-label">Nombre de Archivo</div>
            <div class="stat-box-value" style="font-size: 1rem; font-family: 'JetBrains Mono', monospace; color: var(--navy-900);">
                ALTAUSUCBPRE.782
            </div>
            <div class="stat-box-desc">Listo para subir a Cabal</div>
        </div>
    </div>

    <!-- TARJETA PRINCIPAL DEL LOTE GENERADO -->
    <section class="batch-card">
        <header class="batch-card-header">
            <div class="batch-title-group">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
                    Contenido del Archivo ALTAUSUCBPRE.782
                </h3>
            </div>
            <div class="batch-card-toolbar">
                <button type="button" class="btn-toolbar secondary" id="btn-copiar" onclick="copiarLoteAlPortapapeles()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    Copiar Lote al Portapapeles
                </button>
                <a href="GenerarNovedadAltas.php?descargar=1" class="btn-toolbar primary" id="btn-descargar-archivo">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Descargar ALTAUSUCBPRE.782
                </a>
            </div>
        </header>

        <div class="batch-info-strip">
            <div>
                <strong>Formato Posicional Intacto:</strong> Cada fila respeta rigurosamente los 128 bytes de longitud sin alterar espacios.
            </div>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">
                Total: <?php echo count($lineas_html); ?> líneas generadas
            </div>
        </div>

        <!-- SALIDA EXACTA DE TEXTO (PRESERVACIÓN ÍNTEGRA DE ESPACIOS CON SALTO DE LÍNEA FINAL) -->
        <div class="batch-terminal-output" id="batch-output-screen"><?php foreach ($lineas_html as $linea): ?><?php echo $linea; ?><br><?php endforeach; ?><br></div>
    </section>

    <!-- TEXTAREA OCULTO CON EL LOTE PURO PARA COPIAR CON ESPACIOS EXACTOS Y SALTO CRLF FINAL -->
    <textarea id="texto-puro-lote" style="position: absolute; left: -9999px; top: -9999px;"><?php echo htmlspecialchars(implode("\r\n", $lineas_txt) . "\r\n"); ?></textarea>

    <!-- GUÍA DE PASOS SIGUIENTES -->
    <section class="next-steps-card">
        <h4>Flujo Operativo Siguiente:</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; margin-bottom: 16px;">
            1. Descargá o copiá el archivo <b>ALTAUSUCBPRE.782</b> y subilo a la plataforma bancaria de Cabal.<br>
            2. La plataforma de Cabal procesará las altas y emitirá el archivo de respuesta bancaria <b>MovAltas.txt</b>.<br>
            3. Una vez recibido <b>MovAltas.txt</b>, subilo al servidor en <a href="SubirArchivo.html" style="color: var(--primary-700); font-weight: 600;">Subir Archivos</a> y procedé a vincular las tarjetas en el padrón.
        </p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer" class="btn-nav-action primary" style="padding: 10px 18px; font-size: 0.9rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                Acceder a Plataforma Cabal (Transmisiones)
            </a>
            <a href="ProcesaNovedadesAltas.php" class="btn-nav-action" style="padding: 10px 18px; font-size: 0.9rem;">
                Siguiente Paso: Procesar MovAltas.txt
            </a>
            <a href="index.php" class="btn-nav-action" style="padding: 10px 18px; font-size: 0.9rem;">
                Volver al Tablero
            </a>
        </div>
    </section>
    <?php endif; ?>

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
            <a href="index.php">Inicio</a>
            <a href="CargaAltasDeExcelaBD.php">Planilla Altas</a>
            <a href="SubirArchivo.html">Subir Archivos</a>
            <a href="ayuda.html" target="_blank">Manual de Usuario</a>
            <a href="docs/ARQUITECTURA.md" target="_blank">Arquitectura</a>
            <a href="GEMINI.md" target="_blank">Normas Técnicas</a>
        </div>
    </div>
</footer>

<script>
function copiarLoteAlPortapapeles() {
    var area = document.getElementById('texto-puro-lote');
    if (!area) return;

    area.select();
    area.setSelectionRange(0, 99999);

    var btn = document.getElementById('btn-copiar');
    var textoOriginal = btn.innerHTML;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(area.value).then(function() {
                mostrarCopiado(btn, textoOriginal);
            }).catch(function() {
                document.execCommand('copy');
                mostrarCopiado(btn, textoOriginal);
            });
        } else {
            document.execCommand('copy');
            mostrarCopiado(btn, textoOriginal);
        }
    } catch (err) {
        alert("Por favor selecciona el texto del lote manualmente para copiarlo.");
    }
}

function mostrarCopiado(btn, textoOriginal) {
    btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> ¡Lote Copiado con Éxito!';
    btn.style.background = '#059669';
    setTimeout(function() {
        btn.innerHTML = textoOriginal;
        btn.style.background = '';
    }, 2500);
}
</script>

</body>
</html>
