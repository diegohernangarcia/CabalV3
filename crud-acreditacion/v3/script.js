document
  .getElementById("cuitForm")
  .addEventListener("submit", function (event) {
    event.preventDefault();

    const cuit = document.getElementById("cuit").value;
    const monto = document.getElementById("monto").value;

    fetch("obtener_beneficiario.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "cuit=" + encodeURIComponent(cuit),
    })
      .then((response) => response.json())
      .then((data) => {
        let beneficiarioInfo = document.getElementById("beneficiarioInfo");
        let beneficiarioTable = document
          .getElementById("beneficiarioTable")
          .getElementsByTagName("tbody")[0];

        if (data.success) {
          let row = beneficiarioTable.insertRow();
          row.insertCell(0).innerText = data.id;
          row.insertCell(1).innerText = data.id_beneficiario;
          row.insertCell(2).innerText = data.nombre;
          row.insertCell(3).innerText = data.apellido;
          row.insertCell(4).innerText = data.numeroCuenta;
          row.insertCell(5).innerText = monto;
          row.insertCell(6).innerText = data.numeroDocumento;

          document.getElementById("beneficiarioTable").style.display = "table";
          document.getElementById("terminar").style.display = "inline-block";
          // Limpiar campos y establecer el foco en el campo "cuit"
          document.getElementById("cuit").value = "";
          document.getElementById("monto").value = "";
          document.getElementById("cuit").focus();

          let deleteCell = row.insertCell(7);
          let deleteButton = document.createElement("button");
          deleteButton.textContent = "Borrar";
          deleteButton.classList.add("btn-delete");
          deleteButton.addEventListener("click", function () {
            beneficiarioTable.deleteRow(row.rowIndex - 1);
            updateTotal();
                if (beneficiarioTable.rows.length === 0) {
                    document.getElementById('beneficiarioTable').style.display = 'none';
                }
          });

          deleteCell.appendChild(deleteButton);

          document.getElementById("beneficiarioTable").style.display = "table";
          document.getElementById("terminar").style.display = "inline-block";

          // Limpiar campos y establecer el foco en el campo "cuit"
          document.getElementById("cuit").value = "";
          document.getElementById("monto").value = "";
          document.getElementById("cuit").focus();
        } else {
          beneficiarioInfo.innerHTML = `<p>No se encontró un beneficiario con el CUIT proporcionado.</p>`;
        }
      })
      .catch((error) => {
        console.error("Error:", error);
      });
  });
  function updateTotal() {
    let beneficiarioTable = document.getElementById('beneficiarioTable').getElementsByTagName('tbody')[0];
    //let totalImporte = 0;

    for (let i = 0; i < beneficiarioTable.rows.length; i++) {
        totalImporte += parseFloat(beneficiarioTable.rows[i].cells[2].innerText) || 0;
    }

    document.getElementById('totalImporte').innerText = totalImporte.toFixed(2);
}

// document.getElementById('terminar').addEventListener('click', function() {
//     grabarDatos();

    document.getElementById('terminar').addEventListener('click', function() {
        truncateAndSaveData();
});

function truncateAndSaveData() {
    fetch('truncate_acreditarmanual.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            saveData();
        } else {
            alert('Error al truncar la tabla.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function saveData() {
    const tableRows = document.getElementById('beneficiarioTable').getElementsByTagName('tbody')[0].rows;
    let totalImporte = 0;

    let promises = [];
    
    for (let i = 0; i < tableRows.length; i++) {
        const row = tableRows[i];
        const id_beneficiario = row.cells[0].innerText;
        const numeroCuenta = row.cells[4].innerText;
        const importeCompra = row.cells[5].innerText;
        const importeCajero = 0; // Siempre cero
        const numeroDocumento = row.cells[6].innerText;
        const fechaActual = new Date().toISOString().split('T')[0];
        const horaActual = new Date().toLocaleTimeString('es-ES');

        totalImporte += parseFloat(importeCompra) || 0;

        let promise = fetch('grabar_beneficiario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id_beneficiario=' + encodeURIComponent(id_beneficiario) +
                  '&Apellido=' + encodeURIComponent(row.cells[3].innerText) +
                  '&Nombre=' + encodeURIComponent(row.cells[2].innerText) +
                  '&NumeroCuenta=' + encodeURIComponent(numeroCuenta) +
                  '&ImporteCompra=' + encodeURIComponent(importeCompra) +
                  '&ImporteCajero=' + encodeURIComponent(importeCajero) +
                  '&DNI=' + encodeURIComponent(numeroDocumento) +
                  '&Fecha=' + encodeURIComponent(fechaActual) +
                  '&Hora=' + encodeURIComponent(horaActual)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error('Error al grabar los datos.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });

        promises.push(promise);
    }

    Promise.all(promises)
    .then(() => {
        alert('Total Importe: ' + totalImporte.toFixed(2));
        window.location.href = '../index.php';
    })
    .catch(error => {
        console.error('Error:', error);
    });
}




  function grabarDatos() {
    const tableRows = document.getElementById('beneficiarioTable').getElementsByTagName('tbody')[0].rows;
    let totalImporte = 0;

    for (let i = 0; i < tableRows.length; i++) {
        const row = tableRows[i];
        const id = row.cells[0].innerText;
        const numeroCuenta = row.cells[4].innerText;
        const importeCompra = row.cells[2].innerText;
        const importeCajero = 0; // Siempre cero
        const numeroDocumento = row.cells[6].innerText;
        const fechaActual = new Date().toISOString().split('T')[0];
        const horaActual = new Date().toLocaleTimeString('es-ES');
        const apellido = row.cells[3].innerText;
        const nombre = row.cells[2].innerText;

        totalImporte += parseFloat(importeCompra) || 0;

        fetch('grabar_beneficiario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id_beneficiario=' + encodeURIComponent(id) +
                  '&Apellido=' + encodeURIComponent(apellido) +
                  '&Nombre=' + encodeURIComponent(nombre) +
                  '&NumeroCuenta=' + encodeURIComponent(numeroCuenta) +
                  '&ImporteCompra=' + encodeURIComponent(importeCompra) +
                  '&ImporteCajero=' + encodeURIComponent(importeCajero) +
                  '&DNI=' + encodeURIComponent(numeroDocumento) +
                  '&Fecha=' + encodeURIComponent(fechaActual) +
                  '&Hora=' + encodeURIComponent(horaActual)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error al grabar los datos.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    alert('Total Importe: ' + totalImporte.toFixed(2));
    window.location.href = '../index.php';
}