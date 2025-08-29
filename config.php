<?php
// Database connection
$host = 'localhost';
$dbname = 'employee_evaluation_system';
$username = 'root';
$password = 'admin@123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

session_start();

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? 'employee';
}

// Get competencies by category
function getCompetenciesByCategory($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM competencies WHERE category_id = ? AND is_active = 1");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all competency categories
function getCompetencyCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM competency_categories WHERE is_active = 1 ORDER BY sort_order");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get current evaluation period
function getCurrentEvaluationPeriod() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM evaluation_periods WHERE is_active = 1 AND end_date >= CURDATE() ORDER BY start_date LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if user has completed evaluation for current period
function hasCompletedEvaluation($user_id, $period_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM evaluations 
                          WHERE employee_id = (SELECT employee_id FROM employees WHERE user_id = ?) 
                          AND period_id = ? AND status = 'approved'");
    $stmt->execute([$user_id, $period_id]);
    return $stmt->fetchColumn() > 0;
}
?>