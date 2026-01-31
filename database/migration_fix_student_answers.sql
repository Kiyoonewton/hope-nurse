-- Migration to fix student_answers table schema
-- This adds the missing columns that the application code expects

USE exam_system;

-- Check if columns exist and add them if they don't
-- First, rename answer_value to answer_text if it exists
SET @dbname = DATABASE();
SET @tablename = 'student_answers';
SET @columnname = 'answer_value';
SET @new_columnname = 'answer_text';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  CONCAT('ALTER TABLE ', @tablename, ' CHANGE COLUMN ', @columnname, ' ', @new_columnname, ' TEXT COMMENT "For text-based answers (short_answer, fill_blank, true_false)"'),
  'SELECT 1'
));

PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Add selected_options column if it doesn't exist
SET @columnname = 'selected_options';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) = 0,
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT COMMENT "For multiple choice/select - stores JSON array of selected option IDs" AFTER answer_text'),
  'SELECT 1'
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verify the changes
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'student_answers'
ORDER BY ORDINAL_POSITION;
