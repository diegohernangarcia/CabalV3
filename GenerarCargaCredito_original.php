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
<h1> Generar Archivo Acreditacion Credito Tarjeta Cabal </h1>
  <h2 class="display-2">  </h2>

<?php
    require("BD/UsarGestion.php");
    require_once("BD/Funciones.php");
    require_once("BD/BD.php");
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
        AgregarEspacios($espacios);
     }
     $header="TE047820000000000000000000".$fecha.$hora.$espacios;
     echo $header;
     echo "<BR>";
     //REGISTRO DETALLE
     $Registro=0;
     $Monto=0;
     foreach ($conn->query($sql) as $row) {
        $NumeroCuenta=$row['NumeroCuenta'];
        $ImporteCompra=$row['ImporteCompra'];
        $Monto=$Monto+$ImporteCompra;
        $MontoCompleto="12345";
        $Registro++;
        $LargoMonto=strlen($ImporteCompra);
        
        // die($ImporteCompra);
        if ($LargoMonto==1)  $MontoCompleto="000000".$ImporteCompra."00";
        if ($LargoMonto==2)  $MontoCompleto="00000".$ImporteCompra."00";
        if ($LargoMonto==3)  $MontoCompleto="0000".$ImporteCompra."00";
        if ($LargoMonto==4)  $MontoCompleto="000".$ImporteCompra."00";
        if ($LargoMonto==5)  $MontoCompleto="00".$ImporteCompra."00";
        if ($LargoMonto==6)  $MontoCompleto="0".$ImporteCompra."00";
        if ($LargoMonto==7)  $MontoCompleto=$ImporteCompra."00";
        die("MONTO COMPLETO ".$MontoCompleto);
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
            AgregarEspacios($espacios);
        }
        $Detalle="TE04782001".$NumeroCuenta."6339".$MontoCompleto."000000000006239".$NumeroRegistro.$espacios;
        echo $Detalle;
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
    $LargoMonto=strlen($Monto);
    if ($LargoMonto==1)  $MontoFinal="0000000".$Monto."00";
    if ($LargoMonto==2)  $MontoFinal="000000".$Monto."00";
    if ($LargoMonto==3)  $MontoFinal="00000".$Monto."00";
    if ($LargoMonto==4)  $MontoFinal="0000".$Monto."00";
    if ($LargoMonto==5)  $MontoFinal="000".$Monto."00";
    if ($LargoMonto==6)  $MontoFinal="00".$Monto."00";
    if ($LargoMonto==7)  $MontoFinal="0".$Monto."00";
    if ($LargoMonto==8)  $MontoFinal=$Monto."00";
    $espacios="";
        for ($i = 0; $i < 29; $i++) {
            AgregarEspacios($espacios);
        }

    $trailer="TE047820009999999999999999".$CantidadRegistros.$MontoFinal."0000000000".$espacios;
    echo $trailer;
    echo "<BR>";echo "<BR>";
?>
<a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>
