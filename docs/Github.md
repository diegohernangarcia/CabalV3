# Documentación de Procesos y Operaciones Git / GitHub

**Proyecto:** Sistema Cabal - Familia Rionegrina Solidaria (FSR / SeNAF)  
**Ubicación:** `/opt/lampp/htdocs/cabal-Gemini/docs/Github.md`  
**Última Actualización:** 21 de Octubre de 2026  

---

## 1. Configuración del Repositorio Remoto Oficial

A partir de la jornada del 21 de Octubre de 2026, el repositorio oficial y activo del sistema pasa a ser **CabalV3**, sustituyendo al repositorio anterior (`cabal.git`):

| Parámetro | Configuración Oficial Activa |
| :--- | :--- |
| **Nombre del Proyecto** | **CabalV3** (Versión 3 de Cabal FSR) |
| **Repositorio Remoto Oficial** | `https://github.com/diegohernangarcia/CabalV3.git` |
| **URL de Conexión SSH (Recomendada)** | `git@github.com:diegohernangarcia/CabalV3.git` |
| **Rama Principal de Trabajo** | `main` |
| **Alias de Remote Activo** | `origin` &rarr; `git@github.com:diegohernangarcia/CabalV3.git` |
| **Repositorio Legado (Histórico/Archivo)** | `cabal_v1` &rarr; `git@github.com:diegohernangarcia/cabal.git` |

---

## 2. Historial de Acciones y Procesos Git Ejecutados

Cada operación realizada en el repositorio debe documentar de forma explícita el comando ejecutado, su estado final (**EXITOSA** o **CON ERROR**) y la resolución técnica aplicada en caso de incidencias.

---

### Acción 01: Creación del Repositorio CabalV3 en GitHub
- **Fecha y Hora:** 21 de Octubre de 2026 (12:53:08 -0300)
- **Operación:** Creación de repositorio remoto en GitHub bajo `diegohernangarcia/CabalV3`.
- **Estado:** ✅ **EXITOSA**
- **Resultado:** Repositorio remoto inicializado con commit `982adea` (*"Initial commit"* conteniendo archivo `README.md`).

---

### Acción 02: Transición de Versión y Descarte de V2
- **Fecha y Hora:** 21 de Octubre de 2026 (12:55:20 -0300)
- **Comando:** `git commit -m "Primer commit: Estructura inicial del proyecto CabalV3 - Abandono Total de la V2"`
- **Hash de Commit:** `e4bae2d`
- **Estado:** ✅ **EXITOSA**
- **Resultado:** 927 archivos procesados, saneamiento de temporales antiguos y consolidación de la estructura base V3.

---

### Acción 03: Confirmación de Regularización de Altas y Documentación
- **Fecha y Hora:** 21 de Octubre de 2026 (13:04:52 -0300)
- **Comando:** `git commit -m "Version 3 de Cabal, se frena el desarrollo de V2 por complejidad alta"`
- **Hash de Commit:** `789af68`
- **Estado:** ✅ **EXITOSA**
- **Resultado:** 4 archivos confirmados (`docs/BITACORA_CAMBIOS.md`, `docs/MODULOS.md`, `docs/PENDIENTES_Y_BACKLOG.md`, `docs/ARQUITECTURA.md`) con 210 inserciones y 11 supresiones.

---

### Acción 04: Intento de Push Inadvertido al Repositorio Anterior (Incidencia de Remote)
- **Fecha y Hora:** 21 de Octubre de 2026 (13:08:27 -0300)
- **Comando:** `git push origin main`
- **Estado:** ⚠️ **ERROR DE ENRUTAMIENTO (Inadvertido)**
- **Diagnóstico del Error:**
  El comando fue técnicamente exitoso a nivel de red, pero **el alias `origin` apuntaba erróneamente al repositorio histórico `cabal.git`** (`git@github.com:diegohernangarcia/cabal.git`), debido a que el nuevo repositorio `CabalV3` había sido añadido localmente con el alias `main` en lugar de `origin`.
- **Salida Observada:**
  ```text
  To github.com:diegohernangarcia/cabal.git
     e4bae2d..789af68  main -> main
  ```
- **Resolución Aplicada:**
  El operador alertó que el destino correcto era `CabalV3.git`. Se procedió de inmediato a reconfigurar los remotes locales en la Acción 05.

---

### Acción 05: Reconfiguración de Remotes Locales
- **Fecha y Hora:** 21 de Octubre de 2026 (13:11:46 -0300)
- **Comandos Ejecutados:**
  ```bash
  git remote rename origin cabal_v1
  git remote remove main
  git remote add origin git@github.com:diegohernangarcia/CabalV3.git
  ```
