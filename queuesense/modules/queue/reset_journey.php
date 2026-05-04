<?php
/**
 * QueueSense — Journey Emergency Reset
 * Clears journey session to allow fresh testing.
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

// Clear journey session
unset($_SESSION['journey']);

// 1. Clear database journey (The Brain)
$db = db_connect();
$stmt = $db->prepare("DELETE FROM user_journeys WHERE user_id = ?");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$stmt->close();

// 2. Clear any lingering active tickets to avoid "Already in queue" bugs
$stmt = $db->prepare("DELETE FROM queue_entries WHERE user_id = ? AND status IN ('waiting', 'pending')");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$stmt->close();

// Redirect back with a clean slate
header("Location: status.php?success=journey_reset");
exit;
