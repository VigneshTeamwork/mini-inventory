-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 10:04 AM
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
-- Database: `store_billing`
--

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
('store-billing-cache-d189a3df25facd3522214377f5876bcb', 'i:3;', 1793177429),
('store-billing-cache-d189a3df25facd3522214377f5876bcb:timer', 'i:1793177429;', 1793177429);

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
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Thomas', 'thomas@example.com', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(2, 'Priya Sharma', 'priya@example.com', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(3, 'Arjun Mehta', 'arjun@example.com', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(4, 'Fatima Khan', 'fatima@example.com', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(5, 'Ines Stehr', 'gabshire@example.net', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(6, 'Noe Schmeler', 'oberbrunner.laila@example.com', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(7, 'Phyllis Bins', 'gleichner.kelli@example.com', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(8, 'Colton Lebsack', 'hyatt.devante@example.net', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(9, 'Verner Kiehn', 'mariela.rodriguez@example.org', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(10, 'Deonte Schaefer IV', 'beaulah.ruecker@example.net', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(11, 'Vignesh', 'vigneshteamwork@gmail.com', '2026-10-28 08:49:30', '2026-10-28 08:49:30');

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

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'default', '{\"uuid\":\"35aac794-bd7e-4399-9a46-1d14d7e792a7\",\"displayName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,30,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"command\":\"O:30:\\\"App\\\\Jobs\\\\SendOrderConfirmation\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1793176762,\"delay\":null}', 0, NULL, 1793176762, 1793176762),
(2, 'default', '{\"uuid\":\"053a5c01-c62d-41d0-bf1a-12056991d541\",\"displayName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,30,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"command\":\"O:30:\\\"App\\\\Jobs\\\\SendOrderConfirmation\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1793176762,\"delay\":null}', 0, NULL, 1793176762, 1793176762),
(3, 'default', '{\"uuid\":\"ee0d48ff-b871-4d6f-a266-442c47943ba8\",\"displayName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,30,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"command\":\"O:30:\\\"App\\\\Jobs\\\\SendOrderConfirmation\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1793176762,\"delay\":null}', 0, NULL, 1793176762, 1793176762),
(4, 'default', '{\"uuid\":\"36f6ef6f-395b-4879-be87-74ed110f7d65\",\"displayName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,30,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderConfirmation\",\"command\":\"O:30:\\\"App\\\\Jobs\\\\SendOrderConfirmation\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:11:\\\"afterCommit\\\";b:1;}\",\"batchId\":null},\"createdAt\":1793177370,\"delay\":null}', 0, NULL, 1793177370, 1793177370);

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
(4, '2026_10_28_000001_create_products_table', 1),
(5, '2026_10_28_000002_create_customers_table', 1),
(6, '2026_10_28_000003_create_orders_table', 1),
(7, '2026_10_28_000004_create_stock_movements_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(32) DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `tax_total` decimal(12,2) NOT NULL,
  `grand_total` decimal(12,2) NOT NULL,
  `amount_tendered` decimal(12,2) DEFAULT NULL,
  `change_due` decimal(12,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'confirmed',
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `subtotal`, `tax_total`, `grand_total`, `amount_tendered`, `change_due`, `status`, `placed_at`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20261028-000001', 1, 150.00, 12.00, 162.00, 200.00, 38.00, 'confirmed', '2026-10-28 08:39:22', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(2, 'ORD-20261028-000002', 1, 886.00, 44.30, 930.30, NULL, NULL, 'confirmed', '2026-10-28 08:39:22', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(3, 'ORD-20261028-000003', 2, 146.00, 10.08, 156.08, 200.00, 43.92, 'confirmed', '2026-10-28 08:39:22', '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(4, 'ORD-20261028-000004', 11, 818.00, 32.50, 850.50, 1000.00, 149.50, 'confirmed', '2026-10-28 08:49:30', '2026-10-28 08:49:30', '2026-10-28 08:49:30');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_code` varchar(32) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `tax_percent` decimal(5,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `line_subtotal` decimal(12,2) NOT NULL,
  `line_tax` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_code`, `product_name`, `unit_price`, `tax_percent`, `quantity`, `line_subtotal`, `line_tax`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'COL-100', 'Colgate Toothpaste', 50.00, 8.00, 2, 100.00, 8.00, 108.00, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(2, 1, 2, 'PAR-010', 'Parle-G Biscuit', 10.00, 8.00, 5, 50.00, 4.00, 54.00, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(3, 2, 6, 'RIC-005', 'Basmati Rice 5kg', 650.00, 5.00, 1, 650.00, 32.50, 682.50, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(4, 2, 8, 'SUG-001', 'Sugar 1kg', 48.00, 5.00, 2, 96.00, 4.80, 100.80, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(5, 2, 9, 'TEA-250', 'Tea Powder 250g', 140.00, 5.00, 1, 140.00, 7.00, 147.00, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(6, 3, 4, 'MLK-001', 'Milk 1L', 62.00, 0.00, 1, 62.00, 0.00, 62.00, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(7, 3, 18, 'NOO-070', 'Instant Noodles 70g', 14.00, 12.00, 6, 84.00, 10.08, 94.08, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(8, 4, 6, 'RIC-005', 'Basmati Rice 5kg', 650.00, 5.00, 1, 650.00, 32.50, 682.50, '2026-10-28 08:49:30', '2026-10-28 08:49:30'),
(9, 4, 5, 'EGG-012', 'Eggs (12)', 84.00, 0.00, 2, 168.00, 0.00, 168.00, '2026-10-28 08:49:30', '2026-10-28 08:49:30');

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
  `code` varchar(32) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `stock_on_hand` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `price`, `tax_percent`, `stock_on_hand`, `created_at`, `updated_at`) VALUES
(1, 'COL-100', 'Colgate Toothpaste', 50.00, 8.00, 38, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(2, 'PAR-010', 'Parle-G Biscuit', 10.00, 8.00, 115, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(3, 'BRD-001', 'Bread', 45.00, 0.00, 4, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(4, 'MLK-001', 'Milk 1L', 62.00, 0.00, 8, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(5, 'EGG-012', 'Eggs (12)', 84.00, 0.00, 0, '2026-10-28 08:39:22', '2026-10-28 08:49:30'),
(6, 'RIC-005', 'Basmati Rice 5kg', 650.00, 5.00, 23, '2026-10-28 08:39:22', '2026-10-28 08:49:30'),
(7, 'ATA-005', 'Wheat Atta 5kg', 285.00, 5.00, 30, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(8, 'SUG-001', 'Sugar 1kg', 48.00, 5.00, 58, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(9, 'TEA-250', 'Tea Powder 250g', 140.00, 5.00, 29, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(10, 'OIL-001', 'Sunflower Oil 1L', 155.00, 5.00, 18, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(11, 'SOP-100', 'Bath Soap 100g', 38.00, 18.00, 75, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(12, 'SHM-180', 'Shampoo 180ml', 165.00, 18.00, 22, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(13, 'DET-001', 'Detergent Powder 1kg', 120.00, 18.00, 14, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(14, 'SAL-001', 'Iodised Salt 1kg', 28.00, 0.00, 50, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(15, 'CHO-050', 'Chocolate Bar 50g', 40.00, 18.00, 0, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(16, 'BUT-100', 'Butter 100g', 58.00, 12.00, 7, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(17, 'PAN-200', 'Paneer 200g', 90.00, 5.00, 11, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(18, 'NOO-070', 'Instant Noodles 70g', 14.00, 12.00, 194, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(19, 'JAM-500', 'Mixed Fruit Jam 500g', 175.00, 12.00, 10, '2026-10-28 08:39:22', '2026-10-28 08:39:22'),
(20, 'COF-100', 'Instant Coffee 100g', 310.00, 18.00, 1, '2026-10-28 08:39:22', '2026-10-28 08:39:22');

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
('duuITM3A33mJq4LOYalj5aklrxaoFNjCUm2rFyz2', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNXBBTFRVZ1VEZnliT3FZelNBT3NIVm1JVVU4ckhEN05BNnk4bTQ2QiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9sb2NhbGhvc3QvdGFzay9wdWJsaWMiO3M6NToicm91dGUiO3M6NzoiY291bnRlciI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793177407);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `change` int(11) NOT NULL,
  `balance_after` int(10) UNSIGNED NOT NULL,
  `reason` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `order_id`, `change`, `balance_after`, `reason`, `created_at`) VALUES
(1, 1, NULL, 40, 40, 'seed', '2026-10-28 08:39:22'),
(2, 2, NULL, 120, 120, 'seed', '2026-10-28 08:39:22'),
(3, 3, NULL, 4, 4, 'seed', '2026-10-28 08:39:22'),
(4, 4, NULL, 9, 9, 'seed', '2026-10-28 08:39:22'),
(5, 5, NULL, 2, 2, 'seed', '2026-10-28 08:39:22'),
(6, 6, NULL, 25, 25, 'seed', '2026-10-28 08:39:22'),
(7, 7, NULL, 30, 30, 'seed', '2026-10-28 08:39:22'),
(8, 8, NULL, 60, 60, 'seed', '2026-10-28 08:39:22'),
(9, 9, NULL, 30, 30, 'seed', '2026-10-28 08:39:22'),
(10, 10, NULL, 18, 18, 'seed', '2026-10-28 08:39:22'),
(11, 11, NULL, 75, 75, 'seed', '2026-10-28 08:39:22'),
(12, 12, NULL, 22, 22, 'seed', '2026-10-28 08:39:22'),
(13, 13, NULL, 14, 14, 'seed', '2026-10-28 08:39:22'),
(14, 14, NULL, 50, 50, 'seed', '2026-10-28 08:39:22'),
(15, 15, NULL, 0, 0, 'seed', '2026-10-28 08:39:22'),
(16, 16, NULL, 7, 7, 'seed', '2026-10-28 08:39:22'),
(17, 17, NULL, 11, 11, 'seed', '2026-10-28 08:39:22'),
(18, 18, NULL, 200, 200, 'seed', '2026-10-28 08:39:22'),
(19, 19, NULL, 10, 10, 'seed', '2026-10-28 08:39:22'),
(20, 20, NULL, 1, 1, 'seed', '2026-10-28 08:39:22'),
(21, 1, 1, -2, 38, 'sale', '2026-10-28 08:39:22'),
(22, 2, 1, -5, 115, 'sale', '2026-10-28 08:39:22'),
(23, 6, 2, -1, 24, 'sale', '2026-10-28 08:39:22'),
(24, 8, 2, -2, 58, 'sale', '2026-10-28 08:39:22'),
(25, 9, 2, -1, 29, 'sale', '2026-10-28 08:39:22'),
(26, 4, 3, -1, 8, 'sale', '2026-10-28 08:39:22'),
(27, 18, 3, -6, 194, 'sale', '2026-10-28 08:39:22'),
(28, 6, 4, -1, 23, 'sale', '2026-10-28 08:49:30'),
(29, 5, 4, -2, 0, 'sale', '2026-10-28 08:49:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

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
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_email_unique` (`email`);

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
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_customer_id_placed_at_index` (`customer_id`,`placed_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_items_order_id_product_id_unique` (`order_id`,`product_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`);

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
  ADD UNIQUE KEY `products_code_unique` (`code`),
  ADD KEY `products_stock_on_hand_index` (`stock_on_hand`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_order_id_foreign` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
