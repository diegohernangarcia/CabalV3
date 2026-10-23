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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador NOVTRAN.782 | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 680px;
            margin: 32px auto 50px;
            padding: 0 20px;
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

<main class="page-container">

    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-text">
            <h2>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-500);">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Cereo Cabal - NOVTRAN.782
            </h2>
            <p>
                Generador y descarga directa de transferencias y débitos específicos bajo formato posicional reglamentario.
            </p>
        </div>
    </section>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3>Subir CSV y Emitir Lote</h3>
                <p>Procese su archivo CSV con CUIT, Monto y Tipo (C = Carga, D = Descarga)</p>
            </div>
        </div>
        <div class="modern-card-body">
            <div class="alert-modern info" style="margin-bottom: 20px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <strong>Especificación Técnica Cabal:</strong><br>
                    • Prefijo de registro: <code>TE04</code><br>
                    • Operaciones: <code>63</code> (Carga) / <code>62</code> (Descarga)<br>
                    • Ancho fijo: <code>80 caracteres</code> por fila con salto final CRLF
                </div>
            </div>

            <?php if($mensaje) echo $mensaje; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group-modern">
                    <label for="archivo_csv">Archivo CSV (.csv):</label>
                    <input type="file" name="archivo_csv" id="archivo_csv" class="form-control-modern" accept=".csv" required>
                </div>
                <button type="submit" name="submit" class="btn-modern-primary" style="width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Generar y Descargar NOVTRAN.782
                </button>
            </form>
        </div>
    </div>
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