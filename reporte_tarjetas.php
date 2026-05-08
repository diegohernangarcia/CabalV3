<?php
// =============================================
//  CONFIGURACIÓN DE BASE DE DATOS
// =============================================
$db_host = 'localhost';
$db_name = 'cabal';
$db_user = 'pucho';       // <-- Cambiar por tu usuario
$db_pass = '78900';           // <-- Cambiar por tu contraseña
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8');

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// =============================================
//  OBTENER VALORES ÚNICOS PARA FILTROS
// =============================================
$archivos = array();
$res = $conn->query("SELECT DISTINCT Archivo FROM MasterTarjetas ORDER BY Archivo");
while ($row = $res->fetch_assoc()) {
    $archivos[] = $row['Archivo'];
}

// =============================================
//  FILTROS RECIBIDOS
// =============================================
$filtro_archivo = isset($_GET['archivo'])        ? trim($_GET['archivo'])        : '';
$filtro_nombre  = isset($_GET['nombre_apellido']) ? trim($_GET['nombre_apellido']) : '';
$exportar       = isset($_GET['exportar'])        ? true : false;

// =============================================
//  CONSTRUIR CONSULTA
// =============================================
$where  = array();
$params = array();
$types  = '';

if ($filtro_archivo !== '') {
    $where[]  = 'Archivo = ?';
    $params[] = $filtro_archivo;
    $types   .= 's';
}
if ($filtro_nombre !== '') {
    $where[]  = 'NombreApellido LIKE ?';
    $params[] = '%' . $filtro_nombre . '%';
    $types   .= 's';
}

$sql = "SELECT id, NumeroCuenta, NumeroTarjeta, NombreApellido, TipoDocumento,
               NroDocumento, Causa, FechaAlta, FechaEmision, FechaVencimiento, Archivo
        FROM MasterTarjetas";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY NombreApellido ASC';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// =============================================
//  EXPORTAR A EXCEL (TSV con BOM UTF-8)
// =============================================
if ($exportar) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_tarjetas_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF"; // BOM UTF-8 para que Excel lo abra bien

    $columnas = array('ID','Nro Cuenta','Nro Tarjeta','Nombre y Apellido',
                      'Tipo Doc','Nro Documento','Causa',
                      'Fecha Alta','Fecha Emision','Fecha Vencimiento','Archivo');
    echo implode("\t", $columnas) . "\n";

    while ($row = $result->fetch_assoc()) {
        $linea = array(
            $row['id'],
            $row['NumeroCuenta'],
            $row['NumeroTarjeta'],
            trim($row['NombreApellido']),
            $row['TipoDocumento'],
            $row['NroDocumento'],
            trim($row['Causa']),
            date('d/m/Y', strtotime($row['FechaAlta'])),
            date('d/m/Y', strtotime($row['FechaEmision'])),
            date('d/m/Y', strtotime($row['FechaVencimiento'])),
            $row['Archivo'],
        );
        echo implode("\t", $linea) . "\n";
    }
    $stmt->close();
    $conn->close();
    exit;
}

// =============================================
//  CONTAR RESULTADOS
// =============================================
$total = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reporte Master Tarjetas</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: Arial, sans-serif;
  background: #f0f2f5;
  color: #333;
  font-size: 13px;
}

