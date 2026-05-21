-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 12:59 PM
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
-- Database: `djln_wholesale_inventory`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_calculate_order_totals` (IN `p_order_id` INT)   BEGIN
                -- Calculate subtotal from order_items
                UPDATE orders o
                SET subtotal = COALESCE((
                    SELECT SUM(oi.line_total) 
                    FROM order_items oi 
                    WHERE oi.order_id = p_order_id
                ), 0)
                WHERE o.id = p_order_id;
                
                -- Recalculate total_amount = subtotal - discount_amount
                UPDATE orders
                SET total_amount = GREATEST(0, subtotal - COALESCE(discount_amount, 0))
                WHERE id = p_order_id;
            END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_deduct_inventory` (IN `p_product_id` INT, IN `p_quantity` INT, IN `p_order_id` INT)   BEGIN
                DECLARE v_current_stock INT DEFAULT 0;
                
                -- Get current stock with row lock
                SELECT stock_qty INTO v_current_stock
                FROM products
                WHERE id = p_product_id
                FOR UPDATE;
                
                -- Check if sufficient stock
                IF v_current_stock < p_quantity THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock';
                END IF;
                
                -- Deduct stock
                UPDATE products
                SET stock_qty = stock_qty - p_quantity,
                    stock_status = CASE
                        WHEN (stock_qty - p_quantity) = 0 THEN 'out_of_stock'
                        WHEN (stock_qty - p_quantity) <= reorder_level THEN 'low_stock'
                        ELSE 'in_stock'
                    END,
                    updated_at = NOW()
                WHERE id = p_product_id;
                
                -- Log audit record
                INSERT INTO audit_logs (model_type, model_id, user_id, action, changes, created_at, updated_at)
                SELECT 'Product', p_product_id, processed_by, 'stock_deduction',
                    JSON_OBJECT('before', JSON_OBJECT('qty', v_current_stock), 'after', JSON_OBJECT('qty', v_current_stock - p_quantity, 'order_id', p_order_id)),
                    NOW(), NOW()
                FROM orders WHERE id = p_order_id;
            END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `model_type`, `model_id`, `changes`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 18, 'stock_deduction', 'Product', 6, '{\"before\": {\"qty\": \"40\"}, \"after\": {\"qty\": 32, \"order_id\": \"4\"}}', NULL, NULL, '2026-05-17 17:53:07', '2026-05-17 17:53:07'),
