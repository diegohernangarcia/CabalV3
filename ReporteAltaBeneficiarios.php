<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Reporte de Padrón de Beneficiarios con Ordenamiento Interactivo
 */

require_once("BD/Funciones.php");

$conn = new mysqli('localhost', 'pucho', '78900', 'cabal');
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Lista blanca de columnas permitidas para ordenar
$allowed_orders = array('Apellido', 'Nombre', 'Localidad', 'FechaProceso', 'NumeroCuenta', 'NumeroTarjeta', 'NumeroDocumento', 'Cuit');
$order = isset($_GET['order']) && in_array($_GET['order'], $allowed_orders) ? $_GET['order'] : 'Apellido';
$direction = (isset($_GET['dir']) && strtoupper($_GET['dir']) === 'DESC') ? 'DESC' : 'ASC';

$sql = "SELECT Apellido, Nombre, Localidad, FechaProceso, NumeroCuenta, NumeroTarjeta, NumeroDocumento, Cuit FROM beneficiario ORDER BY " . $order . " " . $direction;
$result = $conn->query($sql);
$total_beneficiarios = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Beneficiarios | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .sortable-th {
            cursor: pointer;
            user-select: none;
            transition: color var(--transition-fast);
        }

        .sortable-th:hover {
            color: var(--primary-600);
        }
    </style>
    <script>
        function ordenarPor(columna) {
            var urlParams = new URLSearchParams(window.location.search);
            var currentOrder = urlParams.get('order');
            var currentDir = urlParams.get('dir') || 'ASC';
            var newDir = 'ASC';
            if (currentOrder === columna && currentDir === 'ASC') {
                newDir = 'DESC';
            }
            window.location.href = "?order=" + encodeURIComponent(columna) + "&dir=" + newDir;
        }
    </script>
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

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Exportar Excel
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
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Padrón de Beneficiarios Registrados
            </h2>
            <p>
                Visualización tabular de los titulares activos en base de datos. Haga clic en los encabezados de columna para ordenar interactivamente.
            </p>
        </div>
    </section>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Listado de Titulares</h3>
                <p>Total registrados: <?php echo number_format($total_beneficiarios, 0, ',', '.'); ?> • Ordenado por: <strong><?php echo htmlspecialchars($order . " (" . $direction . ")"); ?></strong></p>
            </div>
            <div>
                <a href="ExportarExcelBeneficiarios.php" class="btn-modern-success" style="padding: 8px 16px; font-size: 0.84rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Descargar en Excel
                </a>
            </div>
        </div>
        <div class="modern-card-body" style="padding: 0;">
            <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 600px; overflow-y: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th class="sortable-th" onclick="ordenarPor('Apellido')" title="Clic para ordenar">Apellido ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('Nombre')" title="Clic para ordenar">Nombre ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('Localidad')" title="Clic para ordenar">Localidad ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('FechaProceso')" title="Clic para ordenar">Fecha Proceso ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('NumeroCuenta')" title="Clic para ordenar">Número Cuenta ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('NumeroTarjeta')" title="Clic para ordenar">Número Tarjeta ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('NumeroDocumento')" title="Clic para ordenar">Documento ↕</th>
                            <th class="sortable-th" onclick="ordenarPor('Cuit')" title="Clic para ordenar">CUIT ↕</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row["Apellido"]); ?></strong></td>
                                <td><?php echo htmlspecialchars($row["Nombre"]); ?></td>
                                <td><?php echo htmlspecialchars($row["Localidad"]); ?></td>
                                <td><?php echo htmlspecialchars($row["FechaProceso"]); ?></td>
                                <td><strong><?php echo htmlspecialchars($row["NumeroCuenta"]); ?></strong></td>
                                <td><code><?php echo htmlspecialchars(formatearTarjetaCredito($row["NumeroTarjeta"])); ?></code></td>
                                <td><?php echo htmlspecialchars($row["NumeroDocumento"]); ?></td>
                                <td><code><?php echo htmlspecialchars($row["Cuit"]); ?></code></td>
                                <td style="text-align: right;">
                                    <a href="consultaBeneficiario.php?Cuit=<?php echo urlencode($row["Cuit"]); ?>" class="btn-modern-secondary" style="padding: 5px 12px; font-size: 0.8rem;">
                                        Ver Ficha
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                    No se encontraron beneficiarios en el padrón.
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