<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = getCurrentUserId();

// Handle POST request to start new exam
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('index.php', 'error', 'Invalid security token.');
    }
    
    $exam_id = intval($_POST['exam_id']);
    
    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        redirectWithMessage('index.php', 'error', 'Exam not found.');
    }
    
    $exam = $result->fetch_assoc();
    
    // Create new exam attempt
    $stmt = $conn->prepare("
        INSERT INTO exam_attempts (student_id, exam_id, status, start_time, total_marks)
        VALUES (?, ?, 'in_progress', NOW(), ?)
    ");
    $stmt->bind_param("iii", $student_id, $exam_id, $exam['total_marks']);
    $stmt->execute();
    $attempt_id = $stmt->insert_id;
    $stmt->close();
    
    // Redirect to take exam page
    header("Location: take-exam.php?attempt_id=" . $attempt_id);
    exit();
}

// Handle GET request with attempt_id
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

if ($attempt_id == 0) {
    redirectWithMessage('index.php', 'error', 'Invalid exam attempt.');
}

// Get attempt details
$stmt = $conn->prepare("
    SELECT ea.*, e.title, e.description, e.duration, e.total_marks
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.id = ? AND ea.student_id = ? AND ea.status = 'in_progress'
");
$stmt->bind_param("ii", $attempt_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirectWithMessage('index.php', 'error', 'Exam attempt not found or already completed.');
}

$attempt = $result->fetch_assoc();
$exam_id = $attempt['exam_id'];
$stmt->close();

// Calculate time remaining
$start_time = strtotime($attempt['start_time']);
$duration_seconds = $attempt['duration'] * 60;
$current_time = time();
$elapsed_seconds = $current_time - $start_time;
$remaining_seconds = max(0, $duration_seconds - $elapsed_seconds);

// If time expired, auto-submit
if ($remaining_seconds == 0) {
    // Auto-submit the exam
    require_once 'submit-exam.php';
    autoSubmitExam($attempt_id, $conn);
    redirectWithMessage('my-results.php', 'warning', 'Exam time expired. Your exam has been automatically submitted.');
}

// Get all questions for this exam
$questions = $conn->query("
    SELECT * FROM questions 
    WHERE exam_id = $exam_id 
    ORDER BY order_number, id
");

// Get student's answers for this attempt
$stmt = $conn->prepare("SELECT question_id, answer_text, selected_options FROM student_answers WHERE attempt_id = ?");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$result = $stmt->get_result();

$student_answers = [];
while ($row = $result->fetch_assoc()) {
    $student_answers[$row['question_id']] = $row;
}
$stmt->close();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($attempt['title']); ?> - Taking Exam</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .exam-header {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .timer-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .timer-box.warning { background: #f8d7da; border-color: #dc3545; color: #dc3545; }
        .question-nav {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 80px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .question-card {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .q-nav-btn {
            width: 40px;
            height: 40px;
            margin: 3px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
        }
        .q-nav-btn.answered { background: #28a745; color: white; border-color: #28a745; }
        .q-nav-btn.current { background: #007bff; color: white; border-color: #007bff; }
        .option-label {
            cursor: pointer;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .option-label:hover { background: #f8f9fa; border-color: #007bff; }
        .option-label.selected { background: #e7f3ff; border-color: #007bff; }
    </style>
</head>
<body>
    <!-- Exam Header -->
    <div class="exam-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($attempt['title']); ?></h5>
                </div>
                <div class="col-md-4 text-center">
                    <div class="timer-box" id="timer">
                        <i class="fas fa-clock"></i> <span id="timeDisplay">Loading...</span>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-info">Total: <?php echo $attempt['total_marks']; ?> marks</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Question Navigation Sidebar -->
            <div class="col-md-2">
                <div class="question-nav">
                    <h6 class="mb-3"><i class="fas fa-list"></i> Questions</h6>
                    <div id="questionNavigation">
                        <?php
                        $q_num = 1;
                        $questions->data_seek(0); // Reset pointer
                        while ($q = $questions->fetch_assoc()) {
                            $is_answered = isset($student_answers[$q['id']]);
                            $class = $is_answered ? 'answered' : '';
                            echo '<button class="btn q-nav-btn ' . $class . '" data-question="' . $q_num . '" onclick="goToQuestion(' . $q_num . ')">' . $q_num . '</button>';
                            $q_num++;
                        }
                        ?>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="d-block mb-2">
                            <span class="badge badge-success">Answered</span><br>
                            <span class="badge badge-light border">Not Answered</span>
                        </small>
                        <button type="button" class="btn btn-primary btn-block btn-sm" onclick="submitExam()">
                            <i class="fas fa-check"></i> Submit Exam
                        </button>
                    </div>
                </div>
            </div>

            <!-- Questions Area -->
            <div class="col-md-10">
                <?php
                $q_num = 1;
                $questions->data_seek(0); // Reset pointer
                while ($question = $questions->fetch_assoc()):
                    $answer = $student_answers[$question['id']] ?? null;
                ?>
                <div class="question-card question-item" id="question-<?php echo $q_num; ?>" style="display: <?php echo $q_num == 1 ? 'block' : 'none'; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5>Question <?php echo $q_num; ?> of <?php echo $questions->num_rows; ?></h5>
                            <span class="badge badge-info"><?php echo getQuestionTypeDisplay($question['question_type']); ?></span>
                            <span class="badge badge-success"><?php echo $question['marks']; ?> mark<?php echo $question['marks'] > 1 ? 's' : ''; ?></span>
                        </div>
                    </div>

                    <div class="question-text mb-4">
                        <p class="lead"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                    </div>

                    <div class="answer-section">
                        <?php if ($question['question_type'] == 'multiple_choice'): ?>
                            <?php
                            $options = $conn->query("SELECT * FROM question_options WHERE question_id = {$question['id']} ORDER BY option_order");
                            $selected_option = $answer['answer_text'] ?? '';
                            ?>
                            <?php while ($option = $options->fetch_assoc()): ?>
                                <div class="option-label <?php echo $selected_option == $option['option_text'] ? 'selected' : ''; ?>" 
                                     onclick="selectOption(<?php echo $q_num; ?>, <?php echo $question['id']; ?>, '<?php echo htmlspecialchars($option['option_text'], ENT_QUOTES); ?>', 'radio')">
                                    <input type="radio" 
                                           name="q_<?php echo $question['id']; ?>" 
                                           value="<?php echo htmlspecialchars($option['option_text']); ?>"
                                           <?php echo $selected_option == $option['option_text'] ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </div>
                            <?php endwhile; ?>

                        <?php elseif ($question['question_type'] == 'multiple_select'): ?>
                            <?php
                            $options = $conn->query("SELECT * FROM question_options WHERE question_id = {$question['id']} ORDER BY option_order");
                            $selected_options = $answer['selected_options'] ? json_decode($answer['selected_options'], true) : [];
                            ?>
                            <?php while ($option = $options->fetch_assoc()): ?>
                                <div class="option-label <?php echo in_array($option['option_text'], $selected_options) ? 'selected' : ''; ?>" 
                                     onclick="toggleCheckbox(<?php echo $q_num; ?>, <?php echo $question['id']; ?>, '<?php echo htmlspecialchars($option['option_text'], ENT_QUOTES); ?>')">
                                    <input type="checkbox" 
                                           class="q_<?php echo $question['id']; ?>_checkbox"
                                           value="<?php echo htmlspecialchars($option['option_text']); ?>"
                                           <?php echo in_array($option['option_text'], $selected_options) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </div>
                            <?php endwhile; ?>

                        <?php elseif ($question['question_type'] == 'true_false'): ?>
                            <?php $selected_answer = $answer['answer_text'] ?? ''; ?>
                            <div class="option-label <?php echo $selected_answer == 'True' ? 'selected' : ''; ?>" 
                                 onclick="selectOption(<?php echo $q_num; ?>, <?php echo $question['id']; ?>, 'True', 'radio')">
                                <input type="radio" name="q_<?php echo $question['id']; ?>" value="True" <?php echo $selected_answer == 'True' ? 'checked' : ''; ?>>
                                True
                            </div>
                            <div class="option-label <?php echo $selected_answer == 'False' ? 'selected' : ''; ?>" 
                                 onclick="selectOption(<?php echo $q_num; ?>, <?php echo $question['id']; ?>, 'False', 'radio')">
                                <input type="radio" name="q_<?php echo $question['id']; ?>" value="False" <?php echo $selected_answer == 'False' ? 'checked' : ''; ?>>
                                False
                            </div>

                        <?php elseif ($question['question_type'] == 'short_answer' || $question['question_type'] == 'fill_blank'): ?>
                            <textarea class="form-control" 
                                      id="q_<?php echo $question['id']; ?>_text"
                                      rows="4" 
                                      placeholder="Type your answer here..."
                                      onblur="saveTextAnswer(<?php echo $q_num; ?>, <?php echo $question['id']; ?>)"><?php echo htmlspecialchars($answer['answer_text'] ?? ''); ?></textarea>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <?php if ($q_num > 1): ?>
                            <button class="btn btn-secondary" onclick="previousQuestion()">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ($q_num < $questions->num_rows): ?>
                            <button class="btn btn-primary" onclick="nextQuestion()">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success" onclick="submitExam()">
                                <i class="fas fa-check"></i> Submit Exam
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $q_num++;
                endwhile;
                ?>
            </div>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="submitForm" method="POST" action="submit-exam.php" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
        <input type="hidden" name="tab_switches" id="tabSwitches" value="0">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Exam state
        let currentQuestion = 1;
        let totalQuestions = <?php echo $questions->num_rows; ?>;
        let attemptId = <?php echo $attempt_id; ?>;
        let remainingSeconds = <?php echo $remaining_seconds; ?>;
        let tabSwitchCount = 0;
        let csrfToken = '<?php echo $csrf_token; ?>';

        // Timer
        function updateTimer() {
            if (remainingSeconds <= 0) {
                autoSubmit();
                return;
            }

            let hours = Math.floor(remainingSeconds / 3600);
            let minutes = Math.floor((remainingSeconds % 3600) / 60);
            let seconds = remainingSeconds % 60;

            let display = '';
            if (hours > 0) display += hours + 'h ';
            display += minutes + 'm ' + seconds + 's';

            $('#timeDisplay').text(display);

            // Warning when 5 minutes left
            if (remainingSeconds <= 300) {
                $('.timer-box').addClass('warning');
            }

            remainingSeconds--;
        }

        // Start timer
        setInterval(updateTimer, 1000);
        updateTimer();

        // Tab switch detection
        let isVisible = true;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isVisible) {
                isVisible = false;
                tabSwitchCount++;
                $('#tabSwitches').val(tabSwitchCount);
                
                if (tabSwitchCount <= 3) {
                    alert('Warning: Tab switching detected! (' + tabSwitchCount + '/3)\nExcessive tab switches may be flagged.');
                }
            } else if (!document.hidden) {
                isVisible = true;
            }
        });

        // Prevent navigation away
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your exam progress is saved but timer continues.';
            return e.returnValue;
        });

        // Question navigation
        function goToQuestion(qNum) {
            $('.question-item').hide();
            $('#question-' + qNum).show();
            currentQuestion = qNum;
            updateNavigation();
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                goToQuestion(currentQuestion + 1);
            }
        }

        function previousQuestion() {
            if (currentQuestion > 1) {
                goToQuestion(currentQuestion - 1);
            }
        }

        function updateNavigation() {
            $('.q-nav-btn').removeClass('current');
            $('.q-nav-btn[data-question="' + currentQuestion + '"]').addClass('current');
        }

        // Save answer functions
        function selectOption(qNum, questionId, value, type) {
            $.post('../api/save-answer.php', {
                attempt_id: attemptId,
                question_id: questionId,
                answer_text: value,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    $('.q-nav-btn[data-question="' + qNum + '"]').addClass('answered');
                    if (type === 'radio') {
                        $('input[name="q_' + questionId + '"]').closest('.option-label').removeClass('selected');
                        $('input[name="q_' + questionId + '"][value="' + value + '"]').closest('.option-label').addClass('selected');
                    }
                }
            }, 'json');
        }

        function toggleCheckbox(qNum, questionId, value) {
            let checkbox = $('.q_' + questionId + '_checkbox[value="' + value + '"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.closest('.option-label').toggleClass('selected');
            
            // Get all checked values
            let selected = [];
            $('.q_' + questionId + '_checkbox:checked').each(function() {
                selected.push($(this).val());
            });

            $.post('../api/save-answer.php', {
                attempt_id: attemptId,
                question_id: questionId,
                selected_options: JSON.stringify(selected),
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    if (selected.length > 0) {
                        $('.q-nav-btn[data-question="' + qNum + '"]').addClass('answered');
                    } else {
                        $('.q-nav-btn[data-question="' + qNum + '"]').removeClass('answered');
                    }
                }
            }, 'json');
        }

        function saveTextAnswer(qNum, questionId) {
            let answer = $('#q_' + questionId + '_text').val().trim();
            
            $.post('../api/save-answer.php', {
                attempt_id: attemptId,
                question_id: questionId,
                answer_text: answer,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    if (answer.length > 0) {
                        $('.q-nav-btn[data-question="' + qNum + '"]').addClass('answered');
                    } else {
                        $('.q-nav-btn[data-question="' + qNum + '"]').removeClass('answered');
                    }
                }
            }, 'json');
        }

        // Submit exam
        function submitExam() {
            if (confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.')) {
                $(window).off('beforeunload');
                $('#submitForm').submit();
            }
        }

        function autoSubmit() {
            alert('Time is up! Your exam will be submitted automatically.');
            $(window).off('beforeunload');
            $('#submitForm').submit();
        }

        // Initialize
        $(document).ready(function() {
            updateNavigation();
        });
    </script>
</body>
</html>