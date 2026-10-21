# Matriz de Módulos y Estado Funcional

**Proyecto:** Sistema Cabal - Familia Rionegrina Solidaria (FSR / SeNAF)  
**Fecha de Auditoría:** 20 de Octubre de 2026  
**Última Actualización:** 21 de Octubre de 2026 (Sprint Mejoras de Altas y Subida Centralizada)  
**Criterio de Evaluación:** Inspección de rutas activas en `index.php`, consistencia de base de datos, lógica de negocio y ejecución segura.

---

## 1. Resumen de Madurez y Estado Funcional

```text
[████████████████████░░░░░░░░] 68% Estado Global del Sistema
- Flujos Operativos Batch (Generación/Lectura de Archivos): 85% Funcional
- Robustez, Manejo de Errores y Validaciones: 55% Funcional
- Seguridad, Autenticación y Autorización: 10% Implementado (Subidas blindadas en upload.php)
- Consolidación y Limpieza de Código (Deuda Técnica): 35% Limpio
```

---

## 2. Matriz de Módulos del Sistema

### Módulo 1: Gestión de Beneficiarios y Altas de Tarjetas
Permite enrolar nuevos beneficiarios de planes sociales, generar el lote de alta para Cabal y conciliar las tarjetas y cuentas otorgadas por la entidad bancaria.

| Submódulo / Acción | Archivos Involucrados | Responsabilidad / Descripción | Estado | Observación / Bloqueante |
| :--- | :--- | :--- | :---: | :--- |
| **Cargar Archivo de Altas** | `SubirArchivo.html` (centralizado), `upload.php` | Interfaz unificada con normalización inteligente (case-insensitive: ej. `AlTaS.xlsx` &rarr; `Altas.xlsx`). | **Completado** | Validación estricta de extensiones y guardado protegido en `archivos/`. |
| **Procesar Excel (Altas.xlsx)** | `CargaAltasDeExcelaBD.php`, `LeerAltasaBD.php` | Lee filas de `archivos/Altas.xlsx` con validación exhaustiva y carga atómica en tabla `altas`. | **Completado** | **Modernizado:** Diseño Tablero Institucional, métricas, tabla de auditoría y transacciones PDO (21/10/2026). Respaldo en `CargaAltasDeExcelaBD_original.php`. |
| **Generar ALTAUSUCBPRE** | `GenerarNovedadAltas.php` | Emite lote bancario Anexo 1 Cabal (3 registros posicionales de 128 bytes por usuario). | **Completado** | **Modernizado:** Tablero institucional, visor terminal monospace, botón de copia pura con CRLF, salto de línea reglamentario final y botón de descarga directa HTTP (`ISO-8859-1`). Respaldo en `GenerarNovedadAltas_original.php`. |
| **Generador ALTAUSUCBPRE V2** | `V2/Altas.php` | Lee CSV y genera el archivo 128 bytes por usuario con descarga directa (`.782`). | **En progreso** | Desarrollado de forma moderna pero **no está enlazado en el menú principal**. |
| **Procesar MovAltas (Devolución)** | `ProcesaNovedadesAltas.php`, `RespuestaAltas.php` | Lee archivo de respuesta bancaria `MovAltas.txt` y vuelca a `beneficiario`. | **Completado** | Funcional, pero no previene duplicados en `beneficiario`. |
| **Exportar Beneficiarios** | `ExportarExcelBeneficiarios.php` | Genera y descarga un Excel con el padrón completo de `beneficiario`. | **Completado** | Utiliza PHPExcel y oculta números de tarjeta (`formatearTarjetaCredito`). |
| **Consulta Beneficiario por DNI** | `ConsultarBeneficiario.php`, `ConsultaBenefiario2.php` | Búsqueda por DNI para ver ficha personal y botón de impresión. | **Completado** | **Vulnerable a SQL Injection** en parámetro `$dni`. |

---

### Módulo 2: Acreditaciones de Saldo (Cargas de Crédito)
Procesa los montos a depositar en las tarjetas de los beneficiarios según los archivos enviados por el área de Suministros/Hacienda.

| Submódulo / Acción | Archivos Involucrados | Responsabilidad / Descripción | Estado | Observación / Bloqueante |
| :--- | :--- | :--- | :---: | :--- |
| **Subir Archivo Acreditaciones** | `SubirArchivo.html` (centralizado), `upload.php` | Recibe `Acreditaciones.xlsx` con normalización automática de mayúsculas/minúsculas y guarda en `archivos/`. | **Completado** | Enlazado directamente desde el menú principal hacia la interfaz unificada. |
| **Procesar Excel Acreditaciones (2026)** | `AcreditarSaldos2026.php`, `AcreditarSaldo2026.php` | Trunca `acreditar2`, cruza CUIL del Excel con `beneficiario` para obtener `NumeroCuenta`. | **Oculto en Menú** | Ocultado en menú principal a solicitud para evitar ejecuciones destructivas no supervisadas. |
| **Procesar Excel (Versión 2023)** | `AcreditarSaldos-2023.php`, `AcreditarSaldos.php` | Versión anterior que operaba sobre la tabla `acreditar`. | **Incompleto** | Código duplicado mantenido por compatibilidad histórica. |
| **Generar NOVCAR 2026** | `GenerarCargaCredito2026.php` | Lee `acreditar2` y escribe lote en `Novcar/novcar.782` (cabecera, detalle `6339`, trailer). | **Completado** | Graba en disco pero advierte incompatibilidad con Linux por saltos de línea LF vs CRLF. |
| **Generar NOVCAR (Legado)** | `GenerarCargaCredito2.php`, `GenerarCargaCredito.php` | Generador anterior para la tabla `acreditar`. | **Incompleto** | Huérfano; superado por la versión 2026. |
| **Subir y Verificar NOVCAR** | `subir_novcar.php`, `VerificarNovcar.php` | Permite subir y auditar en pantalla la integridad posicional de `novcar.782`. | **Completado** | Valida campos posicionales de longitud y montos. |
| **Subir y Procesar Resp. Cargas** | `SubirArchivo.html` (centralizado), `upload.php`, `ProcesarCarga.php` | Recibe `RespuestaCargas.txt` (782_RCAR) y muestra confirmación en pantalla. | **Completado** | Enlace del menú principal redirigido a `SubirArchivo.html`. |

