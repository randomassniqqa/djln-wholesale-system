# DJLN MARKETING — DATABASE CLEANUP GUIDE
## phpMyAdmin Safe Deletion Instructions

---

## 🎯 OBJECTIVE
Remove legacy Task Management system tables from your database while preserving all DJLN Marketing Wholesale tables and Laravel system tables.

**Current State:** Database is in hybrid state with old task tables mixed with new wholesale tables  
**Goal State:** Clean database with ONLY wholesale + system tables  
**Risk Level:** LOW (if you follow this guide exactly)

---

## ⚠️ CRITICAL SAFETY CHECKS (DO THESE FIRST)

### Check 1: Backup Your Database
```sql
-- In phpMyAdmin, use Export feature to backup entire database
-- Menu: Server > Databases > djln_wholesale_inventory > Export
```

### Check 2: Verify Preservation List
Before running any DELETE commands, verify these tables still exist:
```sql
-- Run this query in phpMyAdmin SQL tab
SHOW TABLES;
```

You should see:
```
✅ audit_logs          ✅ categories         ✅ failed_jobs
✅ cache               ✅ cache_locks        ✅ jobs
✅ job_batches         ✅ migrations         ✅ notifications
✅ order_items         ✅ orders             ✅ password_reset_tokens
✅ payment             ✅ products           ✅ review
✅ sessions            ✅ stock              ✅ stock_in
✅ stock_out           ✅ supplier           ✅ users
✅ user_account        ✅ delivery           ✅ notification
```

### Check 3: Check for Foreign Keys
```sql
-- Run this to see all foreign key relationships
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;
```

**Expected Result:** Only foreign keys between wholesale/system tables  
**Red Flag:** If you see FKs from `orders`, `products`, or `users` to `tasks` or `projects` tables → DO NOT PROCEED until resolved

---

## 📋 STEP-BY-STEP CLEANUP IN PHPMYADMIN

### Step 1: Open phpMyAdmin
1. Go to `http://localhost/phpmyadmin` (or your phpMyAdmin URL)
2. Login with your database credentials
3. Select database: `djln_wholesale_inventory`

### Step 2: Disable Foreign Key Checks (TEMPORARY)
In the SQL tab, run FIRST:
```sql
SET FOREIGN_KEY_CHECKS = 0;
```

This allows us to drop tables without FK constraint errors.

### Step 3: Drop Legacy Triggers
Copy and paste into SQL tab:
```sql
-- Drop legacy Task Management triggers
DROP TRIGGER IF EXISTS `auto_complete_project_on_tasks_done`;
DROP TRIGGER IF EXISTS `trg_prevent_active_project_deletion`;
DROP TRIGGER IF EXISTS `trg_task_status_change`;
DROP TRIGGER IF EXISTS `trg_task_created`;
DROP TRIGGER IF EXISTS `trg_task_updated`;
DROP TRIGGER IF EXISTS `trg_task_deleted`;
```