- **Estado:** ✅ **EXITOSA**
- **Resultado:**
  El alias `origin` quedó enlazado a `CabalV3.git` y el repositorio histórico quedó resguardado bajo el alias `cabal_v1`:
  ```text
  origin      git@github.com:diegohernangarcia/CabalV3.git (fetch / push)
  cabal_v1    git@github.com:diegohernangarcia/cabal.git (fetch / push)
  ```

---

### Acción 06: Rechazo de Push por Falta de Avance Rápido (Non-Fast-Forward)
- **Fecha y Hora:** 21 de Octubre de 2026 (13:11:58 -0300)
- **Comando:** `git push -u origin main`
- **Estado:** ❌ **OPERACIÓN RECHAZADA POR GITHUB (Error Previsto)**
- **Diagnóstico del Error:**
  GitHub rechazó la subida con error `non-fast-forward` porque el repositorio remoto `CabalV3` contenía el commit inicial `982adea` (`README.md`), el cual no formaba parte del historial de la rama local `main`.
- **Salida del Error:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
   ! [rejected]        main -> main (non-fast-forward)
  error: falló el empuje de algunas referencias a 'github.com:diegohernangarcia/CabalV3.git'
  hint: Updates were rejected because the tip of your current branch is behind
  hint: its remote counterpart.
  ```
- **Resolución Aplicada:**
  Dado que el commit remoto `982adea` era únicamente la inicialización automática de GitHub con una sola línea (`# CabalV3`), se aplicó una actualización forzada (`--force`) en la Acción 07 para establecer el árbol completo de la Versión 3 como rama base.

---

### Acción 07: Push Forzado Inicial y Vinculación de Rama Principal
- **Fecha y Hora:** 21 de Octubre de 2026 (13:12:30 -0300)
- **Comando:** `git push -u origin main --force`
- **Estado:** ✅ **EXITOSA**
- **Resultado:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
   + 982adea...789af68 main -> main (forced update)
  rama 'main' configurada para rastrear 'origin/main'.
  ```
  La totalidad de la historia, archivos fuente y documentación de CabalV3 quedó publicada exitosamente en GitHub.

---

### Acción 08: Confirmación y Envío de `docs/Github.md` y Directivas `GEMINI.md`
- **Fecha y Hora:** 21 de Octubre de 2026 (13:23:50 -0300)
- **Comandos Ejecutados:**
  ```bash
  git add GEMINI.md docs/BITACORA_CAMBIOS.md docs/Github.md
  git commit -m "docs: Creacion de Github.md y directivas obligatorias de registro en GEMINI.md"
  git push origin main
  ```
- **Hash de Commit:** `39ecbc2`
- **Estado:** ✅ **EXITOSA**
- **Resultado:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
     789af68..39ecbc2  main -> main
  ```
  Sincronización remota completada sin errores.

---

### Acción 09: Estandarización de Auditoría de Éxito y Error en Documentación
- **Fecha y Hora:** 21 de Octubre de 2026 (13:28:15 -0300)
- **Operación:** Incorporación del desglose explícito de estados de éxito (✅) e incidencias/errores (❌ / ⚠️) con diagnósticos y resoluciones técnicas en `docs/Github.md` y actualización de directivas en `GEMINI.md`.
- **Estado:** ✅ **EXITOSA**
- **Resultado:** Documentación actualizada y regla de trazabilidad consolidada en `GEMINI.md:L94`.

---

### Acción 10: Implementación y Habilitación del Modo Oscuro Institucional
- **Fecha y Hora:** 21 de Octubre de 2026 (14:02:00 -0300)
- **Comando:** `git commit -m "feat(ui): habilitar modo oscuro predeterminado con selector dinamico y persistencia" && git push origin main`
- **Hash de Commit:** `55fb095`
- **Estado:** ✅ **EXITOSA**
- **Resultado:** Incorporación de variables y estilos de alto contraste en `css/dashboard.css`, script modular anti-parpadeo `js/theme.js`, integración del botón de alternancia en la barra de navegación de `index.php` e inyección automática en las vistas operativas del sistema. Push completado exitosamente a `origin/main` (`c5c5ffc..55fb095`).

---

