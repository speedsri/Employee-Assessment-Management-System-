<?php
require_once 'config.php';
checkAuth();

if ($_POST['action'] == 'save_draft') {
    $userId = $_SESSION['user_id'];
    $periodId = $_POST['period_id'];
    $scores = $_POST['scores'];
    $comments = $_POST['comments'];
    $overall = [
        'strengths' => $_POST['strengths'],
        'development_areas' => $_POST['development_areas'],
        'achievements' => $_POST['achievements'],
        'goals' => $_POST['goals'],
        'support_needed' => $_POST['support_needed']
    ];
    
    // Save to database or session
    $_SESSION['evaluation_draft'] = [
        'scores' => $scores,
        'comments' => $comments,
        'overall' => $overall
    ];
    
    echo json_encode(['success' => true]);
}
?>