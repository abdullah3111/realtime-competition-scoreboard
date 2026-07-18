<?php
require_once 'config.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch($action) {
        case 'start_timer':
            $duration = $data['duration_seconds'];
            $name = $data['timer_name'];
            
            // Stop any existing timer
            $pdo->exec("UPDATE timers SET is_active = 0");
            
            // Create new timer
            $stmt = $pdo->prepare("
                INSERT INTO timers (timer_name, duration_seconds, remaining_seconds, is_active, start_time) 
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$name, $duration, $duration]);
            
            $timerId = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("SELECT * FROM timers WHERE id = ?");
            $stmt->execute([$timerId]);
            $timer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'timer' => $timer]);
            break;
            
        case 'pause_timer':
            $stmt = $pdo->prepare("UPDATE timers SET is_active = 0 WHERE is_active = 1");
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;
            
        case 'stop_timer':
            $stmt = $pdo->prepare("UPDATE timers SET is_active = 0 WHERE is_active = 1");
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;
            
        case 'reset_timer':
            $timerId = $data['timer_id'];
            
            $stmt = $pdo->prepare("SELECT duration_seconds FROM timers WHERE id = ?");
            $stmt->execute([$timerId]);
            $timer = $stmt->fetch();
            
            if ($timer) {
                $stmt = $pdo->prepare("UPDATE timers SET remaining_seconds = ?, is_active = 1, start_time = NOW() WHERE id = ?");
                $stmt->execute([$timer['duration_seconds'], $timerId]);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Timer not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>