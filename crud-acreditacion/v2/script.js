document.getElementById('cuitForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const cuit = document.getElementById('cuit').value;

    fetch('obtener_beneficiario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cuit=' + encodeURIComponent(cuit)
    })
    .then(response => response.json())
    .then(data => {
        let beneficiarioInfo = document.getElementById('beneficiarioInfo');
        if (data.success) {
            beneficiarioInfo.innerHTML = `
                <h2>Datos del Beneficiario</h2>
                <p>Nombre: ${data.nombre}</p>
                <p>Apellido: ${data.apellido}</p>
                <input type="hidden" id="beneficiarioId" value="${data.id}">
                <input type="hidden" id="beneficiarioIdBeneficiario" value="${data.id_beneficiario}">
                <input type="hidden" id="beneficiarioNombre" value="${data.nombre}">
                <input type="hidden" id="beneficiarioApellido" value="${data.apellido}">
                <input type="hidden" id="beneficiarioNumeroCuenta" value="${data.numeroCuenta}">
                <input type="hidden" id="beneficiarioImporteCompra" value="${document.getElementById('monto').value}">
                <input type="hidden" id="beneficiarioImporteCajero" value="${data.importeCajero}">
                <input type="hidden" id="beneficiarioNumeroDocumento" value="${data.numeroDocumento}">
            `;
            document.getElementById('grabarAgregarOtro').style.display = 'inline-block';
            document.getElementById('terminar').style.display = 'inline-block';
        } else {
            beneficiarioInfo.innerHTML = `<p>No se encontró un beneficiario con el CUIT proporcionado.</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

document.getElementById('grabarAgregarOtro').addEventListener('click', function() {
    grabarDatos(false);
});

document.getElementById('terminar').addEventListener('click', function() {
    grabarDatos(true);
});

function grabarDatos(terminar) {
    const id = document.getElementById('beneficiarioId').value;
    const idBeneficiario = document.getElementById('beneficiarioIdBeneficiario').value;
    const nombre = document.getElementById('beneficiarioNombre').value;
    const apellido = document.getElementById('beneficiarioApellido').value;
    const numeroCuenta = document.getElementById('beneficiarioNumeroCuenta').value;
    const importeCompra = document.getElementById('beneficiarioImporteCompra').value;
    const importeCajero = document.getElementById('beneficiarioImporteCajero').value;
    const numeroDocumento = document.getElementById('beneficiarioNumeroDocumento').value;
    const fechaActual = new Date().toISOString().split('T')[0];
    const horaActual = new Date().toLocaleTimeString('es-ES');

    fetch('grabar_beneficiario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + encodeURIComponent(id) +
              '&idBeneficiario=' + encodeURIComponent(idBeneficiario) +
              '&nombre=' + encodeURIComponent(nombre) +
              '&apellido=' + encodeURIComponent(apellido) +
              '&numeroCuenta=' + encodeURIComponent(numeroCuenta) +
              '&importeCompra=' + encodeURIComponent(importeCompra) +
              '&importeCajero=' + encodeURIComponent(importeCajero) +
              '&numeroDocumento=' + encodeURIComponent(numeroDocumento) +
              '&fechaActual=' + encodeURIComponent(fechaActual) +
              '&horaActual=' + encodeURIComponent(horaActual)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (terminar) {
                window.location.href = 'index.php';
            } else {
                document.getElementById('cuitForm').reset();
                document.getElementById('beneficiarioInfo').innerHTML = '';
                document.getElementById('grabarAgregarOtro').style.display = 'none';
                document.getElementById('terminar').style.display = 'none';
            }
        } else {
            alert('Error al grabar los datos.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
