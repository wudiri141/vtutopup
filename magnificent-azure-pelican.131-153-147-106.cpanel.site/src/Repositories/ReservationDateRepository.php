<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class ReservationDateRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function all(): array
    {
        $statement = $this->connection->pdo()->query(
            'SELECT date, total_slots, remaining_slots FROM reservation_dates ORDER BY date ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDate(string $date): ?array
    {
        $statement = $this->connection->pdo()->prepare(
            'SELECT date, total_slots, remaining_slots FROM reservation_dates WHERE date = :date LIMIT 1'
        );
        $statement->execute(['date' => $date]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function decrementRemainingSlots(string $date): bool
    {
        $statement = $this->connection->pdo()->prepare(
            'UPDATE reservation_dates
             SET remaining_slots = remaining_slots - 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE date = :date AND remaining_slots > 0'
        );

        $statement->execute(['date' => $date]);

        return $statement->rowCount() === 1;
    }
}
