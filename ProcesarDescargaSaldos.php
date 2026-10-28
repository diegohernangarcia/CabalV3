<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Descarga de Saldos (DescargaSaldos.xlsx) y Emisión NOVDES.782
 */

require_once('phpexcel/PHPExcel.php');
require_once('BD/BD.php');

$archivo = "archivos/DescargaSaldos.xlsx";
$archivo_existe = file_exists($archivo);

$filas_html = array();
$lineas_lote = array();
$sumatoria = 0;
$num = 0;

if ($archivo_existe) {
    try {
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $dia = date("d");
        $mes = date("m");
        $anio = date("y");
        $fecha = $dia . $mes . $anio;
        date_default_timezone_set("America/Mendoza");
        $hora = getdate();
        $horaactual = str_pad($hora["hours"], 2, "0", STR_PAD_LEFT) . str_pad($hora["minutes"], 2, "0", STR_PAD_LEFT) . str_pad($hora["seconds"], 2, "0", STR_PAD_LEFT);

        for ($row = 2; $row <= $highestRow; $row++) {
            $id = $sheet->getCell("A" . $row)->getValue();
            $Cuit = trim((string)$sheet->getCell("B" . $row)->getValue());
            $DNI = trim((string)$sheet->getCell("C" . $row)->getValue());
            $Nombre = trim((string)$sheet->getCell("D" . $row)->getValue());
            $Monto = (float)$sheet->getCell("E" . $row)->getValue();

            if (empty($Cuit) && empty($DNI) && empty($Nombre)) {
                continue;
            }

            $num++;
            $cuenta = DevuelvoNumeroCuenta($DNI, $Cuit);
            $sumatoria += $Monto;

            $filas_html[] = array(
                'id' => $id,
                'cuit' => $Cuit,
                'dni' => $DNI,
                'nombre' => $Nombre,
                'monto' => $Monto,
                'cuenta' => $cuenta,
                'fecha' => $fecha,
                'hora' => $horaactual
            );
        }

        // Construir lote posicional NOVDES (80 bytes)
        $header_lote = "TE04782000" . str_repeat("0", 16) . $fecha . $horaactual . "62" . str_repeat(" ", 40);
        $lineas_lote[] = $header_lote;

        foreach ($filas_html as $f) {
            $cuenta_pad = str_pad(substr($f['cuenta'], 0, 16), 16, "0", STR_PAD_LEFT);
            $imp_pad = formatearNumero9Digitos($f['monto']);
            $detalle_lote = "TE04782001" . $cuenta_pad . "6239" . $imp_pad . str_repeat("0", 23) . str_repeat(" ", 18);
            $lineas_lote[] = $detalle_lote;
        }

        $cant_registros_pad = str_pad($num, 5, "0", STR_PAD_LEFT);
        $trailer_lote = "TE047820009999999999999999" . $cant_registros_pad . formatearNumero10Digitos($sumatoria) . "0000000000" . str_repeat(" ", 29);
        $lineas_lote[] = $trailer_lote;

        $contenido_final_lote = implode("\r\n", $lineas_lote) . "\r\n";

        // Descarga directa opcional
        if (isset($_GET['descargar']) && $_GET['descargar'] == '1') {
            header('Content-Type: text/plain; charset=ISO-8859-1');
            header('Content-Disposition: attachment; filename="NOVDES.782"');
            echo $contenido_final_lote;
            exit;
        }

    } catch (Exception $e) {
        $error_excel = "Error al leer planilla: " . $e->getMessage();
    }
}
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

            <a href="generar_novdes.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Generador CSV
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
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Importar DescargaSaldos.xlsx y Generar NOVDES.782
            </h2>
            <p>
                Lectura de la planilla de débitos/descargas y ensamblado automático del lote bancario posicional 80 bytes (código operación 62).
            </p>
        </div>
    </section>

    <?php if (!$archivo_existe): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>Archivo no encontrado:</strong> <code>archivos/DescargaSaldos.xlsx</code>.<br>
                Súbalo primero desde el <a href="SubirArchivo.html" style="color: inherit; text-decoration: underline;">Centro de Subida de Archivos</a>.
            </div>
        </div>
    <?php elseif (isset($error_excel)): ?>
        <div class="alert-modern danger">
            <div><?php echo htmlspecialchars($error_excel); ?></div>
        </div>
    <?php else: ?>
        <div class="meta-stats-grid">
            <div class="meta-stat-card">
                <div class="meta-stat-label">Registros Procesados</div>
                <div class="meta-stat-val"><?php echo number_format($num, 0, ',', '.'); ?></div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">Total a Descargar</div>
                <div class="meta-stat-val" style="color: #ef4444;">-$<?php echo number_format($sumatoria, 2, ',', '.'); ?></div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">Ancho de Lote</div>
                <div class="meta-stat-val">80 <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">bytes</span></div>
            </div>
        </div>

        <div style="margin-bottom: 24px; display: flex; gap: 14px; flex-wrap: wrap;">
            <a href="ProcesarDescargaSaldos.php?descargar=1" class="btn-modern-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Descargar NOVDES.782
            </a>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Planilla de Descargas</h3>
                    <p>Detalle de beneficiarios, montos a descargar y cuentas asignadas</p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 480px; overflow-y: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>CUIT</th>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Monto a Descargar</th>
                                <th>Número Cuenta</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas_html as $f): ?>
                            <tr>
                                <td><code>#<?php echo htmlspecialchars($f['id']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($f['cuit']); ?></code></td>
                                <td><?php echo htmlspecialchars($f['dni']); ?></td>
                                <td><strong><?php echo htmlspecialchars($f['nombre']); ?></strong></td>
                                <td style="font-weight: 700; color: #ef4444;">-$<?php echo number_format($f['monto'], 2, ',', '.'); ?></td>
                                <td><strong><?php echo htmlspecialchars($f['cuenta']); ?></strong></td>
                                <td><?php echo htmlspecialchars($f['fecha']); ?></td>
                                <td><?php echo htmlspecialchars($f['hora']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Terminal Output -->
        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Lote NOVDES.782 Generado</h3>
                    <p>Estructura posicional fija de 80 bytes por registro con código de operación 62</p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="batch-terminal-output" style="margin: 0; border-radius: 0;"><?php echo htmlspecialchars($contenido_final_lote); ?></div>
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