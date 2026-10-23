<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Nombre de Archivo</title>
    <script type="text/javascript">
        function solicitarNombreArchivo() {
            // Solicita el nombre del archivo al usuario
            var nombreArchivo = prompt("Por favor, introduce el nombre del archivo:");

            // Si el usuario introduce un nombre, redirige a la página de procesamiento
            if (nombreArchivo != null && nombreArchivo.trim() !== "") {
                // Redirige a la página de procesamiento con el nombre del archivo como parámetro
                window.location.href = "ProcesarMaestroPlasticos.php?archivo=" + encodeURIComponent(nombreArchivo.trim());
            } else {
                // Si no se introduce un nombre, muestra un mensaje de error
                alert("Debe introducir un nombre de archivo válido.");
            }
        }
    </script>
</head>
<body onload="solicitarNombreArchivo()">
    <h1>Procesando solicitud...</h1>
    <p>Por favor, espera mientras procesamos tu solicitud.</p>
</body>
</html>
