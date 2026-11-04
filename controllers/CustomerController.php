<?php

require_once __DIR__ . "/../classes/CustomerClass.php";

/**
 * CustomerController
 * Registration and login rules sit here; the model only talks to the database.
 */
class CustomerController
{
    private $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerClass();
    }

    /**
     * Validate and register a customer.
     * Returns ['success' => true, 'user' => [...]] or ['success' => false, 'error' => '...'].
     */
    public function register($name, $email, $pass, $confirm, $contact, $city)
    {
        if ($name === '' || $email === '' || $pass === '' || $contact === '' || $city === '') {
            return ['success' => false, 'error' => 'Fill in every field.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Enter a valid email address.'];
        }
        if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
            return ['success' => false, 'error' => 'Your password needs at least 8 characters, including a letter and a number.'];
        }
        if ($pass !== $confirm) {
            return ['success' => false, 'error' => 'The two passwords do not match.'];
        }
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $contact))) {
            return ['success' => false, 'error' => 'Enter a valid phone number, e.g. 024 123 4567.'];
        }
        if ($this->customerModel->emailExists($email)) {
            return ['success' => false, 'error' => 'An account with this email already exists. Try logging in.'];
        }

        $id = $this->customerModel->addCustomer($name, $email, $pass, $contact, $city);
        if (!$id) {
            return ['success' => false, 'error' => 'We could not create your account. Try again.'];
        }
        return ['success' => true, 'user' => $this->customerModel->getCustomerById($id)];
    }

    // Brute-force protection: 5 failed tries locks login for 10 minutes (per session).
    public function login($email, $pass)
    {
        $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'since' => time()];
        if ($attempts['count'] >= 5 && time() - $attempts['since'] < 600) {
            return ['success' => false, 'error' => 'Too many failed attempts. Wait 10 minutes and try again.'];
        }
        if (time() - $attempts['since'] >= 600) {
            $attempts = ['count' => 0, 'since' => time()];
        }

        $user = $this->customerModel->getCustomerByEmail($email);
        if (!$user || !password_verify($pass, $user['customer_pass'])) {
            $attempts['count']++;
            $_SESSION['login_attempts'] = $attempts;
            return ['success' => false, 'error' => 'Incorrect email or password. Check them and try again.'];
        }

        unset($_SESSION['login_attempts']);
        unset($user['customer_pass']);
        return ['success' => true, 'user' => $user];
    }

    public function getCustomerById($id)
    {
        return $this->customerModel->getCustomerById($id);
    }

    public function getAllCustomers()
    {
        return $this->customerModel->getAllCustomers();
    }

    public function countCustomers()
    {
        return $this->customerModel->countCustomers();
    }
}
