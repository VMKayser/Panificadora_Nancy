-- MySQL dump 10.13  Distrib 8.0.32, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	8.0.32

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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1760151622),('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1760151622;',1760151622),('laravel-cache-421762700a2c10da200dfdb9fe1032c83dd620c2','i:1;',1760152031),('laravel-cache-421762700a2c10da200dfdb9fe1032c83dd620c2:timer','i:1760152031;',1760152031);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capacidad_produccion`
--

DROP TABLE IF EXISTS `capacidad_produccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capacidad_produccion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `limite_semanal` int NOT NULL,
  `semana_inicio` date NOT NULL,
  `semana_fin` date NOT NULL,
  `cantidad_reservada` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `capacidad_produccion_producto_id_semana_inicio_index` (`producto_id`,`semana_inicio`),
  CONSTRAINT `capacidad_produccion_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capacidad_produccion`
--

LOCK TABLES `capacidad_produccion` WRITE;
/*!40000 ALTER TABLE `capacidad_produccion` DISABLE KEYS */;
/*!40000 ALTER TABLE `capacidad_produccion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esta_activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categorias_url_unique` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Panes','panes','Variedad de panes artesanales',NULL,1,0,1,'2025-10-10 01:56:59','2025-10-10 01:56:59'),(2,'Empanadas','empanadas','Empanadas caseras con diferentes rellenos',NULL,1,0,2,'2025-10-10 01:56:59','2025-10-10 01:56:59'),(3,'Temporada','temporada','Productos especiales de temporada',NULL,1,0,3,'2025-10-10 01:56:59','2025-10-10 01:56:59');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `ci` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_cliente` enum('regular','mayorista','vip') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `total_pedidos` int NOT NULL DEFAULT '0',
  `total_gastado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_ultimo_pedido` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_email_unique` (`email`),
  KEY `clientes_email_index` (`email`),
  KEY `clientes_activo_index` (`activo`),
  KEY `clientes_fecha_ultimo_pedido_index` (`fecha_ultimo_pedido`),
  KEY `clientes_user_id_foreign` (`user_id`),
  KEY `idx_clientes_ci` (`ci`),
  KEY `idx_clientes_telefono` (`telefono`),
  KEY `idx_clientes_activo` (`activo`),
  KEY `idx_clientes_tipo_cliente` (`tipo_cliente`),
  CONSTRAINT `clientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,3,'María','García','maria@cliente.com','65559876','Av. 6 de Agosto #123, La Paz','7654321 LP','regular',0,0.00,NULL,1,NULL,'2025-10-10 21:24:35','2025-10-10 21:24:35',NULL),(2,4,'Juan','Pérez','juan@cliente.com','65555555','Calle Comercio #456, La Paz','9876543 LP','regular',0,0.00,NULL,1,NULL,'2025-10-10 21:24:35','2025-10-10 21:24:35',NULL),(3,5,'Ana','López','ana@cliente.com','65554321','Zona Sur, Calle 21 #789, La Paz','1234567 LP','mayorista',0,0.00,NULL,1,NULL,'2025-10-10 21:24:36','2025-10-10 21:24:36',NULL),(4,NULL,'Test','User','testuser@example.com','999999999',NULL,NULL,'regular',1,0.00,'2025-10-10',1,NULL,'2025-10-10 22:44:35','2025-10-11 22:03:41',NULL),(5,NULL,'andre','claros','antrak13@gmail.com','75587987',NULL,NULL,'regular',1,15.00,'2025-10-10',1,NULL,'2025-10-10 23:05:52','2025-10-11 22:03:26',NULL),(6,10,'Panadero','Test','panadero-test@mailinator.com',NULL,NULL,NULL,'regular',0,0.00,NULL,1,NULL,'2025-10-12 04:02:22','2025-10-12 04:02:22',NULL),(7,14,'test','sync','test-sync@mailinator.com','',NULL,NULL,'regular',0,0.00,NULL,1,NULL,'2025-10-12 14:26:18','2025-10-12 14:26:18',NULL),(8,NULL,'ClienteTest','C','testcliente+1760296705@example.com','44455566',NULL,'CI-C1760296705','regular',0,0.00,NULL,1,NULL,'2025-10-12 19:18:25','2025-10-12 19:18:25',NULL);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_sistema`
--

DROP TABLE IF EXISTS `configuracion_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion_sistema` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Clave única de configuración',
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Valor de la configuración',
  `tipo` enum('texto','numero','boolean','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'texto' COMMENT 'Tipo de dato',
  `descripcion` text COLLATE utf8mb4_unicode_ci COMMENT 'Descripción de la configuración',
  `grupo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Grupo al que pertenece (produccion, ventas, sistema, etc.)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `configuracion_sistema_clave_unique` (`clave`),
  KEY `configuracion_sistema_clave_index` (`clave`),
  KEY `configuracion_sistema_grupo_index` (`grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_sistema`
--

LOCK TABLES `configuracion_sistema` WRITE;
/*!40000 ALTER TABLE `configuracion_sistema` DISABLE KEYS */;
INSERT INTO `configuracion_sistema` VALUES (1,'precio_kilo_produccion','15.00','numero','Precio pagado por kilo de producción a panaderos','produccion','2025-10-11 20:34:37','2025-10-11 20:34:37'),(2,'meta_produccion_diaria','500','numero','Meta de kilos a producir diariamente','produccion','2025-10-11 20:34:37','2025-10-11 20:34:37'),(3,'comision_vendedor_defecto','3.00','numero','Porcentaje de comisión por defecto para vendedores','ventas','2025-10-11 20:34:38','2025-10-11 20:34:38'),(4,'descuento_maximo_defecto','50.00','numero','Descuento máximo en Bs que puede dar un vendedor','ventas','2025-10-11 20:34:38','2025-10-11 20:34:38'),(5,'descuento_mayorista_porcentaje','10.00','numero','Porcentaje de descuento para clientes mayoristas','ventas','2025-10-11 20:34:38','2025-10-11 20:34:38'),(6,'stock_minimo_alerta','10','numero','Cantidad mínima de stock para generar alerta','inventario','2025-10-11 20:34:38','2025-10-11 20:34:38'),(7,'dias_anticipacion_pedidos','1','numero','Días de anticipación requeridos para pedidos especiales','inventario','2025-10-11 20:34:38','2025-10-11 20:34:38'),(8,'nombre_empresa','Panificadora Nancy','texto','Nombre de la empresa','sistema','2025-10-11 20:34:38','2025-10-11 20:34:38'),(9,'telefono_contacto','+591 764 90687','texto','Teléfono de contacto principal','sistema','2025-10-11 20:34:38','2025-10-11 20:34:38'),(10,'direccion','Av. Martín Cardenas, Quillacollo, Cochabamba','texto','Dirección del negocio','sistema','2025-10-11 20:34:38','2025-10-11 20:34:38'),(11,'horario_apertura','07:00','texto','Hora de apertura','sistema','2025-10-11 20:34:38','2025-10-11 20:34:38'),(12,'horario_cierre','20:00','texto','Hora de cierre','sistema','2025-10-11 20:34:38','2025-10-11 20:34:38');
/*!40000 ALTER TABLE `configuracion_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedidos`
--

DROP TABLE IF EXISTS `detalle_pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedidos_id` bigint unsigned NOT NULL,
  `productos_id` bigint unsigned NOT NULL,
  `nombre_producto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `requiere_anticipacion` tinyint(1) NOT NULL DEFAULT '0',
  `tiempo_anticipacion` int DEFAULT NULL,
  `unidad_tiempo` enum('horas','dias','semanas') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalle_pedidos_pedidos_id_foreign` (`pedidos_id`),
  KEY `detalle_pedidos_productos_id_foreign` (`productos_id`),
  CONSTRAINT `detalle_pedidos_pedidos_id_foreign` FOREIGN KEY (`pedidos_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_pedidos_productos_id_foreign` FOREIGN KEY (`productos_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedidos`
--

LOCK TABLES `detalle_pedidos` WRITE;
/*!40000 ALTER TABLE `detalle_pedidos` DISABLE KEYS */;
INSERT INTO `detalle_pedidos` VALUES (1,1,1,'Pan Integral',8.00,3,24.00,0,NULL,NULL,'2025-10-10 22:44:35','2025-10-10 22:44:35'),(2,2,4,'TantaWawas',15.00,1,15.00,1,24,'horas','2025-10-10 23:05:52','2025-10-10 23:05:52');
/*!40000 ALTER TABLE `detalle_pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imagen_producto`
--

DROP TABLE IF EXISTS `imagen_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imagen_producto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `url_imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto_alternativo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_imagen_principal` tinyint(1) NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `imagen_producto_producto_id_foreign` (`producto_id`),
  CONSTRAINT `imagen_producto_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imagen_producto`
--

LOCK TABLES `imagen_producto` WRITE;
/*!40000 ALTER TABLE `imagen_producto` DISABLE KEYS */;
INSERT INTO `imagen_producto` VALUES (1,3,'http://localhost/storage/productos/610163fe11862d4b9788a51d3402a43eebd52a5354993637649cec4e217e5f69.jpg',NULL,1,1,'2025-10-11 22:29:43','2025-10-11 22:29:43'),(5,1,'http://localhost/storage/productos/58aef57b01abd6b04e0a4993648fc97e67158fb80b9cf46ab8b9aa419e8bbb2f.jpg',NULL,1,1,'2025-10-12 21:05:54','2025-10-12 21:05:54'),(10,11,'http://localhost/storage/productos/34923d9abdb823aacdc814b84b2f5b43af8873551faa6c426d93b7d02f86e2d6.jpeg',NULL,1,1,'2025-10-13 12:53:44','2025-10-13 12:53:44');
/*!40000 ALTER TABLE `imagen_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredientes_receta`
--

DROP TABLE IF EXISTS `ingredientes_receta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredientes_receta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receta_id` bigint unsigned NOT NULL,
  `materia_prima_id` bigint unsigned NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `unidad` enum('kg','g','L','ml','unidades') COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_calculado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ingrediente_receta` (`receta_id`,`materia_prima_id`),
  KEY `ingredientes_receta_materia_prima_id_foreign` (`materia_prima_id`),
  KEY `ingredientes_receta_receta_id_orden_index` (`receta_id`,`orden`),
  CONSTRAINT `ingredientes_receta_materia_prima_id_foreign` FOREIGN KEY (`materia_prima_id`) REFERENCES `materias_primas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ingredientes_receta_receta_id_foreign` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredientes_receta`
--

LOCK TABLES `ingredientes_receta` WRITE;
/*!40000 ALTER TABLE `ingredientes_receta` DISABLE KEYS */;
INSERT INTO `ingredientes_receta` VALUES (1,1,1,5.000,'kg',42.50,1,'2025-10-10 01:56:59','2025-10-10 01:56:59'),(2,1,5,0.200,'kg',8.00,2,'2025-10-10 01:56:59','2025-10-10 01:56:59'),(3,1,6,0.150,'kg',0.38,3,'2025-10-10 01:56:59','2025-10-10 01:56:59'),(4,1,2,0.300,'kg',1.80,4,'2025-10-10 01:56:59','2025-10-10 01:56:59');
/*!40000 ALTER TABLE `ingredientes_receta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventario_productos_finales`
--

DROP TABLE IF EXISTS `inventario_productos_finales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_productos_finales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_elaboracion` date DEFAULT NULL,
  `dias_vida_util` int DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `costo_promedio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventario_productos_finales_producto_id_unique` (`producto_id`),
  KEY `inventario_productos_finales_stock_actual_index` (`stock_actual`),
  KEY `inventario_productos_finales_fecha_vencimiento_index` (`fecha_vencimiento`),
  CONSTRAINT `inventario_productos_finales_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario_productos_finales`
--

LOCK TABLES `inventario_productos_finales` WRITE;
/*!40000 ALTER TABLE `inventario_productos_finales` DISABLE KEYS */;
INSERT INTO `inventario_productos_finales` VALUES (1,1,1.00,0.00,NULL,NULL,NULL,0.00,'2025-10-10 22:42:34','2025-10-10 22:44:57'),(2,2,0.00,0.00,NULL,NULL,NULL,0.00,'2025-10-12 21:57:57','2025-10-12 21:57:57'),(3,3,1.00,0.00,NULL,NULL,NULL,0.00,'2025-10-12 21:57:57','2025-10-12 21:57:57'),(4,5,0.00,0.00,NULL,NULL,NULL,0.00,'2025-10-12 21:57:57','2025-10-12 21:57:57'),(5,6,1.00,0.00,NULL,NULL,NULL,0.00,'2025-10-12 21:57:57','2025-10-12 21:57:57'),(6,9,0.00,0.00,NULL,NULL,NULL,0.00,'2025-10-12 21:57:57','2025-10-12 21:57:57'),(7,10,0.00,0.00,NULL,NULL,NULL,0.00,'2025-10-12 21:57:57','2025-10-12 21:57:57'),(8,11,0.99,0.00,NULL,NULL,NULL,0.00,'2025-10-13 12:41:52','2025-10-13 12:41:52'),(9,4,0.00,0.00,NULL,NULL,NULL,15.00,'2025-10-13 12:53:18','2025-10-13 12:53:18'),(10,7,0.00,0.00,NULL,NULL,NULL,8.00,'2025-10-13 12:53:18','2025-10-13 12:53:18'),(11,8,0.00,0.00,NULL,NULL,NULL,45.00,'2025-10-13 12:53:18','2025-10-13 12:53:18');
/*!40000 ALTER TABLE `inventario_productos_finales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lotes_produccion`
--

DROP TABLE IF EXISTS `lotes_produccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotes_produccion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_lote` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produccion_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad_producida` int NOT NULL,
  `fecha_produccion` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_disponible` int NOT NULL,
  `estado` enum('activo','vencido','agotado','retirado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotes_produccion_codigo_lote_unique` (`codigo_lote`),
  KEY `lotes_produccion_produccion_id_foreign` (`produccion_id`),
  KEY `lotes_produccion_producto_id_fecha_produccion_index` (`producto_id`,`fecha_produccion`),
  KEY `lotes_produccion_estado_fecha_vencimiento_index` (`estado`,`fecha_vencimiento`),
  CONSTRAINT `lotes_produccion_produccion_id_foreign` FOREIGN KEY (`produccion_id`) REFERENCES `producciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lotes_produccion_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lotes_produccion`
--

LOCK TABLES `lotes_produccion` WRITE;
/*!40000 ALTER TABLE `lotes_produccion` DISABLE KEYS */;
/*!40000 ALTER TABLE `lotes_produccion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materias_primas`
--

DROP TABLE IF EXISTS `materias_primas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materias_primas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_interno` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_medida` enum('kg','g','L','ml','unidades') COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock_actual` decimal(10,3) NOT NULL DEFAULT '0.000',
  `stock_minimo` decimal(10,3) NOT NULL DEFAULT '0.000',
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `proveedor` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultima_compra` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materias_primas_codigo_interno_unique` (`codigo_interno`),
  KEY `materias_primas_nombre_index` (`nombre`),
  KEY `materias_primas_activo_index` (`activo`),
  KEY `materias_primas_activo_stock_actual_index` (`activo`,`stock_actual`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materias_primas`
--

LOCK TABLES `materias_primas` WRITE;
/*!40000 ALTER TABLE `materias_primas` DISABLE KEYS */;
INSERT INTO `materias_primas` VALUES (1,'Harina de trigo','MP001','kg',100.000,20.000,8.50,'Distribuidora La Victoria','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(2,'Azúcar refinada','MP002','kg',50.000,10.000,6.00,'Distribuidora La Victoria','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(3,'Huevos','MP003','unidades',200.000,50.000,0.80,'Granja Avícola','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(4,'Manteca vegetal','MP004','kg',25.000,5.000,15.00,'Distribuidora La Victoria','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(5,'Levadura instantánea','MP005','kg',5.000,1.000,40.00,'Distribuidora La Victoria','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(6,'Sal fina','MP006','kg',10.000,2.000,2.50,'Distribuidora La Victoria','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(7,'Leche líquida','MP007','L',30.000,10.000,7.00,'PIL Andina','2025-10-10',1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL);
/*!40000 ALTER TABLE `materias_primas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metodos_pago` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `icono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esta_activo` tinyint(1) NOT NULL DEFAULT '1',
  `comision_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `metodos_pago_codigo_unique` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodos_pago`
--

LOCK TABLES `metodos_pago` WRITE;
/*!40000 ALTER TABLE `metodos_pago` DISABLE KEYS */;
INSERT INTO `metodos_pago` VALUES (1,'Efectivo','efectivo',NULL,NULL,1,0.00,1,'2025-10-10 22:44:07','2025-10-10 22:44:07'),(3,'QR','qr',NULL,NULL,1,0.00,3,'2025-10-11 22:21:07','2025-10-11 22:21:07');
/*!40000 ALTER TABLE `metodos_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_10_06_004558_create_categories_table',1),(5,'2025_10_06_005232_create_products_table',1),(6,'2025_10_06_012406_create_imagen_producto_table',1),(7,'2025_10_06_021802_create_capacidad_produccion_table',1),(8,'2025_10_06_032207_create_personal_access_tokens_table',1),(9,'2025_10_06_034357_create_metodos_pago_table',1),(10,'2025_10_06_034403_create_pedidos_table',1),(11,'2025_10_06_034405_create_detalle_pedidos_table',1),(12,'2025_10_07_091856_add_unit_fields_to_productos_table',1),(13,'2025_10_07_094909_add_extras_to_productos_table',1),(14,'2025_10_07_104401_add_estado_fields_to_pedidos_table',1),(15,'2025_10_08_033136_create_clientes_table',1),(16,'2025_10_08_033157_add_cliente_id_to_pedidos_table',1),(17,'2025_10_08_040848_add_phone_and_is_active_to_users_table',1),(18,'2025_10_08_040852_create_roles_table',1),(19,'2025_10_08_040856_create_role_user_table',1),(20,'2025_10_08_045918_sync_existing_users_to_clientes_table',1),(21,'2025_10_09_041842_add_indexes_for_performance',1),(22,'2025_10_09_043252_add_delivery_fields_to_productos_table',1),(23,'2025_10_09_053529_add_tiene_extras_to_productos_table',1),(24,'2025_10_09_060101_add_notas_cancelacion_to_pedidos_table',1),(25,'2025_10_10_003625_create_inventario_sistema_completo',1),(26,'2025_10_10_213034_create_panaderos_table',2),(27,'2025_10_10_200000_add_stock_descargado_to_pedidos',3),(28,'2025_10_10_230000_create_vendedores_table',4),(29,'2025_10_10_231000_add_user_id_to_panaderos',4),(30,'2025_10_11_000001_add_compat_columns',5),(31,'2025_10_11_203303_create_configuracion_sistema_table',6),(32,'2025_10_11_223231_create_lotes_produccion_table',7),(33,'2025_10_11_223418_create_promociones_table',7),(34,'2025_10_11_223437_create_promociones_table',8),(35,'2025_10_11_223519_remove_duplicate_fields_from_empleados_tables',9),(36,'2025_10_12_000000_add_role_to_users_table',10),(37,'2025_10_11_235959_add_salario_por_kilo_to_panaderos_table',11),(38,'2025_10_12_000000_add_user_id_to_clientes_table',12),(39,'2025_10_12_000001_make_cliente_apellido_nullable',13),(40,'2025_10_12_000002_make_cliente_telefono_nullable',14),(41,'2025_10_12_000003_set_cliente_apellido_telefono_defaults',15),(42,'2025_10_12_150000_move_contact_info_to_users',16),(43,'2025_10_12_160000_add_perf_indexes',17),(44,'2025_10_12_170000_add_filter_indexes',18),(45,'2025_10_12_171500_nullable_contact_fields',19),(46,'2025_10_13_120000_drop_cantidad_from_productos',20);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_materia_prima`
--

DROP TABLE IF EXISTS `movimientos_materia_prima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_materia_prima` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `materia_prima_id` bigint unsigned NOT NULL,
  `tipo_movimiento` enum('entrada_compra','entrada_devolucion','entrada_ajuste','salida_produccion','salida_merma','salida_ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT NULL,
  `stock_anterior` decimal(10,3) NOT NULL,
  `stock_nuevo` decimal(10,3) NOT NULL,
  `produccion_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `numero_factura` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_materia_prima_produccion_id_foreign` (`produccion_id`),
  KEY `movimientos_materia_prima_user_id_foreign` (`user_id`),
  KEY `movimientos_materia_prima_materia_prima_id_index` (`materia_prima_id`),
  KEY `movimientos_materia_prima_tipo_movimiento_index` (`tipo_movimiento`),
  KEY `movimientos_materia_prima_created_at_index` (`created_at`),
  KEY `movimientos_materia_prima_materia_prima_id_created_at_index` (`materia_prima_id`,`created_at`),
  CONSTRAINT `movimientos_materia_prima_materia_prima_id_foreign` FOREIGN KEY (`materia_prima_id`) REFERENCES `materias_primas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_materia_prima_produccion_id_foreign` FOREIGN KEY (`produccion_id`) REFERENCES `producciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_materia_prima_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_materia_prima`
--

LOCK TABLES `movimientos_materia_prima` WRITE;
/*!40000 ALTER TABLE `movimientos_materia_prima` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimientos_materia_prima` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_productos_finales`
--

DROP TABLE IF EXISTS `movimientos_productos_finales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_productos_finales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `tipo_movimiento` enum('entrada_produccion','salida_venta','salida_merma','salida_degustacion','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `stock_anterior` decimal(10,3) NOT NULL,
  `stock_nuevo` decimal(10,3) NOT NULL,
  `produccion_id` bigint unsigned DEFAULT NULL,
  `pedido_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_productos_finales_produccion_id_foreign` (`produccion_id`),
  KEY `movimientos_productos_finales_pedido_id_foreign` (`pedido_id`),
  KEY `movimientos_productos_finales_user_id_foreign` (`user_id`),
  KEY `movimientos_productos_finales_producto_id_index` (`producto_id`),
  KEY `movimientos_productos_finales_tipo_movimiento_index` (`tipo_movimiento`),
  KEY `movimientos_productos_finales_created_at_index` (`created_at`),
  KEY `movimientos_productos_finales_producto_id_created_at_index` (`producto_id`,`created_at`),
  CONSTRAINT `movimientos_productos_finales_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_productos_finales_produccion_id_foreign` FOREIGN KEY (`produccion_id`) REFERENCES `producciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_productos_finales_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_productos_finales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_productos_finales`
--

LOCK TABLES `movimientos_productos_finales` WRITE;
/*!40000 ALTER TABLE `movimientos_productos_finales` DISABLE KEYS */;
INSERT INTO `movimientos_productos_finales` VALUES (1,1,'ajuste',20.000,0.000,20.000,NULL,NULL,1,'Motivo: Prueba inicial. Cargando stock inicial para pruebas','2025-10-10 22:42:34','2025-10-10 22:42:34'),(2,1,'salida_venta',3.000,20.000,17.000,NULL,1,1,'Salida por venta (Pedido PED-2025-0001)','2025-10-10 22:44:57','2025-10-10 22:44:57');
/*!40000 ALTER TABLE `movimientos_productos_finales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `panaderos`
--

DROP TABLE IF EXISTS `panaderos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `panaderos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `codigo_panadero` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `fecha_ingreso` date NOT NULL,
  `turno` enum('mañana','tarde','noche','rotativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mañana',
  `especialidad` enum('pan','reposteria','ambos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ambos',
  `salario_base` decimal(10,2) NOT NULL,
  `salario_por_kilo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_kilos_producidos` int NOT NULL DEFAULT '0',
  `total_unidades_producidas` int NOT NULL DEFAULT '0',
  `ultima_produccion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `panaderos_codigo_panadero_unique` (`codigo_panadero`),
  KEY `panaderos_activo_index` (`activo`),
  KEY `panaderos_turno_index` (`turno`),
  KEY `panaderos_especialidad_index` (`especialidad`),
  KEY `panaderos_codigo_panadero_index` (`codigo_panadero`),
  KEY `idx_panaderos_user` (`user_id`),
  KEY `idx_panaderos_turno` (`turno`),
  KEY `idx_panaderos_especialidad` (`especialidad`),
  CONSTRAINT `panaderos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `panaderos`
--

LOCK TABLES `panaderos` WRITE;
/*!40000 ALTER TABLE `panaderos` DISABLE KEYS */;
INSERT INTO `panaderos` VALUES (1,NULL,NULL,'Calle Los Hornos #123, La Paz','2024-01-15','mañana','ambos',3500.00,0.00,0,0,NULL,1,'Panadero con 10 años de experiencia','2025-10-10 21:35:03','2025-10-10 21:35:03',NULL),(2,5,'PAN-0002',NULL,'2025-10-12','mañana','ambos',3000.00,0.00,0,0,NULL,1,NULL,'2025-10-12 02:57:25','2025-10-12 02:57:25',NULL),(3,8,NULL,NULL,'2013-07-10','tarde','reposteria',63.00,0.00,0,0,NULL,1,NULL,'2025-10-12 04:39:13','2025-10-12 04:39:13',NULL),(4,15,NULL,NULL,'2025-10-12','mañana','pan',2000.00,0.00,0,0,NULL,1,NULL,'2025-10-12 19:15:52','2025-10-12 19:15:52',NULL);
/*!40000 ALTER TABLE `panaderos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned DEFAULT NULL,
  `numero_pedido` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `vendedor_id` bigint unsigned DEFAULT NULL,
  `cliente_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_apellido` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefono` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_entrega` enum('delivery','recoger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recoger',
  `direccion_entrega` text COLLATE utf8mb4_unicode_ci,
  `indicaciones_especiales` text COLLATE utf8mb4_unicode_ci,
  `notas_admin` text COLLATE utf8mb4_unicode_ci,
  `notas_cancelacion` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `descuento_bs` decimal(10,2) NOT NULL DEFAULT '0.00',
  `motivo_descuento` text COLLATE utf8mb4_unicode_ci,
  `metodos_pago_id` bigint unsigned NOT NULL,
  `codigo_promocional` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('pendiente','confirmado','en_preparacion','listo','entregado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `stock_descargado` tinyint(1) NOT NULL DEFAULT '0',
  `estado_pago` enum('pendiente','pagado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `qr_pago` text COLLATE utf8mb4_unicode_ci,
  `referencia_pago` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `hora_entrega` time DEFAULT NULL,
  `fecha_pago` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pedidos_numero_pedido_unique` (`numero_pedido`),
  KEY `pedidos_user_id_foreign` (`user_id`),
  KEY `pedidos_metodos_pago_id_foreign` (`metodos_pago_id`),
  KEY `pedidos_cliente_id_foreign` (`cliente_id`),
  KEY `pedidos_created_at_index` (`created_at`),
  KEY `pedidos_vendedor_id_foreign` (`vendedor_id`),
  KEY `idx_pedidos_estado` (`estado`),
  KEY `idx_pedidos_fecha_entrega` (`fecha_entrega`),
  CONSTRAINT `pedidos_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_metodos_pago_id_foreign` FOREIGN KEY (`metodos_pago_id`) REFERENCES `metodos_pago` (`id`),
  CONSTRAINT `pedidos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_vendedor_id_foreign` FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,4,'PED-2025-0001',NULL,NULL,'Test','User','testuser@example.com','999999999','recoger',NULL,NULL,NULL,'no habia',24.00,0.00,24.00,0.00,NULL,1,NULL,'cancelado',1,'pendiente',NULL,NULL,NULL,NULL,NULL,'2025-10-10 22:44:35','2025-10-11 22:03:41',NULL),(2,5,'PED-2025-0002',NULL,NULL,'andre','claros','antrak13@gmail.com','75587987','recoger',NULL,NULL,'hola mundo',NULL,15.00,0.00,15.00,0.00,NULL,1,NULL,'confirmado',0,'pendiente',NULL,NULL,'2025-10-11 00:00:00','18:03:00',NULL,'2025-10-10 23:05:52','2025-10-11 22:03:26',NULL);
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (3,'App\\Models\\User',3,'auth_token','01288b169f9ab60785ad006e049dbd1005c849e162db8065d17301e436294c3d','[\"*\"]',NULL,NULL,'2025-10-10 21:27:39','2025-10-10 21:27:39'),(42,'App\\Models\\User',1,'auth_token','8539a28fecb14af2f24d43b3879f55ac77f68c361b695d3f0e5548eab69ec9a1','[\"*\"]','2025-10-14 00:52:19',NULL,'2025-10-13 21:05:18','2025-10-14 00:52:19');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producciones`
--

DROP TABLE IF EXISTS `producciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `receta_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `fecha_produccion` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `cantidad_producida` decimal(10,3) NOT NULL,
  `unidad` enum('unidades','kg','docenas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unidades',
  `harina_real_usada` decimal(10,3) DEFAULT NULL,
  `harina_teorica` decimal(10,3) DEFAULT NULL,
  `diferencia_harina` decimal(10,3) DEFAULT NULL,
  `tipo_diferencia` enum('normal','merma','exceso') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `costo_produccion` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('en_proceso','completado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_proceso',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producciones_producto_id_foreign` (`producto_id`),
  KEY `producciones_receta_id_foreign` (`receta_id`),
  KEY `producciones_fecha_produccion_index` (`fecha_produccion`),
  KEY `producciones_user_id_fecha_produccion_index` (`user_id`,`fecha_produccion`),
  KEY `producciones_estado_index` (`estado`),
  CONSTRAINT `producciones_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `producciones_receta_id_foreign` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `producciones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producciones`
--

LOCK TABLES `producciones` WRITE;
/*!40000 ALTER TABLE `producciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `producciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `categorias_id` bigint unsigned NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `descripcion_corta` text COLLATE utf8mb4_unicode_ci,
  `unidad_medida` enum('unidad','cm','docena','paquete','gramos','kilogramos','arroba','porcion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unidad',
  `presentacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiene_variantes` tinyint(1) NOT NULL DEFAULT '0',
  `extras_disponibles` json DEFAULT NULL,
  `precio_minorista` decimal(10,2) NOT NULL,
  `precio_mayorista` decimal(10,2) DEFAULT NULL,
  `cantidad_minima_mayoreo` int NOT NULL DEFAULT '10',
  `es_de_temporada` tinyint(1) NOT NULL DEFAULT '0',
  `esta_activo` tinyint(1) NOT NULL DEFAULT '1',
  `permite_delivery` tinyint(1) NOT NULL DEFAULT '1',
  `permite_envio_nacional` tinyint(1) NOT NULL DEFAULT '0',
  `requiere_tiempo_anticipacion` tinyint(1) NOT NULL DEFAULT '0',
  `tiempo_anticipacion` int DEFAULT NULL,
  `unidad_tiempo` enum('horas','dias','semanas') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiene_limite_produccion` tinyint(1) NOT NULL DEFAULT '0',
  `limite_produccion` tinyint(1) NOT NULL DEFAULT '0',
  `tiene_extras` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productos_url_unique` (`url`),
  KEY `productos_esta_activo_index` (`esta_activo`),
  KEY `idx_productos_categoria` (`categorias_id`),
  KEY `idx_productos_esta_activo` (`esta_activo`),
  CONSTRAINT `productos_categorias_id_foreign` FOREIGN KEY (`categorias_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,'Pan Integral','pan-integral','Pan integral 100% natural, elaborado con harina integral','Pan saludable y nutritivo','unidad',NULL,0,'[]',8.00,6.50,10,0,1,1,1,0,NULL,'horas',0,0,0,'2025-10-10 01:56:59','2025-10-12 21:05:54',NULL),(2,1,'Pan Francés','pan-frances','Pan francés crujiente, tradicional de panadería','Pan clásico crujiente','unidad',NULL,0,NULL,5.00,4.00,20,0,1,1,0,0,NULL,NULL,0,0,0,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(3,2,'Empanadas de Queso','empanadas-queso','Empanadas rellenas de queso fresco','Deliciosas empanadas de queso','unidad',NULL,0,'[]',3.50,3.00,12,0,1,1,0,0,NULL,'horas',0,0,0,'2025-10-10 01:56:59','2025-10-11 22:29:43',NULL),(4,3,'TantaWawas','tantawawas','Pan especial de Todos Santos, elaborado con ingredientes tradicionales','Pan tradicional de Todos Santos','unidad',NULL,0,NULL,15.00,12.00,5,1,0,1,0,1,24,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-11 16:08:07',NULL),(5,3,'Masitas de Todos Santos','masitas-todos-santos','Deliciosas masitas dulces decoradas para la festividad','Masitas tradicionales','unidad',NULL,0,NULL,12.00,10.00,10,1,1,1,0,1,48,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-11 16:08:15',NULL),(6,3,'Pan de Pascua','pan-pascua','Pan especial navideño con frutas confitadas y nueces','Pan navideño tradicional','unidad',NULL,0,'[]',35.00,30.00,10,0,1,1,1,0,72,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-12 20:38:45',NULL),(7,3,'Buñuelos de Carnaval','bunuelos-carnaval','Buñuelos crujientes tradicionales de la época de carnaval','Buñuelos de carnaval','unidad',NULL,0,NULL,8.00,6.50,12,1,0,1,0,1,24,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(8,3,'Rosca de Reyes','rosca-reyes','Tradicional rosca de reyes con sorpresas, decorada con frutas confitadas','Rosca tradicional de reyes','unidad',NULL,0,NULL,45.00,40.00,2,1,0,1,0,1,96,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(9,3,'Humintas','humintas','Humintas dulces o saladas envueltas en hojas de choclo','Humintas tradicionales','unidad',NULL,0,NULL,6.00,5.00,15,1,1,1,0,1,24,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-11 16:08:12',NULL),(10,3,'Tawa Tawas Grande','tawa-tawas-grande','Versión grande del pan de Todos Santos, ideal para compartir','Tawa Tawa grande','unidad',NULL,0,NULL,25.00,20.00,3,1,1,1,0,1,48,'horas',0,1,0,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL),(11,1,'Figuras de Masa Individuales','figuras-de-masa-individuales','fsfsdfsdf','12sdfsdfsd','unidad','sdfsf',0,'[{\"nombre\": \"grfhgf\", \"precio\": 9.98}]',12.00,12.00,10,0,1,1,0,0,24,'horas',0,0,1,'2025-10-13 12:41:52','2025-10-13 12:53:44',NULL);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promociones`
--

DROP TABLE IF EXISTS `promociones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promociones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tipo_descuento` enum('porcentaje','monto_fijo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `monto_minimo_compra` decimal(10,2) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `usos_maximos` int DEFAULT NULL,
  `usos_por_cliente` int NOT NULL DEFAULT '1',
  `usos_actuales` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `productos_aplicables` json DEFAULT NULL,
  `categorias_aplicables` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promociones_codigo_unique` (`codigo`),
  KEY `promociones_codigo_activo_index` (`codigo`,`activo`),
  KEY `promociones_fecha_inicio_fecha_fin_index` (`fecha_inicio`,`fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promociones`
--

LOCK TABLES `promociones` WRITE;
/*!40000 ALTER TABLE `promociones` DISABLE KEYS */;
/*!40000 ALTER TABLE `promociones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recetas`
--

DROP TABLE IF EXISTS `recetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recetas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `nombre_receta` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `rendimiento` decimal(10,3) NOT NULL,
  `unidad_rendimiento` enum('unidades','kg','docenas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unidades',
  `costo_total_calculado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_unitario_calculado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `version` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recetas_producto_id_activa_index` (`producto_id`,`activa`),
  KEY `recetas_version_index` (`version`),
  CONSTRAINT `recetas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recetas`
--

LOCK TABLES `recetas` WRITE;
/*!40000 ALTER TABLE `recetas` DISABLE KEYS */;
INSERT INTO `recetas` VALUES (1,1,'Pan de Batalla v1.0','Receta estándar de pan. Rinde 100 unidades.',100.000,'unidades',52.68,0.53,1,1,'2025-10-10 01:56:59','2025-10-10 01:56:59',NULL);
/*!40000 ALTER TABLE `recetas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_user_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  KEY `idx_role_user_user_role` (`user_id`,`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (1,1,1,NULL,NULL),(2,2,2,NULL,NULL),(3,3,3,NULL,NULL),(4,4,3,NULL,NULL),(5,5,3,NULL,NULL),(6,5,4,NULL,NULL),(7,8,4,NULL,NULL),(8,14,2,NULL,NULL),(9,3,2,NULL,NULL),(10,6,2,NULL,NULL),(11,7,3,NULL,NULL),(12,9,3,NULL,NULL),(13,10,3,NULL,NULL),(14,12,3,NULL,NULL),(15,13,3,NULL,NULL);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Administrador total del sistema','2025-10-10 21:16:54','2025-10-10 21:16:54'),(2,'vendedor','Personal de tienda - puede ver y gestionar pedidos','2025-10-10 21:16:54','2025-10-10 21:16:54'),(3,'cliente','Usuario comprador - puede hacer pedidos','2025-10-10 21:16:54','2025-10-10 21:16:54'),(4,'panadero','Empleado de producción - maneja la elaboración de productos','2025-10-12 14:13:40','2025-10-12 14:13:40');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('tE3syMLJKgkHBkgL8xowWlqEMCH3557J1iL5n8Tp',NULL,'172.21.0.1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZWJFZVFSaG5GNWpPWjlzTTFpclE3Qm5LN2NNdFljaTJMamtnV3NSViI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHA6Ly9sb2NhbGhvc3QvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1760151864);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cliente',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ci` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_is_active_index` (`is_active`),
  KEY `users_email_verified_at_index` (`email_verified_at`),
  KEY `idx_users_ci` (`ci`),
  KEY `idx_users_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador','admin@panificadoranancy.com','admin','77777777',NULL,'2025-10-10 21:16:54','$2y$12$YIyZ1iKWe0H0x4WYOmZVD.JkaphL0dMRhsRWcKw1MoflYdWmSf7pi',1,NULL,'2025-10-10 21:16:54','2025-10-12 14:06:06'),(2,'Carlos Panificador','vendedor@panificadoranancy.com','vendedor','65551234',NULL,'2025-10-10 21:17:59','$2y$12$jgHvk5AZcZG3vFTVTjO7COtb38RvXL3Xa9D9L5iiach0v4tYuadGW',1,NULL,'2025-10-10 21:17:59','2025-10-12 14:06:06'),(3,'María García','maria@cliente.com','vendedor','65559876',NULL,'2025-10-10 21:18:00','$2y$12$FXLv2hkm2v5ZbAPT3HbPke9CLAuMEGNrPS.BnkU4ClJRqaINfrRqO',1,NULL,'2025-10-10 21:18:00','2025-10-12 14:33:20'),(4,'Juan Pérez','juan@cliente.com','cliente','65555555',NULL,'2025-10-10 21:18:00','$2y$12$y/mpbajqEj9RwZY.jjbblOwpmMuV7ezLro.2UPSMySJb1hL4lWyua',1,NULL,'2025-10-10 21:18:00','2025-10-10 23:26:44'),(5,'Ana López','ana@cliente.com','panadero','65554321','CI-5','2025-10-10 21:18:00','$2y$12$1t3vSclXunQWW3SyXFOrxOJ7ehQFwlsGu30Q3XXEdui1klc2TC9GG',1,NULL,'2025-10-10 21:18:00','2025-10-12 14:33:20'),(6,'Freddy VAlencia','fvalenciamedina97@gmail.com','vendedor',NULL,NULL,NULL,'$2y$12$oNzD8h7KTZFC2H9TKWb10uKnogOzqAoYYUvimo0cRH1PUPyYfhCMC',1,NULL,'2025-10-12 03:39:06','2025-10-12 04:43:30'),(7,'Optio perferendis n Id sit voluptas ra','zixow@mailinator.com','cliente',NULL,NULL,NULL,'$2y$12$GP1Z.hjiY7D7J1VBgTJoe.W5wDxen.CRbsQE4IiKPoiaewVVddlSu',1,NULL,'2025-10-12 03:40:31','2025-10-12 14:33:20'),(8,'Rerum sapiente optio A beatae officiis au','sevoxesu@mailinator.com','panadero','Est optio qui ut qu','Quasi modi molestiae',NULL,'$2y$12$jPfcQNPehDcULNvQ/rE8M.lYsZSJhL7ihYPNpCUgC5J2IKhfjtjI6',1,NULL,'2025-10-12 03:48:59','2025-10-12 04:39:13'),(9,'Et consequat Et et Quaerat beatae ex vo','wyjezomibo@mailinator.com','cliente',NULL,NULL,NULL,'$2y$12$d1mzHdn9GqcwiWt/3UPtjelC8wxZ2UDwxF5HQ7chd/j7vRo.xX.Pm',1,NULL,'2025-10-12 03:58:29','2025-10-12 14:33:20'),(10,'Panadero Test','panadero-test@mailinator.com','cliente',NULL,NULL,NULL,'$2y$12$QvbZLuOJR/WA2ZTpiEnn5OKsSsROe/oEwKwy4VgsVw.DVLORjF31u',1,NULL,'2025-10-12 04:02:22','2025-10-12 14:33:20'),(12,'Nulla ea totam labor Doloribus ullam volu','kutoby@mailinator.com','cliente',NULL,NULL,NULL,'$2y$12$6YXRmgCqF8WuP1ZxxTVqWu7ySeZiEZMia0oU5bGMhwe3/ALbZVEnS',1,NULL,'2025-10-12 04:18:45','2025-10-12 14:33:20'),(13,'Ad et sint enim natu Pariatur Dignissimo','qaxyfitu@mailinator.com','cliente',NULL,NULL,NULL,'$2y$12$q8lxj7VC6nZ90LT7cgpPsuELU3h1YKAKNcQG1XayarrlXTrkgHe.S',1,NULL,'2025-10-12 04:20:44','2025-10-12 14:33:20'),(14,'test sync','test-sync@mailinator.com','vendedor',NULL,NULL,NULL,'$2y$12$6pZvKUy6hbW8aQOIzIyTNuT0srhviaIvgjuo7nEnt9yVx7PUCvfIu',1,NULL,'2025-10-12 14:26:17','2025-10-12 14:26:18'),(15,'Test User','testpanadero+1760296551@example.com','cliente','12345678','CI-1760296551',NULL,'$2y$12$pFOniuIEpolBQXjLoTf.Lu/X5cMk2euCpElhYRjEIOvqzTsK2ABBC',1,NULL,'2025-10-12 19:15:52','2025-10-12 19:15:52'),(16,'TestV User','testvendedor+1760296699@example.com','cliente','87654321','CI-V1760296699',NULL,'$2y$12$p5.k4spfPR5.piUZ6UGmvu9bmAimPmH.Y5ZltsAjfipxhWU9hvYuW',1,NULL,'2025-10-12 19:18:20','2025-10-12 19:18:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendedores`
--

DROP TABLE IF EXISTS `vendedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendedores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `codigo_vendedor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comision_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `descuento_maximo_bs` decimal(10,2) NOT NULL DEFAULT '0.00',
  `puede_dar_descuentos` tinyint(1) NOT NULL DEFAULT '1',
  `puede_cancelar_ventas` tinyint(1) NOT NULL DEFAULT '0',
  `turno` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `estado` enum('activo','inactivo','suspendido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `ventas_realizadas` int NOT NULL DEFAULT '0',
  `total_vendido` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descuentos_otorgados` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendedores_codigo_vendedor_unique` (`codigo_vendedor`),
  KEY `vendedores_codigo_vendedor_index` (`codigo_vendedor`),
  KEY `vendedores_estado_index` (`estado`),
  KEY `vendedores_turno_index` (`turno`),
  KEY `idx_vendedores_user` (`user_id`),
  KEY `idx_vendedores_estado` (`estado`),
  KEY `idx_vendedores_turno` (`turno`),
  CONSTRAINT `vendedores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendedores`
--

LOCK TABLES `vendedores` WRITE;
/*!40000 ALTER TABLE `vendedores` DISABLE KEYS */;
INSERT INTO `vendedores` VALUES (1,2,'VEN-0001',2.00,50.00,1,0,'mañana','2025-10-11','activo',NULL,0,0.00,0.00,'2025-10-11 01:04:33','2025-10-12 14:49:23',NULL),(2,3,'VEN-0002',2.50,50.00,1,0,NULL,'2025-10-12','activo',NULL,0,0.00,0.00,'2025-10-12 01:07:01','2025-10-12 01:07:01',NULL),(3,6,'VEN-2025-0003',51.00,0.00,1,0,'mañana','1980-04-24','activo','Maiores odio id ex d',0,0.00,0.00,'2025-10-12 04:43:30','2025-10-12 04:43:30',NULL),(4,16,'VEN-2025-0004',5.00,0.00,1,0,'tarde','2025-10-12','activo',NULL,0,0.00,0.00,'2025-10-12 19:18:20','2025-10-12 19:18:20',NULL);
/*!40000 ALTER TABLE `vendedores` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-14  1:11:11
