<?php
/**
 * Generador NOVTRAN.782 - Versión Manual Específico
 * Códigos: 62 = Descarga, 63 = Carga
 * Todos los registros inician con TE04
 * Incluye salto de línea al final del archivo
 * PHP 5.6.4 / MySQL
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
$sucursal = "000";
$mensaje  = "";

if (isset($_POST['submit']) && isset($_FILES['archivo_csv'])) {
    
    $conn = mysqli_connect($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if (!$conn) {
        $mensaje = "<div style='color:red; padding:10px; border:1px solid red;'>Error de conexión: " . mysqli_connect_error() . "</div>";
    } else {
        $file = $_FILES['archivo_csv']['tmp_name'];
        
        if (($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle, 1000, ","); // Saltar encabezado del CSV (Nro, Cuit, Nombre, Monto, C-D)
            
            $lineas_detalle = array();
            $cant_reg = 0;
            $balance_neto = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data[1])) continue; 

                $cuit  = trim($data[1]);
                $monto = floatval($data[3]);
                $tipo  = strtoupper(trim($data[4])); 

                // Buscar NumeroCuenta por CUIT
                $sql = "SELECT NumeroCuenta FROM beneficiario WHERE Cuit = '" . mysqli_real_escape_string($conn, $cuit) . "' LIMIT 1";
                $res = mysqli_query($conn, $sql);

                if ($row = mysqli_fetch_assoc($res)) {
                    $cuenta = str_pad(substr($row['NumeroCuenta'], 0, 16), 16, "0", STR_PAD_LEFT);
                    
                    // Códigos solicitados: 62 Descarga, 63 Carga
                    $cod_op = ($tipo == 'C') ? "63" : "62";
                    
                    // Importe 9 posiciones (centavos incluidos, sin puntos)
                    $imp_formateado = str_pad(number_format($monto * 100, 0, '', ''), 9, "0", STR_PAD_LEFT);

                    // --- REGISTRO DE DETALLE (80 caracteres) ---
                    $registro = "TE04";                     // 1-4: ID Registro
                    $registro .= $entidad . $sucursal;      // 5-10: Entidad/Sucursal
                    $registro .= $cuenta;                   // 11-26: Cuenta
                    $registro .= $cod_op;                   // 27-28: CÓDIGO OPERACIÓN (62 o 63)
                    $registro .= "80";                      // 29-30: Subcódigo Op
                    $registro .= $imp_formateado;           // 31-39: Importe
                    $registro .= str_repeat(" ", 39);       // 40-78: Relleno
                    $registro .= "80";                      // 79-80: Subcódigo Traslado

                    $lineas_detalle[] = $registro;
                    $cant_reg++;
                    
                    // Lógica para el balance del trailer
                    if ($tipo == 'C') $balance_neto += ($monto * 100);
                    else $balance_neto -= ($monto * 100);
                }
            }
            fclose($handle);
            mysqli_close($conn);

            if ($cant_reg > 0) {
                // 1. CABECERA (TE04)
                $header = "TE04" . $entidad . $sucursal . date("dmy") . date("His") . str_repeat(" ", 58);
                
                // 2. CIERRE / TRAILER (TE04 según pedido)
                // Cantidad registros (7 pos) + Importe absoluto (15 pos)
                $trailer = "TE04" . $entidad . $sucursal . str_pad($cant_reg, 7, "0", STR_PAD_LEFT) . str_pad(abs($balance_neto), 15, "0", STR_PAD_LEFT) . str_repeat(" ", 48);

                // --- CONSTRUCCIÓN FINAL CON SALTO DE LÍNEA AL FINAL ---
                $final_file = $header . "\r\n";
                foreach ($lineas_detalle as $d) {
                    $final_file .= $d . "\r\n";
                }
                $final_file .= $trailer . "\r\n"; // <--- Salto de línea al final del último registro

                // Forzar descarga del archivo
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="NOVTRAN.782"');
                echo $final_file;
                exit;
            } else {
                $mensaje = "<div style='color:orange; padding:10px; border:1px solid orange;'>No se encontraron cuentas para los CUITs procesados.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador NOVTRAN.782</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; padding: 40px; text-align: center; }
        .box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: inline-block; width: 100%; max-width: 400px; }
        .specs { text-align: left; font-size: 0.85em; background: #fffde7; padding: 10px; border: 1px solid #fff59d; margin-bottom: 20px; }
        input[type="file"] { margin: 20px 0; display: block; width: 100%; }
        button { background: #1b5e20; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background: #2e7d32; }
    </style>
</head>
<body>

<div class="box">
    <h3>Cereo Cabal - NOVTRAN.782</h3>
    <div class="specs">
        <b>Formato de archivo:</b><br>
        • Todos los registros inician con: <b>TE04</b><br>
        • Carga: <b>63</b> / Descarga: <b>62</b><br>
        • Largo de línea: <b>80 car.</b><br>
        • Salto de línea al final del trailer: <b>SÍ</b>
    </div>

    <?php if($mensaje) echo $mensaje; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label>Subir CSV para procesar:</label>
        <input type="file" name="archivo_csv" accept=".csv" required>
        <button type="submit" name="submit">GENERAR Y DESCARGAR</button>
    </form>
</div>

</body>
</html>