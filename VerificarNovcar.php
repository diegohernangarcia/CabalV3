<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Verificación y Auditoría de Lote NOVCAR.782 (Entidad 782)
 * Valida formato posicional de 80 bytes por registro y codificación CRLF Windows
 */

require_once("BD/Funciones.php");

function DevuelvoNombrePorCuenta($Cuenta) {
    static $pdo = null;
    if ($pdo === null) {
        $usuario = 'pucho';
        $pass = '78900';
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=cabal;charset=utf8', $usuario, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            return 'Error DB';
        }
    }
    $stmt = $pdo->prepare("SELECT Apellido, Nombre FROM beneficiario WHERE NumeroCuenta = ? LIMIT 1");
    $stmt->execute(array($Cuenta));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return htmlspecialchars($row['Apellido'] . " " . $row['Nombre'], ENT_QUOTES, 'UTF-8');
    }
    return '<span style="color:var(--text-light);">(No asignado)</span>';
}

function pasaramoneda($valor) {
    $largo = strlen($valor);
    if ($largo < 2) return "$0,00";
    $entera = substr($valor, 0, $largo - 2);
    $decimal = substr($valor, $largo - 2, 2);
    $numero = (float)($entera . "." . $decimal);
    return "$" . number_format($numero, 2, ',', '.');
}

function esta_codificado_para_windows($archivo) {
    if (!file_exists($archivo)) return false;
    $contenido = file_get_contents($archivo);
    return (strpos($contenido, "\r\n") !== false);
}

// Ubicaciones posibles de NOVCAR.782
$nombreArchivo = 'Novcar/novcar.782';
if (!file_exists($nombreArchivo)) {
    $nombreArchivo = 'archivos/novcar.782';
}

