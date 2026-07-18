<?php
session_start();
require_once 'config.php';

// Get teams with round scores
$teams = $pdo->query("
    SELECT *, 
    (round1_score + round2_score + round3_score + round4_score) as total_score 
    FROM teams 
    ORDER BY total_score DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent events
$events = $pdo->query("
    SELECT le.*, t.team_name, t.color_code 
    FROM live_events le 
    JOIN teams t ON le.team_id = t.id 
    ORDER BY le.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get active round
$activeRound = $pdo->query("SELECT * FROM rounds WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aptech Quiz Competition - Live Scoreboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --aptech-orange: #FF6600;
            --aptech-yellow: #FFCC00;
            --aptech-dark: #1a1a1a;
            --aptech-light: #f8f9fa;
            --aptech-red: #FF3333;
            --aptech-green: #00CC66;
            --aptech-blue: #0066CC;
            --card-bg: rgba(255, 255, 255, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0a0a0a, #1a1a1a, #2a2a2a);
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .live-display-container {
            display: grid;
            grid-template-rows: auto 1fr auto;
            min-height: 100vh;
            padding: 20px;
            gap: 20px;
            max-width: 100vw;
            overflow: hidden;
            background: 
                radial-gradient(circle at 10% 20%, rgba(255, 102, 0, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 204, 0, 0.1) 0%, transparent 20%);
        }
        
        /* HEADER SECTION */
        .header-section {
            background: linear-gradient(90deg, 
                rgba(26, 26, 26, 0.95), 
                rgba(40, 40, 40, 0.95));
            padding: 20px 40px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid var(--aptech-orange);
            box-shadow: 
                0 10px 30px rgba(255, 102, 0, 0.3),
                inset 0 0 50px rgba(255, 102, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                var(--aptech-orange), 
                var(--aptech-yellow), 
                var(--aptech-orange));
            animation: headerGlow 3s infinite alternate;
        }
        
        @keyframes headerGlow {
            0% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .aptech-logo {
            height: 80px;
            width: auto;
            filter: 
                drop-shadow(0 0 10px rgba(255, 102, 0, 0.5))
                drop-shadow(0 0 20px rgba(255, 204, 0, 0.3));
            animation: logoPulse 2s infinite alternate;
        }
        
        @keyframes logoPulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        
        .competition-info {
            text-align: center;
            flex: 1;
        }
        
        .competition-name {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(90deg, 
                var(--aptech-yellow), 
                var(--aptech-orange),
                var(--aptech-yellow));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            animation: textGlow 2s infinite alternate;
        }
        
        @keyframes textGlow {
            0% { 
                text-shadow: 0 2px 10px rgba(255, 102, 0, 0.3);
                background-position: 0% 50%;
            }
            100% { 
                text-shadow: 0 2px 20px rgba(255, 204, 0, 0.5);
                background-position: 100% 50%;
            }
        }
        
        .competition-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .active-round-display {
            background: rgba(0, 102, 255, 0.2);
            padding: 10px 25px;
            border-radius: 15px;
            border: 2px solid #0066ff;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .round-number {
            background: #0066ff;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
        }
        
        .live-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(45deg, var(--aptech-red), #ff6666);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
            animation: livePulse 2s infinite;
            box-shadow: 0 5px 15px rgba(255, 51, 51, 0.4);
        }
        
        @keyframes livePulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 5px 15px rgba(255, 51, 51, 0.4);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 8px 25px rgba(255, 51, 51, 0.6);
            }
        }
        
        /* SCOREBOARD GRID */
        .scoreboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin: 20px 0;
        }
        
        .team-card {
            background: linear-gradient(145deg, 
                rgba(40, 40, 40, 0.9), 
                rgba(30, 30, 30, 0.9));
            border-radius: 20px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            min-height: 320px;
            backdrop-filter: blur(10px);
        }
        
        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, 
                var(--aptech-orange), 
                var(--aptech-yellow), 
                var(--aptech-orange));
            animation: borderGlow 3s infinite linear;
        }
        
        @keyframes borderGlow {
            0% { background-position: -100px 0; }
            100% { background-position: 100px 0; }
        }
        
        .team-card.leader {
            border-color: var(--aptech-yellow);
            box-shadow: 
                0 0 40px rgba(255, 204, 0, 0.3),
                inset 0 0 20px rgba(255, 204, 0, 0.1);
            transform: scale(1.02);
        }
        
        .team-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.5),
                0 0 30px rgba(255, 102, 0, 0.2);
        }
        
        .rank-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 900;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 2;
            border: 2px solid white;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, var(--aptech-yellow), #ff9900);
            color: #000;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #909090);
            color: #000;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #a0522d);
            color: #fff;
        }
        
        .rank-other {
            background: linear-gradient(135deg, var(--aptech-orange), #cc5200);
            color: #fff;
        }
        
        .team-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .team-logo-container {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid;
            box-shadow: 0 0 20px currentColor;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .team-logo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .team-card:hover .team-logo {
            transform: rotate(15deg) scale(1.1);
        }
        
        .team-info {
            flex: 1;
        }
        
        .team-name {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .team-status {
            display: inline-block;
            padding: 6px 15px;
            background: rgba(0, 204, 102, 0.2);
            color: var(--aptech-green);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            animation: statusPulse 2s infinite;
        }
        
        @keyframes statusPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .team-round-scores {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .round-score-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }
        
        .round-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }
        
        .round-value {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--aptech-yellow);
        }
        
        .score-display {
            text-align: center;
            margin: 15px 0;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .score-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 204, 0, 0.1), 
                transparent);
        }
        
        .team-card:hover .score-display::before {
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            100% { left: 100%; }
        }
        
        .score-value {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
            margin: 10px 0;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }
        
        .score-label {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .progress-container {
            margin-top: auto;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .progress-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5);
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease-in-out;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent);
            animation: shimmer 2s infinite;
        }
        
        /* LIVE EVENTS TICKER */
        .live-events-ticker {
            background: linear-gradient(90deg, 
                rgba(255, 102, 0, 0.9), 
                rgba(255, 204, 0, 0.9));
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(255, 102, 0, 0.3);
        }
        
        .ticker-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .ticker-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ticker-header h3 i {
            color: var(--aptech-red);
        }
        
        .ticker-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #000;
            color: var(--aptech-yellow);
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .events-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: thin;
            scrollbar-color: var(--aptech-orange) rgba(0, 0, 0, 0.2);
        }
        
        .events-container::-webkit-scrollbar {
            height: 6px;
        }
        
        .events-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        
        .events-container::-webkit-scrollbar-thumb {
            background: var(--aptech-orange);
            border-radius: 10px;
        }
        
        .event-item {
            flex: 0 0 auto;
            width: 280px;
            background: rgba(0, 0, 0, 0.7);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid;
            animation: slideIn 0.5s ease-out;
            transition: transform 0.3s ease;
        }
        
        .event-item:hover {
            transform: translateY(-5px);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .event-team {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .event-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .event-points {
            font-weight: 800;
            font-size: 1.2rem;
        }
        
        /* FOOTER */
        .display-footer {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(255, 102, 0, 0.3);
            margin-top: 20px;
            backdrop-filter: blur(10px);
        }
        
        .footer-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(90deg, 
                var(--aptech-yellow), 
                var(--aptech-orange));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        
        /* FULLSCREEN BUTTON */
        .fullscreen-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(45deg, var(--aptech-orange), var(--aptech-yellow));
            color: #000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(255, 102, 0, 0.5);
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .fullscreen-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(255, 102, 0, 0.7);
        }
        
        /* SCORE CHANGE ANIMATION */
        .score-change {
            position: absolute;
            font-size: 2.8rem;
            font-weight: 900;
            z-index: 100;
            pointer-events: none;
            animation: scorePop 1.5s ease-out forwards;
            text-shadow: 
                0 0 20px currentColor,
                0 0 40px rgba(255, 255, 255, 0.3);
        }
        
        @keyframes scorePop {
            0% {
                transform: translate(-50%, 0) scale(0.5);
                opacity: 0;
            }
            20% {
                opacity: 1;
                transform: translate(-50%, 0) scale(1.3);
            }
            40% {
                opacity: 1;
                transform: translate(-50%, -40px) scale(1.2);
            }
            100% {
                transform: translate(-50%, -120px) scale(1);
                opacity: 0;
            }
        }
        
        .broadcast-overlay {
            position: fixed;
            top: 20px;
            left: 20px;
            background: linear-gradient(45deg, var(--aptech-red), #ff6666);
            color: white;
            padding: 12px 24px;
            border-radius: 20px;
            font-weight: 700;
            z-index: 999;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.5s ease-out;
            box-shadow: 0 5px 20px rgba(255, 51, 51, 0.5);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes scoreUpdate {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        
        .score-updating {
            animation: scoreUpdate 0.5s ease-in-out;
        }
        
        /* VS MODAL STYLES */
        .vs-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .vs-modal {
            width: 90%;
            max-width: 900px;
            background: linear-gradient(135deg, 
                rgba(20, 20, 20, 0.98), 
                rgba(40, 40, 40, 0.98));
            border-radius: 20px;
            border: 4px solid;
            border-image: linear-gradient(45deg, #ff6600, #ffcc00, #ff6600) 1;
            padding: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 0 100px rgba(255, 102, 0, 0.6),
                0 0 200px rgba(255, 204, 0, 0.4);
        }
        
        .vs-modal.show {
            animation: modalEntrance 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        @keyframes modalEntrance {
            0% {
                transform: scale(0.8) translateY(50px);
                opacity: 0;
            }
            100% {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }
        
        .vs-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 102, 0, 0.5);
        }
        
        .vs-title {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(90deg, #ff6600, #ffcc00, #ff6600);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-align: center;
            flex: 1;
            animation: titleGlow 2s infinite alternate;
        }
        
        @keyframes titleGlow {
            0% { 
                text-shadow: 0 0 20px rgba(255, 102, 0, 0.5);
                background-position: 0% 50%;
            }
            100% { 
                text-shadow: 0 0 30px rgba(255, 204, 0, 0.8);
                background-position: 100% 50%;
            }
        }
        
        .vs-close-btn {
            background: none;
            border: none;
            color: #ffcc00;
            font-size: 3rem;
            cursor: pointer;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-weight: bold;
        }
        
        .vs-close-btn:hover {
            background: rgba(255, 102, 0, 0.2);
            transform: rotate(90deg);
        }
        
        .vs-teams-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0;
            position: relative;
            z-index: 2;
        }
        
        .vs-team {
            flex: 1;
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border: 3px solid;
            transition: all 0.3s;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .vs-team-left {
            border-color: #ff6600;
            box-shadow: 
                0 0 40px rgba(255, 102, 0, 0.4),
                inset 0 0 20px rgba(255, 102, 0, 0.2);
        }
        
        .vs-team-right {
            border-color: #0066ff;
            box-shadow: 
                0 0 40px rgba(0, 102, 255, 0.4),
                inset 0 0 20px rgba(0, 102, 255, 0.2);
        }
        
        .vs-team-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid;
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .vs-team-left .vs-team-logo {
            border-color: #ff6600;
            box-shadow: 0 0 30px rgba(255, 102, 0, 0.5);
        }
        
        .vs-team-right .vs-team-logo {
            border-color: #0066ff;
            box-shadow: 0 0 30px rgba(0, 102, 255, 0.5);
        }
        
        .vs-team-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .vs-team-score {
            font-size: 4rem;
            font-weight: 900;
            margin: 20px 0;
            text-shadow: 
                0 0 20px currentColor,
                0 0 40px rgba(255, 255, 255, 0.3);
            animation: scorePulse 2s infinite;
        }
        
        @keyframes scorePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .vs-team-left .vs-team-score {
            color: #ff6600;
        }
        
        .vs-team-right .vs-team-score {
            color: #0066ff;
        }
        
        .vs-middle-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 40px;
            position: relative;
        }
        
        .vs-vs {
            font-size: 5rem;
            font-weight: 900;
            color: #ffcc00;
            text-shadow: 
                0 0 20px #ffcc00,
                0 0 40px #ff6600;
            margin-bottom: 20px;
            animation: vsPulse 1s infinite alternate;
        }
        
        @keyframes vsPulse {
            0% { 
                transform: scale(1);
                text-shadow: 0 0 20px #ffcc00;
            }
            100% { 
                transform: scale(1.1);
                text-shadow: 0 0 40px #ff6600;
            }
        }
        
        .vs-message {
            font-size: 1.5rem;
            color: #ffcc00;
            font-weight: 600;
            text-align: center;
            max-width: 300px;
            line-height: 1.5;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .vs-animation-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .vs-lightning {
            position: absolute;
            top: 50%;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent, 
                #ffcc00, 
                #ff6600, 
                #ffcc00, 
                transparent);
            animation: lightningFlash 1.5s infinite;
        }
        
        .vs-lightning.left {
            left: 20%;
            transform: translateY(-50%) rotate(-45deg);
        }
        
        .vs-lightning.right {
            right: 20%;
            transform: translateY(-50%) rotate(45deg);
        }
        
        @keyframes lightningFlash {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
        
        .vs-timer-container {
            width: 100%;
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin: 30px 0;
            overflow: hidden;
            position: relative;
        }
        
        .vs-timer-fill {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, 
                #ff6600, 
                #ffcc00, 
                #ff6600);
            border-radius: 5px;
            transform-origin: left center;
            animation: timerShrink 7s linear forwards;
        }
        
        @keyframes timerShrink {
            0% { 
                transform: scaleX(1);
            }
            100% { 
                transform: scaleX(0);
            }
        }
        
        .vs-timer-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ffcc00;
            font-weight: bold;
            font-size: 12px;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }
        
        .vs-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* New animation for sparks */
        .vs-spark {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #ffcc00;
            border-radius: 50%;
            filter: blur(1px);
            animation: sparkFloat 2s infinite;
        }
        
        @keyframes sparkFloat {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                transform: translate(var(--tx, 100px), var(--ty, -100px)) scale(0);
                opacity: 0;
            }
        }
        
        /* ROUND MODAL STYLES */
        .round-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9998;
        }
        
        .round-modal {
            width: 95%;
            max-width: 1400px;
            background: linear-gradient(135deg, 
                rgba(20, 20, 20, 0.98), 
                rgba(40, 40, 40, 0.98));
            border-radius: 25px;
            border: 5px solid #ff6600;
            padding: 40px;
            position: relative;
            max-height: 95vh;
            overflow-y: auto;
            box-shadow: 
                0 0 80px rgba(255, 102, 0, 0.6),
                0 0 160px rgba(255, 204, 0, 0.4);
        }
        
        .round-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid rgba(255, 102, 0, 0.5);
        }
        
        .round-title {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(90deg, #ff6600, #ffcc00, #ff6600);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .round-close-btn {
            background: none;
            border: none;
            color: #ffcc00;
            font-size: 3.5rem;
            cursor: pointer;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-weight: bold;
        }
        
        .round-close-btn:hover {
            background: rgba(255, 102, 0, 0.2);
            transform: rotate(90deg);
        }
        
        .round-info-bar {
            display: flex;
            justify-content: space-around;
            background: rgba(255, 102, 0, 0.15);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 35px;
            border: 2px solid rgba(255, 102, 0, 0.3);
        }
        
        .round-stat {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #ffcc00;
            font-size: 1.3rem;
        }
        
        .round-stat i {
            font-size: 2rem;
            color: #ff6600;
        }
        
        .round-stat strong {
            font-size: 1.5rem;
            color: white;
        }
        
        .round-teams-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .round-team-card {
            background: linear-gradient(145deg, rgba(40, 40, 40, 0.95), rgba(30, 30, 30, 0.95));
            border-radius: 20px;
            padding: 25px;
            border: 3px solid;
            transition: all 0.3s;
            position: relative;
        }
        
        .round-team-card.leader {
            border-color: #ffcc00 !important;
            box-shadow: 0 0 40px rgba(255, 204, 0, 0.4);
            transform: scale(1.02);
        }
        
        .round-team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff6600, #ffcc00, #ff6600);
        }
        
        .round-team-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .round-team-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        
        .round-team-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .round-team-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            flex: 1;
        }
        
        .round-team-rank {
            font-size: 2.5rem;
            font-weight: 900;
            color: #ffcc00;
            min-width: 60px;
            text-align: center;
        }
        
        .round-team-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .round-stat-box {
            text-align: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .round-stat-box:hover {
            transform: translateY(-5px);
            background: rgba(0, 0, 0, 0.6);
        }
        
        .stat-label {
            display: block;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-value.correct {
            color: #00ff88;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.5);
        }
        
        .stat-value.incorrect {
            color: #ff5555;
            text-shadow: 0 0 10px rgba(255, 85, 85, 0.5);
        }
        
        .round-team-score {
            text-align: center;
            font-size: 3rem;
            font-weight: 900;
            color: #ff6600;
            padding: 20px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 15px;
            margin-top: 15px;
            text-shadow: 0 0 20px rgba(255, 102, 0, 0.7);
            border: 2px solid rgba(255, 102, 0, 0.3);
        }
        
        .round-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .round-progress {
            flex: 1;
            margin-right: 30px;
        }
        
        .progress-label {
            color: #ffcc00;
            margin-bottom: 12px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .progress-bar {
            height: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 102, 0, 0.3);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6600, #ffcc00);
            border-radius: 10px;
            transition: width 1s ease;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent);
            animation: shimmer 2s infinite;
        }
        
        .progress-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            text-align: center;
        }
        
        .round-action-btn {
            padding: 18px 40px;
            background: linear-gradient(135deg, #ff6600, #ffcc00);
            color: black;
            border: none;
            border-radius: 15px;
            font-weight: bold;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(255, 102, 0, 0.4);
        }
        
        .round-action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 35px rgba(255, 102, 0, 0.6);
            background: linear-gradient(135deg, #ffcc00, #ff6600);
        }
        
        /* TIMER MODAL STYLES */
        .timer-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.97);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9997;
        }
        
        .timer-modal {
            width: 60%;
            max-width: 800px;
            background: linear-gradient(135deg, 
                rgba(10, 10, 10, 0.98), 
                rgba(30, 30, 30, 0.98));
            border-radius: 25px;
            border: 5px solid #00cc66;
            padding: 40px;
            position: relative;
            text-align: center;
            box-shadow: 
                0 0 80px rgba(0, 204, 102, 0.6),
                0 0 160px rgba(0, 255, 136, 0.4);
        }
        
        .timer-modal-header {
            margin-bottom: 30px;
        }
        
        .timer-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: #00ff88;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(0, 255, 136, 0.7);
            animation: timerTitleGlow 2s infinite alternate;
        }
        
        @keyframes timerTitleGlow {
            0% { text-shadow: 0 0 20px rgba(0, 255, 136, 0.7); }
            100% { text-shadow: 0 0 30px rgba(0, 255, 136, 1); }
        }
        
        .timer-display {
            font-size: 8rem;
            font-weight: 900;
            color: #00ff88;
            font-family: 'Courier New', monospace;
            margin: 30px 0;
            text-shadow: 
                0 0 30px rgba(0, 255, 136, 0.9),
                0 0 60px rgba(0, 255, 136, 0.7);
            animation: timerPulse 1s infinite alternate;
        }
        
        @keyframes timerPulse {
            0% { 
                transform: scale(1);
                text-shadow: 0 0 30px rgba(0, 255, 136, 0.9);
            }
            100% { 
                transform: scale(1.03);
                text-shadow: 0 0 50px rgba(0, 255, 136, 1);
            }
        }
        
        .timer-progress {
            height: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin: 30px 0;
            border: 2px solid rgba(0, 255, 136, 0.3);
        }
        
        .timer-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff88, #00cc66, #00ff88);
            border-radius: 10px;
            transition: width 1s linear;
            position: relative;
        }
        
        .timer-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.4), 
                transparent);
            animation: shimmer 1s infinite;
        }
        
        .timer-status {
            font-size: 1.5rem;
            color: #ffcc00;
            margin-top: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .timer-status.low {
            color: #ff5555;
            animation: lowTimerAlert 1s infinite;
        }
        
        @keyframes lowTimerAlert {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* RESPONSIVE DESIGN */
        @media (max-width: 1200px) {
            .scoreboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .round-teams-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .timer-display {
                font-size: 6rem;
            }
        }
        
        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }
            
            .competition-name {
                font-size: 2rem;
            }
            
            .scoreboard-grid {
                grid-template-columns: 1fr;
            }
            
            .team-name {
                font-size: 1.6rem;
            }
            
            .score-value {
                font-size: 3rem;
            }
            
            .fullscreen-btn {
                bottom: 15px;
                right: 15px;
                width: 45px;
                height: 45px;
            }
            
            .round-modal {
                width: 98%;
                padding: 20px;
            }
            
            .round-title {
                font-size: 2rem;
            }
            
            .round-teams-container {
                grid-template-columns: 1fr;
            }
            
            .round-team-card {
                padding: 20px;
            }
            
            .round-team-stats {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .round-stat {
                font-size: 1rem;
            }
            
            .round-stat i {
                font-size: 1.5rem;
            }
            
            .timer-modal {
                width: 95%;
                padding: 20px;
            }
            
            .timer-display {
                font-size: 4rem;
            }
            
            .timer-title {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .team-round-scores {
                grid-template-columns: 1fr;
            }
            
            .round-stat-box {
                padding: 10px;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .round-team-score {
                font-size: 2.2rem;
                padding: 15px;
            }
            
            .timer-display {
                font-size: 3.5rem;
            }
            
            .round-footer {
                flex-direction: column;
                gap: 20px;
            }
            
            .round-progress {
                margin-right: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    
    <!-- Fullscreen Button -->
    <div class="fullscreen-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
        <i class="fas fa-expand"></i>
    </div>
    
    <!-- VS Modal -->
    <div class="vs-modal-overlay" id="vsModal">
        <div class="vs-modal">
            <div class="vs-modal-header">
                <div class="vs-title" id="vsBattleTitle">⚔️ VS BATTLE ⚔️</div>
                <button class="vs-close-btn" onclick="closeVSmodal()">×</button>
            </div>
            
            <div class="vs-teams-container">
                <div class="vs-team vs-team-left">
                    <div class="vs-team-logo" id="vsTeam1Logo">
                        <!-- Logo will be inserted by JS -->
                    </div>
                    <div class="vs-team-name" id="vsTeam1Name">Team 1</div>
                    <div class="vs-team-score" id="vsTeam1Score">0</div>
                </div>
                
                <div class="vs-middle-section">
                    <div class="vs-vs">VS</div>
                    <div class="vs-message" id="vsMessage">Head to Head Battle</div>
                </div>
                
                <div class="vs-team vs-team-right">
                    <div class="vs-team-logo" id="vsTeam2Logo">
                        <!-- Logo will be inserted by JS -->
                    </div>
                    <div class="vs-team-name" id="vsTeam2Name">Team 2</div>
                    <div class="vs-team-score" id="vsTeam2Score">0</div>
                </div>
            </div>
            
            <div class="vs-animation-container" id="vsAnimationContainer">
                <!-- Sparks will be added by JavaScript -->
            </div>
            
            <div class="vs-timer-container">
                <div class="vs-timer-fill" id="vsTimerFill"></div>
                <div class="vs-timer-text" id="vsTimerText">Closing in 7 seconds</div>
            </div>
            
            <div class="vs-footer">
                <div>Aptech Quiz Competition 2024</div>
                <div id="vsTime">Live Now</div>
            </div>
        </div>
    </div>
    
    <!-- Round Scores Modal -->
    <div class="round-modal-overlay" id="roundModal">
        <div class="round-modal">
            <div class="round-modal-header">
                <div class="round-title" id="roundModalTitle">Round 1 Scores</div>
                <button class="round-close-btn" onclick="closeRoundModal()">×</button>
            </div>
            
            <div class="round-info-bar">
                <div class="round-stat">
                    <i class="fas fa-question-circle"></i>
                    <span>Total Questions: <strong id="roundTotalQuestions">20</strong></span>
                </div>
                <div class="round-stat">
                    <i class="fas fa-star"></i>
                    <span>Points per Correct: <strong id="roundPointsCorrect">10</strong></span>
                </div>
                <div class="round-stat">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Points per Incorrect: <strong id="roundPointsIncorrect">0</strong></span>
                </div>
            </div>
            
            <div class="round-teams-container" id="roundTeamsContainer">
                <!-- Teams will be loaded here -->
            </div>
            
            <div class="round-footer">
                <div class="round-progress">
                    <div class="progress-label">Round Progress</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="roundProgressFill"></div>
                    </div>
                    <div class="progress-text" id="roundProgressText">0/20</div>
                </div>
                
                <button class="round-action-btn" onclick="showNextRound()">
                    <i class="fas fa-arrow-right"></i> Next Round
                </button>
            </div>
        </div>
    </div>
    
    <!-- Timer Modal -->
    <div class="timer-modal-overlay" id="timerModal">
        <div class="timer-modal">
            <div class="timer-modal-header">
                <div class="timer-title" id="timerModalTitle">Quiz Timer</div>
            </div>
            
            <div class="timer-display" id="timerDisplay">00:00</div>
            
            <div class="timer-progress">
                <div class="timer-progress-fill" id="timerProgressFill" style="width: 100%"></div>
            </div>
            
            <div class="timer-status" id="timerStatus">
                <i class="fas fa-clock"></i>
                <span>Time Remaining</span>
            </div>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="live-display-container">
        <header class="header-section">
            <div class="logo-container">
                <img class="aptech-logo" src="./Logo.png" alt="Aptech Logo">
            </div>
            
            <div class="competition-info">
                <h1 class="competition-name">Quiz Competition 2026</h1>
                <p class="competition-subtitle">Knowledge • Challenge • Excellence</p>
                
                <?php if($activeRound): ?>
                <div class="active-round-display">
                    <div class="round-number"><?php echo $activeRound['round_number']; ?></div>
                    <span><?php echo htmlspecialchars($activeRound['round_name']); ?></span>
                    <span style="margin-left: 15px; color: #00ff88;">
                        Q: <?php echo $activeRound['current_question']; ?>/<?php echo $activeRound['total_questions']; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="live-badge">
                <i class="fas fa-circle"></i>
                <span>LIVE NOW</span>
            </div>
        </header>
        
        <main class="scoreboard-grid" id="scoreBoard">
            <?php foreach($teams as $index => $team): 
                $rankClass = 'rank-other';
                if ($index == 0) $rankClass = 'rank-1';
                elseif ($index == 1) $rankClass = 'rank-2';
                elseif ($index == 2) $rankClass = 'rank-3';
                
                $cardClass = $index == 0 ? 'leader' : '';
                
                // Calculate total from rounds
                $totalScore = $team['round1_score'] + $team['round2_score'] + $team['round3_score'] + $team['round4_score'];
            ?>
            <div class="team-card <?php echo $cardClass; ?>" 
                 data-team-id="<?php echo $team['id']; ?>"
                 style="border-color: <?php echo $team['color_code']; ?>">
                
                <div class="rank-badge <?php echo $rankClass; ?>">
                    #<?php echo $index + 1; ?>
                </div>
                
                <div class="team-header">
                    <div class="team-logo-container" style="border-color: <?php echo $team['color_code']; ?>">
                        <?php if($team['logo_url']): ?>
                            <img src="<?php echo htmlspecialchars($team['logo_url']); ?>" class="team-logo" alt="<?php echo htmlspecialchars($team['team_name']); ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: <?php echo $team['color_code']; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; color: white;">
                                <?php echo substr($team['team_name'], 0, 2); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="team-info">
                        <h2 class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></h2>
                        <span class="team-status">
                            <i class="fas fa-circle"></i> ACTIVE
                        </span>
                    </div>
                </div>
                
                <!-- Round Scores Display -->
                <div class="team-round-scores">
                    <div class="round-score-item">
                        <div class="round-label">Round 1</div>
                        <div class="round-value"><?php echo $team['round1_score']; ?></div>
                    </div>
                    <div class="round-score-item">
                        <div class="round-label">Round 2</div>
                        <div class="round-value"><?php echo $team['round2_score']; ?></div>
                    </div>
                    <div class="round-score-item">
                        <div class="round-label">Round 3</div>
                        <div class="round-value"><?php echo $team['round3_score']; ?></div>
                    </div>
                    <div class="round-score-item">
                        <div class="round-label">Round 4</div>
                        <div class="round-value"><?php echo $team['round4_score']; ?></div>
                    </div>
                </div>
                
                <div class="score-display">
                    <div class="score-label">TOTAL SCORE</div>
                    <div class="score-value" id="score-<?php echo $team['id']; ?>"
                         style="color: <?php echo $team['color_code']; ?>">
                        <?php echo number_format($totalScore); ?>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-header">
                        <span>PROGRESS</span>
                        <span id="progress-percent-<?php echo $team['id']; ?>">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-<?php echo $team['id']; ?>"
                             style="background: <?php echo $team['color_code']; ?>; width: 0%">
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
        
        <section class="live-events-ticker">
            <div class="ticker-header">
                <h3><i class="fas fa-bolt"></i> LIVE UPDATES</h3>
                <span class="ticker-badge">
                    <i class="fas fa-sync-alt fa-spin"></i>
                    <span>REAL-TIME</span>
                </span>
            </div>
            
            <div class="events-container" id="eventsContainer">
                <?php foreach($events as $event): ?>
                <div class="event-item" style="border-left-color: <?php echo $event['color_code']; ?>">
                    <div class="event-team" style="color: <?php echo $event['color_code']; ?>">
                        <?php echo htmlspecialchars($event['team_name']); ?>
                    </div>
                    <div class="event-description">
                        <?php echo htmlspecialchars($event['description']); ?>
                    </div>
                    <div class="event-points" style="color: <?php echo $event['points_change'] > 0 ? '#00CC66' : '#FF3333'; ?>">
                        <?php echo $event['points_change'] > 0 ? '+' : ''; ?><?php echo $event['points_change']; ?> points
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($events)): ?>
                <div class="event-item" style="border-left-color: #FF6600; text-align: center; padding: 30px 20px; width: 350px;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: #FF6600; margin-bottom: 15px; display: block;"></i>
                    <div style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.9);">
                        Competition starting soon. Live updates will appear here!
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <footer class="display-footer">
            <div class="footer-stats">
                <div class="stat-item">
                    <div class="stat-value" id="totalTeams"><?php echo count($teams); ?></div>
                    <div class="stat-label">Teams Competing</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="totalPoints">
                        <?php 
                            $totalPoints = 0;
                            foreach($teams as $team) {
                                $totalPoints += ($team['round1_score'] + $team['round2_score'] + $team['round3_score'] + $team['round4_score']);
                            }
                            echo number_format($totalPoints);
                        ?>
                    </div>
                    <div class="stat-label">Total Points</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="activeTeams">
                        <?php echo count($teams); ?>
                    </div>
                    <div class="stat-label">Active Teams</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="topScore">
                        <?php 
                            $topScore = 0;
                            if (!empty($teams)) {
                                $topScore = $teams[0]['round1_score'] + $teams[0]['round2_score'] + $teams[0]['round3_score'] + $teams[0]['round4_score'];
                            }
                            echo number_format($topScore);
                        ?>
                    </div>
                    <div class="stat-label">Leading Score</div>
                </div>
            </div>
        </footer>
    </div>
    
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let lastScores = {};
let shownEventIds = new Set();
let updateInterval = 2000;
let allTeams = [];

// VS Modal Variables
let vsModalActive = false;
let currentVSId = null;
let shownVS = new Set();

// Timer Modal Variables
let timerModalActive = false;
let timerInterval = null;
let timerSeconds = 0;
let totalTimerSeconds = 0;

// Round Modal Variables
let roundModalActive = false;
let currentRound = 1;

<?php foreach($teams as $team): 
    $totalScore = $team['round1_score'] + $team['round2_score'] + $team['round3_score'] + $team['round4_score'];
?>
lastScores[<?php echo $team['id']; ?>] = <?php echo $totalScore; ?>;
allTeams.push({
    id: <?php echo $team['id']; ?>,
    team_name: "<?php echo addslashes($team['team_name']); ?>",
    color_code: "<?php echo $team['color_code']; ?>",
    logo_url: "<?php echo $team['logo_url'] ?? ''; ?>",
    round1_score: <?php echo $team['round1_score']; ?>,
    round2_score: <?php echo $team['round2_score']; ?>,
    round3_score: <?php echo $team['round3_score']; ?>,
    round4_score: <?php echo $team['round4_score']; ?>,
    total_score: <?php echo $totalScore; ?>,
    round1_correct: <?php echo $team['round1_correct'] ?? 0; ?>,
    round1_incorrect: <?php echo $team['round1_incorrect'] ?? 0; ?>,
    round2_correct: <?php echo $team['round2_correct'] ?? 0; ?>,
    round2_incorrect: <?php echo $team['round2_incorrect'] ?? 0; ?>,
    round3_correct: <?php echo $team['round3_correct'] ?? 0; ?>,
    round3_incorrect: <?php echo $team['round3_incorrect'] ?? 0; ?>,
    round4_correct: <?php echo $team['round4_correct'] ?? 0; ?>,
    round4_incorrect: <?php echo $team['round4_incorrect'] ?? 0; ?>
});
<?php endforeach; ?>

$(document).ready(function() {
    updateProgressBars();
    
    fetchLiveUpdates();
    setInterval(fetchLiveUpdates, updateInterval);
    
    autoScrollEvents();
    addVisualEffects();
    
    // Check for updates
    setInterval(checkForDisplayUpdates, 3000);
    
    // Keyboard controls
    $(document).on('keydown', function(e) {
        // Escape key closes any modal
        if (e.key === 'Escape' || e.keyCode === 27) {
            if (vsModalActive) closeVSmodal();
            if (roundModalActive) closeRoundModal();
            if (timerModalActive) closeTimerModal();
            e.preventDefault();
        }
        
        // Space toggles timer pause/resume
        if (e.key === ' ' || e.keyCode === 32) {
            e.preventDefault();
        }
    });
    
    // Check for active timer
    checkForActiveTimer();
    setInterval(checkForActiveTimer, 1000);
});

// ================================================
// LIVE UPDATES FUNCTIONS
// ================================================

function fetchLiveUpdates() {
    $.ajax({
        url: 'get_live_scores.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateTeams(response.teams);
                
                if (response.events && response.events.length > 0) {
                    updateEvents(response.events);
                }
                
                updateStats(response.teams);
                
                // Update active round display
                if (response.active_round) {
                    updateActiveRound(response.active_round);
                }
            }
        },
        error: function(xhr, status, error) {
            console.log('Update error:', error);
        }
    });
}

function updateTeams(teams) {
    teams.forEach(team => {
        let card = $('.team-card[data-team-id="' + team.id + '"]');
        
        if (card.length === 0) return;
        
        let oldScore = lastScores[team.id] || 0;
        let newScore = team.total_score;
        
        if (newScore !== oldScore) {
            let scoreElement = $('#score-' + team.id);
            let change = newScore - oldScore;
            
            // Update score with animation
            scoreElement.text(newScore.toLocaleString());
            
            if (Math.abs(change) > 0) {
                showScoreAnimation(team.id, change, team.color_code);
                scoreElement.addClass('score-updating');
                setTimeout(() => {
                    scoreElement.removeClass('score-updating');
                }, 500);
            }
            
            // Update round scores
            updateTeamRoundScores(team);
            
            lastScores[team.id] = newScore;
            
            const teamIndex = allTeams.findIndex(t => t.id === team.id);
            if (teamIndex !== -1) {
                allTeams[teamIndex] = team;
            }
        }
    });
    
    reorderTeams();
    updateProgressBars();
}

function updateTeamRoundScores(team) {
    // Update round scores display
    const teamCard = $(`.team-card[data-team-id="${team.id}"]`);
    if (teamCard.length) {
        teamCard.find('.team-round-scores .round-score-item:nth-child(1) .round-value').text(team.round1_score);
        teamCard.find('.team-round-scores .round-score-item:nth-child(2) .round-value').text(team.round2_score);
        teamCard.find('.team-round-scores .round-score-item:nth-child(3) .round-value').text(team.round3_score);
        teamCard.find('.team-round-scores .round-score-item:nth-child(4) .round-value').text(team.round4_score);
    }
}

function updateProgressBars() {
    let maxScore = Math.max(...Object.values(lastScores));
    if (maxScore === 0) maxScore = 1;
    
    Object.keys(lastScores).forEach(teamId => {
        let percentage = (lastScores[teamId] / maxScore) * 100;
        $('#progress-' + teamId).css('width', percentage + '%');
        $('#progress-percent-' + teamId).text(Math.round(percentage) + '%');
    });
}

function reorderTeams() {
    let cards = $('.team-card').toArray();
    
    cards.sort((a, b) => {
        let scoreA = parseInt($(a).find('.score-value').text().replace(/,/g, ''));
        let scoreB = parseInt($(b).find('.score-value').text().replace(/,/g, ''));
        return scoreB - scoreA;
    });
    
    $('#scoreBoard').html(cards);
    
    $('.team-card').each(function(index) {
        let rankBadge = $(this).find('.rank-badge');
        rankBadge.removeClass('rank-1 rank-2 rank-3 rank-other');
        
        if (index === 0) {
            rankBadge.addClass('rank-1');
            $(this).addClass('leader');
        } else if (index === 1) {
            rankBadge.addClass('rank-2');
            $(this).removeClass('leader');
        } else if (index === 2) {
            rankBadge.addClass('rank-3');
            $(this).removeClass('leader');
        } else {
            rankBadge.addClass('rank-other');
            $(this).removeClass('leader');
        }
        
        rankBadge.text('#' + (index + 1));
    });
}

function updateEvents(events) {
    events.forEach(event => {
        if (!shownEventIds.has(event.id)) {
            let eventHtml = `
                <div class="event-item" style="border-left-color: ${event.color_code}">
                    <div class="event-team" style="color: ${event.color_code}">
                        ${event.team_name}
                    </div>
                    <div class="event-description">
                        ${event.description}
                    </div>
                    <div class="event-points" style="color: ${event.points_change > 0 ? '#00CC66' : '#FF3333'}">
                        ${event.points_change > 0 ? '+' : ''}${event.points_change} points
                    </div>
                </div>`;
            
            $('#eventsContainer').prepend(eventHtml);
            shownEventIds.add(event.id);
            
            let eventItems = $('#eventsContainer .event-item');
            if (eventItems.length > 8) {
                eventItems.last().remove();
            }
        }
    });
}

function updateStats(teams) {
    if (!teams) return;
    
    let totalPoints = teams.reduce((sum, team) => sum + team.total_score, 0);
    $('#totalPoints').text(totalPoints.toLocaleString());
    
    if (teams.length > 0) {
        $('#topScore').text(teams[0].total_score.toLocaleString());
    }
    
    $('#activeTeams').text(teams.length);
    $('#totalTeams').text(teams.length);
}

function updateActiveRound(roundData) {
    const roundDisplay = $('.active-round-display');
    if (roundDisplay.length && roundData) {
        roundDisplay.find('.round-number').text(roundData.round_number);
        roundDisplay.find('span:nth-child(2)').text(roundData.round_name);
        roundDisplay.find('span:nth-child(3)').html(`Q: <strong>${roundData.current_question}/${roundData.total_questions}</strong>`);
    }
}

// ================================================
// SCORE ANIMATION FUNCTIONS
// ================================================

function showScoreAnimation(teamId, change, color) {
    const scoreElement = $(`#score-${teamId}`);
    if (!scoreElement.length) return;
    
    const scorePos = scoreElement.offset();
    const scoreWidth = scoreElement.width();
    const startLeft = scorePos.left + scoreWidth / 2;
    const startTop = scorePos.top - 20;
    
    const fontSize = Math.abs(change) >= 20 ? '3rem' : 
                    Math.abs(change) >= 10 ? '2.5rem' : '2rem';
    const distance = Math.abs(change) >= 20 ? 120 : 
                    Math.abs(change) >= 10 ? 100 : 80;
    const duration = Math.abs(change) >= 20 ? 1.8 : 
                    Math.abs(change) >= 10 ? 1.5 : 1.2;
    
    const changeElement = $('<div class="score-change">' + 
        (change > 0 ? '+' : '') + change + '</div>');
    
    changeElement.css({
        'position': 'fixed',
        'font-size': fontSize,
        'font-weight': '900',
        'color': change > 0 ? '#00FF88' : '#FF3333',
        'text-shadow': change > 0 
            ? '0 0 20px rgba(0, 255, 136, 0.9), 0 0 40px rgba(0, 255, 136, 0.5)'
            : '0 0 20px rgba(255, 51, 51, 0.9), 0 0 40px rgba(255, 51, 51, 0.5)',
        'z-index': '9999',
        'left': startLeft + 'px',
        'top': startTop + 'px',
        'opacity': '0',
        'pointer-events': 'none',
        'transform': 'translate(-50%, 0)',
        'transition': `all ${duration}s ease-out`
    });
    
    $('body').append(changeElement);
    
    setTimeout(() => {
        changeElement.css({
            'opacity': '1',
            'transform': `translate(-50%, -${distance}px)`,
            'top': (startTop - distance) + 'px'
        });
    }, 10);
    
    setTimeout(() => {
        changeElement.css('opacity', '0');
        setTimeout(() => {
            changeElement.remove();
        }, 300);
    }, duration * 1000);
}

// ================================================
// ROUND MODAL FUNCTIONS
// ================================================

function showRoundScores(roundNumber) {
    currentRound = roundNumber;
    
    // Fetch round data
    $.getJSON('get_round_data.php?round=' + roundNumber)
        .done(data => {
            if (data.success) {
                const round = data.round;
                
                // Update modal title and info
                $('#roundModalTitle').text(round.round_name);
                $('#roundTotalQuestions').text(round.total_questions);
                $('#roundPointsCorrect').text(round.points_per_correct);
                $('#roundPointsIncorrect').text(round.points_per_incorrect);
                
                // Calculate progress
                const progressPercent = (round.current_question / round.total_questions) * 100;
                $('#roundProgressFill').css('width', progressPercent + '%');
                $('#roundProgressText').text(`${round.current_question}/${round.total_questions}`);
                
                // Load teams for this round
                loadRoundTeams(roundNumber, round);
                
                // Show modal
                $('#roundModal').css('display', 'flex');
                roundModalActive = true;
                
                setTimeout(() => {
                    $('.round-modal').addClass('show');
                }, 100);
            }
        })
        .fail(error => {
            console.log('Error loading round data:', error);
            showBroadcastMessage('Failed to load round data', 'error');
        });
}

function loadRoundTeams(roundNumber, roundData) {
    // Sort teams by this round's score
    const roundScoreKey = `round${roundNumber}_score`;
    const sortedTeams = [...allTeams].sort((a, b) => b[roundScoreKey] - a[roundScoreKey]);
    
    const container = $('#roundTeamsContainer');
    container.empty();
    
    sortedTeams.forEach((team, index) => {
        const correctKey = `round${roundNumber}_correct`;
        const incorrectKey = `round${roundNumber}_incorrect`;
        const scoreKey = `round${roundNumber}_score`;
        
        const isLeader = index === 0;
        const borderColor = isLeader ? '#ffcc00' : team.color_code;
        
        const teamCard = $(`
            <div class="round-team-card ${isLeader ? 'leader' : ''}" 
                 style="border-color: ${borderColor}">
                
                <div class="round-team-header">
                    <div class="round-team-logo" style="border-color: ${team.color_code}">
                        ${team.logo_url ? 
                            `<img src="${team.logo_url}" alt="${team.team_name}">` :
                            `<div style="width:100%;height:100%;background:${team.color_code};display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:bold;color:#fff">
                                ${team.team_name.substring(0, 2).toUpperCase()}
                             </div>`
                        }
                    </div>
                    <div class="round-team-name">${team.team_name}</div>
                    <div class="round-team-rank">#${index + 1}</div>
                </div>
                
                <div class="round-team-stats">
                    <div class="round-stat-box">
                        <div class="stat-label">Correct</div>
                        <div class="stat-value correct">${team[correctKey] || 0}</div>
                    </div>
                    <div class="round-stat-box">
                        <div class="stat-label">Incorrect</div>
                        <div class="stat-value incorrect">${team[incorrectKey] || 0}</div>
                    </div>
                    <div class="round-stat-box">
                        <div class="stat-label">Accuracy</div>
                        <div class="stat-value">
                            ${calculateAccuracy(team[correctKey] || 0, team[incorrectKey] || 0)}%
                        </div>
                    </div>
                </div>
                
                <div class="round-team-score">
                    ${team[scoreKey] || 0} Points
                </div>
            </div>
        `);
        
        container.append(teamCard);
    });
}

function calculateAccuracy(correct, incorrect) {
    const total = correct + incorrect;
    if (total === 0) return 0;
    return Math.round((correct / total) * 100);
}

function closeRoundModal() {
    $('.round-modal').removeClass('show');
    
    setTimeout(() => {
        $('#roundModal').css('display', 'none');
        roundModalActive = false;
    }, 300);
}

function showNextRound() {
    const nextRound = currentRound + 1;
    if (nextRound <= 4) {
        closeRoundModal();
        setTimeout(() => {
            showRoundScores(nextRound);
        }, 400);
    } else {
        showBroadcastMessage('This is the final round!', 'info');
    }
}

function showTotalScores() {
    // Show all rounds summary
    $('#roundModalTitle').text('TOTAL SCORES');
    $('#roundTotalQuestions').text('All Rounds');
    $('#roundPointsCorrect').text('-');
    $('#roundPointsIncorrect').text('-');
    
    // Calculate total progress (average of all rounds)
    $.getJSON('get_rounds_progress.php')
        .done(data => {
            if (data.success) {
                const avgProgress = data.average_progress;
                $('#roundProgressFill').css('width', avgProgress + '%');
                $('#roundProgressText').text(`${Math.round(avgProgress)}% Complete`);
            }
        });
    
    // Load teams sorted by total score
    const sortedTeams = [...allTeams].sort((a, b) => b.total_score - a.total_score);
    
    const container = $('#roundTeamsContainer');
    container.empty();
    
    sortedTeams.forEach((team, index) => {
        const totalCorrect = (team.round1_correct || 0) + (team.round2_correct || 0) + 
                            (team.round3_correct || 0) + (team.round4_correct || 0);
        const totalIncorrect = (team.round1_incorrect || 0) + (team.round2_incorrect || 0) + 
                              (team.round3_incorrect || 0) + (team.round4_incorrect || 0);
        
        const isLeader = index === 0;
        const borderColor = isLeader ? '#ffcc00' : team.color_code;
        
        const teamCard = $(`
            <div class="round-team-card ${isLeader ? 'leader' : ''}" 
                 style="border-color: ${borderColor}">
                
                <div class="round-team-header">
                    <div class="round-team-logo" style="border-color: ${team.color_code}">
                        ${team.logo_url ? 
                            `<img src="${team.logo_url}" alt="${team.team_name}">` :
                            `<div style="width:100%;height:100%;background:${team.color_code};display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:bold;color:#fff">
                                ${team.team_name.substring(0, 2).toUpperCase()}
                             </div>`
                        }
                    </div>
                    <div class="round-team-name">${team.team_name}</div>
                    <div class="round-team-rank">#${index + 1}</div>
                </div>
                
                <div class="round-team-stats">
                    <div class="round-stat-box">
                        <div class="stat-label">Total Correct</div>
                        <div class="stat-value correct">${totalCorrect}</div>
                    </div>
                    <div class="round-stat-box">
                        <div class="stat-label">Total Incorrect</div>
                        <div class="stat-value incorrect">${totalIncorrect}</div>
                    </div>
                    <div class="round-stat-box">
                        <div class="stat-label">Accuracy</div>
                        <div class="stat-value">
                            ${calculateAccuracy(totalCorrect, totalIncorrect)}%
                        </div>
                    </div>
                </div>
                
                <div class="round-team-score">
                    ${team.total_score} Total Points
                </div>
            </div>
        `);
        
        container.append(teamCard);
    });
    
    // Show modal
    $('#roundModal').css('display', 'flex');
    roundModalActive = true;
    
    setTimeout(() => {
        $('.round-modal').addClass('show');
    }, 100);
}

// ================================================
// VS MODAL FUNCTIONS
// ================================================

function showVSmodal(vsData) {
    if (!vsData || shownVS.has(vsData.id)) return;
    
    currentVSId = vsData.id;
    shownVS.add(vsData.id);
    
    // Update modal content
    document.getElementById('vsBattleTitle').textContent = `⚔️ ${vsData.mode.toUpperCase()} ⚔️`;
    document.getElementById('vsTeam1Name').textContent = vsData.team1_name;
    document.getElementById('vsTeam2Name').textContent = vsData.team2_name;
    document.getElementById('vsTeam1Score').textContent = vsData.team1_score;
    document.getElementById('vsTeam2Score').textContent = vsData.team2_score;
    document.getElementById('vsMessage').textContent = vsData.message;
    document.getElementById('vsTime').textContent = 'Live Now';
    
    // Set team logos
    updateVSLogo('vsTeam1Logo', vsData.team1_logo, vsData.team1_color, vsData.team1_name);
    updateVSLogo('vsTeam2Logo', vsData.team2_logo, vsData.team2_color, vsData.team2_name);
    
    // Set timer duration
    const duration = vsData.duration || 7;
    
    // Create sparks animation
    createSparks();
    
    // Show modal with animation
    const modal = document.getElementById('vsModal');
    modal.style.display = 'flex';
    setTimeout(() => {
        document.querySelector('.vs-modal').classList.add('show');
    }, 10);
    
    vsModalActive = true;
    
    // Start timer animation
    startVStimer(duration);
    
    // Update timer text
    updateTimerText(duration);
    
    // Auto close after duration
    setTimeout(() => {
        if (vsModalActive) {
            closeVSmodal();
        }
    }, duration * 1000);
    
    // Mark as shown in database
    markVSasShown(vsData.id);
}

function closeVSmodal() {
    const modal = document.getElementById('vsModal');
    document.querySelector('.vs-modal').classList.remove('show');
    
    // Reset timer animation
    const timerFill = document.getElementById('vsTimerFill');
    timerFill.style.animation = 'none';
    
    setTimeout(() => {
        modal.style.display = 'none';
        vsModalActive = false;
        currentVSId = null;
        
        // Clear sparks
        const container = document.getElementById('vsAnimationContainer');
        container.innerHTML = '';
    }, 300);
}

function updateVSLogo(elementId, logoUrl, color, teamName) {
    const element = document.getElementById(elementId);
    element.style.background = color;
    
    if (logoUrl && logoUrl.trim() !== '') {
        element.innerHTML = `<img src="${logoUrl}" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:bold;color:#fff\'>${teamName.substring(0, 2).toUpperCase()}</div>'">`;
    } else {
        element.innerHTML = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:bold;color:#fff">
            ${teamName.substring(0, 2).toUpperCase()}
        </div>`;
    }
}

function startVStimer(duration) {
    const timerFill = document.getElementById('vsTimerFill');
    // Reset animation
    timerFill.style.animation = 'none';
    
    // Force reflow
    void timerFill.offsetWidth;
    
    // Start new animation
    timerFill.style.animation = `timerShrink ${duration}s linear forwards`;
}

function updateTimerText(seconds) {
    const timerText = document.getElementById('vsTimerText');
    if (seconds > 0) {
        timerText.textContent = `Closing in ${seconds} second${seconds !== 1 ? 's' : ''}`;
    } else {
        timerText.textContent = 'Closing...';
    }
}

function createSparks() {
    const container = document.getElementById('vsAnimationContainer');
    container.innerHTML = '';
    
    // Create 15 random sparks
    for (let i = 0; i < 15; i++) {
        const spark = document.createElement('div');
        spark.className = 'vs-spark';
        
        // Random position
        const left = Math.random() * 100;
        const top = Math.random() * 100;
        
        // Random movement
        const tx = (Math.random() - 0.5) * 200;
        const ty = (Math.random() - 0.5) * 200;
        
        // Random delay
        const delay = Math.random() * 2;
        
        spark.style.cssText = `
            left: ${left}%;
            top: ${top}%;
            --tx: ${tx}px;
            --ty: ${ty}px;
            animation-delay: ${delay}s;
            background: ${i % 3 === 0 ? '#ff6600' : i % 3 === 1 ? '#ffcc00' : '#ff9900'};
        `;
        
        container.appendChild(spark);
    }
}

function markVSasShown(id) {
    fetch('update_vs_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}`
    });
}

