# Live Server - Parent API Token Migration

## Migration File
`2025_12_13_124527_add_api_token_to_parent_accounts_table.php`

Ye migration `parent_accounts` table me `api_token` column add karega.

---

## Method 1: Laravel Artisan Command (Recommended) ✅

### Steps:

1. **SSH se server connect karein:**
```bash
ssh user@your-server.com
```

2. **Project directory me jayein:**
```bash
cd /path/to/your/project/school
```

3. **Migration run karein:**
```bash
php artisan migrate --path=database/migrations/2025_12_13_124527_add_api_token_to_parent_accounts_table.php
```

Ya sabhi pending migrations run karein:
```bash
php artisan migrate
```

4. **Verify karein:**
```bash
php artisan migrate:status
```

---

## Method 2: Direct SQL Query (phpMyAdmin) 🗄️

Agar SSH access nahi hai, to phpMyAdmin se directly SQL run karein:

### Steps:

1. **phpMyAdmin open karein**
2. **Apna database select karein**
3. **SQL tab par jayein**
4. **Ye SQL paste karein aur Execute karein:**

```sql
ALTER TABLE `parent_accounts` 
ADD COLUMN `api_token` TEXT NULL DEFAULT NULL AFTER `password`;
```

5. **Done!** ✅

---

## Method 3: MySQL Command Line (SSH) 💻

```bash
# SSH connect karein
ssh user@your-server.com

# MySQL login karein
mysql -u your_username -p your_database_name

# SQL run karein
ALTER TABLE `parent_accounts` 
ADD COLUMN `api_token` TEXT NULL DEFAULT NULL AFTER `password`;

# Exit
exit;
```

---

## Verification ✅

Migration run hone ke baad verify karein:

### Using SQL:
```sql
-- Check if column exists
SHOW COLUMNS FROM `parent_accounts` LIKE 'api_token';

-- Or check full table structure
DESCRIBE `parent_accounts`;

-- Should show:
-- api_token | text | YES | NULL
```

### Using Laravel:
```bash
php artisan tinker
```
```php
Schema::hasColumn('parent_accounts', 'api_token');
// Should return: true
```

---

## Quick SQL (Copy-Paste Ready) 📋

```sql
ALTER TABLE `parent_accounts` 
ADD COLUMN `api_token` TEXT NULL DEFAULT NULL AFTER `password`;
```

---

## ⚠️ Important Notes

1. **Backup pehle lein** - Safety ke liye database backup zaroor lein
2. **Column already exist ho to error aayega** - Ignore karein, matlab already add ho chuki hai
3. **Production server par safe hai** - Ye operation safe hai, existing data affect nahi hoga
4. **Migration table update** - Agar Method 2 ya 3 use karein, to manually `migrations` table me entry add karein:

```sql
INSERT INTO `migrations` (`migration`, `batch`) 
VALUES ('2025_12_13_124527_add_api_token_to_parent_accounts_table', 
        (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS temp));
```

---

## After Migration ✅

1. **Parent API test karein:**
```bash
POST /api/parent/login
{
    "email": "parent@example.com",
    "password": "password123"
}
```

2. **Check karein ke token generate ho raha hai**

3. **Profile API test karein:**
```bash
GET /api/parent/profile
Headers: Authorization: Bearer {token}
```

---

## Rollback (If Needed) ⏪

Agar column remove karni ho:

```sql
ALTER TABLE `parent_accounts` 
DROP COLUMN `api_token`;
```

Ya Laravel se:
```bash
php artisan migrate:rollback --step=1
```

---

## Troubleshooting 🔧

### Error: Column already exists
- **Solution:** Ignore karein, matlab column already add ho chuki hai

### Error: Table doesn't exist
- **Solution:** Pehle `parent_accounts` table create karein

### Error: Permission denied
- **Solution:** MySQL user ko ALTER TABLE permission dein

### Migration not showing in migrations table
- **Solution:** Method 2 ya 3 use kiye ho to manually entry add karein (see above)

---

## Success Checklist ✅

- [ ] Migration file uploaded to server
- [ ] Database backup taken
- [ ] Migration run successfully
- [ ] Column verified in database
- [ ] Parent login API tested
- [ ] Token generation working
- [ ] Profile API tested

---

**Migration Date:** December 13, 2025  
**Migration Name:** add_api_token_to_parent_accounts_table

