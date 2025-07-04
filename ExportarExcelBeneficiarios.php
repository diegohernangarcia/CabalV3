<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar a Excel los Beneficiarios</title>
</head>

<body>
    <?php
    $usuario = 'pucho';
    $pass = '78900';
    $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $fecha_actual = date("d-m-Y");
    $conexion = new mysqli("localhost", $usuario, $pass, "cabal");
    // Verificar la conexión
    if ($conexion->connect_error) {
        die("Error en la conexión: " . $conexion->connect_error);
    }
    $consulta = "SELECT * FROM beneficiario";
    $resultado = $conexion->query($consulta);
    if ($resultado->num_rows > 0) {
        // Crear un objeto de la librería PHPExcel
        require_once "./phpexcel/PHPExcel.php";
        require ("BD/Funciones.php");

        $objPHPExcel = new PHPExcel();
        // Establecer el nombre de la hoja de cálculo
        $objPHPExcel->getActiveSheet()->setTitle('Beneficiarios');
        // Escribir los encabezados de las columnas
        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Id');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Apellido');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Nombre');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Localidad');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Provincia');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', 'NumeroCuenta');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', 'NumeroTarjeta');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', 'CodigoPostal');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', 'TipoDocumento');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', 'NumeroDocumento');
        $objPHPExcel->getActiveSheet()->setCellValue('K1', 'Cuit');

        $fila = 2; // Iniciar desde la fila 2 para los datos
        while ($row = $resultado->fetch_assoc()) {
            // Escribir los datos de cada fila
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $fila, $row['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $fila, $row['Apellido']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $fila, $row['Nombre']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $fila, $row['Localidad']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $fila, $row['Provincia']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $fila, $row['NumeroCuenta']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $fila, formatearTarjetaCredito($row['NumeroTarjeta']));
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $fila, $row['CodigoPostal']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $fila, $row['TipoDocumento']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $fila, $row['NumeroDocumento']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $fila, $row['Cuit']);


            $fila++;
        }
        // Configurar el formato de la hoja de cálculo
        $objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);


        // Crear el objeto de escritura del archivo Excel
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        // Guardar el archivo en el servidor
        $archivo = "Beneficiarios FSR $fecha_actual.xlsx";
        $objWriter->save($archivo);
        // Generar el enlace para descargar el archivo
        $urlArchivo = '' . $archivo;
        echo "La tabla Beneficiarios se ha exportado correctamente. Descarga el archivo <a href='$urlArchivo'>$archivo</a>.";
    } else {
        echo "No se encontraron registros en la tabla Legajos.";
    }
    $resultado->free_result();
    $conexion->close();
    ?>
</body>

</html>