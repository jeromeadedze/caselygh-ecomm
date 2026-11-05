-- Adds order tracking to an existing Casely database. Adds only, and safe to run more than once.
-- tracking_token is the secret in an order's tracking link. Older orders get theirs from the
-- app the first time a link is needed, because PHP makes stronger random values than SQL here.
SET @has_token := (SELECT COUNT(*) FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'tracking_token');
SET @add_token := IF(@has_token = 0,
    'ALTER TABLE `orders` ADD COLUMN `tracking_token` char(32) DEFAULT NULL AFTER `invoice_no`, ADD UNIQUE KEY `tracking_token` (`tracking_token`)',
    'SELECT 1');
PREPARE add_token FROM @add_token;
EXECUTE add_token;
DEALLOCATE PREPARE add_token;

-- When each order reached each status, for the dates on the tracking page.
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(30) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment dates are already known for orders paid before this change.
INSERT INTO `order_status_history` (`order_id`, `status`, `changed_at`)
SELECT p.order_id, 'paid', p.paid_at FROM payments p
WHERE p.status = 'success' AND p.paid_at IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_status_history h WHERE h.order_id = p.order_id AND h.status = 'paid');
