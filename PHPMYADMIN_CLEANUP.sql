-- ════════════════════════════════════════════════════════════════════════════════════════
-- DJLN MARKETING WHOLESALE SYSTEM — DATABASE CLEANUP SQL
-- Safe to run in phpMyAdmin SQL tab
-- ════════════════════════════════════════════════════════════════════════════════════════
-- 
-- CRITICAL: This script ONLY removes legacy Task Management system tables
-- All DJLN Marketing wholesale tables are preserved
--
-- ════════════════════════════════════════════════════════════════════════════════════════
-- PART 1: DROP LEGACY TRIGGERS (in correct order)
-- ════════════════════════════════════════════════════════════════════════════════════════

-- These triggers were part of the old Task Management system
-- They are NOT needed for wholesale inventory

DROP TRIGGER IF EXISTS `auto_complete_project_on_tasks_done`;
DROP TRIGGER IF EXISTS `trg_prevent_active_project_deletion`;
DROP TRIGGER IF EXISTS `trg_task_status_change`;
DROP TRIGGER IF EXISTS `trg_task_created`;
DROP TRIGGER IF EXISTS `trg_task_updated`;
DROP TRIGGER IF EXISTS `trg_task_deleted`;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- PART 2: DROP LEGACY STORED PROCEDURES  
-- ════════════════════════════════════════════════════════════════════════════════════════

DROP PROCEDURE IF EXISTS `sp_complete_task`;
DROP PROCEDURE IF EXISTS `sp_assign_task`;
DROP PROCEDURE IF EXISTS `sp_update_task_status`;
DROP PROCEDURE IF EXISTS `sp_generate_task_report`;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- PART 3: DROP LEGACY TABLES (in correct dependency order)
-- ════════════════════════════════════════════════════════════════════════════════════════
-- 
-- IMPORTANT: Drop child tables BEFORE parent tables (to respect foreign key constraints)
-- Order: attachments/comments/activities → main tables
--
-- ════════════════════════════════════════════════════════════════════════════════════════

-- Drop child tables first (they have foreign keys to parent tables)
DROP TABLE IF EXISTS `task_attachments`;     -- FK to tasks
DROP TABLE IF EXISTS `task_comments`;        -- FK to tasks  
DROP TABLE IF EXISTS `task_activities`;      -- FK to tasks
DROP TABLE IF EXISTS `project_members`;      -- FK to projects

-- Drop main tables
DROP TABLE IF EXISTS `tasks`;                -- Main task table
DROP TABLE IF EXISTS `projects`;             -- Main project table

-- ════════════════════════════════════════════════════════════════════════════════════════
-- PART 4: VERIFICATION (run these to confirm cleanup)
-- ════════════════════════════════════════════════════════════════════════════════════════

-- Check remaining tables (should see only wholesale + system tables)
-- SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME;

-- Check remaining triggers (should see only tr_order_* triggers)
-- SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE();

-- Check remaining procedures (should see only proc_* procedures)
-- SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE';

-- ════════════════════════════════════════════════════════════════════════════════════════
-- PART 5: TABLES THAT ARE PRESERVED (DO NOT DELETE)
-- ════════════════════════════════════════════════════════════════════════════════════════
--
-- ✅ WHOLESALE DATA TABLES (KEEP):
--    - categories          (product categories)
--    - products            (SKU inventory)
--    - orders              (wholesale & retail orders)
--    - order_items         (line items per order)
--    - stock               (stock tracking)
--    - stock_in            (inbound shipments)
--    - stock_out           (outbound shipments)
--    - payment             (payment records)
--    - delivery            (delivery tracking)
--    - supplier            (supplier information)
--    - review              (product reviews)
--    - notification        (user notifications)
--    - audit_logs          (transaction audit trail)
--
-- ✅ SYSTEM & USER TABLES (KEEP):
--    - users               (user accounts)
--    - user_account        (user profile data)
--    - sessions            (Laravel session storage)
--    - migrations          (Laravel migration history)
--    - failed_jobs         (Laravel queue failures)
--    - cache               (Laravel cache)
--    - cache_locks         (cache locking)
--    - jobs                (Laravel queue jobs)
--    - job_batches         (Laravel batch jobs)
--    - password_reset_tokens (password reset)
--    - notifications       (Laravel notifications)
--
-- ════════════════════════════════════════════════════════════════════════════════════════
-- END OF CLEANUP SCRIPT
-- ════════════════════════════════════════════════════════════════════════════════════════
