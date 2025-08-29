<?php
require_once 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $period_id = $_POST['period_id'];
    $scores = $_POST['scores'];
    $comments = $_POST['comments'] ?? [];
    $general_comments = $_POST['general_comments'] ?? '';
    
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
    $stmt = $pdo->prepare("INSERT INTO evaluations (employee_id, period_id, evaluation_type, status, general_comments) 
                          VALUES (?, ?, 'self', 'submitted', ?)");
    $stmt->execute([$employee['employee_id'], $period_id, $general_comments]);
    $evaluation_id = $pdo->lastInsertId();
    
    // Save scores
    foreach ($scores as $competency_id => $score) {
        $rating = getRatingFromScore($score);
        $comment = $comments[$competency_id] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO evaluation_scores (evaluation_id, competency_id, score, rating, comments) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$evaluation_id, $competency_id, $score, $rating, $comment]);
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