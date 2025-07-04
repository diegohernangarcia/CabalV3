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
                <p>Localidad: ${data.Localidad}</p>
                <p>Numero Cuenta: ${data.NumeroCuenta}</p>
                
                <p>DNI: ${data.DNI}</p>
                


            `;
        } else {
            beneficiarioInfo.innerHTML = `<p>No se encontró un beneficiario con el CUIT proporcionado.</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
