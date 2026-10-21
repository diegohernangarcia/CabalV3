# Bitácora Oficial de Cambios y Regularización Técnica

**Proyecto:** Sistema Cabal - Familia Rionegrina Solidaria (FSR / SeNAF)  
**Ubicación:** `/opt/lampp/htdocs/cabal-Gemini/docs/BITACORA_CAMBIOS.md`  
**Entorno de Ejecución:** Apache 2.4.38 • PHP 5.6.40 (XAMPP LAMPP) • MariaDB `cabal`  

---

## Índice Cronológico
- [Jornada: 21 de Octubre de 2026 (Sprint Mejoras de Altas y Subida Centralizada)](#jornada-21-de-octubre-de-2026)
- [Jornada: 20 de Octubre de 2026 (Auditoría Integral, Documentación Base y Nuevo Tablero)](#jornada-20-de-octubre-de-2026)

---

## Jornada: 21 de Octubre de 2026

### 1. Objetivos de la Sesión
1. Centralizar los puntos de acceso de subida de archivos hacia la interfaz unificada `SubirArchivo.html`.
2. Proteger la base de datos ocultando acciones operativas de alto riesgo (como el procesamiento destructivo de acreditaciones).
3. Resolver el problema de inconsistencia en nombres de archivos con mayúsculas/minúsculas (`AlTaS.xlsx` &rarr; `Altas.xlsx`).
4. Modernizar la interfaz y la seguridad de datos del procesador de planillas de altas `CargaAltasDeExcelaBD.php`.
5. Modernizar la pantalla de emisión del lote bancario `GenerarNovedadAltas.php` respetando estrictamente el formato fijo de 128 bytes por registro Cabal, con botón de copia con CRLF, salto de línea reglamentario al final y descarga directa HTTP `ALTAUSUCBPRE.782`.

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

---

### 3. Matriz de Verificación y Pruebas Ejecutadas (21/10/2026)

| Prueba | Comando Ejecutado | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :---: |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l GenerarNovedadAltas.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l CargaAltasDeExcelaBD.php` | `No syntax errors detected` | ✅ PASÓ |
| **Sintaxis PHP 5.6** | `/opt/lampp/bin/php -l upload.php` | `No syntax errors detected` | ✅ PASÓ |
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
