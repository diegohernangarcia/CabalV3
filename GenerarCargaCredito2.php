<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Generación de Lote Bancario NOVCAR.782 (Entidad 782 - Tabla acreditar)
 * Formato Posicional Fijo de 80 bytes por registro con CRLF Windows
 */

require("BD/UsarGestion.php");
require_once("BD/Funciones.php");
require_once("BD/BD.php");

$miArray = array();
$lineas_txt = array();

$sql = 'SELECT * FROM acreditar';

// 1. HEADER (80 caracteres exactos)
$dia = date("d");
$mes = date("m");
$anio = date("y");
$fecha = $dia . $mes . $anio;
date_default_timezone_set("America/Mendoza");
$hora = "090000";

$espacios = "";
for ($i = 0; $i < 42; $i++) {
    AgregarEspacios2($espacios);
}
$header = "TE047820000000000000000000" . $fecha . $hora . $espacios;
$miArray[] = $header;
$lineas_txt[] = $header;

// 2. REGISTROS DETALLE (80 caracteres exactos cada uno)
$Registro = 0;
$MontoFinal = 0;
$ImporteCompra = 0;

$stmt = $conn->query($sql);
if ($stmt) {
    foreach ($stmt as $row) {
        $NumeroCuenta = $row['NumeroCuenta'];
        $ImporteCompra = $row['ImporteCompra'];
        $MontoFinal += $ImporteCompra;
        $Registro++;

        $LargoRegistro = strlen((string)$Registro);
        if ($LargoRegistro == 1) $NumeroRegistro = "0000000" . $Registro;
        elseif ($LargoRegistro == 2) $NumeroRegistro = "000000" . $Registro;
        elseif ($LargoRegistro == 3) $NumeroRegistro = "00000" . $Registro;
        elseif ($LargoRegistro == 4) $NumeroRegistro = "0000" . $Registro;
        elseif ($LargoRegistro == 5) $NumeroRegistro = "000" . $Registro;
        elseif ($LargoRegistro == 6) $NumeroRegistro = "00" . $Registro;
        elseif ($LargoRegistro == 7) $NumeroRegistro = "0" . $Registro;
        else $NumeroRegistro = (string)$Registro;

        $espacios = "";
        for ($i = 0; $i < 18; $i++) {
            AgregarEspacios2($espacios);
        }

        $Detalle = "TE04782001" . $NumeroCuenta . "6339" . CompletarMontoConCeros($ImporteCompra, 9) . "000000000006239" . $NumeroRegistro . $espacios;
        $miArray[] = $Detalle;
        $lineas_txt[] = $Detalle;
    }
}

// 3. TRAILER (80 caracteres exactos)
$LargoRegistro = strlen((string)$Registro);
if ($LargoRegistro == 1) $CantidadRegistros = "0000" . $Registro;
elseif ($LargoRegistro == 2) $CantidadRegistros = "000" . $Registro;
elseif ($LargoRegistro == 3) $CantidadRegistros = "00" . $Registro;
elseif ($LargoRegistro == 4) $CantidadRegistros = "0" . $Registro;
else $CantidadRegistros = (string)$Registro;

$MontoFinalStr = CompletarMontoConCeros($MontoFinal, 10);
$espacios = "";
for ($i = 0; $i < 29; $i++) {
    AgregarEspacios2($espacios);
}

$trailer = "TE047820009999999999999999" . $CantidadRegistros . $MontoFinalStr . "0000000000" . $espacios;
$miArray[] = $trailer;
$lineas_txt[] = $trailer;

// Guardar en disco con CRLF Windows
$contenido_final = implode("\r\n", $lineas_txt) . "\r\n";
file_put_contents("Novcar/novcar.782", $contenido_final);
escribirArrayEnArchivo($miArray, "/tmp/novcar.782");

