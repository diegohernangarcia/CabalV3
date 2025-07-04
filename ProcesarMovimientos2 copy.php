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
    <h2 class="display-2">PROCESAR MOVIMIENTOS TARJETAS</h2>
    <?php
        $mes=($_POST['Mes']);
        $anio=($_POST['Anio']);
        $Archivo=($_POST['Archivo']);
        require("BD/BD.php");
        if (EstaFechaMovimiento ($mes,$anio,$Archivo)==true) {
            ?>
            <h5 class="display-5">El Mes de <?php echo $mes?>  de <?php echo $anio." Con la Carga ".$Archivo?> Ya Se Proceso y Esta Cargado En La Base De Datos</h5>
            <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  
            <?php
            die();
        } else {
            //echo '<h5 class="display-5">Seguimos Entonces</h5>';  
        }
       // echo $mes." ".$anio;
        require_once("BD/BD.php");
        $i=0;
        $datos=array();
        $queArchivo="archivos/Movimientos.txt";
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
        $Movimiento[0][1]= "Banco";
        $Movimiento[0][2]= "Sucursal";
        $Movimiento[0][3]= "Cuenta";
        $Movimiento[0][4]= "Tarjeta";
        $Movimiento[0][5]= "Tipo Doc";
        $Movimiento[0][6]= "Documento";
        $Movimiento[0][7]= "Nombre y Aplellido";
        $Movimiento[0][8]= "Codigo Operacion";
        $Movimiento[0][9]= "Fecha";
        $Movimiento[0][10]= "Comercio";
        $Movimiento[0][11]= "Importe";
        $Movimiento[0][12]= "Signo";
        $Movimiento[0][13]= "Mes";
        $Movimiento[0][14]= "Año";
        $Movimiento[0][15]= "Archivo";
        $Movimiento[0][16]= "Importe2";
        $Movimiento[0][17]= "Signo2";
        $txt_file = fopen('archivos/Movimientos.txt','r');
        $a = 1;
        while ($line = fgets($txt_file)) {
           // echo($a." ".$line)."<br>";
           $Movimiento[$a][1]= substr($line,0,3);
           //echo $Movimiento[$a][1]; echo "<br>";
           $Movimiento[$a][2]= substr($line,4,3);
           $Movimiento[$a][3]= substr($line,7,13);
           $Movimiento[$a][4]= substr($line,20,16);
           $Movimiento[$a][5]= substr($line,36,1);
           $Movimiento[$a][6]= substr($line,36,11);
           $Movimiento[$a][7]= substr($line,47,25);
           $CodigoOperacion=substr($line,72,3);
           if ($CodigoOperacion=="999") $codigo="SALDO INICIAL";
           if ($CodigoOperacion=="671") $codigo="CARGA/DESCARGA";
           if ($CodigoOperacion=="491") $codigo="AUTORIZACIONES";
           if ($CodigoOperacion=="241") $codigo="SALDO FINAL";

           $Movimiento[$a][8]= $codigo;
           $FechaInicial=substr($line,75,8);
           $Fecha=substr($FechaInicial,0,2)."/".substr($FechaInicial,2,2)."/".substr($FechaInicial,4,4);
           $Movimiento[$a][9]= $Fecha;
           $Movimiento[$a][10]= substr($line,83,25);
           $ImporteInicial=substr($line,108,11);
           $Importe=substr($ImporteInicial,0,9).".".substr($ImporteInicial,9,2);
           //$Importe=floatval($Importe);
           $ImporteInicial=substr($line,131,17);
           $Importe2=substr($ImporteInicial,0,15).".".substr($ImporteInicial,15,2);
           
           $Movimiento[$a][11]= $Importe;
           $Movimiento[$a][12]= substr($line,119,1);
           $Movimiento[$a][13]= $mes;
           $Movimiento[$a][14]= $anio;
           $Movimiento[$a][15]= $Archivo;
           $Movimiento[$a][16]= $Importe2;
           $Movimiento[$a][17]= substr($line,148,1);;
          // echo $Movimiento[$a][17]."<br>";
           $a++;
           }
        fclose($txt_file);
        
        echo '<table class="table table-bordered border-primary" border="2" width="800" align="center" ><tr color="#c0c0c0">';
            for ($x=1;$x<=17;$x++){
                echo '<td>' . '<b>'.$Movimiento[0][$x].'</b></td>';
            }
            for ($x=1;$x<=sizeof($Movimiento)-1;$x++) {
            echo '<tr>';
                for ($n=1;$n<=17;$n++) { 
                    echo '<td>'.$Movimiento[$x][$n].'</td>'; }
                echo '</tr>';
            }
            echo '</table>'; 
        // GRABO DATOS EN BASE DE DATOS
        //echo "INSERT INTO movimientos (Banco,Sucursal,Cuenta,Tarjeta,TipoDocumento,Documento,NombreApellido,CodigoOperacion,Fecha,Comercio,Importe,Signo,Mes,Anio) VALUES (:Banco,:Sucursal,:Cuenta,:Tarjeta,:TipoDocumento,:Documento,:NombreApellido,:CodigoOperacion,:Fecha,:Comercio,:Importe,:Signo,:Mes,:Anio";
        //die();
        require_once("BD/UsarGestion.php");
        $my_Insert_Statement = $conn->prepare("INSERT INTO movimientos (Banco,Sucursal,Cuenta,Tarjeta,TipoDocumento,Documento,NombreApellido,CodigoOperacion,Fecha,Comercio,Importe,Signo,Importe2, Signo2,Mes,Anio,Archivo) VALUES (:Banco,:Sucursal,:Cuenta,:Tarjeta,:TipoDocumento,:Documento,:NombreApellido,:CodigoOperacion,:Fecha,:Comercio,:Importe,:Signo,:Importe2,:Signo2,:Mes,:Anio,:Archivo)");
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
              $my_Insert_Statement->bindParam(':Archivo', $Movimiento[$reg][15]);
              $my_Insert_Statement->bindParam(':Importe2', $Movimiento[$reg][16]);
              $my_Insert_Statement->bindParam(':Signo2', $Movimiento[$reg][17]);
              if ($my_Insert_Statement->execute()) {
              } else {
                echo '<h5 class="display-5">No Se Pudieron Guardar Los Registros en la Base de Datos</h5>';  
              }
        }
        echo '<h5 class="display-5">Registros Importado en la Base de Datos</h5>';
    ?>  
    <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  
</body>