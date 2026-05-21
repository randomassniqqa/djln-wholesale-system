# Phase 1 Quick Reference Guide

## 🚀 Core Components

### Models & Relationships
```php
// User has many projects
$user->managedProjects()->get();

// Project has many tasks
$project->tasks()->get();

// Task belongs to project
$task->project;

// Task assigned to user
$task->assignedUser;

// Task created by user
$task->creator;

// Task has comments
$task->comments;

// Task has activity log
$task->activities;
```

### Service Layer Usage
```php
use App\Services\TaskService;
use App\Models\Task, User, Project;

$taskService = app(TaskService::class);

// Create
$task = $taskService->createTask([
    'project_id' => 1,
    'title' => 'Task title',
    'due_date' => now()->addDays(5),
    'created_by' => auth()->id(),
]);

// Read
$tasks = $taskService->getProjectTasks(1);
$overdue = $taskService->getOverdueTasks();
$upcoming = $taskService->getTasksDueSoon();

// Update
$task = $taskService->updateTask($task, ['status' => 'in_progress']);
$task = $taskService->assignTask($task, $user);

// Status
$task = $taskService->changeTaskStatus($task, 'completed');

// Delete
$taskService->deleteTask($task);

// Stats
$stats = $taskService->getProjectTaskStats(1);
$userStats = $taskService->getUserTaskStats($userId);
```

### Authorization
```php
// Check permission
auth()->user()->can('update', $task);
auth()->user()->cannot('delete', $project);

// Authorize or fail
$this->authorize('update', $task);

// Gate checks
Gate::allows('update', $task);
Gate::denies('delete', $project);

// Role checks
$user->isAdmin();
$user->isProjectManager();
$user->isTeamMember();
```

### Form Validation
```php
// Automatically validates input
public function store(StoreTaskRequest $request)
{
    // All input validated against rules
    $validated = $request->validated();
    $task = $taskService->createTask($validated);
}

public function update(UpdateTaskRequest $request, Task $task)
{
    $validated = $request->validated();
    $task = $taskService->updateTask($task, $validated);
}
```

### Observer Pattern
```php
// Automatic logging on task events:
Task::create([...]);  // → TaskActivity created
$task->update([...]);  // → TaskActivity updated

// Activities created automatically:
// - 'created' when task created
// - 'status_changed' when status changes
// - 'priority_changed' when priority changes
// - 'assigned' when assigned/reassigned
// - 'due_date_changed' when due date changes

// View activity log:
$task->activities()->get();

// Get human-readable label:
$activity->getActivityLabel();
```

## 📊 Common Queries

### Get User's Tasks
```php
$tasks = $taskService->getUserTasks(auth()->user(), 15);
// Paginated, eager-loaded with relationships
```

### Get Project Tasks (Filtered)
```php
$tasks = $taskService->getProjectTasks(
    projectId: 1,
    filters: [
        'status' => 'pending',
        'priority' => 'high',
        'assigned_user_id' => 2,
        'due_date_from' => now()->toDateString(),
        'due_date_to' => now()->addDays(7)->toDateString(),
    ],
    perPage: 15
);
```

### Get Task Statistics
```php
$projectStats = $taskService->getProjectTaskStats(1);
// Returns: [
//     'total' => 25,
//     'pending' => 10,
//     'in_progress' => 5,
//     'on_hold' => 2,
//     'completed' => 7,
//     'cancelled' => 1,
//     'overdue' => 3,
// ]
```

### Find Overdue Tasks
```php
$overdue = $taskService->getOverdueTasks();
// Returns collection of tasks past due_date (not completed)
```

### Find Tasks Due Soon
```php
$soon = $taskService->getTasksDueSoon();
// Returns collection of tasks due within 7 days
```

## 🔍 Model Helpers

### Task Model Methods
```php
$task->isOverdue();           // Is past due date and not completed?
$task->isCompleted();         // Status === 'completed'?
$task->canBeEdited();         // Can be modified (not completed/cancelled)
$task->getProjectManager();   // Get the PM's User instance
$task->loadAllRelationships(); // Eager load all relations
```

### User Model Methods
```php
$user->hasRole('admin');      // Check specific role
$user->isAdmin();             // Is admin?
$user->isProjectManager();    // Is project manager?
$user->isTeamMember();        // Is team member?
```

### Activity Helper
```php
$activity->getActivityLabel();
// Returns: 'Task created' or 'Status changed', etc.
```

## 🛡️ Authorization Rules

### Task Policies
```php
can('view', $task)                    → Admin | PM | assigned user | creator
can('viewAny', Task::class)           → Admin only
can('create', Task::class)            → Admin | Project Manager
can('update', $task)                  → Admin | PM | assigned user (not completed)
can('updateStatus', $task)            → Admin | PM | assigned user (not completed)
can('assign', $task)                  → Admin | Project Manager
can('delete', $task)                  → Admin | Project Manager
```

