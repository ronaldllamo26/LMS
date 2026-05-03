<?php
/**
 * QueueSense — Helper Functions
 * Reusable utility functions used across the entire system.
 */

require_once __DIR__ . '/../config.php';

// ─── Auth Helpers ─────────────────────────────────────────────────────────────

/**
 * Returns the currently logged-in user's data from session.
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Returns true if a user is logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user']);
}

/**
 * Returns true if the current user has the specified role.
 */
function has_role(string $role): bool {
    $user = current_user();
    return $user && $user['role'] === $role;
}

/**
 * Redirects to login if the user is not authenticated.
 * Optionally checks for a required role.
 */
function require_auth(?string $role = null): void {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/modules/auth/login.php');
    }
    if ($role !== null && !has_role($role)) {
        redirect(BASE_URL . '/modules/auth/login.php?error=unauthorized');
    }
}

// ─── Redirect ─────────────────────────────────────────────────────────────────

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

// ─── Ticket Helpers ───────────────────────────────────────────────────────────

/**
 * Generates the next ticket number for a queue type.
 * Format: PREFIX-001, PREFIX-002, etc. — resets daily.
 *
 * @param int    $queue_type_id  The queue type ID
 * @param string $prefix         The ticket prefix (e.g., "R", "C")
 * @return string                e.g., "R-042"
 */
function generate_ticket_number(int $queue_type_id, string $prefix): string {
    $db  = db_connect();
    $sql = "SELECT COUNT(*) as total
            FROM queue_entries
            WHERE queue_type_id = ?
              AND DATE(joined_at) = CURDATE()";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $queue_type_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $next_number = ($result['total'] ?? 0) + 1;
    return strtoupper($prefix) . '-' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
}

// ─── Time & Formatting ────────────────────────────────────────────────────────

/**
 * Formats minutes into a human-readable string.
 * e.g., 75 → "1 hr 15 mins", 5 → "5 mins"
 */
function format_wait_time(int $minutes): string {
    if ($minutes <= 0) return 'Less than a minute';
    if ($minutes < 60) return "{$minutes} min" . ($minutes > 1 ? 's' : '');
    $hours   = intdiv($minutes, 60);
    $mins    = $minutes % 60;
    $result  = "{$hours} hr" . ($hours > 1 ? 's' : '');
    if ($mins > 0) $result .= " {$mins} min" . ($mins > 1 ? 's' : '');
    return $result;
}

/**
 * Returns a human-friendly time label for an hour slot.
 * e.g., 8 → "8:00 AM", 13 → "1:00 PM"
 */
function format_hour_slot(int $hour): string {
    return date('g:00 A', mktime($hour, 0, 0));
}

/**
 * Returns "Today", "Yesterday", or a formatted date string.
 */
function friendly_date(string $datetime): string {
    $date      = date('Y-m-d', strtotime($datetime));
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($date === $today)     return 'Today, '     . date('g:i A', strtotime($datetime));
    if ($date === $yesterday) return 'Yesterday, ' . date('g:i A', strtotime($datetime));
    return date('M d, Y g:i A', strtotime($datetime));
}

// ─── Wait Time Prediction (AI Core) ──────────────────────────────────────────

/**
 * Predicts estimated waiting time for a student in queue.
 *
 * Algorithm:
 *   1. Count how many people are ahead (status = 'waiting' with priority sort)
 *   2. Get rolling average service time from last 20 completed entries
 *      for this queue_type on the same day-of-week
 *   3. Multiply and return
 *
 * @param int $queue_type_id
 * @param int $user_position  The user's current position number
 * @return array ['minutes' => int, 'label' => string, 'people_ahead' => int]
 */
function predict_wait_time(int $queue_type_id, int $user_position): array {
    $db = db_connect();

    // Step 1: Count people currently ahead (waiting or serving)
    $sql_ahead = "SELECT COUNT(*) as ahead
                  FROM queue_entries
                  WHERE queue_type_id = ?
                    AND status IN ('waiting','serving')
                    AND DATE(joined_at) = CURDATE()
                    AND position < ?";

    $stmt = $db->prepare($sql_ahead);
    $stmt->bind_param('ii', $queue_type_id, $user_position);
    $stmt->execute();
    $people_ahead = (int)($stmt->get_result()->fetch_assoc()['ahead'] ?? 0);
    $stmt->close();

    // Step 2: Rolling average service time from last 20 completed entries
    //         on the same day of week (to account for Mon=slow, Fri=busy etc.)
    $day_of_week = date('N'); // 1=Monday ... 7=Sunday
    $sql_avg = "SELECT AVG(wait_minutes) as avg_wait
                FROM (
                    SELECT wait_minutes
                    FROM queue_entries
                    WHERE queue_type_id = ?
                      AND status = 'done'
                      AND wait_minutes IS NOT NULL
                      AND DAYOFWEEK(joined_at) = DAYOFWEEK(NOW())
                    ORDER BY joined_at DESC
                    LIMIT 20
                ) as recent";

    $stmt = $db->prepare($sql_avg);
    $stmt->bind_param('i', $queue_type_id);
    $stmt->execute();
    $avg_result  = $stmt->get_result()->fetch_assoc();
    $avg_service = round((float)($avg_result['avg_wait'] ?? 5)); // fallback: 5 mins
    $stmt->close();

    // Step 3: Calculate estimate
    $estimated_minutes = max(1, $people_ahead * $avg_service);

    return [
        'minutes'      => $estimated_minutes,
        'label'        => format_wait_time($estimated_minutes),
        'people_ahead' => $people_ahead,
        'avg_service'  => $avg_service,
    ];
}

