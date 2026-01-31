<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('exams.php', 'error', 'Invalid security token.');
    }
    
    $action = $_POST['action'];
    
    if ($action == 'create' || $action == 'edit') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $duration = intval($_POST['duration']);
        $passing_marks = intval($_POST['passing_marks']);
        $status = sanitizeInput($_POST['status']);
        $allow_retake = isset($_POST['allow_retake']) ? 1 : 0;
        
        if (empty($title) || $duration <= 0) {
            redirectWithMessage('exams.php', 'error', 'Please fill all required fields.');
        }
        
        if ($action == 'create') {
            $stmt = $conn->prepare("INSERT INTO exams (title, description, duration, passing_marks, status, allow_retake, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $user_id = getCurrentUserId();
            $stmt->bind_param("ssiisii", $title, $description, $duration, $passing_marks, $status, $allow_retake, $user_id);
            
            if ($stmt->execute()) {
                redirectWithMessage('exams.php', 'success', 'Exam created successfully!');
            } else {
                redirectWithMessage('exams.php', 'error', 'Failed to create exam.');
            }
        } else {
            $exam_id = intval($_POST['exam_id']);
            $stmt = $conn->prepare("UPDATE exams SET title = ?, description = ?, duration = ?, passing_marks = ?, status = ?, allow_retake = ? WHERE id = ?");
            $stmt->bind_param("ssiisii", $title, $description, $duration, $passing_marks, $status, $allow_retake, $exam_id);
            
            if ($stmt->execute()) {
                redirectWithMessage('exams.php', 'success', 'Exam updated successfully!');
            } else {
                redirectWithMessage('exams.php', 'error', 'Failed to update exam.');
            }
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        $exam_id = intval($_POST['exam_id']);
        $stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
        $stmt->bind_param("i", $exam_id);
        
        if ($stmt->execute()) {
            redirectWithMessage('exams.php', 'success', 'Exam deleted successfully!');
        } else {
            redirectWithMessage('exams.php', 'error', 'Failed to delete exam.');
        }
        $stmt->close();
    }
}

// Get all exams
$exams = $conn->query("
    SELECT e.*, u.full_name as creator_name,
           (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count
    FROM exams e
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.created_at DESC
");

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - Admin</title>
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
                            <a class="nav-link active" href="exams.php">
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
                        <h2><i class="fas fa-file-alt"></i> Manage Exams</h2>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#examModal" onclick="resetExamForm()">
                            <i class="fas fa-plus"></i> Create New Exam
                        </button>
                    </div>

                    <?php displayFlashMessage(); ?>

                    <!-- Exams Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if ($exams->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Title</th>
                                                <th>Duration</th>
                                                <th>Pass %</th>
                                                <th>Questions</th>
                                                <th>Status</th>
                                                <th>Retake</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($exam = $exams->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $exam['id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($exam['title']); ?></strong>
                                                        <?php if ($exam['description']): ?>
                                                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($exam['description']), 0, 50); ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo formatDuration($exam['duration']); ?></td>
                                                    <td><?php echo $exam['passing_marks']; ?>%</td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo $exam['question_count']; ?> questions</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo getExamStatusBadge($exam['status']); ?>">
                                                            <?php echo ucfirst($exam['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($exam['allow_retake']): ?>
                                                            <span class="badge badge-success">Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo formatDate($exam['created_at']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-info" onclick='editExam(<?php echo json_encode($exam); ?>)' title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <a href="questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary" title="Manage Questions">
                                                                <i class="fas fa-question-circle"></i>
                                                            </a>
                                                            <button class="btn btn-danger" onclick="deleteExam(<?php echo $exam['id']; ?>, '<?php echo htmlspecialchars($exam['title']); ?>')" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No exams created yet. Click "Create New Exam" to get started.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Exam Modal -->
    <div class="modal fade" id="examModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" id="modalAction" value="create">
                    <input type="hidden" name="exam_id" id="examId" value="">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle"></i> Create New Exam
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title">Exam Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duration">Duration (minutes) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="passing_marks">Passing Percentage (%)</label>
                                    <input type="number" class="form-control" id="passing_marks" name="passing_marks" min="0" max="100" value="50">
                                    <small class="form-text text-muted">Students must score at least this percentage to pass</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="custom-control custom-checkbox mt-2">
                                        <input type="checkbox" class="custom-control-input" id="allow_retake" name="allow_retake">
                                        <label class="custom-control-label" for="allow_retake">Allow Retake</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="exam_id" id="deleteExamId">
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <p>Are you sure you want to delete the exam "<strong id="deleteExamTitle"></strong>"?</p>
                        <p class="text-danger">This will also delete all associated questions and student attempts. This action cannot be undone.</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function resetExamForm() {
            $('#modalAction').val('create');
            $('#examId').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle"></i> Create New Exam');
            $('#title').val('');
            $('#description').val('');
            $('#duration').val('');
            $('#passing_marks').val('50');
            $('#status').val('draft');
            $('#allow_retake').prop('checked', false);
        }
        
        function editExam(exam) {
            $('#modalAction').val('edit');
            $('#examId').val(exam.id);
            $('#modalTitle').html('<i class="fas fa-edit"></i> Edit Exam');
            $('#title').val(exam.title);
            $('#description').val(exam.description);
            $('#duration').val(exam.duration);
            $('#passing_marks').val(exam.passing_marks);
            $('#status').val(exam.status);
            $('#allow_retake').prop('checked', exam.allow_retake == 1);
            $('#examModal').modal('show');
        }
        
        function deleteExam(id, title) {
            $('#deleteExamId').val(id);
            $('#deleteExamTitle').text(title);
            $('#deleteModal').modal('show');
        }
    </script>
</body>
</html>