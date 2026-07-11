<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use App\Exceptions\ReservationException;
use App\Repositories\ReservationDateRepository;
use App\Repositories\ReservationRepository;
use App\Support\Logger;
use App\Support\ReservationIdGenerator;
use App\Support\Validator;
use Throwable;

final class ReservationService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ReservationDateRepository $dateRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly MailerService $mailerService,
        private readonly array $allowedDates,
        private readonly int $defaultTotalSlots = 10
    ) {
    }

    public function availability(): array
    {
        $rows = [];
        foreach ($this->dateRepository->all() as $row) {
            $rows[(string) $row['date']] = $row;
        }

        $dates = [];
        foreach ($this->allowedDates as $date) {
            $row = $rows[$date] ?? [
                'date' => $date,
                'total_slots' => $this->defaultTotalSlots,
                'remaining_slots' => 0,
            ];

            $remainingSlots = (int) $row['remaining_slots'];
            $totalSlots = (int) $row['total_slots'];

            $dates[] = [
                'date' => $date,
                'total_slots' => $totalSlots > 0 ? $totalSlots : $this->defaultTotalSlots,
                'remaining_slots' => max(0, $remainingSlots),
                'is_available' => $remainingSlots > 0,
            ];
        }

        return [
            'success' => true,
            'dates' => $dates,
        ];
    }

    public function create(array $input): array
    {
        $validationErrors = Validator::validateReservation($input, $this->allowedDates);
        if ($validationErrors !== []) {
            return [
                'status' => 422,
                'payload' => [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationErrors,
                ],
            ];
        }

        $reservation = null;
        $reservationId = null;

        try {
            $reservation = $this->connection->transaction(function () use ($input, &$reservationId): array {
                $eventDate = (string) $input['event_date'];

                $dateRow = $this->dateRepository->findByDate($eventDate);
                if ($dateRow === null) {
                    throw new ReservationException('Selected event date is invalid', 422);
                }

                if (!$this->dateRepository->decrementRemainingSlots($eventDate)) {
                    throw new ReservationException('No slots available for selected date', 409);
                }

                $insertedId = $this->reservationRepository->create([
                    'reservation_id' => null,
                    'full_name' => $input['full_name'],
                    'email' => $input['email'],
                    'phone' => $input['phone'],
                    'event_date' => $eventDate,
                    'event_type' => $input['event_type'] ?? null,
                    'message' => $input['message'] ?? null,
                ]);

                $reservationId = ReservationIdGenerator::generate($eventDate, $insertedId);
                $this->reservationRepository->updateReservationId($insertedId, $reservationId);

                return [
                    'status' => 201,
                    'payload' => [
                        'success' => true,
                        'message' => 'Reservation submitted successfully',
                        'reservation_id' => $reservationId,
                    ],
                ];
            });
        } catch (Throwable $throwable) {
            if ($throwable instanceof ReservationException) {
                return [
                    'status' => $throwable->statusCode(),
                    'payload' => [
                        'success' => false,
                        'message' => $throwable->getMessage(),
                    ],
                ];
            }

            Logger::error('Reservation transaction failed: ' . $throwable->getMessage());

            return [
                'status' => 500,
                'payload' => [
                    'success' => false,
                    'message' => 'Unable to create reservation',
                ],
            ];
        }

        if (($reservation['status'] ?? 500) !== 201) {
            return $reservation;
        }

        $record = [
            'reservation_id' => $reservation['payload']['reservation_id'],
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'event_date' => $input['event_date'],
            'event_type' => $input['event_type'] ?? '',
        ];

        try {
            $this->mailerService->sendCustomerConfirmation($record);
            $this->mailerService->sendOrganizerNotification($record);
        } catch (Throwable $throwable) {
            Logger::error('Reservation email delivery failed: ' . $throwable->getMessage());
        }

        return $reservation;
    }
}