// Descarga directa HTTP
if (isset($_GET['descargar']) && $_GET['descargar'] == '1') {
    header('Content-Type: text/plain; charset=ISO-8859-1');
    header('Content-Disposition: attachment; filename="NOVCAR.782"');
    header('Content-Length: ' . strlen($contenido_final));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $contenido_final;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <meta name="description" content="Generador del archivo de novedades de acreditación NOVCAR.782 para Cabal Entidad 782">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1380px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .meta-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .meta-stat-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            box-shadow: var(--shadow-sm);
        }

        .meta-stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .meta-stat-val {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--navy-800);
        }

        .actions-bar-lote {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .terminal-wrap {
            background: #080d1a;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .terminal-header {
            background: #0f172a;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .terminal-body {
            padding: 20px 24px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            line-height: 1.6;
            color: #38bdf8;
            overflow-x: auto;
            white-space: pre;
            max-height: 480px;
            overflow-y: auto;
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

            <a href="VerificarNovcar.php" class="btn-nav-action" id="btn-verificar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Verificar Lote
            </a>

            <a href="ayuda.html" target="_blank" class="btn-nav-action" id="btn-ayuda">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Guía Operativa
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="margin-top: 32px;">

    <section class="help-hero-banner">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--amber-500);">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Emisión de Lote NOVCAR.782
            </h2>
            <p>
                Generación del lote de acreditación bancaria (tabla acreditar) con formato reglamentario Cabal de 80 bytes por registro con codificación ANSI y saltos CRLF.
            </p>
        </div>
    </section>

    <div class="meta-stats-grid">
        <div class="meta-stat-card">
            <div class="meta-stat-label">Registros Acreditados</div>
            <div class="meta-stat-val"><?php echo number_format($Registro, 0, ',', '.'); ?></div>
        </div>

        <div class="meta-stat-card">
            <div class="meta-stat-label">Importe Total Acreditado</div>
            <div class="meta-stat-val" style="color: var(--emerald-600);">$<?php echo number_format($MontoFinal, 2, ',', '.'); ?></div>
        </div>

        <div class="meta-stat-card">
            <div class="meta-stat-label">Ancho de Registro Cabal</div>
            <div class="meta-stat-val">80 <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">bytes fijas</span></div>
        </div>

        <div class="meta-stat-card">
            <div class="meta-stat-label">Estado del Lote</div>
            <div class="meta-stat-val" style="color: var(--primary-600); font-size: 1.25rem;">
                <span class="status-dot" style="display:inline-block; margin-right: 6px;"></span>
                Generado OK
            </div>
        </div>
    </div>

    <div class="actions-bar-lote">
        <a href="GenerarCargaCredito2.php?descargar=1" class="btn-modern-primary" id="btn-descargar-directa">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Descargar NOVCAR.782
        </a>

        <button type="button" class="btn-modern-secondary" id="btn-copiar-lote" onclick="copiarLotePortapapeles()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            Copiar al Portapapeles
        </button>

        <a href="VerificarNovcar.php" class="btn-modern-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Auditar con Verificar NOVCAR
        </a>
    </div>

    <!-- Terminal Output -->
    <div class="terminal-wrap">
        <div class="terminal-header">
            <span>Visor de Lote Generado (Ancho: 80 columnas)</span>
            <span>Total líneas: <?php echo count($lineas_txt); ?></span>
        </div>
        <div class="terminal-body" id="terminal-content"><?php echo htmlspecialchars($contenido_final); ?></div>
    </div>

    <textarea id="texto-puro-lote" style="position: absolute; left: -9999px;"><?php echo $contenido_final; ?></textarea>
</main>

<script>
function copiarLotePortapapeles() {
    var copyText = document.getElementById("texto-puro-lote");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(copyText.value).then(function() {
            mostrarToastCopiado();
        }).catch(function() {
            document.execCommand("copy");
            mostrarToastCopiado();
        });
    } catch (e) {
        document.execCommand("copy");
        mostrarToastCopiado();
    }
}

function mostrarToastCopiado() {
    var btn = document.getElementById("btn-copiar-lote");
    var originalHTML = btn.innerHTML;
    btn.innerHTML = '✓ ¡Copiado con CRLF!';
    btn.style.borderColor = 'var(--emerald-500)';
    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.style.borderColor = '';
    }, 2500);
}
</script>

</body>
</html>
