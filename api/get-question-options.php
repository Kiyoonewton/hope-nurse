<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireAdmin();

header('Content-Type: application/json');

$question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;

if ($question_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY option_order");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
    
    echo json_encode($options);
    $stmt->close();
} else {
    echo json_encode([]);
}
?>