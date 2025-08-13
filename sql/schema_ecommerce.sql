-- =========================
-- SCHEMA ECOMMERCEWEB
-- Pulizia
-- =========================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_item;
DROP TABLE IF EXISTS `order`;
DROP TABLE IF EXISTS cart_item;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS `user`;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================
-- TABELLE
-- =========================

CREATE TABLE `user` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE category (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NULL,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(180) NOT NULL UNIQUE,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `stock` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `image_filename` VARCHAR(200) DEFAULT NULL, -- se NULL si usa {id}.png
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES category(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart_item (         -- carrello in sessione utente
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL,
  UNIQUE KEY uq_user_product (user_id, product_id),
  CONSTRAINT fk_ci_user FOREIGN KEY (user_id) REFERENCES `user`(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ci_product FOREIGN KEY (product_id) REFERENCES product(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `status` ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES `user`(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_item (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES `order`(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES product(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- DATI DEMO
-- =========================

INSERT INTO `user` (name,email,password_hash,role) VALUES
('Demo Customer','customer@example.com',  -- pwd: demo1234
  '$2y$10$DnM8F9s.QyG0H3ag7p7F2O0bq2h2m8m3Qy6m1qR8jK2XwSXQyO1jS', 'customer'),
('Admin','admin@example.com',             -- pwd: admin1234
  '$2y$10$0YJdQyKZC3wR0qN1n7nC0uDk6n1y7i6G5Q2ZbJ7mnY3QkqGJpX2y2', 'admin');

INSERT INTO category (name, slug) VALUES
('Zaini','zaini'),
('Tende','tende'),
('Accessori','accessori');

INSERT INTO product (category_id,title,slug,description,price,stock,is_active,image_filename) VALUES
(1,'Zaino Trek 30L','zaino-trek-30l','Zaino leggero per trekking giornaliero.',69.90,25,1,NULL),
(1,'Zaino Pro 45L','zaino-pro-45l','Zaino escursionismo con supporto lombare.',99.00,12,1,NULL),
(2,'Tenda Ultra 2P','tenda-ultra-2p','Tenda due posti ultraleggera.',179.00,8,1,NULL),
(3,'Lampada Frontale X','lampada-frontale-x','Frontale LED ricaricabile.',24.50,40,1,NULL);

-- Nota immagini:
-- Salva i PNG con nome {id}.png in uploads/products/.
-- I quattro prodotti di cui sopra avranno immagini: 1.png, 2.png, 3.png, 4.png

-- =========================
-- FINE
-- =========================
