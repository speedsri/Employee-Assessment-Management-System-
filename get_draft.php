<?php
require_once 'config.php';
checkAuth();

$periodId = $_GET['period_id'];
$userId = $_SESSION['user_id'];

if (isset($_SESSION['evaluation_draft'])) {
    echo json_encode([
        'success' => true,
        'draft' => $_SESSION['evaluation_draft']
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>