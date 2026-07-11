<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

use App\Database\Connection;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Repositories\ReservationDateRepository;
use App\Repositories\ReservationRepository;
use App\Services\MailerService;
use App\Services\ReservationService;
use App\Support\Environment;

$config = require __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $appConfig = $config['app'];
    $databaseConfig = $config['database'];
    $mailConfig = $config['mail'];
    $eventDates = $config['event_dates'];

    $connection = new Connection($databaseConfig);
    $dateRepository = new ReservationDateRepository($connection);
    $reservationRepository = new ReservationRepository($connection);
    $mailerService = new MailerService($mailConfig);
    $reservationService = new ReservationService(
        $connection,
        $dateRepository,
        $reservationRepository,
        $mailerService,
        $eventDates,
        (int) $appConfig['default_total_slots']
    );

    $request = Request::fromGlobals();
    $method = $request->method();
    $path = $request->path();

    if ($method === 'GET' && $path === '/api/reservations/availability') {
        JsonResponse::send(200, $reservationService->availability());
    }

    if ($method === 'POST' && $path === '/api/reservations') {
        $response = $reservationService->create($request->json());
        JsonResponse::send($response['status'], $response['payload']);
    }

    JsonResponse::send(404, [
        'success' => false,
        'message' => 'Route not found',
    ]);
} catch (Throwable $throwable) {
    $debug = filter_var(Environment::get('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL);

    JsonResponse::send(500, [
        'success' => false,
        'message' => 'Internal server error',
        'error' => $debug ? $throwable->getMessage() : null,
    ]);
}
