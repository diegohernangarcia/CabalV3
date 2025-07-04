<?php
// Comprobar si se ha enviado el formulario
if (isset($_POST['submit'])) {
  // Incluir archivo de conexión a la base de datos
  include_once 'conexion.php';

  // Obtener los datos del formulario
  $apellido = $_POST['apellido'];
  $nombre = $_POST['nombre'];

  // Consulta para insertar el nuevo registro
  $conexion->query("INSERT INTO beneficiario (Apellido, Nombre) VALUES ('$apellido', '$nombre')");

  // Cerrar la conexión a la base de datos
  $conexion->close();

  // Redirigir a la página principal
  header('Location: index.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear beneficiario</title>
</head>
<body>
  <h1>Crear beneficiario</h1>
  <form method="post">
    <label>Apellido:</label>
    <input type="text" name="apellido" required>
    <br>
    <label>Nombre:</label>
    <input type="text" name="nombre" required>
    <br>
    <label>Nombre:</label>
    <input type="text" name="nombre" required>
    <br>
    <input type="submit" name="submit" value="Crear">
  </form>
</body>
</html>
