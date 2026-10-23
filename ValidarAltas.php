<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Validador Posicional de Lote de Altas (128 bytes + CRLF = 130 bytes)
 * Entidad 782 - Cabal
 */

$archivosPosibles = array("Altas.txt", "archivos/Altas.txt", "ALTAUSUCBPRE.782", "archivos/ALTAUSUCBPRE.782");
$queArchivo = "";
foreach ($archivosPosibles as $posible) {
    if (file_exists($posible)) {
        $queArchivo = $posible;
        break;
    }
}

$archivoExiste = !empty($queArchivo);
$filas = array();
$totalLineas = 0;
$lineasValidas = 0;
$lineasConError = 0;
$tieneError = false;

if ($archivoExiste) {
    $handle = fopen($queArchivo, "r");
    if ($handle) {
        $num = 0;
        while (($line = fgets($handle)) !== false) {
            $num++;
            $totalLineas++;
            $longitudReal = strlen($line);
            // 128 bytes de datos + 2 bytes CRLF = 130
            $esCorrecto = ($longitudReal === 130);
            if ($esCorrecto) {
                $lineasValidas++;
            } else {
                $lineasConError++;
                $tieneError = true;
            }

            $filas[] = array(
                'num' => $num,
                'longitud' => $longitudReal,
                'esperado' => 130,
                'correcto' => $esCorrecto,
                'tipo_registro' => substr($line, 0, 4) . ' (' . substr($line, 14, 2) . ')'
            );
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Lote de Altas — Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-status.valido {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-status.invalido {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        [data-theme="dark"] .badge-status.valido {
            color: #34d399;
        }
        [data-theme="dark"] .badge-status.invalido {
            color: #f87171;
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
            <a href="GenerarNovedadAltas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Generador Altas
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1000px; padding-top: 24px;">

    <?php if (!$archivoExiste): ?>
    <div class="alert-modern warning" style="margin-bottom: 24px;">
        <strong>Archivo no encontrado:</strong> No se detectó <code>Altas.txt</code> en la raíz ni en la carpeta <code>archivos/</code>. Genere primero el lote desde el módulo de Altas.
    </div>
    <?php else: ?>
        <div class="alert-modern <?php echo $tieneError ? 'danger' : 'success'; ?>" style="margin-bottom: 24px;">
            <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">
                <?php echo $tieneError ? '❌ El archivo posee errores de longitud posicional' : '✅ El archivo respeta estrictamente el formato Cabal (128 bytes + CRLF)'; ?>
            </div>
            <div style="font-size: 0.9rem;">
                Archivo verificado: <code><?php echo htmlspecialchars($queArchivo, ENT_QUOTES, 'UTF-8'); ?></code>
            </div>
        </div>

        <!-- TARJETAS DE MÉTRICAS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Total Líneas Auditadas
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                    <?php echo number_format($totalLineas, 0, ',', '.'); ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Líneas Válidas (130 B)
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                    <?php echo number_format($lineasValidas, 0, ',', '.'); ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Líneas Incorrectas
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: <?php echo $lineasConError > 0 ? '#dc2626' : '#059669'; ?>;">
                    <?php echo number_format($lineasConError, 0, ',', '.'); ?>
                </div>
            </div>
        </div>

        <!-- TABLA DE DETALLE -->
        <div class="modern-card">
            <div class="card-header-custom" style="padding: 18px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                    Auditoría Línea por Línea
                </h3>
            </div>
            <div style="overflow-x: auto; max-height: 480px;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Línea</th>
                            <th>Tipo Registro</th>
                            <th>Longitud Real (Bytes)</th>
                            <th>Longitud Esperada</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filas as $f): ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $f['num']; ?></td>
                            <td><code><?php echo htmlspecialchars($f['tipo_registro'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td style="font-weight: 700; color: <?php echo $f['correcto'] ? 'inherit' : '#dc2626'; ?>;">
                                <?php echo $f['longitud']; ?> bytes
                            </td>
                            <td><?php echo $f['esperado']; ?> bytes (128 + CRLF)</td>
                            <td>
                                <span class="badge-status <?php echo $f['correcto'] ? 'valido' : 'invalido'; ?>">
                                    <?php echo $f['correcto'] ? 'Válido' : 'Incorrecto'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</main>

<a href="javascript:history.back()" class="btn-modern-flotante" title="Volver atrás">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver
</a>

</body>
</html>