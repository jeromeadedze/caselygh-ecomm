<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * CustomerClass (Model)
 * All database work for the `customer` table (shoppers and the Casely team).
 */
class CustomerClass extends Database
{
    public function emailExists($email)
    {
        $row = $this->fetchOne("SELECT customer_id FROM customer WHERE customer_email = ?", [$email]);
        return $row ? true : false;
    }

    // Register a new customer. The password is hashed before it is stored.
    public function addCustomer($name, $email, $pass, $contact, $city, $role = 2)
    {
        $sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_contact, customer_city, user_role)
                VALUES (?, ?, ?, ?, ?, ?)";
        $ok = $this->execute($sql, [$name, $email, password_hash($pass, PASSWORD_BCRYPT), $contact, $city, $role]);
        return $ok ? (int) $this->getLastInsertId() : false;
    }

    // Includes the password hash: only used by login.
    public function getCustomerByEmail($email)
    {
        return $this->fetchOne("SELECT * FROM customer WHERE customer_email = ?", [$email]);
    }

    // Never returns the password hash.
    public function getCustomerById($id)
    {
        $sql = "SELECT customer_id, customer_name, customer_email, customer_contact, customer_city, user_role, created_at
                FROM customer WHERE customer_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function getAllCustomers()
    {
        $sql = "SELECT customer_id, customer_name, customer_email, customer_contact, customer_city, user_role, created_at
                FROM customer ORDER BY customer_id DESC";
        return $this->fetchAll($sql);
    }

    public function countCustomers()
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS n FROM customer WHERE user_role = 2");
        return (int) $row['n'];
    }
}
