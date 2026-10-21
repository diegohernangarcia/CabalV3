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

### Acción 01: Creación del Repositorio CabalV3 en GitHub
- **Fecha:** 21 de Octubre de 2026 (12:53:08)
- **Operación:** Creación de un nuevo repositorio remoto en la plataforma GitHub bajo la cuenta `diegohernangarcia`: `https://github.com/diegohernangarcia/CabalV3.git`.
- **Commit Inicial Remoto:** `982adea` (*"Initial commit"* con archivo `README.md`).

### Acción 02: Transición de Versión y Descarte de V2
- **Fecha:** 21 de Octubre de 2026 (12:55:20)
- **Hash de Commit:** `e4bae2d`
- **Mensaje:** `Primer commit: Estructura inicial del proyecto CabalV3 - Abandono Total de la V2`
- **Contenido:** Consolidación de la estructura base del proyecto CabalV3 y saneamiento de dependencias y archivos innecesarios.

### Acción 03: Confirmación de Regularización de Altas y Documentación
- **Fecha:** 21 de Octubre de 2026 (13:04:52)
- **Hash de Commit:** `789af68`
- **Mensaje:** `Version 3 de Cabal, se frena el desarrollo de V2 por complejidad alta`
- **Contenido del Commit:**
  - `docs/BITACORA_CAMBIOS.md` (Creación de bitácora oficial de jornadas del 20 y 21 de Octubre).
  - `docs/MODULOS.md` (Actualización de estados en Módulos de Altas y Acreditaciones).
  - `docs/PENDIENTES_Y_BACKLOG.md` (Incorporación de tareas `SPR-21-01` a `SPR-21-05`).
  - `docs/ARQUITECTURA.md` (Actualización de especificaciones y fechas de revisión).

### Acción 04: Reconfiguración de Remotos Locales
- **Fecha:** 21 de Octubre de 2026 (13:11:46)
- **Diagnóstico del Problema:**
  El repositorio local tenía configurado el alias `origin` apuntando al repositorio viejo (`cabal.git`) y el nuevo repositorio `CabalV3` había sido añadido erróneamente con el alias `main`.
- **Comandos Ejecutados:**
  ```bash
  # 1. Preservar repositorio anterior como referencia histórica
  git remote rename origin cabal_v1

  # 2. Eliminar el alias redundante 'main'
  git remote remove main

  # 3. Establecer 'origin' apuntando al nuevo repositorio CabalV3
  git remote add origin git@github.com:diegohernangarcia/CabalV3.git
  ```
- **Resultado:**
  ```text
  origin      git@github.com:diegohernangarcia/CabalV3.git (fetch)
  origin      git@github.com:diegohernangarcia/CabalV3.git (push)
  cabal_v1    git@github.com:diegohernangarcia/cabal.git (fetch)
  cabal_v1    git@github.com:diegohernangarcia/cabal.git (push)
  ```

### Acción 05: Publicación Inicial Forzada (Push Initial) a CabalV3
- **Fecha:** 21 de Octubre de 2026 (13:12:30)
- **Comando Ejecutado:**
  ```bash
  git push -u origin main --force
  ```
- **Resultado:**
  - Sustitución del commit inicial dummy de GitHub (`982adea`) por la totalidad de la historia y código fuente del proyecto (`789af68`).
  - Configuración permanente del rastreo: `main` &rarr; `origin/main`.
  - El repositorio en `https://github.com/diegohernangarcia/CabalV3` quedó 100% actualizado.

---

## 3. Protocolos Operativos y Comandos Estándar

### 3.1. Flujo Obligatorio para Realizar Commits
Cada vez que se complete una tarea técnica o modificación funcional:

1. **Documentar el cambio:** Actualizar `docs/BITACORA_CAMBIOS.md` detallando archivos, propósito y pruebas.
2. **Verificar estado:**
   ```bash
   git status
   ```
3. **Añadir archivos al stage:**
   ```bash
   git add <archivo1> <archivo2> docs/BITACORA_CAMBIOS.md
   ```
4. **Crear commit con mensaje descriptivo e institucional:**
   ```bash
   git commit -m "Descripción concisa y clara de la tarea realizada"
   ```

### 3.2. Flujo para Enviar Cambios a GitHub (Push)
```bash
# Enviar commits locales a la rama main del repositorio oficial CabalV3
git push origin main
```

### 3.3. Verificación de Sincronización Remota
```bash
# Comprobar ramas y commits de desfase
git branch -vv

# Comprobar estado limpio
git status
```

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
