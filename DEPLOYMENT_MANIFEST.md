# TaskFlow Laboratory Deployment Manifest

**System**: TaskFlow Sovereign Project Management Ecosystem  
**Version**: 2.0 Enterprise Grade  
**Deployment Date**: April 16, 2026  
**Portability**: Universal Lab Deployment

---

## Pre-Deployment Verification Checklist

### ✅ Repository Cleanliness
- [x] `.gitignore` excludes `node_modules`, `vendor`, `.env`, `storage/*.key`
- [x] Repository size optimized for lab Wi-Fi (no binaries, only source code)
- [x] All relative paths used (no hardcoded `C:\Users\`, `/home/user/`, etc.)
- [x] `.git` history clean and ready for clone

### ✅ Environment Configuration
- [x] `.env.example` contains all required placeholders
- [x] `APP_KEY` generation documented in quick-start
- [x] Database connection defaults to SQLite (zero setup required)
- [x] MySQL alternative documented in `.env.example` (commented)

### ✅ Database Layer
- [x] All migrations use relative paths via Laravel helpers
- [x] Foreign key constraints with CASCADE delete enforced
- [x] `database/seeders/DatabaseSeeder.php` creates:
  - 1 Admin account (`admin@test.com`)
  - 2 Project Managers (`pm1@test.com`, `pm2@test.com`)
  - 5 Team Members (`team1@test.com` - `team5@test.com`)
  - 5 Sample projects
  - 30 Sample tasks
  - All test password: `password`
- [x] Zero hardcoded IDs in migration logic
- [x] Idempotency keys table for request deduplication
- [x] Activity logging observer auto-wires on migration

### ✅ Frontend Asset Pipeline
- [x] `vite.config.js` uses relative paths (no hardcoded project path)
- [x] Tailwind CSS v4 configured for dynamic JIT compilation
- [x] `npm install && npm run build` generates `/public/build` artifacts
- [x] Asset caching optimized for development and production
- [x] `public/build` folder gitignored (regenerated on each deploy)

### ✅ Authorization & Security
- [x] `Gate::before()` centralized authorization ready
- [x] Policy classes validated against Laravel v11
- [x] Role-based access control (admin, project_manager, team_member)
- [x] Session driver set to `database` (survives browser close)
- [x] No hardcoded encryption keys (generated per deployment)

### ✅ Performance & Caching
- [x] 7+ composite database indexes present
- [x] Eager loading patterns verified in DashboardController
- [x] 5-minute cache TTL configured for KPI aggregation
- [x] Queue system set to database (no external dependencies)

### ✅ Code Portability
- [x] Storage folder paths use `storage_path()` helper
- [x] Public folder paths use `public_path()` helper
- [x] Base application path uses `base_path()` helper
- [x] No shell-specific commands in PHP (cross-OS compatible)
- [x] Migration timestamps use UTC (locale-independent)

---

## Deployment Procedure

### Terminal Location
```
cd /path/to/task-management-system
```

### Command 1: PHP Dependency Installation
```bash
composer install
```
**Duration**: 30-60 seconds (depends on Wi-Fi speed)  
**Output**: Creates `vendor/` folder with all Laravel dependencies  
**Failure Mode**: If fails, likely PHP version mismatch (requires 8.3+)

### Command 2: Environment Initialization
```bash
cp .env.example .env
php artisan key:generate
```
**Duration**: 2-3 seconds  
**Output**: 
- Creates `.env` file (copy of `.env.example`)
- Generates unique `APP_KEY` (critical for session encryption)
**Failure Mode**: If `.env` already exists, `cp` will error (safe to skip)

### Command 3: Database Seeding
```bash
php artisan migrate:fresh --seed
```
**Duration**: 5-10 seconds  
**Output**:
- Creates `database/database.sqlite` (SQLite database)
- Runs 13 migrations (schema creation)
- Seeds test data (1 admin, 2 managers, 5 team members, 5 projects, 30 tasks)
**Failure Mode**: If database file locked, close any other processes accessing it

### Command 4: Frontend Asset Build
```bash
npm install && npm run build
```
**Duration**: 30-90 seconds (first time longer due to npm download)  
**Output**: Generates `/public/build` folder with compiled CSS/JS  
**Failure Mode**: If Node.js missing, install from https://nodejs.org/

---

## Runtime Verification

### Start Development Server
```bash
php artisan serve
```
**Output**: `Server running on http://localhost:8000`  
**Port**: Default 8000 (changeable via `--port` flag)  
**Access**: Open browser to `http://localhost:8000`

### Test Login
1. Navigate to login page (automatically redirected if not authenticated)
2. Email: `admin@test.com` Password: `password`
3. Verify dashboard loads with sample projects visible
4. Check cache working: Refresh dashboard (should load in <250ms)

### Test Admin Functions
1. Click "Users" in admin panel
2. Verify all 8 users created successfully
3. Test role assignment changes (save immediately)
4. Verify audit trail in TaskActivity table

### Test Permissions
1. Logout and login as `pm1@test.com`
2. Verify only assigned projects visible
3. Attempt to access other manager's project (should fail with 403)
4. Create new task and verify audit trail auto-logged

---

## Deployment Environment Specifications

### Minimum Lab Requirements
- **OS**: Windows 10+, macOS 11+, Linux (Ubuntu 20.04+)
- **PHP**: 8.3 or higher (check: `php -v`)
- **Composer**: Latest (check: `composer --version`)
- **Node.js**: 18.0+ (check: `node -v`)
- **npm**: 9.0+ (check: `npm -v`)
- **SQLite**: Included with PHP (check: `php -m | grep sqlite`)
- **Storage**: ~500MB disk space (including `node_modules`)
- **RAM**: 2GB minimum (4GB recommended)
- **Network**: Lab Wi-Fi required (for initial clone/npm install)

### Optional Upgrades
- **MySQL**: If using MySQL instead of SQLite, update `.env`:
  ```
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=taskflow_db
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- **Redis**: For production caching (uncomment in `.env`)
- **Supervisor**: For queue worker (optional for lab)

---

## Post-Deployment Maintenance

### Daily Lab Restart
```bash
# Terminal 1: PHP Server
php artisan serve

# Terminal 2 (optional): Frontend Watch Mode
npm run dev
```

### Reset Database (Erase Test Data)
```bash
php artisan migrate:fresh --seed
```
This recreates fresh sample data.

### Rebuild Assets After Code Changes
```bash
npm run build
```

### View Application Logs
```bash
tail -f storage/logs/laravel.log
```

### Database Backup (SQLite)
```bash
cp database/database.sqlite database/database.sqlite.backup
```

---

## Troubleshooting Reference

### Issue: "Class not found" during migration
**Cause**: Composer install incomplete  
**Fix**: `composer install --no-dev`

### Issue: "Port 8000 already in use"
**Cause**: Another application using port 8000  
**Fix**: `php artisan serve --port=8001`

### Issue: Assets not loading (404 on CSS/JS)
**Cause**: Vite assets not built  
**Fix**: `npm run build` (or `npm run dev` in separate terminal)

### Issue: "SQLSTATE[HY000]: General error: 1 unable to open database file"
**Cause**: SQLite database file permission issue  
**Fix**: `chmod 666 database/database.sqlite`

### Issue: Seeding fails with "Unique constraint"
**Cause**: Database not fully fresh  
**Fix**: `php artisan migrate:refresh --seed`

### Issue: Session data lost after page refresh
**Cause**: Session driver not database  
**Check**: Verify `.env` contains `SESSION_DRIVER=database`

---

## Security Attestations

- ✅ **No Shell Commands**: All commands cross-OS compatible
- ✅ **No Hardcoded Credentials**: All credentials in `.env`
- ✅ **No Hardcoded Paths**: All paths relative (Laravel helpers)
- ✅ **Unique Keys Per Deploy**: `php artisan key:generate` each instance
- ✅ **Immutable Audit Logs**: TaskActivity records cannot be modified
- ✅ **Encrypted Sessions**: APP_KEY encrypts session data
- ✅ **Password Hashing**: All test passwords bcrypt hashed
- ✅ **CSRF Protection**: Enabled on all forms (Laravel default)

---

## GitHub Deployment Notes

Before pushing to lab repository:

```bash
# Verify .gitignore is working
git status | grep -E "vendor/|node_modules/|\.env|storage/.*\.key"
# (Should return nothing)

# Remove accidentally committed files
git rm --cached vendor/ node_modules/ .env storage/*.key

# Commit final state
git add -A
git commit -m "Lab deployment ready - portable configuration"
git push origin main
```

---

## Final Checklist (Lab PC Day-Of)

- [ ] Clone repository: `git clone <repo-url>`
- [ ] Navigate to project: `cd task-management-system`
- [ ] Run: `composer install`
- [ ] Run: `cp .env.example .env && php artisan key:generate`
- [ ] Run: `php artisan migrate:fresh --seed`
- [ ] Run: `npm install && npm run build`
- [ ] Run: `php artisan serve`
- [ ] Open: `http://localhost:8000`
- [ ] Login: `admin@test.com` / `password`
- [ ] Verify dashboard appears
- [ ] Verify all 8 users in admin panel

---

**Deployment Manifest Verified**: April 16, 2026  
**Author**: Kenny Ray M. Tadena  
**Status**: ✅ Ready for Universal Lab Deployment
