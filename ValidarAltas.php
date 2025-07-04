<!--
Valida que el archivo de texto de altas generadas, cada linea contenga 128 caracteres
-->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <title>Validacion Archivo txt Generado</title>
  <link rel="icon" href="img/favicon.png">

  
</head>
<body>
  <h1> </h1>
  <h2 class="display-2">Validacion Archivo Altas (txt) Generado </h2>
<?php
    $i=0;
    $datos=array();
    $queArchivo="Altas.txt";
    $handle=fopen($queArchivo, "r") or die("¡Error al leer el archivo!");
    // (B) LEER LÍNEA POR LÍNEA
    while (($line = fgets($handle)) !== false) {
        
        $datos[$i]=$line;
        $i++;
        
    }
    // (C) CERRAR EL ARCHIVO
    fclose($handle);
    $largo=0;
    $error=0;
    foreach ($datos as $valor) {
       /// $Operacion= substr($valor,20,2);
       $largo=strlen($valor);
       //echo "Largo Fila ";
       //echo $largo -2;
       //echo $valor;
       //echo "<BR>";
       echo $largo."<BR>";
       if ($largo != 130) {
         $error=1;
             }   
       
    }
    if ($error==1) {
      echo "<br>";echo "<br>";echo "<br>";
      echo '<h2 class="display-4">El archivo posee errores </h2>';
    }
    else {
      echo "<br>";echo "<br>";echo "<br>";
      echo '<h2 class="display-1">El archivo NO posee errores </h2>';
    }
?>
 <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>
  </body>
  </html>
  