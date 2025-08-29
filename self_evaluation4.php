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
        $competenciesByCategory[$category['category_name']] = [
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

// Define the group structure from your original form
$competencyGroups = [
    'Personal Excellence' => [
        'Achievement Orientation',
        'Initiative',
        'Self-Confidence',
        'Self-Control'
    ],
    'Strategic Thinking' => [
        'Business/Strategic Orientation',
        'Critical Thinking',
        'Innovation'
    ],
    'Quality & Knowledge' => [
        'Concern for Order and Quality',
        'Expertise',
        'Information Seeking'
    ],
    'People Leadership' => [
        'Developing People',
        'Directiveness',
        'Team Leadership',
        'Teamwork'
    ],
    'Interpersonal Skills' => [
        'Flexibility',
        'Impact and Influence',
        'Listening and Responding',
        'Relationship Building'
    ],
    'Organizational Excellence' => [
        'Organizational Awareness',
        'Personalized Customer Service'
    ]
];

// Create a new grouped structure based on the database data
$groupedCompetencies = [];
foreach ($competencyGroups as $groupName => $competencyNames) {
    $groupedCompetencies[$groupName] = [
        'description' => '', // We'll set this below
        'competencies' => []
    ];
    
    // Set group description based on the group name
    switch ($groupName) {
        case 'Personal Excellence':
            $groupedCompetencies[$groupName]['description'] = 'Core competencies related to personal performance and self-management';
            break;
        case 'Strategic Thinking':
            $groupedCompetencies[$groupName]['description'] = 'Competencies related to planning, analysis, and strategic decision-making';
            break;
        case 'Quality & Knowledge':
            $groupedCompetencies[$groupName]['description'] = 'Competencies focused on maintaining standards and sharing expertise';
            break;
        case 'People Leadership':
            $groupedCompetencies[$groupName]['description'] = 'Competencies for leading, developing, and working with others';
            break;
        case 'Interpersonal Skills':
            $groupedCompetencies[$groupName]['description'] = 'Competencies for effective interaction and relationship management';
            break;
        case 'Organizational Excellence':
            $groupedCompetencies[$groupName]['description'] = 'Competencies for understanding and serving organizational needs';
            break;
    }
    
    // Add competencies to the group
    foreach ($competencyNames as $competencyName) {
        foreach ($competenciesByCategory as $categoryName => $categoryData) {
            if ($categoryName === $competencyName) {
                foreach ($categoryData['competencies'] as $competency) {
                    $groupedCompetencies[$groupName]['competencies'][$competencyName] = [
                        'competency_id' => $competency['competency_id'],
                        'positive_indicator' => $competency['positive_indicator'],
                        'negative_indicator' => $competency['negative_indicator'],
                        'description' => $categoryData['description']
                    ];
                }
                break;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Self Evaluation - Employee Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .competency-card {
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .competency-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .rating-slider {
            width: 100%;
            height: 8px;
        }
        .slider-container {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 15px;
        }
        .indicator {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .positive-indicators {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .negative-indicators {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .competency-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .score-display {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .progress-indicator {
            position: fixed;
            top: 100px;
            right: 20px;
            width: 250px;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        .rating-guide {
            background: #e9ecef;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .indicator-list li {
            margin-bottom: 5px;
        }
        .save-draft-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .progress-indicator {
                position: relative;
                top: auto;
                right: auto;
                width: 100%;
                margin-bottom: 20px;
            }
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
                    <h1 class="h2">
                        <i class="fas fa-clipboard-check me-2"></i>Comprehensive Self Evaluation
                    </h1>
                    <?php if ($period): ?>
                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-calendar-alt me-1"></i> 
                        Period: <?php echo $period['period_name']; ?>
                        (<?php echo date('M j, Y', strtotime($period['start_date'])); ?> - 
                        <?php echo date('M j, Y', strtotime($period['end_date'])); ?>)
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$period): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i> 
                    There is no active evaluation period at this time. Please check back later.
                </div>
                <?php else: ?>
                
                <?php if ($draft_evaluation): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i> 
                    You have a draft evaluation from <?php echo date('M j, Y', strtotime($draft_evaluation['updated_at'])); ?>.
                    Your previous ratings have been loaded.
                </div>
                <?php endif; ?>
                
                <!-- Progress Indicator -->
                <div class="progress-indicator d-none d-lg-block">
                    <h6 class="mb-3">Evaluation Progress</h6>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" id="progressBar" style="width: 0%">0%</div>
                    </div>
                    <small class="text-muted">
                        <span id="completedCount">0</span> of <span id="totalCount">0</span> competencies rated
                    </small>
                    <hr>
                    <div id="categoryProgress"></div>
                </div>
                
                <!-- Instructions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle text-info me-2"></i>Evaluation Instructions
                        </h5>
                        <p>Please evaluate yourself honestly on all 21 core managerial competencies using the scale below:</p>
                        <div class="rating-guide">
                            <div class="row">
                                <div class="col-md-3"><strong>1-2:</strong> Unsatisfactory</div>
                                <div class="col-md-3"><strong>3-4:</strong> Below Expectations</div>
                                <div class="col-md-3"><strong>5-6:</strong> Meets Expectations</div>
                                <div class="col-md-3"><strong>7-8:</strong> Above Expectations</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12"><strong>9-10:</strong> Exceptional - Demonstrates mastery</div>
                            </div>
                        </div>
                        <p class="mb-0">
                            <i class="fas fa-lightbulb text-warning me-1"></i>
                            <strong>Tip:</strong> Review both the positive ("Looks Like") and negative ("Doesn't Look Like") 
                            indicators for each competency to guide your self-assessment.
                        </p>
                    </div>
                </div>
                
                <!-- Evaluation Form -->
                <form method="POST" action="submit_evaluation.php" id="evaluationForm">
                    <input type="hidden" name="period_id" value="<?php echo $period['period_id']; ?>">
                    <input type="hidden" name="evaluation_type" value="comprehensive">
                    
                    <?php 
                    $competencyIndex = 0;
                    foreach ($groupedCompetencies as $groupName => $groupData): 
                    ?>
                    <div class="card mb-4">
                        <div class="category-header">
                            <h3 class="h4 mb-2">
                                <i class="fas fa-layer-group me-2"></i><?php echo $groupName; ?>
                            </h3>
                            <p class="mb-0"><?php echo $groupData['description']; ?></p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($groupData['competencies'] as $competencyName => $competency): 
                                $competencyIndex++;
                                $competencyId = $competency['competency_id'];
                                
                                // Check if this competency has a draft score
                                $draft_score = null;
                                $draft_comment = null;
                                if ($draft_evaluation && !empty($draft_scores)) {
                                    foreach ($draft_scores as $score) {
                                        if ($score['competency_id'] == $competencyId) {
                                            $draft_score = $score['score'];
                                            $draft_comment = $score['comments'];
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <div class="competency-card">
                                <h5 class="competency-title">
                                    <?php echo $competencyIndex; ?>. <?php echo $competencyName; ?>
                                </h5>
                                <p class="text-muted"><?php echo $competency['description']; ?></p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="positive-indicators indicator">
                                            <h6><i class="fas fa-check-circle me-1"></i>Looks Like:</h6>
                                            <ul class="indicator-list mb-0">
                                                <li><?php echo $competency['positive_indicator']; ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="negative-indicators indicator">
                                            <h6><i class="fas fa-times-circle me-1"></i>Doesn't Look Like:</h6>
                                            <ul class="indicator-list mb-0">
                                                <li><?php echo $competency['negative_indicator']; ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="slider-container">
                                    <div class="row align-items-center">
                                        <div class="col-md-9">
                                            <label for="score_<?php echo $competencyId; ?>" class="form-label">
                                                <strong>Your Rating:</strong>
                                            </label>
                                            <input type="range" 
                                                   class="form-range rating-slider" 
                                                   min="1" max="10" step="0.5" 
                                                   value="<?php echo $draft_score ? $draft_score : '5.5'; ?>"
                                                   id="score_<?php echo $competencyId; ?>" 
                                                   name="scores[<?php echo $competencyId; ?>]"
                                                   data-category="<?php echo htmlspecialchars($groupName); ?>"
                                                   oninput="updateRating('<?php echo $competencyId; ?>', this.value)">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">1 - Unsatisfactory</small>
                                                <small class="text-muted">5.5 - Meets Expectations</small>
                                                <small class="text-muted">10 - Exceptional</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="score-display" id="score_<?php echo $competencyId; ?>">
                                                <?php echo $draft_score ? $draft_score : '5.5'; ?>
                                            </div>
                                            <small class="text-muted">Current Score</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <label for="comment_<?php echo $competencyId; ?>" class="form-label">
                                            Comments (Optional):
                                        </label>
                                        <textarea class="form-control" 
                                                  id="comment_<?php echo $competencyId; ?>" 
                                                  name="comments[<?php echo $competencyId; ?>]" 
                                                  rows="2"
                                                  placeholder="Provide specific examples or context for your rating..."><?php echo $draft_comment ? $draft_comment : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Overall Assessment -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h3 class="h4 mb-0">
                                <i class="fas fa-comment-alt me-2"></i>Overall Assessment
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="strengths" class="form-label">
                                    <strong>Key Strengths:</strong> What are your top 3 strengths?
                                </label>
                                <textarea class="form-control" id="strengths" name="strengths" rows="3" 
                                          placeholder="List your key strengths and how they contribute to your role..."><?php echo $draft_evaluation ? $draft_evaluation['strengths'] : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="development_areas" class="form-label">
                                    <strong>Development Areas:</strong> What are your top 3 areas for improvement?
                                </label>
                                <textarea class="form-control" id="development_areas" name="development_areas" rows="3"
                                          placeholder="Identify areas where you need to develop..."><?php echo $draft_evaluation ? $draft_evaluation['areas_for_improvement'] : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="achievements" class="form-label">
                                    <strong>Key Achievements:</strong> What are your most significant accomplishments this period?
                                </label>
                                <textarea class="form-control" id="achievements" name="achievements" rows="3"
                                          placeholder="Describe your key achievements and their impact..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="goals" class="form-label">
                                    <strong>Goals for Next Period:</strong> What do you aim to achieve?
                                </label>
                                <textarea class="form-control" id="goals" name="goals" rows="3"
                                          placeholder="Set specific, measurable goals for the next evaluation period..."><?php echo $draft_evaluation ? $draft_evaluation['goals_next_period'] : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="support_needed" class="form-label">
                                    <strong>Support Needed:</strong> What support do you need to succeed?
                                </label>
                                <textarea class="form-control" id="support_needed" name="support_needed" rows="3"
                                          placeholder="Describe any training, resources, or support you need..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <button type="submit" name="save_draft" value="1" class="btn btn-secondary me-md-2">
                            <i class="fas fa-save me-1"></i> Save Draft
                        </button>
                        <button type="submit" name="submit_evaluation" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-1"></i> Submit Final Evaluation
                        </button>
                    </div>
                </form>
                
                <!-- Save Draft Button (Mobile) -->
                <button type="button" class="btn btn-secondary save-draft-btn d-lg-none" onclick="document.getElementById('evaluationForm').submit();">
                    <i class="fas fa-save"></i>
                </button>
                
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize variables
        let totalCompetencies = <?php echo $competencyIndex; ?>;
        let completedCompetencies = 0;
        let categoryScores = {};
        
        // Set total count
        document.getElementById('totalCount').textContent = totalCompetencies;
        
        // Initialize category tracking
        <?php foreach ($groupedCompetencies as $groupName => $groupData): ?>
        categoryScores['<?php echo $groupName; ?>'] = {
            total: <?php echo count($groupData['competencies']); ?>,
            completed: 0
        };
        <?php endforeach; ?>
        
        // Track which competencies have been rated
        let ratedCompetencies = new Set();
        
        function updateRating(competencyId, value) {
            // Update display
            document.getElementById('score_' + competencyId).textContent = value;
            
            // Track completion
            if (!ratedCompetencies.has(competencyId)) {
                ratedCompetencies.add(competencyId);
                completedCompetencies++;
                
                // Update category completion
                let slider = document.getElementById('score_' + competencyId);
                let category = slider.getAttribute('data-category');
                if (category && categoryScores[category]) {
                    categoryScores[category].completed++;
                }
                
                updateProgress();
            }
            
            // Change color based on score
            let scoreDisplay = document.getElementById('score_' + competencyId);
            if (value <= 4) {
                scoreDisplay.style.color = '#dc3545'; // Red
            } else if (value <= 7) {
                scoreDisplay.style.color = '#ffc107'; // Yellow
            } else {
                scoreDisplay.style.color = '#28a745'; // Green
            }
        }
        
        function updateProgress() {
            let percentage = Math.round((completedCompetencies / totalCompetencies) * 100);
            let progressBar = document.getElementById('progressBar');
            progressBar.style.width = percentage + '%';
            progressBar.textContent = percentage + '%';
            
            document.getElementById('completedCount').textContent = completedCompetencies;
            
            // Update category progress
            let categoryHtml = '';
            for (let category in categoryScores) {
                let catData = categoryScores[category];
                let catPercentage = Math.round((catData.completed / catData.total) * 100);
                categoryHtml += `
                    <div class="mb-2">
                        <small>${category}</small>
                        <div class="progress" style="height: 15px;">
                            <div class="progress-bar bg-success" style="width: ${catPercentage}%">
                                ${catData.completed}/${catData.total}
                            </div>
                        </div>
                    </div>
                `;
            }
            document.getElementById('categoryProgress').innerHTML = categoryHtml;
        }
        
        // Initialize all sliders with their current values
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.rating-slider').forEach(slider => {
                updateRating(slider.id.replace('score_', ''), slider.value);
            });
        });
        
        // Warn before leaving if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (completedCompetencies > 0) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Form validation before submission
        document.getElementById('evaluationForm').addEventListener('submit', function(e) {
            if (completedCompetencies < totalCompetencies) {
                if (!confirm(`You have only completed ${completedCompetencies} out of ${totalCompetencies} competencies. Are you sure you want to submit?`)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>