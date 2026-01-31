<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Get exam details
if ($exam_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam_result = $stmt->get_result();
    
    if ($exam_result->num_rows == 0) {
        redirectWithMessage('exams.php', 'error', 'Exam not found.');
    }
    $exam = $exam_result->fetch_assoc();
    $stmt->close();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('questions.php?exam_id=' . $exam_id, 'error', 'Invalid security token.');
    }
    
    $action = $_POST['action'];
    
    if ($action == 'create' || $action == 'edit') {
        $question_exam_id = intval($_POST['exam_id']);
        $question_text = sanitizeInput($_POST['question_text']);
        $question_type = sanitizeInput($_POST['question_type']);
        $marks = intval($_POST['marks']);
        $order_number = intval($_POST['order_number']);
        
        if (empty($question_text) || empty($question_type) || $marks <= 0) {
            redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'error', 'Please fill all required fields.');
        }
        
        if ($action == 'create') {
            $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, marks, order_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $question_exam_id, $question_text, $question_type, $marks, $order_number);
            
            if ($stmt->execute()) {
                $question_id = $stmt->insert_id;
                
                // Handle different question types
                if ($question_type == 'multiple_choice' || $question_type == 'multiple_select') {
                    // Save options
                    if (isset($_POST['options']) && is_array($_POST['options'])) {
                        $stmt_option = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
                        
                        foreach ($_POST['options'] as $index => $option_text) {
                            $option_text = sanitizeInput($option_text);
                            if (!empty($option_text)) {
                                $is_correct = 0;
                                if ($question_type == 'multiple_choice') {
                                    $is_correct = (isset($_POST['correct_option']) && $_POST['correct_option'] == $index) ? 1 : 0;
                                } else {
                                    $is_correct = (isset($_POST['correct_options']) && in_array($index, $_POST['correct_options'])) ? 1 : 0;
                                }
                                
                                $stmt_option->bind_param("isii", $question_id, $option_text, $is_correct, $index);
                                $stmt_option->execute();
                            }
                        }
                        $stmt_option->close();
                    }
                } elseif ($question_type == 'true_false') {
                    $correct_answer = sanitizeInput($_POST['correct_answer']);
                    $stmt_update = $conn->prepare("UPDATE questions SET correct_answer = ? WHERE id = ?");
                    $stmt_update->bind_param("si", $correct_answer, $question_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                } elseif ($question_type == 'short_answer' || $question_type == 'fill_blank') {
                    $correct_answer = sanitizeInput($_POST['correct_answer']);
                    $stmt_update = $conn->prepare("UPDATE questions SET correct_answer = ? WHERE id = ?");
                    $stmt_update->bind_param("si", $correct_answer, $question_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
                
                // Update exam total marks
                updateExamTotalMarks($conn, $question_exam_id);
                
                redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'success', 'Question created successfully!');
            } else {
                redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'error', 'Failed to create question.');
            }
        } else {
            // Edit question
            $question_id = intval($_POST['question_id']);
            $stmt = $conn->prepare("UPDATE questions SET question_text = ?, question_type = ?, marks = ?, order_number = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $question_text, $question_type, $marks, $order_number, $question_id);
            
            if ($stmt->execute()) {
                // Delete existing options
                $conn->query("DELETE FROM question_options WHERE question_id = $question_id");
                
                // Handle different question types (same as create)
                if ($question_type == 'multiple_choice' || $question_type == 'multiple_select') {
                    if (isset($_POST['options']) && is_array($_POST['options'])) {
                        $stmt_option = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
                        
                        foreach ($_POST['options'] as $index => $option_text) {
                            $option_text = sanitizeInput($option_text);
                            if (!empty($option_text)) {
                                $is_correct = 0;
                                if ($question_type == 'multiple_choice') {
                                    $is_correct = (isset($_POST['correct_option']) && $_POST['correct_option'] == $index) ? 1 : 0;
                                } else {
                                    $is_correct = (isset($_POST['correct_options']) && in_array($index, $_POST['correct_options'])) ? 1 : 0;
                                }
                                
                                $stmt_option->bind_param("isii", $question_id, $option_text, $is_correct, $index);
                                $stmt_option->execute();
                            }
                        }
                        $stmt_option->close();
                    }
                } else {
                    $correct_answer = sanitizeInput($_POST['correct_answer']);
                    $stmt_update = $conn->prepare("UPDATE questions SET correct_answer = ? WHERE id = ?");
                    $stmt_update->bind_param("si", $correct_answer, $question_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
                
                // Update exam total marks
                updateExamTotalMarks($conn, $question_exam_id);
                
                redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'success', 'Question updated successfully!');
            } else {
                redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'error', 'Failed to update question.');
            }
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        $question_id = intval($_POST['question_id']);
        $question_exam_id = intval($_POST['exam_id']);
        
        $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $question_id);
        
        if ($stmt->execute()) {
            updateExamTotalMarks($conn, $question_exam_id);
            redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'success', 'Question deleted successfully!');
        } else {
            redirectWithMessage('questions.php?exam_id=' . $question_exam_id, 'error', 'Failed to delete question.');
        }
        $stmt->close();
    }
}

// Function to update exam total marks
function updateExamTotalMarks($conn, $exam_id) {
    $result = $conn->query("SELECT SUM(marks) as total FROM questions WHERE exam_id = $exam_id");
    $row = $result->fetch_assoc();
    $total_marks = $row['total'] ?? 0;
    
    $stmt = $conn->prepare("UPDATE exams SET total_marks = ? WHERE id = ?");
    $stmt->bind_param("ii", $total_marks, $exam_id);
    $stmt->execute();
    $stmt->close();
}

