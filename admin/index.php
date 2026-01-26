<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Get dashboard statistics
$stats = [];

// Total exams
$result = $conn->query("SELECT COUNT(*) as count FROM exams");
$stats['total_exams'] = $result->fetch_assoc()['count'];

// Active exams
$result = $conn->query("SELECT COUNT(*) as count FROM exams WHERE status = 'active'");
$stats['active_exams'] = $result->fetch_assoc()['count'];

// Total students
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

// Total questions
$result = $conn->query("SELECT COUNT(*) as count FROM questions");
$stats['total_questions'] = $result->fetch_assoc()['count'];

// Recent exam attempts
$recent_attempts = $conn->query("
    SELECT ea.*, e.title as exam_title, u.full_name as student_name 
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    JOIN users u ON ea.student_id = u.id
    ORDER BY ea.created_at DESC
    LIMIT 5
");

// Recent exams
$recent_exams = $conn->query("
    SELECT * FROM exams 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Examination System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap"></i> Exam System - Admin
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <span class="navbar-text text-white mr-3">
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

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="exams.php">
                                <i class="fas fa-file-alt"></i> Manage Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="questions.php">
                                <i class="fas fa-question-circle"></i> Manage Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-users"></i> Manage Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="results.php">
                                <i class="fas fa-chart-bar"></i> View Results
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-10 ml-sm-auto px-md-4">
                <div class="dashboard-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                    </div>

                    <?php displayFlashMessage(); ?>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">Total Exams</h6>
                                            <h2 class="mb-0 mt-2"><?php echo $stats['total_exams']; ?></h2>
                                        </div>
                                        <div class="stat-icon">
                                            <i class="fas fa-file-alt"></i>
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
                                            <h6 class="mb-0">Active Exams</h6>
                                            <h2 class="mb-0 mt-2"><?php echo $stats['active_exams']; ?></h2>
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
                                            <h6 class="mb-0">Total Students</h6>
                                            <h2 class="mb-0 mt-2"><?php echo $stats['total_students']; ?></h2>
                                        </div>
                                        <div class="stat-icon">
                                            <i class="fas fa-users"></i>
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
                                            <h6 class="mb-0">Total Questions</h6>
                                            <h2 class="mb-0 mt-2"><?php echo $stats['total_questions']; ?></h2>
                                        </div>
                                        <div class="stat-icon">
                                            <i class="fas fa-question-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Exam Attempts -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-history"></i> Recent Exam Attempts
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_attempts->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Exam</th>
                                                        <th>Score</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($attempt = $recent_attempts->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                                            <td>
                                                                <strong><?php echo $attempt['score']; ?></strong> / <?php echo $attempt['total_marks']; ?>
                                                                (<?php echo $attempt['percentage']; ?>%)
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
                                        <p class="text-muted text-center mb-0">No exam attempts yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Exams -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-file-alt"></i> Recent Exams</span>
                                    <a href="exams.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Create Exam
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_exams->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Duration</th>
                                                        <th>Total Marks</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($exam = $recent_exams->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                                            <td><?php echo formatDuration($exam['duration']); ?></td>
                                                            <td><?php echo $exam['total_marks']; ?></td>
                                                            <td>
                                                                <span class="badge <?php echo getExamStatusBadge($exam['status']); ?>">
                                                                    <?php echo ucfirst($exam['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo formatDate($exam['created_at']); ?></td>
                                                            <td>
                                                                <a href="questions.php?exam_id=<?php echo $exam['id']; ?>" 
                                                                   class="btn btn-sm btn-info" title="Manage Questions">
                                                                    <i class="fas fa-question-circle"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0">No exams created yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>