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
    if (isset($_POST['add_employee'])) {
        // Add new employee
        $user_data = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'role' => $_POST['role']
        ];
        
        try {
            $pdo->beginTransaction();
            
            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_data['username'], $user_data['email'], $user_data['password'], 
                           $user_data['first_name'], $user_data['last_name'], $user_data['role']]);
            $user_id = $pdo->lastInsertId();
            
            // Insert into employees table
            $employee_data = [
                'user_id' => $user_id,
                'employee_code' => $_POST['employee_code'],
                'department_id' => $_POST['department_id'] ?: null,
                'position_id' => $_POST['position_id'] ?: null,
                'reporting_manager_id' => $_POST['reporting_manager_id'] ?: null,
                'hire_date' => $_POST['hire_date'],
                'employment_status' => $_POST['employment_status'],
                'employment_type' => $_POST['employment_type'],
                'phone_number' => $_POST['phone_number'] ?: null,
                'address' => $_POST['address'] ?: null,
                'emergency_contact' => $_POST['emergency_contact'] ?: null,
                'emergency_phone' => $_POST['emergency_phone'] ?: null,
                'date_of_birth' => $_POST['date_of_birth'] ?: null,
                'gender' => $_POST['gender'] ?: null
            ];
            
            $stmt = $pdo->prepare("INSERT INTO employees (user_id, employee_code, department_id, position_id, 
                                  reporting_manager_id, hire_date, employment_status, employment_type, phone_number, 
                                  address, emergency_contact, emergency_phone, date_of_birth, gender) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_values($employee_data));
            
            $pdo->commit();
            $_SESSION['success'] = "Employee added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error adding employee: " . $e->getMessage();
        }
        
    } elseif (isset($_POST['add_department'])) {
        // Add new department
        $department_name = $_POST['department_name'];
        $department_code = $_POST['department_code'] ?: null;
        $description = $_POST['description'] ?: null;
        $manager_id = $_POST['manager_id'] ?: null;
        $parent_department_id = $_POST['parent_department_id'] ?: null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (department_name, department_code, description, manager_id, parent_department_id) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$department_name, $department_code, $description, $manager_id, $parent_department_id]);
            $_SESSION['success'] = "Department added successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adding department: " . $e->getMessage();
        }
        
    } elseif (isset($_POST['add_competency'])) {
        // Add new competency
        $competency_name = $_POST['competency_name'];
        $category_id = $_POST['category_id'];
        $positive_indicator = $_POST['positive_indicator'] ?: null;
        $negative_indicator = $_POST['negative_indicator'] ?: null;
        $weight = $_POST['weight'] ?: 1.00;
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $applies_to_level = $_POST['applies_to_level'] ?: null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO competencies (competency_name, category_id, positive_indicator, 
                                  negative_indicator, weight, is_required, applies_to_level) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$competency_name, $category_id, $positive_indicator, $negative_indicator, 
                           $weight, $is_required, $applies_to_level]);
            $_SESSION['success'] = "Competency added successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adding competency: " . $e->getMessage();
        }
        
    } elseif (isset($_POST['add_position'])) {
        // Add new position
        $position_title = $_POST['position_title'];
        $position_level = $_POST['position_level'] ?: null;
        $min_salary = $_POST['min_salary'] ?: null;
        $max_salary = $_POST['max_salary'] ?: null;
        $description = $_POST['description'] ?: null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO positions (position_title, position_level, min_salary, max_salary, description) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$position_title, $position_level, $min_salary, $max_salary, $description]);
            $_SESSION['success'] = "Position added successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adding position: " . $e->getMessage();
        }
        
    } elseif (isset($_POST['add_period'])) {
        // Add evaluation period (existing code)
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
        // Update system settings (existing code)
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $_SESSION['success'] = "System settings updated successfully!";
    }
    
    // Redirect to prevent form resubmission
    header('Location: management.php');
    exit();
}

