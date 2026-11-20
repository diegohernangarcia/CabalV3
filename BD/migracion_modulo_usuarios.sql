-- =============================================================================
-- MIGRACIÓN BASE DE DATOS: MÓDULO DE USUARIOS, ROLES Y AUTENTICACIÓN
-- Sistema Cabal FSR - Programa Familia Rionegrina Solidaria (SeNAF Río Negro)
-- Base de Datos: cabal
-- Compatibilidad: MariaDB 10.x+ / MySQL 5.7+ / PHP 5.6.40+
-- Fecha: 19 de Noviembre de 2026
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. TABLA: roles
-- Catálogo de perfiles y niveles de autorización dentro del sistema
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(30) NOT NULL UNIQUE,
    `nombre` VARCHAR(60) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `permisos_json` TEXT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Población inicial de roles institucionales
INSERT INTO `roles` (`id`, `codigo`, `nombre`, `descripcion`, `permisos_json`) 
VALUES 
    (1, 'ADMIN', 'Administrador General', 'Control total del sistema, administración de usuarios, auditoría y operaciones bancarias completas.', '{"usuarios_crud":true,"lotes_generar":true,"archivos_subir":true,"padron_editar":true,"reportes_ver":true,"auditoria_ver":true}'),
    (2, 'OPERADOR', 'Operador de Carga y Lotes', 'Emisión y procesamiento de lotes bancarios (NOVCAR, ALTAS, NOVTRAN), importación de Excel e informes EML.', '{"usuarios_crud":false,"lotes_generar":true,"archivos_subir":true,"padron_editar":true,"reportes_ver":true,"auditoria_ver":false}'),
    (3, 'AUDITOR', 'Auditor / Consulta', 'Visualización de padrón, consulta individual de beneficiarios y emisión de reportes mensuales de consumo.', '{"usuarios_crud":false,"lotes_generar":false,"archivos_subir":false,"padron_editar":false,"reportes_ver":true,"auditoria_ver":true}')
ON DUPLICATE KEY UPDATE 
    `nombre` = VALUES(`nombre`), 
    `descripcion` = VALUES(`descripcion`), 
    `permisos_json` = VALUES(`permisos_json`);

-- -----------------------------------------------------------------------------
-- 2. TABLA: usuarios
-- Cuentas de acceso al sistema con autenticación cifrada (bcrypt) y rate limiting
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rol` INT NOT NULL,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `nombre_completo` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `intentos_fallidos` INT NOT NULL DEFAULT 0,
    `bloqueado_hasta` DATETIME NULL DEFAULT NULL,
    `ultimo_login` DATETIME NULL DEFAULT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Alta inicial del usuario Administrador General (dgarcia / 78900)
-- Hash BCRYPT generado con algoritmo estándar compatible con password_verify() de PHP
INSERT INTO `usuarios` (`id`, `id_rol`, `usuario`, `email`, `nombre_completo`, `password_hash`, `activo`)
VALUES (
    1, 
    1, 
    'dgarcia', 
    'dgarcia@senaf.rionegro.gov.ar', 
    'Diego Garcia', 
    '$2y$12$cFdr0d5SXuQhqTHVv7GLoOgm0zT3GuAjRuGzXJftWZUSsF1v7fz1G', 
    1
)
ON DUPLICATE KEY UPDATE 
    `email` = VALUES(`email`),
    `nombre_completo` = VALUES(`nombre_completo`),
    `activo` = 1;

-- -----------------------------------------------------------------------------
-- 3. TABLA: recuperacion_tokens
-- Tokens criptográficos temporales de un solo uso para recuperación de contraseña
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recuperacion_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `expira_en` DATETIME NOT NULL,
    `utilizado` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_solicitud` VARCHAR(45) NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------------------------------
-- 4. TABLA: auditoria_accesos
-- Registro de seguridad y trazabilidad de eventos de autenticación
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria_accesos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NULL,
    `usuario_ingresado` VARCHAR(50) NOT NULL,
    `accion` VARCHAR(50) NOT NULL, -- 'LOGIN_OK', 'LOGIN_FALLIDO', 'LOGOUT', 'BLOQUEADO', 'RECUPERAR_SOLICITUD', 'CLAVE_RESTABLECIDA'
    `ip_origen` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_usuario_fecha` (`usuario_ingresado`, `fecha_hora`),
    INDEX `idx_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
