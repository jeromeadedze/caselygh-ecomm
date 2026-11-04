<?php

require_once __DIR__ . "/../classes/OrderClass.php";

/**
 * OrderController
 * Orders, order status changes and the admin sales summary.
 */
class OrderController
{
    private $orderModel;

    // Every status an order can have, in the order it normally moves through them.
    const STATUSES = ['pending_payment', 'paid', 'printing', 'out_for_delivery', 'delivered', 'cancelled'];

    public function __construct()
    {
        $this->orderModel = new OrderClass();
    }

    /**
     * Validate delivery details and create a pending order from the cart.
     * Returns ['success' => bool, 'order_id' => int, 'error' => string]
     */
    public function createFromCart($customerId, array $delivery, array $cartItems)
    {
        if (!$cartItems) {
            return ['success' => false, 'error' => 'Your cart is empty.'];
        }
        foreach (['name', 'phone', 'address', 'city'] as $field) {
            if (trim($delivery[$field] ?? '') === '') {
                return ['success' => false, 'error' => 'Fill in all delivery details.'];
            }
        }
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $delivery['phone']))) {
            return ['success' => false, 'error' => 'Enter a valid phone number for delivery.'];
        }

        $orderId = $this->orderModel->createOrder($customerId, array_map('trim', $delivery), $cartItems, DELIVERY_FEE);
        return $orderId ? ['success' => true, 'order_id' => $orderId] : ['success' => false, 'error' => 'We could not create your order. Try again.'];
    }

    public function getOrder($orderId)
    {
        return $this->orderModel->getOrder($orderId);
    }

    public function getOrderItems($orderId)
    {
        return $this->orderModel->getOrderItems($orderId);
    }

    public function getOrdersByCustomer($customerId)
    {
        return $this->orderModel->getOrdersByCustomer($customerId);
    }

    public function getAllOrders($status = null)
    {
        return $this->orderModel->getAllOrders(in_array($status, self::STATUSES, true) ? $status : null);
    }

    public function updateStatus($orderId, $status)
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        return $this->orderModel->updateStatus($orderId, $status);
    }

    public function createPayment($orderId, $reference, $amount)
    {
        return $this->orderModel->createPayment($orderId, $reference, $amount);
    }

    public function getPaymentByReference($reference)
    {
        return $this->orderModel->getPaymentByReference($reference);
    }

    public function getPaymentForOrder($orderId)
    {
        return $this->orderModel->getPaymentForOrder($orderId);
    }

    public function markPaid($reference, $channel)
    {
        return $this->orderModel->markPaid($reference, $channel);
    }

    public function markPaymentFailed($reference)
    {
        return $this->orderModel->markPaymentFailed($reference);
    }

    public function getSalesSummary()
    {
        return $this->orderModel->getSalesSummary();
    }

    public function getBestSellers($limit = 5)
    {
        return $this->orderModel->getBestSellers($limit);
    }
}
