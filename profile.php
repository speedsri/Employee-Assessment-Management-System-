<?php
require_once 'config.php';
checkAuth();

// Get user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get employee details
$stmt = $pdo->prepare("SELECT e.*, d.department_name, p.position_title 
                      FROM employees e 
                      LEFT JOIN departments d ON e.department_id = d.department_id 
                      LEFT JOIN positions p ON e.position_id = p.position_id 
                      WHERE e.user_id = ?");
$stmt->execute([$user_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $emergency_contact = $_POST['emergency_contact'];
    $emergency_phone = $_POST['emergency_phone'];
    
    // Update user table
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?");
    $stmt->execute([$first_name, $last_name, $user_id]);
    
    // Update employee table
    $stmt = $pdo->prepare("UPDATE employees SET phone_number = ?, address = ?, emergency_contact = ?, emergency_phone = ? WHERE user_id = ?");
    $stmt->execute([$phone_number, $address, $emergency_contact, $emergency_phone, $user_id]);
    
    // Update session variables
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    
    $_SESSION['success'] = "Profile updated successfully!";
    header('Location: profile.php');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $user['password_hash'])) {
        $_SESSION['error'] = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        $_SESSION['success'] = "Password changed successfully!";
    }
    
    header('Location: profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }
        .nav-pills .nav-link.active {
            font-weight: 600;
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
                    <h1 class="h2">Profile</h1>
                </div>
                
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; font-size: 3rem;">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="col">
                            <h2><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
                            <p class="text-muted mb-1"><?php echo $employee['position_title'] ?? 'Employee'; ?></p>
                            <p class="text-muted mb-0"><?php echo $employee['department_name'] ?? 'No Department'; ?></p>
                            <p class="text-muted">Employee ID: <?php echo $employee['employee_code'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-<?php 
                                switch($user['role']) {
                                    case 'system_admin': echo 'danger'; break;
                                    case 'hr_admin': echo 'info'; break;
                                    case 'manager': echo 'warning'; break;
                                    default: echo 'secondary';
                                }
                            ?> text-capitalize fs-6">
                                <?php echo str_replace('_', ' ', $user['role']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-tab" data-bs-toggle="pill" data-bs-target="#personal" type="button" role="tab">
                            <i class="fas fa-user me-1"></i> Personal Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="employment-tab" data-bs-toggle="pill" data-bs-target="#employment" type="button" role="tab">
                            <i class="fas fa-briefcase me-1"></i> Employment Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                            <i class="fas fa-lock me-1"></i> Security
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="profileTabsContent">
                    <!-- Personal Information Tab -->
                    <div class="tab-pane fade show active" id="personal" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                                                <div class="form-text">Contact administrator to change your email address</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo $employee['phone_number'] ?? ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $employee['address'] ?? ''; ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" value="<?php echo $employee['emergency_contact'] ?? ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="emergency_phone" class="form-label">Emergency Contact Phone</label>
                                                <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone" value="<?php echo $employee['emergency_phone'] ?? ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Details Tab -->
                    <div class="tab-pane fade" id="employment" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Employment Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Employee ID</label>
                                            <input type="text" class="form-control" value="<?php echo $employee['employee_code'] ?? 'N/A'; ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Department</label>
                                            <input type="text" class="form-control" value="<?php echo $employee['department_name'] ?? 'N/A'; ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Position</label>
                                            <input type="text" class="form-control" value="<?php echo $employee['position_title'] ?? 'N/A'; ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Employment Type</label>
                                            <input type="text" class="form-control" value="<?php echo isset($employee['employment_type']) ? ucfirst(str_replace('_', ' ', $employee['employment_type'])) : 'N/A'; ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Hire Date</label>
                                            <input type="text" class="form-control" value="<?php echo isset($employee['hire_date']) ? date('M j, Y', strtotime($employee['hire_date'])) : 'N/A'; ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Reporting Manager</label>
                                            <?php
                                            if (isset($employee['reporting_manager_id'])) {
                                                $stmt = $pdo->prepare("SELECT u.first_name, u.last_name 
                                                                      FROM employees e 
                                                                      JOIN users u ON e.user_id = u.user_id 
                                                                      WHERE e.employee_id = ?");
                                                $stmt->execute([$employee['reporting_manager_id']]);
                                                $manager = $stmt->fetch(PDO::FETCH_ASSOC);
                                                $manager_name = $manager ? $manager['first_name'] . ' ' . $manager['last_name'] : 'N/A';
                                            } else {
                                                $manager_name = 'N/A';
                                            }
                                            ?>
                                            <input type="text" class="form-control" value="<?php echo $manager_name; ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Employment Status</label>
                                    <div>
                                        <span class="badge bg-<?php 
                                            switch($employee['employment_status'] ?? 'active') {
                                                case 'active': echo 'success'; break;
                                                case 'on_leave': echo 'warning'; break;
                                                case 'terminated': echo 'danger'; break;
                                                case 'suspended': echo 'secondary'; break;
                                                default: echo 'secondary';
                                            }
                                        ?> text-capitalize">
                                            <?php echo isset($employee['employment_status']) ? str_replace('_', ' ', $employee['employment_status']) : 'Active'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 8 characters long</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Login Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>IP Address</th>
                                                <th>Device</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?php echo date('M j, Y, g:i a'); ?></td>
                                                <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                                                <td><?php echo $_SERVER['HTTP_USER_AGENT']; ?></td>
                                                <td><span class="badge bg-success">Successful</span></td>
                                            </tr>
                                            <tr>
                                                <td>Oct 15, 2023, 2:30 pm</td>
                                                <td>192.168.1.105</td>
                                                <td>Chrome on Windows</td>
                                                <td><span class="badge bg-success">Successful</span></td>
                                            </tr>
                                            <tr>
                                                <td>Oct 14, 2023, 9:15 am</td>
                                                <td>192.168.1.105</td>
                                                <td>Chrome on Windows</td>
                                                <td><span class="badge bg-success">Successful</span></td>
                                            </tr>
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