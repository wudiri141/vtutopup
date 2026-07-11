<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class Validator
{
    public static function validateReservation(array $input, array $allowedDates): array
    {
        $errors = [];

        $fullName = trim((string) ($input['full_name'] ?? ''));
        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be valid.';
        }

        $phone = trim((string) ($input['phone'] ?? ''));
        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        }

        $eventDate = trim((string) ($input['event_date'] ?? ''));
        if ($eventDate === '') {
            $errors['event_date'] = 'Event date is required.';
        } elseif (!self::isValidDate($eventDate)) {
            $errors['event_date'] = 'Event date must be in YYYY-MM-DD format.';
        } elseif (!in_array($eventDate, $allowedDates, true)) {
            $errors['event_date'] = 'Event date must exist in the configured event dates.';
        }

        return $errors;
    }

    private static function isValidDate(string $date): bool
    {
        $created = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($created === false) {
            return false;
        }

        return $created->format('Y-m-d') === $date;
    }
}
