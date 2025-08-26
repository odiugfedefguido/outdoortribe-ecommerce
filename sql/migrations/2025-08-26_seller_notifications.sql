-- 2025-08-26 Seller notifications (sold out)
-- Aggiunge tabella notification e (se non esiste) la colonna seller_id ai prodotti.

-- 1) Colonna seller_id su product (se non esiste)
ALTER TABLE product
  ADD COLUMN IF NOT EXISTS seller_id INT NULL AFTER category_id;

-- Aggiungi FK (se il tuo MySQL non supporta IF NOT EXISTS per FK, esegui manualmente)
-- ATTENZIONE: cambia il nome del vincolo se in conflitto
ALTER TABLE product
  ADD CONSTRAINT fk_product_seller
  FOREIGN KEY (seller_id) REFERENCES user(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- 2) Tabella notification
CREATE TABLE IF NOT EXISTS notification (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NULL,
  type ENUM('SOLD_OUT') NOT NULL,
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_product_type (user_id, product_id, type),
  KEY idx_user_id (user_id),
  KEY idx_product_id (product_id),
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
  CONSTRAINT fk_notification_product FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) (Opzionale) Unique key per cart_item se non c'è
ALTER TABLE cart_item
  ADD UNIQUE KEY IF NOT EXISTS uniq_cart (user_id, product_id);
