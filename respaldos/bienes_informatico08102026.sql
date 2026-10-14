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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_permisos`
--

LOCK TABLES `bee_permisos` WRITE;
/*!40000 ALTER TABLE `bee_permisos` DISABLE KEYS */;
INSERT INTO `bee_permisos` VALUES (4,'Guardar bienes','bienes-guardar','Registrar y editar bienes.','2026-09-22 11:43:25'),(5,'Inactivar bienes','bienes-inactivar','Cambiar el estado activo de bienes.','2026-09-22 11:43:25'),(7,'Exportar reportes','reportes-exportar','Exportar el inventario a PDF.','2026-09-22 11:43:25'),(8,'Generar documentos','documentos-generar','Generar tarjetas, resguardos y bajas.','2026-09-22 11:43:25'),(9,'Acceso de administrador','admin-access','Acceso total a la administración.','2026-09-22 11:43:45'),(10,'Consultar inventario','inventario-consultar','Acceso de consulta a los módulos del inventario.','2026-09-25 13:16:33'),(11,'Capturar catálogos','catalogos-guardar','Crear y editar información de catálogos.','2026-09-25 14:27:37'),(12,'Activar o desactivar catálogos','catalogos-inactivar','Cambiar el estado de catálogos.','2026-09-25 14:27:37'),(13,'Eliminar unidades administrativas','catalogos-eliminar','Eliminar unidades administrativas sin dependencias.','2026-09-25 14:27:37'),(107,'Consultar inicio','inicio-consultar','Acceso al panel principal.','2026-09-30 17:06:46'),(108,'Consultar bienes','bienes-consultar','Consultar inventario, detalle e identificación de bienes.','2026-09-30 17:06:46'),(109,'Crear bienes','bienes-crear','Registrar bienes nuevos.','2026-09-30 17:06:46'),(110,'Actualizar bienes','bienes-actualizar','Editar bienes existentes.','2026-09-30 17:06:46'),(111,'Activar bienes','bienes-activar','Reactivar bienes inactivos.','2026-09-30 17:06:46'),(112,'Desactivar bienes','bienes-desactivar','Inactivar bienes activos.','2026-09-30 17:06:46'),(113,'Imprimir bienes','bienes-imprimir','Imprimir la etiqueta/código QR de un bien.','2026-09-30 17:06:46'),(114,'Consultar catálogos','catalogos-consultar','Consultar los catálogos del sistema.','2026-09-30 17:06:46'),(115,'Crear catálogos','catalogos-crear','Crear registros de catálogo.','2026-09-30 17:06:46'),(116,'Actualizar catálogos','catalogos-actualizar','Editar registros y asignaciones de catálogo.','2026-09-30 17:06:46'),(117,'Activar catálogos','catalogos-activar','Reactivar registros de catálogo.','2026-09-30 17:06:46'),(118,'Desactivar catálogos','catalogos-desactivar','Inactivar registros de catálogo.','2026-09-30 17:06:46'),(119,'Consultar resguardos','resguardos-consultar','Consultar resguardos y sus detalles.','2026-09-30 17:06:46'),(120,'Consultar movimientos','movimientos-consultar','Consultar el histórico de movimientos.','2026-09-30 17:06:46'),(121,'Consultar documentos','documentos-consultar','Acceder a formularios y datos previos de documentos.','2026-09-30 17:06:46'),(122,'Consultar usuarios','usuarios-consultar','Consultar la lista y los datos administrativos de usuarios.','2026-09-30 17:06:46'),(123,'Crear usuarios','usuarios-crear','Registrar cuentas de usuario.','2026-09-30 17:06:46'),(124,'Actualizar usuarios','usuarios-actualizar','Modificar datos y asignar roles a usuarios.','2026-09-30 17:06:46'),(125,'Eliminar usuarios','usuarios-eliminar','Eliminar físicamente cuentas de usuario.','2026-09-30 17:06:46'),(126,'Activar usuarios','usuarios-activar','Activar cuentas de usuario.','2026-09-30 17:06:46'),(127,'Desactivar usuarios','usuarios-desactivar','Desactivar cuentas de usuario.','2026-09-30 17:06:46'),(128,'Cerrar sesiones de usuarios','usuarios-cerrar-sesion','Invalidar la sesión activa de otro usuario.','2026-09-30 17:06:46'),(129,'Consultar roles y permisos','roles-consultar','Consultar la matriz y el acceso efectivo de usuarios.','2026-09-30 17:06:46'),(130,'Modificar permisos de roles','permisos-actualizar','Cambiar los permisos asignados a roles.','2026-09-30 17:06:46'),(138,'Consultar traspasos','traspasos-consultar','Consultar expedientes de traspaso.','2026-10-02 16:24:58'),(139,'Crear traspaso','traspasos-crear','Crear expedientes de traspaso.','2026-10-02 16:24:58'),(140,'Modificar traspaso','traspasos-modificar','Modificar expedientes en estados permitidos.','2026-10-02 16:24:58'),(141,'Cancelar traspaso','traspasos-cancelar','Cancelar expedientes antes de autorización.','2026-10-02 16:24:58'),(142,'Subir documentos de traspaso','traspasos-subir-documento','Cargar oficio y documentos en el expediente.','2026-10-02 16:24:58'),(143,'Autorizar traspaso','traspasos-autorizar','Autorizar traspasos entre unidades.','2026-10-02 16:24:58'),(144,'Rechazar traspaso','traspasos-rechazar','Rechazar traspasos entre unidades.','2026-10-02 16:24:58'),(145,'Ejecutar traspaso','traspasos-ejecutar','Completar el cambio patrimonial.','2026-10-02 16:24:58'),(146,'Confirmar entrega','traspasos-confirmar-entrega','Registrar la confirmación de entrega.','2026-10-02 16:24:58'),(147,'Confirmar recepción','traspasos-confirmar-recepcion','Registrar la confirmación de recepción.','2026-10-02 16:24:58'),(148,'Generar Tarjeta de Resguardo','traspasos-generar-tarjeta','Generar tarjeta posterior al traspaso.','2026-10-02 16:24:58'),(149,'Generar Resguardo del Equipo','traspasos-generar-resguardo','Generar resguardo del equipo posterior al traspaso.','2026-10-02 16:24:58'),(150,'Descargar documentos de traspaso','traspasos-descargar-documentos','Descargar documentos autorizados del expediente.','2026-10-02 16:24:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_roles_permisos`
--

LOCK TABLES `bee_roles_permisos` WRITE;
/*!40000 ALTER TABLE `bee_roles_permisos` DISABLE KEYS */;
INSERT INTO `bee_roles_permisos` VALUES (22,4,9),(25,7,10),(26,7,4),(27,7,8),(28,7,11),(31,6,10),(32,6,8),(117,6,107),(118,7,107),(119,6,108),(120,7,108),(121,6,114),(122,7,114),(123,6,119),(124,7,119),(125,6,120),(126,7,120),(132,6,121),(133,7,121),(135,7,109),(136,7,110),(138,7,115),(139,7,116),(141,7,111),(142,7,112),(144,4,122),(145,4,123),(146,4,124),(147,4,125),(148,4,126),(149,4,127),(150,4,128),(151,4,129),(152,4,130),(159,7,113),(160,6,113),(162,7,7),(163,6,7),(165,7,138),(166,7,139),(167,7,140),(168,7,141),(169,7,142),(170,7,145),(171,7,146),(172,7,147),(173,7,148),(174,7,149),(175,7,150),(180,6,138),(181,6,150);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bee_users`
--

LOCK TABLES `bee_users` WRITE;
/*!40000 ALTER TABLE `bee_users` DISABLE KEYS */;
INSERT INTO `bee_users` VALUES (1,'$2y$10$M2IEm/k28vZ86QWSbVmueuNPNKn4Hr87EKbjE93GcpYkRprhauZuO','bee','$2y$10$xHEI5cJ3q7rBJaL.M9qBRe909ahHvIZVTfRRxlLqfnWwAYwWQE/Wu','jslocal@localhost.com','bee','','','','admin',1,'2021-12-05 15:52:17'),(2,NULL,'Capturista1','$2y$10$DX8YiqKdzbEgIEkOAy9xsunV0sL8PK6JLq/aEljio8vexYbsgwbDq','halemonofre97@gmail.com','Halem Onofre','','','527224296790','capturista',1,'2026-09-25 12:58:38'),(3,NULL,'Consultor1','$2y$10$28DDFy8pGykHdYwZt7sQee7b/c7jpYC0eIVq9Adrf6P/gK2rIpKKG','carmonaavilesanakaren@gmail.com','Ana KAREN C A','C','A','527224212191','consultor',1,'2026-09-25 13:00:12'),(4,'$2y$10$k6wfV7bq0svVmQ1yncz4tuHAE2fIcpKWHyfKJsiywmvVYjbGRu44S','capturistaP','$2y$10$1vgTZRJQYuwoOeDHkCzgc.ap3Tm9/aNR3Sf7xcJDjC3yXwbPgAeYu','capturista@gmail.com','capturista prueba1','','','7224296790','capturista',1,'2026-09-25 13:37:52'),(5,'$2y$10$VTxQDwrKWQQf4e76cCrfnuWNx4CItOjLHDQ.ZcmM5H/6K6UfGw1u2','consultorP','$2y$10$1wZIgUTOkLtJy9FwKVbarOSW175L2BVyGJK.3420K4uBDuMlw2pCy','suzukikenna@gmail.com','consultor prueba','','','7296671633','consultor',1,'2026-09-25 13:52:43'),(6,'$2y$10$gst65P/ahVUHCez50sms8.XB7kk0t/i71mIOHjnQhZNpMh7zxCszy','abeja','$2y$10$4VM/Ulax6wi9l3omMXj3ROGVM9OBLfu4KYp7u2fm.iIAbuWTZQWUe','abeja@gmail.com','Abeja Reyna','','','1234567890','capturista',1,'2026-09-25 15:28:54'),(9,NULL,'Yaz123','$2y$10$IZFrUPr8W8LvN4CxiWclguC3KfIWuhOZ/T7WfK90MvHn3Oxx4pHmW','18onofre.halem@gmail.com','Jazmin Onofre García','Onofre','García','7224296790','consultor',1,'2026-10-01 14:43:15'),(10,NULL,'gustavoCA62','$2y$10$rtDPb8rKHkrjRDsIyXGXOOvqJfZJaHFgJKbWlgJMboIkRzuhCsWfG','halemonofre825@gmail.com','Gustavo Carmona Aviles','Carmona','Aviles','7226824138','consultor',1,'2026-10-01 15:10:12'),(11,'$2y$10$oaCphOcYnLQgQuEY9DcmAuG5W2QEWiTYRmX0zr7rppUXx9M9.zpdy','harryPT34','$2y$10$gP4MMcXtRw29sxme/G9kvepeCG7.JDwkRj/z6cXVKI4iHfLl00moK','onofregarciah@gmail.com','Harry Po tter','Po','tter','7225505084','consultor',1,'2026-10-01 15:45:17');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bien`
--

