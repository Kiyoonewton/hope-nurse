<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('students.php', 'error', 'Invalid security token.');
    }
    
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            redirectWithMessage('students.php', 'error', 'All fields are required.');
        }
        
        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            redirectWithMessage('students.php', 'error', 'Username or email already exists.');
        }
        
        $hashed_password = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'student')");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
        
        if ($stmt->execute()) {
            redirectWithMessage('students.php', 'success', 'Student created successfully!');
        } else {
            redirectWithMessage('students.php', 'error', 'Failed to create student.');
        }
        $stmt->close();
    } elseif ($action == 'edit') {
        $user_id = intval($_POST['user_id']);
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $status = sanitizeInput($_POST['status']);
        $password = $_POST['password'];
        
        if (!empty($password)) {
            $hashed_password = hashPassword($password);
            $stmt = $conn->prepare("UPDATE users SET email = ?, full_name = ?, status = ?, password = ? WHERE id = ? AND role = 'student'");
            $stmt->bind_param("ssssi", $email, $full_name, $status, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ?, full_name = ?, status = ? WHERE id = ? AND role = 'student'");
            $stmt->bind_param("sssi", $email, $full_name, $status, $user_id);
        }
        
        if ($stmt->execute()) {
            redirectWithMessage('students.php', 'success', 'Student updated successfully!');
        } else {
            redirectWithMessage('students.php', 'error', 'Failed to update student.');
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            redirectWithMessage('students.php', 'success', 'Student deleted successfully!');
        } else {
            redirectWithMessage('students.php', 'error', 'Failed to delete student.');
        }
        $stmt->close();
    }
}

// Get all students with their exam statistics
$students = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT ea.id) as total_attempts,
           COUNT(DISTINCT CASE WHEN ea.status = 'submitted' THEN ea.id END) as completed_attempts,
           ROUND(AVG(CASE WHEN ea.status = 'submitted' THEN ea.percentage END), 2) as avg_percentage
    FROM users u
    LEFT JOIN exam_attempts ea ON u.id = ea.student_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
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
                            <a class="nav-link active" href="students.php">
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
                        <h2><i class="fas fa-users"></i> Manage Students</h2>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#studentModal" onclick="resetStudentForm()">
                            <i class="fas fa-plus"></i> Add New Student
                        </button>
                    </div>

                    <?php displayFlashMessage(); ?>

                    <!-- Students Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if ($students->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Exams Taken</th>
                                                <th>Completed</th>
                                                <th>Avg Score</th>
                                                <th>Registered</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = $students->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $student['id']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($student['username']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td>
                                                        <?php if ($student['status'] == 'active'): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $student['total_attempts']; ?></td>
                                                    <td><?php echo $student['completed_attempts']; ?></td>
                                                    <td>
                                                        <?php if ($student['avg_percentage']): ?>
                                                            <strong><?php echo $student['avg_percentage']; ?>%</strong>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo formatDate($student['created_at']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-info" onclick='editStudent(<?php echo json_encode($student); ?>)' title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <a href="results.php?student_id=<?php echo $student['id']; ?>" class="btn btn-primary" title="View Results">
                                                                <i class="fas fa-chart-bar"></i>
                                                            </a>
                                                            <button class="btn btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['username']); ?>')" title="Delete">
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
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No students registered yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Student Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" id="modalAction" value="create">
                    <input type="hidden" name="user_id" id="userId" value="">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle"></i> Add New Student
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="form-group" id="usernameGroup">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group" id="statusGroup" style="display:none;">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password <span id="passwordRequired" class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted" id="passwordHelp">
                                Minimum 6 characters
                            </small>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <p>Are you sure you want to delete student "<strong id="deleteStudentName"></strong>"?</p>
                        <p class="text-danger">This will also delete all exam attempts by this student. This action cannot be undone.</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Student
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
        function resetStudentForm() {
            $('#modalAction').val('create');
            $('#userId').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle"></i> Add New Student');
            $('#username').val('').prop('readonly', false);
            $('#full_name').val('');
            $('#email').val('');
            $('#password').val('').prop('required', true);
            $('#status').val('active');
            $('#usernameGroup').show();
            $('#statusGroup').hide();
            $('#passwordRequired').show();
            $('#passwordHelp').text('Minimum 6 characters');
        }
        
        function editStudent(student) {
            $('#modalAction').val('edit');
            $('#userId').val(student.id);
            $('#modalTitle').html('<i class="fas fa-edit"></i> Edit Student');
            $('#username').val(student.username).prop('readonly', true);
            $('#full_name').val(student.full_name);
            $('#email').val(student.email);
            $('#password').val('').prop('required', false);
            $('#status').val(student.status);
            $('#usernameGroup').hide();
            $('#statusGroup').show();
            $('#passwordRequired').hide();
            $('#passwordHelp').text('Leave blank to keep current password');
            $('#studentModal').modal('show');
        }
        
        function deleteStudent(id, username) {
            $('#deleteUserId').val(id);
            $('#deleteStudentName').text(username);
            $('#deleteModal').modal('show');
        }
    </script>
</body>
</html>