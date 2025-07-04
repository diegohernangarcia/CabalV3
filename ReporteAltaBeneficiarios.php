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
<?php
require_once("BD/Funciones.php");
// Conectar a la base de datos
$conn = new mysqli('localhost', 'pucho', '78900', 'cabal');

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el orden actual (por defecto 'Apellido')
$order = isset($_GET['order']) ? $_GET['order'] : 'Apellido';

// Consultar datos ordenados por el campo especificado
$sql = "SELECT Apellido, Nombre, Localidad, FechaProceso, NumeroCuenta, NumeroTarjeta, NumeroDocumento, Cuit FROM beneficiario ORDER BY $order";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lista de Beneficiarios</title>
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
            cursor: pointer;
        }
    </style>
    <script>
        function changeOrder(newOrder) {
            window.location.href = "?order=" + newOrder;
        }
    </script>
</head>
<body>

<h2>Lista de Beneficiarios</h2>
<!-- 
<button onclick="changeOrder('Apellido')">Ordenar por Apellido</button>
<button onclick="changeOrder('Nombre')">Ordenar por Nombre</button>
<button onclick="changeOrder('Localidad')">Ordenar por Localidad</button>
<button onclick="changeOrder('FechaProceso')">Ordenar por Fecha Proceso</button> -->

<table>
    <tr>
        <th onclick="changeOrder('Apellido')">Apellido</th>
        <th onclick="changeOrder('Nombre')">Nombre</th>
        <th onclick="changeOrder('Localidad')">Localidad</th>
        <th onclick="changeOrder('FechaProceso')">Fecha Proceso</th>
        <th onclick="changeOrder('NumeroCuenta')">Número de Cuenta</th>
        <th onclick="changeOrder('NumeroTarjeta')">Número de Tarjeta</th>
        <th onclick="changeOrder('NumeroDocumento')">Número de Documento</th>
        <th onclick="changeOrder('Cuit')">CUIT</th>
        <th>Acción</th>
    </tr>
    <?php
    //$NombreApellido=  $row['Nombre']." ". $row['Apellido'];
    if ($result->num_rows > 0) {
        // Mostrar datos de cada fila
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Apellido'] . "</td>";
            echo "<td>" . $row['Nombre'] . "</td>";
            echo "<td>" . $row['Localidad'] . "</td>";
            echo "<td>" . convertirFecha($row['FechaProceso']) . "</td>";
            echo "<td>" . $row['NumeroCuenta'] . "</td>";
            echo "<td>" . formatearTarjetaCredito($row['NumeroTarjeta']) . "</td>";
            echo "<td>" . $row['NumeroDocumento'] . "</td>";
            echo "<td>" . $row['Cuit'] . "</td>";
            // echo "<td><button onclick=\"consultar('" . $row['Cuit'] . "')\">Consultar</button></td>";
            //echo "<td><button onclick=\"consultar('" . $row['Cuit'] . "')\">Consultar</button></td>";
            echo "<td><a href='consultaBeneficiario.php?Cuit=" . $row["Cuit"]  . "'><button>Consultar</button></a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='9'>No se encontraron registros</td></tr>";
    }
    $conn->close();
    ?>
    <button onclick="location.href='index.php'">Volver al Inicio</button> <br>
</table>

<script>
    function consultar(cuit) {
        alert('Consultar beneficiario con CUIT: ' + cuit);
        // Aquí puedes agregar la lógica para la acción de consultar
    }
</script>

</body>
</html>





</body>

</html>