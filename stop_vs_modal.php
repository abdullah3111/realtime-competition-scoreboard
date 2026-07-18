<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['admin_auth'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Deactivate all active VS displays
        $stmt = $pdo->prepare("UPDATE vs_displays SET is_active = 0, shown_at = NOW() WHERE is_active = 1");
        $stmt->execute();
        
        // 2. Create stop command for scoreboard to check
        $stmt = $pdo->prepare("INSERT INTO admin_commands (command, details) VALUES (?, ?)");
        $stmt->execute([
            'stop_vs_modal', 
            'Admin manually stopped VS modal at ' . date('Y-m-d H:i:s')
        ]);
        
        // 3. Clear old commands (older than 1 minute)
        $stmt = $pdo->prepare("DELETE FROM admin_commands WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $stmt->execute();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'VS modal stop command sent successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid request method'
    ]);
}
?>