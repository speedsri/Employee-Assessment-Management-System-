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

// Session management
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function checkAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Get user role
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_evaluation'])) {
        submitEvaluation($_POST);
    } elseif (isset($_POST['login'])) {
        handleLogin($_POST);
    }
}

function handleLogin($data) {
    global $pdo;
    $username = $data['username'];
    $password = $data['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = "Invalid username or password";
        header('Location: login.php');
        exit();
    }
}

function submitEvaluation($data) {
    global $pdo;
    $period = getCurrentEvaluationPeriod();
    
    if (!$period) {
        $_SESSION['error'] = "No active evaluation period found";
        return;
    }
    
    // Get employee ID
    $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        $_SESSION['error'] = "Employee record not found";
        return;
    }
    
    // Create evaluation record
    $stmt = $pdo->prepare("INSERT INTO evaluations (employee_id, period_id, evaluation_type, status) 
                          VALUES (?, ?, 'self', 'submitted')");
    $stmt->execute([$employee['employee_id'], $period['period_id']]);
    $evaluation_id = $pdo->lastInsertId();
    
    // Save scores
    foreach ($data['scores'] as $competency_id => $score) {
        $rating = getRatingFromScore($score);
        $stmt = $pdo->prepare("INSERT INTO evaluation_scores (evaluation_id, competency_id, score, rating) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$evaluation_id, $competency_id, $score, $rating]);
    }
    
    // Save comments
    if (!empty($data['comments'])) {
        $stmt = $pdo->prepare("UPDATE evaluations SET general_comments = ? WHERE evaluation_id = ?");
        $stmt->execute([$data['comments'], $evaluation_id]);
    }
    
    $_SESSION['success'] = "Evaluation submitted successfully!";
    header('Location: index.php');
    exit();
}

function getRatingFromScore($score) {
    if ($score >= 9.0) return 'exceptional';
    if ($score >= 7.5) return 'exceeds_expectations';
    if ($score >= 5.0) return 'meets_expectations';
    if ($score >= 3.0) return 'needs_improvement';
    return 'unsatisfactory';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .competency-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .competency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .rating-slider {
            width: 100%;
        }
        .nav-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .main-content {
            padding-top: 20px;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand nav-brand" href="index.php">
                <i class="fas fa-chart-line me-2"></i>Employee Evaluation System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if (isLoggedIn()): ?>
            <div class="col-md-3 col-lg-2 sidebar d-md-block">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                    <a href="self_evaluation.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-check me-2"></i> Self Evaluation
                    </a>
                    <?php if (in_array(getUserRole(), ['manager', 'hr_admin', 'system_admin'])): ?>
                    <a href="team_evaluations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Team Evaluations
                    </a>
                    <?php endif; ?>
                    <?php if (in_array(getUserRole(), ['hr_admin', 'system_admin'])): ?>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <a href="management.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Management
                    </a>
                    <?php endif; ?>
                    <a href="help.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-question-circle me-2"></i> Help
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="<?php echo isLoggedIn() ? 'col-md-9 col-lg-10' : 'col-12'; ?> main-content">
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>