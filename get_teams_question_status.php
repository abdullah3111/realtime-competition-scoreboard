<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$round = $data['round'] ?? 1;
$question = $data['question'] ?? 1;

// Get all teams with their round scores
$teams = $pdo->query("SELECT * FROM teams ORDER BY team_name")->fetchAll(PDO::FETCH_ASSOC);

// Get answer log for this question
$stmt = $pdo->prepare("
    SELECT ql.*, t.team_name 
    FROM question_logs ql 
    JOIN teams t ON ql.team_id = t.id 
    WHERE ql.round_number = ? AND ql.question_number = ?
    ORDER BY ql.created_at DESC
");
$stmt->execute([$round, $question]);
$log = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format log entries
foreach($log as &$entry) {
    $entry['time'] = date('H:i:s', strtotime($entry['created_at']));
}

echo json_encode([
    'success' => true,
    'teams' => $teams,
    'log' => $log
]);
?>