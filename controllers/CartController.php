<?php

require_once __DIR__ . "/../classes/CartClass.php";
require_once __DIR__ . "/../classes/ProductClass.php";

/**
 * CartController
 * Checks a request makes sense (real variant, in stock, sensible quantity)
 * before asking CartClass to change the cart.
 */
class CartController
{
    private $cartModel;
    private $productModel;

    public function __construct()
    {
        $this->cartModel = new CartClass();
        $this->productModel = new ProductClass();
    }

    /**
     * Add a ready-made case for a chosen phone model and case type.
     * Returns ['success' => bool, 'error' => string]
     */
    public function addProduct($cartKey, $customerId, $productId, $modelId, $caseType, $qty)
    {
        if (!in_array($caseType, ['Standard', 'Tough'], true)) {
            return ['success' => false, 'error' => 'Choose a case type.'];
        }
        $variant = $this->productModel->getVariant($productId, $modelId, $caseType);
        if (!$variant || !$variant['is_active']) {
            return ['success' => false, 'error' => 'This case is not made for that phone. Choose another phone or case.'];
        }
        if ((int) $variant['stock'] < 1) {
            return ['success' => false, 'error' => 'Sold out for ' . $variant['model_name'] . '. Choose another phone or case.'];
        }
        $qty = max(1, min((int) $qty, (int) $variant['stock'], 10));
        $ok = $this->cartModel->addVariant($cartKey, $customerId, $variant['variant_id'], $qty);
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'Could not add to cart. Try again.'];
    }

    public function addDesign($cartKey, $customerId, $designId)
    {
        return $this->cartModel->addDesign($cartKey, $customerId, $designId);
    }

    public function getCartItems($cartKey)
    {
        return $this->cartModel->getCartItems($cartKey);
    }

    public function getCartCount($cartKey)
    {
        return $this->cartModel->getCartCount($cartKey);
    }

    public function getCartTotal($cartKey)
    {
        return $this->cartModel->getCartTotal($cartKey);
    }

    public function updateQty($cartId, $qty, $cartKey)
    {
        return $this->cartModel->updateQty($cartId, (int) $qty, $cartKey);
    }

    public function removeItem($cartId, $cartKey)
    {
        return $this->cartModel->removeItem($cartId, $cartKey);
    }

    public function clearCart($cartKey)
    {
        return $this->cartModel->clearCart($cartKey);
    }

    public function attachCustomer($cartKey, $customerId)
    {
        return $this->cartModel->attachCustomer($cartKey, $customerId);
    }

    // Items whose stock dropped below the quantity in the cart.
    public function findStockProblems(array $items)
    {
        $problems = [];
        foreach ($items as $item) {
            if (!$item['is_custom'] && (int) $item['stock'] < (int) $item['qty']) {
                $problems[] = $item['item_name'] . ' (' . $item['model_name'] . ')';
            }
        }
        return $problems;
    }
}
