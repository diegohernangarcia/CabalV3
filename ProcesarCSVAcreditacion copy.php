<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
    <title>Procesando Archivo CSV</title>
</head>
<body>
<?php
// Ruta del archivo CSV
$csvFile = 'archivos/'.$nombre;
echo "<BR>";



// Abrir el archivo CSV en modo lectura
$file = fopen($csvFile, 'r');

// Leer la primera línea (encabezados de columna) y almacenarlos en un arreglo
$headers = fgetcsv($file, 1000, ',');

// Crear un arreglo para almacenar los datos del CSV
$data = [];

// Leer el contenido del archivo línea por línea
while (($row = fgetcsv($file, 1000, ',')) !== false) {
    // Crear un arreglo asociativo para cada fila, utilizando los encabezados como claves
    $row = array_combine($headers, $row);
    
    // Agregar la fila al arreglo de datos
    $data[] = $row;
}
//var_dump($data);die();

// Cerrar el archivo CSV
fclose($file);
//var_dump($data[18]);die();
// Procesar los datos
require_once("BD/UsarGestion.php");
require_once("BD/BD.php");
require_once("BD/Funciones.php");
TruncateTable('cargatarjeta');
// GRABAR EN LA BASE DE DATOS
TruncateTable('acreditar');
$IdBenef=1;
$hora=$hoy = getdate();
$horaactual=$hora["hours"].$hora["minutes"].$hora["seconds"];
$dia=date("d");
$mes=date("m");
$anio=date("y");
$fecha=$dia.$mes.$anio;
//echo $fecha;die();
date_default_timezone_set("UTC");
date_default_timezone_set("America/Mendoza");
$contador=0;
foreach ($data as  $row) {
    $contador++;
    var_dump($row);die();
    $id = $row['Id'];
    $apellido = $row['Apellido'];
    $nombre = $row['Nombre'];
    $localidad = $row['Localidad'];
    $dni = $row['DNI'];
    $cuit = $row['Cuit'];
    
    //VALIDO EL CUIT
    if (!validar_cuit_cuil($cuit)) {
        //echo "<h2> 'El CUIT/CUIL de '.$apellido.' '.$nombre.' no es válido' </h2>";
        echo "<h2> El CUIT/CUIL de $apellido $nombre  no es válido </h2>";
        ?> <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a> <?php
        //die();
      }



    $monto = $row['Monto a Acreditar'];
    // Realizar las operaciones o manipulaciones necesarias con los datos
    // ...
    // Ejemplo: Imprimir los valores de cada fila
    //echo "ID: $id, Apellido: $apellido, Nombre: $nombre, Localidad: $localidad, DNI: $dni, CUIT: $cuit, Monto: $monto<br>";
    $my_Insert_Statement = $conn->prepare("INSERT INTO acreditar(id_beneficiario,Apellido,Nombre,NumeroCuenta,ImporteCompra,ImporteCajero,DNI,Fecha,Hora) VALUES (:id_beneficiario,:Apellido,:Nombre,:NumeroCuenta,:ImporteCompra,:ImporteCajero,:DNI,:Fecha,:Hora)");
    $Apellido=$apellido; 
    $Nombre=$nombre; 
    $NumeroCuenta=$cuenta=DevuelvoNumeroCuenta($dni,$cuit); 
    $ImporteCompra=$monto; 
    $ImporteCajero=0; 
    $DNI=$dni;
    $IdBenef=DevuelvoIdBeneficiario($dni);
    $Fecha=strval($fecha); 
    $Hora=strval($horaactual); 
    $my_Insert_Statement->bindParam(':id_beneficiario', $IdBenef);
    $my_Insert_Statement->bindParam(':Apellido',$Apellido);
    $my_Insert_Statement->bindParam(':Nombre',$Nombre);
    $my_Insert_Statement->bindParam(':NumeroCuenta',$NumeroCuenta);
    $my_Insert_Statement->bindParam(':ImporteCompra',$ImporteCompra);
    $my_Insert_Statement->bindParam(':ImporteCajero',$ImporteCajero);
    $my_Insert_Statement->bindParam(':DNI',$DNI);
    $my_Insert_Statement->bindParam(':Fecha',$Fecha);
    $my_Insert_Statement->bindParam(':Hora',$Hora);
    $my_Insert_Statement->execute();
}





?>
    <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  
    <center><a href="GenerarCargaCredito2.php">Continuar a Generar NOVCAR.782</a></center><br>
</body>

</html>