// ================================================
// TIMER MODAL FUNCTIONS
// ================================================

function checkForActiveTimer() {
    $.getJSON('get_active_timer.php')
        .done(data => {
            if (data.active && data.remaining_seconds > 0) {
                if (!timerModalActive) {
                    startTimerDisplay(data.timer_name, data.remaining_seconds, data.duration_seconds);
                } else {
                    updateTimerDisplay(data.remaining_seconds, data.duration_seconds);
                }
            } else if (timerModalActive) {
                closeTimerModal();
            }
        })
        .fail(error => {
            console.log('Timer check error:', error);
        });
}

function startTimerDisplay(timerName, seconds, totalSeconds) {
    timerSeconds = seconds;
    totalTimerSeconds = totalSeconds;
    
    $('#timerModalTitle').text(timerName);
    updateTimerDisplay(seconds, totalSeconds);
    
    $('#timerModal').css('display', 'flex');
    timerModalActive = true;
    
    setTimeout(() => {
        $('.timer-modal').addClass('show');
    }, 100);
    
    // Start countdown
    if (timerInterval) clearInterval(timerInterval);
    
    timerInterval = setInterval(() => {
        if (timerSeconds > 0) {
            timerSeconds--;
            updateTimerDisplay(timerSeconds, totalTimerSeconds);
            
            if (timerSeconds <= 0) {
                clearInterval(timerInterval);
                setTimeout(closeTimerModal, 1000);
            }
        }
    }, 1000);
}