$archivoExiste = file_exists($nombreArchivo);
$lineasArray = $archivoExiste ? file($nombreArchivo, FILE_IGNORE_NEW_LINES) : false;
$esWindows = $archivoExiste ? esta_codificado_para_windows($nombreArchivo) : false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría y Verificación NOVCAR.782 | Sistema Cabal FSR</title>
    <meta name="description" content="Auditoría de consistencia posicional 80 bytes y saltos CRLF para lotes de acreditación NOVCAR.782">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1400px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .audit-badge-ok {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            font-weight: 700;
            font-size: 0.78rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .audit-badge-bad {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            font-weight: 700;
            font-size: 0.78rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
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

            <a href="GenerarCargaCredito2026.php" class="btn-nav-action" id="btn-generar">
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
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Verificación de Lote NOVCAR.782
            </h2>
            <p>
                Inspección técnica posicional del archivo de acreditaciones: valida ancho de 80 bytes por línea, formato TE04, sumatorias de control y saltos de línea reglamentarios CRLF.
            </p>
        </div>
    </section>

    <?php if (!$archivoExiste || $lineasArray === false): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>Archivo NOVCAR.782 no encontrado.</strong><br>
                Genere el lote primero desde <a href="GenerarCargaCredito2026.php" style="color: inherit; text-decoration: underline;">Generar NOVCAR</a> o súbalo desde <a href="SubirArchivo.html" style="color: inherit; text-decoration: underline;">Subir Archivos</a>.
            </div>
        </div>
    <?php else: 
        $totalLineas = count($lineasArray);
    ?>

        <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 24px;">
            <div class="alert-modern <?php echo $esWindows ? 'success' : 'danger'; ?>" style="flex: 1; margin-bottom: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                <div>
                    <strong>Codificación CRLF:</strong> <?php echo $esWindows ? 'Correcta (Saltos Windows CRLF detectados)' : 'Atención: No se detectaron saltos CRLF'; ?>
                </div>
            </div>

            <div class="alert-modern info" style="flex: 1; margin-bottom: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                <div>
                    <strong>Archivo auditado:</strong> <code><?php echo htmlspecialchars($nombreArchivo); ?></code> (<?php echo $totalLineas; ?> líneas en total)
                </div>
            </div>
        </div>

        <?php
        $i = 0;
        foreach ($lineasArray as $linea):
            $i++;
            $largo = strlen($linea);

            // 1. HEADER
            if ($i == 1):
                $h1 = substr($linea, 0, 4);
                $h2 = substr($linea, 4, 3);
                $h3 = substr($linea, 7, 3);
                $h4 = substr($linea, 10, 16);
                $h5 = substr($linea, 26, 6);
                $fecha = substr($h5, 0, 2) . "/" . substr($h5, 2, 2) . "/" . substr($h5, 4, 2);
                $h6 = substr($linea, 32, 6);
                $hora = substr($h6, 0, 2) . ":" . substr($h6, 2, 2) . ":" . substr($h6, 4, 2);
        ?>
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="modern-card-title-group">
                        <h3>1. Registro de Cabecera (Header)</h3>
                        <p>Identificación de la entidad 782, fecha y hora de emisión del lote</p>
                    </div>
                    <div>
                        <?php if ($largo == 80): ?>
                            <span class="audit-badge-ok">✓ 80 Bytes Reglamentarios</span>
                        <?php else: ?>
                            <span class="audit-badge-bad">✕ Error: <?php echo $largo; ?> Bytes</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modern-card-body" style="padding: 0;">
                    <div class="modern-table-responsive" style="margin: 0; border: none;">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Identificador</th>
                                    <th>Entidad</th>
                                    <th>Sucursal</th>
                                    <th>Relleno Ceros</th>
                                    <th>Fecha Proceso</th>
                                    <th>Hora Proceso</th>
                                    <th>Estado Longitud</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($h1); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($h2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($h3); ?></td>
                                    <td><code><?php echo htmlspecialchars($h4); ?></code></td>
                                    <td><?php echo htmlspecialchars($fecha); ?></td>
                                    <td><?php echo htmlspecialchars($hora); ?></td>
                                    <td>
                                        <?php if ($largo == 80): ?>
                                            <span class="audit-badge-ok">80 Bytes OK</span>
                                        <?php else: ?>
                                            <span class="audit-badge-bad">ERROR: <?php echo $largo; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABLA DE DETALLES -->
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="modern-card-title-group">
                        <h3>2. Registros de Detalle de Acreditación</h3>
                        <p>Movimientos monetarios código 6339 por beneficiario</p>
                    </div>
                </div>
                <div class="modern-card-body" style="padding: 0;">
                    <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 480px; overflow-y: auto;">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Prefijo</th>
                                    <th>Entidad</th>
                                    <th>Número de Cuenta</th>
                                    <th>Titular Beneficiario</th>
                                    <th>Op</th>
                                    <th>Sub</th>
                                    <th>Importe Compras</th>
                                    <th>Importe Cajero</th>
                                    <th>Carga N°</th>
                                    <th>Longitud</th>
                                </tr>
                            </thead>
                            <tbody>
            <?php 
            endif;

            // 2. DETALLE
            if ($i > 1 && $i < $totalLineas):
                $d1 = substr($linea, 0, 4);
                $d2 = substr($linea, 4, 6);
                $d3 = substr($linea, 10, 16);
                $d4 = substr($linea, 26, 2);
                $d5 = substr($linea, 28, 2);
                $d6 = substr($linea, 30, 9);
                $d7 = substr($linea, 39, 9);
                $d9 = substr($linea, 54, 8);
            ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($d1); ?></code></td>
                    <td><?php echo htmlspecialchars($d2); ?></td>
                    <td><strong><?php echo htmlspecialchars($d3); ?></strong></td>
                    <td><?php echo DevuelvoNombrePorCuenta($d3); ?></td>
                    <td><?php echo htmlspecialchars($d4); ?></td>
                    <td><?php echo htmlspecialchars($d5); ?></td>
                    <td style="color: var(--emerald-600); font-weight: 700;"><?php echo pasaramoneda($d6); ?></td>
                    <td><?php echo pasaramoneda($d7); ?></td>
                    <td><code>#<?php echo htmlspecialchars($d9); ?></code></td>
                    <td>
                        <?php if ($largo == 80): ?>
                            <span class="audit-badge-ok">80</span>
                        <?php else: ?>
                            <span class="audit-badge-bad">ERROR <?php echo $largo; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php
            endif;

            // 3. TRAILER
            if ($i == $totalLineas):
            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php
                $t1 = substr($linea, 0, 4);
                $t2 = substr($linea, 4, 3);
                $t3 = substr($linea, 7, 3);
                $t4 = substr($linea, 10, 16);
                $t5 = substr($linea, 26, 5);
                $t6 = substr($linea, 31, 10);
                $t7 = substr($linea, 42, 10);
            ?>
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="modern-card-title-group">
                        <h3>3. Registro de Cierre (Trailer)</h3>
                        <p>Totales de control del lote bancario para conciliación en plataforma</p>
                    </div>
                    <div>
                        <?php if ($largo == 80): ?>
                            <span class="audit-badge-ok">✓ 80 Bytes Reglamentarios</span>
                        <?php else: ?>
                            <span class="audit-badge-bad">✕ Error: <?php echo $largo; ?> Bytes</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modern-card-body" style="padding: 0;">
                    <div class="modern-table-responsive" style="margin: 0; border: none;">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Identificador</th>
                                    <th>Entidad</th>
                                    <th>Sucursal</th>
                                    <th>Identificador Cierre</th>
                                    <th>Cantidad Registros</th>
                                    <th>Sumatoria Compras</th>
                                    <th>Sumatoria Cajeros</th>
                                    <th>Estado Longitud</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($t1); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($t2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t3); ?></td>
                                    <td><code><?php echo htmlspecialchars($t4); ?></code></td>
                                    <td><strong><?php echo (int)$t5; ?></strong></td>
                                    <td style="color: var(--emerald-600); font-weight: 700; font-size: 1rem;"><?php echo pasaramoneda($t6); ?></td>
                                    <td><?php echo pasaramoneda($t7); ?></td>
                                    <td>
                                        <?php if ($largo == 80): ?>
                                            <span class="audit-badge-ok">80 Bytes OK</span>
                                        <?php else: ?>
                                            <span class="audit-badge-bad">ERROR: <?php echo $largo; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php
            endif;
        endforeach;
    endif;
    ?>

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