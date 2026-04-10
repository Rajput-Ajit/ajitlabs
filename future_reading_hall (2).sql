-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 11:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `future_reading_hall`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(500) DEFAULT NULL,
  `status` enum('active','blocked','suspended','pending_verification') NOT NULL DEFAULT 'pending_verification',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `contact_verified` tinyint(1) NOT NULL DEFAULT 0,
  `plan_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'current active plan',
  `plan_expires_at` datetime DEFAULT NULL,
  `is_read_only` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 after plan expiry',
  `created_by_super` int(10) UNSIGNED DEFAULT NULL,
  `meta` longtext DEFAULT NULL COMMENT 'JSON: timezone, locale, etc.',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Library owners / admins';

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `uuid`, `first_name`, `last_name`, `email`, `contact`, `password_hash`, `profile_photo`, `status`, `email_verified`, `contact_verified`, `plan_id`, `plan_expires_at`, `is_read_only`, `created_by_super`, `meta`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'a006c917-f0f8-4d68-8563-f739fdf29187', 'Ajit', 'Rajput', 'chhanwalajit234@gmail.com', '8317217487', '$2y$12$pncRjAf4gBwETfLBpRn8p./waFWm2eYFlRl88J7gB3pQRAYyND1Me', NULL, 'active', 1, 0, 1, '2126-04-08 00:00:00', 0, NULL, NULL, '2026-04-08 16:51:48', '2026-04-10 01:10:17', NULL),
(5, '5a312b4a-2126-467e-ab45-d509cb461bcf', 'Ajit', 'Rajput', 'chhanwalajit234og@gmail.com', '8317217474', '$2y$12$/WeHlS6JKKh4KAlTOkTbr.ZZX1zVELoP.Awn7CanTOc2UfL/gDh.K', NULL, 'active', 1, 0, 1, '2126-04-10 00:00:00', 0, NULL, NULL, '2026-04-10 00:37:55', '2026-04-10 01:10:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_subscriptions`
--

CREATE TABLE `admin_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','upi','bank_transfer','manual') NOT NULL DEFAULT 'manual',
  `payment_ref` varchar(100) DEFAULT NULL,
  `payment_note` varchar(500) DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'super_admin id who approved',
  `status` enum('pending','active','expired','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin SaaS subscription history (manual payment approval)';

--
-- Dumping data for table `admin_subscriptions`
--

INSERT INTO `admin_subscriptions` (`id`, `admin_id`, `plan_id`, `start_date`, `end_date`, `amount_paid`, `payment_method`, `payment_ref`, `payment_note`, `approved_by`, `status`, `created_at`, `updated_at`) VALUES
(4, 4, 1, '2026-04-08', '2126-04-08', 0.00, 'manual', NULL, NULL, NULL, 'active', '2026-04-08 16:51:49', '2026-04-08 16:51:49'),
(5, 5, 1, '2026-04-10', '2126-04-10', 0.00, 'manual', NULL, NULL, NULL, 'active', '2026-04-10 00:37:55', '2026-04-10 00:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'tenant context',
  `actor_type` enum('super_admin','admin','sub_admin','student','system') NOT NULL DEFAULT 'system',
  `actor_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL COMMENT 'e.g. seat.assign, fee.collect',
  `entity_type` varchar(80) DEFAULT NULL COMMENT 'table name',
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` longtext DEFAULT NULL COMMENT 'JSON snapshot before',
  `new_value` longtext DEFAULT NULL COMMENT 'JSON snapshot after',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Immutable audit trail for all write operations';

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL COMMENT 'URL-safe unique per admin',
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` char(2) NOT NULL DEFAULT 'IN',
  `address` text DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `meta` longtext DEFAULT NULL COMMENT 'JSON: logo_url, timings, etc.',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `admin_id`, `name`, `slug`, `city`, `state`, `country`, `address`, `pincode`, `phone`, `email`, `latitude`, `longitude`, `status`, `meta`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 4, 'Ajit Abhyasika', 'ajit-abhyasika', 'Chhatrapati Sambhajinagar', NULL, 'IN', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-04-08 16:51:49', '2026-04-08 16:51:49', NULL),
