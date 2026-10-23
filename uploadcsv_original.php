
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
    <title>Procesar Cargas de Credito</title>
</head>
<body>
<?php
    
    if(isset($_FILES['archivo'])) {
        $archivo = $_FILES['archivo'];
        $nombre = $archivo['name'];
        $tipo = $archivo['type'];
        $tamano = $archivo['size'];
        $tmp = $archivo['tmp_name'];
        if ($tipo=="text/csv") {
            // Mover el archivo a la ubicación deseada
            move_uploaded_file($tmp, 'archivos/'.$nombre);
            //echo "Se subio el archivo ".$nombre;
            require_once("ProcesarCSVAcreditacion.php");





        } else {
            echo "El archivo no posee el formato solicitado";
        }
    }
?>           
    <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>
</body>
</html>
