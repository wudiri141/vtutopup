<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;
use Throwable;

final class Connection
{
    private PDO $pdo;
    private string $driver;

    public function __construct(array $config)
    {
        $this->driver = (string) ($config['driver'] ?? '');

        try {
            $this->pdo = new PDO(
                $config['dsn'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Throwable $throwable) {
            throw new RuntimeException('Database connection failed: ' . $throwable->getMessage(), 0, $throwable);
        }

        if ($this->driver === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @template T
     * @param callable(PDO):T $callback
     * @return T
     */
    public function transaction(callable $callback)
    {
        if ($this->driver === 'sqlite') {
            $this->pdo->exec('BEGIN IMMEDIATE TRANSACTION');
        } else {
            if (!$this->pdo->beginTransaction()) {
                throw new RuntimeException('Unable to begin database transaction.');
            }
        }

        try {
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }
}
