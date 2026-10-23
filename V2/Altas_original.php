<?php
/**
 * Conversor Cabal ALTAUSUCBPRE.782
 * Formato: Fijo 128 bytes - 3 Registros por usuario
 */

// Configuraciones de la Entidad (Ajustar según corresponda)
$entidad = "782"; // Extraído del nombre de tu archivo
$sucursal = "001"; 
$prefijo = str_pad($entidad, 6, "0", STR_PAD_LEFT); 
$eeesss = $entidad . $sucursal;

if (isset($_POST['submit'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    if (($handle = fopen($file, "r")) !== FALSE) {
        
        $output = "";
        $formulario = 1;
        
        // Saltar cabecera si existe (ajustar según tu CSV)
        $header = fgetcsv($handle, 1000, ","); 
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Mapeo de columnas basado en tu archivo "A Completar.csv"
            // 0:Apellido, 1:Nombre, 2:Calle, 3:Nro, 4:Piso, 5:Depto, 6:Localidad, 7:Pcia, 8:Sexo, 
            // 9:CP, 10:TelCaract, 11:TelNro, 12:EstCivil, 13:FecNac, 14:TipoDoc, 15:NumDoc, 16:ProvDoc, 17:CUIT
            
            $apellido = formatText($data[0], 16);
            $nombre = formatText($data[1], 8); // El total Apellido + / + Nombre es 25
            $apynom = str_pad($apellido . "/" . $nombre, 25, " ", STR_PAD_RIGHT);
            
            $calle = formatText($data[2], 25);
            $nro = formatText($data[3], 5, "N");
            $piso = formatText($data[4], 2, "X");
            $depto = formatText($data[5], 2, "X");
            $localidad = formatText($data[6], 30);
            $pcia = str_pad($data[7], 2, "0", STR_PAD_LEFT);
            $sexo = (strtoupper($data[8][0]) == 'F') ? 'F' : 'M';
            $form_str = str_pad($formulario, 5, "0", STR_PAD_LEFT);

            // --- REGISTRO 1 ---
            $reg1 = "TE01" . $prefijo . "600000000001" . $eeesss . $apynom . $calle . $nro . $piso . $depto . $localidad . $pcia . "000" . $sexo . $form_str;
            $output .= str_pad($reg1, 128, " ", STR_PAD_RIGHT) . "\r\n";

            // --- REGISTRO 2 ---
            $cp = str_pad($data[9], 8, " ", STR_PAD_RIGHT);
            $tel_car = str_pad(preg_replace('/[^0-9]/', '', $data[10]), 5, "0", STR_PAD_LEFT);
            $tel_nro = str_pad(preg_replace('/[^0-9]/', '', $data[11]), 8, "0", STR_PAD_LEFT);
            $est_civil = strtoupper($data[12][0]); // C, S, V, O
            $fec_nac = date("dmY", strtotime($data[13]));
            $tipo_doc = getTipoDoc($data[14]);
            $num_doc = str_pad(preg_replace('/[^0-9]/', '', $data[15]), 11, "0", STR_PAD_LEFT);
            $pcia_doc = str_pad($data[16], 2, "0", STR_PAD_LEFT);
            $cuit = str_pad(preg_replace('/[^0-9]/', '', $data[17]), 11, "0", STR_PAD_LEFT);
            
            $reg2 = "TE01" . $prefijo . "600000000002" . $cp . $tel_car . $tel_nro . $est_civil . $fec_nac . $tipo_doc . $num_doc . $pcia_doc . $cuit . str_repeat("0", 13) . "0000000" . str_repeat(" ", 25) . $form_str;
            $output .= str_pad($reg2, 128, " ", STR_PAD_RIGHT) . "\r\n";

            // --- REGISTRO 3 ---
            $reg3 = "TE01" . $prefijo . "600000000003" . "99" . "P" . "S" . "S" . " " . str_repeat(" ", 85) . "A" . "1" . str_repeat(" ", 5) . "TPC" . $form_str;
            $output .= str_pad($reg3, 128, " ", STR_PAD_RIGHT) . "\r\n";

            $formulario++;
        }
        fclose($handle);
        
        if (isset($_POST['download'])) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="ALTAUSUCBPRE.' . $entidad . '"');
            echo $output;
            exit;
        }
    }
}

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
    $map = array("LE"=>"01", "LC"=>"02", "DNI"=>"03", "CI"=>"04", "DNE"=>"05");
    return isset($map[$tipo]) ? $map[$tipo] : "03";
}
?>

<!DOCTYPE html>
<html>
<head><title>Conversor Cabal</title></head>
<body>
    <h2>Generar ALTAUSUCBPRE.<?php echo $entidad; ?></h2>
    <form method="post" enctype="multipart/form-data">
        Seleccionar CSV: <input type="file" name="excel_file" required><br><br>
        <button type="submit" name="submit">Ver en Pantalla</button>
        <button type="submit" name="submit" value="1" onclick="this.form.download.value=1">Descargar .TXT</button>
        <input type="hidden" name="download" value="0">
    </form>

    <?php if (isset($output) && !isset($_POST['download'])): ?>
        <h3>Resultado:</h3>
        <pre style="background:#eee; padding:10px; border:1px solid #ccc;"><?php echo htmlspecialchars($output); ?></pre>
    <?php endif; ?>
</body>
</html>