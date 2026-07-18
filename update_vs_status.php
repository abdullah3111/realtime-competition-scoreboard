<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    if ($id) {
        // Mark VS display as shown
        $stmt = $pdo->prepare("UPDATE vs_displays SET shown_at = NOW(), is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'No ID provided']);
    }
}
?>