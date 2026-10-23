<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
    <title>Importar Altas A Base De Datos</title>
    <link rel="icon" href="img/favicon.png">
</head>
<body>
<BR>
<h2 class="display-2">Importar Excel Altas a Base de Datos </h2>
<BR><BR>

<table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>Apellido</th>
          <th>Nombre</th>
          <th>Domicilio</th>
          <th>Numero</th>
          <th>Piso</th>
          <th>Departamento</th>
          <th>Localidad</th>
          <th>Provincia</th>
          <th>Genero</th>
          <th>Codigo Postal</th>
          <th>T. Caracteristica</th>
          <th>T. Numero</th>
          <th>Estado Civil</th>
          <th>Fecha Nacimiento</th>
          <th>Tipo Doc.</th>
          <th>Numero Doc.</th>
          <th>Prov. Doc.</th>
          <th>CUIL</th>
          
          
        </tr>
      </thead>
      <tbody>        

    <?php
        require_once('phpexcel/PHPExcel.php');
        require_once('BD/BD.php');
        $archivo = "Altas.xlsx";
        $error=0;
        $num=0;
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = $sheet->getHighestRow(); 
        $highestColumn = $sheet->getHighestColumn();
        // MUESTRO LOS DATOS EN UNA TABLA
        for ($row = 2; $row <= $highestRow; $row++){ 
            $num++;?>
            <tr>
                <th scope='row'><?php echo $num;?></th>
                <td><?php echo $sheet->getCell("A".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("B".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("C".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("D".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("E".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("F".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("G".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("H".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("I".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("J".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("K".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("L".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("M".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("N".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("O".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("P".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("Q".$row)->getValue();?></td>
                <td><?php echo $sheet->getCell("R".$row)->getValue();?></td>
            </tr>
        <?php
      }
        // CARGO EL ARRAY PARA SUBIR A LA BASE DE DATOS
        $Beneficiario=array();
        $num=0;
        for ($row = 2; $row <= $highestRow; $row++){ 
            $num++;
            $Beneficiario[$num-1][0]=$sheet->getCell("A".$row)->getValue();
            $Beneficiario[$num-1][1]=$sheet->getCell("B".$row)->getValue();
            $Beneficiario[$num-1][2]=$sheet->getCell("C".$row)->getValue();
            $Beneficiario[$num-1][3]=$sheet->getCell("D".$row)->getValue();
            $Beneficiario[$num-1][4]=$sheet->getCell("E".$row)->getValue();
            $Beneficiario[$num-1][5]=$sheet->getCell("F".$row)->getValue();
            $Beneficiario[$num-1][6]=$sheet->getCell("G".$row)->getValue();
            $Beneficiario[$num-1][7]=$sheet->getCell("H".$row)->getValue();
            $Beneficiario[$num-1][8]=$sheet->getCell("I".$row)->getValue();
            $Beneficiario[$num-1][9]=$sheet->getCell("J".$row)->getValue();
            $Beneficiario[$num-1][10]=$sheet->getCell("K".$row)->getValue();
            $Beneficiario[$num-1][11]=$sheet->getCell("L".$row)->getValue();
            $Beneficiario[$num-1][12]=$sheet->getCell("M".$row)->getValue();
            $Beneficiario[$num-1][13]=$sheet->getCell("N".$row)->getValue();
            $Beneficiario[$num-1][14]=$sheet->getCell("O".$row)->getValue();
            $Beneficiario[$num-1][15]=$sheet->getCell("P".$row)->getValue();
            $Beneficiario[$num-1][16]=$sheet->getCell("Q".$row)->getValue();
            $Beneficiario[$num-1][17]=$sheet->getCell("R".$row)->getValue();

            ///////////////////////////////////////////////////////////
            // VALIDO LONGITUDES DATOS DEL EXCEL
            ///////////////////////////////////////////////////////////
            if (strlen($Beneficiario[$num-1][0]) >16){
              ?> <h5>El Apellido de la linea <?php echo $num; ?> Contiene un Error (Longitud) </h5> <?php
              $error=1; echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][0].'/'.$Beneficiario[$num-1][1]) >25){
              ?> <h5>El Apellido y Nombre de la linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][2]) >25){
              ?> <h5>La Calle de la linea <?php echo $num; ?>  Contiene un Error (Longitud) </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][3]) >5){
              ?> <h5>La Calle Numero de la linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][4]) >2){
              ?> <h5>La Calle Piso de la linea <?php echo $num; ?>  Contiene un Error (Longitud) </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][5]) >2){
              ?> <h5">La Calle Departamento de la linea <?php echo $num; ?>  Contiene un Error (Longitud) </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][6]) >30){
              ?> <h5>La Localidad  de la linea <?php echo $num; ?>  Contiene un Error (Longitud) </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][7]) >2){
              ?> <h5>La Provincia de la linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][8]) >1){
              ?> <h5>El Genero de la linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (($Beneficiario[$num-1][8])<>'M' and ($Beneficiario[$num-1][8])<>'F') {
              ?> <h5>El Genero de la linea <?php echo $num; ?>  Contiene un Error Solo F o M  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][9]) <>8){
              ?> <h5>El Codigo Postal de la Linea <?php echo $num; ?>  Contiene un Error Debe de ser el CPA de Correo Argentino  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][10]) >5){
              ?> <h5>El Caracteristica del Telefono <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][10]) >5){
              ?> <h5>El Caracteristica del Telefono de la Linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][11]) >8){
              ?> <h5>El Numero de Telefono de la Linea<?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            
            if (strlen($Beneficiario[$num-1][12]) >1){
              ?> <h5>El Estado Civil de la Linea<?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            
            $os = array("C", "S", "V", "O");
            
            if (in_array($Beneficiario[$num-1][12],$os)) {
              } else {
              ?> <h5>El Estado Civil de la Linea <?php echo $num; ?>  Contiene un Error Solo C,S,V,O  </h5> <?php
              $error=1;echo ('<BR>');
            }
            
            if (strlen($Beneficiario[$num-1][13]) >8){
              ?> <h5>La Fecha de Nacimiento de la Linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1; echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][14]) >2){
              ?> <h5>El Tipo Documento de la Linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][15]) >11){
              ?> <h5>El Numero de Documento de la Linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][16]) >2){
              ?> <h5>La Provincia del Documento de la Linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
            if (strlen($Beneficiario[$num-1][17]) >11){
              ?> <h5>El CUIT de la Linea <?php echo $num; ?>  Contiene un Error (Longitud)  </h5> <?php
              $error=1;echo ('<BR>');
            }
       
        if ($error==1) {
          echo '<h1 class="display-1">EL ARCHIVO ORIGEN POSEE ERRORES </h1> ';
          die(); 
        } 
        }
        TruncateTable('altas');
        // GRABAR EN LA BASE DE DATOS
        require("BD/UsarGestion.php");
        $my_Insert_Statement = $conn->prepare("INSERT INTO altas (Apellido,Nombre,DomicilioCalle,DomicilioNumero,DomicilioPiso,DomicilioDepartamento,Localidad,Provincia,Sexo,CodigoPostal,TelefonoCaracteristica,TelefonoNumero,EstadoCivil,FechaNacimiento,DocumentoTipo,DocumentoNumero,DocumentoProvincia,CUIT) VALUES (:Apellido,:Nombre,:DomicilioCalle,:DomicilioNumero,:DomicilioPiso,:DomicilioDepartamento,:Localidad,:Provincia,:Sexo,:CodigoPostal,:TelefonoCaracteristica,:TelefonoNumero,:EstadoCivil,:FechaNacimiento,:DocumentoTipo,:DocumentoNumero,:DocumentoProvincia,:CUIT)");
        for ($reg=0;$reg<=sizeof($Beneficiario)-1;$reg++) {
                $my_Insert_Statement->bindParam(':Apellido', $Beneficiario[$reg][0]);
                $my_Insert_Statement->bindParam(':Nombre', $Beneficiario[$reg][1]);
                $my_Insert_Statement->bindParam(':DomicilioCalle', $Beneficiario[$reg][2]);
                $my_Insert_Statement->bindParam(':DomicilioNumero', $Beneficiario[$reg][3]);
                $my_Insert_Statement->bindParam(':DomicilioPiso', $Beneficiario[$reg][4]);
                $my_Insert_Statement->bindParam(':DomicilioDepartamento', $Beneficiario[$reg][5]);
                $my_Insert_Statement->bindParam(':Localidad', $Beneficiario[$reg][6]);
                $my_Insert_Statement->bindParam(':Provincia', $Beneficiario[$reg][7]);
                $my_Insert_Statement->bindParam(':Sexo', $Beneficiario[$reg][8]);
                $my_Insert_Statement->bindParam(':CodigoPostal', $Beneficiario[$reg][9]);
                $my_Insert_Statement->bindParam(':TelefonoCaracteristica', $Beneficiario[$reg][10]);
                $my_Insert_Statement->bindParam(':TelefonoNumero', $Beneficiario[$reg][11]);
                $my_Insert_Statement->bindParam(':EstadoCivil', $Beneficiario[$reg][12]);
                $my_Insert_Statement->bindParam(':FechaNacimiento', $Beneficiario[$reg][13]);
                $my_Insert_Statement->bindParam(':DocumentoTipo', $Beneficiario[$reg][14]);
                $my_Insert_Statement->bindParam(':DocumentoNumero', $Beneficiario[$reg][15]);
                $my_Insert_Statement->bindParam(':DocumentoProvincia', $Beneficiario[$reg][16]);
                $my_Insert_Statement->bindParam(':CUIT', $Beneficiario[$reg][17]);
                if ($my_Insert_Statement->execute()) {
                } else {
                  echo '<h5 class="display-5">No Se Pudieron Guardar Los Registros en la Base de Datos</h5>';  
        }
  }
  echo '<h5 class="display-5">Registros Importado en la Base de Datos</h5>';
?> 
