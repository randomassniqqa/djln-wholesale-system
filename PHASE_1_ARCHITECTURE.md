# Task Management System - Phase 1: Database Engine Architecture

## Overview

Phase 1 implements the complete database layer and business logic engine for the Task Management System, adhering to SOLID principles and production-ready standards.

---

## Directory Structure

```
app/
├── Events/
│   ├── TaskCreated.php
│   └── TaskUpdated.php
├── Http/
│   └── Requests/
│       ├── StoreTaskRequest.php
│       ├── UpdateTaskRequest.php
│       ├── StoreProjectRequest.php
│       ├── UpdateProjectRequest.php
│       └── StoreTaskCommentRequest.php
├── Models/
│   ├── User.php
│   ├── Project.php
│   ├── Task.php
│   ├── TaskComment.php
│   └── TaskActivity.php
├── Observers/
│   └── TaskObserver.php
├── Policies/
│   ├── TaskPolicy.php
│   ├── ProjectPolicy.php
│   └── TaskCommentPolicy.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    └── TaskService.php

database/
└── migrations/
    ├── 2025_01_01_000001_create_users_table.php
    ├── 2025_01_01_000002_create_projects_table.php
    ├── 2025_01_01_000003_create_tasks_table.php
    ├── 2025_01_01_000004_create_task_comments_table.php
    └── 2025_01_01_000005_create_task_activities_table.php
```

---

## 1. Database Design

### Schema Overview

#### `users` Table
- **Purpose**: Store user accounts with role-based access
- **Key Fields**:
  - `id` (PK)
  - `role` enum: admin, project_manager, team_member
  - **Indexes**: role, email
- **Relationships**: HasMany (managedProjects, assignedTasks, createdTasks)

#### `projects` Table
- **Purpose**: Store project records
- **Key Fields**:
  - `id` (PK)
  - `manager_id` (FK → users)
  - `status` enum: planning, active, on_hold, completed, cancelled
  - `priority` enum: low, medium, high, critical
  - `due_date` (date)
- **Indexes**: 
  - Single: status, priority, due_date, manager_id
  - Composite: (status, priority)
- **Constraints**: CASCADE on delete (manager_id)

#### `tasks` Table
- **Purpose**: Store individual tasks
- **Key Fields**:
  - `id` (PK)
  - `project_id` (FK → projects)
  - `assigned_user_id` (FK → users, nullable)
  - `created_by` (FK → users)
  - `status`, `priority`, `due_date`
- **Indexes**:
  - Single: project_id, assigned_user_id, created_by, status, priority, due_date
  - Composite: (project_id, status), (assigned_user_id, status), (status, due_date)
- **Constraints**: 
  - CASCADE on delete (project_id)
  - NULL on delete (assigned_user_id)
  - RESTRICT on delete (created_by - preserves audit trail)

#### `task_comments` Table
- **Purpose**: Store task comments with audit trail
- **Key Fields**:
  - `id` (PK)
  - `task_id` (FK → tasks)
  - `user_id` (FK → users)
  - `comment` (text)
- **Indexes**: task_id, user_id, created_at
- **Constraints**: CASCADE on delete

#### `task_activities` Table
- **Purpose**: Automatic activity logging via Observer
- **Key Fields**:
  - `id` (PK)
  - `task_id` (FK → tasks)
  - `user_id` (FK → users)
  - `activity_type` enum: created, status_changed, priority_changed, assigned, reopened, commented, due_date_changed
  - `activity_date` (timestamp, indexed)
  - `description` (text)
- **Indexes**: task_id, user_id, activity_type, activity_date, (task_id, activity_type)
- **Constraints**: CASCADE on delete

### Performance Optimization

**Strategic Indexing**:
- ✅ Single-column indexes on all foreign keys and searchable fields
- ✅ Composite indexes on common query patterns (project_id + status, assigned_user_id + status)
- ✅ Date indexes for sorting and filtering by due_date

**Eager Loading Pattern**:
- All queries use `->with()` to prevent N+1 problems
- Models include convenience methods: `loadAllRelationships()`, `load_relationships()`

**Foreign Key Constraints**:
- CASCADE for cleanup relationships (project → tasks)
- NULL for optional assignments (user unassignment)
- RESTRICT for audit trails (preserves created_by ownership)

---

## 2. Eloquent Models

### Model Architecture

All models follow these principles:

