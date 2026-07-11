<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

final class ReservationRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(array $reservation): int
    {
        $statement = $this->connection->pdo()->prepare(
            'INSERT INTO reservations
                (reservation_id, full_name, email, phone, event_date, event_type, message, created_at)
             VALUES
                (:reservation_id, :full_name, :email, :phone, :event_date, :event_type, :message, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'reservation_id' => $reservation['reservation_id'] ?? null,
            'full_name' => $reservation['full_name'],
            'email' => $reservation['email'],
            'phone' => $reservation['phone'],
            'event_date' => $reservation['event_date'],
            'event_type' => $reservation['event_type'] ?? null,
            'message' => $reservation['message'] ?? null,
        ]);

        return (int) $this->connection->pdo()->lastInsertId();
    }

    public function updateReservationId(int $id, string $reservationId): void
    {
        $statement = $this->connection->pdo()->prepare(
            'UPDATE reservations SET reservation_id = :reservation_id WHERE id = :id'
        );

        $statement->execute([
            'reservation_id' => $reservationId,
            'id' => $id,
        ]);
    }
}
