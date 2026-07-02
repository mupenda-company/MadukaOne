<?php

declare(strict_types=1);

final class Database
{
    private static ?self $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);

        $this->disableStockUpdate();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function connection(): PDO
    {
        return self::getInstance()->pdo;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function enableStockUpdate(): void
    {
        $this->pdo->exec('SET @allow_stock_update = 1');
    }

    public function disableStockUpdate(): void
    {
        $this->pdo->exec('SET @allow_stock_update = 0');
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this->pdo, $this);
            $this->disableStockUpdate();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->disableStockUpdate();

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize database singleton.');
    }
}