1. **Complete Relationships**: All foreign keys mapped to relationships
2. **Fillable Arrays**: Explicit mass assignment protection
3. **Type Casting**: Dates cast to Carbon instances
4. **Eloquent Helpers**: Business logic methods on models
5. **Eager Loading Helpers**: Methods to prevent N+1 queries

### Model: User

**Relationships**:
- `managedProjects()`: HasMany (1-to-many)
- `assignedTasks()`: HasMany
- `createdTasks()`: HasMany
- `taskComments()`: HasMany
- `taskActivities()`: HasMany

**Helper Methods**:
- `hasRole(string $role)`: Check role
- `isAdmin()`, `isProjectManager()`, `isTeamMember()`: Role shortcuts

### Model: Project

**Relationships**:
- `manager()`: BelongsTo User
- `tasks()`: HasMany Task

**Eager Loading**:
- `load_relationships()`: Loads manager + all tasks with relationships

### Model: Task

**Relationships**:
- `project()`: BelongsTo Project
- `assignedUser()`: BelongsTo User (nullable)
- `creator()`: BelongsTo User
- `comments()`: HasMany TaskComment (ordered by created_at DESC)
- `activities()`: HasMany TaskActivity (ordered by activity_date DESC)

**Dispatch Events**:
- `created` → TaskCreated event
- `updated` → TaskUpdated event

**Helper Methods**:
- `isOverdue()`: Check if past due date and not completed
- `isCompleted()`: Check if status === 'completed'
- `canBeEdited()`: Check if editable (not completed/cancelled)
- `getProjectManager()`: Get the project manager
- `loadAllRelationships()`: Full eager loading with all relationships

### Model: TaskComment

**Relationships**:
- `task()`: BelongsTo Task
- `user()`: BelongsTo User

### Model: TaskActivity

**Relationships**:
- `task()`: BelongsTo Task
- `user()`: BelongsTo User

**Helper Methods**:
- `getActivityLabel()`: Human-readable activity type label

---

## 3. Service Layer Architecture

### TaskService Pattern

**Purpose**: Encapsulate all business logic, keep controllers thin.

**All Public Methods**:
1. `createTask(array $data): Task` - Creates task, Observer handles activity logging
2. `updateTask(Task $task, array $data): Task` - Updates task, Observer logs changes
3. `getUserTasks(User $user, int $perPage): Paginator` - Paginated user tasks with eager loading
4. `getProjectTasks(int $projectId, array $filters): Paginator` - Filtered project tasks
5. `getOverdueTasks(): Collection` - All overdue tasks
6. `getTasksDueSoon(): Collection` - Tasks due in next 7 days
7. `changeTaskStatus(Task $task, string $newStatus): Task` - Status change with logging
8. `assignTask(Task $task, ?User $user): Task` - Task assignment
9. `deleteTask(Task $task): bool` - Task deletion
10. `getProjectTaskStats(int $projectId): array` - Project statistics
11. `getUserTaskStats(int $userId): array` - User statistics
12. `logTaskActivity(Task $task, User $user, string $type, ?string $description): TaskActivity` - Manual logging

**Key Features**:
- ✅ 100% eager loading to prevent N+1
- ✅ Consistent pagination support
- ✅ Filtering and sorting built-in
- ✅ Automatic Observer integration (no manual activity logging needed)
- ✅ All methods return properly loaded relationships

### Usage Example

```php
$taskService = app(TaskService::class);

// Create task - activity logged automatically
$task = $taskService->createTask([
    'project_id' => 1,
    'title' => 'Implement authentication',
    'description' => 'Setup OAuth2',
    'priority' => 'high',
    'due_date' => now()->addDays(5),
    'assigned_user_id' => 2,
    'created_by' => auth()->id(),
]);

// Update task - activity logged automatically
$updated = $taskService->updateTask($task, [
    'status' => 'in_progress',
    'priority' => 'critical',
]);

// Get paginated tasks with filters
$tasks = $taskService->getProjectTasks(1, [
    'status' => 'pending',
    'priority' => 'high',
], 15);
```

---

## 4. Observer Pattern (Automatic Activity Logging)

### TaskObserver

**Location**: `app/Observers/TaskObserver.php`

**Automatically Logs**:
- ✅ Task creation
- ✅ Status changes (with before/after values)
- ✅ Priority changes
- ✅ Task assignments/reassignments
- ✅ Due date changes

**How It Works**:
1. When task is created → `created()` hook fires → TaskActivity recorded
2. When task is updated → `updated()` hook fires → Detects changes → TaskActivity recorded