### Project Policies
```php
can('view', $project)                 → Admin | PM | has task in project
can('viewAny', Project::class)        → All authenticated users
can('create', Project::class)         → Admin | Project Manager
can('update', $project)               → Admin | PM
can('delete', $project)               → Admin only
```

### Comment Policies
```php
can('create', TaskComment::class)     → Any authenticated user
can('update', $comment)               → Admin | comment author
can('delete', $comment)               → Admin | comment author
```

## ✅ Validation Rules

### Task Validation
```
project_id     required | integer | exists:projects,id
title          required | string | min:3 | max:255
description    nullable | string | max:2000
status         nullable | in:pending,in_progress,on_hold,completed,cancelled
priority       nullable | in:low,medium,high,critical
due_date       required | date | after_or_equal:today
assigned_user_id nullable | integer | exists:users,id
```

### Project Validation
```
name           required | string | min:3 | max:255
description    nullable | string | max:2000
start_date     required | date | after_or_equal:today
due_date       required | date | after:start_date
status         nullable | in:planning,active,on_hold,completed,cancelled
priority       nullable | in:low,medium,high,critical
manager_id     required | integer | exists:users,id
```

## 🎯 Status & Priority Values

### Task/Project Status Enums
```
pending       (planning stage)
in_progress   (actively being worked on)
on_hold       (paused)
completed     (finished)
cancelled     (abandoned)
```

### Priority Enums
```
low           (can wait)
medium        (normal priority)
high          (important)
critical      (urgent/blocking)
```

## 🔗 Database Indexes

### Performance Indexes Created
```
users:              role, email
projects:           status, priority, due_date, manager_id, (status,priority)
tasks:              project_id, assigned_user_id, created_by, status, priority, 
                    due_date, (project_id,status), (assigned_user_id,status), 
                    (status,due_date)
task_comments:      task_id, user_id, created_at
task_activities:    task_id, user_id, activity_type, activity_date, (task_id,type)
```

## 🚫 Constraint Details

### Foreign Keys
```
projects.manager_id           → RESTRICT (keep PM history)
tasks.project_id              → CASCADE (clean up)
tasks.assigned_user_id        → NULL (keep task history)
tasks.created_by              → RESTRICT (audit trail)
task_comments.*               → CASCADE (clean up)
task_activities.*             → CASCADE (clean up)
```

## 📝 Event Dispatching

### Task Events
```php
// Dispatched automatically
TaskCreated::dispatch($task);   // When task created
TaskUpdated::dispatch($task);   // When task updated

// Listen to events (Phase 2)
// Each event can trigger listeners, notifications, broadcasting, etc.
```

## ⚡ Performance Tips

1. **Always use TaskService** - Not raw models directly
2. **Service methods eager load** - Never N+1 queries
3. **Use pagination** - For large result sets
4. **Filter at database level** - Not in PHP
5. **Check indexes** - Before complex queries
6. **Use composite indexes** - For common filters
7. **Eager load relationships** - With `with()`
8. **Avoid loop queries** - Load all at once

## 🐛 Debugging

### Check N+1 Issues
```php
// Use eager loading metrics
DB::listen(function ($query) {
    echo $query->sql;
});

$tasks = Task::with('project', 'assignedUser')->get();
// Good: 3 queries max (1 tasks + 2 relationships)

$tasks = Task::all();
foreach ($tasks as $task) {
    echo $task->project->name;  // Bad: 1 + N queries
}
```

### View Activity Log
```php
$task->activities()
    ->with('user')
    ->orderByDesc('activity_date')
    ->get();
```

### Test Authorization
```php
auth()->login($user);
auth()->user()->can('update', $task); // true/false
auth()->user()->cannot('delete', $task); // true/false
```

## 🔄 Workflow Example

```php
// 1. Create project
$project = Project::create([
    'name' => 'Q1 Release',
    'manager_id' => $pm->id,
    'due_date' => now()->addMonths(3),
]);

// 2. Create tasks via service
$task1 = $taskService->createTask([
    'project_id' => $project->id,
    'title' => 'Authentication',
    'priority' => 'critical',
    'due_date' => now()->addWeeks(2),
    'created_by' => auth()->id(),
]);

// Activity: 'created' logged automatically

// 3. Assign task
$task1 = $taskService->assignTask($task1, $dev);
// Activity: 'assigned' logged automatically

// 4. Update status
$task1 = $taskService->changeTaskStatus($task1, 'in_progress');
// Activity: 'status_changed' logged automatically

// 5. View activity
$task1->activities()->with('user')->get();
// Shows complete audit trail

// 6. Get stats
$stats = $taskService->getProjectTaskStats($project->id);
// Shows progress overview
```

---

**Remember**: The engine is designed to be extended, not modified. Use services, policies, and observers for all changes.
