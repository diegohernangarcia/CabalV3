<!--
Genera el Archivo ALTAUSUCBPRE en Pantalla para enviar al banco
Primero se debe de llenar la tabla altas.
-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="img/favicon.png">
  <link rel="stylesheet" type="text/css" href="css/estilos.css" />
  <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">

  <title>Generar Archivo Texto Acreditacion</title>
</head>
<body>
<!-- <h1>Archivo Acreditacion Credito Tarjeta Cabal - NOVCAR.782</h1>
  <h2 class="display-2">  </h2>
  <h1> NO OLVIDAR EN NOTEPAD++ CAMBIAR TIPO DE CODIFICACION WINDOWS </H1><br>
  <h2> SOLO EN WINDOWS SE PUEDE HACER, SI ES DESDE LINUX EL ARCHIVO ES INCOMPATIBLE </h2><br>
  <h2> Y EL ARCHIVO TERMINA CON UN SALTO DE LINEA </h2><br> 
  <center><a href="Novcar/novcar.782">Descargar Archivo Generado <br> Debe de llamarse NOVCAR.782 para Subirlo a la Plataforma del Banco</a></center>
  
  
<br><br>      -->
<?php
    $miArray = [];
    /*header("Content-disposition: attachment; filename=Novcar/EsteArchivo.txt");
    header("Content-type: application/txt");
    readfile("Novcar/EsteArchivo.txt");*/
    
    require("BD/UsarGestion.php");
    require_once("BD/Funciones.php");
    require_once("BD/BD.php");
    $file = fopen("Novcar/novcar.782", "w+");


    $sql = 'SELECT * FROM acreditar';
    //HEADER
    $dia=date("d");
     $mes=date("m");
     $anio=date("y");
     $fecha=$dia.$mes.$anio;
     date_default_timezone_set("UTC");
     date_default_timezone_set("America/Mendoza");
     $hora="090000";
     $espacios="";
     for ($i = 0; $i < 42; $i++) {
        AgregarEspacios2($espacios);
     }
     $header="TE047820000000000000000000".$fecha.$hora.$espacios;
     Array_push($miArray,$header);
     echo $header;
     fwrite($file, $header . PHP_EOL);
     echo "<BR>";
     //REGISTRO DETALLE
     $Registro=0;
     $MontoFinal=0;
     $ImporteCompra=0;
     foreach ($conn->query($sql) as $row) {
        $NumeroCuenta=$row['NumeroCuenta'];
        $ImporteCompra=$row['ImporteCompra'];
        $MontoFinal=$MontoFinal+$ImporteCompra;
        $Registro++;
       // $ImporteCompra=CompletarMontoConCeros($ImporteCompra,11);
        //echo $ImporteCompra."<br>";
        $LargoRegistro=strlen($Registro);
        if ($LargoRegistro==1)  $NumeroRegistro="0000000".$Registro;
        if ($LargoRegistro==2)  $NumeroRegistro="000000".$Registro;
        if ($LargoRegistro==3)  $NumeroRegistro="00000".$Registro;
        if ($LargoRegistro==4)  $NumeroRegistro="0000".$Registro;
        if ($LargoRegistro==5)  $NumeroRegistro="000".$Registro;
        if ($LargoRegistro==6)  $NumeroRegistro="00".$Registro;
        if ($LargoRegistro==7)  $NumeroRegistro="0".$Registro;
        if ($LargoRegistro==8)  $NumeroRegistro=$Registro;
        $espacios="";
        for ($i = 0; $i < 18; $i++) {
            AgregarEspacios2($espacios);
        }
        //echo CompletarMontoConCeros($ImporteCompra,9)."<br>";
        $Detalle="TE04782001".$NumeroCuenta."6339".CompletarMontoConCeros($ImporteCompra,9)."000000000006239".$NumeroRegistro.$espacios;
        echo $Detalle;
        Array_push($miArray,$Detalle);
        fwrite($file, $Detalle. PHP_EOL);
        echo "<BR>";
    }
    
    //TRAILER
    //echo "<BR> Importe Total ".$Monto." Cantidad Registros ".$Registro;
    
    $LargoRegistro=strlen($Registro);
    if ($LargoRegistro==1)  $CantidadRegistros="0000".$Registro;
    if ($LargoRegistro==2)  $CantidadRegistros="000".$Registro;
    if ($LargoRegistro==3)  $CantidadRegistros="00".$Registro;
    if ($LargoRegistro==4)  $CantidadRegistros="0".$Registro;
    if ($LargoRegistro==5)  $CantidadRegistros=$Registro;
    $MontoFinal=CompletarMontoConCeros($MontoFinal,10);
    $espacios="";
        for ($i = 0; $i < 29; $i++) {
            AgregarEspacios2($espacios);
        }

    $trailer="TE047820009999999999999999".$CantidadRegistros.$MontoFinal."0000000000".$espacios;
    Array_push($miArray,$trailer);
    echo $trailer;
    fwrite($file, $trailer. PHP_EOL);
    fclose($file);
    
    escribirArrayEnArchivo ($miArray,"/tmp/novcar.782");

    echo "<BR>";echo "<BR>";
?>
<center><a href="Novcar/novcar.782">Descargar Archivo Generado <br> Debe de llamarse NOVCAR.782 para Subirlo a la Plataforma del Banco</a></center>
<br><br>

<a href="index.php" class="btn-flotante">Volver Atras</a>
