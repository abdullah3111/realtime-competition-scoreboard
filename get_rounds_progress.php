<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT COUNT(*) as total_rounds, AVG((current_question/total_questions)*100) as average_progress FROM rounds");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'total_rounds' => $result['total_rounds'],
    'average_progress' => round($result['average_progress'], 1)
]);
?>