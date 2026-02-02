<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

// Get filter parameters
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';

if ($student_id > 0) {
    $where_clauses[] = "ea.student_id = ?";
    $params[] = $student_id;
    $types .= 'i';
}

if ($exam_id > 0) {
    $where_clauses[] = "ea.exam_id = ?";
    $params[] = $exam_id;
    $types .= 'i';
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$sql = "
    SELECT ea.*, e.title as exam_title, e.passing_marks, u.full_name as student_name, u.username
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    JOIN users u ON ea.student_id = u.id
    $where_sql
    ORDER BY ea.created_at DESC
";

if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result();
} else {
    $results = $conn->query($sql);
}

// Get all students for filter
$students = $conn->query("SELECT id, username, full_name FROM users WHERE role = 'student' ORDER BY full_name");

// Get all exams for filter
$exams = $conn->query("SELECT id, title FROM exams ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - Admin</title>
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
                            <a class="nav-link" href="index.php">
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
                            <a class="nav-link active" href="results.php">
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
                        <h2><i class="fas fa-chart-bar"></i> Exam Results</h2>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="student_id">Filter by Student</label>
                                            <select class="form-control" id="student_id" name="student_id">
                                                <option value="">All Students</option>
                                                <?php while ($student = $students->fetch_assoc()): ?>
                                                    <option value="<?php echo $student['id']; ?>"
                                                            <?php echo ($student_id == $student['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($student['full_name']) . ' (' . htmlspecialchars($student['username']) . ')'; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="exam_id">Filter by Exam</label>
                                            <select class="form-control" id="exam_id" name="exam_id">
                                                <option value="">All Exams</option>
                                                <?php while ($exam = $exams->fetch_assoc()): ?>
                                                    <option value="<?php echo $exam['id']; ?>"
                                                            <?php echo ($exam_id == $exam['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($exam['title']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if ($results->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Student</th>
                                                <th>Exam</th>
                                                <th>Score</th>
                                                <th>Percentage</th>
                                                <th>Result</th>
                                                <th>Status</th>
                                                <th>Start Time</th>
                                                <th>Duration</th>
                                                <th>Tab Switches</th>
                                                <th>Submitted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($result = $results->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $result['id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($result['student_name']); ?></strong><br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($result['username']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                                    <td>
                                                        <strong><?php echo $result['score']; ?></strong> / <?php echo $result['total_marks']; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $percentage = $result['percentage'];
                                                        $badge_class = 'badge-danger';
                                                        if ($percentage >= 80) $badge_class = 'badge-success';
                                                        elseif ($percentage >= 60) $badge_class = 'badge-warning';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>">
                                                            <?php echo $percentage; ?>%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($result['status'] == 'submitted'): ?>
                                                            <?php if ($percentage >= $result['passing_marks']): ?>
                                                                <span class="badge badge-success"><i class="fas fa-check"></i> Passed</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-danger"><i class="fas fa-times"></i> Failed</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo getAttemptStatusBadge($result['status']); ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $result['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $result['start_time'] ? formatDateTime($result['start_time']) : 'N/A'; ?></td>
                                                    <td>
                                                        <?php if ($result['duration_used']): ?>
                                                            <?php echo formatDuration($result['duration_used']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($result['tab_switches'] > 0): ?>
                                                            <span class="badge badge-warning"><?php echo $result['tab_switches']; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge badge-success">0</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $result['submitted_at'] ? formatDateTime($result['submitted_at']) : 'Not submitted'; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No results found with the selected filters.</p>
                                </div>
                            <?php endif; ?>
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
