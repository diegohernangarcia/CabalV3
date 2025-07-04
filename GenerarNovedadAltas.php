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

  <title>Generar Archivo Novedades</title>
  <style>
      * {font-family: 'Courier New', Courier, monospace;}
</style>
</head>
<body>
<h1> Generar Archivo Novedades </h1>
  <h2 class="display-2">  </h2>


<?php
    require("BD/UsarGestion.php");
    require_once("BD/Funciones.php");
    require_once("BD/BD.php");
    $sql = 'SELECT * FROM altas';
    //print "Nombre de Beneficiario:<br>";
    $formulario=1;
    foreach ($conn->query($sql) as $row) {
		$LargoFormulario=strlen($formulario);
    if ($LargoFormulario==1)  $NumeroFormulario="0000".$formulario;
    if ($LargoFormulario==2)  $NumeroFormulario="000".$formulario;
    if ($LargoFormulario==3)  $NumeroFormulario="00".$formulario;
    if ($LargoFormulario==4)  $NumeroFormulario="0".$formulario;
    if ($LargoFormulario==5)  $NumeroFormulario=$formulario;
      $Apellido=$row['Apellido'];
        $LargoApellido=LargoString($Apellido);
        /*if ($LargoApellido>=16) {
          echo "<br><br><br><br><br><br><br><br><br><br><br><br><br>";
          echo "POR FAVOR CORRIJA EL ANCHO DEL APELLIDO <b> ".$Apellido. " " .$Nombre." </b>NO PUEDE SER MAYOR A 16 CARACTERES";
            die();
        }*/
        $reserva="";$reserva2="";
        $Nombre=$row['Nombre'];
        $ApellidoNombre=$Apellido."/".$Nombre;
        $DomicilioCalle=$row['DomicilioCalle'];
        $DomicilioNumero=$row['DomicilioNumero'];	
        $DomicilioPiso=$row['DomicilioPiso'];	
        $DomicilioDepartamento=$row['DomicilioDepartamento'];
        // if (strlen($DomicilioNumero==0)) $DomicilioNumero="  ";
        // if (strlen($DomicilioPiso==0)) $DomicilioPiso="  ";
        // if (strlen($DomicilioDepartamento==0)) $DomicilioDepartamento="  ";
        $Localidad=$row['Localidad'];
        $Provincia=$row['Provincia'];	
        $Sexo=$row['Sexo'];
        $CodigoPostal=$row['CodigoPostal'];
        $TelefonoCaracteristica=$row['TelefonoCaracteristica'];
        $TelefonoNumero=$row['TelefonoNumero'];
        $EstadoCivil=$row['EstadoCivil'];
        $FechaNacimiento=$row['FechaNacimiento'];
        $resultado = validarFechaDDMMAAAA($FechaNacimiento);
        // if ($resultado !="La fecha es válida.") {
        //   echo $FechaNacimiento."<br>";
        //     echo ("POR FAVOR VERIFICAR EL FORMATO DE LA FECHA DE NACIMIENTO. <br> $resultado");
        //     //die();
        // }
        $DocumentoTipo=$row['DocumentoTipo'];
        $DocumentoNumero=$row['DocumentoNumero'];
        $DocumentoProvincia=$row['DocumentoProvincia'];
        $CUIT=$row['CUIT'];

        $esta=EstaDNI($DocumentoNumero);
        if ($esta==true) {

          echo "<h5> El Beneficiario ".$ApellidoNombre." Con DNI ".$DocumentoNumero." YA se encuentra cargado como Beneficiario </H5>";
          echo "<h4> PROCESO INTERRUMPIDO </h4>"; 
          ?> <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a> <?php
          die();
        }
            
        //############################################################################
        // INICIO REGISTRO 1
        //############################################################################
        $largo=LargoString($ApellidoNombre);
        $cuantos=25-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
            AgregarEspacios($ApellidoNombre);
        }
        $largo=LargoString($DomicilioCalle);
        $cuantos=25-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
            AgregarEspacios($DomicilioCalle);
        }
        $largo=LargoString($DomicilioNumero);
        $cuantos=5-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
            AgregarEspacios($DomicilioNumero);
        }
        $largo=LargoString($DomicilioPiso);
        $cuantos=2-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
          AgregarEspacios($DomicilioPiso);
        }
        $largo=LargoString($DomicilioDepartamento);
        $cuantos=2-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
          AgregarEspacios($DomicilioDepartamento);
        }
        $largo=LargoString($Localidad);
        $cuantos=30-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
          AgregarEspacios($Localidad);
        }
        $largo=strlen($CodigoPostal);
        if ($largo==4) {
          $CodigoPostal="&nbsp;".$CodigoPostal."&nbsp;&nbsp;&nbsp;";
        }
        
        
        //$NumeroFormulario;die();
        echo "TE01"."604386"."60"."00000000"."01"."782001".$ApellidoNombre.$DomicilioCalle.$DomicilioNumero.$DomicilioPiso.$DomicilioDepartamento.$Localidad."16000".$Sexo.$NumeroFormulario."<br>";
        
        //############################################################################
        // FIN REGISTRO 1
        //############################################################################
        //############################################################################
        // INICIO REGISTRO 2
        //############################################################################
        $Filler="";
        $GrupoAfinidad="0000000";
        $Mensaje="";
        for ($i = 0; $i <=13; $i++) {
          AgregarCeros($Filler);
        }
        /*for ($i = 0; $i <=7; $i++) {
          AgregarCeros($GrupoAfinidad);
        }*/
        for ($i = 0; $i <=25; $i++) {
          AgregarCeros($Mensaje);
        }
        /*$largo=LargoString($TelefonoNumero);
        $cuantos=8-$largo;
        for ($i = 0; $i <=$cuantos-1; $i++) {
          AgregarCeros($TelefonoNumero);
        } */
        CompletarCaracteristica($TelefonoCaracteristica);
        CompletarNumeroTelefono($TelefonoNumero);
        CompletarTipoDocumento($DocumentoTipo);
        CompletarNumeroDocumento($DocumentoNumero);
        CompletarCUIT($CUIT);
        CompletarFechaNacimiento($FechaNacimiento);
        CompletarDocumentoProvincia($DocumentoProvincia);
        $DocumentoProvincia="00";
        $extras="00000000000000000000&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "TE01"."604386"."60"."00000000"."02".$CodigoPostal.$TelefonoCaracteristica.$TelefonoNumero.$EstadoCivil.$FechaNacimiento.$DocumentoTipo.$DocumentoNumero.$DocumentoProvincia.$CUIT.$extras.$NumeroFormulario."<br>";
        //############################################################################
        // FIN REGISTRO 2
        //############################################################################
        //############################################################################
        // INICIO REGISTRO 3
        //############################################################################
        for ($i=0;$i <=84;$i++) {
            AgregarEspacios($reserva);
        }
        for ($i=0;$i <=4;$i++) {
          AgregarEspacios($reserva2);
      }
        
        echo "TE01"."604386"."60"."00000000"."03"."99"."P"."S"."S"."&nbsp;".$reserva."A"."1".$reserva2."TPC".$NumeroFormulario."<br>";
        //############################################################################
        // FIN REGISTRO 3
        //############################################################################
        
        //$formulario=$formulario+1;
        
        $formulario++;

    }
	echo "<br>";
  
  //echo "&nbsp;";
  ?>
  <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>
</body>
</html>  
