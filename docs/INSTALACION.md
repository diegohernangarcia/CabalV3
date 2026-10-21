# Guía de Instalación, Configuración y Puesta en Marcha Local

**Proyecto:** Sistema Cabal - Familia Rionegrina Solidaria (FSR / SeNAF)  
**Entorno de Referencia:** Linux Ubuntu x86_64 con stack XAMPP/LAMPP  
**Fecha de Elaboración:** 20 de Octubre de 2026  

---

## 1. Requisitos Previos del Sistema

### 1.1. Arquitectura de Software y Entorno de Ejecución
La aplicación está diseñada y opera actualmente sobre la pila **XAMPP para Linux** instalada en `/opt/lampp`.

| Componente | Versión en Producción Local | Alternativa / Recomendación Moderna |
| :--- | :--- | :--- |
| **Sistema Operativo** | Linux Ubuntu (kernel 6.x / 7.x) | Cualquier distribución Linux con soporte Apache/PHP |
| **Servidor Web** | Apache 2.4.38 (`/opt/lampp/bin/httpd`) | Apache 2.4+ con módulo `mod_rewrite` habilitado |
| **Motor PHP Activo** | **PHP 5.6.40** (`/opt/lampp/bin/php`) | Requiere refactorización previa para migrar a PHP 8.2+ |
| **Motor Base de Datos** | MySQL 5.7+ / MariaDB 10.x | Puerto por defecto: `3306` |
| **Extensiones PHP Requeridas** | `pdo_mysql`, `mysqli`, `mbstring`, `zip`, `xml`, `gd` | Indispensables para la suite PHPExcel y conexión MySQL |

> [!WARNING]
> **Dualidad de Entornos PHP en el Servidor:**
> En la máquina de desarrollo coexisten dos binarios PHP:
> 1. `/usr/bin/php` -> **PHP 8.5.4 (CLI del sistema)**: No tiene activos los módulos de PDO MySQL ni la librería PHPExcel compatible.
> 2. `/opt/lampp/bin/php` -> **PHP 5.6.40 (CLI de XAMPP)**: Es el binario con el cual la aplicación funciona correctamente. Toda ejecución por línea de comandos o cron debe invocar explícitamente `/opt/lampp/bin/php`.

---

## 2. Pasos de Instalación y Puesta en Marcha Local

### Paso 1: Clonación del Repositorio y Ubicación
El código debe residir dentro del directorio `htdocs` de XAMPP:
```bash
cd /opt/lampp/htdocs
# Si se clona por primera vez:
git clone https://github.com/diegohernangarcia/cabal.git cabal-Gemini
cd /opt/lampp/htdocs/cabal-Gemini
```

### Paso 2: Creación de Directorios Operativos y Permisos
Debido a que el servidor Apache de XAMPP corre bajo el usuario `daemon` o el usuario local, es necesario asegurar que los directorios donde se guardan lotes y archivos de subida existan y tengan permisos de escritura:

```bash
# Crear directorios obligatorios para subidas y lotes bancarios
mkdir -p archivos
mkdir -p uploads
mkdir -p Novcar

# Asignar permisos de lectura y escritura para el servidor web local
chmod 775 archivos uploads Novcar
```

### Paso 3: Configuración de la Base de Datos MySQL

1. Iniciar los servicios de XAMPP si no están corriendo:
   ```bash
   sudo /opt/lampp/lampp startmysql
   sudo /opt/lampp/lampp startapache
   ```

