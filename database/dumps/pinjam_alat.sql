/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: pinjam_alat
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `pinjam_alat`
--

/*!40000 DROP DATABASE IF EXISTS `pinjam_alat`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `pinjam_alat` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `pinjam_alat`;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_log_loan` (`loan_id`),
  KEY `idx_log_actor` (`actor_id`),
  KEY `idx_log_created` (`created_at`),
  CONSTRAINT `fk_log_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES
(1,1,3,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-02 06:55:43'),
(2,2,5,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-03 06:55:43'),
(3,2,1,'reviewing','Status diubah menjadi Sedang Ditinjau',NULL,NULL,'2026-11-05 04:55:43'),
(4,3,4,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-30 05:55:43'),
(5,3,1,'approved','Status diubah menjadi Disetujui',NULL,NULL,'2026-11-05 04:55:43'),
(6,4,5,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-03 06:55:43'),
(7,4,1,'ready_pickup','Status diubah menjadi Menunggu Pengambilan',NULL,NULL,'2026-11-05 04:55:43'),
(8,5,2,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-02 06:55:43'),
(9,5,1,'borrowed','Status diubah menjadi Dipinjam',NULL,NULL,'2026-11-05 04:55:43'),
(10,6,6,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-27 05:55:43'),
(11,6,1,'overdue','Status diubah menjadi Terlambat',NULL,NULL,'2026-11-05 04:55:43'),
(12,7,7,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-26 05:55:43'),
(13,7,1,'returned','Status diubah menjadi Dikembalikan',NULL,NULL,'2026-11-05 04:55:43'),
(14,8,2,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-30 05:55:43'),
(15,8,1,'rejected','Status diubah menjadi Ditolak',NULL,NULL,'2026-11-05 04:55:43'),
(16,9,3,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-30 05:55:43'),
(17,9,3,'cancelled','Pengajuan dibatalkan oleh siswa',NULL,NULL,'2026-11-04 10:55:43'),
(18,10,4,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-29 05:55:43'),
(19,10,1,'expired','Status diubah menjadi Kedaluwarsa',NULL,NULL,'2026-11-05 04:55:43'),
(20,2,1,'approved','Pengajuan disetujui','{\"status_from\":\"reviewing\",\"status_to\":\"approved\",\"pickup_date\":\"2026-11-09\"}','127.0.0.1','2026-11-08 19:22:31'),
(21,2,1,'ready_pickup','Alat disiapkan, menunggu pengambilan','{\"status_from\":\"approved\",\"status_to\":\"ready_pickup\"}','127.0.0.1','2026-11-08 19:22:39'),
(22,2,1,'handover','Serah terima alat ke siswa','{\"status_from\":\"ready_pickup\",\"status_to\":\"borrowed\",\"unit_codes\":[\"LAB-007-02\"]}','127.0.0.1','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `code_prefix` varchar(10) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Elektronik','elektronik','ELK',1,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(2,'Laboratorium','laboratorium','LAB',2,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(3,'Audio Visual','audio-visual','AVS',3,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(4,'Olahraga','olahraga','OLR',4,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(5,'Praktikum','praktikum','PRK',5,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(6,'Multimedia','multimedia','MLT',6,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(7,'Robotika','robotika','ROB',7,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(8,'Perlengkapan Kegiatan','perlengkapan-kegiatan','KGT',8,'2026-11-05 06:55:42','2026-11-05 06:55:42');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `damage_reports`
--

DROP TABLE IF EXISTS `damage_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `damage_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `item_unit_id` bigint(20) unsigned NOT NULL,
  `type` enum('rusak','hilang') NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_repair','resolved','written_off') NOT NULL DEFAULT 'open',
  `reported_by` bigint(20) unsigned NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_dr_loan` (`loan_id`),
  KEY `fk_dr_unit` (`item_unit_id`),
  KEY `fk_dr_user` (`reported_by`),
  KEY `idx_dr_status` (`status`),
  CONSTRAINT `fk_dr_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `fk_dr_unit` FOREIGN KEY (`item_unit_id`) REFERENCES `item_units` (`id`),
  CONSTRAINT `fk_dr_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `damage_reports`
--

LOCK TABLES `damage_reports` WRITE;
/*!40000 ALTER TABLE `damage_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `damage_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holidays_date_unique` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES
(1,'2026-12-24','Cuti Bersama Natal','2026-11-11 11:05:51','2026-11-11 11:05:51'),
(2,'2026-12-25','Hari Raya Natal','2026-11-11 11:05:51','2026-11-11 11:05:51');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inspection_media`
--

DROP TABLE IF EXISTS `inspection_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `media_type` enum('photo','video') NOT NULL,
  `path` varchar(255) NOT NULL,
  `caption` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_media_insp` (`inspection_id`),
  CONSTRAINT `fk_media_insp` FOREIGN KEY (`inspection_id`) REFERENCES `loan_inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inspection_media`
--

LOCK TABLES `inspection_media` WRITE;
/*!40000 ALTER TABLE `inspection_media` DISABLE KEYS */;
INSERT INTO `inspection_media` VALUES
(1,1,'photo','inspections/5/handover/demo-1.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(2,1,'photo','inspections/5/handover/demo-2.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(3,2,'photo','inspections/6/handover/demo-1.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(4,2,'photo','inspections/6/handover/demo-2.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,3,'photo','inspections/7/handover/demo-1.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(6,3,'photo','inspections/7/handover/demo-2.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(7,4,'photo','inspections/7/return/demo-1.jpg','Kondisi saat dikembalikan','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(8,5,'photo','inspections/2/handover/WDF26OlVGlZIA87AxInFQzoS6FzQoOkubumE1TKs.jpg','Kondisi awal','2026-11-08 19:41:24','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `inspection_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_photos`
--

DROP TABLE IF EXISTS `item_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_photos_item` (`item_id`),
  CONSTRAINT `fk_photos_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_photos`
--

LOCK TABLES `item_photos` WRITE;
/*!40000 ALTER TABLE `item_photos` DISABLE KEYS */;
INSERT INTO `item_photos` VALUES
(1,1,'items/1/demo-1.jpg',1,1,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(2,1,'items/1/demo-2.jpg',2,0,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(3,1,'items/1/demo-3.jpg',3,0,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(4,2,'items/2/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,2,'items/2/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(6,2,'items/2/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(7,3,'items/3/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(8,3,'items/3/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(9,3,'items/3/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(10,4,'items/4/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(11,4,'items/4/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(12,4,'items/4/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(13,5,'items/5/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(14,5,'items/5/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(15,5,'items/5/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(16,6,'items/6/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(17,6,'items/6/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(18,6,'items/6/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(19,7,'items/7/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(20,7,'items/7/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(21,7,'items/7/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43');
/*!40000 ALTER TABLE `item_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_units`
--

DROP TABLE IF EXISTS `item_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) unsigned NOT NULL,
  `unit_code` varchar(40) NOT NULL,
  `status` enum('available','maintenance','lost','inactive') NOT NULL DEFAULT 'available',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_units_unit_code_unique` (`unit_code`),
  KEY `idx_units_item_status` (`item_id`,`status`),
  CONSTRAINT `fk_units_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_units`
--

LOCK TABLES `item_units` WRITE;
/*!40000 ALTER TABLE `item_units` DISABLE KEYS */;
INSERT INTO `item_units` VALUES
(1,1,'ELK-012-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(2,1,'ELK-012-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(3,1,'ELK-012-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(4,1,'ELK-012-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(5,1,'ELK-012-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(6,1,'ELK-012-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(7,1,'ELK-012-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(8,1,'ELK-012-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(9,2,'AVS-004-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(10,2,'AVS-004-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(11,2,'AVS-004-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(12,2,'AVS-004-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(13,2,'AVS-004-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(14,2,'AVS-004-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(15,2,'AVS-004-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(16,2,'AVS-004-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(17,3,'LAB-007-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(18,3,'LAB-007-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(19,3,'LAB-007-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(20,3,'LAB-007-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(21,3,'LAB-007-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(22,3,'LAB-007-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(23,4,'AVS-009-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(24,4,'AVS-009-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(25,4,'AVS-009-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(26,4,'AVS-009-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(27,4,'AVS-009-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(28,4,'AVS-009-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(29,4,'AVS-009-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(30,4,'AVS-009-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(31,4,'AVS-009-09','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(32,4,'AVS-009-10','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(33,5,'AVS-015-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(34,5,'AVS-015-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(35,5,'AVS-015-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(36,5,'AVS-015-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(37,5,'AVS-015-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(38,6,'PRK-003-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(39,6,'PRK-003-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(40,6,'PRK-003-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(41,6,'PRK-003-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(42,6,'PRK-003-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(43,6,'PRK-003-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(44,6,'PRK-003-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(45,6,'PRK-003-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(46,7,'OLR-001-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(47,7,'OLR-001-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(48,7,'OLR-001-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(49,7,'OLR-001-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(50,7,'OLR-001-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(51,7,'OLR-001-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(52,7,'OLR-001-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(53,7,'OLR-001-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(54,7,'OLR-001-09','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(55,7,'OLR-001-10','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(56,7,'OLR-001-11','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(57,7,'OLR-001-12','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL);
/*!40000 ALTER TABLE `item_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `package_contents` text DEFAULT NULL,
  `checklist_template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist_template`)),
  `location` varchar(150) DEFAULT NULL,
  `max_loan_days` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `max_qty_per_loan` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `min_stock` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `video_url` varchar(255) DEFAULT NULL,
  `video_title` varchar(150) DEFAULT NULL,
  `video_duration` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `items_code_unique` (`code`),
  KEY `fk_items_category` (`category_id`),
  KEY `idx_items_search` (`name`,`code`),
  KEY `idx_items_status` (`status`,`category_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES
(1,1,'ELK-012','Kamera DSLR Canon 80D','24,2 MP • Lensa 18-135 mm','Kamera DSLR untuk dokumentasi kegiatan, jurnalistik sekolah, dan praktik fotografi.','{\"Sensor\":\"APS-C 24,2 MP\",\"Lensa\":\"18-135 mm f\\/3.5-5.6\",\"Video\":\"Full HD 60 fps\",\"Penyimpanan\":\"SD Card\"}','Body, lensa 18-135 mm, baterai LP-E6N + charger, strap, tas kamera, SD card 32 GB.','[\"Body & label aset sesuai\",\"Lensa lengkap & bersih\",\"Baterai + charger\",\"Strap & tas kamera\",\"SD card terpasang\",\"Layar dan tombol berfungsi\"]','Ruang Sarpras, Gedung B',7,2,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(2,3,'AVS-004','Proyektor Epson EB-X500','3.600 lumen • XGA • HDMI/VGA','Proyektor untuk presentasi kelas, seminar, dan kegiatan ekstrakurikuler.','{\"Kecerahan\":\"3.600 lumen\",\"Resolusi\":\"XGA (1024\\u00d7768)\",\"Input\":\"HDMI, VGA, USB\"}','Unit proyektor, kabel power, kabel HDMI, kabel VGA, remote, tas.','[\"Unit & label aset sesuai\",\"Lensa bersih tanpa goresan\",\"Kabel power\",\"Kabel HDMI\\/VGA\",\"Remote + baterai\",\"Menyala & menampilkan gambar\"]','Ruang Sarpras, Gedung B',7,1,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(3,2,'LAB-007','Mikroskop Binokuler','Perbesaran 40x–1000x • Lampu LED','Mikroskop binokuler untuk praktikum biologi dan pengamatan preparat.','{\"Perbesaran\":\"40x\\u20131000x\",\"Pencahayaan\":\"LED\",\"Okuler\":\"WF10x\"}','Unit mikroskop, 4 lensa objektif, kabel power, penutup debu.','[\"Unit & label aset sesuai\",\"Lensa objektif lengkap\",\"Okuler bersih\",\"Lampu LED menyala\",\"Penutup debu\"]','Laboratorium Biologi',7,2,1,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(4,3,'AVS-009','Tripod Video 170 cm','Kepala fluid • Beban maks. 5 kg','Tripod video dengan kepala fluid untuk pengambilan gambar yang stabil.','{\"Tinggi maks.\":\"170 cm\",\"Beban maks.\":\"5 kg\",\"Kepala\":\"Fluid pan & tilt\"}','Tripod, kepala fluid, quick-release plate, tas.','[\"Kaki tripod mengunci\",\"Kepala fluid halus\",\"Quick-release plate\",\"Tas tripod\"]','Ruang Sarpras, Gedung B',7,3,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(5,3,'AVS-015','Speaker Portabel 60W','Bluetooth • Baterai 8 jam • 2 mic input','Speaker portabel untuk kegiatan upacara, lomba, dan acara kelas.','{\"Daya\":\"60 W\",\"Konektivitas\":\"Bluetooth, AUX\",\"Baterai\":\"\\u00b1 8 jam\"}','Speaker, charger, 1 mikrofon nirkabel, kabel AUX.','[\"Unit & label aset sesuai\",\"Charger\",\"Mikrofon + baterai\",\"Suara jernih tanpa pecah\"]','Ruang Sarpras, Gedung B',7,1,1,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(6,5,'PRK-003','Kit Robotika Arduino','Arduino Uno • Sensor & motor lengkap','Paket Arduino untuk praktikum elektronika dan proyek robotika.','{\"Board\":\"Arduino Uno R3\",\"Sensor\":\"Ultrasonik, IR, suhu\",\"Aktuator\":\"Servo & motor DC\"}','Arduino Uno, breadboard, kabel jumper, sensor ultrasonik, servo, motor DC + driver, box penyimpanan.','[\"Board Arduino\",\"Breadboard & kabel jumper\",\"Sensor ultrasonik\",\"Servo & motor DC\",\"Kabel USB\",\"Box lengkap\"]','Laboratorium Komputer',7,2,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(7,4,'OLR-001','Bola Basket Molten','Ukuran 7 • Indoor/outdoor','Bola basket resmi ukuran 7 untuk latihan dan pertandingan antar kelas.','{\"Ukuran\":\"7\",\"Material\":\"Komposit\",\"Penggunaan\":\"Indoor\\/outdoor\"}','Bola basket, jaring bola.','[\"Bola tidak kempes\",\"Permukaan tidak mengelupas\",\"Jaring bola\"]','Gudang Olahraga',7,3,3,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL);
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_queue` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES
(1,'default','{\"uuid\":\"d2cab372-049f-44da-8614-369f597d8d08\",\"displayName\":\"App\\\\Events\\\\LoanStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:28:\\\"App\\\\Events\\\\LoanStatusUpdated\\\":4:{s:4:\\\"loan\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\Loan\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"oldStatus\\\";s:9:\\\"reviewing\\\";s:9:\\\"newStatus\\\";s:8:\\\"approved\\\";s:7:\\\"message\\\";s:19:\\\"Pengajuan disetujui\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}',0,NULL,1794165751,1794165751),
(2,'default','{\"uuid\":\"e4cdeb16-7238-4bb5-9ec2-fa5c00a2cb5a\",\"displayName\":\"App\\\\Events\\\\LoanStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:28:\\\"App\\\\Events\\\\LoanStatusUpdated\\\":4:{s:4:\\\"loan\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\Loan\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"oldStatus\\\";s:8:\\\"approved\\\";s:9:\\\"newStatus\\\";s:12:\\\"ready_pickup\\\";s:7:\\\"message\\\";s:36:\\\"Alat disiapkan, menunggu pengambilan\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}',0,NULL,1794165759,1794165759),
(3,'default','{\"uuid\":\"45142522-7596-4ad2-8c50-d32d8640aafb\",\"displayName\":\"App\\\\Events\\\\LoanStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:28:\\\"App\\\\Events\\\\LoanStatusUpdated\\\":4:{s:4:\\\"loan\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\Loan\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"oldStatus\\\";s:12:\\\"ready_pickup\\\";s:9:\\\"newStatus\\\";s:8:\\\"borrowed\\\";s:7:\\\"message\\\";s:26:\\\"Serah terima alat ke siswa\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}',0,NULL,1794166884,1794166884);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_inspections`
--

DROP TABLE IF EXISTS `loan_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `type` enum('handover','return') NOT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`checklist`)),
  `final_condition` enum('baik','rusak','hilang') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `inspected_by` bigint(20) unsigned NOT NULL,
  `inspected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_type` (`loan_id`,`type`),
  KEY `fk_insp_user` (`inspected_by`),
  CONSTRAINT `fk_insp_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insp_user` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_inspections`
--

LOCK TABLES `loan_inspections` WRITE;
/*!40000 ALTER TABLE `loan_inspections` DISABLE KEYS */;
INSERT INTO `loan_inspections` VALUES
(1,5,'handover','[{\"label\":\"Body & label aset sesuai\",\"checked\":true},{\"label\":\"Lensa lengkap & bersih\",\"checked\":true},{\"label\":\"Baterai + charger\",\"checked\":true},{\"label\":\"Strap & tas kamera\",\"checked\":true},{\"label\":\"SD card terpasang\",\"checked\":true},{\"label\":\"Layar dan tombol berfungsi\",\"checked\":true}]','baik','Unit lengkap dan berfungsi baik.',1,'2026-11-03 06:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(2,6,'handover','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Lensa objektif lengkap\",\"checked\":true},{\"label\":\"Okuler bersih\",\"checked\":true},{\"label\":\"Lampu LED menyala\",\"checked\":true},{\"label\":\"Penutup debu\",\"checked\":true}]','baik','Unit lengkap dan berfungsi baik.',1,'2026-10-28 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(3,7,'handover','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Charger\",\"checked\":true},{\"label\":\"Mikrofon + baterai\",\"checked\":true},{\"label\":\"Suara jernih tanpa pecah\",\"checked\":true}]','baik','Unit lengkap dan berfungsi baik.',1,'2026-10-26 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(4,7,'return','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Charger\",\"checked\":true},{\"label\":\"Mikrofon + baterai\",\"checked\":true},{\"label\":\"Suara jernih tanpa pecah\",\"checked\":true}]','baik','Dikembalikan lengkap dan baik.',1,'2026-10-29 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,2,'handover','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Lensa objektif lengkap\",\"checked\":true},{\"label\":\"Okuler bersih\",\"checked\":true},{\"label\":\"Lampu LED menyala\",\"checked\":true},{\"label\":\"Penutup debu\",\"checked\":true}]','baik',NULL,1,'2026-11-08 19:41:24','2026-11-08 19:41:24','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `loan_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_units`
--

DROP TABLE IF EXISTS `loan_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `item_unit_id` bigint(20) unsigned NOT NULL,
  `condition_out` enum('baik','rusak') NOT NULL DEFAULT 'baik',
  `condition_in` enum('baik','rusak','hilang') DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_unit` (`loan_id`,`item_unit_id`),
  KEY `idx_lu_unit` (`item_unit_id`),
  CONSTRAINT `fk_lu_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lu_unit` FOREIGN KEY (`item_unit_id`) REFERENCES `item_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_units`
--

LOCK TABLES `loan_units` WRITE;
/*!40000 ALTER TABLE `loan_units` DISABLE KEYS */;
INSERT INTO `loan_units` VALUES
(1,5,1,'baik',NULL,NULL,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(2,5,2,'baik',NULL,NULL,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(3,6,17,'baik',NULL,NULL,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(4,7,33,'baik','baik','2026-10-29 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,2,18,'baik',NULL,NULL,'2026-11-08 19:41:24','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `loan_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_code` varchar(30) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `quantity` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `purpose` text NOT NULL,
  `student_card_path` varchar(255) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `agreement_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','reviewing','approved','ready_pickup','borrowed','returned','returned_damaged','returned_lost','rejected','cancelled','expired','overdue') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time_start` time DEFAULT NULL,
  `pickup_time_end` time DEFAULT NULL,
  `pickup_location` varchar(150) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `borrowed_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `late_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `due_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `pending_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `resubmitted_from_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `extension_days` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `fine_paid_at` timestamp NULL DEFAULT NULL,
  `extended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loans_request_code_unique` (`request_code`),
  KEY `fk_loans_reviewer` (`reviewed_by`),
  KEY `fk_loans_resubmit` (`resubmitted_from_id`),
  KEY `idx_loans_status` (`status`),
  KEY `idx_loans_user` (`user_id`,`status`),
  KEY `idx_loans_item_dates` (`item_id`,`start_date`,`end_date`),
  KEY `idx_loans_dates` (`start_date`,`end_date`),
  KEY `idx_loans_created` (`created_at`),
  CONSTRAINT `fk_loans_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `fk_loans_resubmit` FOREIGN KEY (`resubmitted_from_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `fk_loans_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_loans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_loans_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES
(1,'REQ-2026-1001',3,2,1,'2026-11-06','2026-11-09','Keperluan kegiatan sekolah: Proyektor Epson EB-X500 untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-02 06:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(2,'REQ-2026-1002',5,3,1,'2026-11-05','2026-11-09','Keperluan kegiatan sekolah: Mikroskop Binokuler untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'borrowed',1,'2026-11-08 19:22:31',NULL,NULL,'2026-11-09','09:00:00','11:00:00','Laboratorium Biologi','2026-11-08 19:22:31','2026-11-08 19:41:24',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-03 06:55:43','2026-11-08 19:41:24',NULL,0,0.00,0,NULL,NULL),
(3,'REQ-2026-1003',4,1,1,'2026-11-09','2026-11-10','Keperluan kegiatan sekolah: Kamera DSLR Canon 80D untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'approved',1,NULL,NULL,NULL,'2026-11-06','09:00:00','11:00:00','Ruang Sarpras, Gedung B','2026-11-05 03:55:43',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-30 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(4,'REQ-2026-1004',5,4,2,'2026-11-05','2026-11-09','Keperluan kegiatan sekolah: Tripod Video 170 cm untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'ready_pickup',1,NULL,NULL,NULL,'2026-11-05','08:00:00','10:00:00','Ruang Sarpras, Gedung B','2026-11-04 06:55:43',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-03 06:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(5,'REQ-2026-1005',2,1,2,'2026-11-02','2026-11-06','Keperluan kegiatan sekolah: Kamera DSLR Canon 80D untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'borrowed',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-01 05:55:43','2026-11-03 06:55:43',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-02 06:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(6,'REQ-2026-1006',6,3,1,'2026-10-27','2026-11-02','Keperluan kegiatan sekolah: Mikroskop Binokuler untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'overdue',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-26 05:55:43','2026-10-28 05:55:43',NULL,NULL,NULL,NULL,1,3,NULL,NULL,NULL,'2026-10-27 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(7,'REQ-2026-1007',7,5,1,'2026-10-26','2026-10-28','Keperluan kegiatan sekolah: Speaker Portabel 60W untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'returned',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-24 05:55:43','2026-10-26 05:55:43','2026-10-29 05:55:43',NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-26 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(8,'REQ-2026-1008',2,2,1,'2026-11-09','2026-11-11','Keperluan kegiatan sekolah: Proyektor Epson EB-X500 untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'rejected',1,'2026-11-04 06:55:43',NULL,'Proyektor dipakai untuk kegiatan sekolah pada tanggal tersebut. Silakan pilih tanggal lain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-30 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(9,'REQ-2026-1009',3,6,1,'2026-11-09','2026-11-10','Keperluan kegiatan sekolah: Kit Robotika Arduino untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'cancelled',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-04 10:55:43','Jadwal praktikum berubah.',NULL,0,0,NULL,NULL,NULL,'2026-10-30 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(10,'REQ-2026-1010',4,7,2,'2026-10-29','2026-10-30','Keperluan kegiatan sekolah: Bola Basket Molten untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'expired',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-29 05:55:43',NULL,NULL,NULL,NULL,'2026-10-31 05:55:43',0,0,NULL,NULL,NULL,'2026-10-29 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL);
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2026_01_01_000001_create_users_table',1),
(2,'2026_01_01_000002_create_categories_and_items_tables',1),
(3,'2026_01_01_000003_create_loans_tables',1),
(4,'2026_01_01_000004_create_inspections_damage_logs_tables',1),
(5,'2026_11_02_195201_create_personal_access_tokens_table',1),
(6,'2026_11_03_190014_add_extension_and_fine_columns_to_loans_table',1),
(7,'2026_11_03_191017_add_fine_amount_to_damage_reports_table',1),
(8,'2026_11_03_200114_add_fine_paid_to_loans_table',1),
(10,'2026_11_07_114743_create_cache_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_notifiable` (`notifiable_type`,`notifiable_id`),
  KEY `idx_notif_read` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
('27cd7f94-c5b2-41b8-8f9e-30942dcd13b2','App\\Notifications\\LoanNotification','App\\Models\\User',5,'{\"kind\":\"ready_pickup\",\"title\":\"Alat siap diambil\",\"message\":\"Alat untuk pengajuan REQ-2026-1002 sudah disiapkan. Ambil di Laboratorium Biologi dan bawa kartu pelajar asli.\",\"loan_id\":2}',NULL,'2026-11-08 19:22:39','2026-11-08 19:22:39'),
('727a2a7a-6368-4ace-91de-a318f577c71b','App\\Notifications\\LoanNotification','App\\Models\\User',5,'{\"kind\":\"handover\",\"title\":\"Serah terima berhasil\",\"message\":\"Alat Mikroskop Binokuler \\u00d71 sudah kamu terima. Kembalikan paling lambat 9 Nov 2026.\",\"loan_id\":2}',NULL,'2026-11-08 19:41:24','2026-11-08 19:41:24'),
('ec939dd5-0015-4605-828c-a772378a057b','App\\Notifications\\LoanNotification','App\\Models\\User',5,'{\"kind\":\"approved\",\"title\":\"Pengajuan disetujui\",\"message\":\"Pengajuan REQ-2026-1002 disetujui. Ambil alat Senin, 9 Nov 2026, 09:00\\u201311:00 WIB di Laboratorium Biologi. Bawa kartu pelajar asli.\",\"loan_id\":2}',NULL,'2026-11-08 19:22:31','2026-11-08 19:22:31');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
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
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_last` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('352PliRdMfzv0POYLZezvgyvaZylCxzy2Aq4hV2v',NULL,'127.0.0.1','curl/8.13.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoibVNXUEUzYW9lVUZaak5zUEVJYWVZTEhpV01MbndkVWxEc2ZVcEV5NyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1793809181),
('d1eGfzgMUKstl4tMkNwZNBYi6gF0Mrt0CkXsAnQ6',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoic0hGUzNDbGJuZmRySnJHcHlKSlRYcG1hSEFVUGI2MmptMWh4bEtUUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794152709),
('DRAYTH2A7HsC02hqwiRQREl1PDLuCHJXritdmiJK',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.2 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiS3VQVEdxR1F0a1VBZFpVaXZEVXhsTUFVVURnWjM3NUdNcDBHR2RVYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvbm90aWZpY2F0aW9ucy91bnJlYWQtY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1794166914),
('DrtvXPZK56w2OFCtQLbajSuDMAf4jbrlmu6HqHnh',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.0 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNzhZR3JyQkU5bzdXdGVkeHhkOHh5OVp6RmRDOUFrZndScG1IMUJGdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9rYXRhbG9nLzciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1793808887),
('gkkRY5hzX3dY7nn0LkE5XG8vgEqxghocUgLkWUxx',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOWZiVUh1bE5ybFdrOXc2MnR4MEtXZzlFMFJUVGxqRzJSbVVDWjN4WSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wZW5nYWp1YW4iO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1793809858),
('L5S6KhmPFVFPHW44Rx1QZoh4F9mzuzO9I4S9FCHi',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.2 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNERGUFlFVDhNMVdwWHN4UU9zVVlqa0NmQWZmcE8wa2tBMkprOFQ5QiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yaXdheWF0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9',1794130287),
('o4xcRNy6tkhnfb2mwyszWcN2XchpeExZKptrTU3m',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSTBBbVhPWHFFUHYwSVdka1Q3aHpyU2lZSllhT3lkUTNuNkFnWVliQiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794130222);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('academic_year','2026/2027','Tahun ajaran','2026-11-11 11:05:51'),
('late_warning_threshold','3','Jumlah keterlambatan sebelum siswa diberi peringatan','2026-11-11 11:05:51'),
('max_loan_days','5','Maksimal hari peminjaman default','2026-11-11 11:05:51'),
('pickup_grace_workdays','1','Toleransi hari kerja pengambilan sebelum expired','2026-11-11 11:05:51'),
('review_sla_workdays','1','Batas hari kerja peninjauan admin','2026-11-11 11:05:51'),
('semester_label','Semester ganjil 2026','Label semester','2026-11-11 11:05:51'),
('service_days','1,2,3,4,5','Hari layanan (1=Senin ... 7=Minggu)','2026-11-11 11:05:51'),
('service_hour_end','15:00','Jam tutup layanan','2026-11-11 11:05:51'),
('service_hour_start','07:00','Jam buka layanan','2026-11-11 11:05:51');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','admin') NOT NULL DEFAULT 'siswa',
  `nis` varchar(30) DEFAULT NULL,
  `employee_code` varchar(30) DEFAULT NULL,
  `kelas` varchar(30) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `late_count` int(10) unsigned NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nis_unique` (`nis`),
  UNIQUE KEY `users_employee_code_unique` (`employee_code`),
  KEY `idx_users_role` (`role`,`is_active`),
  KEY `idx_users_kelas` (`kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Ibu Maya Sari','maya.sari','maya.sari@sman-nusantara.sch.id',NULL,'$2y$12$aTbSIr4PhboZViTRqjprtekhR/WnWdiK7aRP2STE55u8AK43g.nP6','admin',NULL,'ADM-004',NULL,'081234567001',NULL,1,0,NULL,'2026-11-08 19:22:21','2026-11-05 06:55:40','2026-11-11 11:05:51',NULL),
(2,'Raka Pratama','raka.pratama','raka.pratama@siswa.sman-nusantara.sch.id',NULL,'$2y$12$6L7T8EpYUH/fVE.sJ0RA2.et1PZisgrWVI5gb32Vv05HqHB5bRFZi','siswa','20240182',NULL,'XII IPA 2','081234567101',NULL,1,0,NULL,'2026-11-08 09:30:57','2026-11-05 06:55:41','2026-11-11 11:05:51',NULL),
(3,'Nadia Putri','nadia.putri','nadia.putri@siswa.sman-nusantara.sch.id',NULL,'$2y$12$Kjs0uxO7gflGa41caRgOcOEfg3tqfWZRsC0jUF.IYfSKUXjoJTQJy','siswa','20240175',NULL,'XII IPA 1','081234567102',NULL,1,0,NULL,NULL,'2026-11-05 06:55:41','2026-11-11 11:05:52',NULL),
(4,'Bagas Aji','bagas.aji','bagas.aji@siswa.sman-nusantara.sch.id',NULL,'$2y$12$OxD1JvEmggDtRQK/OJI3o.hwVusr1M6PTC8gwf7NZbXibFy4hG1YC','siswa','20240203',NULL,'XI IPS 1','081234567103',NULL,1,0,NULL,NULL,'2026-11-05 06:55:41','2026-11-11 11:05:52',NULL),
(5,'Salsa Nur','salsa.nur','salsa.nur@siswa.sman-nusantara.sch.id',NULL,'$2y$12$T9gcKRapXN6lYLZlKixHIesb0CChDNyBBZfguYE61VtVU1hK2scyu','siswa','20240219',NULL,'XI IPA 3','081234567104',NULL,1,0,NULL,NULL,'2026-11-05 06:55:42','2026-11-11 11:05:52',NULL),
(6,'Rizky Akbar','rizky.akbar','rizky.akbar@siswa.sman-nusantara.sch.id',NULL,'$2y$12$fUPKXWmoAHdt41ATW6t0lunXPdOoE2rKT7M4kkQ2G4rKyRCnTH/ei','siswa','20240231',NULL,'X IPA 2','081234567105',NULL,1,1,NULL,NULL,'2026-11-05 06:55:42','2026-11-11 11:05:53',NULL),
(7,'Alya Rahma','alya.rahma','alya.rahma@siswa.sman-nusantara.sch.id',NULL,'$2y$12$xgEyEAaYWx5QZ/M7xYdfsuzkjCkmtCYh7VN6AW8zen4H8Qw.HF1w2','siswa','20240248',NULL,'X IPS 2','081234567106',NULL,1,0,NULL,NULL,'2026-11-05 06:55:42','2026-11-11 11:05:53',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'pinjam_alat'
--

--
-- Dumping routines for database 'pinjam_alat'
--

--
-- Current Database: `pinjam_alat`
--

/*!40000 DROP DATABASE IF EXISTS `pinjam_alat`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `pinjam_alat` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `pinjam_alat`;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_log_loan` (`loan_id`),
  KEY `idx_log_actor` (`actor_id`),
  KEY `idx_log_created` (`created_at`),
  CONSTRAINT `fk_log_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES
(1,1,3,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-02 06:55:43'),
(2,2,5,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-03 06:55:43'),
(3,2,1,'reviewing','Status diubah menjadi Sedang Ditinjau',NULL,NULL,'2026-11-05 04:55:43'),
(4,3,4,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-30 05:55:43'),
(5,3,1,'approved','Status diubah menjadi Disetujui',NULL,NULL,'2026-11-05 04:55:43'),
(6,4,5,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-03 06:55:43'),
(7,4,1,'ready_pickup','Status diubah menjadi Menunggu Pengambilan',NULL,NULL,'2026-11-05 04:55:43'),
(8,5,2,'submitted','Pengajuan dikirim',NULL,NULL,'2026-11-02 06:55:43'),
(9,5,1,'borrowed','Status diubah menjadi Dipinjam',NULL,NULL,'2026-11-05 04:55:43'),
(10,6,6,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-27 05:55:43'),
(11,6,1,'overdue','Status diubah menjadi Terlambat',NULL,NULL,'2026-11-05 04:55:43'),
(12,7,7,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-26 05:55:43'),
(13,7,1,'returned','Status diubah menjadi Dikembalikan',NULL,NULL,'2026-11-05 04:55:43'),
(14,8,2,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-30 05:55:43'),
(15,8,1,'rejected','Status diubah menjadi Ditolak',NULL,NULL,'2026-11-05 04:55:43'),
(16,9,3,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-30 05:55:43'),
(17,9,3,'cancelled','Pengajuan dibatalkan oleh siswa',NULL,NULL,'2026-11-04 10:55:43'),
(18,10,4,'submitted','Pengajuan dikirim',NULL,NULL,'2026-10-29 05:55:43'),
(19,10,1,'expired','Status diubah menjadi Kedaluwarsa',NULL,NULL,'2026-11-05 04:55:43'),
(20,2,1,'approved','Pengajuan disetujui','{\"status_from\":\"reviewing\",\"status_to\":\"approved\",\"pickup_date\":\"2026-11-09\"}','127.0.0.1','2026-11-08 19:22:31'),
(21,2,1,'ready_pickup','Alat disiapkan, menunggu pengambilan','{\"status_from\":\"approved\",\"status_to\":\"ready_pickup\"}','127.0.0.1','2026-11-08 19:22:39'),
(22,2,1,'handover','Serah terima alat ke siswa','{\"status_from\":\"ready_pickup\",\"status_to\":\"borrowed\",\"unit_codes\":[\"LAB-007-02\"]}','127.0.0.1','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `code_prefix` varchar(10) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Elektronik','elektronik','ELK',1,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(2,'Laboratorium','laboratorium','LAB',2,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(3,'Audio Visual','audio-visual','AVS',3,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(4,'Olahraga','olahraga','OLR',4,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(5,'Praktikum','praktikum','PRK',5,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(6,'Multimedia','multimedia','MLT',6,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(7,'Robotika','robotika','ROB',7,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(8,'Perlengkapan Kegiatan','perlengkapan-kegiatan','KGT',8,'2026-11-05 06:55:42','2026-11-05 06:55:42');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `damage_reports`
--

DROP TABLE IF EXISTS `damage_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `damage_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `item_unit_id` bigint(20) unsigned NOT NULL,
  `type` enum('rusak','hilang') NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_repair','resolved','written_off') NOT NULL DEFAULT 'open',
  `reported_by` bigint(20) unsigned NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_dr_loan` (`loan_id`),
  KEY `fk_dr_unit` (`item_unit_id`),
  KEY `fk_dr_user` (`reported_by`),
  KEY `idx_dr_status` (`status`),
  CONSTRAINT `fk_dr_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `fk_dr_unit` FOREIGN KEY (`item_unit_id`) REFERENCES `item_units` (`id`),
  CONSTRAINT `fk_dr_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `damage_reports`
--

LOCK TABLES `damage_reports` WRITE;
/*!40000 ALTER TABLE `damage_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `damage_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holidays_date_unique` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES
(1,'2026-12-24','Cuti Bersama Natal','2026-11-11 11:05:51','2026-11-11 11:05:51'),
(2,'2026-12-25','Hari Raya Natal','2026-11-11 11:05:51','2026-11-11 11:05:51');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inspection_media`
--

DROP TABLE IF EXISTS `inspection_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `media_type` enum('photo','video') NOT NULL,
  `path` varchar(255) NOT NULL,
  `caption` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_media_insp` (`inspection_id`),
  CONSTRAINT `fk_media_insp` FOREIGN KEY (`inspection_id`) REFERENCES `loan_inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inspection_media`
--

LOCK TABLES `inspection_media` WRITE;
/*!40000 ALTER TABLE `inspection_media` DISABLE KEYS */;
INSERT INTO `inspection_media` VALUES
(1,1,'photo','inspections/5/handover/demo-1.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(2,1,'photo','inspections/5/handover/demo-2.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(3,2,'photo','inspections/6/handover/demo-1.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(4,2,'photo','inspections/6/handover/demo-2.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,3,'photo','inspections/7/handover/demo-1.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(6,3,'photo','inspections/7/handover/demo-2.jpg','Kondisi awal','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(7,4,'photo','inspections/7/return/demo-1.jpg','Kondisi saat dikembalikan','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(8,5,'photo','inspections/2/handover/WDF26OlVGlZIA87AxInFQzoS6FzQoOkubumE1TKs.jpg','Kondisi awal','2026-11-08 19:41:24','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `inspection_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_photos`
--

DROP TABLE IF EXISTS `item_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_photos_item` (`item_id`),
  CONSTRAINT `fk_photos_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_photos`
--

LOCK TABLES `item_photos` WRITE;
/*!40000 ALTER TABLE `item_photos` DISABLE KEYS */;
INSERT INTO `item_photos` VALUES
(1,1,'items/1/demo-1.jpg',1,1,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(2,1,'items/1/demo-2.jpg',2,0,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(3,1,'items/1/demo-3.jpg',3,0,'2026-11-05 06:55:42','2026-11-05 06:55:42'),
(4,2,'items/2/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,2,'items/2/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(6,2,'items/2/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(7,3,'items/3/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(8,3,'items/3/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(9,3,'items/3/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(10,4,'items/4/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(11,4,'items/4/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(12,4,'items/4/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(13,5,'items/5/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(14,5,'items/5/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(15,5,'items/5/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(16,6,'items/6/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(17,6,'items/6/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(18,6,'items/6/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(19,7,'items/7/demo-1.jpg',1,1,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(20,7,'items/7/demo-2.jpg',2,0,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(21,7,'items/7/demo-3.jpg',3,0,'2026-11-05 06:55:43','2026-11-05 06:55:43');
/*!40000 ALTER TABLE `item_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_units`
--

DROP TABLE IF EXISTS `item_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) unsigned NOT NULL,
  `unit_code` varchar(40) NOT NULL,
  `status` enum('available','maintenance','lost','inactive') NOT NULL DEFAULT 'available',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_units_unit_code_unique` (`unit_code`),
  KEY `idx_units_item_status` (`item_id`,`status`),
  CONSTRAINT `fk_units_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_units`
--

LOCK TABLES `item_units` WRITE;
/*!40000 ALTER TABLE `item_units` DISABLE KEYS */;
INSERT INTO `item_units` VALUES
(1,1,'ELK-012-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(2,1,'ELK-012-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(3,1,'ELK-012-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(4,1,'ELK-012-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(5,1,'ELK-012-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(6,1,'ELK-012-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(7,1,'ELK-012-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(8,1,'ELK-012-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(9,2,'AVS-004-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(10,2,'AVS-004-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(11,2,'AVS-004-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(12,2,'AVS-004-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(13,2,'AVS-004-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(14,2,'AVS-004-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(15,2,'AVS-004-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(16,2,'AVS-004-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(17,3,'LAB-007-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(18,3,'LAB-007-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(19,3,'LAB-007-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(20,3,'LAB-007-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(21,3,'LAB-007-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(22,3,'LAB-007-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(23,4,'AVS-009-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(24,4,'AVS-009-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(25,4,'AVS-009-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(26,4,'AVS-009-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(27,4,'AVS-009-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(28,4,'AVS-009-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(29,4,'AVS-009-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(30,4,'AVS-009-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(31,4,'AVS-009-09','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(32,4,'AVS-009-10','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(33,5,'AVS-015-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(34,5,'AVS-015-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(35,5,'AVS-015-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(36,5,'AVS-015-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(37,5,'AVS-015-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(38,6,'PRK-003-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(39,6,'PRK-003-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(40,6,'PRK-003-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(41,6,'PRK-003-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(42,6,'PRK-003-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(43,6,'PRK-003-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(44,6,'PRK-003-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(45,6,'PRK-003-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(46,7,'OLR-001-01','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(47,7,'OLR-001-02','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(48,7,'OLR-001-03','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(49,7,'OLR-001-04','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(50,7,'OLR-001-05','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(51,7,'OLR-001-06','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(52,7,'OLR-001-07','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(53,7,'OLR-001-08','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(54,7,'OLR-001-09','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(55,7,'OLR-001-10','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(56,7,'OLR-001-11','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(57,7,'OLR-001-12','available',NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL);
/*!40000 ALTER TABLE `item_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `package_contents` text DEFAULT NULL,
  `checklist_template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist_template`)),
  `location` varchar(150) DEFAULT NULL,
  `max_loan_days` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `max_qty_per_loan` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `min_stock` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `video_url` varchar(255) DEFAULT NULL,
  `video_title` varchar(150) DEFAULT NULL,
  `video_duration` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `items_code_unique` (`code`),
  KEY `fk_items_category` (`category_id`),
  KEY `idx_items_search` (`name`,`code`),
  KEY `idx_items_status` (`status`,`category_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES
(1,1,'ELK-012','Kamera DSLR Canon 80D','24,2 MP • Lensa 18-135 mm','Kamera DSLR untuk dokumentasi kegiatan, jurnalistik sekolah, dan praktik fotografi.','{\"Sensor\":\"APS-C 24,2 MP\",\"Lensa\":\"18-135 mm f\\/3.5-5.6\",\"Video\":\"Full HD 60 fps\",\"Penyimpanan\":\"SD Card\"}','Body, lensa 18-135 mm, baterai LP-E6N + charger, strap, tas kamera, SD card 32 GB.','[\"Body & label aset sesuai\",\"Lensa lengkap & bersih\",\"Baterai + charger\",\"Strap & tas kamera\",\"SD card terpasang\",\"Layar dan tombol berfungsi\"]','Ruang Sarpras, Gedung B',7,2,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(2,3,'AVS-004','Proyektor Epson EB-X500','3.600 lumen • XGA • HDMI/VGA','Proyektor untuk presentasi kelas, seminar, dan kegiatan ekstrakurikuler.','{\"Kecerahan\":\"3.600 lumen\",\"Resolusi\":\"XGA (1024\\u00d7768)\",\"Input\":\"HDMI, VGA, USB\"}','Unit proyektor, kabel power, kabel HDMI, kabel VGA, remote, tas.','[\"Unit & label aset sesuai\",\"Lensa bersih tanpa goresan\",\"Kabel power\",\"Kabel HDMI\\/VGA\",\"Remote + baterai\",\"Menyala & menampilkan gambar\"]','Ruang Sarpras, Gedung B',7,1,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(3,2,'LAB-007','Mikroskop Binokuler','Perbesaran 40x–1000x • Lampu LED','Mikroskop binokuler untuk praktikum biologi dan pengamatan preparat.','{\"Perbesaran\":\"40x\\u20131000x\",\"Pencahayaan\":\"LED\",\"Okuler\":\"WF10x\"}','Unit mikroskop, 4 lensa objektif, kabel power, penutup debu.','[\"Unit & label aset sesuai\",\"Lensa objektif lengkap\",\"Okuler bersih\",\"Lampu LED menyala\",\"Penutup debu\"]','Laboratorium Biologi',7,2,1,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(4,3,'AVS-009','Tripod Video 170 cm','Kepala fluid • Beban maks. 5 kg','Tripod video dengan kepala fluid untuk pengambilan gambar yang stabil.','{\"Tinggi maks.\":\"170 cm\",\"Beban maks.\":\"5 kg\",\"Kepala\":\"Fluid pan & tilt\"}','Tripod, kepala fluid, quick-release plate, tas.','[\"Kaki tripod mengunci\",\"Kepala fluid halus\",\"Quick-release plate\",\"Tas tripod\"]','Ruang Sarpras, Gedung B',7,3,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(5,3,'AVS-015','Speaker Portabel 60W','Bluetooth • Baterai 8 jam • 2 mic input','Speaker portabel untuk kegiatan upacara, lomba, dan acara kelas.','{\"Daya\":\"60 W\",\"Konektivitas\":\"Bluetooth, AUX\",\"Baterai\":\"\\u00b1 8 jam\"}','Speaker, charger, 1 mikrofon nirkabel, kabel AUX.','[\"Unit & label aset sesuai\",\"Charger\",\"Mikrofon + baterai\",\"Suara jernih tanpa pecah\"]','Ruang Sarpras, Gedung B',7,1,1,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(6,5,'PRK-003','Kit Robotika Arduino','Arduino Uno • Sensor & motor lengkap','Paket Arduino untuk praktikum elektronika dan proyek robotika.','{\"Board\":\"Arduino Uno R3\",\"Sensor\":\"Ultrasonik, IR, suhu\",\"Aktuator\":\"Servo & motor DC\"}','Arduino Uno, breadboard, kabel jumper, sensor ultrasonik, servo, motor DC + driver, box penyimpanan.','[\"Board Arduino\",\"Breadboard & kabel jumper\",\"Sensor ultrasonik\",\"Servo & motor DC\",\"Kabel USB\",\"Box lengkap\"]','Laboratorium Komputer',7,2,2,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL),
(7,4,'OLR-001','Bola Basket Molten','Ukuran 7 • Indoor/outdoor','Bola basket resmi ukuran 7 untuk latihan dan pertandingan antar kelas.','{\"Ukuran\":\"7\",\"Material\":\"Komposit\",\"Penggunaan\":\"Indoor\\/outdoor\"}','Bola basket, jaring bola.','[\"Bola tidak kempes\",\"Permukaan tidak mengelupas\",\"Jaring bola\"]','Gudang Olahraga',7,3,3,'active',NULL,NULL,NULL,'2026-11-05 06:55:42','2026-11-05 06:55:42',NULL);
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_queue` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES
(1,'default','{\"uuid\":\"d2cab372-049f-44da-8614-369f597d8d08\",\"displayName\":\"App\\\\Events\\\\LoanStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:28:\\\"App\\\\Events\\\\LoanStatusUpdated\\\":4:{s:4:\\\"loan\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\Loan\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"oldStatus\\\";s:9:\\\"reviewing\\\";s:9:\\\"newStatus\\\";s:8:\\\"approved\\\";s:7:\\\"message\\\";s:19:\\\"Pengajuan disetujui\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}',0,NULL,1794165751,1794165751),
(2,'default','{\"uuid\":\"e4cdeb16-7238-4bb5-9ec2-fa5c00a2cb5a\",\"displayName\":\"App\\\\Events\\\\LoanStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:28:\\\"App\\\\Events\\\\LoanStatusUpdated\\\":4:{s:4:\\\"loan\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\Loan\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"oldStatus\\\";s:8:\\\"approved\\\";s:9:\\\"newStatus\\\";s:12:\\\"ready_pickup\\\";s:7:\\\"message\\\";s:36:\\\"Alat disiapkan, menunggu pengambilan\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}',0,NULL,1794165759,1794165759),
(3,'default','{\"uuid\":\"45142522-7596-4ad2-8c50-d32d8640aafb\",\"displayName\":\"App\\\\Events\\\\LoanStatusUpdated\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:28:\\\"App\\\\Events\\\\LoanStatusUpdated\\\":4:{s:4:\\\"loan\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\Loan\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"oldStatus\\\";s:12:\\\"ready_pickup\\\";s:9:\\\"newStatus\\\";s:8:\\\"borrowed\\\";s:7:\\\"message\\\";s:26:\\\"Serah terima alat ke siswa\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}',0,NULL,1794166884,1794166884);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_inspections`
--

DROP TABLE IF EXISTS `loan_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `type` enum('handover','return') NOT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`checklist`)),
  `final_condition` enum('baik','rusak','hilang') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `inspected_by` bigint(20) unsigned NOT NULL,
  `inspected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_type` (`loan_id`,`type`),
  KEY `fk_insp_user` (`inspected_by`),
  CONSTRAINT `fk_insp_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insp_user` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_inspections`
--

LOCK TABLES `loan_inspections` WRITE;
/*!40000 ALTER TABLE `loan_inspections` DISABLE KEYS */;
INSERT INTO `loan_inspections` VALUES
(1,5,'handover','[{\"label\":\"Body & label aset sesuai\",\"checked\":true},{\"label\":\"Lensa lengkap & bersih\",\"checked\":true},{\"label\":\"Baterai + charger\",\"checked\":true},{\"label\":\"Strap & tas kamera\",\"checked\":true},{\"label\":\"SD card terpasang\",\"checked\":true},{\"label\":\"Layar dan tombol berfungsi\",\"checked\":true}]','baik','Unit lengkap dan berfungsi baik.',1,'2026-11-03 06:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(2,6,'handover','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Lensa objektif lengkap\",\"checked\":true},{\"label\":\"Okuler bersih\",\"checked\":true},{\"label\":\"Lampu LED menyala\",\"checked\":true},{\"label\":\"Penutup debu\",\"checked\":true}]','baik','Unit lengkap dan berfungsi baik.',1,'2026-10-28 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(3,7,'handover','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Charger\",\"checked\":true},{\"label\":\"Mikrofon + baterai\",\"checked\":true},{\"label\":\"Suara jernih tanpa pecah\",\"checked\":true}]','baik','Unit lengkap dan berfungsi baik.',1,'2026-10-26 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(4,7,'return','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Charger\",\"checked\":true},{\"label\":\"Mikrofon + baterai\",\"checked\":true},{\"label\":\"Suara jernih tanpa pecah\",\"checked\":true}]','baik','Dikembalikan lengkap dan baik.',1,'2026-10-29 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,2,'handover','[{\"label\":\"Unit & label aset sesuai\",\"checked\":true},{\"label\":\"Lensa objektif lengkap\",\"checked\":true},{\"label\":\"Okuler bersih\",\"checked\":true},{\"label\":\"Lampu LED menyala\",\"checked\":true},{\"label\":\"Penutup debu\",\"checked\":true}]','baik',NULL,1,'2026-11-08 19:41:24','2026-11-08 19:41:24','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `loan_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_units`
--

DROP TABLE IF EXISTS `loan_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `item_unit_id` bigint(20) unsigned NOT NULL,
  `condition_out` enum('baik','rusak') NOT NULL DEFAULT 'baik',
  `condition_in` enum('baik','rusak','hilang') DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_unit` (`loan_id`,`item_unit_id`),
  KEY `idx_lu_unit` (`item_unit_id`),
  CONSTRAINT `fk_lu_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lu_unit` FOREIGN KEY (`item_unit_id`) REFERENCES `item_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_units`
--

LOCK TABLES `loan_units` WRITE;
/*!40000 ALTER TABLE `loan_units` DISABLE KEYS */;
INSERT INTO `loan_units` VALUES
(1,5,1,'baik',NULL,NULL,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(2,5,2,'baik',NULL,NULL,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(3,6,17,'baik',NULL,NULL,'2026-11-05 06:55:43','2026-11-05 06:55:43'),
(4,7,33,'baik','baik','2026-10-29 05:55:43','2026-11-05 06:55:43','2026-11-05 06:55:43'),
(5,2,18,'baik',NULL,NULL,'2026-11-08 19:41:24','2026-11-08 19:41:24');
/*!40000 ALTER TABLE `loan_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_code` varchar(30) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `quantity` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `purpose` text NOT NULL,
  `student_card_path` varchar(255) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `agreement_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','reviewing','approved','ready_pickup','borrowed','returned','returned_damaged','returned_lost','rejected','cancelled','expired','overdue') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time_start` time DEFAULT NULL,
  `pickup_time_end` time DEFAULT NULL,
  `pickup_location` varchar(150) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `borrowed_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `late_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `due_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `pending_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `resubmitted_from_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `extension_days` int(11) NOT NULL DEFAULT 0,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `fine_paid_at` timestamp NULL DEFAULT NULL,
  `extended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loans_request_code_unique` (`request_code`),
  KEY `fk_loans_reviewer` (`reviewed_by`),
  KEY `fk_loans_resubmit` (`resubmitted_from_id`),
  KEY `idx_loans_status` (`status`),
  KEY `idx_loans_user` (`user_id`,`status`),
  KEY `idx_loans_item_dates` (`item_id`,`start_date`,`end_date`),
  KEY `idx_loans_dates` (`start_date`,`end_date`),
  KEY `idx_loans_created` (`created_at`),
  CONSTRAINT `fk_loans_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `fk_loans_resubmit` FOREIGN KEY (`resubmitted_from_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `fk_loans_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_loans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_loans_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES
(1,'REQ-2026-1001',3,2,1,'2026-11-06','2026-11-09','Keperluan kegiatan sekolah: Proyektor Epson EB-X500 untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-02 06:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(2,'REQ-2026-1002',5,3,1,'2026-11-05','2026-11-09','Keperluan kegiatan sekolah: Mikroskop Binokuler untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'borrowed',1,'2026-11-08 19:22:31',NULL,NULL,'2026-11-09','09:00:00','11:00:00','Laboratorium Biologi','2026-11-08 19:22:31','2026-11-08 19:41:24',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-03 06:55:43','2026-11-08 19:41:24',NULL,0,0.00,0,NULL,NULL),
(3,'REQ-2026-1003',4,1,1,'2026-11-09','2026-11-10','Keperluan kegiatan sekolah: Kamera DSLR Canon 80D untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'approved',1,NULL,NULL,NULL,'2026-11-06','09:00:00','11:00:00','Ruang Sarpras, Gedung B','2026-11-05 03:55:43',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-30 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(4,'REQ-2026-1004',5,4,2,'2026-11-05','2026-11-09','Keperluan kegiatan sekolah: Tripod Video 170 cm untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'ready_pickup',1,NULL,NULL,NULL,'2026-11-05','08:00:00','10:00:00','Ruang Sarpras, Gedung B','2026-11-04 06:55:43',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-03 06:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(5,'REQ-2026-1005',2,1,2,'2026-11-02','2026-11-06','Keperluan kegiatan sekolah: Kamera DSLR Canon 80D untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'borrowed',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-01 05:55:43','2026-11-03 06:55:43',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-11-02 06:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(6,'REQ-2026-1006',6,3,1,'2026-10-27','2026-11-02','Keperluan kegiatan sekolah: Mikroskop Binokuler untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'overdue',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-26 05:55:43','2026-10-28 05:55:43',NULL,NULL,NULL,NULL,1,3,NULL,NULL,NULL,'2026-10-27 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(7,'REQ-2026-1007',7,5,1,'2026-10-26','2026-10-28','Keperluan kegiatan sekolah: Speaker Portabel 60W untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'returned',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-24 05:55:43','2026-10-26 05:55:43','2026-10-29 05:55:43',NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-26 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(8,'REQ-2026-1008',2,2,1,'2026-11-09','2026-11-11','Keperluan kegiatan sekolah: Proyektor Epson EB-X500 untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'rejected',1,'2026-11-04 06:55:43',NULL,'Proyektor dipakai untuk kegiatan sekolah pada tanggal tersebut. Silakan pilih tanggal lain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-30 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(9,'REQ-2026-1009',3,6,1,'2026-11-09','2026-11-10','Keperluan kegiatan sekolah: Kit Robotika Arduino untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'cancelled',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-04 10:55:43','Jadwal praktikum berubah.',NULL,0,0,NULL,NULL,NULL,'2026-10-30 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL),
(10,'REQ-2026-1010',4,7,2,'2026-10-29','2026-10-30','Keperluan kegiatan sekolah: Bola Basket Molten untuk dokumentasi dan praktik kelas.','student-cards/demo-kartu.jpg',NULL,1,'expired',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-29 05:55:43',NULL,NULL,NULL,NULL,'2026-10-31 05:55:43',0,0,NULL,NULL,NULL,'2026-10-29 05:55:43','2026-11-05 06:55:43',NULL,0,0.00,0,NULL,NULL);
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2026_01_01_000001_create_users_table',1),
(2,'2026_01_01_000002_create_categories_and_items_tables',1),
(3,'2026_01_01_000003_create_loans_tables',1),
(4,'2026_01_01_000004_create_inspections_damage_logs_tables',1),
(5,'2026_11_02_195201_create_personal_access_tokens_table',1),
(6,'2026_11_03_190014_add_extension_and_fine_columns_to_loans_table',1),
(7,'2026_11_03_191017_add_fine_amount_to_damage_reports_table',1),
(8,'2026_11_03_200114_add_fine_paid_to_loans_table',1),
(10,'2026_11_07_114743_create_cache_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_notifiable` (`notifiable_type`,`notifiable_id`),
  KEY `idx_notif_read` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
('27cd7f94-c5b2-41b8-8f9e-30942dcd13b2','App\\Notifications\\LoanNotification','App\\Models\\User',5,'{\"kind\":\"ready_pickup\",\"title\":\"Alat siap diambil\",\"message\":\"Alat untuk pengajuan REQ-2026-1002 sudah disiapkan. Ambil di Laboratorium Biologi dan bawa kartu pelajar asli.\",\"loan_id\":2}',NULL,'2026-11-08 19:22:39','2026-11-08 19:22:39'),
('727a2a7a-6368-4ace-91de-a318f577c71b','App\\Notifications\\LoanNotification','App\\Models\\User',5,'{\"kind\":\"handover\",\"title\":\"Serah terima berhasil\",\"message\":\"Alat Mikroskop Binokuler \\u00d71 sudah kamu terima. Kembalikan paling lambat 9 Nov 2026.\",\"loan_id\":2}',NULL,'2026-11-08 19:41:24','2026-11-08 19:41:24'),
('ec939dd5-0015-4605-828c-a772378a057b','App\\Notifications\\LoanNotification','App\\Models\\User',5,'{\"kind\":\"approved\",\"title\":\"Pengajuan disetujui\",\"message\":\"Pengajuan REQ-2026-1002 disetujui. Ambil alat Senin, 9 Nov 2026, 09:00\\u201311:00 WIB di Laboratorium Biologi. Bawa kartu pelajar asli.\",\"loan_id\":2}',NULL,'2026-11-08 19:22:31','2026-11-08 19:22:31');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
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
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_last` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('352PliRdMfzv0POYLZezvgyvaZylCxzy2Aq4hV2v',NULL,'127.0.0.1','curl/8.13.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoibVNXUEUzYW9lVUZaak5zUEVJYWVZTEhpV01MbndkVWxEc2ZVcEV5NyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1793809181),
('d1eGfzgMUKstl4tMkNwZNBYi6gF0Mrt0CkXsAnQ6',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoic0hGUzNDbGJuZmRySnJHcHlKSlRYcG1hSEFVUGI2MmptMWh4bEtUUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794152709),
('DRAYTH2A7HsC02hqwiRQREl1PDLuCHJXritdmiJK',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.2 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiS3VQVEdxR1F0a1VBZFpVaXZEVXhsTUFVVURnWjM3NUdNcDBHR2RVYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvbm90aWZpY2F0aW9ucy91bnJlYWQtY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1794166914),
('DrtvXPZK56w2OFCtQLbajSuDMAf4jbrlmu6HqHnh',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.0 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNzhZR3JyQkU5bzdXdGVkeHhkOHh5OVp6RmRDOUFrZndScG1IMUJGdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9rYXRhbG9nLzciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1793808887),
('gkkRY5hzX3dY7nn0LkE5XG8vgEqxghocUgLkWUxx',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOWZiVUh1bE5ybFdrOXc2MnR4MEtXZzlFMFJUVGxqRzJSbVVDWjN4WSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wZW5nYWp1YW4iO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1793809858),
('L5S6KhmPFVFPHW44Rx1QZoh4F9mzuzO9I4S9FCHi',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.2 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNERGUFlFVDhNMVdwWHN4UU9zVVlqa0NmQWZmcE8wa2tBMkprOFQ5QiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yaXdheWF0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9',1794130287),
('o4xcRNy6tkhnfb2mwyszWcN2XchpeExZKptrTU3m',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSTBBbVhPWHFFUHYwSVdka1Q3aHpyU2lZSllhT3lkUTNuNkFnWVliQiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794130222);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('academic_year','2026/2027','Tahun ajaran','2026-11-11 11:05:51'),
('late_warning_threshold','3','Jumlah keterlambatan sebelum siswa diberi peringatan','2026-11-11 11:05:51'),
('max_loan_days','5','Maksimal hari peminjaman default','2026-11-11 11:05:51'),
('pickup_grace_workdays','1','Toleransi hari kerja pengambilan sebelum expired','2026-11-11 11:05:51'),
('review_sla_workdays','1','Batas hari kerja peninjauan admin','2026-11-11 11:05:51'),
('semester_label','Semester ganjil 2026','Label semester','2026-11-11 11:05:51'),
('service_days','1,2,3,4,5','Hari layanan (1=Senin ... 7=Minggu)','2026-11-11 11:05:51'),
('service_hour_end','15:00','Jam tutup layanan','2026-11-11 11:05:51'),
('service_hour_start','07:00','Jam buka layanan','2026-11-11 11:05:51');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','admin') NOT NULL DEFAULT 'siswa',
  `nis` varchar(30) DEFAULT NULL,
  `employee_code` varchar(30) DEFAULT NULL,
  `kelas` varchar(30) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `late_count` int(10) unsigned NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nis_unique` (`nis`),
  UNIQUE KEY `users_employee_code_unique` (`employee_code`),
  KEY `idx_users_role` (`role`,`is_active`),
  KEY `idx_users_kelas` (`kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Ibu Maya Sari','maya.sari','maya.sari@sman-nusantara.sch.id',NULL,'$2y$12$aTbSIr4PhboZViTRqjprtekhR/WnWdiK7aRP2STE55u8AK43g.nP6','admin',NULL,'ADM-004',NULL,'081234567001',NULL,1,0,NULL,'2026-11-08 19:22:21','2026-11-05 06:55:40','2026-11-11 11:05:51',NULL),
(2,'Raka Pratama','raka.pratama','raka.pratama@siswa.sman-nusantara.sch.id',NULL,'$2y$12$6L7T8EpYUH/fVE.sJ0RA2.et1PZisgrWVI5gb32Vv05HqHB5bRFZi','siswa','20240182',NULL,'XII IPA 2','081234567101',NULL,1,0,NULL,'2026-11-08 09:30:57','2026-11-05 06:55:41','2026-11-11 11:05:51',NULL),
(3,'Nadia Putri','nadia.putri','nadia.putri@siswa.sman-nusantara.sch.id',NULL,'$2y$12$Kjs0uxO7gflGa41caRgOcOEfg3tqfWZRsC0jUF.IYfSKUXjoJTQJy','siswa','20240175',NULL,'XII IPA 1','081234567102',NULL,1,0,NULL,NULL,'2026-11-05 06:55:41','2026-11-11 11:05:52',NULL),
(4,'Bagas Aji','bagas.aji','bagas.aji@siswa.sman-nusantara.sch.id',NULL,'$2y$12$OxD1JvEmggDtRQK/OJI3o.hwVusr1M6PTC8gwf7NZbXibFy4hG1YC','siswa','20240203',NULL,'XI IPS 1','081234567103',NULL,1,0,NULL,NULL,'2026-11-05 06:55:41','2026-11-11 11:05:52',NULL),
(5,'Salsa Nur','salsa.nur','salsa.nur@siswa.sman-nusantara.sch.id',NULL,'$2y$12$T9gcKRapXN6lYLZlKixHIesb0CChDNyBBZfguYE61VtVU1hK2scyu','siswa','20240219',NULL,'XI IPA 3','081234567104',NULL,1,0,NULL,NULL,'2026-11-05 06:55:42','2026-11-11 11:05:52',NULL),
(6,'Rizky Akbar','rizky.akbar','rizky.akbar@siswa.sman-nusantara.sch.id',NULL,'$2y$12$fUPKXWmoAHdt41ATW6t0lunXPdOoE2rKT7M4kkQ2G4rKyRCnTH/ei','siswa','20240231',NULL,'X IPA 2','081234567105',NULL,1,1,NULL,NULL,'2026-11-05 06:55:42','2026-11-11 11:05:53',NULL),
(7,'Alya Rahma','alya.rahma','alya.rahma@siswa.sman-nusantara.sch.id',NULL,'$2y$12$xgEyEAaYWx5QZ/M7xYdfsuzkjCkmtCYh7VN6AW8zen4H8Qw.HF1w2','siswa','20240248',NULL,'X IPS 2','081234567106',NULL,1,0,NULL,NULL,'2026-11-05 06:55:42','2026-11-11 11:05:53',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'pinjam_alat'
--

--
-- Dumping routines for database 'pinjam_alat'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-11 19:15:40
