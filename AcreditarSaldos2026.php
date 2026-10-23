<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar PHPExcel
require_once 'phpexcel/PHPExcel.php';

// Conexión
$conn = mysqli_connect("localhost", "pucho", "78900", "cabal");
mysqli_set_charset($conn, "utf8");

$stats = array('total' => 0, 'exitos' => 0, 'fallidos' => 0, 'dinero' => 0);
$procesados = array();

if (isset($_POST['btn_cargar']) && isset($_FILES['archivo_excel']['tmp_name']) && !empty($_FILES['archivo_excel']['tmp_name'])) {
    $archivoTmp = $_FILES['archivo_excel']['tmp_name'];

    try {
        $phpExcel = PHPExcel_IOFactory::load($archivoTmp);
        $hoja = $phpExcel->getSheet(0); 
        $filasMax = $hoja->getHighestRow();

        mysqli_query($conn, "TRUNCATE TABLE acreditar2");

        $sql_busqueda = "SELECT id, Apellido, Nombre, NumeroCuenta, NumeroDocumento FROM beneficiario WHERE Cuit = ? LIMIT 1";
        $stmt_busqueda = mysqli_prepare($conn, $sql_busqueda);

        $sql_ins = "INSERT INTO acreditar2 (id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) 
                    VALUES (?, ?, ?, ?, ?, '0', ?, ?, ?)";
        $stmt_ins = mysqli_prepare($conn, $sql_ins);

        // Empezamos en fila 6. 
        for ($fila = 6; $fila <= $filasMax; $fila++) {
            $cuil_excel = trim((string)$hoja->getCellByColumnAndRow(6, $fila)->getValue());
            $monto = $hoja->getCellByColumnAndRow(7, $fila)->getValue();
            $apellido_check = trim((string)$hoja->getCellByColumnAndRow(2, $fila)->getValue());

            // --- FILTRO: Ignorar fila de total o vacías ---
            if (empty($apellido_check) || empty($cuil_excel) || !is_numeric($cuil_excel)) {
                continue; 
            }

            $stats['total']++;
            mysqli_stmt_bind_param($stmt_busqueda, "s", $cuil_excel);
            mysqli_stmt_execute($stmt_busqueda);
            $resultado = mysqli_stmt_get_result($stmt_busqueda);

            $item = array('fila' => $fila, 'cuil' => $cuil_excel, 'monto' => $monto, 'status' => 'error', 'msg' => 'No encontrado en BD');

            if ($row = mysqli_fetch_assoc($resultado)) {
                $fecha_actual = date("Y-m-d");
                $hora_actual  = date("H:i:s");
                
                mysqli_stmt_bind_param($stmt_ins, "isssssss", 
                    $row['id'], $row['Apellido'], $row['Nombre'], $row['NumeroCuenta'], 
                    $monto, $row['NumeroDocumento'], $fecha_actual, $hora_actual
                );

                if (mysqli_stmt_execute($stmt_ins)) {
                    $stats['exitos']++;
                    $stats['dinero'] += (float)$monto;
                    $item['status'] = 'success';
                    $item['msg'] = htmlspecialchars($row['Apellido'] . ' ' . $row['Nombre'], ENT_QUOTES, 'UTF-8');
                } else {
                    $item['msg'] = 'Error al insertar registro';
                    $stats['fallidos']++;
                }
            } else {
                $stats['fallidos']++;
            }

            $procesados[] = $item;
        }

        mysqli_stmt_close($stmt_busqueda);
        mysqli_stmt_close($stmt_ins);

    } catch (Exception $e) {
        $error_msg = "Error al procesar el archivo Excel: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acreditar Saldos 2026 | Sistema Cabal FSR</title>
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

        .audit-badge-ok {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .audit-badge-bad {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            font-weight: 700;
            font-size: 0.75rem;
        }

        [data-theme="dark"] .audit-badge-ok {
            background: rgba(16, 185, 129, 0.22);
            color: #6ee7b7;
        }

        [data-theme="dark"] .audit-badge-bad {
            background: rgba(239, 68, 68, 0.22);
            color: #fca5a5;
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

            <a href="GenerarCargaCredito2026.php" class="btn-nav-action" id="btn-generar-novcar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Generar NOVCAR
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
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                Procesar Excel de Acreditaciones (Versión 2026)
            </h2>
            <p>
                Lectura de la planilla de acreditación mensual, validación por CUIT contra el padrón de beneficiarios y preparación de la tabla operativa <code>acreditar2</code>.
            </p>
        </div>
    </section>

    <?php if (isset($error_msg)): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div><?php echo htmlspecialchars($error_msg); ?></div>
        </div>
    <?php endif; ?>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Subida y Procesamiento de Planilla</h3>
                <p>Seleccione el archivo Excel de suministro para procesar y cargar a la base de datos</p>
            </div>
        </div>
        <div class="modern-card-body">
            <div class="alert-modern warning" style="margin-bottom: 20px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <div>
                    <strong>Atención Operativa:</strong> Al procesar una nueva planilla, la tabla transitoria <code>acreditar2</code> será vaciada automáticamente para alojar el nuevo lote de acreditación.
                </div>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group-modern">
                    <label for="archivo_excel">Planilla Excel (.xlsx):</label>
                    <input type="file" name="archivo_excel" id="archivo_excel" class="form-control-modern" accept=".xlsx" required>
                </div>
                <button type="submit" name="btn_cargar" class="btn-modern-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Procesar y Blanquear Tabla acreditar2
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($procesados)): ?>
        <div class="meta-stats-grid">
            <div class="meta-stat-card">
                <div class="meta-stat-label">Filas Leídas</div>
                <div class="meta-stat-val"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
            </div>
            <div class="meta-stat-card">
                <div class="meta-stat-label">Cargados Correctamente</div>
                <div class="meta-stat-val" style="color: var(--emerald-600);"><?php echo number_format($stats['exitos'], 0, ',', '.'); ?></div>
            </div>
            <div class="meta-stat-card">
                <div class="meta-stat-label">No Encontrados en Padrón</div>
                <div class="meta-stat-val" style="color: #ef4444;"><?php echo number_format($stats['fallidos'], 0, ',', '.'); ?></div>
            </div>
            <div class="meta-stat-card">
                <div class="meta-stat-label">Total Acreditado</div>
                <div class="meta-stat-val" style="color: var(--primary-600);">$<?php echo number_format($stats['dinero'], 2, ',', '.'); ?></div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <a href="GenerarCargaCredito2026.php" class="btn-modern-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                Continuar a Generar Lote NOVCAR.782 &rarr;
            </a>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Detalle de Registros Procesados</h3>
                    <p>Auditoría fila por fila de la planilla importada</p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 520px; overflow-y: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Fila</th>
                                <th>CUIL / CUIT</th>
                                <th>Titular Beneficiario</th>
                                <th>Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procesados as $p): ?>
                            <tr>
                                <td><code>#<?php echo $p['fila']; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($p['cuil']); ?></strong></td>
                                <td><?php echo $p['msg']; ?></td>
                                <td style="font-weight: 700; color: var(--emerald-600);">$<?php echo number_format($p['monto'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($p['status'] == 'success'): ?>
                                        <span class="audit-badge-ok">✓ PROCESADO</span>
                                    <?php else: ?>
                                        <span class="audit-badge-bad">✕ NO ENCONTRADO</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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