// ─── Best Time Recommendation ─────────────────────────────────────────────────

/**
 * Returns the 3 best hours to visit a queue based on 30-day history.
 *
 * @param int $queue_type_id
 * @return array  e.g., [['hour' => 10, 'label' => '10:00 AM', 'avg_wait' => 3.5], ...]
 */
function get_best_visit_times(int $queue_type_id): array {
    $db  = db_connect();
    $sql = "SELECT hour_slot,
                   AVG(avg_wait_time) AS avg_wait,
                   AVG(total_served)  AS avg_volume
            FROM analytics_log
            WHERE queue_type_id = ?
              AND log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              AND DAYOFWEEK(log_date) = DAYOFWEEK(NOW())
              AND hour_slot BETWEEN 7 AND 17
            GROUP BY hour_slot
            HAVING avg_volume > 0
            ORDER BY avg_wait ASC
            LIMIT 3";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $queue_type_id);
    $stmt->execute();
    $rows   = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn($row) => [
        'hour'     => (int)$row['hour_slot'],
        'label'    => format_hour_slot((int)$row['hour_slot']),
        'avg_wait' => round((float)$row['avg_wait'], 1),
    ], $rows);
}

// ─── Analytics Logger ─────────────────────────────────────────────────────────

/**
 * Updates the analytics_log table when a ticket is completed.
 * Called every time a staff member marks a ticket as "done".
 *
 * @param int $queue_type_id
 * @param int $wait_minutes   Actual wait time of the completed ticket
 */
function update_analytics_log(int $queue_type_id, int $wait_minutes): void {
    $db        = db_connect();
    $hour_slot = (int)date('G'); // 0-23
    $log_date  = date('Y-m-d');

    // Get current stats for this slot
    $sql_select = "SELECT id, total_served, avg_wait_time
                   FROM analytics_log
                   WHERE queue_type_id = ? AND log_date = ? AND hour_slot = ?";

    $stmt = $db->prepare($sql_select);
    $stmt->bind_param('isi', $queue_type_id, $log_date, $hour_slot);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Update: recalculate running average
        $new_total    = $existing['total_served'] + 1;
        $new_avg      = (($existing['avg_wait_time'] * $existing['total_served']) + $wait_minutes) / $new_total;
        $is_peak      = $new_total >= PEAK_THRESHOLD ? 1 : 0;

        $sql_update = "UPDATE analytics_log
                       SET total_served = ?, avg_wait_time = ?, peak_flag = ?
                       WHERE id = ?";
        $stmt = $db->prepare($sql_update);
        $stmt->bind_param('idii', $new_total, $new_avg, $is_peak, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert: first entry for this slot today
        $sql_insert = "INSERT INTO analytics_log
                       (queue_type_id, log_date, hour_slot, total_served, avg_wait_time)
                       VALUES (?, ?, ?, 1, ?)";
        $stmt = $db->prepare($sql_insert);
        $stmt->bind_param('isid', $queue_type_id, $log_date, $hour_slot, $wait_minutes);
        $stmt->execute();
        $stmt->close();
    }
}

// ─── Notification Helpers ─────────────────────────────────────────────────────

/**
 * Creates a notification for a specific user.
 */
function create_notification(int $user_id, string $message, string $type = 'info'): void {
    $db  = db_connect();
    $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iss', $user_id, $message, $type);
    $stmt->execute();
    $stmt->close();
}

/**
 * Returns the count of unread notifications for a user.
 */
function unread_notification_count(int $user_id): int {
    $db  = db_connect();
    $sql = "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();
    return $count;
}

// ─── System Logger ────────────────────────────────────────────────────────────

/**
 * Logs a system action to the system_logs table.
 */
function log_action(string $action, ?string $details = null, ?int $actor_id = null): void {
    $db        = db_connect();
    $actor_id  = $actor_id ?? (current_user()['id'] ?? null);
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sql       = "INSERT INTO system_logs (actor_id, action, details, ip_address)
                  VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('isss', $actor_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// ─── Input Sanitization ───────────────────────────────────────────────────────

function clean(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// ─── QR Code URL Generator ────────────────────────────────────────────────────

function generate_qr_url(string $token): string {
    $scan_url = urlencode(BASE_URL . '/modules/auth/qr_login.php?token=' . $token);
    return QR_API_URL . '?data=' . $scan_url . '&size=' . QR_SIZE . '&color=1e40af';
}
