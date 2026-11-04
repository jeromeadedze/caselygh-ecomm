-- Adds studio drafts to an existing Casely database. Safe to run more than once.
-- ---------- Studio drafts ----------
-- A signed-in customer's unfinished custom design. `settings` holds every
-- studio choice as JSON; `photos` is a JSON list of the draft's photo files in
-- uploads/private/drafts/<customer_id>/ (never served directly).
CREATE TABLE IF NOT EXISTS `design_drafts` (
  `draft_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `draft_name` varchar(80) NOT NULL,
  `model_id` int(11) DEFAULT NULL,
  `settings` mediumtext NOT NULL,
  `photos` text NOT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`draft_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `design_drafts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `design_drafts_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `phone_models` (`model_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
