<?php
// =============================================
//  CONFIGURACIÓN DE BASE DE DATOS
// =============================================
require_once("BD/Funciones.php");

$db_host = 'localhost';
$db_name = 'cabal';
$db_user = 'pucho';
$db_pass = '78900';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8');

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// =============================================
//  OBTENER VALORES ÚNICOS PARA FILTROS
// =============================================
$archivos = array();
$res = $conn->query("SELECT DISTINCT Archivo FROM MasterTarjetas ORDER BY Archivo");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $archivos[] = $row['Archivo'];
    }
}

// =============================================
//  FILTROS RECIBIDOS
// =============================================
$filtro_archivo = isset($_GET['archivo'])        ? trim($_GET['archivo'])        : '';
$filtro_nombre  = isset($_GET['nombre_apellido']) ? trim($_GET['nombre_apellido']) : '';
$exportar       = isset($_GET['exportar'])        ? true : false;

// =============================================
//  CONSTRUIR CONSULTA
// =============================================
$where  = array();
$params = array();
$types  = '';

if ($filtro_archivo !== '') {
    $where[]  = 'Archivo = ?';
    $params[] = $filtro_archivo;
    $types   .= 's';
}
if ($filtro_nombre !== '') {
    $where[]  = 'NombreApellido LIKE ?';
    $params[] = '%' . $filtro_nombre . '%';
    $types   .= 's';
}

$sql = "SELECT id, NumeroCuenta, NumeroTarjeta, NombreApellido, TipoDocumento,
               NroDocumento, Causa, FechaAlta, FechaEmision, FechaVencimiento, Archivo
        FROM MasterTarjetas";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY NombreApellido ASC';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    if (count($params) === 1) {
        $stmt->bind_param($types, $params[0]);
    } elseif (count($params) === 2) {
        $stmt->bind_param($types, $params[0], $params[1]);
    }
}
$stmt->execute();
$result = $stmt->get_result();

// =============================================
//  EXPORTAR A EXCEL (CSV con separador ;)
// =============================================
if ($exportar) {
    $filename = 'reporte_tarjetas_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // BOM UTF-8 para Excel
    $out = fopen('php://output', 'w');

    fputcsv($out, array(
        'ID',
        'Numero Cuenta',
        'Numero Tarjeta',
        'Nombre y Apellido',
        'Tipo Doc',
        'Nro Documento',
        'Causa',
        'Fecha Alta',
        'Fecha Emision',
        'Fecha Vencimiento',
        'Archivo'
    ), ';');

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, array(
            $row['id'],
            $row['NumeroCuenta'],
            $row['NumeroTarjeta'],
            $row['NombreApellido'],
            $row['TipoDocumento'],
            $row['NroDocumento'],
            $row['Causa'],
            $row['FechaAlta'],
            $row['FechaEmision'],
            $row['FechaVencimiento'],
            $row['Archivo'],
        ), ';');
    }

    fclose($out);
    $stmt->close();
    $conn->close();
    exit;
}

