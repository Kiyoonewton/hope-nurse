<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = getCurrentUserId();

// Get statistics
$stats = [];

// Total exams available
$result = $conn->query("SELECT COUNT(*) as count FROM exams WHERE status = 'active'");
$stats['available_exams'] = $result->fetch_assoc()['count'];

// Exams taken
$stmt = $conn->prepare("SELECT COUNT(DISTINCT exam_id) as count FROM exam_attempts WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['exams_taken'] = $result->fetch_assoc()['count'];

// Completed exams
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM exam_attempts WHERE student_id = ? AND status = 'submitted'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['completed_exams'] = $result->fetch_assoc()['count'];

// Average score
$stmt = $conn->prepare("SELECT ROUND(AVG(percentage), 2) as avg FROM exam_attempts WHERE student_id = ? AND status = 'submitted'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$avg_result = $result->fetch_assoc();
$stats['average_score'] = $avg_result['avg'] ?? 0;

// Get available exams (active exams that student hasn't taken or can retake)
$available_exams = $conn->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
           (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND student_id = $student_id) as attempt_count
    FROM exams e
    WHERE e.status = 'active'
    AND (
        e.allow_retake = 1
        OR NOT EXISTS (
            SELECT 1 FROM exam_attempts ea 
            WHERE ea.exam_id = e.id AND ea.student_id = $student_id
        )
    )
    ORDER BY e.created_at DESC
");

// Get recent attempts
$recent_attempts = $conn->query("
    SELECT ea.*, e.title as exam_title
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.student_id = $student_id
    ORDER BY ea.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Online Examination System</title>
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
                        <a class="nav-link" href="my-results.php">
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
            <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Dashboard</h2>

            <?php displayFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Available Exams</h6>
                                    <h2 class="mb-0 mt-2"><?php echo $stats['available_exams']; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
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
                                    <h6 class="mb-0">Exams Taken</h6>
                                    <h2 class="mb-0 mt-2"><?php echo $stats['exams_taken']; ?></h2>
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
                                    <h2 class="mb-0 mt-2"><?php echo $stats['completed_exams']; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
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
                                    <h2 class="mb-0 mt-2"><?php echo $stats['average_score']; ?>%</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Exams -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Available Exams</h5>
                </div>
                <div class="card-body">
                    <?php if ($available_exams->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($exam = $available_exams->fetch_assoc()): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card exam-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                            <p class="card-text text-muted"><?php echo htmlspecialchars($exam['description']); ?></p>
                                            
                                            <div class="mb-3">
                                                <span class="badge badge-info">
                                                    <i class="fas fa-question-circle"></i> <?php echo $exam['question_count']; ?> Questions
                                                </span>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> <?php echo formatDuration($exam['duration']); ?>
                                                </span>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-star"></i> <?php echo $exam['total_marks']; ?> Marks
                                                </span>
                                            </div>
                                            
                                            <?php if ($exam['attempt_count'] > 0): ?>
                                                <p class="text-muted small mb-2">
                                                    <i class="fas fa-info-circle"></i> You have taken this exam <?php echo $exam['attempt_count']; ?> time(s)
                                                </p>
                                            <?php endif; ?>
                                            
                                            <a href="exam-instructions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-block">
                                                <i class="fas fa-play"></i> Start Exam
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No exams available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Attempts -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Attempts</h5>
                    <a href="my-results.php" class="btn btn-sm btn-primary">View All Results</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_attempts->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($attempt = $recent_attempts->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($attempt['exam_title']); ?></strong></td>
                                            <td><?php echo $attempt['score']; ?> / <?php echo $attempt['total_marks']; ?></td>
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
                                            <td><?php echo formatDateTime($attempt['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't taken any exams yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>