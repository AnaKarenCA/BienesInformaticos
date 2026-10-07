-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: bienes_informaticos
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `bienes_informaticos`
--

/*!40000 DROP DATABASE IF EXISTS `bienes_informaticos`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bienes_informaticos` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `bienes_informaticos`;

--
-- Table structure for table `activo_especifico`
--

DROP TABLE IF EXISTS `activo_especifico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activo_especifico` (
  `id_activo_especifico` int NOT NULL AUTO_INCREMENT,
  `id_grupo_activo` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_activo_especifico`),
  KEY `FK_activo_especifico_grupo` (`id_grupo_activo`),
  CONSTRAINT `FK_activo_especifico_grupo` FOREIGN KEY (`id_grupo_activo`) REFERENCES `grupo_activo` (`id_grupo_activo`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activo_especifico`
--

LOCK TABLES `activo_especifico` WRITE;
/*!40000 ALTER TABLE `activo_especifico` DISABLE KEYS */;
INSERT INTO `activo_especifico` VALUES (1,1,'1',NULL,1),(2,1,'LAPTOP','Computadora portatil',1),(3,1,'COMPUTADORA DE ESCRITORIO','Equipo de computo de escritorio',1),(4,2,'IMPRESORA LASER','Impresora laser para oficina',1);
/*!40000 ALTER TABLE `activo_especifico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activo_generico`
--

DROP TABLE IF EXISTS `activo_generico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activo_generico` (
  `id_activo_generico` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_activo_generico`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activo_generico`
--

LOCK TABLES `activo_generico` WRITE;
/*!40000 ALTER TABLE `activo_generico` DISABLE KEYS */;
INSERT INTO `activo_generico` VALUES (1,'EQUIPO DE COMPUTO','Equipo de computo',1),(2,'EQUIPO DE COMPUTO','Equipo de computo',1),(3,'EQUIPO DE IMPRESION','Equipos destinados a impresion de documentos',1),(4,'EQUIPO DE COMUNICACION','Equipos utilizados para comunicacion y conectividad',1);
/*!40000 ALTER TABLE `activo_generico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `baja_resguardo`
--

DROP TABLE IF EXISTS `baja_resguardo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `baja_resguardo` (
  `id_baja` int NOT NULL AUTO_INCREMENT,
  `id_resguardo` int NOT NULL,
  `id_bien` int NOT NULL,
  `fecha_baja` date NOT NULL,
  `motivo` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_monitor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_cpu` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_teclado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_mouse` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_ups_cargador` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_baja`),
  KEY `FK_baja_resguardo` (`id_resguardo`),
  KEY `FK_baja_bien` (`id_bien`),
  CONSTRAINT `FK_baja_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_baja_resguardo` FOREIGN KEY (`id_resguardo`) REFERENCES `resguardo` (`id_resguardo`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `baja_resguardo`
--

LOCK TABLES `baja_resguardo` WRITE;
/*!40000 ALTER TABLE `baja_resguardo` DISABLE KEYS */;
/*!40000 ALTER TABLE `baja_resguardo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bee_permisos`
--

DROP TABLE IF EXISTS `bee_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bee_permisos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_permisos`
--

LOCK TABLES `bee_permisos` WRITE;
/*!40000 ALTER TABLE `bee_permisos` DISABLE KEYS */;
INSERT INTO `bee_permisos` VALUES (4,'Guardar bienes','bienes-guardar','Registrar y editar bienes.','2026-09-22 11:43:25'),(5,'Inactivar bienes','bienes-inactivar','Cambiar el estado activo de bienes.','2026-09-22 11:43:25'),(6,'Gestionar cat├ílogos','catalogos-gestionar','Administrar cat├ílogos del inventario.','2026-09-22 11:43:25'),(7,'Exportar reportes','reportes-exportar','Exportar el inventario a PDF.','2026-09-22 11:43:25'),(8,'Generar documentos','documentos-generar','Generar tarjetas, resguardos y bajas.','2026-09-22 11:43:25'),(9,'Acceso de administrador','admin-access','Acceso total a la administración.','2026-09-22 11:43:45');
/*!40000 ALTER TABLE `bee_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bee_roles`
--

DROP TABLE IF EXISTS `bee_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bee_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_roles`
--

LOCK TABLES `bee_roles` WRITE;
/*!40000 ALTER TABLE `bee_roles` DISABLE KEYS */;
INSERT INTO `bee_roles` VALUES (4,'Administrador de inventario','admin','2026-09-22 11:43:25'),(5,'Operador de inventario','inventario','2026-09-22 11:43:25');
/*!40000 ALTER TABLE `bee_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bee_roles_permisos`
--

DROP TABLE IF EXISTS `bee_roles_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bee_roles_permisos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_role` int NOT NULL,
  `id_permiso` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_roles_permisos`
--

LOCK TABLES `bee_roles_permisos` WRITE;
/*!40000 ALTER TABLE `bee_roles_permisos` DISABLE KEYS */;
INSERT INTO `bee_roles_permisos` VALUES (4,5,4),(5,5,5),(6,5,6),(7,5,7),(8,5,8),(11,4,9);
/*!40000 ALTER TABLE `bee_roles_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bee_users`
--

DROP TABLE IF EXISTS `bee_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bee_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `auth_token` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'inventario',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_users`
--

LOCK TABLES `bee_users` WRITE;
/*!40000 ALTER TABLE `bee_users` DISABLE KEYS */;
INSERT INTO `bee_users` VALUES (1,'$2y$10$531DhkknebQJtCufLN91t.C7tbftiMGRYwukPgDPgAJx35gfeevJu','bee','$2y$10$xHEI5cJ3q7rBJaL.M9qBRe909ahHvIZVTfRRxlLqfnWwAYwWQE/Wu','jslocal@localhost.com',NULL,NULL,'admin',1,'2021-12-05 15:52:17');
/*!40000 ALTER TABLE `bee_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bien`
--

DROP TABLE IF EXISTS `bien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bien` (
  `id_bien` int NOT NULL AUTO_INCREMENT,
  `numero_inventario` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nic_cea` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_unidad` int NOT NULL,
  `id_activo_especifico` int DEFAULT NULL,
  `nombre_bien` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `material` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_marca` int DEFAULT NULL,
  `id_modelo` int DEFAULT NULL,
  `color` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_estado_uso` int DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caracteristicas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_alta` date DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_elaboracion` date DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `valor` decimal(18,2) DEFAULT NULL,
  `id_ubicacion` int DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_bien`),
  UNIQUE KEY `numero_inventario` (`numero_inventario`),
  KEY `FK_bien_unidad` (`id_unidad`),
  KEY `FK_bien_activo_especifico` (`id_activo_especifico`),
  KEY `FK_bien_marca` (`id_marca`),
  KEY `FK_bien_modelo` (`id_modelo`),
  KEY `FK_bien_estado` (`id_estado_uso`),
  KEY `FK_bien_ubicacion` (`id_ubicacion`),
  KEY `idx_bien_inventario` (`numero_inventario`),
  KEY `idx_bien_serie` (`numero_serie`),
  KEY `idx_bien_activo` (`activo`),
  CONSTRAINT `FK_bien_activo_especifico` FOREIGN KEY (`id_activo_especifico`) REFERENCES `activo_especifico` (`id_activo_especifico`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_estado` FOREIGN KEY (`id_estado_uso`) REFERENCES `estado_uso` (`id_estado_uso`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_marca` FOREIGN KEY (`id_marca`) REFERENCES `marca` (`id_marca`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `modelo` (`id_modelo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_administrativa` (`id_unidad`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bien`
--

LOCK TABLES `bien` WRITE;
/*!40000 ALTER TABLE `bien` DISABLE KEYS */;
INSERT INTO `bien` VALUES (1,'BI-000001','NIC-000001',10,2,'Computadora de escritorio','Metal y plastico',1,1,'Negro',1,'SERIE-HP-000001','Core i5, 16 GB RAM, SSD 512 GB','Equipo de prueba para inventario','2026-09-01','2026-08-20','2026-08-25','2026-09-01',18500.00,1,'2026-09-22 14:09:30',1),(2,'BI-000002','NIC-000002',10,1,'Laptop','Metal y plastico',2,2,'Gris',1,'SERIE-DELL-000002','Core i5, 16 GB RAM, SSD 512 GB','Equipo portatil de prueba','2026-09-02','2026-08-21','2026-08-26','2026-09-02',22000.00,2,'2026-09-22 14:09:30',1),(3,'BI-000003','NIC-000003',21,3,'Impresora','Plastico',3,3,'Negro',2,'SERIE-EPSON-000003','Impresora multifuncional','Equipo de prueba','2026-09-03','2026-08-22','2026-08-27',NULL,6500.00,3,'2026-09-22 14:09:30',1);
/*!40000 ALTER TABLE `bien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codigo_barra`
--

DROP TABLE IF EXISTS `codigo_barra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `codigo_barra` (
  `id_codigo_barra` int NOT NULL AUTO_INCREMENT,
  `id_bien` int NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CODE128',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_codigo_barra`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `FK_codigo_barra_bien` (`id_bien`),
  CONSTRAINT `FK_codigo_barra_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codigo_barra`
--

LOCK TABLES `codigo_barra` WRITE;
/*!40000 ALTER TABLE `codigo_barra` DISABLE KEYS */;
INSERT INTO `codigo_barra` VALUES (1,1,'BI-000001','CODE128',1,'2026-09-22 14:09:54'),(2,2,'BI-000002','CODE128',1,'2026-09-22 14:09:54'),(3,3,'BI-000003','CODE128',1,'2026-09-22 14:09:54');
/*!40000 ALTER TABLE `codigo_barra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `componente`
--

DROP TABLE IF EXISTS `componente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `componente` (
  `id_componente` int NOT NULL AUTO_INCREMENT,
  `id_bien` int NOT NULL,
  `tipo_componente` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_inventario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_componente`),
  KEY `FK_componente_bien` (`id_bien`),
  CONSTRAINT `FK_componente_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `componente`
--

LOCK TABLES `componente` WRITE;
/*!40000 ALTER TABLE `componente` DISABLE KEYS */;
INSERT INTO `componente` VALUES (1,1,'Monitor','P2422H','DELL','MON-000001','COMP-000001',1),(2,1,'Teclado','KB216','DELL','TEC-000001','COMP-000002',1),(3,2,'Cargador','65W USB-C','DELL','CAR-000001','COMP-000003',1);
/*!40000 ALTER TABLE `componente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado_uso`
--

DROP TABLE IF EXISTS `estado_uso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_uso` (
  `id_estado_uso` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_estado_uso`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_uso`
--

LOCK TABLES `estado_uso` WRITE;
/*!40000 ALTER TABLE `estado_uso` DISABLE KEYS */;
INSERT INTO `estado_uso` VALUES (1,'Bueno'),(3,'Malo'),(2,'Regular');
/*!40000 ALTER TABLE `estado_uso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_activo`
--

DROP TABLE IF EXISTS `grupo_activo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_activo` (
  `id_grupo_activo` int NOT NULL AUTO_INCREMENT,
  `id_activo_generico` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_grupo_activo`),
  KEY `FK_grupo_activo_generico` (`id_activo_generico`),
  CONSTRAINT `FK_grupo_activo_generico` FOREIGN KEY (`id_activo_generico`) REFERENCES `activo_generico` (`id_activo_generico`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_activo`
--

LOCK TABLES `grupo_activo` WRITE;
/*!40000 ALTER TABLE `grupo_activo` DISABLE KEYS */;
INSERT INTO `grupo_activo` VALUES (1,1,'EQUIPO DE COMPUTO',NULL,1),(2,1,'COMPUTADORAS','Equipos de computo personales',1),(3,2,'IMPRESORAS','Equipos de impresion',1),(4,3,'REDES','Equipos de comunicacion y conectividad',1),(5,1,'COMPUTADORAS','Equipos de computo personales',1),(6,2,'IMPRESORAS','Equipos de impresion',1),(7,3,'REDES','Equipos de comunicacion y conectividad',1);
/*!40000 ALTER TABLE `grupo_activo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marca`
--

DROP TABLE IF EXISTS `marca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marca` (
  `id_marca` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_marca`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marca`
--

LOCK TABLES `marca` WRITE;
/*!40000 ALTER TABLE `marca` DISABLE KEYS */;
INSERT INTO `marca` VALUES (1,'HP',1),(2,'DELL',1),(3,'LENOVO',1),(4,'EPSON',1);
/*!40000 ALTER TABLE `marca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modelo`
--

DROP TABLE IF EXISTS `modelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelo` (
  `id_modelo` int NOT NULL AUTO_INCREMENT,
  `id_marca` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_modelo`),
  KEY `FK_modelo_marca` (`id_marca`),
  CONSTRAINT `FK_modelo_marca` FOREIGN KEY (`id_marca`) REFERENCES `marca` (`id_marca`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modelo`
--

LOCK TABLES `modelo` WRITE;
/*!40000 ALTER TABLE `modelo` DISABLE KEYS */;
INSERT INTO `modelo` VALUES (1,1,'S1922',1),(2,1,'LATITUDE 5420',1),(3,2,'THINKCENTRE M70Q',1),(4,3,'ECOTANK L3250',1);
/*!40000 ALTER TABLE `modelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_bien`
--

DROP TABLE IF EXISTS `movimiento_bien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimiento_bien` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `id_bien` int NOT NULL,
  `tipo_movimiento` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion_anterior` int DEFAULT NULL,
  `ubicacion_nueva` int DEFAULT NULL,
  `resguardante_anterior` int DEFAULT NULL,
  `resguardante_nuevo` int DEFAULT NULL,
  `id_resguardo_anterior` int DEFAULT NULL,
  `id_resguardo_nuevo` int DEFAULT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `FK_movimiento_ubicacion_anterior` (`ubicacion_anterior`),
  KEY `FK_movimiento_ubicacion_nueva` (`ubicacion_nueva`),
  KEY `FK_movimiento_resguardante_anterior` (`resguardante_anterior`),
  KEY `FK_movimiento_resguardante_nuevo` (`resguardante_nuevo`),
  KEY `FK_movimiento_resguardo_anterior` (`id_resguardo_anterior`),
  KEY `FK_movimiento_resguardo_nuevo` (`id_resguardo_nuevo`),
  KEY `idx_movimiento_bien_fecha` (`id_bien`,`fecha_movimiento`),
  CONSTRAINT `FK_movimiento_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardante_anterior` FOREIGN KEY (`resguardante_anterior`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardante_nuevo` FOREIGN KEY (`resguardante_nuevo`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardo_anterior` FOREIGN KEY (`id_resguardo_anterior`) REFERENCES `resguardo` (`id_resguardo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardo_nuevo` FOREIGN KEY (`id_resguardo_nuevo`) REFERENCES `resguardo` (`id_resguardo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_ubicacion_anterior` FOREIGN KEY (`ubicacion_anterior`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_ubicacion_nueva` FOREIGN KEY (`ubicacion_nueva`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_bien`
--

LOCK TABLES `movimiento_bien` WRITE;
/*!40000 ALTER TABLE `movimiento_bien` DISABLE KEYS */;
INSERT INTO `movimiento_bien` VALUES (1,1,'ASIGNACION',NULL,1,NULL,1,NULL,1,'2026-09-01 09:00:00','Asignacion inicial','Registro inicial del bien'),(2,2,'ASIGNACION',NULL,2,NULL,2,NULL,2,'2026-09-02 10:00:00','Asignacion inicial','Registro inicial del bien'),(3,3,'ASIGNACION',NULL,3,NULL,3,NULL,3,'2026-09-03 11:00:00','Asignacion inicial','Registro inicial del bien');
/*!40000 ALTER TABLE `movimiento_bien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `options`
--

DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `option` varchar(255) DEFAULT NULL,
  `val` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `options`
--

LOCK TABLES `options` WRITE;
/*!40000 ALTER TABLE `options` DISABLE KEYS */;
/*!40000 ALTER TABLE `options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT '',
  `id_padre` bigint DEFAULT NULL,
  `id_usuario` bigint DEFAULT NULL,
  `id_ref` bigint DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `permalink` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `contenido` text COLLATE utf8mb3_unicode_ci,
  `status` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creado` datetime DEFAULT NULL,
  `actualizado` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pruebas`
--

DROP TABLE IF EXISTS `pruebas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pruebas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT '',
  `titulo` varchar(255) DEFAULT NULL,
  `contenido` text,
  `creado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pruebas`
--

LOCK TABLES `pruebas` WRITE;
/*!40000 ALTER TABLE `pruebas` DISABLE KEYS */;
/*!40000 ALTER TABLE `pruebas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resguardante`
--

DROP TABLE IF EXISTS `resguardante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resguardante` (
  `id_resguardante` int NOT NULL AUTO_INCREMENT,
  `clave_interna` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_paterno` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_materno` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `csp` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_resguardante`),
  UNIQUE KEY `clave_interna` (`clave_interna`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resguardante`
--

LOCK TABLES `resguardante` WRITE;
/*!40000 ALTER TABLE `resguardante` DISABLE KEYS */;
INSERT INTO `resguardante` VALUES (1,'RES-000001','Juan','Martinez','Lopez','CSP000001','Resguardante de prueba 1',1),(2,'RES-000002','Maria','Hernandez','Garcia','CSP000002','Resguardante de prueba 2',1),(3,'RES-000003','Carlos','Ramirez','Sanchez','CSP000003','Resguardante de prueba 3',1);
/*!40000 ALTER TABLE `resguardante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resguardo`
--

DROP TABLE IF EXISTS `resguardo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resguardo` (
  `id_resguardo` int NOT NULL AUTO_INCREMENT,
  `id_bien` int NOT NULL,
  `id_resguardante` int NOT NULL,
  `tipo_equipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_equipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `candado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_resguardo`),
  KEY `FK_resguardo_resguardante` (`id_resguardante`),
  KEY `idx_resguardo_vigente` (`id_bien`,`activo`),
  CONSTRAINT `FK_resguardo_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_resguardo_resguardante` FOREIGN KEY (`id_resguardante`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resguardo`
--

LOCK TABLES `resguardo` WRITE;
/*!40000 ALTER TABLE `resguardo` DISABLE KEYS */;
INSERT INTO `resguardo` VALUES (1,1,1,'Computadora de escritorio','Bueno','Equipo asignado para actividades administrativas','NO','2026-09-01',NULL,1),(2,2,2,'Laptop','Bueno','Equipo portatil asignado al resguardante','SI','2026-09-02',NULL,1),(3,3,3,'Impresora','Regular','Equipo compartido para impresion','NO','2026-09-03',NULL,1);
/*!40000 ALTER TABLE `resguardo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicacion`
--

DROP TABLE IF EXISTS `ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ubicacion` (
  `id_ubicacion` int NOT NULL AUTO_INCREMENT,
  `municipio` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localidad` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion_fisica` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicacion`
--

LOCK TABLES `ubicacion` WRITE;
/*!40000 ALTER TABLE `ubicacion` DISABLE KEYS */;
INSERT INTO `ubicacion` VALUES (1,'TOLUCA','TOLUCA',NULL,1),(2,'TOLUCA','TOLUCA','Departamento de Tecnologias de la Informacion',1),(3,'TOLUCA','TOLUCA','Departamento de Recursos Humanos',1),(4,'TOLUCA','TOLUCA','Departamento de Recursos Materiales',1);
/*!40000 ALTER TABLE `ubicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidad_administrativa`
--

DROP TABLE IF EXISTS `unidad_administrativa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidad_administrativa` (
  `id_unidad` int NOT NULL AUTO_INCREMENT,
  `codigo_ua` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_padre` int DEFAULT NULL,
  PRIMARY KEY (`id_unidad`),
  KEY `FK_ua_padre` (`id_padre`),
  CONSTRAINT `FK_ua_padre` FOREIGN KEY (`id_padre`) REFERENCES `unidad_administrativa` (`id_unidad`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidad_administrativa`
--

LOCK TABLES `unidad_administrativa` WRITE;
/*!40000 ALTER TABLE `unidad_administrativa` DISABLE KEYS */;
INSERT INTO `unidad_administrativa` VALUES (1,'22600000000000L','Secretaría de Cultura y Turismo',NULL),(2,'22600001000000S','Secretaría Particular',1),(3,'22600002000000S','Órgano Interno de Control',1),(4,'22600002000001S','Área de Auditoría',3),(5,'22600002000002S','Área de Quejas',3),(6,'22600002000003S','Área de Responsabilidades',3),(7,'22600004000000S','Unidad de Información, Planeación, Programación y Evaluación',1),(8,'22600004000001S','Departamento de Información y Planeación',7),(9,'22600004000002S','Departamento de Programación y Evaluación',7),(10,'22600004000003S','Departamento de Tecnologías de la Información',7),(11,'22600004000004S','Departamento de Mejora Regulatoria',7),(12,'22600007000000S','Coordinación Jurídica, de Igualdad de Género y Erradicación de la Violencia',1),(13,'22600007000100S','Subdirección Jurídica de Cultura',12),(14,'22600007000200S','Subdirección Jurídica de Turismo',12),(15,'22600007000300S','Subdirección de Normatividad y Consulta',12),(16,'22600003000000S','Coordinación Administrativa',1),(17,'22600003010000S','Dirección de Finanzas',16),(18,'22600003010001S','Departamento de Control de Ingresos',17),(19,'22600003010002S','Departamento de Contabilidad',17),(20,'22600003010003S','Departamento de Control Presupuestal',17),(21,'22600003020000S','Dirección de Administración',16),(22,'22600003020001S','Departamento de Recursos Humanos',21),(23,'22600003020002S','Departamento de Recursos Materiales',21),(24,'22600003020003S','Departamento de Servicios Generales',21),(25,'22600003030000S','Dirección de Infraestructura',16),(26,'22600003030001S','Departamento de Proyectos de Inversión y Control de Estimaciones',25),(27,'22600003030004S','Departamento de Mantenimiento',25),(28,'22600003030005S','Departamento de Arquitectura y Diseño',25),(29,'22600003000001S','Departamento de Gestión Documental y Control de Archivos',16),(30,'22600200000000L','Subsecretaría de Cultura',1),(31,'22600201000000L','Dirección General de Patrimonio y Servicios Culturales del Valle de los Volcanes',30),(32,'22600201000100S','Delegación Administrativa',31),(33,'22600201000101L','Departamento de Supervisión de la Calidad de los Servicios y Validación de Pagos (PPS)',32),(34,'22600201010000L','Dirección de Patrimonio Cultural',31),(35,'22600201010001L','Departamento de Bibliotecas',34),(36,'GEN0101000101L','Bibliotecas',35),(37,'22600201010002L','Departamento de Museos',34),(38,'GEN0101000201L','Museos',37),(39,'22600201020000L','Dirección de Servicios Culturales',31),(40,'22600201020001L','Departamento de Enseñanza Artística, Centros Regionales y Capacitación Cultural',39),(41,'22600201020002L','Departamento de Actividades Culturales y Artísticas',39),(42,'GEN0102000201L','Centros Regionales de Cultura',41),(43,'22600202000000L','Dirección General de Patrimonio y Servicios Culturales del Valle de Toluca',30),(44,'22600202010000S','Unidad de Desarrollo de Proyectos Culturales',43),(45,'22600202000100S','Delegación Administrativa',43),(46,'22600202010000L','Dirección de Patrimonio Cultural',43),(47,'22600202010100L','Subdirección de Bibliotecas y Documentación',46),(48,'22600202010101L','Archivo Histórico del Estado De México',47),(49,'22600202010102L','Departamento de Fomento a la Lectura',47),(50,'22600202010103L','Departamento de Bibliotecas',47),(51,'GEN0201010301L','Bibliotecas',50),(52,'22600202010200L','Subdirección de Acervo Cultural',46),(53,'22600202010201L','Departamento de Restauración',52),(54,'22600202010202L','Departamento de Museos',52),(55,'GEN0201020201L','Museos',54),(56,'22600202020000L','Dirección de Servicios Culturales',43),(57,'22600202020001L','Coordinación de Artes Escénicas',56),(58,'22600202020100L','Subdirección de Promoción Cultural',56),(59,'22600202020101L','Departamento de Capacitación Cultural y Vinculación',58),(60,'22600202020102L','Departamento de Artes Plásticas y Visuales',58),(61,'GEN0202010201L','Centros Regionales de Cultura',60),(62,'22600202030000L','Dirección de la Cineteca Mexiquense',43),(63,'22600202030100L','Subdirección de Acervos',62),(64,'22600202030101L','Departamento de Investigación y Preservación de Acervos',63),(65,'22600202030102L','Departamento de Operación de Salas y Apoyo Técnico',63),(66,'22600202030200L','Subdirección de Programación',62),(67,'22600202030201L','Departamento de Contenidos Audiovisuales',66),(68,'22600202030202L','Departamento de Relaciones Públicas',66),(69,'22600200010000L','Dirección de Desarrollo de Proyectos Culturales',30),(70,'22600200020000L','Dirección del Conservatorio de Música del Estado de México',30),(71,'22600200020100S','Delegación Administrativa',70),(72,'22600200020200L','Subdirección Académica',70),(73,'GEN00020201L','Coordinación de Licenciatura',72),(74,'GEN0002020101L','Control Escolar de Licenciatura',73),(75,'GEN00020202L','Coordinación de Carreras Técnicas',72),(76,'GEN0002020201L','Control Escolar de Carreras Técnicas',75),(77,'GEN00020203L','Informática',72),(78,'GEN00020204L','Biblioteca',72),(79,'GEN00020205L','Fonoteca',72),(80,'GEN00020300L','Relaciones Públicas y Vinculación',70),(81,'22600200030000L','Dirección de la Orquesta Sinfónica del Estado de México',30),(82,'22600200030100S','Delegación Administrativa',81),(83,'22600200030200L','Subdirección Artística de la Orquesta Filarmónica Mexiquense',81),(84,'22600200030300L','Subdirección Artística del Coro Polifónico del Estado de México',81),(85,'22600200030400L','Subdirección Operativa',81),(86,'22600200030500L','Subdirección de Relaciones Públicas',81),(87,'22600200030600L','Subdirección de Promoción y Ventas',81),(88,'22600008000000L','Secretaría Ejecutiva del Consejo Editorial de la Administración Pública Estatal',1),(89,'22600008000100S','Delegación Administrativa',88),(90,'22600008000200L','Subdirección de Producción Editorial',88),(91,'22600008000201L','Departamento de Ediciones, Publicidad y Diseño Gráfico',90),(92,'22600008000300L','Subdirección de Difusión y Distribución',88),(93,'22600009000000L','Dirección General de Planeación y Desarrollo Turístico Sostenible',1),(94,'22600009000100S','Delegación Administrativa',93),(95,'22600009010000L','Dirección de Fomento y Desarrollo Turístico Sostenible',93),(96,'22600009010001L','Departamento de Planeación en Materia Turística',95),(97,'22600009010002L','Departamento de Desarrollo Turístico Sostenible',95),(98,'22600009000200L','Subdirección de Vinculación y Evaluación Turística',93),(99,'22600010000000L','Dirección General de Promoción Turística',1),(100,'22600010010000L','Dirección de Atracción de Reuniones, Congresos y Convenciones',99),(101,'22600010010001L','Departamento de Vinculación Empresarial y Reuniones de Negocios',100),(102,'22600010010002L','Departamento de Promoción Turística',100),(103,'22600010020000L','Dirección de Proyectos Turísticos',99),(104,'22600010020001L','Departamento de Seguimiento y Evaluación de Proyectos Turísticos',103),(105,'22600011000000L','Dirección General de Calidad y Servicios Turísticos',1),(106,'22600011000100L','Subdirección de Capacitación Turística',105),(107,'22600011000001L','Departamento de Calidad, Certificación y Regulación Turística',105);
/*!40000 ALTER TABLE `unidad_administrativa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'bienes_informaticos'
--

--
-- Dumping routines for database 'bienes_informaticos'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-22 14:10:42
