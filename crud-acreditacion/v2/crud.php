<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Ingreso</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Formulario de Ingreso</h1>
        <form id="cuitForm">
            <label for="cuit">CUIT:</label>
            <input type="text" id="cuit" name="cuit" required>

            <label for="monto">Monto:</label>
            <input type="number" id="monto" name="monto" required step="0.01">

            <input type="submit" value="Buscar Beneficiario" class="btn">
            <button type="button" onclick="history.back();" class="btn">Volver</button>
        </form>
        <div id="beneficiarioInfo"></div>
        <button id="grabarAgregarOtro" style="display:none;" class="btn">Agregar otro</button>
        <button id="terminar" style="display:none;" class="btn">Grabar y Terminar</button>
    </div>

    <script src="script.js"></script>
</body>
</html>
