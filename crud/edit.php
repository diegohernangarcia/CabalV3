<style>
    body {
      font-family: Arial, sans-serif;
    }
    h1 {
      text-align: center;
      color: #333;
    }
    form {
      max-width: 400px;
      margin: 0 auto;
      padding: 20px;
      background-color: #eee;
      border-radius: 5px;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
    }
    label {
      display: block;
      margin-bottom: 5px;
      color: #333;
    }
    input[type="text"], input[type="submit"] {
      display: block;
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 5px;
      border: none;
      box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.3);
    }
    input[type="submit"] {
      background-color: #333;
      color: #fff;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background-color: #555;
    }
  </style>
<?php
// Comprobar si se ha enviado el formulario
if (isset($_POST['submit'])) {
  // Incluir archivo de conexión a la base de datos
  include_once 'conexion.php';

  // Obtener los datos del formulario
  $id = $_POST['id'];
  $apellido = $_POST['apellido'];
  $nombre = $_POST['nombre'];

  // Consulta para actualizar el registro
  $conexion->query("UPDATE beneficiario SET Apellido = '$apellido', Nombre = '$nombre' WHERE id = $id");

  // Cerrar la conexión a la base de datos
  $conexion->close();

  // Redirigir a la página principal
  header('Location: index.php');
}

// Obtener el id del registro a editar
$id = $_GET['id'];

// Incluir archivo de conexión a la base de datos
include_once 'conexion.php';

// Consulta para obtener los datos del registro a editar
$resultado = $conexion->query("SELECT * FROM beneficiario WHERE id = $id");
$fila = $resultado->fetch_assoc();

// Cerrar la conexión a la base de datos
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar beneficiario</title>
</head>
<body>
  <h1>Editar beneficiario</h1>
  <form method="post">
    <input type="hidden" name="id" value="<?= $fila['id'] ?>">
    <label>Apellido:</label>
    <input type="text" name="apellido" value="<?= $fila['Apellido'] ?>" required>
    <br>
    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= $fila['Nombre'] ?>" required>
<br>
<input type="submit" name="submit" value="Guardar">

  </form>
</body>
</html>
