<?php
// Definir carpeta de destino usando ruta absoluta
$targetDir = __DIR__ . "/archivos/";

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file"])) {

    // Crear carpeta si no existe
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["file"]["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Nombre final fijo
    $targetFilePath = $targetDir . "Altas.xlsx";

    // Validar extensión
    if ($fileType === "xlsx") {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            $mensaje = "
            <div class='alert-modern success'>
                <div>
                    ✅ El archivo fue subido correctamente como <b>Altas.xlsx</b>.
                    <div style='margin-top: 12px;'>
                        <a href='CargaAltasDeExcelaBD.php' class='btn-modern-primary'>Procesar Altas a BD</a>
                        <a href='index.php' class='btn-modern-secondary' style='margin-left: 8px;'>Volver al inicio</a>
                    </div>
                </div>
            </div>";
        } else {
            $mensaje = "<div class='alert-modern danger'>❌ Error al mover el archivo al servidor.</div>";
        }
    } elseif ($fileType === "xls" || $fileType === "ods") {
        $mensaje = "
        <div class='alert-modern warning'>
            ⚠️ Solo se admite formato <b>.xlsx</b> (Excel 2007/365).<br>
            Convertí tu archivo desde LibreOffice o Excel a <b>.xlsx</b> y volvé a intentarlo.
        </div>";
    } else {
        $mensaje = "
        <div class='alert-modern danger'>
            ❌ Formato de archivo no permitido. Solo se acepta <b>.xlsx</b>.
        </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Archivo de Altas | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 680px;
            margin: 40px auto;
            padding: 0 20px;
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
                <h1>
                    CABAL FSR
                    <span class="brand-badge">Entidad 782</span>
                </h1>
                <p>Familia Rionegrina Solidaria • SeNAF Río Negro</p>
            </div>
        </a>

        <div class="nav-actions">
            <a href="index.php" class="btn-nav-action primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>
            <a href="SubirArchivo.html" class="btn-nav-action">Centro de Subidas</a>
        </div>
    </div>
</header>

<main class="page-container">
    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-600);">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Subir Planilla de Altas (Altas.xlsx)
                </h3>
                <p>Carga de beneficiarios nuevos para procesamiento a base de datos</p>
            </div>
        </div>
        <div class="modern-card-body">
            <?php if (!empty($mensaje)) { echo $mensaje; } ?>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group-modern">
                    <label for="file">Seleccionar archivo Excel (.xlsx):</label>
                    <input type="file" name="file" id="file" class="form-control-modern" accept=".xlsx" required>
                </div>
                <button type="submit" class="btn-modern-primary" style="width: 100%;">Subir Planilla de Altas</button>
            </form>
        </div>
    </div>
</main>

</body>
</html>
