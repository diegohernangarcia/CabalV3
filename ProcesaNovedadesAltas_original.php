<!--
Procesa el Archivo ALTASUCBPRE Enviado por el Banco
y guarda los registros en la tabla Beneficiario
-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="css/estilos.css" />
  <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
  <title>Devolucion Altas Cabal</title>
  <link rel="icon" href="img/favicon.png">

  
</head>
<body>
  <h1> </h1>
  <h2 class="display-2">Devolucion Altas CABAL </h2>
<?php
require_once("BD/BD.php");
$i=0;
$datos=array();
$queArchivo="archivos/MovAltas.txt";
$DNIaConsultar="";
$handle=fopen($queArchivo, "r") or die("¡Error al leer el archivo!");
// (B) LEER LÍNEA POR LÍNEA
while (($line = fgets($handle)) !== false) {
// Para gestionar mejor la memoria, también se puede especificar cuántos bytes se van a leer de una vez
// while (($line = fgets($handle, 4096)) !== false) {
  //echo $line; echo "<br>";
  $datos[$i]=$line;
  $i++;
}
// (C) CERRAR EL ARCHIVO
fclose($handle);
//echo "<br><br><br><br>"."Recorro Array<br><br>";
//print_r($datos);
$Beneficiario=array();
$i=0;
$indice=1;
$Beneficiario[0][1]= "Apellido";
$Beneficiario[0][2]= "Nombre";
$Beneficiario[0][3]= "Calle";
$Beneficiario[0][4]= "Numero";
$Beneficiario[0][5]= "Piso";
$Beneficiario[0][6]= "Departamento";
$Beneficiario[0][7]= "Localidad";
$Beneficiario[0][8]= "Provincia";
$Beneficiario[0][9]= "Sexo";
$Beneficiario[0][10]="Entidad Emisora";
$Beneficiario[0][11]= "Fecha Proceso";
$Beneficiario[0][12]= "Numero Cuenta";
$Beneficiario[0][13]= "Numero Tarjeta";
$Beneficiario[0][14]= "Codigo Postal";
$Beneficiario[0][15]= "Telefono Caracteristica";
$Beneficiario[0][16]= "Telefono Numero";
$Beneficiario[0][17]= "Estado Civil";
$Beneficiario[0][18]= "Fecha Nacimiento";
$Beneficiario[0][19]= "Tipo Documento";
$Beneficiario[0][20]= "Numero Documento";
$Beneficiario[0][21]= "Provincia Documento";
$Beneficiario[0][22]= "CUIT";
$Beneficiario[0][23]= "Codigo Ingreso";
$Beneficiario[0][24]= "Codigo Rechazo";
foreach ($datos as $valor) {
    $Operacion= substr($valor,20,2);
    //echo $valor."<br><br><br><br><br>";
    $R1=array();
    $R2=array();
    $R3=array();
    //echo "Codigo Operacion ".$Operacion."<br><br><br><br><br>";
    switch ($Operacion) {
      case 1 : 
        $R1[0]=substr($valor,0,4); //TE01
        $R1[1]=substr($valor,4,16);//N° TARJETA
        //$LineaDatos[]=substr($valor,,);
        $R1[2]=substr($valor,20,2); //COD OPERACION
        $R1[3]=substr($valor,22,6); //ENTIDAD y SUCURSAL
        $R1[4]=substr($valor,28,25); // APELLIDO Y NOMBRE
        $R1[5]=substr($valor,53,25);//CALLE
        $R1[6]=substr($valor,78,5);//NUMERO
        $R1[7]=substr($valor,83,2);//PISO
        $R1[8]=substr($valor,85,2);//Departamento
        $R1[9]=substr($valor,87,30); //Localidad
        $R1[10]=substr($valor,117,2);//Provincia
        $R1[11]=substr($valor,121,2); //PAIS
        $R1[12]=substr($valor,122,1); //SEXO
        $R1[13]=substr($valor,123,5); //Numero Formulario
        $R1[14]=substr($valor,128,1); //Codigo Ingreso 0 Aceptado - 1 Rechazado
        $R1[15]=substr($valor,129,36); //Codigo Rechazo
        $R1[16]=substr($valor,165,3); //FILLER
        $R1[17]=substr($valor,168,6); //ENTIDAD y SUCURSAL
        $R1[18]=substr($valor,174,6); // FECHA PROCESO AAMMDD
        $R1[19]=substr($valor,180,16); // Numero Cuenta
        $NombreBeneficiario=$R1[4];
        break;
      case 2 :
        //$R2[]=substr($valor,,);
        $R2[0]=substr($valor,0,4); // TE01
        $R2[1]=substr($valor,4,16);// Numero Tarjeta Asignado
        $R2[2]=substr($valor,20,2); // Cod. Operacion
        $R2[3]=substr($valor,22,8); // Cod. Postal
        $R2[4]=substr($valor,30,5); // Caracteristica Telefono
        $R2[5]=substr($valor,35,8); // Numero Telefono
        $R2[6]=substr($valor,43,1); //Estado Civil
        $R2[7]=substr($valor,44,8); // Fehc Nacimiento DDMMAAAA
        $R2[8]=substr($valor,52,2); // Tipo Documento
        $R2[9]=substr($valor,54,11); // Numero Documento
        $R2[10]=substr($valor,65,2); // Provincia del Documento
        $R2[11]=substr($valor,67,11); // CUIL/CUIT
        $R2[12]=substr($valor,78,13); //Filler
        $R2[13]=substr($valor,91,7); // Grupo Afinidad (Todos Ceros)
        $R2[14]=substr($valor,98,25); // Mensaje Tarjeta
        $R2[15]=substr($valor,123,5); // Numero Formulario
        $R2[16]=substr($valor,128,1); // Codigo Ingreso 0 Aceptado 1 Rechazado
        $R2[17]=substr($valor,129,36); //Codigo Rechazo
        $R2[18]=substr($valor,168,6); //Entidad y Sucursal Emisora
        $R2[19]=substr($valor,174,6); // Fecha de Proceso AAMMDD
        $R2[20]=substr($valor,180,16); // Numero de cuenta
        $DNIaConsultar=$R2[9];
        break;
      case 3 :
        //$R3[]=substr($valor,,);//
        $R3[0]=substr($valor,0,3);// TE01
        $R3[1]=substr($valor,4,16);// Numero Tarjeta
        $R3[2]=substr($valor,20,2);// Codigo Operacion = 03
        $R3[3]=substr($valor,22,2);// Cod. Profesion = 99
        $R3[4]=substr($valor,24,1);// Tipo Producto =P
        $R3[5]=substr($valor,25,1);// Señal de Entrega en filial del sobre  pin " " Dist Normal S=Entrega en Filial Siempre S
        $R3[6]=substr($valor,26,1);// Señal de Entrega en filial del Tarjeta " " Dist Normal S=Entrega en Filial Siempre S
        $R3[7]=substr($valor,27,1);// Señal de Vinculacion en Alta  " " No tiene Vinculacion  S=Tiene Vinculacion Siempre Espacio " "
        $R3[8]=substr($valor,28,85);// Reserva (Siempre Espacios)
        $R3[9]=substr($valor,113,1);// Clase Siempre A
        $R3[10]=substr($valor,114,1);// Tipo Pin Tarjeta Precargada Siempre 1   0=PIN CARTA PAPEL 1=PIN TELEFONICO
        $R3[11]=substr($valor,115,5);// Reserva (Siempre Espacios)
        $R3[12]=substr($valor,120,3);// Siempre TPC
        $R3[13]=substr($valor,123,5);// Numero Formulario
        $R3[14]=substr($valor,128,1);// Codigo Ingreso 0 Aceptada 1= Rechazado 
        $R3[15]=substr($valor,129,36);// Codigo Rechazo (Ver Anexo 212)
        $R3[16]=substr($valor,165,3);// FILLER Espacios
        $R3[17]=substr($valor,168,6);// Entidad y Sucursal
        $R3[18]=substr($valor,174,6);// Fecha de Proceso AAMMDD
        $R3[19]=substr($valor,180,16);// Numero de Cuenta
        break;
    }
    //echo "<BR>";
    $esta=EstaDNI($DNIaConsultar);
    if ($esta==true) {
      echo "<h5> EL BENEFICIARIO ".$NombreBeneficiario." Con DNI ".$DNIaConsultar." YA SE ENCUENTRA CARGADO COMO BENEFICIARIO </h5>"; 
      echo "<h5> Por favor verifique que no este cargado </h5>";
      echo "<h4> PROCESO INTERRUMPIDO </h4>";
      ?> "<a href="javascript:history.back()" class="btn-flotante">Volver Atras</a> <?php
      die();
    }
        

     






    if (sizeof($R1)!=0) {
      //echo "########################R1########################";echo "<br>";
      //var_dump($R1);        
      $LargoNombre=strlen($R1[4]);
      $pos=strpos($R1[4],"/");
      $Apellido=substr($R1[4],0,$pos);
      $Nombre=substr($R1[4],$pos+1,$LargoNombre-$pos);
      $Beneficiario[$indice][1]= $Apellido;// Apellido
      $Beneficiario[$indice][2]= $Nombre;// Nombre
      $Beneficiario[$indice][3]= $R1[5];// Calle
      $Beneficiario[$indice][4]= $R1[6];// Numero
      $Beneficiario[$indice][5]= $R1[7];// Piso
      $Beneficiario[$indice][6]= $R1[8];// Departamento
      $Beneficiario[$indice][7]= $R1[9];// Localidad
      $Beneficiario[$indice][8]= $R1[10];// Provincia
      $Beneficiario[$indice][9]= $R1[12];// Sexo;
      $Beneficiario[$indice][10]= $R1[17];// Entidad Emisora
      $Beneficiario[$indice][11]= $R1[18];// Fecha Proceso
      $Beneficiario[$indice][12]=$R1[19] ;// NUmero Cuenta
      $Beneficiario[$indice][13]= $R1[1] ;// Numero Tarjeta
      }
      
    if (sizeof($R2)!=0) {
      //echo "########################R2########################"; 
      //var_dump($R2);
      $Beneficiario[$indice][14]= $R2[3];// Codigo Postal
      $Beneficiario[$indice][15]= $R2[4];// Telefono Caracteristica
      $Beneficiario[$indice][16]= $R2[5];// Telefono Numero
      $Beneficiario[$indice][17]= $R2[6];// Estado Civil
      $Beneficiario[$indice][18]= $R2[7];// Fecha Nacimiento
      $Beneficiario[$indice][19]= $R2[8] ;// Tipo Documento
      $Beneficiario[$indice][20]= $R2[9];// Numero Documento
      $Beneficiario[$indice][21]= $R2[10];// Provincia Docuemento
      $Beneficiario[$indice][22]= $R2[11] ;// CUIT
      $Beneficiario[$indice][23]= $R2[16];// Codigo Ingreso
      $Beneficiario[$indice][24]= $R2[17];// Codigo Rechazo
    }
    if (sizeof($R3)!=0) {
     // echo "########################R3########################"; echo "<br>";
      //var_dump($R3);
    }
  if ($Operacion==3) {
    $indice++;
  }
}
  echo '<table class="table table-bordered border-primary" border="2" width="800" align="center" ><tr color="#c0c0c0">';
  for ($x=1;$x<=24;$x++){
    echo '<td>' . '<b>'.$Beneficiario[0][$x].'</b></td>';
  }
  
  for ($x=1;$x<=sizeof($Beneficiario)-1;$x++) {
        echo '<tr>';
        for ($n=1;$n<=24;$n++) { 
            echo '<td>'.$Beneficiario[$x][$n].'</td>'; }
        echo '</tr>';
  }
  echo '</table>'; 

  // GRABAR EN LA BASE DE DATOS  
  require_once("BD/UsarGestion.php");
  

  $my_Insert_Statement = $conn->prepare("INSERT INTO beneficiario (Apellido,Nombre,Calle,Numero,Piso,Departamento,Localidad,Provincia,Sexo,EntidadEmisora,FechaProceso,NumeroCuenta,NumeroTarjeta,CodigoPostal,TelefonoCaracteristica,TelefonoNumero,EstadoCivil,FechaNacimiento,TipoDocumento,NumeroDocumento,ProvinciaDocumento,Cuit,CodigoIngreso,CodigoRechazo) VALUES (:Apellido,:Nombre,:Calle,:Numero,:Piso,:Departamento,:Localidad,:Provincia,:Sexo,:EntidadEmisora,:FechaProceso,:NumeroCuenta,:NumeroTarjeta,:CodigoPostal,:TelefonoCaracteristica,:TelefonoNumero,:EstadoCivil,:FechaNacimiento,:TipoDocumento,:NumeroDocumento,:ProvinciaDocumento,:Cuit,:CodigoIngreso,:CodigoRechazo)");
  for ($reg=1;$reg<=sizeof($Beneficiario)-1;$reg++) {
        $my_Insert_Statement->bindParam(':Apellido', $Beneficiario[$reg][1]);
        $my_Insert_Statement->bindParam(':Nombre', $Beneficiario[$reg][2]);
        $my_Insert_Statement->bindParam(':Calle', $Beneficiario[$reg][3]);
        $my_Insert_Statement->bindParam(':Numero', $Beneficiario[$reg][4]);
        $my_Insert_Statement->bindParam(':Piso', $Beneficiario[$reg][5]);
        $my_Insert_Statement->bindParam(':Departamento', $Beneficiario[$reg][6]);
        $my_Insert_Statement->bindParam(':Localidad', $Beneficiario[$reg][7]);
        $my_Insert_Statement->bindParam(':Provincia', $Beneficiario[$reg][8]);
        $my_Insert_Statement->bindParam(':Sexo', $Beneficiario[$reg][9]);
        $my_Insert_Statement->bindParam(':EntidadEmisora', $Beneficiario[$reg][10]);
        $my_Insert_Statement->bindParam(':FechaProceso', $Beneficiario[$reg][11]);
        $my_Insert_Statement->bindParam(':NumeroCuenta', $Beneficiario[$reg][12]);
        $my_Insert_Statement->bindParam(':NumeroTarjeta', $Beneficiario[$reg][13]);
        $my_Insert_Statement->bindParam(':CodigoPostal', $Beneficiario[$reg][14]);
        $my_Insert_Statement->bindParam(':TelefonoCaracteristica', $Beneficiario[$reg][15]);
        $my_Insert_Statement->bindParam(':TelefonoNumero', $Beneficiario[$reg][16]);
        $my_Insert_Statement->bindParam(':EstadoCivil', $Beneficiario[$reg][17]);
        $my_Insert_Statement->bindParam(':FechaNacimiento', $Beneficiario[$reg][18]);
        $my_Insert_Statement->bindParam(':TipoDocumento', $Beneficiario[$reg][19]);
        $my_Insert_Statement->bindParam(':NumeroDocumento', $Beneficiario[$reg][20]);
        $my_Insert_Statement->bindParam(':ProvinciaDocumento', $Beneficiario[$reg][21]);
        $my_Insert_Statement->bindParam(':Cuit', $Beneficiario[$reg][22]);
        $my_Insert_Statement->bindParam(':CodigoIngreso', $Beneficiario[$reg][23]);
        $my_Insert_Statement->bindParam(':CodigoRechazo', $Beneficiario[$reg][24]);
        if ($my_Insert_Statement->execute()) {
         
        } else {
          echo '<h5 class="display-5">No Se Pudieron Guardar Los Registros en la Base de Datos</h5>';  
        }
  }
  echo '<h5 class="display-5">Registros Importado en la Base de Datos</h5>';
?> 
<a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>
</body>
</html>





