-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 27, 2026 at 01:32 PM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u805399352_smartpro_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED NOT NULL,
  `decision` enum('approved','rejected') DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `approvals`
--

INSERT INTO `approvals` (`id`, `document_id`, `approver_id`, `decision`, `comment`, `signed_at`, `created_at`, `updated_at`) VALUES
(29, 1179, 681, 'approved', NULL, '2026-08-27 13:17:27', '2026-08-27 13:17:27', '2026-08-27 13:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `section_key` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attachment_comments`
--

CREATE TABLE `attachment_comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attachment_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `document_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `document_id`, `action`, `meta_json`, `ip_address`, `created_at`) VALUES
(1582, 1, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 02:54:43'),
(1583, 1, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:15:39'),
(1584, 1, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:15:53'),
(1585, 1, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:16:13'),
(1586, 5, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:16:17'),
(1587, 5, 1161, 'document.arsip_upload', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":4}', '10.7.110.79', '2026-08-13 03:18:25'),
(1588, 5, 1162, 'document.arsip_upload', '{\"doc_number\":\"PPA-ADRO-FK-ICTMD-10\",\"type\":\"FK\",\"department\":\"ICTMD\",\"edisi\":2,\"no_revisi\":3}', '10.7.110.79', '2026-08-13 03:18:59'),
(1589, 5, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:24:25'),
(1590, 5, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:24:58'),
(1591, 5, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:25:02'),
(1592, 1, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:25:05'),
(1593, 1, NULL, 'user.create_staff', '{\"created_user_id\":666,\"role\":\"staff\",\"department_id\":5}', '10.7.110.79', '2026-08-13 03:26:02'),
(1594, 1, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:26:11'),
(1595, 666, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:26:21'),
(1596, 666, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:28:36'),
(1597, 666, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:38:21'),
(1598, 666, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:38:25'),
(1599, 5, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:38:29'),
(1600, 5, 1163, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '10.7.110.79', '2026-08-13 03:38:57'),
(1601, 1, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:39:21'),
(1602, 1, NULL, 'user.logout', NULL, '10.7.110.79', '2026-08-13 03:39:36'),
(1603, 11, NULL, 'user.login', NULL, '10.7.110.79', '2026-08-13 03:39:39'),
(1604, 5, 1163, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '10.7.110.79', '2026-08-13 03:40:55'),
(1605, 5, 1164, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '10.7.110.79', '2026-08-13 04:02:28'),
(1606, 5, 1164, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '10.7.110.79', '2026-08-13 04:02:57'),
(1607, 11, 1164, 'document.review_start', NULL, '10.7.110.79', '2026-08-13 04:18:52'),
(1608, 11, 1164, 'document.review_reject', '{\"annotations\":0,\"ai_adopted\":0}', '10.7.110.79', '2026-08-13 04:18:56'),
(1609, 11, 1163, 'document.review_start', NULL, '10.7.110.79', '2026-08-13 04:19:01'),
(1610, 11, 1163, 'document.review_reject', '{\"annotations\":0,\"ai_adopted\":0}', '10.7.110.79', '2026-08-13 04:19:04'),
(1611, 5, 1163, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":1,\"no_revisi\":0}', '10.7.110.79', '2026-08-13 04:19:34'),
(1612, 5, 1164, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":1,\"no_revisi\":0}', '10.7.110.79', '2026-08-13 04:19:42'),
(1613, 11, 1164, 'document.review_start', NULL, '10.7.110.79', '2026-08-13 04:20:43'),
(1614, 11, 1164, 'document.review_reject', '{\"annotations\":0,\"ai_adopted\":0}', '10.7.110.79', '2026-08-13 04:20:46'),
(1615, 11, 1163, 'document.review_start', NULL, '10.7.110.79', '2026-08-13 04:20:48'),
(1616, 11, 1163, 'document.review_reject', '{\"annotations\":0,\"ai_adopted\":0}', '10.7.110.79', '2026-08-13 04:20:51'),
(1617, 5, 1163, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":2,\"no_revisi\":0}', '10.7.110.79', '2026-08-13 04:21:14'),
(1618, 5, 1164, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":2,\"no_revisi\":0}', '10.7.110.79', '2026-08-13 04:21:24'),
(1619, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 13:16:31'),
(1620, 1, NULL, 'user.create_staff', '{\"created_user_id\":667,\"role\":\"group_leader\",\"department_id\":5}', '103.88.153.198', '2026-08-13 15:14:21'),
(1621, 1, NULL, 'user.create_staff', '{\"created_user_id\":668,\"role\":\"section_head\",\"department_id\":5}', '103.88.153.198', '2026-08-13 15:15:03'),
(1622, 1, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-13 15:15:13'),
(1623, 667, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:15:31'),
(1624, 667, 1165, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '103.88.153.198', '2026-08-13 15:15:39'),
(1625, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:16:28'),
(1626, 1, NULL, 'user.create_staff', '{\"created_user_id\":669,\"role\":\"pimpinan\",\"department_id\":null}', '103.88.153.198', '2026-08-13 15:17:30'),
(1627, 667, 1165, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '103.88.153.198', '2026-08-13 15:17:57'),
(1628, 1, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-13 15:18:03'),
(1629, 667, 1166, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '103.88.153.198', '2026-08-13 15:18:19'),
(1630, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:18:58'),
(1631, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:21:20'),
(1632, 668, 1165, 'document.review_start', NULL, '103.88.153.198', '2026-08-13 15:21:25'),
(1633, 668, 1165, 'document.review_approve', '{\"lanjut_ke\":\"md\"}', '103.88.153.198', '2026-08-13 15:21:29'),
(1634, 669, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:21:58'),
(1635, 667, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-13 15:22:10'),
(1636, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:22:15'),
(1637, 1, NULL, 'user.create_staff', '{\"created_user_id\":670,\"role\":\"management_development\",\"department_id\":5}', '103.88.153.198', '2026-08-13 15:23:06'),
(1638, 668, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-13 15:24:05'),
(1639, 670, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:24:11'),
(1640, 670, 1165, 'document.md_approve', NULL, '103.88.153.198', '2026-08-13 15:24:17'),
(1641, 670, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-13 15:24:20'),
(1642, 669, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 15:24:30'),
(1643, 669, 1165, 'document.approve', '{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-SOP-ICTMD-01\"}', '103.88.153.198', '2026-08-13 15:24:54'),
(1644, 1, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-13 16:40:51'),
(1645, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-13 16:41:08'),
(1646, 1, NULL, 'user.login', NULL, '2a04:4e41:76d5:d44d::65d5:d44d', '2026-08-13 23:30:48'),
(1647, 667, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-14 09:21:43'),
(1648, 667, 1167, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-001\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '103.88.153.198', '2026-08-14 09:22:54'),
(1649, 667, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-14 15:43:24'),
(1650, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-14 16:52:04'),
(1651, 1, NULL, 'user.create_staff', '{\"created_user_id\":671,\"role\":\"group_leader\",\"department_id\":7}', '103.88.153.198', '2026-08-14 16:53:44'),
(1652, 671, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-14 17:04:51'),
(1653, 1, NULL, 'user.login', NULL, '2a04:4e41:76c9:e561::ed49:e561', '2026-08-15 01:19:54'),
(1654, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-15 08:43:39'),
(1655, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-15 15:56:39'),
(1656, 1, NULL, 'user.create_staff', '{\"created_user_id\":672,\"role\":\"group_leader\",\"department_id\":2}', '103.88.153.198', '2026-08-15 16:11:41'),
(1657, 1, NULL, 'user.create_staff', '{\"created_user_id\":673,\"role\":\"group_leader\",\"department_id\":1}', '103.88.153.198', '2026-08-15 16:13:07'),
(1658, 1, NULL, 'user.create_staff', '{\"created_user_id\":674,\"role\":\"group_leader\",\"department_id\":2}', '103.88.153.198', '2026-08-15 16:14:25'),
(1659, 1, NULL, 'user.create_staff', '{\"created_user_id\":675,\"role\":\"departemen_head\",\"department_id\":7}', '103.88.153.198', '2026-08-15 16:22:37'),
(1660, 1, NULL, 'user.login', NULL, '182.8.130.95', '2026-08-15 18:59:38'),
(1661, 1, NULL, 'user.create_staff', '{\"created_user_id\":676,\"role\":\"section_head\",\"department_id\":7}', '182.8.130.95', '2026-08-15 19:07:47'),
(1662, 1, NULL, 'user.create_staff', '{\"created_user_id\":677,\"role\":\"group_leader\",\"department_id\":4}', '182.8.130.95', '2026-08-15 19:08:46'),
(1663, 1, NULL, 'user.create_staff', '{\"created_user_id\":678,\"role\":\"section_head\",\"department_id\":1}', '182.8.130.95', '2026-08-15 19:09:49'),
(1664, 1, NULL, 'user.create_staff', '{\"created_user_id\":679,\"role\":\"group_leader\",\"department_id\":3}', '182.8.130.95', '2026-08-15 19:11:24'),
(1665, 1, NULL, 'user.create_staff', '{\"created_user_id\":680,\"role\":\"group_leader\",\"department_id\":7}', '182.8.130.95', '2026-08-15 19:12:37'),
(1666, 1, NULL, 'user.create_staff', '{\"created_user_id\":681,\"role\":\"section_head\",\"department_id\":6}', '182.8.130.95', '2026-08-15 19:13:55'),
(1667, 1, NULL, 'user.create_staff', '{\"created_user_id\":682,\"role\":\"group_leader\",\"department_id\":6}', '182.8.130.95', '2026-08-15 19:15:01'),
(1668, 1, NULL, 'user.create_staff', '{\"created_user_id\":683,\"role\":\"group_leader\",\"department_id\":6}', '182.8.130.95', '2026-08-15 19:16:12'),
(1669, 1, NULL, 'user.create_staff', '{\"created_user_id\":684,\"role\":\"section_head\",\"department_id\":2}', '182.8.130.95', '2026-08-15 19:17:50'),
(1670, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-18 09:42:28'),
(1671, 668, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-18 09:42:47'),
(1672, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-18 09:42:51'),
(1673, 1, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-18 09:50:26'),
(1674, 667, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-18 09:51:26'),
(1675, 667, 1168, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '103.88.153.198', '2026-08-18 09:53:28'),
(1676, 667, 1168, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '103.88.153.198', '2026-08-18 10:02:17'),
(1677, 667, 1169, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '103.88.153.198', '2026-08-18 10:03:52'),
(1678, 667, 1170, 'document.arsip_upload', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"type\":\"SOP\",\"department\":\"ICTMD\",\"edisi\":1,\"no_revisi\":4}', '103.88.153.198', '2026-08-18 10:05:22'),
(1679, 667, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-18 10:05:42'),
(1680, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-18 10:07:59'),
(1681, 668, 1171, 'document.request_revision', '{\"from_document_id\":1165,\"no_revisi\":0}', '103.88.153.198', '2026-08-18 10:09:03'),
(1682, 668, 1168, 'document.review_start', NULL, '103.88.153.198', '2026-08-18 10:09:25'),
(1683, 668, 1168, 'document.ai_review', '{\"findings\":0}', '103.88.153.198', '2026-08-18 10:09:36'),
(1684, 668, 1168, 'document.review_approve', '{\"lanjut_ke\":\"md\"}', '103.88.153.198', '2026-08-18 10:10:29'),
(1685, 668, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-18 10:10:32'),
(1686, 667, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-18 10:10:42'),
(1687, 667, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-18 10:19:59'),
(1688, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-18 10:20:06'),
(1689, 667, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-19 09:35:59'),
(1690, 667, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-19 09:36:58'),
(1691, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-19 09:37:06'),
(1692, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 09:13:03'),
(1693, 1, 1172, 'document.create', '{\"doc_number\":\"PPA-ADRO-ICTMD-009\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '202.65.239.90', '2026-08-22 09:16:50'),
(1694, 1, 1172, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '36.88.128.42', '2026-08-22 09:34:42'),
(1695, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-22 09:36:48'),
(1696, 668, 1172, 'document.review_start', NULL, '103.88.153.198', '2026-08-22 09:36:52'),
(1697, 1, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-22 09:41:22'),
(1698, 668, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-22 09:41:24'),
(1699, 668, 1172, 'document.ai_review', '{\"findings\":0}', '103.88.153.198', '2026-08-22 09:42:16'),
(1700, 668, 1172, 'document.review_approve', '{\"lanjut_ke\":\"md\"}', '202.65.239.90', '2026-08-22 09:43:16'),
(1701, 668, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 09:43:23'),
(1702, 670, NULL, 'user.login', NULL, '202.65.239.90', '2026-08-22 09:43:30'),
(1703, 670, 1172, 'document.md_approve', NULL, '36.88.128.42', '2026-08-22 09:44:51'),
(1704, 670, NULL, 'user.logout', NULL, '202.65.239.90', '2026-08-22 09:45:05'),
(1705, 1, NULL, 'user.login', NULL, '202.65.239.90', '2026-08-22 09:45:11'),
(1706, 1, NULL, 'user.logout', NULL, '202.65.239.90', '2026-08-22 09:45:29'),
(1707, 669, NULL, 'user.login', NULL, '202.65.239.90', '2026-08-22 09:45:31'),
(1708, 669, 1172, 'document.approve', '{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-ICTMD-009\"}', '202.65.239.90', '2026-08-22 09:46:38'),
(1709, 669, NULL, 'user.logout', NULL, '202.65.239.90', '2026-08-22 09:46:42'),
(1710, 1, NULL, 'user.login', NULL, '202.65.239.90', '2026-08-22 09:46:47'),
(1711, 1, 1165, 'document.cancel_revision_b', NULL, '202.65.239.90', '2026-08-22 09:47:02'),
(1712, 1, 1170, 'document.make_obsolete', '{\"from\":\"published\"}', '202.65.239.90', '2026-08-22 09:47:12'),
(1713, 1, NULL, 'document.purge', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"1\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"TESTING PENGHAPUSAN\"}', '202.65.239.90', '2026-08-22 09:47:47'),
(1714, 1, 1173, 'document.request_revision', '{\"from_document_id\":1172,\"no_revisi\":0}', '36.88.128.42', '2026-08-22 09:53:57'),
(1715, 1, 1173, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":1}', '36.88.128.42', '2026-08-22 09:58:28'),
(1716, 1, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 09:59:25'),
(1717, 668, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 09:59:27'),
(1718, 668, 1173, 'document.review_start', NULL, '36.88.128.42', '2026-08-22 09:59:35'),
(1719, 668, 1173, 'document.review_approve', '{\"lanjut_ke\":\"md\"}', '36.88.128.42', '2026-08-22 09:59:39'),
(1720, 668, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 09:59:42'),
(1721, 669, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 10:00:00'),
(1722, 669, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 10:00:21'),
(1723, 670, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 10:00:26'),
(1724, 670, 1173, 'document.md_approve', NULL, '36.88.128.42', '2026-08-22 10:00:52'),
(1725, 670, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 10:00:56'),
(1726, 669, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 10:01:00'),
(1727, 669, 1173, 'document.approve', '{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-ICTMD-009\"}', '36.88.128.42', '2026-08-22 10:01:08'),
(1728, 669, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 10:01:11'),
(1729, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 10:01:18'),
(1730, 1, NULL, 'document.purge', '{\"doc_number\":\"PPA-ADRO-ICTMD-009\",\"title\":\"INSTALASI, PEMELIHARAAN & REPOSISI PERANGKAT SIGRA\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"TESTING PEMUSNAHAN\"}', '103.88.153.198', '2026-08-22 10:09:08'),
(1731, 1, NULL, 'document.purge', '{\"doc_number\":\"PPA-ADRO-ICTMD-009\",\"title\":\"INSTALASI, PEMELIHARAAN & REPOSISI PERANGKAT SIGRA\",\"type\":\"SOP\",\"department_id\":5,\"alasan\":\"PEMUSNAHAN DOKUMEN TEST\"}', '103.88.153.198', '2026-08-22 10:09:31'),
(1732, 1, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-22 13:20:37'),
(1733, 667, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-22 13:20:39'),
(1734, 667, 1169, 'document.delete', NULL, '36.88.128.42', '2026-08-22 13:21:06'),
(1735, 667, 1167, 'document.delete', NULL, '36.88.128.42', '2026-08-22 13:21:11'),
(1736, 667, 1166, 'document.delete', NULL, '36.88.128.42', '2026-08-22 13:21:15'),
(1737, 667, NULL, 'user.logout', NULL, '103.88.153.198', '2026-08-22 13:22:20'),
(1738, 673, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-23 08:33:47'),
(1739, 674, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-23 09:53:45'),
(1740, 667, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-23 10:53:44'),
(1741, 667, 1174, 'document.create', '{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-01\",\"type\":\"JSA\",\"department\":\"ICTMD\"}', '36.88.128.42', '2026-08-23 10:54:26'),
(1742, 667, 1175, 'document.create', '{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"type\":\"JSA\",\"department\":\"ICTMD\"}', '36.88.128.42', '2026-08-23 10:58:06'),
(1743, 667, 1175, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '202.125.100.29', '2026-08-23 11:17:14'),
(1744, 667, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-23 11:17:23'),
(1745, 1, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-23 11:17:47'),
(1746, 1, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-23 11:18:12'),
(1747, 668, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-23 11:18:14'),
(1748, 668, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-23 11:18:20'),
(1749, 673, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-23 11:18:39'),
(1750, 673, 1175, 'document.review_start', NULL, '202.125.100.29', '2026-08-23 11:18:45'),
(1751, 673, 1175, 'document.review_approve', NULL, '202.125.100.29', '2026-08-23 11:19:47'),
(1752, 673, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-23 11:19:49'),
(1753, 668, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-23 11:19:52'),
(1754, 668, 1175, 'document.approve', '{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-JSA-ICTMD-03\"}', '202.125.100.29', '2026-08-23 11:20:01'),
(1755, 668, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-23 11:20:03'),
(1756, 667, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-23 11:20:08'),
(1757, 673, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-23 13:14:22'),
(1758, 667, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-23 14:13:18'),
(1759, 667, 1176, 'document.create', '{\"doc_number\":\"PPA-ADRO-JSA-ICTMD-04\",\"type\":\"JSA\",\"department\":\"ICTMD\"}', '103.88.153.198', '2026-08-23 14:15:30'),
(1760, 667, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-23 14:30:44'),
(1761, 673, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-24 14:08:22'),
(1762, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-24 16:26:45'),
(1763, 1, NULL, 'user.create_staff', '{\"created_user_id\":685,\"role\":\"section_head\",\"department_id\":1}', '36.88.128.42', '2026-08-24 16:28:33'),
(1764, 1, NULL, 'user.create_staff', '{\"created_user_id\":686,\"role\":\"section_head\",\"department_id\":1}', '36.88.128.42', '2026-08-24 16:30:30'),
(1765, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-25 08:47:44'),
(1766, 1, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-25 13:34:25'),
(1767, 1, NULL, 'user.create_staff', '{\"created_user_id\":687,\"role\":\"group_leader\",\"department_id\":7}', '202.125.100.29', '2026-08-25 13:40:48'),
(1768, 1, NULL, 'user.create_staff', '{\"created_user_id\":688,\"role\":\"group_leader\",\"department_id\":7}', '202.125.100.29', '2026-08-25 13:41:21'),
(1769, 1, NULL, 'user.create_staff', '{\"created_user_id\":689,\"role\":\"group_leader\",\"department_id\":7}', '202.125.100.29', '2026-08-25 13:41:54'),
(1770, 1, NULL, 'user.create_staff', '{\"created_user_id\":690,\"role\":\"group_leader\",\"department_id\":7}', '202.125.100.29', '2026-08-25 13:43:00'),
(1771, 1, NULL, 'user.create_staff', '{\"created_user_id\":691,\"role\":\"group_leader\",\"department_id\":7}', '202.125.100.29', '2026-08-25 13:43:31'),
(1772, 1, NULL, 'user.create_staff', '{\"created_user_id\":692,\"role\":\"group_leader\",\"department_id\":7}', '202.125.100.29', '2026-08-25 13:44:03'),
(1773, 1, NULL, 'user.create_staff', '{\"created_user_id\":693,\"role\":\"group_leader\",\"department_id\":4}', '202.125.100.29', '2026-08-25 13:44:49'),
(1774, 1, NULL, 'user.create_staff', '{\"created_user_id\":694,\"role\":\"group_leader\",\"department_id\":3}', '202.125.100.29', '2026-08-25 13:45:39'),
(1775, 1, NULL, 'user.create_staff', '{\"created_user_id\":695,\"role\":\"group_leader\",\"department_id\":3}', '202.125.100.29', '2026-08-25 13:46:10'),
(1776, 1, NULL, 'user.create_staff', '{\"created_user_id\":696,\"role\":\"group_leader\",\"department_id\":5}', '202.125.100.29', '2026-08-25 13:47:36'),
(1777, 1, NULL, 'user.create_staff', '{\"created_user_id\":697,\"role\":\"group_leader\",\"department_id\":1}', '202.125.100.29', '2026-08-25 13:49:07'),
(1778, 1, NULL, 'user.create_staff', '{\"created_user_id\":698,\"role\":\"group_leader\",\"department_id\":2}', '202.125.100.29', '2026-08-25 13:49:52'),
(1779, 1, NULL, 'user.create_staff', '{\"created_user_id\":699,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 13:50:58'),
(1780, 1, NULL, 'user.create_staff', '{\"created_user_id\":700,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 13:51:28'),
(1781, 1, NULL, 'user.create_staff', '{\"created_user_id\":701,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 13:51:55'),
(1782, 1, NULL, 'user.create_staff', '{\"created_user_id\":702,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 13:52:56'),
(1783, 1, NULL, 'user.create_staff', '{\"created_user_id\":703,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:04:31'),
(1784, 1, NULL, 'user.create_staff', '{\"created_user_id\":704,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:05:21'),
(1785, 1, NULL, 'user.create_staff', '{\"created_user_id\":705,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:05:55'),
(1786, 1, NULL, 'user.create_staff', '{\"created_user_id\":706,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:06:27'),
(1787, 1, NULL, 'user.create_staff', '{\"created_user_id\":707,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:07:01'),
(1788, 1, NULL, 'user.create_staff', '{\"created_user_id\":708,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:07:54'),
(1789, 1, NULL, 'user.create_staff', '{\"created_user_id\":709,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:08:18'),
(1790, 1, NULL, 'user.create_staff', '{\"created_user_id\":710,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:13:31'),
(1791, 1, NULL, 'user.create_staff', '{\"created_user_id\":711,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:14:38'),
(1792, 1, NULL, 'user.create_staff', '{\"created_user_id\":712,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:15:08'),
(1793, 1, NULL, 'user.create_staff', '{\"created_user_id\":713,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:16:25'),
(1794, 1, NULL, 'user.create_staff', '{\"created_user_id\":714,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:17:16'),
(1795, 1, NULL, 'user.create_staff', '{\"created_user_id\":715,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:18:22'),
(1796, 1, NULL, 'user.create_staff', '{\"created_user_id\":716,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:19:01'),
(1797, 1, NULL, 'user.create_staff', '{\"created_user_id\":717,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:20:38'),
(1798, 1, NULL, 'user.create_staff', '{\"created_user_id\":718,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:21:08'),
(1799, 1, NULL, 'user.create_staff', '{\"created_user_id\":719,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:21:43'),
(1800, 1, NULL, 'user.create_staff', '{\"created_user_id\":720,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:22:11'),
(1801, 1, NULL, 'user.create_staff', '{\"created_user_id\":721,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:22:40'),
(1802, 1, NULL, 'user.create_staff', '{\"created_user_id\":722,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:23:50'),
(1803, 1, NULL, 'user.create_staff', '{\"created_user_id\":723,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:24:18'),
(1804, 1, NULL, 'user.create_staff', '{\"created_user_id\":724,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:24:44'),
(1805, 1, NULL, 'user.create_staff', '{\"created_user_id\":725,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:25:17'),
(1806, 1, NULL, 'user.create_staff', '{\"created_user_id\":726,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:26:07'),
(1807, 1, NULL, 'user.create_staff', '{\"created_user_id\":727,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:26:31'),
(1808, 1, NULL, 'user.create_staff', '{\"created_user_id\":728,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:26:52'),
(1809, 1, NULL, 'user.create_staff', '{\"created_user_id\":729,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:27:15'),
(1810, 1, NULL, 'user.create_staff', '{\"created_user_id\":730,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:27:51'),
(1811, 1, NULL, 'user.create_staff', '{\"created_user_id\":731,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:28:17'),
(1812, 1, NULL, 'user.create_staff', '{\"created_user_id\":732,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:28:42'),
(1813, 1, NULL, 'user.create_staff', '{\"created_user_id\":733,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:29:10'),
(1814, 1, NULL, 'user.create_staff', '{\"created_user_id\":734,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:29:56'),
(1815, 1, NULL, 'user.create_staff', '{\"created_user_id\":735,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:30:24'),
(1816, 1, NULL, 'user.create_staff', '{\"created_user_id\":736,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:30:51'),
(1817, 1, NULL, 'user.create_staff', '{\"created_user_id\":737,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:31:29'),
(1818, 1, NULL, 'user.create_staff', '{\"created_user_id\":738,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:31:55'),
(1819, 1, NULL, 'user.create_staff', '{\"created_user_id\":739,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:32:20'),
(1820, 1, NULL, 'user.create_staff', '{\"created_user_id\":740,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:32:44'),
(1821, 1, NULL, 'user.create_staff', '{\"created_user_id\":741,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:33:11'),
(1822, 1, NULL, 'user.create_staff', '{\"created_user_id\":742,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:33:40'),
(1823, 1, NULL, 'user.create_staff', '{\"created_user_id\":743,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:34:05'),
(1824, 1, NULL, 'user.create_staff', '{\"created_user_id\":744,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:34:33'),
(1825, 1, NULL, 'user.create_staff', '{\"created_user_id\":745,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:35:33'),
(1826, 1, NULL, 'user.create_staff', '{\"created_user_id\":746,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:36:05'),
(1827, 1, NULL, 'user.create_staff', '{\"created_user_id\":747,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:36:35'),
(1828, 1, NULL, 'user.create_staff', '{\"created_user_id\":748,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:37:01'),
(1829, 1, NULL, 'user.create_staff', '{\"created_user_id\":749,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:37:27'),
(1830, 1, NULL, 'user.create_staff', '{\"created_user_id\":750,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:37:53'),
(1831, 1, NULL, 'user.create_staff', '{\"created_user_id\":751,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:38:15'),
(1832, 1, NULL, 'user.create_staff', '{\"created_user_id\":752,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:38:45'),
(1833, 1, NULL, 'user.create_staff', '{\"created_user_id\":753,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:39:22'),
(1834, 1, NULL, 'user.create_staff', '{\"created_user_id\":754,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:39:56'),
(1835, 1, NULL, 'user.create_staff', '{\"created_user_id\":755,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:40:25'),
(1836, 1, NULL, 'user.create_staff', '{\"created_user_id\":756,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:40:53'),
(1837, 1, NULL, 'user.create_staff', '{\"created_user_id\":757,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:41:20'),
(1838, 1, NULL, 'user.create_staff', '{\"created_user_id\":758,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:41:55'),
(1839, 1, NULL, 'user.create_staff', '{\"created_user_id\":759,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:42:26'),
(1840, 1, NULL, 'user.create_staff', '{\"created_user_id\":760,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:42:58'),
(1841, 1, NULL, 'user.create_staff', '{\"created_user_id\":761,\"role\":\"group_leader\",\"department_id\":6}', '103.88.153.198', '2026-08-25 14:43:26'),
(1842, 1, NULL, 'user.create_staff', '{\"created_user_id\":762,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 14:46:40'),
(1843, 1, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-25 14:47:06'),
(1844, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-25 14:47:21'),
(1845, 1, NULL, 'user.delete', '{\"user_id\":762,\"nrp\":\"22001354\"}', '36.88.128.42', '2026-08-25 14:47:29'),
(1846, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-25 14:48:31'),
(1847, 1, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-25 14:49:25'),
(1848, 683, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-25 14:49:41'),
(1849, 683, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-25 14:49:47'),
(1850, 1, NULL, 'user.create_staff', '{\"created_user_id\":763,\"role\":\"group_leader\",\"department_id\":6}', '202.125.100.29', '2026-08-25 14:50:21'),
(1851, 1, NULL, 'user.create_staff', '{\"created_user_id\":764,\"role\":\"group_leader\",\"department_id\":4}', '202.125.100.29', '2026-08-25 14:51:15'),
(1852, 1, NULL, 'user.create_staff', '{\"created_user_id\":765,\"role\":\"group_leader\",\"department_id\":1}', '202.125.100.29', '2026-08-25 14:52:19'),
(1853, 1, NULL, 'user.create_staff', '{\"created_user_id\":766,\"role\":\"group_leader\",\"department_id\":1}', '36.88.128.42', '2026-08-25 14:55:08'),
(1854, 1, NULL, 'user.create_staff', '{\"created_user_id\":767,\"role\":\"group_leader\",\"department_id\":1}', '36.88.128.42', '2026-08-25 14:55:44'),
(1855, 1, NULL, 'user.create_staff', '{\"created_user_id\":768,\"role\":\"group_leader\",\"department_id\":1}', '36.88.128.42', '2026-08-25 14:56:12'),
(1856, 1, NULL, 'user.create_staff', '{\"created_user_id\":769,\"role\":\"group_leader\",\"department_id\":1}', '36.88.128.42', '2026-08-25 14:56:40'),
(1857, 1, NULL, 'user.create_staff', '{\"created_user_id\":770,\"role\":\"group_leader\",\"department_id\":1}', '36.88.128.42', '2026-08-25 14:57:16'),
(1858, 1, NULL, 'user.create_staff', '{\"created_user_id\":771,\"role\":\"group_leader\",\"department_id\":1}', '103.88.153.198', '2026-08-25 14:58:34'),
(1859, 1, NULL, 'user.create_staff', '{\"created_user_id\":772,\"role\":\"group_leader\",\"department_id\":6}', '36.88.128.42', '2026-08-25 15:02:31'),
(1860, 1, NULL, 'user.create_staff', '{\"created_user_id\":773,\"role\":\"departemen_head\",\"department_id\":2}', '36.88.128.42', '2026-08-25 15:07:03'),
(1861, 1, NULL, 'user.create_staff', '{\"created_user_id\":774,\"role\":\"group_leader\",\"department_id\":2}', '36.88.128.42', '2026-08-25 15:07:45'),
(1862, 1, NULL, 'user.create_staff', '{\"created_user_id\":775,\"role\":\"group_leader\",\"department_id\":2}', '36.88.128.42', '2026-08-25 15:08:19'),
(1863, 1, NULL, 'user.create_staff', '{\"created_user_id\":776,\"role\":\"group_leader\",\"department_id\":2}', '36.88.128.42', '2026-08-25 15:09:00'),
(1864, 1, NULL, 'user.create_staff', '{\"created_user_id\":777,\"role\":\"group_leader\",\"department_id\":2}', '36.88.128.42', '2026-08-25 15:09:36'),
(1865, 1, NULL, 'user.create_staff', '{\"created_user_id\":778,\"role\":\"group_leader\",\"department_id\":2}', '36.88.128.42', '2026-08-25 15:10:07'),
(1866, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-25 15:13:15'),
(1867, 1, NULL, 'user.logout', NULL, '36.88.128.42', '2026-08-25 15:58:20'),
(1868, 1, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-26 07:13:07'),
(1869, 673, 1177, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-SHE-03\",\"type\":\"SOP\",\"department\":\"SHE\"}', '36.88.128.42', '2026-08-26 13:32:47'),
(1870, 673, 1178, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-SHE-09\",\"type\":\"SOP\",\"department\":\"SHE\"}', '36.88.128.42', '2026-08-26 14:17:14'),
(1871, 673, 1178, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '36.88.128.42', '2026-08-26 15:08:28'),
(1872, 681, NULL, 'user.login', NULL, '2404:c0:4c3a:fe3b:b0a2:ef1b:abb7:11df', '2026-08-26 16:05:25'),
(1873, 769, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-26 16:07:38'),
(1874, NULL, NULL, 'user.register', '{\"user_id\":779,\"department_id\":\"6\"}', '202.125.100.29', '2026-08-26 16:12:18'),
(1875, 779, NULL, 'user.logout', NULL, '202.125.100.29', '2026-08-26 16:12:47'),
(1876, 712, NULL, 'user.login', NULL, '202.125.100.29', '2026-08-26 16:26:49'),
(1877, 712, 1179, 'document.create', '{\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"type\":\"JSA\",\"department\":\"PRODUKSI\"}', '36.88.128.42', '2026-08-26 16:27:59'),
(1878, 712, 1179, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '103.88.153.198', '2026-08-26 16:38:18'),
(1879, 1, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-26 16:40:21'),
(1880, 1, 1180, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '36.88.128.42', '2026-08-26 16:40:28'),
(1881, 673, 1179, 'document.review_start', NULL, '202.125.100.29', '2026-08-26 16:42:15'),
(1882, 673, 1179, 'document.review_reject', '{\"annotations\":33,\"ai_adopted\":0}', '36.88.128.42', '2026-08-26 17:04:00'),
(1883, 673, 1178, 'document.withdraw', NULL, '103.88.153.198', '2026-08-26 17:08:28'),
(1884, 673, 1178, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":0,\"no_revisi\":0}', '103.88.153.198', '2026-08-26 17:09:16'),
(1885, 686, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-26 17:11:29'),
(1886, 686, 1178, 'document.review_start', NULL, '202.125.100.29', '2026-08-26 17:11:59'),
(1887, 712, NULL, 'user.login', NULL, '36.88.128.42', '2026-08-27 11:48:09'),
(1888, 712, 1179, 'document.submit', '{\"status\":\"waiting_for_review\",\"round\":1,\"no_revisi\":0}', '103.88.153.198', '2026-08-27 11:58:31'),
(1889, 673, 1179, 'document.review_start', NULL, '36.88.128.42', '2026-08-27 13:07:52'),
(1890, 673, 1179, 'document.review_approve', NULL, '103.88.153.198', '2026-08-27 13:09:28'),
(1891, 681, 1179, 'document.approve', '{\"status\":\"published\",\"doc_number_final\":\"PPA-ADRO-JSA-PRODUKSI-01\"}', '103.88.153.198', '2026-08-27 13:17:27'),
(1892, 685, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-27 13:30:55'),
(1893, 678, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-27 13:33:38'),
(1894, 766, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-27 13:38:37'),
(1895, 667, NULL, 'user.login', NULL, '103.88.153.198', '2026-08-27 13:55:49'),
(1896, 667, 1174, 'document.delete', NULL, '36.88.128.42', '2026-08-27 13:56:32'),
(1897, 667, 1176, 'document.delete', NULL, '36.88.128.42', '2026-08-27 13:56:36'),
(1898, 667, 1181, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '36.88.128.42', '2026-08-27 14:06:08'),
(1899, 1, NULL, 'user.login', NULL, '2404:c0:c202:c237:5d5c:27c4:55fc:9bbc', '2026-08-27 21:19:58');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('smartpro-cache-17042174|36.88.128.42', 'i:2;', 1787731930),
('smartpro-cache-17042174|36.88.128.42:timer', 'i:1787731930;', 1787731930),
('smartpro-cache-17123080|202.125.100.29', 'i:1;', 1787455112),
('smartpro-cache-17123080|202.125.100.29:timer', 'i:1787455112;', 1787455112),
('smartpro-cache-18125700|202.125.100.29', 'i:1;', 1787551779),
('smartpro-cache-18125700|202.125.100.29:timer', 'i:1787551779;', 1787551779),
('smartpro-cache-20260219|2a04:4e41:76d5:d44d::65d5:d44d', 'i:1;', 1786635083),
('smartpro-cache-20260219|2a04:4e41:76d5:d44d::65d5:d44d:timer', 'i:1786635083;', 1786635083),
('smartpro-cache-22001345|36.88.128.42', 'i:1;', 1787640629),
('smartpro-cache-22001345|36.88.128.42:timer', 'i:1787640629;', 1787640629),
('smartpro-cache-220574|103.88.153.198', 'i:2;', 1787732099),
('smartpro-cache-220574|103.88.153.198:timer', 'i:1787732099;', 1787732099),
('smartpro-cache-230256|36.88.128.42', 'i:1;', 1787731910),
('smartpro-cache-230256|36.88.128.42:timer', 'i:1787731910;', 1787731910),
('smartpro-cache-250504|10.7.110.79', 'i:2;', 1786591535),
('smartpro-cache-250504|10.7.110.79:timer', 'i:1786591535;', 1786591535),
('smartpro-cache-250504|2a04:4e41:76d5:d44d::65d5:d44d', 'i:1;', 1786635098),
('smartpro-cache-250504|2a04:4e41:76d5:d44d::65d5:d44d:timer', 'i:1786635098;', 1786635098),
('smartpro-cache-250506|10.7.110.79', 'i:2;', 1786591542),
('smartpro-cache-250506|10.7.110.79:timer', 'i:1786591542;', 1786591542),
('smartpro-cache-514a0f0c1c4f09cacbad97d1af325d43c3f5bfbd', 'i:1;', 1786606361),
('smartpro-cache-514a0f0c1c4f09cacbad97d1af325d43c3f5bfbd:timer', 'i:1786606361;', 1786606361),
('smartpro-cache-c601973cb7fc01c255f027b5194c386eee1cda89', 'i:1;', 1786591118),
('smartpro-cache-c601973cb7fc01c255f027b5194c386eee1cda89:timer', 'i:1786591118;', 1786591118),
('smartpro-cache-gl-0001|36.88.128.42', 'i:2;', 1787361232),
('smartpro-cache-gl-0001|36.88.128.42:timer', 'i:1787361232;', 1787361232),
('smartpro-cache-libur_nasional:2026', 'a:0:{}', 1787888923),
('smartpro-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:19:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"document.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"document.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"document.submit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"document.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"document.review\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:7;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:16:\"document.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:7;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:16:\"document.publish\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"document.view_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;i:4;i:7;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:19:\"document.view_scope\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:17:\"document.view_all\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:8;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:11:\"user.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:25:\"user.approve_registration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"user.create_staff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:22:\"document.change_status\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:10:\"audit.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;i:5;i:8;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:25:\"document.request_revision\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:7;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:19:\"document.review_jsa\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:18:\"document.review_md\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:8;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:16:\"informasi.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}}s:5:\"roles\";a:7:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:8:\"admin_it\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"group_leader\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"section_head\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:15:\"departemen_head\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:8:\"pimpinan\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:5:\"staff\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:22:\"management_development\";s:1:\"c\";s:3:\"web\";}}}', 1787923199);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `alias`, `created_at`, `updated_at`) VALUES
(1, 'SHE', 'Safety, Health & Environment', NULL, '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(2, 'PLANT', 'Plant', NULL, '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(3, 'HCGA', 'Human Capital & General Affairs', NULL, '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(4, 'FAW-SCM', 'Finance, Accounting & Warehouse — Supply Chain Management', 'FALOG', '2026-07-11 16:37:14', '2026-07-30 20:18:21'),
(5, 'ICTMD', 'ICT & Management Development', NULL, '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(6, 'PRODUKSI', 'Produksi', NULL, '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(7, 'ENGINEERING', 'Engineering', NULL, '2026-07-11 16:37:14', '2026-07-11 16:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `doc_number_final` varchar(255) DEFAULT NULL,
  `doc_number` varchar(255) DEFAULT NULL,
  `doc_number_manual` tinyint(1) NOT NULL DEFAULT 0,
  `arsip_path` varchar(255) DEFAULT NULL,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('verifikasi_md','draft','waiting_for_review','in_review','rejected','pending_approval','published','sedang_direvisi','obsolete','submitted','needs_revision','archived') NOT NULL DEFAULT 'draft',
  `current_step` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `revision_round` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `no_revisi` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `revises_document_id` bigint(20) UNSIGNED DEFAULT NULL,
  `edisi` varchar(255) DEFAULT NULL,
  `is_controlled` tinyint(1) NOT NULL DEFAULT 1,
  `reviewer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `doc_number_final`, `doc_number`, `doc_number_manual`, `arsip_path`, `document_type_id`, `department_id`, `title`, `status`, `current_step`, `revision_round`, `no_revisi`, `revises_document_id`, `edisi`, `is_controlled`, `reviewer_id`, `approver_id`, `created_by`, `submitted_at`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1177, NULL, 'PPA-ADRO-SOP-SHE-03', 1, NULL, 1, 1, 'IDENTIFIKASI BAHAYA DAN PENILAIAN RISIKO', 'draft', 2, 0, 0, NULL, NULL, 1, NULL, NULL, 673, NULL, NULL, '2026-08-26 13:32:47', '2026-08-26 13:36:45', NULL),
(1178, NULL, 'PPA-ADRO-SOP-SHE-09', 1, NULL, 1, 1, 'AUDIT INTERNAL', 'in_review', 2, 0, 0, NULL, '1', 1, 686, 669, 673, '2026-08-26 17:09:16', NULL, '2026-08-26 14:17:14', '2026-08-26 17:11:59', NULL),
(1179, 'PPA-ADRO-JSA-PRODUKSI-01', 'PPA-ADRO-JSA-PRODUKSI-01', 0, NULL, 4, 6, 'Loading Material Debu', 'published', 2, 1, 0, NULL, '1', 1, 673, 681, 712, '2026-08-27 11:58:31', '2026-08-27 13:17:27', '2026-08-26 16:27:59', '2026-08-27 13:17:27', NULL),
(1181, NULL, 'PPA-ADRO-SOP-ICTMD-01', 1, NULL, 1, 5, 'STANDARISASI KOMPUTER & PEMELIHARAAN PERANGKAT SERTA INFRASTRUKTUR ICT', 'draft', 1, 0, 0, NULL, NULL, 1, NULL, NULL, 667, NULL, NULL, '2026-08-27 14:06:08', '2026-08-27 14:06:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_authors`
--

CREATE TABLE `document_authors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_authors`
--

INSERT INTO `document_authors` (`id`, `document_id`, `user_id`, `is_primary`, `created_at`, `updated_at`) VALUES
(1215, 1177, 673, 1, '2026-08-26 13:32:47', '2026-08-26 13:32:47'),
(1216, 1178, 673, 1, '2026-08-26 14:17:14', '2026-08-26 14:17:14'),
(1217, 1179, 712, 1, '2026-08-26 16:27:59', '2026-08-26 16:27:59'),
(1219, 1181, 667, 1, '2026-08-27 14:06:08', '2026-08-27 14:06:08');

-- --------------------------------------------------------

--
-- Table structure for table `document_contents`
--

CREATE TABLE `document_contents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_contents`
--

INSERT INTO `document_contents` (`id`, `document_id`, `section_key`, `value_json`, `created_at`, `updated_at`) VALUES
(702, 1177, 'tujuan', '[\"Memastikan proses identifikasi bahaya, penilaian risiko, dan pengendalian risiko dilaksanakan secara sistematis, terdokumentasi, dan konsisten di seluruh area operasional perusahaan.\"]', '2026-08-26 13:33:05', '2026-08-26 14:05:31'),
(703, 1177, 'ruang_lingkup', '[\"Prosedur ini dapat digunakan oleh seluruh karyawan PT Putra Perkasa Abadi, baik yang berada di project \\/ site maupun Workshop dan sub-kontraktor. Cakupan prosedur ini dimulai dari pelaksanaan identifikasi, penilaian Risiko yang berkaitan dengan bahaya yang ada dan pengendalian Risiko melalui tingkat pengendalian (hierarchy of control) standar yang telah ditentukan atau yang disebut dengan Manajemen Risiko dan juga pembuatan Job Safety Analysis (JSA)\\/ analisa pekerjaan berwawasan K3.\"]', '2026-08-26 13:33:05', '2026-08-26 13:33:57'),
(704, 1177, 'referensi', '[\"Undang-Undang No. 1 Tahun 1970 Tentang Keselamatan Kerja\"]', '2026-08-26 13:33:05', '2026-08-26 14:05:31'),
(705, 1177, 'definisi', '[\"Manajemen Risiko adalah Suatu proses manajemen yang dilakukan dengan maksud meminimalkan Risiko atau sedapat mungkin menghindari sama sekali Risiko tersebut.\"]', '2026-08-26 13:33:05', '2026-08-26 14:05:31'),
(706, 1177, 'flowchart', NULL, '2026-08-26 13:38:30', '2026-08-26 13:38:30'),
(707, 1177, 'aktivitas', '[{\"sub_judul\":\"Prinsip Dasar\",\"deskripsi\":\"a. Jika memungkinkan, hindari Risiko secara total dengan menggunakan metode atau material alternatif.\\r\\nb. Hilangkan Risiko pada sumbernya dibandingkan dengan mengukurnya, karena akan tetap meninggalkan Risiko di lokasi. Akan tetapi, tetaplah mencegah terjadi kontak dengan Risiko.\\r\\nc. Bila memungkinkan, sesuaikan pekerjaan dengan kondisi karyawan terutama dalam memilih peralatan dan metode kerja.\\r\\nd. Keuntungan dari perkembangan teknologi yang sering menawarkan kesempatan yang lebih aman dan metode kerja yang lebih efisien.\\r\\ne. Masukkan tindakan pencegahan kedalam perencanaan yang saling berkaitan untuk mengurangi Risiko yang tidak dapat dihindari dan terdapat dalam kondisi pekerjaan, faktor organisasi, lingkungan kerja dan faktor sosial.\\r\\nf. Berikan prioritas terhadap tindakan-tindakan tersebut yang dapat melindungi karyawan atau aktivitasnya dan memberikan keuntungan yang besar, misalnya berikan tindakan perlindungan yang menyeluruh seperti penyediaan platform yang sesuai dengan proteksi samping, terutama lagi adalah perlindungan individual seperti safety harness.\\r\\ng. Adakan pengawasan dan karyawan harus mengerti apa yang mereka harus lakukan misalnya melalui pelatihan, komunikasi, instruksi, dsb.\\r\\nh. Monitor tindakan pengendalian secara teratur untuk menentukan efektivitas Manajemen Risiko.\\r\\ni. Review dan revisi Manajemen Risiko yang telah dilakukan jika terdapat pengembangan sehingga yang lama sudah tidak valid lagi. Dalam banyak hal, sangatlah baik merencanakan review yang bergantung pada sumber bahaya dan seberapa sering metode kerja berubah.\",\"pic\":\"All Departemen\"},{\"sub_judul\":\"Identifikasi Bahaya\",\"deskripsi\":\"a. Jenis Bahaya\\r\\n1) Bahaya kimia\\r\\nKimia dapat mempengaruhi kulit melalui kontak atau mempengaruhi badan baik melalui sistem pencernaan atau melalui paru-paru jika udara terkontaminasi dengan kimia, asap atau debu. Akan terjadi dampak yang akut\\/mendadak (misalnya karyawan terkontaminasi dengan tiba-tiba) atau dapat terjadi dampak yang kronis (misalnya karyawan terkontaminasi dalam jangka waktu yang sedang atau cukup lama).\\r\\n\\r\\n2) Bahaya fisik, seperti :\\r\\n\\u2022 Bahaya kebisingan\\r\\n\\u2022 Bahaya pencahayaan yang kurang baik\\r\\n\\u2022 Bahaya vibrasi\\/ getaran\\r\\n\\u2022 Bahaya temperatur kerja\\r\\n\\u2022 Bahaya listrik\\r\\nBahaya ini termasuk Risiko dari cidera yang berasal dari semua bentuk energi listrik.\\r\\n\\r\\n3) Bahaya Radiasi\\r\\n\\u2022 Radiasi ion terdapat dalam sejumlah peralatan seperti peralatan pengukuran radioaktif, sumber radiografi atau unsur pelacak radioaktif yang digunakan dalam kimia analis.\\r\\n\\u2022 Radiasi non ion seperti radiasi infra-red (proses yang menghasilkan panas), laser, radiasi ultraviolet (pengelasan, sinar matahari) dan gelombang mikro (mesin las yang menggunakan frekuensi yang tinggi, dsb).\\r\\n\\r\\n4) Bahaya Biologi\\r\\nTermasuk serangga, bakteri, jamur, tanaman, kutu, binatang dan virus.\\r\\n\\r\\n5) Bahaya ergonomic\\/Biomekanik\\r\\nTermasuk Risiko dari cidera posisi kerja, pengangkutan manual, gerakan berulang serta ergonomi tempat kerja\\/alat\\/mesin). Dengan menerapkan prosedur pengangkatan secara manual\\/ manual handling, desain tempat kerja yang tidak sesuai, dsb.\\r\\n\\r\\n6) Bahaya Psikis\\/social\\r\\nTermasuk stres, kelelahan, pengaruh kerja shift dan bahkan serangan fisik terhadap karyawan lain.\\r\\n\\r\\n\\r\\nb. Identifikasi Bahaya Berdasarkan Tempat Kerja.\\r\\n1) Tempat kerja yang tetap\\/ tidak berpindah\\u2013pindah sebaiknya menggunakan identifikasi bahaya berdasarkan tempat kerja dan mengidentifikasinya dengan melakukan pengamatan langsung secara detail bagian-bagian tempat kerja yang berbeda.\\r\\n2) Rincian tahapan pelaksanaan prosesnya adalah sebagai Berikut :\\r\\n\\u2022 Dapatkan rencana yang pasti dan terbaru\\/ up to date dari rencana project\\/ site\\/tempat kerja yang baru atau perubahan\\/ pengembangan.\\r\\n\\u2022 Gambarkan diagram alir dari proses kerja\\/ business process yang termasuk dalam ruang lingkup kerja project\\/ site\\/ tempat kerja.\\r\\n\\u2022 Bagi tempat kerja menjadi beberapa bagian dan urutkan. Bagian-bagian ini dapat dibedakan dari bagaimana pekerjaan tersebut dilakukan atau berdasarkan lay out di lapangan. Misalnya sebuah \\u201cpower plant\\/ ruang pembangkit\\u201d, \\u201cdaerah gudang\\/ store area\\u201d, \\u201cbengkel\\/ workshop\\u201d, \\u201ckantor\\u201d, dsb.\\r\\n\\u2022 Tanya langsung kepada karyawan\\/ staff dari bagian yang diidentifikasi untuk membuat daftar bahaya apa saja yang mungkin timbul dari tempat kerjanya dan tanyakan mengapa hal tersebut merupakan bahaya yang potensial. Gunakan form pengumpulan data yaitu Form \\u201cIdentifikasi Bahaya, Penilaian dan Pengendalian Risiko\\/HIRA = Hazard Identification Risk Assesment\\u201d. (PPA-ADRO-F-SHE-03A)\\r\\n\\u2022 Bila semua data mengenai bahaya telah teridentifikasi maka disarankan untuk mengadakan pertemuan daripada hanya menanganinya secara sendiri. Pada saat ini, tingkat keparahan dari bahaya yang muncul tidak dimunculkan terlebih dahulu.\\r\\n\\u2022 Untuk memudahkan dalam pelaksanaan identifikasi bahaya ini, gunakan informasi yang tersedia. Hal ini dapat berasal dari sumber-sumber berikut : panduan pemakaian\\/code of practice, bahan informasi mengenai project\\/ site, laporan internal dan eksternal, laporan keluhan, laporan pemantauan lingkungan dan kesehatan, MSDS, laporan inspeksi, dsb. Catatan laporan kecelakaan dan insiden\\/near miss juga dapat digunakan pada suatu project\\/site tertentu atau diantara industri pertambangan secara keseluruhan.\\r\\n\\u2022 Identifikasi Bahaya Berdasarkan Analisa Pekerjaan.\\r\\n\\r\\n\\r\\nc. Identifikasi Bahaya Berdasarkan Jenis Pekerjaan.\\r\\nPekerjaan yang tidak dilakukan pada tempat kerja yang menetap maka sebaiknya dianalisa dengan menggunakan jenis pekerjaan yang lain yaitu identifikasi bahaya berdasarkan analisa pekerjaan dan mempertimbang beberapa yang berasal dari internal dan eksternal.\\r\\n1) Penentuan faktor internal diantaranya :\\r\\n\\u2022 Kegiatan dan proses rutin dan tidak rutin.\\r\\n\\u2022 Perubahan-perubahan pada organisasi, lingkungan kerja, kegiatan, atau bahan\\/material.\\r\\n\\u2022 Modifikasi pada sistem manajemen Keselamatan Pertambangan, termasuk perubahan-perubahan sementara, serta dampak pada operasi, proses, dan kegiatan.\\r\\n\\u2022 Fasilitas yang baru dibangun, peralatan atau proses yang baru diperkenalkan, serta kegiatan dan instalasi Perusahaan Jasa Pertambangan di dalam lokasi kerja.\\r\\n\\u2022 Kondisi normal dan abnormal dan\\/ atau kondisi proses serta potensi insiden dan keadaan darurat selama siklus pemakaian produk dan\\/ atau siklus lamanya proses.\\r\\n\\u2022 Ketidakpatuhan terhadap rekomendasi sebelumnya, standar dan\\/ atau prosedur Keselamatan Pertambangan yang ada, atau ketidakpatuhan terhadap tindak lanjut rekomendasi insiden.\\r\\n\\u2022 Faktor personal pekerja.\\r\\n\\u2022 Desain area kerja, proses, instalasi, peralatan, prosedur operasi dan organisasi kerja, termasuk kemampuan adaptasi manusia.\\r\\n\\u2022 Sistem dan pelaksanaan pemeliharaan perawatan sarana, prasarana instalasi, dan peralatan pertambangan.\\r\\n\\u2022 Pengamanan instalasi.\\r\\n\\u2022 Kelayakan sarana, prasarana, instalasi, serta peralatan pertambangan.\\r\\n\\u2022 Kompetensi tenaga teknik, dan\\r\\n\\u2022 Evaluasi laporan hasil kajian teknis pertambangan.\\r\\n2)Sedangkan penentuan faktor eksternal mempertimbangkan :\\r\\n\\u2022 Budaya, politik, hukum, keuangan, teknologi, ekonomi, alam dan lingkungan yang kompetitif secara lokal, nasional, regional, dan internasional.\\r\\n\\u2022 Pendorong utama dan perkembangan isu yang berdampak terhadap tujuan organisasi.\\r\\n\\u2022 Persepsi dan nilai-nilai dari para pemangku kepentingan eksternal.\\r\\n\\u2022 Kegiatan semua orang yang memiliki akses ke tempat kerja, termasuk yang dilakukan oleh Perusahaan Jasa Pertambangan dan para tamu.\\r\\n\\u2022 Fasilitas yang baru dibangun, peralatan atau proses yang baru diperkenalkan, serta kegiatan dan instalasi Perusahaan Jasa Pertambangan diluar lokasi kerja.\\r\\n\\u2022 Bahaya-bahaya teridentifikasi yang berasal dari luar lokasi kerja, yang dapat membahayakan keselamatan dan kesehatan orang di tempat kerja yang berada dalam kendali perusahaan.\\r\\n\\u2022 Infrastruktur, peralatan, dan bahan-bahan ditempat kerja, yang disediakan oleh pihak lain.\\r\\n\\u2022 Kewajiban hukum yang berkaitan dengan identifikasi bahaya dan penilaian Risiko serta pengendalian yang diperlukan.\\r\\n\\u2022 Hal-hal lain yang mempengaruhi Keselamatan Pertambangan\\r\\n3) Rincian tahapan pelaksanaan prosesnya adalah sebagai berikut:\\r\\n\\u2022 Identifikasi semua pekerjaan yang dilakukan oleh karyawan. Sebuah pekerjaan terdiri dari sejumlah langkah-langkah\\/tahapan-tahapan yang dilakukan untuk menyelesaikan suatu pekerjaan tersebut. Proses identifikasi pekerjaan dapat dimulai dengan menanyakan karyawan tentang apa mereka lakukan secara spesifik mulai dari awal sampai dengan pekerjaan tersebut selesai. Pekerjaan tersebut seharusnya dipecah\\/ diuraikan menjadi beberapa tahapan dasar\\/ langkah yang sederhana untuk dianalisa.\\r\\n\\u2022 Diskusikan dan kemudian buat daftar dari langkah-langkah\\/ tahapan dasar tersebut.\\r\\n\\u2022 Tanya karyawan yang terlibat langsung dengan pekerjaan tersebut apakah langkah-langkah\\/ tahapan dasar tersebut memang benar-benar mereka lakukan dalam pekerjaan sehari-hari dan catat hasilnya.\\r\\n\\u2022 Untuk memudahkan dalam pelaksanaan identifikasi bahaya ini, gunakan informasi yang tersedia.\\r\\n4) Daftar rinci mengenai bahaya yang teridentifikasi haru menggambarkan bahaya yang spesifik terhadap area kerja, aktivitas tempat\\/ proses kerja di dalam tempat kerja.\\r\\n\\u2022 Semua informasi yang didapat, harus dicatat dalam Formulir Identifikasi Bahaya, Penilaian dan Penetapan Pengendalian PPA-F-SHE-03A.\\r\\n\\r\\n\\r\\nd. Penilaian Risiko\\r\\n1) Menentukan Risiko\\r\\n\\u2022 Risiko yang ditentukan dari bahaya yang teridentifikasi, ditentukan dengan memperkirakan akibat dan kemungkinan yang mungkin terjadi.\\r\\n\\u2022 Dari satu bahaya yang telah teridentifikasi maka dimungkinkan terdapat satu atau lebih Risiko yang mungkin.\\r\\n2) Risiko dihitung secara matriks dengan berdasarkan pada tabel berikut : (Berdasarkan Standar Australia\\/New Zealand No.4360 tahun 2004 tentang Risk Management).\",\"pic\":null}]', '2026-08-26 13:38:30', '2026-08-26 14:02:55'),
(708, 1177, 'lampiran', '[]', '2026-08-26 13:38:30', '2026-08-26 13:38:30'),
(709, 1178, 'tujuan', '[\"Prosedur dibuat sebagai petunjuk pelaksanaan audit internal dan untuk memastikan kegiatan audit internal dilaksanakan sesuai proses audit yang benar, baik untuk sistem manajemen keselamatan, kesehatan kerja pertambangan, lingkungan hidup, mutu dan energi (K3PLM).\"]', '2026-08-26 14:17:36', '2026-08-26 14:17:36'),
(710, 1178, 'ruang_lingkup', '[\"Prosedur ini dipergunakan sebagai petunjuk audit internal penerapan sistem manajemen terintegrasi K3PLM yang diimplementasikan di lingkungan PT Putra Perkasa Abadi : Audit internal SMK3, Audit internal SMKP dan Audit internal ISO Series.\"]', '2026-08-26 14:17:36', '2026-08-26 14:24:08'),
(711, 1178, 'referensi', '[\"PP No. 50 Tahun 2012 Tentang Penerapan SMK3, Elemen 11.1 Audit Internal\",\"Keputusan Menteri ESDM No.1827 K\\/30\\/MEM\\/2018 Elemen 5.6 Audit Internal\",\"Keputusan Direktur Jenderal Minerba Kementerian ESDM No. 185.K\\/37.04\\/DJB\\/2019 Tentang Petunjuk Teknis Pelaksanaan Keselamatan Pertambangan dan Pelaksanaan, Penilaian, dan Pelaporan Sistem Manajemen Keselamatan Pertambangan dan Mineral.\",\"ISO 9001: 2015 Klausul 9.2 Audit Internal\",\"ISO 14001: 2015 Klausul 9.2 Audit Internal\",\"ISO 45001: 2018 Klausul 9.2 Audit Internal\",\"ISO 50001: 2018 Klausul 9.2 Audit Internal\",\"ISO 19011: 2018 Pedoman Audit Sistem Manajemen\",\"PPA-HO-MAN-MD-001 - Manual Sistem Manajemen Terintegrasi Edisi Revisi 3\"]', '2026-08-26 14:17:36', '2026-08-26 14:25:00'),
(712, 1178, 'definisi', '[\"Audit adalah proses yang sistematik, mandiri dan terdokumentasi untuk memperoleh bukti objektif dan mengevaluasi bukti tersebut secara objektif untuk menentukan sejauh mana kriteria audit dipenuhi.\",\"Audit internal, kadang disebut audit pihak pertama, dilaksanakan oleh, atau atas nama organisasi itu sendiri.\",\"Program audit adalah pengaturan satu atau lebih audit yang direncanakan dalam jangka waktu tertentu dan diarahkan untuk tujuan spesifik.\",\"Rencana audit adalah uraian tentang kegiatan dan pengaturan audit.\",\"Kriteria audit adalah seperangkat persyaratan yang digunakan sebagai acuan pembanding terhadap bukti objektif.\",\"Bukti audit adalah rekaman, pernyataan mengenai fakta atau informasi lain yang terkait dengan kriteria audit dan dapat diverifikasi.\",\"Bukti objektif adalah data yang mendukung keberadaan atau kebenaran sesuatu. Bukti objektif dapat diperoleh melalui pengamatan, pengukuran, pengujian atau cara lain. Bukti objektif untuk tujuan audit umumnya terdiri dari rekaman, pernyataan tentang fakta, atau informasi lain yang relevan terhadap kriteria audit dan dapat diverifikasi.\",\"Temuan audit adalah hasil evaluasi bukti audit yang dikumpulkan dibandingkan dengan kriteria audit. Temuan audit mengindikasikan kesesuaian atau ketidaksesuaian.\",\"Kesesuaian adalah pemenuhan suatu persyaratan.\",\"Ketidaksesuaian adalah tidak terpenuhinya suatu persyaratan.\",\"Persyaratan adalah kebutuhan atau harapan yang dinyatakan, umumnya tersirat atau wajib. \\\"Umumnya tersirat\\u201d berarti hal ini merupakan kebiasaan atau praktik umum organisasi dan pihak berkepentingan sesuai kebutuhan dan harapan yang dipertimbangkan tersirat. Persyaratan yang ditetapkan merupakan sesuatu yang dinyatakan, contoh dalam informasi terdokumentasi.\",\"Lead Auditor adalah auditor yang mengatur tata laksana pelaksanaan audit.\",\"Auditor adalah orang yang kompeten untuk melaksanakan audit.\",\"Auditi adalah orang yang bertanggungjawab pada bagian yang diaudit.\",\"Temuan audit adalah hasil dari audit yang menunjukkan kesesuaian atau ketidaksesuaian terhadap persyaratan serta saran untuk peningkatan\\/ perbaikan.\",\"Manajemen adalah Penanggung Jawab Operasional, Departemen dan\\/ atau Section Head Departemen.\",\"Kepala Bagian adalah pimpinan tertinggi departemen yang dapat berupa Departemen Head atau Section Head.\"]', '2026-08-26 14:17:36', '2026-08-26 14:27:18'),
(713, 1178, 'flowchart', '[]', '2026-08-26 14:28:23', '2026-08-26 15:08:28'),
(714, 1178, 'aktivitas', '[{\"sub_judul\":\"Perencanaan Audit Internal\",\"deskripsi\":\"a. Program Audit Internal\\r\\nPerencanaan program audit harus mempertimbangkan status, pentingnya proses dan area yang diaudit termasuk hasil-hasil audit sebelumnya.\\r\\nKegiatan audit internal dilaksanakan setiap 1 tahun sekali. Apabila terdapat hal-hal tertentu yang penting dan\\/ atau adanya kehendak dari manajemen, dimungkinkan untuk dilaksanakan audit internal di luar jadwal yang telah ditetapkan.\\r\\nPenanggungjawab dan pelaksana audit internal yang bukan merupakan bagian dari penerapan sistem manajemen keselamatan dan kesehatan kerja, lingkungan, mutu dan energi ditentukan oleh manajemen sesuai dengan sasaran yang akan dicapai.\\r\\nb. Kriteria dan Ruang Lingkup Audit\\r\\nKriteria yang digunakan sebagai acuan pada pelaksanaan audit internal adalah persyaratan standar sistem manajemen baik manajemen keselamatan dan kesehatan kerja, lingkungan, mutu dan energi yang diterapkan serta persyaratan yang ditetapkan oleh internal perusahaan yang berlaku pada lingkup PT Putra Perkasa Abadi group.\\r\\nc. Metode Audit\\r\\nMetode yang digunakan pada pelaksanaan kegiatan audit internal dapat melalui tinjauan dokumen, wawancara dan observasi.\\r\\nd. Tujuan audit internal\\r\\nAdapun tujuan dari pelaksanaan audit internal antara lain sebagai berikut :\\r\\n1) Memastikan kesesuaian system manajemen terintegrasi memenuhi persyaratan standar yang telah diterapkan terhadap kebijakan perusahaan, prosedur dan persyaratan-persyaratan yang telah ditetapkan.\\r\\n2) Sebagai sarana perbaikan sistem manajemen yang diterapkan.\\r\\n3) Menyediakan informasi hasil audit internal bagi pihak-pihak yang berkepentingan.\",\"pic\":\"All Departemen\"},{\"sub_judul\":\"Persiapan Audit Internal\",\"deskripsi\":\"a. Langkah-langkah yang harus dilakukan sebelum melaksanakan kegiatan audit internal antara lain sebagai berikut :\\r\\n1) Rapat manajemen untuk menentukan tim auditor, waktu dan lamanya kegiatan audit. Dalam menentukan waktu lamanya kegiatan audit dan jumlah anggota tim auditor harus disesuaikan dengan program audit yang akan berjalan dan kondisi lapangan yang sedang berlangsung.\\r\\n2) Rapat koordinasi antara manajemen dengan tim auditor.\\r\\n3) Menyusun jadwal audit internal untuk tiap departemen atau area.\\r\\n\\r\\nb. Kriteria auditor internal adalah memiliki kompetensi internal audit, baik audit system manajemen terintegrasi dan\\/ atau internal audit SMKP sesuai dengan jenis audit yang akan dilakukan.\\r\\nc. Pemilihan auditor dan pelaksanaan audit harus memelihara obyektifitas dan ketidakberpihakan proses audit.\\r\\nd. Tim auditor yang bertugas untuk melaksanakan audit selanjutnya mempersiapkan dan mengumpulkan informasi untuk area yang akan diaudit, seperti:\\r\\n1) Standar operasional prosedur\\r\\n2) Hasil audit sebelumnya\\r\\n3) Jadwal audit\\r\\n4) Umpan balik pelanggan, berupa kepuasan maupun keluhan pelanggan\\r\\n5) Pembuatan daftar periksa \\/ cheklist audit\\r\\n6) Kelengkapan lainnya.\\r\\n\\r\\ne. Manajemen memastikan bahwa tim Auditi telah siap untuk melaksanakan audit sesuai jadwal termasuk kelengkapan yang diperlukan.\",\"pic\":\"All Departemen\"},{\"sub_judul\":\"Pelaksanaan Audit Internal\",\"deskripsi\":\"a. Pertemuan pembukaan\\r\\nSebelum memulai audit, auditor memberikan penjelasan kepada Auditi tentang audit yang akan dilaksanakan, meliputi :\\r\\n1) Tujuan audit\\r\\n2) Metodologi Audit\\r\\n3) Konfirmasi jadwal dan ruang lingkup audit\\r\\n4) Proses audit\\r\\n5) Tanya jawab (Bila ada)\\r\\n\\r\\nb. Mengumpulkan dan melakukan verifikasi informasi,\\r\\nDengan menggunakan daftar periksa\\/ cheklist yang telah dipersiapkan, auditor mengumpulkan dan memverifikasi informasi yang diperoleh. Pengumpulan informasi diperoleh melalui beberapa langkah berikut :\\r\\n1) Wawancara dengan personel yang bertanggung jawab.\\r\\n2) Observasi lapangan (melihat bagaimana penanggungjawab dan personel lainnya melakukan pekerjaannya). Memeriksa dokumen (Manual, prosedur, instruksi kerja, standar parameter, spesifikasi dan catatan-catatan) untuk memastikan kesesuaiannya dengan ketentuan yang diharuskan dalam dokumen.\\r\\n\\r\\nc. Mencatat bukti obyektif dan temuan ketidaksesuaian\\r\\nMencatat bukti obyektif yang diperiksa termasuk jika terdapat temuan ketidaksesuaian dalam pelaksanaan audit internal. Dalam mencatat bukti obyektif harus secara jelas dan lengkap sehingga sampel dapat ditemukan kembali apabila diperlukan dan kemudahan bagi Auditi untuk melakukan perbaikan. Adapun kriteria ketidaksesuaian atau temuan dalam pelaksanaan audit internal system terintegrasi mutu, lingkungan, K3 dan energi adalah sebagai berikut :\\r\\n1) Temuan mayor merupakan ketidaksesuaian yang berpotensi menimbulkan dampak serius terhadap mutu, efektivitas sistem manajemen (Mutu, Lingkungan, K3, dan Energi), atau ketidakpatuhan total terhadap isi dokumen.\\r\\n2) Temuan minor merupakan ketidaksesuaian yang tidak berdampak serius terhadap mutu, lingkungan, K3, dan energi, maupun efektivitas sistem manajemen; atau ketidakpatuhan hanya pada sebagian isi dokumen.\\r\\n3) Observasi merupakan saran-saran dari auditor yang disetujui Auditi untuk perbaikan sistem manajemen mutu, lingkungan, K3 dan Energy.\\r\\nSedangkan kriteria ketidaksesuaian atau temuan dalam penerapan SMK3 & SMKP Sebagai berikut :\\r\\n1) Kategori kritikal\\r\\nTemuan yang dapat mengakibatkan fatality\\/ kematian.\\r\\n2) Kategori mayor\\r\\na) Pada hasil pemeriksaan elemen ditemukan sub elemen yang nilainya kurang dari 50% (lima puluh persen) nilai maksimum sub elemen tersebut.\\r\\n3) Kategori minor\\r\\nKetidak sesuaian dalam pemenuhan persyaratan peraturan perundang undangan, standar, pedoman dan acuan lainnya.\\r\\n\\r\\nd. Pertemuan penutup\\r\\nApabila kegiatan audit telah selesai, maka dilakukan pertemuan penutup yang mencakup tetapi tidak terbatas pada hal-hal berikut :\\r\\n1) Penyampaian hasil-hasil audit terutama ketidaksesuaian dari auditor\\r\\n2) Mengklarifikasi temuan audit yang tidak sesuai (bila ada)\\r\\n3) Menyelesaikan perbedaan pendapat atau persepsi (bila ada).\\r\\n4) Dalam hal terdapat temuan ketidaksesuaian, maka manajemen wajib memastikan dilakukan adanya tindakan perbaikan\\r\\n5) Mendiskusikan penyebab ketidaksesuaian dan menetapkan bentuk tindakan perbaikan serta batas waktu untuk penyelesaian tindakan perbaikan pada formulir permintaan tindakan perbaikan.\\r\\n6) Auditor dapat memberikan rekomendasi jika diperlukan untuk perbaikan ke arah sistem manajemen yang lebih baik.\\r\\nDalam hal terdapat perbedaan pendapat atau persepsi atas hasil-hasil audit yang tidak terselesaikan, maka permasalahannya didiskusikan antara Tim Auditor dan manajemen untuk mendapatkan penyelesaiannya.\",\"pic\":\"All Departemen\"},{\"sub_judul\":\"Laporan Hasil Audit\",\"deskripsi\":\"a. Auditor melaporkan dan menyerahkan hasil audit yang dilakukan melalui rekapitulasi ketidaksesuaian audit.\\r\\nb. Kepala Bagian dan Auditi melakukan verifikasi terhadap temuan ketidaksesuaian yang disampaikan oleh auditor.\",\"pic\":\"All Departemen\"},{\"sub_judul\":\"Tindak Lanjut Hasil Audit\",\"deskripsi\":\"a. Manajemen wajib melakukan tindak lanjut atas temuan audit,\\r\\nb. Tindak lanjut temuan audit diawali dengan penentuan akar masalah yang menjadi penyebab utama adanya suatu ketidaksesuaian,\\r\\nc. Setelah akar masalah ditentukan, disusun rencana tindakan perbaikan dan batas waktu yang sesuai dengan akar masalah tersebut,\\r\\nd. Akar masalah, tindakan perbaikan dan bukti tindakan perbaikan dicatat pada formulir rekapitulasi dan diverifikasi oleh auditor,\\r\\ne. Auditor melakukan verifikasi dengan memberikan status tindakan perbaikan melalui beberapa kriteria berikut,\\r\\n1) Open, yang berarti bahwa tindakan perbaikan belum dilakukan atau belum sesuai\\r\\n2) Close, yang berarti bahwa tindakan perbaikan telah dilakukan dan telah menyelesaikan akar permasalahan yang ada.\\r\\nf. Dalam melakukan verifikasi, auditor melihat bukti-bukti obyektif atas hasil tindakan perbaikan.\\r\\ng. Dalam hal sampai batas waktu yang telah ditentukan untuk penyelesaian tindakan perbaikan tidak terpenuhi, maka perlu dilakukan kajian kembali pada rencana tindakan perbaikan yang paling efektif dan reliable.\\r\\nh. Laporan ketidaksesuaian dan tindakan perbaikan yang telah diverifikasi oleh auditor kemudian harus mendapatkan persetujuan dari PJO sebagai bukti temuan tersebut telah diselesaikan.\",\"pic\":\"All Departemen\"}]', '2026-08-26 14:28:23', '2026-08-26 15:00:11'),
(715, 1178, 'lampiran', '[{\"dokumen\":\"PPA-ADRO-F-SHE-09A Formulir Checklist Audit Internal\",\"keterangan\":null},{\"dokumen\":\"PPA-ADRO-F-SHE-09B Formulir Agenda Audit Internal\",\"keterangan\":null},{\"dokumen\":\"PPA-ADRO-F-SHE-09C Formulir Rekapitulasi Ketidaksesuaian dan Tindakan Perbaikan Audit Internal\",\"keterangan\":null},{\"dokumen\":\"PPA-ADRO-F-SHE-09D Formulir Permintaan Tindakan Perbaikan Audit Internal\",\"keterangan\":null},{\"dokumen\":\"PPA-ADRO-F-SHE-09E Formulir Matriks Referensi Silang SMK3PLM\",\"keterangan\":null}]', '2026-08-26 14:28:23', '2026-08-26 15:05:40'),
(716, 1179, 'lokasi_kerja', '\"Pit Wara\"', '2026-08-26 16:28:11', '2026-08-26 16:28:11'),
(717, 1179, 'apd', '[\"Helm\",\"Sepatu Safety\",\"Baju Reflektor\"]', '2026-08-26 16:28:11', '2026-08-26 16:28:31'),
(718, 1179, 'tools', '[\"A2B\"]', '2026-08-26 16:28:11', '2026-08-26 16:28:36'),
(719, 1179, 'form_tgl_efektif', '\"2026-08-26\"', '2026-08-26 16:28:11', '2026-08-26 16:28:41'),
(720, 1179, 'analisa', '[{\"langkah\":\"Inspeksi Area Front Loading Oleh Pengawas\",\"bahaya\":[{\"risiko\":\"Pengawas Terjatuh\\/Tegelincir\\/Terperosok\",\"pengendalian\":[\"Berpijak hanya pada area yang keras dan padat saat melakukan inspeksi\",\"Gunakan jalur inspeksi yang aman dengan beda tinggi maksimal 1 meter\"]},{\"risiko\":\"Pengawas Tertimpa Material\",\"pengendalian\":[\"Lakukan Inspeksi area pada jarak aman 1,5 x tinggi tebing\",\"Lakukan Inspeksi area diluar jalur lintasan unit HD untuk menghindari adanya lentingan material dari vessel\"]},{\"risiko\":\"Pengawas Tertabrak Unit A2B\",\"pengendalian\":[\"Lakukan Inspeksi diluar area aktivitas unit A2B\",\"Lakukan komunikasi 2 arah menggunakan Radio dua arah pada Channel Radio PPA Front\"]}]},{\"langkah\":\"Excavator membuat dudukan untuk loading\",\"bahaya\":[{\"risiko\":\"Excavator amblas\",\"pengendalian\":[\"Cek tingkat kekerasan dan kepadatan material dengan cara menancapkan teeth bucket sebelum unit bergerak\",\"Ikuti Instruksi pengawas di area front untuk membuat dudukan exca dari sisi yang sudah di inspeksi dan aman\"]}]},{\"langkah\":\"Melakukan Pengumpulan Debu\",\"bahaya\":[{\"risiko\":\"Jarak Pandang Terbatas\",\"pengendalian\":[\"Lakukan penyiraman debu menggunakan Water Truck (WT) secara continue\",\"Buat dan bersihkan parit aliran air menuju lowest point secara berkala untuk mencegah base lembab\\/berair.\"]},{\"risiko\":\"Pandangan terbatas saat aktivitas di malam hari\",\"pengendalian\":[\"Posisikan dan arahkan Tower Lamp ke area kerja malam hari untuk memastikan tingkat pencahayaan minimal 5 Lux.\"]}]},{\"langkah\":\"Melakukan Pembentukan front loading\",\"bahaya\":[{\"risiko\":\"Unit A2B Amblas\",\"pengendalian\":[\"Lakukan layering base front menggunakan good material untuk membuat base yang keras dan padat\",\"Pasang rambu\\/barikade penanda pada area yang belum siap dilalui unit hauler\"]}]},{\"langkah\":\"Unit HD memasuki front dan melakukan manuver\",\"bahaya\":[{\"risiko\":\"Unit HD amblas\",\"pengendalian\":[\"Jika terdapat area lembek lakukan layering base front menggunakan good material untuk membuat base yang keras\",\"Kontrol drainase menuju lowest point agar base tidak menjadi lembab dan berair\",\"Komunikasi 2 arah Di Chanel Radio PPA Front dengan pengawas dan unit A2B lain selama aktivitas loading\",\"Pasang rambu\\/barikade penanda pada area yang belum siap dilalui unit hauler\"]},{\"risiko\":\"Unit HD menabrak dozer\",\"pengendalian\":[\"Komunikasi 2 arah Di Chanel Radio PPA Front dengan pengawas dan unit A2B lain selama aktivitas loading\",\"Posisikan unit bulldozer di luar area manuver HD\",\"Posisikan unit Bulldozer pada area yang bukan blindspot dari unit HD\",\"Berikan isyarat klaskon saat akan maju\\/mundur saat melakukan manuver\"]},{\"risiko\":\"Unit HD menabrak Excavator\",\"pengendalian\":[\"Posisikan vessel pada posisi bucket excavator saat mundur setalah melakukan manuver\",\"Komunikasi 2 Di Chanel Radio Front arah dengan pengawas dan unit A2B lain selama aktivitas loading di area front\",\"Gunakan isyarat klakson jika HD sudah mundur diposisi yang tepat\"]},{\"risiko\":\"Unit HD terperosok pada area beda tinggi\",\"pengendalian\":[\"Bentuk tanggul dengan tinggi 3\\/4 tinggi tyre HD menggunakan material dari front tersebut\"]},{\"risiko\":\"Pengawas Tertabrak Unit A2B\",\"pengendalian\":[\"Lakukan pengawasan diluar aktivitas unit A2B\",\"Komunikasi 2 arah Di Chanel Radio Front antara unit A2B dengan pengawas\"]}]},{\"langkah\":\"Loading material Debu\",\"bahaya\":[{\"risiko\":\"Unit HD amblas\",\"pengendalian\":[\"Sesuaikan muatan agar material debu tidak tumpah\",\"Gunakan isyarat klakson setelah muatan penuh agar HD dapat bergerak maju\",\"Komunikasi 2 arah Di Chanel Radio PPA Front dengan unit A2B yang beraktivitas di area front\"]},{\"risiko\":\"Kabin Unit HD tertimpa material\",\"pengendalian\":[\"Dilarang memuat material sampai ke kanopi vessel unit HD\"]},{\"risiko\":\"Unit Excavator rebah\",\"pengendalian\":[\"Posisikan Excavator di area front loading yang keras\"]}]},{\"langkah\":\"Unit HD keluar front\",\"bahaya\":[{\"risiko\":\"Unit HD amblas\",\"pengendalian\":[\"Sesuaikan muatan agar tidak over load dan HD amblas saat loading\"]},{\"risiko\":\"Senggolan antar unit A2B\",\"pengendalian\":[\"Komunikasi 2 arah Di Chanel Radio Front dengan unit A2B yang beraktivitas di area front\"]}]},{\"langkah\":\"Perapihan Front Loading\",\"bahaya\":[{\"risiko\":\"Senggolan antar unit A2B\",\"pengendalian\":[\"Komunikasi 2 arah Di Chanel Radio Front dengan unit A2B yang beraktivitas di area front\"]}]}]', '2026-08-26 16:29:33', '2026-08-27 11:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `document_feedback`
--

CREATE TABLE `document_feedback` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `feedback_number` varchar(30) NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `isi` text NOT NULL,
  `status` enum('baru','dibaca','diadopsi','ditolak') NOT NULL DEFAULT 'baru',
  `balasan` text DEFAULT NULL,
  `replied_by` bigint(20) UNSIGNED DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `revision_document_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_reads`
--

CREATE TABLE `document_reads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `first_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `platform` varchar(10) NOT NULL DEFAULT 'web'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_reads`
--

INSERT INTO `document_reads` (`id`, `document_id`, `user_id`, `first_read_at`, `last_read_at`, `read_count`, `download_count`, `platform`) VALUES
(110, 1179, 681, '2026-08-27 13:17:56', '2026-08-27 13:50:28', 5, 5, 'web'),
(115, 1179, 1, '2026-08-27 21:20:39', '2026-08-27 21:20:39', 1, 1, 'web');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `schema_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema_json`)),
  `class` enum('inti','independen','lintas','unggahan') NOT NULL DEFAULT 'inti',
  `scope` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `code`, `name`, `schema_json`, `class`, `scope`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'SOP', 'Standard Operating Procedure', '{\"doc_type\":\"SOP\",\"doc_type_label\":\"STANDARD OPERATING PROCEDURE\",\"header\":\"_kop\",\"footer\":\"_footer\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"cover_page\":\"_cover\",\"steps\":[{\"step\":1,\"title\":\"Tujuan, Ruang Lingkup, Referensi & Definisi\",\"sections\":[{\"label\":\"I. TUJUAN\",\"auto_number\":\"1.\",\"key\":\"tujuan\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"Masukkan poin tujuan...\"},{\"label\":\"II. RUANG LINGKUP\",\"auto_number\":\"2.\",\"key\":\"ruang_lingkup\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"Masukkan poin ruang lingkup...\"},{\"label\":\"III. REFERENSI\",\"auto_number\":\"3.\",\"key\":\"referensi\",\"type\":\"reference_picker\",\"allow_add\":true,\"placeholder\":\"Masukkan referensi...\",\"suggestions\":[\"ISO 9001:2015 Sistem Manajemen Mutu\",\"ISO 45001:2018 Sistem Manajemen K3\",\"ISO 14001:2015 Sistem Manajemen Lingkungan\",\"SMKP Minerba (Permen ESDM No. 26\\/2018)\",\"UU No. 1 Tahun 1970 tentang Keselamatan Kerja\"]},{\"label\":\"IV. DEFINISI\",\"auto_number\":\"4.\",\"key\":\"definisi\",\"type\":\"rich_list\",\"placeholder\":\"Masukkan definisi...\"}]},{\"step\":2,\"title\":\"Flowchart, Aktivitas, Lampiran & Verifikasi\",\"sections\":[{\"label\":\"V. FLOWCHART\",\"auto_number\":\"5.\",\"key\":\"flowchart\",\"type\":\"repeatable_group\",\"min_groups\":1,\"page_break\":\"both\",\"required\":false,\"warn_if_empty\":true,\"add_button_label\":\"+ Tambah Flowchart\",\"group_fields\":[{\"key\":\"judul\",\"label\":\"Judul Flowchart\",\"type\":\"text\",\"placeholder\":\"Judul (Contoh: Alur Serah Terima Shift)\",\"warn_if_empty\":true},{\"key\":\"gambar\",\"label\":\"Gambar Flowchart\",\"type\":\"image\",\"image_accept\":\"image\\/jpeg,image\\/png\",\"image_max_mb\":2},{\"key\":\"keterangan\",\"label\":\"Keterangan\",\"type\":\"textarea\",\"placeholder\":\"Keterangan flowchart...\",\"warn_if_empty\":true}]},{\"label\":\"VI. AKTIVITAS DAN TANGGUNG JAWAB\",\"auto_number\":\"6.\",\"key\":\"aktivitas\",\"type\":\"repeatable_group\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Aktivitas\\/Tanggung Jawab\",\"group_fields\":[{\"key\":\"sub_judul\",\"label\":\"Sub Judul\",\"type\":\"text\",\"placeholder\":\"Sub Judul (Contoh: Tahap Persiapan)\"},{\"key\":\"deskripsi\",\"label\":\"Deskripsi Aktivitas\",\"type\":\"textarea\",\"placeholder\":\"Deskripsi aktivitas...\"},{\"key\":\"pic\",\"label\":\"PIC\",\"type\":\"text\",\"placeholder\":\"PIC (Contoh: Tim ICT)\"}]},{\"label\":\"VII. LAMPIRAN\",\"auto_number\":\"7.\",\"key\":\"lampiran\",\"type\":\"repeatable_group\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Lampiran\",\"required\":false,\"warn_if_empty\":true,\"group_fields\":[{\"key\":\"dokumen\",\"label\":\"Dokumen\",\"type\":\"document_picker\",\"placeholder\":\"Pilih dokumen Berlaku, atau ketik manual...\",\"warn_if_empty\":true},{\"key\":\"keterangan\",\"label\":\"Keterangan (opsional)\",\"type\":\"textarea\",\"placeholder\":\"Boleh dikosongkan.\",\"warn_if_empty\":true}]},{\"key\":\"pembuat_tambahan\",\"label\":\"Pembuat Tambahan (opsional)\",\"type\":\"user_picker\",\"multiple\":true,\"required\":false,\"hint\":\"Gunakan tombol + jika pembuat lebih dari 1 orang.\"},{\"key\":\"peninjau\",\"label\":\"Ditinjau Oleh (DH\\/SH)\",\"type\":\"user_picker\",\"role_filter\":[\"group_leader\",\"section_head\"],\"required\":true},{\"key\":\"penyetuju\",\"label\":\"Disetujui Oleh\",\"type\":\"user_picker\",\"role_filter\":[\"pimpinan\"],\"required\":true}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau Oleh\",\"role\":\"peninjau\"},{\"role_label\":\"Disetujui Oleh\",\"role\":\"penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}', 'inti', 'all_departments', 1, '2026-07-11 16:37:14', '2026-08-05 22:35:33'),
(2, 'IK', 'Instruksi Kerja', '{\"doc_type\":\"IK\",\"doc_type_label\":\"INSTRUKSI KERJA\",\"header\":\"_kop\",\"footer\":\"_footer\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"cover_page\":\"_cover\",\"steps\":[{\"step\":1,\"title\":\"Aktivitas & Pengesahan\",\"sections\":[{\"key\":\"aktivitas\",\"label\":\"I. AKTIVITAS DAN TANGGUNG JAWAB\",\"type\":\"repeatable_group\",\"auto_number\":\"1.\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Aktivitas\\/Tanggung Jawab\",\"group_fields\":[{\"key\":\"sub_judul\",\"label\":\"Sub Judul\",\"type\":\"text\",\"placeholder\":\"Sub Judul (Contoh: Pemeliharaan bulanan)\"},{\"key\":\"deskripsi\",\"label\":\"Deskripsi Aktivitas\",\"type\":\"textarea\",\"placeholder\":\"Deskripsi aktivitas...\"},{\"key\":\"pic\",\"label\":\"PIC\",\"type\":\"text\",\"placeholder\":\"PIC (Contoh: ICT)\"}]},{\"key\":\"pembuat_tambahan\",\"label\":\"Pembuat Tambahan (opsional)\",\"type\":\"user_picker\",\"multiple\":true,\"required\":false,\"hint\":\"Gunakan tombol + jika pembuat lebih dari 1 orang.\"},{\"key\":\"peninjau_penyetuju\",\"label\":\"Ditinjau & Disetujui Oleh (SH\\/DH Dept)\",\"type\":\"user_picker\",\"role_filter\":[\"section_head\",\"departemen_head\"],\"required\":true}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau dan Disetujui Oleh\",\"role\":\"peninjau_penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}', 'inti', 'all_departments', 1, '2026-07-11 22:36:16', '2026-08-03 23:39:20'),
(3, 'SP', 'Standar Parameter', '{\"doc_type\": \"SP\", \"doc_type_label\": \"STANDAR PARAMETER\", \"header\": \"_kop\", \"footer\": \"_footer\", \"footer_text\": \"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\", \"cover_page\": \"_cover\", \"steps\": [{\"step\": 1, \"title\": \"Tujuan, Ruang Lingkup, Referensi & Definisi\", \"sections\": [{\"label\": \"I. TUJUAN\", \"auto_number\": \"1.\", \"key\": \"tujuan\", \"type\": \"rich_list\", \"min_items\": 1, \"placeholder\": \"Masukkan poin tujuan...\"}, {\"label\": \"II. RUANG LINGKUP\", \"auto_number\": \"2.\", \"key\": \"ruang_lingkup\", \"type\": \"rich_list\", \"min_items\": 1, \"placeholder\": \"Masukkan poin ruang lingkup...\"}, {\"label\": \"III. REFERENSI\", \"auto_number\": \"3.\", \"key\": \"referensi\", \"type\": \"reference_picker\", \"allow_add\": true, \"placeholder\": \"Masukkan referensi...\", \"suggestions\": [\"ISO 9001:2015 Sistem Manajemen Mutu\", \"ISO 45001:2018 Sistem Manajemen K3\", \"ISO 14001:2015 Sistem Manajemen Lingkungan\", \"SMKP Minerba (Permen ESDM No. 26\\/2018)\", \"UU No. 1 Tahun 1970 tentang Keselamatan Kerja\"]}, {\"label\": \"IV. DEFINISI\", \"auto_number\": \"4.\", \"key\": \"definisi\", \"type\": \"rich_list\", \"placeholder\": \"Masukkan definisi...\"}]}, {\"step\": 2, \"title\": \"Aktivitas, Lampiran & Verifikasi\", \"sections\": [{\"label\": \"V. AKTIVITAS DAN TANGGUNG JAWAB\", \"auto_number\": \"5.\", \"key\": \"aktivitas\", \"type\": \"repeatable_group\", \"min_groups\": 1, \"add_button_label\": \"+ Tambah Aktivitas\\/Tanggung Jawab\", \"group_fields\": [{\"key\": \"sub_judul\", \"label\": \"Sub Judul\", \"type\": \"text\", \"placeholder\": \"Sub Judul (Contoh: Tahap Persiapan)\"}, {\"key\": \"deskripsi\", \"label\": \"Deskripsi Aktivitas\", \"type\": \"textarea\", \"placeholder\": \"Deskripsi aktivitas...\"}, {\"key\": \"pic\", \"label\": \"PIC\", \"type\": \"text\", \"placeholder\": \"PIC (Contoh: Tim ICT)\"}]}, {\"label\": \"VI. LAMPIRAN\", \"auto_number\": \"6.\", \"key\": \"lampiran\", \"type\": \"repeatable_group\", \"min_groups\": 0, \"add_button_label\": \"+ Tambah Lampiran Baru\", \"required\": false, \"group_fields\": [{\"key\": \"judul\", \"label\": \"Judul Lampiran\", \"type\": \"text\", \"placeholder\": \"Judul Lampiran (Contoh: Form Ceklis)\"}, {\"key\": \"keterangan\", \"label\": \"Keterangan\", \"type\": \"textarea\", \"placeholder\": \"Keterangan \\/ caption lampiran (opsional)...\", \"warn_if_empty\": true}, {\"key\": \"gambar\", \"label\": \"Foto \\/ Gambar (opsional)\", \"type\": \"image\", \"image_accept\": \"image\\/jpeg,image\\/png\", \"image_max_mb\": 2}]}, {\"key\": \"pembuat_tambahan\", \"label\": \"Pembuat Tambahan (opsional)\", \"type\": \"user_picker\", \"multiple\": true, \"required\": false, \"hint\": \"Gunakan tombol + jika pembuat lebih dari 1 orang.\"}, {\"key\": \"peninjau_penyetuju\", \"label\": \"Ditinjau & Disetujui Oleh (SH\\/DH Dept)\", \"type\": \"user_picker\", \"role_filter\": [\"section_head\", \"departemen_head\"], \"required\": true}]}], \"approval_page_layout\": {\"columns\": [\"Nama\", \"Jabatan\", \"Tanggal\", \"Pengesahan\"], \"rows\": [{\"role_label\": \"Dibuat Oleh\", \"role\": \"pembuat\"}, {\"role_label\": \"Ditinjau dan Disetujui Oleh\", \"role\": \"peninjau_penyetuju\"}], \"stamp_on_published\": \"APPROVED\"}}', 'inti', 'all_departments', 1, '2026-07-11 22:36:16', '2026-08-05 22:41:33'),
(4, 'JSA', 'Job Safety Analysis', '{\"doc_type\":\"JSA\",\"doc_type_label\":\"FORMULIR JOB SAFETY ANALYSIS\",\"orientation\":\"landscape\",\"header\":\"_kop_jsa\",\"footer\":\"_footer\",\"print_view\":\"documents.print.render-jsa\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"approval_page\":\"_pengesahan\",\"steps\":[{\"step\":1,\"title\":\"Informasi Umum\",\"sections\":[{\"key\":\"lokasi_kerja\",\"label\":\"Lokasi Kerja\",\"type\":\"text\",\"placeholder\":\"mis. View Point\"},{\"key\":\"apd\",\"label\":\"APD yang digunakan\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"mis. Helm safety, Body harness, Sarung tangan...\"},{\"key\":\"tools\",\"label\":\"Peralatan yang digunakan\",\"type\":\"rich_list\",\"min_items\":1,\"placeholder\":\"mis. Obeng, Bor, Tang...\"},{\"key\":\"form_tgl_efektif\",\"label\":\"Tgl. Efektif (kop)\",\"type\":\"date\"},{\"key\":\"peninjau\",\"label\":\"Ditinjau Oleh\",\"type\":\"user_picker\",\"role_filter\":[\"group_leader\",\"section_head\"],\"required\":true},{\"key\":\"penyetuju\",\"label\":\"Disetujui Oleh\",\"type\":\"user_picker\",\"role_filter\":[\"pimpinan\"],\"required\":true}]},{\"step\":2,\"title\":\"Analisa Bahaya\",\"sections\":[{\"key\":\"analisa\",\"label\":\"Analisa Bahaya\",\"type\":\"jsa_analysis\",\"help\":\"Masukkan tahapan pekerjaan, potensi bahaya yang mungkin timbul, dan langkah pengendaliannya.\"}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau Oleh\",\"role\":\"peninjau\"},{\"role_label\":\"Disetujui Oleh\",\"role\":\"penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}', 'inti', 'all_departments', 1, '2026-07-11 22:36:16', '2026-08-03 15:27:04'),
(5, 'FK', 'Formulir Kerja', '[]', 'unggahan', 'all_departments', 1, '2026-08-12 23:49:14', '2026-08-12 23:49:14'),
(6, 'PX', 'Prosedur External', '[]', 'unggahan', 'all_departments', 1, '2026-08-12 23:49:14', '2026-08-12 23:49:14');

-- --------------------------------------------------------

--
-- Table structure for table `document_versions`
--

CREATE TABLE `document_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `no_revisi` int(10) UNSIGNED NOT NULL,
  `snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_json`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `informasi`
--

CREATE TABLE `informasi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kategori` varchar(32) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `edisi` int(10) UNSIGNED DEFAULT NULL,
  `no_revisi` int(10) UNSIGNED DEFAULT NULL,
  `tanggal_efektif` date DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_mime` varchar(100) NOT NULL,
  `berlaku` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_checklist_items`
--

CREATE TABLE `job_checklist_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_execution_id` bigint(20) UNSIGNED NOT NULL,
  `langkah_ke` smallint(5) UNSIGNED NOT NULL,
  `bahaya_ke` smallint(5) UNSIGNED NOT NULL,
  `pengendalian_ke` smallint(5) UNSIGNED NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT 0,
  `checked_at` timestamp NULL DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_executions`
--

CREATE TABLE `job_executions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nama_pekerjaan` varchar(255) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `tanggal_pelaksanaan` date NOT NULL,
  `analisa_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`analisa_snapshot`)),
  `status` enum('berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'berlangsung',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_12_082503_create_permission_tables', 1),
(5, '2026_07_12_090001_create_departments_table', 1),
(6, '2026_07_12_090002_create_document_types_table', 1),
(7, '2026_07_12_090003_create_documents_table', 1),
(8, '2026_07_12_090004_create_document_contents_table', 1),
(9, '2026_07_12_090005_create_document_versions_table', 1),
(10, '2026_07_12_090006_create_reviews_table', 1),
(11, '2026_07_12_090007_create_approvals_table', 1),
(12, '2026_07_12_090008_create_attachments_table', 1),
(13, '2026_07_12_090009_create_audit_logs_table', 1),
(14, '2026_07_12_090010_create_notifications_table', 1),
(15, '2026_07_12_110000_create_document_authors_table', 1),
(16, '2026_07_13_100001_create_attachment_comments_table', 1),
(17, '2026_07_31_161340_create_personal_access_tokens_table', 1),
(18, '2026_08_01_090000_create_document_feedback_table', 1),
(19, '2026_08_01_093000_add_pending_md_status_to_documents', 1),
(20, '2026_08_01_094000_add_ai_review_enabled_to_users', 1),
(21, '2026_08_03_080000_create_job_executions_table', 1),
(22, '2026_08_03_080001_create_job_checklist_items_table', 1),
(23, '2026_08_03_090000_rename_pending_md_to_verifikasi_md', 1),
(24, '2026_08_03_100000_create_user_off_days_table', 1),
(25, '2026_08_04_100000_add_verdict_to_review_annotations', 1),
(26, '2026_08_05_100000_create_document_reads_table', 1),
(27, '2026_08_06_090000_drop_doc_number_temp_from_documents', 1),
(30, '2026_08_10_090000_rename_sp_standar_parameter', 2),
(31, '2026_08_12_090000_add_arsip_path_to_documents_table', 3),
(32, '2026_08_12_100000_create_informasi_table', 4),
(33, '2026_08_13_090000_tambah_kelas_unggahan_document_types', 5);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(8, 'App\\Models\\User', 2),
(4, 'App\\Models\\User', 5),
(4, 'App\\Models\\User', 6),
(4, 'App\\Models\\User', 7),
(4, 'App\\Models\\User', 8),
(4, 'App\\Models\\User', 9),
(4, 'App\\Models\\User', 10),
(3, 'App\\Models\\User', 11),
(3, 'App\\Models\\User', 12),
(7, 'App\\Models\\User', 13),
(3, 'App\\Models\\User', 14),
(2, 'App\\Models\\User', 15),
(6, 'App\\Models\\User', 16),
(6, 'App\\Models\\User', 666),
(4, 'App\\Models\\User', 667),
(3, 'App\\Models\\User', 668),
(2, 'App\\Models\\User', 669),
(8, 'App\\Models\\User', 670),
(4, 'App\\Models\\User', 671),
(4, 'App\\Models\\User', 672),
(4, 'App\\Models\\User', 673),
(4, 'App\\Models\\User', 674),
(7, 'App\\Models\\User', 675),
(3, 'App\\Models\\User', 676),
(4, 'App\\Models\\User', 677),
(3, 'App\\Models\\User', 678),
(4, 'App\\Models\\User', 679),
(4, 'App\\Models\\User', 680),
(3, 'App\\Models\\User', 681),
(4, 'App\\Models\\User', 682),
(4, 'App\\Models\\User', 683),
(3, 'App\\Models\\User', 684),
(3, 'App\\Models\\User', 685),
(3, 'App\\Models\\User', 686),
(4, 'App\\Models\\User', 687),
(4, 'App\\Models\\User', 688),
(4, 'App\\Models\\User', 689),
(4, 'App\\Models\\User', 690),
(4, 'App\\Models\\User', 691),
(4, 'App\\Models\\User', 692),
(4, 'App\\Models\\User', 693),
(4, 'App\\Models\\User', 694),
(4, 'App\\Models\\User', 695),
(4, 'App\\Models\\User', 696),
(4, 'App\\Models\\User', 697),
(4, 'App\\Models\\User', 698),
(4, 'App\\Models\\User', 699),
(4, 'App\\Models\\User', 700),
(4, 'App\\Models\\User', 701),
(4, 'App\\Models\\User', 702),
(4, 'App\\Models\\User', 703),
(4, 'App\\Models\\User', 704),
(4, 'App\\Models\\User', 705),
(4, 'App\\Models\\User', 706),
(4, 'App\\Models\\User', 707),
(4, 'App\\Models\\User', 708),
(4, 'App\\Models\\User', 709),
(4, 'App\\Models\\User', 710),
(4, 'App\\Models\\User', 711),
(4, 'App\\Models\\User', 712),
(4, 'App\\Models\\User', 713),
(4, 'App\\Models\\User', 714),
(4, 'App\\Models\\User', 715),
(4, 'App\\Models\\User', 716),
(4, 'App\\Models\\User', 717),
(4, 'App\\Models\\User', 718),
(4, 'App\\Models\\User', 719),
(4, 'App\\Models\\User', 720),
(4, 'App\\Models\\User', 721),
(4, 'App\\Models\\User', 722),
(4, 'App\\Models\\User', 723),
(4, 'App\\Models\\User', 724),
(4, 'App\\Models\\User', 725),
(4, 'App\\Models\\User', 726),
(4, 'App\\Models\\User', 727),
(4, 'App\\Models\\User', 728),
(4, 'App\\Models\\User', 729),
(4, 'App\\Models\\User', 730),
(4, 'App\\Models\\User', 731),
(4, 'App\\Models\\User', 732),
(4, 'App\\Models\\User', 733),
(4, 'App\\Models\\User', 734),
(4, 'App\\Models\\User', 735),
(4, 'App\\Models\\User', 736),
(4, 'App\\Models\\User', 737),
(4, 'App\\Models\\User', 738),
(4, 'App\\Models\\User', 739),
(4, 'App\\Models\\User', 740),
(4, 'App\\Models\\User', 741),
(4, 'App\\Models\\User', 742),
(4, 'App\\Models\\User', 743),
(4, 'App\\Models\\User', 744),
(4, 'App\\Models\\User', 745),
(4, 'App\\Models\\User', 746),
(4, 'App\\Models\\User', 747),
(4, 'App\\Models\\User', 748),
(4, 'App\\Models\\User', 749),
(4, 'App\\Models\\User', 750),
(4, 'App\\Models\\User', 751),
(4, 'App\\Models\\User', 752),
(4, 'App\\Models\\User', 753),
(4, 'App\\Models\\User', 754),
(4, 'App\\Models\\User', 755),
(4, 'App\\Models\\User', 756),
(4, 'App\\Models\\User', 757),
(4, 'App\\Models\\User', 758),
(4, 'App\\Models\\User', 759),
(4, 'App\\Models\\User', 760),
(4, 'App\\Models\\User', 761),
(4, 'App\\Models\\User', 762),
(4, 'App\\Models\\User', 763),
(4, 'App\\Models\\User', 764),
(4, 'App\\Models\\User', 765),
(4, 'App\\Models\\User', 766),
(4, 'App\\Models\\User', 767),
(4, 'App\\Models\\User', 768),
(4, 'App\\Models\\User', 769),
(4, 'App\\Models\\User', 770),
(4, 'App\\Models\\User', 771),
(4, 'App\\Models\\User', 772),
(7, 'App\\Models\\User', 773),
(4, 'App\\Models\\User', 774),
(4, 'App\\Models\\User', 775),
(4, 'App\\Models\\User', 776),
(4, 'App\\Models\\User', 777),
(4, 'App\\Models\\User', 778),
(6, 'App\\Models\\User', 779);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('1498d2d6-448f-4f98-b642-59058271ec36', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 667, '{\"document_id\":1175,\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"title\":\"Instalasi Perangkat VHMS di Unit HD\",\"message\":\"Dokumen PPA-ADRO-JSA-ICTMD-03 lolos tinjauan \\u2014 menunggu persetujuan.\",\"icon\":\"bi-check2\",\"route\":\"documents.index\"}', NULL, '2026-08-23 11:19:47', '2026-08-23 11:19:47'),
('1b1c992c-296b-45e3-be00-df28d9a15882', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 5, '{\"document_id\":1164,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 dikembalikan untuk revisi.\",\"icon\":\"bi-arrow-counterclockwise\",\"route\":\"documents.revisions\"}', NULL, '2026-08-13 04:20:46', '2026-08-13 04:20:46'),
('2abc5578-a7ee-4169-8d13-a340895b401c', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 5, '{\"document_id\":1163,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-02 dikembalikan untuk revisi.\",\"icon\":\"bi-arrow-counterclockwise\",\"route\":\"documents.revisions\"}', NULL, '2026-08-13 04:20:51', '2026-08-13 04:20:51'),
('352c8e2c-15ab-4a3a-8ce1-e853863fc9f5', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1163,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-02 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-13 04:19:34', '2026-08-13 04:19:34'),
('367b5ca8-51bb-40b4-a127-6f70d0ed94ac', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 712, '{\"document_id\":1179,\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"message\":\"Dokumen PPA-ADRO-JSA-PRODUKSI-01 lolos tinjauan \\u2014 menunggu persetujuan.\",\"icon\":\"bi-check2\",\"route\":\"documents.index\"}', NULL, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
('36a7e83e-eb75-4e4e-972f-227038f87558', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1163,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-02 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-13 04:21:14', '2026-08-13 04:21:14'),
('396fbc34-6ede-46ab-8cd6-217f9e934435', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 668, '{\"document_id\":1170,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"TESTING EXISTING\",\"message\":\"Dokumen lama PPA-ADRO-SOP-ICTMD-10 \\u2014 TESTING EXISTING didaftarkan Angga Margi Saputro dan langsung Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.published\"}', NULL, '2026-08-18 10:05:22', '2026-08-18 10:05:22'),
('5e2bac75-bb2d-438f-a67d-746daf2049fa', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 712, '{\"document_id\":1179,\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"message\":\"Dokumen PPA-ADRO-JSA-PRODUKSI-01 dikembalikan untuk revisi.\",\"icon\":\"bi-arrow-counterclockwise\",\"route\":\"documents.revisions\"}', NULL, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
('6186521c-1df2-40e8-8f33-72ae9baa08a7', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1163,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-02 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', '2026-08-13 03:41:02', '2026-08-13 03:40:55', '2026-08-13 03:41:02'),
('667303f6-b8d4-4efc-a696-d62a1ce7fd01', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1164,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-13 04:19:42', '2026-08-13 04:19:42'),
('7269d163-5f8a-4d1e-9d57-5b30b2c803f9', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 670, '{\"document_id\":1168,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"sop testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 perlu ditinjau Management Development.\",\"icon\":\"bi-spellcheck\",\"route\":\"review.md\"}', NULL, '2026-08-18 10:10:29', '2026-08-18 10:10:29'),
('76144b64-8995-4622-ac7d-c3847e0159d4', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 667, '{\"document_id\":1175,\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"title\":\"Instalasi Perangkat VHMS di Unit HD\",\"message\":\"Dokumen PPA-ADRO-JSA-ICTMD-03 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.index\"}', NULL, '2026-08-23 11:20:01', '2026-08-23 11:20:01'),
('7d2f993b-d5d1-4e80-9ca0-e76a99930418', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 678, '{\"document_id\":1178,\"doc_number\":\"PPA-ADRO-SOP-SHE-09\",\"title\":\"AUDIT INTERNAL\",\"message\":\"Dokumen PPA-ADRO-SOP-SHE-09 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-26 15:08:28', '2026-08-26 15:08:28'),
('8b85bdba-735d-4eae-8951-9f83cc104c30', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 673, '{\"document_id\":1175,\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"title\":\"Instalasi Perangkat VHMS di Unit HD\",\"message\":\"Dokumen PPA-ADRO-JSA-ICTMD-03 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', '2026-08-23 11:18:43', '2026-08-23 11:17:14', '2026-08-23 11:18:43'),
('8e45927d-4047-4d4b-8990-60d03cd01917', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 668, '{\"document_id\":1175,\"doc_number\":\"PPA-ADRO-JSA-ICTMD-03\",\"title\":\"Instalasi Perangkat VHMS di Unit HD\",\"message\":\"Dokumen PPA-ADRO-JSA-ICTMD-03 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', '2026-08-23 11:19:55', '2026-08-23 11:19:47', '2026-08-23 11:19:55'),
('8edd26d8-7460-4291-b617-2d77d350bab4', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 668, '{\"document_id\":1168,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"sop testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-18 10:02:17', '2026-08-18 10:02:17'),
('a0096d13-7b30-44d5-be60-36f8f81005a9', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 673, '{\"document_id\":1179,\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"message\":\"Dokumen PPA-ADRO-JSA-PRODUKSI-01 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-26 16:38:18', '2026-08-26 16:38:18'),
('a2b72fa6-0318-429f-b5db-3bcb456f632e', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 712, '{\"document_id\":1179,\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"message\":\"Dokumen PPA-ADRO-JSA-PRODUKSI-01 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.index\"}', NULL, '2026-08-27 13:17:27', '2026-08-27 13:17:27'),
('a3dcca7a-c426-445a-8412-90579328222b', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1164,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-13 04:02:57', '2026-08-13 04:02:57'),
('a711a325-6150-48a4-b062-d42acb589f0f', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1162,\"doc_number\":\"PPA-ADRO-FK-ICTMD-10\",\"title\":\"Form kerja testing\",\"message\":\"Dokumen lama PPA-ADRO-FK-ICTMD-10 \\u2014 Form kerja testing didaftarkan ANGGA MARGI SAPUTRO dan langsung Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.published\"}', NULL, '2026-08-13 03:18:59', '2026-08-13 03:18:59'),
('afe3e7df-949c-4d53-8ca9-8e8693e11a72', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 686, '{\"document_id\":1178,\"doc_number\":\"PPA-ADRO-SOP-SHE-09\",\"title\":\"AUDIT INTERNAL\",\"message\":\"Dokumen PPA-ADRO-SOP-SHE-09 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', '2026-08-26 17:11:53', '2026-08-26 17:09:16', '2026-08-26 17:11:53'),
('b7cabf04-6615-4058-8544-f5c83dc54175', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 673, '{\"document_id\":1179,\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"message\":\"Dokumen PPA-ADRO-JSA-PRODUKSI-01 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-27 11:58:31', '2026-08-27 11:58:31'),
('b8763052-3128-4bf0-b9ee-de2f5cdc9a16', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 5, '{\"document_id\":1163,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"title\":\"testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-02 dikembalikan untuk revisi.\",\"icon\":\"bi-arrow-counterclockwise\",\"route\":\"documents.revisions\"}', NULL, '2026-08-13 04:19:04', '2026-08-13 04:19:04'),
('d1363353-13b1-4aa1-92b7-746989db602f', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1164,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-08-13 04:21:24', '2026-08-13 04:21:24'),
('d5ea706d-49c6-4f02-9bcd-cd9e21ce3928', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 681, '{\"document_id\":1179,\"doc_number\":\"PPA-ADRO-JSA-PRODUKSI-01\",\"title\":\"Loading Material Debu\",\"message\":\"Dokumen PPA-ADRO-JSA-PRODUKSI-01 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', '2026-08-27 13:17:34', '2026-08-27 13:09:28', '2026-08-27 13:17:34'),
('d6923e8c-20d5-4030-b1fe-c3290c2dc4b6', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 667, '{\"document_id\":1168,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"sop testing\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 lolos tinjauan \\u2014 diteruskan ke Management Development.\",\"icon\":\"bi-check2\",\"route\":\"documents.index\"}', NULL, '2026-08-18 10:10:29', '2026-08-18 10:10:29'),
('dade056b-a371-4db4-bb78-301f430f6593', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 667, '{\"document_id\":1171,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"1\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-01 diajukan revisi (akan menjadi Edisi 1 Rev 1) \\u2014 silakan perbarui lalu kirim ulang.\",\"icon\":\"bi-arrow-repeat\",\"route\":\"documents.index\"}', NULL, '2026-08-18 10:09:03', '2026-08-18 10:09:03'),
('dbaa365a-65e8-4850-9a55-f49585f7a7c7', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 11, '{\"document_id\":1161,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"title\":\"Testing random\",\"message\":\"Dokumen lama PPA-ADRO-SOP-ICTMD-01 \\u2014 Testing random didaftarkan ANGGA MARGI SAPUTRO dan langsung Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.published\"}', NULL, '2026-08-13 03:18:26', '2026-08-13 03:18:26'),
('efc36e6e-e482-40b8-9fbe-2b165a77fc89', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 5, '{\"document_id\":1164,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"title\":\"2\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-03 dikembalikan untuk revisi.\",\"icon\":\"bi-arrow-counterclockwise\",\"route\":\"documents.revisions\"}', NULL, '2026-08-13 04:18:58', '2026-08-13 04:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'document.create', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(2, 'document.edit', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(3, 'document.submit', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(4, 'document.delete', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(5, 'document.review', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(6, 'document.approve', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(7, 'document.publish', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(8, 'document.view_department', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(9, 'document.view_scope', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(10, 'document.view_all', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(11, 'user.manage', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(12, 'user.approve_registration', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(13, 'user.create_staff', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(14, 'document.change_status', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(15, 'audit.view', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(16, 'document.request_revision', 'web', '2026-07-11 22:36:15', '2026-07-11 22:36:15'),
(17, 'document.review_jsa', 'web', '2026-07-28 05:23:14', '2026-07-28 05:23:14'),
(18, 'document.review_md', 'web', '2026-08-01 00:08:55', '2026-08-01 00:08:55'),
(19, 'informasi.manage', 'web', '2026-08-12 04:39:01', '2026-08-12 04:39:01');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(29, 'App\\Models\\User', 5, 'mobile', '248d60c6a2a0e28b400c87f21391c89a8409fe8862c442ee9098d2d6894e15c9', '[\"*\"]', '2026-08-13 03:21:55', NULL, '2026-08-13 03:17:38', '2026-08-13 03:21:55'),
(30, 'App\\Models\\User', 667, 'mobile', 'de7c3f1f84ed2b822be1fb25b41abb6315e514be08f7cc0115f7759078a760d7', '[\"*\"]', '2026-08-22 11:58:33', NULL, '2026-08-13 15:31:42', '2026-08-22 11:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `reviewer_id` bigint(20) UNSIGNED NOT NULL,
  `revision_round` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `decision` enum('pending','approved','needs_revision') NOT NULL DEFAULT 'pending',
  `summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `document_id`, `reviewer_id`, `revision_round`, `decision`, `summary`, `created_at`, `updated_at`) VALUES
(119, 1179, 673, 0, 'needs_revision', 'mohom segera lakukan perbaikan', '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(120, 1179, 673, 1, 'approved', NULL, '2026-08-27 13:09:28', '2026-08-27 13:09:28');

-- --------------------------------------------------------

--
-- Table structure for table `review_annotations`
--

CREATE TABLE `review_annotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `review_id` bigint(20) UNSIGNED NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `item_ref` varchar(255) DEFAULT NULL,
  `verdict` enum('sesuai','perlu_revisi') DEFAULT NULL,
  `severity` enum('info','minor','major','critical') NOT NULL DEFAULT 'minor',
  `comment` text DEFAULT NULL,
  `ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `ai_adopted` tinyint(1) NOT NULL DEFAULT 0,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_annotations`
--

INSERT INTO `review_annotations` (`id`, `review_id`, `section_key`, `item_ref`, `verdict`, `severity`, `comment`, `ai_generated`, `ai_adopted`, `resolved`, `created_at`, `updated_at`) VALUES
(89, 119, 'analisa', 'L0-B0-P0', 'perlu_revisi', 'minor', 'kata memilih agar tidak digunakan, gunakan kata kalimat yang langsung mengarahkan pekerja untuk berpijak pada area yang keras dan padat', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(90, 119, 'analisa', 'L0-B0-P1', 'perlu_revisi', 'minor', 'kata \"jangan\" merupakan bahasa surga (kata Opung), gunakan kalimat yang instruksi lngsg pada tindakan pada pekerja', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(91, 119, 'analisa', 'L0-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(92, 119, 'analisa', 'L0-B1-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(93, 119, 'analisa', 'L0-B2-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(94, 119, 'analisa', 'L0-B2-P1', 'perlu_revisi', 'minor', 'komunikasi yang digunakan menggunakan media apa? (misal radio dengan channel radio apa?)', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(95, 119, 'analisa', 'L1-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(96, 119, 'analisa', 'L1-B0-P1', 'sesuai', 'minor', 'Lakukan komunikasi 2 dengan pengawas menggunakan channel radio apa?', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(97, 119, 'analisa', 'L2-B0-P0', 'perlu_revisi', 'minor', '\"Lakukan\" penyiraman WT brpa?', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(98, 119, 'analisa', 'L2-B0-P1', 'perlu_revisi', 'minor', 'cara kontrol nya seperti apa? jelaskan', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(99, 119, 'analisa', 'L2-B1-P0', 'perlu_revisi', 'minor', 'pakai kalimat instruksi terkait pemindahan tower untuk menerangi area kerja tersebut dengan min pencahayaan 5 lux', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(100, 119, 'analisa', 'L3-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(101, 119, 'analisa', 'L4-B0-P0', 'perlu_revisi', 'minor', 'tambahkan kontrol sign sbgai bntuk komunikasi pengawas terkait area yang lembek dan belm siap di lalui unit hauler', 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(102, 119, 'analisa', 'L4-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(103, 119, 'analisa', 'L4-B0-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(104, 119, 'analisa', 'L4-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(105, 119, 'analisa', 'L4-B1-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(106, 119, 'analisa', 'L4-B1-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(107, 119, 'analisa', 'L4-B1-P3', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(108, 119, 'analisa', 'L4-B2-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(109, 119, 'analisa', 'L4-B2-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(110, 119, 'analisa', 'L4-B2-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(111, 119, 'analisa', 'L4-B3-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(112, 119, 'analisa', 'L4-B4-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(113, 119, 'analisa', 'L4-B4-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(114, 119, 'analisa', 'L5-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(115, 119, 'analisa', 'L5-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(116, 119, 'analisa', 'L5-B0-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(117, 119, 'analisa', 'L5-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(118, 119, 'analisa', 'L5-B2-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(119, 119, 'analisa', 'L6-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(120, 119, 'analisa', 'L6-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(121, 119, 'analisa', 'L7-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-26 17:04:00', '2026-08-26 17:04:00'),
(122, 120, 'analisa', 'L0-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(123, 120, 'analisa', 'L0-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(124, 120, 'analisa', 'L0-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(125, 120, 'analisa', 'L0-B1-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(126, 120, 'analisa', 'L0-B2-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(127, 120, 'analisa', 'L0-B2-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(128, 120, 'analisa', 'L1-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(129, 120, 'analisa', 'L1-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(130, 120, 'analisa', 'L2-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(131, 120, 'analisa', 'L2-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(132, 120, 'analisa', 'L2-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(133, 120, 'analisa', 'L3-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(134, 120, 'analisa', 'L3-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(135, 120, 'analisa', 'L4-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(136, 120, 'analisa', 'L4-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(137, 120, 'analisa', 'L4-B0-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(138, 120, 'analisa', 'L4-B0-P3', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(139, 120, 'analisa', 'L4-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(140, 120, 'analisa', 'L4-B1-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(141, 120, 'analisa', 'L4-B1-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(142, 120, 'analisa', 'L4-B1-P3', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(143, 120, 'analisa', 'L4-B2-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(144, 120, 'analisa', 'L4-B2-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(145, 120, 'analisa', 'L4-B2-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(146, 120, 'analisa', 'L4-B3-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(147, 120, 'analisa', 'L4-B4-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(148, 120, 'analisa', 'L4-B4-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(149, 120, 'analisa', 'L5-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(150, 120, 'analisa', 'L5-B0-P1', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(151, 120, 'analisa', 'L5-B0-P2', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(152, 120, 'analisa', 'L5-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(153, 120, 'analisa', 'L5-B2-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(154, 120, 'analisa', 'L6-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(155, 120, 'analisa', 'L6-B1-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28'),
(156, 120, 'analisa', 'L7-B0-P0', 'sesuai', 'minor', NULL, 0, 0, 0, '2026-08-27 13:09:28', '2026-08-27 13:09:28');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin_it', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(2, 'pimpinan', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(3, 'section_head', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(4, 'group_leader', 'web', '2026-07-11 16:37:14', '2026-07-11 16:37:14'),
(6, 'staff', 'web', '2026-07-11 22:36:16', '2026-07-11 22:36:16'),
(7, 'departemen_head', 'web', '2026-07-15 06:29:48', '2026-07-15 06:29:48'),
(8, 'management_development', 'web', '2026-08-01 00:08:56', '2026-08-01 00:08:56');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(6, 2),
(7, 2),
(10, 2),
(12, 2),
(15, 2),
(16, 2),
(5, 3),
(6, 3),
(8, 3),
(12, 3),
(15, 3),
(16, 3),
(1, 4),
(2, 4),
(3, 4),
(4, 4),
(8, 4),
(12, 4),
(15, 4),
(17, 4),
(19, 4),
(8, 6),
(5, 7),
(6, 7),
(8, 7),
(12, 7),
(15, 7),
(16, 7),
(10, 8),
(15, 8),
(18, 8);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('5FwgGOo3Y263va4noTCpy45xJHOXuhGrt8xd6EnM', 681, '36.88.128.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidEFGa2ZPTHF5Rm1zMEpId3RlVGc0TzFEVTZUZ3p6V2IzTXZ6NTVZMCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjgxO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQ1OiJodHRwczovL2Fkdy5wcm93b3JrcHBhLmNvbS9kb2N1bWVudHMvMTE3OS9wZGYiO3M6NToicm91dGUiO3M6MTM6ImRvY3VtZW50cy5wZGYiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1787809828),
('6R4Huz6QRcpjPJfeZpX05qwvDvF0cd7niYz9RCyw', 685, '103.88.153.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicW1wdWt2S1l5MlVtN1E4TnhDSjR5QlhqWVdxUmFIZWkyc0k3UGZPdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL3JldmlldyI7czo1OiJyb3V0ZSI7czoxMjoicmV2aWV3LmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Njg1O30=', 1787809104),
('87NeJuTGNkMi9ONR79lhMJmxZmsA4jpUnDdEXJOO', 667, '36.88.128.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNDhrUm1jbmp5S2V5eE5NcE1adHpYZk11TG9aVlhrUVluRkJrMmdzVyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjQ6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2RvY3VtZW50cy8xMTgxL3BkZj92PWZjZTRlMzgyMWE4ZjMzOTEiO3M6NToicm91dGUiO3M6MTM6ImRvY3VtZW50cy5wZGYiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo2Njc7fQ==', 1787810768),
('AwvnG1PWomusLKXyESQV1R1I89XYBVRgFEQe4098', 766, '103.88.153.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYXkwMVl3dXFJYlBjc004WG5TSEJoVEozdE9pckFWSzNEUlFsMWZ2dyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo3NjY7fQ==', 1787809153),
('b0g32p70z6rdPdmrQXZRIIrUxLXPlVsuS7fGNoPE', NULL, '103.22.242.5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVUIxQzY4a3R1U0J5UWFwOVFTdmVjYmdROUg2dmNwd3RHWk1QRmdVciI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cHM6Ly9hZHcucHJvd29ya3BwYS5jb20vcmV2aWV3Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzI6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787809024),
('BMYRZRGJwHyJx67H4EjA7ckh5glPkDGXnbuP5VUE', NULL, '36.50.157.22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicWE0NlRUczRGdTFvUHZRdWJDRlczeENnU2I1WTF3d2JVdGZaRHpHTiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0NDoiaHR0cHM6Ly9hZHcucHJvd29ya3BwYS5jb20vcml3YXlhdC1wZWtlcmphYW4iO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozMjoiaHR0cHM6Ly9hZHcucHJvd29ya3BwYS5jb20vbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1787809089),
('C5sNUFNEAM3OQLujADhnLtDxkBaulK29z31fuQKx', 673, '36.88.128.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU3V0QUhrSU15RkpDOGdmTFpSem1uVVd4bzJYWG83QVNYTjZ4ZFVOViI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjczO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjg3OiJodHRwczovL2Fkdy5wcm93b3JrcHBhLmNvbS9zdG9yYWdlL2F2YXRhcnMvQk9icW9ibVRsYU51bDR4eHJYSTFNWGUwZlhtVjQ4MGpZeFJLS1Y3MS5qcGciO3M6NToicm91dGUiO3M6MTQ6InN0b3JhZ2UucHVibGljIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787809167),
('fxBOVDgqohTDQFLqYlIeUj09FFqJucU56G5apYIj', NULL, '175.45.186.197', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVVR0Smw0WWRzYW1zbFN4V082dTU4TWIwNUkyNnBrZmRlc1RNblhXbiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cHM6Ly9hZHcucHJvd29ya3BwYS5jb20vcmV2aWV3Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzI6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787809145),
('hVHVet1G7uEX9AZupvjaTJegE93AMtP0ro1wrVju', NULL, '136.113.11.235', 'crusader-worker/1.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicjNiYWNDMnRuSncxOXdGMTg4UGpyQVN0dWt0TktKQTU4blJ6Wm5GMiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHBzOi8vd3d3LmFkdy5wcm93b3JrcHBhLmNvbSI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787829671),
('n2DojvYz8r0bnI4t7FMOIQx79yGhNkK3P86pRhrv', 678, '36.88.128.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTTNCQ2kyUHZkaEhza20xTm5qbjlGVThselZ0cW9ZNGdaZ2J5dFJqeSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2RvY3VtZW50cy8xMTc3IjtzOjU6InJvdXRlIjtzOjE0OiJkb2N1bWVudHMuc2hvdyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjY3ODt9', 1787809102),
('p4cDRKc5iIMBWugC4cpeIhIlZV3wHdbrm83GguPg', 712, '103.88.153.198', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZHdhZzJybjZDNTFFVUNKYzNDQVRmSm8yMmMwM09TQ1R0N1FyZGk0TiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2RvY3VtZW50cyI7czo1OiJyb3V0ZSI7czoxNToiZG9jdW1lbnRzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NzEyO30=', 1787803111),
('Q0RPqAdCc8DBkYxvKgz0FInG2wuLd5pDXgrCIJSB', 1, '2404:c0:c202:c237:5d5c:27c4:55fc:9bbc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidmZ4b2tPV21UcXVoNWtSTExxbmFQS1ZKNlBvMVI0b0JYd2t6YlZMTyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDU6Imh0dHBzOi8vYWR3LnByb3dvcmtwcGEuY29tL2RvY3VtZW50cy8xMTc5L3BkZiI7czo1OiJyb3V0ZSI7czoxMzoiZG9jdW1lbnRzLnBkZiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1787836839);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `ai_review_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `nrp`, `jabatan`, `jabatan_diajukan`, `nomor_hp`, `email`, `photo_path`, `email_verified_at`, `password`, `department_id`, `status`, `ai_review_enabled`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Admin IT', NULL, 'ADM-0001', NULL, NULL, NULL, 'adm-0001@ppa-adro.local', NULL, '2026-08-10 04:10:10', '$2y$12$hH4ZS0hzRjuMYoffTMUfCOvzjKVj3cowUVecVSzG9DRzQedPMatnK', 5, 'active', 1, NULL, '2026-07-18 00:14:18', '2026-08-10 04:10:10', NULL),
(667, 'Angga Margi Saputro', NULL, '18064116', 'group_leader', NULL, '085753097927', 'ajipraja471@gmail.com', NULL, NULL, '$2y$12$9TZjhVShBMN7bGddwEE.YObGgTpt.sexToYFyYHd00t/kWtqMeFZe', 5, 'active', 1, NULL, '2026-08-13 15:14:21', '2026-08-13 15:14:21', NULL),
(668, 'Arisal Farzan', NULL, '17021841', 'section_head', NULL, '085753097926', 'ajipraja174@gmail.com', NULL, NULL, '$2y$12$I0q5rcPWvkQgz4yc3vd27.tcVQlxm34WT5oTfxY//mJZmYX.TgiZu', 5, 'active', 1, NULL, '2026-08-13 15:15:03', '2026-08-13 15:15:03', NULL),
(669, 'wahyu binuko', NULL, '16071367', 'pimpinan', NULL, '08123456789', 'anggunsafira471@gmail.com', NULL, NULL, '$2y$12$IN.fhicb65xui4v.9l4bIeY1bp/7/SVLVnO5G/lv.wRJ8a2QRtrAC', NULL, 'active', 1, NULL, '2026-08-13 15:17:30', '2026-08-13 15:17:30', NULL),
(670, 'MD', NULL, 'MD-0001', NULL, NULL, '08123456888', 'devict.ppa@gmail.com', NULL, NULL, '$2y$12$AcWsAoVH2KkOG8smTsZeEu4NqHUYrJwl.oQmnyktOsCKd1Q0L7KtC', 5, 'active', 1, NULL, '2026-08-13 15:23:06', '2026-08-13 15:23:06', NULL),
(671, 'RIZKI DWI SAFITRI', NULL, '23002774', 'group_leader', NULL, '085225882141', 'rizkidwis@ppa.co.id', NULL, NULL, '$2y$12$hZ/uyQunkPnWHpIn/Dhvv.K8VW1Inqa7ul6pBVF4RxkY2unpR8yCi', 7, 'active', 1, NULL, '2026-08-14 16:53:44', '2026-08-14 16:53:44', NULL),
(672, 'AJI PAKERTI HANDIAARTO PUTRA', NULL, '21000860', 'group_leader', NULL, '087887636626', 'ajipakerti@amm.co.id', NULL, NULL, '$2y$12$9s.Mfzcl6cYXwXh0Al7kceMAsEHrzFJto6X/rWyt8JUbt3P81jpzS', 2, 'active', 1, NULL, '2026-08-15 16:11:41', '2026-08-15 16:11:41', NULL),
(673, 'MARCIO CALVIN ROHI', NULL, '23000651', 'group_leader', NULL, '081237767013', 'marcio.calvin@ppa.co.id', 'avatars/BObqobmTlaNul4xxrXI1MXe0fXmV480jYxRKKV71.jpg', NULL, '$2y$12$IXY./wCp4zmSmliVDSq0C.lpG6AEG.6b8U1a8Dbgc8aQAsw/yDP2C', 1, 'active', 1, 'fPt6twoQ5OO6N4bX23oAabWBIVGW3eA1jhol8etdvFiXp1DnNgswFP6pXySV', '2026-08-15 16:13:07', '2026-08-23 08:37:13', NULL),
(674, 'RENDY ABRAHAM DOMIS', NULL, '22004759', 'group_leader', NULL, '085348493635', 'rendy.abraham@ppa.co.id', 'avatars/encHBcXE5WAM6CHp9aWHhMRg5c1PijXzzjNVnctR.jpg', NULL, '$2y$12$.AcoewjfnhGHg0kBSg.6QOvTe7ibzSuNoRx8RwUxEV4Gf.tDbr3C6', 2, 'active', 1, NULL, '2026-08-15 16:14:25', '2026-08-23 10:34:42', NULL),
(675, 'SYAFIQ ABDULLAH', NULL, '17092728', 'departemen_head', NULL, '081320659262', 'syafiq.abdullah@ppa.co.id', NULL, NULL, '$2y$12$JfIN5M3wZOd2xH.JZf3/Y.nreigdmhkek6t45Leiqp1D1P52bDTtW', 7, 'active', 1, NULL, '2026-08-15 16:22:37', '2026-08-15 16:22:37', NULL),
(676, 'RICKY FADLIANDA', NULL, '22000610', 'section_head', NULL, '082328199908', 'ricky.fadlianda@ppa.co.id', NULL, NULL, '$2y$12$.2WCZSli1yRo7V2egTtiI.RtRQFwlPlHwa/jHrK772EZMngiGDY0y', 7, 'active', 1, NULL, '2026-08-15 19:07:47', '2026-08-15 19:07:47', NULL),
(677, 'RACHMAT FACHRUDDIN', NULL, '20000701', 'group_leader', NULL, '085250953131', 'rachmat.fachruddin@ppa.co.id', NULL, NULL, '$2y$12$14KCSQoZOFnPLvIaQAlqye4/5/lCcnu7xdH3FW/YvSGSyKk7UqTr.', 4, 'active', 1, NULL, '2026-08-15 19:08:46', '2026-08-15 19:08:46', NULL),
(678, 'DEDEN DISA ABDULLAH', NULL, '18043829', 'section_head', NULL, '082317050394', 'deden.disa@ppa.co.id', NULL, NULL, '$2y$12$yKDZi1jdOHiHPwKtDymo3eo/JFQVoMRK3uO5E43LQc7x03/5/VgBm', 1, 'active', 1, NULL, '2026-08-15 19:09:49', '2026-08-15 19:09:49', NULL),
(679, 'HERFIT ALMIYA', NULL, '22001871', 'group_leader', NULL, '081254521969', 'herfit.almiyah@ppa.co.id', NULL, NULL, '$2y$12$c.1nwKjuL6xUoGe/HueU8eBfSBFhBF0zQ/UtA.g.2nlSNU/GwyN5K', 3, 'active', 1, NULL, '2026-08-15 19:11:24', '2026-08-15 19:11:24', NULL),
(680, 'KARTIKA HARYO KUSUMO', NULL, '22005011', 'group_leader', NULL, '082237218931', 'kartikaharyokusumo@ppa.co.id', NULL, NULL, '$2y$12$cZOCALGUqwq.EUQyT44rQO1aM7psrqM7vIpr2gOE/ciY6UPViTbWC', 7, 'active', 1, NULL, '2026-08-15 19:12:37', '2026-08-15 19:12:37', NULL),
(681, 'IRWAN ADI PUTRA', NULL, '18074307', 'section_head', NULL, '081345128436', 'irwanputra@ppa.co.id', NULL, NULL, '$2y$12$soRWJ1Bm.gH2ogb4grwgnuBIoB1xY3YQaKMjwJl5eS30kxsUeouZu', 6, 'active', 1, '5OgbrDx5KzUlvDgPyfeSbQKkv6OETTjkdUKrSdxgPC9Wg3zkU4wlCKydzqlS', '2026-08-15 19:13:55', '2026-08-15 19:13:55', NULL),
(682, 'MUHAMMAD RIZAL ASSIDDIQ', NULL, '23002780', 'group_leader', NULL, '089622407542', 'assiddiq.rizal@ppa.co.id', NULL, NULL, '$2y$12$RkuRZ0Ut92iegK8ghr9Kx.P2UZbjeZNQq6ENg78/d3shMMSP1SdSW', 6, 'active', 1, NULL, '2026-08-15 19:15:01', '2026-08-15 19:15:01', NULL),
(683, 'DWI ANANDA ARDIANTO', NULL, '21000718', 'group_leader', NULL, '082115735515', 'dwianandaa21@ppa.co.id', NULL, NULL, '$2y$12$uyFfnLfI/B3aacp3uvg7auX1L0AHI70.Y04aY7l4UNYM.i25OzZLu', 6, 'active', 1, NULL, '2026-08-15 19:16:12', '2026-08-15 19:16:12', NULL),
(684, 'MIFTAQUL ILMI', NULL, '18043807', 'section_head', NULL, '081351963974', 'miftaqulilmi@ppa.co.id', NULL, NULL, '$2y$12$.9WCKEfdwoW/XsiNrGi7ieSUU55qoi0d7Ly6wXOnC7aaNXZzwEgY2', 2, 'active', 1, NULL, '2026-08-15 19:17:50', '2026-08-15 19:17:50', NULL),
(685, 'MOHAMMAD IRKHAS KARIMULLAH', NULL, '18125700', 'section_head', NULL, '081234257401', 'mohamad.irkhas@ppa.co.id', NULL, NULL, '$2y$12$zgS9ieHLy7GRd9X7Lmt0YO8wk32WObOFvUiNTyqMVjduKY/5z4xZe', 1, 'active', 1, 'dSydVWuFRKutWVxss7ZDhxc97CI917X9SL7eWFh58mk2iTu1dSbvAztoiKhH', '2026-08-24 16:28:33', '2026-08-24 16:28:33', NULL),
(686, 'MUAMMER KHADAFI', NULL, '16081467', 'section_head', NULL, '082192129484', 'khadafi@ppa.co.id', NULL, NULL, '$2y$12$KiEQR7SEDX8leIgr0h8SdOdIdVy8vucnoDwiQn.8osYycm6/UlIe.', 1, 'active', 1, 'I0MF0mdjjroxOg4IRco2siE5fMf1eGGT3MfcgzS9NKQTtFQw8Jx7b0J0ArkE', '2026-08-24 16:30:30', '2026-08-24 16:30:30', NULL),
(687, 'KURNIAWAN HAJRIYANTO', NULL, '22005129', 'group_leader', NULL, '081227914789', 'kurniawan.h@ppa.co.id', NULL, NULL, '$2y$12$w0Rsl8SM7t65u/.PyQ.pAe4OvPHSET0SQkZXuJcm8KbcBTmOWTeHi', 7, 'active', 1, NULL, '2026-08-25 13:40:48', '2026-08-25 13:40:48', NULL),
(688, 'ZAIDAN JAUHARI', NULL, '22001948', 'group_leader', NULL, '081255465602', 'zulidanjauhary@ppa.co.id', NULL, NULL, '$2y$12$rSGPUSV.NaYVaxTXjvtWLuqjtwKAq7J1DNJGvr8jzaaWWZuTbW3w6', 7, 'active', 1, NULL, '2026-08-25 13:41:21', '2026-08-25 13:41:21', NULL),
(689, 'DAVID OKTATIASA', NULL, '22005270', 'group_leader', NULL, '081251407427', 'david.oktatiasa@ppa.co.id', NULL, NULL, '$2y$12$rgD3YLEACpjHY3fLAz4wrOuY9knnY1ILO2UxRhFbtEn2YFeq9BdcS', 7, 'active', 1, NULL, '2026-08-25 13:41:54', '2026-08-25 13:41:54', NULL),
(690, 'BRILLIAN ISTANA AUDIO', NULL, '23002773', 'group_leader', NULL, '081359107816', 'brilliandio@ppa.co.id', NULL, NULL, '$2y$12$b8afCjyqfjn3j7As3cHDUeJwk0abBv7wcJL23bmylP3TE7Xm5QwBm', 7, 'active', 1, NULL, '2026-08-25 13:43:00', '2026-08-25 13:43:00', NULL),
(691, 'PASCAL YUSDA ADITAMA', NULL, '24006336', 'group_leader', NULL, '087878837874', 'pascalyusda.aditama@ppa.co.id', NULL, NULL, '$2y$12$1oc1P.i8JMJ4YJchP6612er4l0QJnfPPy6aeTL9.hB2nt5Ryz6N3u', 7, 'active', 1, NULL, '2026-08-25 13:43:31', '2026-08-25 13:43:31', NULL),
(692, 'FEBRI RAMADHAN', NULL, '16041186', 'group_leader', NULL, '085248803233', 'febriramadhan@ppa.co.id', NULL, NULL, '$2y$12$BQt/WSjAxekfuxS60vsunu0FNfoDEO1xb.TC3Kd0B4IBqvOXQpt0G', 7, 'active', 1, NULL, '2026-08-25 13:44:03', '2026-08-25 13:44:03', NULL),
(693, 'ARIE JULMIDAR', NULL, '16111658', 'group_leader', NULL, '085273059879', 'arie.julmidar@ppa.co.id', NULL, NULL, '$2y$12$fxFfNQ58M/qm7LVuF3OIWeBiJXJjw3BXHCtaHKGQ3riCQrkddmFD6', 4, 'active', 1, NULL, '2026-08-25 13:44:49', '2026-08-25 13:44:49', NULL),
(694, 'MUHAMAT RAHMADONI', NULL, '22004619', 'group_leader', NULL, '085752869470', 'Rahma.doni@ppa.co.id', NULL, NULL, '$2y$12$LDh4r/nyRHY0xuND9wsBEuVr2yzm99N/u8r6NmluNoaRCBTX4BXO.', 3, 'active', 1, NULL, '2026-08-25 13:45:39', '2026-08-25 13:45:39', NULL),
(695, 'ARIEF AHMAD', NULL, '23002769', 'group_leader', NULL, '082226063100', 'arief.ahmad@ppa.co.id', NULL, NULL, '$2y$12$PLx1uDH4qWcw/VE1ThpY5.52pul1ePDv6AwVccqAhnKDUHHd0q/Ou', 3, 'active', 1, NULL, '2026-08-25 13:46:10', '2026-08-25 13:46:10', NULL),
(696, 'SLAMET HUDA FIRMANSYAH', NULL, '18125648', 'group_leader', NULL, '081252834817', 'slamethuda.firmansyah@ppa.co.id', NULL, NULL, '$2y$12$41UFvXf0kbI6ToaVcClhC.TnRY8fUBdiYh/EekqGYyaXWMGc4v18.', 5, 'active', 1, NULL, '2026-08-25 13:47:36', '2026-08-25 13:47:36', NULL),
(697, 'YULIA PUTRIANA', NULL, '23002779', 'group_leader', NULL, '085338129972', 'yulia.putriana@ppa.co.id', NULL, NULL, '$2y$12$Wb2vU2FngzZslyzTYu6YGuEpDCB48myt8adBl7bpb.2ulzOl0Bu6i', 1, 'active', 1, NULL, '2026-08-25 13:49:07', '2026-08-25 13:49:07', NULL),
(698, 'DEVINDO ALMIRA VITO', NULL, '18125630', 'group_leader', NULL, '085725084142', 'devindo@ppa.co.id', NULL, NULL, '$2y$12$E.m6RFfuep7c.7hn2FPvw.vJPuVu.OYX2mbMgwUC/mMylA4zIIHoC', 2, 'active', 1, NULL, '2026-08-25 13:49:52', '2026-08-25 13:49:52', NULL),
(699, 'DEDE ISTIAN', NULL, '14100851', 'group_leader', NULL, '082251252977', 'distian41@gmail.com', NULL, NULL, '$2y$12$ojt8IxwaqTmTW3F49nAHOeaQBk6Cr2v1edHwVoyrPBhi4fRP3plHm', 6, 'active', 1, NULL, '2026-08-25 13:50:58', '2026-08-25 13:50:58', NULL),
(700, 'SUNARYO', NULL, '14110760', 'group_leader', NULL, '081259763535', 'sunaryosragenn@gmail.com', NULL, NULL, '$2y$12$dP6qXIsJ52bOMoo692ZsPuapGCXfYniec0Lj9mPYbO8XqCbEYgxOG', 6, 'active', 1, NULL, '2026-08-25 13:51:28', '2026-08-25 13:51:28', NULL),
(701, 'YULIUS TANGGO KADANG', NULL, '15060939', 'group_leader', NULL, '085341472136', 'yuliustanggokadang@gmail.com', NULL, NULL, '$2y$12$X8WDFygxpL6mE.9LKDYm8uaFCulYjSgTOWHuCzlEBQ4zuBgogbNp6', 6, 'active', 1, NULL, '2026-08-25 13:51:55', '2026-08-25 13:51:55', NULL),
(702, 'HENDRO DARMAWANTO', NULL, '15111113', 'group_leader', NULL, '081357883211', 'hendrodarmawanto@gmail.com', NULL, NULL, '$2y$12$QL8WW1TEvbcbt0zOSByuSeHs0gKl/TAfsNiQ6wLuL/8SxhMgZ2WKC', 6, 'active', 1, NULL, '2026-08-25 13:52:56', '2026-08-25 13:52:56', NULL),
(703, 'RIMBA WAHYUDI', NULL, '16101567', 'group_leader', NULL, '081346287954', 'rimbha1105@gmail.com', NULL, NULL, '$2y$12$L11heSSTqBzAKbst5/GVJOxxfGF9e0QZ1jQu.Dr9rPccea/eNdmqS', 6, 'active', 1, NULL, '2026-08-25 14:04:31', '2026-08-25 14:04:31', NULL),
(704, 'BAYU TRISWANTO', NULL, '17052250', 'group_leader', NULL, '081253326531', 'bayutriswanto86@gmail.com', NULL, NULL, '$2y$12$zU0fO2PrXyG1pNqX9fsHv.06gxgmIv/ZERQNoAHYwDoSWpSVjKHWu', 6, 'active', 1, NULL, '2026-08-25 14:05:21', '2026-08-25 14:05:21', NULL),
(705, 'YUDIANTO', NULL, '17082610', 'group_leader', NULL, '085389579136', 'antoyudi43177@gmail.com', NULL, NULL, '$2y$12$qK99oKfQTYg0oKd.8fQXBOkjVKgbhjktpBf2jQWt72MVb4B/eqjjS', 6, 'active', 1, NULL, '2026-08-25 14:05:55', '2026-08-25 14:05:55', NULL),
(706, 'TRI JOKO', NULL, '17102901', 'group_leader', NULL, '081393781553', 'trijoko1992@gmail.com', NULL, NULL, '$2y$12$X9Suceqnsg.CIbzRg.YSGepUA0w1DJEB6oXny1AgyHSpyMRHqejDO', 6, 'active', 1, NULL, '2026-08-25 14:06:27', '2026-08-25 14:06:27', NULL),
(707, 'SARI AHMAD WAHYUDIN', NULL, '17113017', 'group_leader', NULL, '085255577739', 'ut.yudi311@gmail.com', NULL, NULL, '$2y$12$a1CukIt.oVqoHgcwXL7Pnu78oZw7Leu1wybOszmREHD.7lst3K5YG', 6, 'active', 1, NULL, '2026-08-25 14:07:01', '2026-08-25 14:07:01', NULL),
(708, 'BANGKIT YULIANTO', NULL, '18105114', 'group_leader', NULL, '082153700165', 'bangkityulianto27@gmail.com', NULL, NULL, '$2y$12$0uBa215QtmNOELznVsz4POxXjivjUE8PsOirtVpdcV6E9i64IIHYi', 6, 'active', 1, NULL, '2026-08-25 14:07:54', '2026-08-25 14:07:54', NULL),
(709, 'ANGGA RACHMA PUTRA', NULL, '18125664', 'group_leader', NULL, '082154967899', 'angga130513@gmail.com', NULL, NULL, '$2y$12$ePbvKlxfy0GgzU2WncLv4eBo9M9Hy90kzaEiL6655tUTDfSbBfg4y', 6, 'active', 1, NULL, '2026-08-25 14:08:18', '2026-08-25 14:08:18', NULL),
(710, 'RIZAL PAHLAPI', NULL, '19005966', 'group_leader', NULL, '085220757166', 'pahlapirizal2@gmail.com', NULL, NULL, '$2y$12$b5IEcW4SUWy/bP9JqZqlp.wEbBFM/LyswLfIXgmtBr2tTJ41n59Km', 6, 'active', 1, NULL, '2026-08-25 14:13:31', '2026-08-25 14:13:31', NULL),
(711, 'FAHRIO ABRIAN INDRA LUPI', NULL, '19019140', 'group_leader', NULL, '081258300111', 'fahrioabrian7@gmail.com', NULL, NULL, '$2y$12$5DxnuWuYd75dJ6hfBm.c0.6XeAXvYENcq8Vf2biwjACmcTeQhwH.G', 6, 'active', 1, NULL, '2026-08-25 14:14:38', '2026-08-25 14:14:38', NULL),
(712, 'LUDI ADHARI MARLE', NULL, '19020361', 'group_leader', NULL, '082254747594', 'ludiadharimarle@gmail.com', NULL, NULL, '$2y$12$wGwwNyFIZCn61EhXnGUvC.XISPjAZ7/aotGzJWqJTVQ5kxBIOmUd2', 6, 'active', 1, NULL, '2026-08-25 14:15:08', '2026-08-25 14:15:08', NULL),
(713, 'SLAMET BUDIYANTO', NULL, '19020609', 'group_leader', NULL, '082225274811', 'slametbudiyanto2022@gmail.com', NULL, NULL, '$2y$12$lLr/vgSTgeK/xcRk7AXeweubCcHTD4XluJ9TiR8yERE5FjDPbwxiO', 6, 'active', 1, NULL, '2026-08-25 14:16:25', '2026-08-25 14:16:25', NULL),
(714, 'ISNAN FAUZY', NULL, '21002900', 'group_leader', NULL, '081214700724', 'isnanfauzy77@gmail.com', NULL, NULL, '$2y$12$DR.aSeaSc.O5GZ/p7aHd0.LGuiH9c4gzKPqS2G5aaW18Si.mnRcKK', 6, 'active', 1, NULL, '2026-08-25 14:17:16', '2026-08-25 14:17:16', NULL),
(715, 'INORA SIPAYUNG', NULL, '22000555', 'group_leader', NULL, '085248302505', 'innora.bagok@gmail.com', NULL, NULL, '$2y$12$6ne8b/cmqZoecmTUkDDBY.75xrTVYBTwsxEaH6UqjdrL8QmDqdC4.', 6, 'active', 1, NULL, '2026-08-25 14:18:22', '2026-08-25 14:18:22', NULL),
(716, 'MUH RIDWAN DALI', NULL, '22001723', 'group_leader', NULL, '082357895866', 'muhridwandali@gmail.com', NULL, NULL, '$2y$12$9PM55lPssxZnlOdJNIkSWOhrcGnD7qe4hmspp55JTx4wtUrFd/UC6', 6, 'active', 1, NULL, '2026-08-25 14:19:01', '2026-08-25 14:19:01', NULL),
(717, 'MUHAMMAD ARIEF KURNIAWAN,S.T.', NULL, '22002074', 'group_leader', NULL, '081323624224', 'muhammadariefk17@gmail.com', NULL, NULL, '$2y$12$z3UKCtvgpkxWg6RD/FSlXOkd.657tV6eaArrrQNrSVURbEEQ8p742', 6, 'active', 1, NULL, '2026-08-25 14:20:38', '2026-08-25 14:20:38', NULL),
(718, 'AGUNG PAMBUDI', NULL, '22002188', 'group_leader', NULL, '082213288198', 'agungpambudi461@gmail.com', NULL, NULL, '$2y$12$Te.AzvQqtkjJrgeyUyL.X.kQxRYNeOkIuo4B9d6GivrKP/EITY1I6', 6, 'active', 1, NULL, '2026-08-25 14:21:08', '2026-08-25 14:21:08', NULL),
(719, 'UMBU AGUS WINARDI', NULL, '22002807', 'group_leader', NULL, '085250520202', 'umbu414@gmail.com', NULL, NULL, '$2y$12$0S1LdVRQu5WR.jRjL2XD6OcfMjUYyTUBef5focj3A7BUXaaG9w6JK', 6, 'active', 1, NULL, '2026-08-25 14:21:43', '2026-08-25 14:21:43', NULL),
(720, 'ARIF BUDI SUSANTO', NULL, '22003480', 'group_leader', NULL, '082158176328', 'arifbudisusanto1@gmail.com', NULL, NULL, '$2y$12$e8kmWhsOj0DElAD93IM9hOUQettKf.suNeCt4zYJA.NiRwJ.t4MpS', 6, 'active', 1, NULL, '2026-08-25 14:22:11', '2026-08-25 14:22:11', NULL),
(721, 'MUHAMMAD HARIANTO', NULL, '22003562', 'group_leader', NULL, '082190931690', 'harry.ace5@gmail.com', NULL, NULL, '$2y$12$VyCoGSioNbRrEfYq9OUsiuCQ3F7KajOmD00LK6TWCkGupzCAHEAey', 6, 'active', 1, NULL, '2026-08-25 14:22:40', '2026-08-25 14:22:40', NULL),
(722, 'YULIUS SULO', NULL, '22003588', 'group_leader', NULL, '082158101123', 'julius.komatsu@gmail.com', NULL, NULL, '$2y$12$iBDRM/7UYcQn0q3lkKWEne0SFjfiL/fOhaH8BMwGGvV3SBFCHSEZy', 6, 'active', 1, NULL, '2026-08-25 14:23:50', '2026-08-25 14:23:50', NULL),
(723, 'YUDHI PASANDA', NULL, '22003636', 'group_leader', NULL, '082291072808', 'yudhia.pasanda@gmail.com', NULL, NULL, '$2y$12$j2M5MidHONtX8iq.krynJuKpttAtiHiu/hsOq8sLYvcR1pzoXRgc6', 6, 'active', 1, NULL, '2026-08-25 14:24:18', '2026-08-25 14:24:18', NULL),
(724, 'MUHAMMAD RIZAL', NULL, '22004158', 'group_leader', NULL, '085350248007', 'muhammxdrizal@gmail.com', NULL, NULL, '$2y$12$Rl1nGcU20AW0rxuRaEIDd.jH7Goz4lie.pTfe4fZ7XEbzo3f49IAi', 6, 'active', 1, NULL, '2026-08-25 14:24:44', '2026-08-25 14:24:44', NULL),
(725, 'AGUS SUPARTO', NULL, '22004438', 'group_leader', NULL, '081331167145', 'agoes.soeparto.as@gmail.com', NULL, NULL, '$2y$12$dhykqqTZ95mvFPhfFEZMheoNtRsrx7iLuAF.wi3nT9GZhunFE4yz6', 6, 'active', 1, NULL, '2026-08-25 14:25:17', '2026-08-25 14:25:17', NULL),
(726, 'ABDULLAH', NULL, '22004760', 'group_leader', NULL, '082154818123', 'abdullah95dullah@gmail.com', NULL, NULL, '$2y$12$clXpO3HYP3UeY0UBSW1gT.S6LC.Ut0iswJrX/wbm25pUfzOFnPfWy', 6, 'active', 1, NULL, '2026-08-25 14:26:07', '2026-08-25 14:26:07', NULL),
(727, 'PETRUS SALIM KATIK', NULL, '22004835', 'group_leader', NULL, '081351677225', 'aryamantha741@gmail.com', NULL, NULL, '$2y$12$0fQCp6088Ci/QOLIseTw4u3e28ErbpUQV3dEs7pPcrJTd6vTKku8u', 6, 'active', 1, NULL, '2026-08-25 14:26:31', '2026-08-25 14:26:31', NULL),
(728, 'HERIANTO R.P', NULL, '22004926', 'group_leader', NULL, '085390156813', 'heriantorombepayung123@gmail.com', NULL, NULL, '$2y$12$6J8wbTQLPMVk.OEW1pCSDuxcu0PcCbvoiakbaTTHBHLbSYLq7c6sS', 6, 'active', 1, NULL, '2026-08-25 14:26:52', '2026-08-25 14:26:52', NULL),
(729, 'AFNAN DOMILI', NULL, '22005131', 'group_leader', NULL, '081347192073', 'afnandomili544@gmail.com', NULL, NULL, '$2y$12$GYCY9IVBUYlUFN07NJBoGeZq9PySXDu4d6lkEVVH7j2tT1Xti5tOi', 6, 'active', 1, NULL, '2026-08-25 14:27:15', '2026-08-25 14:27:15', NULL),
(730, 'OKTAVIANUS', NULL, '22005272', 'group_leader', NULL, '081350010608', 'oktavianus268@gmail.com', NULL, NULL, '$2y$12$eisv8ojR0M6dqx8F/UdcTehlxQH1iCeote3aj1du3TFegR2Qdf2tK', 6, 'active', 1, NULL, '2026-08-25 14:27:51', '2026-08-25 14:27:51', NULL),
(731, 'RICKY ARIA', NULL, '22005273', 'group_leader', NULL, '082321118196', 'rickyaria1999@gmail.com', NULL, NULL, '$2y$12$ysztT.yTlVT5tkf9njNAHOnwWp9rNPY3d.dmqYxc.un/GkEXIWOFW', 6, 'active', 1, NULL, '2026-08-25 14:28:17', '2026-08-25 14:28:17', NULL),
(732, 'AHMAD SAHBANA', NULL, '22005435', 'group_leader', NULL, '082268560808', 'banappaadw@gmail.com', NULL, NULL, '$2y$12$gCWbY1CTlhhEa.hSYziEseY8nf1dq8ijLCBzO3IuVzyMyADrFwmM2', 6, 'active', 1, NULL, '2026-08-25 14:28:42', '2026-08-25 14:28:42', NULL),
(733, 'YULIANTO', NULL, '22005437', 'group_leader', NULL, '085341680963', 'yulianto250794@gmail.com', NULL, NULL, '$2y$12$JzRyQzQGr5TdGTKpruTl6OeWj.qBjGRWm6LxBEfqYzbDOaWsJS6AO', 6, 'active', 1, NULL, '2026-08-25 14:29:10', '2026-08-25 14:29:10', NULL),
(734, 'ARDIANUS RANTE ALLO', NULL, '22005482', 'group_leader', NULL, '085299658277', 'rantealloardianus@gmail.com', NULL, NULL, '$2y$12$Z9b.pfysldNKoZ8d1INc7O6rfWuB3tNlRxxtv7NiFL1NQuzLMbpxK', 6, 'active', 1, NULL, '2026-08-25 14:29:56', '2026-08-25 14:29:56', NULL),
(735, 'CHAIRUL HADI SUPRAYITNO', NULL, '22005569', 'group_leader', NULL, '082293158466', 'smdhairul@gmail.com', NULL, NULL, '$2y$12$m2gk5W0BvlKIt.uZ0RoI.uSPO.5liQri4IoeCnkKzCFlqRTgKez7m', 6, 'active', 1, NULL, '2026-08-25 14:30:24', '2026-08-25 14:30:24', NULL),
(736, 'RADHITYA HAJI SUKMA', NULL, '23000800', 'group_leader', NULL, '081353376191', 'radhityahajisukma@gmail.com', NULL, NULL, '$2y$12$CecF3daYJ4O15mxl2sV/2upb/pZzmtZknpBdUASidMDlNsMC6HkzC', 6, 'active', 1, NULL, '2026-08-25 14:30:51', '2026-08-25 14:30:51', NULL),
(737, 'M RAHIM', NULL, '23000803', 'group_leader', NULL, '085348437394', 'rahimmuhammad93@yahoo.com', NULL, NULL, '$2y$12$hrEbFBNhmifICKaI8wLNhOzGB8J/DMNh5wwA/aBiyVt8C9aCcVKDy', 6, 'active', 1, NULL, '2026-08-25 14:31:29', '2026-08-25 14:31:29', NULL),
(738, 'THEO ARMANTO TALEBONG', NULL, '23000847', 'group_leader', NULL, '085399370650', 'theoarmanto07@gmail.com', NULL, NULL, '$2y$12$TYhPUHZl0M.LZ56RcTjBce6d9VaG0QsLJ75/eABg.jx1u/W/bYm7a', 6, 'active', 1, NULL, '2026-08-25 14:31:55', '2026-08-25 14:31:55', NULL),
(739, 'ANWAR SUHADI', NULL, '23001064', 'group_leader', NULL, '082154883272', 'anwarsuhadi@gmail.com', NULL, NULL, '$2y$12$yTxo/ivddnngWFsKrTqyR.sUESlHALQV2Dz3Cu10/EpYTKlovPV/C', 6, 'active', 1, NULL, '2026-08-25 14:32:20', '2026-08-25 14:32:20', NULL),
(740, 'MUHAMMAD SAIFUR RAHMAN', NULL, '23001108', 'group_leader', NULL, '082153238503', 'saifurrahman17012@gmail.com', NULL, NULL, '$2y$12$GPbMegKLU/2bfoCHxdmfQ.MWoMuYCPaB0fTKFUR82NvcvkaJPqkjW', 6, 'active', 1, NULL, '2026-08-25 14:32:44', '2026-08-25 14:32:44', NULL),
(741, 'PUJIANTO', NULL, '23001111', 'group_leader', NULL, '082350415363', 'asuspuji@gmail.com', NULL, NULL, '$2y$12$vZsk4jX8tR0lnpnHjX5GQOMjFijFT6vfszMKwQgpfLTcAoNdLg7Pa', 6, 'active', 1, NULL, '2026-08-25 14:33:11', '2026-08-25 14:33:11', NULL),
(742, 'SAMDIE BARAU', NULL, '23001217', 'group_leader', NULL, '081357032379', 'bsamdie@gmail.com', NULL, NULL, '$2y$12$IDHCSqWmgm8uBUi0OdErX.QBfFktK5fY1lLhKXrLVw7NNfBsFOOle', 6, 'active', 1, NULL, '2026-08-25 14:33:40', '2026-08-25 14:33:40', NULL),
(743, 'ROBIBEN SIREGAR', NULL, '23001415', 'group_leader', NULL, '082299782036', 'robibensiregar93@gmail.com', NULL, NULL, '$2y$12$ppZv6xINDm5Ctpy7gS4ePur2NUrQUBJwqWxLguwlK2zFFzVvknkOS', 6, 'active', 1, NULL, '2026-08-25 14:34:05', '2026-08-25 14:34:05', NULL),
(744, 'AGUNG BUDI HARSONO', NULL, '23001535', 'group_leader', NULL, '081250149860', 'agung.budi.hs.st@gmail.com', NULL, NULL, '$2y$12$ZBvPOBnMtSqjJkpJNWryieC79P4KUIPl1vtD.7mNbX2B8k6Jxo95y', 6, 'active', 1, NULL, '2026-08-25 14:34:33', '2026-08-25 14:34:33', NULL),
(745, 'DELVIN ALDI PRAYOGA', NULL, '23002781', 'group_leader', NULL, '081351059206', 'delvinaldy72@gmail.com', NULL, NULL, '$2y$12$T.yA9d6/E4ce2bH7FtqgluZK0GvOVWpzbwgulkNi0BniV32Fo3jOi', 6, 'active', 1, NULL, '2026-08-25 14:35:33', '2026-08-25 14:35:33', NULL),
(746, 'ARSAD NOVANDRA', NULL, '23002828', 'group_leader', NULL, '082285336143', 'arsadnovandra30@gmail.com', NULL, NULL, '$2y$12$rNggs5.oDZrAVh0lut29N.efdkmgqIX1U44iPCEFlNIV92raigRH6', 6, 'active', 1, NULL, '2026-08-25 14:36:05', '2026-08-25 14:36:05', NULL),
(747, 'MUHAMMAD SAIPULLAH', NULL, '23002928', 'group_leader', NULL, '082154199267', 'saiful.pule@gmail.com', NULL, NULL, '$2y$12$uZeYnuz0J7gExdDKjU/ps.MxOB1ELILlDngxwaNgX5djWNON8pEJC', 6, 'active', 1, NULL, '2026-08-25 14:36:35', '2026-08-25 14:36:35', NULL),
(748, 'MOHAMMAD NADHIP', NULL, '24006335', 'group_leader', NULL, '085394089752', 'mhmmdnadip@gmail.com', NULL, NULL, '$2y$12$f5lbhf8jslOuhf6SrGPWjuKmKhDo6z.sr5ULLWeGFJgJcbOAqwxZS', 6, 'active', 1, NULL, '2026-08-25 14:37:01', '2026-08-25 14:37:01', NULL),
(749, 'HERMAWAN WAHYU RAHMADIANTO', NULL, '25002283', 'group_leader', NULL, '081347083504', 'hermawansp45@gmail.com', NULL, NULL, '$2y$12$PR5yq4Wo12HrClHo.s9oTel8sNPFf7ZiAINxBnrldBcL7ZAGhUKxC', 6, 'active', 1, NULL, '2026-08-25 14:37:27', '2026-08-25 14:37:27', NULL),
(750, 'HENDRA RUJIADI ADHA', NULL, '25002489', 'group_leader', NULL, '081346218446', 'hendrarujiadi@gmail.com', NULL, NULL, '$2y$12$nOUm2eRfALXdgnvMbrNDFO/KcLByyb12TlzTnk0TtCFd0h37ugvPS', 6, 'active', 1, NULL, '2026-08-25 14:37:53', '2026-08-25 14:37:53', NULL),
(751, 'DITYANTO MUHAMMAD TAUFIK', NULL, '25002493', 'group_leader', NULL, '087825447534', 'dityantomt@gmail.com', NULL, NULL, '$2y$12$TzT9i8LqCBeyOuL4Z38iuep9DNNbifCbkXGQfvHIeP1MRmpfk1HT6', 6, 'active', 1, NULL, '2026-08-25 14:38:15', '2026-08-25 14:38:15', NULL),
(752, 'FAUJI', NULL, '25002993', 'group_leader', NULL, '085249649938', 'paujiyy@gmail.com', NULL, NULL, '$2y$12$boTS9VeucuM6LTuzxfwGL.7nEojJbF.fIqFy8/gaoEldMaBYKVMD6', 6, 'active', 1, NULL, '2026-08-25 14:38:45', '2026-08-25 14:38:45', NULL),
(753, 'ADIL SULTHONI', NULL, '25003110', 'group_leader', NULL, '082113825848', 'adilsulthoni23@gmail.com', NULL, NULL, '$2y$12$SfS.PBvacKARrj0ME7hrJeL4IxG4jtc1X8S9cy2Z0xO1H3IEFBye2', 6, 'active', 1, NULL, '2026-08-25 14:39:22', '2026-08-25 14:39:22', NULL),
(754, 'MUHAMMAD RICKY WURIANDI', NULL, '26000432', 'group_leader', NULL, '081242173663', 'muhammadrickyw19@gmail.com', NULL, NULL, '$2y$12$PMmsTRW0rNGVu5VkvyKO0eE54p47uwugCw8k8QuodvqwrUwDJP/wW', 6, 'active', 1, NULL, '2026-08-25 14:39:56', '2026-08-25 14:39:56', NULL),
(755, 'FERRY BUDIMAN', NULL, '25003133', 'group_leader', NULL, '085251104555', 'ferrybudiman97@gmail.com', NULL, NULL, '$2y$12$2c5Qax.YrwZBuJQx0Rnheu51U82tNxH6L2A7MEIP9PZCw/.uw8mTi', 6, 'active', 1, NULL, '2026-08-25 14:40:25', '2026-08-25 14:40:25', NULL),
(756, 'DAMARA MULYA PERMANA', NULL, '26000434', 'group_leader', NULL, '081296460077', 'damaramulyapermana@gmail.com', NULL, NULL, '$2y$12$2gaiPyV92YA4/DH07HHY3u.pmCLr1thoHihShmTjh6NfzlCUCAppy', 6, 'active', 1, NULL, '2026-08-25 14:40:53', '2026-08-25 14:40:53', NULL),
(757, 'ZAKKA RIDHA', NULL, '26000435', 'group_leader', NULL, '082111452265', 'zakkaridha02@gmail.com', NULL, NULL, '$2y$12$L7UEB7yxiRinjvy6FV/UM.JbTFPKd/N8IIq8VBr0cwjh.JujjSc6O', 6, 'active', 1, NULL, '2026-08-25 14:41:20', '2026-08-25 14:41:20', NULL),
(758, 'KADEK DWI PRABAWA', NULL, '26000443', 'group_leader', NULL, '081337015474', 'dwiprabawa16@gmail.com', NULL, NULL, '$2y$12$Li3yxQZgOGJ5tcvs4LhPWeAV/2XqvsuMtyOvCUm.DcitsQtHlqC1y', 6, 'active', 1, NULL, '2026-08-25 14:41:55', '2026-08-25 14:41:55', NULL),
(759, 'SAMSUL ARIFIN', NULL, '15050890', 'group_leader', NULL, '081350390399', 'samsularifin.opd@ppa.co.id', NULL, NULL, '$2y$12$0WcguNNQFgdAjJFQ5UWiFejNThxq3/4qxgwQNP9HtavwZ8lsE6.xy', 6, 'active', 1, NULL, '2026-08-25 14:42:26', '2026-08-25 14:42:26', NULL),
(760, 'EKO YULIANTO', NULL, '15111111', 'group_leader', NULL, '085316825555', 'eyulianto@ppa.co.id', NULL, NULL, '$2y$12$pdc.SxmTL5lY45Ec8SkCYOyjrNESnfs27le5yBzU2Dg39e8V/XtZm', 6, 'active', 1, NULL, '2026-08-25 14:42:58', '2026-08-25 14:42:58', NULL),
(761, 'BAYU WARDHANA', NULL, '17123080', 'group_leader', NULL, '082150628918', 'bayu.wardana@ppa.co.id', NULL, NULL, '$2y$12$pAZa2kXfbb8piJ46qX1qXOZabBCeG/hyYqlBPhRVs0PdjMIihe7li', 6, 'active', 1, NULL, '2026-08-25 14:43:26', '2026-08-25 14:43:26', NULL),
(763, 'MICKEL SANTONI', NULL, '22005258', 'group_leader', NULL, '081251276488', 'mickel.santoni@ppa.co.id', NULL, NULL, '$2y$12$QHXCNERitQC.apwjAgHBbOVHAe2eyIJZAQl1/G/RQpdUbMePN49K2', 6, 'active', 1, NULL, '2026-08-25 14:50:21', '2026-08-25 14:50:21', NULL),
(764, 'EVA PRAMUDEA SAFITRI', NULL, '24006327', 'group_leader', NULL, '089613720853', 'evapramudea@ppa.co.id', NULL, NULL, '$2y$12$BYM/SCkyFwNz7h7JqPX54OkkJ5wGhiD6jctnKuNpyhFRRNdQzhEz.', 4, 'active', 1, NULL, '2026-08-25 14:51:15', '2026-08-25 14:51:15', NULL),
(765, 'AHMAD RYAN AL AQSA', NULL, '26003043', 'group_leader', NULL, '085243190409', 'ahmadryanalaqsha@ppa.co.id', NULL, NULL, '$2y$12$RliLYaYCaceRmFxmqT2PpuPyVVYq35VW7bRpJ0YXCzF7qPkdhvGS6', 1, 'active', 1, NULL, '2026-08-25 14:52:19', '2026-08-25 14:52:19', NULL),
(766, 'ZULFA HIFNI FAJRIYAH', NULL, '24006329', 'group_leader', NULL, '085337250820', 'zulfahifni@ppa.co.id', NULL, NULL, '$2y$12$6D2p.GLlIlnNFYjXFeg2duiILSnFnLpw1wvFJWXwdAxasGb.AN3FO', 1, 'active', 1, NULL, '2026-08-25 14:55:08', '2026-08-25 14:55:08', NULL),
(767, 'SHAFFA RASYA FERDINAND PUTRI', NULL, '26000433', 'group_leader', NULL, '081212178262', 'shaffarasyaferdinandputri@gmail.com', NULL, NULL, '$2y$12$.zImrf39GnVUKMMS6DJYs.7EL0yFMlV5JtOqmDK2KB2NMJ5yGzWum', 1, 'active', 1, NULL, '2026-08-25 14:55:44', '2026-08-25 14:55:44', NULL),
(768, 'FANI NUR HIDAYAT', NULL, '23002778', 'group_leader', NULL, '085173441048', 'fani.hidayat@ppa.co.id', NULL, NULL, '$2y$12$BYHRc7x5ltLdFuSzfnf0XOmKagyIGskj9PZ2gDDv84qcgp1NaM/qu', 1, 'active', 1, NULL, '2026-08-25 14:56:12', '2026-08-25 14:56:12', NULL),
(769, 'AHMAD WAHID ANWARUDIN', NULL, '22003516', 'group_leader', NULL, '082243857514', 'anwarwahid71@gmail.com', NULL, NULL, '$2y$12$57OMS3UBfKfLJ5LcBrsHJeMnEmaGpdHPiuMmV8laDk999SghIEquW', 1, 'active', 1, NULL, '2026-08-25 14:56:40', '2026-08-25 14:56:40', NULL),
(770, 'YUSRIAN HIDAYAT', NULL, '22004823', 'group_leader', NULL, '082310686312', 'yusrianhidayat@gmail.com', NULL, NULL, '$2y$12$PVGCwesyEQAuyLWgeRGf4eKvNbBC97SELSwbp.nD3oytQ.iw8Jr4y', 1, 'active', 1, NULL, '2026-08-25 14:57:16', '2026-08-25 14:57:16', NULL),
(771, 'VIRGO HIDAYAT SUROSO', NULL, '21001417', 'group_leader', NULL, '082352024279', 'Virgo.hidayat@ppa.co.id', NULL, NULL, '$2y$12$1UlS9xTJVgzvRm9QdbrOdexmGmTW8HXNPP0W.Z2govvJRcDCpzD2q', 1, 'active', 1, NULL, '2026-08-25 14:58:34', '2026-08-25 14:58:34', NULL),
(772, 'SARJU', NULL, '22001345', 'group_leader', NULL, '085235322442', 'sarju@ppa.co.id', NULL, NULL, '$2y$12$ZTTNlmmZuWbJIGIF.WZacuqYWV.ye8euc/nGgqMuHgZQ0ntyVPD0.', 6, 'active', 1, NULL, '2026-08-25 15:02:31', '2026-08-25 15:02:31', NULL),
(773, 'ZAINAL ABIDIN', NULL, '22004627', 'departemen_head', NULL, '081232660630', 'zainal.abidin@amm.id', NULL, NULL, '$2y$12$H9aGWOPSLLH.qA.OQ.vnHu2XgsOxAH2m3F79BJprowIAMuqZxTC7y', 2, 'active', 1, NULL, '2026-08-25 15:07:03', '2026-08-25 15:07:03', NULL),
(774, 'MOHAMAD AKHSAN ASHARIADI', NULL, '18074525', 'group_leader', NULL, '085226819678', 'akhsan.ashariadi@ppa.co.id', NULL, NULL, '$2y$12$Wl3xrJI7CmJVzcv5fDaW0O3Jmkp1VYy1LQGkWAkB.KCsBXIxZwqna', 2, 'active', 1, NULL, '2026-08-25 15:07:45', '2026-08-25 15:07:45', NULL),
(775, 'ENGGAR EKA PRASETYA', NULL, '14040677', 'group_leader', NULL, '082277552282', 'enggarekaprasetya@ppa.co.id', NULL, NULL, '$2y$12$nJ10jy1A4FgfEfF6qV77JOkP.yyWfv71h8wveSWOSOhp/2GxIlK6O', 2, 'active', 1, NULL, '2026-08-25 15:08:19', '2026-08-25 15:08:19', NULL),
(776, 'HAIRANI', NULL, '22003449', 'group_leader', NULL, '085246229391', 'hairani@ppa.co.id', NULL, NULL, '$2y$12$S435SNg1kVLnykHnLgw/Uui2CW69H4PFj9ZxKMLajcgFshBgW6PP6', 2, 'active', 1, NULL, '2026-08-25 15:09:00', '2026-08-25 15:09:00', NULL),
(777, 'HERI KUSWANTO', NULL, '19020756', 'group_leader', NULL, '085346806861', 'herikuswanto@ppa.co.id', NULL, NULL, '$2y$12$xRstZJUTlR8bwEeAMngUlO.vWbWY7boJOTZgAEXS1Zj/zb4nxmPxu', 2, 'active', 1, NULL, '2026-08-25 15:09:36', '2026-08-25 15:09:36', NULL),
(778, 'EDO SYAHRIZAL', NULL, '24006328', 'group_leader', NULL, '081261989969', 'edo.syahrizal@ppa.co.id', NULL, NULL, '$2y$12$FbsqZDcm2JJHAUhqFgTckuI/0QQfHKkKoGW0gcIZR5BYSrCTfqIJm', 2, 'active', 1, NULL, '2026-08-25 15:10:07', '2026-08-25 15:10:07', NULL),
(779, 'NOVTANDHI PANANLULUY', NULL, '230256', 'staff', 'Admin Produksi', '082232116054', NULL, NULL, NULL, '$2y$12$d6HMTNR.tTjry9/ep/SF4eIFWzahscByKOhxZst54OB.16zNDzqxW', 6, 'pending', 1, NULL, '2026-08-26 16:12:18', '2026-08-26 16:12:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_off_days`
--

CREATE TABLE `user_off_days` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `jenis` enum('cuti','off_day','dinas_luar') NOT NULL DEFAULT 'cuti',
  `mulai` date NOT NULL,
  `sampai` date NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approvals_document_id_foreign` (`document_id`),
  ADD KEY `approvals_approver_id_index` (`approver_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attachments_document_id_foreign` (`document_id`);

--
-- Indexes for table `attachment_comments`
--
ALTER TABLE `attachment_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attachment_comments_attachment_id_foreign` (`attachment_id`),
  ADD KEY `attachment_comments_user_id_foreign` (`user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_index` (`user_id`),
  ADD KEY `audit_logs_document_id_index` (`document_id`),
  ADD KEY `audit_logs_action_index` (`action`),
  ADD KEY `audit_logs_created_at_index` (`created_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_document_type_id_foreign` (`document_type_id`),
  ADD KEY `documents_revises_document_id_foreign` (`revises_document_id`),
  ADD KEY `documents_doc_number_final_index` (`doc_number_final`),
  ADD KEY `documents_doc_number_index` (`doc_number`),
  ADD KEY `documents_department_id_index` (`department_id`),
  ADD KEY `documents_status_index` (`status`),
  ADD KEY `documents_reviewer_id_index` (`reviewer_id`),
  ADD KEY `documents_approver_id_index` (`approver_id`),
  ADD KEY `documents_created_by_index` (`created_by`);

--
-- Indexes for table `document_authors`
--
ALTER TABLE `document_authors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_authors_document_id_user_id_unique` (`document_id`,`user_id`),
  ADD KEY `document_authors_user_id_foreign` (`user_id`);

--
-- Indexes for table `document_contents`
--
ALTER TABLE `document_contents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_contents_document_id_section_key_unique` (`document_id`,`section_key`),
  ADD KEY `document_contents_section_key_index` (`section_key`);

--
-- Indexes for table `document_feedback`
--
ALTER TABLE `document_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_feedback_feedback_number_unique` (`feedback_number`),
  ADD KEY `document_feedback_user_id_foreign` (`user_id`),
  ADD KEY `document_feedback_replied_by_foreign` (`replied_by`),
  ADD KEY `document_feedback_revision_document_id_foreign` (`revision_document_id`),
  ADD KEY `document_feedback_document_id_status_index` (`document_id`,`status`),
  ADD KEY `document_feedback_status_index` (`status`);

--
-- Indexes for table `document_reads`
--
ALTER TABLE `document_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_reads_document_id_user_id_unique` (`document_id`,`user_id`),
  ADD KEY `document_reads_user_id_foreign` (`user_id`),
  ADD KEY `document_reads_document_id_last_read_at_index` (`document_id`,`last_read_at`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_types_code_unique` (`code`);

--
-- Indexes for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_versions_document_id_foreign` (`document_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `informasi`
--
ALTER TABLE `informasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `informasi_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `informasi_kategori_nomor_index` (`kategori`,`nomor`),
  ADD KEY `informasi_kategori_index` (`kategori`),
  ADD KEY `informasi_berlaku_index` (`berlaku`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_checklist_items`
--
ALTER TABLE `job_checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_checklist_position` (`job_execution_id`,`langkah_ke`,`bahaya_ke`,`pengendalian_ke`);

--
-- Indexes for table `job_executions`
--
ALTER TABLE `job_executions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_executions_document_id_foreign` (`document_id`),
  ADD KEY `job_executions_user_id_foreign` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_document_id_foreign` (`document_id`),
  ADD KEY `reviews_reviewer_id_index` (`reviewer_id`);

--
-- Indexes for table `review_annotations`
--
ALTER TABLE `review_annotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_annotations_review_id_foreign` (`review_id`),
  ADD KEY `review_annotations_section_key_index` (`section_key`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_nrp_unique` (`nrp`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_department_id_index` (`department_id`),
  ADD KEY `users_status_index` (`status`);

--
-- Indexes for table `user_off_days`
--
ALTER TABLE `user_off_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_off_days_user_id_mulai_sampai_index` (`user_id`,`mulai`,`sampai`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `attachment_comments`
--
ALTER TABLE `attachment_comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1900;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1182;

--
-- AUTO_INCREMENT for table `document_authors`
--
ALTER TABLE `document_authors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1220;

--
-- AUTO_INCREMENT for table `document_contents`
--
ALTER TABLE `document_contents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=725;

--
-- AUTO_INCREMENT for table `document_feedback`
--
ALTER TABLE `document_feedback`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `document_reads`
--
ALTER TABLE `document_reads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `informasi`
--
ALTER TABLE `informasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `job_checklist_items`
--
ALTER TABLE `job_checklist_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `job_executions`
--
ALTER TABLE `job_executions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `review_annotations`
--
ALTER TABLE `review_annotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=780;

--
-- AUTO_INCREMENT for table `user_off_days`
--
ALTER TABLE `user_off_days`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attachment_comments`
--
ALTER TABLE `attachment_comments`
  ADD CONSTRAINT `attachment_comments_attachment_id_foreign` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachment_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  ADD CONSTRAINT `documents_revises_document_id_foreign` FOREIGN KEY (`revises_document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_authors`
--
ALTER TABLE `document_authors`
  ADD CONSTRAINT `document_authors_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_authors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_contents`
--
ALTER TABLE `document_contents`
  ADD CONSTRAINT `document_contents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_feedback`
--
ALTER TABLE `document_feedback`
  ADD CONSTRAINT `document_feedback_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_feedback_replied_by_foreign` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `document_feedback_revision_document_id_foreign` FOREIGN KEY (`revision_document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_reads`
--
ALTER TABLE `document_reads`
  ADD CONSTRAINT `document_reads_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD CONSTRAINT `document_versions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `informasi`
--
ALTER TABLE `informasi`
  ADD CONSTRAINT `informasi_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `job_checklist_items`
--
ALTER TABLE `job_checklist_items`
  ADD CONSTRAINT `job_checklist_items_job_execution_id_foreign` FOREIGN KEY (`job_execution_id`) REFERENCES `job_executions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_executions`
--
ALTER TABLE `job_executions`
  ADD CONSTRAINT `job_executions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  ADD CONSTRAINT `job_executions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_annotations`
--
ALTER TABLE `review_annotations`
  ADD CONSTRAINT `review_annotations_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_off_days`
--
ALTER TABLE `user_off_days`
  ADD CONSTRAINT `user_off_days_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
