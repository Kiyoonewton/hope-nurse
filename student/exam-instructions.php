<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = getCurrentUserId();
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id == 0) {
    redirectWithMessage('index.php', 'error', 'Invalid exam selected.');
}

// Get exam details
$stmt = $conn->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count
    FROM exams e
    WHERE e.id = ? AND e.status = 'active'
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirectWithMessage('index.php', 'error', 'Exam not found or not available.');
}

$exam = $result->fetch_assoc();
$stmt->close();

// Check if student has already taken this exam
$stmt = $conn->prepare("
    SELECT COUNT(*) as attempt_count 
    FROM exam_attempts 
    WHERE student_id = ? AND exam_id = ?
");
$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$attempt_data = $result->fetch_assoc();
$attempt_count = $attempt_data['attempt_count'];
$stmt->close();

// Check if retake is allowed
if ($attempt_count > 0 && !$exam['allow_retake']) {
    redirectWithMessage('index.php', 'error', 'You have already taken this exam. Retakes are not allowed.');
}

// Check for in-progress attempt
$stmt = $conn->prepare("
    SELECT id 
    FROM exam_attempts 
    WHERE student_id = ? AND exam_id = ? AND status = 'in_progress'
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("ii", $student_id, $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $in_progress = $result->fetch_assoc();
    header("Location: take-exam.php?attempt_id=" . $in_progress['id']);
    exit();
}
$stmt->close();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Instructions - <?php echo htmlspecialchars($exam['title']); ?></title>
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
            <div class="ml-auto">
                <span class="navbar-text text-white">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars(getCurrentUserFullName()); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle"></i> Exam Instructions</h4>
                    </div>
                    <div class="card-body">
                        <h3 class="text-center mb-4"><?php echo htmlspecialchars($exam['title']); ?></h3>
                        
                        <?php if ($exam['description']): ?>
                            <div class="alert alert-info">
                                <strong>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($exam['description'])); ?>
                            </div>
                        <?php endif; ?>

                        <div class="row text-center mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <i class="fas fa-question-circle fa-2x text-primary mb-2"></i>
                                        <h5><?php echo $exam['question_count']; ?></h5>
                                        <p class="text-muted mb-0">Questions</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                        <h5><?php echo formatDuration($exam['duration']); ?></h5>
                                        <p class="text-muted mb-0">Duration</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <i class="fas fa-star fa-2x text-success mb-2"></i>
                                        <h5><?php echo $exam['total_marks']; ?></h5>
                                        <p class="text-muted mb-0">Total Marks</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($attempt_count > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Note:</strong> You have already taken this exam <?php echo $attempt_count; ?> time(s). 
                                This will be attempt #<?php echo ($attempt_count + 1); ?>.
                            </div>
                        <?php endif; ?>

                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Important Instructions</h5>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Read each question carefully before answering.</li>
                                    <li>The timer will start immediately when you begin the exam.</li>
                                    <li>You must complete the exam within the allocated time.</li>
                                    <li>The exam will automatically submit when time expires.</li>
                                    <li>You can navigate between questions using the question navigation panel.</li>
                                    <li>Click "Submit Exam" when you're done to finish early.</li>
                                    <li>Do not refresh the page or use the browser back button during the exam.</li>
                                    <li>Switching tabs or minimizing the browser may be monitored.</li>
                                    <li>Once submitted, you cannot change your answers.</li>
                                    <li>Ensure you have a stable internet connection throughout the exam.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-danger">
                            <i class="fas fa-shield-alt"></i> 
                            <strong>Anti-Cheating Notice:</strong> This exam monitors tab switching and window focus changes. 
                            Excessive violations may be flagged for review.
                        </div>

                        <form method="POST" action="take-exam.php" id="startExamForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                            
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="agreeTerms" required>
                                <label class="custom-control-label" for="agreeTerms">
                                    I have read and understood all the instructions and agree to follow the exam rules.
                                </label>
                            </div>

                            <div class="text-center">
                                <a href="index.php" class="btn btn-secondary btn-lg mr-2">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-play"></i> Start Exam
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Prevent accidental navigation away from page
        $('#startExamForm').on('submit', function() {
            $(window).off('beforeunload');
        });
    </script>
</body>
</html>