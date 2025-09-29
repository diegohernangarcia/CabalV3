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
    $targetFilePath = $targetDir . "novcar.782";
    $targetFilePath = strtolower ($targetFilePath);
    // Validar extensión
    if ($fileType === "782") {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            $mensaje = "
            <div class='alert alert-success'>
                ✅ El archivo fue subido correctamente como <b>novcar.782</b>.
                <div class='mt-3'>
                    <a href='index.php' class='btn btn-primary'>Volver al inicio</a>
                </div>
            </div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>❌ Error al mover el archivo.</div>";
        }
    } elseif ($fileType === "xls" || $fileType === "ods" || $fileType === "xlsx"){
        $mensaje = "
        <div class='alert alert-warning'>
            ⚠️ Solo se admite archivo <b>novcar.782</b> <br>
            
        </div>";
    } else {
        $mensaje = "
        <div class='alert alert-danger'>
            ❌ Formato de archivo no permitido. Solo se acepta <b>.xlsx</b>.
        </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Excel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Subir archivo Excel</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensaje)) { echo $mensaje; } ?>
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="file" class="form-label">Seleccionar archivo:</label>
                                <input type="file" name="file" id="file" class="form-control" accept="" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Subir Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