// Get data for dropdowns
$departments = $pdo->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$positions = $pdo->query("SELECT * FROM positions ORDER BY position_title")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT e.employee_id, u.first_name, u.last_name 
                         FROM employees e 
                         JOIN users u ON e.user_id = u.user_id 
                         ORDER BY u.first_name, u.last_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT * FROM competency_categories WHERE is_active = 1 ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
$periods = $pdo->query("SELECT * FROM evaluation_periods ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_ASSOC);

// Create settings map
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
        .form-card {
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
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
                    <h1 class="h2">Management Dashboard</h1>
                </div>
                
                <ul class="nav nav-pills mb-4" id="managementTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="employees-tab" data-bs-toggle="pill" data-bs-target="#employees" type="button" role="tab">
                            <i class="fas fa-users me-1"></i> Employees
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="departments-tab" data-bs-toggle="pill" data-bs-target="#departments" type="button" role="tab">
                            <i class="fas fa-building me-1"></i> Departments
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="competencies-tab" data-bs-toggle="pill" data-bs-target="#competencies" type="button" role="tab">
                            <i class="fas fa-list-check me-1"></i> Competencies
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="positions-tab" data-bs-toggle="pill" data-bs-target="#positions" type="button" role="tab">
                            <i class="fas fa-briefcase me-1"></i> Positions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="periods-tab" data-bs-toggle="pill" data-bs-target="#periods" type="button" role="tab">
                            <i class="fas fa-calendar-alt me-1"></i> Evaluation Periods
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab">
                            <i class="fas fa-cog me-1"></i> System Settings
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="managementTabsContent">
                    <!-- Employees Tab -->
                    <div class="tab-pane fade show active" id="employees" role="tabpanel">
                        <div class="card form-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Add New Employee</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name *</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username *</label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email *</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password *</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="employee_code" class="form-label">Employee Code *</label>
                                                <input type="text" class="form-control" id="employee_code" name="employee_code" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="role" class="form-label">Role *</label>
                                                <select class="form-select" id="role" name="role" required>
                                                    <option value="employee">Employee</option>
                                                    <option value="manager">Manager</option>
                                                    <option value="hr_admin">HR Admin</option>
                                                    <option value="system_admin">System Admin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="department_id" class="form-label">Department</label>
                                                <select class="form-select" id="department_id" name="department_id">
                                                    <option value="">Select Department</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="position_id" class="form-label">Position</label>
                                                <select class="form-select" id="position_id" name="position_id">
                                                    <option value="">Select Position</option>
                                                    <?php foreach ($positions as $pos): ?>
                                                    <option value="<?php echo $pos['position_id']; ?>"><?php echo $pos['position_title']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="reporting_manager_id" class="form-label">Reporting Manager</label>
                                                <select class="form-select" id="reporting_manager_id" name="reporting_manager_id">
                                                    <option value="">Select Manager</option>
                                                    <?php foreach ($employees as $emp): ?>
                                                    <option value="<?php echo $emp['employee_id']; ?>"><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="hire_date" class="form-label">Hire Date *</label>
                                                <input type="date" class="form-control" id="hire_date" name="hire_date" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="employment_status" class="form-label">Employment Status *</label>
                                                <select class="form-select" id="employment_status" name="employment_status" required>
                                                    <option value="active">Active</option>
                                                    <option value="on_leave">On Leave</option>
                                                    <option value="terminated">Terminated</option>
                                                    <option value="suspended">Suspended</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="employment_type" class="form-label">Employment Type *</label>
                                                <select class="form-select" id="employment_type" name="employment_type" required>
                                                    <option value="full_time">Full Time</option>
                                                    <option value="part_time">Part Time</option>
                                                    <option value="contract">Contract</option>
                                                    <option value="intern">Intern</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone_number" name="phone_number">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="emergency_phone" class="form-label">Emergency Contact Phone</label>
                                                <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="gender" class="form-label">Gender</label>
                                                <select class="form-select" id="gender" name="gender">
                                                    <option value="">Select Gender</option>
                                                    <option value="male">Male</option>
                                                    <option value="female">Female</option>
                                                    <option value="other">Other</option>
                                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Employees</h5>
                                <span class="badge bg-primary"><?php echo count($employees); ?> Employees</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Department</th>
                                                <th>Position</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $pdo->query("SELECT u.*, e.*, d.department_name, p.position_title 
                                                                FROM users u 
                                                                JOIN employees e ON u.user_id = e.user_id 
                                                                LEFT JOIN departments d ON e.department_id = d.department_id 
                                                                LEFT JOIN positions p ON e.position_id = p.position_id 
                                                                ORDER BY u.first_name, u.last_name");
                                            $all_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($all_employees as $emp):
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                            <?php echo strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?>
                                                            <div class="text-muted small"><?php echo $emp['employee_code']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $emp['email']; ?></td>
                                                <td><?php echo $emp['department_name'] ?? 'N/A'; ?></td>
                                                <td><?php echo $emp['position_title'] ?? 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($emp['employment_status']) {
                                                            case 'active': echo 'success'; break;
                                                            case 'on_leave': echo 'warning'; break;
                                                            case 'terminated': echo 'danger'; break;
                                                            case 'suspended': echo 'secondary'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?> text-capitalize">
                                                        <?php echo str_replace('_', ' ', $emp['employment_status']); ?>
                                                    </span>
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
                    
                    <!-- Departments Tab -->
                    <div class="tab-pane fade" id="departments" role="tabpanel">
                        <div class="card form-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Add New Department</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="department_name" class="form-label">Department Name *</label>
                                                <input type="text" class="form-control" id="department_name" name="department_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="department_code" class="form-label">Department Code</label>
                                                <input type="text" class="form-control" id="department_code" name="department_code">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="manager_id" class="form-label">Department Manager</label>
                                                <select class="form-select" id="manager_id" name="manager_id">
                                                    <option value="">Select Manager</option>
                                                    <?php foreach ($employees as $emp): ?>
                                                    <option value="<?php echo $emp['employee_id']; ?>"><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="parent_department_id" class="form-label">Parent Department</label>
                                                <select class="form-select" id="parent_department_id" name="parent_department_id">
                                                    <option value="">Select Department</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Departments</h5>
                                <span class="badge bg-primary"><?php echo count($departments); ?> Departments</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Department Name</th>
                                                <th>Code</th>
                                                <th>Manager</th>
                                                <th>Parent Department</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($departments as $dept): 
                                                // Get manager name
                                                $manager_name = 'N/A';
                                                if ($dept['manager_id']) {
                                                    $stmt = $pdo->prepare("SELECT u.first_name, u.last_name 
                                                                          FROM employees e 
                                                                          JOIN users u ON e.user_id = u.user_id 
                                                                          WHERE e.employee_id = ?");
                                                    $stmt->execute([$dept['manager_id']]);
                                                    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    if ($manager) {
                                                        $manager_name = $manager['first_name'] . ' ' . $manager['last_name'];
                                                    }
                                                }
                                                
                                                // Get parent department name
                                                $parent_name = 'N/A';
                                                if ($dept['parent_department_id']) {
                                                    $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
                                                    $stmt->execute([$dept['parent_department_id']]);
                                                    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    if ($parent) {
                                                        $parent_name = $parent['department_name'];
                                                    }
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $dept['department_name']; ?></td>
                                                <td><?php echo $dept['department_code'] ?? 'N/A'; ?></td>
                                                <td><?php echo $manager_name; ?></td>
                                                <td><?php echo $parent_name; ?></td>
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
                    
                    <!-- Competencies Tab -->
                    <div class="tab-pane fade" id="competencies" role="tabpanel">
                        <div class="card form-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Add New Competency</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="competency_name" class="form-label">Competency Name *</label>
                                                <input type="text" class="form-control" id="competency_name" name="competency_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="category_id" class="form-label">Category *</label>
                                                <select class="form-select" id="category_id" name="category_id" required>
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="positive_indicator" class="form-label">Positive Indicator (Looks Like)</label>
                                        <textarea class="form-control" id="positive_indicator" name="positive_indicator" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="negative_indicator" class="form-label">Negative Indicator (Doesn't Look Like)</label>
                                        <textarea class="form-control" id="negative_indicator" name="negative_indicator" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="weight" class="form-label">Weight</label>
                                                <input type="number" step="0.01" min="0.1" max="5.0" class="form-control" id="weight" name="weight" value="1.00">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="applies_to_level" class="form-label">Applies to Position Level</label>
                                                <input type="number" class="form-control" id="applies_to_level" name="applies_to_level">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="is_required" name="is_required" value="1" checked>
                                                <label class="form-check-label" for="is_required">Required Competency</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="add_competency" class="btn btn-primary">Add Competency</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Competencies</h5>
                                <span class="badge bg-primary">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM competencies");
                                    $comp_count = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $comp_count['count'] . ' Competencies';
                                    ?>
                                </span>
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
                                            
                                            foreach ($competencies as $comp):
                                            ?>
                                            <tr>
                                                <td><?php echo $comp['competency_name']; ?></td>
                                                <td><?php echo $comp['category_name']; ?></td>
                                                <td><?php echo $comp['weight']; ?></td>
                                                <td>
                                                    <?php if ($comp['is_required']): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($comp['is_active']): ?>
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
                    
                    <!-- Positions Tab -->
                    <div class="tab-pane fade" id="positions" role="tabpanel">
                        <div class="card form-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Add New Position</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="position_title" class="form-label">Position Title *</label>
                                                <input type="text" class="form-control" id="position_title" name="position_title" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="position_level" class="form-label">Position Level</label>
                                                <input type="number" class="form-control" id="position_level" name="position_level">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="min_salary" class="form-label">Minimum Salary</label>
                                                <input type="number" step="0.01" class="form-control" id="min_salary" name="min_salary">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="max_salary" class="form-label">Maximum Salary</label>
                                                <input type="number" step="0.01" class="form-control" id="max_salary" name="max_salary">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" name="add_position" class="btn btn-primary">Add Position</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Positions</h5>
                                <span class="badge bg-primary"><?php echo count($positions); ?> Positions</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Position Title</th>
                                                <th>Level</th>
                                                <th>Min Salary</th>
                                                <th>Max Salary</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($positions as $pos): ?>
                                            <tr>
                                                <td><?php echo $pos['position_title']; ?></td>
                                                <td><?php echo $pos['position_level'] ?? 'N/A'; ?></td>
                                                <td><?php echo $pos['min_salary'] ? '$' . number_format($pos['min_salary'], 2) : 'N/A'; ?></td>
                                                <td><?php echo $pos['max_salary'] ? '$' . number_format($pos['max_salary'], 2) : 'N/A'; ?></td>
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
                    
                    <!-- Evaluation Periods Tab -->
                    <div class="tab-pane fade" id="periods" role="tabpanel">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="card form-card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Add New Evaluation Period</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="period_name" class="form-label">Period Name *</label>
                                                <input type="text" class="form-control" id="period_name" name="period_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="period_type" class="form-label">Period Type *</label>
                                                <select class="form-select" id="period_type" name="period_type" required>
                                                    <option value="quarterly">Quarterly</option>
                                                    <option value="monthly">Monthly</option>
                                                    <option value="bi_annual">Bi-Annual</option>
                                                    <option value="annual">Annual</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">Start Date *</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="end_date" class="form-label">End Date *</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="evaluation_deadline" class="form-label">Evaluation Deadline *</label>
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
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tab functionality
        const triggerTabList = document.querySelectorAll('#managementTabs button')
        triggerTabList.forEach(triggerEl => {
            new bootstrap.Tab(triggerEl)
        })
        
        // Set today's date as default for date fields
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('hire_date').value = today;
            
            // Set evaluation deadline to 7 days from now
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            const nextWeekFormatted = nextWeek.toISOString().split('T')[0];
            document.getElementById('evaluation_deadline').value = nextWeekFormatted;
            
            // Set period end date to 3 months from now
            const threeMonths = new Date();
            threeMonths.setMonth(threeMonths.getMonth() + 3);
            const threeMonthsFormatted = threeMonths.toISOString().split('T')[0];
            document.getElementById('end_date').value = threeMonthsFormatted;
        });
    </script>
</body>
</html>