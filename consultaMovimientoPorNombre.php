<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Consulta de Movimientos por Nombre de Beneficiario
 */

require_once("BD/Funciones.php");
require_once("BD/BD.php");

$servername = "localhost";
$username = "pucho";
$password = "78900";
$dbname = "cabal";

$NombreApellido = isset($_GET['NombreApellido']) ? trim($_GET['NombreApellido']) : '';

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Prepared Statement para mitigar SQL Injection
$sql = "SELECT * FROM movimientos WHERE NombreApellido = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $NombreApellido);
$stmt->execute();
$result = $stmt->get_result();
$total_movimientos = $result ? $result->num_rows : 0;
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
            max-width: 1400px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .op-badge-initial {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .op-badge-cargas {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(59, 130, 246, 0.15);
            color: #2563eb;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .op-badge-autoriz {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .op-badge-final {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(126, 34, 206, 0.15);
            color: #7e22ce;
            font-weight: 700;
            font-size: 0.75rem;
        }

        [data-theme="dark"] .op-badge-initial { background: rgba(16, 185, 129, 0.25); color: #6ee7b7; }
        [data-theme="dark"] .op-badge-cargas  { background: rgba(59, 130, 246, 0.25); color: #93c5fd; }
        [data-theme="dark"] .op-badge-autoriz { background: rgba(245, 158, 11, 0.25); color: #fde68a; }
        [data-theme="dark"] .op-badge-final   { background: rgba(168, 85, 247, 0.25); color: #d8b4fe; }
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
            <a href="ReporteMovimientosPorNombre.php" class="btn-nav-action primary" id="btn-volver-beneficiarios">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver a Beneficiarios
            </a>

            <button type="button" class="btn-nav-action" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Imprimir
            </button>

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
                Movimientos de <?php echo htmlspecialchars($NombreApellido); ?>
            </h2>
            <p>
                Historial completo de consumos y movimientos asociados a este titular.
            </p>
        </div>
    </section>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Detalle de Transacciones</h3>
                <p>Total de operaciones: <?php echo $total_movimientos; ?></p>
            </div>
            <div>
                <button type="button" class="btn-modern-secondary" onclick="window.print()" style="padding: 8px 16px; font-size: 0.84rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Imprimir Informe
                </button>
            </div>
        </div>
        <div class="modern-card-body" style="padding: 0;">
            <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 580px; overflow-y: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cuenta</th>
                            <th>Tarjeta</th>
                            <th>Documento</th>
                            <th>Operación</th>
                            <th>Comercio</th>
                            <th>Importe</th>
                            <th>Período</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                $codOp = $row["CodigoOperacion"];
                                $badgeClass = 'op-badge-initial';
                                if ($codOp == 'SALDO FINAL') $badgeClass = 'op-badge-final';
                                elseif ($codOp == 'CARGA/DESCARGA') $badgeClass = 'op-badge-cargas';
                                elseif ($codOp == 'AUTORIZACIONES') $badgeClass = 'op-badge-autoriz';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["Fecha"]); ?></td>
                                <td><strong><?php echo htmlspecialchars($row["Cuenta"]); ?></strong></td>
                                <td><code><?php echo htmlspecialchars(formatearTarjetaCredito($row["Tarjeta"])); ?></code></td>
                                <td><?php echo htmlspecialchars(DevueltoTipoDoc($row["TipoDocumento"])) . " " . EliminarCerosIzquierda(htmlspecialchars($row["Documento"])); ?></td>
                                <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($codOp); ?></span></td>
                                <td><?php echo htmlspecialchars($row["Comercio"]); ?></td>
                                <td style="font-weight: 700; color: <?php echo ($row["Signo"] == '-' ? '#ef4444' : 'var(--emerald-600)'); ?>;">
                                    <?php echo $row["Signo"] . "$" . number_format((float)$row["Importe"], 2, ',', '.'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row["Mes"] . " " . $row["Anio"]); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                    No se encontraron movimientos registrados para este titular.
                                </td>
                            </tr>
                        <?php endif; 
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<a href="ReporteMovimientosPorNombre.php" class="btn-modern-flotante" title="Volver al Listado de Beneficiarios">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver a Beneficiarios
</a>

</body>
</html>