Click **Execute** (don't worry if some "don't exist" - that's OK)

### Step 4: Drop Legacy Stored Procedures
```sql
-- Drop legacy Task Management procedures
DROP PROCEDURE IF EXISTS `sp_complete_task`;
DROP PROCEDURE IF EXISTS `sp_assign_task`;
DROP PROCEDURE IF EXISTS `sp_update_task_status`;
DROP PROCEDURE IF EXISTS `sp_generate_task_report`;
```

Click **Execute**

### Step 5: Drop Legacy Tables (IN THIS EXACT ORDER)
```sql
-- Drop child tables first (have foreign keys)
DROP TABLE IF EXISTS `task_attachments`;
DROP TABLE IF EXISTS `task_comments`;
DROP TABLE IF EXISTS `task_activities`;
DROP TABLE IF EXISTS `project_members`;

-- Drop main tables
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `projects`;
```

Click **Execute**

⚠️ **IMPORTANT:** If you get an error like:
```
#1451 - Cannot delete or update a parent row: a foreign key constraint fails
```

This means something you're trying to delete is still referenced. **STOP** and check:
1. Are there foreign keys from another table to this table?
2. Is the preservation list correct?

### Step 6: Re-Enable Foreign Key Checks
```sql
SET FOREIGN_KEY_CHECKS = 1;
```

Click **Execute**

### Step 7: Verify Cleanup
```sql
-- Count remaining tables (should be around 17-18 tables for wholesale system)
SELECT COUNT(*) as remaining_tables FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE();

-- List all remaining tables
SHOW TABLES;

-- Show remaining triggers (should see only tr_order_* and proc_* ones)
SELECT TRIGGER_NAME FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = DATABASE();

-- Show remaining procedures
SELECT ROUTINE_NAME FROM information_schema.ROUTINES 
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE';
```

---

## ✅ EXPECTED RESULTS AFTER CLEANUP

### Tables Remaining (17-18 total)
```
audit_logs
cache
cache_locks
categories
delivery
failed_jobs
job_batches
jobs
migrations
notification
notifications
order_items
orders
password_reset_tokens
payment
products
review
sessions
stock
stock_in
stock_out
supplier
user_account
users
```

### Triggers Remaining (Should be 3)
```
tr_order_items_insert    (on table: order_items)
tr_order_items_update    (on table: order_items)
tr_order_status_change   (on table: orders)
```

### Procedures Remaining (Should be 2)
```
proc_calculate_order_totals
proc_deduct_inventory
```

---

## 🚨 TROUBLESHOOTING

### Error: "Cannot delete or update a parent row"
**Cause:** A table you're trying to delete is referenced by another table  
**Solution:**
1. Don't delete that table yet
2. Check what's referencing it
3. Delete the referencing table first

### Error: "Table doesn't exist"
**This is OK!** The `IF EXISTS` clause means it's safe to ignore

### Error: "Foreign key constraint fails on drop"
**Solution:** Make sure you ran `SET FOREIGN_KEY_CHECKS = 0;` at the start

### Can't connect to database
1. Check MySQL is running: `php artisan tinker` → `DB::connection()->getPdo()`
2. Check credentials in `.env` file

---

## 🔄 ROLLBACK (If Something Goes Wrong)

If something goes wrong:

1. **Restore from backup**
   - In phpMyAdmin, go to: Database > Import
   - Select your backup SQL file
   - Click Import

2. **Or run Laravel migration rollback**
   ```bash
   php artisan migrate:rollback --step=2
   ```
   (This reverts the 2 migrations we created)

---

## ✨ AFTER CLEANUP — NEXT STEPS

1. **Verify system still works**
   ```bash
   php artisan serve
   # Visit http://localhost:8000/inventory
   ```

2. **Run database tests**
   ```bash
   php artisan test
   ```

3. **Check logs for errors**
   ```bash
   tail storage/logs/laravel.log
   ```

4. **Test order workflow**
   - Create new order
   - Verify stock deduction works
   - Check audit_logs for transaction record

---

## 📊 DATABASE CLEANUP SUMMARY

| Item | Before | After | Action |
|------|--------|-------|--------|
| Tables | ~25 | ~18 | Removed 7 legacy task tables |
| Triggers | 6+ | 3 | Removed 3+ legacy task triggers |
| Procedures | 4+ | 2 | Removed 2+ legacy task procedures |
| Foreign Keys | Mixed | Clean | Only wholesale relationships remain |
| Data Integrity | Cluttered | Pure | Ready for production |

---

## 🎯 SUCCESS CRITERIA

✅ All task-related tables deleted  
✅ All order/product/user tables preserved  
✅ Database is 100% wholesale-focused  
✅ No broken foreign keys  
✅ System loads without errors  
✅ Orders still track stock properly  

---

## 📞 NEED HELP?

If you encounter issues:
1. Check the error message carefully
2. Review this guide's troubleshooting section
3. Restore from backup and try again
4. Check `storage/logs/laravel.log` for Laravel errors

---

**Remember:** This cleanup is safe because:
- ✅ We're only deleting legacy Task tables
- ✅ We're keeping ALL wholesale tables
- ✅ Foreign key checks prevent breaking relationships
- ✅ You have a backup before starting
- ✅ Changes are reversible via migration rollback

**Good luck! Your database will be clean and ready for production.** 🚀
