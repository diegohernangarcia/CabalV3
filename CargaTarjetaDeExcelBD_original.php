<!--
Lee el archivo de excel con los datos de nuevas cargas de credito y carga la BD
Valida la longitud de los campos
y lo guarda en la tabla altas
Cada vez que se ejecuta vacia la tabla altas para agregar datos.
-->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <title>Importar Altas A Base De Datos</title>
    <link rel="icon" href="img/favicon.png">
</head>
<body>
<BR>
<h4 class="display-4">Importar Excel Cargar Credito Tarjetas a Base de Datos </h4>
<BR><BR>

<table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>Apellido</th>
          <th>Nombre</th>
          <th>NumeroCuenta</th>
          <th>Importe Compras</th>
          <th>Importe Cajero</th>
          <th>DNI</th>
          <th>Fecha</th>
          <th>Hora</th>
        </tr>
      </thead>
      <tbody>     

<?php
     require_once('phpexcel/Classes/PHPExcel.php');
     require_once('BD/BD.php');
     $archivo = "archivos/AltaCargas.xlsx";
     $error=0;
     $num=0;
     $inputFileType = PHPExcel_IOFactory::identify($archivo);
     $objReader = PHPExcel_IOFactory::createReader($inputFileType);
     $objPHPExcel = $objReader->load($archivo);
     $sheet = $objPHPExcel->getSheet(0); 
     $highestRow = $sheet->getHighestRow(); 
     $highestColumn = $sheet->getHighestColumn();
     $dia=date("d");
     $mes=date("m");
     $anio=date("y");
     $fecha=$dia.$mes.$anio;
     //echo $fecha;die();
     
     date_default_timezone_set("UTC");
     date_default_timezone_set("America/Mendoza");

     $hora=$hoy = getdate();
     $horaactual=$hora["hours"].$hora["minutes"].$hora["seconds"];
     //echo $fecha." ".$horaactual;
    //EXCEL VA A USAR ESTAS CELDAS
    //A =ID
    //B=APELLIDO
    //C=NOMBRE
    //D=DNI
    //E=NUMERO CUENTA
    //F=CREDITO A CARGAR
    //G=CREDITO RETIRAR CAJERO
    


    for ($row = 2; $row <= $highestRow; $row++){ 
        $num++;?>
        <tr>
            <th scope='row'><?php echo $num;?></th>
            <td><?php echo $sheet->getCell("B".$row)->getValue();?></td> 
            <td><?php echo $sheet->getCell("C".$row)->getValue();?></td> 
            <?php $cuentaExcel=($sheet->getCell("E".$row)->getValue()); ?> 
            <td><?php echo $cuentaExcel=number_format($cuentaExcel, 0, '.', '');?></td>
            <td><?php echo $sheet->getCell("F".$row)->getValue();?></td> 
            <td><?php echo $sheet->getCell("G".$row)->getValue();?></td> 
            <td><?php echo $sheet->getCell("D".$row)->getValue();?></td> 
            <td><?php echo $fecha?></td>
            <td><?php echo $horaactual?></td> 
        </tr> 
    <?php 
    }
    // CARGO EL ARRAY PARA SUBIR A LA BASE DE DATOS
    $CargaTarjetaA=array();
    $num=0;
    // echo $highestRow;die();
    for ($row = 2; $row <= $highestRow; $row++){ 
        $num++;
        //echo $CargaTarjetaA[$num-1][0];die();
        $CargaTarjetaA[$num-1][0]=$sheet->getCell("B".$row)->getValue();
        $CargaTarjetaA[$num-1][1]=$sheet->getCell("C".$row)->getValue();
        $CargaTarjetaA[$num-1][2]=$sheet->getCell("E".$row)->getValue();
        $CargaTarjetaA[$num-1][2]=number_format($CargaTarjetaA[$num-1][2], 0, '.', '');
        $CargaTarjetaA[$num-1][3]=$sheet->getCell("F".$row)->getValue();
        $CargaTarjetaA[$num-1][4]=$sheet->getCell("G".$row)->getValue();
        $CargaTarjetaA[$num-1][5]=$sheet->getCell("D".$row)->getValue();
        $CargaTarjetaA[$num-1][6]=$fecha;
        $CargaTarjetaA[$num-1][7]=$horaactual;
      }
    TruncateTable('cargatarjeta');
    // GRABAR EN LA BASE DE DATOS
    require_once("BD/UsarGestion.php");
    TruncateTable('acreditar');
    $IdBenef=1;
    
    //var_dump($CargaTarjetaA);die();
    //$conn = new PDO('mysql:host=localhost;dbname=cabal','root','');
    $my_Insert_Statement = $conn->prepare("INSERT INTO acreditar(id_beneficiario,Apellido,Nombre,NumeroCuenta,ImporteCompra,ImporteCajero,DNI,Fecha,Hora) VALUES (:id_beneficiario,:Apellido,:Nombre,:NumeroCuenta,:ImporteCompra,:ImporteCajero,:DNI,:Fecha,:Hora)");
    for ($reg=0;$reg<=sizeof($CargaTarjetaA)-1;$reg++) {
          try {      
              // var_dump($CargaTarjetaA);die();
                $Apellido=strval($CargaTarjetaA[$reg][0]); 
                $Nombre=strval($CargaTarjetaA[$reg][1]); 
                $NumeroCuenta=strval($CargaTarjetaA[$reg][2]); 
                $ImporteCompra=strval($CargaTarjetaA[$reg][3]); 
                $ImporteCajero=strval($CargaTarjetaA[$reg][4]); 
                $DNI=strval($CargaTarjetaA[$reg][5]); 
                $IdBenef=DevuelvoIdBeneficiario($DNI);
                $Fecha=strval($CargaTarjetaA[$reg][6]); 
                $Hora=strval($CargaTarjetaA[$reg][7]); 
                $my_Insert_Statement->bindParam(':id_beneficiario', $IdBenef);
                $my_Insert_Statement->bindParam(':Apellido',$Apellido);
                $my_Insert_Statement->bindParam(':Nombre',$Nombre);
                $my_Insert_Statement->bindParam(':NumeroCuenta',$NumeroCuenta);
                $my_Insert_Statement->bindParam(':ImporteCompra',$ImporteCompra);
                $my_Insert_Statement->bindParam(':ImporteCajero',$ImporteCajero);
                $my_Insert_Statement->bindParam(':DNI',$DNI);
                $my_Insert_Statement->bindParam(':Fecha',$Fecha);
                $my_Insert_Statement->bindParam(':Hora',$Hora);
                //var_dump($CargaTarjetaA);die();
                /*if ($my_Insert_Statement->execute()) {
                  } else {
                    echo 'No Se Pudo Guardar el Registro en la Base de Datos <br>';  
                  } */
                $my_Insert_Statement->execute();

                }catch (PDOException $e) {
                                   echo "Fallo de conexion al enviar los datos:".$e ->getMessage()."<br>";
                } 


    }
    //echo 'Registros Importado en la Base de Datos';
?>