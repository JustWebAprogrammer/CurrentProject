<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database
{
    private $host = 'localhost';
    private $db_name = 'SiteDonaXinga';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=5396;dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>