---

### Módulo 3: Descarga de Saldos / Débitos de Tarjetas
Permite reversar o descargar fondos remanentes de las cuentas de beneficiarios mediante novedades tipo `62` (débito).

| Submódulo / Acción | Archivos Involucrados | Responsabilidad / Descripción | Estado | Observación / Bloqueante |
| :--- | :--- | :--- | :---: | :--- |
| **Procesar DescargaSaldos.xlsx** | `ProcesarDescargaSaldos.php` | Lee planillas de bajas de saldo y calcula totales de débito. | **Incompleto** | Imprime los registros con `&nbsp;` y `<br>` en pantalla en lugar de generar archivo descargable. |
| **Generador NOVTRAN (Débito/Crédito)** | `cereo_upload.php` | Generador autónomo que recibe un CSV y genera `NOVTRAN.782` con descarga directa. | **Completado** | Código limpio y estructurado con soporte de código `62` (descarga) y `63` (carga). |
| **Generar NOVDES Alternativo** | `generar_novdes.php` | Generación alternativa de novedad de descarga. | **En progreso** | Duplica parcialmente la funcionalidad de `cereo_upload.php`. |

---

### Módulo 4: Movimientos Mensuales y Conciliación
Importa las liquidaciones mensuales enviadas por Cabal y genera los informes de auditoría interna.

| Submódulo / Acción | Archivos Involucrados | Responsabilidad / Descripción | Estado | Observación / Bloqueante |
| :--- | :--- | :--- | :---: | :--- |
| **Procesar Movimientos.txt** | `ProcesarMovimientos.html`, `ProcesarMovimientos2.php` | Lee archivo posicional `Movimientos.txt` y lo almacena en tabla `movimientos`. | **Completado** | **Bug en `EstaFechaMovimiento()`** en `BD/BD.php:L78`. El chequeo de duplicados puede fallar. |
| **Reporte Movimientos Mensual** | `ReporteMovimientos.php`, `consultaMovimiento.php` | Muestra movimientos por mes, año y archivo. | **Completado** | Lento en bases grandes por falta de índices en tabla `movimientos`. |
| **Reporte por Beneficiario** | `ReporteMovimientosPorNombre.php`, `consultaMovimientoPorNombre.php` | Filtra consumos por nombre y apellido del titular. | **Completado** | **Vulnerable a SQL Injection** en parámetro `NombreApellido`. |
| **Consulta Movimientos por DNI** | `consultaMovimientoPorDNI.php` | Muestra histórico de consumos para un DNI dado. | **Completado** | **Vulnerable a SQL Injection** en parámetro `DNI`. |

---

### Módulo 5: Maestro de Plásticos (Emisión y Vencimientos)
Gestiona el inventario de plásticos emitidos reportados por Cabal (`MAESPLA`).

| Submódulo / Acción | Archivos Involucrados | Responsabilidad / Descripción | Estado | Observación / Bloqueante |
| :--- | :--- | :--- | :---: | :--- |
| **Procesar Maestro Plásticos** | `ProcesarMaestroPlasticosArchivo.php`, `ProcesarMaestroPlasticos.php` | Parsea archivo posicional y registra en `MasterTarjetas`. | **Completado** | Almacena fechas de alta, emisión y vencimiento por plástico. |
| **Reporte Maestro Tarjetas** | `reporte_tarjetas.php` | Listado avanzado con filtros por archivo, nombre y exportación Excel. | **Completado** | Excelente implementación con Bootstrap moderno y consultas preparadas. |

---

### Módulo 6: Módulos Prototipo, Inconclusos y Código Huérfano
Sub-aplicaciones y experimentos que se encuentran dentro del repositorio pero no están integrados al flujo de producción.

| Submódulo / Componente | Archivos Involucrados | Estado | Descripción y Diagnóstico |
| :--- | :--- | :---: | :--- |
| **Acreditación Manual AJAX** | `crud-acreditacion/crud.php`, `v3/`, `gemin/`, `chatgpt/` | **Incompleto** | Permite ingresar acreditaciones individuales por CUIT mediante AJAX. La versión `v2` falla porque hace `INSERT INTO otra_tabla` (tabla inexistente). Las versiones `v1` y `v3` presentan SQL Injection. |
| **CRUD Beneficiario 1** | `crud/index.php`, `create.php`, `edit.php` | **Incompleto** | CRUD genérico con DataTables. Vulnerable a SQL Injection y sin autenticación ni CSRF. |
| **CRUD Beneficiario 2** | `Crud2/index.php`, `delete.php`, `update.php` | **Incompleto** | Prototipo roto. `delete.php` espera `$id = $_POST['id']` pero se invoca vía `<a href="delete.php?id=...">` (GET). |
| **Subida Genérica de Archivos** | `upload.php`, `SubirArchivo.html` | **Inseguro** | **Vulnerabilidad Crítica de Ejecución Remota de Código (RCE)**. Permite subir cualquier archivo sin validar extensión. |