function updateTimerDisplay(seconds, totalSeconds) {
    // Format time as MM:SS
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    const timeString = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    
    $('#timerDisplay').text(timeString);
    
    // Update progress bar
    const progressPercent = (seconds / totalSeconds) * 100;
    $('#timerProgressFill').css('width', progressPercent + '%');
    
    // Update status and colors
    const statusElement = $('#timerStatus');
    if (seconds <= 10) {
        statusElement.addClass('low');
        statusElement.html('<i class="fas fa-exclamation-triangle"></i><span>Time Running Out!</span>');
        $('#timerDisplay').css('color', '#ff5555');
        $('#timerProgressFill').css('background', 'linear-gradient(90deg, #ff5555, #ff3333, #ff5555)');
    } else if (seconds <= 30) {
        statusElement.removeClass('low');
        statusElement.html('<i class="fas fa-clock"></i><span>Hurry Up!</span>');
        $('#timerDisplay').css('color', '#ff9900');
        $('#timerProgressFill').css('background', 'linear-gradient(90deg, #ff9900, #ffcc00, #ff9900)');
    } else {
        statusElement.removeClass('low');
        statusElement.html('<i class="fas fa-clock"></i><span>Time Remaining</span>');
        $('#timerDisplay').css('color', '#00ff88');
        $('#timerProgressFill').css('background', 'linear-gradient(90deg, #00ff88, #00cc66, #00ff88)');
    }
}

