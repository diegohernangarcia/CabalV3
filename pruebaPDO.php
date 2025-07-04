<?php
    require("BD/UsarGestion.php");
    $Nombre="Facundo";
    $Apellido="Toledo";
    $my_Insert_Statement = $conn->prepare("INSERT INTO Prueba (Nombre, Apellido) VALUES (:Nombre, :Apellido)");
    $my_Insert_Statement->bindParam(':Nombre', $Nombre);
    $my_Insert_Statement->bindParam(':Apellido', $Apellido);
    if ($my_Insert_Statement->execute()) {
        echo "New record created successfully";
      } else {
        echo "Unable to create record";
      }
?>