<!--
Lee archivo con los movimientos mensuales de las tarjetas cabal
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
    <h2 class="display-2">PROCESAR ACREDITACIONES SALDOS</h2>
    <?php
        
        require_once("BD/BD.php");
        $i=0;
        $datos=array();
        $queArchivo="archivos/RespuestaCargas.txt";
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
        $Movimiento=array();
        $i=0;
        $indice=1;
        $Movimiento[0][1]= "Cuenta";
        $Movimiento[0][2]= "Apellido y Nombre";
        $Movimiento[0][3]= "Importe Acreditado";
        $Movimiento[0][4]= "Codigo Error";
        $txt_file = fopen('archivos/RespuestaCargas.txt','r');
        $a = 1;
        
        while ($line = fgets($txt_file)) {
           
           if ($a>1) {
                $Movimiento[$a][1]= substr($line,10,16);
                //echo $Movimiento[$a][1]; echo "<br>";
                $Quien=DevuelvoApellidoyNombrePorNumeroCuenta($Movimiento[$a][1]);
                $Movimiento[$a][2]= $Quien;
                $Movimiento[$a][3]= substr($line,30,7);
                $Movimiento[$a][3]=$Movimiento[$a][3].".";
                $Movimiento[$a][3]= $Movimiento[$a][3].substr($line,37,2);
                $Movimiento[$a][3]=floatval($Movimiento[$a][3]);
                $Movimiento[$a][3]="$".number_format($Movimiento[$a][3], 2);
                $Movimiento[$a][4]= substr($line,77,3);
            }
            $TotalDepositado= substr($line,31,8);
            $a++;
          
        }
        //$TotalDepositado= substr($line,41,10);
           //unset($Movimiento[1]);
           
           //unset ($Movimiento[sizeof($Movimiento)]);
           
        fclose($txt_file);
        
        echo '<table class="table table-bordered border-primary" border="2" width="800" align="center" ><tr color="#c0c0c0">';
            for ($x=1;$x<=4;$x++){
                echo '<td>' . '<b>'.$Movimiento[0][$x].'</b></td>';
            }
            for ($x=2;$x<=sizeof($Movimiento)-1;$x++) {
            echo '<tr>';
                for ($n=1;$n<=4;$n++) { 
                    echo '<td>'.$Movimiento[$x][$n].'</td>'; }
                echo '</tr>';
            }
            echo '</table>'; 
        // GRABO DATOS EN BASE DE DATOS
        //echo "INSERT INTO movimientos (Banco,Sucursal,Cuenta,Tarjeta,TipoDocumento,Documento,NombreApellido,CodigoOperacion,Fecha,Comercio,Importe,Signo,Mes,Anio) VALUES (:Banco,:Sucursal,:Cuenta,:Tarjeta,:TipoDocumento,:Documento,:NombreApellido,:CodigoOperacion,:Fecha,:Comercio,:Importe,:Signo,:Mes,:Anio";
        //die();
       /* require_once("BD/UsarGestion.php");
        $my_Insert_Statement = $conn->prepare("INSERT INTO movimientos (Banco,Sucursal,Cuenta,Tarjeta,TipoDocumento,Documento,NombreApellido,CodigoOperacion,Fecha,Comercio,Importe,Signo,Mes,Anio) VALUES (:Banco,:Sucursal,:Cuenta,:Tarjeta,:TipoDocumento,:Documento,:NombreApellido,:CodigoOperacion,:Fecha,:Comercio,:Importe,:Signo,:Mes,:Anio)");
        for ($reg=1;$reg<=sizeof($Movimiento)-1;$reg++) {
              //$my_Insert_Statement->bindParam(':Apellido', $Beneficiario[$reg][1]);
              $my_Insert_Statement->bindParam(':Banco', $Movimiento[$reg][1]);
              $my_Insert_Statement->bindParam(':Sucursal', $Movimiento[$reg][2]);
              $my_Insert_Statement->bindParam(':Cuenta', $Movimiento[$reg][3]);
              $my_Insert_Statement->bindParam(':Tarjeta', $Movimiento[$reg][4]);
              $my_Insert_Statement->bindParam(':TipoDocumento', $Movimiento[$reg][5]);
              $my_Insert_Statement->bindParam(':Documento', $Movimiento[$reg][6]);
              $my_Insert_Statement->bindParam(':NombreApellido', $Movimiento[$reg][7]);
              $my_Insert_Statement->bindParam(':CodigoOperacion', $Movimiento[$reg][8]);
              $my_Insert_Statement->bindParam(':Fecha', $Movimiento[$reg][9]);
              $my_Insert_Statement->bindParam(':Comercio', $Movimiento[$reg][10]);
              $my_Insert_Statement->bindParam(':Importe', $Movimiento[$reg][11]);
              $my_Insert_Statement->bindParam(':Signo', $Movimiento[$reg][12]);
              $my_Insert_Statement->bindParam(':Mes', $Movimiento[$reg][13]);
              $my_Insert_Statement->bindParam(':Anio', $Movimiento[$reg][14]);
              if ($my_Insert_Statement->execute()) {
              } else {
                echo '<h5 class="display-5">No Se Pudieron Guardar Los Registros en la Base de Datos</h5>';  
              }
        }
        echo '<h5 class="display-5">Registros Importado en la Base de Datos</h5>';*/
        $TotalDepositado=number_format($TotalDepositado, 2);
        echo "TOTAL DEPOSITADO = $". $TotalDepositado;
    ?>  
    <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  
</body>