function closeTimerModal() {
    $('.timer-modal').removeClass('show');
    
    setTimeout(() => {
        $('#timerModal').css('display', 'none');
        timerModalActive = false;
        
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }, 300);
}

// ================================================
// UTILITY FUNCTIONS
// ================================================

function autoScrollEvents() {
    setInterval(() => {
        let container = $('#eventsContainer');
        let scrollLeft = container.scrollLeft();
        let maxScroll = container[0].scrollWidth - container.width();
        
        if (scrollLeft >= maxScroll - 10) {
            container.animate({ scrollLeft: 0 }, 800);
        } else {
            container.animate({ scrollLeft: scrollLeft + 300 }, 800);
        }
    }, 5000);
}

function addVisualEffects() {
    setInterval(() => {
        let cards = $('.team-card').not('.leader');
        if (cards.length > 0) {
            let randomCard = cards.eq(Math.floor(Math.random() * cards.length));
            randomCard.css('box-shadow', '0 0 30px rgba(255, 102, 0, 0.5)');
            
            setTimeout(() => {
                randomCard.css('box-shadow', '');
            }, 1000);
        }
    }, 4000);
    
    setInterval(() => {
        $('.team-card.leader').css('box-shadow', 
            '0 0 40px rgba(255, 204, 0, 0.5), inset 0 0 20px rgba(255, 204, 0, 0.2)');
        
        setTimeout(() => {
            $('.team-card.leader').css('box-shadow', 
                '0 0 40px rgba(255, 204, 0, 0.3), inset 0 0 20px rgba(255, 204, 0, 0.1)');
        }, 500);
    }, 3000);
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.log(`Fullscreen error: ${err.message}`);
        });
        $('.fullscreen-btn i').removeClass('fa-expand').addClass('fa-compress');
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
            $('.fullscreen-btn i').removeClass('fa-compress').addClass('fa-expand');
        }
    }
}

