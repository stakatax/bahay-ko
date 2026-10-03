<?php
class BaseModel
{

    protected $conn;

    public function __construct(?mysqli $connection = null)
    {

        if ($connection !== null) {
            $this->conn = $connection;
            return;
        }
        require __DIR__ . '/../../config/dbconnect.php';
        $this->conn = $conn;
    }

    public function getDatabaseConnection(): mysqli
    {
        return $this->conn;
    }

    protected function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    protected function getConnection()
    {
        return $this->conn;
    }
}
