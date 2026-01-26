<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$answer_text = isset($_POST['answer_text']) ? sanitizeInput($_POST['answer_text']) : '';
$selected_options = isset($_POST['selected_options']) ? $_POST['selected_options'] : null;

$student_id = getCurrentUserId();

// Verify this attempt belongs to the student and is in progress
$stmt = $conn->prepare("
    SELECT id FROM exam_attempts 
    WHERE id = ? AND student_id = ? AND status = 'in_progress'
");
$stmt->bind_param("ii", $attempt_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam attempt']);
    exit();
}
$stmt->close();

// Check if answer already exists
$stmt = $conn->prepare("
    SELECT id FROM student_answers 
    WHERE attempt_id = ? AND question_id = ?
");
$stmt->bind_param("ii", $attempt_id, $question_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing answer
    if ($selected_options !== null) {
        $stmt = $conn->prepare("
            UPDATE student_answers 
            SET selected_options = ?, answer_text = NULL, answered_at = NOW()
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->bind_param("sii", $selected_options, $attempt_id, $question_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE student_answers 
            SET answer_text = ?, selected_options = NULL, answered_at = NOW()
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->bind_param("sii", $answer_text, $attempt_id, $question_id);
    }
} else {
    // Insert new answer
    if ($selected_options !== null) {
        $stmt = $conn->prepare("
            INSERT INTO student_answers (attempt_id, question_id, selected_options, answered_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $attempt_id, $question_id, $selected_options);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO student_answers (attempt_id, question_id, answer_text, answered_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $attempt_id, $question_id, $answer_text);
    }
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Answer saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save answer']);
}

$stmt->close();
?>