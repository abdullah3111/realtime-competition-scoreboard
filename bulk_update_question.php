<?php
require_once 'config.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$round = $data['round'];
$question = $data['question'];
$answer = $data['answer'];
$correctPoints = $data['points_per_correct'] ?? 0;
$incorrectPoints = $data['points_per_incorrect'] ?? 0;

$isCorrect = ($answer === 'correct');
$points = $isCorrect ? $correctPoints : $incorrectPoints;

try {
    $pdo->beginTransaction();
    
    // Get all teams
    $teams = $pdo->query("SELECT id FROM teams")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($teams as $teamId) {
        // Check if already answered
        $stmt = $pdo->prepare("
            SELECT * FROM question_logs 
            WHERE team_id = ? AND round_number = ? AND question_number = ?
        ");
        $stmt->execute([$teamId, $round, $question]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $pdo->prepare("
                UPDATE question_logs 
                SET is_correct = ?, points = ?, added_by = ?, created_at = NOW()
                WHERE id = ?
            ")->execute([$isCorrect, $points, $_SESSION['admin_name'] ?? 'Admin', $existing['id']]);
            
            $adjustment = $points - $existing['points'];
        } else {
            // Insert new
            $pdo->prepare("
                INSERT INTO question_logs 
                (team_id, round_number, question_number, is_correct, points, added_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$teamId, $round, $question, $isCorrect, $points, $_SESSION['admin_name'] ?? 'Admin']);
            
            $adjustment = $points;
        }
        
        // Update team's round scores
        $correctColumn = "round{$round}_correct";
        $incorrectColumn = "round{$round}_incorrect";
        $scoreColumn = "round{$round}_score";
        
        if ($existing) {
            // Remove old count
            if ($existing['is_correct']) {
                $pdo->prepare("UPDATE teams SET $correctColumn = $correctColumn - 1 WHERE id = ?")->execute([$teamId]);
            } else {
                $pdo->prepare("UPDATE teams SET $incorrectColumn = $incorrectColumn - 1 WHERE id = ?")->execute([$teamId]);
            }
        }
        
        // Add new count
        if ($isCorrect) {
            $pdo->prepare("UPDATE teams SET $correctColumn = $correctColumn + 1 WHERE id = ?")->execute([$teamId]);
        } else {
            $pdo->prepare("UPDATE teams SET $incorrectColumn = $incorrectColumn + 1 WHERE id = ?")->execute([$teamId]);
        }
        
        // Update round score and total
        $pdo->prepare("
            UPDATE teams 
            SET $scoreColumn = $scoreColumn + ?,
                total_score = total_score + ?
            WHERE id = ?
        ")->execute([$adjustment, $adjustment, $teamId]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'All teams updated'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>