(2, 18, 'stock_deduction', 'Product', 5, '{\"before\": {\"qty\": \"67\"}, \"after\": {\"qty\": 57, \"order_id\": \"4\"}}', NULL, NULL, '2026-05-17 17:53:07', '2026-05-17 17:53:07'),
(3, 18, 'fulfillment_status_change', 'Order', 4, '{\"before\": {\"status\": \"pending\"}, \"after\": {\"status\": \"shipped\"}}', NULL, NULL, '2026-05-17 17:53:38', '2026-05-17 17:53:38'),
(4, 18, 'fulfillment_status_change', 'Order', 4, '{\"before\": {\"status\": \"shipped\"}, \"after\": {\"status\": \"delivered\"}}', NULL, NULL, '2026-05-17 17:53:44', '2026-05-17 17:53:44'),
(5, 18, 'stock_deduction', 'Product', 11, '{\"before\": {\"qty\": \"180\"}, \"after\": {\"qty\": 160, \"order_id\": \"5\"}}', NULL, NULL, '2026-05-21 09:47:41', '2026-05-21 09:47:41'),
(6, 18, 'stock_deduction', 'Product', 13, '{\"before\": {\"qty\": \"350\"}, \"after\": {\"qty\": 300, \"order_id\": \"5\"}}', NULL, NULL, '2026-05-21 09:47:41', '2026-05-21 09:47:41'),
(7, 18, 'stock_deduction', 'Product', 8, '{\"before\": {\"qty\": \"150\"}, \"after\": {\"qty\": 125, \"order_id\": \"5\"}}', NULL, NULL, '2026-05-21 09:47:41', '2026-05-21 09:47:41');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `slug`, `icon`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Candies & Gummies', 'Assorted hard candies, gummy bears, sour worms, and chewy confections.', 'candies-gummies', '🍬', 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(2, 'Lollipops', 'Flat pops, round pops, and novelty lollipops in bulk packaging.', 'lollipops', '🍭', 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(3, 'Chocolates', 'Milk, dark, and white chocolate bars, coins, and coated treats.', 'chocolates', '🍫', 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(4, 'Latex & Foil Balloons', 'Latex standard balloons and premium metallic foil balloons.', 'balloons', '🎈', 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(5, 'Party Needs', 'Party hats, streamers, banners, candles, and celebration accessories.', 'party-needs', '🎉', 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52');

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
(4, '2025_01_01_projects', 1),
(5, '2025_01_01_tasks', 1),
(6, '2025_01_02_activities', 1),
(7, '2025_01_02_add_role_to_users', 1),
(8, '2025_01_03_comments', 1),
(9, '2025_01_12_000002_create_task_attachments_table', 1),
(10, '2026_04_13_add_performance_indexes', 1),
(11, '2026_04_20_add_client_role_v2', 1),
(12, '2026_04_22_add_capacity_planning_to_tasks', 1),
(13, '2026_04_22_add_theme_preference_to_users', 1),
(14, '2026_04_22_create_audit_logs_table', 1),
(15, '2026_04_22_create_notifications_table', 1),
(16, '2026_04_30_create_hybrid_integrity_triggers', 1),
(17, '2026_05_13_add_activity_types_to_task_activities', 1),
(18, '2026_05_13_fix_project_delete_trigger', 1),
(19, '2026_05_15_001_pivot_projects_to_categories', 1),
(20, '2026_05_15_002_pivot_tasks_to_products', 1),
(21, '2026_05_15_003_create_orders_tables', 1),
(22, '2026_05_17_000001_drop_legacy_task_tables', 1),
(23, '2026_05_17_000002_create_wholesale_procedures_triggers', 1),
(24, '2026_05_18_000001_add_wholesale_product_fields_and_order_trigger', 2),
(25, '2026_05_18_000002_patch_order_notification_trigger_with_customer_name', 3),
(26, '2026_05_18_000003_add_contact_number_to_users', 4);

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
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('1c00bf0a-54fa-11f1-ad66-2cf05dd037cd', 'App\\Notifications\\NewOrderAlert', 'App\\Models\\User', 1, '{\"order_id\": 5, \"order_number\": \"DJLN-2026-00005\", \"customer_id\": 18, \"customer_name\": \"Balloon Bash Co.\", \"total_amount\": 0.00, \"order_type\": \"retail\", \"message\": \"New Order #DJLN-2026-00005 received from Balloon Bash Co. for u20b10.00\", \"icon\": \"ud83duded2\", \"created_at\": \"2026-05-21T17:47:41Z\"}', NULL, '2026-05-21 09:47:41', '2026-05-21 09:47:41'),
('42aaa135-5219-11f1-b778-2cf05dd037cd', 'App\\Notifications\\NewOrderAlert', 'App\\Models\\User', 1, '{\"order_id\": 4, \"order_number\": \"DJLN-2026-00004\", \"customer_id\": 18, \"customer_name\": \"techcorp\", \"total_amount\": 0.00, \"order_type\": \"wholesale\", \"message\": \"New Order #DJLN-2026-00004 received from techcorp for u20b10.00\", \"icon\": \"ud83duded2\", \"created_at\": \"2026-05-18T01:53:07Z\"}', '2026-05-17 17:53:25', '2026-05-17 17:53:07', '2026-05-17 09:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `order_type` enum('wholesale','retail') NOT NULL DEFAULT 'retail',
  `fulfillment_status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `processed_by`, `order_type`, `fulfillment_status`, `payment_status`, `subtotal`, `discount_amount`, `total_amount`, `notes`, `ordered_at`, `created_at`, `updated_at`) VALUES
(4, 'DJLN-2026-00004', 18, 18, 'wholesale', 'delivered', 'paid', 1980.00, 0.00, 1980.00, NULL, '2026-05-17 09:53:07', '2026-05-17 09:53:07', '2026-05-17 09:53:44'),
(5, 'DJLN-2026-00005', 18, 18, 'retail', 'pending', 'unpaid', 3725.00, 0.00, 3725.00, NULL, '2026-05-21 01:47:41', '2026-05-21 01:47:41', '2026-05-21 01:47:41');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `tr_new_order_notification` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
                DECLARE v_customer_name VARCHAR(255) DEFAULT 'Unknown';

                -- Look up the customer name at insert time
                SELECT name INTO v_customer_name
                FROM users
                WHERE id = NEW.customer_id
                LIMIT 1;

                -- Insert one notification row per admin user
                INSERT INTO notifications (
                    id,
                    type,
                    notifiable_type,
                    notifiable_id,
                    data,
                    read_at,
                    created_at,
                    updated_at
                )
                SELECT
                    UUID(),
                    'App\Notifications\NewOrderAlert',
                    'App\Models\User',
                    u.id,
                    JSON_OBJECT(
                        'order_id',      NEW.id,
                        'order_number',  NEW.order_number,
                        'customer_id',   NEW.customer_id,
                        'customer_name', v_customer_name,
                        'total_amount',  NEW.total_amount,
                        'order_type',    NEW.order_type,
                        'message',       CONCAT(
                            'New Order #',
                            NEW.order_number,
                            ' received from ',
                            v_customer_name,
                            ' for u20b1',
                            FORMAT(NEW.total_amount, 2)
                        ),
                        'icon',          'ud83duded2',
                        'created_at',    DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
                    ),
                    NULL,
                    NOW(),
                    NOW()
                FROM users u
                WHERE LOWER(u.role) = 'admin';
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_order_status_change` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
                -- If payment status changes to 'paid' and order is still pending, move to processing
                IF OLD.payment_status != 'paid' AND NEW.payment_status = 'paid' THEN
                    IF NEW.fulfillment_status = 'pending' THEN
                        SET NEW.fulfillment_status = 'processing';
                        SET NEW.updated_at = NOW();
                    END IF;
                END IF;
                
                -- Log status change in audit_logs
                IF OLD.fulfillment_status != NEW.fulfillment_status THEN
                    INSERT INTO audit_logs (model_type, model_id, user_id, action, changes, created_at, updated_at)
                    VALUES ('Order', NEW.id, NEW.processed_by, 'fulfillment_status_change',
                        JSON_OBJECT('before', JSON_OBJECT('status', OLD.fulfillment_status), 'after', JSON_OBJECT('status', NEW.fulfillment_status)),
                        NOW(), NOW());
                END IF;
            END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `line_total`, `created_at`, `updated_at`) VALUES
