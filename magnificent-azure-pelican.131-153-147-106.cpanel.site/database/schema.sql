CREATE TABLE IF NOT EXISTS reservation_dates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL UNIQUE,
    total_slots INTEGER NOT NULL DEFAULT 10,
    remaining_slots INTEGER NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reservation_id VARCHAR(32) UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    event_type VARCHAR(150) NULL,
    message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_date) REFERENCES reservation_dates(date)
);

CREATE INDEX IF NOT EXISTS idx_reservations_event_date ON reservations (event_date);
CREATE INDEX IF NOT EXISTS idx_reservation_dates_date ON reservation_dates (date);
