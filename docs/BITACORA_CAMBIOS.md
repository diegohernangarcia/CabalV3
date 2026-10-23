# Bitácora Oficial de Cambios y Regularización Técnica

**Proyecto:** Sistema Cabal - Familia Rionegrina Solidaria (FSR / SeNAF)  
**Ubicación:** `/opt/lampp/htdocs/cabal-Gemini/docs/BITACORA_CAMBIOS.md`  
**Entorno de Ejecución:** Apache 2.4.38 • PHP 5.6.40 (XAMPP LAMPP) • MariaDB `cabal`  

---

## Índice Cronológico
- [Jornada: 23 de Octubre de 2026 (Modernización Integral de Estilos y Colores en Todas las Páginas)](#jornada-23-de-octubre-de-2026)
- [Jornada: 21 de Octubre de 2026 (Sprint Mejoras de Altas y Subida Centralizada)](#jornada-21-de-octubre-de-2026)
- [Jornada: 20 de Octubre de 2026 (Auditoría Integral, Documentación Base y Nuevo Tablero)](#jornada-20-de-octubre-de-2026)

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

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (23/10/2026)

| Prueba | Comando / Método de Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Lint Sintáctico Exhaustivo** | `find . -name "*.php" -exec /opt/lampp/bin/php -l {} \;` | 0 errores de sintaxis en el 100% de archivos PHP | ✅ PASÓ |
| **Compatibilidad PHP 5.6** | Verificación de ausencia de operadores PHP 7+ (`??`, match, return types) | Totalmente compatible con PHP 5.6.40 | ✅ PASÓ |
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

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (20/10/2026)

| Prueba | Comando / Verificación | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Integridad del Stack** | `/opt/lampp/bin/php -v` | PHP 5.6.40 CLI verificado | ✅ PASÓ |
| **Conexión MySQL local** | Consulta PDO sobre base `cabal` | Conexión exitosa, 13 tablas leídas | ✅ PASÓ |
| **Enmascaramiento de Tarjeta** | Verificación en `ConsultaBenefiario2.php` | `formatearTarjetaCredito()` activo | ✅ PASÓ |
| **Render Tablero index.php** | Inspección visual en navegador local | Renderizado responsive y componentes OK | ✅ PASÓ |
| **Sintaxis de Archivos Modificados** | `/opt/lampp/bin/php -l <archivo>.php` | Sin errores de sintaxis | ✅ PASÓ |

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
