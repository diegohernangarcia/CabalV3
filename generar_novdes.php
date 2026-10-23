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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador NOVDES.782 | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1000px;
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
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--amber-500);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Generador de Novedades de Descarga (NOVDES.782)
            </h2>
            <p>
                Procesamiento de CSV y generación de lote de débito / descarga con vista previa posicional de 80 bytes.
            </p>
        </div>
    </section>

    <div class="modern-card">
        <div class="modern-card-header">
            <div class="modern-card-title-group">
                <h3><?php echo !$contenido_final ? 'Subir Planilla CSV' : 'Vista Previa del Lote Generado'; ?></h3>
                <p>Ancho reglamentario posicional de 80 caracteres por fila</p>
            </div>
        </div>
        <div class="modern-card-body">
            <?php if (!$contenido_final): ?>
                <div class="alert-modern info">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <div>Sube el archivo CSV para procesar y ver la vista previa antes de exportar al mainframe Cabal.</div>
                </div>

                <?php if($mensaje) echo $mensaje; ?>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group-modern">
                        <label for="archivo_csv">Archivo CSV (.csv):</label>
                        <input type="file" name="archivo_csv" id="archivo_csv" class="form-control-modern" accept=".csv" required>
                    </div>
                    <button type="submit" name="submit" class="btn-modern-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Procesar CSV y Ver Vista Previa
                    </button>
                </form>
            <?php else: ?>
                <div class="alert-modern success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <div>
                        <strong>✓ CSV Procesado con éxito.</strong> Revisa el contenido posicional del lote en el visor inferior.
                    </div>
                </div>

                <div class="batch-terminal-output"><?php echo htmlspecialchars($contenido_final); ?></div>

                <form action="" method="post" style="margin-top: 20px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <input type="hidden" name="contenido_archivo" value="<?php echo htmlspecialchars($contenido_final); ?>">
                    <button type="submit" name="descargar_archivo" class="btn-modern-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Descargar Archivo NOVDES.782
                    </button>
                    <a href="generar_novdes.php" class="btn-modern-secondary">Procesar otro archivo</a>
                </form>
            <?php endif; ?>
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