(5, 5, 'Web Reading Hall', 'web-reading-hall', 'Chhatrapati Sambhajinagar', NULL, 'IN', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-04-10 00:37:55', '2026-04-10 00:37:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `allocation_id` int(10) UNSIGNED NOT NULL COMMENT 'links to seat_allocations',
  `seat_id` int(10) UNSIGNED NOT NULL,
  `shift_id` int(10) UNSIGNED NOT NULL,
  `hall_id` int(10) UNSIGNED NOT NULL,
  `branch_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL COMMENT 'amount - discount',
  `payment_method` enum('cash','upi','card','bank_transfer','cheque','online') NOT NULL DEFAULT 'cash',
  `payment_ref` varchar(100) DEFAULT NULL COMMENT 'transaction id / receipt no',
  `duration_months` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('paid','pending','partial','failed','refunded') NOT NULL DEFAULT 'pending',
  `collected_by_type` enum('admin','sub_admin') NOT NULL DEFAULT 'admin',
  `collected_by_id` int(10) UNSIGNED NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL COMMENT 'human-readable receipt no',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fee/payment records linked to seat allocations';

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `allocation_id`, `seat_id`, `shift_id`, `hall_id`, `branch_id`, `amount`, `discount`, `final_amount`, `payment_method`, `payment_ref`, `duration_months`, `start_date`, `expiry_date`, `status`, `collected_by_type`, `collected_by_id`, `receipt_number`, `notes`, `created_at`, `updated_at`) VALUES
(6, 4, 5, 302, 12, 7, 4, 800.00, 0.00, 800.00, 'cash', NULL, 1, '2026-04-04', '2026-05-04', 'paid', 'admin', 4, 'ADM4-2026-00001', NULL, '2026-04-08 19:41:28', '2026-04-08 19:41:28'),
(7, 4, 6, 303, 12, 7, 4, 800.00, 0.00, 800.00, 'cash', NULL, 1, '2026-04-04', '2026-05-04', 'paid', 'admin', 4, 'ADM4-2026-00002', NULL, '2026-04-08 19:41:46', '2026-04-08 19:41:46');

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE `fee_reminders` (
  `id` int(10) UNSIGNED NOT NULL,
  `fee_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `reminder_type` enum('sms','email','whatsapp','push') NOT NULL DEFAULT 'sms',
  `sent_at` datetime DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `halls`
--

CREATE TABLE `halls` (
  `id` int(10) UNSIGNED NOT NULL,
  `branch_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('ac','non_ac','semi_ac') DEFAULT 'non_ac',
  `capacity` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'max seats',
  `seat_start_number` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'seat numbering offset',
  `seat_prefix` varchar(20) DEFAULT NULL COMMENT 'custom prefix e.g. A, S, NULL=numeric',
  `features` text DEFAULT NULL COMMENT 'JSON array: wifi, locker, etc.',
  `qr_code` varchar(500) DEFAULT NULL COMMENT 'URL or base64 QR',
  `status` enum('open','closed','maintenance') NOT NULL DEFAULT 'open',
  `meta` longtext DEFAULT NULL COMMENT 'JSON: rules, images, floor_plan',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `halls`
--

INSERT INTO `halls` (`id`, `branch_id`, `name`, `type`, `capacity`, `seat_start_number`, `seat_prefix`, `features`, `qr_code`, `status`, `meta`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 4, 'Ajit Rajput First Hall', NULL, 21, 1, 'S', NULL, NULL, 'open', NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL COMMENT 'which tenant',
  `actor_type` enum('admin','sub_admin','student','system') NOT NULL DEFAULT 'system',
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `target_type` enum('admin','sub_admin','student','branch','hall') NOT NULL,
  `target_id` int(10) UNSIGNED NOT NULL,
  `channel` enum('in_app','sms','email','push','whatsapp') NOT NULL DEFAULT 'in_app',
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `status` enum('queued','sent','delivered','failed','read') NOT NULL DEFAULT 'queued',
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `actor_type` enum('admin','sub_admin','student') NOT NULL DEFAULT 'student',
  `actor_id` int(10) UNSIGNED NOT NULL,
  `purpose` enum('login','email_verify','contact_verify','password_reset') NOT NULL DEFAULT 'login',
  `channel` enum('email','sms','whatsapp') NOT NULL DEFAULT 'sms',
  `target` varchar(191) NOT NULL,
  `otp_hash` varchar(255) NOT NULL COMMENT 'bcrypt/sha256 hash',
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`id`, `actor_type`, `actor_id`, `purpose`, `channel`, `target`, `otp_hash`, `expires_at`, `is_used`, `is_verified`, `verified_at`, `used_at`, `created_at`) VALUES
(17, 'admin', 0, 'email_verify', 'email', 'chhanwalajit234@gmail.com', '91b4d142823f7d20c5f08df69122de43f35f057a988d9619f6d3138485c9a203', '2026-04-10 00:41:39', 1, 1, '2026-04-10 00:36:53', NULL, '2026-04-10 00:36:46'),
(18, 'admin', 0, 'contact_verify', 'sms', '8317217474', '91b4d142823f7d20c5f08df69122de43f35f057a988d9619f6d3138485c9a203', '2026-04-10 00:42:14', 1, 1, '2026-04-10 00:37:30', NULL, '2026-04-10 00:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(80) NOT NULL,
  `label` varchar(150) NOT NULL,
  `group_name` varchar(80) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `label`, `group_name`, `description`) VALUES
(1, 'branch.view', 'View Branches', 'branch', NULL),
(2, 'branch.manage', 'Manage Branches', 'branch', NULL),
(3, 'hall.view', 'View Halls', 'hall', NULL),
(4, 'hall.manage', 'Manage Halls', 'hall', NULL),
(5, 'seat.view', 'View Seats', 'seat', NULL),
(6, 'seat.manage', 'Manage Seats', 'seat', NULL),
(7, 'seat.assign', 'Assign Seats', 'seat', NULL),
(8, 'student.view', 'View Students', 'student', NULL),
(9, 'student.manage', 'Manage Students', 'student', NULL),
(10, 'fee.view', 'View Fees', 'fee', NULL),
(11, 'fee.manage', 'Collect/Record Fees', 'fee', NULL),
(12, 'report.view', 'View Reports', 'report', NULL),
(13, 'notification.send', 'Send Notifications', 'notification', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `qr_scans`
--

CREATE TABLE `qr_scans` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `hall_id` int(10) UNSIGNED NOT NULL,
  `branch_id` int(10) UNSIGNED NOT NULL,
  `scan_type` enum('entry','exit','verify') NOT NULL DEFAULT 'verify',
  `scanned_by_type` enum('admin','sub_admin','kiosk','self') NOT NULL DEFAULT 'self',
  `scanned_by_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `scanned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='QR scan attendance log (future attendance feature)';

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(10) UNSIGNED NOT NULL,
  `key_name` varchar(191) NOT NULL COMMENT 'IP or user identifier',
  `endpoint` varchar(255) NOT NULL,
  `request_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `window_start` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blocked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `key_name`, `endpoint`, `request_count`, `window_start`, `created_at`, `updated_at`, `blocked_until`) VALUES
(261, '::1', '/ajitlabs/app/api/admin.login.php', 2, '2026-04-10 15:18:24', '2026-04-10 14:03:00', '2026-04-10 15:18:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `id` int(10) UNSIGNED NOT NULL,
  `hall_id` int(10) UNSIGNED NOT NULL,
  `seat_number` varchar(30) NOT NULL COMMENT 'display label e.g. A-1, 101, Window Seat',
  `seat_serial` int(10) UNSIGNED NOT NULL COMMENT 'numeric sort order within hall',
  `custom_name` varchar(100) DEFAULT NULL COMMENT 'optional custom label (future)',
  `status` enum('available','maintenance','removed') NOT NULL DEFAULT 'available',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta` longtext DEFAULT NULL COMMENT 'JSON: position, notes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Physical seats. Status derived via seat_allocations at query time.';

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`id`, `hall_id`, `seat_number`, `seat_serial`, `custom_name`, `status`, `is_active`, `meta`, `created_at`, `updated_at`, `deleted_at`) VALUES
(302, 7, 'S-1', 1, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(303, 7, 'S-2', 2, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(304, 7, 'S-3', 3, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(305, 7, 'S-4', 4, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(306, 7, 'S-5', 5, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(307, 7, 'S-6', 6, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(308, 7, 'S-7', 7, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(309, 7, 'S-8', 8, NULL, 'available', 1, NULL, '2026-04-08 18:56:36', '2026-04-08 18:56:36', NULL),
(310, 7, 'S-9', 9, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(311, 7, 'S-10', 10, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(312, 7, 'S-11', 11, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(313, 7, 'S-12', 12, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(314, 7, 'S-13', 13, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(315, 7, 'S-14', 14, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(316, 7, 'S-15', 15, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(317, 7, 'S-16', 16, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(318, 7, 'S-17', 17, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(319, 7, 'S-18', 18, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(320, 7, 'S-19', 19, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(321, 7, 'S-20', 20, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL),
(322, 7, 'S-21', 21, NULL, 'available', 1, NULL, '2026-04-08 18:56:37', '2026-04-08 18:56:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seat_allocations`
--

CREATE TABLE `seat_allocations` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `seat_id` int(10) UNSIGNED NOT NULL,
  `shift_id` int(10) UNSIGNED NOT NULL,
  `hall_id` int(10) UNSIGNED NOT NULL,
  `branch_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled','transferred') NOT NULL DEFAULT 'active',
  `booked_by_type` enum('admin','sub_admin','student','api') NOT NULL DEFAULT 'admin',
  `booked_by_id` int(10) UNSIGNED NOT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by_id` int(10) UNSIGNED DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Core booking table. status=active means seat is occupied for that shift.';

--
-- Dumping data for table `seat_allocations`
--

INSERT INTO `seat_allocations` (`id`, `student_id`, `seat_id`, `shift_id`, `hall_id`, `branch_id`, `start_date`, `end_date`, `status`, `booked_by_type`, `booked_by_id`, `cancelled_at`, `cancelled_by_id`, `cancel_reason`, `meta`, `created_at`, `updated_at`) VALUES
(5, 4, 302, 12, 7, 4, '0000-00-00', '2026-05-04', 'active', 'admin', 4, NULL, NULL, NULL, NULL, '2026-04-08 19:41:28', '2026-04-08 19:41:28'),
(6, 4, 303, 12, 7, 4, '0000-00-00', '2026-05-04', 'active', 'admin', 4, NULL, NULL, NULL, NULL, '2026-04-08 19:41:46', '2026-04-08 19:41:46');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(10) UNSIGNED NOT NULL,
  `hall_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL COMMENT 'morning | evening | fullday | night',
  `code` varchar(20) NOT NULL COMMENT 'slug: morning, evening, fullday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quarterly_fee` decimal(10,2) DEFAULT NULL,
  `half_yearly_fee` decimal(10,2) DEFAULT NULL,
  `yearly_fee` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Flexible shift/slot definitions per hall';

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `hall_id`, `name`, `code`, `start_time`, `end_time`, `monthly_fee`, `quarterly_fee`, `half_yearly_fee`, `yearly_fee`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(10, 7, 'Morning', 'morning', '07:00:00', '15:00:00', 500.00, NULL, NULL, NULL, 1, 1, '2026-04-08 18:56:36', '2026-04-08 18:56:36'),
(11, 7, 'Evening', 'evening', '15:00:00', '22:00:00', 500.00, NULL, NULL, NULL, 1, 2, '2026-04-08 18:56:36', '2026-04-08 18:56:36'),
(12, 7, 'Full Day', 'fullday', '07:00:00', '22:00:00', 800.00, NULL, NULL, NULL, 1, 3, '2026-04-08 18:56:36', '2026-04-08 18:56:36');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `admin_id` int(10) UNSIGNED NOT NULL COMMENT 'which library they registered at',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `contact` varchar(20) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'NULL = QR-only login',
  `profile_photo` varchar(500) DEFAULT NULL,
  `id_proof_type` varchar(50) DEFAULT NULL COMMENT 'aadhar, pan, student_id',
  `id_proof_number` varchar(100) DEFAULT NULL,
  `id_proof_doc` varchar(500) DEFAULT NULL COMMENT 'file path/URL',
  `qr_token` varchar(100) DEFAULT NULL COMMENT 'QR scan identifier',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `contact_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','blocked','inactive') NOT NULL DEFAULT 'active',
  `registered_via` enum('form','qr','app','api') NOT NULL DEFAULT 'form',
  `branch_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'primary branch of registration',
  `meta` longtext DEFAULT NULL COMMENT 'JSON: address, emergency contact',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='End users (library members / students)';

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `uuid`, `admin_id`, `first_name`, `last_name`, `email`, `contact`, `password_hash`, `profile_photo`, `id_proof_type`, `id_proof_number`, `id_proof_doc`, `qr_token`, `email_verified`, `contact_verified`, `status`, `registered_via`, `branch_id`, `meta`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'd5adbb26-3354-11f1-90ac-08bfb8db5b5c', 4, 'Ajit', 'Rajput', NULL, '8317217487', '$2y$10$X8Ewr0Z1pb3aSTjvvjJhVO4kUA./NiOmqZwdOXrmrWFhwpTosvAcC', NULL, NULL, NULL, NULL, '6841cb719837ddeeeb3be8a8dd714291', 0, 0, 'active', 'form', 4, NULL, '2026-04-08 19:41:28', '2026-04-08 19:41:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL COMMENT 'free | starter | pro | enterprise',
  `price_monthly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_yearly` decimal(10,2) DEFAULT NULL COMMENT 'optional yearly billing',
  `max_branches` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `max_halls` smallint(5) UNSIGNED NOT NULL DEFAULT 1 COMMENT '0 = unlimited',
  `max_students` mediumint(8) UNSIGNED NOT NULL DEFAULT 50 COMMENT '0 = unlimited',
  `max_sub_admins` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `has_analytics` tinyint(1) NOT NULL DEFAULT 0,
  `has_sms_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `has_fee_management` tinyint(1) NOT NULL DEFAULT 0,
  `has_shift_timings` tinyint(1) NOT NULL DEFAULT 0,
  `has_api_access` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `meta` longtext DEFAULT NULL COMMENT 'JSON: extra feature flags',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SaaS subscription plans (managed by super admin)';

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `slug`, `price_monthly`, `price_yearly`, `max_branches`, `max_halls`, `max_students`, `max_sub_admins`, `has_analytics`, `has_sms_alerts`, `has_fee_management`, `has_shift_timings`, `has_api_access`, `is_active`, `display_order`, `meta`, `created_at`, `updated_at`) VALUES
(1, 'Free', 'free', 0.00, NULL, 1, 1, 50, 0, 0, 0, 0, 0, 0, 1, 1, NULL, '2026-04-06 18:39:55', '2026-04-06 18:39:55'),
(2, 'Starter', 'starter', 799.00, NULL, 2, 5, 200, 1, 0, 0, 1, 1, 0, 1, 2, NULL, '2026-04-06 18:39:55', '2026-04-06 18:39:55'),
(3, 'Pro', 'pro', 2499.00, NULL, 5, 0, 500, 5, 1, 1, 1, 1, 0, 1, 3, NULL, '2026-04-06 18:39:55', '2026-04-06 18:39:55'),
(4, 'Enterprise', 'enterprise', 0.00, NULL, 0, 0, 0, 20, 1, 1, 1, 1, 1, 1, 4, NULL, '2026-04-06 18:39:55', '2026-04-06 18:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `sub_admins`
--

CREATE TABLE `sub_admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL COMMENT 'parent admin (owner)',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sub-admins created by admin (plan limit enforced in app)';

-- --------------------------------------------------------

--
-- Table structure for table `sub_admin_branch_scope`
--

CREATE TABLE `sub_admin_branch_scope` (
  `sub_admin_id` int(10) UNSIGNED NOT NULL,
  `branch_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_admin_hall_scope`
--

CREATE TABLE `sub_admin_hall_scope` (
  `sub_admin_id` int(10) UNSIGNED NOT NULL,
  `hall_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_admin_permissions`
--

CREATE TABLE `sub_admin_permissions` (
  `sub_admin_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `granted_by` int(10) UNSIGNED NOT NULL COMMENT 'admin_id who granted',
  `granted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','blocked','suspended') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = first super admin',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Platform owners and their delegates';

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_permissions`
--

CREATE TABLE `super_admin_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_permission_map`
--

CREATE TABLE `super_admin_permission_map` (
  `super_admin_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `granted_by` int(10) UNSIGNED NOT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admins_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_admins_email` (`email`),
  ADD KEY `idx_admins_plan` (`plan_id`),
  ADD KEY `idx_admins_status` (`status`);

--
-- Indexes for table `admin_subscriptions`
--
ALTER TABLE `admin_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asub_admin` (`admin_id`),
  ADD KEY `idx_asub_plan` (`plan_id`),
  ADD KEY `idx_asub_status` (`status`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_actor` (`actor_type`,`actor_id`),
  ADD KEY `idx_al_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_al_admin` (`admin_id`),
  ADD KEY `idx_al_action` (`action`),
  ADD KEY `idx_al_created` (`created_at`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_branches_admin_slug` (`admin_id`,`slug`),
  ADD KEY `idx_branches_admin` (`admin_id`),
  ADD KEY `idx_branches_status` (`status`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fees_student` (`student_id`),
  ADD KEY `idx_fees_allocation` (`allocation_id`),
  ADD KEY `idx_fees_hall` (`hall_id`),
  ADD KEY `idx_fees_branch` (`branch_id`),
  ADD KEY `idx_fees_status` (`status`),
  ADD KEY `idx_fees_dates` (`start_date`,`expiry_date`),
  ADD KEY `fk_fees_seat` (`seat_id`),
  ADD KEY `fk_fees_shift` (`shift_id`);

--
-- Indexes for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fr_fee` (`fee_id`),
  ADD KEY `idx_fr_student` (`student_id`);

--
-- Indexes for table `halls`
--
ALTER TABLE `halls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_halls_branch` (`branch_id`),
  ADD KEY `idx_halls_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_target` (`target_type`,`target_id`),
  ADD KEY `idx_notif_admin` (`admin_id`),
  ADD KEY `idx_notif_status` (`status`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otps_actor` (`actor_type`,`actor_id`),
  ADD KEY `idx_otps_expires` (`expires_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_code` (`code`);

--
-- Indexes for table `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qs_student` (`student_id`),
  ADD KEY `idx_qs_hall` (`hall_id`),
  ADD KEY `idx_qs_date` (`scanned_at`),
  ADD KEY `fk_qs_branch` (`branch_id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rl_key_endpoint` (`key_name`,`endpoint`),
  ADD KEY `idx_rl_window` (`window_start`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seats_hall_number` (`hall_id`,`seat_number`),
  ADD KEY `idx_seats_hall` (`hall_id`),
  ADD KEY `idx_seats_status` (`status`);

--
-- Indexes for table `seat_allocations`
--
ALTER TABLE `seat_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_seat_shift_start` (`seat_id`,`shift_id`,`start_date`),
  ADD KEY `idx_sa_student` (`student_id`),
  ADD KEY `idx_sa_seat` (`seat_id`),
  ADD KEY `idx_sa_shift` (`shift_id`),
  ADD KEY `idx_sa_hall` (`hall_id`),
  ADD KEY `idx_sa_branch` (`branch_id`),
  ADD KEY `idx_sa_status` (`status`),
  ADD KEY `idx_sa_dates` (`start_date`,`end_date`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shifts_hall_code` (`hall_id`,`code`),
  ADD KEY `idx_shifts_hall` (`hall_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_uuid` (`uuid`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `idx_students_admin` (`admin_id`),
  ADD KEY `idx_students_contact` (`contact`),
  ADD KEY `idx_students_email` (`email`),
  ADD KEY `idx_students_status` (`status`),
  ADD KEY `idx_students_branch` (`branch_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plans_slug` (`slug`);

--
-- Indexes for table `sub_admins`
--
ALTER TABLE `sub_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sub_admins_email` (`email`),
  ADD KEY `idx_sub_admins_admin` (`admin_id`);

--
-- Indexes for table `sub_admin_branch_scope`
--
ALTER TABLE `sub_admin_branch_scope`
  ADD PRIMARY KEY (`sub_admin_id`,`branch_id`),
  ADD KEY `fk_sabs_branch` (`branch_id`);

--
-- Indexes for table `sub_admin_hall_scope`
--
ALTER TABLE `sub_admin_hall_scope`
  ADD PRIMARY KEY (`sub_admin_id`,`hall_id`),
  ADD KEY `fk_sahs_hall` (`hall_id`);

--
-- Indexes for table `sub_admin_permissions`
--
ALTER TABLE `sub_admin_permissions`
  ADD PRIMARY KEY (`sub_admin_id`,`permission_id`),
  ADD KEY `idx_sap_permission` (`permission_id`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_super_admins_email` (`email`),
  ADD KEY `idx_super_admins_status` (`status`);

--
-- Indexes for table `super_admin_permissions`
--
ALTER TABLE `super_admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sap_code` (`code`);

--
-- Indexes for table `super_admin_permission_map`
--
ALTER TABLE `super_admin_permission_map`
  ADD PRIMARY KEY (`super_admin_id`,`permission_id`),
  ADD KEY `idx_sapm_permission` (`permission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_subscriptions`
--
ALTER TABLE `admin_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `halls`
--
ALTER TABLE `halls`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `qr_scans`
--
ALTER TABLE `qr_scans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=516;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- AUTO_INCREMENT for table `seat_allocations`
--
ALTER TABLE `seat_allocations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sub_admins`
--
ALTER TABLE `sub_admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `super_admin_permissions`
--
ALTER TABLE `super_admin_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admins_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `admin_subscriptions`
--
ALTER TABLE `admin_subscriptions`
  ADD CONSTRAINT `fk_asub_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asub_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `fk_branches_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fk_fees_allocation` FOREIGN KEY (`allocation_id`) REFERENCES `seat_allocations` (`id`),
  ADD CONSTRAINT `fk_fees_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_fees_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`),
  ADD CONSTRAINT `fk_fees_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`),
  ADD CONSTRAINT `fk_fees_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  ADD CONSTRAINT `fk_fees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD CONSTRAINT `fk_fr_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `halls`
--
ALTER TABLE `halls`
  ADD CONSTRAINT `fk_halls_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD CONSTRAINT `fk_qs_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qs_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `fk_seats_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seat_allocations`
--
ALTER TABLE `seat_allocations`
  ADD CONSTRAINT `fk_sa_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_sa_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`),
  ADD CONSTRAINT `fk_sa_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`),
  ADD CONSTRAINT `fk_sa_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  ADD CONSTRAINT `fk_sa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `fk_shifts_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_students_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sub_admins`
--
ALTER TABLE `sub_admins`
  ADD CONSTRAINT `fk_subadmins_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_admin_branch_scope`
--
ALTER TABLE `sub_admin_branch_scope`
  ADD CONSTRAINT `fk_sabs_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sabs_sub_admin` FOREIGN KEY (`sub_admin_id`) REFERENCES `sub_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_admin_hall_scope`
--
ALTER TABLE `sub_admin_hall_scope`
  ADD CONSTRAINT `fk_sahs_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sahs_sub_admin` FOREIGN KEY (`sub_admin_id`) REFERENCES `sub_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_admin_permissions`
--
ALTER TABLE `sub_admin_permissions`
  ADD CONSTRAINT `fk_sap_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sap_sub_admin` FOREIGN KEY (`sub_admin_id`) REFERENCES `sub_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `super_admin_permission_map`
--
ALTER TABLE `super_admin_permission_map`
  ADD CONSTRAINT `fk_sapm_permission` FOREIGN KEY (`permission_id`) REFERENCES `super_admin_permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sapm_super_admin` FOREIGN KEY (`super_admin_id`) REFERENCES `super_admins` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