// Get questions for the exam
if ($exam_id > 0) {
    $questions = $conn->query("
        SELECT * FROM questions 
        WHERE exam_id = $exam_id 
        ORDER BY order_number, id
    ");
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Admin</title>
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
                            <a class="nav-link active" href="questions.php">
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
                    <?php if ($exam_id > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2><i class="fas fa-question-circle"></i> Manage Questions</h2>
                                <p class="text-muted mb-0">
                                    Exam: <strong><?php echo htmlspecialchars($exam['title']); ?></strong>
                                    <span class="badge <?php echo getExamStatusBadge($exam['status']); ?> ml-2">
                                        <?php echo ucfirst($exam['status']); ?>
                                    </span>
                                </p>
                                <p class="text-muted">
                                    Total Marks: <strong><?php echo $exam['total_marks']; ?></strong> | 
                                    Duration: <strong><?php echo formatDuration($exam['duration']); ?></strong>
                                </p>
                            </div>
                            <div>
                                <a href="exams.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Exams
                                </a>
                                <button class="btn btn-primary" onclick="showQuestionModal()">
                                    <i class="fas fa-plus"></i> Add Question
                                </button>
                            </div>
                        </div>

                        <?php displayFlashMessage(); ?>

                        <!-- Questions List -->
                        <div class="card">
                            <div class="card-body">
                                <?php if (isset($questions) && $questions->num_rows > 0): ?>
                                    <?php $qnum = 1; while ($question = $questions->fetch_assoc()): ?>
                                        <div class="question-container">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <span class="question-number">Question <?php echo $qnum++; ?></span>
                                                    <span class="badge badge-info ml-2"><?php echo getQuestionTypeDisplay($question['question_type']); ?></span>
                                                    <span class="badge badge-success ml-2"><?php echo $question['marks']; ?> marks</span>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick='editQuestion(<?php echo json_encode($question); ?>, <?php echo $question['id']; ?>)'>
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <p class="mb-3"><strong><?php echo htmlspecialchars($question['question_text']); ?></strong></p>
                                            
                                            <?php
                                            // Display options based on question type
                                            if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'multiple_select') {
                                                $options = $conn->query("SELECT * FROM question_options WHERE question_id = {$question['id']} ORDER BY option_order");
                                                if ($options->num_rows > 0):
                                            ?>
                                                <div class="ml-4">
                                                    <?php while ($option = $options->fetch_assoc()): ?>
                                                        <div class="mb-2">
                                                            <?php if ($option['is_correct']): ?>
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-circle text-muted"></i>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php } elseif ($question['question_type'] == 'true_false') { ?>
                                                <div class="ml-4">
                                                    <p><strong>Correct Answer:</strong> <?php echo htmlspecialchars($question['correct_answer']); ?></p>
                                                </div>
                                            <?php } elseif ($question['question_type'] == 'short_answer' || $question['question_type'] == 'fill_blank') { ?>
                                                <div class="ml-4">
                                                    <p><strong>Expected Answer:</strong> <?php echo htmlspecialchars($question['correct_answer']); ?></p>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No questions added yet. Click "Add Question" to create your first question.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h4>No Exam Selected</h4>
                            <p class="text-muted">Please select an exam from the exams page to manage its questions.</p>
                            <a href="exams.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Go to Exams
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Question Modal -->
    <div class="modal fade" id="questionModal" tabindex="-1">
        <div class="modal-dialog modal-xl ">
            <div class="modal-content">
                <form method="POST" action="" id="questionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" id="modalAction" value="create">
                    <input type="hidden" name="question_id" id="questionId" value="">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle"></i> Add New Question
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="question_text">Question Text <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="question_type">Question Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="question_type" name="question_type" required onchange="changeQuestionType()">
                                        <option value="">Select Type</option>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="multiple_select">Multiple Select</option>
                                        <option value="true_false">True/False</option>
                                        <option value="short_answer">Short Answer</option>
                                        <option value="fill_blank">Fill in the Blank</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="marks">Marks <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="marks" name="marks" min="1" value="1" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="order_number">Order</label>
                                    <input type="number" class="form-control" id="order_number" name="order_number" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Multiple Choice Options -->
                        <div id="multipleChoiceOptions" class="question-type-options" style="display:none;">
                            <hr>
                            <h6><i class="fas fa-list"></i> Options (Select one correct answer)</h6>
                            <div id="optionsContainer"></div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addOption()">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                        
                        <!-- Multiple Select Options -->
                        <div id="multipleSelectOptions" class="question-type-options" style="display:none;">
                            <hr>
                            <h6><i class="fas fa-check-square"></i> Options (Select all correct answers)</h6>
                            <div id="multiSelectContainer"></div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addMultiSelectOption()">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                        
                        <!-- True/False -->
                        <div id="trueFalseOptions" class="question-type-options" style="display:none;">
                            <hr>
                            <div class="form-group">
                                <label>Correct Answer <span class="text-danger">*</span></label>
                                <div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="true_option" name="correct_answer" value="True" class="custom-control-input">
                                        <label class="custom-control-label" for="true_option">True</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="false_option" name="correct_answer" value="False" class="custom-control-input">
                                        <label class="custom-control-label" for="false_option">False</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Short Answer / Fill Blank -->
                        <div id="textAnswerOptions" class="question-type-options" style="display:none;">
                            <hr>
                            <div class="form-group">
                                <label for="correct_answer_text">Expected Answer <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="correct_answer_text" name="correct_answer" placeholder="Enter the expected answer">
                                <small class="form-text text-muted">Note: Short answers may require manual grading</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Question
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
                    <input type="hidden" name="question_id" id="deleteQuestionId">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <p>Are you sure you want to delete this question?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../assets/js/questions.js"></script>
</body>
</html>