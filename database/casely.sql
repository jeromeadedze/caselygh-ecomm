-- ============================================================
-- Casely Store database
-- ------------------------------------------------------------
-- Import into an empty database named `casely`
-- (phpMyAdmin > Import, or: mysql -u root -p casely < casely.sql)
--
-- Seed accounts (change these passwords before going live):
--   Admin:    admin@casely.store   /  Casely@Admin2026
--   Customer: demo@casely.store    /  Demo@2026
--
-- The six products are the sample designs from the Lovable
-- prototype. Replace them with Casely's real catalogue from the
-- admin area (Admin > Products).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `community_posts`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `orderdetails`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `custom_designs`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `phone_models`;
DROP TABLE IF EXISTS `phone_brands`;
DROP TABLE IF EXISTS `collections`;
DROP TABLE IF EXISTS `customer`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Users ----------
-- user_role: 1 = admin (Casely team), 2 = customer
CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_pass` varchar(255) NOT NULL,
  `customer_contact` varchar(20) NOT NULL,
  `customer_city` varchar(60) NOT NULL,
  `user_role` tinyint(4) NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Catalogue ----------
CREATE TABLE `collections` (
  `collection_id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_name` varchar(60) NOT NULL,
  `collection_slug` varchar(60) NOT NULL,
  `collection_tone` varchar(30) NOT NULL DEFAULT 'pink',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`collection_id`),
  UNIQUE KEY `collection_slug` (`collection_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `phone_brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(60) NOT NULL,
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `phone_models` (
  `model_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL,
  `model_name` varchar(60) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`model_id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `phone_models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `phone_brands` (`brand_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- product_price is the Standard price; a Tough variant adds TOUGH_EXTRA.
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `product_title` varchar(150) NOT NULL,
  `product_slug` varchar(160) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_desc` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_keywords` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_slug` (`product_slug`),
  KEY `collection_id` (`collection_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per product x phone model x case type, each with its own
-- price and stock. This is what lets customers shop for their exact phone.
CREATE TABLE `product_variants` (
  `variant_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `model_id` int(11) NOT NULL,
  `case_type` enum('Standard','Tough') NOT NULL DEFAULT 'Standard',
  `variant_price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`variant_id`),
  UNIQUE KEY `product_model_type` (`product_id`,`model_id`,`case_type`),
  KEY `model_id` (`model_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `product_variants_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `phone_models` (`model_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Custom case studio ----------
-- design_image = the print-ready PNG exported from the studio
-- source_image = the customer's original photo (kept private)
-- settings     = JSON of the studio choices (zoom, rotation, font, layout ...)
-- status       = pending until the Casely team approves it for printing
CREATE TABLE `custom_designs` (
  `design_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `cart_key` varchar(64) NOT NULL,
  `model_id` int(11) NOT NULL,
  `case_type` enum('Standard','Tough') NOT NULL DEFAULT 'Standard',
  `design_image` varchar(255) NOT NULL,
  `source_image` varchar(255) DEFAULT NULL,
  `design_text` varchar(30) DEFAULT NULL,
  `settings` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `consent_given` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `review_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`design_id`),
  KEY `customer_id` (`customer_id`),
  KEY `model_id` (`model_id`),
  CONSTRAINT `custom_designs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL,
  CONSTRAINT `custom_designs_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `phone_models` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Cart ----------
-- A cart row holds either a ready-made variant or a custom design.
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_key` varchar(64) NOT NULL,
  `c_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `design_id` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  KEY `cart_key` (`cart_key`),
  KEY `c_id` (`c_id`),
  KEY `variant_id` (`variant_id`),
  KEY `design_id` (`design_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`c_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`design_id`) REFERENCES `custom_designs` (`design_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Orders and payments ----------
-- order_status: pending_payment, paid, printing, out_for_delivery, delivered, cancelled
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `invoice_no` varchar(20) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_status` varchar(30) NOT NULL DEFAULT 'pending_payment',
  `delivery_name` varchar(100) NOT NULL,
  `delivery_phone` varchar(20) NOT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `delivery_city` varchar(60) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- item_name and unit_price are copied at purchase time, so later price
-- or name changes never rewrite old orders.
CREATE TABLE `orderdetails` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `design_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `orderdetails_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `orderdetails_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE SET NULL,
  CONSTRAINT `orderdetails_ibfk_3` FOREIGN KEY (`design_id`) REFERENCES `custom_designs` (`design_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
  `pay_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'GHS',
  `channel` varchar(30) DEFAULT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pay_id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Casely Community ----------
CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `post_image` varchar(255) NOT NULL,
  `caption` varchar(150) DEFAULT NULL,
  `handle` varchar(40) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reward_code` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Seed data
-- ============================================================

INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `customer_city`, `user_role`) VALUES
(1, 'Casely Admin', 'admin@casely.store', '$2y$10$Ozlnd92Mx2v6SfSxmhprheqlNiFhOY450sRgdaG5D.K7UH334U7La', '0200000000', 'Accra', 1),
(2, 'Demo Customer', 'demo@casely.store', '$2y$10$EdQC7hlx3QnB8cHJQxkLQOHRQ4wyi/Y9MbfZt2AIUIQXLOnMrzvf2', '0240000000', 'Accra', 2);

INSERT INTO `collections` (`collection_id`, `collection_name`, `collection_slug`, `collection_tone`, `sort_order`) VALUES
(1, 'Grab n Go', 'grab-n-go', 'pink', 1),
(2, 'Designed', 'designed', 'lilac', 2),
(3, 'Tough', 'tough', 'sky', 3),
(4, 'Limited drops', 'limited-drops', 'butter', 4);

INSERT INTO `phone_brands` (`brand_id`, `brand_name`) VALUES
(1, 'Apple'),
(2, 'Samsung');

INSERT INTO `phone_models` (`model_id`, `brand_id`, `model_name`, `sort_order`) VALUES
(1, 1, 'iPhone 13', 1),
(2, 1, 'iPhone 14', 2),
(3, 1, 'iPhone 15', 3),
(4, 1, 'iPhone 15 Pro', 4),
(5, 1, 'iPhone 16 Pro', 5),
(6, 1, 'iPhone 17 Pro', 6),
(7, 2, 'Galaxy S23', 1),
(8, 2, 'Galaxy S24', 2),
(9, 2, 'Galaxy A54', 3);

INSERT INTO `products` (`product_id`, `collection_id`, `product_title`, `product_slug`, `product_price`, `product_desc`, `product_image`, `product_keywords`) VALUES
(1, 2, 'Pink Script Initial', 'pink-script-initial', 95.00, 'Soft-touch pink with your initial in elegant white script.', 'case_pink_initial.jpg', 'pink initial script name'),
(2, 2, 'Lilac Gingham Name', 'lilac-gingham-name', 110.00, 'Dreamy lilac gingham finished with your name in handwriting.', 'case_lilac_gingham.jpg', 'lilac gingham name purple'),
(3, 4, 'Butter Bold Name', 'butter-bold-name', 120.00, 'Butter yellow with a loud, proud vertical name.', 'case_yellow_name.jpg', 'yellow butter bold name'),
(4, 3, 'Sky Gingham', 'sky-gingham-tough', 130.00, 'Sky blue gingham on our double-layer drop-tested shell.', 'case_blue_gingham.jpg', 'blue sky gingham tough'),
(5, 1, 'Pink Everyday', 'pink-everyday', 75.00, 'Ready to ship today. Pretty, slim and simple.', 'case_pink_initial.jpg', 'pink everyday plain ready'),
(6, 1, 'Lilac Everyday', 'lilac-everyday', 80.00, 'Ready-made gingham classic, ships same day in Accra.', 'case_lilac_gingham.jpg', 'lilac gingham everyday ready');

-- Which phones each product is made for (from the prototype's data):
-- Apple-only products: 2, 4, 6. Apple + Samsung: 1, 3, 5.
-- Every product gets a Standard and a Tough variant per model, 20 in stock.
INSERT INTO `product_variants` (`product_id`, `model_id`, `case_type`, `variant_price`, `stock`)
SELECT p.product_id, m.model_id, t.case_type,
       p.product_price + IF(t.case_type = 'Tough', 25, 0), 20
FROM products p
JOIN phone_models m
JOIN (SELECT 'Standard' AS case_type UNION ALL SELECT 'Tough') t
WHERE m.brand_id = 1 OR p.product_id IN (1, 3, 5);

-- Three approved demo posts so the Community wall is not empty.
-- Delete these once real customers start posting.
INSERT INTO `community_posts` (`customer_id`, `product_id`, `post_image`, `caption`, `handle`, `status`) VALUES
(2, 1, 'seed/community_selfie.jpg', 'Demo post', '@casely.gh', 'approved'),
(2, 3, 'seed/case_yellow_name.jpg', 'Demo post', '@casely.gh', 'approved'),
(2, 2, 'seed/case_lilac_gingham.jpg', 'Demo post', '@casely.gh', 'approved');
