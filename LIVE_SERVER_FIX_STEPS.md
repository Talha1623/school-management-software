# 🔧 Live Server Fix - Add start_time and end_time Columns

## ❌ Error
```
Column not found: 1054 Unknown column 'start_time' in 'SET'
```

## ✅ Solution

Live server par `start_time` aur `end_time` columns add karein.

---

## Method 1: phpMyAdmin (Easiest)

1. **phpMyAdmin open karein**
2. **Apna database select karein**
3. **SQL tab par jayein**
4. **Ye SQL paste karein aur run karein:**

```sql
ALTER TABLE `staff_attendances` 
ADD COLUMN `start_time` TIME NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `end_time` TIME NULL DEFAULT NULL AFTER `start_time`;
```

5. **Done!** ✅

---

## Method 2: MySQL Command Line (SSH)

```bash
# SSH connect karein
ssh user@your-server.com

# MySQL login karein
mysql -u your_username -p your_database_name

# SQL run karein
ALTER TABLE `staff_attendances` 
ADD COLUMN `start_time` TIME NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `end_time` TIME NULL DEFAULT NULL AFTER `start_time`;

# Exit
exit;
```

---

## Method 3: Direct SQL File

```bash
# SSH connect karein
ssh user@your-server.com

# SQL file upload karein aur run karein
mysql -u your_username -p your_database_name < LIVE_SERVER_FIX.sql
```

---

## Verification

Columns add hone ke baad verify karein:

```sql
-- Check table structure
DESCRIBE staff_attendances;

-- Or
SHOW COLUMNS FROM staff_attendances;

-- Should show:
-- start_time | time | YES | NULL
-- end_time   | time | YES | NULL
```

---

## Quick Fix SQL (Copy-Paste Ready)

```sql
ALTER TABLE `staff_attendances` 
ADD COLUMN `start_time` TIME NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `end_time` TIME NULL DEFAULT NULL AFTER `start_time`;
```

---

## ⚠️ Important Notes

1. **Backup pehle lein** (safety ke liye)
2. **Columns already exist ho to error aayega** - ignore karein, matlab already add ho chuki hain
3. **Production server par directly run karein** - ye safe operation hai

---

## After Running SQL

1. API test karein:
```bash
curl -X POST https://school.ritpk.com/api/teacher/self-attendance/mark \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "attendance_date": "2025-12-22",
    "status": "Present",
    "start_time": "09:00:00",
    "end_time": "17:00:00"
  }'
```

2. **Error nahi aana chahiye ab!** ✅

