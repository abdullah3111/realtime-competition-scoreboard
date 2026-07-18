<?php
require_once 'config.php';

$round = $_GET['round'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM rounds WHERE round_number = ?");
$stmt->execute([$round]);
$roundData = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'round' => $roundData
]);
?>