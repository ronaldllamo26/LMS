-- ═══════════════════════════════════════════════════════════════════
-- QueueSense Database Schema
-- Version: 1.0.0
-- Encoding: utf8mb4
-- ═══════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS queuesense_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE queuesense_db;

-- ─── USERS ───────────────────────────────────────────────────────────────────
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id    VARCHAR(20) NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    role          ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
    department    VARCHAR(100) DEFAULT NULL,
    qr_token      VARCHAR(64) DEFAULT NULL UNIQUE,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    avatar        VARCHAR(255) DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── QUEUE TYPES ─────────────────────────────────────────────────────────────
CREATE TABLE queue_types (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    description      TEXT DEFAULT NULL,
    prefix           VARCHAR(5) NOT NULL,
    icon             VARCHAR(50) DEFAULT 'bi-person-lines-fill',
    color            VARCHAR(20) DEFAULT '#1e40af',
    daily_limit      INT NOT NULL DEFAULT 150,
    avg_service_time INT NOT NULL DEFAULT 5,
    is_open          TINYINT(1) NOT NULL DEFAULT 1,
    open_time        TIME NOT NULL DEFAULT '08:00:00',
    close_time       TIME NOT NULL DEFAULT '17:00:00',
    sort_order       INT NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SERVICE WINDOWS ─────────────────────────────────────────────────────────
CREATE TABLE service_windows (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue_type_id INT UNSIGNED NOT NULL,
    window_label  VARCHAR(50) NOT NULL,
    staff_id      INT UNSIGNED DEFAULT NULL,
    status        ENUM('open','closed','break') NOT NULL DEFAULT 'closed',
    FOREIGN KEY (queue_type_id) REFERENCES queue_types(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id)      REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_queue_type (queue_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── QUEUE ENTRIES ────────────────────────────────────────────────────────────
CREATE TABLE queue_entries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    queue_type_id INT UNSIGNED NOT NULL,
    window_id     INT UNSIGNED DEFAULT NULL,
    ticket_number VARCHAR(20) NOT NULL,
    position      INT NOT NULL,
    status        ENUM('waiting','serving','done','cancelled','no_show') NOT NULL DEFAULT 'waiting',
    priority      TINYINT(1) NOT NULL DEFAULT 0,
    joined_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    called_at     DATETIME DEFAULT NULL,
    served_at     DATETIME DEFAULT NULL,
    wait_minutes  INT DEFAULT NULL,
    call_count    INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id)       REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (queue_type_id) REFERENCES queue_types(id) ON DELETE CASCADE,
    FOREIGN KEY (window_id)     REFERENCES service_windows(id) ON DELETE SET NULL,
    INDEX idx_status      (status),
    INDEX idx_date_queue  (queue_type_id, joined_at),
    INDEX idx_user_active (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ANALYTICS LOG ───────────────────────────────────────────────────────────
CREATE TABLE analytics_log (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue_type_id INT UNSIGNED NOT NULL,
    log_date      DATE NOT NULL,
    hour_slot     TINYINT UNSIGNED NOT NULL,
    total_served  INT NOT NULL DEFAULT 0,
    total_waiting INT NOT NULL DEFAULT 0,
    avg_wait_time DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    peak_flag     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (queue_type_id) REFERENCES queue_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slot (queue_type_id, log_date, hour_slot),
    INDEX idx_log_date (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── NOTIFICATIONS ───────────────────────────────────────────────────────────
CREATE TABLE notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    message    TEXT NOT NULL,
    type       ENUM('info','warning','success','call') NOT NULL DEFAULT 'info',
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SYSTEM LOGS ─────────────────────────────────────────────────────────────
CREATE TABLE system_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id   INT UNSIGNED DEFAULT NULL,
    action     VARCHAR(100) NOT NULL,
    details    TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    logged_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action    (action),
    INDEX idx_logged_at (logged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════════════════════════════

-- ─── Admin Account ───────────────────────────────────────────────────────────
-- Password: Admin@1234 (bcrypt hash)
INSERT INTO users (student_id, full_name, email, password_hash, role, department) VALUES
('ADMIN-001', 'System Administrator', 'admin@queuesense.edu.ph',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT Department');

-- ─── Staff Accounts ──────────────────────────────────────────────────────────
-- Password: Staff@1234 (same hash for demo)
INSERT INTO users (student_id, full_name, email, password_hash, role, department) VALUES
('STAFF-001', 'Maria Santos',   'registrar@queuesense.edu.ph',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Registrar Office'),
('STAFF-002', 'Juan Reyes',     'cashier@queuesense.edu.ph',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Accounting Office'),
('STAFF-003', 'Ana Cruz',       'guidance@queuesense.edu.ph',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Guidance Office'),
('STAFF-004', 'Carlos Mendoza', 'library@queuesense.edu.ph',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Library');

-- ─── Student Accounts ────────────────────────────────────────────────────────
INSERT INTO users (student_id, full_name, email, role, department, qr_token) VALUES
('2021-00001', 'Alejandro Garcia',    'garcia@student.edu.ph',   'student', 'BSCS', MD5('2021-00001-token')),
('2021-00002', 'Beatriz Dela Cruz',   'delacruz@student.edu.ph', 'student', 'BSIT', MD5('2021-00002-token')),
('2021-00003', 'Cedric Lim',          'lim@student.edu.ph',      'student', 'BSCS', MD5('2021-00003-token')),
('2021-00004', 'Diana Macaraeg',      'macaraeg@student.edu.ph', 'student', 'BSBA', MD5('2021-00004-token')),
('2021-00005', 'Eduardo Ramos',       'ramos@student.edu.ph',    'student', 'BSCE', MD5('2021-00005-token')),
('2021-00006', 'Filipina Torres',     'torres@student.edu.ph',   'student', 'BSIT', MD5('2021-00006-token')),
('2021-00007', 'Gilbert Navarro',     'navarro@student.edu.ph',  'student', 'BSCS', MD5('2021-00007-token')),
('2021-00008', 'Helena Aquino',       'aquino@student.edu.ph',   'student', 'BSBA', MD5('2021-00008-token')),
('2021-00009', 'Ivan Castillo',       'castillo@student.edu.ph', 'student', 'BSCE', MD5('2021-00009-token')),
('2021-00010', 'Josephine Morales',   'morales@student.edu.ph',  'student', 'BSIT', MD5('2021-00010-token')),
('2022-00001', 'Kevin Sta. Maria',    'stamaria@student.edu.ph', 'student', 'BSCS', MD5('2022-00001-token')),
('2022-00002', 'Lorraine Vidal',      'vidal@student.edu.ph',    'student', 'BSBA', MD5('2022-00002-token')),
('2022-00003', 'Manuel Ocampo',       'ocampo@student.edu.ph',   'student', 'BSCE', MD5('2022-00003-token')),
('2022-00004', 'Nadine Pascual',      'pascual@student.edu.ph',  'student', 'BSIT', MD5('2022-00004-token')),
('2022-00005', 'Oscar Fernandez',     'fernandez@student.edu.ph','student', 'BSCS', MD5('2022-00005-token'));

-- ─── Queue Types ─────────────────────────────────────────────────────────────
INSERT INTO queue_types (name, description, prefix, icon, color, daily_limit, avg_service_time, sort_order) VALUES
('Registrar',       'Enrollment, transcript requests, clearance, and official documents', 'R', 'bi-file-earmark-text', '#1e40af', 150, 6, 1),
('Cashier',         'Tuition payment, official receipts, and financial transactions',     'C', 'bi-cash-stack',        '#059669', 120, 4, 2),
('Guidance Office', 'Counseling sessions, student concerns, and mental health services',  'G', 'bi-heart-pulse',       '#7c3aed', 60,  15, 3),
('Library',         'Book borrowing, return, and research assistance services',           'L', 'bi-book',             '#b45309', 100, 5, 4);

-- ─── Service Windows ─────────────────────────────────────────────────────────
INSERT INTO service_windows (queue_type_id, window_label, staff_id, status) VALUES
(1, 'Window R-1 (Enrollment)',  2, 'open'),
(1, 'Window R-2 (Documents)',   NULL, 'closed'),
(2, 'Window C-1 (Payments)',    3, 'open'),
(2, 'Window C-2 (Receipts)',    NULL, 'closed'),
(3, 'Counseling Room 1',        4, 'open'),
(4, 'Library Desk',             5, 'open');

-- ─── Analytics Seed Data (14 days, realistic peak hours) ─────────────────────
-- Simulates Mon-Fri office usage patterns:
-- Peaks at 8-10AM (morning rush) and 1-3PM (after lunch)
-- Quiet hours: 11AM, 12PM (lunch), 4-5PM (winding down)

INSERT INTO analytics_log (queue_type_id, log_date, hour_slot, total_served, avg_wait_time, peak_flag) VALUES
-- Queue 1 (Registrar) — Last 14 weekdays
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  8, 18, 12.5, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  9, 22, 14.2, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 10, 15,  9.8, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 11,  9,  6.4, 0),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 13, 19, 13.1, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 14, 21, 15.6, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 15, 11,  7.2, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 16,  6,  4.1, 0),

(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY),  8, 20, 13.8, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY),  9, 25, 16.1, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 10, 14,  9.2, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 11,  7,  5.5, 0),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 13, 20, 14.0, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 14, 18, 12.4, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 15, 10,  7.0, 0),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 16,  5,  3.5, 0),

(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  8, 16, 11.0, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  9, 19, 13.5, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 10, 12,  8.1, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 13, 17, 11.9, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 14, 20, 14.8, 1),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 15,  9,  6.5, 0),

-- Queue 2 (Cashier)
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  8, 14,  8.2, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  9, 20, 11.5, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 10, 16,  9.0, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 13, 18, 10.2, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 14, 22, 12.8, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 15, 12,  7.1, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 16,  5,  3.0, 0),

(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  8, 15,  8.8, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  9, 22, 12.4, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 10, 14,  8.5, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 13, 20, 11.5, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 14, 18, 10.0, 1),
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 15, 10,  6.0, 0),

-- Queue 3 (Guidance)
(3, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  9,  4, 18.0, 0),
(3, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 10,  5, 20.5, 0),
(3, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 13,  4, 17.2, 0),
(3, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 14,  3, 15.0, 0),

(3, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  9,  5, 19.0, 0),
(3, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 10,  6, 22.0, 0),
(3, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 13,  4, 16.5, 0),
(3, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 14,  3, 14.0, 0),

-- Queue 4 (Library)
(4, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  8, 10,  4.5, 0),
(4, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  9, 14,  6.2, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 10, 12,  5.4, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 13, 15,  6.8, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 14, 11,  5.0, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 15,  7,  3.2, 0),

(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  8, 11,  4.8, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY),  9, 13,  5.9, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 10, 12,  5.5, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 13, 16,  7.0, 1),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 14, 10,  4.6, 0),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 15,  6,  2.9, 0);