$total = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Master Tarjetas | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            align-items: flex-end;
            margin-bottom: 20px;
        }

        .badge-hab { background: rgba(16, 185, 129, 0.18); color: #059669; }
        .badge-anulada { background: rgba(239, 68, 68, 0.18); color: #dc2626; }
        .badge-denuncia { background: rgba(245, 158, 11, 0.18); color: #d97706; }
        .badge-reemplazo { background: rgba(126, 34, 206, 0.18); color: #7e22ce; }
        .badge-otro { background: rgba(148, 163, 184, 0.18); color: #475569; }

        [data-theme="dark"] .badge-hab { background: rgba(16, 185, 129, 0.25); color: #6ee7b7; }
        [data-theme="dark"] .badge-anulada { background: rgba(239, 68, 68, 0.25); color: #fca5a5; }
        [data-theme="dark"] .badge-denuncia { background: rgba(245, 158, 11, 0.25); color: #fde68a; }
        [data-theme="dark"] .badge-reemplazo { background: rgba(168, 85, 247, 0.25); color: #d8b4fe; }
        [data-theme="dark"] .badge-otro { background: rgba(148, 163, 184, 0.25); color: #cbd5e1; }
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

            <a href="ProcesarMaestroPlasticosArchivo.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Procesar Maestro
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
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-500);">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Catálogo de Master Tarjetas
            </h2>
            <p>
                Inventario consolidado de plásticos emitidos, estados de habilitación, causas y vigencias.
            </p>
        </div>
    </section>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Filtros de Búsqueda</h3>
                <p>Total de tarjetas encontradas: <strong><?php echo number_format($total); ?></strong></p>
            </div>
            <div>
                <a href="?<?php
                    $qe = $_GET;
                    $qe['exportar'] = '1';
                    echo http_build_query($qe);
                ?>" class="btn-modern-success" style="padding: 8px 16px; font-size: 0.84rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Exportar a Excel (CSV)
                </a>
            </div>
        </div>
        <div class="modern-card-body">
            <form method="GET" action="">
                <div class="filtros-grid">
                    <div class="form-group-modern" style="margin-bottom: 0;">
                        <label for="archivo">Archivo Fuente:</label>
                        <select name="archivo" id="archivo" class="form-control-modern">
                            <option value="">— Todos los archivos —</option>
                            <?php foreach ($archivos as $arch): ?>
                                <option value="<?php echo htmlspecialchars($arch); ?>" <?php echo ($filtro_archivo === $arch) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($arch); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-modern" style="margin-bottom: 0;">
                        <label for="nombre_apellido">Nombre y Apellido:</label>
                        <input type="text" name="nombre_apellido" id="nombre_apellido" class="form-control-modern" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($filtro_nombre); ?>">
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn-modern-primary" style="flex: 1;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            Filtrar
                        </button>
                        <a href="reporte_tarjetas.php" class="btn-modern-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Padrón de Plásticos</h3>
                <p>Número de tarjeta protegido con máscara de seguridad reglamentaria</p>
            </div>
        </div>
        <div class="modern-card-body" style="padding: 0;">
            <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 580px; overflow-y: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nro Cuenta</th>
                            <th>Nro Tarjeta</th>
                            <th>Nombre y Apellido</th>
                            <th>Tipo Doc</th>
                            <th>Nro Documento</th>
                            <th>Causa / Estado</th>
                            <th>Fecha Alta</th>
                            <th>Fecha Emisión</th>
                            <th>Fecha Vto</th>
                            <th>Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $causa = trim($row['Causa']);
                                if ($causa === 'HABILITADA') $badge = 'badge-hab';
                                elseif (strpos($causa, 'ANULACION') !== false) $badge = 'badge-anulada';
                                elseif (strpos($causa, 'DENUNCIA') !== false) $badge = 'badge-denuncia';
                                elseif ($causa === 'REEMPLAZO') $badge = 'badge-reemplazo';
                                else $badge = 'badge-otro';
                            ?>
                            <tr>
                                <td><code>#<?php echo $row['id']; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($row['NumeroCuenta']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars(formatearTarjetaCredito($row['NumeroTarjeta'])); ?></code></td>
                                <td><strong><?php echo htmlspecialchars(trim($row['NombreApellido'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['TipoDocumento']); ?></td>
                                <td><?php echo htmlspecialchars($row['NroDocumento']); ?></td>
                                <td><span class="badge-tag <?php echo $badge; ?>"><?php echo htmlspecialchars($causa); ?></span></td>
                                <td><?php echo !empty($row['FechaAlta']) ? date('d/m/Y', strtotime($row['FechaAlta'])) : '-'; ?></td>
                                <td><?php echo !empty($row['FechaEmision']) ? date('d/m/Y', strtotime($row['FechaEmision'])) : '-'; ?></td>
                                <td><?php echo !empty($row['FechaVencimiento']) ? date('d/m/Y', strtotime($row['FechaVencimiento'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($row['Archivo']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    No se encontraron plásticos registrados con los filtros seleccionados.
                                </td>
                            </tr>
                        <?php endif; ?>
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
<?php
$stmt->close();
$conn->close();
?>
