# DIRECTIVAS DE DESARROLLO Y REGLAS DE TRABAJO - PROYECTO CABAL FSR

**Destinatarios:** Asistente Gemini en Antigravity y Desarrolladores del Proyecto  
**Ubicación:** Raíz del Repositorio (`/opt/lampp/htdocs/cabal-Gemini/GEMINI.md`)  
**Última Actualización:** 20 de Octubre de 2026 (Auditoría Integral Inicial)  

---

## 1. Convenciones de Código y Estilo del Repositorio

1. **Codificación de Archivos y Saltos de Línea:**
   - Todos los archivos de código fuente (`.php`, `.html`, `.css`, `.js`) deben guardarse estrictamente en **UTF-8 sin BOM**.
   - **ARCHIVOS BANCARIOS CABAL:** Todo archivo batch generado para intercambio con la plataforma bancaria Cabal (`ALTAUSUCBPRE.782`, `NOVCAR.782`, `NOVTRAN.782`) debe forzar obligatoriamente codificación **ANSI / ISO-8859-1** y saltos de línea estilo Windows **CRLF (`\r\n`)**.
   - Queda estrictamente prohibido usar `PHP_EOL` en generadores de lotes bancarios cuando se ejecute en Linux, ya que produce `\n` (LF) y el mainframe de Cabal rechaza el lote.

2. **Manejo de Base de Datos:**
   - No instanciar conexiones ad-hoc directas (`new mysqli` o `new PDO`) con credenciales hardcodeadas en scripts individuales.
   - Utilizar siempre la conexión centralizada provista o preparar la transición hacia un archivo central `config/database.php`.
   - **Tolerancia Cero a SQL Injection:** Toda consulta con parámetros externos (GET, POST, JSON) debe utilizar **Prepared Statements** con `PDO::prepare()` o `mysqli_prepare()` y bindeo de tipos explícito (`bind_param` o `:named_parameters`). Queda prohibida la concatenación directa de variables en cadenas SQL.

3. **Compatibilidad PHP y Dependencias:**
   - Recordar que el entorno web actual corre bajo **PHP 5.6.40 en XAMPP (`/opt/lampp/bin/php`)**.
   - No utilizar sintaxis ni operadores de PHP 7+ / 8+ (como tipado estricto en retornos, operador null coalescing `??`, operador match, enums o propiedades tipadas) hasta que se complete formalmente la migración a PHP 8.x en el Sprint 4.
   - No agregar nuevas funciones dependientes de la librería obsoleta `phpexcel/`. Cualquier nueva funcionalidad con hojas de cálculo debe planificarse sobre `PhpSpreadsheet`.

---

## 2. Restricciones Arquitectónicas y de Negocio

1. **Inviolabilidad de Formatos Posicionales de Cabal (Entidad 782):**
   - No modificar las longitudes ni la estructura de los campos fijos de los archivos bancarios:
     - `ALTAUSUCBPRE.782`: Exactamente 128 bytes por registro (3 registros por titular).
     - `NOVCAR.782`: Registros de detalle de 80 bytes (código operación `6339`, importe en centavos padded a 9 dígitos).
     - `NOVTRAN.782`: Registros de detalle de 80 bytes (código operación `62` para descarga, `63` para carga).
   - Cualquier cambio en los formatos debe ser validado contra los manuales oficiales de Cabal antes de implementarse.

2. **Descarga Directa vs. Renderizado en Pantalla:**
   - Queda terminantemente prohibido imprimir datos de lotes bancarios en pantalla con etiquetas HTML (`&nbsp;` o `<br>`).
   - Todo archivo bancario debe ser entregado mediante descarga HTTP directa con encabezados `header('Content-Type: text/plain')` y `header('Content-Disposition: attachment; filename="NOMBRE.782"')` o guardado en disco con permisos controlados.

3. **Manejo de Errores y Transacciones en Base de Datos:**
   - Operaciones que requieran vaciar o sobreescribir tablas (`TRUNCATE TABLE acreditar2`) no deben ejecutarse en caliente sin antes validar que el archivo entrante sea válido y contenga registros procesables.
   - Usar transacciones (`beginTransaction()`, `commit()`, `rollBack()`) siempre que sea posible.

---

## 3. Pautas de Seguridad Obligatorias

1. **Subida Segura de Archivos (File Uploads):**
   - Todo formulario de subida debe validar:
     - Lista blanca estricta de extensiones permitidas (ej. solo `.xlsx` o `.txt`).
     - Tipo MIME real del archivo (`finfo_file` o `mime_content_type`).
     - Tamaño máximo en bytes.
   - Nunca utilizar el nombre original del archivo para ejecutarlo o incluirlo (`include`/`require`).
   - El script `upload.php` original queda marcado como **CRÍTICO - DESHABILITADO** y no debe utilizarse.

2. **Privacidad de Datos y Sanitización de Vistas:**
   - Nunca exponer en pantalla el número completo de tarjeta de crédito/débito. Utilizar siempre la función `formatearTarjetaCredito()` para enmascarar los dígitos centrales (`XXXX.XX**.****.XXXX`).
   - Toda salida de datos hacia el navegador debe escapar caracteres especiales mediante `htmlspecialchars($campo, ENT_QUOTES, 'UTF-8')` para neutralizar ataques XSS.

3. **Credenciales y Secretos:**
   - No versionar ni imprimir en logs contraseñas, tokens de API ni credenciales de base de datos.
   - Enmascarar cualquier valor confidencial bajo el formato `[REDACTADO / SECRETO]`.

---

## 4. Procedimiento para Probar y Verificar Cambios

1. **Verificación Sintáctica (Linting):**
   Antes de dar por finalizada cualquier modificación, verificar la sintaxis de los archivos PHP modificados:
   ```bash
   /opt/lampp/bin/php -l ruta_del_archivo.php
   ```

2. **Inspección de Longitud de Lotes Generados:**
   Al modificar o crear generadores de archivos `.782`, comprobar mediante línea de comandos que cada línea respete exactamente el número de bytes estipulado (ej. 128 bytes + 2 bytes de CRLF = 130 bytes por línea):
   ```bash
   awk '{print length}' Novcar/novcar.782 | sort -u
   ```

3. **Prueba en Modo Solo Lectura:**
   No realizar pruebas que alteren la base de datos de producción local (`cabal`) con datos de prueba ficticios sin previa autorización o sin respaldo de las tablas afectadas (`beneficiario`, `movimientos`).