LOCK TABLES `bien` WRITE;
/*!40000 ALTER TABLE `bien` DISABLE KEYS */;
INSERT INTO `bien` VALUES (1,'10C027613G','CI-000001','NIC-000001',10,3,'Computadora de escritorio 2','Metal y plastico',1,2,NULL,'Negro',1,2,'000001','Core i5, 16 GB RAM, SSD 512 GB','Equipo de prueba para inventario','2026-09-01','2026-08-20','2026-08-25','2026-09-01',18500.00,1,'PISO1','SECCION1','CUB1','2026-09-22 14:09:30',1),(2,'10C027611G','CI-000002','NIC-000002',4,6,'Laptop','Metal y plastico',1,2,6,'Gris',2,2,'SERIE-DELL-000002','Core i5, 16 GB RAM, SSD 512 GB','Equipo portatil de prueba','2026-09-02','2026-08-21','2026-08-26','2026-09-23',22000.00,2,'PISO1','SECCION1','CUB1','2026-09-22 14:09:30',1),(3,'10KH25889','CI-000003','NIC-000003',21,8,'Impresora','Plastico',2,3,8,'Negro',1,2,'445000003','Impresora multifuncional','Equipo de prueba','2026-09-03','2026-08-22','2026-08-27','2026-09-03',6500.00,6,'P','SECCION1','C','2026-09-22 14:09:30',1),(4,'10C02761INVS','CI-202609221738531','7856NIC',82,10,'NOMBRE BI DE PRUEBA 1','PLASTICO',2,3,7,'GRIS',2,1,'Numserie112234','LEVES RAYONES','S/O','2026-09-22','2026-09-23','2026-09-22','2026-09-23',80005.00,3,'PISO1','ALA1','CUB1','2026-09-22 17:38:53',1),(5,'10C027615G','CI-202609231318141','589889',105,13,'Celular','Metal',3,3,9,'Blanco',3,2,'NSERIE','Esta rota la pantalla','S/O','2026-09-23','2026-09-23','2026-09-23','2026-09-23',2500.00,1,'5',NULL,'Cub2','2026-09-23 13:18:14',1),(8,'10C027629G','CI-202609291639001','589899',90,5,'1',NULL,6,2,6,NULL,6,1,'102122','S/C','S/O','2026-09-29','2026-09-29','2026-09-29',NULL,1.00,5,'PISO1','SECCION1','Cub2','2026-09-29 16:39:00',1);
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
INSERT INTO `codigo_barra` VALUES (1,1,'CI-000001','QR',1,'2026-09-22 14:09:54'),(2,2,'CI-000002','QR',1,'2026-09-22 14:09:54'),(3,3,'CI-000003','CODE128',1,'2026-09-22 14:09:54'),(4,4,'CI-202609221738531','QR',1,'2026-09-22 17:38:53'),(5,5,'CI-202609231318141','QR',1,'2026-09-23 13:18:14'),(8,8,'CI-202609291639001','QR',1,'2026-09-29 16:39:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `componente`
--

LOCK TABLES `componente` WRITE;
/*!40000 ALTER TABLE `componente` DISABLE KEYS */;
INSERT INTO `componente` VALUES (1,1,'Monitor','P2422H','DELL','MON-000001','COMP-000001',0),(2,1,'Teclado','KB216','DELL','TEC-000001','COMP-000002',0),(3,2,'Cargador','65W USB-C','DELL','CAR-000001','COMP-000003',0),(4,1,'CPU','A','A','A',NULL,0),(5,1,'CPU','PRO MAX 1','DELL','3PSJGFVBNJHGF',NULL,1),(6,2,'CARGADOR','HHJKLÑ.-{','GENERICO','ASDFGHJNKM',NULL,1),(7,4,'CPU','MODELO PRO MAX','DELL','SERIESADSSD','CI-00000147852458',1),(8,1,'CARGADOR',NULL,NULL,NULL,NULL,1),(9,1,'MONITOR',NULL,NULL,NULL,NULL,1),(10,1,'MOUSE',NULL,NULL,NULL,NULL,1),(11,1,'TECLADO',NULL,NULL,NULL,NULL,1),(12,1,'UPS',NULL,NULL,NULL,NULL,1),(13,3,'CPU','MODELOCPU','MARCACPU','SERIECPU123456','CI-20261007CPU',1),(14,3,'CARGADOR','MODELOCARGADOR','MARCACARGADOR','SERIECARGADOR123456','CI-20261007CARGADOR',1),(15,3,'MONITOR','MODELOMONITOR','MARCAMONITOR','SERIEMONITOR123456','CI-2025841MONITOR',1),(16,3,'MOUSE','MODELOMOUSE','MARCAMOUSE','SERIEMOUSE123456','CI-20261007MOUSE',1),(17,3,'TECLADO','MODELOTECLADO','MARCATECLADO','SERIETECLADO123456','CI-20261007TECLADO',1),(18,3,'UPS','MODELOUPS','MARCAUPS','SERIEUPS123456','CI-20261007UPS',1);
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
INSERT INTO `modelo` VALUES (1,1,'S1922',1),(2,1,'LATITUDE 5420',1),(3,2,'THINKCENTRE M70Q',1),(4,3,'ECOTANK L3250',1),(5,4,'LATITUDE 5420',1),(6,2,'LATITUDE 5420',1),(7,3,'SLIM',1),(8,3,'THINKCENTRE M70Q',1),(9,3,'Motog40',1),(10,1,'SLIM',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_bien`
--

LOCK TABLES `movimiento_bien` WRITE;
/*!40000 ALTER TABLE `movimiento_bien` DISABLE KEYS */;
INSERT INTO `movimiento_bien` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASIGNACION',NULL,1,NULL,1,NULL,1,'2026-09-01 09:00:00','Asignacion inicial','Registro inicial del bien'),(2,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASIGNACION',NULL,2,NULL,2,NULL,2,'2026-09-02 10:00:00','Asignacion inicial','Registro inicial del bien'),(3,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASIGNACION',NULL,3,NULL,3,NULL,3,'2026-09-03 11:00:00','Asignacion inicial','Registro inicial del bien'),(4,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Liberación de resguardo',NULL,NULL,1,NULL,NULL,NULL,'2026-09-22 17:39:37',NULL,NULL),(5,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Asignación de resguardo',NULL,NULL,NULL,3,NULL,NULL,'2026-09-23 10:00:43',NULL,NULL),(6,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Asignación de resguardo',NULL,NULL,NULL,4,NULL,NULL,'2026-09-23 13:18:14',NULL,NULL),(8,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,3,2,NULL,NULL,'2026-09-23 15:04:49',NULL,'Transferencia por desactivación de resguardante.'),(9,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,3,2,NULL,NULL,'2026-09-23 15:04:49',NULL,'Transferencia por desactivación de resguardante.'),(10,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,2,1,NULL,NULL,'2026-09-23 15:48:05',NULL,'Transferencia individual por desactivación de resguardante.'),(11,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,2,4,NULL,NULL,'2026-09-23 15:48:05',NULL,'Transferencia individual por desactivación de resguardante.'),(12,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cambio de resguardante',NULL,NULL,2,4,NULL,NULL,'2026-09-23 15:48:05',NULL,'Transferencia individual por desactivación de resguardante.'),(13,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Inactivo','Baja de bien',NULL,NULL,4,4,10,10,'2026-09-24 13:25:10',NULL,NULL),(14,2,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','Zinacantepec, Zinacantepec · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','123450001','Juan Martinez Lopez','123450001','Juan Martinez Lopez','Coordinación Administrativa','22600003000000S','Coordinación Administrativa','22600003000000S','Activo','Activo','Cambio de ubicación',1,2,1,1,9,9,'2026-09-25 10:13:09',NULL,NULL),(15,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Cambio de ubicación',1,3,4,4,11,11,'2026-09-25 10:14:08',NULL,NULL),(16,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Inactivo','Activo','Reactivación de bien',NULL,NULL,4,4,10,10,'2026-09-28 12:03:16',NULL,NULL),(17,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Inactivo','Activo','Reactivación de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-28 12:19:19',NULL,NULL),(18,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Inactivo','Baja de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-28 12:19:24',NULL,NULL),(19,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Inactivo','Baja de bien',NULL,NULL,4,4,10,10,'2026-09-28 12:19:31',NULL,NULL),(20,3,1,'bee','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Inactivo','Activo','Reactivación de bien',NULL,NULL,4,4,10,10,'2026-09-28 12:19:39',NULL,NULL),(21,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L',NULL,NULL,'Activo','Activo','Devolución de resguardo',3,3,4,NULL,11,NULL,'2026-09-28 14:12:13',NULL,NULL),(22,5,1,'bee','Dirección General de Calidad y Servicios Turísticos','22600011000000L','Dirección General de Calidad y Servicios Turísticos','22600011000000L','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2','210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L',NULL,NULL,'Activo','Activo','Devolución de resguardo',1,1,4,NULL,5,NULL,'2026-09-28 14:13:05','mjbhvcgxt',NULL),(23,5,1,'bee','Dirección General de Calidad y Servicios Turísticos','22600011000000L','Dirección General de Calidad y Servicios Turísticos','22600011000000L','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2','TOLUCA, TOLUCA · Piso: 5 · Cubículo: Cub2',NULL,NULL,'123450001','Juan Martinez Lopez',NULL,NULL,'Coordinación Administrativa','22600003000000S','Activo','Activo','Asignación de resguardo',1,1,NULL,1,NULL,12,'2026-09-28 14:13:51',NULL,NULL),(24,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1',NULL,NULL,'210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Asignación de resguardo',3,3,NULL,4,NULL,13,'2026-09-28 14:14:04',NULL,NULL),(25,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L',NULL,NULL,'Activo','Activo','Devolución de resguardo',3,3,4,NULL,13,NULL,'2026-09-28 14:14:27',NULL,NULL),(26,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Inactivo','Activo','Reactivación de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-29 10:39:25',NULL,NULL),(27,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Inactivo','Baja de bien',1,1,NULL,NULL,NULL,NULL,'2026-09-29 10:39:30',NULL,NULL),(29,8,1,'bee',NULL,NULL,'Órgano Interno de Control','22600002000000S',NULL,'Almoloya de Juarez, Almoloya de Juarez',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Alta de bien',NULL,3,NULL,NULL,NULL,NULL,'2026-09-29 16:39:00',NULL,NULL),(30,8,1,'bee','Órgano Interno de Control','22600002000000S','Área de Responsabilidades','22600002000003S','Almoloya de Juarez, Almoloya de Juarez','Almoloya de Juarez, Almoloya de Juarez',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Activo','Cambio de unidad administrativa',3,3,NULL,NULL,NULL,NULL,'2026-09-29 16:40:44',NULL,NULL),(31,8,1,'bee','Área de Responsabilidades','22600002000003S','Área de Responsabilidades','22600002000003S','Almoloya de Juarez, Almoloya de Juarez','Ocuilan, Ocuilan de Arteaga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Activo','Activo','Cambio de ubicación',3,5,NULL,NULL,NULL,NULL,'2026-09-29 17:04:02',NULL,NULL),(46,1,1,'bee','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1',NULL,NULL,'123450001','Juan Martinez Lopez',NULL,NULL,'Coordinación Administrativa','22600003000000S','Inactivo','Inactivo','Asignación de resguardo',1,1,NULL,1,NULL,28,'2026-10-02 09:48:46',NULL,NULL),(47,4,1,'bee','Delegación Administrativa','22600200030100S','Delegación Administrativa','22600200030100S','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1','Almoloya de Juarez, Almoloya de Juarez · Piso: PISO1 · Sección/Ala: ALA1 · Cubículo: CUB1',NULL,NULL,'210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Asignación de resguardo',3,3,NULL,4,NULL,29,'2026-10-02 09:49:08',NULL,NULL),(48,8,1,'bee','Área de Responsabilidades','22600002000003S','Área de Responsabilidades','22600002000003S','Ocuilan, Ocuilan de Arteaga','Ocuilan, Ocuilan de Arteaga',NULL,NULL,'210061636','JAZMIN ONOFRE GARCIA',NULL,NULL,'Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Asignación de resguardo',5,5,NULL,4,NULL,30,'2026-10-02 09:49:26',NULL,NULL),(49,1,4,'capturista prueba1','Departamento de Tecnologías de la Información','22600004000003S','Departamento de Tecnologías de la Información','22600004000003S','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','TOLUCA, TOLUCA · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','123450001','Juan Martinez Lopez','123450001','Juan Martinez Lopez','Coordinación Administrativa','22600003000000S','Coordinación Administrativa','22600003000000S','Inactivo','Activo','Reactivación de bien',1,1,1,1,28,28,'2026-10-02 16:19:16',NULL,NULL),(50,8,1,'bee','Área de Responsabilidades','22600002000003S','Área de Responsabilidades','22600002000003S','Ocuilan, Ocuilan de Arteaga','Ocuilan, Ocuilan de Arteaga · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: Cub2','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Cambio de ubicación',5,5,4,4,30,30,'2026-10-02 17:22:54',NULL,NULL),(51,3,4,'capturista prueba1','Dirección de Administración','22600003020000S','Dirección de Administración','22600003020000S','Piso: P · Sección/Ala: SECCION1 · Cubículo: C','TOLUCA, San Mateo Atenco · Piso: P · Sección/Ala: SECCION1 · Cubículo: C','210061636','JAZMIN ONOFRE GARCIA','210061636','JAZMIN ONOFRE GARCIA','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Cambio de ubicación',NULL,6,4,4,10,10,'2026-10-07 13:01:35',NULL,NULL),(52,8,1,NULL,'Área de Responsabilidades','22600002000003S','Subdirección de Producción Editorial','22600008000200L','Ocuilan, Ocuilan de Arteaga · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: Cub2','Ocuilan, Ocuilan de Arteaga · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: Cub2','210061636','JAZMIN ONOFRE GARCIA','123456603','Carlos Ramirez Sanchez','Subdirección de Producción Editorial','22600008000200L','Subdirección de Producción Editorial','22600008000200L','Activo','Activo','Traspaso TR-000004',5,5,4,3,30,31,'2026-10-07 13:28:36','Restablecimiento','sdfgh'),(53,2,1,NULL,'Departamento de Tecnologías de la Información','22600004000003S','Área de Auditoría','22600002000001S','Zinacantepec, Zinacantepec · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','Zinacantepec, Zinacantepec · Piso: PISO1 · Sección/Ala: SECCION1 · Cubículo: CUB1','123450001','Juan Martinez Lopez','123333333','Maria Hernandez Garcia','Coordinación Administrativa','22600003000000S','Área de Auditoría','22600002000001S','Activo','Activo','Traspaso TR-000007',2,2,1,2,9,32,'2026-10-07 17:22:59','asdd','asdf');
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
-- Table structure for table `password_reset_requests`
--

DROP TABLE IF EXISTS `password_reset_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `method` enum('email','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL,
  `lookup_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `request_ip_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `verified_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_password_reset_lookup_created` (`lookup_hash`,`created_at`),
  KEY `idx_password_reset_ip_created` (`request_ip_hash`,`created_at`),
  KEY `idx_password_reset_user_state` (`user_id`,`used_at`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_requests`
--

LOCK TABLES `password_reset_requests` WRITE;
/*!40000 ALTER TABLE `password_reset_requests` DISABLE KEYS */;
INSERT INTO `password_reset_requests` VALUES (10,5,'email','a2bff5d93f5bd641802181e44d0d3bbebb0414cf01f40a59a86084442515448e','98e153335dda0bd969bdd60bf1b8da7d358d8c035cfec23036e61b37b976b259','$2y$10$p8oD2yA4MTTIRcXQmxxlSOjnsVeMo/T4OM5evkyJQMzEm/Rcmaem6','2026-10-01 19:50:35',0,'2026-10-01 19:41:54','2026-10-01 19:43:00','2026-10-01 19:40:35'),(11,3,'email','83895bb81396c2fccc0b7871fe29a58305b58e642ef0792b91512eb7f2484f72','98e153335dda0bd969bdd60bf1b8da7d358d8c035cfec23036e61b37b976b259','$2y$10$Klc.JBQ3cYt8F7UWqrHYxuCNwzG9Rncs/WQWcg0dAksMVWWzXiF9K','2026-10-01 20:00:14',0,NULL,'2026-10-01 20:07:58','2026-10-01 19:50:14'),(12,3,'email','83895bb81396c2fccc0b7871fe29a58305b58e642ef0792b91512eb7f2484f72','98e153335dda0bd969bdd60bf1b8da7d358d8c035cfec23036e61b37b976b259','$2y$10$Xaao2mesrZXMK2zlD4d/3eNOxRXTyu99CteW/YE043V8opb6GfKqW','2026-10-01 20:17:59',0,NULL,'2026-10-01 20:08:18','2026-10-01 20:07:59'),(13,3,'email','83895bb81396c2fccc0b7871fe29a58305b58e642ef0792b91512eb7f2484f72','98e153335dda0bd969bdd60bf1b8da7d358d8c035cfec23036e61b37b976b259','$2y$10$RMikpzjiBUVAJEBKG1/LfegayykPTDQWrRICxiFPu..sUMphGNC36','2026-10-01 20:18:18',1,'2026-10-01 20:08:57','2026-10-01 20:09:17','2026-10-01 20:08:18'),(14,5,'email','a2bff5d93f5bd641802181e44d0d3bbebb0414cf01f40a59a86084442515448e','5f12d269789167c889397f2d42c55b91541e4e0aca09d8ac30dfec8a20db5f79','$2y$10$zeHEsBKVXniS97kPRmHI3OHJuiwelDndo4nfnd5WVCouylu7EsOg2','2026-10-06 18:00:00',0,NULL,'2026-10-06 17:50:09','2026-10-06 17:50:00'),(15,5,'email','a2bff5d93f5bd641802181e44d0d3bbebb0414cf01f40a59a86084442515448e','5f12d269789167c889397f2d42c55b91541e4e0aca09d8ac30dfec8a20db5f79','$2y$10$uAIXZwwcVV0owAMgNQ1Ise5O6BWRG1DSjoQPK7QBud8nulKy5wEaG','2026-10-06 18:00:09',2,'2026-10-06 17:51:02','2026-10-06 17:51:35','2026-10-06 17:50:09');
/*!40000 ALTER TABLE `password_reset_requests` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resguardante`
--

LOCK TABLES `resguardante` WRITE;
/*!40000 ALTER TABLE `resguardante` DISABLE KEYS */;
INSERT INTO `resguardante` VALUES (1,NULL,'Juan','Martinez','Lopez','123450001',16,'Resguardante de prueba 1',1),(2,NULL,'Maria','Hernandez','Garcia','123333333',4,'Resguardante de prueba 2',1),(3,NULL,'Carlos','Ramirez','Sanchez','123456603',90,'Resguardante de prueba 3',1),(4,NULL,'JAZMIN','ONOFRE','GARCIA','210061636',90,NULL,1),(14,NULL,'Paco','hernandez','napoleon','200002',2,NULL,1),(15,NULL,'Chicharito','Ramon','Valdez','123456789',16,NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resguardo`
--

LOCK TABLES `resguardo` WRITE;
/*!40000 ALTER TABLE `resguardo` DISABLE KEYS */;
INSERT INTO `resguardo` VALUES (1,1,1,'123450001','Computadora de escritorio','Bueno','Equipo asignado para actividades administrativas','NO','2026-09-01','2026-09-22',0),(2,2,2,'123333333','Laptop','Bueno','Equipo portatil asignado al resguardante','SI','2026-09-02','2026-09-23',0),(3,3,3,'123456603','Impresora','Regular','Equipo compartido para impresion','NO','2026-09-03','2026-09-23',0),(4,4,3,'123456603',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-23',0),(5,5,4,'210061636',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-28',0),(7,3,2,'123333333','Impresora','Regular','Equipo compartido para impresion','NO','2026-09-23','2026-09-23',0),(8,4,2,'123333333',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-23',0),(9,2,1,'123450001','Laptop','Bueno','Equipo portatil asignado al resguardante','SI','2026-09-23','2026-10-07',0),(10,3,4,'210061636','Impresora','Regular','Equipo compartido para impresion','NO','2026-09-23',NULL,1),(11,4,4,'210061636',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-28',0),(12,5,1,'123450001',NULL,NULL,NULL,NULL,'2026-09-23',NULL,1),(13,4,4,'210061636',NULL,NULL,NULL,NULL,'2026-09-23','2026-09-28',0),(28,1,1,'123450001',NULL,NULL,NULL,NULL,'2026-10-02',NULL,1),(29,4,4,'210061636',NULL,NULL,NULL,NULL,'2026-10-02',NULL,1),(30,8,4,'210061636',NULL,NULL,NULL,NULL,'2026-10-02','2026-10-07',0),(31,8,3,'123456603',NULL,NULL,NULL,NULL,'2026-10-07',NULL,1),(32,2,2,'123333333','Laptop','Bueno','Equipo portatil asignado al resguardante','SI','2026-10-07',NULL,1);
/*!40000 ALTER TABLE `resguardo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_unidad_administrativa`
--

DROP TABLE IF EXISTS `tipo_unidad_administrativa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_unidad_administrativa` (
  `clave_tipo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campo_visual` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` smallint unsigned NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`clave_tipo`),
  UNIQUE KEY `uq_tipo_unidad_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_unidad_administrativa`
--

LOCK TABLES `tipo_unidad_administrativa` WRITE;
/*!40000 ALTER TABLE `tipo_unidad_administrativa` DISABLE KEYS */;
INSERT INTO `tipo_unidad_administrativa` VALUES ('AREA','Área',NULL,110,0),('COORDINACION','Coordinación',NULL,100,0),('DELEGACION_ADMINISTRATIVA','Delegación Administrativa','delegacion_administrativa',50,0),('DEPARTAMENTO','Departamento','departamento',60,1),('DIRECCION','Dirección','direccion_secretaria',30,1),('DIRECCION_AREA','Dirección de área','direccion_area',40,1),('OFICINA','Oficina','oficina',70,1),('OTRO','Otro',NULL,999,0),('PENDIENTE','Pendiente de asignar',NULL,999,1),('SECRETARIA','Secretaría','secretaria',10,1),('STAFF','STAFF','subsecretaria',25,1),('SUBDIRECCION','Subdirección','subsecretaria',50,1),('SUBSECRETARIA','Subsecretaría','subsecretaria',20,1),('UNIDAD','Unidad',NULL,120,0);
/*!40000 ALTER TABLE `tipo_unidad_administrativa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traspaso`
--

DROP TABLE IF EXISTS `traspaso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traspaso` (
  `id_traspaso` int NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('ENTRE_UNIDADES','MISMA_UNIDAD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('CREADO','PENDIENTE_AUTORIZACION','AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA','PENDIENTE_VALIDACION','VALIDADO','COMPLETADO','RECHAZADO','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CREADO',
  `id_unidad_origen` int NOT NULL,
  `id_unidad_destino` int NOT NULL,
  `id_resguardante_destino` int NOT NULL,
  `id_usuario_creador` int DEFAULT NULL,
  `id_usuario_autoriza` int DEFAULT NULL,
  `fecha_autorizacion` datetime DEFAULT NULL,
  `id_usuario_rechaza` int DEFAULT NULL,
  `fecha_rechazo` datetime DEFAULT NULL,
  `motivo_rechazo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_entrega` enum('RESGUARDANTE_ACTUAL','ADMINISTRADOR') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_resguardante_entrega` int DEFAULT NULL,
  `id_usuario_entrega_persona` int DEFAULT NULL,
  `id_usuario_confirma_entrega` int DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `id_usuario_confirma_recepcion` int DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `id_usuario_cancela` int DEFAULT NULL,
  `fecha_cancelacion` datetime DEFAULT NULL,
  `motivo_cancelacion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_traspaso_revertido` int DEFAULT NULL,
  `id_usuario_reversion` int DEFAULT NULL,
  `fecha_reversion` datetime DEFAULT NULL,
  `motivo_reversion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_completado` datetime DEFAULT NULL,
  PRIMARY KEY (`id_traspaso`),
  UNIQUE KEY `uq_traspaso_folio` (`folio`),
  KEY `idx_traspaso_reversion` (`id_traspaso_revertido`),
  KEY `idx_traspaso_estado_fecha` (`estado`,`fecha_creacion`),
  KEY `idx_traspaso_unidades` (`id_unidad_origen`,`id_unidad_destino`),
  KEY `idx_traspaso_resguardantes` (`id_resguardante_entrega`,`id_resguardante_destino`),
  KEY `fk_traspaso_unidad_destino` (`id_unidad_destino`),
  KEY `fk_traspaso_resguardante_destino` (`id_resguardante_destino`),
  KEY `fk_traspaso_creador` (`id_usuario_creador`),
  KEY `fk_traspaso_autoriza` (`id_usuario_autoriza`),
  KEY `fk_traspaso_rechaza` (`id_usuario_rechaza`),
  KEY `fk_traspaso_entrega_persona` (`id_usuario_entrega_persona`),
  KEY `fk_traspaso_confirma_entrega` (`id_usuario_confirma_entrega`),
  KEY `fk_traspaso_confirma_recepcion` (`id_usuario_confirma_recepcion`),
  KEY `fk_traspaso_cancela` (`id_usuario_cancela`),
  KEY `fk_traspaso_usuario_reversion` (`id_usuario_reversion`),
  CONSTRAINT `fk_traspaso_autoriza` FOREIGN KEY (`id_usuario_autoriza`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_cancela` FOREIGN KEY (`id_usuario_cancela`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_confirma_entrega` FOREIGN KEY (`id_usuario_confirma_entrega`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_confirma_recepcion` FOREIGN KEY (`id_usuario_confirma_recepcion`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_creador` FOREIGN KEY (`id_usuario_creador`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_entrega_persona` FOREIGN KEY (`id_usuario_entrega_persona`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_rechaza` FOREIGN KEY (`id_usuario_rechaza`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_resguardante_destino` FOREIGN KEY (`id_resguardante_destino`) REFERENCES `resguardante` (`id_resguardante`),
  CONSTRAINT `fk_traspaso_resguardante_entrega` FOREIGN KEY (`id_resguardante_entrega`) REFERENCES `resguardante` (`id_resguardante`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_reversion` FOREIGN KEY (`id_traspaso_revertido`) REFERENCES `traspaso` (`id_traspaso`),
  CONSTRAINT `fk_traspaso_unidad_destino` FOREIGN KEY (`id_unidad_destino`) REFERENCES `unidad_administrativa` (`id_unidad`),
  CONSTRAINT `fk_traspaso_unidad_origen` FOREIGN KEY (`id_unidad_origen`) REFERENCES `unidad_administrativa` (`id_unidad`),
  CONSTRAINT `fk_traspaso_usuario_reversion` FOREIGN KEY (`id_usuario_reversion`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traspaso`
--

LOCK TABLES `traspaso` WRITE;
/*!40000 ALTER TABLE `traspaso` DISABLE KEYS */;
INSERT INTO `traspaso` VALUES (3,'TR-000003','ENTRE_UNIDADES','CANCELADO',90,4,2,4,1,'2026-10-07 13:07:50',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,'2026-10-07 13:15:56',NULL,NULL,NULL,NULL,NULL,'Restablecimiento','asdfg','2026-10-07 13:02:36','2026-10-07 13:15:56',NULL),(4,'TR-000004','MISMA_UNIDAD','COMPLETADO',90,90,3,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-10-07 13:28:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Restablecimiento','sdfgh','2026-10-07 13:07:25','2026-10-07 13:28:36','2026-10-07 13:28:36'),(5,'TR-000005','ENTRE_UNIDADES','VALIDADO',16,4,2,1,1,'2026-10-07 15:25:46',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'asb','asd','2026-10-07 15:12:47','2026-10-07 15:25:46',NULL),(6,'TR-000006','MISMA_UNIDAD','VALIDADO',16,16,15,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Restablecimiento','sdf','2026-10-07 15:14:15','2026-10-07 15:14:15',NULL),(7,'TR-000007','ENTRE_UNIDADES','COMPLETADO',16,4,2,4,1,'2026-10-07 16:51:42',NULL,NULL,NULL,'RESGUARDANTE_ACTUAL',1,NULL,4,'2026-10-07 17:21:18',1,'2026-10-07 17:22:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'asdd','asdf','2026-10-07 15:46:10','2026-10-07 17:22:59','2026-10-07 17:22:59'),(8,'TR-000008','ENTRE_UNIDADES','VALIDADO',90,2,14,1,1,'2026-10-07 17:39:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'asdf','asdf','2026-10-07 17:06:11','2026-10-07 17:39:56',NULL);
/*!40000 ALTER TABLE `traspaso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traspaso_bien`
--

DROP TABLE IF EXISTS `traspaso_bien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traspaso_bien` (
  `id_traspaso_bien` int NOT NULL AUTO_INCREMENT,
  `id_traspaso` int NOT NULL,
  `id_bien` int NOT NULL,
  `id_resguardante_origen` int NOT NULL,
  `id_resguardo_origen` int NOT NULL,
  `id_resguardo_destino` int DEFAULT NULL,
  `id_movimiento` int DEFAULT NULL,
  `id_ubicacion_destino` int DEFAULT NULL,
  `ubicacion_sin_cambio` tinyint(1) NOT NULL DEFAULT '1',
  `origen_snapshot` json DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_traspaso_bien`),
  UNIQUE KEY `uq_traspaso_bien` (`id_traspaso`,`id_bien`),
  UNIQUE KEY `uq_traspaso_movimiento` (`id_movimiento`),
  KEY `idx_traspaso_bien_bien` (`id_bien`),
  KEY `idx_traspaso_bien_resguardo` (`id_resguardo_origen`,`id_resguardo_destino`),
  KEY `idx_traspaso_bien_origen` (`id_resguardante_origen`),
  KEY `fk_traspaso_bien_resguardo_destino` (`id_resguardo_destino`),
  KEY `fk_traspaso_bien_ubicacion` (`id_ubicacion_destino`),
  CONSTRAINT `fk_traspaso_bien_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`),
  CONSTRAINT `fk_traspaso_bien_expediente` FOREIGN KEY (`id_traspaso`) REFERENCES `traspaso` (`id_traspaso`),
  CONSTRAINT `fk_traspaso_bien_movimiento` FOREIGN KEY (`id_movimiento`) REFERENCES `movimiento_bien` (`id_movimiento`),
  CONSTRAINT `fk_traspaso_bien_resguardante_origen` FOREIGN KEY (`id_resguardante_origen`) REFERENCES `resguardante` (`id_resguardante`),
  CONSTRAINT `fk_traspaso_bien_resguardo_destino` FOREIGN KEY (`id_resguardo_destino`) REFERENCES `resguardo` (`id_resguardo`),
  CONSTRAINT `fk_traspaso_bien_resguardo_origen` FOREIGN KEY (`id_resguardo_origen`) REFERENCES `resguardo` (`id_resguardo`),
  CONSTRAINT `fk_traspaso_bien_ubicacion` FOREIGN KEY (`id_ubicacion_destino`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traspaso_bien`
--

LOCK TABLES `traspaso_bien` WRITE;
/*!40000 ALTER TABLE `traspaso_bien` DISABLE KEYS */;
INSERT INTO `traspaso_bien` VALUES (1,3,3,4,10,NULL,NULL,NULL,1,'{\"unidad\": {\"id\": 21, \"nombre\": \"Dirección de Administración\", \"codigo_ua\": \"22600003020000S\"}, \"ubicacion\": \"TOLUCA, San Mateo Atenco\", \"resguardante\": {\"id\": 4, \"csp\": \"210061636\", \"nombre\": \"JAZMIN ONOFRE GARCIA\"}, \"resguardo_id\": 10, \"ubicacion_id\": 6, \"clave_interna\": \"CI-000003\", \"numero_inventario\": \"10KH25889\", \"unidad_resguardante\": {\"id\": 90, \"nombre\": \"Subdirección de Producción Editorial\", \"codigo_ua\": \"22600008000200L\"}}','2026-10-07 13:02:36'),(2,4,8,4,30,31,52,NULL,1,'{\"unidad\": {\"id\": 6, \"nombre\": \"Área de Responsabilidades\", \"codigo_ua\": \"22600002000003S\"}, \"ubicacion\": \"Ocuilan, Ocuilan de Arteaga\", \"resguardante\": {\"id\": 4, \"csp\": \"210061636\", \"nombre\": \"JAZMIN ONOFRE GARCIA\"}, \"resguardo_id\": 30, \"ubicacion_id\": 5, \"clave_interna\": \"CI-202609291639001\", \"numero_inventario\": \"10C027629G\", \"unidad_resguardante\": {\"id\": 90, \"nombre\": \"Subdirección de Producción Editorial\", \"codigo_ua\": \"22600008000200L\"}}','2026-10-07 13:07:25'),(3,5,1,1,28,NULL,NULL,NULL,1,'{\"unidad\": {\"id\": 10, \"nombre\": \"Departamento de Tecnologías de la Información\", \"codigo_ua\": \"22600004000003S\"}, \"ubicacion\": \"TOLUCA, TOLUCA\", \"resguardante\": {\"id\": 1, \"csp\": \"123450001\", \"nombre\": \"Juan Martinez Lopez\"}, \"resguardo_id\": 28, \"ubicacion_id\": 1, \"clave_interna\": \"CI-000001\", \"numero_inventario\": \"10C027613G\", \"unidad_resguardante\": {\"id\": 16, \"nombre\": \"Coordinación Administrativa\", \"codigo_ua\": \"22600003000000S\"}}','2026-10-07 15:12:47'),(4,6,5,1,12,NULL,NULL,NULL,1,'{\"unidad\": {\"id\": 105, \"nombre\": \"Dirección General de Calidad y Servicios Turísticos\", \"codigo_ua\": \"22600011000000L\"}, \"ubicacion\": \"TOLUCA, TOLUCA\", \"resguardante\": {\"id\": 1, \"csp\": \"123450001\", \"nombre\": \"Juan Martinez Lopez\"}, \"resguardo_id\": 12, \"ubicacion_id\": 1, \"clave_interna\": \"CI-202609231318141\", \"numero_inventario\": \"10C027615G\", \"unidad_resguardante\": {\"id\": 16, \"nombre\": \"Coordinación Administrativa\", \"codigo_ua\": \"22600003000000S\"}}','2026-10-07 15:14:15'),(5,7,2,1,9,32,53,NULL,1,'{\"unidad\": {\"id\": 10, \"nombre\": \"Departamento de Tecnologías de la Información\", \"codigo_ua\": \"22600004000003S\"}, \"ubicacion\": \"Zinacantepec, Zinacantepec\", \"resguardante\": {\"id\": 1, \"csp\": \"123450001\", \"nombre\": \"Juan Martinez Lopez\"}, \"resguardo_id\": 9, \"ubicacion_id\": 2, \"clave_interna\": \"CI-000002\", \"numero_inventario\": \"10C027611G\", \"unidad_resguardante\": {\"id\": 16, \"nombre\": \"Coordinación Administrativa\", \"codigo_ua\": \"22600003000000S\"}}','2026-10-07 15:46:10'),(6,8,3,4,10,NULL,NULL,NULL,1,'{\"unidad\": {\"id\": 21, \"nombre\": \"Dirección de Administración\", \"codigo_ua\": \"22600003020000S\"}, \"ubicacion\": \"TOLUCA, San Mateo Atenco\", \"resguardante\": {\"id\": 4, \"csp\": \"210061636\", \"nombre\": \"JAZMIN ONOFRE GARCIA\"}, \"resguardo_id\": 10, \"ubicacion_id\": 6, \"clave_interna\": \"CI-000003\", \"numero_inventario\": \"10KH25889\", \"unidad_resguardante\": {\"id\": 90, \"nombre\": \"Subdirección de Producción Editorial\", \"codigo_ua\": \"22600008000200L\"}}','2026-10-07 17:06:11');
/*!40000 ALTER TABLE `traspaso_bien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traspaso_documento`
--

DROP TABLE IF EXISTS `traspaso_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traspaso_documento` (
  `id_traspaso_documento` int NOT NULL AUTO_INCREMENT,
  `id_traspaso` int NOT NULL,
  `id_bien` int DEFAULT NULL,
  `tipo_documento` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_interna` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` smallint unsigned NOT NULL DEFAULT '1',
  `id_documento_anterior` int DEFAULT NULL,
  `id_usuario_carga` int DEFAULT NULL,
  `fecha_carga` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_traspaso_documento`),
  KEY `idx_traspaso_doc_expediente` (`id_traspaso`,`tipo_documento`,`fecha_carga`),
  KEY `idx_traspaso_doc_hash` (`sha256`),
  KEY `fk_traspaso_doc_anterior` (`id_documento_anterior`),
  KEY `fk_traspaso_doc_usuario` (`id_usuario_carga`),
  KEY `idx_traspaso_doc_bien` (`id_bien`,`id_traspaso`,`tipo_documento`),
  CONSTRAINT `fk_traspaso_doc_anterior` FOREIGN KEY (`id_documento_anterior`) REFERENCES `traspaso_documento` (`id_traspaso_documento`),
  CONSTRAINT `fk_traspaso_doc_bien` FOREIGN KEY (`id_bien`) REFERENCES `bien` (`id_bien`) ON DELETE SET NULL,
  CONSTRAINT `fk_traspaso_doc_expediente` FOREIGN KEY (`id_traspaso`) REFERENCES `traspaso` (`id_traspaso`),
  CONSTRAINT `fk_traspaso_doc_usuario` FOREIGN KEY (`id_usuario_carga`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traspaso_documento`
--

LOCK TABLES `traspaso_documento` WRITE;
/*!40000 ALTER TABLE `traspaso_documento` DISABLE KEYS */;
INSERT INTO `traspaso_documento` VALUES (1,3,NULL,'OFICIO_AUTORIZACION','TR-000003/oficio-d488be55406bb39e0954ff9fa4556a8c.pdf','8502323515771 (1).pdf','application/pdf',127351,'c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe',1,NULL,4,'2026-10-07 13:02:36'),(2,4,8,'TARJETA_RESGUARDO','TR-000004/doc-01fd0480d9d4e797258c16c433d77bf067fc.pdf','TARJETA_RESGUARDO-CI-202609291639001.pdf','application/pdf',4242,'c16f631b00e93d7fcc51f2a4f426846f9ed23745edc9a29f83c6dbb8029fc19d',1,NULL,1,'2026-10-07 13:24:27'),(3,4,8,'RESGUARDO_EQUIPO','TR-000004/doc-ce1d1a7ca7e7fc2f71f69401f14ed3696479.pdf','RESGUARDO_EQUIPO-CI-202609291639001.pdf','application/pdf',3359,'31150202473354a1616934d340965ca78727cf639087b4576acc8674eaaae8ce',1,NULL,1,'2026-10-07 13:24:27'),(4,4,8,'TARJETA_RESGUARDO','TR-000004/doc-c8a5ce54f8d086d64cd4614c890794b654f0.pdf','TARJETA_RESGUARDO-CI-202609291639001.pdf','application/pdf',4242,'7db972b2900dfa8f6962fbf46cdf2f0dbb4bab630f129b8790a7212f4a3a1b49',1,NULL,1,'2026-10-07 13:27:42'),(5,4,8,'RESGUARDO_EQUIPO','TR-000004/doc-4f3b50d7f61890bd7b46e3ffc5e7b5d21a60.pdf','RESGUARDO_EQUIPO-CI-202609291639001.pdf','application/pdf',3359,'21291968448e9cc6dca2c87b321c27199659125045fadfbb444105d3c533caa3',1,NULL,1,'2026-10-07 13:27:42'),(6,4,8,'RESGUARDO_EQUIPO','TR-000004/firmado-5-834b6a52f32b388f2e3c8a120376490e.pdf','RESGUARDO_EQUIPO-CI-202609291639001.pdf','application/pdf',3359,'31150202473354a1616934d340965ca78727cf639087b4576acc8674eaaae8ce',2,5,1,'2026-10-07 13:28:01'),(7,4,8,'TARJETA_RESGUARDO','TR-000004/firmado-4-612b3b4d88a9f2898c5d14b95b9a9836.pdf','TARJETA_RESGUARDO-CI-202609291639001.pdf','application/pdf',4242,'c16f631b00e93d7fcc51f2a4f426846f9ed23745edc9a29f83c6dbb8029fc19d',2,4,1,'2026-10-07 13:28:13'),(8,5,NULL,'OFICIO_AUTORIZACION','TR-000005/oficio-784b621019482e37f9ef9413fef072bf.pdf','RESGUARDO_EQUIPO-CI-202609291639001 (1).pdf','application/pdf',3359,'31150202473354a1616934d340965ca78727cf639087b4576acc8674eaaae8ce',1,NULL,1,'2026-10-07 15:12:47'),(9,6,5,'TARJETA_RESGUARDO','TR-000006/doc-5ecba4e239841e26a50c2629226c11cbf1b4.pdf','TARJETA_RESGUARDO-CI-202609231318141.pdf','application/pdf',4115,'a7917be757307ed025632556a508b874a2978b56979240a0711ce5abf892f0e3',1,NULL,4,'2026-10-07 15:18:53'),(10,6,5,'RESGUARDO_EQUIPO','TR-000006/doc-1e7c80cf85e6fcb893738a328456c7a4fbbd.pdf','RESGUARDO_EQUIPO-CI-202609231318141.pdf','application/pdf',3418,'ef43e8174d86b5423c264e7d3ea82dc98f5e655922e648d62463138ebd62b07f',1,NULL,4,'2026-10-07 15:18:53'),(11,6,5,'BAJA_RESGUARDANTE','TR-000006/doc-0a6a5975346e63c671d1212fbdafb1ea6a9d.pdf','BAJA_RESGUARDANTE-CI-202609231318141.pdf','application/pdf',2108,'af32d864a64abce40e4fe993929c66515c9b58b07d2b3add2577995c3694522d',1,NULL,4,'2026-10-07 15:18:53'),(12,5,1,'TARJETA_RESGUARDO','TR-000005/doc-c3dc265a3b0eb7986a957a210edddf2958d9.pdf','TARJETA_RESGUARDO-CI-000001.pdf','application/pdf',4297,'741c6f43b62d906439c7f057fd38eb9396e71a67677a3849bd6d377768caab84',1,NULL,1,'2026-10-07 15:26:09'),(13,5,1,'RESGUARDO_EQUIPO','TR-000005/doc-08c1158d743eafb3a64083f4bf4800ec9ebb.pdf','RESGUARDO_EQUIPO-CI-000001.pdf','application/pdf',3444,'e09baace08b9595f49ba7d1eb839f8c3e7ba44948d636a5a0933987d6838e466',1,NULL,1,'2026-10-07 15:26:09'),(14,7,NULL,'OFICIO_AUTORIZACION','TR-000007/oficio-62e0cb181002a05a5a0996e96008214e.pdf','8502323515771 (1) (1).pdf','application/pdf',127351,'c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe',1,NULL,4,'2026-10-07 15:46:10'),(15,6,5,'BAJA_RESGUARDANTE','TR-000006/firmado-11-85d028a92c29a167ad0eedcc7d4b9f29.pdf','TARJETA_RESGUARDO-CI-202609291639001 (1).pdf','application/pdf',4242,'c16f631b00e93d7fcc51f2a4f426846f9ed23745edc9a29f83c6dbb8029fc19d',2,11,1,'2026-10-07 16:21:50'),(16,7,2,'TARJETA_RESGUARDO','TR-000007/doc-ee053bf743e2829775ab2adbd00c511cee2a.pdf','TARJETA_RESGUARDO-CI-000002.pdf','application/pdf',4348,'6817f1c01364d737814b3c38a1633e80ea035030b77b07e64118fa3d1133c7fa',1,NULL,1,'2026-10-07 16:52:17'),(17,7,2,'RESGUARDO_EQUIPO','TR-000007/doc-6bac96037d7c20b17784e03057f54318449b.pdf','RESGUARDO_EQUIPO-CI-000002.pdf','application/pdf',3479,'21b7ad478c13c0cb3a81fec37edfffe40b823d2cfc5b7cf288be96fae75588f5',1,NULL,1,'2026-10-07 16:52:17'),(18,7,2,'BAJA_RESGUARDANTE','TR-000007/doc-a1d81b28fb02e1ee3198b190fcb2f13754ba.pdf','BAJA_RESGUARDANTE-CI-000002.pdf','application/pdf',2106,'d47c90e803e1209fcd5b656228811c8232905b5958f3b86ca106bf7b7b949f36',1,NULL,1,'2026-10-07 16:52:18'),(19,8,NULL,'OFICIO_AUTORIZACION','TR-000008/oficio-a6ecd12e4dd808804793efd426a63c8b.pdf','resguardo-del-equipo.pdf','application/pdf',1749,'f610f2d762a5f68915a24d73f1c0a18ec4c67dd662af9186f0e2dc4639695b11',1,NULL,1,'2026-10-07 17:06:11'),(20,7,2,'BAJA_RESGUARDANTE','TR-000007/reemplazo-625289647bc8eea022275cf62cd592b0.pdf','8502323515771 (1) (1).pdf','application/pdf',127351,'c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe',2,18,4,'2026-10-07 17:12:10'),(21,7,2,'BAJA_RESGUARDANTE','TR-000007/firmado-20-df5bfd8e0cd06d4228aa495d2da449cd.pdf','8502323515771 (1) (1).pdf','application/pdf',127351,'c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe',3,20,4,'2026-10-07 17:14:28'),(22,7,2,'RESGUARDO_EQUIPO','TR-000007/firmado-17-b345a837864c5e77c61dab305bdb1dee.pdf','8502323515771 (1) (1).pdf','application/pdf',127351,'c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe',2,17,4,'2026-10-07 17:20:31'),(23,7,2,'TARJETA_RESGUARDO','TR-000007/firmado-16-a24719eeee2ae7e8d1c2fe5d04b84e7c.pdf','8502323515771 (1) (1).pdf','application/pdf',127351,'c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe',2,16,4,'2026-10-07 17:20:57'),(24,8,3,'TARJETA_RESGUARDO','TR-000008/doc-8f777619f2ae13e3ffaa89564b6946c1fc0c.pdf','TARJETA_RESGUARDO-CI-000003.pdf','application/pdf',4149,'c7367f38fe1048d8594202a8a484cadb1f750b2dc4db7c94e6f7fa09bb7ccebc',1,NULL,1,'2026-10-07 18:01:04'),(25,8,3,'RESGUARDO_EQUIPO','TR-000008/doc-0cc1834c9df69b717595ca09207c121ac8db.pdf','RESGUARDO_EQUIPO-CI-000003.pdf','application/pdf',3438,'8962d7e7befe5869f024d9e64198f4af56da84bbc60000342cfc2646d315412f',1,NULL,1,'2026-10-07 18:01:04'),(26,8,3,'BAJA_RESGUARDANTE','TR-000008/doc-cd0f34b295fecd757517da3bd0b4d39998f1.pdf','BAJA_RESGUARDANTE-CI-000003.pdf','application/pdf',2106,'d460a48d0ff1b1960afbc8fa14957c9d796515eec718f60221f0c605c02f8990',1,NULL,1,'2026-10-07 18:01:04'),(27,5,1,'BAJA_RESGUARDANTE','TR-000005/doc-a6311c54041b0443e689a36642b24410bcb9.pdf','BAJA_RESGUARDANTE-CI-000001.pdf','application/pdf',2108,'4b454b3f2afb47aea4c0d248e15a6c6d652b3f66b1f6ed6ce8b71428d70986a1',1,NULL,1,'2026-10-07 18:02:49'),(28,6,5,'BAJA_RESGUARDANTE','TR-000006/firmado-11-30581e648cef94fce74459f743a11f49.pdf','BAJA_RESGUARDANTE-CI-202609231318141.pdf','application/pdf',2108,'af32d864a64abce40e4fe993929c66515c9b58b07d2b3add2577995c3694522d',3,11,4,'2026-10-07 18:05:46'),(29,8,3,'BAJA_RESGUARDANTE','TR-000008/doc-def42ca0dbcb37badec01e8cea531f453858.pdf','BAJA_RESGUARDANTE-CI-000003.pdf','application/pdf',156071,'e1a90bfc8cfc2c02ab050df7ea4670c6749021df256f99499dadcf2b54e7325e',2,26,1,'2026-10-08 12:02:47'),(30,8,3,'BAJA_RESGUARDANTE','TR-000008/doc-200277e375e00e88788f602ae040db37a9e6.pdf','BAJA_RESGUARDANTE-CI-000003.pdf','application/pdf',155941,'0e9634c3af8d05a72faf3e076557c120830e36e4ab523f9bf5f234f065d3ff2f',3,29,1,'2026-10-08 12:32:55'),(31,8,3,'BAJA_RESGUARDANTE','TR-000008/doc-cb549b9b3ba176f1d3e8789a799111289d37.pdf','BAJA_RESGUARDANTE-CI-000003.pdf','application/pdf',155963,'820f808449062c51dcd13438b89b4ad52a3abc14334784fd59b5eb9b5ab98dc0',4,30,1,'2026-10-08 12:55:42'),(32,8,3,'BAJA_RESGUARDANTE','TR-000008/doc-f0c0ace818e58bed0a9ad19cfccafe140392.pdf','BAJA_RESGUARDANTE-CI-000003.pdf','application/pdf',156011,'02c6b40b0b576d6ef945bd92544819749d10d1aec0bbea380f4fe401f90d75b2',5,31,1,'2026-10-08 12:59:25'),(33,8,3,'TARJETA_RESGUARDO','TR-000008/doc-07637796653df562deec9ee02adc632ddd2e.pdf','TARJETA_RESGUARDO-CI-000003.pdf','application/pdf',4657,'a2e806ce06673359b15ecaf3a59835cefea91db918bd3974120560c5d969edb2',2,24,1,'2026-10-08 13:54:59'),(34,8,3,'RESGUARDO_EQUIPO','TR-000008/doc-a64b191fe3df7ca18251493ed9e86baee9d8.pdf','RESGUARDO_EQUIPO-CI-000003.pdf','application/pdf',7286,'7bc44612ec306867722ed3ca89625e832ec4f3ac191be966e79f8738a927d9ab',2,25,1,'2026-10-08 15:14:17'),(35,8,3,'RESGUARDO_EQUIPO','TR-000008/doc-fb4ea68d437726e1fccf61ab8abc62e3de73.pdf','RESGUARDO_EQUIPO-CI-000003.pdf','application/pdf',7286,'ec3a4c9e1b70cf24ad6476241de9734642069d8e3b36b044155d9e227650ec2c',3,34,1,'2026-10-08 15:39:51'),(36,8,3,'TARJETA_RESGUARDO','TR-000008/doc-525406f4cfd51d3d2843d914a882eabd8610.pdf','TARJETA_RESGUARDO-CI-000003.pdf','application/pdf',137915,'a57072a7e0a90988ee02bf9e7852f00230878f807b1688e50e8fbb0a4dbd611d',3,33,1,'2026-10-08 16:18:08'),(37,8,3,'RESGUARDO_EQUIPO','TR-000008/doc-93126a78e02ce6b4727928979526f590be64.pdf','RESGUARDO_EQUIPO-CI-000003.pdf','application/pdf',58134,'071b72b5b792457c40b61c293a49dc53d5448b8cf0f62358792191d53750db36',4,35,1,'2026-10-08 16:18:08'),(38,8,3,'BAJA_RESGUARDANTE','TR-000008/doc-a9ba81f710510635218a1abecd75cd21a640.pdf','BAJA_RESGUARDANTE-CI-000003.pdf','application/pdf',156028,'c7d198cd6fb525ab7e15c279eafbbcb53b6e4fb6089203d039b7de50fbec4947',6,32,1,'2026-10-08 16:18:08');
/*!40000 ALTER TABLE `traspaso_documento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traspaso_evento`
--

DROP TABLE IF EXISTS `traspaso_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traspaso_evento` (
  `id_traspaso_evento` int NOT NULL AUTO_INCREMENT,
  `id_traspaso` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `tipo_evento` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_anterior` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_nuevo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadatos` json DEFAULT NULL,
  `fecha_evento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_traspaso_evento`),
  KEY `idx_traspaso_evento_fecha` (`id_traspaso`,`fecha_evento`),
  KEY `fk_traspaso_evento_usuario` (`id_usuario`),
  CONSTRAINT `fk_traspaso_evento_expediente` FOREIGN KEY (`id_traspaso`) REFERENCES `traspaso` (`id_traspaso`),
  CONSTRAINT `fk_traspaso_evento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `bee_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traspaso_evento`
--

LOCK TABLES `traspaso_evento` WRITE;
/*!40000 ALTER TABLE `traspaso_evento` DISABLE KEYS */;
INSERT INTO `traspaso_evento` VALUES (1,3,4,'CREACION',NULL,'PENDIENTE_AUTORIZACION',NULL,'{\"tipo\": \"ENTRE_UNIDADES\", \"folio\": \"TR-000003\", \"bienes\": [3], \"resguardante_origen\": 4}','2026-10-07 13:02:36'),(2,3,4,'CARGA_DOCUMENTO','PENDIENTE_AUTORIZACION','PENDIENTE_AUTORIZACION',NULL,'{\"tipo\": \"OFICIO_AUTORIZACION\", \"sha256\": \"c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe\", \"version\": 1, \"documento\": 1, \"fecha_oficio\": \"2026-10-07\", \"numero_oficio\": \"asdfg44588-54521\"}','2026-10-07 13:02:36'),(3,4,4,'CREACION',NULL,'DOCUMENTOS',NULL,'{\"tipo\": \"MISMA_UNIDAD\", \"folio\": \"TR-000004\", \"bienes\": [8], \"resguardante_origen\": 4}','2026-10-07 13:07:25'),(4,3,1,'AUTORIZACION','PENDIENTE_AUTORIZACION','DOCUMENTOS','Oficio revisado y traspaso autorizado.',NULL,'2026-10-07 13:07:50'),(5,3,6,'CANCELACION','DOCUMENTOS','CANCELADO',NULL,NULL,'2026-10-07 13:15:56'),(6,4,4,'EDICION_DATOS_DOCUMENTO','DOCUMENTOS','DOCUMENTOS',NULL,'{\"antes\": {\"motivo\": \"Restablecimiento\", \"observaciones\": \"sdfgh\"}, \"despues\": {\"motivo\": \"Restablecimiento\", \"observaciones\": \"sdfgh\"}, \"documento_desde\": 1}','2026-10-07 13:20:03'),(7,4,1,'GENERACION_DOCUMENTO','DOCUMENTOS','DOCUMENTOS',NULL,'{\"bien\": 8, \"tipo\": \"TARJETA_RESGUARDO\", \"documento\": 2}','2026-10-07 13:24:27'),(8,4,1,'GENERACION_DOCUMENTO','DOCUMENTOS','DOCUMENTOS',NULL,'{\"bien\": 8, \"tipo\": \"RESGUARDO_EQUIPO\", \"documento\": 3}','2026-10-07 13:24:27'),(9,4,1,'AVANCE','DOCUMENTOS','FIRMA',NULL,NULL,'2026-10-07 13:24:27'),(10,4,1,'EDICION_DATOS_DOCUMENTO','FIRMA','DOCUMENTOS',NULL,'{\"antes\": {\"motivo\": \"Restablecimiento\", \"observaciones\": \"sdfgh\"}, \"despues\": {\"motivo\": \"Restablecimiento\", \"observaciones\": \"sdfgh\"}, \"documento_desde\": 4}','2026-10-07 13:27:16'),(11,4,1,'GENERACION_DOCUMENTO','DOCUMENTOS','DOCUMENTOS',NULL,'{\"bien\": 8, \"tipo\": \"TARJETA_RESGUARDO\", \"documento\": 4}','2026-10-07 13:27:42'),(12,4,1,'GENERACION_DOCUMENTO','DOCUMENTOS','DOCUMENTOS',NULL,'{\"bien\": 8, \"tipo\": \"RESGUARDO_EQUIPO\", \"documento\": 5}','2026-10-07 13:27:42'),(13,4,1,'AVANCE','DOCUMENTOS','FIRMA',NULL,NULL,'2026-10-07 13:27:42'),(14,4,1,'CARGA_DOCUMENTO_FIRMADO','FIRMA','FIRMA',NULL,'{\"bien\": 8, \"tipo\": \"RESGUARDO_EQUIPO\", \"sha256\": \"31150202473354a1616934d340965ca78727cf639087b4576acc8674eaaae8ce\", \"version\": 2, \"documento\": 6, \"documento_origen\": 5}','2026-10-07 13:28:01'),(15,4,1,'CARGA_DOCUMENTO_FIRMADO','FIRMA','FIRMA',NULL,'{\"bien\": 8, \"tipo\": \"TARJETA_RESGUARDO\", \"sha256\": \"c16f631b00e93d7fcc51f2a4f426846f9ed23745edc9a29f83c6dbb8029fc19d\", \"version\": 2, \"documento\": 7, \"documento_origen\": 4}','2026-10-07 13:28:13'),(16,4,1,'EJECUCION','FIRMA','COMPLETADO',NULL,NULL,'2026-10-07 13:28:36'),(17,5,1,'CREACION',NULL,'PENDIENTE_VALIDACION',NULL,'{\"tipo\": \"ENTRE_UNIDADES\", \"folio\": \"TR-000005\", \"bienes\": [1], \"resguardante_origen\": 1}','2026-10-07 15:12:47'),(18,5,1,'CARGA_DOCUMENTO','PENDIENTE_VALIDACION','PENDIENTE_VALIDACION',NULL,'{\"tipo\": \"OFICIO_AUTORIZACION\", \"sha256\": \"31150202473354a1616934d340965ca78727cf639087b4576acc8674eaaae8ce\", \"version\": 1, \"documento\": 8, \"fecha_oficio\": \"2026-10-07\", \"numero_oficio\": \"123\"}','2026-10-07 15:12:47'),(19,6,4,'CREACION',NULL,'VALIDADO',NULL,'{\"tipo\": \"MISMA_UNIDAD\", \"folio\": \"TR-000006\", \"bienes\": [5], \"resguardante_origen\": 1}','2026-10-07 15:14:15'),(20,6,4,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"TARJETA_RESGUARDO\", \"documento\": 9}','2026-10-07 15:18:53'),(21,6,4,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"RESGUARDO_EQUIPO\", \"documento\": 10}','2026-10-07 15:18:53'),(22,6,4,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"BAJA_RESGUARDANTE\", \"documento\": 11}','2026-10-07 15:18:53'),(23,5,1,'VALIDACION_DOCUMENTAL','PENDIENTE_VALIDACION','VALIDADO','El administrador validó documentalmente el oficio externo.',NULL,'2026-10-07 15:25:46'),(24,5,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 1, \"tipo\": \"TARJETA_RESGUARDO\", \"documento\": 12}','2026-10-07 15:26:09'),(25,5,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 1, \"tipo\": \"RESGUARDO_EQUIPO\", \"documento\": 13}','2026-10-07 15:26:09'),(26,7,4,'CREACION',NULL,'PENDIENTE_VALIDACION',NULL,'{\"tipo\": \"ENTRE_UNIDADES\", \"folio\": \"TR-000007\", \"bienes\": [2], \"resguardante_origen\": 1}','2026-10-07 15:46:10'),(27,7,4,'CARGA_DOCUMENTO','PENDIENTE_VALIDACION','PENDIENTE_VALIDACION',NULL,'{\"tipo\": \"OFICIO_AUTORIZACION\", \"sha256\": \"c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe\", \"version\": 1, \"documento\": 14, \"fecha_oficio\": \"2026-10-07\", \"numero_oficio\": \"asdfghjk\"}','2026-10-07 15:46:10'),(28,6,1,'CARGA_DOCUMENTO_FIRMADO','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"BAJA_RESGUARDANTE\", \"sha256\": \"c16f631b00e93d7fcc51f2a4f426846f9ed23745edc9a29f83c6dbb8029fc19d\", \"version\": 2, \"documento\": 15, \"documento_origen\": 11}','2026-10-07 16:21:50'),(29,7,1,'VALIDACION_DOCUMENTAL','PENDIENTE_VALIDACION','VALIDADO','El administrador validó documentalmente el oficio externo.',NULL,'2026-10-07 16:51:42'),(30,7,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 2, \"tipo\": \"TARJETA_RESGUARDO\", \"documento\": 16}','2026-10-07 16:52:17'),(31,7,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 2, \"tipo\": \"RESGUARDO_EQUIPO\", \"documento\": 17}','2026-10-07 16:52:17'),(32,7,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 2, \"tipo\": \"BAJA_RESGUARDANTE\", \"documento\": 18}','2026-10-07 16:52:18'),(33,8,1,'CREACION',NULL,'PENDIENTE_VALIDACION',NULL,'{\"tipo\": \"ENTRE_UNIDADES\", \"folio\": \"TR-000008\", \"bienes\": [3], \"resguardante_origen\": 4}','2026-10-07 17:06:11'),(34,8,1,'CARGA_DOCUMENTO','PENDIENTE_VALIDACION','PENDIENTE_VALIDACION',NULL,'{\"tipo\": \"OFICIO_AUTORIZACION\", \"sha256\": \"f610f2d762a5f68915a24d73f1c0a18ec4c67dd662af9186f0e2dc4639695b11\", \"version\": 1, \"documento\": 19, \"fecha_oficio\": \"2026-10-07\", \"numero_oficio\": \"kjihgffvh\"}','2026-10-07 17:06:11'),(35,7,4,'CARGA_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"tipo\": \"BAJA_RESGUARDANTE\", \"sha256\": \"c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe\", \"version\": 2, \"documento\": 20}','2026-10-07 17:12:10'),(36,7,4,'CARGA_DOCUMENTO_FIRMADO','VALIDADO','VALIDADO',NULL,'{\"bien\": 2, \"tipo\": \"BAJA_RESGUARDANTE\", \"sha256\": \"c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe\", \"version\": 3, \"documento\": 21, \"documento_origen\": 20}','2026-10-07 17:14:28'),(37,7,4,'CARGA_DOCUMENTO_FIRMADO','VALIDADO','VALIDADO',NULL,'{\"bien\": 2, \"tipo\": \"RESGUARDO_EQUIPO\", \"sha256\": \"c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe\", \"version\": 2, \"documento\": 22, \"documento_origen\": 17}','2026-10-07 17:20:31'),(38,7,4,'CARGA_DOCUMENTO_FIRMADO','VALIDADO','VALIDADO',NULL,'{\"bien\": 2, \"tipo\": \"TARJETA_RESGUARDO\", \"sha256\": \"c4e3a0e315537b6b6e6ecc69d726c7cf830f17dff60b0d7593f1aeff0309c5fe\", \"version\": 2, \"documento\": 23, \"documento_origen\": 16}','2026-10-07 17:20:57'),(39,7,4,'ENTREGA_REGISTRADA','VALIDADO','VALIDADO',NULL,'{\"resguardante\": 1, \"tipo_entrega\": \"RESGUARDANTE_ACTUAL\", \"responsable_fisico\": \"sdf\"}','2026-10-07 17:21:18'),(40,7,1,'RECEPCION_REGISTRADA','VALIDADO','VALIDADO',NULL,'{\"responsable_fisico\": \"lkjhg\", \"resguardante_receptor\": 2}','2026-10-07 17:22:51'),(41,7,1,'EJECUCION','VALIDADO','COMPLETADO',NULL,NULL,'2026-10-07 17:22:59'),(42,8,1,'VALIDACION_DOCUMENTAL','PENDIENTE_VALIDACION','VALIDADO','El administrador validó documentalmente el oficio externo.',NULL,'2026-10-07 17:39:56'),(43,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"TARJETA_RESGUARDO\", \"documento\": 24}','2026-10-07 18:01:04'),(44,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"RESGUARDO_EQUIPO\", \"documento\": 25}','2026-10-07 18:01:04'),(45,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"BAJA_RESGUARDANTE\", \"documento\": 26}','2026-10-07 18:01:04'),(46,5,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 1, \"tipo\": \"BAJA_RESGUARDANTE\", \"documento\": 27}','2026-10-07 18:02:49'),(47,6,1,'VALIDACION_EVIDENCIA','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"BAJA_RESGUARDANTE\", \"documento_origen\": 11, \"documento_evidencia\": 15}','2026-10-07 18:04:12'),(48,6,4,'CARGA_DOCUMENTO_FIRMADO','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"BAJA_RESGUARDANTE\", \"sha256\": \"af32d864a64abce40e4fe993929c66515c9b58b07d2b3add2577995c3694522d\", \"version\": 3, \"documento\": 28, \"documento_origen\": 11}','2026-10-07 18:05:46'),(49,6,1,'VALIDACION_EVIDENCIA','VALIDADO','VALIDADO',NULL,'{\"bien\": 5, \"tipo\": \"BAJA_RESGUARDANTE\", \"documento_origen\": 11, \"documento_evidencia\": 28}','2026-10-07 18:06:24'),(50,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"BAJA_RESGUARDANTE\", \"formato\": \"BAJA_INSTITUCIONAL_V1\", \"version\": 2, \"documento\": 29, \"formato_hash\": \"f9d9b0673cdb3489ae0e961ba076f942e77caa11e805a2050b8deb2f020a5758\"}','2026-10-08 12:02:47'),(51,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"BAJA_RESGUARDANTE\", \"formato\": \"BAJA_INSTITUCIONAL_V1\", \"version\": 3, \"documento\": 30, \"formato_hash\": \"89034dbb0157aeae92a98e397197a2ec52c0a9b150f5135d0c80cc7504a03109\"}','2026-10-08 12:32:55'),(52,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"BAJA_RESGUARDANTE\", \"formato\": \"BAJA_INSTITUCIONAL_V1\", \"version\": 4, \"documento\": 31, \"formato_hash\": \"3b002dd520328b5c95b28fc9be1090e0bbf7bab0fe3922f962d9494be3ee8c82\"}','2026-10-08 12:55:42'),(53,8,1,'EDICION_CCP_BAJA','VALIDADO','VALIDADO',NULL,'{\"ccp\": \"}\", \"bien\": 3, \"documento_baja\": 31}','2026-10-08 12:58:41'),(54,8,1,'EDICION_CCP_BAJA','VALIDADO','VALIDADO',NULL,'{\"ccp\": \"}}\", \"bien\": 3, \"documento_baja\": 31}','2026-10-08 12:58:42'),(55,8,1,'EDICION_CCP_BAJA','VALIDADO','VALIDADO',NULL,'{\"ccp\": \"kjh\", \"bien\": 3, \"documento_baja\": 31}','2026-10-08 12:58:53'),(56,8,1,'EDICION_CCP_BAJA','VALIDADO','VALIDADO',NULL,'{\"ccp\": \"kjhasdfghjkl\", \"bien\": 3, \"documento_baja\": 31}','2026-10-08 12:59:12'),(57,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"BAJA_RESGUARDANTE\", \"formato\": \"BAJA_INSTITUCIONAL_V1\", \"version\": 5, \"documento\": 32, \"formato_hash\": \"1af161c1664e34f2e988665a4a4116963c1a468e9670d24fb9234999d4676f58\"}','2026-10-08 12:59:25'),(58,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"TARJETA_RESGUARDO\", \"formato\": \"TARJETA_RESGUARDO_V1\", \"version\": 2, \"documento\": 33, \"formato_hash\": \"631755ee239edcfbfd0510f0440df63440116a4ecc1ec2bfbeaa56a7b8d09d04\"}','2026-10-08 13:54:59'),(59,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"RESGUARDO_EQUIPO\", \"formato\": \"RESGUARDO_EQUIPO_INSTITUCIONAL_V1\", \"version\": 2, \"documento\": 34, \"formato_hash\": \"4749409a4f01f5a9a330a69bf0d833c446cbe1f3c17b192bef0a5f914cd05b1a\"}','2026-10-08 15:14:17'),(60,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"RESGUARDO_EQUIPO\", \"formato\": \"RESGUARDO_EQUIPO_INSTITUCIONAL_V1\", \"version\": 3, \"documento\": 35, \"formato_hash\": \"927ef866399ded8789665b512087cccc9dc9769e27195e4fd06b710e79e379dc\"}','2026-10-08 15:39:51'),(61,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"TARJETA_RESGUARDO\", \"formato\": \"TARJETA_RESGUARDO_V1\", \"version\": 3, \"documento\": 36, \"formato_hash\": \"7ab9c247e7ba9308e07c5dde89ad974298c6977f41896a76cbf998abf3ae256a\"}','2026-10-08 16:18:08'),(62,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"RESGUARDO_EQUIPO\", \"formato\": \"RESGUARDO_EQUIPO_INSTITUCIONAL_V1\", \"version\": 4, \"documento\": 37, \"formato_hash\": \"19765c754aead9d051d567ff795feb3c8185f3dac92d6dfc1bc8b665f8702916\"}','2026-10-08 16:18:08'),(63,8,1,'GENERACION_DOCUMENTO','VALIDADO','VALIDADO',NULL,'{\"bien\": 3, \"tipo\": \"BAJA_RESGUARDANTE\", \"formato\": \"BAJA_INSTITUCIONAL_V1\", \"version\": 6, \"documento\": 38, \"formato_hash\": \"65a30bd70e6422718492c5e8a47f2f0bf342f4cea8f058a06744751bc31de195\"}','2026-10-08 16:18:08');
/*!40000 ALTER TABLE `traspaso_evento` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicacion`
--

LOCK TABLES `ubicacion` WRITE;
/*!40000 ALTER TABLE `ubicacion` DISABLE KEYS */;
INSERT INTO `ubicacion` VALUES (1,'TOLUCA','TOLUCA',NULL,1),(2,'Zinacantepec','Zinacantepec','Departamento de Tecnologias de la Informacion',1),(3,'Almoloya de Juarez','Almoloya de Juarez','Departamento de Recursos Humanos',1),(4,'TOLUCA','SAN BUENAVENTURA','Departamento de Recursos Materiales',1),(5,'Ocuilan','Ocuilan de Arteaga',NULL,1),(6,'TOLUCA','San Mateo Atenco',NULL,1);
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
  `tipo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_padre` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_unidad`),
  KEY `FK_ua_padre` (`id_padre`),
  KEY `idx_unidad_tipo` (`tipo`),
  CONSTRAINT `FK_ua_padre` FOREIGN KEY (`id_padre`) REFERENCES `unidad_administrativa` (`id_unidad`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_unidad_tipo` FOREIGN KEY (`tipo`) REFERENCES `tipo_unidad_administrativa` (`clave_tipo`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidad_administrativa`
--

LOCK TABLES `unidad_administrativa` WRITE;
/*!40000 ALTER TABLE `unidad_administrativa` DISABLE KEYS */;
INSERT INTO `unidad_administrativa` VALUES (1,'22600000000000L','SECRETARIA','Secretaría de Cultura y Turismo',NULL,1),(2,'22600001000000S','SUBSECRETARIA','Secretaría Particular',1,1),(3,'22600002000000S','STAFF','Órgano Interno de Control',1,1),(4,'22600002000001S','PENDIENTE','Área de Auditoría',3,1),(5,'22600002000002S','PENDIENTE','Área de Quejas',3,1),(6,'22600002000003S','PENDIENTE','Área de Responsabilidades',3,1),(7,'22600004000000S','SUBSECRETARIA','Unidad de Información, Planeación, Programación y Evaluación',1,1),(8,'22600004000001S','DEPARTAMENTO','Departamento de Información y Planeación',7,1),(9,'22600004000002S','DEPARTAMENTO','Departamento de Programación y Evaluación',7,1),(10,'22600004000003S','DEPARTAMENTO','Departamento de Tecnologías de la Información',7,1),(11,'22600004000004S','DEPARTAMENTO','Departamento de Mejora Regulatoria',7,1),(12,'22600007000000S','SUBSECRETARIA','Coordinación Jurídica, de Igualdad de Género y Erradicación de la Violencia',1,1),(13,'22600007000100S','SUBDIRECCION','Subdirección Jurídica de Cultura',12,1),(14,'22600007000200S','SUBDIRECCION','Subdirección Jurídica de Turismo',12,1),(15,'22600007000300S','SUBDIRECCION','Subdirección de Normatividad y Consulta',12,1),(16,'22600003000000S','SUBSECRETARIA','Coordinación Administrativa',1,1),(17,'22600003010000S','DIRECCION','Dirección de Finanzas',16,1),(18,'22600003010001S','DEPARTAMENTO','Departamento de Control de Ingresos',17,1),(19,'22600003010002S','DEPARTAMENTO','Departamento de Contabilidad',17,1),(20,'22600003010003S','DEPARTAMENTO','Departamento de Control Presupuestal',17,1),(21,'22600003020000S','DIRECCION','Dirección de Administración',16,1),(22,'22600003020001S','DEPARTAMENTO','Departamento de Recursos Humanos',21,1),(23,'22600003020002S','DEPARTAMENTO','Departamento de Recursos Materiales',21,1),(24,'22600003020003S','DEPARTAMENTO','Departamento de Servicios Generales',21,1),(25,'22600003030000S','DIRECCION','Dirección de Infraestructura',16,1),(26,'22600003030001S','DEPARTAMENTO','Departamento de Proyectos de Inversión y Control de Estimaciones',25,1),(27,'22600003030004S','DEPARTAMENTO','Departamento de Mantenimiento',25,1),(28,'22600003030005S','DEPARTAMENTO','Departamento de Arquitectura y Diseño',25,1),(29,'22600003000001S','DEPARTAMENTO','Departamento de Gestión Documental y Control de Archivos',16,1),(30,'22600200000000L','SUBSECRETARIA','Subsecretaría de Cultura',1,1),(31,'22600201000000L','DIRECCION','Dirección General de Patrimonio y Servicios Culturales del Valle de los Volcanes',30,1),(32,'22600201000100S','PENDIENTE','Delegación Administrativa',31,1),(33,'22600201000101L','DEPARTAMENTO','Departamento de Supervisión de la Calidad de los Servicios y Validación de Pagos (PPS)',32,1),(34,'22600201010000L','DIRECCION_AREA','Dirección de Patrimonio Cultural',31,1),(35,'22600201010001L','DEPARTAMENTO','Departamento de Bibliotecas',34,1),(36,NULL,'PENDIENTE','Bibliotecas',35,1),(37,'22600201010002L','DEPARTAMENTO','Departamento de Museos',34,1),(38,NULL,'PENDIENTE','Museos',37,1),(39,'22600201020000L','DIRECCION_AREA','Dirección de Servicios Culturales',31,1),(40,'22600201020001L','DEPARTAMENTO','Departamento de Enseñanza Artística, Centros Regionales y Capacitación Cultural',39,1),(41,'22600201020002L','DEPARTAMENTO','Departamento de Actividades Culturales y Artísticas',39,1),(42,NULL,'PENDIENTE','Centros Regionales de Cultura',41,1),(43,'22600202000000L','DIRECCION','Dirección General de Patrimonio y Servicios Culturales del Valle de Toluca',30,1),(44,'22600202010000S','DIRECCION_AREA','Unidad de Desarrollo de Proyectos Culturales',43,1),(45,'22600202000100S','PENDIENTE','Delegación Administrativa',43,1),(46,'22600202010000L','DIRECCION','Dirección de Patrimonio Cultural',43,1),(47,'22600202010100L','SUBDIRECCION','Subdirección de Bibliotecas y Documentación',46,1),(48,'22600202010101L','DEPARTAMENTO','Archivo Histórico del Estado De México',47,1),(49,'22600202010102L','DEPARTAMENTO','Departamento de Fomento a la Lectura',47,1),(50,'22600202010103L','DEPARTAMENTO','Departamento de Bibliotecas',47,1),(51,NULL,'PENDIENTE','Bibliotecas',50,1),(52,'22600202010200L','SUBDIRECCION','Subdirección de Acervo Cultural',46,1),(53,'22600202010201L','DEPARTAMENTO','Departamento de Restauración',52,1),(54,'22600202010202L','DEPARTAMENTO','Departamento de Museos',52,1),(55,NULL,'PENDIENTE','Museos',54,1),(56,'22600202020000L','DIRECCION','Dirección de Servicios Culturales',43,1),(57,'22600202020001L','PENDIENTE','Coordinación de Artes Escénicas',56,1),(58,'22600202020100L','SUBDIRECCION','Subdirección de Promoción Cultural',56,1),(59,'22600202020101L','DEPARTAMENTO','Departamento de Capacitación Cultural y Vinculación',58,1),(60,'22600202020102L','DEPARTAMENTO','Departamento de Artes Plásticas y Visuales',58,1),(61,NULL,'PENDIENTE','Centros Regionales de Cultura',60,1),(62,'22600202030000L','DIRECCION_AREA','Dirección de la Cineteca Mexiquense',43,1),(63,'22600202030100L','SUBDIRECCION','Subdirección de Acervos',62,1),(64,'22600202030101L','DEPARTAMENTO','Departamento de Investigación y Preservación de Acervos',63,1),(65,'22600202030102L','DEPARTAMENTO','Departamento de Operación de Salas y Apoyo Técnico',63,1),(66,'22600202030200L','SUBDIRECCION','Subdirección de Programación',62,1),(67,'22600202030201L','DEPARTAMENTO','Departamento de Contenidos Audiovisuales',66,1),(68,'22600202030202L','DEPARTAMENTO','Departamento de Relaciones Públicas',66,1),(69,'22600200010000L','DIRECCION_AREA','Dirección de Desarrollo de Proyectos Culturales',30,1),(70,'22600200020000L','DIRECCION_AREA','Dirección del Conservatorio de Música del Estado de México',30,1),(71,'22600200020100S','PENDIENTE','Delegación Administrativa',70,1),(72,'22600200020200L','SUBDIRECCION','Subdirección Académica',70,1),(73,NULL,'PENDIENTE','Coordinación de Licenciatura',72,1),(74,NULL,'PENDIENTE','Control Escolar de Licenciatura',73,1),(75,NULL,'PENDIENTE','Coordinación de Carreras Técnicas',72,1),(76,NULL,'PENDIENTE','Control Escolar de Carreras Técnicas',75,1),(77,NULL,'PENDIENTE','Informática',72,1),(78,NULL,'PENDIENTE','Biblioteca',72,1),(79,NULL,'PENDIENTE','Fonoteca',72,1),(80,NULL,'PENDIENTE','Relaciones Públicas y Vinculación',70,1),(81,'22600200030000L','DIRECCION_AREA','Dirección de la Orquesta Sinfónica del Estado de México',30,1),(82,'22600200030100S','PENDIENTE','Delegación Administrativa',81,1),(83,'22600200030200L','SUBDIRECCION','Subdirección Artística de la Orquesta Filarmónica Mexiquense',81,1),(84,'22600200030300L','SUBDIRECCION','Subdirección Artística del Coro Polifónico del Estado de México',81,1),(85,'22600200030400L','SUBDIRECCION','Subdirección Operativa',81,1),(86,'22600200030500L','SUBDIRECCION','Subdirección de Relaciones Públicas',81,1),(87,'22600200030600L','SUBDIRECCION','Subdirección de Promoción y Ventas',81,1),(88,'22600008000000L','DIRECCION','Secretaría Ejecutiva del Consejo Editorial de la Administración Pública Estatal',1,1),(89,'22600008000100S','PENDIENTE','Delegación Administrativa',88,1),(90,'22600008000200L','SUBDIRECCION','Subdirección de Producción Editorial',88,1),(91,'22600008000201L','DEPARTAMENTO','Departamento de Ediciones, Publicidad y Diseño Gráfico',90,1),(92,'22600008000300L','SUBDIRECCION','Subdirección de Difusión y Distribución',88,1),(93,'22600009000000L','DIRECCION','Dirección General de Planeación y Desarrollo Turístico Sostenible',1,1),(94,'22600009000100S','PENDIENTE','Delegación Administrativa',93,1),(95,'22600009010000L','DIRECCION_AREA','Dirección de Fomento y Desarrollo Turístico Sostenible',93,1),(96,'22600009010001L','DEPARTAMENTO','Departamento de Planeación en Materia Turística',95,1),(97,'22600009010002L','DEPARTAMENTO','Departamento de Desarrollo Turístico Sostenible',95,1),(98,'22600009000200L','SUBDIRECCION','Subdirección de Vinculación y Evaluación Turística',93,1),(99,'22600010000000L','DIRECCION','Dirección General de Promoción Turística',1,1),(100,'22600010010000L','DIRECCION_AREA','Dirección de Atracción de Reuniones, Congresos y Convenciones',99,1),(101,'22600010010001L','DEPARTAMENTO','Departamento de Vinculación Empresarial y Reuniones de Negocios',100,1),(102,'22600010010002L','DEPARTAMENTO','Departamento de Promoción Turística',100,1),(103,'22600010020000L','DIRECCION_AREA','Dirección de Proyectos Turísticos',99,1),(104,'22600010020001L','DEPARTAMENTO','Departamento de Seguimiento y Evaluación de Proyectos Turísticos',103,1),(105,'22600011000000L','DIRECCION','Dirección General de Calidad y Servicios Turísticos',1,1),(106,'22600011000100L','SUBDIRECCION','Subdirección de Capacitación Turística',105,1),(107,'22600011000001L','DEPARTAMENTO','Departamento de Calidad, Certificación y Regulación Turística',105,1);
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

-- Dump completed on 2026-10-08 16:22:31