(8, 4, 6, 8, 60.00, 480.00, '2026-05-17 09:53:07', '2026-05-17 09:53:07'),
(9, 4, 5, 10, 150.00, 1500.00, '2026-05-17 09:53:07', '2026-05-17 09:53:07'),
(10, 5, 11, 20, 35.00, 700.00, '2026-05-21 01:47:41', '2026-05-21 01:47:41'),
(11, 5, 13, 50, 18.00, 900.00, '2026-05-21 01:47:41', '2026-05-21 01:47:41'),
(12, 5, 8, 25, 85.00, 2125.00, '2026-05-21 01:47:41', '2026-05-21 01:47:41');

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `tr_order_items_insert` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
                DECLARE v_order_id INT;
                SELECT order_id INTO v_order_id FROM order_items WHERE id = NEW.id LIMIT 1;
                
                -- Only auto-deduct for 'pending' or 'processing' orders
                IF EXISTS (SELECT 1 FROM orders WHERE id = v_order_id AND fulfillment_status IN ('pending', 'processing')) THEN
                    CALL proc_deduct_inventory(NEW.product_id, NEW.quantity, v_order_id);
                END IF;
                
                -- Recalculate order totals
                CALL proc_calculate_order_totals(v_order_id);
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_order_items_update` AFTER UPDATE ON `order_items` FOR EACH ROW BEGIN
                DECLARE v_qty_diff INT;
                DECLARE v_order_id INT;
                
                SET v_qty_diff = NEW.quantity - OLD.quantity;
                SELECT order_id INTO v_order_id FROM order_items WHERE id = NEW.id LIMIT 1;
                
                -- If quantity increased, deduct more stock
                IF v_qty_diff > 0 THEN
                    CALL proc_deduct_inventory(NEW.product_id, v_qty_diff, v_order_id);
                -- If quantity decreased, refund stock
                ELSEIF v_qty_diff < 0 THEN
                    UPDATE products
                    SET stock_qty = stock_qty - v_qty_diff,
                        stock_status = CASE
                            WHEN stock_qty - v_qty_diff > reorder_level THEN 'in_stock'
                            WHEN stock_qty - v_qty_diff > 0 THEN 'low_stock'
                            ELSE 'out_of_stock'
                        END,
                        updated_at = NOW()
                    WHERE id = NEW.product_id;
                END IF;
                
                -- Recalculate order totals
                CALL proc_calculate_order_totals(v_order_id);
            END
$$
DELIMITER ;

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
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `brand_name` varchar(100) DEFAULT NULL COMMENT 'Manufacturer / brand (e.g. Sour Worms Co.)',
  `weight_volume` varchar(50) DEFAULT NULL COMMENT 'Shipping descriptor e.g. "5 kg", "100 pcs", "2 L"',
  `unit` varchar(255) NOT NULL DEFAULT 'piece',
  `wholesale_price` decimal(10,2) NOT NULL,
  `retail_price` decimal(10,2) NOT NULL,
  `stock_qty` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reorder_level` int(10) UNSIGNED NOT NULL DEFAULT 10,
  `moq` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Minimum Order Quantity — wholesale buying rule',
  `stock_status` enum('in_stock','low_stock','out_of_stock','discontinued') NOT NULL DEFAULT 'in_stock',
  `expiry_date` date DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `sku`, `description`, `brand_name`, `weight_volume`, `unit`, `wholesale_price`, `retail_price`, `stock_qty`, `reorder_level`, `moq`, `stock_status`, `expiry_date`, `image_url`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Gummy Bears — Bulk 5 kg', 'GUM-BEAR-5KG', '5 kg resealable bag of assorted fruit-flavored gummy bears.', NULL, NULL, 'bag', 280.00, 350.00, 120, 20, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(2, 1, 'Sour Worms — 1 kg Pack', 'GUM-SWORM-1KG', 'Tangy sour coated worm gummies, 1 kg pack.', NULL, NULL, 'pack', 95.00, 130.00, 65, 15, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 14:33:53'),
