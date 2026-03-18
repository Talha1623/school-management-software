    # Fix quiz_questions Table Issue

## Problem
The `quiz_questions` table has an orphaned tablespace file in MySQL, causing the error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'school.quiz_questions' doesn't exist
```

## Quick Fix (Try This First)

Run the Artisan command:
```bash
php artisan fix:quiz-questions-table
```

If that doesn't work, try the SQL script:
```bash
# In MySQL command line or phpMyAdmin, run:
mysql -u root -p school < fix_quiz_questions.sql
```

## Manual Solution

If the above doesn't work, you need to manually delete the orphaned `.ibd` file from MySQL's data directory.

### Steps:

1. **Find your MySQL data directory:**
   - Run this in MySQL: `SHOW VARIABLES LIKE 'datadir';`
   - Or check your MySQL configuration file (my.ini or my.cnf)

2. **Navigate to the table file:**
   - Go to: `[MySQL Data Directory]/school/`
   - Look for: `quiz_questions.ibd`

3. **Stop MySQL service** (important!)

4. **Delete the orphaned file:**
   - Delete: `quiz_questions.ibd`
   - Also check for: `quiz_questions.frm` (if exists, delete it too)

5. **Start MySQL service**

6. **Run the migration:**
   ```bash
   php artisan migrate
   ```

   Or use the fix command:
   ```bash
   php artisan fix:quiz-questions-table
   ```

### Alternative: Using MySQL Command Line

If you have MySQL command line access:

```sql
-- Connect to MySQL
mysql -u root -p

-- Use your database
USE school;

-- Drop the table (this should work after stopping MySQL and deleting .ibd file)
DROP TABLE IF EXISTS quiz_questions;

-- Then run the migration
-- Exit MySQL and run: php artisan migrate
```

### Quick Fix Script (if you have shell access to MySQL data directory)

```bash
# Stop MySQL
# Windows: net stop MySQL
# Linux: sudo systemctl stop mysql

# Delete the file (adjust path as needed)
# Windows example: del "C:\ProgramData\MySQL\MySQL Server 8.0\Data\school\quiz_questions.ibd"
# Linux example: rm /var/lib/mysql/school/quiz_questions.ibd

# Start MySQL
# Windows: net start MySQL  
# Linux: sudo systemctl start mysql

# Run migration
php artisan migrate
```
