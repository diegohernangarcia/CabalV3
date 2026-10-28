<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Recepción y Almacenamiento de Archivos Operativos
 * Con verificación y autorenombrado automático de mayúsculas/minúsculas
 */

$directorio = 'archivos/';
if (!is_dir($directorio)) {
    @mkdir($directorio, 0775, true);
}

// Catálogo canónico según "Nombre Requerido" (Mapeo insensible a mayúsculas/minúsculas)
$archivos_requeridos = array(
    'altas.xlsx'          => 'Altas.xlsx',
    'movaltas.txt'        => 'MovAltas.txt',
    'altacargas.xlsx'     => 'AltaCargas.xlsx',
    'acreditaciones.xlsx' => 'Acreditaciones.xlsx',
    'movimientos.txt'     => 'Movimientos.txt',
    'respuestacargas.txt' => 'RespuestaCargas.txt',
    'novcar.782'          => 'novcar.782',
    'maesplas.txt'        => 'MAESPLAS.txt',
    'maespla.txt'         => 'MAESPLAS.txt',
    'descargasaldos.xlsx' => 'DescargaSaldos.xlsx'
);

// Extensiones permitidas por seguridad
$extensiones_permitidas = array('xlsx', 'xls', 'txt', 'csv', '782');

$exito = false;
$mensaje = '';
$nombre_original = '';
$nombre_guardado = '';
$fue_autorenombrado = false;
$tamano_formateado = '0 Bytes';

function formatearTamano($bytes) {
    if ($bytes <= 0) return '0 Bytes';
    $unidades = array('Bytes', 'KB', 'MB', 'GB');
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $unidades[$i];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['subir_archivo'])) {
    $archivo = $_FILES['subir_archivo'];

    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $nombre_original = basename($archivo['name']);
        $tamano = (int)$archivo['size'];
        $tamano_formateado = formatearTamano($tamano);

        $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        if (!in_array($ext, $extensiones_permitidas)) {
            $mensaje = "Extensión no permitida (.{$ext}). Por motivos de seguridad solo se admiten archivos .xlsx, .xls, .txt, .csv o .782.";
        } else {
            $nombre_original_lower = strtolower($nombre_original);
            $nombre_guardado = $nombre_original;

            // 1. Verificar si coincide con el catálogo canónico (case-insensitive)
            if (isset($archivos_requeridos[$nombre_original_lower])) {
                $nombre_guardado = $archivos_requeridos[$nombre_original_lower];
            } elseif (!empty($_POST['nombre_normalizado'])) {
                // 2. Revisar si vino especificado por el frontend
                $sugerido = basename($_POST['nombre_normalizado']);
                $sugerido_lower = strtolower($sugerido);
                if (isset($archivos_requeridos[$sugerido_lower])) {
                    $nombre_guardado = $archivos_requeridos[$sugerido_lower];
                }
            }

            if ($nombre_original !== $nombre_guardado) {
                $fue_autorenombrado = true;
            }

            $ruta_destino = $directorio . $nombre_guardado;

            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                @chmod($ruta_destino, 0664);
                $exito = true;
                $mensaje = "El archivo se cargó correctamente en el servidor.";
            } else {
                $mensaje = "No se pudo guardar el archivo en la carpeta de destino ({$directorio}). Verifique los permisos de escritura en el servidor.";
            }
        }
    } else {
        switch ($archivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $mensaje = "El archivo excede el tamaño máximo permitido (10 MB).";
                break;
            case UPLOAD_ERR_NO_FILE:
                $mensaje = "No se seleccionó ningún archivo para subir.";
                break;
            default:
                $mensaje = "Ocurrió un error al subir el archivo (Código de error: {$archivo['error']}).";
                break;
        }
    }
} else {
    $mensaje = "No se recibieron datos de subida.";
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $exito ? "Archivo Recibido" : "Error en la Carga"; ?> | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Gestor de Modo Oscuro / Claro -->
    <script src="js/theme.js"></script>
    <style>
        .result-container {
            max-width: 680px;
            margin: 50px auto 40px;
            padding: 0 20px;
        }
        .result-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
            text-align: center;
            padding: 40px 32px;
        }
        .status-icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .status-icon-circle.success {
            background: #dcfce7;
            color: #15803d;
        }
        .status-icon-circle.error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .details-box {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 18px 24px;
            margin: 24px 0;
            text-align: left;
            font-size: 0.9rem;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed var(--border-light);
        }
        .details-row:last-child {
            border-bottom: none;
        }
        .details-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        .details-val {
            font-weight: 700;
            color: var(--navy-900);
            font-family: 'JetBrains Mono', monospace;
        }
        .rename-notice {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin: 18px 0 6px;
            text-align: left;
            font-size: 0.86rem;
            line-height: 1.5;
        }
        .btn-group-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 28px;
        }
    </style>
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <a href="index.php" class="brand-group">
            <div class="brand-logo-wrap">
                <img src="img/cabal.png" alt="Logo Cabal" onerror="this.src='img/favicon.png'">
            </div>
            <div class="brand-text">
                <h1>CABAL FSR <span class="brand-badge">Entidad 782</span></h1>
                <p>Familia Rionegrina Solidaria • SeNAF Río Negro</p>
            </div>
        </a>
        <div class="nav-actions">
            <a href="SubirArchivo.html" class="btn-nav-action primary">Subir Otro Archivo</a>
            <a href="index.php" class="btn-nav-action">Ir al Tablero</a>
        </div>
    </div>