function showBroadcastMessage(message, type = 'info') {
    const colors = {
        info: { bg: '#0066FF', icon: 'info-circle' },
        success: { bg: '#00CC66', icon: 'check-circle' },
        warning: { bg: '#FFCC00', icon: 'exclamation-circle' },
        error: { bg: '#FF3333', icon: 'times-circle' }
    };
    
    const config = colors[type] || colors.info;
    $('.broadcast-overlay').remove();
    
    const overlay = $(`
        <div class="broadcast-overlay">
            <i class="fas fa-${config.icon}"></i>
            <span>${message}</span>
        </div>
    `).css({
        'background': `linear-gradient(45deg, ${config.bg}, ${config.bg}dd)`,
        'border': `2px solid ${config.bg}`
    });
    
    $('body').append(overlay);
    
    setTimeout(() => {
        overlay.animate({ opacity: 0, right: '-100%' }, 500, function() {
            $(this).remove();
        });
    }, 3000);
}

function checkForDisplayUpdates() {
    $.getJSON('check_display_updates.php')
        .done(data => {
            // Check for round scores display
            if (data.show_round_scores) {
                showRoundScores(data.round_number);
            }
            
            // Check for total scores display
            if (data.show_total_scores) {
                showTotalScores();
            }
            
            // NEW: Check if round modal should be closed
            if (data.close_round) {
                if (roundModalActive) {
                    closeRoundModal();
                    showBroadcastMessage('Round display closed', 'info');
                }
            }
            
            // Check for timer updates
            if (data.timer_update) {
                // Timer updates are handled separately
            }
            
            // Check for VS display
            if (data.vs_display) {
                showVSmodal(data.vs_display);
            }
        })
        .fail(error => {
            console.log('Display check error:', error);
        });
}

// Initialize
$(document).ready(function() {
    // Auto-refresh data
    setInterval(fetchLiveUpdates, updateInterval);
    
    // Check for display triggers
    setInterval(checkForDisplayUpdates, 2000);
});
</script>
</body>
</html>