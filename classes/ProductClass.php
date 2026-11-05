<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * ProductClass (Model)
 * Products, their collections, phone brands/models and the
 * product variants (product x phone model x case type).
 */
class ProductClass extends Database
{
    // ---------- Collections ----------
    public function getCollections()
    {
        return $this->fetchAll("SELECT * FROM collections ORDER BY sort_order, collection_name");
    }

    public function getCollectionBySlug($slug)
    {
        return $this->fetchOne("SELECT * FROM collections WHERE collection_slug = ?", [$slug]);
    }

    // ---------- Phone brands and models ----------
    public function getBrands()
    {
        return $this->fetchAll("SELECT * FROM phone_brands ORDER BY brand_name");
    }

    public function getModels()
    {
        $sql = "SELECT m.*, b.brand_name
                FROM phone_models m JOIN phone_brands b ON b.brand_id = m.brand_id
                ORDER BY b.brand_name, m.sort_order, m.model_name";
        return $this->fetchAll($sql);
    }

    public function getModelById($id)
    {
        $sql = "SELECT m.*, b.brand_name FROM phone_models m
                JOIN phone_brands b ON b.brand_id = m.brand_id WHERE m.model_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addModel($brandId, $name)
    {
        $row = $this->fetchOne("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next FROM phone_models WHERE brand_id = ?", [$brandId]);
        return $this->execute("INSERT INTO phone_models (brand_id, model_name, sort_order) VALUES (?, ?, ?)", [$brandId, $name, $row['next']]);
    }

    // ---------- Products ----------
    /**
     * Products for the shop page.
     * $filters: collection (slug), brand_id, model_id, q (search), sort (newest|low|high), limit
     * A brand/model filter only shows products made for that phone.
     */
    public function getProducts(array $filters = [])
    {
        [$where, $params] = $this->productFilters($filters);

        $order = "p.created_at DESC, p.product_id DESC";
        if (($filters['sort'] ?? '') === 'low') {
            $order = "p.product_price ASC";
        } elseif (($filters['sort'] ?? '') === 'high') {
            $order = "p.product_price DESC";
        }

        $sql = "SELECT p.*, c.collection_name, c.collection_slug
                FROM products p JOIN collections c ON c.collection_id = p.collection_id
                WHERE " . implode(' AND ', $where) . " ORDER BY $order";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'] . " OFFSET " . max(0, (int) ($filters['offset'] ?? 0));
        }
        return $this->fetchAll($sql, $params);
    }

