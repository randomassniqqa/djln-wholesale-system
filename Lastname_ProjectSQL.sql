-- Lastname_ProjectSQL.sql
-- TaskFlow Lab Activity: SQL Procedures, Views & Delimiters
--
-- Main tables used in this project:
-- - users
-- - projects
-- - tasks
-- - task_activities
--
-- This script is written in MySQL/MariaDB style because the lab requires DELIMITER blocks.
-- If you run PostgreSQL for the app, keep this file for submission or adapt the syntax for PostgreSQL procedures.

/* =========================================================
   PART 1: TABLES USED
   ========================================================= */
-- Main table: projects
-- Related table: tasks
-- Supporting tables: users, task_activities

/* =========================================================
   PART 2: VIEW #1
   A reusable summary for project dashboards.
   ========================================================= */
DROP VIEW IF EXISTS vw_project_task_summary;
CREATE OR REPLACE VIEW vw_project_task_summary AS
SELECT
    p.id AS project_id,
    p.name AS project_name,
    p.status AS project_status,
    p.priority AS project_priority,
    p.start_date,
    p.due_date,
    u.name AS manager_name,
    COUNT(t.id) AS total_tasks,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
    SUM(CASE WHEN t.status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) AS open_tasks,
    SUM(CASE WHEN t.due_date < CURDATE() AND t.status <> 'completed' THEN 1 ELSE 0 END) AS overdue_tasks
FROM projects p
LEFT JOIN users u ON u.id = p.manager_id
LEFT JOIN tasks t ON t.project_id = p.id
GROUP BY
    p.id,
    p.name,
    p.status,
    p.priority,
    p.start_date,
    p.due_date,
    u.name;

/* =========================================================
   PART 3: VIEW #2
   Filtered list of active projects only.
   ========================================================= */
DROP VIEW IF EXISTS vw_active_projects;
CREATE OR REPLACE VIEW vw_active_projects AS
SELECT
    p.id AS project_id,
    p.name AS project_name,
    p.description,
    p.status,
    p.priority,
    p.due_date,
    u.name AS manager_name,
    COUNT(t.id) AS task_count
FROM projects p
LEFT JOIN users u ON u.id = p.manager_id
LEFT JOIN tasks t ON t.project_id = p.id
WHERE p.status = 'active'
GROUP BY
    p.id,
    p.name,
    p.description,
    p.status,
    p.priority,
    p.due_date,
    u.name;

/* =========================================================
   PART 4: DELIMITER
   ========================================================= */
DELIMITER $$

/* =========================================================
   PART 5: STORED PROCEDURE #1
   Display project summary data from the reusable view.
   ========================================================= */
DROP PROCEDURE IF EXISTS sp_show_project_task_summary$$
CREATE PROCEDURE sp_show_project_task_summary()
BEGIN
    SELECT *
    FROM vw_project_task_summary
    ORDER BY project_name ASC;
END$$

/* =========================================================
   PART 5: STORED PROCEDURE #2
   Display tasks for a specific project.
   ========================================================= */
DROP PROCEDURE IF EXISTS sp_get_tasks_by_project$$
CREATE PROCEDURE sp_get_tasks_by_project(IN p_project_id BIGINT)
BEGIN
    SELECT
        t.id AS task_id,
        t.title,
        t.status,
        t.priority,
        t.due_date,
        p.name AS project_name,
        assigned_user.name AS assigned_to,
        creator.name AS created_by_name
    FROM tasks t
    INNER JOIN projects p ON p.id = t.project_id
    LEFT JOIN users assigned_user ON assigned_user.id = t.assigned_user_id
    LEFT JOIN users creator ON creator.id = t.created_by
    WHERE t.project_id = p_project_id
    ORDER BY t.due_date ASC, t.id ASC;
END$$

/* =========================================================
   PART 5: STORED PROCEDURE #3
   Perform an actual operation in the system.
   Marks a task as completed and writes an activity record.
   ========================================================= */
DROP PROCEDURE IF EXISTS sp_complete_task$$
CREATE PROCEDURE sp_complete_task(IN p_task_id BIGINT, IN p_user_id BIGINT)
BEGIN
    DECLARE v_task_title VARCHAR(255);

    START TRANSACTION;

    SELECT title
    INTO v_task_title
    FROM tasks
    WHERE id = p_task_id;

    UPDATE tasks
    SET status = 'completed',
        updated_at = NOW()
    WHERE id = p_task_id;

    INSERT INTO task_activities (
        task_id,
        user_id,
        activity_type,
        description,
        activity_date,
        created_at,
        updated_at
    )
    VALUES (
        p_task_id,
        p_user_id,
        'status_changed',
        CONCAT('Task "', COALESCE(v_task_title, 'Unknown'), '" was marked as completed'),
        NOW(),
        NOW(),
        NOW()
    );

    COMMIT;
END$$

/* =========================================================
   PART 6: RESTORE DELIMITER
   ========================================================= */
DELIMITER ;

/* =========================================================
   PART 7: EXECUTION EXAMPLES
   ========================================================= */
-- Views
SELECT * FROM vw_project_task_summary;
SELECT * FROM vw_active_projects;

-- Procedures
CALL sp_show_project_task_summary();
CALL sp_get_tasks_by_project(1);
CALL sp_complete_task(1, 1);

/* =========================================================
   PART 8: APPLY IN YOUR SYSTEM (Laravel example)
   ========================================================= */
-- DB::select('SELECT * FROM vw_project_task_summary');
-- DB::statement('CALL sp_complete_task(?, ?)', [$taskId, auth()->id()]);

/* =========================================================
   PART 9: SHORT EXPLANATION (3-5 sentences)
   ========================================================= */
-- This script adds two reusable views that simplify common project and task queries.
-- The first view gives a project summary with task counts, completed work, and overdue tasks.
-- The second view filters the data to active projects only for quick reporting.
-- Three stored procedures are included for showing summaries, filtering tasks by project, and completing a task while logging activity.
