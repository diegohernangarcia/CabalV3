<?php
// Incluir archivo de conexión a la base de datos
include_once 'conexion.php';

// Consulta para obtener todos los registros de la tabla beneficiario
$resultado = $conexion->query("SELECT * FROM beneficiario");

// Comprobar si se ha pulsado el botón "Eliminar"
if (isset($_POST['eliminar'])) {
  // Obtener el id del registro a eliminar
  $id = $_POST['id'];

  // Consulta para eliminar el registro
  $conexion->query("DELETE FROM beneficiario WHERE id = $id");

  // Redirigir a la página principal
  header('Location: index.php');
}

// Cerrar la conexión a la base de datos
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>CRUD con PHP y MySQL</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
  <style>
    /* Estilos CSS personalizados */
    table.dataTable tbody tr {
      cursor: pointer;
    }
    .editar, .eliminar {
      color: blue;
      text-decoration: underline;
      cursor: pointer;
    }
  </style>
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
    .editar {
        background-color: #199319;
  color: white;
  padding: 5px 25px;
  text-decoration: none;
    }
    .eliminar {
        background-color: red;
        color: white;
        
        text-decoration: none;
    }
  </style>
</head>
<body>
  <h1>CRUD con PHP y MySQL</h1>
  <a href="create.php">Crear beneficiario</a>
  
  <table id="tabla">
    <thead>
      <tr>
        <th>ID</th>
        <th>Apellido</th>
        <th>Nombre</th>
        <th>Calle</th>
        <th>Numero</th>
        <th>Piso</th>
        <th>Departamento</th>
        <th>Localidad</th>
        <th>Provincia</th>
        <th>Sexo</th>
        <th>Entidad Emisora</th>
        <th>Fecha Proceso</th>
        <th>Numero de Cuenta</th>
        <th>Numero de Tarjeta</th>
        <th>Codigo Postal</th>
        <th>Telefono Caracteristica</th>
        <th>Telefono Numero</th>
        <th>Estado Civil</th>
        <th>Fecha Nacimiento</th>
        <th>Tipo Documento</th>
        <th>Numero Documento</th>
        <th>Provincia Documento</th>
        <th>Cuit</th>
        <th>Codigo Ingreso</th>
        <th>Codigo Rechazo</th>
              
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr>
           <td><?= $fila['id'] ?></td>
          <td><?= $fila['Apellido'] ?></td>
          <td><?= $fila['Nombre'] ?></td>
          <td><?= $fila['Calle'] ?></td>
          <td><?= $fila['Numero'] ?></td>
          <td><?= $fila['Piso'] ?></td>
          <td><?= $fila['Departamento'] ?></td>
          <td><?= $fila['Localidad'] ?></td>
          <td><?= $fila['Provincia'] ?></td>
          <td><?= $fila['Sexo'] ?></td>
          <td><?= $fila['EntidadEmisora'] ?></td>
          <td><?= $fila['FechaProceso'] ?></td>
          <td><?= $fila['NumeroCuenta'] ?></td>
          <td><?= $fila['NumeroTarjeta'] ?></td>
          <td><?= $fila['CodigoPostal'] ?></td>
          <td><?= $fila['TelefonoCaracteristica'] ?></td>
          <td><?= $fila['TelefonoNumero'] ?></td>
          <td><?= $fila['EstadoCivil'] ?></td>
          <td><?= $fila['FechaNacimiento'] ?></td>
          <td><?= $fila['TipoDocumento'] ?></td>
          <td><?= $fila['NumeroDocumento'] ?></td>
          <td><?= $fila['ProvinciaDocumento'] ?></td>
          <td><?= $fila['Cuit'] ?></td>
          <td><?= $fila['CodigoIngreso'] ?></td>
          <td><?= $fila['CodigoRechazo'] ?></td>
          

          <td>
            <span class="editar" data-id="<?= $fila['id'] ?>">Editar</span>
           
            <!--<span class="eliminar" data-id="
            <?//= $fila['id'] ?>
            ">Eliminar</span> -->
           
          </td>
        </tr>
      <?php endwhile ?>
    </tbody>
  </table>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
  <script>
    $(document).ready(function() {
      // Inicializar DataTables
      $('#tabla').DataTable();

      // Redirigir al formulario de edición al hacer clic en "Editar"
      $('.editar').click(function() {
        var id = $(this).data('id');
        window.location.href = 'edit.php?id=' + id;
      });

        // Mostrar confirmación antes de eliminar el registro
  $('.eliminar').click(function() {
    var id = $(this).data('id');
    if (confirm('¿Está seguro de que desea eliminar este registro?')) {
      // Enviar el formulario de eliminación
      $('<form method="post"><input type="hidden" name="id" value="' + id + '"><input type="hidden" name="eliminar" value="1"></form>').submit();
    }
  });
});
</script>
</body>
</html>
 