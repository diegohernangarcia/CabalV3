<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Ficha Individual de Beneficiario por CUIT
 */

require_once("BD/Funciones.php");

$conn = new mysqli('localhost', 'pucho', '78900', 'cabal');
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$cuit = isset($_GET['Cuit']) ? trim($_GET['Cuit']) : '';
$beneficiario = null;

if (!empty($cuit)) {
    $sql = "SELECT Apellido, Nombre, Localidad, FechaProceso, NumeroCuenta, NumeroTarjeta, NumeroDocumento, Cuit FROM beneficiario WHERE Cuit = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cuit);
    $stmt->execute();
    $result = $stmt->get_result();
    $beneficiario = $result->fetch_assoc();
    $stmt->close();
}

function convertirFecha2($fecha) {
    if (strlen($fecha) < 6) return $fecha;
    $año = substr($fecha, 0, 2);
    $mes = substr($fecha, 2, 2);
    $día = substr($fecha, 4, 2);
    return $día . '/' . $mes . '/20' . $año;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Beneficiario | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 800px;
            margin: 32px auto 50px;
            padding: 0 20px;
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .data-row:last-child {
            border-bottom: none;
        }

        .data-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
        }

        .data-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--navy-800);
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
            <a href="ConsultarBeneficiario.php" class="btn-nav-action primary" id="btn-volver-consulta">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Buscar otro DNI
            </a>

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Padrón
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

<main class="page-container">

    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Ficha del Titular Cabal
            </h2>
            <p>
                Detalle filiatorio, cuenta bancaria asignada y plástico asociado al beneficiario.
            </p>
        </div>
    </section>

    <?php if ($beneficiario): ?>
        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3><?php echo htmlspecialchars($beneficiario["Apellido"] . " " . $beneficiario["Nombre"]); ?></h3>
                    <p>DNI: <?php echo htmlspecialchars($beneficiario["NumeroDocumento"]); ?> • CUIT: <?php echo htmlspecialchars($beneficiario["Cuit"]); ?></p>
                </div>
                <div>
                    <a href="consultaMovimientoPorDNI.php?DNI=<?php echo urlencode($beneficiario["NumeroDocumento"]); ?>" class="btn-modern-primary" style="font-size: 0.84rem; padding: 8px 16px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        Ver Movimientos
                    </a>
                </div>
            </div>
            <div class="modern-card-body">
                <div class="data-row">
                    <span class="data-label">Apellido y Nombre</span>
                    <span class="data-value"><?php echo htmlspecialchars($beneficiario["Apellido"] . ", " . $beneficiario["Nombre"]); ?></span>
                </div>

                <div class="data-row">
                    <span class="data-label">Localidad</span>
                    <span class="data-value"><?php echo htmlspecialchars($beneficiario["Localidad"]); ?></span>
                </div>

                <div class="data-row">
                    <span class="data-label">Fecha de Proceso</span>
                    <span class="data-value"><?php echo htmlspecialchars(convertirFecha2($beneficiario["FechaProceso"])); ?></span>
                </div>

                <div class="data-row">
                    <span class="data-label">Número de Cuenta</span>
                    <span class="data-value" style="color: var(--primary-600);"><?php echo htmlspecialchars($beneficiario["NumeroCuenta"]); ?></span>
                </div>

                <div class="data-row">
                    <span class="data-label">Número de Tarjeta (Enmascarada)</span>
                    <span class="data-value"><code><?php echo htmlspecialchars(formatearTarjetaCredito($beneficiario["NumeroTarjeta"])); ?></code></span>
                </div>

                <div class="data-row">
                    <span class="data-label">Documento Nacional de Identidad</span>
                    <span class="data-value"><?php echo htmlspecialchars($beneficiario["NumeroDocumento"]); ?></span>
                </div>

                <div class="data-row">
                    <span class="data-label">CUIT / CUIL</span>
                    <span class="data-value"><code><?php echo htmlspecialchars($beneficiario["Cuit"]); ?></code></span>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>No se encontró ningún beneficiario con el CUIT especificado (<code><?php echo htmlspecialchars($cuit); ?></code>).</div>
        </div>
    <?php endif; ?>

</main>

<a href="index.php" class="btn-modern-flotante" title="Volver al Tablero Principal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver al Tablero
</a>

</body>
</html>
