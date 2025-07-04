<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Tarjetas - Maestro Plasticos</title>
</head>

<body>
    <?php
   // $nombreArchivo = $_FILES['archivo']['tmp_name'];
   if (isset($_FILES['archivoExcel']) && $_FILES['archivoExcel']['error'] === UPLOAD_ERR_OK) {
    $archivoExcel = $_FILES['archivoExcel']['tmp_name'];
        // Ruta al archivo Excel y hoja de cálculo
        //$archivoExcel = "./files/legajos.xls";
        $hojaCalculo = 0;
        echo "ARCHIOVO  $archivoExcel";die();
        $lineasArray = file($archivoExcel, FILE_IGNORE_NEW_LINES);
        if ($lineasArray !== false) {
            // Imprime cada línea del array
            $i = 0;
            echo "HOLA GATOSSSSSSSSS";
            $largoarray = count($lineasArray);
            foreach ($lineasArray as $linea) {
                $i++;
                if ($i == 1) {
                    $h1 = substr($linea, 0, 15);
                    $h2 = substr($linea, 16, 31);
                    $h3 = substr($linea, 32, 56);
                    $h4 = substr($linea, 57, 58);
                    $h5 = substr($linea, 59, 69);
                    $h6 = substr($linea, 70, 71);
                    $h7 = substr($linea, 72, 77);
                    $h8 = substr($linea, 78, 83);
                    $h9 = substr($linea, 84, 89);
                    $h10 = substr($linea, 90, 109);
                } else {
                    die("NO SE CARGO ARCHIVO");
                }
            }
        }
    }
    ?>

    <table>
        <caption> Maestro Tarjetas </caption>
        <thead>
            <tr>
                <th>Numero Cuenta</th>
                <th>Numero Tarjeta</th>
                <th>Nombre y Apellido</th>
                <th>Tipo Documento</th>
                <th>Nro Documento</th>
                <th>Causa</th>
                <th>Fecha Alta</th>
                <th>Fecha Emision</th>
                <th>Fecha Vencimiento</th>
                
            </tr>
            <td><?php echo $h1 ?></td>
            <td><?php echo $h2 ?></td>
            <td><?php echo $h3 ?></td>
            <td><?php echo $h4 ?></td>
            <td><?php echo $h5 ?></td>
            <td><?php echo $h6 ?></td>
            <td><?php echo $h7 ?></td>
            <td><?php echo $h8 ?></td>
            <td><?php echo $h9 ?></td>

            
    </table>
    <br><br>

</body>

</html>