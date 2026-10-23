<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Respuesta de Acreditaciones (RespuestaCargas.txt)
 */

require_once("BD/BD.php");

$queArchivo = "archivos/RespuestaCargas.txt";
$archivo_existe = file_exists($queArchivo);
$Movimiento = array();
$TotalDepositado = 0;
$cant_registros = 0;

if ($archivo_existe) {
    $txt_file = fopen($queArchivo, 'r');
    $a = 1;

    while ($line = fgets($txt_file)) {
        if ($a > 1) {
            $cuenta = trim(substr($line, 10, 16));
            if (!empty($cuenta)) {
                $Quien = DevuelvoApellidoyNombrePorNumeroCuenta($cuenta);
                $imp_str = substr($line, 30, 7) . "." . substr($line, 37, 2);
                $imp = floatval($imp_str);
                $cod_error = trim(substr($line, 77, 3));

                $Movimiento[] = array(
                    'cuenta' => $cuenta,
                    'nombre' => $Quien ? $Quien : '(No encontrado)',
                    'importe' => $imp,
                    'error' => $cod_error ? $cod_error : '000'
                );
                $TotalDepositado += $imp;
                $cant_registros++;
            }
        }
        $a++;
    }
    fclose($txt_file);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta de Acreditación de Saldos | Sistema Cabal FSR</title>
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

            <a href="SubirArchivo.html" class="btn-nav-action" id="btn-subir">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Subir Nuevo Archivo
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
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Respuesta de Cargas Bancarias (RespuestaCargas.txt)
            </h2>
            <p>
                Visualización de la devolución emitida por Cabal con el estado de acreditación de cuentas, importes acreditados y códigos de error.
            </p>
        </div>
    </section>

    <?php if (!$archivo_existe): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>Archivo no encontrado:</strong> <code>archivos/RespuestaCargas.txt</code>.<br>
                Súbalo primero desde el <a href="SubirArchivo.html" style="color: inherit; text-decoration: underline;">Centro de Subida de Archivos</a>.
            </div>
        </div>
    <?php else: ?>
        <div class="meta-stats-grid">
            <div class="meta-stat-card">
                <div class="meta-stat-label">Cuentas Procesadas</div>
                <div class="meta-stat-val"><?php echo number_format($cant_registros, 0, ',', '.'); ?></div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">Total Depositado Efectivo</div>
                <div class="meta-stat-val" style="color: var(--emerald-600);">$<?php echo number_format($TotalDepositado, 2, ',', '.'); ?></div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">Archivo Fuente</div>
                <div class="meta-stat-val" style="font-size: 1.15rem; color: var(--navy-700);">RespuestaCargas.txt</div>
            </div>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Detalle de Acreditaciones</h3>
                    <p>Relación de cuenta, titular, monto acreditado y código de respuesta bancario</p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 520px; overflow-y: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Número de Cuenta</th>
                                <th>Apellido y Nombre</th>
                                <th>Importe Acreditado</th>
                                <th>Código Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($Movimiento as $m): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($m['cuenta']); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                                <td style="font-weight: 700; color: var(--emerald-600);">$<?php echo number_format($m['importe'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($m['error'] == '000' || empty($m['error'])): ?>
                                        <span class="badge-tag success">OK (000)</span>
                                    <?php else: ?>
                                        <span class="badge-tag warning">Error <?php echo htmlspecialchars($m['error']); ?></span>
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