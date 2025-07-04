const formulario = document.getElementById("formulario");
const cuitInput = document.getElementById("cuit");
const montoInput = document.getElementById("monto");
const informacionBeneficiario = document.getElementById("informacion-beneficiario");

formulario.addEventListener("submit", (event) => {
    event.preventDefault();

    const cuit = cuitInput.value;
    const monto = montoInput.value;
    
    // Consultar información del beneficiario mediante AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "buscarBeneficiario.php?cuit=" + cuit);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
           console.log("campeon");
            if (data.beneficiario) {
                informacionBeneficiario.innerHTML = `
                    <p>Nombre y Apellido: ${data.beneficiario.nombre}</p>
                `;
            } else {
                informacionBeneficiario.innerHTML = `
                    <p>Beneficiario no encontrado con CUIT: ${cuit}</p>
                    

                `;
            }
        } else {
            console.error("Error al buscar beneficiario:", xhr.statusText);
        }
    };
    xhr.send();
});
