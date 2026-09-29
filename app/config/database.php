<?php
class Database
{
    public function getConnection(): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'business_management_system';
        $user = getenv('DB_USER') ?: 'bms';
        $password = getenv('DB_PASSWORD') ?: '';

        return new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
