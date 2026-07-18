<?php
require_once 'config.php';

$teams = $pdo->query("SELECT * FROM teams ORDER BY total_score DESC")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'teams' => $teams,
    'count' => count($teams)
]);
?>