<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <link rel="icon" href="img/favicon.png">
    <title>Cabal</title>
</head>
<body>
    
<?php

require_once("BD/Funciones.php");
require_once("BD/BD.php");
$servername = "localhost";
$username = "pucho";
$password = "78900";
$dbname = "cabal";

// Obtener parámetros del formulario
$mes = $_GET['mes'];
$anio = $_GET['anio'];
$archivo = $_GET['archivo'];

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT * FROM movimientos WHERE Mes = ? AND Anio = ? AND Archivo = ?";
//echo $sql;
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $mes, $anio, $archivo);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Resultados de Movimientos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2>Resultados de Movimientos para Año: <?php echo htmlspecialchars($anio); ?>, Mes: <?php echo htmlspecialchars($mes); ?>, Archivo: <?php echo htmlspecialchars($archivo); ?></h2>
<a href="javascript:window.print()" >Imprimir (Seleccionar Escala 70%)</a>  <br>
<table>
    <tr>
        <th>ID</th>
        <th>Banco</th>
        <th>Sucursal</th>
        <th>Cuenta</th>
        <th>Tarjeta</th>
        <th>Tipo Documento</th>
        <th>Documento</th>
        <th>Nombre y Apellido</th>
        <th>Cod. Operacion</th>
        <th>Fecha</th>
        <th>Comercio</th>
        <th>Importe</th>
        <th>Signo</th>
        <th>Importe2</th>
        <th>Signo2</th>
        <th>Mes</th>
        <th>Año</th>
        <th>Archivo</th>
        <!-- Añade más encabezados de columna según los campos de tu tabla -->
    </tr>

    <?php
    if ($result->num_rows > 0) {
        // Salida de los datos de cada fila
        while($row = $result->fetch_assoc()) {
            switch ($row["CodigoOperacion"])
            {         
                case "SALDO INICIAL" : 
                    echo "<tr class='back-green'>";
                    
                    break;
                case "SALDO FINAL":
                    echo "<tr class='back-red   '>";
                    break;
                case "CARGA/DESCARGA":
                    echo "<tr class='back-yellow'>";
                    break;
                case "AUTORIZACIONES":
                    echo "<tr class='back-blue'>";
                    break;
                default :
                    echo "<tr>"; 

            }
            
            //echo "<tr>";
            echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Banco"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Sucursal"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Cuenta"]) . "</td>";
            echo "<td>" . htmlspecialchars(formatearTarjetaCredito($row["Tarjeta"])) . "</td>";
            echo "<td>" . htmlspecialchars(DevueltoTipoDoc($row["TipoDocumento"])) . "</td>";
            echo "<td>" . EliminarCerosIzquierda(htmlspecialchars($row["Documento"])) . "</td>";
            echo "<td>" . htmlspecialchars($row["NombreApellido"]) . "</td>";
           // echo "<td>" . htmlspecialchars($row["CodigoOperacion"]) . "</td>";
            // switch ($row["CodigoOperacion"])
            // {         
            //     case "SALDO INICIAL" : 
            //         echo "<td class='texto-verde'>" . htmlspecialchars($row["CodigoOperacion"]) . "</td>";
                    
            //         break;
            //     case "SALDO FINAL":
            //         echo "<td class='texto-rojo'>" . htmlspecialchars($row["CodigoOperacion"]) . "</td>";
            //         break;
            //     default :
            //         echo "<td>" . htmlspecialchars($row["CodigoOperacion"]) . "</td>";

            // }
            echo "<td>" . htmlspecialchars($row["CodigoOperacion"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Fecha"]) . "</td>";
     



            echo "<td>" . htmlspecialchars($row["Comercio"]) . "</td>";
            echo "<td>" . htmlspecialchars(formatCurrency2($row["Importe"])) . "</td>";
            echo "<td>" . htmlspecialchars($row["Signo"]) . "</td>";
            echo "<td>" . htmlspecialchars(formatCurrency2($row["Importe2"])) . "</td>";
            echo "<td>" . htmlspecialchars($row["Signo2"]) . "</td>";



            echo "<td>" . htmlspecialchars($row["Mes"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Anio"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Archivo"]) . "</td>";
            // Añade más celdas según los campos de tu tabla
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No se encontraron resultados</td></tr>";
    }
    // echo $sql."<br>";
    $consulta= "SELECT * FROM movimientos WHERE Mes = '$mes' AND Anio = $anio AND Archivo = $archivo";
    
    $conn->close();
    ExportaraExcelMovimientosPorMes($consulta);
    
    ?>
</table>
<a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  


</body>
</html>
