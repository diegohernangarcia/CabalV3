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
        <h1>Formulario de Ingreso Beneficiarios</h1>
        <form id="cuitForm">
            <label for="cuit">CUIT:</label>
            <input type="text" id="cuit" name="cuit" required>

            <label for="monto">Monto:</label>
            <input type="number" id="monto" name="monto" required step="0.01">

            <input type="submit" value="Buscar Beneficiario x2" class="btn">
            <button type="button" onclick="history.back();" class="btn">Volver</button>
        </form>
        <div id="beneficiarioInfo"></div>
        <div class="tabla-container">
            <table id="beneficiarioTable" style="display:none;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID Beneficiario</th>
                        <th>Nombre</th>
                        <th>Apellido</>
                        <th>Número de Cuenta</th>
                        <th>Monto</th>
                        <th>Número de Documento</th>
                        <th>Acciones</th> <!-- Nueva columna para las acciones -->
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td id="totalImporte">0</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            </table>
            <button id="terminar" style="display:none;" class="btn">Terminar</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>