### Acción 11: Corrección de Contraste en Tablas y Acción Dinámica en Selector de Tema
- **Fecha y Hora:** 21 de Octubre de 2026 (14:12:00 -0300)
- **Comando:** `git commit -m "fix(ui): corregir contraste en tabla exportar beneficiarios y rotular accion dinamica en boton de tema" && git push origin main`
- **Hash de Commit:** `cbabbb4`
- **Estado:** ✅ **EXITOSA**
- **Resultado:** Adaptación de `.table-responsive-container` y `.table-beneficiarios` en `ExportarExcelBeneficiarios.php` y `css/dashboard.css` para eliminar fondos blancos fijos en dark mode. Refactorización de `js/theme.js` e `index.php` para que el botón de tema indique la acción a ejecutar (*"Cambiar a Modo Oscuro"* / *"Cambiar a Modo Claro"*). Creación de `.gitignore` para protección de planillas PII generadas. Push completado a `origin/main` (`818dd42..cbabbb4`).

---

### Acción 12: Unificación Visual Total y Formato Institucional en Todas las Páginas del Sistema
- **Fecha y Hora:** 23 de Octubre de 2026 (09:12:00 -0300)
- **Comando:** `git commit -m "feat(ui): unificar formato de colores y diseno del inicio en todas las paginas restantes" && git push origin main`
- **Hash de Commit:** `9823c06`
- **Estado:** ✅ **EXITOSA**
- **Resultado:** Modernización integral de más de 45 páginas PHP y HTML del sistema. Se aplicó el sistema de diseño del tablero (`css/dashboard.css`), barra de navegación institucional superior (`<header class="top-nav">`) con logo, Entidad 782, enlace a tablero y selector dinámico de modo oscuro/claro (`js/theme.js`). Implementación de enmascaramiento de tarjetas (`formatearTarjetaCredito()`), prepared statements contra inyecciones SQL, cumplimiento de los formatos fijos Cabal (128 bytes y 80 bytes con CRLF), descargas directas HTTP y creación sistemática de respaldos de seguridad (`*_original.*`). Verificación de sintaxis PHP 5.6 con 0 errores en el 100% de los archivos. Push completado exitosamente a `origin/main` (`39abb7f..9823c06`).

---

### Acción 13: Implementación del Módulo Importar y Subir Altas Validado desde Excel
- **Fecha y Hora:** 23 de Octubre de 2026 (13:57:00 -0300)
- **Comando:** `git commit -m "Nuevo Improtacion Alta Beneficiario Excel" && git push origin main`
- **Hash de Commit:** `2bd1c81` (`main -> origin/main`)
- **Estado:** ✅ **EXITOSA**
- **Resultado:** Integración completa del nuevo flujo del ítem 4 en `index.php` y `CargaAltasDeExcelaBD.php`. Detección dinámica de cabeceras en primera hoja comenzando con la columna 'Apellido' (independientemente de la fila o columna inicial), lectura ultrarrápida y segura con PHPExcel (`setReadDataOnly(true)` y corte ante filas vacías), validación normativa exhaustiva según `docs/normas_de_validacion.md` (longitudes fijas, Anexo 9 Provincias, Sexo M/F, CP, Estado Civil, Fechas DDMMAAAA y Anexo 10 Tipo Documento), control crítico de CUIT contra la tabla `beneficiario` (bloqueo estricto del botón 'Guardar datos en BD' ante duplicados o inconsistencias), confirmación transaccional atómica en 2 pasos (1: TRUNCATE altas, 2: INSERT masivo en altas) y sincronización con `GenerarNovedadAltas.php`.

---

### Acción 14: Validación Estricta por Archivo Adjunto, Normativa Cabal y Reestructuración de Altas
- **Fecha y Hora:** 28 de Octubre de 2026 (12:38:00 -0300)
- **Comando Ejecutado:** `git commit -m "feat: estandarizacion de validacion estricta por archivo adjunto, integracion normativa Cabal y reestructuracion de flujo de altas" && git push origin main`
- **Hash de Commit:** `b819a8b` (`966cd49..b819a8b` hacia `origin/main`)
- **Estado:** ✅ **EXITOSA**
- **Detalle de la Operación:**
  - 53 archivos modificados o creados con 10.498 inserciones y 938 supresiones.
  - Estandarización transversal de validación estricta: sólo se procesan y auditan los datos subidos por el operador en el componente superior (Dropzone) en `ValidarAltas.php`, `VerificarNovcar.php`, `ProcesarCarga.php`, `CargaAltasDeExcelaBD.php`, `ImportarAcreditacionesValidado.php` y `ProcesaNovedadesAltas.php`, eliminando lecturas automáticas o botones de análisis sobre archivos residuales del servidor.
  - Incorporación de especificaciones oficiales y normativas en `docs/Cabal.md`, `docs/NOVCAR.md` y `docs/rcar.md`.
  - Reestructuración canónica del circuito de Altas de Tarjetas en 3 pasos ordenados y enriquecimiento del Módulo Beneficiarios en `index.php` e `index_menu_original.php`.
  - Creación del catálogo centralizado de códigos de respuesta Cabal (`BD/CabalErrores.php`).
  - Sustitución de popups de JavaScript nativo por ventana emergente modal estilizada con los colores de la aplicación en `CargaAltasDeExcelaBD.php`.
  - Configuración estricta en `.gitattributes` para asegurar preservación inalterada de saltos de línea Windows CRLF (`\r\n`) en todos los archivos de lote `.782` y texto Cabal.
