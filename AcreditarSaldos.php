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
          <th>Localidad</th>
          <th>DNI</th>
          <th>CUIT</th>
          <th>Monto Acreditar</th>
          <th>Numero Cuenta</th>
          <th>Fecha</th>
          <th>Hora</th>
        </tr>
      </thead>
      <tbody>     

<?php
     require_once('phpexcel/PHPExcel.php');
     require_once('BD/BD.php');
     $archivo = "archivos/Acreditaciones.xlsx";
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
    for ($row = 2; $row <= $highestRow; $row++){ 
        $num++;?>
        <tr>
            <th scope='row'><?php echo $num;?></th>
            <td><?php echo $sheet->getCell("B".$row)->getValue();?></td> <!-- Apellido -->
            <td><?php echo $sheet->getCell("C".$row)->getValue();?></td>  <!-- Nombre -->
            <td><?php echo $sheet->getCell("D".$row)->getValue();?></td>  <!-- Localidad -->
            <td><?php echo $sheet->getCell("E".$row)->getValue();?></td>  <!-- DNI -->
            <td><?php echo $sheet->getCell("F".$row)->getValue();?></td>  <!-- CUIT -->
            <td><?php echo $sheet->getCell("G".$row)->getValue();?></td>  <!-- Monto -->
            
            <?php 
              $DNICARGA=$sheet->getCell("E".$row)->getValue(); 
              $CUITCARGA=$sheet->getCell("F".$row)->getValue(); 
              $apellidocarga= $sheet->getCell("B".$row)->getValue();
              $nombrecarga=$sheet->getCell("C".$row)->getValue();
              $quien= $apellidocarga . " ". $nombrecarga;?>
              
            <?php 
              $cuenta=DevuelvoNumeroCuenta($DNICARGA,$CUITCARGA);
              $querror="POR FAVOR VERIFICAR LOS DATOS DEL EXCEL ".$quien;
              if (empty($cuenta)) {
                //echo "POR FAVOR VERIFIQUE LOS DATOS DE ". $quien;
                ?> <h4 class="display-6"> <?php echo "POR FAVOR VERIFIQUE LOS DATOS DE ". $quien;?> </h4> <?php
                die();
              }
              /*$Estabien=EstaBienCuenta($nombrecarga,$apellidocarga, DevuelvoNumeroCuenta($DNICARGA,$CUITCARGA));
              //echo "Esta Buebn ".$Estabien;
              
              if (empty($Estabien)) {
                ?> <h4 class="display-8"> <?php echo "POR FAVOR VERIFIQUE LOS DATOS DE ". $quien;?> </h4> <?php
                
              }*/
              ?>
            <td><?php echo DevuelvoNumeroCuenta($DNICARGA,$CUITCARGA); ?></td>  <!-- Monto -->
            <td><?php echo $fecha; ?></td>  <!-- Fecha-->
            <td><?php echo $horaactual; ?></td>  <!-- Hora-->
            


        </tr> 
    <?php 
    }
    // CARGO EL ARRAY PARA SUBIR A LA BASE DE DATOS
    $CargaTarjetaA=array();
    $num=0;
    for ($row = 2; $row <= $highestRow; $row++){ 
        $num++;
/*        $CargaTarjetaA[$num-1][0]=$sheet->getCell("B".$row)->getValue();
        $CargaTarjetaA[$num-1][1]=$sheet->getCell("C".$row)->getValue();
        $CargaTarjetaA[$num-1][2]=$sheet->getCell("E".$row)->getValue();
        $CargaTarjetaA[$num-1][2]=number_format($CargaTarjetaA[$num-1][2], 0, '.', '');
        $CargaTarjetaA[$num-1][3]=$sheet->getCell("F".$row)->getValue();
        $CargaTarjetaA[$num-1][4]=$sheet->getCell("G".$row)->getValue();
        $CargaTarjetaA[$num-1][5]=$sheet->getCell("D".$row)->getValue();
        $CargaTarjetaA[$num-1][6]=$fecha;
        $CargaTarjetaA[$num-1][7]=$horaactual; */

        $CargaTarjetaA[$num-1][0]=$sheet->getCell("B".$row)->getValue(); //Apellido
        $CargaTarjetaA[$num-1][1]=$sheet->getCell("C".$row)->getValue(); // Nombre
        $CargaTarjetaA[$num-1][2]=$sheet->getCell("D".$row)->getValue(); // Localidad
        $CargaTarjetaA[$num-1][3]=$sheet->getCell("E".$row)->getValue(); // DNI
        $CargaTarjetaA[$num-1][4]=$sheet->getCell("F".$row)->getValue(); // CUIT
        $CargaTarjetaA[$num-1][5]=$sheet->getCell("G".$row)->getValue(); // Monto
        $CargaTarjetaA[$num-1][6]=DevuelvoNumeroCuenta ($CargaTarjetaA[$num-1][3],$CargaTarjetaA[$num-1][4]);
       // $CargaTarjetaA[$num-1][6]="PEPE";
        //die();
        
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
                $Apellido=strval($CargaTarjetaA[$reg][0]); 
                $Nombre=strval($CargaTarjetaA[$reg][1]); 
                $NumeroCuenta=strval($CargaTarjetaA[$reg][6]); 
                $ImporteCompra=strval($CargaTarjetaA[$reg][5]); 
                $ImporteCajero=0; 
                $DNI=strval($CargaTarjetaA[$reg][3]); 
                $IdBenef=DevuelvoIdBeneficiario($DNI);
                $Fecha=strval($fecha); 
                $Hora=strval($horaactual); 
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
    <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  
    <!-- 
    <button type="button" href="javascript:history.back()" class="btn btn-danger">Volver a Principal</button> <br> <a href="GenerarCargaCredito2.php" class="btn-flotante">Generar Archivo NOVCAR</a>
    <button type="button" href="GenerarCargaCredito2.php" class="btn btn-success">Generar Archivo NOVCAR</button>  -->
    
    <center><a href="GenerarCargaCredito2.php">Continuar a Generar NOVCAR.782</a></center><br>


