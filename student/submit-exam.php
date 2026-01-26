<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Function for auto-submit (called from take-exam.php)
function autoSubmitExam($attempt_id, $conn) {
    $stmt = $conn->prepare("
        SELECT student_id, start_time, duration 
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        WHERE ea.id = ?
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return false;
    }
    
    $attempt = $result->fetch_assoc();
    $stmt->close();
    
    // Calculate duration used
    $start_time = strtotime($attempt['start_time']);
    $end_time = time();
    $duration_used = round(($end_time - $start_time) / 60);
    
    // Grade the exam
    $score = gradeExam($attempt_id, $conn);
    
    // Update attempt status
    $stmt = $conn->prepare("
        UPDATE exam_attempts 
        SET status = 'submitted', 
            submitted_at = NOW(),
            score = ?,
            duration_used = ?
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $score['score'], $duration_used, $attempt_id);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

// Main submission handler
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('index.php', 'error', 'Invalid request.');
}

if (!verifyCSRFToken($_POST['csrf_token'])) {
    redirectWithMessage('index.php', 'error', 'Invalid security token.');
}

$attempt_id = intval($_POST['attempt_id']);
$tab_switches = intval($_POST['tab_switches']);
$student_id = getCurrentUserId();

// Verify attempt belongs to student
$stmt = $conn->prepare("
    SELECT ea.*, e.duration
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.id = ? AND ea.student_id = ? AND ea.status = 'in_progress'
");
$stmt->bind_param("ii", $attempt_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirectWithMessage('index.php', 'error', 'Invalid exam attempt.');
}

$attempt = $result->fetch_assoc();
$stmt->close();

// Calculate duration used
$start_time = strtotime($attempt['start_time']);
$end_time = time();
$duration_used = round(($end_time - $start_time) / 60);

// Make sure they didn't exceed the time limit (with 1 minute grace period)
if ($duration_used > ($attempt['duration'] + 1)) {
    redirectWithMessage('my-results.php', 'warning', 'Exam time exceeded. Your exam has been submitted.');
}

// Grade the exam
$score = gradeExam($attempt_id, $conn);

// Calculate percentage
$percentage = 0;
if ($score['total_marks'] > 0) {
    $percentage = round(($score['score'] / $score['total_marks']) * 100, 2);
}

// Update attempt
$stmt = $conn->prepare("
    UPDATE exam_attempts 
    SET status = 'submitted',
        submitted_at = NOW(),
        score = ?,
        percentage = ?,
        duration_used = ?,
        tab_switches = ?
    WHERE id = ?
");
$stmt->bind_param("idiii", $score['score'], $percentage, $duration_used, $tab_switches, $attempt_id);
$stmt->execute();
$stmt->close();

redirectWithMessage('exam-review.php?attempt_id=' . $attempt_id, 'success', 'Exam submitted successfully!');

// Grading function
function gradeExam($attempt_id, $conn) {
    // Get exam_id and total marks
    $stmt = $conn->prepare("SELECT exam_id, total_marks FROM exam_attempts WHERE id = ?");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt = $result->fetch_assoc();
    $exam_id = $attempt['exam_id'];
    $total_marks = $attempt['total_marks'];
    $stmt->close();
    
    // Get all questions
    $questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id");
    
    $total_score = 0;
    
    while ($question = $questions->fetch_assoc()) {
        $question_id = $question['id'];
        $marks = $question['marks'];
        
        // Get student answer
        $stmt = $conn->prepare("
            SELECT answer_text, selected_options 
            FROM student_answers 
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->bind_param("ii", $attempt_id, $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // No answer provided
            $stmt->close();
            continue;
        }
        
        $answer = $result->fetch_assoc();
        $stmt->close();
        
        $is_correct = 0;
        
        // Grade based on question type
        if ($question['question_type'] == 'multiple_choice') {
            // Get correct option
            $correct_option = $conn->query("
                SELECT option_text FROM question_options 
                WHERE question_id = $question_id AND is_correct = 1
            ")->fetch_assoc();
            
            if ($correct_option && $answer['answer_text'] == $correct_option['option_text']) {
                $is_correct = 1;
                $total_score += $marks;
            }
            
        } elseif ($question['question_type'] == 'multiple_select') {
            // Get all correct options
            $correct_options = $conn->query("
                SELECT option_text FROM question_options 
                WHERE question_id = $question_id AND is_correct = 1
                ORDER BY option_text
            ");
            
            $correct_array = [];
            while ($opt = $correct_options->fetch_assoc()) {
                $correct_array[] = $opt['option_text'];
            }
            
            $student_array = json_decode($answer['selected_options'], true) ?? [];
            sort($correct_array);
            sort($student_array);
            
            if ($correct_array === $student_array) {
                $is_correct = 1;
                $total_score += $marks;
            }
            
        } elseif ($question['question_type'] == 'true_false') {
            if (strtolower(trim($answer['answer_text'])) == strtolower(trim($question['correct_answer']))) {
                $is_correct = 1;
                $total_score += $marks;
            }
            
        } elseif ($question['question_type'] == 'short_answer' || $question['question_type'] == 'fill_blank') {
            // Case-insensitive comparison, trim whitespace
            $student_answer = strtolower(trim($answer['answer_text']));
            $correct_answer = strtolower(trim($question['correct_answer']));
            
            if ($student_answer == $correct_answer) {
                $is_correct = 1;
                $total_score += $marks;
            }
        }
        
        // Update student answer with is_correct flag
        $stmt = $conn->prepare("
            UPDATE student_answers 
            SET is_correct = ? 
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->bind_param("iii", $is_correct, $attempt_id, $question_id);
        $stmt->execute();
        $stmt->close();
    }
    
    return [
        'score' => $total_score,
        'total_marks' => $total_marks
    ];
}
?>