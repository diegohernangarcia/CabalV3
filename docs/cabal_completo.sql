-- =============================================================================
-- NEXO FRS - SISTEMA DE GESTIÓN CABAL ENTIDAD 782
-- SeNAF Río Negro • Programa Familia Rionegrina Solidaria
-- 
-- SCRIPT COMPLETO DE ESTRUCTURA E IMPORTACIÓN LIMPIA DE BASE DE DATOS
-- Ubicación: docs/cabal_completo.sql
-- Base de Datos: cabal
-- Compatibilidad: MySQL 5.7+ / MariaDB 10.2+ / XAMPP LAMPP (PHP 5.6 a 8.x)
-- Codificación: UTF-8 sin BOM (utf8mb4)
-- 
-- Características del Script:
-- 1. Estructura DDL completa de todas las 11 tablas del sistema.
-- 2. Padrón de Beneficiarios (tabla 'beneficiario') y tablas transaccionales
--    exportadas VACÍAS (sin datos personales ni consumos bancarios).
-- 3. Población inicial de catálogo de ROLES institucionales (ADMIN, OPERADOR, AUDITOR).
-- 4. Inserción exclusiva de las 2 cuentas de USUARIOS administradores activos.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- -----------------------------------------------------------------------------
-- 0. CREACIÓN Y SELECCIÓN DE BASE DE DATOS
-- -----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `cabal` 
    DEFAULT CHARACTER SET utf8 
    COLLATE utf8_general_ci;

USE `cabal`;

-- =============================================================================
-- SECCIÓN A: MÓDULO DE AUTENTICACIÓN, ROLES Y AUDITORÍA
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. TABLA: roles
-- Catálogo de perfiles institucionales y permisos en formato JSON
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `permisos_json` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- Datos iniciales de roles
INSERT INTO `roles` (`id`, `codigo`, `nombre`, `descripcion`, `permisos_json`, `creado_en`) VALUES
(1, 'ADMIN', 'Administrador General', 'Control total del sistema, administración de usuarios y operaciones bancarias completas.', '{\"usuarios_crud\":true,\"lotes_generar\":true,\"archivos_subir\":true,\"padron_editar\":true,\"reportes_ver\":true,\"auditoria_ver\":true}', '2026-11-19 13:32:56'),
(2, 'OPERADOR', 'Operador de Carga y Lotes', 'Emisión y procesamiento de lotes bancarios (NOVCAR, ALTAS, NOVTRAN), importación e informes EML.', '{\"usuarios_crud\":false,\"lotes_generar\":true,\"archivos_subir\":true,\"padron_editar\":true,\"reportes_ver\":true,\"auditoria_ver\":false}', '2026-11-19 13:32:56'),
(3, 'AUDITOR', 'Auditor / Consulta', 'Visualización de padrón, consulta individual de beneficiarios y emisión de reportes mensuales.', '{\"usuarios_crud\":false,\"lotes_generar\":false,\"archivos_subir\":false,\"padron_editar\":false,\"reportes_ver\":true,\"auditoria_ver\":true}', '2026-11-19 13:32:56');

