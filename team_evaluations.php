<?php
require_once 'config.php';
checkAuth();

// Only managers and above can access team evaluations
if (!in_array(getUserRole(), ['manager', 'hr_admin', 'system_admin'])) {
    header('Location: index.php');
    exit();
}

// Get team members
function getTeamMembers($manager_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, u.first_name, u.last_name 
                          FROM employees e 
                          JOIN users u ON e.user_id = u.user_id 
                          WHERE e.reporting_manager_id = ?");
    $stmt->execute([$manager_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$team_members = getTeamMembers($_SESSION['employee_id']);
$current_period = getCurrentEvaluationPeriod();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Evaluations - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .evaluation-status {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
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
                    <h1 class="h2">Team Evaluations</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($current_period): ?>
                        <div class="alert alert-info py-2 me-2">
                            <i class="fas fa-calendar-alt me-1"></i> 
                            Current Period: <?php echo $current_period['period_name']; ?>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Team Members</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($team_members) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Position</th>
                                        <th>Evaluation Status</th>
                                        <th>Last Evaluation</th>
                                        <th>Overall Rating</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_members as $member): 
                                        // Get evaluation status for current period
                                        $stmt = $pdo->prepare("SELECT status, overall_score, overall_rating 
                                                             FROM evaluations 
                                                             WHERE employee_id = ? AND period_id = ?");
                                        $stmt->execute([$member['employee_id'], $current_period['period_id']]);
                                        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                    <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <?php echo $member['first_name'] . ' ' . $member['last_name']; ?>
                                                    <div class="text-muted small"><?php echo $member['employee_code']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $stmt = $pdo->prepare("SELECT position_title FROM positions WHERE position_id = ?");
                                            $stmt->execute([$member['position_id']]);
                                            $position = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo $position ? $position['position_title'] : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($evaluation): 
                                                $status_class = [
                                                    'not_started' => 'secondary',
                                                    'draft' => 'warning',
                                                    'in_progress' => 'info',
                                                    'submitted' => 'primary',
                                                    'reviewed' => 'info',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger'
                                                ][$evaluation['status']];
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?> evaluation-status text-capitalize">
                                                <?php echo str_replace('_', ' ', $evaluation['status']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary evaluation-status">Not Started</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT MAX(created_at) as last_eval FROM evaluations WHERE employee_id = ?");
                                            $stmt->execute([$member['employee_id']]);
                                            $last_eval = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo $last_eval && $last_eval['last_eval'] ? date('M j, Y', strtotime($last_eval['last_eval'])) : 'Never';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($evaluation && $evaluation['overall_rating']): 
                                                $rating_class = [
                                                    'exceptional' => 'success',
                                                    'exceeds_expectations' => 'primary',
                                                    'meets_expectations' => 'warning',
                                                    'needs_improvement' => 'info',
                                                    'unsatisfactory' => 'danger'
                                                ][$evaluation['overall_rating']];
                                            ?>
                                            <span class="badge bg-<?php echo $rating_class; ?> text-capitalize">
                                                <?php echo str_replace('_', ' ', $evaluation['overall_rating']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_evaluation.php?employee_id=<?php echo $member['employee_id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="evaluate_employee.php?employee_id=<?php echo $member['employee_id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i> Evaluate
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No team members found</h5>
                            <p class="text-muted">You don't have any team members reporting to you.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Evaluation Progress</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="progressChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Team Ratings Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ratingsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Progress chart
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(progressCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [5, 3, 2],
                    backgroundColor: ['#28a745', '#17a2b8', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Ratings chart
        const ratingsCtx = document.getElementById('ratingsChart').getContext('2d');
        const ratingsChart = new Chart(ratingsCtx, {
            type: 'bar',
            data: {
                labels: ['Exceptional', 'Exceeds Expectations', 'Meets Expectations', 'Needs Improvement', 'Unsatisfactory'],
                datasets: [{
                    label: '# of Employees',
                    data: [2, 3, 3, 1, 1],
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>