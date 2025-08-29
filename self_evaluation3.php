<?php
// self_evaluation.php
require_once 'config.php';
checkAuth();

$period = getCurrentEvaluationPeriod();

// Check if evaluation already completed
if ($period && hasCompletedEvaluation($_SESSION['user_id'], $period['period_id'])) {
    $_SESSION['info'] = "You have already completed your evaluation for the current period.";
    header('Location: index.php');
    exit();
}

// Get all competency categories and their competencies from the database
$categories = getCompetencyCategories();
$competenciesByCategory = [];

foreach ($categories as $category) {
    $competencies = getCompetenciesByCategory($category['category_id']);
    if (!empty($competencies)) {
        $competenciesByCategory[$category['category_id']] = [
            'category_name' => $category['category_name'],
            'description' => $category['description'],
            'competencies' => $competencies
        ];
    }
}

// Get current employee ID
$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id) {
    $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employee_id = $employee['employee_id'] ?? null;
    $_SESSION['employee_id'] = $employee_id;
}

// Check if there's a draft evaluation for this period
$draft_evaluation = null;
if ($period && $employee_id) {
    $stmt = $pdo->prepare("SELECT * FROM evaluations 
                          WHERE employee_id = ? AND period_id = ? AND status = 'draft'");
    $stmt->execute([$employee_id, $period['period_id']]);
    $draft_evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($draft_evaluation) {
        // Get draft scores
        $stmt = $pdo->prepare("SELECT * FROM evaluation_scores WHERE evaluation_id = ?");
        $stmt->execute([$draft_evaluation['evaluation_id']]);
        $draft_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self Evaluation - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .competency-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .rating-slider {
            width: 100%;
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
        .category-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
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
                    <h1 class="h2">Self Evaluation</h1>
                    <?php if ($period): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-calendar-alt me-1"></i> 
                            Current Evaluation Period: <?php echo $period['period_name']; ?>
                            (<?php echo date('M j, Y', strtotime($period['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($period['end_date'])); ?>)
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$period): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i> There is no active evaluation period at this time.
                </div>
                <?php else: ?>
                
                <?php if ($draft_evaluation): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i> 
                    You have a draft evaluation from <?php echo date('M j, Y', strtotime($draft_evaluation['updated_at'])); ?>.
                    Your previous ratings have been loaded.
                </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-1"></i> Evaluation Instructions</h5>
                    <p class="mb-0">Please rate yourself on each competency using the sliding scale (1-10). Consider the positive and negative indicators provided for each competency. Be honest and objective in your self-assessment.</p>
                </div>
                
                <form method="POST" action="submit_evaluation.php">
                    <input type="hidden" name="period_id" value="<?php echo $period['period_id']; ?>">
                    <input type="hidden" name="evaluation_type" value="self">
                    
                    <?php foreach ($competenciesByCategory as $category_id => $category): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h3 class="h5 mb-0"><?php echo $category['category_name']; ?></h3>
                            <p class="text-muted mb-0"><?php echo $category['description']; ?></p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($category['competencies'] as $competency): 
                                // Check if this competency has a draft score
                                $draft_score = null;
                                $draft_comment = null;
                                if ($draft_evaluation && !empty($draft_scores)) {
                                    foreach ($draft_scores as $score) {
                                        if ($score['competency_id'] == $competency['competency_id']) {
                                            $draft_score = $score['score'];
                                            $draft_comment = $score['comments'];
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <div class="competency-card">
                                <h5><?php echo $competency['competency_name']; ?></h5>
                                
                                <?php if (!empty($competency['positive_indicator'])): ?>
                                <div class="indicator positive">
                                    <strong>Looks like:</strong> <?php echo $competency['positive_indicator']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($competency['negative_indicator'])): ?>
                                <div class="indicator negative">
                                    <strong>Doesn't look like:</strong> <?php echo $competency['negative_indicator']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <label for="score_<?php echo $competency['competency_id']; ?>" class="form-label">Rating (1-10):</label>
                                    <input type="range" class="form-range rating-slider" min="1" max="10" step="0.5" 
                                           id="score_<?php echo $competency['competency_id']; ?>" 
                                           name="scores[<?php echo $competency['competency_id']; ?>]"
                                           value="<?php echo $draft_score ? $draft_score : '5.5'; ?>"
                                           oninput="updateValue(<?php echo $competency['competency_id']; ?>, this.value)">
                                    <div class="d-flex justify-content-between">
                                        <small>1 (Unsatisfactory)</small>
                                        <span id="value_<?php echo $competency['competency_id']; ?>"><?php echo $draft_score ? $draft_score : '5.5'; ?></span>
                                        <small>10 (Exceptional)</small>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="comments_<?php echo $competency['competency_id']; ?>" class="form-label">Comments (optional):</label>
                                    <textarea class="form-control" id="comments_<?php echo $competency['competency_id']; ?>" 
                                              name="comments[<?php echo $competency['competency_id']; ?>]" rows="2"><?php echo $draft_comment ? $draft_comment : ''; ?></textarea>
                                </div>
                            </div>
                            <hr>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h3 class="h5 mb-0">Additional Comments</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="general_comments" class="form-label">General comments about your performance, achievements, or areas for development:</label>
                                <textarea class="form-control" id="general_comments" name="general_comments" rows="4"><?php echo $draft_evaluation ? $draft_evaluation['general_comments'] : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <button type="submit" name="save_draft" value="1" class="btn btn-secondary me-md-2">
                            <i class="fas fa-save me-1"></i> Save Draft
                        </button>
                        <button type="submit" name="submit_evaluation" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit Evaluation
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script>
        function updateValue(competencyId, value) {
            document.getElementById('value_' + competencyId).textContent = value;
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>