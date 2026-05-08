<?php
/**
 * Conversor Cabal ALTAUSUCBPRE.782
 * Versión con descarga forzada y limpieza de búfer
 */

// 1. Configuración de Errores y Búfer
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Carga de Librería (Ajusta la ruta si es necesario)
require_once 'phpexcel/PHPExcel.php';

$entidad = "782"; 

// 3. Lógica de Procesamiento y Descarga
if (isset($_POST['submit'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        
        $archivoExcel = $_FILES['excel_file']['tmp_name'];
        
        try {
            $tipoArchivo = PHPExcel_IOFactory::identify($archivoExcel);
            $lector = PHPExcel_IOFactory::createReader($tipoArchivo);
            $phpExcel = $lector->load($archivoExcel);
            $hoja = $phpExcel->getSheet(0); 
            $filas = $hoja->getHighestRow();
            
            $output = "";
            $numFormulario = 1;
            $prefijo = str_pad($entidad, 6, "0", STR_PAD_LEFT); 
            $eeesss = $entidad . "001";

            for ($i = 2; $i <= $filas; $i++) {
                $apellido = trim((string)$hoja->getCell('A'.$i)->getValue());
                $nombre   = trim((string)$hoja->getCell('B'.$i)->getValue());
                if ($apellido == "" && $nombre == "") continue;

                // Lectura de campos
                $calle     = trim((string)$hoja->getCell('C'.$i)->getValue());
                $nro       = trim((string)$hoja->getCell('D'.$i)->getValue());
                $piso      = trim((string)$hoja->getCell('E'.$i)->getValue());
                $depto     = trim((string)$hoja->getCell('F'.$i)->getValue());
                $localidad = trim((string)$hoja->getCell('G'.$i)->getValue());
                $provincia = trim((string)$hoja->getCell('H'.$i)->getValue());
                $sexoRaw   = trim((string)$hoja->getCell('I'.$i)->getValue());
                $cp        = trim((string)$hoja->getCell('J'.$i)->getValue());
                $telCar    = trim((string)$hoja->getCell('K'.$i)->getValue());
                $telNro    = trim((string)$hoja->getCell('L'.$i)->getValue());
                $estCivil  = trim((string)$hoja->getCell('M'.$i)->getValue());
                $fecNac    = $hoja->getCell('N'.$i)->getValue(); 
                $tipoDoc   = trim((string)$hoja->getCell('O'.$i)->getValue());
                $numDoc    = trim((string)$hoja->getCell('P'.$i)->getValue());
                $provDoc   = trim((string)$hoja->getCell('Q'.$i)->getValue());
                $cuit      = trim((string)$hoja->getCell('R'.$i)->getValue());

                $sexo = (strtoupper(substr($sexoRaw, 0, 1)) === 'F') ? 'F' : 'M';
                $e_civ = (strlen($estCivil) > 0) ? strtoupper(substr($estCivil, 0, 1)) : 'S';
                $formStr  = str_pad($numFormulario, 5, "0", STR_PAD_LEFT);

                // Registro 1
                $apynom = substr(str_replace("Ñ","#",strtoupper($apellido)) . "/" . str_replace("Ñ","#",strtoupper($nombre)), 0, 25);
                $r1 = "TE01" . $prefijo . "600000000001" . $eeesss . str_pad($apynom, 25, " ") . str_pad(substr(str_replace("Ñ","#",strtoupper($calle)), 0, 25), 25, " ") . str_pad(substr(preg_replace('/[^0-9]/', '', $nro), 0, 5), 5, "0", STR_PAD_LEFT) . str_pad(substr($piso, 0, 2), 2, " ") . str_pad(substr($depto, 0, 2), 2, " ") . str_pad(substr(str_replace("Ñ","#",strtoupper($localidad)), 0, 30), 30, " ") . str_pad(preg_replace('/[^0-9]/', '', $provincia), 2, "0", STR_PAD_LEFT) . "000" . $sexo . $formStr;
                $output .= str_pad(substr($r1, 0, 128), 128, " ") . "\r\n";

                // Registro 2
                $f_nac = is_numeric($fecNac) ? date("dmY", PHPExcel_Shared_Date::ExcelToPHP($fecNac)) : "01011980";
                $n_doc = str_pad(substr(preg_replace('/[^0-9]/', '', $numDoc), 0, 11), 11, "0", STR_PAD_LEFT);
                $c_uit = str_pad(substr(preg_replace('/[^0-9]/', '', $cuit), 0, 11), 11, "0", STR_PAD_LEFT);
                $r2 = "TE01" . $prefijo . "600000000002" . str_pad(substr($cp, 0, 8), 8, " ") . str_pad(substr(preg_replace('/[^0-9]/', '', $telCar), 0, 5), 5, "0", STR_PAD_LEFT) . str_pad(substr(preg_replace('/[^0-9]/', '', $telNro), 0, 8), 8, "0", STR_PAD_LEFT) . $e_civ . $f_nac . "03" . $n_doc . str_pad(preg_replace('/[^0-9]/', '', $provDoc), 2, "0", STR_PAD_LEFT) . $c_uit . str_repeat("0", 13) . str_repeat("0", 7) . str_repeat(" ", 25) . $formStr;
                $output .= str_pad(substr($r2, 0, 128), 128, " ") . "\r\n";

                // Registro 3
                $r3 = "TE01" . $prefijo . "600000000003" . "99PSS " . str_repeat(" ", 85) . "A1" . str_repeat(" ", 5) . "TPC" . $formStr;
                $output .= str_pad(substr($r3, 0, 128), 128, " ") . "\r\n";

                $numFormulario++;
            }

            // SI SE PIDIÓ DESCARGAR
            if ($_POST['accion'] == "descargar") {
                ob_clean();
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="ALTAUSUCBPRE.'.$entidad.'"');
                echo rtrim($output, "\r\n");
                exit;
            }

        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Conversor Cabal</title></head>
<body>
    <h2>Generador ALTAUSUCBPRE.782</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="excel_file" required>
        <input type="hidden" name="accion" id="accion" value="preview">
        <br><br>
        <button type="submit" name="submit" onclick="document.getElementById('accion').value='preview'">Ver Vista Previa</button>
        <button type="submit" name="submit" onclick="document.getElementById('accion').value='descargar'">Descargar TXT</button>
    </form>

    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($output) && $_POST['accion'] == "preview"): ?>
        <hr>
        <textarea style="width:100%; height:300px; font-family:monospace;"><?php echo htmlspecialchars($output); ?></textarea>
    <?php endif; ?>
</body>
</html>