- **Salida Observada:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
     966cd49..b819a8b  main -> main
  ```

---

### Acción 15: Módulos de Informe Alta e Informe Acreditación con Exportación a EML para Thunderbird
- **Fecha y Hora:** 2026-11-19 09:54:00 -0300
- **Comando Ejecutado:** `git commit -m "Informe en EML para nuevos beneficiarios y liquidaciones" && git push origin main`
- **Hash de Commit:** `a4a2f21` (`cd24b5f..a4a2f21` hacia `origin/main`)
- **Estado:** ✅ **EXITOSA**
- **Detalle de la Operación:**
  - 36 archivos procesados (+8.814 inserciones, -26 supresiones).
  - Creación de `InformeAlta.php` (Item 4 en Altas de Tarjetas) e `InformeAcreditacion.php` (Item 7 en Acreditación de Saldo).
  - Generación de correos MIME RFC 822 en formato `.eml` con cabecera `X-Unsent: 1` para apertura directa en modo redacción en Mozilla Thunderbird.
  - Inclusión de cabecera institucional dual con logos del Ministerio de Desarrollo Humano y Cabal Entidad 782 (`img/cabecera_ministerio.png`, `img/logo_cabal_oficial.png`).
  - Actualización a la denominación oficial "Secretaria de Niñez, Adolescencia y Familia" y enmascaramiento obligatorio de tarjetas (`formatearTarjetaCredito()`).
  - Integración en `index.php`, `ProcesarCarga.php`, `ProcesaNovedadesAltas.php`, `GenerarNovedadAltas.php`, `RespuestaAltas.php` e `index-menu.html`.
  - Instalación de la skill de auditoría de seguridad en `.agents/skills/security-audit/`.
  - Actualización exhaustiva de `docs/BITACORA_CAMBIOS.md`.
- **Salida Observada:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
     cd24b5f..a4a2f21  main -> main
  ```

---

### Acción 16: Módulo Informe Padrón General EML y Modernización de Tarjetas de Flujo
- **Fecha y Hora:** 2026-11-19 13:01:00 -0300
- **Comando Ejecutado:** `git commit -m "feat: modulo informe padron general EML, renombrado de tarjeta beneficiarios y modernizacion visual de items" && git push origin main`
- **Hash de Commit:** `d843125` (`ed7927b..d843125` hacia `origin/main`)
- **Estado:** ✅ **EXITOSA**
- **Detalle de la Operación:**
  - 7 archivos procesados (+1.576 inserciones, -68 supresiones).
  - Creación de `InformePadronGeneral.php` con generación de correos MIME RFC 822 con `X-Unsent: 1` para apertura directa en Thunderbird, cabecera institucional dual y enmascaramiento estricto de tarjetas.
  - Renombramiento de la Tarjeta 1 a "Beneficiarios" e incorporación de accesos a `crud/index.php` (Administración de Beneficiarios) e `InformePadronGeneral.php`.
  - Rediseño moderno de los ítems de las 4 tarjetas de flujo en `index.php` y `css/dashboard.css` (`.step-item-btn` interactivos con insignias numéricas, micro-tags temáticos, animación hover y flecha de acción).
  - Alineación de altura uniforme (`min-height: 68px` en cabeceras y 38px en ítems) y contención estricta dentro del marco visual con `grid-template-columns: repeat(4, minmax(0, 1fr))` y `min-width: 0` sin desbordamiento.
  - Integración en `ReporteAltaBeneficiarios.php` e `index-menu.html`.
  - Actualización exhaustiva de bitácora en `docs/BITACORA_CAMBIOS.md`.
