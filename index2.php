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

function redirectIfNotLoggedIn() {
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

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        // Get employee ID if exists
        $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($employee) {
            $_SESSION['employee_id'] = $employee['employee_id'];
        }
        
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid username or password";
    }
}

// Handle evaluation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $period = getCurrentEvaluationPeriod();
    
    if (!$period) {
        $_SESSION['error'] = "No active evaluation period found";
        header('Location: self_evaluation.php');
        exit();
    }
    
    // Get employee ID
    $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        $_SESSION['error'] = "Employee record not found";
        header('Location: self_evaluation.php');
        exit();
    }
    
    // Create evaluation record
    $stmt = $pdo->prepare("INSERT INTO evaluations (employee_id, period_id, evaluation_type, status) 
                          VALUES (?, ?, 'self', 'submitted')");
    $stmt->execute([$employee['employee_id'], $period['period_id']]);
    $evaluation_id = $pdo->lastInsertId();
    
    // Save scores
    foreach ($_POST['scores'] as $competency_id => $score) {
        $rating = getRatingFromScore($score);
        $stmt = $pdo->prepare("INSERT INTO evaluation_scores (evaluation_id, competency_id, score, rating) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$evaluation_id, $competency_id, $score, $rating]);
    }
    
    // Save comments
    if (!empty($_POST['general_comments'])) {
        $stmt = $pdo->prepare("UPDATE evaluations SET general_comments = ? WHERE evaluation_id = ?");
        $stmt->execute([$_POST['general_comments'], $evaluation_id]);
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
        .indicator {
            padding: 10px;
            border-left: 4px solid #f8f9fa;
            margin-bottom: 10px;
        }
        .positive {
            border-left-color: #28a745;
        }
        .negative {
            border-left-color: #dc3545;
        }
        .card-dashboard {
            transition: transform 0.2s;
            border-left: 4px solid #0d6efd;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
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

                <?php if (!isLoggedIn()): ?>
                <!-- Login Form (shown when not logged in) -->
                <div class="row justify-content-center mt-5">
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow">
                            <div class="card-body p-5">
                                <h2 class="text-center mb-4">Login</h2>
                                <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Dashboard Content (shown when logged in) -->
                <?php
                $period = getCurrentEvaluationPeriod();
                $completed = false;
                if ($period) {
                    $completed = hasCompletedEvaluation($_SESSION['user_id'], $period['period_id']);
                }
                ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($period): ?>
                        <div class="alert alert-info py-2">
                            <i class="fas fa-calendar-alt me-1"></i> 
                            Current Evaluation Period: <?php echo $period['period_name']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card card-dashboard">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Current Period</h6>
                                        <h4 class="card-text">
                                            <?php echo $period ? $period['period_name'] : 'None'; ?>
                                        </h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card card-dashboard">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Evaluation Status</h6>
                                        <h4 class="card-text">
                                            <?php echo $completed ? 'Completed' : 'Pending'; ?>
                                        </h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clipboard-check fa-2x text-<?php echo $completed ? 'success' : 'warning'; ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card card-dashboard">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Your Role</h6>
                                        <h4 class="card-text text-capitalize">
                                            <?php echo str_replace('_', ' ', getUserRole()); ?>
                                        </h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-tie fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card card-dashboard">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted">Total Evaluations</h6>
                                        <h4 class="card-text">5</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-bar fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Evaluations</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Period</th>
                                                <th>Type</th>
                                                <th>Score</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Q3 2023</td>
                                                <td>Self</td>
                                                <td><span class="badge bg-success">8.7</span></td>
                                                <td>Approved</td>
                                                <td>Sep 15, 2023</td>
                                            </tr>
                                            <tr>
                                                <td>Q2 2023</td>
                                                <td>Self</td>
                                                <td><span class="badge bg-primary">7.8</span></td>
                                                <td>Approved</td>
                                                <td>Jun 20, 2023</td>
                                            </tr>
                                            <tr>
                                                <td>Q1 2023</td>
                                                <td>Self</td>
                                                <td><span class="badge bg-warning">6.2</span></td>
                                                <td>Approved</td>
                                                <td>Mar 10, 2023</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if ($period && !$completed): ?>
                                    <a href="self_evaluation.php" class="btn btn-primary mb-2">
                                        <i class="fas fa-clipboard-check me-1"></i> Complete Self Evaluation
                                    </a>
                                    <?php elseif ($period): ?>
                                    <button class="btn btn-success mb-2" disabled>
                                        <i class="fas fa-check-circle me-1"></i> Evaluation Completed
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="profile.php" class="btn btn-outline-primary mb-2">
                                        <i class="fas fa-user-cog me-1"></i> Update Profile
                                    </a>
                                    
                                    <?php if (in_array(getUserRole(), ['manager', 'hr_admin', 'system_admin'])): ?>
                                    <a href="team_evaluations.php" class="btn btn-outline-info mb-2">
                                        <i class="fas fa-users me-1"></i> Team Evaluations
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array(getUserRole(), ['hr_admin', 'system_admin'])): ?>
                                    <a href="reports.php" class="btn btn-outline-secondary mb-2">
                                        <i class="fas fa-chart-bar me-1"></i> View Reports
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($period): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Evaluation Period</h5>
                            </div>
                            <div class="card-body">
                                <h6><?php echo $period['period_name']; ?></h6>
                                <p class="text-muted mb-2">
                                    <?php echo date('M j, Y', strtotime($period['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($period['end_date'])); ?>
                                </p>
                                
                                <?php if ($period['evaluation_deadline']): ?>
                                <p class="small mb-0">
                                    <strong>Deadline:</strong> 
                                    <?php echo date('M j, Y', strtotime($period['evaluation_deadline'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>