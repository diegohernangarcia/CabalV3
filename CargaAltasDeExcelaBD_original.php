<!--
Lee el archivo de excel con los datos de nuevas altas
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
        $archivo = "archivos/Altas.xlsx";
        $error=0;
        $num=0;
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = $sheet->getHighestRow(); 
        $highestColumn = $sheet->getHighestColumn();
        
        // MUESTRO LOS DATOS EN UNA TABLA
        //echo "FILAS   ".$highestRow;die();
        
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

            $DocumentoNumero=$sheet->getCell("P".$row)->getValue();
            $ApellidoNombre2=$sheet->getCell("A".$row)->getValue()." ".$sheet->getCell("B".$row)->getValue();
            $esta=EstaDNI($DocumentoNumero);
            if ($esta==true) {
                echo "<h5> El Beneficiario ".$ApellidoNombre2." Con DNI ".$DocumentoNumero." YA se encuentra cargado como Beneficiario </H5>"; 
                echo "<h4> PROCESO INTERRUMPIDO </h4>"
                ?> <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a> <?php
                die();
            }

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
              ?> <h5>La Calle Departamento de la linea <?php echo $num; ?>  Contiene un Error (Longitud) </h5> <?php
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
            if (strlen($Beneficiario[$num-1][9]) >8){
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
       
       
        }
	 if ($error==1) {
          echo '<h1 class="display-1">EL ARCHIVO ORIGEN POSEE ERRORES </h1> ';
         // die(); 
        } 
        TruncateTable('altas');
        // GRABAR EN LA BASE DE DATOS
        require("BD/UsarGestion.php");
        $my_Insert_Statement = $conn->prepare("INSERT INTO altas (Apellido,Nombre,DomicilioCalle,DomicilioNumero,DomicilioPiso,DomicilioDepartamento,Localidad,Provincia,Sexo,CodigoPostal,TelefonoCaracteristica,TelefonoNumero,EstadoCivil,FechaNacimiento,DocumentoTipo,DocumentoNumero,DocumentoProvincia,CUIT) VALUES (:Apellido,:Nombre,:DomicilioCalle,:DomicilioNumero,:DomicilioPiso,:DomicilioDepartamento,:Localidad,:Provincia,:Sexo,:CodigoPostal,:TelefonoCaracteristica,:TelefonoNumero,:EstadoCivil,:FechaNacimiento,:DocumentoTipo,:DocumentoNumero,:DocumentoProvincia,:CUIT)");
        for ($reg=0;$reg<=sizeof($Beneficiario)-1;$reg++) {
                $Apellido =strtoupper ($Beneficiario[$reg][0]);
                $Nombre=strtoupper ($Beneficiario[$reg][1]);
                $DomicilioCalle =strtoupper ($Beneficiario[$reg][2]);
                $DomicilioNumero =strtoupper ($Beneficiario[$reg][3]);
                $DomicilioPiso =strtoupper ($Beneficiario[$reg][4]);
                $DomicilioDepartamento =strtoupper ($Beneficiario[$reg][5]);
                $Localidad =strtoupper ($Beneficiario[$reg][6]);
                $Provincia =strtoupper ($Beneficiario[$reg][7]);
                $Sexo =strtoupper ($Beneficiario[$reg][8]);
                $CodigoPostal =strtoupper ($Beneficiario[$reg][9]);
                $TelefonoCaracteristica =strtoupper ($Beneficiario[$reg][10]);
                $TelefonoNumero =strtoupper ($Beneficiario[$reg][11]);
                $EstadoCivil =strtoupper ($Beneficiario[$reg][12]);
                $FechaNacimiento =strtoupper ($Beneficiario[$reg][13]);
                $DocumentoTipo =strtoupper ($Beneficiario[$reg][14]);
                $DocumentoNumero =strtoupper ($Beneficiario[$reg][15]);
                $DocumentoProvincia =strtoupper ($Beneficiario[$reg][16]);
                $CUIT =strtoupper ($Beneficiario[$reg][17]);
                $my_Insert_Statement->bindParam(':Apellido', $Apellido);
                $my_Insert_Statement->bindParam(':Nombre', $Nombre);
                $my_Insert_Statement->bindParam(':DomicilioCalle',$DomicilioCalle);
                $my_Insert_Statement->bindParam(':DomicilioNumero', $DomicilioNumero);
                $my_Insert_Statement->bindParam(':DomicilioPiso', $DomicilioPiso);
                $my_Insert_Statement->bindParam(':DomicilioDepartamento', $DomicilioDepartamento);
                $my_Insert_Statement->bindParam(':Localidad', $Localidad);
                $my_Insert_Statement->bindParam(':Provincia', $Provincia);
                $my_Insert_Statement->bindParam(':Sexo', $Sexo);
                $my_Insert_Statement->bindParam(':CodigoPostal', $CodigoPostal);
                $my_Insert_Statement->bindParam(':TelefonoCaracteristica', $TelefonoCaracteristica);
                $my_Insert_Statement->bindParam(':TelefonoNumero', $TelefonoNumero);
                $my_Insert_Statement->bindParam(':EstadoCivil', $EstadoCivil);
                $my_Insert_Statement->bindParam(':FechaNacimiento', $FechaNacimiento);
                $my_Insert_Statement->bindParam(':DocumentoTipo', $DocumentoTipo);
                $my_Insert_Statement->bindParam(':DocumentoNumero', $DocumentoNumero);
                $my_Insert_Statement->bindParam(':DocumentoProvincia', $DocumentoProvincia);
                $my_Insert_Statement->bindParam(':CUIT', $CUIT);
                if ($my_Insert_Statement->execute()) {
                } else {
                  echo '<h5 class="display-5">No Se Pudieron Guardar Los Registros en la Base de Datos</h5>';  
        }
  }
  echo '<h5 class="display-5">Registros Importado en la Base de Datos</h5>';
?> 
  <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>


</body>
