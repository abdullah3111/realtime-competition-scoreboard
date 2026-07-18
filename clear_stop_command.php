<?php
require_once 'config.php';

// Check if there's a stop command from admin
$stmt = $pdo->prepare("
    SELECT * FROM admin_commands 
    WHERE command = 'stop_vs_modal' 
    AND executed = 0 
    AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND) 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute();
$command = $stmt->fetch(PDO::FETCH_ASSOC);

if ($command) {
    // Mark command as executed
    $stmt = $pdo->prepare("UPDATE admin_commands SET executed = 1 WHERE id = ?");
    $stmt->execute([$command['id']]);
    
    echo json_encode([
        'stop_requested' => true,
        'command_id' => $command['id'],
        'timestamp' => $command['created_at']
    ]);
} else {
    echo json_encode(['stop_requested' => false]);
}
?>