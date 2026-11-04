-- Adds the iPhone Air and makes every active case available for it.
-- Only adds rows. Safe to run twice: nothing is duplicated or changed.
-- Stock starts at 20 per case type, the same as the other seeded phones.
INSERT INTO phone_models (brand_id, model_name, sort_order)
SELECT b.brand_id, 'iPhone Air', 7
FROM phone_brands b
WHERE b.brand_name = 'Apple'
  AND NOT EXISTS (SELECT 1 FROM phone_models m WHERE m.model_name = 'iPhone Air');

INSERT INTO product_variants (product_id, model_id, case_type, variant_price, stock)
SELECT p.product_id, m.model_id, t.case_type, p.product_price + IF(t.case_type = 'Tough', 25, 0), 20
FROM products p
JOIN phone_models m ON m.model_name = 'iPhone Air'
JOIN (SELECT 'Standard' AS case_type UNION ALL SELECT 'Tough') t
WHERE p.is_active = 1
  AND NOT EXISTS (
    SELECT 1 FROM product_variants v
    WHERE v.product_id = p.product_id AND v.model_id = m.model_id AND v.case_type = t.case_type
  );
