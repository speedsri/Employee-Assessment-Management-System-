<?php
require_once 'config.php';
checkAuth();

// Only HR admins and system admins can access management functions
if (!in_array(getUserRole(), ['hr_admin', 'system_admin'])) {
    header('Location: index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_period'])) {
        // Add new evaluation period
        $period_name = $_POST['period_name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $evaluation_deadline = $_POST['evaluation_deadline'];
        $period_type = $_POST['period_type'];
        
        $stmt = $pdo->prepare("INSERT INTO evaluation_periods (period_name, start_date, end_date, evaluation_deadline, period_type) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$period_name, $start_date, $end_date, $evaluation_deadline, $period_type]);
        
        $_SESSION['success'] = "Evaluation period added successfully!";
    } elseif (isset($_POST['update_settings'])) {
        // Update system settings
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $_SESSION['success'] = "System settings updated successfully!";
    }
}

// Get evaluation periods
$stmt = $pdo->query("SELECT * FROM evaluation_periods ORDER BY start_date DESC");
$periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get system settings
$stmt = $pdo->query("SELECT * FROM system_settings");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings_map = [];
foreach ($settings as $setting) {
    $settings_map[$setting['setting_key']] = $setting['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-pills .nav-link.active {
            font-weight: 600;
        }
        .setting-group {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 20px;
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
                    <h1 class="h2">Management</h1>
                </div>
                
                <ul class="nav nav-pills mb-4" id="managementTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="periods-tab" data-bs-toggle="pill" data-bs-target="#periods" type="button" role="tab">
                            <i class="fas fa-calendar-alt me-1"></i> Evaluation Periods
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab">
                            <i class="fas fa-cog me-1"></i> System Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="competencies-tab" data-bs-toggle="pill" data-bs-target="#competencies" type="button" role="tab">
                            <i class="fas fa-list-check me-1"></i> Competencies
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="pill" data-bs-target="#users" type="button" role="tab">
                            <i class="fas fa-users me-1"></i> User Management
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="managementTabsContent">
                    <!-- Evaluation Periods Tab -->
                    <div class="tab-pane fade show active" id="periods" role="tabpanel">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Add New Evaluation Period</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="period_name" class="form-label">Period Name</label>
                                                <input type="text" class="form-control" id="period_name" name="period_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="period_type" class="form-label">Period Type</label>
                                                <select class="form-select" id="period_type" name="period_type" required>
                                                    <option value="quarterly">Quarterly</option>
                                                    <option value="monthly">Monthly</option>
                                                    <option value="bi_annual">Bi-Annual</option>
                                                    <option value="annual">Annual</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="evaluation_deadline" class="form-label">Evaluation Deadline</label>
                                                <input type="date" class="form-control" id="evaluation_deadline" name="evaluation_deadline" required>
                                            </div>
                                            <button type="submit" name="add_period" class="btn btn-primary">Add Period</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="card">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Evaluation Periods</h5>
                                        <span class="badge bg-primary"><?php echo count($periods); ?> Periods</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Period Name</th>
                                                        <th>Type</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($periods as $period): 
                                                        $is_active = (strtotime($period['end_date']) >= time() && $period['is_active'] == 1);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $period['period_name']; ?></td>
                                                        <td class="text-capitalize"><?php echo str_replace('_', ' ', $period['period_type']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($period['start_date'])); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($period['end_date'])); ?></td>
                                                        <td>
                                                            <?php if ($is_active): ?>
                                                            <span class="badge bg-success">Active</span>
                                                            <?php else: ?>
                                                            <span class="badge bg-secondary">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Settings Tab -->
                    <div class="tab-pane fade" id="settings" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">System Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="setting-group">
                                        <h6>Evaluation Settings</h6>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="evaluation_frequency" class="form-label">Evaluation Frequency</label>
                                                <select class="form-select" id="evaluation_frequency" name="settings[evaluation_frequency]">
                                                    <option value="monthly" <?php echo $settings_map['evaluation_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                    <option value="quarterly" <?php echo $settings_map['evaluation_frequency'] == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                                    <option value="bi_annual" <?php echo $settings_map['evaluation_frequency'] == 'bi_annual' ? 'selected' : ''; ?>>Bi-Annual</option>
                                                    <option value="annual" <?php echo $settings_map['evaluation_frequency'] == 'annual' ? 'selected' : ''; ?>>Annual</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="min_score_threshold" class="form-label">Minimum Score Threshold</label>
                                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="min_score_threshold" 
                                                       name="settings[min_score_threshold]" value="<?php echo $settings_map['min_score_threshold']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h6>Notification Settings</h6>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="auto_reminder_enabled" class="form-label">Auto Reminders</label>
                                                <select class="form-select" id="auto_reminder_enabled" name="settings[auto_reminder_enabled]">
                                                    <option value="true" <?php echo $settings_map['auto_reminder_enabled'] == 'true' ? 'selected' : ''; ?>>Enabled</option>
                                                    <option value="false" <?php echo $settings_map['auto_reminder_enabled'] == 'false' ? 'selected' : ''; ?>>Disabled</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="reminder_days_before" class="form-label">Reminder Days Before Deadline</label>
                                                <input type="number" class="form-control" id="reminder_days_before" 
                                                       name="settings[reminder_days_before]" value="<?php echo $settings_map['reminder_days_before']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h6>Security Settings</h6>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                                <input type="number" class="form-control" id="max_login_attempts" 
                                                       name="settings[max_login_attempts]" value="<?php echo $settings_map['max_login_attempts']; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                                <input type="number" class="form-control" id="session_timeout" 
                                                       name="settings[session_timeout]" value="<?php echo $settings_map['session_timeout']; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                                <input type="number" class="form-control" id="password_min_length" 
                                                       name="settings[password_min_length]" value="<?php echo $settings_map['password_min_length']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h6>Feature Settings</h6>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="self_evaluation_enabled" class="form-label">Self Evaluation</label>
                                                <select class="form-select" id="self_evaluation_enabled" name="settings[self_evaluation_enabled]">
                                                    <option value="true" <?php echo $settings_map['self_evaluation_enabled'] == 'true' ? 'selected' : ''; ?>>Enabled</option>
                                                    <option value="false" <?php echo $settings_map['self_evaluation_enabled'] == 'false' ? 'selected' : ''; ?>>Disabled</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Competencies Tab -->
                    <div class="tab-pane fade" id="competencies" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Competency Management</h5>
                                <button class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add Competency
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Competency</th>
                                                <th>Category</th>
                                                <th>Weight</th>
                                                <th>Required</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $pdo->query("SELECT c.*, cc.category_name 
                                                                FROM competencies c 
                                                                JOIN competency_categories cc ON c.category_id = cc.category_id 
                                                                ORDER BY cc.category_name, c.competency_name");
                                            $competencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($competencies as $competency):
                                            ?>
                                            <tr>
                                                <td><?php echo $competency['competency_name']; ?></td>
                                                <td><?php echo $competency['category_name']; ?></td>
                                                <td><?php echo $competency['weight']; ?></td>
                                                <td>
                                                    <?php if ($competency['is_required']): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($competency['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button class="btn btn-outline-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Management Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">User Management</h5>
                                <button class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add User
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $pdo->query("SELECT u.*, e.department_id, d.department_name 
                                                                FROM users u 
                                                                LEFT JOIN employees e ON u.user_id = e.user_id 
                                                                LEFT JOIN departments d ON e.department_id = d.department_id 
                                                                ORDER BY u.role, u.first_name, u.last_name");
                                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($users as $user):
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($user['role']) {
                                                            case 'system_admin': echo 'danger'; break;
                                                            case 'hr_admin': echo 'info'; break;
                                                            case 'manager': echo 'warning'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?> text-capitalize">
                                                        <?php echo str_replace('_', ' ', $user['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $user['department_name'] ?? 'N/A'; ?></td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button class="btn btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>">
                                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i> 
                                                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>