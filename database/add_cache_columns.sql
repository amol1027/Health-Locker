-- Add columns to store cached simplified reports
-- Run this SQL in phpMyAdmin or MySQL client

ALTER TABLE `medical_records` 
ADD COLUMN `simplified_data` LONGTEXT DEFAULT NULL COMMENT 'Cached simplified report JSON',
ADD COLUMN `simplified_language` VARCHAR(10) DEFAULT NULL COMMENT 'Language code (en, mr, hi)',
ADD COLUMN `simplified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When report was simplified';

-- Add index for faster cache lookups
ALTER TABLE `medical_records` 
ADD INDEX `idx_simplified` (`id`, `simplified_language`);
