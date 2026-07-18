<?php
require_once 'config.php';

// Update remaining time
$stmt = $pdo->query("
    UPDATE timers 
    SET remaining_seconds = GREATEST(0, duration_seconds - TIMESTAMPDIFF(SECOND, start_time, NOW()))
    WHERE is_active = 1 AND start_time IS NOT NULL
");

// Get active timer
$stmt = $pdo->query("SELECT * FROM timers WHERE is_active = 1 AND remaining_seconds > 0 LIMIT 1");
$timer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($timer) {
    echo json_encode([
        'active' => true,
        'timer' => $timer,
        'timer_name' => $timer['timer_name'],
        'duration_seconds' => $timer['duration_seconds'],
        'remaining_seconds' => $timer['remaining_seconds']
    ]);
} else {
    echo json_encode([
        'active' => false,
        'remaining_seconds' => 0
    ]);
}
?>