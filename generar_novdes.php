<?php
/**
 * Generador NOVDES.782 con Vista Previa
 * PHP 5.6.4 / MySQL
 * Credenciales: pucho / 78900
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- CONFIGURACIÓN DE BASE DE DATOS ---
$db_config = array(
    'host' => 'localhost',
    'user' => 'pucho',
    'pass' => '78900', 
    'name' => 'cabal'
);

$entidad  = "782";
$sucursal = "001"; 
$mensaje  = "";
$contenido_final = "";

// ACCIÓN: DESCARGAR (Cuando se presiona el botón de descarga después de la vista previa)
if (isset($_POST['descargar_archivo'])) {
    $contenido = $_POST['contenido_archivo'];
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="NOVDES.782"');
    echo $contenido;
    exit;
}

// ACCIÓN: PROCESAR (Cuando se sube el CSV)
if (isset($_POST['submit']) && isset($_FILES['archivo_csv'])) {
    
    $conn = mysqli_connect($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if (!$conn) {
        $mensaje = "<div style='color:red; padding:10px; border:1px solid red;'>Error de conexión: " . mysqli_connect_error() . "</div>";
    } else {
        $file = $_FILES['archivo_csv']['tmp_name'];
        
        if (($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle, 1000, ","); // Saltar encabezado
            
            $lineas_detalle = array();
            $cant_reg = 0;
            $suma_total_compras = 0;
            $cual=0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data[1])) continue; 
                $cual++;
                $cuit  = trim($data[1]);
                $monto = floatval($data[3]);

                $sql = "SELECT NumeroCuenta FROM beneficiario WHERE Cuit = '" . mysqli_real_escape_string($conn, $cuit) . "' LIMIT 1";
                $res = mysqli_query($conn, $sql);

                if ($row = mysqli_fetch_assoc($res)) {
                    $cuenta = str_pad(substr($row['NumeroCuenta'], 0, 16), 16, "0", STR_PAD_LEFT);
                    $imp_compras = str_pad(number_format($monto * 100, 0, '', ''), 9, "0", STR_PAD_LEFT);
                    $NumeroCarga= str_pad($cual, 8, "0", STR_PAD_LEFT);

                    $imp_cajeros = "000000000"; 
                    
                    // --- REGISTRO DE DETALLE TE04 ---
                    $registro = "TE04";                     // 1-4
                    $registro .= $entidad . $sucursal;      // 5-10: 782001
                    $registro .= $cuenta;                   // 11-26
                    $registro .= "63";                      // 27-28
                    $registro .= "39";                      // 29-30
                    $registro .= $imp_compras;              // 31-39
                    $registro .= $imp_cajeros;              // 40-48
                    $registro .= "006239";                  // 49-54
                    $registro.=$NumeroCarga;
                    $registro .= str_repeat(" ", 18);       // 55-80 (Filler)

                    $lineas_detalle[] = $registro;
                    $cant_reg++;
                    $suma_total_compras += ($monto * 100);
                }
            }
            fclose($handle);
            mysqli_close($conn);

            if ($cant_reg > 0) {
                // --- HEADER TE01 ---
                $header = "TE04" . $entidad . "000" . str_pad("", 16, "0") . date("dmy") . date("His") . str_repeat(" ", 42);

                // --- TRAILER TE04 ---
                $trailer = "TE04" . $entidad . "000"."9999999999999999" . str_pad($cant_reg, 5, "0", STR_PAD_LEFT) . str_pad($suma_total_compras, 10, "0", STR_PAD_LEFT) . "0000000000".str_repeat(" ", 29);

                // ENSAMBLADO
                $contenido_final = $header . "\r\n";
                foreach ($lineas_detalle as $l) {
                    $contenido_final .= $l . "\r\n";
                }
                $contenido_final .= $trailer . "\r\n";
            } else {
                $mensaje = "<div style='color:orange;'>No se encontraron registros en la BD para los CUITs del CSV.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador NOVDES - Vista Previa</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f0f2f5; padding: 20px; color: #333; }
        .container { background: white; max-width: 900px; margin: 0 auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { font-family: sans-serif; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .preview-box { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre; font-size: 13px; margin-top: 20px; border: 1px solid #000; }
        .btn-process { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-download { background: #27ae60; color: white; border: none; padding: 15px 30px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1.1em; margin-top: 20px; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; font-family: sans-serif; }
        .info { font-family: sans-serif; font-size: 0.9em; color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Generador de Novedades (NOVDES)</h2>
    
    <?php if (!$contenido_final): ?>
        <p class="info">Sube el archivo CSV para procesar y ver la vista previa antes de exportar.</p>
        <?php echo $mensaje; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="archivo_csv" accept=".csv" required style="margin-bottom: 20px; display: block;">
            <button type="submit" name="submit" class="btn-process">PROCESAR CSV</button>
        </form>
    <?php else: ?>
        <div class="alert" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; font-family: sans-serif;">
            ✓ CSV Procesado con éxito. Revisa el contenido abajo.
        </div>

        <h3 style="font-family: sans-serif;">Vista Previa del Archivo (Ancho fijo 80 car.)</h3>
        <div class="preview-box"><?php echo htmlspecialchars($contenido_final); ?></div>

        <form action="" method="post">
            <input type="hidden" name="contenido_archivo" value="<?php echo htmlspecialchars($contenido_final); ?>">
            <button type="submit" name="descargar_archivo" class="btn-download">DESCARGAR ARCHIVO NOVDES.782</button>
            <a href="" style="margin-left: 20px; font-family: sans-serif; color: #e74c3c;">Cancelar y subir otro</a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>