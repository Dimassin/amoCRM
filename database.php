<?php
class Db {
    private static Db|null $instance = null;
    private PDO $connection;

    private function __construct() {
        $this->connection = new PDO(
            "mysql:host=localhost;dbname=cz28393_amocrm",
            "cz28393_amocrm",
            "Mck66XwF"
        );
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance(): Db|null {
        if (!self::$instance) {
            self::$instance = new Db();
        }
        return self::$instance;
    }

    public function connect(): PDO {
        return $this->connection;
    }
}