2. Crear la base de datos `cabal` y el usuario operativo en MySQL:
   ```sql
   CREATE DATABASE IF NOT EXISTS `cabal` CHARACTER SET utf8 COLLATE utf8_spanish_ci;

   -- Crear usuario operativo configurado en el sistema
   CREATE USER IF NOT EXISTS 'pucho'@'localhost' IDENTIFIED BY '78900';
   GRANT ALL PRIVILEGES ON `cabal`.* TO 'pucho'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Importación del Esquema Inicial:**
   Debido a que el repositorio no incluye archivos `.sql` iniciales, si se despliega en una máquina vacía, se deben crear las tablas base mínimas:
   ```sql
   USE `cabal`;

   CREATE TABLE IF NOT EXISTS `beneficiario` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `Apellido` varchar(45) DEFAULT NULL,
     `Nombre` varchar(45) DEFAULT NULL,
     `Calle` varchar(45) DEFAULT NULL,
     `Numero` varchar(45) DEFAULT NULL,
     `Piso` varchar(45) DEFAULT NULL,
     `Departamento` varchar(45) DEFAULT NULL,
     `Localidad` varchar(45) DEFAULT NULL,
     `Provincia` varchar(45) DEFAULT NULL,
     `Sexo` varchar(45) DEFAULT NULL,
     `EntidadEmisora` varchar(45) DEFAULT NULL,
     `FechaProceso` varchar(45) NOT NULL,
     `NumeroCuenta` varchar(45) DEFAULT NULL,
     `NumeroTarjeta` varchar(45) DEFAULT NULL,
     `CodigoPostal` varchar(45) DEFAULT NULL,
     `TelefonoCaracteristica` varchar(45) DEFAULT NULL,
     `TelefonoNumero` varchar(45) DEFAULT NULL,
     `EstadoCivil` varchar(45) DEFAULT NULL,
     `FechaNacimiento` varchar(45) DEFAULT NULL,
     `TipoDocumento` varchar(45) DEFAULT NULL,
     `NumeroDocumento` varchar(45) DEFAULT NULL,
     `ProvinciaDocumento` varchar(45) DEFAULT NULL,
     `Cuit` varchar(45) DEFAULT NULL,
     `CodigoIngreso` varchar(45) DEFAULT NULL,
     `CodigoRechazo` varchar(45) DEFAULT NULL,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

   CREATE TABLE IF NOT EXISTS `movimientos` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `Banco` varchar(50) NOT NULL,
     `Sucursal` varchar(50) NOT NULL,
     `Cuenta` varchar(50) NOT NULL,
     `Tarjeta` varchar(50) NOT NULL,
     `TipoDocumento` varchar(50) NOT NULL,
     `Documento` varchar(50) NOT NULL,
     `NombreApellido` varchar(50) NOT NULL,
     `CodigoOperacion` varchar(50) NOT NULL,
     `Fecha` varchar(50) NOT NULL,
     `Comercio` varchar(50) NOT NULL,
     `Importe` varchar(50) NOT NULL,
     `Signo` varchar(50) NOT NULL,
     `Importe2` varchar(50) NOT NULL,
     `Signo2` varchar(5) NOT NULL,
     `Mes` varchar(50) NOT NULL,
     `Anio` varchar(50) NOT NULL,
     `Archivo` int(11) NOT NULL,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

   CREATE TABLE IF NOT EXISTS `MasterTarjetas` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `NumeroCuenta` bigint(20) NOT NULL,
     `NumeroTarjeta` bigint(20) NOT NULL,
     `NombreApellido` varchar(100) NOT NULL,
     `TipoDocumento` int(11) NOT NULL,
     `NroDocumento` bigint(20) NOT NULL,
     `Causa` varchar(25) NOT NULL,
     `FechaAlta` date NOT NULL,
     `FechaEmision` date NOT NULL,
     `FechaVencimiento` date NOT NULL,
     `Archivo` varchar(100) NOT NULL,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

   CREATE TABLE IF NOT EXISTS `altas` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `Apellido` text,
     `Nombre` text,
     `DomicilioCalle` text,
     `DomicilioNumero` text,
     `DomicilioPiso` text,
     `DomicilioDepartamento` text,
     `Localidad` text,
     `Provincia` text,
     `Sexo` text,
     `CodigoPostal` text,
     `TelefonoCaracteristica` text,
     `TelefonoNumero` text,
     `EstadoCivil` text,
     `FechaNacimiento` text,
     `DocumentoTipo` text,
     `DocumentoNumero` text,
     `DocumentoProvincia` text,
     `CUIT` text,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

   CREATE TABLE IF NOT EXISTS `acreditar2` (
     `id` int(11) DEFAULT NULL,
     `id_beneficiario` int(11) DEFAULT NULL,
     `Apellido` varchar(50) DEFAULT NULL,
     `Nombre` varchar(50) DEFAULT NULL,
     `NumeroCuenta` varchar(50) DEFAULT NULL,
     `ImporteCompra` varchar(50) DEFAULT NULL,
     `ImporteCajero` varchar(50) DEFAULT NULL,
     `DNI` varchar(50) DEFAULT NULL,
     `Fecha` varchar(50) DEFAULT NULL,
     `Hora` varchar(50) DEFAULT NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
   ```

---

## 3. Configuración del Servidor Web (Apache)

### 3.1. Acceso URL Local
Por defecto en XAMPP, el sistema queda accesible a través del navegador web en:
`http://localhost/cabal-Gemini/` o `http://127.0.0.1/cabal-Gemini/`

### 3.2. Configuración Recomendada de PHP (`/opt/lampp/etc/php.ini`)
Dado que se procesan planillas de liquidación y archivos de hasta 50.000 filas (como `Movimientos.Enero2026-1.xlsx`), es mandatorio ajustar los siguientes parámetros en `php.ini`:

```ini
max_execution_time = 300      ; 5 minutos para parseo de Excel pesado
max_input_time = 300
memory_limit = 512M           ; PHPExcel requiere abundante memoria RAM
post_max_size = 64M
upload_max_filesize = 64M
date.timezone = "America/Argentina/Buenos_Aires"
```

---

## 4. Diagnóstico y Solución de Problemas Comunes (Troubleshooting)

### Problema 1: "Fatal error: could not find driver" al ejecutar scripts CLI
- **Causa:** Se está invocando `php script.php` usando el PHP 8.5 del sistema operativo en lugar de la versión de XAMPP.
- **Solución:** Ejecutar siempre con la ruta absoluta de XAMPP:
  ```bash
  /opt/lampp/bin/php nombre_del_script.php
  ```

### Problema 2: El Banco rechaza los archivos de texto (`ALTAUSUCBPRE.782` o `NOVCAR.782`)
- **Causa 1:** El archivo fue guardado con saltos de línea de Linux (`\n` / LF) en vez de Windows (`\r\n` / CRLF) esperado por el mainframe de Cabal.
- **Causa 2:** El archivo generado en pantalla contiene etiquetas HTML (`&nbsp;`, `<br>`).
- **Solución:** Usar los módulos de descarga directa con cabecera `Content-Type: text/plain` y asegurar que la concatenación de líneas termine con `"\r\n"`.

### Problema 3: "¡Error al leer el archivo!" al procesar Altas o Acreditaciones
- **Causa:** El script busca archivos en la carpeta `archivos/` (ej. `archivos/MovAltas.txt` o `archivos/Altas.xlsx`), pero la carpeta fue eliminada del disco de trabajo o no tiene permisos de lectura.
- **Solución:** Verificar que exista la carpeta `archivos/` y que el archivo haya sido previamente subido a través del formulario correspondiente.
