@echo off
REM ############################################################################
REM TaskFlow Universal Lab Setup Script (Windows)
REM Purpose: Automated single-command deployment for Windows lab environments
REM Usage: setup.bat or double-click in File Explorer
REM ############################################################################

setlocal enabledelayedexpansion
color 0A

echo.
echo ╔════════════════════════════════════════════════════════════════════╗
echo ║                  TaskFlow Lab Setup Script (Windows)               ║
echo ║              Sovereign Project Management System v2.0              ║
echo ╚════════════════════════════════════════════════════════════════════╝
echo.

REM Check for required commands
echo Checking system requirements...

php -v >nul 2>&1
if errorlevel 1 (
    echo [X] PHP not found. Please install PHP 8.3+
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('php -v ^| findstr /R "PHP [0-9]"') do set PHP_VERSION=%%i
echo [OK] %PHP_VERSION%

composer --version >nul 2>&1
if errorlevel 1 (
    echo [X] Composer not found. Please install Composer
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('composer --version') do set COMPOSER_VERSION=%%i
echo [OK] %COMPOSER_VERSION%

node -v >nul 2>&1
if errorlevel 1 (
    echo [X] Node.js not found. Please install Node.js 18+
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('node -v') do set NODE_VERSION=%%i
echo [OK] Node.js %NODE_VERSION%

npm -v >nul 2>&1
if errorlevel 1 (
    echo [X] npm not found. Please install npm 9+
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('npm -v') do set NPM_VERSION=%%i
echo [OK] npm %NPM_VERSION%

echo.
echo Step 1/5: Installing PHP dependencies...
if not exist "vendor" (
    call composer install --no-interaction --prefer-dist
    echo [OK] PHP dependencies installed
) else (
    echo [!] vendor/ already exists, skipping composer install
)

echo.
echo Step 2/5: Setting up environment...
if not exist ".env" (
    copy .env.example .env >nul
    echo [OK] .env created from template
) else (
    echo [!] .env already exists, skipping
)

php artisan key:generate --force
echo [OK] APP_KEY generated

echo.
echo Step 3/5: Setting up database...
php artisan migrate:fresh --seed --force
echo [OK] Database migrated and seeded with test data

echo.
echo Step 4/5: Installing frontend dependencies...
if not exist "node_modules" (
    call npm install --prefer-offline
    echo [OK] npm dependencies installed
) else (
    echo [!] node_modules/ already exists, skipping npm install
)

echo.
echo Step 5/5: Building frontend assets...
call npm run build
echo [OK] Assets compiled successfully

echo.
echo ╔════════════════════════════════════════════════════════════════════╗
echo ║               [OK] Setup Complete - Ready to Deploy               ║
echo ╚════════════════════════════════════════════════════════════════════╝
echo.
echo Next steps:
echo   1. Start development server:
echo      php artisan serve
echo.
echo   2. Open in browser:
echo      http://localhost:8000
echo.
echo   3. Login with test credentials:
echo      Email: admin@test.com
echo      Password: password
echo.
echo For development with live reload, in separate command prompt run:
echo      npm run dev
echo.
pause
