-- Publishes the ModusArtem x Casely Just-In-Case cases (Animo Bronze, Scarlet, Titanium).
-- Price and phones come from caselygh.store on 2026-11-04; all were available there.
-- Stock counts aren't public, so each starts at 20. Correct them in admin. Safe to run twice.
START TRANSACTION;

UPDATE products
SET product_price = 125.00, is_active = 1,
    product_desc = 'ModusArtem x Casely Just-In-Case collab case.',
    product_keywords = CONCAT(LOWER(product_title), ' phone case modusartem just-in-case collab')
WHERE product_slug IN ('animo-bronze','animo-scarlet','animo-titanium');

INSERT IGNORE INTO product_variants (product_id, model_id, case_type, variant_price, stock)
SELECT p.product_id, m.model_id, t.case_type, IF(t.case_type = 'Tough', 150.00, 125.00), 20
FROM products p
JOIN phone_models m ON m.model_name IN ('iPhone 11','iPhone 11 Pro','iPhone 11 Pro Max','iPhone 12','iPhone 12 Pro','iPhone 12 Pro Max','iPhone 13','iPhone 13 Pro','iPhone 13 Pro Max','iPhone 14','iPhone 14 Plus','iPhone 14 Pro','iPhone 14 Pro Max','iPhone 15','iPhone 15 Plus','iPhone 15 Pro','iPhone 15 Pro Max','iPhone 16','iPhone 16 Plus','iPhone 16 Pro','iPhone 16 Pro Max','iPhone 17','iPhone 17 Pro','iPhone 17 Pro Max')
JOIN (SELECT 'Standard' AS case_type UNION ALL SELECT 'Tough') t
WHERE p.product_slug IN ('animo-bronze','animo-scarlet','animo-titanium');

COMMIT;
