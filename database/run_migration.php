<?php
/**
 * Migration Script to Fix student_answers Table
 * Run this file in your browser or via CLI: php run_migration.php
 */

require_once '../config/database.php';

echo "<pre>";
echo "=================================================\n";
echo "Migration: Fix student_answers table schema\n";
echo "=================================================\n\n";

try {
    // Check current table structure
    echo "1. Checking current table structure...\n";
    $result = $conn->query("DESCRIBE student_answers");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "   - {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";

    // Check if we need to rename answer_value to answer_text
    if (in_array('answer_value', $columns) && !in_array('answer_text', $columns)) {
        echo "2. Renaming 'answer_value' to 'answer_text'...\n";
        $sql = "ALTER TABLE student_answers
                CHANGE COLUMN answer_value answer_text TEXT
                COMMENT 'For text-based answers (short_answer, fill_blank, true_false)'";

        if ($conn->query($sql)) {
            echo "   ✓ Successfully renamed column\n\n";
        } else {
            throw new Exception("Failed to rename column: " . $conn->error);
        }
    } else if (in_array('answer_text', $columns)) {
        echo "2. Column 'answer_text' already exists, skipping rename\n\n";
    }

    // Check if we need to add selected_options
    if (!in_array('selected_options', $columns)) {
        echo "3. Adding 'selected_options' column...\n";
        $sql = "ALTER TABLE student_answers
                ADD COLUMN selected_options TEXT
                COMMENT 'For multiple choice/select - stores JSON array of selected option IDs'
                AFTER answer_text";

        if ($conn->query($sql)) {
            echo "   ✓ Successfully added column\n\n";
        } else {
            throw new Exception("Failed to add column: " . $conn->error);
        }
    } else {
        echo "3. Column 'selected_options' already exists, skipping\n\n";
    }

    // Verify final structure
    echo "4. Verifying final table structure...\n";
    $result = $conn->query("DESCRIBE student_answers");
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['Field']} ({$row['Type']})";
        if ($row['Comment']) {
            echo " - {$row['Comment']}";
        }
        echo "\n";
    }
    echo "\n";

    echo "=================================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "=================================================\n";

} catch (Exception $e) {
    echo "=================================================\n";
    echo "✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "=================================================\n";
    exit(1);
}

$conn->close();
?>
