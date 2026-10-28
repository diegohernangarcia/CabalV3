<?php
/**
 * Conversor Cabal ALTAUSUCBPRE.782
 * Formato: Fijo 128 bytes - 3 Registros por usuario
 * Entidad 782 - Cabal FSR
 */

$entidad = "782";
$sucursal = "001"; 
$prefijo = str_pad($entidad, 6, "0", STR_PAD_LEFT); 
$eeesss = $entidad . $sucursal;

$output = "";
$total_titulares = 0;
$total_lineas = 0;
$error_msg = "";

function formatText($text, $len, $type = "X") {
    $text = strtoupper($text);
    $text = str_replace("Ñ", "#", $text);
    if ($type == "N") {
        return str_pad(substr(preg_replace('/[^0-9]/', '', $text), 0, $len), $len, "0", STR_PAD_LEFT);
    }
    return str_pad(substr($text, 0, $len), $len, " ", STR_PAD_RIGHT);
}

function getTipoDoc($tipo) {
    $tipo = strtoupper(trim($tipo));
    $map = array("LE" => "01", "LC" => "02", "DNI" => "03", "CI" => "04", "DNE" => "05");
    return isset($map[$tipo]) ? $map[$tipo] : "03";
}

if (isset($_POST['submit']) && isset($_FILES['excel_file'])) {
    if ($_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $formulario = 1;
            // Saltar cabecera si existe
            $header = fgetcsv($handle, 1000, ","); 
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data[0]) && empty($data[1]) && empty($data[15])) {
                    continue;
                }
                
                $apellido = formatText($data[0], 16);
                $nombre = formatText($data[1], 8);
                $apynom = str_pad($apellido . "/" . $nombre, 25, " ", STR_PAD_RIGHT);
                
                $calle = formatText($data[2], 25);
                $nro = formatText($data[3], 5, "N");
                $piso = formatText($data[4], 2, "X");
                $depto = formatText($data[5], 2, "X");
                $localidad = formatText($data[6], 30);
                $pcia = str_pad($data[7], 2, "0", STR_PAD_LEFT);
                $sexo = (isset($data[8][0]) && strtoupper($data[8][0]) == 'F') ? 'F' : 'M';
                $form_str = str_pad($formulario, 5, "0", STR_PAD_LEFT);

                // --- REGISTRO 1: Domicilio y Filiación ---
                $reg1 = "TE01" . $prefijo . "600000000001" . $eeesss . $apynom . $calle . $nro . $piso . $depto . $localidad . $pcia . "000" . $sexo . $form_str;
                $output .= str_pad($reg1, 128, " ", STR_PAD_RIGHT) . "\r\n";

                // --- REGISTRO 2: Documento y Datos de Contacto ---
                $cp = str_pad($data[9], 8, " ", STR_PAD_RIGHT);
                $tel_car = str_pad(preg_replace('/[^0-9]/', '', $data[10]), 5, "0", STR_PAD_LEFT);
                $tel_nro = str_pad(preg_replace('/[^0-9]/', '', $data[11]), 8, "0", STR_PAD_LEFT);
                $est_civil = isset($data[12][0]) ? strtoupper($data[12][0]) : 'S';
                $fec_nac = date("dmY", strtotime($data[13]));
                $tipo_doc = getTipoDoc($data[14]);
                $num_doc = str_pad(preg_replace('/[^0-9]/', '', $data[15]), 11, "0", STR_PAD_LEFT);
                $pcia_doc = str_pad($data[16], 2, "0", STR_PAD_LEFT);
                $cuit = str_pad(preg_replace('/[^0-9]/', '', $data[17]), 11, "0", STR_PAD_LEFT);
                
                $reg2 = "TE01" . $prefijo . "600000000002" . $cp . $tel_car . $tel_nro . $est_civil . $fec_nac . $tipo_doc . $num_doc . $pcia_doc . $cuit . str_repeat("0", 13) . "0000000" . str_repeat(" ", 25) . $form_str;
                $output .= str_pad($reg2, 128, " ", STR_PAD_RIGHT) . "\r\n";

                // --- REGISTRO 3: Parámetros Operativos ---
                $reg3 = "TE01" . $prefijo . "600000000003" . "99" . "P" . "S" . "S" . " " . str_repeat(" ", 85) . "A" . "1" . str_repeat(" ", 5) . "TPC" . $form_str;
                $output .= str_pad($reg3, 128, " ", STR_PAD_RIGHT) . "\r\n";

                $formulario++;
            }
            fclose($handle);

            $total_titulares = $formulario - 1;
            $total_lineas = $total_titulares * 3;
            
            if (isset($_POST['download']) && $_POST['download'] == "1") {
                header('Content-Type: text/plain; charset=iso-8859-1');
                header('Content-Disposition: attachment; filename="ALTAUSUCBPRE.' . $entidad . '"');
                header('Content-Length: ' . strlen($output));
                echo $output;
                exit;
            }
        } else {
            $error_msg = "No se pudo leer el archivo cargado.";
        }
    } else {
        $error_msg = "Error en la subida del archivo CSV.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <link rel="icon" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="../js/theme.js"></script>
    <style>
        .terminal-block {
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.82rem;
            line-height: 1.5;
            padding: 20px;
            border-radius: var(--radius-md);
            overflow-x: auto;
            white-space: pre;
            border: 1px solid rgba(56, 189, 248, 0.2);
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
            max-height: 520px;
        }
        [data-theme="dark"] .terminal-block {
            background: #020617;
            border-color: rgba(56, 189, 248, 0.3);
        }
    </style>
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <a href="../index.php" class="brand-group" id="brand-link">
            <div class="brand-logo-wrap">
                <img src="../img/cabal.png" alt="Logo Cabal" onerror="this.src='../img/favicon.png'">
            </div>
            <div class="brand-text">
                <h1>
                    CABAL FSR
                    <span class="brand-badge">Entidad <?php echo htmlspecialchars($entidad); ?></span>
                </h1>
                <p>Familia Rionegrina Solidaria • SeNAF Río Negro</p>
            </div>
        </a>

        <div class="nav-actions">
            <a href="../index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>
            <a href="../GenerarNovedadAltas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Altas desde BD
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1100px; padding-top: 24px;">

    <?php if (!empty($error_msg)): ?>
    <div class="alert-modern danger" style="margin-bottom: 20px;">
        <strong>Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
    </div>
    <?php endif; ?>

    <div class="modern-card" style="margin-bottom: 24px;">
        <div class="card-header-custom" style="padding: 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800); margin-bottom: 6px;">
                ⚙️ Generador de Lote Bancario ALTAUSUCBPRE.<?php echo htmlspecialchars($entidad); ?> (Versión 2 CSV)
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                Genera lotes de 128 bytes por registro (3 registros por cada titular) a partir de una planilla CSV de altas.
            </p>
        </div>

        <div class="card-body-custom" style="padding: 28px;">
            <form method="post" enctype="multipart/form-data" id="form-altas-v2">
                <input type="hidden" name="download" id="download_field" value="0">

                <div style="margin-bottom: 24px;">
                    <label for="excel_file" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-main);">
                        Seleccionar Archivo CSV de Altas:
                    </label>
                    <input type="file" name="excel_file" id="excel_file" accept=".csv" required
                           style="width: 100%; padding: 12px; border: 2px dashed var(--border-light); border-radius: var(--radius-md); background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                    <small style="display: block; margin-top: 6px; color: var(--text-muted); font-size: 0.8rem;">
                        Columnas requeridas: Apellido, Nombre, Calle, Nro, Piso, Depto, Localidad, Pcia, Sexo, CP, TelCaract, TelNro, EstCivil, FecNac, TipoDoc, NumDoc, ProvDoc, CUIT
                    </small>
                </div>

                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <button type="submit" name="submit" value="view" onclick="document.getElementById('download_field').value='0';"
                            class="btn-modern-primary" style="padding: 12px 24px; font-weight: 600; border: none; border-radius: var(--radius-sm); cursor: pointer;">
                        👁️ Ver en Pantalla
                    </button>
                    <button type="submit" name="submit" value="download" onclick="document.getElementById('download_field').value='1';"
                            class="btn-modern-secondary" style="padding: 12px 24px; font-weight: 600; border-radius: var(--radius-sm); cursor: pointer; background: var(--emerald-600); color: #fff; border: none;">
                        💾 Descargar ALTAUSUCBPRE.<?php echo htmlspecialchars($entidad); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($output)): ?>
    <!-- MÉTRICAS DEL LOTE -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Total Titulares Procesados
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                <?php echo number_format($total_titulares, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Registros Generados (x3)
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #2563eb;">
                <?php echo number_format($total_lineas, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Longitud por Registro
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                128 bytes (CRLF)
            </div>
        </div>
    </div>

    <!-- VISTA PREVIA DEL LOTE -->
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 18px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                Vista Previa del Archivo Batch Posicional
            </h3>
            <button type="button" onclick="copiarLote()" class="btn-modern-primary" style="padding: 6px 14px; font-size: 0.8rem; border-radius: var(--radius-sm); border: none; cursor: pointer;">
                📋 Copiar al Portapapeles
            </button>
        </div>
        <div style="padding: 20px;">
            <pre class="terminal-block" id="lote-preview"><?php echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8'); ?></pre>
        </div>
    </div>

    <script>
        function copiarLote() {
            var pre = document.getElementById('lote-preview');
            navigator.clipboard.writeText(pre.innerText).then(function() {
                alert('Lote copiado exitosamente al portapapeles.');
            });
        }
    </script>
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