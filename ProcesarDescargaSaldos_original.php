<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="css/estilos.css" />
  <title>Procesar DescargaSaldos.xlsx y Generar NOVDES.782</title>
  <link rel="icon" href="img/favicon.png">
</head>

<body>
  <BR>
  <h4 class="display-4">Importar Excel Debito Credito a Base de Datos </h4>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Id</th>
        <th>CUIT</th>
        <th>DNI</th>
        <th>Nombre</th>
        <th>Monto a Descargar</th>
        <th>Numero Cuenta</th>
        <th>Fecha</th>
        <th>Hora</th>
      </tr>
    </thead>
    <tbody>
      <?php
      require_once('phpexcel/PHPExcel.php');
      require_once('BD/BD.php');
      $archivo = "archivos/DescargaSaldos.xlsx";
      $error = 0;
      $num = 0;
      $inputFileType = PHPExcel_IOFactory::identify($archivo);
      $objReader = PHPExcel_IOFactory::createReader($inputFileType);
      $objPHPExcel = $objReader->load($archivo);
      $sheet = $objPHPExcel->getSheet(0);
      $highestRow = $sheet->getHighestRow();
      $highestColumn = $sheet->getHighestColumn();
      $dia = date("d");
      $mes = date("m");
      $anio = date("y");
      $fecha = $dia . $mes . $anio;
      //echo $fecha;die();
      date_default_timezone_set("UTC");
      date_default_timezone_set("America/Mendoza");
      $hora = $hoy = getdate();
      $horaactual = $hora["hours"] . $hora["minutes"] . $hora["seconds"];
      //echo $fecha." ".$horaactual;
      //EXCEL VA A USAR ESTAS CELDAS
      for ($row = 2; $row <= $highestRow; $row++) {
        $num++;
      ?>
        <tr>
          <!-- <th scope='row'></th> -->
          <?php
          $id = $sheet->getCell("A" . $row)->getValue();
          $Cuit = $sheet->getCell("B" . $row)->getValue();
          $DNI = $sheet->getCell("C" . $row)->getValue();
          $Nombre = $sheet->getCell("D" . $row)->getValue();
          $Monto = $sheet->getCell("E" . $row)->getValue();
          ?>
          <td><?php echo $id; ?></td> <!-- id -->
          <td><?php echo $Cuit ?></td> <!-- CUIT -->
          <td><?php echo $DNI; ?></td> <!-- DNI -->
          <td><?php echo $Nombre ?></td> <!-- Apellido -->
          <td><?php echo $Monto ?></td> <!-- Monto -->
          <td><?php echo DevuelvoNumeroCuenta($DNI, $Cuit); ?></td> <!-- Cuenta -->
          <td><?php echo $fecha; ?></td> <!-- Fecha -->
          <td><?php echo $horaactual; ?></td> <!-- Hora -->


        <?php }
      echo "</table>";
      echo "GENERAR ARCHIVO NOVDES.782 <br>";
      // CARGO EL ARRAY PARA SUBIR A LA BASE DE DATOS
      $CargaTarjetaA = array();
      $num = 0;
      $sumatoria=0;
      for ($row = 2; $row <= $highestRow; $row++) {
        $num++;
        $CargaTarjeta[$num - 1][0] = $sheet->getCell("A" . $row)->getValue();
        $CargaTarjeta[$num - 1][1] = $sheet->getCell("B" . $row)->getValue();
        $CargaTarjeta[$num - 1][2] = $sheet->getCell("C" . $row)->getValue();

        $CargaTarjeta[$num - 1][3] = $sheet->getCell("D" . $row)->getValue();
        $CargaTarjeta[$num - 1][4] = $sheet->getCell("E" . $row)->getValue();

        $CargaTarjeta[$num - 1][0] = $sheet->getCell("A" . $row)->getValue(); //id
        $CargaTarjeta[$num - 1][1] = $sheet->getCell("B" . $row)->getValue(); // Cuit
        $CargaTarjeta[$num - 1][2] = $sheet->getCell("C" . $row)->getValue(); // DNI
        $CargaTarjeta[$num - 1][3] = $sheet->getCell("D" . $row)->getValue(); // Nombre
        $CargaTarjeta[$num - 1][4] = $sheet->getCell("E" . $row)->getValue(); // MOnto
        $sumatoria=$sumatoria+$CargaTarjeta[$num - 1][4];
        $CargaTarjeta[$num - 1][5] = DevuelvoNumeroCuenta($CargaTarjeta[$num - 1][2] ,$CargaTarjeta[$num - 1][1] ); // MOnto
        $CargaTarjeta[$num - 1][6] = $fecha;
        $CargaTarjeta[$num - 1][7] = $horaactual;
      }
      
     // var_dump($CargaTarjeta)
      //RECORRO EL ARRAY
      // for ($i=0;$i<=$num-1;$i++){
      //     echo $CargaTarjeta[$i][0];echo "--";
      //     echo $CargaTarjeta[$i][1];echo "--";
      //     echo $CargaTarjeta[$i][2];echo "--";
      //     echo $CargaTarjeta[$i][3];echo "--";
      //     echo $CargaTarjeta[$i][4];echo "--";
      //     echo $CargaTarjeta[$i][5];echo "--";
      //     echo $CargaTarjeta[$i][6];echo "--";
      //     echo $CargaTarjeta[$i][7];echo "--";
      //     echo "<br>";
      // }




      $TodosCeros="";
      $TodosCeros=StringTodoIguales("0",$TodosCeros,16);
      $Filler="";
      $Filler=StringTodoIguales("&nbsp;",$Filler,40);
      //echo $TodosCeros;
      echo "<br><br><br><br>";
      echo "TE04782000".$TodosCeros.$fecha.$horaactual."62".$Filler."<br>";  // HEADER
      //echo "<br>";
      for ($i=0;$i<=$num-1;$i++){
        $NumeroCuenta= $CargaTarjeta[$i][5];
        $Importe=$CargaTarjeta[$i][4];
        //$Importe=CompletarMontoConCeros($Importe,9);
        $Filler="";
        $Filler=StringTodoIguales("&nbsp;",$Filler,18);
        //echo "Cuenta -> $NumeroCuenta -- Importe --> $Importe.<br>";
       echo "TE04782001".$NumeroCuenta."6239".formatearNumero9Digitos($Importe)."00000000000000000000000".$Filler;
       //echo formatearNumero9Digitos($Importe)."<br>";
       if ($i!=$num) { echo "<br>";}
       
    }
    //echo "<br>";
    $LargoRegistro=strlen($num);
    if ($LargoRegistro==1)  $CantidadRegistros="0000".$num;
    if ($LargoRegistro==2)  $CantidadRegistros="000".$num;
    if ($LargoRegistro==3)  $CantidadRegistros="00".$num;
    if ($LargoRegistro==4)  $CantidadRegistros="0".$num;
    if ($LargoRegistro==5)  $CantidadRegistros=$num;
   // $MontoFinal=CompletarMontoConCeros($MontoFinal,10);
   $Filler="";
    $Filler=StringTodoIguales("&nbsp;",$Filler,29);
    echo "TE047820009999999999999999".$CantidadRegistros.formatearNumero10Digitos($sumatoria)."0000000000".$Filler."<br>";
    echo "<br>";


?>

</body>

</html>