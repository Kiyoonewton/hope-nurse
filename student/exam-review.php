<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = getCurrentUserId();
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

if ($attempt_id == 0) {
    redirectWithMessage('my-results.php', 'error', 'Invalid exam attempt.');
}

// Get attempt details
$stmt = $conn->prepare("
    SELECT ea.*, e.title, e.description, e.passing_marks
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.id = ? AND ea.student_id = ?
");
$stmt->bind_param("ii", $attempt_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirectWithMessage('my-results.php', 'error', 'Exam attempt not found.');
}

$attempt = $result->fetch_assoc();
$exam_id = $attempt['exam_id'];
$passed = $attempt['score'] >= $attempt['passing_marks'];
$stmt->close();

// Get questions and answers
$questions = $conn->query("
    SELECT * FROM questions 
    WHERE exam_id = $exam_id 
    ORDER BY order_number, id
");

// Get student answers
$stmt = $conn->prepare("
    SELECT * FROM student_answers 
    WHERE attempt_id = ?
");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$result = $stmt->get_result();

$student_answers = [];
while ($row = $result->fetch_assoc()) {
    $student_answers[$row['question_id']] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Review - <?php echo htmlspecialchars($attempt['title']); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .result-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .score-circle.pass { color: #28a745; }
        .score-circle.fail { color: #dc3545; }
        .question-review {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .correct-answer { background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0; }
        .wrong-answer { background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .student-answer { background: #e7f3ff; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0; }
    </style>
</head>
<body style="background: #f5f5f5;">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap"></i> Exam System - Student
            </a>
            <div class="ml-auto">
                <span class="navbar-text text-white mr-3">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars(getCurrentUserFullName()); ?>
                </span>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php displayFlashMessage(); ?>

        <!-- Result Summary -->
        <div class="result-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-clipboard-check"></i> <?php echo htmlspecialchars($attempt['title']); ?></h2>
                    <p class="mb-0">Exam Review</p>
                </div>
                <div class="col-md-4">
                    <div class="score-circle <?php echo $passed ? 'pass' : 'fail'; ?>">
                        <?php echo $attempt['percentage']; ?>%
                    </div>
                </div>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.3);">
            
            <div class="row text-center">
                <div class="col-md-3">
                    <h5><?php echo $attempt['score']; ?> / <?php echo $attempt['total_marks']; ?></h5>
                    <p class="mb-0">Score</p>
                </div>
                <div class="col-md-3">
                    <h5><?php echo $attempt['percentage']; ?>%</h5>
                    <p class="mb-0">Percentage</p>
                </div>
                <div class="col-md-3">
                    <h5><?php echo formatDuration($attempt['duration_used']); ?></h5>
                    <p class="mb-0">Time Used</p>
                </div>
                <div class="col-md-3">
                    <h5>
                        <?php if ($passed): ?>
                            <span class="badge badge-success" style="font-size: 1rem;">PASSED</span>
                        <?php else: ?>
                            <span class="badge badge-danger" style="font-size: 1rem;">FAILED</span>
                        <?php endif; ?>
                    </h5>
                    <p class="mb-0">Status</p>
                </div>
            </div>

            <?php if ($attempt['tab_switches'] > 0): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Tab switches detected:</strong> <?php echo $attempt['tab_switches']; ?> time(s)
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mb-4">
            <a href="my-results.php" class="btn btn-primary">
                <i class="fas fa-list"></i> View All Results
            </a>
            <a href="index.php" class="btn btn-success">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Results
            </button>
        </div>

        <!-- Questions Review -->
        <h4 class="mb-3"><i class="fas fa-list-alt"></i> Question-by-Question Review</h4>

        <?php
        $q_num = 1;
        while ($question = $questions->fetch_assoc()):
            $answer = $student_answers[$question['id']] ?? null;
            $is_correct = $answer['is_correct'] ?? 0;
        ?>
        <div class="question-review">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5>
                        Question <?php echo $q_num; ?>
                        <?php if ($is_correct): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Correct</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Incorrect</span>
                        <?php endif; ?>
                    </h5>
                    <span class="badge badge-info"><?php echo getQuestionTypeDisplay($question['question_type']); ?></span>
                    <span class="badge badge-secondary"><?php echo $question['marks']; ?> mark<?php echo $question['marks'] > 1 ? 's' : ''; ?></span>
                </div>
                <div>
                    <?php if ($is_correct): ?>
                        <h5 class="text-success mb-0">+<?php echo $question['marks']; ?></h5>
                    <?php else: ?>
                        <h5 class="text-danger mb-0">0</h5>
                    <?php endif; ?>
                </div>
            </div>

            <p class="lead"><strong><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></strong></p>

            <?php if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'multiple_select'): ?>
                <?php
                $options = $conn->query("SELECT * FROM question_options WHERE question_id = {$question['id']} ORDER BY option_order");
                $student_answer = $answer['answer_text'] ?? '';
                $student_options = $answer['selected_options'] ? json_decode($answer['selected_options'], true) : [];
                ?>
                
                <div class="ml-3">
                    <?php while ($option = $options->fetch_assoc()): ?>
                        <?php
                        $is_selected = false;
                        if ($question['question_type'] == 'multiple_choice') {
                            $is_selected = ($student_answer == $option['option_text']);
                        } else {
                            $is_selected = in_array($option['option_text'], $student_options);
                        }
                        ?>
                        <div class="mb-2">
                            <?php if ($option['is_correct']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php elseif ($is_selected): ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php else: ?>
                                <i class="far fa-circle text-muted"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($option['option_text']); ?>
                            
                            <?php if ($option['is_correct']): ?>
                                <span class="badge badge-success">Correct Answer</span>
                            <?php endif; ?>
                            <?php if ($is_selected && !$option['is_correct']): ?>
                                <span class="badge badge-danger">Your Answer</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php elseif ($question['question_type'] == 'true_false'): ?>
                <div class="student-answer">
                    <strong>Your Answer:</strong> <?php echo htmlspecialchars($answer['answer_text'] ?? 'Not answered'); ?>
                </div>
                <div class="correct-answer">
                    <strong>Correct Answer:</strong> <?php echo htmlspecialchars($question['correct_answer']); ?>
                </div>

            <?php elseif ($question['question_type'] == 'short_answer' || $question['question_type'] == 'fill_blank'): ?>
                <div class="student-answer">
                    <strong>Your Answer:</strong><br>
                    <?php echo $answer['answer_text'] ? nl2br(htmlspecialchars($answer['answer_text'])) : '<em>Not answered</em>'; ?>
                </div>
                <div class="correct-answer">
                    <strong>Expected Answer:</strong><br>
                    <?php echo nl2br(htmlspecialchars($question['correct_answer'])); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $q_num++;
        endwhile;
        ?>

        <!-- Bottom Navigation -->
        <div class="text-center mb-5">
            <a href="my-results.php" class="btn btn-primary btn-lg">
                <i class="fas fa-list"></i> View All Results
            </a>
            <a href="index.php" class="btn btn-success btn-lg">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>