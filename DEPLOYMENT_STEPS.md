# Live Server Migration Steps

## Step 1: Migration File Upload
1. Migration file ko live server par upload karein:
   - File: `database/migrations/2026_03_08_191202_make_staff_id_nullable_in_leaves_table.php`
   - Upload to: `your-project-path/database/migrations/`

## Step 2: SSH se Connect
```bash
ssh user@your-server-ip
cd /path/to/your/project
```

## Step 3: Migration Run
```bash
php artisan migrate --path=database/migrations/2026_03_08_191202_make_staff_id_nullable_in_leaves_table.php
```

## Alternative: Manual SQL Commands (Agar migration issue ho)

### Step 1: Foreign Key Constraint Name Check
```sql
SELECT CONSTRAINT_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'your_database_name' 
AND TABLE_NAME = 'leaves' 
AND COLUMN_NAME = 'staff_id' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### Step 2: Foreign Key Drop
```sql
-- Replace 'constraint_name' with actual constraint name from Step 1
ALTER TABLE leaves DROP FOREIGN KEY constraint_name;
```

### Step 3: Make staff_id Nullable
```sql
ALTER TABLE leaves MODIFY COLUMN staff_id BIGINT UNSIGNED NULL;
```

### Step 4: Re-add Foreign Key
```sql
ALTER TABLE leaves 
ADD CONSTRAINT leaves_staff_id_foreign 
FOREIGN KEY (staff_id) REFERENCES staff(id) 
ON DELETE CASCADE;
```

## Verification
```sql
DESCRIBE leaves;
-- Check if staff_id shows NULL in "Null" column
```
