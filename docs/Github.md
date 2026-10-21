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
