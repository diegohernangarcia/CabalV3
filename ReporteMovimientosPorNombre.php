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
require_once("BD/BD.php");
// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

//$sql = "SELECT DISTINCT mes, anio, archivo FROM movimientos ORDER by anio DESC AND mes desc";
$sql = "SELECT DISTINCT NombreApellido FROM movimientos ORDER BY NombreApellido ASC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Resultados de Beneficiarios</title>
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
<h2> Meses con Movimientos Cargados </h2>
<?php echo (generateTable()); ?>
<h2>Resultados de Movimientos</h2>

<table>
    <tr>
        <th>Nombre y Apellido</th>
        <th>Acción</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {
        // Salida de los datos de cada fila
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["NombreApellido"] . "</td>";
            // Crear el enlace con los parámetros mes, anio y archivo
            echo "<td><a href='consultaMovimientoPorNombre.php?NombreApellido=" . $row["NombreApellido"]  . "'><button>Consultar</button></a></td>";
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

