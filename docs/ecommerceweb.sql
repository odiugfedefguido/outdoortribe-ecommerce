-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Ago 28, 2025 alle 18:05
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerceweb`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `cart_item`
--

CREATE TABLE `cart_item` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `category`
--

INSERT INTO `category` (`id`, `name`, `slug`, `is_active`) VALUES
(1, 'Attrezzatura Trekking', 'attrezzatura-trekking', 1),
(2, 'Ciclismo', 'ciclismo', 1),
(5, 'Trail Running', 'trail-running', 1),
(6, 'Arrampicata', 'arrampicata', 1),
(7, 'Campeggio', 'campeggio', 1),
(8, 'Ciclismo MTB', 'ciclismo-mtb', 1),
(9, 'Escursionismo', 'escursionismo', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `notification`
--

INSERT INTO `notification` (`id`, `user_id`, `product_id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 6, 13, 'product_sold', 'Hai venduto 1× merda', '/outdoortribe-ecommerce/public/products/details.php?id=13', 1, '2025-08-27 10:30:13'),
(4, 1, 7, 'product_sold', 'Hai venduto 1× casa', '/outdoortribe-ecommerce/public/products/details.php?id=7', 0, '2025-08-27 14:24:25'),
(5, 1, 4, 'product_sold', 'Hai venduto 3× doccia', '/outdoortribe-ecommerce/public/products/details.php?id=4', 0, '2025-08-28 11:49:10'),
(6, 6, 15, 'product_sold', 'Hai venduto 1× dosso', '/outdoortribe-ecommerce/public/products/details.php?id=15', 1, '2025-08-28 11:49:10'),
(8, 1, 8, 'product_sold', 'Hai venduto 1× lampada', '/outdoortribe-ecommerce/public/products/details.php?id=8', 0, '2025-08-28 12:27:40'),
(11, 6, 16, 'product_sold', 'Hai venduto 2× chiavi', '/outdoortribe-ecommerce/public/products/details.php?id=16', 1, '2025-08-28 12:38:16'),
(14, 1, 3, 'product_sold', 'Hai venduto 1× Casco Ciclismo Aero', '/outdoortribe-ecommerce/public/products/details.php?id=3', 0, '2025-08-28 12:40:25');

-- --------------------------------------------------------

--
-- Struttura della tabella `order`
--

CREATE TABLE `order` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_code` varchar(30) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `status` enum('placed','pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'placed',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'EUR',
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_address_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_name` varchar(120) DEFAULT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `customer_phone` varchar(40) DEFAULT NULL,
  `ship_address` varchar(255) DEFAULT NULL,
  `ship_city` varchar(120) DEFAULT NULL,
  `ship_zip` varchar(20) DEFAULT NULL,
  `ship_country` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `order`
--

