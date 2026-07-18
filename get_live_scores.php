<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = ['success' => false];

try {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY total_score DESC");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT le.*, t.team_name, t.color_code 
                          FROM live_events le 
                          JOIN teams t ON le.team_id = t.id 
                          WHERE le.created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                          ORDER BY le.created_at DESC 
                          LIMIT 5");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'teams' => $teams,
        'events' => $events,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch(PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>