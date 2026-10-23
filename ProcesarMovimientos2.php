<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Movimientos de Tarjetas (Movimientos.txt) hacia tabla movimientos
 */

require_once("BD/BD.php");
require_once("BD/UsarGestion.php");

$mes = isset($_POST['Mes']) ? htmlspecialchars($_POST['Mes']) : '';
$anio = isset($_POST['Anio']) ? htmlspecialchars($_POST['Anio']) : '';
$Archivo = isset($_POST['Archivo']) ? htmlspecialchars($_POST['Archivo']) : '';
$ya_procesado = false;
$error_archivo = false;
$total_registros = 0;
$guardados_ok = 0;

if (empty($mes) || empty($anio) || empty($Archivo)) {
    header("Location: ProcesarMovimientos.html");
    exit;
}

if (EstaFechaMovimiento($mes, $anio, $Archivo) == true) {
    $ya_procesado = true;
}

$queArchivo = "archivos/Movimientos.txt";
$filas_datos = array();

if (!$ya_procesado) {
    if (!file_exists($queArchivo)) {
        $error_archivo = true;
    } else {
        $txt_file = fopen($queArchivo, 'r');
        $a = 1;
        while ($line = fgets($txt_file)) {
            $Banco = substr($line, 0, 3);
            $Sucursal = substr($line, 3, 3);
            $Cuenta = trim(substr($line, 6, 13));
            $Tarjeta = trim(substr($line, 19, 16));
            $TipoDoc = substr($line, 35, 1);
            $Documento = trim(substr($line, 36, 11));
            $NombreApellido = trim(substr($line, 47, 25));
            $CodigoOperacion = substr($line, 72, 3);

            if ($CodigoOperacion == "999") $codigo = "SALDO INICIAL";
            elseif ($CodigoOperacion == "671") $codigo = "CARGA/DESCARGA";
            elseif ($CodigoOperacion == "491") $codigo = "AUTORIZACIONES";
            elseif ($CodigoOperacion == "241") $codigo = "SALDO FINAL";
            else $codigo = $CodigoOperacion;

            $FechaInicial = substr($line, 75, 8);
            $Fecha = substr($FechaInicial, 0, 2) . "/" . substr($FechaInicial, 2, 2) . "/" . substr($FechaInicial, 4, 4);
            $Comercio = trim(substr($line, 83, 25));

            $ImporteInicial = substr($line, 108, 11);
            $Importe = substr($ImporteInicial, 0, 9) . "." . substr($ImporteInicial, 9, 2);
            $Signo = substr($line, 119, 1);

            $ImporteInicial2 = substr($line, 131, 17);
            $Importe2 = substr($ImporteInicial2, 0, 15) . "." . substr($ImporteInicial2, 15, 2);
            $Signo2 = substr($line, 148, 1);

            $filas_datos[] = array(
                'banco' => $Banco,
                'sucursal' => $Sucursal,
                'cuenta' => $Cuenta,
                'tarjeta' => $Tarjeta,
                'tipo_doc' => $TipoDoc,
                'documento' => $Documento,
                'nombre' => $NombreApellido,
                'codigo' => $codigo,
                'fecha' => $Fecha,
                'comercio' => $Comercio,
                'importe' => $Importe,
                'signo' => $Signo,
                'importe2' => $Importe2,
                'signo2' => $Signo2
            );
            $a++;
        }
        fclose($txt_file);

        $total_registros = count($filas_datos);

        // Guardar en la base de datos
        $my_Insert_Statement = $conn->prepare("INSERT INTO movimientos (Banco,Sucursal,Cuenta,Tarjeta,TipoDocumento,Documento,NombreApellido,CodigoOperacion,Fecha,Comercio,Importe,Signo,Importe2,Signo2,Mes,Anio,Archivo) VALUES (:Banco,:Sucursal,:Cuenta,:Tarjeta,:TipoDocumento,:Documento,:NombreApellido,:CodigoOperacion,:Fecha,:Comercio,:Importe,:Signo,:Importe2,:Signo2,:Mes,:Anio,:Archivo)");

        foreach ($filas_datos as $f) {
            $my_Insert_Statement->bindParam(':Banco', $f['banco']);
            $my_Insert_Statement->bindParam(':Sucursal', $f['sucursal']);
            $my_Insert_Statement->bindParam(':Cuenta', $f['cuenta']);
            $my_Insert_Statement->bindParam(':Tarjeta', $f['tarjeta']);
            $my_Insert_Statement->bindParam(':TipoDocumento', $f['tipo_doc']);
            $my_Insert_Statement->bindParam(':Documento', $f['documento']);
            $my_Insert_Statement->bindParam(':NombreApellido', $f['nombre']);
            $my_Insert_Statement->bindParam(':CodigoOperacion', $f['codigo']);
            $my_Insert_Statement->bindParam(':Fecha', $f['fecha']);
            $my_Insert_Statement->bindParam(':Comercio', $f['comercio']);
            $my_Insert_Statement->bindParam(':Importe', $f['importe']);
            $my_Insert_Statement->bindParam(':Signo', $f['signo']);
            $my_Insert_Statement->bindParam(':Importe2', $f['importe2']);
            $my_Insert_Statement->bindParam(':Signo2', $f['signo2']);
            $my_Insert_Statement->bindParam(':Mes', $mes);
            $my_Insert_Statement->bindParam(':Anio', $anio);
            $my_Insert_Statement->bindParam(':Archivo', $Archivo);

            if ($my_Insert_Statement->execute()) {
                $guardados_ok++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesamiento de Movimientos | Sistema Cabal FSR</title>
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

            <a href="ReporteMovimientos.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Reporte Mensual
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
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Procesamiento de Movimientos: <?php echo htmlspecialchars($mes . " " . $anio); ?> (Carga <?php echo htmlspecialchars($Archivo); ?>)
            </h2>
            <p>
                Resultado de la lectura del extracto de movimientos y grabación en la base de datos de auditoría.
            </p>
        </div>
    </section>

    <?php if ($ya_procesado): ?>
        <div class="alert-modern warning">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <div>
                <strong>Período ya registrado:</strong> El mes de <strong><?php echo htmlspecialchars($mes . " de " . $anio); ?></strong> con la carga <strong><?php echo htmlspecialchars($Archivo); ?></strong> ya fue procesado y se encuentra actualmente cargado en la base de datos.
                <div style="margin-top: 14px;">
                    <a href="ReporteMovimientos.php" class="btn-modern-primary">Ver Reporte Mensual</a>
                    <a href="ProcesarMovimientos.html" class="btn-modern-secondary" style="margin-left: 8px;">Volver Atrás</a>
                </div>
            </div>
        </div>
    <?php elseif ($error_archivo): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>Archivo no encontrado:</strong> <code>archivos/Movimientos.txt</code>.<br>
                Súbalo primero desde el <a href="SubirArchivo.html" style="color: inherit; text-decoration: underline;">Centro de Subida de Archivos</a>.
            </div>
        </div>
    <?php else: ?>
        <div class="meta-stats-grid">
            <div class="meta-stat-card">
                <div class="meta-stat-label">Movimientos Leídos</div>
                <div class="meta-stat-val"><?php echo number_format($total_registros, 0, ',', '.'); ?></div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">Guardados en BD</div>
                <div class="meta-stat-val" style="color: var(--emerald-600);"><?php echo number_format($guardados_ok, 0, ',', '.'); ?></div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">Período</div>
                <div class="meta-stat-val" style="font-size: 1.25rem; color: var(--navy-800);"><?php echo htmlspecialchars($mes . " " . $anio); ?></div>
            </div>
        </div>

        <div class="alert-modern success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>
                <strong>¡Movimientos guardados exitosamente en la base de datos!</strong>
                <a href="ReporteMovimientos.php" style="margin-left: 10px; color: inherit; text-decoration: underline; font-weight: 700;">Ver Reporte Mensual &rarr;</a>
            </div>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Extracto de Movimientos Procesados</h3>
                    <p>Detalle de consumos y operaciones importadas</p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 520px; overflow-y: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cuenta</th>
                                <th>Tarjeta</th>
                                <th>Titular</th>
                                <th>Operación</th>
                                <th>Comercio</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas_datos as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['fecha']); ?></td>
                                <td><strong><?php echo htmlspecialchars($d['cuenta']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($d['tarjeta']); ?></code></td>
                                <td><?php echo htmlspecialchars($d['nombre']); ?></td>
                                <td><span class="badge-tag accent"><?php echo htmlspecialchars($d['codigo']); ?></span></td>
                                <td><?php echo htmlspecialchars($d['comercio']); ?></td>
                                <td style="font-weight: 700; color: <?php echo ($d['signo'] == '-' ? '#ef4444' : 'var(--emerald-600)'); ?>;">
                                    <?php echo $d['signo'] . "$" . number_format((float)$d['importe'], 2, ',', '.'); ?>
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