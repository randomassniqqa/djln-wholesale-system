# Phase 5 - Quick Start Guide

## Prerequisites
- PHP 8.2+
- Laravel 11
- MySQL 8.0+
- Node.js (for asset compilation if needed)

## Installation & Setup

### Step 1: Run Database Migrations
```bash
cd task-management-system
php artisan migrate
```

This creates:
- `notifications` table - Stores all notification history
- `task_attachments` table - Stores file attachment metadata

### Step 2: Setup File Storage
```bash
php artisan storage:link
```

Creates symlink for public file access at `public/storage`

### Step 3: Clear Application Cache
```bash
php artisan config:cache
php artisan route:cache
```

## Verification

### Test Notifications
1. Login as User A
2. Create a task and assign to User B
3. User B should receive a notification
4. Click notification to navigate to task

### Test File Attachments
1. Open any task
2. Click "Documents" tab
3. Drag a file into the upload zone
4. File should appear in list
5. Click download to verify
6. Click delete to remove

### Test Analytics
1. Login as Admin or Project Manager
2. Navigate to Admin → Analytics
3. View dashboard with metrics and charts
4. Verify pie chart and line chart render

### Test Global Search
1. Click on search bar in navbar
2. Type at least 2 characters
3. See projects and tasks in dropdown
4. Click result to navigate

## Configuration

### File Upload Settings
File: `app/Http/Controllers/AttachmentController.php`

```php
// Max file size (default 10MB)
$request->validate([
    'file' => 'required|file|max:10240'
]);

// Allowed MIME types (optional restriction)
// $request->validate([
//     'file' => 'required|file|mimes:pdf,jpg,png,doc,docx|max:10240'
// ]);
```

### Search Settings
File: `app/Http/Controllers/SearchController.php`

```php
// Minimum query length
if (strlen($query) < 2) { ... }

// Results per type
->limit(5)
```

### Analytics Caching
File: `app/Http/Controllers/AnalyticsController.php`

```php
// Optional: Cache stats for 1 hour
Cache::remember('analytics_stats', 3600, function () {
    return $this->getStats();
});
```

## Troubleshooting

### "Class NotificationController not found"
```bash
php artisan config:cache
php artisan route:cache
```

### File uploads not working
1. Check storage directory is writable: `chmod 755 storage/app/public`
2. Verify storage symlink exists: `ls -la public/storage`
3. Check `FILESYSTEM_DISK` in `.env` is set to 'public'

### Notifications not appearing
1. Check `php artisan queue:listen` if using queues
2. Verify `TaskObserver` is registered in `AppServiceProvider`
3. Check database for notifications: `SELECT * FROM notifications;`

### Search returns no results
1. Verify database connection is active
2. Check search query is at least 2 characters
3. Verify projects/tasks exist in database
4. Run: `php artisan tinker` → `Project::count()`

### Charts not rendering
1. Check browser console for errors
2. Verify Chart.js CDN is accessible
3. Clear browser cache (Ctrl+F5)
4. Check data is JSON valid: `/analytics/task-status`

## API Endpoints

### Notifications
```
GET    /notifications/unread-count         # Returns {count: N}
GET    /notifications/recent               # Returns last 10 notifications
PUT    /notifications/{id}/read             # Mark as read
PUT    /notifications/mark-all-read        # Mark all as read
DELETE /notifications/{id}                 # Delete notification
```

### Attachments
```
POST   /tasks/{task}/attachments           # Upload file
DELETE /tasks/{task}/attachments/{id}      # Delete file
GET    /attachments/{id}/download          # Download file
```

### Analytics
```
GET    /analytics/                         # Dashboard view
GET    /analytics/task-status             # JSON: pie chart data
GET    /analytics/completion-trend        # JSON: trend line data
```

### Search
```
GET    /search?query={q}                  # Returns {projects[], tasks[]}
```

## Performance Optimization

### Database Indexes
Verify indexes exist:
```bash
php artisan tinker
>>> DB::table('task_attachments')->getConnection()->getDoctrineSchemaManager()->listTableIndexes('task_attachments');
>>> DB::table('notifications')->getConnection()->getDoctrineSchemaManager()->listTableIndexes('notifications');
```

### Query Optimization
Enable query logging in development:
```php
// In .env or tinker
DB::enableQueryLog();
// Run command
dd(DB::getQueryLog());
```

### Asset Caching
Add to `.htaccess` or Nginx config:
```
Cache-Control: max-age=31536000 (for CDN assets)
Cache-Control: no-cache (for Chart.js)
```

## Monitoring

### Check Application Health
```bash
# Check logs
tail -f storage/logs/laravel.log

# Check database connection
php artisan tinker
>>> DB::getPdo();
```

### Monitor Notification Queue (if using)
```bash
php artisan queue:list
php artisan queue:monitor
```

## Rollback (If Needed)

### Undo Migrations
```bash
# Rollback all Phase 5 migrations
php artisan migrate:rollback --step=2

# Or manually delete tables
DROP TABLE task_attachments;
DROP TABLE notifications;
```

### Revert Code Changes
```bash
git revert --no-edit <commit-hash>
```

## Support & Debugging

### Enable Debug Mode
```php
# .env
APP_DEBUG=true
```

### Access Tinker Shell
```bash
php artisan tinker
>>> User::find(1)->notifications()->first();
>>> Task::find(1)->attachments()->first();
```

### Check User Permissions
```bash
php artisan tinker
>>> auth()->loginUsingId(1);
>>> Gate::allows('update', Task::find(1));
```

## Next Steps

1. ✅ Run migrations: `php artisan migrate`
2. ✅ Test all Phase 5 features
3. ✅ Review security checklist
4. ✅ Deploy to staging environment
5. ✅ Conduct user acceptance testing (UAT)
6. ✅ Deploy to production

---

**Version:** 1.0  
**Last Updated:** January 12, 2025  
**Status:** Ready for Production
