-- 
-- File: sql/schema_ecommerce.sql
-- Scopo: Bozza tabelle e‑commerce (NUOVO).
-- Stato: NUOVO (bozza da adattare al tuo schema utente).
-- -----------------------------------------------------

-- Utenti: riusa la tabella `user` esistente. Aggiungi colonna ruolo se manca.
-- ALTER TABLE `user` ADD COLUMN `role` ENUM('buyer','seller','admin') DEFAULT 'buyer';

CREATE TABLE IF NOT EXISTS `product` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `stock` INT NOT NULL DEFAULT 0,
  `images` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `cart_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS `order` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `status` ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `order_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL
);
