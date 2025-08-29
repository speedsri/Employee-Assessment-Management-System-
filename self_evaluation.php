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

// Define all competency categories and their competencies
$competencyData = [
    'Personal Excellence' => [
        'description' => 'Core competencies related to personal performance and self-management',
        'competencies' => [
            'Achievement Orientation' => [
                'description' => 'Concern for meeting or exceeding a standard of excellence and focusing attention to achieve effective outcomes.',
                'positive' => [
                    'Striving to beat deadlines',
                    'Setting your own high standards for quality of work beyond what is normally expected',
                    'Aggressively seeking better ways to do things',
                    'Improving the profitability of your store or division'
                ],
                'negative' => [
                    'Being satisfied if the project is only a little bit late',
                    'Doing the minimum needed to get by',
                    'Accepting the old way of doing things as the best',
                    'Being satisfied with middle of the pack performance'
                ]
            ],
            'Initiative' => [
                'description' => 'A bias for taking action, proactively doing things and not simply thinking about future actions.',
                'positive' => [
                    'Thinking ahead about possible problems and taking action to prevent or resolve them',
                    'Looking ahead at trends and acting to ensure your group will have necessary skills',
                    'Initiating course of action that will lead to improved performance',
                    'Volunteering information or pointing out concerns even if not directly involved'
                ],
                'negative' => [
                    'Continually putting out fires',
                    'Focusing on the immediate concerns of your group',
                    'Hoping that your group will get better over time',
                    'Keeping your nose out of other people\'s business'
                ]
            ],
            'Self-Confidence' => [
                'description' => 'Belief in and ability to demonstrate one\'s own capability to accomplish a task.',
                'positive' => [
                    'Willingly taking on a challenging new assignment',
                    'Standing up for your ideas in the face of criticism',
                    'Taking action based on your expertise and understanding'
                ],
                'negative' => [
                    'Hesitating to tackle a difficult project',
                    'Backing down when someone criticizes your position',
                    'Checking everything with your manager before proceeding'
                ]
            ],
            'Self-Control' => [
                'description' => 'Ability to keep emotions under control and restrain negative reactions when provoked.',
                'positive' => [
                    'Ignoring rude behavior and focusing on solving problems',
                    'Remaining polite and in control under pressure',
                    'Taking steps to calm upset colleagues or customers',
                    'Maintaining open perspective and displaying empathy'
                ],
                'negative' => [
                    'Expressing strong negative emotions in response to difficult customers',
                    'Giving into unreasonable demands or blaming others',
                    'Refusing to get involved in emotionally charged situations',
                    'Refusing to look for alternatives to resolve problems'
                ]
            ]
        ]
    ],
    'Strategic Thinking' => [
        'description' => 'Competencies related to planning, analysis, and strategic decision-making',
        'competencies' => [
            'Business/Strategic Orientation' => [
                'description' => 'Ability to understand business implications and improve organizational performance.',
                'positive' => [
                    'The ability to visualize what might or could be',
                    'Identifying unmet needs of existing or potential customers',
                    'Understanding competition and their impact',
                    'Developing contingency plans with ready resources',
                    'Aligning division goals with organizational strategic plan'
                ],
                'negative' => [
                    'Day to day approach to handling issues',
                    'Focusing only on present needs while neglecting future',
                    'Waiting for competitors to launch before reacting',
                    'Focusing on short-term objectives only',
                    'Meeting only your own goals without considering big picture'
                ]
            ],
            'Critical Thinking' => [
                'description' => 'Ability to comprehend situations by breaking them into components and identifying key issues.',
                'positive' => [
                    'Resolving problems in a systematic, step by step way',
                    'Thinking about the chain of causal factors',
                    'Thinking ahead about outcomes of actions',
                    'Planning for long-term consequences'
                ],
                'negative' => [
                    'Quickly trying whatever comes to mind',
                    'Explaining problems in vague, general ways',
                    'Doing work without thinking through impacts',
                    'Dealing with situations without considering long-term impact'
                ]
            ],
            'Innovation' => [
                'description' => 'Generating new solutions and implementing creative approaches for improved performance.',
                'positive' => [
                    'Trying new approaches',
                    'Re-assessing systems and procedures for better ways',
                    'Making suggestions for improvement and seeing them through'
                ],
                'negative' => [
                    'Solving problems the way they\'ve always been done',
                    'Sticking to established procedures',
                    'Ignoring others\' suggestions for improvement'
                ]
            ]
        ]
    ],
    'Quality & Knowledge' => [
        'description' => 'Competencies focused on maintaining standards and sharing expertise',
        'competencies' => [
            'Concern for Order and Quality' => [
                'description' => 'Drive to increase certainty and maintain high standards in work processes.',
                'positive' => [
                    'Getting detailed understanding of relevant systems and programs',
                    'Systematically checking work to ensure correctness',
                    'Keeping records well organized and up to date',
                    'Monitoring progress against goals and deadlines'
                ],
                'negative' => [
                    'Understanding only big picture, letting others handle details',
                    'Being somewhat confident that almost everything is correct',
                    'Keeping everything in your head',
                    'Letting management worry about deadlines'
                ]
            ],
            'Expertise' => [
                'description' => 'Ability to distribute expert technical and procedural knowledge to others.',
                'positive' => [
                    'Spontaneously sharing helpful information with colleagues',
                    'Learning everything possible about your area',
                    'Explaining why procedures are set up certain ways'
                ],
                'negative' => [
                    'Hoarding information and expertise',
                    'Learning just enough to get by',
                    'Explaining only the steps without context'
                ]
            ],
            'Information Seeking' => [
                'description' => 'Taking action to find out more and improve understanding about situations.',
                'positive' => [
                    'Asking probing questions to ensure understanding',
                    'Digging to resolve data discrepancies',
                    'Getting extra information from industry experts',
                    'Reading manuals and technical journals thoroughly'
                ],
                'negative' => [
                    'Doing tasks without asking why',
                    'Proceeding with probably correct information',
                    'Relying only on immediate contacts for information',
                    'Learning just enough to deal with immediate problems'
                ]
            ]
        ]
    ],
    'People Leadership' => [
        'description' => 'Competencies for leading, developing, and working with others',
        'competencies' => [
            'Developing People' => [
                'description' => 'Fostering long-term learning and development of others.',
                'positive' => [
                    'Actively seeking challenging opportunities for staff growth',
                    'Providing constructive criticism and reassurance after setbacks',
                    'Giving ongoing daily feedback on work',
                    'Empowering staff to lead their own development'
                ],
                'negative' => [
                    'Assigning only familiar work staff already excel at',
                    'Not directly telling employees what to improve',
                    'Waiting until annual reviews to give feedback',
                    'Keeping tight control over all decisions'
                ]
            ],
            'Directiveness' => [
                'description' => 'Intent to make others comply appropriately using position power effectively.',
                'positive' => [
                    'Giving clear instructions with expectations',
                    'Clearly explaining when expectations are unreasonable',
                    'Confronting people when performance is not up to standard'
                ],
                'negative' => [
                    'Giving assignments without deadlines or quality requirements',
                    'Complaining about requests without confronting directly',
                    'Being reluctant to address inadequate performance'
                ]
            ],
            'Team Leadership' => [
                'description' => 'Ability to perform as the leader of a team or group.',
                'positive' => [
                    'Keeping team informed about decisions and rationale',
                    'Setting direction and providing role clarity',
                    'Clearing away barriers for staff success',
                    'Making ongoing efforts to enhance morale'
                ],
                'negative' => [
                    'Dictating orders on need-to-know basis',
                    'Being nice to everyone without tough decisions',
                    'Believing you must do everything yourself',
                    'Being only the technical expert'
                ]
            ],
            'Teamwork' => [
                'description' => 'Ability to work cooperatively to achieve group and organizational goals.',
                'positive' => [
                    'Drawing on skills and viewpoints of team members',
                    'Keeping others informed with useful information',
                    'Supporting and encouraging team members',
                    'Speaking positively about team members'
                ],
                'negative' => [
                    'Preferring to work alone',
                    'Attending meetings without contributing',
                    'Competing with team members',
                    'Making negative comments about others'
                ]
            ]
        ]
    ],
    'Interpersonal Skills' => [
        'description' => 'Competencies for effective interaction and relationship management',
        'competencies' => [
            'Flexibility' => [
                'description' => 'Understanding different perspectives and adapting approach as situations change.',
                'positive' => [
                    'Giving up preferred approach to meet division needs',
                    'Changing plans when new information shows better path',
                    'Providing service in way most comfortable for customers'
                ],
                'negative' => [
                    'Sticking firmly to your preferred way',
                    'Staying with original plan regardless of new information',
                    'Approaching others only at your comfort level'
                ]
            ],
            'Impact and Influence' => [
                'description' => 'Ability to influence others through persuasive arguments and negotiation.',
                'positive' => [
                    'Considering others\' viewpoints to bring them onside',
                    'Trying different tactics until you succeed',
                    'Thinking through approach before presentations',
                    'Anticipating how people will respond'
                ],
                'negative' => [
                    'Telling it only from your perspective',
                    'Giving up after single attempt',
                    'Being too busy for communication niceties',
                    'Thinking only about practical matters'
                ]
            ],
            'Listening, Understanding and Responding' => [
                'description' => 'Ability to interact effectively through accurate listening and appropriate response.',
                'positive' => [
                    'Creating opportunities for meaningful discussion',
                    'Deferring judgment and seeking more information',
                    'Recognizing non-verbal behavior mismatches',
                    'Recognizing underlying unexpressed concerns'
                ],
                'negative' => [
                    'Rushing through interactions',
                    'Jumping to solutions prematurely',
                    'Accepting everything at face value',
                    'Ignoring emotional undercurrents'
                ]
            ],
            'Relationship Building' => [
                'description' => 'Building and maintaining networks for achieving work-related goals.',
                'positive' => [
                    'Remembering personal details about customers',
                    'Getting to know colleagues in other divisions',
                    'Serving on community boards for networking',
                    'Building long-term customer relationships'
                ],
                'negative' => [
                    'Keeping things strictly business',
                    'Contacting others only when needing something',
                    'Declining community involvement opportunities',
                    'Focusing only on short-term interactions'
                ]
            ]
        ]
    ],
    'Organizational Excellence' => [
        'description' => 'Competencies for understanding and serving organizational needs',
        'competencies' => [
            'Organizational Awareness' => [
                'description' => 'Understanding and managing power relationships and organizational dynamics.',
                'positive' => [
                    'Identifying opinion leaders and decision makers',
                    'Using informal systems effectively',
                    'Knowing major organizational initiatives',
                    'Understanding community perspectives'
                ],
                'negative' => [
                    'Avoiding complex interdepartmental needs',
                    'Relying only on formal systems',
                    'Acting against organizational norms',
                    'Considering only organizational perspective'
                ]
            ],
            'Personalized Customer Service' => [
                'description' => 'Desire to help and serve others to meet their needs effectively.',
                'positive' => [
                    'Proactively informing customers about relevant products',
                    'Discussing needs and satisfaction regularly',
                    'Following up on referrals',
                    'Finding ways to meet underlying needs'
                ],
                'negative' => [
                    'Waiting for customers to ask',
                    'Assuming customers will complain if unhappy',
                    'Washing hands of problems once passed on',
                    'Responding only to direct requests'
                ]
            ]
        ]
    ]
];

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
                    foreach ($competencyData as $categoryName => $categoryData): 
                    ?>
                    <div class="card mb-4">
                        <div class="category-header">
                            <h3 class="h4 mb-2">
                                <i class="fas fa-layer-group me-2"></i><?php echo $categoryName; ?>
                            </h3>
                            <p class="mb-0"><?php echo $categoryData['description']; ?></p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($categoryData['competencies'] as $competencyName => $competency): 
                                $competencyIndex++;
                                $competencyId = 'comp_' . $competencyIndex;
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
                                                <?php foreach ($competency['positive'] as $indicator): ?>
                                                <li><?php echo $indicator; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="negative-indicators indicator">
                                            <h6><i class="fas fa-times-circle me-1"></i>Doesn't Look Like:</h6>
                                            <ul class="indicator-list mb-0">
                                                <?php foreach ($competency['negative'] as $indicator): ?>
                                                <li><?php echo $indicator; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="slider-container">
                                    <div class="row align-items-center">
                                        <div class="col-md-9">
                                            <label for="<?php echo $competencyId; ?>" class="form-label">
                                                <strong>Your Rating:</strong>
                                            </label>
                                            <input type="range" 
                                                   class="form-range rating-slider" 
                                                   min="1" max="10" step="0.5" value="5.5"
                                                   id="<?php echo $competencyId; ?>" 
                                                   name="scores[<?php echo $competencyId; ?>]"
                                                   data-category="<?php echo htmlspecialchars($categoryName); ?>"
                                                   oninput="updateRating('<?php echo $competencyId; ?>', this.value)">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">1 - Unsatisfactory</small>
                                                <small class="text-muted">5.5 - Meets Expectations</small>
                                                <small class="text-muted">10 - Exceptional</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="score-display" id="score_<?php echo $competencyId; ?>">5.5</div>
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
                                                  placeholder="Provide specific examples or context for your rating..."></textarea>
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
                                          placeholder="List your key strengths and how they contribute to your role..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="development_areas" class="form-label">
                                    <strong>Development Areas:</strong> What are your top 3 areas for improvement?
                                </label>
                                <textarea class="form-control" id="development_areas" name="development_areas" rows="3"
                                          placeholder="Identify areas where you need to develop..."></textarea>
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
                                          placeholder="Set specific, measurable goals for the next evaluation period..."></textarea>
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
                        <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                            <i class="fas fa-save me-1"></i> Save as Draft
                        </button>
                        <button type="button" class="btn btn-info" onclick="previewEvaluation()">
                            <i class="fas fa-eye me-1"></i> Preview
                        </button>
                        <button type="submit" name="submit_evaluation" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-1"></i> Submit Final Evaluation
                        </button>
                    </div>
                </form>
                
                <!-- Save Draft Button (Mobile) -->
                <button type="button" class="btn btn-secondary save-draft-btn d-lg-none" onclick="saveDraft()">
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
        <?php foreach ($competencyData as $categoryName => $categoryData): ?>
        categoryScores['<?php echo $categoryName; ?>'] = {
            total: <?php echo count($categoryData['competencies']); ?>,
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
                let slider = document.getElementById(competencyId);
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
        
        function saveDraft() {
            // Collect form data
            let formData = new FormData(document.getElementById('evaluationForm'));
            formData.append('action', 'save_draft');
            
            fetch('save_draft.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Draft saved successfully!', 'success');
                } else {
                    showNotification('Error saving draft. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error saving draft. Please try again.', 'danger');
            });
        }
        
        function previewEvaluation() {
            // Calculate average scores
            let totalScore = 0;
            let scoredCount = 0;
            let categoryAverages = {};
            
            document.querySelectorAll('.rating-slider').forEach(slider => {
                if (slider.value) {
                    totalScore += parseFloat(slider.value);
                    scoredCount++;
                    
                    let category = slider.getAttribute('data-category');
                    if (!categoryAverages[category]) {
                        categoryAverages[category] = { total: 0, count: 0 };
                    }
                    categoryAverages[category].total += parseFloat(slider.value);
                    categoryAverages[category].count++;
                }
            });
            
            let overallAverage = scoredCount > 0 ? (totalScore / scoredCount).toFixed(1) : 0;
            
            // Build preview HTML
            let previewHtml = `
                <h4>Evaluation Preview</h4>
                <hr>
                <p><strong>Overall Average Score:</strong> ${overallAverage}/10</p>
                <h5>Category Averages:</h5>
                <ul>
            `;
            
            for (let category in categoryAverages) {
                let avg = (categoryAverages[category].total / categoryAverages[category].count).toFixed(1);
                previewHtml += `<li>${category}: ${avg}/10</li>`;
            }
            
            previewHtml += '</ul>';
            
            // Show preview in modal
            showModal('Evaluation Preview', previewHtml);
        }
        
        function showNotification(message, type = 'info') {
            let notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        function showModal(title, content) {
            let modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal fade" id="previewModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                ${content}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            let modalInstance = new bootstrap.Modal(document.getElementById('previewModal'));
            modalInstance.show();
            
            document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
                modal.remove();
            });
        }
        
        // Auto-save draft every 5 minutes
        setInterval(function() {
            if (completedCompetencies > 0) {
                saveDraft();
            }
        }, 300000);
        
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
                e.preventDefault();
                if (!confirm(`You have only completed ${completedCompetencies} out of ${totalCompetencies} competencies. Are you sure you want to submit?`)) {
                    return false;
                }
            }
            
            // Show loading indicator
            showNotification('Submitting your evaluation...', 'info');
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Load saved draft on page load (if exists)
        window.addEventListener('load', function() {
            fetch('get_draft.php?period_id=<?php echo $period['period_id']; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.draft) {
                        // Populate form with draft data
                        for (let competencyId in data.draft.scores) {
                            let slider = document.getElementById(competencyId);
                            if (slider) {
                                slider.value = data.draft.scores[competencyId];
                                updateRating(competencyId, data.draft.scores[competencyId]);
                            }
                        }
                        
                        // Populate comments
                        for (let competencyId in data.draft.comments) {
                            let textarea = document.getElementById('comment_' + competencyId);
                            if (textarea) {
                                textarea.value = data.draft.comments[competencyId];
                            }
                        }
                        
                        // Populate overall assessment fields
                        if (data.draft.overall) {
                            for (let field in data.draft.overall) {
                                let element = document.getElementById(field);
                                if (element) {
                                    element.value = data.draft.overall[field];
                                }
                            }
                        }
                        
                        showNotification('Previous draft loaded successfully', 'success');
                    }
                })
                .catch(error => {
                    console.error('Error loading draft:', error);
                });
        });
    </script>
</body>
</html>