-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: bienes_informaticos
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `activo_especifico`
--

DROP TABLE IF EXISTS `activo_especifico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activo_especifico` (
  `id_activo_especifico` int NOT NULL AUTO_INCREMENT,
  `id_grupo_activo` int NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_activo_especifico`),
  KEY `FK_activo_especifico_grupo` (`id_grupo_activo`),
  CONSTRAINT `FK_activo_especifico_grupo` FOREIGN KEY (`id_grupo_activo`) REFERENCES `grupo_activo` (`id_grupo_activo`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activo_especifico`
--

LOCK TABLES `activo_especifico` WRITE;
/*!40000 ALTER TABLE `activo_especifico` DISABLE KEYS */;
INSERT INTO `activo_especifico` VALUES (1,1,'1','jkl',1),(2,1,'LAPTOP','Computadora portatil',1),(3,1,'COMPUTADORA DE ESCRITORIO','Equipo de computo de escritorio',1),(4,2,'IMPRESORA LASER','Impresora laser para oficina',1),(5,2,'MONITOR',NULL,1),(6,1,'COMPUTADORA DE ESCRITORIO 1',NULL,1),(7,1,'COMPUTADORA DE ESCRITORIO PRUEBA',NULL,1),(8,1,'COMPU',NULL,1),(9,2,'ACT ESP IMPRESORA LASER',NULL,1),(10,1,'LAPTOP ACT ESP',NULL,1),(11,2,'LAPTOP ACT ESP',NULL,1),(12,8,'celular Samsung',NULL,1),(13,8,'celular Moto',NULL,1),(14,9,'Escritorio',NULL,1);
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
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_activo_generico`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activo_generico`
--

LOCK TABLES `activo_generico` WRITE;
/*!40000 ALTER TABLE `activo_generico` DISABLE KEYS */;
INSERT INTO `activo_generico` VALUES (1,'EQUIPO DE COMPUTO',NULL,1),(2,'EQUIPO DE COMPUTO','Equipo de computo',1),(3,'EQUIPO DE IMPRESION','Equipos destinados a impresion de documentos',1),(4,'EQUIPO DE COMUNICACION','Equipos utilizados para comunicacion y conectividad',1),(5,'Telefono','telefono',1),(6,'Telefono','telefono',0),(7,'Inmueble',NULL,1);
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
  `motivo` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_monitor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_cpu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_teclado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_mouse` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie_ups_cargador` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_baja`),
  KEY `FK_baja_resguardo` (`id_resguardo`),
  KEY `FK_baja_bien` (`id_bien`),
  CONSTRAINT `FK_baja_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_baja_resguardo` FOREIGN KEY (`id_resguardo`) REFERENCES `resguardo` (`id_resguardo`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `baja_resguardo`
--

LOCK TABLES `baja_resguardo` WRITE;
/*!40000 ALTER TABLE `baja_resguardo` DISABLE KEYS */;
INSERT INTO `baja_resguardo` VALUES (1,11,4,'2026-09-28',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,5,5,'2026-09-28','mjbhvcgxt',NULL,NULL,NULL,NULL,NULL,NULL),(3,13,4,'2026-09-28',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_permisos`
--

LOCK TABLES `bee_permisos` WRITE;
/*!40000 ALTER TABLE `bee_permisos` DISABLE KEYS */;
INSERT INTO `bee_permisos` VALUES (4,'Guardar bienes','bienes-guardar','Registrar y editar bienes.','2026-09-22 11:43:25'),(5,'Inactivar bienes','bienes-inactivar','Cambiar el estado activo de bienes.','2026-09-22 11:43:25'),(7,'Exportar reportes','reportes-exportar','Exportar el inventario a PDF.','2026-09-22 11:43:25'),(8,'Generar documentos','documentos-generar','Generar tarjetas, resguardos y bajas.','2026-09-22 11:43:25'),(9,'Acceso de administrador','admin-access','Acceso total a la administración.','2026-09-22 11:43:45'),(10,'Consultar inventario','inventario-consultar','Acceso de consulta a los módulos del inventario.','2026-09-25 13:16:33'),(11,'Capturar catálogos','catalogos-guardar','Crear y editar información de catálogos.','2026-09-25 14:27:37'),(12,'Activar o desactivar catálogos','catalogos-inactivar','Cambiar el estado de catálogos.','2026-09-25 14:27:37'),(13,'Eliminar unidades administrativas','catalogos-eliminar','Eliminar unidades administrativas sin dependencias.','2026-09-25 14:27:37'),(107,'Consultar inicio','inicio-consultar','Acceso al panel principal.','2026-09-30 17:06:46'),(108,'Consultar bienes','bienes-consultar','Consultar inventario, detalle e identificación de bienes.','2026-09-30 17:06:46'),(109,'Crear bienes','bienes-crear','Registrar bienes nuevos.','2026-09-30 17:06:46'),(110,'Actualizar bienes','bienes-actualizar','Editar bienes existentes.','2026-09-30 17:06:46'),(111,'Activar bienes','bienes-activar','Reactivar bienes inactivos.','2026-09-30 17:06:46'),(112,'Desactivar bienes','bienes-desactivar','Inactivar bienes activos.','2026-09-30 17:06:46'),(113,'Imprimir bienes','bienes-imprimir','Imprimir la etiqueta/código QR de un bien.','2026-09-30 17:06:46'),(114,'Consultar catálogos','catalogos-consultar','Consultar los catálogos del sistema.','2026-09-30 17:06:46'),(115,'Crear catálogos','catalogos-crear','Crear registros de catálogo.','2026-09-30 17:06:46'),(116,'Actualizar catálogos','catalogos-actualizar','Editar registros y asignaciones de catálogo.','2026-09-30 17:06:46'),(117,'Activar catálogos','catalogos-activar','Reactivar registros de catálogo.','2026-09-30 17:06:46'),(118,'Desactivar catálogos','catalogos-desactivar','Inactivar registros de catálogo.','2026-09-30 17:06:46'),(119,'Consultar resguardos','resguardos-consultar','Consultar resguardos y sus detalles.','2026-09-30 17:06:46'),(120,'Consultar movimientos','movimientos-consultar','Consultar el histórico de movimientos.','2026-09-30 17:06:46'),(121,'Consultar documentos','documentos-consultar','Acceder a formularios y datos previos de documentos.','2026-09-30 17:06:46'),(122,'Consultar usuarios','usuarios-consultar','Consultar la lista y los datos administrativos de usuarios.','2026-09-30 17:06:46'),(123,'Crear usuarios','usuarios-crear','Registrar cuentas de usuario.','2026-09-30 17:06:46'),(124,'Actualizar usuarios','usuarios-actualizar','Modificar datos y asignar roles a usuarios.','2026-09-30 17:06:46'),(125,'Eliminar usuarios','usuarios-eliminar','Eliminar físicamente cuentas de usuario.','2026-09-30 17:06:46'),(126,'Activar usuarios','usuarios-activar','Activar cuentas de usuario.','2026-09-30 17:06:46'),(127,'Desactivar usuarios','usuarios-desactivar','Desactivar cuentas de usuario.','2026-09-30 17:06:46'),(128,'Cerrar sesiones de usuarios','usuarios-cerrar-sesion','Invalidar la sesión activa de otro usuario.','2026-09-30 17:06:46'),(129,'Consultar roles y permisos','roles-consultar','Consultar la matriz y el acceso efectivo de usuarios.','2026-09-30 17:06:46'),(130,'Modificar permisos de roles','permisos-actualizar','Cambiar los permisos asignados a roles.','2026-09-30 17:06:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_roles`
--

LOCK TABLES `bee_roles` WRITE;
/*!40000 ALTER TABLE `bee_roles` DISABLE KEYS */;
INSERT INTO `bee_roles` VALUES (4,'Administrador','admin','2026-09-22 11:43:25'),(6,'Consultor','consultor','2026-09-25 13:16:33'),(7,'Capturista','capturista','2026-09-25 13:29:21');
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
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_roles_permisos`
--

LOCK TABLES `bee_roles_permisos` WRITE;
/*!40000 ALTER TABLE `bee_roles_permisos` DISABLE KEYS */;
INSERT INTO `bee_roles_permisos` VALUES (22,4,9),(25,7,10),(26,7,4),(27,7,8),(28,7,11),(31,6,10),(32,6,8),(117,6,107),(118,7,107),(119,6,108),(120,7,108),(121,6,114),(122,7,114),(123,6,119),(124,7,119),(125,6,120),(126,7,120),(132,6,121),(133,7,121),(135,7,109),(136,7,110),(138,7,115),(139,7,116),(141,7,111),(142,7,112),(144,4,122),(145,4,123),(146,4,124),(147,4,125),(148,4,126),(149,4,127),(150,4,128),(151,4,129),(152,4,130),(159,7,113),(160,6,113),(162,7,7),(163,6,7);
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
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellido_paterno` varchar(80) NOT NULL DEFAULT '',
  `apellido_materno` varchar(80) NOT NULL DEFAULT '',
  `telefono` varchar(15) NOT NULL DEFAULT '',
  `rol` varchar(20) NOT NULL DEFAULT 'consultor',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_users`
--

LOCK TABLES `bee_users` WRITE;
/*!40000 ALTER TABLE `bee_users` DISABLE KEYS */;
INSERT INTO `bee_users` VALUES (1,'$2y$10$gekaE7/Li2zjU5.lCKkkf.NNCGP7C8DOK1cycx8PYYksc5ZNtfkde','bee','$2y$10$xHEI5cJ3q7rBJaL.M9qBRe909ahHvIZVTfRRxlLqfnWwAYwWQE/Wu','jslocal@localhost.com','bee','','','','admin',1,'2021-12-05 15:52:17'),(2,NULL,'Capturista1','$2y$10$DX8YiqKdzbEgIEkOAy9xsunV0sL8PK6JLq/aEljio8vexYbsgwbDq','halemonofre97@gmail.com','Halem Onofre','','','527224296790','capturista',1,'2026-09-25 12:58:38'),(3,NULL,'Consultor1','$2y$10$5WfjZx4If2PWa.2nitG3Je7EFmZIT.s8Hevy5gHTJgUaAPnx1iqTK','marigarciar85@gmail.com','Ana Karen CA','','','527224212191','consultor',1,'2026-09-25 13:00:12'),(4,NULL,'capturistaP','$2y$10$1vgTZRJQYuwoOeDHkCzgc.ap3Tm9/aNR3Sf7xcJDjC3yXwbPgAeYu','capturista@gmail.com','capturista prueba1','','','7224296790','capturista',1,'2026-09-25 13:37:52'),(5,'$2y$10$WRodpYHVLxKYc67oAP5/nu6m2gyKAHPGemmDEFcLXcXznYOBufWK2','consultorP','$2y$10$kIi3TKU0kmlNQsgf4.xpuuIYRTvBGwlqrRQMtAS9oGTnMUma/Ju2O','consultor@gmail.com','consultor prueba','','','7226824735','consultor',1,'2026-09-25 13:52:43'),(6,NULL,'abeja','$2y$10$4VM/Ulax6wi9l3omMXj3ROGVM9OBLfu4KYp7u2fm.iIAbuWTZQWUe','abeja@gmail.com','Abeja Reyna','','','1234567890','capturista',1,'2026-09-25 15:28:54');
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
  `numero_inventario` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clave_interna` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nic_cea` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_unidad` int NOT NULL,
  `id_activo_especifico` int DEFAULT NULL,
  `nombre_bien` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `material` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_material` int DEFAULT NULL,
  `id_marca` int DEFAULT NULL,
  `id_modelo` int DEFAULT NULL,
  `color` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_color` int DEFAULT NULL,
  `id_estado_uso` int DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caracteristicas` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_alta` date DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_elaboracion` date DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `valor` decimal(18,2) DEFAULT NULL,
  `id_ubicacion` int DEFAULT NULL,
  `piso` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seccion_ala` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cubiculo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_bien`),
  UNIQUE KEY `numero_inventario` (`numero_inventario`),
  UNIQUE KEY `uq_bien_clave_interna` (`clave_interna`),
  KEY `FK_bien_unidad` (`id_unidad`),
  KEY `FK_bien_activo_especifico` (`id_activo_especifico`),
  KEY `FK_bien_marca` (`id_marca`),
  KEY `FK_bien_modelo` (`id_modelo`),
  KEY `FK_bien_estado` (`id_estado_uso`),
  KEY `FK_bien_ubicacion` (`id_ubicacion`),
  KEY `idx_bien_inventario` (`numero_inventario`),
  KEY `idx_bien_serie` (`numero_serie`),
  KEY `idx_bien_activo` (`activo`),
  KEY `idx_bien_clave_interna` (`clave_interna`),
  KEY `FK_bien_material` (`id_material`),
  KEY `FK_bien_color` (`id_color`),
  CONSTRAINT `FK_bien_activo_especifico` FOREIGN KEY (`id_activo_especifico`) REFERENCES `activo_especifico` (`id_activo_especifico`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_color` FOREIGN KEY (`id_color`) REFERENCES `color` (`id_color`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_estado` FOREIGN KEY (`id_estado_uso`) REFERENCES `estado_uso` (`id_estado_uso`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_marca` FOREIGN KEY (`id_marca`) REFERENCES `marca` (`id_marca`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `modelo` (`id_modelo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_bien_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_administrativa` (`id_unidad`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bien`
--

LOCK TABLES `bien` WRITE;
/*!40000 ALTER TABLE `bien` DISABLE KEYS */;
INSERT INTO `bien` VALUES (1,'10C027613G','CI-000001','NIC-000001',10,3,'Computadora de escritorio 2','Metal y plastico',1,4,5,'Negro',1,2,'000001','Core i5, 16 GB RAM, SSD 512 GB','Equipo de prueba para inventario','2026-09-01','2026-08-20','2026-08-25','2026-09-01',18500.00,1,'PISO1','SECCION1','CUB1','2026-09-22 14:09:30',0),(2,'10C027611G','CI-000002','NIC-000002',10,6,'Laptop','Metal y plastico',1,2,6,'Gris',2,2,'SERIE-DELL-000002','Core i5, 16 GB RAM, SSD 512 GB','Equipo portatil de prueba','2026-09-02','2026-08-21','2026-08-26','2026-09-23',22000.00,2,'PISO1','SECCION1','CUB1','2026-09-22 14:09:30',1),(3,'10KH25889','CI-000003','NIC-000003',21,8,'Impresora','Plastico',2,3,8,'Negro',1,2,'445000003','Impresora multifuncional','Equipo de prueba','2026-09-03','2026-08-22','2026-08-27','2026-09-03',6500.00,NULL,'P','SECCION1','C','2026-09-22 14:09:30',1),(4,'10C02761INVS','CI-202609221738531','7856NIC',82,10,'NOMBRE BI DE PRUEBA 1','PLASTICO',2,3,7,'GRIS',2,1,'Numserie112234','LEVES RAYONES','S/O','2026-09-22','2026-09-23','2026-09-22','2026-09-23',80005.00,3,'PISO1','ALA1','CUB1','2026-09-22 17:38:53',1),(5,'10C027615G','CI-202609231318141','589889',105,13,'Celular','Metal',3,3,9,'Blanco',3,2,'NSERIE','Esta rota la pantalla','S/O','2026-09-23','2026-09-23','2026-09-23','2026-09-23',2500.00,1,'5',NULL,'Cub2','2026-09-23 13:18:14',1),(8,'10C027629G','CI-202609291639001','589899',6,5,'1',NULL,6,2,6,NULL,6,1,'1','S/C','S/O','2026-09-29','2026-09-29','2026-09-29',NULL,1.00,5,NULL,NULL,NULL,'2026-09-29 16:39:00',1);
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
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_codigo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CODE128',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_codigo_barra`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `FK_codigo_barra_bien` (`id_bien`),
  CONSTRAINT `FK_codigo_barra_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codigo_barra`
--

LOCK TABLES `codigo_barra` WRITE;
/*!40000 ALTER TABLE `codigo_barra` DISABLE KEYS */;
INSERT INTO `codigo_barra` VALUES (1,1,'CI-000001','QR',1,'2026-09-22 14:09:54'),(2,2,'CI-000002','CODE128',1,'2026-09-22 14:09:54'),(3,3,'CI-000003','CODE128',1,'2026-09-22 14:09:54'),(4,4,'CI-202609221738531','QR',1,'2026-09-22 17:38:53'),(5,5,'CI-202609231318141','QR',1,'2026-09-23 13:18:14'),(8,8,'CI-202609291639001','QR',1,'2026-09-29 16:39:00');
/*!40000 ALTER TABLE `codigo_barra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `color`
--

DROP TABLE IF EXISTS `color`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `color` (
  `id_color` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_normalizado` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_color`),
  UNIQUE KEY `uq_color_nombre_normalizado` (`nombre_normalizado`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `color`
--

LOCK TABLES `color` WRITE;
/*!40000 ALTER TABLE `color` DISABLE KEYS */;
INSERT INTO `color` VALUES (1,'Negro','negro',1),(2,'Gris','gris',1),(3,'Blanco','blanco',1),(5,'Verde','verde',1),(6,'Cafe','cafe',1);
/*!40000 ALTER TABLE `color` ENABLE KEYS */;
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
  `tipo_componente` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_inventario` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_componente`),
  KEY `FK_componente_bien` (`id_bien`),
  CONSTRAINT `FK_componente_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `componente`
--

LOCK TABLES `componente` WRITE;
/*!40000 ALTER TABLE `componente` DISABLE KEYS */;
INSERT INTO `componente` VALUES (1,1,'Monitor','P2422H','DELL','MON-000001','COMP-000001',0),(2,1,'Teclado','KB216','DELL','TEC-000001','COMP-000002',0),(3,2,'Cargador','65W USB-C','DELL','CAR-000001','COMP-000003',0),(4,1,'CPU','A','A','A',NULL,0),(5,1,'CPU','PRO MAX 1','DELL','3PSJGFVBNJHGF',NULL,1),(6,2,'CARGADOR','HHJKLÑ.-{','GENERICO','ASDFGHJNKM',NULL,1),(7,4,'CPU','MODELO PRO MAX','DELL','SERIESADSSD','CI-00000147852458',1),(8,1,'CARGADOR',NULL,NULL,NULL,NULL,1),(9,1,'MONITOR',NULL,NULL,NULL,NULL,1),(10,1,'MOUSE',NULL,NULL,NULL,NULL,1),(11,1,'TECLADO',NULL,NULL,NULL,NULL,1),(12,1,'UPS',NULL,NULL,NULL,NULL,1);
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
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_estado_uso`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_uso`
--

LOCK TABLES `estado_uso` WRITE;
/*!40000 ALTER TABLE `estado_uso` DISABLE KEYS */;
INSERT INTO `estado_uso` VALUES (1,'Bueno',1),(2,'Regular',1),(3,'Malo',1);
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
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_grupo_activo`),
  KEY `FK_grupo_activo_generico` (`id_activo_generico`),
  CONSTRAINT `FK_grupo_activo_generico` FOREIGN KEY (`id_activo_generico`) REFERENCES `activo_generico` (`id_activo_generico`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_activo`
--

LOCK TABLES `grupo_activo` WRITE;
/*!40000 ALTER TABLE `grupo_activo` DISABLE KEYS */;
INSERT INTO `grupo_activo` VALUES (1,1,'EQUIPO DE COMPUTO',NULL,1),(2,1,'COMPUTADORAS','Equipos de computo personales',1),(3,2,'IMPRESORAS','Equipos de impresion',1),(4,3,'REDES','Equipos de comunicacion y conectividad',1),(5,1,'COMPUTADORAS','Equipos de computo personales',1),(6,2,'IMPRESORAS','Equipos de impresion',1),(7,3,'REDES','Equipos de comunicacion y conectividad',1),(8,5,'Celular',NULL,1),(9,7,'Mesas',NULL,1);
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
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `material`
--

DROP TABLE IF EXISTS `material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material` (
  `id_material` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_normalizado` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_material`),
  UNIQUE KEY `uq_material_nombre_normalizado` (`nombre_normalizado`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material`
--

LOCK TABLES `material` WRITE;
/*!40000 ALTER TABLE `material` DISABLE KEYS */;
INSERT INTO `material` VALUES (1,'Metal y plastico','metal y plastico',1),(2,'Plastico','plastico',1),(3,'Metal','metal',1),(5,'Vidrio','vidrio',1),(6,'Carton','carton',1);
/*!40000 ALTER TABLE `material` ENABLE KEYS */;
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
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_modelo`),
  KEY `FK_modelo_marca` (`id_marca`),
  CONSTRAINT `FK_modelo_marca` FOREIGN KEY (`id_marca`) REFERENCES `marca` (`id_marca`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modelo`
--

LOCK TABLES `modelo` WRITE;
/*!40000 ALTER TABLE `modelo` DISABLE KEYS */;
INSERT INTO `modelo` VALUES (1,1,'S1922',1),(2,1,'LATITUDE 5420',1),(3,2,'THINKCENTRE M70Q',1),(4,3,'ECOTANK L3250',1),(5,4,'LATITUDE 5420',1),(6,2,'LATITUDE 5420',1),(7,3,'SLIM',1),(8,3,'THINKCENTRE M70Q',1),(9,3,'Motog40',1),(10,1,'slim',1);
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
  `id_usuario` int DEFAULT NULL,
  `usuario_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_anterior` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_ua_anterior` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_nueva` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_ua_nueva` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion_anterior_detalle` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion_nueva_detalle` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `csp_anterior` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_resguardante_anterior` varchar(305) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `csp_nuevo` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_resguardante_nuevo` varchar(305) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_resguardante_anterior` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_ua_resguardante_anterior` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_resguardante_nueva` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_ua_resguardante_nueva` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_anterior` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_nuevo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_movimiento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion_anterior` int DEFAULT NULL,
  `ubicacion_nueva` int DEFAULT NULL,
  `resguardante_anterior` int DEFAULT NULL,
  `resguardante_nuevo` int DEFAULT NULL,
  `id_resguardo_anterior` int DEFAULT NULL,
  `id_resguardo_nuevo` int DEFAULT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `FK_movimiento_ubicacion_anterior` (`ubicacion_anterior`),
  KEY `FK_movimiento_ubicacion_nueva` (`ubicacion_nueva`),
  KEY `FK_movimiento_resguardante_anterior` (`resguardante_anterior`),
  KEY `FK_movimiento_resguardante_nuevo` (`resguardante_nuevo`),
  KEY `FK_movimiento_resguardo_anterior` (`id_resguardo_anterior`),
  KEY `FK_movimiento_resguardo_nuevo` (`id_resguardo_nuevo`),
  KEY `idx_movimiento_bien_fecha` (`id_bien`,`fecha_movimiento`),
  KEY `idx_movimiento_usuario` (`id_usuario`),
  CONSTRAINT `FK_movimiento_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardante_anterior` FOREIGN KEY (`resguardante_anterior`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardante_nuevo` FOREIGN KEY (`resguardante_nuevo`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardo_anterior` FOREIGN KEY (`id_resguardo_anterior`) REFERENCES `resguardo` (`id_resguardo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_resguardo_nuevo` FOREIGN KEY (`id_resguardo_nuevo`) REFERENCES `resguardo` (`id_resguardo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_ubicacion_anterior` FOREIGN KEY (`ubicacion_anterior`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_ubicacion_nueva` FOREIGN KEY (`ubicacion_nueva`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_movimiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_bien`
--

LOCK TABLES `movimiento_bien` WRITE;
/*!40000 ALTER TABLE `movimiento_bien` DISABLE KEYS */;
INSERT INTO `movimiento_bien` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASIGNACION',NULL,1,NULL,1,NULL,1,'2026-09-01 09:00:00','Asignacion inicial','Registro inicial del bien'),(2,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASIGNACION',NULL,2,NULL,2,NULL,2,'2026-09-02 10:00:00','Asignacion inicial','Registro inicial del bien'),(3,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASIGNACION',NULL,3,NULL,3,NULL,3,'2026-09-03 11:00:00','Asignacion inicial','Registro inicial del bien'),(4,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Liberación de resguardo',NULL,NULL,1,NULL,NULL,NULL,'2026-09-22 17:39:37',NULL,NULL),(5,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Asignación de resguardo',NULL,NULL,NULL,3,NULL,NULL,'2026-09-23 10:00:43',NULL,NULL),(6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Asignación de resguardo',NULL,NULL,NULL,4,NULL,NULL,'2026-09-23 13:18:14',NULL,NULL),(8,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,3,2,NULL,NULL,'2026-09-23 15:04:49',NULL,'Transferencia por desactivación de resguardante.'),(9,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,3,2,NULL,NULL,'2026-09-23 15:04:49',NULL,'Transferencia por desactivación de resguardante.'),(10,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,2,1,NULL,NULL,'2026-09-23 15:48:05',NULL,'Transferencia individual por desactivación de resguardante.'),(11,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,2,4,NULL,NULL,'2026-09-23 15:48:05',NULL,'Transferencia individual por desactivación de resguardante.'),(12,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,2,4,NULL,NULL,'2026-09-23 15:48:05',NULL,'Transferencia individual por desactivación de resguardante.'),(13,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Inactivo','Baja de bien',NULL,NULL,4,4,10,10,'2026-09-24 13:25:10',NULL,NULL),(14,2,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','Zinacantepec, Zinacantepec · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','123450001','Juan Martinez Lopez','123450001','Juan Martinez Lopez','Coordinación Administrativa','22600003000000S','Coordinación Administrativa','22600003000000S','Activo','Activo','Cambio de ubicación',1,2,1,1,9,9,'2026-09-25 10:13:09',NULL,NULL),(15,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Cambio de ubicación',1,3,4,4,11,11,'2026-09-25 10:14:08',NULL,NULL),(16,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Inactivo','Activo','Reactivación de bien',NULL,NULL,4,4,10,10,'2026-09-28 12:03:16',NULL,NULL),(17,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Inactivo','Activo','Reactivación de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-28 12:19:19',NULL,NULL),(18,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Inactivo','Baja de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-28 12:19:24',NULL,NULL),(19,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Inactivo','Baja de bien',NULL,NULL,4,4,10,10,'2026-09-28 12:19:31',NULL,NULL),(20,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Inactivo','Activo','Reactivación de bien',NULL,NULL,4,4,10,10,'2026-09-28 12:19:39',NULL,NULL),(21,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L',NULL,NULL,'Activo','Activo','Devolución de resguardo',3,3,4,NULL,11,NULL,'2026-09-28 14:12:13',NULL,NULL),(22,5,1,'bee','Dirección General de Calidad y Servicios Turísticos','22600011000000L','Dirección General de Calidad y Servicios Turísticos','22600011000000L','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2','210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L',NULL,NULL,'Activo','Activo','Devolución de resguardo',1,1,4,NULL,5,NULL,'2026-09-28 14:13:05','mjbhvcgxt',NULL),(23,5,1,'bee','Dirección General de Calidad y Servicios Turísticos','22600011000000L','Dirección General de Calidad y Servicios Turísticos','22600011000000L','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2',NULL,NULL,'123450001','Juan Martinez Lopez',NULL,NULL,'Coordinación Administrativa','22600003000000S','Activo','Activo','Asignación de resguardo',1,1,NULL,1,NULL,12,'2026-09-28 14:13:51',NULL,NULL),(24,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1',NULL,NULL,'210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Asignación de resguardo',3,3,NULL,4,NULL,13,'2026-09-28 14:14:04',NULL,NULL),(25,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L',NULL,NULL,'Activo','Activo','Devolución de resguardo',3,3,4,NULL,13,NULL,'2026-09-28 14:14:27',NULL,NULL),(26,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Inactivo','Activo','Reactivación de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-29 10:39:25',NULL,NULL),(27,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Inactivo','Baja de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-29 10:39:30',NULL,NULL),(29,8,1,'bee',NULL,NULL,'Órgano Interno de Control','22600002000000S',NULL,'Almoloya de Juarez, Almoloya de Juarez',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Alta de bien',NULL,3,NULL,NULL,NULL,NULL,'2026-09-29 16:39:00',NULL,NULL),(30,8,1,'bee','Órgano Interno de Control','22600002000000S','Área de Responsabilidades','22600002000003S','Almoloya de Juarez, Almoloya de Juarez','Almoloya de Juarez, Almoloya de Juarez',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Activo','Cambio de unidad administrativa',3,3,NULL,NULL,NULL,NULL,'2026-09-29 16:40:44',NULL,NULL),(31,8,1,'bee','Área de Responsabilidades','22600002000003S','Área de Responsabilidades','22600002000003S','Almoloya de Juarez, Almoloya de Juarez','Ocuilan, Ocuilan de Arteaga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Activo','Cambio de ubicación',3,5,NULL,NULL,NULL,NULL,'2026-09-29 17:04:02',NULL,NULL);
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
  `tipo` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `id_padre` bigint DEFAULT NULL,
  `id_usuario` bigint DEFAULT NULL,
  `id_ref` bigint DEFAULT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `permalink` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `contenido` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
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
  `clave_interna` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_paterno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_materno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `csp` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_unidad` int DEFAULT NULL,
  `notas` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_resguardante`),
  UNIQUE KEY `uq_resguardante_csp` (`csp`),
  UNIQUE KEY `clave_interna` (`clave_interna`),
  KEY `idx_resguardante_unidad` (`id_unidad`),
  CONSTRAINT `FK_resguardante_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_administrativa` (`id_unidad`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resguardante`
--

LOCK TABLES `resguardante` WRITE;
/*!40000 ALTER TABLE `resguardante` DISABLE KEYS */;
INSERT INTO `resguardante` VALUES (1,NULL,'Juan','Martinez','Lopez','123450001',16,'Resguardante de prueba 1',1),(2,NULL,'Maria','Hernandez','Garcia','123333333',4,'Resguardante de prueba 2',0),(3,NULL,'Carlos','Ramirez','Sanchez','123456603',90,'Resguardante de prueba 3',0),(4,NULL,'JAZMIN','ONOFRE','GARCIA','210061636',90,NULL,1);
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
  `csp` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_equipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_equipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `candado` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_resguardo`),
  KEY `FK_resguardo_resguardante` (`id_resguardante`),
  KEY `idx_resguardo_vigente` (`id_bien`,`activo`),
  KEY `idx_resguardo_csp` (`csp`),
  CONSTRAINT `FK_resguardo_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_resguardo_csp` FOREIGN KEY (`csp`) REFERENCES `resguardante` (`csp`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_resguardo_resguardante` FOREIGN KEY (`id_resguardante`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resguardo`
--

LOCK TABLES `resguardo` WRITE;
/*!40000 ALTER TABLE `resguardo` DISABLE KEYS */;
INSERT INTO `resguardo` VALUES (1,1,1,'123450001','Computadora de escritorio','Bueno','Equipo asignado para actividades administrativas','NO','2026-09-01','2026-09-22',0),(2,2,2,'123333333','Laptop','Bueno','Equipo portatil asignado al resguardante','SI','2026-09-02','2026-09-23',0),(3,3,3,'123456603','Impresora','Regular','Equipo compartido para impresion','NO','2026-09-03','2026-09-23',0),(4,4,3,'123456603',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-23',0),(5,5,4,'210061636',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-28',0),(7,3,2,'123333333','Impresora','Regular','Equipo compartido para impresion','NO','2026-09-23','2026-09-23',0),(8,4,2,'123333333',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-23',0),(9,2,1,'123450001','Laptop','Bueno','Equipo portatil asignado al resguardante','SI','2026-09-23',NULL,1),(10,3,4,'210061636','Impresora','Regular','Equipo compartido para impresion','NO','2026-09-23',NULL,1),(11,4,4,'210061636',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-28',0),(12,5,1,'123450001',NULL,NULL,NULL,NULL,'2026-09-23',NULL,1),(13,4,4,'210061636',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-28',0);
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
  `municipio` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `localidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion_fisica` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicacion`
--

LOCK TABLES `ubicacion` WRITE;
/*!40000 ALTER TABLE `ubicacion` DISABLE KEYS */;
INSERT INTO `ubicacion` VALUES (1,'TOLUCA','TOLUCA',NULL,1),(2,'Zinacantepec','Zinacantepec','Departamento de Tecnologias de la Informacion',1),(3,'Almoloya de Juarez','Almoloya de Juarez','Departamento de Recursos Humanos',1),(4,'TOLUCA','SAN BUENAVENTURA','Departamento de Recursos Materiales',1),(5,'Ocuilan','Ocuilan de Arteaga',NULL,1);
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
  `codigo_ua` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_padre` int DEFAULT NULL,
  PRIMARY KEY (`id_unidad`),
  KEY `FK_ua_padre` (`id_padre`),
  CONSTRAINT `FK_ua_padre` FOREIGN KEY (`id_padre`) REFERENCES `unidad_administrativa` (`id_unidad`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidad_administrativa`
--

LOCK TABLES `unidad_administrativa` WRITE;
/*!40000 ALTER TABLE `unidad_administrativa` DISABLE KEYS */;
INSERT INTO `unidad_administrativa` VALUES (1,'22600000000000L','Secretaría de Cultura y Turismo',NULL),(2,'22600001000000S','Secretaría Particular',1),(3,'22600002000000S','Órgano Interno de Control',1),(4,'22600002000001S','Área de Auditoría',3),(5,'22600002000002S','Área de Quejas',3),(6,'22600002000003S','Área de Responsabilidades',3),(7,'22600004000000S','Unidad de Información, Planeación, Programación y Evaluación',1),(8,'22600004000001S','Departamento de Información y Planeación',7),(9,'22600004000002S','Departamento de Programación y Evaluación',7),(10,'22600004000003S','Departamento de Tecnologías de la Información',7),(11,'22600004000004S','Departamento de Mejora Regulatoria',7),(12,'22600007000000S','Coordinación Jurídica, de Igualdad de Género y Erradicación de la Violencia',1),(13,'22600007000100S','Subdirección Jurídica de Cultura',12),(14,'22600007000200S','Subdirección Jurídica de Turismo',12),(15,'22600007000300S','Subdirección de Normatividad y Consulta',12),(16,'22600003000000S','Coordinación Administrativa',1),(17,'22600003010000S','Dirección de Finanzas',16),(18,'22600003010001S','Departamento de Control de Ingresos',17),(19,'22600003010002S','Departamento de Contabilidad',17),(20,'22600003010003S','Departamento de Control Presupuestal',17),(21,'22600003020000S','Dirección de Administración',16),(22,'22600003020001S','Departamento de Recursos Humanos',21),(23,'22600003020002S','Departamento de Recursos Materiales',21),(24,'22600003020003S','Departamento de Servicios Generales',21),(25,'22600003030000S','Dirección de Infraestructura',16),(26,'22600003030001S','Departamento de Proyectos de Inversión y Control de Estimaciones',25),(27,'22600003030004S','Departamento de Mantenimiento',25),(28,'22600003030005S','Departamento de Arquitectura y Diseño',25),(29,'22600003000001S','Departamento de Gestión Documental y Control de Archivos',16),(30,'22600200000000L','Subsecretaría de Cultura',1),(31,'22600201000000L','Dirección General de Patrimonio y Servicios Culturales del Valle de los Volcanes',30),(32,'22600201000100S','Delegación Administrativa',31),(33,'22600201000101L','Departamento de Supervisión de la Calidad de los Servicios y Validación de Pagos (PPS)',32),(34,'22600201010000L','Dirección de Patrimonio Cultural',31),(35,'22600201010001L','Departamento de Bibliotecas',34),(36,NULL,'Bibliotecas',35),(37,'22600201010002L','Departamento de Museos',34),(38,NULL,'Museos',37),(39,'22600201020000L','Dirección de Servicios Culturales',31),(40,'22600201020001L','Departamento de Enseñanza Artística, Centros Regionales y Capacitación Cultural',39),(41,'22600201020002L','Departamento de Actividades Culturales y Artísticas',39),(42,NULL,'Centros Regionales de Cultura',41),(43,'22600202000000L','Dirección General de Patrimonio y Servicios Culturales del Valle de Toluca',30),(44,'22600202010000S','Unidad de Desarrollo de Proyectos Culturales',43),(45,'22600202000100S','Delegación Administrativa',43),(46,'22600202010000L','Dirección de Patrimonio Cultural',43),(47,'22600202010100L','Subdirección de Bibliotecas y Documentación',46),(48,'22600202010101L','Archivo Histórico del Estado De México',47),(49,'22600202010102L','Departamento de Fomento a la Lectura',47),(50,'22600202010103L','Departamento de Bibliotecas',47),(51,NULL,'Bibliotecas',50),(52,'22600202010200L','Subdirección de Acervo Cultural',46),(53,'22600202010201L','Departamento de Restauración',52),(54,'22600202010202L','Departamento de Museos',52),(55,NULL,'Museos',54),(56,'22600202020000L','Dirección de Servicios Culturales',43),(57,'22600202020001L','Coordinación de Artes Escénicas',56),(58,'22600202020100L','Subdirección de Promoción Cultural',56),(59,'22600202020101L','Departamento de Capacitación Cultural y Vinculación',58),(60,'22600202020102L','Departamento de Artes Plásticas y Visuales',58),(61,NULL,'Centros Regionales de Cultura',60),(62,'22600202030000L','Dirección de la Cineteca Mexiquense',43),(63,'22600202030100L','Subdirección de Acervos',62),(64,'22600202030101L','Departamento de Investigación y Preservación de Acervos',63),(65,'22600202030102L','Departamento de Operación de Salas y Apoyo Técnico',63),(66,'22600202030200L','Subdirección de Programación',62),(67,'22600202030201L','Departamento de Contenidos Audiovisuales',66),(68,'22600202030202L','Departamento de Relaciones Públicas',66),(69,'22600200010000L','Dirección de Desarrollo de Proyectos Culturales',30),(70,'22600200020000L','Dirección del Conservatorio de Música del Estado de México',30),(71,'22600200020100S','Delegación Administrativa',70),(72,'22600200020200L','Subdirección Académica',70),(73,NULL,'Coordinación de Licenciatura',72),(74,NULL,'Control Escolar de Licenciatura',73),(75,NULL,'Coordinación de Carreras Técnicas',72),(76,NULL,'Control Escolar de Carreras Técnicas',75),(77,NULL,'Informática',72),(78,NULL,'Biblioteca',72),(79,NULL,'Fonoteca',72),(80,NULL,'Relaciones Públicas y Vinculación',70),(81,'22600200030000L','Dirección de la Orquesta Sinfónica del Estado de México',30),(82,'22600200030100S','Delegación Administrativa',81),(83,'22600200030200L','Subdirección Artística de la Orquesta Filarmónica Mexiquense',81),(84,'22600200030300L','Subdirección Artística del Coro Polifónico del Estado de México',81),(85,'22600200030400L','Subdirección Operativa',81),(86,'22600200030500L','Subdirección de Relaciones Públicas',81),(87,'22600200030600L','Subdirección de Promoción y Ventas',81),(88,'22600008000000L','Secretaría Ejecutiva del Consejo Editorial de la Administración Pública Estatal',1),(89,'22600008000100S','Delegación Administrativa',88),(90,'22600008000200L','Subdirección de Producción Editorial',88),(91,'22600008000201L','Departamento de Ediciones, Publicidad y Diseño Gráfico',90),(92,'22600008000300L','Subdirección de Difusión y Distribución',88),(93,'22600009000000L','Dirección General de Planeación y Desarrollo Turístico Sostenible',1),(94,'22600009000100S','Delegación Administrativa',93),(95,'22600009010000L','Dirección de Fomento y Desarrollo Turístico Sostenible',93),(96,'22600009010001L','Departamento de Planeación en Materia Turística',95),(97,'22600009010002L','Departamento de Desarrollo Turístico Sostenible',95),(98,'22600009000200L','Subdirección de Vinculación y Evaluación Turística',93),(99,'22600010000000L','Dirección General de Promoción Turística',1),(100,'22600010010000L','Dirección de Atracción de Reuniones, Congresos y Convenciones',99),(101,'22600010010001L','Departamento de Vinculación Empresarial y Reuniones de Negocios',100),(102,'22600010010002L','Departamento de Promoción Turística',100),(103,'22600010020000L','Dirección de Proyectos Turísticos',99),(104,'22600010020001L','Departamento de Seguimiento y Evaluación de Proyectos Turísticos',103),(105,'22600011000000L','Dirección General de Calidad y Servicios Turísticos',1),(106,'22600011000100L','Subdirección de Capacitación Turística',105),(107,'22600011000001L','Departamento de Calidad, Certificación y Regulación Turística',105);
/*!40000 ALTER TABLE `unidad_administrativa` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 10:09:07
