<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

$round = $data['round'];
$question = $data['question'];

try {
    $pdo->beginTransaction();
    
    // Get all answers for this question
    $stmt = $pdo->prepare("SELECT * FROM question_logs WHERE round_number = ? AND question_number = ?");
    $stmt->execute([$round, $question]);
    $answers = $stmt->fetchAll();
    
    foreach ($answers as $answer) {
        $teamId = $answer['team_id'];
        $points = $answer['points'];
        
        // Update team's round scores
        $correctColumn = "round{$round}_correct";
        $incorrectColumn = "round{$round}_incorrect";
        $scoreColumn = "round{$round}_score";
        
        // Remove count
        if ($answer['is_correct']) {
            $pdo->prepare("UPDATE teams SET $correctColumn = $correctColumn - 1 WHERE id = ?")->execute([$teamId]);
        } else {
            $pdo->prepare("UPDATE teams SET $incorrectColumn = $incorrectColumn - 1 WHERE id = ?")->execute([$teamId]);
        }
        
        // Update round score and total
        $pdo->prepare("
            UPDATE teams 
            SET $scoreColumn = $scoreColumn - ?,
                total_score = total_score - ?
            WHERE id = ?
        ")->execute([$points, $points, $teamId]);
    }
    
    // Delete the logs
    $pdo->prepare("DELETE FROM question_logs WHERE round_number = ? AND question_number = ?")->execute([$round, $question]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Question answers reset'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>