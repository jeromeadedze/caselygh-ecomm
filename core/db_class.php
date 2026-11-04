<?php

// Pull in the connection settings (DB_HOST, DB_USER, DB_PASS, DB_NAME)
require_once __DIR__ . "/db_cred.php";

// Database is the base class every model in classes/ extends.
// It opens one PDO connection and offers safe query helpers that
// always use prepared statements (protection against SQL injection).
class Database
{
    // One shared connection for the whole request, so creating several
    // models on one page does not open several connections.
    private static $sharedConn = null;
    private $conn;

    public function __construct()
    {
        if (self::$sharedConn === null) {
            try {
                self::$sharedConn = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS
                );
                self::$sharedConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$sharedConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                // Log the real reason; show visitors a generic message.
                error_log("Database connection failed: " . $e->getMessage());
                http_response_code(500);
                die("Sorry, the store is unavailable right now. Please try again shortly.");
            }
        }
        $this->conn = self::$sharedConn;
    }

    // SELECT that returns many rows
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SELECT that returns one row (or false)
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // INSERT / UPDATE / DELETE
    public function execute($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Number of rows changed by an UPDATE / DELETE
    public function executeCount($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    // Transactions: used when several writes must all succeed or all fail
    // (e.g. creating an order and its items).
    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        if ($this->conn->inTransaction()) {
            return $this->conn->rollBack();
        }
        return false;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
