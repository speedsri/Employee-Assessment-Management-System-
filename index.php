<?php
// index.php
require_once 'config.php';
checkAuth();

// Get user information
$user_id = $_SESSION['user_id'];
$role = getUserRole();

// Get current evaluation period
$period = getCurrentEvaluationPeriod();

// Check if user has completed evaluation for current period
$completed = false;
if ($period) {
    $completed = hasCompletedEvaluation($user_id, $period['period_id']);
}

// Get recent evaluations
function getRecentEvaluations($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, ep.period_name, ep.start_date, ep.end_date 
                          FROM evaluations e 
                          JOIN evaluation_periods ep ON e.period_id = ep.period_id 
                          WHERE e.employee_id = (SELECT employee_id FROM employees WHERE user_id = ?) 
                          ORDER BY e.created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$recent_evaluations = getRecentEvaluations($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-dashboard {
            transition: transform 0.2s;
            border-left: 4px solid #0d6efd;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .progress {
            height: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
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
                                            <?php echo str_replace('_', ' ', $role); ?>
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
                                        <h4 class="card-text"><?php echo count($recent_evaluations); ?></h4>
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
                                <?php if (count($recent_evaluations) > 0): ?>
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
                                            <?php foreach ($recent_evaluations as $evaluation): ?>
                                            <tr>
                                                <td><?php echo $evaluation['period_name']; ?></td>
                                                <td class="text-capitalize"><?php echo $evaluation['evaluation_type']; ?></td>
                                                <td>
                                                    <?php if ($evaluation['overall_score']): ?>
                                                    <span class="badge bg-<?php 
                                                        if ($evaluation['overall_score'] >= 9) echo 'success';
                                                        elseif ($evaluation['overall_score'] >= 7.5) echo 'primary';
                                                        elseif ($evaluation['overall_score'] >= 5) echo 'warning';
                                                        else echo 'danger';
                                                    ?>">
                                                        <?php echo $evaluation['overall_score']; ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-capitalize"><?php echo str_replace('_', ' ', $evaluation['status']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($evaluation['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No evaluations found.</p>
                                <?php endif; ?>
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
                                    
                                    <?php if (in_array($role, ['manager', 'hr_admin', 'system_admin'])): ?>
                                    <a href="team_evaluations.php" class="btn btn-outline-info mb-2">
                                        <i class="fas fa-users me-1"></i> Team Evaluations
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($role, ['hr_admin', 'system_admin'])): ?>
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
                                
                                <?php 
                                $today = time();
                                $start_date = strtotime($period['start_date']);
                                $end_date = strtotime($period['end_date']);
                                $total_days = $end_date - $start_date;
                                $elapsed_days = $today - $start_date;
                                $percentage = min(100, max(0, ($elapsed_days / $total_days) * 100));
                                ?>
                                
                                <div class="mb-2">
                                    <span class="small text-muted">Progress: <?php echo round($percentage); ?>%</span>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                                
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
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>