(3, 1, 'Jelly Beans — Assorted 500 g', 'CND-JBEAN-500G', 'Assorted fruit-flavored jelly beans, 500 g resealable bag.', NULL, NULL, 'pack', 55.00, 80.00, 200, 30, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(4, 1, 'Taffy Chews — Mixed Flavors 1 kg', 'CND-TAFFY-1KG', 'Soft taffy chews in assorted fruit flavors, bulk 1 kg.', NULL, NULL, 'pack', 70.00, 100.00, 60, 10, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(5, 2, 'Classic Round Lollipops — 100 ct', 'LLP-ROUND-100', 'Assorted fruit-flavored round lollipops, 100-count display box.', NULL, NULL, 'box', 150.00, 200.00, 57, 10, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 09:53:07'),
(6, 2, 'Flat Lollipop Sticks — 50 ct', 'LLP-FLAT-50', 'Strawberry, grape, and watermelon flat pops, 50-count pack.', NULL, NULL, 'pack', 60.00, 90.00, 32, 8, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 09:53:07'),
(7, 3, 'Milk Chocolate Coins — 500 g', 'CHC-COIN-500G', 'Foil-wrapped milk chocolate coins, 500 g bag.', NULL, NULL, 'bag', 180.00, 240.00, 50, 10, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(8, 4, 'Latex Balloons — 100 ct Assorted', 'BAL-LATEX-100', 'Standard 11-inch latex balloons, assorted colors, 100-count bag.', NULL, NULL, 'bag', 85.00, 120.00, 125, 25, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-21 01:47:41'),
(9, 4, 'Foil Star Balloon — 18 in (each)', 'BAL-FOIL-STAR18', '18-inch metallic foil star balloon, gold/silver/rose gold.', NULL, NULL, 'piece', 25.00, 45.00, 300, 40, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(10, 4, 'Foil Number Balloon Set — 0–9', 'BAL-FOIL-NUMSET', 'Complete set of 40-inch foil number balloons (0–9), gold.', NULL, NULL, 'set', 130.00, 199.00, 8, 10, 1, 'low_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(11, 5, 'Party Hats — Assorted 12 ct', 'PTY-HAT-12', 'Colorful cone party hats with elastic, 12-count pack.', NULL, NULL, 'pack', 35.00, 55.00, 160, 20, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-21 01:47:41'),
(12, 5, 'Metallic Streamers — 3-Roll Pack', 'PTY-STRM-3PK', 'Metallic crepe streamers in gold, silver, and rainbow, 3-roll pack.', NULL, NULL, 'pack', 28.00, 45.00, 220, 30, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(13, 5, 'Birthday Candle Set — 10 pcs', 'PTY-CNDL-10', 'Classic birthday candles, assorted rainbow colors, 10-piece set.', NULL, NULL, 'set', 18.00, 30.00, 300, 50, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-17 06:33:52', '2026-05-21 01:47:41'),
(14, 1, 'BERRY SOUR GUMMY WORMS - 20 pc', 'GUM-2026-001', 'berry berry sourry gummy wormy', NULL, NULL, 'pack', 95.00, 130.00, 50, 10, 1, 'in_stock', NULL, NULL, 1, 1, '2026-05-21 02:01:45', '2026-05-21 02:09:25');

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
('5ZG2TuYxjwc8vPTEArDt5flMcJwCUhP4FvFTHEkj', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibUE3akJBa0FqdE1UamJ5VGM5OU92TzQ5RWs4cklaOHNmUWtjUnNGZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hdWRpdC1sb2dzIjtzOjU6InJvdXRlIjtzOjEwOiJhdWRpdC1sb2dzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1779355987),
('bZEkoQmVyw0wDVaLq9xmunNSbVFOYAs2o0Umcohg', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicG1CZG90S1RZVVh3RG56aUNVUElkaDNBd3VIMXBwSjFXRUdDRTRDMCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wcm9kdWN0cyI7czo1OiJyb3V0ZSI7czoxNDoicHJvZHVjdHMuaW5kZXgiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1779359095);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL COMMENT 'PH mobile or landline — e.g. 09171234567',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','project_manager','team_member','client') NOT NULL,
  `theme_preference` varchar(255) NOT NULL DEFAULT 'light',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `contact_number`, `email_verified_at`, `password`, `role`, `theme_preference`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin', NULL, '2026-05-17 06:33:48', '$2y$12$TdE36n4h8uWgrR6n7yHPUuo4ivuyvWgWLCPw/Z/VENV8E/yX2Q7yi', 'admin', 'light', 'xd3gwTF9TWygqA79UpxJK7Z6cnlkDUoEJGEpZaUVxgKVZGczJZPM1xoPINgT', '2026-05-17 06:33:48', '2026-05-17 06:33:48'),
(2, 'Sarah', 'sarah', NULL, '2026-05-17 06:33:49', '$2y$12$nUj57RnBX1Qji427A.zmFuzTkljQ1YmlIQBhng/dBymj5RWftVsvW', 'project_manager', 'light', 'czMkDmqumYInfqMXGN9r4vYYuLGzSVaC6SZKOxZ780znOEnynjwRQKT6RcaD', '2026-05-17 06:33:49', '2026-05-17 06:33:49'),
(3, 'Michael', 'michael', NULL, '2026-05-17 06:33:49', '$2y$12$FScuQzHGDYkEWpRPWL//YOnnxHB2jPVB452oYqXhUcyJf30.a8mFW', 'project_manager', 'light', 'AXmQ3KA1m1', '2026-05-17 06:33:49', '2026-05-17 06:33:49'),
(4, 'Emma', 'emma', NULL, '2026-05-17 06:33:49', '$2y$12$qZA3BVQvjTgZTYHmaawvUufWqCcJ335eFtrwLL6DBppgbLDMZ3RP.', 'project_manager', 'light', 'Ve4Q5HmfOz', '2026-05-17 06:33:49', '2026-05-17 06:33:49'),
(5, 'Alex', 'alex', NULL, '2026-05-17 06:33:50', '$2y$12$.rvD892VOgSVXO8gdKz50eUKjitYFDzls91oIjEK6zzBJdvHnHUMS', 'team_member', 'light', 'Bo5tHbmcXm', '2026-05-17 06:33:50', '2026-05-17 06:33:50'),
(6, 'Jessica', 'jessica', NULL, '2026-05-17 06:33:50', '$2y$12$uA6xbn/tIsKnpbegnS2ADuqxcUrOI2g8eFN6iauM1M1oDD4kWaOTC', 'team_member', 'light', '6Up5FdwG10', '2026-05-17 06:33:50', '2026-05-17 06:33:50'),
(7, 'David', 'david', NULL, '2026-05-17 06:33:50', '$2y$12$gA3FX6M2fWz75TtVr.y5ueAGaejUfmhZs3EeRsXiH7Ifediq41PMu', 'team_member', 'light', 'N6HsVPlcpw', '2026-05-17 06:33:50', '2026-05-17 06:33:50'),
(8, 'Lisa', 'lisa', NULL, '2026-05-17 06:33:50', '$2y$12$tumpBjoxJU8HTRV263EHOOeZGnG9cdMeaOeB9iPuU/CJpTjerZXf2', 'team_member', 'light', 'be1skfBtaJ', '2026-05-17 06:33:50', '2026-05-17 06:33:50'),
(9, 'James', 'james', NULL, '2026-05-17 06:33:51', '$2y$12$/EyxzM2lUbkISEVwpmdKqeVNQLiIJY8AV4APU2TUH4akAg02XsTy6', 'team_member', 'light', 'aaCdHy53uN', '2026-05-17 06:33:51', '2026-05-17 06:33:51'),
(10, 'Nicole', 'nicole', NULL, '2026-05-17 06:33:51', '$2y$12$MlsB2aJbC4GNvrI4f9it.Os2UOc7rr7zTa.wVPUh.3jM7IiiHDEtW', 'team_member', 'light', '2oc2oVpYBT', '2026-05-17 06:33:51', '2026-05-17 06:33:51'),
(11, 'Robert', 'robert', NULL, '2026-05-17 06:33:51', '$2y$12$XhvKj6hHdcc6JXM5Fwatqe1ZsJ/07sp9blUUEPRS88jW.TVcFJbda', 'team_member', 'light', '0usO3oW5pP', '2026-05-17 06:33:51', '2026-05-17 06:33:51'),
(12, 'Angela', 'angela', NULL, '2026-05-17 06:33:51', '$2y$12$63e6Ly66L3L9eiQMuFE1jeWHO53ZLyvKy2ctk8hZ1qyvKW1/m1EN2', 'team_member', 'light', 'j28qtqNtPB', '2026-05-17 06:33:51', '2026-05-17 06:33:51'),
(13, 'Marcus', 'marcus', NULL, '2026-05-17 06:33:51', '$2y$12$6xPRZP9hUyf8iV67J9rHLeLuB5M4LzSY66U6v6wFuU2FyHIVA1gVO', 'team_member', 'light', 'fqcOqjvQwF', '2026-05-17 06:33:51', '2026-05-17 06:33:51'),
(14, 'Rachel', 'rachel', NULL, '2026-05-17 06:33:52', '$2y$12$i/UrjsVZHjGJ4yKvUcTXFOzWde2L0vzkcQHpaMAdyDNlx4Yywnqu2', 'team_member', 'light', 'qoDDSS5O0n', '2026-05-17 06:33:52', '2026-05-17 06:33:52'),
(17, 'Eduardo Plomos', 'eduardoplomos1990@gmail.com', '09910967443', NULL, '$2y$12$KwOYdAo8uacaIujIwyL0nOKEuUyHI1jlqmgqMX/mFYF8uwe5Rilwy', 'client', 'light', NULL, '2026-05-17 09:33:00', '2026-05-17 12:50:00'),
(18, 'Balloon Bash Co.', 'balloonbash', NULL, NULL, '$2y$12$wY5B9iLcNH54e90kj.MiWuf2wyPCGvrHmhF.GJrCooG/kPk301Yyq', 'client', 'light', NULL, '2026-05-17 09:52:05', '2026-05-17 12:27:31');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_ordersummary`
-- (See below for the actual view)
--
CREATE TABLE `vw_ordersummary` (
`order_id` bigint(20) unsigned
,`order_number` varchar(255)
,`customer_name` varchar(255)
,`order_type` enum('wholesale','retail')
,`fulfillment_status` enum('pending','processing','shipped','delivered','cancelled')
,`payment_status` enum('unpaid','partial','paid')
,`total_amount` decimal(12,2)
,`ordered_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_productstockstatus`
-- (See below for the actual view)
--
CREATE TABLE `vw_productstockstatus` (
`product_id` bigint(20) unsigned
,`sku` varchar(255)
,`product_name` varchar(255)
,`category_name` varchar(255)
,`stock_qty` int(10) unsigned
,`reorder_level` int(10) unsigned
,`stock_health` varchar(12)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_ordersummary`
--
DROP TABLE IF EXISTS `vw_ordersummary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ordersummary`  AS SELECT `o`.`id` AS `order_id`, `o`.`order_number` AS `order_number`, `u`.`name` AS `customer_name`, `o`.`order_type` AS `order_type`, `o`.`fulfillment_status` AS `fulfillment_status`, `o`.`payment_status` AS `payment_status`, `o`.`total_amount` AS `total_amount`, `o`.`ordered_at` AS `ordered_at` FROM (`orders` `o` left join `users` `u` on(`o`.`customer_id` = `u`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_productstockstatus`
--
DROP TABLE IF EXISTS `vw_productstockstatus`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productstockstatus`  AS SELECT `p`.`id` AS `product_id`, `p`.`sku` AS `sku`, `p`.`name` AS `product_name`, `c`.`name` AS `category_name`, `p`.`stock_qty` AS `stock_qty`, `p`.`reorder_level` AS `reorder_level`, CASE WHEN `p`.`stock_qty` = 0 THEN 'Out of Stock' WHEN `p`.`stock_qty` <= `p`.`reorder_level` THEN 'Low Stock' ELSE 'Healthy' END AS `stock_health` FROM (`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `audit_logs_action_created_at_index` (`action`,`created_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_created_by_foreign` (`created_by`),
  ADD KEY `categories_slug_index` (`slug`),
  ADD KEY `categories_is_active_index` (`is_active`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

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
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_processed_by_foreign` (`processed_by`),
  ADD KEY `orders_order_number_index` (`order_number`),
  ADD KEY `orders_customer_id_index` (`customer_id`),
  ADD KEY `orders_fulfillment_status_index` (`fulfillment_status`),
  ADD KEY `orders_order_type_index` (`order_type`),
  ADD KEY `orders_payment_status_index` (`payment_status`),
  ADD KEY `orders_customer_id_fulfillment_status_index` (`customer_id`,`fulfillment_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_index` (`order_id`),
  ADD KEY `order_items_product_id_index` (`product_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_sku_unique` (`sku`),
  ADD KEY `products_created_by_foreign` (`created_by`),
  ADD KEY `products_category_id_index` (`category_id`),
  ADD KEY `products_sku_index` (`sku`),
  ADD KEY `products_stock_status_index` (`stock_status`),
  ADD KEY `products_is_active_index` (`is_active`),
  ADD KEY `products_category_id_stock_status_index` (`category_id`,`stock_status`),
  ADD KEY `products_stock_qty_reorder_level_index` (`stock_qty`,`reorder_level`);

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
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_theme_preference_index` (`theme_preference`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
