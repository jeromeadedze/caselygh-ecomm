<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * OrderClass (Model)
 * Orders, their line items (orderdetails) and Paystack payments.
 */
class OrderClass extends Database
{
    /**
     * Create an order and its items in one transaction, so a failure
     * never leaves half an order behind. Returns the new order_id.
     */
    public function createOrder($customerId, array $delivery, array $items, $deliveryFee)
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['unit_price'] * (int) $item['qty'];
        }
        $total = $subtotal + $deliveryFee;

        try {
            $this->beginTransaction();

            $invoice = $this->newInvoiceNo();
            $sql = "INSERT INTO orders (customer_id, invoice_no, order_status, delivery_name, delivery_phone,
                                        delivery_address, delivery_city, subtotal, delivery_fee, total)
                    VALUES (?, ?, 'pending_payment', ?, ?, ?, ?, ?, ?, ?)";
            $this->execute($sql, [
                $customerId, $invoice, $delivery['name'], $delivery['phone'],
                $delivery['address'], $delivery['city'], $subtotal, $deliveryFee, $total,
            ]);
            $orderId = (int) $this->getLastInsertId();

            $itemSql = "INSERT INTO orderdetails (order_id, variant_id, design_id, item_name, qty, unit_price)
                        VALUES (?, ?, ?, ?, ?, ?)";
            foreach ($items as $item) {
                $name = $item['item_name'] . ' · ' . $item['model_name'] . ' · ' . $item['case_type'];
                $this->execute($itemSql, [
                    $orderId, $item['variant_id'] ?: null, $item['design_id'] ?: null,
                    $name, (int) $item['qty'], $item['unit_price'],
                ]);
            }

            $this->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->rollBack();
            error_log("createOrder failed: " . $e->getMessage());
            return false;
        }
    }

    // Short, readable, unique invoice number, e.g. CSL-48213907
    private function newInvoiceNo()
    {
        do {
            $invoice = 'CSL-' . random_int(10000000, 99999999);
        } while ($this->fetchOne("SELECT order_id FROM orders WHERE invoice_no = ?", [$invoice]));
        return $invoice;
    }

    public function getOrder($orderId)
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_email
                FROM orders o JOIN customer c ON c.customer_id = o.customer_id
                WHERE o.order_id = ?";
        return $this->fetchOne($sql, [$orderId]);
    }

    public function getOrderItems($orderId)
    {
        $sql = "SELECT od.*, v.product_id, p.product_image, d.design_image, d.status AS design_status
                FROM orderdetails od
                LEFT JOIN product_variants v ON v.variant_id = od.variant_id
                LEFT JOIN products p ON p.product_id = v.product_id
                LEFT JOIN custom_designs d ON d.design_id = od.design_id
                WHERE od.order_id = ?";
        return $this->fetchAll($sql, [$orderId]);
    }

    public function getOrdersByCustomer($customerId)
    {
        $sql = "SELECT o.*, (SELECT COALESCE(SUM(qty), 0) FROM orderdetails WHERE order_id = o.order_id) AS item_count
                FROM orders o WHERE o.customer_id = ? ORDER BY o.order_date DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function countAllOrders($status = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM orders" . ($status ? " WHERE order_status = ?" : "");
        $row = $this->fetchOne($sql, $status ? [$status] : []);
        return (int) ($row['total'] ?? 0);
    }

    public function getAllOrders($status = null, $limit = 0, $offset = 0)
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_email,
                       (SELECT COALESCE(SUM(qty), 0) FROM orderdetails WHERE order_id = o.order_id) AS item_count
                FROM orders o JOIN customer c ON c.customer_id = o.customer_id";
        $params = [];
        if ($status) {
            $sql .= " WHERE o.order_status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY o.order_date DESC, o.order_id DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . max(0, (int) $offset);
        }
        return $this->fetchAll($sql, $params);
    }

    public function updateStatus($orderId, $status)
    {
        return $this->execute("UPDATE orders SET order_status = ? WHERE order_id = ?", [$status, $orderId]);
    }

    // ---------- Payments ----------
    public function createPayment($orderId, $reference, $amount)
    {
        return $this->execute("INSERT INTO payments (order_id, reference, amount) VALUES (?, ?, ?)", [$orderId, $reference, $amount]);
    }

    public function getPaymentByReference($reference)
    {
        return $this->fetchOne("SELECT * FROM payments WHERE reference = ?", [$reference]);
    }

    public function getPaymentForOrder($orderId)
    {
        return $this->fetchOne("SELECT * FROM payments WHERE order_id = ? ORDER BY pay_id DESC LIMIT 1", [$orderId]);
    }

    /**
     * Mark a payment successful and its order paid. Returns true only the
     * first time, so the callback and the webhook can both call this without
     * double-processing an order.
     */
    public function markPaid($reference, $channel)
    {
        try {
            $this->beginTransaction();
            $changed = $this->executeCount(
                "UPDATE payments SET status = 'success', channel = ?, paid_at = NOW() WHERE reference = ? AND status <> 'success'",
                [$channel, $reference]
            );
            if ($changed === 1) {
                $this->execute(
                    "UPDATE orders o JOIN payments p ON p.order_id = o.order_id
                     SET o.order_status = 'paid' WHERE p.reference = ? AND o.order_status = 'pending_payment'",
                    [$reference]
                );
            }
            $this->commit();
            return $changed === 1;
        } catch (Exception $e) {
            $this->rollBack();
            error_log("markPaid failed: " . $e->getMessage());
            return false;
        }
    }

    public function markPaymentFailed($reference)
    {
        return $this->execute("UPDATE payments SET status = 'failed' WHERE reference = ? AND status = 'pending'", [$reference]);
    }

    // ---------- Admin dashboard numbers ----------
    public function getSalesSummary()
    {
        $sql = "SELECT
                  COALESCE(SUM(CASE WHEN order_status NOT IN ('pending_payment','cancelled') THEN total END), 0) AS revenue,
                  COALESCE(SUM(CASE WHEN order_status NOT IN ('pending_payment','cancelled') AND DATE(order_date) = CURDATE() THEN total END), 0) AS revenue_today,
                  SUM(order_status NOT IN ('pending_payment','cancelled')) AS paid_orders,
                  SUM(order_status IN ('paid','printing')) AS to_fulfil
                FROM orders";
        return $this->fetchOne($sql);
    }

    public function getBestSellers($limit = 5)
    {
        $sql = "SELECT p.product_title, SUM(od.qty) AS sold
                FROM orderdetails od
                JOIN orders o ON o.order_id = od.order_id AND o.order_status NOT IN ('pending_payment','cancelled')
                JOIN product_variants v ON v.variant_id = od.variant_id
                JOIN products p ON p.product_id = v.product_id
                GROUP BY p.product_id ORDER BY sold DESC LIMIT " . (int) $limit;
        return $this->fetchAll($sql);
    }
}
