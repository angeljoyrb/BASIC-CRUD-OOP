<?php
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "AJRB128@ajrb";
    private $dbname = "oop_user_db";
    public $conn;

    // Establish database connection
    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        //Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    // Get the database connection
    public function getConnection() {
        return $this->conn;
    }
}
?>