<?php
/**
 * QueueSense — API: Get Admin Stats
 * Returns real-time data for the Admin Dashboard.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$db = db_connect();

// 1. Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE status = 'done' AND DATE(served_at) = CURDATE()");
$stmt->execute();
$total_served = $stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE status = 'waiting' AND DATE(joined_at) = CURDATE()");
$stmt->execute();
$total_waiting = $stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare("SELECT COUNT(*) FROM service_windows WHERE status = 'open'");
$stmt->execute();
$active_windows = $stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare("SELECT AVG(avg_wait_time) FROM analytics_log WHERE DATE(log_date) = CURDATE()");
$stmt->execute();
$avg_time = round($stmt->get_result()->fetch_row()[0] ?? 0, 1);

// 2. Windows
$windows_sql = "SELECT sw.window_label, sw.status, u.full_name AS staff_name, qt.name AS queue_name,
                (SELECT ticket_number FROM queue_entries 
                 WHERE window_id = sw.id AND status = 'serving' 
                 ORDER BY called_at DESC LIMIT 1) AS ticket_number
                FROM service_windows sw
                LEFT JOIN users u ON u.id = sw.staff_id
                LEFT JOIN queue_types qt ON qt.id = sw.queue_type_id
                ORDER BY sw.window_label ASC";
$windows = $db->query($windows_sql)->fetch_all(MYSQLI_ASSOC);

// 3. Activity Feed (Actual logs)
$logs_sql = "SELECT sl.action, sl.details, sl.created_at, u.full_name as actor_name
             FROM system_logs sl
             LEFT JOIN users u ON u.id = sl.actor_id
             ORDER BY sl.id DESC LIMIT 10";
$logs = $db->query($logs_sql)->fetch_all(MYSQLI_ASSOC);

// 4. Traffic Chart Data
$hourly_sql = "SELECT HOUR(joined_at) as hr, COUNT(*) as qty 
               FROM queue_entries 
               WHERE DATE(joined_at) = CURDATE() 
               GROUP BY hr ORDER BY hr ASC";
$hourly_res = $db->query($hourly_sql)->fetch_all(MYSQLI_ASSOC);
$chart_labels = [];
$chart_values = [];
for ($i = 7; $i <= 18; $i++) {
    $found = false;
    foreach($hourly_res as $h) {
        if ($h['hr'] == $i) {
            $chart_labels[] = date("gA", strtotime($i . ":00"));
            $chart_values[] = (int)$h['qty'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chart_labels[] = date("gA", strtotime($i . ":00"));
        $chart_values[] = 0;
    }
}

echo json_encode([
    'stats' => [
        'total_served' => $total_served,
        'total_waiting' => $total_waiting,
        'active_windows' => $active_windows,
        'avg_time' => $avg_time
    ],
    'windows' => $windows,
    'logs' => $logs,
    'chart' => [
        'labels' => $chart_labels,
        'values' => $chart_values
    ]
]);
