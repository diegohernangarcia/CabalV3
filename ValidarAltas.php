<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Validador Posicional de Lote de Altas (128 bytes + CRLF = 130 bytes)
 * Entidad 782 - Cabal
 * 
 * Regla de Validación: Los datos a validar se suben exclusivamente en el adjunto de arriba.
 * No se valida con archivos cargados en el sistema con anterioridad.
 */

require_once("BD/Funciones.php");

$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0775, true);
}

$archivoAuditado = false;
$nombreArchivoMostrado = '';
$tamanoArchivo = 0;
$mensajeFeedback = '';
$tipoFeedback = '';
$filas = array();
$totalLineas = 0;
$lineasValidas = 0;
$lineasConError = 0;
$tieneError = false;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';

    if ($accion === 'subir_altas') {
        if (isset($_FILES['archivo_altas']) && $_FILES['archivo_altas']['error'] === UPLOAD_ERR_OK) {
            $nombreOriginal = basename($_FILES['archivo_altas']['name']);
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

            if (in_array($ext, array('782', 'txt', 'dat'))) {
                $fileTmp = $_FILES['archivo_altas']['tmp_name'];
                $fileSize = $_FILES['archivo_altas']['size'];

                if ($fileSize <= 0) {
                    $mensajeFeedback = "El archivo adjuntado está vacío (0 bytes).";
                    $tipoFeedback = "danger";
                } else {
                    $destino = $targetDir . "altas_verificar.782";
                    if (move_uploaded_file($fileTmp, $destino)) {
                        @chmod($destino, 0664);
                        $nombreArchivoMostrado = $nombreOriginal;
                        $tamanoArchivo = $fileSize;
                        $archivoAuditado = true;

                        // Ejecutar auditoría posicional línea por línea
                        $handle = fopen($destino, "r");
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
                                    'tipo_registro' => (strlen($line) >= 4 ? substr($line, 0, 4) : 'N/A') . (strlen($line) >= 16 ? ' (' . substr($line, 14, 2) . ')' : '')
                                );
                            }
                            fclose($handle);
                        }

                        $mensajeFeedback = "Archivo <strong>" . htmlspecialchars($nombreOriginal, ENT_QUOTES, 'UTF-8') . "</strong> recibido y auditado con éxito.";
                        $tipoFeedback = $tieneError ? "warning" : "success";
                    } else {
                        $mensajeFeedback = "Error al guardar el archivo adjuntado en la carpeta de trabajo.";
                        $tipoFeedback = "danger";
                    }
                }
            } else {
                $mensajeFeedback = "Formato no admitido (.{$ext}). Se admiten exclusivamente archivos con extensiones <code>.782</code>, <code>.txt</code> o <code>.dat</code>.";
                $tipoFeedback = "danger";
            }
        } else {
            $errCode = isset($_FILES['archivo_altas']['error']) ? $_FILES['archivo_altas']['error'] : -1;
            $mensajeFeedback = "No se pudo recibir el archivo (Código de error: {$errCode}). Por favor, intente nuevamente.";
            $tipoFeedback = "danger";
        }
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
        .dropzone-altas {
            border: 2px dashed var(--border-light, #cbd5e1);
            border-radius: var(--radius-lg, 12px);
            padding: 36px 20px;
            text-align: center;
            background: var(--surface-subtle, #f8fafc);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dropzone-altas:hover {
            border-color: var(--primary-600, #2563eb);
            background: rgba(37, 99, 235, 0.04);
        }
        .dropzone-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-600, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px auto;
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

    <!-- MENSAJE DE FEEDBACK DE SUBIDA -->
    <?php if (!empty($mensajeFeedback)): ?>
        <div class="alert-modern <?php echo htmlspecialchars($tipoFeedback); ?>" style="margin-bottom: 24px;">
            <div><?php echo $mensajeFeedback; ?></div>
        </div>
    <?php endif; ?>

    <!-- PANEL ADJUNTO SUPERIOR DE CARGA (EL ADJUNTO DE ARRIBA) -->
    <?php if (!$archivoAuditado): ?>
        <div class="modern-card" style="margin-bottom: 24px; padding: 24px;">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                    📁 Adjuntar Archivo de Lote para Validar (ALTAUSUCBPRE.782 / Altas.txt)
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                    Suba el archivo que desea auditar. El sistema validará la longitud posicional estricta de 128 bytes por registro más CRLF (130 bytes).
                </p>
            </div>

            <form action="ValidarAltas.php" method="POST" enctype="multipart/form-data" id="formUploadAltas">
                <input type="hidden" name="accion" value="subir_altas">
                <div class="dropzone-altas" id="dropzoneAltas" onclick="document.getElementById('fileInputAltas').click();">
                    <input type="file" name="archivo_altas" id="fileInputAltas" accept=".782,.txt,.dat" style="display:none;" onchange="document.getElementById('formUploadAltas').submit();">
                    <div class="dropzone-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div style="font-size: 1.08rem; font-weight: 700; color: var(--navy-800); margin-bottom: 4px;">
                        Haga clic aquí o arrastre su archivo <code>ALTAUSUCBPRE.782</code> para auditar
                    </div>
                    <div style="font-size: 0.84rem; color: var(--text-muted);">
                        Formatos soportados: <strong>.782</strong> (Lote Cabal) o <strong>.TXT</strong> (Posicional 128 bytes + CRLF)
                    </div>
                </div>
            </form>

            <div class="alert-modern warning" style="margin-top: 18px; margin-bottom: 0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <div style="font-size: 0.85rem;">
                    <strong>Regla de Verificación:</strong> Los datos a auditar se cargan exclusivamente en el adjunto superior. No se valida con archivos cargados en el sistema con anterioridad.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- BANNER DE ARCHIVO ADJUNTO AUDITADO -->
        <div class="modern-card" style="padding: 18px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; background: rgba(37, 99, 235, 0.05); border: 1.5px solid rgba(37, 99, 235, 0.25);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--primary-600); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    📄
                </div>
                <div>
                    <div style="font-size: 1rem; font-weight: 800; color: var(--navy-800);">
                        Archivo auditado: <code><?php echo htmlspecialchars($nombreArchivoMostrado, ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Tamaño: <strong><?php echo number_format($tamanoArchivo, 0, ',', '.'); ?> bytes</strong> &bull; Subido en este momento por el operador
                    </div>
                </div>
            </div>
            <a href="ValidarAltas.php" class="btn-modern-secondary" style="padding: 10px 18px; font-size: 0.88rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Adjuntar Otro Archivo
            </a>
        </div>

        <div class="alert-modern <?php echo $tieneError ? 'danger' : 'success'; ?>" style="margin-bottom: 24px;">
            <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">
                <?php echo $tieneError ? '❌ El archivo posee errores de longitud posicional' : '✅ El archivo respeta estrictamente el formato Cabal (128 bytes + CRLF = 130 bytes)'; ?>
            </div>
            <div style="font-size: 0.9rem;">
                Resultado de auditoría del archivo subido: <code><?php echo htmlspecialchars($nombreArchivoMostrado, ENT_QUOTES, 'UTF-8'); ?></code>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($archivoAuditado): ?>

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