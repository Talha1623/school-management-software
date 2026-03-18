-- ============================================
-- Live Server SQL Fix: Make staff_id Nullable in leaves Table
-- ============================================

-- Step 1: Check current foreign key constraint name
SELECT CONSTRAINT_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'leaves' 
AND COLUMN_NAME = 'staff_id' 
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Step 2: Drop the foreign key constraint
-- Note: Replace 'leaves_staff_id_foreign' with actual constraint name from Step 1
ALTER TABLE leaves DROP FOREIGN KEY leaves_staff_id_foreign;

-- Step 3: Make staff_id column nullable
ALTER TABLE leaves MODIFY COLUMN staff_id BIGINT UNSIGNED NULL;

-- Step 4: Re-add the foreign key constraint
ALTER TABLE leaves 
ADD CONSTRAINT leaves_staff_id_foreign 
FOREIGN KEY (staff_id) REFERENCES staff(id) 
ON DELETE CASCADE;

-- Step 5: Verify the change
DESCRIBE leaves;
-- Check: staff_id column should show "YES" in the "Null" column