-- ─── Sample Queue Entries (Today — for live demo) ─────────────────────────────
INSERT INTO queue_entries (user_id, queue_type_id, window_id, ticket_number, position, status, joined_at, called_at, served_at, wait_minutes) VALUES
(6,  1, 1, 'R-001', 1, 'done',    NOW() - INTERVAL 90 MINUTE, NOW() - INTERVAL 85 MINUTE, NOW() - INTERVAL 79 MINUTE, 6),
(7,  1, 1, 'R-002', 2, 'done',    NOW() - INTERVAL 83 MINUTE, NOW() - INTERVAL 78 MINUTE, NOW() - INTERVAL 72 MINUTE, 6),
(8,  1, 1, 'R-003', 3, 'done',    NOW() - INTERVAL 75 MINUTE, NOW() - INTERVAL 71 MINUTE, NOW() - INTERVAL 65 MINUTE, 6),
(9,  1, 1, 'R-004', 4, 'done',    NOW() - INTERVAL 68 MINUTE, NOW() - INTERVAL 64 MINUTE, NOW() - INTERVAL 57 MINUTE, 7),
(10, 1, 1, 'R-005', 5, 'done',    NOW() - INTERVAL 60 MINUTE, NOW() - INTERVAL 56 MINUTE, NOW() - INTERVAL 50 MINUTE, 6),
(11, 1, 1, 'R-006', 6, 'done',    NOW() - INTERVAL 52 MINUTE, NOW() - INTERVAL 48 MINUTE, NOW() - INTERVAL 42 MINUTE, 6),
(12, 1, 1, 'R-007', 7, 'serving', NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 5 MINUTE,  NULL,                       NULL),
(13, 1, NULL,'R-008',8, 'waiting', NOW() - INTERVAL 20 MINUTE, NULL, NULL, NULL),
(14, 1, NULL,'R-009',9, 'waiting', NOW() - INTERVAL 15 MINUTE, NULL, NULL, NULL),
(15, 1, NULL,'R-010',10,'waiting', NOW() - INTERVAL 10 MINUTE, NULL, NULL, NULL),

(6,  2, 3, 'C-001', 1, 'done',    NOW() - INTERVAL 70 MINUTE, NOW() - INTERVAL 67 MINUTE, NOW() - INTERVAL 63 MINUTE, 4),
(7,  2, 3, 'C-002', 2, 'done',    NOW() - INTERVAL 62 MINUTE, NOW() - INTERVAL 60 MINUTE, NOW() - INTERVAL 56 MINUTE, 4),
(8,  2, 3, 'C-003', 3, 'serving', NOW() - INTERVAL 20 MINUTE, NOW() - INTERVAL 4 MINUTE,  NULL,                       NULL),
(9,  2, NULL,'C-004',4, 'waiting', NOW() - INTERVAL 12 MINUTE, NULL, NULL, NULL),
(10, 2, NULL,'C-005',5, 'waiting', NOW() - INTERVAL 8 MINUTE,  NULL, NULL, NULL);
