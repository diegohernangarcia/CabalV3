<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <title>Document</title>
</head>
<body>
    

<?php
$servername = "localhost";
$username = "pucho";
$password = "78900";
$dbname = "cabal";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

//$sql = "SELECT DISTINCT mes, anio, archivo FROM movimientos ORDER by anio DESC AND mes desc";
$sql = "SELECT DISTINCT mes, anio, archivo FROM movimientos ORDER BY anio, 
        CASE 
            WHEN mes = 'Enero' THEN 1
            WHEN mes = 'Febrero' THEN 2
            WHEN mes = 'Marzo' THEN 3
            WHEN mes = 'Abril' THEN 4
            WHEN mes = 'Mayo' THEN 5
            WHEN mes = 'Junio' THEN 6
            WHEN mes = 'Julio' THEN 7
            WHEN mes = 'Agosto' THEN 8
            WHEN mes = 'Septiembre' THEN 9
            WHEN mes = 'Octubre' THEN 10
            WHEN mes = 'Noviembre' THEN 11
            WHEN mes = 'Diciembre' THEN 12
        END";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Resultados de Movimientos</title>
    <style>
        table {
            width: 50%;
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

<h2>Resultados de Movimientos</h2>

<table>
    <tr>
        <th>Mes</th>
        <th>Año</th>
        <th>Archivo</th>
        <th>Acción</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {
        // Salida de los datos de cada fila
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["mes"] . "</td>";
            echo "<td>" . $row["anio"] . "</td>";
            echo "<td>" . $row["archivo"] . "</td>";
            // Crear el enlace con los parámetros mes, anio y archivo
            echo "<td><a href='consultaMovimiento.php?mes=" . $row["mes"] . "&anio=" . $row["anio"] . "&archivo=" . $row["archivo"] . "'><button>Consultar</button></a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No se encontraron resultados</td></tr>";
    }
    $conn->close();
    ?>
</table>
</body>
<a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  
</html>

