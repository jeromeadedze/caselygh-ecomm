<?php

require_once __DIR__ . "/../classes/ProductClass.php";

/**
 * ProductController
 * Sits between the views/actions and ProductClass.
 */
class ProductController
{
    const LATEST_DESIGNS = 4; // how many cases the home page row shows

    private $productModel;

    public function __construct()
    {
        $this->productModel = new ProductClass();
    }

    public function getCollections()
    {
        return $this->productModel->getCollections();
    }

    public function getCollectionBySlug($slug)
    {
        return $this->productModel->getCollectionBySlug($slug);
    }

    public function getBrands()
    {
        return $this->productModel->getBrands();
    }

    public function getModels()
    {
        return $this->productModel->getModels();
    }

    // Models grouped by brand name: ['Apple' => [model rows], 'Samsung' => [...]]
    public function getModelsByBrand()
    {
        $grouped = [];
        foreach ($this->productModel->getModels() as $m) {
            $grouped[$m['brand_name']][] = $m;
        }
        return $grouped;
    }

    public function getModelById($id)
    {
        return $this->productModel->getModelById($id);
    }

    public function addModel($brandId, $name)
    {
        $name = trim($name);
        if ($name === '' || (int) $brandId <= 0) {
            return false;
        }
        return $this->productModel->addModel((int) $brandId, $name);
    }

    public function getProducts(array $filters = [])
    {
        return $this->productModel->getProducts($filters);
    }

    public function getFeaturedProducts($limit = self::LATEST_DESIGNS)
    {
        return $this->productModel->getLatestDesigns($limit);
    }

    public function countLowStockVariants($threshold = 3)
    {
        return $this->productModel->countLowStockVariants($threshold);
    }

    public function countFeatured()
    {
        return $this->productModel->countFeatured();
    }

    /** Add a case to the home page's Latest designs, or take it off. */
    public function setFeatured($productId, $featured)
    {
        if ($featured && $this->productModel->countFeatured() >= self::LATEST_DESIGNS) {
            return ['success' => false, 'error' => 'Latest designs is full. Remove a case first, then add this one.'];
        }
        return $this->productModel->setFeatured($productId, $featured)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Unable to update Latest designs. Try again.'];
    }

    public function getProductBySlug($slug)
    {
        return $this->productModel->getProductBySlug($slug);
    }

    public function getProductById($id)
    {
        return $this->productModel->getProductById($id);
    }

    public function getAllProductsAdmin()
    {
        return $this->productModel->getAllProductsAdmin();
    }

    public function getVariantsForProduct($productId)
    {
        return $this->productModel->getVariantsForProduct($productId);
    }

    public function getVariant($productId, $modelId, $caseType)
    {
        return $this->productModel->getVariant($productId, $modelId, $caseType);
    }

    /**
     * Create or update a product and its variants.
     * $data: collection_id, title, price, desc, keywords, model_ids[], stock
     * Returns ['success' => bool, 'product_id' => int, 'error' => string]
     */
    public function saveProduct(array $data, $image = null, $productId = 0)
    {
        $title = trim($data['title'] ?? '');
        $price = (float) ($data['price'] ?? 0);
        $collectionId = (int) ($data['collection_id'] ?? 0);
        $modelIds = $data['model_ids'] ?? [];

        if ($title === '' || $price <= 0 || $collectionId <= 0) {
            return ['success' => false, 'error' => 'Name, collection and a price above zero are required.'];
        }
        if (!$modelIds) {
            return ['success' => false, 'error' => 'Tick at least one phone model this case is made for.'];
        }

        $slug = $this->makeSlug($title, $productId);
        $desc = trim($data['desc'] ?? '');
        $keywords = trim($data['keywords'] ?? '');

        if ($productId) {
            $this->productModel->updateProduct($productId, $collectionId, $title, $slug, $price, $desc, $keywords, $image);
        } else {
            $productId = $this->productModel->addProduct($collectionId, $title, $slug, $price, $desc, $image, $keywords);
            if (!$productId) {
                return ['success' => false, 'error' => 'Unable to save this case. Check the details and try again.'];
            }
        }

        $this->productModel->syncVariants($productId, $modelIds, $price, TOUGH_EXTRA, max(0, (int) ($data['stock'] ?? 10)));
        return ['success' => true, 'product_id' => $productId];
    }

    public function setActive($productId, $active)
    {
        return $this->productModel->setActive($productId, $active);
    }

    public function updateVariantStock($variantId, $stock)
    {
        return $this->productModel->updateVariantStock($variantId, $stock);
    }

    public function decreaseStock($variantId, $qty)
    {
        return $this->productModel->decreaseStock($variantId, $qty);
    }

    public function getLowStockVariants($threshold = 3)
    {
        return $this->productModel->getLowStockVariants($threshold);
    }

    // "Pink Script Initial" -> "pink-script-initial" (made unique if needed)
    private function makeSlug($title, $exceptId)
    {
        $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-')) ?: 'case';
        $slug = $base;
        $n = 2;
        while ($this->productModel->slugExists($slug, (int) $exceptId)) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
