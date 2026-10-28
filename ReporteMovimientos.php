<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Reporte General Mensual de Movimientos
 */

$servername = "localhost";
$username = "pucho";
$password = "78900";
$dbname = "cabal";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT DISTINCT mes, anio, archivo FROM movimientos ORDER BY anio DESC, 
        CASE 
            WHEN mes = 'Enero' THEN 1
            WHEN mes = 'Febrero' THEN 2
            WHEN mes = 'Marzo' THEN 3
            WHEN mes = 'Abril' THEN 4
            WHEN mes = 'Mayo' THEN 5
            WHEN mes = 'Junio' THEN 6
            WHEN mes = 'Julio' THEN 7
            WHEN mes = 'Agosto' THEN 8
            WHEN mes = 'Septiembre' THEN 9
            WHEN mes = 'Octubre' THEN 10
            WHEN mes = 'Noviembre' THEN 11
            WHEN mes = 'Diciembre' THEN 12
        END DESC";
$result = $conn->query($sql);
$total_periodos = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1100px;
            margin: 0 auto 50px;
            padding: 0 24px;
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

            <a href="ReporteMovimientosPorNombre.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Por Beneficiario
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
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--purple-600);">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Reporte Mensual de Movimientos
            </h2>
            <p>
                Catálogo histórico de períodos y lotes de movimientos procesados en el sistema Cabal FSR.
            </p>
        </div>
    </section>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Períodos con Movimientos Registrados</h3>
                <p>Total de lotes disponibles: <?php echo $total_periodos; ?></p>
            </div>
            <div>
                <a href="ProcesarMovimientos.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.84rem;">
                    + Procesar Nuevo Archivo
                </a>
            </div>
        </div>
        <div class="modern-card-body" style="padding: 0;">
            <div class="modern-table-responsive" style="margin: 0; border: none;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Año</th>
                            <th>Archivo / Carga</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row["mes"]); ?></strong></td>
                                <td><?php echo htmlspecialchars($row["anio"]); ?></td>
                                <td><span class="badge-tag accent">Carga #<?php echo htmlspecialchars($row["archivo"]); ?></span></td>
                                <td style="text-align: right;">
                                    <a href="consultaMovimiento.php?mes=<?php echo urlencode($row["mes"]); ?>&anio=<?php echo urlencode($row["anio"]); ?>&archivo=<?php echo urlencode($row["archivo"]); ?>" class="btn-modern-secondary" style="padding: 6px 14px; font-size: 0.82rem;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                        Consultar Extracto
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                    No se encontraron períodos de movimientos registrados en la base de datos.
                                </td>
                            </tr>
                        <?php endif; 
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
