<?php
require_once 'config.php';
checkAuth();

// Only HR admins and system admins can access reports
if (!in_array(getUserRole(), ['hr_admin', 'system_admin'])) {
    header('Location: index.php');
    exit();
}

// Get evaluation periods for filter
$stmt = $pdo->query("SELECT * FROM evaluation_periods ORDER BY start_date DESC");
$periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for filter
$stmt = $pdo->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set default filter values
$selected_period = $_GET['period'] ?? '';
$selected_department = $_GET['department'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-report {
            transition: transform 0.2s;
            border-left: 4px solid #0d6efd;
        }
        .card-report:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
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
                    <h1 class="h2">Reports & Analytics</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i> Export PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-csv me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Report Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="period" class="form-label">Evaluation Period</label>
                                        <select class="form-select" id="period" name="period">
                                            <option value="">All Periods</option>
                                            <?php foreach ($periods as $period): ?>
                                            <option value="<?php echo $period['period_id']; ?>" <?php echo $selected_period == $period['period_id'] ? 'selected' : ''; ?>>
                                                <?php echo $period['period_name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <select class="form-select" id="department" name="department">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $department): ?>
                                            <option value="<?php echo $department['department_id']; ?>" <?php echo $selected_department == $department['department_id'] ? 'selected' : ''; ?>>
                                                <?php echo $department['department_name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                            </button>
                                            <a href="reports.php" class="btn btn-outline-secondary">Clear</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card card-report text-center">
                            <div class="card-body">
                                <div class="stats-number text-primary">87%</div>
                                <div class="text-muted">Completion Rate</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card card-report text-center">
                            <div class="card-body">
                                <div class="stats-number text-success">7.8</div>
                                <div class="text-muted">Average Rating</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card card-report text-center">
                            <div class="card-body">
                                <div class="stats-number text-info">124</div>
                                <div class="text-muted">Evaluations Completed</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card card-report text-center">
                            <div class="card-body">
                                <div class="stats-number text-warning">18</div>
                                <div class="text-muted">Pending Reviews</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Rating Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ratingDistributionChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Department Comparison</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="departmentComparisonChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Competency Analysis -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Competency Analysis</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="competencyAnalysisChart" height="300"></canvas>
                    </div>
                </div>
                
                <!-- Top Performers -->
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Top Performers</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Rating</th>
                                        <th>Score</th>
                                        <th>Strengths</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Sarah Johnson</td>
                                        <td>Marketing</td>
                                        <td><span class="badge bg-success">Exceptional</span></td>
                                        <td>9.6</td>
                                        <td>Leadership, Innovation, Strategic Thinking</td>
                                    </tr>
                                    <tr>
                                        <td>Mike Davis</td>
                                        <td>Sales</td>
                                        <td><span class="badge bg-success">Exceptional</span></td>
                                        <td>9.4</td>
                                        <td>Customer Service, Relationship Building</td>
                                    </tr>
                                    <tr>
                                        <td>Emily Chen</td>
                                        <td>Development</td>
                                        <td><span class="badge bg-primary">Exceeds Expectations</span></td>
                                        <td>8.9</td>
                                        <td>Technical Skills, Problem Solving</td>
                                    </tr>
                                    <tr>
                                        <td>David Wilson</td>
                                        <td>Operations</td>
                                        <td><span class="badge bg-primary">Exceeds Expectations</span></td>
                                        <td>8.7</td>
                                        <td>Efficiency, Process Improvement</td>
                                    </tr>
                                    <tr>
                                        <td>Lisa Brown</td>
                                        <td>HR</td>
                                        <td><span class="badge bg-primary">Exceeds Expectations</span></td>
                                        <td>8.5</td>
                                        <td>Communication, Employee Development</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Rating Distribution Chart
        const ratingDistCtx = document.getElementById('ratingDistributionChart').getContext('2d');
        const ratingDistChart = new Chart(ratingDistCtx, {
            type: 'pie',
            data: {
                labels: ['Exceptional', 'Exceeds Expectations', 'Meets Expectations', 'Needs Improvement', 'Unsatisfactory'],
                datasets: [{
                    data: [15, 35, 40, 8, 2],
                    backgroundColor: ['#28a745', '#007bff', '#ffc107', '#17a2b8', '#dc3545']
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
        
        // Department Comparison Chart
        const deptCompCtx = document.getElementById('departmentComparisonChart').getContext('2d');
        const deptCompChart = new Chart(deptCompCtx, {
            type: 'bar',
            data: {
                labels: ['Sales', 'Marketing', 'Development', 'Operations', 'HR', 'Finance'],
                datasets: [{
                    label: 'Average Rating',
                    data: [8.2, 7.9, 8.5, 7.6, 8.1, 7.8],
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 7,
                        max: 9
                    }
                }
            }
        });
        
        // Competency Analysis Chart
        const competencyCtx = document.getElementById('competencyAnalysisChart').getContext('2d');
        const competencyChart = new Chart(competencyCtx, {
            type: 'radar',
            data: {
                labels: ['Achievement Orientation', 'Business Strategy', 'Critical Thinking', 'Quality Focus', 'People Development'],
                datasets: [
                    {
                        label: 'Company Average',
                        data: [7.8, 7.5, 8.2, 8.0, 7.3],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        pointBackgroundColor: 'rgba(0, 123, 255, 1)'
                    },
                    {
                        label: 'Top Performers',
                        data: [9.2, 8.8, 9.0, 8.7, 9.1],
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 5,
                        suggestedMax: 10
                    }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>