- **Salida Observada:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
     ed7927b..d843125  main -> main
  ```

---

### Acción 17: Módulo de Usuarios, Control de Acceso RBAC, Seguridad BCRYPT, Blanqueo y Blindaje Operativo
- **Fecha y Hora:** 2026-11-20 07:48:00 -0300
- **Comando Ejecutado:** `git commit -m "Modulo Usuario" && git push origin main`
- **Hash de Commit:** `350ba6f` (`edd9c64..350ba6f` hacia `origin/main`)
- **Estado:** ✅ **EXITOSA**
- **Salida Observada:**
  ```text
  To github.com:diegohernangarcia/CabalV3.git
     edd9c64..350ba6f  main -> main
  ```
- **Detalle de la Operación:**
  - Creación del esquema relacional de seguridad (`BD/migracion_modulo_usuarios.sql` y `BD/migrar_usuarios.php`) con tablas `roles`, `usuarios`, `recuperacion_tokens`, `auditoria_accesos` y carga de roles `ADMIN`, `OPERADOR`, `AUDITOR`.
  - Desarrollo del núcleo de autenticación y sesiones seguras en `auth/` (`config_sesion.php`, `funciones_auth.php`, `verificar_sesion.php`):
    - Cifrado con algoritmo estándar BCRYPT (`PASSWORD_BCRYPT`).
    - Caducidad automática de sesión por inactividad tras 20 minutos (1200 seg).
    - Protección contra fuerza bruta (bloqueo preventivo de 15 min al alcanzar 5 intentos fallidos consecutivos).
    - Protección anti-CSRF con tokens de sesión y prevención de Session Fixation.
    - Bitácora forense de auditoría de accesos (`auditoria_accesos`) con IP, User-Agent y timestamp.
  - Creación de la pantalla de inicio de sesión institucional (`auth/login.php`) con diseño glassmorphism y credenciales iniciales.
  - Creación del panel de administración integral de usuarios (`admin_usuarios.php`) reservado exclusivamente para rol `ADMIN`:
    - Métricas KPI ejecutivas (Total Usuarios, Cuentas Activas, Cuentas Bloqueadas, Roles).
    - Modal de creación de nuevo usuario con asignación de roles.
    - Modal de edición de datos, rol y activación/desactivación.
    - Botón de desbloqueo inmediato para cuentas bloqueadas por fuerza bruta.
    - Botón y modal de **Blanqueo de Contraseña** con sugerencias rápidas ("12345", "cabal2026", generador aleatorio) y registro de auditoría.
    - Visor forense de eventos de seguridad con filtrado en tiempo real.
  - Blindaje con guardas de autenticación obligatoria (`requerirAutenticacion()`) en:
    - `index.php` (Panel Principal, con barra de usuario e indicador de rol `ADMIN` / botón directo a Usuarios).
    - `InformeAlta.php`
    - `InformeAcreditacion.php`
    - `InformePadronGeneral.php`
    - `crud/index.php` (Administración de Beneficiarios)
  - Actualización exhaustiva de bitácora en `docs/BITACORA_CAMBIOS.md`.

---

## 3. Protocolos Operativos y Estándar Obligatorio de Registro

Para cumplir con la directiva permanente fijada en `GEMINI.md`:

### 3.1. Formato Obligatorio para Registrar Futuras Acciones
Toda nueva acción con Git o GitHub debe incorporarse en este archivo siguiendo la siguiente plantilla:

```markdown
### Acción NN: [Descripción Breve de la Tarea]
- **Fecha y Hora:** [AAAA-MM-DD HH:MM:SS]
- **Comando Ejecutado:** `git ...`
- **Hash de Commit (si aplica):** `[hash]`
- **Estado:** ✅ **EXITOSA** / ❌ **CON ERROR** / ⚠️ **ADVERTENCIA**
- **Salida / Mensaje de Git:**
  ```text
  [Salida textual del comando o error emitido]
  ```
- **Resolución Aplicada (si hubo error):** [Explicación técnica de la corrección]
```

### 3.2. Comandos Frecuentes
- **Comprobar estado local:** `git status`
- **Comprobar sincronización con GitHub:** `git branch -vv`
- **Enviar cambios:** `git push origin main`
- **Obtener cambios del remoto:** `git pull origin main`

---

## 4. Políticas de Seguridad en el Repositorio

1. **Datos Financieros y Personales (PII):**
   - Queda estrictamente prohibido hacer commit o push de planillas con datos reales de beneficiarios (`Beneficiarios*.xlsx`, archivos con DNI/Cuentas reales no sanitizados).
   - Utilizar únicamente archivos de prueba anonimizados en entornos de desarrollo.

2. **Secretos y Credenciales:**
   - No versionar contraseñas de bases de datos, claves SSH privadas ni tokens personales de GitHub.
   - En documentación y código, enmascarar valores sensibles bajo `[REDACTADO / SECRETO]`.

3. **Archivos de Respaldo (`*_original.*`):**
   - Los archivos de respaldo generados durante refactorizaciones críticas (`CargaAltasDeExcelaBD_original.php`, `GenerarNovedadAltas_original.php`, etc.) deben versionarse temporalmente para permitir auditoría y rollback instantáneo.
