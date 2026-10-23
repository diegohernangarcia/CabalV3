<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Reporte de Movimientos por Beneficiario
 */

require_once("BD/BD.php");

$servername = "localhost";
$username = "pucho";
$password = "78900";
$dbname = "cabal";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$filtro_nombre = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

if (!empty($filtro_nombre)) {
    $stmt = $conn->prepare("SELECT DISTINCT NombreApellido FROM movimientos WHERE NombreApellido LIKE ? ORDER BY NombreApellido ASC");
    $param = "%" . $filtro_nombre . "%";
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT DISTINCT NombreApellido FROM movimientos ORDER BY NombreApellido ASC";
    $result = $conn->query($sql);
}

$total_titulares = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos por Beneficiario | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-input-wrap {
            flex: 1;
            min-width: 260px;
            position: relative;
        }

        .filter-input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .filter-input-wrap input {
            padding-left: 42px;
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

            <a href="ReporteMovimientos.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Por Mes
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
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Reporte de Movimientos por Beneficiario
            </h2>
            <p>
                Búsqueda individual de consumos, recargas y operaciones por nombre y apellido del titular de tarjeta.
            </p>
        </div>
    </section>

    <form method="get" action="" class="filter-bar">
        <div class="filter-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="buscar" class="form-control-modern" placeholder="Filtrar por Nombre y Apellido..." value="<?php echo htmlspecialchars($filtro_nombre); ?>">
        </div>
        <button type="submit" class="btn-modern-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Buscar
        </button>
        <?php if (!empty($filtro_nombre)): ?>
            <a href="ReporteMovimientosPorNombre.php" class="btn-modern-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Titulares con Movimientos</h3>
                <p>Encontrados: <?php echo $total_titulares; ?> beneficiarios</p>
            </div>
        </div>
        <div class="modern-card-body" style="padding: 0;">
            <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 540px; overflow-y: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nombre y Apellido</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row["NombreApellido"]); ?></strong></td>
                                <td style="text-align: right;">
                                    <a href="consultaMovimientoPorNombre.php?NombreApellido=<?php echo urlencode($row["NombreApellido"]); ?>" class="btn-modern-secondary" style="padding: 6px 14px; font-size: 0.82rem;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                        Ver Movimientos
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                    No se encontraron beneficiarios con movimientos registrados.
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
