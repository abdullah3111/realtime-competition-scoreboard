<?php
session_start();

$ADMIN_PASSWORD = 'Hashtag21!';

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (isset($_POST['admin_pass'])) {
    if (hash_equals($ADMIN_PASSWORD, $_POST['admin_pass'])) {
        $_SESSION['admin_auth'] = true;
    } else {
        $error = "Wrong password!";
    }
}

if (!isset($_SESSION['admin_auth'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access</title>
    <style>
        body{
            margin:0;
            font-family:Arial;
            background:#0f0f0f;
        }
        .overlay{
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.95);
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .box{
            background:#1c1c1c;
            padding:35px;
            border-radius:12px;
            text-align:center;
            color:#fff;
            box-shadow:0 0 25px #000;
        }
        input{
            padding:12px;
            width:220px;
            border:none;
            border-radius:6px;
            margin-top:10px;
        }
        button{
            margin-top:15px;
            padding:10px 25px;
            border:none;
            border-radius:6px;
            cursor:pointer;
        }
        .err{color:#ff5c5c;}
    </style>
</head>
<body>
<div class="overlay">
    <form class="box" method="POST">
        <h3>Admin Panel</h3>
        <?php if(isset($error)) echo "<p class='err'>$error</p>"; ?>
        <input type="password" name="admin_pass" placeholder="Enter password" required autofocus>
        <br>
        <button>Enter</button>
    </form>
</div>
</body>
</html>
<?php
exit; 
}
?>

<?php
require_once 'config.php';

$teams = $pdo->query("SELECT * FROM teams ORDER BY team_name")->fetchAll();
$rounds = $pdo->query("SELECT * FROM rounds ORDER BY round_number")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quiz Competition</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0;
            background: linear-gradient(135deg, #0a0a0a, #1a1a1a, #2a2a2a);
            color: white;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section {
            background: linear-gradient(145deg, rgba(40, 40, 40, 0.95), rgba(30, 30, 30, 0.95));
            padding: 30px;
            border-radius: 20px;
            box-shadow: 
                0 10px 30px rgba(0,0,0,0.4),
                inset 0 0 0 1px rgba(255, 102, 0, 0.2);
            margin-bottom: 35px;
            border: 2px solid rgba(255, 102, 0, 0.3);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        
        .section:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 15px 40px rgba(0,0,0,0.5),
                inset 0 0 0 1px rgba(255, 102, 0, 0.3);
        }
        
        h2 {
            color: #ffcc00;
            border-bottom: 3px solid #ff6600;
            padding-bottom: 15px;
            margin-top: 0;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        h2 i {
            color: #ff6600;
            font-size: 1.8rem;
        }
        
        .form-group { 
            margin: 20px 0; 
        }
        
        label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: bold;
            color: #ffcc00;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        input, select, textarea { 
            padding: 15px 20px; 
            width: 100%; 
            border: 2px solid rgba(255, 102, 0, 0.3);
            border-radius: 12px;
            box-sizing: border-box;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #ff6600;
            outline: none;
            box-shadow: 0 0 20px rgba(255, 102, 0, 0.3);
            background: rgba(0, 0, 0, 0.8);
        }
        
        .success { 
            color: #00ff88; 
            padding: 20px; 
            background: rgba(0, 255, 136, 0.1); 
            border: 2px solid #00ff88;
            border-radius: 15px;
            margin-bottom: 25px;
            display: none;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        .error { 
            color: #ff5555; 
            padding: 20px; 
            background: rgba(255, 85, 85, 0.1); 
            border: 2px solid #ff5555;
            border-radius: 15px;
            margin-bottom: 25px;
            display: none;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Single Team Update Styles */
        .single-team-form {
            background: rgba(0, 102, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(0, 102, 255, 0.3);
        }
        
        .single-team-points-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .points-action-btn {
            padding: 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            text-align: center;
        }
        
        .points-action-btn.positive {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .points-action-btn.negative {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .points-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .quick-points-buttons {
            display: flex;
            gap: 12px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .quick-points-btn {
            padding: 12px 20px;
            background: rgba(255, 102, 0, 0.2);
            color: #ffcc00;
            border: 2px solid #ff6600;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .quick-points-btn:hover {
            background: rgba(255, 102, 0, 0.4);
            transform: scale(1.05);
        }
        
        .single-team-submit-btn {
            background: linear-gradient(135deg, #ff6600, #ffcc00);
            color: black;
            padding: 18px 35px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.3rem;
            font-weight: bold;
            margin-top: 25px;
            display: block;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .single-team-submit-btn:hover {
            background: linear-gradient(135deg, #ffcc00, #ff6600);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 102, 0, 0.4);
        }
        
        /* VS Competition Update Styles */
        .vs-competition-section {
            background: rgba(255, 102, 0, 0.1);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(255, 102, 0, 0.3);
            margin: 25px 0;
        }
        
        .vs-teams-picker {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .vs-team-picker {
            flex: 1;
        }
        
        .vs-divider {
            font-size: 24px;
            font-weight: bold;
            color: #ffcc00;
            padding: 0 10px;
        }
        /* Add to your existing CSS */
.round-display-btn.close-btn {
    background: linear-gradient(135deg, #dc3545, #ff6b6b) !important;
    color: white !important;
}

.round-display-btn.close-btn:hover {
    background: linear-gradient(135deg, #ff6b6b, #dc3545) !important;
}
        
        .points-control {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            align-items: center;
        }
        
        .points-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .add-btn:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
        }
        
        .sub-btn {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .sub-btn:hover {
            background: linear-gradient(135deg, #ff6b6b, #dc3545);
        }
        
        .quick-points-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .quick-point-btn {
            padding: 12px;
            background: rgba(255, 204, 0, 0.2);
            color: #ffcc00;
            border: 2px solid #ffcc00;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
        }
        
        .quick-point-btn:hover {
            background: rgba(255, 204, 0, 0.4);
            transform: scale(1.05);
        }
        
        /* VS Selection Modal */
        .vs-selection {
            background: rgba(0, 0, 0, 0.6);
            padding: 30px;
            border-radius: 15px;
            border: 3px solid #ff6600;
            margin: 25px 0;
            display: none;
        }
        
        .vs-modal-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .vs-modal-submit-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .vs-modal-submit-btn.primary {
            background: linear-gradient(135deg, #ff6600, #ffcc00);
            color: black;
        }
        
        .vs-modal-submit-btn.secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .vs-modal-submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        /* Question-wise Score Update Styles */
        .question-navigation {
            background: rgba(255, 102, 0, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px solid rgba(255, 102, 0, 0.3);
        }
        
        .question-nav-buttons {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .nav-btn:hover {
            background: linear-gradient(135deg, #495057, #6c757d);
            transform: translateY(-3px);
        }
        
        .teams-score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .team-score-card {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9), rgba(30, 30, 30, 0.9));
            border-radius: 12px;
            padding: 20px;
            border: 2px solid;
            transition: all 0.3s;
            position: relative;
        }
        
        .team-score-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .team-color-indicator {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .team-name-display {
            font-size: 1.3rem;
            font-weight: bold;
            color: #fff;
            flex: 1;
        }
        
        .team-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #aaa;
        }
        
        .team-stat-item {
            text-align: center;
        }
        
        .team-stat-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffcc00;
            display: block;
        }
        
        .score-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        
        .score-action-btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .score-action-btn.correct-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .score-action-btn.correct-btn:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.5);
        }
        
        .score-action-btn.incorrect-btn {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .score-action-btn.incorrect-btn:hover {
            background: linear-gradient(135deg, #ff6b6b, #dc3545);
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.5);
        }
        
        .score-action-btn.active {
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
        }
        
        .team-total-score {
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffcc00;
            margin: 10px 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }
        
        /* Bulk Actions */
        .bulk-actions {
            background: rgba(0, 102, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px solid rgba(0, 102, 255, 0.3);
        }
        
        .bulk-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .bulk-btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            flex: 1;
            min-width: 200px;
        }
        
        .bulk-btn.correct-all {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .bulk-btn.incorrect-all {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .bulk-btn.reset-q {
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #000;
        }
        
        .bulk-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        /* Question Log */
        .question-log {
            background: rgba(108, 117, 125, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px solid rgba(108, 117, 125, 0.3);
        }
        
        .log-container {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }
        
        .log-entry {
            padding: 10px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border-left: 4px solid;
            font-size: 0.9rem;
        }
        
        .log-entry.correct {
            border-left-color: #28a745;
        }
        
        .log-entry.incorrect {
            border-left-color: #dc3545;
        }
        
        .log-team {
            font-weight: bold;
            color: #ffcc00;
        }
        
        .log-points {
            font-weight: bold;
            float: right;
        }
        
        .log-points.positive {
            color: #28a745;
        }
        
        .log-points.negative {
            color: #dc3545;
        }
        
        /* Round Management Styles */
        .rounds-display {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 25px 0;
        }
        
        .round-card {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9), rgba(30, 30, 30, 0.9));
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #444;
            transition: all 0.3s;
        }
        
        .round-card.active {
            border-color: #00ff88;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        
        .round-card.new-round {
            border: 2px dashed #ff6600;
            background: rgba(255, 102, 0, 0.05);
        }
        
        .round-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .round-header h3 {
            color: #ffcc00;
            margin: 0;
            font-size: 1.4rem;
        }
        
        .round-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .round-card.active .round-status {
            background: rgba(0, 255, 136, 0.2);
            color: #00ff88;
        }
        
        .round-card:not(.active) .round-status {
            background: rgba(255, 255, 255, 0.1);
            color: #aaa;
        }
        
        .round-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .round-stat {
            text-align: center;
        }
        
        .round-stat .stat-label {
            font-size: 0.8rem;
            color: #aaa;
            margin-bottom: 5px;
        }
        
        .round-stat .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffcc00;
        }
        
        .round-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .round-btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .round-btn.start-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .round-btn.next-btn {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }
        
        .round-btn.stop-btn {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .round-btn.reset-btn {
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #000;
        }
        
        .round-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .round-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .round-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* Timer Control Panel */
        .timer-control-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 25px 0;
        }
        
        .timer-presets, .custom-timer, .active-timer-display {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(0, 102, 255, 0.3);
        }
        
        .preset-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .preset-btn {
            padding: 20px;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .preset-btn:hover {
            background: linear-gradient(135deg, #495057, #6c757d);
            transform: translateY(-5px);
        }
        
        .preset-btn .time {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffcc00;
        }
        
        .preset-btn .label {
            font-size: 0.9rem;
            color: #aaa;
        }
        
        .timer-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .timer-action-btn {
            padding: 15px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .timer-action-btn.start-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .timer-action-btn.pause-btn {
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #000;
        }
        
        .timer-action-btn.stop-btn {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .timer-action-btn.reset-btn {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }
        
        .timer-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .live-timer-preview {
            text-align: center;
            padding: 30px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid rgba(0, 255, 136, 0.3);
        }
        
        .timer-name {
            font-size: 1.5rem;
            color: #ffcc00;
            margin-bottom: 10px;
        }
        
        .timer-countdown {
            font-size: 3rem;
            font-weight: bold;
            color: #00ff88;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
        }
        
        .timer-status {
            font-size: 1.1rem;
            color: #aaa;
        }
        
        /* Round Display Triggers */
        .round-display-triggers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .round-display-btn {
            padding: 30px 20px;
            background: linear-gradient(135deg, #ff6600, #ff9900);
            color: black;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            text-align: center;
        }
        
        .round-display-btn:hover {
            background: linear-gradient(135deg, #ff9900, #ff6600);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 102, 0, 0.4);
        }
        
        .round-display-btn i {
            font-size: 2.5rem;
        }
        
        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, #ff6600, #ffcc00);
            color: black;
            padding: 18px 35px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.3rem;
            font-weight: bold;
            margin-top: 25px;
            display: block;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #ffcc00, #ff6600);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 102, 0, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Logout Button */
        .logout-btn {
            position: fixed;
            top: 25px;
            right: 25px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            z-index: 1000;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.5);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #ff6b6b, #dc3545);
            transform: scale(1.08);
            box-shadow: 0 12px 35px rgba(220, 53, 69, 0.7);
        }
        
        /* VS Trigger Buttons */
        .vs-trigger-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .vs-trigger-btn {
            padding: 30px 20px;
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            min-height: 150px;
        }
        
        .vs-trigger-btn:nth-child(1) { background: linear-gradient(135deg, #ff416c, #ff4b2b); }
        .vs-trigger-btn:nth-child(2) { background: linear-gradient(135deg, #00b09b, #96c93d); }
        .vs-trigger-btn:nth-child(3) { background: linear-gradient(135deg, #ff9966, #ff5e62); }
        .vs-trigger-btn:nth-child(4) { background: linear-gradient(135deg, #36d1dc, #5b86e5); }
        .vs-trigger-btn:nth-child(5) { background: linear-gradient(135deg, #da4453, #89216b); }
        .vs-trigger-btn:nth-child(6) { background: linear-gradient(135deg, #654ea3, #eaafc8); }
        
        .vs-trigger-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        
        /* Utility Buttons */
        .utility-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .utility-btn {
            padding: 25px 30px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            min-height: 100px;
        }
        
        .utility-btn.random-btn {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }
        
        .utility-btn.reset-btn {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .utility-btn.addteam-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .utility-btn:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .timer-control-panel {
                grid-template-columns: 1fr;
            }
            
            .teams-score-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .rounds-display {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .single-team-points-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-points-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .section {
                padding: 20px;
            }
            
            .teams-score-grid {
                grid-template-columns: 1fr;
            }
            
            .rounds-display {
                grid-template-columns: 1fr;
            }
            
            .vs-teams-picker {
                flex-direction: column;
            }
            
            .vs-divider {
                padding: 20px 0;
                transform: rotate(90deg);
            }
            
            .points-control {
                flex-direction: column;
            }
            
            .score-actions {
                grid-template-columns: 1fr;
            }
            
            .bulk-buttons {
                flex-direction: column;
            }
            
            .bulk-btn {
                min-width: 100%;
            }
            
            .preset-buttons {
                grid-template-columns: 1fr;
            }
            
            .round-display-triggers {
                grid-template-columns: 1fr;
            }
            
            .vs-trigger-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .utility-buttons {
                grid-template-columns: 1fr;
            }
            
            .single-team-points-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            h2 {
                font-size: 1.6rem;
            }
        }
        
        @media (max-width: 480px) {
            .vs-trigger-buttons {
                grid-template-columns: 1fr;
            }
            
            .round-actions {
                grid-template-columns: 1fr;
            }
            
            .timer-actions {
                grid-template-columns: 1fr;
            }
            
            .round-info {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .logout-btn {
                padding: 10px 20px;
                font-size: 1rem;
                top: 15px;
                right: 15px;
            }
            
            .single-team-points-actions {
                grid-template-columns: 1fr;
            }
            
            .quick-points-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <form method="POST">
        <button type="submit" name="logout" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </form>
    
    <div class="container">
        <div class="success" id="successMsg"></div>
        <div class="error" id="errorMsg"></div>
        
        <!-- Round Management Section -->
        <div class="section">
            <h2><i class="fas fa-list-ol"></i> Round Management</h2>
            <div class="rounds-display">
                <?php foreach($rounds as $round): 
                    $activeClass = $round['is_active'] ? 'active' : '';
                ?>
                <div class="round-card <?= $activeClass ?>" data-round-id="<?= $round['id'] ?>">
                    <div class="round-header">
                        <h3><?= htmlspecialchars($round['round_name']) ?></h3>
                        <span class="round-status">
                            <?= $round['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                        </span>
                    </div>
                    <div class="round-info">
                        <div class="round-stat">
                            <span class="stat-label">Round:</span>
                            <span class="stat-value"><?= $round['round_number'] ?></span>
                        </div>
                        <div class="round-stat">
                            <span class="stat-label">Questions:</span>
                            <span class="stat-value"><?= $round['current_question'] ?>/<?= $round['total_questions'] ?></span>
                        </div>
                        <div class="round-stat">
                            <span class="stat-label">Status:</span>
                            <span class="stat-value" id="roundStatus<?= $round['id'] ?>">
                                <?= $round['is_active'] ? 'Live' : 'Stopped' ?>
                            </span>
                        </div>
                    </div>
                    <div class="round-actions">
                        <button type="button" class="round-btn start-btn" onclick="startRound(<?= $round['id'] ?>)"
                                <?= $round['is_active'] ? 'disabled' : '' ?>>
                            <i class="fas fa-play"></i> Start
                        </button>
                        <button type="button" class="round-btn next-btn" onclick="nextQuestion(<?= $round['id'] ?>)"
                                <?= !$round['is_active'] ? 'disabled' : '' ?>>
                            <i class="fas fa-forward"></i> Next Q
                        </button>
                        <button type="button" class="round-btn stop-btn" onclick="stopRound(<?= $round['id'] ?>)"
                                <?= !$round['is_active'] ? 'disabled' : '' ?>>
                            <i class="fas fa-stop"></i> Stop
                        </button>
                        <button type="button" class="round-btn reset-btn" onclick="resetRound(<?= $round['id'] ?>)">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="round-card new-round">
                    <div class="round-header">
                        <h3>Create New Round</h3>
                    </div>
                    <div class="round-form">
                        <div class="form-group">
                            <input type="text" id="newRoundName" placeholder="Round Name (e.g., Brain Booster)" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="number" id="newRoundNumber" placeholder="Round Number" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="number" id="newTotalQuestions" placeholder="Total Questions" class="form-control">
                        </div>
                        <button type="button" class="submit-btn" onclick="createNewRound()">
                            <i class="fas fa-plus"></i> Create Round
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Timer Display Control -->
        <div class="section">
            <h2><i class="fas fa-clock"></i> Timer Display Control</h2>
            <div class="timer-control-panel">
                <div class="timer-presets">
                    <h3><i class="fas fa-bolt"></i> Quick Timer Presets</h3>
                    <div class="preset-buttons">
                        <button type="button" class="preset-btn" onclick="startTimer(30, 'Question Timer')">
                            <span class="time">30s</span>
                            <span class="label">Question</span>
                        </button>
                        <button type="button" class="preset-btn" onclick="startTimer(60, 'Thinking Time')">
                            <span class="time">60s</span>
                            <span class="label">Thinking</span>
                        </button>
                        <button type="button" class="preset-btn" onclick="startTimer(120, 'Discussion')">
                            <span class="time">2m</span>
                            <span class="label">Discussion</span>
                        </button>
                        <button type="button" class="preset-btn" onclick="startTimer(300, 'Break Time')">
                            <span class="time">5m</span>
                            <span class="label">Break</span>
                        </button>
                        <button type="button" class="preset-btn" onclick="startTimer(600, 'Intermission')">
                            <span class="time">10m</span>
                            <span class="label">Intermission</span>
                        </button>
                    </div>
                </div>
                
                <div class="custom-timer">
                    <h3><i class="fas fa-cog"></i> Custom Timer</h3>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Duration (seconds):</label>
                        <input type="number" id="timerDuration" value="60" min="10" max="3600">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-font"></i> Timer Name:</label>
                        <input type="text" id="timerName" placeholder="e.g., Buzzer Round Timer" value="Quiz Timer">
                    </div>
                    <div class="timer-actions">
                        <button type="button" class="timer-action-btn start-btn" onclick="startCustomTimer()">
                            <i class="fas fa-play"></i> Start Timer
                        </button>
                        <button type="button" class="timer-action-btn pause-btn" onclick="pauseTimer()">
                            <i class="fas fa-pause"></i> Pause
                        </button>
                        <button type="button" class="timer-action-btn stop-btn" onclick="stopTimer()">
                            <i class="fas fa-stop"></i> Stop
                        </button>
                        <button type="button" class="timer-action-btn reset-btn" onclick="resetTimer()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
                
                <div class="active-timer-display">
                    <h3><i class="fas fa-tv"></i> Active Timer Display</h3>
                    <div class="live-timer-preview" id="liveTimerPreview">
                        <div class="timer-name">No Active Timer</div>
                        <div class="timer-countdown">00:00</div>
                        <div class="timer-status">IDLE</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Round Score Display Trigger -->
        <div class="section">
            <h2><i class="fas fa-chart-bar"></i> Round Score Display</h2>
            <div class="round-display-triggers">
                <button type="button" class="round-display-btn" onclick="triggerRoundDisplay(1)">
                    <i class="fas fa-play-circle"></i>
                    <span>Show Round 1 Scores</span>
                </button>
                <button type="button" class="round-display-btn" onclick="triggerRoundDisplay(2)">
                    <i class="fas fa-brain"></i>
                    <span>Show Brain Booster Round</span>
                </button>
                <button type="button" class="round-display-btn" onclick="triggerRoundDisplay(3)">
                    <i class="fas fa-bell"></i>
                    <span>Show Buzzer Round</span>
                </button>
                <button type="button" class="round-display-btn" onclick="triggerRoundDisplay(4)">
                    <i class="fas fa-bolt"></i>
                    <span>Show Rapid Fire Round</span>
                </button>
                <button type="button" class="round-display-btn" onclick="triggerTotalDisplay()">
                    <i class="fas fa-trophy"></i>
                    <span>Show Total Scores</span>
                </button>
                 <button type="button" class="round-display-btn" style="background: linear-gradient(135deg, #dc3545, #ff6b6b);" 
                onclick="closeRoundDisplay()">
            <i class="fas fa-times-circle"></i>
            <span>Close Round Display</span>
        </button>
            </div>
        </div>
        
        <!-- VS Battle Display -->
        <div class="section">
            <h2><i class="fas fa-crosshairs"></i> VS Battle Display</h2>
            <div class="vs-trigger-buttons">
                <button type="button" class="vs-trigger-btn" onclick="showVSselection('head_to_head')">
                    <i class="fas fa-fist-raised"></i>
                    <span>🥊 Head to Head</span>
                </button>
                <button type="button" class="vs-trigger-btn" onclick="showVSselection('correct_answer')">
                    <i class="fas fa-check-circle"></i>
                    <span>✅ Correct Answer</span>
                </button>
                <button type="button" class="vs-trigger-btn" onclick="showVSselection('speed_round')">
                    <i class="fas fa-bolt"></i>
                    <span>⚡ Speed Round</span>
                </button>
                <button type="button" class="vs-trigger-btn" onclick="showVSselection('bonus_round')">
                    <i class="fas fa-bullseye"></i>
                    <span>🎯 Bonus Round</span>
                </button>
                <button type="button" class="vs-trigger-btn" onclick="showVSselection('final_battle')">
                    <i class="fas fa-trophy"></i>
                    <span>🏆 Final Battle</span>
                </button>
                <button type="button" class="vs-trigger-btn" onclick="showVSselection('custom')">
                    <i class="fas fa-magic"></i>
                    <span>✨ Custom</span>
                </button>
            </div>
            
            <!-- VS Selection Form -->
            <div class="vs-selection" id="vsSelection">
                <h3 id="vsModeTitle">VS Battle Configuration</h3>
                
                <div class="vs-teams-picker">
                    <div class="vs-team-picker">
                        <label><i class="fas fa-users"></i> Team 1:</label>
                        <select id="vsTeam1" class="form-control">
                            <option value="">Select Team 1</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['id'] ?>" data-color="<?= $team['color_code'] ?>" data-logo="<?= $team['logo_url'] ?>">
                                    <?= $team['team_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="vs-divider">VS</div>
                    
                    <div class="vs-team-picker">
                        <label><i class="fas fa-users"></i> Team 2:</label>
                        <select id="vsTeam2" class="form-control">
                            <option value="">Select Team 2</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['id'] ?>" data-color="<?= $team['color_code'] ?>" data-logo="<?= $team['logo_url'] ?>">
                                    <?= $team['team_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Battle Message:</label>
                    <input type="text" id="vsMessage" class="form-control" placeholder="e.g., Round 3 - Correct Answer">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Display Duration (seconds):</label>
                    <input type="number" id="vsDuration" value="7" min="3" max="15" style="width: 120px; display: inline-block;">
                    <div class="quick-points-grid">
                        <button type="button" class="quick-point-btn" onclick="setDuration(3)">3s</button>
                        <button type="button" class="quick-point-btn" onclick="setDuration(5)">5s</button>
                        <button type="button" class="quick-point-btn" onclick="setDuration(7)">7s</button>
                        <button type="button" class="quick-point-btn" onclick="setDuration(10)">10s</button>
                    </div>
                </div>
                
                <div class="vs-modal-buttons">
                    <button type="button" class="vs-modal-submit-btn primary" onclick="triggerVSdisplay()">
                        <i class="fas fa-bolt"></i> Show VS Battle on Scoreboard
                    </button>
                    <button type="button" class="vs-modal-submit-btn secondary" onclick="hideVSselection()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
        
        <!-- VS Competition Update -->
        <div class="section">
            <h2><i class="fas fa-users"></i> VS Competition Update</h2>
            <p style="color: #aaa; margin-bottom: 20px; font-size: 1.1rem;">
                Update two teams simultaneously - one gets positive, other gets negative points:
            </p>
            
            <div class="vs-competition-section">
                <div class="vs-teams-picker">
                    <div class="vs-team-picker">
                        <label><i class="fas fa-users"></i> Team 1:</label>
                        <select id="vsTeam1Points" class="form-control">
                            <option value="">Select Team 1</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['id'] ?>" data-score="<?= $team['total_score'] ?>">
                                    <?= $team['team_name'] ?> (<?= $team['total_score'] ?> pts)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="team-info" id="vsTeam1PointsInfo" style="font-size: 12px; color: #666; margin-top: 5px;">Current: --</div>
                    </div>
                    
                    <div class="vs-divider">VS</div>
                    
                    <div class="vs-team-picker">
                        <label><i class="fas fa-users"></i> Team 2:</label>
                        <select id="vsTeam2Points" class="form-control">
                            <option value="">Select Team 2</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['id'] ?>" data-score="<?= $team['total_score'] ?>">
                                    <?= $team['team_name'] ?> (<?= $team['total_score'] ?> pts)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="team-info" id="vsTeam2PointsInfo" style="font-size: 12px; color: #666; margin-top: 5px;">Current: --</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-star"></i> Points Amount:</label>
                    <input type="number" id="vsPointsAmount" value="10" min="1" class="form-control">
                    <div class="quick-points-grid">
                        <button type="button" class="quick-point-btn" onclick="setVSPoints(2)">2</button>
                        <button type="button" class="quick-point-btn" onclick="setVSPoints(5)">5</button>
                        <button type="button" class="quick-point-btn" onclick="setVSPoints(10)">10</button>
                        <button type="button" class="quick-point-btn" onclick="setVSPoints(20)">20</button>
                    </div>
                </div>
                
                <div class="points-control">
                    <button type="button" class="points-btn add-btn" onclick="setVSAction('team1_plus')">
                        <i class="fas fa-plus-circle"></i> Team 1 GETS + / Team 2 GETS -
                    </button>
                    <button type="button" class="points-btn sub-btn" onclick="setVSAction('team2_plus')">
                        <i class="fas fa-minus-circle"></i> Team 1 GETS - / Team 2 GETS +
                    </button>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Reason:</label>
                    <textarea id="vsPointsReason" rows="2" class="form-control" placeholder="e.g., Correct answer in Round 3"></textarea>
                </div>
                
                <button type="button" class="submit-btn" onclick="executeVSPointsUpdate()">
                    <i class="fas fa-sync-alt"></i> Update Both Teams
                </button>
            </div>
        </div>
        
        <!-- Single Team Update Section -->
        <div class="section">
            <h2><i class="fas fa-user-edit"></i> Single Team Update</h2>
            <div class="single-team-form">
                <form id="singleTeamForm">
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Select Team:</label>
                        <select name="team_id" required class="form-control">
                            <option value="">Select a team</option>
                            <?php foreach($teams as $team): ?>
                            <option value="<?= $team['id'] ?>">
                                <?= htmlspecialchars($team['team_name']) ?> (Current: <?= $team['total_score'] ?> points)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-star"></i> Points:</label>
                        <input type="number" name="points" required placeholder="e.g., 10 or -5" class="form-control" id="singleTeamPoints">
                        <div class="quick-points-buttons">
                            <button type="button" class="quick-points-btn" onclick="setSinglePoints(2)">+2</button>
                            <button type="button" class="quick-points-btn" onclick="setSinglePoints(5)">+5</button>
                            <button type="button" class="quick-points-btn" onclick="setSinglePoints(10)">+10</button>
                            <button type="button" class="quick-points-btn" onclick="setSinglePoints(-2)">-2</button>
                            <button type="button" class="quick-points-btn" onclick="setSinglePoints(-5)">-5</button>
                            <button type="button" class="quick-points-btn" onclick="setSinglePoints(-10)">-10</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Reason:</label>
                        <textarea name="reason" rows="3" required placeholder="Reason for score update (e.g., Correct answer in Round 2)" class="form-control"></textarea>
                    </div>
                    
                    <div class="single-team-points-actions">
                        <button type="button" class="points-action-btn positive" onclick="setSinglePoints(5)">
                            <i class="fas fa-plus-circle"></i>
                            <span>+5 Points</span>
                        </button>
                        <button type="button" class="points-action-btn positive" onclick="setSinglePoints(10)">
                            <i class="fas fa-plus-circle"></i>
                            <span>+10 Points</span>
                        </button>
                        <button type="button" class="points-action-btn negative" onclick="setSinglePoints(-5)">
                            <i class="fas fa-minus-circle"></i>
                            <span>-5 Points</span>
                        </button>
                        <button type="button" class="points-action-btn negative" onclick="setSinglePoints(-10)">
                            <i class="fas fa-minus-circle"></i>
                            <span>-10 Points</span>
                        </button>
                    </div>
                    
                    <button type="submit" class="single-team-submit-btn">
                        <i class="fas fa-sync-alt"></i> Update Single Team Score
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Question-wise Score Update Section -->
        <div class="section">
            <h2><i class="fas fa-question-circle"></i> Question-wise Score Update</h2>
            
            <!-- Round Selection -->
            <div class="form-group">
                <label><i class="fas fa-list-ol"></i> Select Round:</label>
                <select id="selectedRound" onchange="loadRoundQuestions()" class="form-control">
                    <?php foreach($rounds as $round): ?>
                    <option value="<?= $round['round_number'] ?>">
                        <?= htmlspecialchars($round['round_name']) ?> (<?= $round['total_questions'] ?> questions)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Question Navigation -->
            <div class="question-navigation">
                <h3 style="color: #ffcc00; margin: 20px 0;">
                    <i class="fas fa-question"></i> 
                    Question: <span id="currentQuestionNum">1</span>/<span id="totalQuestions">20</span>
                </h3>
                
                <div class="question-nav-buttons">
                    <button type="button" class="nav-btn" onclick="changeQuestion(-1)">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <button type="button" class="nav-btn" onclick="changeQuestion(1)">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <input type="number" id="jumpToQuestion" min="1" max="50" value="1" style="width: 80px; margin: 0 10px;">
                    <button type="button" class="nav-btn" onclick="jumpToQuestion()">
                        <i class="fas fa-forward"></i> Jump
                    </button>
                </div>
            </div>
            
            <!-- Teams Score Update Grid -->
            <div class="teams-score-grid" id="teamsScoreGrid">
                <!-- Dynamic content load hoga -->
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <h3 style="color: #ffcc00; margin: 20px 0;">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h3>
                
                <div class="bulk-buttons">
                    <button type="button" class="bulk-btn correct-all" onclick="markAllCorrect()">
                        <i class="fas fa-check-circle"></i> Mark All Correct
                    </button>
                    <button type="button" class="bulk-btn incorrect-all" onclick="markAllIncorrect()">
                        <i class="fas fa-times-circle"></i> Mark All Incorrect
                    </button>
                    <button type="button" class="bulk-btn reset-q" onclick="resetQuestion()">
                        <i class="fas fa-redo"></i> Reset This Question
                    </button>
                </div>
            </div>
            
            <!-- Question Log -->
            <div class="question-log">
                <h3 style="color: #ffcc00; margin: 20px 0;">
                    <i class="fas fa-history"></i> Question History
                </h3>
                <div class="log-container" id="questionLog">
                    <!-- Log entries will appear here -->
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="section">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="utility-buttons">
                <button type="button" class="utility-btn random-btn" onclick="executeAction('random')">
                    <i class="fas fa-dice"></i> Random Points
                </button>
                <button type="button" class="utility-btn reset-btn" onclick="executeAction('reset')">
                    <i class="fas fa-history"></i> Reset Scores
                </button>
                <button type="button" class="utility-btn addteam-btn" onclick="addNewTeam()">
                    <i class="fas fa-plus"></i> Add Team
                </button>
            </div>
        </div>
    </div>
    
<script>
let currentRound = 1;
let currentQuestion = 1;
let roundData = {};
let teamAnswers = {};
let timerInterval = null;
let currentTimer = null;

// VS Modal variables
let currentVSmode = '';
let currentVSAction = 'team1_plus';
const vsMessages = {
    'head_to_head': 'Head to Head Battle',
    'correct_answer': 'Correct Answer Showdown',
    'speed_round': 'Speed Round Face-off',
    'bonus_round': 'Bonus Round Duel',
    'final_battle': 'Final Battle Royale',
    'custom': 'Custom Battle'
};

document.addEventListener('DOMContentLoaded', function() {
    loadRoundQuestions();
    updateTimerPreview();
    
    // Auto-update timer preview every second
    setInterval(updateTimerPreview, 1000);
    
    // Single Team Form submission
    document.getElementById('singleTeamForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateSingleTeam();
    });
    
    // Initialize team info display for VS competition
    document.getElementById('vsTeam1Points').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        document.getElementById('vsTeam1PointsInfo').textContent = 
            selected.value ? `Current: ${selected.dataset.score} points` : 'Current: --';
    });
    
    document.getElementById('vsTeam2Points').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        document.getElementById('vsTeam2PointsInfo').textContent = 
            selected.value ? `Current: ${selected.dataset.score} points` : 'Current: --';
    });
    
    // Initialize VS action
    setVSAction('team1_plus');
});

// ================================================
// SINGLE TEAM UPDATE FUNCTIONS
// ================================================

function setSinglePoints(points) {
    document.getElementById('singleTeamPoints').value = points;
}

async function updateSingleTeam() {
    const form = document.getElementById('singleTeamForm');
    const formData = new FormData(form);
    
    // Add action type
    formData.append('action', 'single_update');
    
    try {
        const response = await fetch('update_score.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('✅ Single team score updated successfully!');
            form.reset();
        } else {
            showError(data.error || 'Failed to update score');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

// ================================================
// VS BATTLE FUNCTIONS
// ================================================

function showVSselection(mode) {
    currentVSmode = mode;
    document.getElementById('vsSelection').style.display = 'block';
    document.getElementById('vsModeTitle').textContent = vsMessages[mode] + ' Configuration';
    
    // Set default message
    if (mode !== 'custom') {
        document.getElementById('vsMessage').value = vsMessages[mode];
    } else {
        document.getElementById('vsMessage').value = '';
        document.getElementById('vsMessage').placeholder = 'Enter your custom battle message...';
    }
    
    // Scroll to selection
    document.getElementById('vsSelection').scrollIntoView({ behavior: 'smooth' });
}

function hideVSselection() {
    document.getElementById('vsSelection').style.display = 'none';
    currentVSmode = '';
}

function setDuration(seconds) {
    document.getElementById('vsDuration').value = seconds;
}

async function triggerVSdisplay() {
    const team1Select = document.getElementById('vsTeam1');
    const team2Select = document.getElementById('vsTeam2');
    const message = document.getElementById('vsMessage').value;
    const duration = document.getElementById('vsDuration').value;
    
    if (!team1Select.value || !team2Select.value) {
        showError('Please select both teams!');
        return;
    }
    
    if (team1Select.value === team2Select.value) {
        showError('Cannot select same team for VS battle!');
        return;
    }
    
    if (!message.trim()) {
        showError('Please enter a battle message!');
        return;
    }
    
    const team1Option = team1Select.options[team1Select.selectedIndex];
    const team2Option = team2Select.options[team2Select.selectedIndex];
    
    const vsData = {
        action: 'trigger_vs_display',
        team1_id: team1Select.value,
        team2_id: team2Select.value,
        team1_name: team1Option.text.split(' (')[0],
        team2_name: team2Option.text.split(' (')[0],
        team1_color: team1Option.dataset.color || '#ff6600',
        team2_color: team2Option.dataset.color || '#0066ff',
        team1_logo: team1Option.dataset.logo || '',
        team2_logo: team2Option.dataset.logo || '',
        mode: currentVSmode,
        message: message,
        duration: duration
    };
    
    try {
        const response = await fetch('update_score.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(vsData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('VS Battle display triggered on scoreboard! It will show for ' + duration + ' seconds.');
            hideVSselection();
            // Clear selections
            team1Select.value = '';
            team2Select.value = '';
            document.getElementById('vsMessage').value = '';
        } else {
            showError(data.error || 'Failed to trigger display');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

// ================================================
// VS COMPETITION UPDATE FUNCTIONS
// ================================================

function setVSPoints(amount) {
    document.getElementById('vsPointsAmount').value = amount;
}

function setVSAction(action) {
    currentVSAction = action;
    // Visual feedback
    const addBtn = document.querySelector('.add-btn');
    const subBtn = document.querySelector('.sub-btn');
    
    if (action === 'team1_plus') {
        addBtn.style.boxShadow = '0 0 10px #28a745';
        subBtn.style.boxShadow = 'none';
    } else {
        subBtn.style.boxShadow = '0 0 10px #dc3545';
        addBtn.style.boxShadow = 'none';
    }
}

async function executeVSPointsUpdate() {
    const team1Select = document.getElementById('vsTeam1Points');
    const team2Select = document.getElementById('vsTeam2Points');
    const points = document.getElementById('vsPointsAmount').value;
    const reason = document.getElementById('vsPointsReason').value;
    
    if (!team1Select.value || !team2Select.value) {
        showError('Please select both teams!');
        return;
    }
    
    if (team1Select.value === team2Select.value) {
        showError('Cannot select same team for VS competition!');
        return;
    }
    
    if (!points || points < 1) {
        showError('Please enter valid points amount!');
        return;
    }
    
    if (!reason.trim()) {
        showError('Please enter a reason!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'vs_competition');
    formData.append('team1_id', team1Select.value);
    formData.append('team2_id', team2Select.value);
    formData.append('points', points);
    formData.append('action_type', currentVSAction);
    formData.append('reason', reason);
    
    try {
        const response = await fetch('update_score.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('VS Competition points updated successfully!');
            document.getElementById('vsPointsReason').value = '';
            setTimeout(() => location.reload(), 1000);
        } else {
            showError(data.error || 'Failed to update points');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

// ================================================
// ROUND MANAGEMENT FUNCTIONS
// ================================================

async function startRound(roundId) {
    if (!confirm('Start this round? This will stop any other active round.')) return;
    
    try {
        const response = await fetch('update_round.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'start_round',
                round_id: roundId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Round started successfully!');
            updateRoundUI(roundId, true);
            loadRoundQuestions();
        } else {
            showError(data.error || 'Failed to start round');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function nextQuestion(roundId) {
    try {
        const response = await fetch('update_round.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'next_question',
                round_id: roundId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Moved to next question!');
            updateRoundQuestionCount(roundId, data.current_question);
            loadRoundQuestions();
        } else {
            showError(data.error || 'Failed to move to next question');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function stopRound(roundId) {
    if (!confirm('Stop this round?')) return;
    
    try {
        const response = await fetch('update_round.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'stop_round',
                round_id: roundId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Round stopped!');
            updateRoundUI(roundId, false);
        } else {
            showError(data.error || 'Failed to stop round');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function resetRound(roundId) {
    if (!confirm('Reset this round? This will clear all scores for this round.')) return;
    
    try {
        const response = await fetch('update_round.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'reset_round',
                round_id: roundId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Round reset successfully!');
            updateRoundQuestionCount(roundId, 0);
            loadRoundQuestions();
        } else {
            showError(data.error || 'Failed to reset round');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function createNewRound() {
    const name = document.getElementById('newRoundName').value.trim();
    const number = document.getElementById('newRoundNumber').value;
    const questions = document.getElementById('newTotalQuestions').value;
    
    if (!name || !number || !questions) {
        showError('Please fill all fields');
        return;
    }
    
    try {
        const response = await fetch('update_round.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'create_round',
                round_name: name,
                round_number: number,
                total_questions: questions
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Round created successfully!');
            
            // Clear form
            document.getElementById('newRoundName').value = '';
            document.getElementById('newRoundNumber').value = '';
            document.getElementById('newTotalQuestions').value = '';
            
            // Reload page after delay
            setTimeout(() => location.reload(), 1500);
        } else {
            showError(data.error || 'Failed to create round');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

function updateRoundUI(roundId, isActive) {
    const roundCard = document.querySelector(`.round-card[data-round-id="${roundId}"]`);
    if (!roundCard) return;
    
    if (isActive) {
        roundCard.classList.add('active');
        roundCard.querySelector('.round-status').textContent = 'ACTIVE';
        roundCard.querySelector('.start-btn').disabled = true;
        roundCard.querySelector('.next-btn').disabled = false;
        roundCard.querySelector('.stop-btn').disabled = false;
    } else {
        roundCard.classList.remove('active');
        roundCard.querySelector('.round-status').textContent = 'INACTIVE';
        roundCard.querySelector('.start-btn').disabled = false;
        roundCard.querySelector('.next-btn').disabled = true;
        roundCard.querySelector('.stop-btn').disabled = true;
    }
}

function updateRoundQuestionCount(roundId, questionNum) {
    const roundCard = document.querySelector(`.round-card[data-round-id="${roundId}"]`);
    if (!roundCard) return;
    
    const questionElement = roundCard.querySelector('.round-info .round-stat:nth-child(2) .stat-value');
    const totalQuestions = roundCard.querySelector('.round-info .round-stat:nth-child(2) .stat-value').textContent.split('/')[1];
    
    if (questionElement) {
        questionElement.textContent = `${questionNum}/${totalQuestions}`;
    }
}

// ================================================
// TIMER FUNCTIONS
// ================================================

async function startTimer(seconds, timerName) {
    await startCustomTimer(seconds, timerName);
}

async function startCustomTimer(customSeconds = null, customName = null) {
    const seconds = customSeconds || document.getElementById('timerDuration').value;
    const name = customName || document.getElementById('timerName').value;
    
    if (!seconds || seconds < 1) {
        showError('Please enter valid duration');
        return;
    }
    
    if (!name.trim()) {
        showError('Please enter timer name');
        return;
    }
    
    try {
        const response = await fetch('update_timer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'start_timer',
                duration_seconds: seconds,
                timer_name: name
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Timer started on scoreboard!');
            currentTimer = data.timer;
            updateTimerPreview();
        } else {
            showError(data.error || 'Failed to start timer');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function pauseTimer() {
    try {
        const response = await fetch('update_timer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'pause_timer'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Timer paused!');
            updateTimerPreview();
        } else {
            showError(data.error || 'Failed to pause timer');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function stopTimer() {
    if (!confirm('Stop the active timer?')) return;
    
    try {
        const response = await fetch('update_timer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'stop_timer'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Timer stopped!');
            currentTimer = null;
            updateTimerPreview();
        } else {
            showError(data.error || 'Failed to stop timer');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function resetTimer() {
    if (!currentTimer) {
        showError('No active timer to reset');
        return;
    }
    
    try {
        const response = await fetch('update_timer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'reset_timer',
                timer_id: currentTimer.id
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Timer reset!');
            updateTimerPreview();
        } else {
            showError(data.error || 'Failed to reset timer');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function updateTimerPreview() {
    try {
        const response = await fetch('get_active_timer.php');
        const data = await response.json();
        
        const preview = document.getElementById('liveTimerPreview');
        
        if (data.active && data.remaining_seconds > 0) {
            currentTimer = data;
            
            // Format time
            const minutes = Math.floor(data.remaining_seconds / 60);
            const seconds = data.remaining_seconds % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            preview.innerHTML = `
                <div class="timer-name">${data.timer_name}</div>
                <div class="timer-countdown">${timeString}</div>
                <div class="timer-status">${data.remaining_seconds <= 10 ? 'ENDING SOON' : 'RUNNING'}</div>
            `;
            
            // Update colors based on time
            if (data.remaining_seconds <= 10) {
                preview.style.borderColor = '#ff5555';
                preview.querySelector('.timer-countdown').style.color = '#ff5555';
            } else if (data.remaining_seconds <= 30) {
                preview.style.borderColor = '#ff9900';
                preview.querySelector('.timer-countdown').style.color = '#ff9900';
            } else {
                preview.style.borderColor = '#00ff88';
                preview.querySelector('.timer-countdown').style.color = '#00ff88';
            }
        } else {
            currentTimer = null;
            preview.innerHTML = `
                <div class="timer-name">No Active Timer</div>
                <div class="timer-countdown">00:00</div>
                <div class="timer-status">IDLE</div>
            `;
            preview.style.borderColor = 'rgba(0, 255, 136, 0.3)';
            preview.querySelector('.timer-countdown').style.color = '#00ff88';
        }
    } catch (error) {
        console.log('Timer preview error:', error);
    }
}

// ================================================
// ROUND DISPLAY FUNCTIONS
// ================================================

async function triggerRoundDisplay(roundNumber) {
    try {
        const response = await fetch('trigger_display.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'show_round_scores',
                round_number: roundNumber
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(`Round ${roundNumber} scores displayed on scoreboard!`);
        } else {
            showError(data.error || 'Failed to trigger display');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function triggerTotalDisplay() {
    try {
        const response = await fetch('trigger_display.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'show_total_scores'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Total scores displayed on scoreboard!');
        } else {
            showError(data.error || 'Failed to trigger display');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

// ================================================
// QUESTION-WISE SCORE FUNCTIONS
// ================================================

async function loadRoundQuestions() {
    currentRound = parseInt(document.getElementById('selectedRound').value);
    currentQuestion = 1;
    
    try {
        // Load round data
        const response = await fetch('get_round_data.php?round=' + currentRound);
        const data = await response.json();
        
        if (data.success) {
            roundData = data.round;
            document.getElementById('totalQuestions').textContent = roundData.total_questions;
            document.getElementById('currentQuestionNum').textContent = currentQuestion;
            
            // Load teams with their current status
            await loadTeamsForQuestion();
        }
    } catch (error) {
        console.error('Error loading round:', error);
        showError('Failed to load round data');
    }
}

async function loadTeamsForQuestion() {
    try {
        const response = await fetch('get_teams_question_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                round: currentRound,
                question: currentQuestion
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateTeamsGrid(data.teams);
            updateQuestionLog(data.log);
        }
    } catch (error) {
        console.error('Error loading teams:', error);
    }
}

function updateTeamsGrid(teams) {
    const grid = document.getElementById('teamsScoreGrid');
    grid.innerHTML = '';
    
    teams.forEach(team => {
        const isCorrect = teamAnswers[team.id] === 'correct';
        const isIncorrect = teamAnswers[team.id] === 'incorrect';
        
        const card = document.createElement('div');
        card.className = 'team-score-card';
        card.style.borderColor = team.color_code;
        
        card.innerHTML = `
            <div class="team-score-header">
                <div class="team-color-indicator" style="background: ${team.color_code}"></div>
                <div class="team-name-display">${team.team_name}</div>
            </div>
            
            <div class="team-stats">
                <div class="team-stat-item">
                    <span style="color: #28a745">Correct:</span>
                    <span class="team-stat-value">${team[`round${currentRound}_correct`] || 0}</span>
                </div>
                <div class="team-stat-item">
                    <span style="color: #dc3545">Incorrect:</span>
                    <span class="team-stat-value">${team[`round${currentRound}_incorrect`] || 0}</span>
                </div>
                <div class="team-stat-item">
                    <span>Round Score:</span>
                    <span class="team-stat-value">${team[`round${currentRound}_score`] || 0}</span>
                </div>
            </div>
            
            <div class="team-total-score">
                Total: ${team.total_score}
            </div>
            
            <div class="score-actions">
                <button type="button" class="score-action-btn correct-btn ${isCorrect ? 'active' : ''}" 
                        onclick="markAnswer(${team.id}, 'correct')">
                    <i class="fas fa-check"></i> Correct (+${roundData.points_per_correct})
                </button>
                <button type="button" class="score-action-btn incorrect-btn ${isIncorrect ? 'active' : ''}"
                        onclick="markAnswer(${team.id}, 'incorrect')">
                    <i class="fas fa-times"></i> Incorrect (${roundData.points_per_incorrect})
                </button>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

async function markAnswer(teamId, answer) {
    if (teamAnswers[teamId] === answer) {
        delete teamAnswers[teamId];
    } else {
        teamAnswers[teamId] = answer;
    }
    
    await loadTeamsForQuestion();
    
    try {
        const response = await fetch('update_question_score.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                team_id: teamId,
                round: currentRound,
                question: currentQuestion,
                answer: answer,
                points_per_correct: roundData.points_per_correct,
                points_per_incorrect: roundData.points_per_incorrect
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(`Updated ${answer} answer for team`);
            
            if (data.should_advance) {
                setTimeout(() => changeQuestion(1), 500);
            }
        }
    } catch (error) {
        showError('Failed to save answer');
    }
}

function changeQuestion(direction) {
    const newQuestion = currentQuestion + direction;
    const totalQuestions = parseInt(document.getElementById('totalQuestions').textContent);
    
    if (newQuestion < 1 || newQuestion > totalQuestions) return;
    
    if (Object.keys(teamAnswers).length > 0) {
        if (!confirm(`You have ${Object.keys(teamAnswers).length} unsaved answers. Move to next question?`)) {
            return;
        }
    }
    
    currentQuestion = newQuestion;
    document.getElementById('currentQuestionNum').textContent = currentQuestion;
    teamAnswers = {};
    loadTeamsForQuestion();
}

function jumpToQuestion() {
    const jumpTo = parseInt(document.getElementById('jumpToQuestion').value);
    const totalQuestions = parseInt(document.getElementById('totalQuestions').textContent);
    
    if (jumpTo < 1 || jumpTo > totalQuestions) {
        alert(`Please enter a number between 1 and ${totalQuestions}`);
        return;
    }
    
    if (Object.keys(teamAnswers).length > 0) {
        if (!confirm(`You have unsaved answers. Jump to question ${jumpTo}?`)) {
            return;
        }
    }
    
    currentQuestion = jumpTo;
    document.getElementById('currentQuestionNum').textContent = currentQuestion;
    teamAnswers = {};
    loadTeamsForQuestion();
}
// ================================================
// CLOSE ROUND DISPLAY FUNCTION
// ================================================

async function closeRoundDisplay() {
    try {
        const response = await fetch('trigger_display.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'close_round_display'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('✅ Round display closed on scoreboard!');
        } else {
            showError(data.error || 'Failed to close display');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}
async function markAllCorrect() {
    if (!confirm(`Mark ALL teams as CORRECT for Question ${currentQuestion}?`)) return;
    
    try {
        const response = await fetch('bulk_update_question.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                round: currentRound,
                question: currentQuestion,
                answer: 'correct',
                points_per_correct: roundData.points_per_correct
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('All teams marked correct!');
            teamAnswers = {};
            await loadTeamsForQuestion();
        }
    } catch (error) {
        showError('Failed to mark all correct');
    }
}

async function markAllIncorrect() {
    if (!confirm(`Mark ALL teams as INCORRECT for Question ${currentQuestion}?`)) return;
    
    try {
        const response = await fetch('bulk_update_question.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                round: currentRound,
                question: currentQuestion,
                answer: 'incorrect',
                points_per_incorrect: roundData.points_per_incorrect
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('All teams marked incorrect!');
            teamAnswers = {};
            await loadTeamsForQuestion();
        }
    } catch (error) {
        showError('Failed to mark all incorrect');
    }
}

async function resetQuestion() {
    if (!confirm(`Reset ALL answers for Question ${currentQuestion}?`)) return;
    
    try {
        const response = await fetch('reset_question.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                round: currentRound,
                question: currentQuestion
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Question answers reset!');
            teamAnswers = {};
            await loadTeamsForQuestion();
        }
    } catch (error) {
        showError('Failed to reset question');
    }
}

function updateQuestionLog(logEntries) {
    const logContainer = document.getElementById('questionLog');
    logContainer.innerHTML = '';
    
    if (!logEntries || logEntries.length === 0) {
        logContainer.innerHTML = '<div class="log-entry" style="text-align: center; color: #aaa;">No answers recorded yet</div>';
        return;
    }
    
    logEntries.forEach(entry => {
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry ${entry.is_correct ? 'correct' : 'incorrect'}`;
        
        const points = entry.is_correct ? 
            `+${entry.points}` : 
            entry.points;
            
        const pointsClass = entry.is_correct ? 'positive' : 'negative';
        
        logEntry.innerHTML = `
            <span class="log-team">${entry.team_name}</span> - 
            ${entry.is_correct ? 'Correct' : 'Incorrect'}
            <span class="log-points ${pointsClass}">${points} pts</span>
            <br>
            <small style="color: #aaa;">${entry.added_by} at ${entry.time}</small>
        `;
        
        logContainer.appendChild(logEntry);
    });
}

// ================================================
// UTILITY FUNCTIONS
// ================================================

async function executeAction(action) {
    if (action === 'reset') {
        if (!confirm('Are you sure you want to reset ALL scores to zero?\n\nThis action cannot be undone!')) {
            return;
        }
    }
    
    if (action === 'random') {
        if (!confirm('Are you sure you want to add random points to all teams?')) {
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('action', action);
    
    try {
        const response = await fetch('update_score.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('✅ Action executed successfully!');
            setTimeout(() => location.reload(), 1000);
        } else {
            showError(data.error || 'Failed to execute action');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

async function addNewTeam() {
    const teamName = prompt('Enter new team name:');
    if (!teamName || teamName.trim() === '') return;
    
    const colors = [
        '#FF6600', '#FFCC00', '#0066FF', '#00CC66', '#FF3366',
        '#9933FF', '#00CCCC', '#FF9900', '#3399FF', '#FF33CC'
    ];
    const color = colors[Math.floor(Math.random() * colors.length)];
    
    const formData = new FormData();
    formData.append('action', 'add_team');
    formData.append('team_name', teamName.trim());
    formData.append('color', color);
    
    try {
        const response = await fetch('update_score.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('✅ Team "' + teamName + '" added successfully!');
            setTimeout(() => location.reload(), 1000);
        } else {
            showError('Failed to add team');
        }
    } catch (error) {
        showError('Error: ' + error);
    }
}

function showSuccess(message) {
    const msg = document.getElementById('successMsg');
    msg.textContent = message;
    msg.style.display = 'block';
    document.getElementById('errorMsg').style.display = 'none';
    
    setTimeout(() => {
        msg.style.display = 'none';
    }, 5000);
}

function showError(message) {
    const msg = document.getElementById('errorMsg');
    msg.textContent = message;
    msg.style.display = 'block';
    document.getElementById('successMsg').style.display = 'none';
    
    setTimeout(() => {
        msg.style.display = 'none';
    }, 5000);
}
</script>
</body>
</html>