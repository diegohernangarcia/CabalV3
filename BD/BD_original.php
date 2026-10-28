<?php
    $usuario='pucho';
    $pass='78900';
    function CantidadRegistrosBD($BD){
        //require_once('UsarGestion.php');
        $usuario='pucho';
    $pass='78900';
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql = 'SELECT * FROM '.$BD;
        $cantidad=0;        
        foreach ($conn->query($sql) as $row) {
            $cantidad++;
        }
        return $cantidad;
    }
    function TruncateTable ($BD){
       // require_once('UsarGestion.php');
       $usuario='pucho';
    $pass='78900';
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="TRUNCATE TABLE ".$BD;
        $statement = $conn->prepare($sql);
        $statement->execute();
    }
    function EstaDNI ($DNI){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        CompletarNumeroDocumento($DNI);
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sentencia = $conn->prepare("SELECT id FROM beneficiario WHERE NumeroDocumento = ? LIMIT 1;");
        $sentencia->execute([$DNI]);
        # Ver cuántas filas devuelve
        $numeroDeFilas = $sentencia->rowCount();
        # Si son 0 o menos, significa que no existe
        if ($numeroDeFilas <= 0) {
        //echo "El usuario con DNI $DNI NO existe";
        return false;
          } else {
               //echo "El usuario con DNI $DNI SÍ existe";
               return true;
        }
    }
    function DevuelvoIdBeneficiario ($DNI){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        //CompletarNumeroDocumento($DNI);
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="SELECT id FROM beneficiario WHERE NumeroDocumento = $DNI LIMIT 1;";
       // echo $sql."<br>";
        foreach ($conn->query($sql) as $row) {
            return $row['id'];
        }
    }
   
    function EstaFecha ($Mes,$Anio){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        CompletarNumeroDocumento($DNI);
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sentencia = $conn->prepare("SELECT id FROM movimientos WHERE Mes='".$Mes. "' AND Anio='".$Anio."';");
        //echo "SELECT id FROM Movimientos WHERE Mes='".$Mes. "' AND Anio='".$Anio."';";die();
        $sentencia->execute([$DNI]);
        # Ver cuántas filas devuelve
        $numeroDeFilas = $sentencia->rowCount();
        # Si son 0 o menos, significa que no existe
        if ($numeroDeFilas <= 0) {
        //echo "El usuario con DNI $DNI NO existe";
        return false;
          } else {
               //echo "El usuario con DNI $DNI SÍ existe";
               return true;
        }
    }   
    
    function EstaFechaMovimiento ($Mes,$Anio,$Archivo){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        CompletarNumeroDocumento($DNI);
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sentencia = $conn->prepare("SELECT id FROM movimientos WHERE Mes='".$Mes. "' AND Anio='".$Anio."' AND Archivo='".$Archivo."';");
       // var_dump($sentencia) ;
        //echo "SELECT id FROM Movimientos WHERE Mes='".$Mes. "' AND Anio='".$Anio."';";die();
        $sentencia->execute([$DNI]);
        # Ver cuántas filas devuelve
        $numeroDeFilas = $sentencia->rowCount();
        # Si son 0 o menos, significa que no existe
        if ($numeroDeFilas <= 0) {
        //echo "El usuario con DNI $DNI NO existe";
        return false;
          } else {
               //echo "El usuario con DNI $DNI SÍ existe";
               return true;
        }
    }
    function  DevuelvoNumeroCuentaX ($DNI,$CUIT){
        $usuario='pucho';
        $pass='78900';
            require_once("BD/Funciones.php");
            CompletarNumeroDocumento($DNI);
            $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
            $sentencia = $conn->prepare("SELECT id FROM movimientos WHERE Mes='".$Mes. "' AND Anio='".$Anio."';");
            //echo "SELECT id FROM Movimientos WHERE Mes='".$Mes. "' AND Anio='".$Anio."';";die();
            $sentencia->execute([$DNI]);
            # Ver cuántas filas devuelve
            $numeroDeFilas = $sentencia->rowCount();
            # Si son 0 o menos, significa que no existe
            if ($numeroDeFilas <= 0) {
            //echo "El usuario con DNI $DNI NO existe";
            return false;
              } else {
                   //echo "El usuario con DNI $DNI SÍ existe";
                   return true;
            }
    }  
    function  DevuelvoNumeroCuenta  ($DNI,$CUIT){
        $usuario='pucho';
        $pass='78900';
        require_once("BD/Funciones.php");
        //CompletarNumeroDocumento($DNI);
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="SELECT NumeroCuenta FROM beneficiario WHERE NumeroDocumento =$DNI and Cuit=$CUIT;";
        //echo "DNI $DNI CUIT $CUIT <br>";

        foreach ($conn->query($sql) as $row) {
                return $row['NumeroCuenta'];
        }
    }  
    
    function DevuelvoNumeroCuentaX2 ($NumeroDocumento, $Cuit) {
        // Datos de conexión a la base de datos
        $host = "localhost";      // Cambiar por el host de tu base de datos
        $usuario = "pucho";  // Cambiar por el usuario de tu base de datos
        $contrasena = "78900"; // Cambiar por la contraseña de tu base de datos
        $base_de_datos = "cabal";  // Cambiar por el nombre de tu base de datos
        echo "DNI $NumeroDocumento CUIT $Cuit <br>";
        // Conexión a la base de datos
        $conexion = new mysqli($host, $usuario, $contrasena, $base_de_datos);
    
        // Verificar la conexión
        if ($conexion->connect_error) {
            die("Conexión fallida: " . $conexion->connect_error);
        }
    
        // Consulta SQL para obtener el NumeroCuenta
        $sql = "SELECT NumeroCuenta FROM beneficiario WHERE NumeroDocumento = ? AND Cuit = ?";
        
        // Preparar la sentencia SQL
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            die("Error en la preparación de la consulta: " . $conexion->error);
        }
    
        // Enlazar parámetros
        $stmt->bind_param("ss", $NumeroDocumento, $Cuit);  // Asumiendo que ambos campos son de tipo string
    
        // Ejecutar la consulta
        $stmt->execute();
    
        // Obtener el resultado
        $resultado = $stmt->get_result();
    
        // Verificar si se encontró un registro
        if ($resultado->num_rows > 0) {
            // Obtener el NumeroCuenta
            $fila = $resultado->fetch_assoc();
            $NumeroCuenta = $fila['NumeroCuenta'];
        } else {
            $NumeroCuenta = null; // No se encontró ningún registro con los parámetros dados
            echo "NO SE ENCUENTRA EL NUMERO DE CUENTA";
        }
    
        // Cerrar la conexión
        // $stmt->close();
        // $conexion->close();
    
        return $NumeroCuenta;
    }

    function EstaBienCuenta ($Nombre,$Apellido,$Cuenta){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        //CompletarNumeroDocumento($DNI);
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="SELECT id FROM beneficiario WHERE Apellido='$Apellido' AND Nombre='$Nombre' AND NumeroCuenta=$Cuenta;";
        
        //echo $sql;//
        foreach ($conn->query($sql) as $row) {
                return $row['id'];
            }
    }
    function DevuelvoDatosCuenta ($DNI){
        $usuario='pucho';
        $pass='78900';
        require_once("BD/Funciones.php");
        $datos=[];
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="SELECT * FROM beneficiario WHERE NumeroDocumento =$DNI;";
        //echo $sql;die();
        $datos[0]=0;

        foreach ($conn->query($sql) as $row) {
                $datos[0]=$row['id'];
                $datos[1]=$row['Apellido'];
                $datos[2]=$row['Nombre'];
                $datos[3]=$row['Calle'];
                $datos[4]=$row['Numero'];
                $datos[5]=$row['Piso'];
                $datos[6]=$row['Departamento'];
                $datos[7]=$row['Localidad'];
                $datos[8]=$row['Provincia'];
                $datos[9]=$row['Sexo'];
                $datos[10]=$row['NumeroCuenta'];
                $datos[11]=$row['NumeroTarjeta'];
                $datos[12]=$row['FechaNacimiento'];
                $datos[13]=$row['TipoDocumento'];
                $datos[14]=$row['NumeroDocumento'];
                $datos[15]=$row['Cuit'];
            }
        return $datos;
        
    }    
    function DevuelvoApellidoNombrePorDNI ($DNI){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        $datos=[];
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="SELECT * FROM beneficiario WHERE NumeroDocumento =$DNI;";
        //echo $sql;die();
        $datos[0]=0;

        foreach ($conn->query($sql) as $row) {
                $datos[1]=trim($row['Apellido']);
                $datos[2]=trim($row['Nombre'])  ;
                
            }
        return $datos;
        
    }   
    function DevuelvoApellidoyNombrePorNumeroCuenta ($NroCuenta){
        $usuario='pucho';
    $pass='78900';
        require_once("BD/Funciones.php");
        $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
        $sql="SELECT * FROM beneficiario WHERE NumeroCuenta =$NroCuenta;";
  //      echo $sql."<br>";
        
        $Quien="SIN DATOS";
        foreach ($conn->query($sql) as $row) {
                $Quien= $row['Apellido']." ".$row['Nombre'];
                //echo $Quien;
            }
        return $Quien;
        
    }     
    