**Important Note**: The Observer uses `auth()->id()` to track who made the change. This requires middleware to set the authenticated user.

**Registration**: Registered in `AppServiceProvider::boot()`

```php
Task::observe(TaskObserver::class);
```

---

## 5. Form Request Validation

### Form Requests

Every data input is validated through dedicated Form Request classes:

#### StoreTaskRequest
- Validates task creation
- `project_id`: required, exists in projects
- `title`: required, string, 3-255 chars
- `due_date`: required, date, after_or_equal today
- `description`, `status`, `priority`, `assigned_user_id`: optional with validation

#### UpdateTaskRequest
- Validates task updates (all fields optional via 'sometimes')
- Same rules as StoreTaskRequest but non-required

#### StoreProjectRequest
- Validates project creation
- Due date must be after start date

#### UpdateProjectRequest
- Validates project updates

#### StoreTaskCommentRequest
- Validates comment creation
- `task_id`: required, exists in tasks
- `comment`: required, 1-2000 chars

### Implementation

Controllers use requests directly in method signatures:

```php
public function store(StoreTaskRequest $request)
{
    // $request->validated() returns only validated data
    $task = $this->taskService->createTask($request->validated());
}
```

---

## 6. Authorization Policies

### Policy Pattern

All authorization delegated to Policy classes, registered in `AppServiceProvider`.

#### TaskPolicy

**Methods**:
- `viewAny(User $user)`: Admin only
- `view(User $user, Task $task)`: Admin, project manager (of project), assigned user, or creator
- `create(User $user)`: Admin or project manager
- `update(User $user, Task $task)`: Admin, project manager (of project), or assigned user (not completed)
- `updateStatus(User $user, Task $task)`: Same as update
- `assign(User $user, Task $task)`: Admin or project manager only
- `delete(User $user, Task $task)`: Admin or project manager
- `restore(User $user, Task $task)`: Same as delete
- `forceDelete(User $user, Task $task)`: Admin only

**Business Rules Enforced**:
- ❌ Completed tasks cannot be edited
- ✅ Only project manager can assign tasks
- ✅ Assigned user or PM can update status
- ✅ Only PM can delete (not team member)

#### ProjectPolicy

**Methods**:
- `viewAny(User $user)`: All authenticated users
- `view(User $user, Project $project)`: Admin, project manager, or has task in project
- `create(User $user)`: Admin or project manager
- `update(User $user, Project $project)`: Admin or project manager
- `delete(User $user, Project $project)`: Admin only

#### TaskCommentPolicy

**Methods**:
- `create(User $user, TaskComment $comment)`: Any authenticated user
- `update(User $user, TaskComment $comment)`: Admin or comment author
- `delete(User $user, TaskComment $comment)`: Admin or comment author

### Usage in Controllers

```php
// Check authorization
$this->authorize('update', $task);

// Or use policy directly
if ($user->cannot('update', $task)) {
    abort(403);
}
```

---

## 7. Event System

### Events

#### TaskCreated
- Fired when task is created
- Carries the Task model instance
- Can trigger listeners, broadcasting, notifications

#### TaskUpdated
- Fired when task is updated
- Carries the Task model instance
- Can trigger listeners, broadcasting, notifications

**Registration**: In Task model via `$dispatchesEvents`:
```php
protected $dispatchesEvents = [
    'created' => \App\Events\TaskCreated::class,
    'updated' => \App\Events\TaskUpdated::class,
];
```

---

## 8. Validation Rules Summary

### Task Validation

| Field | Create | Update | Rules |
|-------|--------|--------|-------|
| project_id | required | - | integer, exists:projects,id |
| title | required | sometimes | string, 3-255 chars |
| description | - | nullable | string, max 2000 |
| status | optional | sometimes | in: pending, in_progress, on_hold, completed, cancelled |
| priority | optional | sometimes | in: low, medium, high, critical |
| due_date | required | sometimes | date, after_or_equal today |
| assigned_user_id | optional | nullable | integer, exists:users,id |

### Project Validation

| Field | Create | Update | Rules |
|-------|--------|--------|-------|
| name | required | sometimes | string, 3-255 chars |
| description | optional | nullable | string, max 2000 |
| start_date | required | sometimes | date, after_or_equal today |
| due_date | required | sometimes | date, after start_date |
| status | optional | sometimes | in: planning, active, on_hold, completed, cancelled |
| priority | optional | sometimes | in: low, medium, high, critical |
| manager_id | required | sometimes | integer, exists:users,id |

