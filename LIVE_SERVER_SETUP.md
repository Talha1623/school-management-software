# Live Server MySQL Setup - Staff Attendances Table

## Option 1: Using phpMyAdmin / MySQL Workbench

1. **Login to phpMyAdmin** or open MySQL Workbench
2. **Select your database**
3. **Go to SQL tab**
4. **Copy and paste** the SQL from `database/migrations/staff_attendances_live_mysql.sql`
5. **Click Execute** or press F5

## Option 2: Using Command Line (SSH)

```bash
# Connect to your server via SSH
ssh user@your-server.com

# Login to MySQL
mysql -u your_username -p your_database_name

# Then paste the SQL commands from staff_attendances_live_mysql.sql
# Or run directly:
mysql -u your_username -p your_database_name < staff_attendances_live_mysql.sql
```

## Option 3: Using Laravel Migration on Live Server

```bash
# SSH into your live server
ssh user@your-server.com

# Navigate to your project directory
cd /path/to/your/project

# Run migration
php artisan migrate --path=database/migrations/2025_12_22_100000_create_staff_attendances_table.php
```

## Verification

After running the migration, verify the table was created:

```sql
-- Check if table exists
SHOW TABLES LIKE 'staff_attendances';

-- Check table structure
DESCRIBE staff_attendances;

-- Check indexes
SHOW INDEXES FROM staff_attendances;
```

## Important Notes

1. **Backup First**: Always backup your database before running migrations on live server
2. **Foreign Key**: Make sure `staff` table exists and has `id` column
3. **Permissions**: Ensure MySQL user has CREATE TABLE and FOREIGN KEY permissions
4. **Charset**: Table uses `utf8mb4_unicode_ci` for proper Unicode support

## Rollback (If Needed)

If you need to remove the table:

```sql
DROP TABLE IF EXISTS `staff_attendances`;
```

