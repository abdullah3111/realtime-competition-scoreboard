<?php
require_once 'config.php';

class DisplayControl {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function showRound($roundNumber) {
        $stmt = $this->pdo->prepare("
            UPDATE display_controls 
            SET control_type = 'round_display', 
                control_value = :round, 
                is_active = TRUE,
                updated_at = NOW()
            WHERE id = 1
        ");
        return $stmt->execute([':round' => $roundNumber]);
    }
    
    public function showTotal() {
        $stmt = $this->pdo->prepare("
            UPDATE display_controls 
            SET control_type = 'total_display', 
                control_value = NULL, 
                is_active = TRUE,
                updated_at = NOW()
            WHERE id = 1
        ");
        return $stmt->execute();
    }
    
    public function closeDisplay() {
        $stmt = $this->pdo->prepare("
            UPDATE display_controls 
            SET is_active = FALSE,
                updated_at = NOW()
            WHERE id = 1
        ");
        return $stmt->execute();
    }
    
    public function getStatus() {
        $stmt = $this->pdo->prepare("SELECT * FROM display_controls WHERE id = 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Usage
$displayControl = new DisplayControl($pdo);
?>