---

## 9. Key Design Decisions

### ✅ Service Layer

All business logic in `TaskService`, controllers remain thin and only orchestrate.

**Benefit**: Tests target service logic directly, easy to reuse from CLI/API/Web.

### ✅ Observer Pattern

Automatic activity logging via `TaskObserver` instead of manual logging in controllers.

**Benefit**: Impossible to forget logging, DRY principle, decoupled from controller logic.

### ✅ Eager Loading Everywhere

Strategic indexes + eager loading on all queries.

**Benefit**: Eliminates N+1 completely, predictable query counts.

### ✅ Strict Foreign Keys

`RESTRICT` on `created_by` to preserve audit trail even if user deleted.

**Benefit**: Historical accuracy, audit compliance.

### ✅ Policies for Authorization

Centralized, reusable authorization logic separate from request/response handling.

**Benefit**: Easy to test, enforced at model level, can be used in multiple channels (Web/API/CLI).

### ✅ Form Requests for Validation

Dedicated validation classes instead of inline rules in controllers.

**Benefit**: Reusable across channels, testable in isolation, clean controller code.

---

## 10. N+1 Prevention Strategy

### Method 1: Dedicated Relationship Loaders

```php
// In Task model
public function loadAllRelationships(): self
{
    return $this->load([
        'project.manager',
        'assignedUser',
        'creator',
        'comments.user',
        'activities.user',
    ]);
}

// Usage
$task = Task::find(1)->loadAllRelationships();
```

### Method 2: Service Layer Eager Loading

```php
// In TaskService
$tasks = Task::with([
    'project.manager',
    'assignedUser',
    'creator'
])->get();
```

### Method 3: Composite Index Queries

Example: Get all pending tasks for a user
```php
$tasks = Task::with(['project', 'comments'])
    ->where('assigned_user_id', $userId)  // Uses index
    ->where('status', 'pending')           // Uses index
    ->get();
```

**Result**: Single query with composite index, relationships loaded in 3 queries max (1 main + 2 relationships).

---

## 11. Testing Strategy

### Unit Tests

Test services with mock repositories:

```php
class TaskServiceTest extends TestCase
{
    public function test_create_task_logs_activity()
    {
        $service = app(TaskService::class);
        $task = $service->createTask([...]);
        
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'activity_type' => 'created',
        ]);
    }
}
```

### Feature Tests

Test policies and authorization:

```php
class TaskPolicyTest extends TestCase
{
    public function test_project_manager_can_update_task()
    {
        $manager = User::factory()->create(['role' => 'project_manager']);
        $task = Task::factory()->create();
        $task->project->update(['manager_id' => $manager->id]);
        
        $this->assertTrue($manager->can('update', $task));
    }
}
```

---

## 12. Next Steps (Phase 2 & Beyond)

### Phase 2: Controllers & Routing
- Implement thin controllers using TaskService & ProjectService
- Setup route groups with middleware
- Integrate Form Requests

### Phase 3: Views & Frontend
- Build Blade templates
- Implement dashboard with statistics
- Task list with filtering/sorting
- Project details page

### Phase 4: API Layer
- Create API controllers
- Implement API policies (may differ from Web)
- API resource transformers
- Pagination, filtering, sorting

### Phase 5: Advanced Features
- Email notifications
- Task reminders
- Reports & export
- Advanced filtering/search
- Performance caching

---

## 13. Deployment Checklist

- [ ] All migrations run successfully
- [ ] All models eager-load relationships
- [ ] Observer registered in AppServiceProvider
- [ ] Policies registered in AppServiceProvider
- [ ] Form Requests validated and tested
- [ ] Database indexes created and verified
- [ ] Foreign key constraints enforced
- [ ] Created_by RESTRICT constraint tested (prevent data loss)
- [ ] Service methods tested for N+1 issues
- [ ] Role-based access tested
- [ ] Activity logging tested

---

## Documentation Summary

**This Phase 1 Implementation Provides**:
- ✅ Optimized database schema with strategic indexing
- ✅ Complete Eloquent models with relationships
- ✅ Service layer with business logic
- ✅ Observer pattern for automatic activity logging
- ✅ Form request validation
- ✅ Authorization policies
- ✅ Event system foundation
- ✅ Zero N+1 query potential (with eager loading)
- ✅ SOLID principles throughout
- ✅ Production-ready code

**Ready for Phase 2**: Controllers & Routing (with thin controllers delegating to services)
