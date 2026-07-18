<?php
require_once 'config.php';
require_once 'display_control.php';

$displayControl = new DisplayControl($pdo);
$status = $displayControl->getStatus();

$response = [
    'show_round_scores' => false,
    'show_total_scores' => false,
    'close_round' => false,
    'timer_update' => false
];

if ($status && $status['is_active']) {
    if ($status['control_type'] === 'round_display') {
        $response['show_round_scores'] = true;
        $response['round_number'] = $status['control_value'];
    } elseif ($status['control_type'] === 'total_display') {
        $response['show_total_scores'] = true;
    }
} else {
    // If display is not active, close any open modal
    $response['close_round'] = true;
}

echo json_encode($response);
?>