function generateTable() {
    ?>
        <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
    <?php
    // Consulta SQL para seleccionar valores distintos y ordenarlos
    $servername="localhost";
    $username="pucho";
    $password="78900";
    $dbname="cabal";
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    $sql = "SELECT DISTINCT mes, anio FROM movimientos ORDER BY anio, mes";
    $result = $conn->query($sql);

    // Comprobar si se obtuvieron resultados
    if ($result->num_rows > 0) {
        // Crear la tabla HTML
        $html = '<table width="50%" border="1" center>';
        $html .= '<tr><th width="30px">Mes y Año</th></tr>';

        // Recorrer los resultados y agregarlos a la tabla
        while ($row = $result->fetch_assoc()) {
            $mes = htmlspecialchars($row['mes']);
            $anio = htmlspecialchars($row['anio']);
           // $archivo = htmlspecialchars($row['archivo']);
            //$link = "consultar.php?mes=$mes&anio=$anio&archivo=$archivo";

            $html .= "<tr>";
            $html .= "<td>$mes - $anio</td>";
            // $html .= "<td>$anio</td>";
           // $html .= "<td>$archivo</td>";
            //$html .= "<td><a href='$link'>Consultar</a></td>";
            $html .= "</tr>";
        }

        $html .= '</table>';
    }  
    else {
        $html = "No se encontraron resultados.";
    }
    $conn->close();
    return $html;
}
function GrabarMasterTarjetasBaseDatos($datos) 
{
    
    
    // Conexión a la base de datos (modifica los parámetros según tu configuración)
    $servername = "localhost";
    $username = "pucho";
    $password = "78900";
    $dbname = "cabal";
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verifica la conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    // Recorre el array bidimensional e inserta cada fila en la base de datos
    $i = 1;
    require_once("BD/UsarGestion.php");
    $my_Insert_Statement = $conn->prepare("INSERT INTO MasterTarjetas(NumeroCuenta,NumeroTarjeta,NombreApellido,TipoDocumento,NroDocumento,Causa,FechaAlta,FechaEmision,FechaVencimiento,Archivo) VALUES (:NumeroCuenta,:NumeroTarjeta,:NombreApellido,:TipoDocumento,:NroDocumento,:Causa,:FechaAlta,:FechaEmision,:FechaVencimiento,:Archivo)");
    for ($reg=1;$reg<=sizeof($datos)-1;$reg++) {
        $my_Insert_Statement->bindParam(':NumeroCuenta', $datos[$reg][2]);
        $my_Insert_Statement->bindParam(':NumeroTarjeta', $datos[$reg][3]);
        $my_Insert_Statement->bindParam(':NombreApellido', $datos[$reg][4]);
        $my_Insert_Statement->bindParam(':TipoDocumento', $datos[$reg][5]);
        $my_Insert_Statement->bindParam(':NroDocumento', $datos[$reg][6]);
        $my_Insert_Statement->bindParam(':Causa', $datos[$reg][7]);
        $my_Insert_Statement->bindParam(':FechaAlta', $datos[$reg][8]);
        $my_Insert_Statement->bindParam(':FechaEmision', $datos[$reg][9]);
        $my_Insert_Statement->bindParam(':FechaVencimiento', $datos[$reg][10]);
        $my_Insert_Statement->bindParam(':Archivo', $datos[$reg][11]);
        if ($my_Insert_Statement->execute()) {
        } else {
          echo '<h5 class="display-5">No Se Pudieron Guardar Los Registros en la Base de Datos</h5>';  
        }
        

        
              
    }
         echo '<h5 class="display-5">Registros Importado en la Base de Datos</h5>';
         echo ' <a href="javascript:history.back()" class="btn-flotante">Volver Atras</a>  ';
}
function EstaArchivoMasterTarjeta ($Archivo){
    $usuario='pucho';
$pass='78900';
    require_once("BD/Funciones.php");
    //CompletarNumeroDocumento($DNI);
    $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
    $sentencia = $conn->prepare("SELECT id FROM MasterTarjetas WHERE Archivo = ? LIMIT 1;");
    $sentencia->execute([$Archivo]);
    # Ver cuántas filas devuelve
    $numeroDeFilas = $sentencia->rowCount();
    # Si son 0 o menos, significa que no existe
    if ($numeroDeFilas <= 0) {
    //echo "El usuario con DNI $DNI NO existe";
    return false;
      } else {
           //echo "El usuario con DNI $DNI SÍ existe";
           return true;
    }
}
function DevuelvoApellidoNombrePorDNICUIT ($DNI,$CUIT){
    $usuario='pucho';
$pass='78900';
    require_once("BD/Funciones.php");
    $datos=[];
    $conn = new PDO('mysql:host=localhost;dbname=cabal',$usuario,$pass);
    $sql="SELECT * FROM beneficiario WHERE NumeroDocumento =$DNI and Cuit=$CUIT";
    //echo $sql;die();
    //$datos[0]=0;
    
    foreach ($conn->query($sql) as $row) {
            // $datos[1]=trim($row['Apellido']);
            $datos[1]=$row['Apellido'];
            $datos[2]=$row['Nombre'];
            // $datos[2]=trim($row['Nombre'])  ;
            
        }
    //var_dump($datos);
        return $datos;
    
}  


?>