</header>

<main class="result-container">
    <div class="result-card">
        <?php if ($exito): ?>
            <div class="status-icon-circle success">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--navy-900); margin-bottom: 8px;">
                ¡Archivo Subido con Éxito!
            </h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                El archivo se encuentra disponible en la carpeta de intercambio para ser procesado por el sistema.
            </p>

            <div class="details-box">
                <div class="details-row">
                    <span class="details-label">Nombre en Servidor:</span>
                    <span class="details-val" style="color: var(--emerald-700);"><?php echo htmlspecialchars($nombre_guardado); ?></span>
                </div>
                <?php if ($fue_autorenombrado): ?>
                <div class="details-row">
                    <span class="details-label">Nombre Original Enviado:</span>
                    <span class="details-val" style="color: var(--text-muted); text-decoration: line-through;"><?php echo htmlspecialchars($nombre_original); ?></span>
                </div>
                <?php endif; ?>
                <div class="details-row">
                    <span class="details-label">Destino Operativo:</span>
                    <span class="details-val">archivos/<?php echo htmlspecialchars($nombre_guardado); ?></span>
                </div>
                <div class="details-row">
                    <span class="details-label">Tamaño:</span>
                    <span class="details-val"><?php echo htmlspecialchars($tamano_formateado); ?></span>
                </div>
            </div>

            <?php if ($fue_autorenombrado): ?>
            <div class="rename-notice">
                <div style="font-weight: 700; margin-bottom: 4px;">
                    ✓ Autocorrección de Mayúsculas/Minúsculas Aplicada
                </div>
                El archivo original <code><?php echo htmlspecialchars($nombre_original); ?></code> fue <strong>autorenombrado a <code><?php echo htmlspecialchars($nombre_guardado); ?></code></strong> siguiendo la lista oficial de "Nombre Requerido" para garantizar la compatibilidad con el servidor Linux.
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="status-icon-circle error">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--navy-900); margin-bottom: 8px;">
                Error al Subir el Archivo
            </h2>
            <p style="color: #b91c1c; font-size: 0.95rem; font-weight: 600; margin-bottom: 20px;">
                <?php echo htmlspecialchars($mensaje); ?>
            </p>
        <?php endif; ?>

        <div class="btn-group-actions">
            <?php if ($exito && $nombre_guardado === 'RespuestaCargas.txt'): ?>
            <a href="ProcesarCarga.php" class="btn-nav-action primary" style="padding: 10px 20px; font-size: 0.92rem; background: var(--emerald-600); border-color: var(--emerald-600);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                Verificar Respuesta Carga
            </a>
            <?php endif; ?>
            <a href="SubirArchivo.html" class="btn-nav-action primary" style="padding: 10px 20px; font-size: 0.92rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Subir Otro Archivo
            </a>
            <a href="index.php" class="btn-nav-action" style="padding: 10px 20px; font-size: 0.92rem;">
                Ir al Tablero Principal
            </a>
            <a href="menu.php" class="btn-nav-action" style="padding: 10px 20px; font-size: 0.92rem;">
                Volver al Menú
            </a>
        </div>
    </div>
</main>

</body>
</html>