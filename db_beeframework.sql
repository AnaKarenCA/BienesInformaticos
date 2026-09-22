/*
Navicat MySQL Data Transfer

Source Server         : XAMPP
Source Server Version : 100427
Source Host           : localhost:3306
Source Database       : db_beeframework

Target Server Type    : MYSQL
Target Server Version : 100427
File Encoding         : 65001

Date: 2023-07-23 13:01:17
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for bee_users
-- ----------------------------
DROP TABLE IF EXISTS `bee_users`;
CREATE TABLE `bee_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auth_token` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of bee_users
-- ----------------------------
INSERT INTO `bee_users` VALUES ('1', '', 'bee', '$2y$10$xHEI5cJ3q7rBJaL.M9qBRe909ahHvIZVTfRRxlLqfnWwAYwWQE/Wu', 'jslocal@localhost.com', '2021-12-05 15:52:17');

-- ----------------------------
-- Table structure for options
-- ----------------------------
DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `option` varchar(255) DEFAULT NULL,
  `val` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Table structure for posts
-- ----------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) DEFAULT '',
  `id_padre` bigint(20) DEFAULT NULL,
  `id_usuario` bigint(20) DEFAULT NULL,
  `id_ref` bigint(20) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `permalink` varchar(255) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  `actualizado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of posts
-- ----------------------------

-- ----------------------------
-- Table structure for posts_meta
-- ----------------------------
DROP TABLE IF EXISTS `posts_meta`;
CREATE TABLE `posts_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_post` int(11) NOT NULL,
  `meta` varchar(255) DEFAULT NULL,
  `valor` text DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  `actualizado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of posts_meta
-- ----------------------------

-- ----------------------------
-- Table structure for pruebas
-- ----------------------------
DROP TABLE IF EXISTS `pruebas`;
CREATE TABLE `pruebas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT '',
  `titulo` varchar(255) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of pruebas
-- ----------------------------
INSERT INTO `pruebas` VALUES ('1', 'John Doe', 'Un post de prueba', 'Lorem ipsum dolorem.', '2021-12-10 10:55:41');
INSERT INTO `pruebas` VALUES ('2', 'Pancho Villa', 'Otro post nuevo', 'Lorem ipsum dolorem.', '2021-12-10 11:02:01');

-- ----------------------------
-- Table structure for products
-- ----------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT '',
  `slug` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `precio_comparacion` decimal(10,2) DEFAULT NULL,
  `stock` int(10) DEFAULT NULL,
  `rastrear_stock` tinyint(5) DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  `actualizado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of products
-- ----------------------------
INSERT INTO `productos` VALUES ('1', null, 'Pack de desarrollo web Full Stack', 'pack-de-desarrollo-full-stack', 'Un paquete con más de 20 cursos premium.', '300.00', '1000.00', '10', '1', 'packfullstack.png', '2023-08-10 07:52:50', '2023-08-11 09:37:31');
INSERT INTO `productos` VALUES ('2', null, 'Emprendepack', 'emprendepack', 'Paquete de cursos para emprendedores', '199.00', '500.00', null, '0', 'testimage.jpg', '2023-08-10 08:18:34', '2023-08-11 09:36:06');
INSERT INTO `productos` VALUES ('3', null, 'Curso crea un sistema escolar con PHP y MySQL', 'curso-crea-un-sistema-escolar', 'Lorel ipsum dolorem etsem.', '150.00', '799.00', null, '0', 'sistemaescolar.jpg', '2023-08-11 09:40:26', '2023-08-11 09:42:50');

-- ----------------------------
-- Table structure for bee_permisos
-- ----------------------------
DROP TABLE IF EXISTS `bee_permisos`;
CREATE TABLE `bee_permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of bee_permisos
-- ----------------------------
INSERT INTO `bee_permisos` VALUES ('1', 'Acceso de administrador', 'admin-access', 'Acceso general de administración', '2023-09-08 11:55:59');
INSERT INTO `bee_permisos` VALUES ('2', 'Listar productos', 'list-all-products', 'Listar todos los productos de la base de datos.', '2023-09-08 12:01:07');
INSERT INTO `bee_permisos` VALUES ('3', 'Agregar nuevos productos', 'add-products', 'Agregar productos a la base de datos.', '2023-09-08 12:28:40');

-- ----------------------------
-- Table structure for bee_roles
-- ----------------------------
DROP TABLE IF EXISTS `bee_roles`;
CREATE TABLE `bee_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of bee_roles
-- ----------------------------
INSERT INTO `bee_roles` VALUES ('1', 'Administrador general', 'admin', '2023-09-08 11:55:12');
INSERT INTO `bee_roles` VALUES ('2', 'Trabajador', 'worker', '2023-09-08 11:55:22');
INSERT INTO `bee_roles` VALUES ('3', 'Role de prueba', 'test', '2023-09-08 12:38:32');

-- ----------------------------
-- Table structure for bee_roles_permisos
-- ----------------------------
DROP TABLE IF EXISTS `bee_roles_permisos`;
CREATE TABLE `bee_roles_permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_role` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Records of bee_roles_permisos
-- ----------------------------
INSERT INTO `bee_roles_permisos` VALUES ('1', '1', '1');
INSERT INTO `bee_roles_permisos` VALUES ('2', '2', '2');
INSERT INTO `bee_roles_permisos` VALUES ('3', '2', '3');


















----------------------------
-- ----------------------------
-- ----------------------------
-- Base actualizada a 2026-09-17
----------------------------
----------------------------
----------------------------

-- ============================================================
-- BASE DE DATOS: bienes_informaticos
-- Motor: MySQL / MariaDB - Laragon
-- ============================================================

CREATE DATABASE IF NOT EXISTS bienes_informaticos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bienes_informaticos;


-- ============================================================
-- 1. UNIDAD ADMINISTRATIVA
-- ============================================================

CREATE TABLE unidad_administrativa (
    id_unidad INT AUTO_INCREMENT PRIMARY KEY,
    codigo_ua VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(250) NOT NULL,
    tipo CHAR(1) NULL COMMENT 'L = Localidad jerárquica, S = Subunidad',
    id_padre INT NULL,

    CONSTRAINT FK_ua_padre
        FOREIGN KEY (id_padre)
        REFERENCES unidad_administrativa(id_unidad)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- 2. ACTIVO GENÉRICO
-- ============================================================

CREATE TABLE activo_generico (
    id_activo_generico INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(250) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;


-- ============================================================
-- 3. GRUPO DE ACTIVO
-- ============================================================

CREATE TABLE grupo_activo (
    id_grupo_activo INT AUTO_INCREMENT PRIMARY KEY,
    id_activo_generico INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(250) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_grupo_activo_generico
        FOREIGN KEY (id_activo_generico)
        REFERENCES activo_generico(id_activo_generico)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================
-- 4. ACTIVO ESPECÍFICO
-- ============================================================

CREATE TABLE activo_especifico (
    id_activo_especifico INT AUTO_INCREMENT PRIMARY KEY,
    id_grupo_activo INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(250) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_activo_especifico_grupo
        FOREIGN KEY (id_grupo_activo)
        REFERENCES grupo_activo(id_grupo_activo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================
-- 5. MARCA
-- ============================================================

CREATE TABLE marca (
    id_marca INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;


-- ============================================================
-- 6. MODELO
-- ============================================================

CREATE TABLE modelo (
    id_modelo INT AUTO_INCREMENT PRIMARY KEY,
    id_marca INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_modelo_marca
        FOREIGN KEY (id_marca)
        REFERENCES marca(id_marca)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================
-- 7. ESTADO DE USO
-- ============================================================

CREATE TABLE estado_uso (
    id_estado_uso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;


INSERT INTO estado_uso (nombre)
VALUES
    ('Bueno'),
    ('Regular'),
    ('Malo');


-- ============================================================
-- 8. UBICACIÓN
-- ============================================================

CREATE TABLE ubicacion (
    id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
    municipio VARCHAR(100) NOT NULL,
    localidad VARCHAR(100) NOT NULL,
    ubicacion_fisica VARCHAR(500) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;


-- ============================================================
-- 9. BIEN
-- ============================================================

CREATE TABLE bien (
    id_bien INT AUTO_INCREMENT PRIMARY KEY,

    numero_inventario VARCHAR(20) NOT NULL UNIQUE,
    nic_cea VARCHAR(20) NULL,

    id_unidad INT NOT NULL,

    id_activo_especifico INT NULL,

    nombre_bien VARCHAR(250) NOT NULL,
    material VARCHAR(150) NULL,

    id_marca INT NULL,
    id_modelo INT NULL,

    color VARCHAR(100) NULL,
    id_estado_uso INT NULL,

    numero_serie VARCHAR(100) NULL,

    caracteristicas VARCHAR(500) NULL,
    observaciones VARCHAR(500) NULL,

    fecha_alta DATE NULL,
    fecha_adquisicion DATE NULL,
    fecha_elaboracion DATE NULL,
    fecha_asignacion DATE NULL,

    valor DECIMAL(18,2) NULL,

    id_ubicacion INT NULL,

    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    activo TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_bien_unidad
        FOREIGN KEY (id_unidad)
        REFERENCES unidad_administrativa(id_unidad)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_bien_activo_especifico
        FOREIGN KEY (id_activo_especifico)
        REFERENCES activo_especifico(id_activo_especifico)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_bien_marca
        FOREIGN KEY (id_marca)
        REFERENCES marca(id_marca)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_bien_modelo
        FOREIGN KEY (id_modelo)
        REFERENCES modelo(id_modelo)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_bien_estado
        FOREIGN KEY (id_estado_uso)
        REFERENCES estado_uso(id_estado_uso)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_bien_ubicacion
        FOREIGN KEY (id_ubicacion)
        REFERENCES ubicacion(id_ubicacion)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- 10. CÓDIGO DE BARRAS
-- ============================================================

CREATE TABLE codigo_barra (
    id_codigo_barra INT AUTO_INCREMENT PRIMARY KEY,

    id_bien INT NOT NULL,

    codigo VARCHAR(50) NOT NULL UNIQUE,

    tipo_codigo VARCHAR(30) NOT NULL DEFAULT 'CODE128',

    activo TINYINT(1) NOT NULL DEFAULT 1,

    fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT FK_codigo_barra_bien
        FOREIGN KEY (id_bien)
        REFERENCES bien(id_bien)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- 11. RESGUARDANTE
-- ============================================================

CREATE TABLE resguardante (
    id_resguardante INT AUTO_INCREMENT PRIMARY KEY,

    clave_interna VARCHAR(18) NOT NULL UNIQUE,

    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NULL,

    csp VARCHAR(9) NULL,

    notas VARCHAR(250) NULL,

    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;


-- ============================================================
-- 12. RESGUARDO
-- ============================================================

CREATE TABLE resguardo (
    id_resguardo INT AUTO_INCREMENT PRIMARY KEY,

    id_bien INT NOT NULL,
    id_resguardante INT NOT NULL,

    tipo_equipo VARCHAR(50) NULL,

    estado_equipo VARCHAR(50) NULL,

    observaciones VARCHAR(250) NULL,

    candado CHAR(2) NULL,

    fecha_asignacion DATE NULL,
    fecha_devolucion DATE NULL,

    activo TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_resguardo_bien
        FOREIGN KEY (id_bien)
        REFERENCES bien(id_bien)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_resguardo_resguardante
        FOREIGN KEY (id_resguardante)
        REFERENCES resguardante(id_resguardante)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================
-- 13. COMPONENTE
-- ============================================================

CREATE TABLE componente (
    id_componente INT AUTO_INCREMENT PRIMARY KEY,

    id_bien INT NOT NULL,

    tipo_componente VARCHAR(50) NOT NULL,

    modelo VARCHAR(100) NULL,
    marca VARCHAR(100) NULL,
    numero_serie VARCHAR(100) NULL,
    numero_inventario VARCHAR(50) NULL,

    activo TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_componente_bien
        FOREIGN KEY (id_bien)
        REFERENCES bien(id_bien)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- 14. MOVIMIENTO DEL BIEN
-- ============================================================

CREATE TABLE movimiento_bien (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,

    id_bien INT NOT NULL,

    tipo_movimiento VARCHAR(50) NOT NULL,

    ubicacion_anterior INT NULL,
    ubicacion_nueva INT NULL,

    resguardante_anterior INT NULL,
    resguardante_nuevo INT NULL,

    fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    motivo VARCHAR(250) NULL,

    observaciones VARCHAR(500) NULL,

    CONSTRAINT FK_movimiento_bien
        FOREIGN KEY (id_bien)
        REFERENCES bien(id_bien)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT FK_movimiento_ubicacion_anterior
        FOREIGN KEY (ubicacion_anterior)
        REFERENCES ubicacion(id_ubicacion)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_movimiento_ubicacion_nueva
        FOREIGN KEY (ubicacion_nueva)
        REFERENCES ubicacion(id_ubicacion)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_movimiento_resguardante_anterior
        FOREIGN KEY (resguardante_anterior)
        REFERENCES resguardante(id_resguardante)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT FK_movimiento_resguardante_nuevo
        FOREIGN KEY (resguardante_nuevo)
        REFERENCES resguardante(id_resguardante)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- 15. BAJA DE RESGUARDO
-- ============================================================

CREATE TABLE baja_resguardo (
    id_baja INT AUTO_INCREMENT PRIMARY KEY,

    id_resguardo INT NOT NULL,
    id_bien INT NOT NULL,

    fecha_baja DATE NOT NULL,

    motivo VARCHAR(250) NULL,

    numero_monitor VARCHAR(50) NULL,
    numero_serie_cpu VARCHAR(50) NULL,
    numero_serie_teclado VARCHAR(50) NULL,
    numero_serie_mouse VARCHAR(50) NULL,
    numero_serie_ups_cargador VARCHAR(50) NULL,

    observaciones VARCHAR(500) NULL,

    CONSTRAINT FK_baja_resguardo
        FOREIGN KEY (id_resguardo)
        REFERENCES resguardo(id_resguardo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_baja_bien
        FOREIGN KEY (id_bien)
        REFERENCES bien(id_bien)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE movimiento_bien
  ADD COLUMN id_resguardo_anterior INT NULL AFTER resguardante_nuevo,
  ADD COLUMN id_resguardo_nuevo INT NULL AFTER id_resguardo_anterior,
  ADD CONSTRAINT FK_movimiento_resguardo_anterior FOREIGN KEY (id_resguardo_anterior) REFERENCES resguardo(id_resguardo) ON UPDATE CASCADE ON DELETE SET NULL,
  ADD CONSTRAINT FK_movimiento_resguardo_nuevo FOREIGN KEY (id_resguardo_nuevo) REFERENCES resguardo(id_resguardo) ON UPDATE CASCADE ON DELETE SET NULL;