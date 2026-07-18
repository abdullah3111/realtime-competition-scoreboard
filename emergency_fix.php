<?php
require_once 'config.php';

echo "<h1>Emergency System Fix</h1>";

try {
    $pdo->beginTransaction();
    
    echo "1. Stopping all loops...<br>";
    $pdo->exec("DELETE FROM system_control WHERE control_key = 'last_random_time'");
    
    echo "2. Clearing duplicate events...<br>";
    $pdo->exec("DELETE FROM live_events WHERE created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    
    if(isset($_GET['reset']) && $_GET['reset'] === 'yes') {
        echo "3. Resetting scores to zero...<br>";
        $pdo->exec("UPDATE teams SET total_score = 0");
        $pdo->exec("DELETE FROM score_updates");
        $pdo->exec("DELETE FROM live_events");
    }
    
    echo "4. Adding system event...<br>";
    $stmt = $pdo->prepare("INSERT INTO live_events (event_type, description) VALUES ('system', 'Emergency system fix applied. All loops stopped.')");
    $stmt->execute();
    
    $pdo->commit();
    
    echo "<h2 style='color: green;'> Emergency fix applied successfully!</h2>";
    echo "<p><a href='index.php'>Go to Score Board</a></p>";
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color: red;'> Error: " . $e->getMessage() . "</h2>";
}
?>