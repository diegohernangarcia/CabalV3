<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" type="text/css" href="css/menu.css" /> -->

    <link href="Bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/estilos2.css" />
    <link rel="icon" href="img/favicon.png">

    <title>CABAL</title>
</head>

<body>
    <!-- <div class="container-fluid px-0">
    <nav class="navbar navbar-expand-sm navbar-dark bg-black py-0 px-0"> <a class="navbar-brand" href="#"><img id="logo" src="img/cabal.png"> CABAL</a> <span class="v-line"></span> <button class="navbar-toggler mr-3" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span> </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active"> <a class="nav-link" href="CargaAltasDeExcelaBD.php" SPAN title="Carga las Altas a la Base de Datos">Cargar Altas Desde Excel</a>  </li>
                <li class="nav-item"> <a class="nav-link" href="GenerarNovedadAltas.php" SPAN title="Genera el archivo de Altas para subir a la plataforma">Generar ALTAUSUCBPRE</a> </li>
                <li class="nav-item"> <a class="nav-link" href="ProcesaNovedadesAltas.php" SPAN title="Procesa el Archivo 782_OUT Altas que es la devolucion con cuentas/tarjeta del ALTAUSUCBPRE">PROCESAR NOV. ALTAS</a> </li>
                <li class="nav-item"> <a class="nav-link" href="ExportarExcelBeneficiarios.php" SPAN title="Exporta los datos de los beneficiarios a un excel">Exportar Excel Beneficiarios</a> </li>
                <li class="nav-item"> <a class="nav-link" href="AcreditarSaldos-2023.php">Acreditar Saldo 3</a>  </li>
                <li class="nav-item"> <a class="nav-link" href="GenerarCargaCredito.php">Generar NOVCAR</a> </li>
                <li class="nav-item"> <a class="nav-link" href="ProcesarCarga.php" SPAN title="Procesar respuesta acreditaciones al archivo RCAR"</a> PROCESAR RESPUESTA CARGA CREDITO</li>
                <li class="nav-item"> <a class="nav-link" href="ProcesarMovimientos.html" SPAN title="Procesar 782_Nacion_20220731_1841_324794 Con Movimiento Cuentas Descargado Desde Plataforma">PROCESAR MOVIMIENTOS</a> </li>
                <li class="nav-item"> <a class="nav-link" href="SubirArchivo.html" SPAN title="Sube Los Archivos Para Importar en La Base de Datos" >Subir Archivos A Servidor</a> </li>
                <li class="nav-item"> <a class="nav-link" href="ayuda.html">Ayuda</a> </li>
                <li class="nav-item"> <a class="nav-link" href="ChequeoAcreditacion.php" SPAN title="Chequea que los DNI correspondan con el benefiario" >Chequeo Archivo Acreditacion</a> </li>
                <li class="nav-item"> <a class="nav-link" href="ConsultarBeneficiario.php">Consultar Beneficiario</a>  </li>
            </ul>
        </div>
    </nav>