/* HEADER */
.header {
  background: linear-gradient(135deg, #1a3a5c 0%, #2563a8 100%);
  color: #fff;
  padding: 18px 30px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.header .logo {
  font-size: 26px;
  font-weight: 900;
  background: rgba(255,255,255,.18);
  border-radius: 8px;
  padding: 4px 12px;
  letter-spacing: -1px;
}
.header h1 { font-size: 20px; font-weight: 600; }
.header p  { font-size: 11px; opacity: .75; margin-top: 2px; }

/* FILTROS */
.filtros-wrap {
  background: #fff;
  border-bottom: 1px solid #dde3eb;
  padding: 16px 30px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: flex-end;
}
.filtros-wrap label {
  display: flex;
  flex-direction: column;
  font-size: 11px;
  font-weight: 700;
  color: #555;
  text-transform: uppercase;
  letter-spacing: .4px;
  gap: 5px;
}
.filtros-wrap select,
.filtros-wrap input[type=text] {
  padding: 7px 10px;
  border: 1px solid #c8d1db;
  border-radius: 5px;
  font-size: 13px;
  min-width: 230px;
  background: #fafbfc;
}
.filtros-wrap select:focus,
.filtros-wrap input[type=text]:focus {
  border-color: #2563a8;
  outline: none;
  background: #fff;
}

.btn {
  padding: 8px 18px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.btn:hover { opacity: .85; }
.btn-primary   { background: #2563a8; color: #fff; }
.btn-secondary { background: #6b7280; color: #fff; }
.btn-excel     { background: #1d7a3d; color: #fff; }

/* CONTENIDO */
.contenido { padding: 20px 30px; }

.barra-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 8px;
}
.barra-info .total { font-size: 13px; color: #555; }
.barra-info .total strong { color: #1a3a5c; font-size: 15px; }

/* TABLA */
.tabla-wrap {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 6px rgba(0,0,0,.08);
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}
thead th {
  background: #1a3a5c;
  color: #fff;
  padding: 10px 12px;
  text-align: left;
  font-weight: 600;
  white-space: nowrap;
}
tbody tr:nth-child(even) { background: #f7f9fc; }
tbody tr:hover { background: #e8f0fb; }
tbody td {
  padding: 8px 12px;
  border-bottom: 1px solid #eaedf1;
  white-space: nowrap;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}
.badge-hab      { background: #dcfce7; color: #166534; }
.badge-anulada  { background: #fee2e2; color: #991b1b; }
.badge-denuncia { background: #fef3c7; color: #92400e; }
.badge-reemplazo{ background: #ede9fe; color: #5b21b6; }
.badge-otro     { background: #f1f5f9; color: #475569; }

.sin-datos {
  text-align: center;
  padding: 60px 20px;
  color: #888;
}
.sin-datos .icon { font-size: 40px; display: block; margin-bottom: 10px; }

footer {
  text-align: center;
  padding: 16px;
  font-size: 11px;
  color: #aaa;
}
</style>
</head>
<body>

<div class="header">
  <div class="logo">MT</div>
  <div>
    <h1>Reporte Master Tarjetas</h1>
    <p>Sistema CABAL &mdash; <?php echo date('d/m/Y H:i'); ?></p>
  </div>
</div>

<!-- FORMULARIO FILTROS -->
<form method="GET" action="">
<div class="filtros-wrap">

  <label>
    Archivo
    <select name="archivo">
      <option value="">— Todos los archivos —</option>
      <?php foreach ($archivos as $arch): ?>
        <option value="<?php echo htmlspecialchars($arch); ?>"
          <?php echo ($filtro_archivo === $arch) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($arch); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    Nombre y Apellido
    <input type="text" name="nombre_apellido"
           placeholder="Buscar por nombre..."
           value="<?php echo htmlspecialchars($filtro_nombre); ?>">
  </label>

  <button type="submit" class="btn btn-primary">&#128269; Filtrar</button>
  <a href="?" class="btn btn-secondary">&#10006; Limpiar</a>

  <!-- Exportar mantiene los filtros activos -->
  <a href="?<?php
    $qe = $_GET;
    $qe['exportar'] = '1';
    echo http_build_query($qe);
  ?>" class="btn btn-excel">&#128196; Exportar a Excel</a>

</div>
</form>

<div class="contenido">

  <div class="barra-info">
    <span class="total">
      Registros encontrados: <strong><?php echo number_format($total); ?></strong>
      <?php if ($filtro_archivo || $filtro_nombre): ?>
        &nbsp;<em style="color:#888">(filtro activo)</em>
      <?php endif; ?>
    </span>
  </div>

  <div class="tabla-wrap">
  <?php if ($total === 0): ?>
    <div class="sin-datos">
      <span class="icon">&#128269;</span>
      No se encontraron registros con los filtros seleccionados.
    </div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nro Cuenta</th>
          <th>Nro Tarjeta</th>
          <th>Nombre y Apellido</th>
          <th>Tipo Doc</th>
          <th>Nro Documento</th>
          <th>Causa</th>
          <th>Fecha Alta</th>
          <th>Fecha Emisi&oacute;n</th>
          <th>Fecha Vencimiento</th>
          <th>Archivo</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $result->fetch_assoc()):
        $causa = trim($row['Causa']);
        if ($causa === 'HABILITADA')                    $badge = 'badge-hab';
        elseif (strpos($causa,'ANULACION') !== false)   $badge = 'badge-anulada';
        elseif (strpos($causa,'DENUNCIA')  !== false)   $badge = 'badge-denuncia';
        elseif ($causa === 'REEMPLAZO')                 $badge = 'badge-reemplazo';
        else                                            $badge = 'badge-otro';
      ?>
        <tr>
          <td><?php echo $row['id']; ?></td>
          <td><?php echo $row['NumeroCuenta']; ?></td>
          <td><?php echo $row['NumeroTarjeta']; ?></td>
          <td><?php echo htmlspecialchars(trim($row['NombreApellido'])); ?></td>
          <td><?php echo $row['TipoDocumento']; ?></td>
          <td><?php echo $row['NroDocumento']; ?></td>
          <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($causa); ?></span></td>
          <td><?php echo date('d/m/Y', strtotime($row['FechaAlta'])); ?></td>
          <td><?php echo date('d/m/Y', strtotime($row['FechaEmision'])); ?></td>
          <td><?php echo date('d/m/Y', strtotime($row['FechaVencimiento'])); ?></td>
          <td><?php echo htmlspecialchars($row['Archivo']); ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
  </div>

</div>

<footer>Sistema de Reportes CABAL &copy; <?php echo date('Y'); ?></footer>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
