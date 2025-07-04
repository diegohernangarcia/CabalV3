<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<device-width>, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<?php
        include ('./BD/Funciones.php');
        $numerooriginal=5626.25;
        $convertido=formatearNumero($numerooriginal);
        echo "NUMERO ORIGINAL $numerooriginal - Numero Convertido $convertido";
    ?>
</body>
</html>