INSERT INTO `order` (`id`, `user_id`, `order_code`, `buyer_id`, `seller_id`, `status`, `total_amount`, `total`, `currency`, `shipping_cost`, `vat_rate`, `vat_amount`, `grand_total`, `shipping_address_id`, `created_at`, `updated_at`, `customer_name`, `customer_email`, `customer_phone`, `ship_address`, `ship_city`, `ship_zip`, `ship_country`, `notes`, `payment_method`) VALUES
(2, 5, 'ORD20250826200733015', NULL, NULL, 'placed', 237.90, 0.00, '0', 0.00, 0.00, 0.00, 237.90, NULL, '2025-08-26 18:07:33', '2025-08-26 18:07:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 7, 'ORD20250827115559190', NULL, NULL, 'placed', 123.00, 0.00, '0', 0.00, 0.00, 0.00, 123.00, NULL, '2025-08-27 09:55:59', '2025-08-27 09:55:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 7, 'ORD20250827121324193', NULL, NULL, 'placed', 36.00, 0.00, '0', 0.00, 0.00, 0.00, 36.00, NULL, '2025-08-27 10:13:24', '2025-08-27 10:13:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 7, 'ORD20250827123013692', NULL, NULL, 'placed', 12.00, 0.00, '0', 0.00, 0.00, 0.00, 12.00, NULL, '2025-08-27 10:30:13', '2025-08-27 10:30:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 6, 'ORD20250827123337199', NULL, NULL, 'placed', 3.00, 0.00, '0', 0.00, 0.00, 0.00, 3.00, NULL, '2025-08-27 10:33:37', '2025-08-27 10:33:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 6, 'ORD20250827152708605', NULL, NULL, 'placed', 12.00, 0.00, '0', 0.00, 0.00, 0.00, 12.00, NULL, '2025-08-27 13:27:08', '2025-08-27 13:27:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 6, 'ORD20250827162425987', NULL, NULL, 'placed', 12.00, 0.00, '0', 0.00, 0.00, 0.00, 12.00, NULL, '2025-08-27 14:24:25', '2025-08-27 14:24:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 7, 'ORD20250828134910574', NULL, NULL, 'placed', 78.00, 0.00, '0', 0.00, 0.00, 0.00, 78.00, NULL, '2025-08-28 11:49:10', '2025-08-28 11:49:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 6, 'ORD20250828142740056', NULL, NULL, 'placed', 26.00, 0.00, '0', 0.00, 0.00, 0.00, 26.00, NULL, '2025-08-28 12:27:40', '2025-08-28 12:27:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 6, 'ORD20250828143429456', NULL, NULL, 'placed', 12.00, 0.00, '0', 0.00, 0.00, 0.00, 12.00, NULL, '2025-08-28 12:34:29', '2025-08-28 12:34:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 6, 'ORD20250828143816423', NULL, NULL, 'placed', 40.00, 0.00, '0', 0.00, 0.00, 0.00, 40.00, NULL, '2025-08-28 12:38:16', '2025-08-28 12:38:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 6, 'ORD20250828143938103', NULL, NULL, 'placed', 14.00, 0.00, 'EUR', 0.00, 0.00, 0.00, 14.00, NULL, '2025-08-28 12:39:38', '2025-08-28 12:39:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 6, 'ORD20250828144025223', NULL, NULL, 'placed', 89.00, 0.00, 'EUR', 0.00, 0.00, 0.00, 89.00, NULL, '2025-08-28 12:40:25', '2025-08-28 12:40:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Trigger `order`
--
DELIMITER $$
CREATE TRIGGER `trg_order_bi_code` BEFORE INSERT ON `order` FOR EACH ROW BEGIN
  IF NEW.order_code IS NULL OR NEW.order_code = '' THEN
    SET NEW.order_code = CONCAT(
      'ORD',
      DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'),
      LPAD(FLOOR(RAND()*1000), 3, '0')
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `order_item`
--

CREATE TABLE `order_item` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'EUR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `order_item`
--

INSERT INTO `order_item` (`id`, `order_id`, `product_id`, `qty`, `unit_price`, `currency`) VALUES
(1, 2, 2, 1, 59.90, 'EUR'),
(2, 2, 3, 2, 89.00, 'EUR'),
(3, 3, 10, 1, 123.00, 'EUR'),
(4, 4, 11, 3, 12.00, 'EUR'),
(5, 5, 13, 1, 12.00, 'EUR'),
(6, 6, 9, 1, 3.00, 'EUR'),
(7, 7, 13, 1, 12.00, 'EUR'),
(8, 8, 7, 1, 12.00, 'EUR'),
(9, 9, 4, 3, 18.00, 'EUR'),
(10, 9, 15, 1, 12.00, 'EUR'),
(11, 9, 7, 1, 12.00, 'EUR'),
(12, 10, 8, 1, 12.00, 'EUR'),
(13, 10, 16, 1, 14.00, 'EUR'),
(14, 11, 15, 1, 12.00, 'EUR'),
(15, 12, 16, 2, 14.00, 'EUR'),
(16, 12, 15, 1, 12.00, 'EUR'),
(17, 13, 16, 1, 14.00, 'EUR'),
(18, 14, 3, 1, 89.00, 'EUR');

-- --------------------------------------------------------

--
-- Struttura della tabella `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(280) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'EUR',
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_filename` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `product`
--

INSERT INTO `product` (`id`, `seller_id`, `title`, `slug`, `description`, `price`, `currency`, `stock`, `image_filename`, `is_active`, `category_id`, `created_at`, `updated_at`) VALUES
(2, 1, 'Zaino Trekking 35L', 'zaino-trekking-35l', 'Zaino leggero per escursioni giornaliere', 59.90, 'EUR', 19, NULL, 1, 1, '2025-08-13 14:59:56', '2025-08-26 18:07:33'),
(3, 1, 'Casco Ciclismo Aero', 'casco-ciclismo-aero', 'Casco ventilato per bici da strada', 89.00, 'EUR', 12, NULL, 1, 2, '2025-08-13 14:59:56', '2025-08-28 12:40:25'),
(4, 1, 'doccia', 'doccia', 'comoda', 18.00, 'EUR', 0, '4.jpg', 1, 1, '2025-08-27 08:58:48', '2025-08-28 11:49:10'),
(7, 1, 'casa', 'casa', 'casina', 12.00, 'EUR', 0, NULL, 1, 6, '2025-08-27 09:26:27', '2025-08-28 11:49:10'),
(8, 1, 'lampada', 'lampada', 'fa luce', 12.00, 'EUR', 2, NULL, 1, 8, '2025-08-27 09:37:09', '2025-08-28 12:27:40'),
(9, NULL, 'spatola', 'spatola', 'spatolina', 3.00, 'EUR', 0, '9.jpg', 1, 2, '2025-08-27 09:44:54', '2025-08-27 10:33:37'),
(10, 6, 'fede', 'fede', '235', 123.00, 'EUR', 0, 'default.jpg', 1, 1, '2025-08-27 09:53:52', '2025-08-27 09:55:59'),
(11, 6, 'canna', 'canna', 'cannone', 12.00, 'EUR', 0, NULL, 1, 9, '2025-08-27 10:12:20', '2025-08-27 10:13:24'),
(13, 6, 'merda', 'merda', 'merdona', 12.00, 'EUR', 0, NULL, 1, 6, '2025-08-27 10:29:37', '2025-08-27 13:27:08'),
(14, 6, 'tazza', 'tazza', '', 12.00, 'EUR', 0, NULL, 1, 1, '2025-08-27 12:21:31', '2025-08-27 12:21:31'),
(15, 6, 'dosso', 'dosso', '', 12.00, 'EUR', 0, 'default.jpg', 1, 6, '2025-08-27 14:54:18', '2025-08-28 12:38:16'),
(16, 6, 'chiavi', 'chiavi', 'ciao', 14.00, 'EUR', 0, '16.jpg', 1, 1, '2025-08-28 11:51:21', '2025-08-28 12:39:38'),
(17, 8, 'scarponi trekking', 'scarponi-trekking', 'scarpe comode', 80.00, 'EUR', 5, '17.jpg', 1, 1, '2025-08-28 14:43:14', '2025-08-28 14:43:14'),
(18, 8, 'mascotte', 'mascotte', 'mascotte castoro', 12.00, 'EUR', 14, 'default.jpg', 1, 7, '2025-08-28 14:44:03', '2025-08-28 14:44:03'),
(19, 8, 'tenda', 'tenda', '3 posti', 50.00, 'EUR', 3, '19.jpg', 1, 7, '2025-08-28 14:46:52', '2025-08-28 14:46:52');

-- --------------------------------------------------------

--
-- Struttura della tabella `product_category`
--

CREATE TABLE `product_category` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `product_category`
--

INSERT INTO `product_category` (`product_id`, `category_id`) VALUES
(2, 1),
(3, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `product_image`
--

CREATE TABLE `product_image` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `url` varchar(512) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `product_image`
--

INSERT INTO `product_image` (`id`, `product_id`, `url`, `sort_order`) VALUES
(1, 2, '/ecommerce/outdoortribe-ecommerce/public/images/demo/zaino.jpg', 1),
(2, 3, '/ecommerce/outdoortribe-ecommerce/public/images/demo/casco.jpg', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `seller_profile`
--

CREATE TABLE `seller_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `vat_number` varchar(32) DEFAULT NULL,
  `fiscal_code` varchar(32) DEFAULT NULL,
  `company_name` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `seller_profile`
--

INSERT INTO `seller_profile` (`id`, `user_id`, `display_name`, `vat_number`, `fiscal_code`, `company_name`, `created_at`) VALUES
(1, 1, 'Outdoor Seller Demo', NULL, NULL, NULL, '2025-08-13 14:59:31');

-- --------------------------------------------------------

--
-- Struttura della tabella `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `surname` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `ship_address` varchar(255) DEFAULT NULL,
  `ship_city` varchar(120) DEFAULT NULL,
  `ship_zip` varchar(20) DEFAULT NULL,
  `ship_country` varchar(120) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password_hash` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `user`
--

INSERT INTO `user` (`id`, `name`, `surname`, `email`, `phone`, `ship_address`, `ship_city`, `ship_zip`, `ship_country`, `role`, `created_at`, `password_hash`, `password`) VALUES
(1, 'Seller', 'Demo', 'seller@example.com', NULL, NULL, NULL, NULL, NULL, 'admin', '2025-08-13 14:59:31', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL),
(2, 'Buyer', 'Demo', 'buyer@example.com', NULL, NULL, NULL, NULL, NULL, 'user', '2025-08-13 15:00:47', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL),
(4, 'fede', '', 'fedef@rica.it', NULL, NULL, NULL, NULL, NULL, 'user', '2025-08-26 15:53:52', '$2y$10$gFrz.9mhsP5bQuiaRiqVDO4RGDQ2f/W9vUIQV.YiYKGkcwFfy/xTi', NULL),
(5, 'federica', '', 'guido@gmail.com', NULL, NULL, NULL, NULL, NULL, 'user', '2025-08-26 17:05:34', '$2y$10$9BHaXdqD57Y2FkYaMNTbdu1RGs/1H8xbXzgVc9wWQwTwy4lorMrL2', NULL),
(6, 'federica', '', 'rossi@mario.it', NULL, NULL, NULL, NULL, NULL, 'admin', '2025-08-27 08:41:50', '$2y$10$yCQJOYExQzvMYh4X1WiGOO3d0jmLx4l1KqBiFBvf5/adGIRlkiQU.', NULL),
(7, 'mario', '', 'fede@rica.it', NULL, NULL, NULL, NULL, NULL, 'user', '2025-08-27 09:55:24', '$2y$10$L5ivMZOnIle18tgZEtZRNul43BKpSbXASes6xYxQlIbK2NUEoyid.', NULL),
(8, 'Mario Rossi', '', 'mario@rossi.it', NULL, NULL, NULL, NULL, NULL, 'admin', '2025-08-28 14:41:30', '$2y$10$Mbw1TZteabLbl11mG0ILeeTwImoGk4uQFsiAe6osxF1N6rrHvc/12', NULL),
(9, 'federica guiducci', '', 'federica@gmail.com', NULL, NULL, NULL, NULL, NULL, 'user', '2025-08-28 14:44:51', '$2y$10$H/mBKlsnODAO4is53yId/eHtZ.8iOGUZ2HFbhB9xo95fvN0JvY7Wq', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `user_address`
--

CREATE TABLE `user_address` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(60) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `street` varchar(160) NOT NULL,
  `city` varchar(120) NOT NULL,
  `province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(80) NOT NULL DEFAULT 'Italia',
  `phone` varchar(40) DEFAULT NULL,
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user` (`user_id`);

--
-- Indici per le tabelle `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_ci_product` (`product_id`);

--
-- Indici per le tabelle `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_slug` (`slug`);

--
-- Indici per le tabelle `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_product_type` (`user_id`,`product_id`,`type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indici per le tabelle `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_code` (`order_code`),
  ADD KEY `fk_order_buyer` (`buyer_id`),
  ADD KEY `fk_order_seller` (`seller_id`),
  ADD KEY `fk_order_ship_addr` (`shipping_address_id`),
  ADD KEY `idx_order_user` (`user_id`);

--
-- Indici per le tabelle `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_oi_order` (`order_id`),
  ADD KEY `idx_oi_product` (`product_id`);

--
-- Indici per le tabelle `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_slug` (`slug`),
  ADD KEY `idx_product_seller` (`seller_id`),
  ADD KEY `idx_product_seller_id` (`seller_id`),
  ADD KEY `idx_product_category` (`category_id`),
  ADD KEY `idx_product_active` (`is_active`),
  ADD KEY `idx_product_stock` (`stock`);
ALTER TABLE `product` ADD FULLTEXT KEY `ft_product_title_desc` (`title`,`description`);

--
-- Indici per le tabelle `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `fk_pc_category` (`category_id`);

--
-- Indici per le tabelle `product_image`
--
ALTER TABLE `product_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_img_product` (`product_id`);

--
-- Indici per le tabelle `seller_profile`
--
ALTER TABLE `seller_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seller_profile_user` (`user_id`);

--
-- Indici per le tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_email` (`email`);

--
-- Indici per le tabelle `user_address`
--
ALTER TABLE `user_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_address_user` (`user_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT per la tabella `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT per la tabella `order`
--
ALTER TABLE `order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT per la tabella `order_item`
--
ALTER TABLE `order_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT per la tabella `product_image`
--
ALTER TABLE `product_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `seller_profile`
--
ALTER TABLE `seller_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `user_address`
--
ALTER TABLE `user_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `cart_item`
--
ALTER TABLE `cart_item`
  ADD CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ci_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_seller` FOREIGN KEY (`seller_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_ship_addr` FOREIGN KEY (`shipping_address_id`) REFERENCES `user_address` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_seller_setnull` FOREIGN KEY (`seller_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `product_category`
--
ALTER TABLE `product_category`
  ADD CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `product_image`
--
ALTER TABLE `product_image`
  ADD CONSTRAINT `fk_img_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `seller_profile`
--
ALTER TABLE `seller_profile`
  ADD CONSTRAINT `fk_seller_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `user_address`
--
ALTER TABLE `user_address`
  ADD CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
