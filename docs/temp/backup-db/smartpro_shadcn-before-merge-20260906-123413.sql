-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: smartpro_shadcn
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `access_profiles`
--

DROP TABLE IF EXISTS `access_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `jenis_dibolehkan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`jenis_dibolehkan`)),
  `boleh_review_jsa` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_profiles_nama_unique` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_profiles`
--

LOCK TABLES `access_profiles` WRITE;
/*!40000 ALTER TABLE `access_profiles` DISABLE KEYS */;
INSERT INTO `access_profiles` VALUES (1,'GL ALL Access','Bisa bikin SOP, SP, IK, FK, PX Dan JSA','[\"SOP\",\"IK\",\"SP\",\"JSA\",\"FK\",\"PX\"]',0,'2026-08-25 06:18:56','2026-08-25 06:35:19');
/*!40000 ALTER TABLE `access_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned NOT NULL,
  `kind` enum('pengesahan','nonaktif') NOT NULL DEFAULT 'pengesahan',
  `decision` enum('approved','rejected') DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approvals_document_id_foreign` (`document_id`),
  KEY `approvals_approver_id_index` (`approver_id`),
  KEY `approvals_kind_index` (`kind`),
  CONSTRAINT `approvals_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
INSERT INTO `approvals` VALUES (40,1244,15,'pengesahan','approved',NULL,'2026-09-06 02:54:05','2026-09-06 02:54:05','2026-09-06 02:54:05');
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachment_comments`
--

DROP TABLE IF EXISTS `attachment_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachment_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attachment_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachment_comments_attachment_id_foreign` (`attachment_id`),
  KEY `attachment_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `attachment_comments_attachment_id_foreign` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachment_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachment_comments`
--

LOCK TABLES `attachment_comments` WRITE;
/*!40000 ALTER TABLE `attachment_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `attachment_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `section_key` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `size` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachments_document_id_foreign` (`document_id`),
  CONSTRAINT `attachments_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `document_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_document_id_index` (`document_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1582,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 02:54:43'),(1583,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:15:39'),(1584,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:15:53'),(1585,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:16:13'),(1586,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:16:17'),(1587,5,1161,'document.arsip_upload','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":4}','10.7.110.79','2026-08-13 03:18:25'),(1588,5,1162,'document.arsip_upload','{\"doc_number\":\"PPA-ADRO-FK-ICTMD-10\",\"type\":\"FK\",\"department\":\"ICTMD\",\"edisi\":2,\"no_revisi\":3}','10.7.110.79','2026-08-13 03:18:59'),(1589,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:24:25'),(1590,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:24:58'),(1591,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:25:02'),(1592,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:25:05'),(1593,1,NULL,'user.create_staff','{\"created_user_id\":666,\"role\":\"staff\",\"department_id\":5}','10.7.110.79','2026-08-13 03:26:02'),(1594,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:26:11'),(1595,666,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:26:21'),(1596,666,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:28:36'),(1597,666,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:38:21'),(1598,666,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:38:25'),(1599,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:38:29'),(1600,5,1163,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-13 03:38:57'),(1601,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:39:21'),(1602,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 03:39:36'),(1603,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 03:39:39'),(1604,5,1163,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.79','2026-08-13 03:40:55'),(1605,5,1164,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-13 04:02:28'),(1606,5,1164,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.79','2026-08-13 04:02:57'),(1607,11,1164,'document.review_start',NULL,'10.7.110.79','2026-08-13 04:18:52'),(1608,11,1164,'document.review_reject','{\"annotations\":0,\"ai_adopted\":0}','10.7.110.79','2026-08-13 04:18:56'),(1609,11,1163,'document.review_start',NULL,'10.7.110.79','2026-08-13 04:19:01'),(1610,11,1163,'document.review_reject','{\"annotations\":0,\"ai_adopted\":0}','10.7.110.79','2026-08-13 04:19:04'),(1611,5,1163,'document.submit','{\"status\":\"waiting_for_review\",\"round\":1,\"no_revisi\":0}','10.7.110.79','2026-08-13 04:19:34'),(1612,5,1164,'document.submit','{\"status\":\"waiting_for_review\",\"round\":1,\"no_revisi\":0}','10.7.110.79','2026-08-13 04:19:42'),(1613,11,1164,'document.review_start',NULL,'10.7.110.79','2026-08-13 04:20:43'),(1614,11,1164,'document.review_reject','{\"annotations\":0,\"ai_adopted\":0}','10.7.110.79','2026-08-13 04:20:46'),(1615,11,1163,'document.review_start',NULL,'10.7.110.79','2026-08-13 04:20:48'),(1616,11,1163,'document.review_reject','{\"annotations\":0,\"ai_adopted\":0}','10.7.110.79','2026-08-13 04:20:51'),(1617,5,1163,'document.submit','{\"status\":\"waiting_for_review\",\"round\":2,\"no_revisi\":0}','10.7.110.79','2026-08-13 04:21:14'),(1618,5,1164,'document.submit','{\"status\":\"waiting_for_review\",\"round\":2,\"no_revisi\":0}','10.7.110.79','2026-08-13 04:21:24'),(1619,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 07:04:52'),(1620,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 07:05:17'),(1621,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 07:05:31'),(1622,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 07:05:34'),(1623,11,1164,'document.review_start',NULL,'10.7.110.79','2026-08-13 07:05:39'),(1624,11,1164,'document.review_reject','{\"annotations\":0,\"ai_adopted\":0}','10.7.110.79','2026-08-13 07:05:45'),(1625,11,1163,'document.review_start',NULL,'10.7.110.79','2026-08-13 07:05:48'),(1626,11,1163,'document.review_reject','{\"annotations\":0,\"ai_adopted\":0}','10.7.110.79','2026-08-13 07:05:51'),(1627,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-13 07:12:45'),(1628,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-13 07:12:51'),(1629,5,NULL,'user.login',NULL,'10.7.110.75','2026-08-18 06:22:13'),(1630,5,1165,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.75','2026-08-18 06:22:29'),(1631,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 03:38:55'),(1632,5,1166,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-19 03:39:06'),(1633,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 05:52:48'),(1634,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 05:54:07'),(1635,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 05:54:12'),(1636,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 05:54:52'),(1637,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 05:54:56'),(1638,5,1167,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-01\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-19 05:55:04'),(1639,12,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 05:56:09'),(1640,12,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 05:56:21'),(1641,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 05:56:25'),(1642,1,NULL,'user.create_staff','{\"created_user_id\":667,\"role\":\"group_leader\",\"department_id\":1}','10.7.110.79','2026-08-19 05:57:59'),(1643,5,1167,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.79','2026-08-19 05:58:33'),(1644,5,1168,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-02\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-19 05:58:40'),(1645,5,1168,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.79','2026-08-19 05:59:02'),(1646,5,1169,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-06\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-19 05:59:09'),(1647,5,1170,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-19 05:59:17'),(1648,5,1170,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.79','2026-08-19 05:59:36'),(1649,5,1171,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-04\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.7.110.79','2026-08-19 05:59:46'),(1650,5,1171,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.79','2026-08-19 06:00:25'),(1651,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:00:43'),(1652,7,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:00:47'),(1653,7,1171,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:01:07'),(1654,7,1171,'document.reassign_review','{\"dari\":\"MARCIO CALVIN ROHI\",\"ke\":\"GL_KEDUA\",\"alasan\":\"test\"}','10.7.110.79','2026-08-19 06:03:15'),(1655,1,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:03:26'),(1656,667,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:03:35'),(1657,667,1171,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:04:31'),(1658,7,1170,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:12:31'),(1659,7,1170,'document.reassign_review','{\"dari\":\"MARCIO CALVIN ROHI\",\"ke\":\"GL_KEDUA\",\"alasan\":\"males\"}','10.7.110.79','2026-08-19 06:12:38'),(1660,7,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:16:02'),(1661,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:16:06'),(1662,5,NULL,'informasi.create','{\"informasi_id\":5,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":null}','10.7.110.79','2026-08-19 06:27:12'),(1663,5,NULL,'informasi.perbarui','{\"informasi_id\":6,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":5}','10.7.110.79','2026-08-19 06:27:42'),(1664,5,NULL,'informasi.perbarui','{\"informasi_id\":7,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":6}','10.7.110.79','2026-08-19 06:27:56'),(1665,667,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:29:01'),(1666,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:29:11'),(1667,5,1172,'document.request_revision','{\"from_document_id\":1162,\"no_revisi\":3}','10.7.110.79','2026-08-19 06:46:34'),(1668,5,1163,'document.submit','{\"status\":\"waiting_for_review\",\"round\":3,\"no_revisi\":0}','10.7.110.79','2026-08-19 06:47:32'),(1669,5,1164,'document.submit','{\"status\":\"waiting_for_review\",\"round\":3,\"no_revisi\":0}','10.7.110.79','2026-08-19 06:47:48'),(1670,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:47:58'),(1671,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:48:09'),(1672,11,1164,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:48:13'),(1673,11,1164,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.7.110.79','2026-08-19 06:48:16'),(1674,11,1163,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:48:17'),(1675,11,1163,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.7.110.79','2026-08-19 06:48:21'),(1676,11,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:48:25'),(1677,2,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:48:33'),(1678,2,1163,'document.md_approve',NULL,'10.7.110.79','2026-08-19 06:53:04'),(1679,2,1164,'document.md_approve',NULL,'10.7.110.79','2026-08-19 06:53:09'),(1680,2,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:53:12'),(1681,15,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:53:22'),(1682,15,1164,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-02\"}','10.7.110.79','2026-08-19 06:53:34'),(1683,15,1163,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-03\"}','10.7.110.79','2026-08-19 06:53:39'),(1684,15,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:53:51'),(1685,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:53:54'),(1686,5,1173,'document.request_revision','{\"from_document_id\":1163,\"no_revisi\":0}','10.7.110.79','2026-08-19 06:55:01'),(1687,5,1174,'document.request_revision','{\"from_document_id\":1164,\"no_revisi\":0}','10.7.110.79','2026-08-19 06:55:09'),(1688,5,1174,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":1}','10.7.110.79','2026-08-19 06:55:45'),(1689,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:55:51'),(1690,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:56:02'),(1691,11,1174,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:56:08'),(1692,11,1174,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.7.110.79','2026-08-19 06:56:11'),(1693,11,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:56:14'),(1694,2,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:56:21'),(1695,2,1174,'document.md_approve',NULL,'10.7.110.79','2026-08-19 06:56:29'),(1696,2,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:56:32'),(1697,15,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:56:37'),(1698,15,1174,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-03\"}','10.7.110.79','2026-08-19 06:56:44'),(1699,15,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:56:49'),(1700,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:56:56'),(1701,5,1175,'document.request_revision','{\"from_document_id\":1174,\"no_revisi\":1}','10.7.110.79','2026-08-19 06:57:11'),(1702,5,1175,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":2}','10.7.110.79','2026-08-19 06:57:51'),(1703,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:57:58'),(1704,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 06:58:11'),(1705,11,1175,'document.review_start',NULL,'10.7.110.79','2026-08-19 06:58:18'),(1706,11,1175,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.7.110.79','2026-08-19 06:58:23'),(1707,11,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 06:58:26'),(1708,2,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:10:38'),(1709,2,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:10:47'),(1710,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:10:52'),(1711,11,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:11:00'),(1712,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:11:06'),(1713,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:11:19'),(1714,2,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:11:23'),(1715,2,1175,'document.md_approve',NULL,'10.7.110.79','2026-08-19 07:11:30'),(1716,2,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:11:32'),(1717,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:11:39'),(1718,11,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:11:56'),(1719,15,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:12:01'),(1720,15,1175,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-03\"}','10.7.110.79','2026-08-19 07:12:11'),(1721,15,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:12:13'),(1722,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:12:19'),(1723,5,1175,'document.nonaktif_ajukan','{\"oleh\":\"ANGGA MARGI SAPUTRO\",\"alasan\":\"mati\",\"tahapan\":[\"sh\",\"md\",\"pjo\"]}','10.7.110.79','2026-08-19 07:36:11'),(1724,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:36:14'),(1725,11,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:36:18'),(1726,11,1175,'document.nonaktif_setuju','{\"oleh\":\"ARISAL FARZAN\",\"tahap\":\"sh\",\"berikut\":\"md\"}','10.7.110.79','2026-08-19 07:36:29'),(1727,11,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:36:33'),(1728,2,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:36:42'),(1729,2,1175,'document.nonaktif_setuju','{\"oleh\":\"Management Development\",\"tahap\":\"md\",\"berikut\":\"pjo\"}','10.7.110.79','2026-08-19 07:36:49'),(1730,2,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:36:53'),(1731,15,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:36:59'),(1732,15,1175,'document.nonaktif_setuju','{\"oleh\":\"WAHYU BINUKO\",\"tahap\":\"pjo\",\"berikut\":null}','10.7.110.79','2026-08-19 07:37:11'),(1733,15,1175,'document.nonaktif_selesai','{\"nomor_dilepas\":\"PPA-ADRO-SOP-ICTMD-03\"}','10.7.110.79','2026-08-19 07:37:11'),(1734,15,NULL,'user.logout',NULL,'10.7.110.79','2026-08-19 07:37:25'),(1735,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-19 07:37:32'),(1736,5,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 01:05:59'),(1737,5,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 01:09:33'),(1738,11,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 01:09:39'),(1739,11,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 01:09:45'),(1740,2,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 01:09:51'),(1741,2,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 01:10:04'),(1742,15,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 01:10:12'),(1743,15,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 01:10:20'),(1744,5,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 01:10:24'),(1745,5,1173,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":1}','10.7.110.83','2026-08-20 01:11:14'),(1746,5,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 01:11:50'),(1747,1,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 01:12:05'),(1748,1,1162,'document.cancel_revision_b',NULL,'10.7.110.83','2026-08-20 01:12:21'),(1749,1,1163,'document.cancel_revision_b',NULL,'10.7.110.83','2026-08-20 01:12:24'),(1750,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-FK-ICTMD-10\",\"title\":\"Form kerja testing\",\"type\":\"FK\",\"department_id\":5,\"alasan\":\"MAI KU AJA\"}','10.7.110.83','2026-08-20 01:13:03'),(1751,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"Testing random\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"MAU KU AJA LGI\"}','10.7.110.83','2026-08-20 01:13:23'),(1752,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"MAU KU AJA LGI\"}','10.7.110.83','2026-08-20 01:13:43'),(1753,5,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 06:28:26'),(1754,5,1176,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.83','2026-08-20 06:28:39'),(1755,5,1176,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.83','2026-08-20 06:29:23'),(1756,5,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 06:29:26'),(1757,11,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 06:29:31'),(1758,11,NULL,'user.logout',NULL,'10.7.110.83','2026-08-20 06:29:44'),(1759,11,NULL,'user.login',NULL,'10.7.110.83','2026-08-20 06:29:48'),(1760,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-20 13:37:04'),(1761,1,NULL,'user.logout',NULL,'127.0.0.1','2026-08-20 13:37:16'),(1762,9,NULL,'user.login',NULL,'127.0.0.1','2026-08-20 13:37:38'),(1763,9,NULL,'user.logout',NULL,'127.0.0.1','2026-08-20 13:37:48'),(1764,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-20 13:38:07'),(1765,5,NULL,'user.login',NULL,'10.147.19.246','2026-08-21 01:42:01'),(1766,5,NULL,'user.login',NULL,'10.7.110.80','2026-08-21 07:01:44'),(1767,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-21 16:11:59'),(1768,5,1177,'document.arsip_upload','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-09o\",\"type\":\"SOP\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":3}','127.0.0.1','2026-08-21 16:14:49'),(1769,5,1178,'document.request_revision','{\"from_document_id\":1177,\"no_revisi\":3}','127.0.0.1','2026-08-21 16:15:56'),(1770,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-21 16:20:21'),(1771,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-21 16:20:24'),(1772,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 00:55:18'),(1773,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 00:59:22'),(1774,1,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 00:59:29'),(1775,1,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:07:49'),(1776,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 01:07:53'),(1777,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:09:19'),(1778,1,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 01:09:46'),(1779,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1780,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"2\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1781,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"title\":\"k\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1782,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"title\":\"eds\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1783,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-01\",\"title\":\"LIPSUM\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1784,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-02\",\"title\":\"18043829\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1785,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-06\",\"title\":\"18043829\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1786,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"title\":\"18043821\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1787,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-04\",\"title\":\"lipsume\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1788,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-FK-ICTMD-10\",\"title\":\"Form kerja testing\",\"type\":\"FK\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1789,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1790,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1791,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1792,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-09o\",\"title\":\"Testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1793,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-09o\",\"title\":\"Testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1794,1,NULL,'document.purge_all','{\"jumlah\":15,\"alasan\":\"preparation sosialisasi\"}','10.7.110.72','2026-08-22 01:10:34'),(1795,1,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:10:43'),(1796,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 01:10:48'),(1797,5,NULL,'informasi.perbarui','{\"informasi_id\":8,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":7}','10.7.110.72','2026-08-22 01:14:18'),(1798,5,NULL,'informasi.perbarui','{\"informasi_id\":9,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":8}','10.7.110.72','2026-08-22 01:14:44'),(1799,5,NULL,'informasi.perbarui','{\"informasi_id\":10,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":9}','10.7.110.72','2026-08-22 01:15:39'),(1800,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:33:52'),(1801,2,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 01:33:59'),(1802,2,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:35:12'),(1803,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 01:35:16'),(1804,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:35:32'),(1805,11,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 01:35:39'),(1806,11,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 01:38:19'),(1807,2,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 02:06:18'),(1808,2,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 02:35:33'),(1809,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 02:35:38'),(1810,5,1179,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 02:37:53'),(1811,5,NULL,'informasi.destroy','{\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"cakupan\":\"semua\",\"jumlah\":6}','10.7.110.72','2026-08-22 02:41:42'),(1812,5,NULL,'informasi.create','{\"informasi_id\":11,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":null}','10.7.110.72','2026-08-22 02:41:54'),(1813,5,NULL,'informasi.perbarui','{\"informasi_id\":12,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":11}','10.7.110.72','2026-08-22 02:42:05'),(1814,5,NULL,'informasi.perbarui','{\"informasi_id\":13,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":12}','10.7.110.72','2026-08-22 02:42:17'),(1815,5,NULL,'informasi.perbarui','{\"informasi_id\":14,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":13}','10.7.110.72','2026-08-22 02:42:28'),(1816,5,NULL,'informasi.perbarui','{\"informasi_id\":15,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":14}','10.7.110.72','2026-08-22 02:42:40'),(1817,5,NULL,'informasi.perbarui','{\"informasi_id\":16,\"kategori\":\"kebijakan\",\"nomor\":\"PPA-ADRO-KBJ-01\",\"judul\":\"testing\",\"menggantikan_id\":15}','10.7.110.72','2026-08-22 02:42:50'),(1818,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 03:38:40'),(1819,2,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 03:38:44'),(1820,2,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 03:39:04'),(1821,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 03:39:08'),(1822,5,NULL,'informasi.create','{\"informasi_id\":17,\"kategori\":\"memo_internal\",\"nomor\":\"PPA-ADRO-INTERNAL-01\",\"judul\":\"testing\",\"menggantikan_id\":null}','10.7.110.72','2026-08-22 03:58:27'),(1823,5,1179,'document.delete',NULL,'10.7.110.72','2026-08-22 04:34:57'),(1824,5,1180,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 04:35:14'),(1825,5,1180,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 04:39:32'),(1826,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 04:39:38'),(1827,11,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 04:39:44'),(1828,11,1180,'document.review_start',NULL,'10.7.110.72','2026-08-22 04:39:55'),(1829,11,1180,'document.ai_review','{\"findings\":0}','10.7.110.72','2026-08-22 04:40:02'),(1830,11,1180,'document.ai_review','{\"findings\":0}','10.7.110.72','2026-08-22 04:43:13'),(1831,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 04:49:36'),(1832,5,1181,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 04:49:54'),(1833,5,1181,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 04:53:29'),(1834,5,1182,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-01\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 04:54:46'),(1835,5,1182,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 05:05:23'),(1836,11,1180,'document.ai_review','{\"findings\":9}','10.7.110.72','2026-08-22 05:09:21'),(1837,5,1183,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 05:09:37'),(1838,11,1181,'document.review_start',NULL,'10.7.110.72','2026-08-22 05:10:06'),(1839,11,1181,'document.ai_review','{\"findings\":9}','10.7.110.72','2026-08-22 05:10:37'),(1840,11,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:12:08'),(1841,1,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:12:17'),(1842,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:12:46'),(1843,7,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:13:13'),(1844,7,1182,'document.review_start',NULL,'10.7.110.72','2026-08-22 05:13:24'),(1845,7,1182,'document.ai_review','{\"findings\":0}','10.7.110.72','2026-08-22 05:18:11'),(1846,7,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:19:54'),(1847,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:19:58'),(1848,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:20:08'),(1849,11,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:20:14'),(1850,11,1181,'document.ai_review','{\"findings\":10}','10.7.110.72','2026-08-22 05:20:48'),(1851,1,NULL,'user.create_staff','{\"created_user_id\":668,\"role\":\"group_leader\",\"department_id\":5}','10.7.110.72','2026-08-22 05:25:52'),(1852,11,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:29:54'),(1853,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:29:59'),(1854,5,1183,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 05:32:05'),(1855,5,1184,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 05:32:20'),(1856,5,1184,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 05:33:47'),(1857,1,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:33:55'),(1858,11,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:34:02'),(1859,11,1184,'document.review_start',NULL,'10.7.110.72','2026-08-22 05:34:13'),(1860,11,1184,'document.ai_review','{\"findings\":8}','10.7.110.72','2026-08-22 05:34:45'),(1861,11,1184,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.7.110.72','2026-08-22 05:35:22'),(1862,11,1183,'document.review_start',NULL,'10.7.110.72','2026-08-22 05:35:25'),(1863,11,1183,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.7.110.72','2026-08-22 05:35:29'),(1864,11,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:35:33'),(1865,2,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:35:44'),(1866,2,1183,'document.md_approve',NULL,'10.7.110.72','2026-08-22 05:35:53'),(1867,2,1184,'document.md_approve',NULL,'10.7.110.72','2026-08-22 05:36:00'),(1868,2,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:36:03'),(1869,15,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:36:11'),(1870,15,1184,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-01\"}','10.7.110.72','2026-08-22 05:36:24'),(1871,15,1183,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-02\"}','10.7.110.72','2026-08-22 05:36:29'),(1872,15,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:37:15'),(1873,11,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:37:24'),(1874,11,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:38:16'),(1875,15,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:38:22'),(1876,15,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:38:50'),(1877,1,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:38:57'),(1878,666,1183,'feedback.create','{\"feedback_number\":\"MSK-2026-0001\"}','10.7.110.68','2026-08-22 05:40:29'),(1879,666,1184,'feedback.create','{\"feedback_number\":\"MSK-2026-0002\"}','10.7.110.68','2026-08-22 05:40:59'),(1880,1,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:41:13'),(1881,666,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:41:22'),(1882,666,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 05:41:48'),(1883,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 05:41:57'),(1884,5,1185,'document.arsip_upload','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"type\":\"SOP\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":2}','10.7.110.72','2026-08-22 05:45:51'),(1885,5,1186,'document.request_revision','{\"from_document_id\":1185,\"no_revisi\":4}','10.7.110.72','2026-08-22 05:48:18'),(1886,5,1187,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-02\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 05:49:10'),(1887,5,1187,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 05:50:41'),(1888,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 06:00:12'),(1889,11,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 06:00:18'),(1890,11,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 06:00:29'),(1891,1,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 06:00:58'),(1892,1,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 06:01:15'),(1893,7,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 06:01:19'),(1894,7,1187,'document.review_start',NULL,'10.7.110.72','2026-08-22 06:01:28'),(1895,7,1187,'document.ai_review','{\"findings\":0}','10.7.110.72','2026-08-22 06:01:38'),(1896,7,1182,'document.ai_review','{\"findings\":11}','10.7.110.72','2026-08-22 06:03:48'),(1897,7,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 06:09:47'),(1898,5,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 06:09:54'),(1899,5,1188,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 06:13:37'),(1900,5,1188,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 06:14:21'),(1901,5,1189,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-06\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-22 06:22:00'),(1902,5,1189,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.110.72','2026-08-22 06:22:51'),(1903,5,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 06:23:46'),(1904,1,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 06:23:51'),(1905,1,NULL,'user.logout',NULL,'10.7.110.72','2026-08-22 06:24:35'),(1906,7,NULL,'user.login',NULL,'10.7.110.72','2026-08-22 06:24:47'),(1907,5,NULL,'user.login',NULL,'10.7.147.254','2026-08-22 06:29:44'),(1908,5,1190,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-07\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.147.254','2026-08-22 06:30:00'),(1909,5,1190,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.7.147.254','2026-08-22 06:30:35'),(1910,5,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:43:50'),(1911,5,1191,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-08\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.121.143.9','2026-08-22 06:46:59'),(1912,5,1191,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.121.143.9','2026-08-22 06:47:35'),(1913,5,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 06:48:03'),(1914,7,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:48:24'),(1915,7,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:48:52'),(1916,7,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 06:49:08'),(1917,7,1187,'document.review_approve',NULL,'10.121.143.9','2026-08-22 06:49:43'),(1918,11,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:49:50'),(1919,11,1187,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-JSA-ICTMD-01\"}','10.121.143.9','2026-08-22 06:50:05'),(1920,11,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 06:50:21'),(1921,5,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:51:07'),(1922,15,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:52:49'),(1923,5,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 06:55:12'),(1924,11,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:55:24'),(1925,11,1180,'document.ai_review','{\"findings\":9}','10.121.143.9','2026-08-22 06:56:40'),(1926,5,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 06:57:19'),(1927,5,1192,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-010\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.121.143.9','2026-08-22 07:03:58'),(1928,5,1192,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.121.143.9','2026-08-22 07:08:56'),(1929,5,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 07:09:32'),(1930,11,1192,'document.review_start',NULL,'10.121.143.9','2026-08-22 07:10:03'),(1931,11,1192,'document.ai_review','{\"findings\":9}','10.121.143.9','2026-08-22 07:10:53'),(1932,11,1192,'document.review_approve','{\"lanjut_ke\":\"md\"}','10.121.143.9','2026-08-22 07:13:05'),(1933,7,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 07:13:18'),(1934,5,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 07:13:59'),(1935,5,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 07:14:15'),(1936,2,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 07:14:28'),(1937,5,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 07:15:39'),(1938,5,1193,'document.create','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"type\":\"JSA\",\"department\":\"ICTMD\"}','10.121.143.9','2026-08-22 07:16:09'),(1939,5,1193,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','10.121.143.9','2026-08-22 07:19:45'),(1940,2,NULL,'user.logout',NULL,'10.121.143.9','2026-08-22 07:19:58'),(1941,7,NULL,'user.login',NULL,'10.121.143.9','2026-08-22 07:20:10'),(1942,7,1193,'document.review_start',NULL,'10.121.143.9','2026-08-22 07:20:33'),(1943,7,1193,'document.review_approve',NULL,'10.121.143.9','2026-08-22 07:21:53'),(1944,11,1193,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-JSA-ICTMD-02\"}','10.121.143.9','2026-08-22 07:22:39'),(1945,5,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 03:56:30'),(1969,5,NULL,'user.logout',NULL,'192.160.110.186','2026-08-25 05:41:05'),(1970,1,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 05:41:13'),(1971,1,NULL,'akses.profil_dibuat','{\"profil\":\"GL ALL Access\",\"jenis\":[\"SOP\",\"IK\",\"SP\",\"JSA\",\"FK\",\"PX\"],\"boleh_review_jsa\":false}','192.160.110.186','2026-08-25 06:18:56'),(1972,1,NULL,'akses.ditetapkan','{\"nrp\":\"18064116\",\"nama\":\"ANGGA MARGI SAPUTRO\",\"sebelum\":\"\\u2014 Tanpa Akses \\u2014\",\"sesudah\":\"GL ALL Access\"}','192.160.110.186','2026-08-25 06:19:01'),(1973,1,NULL,'user.logout',NULL,'192.160.110.186','2026-08-25 06:19:17'),(1974,5,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 06:19:22'),(1975,5,NULL,'user.logout',NULL,'192.160.110.186','2026-08-25 06:19:33'),(1976,7,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 06:19:45'),(1977,7,NULL,'user.logout',NULL,'192.160.110.186','2026-08-25 06:24:16'),(1978,5,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 06:24:21'),(1979,5,NULL,'user.logout',NULL,'192.160.110.186','2026-08-25 06:33:27'),(1980,5,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 06:33:47'),(1981,5,NULL,'user.logout',NULL,'192.160.110.186','2026-08-25 06:34:00'),(1982,1,NULL,'user.login',NULL,'192.160.110.186','2026-08-25 06:34:05'),(1983,1,NULL,'akses.profil_diubah','{\"profil\":\"GL ALL Access\",\"sebelum\":{\"nama\":\"GL ALL Access\",\"jenis_dibolehkan\":[\"SOP\",\"IK\",\"SP\",\"JSA\",\"FK\",\"PX\"],\"boleh_review_jsa\":false},\"sesudah\":{\"nama\":\"GL ALL Access\",\"jenis_dibolehkan\":[\"SOP\",\"IK\",\"SP\",\"JSA\",\"FK\",\"PX\"],\"boleh_review_jsa\":false},\"pengguna_terdampak\":1}','192.160.110.186','2026-08-25 06:35:19'),(1984,5,NULL,'user.login',NULL,'10.147.19.246','2026-08-25 16:39:27'),(1985,5,NULL,'user.logout',NULL,'10.147.19.246','2026-08-25 16:40:37'),(1986,1,NULL,'user.login',NULL,'10.147.19.246','2026-08-25 16:40:56'),(1987,1,NULL,'user.login',NULL,'10.147.19.246','2026-08-25 20:56:35'),(1988,5,NULL,'user.login',NULL,'10.7.110.79','2026-08-25 23:29:50'),(1989,5,NULL,'user.logout',NULL,'10.7.110.79','2026-08-25 23:30:00'),(1990,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-25 23:30:09'),(1991,1,NULL,'pengaturan.ai_diubah','{\"sebelum\":{\"aktif\":true,\"provider\":\"openrouter\",\"model\":\"nvidia\\/nemotron-3-ultra-550b-a55b:free\"},\"sesudah\":{\"aktif\":true,\"provider\":\"openrouter\",\"model\":\"nvidia\\/nemotron-3-ultra-550b-a55b:free\"},\"kunci\":\"tetap\",\"kunci_cadangan\":\"diganti\"}','10.7.110.79','2026-08-25 23:45:45'),(1992,1,NULL,'pengaturan.ai_diubah','{\"sebelum\":{\"aktif\":true,\"provider\":\"openrouter\",\"model\":\"nvidia\\/nemotron-3-ultra-550b-a55b:free\"},\"sesudah\":{\"aktif\":true,\"provider\":\"openrouter\",\"model\":\"nvidia\\/nemotron-3-ultra-550b-a55b:free\"},\"kunci\":\"tetap\",\"kunci_cadangan\":\"diganti\"}','10.7.110.79','2026-08-25 23:46:39'),(1993,1,NULL,'user.login',NULL,'10.7.110.79','2026-08-26 03:50:25'),(1994,1,NULL,'user.delete','{\"user_id\":667,\"nrp\":\"GLSHE-0001\"}','10.7.110.79','2026-08-26 03:53:01'),(1995,1,NULL,'pengaturan.ai_diuji','{\"hasil\":[{\"label\":\"Utama\",\"provider\":\"openrouter\",\"model\":\"nvidia\\/nemotron-3-ultra-550b-a55b:free\",\"berhasil\":true},{\"label\":\"Cadangan\",\"provider\":\"openrouter\",\"model\":\"chatgptnew\",\"berhasil\":false}]}','10.7.110.79','2026-08-26 04:01:32'),(1996,1,NULL,'pengaturan.cache_dibersihkan',NULL,'10.7.110.79','2026-08-26 04:02:03'),(1997,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:50'),(1998,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"PEMBERSIHAN DAN PEMELIHARAAN KOMPUTER (PC)\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(1999,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"PENANGANAN GANGGUAN JARINGAN INTERNET\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2000,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-01\",\"title\":\"Pergantian AP di View Point\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2001,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"Testing AI\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2002,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"Prosedur backup\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2003,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"Testing Existing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2004,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"Testing Existing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2005,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-01\",\"title\":\"Testing Asal JSA\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2006,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"title\":\"Testing mail\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2007,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-06\",\"title\":\"testing mailpit ke sh\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2008,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-07\",\"title\":\"testing lagi\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2009,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-08\",\"title\":\"wafe\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2010,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-010\",\"title\":\"Lorem Ipsum\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2011,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-02\",\"title\":\"PEMASANGAN ANTENA RADIO\",\"type\":\"JSA\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2012,NULL,NULL,'document.purge_all','{\"jumlah\":15,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-26 04:54:52'),(2013,NULL,NULL,'informasi.purge_all','{\"jumlah\":7,\"alasan\":\"Pengosongan data pengembangan sebelum rilis produksi (PLAN v9 fase akhir)\"}','127.0.0.1','2026-08-26 04:55:26'),(2014,1,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 00:11:28'),(2015,1,NULL,'user.logout',NULL,'10.7.110.86','2026-08-27 00:41:25'),(2016,5,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 00:41:32'),(2017,5,1217,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.86','2026-08-27 00:41:53'),(2018,5,NULL,'user.logout',NULL,'10.7.110.86','2026-08-27 01:17:02'),(2019,1,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 01:17:06'),(2020,1,1218,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.86','2026-08-27 01:17:29'),(2021,1,NULL,'user.logout',NULL,'10.7.110.86','2026-08-27 01:18:26'),(2022,5,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 01:18:32'),(2023,5,1218,'masukan_sejawat.kirim','{\"catatan\":2}','10.7.110.86','2026-08-27 01:19:07'),(2024,5,NULL,'user.logout',NULL,'10.7.110.86','2026-08-27 01:19:10'),(2025,1,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 01:19:14'),(2026,1,NULL,'user.logout',NULL,'10.7.110.86','2026-08-27 01:20:17'),(2027,5,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 01:20:22'),(2028,5,1219,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.86','2026-08-27 01:38:15'),(2029,5,NULL,'user.login',NULL,'10.7.110.86','2026-08-27 04:04:19'),(2030,5,1220,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.86','2026-08-27 04:05:56'),(2032,5,NULL,'user.login',NULL,'192.168.100.187','2026-08-27 12:31:34'),(2033,5,1222,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','192.168.100.187','2026-08-27 12:31:53'),(2034,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"Test QuillJS\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-27 14:00:20'),(2035,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-27 14:00:20'),(2036,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"test preview quill js\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-27 14:00:20'),(2037,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"title\":\"sedfv\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-27 14:00:20'),(2038,NULL,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"title\":\"Testing lagi\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-27 14:00:20'),(2039,NULL,NULL,'document.purge_all','{\"jumlah\":5,\"alasan\":\"Pembersihan data pengembangan (smartpro:hapus-dokumen)\"}','127.0.0.1','2026-08-27 14:00:20'),(2040,1,NULL,'user.login',NULL,'192.168.100.187','2026-08-27 19:55:04'),(2041,1,NULL,'user.logout',NULL,'192.168.100.187','2026-08-27 20:11:48'),(2042,7,NULL,'user.login',NULL,'192.168.100.187','2026-08-27 20:11:53'),(2043,7,NULL,'user.logout',NULL,'192.168.100.187','2026-08-27 21:12:59'),(2049,5,1223,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-28 04:09:16'),(2050,5,1224,'document.create','{\"doc_number\":\"PPA-ADRO-IK-ICTMD-01\",\"type\":\"IK\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-28 04:12:55'),(2051,5,1225,'document.create','{\"doc_number\":\"PPA-ADRO-SP-ICTMD-01\",\"type\":\"SP\",\"department\":\"ICTMD\"}','10.7.110.72','2026-08-28 04:15:25'),(2052,NULL,1226,'document.arsip_upload','{\"doc_number\":\"PPA-ADRO-FK-ICTMD-99\",\"type\":\"FK\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":0}','127.0.0.1','2026-08-28 04:40:02'),(2053,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-28 10:53:21'),(2054,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-28 11:42:54'),(2055,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-28 12:07:18'),(2056,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-29 00:26:10'),(2057,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-29 00:26:55'),(2058,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-29 00:27:19'),(2059,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-29 01:31:08'),(2060,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-29 08:43:02'),(2061,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-29 19:11:54'),(2062,1,1228,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','127.0.0.1','2026-08-29 19:12:35'),(2071,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-30 01:16:18'),(2072,NULL,1228,'document.delete',NULL,'127.0.0.1','2026-08-30 02:16:44'),(2073,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-30 10:29:25'),(2074,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-30 13:49:11'),(2075,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-30 21:33:52'),(2076,1,1239,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','127.0.0.1','2026-08-30 23:21:36'),(2077,1,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 03:38:55'),(2078,1,NULL,'user.toggle_status','{\"user_id\":796,\"status\":\"active\"}','127.0.0.1','2026-08-31 05:49:04'),(2079,1,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 05:52:31'),(2080,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 05:52:36'),(2081,5,1240,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','127.0.0.1','2026-08-31 08:42:14'),(2082,5,1240,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','127.0.0.1','2026-08-31 08:43:06'),(2083,5,1225,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','127.0.0.1','2026-08-31 08:43:37'),(2084,5,1224,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','127.0.0.1','2026-08-31 08:43:54'),(2085,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 08:44:02'),(2086,11,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 08:44:12'),(2087,11,1224,'document.review_start',NULL,'127.0.0.1','2026-08-31 08:44:21'),(2088,11,1224,'document.review_approve',NULL,'127.0.0.1','2026-08-31 08:44:36'),(2089,11,1225,'document.review_start',NULL,'127.0.0.1','2026-08-31 08:44:43'),(2090,2,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 13:50:36'),(2091,2,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 13:56:07'),(2092,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 13:56:13'),(2093,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 14:38:40'),(2094,666,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 14:38:48'),(2095,666,1226,'feedback.create','{\"feedback_number\":\"MSK-2026-0001\"}','127.0.0.1','2026-08-31 14:39:00'),(2096,666,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 14:39:20'),(2097,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 14:39:25'),(2098,5,1241,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','127.0.0.1','2026-08-31 17:58:09'),(2099,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 18:42:53'),(2100,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 18:55:44'),(2101,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 18:55:57'),(2102,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 23:19:17'),(2103,5,NULL,'user.logout',NULL,'127.0.0.1','2026-08-31 23:19:31'),(2104,5,NULL,'user.login',NULL,'127.0.0.1','2026-08-31 23:52:13'),(2105,5,NULL,'user.login',NULL,'127.0.0.1','2026-09-01 05:21:45'),(2106,5,NULL,'user.logout',NULL,'127.0.0.1','2026-09-01 05:24:14'),(2107,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-01 05:24:22'),(2108,11,1225,'document.review_approve',NULL,'127.0.0.1','2026-09-01 05:26:10'),(2109,11,1240,'document.review_start',NULL,'127.0.0.1','2026-09-01 05:26:13'),(2110,11,1240,'document.review_approve','{\"lanjut_ke\":\"md\"}','127.0.0.1','2026-09-01 05:26:18'),(2111,11,NULL,'user.logout',NULL,'127.0.0.1','2026-09-01 05:45:08'),(2112,15,NULL,'user.login',NULL,'127.0.0.1','2026-09-01 05:45:19'),(2113,15,1225,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SP-ICTMD-01\"}','127.0.0.1','2026-09-01 06:16:59'),(2114,15,1224,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-IK-ICTMD-01\"}','127.0.0.1','2026-09-01 06:17:06'),(2115,15,NULL,'user.logout',NULL,'127.0.0.1','2026-09-01 06:18:21'),(2116,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-01 06:18:27'),(2117,5,NULL,'user.login',NULL,'127.0.0.1','2026-09-02 23:09:58'),(2118,5,NULL,'user.logout',NULL,'127.0.0.1','2026-09-02 23:10:53'),(2119,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-02 23:10:58'),(2120,11,NULL,'user.logout',NULL,'127.0.0.1','2026-09-02 23:11:46'),(2121,15,NULL,'user.login',NULL,'127.0.0.1','2026-09-02 23:11:53'),(2122,5,NULL,'user.login',NULL,'127.0.0.1','2026-09-03 23:55:55'),(2123,5,NULL,'user.logout',NULL,'127.0.0.1','2026-09-03 23:56:33'),(2124,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-03 23:56:42'),(2125,11,NULL,'user.logout',NULL,'127.0.0.1','2026-09-04 00:21:52'),(2126,15,NULL,'user.login',NULL,'127.0.0.1','2026-09-04 00:21:59'),(2127,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-04 03:16:27'),(2128,11,NULL,'user.logout',NULL,'127.0.0.1','2026-09-04 03:21:15'),(2129,15,NULL,'user.login',NULL,'127.0.0.1','2026-09-04 03:21:20'),(2130,15,NULL,'user.login',NULL,'127.0.0.1','2026-09-04 11:55:08'),(2131,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-04 23:32:08'),(2132,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-05 03:15:37'),(2133,5,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 01:05:55'),(2134,5,NULL,'user.logout',NULL,'127.0.0.1','2026-09-06 02:36:08'),(2135,1,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 02:36:15'),(2136,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-SHE-03\",\"title\":\"IDENTIFIKASI BAHAYA DAN PENILAIAN RISIKO\",\"type\":\"SOP\",\"department_id\":1,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2137,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-SHE-09\",\"title\":\"AUDIT INTERNAL\",\"type\":\"SOP\",\"department_id\":1,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2138,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"type\":\"JSA\",\"department_id\":6,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2139,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"STANDARISASI KOMPUTER & PEMELIHARAAN PERANGKAT SERTA INFRASTRUKTUR ICT\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2140,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"New-App SOP\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2141,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-IK-ICTMD-01\",\"title\":\"New-App IK\",\"type\":\"IK\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2142,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SP-ICTMD-01\",\"title\":\"NEW-APP SP\",\"type\":\"SP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2143,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-FK-ICTMD-99\",\"title\":\"Formulir Kerja Baseline Uji\",\"type\":\"FK\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2144,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-98\",\"title\":\"New-App SOP (uji Log Revisi)\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2145,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"m\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2146,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"testing\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2147,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"title\":\"tess\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2148,1,NULL,'document.purge','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"title\":\"tes\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2149,1,NULL,'document.purge_all','{\"jumlah\":13,\"alasan\":\"test 2 md AJA\"}','127.0.0.1','2026-09-06 02:37:24'),(2150,1,NULL,'user.logout',NULL,'127.0.0.1','2026-09-06 02:37:37'),(2151,5,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 02:37:48'),(2152,5,1242,'document.arsip_upload','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"type\":\"SOP\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":2}','127.0.0.1','2026-09-06 02:39:27'),(2153,5,1243,'document.request_revision','{\"from_document_id\":1242,\"no_revisi\":2}','127.0.0.1','2026-09-06 02:40:05'),(2154,5,1243,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-10\",\"sumber\":\"salin_arsip\"}','127.0.0.1','2026-09-06 02:43:21'),(2155,5,1244,'document.create','{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}','127.0.0.1','2026-09-06 02:44:51'),(2156,5,1244,'document.submit','{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}','127.0.0.1','2026-09-06 02:48:35'),(2157,5,NULL,'user.logout',NULL,'127.0.0.1','2026-09-06 02:50:18'),(2158,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 02:50:23'),(2159,11,NULL,'user.logout',NULL,'127.0.0.1','2026-09-06 02:50:42'),(2160,11,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 02:50:47'),(2161,11,1244,'document.review_start',NULL,'127.0.0.1','2026-09-06 02:51:30'),(2162,11,1244,'document.review_approve','{\"lanjut_ke\":\"md\"}','127.0.0.1','2026-09-06 02:51:52'),(2163,11,NULL,'user.logout',NULL,'127.0.0.1','2026-09-06 02:52:01'),(2164,2,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 02:52:08'),(2165,2,1244,'document.md_approve',NULL,'127.0.0.1','2026-09-06 02:52:19'),(2166,2,NULL,'user.logout',NULL,'127.0.0.1','2026-09-06 02:52:50'),(2167,15,NULL,'user.login',NULL,'127.0.0.1','2026-09-06 02:52:57'),(2168,15,1244,'document.approve','{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-01\"}','127.0.0.1','2026-09-06 02:54:05');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('smartpro-shadcn-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:2;',1788576432),('smartpro-shadcn-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1788576432;',1788576432),('smartpro-shadcn-cache-kesehatan:lampiran_bytes','i:1104365;',1788662515),('smartpro-shadcn-cache-libur_nasional:2026','a:0:{}',1788749312),('smartpro-shadcn-cache-pengaturan:ai.cadangan.key','s:312:\"eyJpdiI6IkFGc2tjZ25XUzdvc3VXRDRwbXhxalE9PSIsInZhbHVlIjoibUh3VE8rZXVVQ3lrU0pXNnhDNW1zUTJHK2gxRlFyUmNoeHRwY0tqMmlzUzQxQ2QxTzJhWVdmaGI0S3BYTndHRmk5Wit2d254enpYalNhUnBCNmgwN2hwdHNrRERkMWIzMkVXVHowbS9Jc0U9IiwibWFjIjoiODNmNDE5YzE4YmExYTdjNzU5NTM1ZGI0NWRjN2JiMzkzODMxMmQ1MGY5NDQxNDk3YjRjODJhNTcxNjkzNTE5MSIsInRhZyI6IiJ9\";',2103850520),('smartpro-shadcn-cache-pengaturan:ai.cadangan.model','s:38:\"nvidia/nemotron-3-super-120b-a12b:free\";',2103850520),('smartpro-shadcn-cache-pengaturan:ai.cadangan.provider','s:10:\"openrouter\";',2103850520),('smartpro-shadcn-cache-pengaturan:ai.enabled','s:1:\"1\";',2103850520),('smartpro-shadcn-cache-pengaturan:ai.key','s:312:\"eyJpdiI6InhHeVA5QXRSekh1MThyaGlTT3hVR1E9PSIsInZhbHVlIjoiYmJWZUw5Sm9xOHMxYW4rSk5iZ280NUxmc1c5SzlBUnU5bnpSWkc2d0lObmdSSDVrYTZDMTJWQ3FJT0ZDb2ZLK1QvZSszM1R5QjhyWUoxY2JMRjdrRXBMcVRHTm04YUhWRFozSlRBc3dvMFE9IiwibWFjIjoiZTU3MjEyZDdiZjljYjhiYTIwMGYyNzk3ZWNiZWQ5OTRmODhmODEzNzRiMzBjMWYzMDVjMjJiNGQ5MTc4ZjVmNiIsInRhZyI6IiJ9\";',2103850520),('smartpro-shadcn-cache-pengaturan:ai.model','s:38:\"nvidia/nemotron-3-ultra-550b-a55b:free\";',2103850520),('smartpro-shadcn-cache-pengaturan:ai.provider','s:10:\"openrouter\";',2103850520),('smartpro-shadcn-cache-pengaturan:site.nama','s:0:\"\";',2103850520),('smartpro-shadcn-cache-pengaturan:site.prefix','s:0:\"\";',2103842627),('smartpro-shadcn-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:20:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"document.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"document.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"document.submit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"document.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"document.review\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:7;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:16:\"document.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:7;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:16:\"document.publish\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"document.view_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;i:4;i:7;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:19:\"document.view_scope\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:17:\"document.view_all\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:8;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:11:\"user.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:25:\"user.approve_registration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"user.create_staff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:22:\"document.change_status\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:10:\"audit.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;i:5;i:8;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:25:\"document.request_revision\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:8;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:19:\"document.review_jsa\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:18:\"document.review_md\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:8;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:16:\"informasi.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:25:\"document.feedback_respond\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:8;}}}s:5:\"roles\";a:7:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:8:\"admin_it\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"group_leader\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"section_head\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:15:\"departemen_head\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:8:\"pimpinan\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:5:\"staff\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:22:\"management_development\";s:1:\"c\";s:3:\"web\";}}}',1788743158);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
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
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'SHE','Safety, Health & Environment',NULL,1,'2026-07-11 16:37:14','2026-07-11 16:37:14'),(2,'PLANT','Plant',NULL,1,'2026-07-11 16:37:14','2026-07-11 16:37:14'),(3,'HCGA','Human Capital & General Affairs',NULL,1,'2026-07-11 16:37:14','2026-07-11 16:37:14'),(4,'FAW-SCM','Finance, Accounting & Warehouse — Supply Chain Management','FALOG',1,'2026-07-11 16:37:14','2026-07-30 20:18:21'),(5,'ICTMD','ICT & Management Development',NULL,1,'2026-07-11 16:37:14','2026-07-11 16:37:14'),(6,'PRODUKSI','Produksi',NULL,1,'2026-07-11 16:37:14','2026-07-11 16:37:14'),(7,'ENGINEERING','Engineering',NULL,1,'2026-07-11 16:37:14','2026-07-11 16:37:14');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_authors`
--

DROP TABLE IF EXISTS `document_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_authors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_authors_document_id_user_id_unique` (`document_id`,`user_id`),
  KEY `document_authors_user_id_foreign` (`user_id`),
  CONSTRAINT `document_authors_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_authors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_authors`
--

LOCK TABLES `document_authors` WRITE;
/*!40000 ALTER TABLE `document_authors` DISABLE KEYS */;
INSERT INTO `document_authors` VALUES (1282,1242,5,1,'2026-09-06 02:39:27','2026-09-06 02:39:27'),(1283,1243,5,1,'2026-09-06 02:40:05','2026-09-06 02:40:05'),(1284,1244,5,1,'2026-09-06 02:44:51','2026-09-06 02:44:51');
/*!40000 ALTER TABLE `document_authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_contents`
--

DROP TABLE IF EXISTS `document_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_contents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_contents_document_id_section_key_unique` (`document_id`,`section_key`),
  KEY `document_contents_section_key_index` (`section_key`),
  CONSTRAINT `document_contents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_contents`
--

LOCK TABLES `document_contents` WRITE;
/*!40000 ALTER TABLE `document_contents` DISABLE KEYS */;
INSERT INTO `document_contents` VALUES (891,1242,'catatan_revisi','[{\"no_rev\":1,\"tanggal\":\"2026-09-04\",\"halaman\":\"1-4\",\"catatan\":\"cxzczszx\"},{\"no_rev\":2,\"tanggal\":\"2026-09-05\",\"halaman\":\"1-11\",\"catatan\":\"zxz\"}]','2026-09-06 02:40:05','2026-09-06 02:40:05'),(892,1243,'catatan_revisi','[{\"no_rev\":1,\"tanggal\":\"2026-09-04\",\"halaman\":\"1-4\",\"catatan\":\"cxzczszx\"},{\"no_rev\":2,\"tanggal\":\"2026-09-05\",\"halaman\":\"1-11\",\"catatan\":\"zxz\"}]','2026-09-06 02:40:05','2026-09-06 02:40:05'),(893,1243,'tujuan','[\"sadadsads\"]','2026-09-06 02:40:27','2026-09-06 02:40:27'),(894,1243,'ruang_lingkup','[\"sadsad\"]','2026-09-06 02:40:27','2026-09-06 02:40:27'),(895,1243,'referensi','[\"sadsad\"]','2026-09-06 02:40:27','2026-09-06 02:40:27'),(896,1243,'definisi','[\"zxzczc\"]','2026-09-06 02:40:27','2026-09-06 02:40:27'),(897,1243,'pakai_flowchart','\"1\"','2026-09-06 02:43:12','2026-09-06 02:43:12'),(898,1243,'flowchart','[{\"judul\":\"Flowcchart Alur Deteksi HD\",\"gambar\":null,\"keterangan\":\"sa\"}]','2026-09-06 02:43:12','2026-09-06 02:43:12'),(899,1243,'aktivitas','[{\"sub_judul\":\"Persiapan Handover\",\"deskripsi\":\"<p>znmNz<\\/p><ol><li>csds<\\/li><li>sdsd<\\/li><li>sds<\\/li><li>ddw<\\/li><\\/ol><p><strong>saskas<em>askashas<u>kaskash,dlaslaslak<\\/u><\\/em><\\/strong><\\/p><p>sdsdsd<\\/p><ul><li>sdsdsd<\\/li><li>sdsd<\\/li><li>sddwdwa<\\/li><\\/ul>\",\"pic\":\"ICT\"}]','2026-09-06 02:43:12','2026-09-06 02:43:12'),(900,1243,'lampiran','[{\"dokumen\":\"malsmals\",\"keterangan\":\"csakl\"}]','2026-09-06 02:43:12','2026-09-06 02:43:12'),(901,1244,'tujuan','[\"sszc\"]','2026-09-06 02:45:04','2026-09-06 02:45:04'),(902,1244,'ruang_lingkup','[\"sczxsczx\"]','2026-09-06 02:45:04','2026-09-06 02:45:04'),(903,1244,'referensi','[\"sczxc\"]','2026-09-06 02:45:04','2026-09-06 02:45:04'),(904,1244,'definisi','[\"sczxcs\"]','2026-09-06 02:45:04','2026-09-06 02:45:04'),(905,1244,'pakai_flowchart','\"1\"','2026-09-06 02:48:35','2026-09-06 02:48:35'),(906,1244,'flowchart','[{\"judul\":\"Flowcchart Alur Deteksi HD\",\"gambar\":null,\"keterangan\":\"xzsacsac\"}]','2026-09-06 02:48:35','2026-09-06 02:48:35'),(907,1244,'aktivitas','[{\"sub_judul\":\"sxax\",\"deskripsi\":\"<p>sczsc<\\/p>\",\"pic\":\"dsc\"}]','2026-09-06 02:48:35','2026-09-06 02:48:35'),(908,1244,'lampiran','[{\"dokumen\":\"PPA-ADRO-SOP-ICTMD-10\",\"keterangan\":\"SOP Produksi\"}]','2026-09-06 02:48:35','2026-09-06 02:48:35');
/*!40000 ALTER TABLE `document_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_feedback`
--

DROP TABLE IF EXISTS `document_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feedback_number` varchar(30) NOT NULL,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `isi` text NOT NULL,
  `status` enum('baru','dibaca','diadopsi','ditolak') NOT NULL DEFAULT 'baru',
  `balasan` text DEFAULT NULL,
  `replied_by` bigint(20) unsigned DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `revision_document_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_feedback_feedback_number_unique` (`feedback_number`),
  KEY `document_feedback_user_id_foreign` (`user_id`),
  KEY `document_feedback_replied_by_foreign` (`replied_by`),
  KEY `document_feedback_revision_document_id_foreign` (`revision_document_id`),
  KEY `document_feedback_document_id_status_index` (`document_id`,`status`),
  KEY `document_feedback_status_index` (`status`),
  CONSTRAINT `document_feedback_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_feedback_replied_by_foreign` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`),
  CONSTRAINT `document_feedback_revision_document_id_foreign` FOREIGN KEY (`revision_document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `document_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_feedback`
--

LOCK TABLES `document_feedback` WRITE;
/*!40000 ALTER TABLE `document_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_feedback_antargl`
--

DROP TABLE IF EXISTS `document_feedback_antargl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_feedback_antargl` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `ringkasan` text DEFAULT NULL,
  `catatan_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`catatan_json`)),
  `dibaca_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_feedback_antargl_user_id_foreign` (`user_id`),
  KEY `document_feedback_antargl_document_id_created_at_index` (`document_id`,`created_at`),
  CONSTRAINT `document_feedback_antargl_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_feedback_antargl_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_feedback_antargl`
--

LOCK TABLES `document_feedback_antargl` WRITE;
/*!40000 ALTER TABLE `document_feedback_antargl` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_feedback_antargl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_reads`
--

DROP TABLE IF EXISTS `document_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `first_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_count` int(10) unsigned NOT NULL DEFAULT 1,
  `download_count` int(10) unsigned NOT NULL DEFAULT 0,
  `platform` varchar(10) NOT NULL DEFAULT 'web',
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_reads_document_id_user_id_unique` (`document_id`,`user_id`),
  KEY `document_reads_user_id_foreign` (`user_id`),
  KEY `document_reads_document_id_last_read_at_index` (`document_id`,`last_read_at`),
  CONSTRAINT `document_reads_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_reads`
--

LOCK TABLES `document_reads` WRITE;
/*!40000 ALTER TABLE `document_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_types`
--

DROP TABLE IF EXISTS `document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `schema_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema_json`)),
  `class` enum('inti','independen','lintas','unggahan') NOT NULL DEFAULT 'inti',
  `scope` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_types`
--

LOCK TABLES `document_types` WRITE;
/*!40000 ALTER TABLE `document_types` DISABLE KEYS */;
INSERT INTO `document_types` VALUES (1,'SOP','Standard Operating Procedure','{\"doc_type\":\"SOP\",\"doc_type_label\":\"STANDARD OPERATING PROCEDURE\",\"header\":\"_kop\",\"footer\":\"_footer\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"cover_page\":\"_cover\",\"steps\":[{\"step\":1,\"title\":\"Tujuan, Ruang Lingkup, Referensi & Definisi\",\"sections\":[{\"label\":\"I. TUJUAN\",\"auto_number\":\"1.\",\"key\":\"tujuan\",\"nama\":\"TUJUAN\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"Masukkan poin tujuan...\"},{\"label\":\"II. RUANG LINGKUP\",\"auto_number\":\"2.\",\"key\":\"ruang_lingkup\",\"nama\":\"RUANG LINGKUP\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"Masukkan poin ruang lingkup...\"},{\"label\":\"III. REFERENSI\",\"auto_number\":\"3.\",\"key\":\"referensi\",\"nama\":\"REFERENSI\",\"type\":\"reference_picker\",\"allow_add\":true,\"placeholder\":\"Masukkan referensi...\",\"suggestions\":[\"ISO 9001:2015 Sistem Manajemen Mutu\",\"ISO 45001:2018 Sistem Manajemen K3\",\"ISO 14001:2015 Sistem Manajemen Lingkungan\",\"SMKP Minerba (Permen ESDM No. 26\\/2018)\",\"UU No. 1 Tahun 1970 tentang Keselamatan Kerja\"]},{\"label\":\"IV. DEFINISI\",\"auto_number\":\"4.\",\"key\":\"definisi\",\"nama\":\"DEFINISI\",\"type\":\"rich_list\",\"placeholder\":\"Masukkan definisi...\"}]},{\"step\":2,\"title\":\"Flowchart, Aktivitas, Lampiran & Verifikasi\",\"title_tanpa_opsional\":\"Aktivitas, Lampiran & Verifikasi\",\"sections\":[{\"label\":\"V. FLOWCHART\",\"auto_number\":\"5.\",\"key\":\"flowchart\",\"nama\":\"FLOWCHART\",\"type\":\"repeatable_group\",\"bab_opsional\":true,\"toggle_key\":\"pakai_flowchart\",\"toggle_label\":\"Gunakan Flowchart\",\"min_groups\":1,\"page_break\":\"both\",\"required\":false,\"warn_if_empty\":true,\"add_button_label\":\"+ Tambah Flowchart\",\"group_fields\":[{\"key\":\"judul\",\"label\":\"Judul Flowchart\",\"type\":\"text\",\"placeholder\":\"Judul (Contoh: Alur Serah Terima Shift)\",\"warn_if_empty\":true},{\"key\":\"gambar\",\"label\":\"Gambar Flowchart\",\"type\":\"image\",\"image_accept\":\"image\\/jpeg,image\\/png\",\"image_max_mb\":2},{\"key\":\"keterangan\",\"label\":\"Keterangan\",\"type\":\"textarea\",\"placeholder\":\"Keterangan flowchart...\",\"warn_if_empty\":true}]},{\"label\":\"VI. AKTIVITAS DAN TANGGUNG JAWAB\",\"auto_number\":\"6.\",\"key\":\"aktivitas\",\"nama\":\"AKTIVITAS DAN TANGGUNG JAWAB\",\"type\":\"repeatable_group\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Aktivitas\\/Tanggung Jawab\",\"group_fields\":[{\"key\":\"sub_judul\",\"label\":\"Sub Judul\",\"type\":\"text\",\"placeholder\":\"Sub Judul (Contoh: Tahap Persiapan)\"},{\"key\":\"deskripsi\",\"label\":\"Deskripsi Aktivitas\",\"type\":\"rich_text\",\"placeholder\":\"Uraikan langkahnya. Bisa ditebalkan, dibuat daftar bernomor, dikutip, atau disisipi foto.\"},{\"key\":\"pic\",\"label\":\"PIC\",\"type\":\"text\",\"placeholder\":\"PIC (Contoh: Tim ICT)\"}]},{\"label\":\"VII. LAMPIRAN\",\"auto_number\":\"7.\",\"key\":\"lampiran\",\"nama\":\"LAMPIRAN\",\"type\":\"repeatable_group\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Lampiran\",\"required\":false,\"warn_if_empty\":true,\"group_fields\":[{\"key\":\"dokumen\",\"label\":\"Nomor Dokumen\",\"type\":\"document_picker\",\"placeholder\":\"Pilih dokumen Berlaku, atau ketik manual...\",\"warn_if_empty\":true,\"judul_ke\":\"keterangan\"},{\"key\":\"keterangan\",\"label\":\"Judul \\/ Keterangan (opsional)\",\"type\":\"textarea\",\"placeholder\":\"Terisi sendiri saat dokumen dipilih dari daftar. Boleh diubah atau dikosongkan.\",\"warn_if_empty\":true}]},{\"key\":\"pembuat_tambahan\",\"label\":\"Pembuat Tambahan (opsional)\",\"type\":\"user_picker\",\"multiple\":true,\"required\":false,\"hint\":\"Gunakan tombol + jika pembuat lebih dari 1 orang.\"},{\"key\":\"peninjau\",\"label\":\"Ditinjau Oleh (DH\\/SH)\",\"type\":\"user_picker\",\"role_filter\":[\"group_leader\",\"section_head\"],\"required\":true},{\"key\":\"penyetuju\",\"label\":\"Disetujui Oleh\",\"type\":\"user_picker\",\"role_filter\":[\"pimpinan\"],\"required\":true}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau Oleh\",\"role\":\"peninjau\"},{\"role_label\":\"Disetujui Oleh\",\"role\":\"penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}','inti','all_departments',1,'2026-07-11 16:37:14','2026-09-02 11:33:03'),(2,'IK','Instruksi Kerja','{\"doc_type\":\"IK\",\"doc_type_label\":\"INSTRUKSI KERJA\",\"header\":\"_kop\",\"footer\":\"_footer\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"cover_page\":\"_cover\",\"steps\":[{\"step\":1,\"title\":\"Aktivitas & Pengesahan\",\"sections\":[{\"key\":\"aktivitas\",\"label\":\"I. AKTIVITAS DAN TANGGUNG JAWAB\",\"type\":\"repeatable_group\",\"auto_number\":\"1.\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Aktivitas\\/Tanggung Jawab\",\"group_fields\":[{\"key\":\"sub_judul\",\"label\":\"Sub Judul\",\"type\":\"text\",\"placeholder\":\"Sub Judul (Contoh: Pemeliharaan bulanan)\"},{\"key\":\"deskripsi\",\"label\":\"Deskripsi Aktivitas\",\"type\":\"rich_text\",\"placeholder\":\"Uraikan langkahnya. Bisa ditebalkan, dibuat daftar bernomor, dikutip, atau disisipi foto.\"},{\"key\":\"pic\",\"label\":\"PIC\",\"type\":\"text\",\"placeholder\":\"PIC (Contoh: ICT)\"}]},{\"key\":\"pembuat_tambahan\",\"label\":\"Pembuat Tambahan (opsional)\",\"type\":\"user_picker\",\"multiple\":true,\"required\":false,\"hint\":\"Gunakan tombol + jika pembuat lebih dari 1 orang.\"},{\"key\":\"peninjau_penyetuju\",\"label\":\"Ditinjau & Disetujui Oleh (SH\\/DH Dept)\",\"type\":\"user_picker\",\"role_filter\":[\"section_head\",\"departemen_head\"],\"required\":true}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau dan Disetujui Oleh\",\"role\":\"peninjau_penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}','inti','all_departments',1,'2026-07-11 22:36:16','2026-08-27 01:24:56'),(3,'SP','Standar Parameter','{\"doc_type\":\"SP\",\"doc_type_label\":\"STANDAR PARAMETER\",\"header\":\"_kop\",\"footer\":\"_footer\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"cover_page\":\"_cover\",\"steps\":[{\"step\":1,\"title\":\"Tujuan, Ruang Lingkup, Referensi & Definisi\",\"sections\":[{\"label\":\"I. TUJUAN\",\"auto_number\":\"1.\",\"key\":\"tujuan\",\"nama\":\"TUJUAN\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"Masukkan poin tujuan...\"},{\"label\":\"II. RUANG LINGKUP\",\"auto_number\":\"2.\",\"key\":\"ruang_lingkup\",\"nama\":\"RUANG LINGKUP\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"Masukkan poin ruang lingkup...\"},{\"label\":\"III. REFERENSI\",\"auto_number\":\"3.\",\"key\":\"referensi\",\"nama\":\"REFERENSI\",\"type\":\"reference_picker\",\"allow_add\":true,\"placeholder\":\"Masukkan referensi...\",\"suggestions\":[\"ISO 9001:2015 Sistem Manajemen Mutu\",\"ISO 45001:2018 Sistem Manajemen K3\",\"ISO 14001:2015 Sistem Manajemen Lingkungan\",\"SMKP Minerba (Permen ESDM No. 26\\/2018)\",\"UU No. 1 Tahun 1970 tentang Keselamatan Kerja\"]},{\"label\":\"IV. DEFINISI\",\"auto_number\":\"4.\",\"key\":\"definisi\",\"nama\":\"DEFINISI\",\"type\":\"rich_list\",\"placeholder\":\"Masukkan definisi...\"}]},{\"step\":2,\"title\":\"Aktivitas, Lampiran & Verifikasi\",\"sections\":[{\"label\":\"V. AKTIVITAS DAN TANGGUNG JAWAB\",\"auto_number\":\"5.\",\"key\":\"aktivitas\",\"nama\":\"AKTIVITAS DAN TANGGUNG JAWAB\",\"type\":\"repeatable_group\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Aktivitas\\/Tanggung Jawab\",\"group_fields\":[{\"key\":\"sub_judul\",\"label\":\"Sub Judul\",\"type\":\"text\",\"placeholder\":\"Sub Judul (Contoh: Tahap Persiapan)\"},{\"key\":\"deskripsi\",\"label\":\"Deskripsi Aktivitas\",\"type\":\"rich_text\",\"placeholder\":\"Uraikan langkahnya. Bisa ditebalkan, dibuat daftar bernomor, dikutip, atau disisipi foto.\"},{\"key\":\"pic\",\"label\":\"PIC\",\"type\":\"text\",\"placeholder\":\"PIC (Contoh: Tim ICT)\"}]},{\"label\":\"VI. LAMPIRAN\",\"auto_number\":\"6.\",\"key\":\"lampiran\",\"nama\":\"LAMPIRAN\",\"type\":\"repeatable_group\",\"min_groups\":0,\"add_button_label\":\"+ Tambah Lampiran Baru\",\"required\":false,\"group_fields\":[{\"key\":\"judul\",\"label\":\"Judul Lampiran\",\"type\":\"text\",\"placeholder\":\"Judul Lampiran (Contoh: Form Ceklis)\"},{\"key\":\"keterangan\",\"label\":\"Keterangan\",\"type\":\"textarea\",\"placeholder\":\"Keterangan \\/ caption lampiran (opsional)...\",\"warn_if_empty\":true},{\"key\":\"gambar\",\"label\":\"Foto \\/ Gambar (opsional)\",\"type\":\"image\",\"image_accept\":\"image\\/jpeg,image\\/png\",\"image_max_mb\":2}]},{\"key\":\"pembuat_tambahan\",\"label\":\"Pembuat Tambahan (opsional)\",\"type\":\"user_picker\",\"multiple\":true,\"required\":false,\"hint\":\"Gunakan tombol + jika pembuat lebih dari 1 orang.\"},{\"key\":\"peninjau_penyetuju\",\"label\":\"Ditinjau & Disetujui Oleh (SH\\/DH Dept)\",\"type\":\"user_picker\",\"role_filter\":[\"section_head\",\"departemen_head\"],\"required\":true}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau dan Disetujui Oleh\",\"role\":\"peninjau_penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}','inti','all_departments',1,'2026-07-11 22:36:16','2026-08-27 08:41:34'),(4,'JSA','Job Safety Analysis','{\"doc_type\":\"JSA\",\"doc_type_label\":\"FORMULIR JOB SAFETY ANALYSIS\",\"orientation\":\"landscape\",\"header\":\"_kop_jsa\",\"footer\":\"_footer\",\"print_view\":\"documents.print.render-jsa\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"approval_page\":\"_pengesahan\",\"steps\":[{\"step\":1,\"title\":\"Informasi Umum\",\"sections\":[{\"key\":\"lokasi_kerja\",\"label\":\"Lokasi Kerja\",\"type\":\"text\",\"placeholder\":\"mis. View Point\"},{\"key\":\"apd\",\"label\":\"APD yang digunakan\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"mis. Helm safety, Body harness, Sarung tangan...\"},{\"key\":\"tools\",\"label\":\"Peralatan yang digunakan\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"mis. Obeng, Bor, Tang...\"},{\"key\":\"form_tgl_efektif\",\"label\":\"Tgl. Efektif (kop)\",\"type\":\"date\"},{\"key\":\"peninjau\",\"label\":\"Ditinjau Oleh\",\"type\":\"user_picker\",\"role_filter\":[\"group_leader\",\"section_head\"],\"required\":true},{\"key\":\"penyetuju\",\"label\":\"Disetujui Oleh\",\"type\":\"user_picker\",\"role_filter\":[\"pimpinan\"],\"required\":true}]},{\"step\":2,\"title\":\"Analisa Bahaya\",\"sections\":[{\"key\":\"analisa\",\"label\":\"Analisa Bahaya\",\"type\":\"jsa_analysis\",\"help\":\"Masukkan tahapan pekerjaan, potensi bahaya yang mungkin timbul, dan langkah pengendaliannya.\"}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau Oleh\",\"role\":\"peninjau\"},{\"role_label\":\"Disetujui Oleh\",\"role\":\"penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}','inti','all_departments',1,'2026-07-11 22:36:16','2026-08-03 15:27:04'),(5,'FK','Formulir Kerja','[]','unggahan','all_departments',1,'2026-08-12 23:49:14','2026-08-12 23:49:14'),(6,'PX','Prosedur External','[]','unggahan','all_departments',1,'2026-08-12 23:49:14','2026-08-12 23:49:14');
/*!40000 ALTER TABLE `document_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_versions`
--

DROP TABLE IF EXISTS `document_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `no_revisi` int(10) unsigned NOT NULL,
  `snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_json`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_versions_document_id_foreign` (`document_id`),
  CONSTRAINT `document_versions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_versions`
--

LOCK TABLES `document_versions` WRITE;
/*!40000 ALTER TABLE `document_versions` DISABLE KEYS */;
INSERT INTO `document_versions` VALUES (68,1242,2,'{\"title\":\"SOP Produksi\",\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"no_revisi\":2,\"edisi\":\"1\",\"arsip_path\":\"arsip\\/ICTMD\\/SOP\\/dE2OETgxLZ0uPRaqtGvfRH71r8EPfd3zGwEX83N8.pdf\",\"status\":\"published\",\"reviewer_id\":null,\"approver_id\":null,\"published_at\":\"2026-09-04 00:00:00\",\"contents\":{\"catatan_revisi\":[{\"no_rev\":1,\"tanggal\":\"2026-09-04\",\"halaman\":\"1-4\",\"catatan\":\"cxzczszx\"},{\"no_rev\":2,\"tanggal\":\"2026-09-05\",\"halaman\":\"1-11\",\"catatan\":\"zxz\"}]}}',5,'2026-09-06 02:40:05','2026-09-06 02:40:05');
/*!40000 ALTER TABLE `document_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doc_number_final` varchar(255) DEFAULT NULL,
  `doc_number` varchar(255) DEFAULT NULL,
  `doc_number_manual` tinyint(1) NOT NULL DEFAULT 0,
  `arsip_path` varchar(255) DEFAULT NULL,
  `arsip_path_asli` varchar(255) DEFAULT NULL,
  `document_type_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('menunggu_nonaktif','draft','waiting_for_review','in_review','rejected','verifikasi_md','pending_approval','published','sedang_direvisi','obsolete','submitted','needs_revision','archived') NOT NULL DEFAULT 'draft',
  `nonaktif_tahap` enum('sh','md','pjo') DEFAULT NULL,
  `nonaktif_oleh` bigint(20) unsigned DEFAULT NULL,
  `obsolete_reason` enum('revisi','dinonaktifkan') DEFAULT NULL,
  `current_step` int(10) unsigned NOT NULL DEFAULT 1,
  `revision_round` int(10) unsigned NOT NULL DEFAULT 0,
  `no_revisi` int(10) unsigned NOT NULL DEFAULT 0,
  `revises_document_id` bigint(20) unsigned DEFAULT NULL,
  `edisi` varchar(255) DEFAULT NULL,
  `is_controlled` tinyint(1) NOT NULL DEFAULT 1,
  `reviewer_id` bigint(20) unsigned DEFAULT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `salin_arsip_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_document_type_id_foreign` (`document_type_id`),
  KEY `documents_revises_document_id_foreign` (`revises_document_id`),
  KEY `documents_doc_number_final_index` (`doc_number_final`),
  KEY `documents_doc_number_index` (`doc_number`),
  KEY `documents_department_id_index` (`department_id`),
  KEY `documents_status_index` (`status`),
  KEY `documents_reviewer_id_index` (`reviewer_id`),
  KEY `documents_approver_id_index` (`approver_id`),
  KEY `documents_created_by_index` (`created_by`),
  KEY `documents_obsolete_reason_index` (`obsolete_reason`),
  CONSTRAINT `documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  CONSTRAINT `documents_revises_document_id_foreign` FOREIGN KEY (`revises_document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1242,'PPA-ADRO-SOP-ICTMD-10','PPA-ADRO-SOP-ICTMD-10',1,'arsip/ICTMD/SOP/dE2OETgxLZ0uPRaqtGvfRH71r8EPfd3zGwEX83N8.pdf',NULL,1,5,'SOP Produksi','obsolete',NULL,NULL,'revisi',1,0,2,NULL,'1',1,NULL,NULL,5,NULL,'2026-09-03 16:00:00',NULL,'2026-09-06 02:39:27','2026-09-06 02:43:21',NULL),(1243,'PPA-ADRO-SOP-ICTMD-10','PPA-ADRO-SOP-ICTMD-10',1,NULL,NULL,1,5,'SOP Produksi','published',NULL,NULL,NULL,3,0,2,1242,'1',1,11,15,5,'2026-09-06 02:43:21','2026-09-06 02:43:21','2026-09-06 02:40:05','2026-09-06 02:40:05','2026-09-06 02:43:21',NULL),(1244,'PPA-ADRO-SOP-ICTMD-01','PPA-ADRO-SOP-ICTMD-01',0,NULL,NULL,1,5,'adwsl;','published',NULL,NULL,NULL,2,0,0,NULL,'1',1,11,15,5,'2026-09-06 02:48:35','2026-09-06 02:54:05',NULL,'2026-09-06 02:44:51','2026-09-06 02:54:05',NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `informasi`
--

DROP TABLE IF EXISTS `informasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informasi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kategori` varchar(32) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `edisi` int(10) unsigned DEFAULT NULL,
  `no_revisi` int(10) unsigned DEFAULT NULL,
  `tanggal_efektif` date DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_mime` varchar(100) NOT NULL,
  `berlaku` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `informasi_uploaded_by_foreign` (`uploaded_by`),
  KEY `informasi_kategori_nomor_index` (`kategori`,`nomor`),
  KEY `informasi_kategori_index` (`kategori`),
  KEY `informasi_berlaku_index` (`berlaku`),
  CONSTRAINT `informasi_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `informasi`
--

LOCK TABLES `informasi` WRITE;
/*!40000 ALTER TABLE `informasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `informasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `informasi_kategori`
--

DROP TABLE IF EXISTS `informasi_kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informasi_kategori` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(32) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `ikon` varchar(50) NOT NULL DEFAULT 'bi-info-circle',
  `kolom_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`kolom_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `urutan` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `informasi_kategori_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `informasi_kategori`
--

LOCK TABLES `informasi_kategori` WRITE;
/*!40000 ALTER TABLE `informasi_kategori` DISABLE KEYS */;
INSERT INTO `informasi_kategori` VALUES (1,'kebijakan','KEBIJAKAN',NULL,'bi-journal-bookmark','[\"edisi\",\"no_revisi\"]',1,10,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(2,'memo_external','MEMO External',NULL,'bi-envelope-open','[]',1,20,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(3,'memo_internal','MEMO Internal',NULL,'bi-envelope','[]',1,30,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(4,'instruksi_ktt','INSTRUKSI KTT',NULL,'bi-megaphone','[\"edisi\",\"no_revisi\",\"tanggal_efektif\"]',1,40,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(5,'poster','POSTER',NULL,'bi-image','[]',1,50,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(6,'msds','MSDS',NULL,'bi-droplet-half','[]',1,60,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(7,'bap','BAP',NULL,'bi-clipboard-data','[]',1,70,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(8,'sertifikat_sio','SERTIFIKAT & SIO',NULL,'bi-patch-check','[]',1,80,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(9,'moc_mprp','MOC & MPRP',NULL,'bi-diagram-3','[]',1,90,'2026-08-25 17:59:16','2026-08-25 17:59:16'),(10,'ibpr','IBPR',NULL,'bi-shield-exclamation','[\"edisi\",\"no_revisi\",\"tanggal_efektif\"]',1,100,'2026-08-25 17:59:16','2026-08-25 17:59:16');
/*!40000 ALTER TABLE `informasi_kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `informasi_reads`
--

DROP TABLE IF EXISTS `informasi_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informasi_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `informasi_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `first_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_count` int(10) unsigned NOT NULL DEFAULT 1,
  `download_count` int(10) unsigned NOT NULL DEFAULT 0,
  `platform` varchar(10) NOT NULL DEFAULT 'web',
  PRIMARY KEY (`id`),
  UNIQUE KEY `informasi_reads_informasi_id_user_id_unique` (`informasi_id`,`user_id`),
  KEY `informasi_reads_user_id_foreign` (`user_id`),
  KEY `informasi_reads_informasi_id_last_read_at_index` (`informasi_id`,`last_read_at`),
  CONSTRAINT `informasi_reads_informasi_id_foreign` FOREIGN KEY (`informasi_id`) REFERENCES `informasi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `informasi_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `informasi_reads`
--

LOCK TABLES `informasi_reads` WRITE;
/*!40000 ALTER TABLE `informasi_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `informasi_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
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
-- Table structure for table `job_checklist_items`
--

DROP TABLE IF EXISTS `job_checklist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_checklist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_execution_id` bigint(20) unsigned NOT NULL,
  `langkah_ke` smallint(5) unsigned NOT NULL,
  `bahaya_ke` smallint(5) unsigned NOT NULL,
  `pengendalian_ke` smallint(5) unsigned NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT 0,
  `checked_at` timestamp NULL DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_checklist_position` (`job_execution_id`,`langkah_ke`,`bahaya_ke`,`pengendalian_ke`),
  CONSTRAINT `job_checklist_items_job_execution_id_foreign` FOREIGN KEY (`job_execution_id`) REFERENCES `job_executions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_checklist_items`
--

LOCK TABLES `job_checklist_items` WRITE;
/*!40000 ALTER TABLE `job_checklist_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_checklist_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_executions`
--

DROP TABLE IF EXISTS `job_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_executions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `nama_pekerjaan` varchar(255) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `tanggal_pelaksanaan` date NOT NULL,
  `analisa_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`analisa_snapshot`)),
  `status` enum('berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'berlangsung',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_executions_document_id_foreign` (`document_id`),
  KEY `job_executions_user_id_foreign` (`user_id`),
  CONSTRAINT `job_executions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  CONSTRAINT `job_executions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_executions`
--

LOCK TABLES `job_executions` WRITE;
/*!40000 ALTER TABLE `job_executions` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_executions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (162,'default','{\"uuid\":\"9adbabd1-81d9-4fd0-9aea-769e1b918a8d\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1240;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:15:\\\"revisesDocument\\\";i:3;s:8:\\\"reviewer\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:45:\\\"Dokumen PPA-ADRO-SOP-ICTMD-04 perlu ditinjau.\\\";s:4:\\\"icon\\\";s:18:\\\"bi-clipboard-check\\\";s:9:\\\"routeName\\\";s:11:\\\"review.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"3933e92f-3cc1-44ef-9e4f-3fa8473d518b\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788165786,\"delay\":null}',0,NULL,1788165786,1788165786),(163,'default','{\"uuid\":\"b8b6ae15-7ea5-48e2-9ed9-564b58b35712\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1225;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:15:\\\"revisesDocument\\\";i:3;s:8:\\\"reviewer\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:44:\\\"Dokumen PPA-ADRO-SP-ICTMD-01 perlu ditinjau.\\\";s:4:\\\"icon\\\";s:18:\\\"bi-clipboard-check\\\";s:9:\\\"routeName\\\";s:11:\\\"review.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"05aabb49-4703-43df-94ca-b0f144134b9e\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788165817,\"delay\":null}',0,NULL,1788165817,1788165817),(164,'default','{\"uuid\":\"c0cfcd9f-5a7a-474e-a08a-909a10fb8424\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1224;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:15:\\\"revisesDocument\\\";i:3;s:8:\\\"reviewer\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:44:\\\"Dokumen PPA-ADRO-IK-ICTMD-01 perlu ditinjau.\\\";s:4:\\\"icon\\\";s:18:\\\"bi-clipboard-check\\\";s:9:\\\"routeName\\\";s:11:\\\"review.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"fe7f2331-dadb-49ff-9997-cd24506c30fd\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788165835,\"delay\":null}',0,NULL,1788165835,1788165835),(165,'default','{\"uuid\":\"df064d85-43f6-4b3b-ad64-c5f0c6a5952f\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1224;s:9:\\\"relations\\\";a:2:{i:0;s:4:\\\"type\\\";i:1;s:8:\\\"approver\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:45:\\\"Dokumen PPA-ADRO-IK-ICTMD-01 perlu disetujui.\\\";s:4:\\\"icon\\\";s:14:\\\"bi-patch-check\\\";s:9:\\\"routeName\\\";s:14:\\\"approvals.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"8ba702ed-6aa1-42c5-80d8-4b2141d5b7fa\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788165876,\"delay\":null}',0,NULL,1788165876,1788165876),(166,'default','{\"uuid\":\"790f1781-307d-4261-a442-bf5a693b3972\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1225;s:9:\\\"relations\\\";a:2:{i:0;s:4:\\\"type\\\";i:1;s:8:\\\"approver\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:45:\\\"Dokumen PPA-ADRO-SP-ICTMD-01 perlu disetujui.\\\";s:4:\\\"icon\\\";s:14:\\\"bi-patch-check\\\";s:9:\\\"routeName\\\";s:14:\\\"approvals.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"72f82af9-8900-4259-ae5d-3617c0b20382\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788240370,\"delay\":null}',0,NULL,1788240370,1788240370),(167,'default','{\"uuid\":\"41fd02a1-8da0-46a4-a1b1-5cd71508dd1f\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:5;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1225;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:7:\\\"creator\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:56:\\\"Dokumen PPA-ADRO-SP-ICTMD-01 disetujui dan kini Berlaku.\\\";s:4:\\\"icon\\\";s:15:\\\"bi-check-circle\\\";s:9:\\\"routeName\\\";s:14:\\\"documents.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"4b8e459a-cb18-4c06-a87d-21a6c655bf35\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788243419,\"delay\":null}',0,NULL,1788243419,1788243419),(168,'default','{\"uuid\":\"2360e4c8-abb8-4999-9c92-d57cba77164b\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:5;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1224;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:7:\\\"creator\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:56:\\\"Dokumen PPA-ADRO-IK-ICTMD-01 disetujui dan kini Berlaku.\\\";s:4:\\\"icon\\\";s:15:\\\"bi-check-circle\\\";s:9:\\\"routeName\\\";s:14:\\\"documents.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"b45bd0ab-0510-4a4c-8e3c-6ed50bd29d26\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788243426,\"delay\":null}',0,NULL,1788243426,1788243426),(169,'default','{\"uuid\":\"6e369c54-13c6-47d4-8ae8-38b431e8f29f\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:5;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1243;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"type\\\";i:1;s:15:\\\"revisesDocument\\\";i:2;s:7:\\\"creator\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:57:\\\"Dokumen PPA-ADRO-SOP-ICTMD-10 disetujui dan kini Berlaku.\\\";s:4:\\\"icon\\\";s:15:\\\"bi-check-circle\\\";s:9:\\\"routeName\\\";s:14:\\\"documents.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"6b63f080-02f8-45b3-85c3-d9ddb54e7ab2\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788662601,\"delay\":null}',0,NULL,1788662601,1788662601),(170,'default','{\"uuid\":\"49d14b93-1320-41d5-a34e-a19fdd1ae950\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1244;s:9:\\\"relations\\\";a:4:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:15:\\\"revisesDocument\\\";i:3;s:8:\\\"reviewer\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:45:\\\"Dokumen PPA-ADRO-SOP-ICTMD-01 perlu ditinjau.\\\";s:4:\\\"icon\\\";s:18:\\\"bi-clipboard-check\\\";s:9:\\\"routeName\\\";s:11:\\\"review.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"d69eb42c-2771-4281-a1d5-282633c4272f\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788662915,\"delay\":null}',0,NULL,1788662915,1788662915),(171,'default','{\"uuid\":\"2a6ab3ab-4392-40f3-98b2-0af4cdaa987d\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:15;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1244;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"approver\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:46:\\\"Dokumen PPA-ADRO-SOP-ICTMD-01 perlu disetujui.\\\";s:4:\\\"icon\\\";s:14:\\\"bi-patch-check\\\";s:9:\\\"routeName\\\";s:14:\\\"approvals.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"9c0e5a83-e932-4036-a646-1a6390eb1414\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788663139,\"delay\":null}',0,NULL,1788663139,1788663139),(172,'default','{\"uuid\":\"16e3d0d2-03ce-4cfa-867c-fd9c2d33057c\",\"displayName\":\"App\\\\Notifications\\\\DocumentNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":4:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:5;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:38:\\\"App\\\\Notifications\\\\DocumentNotification\\\":7:{s:8:\\\"document\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Document\\\";s:2:\\\"id\\\";i:1244;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"type\\\";i:1;s:10:\\\"department\\\";i:2;s:7:\\\"creator\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";s:57:\\\"Dokumen PPA-ADRO-SOP-ICTMD-01 disetujui dan kini Berlaku.\\\";s:4:\\\"icon\\\";s:15:\\\"bi-check-circle\\\";s:9:\\\"routeName\\\";s:14:\\\"documents.show\\\";s:7:\\\"penting\\\";b:1;s:7:\\\"catatan\\\";N;s:2:\\\"id\\\";s:36:\\\"2eafd2cc-5318-4d88-b869-32d0f8c2a328\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:10:\\\"connection\\\";s:8:\\\"database\\\";}\",\"batchId\":null},\"createdAt\":1788663245,\"delay\":null}',0,NULL,1788663245,1788663245);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_07_12_082503_create_permission_tables',1),(5,'2026_07_12_090001_create_departments_table',1),(6,'2026_07_12_090002_create_document_types_table',1),(7,'2026_07_12_090003_create_documents_table',1),(8,'2026_07_12_090004_create_document_contents_table',1),(9,'2026_07_12_090005_create_document_versions_table',1),(10,'2026_07_12_090006_create_reviews_table',1),(11,'2026_07_12_090007_create_approvals_table',1),(12,'2026_07_12_090008_create_attachments_table',1),(13,'2026_07_12_090009_create_audit_logs_table',1),(14,'2026_07_12_090010_create_notifications_table',1),(15,'2026_07_12_110000_create_document_authors_table',1),(16,'2026_07_13_100001_create_attachment_comments_table',1),(17,'2026_07_31_161340_create_personal_access_tokens_table',1),(18,'2026_08_01_090000_create_document_feedback_table',1),(19,'2026_08_01_093000_add_pending_md_status_to_documents',1),(20,'2026_08_01_094000_add_ai_review_enabled_to_users',1),(21,'2026_08_03_080000_create_job_executions_table',1),(22,'2026_08_03_080001_create_job_checklist_items_table',1),(23,'2026_08_03_090000_rename_pending_md_to_verifikasi_md',1),(24,'2026_08_03_100000_create_user_off_days_table',1),(25,'2026_08_04_100000_add_verdict_to_review_annotations',1),(26,'2026_08_05_100000_create_document_reads_table',1),(27,'2026_08_06_090000_drop_doc_number_temp_from_documents',1),(30,'2026_08_10_090000_rename_sp_standar_parameter',2),(31,'2026_08_12_090000_add_arsip_path_to_documents_table',3),(32,'2026_08_12_100000_create_informasi_table',4),(33,'2026_08_13_090000_tambah_kelas_unggahan_document_types',5),(34,'2026_08_19_100000_create_informasi_reads_table',6),(35,'2026_08_20_090000_add_nonaktif_berjenjang_to_documents',7),(36,'2026_08_20_090100_add_kind_to_approvals',7),(37,'2026_08_21_090000_add_arsip_path_asli_to_documents',8),(38,'2026_08_25_090000_create_access_profiles_table',9),(39,'2026_08_25_100000_create_document_feedback_antargl_table',10),(40,'2026_08_26_090000_create_pengaturan_table',11),(41,'2026_08_26_100000_add_is_active_to_departments_table',12),(42,'2026_08_26_100100_create_informasi_kategori_table',12),(43,'2026_09_02_090000_add_salin_arsip_at_to_documents',13);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',15),(3,'App\\Models\\User',11),(3,'App\\Models\\User',12),(3,'App\\Models\\User',14),(3,'App\\Models\\User',698),(3,'App\\Models\\User',703),(3,'App\\Models\\User',704),(4,'App\\Models\\User',5),(4,'App\\Models\\User',6),(4,'App\\Models\\User',7),(4,'App\\Models\\User',8),(4,'App\\Models\\User',9),(4,'App\\Models\\User',10),(4,'App\\Models\\User',667),(4,'App\\Models\\User',668),(4,'App\\Models\\User',697),(4,'App\\Models\\User',699),(4,'App\\Models\\User',700),(4,'App\\Models\\User',701),(4,'App\\Models\\User',702),(4,'App\\Models\\User',705),(4,'App\\Models\\User',706),(4,'App\\Models\\User',707),(4,'App\\Models\\User',708),(4,'App\\Models\\User',709),(4,'App\\Models\\User',710),(4,'App\\Models\\User',711),(4,'App\\Models\\User',712),(4,'App\\Models\\User',713),(4,'App\\Models\\User',714),(4,'App\\Models\\User',715),(4,'App\\Models\\User',716),(4,'App\\Models\\User',717),(4,'App\\Models\\User',718),(4,'App\\Models\\User',719),(4,'App\\Models\\User',720),(4,'App\\Models\\User',721),(4,'App\\Models\\User',722),(4,'App\\Models\\User',723),(4,'App\\Models\\User',724),(4,'App\\Models\\User',725),(4,'App\\Models\\User',726),(4,'App\\Models\\User',727),(4,'App\\Models\\User',728),(4,'App\\Models\\User',729),(4,'App\\Models\\User',730),(4,'App\\Models\\User',731),(4,'App\\Models\\User',732),(4,'App\\Models\\User',733),(4,'App\\Models\\User',734),(4,'App\\Models\\User',735),(4,'App\\Models\\User',736),(4,'App\\Models\\User',737),(4,'App\\Models\\User',738),(4,'App\\Models\\User',739),(4,'App\\Models\\User',740),(4,'App\\Models\\User',741),(4,'App\\Models\\User',742),(4,'App\\Models\\User',743),(4,'App\\Models\\User',744),(4,'App\\Models\\User',745),(4,'App\\Models\\User',746),(4,'App\\Models\\User',747),(4,'App\\Models\\User',748),(4,'App\\Models\\User',749),(4,'App\\Models\\User',750),(4,'App\\Models\\User',751),(4,'App\\Models\\User',752),(4,'App\\Models\\User',753),(4,'App\\Models\\User',754),(4,'App\\Models\\User',755),(4,'App\\Models\\User',756),(4,'App\\Models\\User',757),(4,'App\\Models\\User',758),(4,'App\\Models\\User',759),(4,'App\\Models\\User',760),(4,'App\\Models\\User',761),(4,'App\\Models\\User',762),(4,'App\\Models\\User',763),(4,'App\\Models\\User',764),(4,'App\\Models\\User',765),(4,'App\\Models\\User',766),(4,'App\\Models\\User',767),(4,'App\\Models\\User',768),(4,'App\\Models\\User',769),(4,'App\\Models\\User',770),(4,'App\\Models\\User',771),(4,'App\\Models\\User',772),(4,'App\\Models\\User',773),(4,'App\\Models\\User',774),(4,'App\\Models\\User',775),(4,'App\\Models\\User',776),(4,'App\\Models\\User',777),(4,'App\\Models\\User',778),(4,'App\\Models\\User',779),(4,'App\\Models\\User',780),(4,'App\\Models\\User',781),(4,'App\\Models\\User',782),(4,'App\\Models\\User',783),(4,'App\\Models\\User',784),(4,'App\\Models\\User',785),(4,'App\\Models\\User',786),(4,'App\\Models\\User',787),(4,'App\\Models\\User',788),(4,'App\\Models\\User',789),(4,'App\\Models\\User',791),(4,'App\\Models\\User',792),(4,'App\\Models\\User',793),(4,'App\\Models\\User',794),(4,'App\\Models\\User',795),(6,'App\\Models\\User',16),(6,'App\\Models\\User',666),(6,'App\\Models\\User',796),(7,'App\\Models\\User',13),(7,'App\\Models\\User',790),(8,'App\\Models\\User',2);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('2eafd2cc-5318-4d88-b869-32d0f8c2a328','App\\Notifications\\DocumentNotification','App\\Models\\User',5,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:54:05','2026-09-06 02:54:05'),('383ea618-1ad1-4d99-b079-4f2959af9e98','App\\Notifications\\DocumentNotification','App\\Models\\User',5,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 lolos Management Development \\u2014 menunggu persetujuan.\",\"icon\":\"bi-check2\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:52:19','2026-09-06 02:52:19'),('3dd3c441-ba3b-42b5-b014-03b2d3b865ec','App\\Notifications\\DocumentNotification','App\\Models\\User',2,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 perlu ditinjau Management Development.\",\"icon\":\"bi-spellcheck\",\"route\":\"review.md.show\"}',NULL,'2026-09-06 02:51:52','2026-09-06 02:51:52'),('6b63f080-02f8-45b3-85c3-d9ddb54e7ab2','App\\Notifications\\DocumentNotification','App\\Models\\User',5,'{\"document_id\":1243,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"SOP Produksi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-10 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:43:21','2026-09-06 02:43:21'),('6c0bee89-2c84-4386-9ab4-10e6b4466028','App\\Notifications\\DocumentNotification','App\\Models\\User',16,'{\"document_id\":1243,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"SOP Produksi\",\"message\":\"Dokumen baru Berlaku di departemen Anda: PPA-ADRO-SOP-ICTMD-10 \\u2014 SOP Produksi.\",\"icon\":\"bi-folder-check\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:43:22','2026-09-06 02:43:22'),('72952a42-131b-457e-b6c9-78d9eac0d4c9','App\\Notifications\\DocumentNotification','App\\Models\\User',666,'{\"document_id\":1243,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"SOP Produksi\",\"message\":\"Dokumen baru Berlaku di departemen Anda: PPA-ADRO-SOP-ICTMD-10 \\u2014 SOP Produksi.\",\"icon\":\"bi-folder-check\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:43:22','2026-09-06 02:43:22'),('8f9f3ac7-1e77-4002-9174-3ddc067337e1','App\\Notifications\\DocumentNotification','App\\Models\\User',666,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen baru Berlaku di departemen Anda: PPA-ADRO-SOP-ICTMD-01 \\u2014 adwsl;.\",\"icon\":\"bi-folder-check\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:54:05','2026-09-06 02:54:05'),('9c0e5a83-e932-4036-a646-1a6390eb1414','App\\Notifications\\DocumentNotification','App\\Models\\User',15,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.show\"}',NULL,'2026-09-06 02:52:19','2026-09-06 02:52:19'),('b995e248-36db-400b-a52f-0455fb4e1d92','App\\Notifications\\DocumentNotification','App\\Models\\User',2,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 yang Anda loloskan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:54:05','2026-09-06 02:54:05'),('c5405661-dc8e-4fb2-a440-eb279194090a','App\\Notifications\\DocumentNotification','App\\Models\\User',11,'{\"document_id\":1242,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"SOP Produksi\",\"message\":\"Dokumen lama PPA-ADRO-SOP-ICTMD-10 \\u2014 SOP Produksi didaftarkan ANGGA MARGI SAPUTRO dan langsung Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:39:30','2026-09-06 02:39:30'),('c7e52119-6ea2-4aad-a109-0f50c8beeecc','App\\Notifications\\DocumentNotification','App\\Models\\User',16,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen baru Berlaku di departemen Anda: PPA-ADRO-SOP-ICTMD-01 \\u2014 adwsl;.\",\"icon\":\"bi-folder-check\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:54:05','2026-09-06 02:54:05'),('c85b0e1f-9e7a-46ad-965b-40ed35562f84','App\\Notifications\\DocumentNotification','App\\Models\\User',5,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 lolos tinjauan \\u2014 diteruskan ke Management Development.\",\"icon\":\"bi-check2\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:51:52','2026-09-06 02:51:52'),('d69eb42c-2771-4281-a1d5-282633c4272f','App\\Notifications\\DocumentNotification','App\\Models\\User',11,'{\"document_id\":1244,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"adwsl;\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.show\"}',NULL,'2026-09-06 02:48:35','2026-09-06 02:48:35'),('d85af42b-aacb-422b-a0c9-6d8a9059a96c','App\\Notifications\\DocumentNotification','App\\Models\\User',2,'{\"document_id\":1243,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"SOP Produksi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-10 yang Anda loloskan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.show\"}',NULL,'2026-09-06 02:43:22','2026-09-06 02:43:22');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
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
-- Table structure for table `pengaturan`
--

DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengaturan` (
  `kunci` varchar(100) NOT NULL,
  `nilai` text DEFAULT NULL,
  PRIMARY KEY (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan`
--

LOCK TABLES `pengaturan` WRITE;
/*!40000 ALTER TABLE `pengaturan` DISABLE KEYS */;
INSERT INTO `pengaturan` VALUES ('ai.cadangan.key','eyJpdiI6IkFGc2tjZ25XUzdvc3VXRDRwbXhxalE9PSIsInZhbHVlIjoibUh3VE8rZXVVQ3lrU0pXNnhDNW1zUTJHK2gxRlFyUmNoeHRwY0tqMmlzUzQxQ2QxTzJhWVdmaGI0S3BYTndHRmk5Wit2d254enpYalNhUnBCNmgwN2hwdHNrRERkMWIzMkVXVHowbS9Jc0U9IiwibWFjIjoiODNmNDE5YzE4YmExYTdjNzU5NTM1ZGI0NWRjN2JiMzkzODMxMmQ1MGY5NDQxNDk3YjRjODJhNTcxNjkzNTE5MSIsInRhZyI6IiJ9'),('ai.cadangan.model','nvidia/nemotron-3-super-120b-a12b:free'),('ai.cadangan.provider','openrouter'),('ai.enabled','1'),('ai.key','eyJpdiI6InhHeVA5QXRSekh1MThyaGlTT3hVR1E9PSIsInZhbHVlIjoiYmJWZUw5Sm9xOHMxYW4rSk5iZ280NUxmc1c5SzlBUnU5bnpSWkc2d0lObmdSSDVrYTZDMTJWQ3FJT0ZDb2ZLK1QvZSszM1R5QjhyWUoxY2JMRjdrRXBMcVRHTm04YUhWRFozSlRBc3dvMFE9IiwibWFjIjoiZTU3MjEyZDdiZjljYjhiYTIwMGYyNzk3ZWNiZWQ5OTRmODhmODEzNzRiMzBjMWYzMDVjMjJiNGQ5MTc4ZjVmNiIsInRhZyI6IiJ9'),('ai.model','nvidia/nemotron-3-ultra-550b-a55b:free'),('ai.provider','openrouter');
/*!40000 ALTER TABLE `pengaturan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'document.create','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(2,'document.edit','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(3,'document.submit','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(4,'document.delete','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(5,'document.review','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(6,'document.approve','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(7,'document.publish','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(8,'document.view_department','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(9,'document.view_scope','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(10,'document.view_all','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(11,'user.manage','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(12,'user.approve_registration','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(13,'user.create_staff','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(14,'document.change_status','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(15,'audit.view','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(16,'document.request_revision','web','2026-07-11 22:36:15','2026-07-11 22:36:15'),(17,'document.review_jsa','web','2026-07-28 05:23:14','2026-07-28 05:23:14'),(18,'document.review_md','web','2026-08-01 00:08:55','2026-08-01 00:08:55'),(19,'informasi.manage','web','2026-08-12 04:39:01','2026-08-12 04:39:01'),(20,'document.feedback_respond','web','2026-08-19 04:49:56','2026-08-19 04:49:56');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_annotations`
--

DROP TABLE IF EXISTS `review_annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review_annotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `item_ref` varchar(255) DEFAULT NULL,
  `verdict` enum('sesuai','perlu_revisi') DEFAULT NULL,
  `severity` enum('info','minor','major','critical') NOT NULL DEFAULT 'minor',
  `comment` text DEFAULT NULL,
  `ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `ai_adopted` tinyint(1) NOT NULL DEFAULT 0,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_annotations_review_id_foreign` (`review_id`),
  KEY `review_annotations_section_key_index` (`section_key`),
  CONSTRAINT `review_annotations_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_annotations`
--

LOCK TABLES `review_annotations` WRITE;
/*!40000 ALTER TABLE `review_annotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_annotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `revision_round` int(10) unsigned NOT NULL DEFAULT 0,
  `decision` enum('pending','approved','needs_revision') NOT NULL DEFAULT 'pending',
  `summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_document_id_foreign` (`document_id`),
  KEY `reviews_reviewer_id_index` (`reviewer_id`),
  CONSTRAINT `reviews_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (134,1244,11,0,'approved',NULL,'2026-09-06 02:51:52','2026-09-06 02:51:52'),(135,1244,2,0,'approved','[Management Development] Sistematika penulisan sesuai.','2026-09-06 02:52:19','2026-09-06 02:52:19');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,4),(2,1),(2,4),(3,1),(3,4),(4,1),(4,4),(5,1),(5,3),(5,7),(6,1),(6,2),(6,3),(6,7),(7,1),(7,2),(8,1),(8,3),(8,4),(8,6),(8,7),(9,1),(10,1),(10,2),(10,8),(11,1),(12,1),(12,2),(12,3),(12,4),(12,7),(13,1),(14,1),(15,1),(15,2),(15,3),(15,4),(15,7),(15,8),(16,1),(16,4),(16,8),(17,1),(17,4),(18,1),(18,8),(19,1),(19,4),(20,1),(20,4),(20,8);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin_it','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(2,'pimpinan','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(3,'section_head','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(4,'group_leader','web','2026-07-11 16:37:14','2026-07-11 16:37:14'),(6,'staff','web','2026-07-11 22:36:16','2026-07-11 22:36:16'),(7,'departemen_head','web','2026-07-15 06:29:48','2026-07-15 06:29:48'),(8,'management_development','web','2026-08-01 00:08:56','2026-08-01 00:08:56');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
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
INSERT INTO `sessions` VALUES ('3pspAprEGzysgLnGq0oFURIgcPdEWqMCaWQbSKQ6',15,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQkZLVHlBUXMzUnVpb2RXUmtqZFRma0M4TnVHQzU5ZHh4bmcxNDduVSI7czo1OiJ1aV92MiI7YjoxO3M6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE1O3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQwOiJodHRwOi8vMTI3LjAuMC4xOjkwOTIvZG9jdW1lbnRzLzEyNDQvcGRmIjtzOjU6InJvdXRlIjtzOjEzOiJkb2N1bWVudHMucGRmIjt9fQ==',1788663249);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_off_days`
--

DROP TABLE IF EXISTS `user_off_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_off_days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `jenis` enum('cuti','off_day','dinas_luar') NOT NULL DEFAULT 'cuti',
  `mulai` date NOT NULL,
  `sampai` date NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_off_days_user_id_mulai_sampai_index` (`user_id`,`mulai`,`sampai`),
  CONSTRAINT `user_off_days_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_off_days`
--

LOCK TABLES `user_off_days` WRITE;
/*!40000 ALTER TABLE `user_off_days` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_off_days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `nrp` varchar(255) DEFAULT NULL,
  `jabatan` varchar(255) DEFAULT NULL,
  `jabatan_diajukan` varchar(100) DEFAULT NULL,
  `nomor_hp` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `access_profile_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `ai_review_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_nrp_unique` (`nrp`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_department_id_index` (`department_id`),
  KEY `users_status_index` (`status`),
  KEY `users_access_profile_id_foreign` (`access_profile_id`),
  CONSTRAINT `users_access_profile_id_foreign` FOREIGN KEY (`access_profile_id`) REFERENCES `access_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=799 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin IT',NULL,'ADM-0001',NULL,NULL,NULL,NULL,NULL,'2026-08-10 04:10:10','$2y$12$hH4ZS0hzRjuMYoffTMUfCOvzjKVj3cowUVecVSzG9DRzQedPMatnK',5,NULL,'active',1,NULL,'2026-07-18 00:14:18','2026-08-29 01:05:43',NULL),(2,'Management Development',NULL,'MD-0001',NULL,NULL,NULL,NULL,NULL,'2026-08-10 04:10:11','$2y$12$3WWM4gBpUeZcDVbwQjcwH.bCJlXHiF950CvTgNBH1VYtjq0XFEPfG',5,NULL,'active',1,NULL,'2026-08-09 06:53:39','2026-08-10 04:10:11',NULL),(5,'ANGGA MARGI SAPUTRO',NULL,'18064116','group_leader',NULL,'085290431155','ajipraja174@gmail.com','avatars/XxOyhoLwlB6rLsU5diuTc6CpEBtAgPVphUz1gv9A.jpg',NULL,'$2y$12$8W4ycq2jVIe.5yTeBeDSD.XZZLz5RjdviIr2ovWSBv99lZLRKQb3q',5,1,'active',1,NULL,'2026-08-09 13:54:16','2026-08-25 06:19:01',NULL),(6,'RIZKI DWI SAFITRI',NULL,'23002774','group_leader',NULL,'085225882141',NULL,NULL,NULL,'$2y$12$KFB4IuWbBcu.t./3sp2Ub.85BYnOGBqQ0QMplQYmfML0vVRE.pWuW',7,NULL,'active',1,NULL,'2026-08-09 13:55:40','2026-08-09 13:55:40',NULL),(7,'MARCIO CALVIN ROHI',NULL,'23000651','group_leader',NULL,'081237767013','marcio.calvin@ppa.co.id','avatars/oe26KLfoQ3QfyiwEscCCFEJ5L9qjNB9SyvZG8CpU.jpg',NULL,'$2y$12$PCJK20AuqQelzStpOOreguzHfXYzkhApIebeGAWf6e9thaBHUwVHu',1,NULL,'active',1,NULL,'2026-08-09 13:56:35','2026-08-22 06:25:24',NULL),(8,'RENDY ABRAHAM DOMIS',NULL,'22004759','group_leader',NULL,'085348493635','rendy.abraham@ppa.co.id',NULL,NULL,'$2y$12$p3dVijGfuLE89GLvqvCfgO//vL/wtMBATPsq0dJqkj7VD40ShvxqO',2,NULL,'active',1,NULL,'2026-08-09 13:57:39','2026-08-09 13:57:39',NULL),(9,'IRWAN ADI PUTRA',NULL,'18074307','group_leader',NULL,'081253136793','irwanputra@ppa.cp.id',NULL,NULL,'$2y$12$cHJgMTDsUPH8KSeQkoIBde96gW5i7fYbH0YtKAi3CIhFkT05ZOyKa',6,NULL,'active',1,NULL,'2026-08-09 13:58:35','2026-08-09 13:58:35',NULL),(10,'MUHAMMAD RIZAL ASSIDDIQ',NULL,'23002780','group_leader',NULL,'089522407542','assiddiq.rizal@ppa.co.id',NULL,NULL,'$2y$12$QUT4x5t2i5BKH2HXBNBzkuQN5lZofUyVNbl8t91lThA0QI2lhsMca',6,NULL,'active',1,NULL,'2026-08-09 14:00:35','2026-08-09 14:00:35',NULL),(11,'ARISAL FARZAN',NULL,'17021841','section_head',NULL,'082154062521','anggunsafira471@gmail.com','avatars/gQbCUylO8Px2wIMbUKsNN9BGuDkIxlsEyc1czKoB.jpg',NULL,'$2y$12$KLnv0zAlhUB.OImAkrGaYOdCyaVFRuwqLb0OIYYkBweFOWrjgsaaq',5,NULL,'active',1,'qfUQ0fCnmOnk66b2dieh7zSoOku75Bb6C9Y1LY0LJAqmVhUxUdc5yHUYhfAi','2026-08-09 14:02:05','2026-08-19 06:30:48',NULL),(12,'DEDEN DISA ABDULLAH',NULL,'18043829','section_head',NULL,'082317050394',NULL,NULL,NULL,'$2y$12$13W43inaFks2JwoTD8N/9.V3HT88foF9ioUU5OkIelNqrZzfml1vm',1,NULL,'active',1,NULL,'2026-08-09 14:04:05','2026-08-09 14:04:05',NULL),(13,'SYAFIQ ABDULLAH',NULL,'17092728','departemen_head',NULL,'081320659262','syafiq.abdulla@ppa.co.id',NULL,NULL,'$2y$12$CMNS2MLBGdD8RJSs.B976O97ZZhP6MCydGl4ch7yq3VT.iKLpu5HO',7,NULL,'active',1,NULL,'2026-08-09 14:06:36','2026-08-09 14:06:36',NULL),(14,'MIFTAQUL ILMI',NULL,'18043807','section_head',NULL,'081351963974','miftaqulilmi@ppa.co.id',NULL,NULL,'$2y$12$/8fsFZnCgXXWp7iRiC8JbOCE4xH6VCDYdml/QhMnCprmEqkxI6OrW',2,NULL,'active',1,NULL,'2026-08-09 14:08:26','2026-08-09 14:08:26',NULL),(15,'WAHYU BINUKO',NULL,'16071367','pimpinan',NULL,'082151549915','wahyu.binuko@ppa.co.id','avatars/jco9opNVmcAU6HUrjrD2TBNqPsZA6POrUpFHmJEt.jpg',NULL,'$2y$12$vRrUIyodJNJB/fjm8CZvc.1GbOI6H4yqgTMGt8m8J6ruT8PoIgE2S',NULL,NULL,'active',1,NULL,'2026-08-09 14:10:33','2026-08-22 05:38:38',NULL),(16,'BAYU REZKY RAMADHAN',NULL,'250504','staff','NON STAFF','085348493635','bayu221200@gmail.com',NULL,NULL,'$2y$12$0ER6pmAPyAu7kC4vcUm/dewc4JicXwF3wGzb8i9lGxmaWPsg4CWwq',5,NULL,'active',1,NULL,'2026-08-09 14:13:20','2026-08-09 14:38:03',NULL),(666,'Muhammad Surya aji Praja',NULL,'20260219','staff',NULL,'085753097927','ajipraja471@gmail.com','avatars/H3lAgczg25jSDr8ePSOgU51YvfPu1HrGfpVP37KX.jpg',NULL,'$2y$12$pYwjSNwpihnA9tbAWneAeO5xizGzxMNMKpkhGS8AXN0qhz1LmHZ56',5,NULL,'active',1,NULL,'2026-08-13 03:26:02','2026-08-13 03:26:36',NULL),(667,'GL_KEDUA',NULL,'GLSHE-0001','group_leader',NULL,'08123456789','glshe@gmail.com',NULL,NULL,'$2y$12$.0U.HJ4t6e6IoGsM9vZSnOAg51DkzKgODkaPwm9IEXaAPQcRzyiq2',1,NULL,'active',1,NULL,'2026-08-19 05:57:59','2026-08-26 03:53:01','2026-08-26 03:53:01'),(668,'T.M.FATHIN RIFAT',NULL,'24006332','group_leader',NULL,'085753097927','fathin@gmail.com',NULL,NULL,'$2y$12$y17o1anE3oiMbOTVyPyRqeqOcjcBDLPWmafjmumBuecPi3ywS6/0e',5,NULL,'active',1,NULL,'2026-08-22 05:25:52','2026-08-22 05:25:52',NULL),(697,'AJI PAKERTI HANDIAARTO PUTRA',NULL,'21000860','group_leader',NULL,'087887636626','ajipakerti@amm.co.id',NULL,NULL,'$2y$12$9s.Mfzcl6cYXwXh0Al7kceMAsEHrzFJto6X/rWyt8JUbt3P81jpzS',2,NULL,'active',1,NULL,'2026-08-15 08:11:41','2026-08-15 08:11:41',NULL),(698,'RICKY FADLIANDA',NULL,'22000610','section_head',NULL,'082328199908','ricky.fadlianda@ppa.co.id',NULL,NULL,'$2y$12$.2WCZSli1yRo7V2egTtiI.RtRQFwlPlHwa/jHrK772EZMngiGDY0y',7,NULL,'active',1,NULL,'2026-08-15 11:07:47','2026-08-15 11:07:47',NULL),(699,'RACHMAT FACHRUDDIN',NULL,'20000701','group_leader',NULL,'085250953131','rachmat.fachruddin@ppa.co.id',NULL,NULL,'$2y$12$14KCSQoZOFnPLvIaQAlqye4/5/lCcnu7xdH3FW/YvSGSyKk7UqTr.',4,NULL,'active',1,NULL,'2026-08-15 11:08:46','2026-08-15 11:08:46',NULL),(700,'HERFIT ALMIYA',NULL,'22001871','group_leader',NULL,'081254521969','herfit.almiyah@ppa.co.id',NULL,NULL,'$2y$12$c.1nwKjuL6xUoGe/HueU8eBfSBFhBF0zQ/UtA.g.2nlSNU/GwyN5K',3,NULL,'active',1,NULL,'2026-08-15 11:11:24','2026-08-15 11:11:24',NULL),(701,'KARTIKA HARYO KUSUMO',NULL,'22005011','group_leader',NULL,'082237218931','kartikaharyokusumo@ppa.co.id',NULL,NULL,'$2y$12$cZOCALGUqwq.EUQyT44rQO1aM7psrqM7vIpr2gOE/ciY6UPViTbWC',7,NULL,'active',1,NULL,'2026-08-15 11:12:37','2026-08-15 11:12:37',NULL),(702,'DWI ANANDA ARDIANTO',NULL,'21000718','group_leader',NULL,'082115735515','dwianandaa21@ppa.co.id',NULL,NULL,'$2y$12$uyFfnLfI/B3aacp3uvg7auX1L0AHI70.Y04aY7l4UNYM.i25OzZLu',6,NULL,'active',1,NULL,'2026-08-15 11:16:12','2026-08-15 11:16:12',NULL),(703,'MOHAMMAD IRKHAS KARIMULLAH',NULL,'18125700','section_head',NULL,'081234257401','mohamad.irkhas@ppa.co.id',NULL,NULL,'$2y$12$zgS9ieHLy7GRd9X7Lmt0YO8wk32WObOFvUiNTyqMVjduKY/5z4xZe',1,NULL,'active',1,NULL,'2026-08-24 08:28:33','2026-08-24 08:28:33',NULL),(704,'MUAMMER KHADAFI',NULL,'16081467','section_head',NULL,'082192129484','khadafi@ppa.co.id',NULL,NULL,'$2y$12$KiEQR7SEDX8leIgr0h8SdOdIdVy8vucnoDwiQn.8osYycm6/UlIe.',1,NULL,'active',1,NULL,'2026-08-24 08:30:30','2026-08-24 08:30:30',NULL),(705,'KURNIAWAN HAJRIYANTO',NULL,'22005129','group_leader',NULL,'081227914789','kurniawan.h@ppa.co.id',NULL,NULL,'$2y$12$w0Rsl8SM7t65u/.PyQ.pAe4OvPHSET0SQkZXuJcm8KbcBTmOWTeHi',7,NULL,'active',1,NULL,'2026-08-25 05:40:48','2026-08-25 05:40:48',NULL),(706,'ZAIDAN JAUHARI',NULL,'22001948','group_leader',NULL,'081255465602','zulidanjauhary@ppa.co.id',NULL,NULL,'$2y$12$rSGPUSV.NaYVaxTXjvtWLuqjtwKAq7J1DNJGvr8jzaaWWZuTbW3w6',7,NULL,'active',1,NULL,'2026-08-25 05:41:21','2026-08-25 05:41:21',NULL),(707,'DAVID OKTATIASA',NULL,'22005270','group_leader',NULL,'081251407427','david.oktatiasa@ppa.co.id',NULL,NULL,'$2y$12$rgD3YLEACpjHY3fLAz4wrOuY9knnY1ILO2UxRhFbtEn2YFeq9BdcS',7,NULL,'active',1,NULL,'2026-08-25 05:41:54','2026-08-25 05:41:54',NULL),(708,'BRILLIAN ISTANA AUDIO',NULL,'23002773','group_leader',NULL,'081359107816','brilliandio@ppa.co.id',NULL,NULL,'$2y$12$b8afCjyqfjn3j7As3cHDUeJwk0abBv7wcJL23bmylP3TE7Xm5QwBm',7,NULL,'active',1,NULL,'2026-08-25 05:43:00','2026-08-25 05:43:00',NULL),(709,'PASCAL YUSDA ADITAMA',NULL,'24006336','group_leader',NULL,'087878837874','pascalyusda.aditama@ppa.co.id',NULL,NULL,'$2y$12$1oc1P.i8JMJ4YJchP6612er4l0QJnfPPy6aeTL9.hB2nt5Ryz6N3u',7,NULL,'active',1,NULL,'2026-08-25 05:43:31','2026-08-25 05:43:31',NULL),(710,'FEBRI RAMADHAN',NULL,'16041186','group_leader',NULL,'085248803233','febriramadhan@ppa.co.id',NULL,NULL,'$2y$12$BQt/WSjAxekfuxS60vsunu0FNfoDEO1xb.TC3Kd0B4IBqvOXQpt0G',7,NULL,'active',1,NULL,'2026-08-25 05:44:03','2026-08-25 05:44:03',NULL),(711,'ARIE JULMIDAR',NULL,'16111658','group_leader',NULL,'085273059879','arie.julmidar@ppa.co.id',NULL,NULL,'$2y$12$fxFfNQ58M/qm7LVuF3OIWeBiJXJjw3BXHCtaHKGQ3riCQrkddmFD6',4,NULL,'active',1,NULL,'2026-08-25 05:44:49','2026-08-25 05:44:49',NULL),(712,'MUHAMAT RAHMADONI',NULL,'22004619','group_leader',NULL,'085752869470','Rahma.doni@ppa.co.id',NULL,NULL,'$2y$12$LDh4r/nyRHY0xuND9wsBEuVr2yzm99N/u8r6NmluNoaRCBTX4BXO.',3,NULL,'active',1,NULL,'2026-08-25 05:45:39','2026-08-25 05:45:39',NULL),(713,'ARIEF AHMAD',NULL,'23002769','group_leader',NULL,'082226063100','arief.ahmad@ppa.co.id',NULL,NULL,'$2y$12$PLx1uDH4qWcw/VE1ThpY5.52pul1ePDv6AwVccqAhnKDUHHd0q/Ou',3,NULL,'active',1,NULL,'2026-08-25 05:46:10','2026-08-25 05:46:10',NULL),(714,'SLAMET HUDA FIRMANSYAH',NULL,'18125648','group_leader',NULL,'081252834817','slamethuda.firmansyah@ppa.co.id',NULL,NULL,'$2y$12$41UFvXf0kbI6ToaVcClhC.TnRY8fUBdiYh/EekqGYyaXWMGc4v18.',5,NULL,'active',1,NULL,'2026-08-25 05:47:36','2026-08-25 05:47:36',NULL),(715,'YULIA PUTRIANA',NULL,'23002779','group_leader',NULL,'085338129972','yulia.putriana@ppa.co.id',NULL,NULL,'$2y$12$Wb2vU2FngzZslyzTYu6YGuEpDCB48myt8adBl7bpb.2ulzOl0Bu6i',1,NULL,'active',1,NULL,'2026-08-25 05:49:07','2026-08-25 05:49:07',NULL),(716,'DEVINDO ALMIRA VITO',NULL,'18125630','group_leader',NULL,'085725084142','devindo@ppa.co.id',NULL,NULL,'$2y$12$E.m6RFfuep7c.7hn2FPvw.vJPuVu.OYX2mbMgwUC/mMylA4zIIHoC',2,NULL,'active',1,NULL,'2026-08-25 05:49:52','2026-08-25 05:49:52',NULL),(717,'DEDE ISTIAN',NULL,'14100851','group_leader',NULL,'082251252977','distian41@gmail.com',NULL,NULL,'$2y$12$ojt8IxwaqTmTW3F49nAHOeaQBk6Cr2v1edHwVoyrPBhi4fRP3plHm',6,NULL,'active',1,NULL,'2026-08-25 05:50:58','2026-08-25 05:50:58',NULL),(718,'SUNARYO',NULL,'14110760','group_leader',NULL,'081259763535','sunaryosragenn@gmail.com',NULL,NULL,'$2y$12$dP6qXIsJ52bOMoo692ZsPuapGCXfYniec0Lj9mPYbO8XqCbEYgxOG',6,NULL,'active',1,NULL,'2026-08-25 05:51:28','2026-08-25 05:51:28',NULL),(719,'YULIUS TANGGO KADANG',NULL,'15060939','group_leader',NULL,'085341472136','yuliustanggokadang@gmail.com',NULL,NULL,'$2y$12$X8WDFygxpL6mE.9LKDYm8uaFCulYjSgTOWHuCzlEBQ4zuBgogbNp6',6,NULL,'active',1,NULL,'2026-08-25 05:51:55','2026-08-25 05:51:55',NULL),(720,'HENDRO DARMAWANTO',NULL,'15111113','group_leader',NULL,'081357883211','hendrodarmawanto@gmail.com',NULL,NULL,'$2y$12$QL8WW1TEvbcbt0zOSByuSeHs0gKl/TAfsNiQ6wLuL/8SxhMgZ2WKC',6,NULL,'active',1,NULL,'2026-08-25 05:52:56','2026-08-25 05:52:56',NULL),(721,'RIMBA WAHYUDI',NULL,'16101567','group_leader',NULL,'081346287954','rimbha1105@gmail.com',NULL,NULL,'$2y$12$L11heSSTqBzAKbst5/GVJOxxfGF9e0QZ1jQu.Dr9rPccea/eNdmqS',6,NULL,'active',1,NULL,'2026-08-25 06:04:31','2026-08-25 06:04:31',NULL),(722,'BAYU TRISWANTO',NULL,'17052250','group_leader',NULL,'081253326531','bayutriswanto86@gmail.com',NULL,NULL,'$2y$12$zU0fO2PrXyG1pNqX9fsHv.06gxgmIv/ZERQNoAHYwDoSWpSVjKHWu',6,NULL,'active',1,NULL,'2026-08-25 06:05:21','2026-08-25 06:05:21',NULL),(723,'YUDIANTO',NULL,'17082610','group_leader',NULL,'085389579136','antoyudi43177@gmail.com',NULL,NULL,'$2y$12$qK99oKfQTYg0oKd.8fQXBOkjVKgbhjktpBf2jQWt72MVb4B/eqjjS',6,NULL,'active',1,NULL,'2026-08-25 06:05:55','2026-08-25 06:05:55',NULL),(724,'TRI JOKO',NULL,'17102901','group_leader',NULL,'081393781553','trijoko1992@gmail.com',NULL,NULL,'$2y$12$X9Suceqnsg.CIbzRg.YSGepUA0w1DJEB6oXny1AgyHSpyMRHqejDO',6,NULL,'active',1,NULL,'2026-08-25 06:06:27','2026-08-25 06:06:27',NULL),(725,'SARI AHMAD WAHYUDIN',NULL,'17113017','group_leader',NULL,'085255577739','ut.yudi311@gmail.com',NULL,NULL,'$2y$12$a1CukIt.oVqoHgcwXL7Pnu78oZw7Leu1wybOszmREHD.7lst3K5YG',6,NULL,'active',1,NULL,'2026-08-25 06:07:01','2026-08-25 06:07:01',NULL),(726,'BANGKIT YULIANTO',NULL,'18105114','group_leader',NULL,'082153700165','bangkityulianto27@gmail.com',NULL,NULL,'$2y$12$0uBa215QtmNOELznVsz4POxXjivjUE8PsOirtVpdcV6E9i64IIHYi',6,NULL,'active',1,NULL,'2026-08-25 06:07:54','2026-08-25 06:07:54',NULL),(727,'ANGGA RACHMA PUTRA',NULL,'18125664','group_leader',NULL,'082154967899','angga130513@gmail.com',NULL,NULL,'$2y$12$ePbvKlxfy0GgzU2WncLv4eBo9M9Hy90kzaEiL6655tUTDfSbBfg4y',6,NULL,'active',1,NULL,'2026-08-25 06:08:18','2026-08-25 06:08:18',NULL),(728,'RIZAL PAHLAPI',NULL,'19005966','group_leader',NULL,'085220757166','pahlapirizal2@gmail.com',NULL,NULL,'$2y$12$b5IEcW4SUWy/bP9JqZqlp.wEbBFM/LyswLfIXgmtBr2tTJ41n59Km',6,NULL,'active',1,NULL,'2026-08-25 06:13:31','2026-08-25 06:13:31',NULL),(729,'FAHRIO ABRIAN INDRA LUPI',NULL,'19019140','group_leader',NULL,'081258300111','fahrioabrian7@gmail.com',NULL,NULL,'$2y$12$5DxnuWuYd75dJ6hfBm.c0.6XeAXvYENcq8Vf2biwjACmcTeQhwH.G',6,NULL,'active',1,NULL,'2026-08-25 06:14:38','2026-08-25 06:14:38',NULL),(730,'LUDI ADHARI MARLE',NULL,'19020361','group_leader',NULL,'082254747594','ludiadharimarle@gmail.com',NULL,NULL,'$2y$12$wGwwNyFIZCn61EhXnGUvC.XISPjAZ7/aotGzJWqJTVQ5kxBIOmUd2',6,NULL,'active',1,NULL,'2026-08-25 06:15:08','2026-08-25 06:15:08',NULL),(731,'SLAMET BUDIYANTO',NULL,'19020609','group_leader',NULL,'082225274811','slametbudiyanto2022@gmail.com',NULL,NULL,'$2y$12$lLr/vgSTgeK/xcRk7AXeweubCcHTD4XluJ9TiR8yERE5FjDPbwxiO',6,NULL,'active',1,NULL,'2026-08-25 06:16:25','2026-08-25 06:16:25',NULL),(732,'ISNAN FAUZY',NULL,'21002900','group_leader',NULL,'081214700724','isnanfauzy77@gmail.com',NULL,NULL,'$2y$12$DR.aSeaSc.O5GZ/p7aHd0.LGuiH9c4gzKPqS2G5aaW18Si.mnRcKK',6,NULL,'active',1,NULL,'2026-08-25 06:17:16','2026-08-25 06:17:16',NULL),(733,'INORA SIPAYUNG',NULL,'22000555','group_leader',NULL,'085248302505','innora.bagok@gmail.com',NULL,NULL,'$2y$12$6ne8b/cmqZoecmTUkDDBY.75xrTVYBTwsxEaH6UqjdrL8QmDqdC4.',6,NULL,'active',1,NULL,'2026-08-25 06:18:22','2026-08-25 06:18:22',NULL),(734,'MUH RIDWAN DALI',NULL,'22001723','group_leader',NULL,'082357895866','muhridwandali@gmail.com',NULL,NULL,'$2y$12$9PM55lPssxZnlOdJNIkSWOhrcGnD7qe4hmspp55JTx4wtUrFd/UC6',6,NULL,'active',1,NULL,'2026-08-25 06:19:01','2026-08-25 06:19:01',NULL),(735,'MUHAMMAD ARIEF KURNIAWAN,S.T.',NULL,'22002074','group_leader',NULL,'081323624224','muhammadariefk17@gmail.com',NULL,NULL,'$2y$12$z3UKCtvgpkxWg6RD/FSlXOkd.657tV6eaArrrQNrSVURbEEQ8p742',6,NULL,'active',1,NULL,'2026-08-25 06:20:38','2026-08-25 06:20:38',NULL),(736,'AGUNG PAMBUDI',NULL,'22002188','group_leader',NULL,'082213288198','agungpambudi461@gmail.com',NULL,NULL,'$2y$12$Te.AzvQqtkjJrgeyUyL.X.kQxRYNeOkIuo4B9d6GivrKP/EITY1I6',6,NULL,'active',1,NULL,'2026-08-25 06:21:08','2026-08-25 06:21:08',NULL),(737,'UMBU AGUS WINARDI',NULL,'22002807','group_leader',NULL,'085250520202','umbu414@gmail.com',NULL,NULL,'$2y$12$0S1LdVRQu5WR.jRjL2XD6OcfMjUYyTUBef5focj3A7BUXaaG9w6JK',6,NULL,'active',1,NULL,'2026-08-25 06:21:43','2026-08-25 06:21:43',NULL),(738,'ARIF BUDI SUSANTO',NULL,'22003480','group_leader',NULL,'082158176328','arifbudisusanto1@gmail.com',NULL,NULL,'$2y$12$e8kmWhsOj0DElAD93IM9hOUQettKf.suNeCt4zYJA.NiRwJ.t4MpS',6,NULL,'active',1,NULL,'2026-08-25 06:22:11','2026-08-25 06:22:11',NULL),(739,'MUHAMMAD HARIANTO',NULL,'22003562','group_leader',NULL,'082190931690','harry.ace5@gmail.com',NULL,NULL,'$2y$12$VyCoGSioNbRrEfYq9OUsiuCQ3F7KajOmD00LK6TWCkGupzCAHEAey',6,NULL,'active',1,NULL,'2026-08-25 06:22:40','2026-08-25 06:22:40',NULL),(740,'YULIUS SULO',NULL,'22003588','group_leader',NULL,'082158101123','julius.komatsu@gmail.com',NULL,NULL,'$2y$12$iBDRM/7UYcQn0q3lkKWEne0SFjfiL/fOhaH8BMwGGvV3SBFCHSEZy',6,NULL,'active',1,NULL,'2026-08-25 06:23:50','2026-08-25 06:23:50',NULL),(741,'YUDHI PASANDA',NULL,'22003636','group_leader',NULL,'082291072808','yudhia.pasanda@gmail.com',NULL,NULL,'$2y$12$j2M5MidHONtX8iq.krynJuKpttAtiHiu/hsOq8sLYvcR1pzoXRgc6',6,NULL,'active',1,NULL,'2026-08-25 06:24:18','2026-08-25 06:24:18',NULL),(742,'MUHAMMAD RIZAL',NULL,'22004158','group_leader',NULL,'085350248007','muhammxdrizal@gmail.com',NULL,NULL,'$2y$12$Rl1nGcU20AW0rxuRaEIDd.jH7Goz4lie.pTfe4fZ7XEbzo3f49IAi',6,NULL,'active',1,NULL,'2026-08-25 06:24:44','2026-08-25 06:24:44',NULL),(743,'AGUS SUPARTO',NULL,'22004438','group_leader',NULL,'081331167145','agoes.soeparto.as@gmail.com',NULL,NULL,'$2y$12$dhykqqTZ95mvFPhfFEZMheoNtRsrx7iLuAF.wi3nT9GZhunFE4yz6',6,NULL,'active',1,NULL,'2026-08-25 06:25:17','2026-08-25 06:25:17',NULL),(744,'ABDULLAH',NULL,'22004760','group_leader',NULL,'082154818123','abdullah95dullah@gmail.com',NULL,NULL,'$2y$12$clXpO3HYP3UeY0UBSW1gT.S6LC.Ut0iswJrX/wbm25pUfzOFnPfWy',6,NULL,'active',1,NULL,'2026-08-25 06:26:07','2026-08-25 06:26:07',NULL),(745,'PETRUS SALIM KATIK',NULL,'22004835','group_leader',NULL,'081351677225','aryamantha741@gmail.com',NULL,NULL,'$2y$12$0fQCp6088Ci/QOLIseTw4u3e28ErbpUQV3dEs7pPcrJTd6vTKku8u',6,NULL,'active',1,NULL,'2026-08-25 06:26:31','2026-08-25 06:26:31',NULL),(746,'HERIANTO R.P',NULL,'22004926','group_leader',NULL,'085390156813','heriantorombepayung123@gmail.com',NULL,NULL,'$2y$12$6J8wbTQLPMVk.OEW1pCSDuxcu0PcCbvoiakbaTTHBHLbSYLq7c6sS',6,NULL,'active',1,NULL,'2026-08-25 06:26:52','2026-08-25 06:26:52',NULL),(747,'AFNAN DOMILI',NULL,'22005131','group_leader',NULL,'081347192073','afnandomili544@gmail.com',NULL,NULL,'$2y$12$GYCY9IVBUYlUFN07NJBoGeZq9PySXDu4d6lkEVVH7j2tT1Xti5tOi',6,NULL,'active',1,NULL,'2026-08-25 06:27:15','2026-08-25 06:27:15',NULL),(748,'OKTAVIANUS',NULL,'22005272','group_leader',NULL,'081350010608','oktavianus268@gmail.com',NULL,NULL,'$2y$12$eisv8ojR0M6dqx8F/UdcTehlxQH1iCeote3aj1du3TFegR2Qdf2tK',6,NULL,'active',1,NULL,'2026-08-25 06:27:51','2026-08-25 06:27:51',NULL),(749,'RICKY ARIA',NULL,'22005273','group_leader',NULL,'082321118196','rickyaria1999@gmail.com',NULL,NULL,'$2y$12$ysztT.yTlVT5tkf9njNAHOnwWp9rNPY3d.dmqYxc.un/GkEXIWOFW',6,NULL,'active',1,NULL,'2026-08-25 06:28:17','2026-08-25 06:28:17',NULL),(750,'AHMAD SAHBANA',NULL,'22005435','group_leader',NULL,'082268560808','banappaadw@gmail.com',NULL,NULL,'$2y$12$gCWbY1CTlhhEa.hSYziEseY8nf1dq8ijLCBzO3IuVzyMyADrFwmM2',6,NULL,'active',1,NULL,'2026-08-25 06:28:42','2026-08-25 06:28:42',NULL),(751,'YULIANTO',NULL,'22005437','group_leader',NULL,'085341680963','yulianto250794@gmail.com',NULL,NULL,'$2y$12$JzRyQzQGr5TdGTKpruTl6OeWj.qBjGRWm6LxBEfqYzbDOaWsJS6AO',6,NULL,'active',1,NULL,'2026-08-25 06:29:10','2026-08-25 06:29:10',NULL),(752,'ARDIANUS RANTE ALLO',NULL,'22005482','group_leader',NULL,'085299658277','rantealloardianus@gmail.com',NULL,NULL,'$2y$12$Z9b.pfysldNKoZ8d1INc7O6rfWuB3tNlRxxtv7NiFL1NQuzLMbpxK',6,NULL,'active',1,NULL,'2026-08-25 06:29:56','2026-08-25 06:29:56',NULL),(753,'CHAIRUL HADI SUPRAYITNO',NULL,'22005569','group_leader',NULL,'082293158466','smdhairul@gmail.com',NULL,NULL,'$2y$12$m2gk5W0BvlKIt.uZ0RoI.uSPO.5liQri4IoeCnkKzCFlqRTgKez7m',6,NULL,'active',1,NULL,'2026-08-25 06:30:24','2026-08-25 06:30:24',NULL),(754,'RADHITYA HAJI SUKMA',NULL,'23000800','group_leader',NULL,'081353376191','radhityahajisukma@gmail.com',NULL,NULL,'$2y$12$CecF3daYJ4O15mxl2sV/2upb/pZzmtZknpBdUASidMDlNsMC6HkzC',6,NULL,'active',1,NULL,'2026-08-25 06:30:51','2026-08-25 06:30:51',NULL),(755,'M RAHIM',NULL,'23000803','group_leader',NULL,'085348437394','rahimmuhammad93@yahoo.com',NULL,NULL,'$2y$12$hrEbFBNhmifICKaI8wLNhOzGB8J/DMNh5wwA/aBiyVt8C9aCcVKDy',6,NULL,'active',1,NULL,'2026-08-25 06:31:29','2026-08-25 06:31:29',NULL),(756,'THEO ARMANTO TALEBONG',NULL,'23000847','group_leader',NULL,'085399370650','theoarmanto07@gmail.com',NULL,NULL,'$2y$12$TYhPUHZl0M.LZ56RcTjBce6d9VaG0QsLJ75/eABg.jx1u/W/bYm7a',6,NULL,'active',1,NULL,'2026-08-25 06:31:55','2026-08-25 06:31:55',NULL),(757,'ANWAR SUHADI',NULL,'23001064','group_leader',NULL,'082154883272','anwarsuhadi@gmail.com',NULL,NULL,'$2y$12$yTxo/ivddnngWFsKrTqyR.sUESlHALQV2Dz3Cu10/EpYTKlovPV/C',6,NULL,'active',1,NULL,'2026-08-25 06:32:20','2026-08-25 06:32:20',NULL),(758,'MUHAMMAD SAIFUR RAHMAN',NULL,'23001108','group_leader',NULL,'082153238503','saifurrahman17012@gmail.com',NULL,NULL,'$2y$12$GPbMegKLU/2bfoCHxdmfQ.MWoMuYCPaB0fTKFUR82NvcvkaJPqkjW',6,NULL,'active',1,NULL,'2026-08-25 06:32:44','2026-08-25 06:32:44',NULL),(759,'PUJIANTO',NULL,'23001111','group_leader',NULL,'082350415363','asuspuji@gmail.com',NULL,NULL,'$2y$12$vZsk4jX8tR0lnpnHjX5GQOMjFijFT6vfszMKwQgpfLTcAoNdLg7Pa',6,NULL,'active',1,NULL,'2026-08-25 06:33:11','2026-08-25 06:33:11',NULL),(760,'SAMDIE BARAU',NULL,'23001217','group_leader',NULL,'081357032379','bsamdie@gmail.com',NULL,NULL,'$2y$12$IDHCSqWmgm8uBUi0OdErX.QBfFktK5fY1lLhKXrLVw7NNfBsFOOle',6,NULL,'active',1,NULL,'2026-08-25 06:33:40','2026-08-25 06:33:40',NULL),(761,'ROBIBEN SIREGAR',NULL,'23001415','group_leader',NULL,'082299782036','robibensiregar93@gmail.com',NULL,NULL,'$2y$12$ppZv6xINDm5Ctpy7gS4ePur2NUrQUBJwqWxLguwlK2zFFzVvknkOS',6,NULL,'active',1,NULL,'2026-08-25 06:34:05','2026-08-25 06:34:05',NULL),(762,'AGUNG BUDI HARSONO',NULL,'23001535','group_leader',NULL,'081250149860','agung.budi.hs.st@gmail.com',NULL,NULL,'$2y$12$ZBvPOBnMtSqjJkpJNWryieC79P4KUIPl1vtD.7mNbX2B8k6Jxo95y',6,NULL,'active',1,NULL,'2026-08-25 06:34:33','2026-08-25 06:34:33',NULL),(763,'DELVIN ALDI PRAYOGA',NULL,'23002781','group_leader',NULL,'081351059206','delvinaldy72@gmail.com',NULL,NULL,'$2y$12$T.yA9d6/E4ce2bH7FtqgluZK0GvOVWpzbwgulkNi0BniV32Fo3jOi',6,NULL,'active',1,NULL,'2026-08-25 06:35:33','2026-08-25 06:35:33',NULL),(764,'ARSAD NOVANDRA',NULL,'23002828','group_leader',NULL,'082285336143','arsadnovandra30@gmail.com',NULL,NULL,'$2y$12$rNggs5.oDZrAVh0lut29N.efdkmgqIX1U44iPCEFlNIV92raigRH6',6,NULL,'active',1,NULL,'2026-08-25 06:36:05','2026-08-25 06:36:05',NULL),(765,'MUHAMMAD SAIPULLAH',NULL,'23002928','group_leader',NULL,'082154199267','saiful.pule@gmail.com',NULL,NULL,'$2y$12$uZeYnuz0J7gExdDKjU/ps.MxOB1ELILlDngxwaNgX5djWNON8pEJC',6,NULL,'active',1,NULL,'2026-08-25 06:36:35','2026-08-25 06:36:35',NULL),(766,'MOHAMMAD NADHIP',NULL,'24006335','group_leader',NULL,'085394089752','mhmmdnadip@gmail.com',NULL,NULL,'$2y$12$f5lbhf8jslOuhf6SrGPWjuKmKhDo6z.sr5ULLWeGFJgJcbOAqwxZS',6,NULL,'active',1,NULL,'2026-08-25 06:37:01','2026-08-25 06:37:01',NULL),(767,'HERMAWAN WAHYU RAHMADIANTO',NULL,'25002283','group_leader',NULL,'081347083504','hermawansp45@gmail.com',NULL,NULL,'$2y$12$PR5yq4Wo12HrClHo.s9oTel8sNPFf7ZiAINxBnrldBcL7ZAGhUKxC',6,NULL,'active',1,NULL,'2026-08-25 06:37:27','2026-08-25 06:37:27',NULL),(768,'HENDRA RUJIADI ADHA',NULL,'25002489','group_leader',NULL,'081346218446','hendrarujiadi@gmail.com',NULL,NULL,'$2y$12$nOUm2eRfALXdgnvMbrNDFO/KcLByyb12TlzTnk0TtCFd0h37ugvPS',6,NULL,'active',1,NULL,'2026-08-25 06:37:53','2026-08-25 06:37:53',NULL),(769,'DITYANTO MUHAMMAD TAUFIK',NULL,'25002493','group_leader',NULL,'087825447534','dityantomt@gmail.com',NULL,NULL,'$2y$12$TzT9i8LqCBeyOuL4Z38iuep9DNNbifCbkXGQfvHIeP1MRmpfk1HT6',6,NULL,'active',1,NULL,'2026-08-25 06:38:15','2026-08-25 06:38:15',NULL),(770,'FAUJI',NULL,'25002993','group_leader',NULL,'085249649938','paujiyy@gmail.com',NULL,NULL,'$2y$12$boTS9VeucuM6LTuzxfwGL.7nEojJbF.fIqFy8/gaoEldMaBYKVMD6',6,NULL,'active',1,NULL,'2026-08-25 06:38:45','2026-08-25 06:38:45',NULL),(771,'ADIL SULTHONI',NULL,'25003110','group_leader',NULL,'082113825848','adilsulthoni23@gmail.com',NULL,NULL,'$2y$12$SfS.PBvacKARrj0ME7hrJeL4IxG4jtc1X8S9cy2Z0xO1H3IEFBye2',6,NULL,'active',1,NULL,'2026-08-25 06:39:22','2026-08-25 06:39:22',NULL),(772,'MUHAMMAD RICKY WURIANDI',NULL,'26000432','group_leader',NULL,'081242173663','muhammadrickyw19@gmail.com',NULL,NULL,'$2y$12$PMmsTRW0rNGVu5VkvyKO0eE54p47uwugCw8k8QuodvqwrUwDJP/wW',6,NULL,'active',1,NULL,'2026-08-25 06:39:56','2026-08-25 06:39:56',NULL),(773,'FERRY BUDIMAN',NULL,'25003133','group_leader',NULL,'085251104555','ferrybudiman97@gmail.com',NULL,NULL,'$2y$12$2c5Qax.YrwZBuJQx0Rnheu51U82tNxH6L2A7MEIP9PZCw/.uw8mTi',6,NULL,'active',1,NULL,'2026-08-25 06:40:25','2026-08-25 06:40:25',NULL),(774,'DAMARA MULYA PERMANA',NULL,'26000434','group_leader',NULL,'081296460077','damaramulyapermana@gmail.com',NULL,NULL,'$2y$12$2gaiPyV92YA4/DH07HHY3u.pmCLr1thoHihShmTjh6NfzlCUCAppy',6,NULL,'active',1,NULL,'2026-08-25 06:40:53','2026-08-25 06:40:53',NULL),(775,'ZAKKA RIDHA',NULL,'26000435','group_leader',NULL,'082111452265','zakkaridha02@gmail.com',NULL,NULL,'$2y$12$L7UEB7yxiRinjvy6FV/UM.JbTFPKd/N8IIq8VBr0cwjh.JujjSc6O',6,NULL,'active',1,NULL,'2026-08-25 06:41:20','2026-08-25 06:41:20',NULL),(776,'KADEK DWI PRABAWA',NULL,'26000443','group_leader',NULL,'081337015474','dwiprabawa16@gmail.com',NULL,NULL,'$2y$12$Li3yxQZgOGJ5tcvs4LhPWeAV/2XqvsuMtyOvCUm.DcitsQtHlqC1y',6,NULL,'active',1,NULL,'2026-08-25 06:41:55','2026-08-25 06:41:55',NULL),(777,'SAMSUL ARIFIN',NULL,'15050890','group_leader',NULL,'081350390399','samsularifin.opd@ppa.co.id',NULL,NULL,'$2y$12$0WcguNNQFgdAjJFQ5UWiFejNThxq3/4qxgwQNP9HtavwZ8lsE6.xy',6,NULL,'active',1,NULL,'2026-08-25 06:42:26','2026-08-25 06:42:26',NULL),(778,'EKO YULIANTO',NULL,'15111111','group_leader',NULL,'085316825555','eyulianto@ppa.co.id',NULL,NULL,'$2y$12$pdc.SxmTL5lY45Ec8SkCYOyjrNESnfs27le5yBzU2Dg39e8V/XtZm',6,NULL,'active',1,NULL,'2026-08-25 06:42:58','2026-08-25 06:42:58',NULL),(779,'BAYU WARDHANA',NULL,'17123080','group_leader',NULL,'082150628918','bayu.wardana@ppa.co.id',NULL,NULL,'$2y$12$pAZa2kXfbb8piJ46qX1qXOZabBCeG/hyYqlBPhRVs0PdjMIihe7li',6,NULL,'active',1,NULL,'2026-08-25 06:43:26','2026-08-25 06:43:26',NULL),(780,'MICKEL SANTONI',NULL,'22005258','group_leader',NULL,'081251276488','mickel.santoni@ppa.co.id',NULL,NULL,'$2y$12$QHXCNERitQC.apwjAgHBbOVHAe2eyIJZAQl1/G/RQpdUbMePN49K2',6,NULL,'active',1,NULL,'2026-08-25 06:50:21','2026-08-25 06:50:21',NULL),(781,'EVA PRAMUDEA SAFITRI',NULL,'24006327','group_leader',NULL,'089613720853','evapramudea@ppa.co.id',NULL,NULL,'$2y$12$BYM/SCkyFwNz7h7JqPX54OkkJ5wGhiD6jctnKuNpyhFRRNdQzhEz.',4,NULL,'active',1,NULL,'2026-08-25 06:51:15','2026-08-25 06:51:15',NULL),(782,'AHMAD RYAN AL AQSA',NULL,'26003043','group_leader',NULL,'085243190409','ahmadryanalaqsha@ppa.co.id',NULL,NULL,'$2y$12$RliLYaYCaceRmFxmqT2PpuPyVVYq35VW7bRpJ0YXCzF7qPkdhvGS6',1,NULL,'active',1,NULL,'2026-08-25 06:52:19','2026-08-25 06:52:19',NULL),(783,'ZULFA HIFNI FAJRIYAH',NULL,'24006329','group_leader',NULL,'085337250820','zulfahifni@ppa.co.id',NULL,NULL,'$2y$12$6D2p.GLlIlnNFYjXFeg2duiILSnFnLpw1wvFJWXwdAxasGb.AN3FO',1,NULL,'active',1,NULL,'2026-08-25 06:55:08','2026-08-25 06:55:08',NULL),(784,'SHAFFA RASYA FERDINAND PUTRI',NULL,'26000433','group_leader',NULL,'081212178262','shaffarasyaferdinandputri@gmail.com',NULL,NULL,'$2y$12$.zImrf39GnVUKMMS6DJYs.7EL0yFMlV5JtOqmDK2KB2NMJ5yGzWum',1,NULL,'active',1,NULL,'2026-08-25 06:55:44','2026-08-25 06:55:44',NULL),(785,'FANI NUR HIDAYAT',NULL,'23002778','group_leader',NULL,'085173441048','fani.hidayat@ppa.co.id',NULL,NULL,'$2y$12$BYHRc7x5ltLdFuSzfnf0XOmKagyIGskj9PZ2gDDv84qcgp1NaM/qu',1,NULL,'active',1,NULL,'2026-08-25 06:56:12','2026-08-25 06:56:12',NULL),(786,'AHMAD WAHID ANWARUDIN',NULL,'22003516','group_leader',NULL,'082243857514','anwarwahid71@gmail.com',NULL,NULL,'$2y$12$57OMS3UBfKfLJ5LcBrsHJeMnEmaGpdHPiuMmV8laDk999SghIEquW',1,NULL,'active',1,NULL,'2026-08-25 06:56:40','2026-08-25 06:56:40',NULL),(787,'YUSRIAN HIDAYAT',NULL,'22004823','group_leader',NULL,'082310686312','yusrianhidayat@gmail.com',NULL,NULL,'$2y$12$PVGCwesyEQAuyLWgeRGf4eKvNbBC97SELSwbp.nD3oytQ.iw8Jr4y',1,NULL,'active',1,NULL,'2026-08-25 06:57:16','2026-08-25 06:57:16',NULL),(788,'VIRGO HIDAYAT SUROSO',NULL,'21001417','group_leader',NULL,'082352024279','Virgo.hidayat@ppa.co.id',NULL,NULL,'$2y$12$1UlS9xTJVgzvRm9QdbrOdexmGmTW8HXNPP0W.Z2govvJRcDCpzD2q',1,NULL,'active',1,NULL,'2026-08-25 06:58:34','2026-08-25 06:58:34',NULL),(789,'SARJU',NULL,'22001345','group_leader',NULL,'085235322442','sarju@ppa.co.id',NULL,NULL,'$2y$12$ZTTNlmmZuWbJIGIF.WZacuqYWV.ye8euc/nGgqMuHgZQ0ntyVPD0.',6,NULL,'active',1,NULL,'2026-08-25 07:02:31','2026-08-25 07:02:31',NULL),(790,'ZAINAL ABIDIN',NULL,'22004627','departemen_head',NULL,'081232660630','zainal.abidin@amm.id',NULL,NULL,'$2y$12$H9aGWOPSLLH.qA.OQ.vnHu2XgsOxAH2m3F79BJprowIAMuqZxTC7y',2,NULL,'active',1,NULL,'2026-08-25 07:07:03','2026-08-25 07:07:03',NULL),(791,'MOHAMAD AKHSAN ASHARIADI',NULL,'18074525','group_leader',NULL,'085226819678','akhsan.ashariadi@ppa.co.id',NULL,NULL,'$2y$12$Wl3xrJI7CmJVzcv5fDaW0O3Jmkp1VYy1LQGkWAkB.KCsBXIxZwqna',2,NULL,'active',1,NULL,'2026-08-25 07:07:45','2026-08-25 07:07:45',NULL),(792,'ENGGAR EKA PRASETYA',NULL,'14040677','group_leader',NULL,'082277552282','enggarekaprasetya@ppa.co.id',NULL,NULL,'$2y$12$nJ10jy1A4FgfEfF6qV77JOkP.yyWfv71h8wveSWOSOhp/2GxIlK6O',2,NULL,'active',1,NULL,'2026-08-25 07:08:19','2026-08-25 07:08:19',NULL),(793,'HAIRANI',NULL,'22003449','group_leader',NULL,'085246229391','hairani@ppa.co.id',NULL,NULL,'$2y$12$S435SNg1kVLnykHnLgw/Uui2CW69H4PFj9ZxKMLajcgFshBgW6PP6',2,NULL,'active',1,NULL,'2026-08-25 07:09:00','2026-08-25 07:09:00',NULL),(794,'HERI KUSWANTO',NULL,'19020756','group_leader',NULL,'085346806861','herikuswanto@ppa.co.id',NULL,NULL,'$2y$12$xRstZJUTlR8bwEeAMngUlO.vWbWY7boJOTZgAEXS1Zj/zb4nxmPxu',2,NULL,'active',1,NULL,'2026-08-25 07:09:36','2026-08-25 07:09:36',NULL),(795,'EDO SYAHRIZAL',NULL,'24006328','group_leader',NULL,'081261989969','edo.syahrizal@ppa.co.id',NULL,NULL,'$2y$12$FbsqZDcm2JJHAUhqFgTckuI/0QQfHKkKoGW0gcIZR5BYSrCTfqIJm',2,NULL,'active',1,NULL,'2026-08-25 07:10:07','2026-08-25 07:10:07',NULL),(796,'NOVTANDHI PANANLULUY',NULL,'230256','staff',NULL,'082232116054',NULL,NULL,NULL,'$2y$12$d6HMTNR.tTjry9/ep/SF4eIFWzahscByKOhxZst54OB.16zNDzqxW',6,NULL,'active',1,NULL,'2026-08-26 16:12:18','2026-08-31 05:49:04',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'smartpro_shadcn'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-06 12:34:14
