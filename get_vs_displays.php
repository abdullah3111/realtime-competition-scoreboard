<?php
require_once 'config.php';

// First, clean up old inactive displays (older than 10 minutes)
$cleanup_stmt = $pdo->prepare("
    UPDATE vs_displays 
    SET is_active = 0 
    WHERE is_active = 1 
    AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
");
$cleanup_stmt->execute();

// Get active VS display
$stmt = $pdo->prepare("
    SELECT vd.*, 
           t1.total_score as team1_current_score, 
           t2.total_score as team2_current_score
    FROM vs_displays vd
    LEFT JOIN teams t1 ON vd.team1_id = t1.id
    LEFT JOIN teams t2 ON vd.team2_id = t2.id
    WHERE vd.is_active = 1 
    AND vd.shown_at IS NULL
    ORDER BY vd.created_at DESC 
    LIMIT 1
");
$stmt->execute();
$vs_display = $stmt->fetch(PDO::FETCH_ASSOC);

if ($vs_display) {
    // Calculate remaining time based on when it was created
    $created_time = strtotime($vs_display['created_at']);
    $current_time = time();
    $elapsed = $current_time - $created_time;
    $duration = (int)$vs_display['duration'];
    $remaining = max(1, $duration - $elapsed); // At least 1 second remaining
    
    echo json_encode([
        'active' => true,
        'id' => $vs_display['id'],
        'team1_id' => $vs_display['team1_id'],
        'team2_id' => $vs_display['team2_id'],
        'team1_name' => $vs_display['team1_name'],
        'team2_name' => $vs_display['team2_name'],
        'team1_color' => $vs_display['team1_color'],
        'team2_color' => $vs_display['team2_color'],
        'team1_logo' => $vs_display['team1_logo'],
        'team2_logo' => $vs_display['team2_logo'],
        'team1_score' => $vs_display['team1_current_score'],
        'team2_score' => $vs_display['team2_current_score'],
        'message' => $vs_display['message'],
        'duration' => $duration,
        'remaining_time' => $remaining,
        'elapsed_seconds' => $elapsed,
        'mode' => $vs_display['mode'],
        'created_at' => $vs_display['created_at']
    ]);
} else {
    echo json_encode(['active' => false]);
}
?>