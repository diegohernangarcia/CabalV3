<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <title>BENEFICIARIO</title>
    <link rel="icon" href="img/favicon.png">
</head>

<body>
    <h2 class="display-2">BENEFICIARIO</h2>
    <?php
        $dni=($_POST['dni']);
        //echo $dni;
        require_once('BD/BD.php');
        $Quien=[];
        $Quien=DevuelvoDatosCuenta($dni);
        if ($Quien[0]==0) {
            ?>
            <h4 class="display-5" color="red">NO SE ENCUENTRA EL BENEFICIARIO SOLICITADO</h4>
            <a href="javascript:history.back()" class="btn btn-primary">Volver Atras</a>  

            <?php
               die();
        }
        ?>  <center>
            <table border center>
                  <tr>
                    <td></td>
                    <th></th>
                  </tr>
                <tr>
                    <th>Nombre Completo</th>
                    <td><?php echo $Quien[1] ." " . $Quien[2]." (" . $Quien[9].")"; ?> </td>
                </tr>
                <tr>
                    <th>Fecha Nacimiento</th>
                    <td><?php echo $Quien[12] ; ?> </td>
                </tr>
                <tr>
                    <th>DNI</th>
                    <td><?php echo $Quien[13] ." " . $Quien[14]; ?> </td>
                </tr>
                <tr>
                    <th>Numero Cuenta</th>
                    <td><?php echo $Quien[10] ; ?> </td>
                </tr>
                <tr>
                    <th>Numero Tarjeta</th>
                    <td><?php echo formatearTarjetaCredito($Quien[11]) ; ?> </td>
                </tr>
                <tr>
                    <th>CUIT</th>
                    <td><?php echo $Quien[15] ; ?> </td>
                </tr>
                <tr>
                    <th>Ubicacion</th>
                    <td><?php echo ($Quien[8]." ".$Quien[7]); ?> </td>
                </tr>
                <tr>
                    <th>Direccion</th>
                    <td><?php echo ($Quien[3]." ".$Quien[4]." ".$Quien[5]." ".$Quien[6]); ?> </td>
                </tr>
            </table>
            <br>
            <input type="button" class="btn btn-primary" value="Imprimir" onclick="javascript:window.print()"/>
            <a href="javascript:history.back()" class="btn btn-primary">Volver Atras</a>  

        <?php
    ?>


