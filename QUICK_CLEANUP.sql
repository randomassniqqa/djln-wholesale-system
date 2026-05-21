-- ════════════════════════════════════════════════════════════════════════════════════════
-- DJLN MARKETING — QUICK REFERENCE: DATABASE CLEANUP SQL
-- Copy & paste directly into phpMyAdmin SQL tab
-- ════════════════════════════════════════════════════════════════════════════════════════

-- STEP 1: Disable foreign key checks (temporary)
SET FOREIGN_KEY_CHECKS = 0;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- STEP 2: Drop Legacy Triggers
-- ════════════════════════════════════════════════════════════════════════════════════════
DROP TRIGGER IF EXISTS `auto_complete_project_on_tasks_done`;
DROP TRIGGER IF EXISTS `trg_prevent_active_project_deletion`;
DROP TRIGGER IF EXISTS `trg_task_status_change`;
DROP TRIGGER IF EXISTS `trg_task_created`;
DROP TRIGGER IF EXISTS `trg_task_updated`;
DROP TRIGGER IF EXISTS `trg_task_deleted`;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- STEP 3: Drop Legacy Procedures
-- ════════════════════════════════════════════════════════════════════════════════════════
DROP PROCEDURE IF EXISTS `sp_complete_task`;
DROP PROCEDURE IF EXISTS `sp_assign_task`;
DROP PROCEDURE IF EXISTS `sp_update_task_status`;
DROP PROCEDURE IF EXISTS `sp_generate_task_report`;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- STEP 4: Drop Legacy Tables (in dependency order - children first)
-- ════════════════════════════════════════════════════════════════════════════════════════

-- Child tables (have foreign keys to parent tables)
DROP TABLE IF EXISTS `task_attachments`;
DROP TABLE IF EXISTS `task_comments`;
DROP TABLE IF EXISTS `task_activities`;
DROP TABLE IF EXISTS `project_members`;

-- Parent tables
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `projects`;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- STEP 5: Re-enable foreign key checks
-- ════════════════════════════════════════════════════════════════════════════════════════
SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════════════════════════════════
-- VERIFICATION: Run these to confirm cleanup was successful
-- ════════════════════════════════════════════════════════════════════════════════════════

-- Show all remaining tables
-- SHOW TABLES;

-- Show table count (should be ~18 tables)
-- SELECT COUNT(*) as total_tables FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE();

-- Show remaining triggers (should see only tr_order_* triggers)
-- SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE();

-- Show remaining procedures (should see only proc_* procedures)  
-- SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE';
