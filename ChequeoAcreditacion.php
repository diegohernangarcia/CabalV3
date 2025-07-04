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
<h5 class="display-5">INFORMA SI EXISTEN ERRORES EN EL EXCEL PARA ACREDITAR</h5>
<BR><BR>
 

    
<table class="table table-bordered">
      
      <thead>
        <tr>
          <th>#</th>
          <th>Apellido</th>
          <th>Nombre</th>
          <th>DNI</th>
          <th>ESTADO</th>
          
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
        $num++;
        $ApellidoCarga=$sheet->getCell("B".$row)->getValue();
        $NombreCarga=$sheet->getCell("C".$row)->getValue();
        $DNICarga=$sheet->getCell("E".$row)->getValue();
        ?>
        <tr>
            <th scope='row'><?php echo $num;?></th>
            
            <td><?php echo $ApellidoCarga?></td> <!-- Apellido -->
            <td><?php echo $NombreCarga?></td>  <!-- Nombre -->
            <td><?php echo $DNICarga?></td>  <!-- DNI -->
            <?php
            $datos=DevuelvoApellidoNombrePorDNI($DNICarga); 
            //$estado="";
            if ((trim($ApellidoCarga)==$datos[1]) and (trim($NombreCarga)==$datos[2])) {
                $estado="";
                } else {
                    $estado="VERIFICAR";
                }
        ?> <td bgcolor="red"><?php echo $estado;?></td>           
        </tr> 
        <?php
       }
?>
    
    <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  