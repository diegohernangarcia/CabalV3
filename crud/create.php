<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear beneficiario</title>
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
</head>
<body>
  <h1>Crear beneficiario</h1>
  <form method="post">
    <label>Apellido:</label>
    <input type="text" name="apellido" required>
    <label>Nombre:</label>
    <input type="text" name="nombre" required>
    <input type="submit" name="submit" value="Crear">
  </form>
</body>
</html>
