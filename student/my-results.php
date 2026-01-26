<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = getCurrentUserId();

// Get all exam attempts for this student
$attempts = $conn->query("
    SELECT ea.*, e.title as exam_title, e.passing_marks
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.student_id = $student_id
    ORDER BY ea.created_at DESC
");

// Get statistics
$stats = [];

// Total attempts
$stats['total_attempts'] = $attempts->num_rows;

// Completed exams
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM exam_attempts 
    WHERE student_id = $student_id AND status = 'submitted'
");
$stats['completed'] = $result->fetch_assoc()['count'];

// Passed exams
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.student_id = $student_id 
    AND ea.status = 'submitted'
    AND ea.score >= e.passing_marks
");
$stats['passed'] = $result->fetch_assoc()['count'];

// Average score
$result = $conn->query("
    SELECT ROUND(AVG(percentage), 2) as avg 
    FROM exam_attempts 
    WHERE student_id = $student_id AND status = 'submitted'
");
$avg_result = $result->fetch_assoc();
$stats['average'] = $avg_result['avg'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Student Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap"></i> Exam System - Student
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my-results.php">
                            <i class="fas fa-chart-line"></i> My Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text text-white mx-3">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars(getCurrentUserFullName()); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="dashboard-container">
            <h2 class="mb-4"><i class="fas fa-chart-line"></i> My Exam Results</h2>

            <?php displayFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Attempts</h6>
                                    <h2 class="mb-0 mt-2"><?php echo $stats['total_attempts']; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-pen"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Completed</h6>
                                    <h2 class="mb-0 mt-2"><?php echo $stats['completed']; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Passed</h6>
                                    <h2 class="mb-0 mt-2"><?php echo $stats['passed']; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Average Score</h6>
                                    <h2 class="mb-0 mt-2"><?php echo $stats['average']; ?>%</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Exam Attempts</h5>
                </div>
                <div class="card-body">
                    <?php if ($attempts->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam Title</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Result</th>
                                        <th>Duration</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $attempts->data_seek(0); // Reset pointer
                                    while ($attempt = $attempts->fetch_assoc()):
                                        $passed = $attempt['score'] >= $attempt['passing_marks'];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($attempt['exam_title']); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo $attempt['score']; ?></strong> / <?php echo $attempt['total_marks']; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $percentage = $attempt['percentage'];
                                                $badge_class = 'badge-danger';
                                                if ($percentage >= 80) $badge_class = 'badge-success';
                                                elseif ($percentage >= 60) $badge_class = 'badge-warning';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo $percentage; ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getAttemptStatusBadge($attempt['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $attempt['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($attempt['status'] == 'submitted'): ?>
                                                    <?php if ($passed): ?>
                                                        <span class="badge badge-success"><i class="fas fa-check"></i> Passed</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Failed</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($attempt['duration_used']): ?>
                                                    <?php echo formatDuration($attempt['duration_used']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($attempt['created_at']); ?></td>
                                            <td>
                                                <?php if ($attempt['status'] == 'submitted'): ?>
                                                    <a href="exam-review.php?attempt_id=<?php echo $attempt['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> Review
                                                    </a>
                                                <?php elseif ($attempt['status'] == 'in_progress'): ?>
                                                    <a href="take-exam.php?attempt_id=<?php echo $attempt['id']; ?>" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-play"></i> Continue
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't taken any exams yet.</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Go to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Chart Placeholder -->
            <?php if ($stats['completed'] > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Performance Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Pass Rate</h6>
                                <div class="progress" style="height: 30px;">
                                    <?php
                                    $pass_rate = ($stats['passed'] / $stats['completed']) * 100;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $pass_rate; ?>%">
                                        <?php echo round($pass_rate, 1); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $stats['passed']; ?> passed out of <?php echo $stats['completed']; ?> completed
                                </small>
                            </div>
                            <div class="col-md-6">
                                <h6>Average Performance</h6>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar <?php echo $stats['average'] >= 80 ? 'bg-success' : ($stats['average'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $stats['average']; ?>%">
                                        <?php echo $stats['average']; ?>%
                                    </div>
                                </div>
                                <small class="text-muted">Average score across all completed exams</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>