    public function countProducts(array $filters = [])
    {
        [$where, $params] = $this->productFilters($filters);
        $sql = "SELECT COUNT(*) AS total
                FROM products p JOIN collections c ON c.collection_id = p.collection_id
                WHERE " . implode(' AND ', $where);
        $row = $this->fetchOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    private function productFilters(array $filters)
    {
        $where = ["p.is_active = 1"];
        $params = [];

        if (!empty($filters['collection'])) {
            $where[] = "c.collection_slug = ?";
            $params[] = $filters['collection'];
        }
        if (!empty($filters['model_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.product_id AND v.model_id = ?)";
            $params[] = (int) $filters['model_id'];
        } elseif (!empty($filters['brand_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM product_variants v JOIN phone_models m ON m.model_id = v.model_id
                                WHERE v.product_id = p.product_id AND m.brand_id = ?)";
            $params[] = (int) $filters['brand_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = "(p.product_title LIKE ? OR p.product_keywords LIKE ? OR p.product_desc LIKE ?)";
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        return [$where, $params];
    }

    public function getProductBySlug($slug)
    {
        $sql = "SELECT p.*, c.collection_name, c.collection_slug
                FROM products p JOIN collections c ON c.collection_id = p.collection_id
                WHERE p.product_slug = ? AND p.is_active = 1";
        return $this->fetchOne($sql, [$slug]);
    }

    public function getProductById($id)
    {
        $sql = "SELECT p.*, c.collection_name, c.collection_slug
                FROM products p JOIN collections c ON c.collection_id = p.collection_id
                WHERE p.product_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    // All products for the admin list, including hidden ones, with stock totals.
    public function getAllProductsAdmin()
    {
        $sql = "SELECT p.*, c.collection_name,
                       COALESCE(SUM(v.stock), 0) AS total_stock,
                       COUNT(DISTINCT v.model_id) AS model_count
                FROM products p
                JOIN collections c ON c.collection_id = p.collection_id
                LEFT JOIN product_variants v ON v.product_id = p.product_id
                GROUP BY p.product_id
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql);
    }

    public function slugExists($slug, $exceptId = 0)
    {
        $row = $this->fetchOne("SELECT product_id FROM products WHERE product_slug = ? AND product_id <> ?", [$slug, $exceptId]);
        return $row ? true : false;
    }

    public function addProduct($collectionId, $title, $slug, $price, $desc, $image, $keywords)
    {
        $sql = "INSERT INTO products (collection_id, product_title, product_slug, product_price, product_desc, product_image, product_keywords)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $ok = $this->execute($sql, [$collectionId, $title, $slug, $price, $desc, $image, $keywords]);
        return $ok ? (int) $this->getLastInsertId() : false;
    }

    public function updateProduct($id, $collectionId, $title, $slug, $price, $desc, $keywords, $image = null)
    {
        if ($image !== null) {
            $sql = "UPDATE products SET collection_id = ?, product_title = ?, product_slug = ?, product_price = ?,
                    product_desc = ?, product_keywords = ?, product_image = ? WHERE product_id = ?";
            return $this->execute($sql, [$collectionId, $title, $slug, $price, $desc, $keywords, $image, $id]);
        }
        $sql = "UPDATE products SET collection_id = ?, product_title = ?, product_slug = ?, product_price = ?,
                product_desc = ?, product_keywords = ? WHERE product_id = ?";
        return $this->execute($sql, [$collectionId, $title, $slug, $price, $desc, $keywords, $id]);
    }

    public function setActive($id, $active)
    {
        return $this->execute("UPDATE products SET is_active = ? WHERE product_id = ?", [$active ? 1 : 0, $id]);
    }

    // Home page row: cases the team chose come first, then the newest fill any gaps.
    // The lowest price a customer can buy a listed case for right now. Null if none are in stock.
    public function getLowestPrice()
    {
        $row = $this->fetchOne(
            "SELECT MIN(v.variant_price) AS lowest FROM product_variants v
             JOIN products p ON p.product_id = v.product_id AND p.is_active = 1
             WHERE v.stock > 0"
        );
        return isset($row['lowest']) ? (float) $row['lowest'] : null;
    }

    public function getLatestDesigns($limit)
    {
        $sql = "SELECT p.*, c.collection_name, c.collection_slug
                FROM products p JOIN collections c ON c.collection_id = p.collection_id
                WHERE p.is_active = 1
                ORDER BY p.featured_at IS NULL, p.featured_at DESC, p.created_at DESC, p.product_id DESC
                LIMIT " . (int) $limit;
        return $this->fetchAll($sql);
    }

    public function countFeatured()
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM products WHERE featured_at IS NOT NULL AND is_active = 1");
        return (int) ($row['total'] ?? 0);
    }

    public function setFeatured($id, $featured)
    {
        $sql = "UPDATE products SET featured_at = " . ($featured ? "NOW()" : "NULL") . " WHERE product_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ---------- Variants ----------
    public function getVariantsForProduct($productId)
    {
        $sql = "SELECT v.*, m.model_name, m.brand_id, b.brand_name
                FROM product_variants v
                JOIN phone_models m ON m.model_id = v.model_id
                JOIN phone_brands b ON b.brand_id = m.brand_id
                WHERE v.product_id = ?
                ORDER BY b.brand_name, m.sort_order, v.case_type";
        return $this->fetchAll($sql, [$productId]);
    }

    public function getVariant($productId, $modelId, $caseType)
    {
        $sql = "SELECT v.*, p.product_title, p.is_active, m.model_name
                FROM product_variants v
                JOIN products p ON p.product_id = v.product_id
                JOIN phone_models m ON m.model_id = v.model_id
                WHERE v.product_id = ? AND v.model_id = ? AND v.case_type = ?";
        return $this->fetchOne($sql, [$productId, $modelId, $caseType]);
    }

    public function getVariantById($variantId)
    {
        $sql = "SELECT v.*, p.product_title, m.model_name FROM product_variants v
                JOIN products p ON p.product_id = v.product_id
                JOIN phone_models m ON m.model_id = v.model_id
                WHERE v.variant_id = ?";
        return $this->fetchOne($sql, [$variantId]);
    }

    /**
     * Make sure the product has a Standard and a Tough variant for each
     * chosen model, priced from the base price, and remove models that
     * were unticked. Existing stock numbers are kept.
     */
    public function syncVariants($productId, array $modelIds, $basePrice, $toughExtra, $defaultStock)
    {
        $modelIds = array_values(array_unique(array_map('intval', $modelIds)));

        foreach ($modelIds as $modelId) {
            foreach (['Standard', 'Tough'] as $type) {
                $price = $basePrice + ($type === 'Tough' ? $toughExtra : 0);
                $sql = "INSERT INTO product_variants (product_id, model_id, case_type, variant_price, stock)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE variant_price = VALUES(variant_price)";
                $this->execute($sql, [$productId, $modelId, $type, $price, $defaultStock]);
            }
        }

        if ($modelIds) {
            $marks = implode(',', array_fill(0, count($modelIds), '?'));
            $this->execute("DELETE FROM product_variants WHERE product_id = ? AND model_id NOT IN ($marks)", array_merge([$productId], $modelIds));
        } else {
            $this->execute("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
        }
        return true;
    }

    public function updateVariantStock($variantId, $stock)
    {
        return $this->execute("UPDATE product_variants SET stock = ? WHERE variant_id = ?", [max(0, (int) $stock), $variantId]);
    }

    // Reduce stock after a paid order (never below zero).
    public function decreaseStock($variantId, $qty)
    {
        return $this->execute("UPDATE product_variants SET stock = GREATEST(stock - ?, 0) WHERE variant_id = ?", [$qty, $variantId]);
    }

    public function getLowStockVariants($threshold = 3, $limit = 10)
    {
        $sql = "SELECT v.variant_id, v.stock, v.case_type, p.product_id, p.product_title, m.model_name
                FROM product_variants v
                JOIN products p ON p.product_id = v.product_id AND p.is_active = 1
                JOIN phone_models m ON m.model_id = v.model_id
                WHERE v.stock <= ?
                ORDER BY v.stock ASC, p.product_title, m.sort_order, v.case_type LIMIT " . (int) $limit;
        return $this->fetchAll($sql, [$threshold]);
    }

    public function countLowStockVariants($threshold = 3)
    {
        $sql = "SELECT COUNT(*) AS total FROM product_variants v
                JOIN products p ON p.product_id = v.product_id AND p.is_active = 1
                WHERE v.stock <= ?";
        $row = $this->fetchOne($sql, [$threshold]);
        return (int) ($row['total'] ?? 0);
    }
}
