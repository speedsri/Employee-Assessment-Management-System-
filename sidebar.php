<?php
// This file should be included in the sidebar section of every page
// It contains the sidebar menu
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar d-md-block">
    <div class="list-group">
        <a href="index.php" class="list-group-item list-group-item-action">
            <i class="fas fa-home me-2"></i> Dashboard
        </a>
        <a href="self_evaluation.php" class="list-group-item list-group-item-action">
            <i class="fas fa-clipboard-check me-2"></i> Self Evaluation
        </a>
        <?php if (in_array(getUserRole(), ['manager', 'hr_admin', 'system_admin'])): ?>
        <a href="team_evaluations.php" class="list-group-item list-group-item-action">
            <i class="fas fa-users me-2"></i> Team Evaluations
        </a>
        <?php endif; ?>
        <?php if (in_array(getUserRole(), ['hr_admin', 'system_admin'])): ?>
        <a href="reports.php" class="list-group-item list-group-item-action">
            <i class="fas fa-chart-bar me-2"></i> Reports
        </a>
        <a href="management.php" class="list-group-item list-group-item-action">
            <i class="fas fa-cog me-2"></i> Management
        </a>
        <?php endif; ?>
        <a href="help.php" class="list-group-item list-group-item-action">
            <i class="fas fa-question-circle me-2"></i> Help
        </a>
    </div>
</div>