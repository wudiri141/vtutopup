# Event Reservation System Backend

PHP backend API for managing reservations across four predefined event dates.

## Endpoints

- `GET /api/reservations/availability`
- `POST /api/reservations`

## Features

- Tracks slot availability per date.
- Prevents overbooking with a transactional slot decrement.
- Generates unique reservation IDs.
- Sends confirmation and organizer emails through PHPMailer.

## Setup

1. Install dependencies:

```bash
composer install
```

2. Copy the environment template:

```bash
cp .env.example .env
```

3. Create the database and seed data:

```sql
-- Run database/schema.sql
-- Run database/seed.sql
```

Or, for the default SQLite setup:

```bash
php bin/setup-database.php
```

4. Start the application with a PHP web server:

```bash
php -S localhost:8000 -t public
```

## Notes

- The default database configuration uses SQLite so the project can run locally without a separate database server.
- The four initial dates are:
  - `2026-07-01`
  - `2026-07-02`
  - `2026-07-03`
  - `2026-07-04`
- If email delivery fails, the backend logs the error and still returns the reservation result after persistence.
