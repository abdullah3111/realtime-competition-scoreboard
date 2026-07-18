<?php
require_once 'config.php';
require_once 'display_control.php';

session_start();

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$displayControl = new DisplayControl($pdo);

try {
    switch($action) {
        case 'show_round_scores':
            $roundNumber = $data['round_number'];
            
            if ($displayControl->showRound($roundNumber)) {
                echo json_encode(['success' => true, 'round_number' => $roundNumber]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
            break;
            
        case 'show_total_scores':
            if ($displayControl->showTotal()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
            break;
            
        case 'close_round_display':
            if ($displayControl->closeDisplay()) {
                echo json_encode(['success' => true, 'message' => 'Round display closed']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>