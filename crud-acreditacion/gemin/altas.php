<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Ingreso de Datos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Formulario de Ingreso de Datos</h1>
    <form id="formulario">
        <label for="cuit">CUIT:</label>
        <input type="text" id="cuit" name="cuit" required>

        <label for="monto">Monto:</label>
        <input type="number" id="monto" name="monto" min="0" step="1" required>
        <br>
        <button type="submit">Enviar</button>
        <div id="informacion-beneficiario"></div>
    </form>

    <script src="script.js"></script>
</body>
</html>
