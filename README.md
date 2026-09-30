# Real-Time Competition & Quiz Scoreboard System

A dynamic, web-based live scoring and game-master administration platform built with **PHP**, **JavaScript (AJAX)**, and **MySQL**. Ideal for school competitions, university hackathons, quiz bowls, and corporate trivia events.

## ✨ Key Features
- **Audience & Projector Display**: High-contrast, full-screen live scoreboard interface displaying real-time team standings and point totals (`index.php`).
- **Game Master Admin Console**: Centralized control panel to add teams, update scores, advance rounds, and control display modes (`admin.php`, `display_control.php`).
- **Live Countdown Timer**: Synchronized competition timer with start, pause, resume, and emergency stop commands (`get_active_timer.php`, `update_timer.php`).
- **Round & Question Management**: Bulk update question banks, toggle active questions, and track round progress (`bulk_update_question.php`, `get_round_data.php`).
- **Zero-Refresh Real-Time Sync**: Lightweight AJAX polling engine ensures audience displays update instantly without manual page reloads.

## 🛠️ Technology Stack
- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB (`databasestructure.txt`)
- **Real-Time Client**: Vanilla JavaScript (Fetch / AJAX Polling)
- **Styling**: Responsive CSS3 with large-format display optimization

## 🚀 Setup Instructions
1. Import table structures from `databasestructure.txt` into your MySQL database.
2. Configure database connection parameters in `config.php`:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "scoreboard_db";
   ```
3. Host on Apache (XAMPP / WAMP) or launch PHP's built-in server:
   ```bash
   php -S localhost:8080
   ```
4. Open `http://localhost:8080/index.php` on the projector display and `http://localhost:8080/admin.php` for the game master.
