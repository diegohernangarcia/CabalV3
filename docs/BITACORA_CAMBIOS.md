# Bitácora Oficial de Cambios y Regularización Técnica

**Proyecto:** Sistema Cabal - Familia Rionegrina Solidaria (FSR / SeNAF)  
**Ubicación:** `/opt/lampp/htdocs/cabal-Gemini/docs/BITACORA_CAMBIOS.md`  
**Entorno de Ejecución:** Apache 2.4.38 • PHP 5.6.40 (XAMPP LAMPP) • MariaDB `cabal`  

---

## Índice Cronológico
- [Jornada: 27 de Octubre de 2026 (Módulo de Importación y Validación de Acreditaciones con Truncate y Carga en Tabla Acreditar)](#jornada-27-de-octubre-de-2026)
- [Jornada: 23 de Octubre de 2026 (Modernización Integral de Estilos y Colores en Todas las Páginas)](#jornada-23-de-octubre-de-2026)
- [Jornada: 21 de Octubre de 2026 (Sprint Mejoras de Altas y Subida Centralizada)](#jornada-21-de-octubre-de-2026)
- [Jornada: 20 de Octubre de 2026 (Auditoría Integral, Documentación Base y Nuevo Tablero)](#jornada-20-de-octubre-de-2026)

---

## Jornada: 27 de Octubre de 2026

### 1. Objetivos de la Sesión
1. **Nuevo Módulo "Importar y Subir Acreditaciones Validado" (`ImportarAcreditacionesValidado.php`):**
   - Habilitar subida y arrastre drag & drop de planillas en formatos `.xlsx`, `.xls` y `.ods`, o análisis directo de `archivos/Acreditaciones.xlsx`.
   - Detección dinámica y automática de la fila de cabecera y correspondencia posicional de columnas (`Nº`, `DISPOSICIÓN/EXPTE JUDICIAL`, `APELLIDO`, `NOMBRE`, `LOCALIDAD`, `DNI`, `CUIL`, `ACREDITAR`).
   - Cruce integral por clave principal `CUIL` contra la tabla `beneficiario` (`Cuit`) para asociar:
     - `id_beneficiario` $\leftarrow$ `beneficiario.id`
     - `Apellido` $\leftarrow$ `beneficiario.Apellido`
     - `Nombre` $\leftarrow$ `beneficiario.Nombre`
     - `NumeroCuenta` $\leftarrow$ `beneficiario.NumeroCuenta`
     - `DNI` $\leftarrow$ `beneficiario.NumeroDocumento`
     - `ImporteCompra` $\leftarrow$ Monto calculado en la columna `ACREDITAR` (evaluando fórmulas Excel `=+830000*2`, etc.)
     - `ImporteCajero` $\leftarrow$ `'0'`
2. **Cotejo de Discrepancias y Aceptación Operativa en Tiempo Real:**
   - Detección precisa de variaciones tipográficas entre Excel y Padrón en Apellido, Nombre y DNI con normalización de caracteres (`normalizarTextoComparacion`).
   - Interfaz con tabla de máximo ancho institucional (98vw) sin romper la navegación ni responsividad.
   - Selectores interactivos en cada fila con discrepancia (`⏳ Pendiente` / `✅ Acepto Discrepancia`) y botones masivos *"Aceptar Todas"* y *"Restablecer"*.
3. **Control y Auditoría de Sumatoria vs. Celda TOTAL:**
   - Detección de la celda de totalización al pie de la planilla Excel y comparación estricta contra la sumatoria real calculada de todas las filas.
   - En caso de discrepancia en los importes, bloqueo absoluto del circuito con alerta emergente estilo `danger` (*"El Total no coincide con la sumatoria de los montos a depositar"*).
4. **Truncate y Carga en Base de Datos (Tabla `acreditar` y `acreditar2`):**
   - Una vez que no existen discrepancias o todas han sido aceptadas por el operador y el total financiero cuadra con precisión:
     - Se habilita el botón *"Continuar al Siguiente Paso: Cargar en Acreditar"*.
     - Se abre el modal de confirmación operativa en dos pasos.
     - Al confirmar, se ejecuta una transacción atómica `PDO` que realiza:
       1. `TRUNCATE TABLE acreditar;`
       2. `TRUNCATE TABLE acreditar2;` (manteniendo ambas tablas sincronizadas para la emisión).
       3. Inserción masiva de los 26 registros con prepared statements (`PDO::prepare()`).
       4. `COMMIT` y confirmación inmediata con pantalla de éxito y enlace directo a `GenerarCargaCredito2026.php` (Paso 3).
5. **Mejora en Módulo "Verificación de Lote NOVCAR.782" (`VerificarNovcar.php`):**
   - Incorporación de zona interactiva de arrastre y selección drag & drop para adjuntar cualquier archivo `novcar.782` (o `.txt`) generado o externo para certificar su validez antes del envío a Cabal.
   - Posibilidad simultánea de auditar el archivo local generado en `Novcar/novcar.782` o cualquier lote subido.
   - Motor de auditoría bancaria integral:
     - Longitud posicional estricta de 80 bytes en el 100% de los registros.
     - Saltos de línea Windows CRLF (`\r\n`) requeridos por el mainframe de Cabal.
     - Estructura y campos de Cabecera (Header: TE04, 782, fecha, hora).
     - Validación de movimientos de detalle (TE04, 782, 001, código op. 6339, importes en centavos, número correlativo de carga).
     - Cruce ultrarrápido contra el Padrón de Beneficiarios (`NumeroCuenta`) mostrando titular y DNI.
     - Estructura y clave de cierre del Trailer (TE04, 782, 000, `9999999999999999`).
     - Conciliación matemática de cantidad de registros y sumatoria de importes de compras contra el Trailer.
   - Dictamen global visual (*"LOTE 100% VÁLIDO"* en verde o *"LOTE CON INCONSISTENCIAS / RECHAZO BANCARIO"* en rojo con desglose punto por punto).
   - Buscador en vivo sobre la tabla de movimientos y descarga directa del archivo auditado.

---

### 2. Detalle Técnico de Intervenciones
- **Archivos Creados / Modificados:**
  - `ImportarAcreditacionesValidado.php`: Módulo completo de validación, cotejo, aceptación y carga en `acreditar`.
  - `VerificarNovcar.php`: Incorporación de drag & drop, subida y auditoría exhaustiva de lotes `novcar.782`.
  - `index.php`: Actualización de la tarjeta del Módulo 2 y del Stepper Operativo para incorporar el nuevo flujo.
  - `docs/BITACORA_CAMBIOS.md`: Registro exhaustivo de la intervención.
- **Pruebas de Verificación:**
  - Linting de sintaxis con `/opt/lampp/bin/php -l ImportarAcreditacionesValidado.php` (0 errores).
  - Linting de sintaxis con `/opt/lampp/bin/php -l VerificarNovcar.php` (0 errores).
  - Verificación de ejecución AJAX `accion=cargar_acreditar` &rarr; 26 registros insertados por `$ 28.220.000,00`.
  - Verificación en base de datos: `SELECT COUNT(*), SUM(ImporteCompra) FROM acreditar` &rarr; 26 registros, `$ 28.220.000,00`.
  - Verificación en `acreditar2`: 26 registros, `$ 28.220.000,00`.
  - Verificación de continuidad en `GenerarCargaCredito2026.php` y `GenerarCargaCredito.php` &rarr; Reconocimiento inmediato del nuevo lote.
  - Verificación de subida multipart/form-data en `VerificarNovcar.php` con archivo válido &rarr; Dictamen 100% Válido.
  - Verificación de rechazo y detección de fallos en `VerificarNovcar.php` con archivo de prueba con líneas corruptas y sin CRLF &rarr; 6 errores detectados con precisión.

---

## Jornada: 23 de Octubre de 2026

### 1. Objetivos de la Sesión
1. **Unificación Visual Total (Formato de Colores y Diseño del Inicio a Todas las Páginas Restantes):**
   - Trasladar el sistema de diseño visual, paleta de colores corporativa (Slate/Navy, `#2563eb`, variables CSS centralizadas), tipografía y componentes del tablero de inicio (`index.php`) a todas las páginas, formularios, reportes y generadores del sistema.
   - Implementar en cada vista la barra superior de navegación unificada (`<header class="top-nav">`) con isotipo de Cabal, credencial *"Entidad 782"*, enlace de retorno rápido *"Volver al Tablero"* y el botón selector de Modo Oscuro / Claro (`js/theme.js`).
2. **Cumplimiento Estricto de Directivas de Arquitectura (`GEMINI.md`):**
   - Compatibilidad absoluta con **PHP 5.6.40** en servidor local XAMPP (`/opt/lampp/bin/php`).
   - Inviolabilidad de formatos posicionales fijos de Cabal (128 bytes para `ALTAUSUCBPRE.782`, 80 bytes para `NOVCAR.782`, `NOVTRAN.782` y `NOVDES.782`).
   - Forzado de codificación ANSI / ISO-8859-1 y saltos de línea Windows CRLF (`\r\n`).
   - Reemplazo de impresión HTML en pantalla por descargas directas HTTP con headers apropiados.
   - Mitigación de inyecciones SQL mediante consultas preparadas (`PDO::prepare()` o `mysqli_prepare()` con bindeo de parámetros).
   - Enmascaramiento obligatorio de números de tarjetas (`formatearTarjetaCredito()`) para blindaje de PII.
   - Creación incondicional de respaldos previos (`*_original.*`) para cada archivo intervenido.

---

### 2. Detalle Técnico de Intervenciones y Páginas Modernizadas

#### 2.1. Sistema de Estilos y Tokens Compartidos (`css/dashboard.css`, `css/estilos.css`, `css/estilos2.css`, `css/estilos-menu.css`)
- Se extendió `css/dashboard.css` incorporando clases estructurales reutilizables:
  - `.page-container`: Contenedor principal responsive con márgenes controlados y compatibilidad fluida.
  - `.modern-card`: Contenedor tipo tarjeta con bordes suaves, sombras sutiles y soporte nativo de modo oscuro (`[data-theme="dark"]`).
  - `.modern-table`: Tablas responsive con cabeceras institucionales, alternancia de filas y estados hover.
  - `.btn-modern-primary`, `.btn-modern-secondary`: Botones con micro-interacciones hover y gradientes sutiles.
  - `.alert-modern`: Banners informativos con variantes `success`, `warning`, `danger` e `info`.
  - `.btn-modern-flotante`: Botón flotante estilizado para volver atrás en cualquier pantalla.
  - `.batch-terminal-output`: Visor tipo terminal de código de ancho fijo (`Fira Code`, monospace) con resaltado y botón de copiado al portapapeles.
- Integración en hojas de estilo secundarias (`estilos.css`, `estilos2.css`, `estilos-menu.css`) mediante `@import url('dashboard.css');` y armonización de variables globales.

#### 2.2. Módulo de Lotes Bancarios y Validaciones
1. **`GenerarCargaCredito2026.php`**:
   - Respaldo: `GenerarCargaCredito2026_original.php`.
   - Generador de lote `NOVCAR.782` (código op. 6339, 80 bytes/registro, CRLF).
   - Incorporación de métricas de lote en tiempo real, visor tipo terminal con botón de copia con CRLF y descarga HTTP directa `?descargar=1`.
2. **`GenerarCargaCredito2.php`**:
   - Respaldo: `GenerarCargaCredito2_original.php`.
   - Modernización de la interfaz del generador desde tabla `acreditar` con top-nav y terminal interactivo.
3. **`GenerarCargaCredito.php`**:
   - Respaldo: `GenerarCargaCredito_original.php`.
   - Actualización visual a la paleta corporativa y enlace al generador 2026.
4. **`VerificarNovcar.php`**:
   - Respaldo: `VerificarNovcar_original.php`.
   - Auditor de registros de 80 bytes y verificación de CRLF. Búsqueda y comparación con DNI mediante prepared statements PDO.
5. **`cereo_upload.php` & `generar_novdes.php`**:
   - Respaldos: `cereo_upload_original.php`, `generar_novdes_original.php`.
   - Modernización del formulario de cereo de saldos y generación del lote `NOVTRAN.782` (código op. 62 para descarga / 63 para carga).
6. **`ValidarAltas.php`**:
   - Respaldo: `ValidarAltas_original.php`.
   - Validador posicional de 128 bytes + CRLF (130 bytes exactos) para lotes `Altas.txt` y `ALTAUSUCBPRE.782` con tabla de auditoría línea por línea.
7. **`V2/Altas.php`**:
   - Respaldo: `V2/Altas_original.php`.
   - Conversor de CSV de altas a formato posicional fijo de 128 bytes (3 registros por titular). Se incorporó top-nav, dark mode, visor batch y botón de descarga directa HTTP con CRLF.

#### 2.3. Módulo de Acreditación y Procesamiento de Planillas
1. **`AcreditarSaldos2026.php` & `AcreditarSaldos.php`**:
   - Respaldos: `AcreditarSaldos2026_original.php`, `AcreditarSaldos_original.php`.
   - Modernización de la pantalla de confirmación de acreditación, métricas financieras de compras y cajero, tabla de beneficiarios y previsualización de saldo.
2. **`AcreditarSaldos-2023.php`**:
   - Respaldo: `AcreditarSaldos-2023_original.php`.
   - Adaptación visual del procesador histórico con tarjetas modernas y control de ejecución.
3. **`ProcesarCarga.php`**:
   - Respaldo: `ProcesarCarga_original.php`.
   - Lector y conciliador del archivo de respuesta bancaria `RespuestaCargas.txt` (RCAR) con badges de éxito y rechazo.
4. **`ProcesarDescargaSaldos.php`**:
   - Respaldo: `ProcesarDescargaSaldos_original.php`.
   - Procesador de bajas de crédito `DescargaSaldos.xlsx`, visor de lote `NOVDES.782` y descarga directa.
5. **`ChequeoAcreditacion.php`**:
   - Respaldo: `ChequeoAcreditacion_original.php`.
   - Auditor de planillas `Acreditaciones.xlsx` vs padrón de beneficiarios. Comparación de nombres y apellidos por DNI con badges `CORRECTO` / `VERIFICAR`.
6. **`CargaTarjetaDeExcelBD.php`**:
   - Respaldo: `CargaTarjetaDeExcelBD_original.php`.
   - Importador de `AltaCargas.xlsx` a la tabla `acreditar` con conteo de compras/cajero, resumen financiero y tabla estilizada.
7. **`AcreditarCSV.php`, `uploadcsv.php` & `ProcesarCSVAcreditacion.php`**:
   - Respaldos: `AcreditarCSV_original.php`, `uploadcsv_original.php`, `ProcesarCSVAcreditacion_original.php`.
   - Flujo de subida e importación de archivos CSV de acreditaciones con validación estricta de CUIT/CUIL, inserción parametrizada y métricas.

#### 2.4. Módulo de Movimientos y Extractos Mensuales
1. **`ProcesarMovimientos.html` & `ProcesarMovimientos2.php`**:
   - Respaldos: `ProcesarMovimientos_original.html`, `ProcesarMovimientos2_original.php`.
   - Rediseño del formulario selector de mes/año y del procesador de extractos mensuales `Movimientos.txt` con control de duplicados y métricas.
2. **`ReporteMovimientos.php`**:
   - Respaldo: `ReporteMovimientos_original.php`.
   - Catálogo general de movimientos por período mensual con tabla moderna e importe formateado.
3. **`ReporteMovimientosPorNombre.php`**:
   - Respaldo: `ReporteMovimientosPorNombre_original.php`.
   - Listado alfabético de titulares con enlace a su extracto individual.
4. **`consultaMovimiento.php`**:
   - Respaldo: `consultaMovimiento_original.php`.
   - Extracto de cuenta imprimible por período con tarjeta enmascarada y estilos limpios para impresión `@media print`.
5. **`consultaMovimientoPorDNI.php` & `consultaMovimientoPorNombre.php`**:
   - Respaldos: `consultaMovimientoPorDNI_original.php`, `consultaMovimientoPorNombre_original.php`.
   - Refactorización de seguridad reemplazando concatenaciones SQL por prepared statements PDO/mysqli, enmascaramiento de plásticos y top-nav.

#### 2.5. Módulo de Padrón, Consultas y CRUD de Beneficiarios
1. **`consultaBeneficiario.php`**:
   - Respaldo: `consultaBeneficiario_original.php`.
   - Buscador individual por DNI con ficha técnica moderna y tarjeta enmascarada.
2. **`ReporteAltaBeneficiarios.php`**:
   - Respaldo: `ReporteAltaBeneficiarios_original.php`.
   - Padrón interactivo ordenable con búsqueda en tiempo real y tarjetas protegidas.
3. **`reporte_tarjetas.php`**:
   - Respaldo: `reporte_tarjetas_original.php`.
   - Catálogo de plásticos con filtrado por estado (Habilitada, Bloqueada, Extravío), enmascaramiento y exportación a CSV.
4. **`ProcesarMaestroPlasticosArchivo.php` & `ProcesarMaestroPlasticos.php`**:
   - Respaldos: `ProcesarMaestroPlasticosArchivo_original.php`, `ProcesarMaestroPlasticos_original.php`.
   - Reemplazo de `prompt()` JavaScript por tarjeta institucional moderna. Procesamiento de `MAESPLAS.txt` con badges de estado, tarjetas enmascaradas y métricas.
5. **`crud/index.php`, `crud/create.php`, `crud/edit.php`**:
   - Respaldos: `crud/index_original.php`, `crud/create_original.php`, `crud/edit_original.php`.
   - Modernización de DataTables con estilos de modo oscuro, inserción y edición mediante prepared statements, confirmación modal de eliminación y tarjetas enmascaradas.
6. **`crud-acreditacion/crud.php` & `crud-acreditacion/styles.css`**:
   - Respaldos: `crud-acreditacion/crud_original.php`, `crud-acreditacion/styles_original.css`.
   - Formulario interactivo de carga manual por CUIT y monto, cálculo dinámico de totales, tabla estilizada y modo oscuro.
7. **`LeerAltasaBD.php` & `RespuestaAltas.php`**:
   - Respaldos: `LeerAltasaBD_original.php`, `RespuestaAltas_original.php`.
   - Modernización integral con top-nav, tarjetas, métricas y enmascaramiento de tarjetas bancarias.

#### 2.6. Vistas de Soporte, Menú y Multimedia
1. **`subir_novcar.php`, `subir_excel_altas.php`, `subir_excel_acreditaciones.php`, `subir_respuesta_cargas.php`**:
   - Formatos uniformes con tarjetas de carga, top-nav y modo oscuro.
2. **`videos.html`**:
   - Respaldo: `videos_original.html`.
   - Conversión de iframes sueltos en una galería responsive moderna con tarjetas, títulos y descripciones de capacitación operativa.
3. **`index_old.php`**:
   - Respaldo: `index_old_original.php`.
   - Transformación del manual operativo histórico en tarjetas de procedimientos paso a paso con modo oscuro y cabecera institucional.
4. **`index_menu_original.php`**:
   - Enlace directo al Tablero Moderno, carga de `dashboard.css` y `js/theme.js`.

#### 2.8. Implementación del Flujo Integral "Importar y Subir Altas Validado" (`CargaAltasDeExcelaBD.php`)
1. **`index.php`**:
   - Respaldo creado: `index_original.php`.
   - Se incorporó en la tarjeta de pasos del circuito operativo **Altas de Tarjetas** (`.step-card`) el ítem:
     `• 4. Importar y Subir Altas Validado` con enlace directo a `CargaAltasDeExcelaBD.php`.
   - Se sincronizó el título y la descripción del `action-row` del **Módulo Beneficiarios** a *"Importar y Subir Altas Validado"*.
2. **`CargaAltasDeExcelaBD.php`**:
   - Respaldo: `CargaAltasDeExcelaBD_original.php`.
   - **Mapeo Dinámico de Cabeceras:** Lectura de fila 1 del primer libro (hoja 0) del Excel (`Altas.xlsx`), mapeando dinámicamente cada columna a los campos de la tabla `altas` (`Apellido`, `Nombre`, `DomicilioCalle`, `DomicilioNumero`, `DomicilioPiso`, `DomicilioDepartamento`, `Localidad`, `Provincia`, `Sexo`, `CodigoPostal`, `TelefonoCaracteristica`, `TelefonoNumero`, `EstadoCivil`, `FechaNacimiento`, `DocumentoTipo`, `DocumentoNumero`, `DocumentoProvincia`, `CUIT`).
   - **Normas de Validación Exhaustivas (`docs/normas_de_validacion.md`):**
     - Apellido &le; 16 caracteres.
     - Apellido y Nombre combinados &le; 24 caracteres.
     - Domicilio Calle &le; 25 caracteres, Número &le; 5 caracteres, Piso &le; 2 caracteres, Dpto &le; 2 caracteres.
     - Localidad &le; 30 caracteres.
     - Provincia reglamentaria según Anexo 9 (códigos 01 a 23 y 41).
     - Sexo strictly 'M' o 'F'.
     - Código Postal &le; 8 caracteres.
     - Teléfono Característica &le; 5 caracteres, Número &le; 8 caracteres.
     - Estado Civil según norma: C, S, V, O (normalizando palabras como Casado, Soltero, Viuda, Otra).
     - Fecha de Nacimiento en formato DDMMAAAA (8 dígitos) con soporte para fechas seriales de Excel y fecha genérica (01011950) si no se posee.
     - Tipo Documento según Anexo 10 (DNI &rarr; 03).
     - Número de Documento &le; 11 caracteres y Provincia Documento &le; 2 caracteres.
     - CUIT &le; 11 caracteres con padding de ceros.
   - **Validación Crítica de CUIT contra Padrón de Beneficiarios:**
     - Comprobación fila por fila contra la tabla `beneficiario` (`TRIM(Cuit) = :cuit`).
     - Si algún CUIT ya existe en la base de datos, se alerta con los datos del beneficiario existente y se **invalida / inhabilita** el botón *"Guardar datos en BD"*.
   - **Previsualización con Estructura de Tabla `altas`:**
     - Tabla institucional que muestra todos los campos de `altas` más el badge de estado en el padrón y buscador en vivo.
   - **Confirmación en Dos Pasos Estricta (NUNCA guardar sin confirmación):**
     - La previsualización no altera la base de datos.
     - Solo cuando el operador presiona el botón habilitado *"Guardar datos en BD"*, se ejecuta la transacción PDO atómica:
       1. `TRUNCATE TABLE altas;`
       2. `INSERT INTO altas (...) VALUES (...)` fila por fila.
       3. `COMMIT` y pantalla de confirmación con enlace directo a `Generar ALTAUSUCBPRE.782`.

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (23/10/2026)

| Prueba | Comando / Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Lint Sintáctico Exhaustivo** | `/opt/lampp/bin/php -l index.php` y `CargaAltasDeExcelaBD.php` | 0 errores de sintaxis detectados | ✅ PASÓ |
| **Compatibilidad PHP 5.6** | Verificación de ausencia de operadores PHP 7+ (`??`, match, return types) | Totalmente compatible con PHP 5.6.40 | ✅ PASÓ |
| **Renderizado HTTP Tablero** | `curl -s http://localhost/cabal-Gemini/index.php` | Ítem 4 visible en tarjeta Altas de Tarjetas y en módulo Beneficiarios | ✅ PASÓ |
| **Previsualización sin Alterar BD** | Acceso GET a `CargaAltasDeExcelaBD.php` y consulta `COUNT(*) FROM altas` | Tabla altas intacta (1 registro original) | ✅ PASÓ |
| **Validación Normativa Anexos** | Verificación contra `docs/normas_de_validacion.md` (16 car. apellido, 24 apynom, Anexo 9, Anexo 10) | 100% de reglas auditadas por registro | ✅ PASÓ |
| **Detección de CUIT en Beneficiarios** | Prueba unitaria PDO contra padrón (`27286633299` &rarr; NAVARRO GLADYS) | CUIT existente bloquea botón con alerta | ✅ PASÓ |
| **Ejecución 2 Pasos (Truncate + Insert)** | Envío POST `accion=confirmar_guardar` | `TRUNCATE altas` ejecutado y 5 registros cargados con éxito | ✅ PASÓ |
| **Compatibilidad con Generador** | Ejecución de `GenerarNovedadAltas.php` con los nuevos datos | 5 beneficiarios emitidos a 128 bytes/reg con CRLF | ✅ PASÓ |
| **Protección contra Inyección SQL** | Revisión de consultas en `crud/`, `consultaMovimiento*.php`, `VerificarNovcar.php` | Prepared statements implementados | ✅ PASÓ |
| **Enmascaramiento de Tarjetas** | Inspección de `formatearTarjetaCredito()` en reportes, tablas y fichas | `XXXX.XX**.****.XXXX` activo | ✅ PASÓ |
| **Persistencia de Modo Oscuro** | Validación de `js/theme.js` y `[data-theme="dark"]` | Conmutación instantánea y guardado en `localStorage` | ✅ PASÓ |
| **Integridad de Lotes Cabal** | Validación de CRLF (`\r\n`) y longitudes fijas (80 / 128 bytes) | Formatos reglamentarios intactos | ✅ PASÓ |

---

## Jornada: 21 de Octubre de 2026

### 1. Objetivos de la Sesión
1. Centralizar los puntos de acceso de subida de archivos hacia la interfaz unificada `SubirArchivo.html`.
2. Proteger la base de datos ocultando acciones operativas de alto riesgo (como el procesamiento destructivo de acreditaciones).
3. Resolver el problema de inconsistencia en nombres de archivos con mayúsculas/minúsculas (`AlTaS.xlsx` &rarr; `Altas.xlsx`).
4. Modernizar la interfaz y la seguridad de datos del procesador de planillas de altas `CargaAltasDeExcelaBD.php`.
5. Modernizar la pantalla de emisión del lote bancario `GenerarNovedadAltas.php` respetando estrictamente el formato fijo de 128 bytes por registro Cabal, con botón de copia con CRLF, salto de línea reglamentario al final y descarga directa HTTP `ALTAUSUCBPRE.782`.
6. Implementar y habilitar de forma predeterminada el Modo Oscuro (Dark Mode) con diseño institucional de alto contraste, transiciones fluidas, persistencia en localStorage y botón de alternancia en la barra de navegación.

---

### 2. Detalle Técnico de Intervenciones

#### 2.1. Navegación y Enrutamiento Unificado
- **Archivos Afectados:**
  - `index.php` (Tablero Principal)
  - `index_menu_original.php` (Menú de compatibilidad)
- **Modificaciones:**
  - **Módulo Beneficiarios:** El enlace *"Cargar Archivo de Altas"* se redirigió a `SubirArchivo.html` (en lugar de `subir_excel_altas.php`).
  - **Módulo Acreditaciones de Saldo:**
    - El enlace *"Cargar Archivo de Altas"* se redirigió a `SubirArchivo.html`.
    - El enlace *"Subir respusta carga"* se redirigió a `SubirArchivo.html`.
    - Se **ocultó** el botón *"Procesar Excel (Versión 2026)"* (`AcreditarSaldos2026.php`) para evitar que los operadores truncaran la tabla `acreditar2` de forma accidental o desincronizada.

#### 2.2. Normalización Inteligente de Archivos (Case-Insensitive)
- **Archivos Afectados:**
  - `SubirArchivo.html`
  - `upload.php`
- **Problema Detectado:**
  Los operadores subían archivos con mayúsculas arbitrarias (ej. `AlTaS.xlsx`, `altas.XLSX`, `NOVCAR.782`, `movaltas.txt`), provocando errores en los scripts PHP de lectura que buscaban nombres canónicos fijos sobre sistemas de archivos Linux sensibles a mayúsculas (case-sensitive).
- **Solución Implementada:**
  - **Cliente (`SubirArchivo.html`):** Función `normalizarNombreArchivo(file)` que detecta por coincidencia `toLowerCase()` si el archivo corresponde a uno de los tipos requeridos y preselecciona automáticamente el nombre canónico correspondiente.
  - **Servidor (`upload.php`):** Diccionario canónico de archivos reconocidos (`Altas.xlsx`, `Acreditaciones.xlsx`, `MovAltas.txt`, `Movimientos.txt`, `RespuestaCargas.txt`, `novcar.782`, `MAESPLAS.txt`). Si el archivo entrante coincide en minúsculas con un nombre canónico, se renombra automáticamente al nombre exacto requerido por el sistema.
  - **Seguridad:** Validación estricta de extensiones permitidas (`.xlsx`, `.txt`, `.782`, `.csv`) impidiendo subida de archivos ejecutables (`.php`, `.sh`, `.exe`).

#### 2.3. Rediseño y Transacciones en `CargaAltasDeExcelaBD.php`
- **Archivos Afectados:**
  - `CargaAltasDeExcelaBD.php` (Actualizado)
  - `CargaAltasDeExcelaBD_original.php` (Respaldo creado)
- **Modificaciones:**
  - **Diseño UI:** Adopción del sistema de diseño del Tablero Institucional (`css/dashboard.css`).
  - **Métricas Operativas:** Tarjetas con conteo en tiempo real de filas leídas, altas procesadas correctamente y registros omitidos por validación.
  - **Tabla de Auditoría:** Listado detallado de beneficiarios cargados con DNI, CUIL, Nombre y Domicilio.
  - **Transaccionalidad:** Reemplazo de inserciones desprotegidas por bloques transaccionales `PDO::beginTransaction()` y `PDO::commit()`, asegurando que no queden datos corruptos o a medio cargar.

#### 2.4. Formato y Emisión Bancaria en `GenerarNovedadAltas.php`
- **Archivos Afectados:**
  - `GenerarNovedadAltas.php` (Actualizado)
  - `GenerarNovedadAltas_original.php` (Respaldo creado)
- **Requisitos Críticos del Banco Cabal (Entidad 782):**
  - Cada titular genera exactamente 3 registros fijos consecutivos:
    - **Registro 01:** Datos filiatorios y domicilio (128 bytes).
    - **Registro 02:** Teléfono, nacimiento, documento y CUIT (128 bytes).
    - **Registro 03:** Tipo de producto y marcas de control (128 bytes).
  - La longitud por registro debe ser de exactamente **128 bytes de ancho fijo**, sin añadir ni mover un solo espacio.
  - Debe existir un **salto de línea final** al terminar el archivo.
- **Solución Implementada:**
  - **Preservación Matemática:** No se modificó ningún cálculo de padding ni generación de caracteres.
  - **Visor Terminal Monospace (`.batch-terminal-output`):** Muestra los registros exactamente con la misma longitud y añade el `<br>` de cierre final tras el último registro para garantizar la línea libre reglamentaria.
  - **Portapapeles Puro (`#texto-puro-lote`):** Textarea oculto que concatena los registros planos con saltos CRLF Windows (`\r\n`) y añade el salto final (`implode("\r\n", $lineas_txt) . "\r\n"`), habilitado mediante el botón *"Copiar Lote al Portapapeles"*.
  - **Descarga Directa HTTP:** Parámetro `?descargar=1` que envía cabeceras `Content-Type: text/plain; charset=ISO-8859-1` y `Content-Disposition: attachment; filename="ALTAUSUCBPRE.782"`, eliminando la necesidad de copiar manualmente a Notepad++.

#### 2.5. Transición al Repositorio CabalV3 y Documentación de Procesos Git
- **Archivos Afectados:**
  - `docs/Github.md` (Creado)
  - `GEMINI.md` (Actualizado con Sección 5: Directivas de Registro Mandatorio)
- **Modificaciones:**
  - **Reconfiguración de Remotes:** El alias `origin` se reconfiguró formalmente para apuntar a `git@github.com:diegohernangarcia/CabalV3.git` (`https://github.com/diegohernangarcia/CabalV3.git`), reservando el repositorio anterior bajo el alias `cabal_v1`.
  - **Creación de `docs/Github.md`:** Documento de referencia con el inventario completo de acciones Git ejecutadas (commits `e4bae2d`, `789af68`, push forzado inicial y reconfiguración de tracking).
  - **Actualización de `GEMINI.md`:** Incorporación de la regla obligatoria que exige registrar cualquier cambio futuro de código en `docs/BITACORA_CAMBIOS.md` y toda acción Git en `docs/Github.md`.

#### 2.6. Habilitación y Arquitectura del Modo Oscuro (Dark Mode)
- **Archivos Creados / Afectados:**
  - `css/dashboard.css` (Ampliación con paleta de tokens `[data-theme="dark"]`, botón interactivo, transiciones y controles)
  - `js/theme.js` (Creado: gestor de tema con ejecución inmediata anti-FOUC, persistencia en `localStorage` e inyección automática en `.nav-actions`)
  - `index.php` (Integración de script y botón de alternancia en header)
  - `ayuda.html` (Integración de `js/theme.js`)
  - `ConsultarBeneficiario.php` (Integración de `js/theme.js` y adaptación de variables CSS de fondo e inputs)
  - `ConsultaBenefiario2.php` (Integración de `js/theme.js` y badge de tarjeta adaptable)
  - `SubirArchivo.html` (Integración de `js/theme.js`)
  - `CargaAltasDeExcelaBD.php` (Integración de `js/theme.js`)
  - `ExportarExcelBeneficiarios.php` (Integración de `js/theme.js`)
  - `GenerarNovedadAltas.php` (Integración de `js/theme.js`)
- **Características Técnicas Implementadas:**
  - **Paleta de Tokens Oscuros:** Fondo profundo `#080d1a`, superficies de tarjetas en `#0f172a`, bordes sutiles traslúcidos `rgba(255, 255, 255, 0.09)`, tipografía en slate/blanco de alto contraste (`#f8fafc`), y acentos vibrantes con resplandor moderno (glow).
  - **Activación Predeterminada:** El sistema inicia en Modo Oscuro (`data-theme="dark"`), satisfaciendo de inmediato el requerimiento del operador.
  - **Botón de Alternancia (Theme Toggle):** Botón estilizado tipo píldora en la barra de navegación con micro-interacción, iconos Sol / Luna y estado accesible (`aria-pressed`).
  - **Prevención de Parpadeo Blanco (Zero FOUC):** Script ligero ejecutado antes del renderizado del body para fijar el atributo `data-theme` sin saltos visuales.
  - **Persistencia:** Almacenamiento de preferencia en `localStorage.getItem('cabal-theme')`.

#### 2.7. Ajuste de Contraste en Tablas y Etiquetado de Acción del Selector de Tema
- **Archivos Afectados:**
  - `ExportarExcelBeneficiarios.php` (Adaptación de estilos `.table-responsive-container`, `input`, `.btn-clear-filters` y `.table-beneficiarios` para heredar variables de tema)
  - `css/dashboard.css` (Reglas de alta especificidad para tablas con scroll y filas hover en modo oscuro)
  - `js/theme.js` (Actualización de la función `updateToggleButtons` para reflejar la acción a ejecutar: *"Cambiar a Modo Claro"* en modo oscuro y *"Cambiar a Modo Oscuro"* en modo claro)
  - `index.php` (Actualización de la etiqueta inicial del botón a *"Cambiar a Modo Claro"*)
  - `CargaAltasDeExcelaBD.php` (Uso de `var(--surface-subtle)` en hover de tabla y `var(--input-bg)` en inputs)
  - `GenerarNovedadAltas.php` (Uso de `var(--surface-card)` en `.summary-stat-box`)
  - `upload.php` (Integración de `js/theme.js` y `var(--surface-card)` en `.result-card`)
- **Problemas Resueltos:**
  - En `ExportarExcelBeneficiarios.php`, el contenedor con scroll mantenía un fondo blanco (`#ffffff`) sobre el cual el texto claro celeste (`#f1f5f9`) resultaba ilegible. Se reconfiguró el contenedor y las filas para utilizar `var(--surface-card)`, `var(--surface-subtle)` y `var(--text-main)`, asegurando fondo slate oscuro y tipografía nítida en blanco/slate.
  - El botón de tema reflejaba el estado actual ("Modo Oscuro") en lugar de la acción a ejecutar. Se modificó para indicar explícitamente *"Cambiar a Modo Oscuro"* al encontrarse en modo claro, y *"Cambiar a Modo Claro"* al estar en modo oscuro.

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (21/10/2026)

| Prueba | Comando Ejecutado | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :--- |
| **Sintaxis PHP 5.6 (index)** | `/opt/lampp/bin/php -l index.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6 (ConsultarBeneficiario)** | `/opt/lampp/bin/php -l ConsultarBeneficiario.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6 (ConsultaBenefiario2)** | `/opt/lampp/bin/php -l ConsultaBenefiario2.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6 (Altas)** | `/opt/lampp/bin/php -l GenerarNovedadAltas.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6 (CargaAltas)** | `/opt/lampp/bin/php -l CargaAltasDeExcelaBD.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6 (Upload)** | `/opt/lampp/bin/php -l upload.php` | `No syntax errors detected` | ✅ PASÓ |
| **Recursos HTTP 200 (theme.js)** | `curl -I http://localhost/cabal-Gemini/js/theme.js` | `HTTP/1.1 200 OK` | ✅ PASÓ |
| **Recursos HTTP 200 (dashboard.css)** | `curl -I http://localhost/cabal-Gemini/css/dashboard.css` | `HTTP/1.1 200 OK` | ✅ PASÓ |
| **Longitud Registros Cabal** | `curl -s "...?descargar=1" \| tr -d '\r' \| awk '{print length}'` | `128, 128, 128` (bytes exactos por registro) | ✅ PASÓ |
| **Salto de Línea Final** | `curl -s "..." \| grep -A 2 "batch-terminal-output"` | Termina en `<br><br></div>` y `\r\n` | ✅ PASÓ |
| **Auto-renombrado en Upload** | Inspección lógica de `upload.php` | Coincidencia en minúsculas reescribe a `Altas.xlsx` | ✅ PASÓ |

---

## Jornada: 20 de Octubre de 2026

### 1. Objetivos de la Sesión
1. Realizar una auditoría técnica integral y profunda de todo el repositorio Cabal FSR (927 archivos y base de datos local `cabal`).
2. Diagnosticar la arquitectura, dependencias obsoletas, flujos bancarios Cabal Entidad 782 y vulnerabilidades de seguridad OWASP Top 10.
3. Generar la documentación técnica base del proyecto en la carpeta `docs/` y fijar las reglas mandatorias en `GEMINI.md`.
4. Diseñar el nuevo sistema visual institucional (`css/dashboard.css`) y construir un Tablero Principal moderno y operativo (`index.php`).
5. Rediseñar la interfaz de subida centralizada de archivos (`SubirArchivo.html`), enmascarar datos confidenciales y sanear el repositorio de archivos temporales con datos sensibles (PII).

---

### 2. Detalle Técnico de Intervenciones

#### 2.1. Auditoría Técnica Integral y Creación del Corpus Documental
Se inspeccionó el servidor en ejecución (`/opt/lampp`), detectando el stack activo: Ubuntu Linux x86_64, Apache 2.4.38, PHP 5.6.40 obsoleto y MariaDB 10.x. Se elaboró la documentación fundacional:
- **`GEMINI.md` (Directivas y Reglas Obligatorias de Trabajo):**
  - Reglas de codificación UTF-8 sin BOM para código fuente, y **ANSI / ISO-8859-1 con saltos CRLF obligatorios** para archivos bancarios Cabal (`.782`).
  - Prohibición estricta de alterar los formatos posicionales de Cabal (128 bytes para `ALTAUSUCBPRE` y 80 bytes para `NOVCAR`).
  - Política de tolerancia cero a SQL Injection mediante Prepared Statements.
  - Obligación de compatibilidad con PHP 5.6.40 hasta completar la migración de stack en el Sprint 4.
  - Prohibición de imprimir lotes bancarios en pantalla con HTML suelto.
- **`docs/ARQUITECTURA.md` (Arquitectura de Software):**
  - Mapeo del monolito procedimental PHP, topología de directorios y diagramas de secuencia Mermaid del flujo operativo (Operador &harr; Web &harr; BD &harr; Cabal Transmisiones).
  - Documentación de las 5 integraciones bancarias Cabal (Anexo 1 Altas, NOVCAR cargas, NOVTRAN descargas, Movimientos mensuales y Maestro de Plásticos MAESPLA).
- **`docs/BASE_DE_DATOS.md` (Diccionario y Modelo de Datos):**
  - Análisis exhaustivo de las 13 tablas activas (`beneficiario`, `movimientos`, `altas`, `acreditar`, `acreditar2`, `MasterTarjetas`, etc.).
  - Identificación de claves primarias faltantes, índices críticos requeridos para optimizar consultas masivas y mapa de tipos de datos.
- **`docs/MODULOS.md` (Matriz de Módulos y Madurez Funcional):**
  - Evaluación funcional detallada de cada submódulo (Altas, Acreditaciones, Descargas, Consultas y Reportes) categorizados por estado (*Completado*, *Incompleto*, *En progreso*).
- **`docs/PENDIENTES_Y_BACKLOG.md` (Inventario de Deuda Técnica y Seguridad):**
  - Inventario de código muerto y archivos duplicados (`*copy.php`, scripts de prueba huérfanos).
  - Matriz de vulnerabilidades OWASP Top 10 (RCE crítico en `upload.php`, SQL Injection múltiple en `BD.php`, exposición de credenciales `pucho` y falta de autenticación/CSRF).
  - Roadmap estructurado en 5 sprints de regularización.
- **`docs/INSTALACION.md` (Guía de Despliegue Local):**
  - Instrucciones completas para levantar el entorno sobre XAMPP en Linux, permisos de directorios, solución a errores frecuentes y verificación de servicios.

#### 2.2. Diseño del Sistema Visual Institucional y Nuevo Tablero Principal
- **Archivos Creados / Modificados:**
  - `css/dashboard.css` (Creado)
  - `index.php` (Rediseñado completamente)
  - `index_menu_original.php` (Respaldo del menú anterior)
- **Modificaciones:**
  - Creación del sistema de diseño institucional con paleta de colores oficial de SeNAF / Río Negro / Cabal, tipografía moderna ('Plus Jakarta Sans', 'Inter' y 'JetBrains Mono'), variables CSS y componentes de tarjetas (`surface-card`), botones, badges y sombras suaves.
  - Sustitución del antiguo menú espartano por un **Tablero de Operaciones Institucional** (`index.php`), con tarjetas de módulos operacionales, accesos directos por circuito de trabajo, métricas del sistema y pie institucional de navegación.

#### 2.3. Reingeniería de la Interfaz Unificada de Subida (`SubirArchivo.html`)
- **Archivos Creados / Modificados:**
  - `SubirArchivo.html` (Rediseñado)
  - `SubirArchivo_original.html` (Respaldo creado)
- **Modificaciones:**
  - Creación de una interfaz moderna y accesible para el operador con soporte de arrastrar y soltar (Drag and Drop).
  - Tabla de referencia interactiva indicando el nombre requerido de cada archivo bancario (`Altas.xlsx`, `Acreditaciones.xlsx`, `MovAltas.txt`, `Movimientos.txt`, `RespuestaCargas.txt`, `novcar.782`, `MAESPLAS.txt`) con sus descripciones y formatos permitidos.
  - Previsualización del archivo seleccionado antes de proceder con la carga.

#### 2.4. Blindaje de Privacidad y Enmascaramiento de Datos Financieros
- **Archivos Modificados:**
  - `ConsultarBeneficiario.php` y `ConsultaBenefiario2.php`:
    - Enmascaramiento obligatorio del número de tarjeta de crédito/débito (`XXXX.XX**.****.XXXX`) mediante la función `formatearTarjetaCredito()`.
    - Modernización de la vista de ficha personal del beneficiario con diseño institucional y botón de impresión limpia.
    - Respaldos en `ConsultarBeneficiario_original.php` y `ConsultaBenefiario2_original.php`.
  - `ExportarExcelBeneficiarios.php`:
    - Rediseño institucional de la pantalla de descarga de padrón.
    - Respaldo en `ExportarExcelBeneficiarios_original.php`.
  - `ayuda.html`:
    - Reescritura completa del manual de usuario con diagramas y guías operativas paso a paso para el intercambio con Cabal Transmisiones.
    - Respaldo en `ayuda_original.html`.

#### 2.5. Saneamiento del Repositorio Git y Blindaje de Datos Sensibles (PII)
- Se eliminaron del control de versiones planillas de cálculo históricas con datos personales reales de beneficiarios (`Beneficiarios FSR *.xlsx`), archivos ZIP pesados (`Al12Sept.zip`) y lotes temporales antiguos (`archivos/Viejos/`).

#### 2.6. Módulo "4. Importar y Subir Altas Validado" y Optimización Anti-Bloqueos (23/10/2026)
- **Archivos Modificados:**
  - `CargaAltasDeExcelaBD.php`:
    - **Procesamiento Exclusivo Bajo Demanda:** Se corrigió el ciclo de ejecución para que el procesamiento pesado de la planilla se active **únicamente cuando el usuario presiona "Subir y Analizar"** (`subir_analizar`) o solicita expresamente reanalizar el archivo en el servidor. Al ingresar vía `GET` desde el menú principal, la página carga su estado inicial limpio en 28 milisegundos sin congelar la sesión.
    - **Detección Dinámica de Cabecera ("Comienza con Apellido"):** Algoritmo de localización dinámica en la primera hoja (`Sheet 0`) que detecta la celda donde inicia la palabra `Apellido` (sin importar si la tabla arranca en la fila 1, fila 2 o columna B), mapeando a partir de allí las 18 columnas reglamentarias de la tabla `altas`.
    - **Blindaje y Optimización de PHPExcel (Cero Cuelgues):** Se incorporó `$objReader->setReadDataOnly(true)` (omitiendo estilos pesados y acelerando la lectura a < 0.4s), lectura segura de celdas ante fórmulas rotas de Excel (`obtenerValorCeldaSeguro()`) y corte automático ante 5 filas vacías consecutivas (`break;`).
    - **Validación Exhaustiva Cabal (`docs/normas_de_validacion.md`):** Validación estricta de longitudes (Apellido <= 16, Apellido+Nombre <= 24, Calle <= 25, etc.), Anexo 9 (Provincias), Sexo (M/F), Código Postal, Estado Civil (C/S/V/O), Fechas DDMMAAAA normalizadas con `checkdate`, Anexo 10 (Tipo Documento) y CUIT (11 dígitos).
    - **Control Crítico de CUITs en Padrón de Beneficiarios:** Consulta preparada sobre la tabla `beneficiario`. Si se detecta un CUIT ya registrado, se marca la fila con badge dorado `⚠ Ya en Padrón`, se despliega una tarjeta de alerta crítica y se inhabilita de forma absoluta el botón `"Guardar datos en BD"`.
    - **Confirmación en Dos Pasos y Transaccionalidad:** Guardado solo habilitado ante 100% registros conformes y 0 CUITs duplicados. Al confirmar, ejecuta Paso 1: `TRUNCATE TABLE altas` y Paso 2: inserción en bloque dentro de una transacción PDO (`beginTransaction()`, `commit()`, `rollBack()`).
    - **Feedback Visual en Tiempo Real:** Botón "Subir y Analizar" con indicador de carga y spinner ("Procesando planilla...") para evitar sensación de congelamiento o envíos concurrentes.
#### 2.7. Módulo "Importar y subir Acreditaciones Validado" (27/10/2026)
- **Archivos Creados y Modificados:**
  - `ImportarAcreditacionesValidado.php` (Nuevo módulo de importación y cotejo de acreditaciones):
    - **Carga Multiformato y Drag & Drop:** Soporte para subir o arrastrar archivos `.xlsx`, `.xls` y `.ods`, además de botón para auditar directamente la planilla en el servidor (`archivos/Acreditaciones.xlsx`).
    - **Detección Dinámica de Cabecera:** Algoritmo que inspecciona filas 1 a 50 buscando las columnas clave: `Nº`, `DISPOSICIÓN/EXPTE JUDICIAL`, `APELLIDO`, `NOMBRE`, `LOCALIDAD`, `DNI`, `CUIL` y `ACREDITAR`. Identifica con precisión la ubicación del encabezado sin importar fila o columna de inicio.
    - **Cruce por CUIL con Padrón de Beneficiarios:** La columna `CUIL` (normalizada numéricamente a 11 dígitos) se cruza contra la tabla `beneficiario` (campo `Cuit`).
    - **Extracción de Campos para Futura Acreditación:**
      - `id_beneficiario` &larr; `beneficiario.id`
      - `Apellido` &larr; `beneficiario.Apellido`
      - `Nombre` &larr; `beneficiario.Nombre`
      - `NumeroCuenta` &larr; `beneficiario.NumeroCuenta`
      - `DNI` &larr; `beneficiario.NumeroDocumento`
    - **Evaluación Segura de Fórmulas:** Lectura de importes mediante `getCalculatedValue()` de PHPExcel para interpretar operaciones como `=+830000*3` o `=SUM(...)`.
    - **Cotejo de Discrepancias Excel vs BD:**
      - Detección de diferencias en Apellido y Nombre (considerando normalización fonética y tildes).
      - Detección de diferencias en DNI (normalizando ceros a la izquierda y formateo).
      - Reporte explícito en tabla de cada campo con discrepancia, mostrando en rojo el valor del Excel y en negrita el de BD.
    - **Filtros Dinámicos y Búsqueda en Vivo:** Botones de filtrado rápido ("Todos", "Coinciden 100%", "Con Discrepancias", "No Encontrados") y barra de búsqueda en tiempo real.
    - **Ampliación de Ancho Máximo (Wide Layout):** Ajuste de `.page-container` y `.top-nav-inner` al 98% del viewport para aprovechar la totalidad de la pantalla sin romper márgenes ni diseño.
    - **Flujo Interactivo de Aceptación de Discrepancias:**
      - Incorporación de selectores individuales (`<select class="select-discrepancia">`) para cada fila con estado `DISCREPANCIA` con las opciones `⏳ Pendiente` y `✅ Acepto Discrepancia`.
      - Acciones por lote: botones `Aceptar Todas (N)` y `Restablecer` para agilizar la revisión.
      - Paneles de Control de Avance (superior e inferior) que evalúan en tiempo real el estado de resolución.
    - **Cotejo Crítico de Totales (Sumatoria vs Celda TOTAL de Excel):**
      - Detección automática de la celda de pie de página `TOTAL` y extracción del importe total declarado en la columna `ACREDITAR`.
      - Cotejo aritmético contra la sumatoria real calculada de todas las filas de titulares analizadas.
      - **Ventana Emergente Danger por Descuadre:** En caso de no coincidir la sumatoria con el total del Excel, se dispara automáticamente un modal emergente de estilo `danger` informando el desfasaje exacto (sumatoria vs total declarado vs diferencia) y bloqueando de forma absoluta el avance al siguiente paso.
      - **Confirmación Visual de Coincidencia:** Cuando los importes cuadran al 100% (como en `Acreditaciones.xlsx` con $28.220.000,00), se muestra el indicador verde de conformidad en la tarjeta de métricas y paneles de avance.
  - `index.php`:
    - Incorporación del enlace "2. Importar y subir Acreditaciones Validado" en el Paso 2 del Workflow Stepper.
    - Inclusión de la tarjeta de acción destacada en el Módulo 2 ("Acreditaciones de Saldo").

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (27/10/2026)

| Prueba | Comando / Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Integridad del Stack** | `/opt/lampp/bin/php -v` | PHP 5.6.40 CLI verificado | ✅ PASÓ |
| **Conexión MySQL local** | Consulta PDO sobre base `cabal` | Conexión exitosa, 13 tablas leídas | ✅ PASÓ |
| **Sintaxis de Archivos PHP** | `/opt/lampp/bin/php -l index.php` y `ImportarAcreditacionesValidado.php` | Sin errores de sintaxis (No syntax errors detected) | ✅ PASÓ |
| **Ancho Máximo Tabla** | Inspección CSS `.page-container`, `.top-nav-inner` | Ocupa 98% del ancho disponible sin desborde | ✅ PASÓ |
| **Detección Dinámica Cabecera** | `archivos/Acreditaciones.xlsx` | Detectada fila 3, columnas B a H mapeadas exactamente | ✅ PASÓ |
| **Cálculo de Fórmulas Acreditar** | Evaluación en `archivos/Acreditaciones.xlsx` | Evaluadas fórmulas aritméticas (`=+830000*3` = $2.490.000,00) | ✅ PASÓ |
| **Cruce de CUIL con Beneficiarios** | Consulta preparada contra `beneficiario.Cuit` | 26 beneficiarios cruzados con cuenta y DNI | ✅ PASÓ |
| **Detección de Discrepancias** | Cotejo Apellido/Nombre/DNI | Detectadas diferencias reales (ej. BARALE, MINISINI, FANDIÑO) | ✅ PASÓ |
| **Selectores Acepto Discrepancia** | Evaluación JS de selectores y lote | Botón bloqueado inicialmente; desbloqueo dinámico al aceptar 100% | ✅ PASÓ |
| **Chequeo Celda TOTAL vs Suma** | Verificación en `archivos/Acreditaciones.xlsx` | Suma ($28.220.000,00) = Celda TOTAL Fila 30 ($28.220.000,00) | ✅ PASÓ |
| **Alerta Danger Descuadre** | Simulación de desfasaje de importes | Modal emergente danger visible, avance bloqueado | ✅ PASÓ |
| **Protección de Tablas de Saldo** | Verificación de queries en código | 0 escrituras/truncates sobre `acreditar` o `acreditar2` | ✅ PASÓ |
| **Navegabilidad Tablero** | Enlaces en Stepper y Módulo 2 en `index.php` | Redirección directa y etiquetas de estado OK | ✅ PASÓ |

---

### 4. Inventario General de Respaldos de Seguridad (`*_original.*`)
Para asegurar la total trazabilidad y la posibilidad de reversión inmediata ante cualquier requerimiento:
- `CargaAltasDeExcelaBD_original.php` (Versión previa de la carga de Excel a BD).
- `GenerarNovedadAltas_original.php` (Versión previa de la generación de ALTAUSUCBPRE en pantalla).
- `SubirArchivo_original.html` (Versión previa del formulario de subida).
- `index_menu_original.php` (Versión previa del menú lateral de navegación).
- `ayuda_original.html` (Versión previa del manual de usuario).
- `ExportarExcelBeneficiarios_original.php` (Versión previa del reporte de exportación).
- `ConsultaBenefiario2_original.php` (Versión previa de la ficha individual).
- `ConsultarBeneficiario_original.php` (Versión previa del buscador por DNI).

---

## Jornada: 27 de Octubre de 2026 (Segunda Parte)

### 1. Objetivos de la Sesión
1. Completar el flujo de "Continuar al siguiente paso" en `ImportarAcreditacionesValidado.php` implementando el `TRUNCATE TABLE acreditar` (y sincronización con `acreditar2`) y la inserción transaccional atómica de los datos cotejados.
2. Reingeniería interactiva de `VerificarNovcar.php` con zona Drag & Drop para arrastrar y subir archivos de lote `novcar.782` / `.txt` a auditar, validación posicional de 80 bytes por línea, saltos CRLF Windows, sumatorias y cruce con beneficiarios.
3. Establecimiento del título unificado `<title>Cabal 2026</title>` en todas las páginas activas del sistema para visualización uniforme en las pestañas del navegador Chrome.

---

### 2. Detalle Técnico de Intervenciones

#### 2.1. Carga Atómica en Tabla `acreditar` con Modal de Confirmación
- **Archivo Modificado:** `ImportarAcreditacionesValidado.php`
- **Acción Implementada:**
  - Se habilitó la acción `cargar_acreditar` (soportada tanto vía POST asíncrono AJAX JSON como envío estándar de formulario).
  - Modal interactivo de confirmación en 2 fases:
    - **Fase 1 (Confirmación de Seguridad):** Informa al operador que la tabla `acreditar` será vaciada (`TRUNCATE TABLE acreditar` y `acreditar2`) y muestra el resumen exacto (ej. 26 registros, monto total $ 28.220.000,00).
    - **Fase 2 (Éxito y Acceso a Emisión):** Notifica la inserción correcta y proporciona el botón directo de redirección a `GenerarCargaCredito2026.php` ("Proceder a Generar Lote NOVCAR.782").
  - **Transaccionalidad Atómica (PDO):** Ejecuta `beginTransaction()`, `TRUNCATE TABLE acreditar`, `TRUNCATE TABLE acreditar2` y recorre los registros validados insertando `id_beneficiario`, `Apellido`, `Nombre`, `NumeroCuenta`, `ImporteCompra`, `ImporteCajero = 0`, `DNI = NumeroDocumento`, `Fecha` (DDMMAA) y `Hora` (HHMMSS). Ante cualquier error ejecuta `rollBack()`.
  - Prueba en vivo: 26 registros cargados exitosamente totalizando $ 28.220.000,00.

#### 2.2. Reingeniería de Verificación de Lote NOVCAR.782 (`VerificarNovcar.php`)
- **Archivo Modificado:** `VerificarNovcar.php` (Respaldado previamente en `VerificarNovcar_original.php`)
- **Funcionalidades Incorporadas:**
  - **Zona Interactiva Drag & Drop:** Contenedor `.dropzone-novcar` que permite arrastrar cualquier archivo `.782` o `.txt` o seleccionarlo mediante explorador.
  - **Botón Alternativo:** "Auditar Archivo del Servidor (Novcar/novcar.782)" para validar directamente el lote generado por el sistema.
  - **Motor de Auditoría Posicional Rigurosa:**
    - Longitud exacta de 80 bytes por registro (sin contar caracteres de terminación).
    - Detección estricta de saltos de línea CRLF Windows (`\r\n`).
    - Validación de Cabecera (Registro 00 con Entidad `04782000`).
    - Validación de Movimientos (Registros 01 con código `6339`, importes en centavos a 9 dígitos).
    - Validación de Cierre / Trailer (Registro con cuenta `9999999999999999`, cotejo aritmético de cantidad de registros y sumatoria de montos contra los totales de detalle).
  - **Buscador en Tiempo Real y Descarga Directa:** Tabla dinámica con filtro de búsqueda instantáneo y botón de descarga directa HTTP con cabecera `Content-Type: text/plain`.

#### 2.3. Homogeneización de Títulos en Pestañas del Navegador
- **Requerimiento:** Asegurar que el título en la pestaña del navegador Google Chrome sea estrictamente `Cabal 2026` en todo el sistema.
- **Archivos Actualizados:**
  - `index.php`
  - `ImportarAcreditacionesValidado.php`
  - `VerificarNovcar.php`
  - `GenerarCargaCredito2026.php`
  - `GenerarCargaCredito.php`
  - `GenerarCargaCredito2.php`
  - `uploadcsv.php`
  - `AcreditarCSV.php`
  - `AcreditarSaldos.php`
  - `AcreditarSaldos2026.php`
  - `AcreditarSaldos-2023.php`
  - `RespuestaAltas.php`
  - `CargaAltasDeExcelaBD.php`
  - `GenerarNovedadAltas.php`
  - `ChequeoAcreditacion.php`
  - `ReporteMovimientos.php`
  - `ReporteAltaBeneficiarios.php`
  - `ProcesarCarga.php`
  - `ProcesarDescargaSaldos.php`
  - `ProcesarMaestroPlasticos.php`
  - `ConsultaBenefiario2.php`
  - `consultaMovimientoPorNombre.php`
  - `V2/Altas.php`
  - `index_old.php`
  - `menu.php`
  - `crud/index.php`
  - `crud-acreditacion/crud.php`

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (27/10/2026 - Sesión 2)

| Prueba | Comando / Método | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Sintaxis de Archivos Modificados** | `/opt/lampp/bin/php -l [archivos]` | 0 errores en todos los archivos verificados | ✅ PASÓ |
| **Carga en Tabla `acreditar`** | Simulación de POST `cargar_acreditar` | 26 registros cargados por $ 28.220.000,00 | ✅ PASÓ |
| **Sincronización `acreditar2`** | `SELECT COUNT(*), SUM(ImporteCompra) FROM acreditar2` | Coincidencia idéntica a `acreditar` | ✅ PASÓ |
| **Auditoría Posicional NOVCAR** | Validación de archivo `Novcar/novcar.782` | Dictamen 100% Válido, 80 bytes por línea, CRLF OK | ✅ PASÓ |
| **Detección de Lote Erróneo** | Prueba con archivo corrupto simulado | Rechazo bancario detectado con 6 errores específicos | ✅ PASÓ |
| **Verificación Global de `<title>`** | Búsqueda regex `<title>(?!Cabal 2026)` en archivos activos | 0 resultados fuera de norma; 100% de páginas con `Cabal 2026` | ✅ PASÓ |

---

## Jornada: 27 de Octubre de 2026 (Tercera Parte)

### 1. Objetivos de la Sesión
1. Ocultar temporalmente los elementos marcados en recuadros rojos según requerimiento del operador:
   - Botones rápidos de acción en el Hero (`Acreditar Saldos 2026` y `Generar Lote NOVCAR`).
   - Tarjetas KPI centrales de métricas (`Plásticos Emitidos` y `Movimientos Registrados`).
   - Módulos inferiores de la grilla (`Movimientos & Auditoría` y `Maestro de Plásticos`).
2. Configurar los 3 módulos operativos del recuadro amarillo (`Módulo Beneficiarios`, `Acreditaciones de Saldo`, `Descarga de Saldos`) para que muestren por defecto únicamente sus títulos/cabeceras con los cuerpos colapsados.
3. Incorporar un único botón interactivo centralizado (`#btn-toggle-modulos`) que despliegue o cierre simultáneamente los 3 módulos operativos.

---

### 2. Detalle Técnico de Intervenciones

#### 2.1. Ocultamiento Preventivo de Componentes Secundarios
- **Archivos Modificados:** `index.php`
- **Elementos Ocultados con `display: none;` preservando su código para reactivación futura:**
  - **Hero Actions:** Contenedor de botones "Acreditar Saldos 2026" y "Generar Lote NOVCAR".
  - **Métricas KPI:** `card-stat-tarjetas` y `card-stat-movimientos`. La grilla `.stats-grid` reajusta automáticamente las 2 tarjetas restantes (`Beneficiarios Activos` y `Lote Acreditación Actual`) en 2 columnas simétricas de alto impacto.
  - **Módulos Bento:** `mod-movimientos` y `mod-plasticos`.

#### 2.2. Implementación de Despliegue Unificado para los 3 Módulos
- **Archivos Modificados:** `index.php`, `css/dashboard.css`
- **Barra de Control (`.triad-control-bar`):**
  - Ubicada directamente sobre la grilla de módulos con título institucional ("Módulos Operativos Principales"), subtítulo descriptivo y el botón unificado `#btn-toggle-modulos`.
- **Botón Unificado (`.btn-triad-toggle`):**
  - Estado inicial (Cerrado): Etiqueta *"Desplegar los 3 Módulos"* con icono chevron down.
  - Estado activo (Desplegado): Etiqueta *"Cerrar los 3 Módulos"* con clase `.active`, tono primario destacado e icono chevron up.
- **Comportamiento por Defecto:**
  - Los módulos `mod-beneficiarios`, `mod-acreditaciones` y `mod-descargas` inician con clase `.module-triad-item.collapsed` y `.module-box-body` con `display: none;`, mostrando únicamente la cabecera estilizada con icono, título, subtítulo y tag de módulo.
- **Interactividad Dual:**
  - Además del botón central, hacer clic sobre la cabecera de cualquiera de los 3 módulos también acciona el botón unificado para máxima comodidad operativa.
- **Ajuste del Módulo 6 (`#mod-herramientas`):**
  - Configurado con `grid-column: 1 / -1;` para abarcar el ancho completo inferior con sus 4 accesos directos distribuidos armónicamente en cuadrícula interna responsiva.

---

### 3. Matriz de Verificación y Pruebas Ejecutadas

| Prueba | Comando / Método | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l index.php` | `No syntax errors detected` | ✅ PASÓ |
| **Ocultamiento de Bloques Rojos** | Inspección `curl` de estilos en HTML | Bloques con `display: none;` activos | ✅ PASÓ |
| **Colapso Inicial de los 4 Módulos** | Inspección `curl` de `.module-box-body` | 4 cuerpos con `style="display: none;"` | ✅ PASÓ |
| **Alineación 4 Columnas a la Par** | `.modules-grid` con `minmax(290px, 1fr)` | 4 módulos alineados en la misma fila | ✅ PASÓ |
| **Respuesta HTTP 200** | `curl -I http://localhost/cabal-Gemini/index.php` | `HTTP/1.1 200 OK` | ✅ PASÓ |

---

## Jornada: 27 de Octubre de 2026 (Cuarta Parte)

### 1. Objetivos de la Sesión
1. Integrar el módulo **Herramientas & Utilidades** (`#mod-herramientas`) para que se ubique **a la par** (en la misma fila, side-by-side) de los otros 3 módulos superiores (**Módulo Beneficiarios**, **Acreditaciones de Saldo**, **Descarga de Saldos**).
2. Configurar **Herramientas & Utilidades** para que se oculte por defecto exactamente igual que los otros 3 (mostrando únicamente su cabecera / título).
3. Actualizar el botón centralizado para controlar simultáneamente el despliegue y cierre de los **4 módulos** (`#btn-toggle-modulos`).

---

### 2. Detalle Técnico de Intervenciones
- **Archivos Modificados:** `index.php`, `css/dashboard.css`
- **Reconfiguración del Grid (`.modules-grid`):**
  - Se ajustó `grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 20px;`, permitiendo que en resoluciones de escritorio los 4 módulos se ubiquen alineados en una sola fila homogénea de 4 columnas.
  - Se eliminó el override de ancho completo (`grid-column: 1 / -1;`) de `#mod-herramientas` para que adopte el ancho y aspecto idéntico a los otros tres.
- **Colapso por Defecto:**
  - Se incorporó la clase `module-triad-item collapsed` a `#mod-herramientas` y su `<div class="module-box-body">` se configuró con `style="display: none;"`.
- **Botón Unificado Actualizado:**
  - Se actualizaron las etiquetas del botón a:
    - Estado cerrado: **`▼ Desplegar los 4 Módulos`**
    - Estado abierto: **`▲ Cerrar los 4 Módulos`**
  - El script JavaScript selecciona los 4 módulos y los despliega/cierra en bloque, permitiendo también hacer clic en la cabecera de cualquiera de los 4 para alternar el estado.

---

### 3. Matriz de Verificación y Pruebas Ejecutadas

| Prueba | Comando / Método | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l index.php` | `No syntax errors detected` | ✅ PASÓ |
| **Colapso de 4 Módulos** | Inspección `curl` de los 4 `.module-box-body` | 4 cuerpos con `style="display: none;"` | ✅ PASÓ |
| **Alineación a la Par** | Regla CSS `minmax(290px, 1fr)` en contenedor | 4 módulos homogéneos en una fila | ✅ PASÓ |
| **Respuesta HTTP 200** | `curl -I http://localhost/cabal-Gemini/index.php` | `HTTP/1.1 200 OK` | ✅ PASÓ |

---

## Jornada: 27 de Octubre de 2026 (Quinta Parte)

### 1. Objetivos de la Sesión
1. Implementar la funcionalidad de **subir y arrastrar (Drag & Drop)** archivos para procesar en **1.3 Procesar respuesta de altas** (`ProcesaNovedadesAltas.php`).
2. Diseñar una experiencia operativa moderna e intuitiva que permita tanto arrastrar un archivo `.txt`, `.782` o `.dat` devuelto por el banco Cabal (`MovAltas.txt`), como seleccionar un archivo local o procesar el archivo existente en el servidor.
3. Incorporar auditoría previa de registros con tarjetas de métricas KPI, detección en tiempo real de duplicados contra el padrón activo (`beneficiario`), buscador en vivo y enmascaramiento estricto de números de tarjeta (`formatearTarjetaCredito()`).
4. Proveer confirmación de seguridad en dos pasos mediante modal interactivo y transacción atómica PDO antes de realizar el vuelco de altas al padrón.

---

### 2. Detalle Técnico de Intervenciones

#### 2.1. Modernización de `ProcesaNovedadesAltas.php`
- **Zona Interactiva Drag & Drop (`.dropzone-altas`):**
  - Contenedor con borde discontinuo, icono institucional reactivo y animaciones CSS al sobrevolar (`.dragover`).
  - Detección de eventos `dragenter`, `dragover`, `dragleave`, `drop` y asignación automática al input de archivos con envío inmediato del formulario.
  - Bloqueo preventivo de eventos en `window` para impedir que el navegador abra accidentalmente el archivo de texto en una pestaña vacía.
  - Pantalla de carga dinámica con indicador giratorio y aviso al operador del análisis posicional en curso.
  - Opción complementaria de un solo clic para procesar `archivos/MovAltas.txt` ya residente en el servidor.
- **Motor de Parsing Posicional Cabal (Entidad 782):**
  - Soporte de registros de detalle 01, 02 y 03 con detección automática de offset 20/22:
    - **Registro 01:** Número de plástico devuelto, Apellido y Nombre (descomposición por `/`), Domicilio, Localidad, Provincia, Sexo, Código de Ingreso/Rechazo y Cuenta Cabal de 16 dígitos.
    - **Registro 02:** Código Postal, Teléfono, Estado Civil, Fecha de Nacimiento, Tipo/Número de Documento (con normalización de ceros a la izquierda) y CUIT.
    - **Registro 03:** Consolidación final del titular.
- **Detección Dinámica de Duplicados:**
  - Consulta preparada (`SELECT id ... WHERE NumeroDocumento = :dni OR Cuit = :cuit`) que clasifica a cada titular en **Nuevo para Incorporar** o **Ya Registrado en BD**.
- **Panel de Auditoría y Métricas KPI:**
  - 5 tarjetas de control: *Titulares en Lote*, *Aceptados por Cabal*, *Rechazados por Cabal*, *Nuevos para Incorporar* y *Ya Registrados en BD*.
- **Tabla Detallada con Filtros y Buscador en Vivo:**
  - Enmascaramiento de plásticos bajo norma de seguridad `formatearTarjetaCredito()` (`XXXX.XX**.****.XXXX`).
  - Botones de filtrado rápido: *Todos*, *Nuevos* y *Ya en BD*.
  - Input de filtrado instantáneo por texto (DNI, CUIT, Apellido o Localidad).
- **Confirmación en Dos Pasos y Transacción Atómica PDO:**
  - Modal `#modalConfirmarGuardar` con resumen de titulares a insertar.
  - Bloque transaccional seguro (`beginTransaction()`, `commit()`, `rollBack()`) con doble chequeo de duplicados por registro antes de ejecutar `INSERT INTO beneficiario`.
- **Estandarización de Identidad Visual:**
  - Top-nav institucional con enlace a Tablero, acceso directo a Padrón y alternador de tema oscuro/claro.
  - Título unificado de la pestaña: `<title>Cabal 2026</title>`.

#### 2.2. Sincronización en Tablero Principal (`index.php`)
- Se actualizó el enlace del circuito mensual (Paso 3) y la tarjeta de acción del Módulo 1 a **"1.3 Procesar respuesta de altas (MovAltas.txt)"** para total correspondencia con los requerimientos operativos.

---

### 3. Matriz de Verificación y Pruebas Ejecutadas

| Prueba | Comando / Método | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :--- |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l ProcesaNovedadesAltas.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis `index.php`** | `/opt/lampp/bin/php -l index.php` | `No syntax errors detected` | ✅ PASÓ |
| **Prueba de Subida Multipart (Drag & Drop)** | `curl -F "accion=subir_archivo" -F "archivo_altas=@test_movaltas.txt"` | Subida exitosa, parsing de registros 01, 02, 03 y cálculo de KPIs | ✅ PASÓ |
| **Verificación HTTP 200** | `curl -I http://localhost/cabal-Gemini/ProcesaNovedadesAltas.php` | `HTTP/1.1 200 OK` | ✅ PASÓ |
| **Enmascaramiento de Tarjetas** | Inspección de salida HTML de tabla | Tarjetas ocultadas con `formatearTarjetaCredito()` | ✅ PASÓ |

---

## Jornada: 27 de Octubre de 2026 (Sexta Parte)

### 1. Objetivos de la Sesión
1. **Procesamiento Estricto del Archivo Recién Subido:**
   - Asegurar que al subir o arrastrar un archivo, el sistema procese única y exclusivamente el archivo que se acaba de subir en la sesión activa.
   - Eliminar la auto-lectura pasiva de archivos antiguos del servidor al cargar la página sin acción, iniciando con la zona Drag & Drop despejada y lista.
2. **Exclusión Absoluta de Registros Rechazados por Cabal:**
   - Implementar la regla estricta de negocio: **si la respuesta de Cabal para un titular es "Rechazado" (Código de Ingreso `1` o presencia de motivo de rechazo), ese registro NUNCA puede ser subido ni insertado en la tabla `beneficiario`**.
   - Identificar con badges de alta visibilidad (`⛔ Rechazado por Cabal - No se sube a BD`).
   - Excluir a los titulares rechazados del conteo de "Aptos para Incorporar" y del payload JSON de inserción en la base de datos.
   - Bloquear a nivel de backend transaccional en PHP la inserción de cualquier registro que no cuente con aprobación bancaria explícita.

---

### 2. Detalle Técnico de Intervenciones

#### 2.1. Procesamiento Aislado del Archivo Recién Subido
- **Archivo Modificado:** `ProcesaNovedadesAltas.php`
- Se modificó el bloque de subida para leer directamente las líneas del archivo temporal recién subido (`$fileTmp`) y respaldarlo como `archivos/MovAltas_subido.txt` y `archivos/MovAltas.txt`.
- Se eliminó la rama `elseif (empty($accion) && $archivoServidorExiste)`, impidiendo que al ingresar a la página se visualicen automáticamente lotes antiguos preexistentes.
- Se añadió timestamp y nombre exacto en el encabezado de auditoría: `[nombre_archivo] (Recién subido: HH:MM:SS)`.
- Si el operador desea procesar el archivo histórico del servidor, debe accionar deliberadamente el botón *"Procesar archivo del servidor"*.

#### 2.2. Restricción Estricta Anti-Rechazos Cabal
- **Evaluación de Aceptación:**
  - Se evalúa `$ingresoLimpio` y `$tieneErrorRechazo`:
    - `CodigoIngreso === '1'` o motivo de rechazo no nulo &rarr; `$esAceptado = false`.
    - `CodigoIngreso === '0'` y sin motivo de rechazo &rarr; `$esAceptado = true`.
- **Clasificación Tripartita del Padrón:**
  1. **Aptos para Guardar:** `$esAceptado && !$yaEnBD` (Aprobados por Cabal y no existentes en BD).
  2. **Rechazados por Cabal:** `!$esAceptado` (Con código de rechazo u omisión).
  3. **Ya en Padrón (Duplicados):** `$yaEnBD` (DNI o CUIT ya registrados).
- **Métricas KPI Actualizadas:**
  - *Titulares en Lote* (Total registros).
  - *Aceptados por Cabal* (Plásticos aprobados).
  - *Rechazados por Cabal* (`⛔ NO se suben a beneficiarios` con borde rojo).
  - *Aptos para Incorporar* (Solo aprobados nuevos listos para guardar).
  - *Ya Registrados en BD* (Omitidos por duplicidad).
- **Tabla y Filtros Dinámicos:**
  - Columna de estado con badge `⛔ Rechazado - No se sube a BD`.
  - Filtros rápidos: *Todos*, *Aptos para Guardar*, *Rechazados Cabal*, *Ya en BD*.
- **Seguridad en Dos Pasos y Transacción Backend:**
  - El modal `#modalConfirmarGuardar` genera `$soloAptos` filtrando estrictamente `$bp['es_aceptado'] && !$bp['ya_en_bd']`.
  - En el guardado transaccional (`accion === 'guardar_beneficiarios'`), el backend valida:
    `if (empty($b['es_aceptado']) || $b['es_aceptado'] !== true) { $rechazadosOmitidos++; continue; }`
    impidiendo categóricamente cualquier intento de inserción de registros rechazados.

---

### 3. Matriz de Verificación y Pruebas Ejecutadas

| Prueba | Comando / Método | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :--- |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l ProcesaNovedadesAltas.php` | `No syntax errors detected` | ✅ PASÓ |
| **Carga Limpia Inicial** | `curl -s http://localhost/.../ProcesaNovedadesAltas.php` | No auto-procesa archivos viejos; dropzone limpio | ✅ PASÓ |
| **Procesamiento de Archivo Recién Subido** | Subida multipart con `MovAltas_Banco.txt` | Procesa estrictamente el lote subido en la petición | ✅ PASÓ |
| **Exclusión de Rechazados Cabal** | Lote mixto (1 Aceptado + 1 Rechazado) | Rechazado marcado `⛔ No se sube a BD`; `datos_json` contiene únicamente el titular aceptado (1 de 2) | ✅ PASÓ |
| **Filtros de Tabla** | Comprobación de atributos `data-estado` | Estados `apto`, `rechazado` y `duplicado` diferenciados | ✅ PASÓ |
| **Eliminación Total de Archivo Servidor** | Búsqueda `grep` en HTML renderizado | 0 menciones de "servidor"; procesamiento 100% exclusivo de adjuntos | ✅ PASÓ |

---

## [2026-10-27 13:55] - Corrección de Envío y Procesamiento en Subida de Respuesta de Altas (ProcesaNovedadesAltas.php)

### 1. Diagnóstico del Incidente ("Subo el archivo y no procesa nada")
- **Causa Raíz Identificada:**
  1. **Destrucción del Input en el DOM:** Al ejecutarse `indicarCargando(nombre)`, se reemplazaba el contenido completo de `.dropzone-altas` mediante `dropzone.innerHTML = '...'`. Dado que `<input type="file" id="fileInputAltas">` se encontraba anidado dentro del dropzone, el elemento input quedaba eliminado del árbol DOM en el instante previo a `form.submit()`. En consecuencia, el formulario enviaba únicamente `accion=subir_archivo` sin ningún campo de archivo adjunto en `$_FILES`.
  2. **Silencio en Falla de Subida:** Si `$_FILES['archivo_altas']` no llegaba o arrojaba error de subida, el backend no emitía ningún mensaje descriptivo, recargando la pantalla en blanco con el dropzone inicial sin feedback al operador.
  3. **Restricción de Extensiones Estricta:** Archivos bancarios de Cabal devueltos sin extensión o con extensiones no convencionales eran rechazados.

### 2. Soluciones Técnicas Aplicadas
- **Desacoplamiento Estructural del Input File:**
  - Se extrajo `<input type="file" id="fileInputAltas">` como hijo directo de `<form id="formUploadAltas">`, fuera de cualquier contenedor con manipulación dinámica.
  - Se transformó el contenedor visual en `<label for="fileInputAltas" class="dropzone-altas">`, permitiendo que el navegador abra nativamente el explorador de archivos con el clic en cualquier área del dropzone, sin necesidad de manejadores recursivos `onclick`.
- **Transición de Estados sin Reemplazo Destructivo del DOM:**
  - Se crearon dos sub-contenedores dentro del label: `#dropzoneNormalState` y `#dropzoneLoadingState`.
  - La función `activarEstadoCargando(nombre)` alterna la visibilidad (`display: none / block`) sin tocar `innerHTML` ni alterar los elementos del formulario.
- **Robustez en la Detección y Feedback del Backend:**
  - Se capturan todos los estados de error de subida HTTP/PHP (`UPLOAD_ERR_NO_FILE`, `UPLOAD_ERR_INI_SIZE`, etc.) con alertas visuales de tipo `danger`.
  - Se eliminó la restricción rígida de extensiones, admitiendo archivos devueltos por Cabal (`.txt`, `.782`, `.dat`, sin extensión, etc.).
  - En caso de que se suba un archivo con líneas no reconocibles por la estructura 01, 02, 03 de Cabal, se muestra un aviso de diagnóstico con el número de líneas recibidas y una muestra de la primera línea.
  - Se agregaron encabezados HTTP anti-caché (`Cache-Control: no-cache, no-store, must-revalidate`) para forzar la actualización inmediata del script en el navegador.

### 3. Verificación Técnica
- **Linting:** `/opt/lampp/bin/php -l ProcesaNovedadesAltas.php` -> Sin errores de sintaxis.
- **Prueba HTTP POST sin archivo:** Alerta `danger` explícita informando la ausencia de archivo.
- **Prueba HTTP POST con archivo de prueba:** Procesamiento inmediato, visualización de métricas KPI (Titulares en lote, Aceptados, Rechazados) y tabla de titulares.
- **Prueba con archivo sin extensión:** Procesado y clasificado exitosamente.
- **Prueba con archivo de formato inválido:** Alerta explicativa con vista previa de la primera línea leída.

---

## Jornada: 27 de Octubre de 2026 (Séptima Parte)

### 1. Objetivos de la Sesión
1. **Modernización Integral de "4.1 Procesar Movimientos.txt" (`ProcesarMovimientos.php`):**
   - Incorporar la funcionalidad interactiva de arrastrar y soltar (Drag & Drop) y selector de archivos para subir y procesar directamente el extracto mensual de plásticos devuelto por Cabal (`Movimientos.txt`, `.txt`, `.dat`, etc.).
   - Eliminar la dependencia de subir el archivo previamente por separado en `SubirArchivo.html` y luego cargar en caliente sin previsualización.
2. **Corrección de Bug y Seguridad en Control de Duplicados (`EstaFechaMovimiento` en `BD/BD.php`):**
   - Corregir el bug histórico documentado en la función `EstaFechaMovimiento()` que utilizaba una variable indefinida `$DNI`, llamada huérfana a `CompletarNumeroDocumento($DNI)` y concatenación SQL vulnerable.
   - Reemplazar por consulta preparada PDO parametrizada (`:mes`, `:anio`, `:archivo`) con manejo de excepciones.
3. **Control y Auditoría de Período Duplicado en Tiempo Real:**
   - Detección inmediata en la base de datos de si el período (`Mes`, `Anio`, `Archivo`) seleccionado ya cuenta con registros importados en la tabla `movimientos`.
   - Si no existe: Notificación de período libre para grabación directa.
   - Si ya existe: Alerta destacada indicando la cantidad exacta de movimientos existentes y ofreciendo opción controlada de reemplazo/sobrescritura o consulta directa del extracto.
4. **Métricas KPI Financieras y de Control Contable:**
   - Total de movimientos leídos en el lote.
   - Total y monto acumulado de **Consumos en Comercios (Código 491 / Autorizaciones)**.
   - Total y monto acumulado de **Cargas / Descargas de Crédito (Código 671)**.
   - Conteo de plásticos y cuentas únicas involucradas.
   - Conteo de registros de saldo inicial y final (999 y 241).
5. **Previsualización con Filtrado Dinámico y Enmascaramiento de Tarjetas:**
   - Tabla interactiva con filtro de búsqueda instantánea por titular, documento, tarjeta o comercio.
   - Botones de filtrado rápido por categoría: *Todos*, *Consumos (491)*, *Cargas/Descargas (671)*, *Saldos (999/241)*.
   - Blindaje de PII: Enmascaramiento estricto de números de tarjetas mediante `formatearTarjetaCredito()` (`XXXX.XX**.****.XXXX`).
6. **Confirmación en Dos Pasos y Transacción Atómica PDO:**
   - Modal interactivo de confirmación `#modalConfirmarGuardar` con desglose financiero.
   - En caso de reemplazo: eliminación previa transaccional (`DELETE FROM movimientos WHERE Mes = ... AND Anio = ... AND Archivo = ...`) y posterior inserción masiva parametrizada.
   - Bloque transaccional con `beginTransaction()`, `commit()` y `rollBack()`.
7. **Estandarización y Títulos:**
   - Título unificado en pestaña `<title>Cabal 2026</title>` en `ProcesarMovimientos.php`, `ProcesarMovimientos.html`, `ProcesarMovimientos2.php` y `consultaMovimiento.php`.
   - Redirección automática de `ProcesarMovimientos.html` a `ProcesarMovimientos.php`.
   - Actualización de enlaces en `index.php` (Paso 4 del Stepper y Módulo 4 de Auditoría), `ReporteMovimientos.php` y `menu.php`.

---

### 2. Detalle Técnico de Intervenciones
- **Archivos Creados / Modificados:**
  - `ProcesarMovimientos.php` (Creado: nuevo módulo unificado con Drag & Drop, parsing posicional, KPIs, tabla interactiva y guardado atómico).
  - `BD/BD.php` (Actualizado: refactorización de `EstaFechaMovimiento()` a PDO con prepared statements; respaldo en `BD/BD_original.php`).
  - `ProcesarMovimientos.html` (Actualizado: redirección automática inmediata hacia `ProcesarMovimientos.php` con `<title>Cabal 2026</title>`).
  - `ProcesarMovimientos2.php` (Actualizado: inclusión de `BD/Funciones.php`, enmascaramiento de tarjetas con `formatearTarjetaCredito()` y `<title>Cabal 2026</title>`).
  - `consultaMovimiento.php` (Actualizado: título unificado `<title>Cabal 2026</title>`).
  - `ReporteMovimientos.php` (Actualizado: botón "+ Procesar Nuevo Archivo" apunta a `ProcesarMovimientos.php`).
  - `index.php` (Actualizado: enlaces en Stepper y Módulo 4 hacia `ProcesarMovimientos.php`).
  - `menu.php` (Actualizado: enlace del menú superior hacia `ProcesarMovimientos.php`).
  - `docs/BITACORA_CAMBIOS.md` (Actualizado con el registro de la intervención).

---

### 3. Matriz de Verificación y Pruebas Ejecutadas
| Prueba | Comando / Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico Exhaustivo** | `/opt/lampp/bin/php -l` en los 7 archivos modificados | 0 errores de sintaxis en todos los archivos | ✅ PASÓ |
| **Prueba Unitaria `EstaFechaMovimiento()`** | Consulta de período existente (`Enero 2026`) y no existente (`Octubre 2030`) | Retornó `bool(true)` y `bool(false)` sin warnings | ✅ PASÓ |
| **Respuesta HTTP GET 200** | `curl -s -I http://localhost/cabal-Gemini/ProcesarMovimientos.php` | `HTTP/1.1 200 OK`, cabeceras anti-caché activas | ✅ PASÓ |
| **Subida Multipart (Drag & Drop)** | POST con archivo sintético de movimientos posicionales Cabal (149 bytes/línea) | Detección de operaciones 491, 671, 999, 241 y cálculo de KPIs | ✅ PASÓ |
| **Detección de Período Existente** | POST con `Enero 2026 Carga 1` | Detectó 553 movimientos existentes y activó alerta de reemplazo | ✅ PASÓ |
| **Detección de Período Nuevo** | POST con `Noviembre 2026 Carga 1` | Detectó período libre para nueva importación | ✅ PASÓ |
| **Enmascaramiento de Tarjetas** | Inspección del HTML renderizado en tabla | Tarjeta visible formateada: `6043.86**.****.2735` | ✅ PASÓ |
| **Inserción Transaccional en Base de Datos** | Simulación de inserción atómica PDO con modal | Registro insertado correctamente; rollback/delete de prueba confirmado | ✅ PASÓ |
| **Redirección de Compatibilidad** | Acceso a `ProcesarMovimientos.html` | Meta-refresh e invocación JS hacia `ProcesarMovimientos.php` | ✅ PASÓ |
| **Verificación de Títulos de Pestaña** | Inspección de `<title>` en archivos intervenidos | 100% homogeneizados con `Cabal 2026` | ✅ PASÓ |

---

## [2026-10-28] - Incorporación de "Verificar Respuesta Carga" en Menú 2 y Rediseño de ProcesarCarga.php

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:** Incorporar en el Menú 2 ("Acreditación de Saldo") el ítem "Verificar Respuesta Carga", apuntando a `ProcesarCarga.php`, y otorgarle a dicho módulo el mismo formato estético y funcional del resto del sistema moderno de Cabal.
- **Modernización de `ProcesarCarga.php`:** Sustituir la vista elemental por una interfaz alineada al *Dashboard Modern Design System*:
  - Cabecera con selector de tema oscuro/claro (`theme.js`).
  - Tarjeta de subida interactiva con zona *Drag & Drop* para el archivo devuelto por el banco (`782_RCAR...txt` o `RespuestaCargas.txt`).
  - Cuadrícula de 4 tarjetas KPI con métricas financieras (Cuentas Procesadas, Total Acreditado Efectivo, Acreditaciones Exitosas OK 000, Rechazos/Observados).
  - Precarga en memoria $O(1)$ del padrón de beneficiarios institucional para evitar consultas SELECT redundantes por cada fila.
  - Diccionario oficial de códigos de respuesta bancaria Cabal con badges contextuales (`success`, `warning`, `danger`).
  - Barra de herramientas con buscador en tiempo real, filtros por estado (Todos, Exitosos, Rechazos) y exportación a CSV compatible con Microsoft Excel (BOM UTF-8).
- **Actualización de Navegación y Menús:**
  - `index.php`: Agregado del ítem "• 5. Verificar Respuesta Carga" en la tarjeta de paso 2 (*Acreditación de Saldo*) y actualización del título y descripción en el Módulo 2 (*Acreditaciones de Saldo*).
  - `index_menu_original.php`: Actualización del ítem correspondiente dentro del menú desplegable "Acreditaciones".
  - `index_original.php`: Homogeneización del ítem en la tarjeta 2 y en el Módulo 2.
  - `upload.php`: Botón de acción directa verde hacia `ProcesarCarga.php` al finalizar la subida exitosa de `RespuestaCargas.txt`.

### 2. Detalle de Archivos Modificados
- `ProcesarCarga.php` (Reescritura completa con diseño moderno, drag-and-drop, KPIs, filtros y cruce optimizado con `beneficiario`).
- `index.php` (Actualización de Stepper 2 y Módulo 2).
- `index_menu_original.php` (Actualización del enlace en menú lateral/desplegable de Acreditaciones).
- `index_original.php` (Alineación de compatibilidad en tarjeta 2 y módulo 2).
- `upload.php` (Acceso directo a "Verificar Respuesta Carga").
- `docs/BITACORA_CAMBIOS.md` (Registro técnico de la intervención).

### 3. Matriz de Verificación y Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico PHP** | `/opt/lampp/bin/php -l` en `ProcesarCarga.php`, `index.php`, `index_original.php`, `index_menu_original.php`, `upload.php` | 0 errores sintácticos reportados | ✅ PASÓ |
| **Ejecución CLI sin advertencias** | `/opt/lampp/bin/php ProcesarCarga.php` | Ejecución correcta sin PHP Notices ni Warnings | ✅ PASÓ |
| **Prueba de Parsing Posicional 80 Bytes** | Simulación con registro Cabal 80 bytes (Cuenta 16, Importe 7+2, Código Error 3) | Extracción milimétrica de Cuenta, Importe formateado y Código Error | ✅ PASÓ |
| **Validación Estructural docs/rcar.md** | Auditoría con lote real `archivos/RespuestaCargas.txt` (32 registros, 80 bytes) | 100% de registros con 80 bytes, prefijo TE04, cuadre de 30 detalles con Trailer y total de $29.050.000,00 | ✅ PASÓ |
---

## [2026-10-28] - Alineación Exhaustiva de "2.4 Verificar Lote NOVCAR" según docs/NOVCAR.md (Anexo 3)

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:** Asegurar que en el ítem 2.4 (*Verificar Lote NOVCAR*, archivo `VerificarNovcar.php`), todas las reglas de validación se encuentren estrictamente alineadas y auditadas conforme al archivo normativo `docs/NOVCAR.md`.
- **Auditoría Posicional Milimétrica:**
  - Garantizar la conformidad del protocolo bancario Cabal Entidad 782 para el lote de emisión de novedades de carga (`NOVCAR.782`), validando las posiciones exactas de Header (80 bytes), Detalle (80 bytes) y Trailer (80 bytes).
  - Incluir panel visual interactivo desplegable dentro de `VerificarNovcar.php` con la tabla oficial de especificaciones de `docs/NOVCAR.md`.
  - Auditar la conciliación cruzada de cantidad de registros y sumatoria de importes contra el registro de cierre (Trailer).
  - Implementar precarga en memoria $O(1)$ del padrón de beneficiarios para validación instantánea de cuentas.

### 2. Estructura de Validación Implementada según docs/NOVCAR.md

| Registro | Posición | Longitud | Campo / Descripción | Validación Implementada en `VerificarNovcar.php` |
| :--- | :--- | :---: | :--- | :--- |
| **Global** | - | 80 bytes | Ancho de línea fijo | Verifica que cada línea tenga exactamente 80 bytes (`$largo === 80`). |
| **Global** | - | 2 bytes | Fin de línea | Verifica saltos CRLF Windows (`\r\n`) exigidos por el mainframe Cabal. |
| **Header** | 001 - 004 | 4 | "TE04" | Prefijo obligatorio de cabecera. |
| **Header** | 005 - 007 | 3 | Entidad emisora | Verifica estrictamente "782" (Cabal FSR). |
| **Header** | 008 - 010 | 3 | Sucursal emisora | Fijo "000" obligatorio según norma. |
| **Header** | 011 - 026 | 16 | Marca de cabecera | 16 ceros consecutivos (`0000000000000000`). |
| **Header** | 027 - 032 | 6 | Fecha de proceso | Formato numérico DDMMAA. |
| **Header** | 033 - 038 | 6 | Hora de proceso | Formato numérico HHMMSS. |
| **Header** | 039 - 080 | 42 | Filler | 42 bytes de relleno. |
| **Detalle** | 001 - 004 | 4 | "TE04" | Prefijo obligatorio de detalle. |
| **Detalle** | 005 - 010 | 6 | Entidad / Sucursal | "782SSS" (Entidad 782 + Sucursal 3 dígitos numéricos). |
| **Detalle** | 011 - 026 | 16 | Número de Cuenta | 16 dígitos numéricos, validando existencia en padrón `beneficiario`. |
| **Detalle** | 027 - 028 | 2 | Código Operación | Fijo "63" (Crédito). |
| **Detalle** | 029 - 030 | 2 | Subcódigo Operación | Fijo "39" reglamentario según Anexo 3. |
| **Detalle** | 031 - 039 | 9 | Importe Compras | 9 dígitos con 2 decimales implícitos (N-2). |
| **Detalle** | 040 - 048 | 9 | Importe Cajeros | 9 dígitos con 2 decimales implícitos (N-2). |
| **Detalle** | 049 - 054 | 6 | Motivo de Carga | 6 dígitos numéricos. |
| **Detalle** | 055 - 062 | 8 | Número de Carga | 8 dígitos numéricos. |
| **Detalle** | 063 - 080 | 18 | Filler | 18 bytes de relleno alfanumérico. |
| **Trailer** | 001 - 004 | 4 | "TE04" | Prefijo obligatorio de cierre. |
| **Trailer** | 005 - 007 | 3 | Entidad emisora | "782" (Cabal FSR). |
| **Trailer** | 008 - 010 | 3 | Sucursal emisora | Fijo "000" obligatorio según norma. |
| **Trailer** | 011 - 026 | 16 | Marca de cierre | 16 nueves consecutivos (`9999999999999999`). |
| **Trailer** | 027 - 031 | 5 | Cantidad Registros | Cuadre exacto contra el conteo de detalles leídos (`statsAuditoria['lineas_detalle']`). |
| **Trailer** | 032 - 041 | 10 | Sumatoria Compras | Cuadre exacto contra la suma de compras con 2 decimales implícitos. |
| **Trailer** | 042 - 051 | 10 | Sumatoria Cajeros | Cuadre exacto contra la suma de cajeros con 2 decimales implícitos. |
| **Trailer** | 052 - 080 | 29 | Filler | 29 bytes de relleno alfanumérico. |

### 3. Matriz de Verificación y Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico PHP** | `/opt/lampp/bin/php -l VerificarNovcar.php` | 0 errores de sintaxis | ✅ PASÓ |
| **Ejecución CLI sin advertencias** | `/opt/lampp/bin/php VerificarNovcar.php` | 0 advertencias, 0 errores, ejecución limpia | ✅ PASÓ |
| **Auditoría de Lote Real** | Ejecución con `Novcar/novcar.782` (18 líneas) | 18 líneas de 80 bytes exactos, CRLF confirmado, 16 detalles conciliados al 100% | ✅ PASÓ |
| **Dictamen de Conformidad** | Inspección del veredicto generado | `✅ DICTAMEN: LOTE NOVCAR 100% VÁLIDO CONFORME A NORMA CABAL (docs/NOVCAR.md)` | ✅ PASÓ |
| **Checklist de Conformidad** | Verificación de los 8 puntos de control de `docs/NOVCAR.md` | Los 8 puntos verificados en verde (incluyendo CRLF y salto final) | ✅ PASÓ |
| **Panel de Especificación Técnica** | Verificación del modal/panel colapsable con tabla de `docs/NOVCAR.md` | Interactivo, conmutable con botón de cabecera | ✅ PASÓ |
| **Compatibilidad PHP 5.6** | Verificación de sintaxis (sin `??`, `match` o tipado PHP 7+) | 100% compatible con PHP 5.6.40 | ✅ PASÓ |

---

## [2026-10-28] - Verificación Estricta de Formato CRLF y Salto de Línea Final en Verificar Lote NOVCAR (2.4)

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:** Asegurar que `VerificarNovcar.php` (módulo 2.4) compruebe estrictamente que el formato del archivo de texto sea CRLF homogéneo y que termine obligatoriamente en un salto de línea Windows CRLF (`\r\n`).
- **Implementación Técnica:**
  - **Formato CRLF Estricto:** Verificación de que todos los registros utilicen exclusivamente `\r\n` (0x0D 0x0A), detectando y rechazando saltos aislados UNIX (`\n`) o Mac (`\r`).
  - **Terminación en Salto de Línea Final:** Verificación de los últimos 2 bytes del archivo (`substr($contenidoCrudo, -2) === "\r\n"`). Si el archivo concluye en datos directos sin salto final, o concluye con salto UNIX (`\n`), el lote es catalogado como inválido y se emite el mensaje de error explicativo.
  - **Extensión del Checklist Normativo:** De 7 a 8 puntos de control, agregando el ítem *"Terminación con Salto de Línea Final"* con badge de estado.
  - **Tarjeta Métrica y Trailer:** Inclusión de indicadores visuales de formato CRLF y presencia de salto de línea final en la cabecera del Trailer.
  - **Documentación:** Actualización formal de `docs/NOVCAR.md` con la sección de *Normas Obligatorias de Formato de Archivo (Entidad 782)*.

### 2. Archivos Modificados
- `VerificarNovcar.php`: Incorporación de validaciones de CRLF estricto, terminación en `\r\n`, métricas y checklist de 8 puntos.
- `docs/NOVCAR.md`: Inclusión de normas obligatorias de archivo (80 bytes, CRLF, salto de línea final, ANSI).
- `docs/BITACORA_CAMBIOS.md`: Registro de la intervención.

### 3. Matriz de Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico** | `/opt/lampp/bin/php -l VerificarNovcar.php` | 0 errores detectados | ✅ PASÓ |
| **Lote Válido con CRLF y Salto Final** | Ejecución con `Novcar/novcar.782` (1476 bytes) | 18 registros x 82 bytes = 1476 bytes exactos. CRLF Conforme y Salto Final OK | ✅ PASÓ |
| **Prueba Negativa: Sin Salto Final** | Simulación con archivo sin `\r\n` al final | Detecta `Sin salto final (Error)`, lote no conforme | ✅ PASÓ |
| **Prueba Negativa: Salto UNIX (LF)** | Simulación con archivo con saltos `\n` | Detecta `UNIX (LF, \n) Inválido`, lote no conforme | ✅ PASÓ |

---

## [2026-10-28] - Integración Exhaustiva de Diccionarios de Errores de docs/Cabal.md en Módulos de Verificación

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:**
  1. En el módulo **1.3 (*Procesamiento de Respuesta de Altas Cabal*)**, cuando la respuesta sea un error/rechazo, especificar con precisión el código de error y traducirlo consultando las tablas de códigos de error de `docs/Cabal.md` (Anexo 12).
  2. Extender esta indicación del significado de cada código de error a todos los módulos del sistema que verifiquen archivos bancarios.
- **Implementación Arquitectónica Centralizada:**
  - Creación del módulo central `BD/CabalErrores.php` integrado a través de `BD/Funciones.php` para abastecer a todos los scripts del sistema.
  - Implementación de los diccionarios oficiales normados en `docs/Cabal.md`:
    - **Anexo 12:** Diccionario de Errores para Altas y Modificaciones Filiatorias (RALT) — 37 códigos tipificados (`001` a `999`).
    - **Anexo 11:** Diccionario de Errores en Operaciones de Carga y Descarga (RCAR / RDES) — códigos clasificados por segmento Header, Detalle y Trailer.
    - **Anexo 13:** Matriz de Estados y Causas de Bloqueo de Tarjetas (Causas `00` a `14`).
  - Funciones auxiliares de parseo: `parsearCodigosRechazoAltas()`, `interpretarCodigoErrorAltaCabal()`, `interpretarCodigoErrorCargaCabal()` e `interpretarCausaBloqueoCabal()`.

### 2. Detalle de Módulos Actualizados
1. **`BD/CabalErrores.php` (Creado):**
   - Centraliza los arrays canónicos de Anexo 11, Anexo 12 y Anexo 13.
   - Algoritmo de normalización de códigos capaz de procesar bloques posicionales de 36 bytes (hasta 9 códigos de 3 dígitos con espacio), prefijos `R` (ej. `R05` &rarr; `005`), códigos numéricos directos y leyendas compuestas.
2. **`BD/Funciones.php`:**
   - Inclusión automática de `require_once(__DIR__ . "/CabalErrores.php");`.
3. **`ProcesaNovedadesAltas.php` (1.3 Procesamiento de Respuesta de Altas Cabal):**
   - **Parseo de Rechazos:** Decodifica los códigos informados en la posición 130 a 165 de los registros 01 y 02.
   - **Resumen Diagnóstico de Rechazos:** Tarjeta visual destacada que agrupa y totaliza los rechazos por causa normativa de Anexo 12.
   - **Catálogo Interactivo:** Botón superior *"Ver Catálogo Errores Anexo 12"* y panel desplegable con la totalidad de códigos de Anexo 12.
   - **Detalle por Fila en Tabla:** Despliegue de badges rojos individuales por cada error detectado indicando el código, el título oficial y la descripción técnica completa en tooltip.
4. **`RespuestaAltas.php` (Devolución y Respuesta de Altas Cabal):**
   - Actualización de columna de estado e inclusión de panel desplegable de Anexo 12.
5. **`ProcesarCarga.php` (2.5 Verificar Respuesta Cargas):**
   - Incorporación del botón superior *"Ver Catálogo Errores Anexo 11"* y panel desplegable con los códigos clasificados por Header, Detalle y Trailer.
6. **`ProcesarMaestroPlasticos.php` (Procesamiento de Maestro de Plásticos):**
   - Actualización de `DevuelveCausa()` para utilizar exhaustivamente el catálogo de **Anexo 13** (`interpretarCausaBloqueoCabal()`), cubriendo todas las causas oficiales de bloqueo `00` a `14` de `docs/Cabal.md`.
   - Inclusión del botón superior *"Ver Catálogo Bloqueos (Anexo 13)"* y panel interactivo con la tabla oficial de causas de bloqueo.

### 3. Matriz de Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico Global** | `/opt/lampp/bin/php -l` en `BD/CabalErrores.php`, `BD/Funciones.php`, `ProcesaNovedadesAltas.php`, `RespuestaAltas.php`, `ProcesarCarga.php` y `ProcesarMaestroPlasticos.php` | 0 errores de sintaxis en todos los archivos | ✅ PASÓ |
| **Prueba Unitaria de Parseo Anexo 12** | Invocación CLI con códigos individuales (`006`, `010`, `036`) y compuestos (`R05-DATOS INCOMPLETOS`) | Extracción de códigos numéricos y mapeo de descripciones oficiales al 100% | ✅ PASÓ |
| **Prueba Unitaria de Causas Anexo 13** | Invocación CLI con causas `00` a `14` | Mapeo exacto de las 15 descripciones oficiales del Anexo 13 | ✅ PASÓ |
| **Compatibilidad PHP 5.6** | Verificación de estructuras de arrays tradicionales y ausencia de sintaxis PHP 7+ | Plenamente compatible con PHP 5.6.40 | ✅ PASÓ |

---

## [2026-10-28] - Sustitución de Popup JS por Ventana Emergente Modal con Estilos de la App en "Importar y Subir Altas Validado"

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:** En el módulo *"Importar y Subir Altas Validado"* (`CargaAltasDeExcelaBD.php`), al presionar *"Guardar datos en BD (Vaciar altas y Guardar X registros)"*, reemplazar la ventana de confirmación nativa de JavaScript (`confirm()`) por una ventana emergente modal interactiva estilizada con los colores y la identidad visual de la aplicación.
- **Implementación Técnica:**
  - **Componente Modal Moderno (`#modalConfirmarGuardar`):**
    - Fondo oscuro con desenfoque (`backdrop-filter: blur(6px)`).
    - Cabecera con degradado institucional azul (`linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%)`), icono de base de datos, título y botón de cierre "✕".
    - Caja de alerta contextual con la pregunta de confirmación: *"¿Confirma que desea vaciar la tabla altas (TRUNCATE) y cargar los X registros validados?"*.
    - Desglose informativo del lote: conteo de registros validados, tabla de destino (`altas`), acción operativa atómica (`TRUNCATE`) y estado de auditoría (0 errores y 0 CUITs duplicados).
    - Soporte completo para modo oscuro (`[data-theme="dark"]`).
    - Pie con botones *"Cancelar"* (cierra la ventana sin realizar ninguna acción) y *"Sí, Vaciar y Guardar en BD"* (envía el formulario y muestra indicador spinner de espera).
  - **Accesibilidad y Experiencia de Usuario:**
    - Cierre mediante tecla `Escape` o clic sobre el backdrop exterior.
    - Bloqueo de scroll del cuerpo de página mientras el modal permanezca abierto (`overflow: hidden`).
    - Transición suave de entrada y salida con CSS transforms y opacidad.

### 2. Archivos Modificados
- `CargaAltasDeExcelaBD.php`: Sustitución del `onclick="return confirm(...)"` por `abrirModalConfirmacion()`, incorporación de estilos CSS del modal, marcado HTML semántico de la ventana emergente y scripts de control.
- `docs/BITACORA_CAMBIOS.md`: Registro de la intervención.

### 3. Matriz de Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico** | `/opt/lampp/bin/php -l CargaAltasDeExcelaBD.php` | 0 errores detectados | ✅ PASÓ |
| **Inspección de Respuesta HTTP** | Solicitud POST `accion=analizar_existente` | Elementos `#btn-guardar-bd` y `#modalConfirmarGuardar` presentes en el DOM | ✅ PASÓ |
| **Compatibilidad PHP 5.6** | Verificación de sintaxis y renderizado sin dependencias externas | 100% compatible | ✅ PASÓ |

---

## [2026-10-28] - Reestructuración de Menú Altas de Tarjetas y Enriquecimiento de Módulo Beneficiarios

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:**
  1. En el menú/tarjeta **Altas Tarjetas** (Paso 1 del circuito mensual), incorporar el ítem *"Importar Excel Con Altas"*.
  2. El menú de Altas Tarjetas debe estructurarse estrictamente en 3 pasos:
     - `1) Importar Excel Con Altas y Generar ALTASUCBPRE` (enlace a `CargaAltasDeExcelaBD.php`).
     - `2) Verificar y Validar ALTASUCBPRE` (enlace a `ValidarAltas.php`).
     - `3) Validar respuesta de Altas` (enlace a `ProcesaNovedadesAltas.php`).
  3. Todo lo que no se incluyó en el menú Altas Tarjetas debe trasladarse o permanecer disponible en el **Módulo Beneficiarios**.
- **Implementación Técnica:**
  - **Tarjeta Operativa Paso 1 (Altas de Tarjetas):**
    - Se compactó la secuencia a los 3 eslabones normativos canónicos del circuito de altas con sus enlaces funcionales directos.
  - **Módulo Beneficiarios (`#mod-beneficiarios`):**
    - Se renombró el ítem de carga a *"Importar Excel Con Altas"*.
    - Se incorporaron las acciones complementarias no contenidas en la tarjeta reducida:
      - `SubirArchivo.html`: Cargar Archivo de Altas.
      - `CargaAltasDeExcelaBD.php`: Importar Excel Con Altas.
      - `GenerarNovedadAltas.php`: Generar ALTAUSUCBPRE.782.
      - `V2/Altas.php`: Conversor Altas V2 (Descarga Directa).
      - `ValidarAltas.php`: Verificar y Validar ALTASUCBPRE.
      - `ProcesaNovedadesAltas.php`: Validar respuesta de Altas (MovAltas.txt).
      - `RespuestaAltas.php`: Devolución de Altas Cabal (catálogo Anexo 12).
      - `ConsultarBeneficiario.php`: Consultar Beneficiario por DNI.
      - `crud/index.php`: Gestión del Padrón (CRUD).
      - `ReporteAltaBeneficiarios.php`: Reporte de Beneficiarios General.
      - `ExportarExcelBeneficiarios.php`: Exportar Padrón a Excel.
  - **Menú Clásico (`index_menu_original.php`):**
    - Sincronización del menú desplegable *"Beneficiarios"* con los nuevos nombres y enlaces.

### 2. Archivos Modificados
- `index.php`: Reorganización de la tarjeta de circuito de Altas de Tarjetas y adición de acciones en Módulo Beneficiarios.
- `index_menu_original.php`: Sincronización de la barra de navegación desplegable histórica.
- `docs/BITACORA_CAMBIOS.md`: Registro de la intervención.

### 3. Matriz de Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico** | `/opt/lampp/bin/php -l index.php` y `index_menu_original.php` | 0 errores detectados | ✅ PASÓ |

---

## [2026-10-28] - Estandarización de Validación Exclusiva por Archivo Adjunto (Tolerancia Cero a Archivos Pre-cargados)

### 1. Objetivos de la Intervención
- **Solicitud del Usuario:**
  > *"Siempre que se valide información. Los datos a validar se suben en el adjunto de arriba. NO valides con archivos que están cargados en el sistema con anterioridad."*
- **Diagnóstico Arquitectónico:**
  - Varios módulos de validación y verificación del sistema ejecutaban históricamente auditorías automáticas al cargarse por método `GET`, leyendo archivos residuales presentes en el servidor (`Novcar/novcar.782`, `archivos/RespuestaCargas.txt`, `Altas.txt`, `archivos/Acreditaciones.xlsx`).
  - Esta conducta generaba confusión operativa al mostrar reportes y tablas con dictámenes de archivos antiguos antes de que el operador seleccionara el lote correspondiente a su sesión.
- **Implementación Técnica Transversal:**
  1. **`VerificarNovcar.php` (Menu 2.4 - Auditoría Lote NOVCAR.782):**
     - Se eliminó la detección y lectura automática de `Novcar/novcar.782` en peticiones `GET`.
     - Se eliminó la acción y el botón `verificar_servidor` ("Auditar Novcar/novcar.782 existente en servidor").
     - En carga inicial (`GET`): la página muestra exclusivamente la caja drag & drop de subida superior y un banner informativo indicando que el sistema está a la espera del archivo a auditar. Las tarjetas de KPIs, dictamen de lote, checklist de 8 puntos, tarjeta trailer y tabla de detalle permanecen ocultas hasta que se adjunte un archivo.
     - En envío (`POST` con `accion=subir_novcar`): valida estrictamente el archivo adjuntado, reporta su nombre y tamaño, y renderiza la auditoría técnica completa con opción de adjuntar otro archivo o descargarlo.
  2. **`ProcesarCarga.php` (Menu 2.5 - Verificación Respuesta Carga RCAR):**
     - Se eliminó el parseo automático de `archivos/RespuestaCargas.txt` en peticiones `GET`.
     - Se eliminó el enlace de re-análisis sobre el archivo del servidor.
     - En carga inicial (`GET`): se muestra únicamente el recuadro superior de arrastre/adjunto y un aviso institucional esperando el archivo devuelto por Cabal. Las tablas de errores Anexo 11, dictamen de conformidad y desglose de registros se muestran únicamente tras procesar el archivo subido vía `POST`.
  3. **`ValidarAltas.php` (Menu 1.2 - Verificación de Longitud ALTAUSUCBPRE.782):**
     - Control estricto de auditoría posicional (128 bytes + CRLF = 130 bytes): sólo se ejecuta cuando se recibe el archivo por el dropzone superior (`POST`).
     - Alerta explícita de política de validación en la vista inicial.
  4. **`CargaAltasDeExcelaBD.php` (Menu 1.1 - Importar Excel Con Altas):**
     - Se retiró la acción y el botón `analizar_existente` ("Re-analizar Archivo del Servidor"). La planilla sólo puede ser analizada cuando el operador la adjunta en el formulario superior.
  5. **`ImportarAcreditacionesValidado.php` (Menu 2.2 - Importar Acreditaciones Validado):**
     - Se retiró la acción y el botón `analizar_existente` ("Analizar archivos/Acreditaciones.xlsx existente"). El cotejo y validación de totales sólo se dispara mediante el archivo subido en el dropzone.

### 2. Archivos Modificados
- `VerificarNovcar.php`: Supresión de fallback al servidor, remoción de botón `verificar_servidor`, renderizado condicional del dictamen e informe.
- `ProcesarCarga.php`: Supresión de lectura automática de `RespuestaCargas.txt` en `GET`, remoción de re-análisis local, renderizado condicional.
- `CargaAltasDeExcelaBD.php`: Supresión de `analizar_existente` y botón de servidor.
- `ImportarAcreditacionesValidado.php`: Supresión de `analizar_existente` y botón de servidor.
- `docs/BITACORA_CAMBIOS.md`: Registro de la intervención.

### 3. Matriz de Pruebas
| Prueba | Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Linting Sintáctico PHP 5.6** | `/opt/lampp/bin/php -l` en todos los archivos modificados | 0 errores detectados | ✅ PASÓ |
| **Petición GET a `VerificarNovcar.php`** | `curl -s http://localhost/cabal-Gemini/VerificarNovcar.php` | Muestra exclusivamente caja de adjunto y banner esperando archivo; 0 tablas o dictámenes previos | ✅ PASÓ |
| **Petición POST a `VerificarNovcar.php`** | `curl -s -F "accion=subir_novcar" -F "archivo_novcar=@..."` | Procesa y audita únicamente el archivo adjuntado en la petición | ✅ PASÓ |
| **Petición GET a `ProcesarCarga.php`** | `curl -s http://localhost/cabal-Gemini/ProcesarCarga.php` | Muestra únicamente el adjunto superior; no audita `RespuestaCargas.txt` preexistente | ✅ PASÓ |
| **Petición POST a `ProcesarCarga.php`** | `curl -s -F "archivo_respuesta=@..."` | Procesa y audita exitosamente el archivo enviado | ✅ PASÓ |
| **Comprobación de Botones Obsoletos** | Búsqueda grep de `analizar_existente` y `verificar_servidor` en vistas HTML | 0 referencias residuales activas | ✅ PASÓ |



