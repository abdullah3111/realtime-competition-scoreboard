<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['admin_auth'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

// Check if it's JSON request
$input = json_decode(file_get_contents('php://input'), true);
$action = $_POST['action'] ?? ($input['action'] ?? '');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    switch($action) {
        case 'random':
            $teams = $pdo->query("SELECT * FROM teams")->fetchAll();
            foreach($teams as $team) {
                $points = rand(-10, 20);
                if($points != 0) {
                    $stmt = $pdo->prepare("UPDATE teams SET total_score = total_score + ? WHERE id = ?");
                    $stmt->execute([$points, $team['id']]);
                    
                    $log_stmt = $pdo->prepare("INSERT INTO live_events (team_id, event_type, description, points_change) 
                                              VALUES (?, 'random', ?, ?)");
                    $log_stmt->execute([$team['id'], "Random update", $points]);
                }
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'reset':
            $pdo->query("UPDATE teams SET total_score = 0");
            $pdo->query("DELETE FROM live_events");
            echo json_encode(['success' => true]);
            break;
            
       // Inside the 'add_team' case:
case 'add_team':
    $team_name = $_POST['team_name'];
    $color = $_POST['color'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO teams (team_name, color_code, total_score) VALUES (?, ?, 0)");
        $stmt->execute([$team_name, $color]);
        
        $new_team_id = $pdo->lastInsertId();
        
        // Create a live event for the new team
        $log_stmt = $pdo->prepare("INSERT INTO live_events (team_id, event_type, description, points_change) 
                                  VALUES (?, 'new_team', ?, 0)");
        $log_stmt->execute([$new_team_id, "New team '{$team_name}' joined the competition!"]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'team_id' => $new_team_id,
            'message' => 'Team added successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    break;
            
            
        case 'single_update':
            $team_id = $_POST['team_id'];
            $points = $_POST['points'];
            $reason = $_POST['reason'];
            
            $stmt = $pdo->prepare("UPDATE teams SET total_score = total_score + ? WHERE id = ?");
            $stmt->execute([$points, $team_id]);
            
            $log_stmt = $pdo->prepare("INSERT INTO live_events (team_id, event_type, description, points_change) 
                                      VALUES (?, 'admin_update', ?, ?)");
            $log_stmt->execute([$team_id, $reason, $points]);
            
            echo json_encode(['success' => true]);
            break;
            
        // VS Competition
        case 'vs_competition':
            if(isset($_POST['team1_id']) && isset($_POST['team2_id']) && isset($_POST['points']) && isset($_POST['action_type'])) {
                $team1_id = $_POST['team1_id'];
                $team2_id = $_POST['team2_id'];
                $points = abs($_POST['points']);
                $action_type = $_POST['action_type'];
                $reason = $_POST['reason'] ?? 'VS Competition';
                
                // Get team names for logging
                $stmt = $pdo->prepare("SELECT team_name FROM teams WHERE id = ?");
                $stmt->execute([$team1_id]);
                $team1 = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt->execute([$team2_id]);
                $team2 = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(!$team1 || !$team2) {
                    echo json_encode(['error' => 'Team not found']);
                    break;
                }
                
                // Determine points based on action type
                if($action_type === 'team1_plus') {
                    $team1_points = $points;
                    $team2_points = -$points;
                    $desc1 = "Beat {$team2['team_name']}: {$reason}";
                    $desc2 = "Lost to {$team1['team_name']}: {$reason}";
                } else {
                    $team1_points = -$points;
                    $team2_points = $points;
                    $desc1 = "Lost to {$team2['team_name']}: {$reason}";
                    $desc2 = "Beat {$team1['team_name']}: {$reason}";
                }
                
                // Update both teams
                $stmt = $pdo->prepare("UPDATE teams SET total_score = total_score + ? WHERE id = ?");
                $stmt->execute([$team1_points, $team1_id]);
                $stmt->execute([$team2_points, $team2_id]);
                
                // Log both events
                $log_stmt = $pdo->prepare("INSERT INTO live_events (team_id, event_type, description, points_change) 
                                          VALUES (?, 'vs_competition', ?, ?)");
                $log_stmt->execute([$team1_id, $desc1, $team1_points]);
                $log_stmt->execute([$team2_id, $desc2, $team2_points]);
                
                echo json_encode(['success' => true, 'message' => 'VS Competition updated']);
            } else {
                echo json_encode(['error' => 'Missing parameters']);
            }
            break;
            
        // VS Display Trigger - FIXED VERSION
        case 'trigger_vs_display':
            // Use input from JSON
            $vs_data = $input;
            
            if (!$vs_data || !isset($vs_data['team1_id']) || !isset($vs_data['team2_id'])) {
                echo json_encode(['error' => 'Invalid VS data']);
                break;
            }
            
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // First, deactivate any existing VS displays
                $stmt = $pdo->prepare("UPDATE vs_displays SET is_active = 0, shown_at = NOW() WHERE is_active = 1");
                $stmt->execute();
                
                // Get team scores and details
                $stmt = $pdo->prepare("SELECT team_name, color_code, logo_url, total_score FROM teams WHERE id = ?");
                $stmt->execute([$vs_data['team1_id']]);
                $team1 = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt->execute([$vs_data['team2_id']]);
                $team2 = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$team1 || !$team2) {
                    throw new Exception("One or both teams not found");
                }
                
                // Validate and sanitize data
                $duration = min(300, max(5, intval($vs_data['duration'] ?? 7))); // 5-30 seconds
                $message = substr(trim($vs_data['message'] ?? 'VS Battle'), 0, 255);
                $mode = $vs_data['mode'] ?? 'head_to_head';
                
                // Insert new VS display
                $stmt = $pdo->prepare("INSERT INTO vs_displays 
                    (team1_id, team2_id, team1_name, team2_name, 
                     team1_color, team2_color, team1_logo, team2_logo,
                     team1_score, team2_score, message, duration, mode, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                
                $result = $stmt->execute([
                    $vs_data['team1_id'],
                    $vs_data['team2_id'],
                    $team1['team_name'],
                    $team2['team_name'],
                    $team1['color_code'],
                    $team2['color_code'],
                    $team1['logo_url'] ?? '',
                    $team2['logo_url'] ?? '',
                    $team1['total_score'],
                    $team2['total_score'],
                    $message,
                    $duration,
                    $mode
                ]);
                
                if (!$result) {
                    throw new Exception("Failed to insert VS display");
                }
                
                $vs_id = $pdo->lastInsertId();
                
                // Also create a live event for this VS battle
                $log_stmt = $pdo->prepare("INSERT INTO live_events 
                    (team_id, event_type, description, points_change, created_at)
                    VALUES (?, 'vs_display', ?, 0, NOW())");
                
                // Log for both teams
                $log_stmt->execute([
                    $vs_data['team1_id'],
                    "VS Battle against {$team2['team_name']}: {$message}"
                ]);
                
                $log_stmt->execute([
                    $vs_data['team2_id'],
                    "VS Battle against {$team1['team_name']}: {$message}"
                ]);
                
                $pdo->commit();
                
                // Prepare response data
                $response_data = [
                    'id' => $vs_id,
                    'team1_id' => $vs_data['team1_id'],
                    'team2_id' => $vs_data['team2_id'],
                    'team1_name' => $team1['team_name'],
                    'team2_name' => $team2['team_name'],
                    'team1_color' => $team1['color_code'],
                    'team2_color' => $team2['color_code'],
                    'team1_logo' => $team1['logo_url'] ?? '',
                    'team2_logo' => $team2['logo_url'] ?? '',
                    'team1_score' => $team1['total_score'],
                    'team2_score' => $team2['total_score'],
                    'message' => $message,
                    'duration' => $duration,
                    'mode' => $mode,
                    'remaining_time' => $duration,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'VS display triggered successfully',
                    'data' => $response_data
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'debug' => $vs_data
                ]);
            }
            break;
            
        // Add a direct stop command
        case 'stop_vs_display':
            try {
                // Deactivate all active VS displays
                $stmt = $pdo->prepare("UPDATE vs_displays SET is_active = 0, shown_at = NOW() WHERE is_active = 1");
                $stmt->execute();
                
                // Create stop command
                $stmt = $pdo->prepare("INSERT INTO admin_commands (command, details) VALUES ('stop_vs_modal', ?)");
                $stmt->execute(['Admin manually stopped VS display at ' . date('Y-m-d H:i:s')]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'VS display stopped'
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
    
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>