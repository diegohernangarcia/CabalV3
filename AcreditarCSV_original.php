<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/estilos.css" />
    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
    <title>SUBIR ARCHIVO CSS</title>
</head>
<body>
<form action="uploadcsv.php" method="post" enctype="multipart/form-data">
  <label for="archivo">Selecciona un archivo:</label>
  <input type="file" name="archivo" id="archivo">
  <button type="submit">Subir archivo</button>
</form>

<style>
  form {
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 5px;
    background-color: #f5f5f5;
    text-align: center;
  }

  label {
    display: block;
    margin-bottom: 10px;
    font-weight: bold;
  }

  input[type="file"] {
    display: block;
    margin: 0 auto 10px;
  }

  button[type="submit"] {
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
  }

  button[type="submit"]:hover {
    background-color: #45a049;
  }
</style>
<a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>
</body>
</html>