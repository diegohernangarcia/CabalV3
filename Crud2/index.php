<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="estilos.css">
  <title>Document</title>
</head>
<body>
<?php
$usuario='pucho';
$pass='78900';
// Connect to the database
$db = new PDO('mysql:host=localhost;dbname=cabal', $usuario,$pass);

// Get the list of beneficiaries
$sql = "SELECT id, Apellido, Nombre FROM beneficiario";
$results = $db->query($sql);

// Create the HTML table
echo "<table>";

// Loop through the results
foreach ($results as $row) {
  echo "<tr>";
  echo "<td>$row[id]</td>";
  echo "<td>$row[Apellido]</td>";
  echo "<td>$row[Nombre]</td>";
  ?>
    <td>
  <a href="update.php?id=<?php echo $row['id']; ?>">Edit</a>
  <a href="delete.php?id=<?php echo $row['id']; ?>">Delete</a>
</td>
  <?php
  echo "</tr>";
}

// Close the table
echo "</table>";

?>
 
</body>
</html>
<?php

