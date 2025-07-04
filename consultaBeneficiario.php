<?php
// Conectar a la base de datos
$conn = new mysqli('localhost', 'pucho', '78900', 'cabal');

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el Cuit del formulario GET
$cuit = isset($_GET['Cuit']) ? $_GET['Cuit'] : '';
require_once("BD/Funciones.php");
// Consultar datos del beneficiario
$sql = "SELECT Apellido, Nombre, Localidad, FechaProceso, NumeroCuenta, NumeroTarjeta, NumeroDocumento, Cuit, NumeroDocumento FROM beneficiario WHERE Cuit = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cuit);
$stmt->execute();
$result = $stmt->get_result();
$beneficiario = $result->fetch_assoc();

// Función para convertir la fecha
function convertirFecha2($fecha) {
    $año = substr($fecha, 0, 2);
    $mes = substr($fecha, 2, 2);
    $día = substr($fecha, 4, 2);
    return $día . '/' . $mes . '/' . $año;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Beneficiario</title>
    <style>
        .beneficiario-info {
            width: 50%;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .beneficiario-info h2 {
            text-align: center;
        }
        .beneficiario-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .beneficiario-info table, th, td {
            border: 1px solid black;
        }
        .beneficiario-info th, .beneficiario-info td {
            padding: 10px;
            text-align: left;
        }
        .beneficiario-info th {
            background-color: #f2f2f2;
        }
        .beneficiario-info button {
            margin: 10px 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
    <script>
        function imprimirPagina() {
            window.print();
        }
        function redirigirFormulario() {
            const cuit = "<?php echo $beneficiario['NumeroDocumento']; ?>";
            window.location.href = "consultaMovimientoPorDNI.php?DNI=" + cuit;
        }
    </script>
</head>
<body>

<div class="beneficiario-info">
    <h2>Detalle de Beneficiario</h2>
    <?php if ($beneficiario): ?>
        <table>
            <tr>
                <th>Apellido</th>
                <td><?php echo $beneficiario['Apellido']; ?></td>
            </tr>
            <tr>
                <th>Nombre</th>
                <td><?php echo $beneficiario['Nombre']; ?></td>
            </tr>
            <tr>
                <th>Localidad</th>
                <td><?php echo $beneficiario['Localidad']; ?></td>
            </tr>
            <tr>
                <th>Fecha de Proceso</th>
                <td><?php echo convertirFecha2($beneficiario['FechaProceso']); ?></td>
            </tr>
            <tr>
                <th>Número de Cuenta</th>
                <td><?php echo $beneficiario['NumeroCuenta']; ?></td>
            </tr>
            <tr>
                <th>Número de Tarjeta</th>
                <td><?php echo formatearTarjetaCredito($beneficiario['NumeroTarjeta']); ?></td>
            </tr>
            <tr>
                <th>Número de Documento</th>
                <td><?php echo $beneficiario['NumeroDocumento']; ?></td>
            </tr>
            <tr>
                <th>CUIT</th>
                <td><?php echo $beneficiario['Cuit']; ?></td>
            </tr>
        </table>
        <button onclick="imprimirPagina()">Imprimir</button>
        <button onclick="redirigirFormulario()">Ver Movimientos</button>
        <button onclick="location.href='ReporteAltaBeneficiarios.php'">Volver atras</button>
        <button onclick="location.href='index.php'">Volver al Inicio</button>
        <?php else: ?>
        <p>No se encontró ningún beneficiario con el CUIT proporcionado.</p>
    <?php endif; ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
