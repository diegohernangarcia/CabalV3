<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link href="Bootstrap/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <link rel="stylesheet" type="text/css" href="css/estilos.css" />  -->
    <link rel="stylesheet" type="text/css" href="css/estilos2.css" />
    <link rel="icon" href="img/favicon.png">
    <title>Verificar Archivo NOVCAR.782</title>
</head>

<body>
    <div class="ayuda">
        <BR>
        <h1>VERIFICAR ARCHIVO NOVCAR.782</h1>
    </div>
    <?php

function pasaramoneda ($valor) {
    $largo=strlen($valor);
    $entera=substr($valor,0,$largo-2);
    $decimal=substr ($valor,$largo-2,2);
    $numero="$entera.$decimal";
    if ($valor==0) {$numero=0;}
    return money_format('%.2n', $numero);
}
function esta_codificado_para_windows($archivo) {
    $contenido = file_get_contents($archivo);
    if (strpos($contenido, "\r\n") !== false) {
        return true; // Contiene el formato de salto de línea de Windows
    } else {
        return false; // No está en formato de salto de línea de Windows
    }
}
    // Nombre del archivo
    $nombreArchivo = 'archivos/novcar.782';

    // Lee el archivo línea por línea y almacena cada línea en un array
    $lineasArray = file($nombreArchivo, FILE_IGNORE_NEW_LINES);

    // Verifica si se ha leído correctamente
    if ($lineasArray !== false) {
        // Imprime cada línea del array
        $i = 0;
        $largoarray = count($lineasArray);
        foreach ($lineasArray as $linea) {
            $i++;
            if ($i == 1) {
                $h1 = substr($linea, 0, 4);
                $h2 = substr($linea, 4, 3);
                $h3 = substr($linea, 7, 3);
                $h4 = substr($linea, 10, 16);
                $h5 = substr($linea, 26, 6);
                $fecha = substr($h5, 0, 2) . "/" . substr($h5, 2, 2) . "/" . substr($h5, 4, 2);
                $h6 = substr($linea, 32, 6);
                $hora = substr($h6, 0, 2) . ":" . substr($h6, 2, 2) . ":" . substr($h6, 4, 2);
                $h7 = substr($linea, 39, 42);

    ?>

                <table>
                    <caption> HEADER </caption>
                    <thead>
                        <tr>
                            <th>TE04</th>
                            <th>782 (Entidad Emisora)</th>
                            <th>000 (Sucursal)</th>
                            <th>Ceros</th>
                            <th>Fecha Proceso</th>
                            <th>Hora Proceso</th>
                            <th>Largo Fila</th>
                        </tr>
                        <td><?php echo $h1 ?></td>
                        <td><?php echo $h2 ?></td>
                        <td><?php echo $h3 ?></td>
                        <td><?php echo $h4 ?></td>
                        <td><?php echo $fecha ?></td>
                        <td><?php echo $hora ?></td>
                        
                        <?php
                        if (strlen($linea)==80) {
                            ?>
                               <td class="h4-ok">80</td> 
                            <?php
                            
                        }
                        if (strlen($linea)<>80) {
                            ?>
                                <td class="h4-bad">ERROR</td> </div><?php
                        }
                        ?>
                </table>
                <br><br>

                <?php


                ?>
                <!-- <div class="ayuda">
                    <BR>
                    <h3>INICIO HEADER</h3>
                </div> -->

                <?php
                //  echo "$h1 $h2 $h3 $h4 $fecha $hora $h7 (Largo de la Linea " . strlen($linea) . ")<br>";
                ?>
                <!-- <div class="ayuda">

                    <h3>FIN HEADER</h3>
                </div> -->

            <?php
            }
            if ($i == 2) {
            ?>
                <!-- <div class="ayuda">
                    <h3>INICIO DETALLES</h3>
                </div> -->
                <table border="1" bordercolor="black" cellpadding="4" cellspacing="4" center>
                    <caption> DETALLE </caption>
                    <thead>
                        <tr>
                            <th>TE01</th>
                            <th>782001</th>
                            <th>Numero Cuenta</th>
                            <th>63</th>
                            <th>39</th>
                            <th>Importe Compras</th>
                            <th>Importe Cajero</th>
                            <th>Motivo</th>
                            <th>Numero de Carga</th>
                            <th>Largo Fila</th>
                        </tr>
                    </thead>


                <?php
            }

            //echo "TE04 782001 NUMERO CUENTA  63  39 COMPRAS  CAJERO    MOTIVO NUMERO  <bR>";
            if (($i > 1) && ($i < $largoarray)) {
                $largo = strlen($linea);
                $d1 = substr($linea, 0, 4);
                $d2 = substr($linea, 4, 6);
                $d3 = substr($linea, 10, 16);
                $d4 = substr($linea, 26, 2);
                $d5 = substr($linea, 28, 2);
                $d6 = substr($linea, 30, 9); //importe compras
                //$compras = "$" . substr($d6, 0, 7) . "." . substr($d6, 7, 2);
                $d6=pasaramoneda($d6);
                $d7 = substr($linea, 39, 9); //importe cajero
                $d7=pasaramoneda($d7);
                $d8 = substr($linea, 48, 6);
                $d9 = substr($linea, 54, 8); //NUMERO DE CARGA
                // echo "$d1 $d2 $d3 $d4 $d5 $d6 $d7 $d8 $d9 <br>";
                ?>
                    <tr>
                        <td><?php echo $d1 ?></td>
                        <td><?php echo $d2 ?></td>
                        <td><?php echo $d3 ?></td>
                        <td><?php echo $d4 ?></td>
                        <td><?php echo $d5 ?></td>
                        <td><?php echo $d6 ?></td>
                        <td><?php echo $d7 ?></td>
                        <td><?php echo $d8 ?></td>
                        <td><?php echo $d9 ?></td>
                        
                        <?php
                        if ($largo==80) {
                            ?>
                               <td class="h4-ok">80</td> 
                            <?php
                            
                        }
                        if ($largo<>80) {
                            ?>
                                <td class="h4-bad">ERROR</td> </div><?php
                        }
                        
                        ?>
                    </tr>


            <?php
                //echo "$linea Largo de la linea $largo<br>"; // Imprime la línea y agrega un salto de línea HTML
            }
        }
            ?>
                </table> <?php
                            // Suponiendo que $lineasArray es el array que contiene las líneas del archivo

                            if ($i == $largoarray) {
                                echo "<br>";
                                //echo $linea;
                                $h1 = substr($linea, 0, 4);
                                $h2 = substr($linea, 4, 3);
                                $h3 = substr($linea, 7, 3);
                                $h4 = substr($linea, 10, 16);
                                $h5 = substr($linea, 26, 5);
                                //$fecha = substr($h5, 0, 2) . "/" . substr($h5, 2, 2) . "/" . substr($h5, 4, 2);
                                $h6 = substr($linea, 31, 10);
                                
                                $h6=pasaramoneda($h6);
                               // $h6=money_format('%.2n', $h6);
                                //$hora = substr($h6, 0, 2) . ":" . substr($h6, 2, 2) . ":" . substr($h6, 4, 2);
                                $h7 = substr($linea, 42, 10);
                                $h7=pasaramoneda($h7);
                            ?>

                    <table>
                        <caption> TRAILER </caption>
                        <thead>
                            <tr>
                                <th>TE04</th>
                                <th>782 (Entidad Emisora)</th>
                                <th>000 (Sucursal)</th>
                                <th>Todos 9</th>
                                <th>Cantidad Registros</th>
                                <th>Sumatoria Compras</th>
                                <th>Sumatoria Cajeros</th>
                                <th>Largo Fila</th>
                            </tr>
                            <td><?php echo $h1 ?></td>
                            <td><?php echo $h2 ?></td>
                            <td><?php echo $h3 ?></td>
                            <td><?php echo $h4 ?></td>
                            <td><?php echo $h5 ?></td>
                            <td><?php echo $h6 ?></td>
                            <td><?php echo $h7 ?></td>

                           
                            <?php
                        if (strlen($linea)==80) {
                            ?>
                               <td class="h4-ok">80</td> 
                            <?php
                            
                        }
                        if (strlen($linea)<>80) {
                            ?>
                                <td class="h4-bad">ERROR</td> </div><?php
                        }
                        ?>
                    </table>
                    <br><br>

                    

            <?php
                
                if (esta_codificado_para_windows($nombreArchivo)) {
                    //echo "El archivo está codificado para Windows.";
                    ?>
                    <div class="h4-ok">
                        El archivo está codificado para Windows 
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="h4-bad">
                    El archivo está codificado para Linux. Error 
                    </div>
                    <h4 Color:red></h4>
                    <?php
                }
                ?>
                    <br>
                    <div class="h4-bad">
                    SOLO FALTA VERIFICAR DE FORMA MANUAL QUE EL ARCHIVO TERMINE CON SOLO UN SALTO DE LINEA 
                    </div>
                    <h4 Color:red></h4>
                    <?php
                            }
                        } else {
                            // Si hay un error al leer el archivo
                            echo "No se pudo leer el archivo.";
                        }
            ?>
<a center href="index.php" class="btn-flotante">Volver Al Inicio</a>
</body>
</html>