</div> -->

    <nav class="navbar navbar-expand-lg bg-body-tertiary" style="background-color: #2C3E50;">
        <div class="container-fluid">
            <a class="navbar-brand" ><img id="logo" src="img/cabal.png"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active"> <a class="nav-link" href="CargaAltasDeExcelaBD.php" SPAN title="Carga las Altas a la Base de Datos">CARGAR ALTAS EXCEL</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="GenerarNovedadAltas.php" SPAN title="Genera el archivo de Altas para subir a la plataforma">GENERAR ALTAUSUCBPRE</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="ProcesaNovedadesAltas.php" SPAN title="Procesa el Archivo 782_OUT Altas que es la devolucion con cuentas/tarjeta del ALTAUSUCBPRE">PROCESAR NOV. ALTAS</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="VerificarNovcar.php" SPAN title="Verificar si el archivo generado respeta los espacios y saltos de linea correctamente">VERIFICAR NOVCAR</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="ExportarExcelBeneficiarios.php" SPAN title="Exporta los datos de los beneficiarios a un excel">EXPORTAR EXCEL BENEFICIARIOS</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="crud-acreditacion/crud.php">CRUD ACREDITACION</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="AcreditarSaldos-2023.php">ACREDITAR SALDO 3</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="GenerarCargaCredito2.php">GENERAR NOVCAR</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="ProcesarCarga.php" SPAN title="Procesar respuesta acreditaciones al archivo RCAR">PROCESAR RESPUESTA CARGA CREDITO</a></li>
                    <li class="nav-item"> <a class="nav-link" href="ProcesarMovimientos.html" SPAN title="Procesar 782_Nacion_20220731_1841_324794 Con Movimiento Cuentas Descargado Desde Plataforma">PROCESAR MOVIMIENTOS</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="ReporteMovimientos.php" SPAN title="Reporte Movimientos">REPORTE MOVIMIENTOS</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="ReporteMovimientosPorNombre.php" SPAN title="Reporte Movimientos">REPORTE MOVIMIENTOS POR NOMBRE</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="SubirArchivo.html" SPAN title="Sube Los Archivos Para Importar en La Base de Datos">SUBIR ARCHIVOS</a> </li>
                    <!-- <li class="nav-item"> <a class="nav-link" href="ayuda.html">Ayuda</a> </li> -->
                    <!-- <li class="nav-item"> <a class="nav-link" href="ChequeoAcreditacion.php" SPAN title="Chequea que los DNI correspondan con el benefiario">Chequeo Archivo Acreditacion</a> </li> -->
                    <li class="nav-item"> <a class="nav-link" href="ConsultarBeneficiario.php">CONSULTAR BENEFICIARIO</a> </li>
                </ul>
            </div>
        </div>
    </nav>
    <br>
    <div class="ayuda">
        <h1> AYUDA</h1>
        <a class="titular"> GENERAR ALTAS NUEVAS </a>
        <br>
        <ol>
            <li>Subir el archivo ALTAS.xlsx.</li>
            <li>Menu - Cargar Altas Excel.</li>
            <Li>El mismo posee el siguiente formato</Li>
            <li>Apellido-Nombre-Domicilio-Calle Domicilio-Numero Domicilio-Piso Domicilio-Departamento-Localidad-Provincia-Sexo-Codigo-Postal-Telefono Caracteristica-Telefono Numero-Estado Civil-Fecha Nacimiento-Tipo Documento-Numero Documento-Prov. Documento-CUIT/CUIL
            </li>
            <li> Respetando el Formato del <b>Anexo 1 - Novedades de Altas de Tarjetas Precargadas </b> Del Manual Cabal</li>
            <li>Menu - Generar ALTAUSUCBPRE.</li>
            <li>Copiar los datos generados en pantalla a un archivo de texto (notepad++) y agregar un salto de linea al final. Debe de hacerce en windows ya que son diferentes los archivos generados entre Linux/Windows.</li>
            <li>Subir ese archivo a la plataforma de cabal con el nombre ALTAUSUCBPRE.782</li>
            <li>La plataforma responde con un archivo del tipo 782_out_altas_tpc_202207XX_XXXX_XXXXXX</li>
        </ol>
        <br>
        <a class="titular">PROCESAR NOV. ALTAS </a>
        <ol>
            <li>En este archivo se encuentran los datos de la nueva tarjeta, incluyendo Numero Cuenta y Numero Tarjeta</li>
            <li>Descargar desde plataforma el archivo 782_out_altas_tpc_202207XX_XXXX_XXXXXX </li>
            <li>Renombrar el mismo a MovAltas.txt</li>
            <li>Subir el archivo MovAltas.txt</li>
            <li>Menu - PROCESAR NOV. ALTAS</li>
            <li>Se cargan los beneficiarios en la BD, se debe de exportar desde phpmyadmin a excel. </li>
        </ol>
        <ol>
        <a class="titular">GENERAR ACREDITACIONES DE SALDO (Este archivo con este formato lo envian desde Suministros con pequeños cambios) </a>
        <ol>
            <li>General el archivo Acreditaciones.xlsx</li>
            <li>El Mismo debe de Tener el siguiente formato</li>
            <li>Id-Apellido-Nombre-Localidad-DNI-CUIT-Monto a Acreditar</li>
            <li>Subir el archivo Acreditaciones.xlsx - En el formato de abajo, no lleva el total en la ultima fila</li>
            <!-- <img id="foto"src="./img/acreditacion.png" alt=""> -->
            <li>ASI ES EL ARCHIVO FINAL</li>
            <img id="foto"src="./img/acreditacion2.png" alt="">
            <li>Menu - Acreditar Saldo 3</li>
            <li>Menu - Generar NOVCAR</li>
            <li>Copiar los datos generados en pantalla a un archivo de texto (notepad++) y agregar un salto de linea al final. Debe de hacerce en windows ya que son diferentes los archivos generados entre Linux/Windows.</li>
            <li>Subir ese archivo a la plataforma de cabal con el nombre NOVCAR.782</li>
        </ol>
        <a  class="titular">PROCESAR MOVIMIENTO DE CUENTAS</a>
        <ol>
            <li>Descargar desde Plataforma Archivo del tipo 782_Nacion_20220731_1841_324794.txt</li>
            <li>Este archivo se sube a la misma El ultimo dia Habil de Cada Mes (o El 1er Dia Del Mes Siguiente</li>
            <li>Renombarar el archivo descargado a Movimientos.txt</li>
            <li>Subir el archivo Movimientos.txt</li>
            <li>Menu - Procesar Movimientos</li>
            <li>Seleccionar el Mes al que corresponden los movimientos</li>
            <li>Si el mes seleccionado ya esta cargado, genera un mensaje de error y no permite nuevamente la carga</li>
            <li>Se graban los datos a la tabla movimientos, exportar la misma a CSV For Ms excel</li>
        </ol>
        <a  class="titular">PROCESAR RESPUESTA DE ACREDITACIONES</a>
        <ol>
            <li>Descargar desde Plataforma Archivo del tipo 782_RCAR_20220731_1841_324794.txt</li>
            <li>Este archivo se el dia que se realiza la acreditacion en las cuentas de los beneficiarios</li>
            <li>Renombarar el archivo descargado a RespuestaCargas.txt</li>
            <li>Subir el archivo RespuestaCargas.txt</li>
            <li>Menu - PROCESAR CARGA CREDITO</li>
            <li>se Mostrara en Pantalla las Cargas de Credito Realizadas</li>
        </ol>
    </div>
</body>
</html>