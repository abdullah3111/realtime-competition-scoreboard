<?php
require_once 'config.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch($action) {
        case 'start_round':
            $roundId = $data['round_id'];
            
            // Stop all other rounds
            $pdo->exec("UPDATE rounds SET is_active = 0");
            
            // Start selected round
            $stmt = $pdo->prepare("UPDATE rounds SET is_active = 1, current_question = 1, start_time = NOW() WHERE id = ?");
            $stmt->execute([$roundId]);
            
            echo json_encode(['success' => true, 'message' => 'Round started']);
            break;
            
        case 'next_question':
            $roundId = $data['round_id'];
            
            $stmt = $pdo->prepare("SELECT total_questions, current_question FROM rounds WHERE id = ?");
            $stmt->execute([$roundId]);
            $round = $stmt->fetch();
            
            if ($round['current_question'] < $round['total_questions']) {
                $newQuestion = $round['current_question'] + 1;
                $stmt = $pdo->prepare("UPDATE rounds SET current_question = ? WHERE id = ?");
                $stmt->execute([$newQuestion, $roundId]);
                
                echo json_encode(['success' => true, 'current_question' => $newQuestion]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Already at last question']);
            }
            break;
            
        case 'stop_round':
            $roundId = $data['round_id'];
            
            $stmt = $pdo->prepare("UPDATE rounds SET is_active = 0, end_time = NOW() WHERE id = ?");
            $stmt->execute([$roundId]);
            
            echo json_encode(['success' => true, 'message' => 'Round stopped']);
            break;
            
        case 'reset_round':
            $roundId = $data['round_id'];
            
            $stmt = $pdo->prepare("SELECT round_number FROM rounds WHERE id = ?");
            $stmt->execute([$roundId]);
            $round = $stmt->fetch();
            $roundNum = $round['round_number'];
            
            // Reset round scores for all teams
            $pdo->prepare("
                UPDATE teams SET 
                round{$roundNum}_score = 0,
                round{$roundNum}_correct = 0,
                round{$roundNum}_incorrect = 0,
                total_score = (round1_score + round2_score + round3_score + round4_score)
            ")->execute();
            
            // Reset round
            $stmt = $pdo->prepare("UPDATE rounds SET is_active = 0, current_question = 0, start_time = NULL, end_time = NULL WHERE id = ?");
            $stmt->execute([$roundId]);
            
            // Clear question logs for this round
            $stmt = $pdo->prepare("DELETE FROM question_logs WHERE round_number = ?");
            $stmt->execute([$roundNum]);
            
            echo json_encode(['success' => true, 'message' => 'Round reset']);
            break;
            
        case 'create_round':
            $roundName = $data['round_name'];
            $roundNumber = $data['round_number'];
            $totalQuestions = $data['total_questions'];
            
            $stmt = $pdo->prepare("INSERT INTO rounds (round_name, round_number, total_questions) VALUES (?, ?, ?)");
            $stmt->execute([$roundName, $roundNumber, $totalQuestions]);
            
            echo json_encode(['success' => true, 'message' => 'Round created']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>