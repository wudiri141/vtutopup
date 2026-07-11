<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class ReservationIdGenerator
{
    public static function generate(string $eventDate, int $sequence): string
    {
        $year = (new DateTimeImmutable($eventDate))->format('Y');

        return sprintf('RES-%s-%04d', $year, $sequence);
    }
}
