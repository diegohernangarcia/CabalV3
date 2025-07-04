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
    

    <style>
        table {
            border: #b2b2b2 1px solid ;
            margin: 0 auto;
}   
        h2  {
            text-align: center;
        }
td, th {
  border: black 1px solid;
}
    </style>
</head>

<body>
    <h2> PROCESAR MAESTRO TARJETAS </h2>
    <?php
    // Recibe el nombre del archivo desde la URL
if (isset($_GET['archivo']) && !empty($_GET['archivo'])) {
    $nombreArchivo = htmlspecialchars($_GET['archivo']); // Escapa caracteres especiales por seguridad

    // Aquí puedes realizar las acciones necesarias con el nombre del archivo
    //echo "<h1>Nombre del archivo recibido: " . $nombreArchivo . "</h1>";
    // Ejemplo: Guardar el nombre en una base de datos, crear un archivo, etc.
} else {
    echo "<h1>Error: No se recibió un nombre de archivo válido.</h1>";
}
    

    require_once("BD/Funciones.php");
    require_once("BD/BD.php");
    function convertirFechaMaster($fecha)
    {
        // Asegurarse de que la cadena de fecha tenga exactamente 6 caracteres
        if (strlen($fecha) !== 6) {
            return "Formato incorrecto";
        }

        // Extraer el día, mes y año de la fecha
        $dia = substr($fecha, 0, 2);  // Primeros 2 caracteres
        $mes = substr($fecha, 2, 2);  // Siguientes 2 caracteres
        $anio = substr($fecha, 4, 2); // Últimos 2 caracteres

        // Devolver la fecha en el formato "DD/MM/AA"
        return "$dia/$mes/$anio";
    }
    function DevuelveCausa($id){
        switch ($id) {
            case 00 : return "HABILITADA";
            case 01 : return "DENUNCIA POR EXTRAVIO";
            case 02 : return "ANULACION POR ENTIDAD";
            case 03 : return "ANULACION";
            case 04 : return "DENUNCIA POR HURTO";
            case 07 : return "REEMPLAZO";
            case 08 : return "NO HABILITADA";
            case 14 : return "BLOQUEO TRANSITORIO";
            default : return "---------------------";
        }

    }
    //$causas=array("
   // $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $i = 0;
    $datos = array();
    $queArchivo = "archivos/MAESPLAS.txt";

    $handle = fopen($queArchivo, "r") or die("¡Error al leer el archivo!");
    while (($line = fgets($handle)) !== false) {
        // Para gestionar mejor la memoria, también se puede especificar cuántos bytes se van a leer de una vez
        // while (($line = fgets($handle, 4096)) !== false) {
        //echo $line; echo "<br>";
        $datos[$i] = $line;
        $i++;
    }
    // (B) LEER LÍNEA POR LÍNEA
    while (($line = fgets($handle)) !== false) {
        $datos[$i] = $line;
        $i++;
    }
    $txt_file = fopen('archivos/MAESPLAS.txt', 'r');
    $Movimiento[0][1]="Nro";
    $Movimiento[0][2] = "Numero Cuenta";
    $Movimiento[0][3] = "Numero Tarjeta";
    $Movimiento[0][4] = "Nombre y Apellido";
    $Movimiento[0][5] = "Tipo Documento";
    $Movimiento[0][6] = "Nro Documento";
    $Movimiento[0][7] = "Causa";
    $Movimiento[0][8] = "Fecha Alta";
    $Movimiento[0][9] = "Fecha Emision";
    $Movimiento[0][10] = "Fecha Vencimiento";
    $Movimiento[0][11] = "Nombre Archivo";





    $a = 1;
    while ($line = fgets($txt_file)) {
        $Movimiento[$a][1] = $a;
        $Movimiento[$a][2] = substr($line, 0, 15);
        $Movimiento[$a][3] = substr($line, 16, 16);
        $Movimiento[$a][4] = substr($line, 32, 25);
        $Movimiento[$a][5] = substr($line, 57, 2);
        $Movimiento[$a][6] = substr($line, 59, 11);
        $Movimiento[$a][7] = DevuelveCausa(substr($line, 70, 2));
        $Movimiento[$a][8] = convertirFechaMaster(substr($line, 72, 6));
        $Movimiento[$a][9] = convertirFechaMaster(substr($line, 78, 6));
        $Movimiento[$a][10] = convertirFechaMaster(substr($line, 84, 6));
        $Movimiento[$a][11] = $nombreArchivo;
        // echo $Movimiento[$a][17]."<br>";
        $a++;
    }
    fclose($txt_file);

    //var_dump($Movimiento);die();
    echo "<table  style='border: 1pt solid #fbb; text-align: center;'>";
    foreach ($Movimiento as $fila) {
        echo "<tr>";
        foreach ($fila as $celda) {
            echo "<td>$celda</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    if (!EstaArchivoMasterTarjeta($nombreArchivo)) {
        GrabarMasterTarjetasBaseDatos($Movimiento);
        echo "SE GRABARON DATOS EN LA BASE DE DATOS";
    }
    else {
        echo "<h1> NO SE PUDO GRABAR DATOS, YA QUE EL ARCHIVO $nombreArchivo YA ESTA IMPORTADO"; }



    ?>




</body>