-- -----------------------------------------------------------------------------
-- 2. TABLA: usuarios
-- Cuentas de operadores y administradores autorizados con hashes BCRYPT
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_rol` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `intentos_fallidos` int(11) NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuarios_rol` (`id_rol`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- Cuentas activas del sistema (2 usuarios administradores creados)
INSERT INTO `usuarios` (`id`, `id_rol`, `usuario`, `email`, `nombre_completo`, `password_hash`, `activo`, `intentos_fallidos`, `bloqueado_hasta`, `ultimo_login`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 'dgarcia', 'dgarcia@senaf.rionegro.gov.ar', 'Diego Garcia', '$2y$10$E9gkHflJWU68ckzlzKiwueM./L.YODZex3bR.F/bHNWP.jq4xgVHe', 1, 0, NULL, '2026-11-20 07:45:29', '2026-11-19 13:32:56', '2026-11-20 07:45:29'),
(2, 1, 'pucho', 'diegohernangarcia@gmail.com', 'Diego Garcia', '$2y$10$YIlQrghm3fRzwuK.vCo5s.sw5Vfs5Gy67hqWnAfx17NcNuoBE2xUq', 1, 0, NULL, '2026-11-20 10:38:17', '2026-11-19 13:37:18', '2026-11-20 10:38:17');

-- -----------------------------------------------------------------------------
-- 3. TABLA: auditoria_accesos
-- Registro de ingresos, fallos de clave y operaciones del sistema
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `auditoria_accesos`;
CREATE TABLE `auditoria_accesos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `usuario_ingresado` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_fecha` (`usuario_ingresado`,`fecha_hora`),
  KEY `idx_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------------------------------
-- 4. TABLA: recuperacion_tokens
-- Tokens temporales para restablecimiento seguro de claves
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `recuperacion_tokens`;
CREATE TABLE `recuperacion_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `utilizado` tinyint(1) NOT NULL DEFAULT '0',
  `ip_solicitud` varchar(45) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_tokens_usuario` (`id_usuario`),
  CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =============================================================================
-- SECCIÓN B: ENTIDADES MAESTRAS Y PADRÓN DE BENEFICIARIOS (SIN DATOS)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 5. TABLA: beneficiario
-- Padrón principal de titulares del Programa Familia Rionegrina Solidaria.
-- NOTA DE PRIVACIDAD: Exportada SIN DATOS conforme a la directiva de seguridad.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `beneficiario`;
CREATE TABLE `beneficiario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Apellido` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Nombre` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Calle` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Numero` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Piso` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Departamento` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Localidad` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Provincia` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Sexo` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EntidadEmisora` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FechaProceso` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `NumeroCuenta` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NumeroTarjeta` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CodigoPostal` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TelefonoCaracteristica` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TelefonoNumero` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EstadoCivil` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FechaNacimiento` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TipoDocumento` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NumeroDocumento` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ProvinciaDocumento` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Cuit` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CodigoIngreso` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CodigoRechazo` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABLA: MasterTarjetas
-- Registro maestro de tarjetas y plásticos emitidos por Cabal (MAESPLA).
-- NOTA: Exportada sin registros transaccionales.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `MasterTarjetas`;
CREATE TABLE `MasterTarjetas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `NumeroCuenta` bigint(20) NOT NULL,
  `NumeroTarjeta` bigint(20) NOT NULL,
  `NombreApellido` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `TipoDocumento` int(11) NOT NULL,
  `NroDocumento` bigint(20) NOT NULL,
  `Causa` varchar(25) COLLATE utf8_spanish_ci NOT NULL,
  `FechaAlta` date NOT NULL,
  `FechaEmision` date NOT NULL,
  `FechaVencimiento` date NOT NULL,
  `Archivo` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- =============================================================================
-- SECCIÓN C: TABLAS OPERATIVAS Y DE PASO (LOTES, NOVCAR, NOVTRAN, ACREDITACIONES)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 7. TABLA: acreditar
-- Tabla de staging / paso para procesamiento de lotes de acreditación mensual
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `acreditar`;
CREATE TABLE `acreditar` (
  `id` int(11) NOT NULL,
  `id_beneficiario` int(11) NOT NULL,
  `Apellido` varchar(50) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `NumeroCuenta` varchar(50) NOT NULL,
  `ImporteCompra` varchar(50) NOT NULL,
  `ImporteCajero` varchar(50) NOT NULL,
  `DNI` varchar(50) NOT NULL,
  `Fecha` varchar(50) NOT NULL,
  `Hora` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- -----------------------------------------------------------------------------
-- 8. TABLA: acreditar2
-- Tabla de staging para el nuevo circuito de acreditaciones (AcreditarCSV)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `acreditar2`;
CREATE TABLE `acreditar2` (
  `id` int(11) NOT NULL,
  `id_beneficiario` int(11) NOT NULL,
  `Apellido` varchar(50) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `NumeroCuenta` varchar(50) NOT NULL,
  `ImporteCompra` varchar(50) NOT NULL,
  `ImporteCajero` varchar(50) NOT NULL,
  `DNI` varchar(50) NOT NULL,
  `Fecha` varchar(50) NOT NULL,
  `Hora` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- -----------------------------------------------------------------------------
-- 9. TABLA: acreditarmanual
-- Tabla de paso para acreditaciones individuales/manuales
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `acreditarmanual`;
CREATE TABLE `acreditarmanual` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_beneficiario` int(11) NOT NULL,
  `Apellido` varchar(50) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `NumeroCuenta` varchar(50) NOT NULL,
  `ImporteCompra` varchar(50) NOT NULL,
  `ImporteCajero` varchar(50) NOT NULL,
  `DNI` varchar(50) NOT NULL,
  `Fecha` varchar(50) NOT NULL,
  `Hora` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- -----------------------------------------------------------------------------
-- 10. TABLA: altas
-- Tabla temporal de staging para importación de altas desde planillas Excel
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `altas`;
CREATE TABLE `altas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Apellido` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Nombre` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DomicilioCalle` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DomicilioNumero` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DomicilioPiso` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DomicilioDepartamento` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Localidad` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Provincia` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Sexo` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CodigoPostal` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TelefonoCaracteristica` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TelefonoNumero` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `EstadoCivil` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `FechaNacimiento` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DocumentoTipo` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DocumentoNumero` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DocumentoProvincia` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CUIT` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. TABLA: movimientos
-- Histórico consolidado de movimientos y consumos bancarios (NOVCAR / NOVTRAN)
-- NOTA: Exportada vacía para garantizar privacidad de consumos.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- -----------------------------------------------------------------------------
-- FINALIZACIÓN DE TRANSACCIÓN Y RESTABLECIMIENTO DE CHECKS
-- -----------------------------------------------------------------------------
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
