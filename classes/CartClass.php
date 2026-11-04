<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * CartClass (Model)
 * The shopping cart. Each row is either a ready-made product variant
 * or a custom design, and belongs to a cart key (one per visitor).
 */
class CartClass extends Database
{
    // Add a ready-made case. If the same variant is already in the cart,
    // increase its quantity instead of adding a second row.
    public function addVariant($cartKey, $customerId, $variantId, $qty)
    {
        $existing = $this->fetchOne("SELECT cart_id, qty FROM cart WHERE cart_key = ? AND variant_id = ?", [$cartKey, $variantId]);
        if ($existing) {
            return $this->execute("UPDATE cart SET qty = ? WHERE cart_id = ?", [min(10, $existing['qty'] + $qty), $existing['cart_id']]);
        }
        return $this->execute("INSERT INTO cart (cart_key, c_id, variant_id, qty) VALUES (?, ?, ?, ?)", [$cartKey, $customerId, $variantId, $qty]);
    }

    // Add a custom design (always quantity 1 to start).
    public function addDesign($cartKey, $customerId, $designId)
    {
        return $this->execute("INSERT INTO cart (cart_key, c_id, design_id, qty) VALUES (?, ?, ?, 1)", [$cartKey, $customerId, $designId]);
    }

    /**
     * Every item in a cart with what the cart page needs to show it:
     * name, image, phone model, case type, unit price and line total.
     */
    public function getCartItems($cartKey)
    {
        $sql = "SELECT cart.cart_id, cart.qty, cart.variant_id, cart.design_id,
                       COALESCE(p.product_title, 'Custom case') AS item_name,
                       p.product_slug, p.product_image, d.design_text,
                       COALESCE(vm.model_name, dm.model_name) AS model_name,
                       COALESCE(v.case_type, d.case_type) AS case_type,
                       COALESCE(v.variant_price, d.price) AS unit_price,
                       v.stock
                FROM cart
                LEFT JOIN product_variants v ON v.variant_id = cart.variant_id
                LEFT JOIN products p ON p.product_id = v.product_id
                LEFT JOIN phone_models vm ON vm.model_id = v.model_id
                LEFT JOIN custom_designs d ON d.design_id = cart.design_id
                LEFT JOIN phone_models dm ON dm.model_id = d.model_id
                WHERE cart.cart_key = ?
                ORDER BY cart.added_at, cart.cart_id";
        $items = $this->fetchAll($sql, [$cartKey]);
        foreach ($items as &$item) {
            $item['is_custom'] = !empty($item['design_id']);
            $item['line_total'] = (float) $item['unit_price'] * (int) $item['qty'];
        }
        return $items;
    }

    public function getCartCount($cartKey)
    {
        $row = $this->fetchOne("SELECT COALESCE(SUM(qty), 0) AS n FROM cart WHERE cart_key = ?", [$cartKey]);
        return (int) $row['n'];
    }

    public function getCartTotal($cartKey)
    {
        $total = 0.0;
        foreach ($this->getCartItems($cartKey) as $item) {
            $total += $item['line_total'];
        }
        return $total;
    }

    // The cart key in the WHERE clause stops one visitor editing another's cart.
    public function updateQty($cartId, $qty, $cartKey)
    {
        if ($qty < 1) {
            return false;
        }
        return $this->execute("UPDATE cart SET qty = ? WHERE cart_id = ? AND cart_key = ?", [min(10, $qty), $cartId, $cartKey]);
    }

    public function removeItem($cartId, $cartKey)
    {
        return $this->execute("DELETE FROM cart WHERE cart_id = ? AND cart_key = ?", [$cartId, $cartKey]);
    }

    public function clearCart($cartKey)
    {
        return $this->execute("DELETE FROM cart WHERE cart_key = ?", [$cartKey]);
    }

    // After login, link the visitor's cart (and its custom designs) to their account.
    public function attachCustomer($cartKey, $customerId)
    {
        $this->execute("UPDATE cart SET c_id = ? WHERE cart_key = ?", [$customerId, $cartKey]);
        $this->execute("UPDATE custom_designs SET customer_id = ? WHERE cart_key = ? AND customer_id IS NULL", [$customerId, $cartKey]);
        return true;
    }
}
