# 📚 TaskFlow Multi-Device Setup Guide

**Complete beginner-friendly guide for running TaskFlow on another device**

---

## 📋 Table of Contents

1. [Prerequisites - What You Need](#prerequisites)
2. [Step-by-Step Setup](#step-by-step-setup)
3. [Run the Application](#run-the-application)
4. [Access the Database](#access-the-database)
5. [Troubleshooting](#troubleshooting)
6. [Demo Accounts](#demo-accounts)

---

## Prerequisites

### What You Need to Install First

Before you can run TaskFlow on another device, you need these three things:

#### **1. PHP 8.2+** ✅
- **What it does:** Runs the TaskFlow application
- **Download:** https://www.php.net/downloads
- **Installation:**
  - Windows: Use installer
  - Mac: Use Homebrew (`brew install php`)
  - Linux: Use package manager

**Verify installation:**
```bash
php --version
```

#### **2. Composer** ✅
- **What it does:** Installs all PHP libraries (dependencies)
- **Download:** https://getcomposer.org/download/
- **Installation:** Follow the installer

**Verify installation:**
```bash
composer --version
```

#### **3. Git** ✅
- **What it does:** Downloads the project from GitHub
- **Download:** https://git-scm.com/download
- **Installation:** Follow the installer

**Verify installation:**
```bash
git --version
```

---

## Step-by-Step Setup

### **Step 1: Open Terminal/Command Prompt**

- **Windows:** 
  - Press `Win + R`
  - Type `powershell` and press Enter
  
- **Mac:** 
  - Open Applications → Utilities → Terminal

- **Linux:** 
  - Open Terminal

---

### **Step 2: Navigate to Desktop**

```bash
cd Desktop
```

*This puts you on your Desktop where we'll download the project*

---

### **Step 3: Clone from GitHub**

```bash
git clone https://github.com/ktadena547638-create/IT9aL.git
```

**What this does:**
- Downloads the entire TaskFlow project to your Desktop
- Creates a folder called `IT9aL`

**Wait for it to complete** (might take 1-2 minutes)

---

### **Step 4: Navigate into Project**

```bash
cd IT9aL/task-management-system
```

**What this does:**
- Opens the TaskFlow project folder
- You should now be in the main project directory

---

### **Step 5: Install Dependencies**

```bash
composer install
```

**What this does:**
- Installs all PHP libraries that TaskFlow needs
- Creates a `vendor` folder

**⏳ Wait for it to complete** (might take 2-5 minutes depending on internet speed)

---

### **Step 6: Copy Environment File**

```bash
copy .env.example .env
```

**What this does:**
- Creates a `.env` file with default settings
- This file stores important configuration

**Note:** On Mac/Linux, use:
```bash
cp .env.example .env
```

---

### **Step 7: Generate Application Key**

```bash
php artisan key:generate
```

**What this does:**
- Creates a secret encryption key for the application
- Stores it in the `.env` file

---

### **Step 8: Create Database**

TaskFlow uses SQLite (easiest) by default. Create it with:

```bash
# On Windows:
type nul > database/database.sqlite

# On Mac/Linux:
touch database/database.sqlite
```

**What this does:**
- Creates an empty database file
- The database will store all your projects, tasks, users, etc.

---

### **Step 9: Run Database Migrations**

```bash
php artisan migrate
```

**What this does:**
- Creates all database tables
- Sets up the structure for storing data

**You should see:**
```
Migrated: ...
Migrated: ...
```

---

### **Step 10: Seed Demo Data**

```bash
php artisan db:seed
```

**What this does:**
- Adds sample data: admin user, managers, team members, projects, tasks
- Allows you to login and test the application

**You should see:**
```
✅ TASKFLOW LIVE ENVIRONMENT CREATED
```

---

## Run the Application

### **Start the Server**

```bash
php artisan serve
```

**What this does:**
- Starts the web server
- Makes TaskFlow accessible from your browser

**You should see:**
```
Server running on [http://127.0.0.1:8000]
```

---

### **Open in Browser**

1. Open your browser (Chrome, Firefox, Edge, etc.)
2. Go to: `http://localhost:8000`

**You should see the TaskFlow login page!** ✅

---

## Access the Database

### **Option A: Simple SQLite Viewer** (Easiest - Single Device)

**Windows:**
1. Download SQLite Browser: https://sqlitebrowser.org/
2. Open it
3. Click File → Open
4. Navigate to: `database/database.sqlite`
5. View your tables and data

**Mac/Linux:**
```bash
# Install if needed:
brew install sqlite3

# Open database:
sqlite3 database/database.sqlite

# View tables:
.tables

# Exit:
.exit
```

---

### **Option B: Convert to MySQL** (For Real Collaboration - **RECOMMENDED FOR YOUR PROJECT**)

If you want to share with team members on different devices, use MySQL:

**Step 1: Install MySQL**
- Download: https://dev.mysql.com/downloads/mysql/
- Follow installer

**Step 2: Create Database**
```bash
mysql -u root -p
CREATE DATABASE taskflow;
EXIT;
```

**Step 3: Update `.env` file**

Open `.env` in a text editor and change:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Step 4: Run migrations**
```bash
php artisan migrate
php artisan db:seed
```

---

### **Option C: Use phpMyAdmin** (Visual Interface - Best for Beginners)

**phpMyAdmin** is like Excel for your database - you can see everything visually!

#### **On Your Current PC:**

**Step 1: Install phpMyAdmin**
1. Download: https://www.phpmyadmin.net/
2. Extract the zip file
3. Place folder in: `C:\xampp\htdocs\` (or your web server folder)

**Step 2: Access It**
1. Open browser
2. Go to: `http://localhost/phpmyadmin`
3. Login with:
   - Username: `root`
   - Password: (leave blank or your MySQL password)

**You'll see your database like in the screenshot!** ✅

---

## 🌐 Access Database from ANOTHER DEVICE (Your Project Need!)

**This is what you really need!** Here's how to see your database from a different PC:

### **EASY METHOD: Share via Network** (Recommended)

#### **On Your Current PC (Server):**

**Step 1: Find Your IP Address**
```bash
ipconfig
```

Look for "IPv4 Address" - should be something like: `192.168.1.100`

**Write it down!** You'll need it.

**Step 2: Enable Remote MySQL Access**

Open file: `C:\Program Files\MySQL\MySQL Server 8.0\my.ini`

Find this line:
```
bind-address=127.0.0.1
```

Change it to:
```
bind-address=0.0.0.0
```

Save the file.

**Step 3: Restart MySQL Service**

Windows:
- Press `Win + R`
- Type: `services.msc`
- Find "MySQL80" (or your version)
- Right-click → Restart

---

#### **On the Other PC (Your Team's Device):**

**Step 1: Install phpMyAdmin on That PC Too**
- Download: https://www.phpmyadmin.net/
- Extract to web folder (same as above)

**Step 2: Access Your Database**
1. Open browser
2. Go to: `http://localhost/phpmyadmin`
3. Click "New" (or add new server)
4. Enter:
   - Server: `YOUR_PC_IP` (example: `192.168.1.100`)
   - Username: `root`
   - Password: (your MySQL password)

**Step 3: Connect!**
- Click "Connect"
- **You'll see YOUR database from the other PC!** ✅

---

### **What You'll See on Other PC:**

✅ All your tables (users, projects, tasks, etc.)  
✅ All your data (admin, managers, team members)  
✅ Can view data visually  
✅ Can add/edit/delete data  
✅ Can run queries  
✅ Can export/backup  

**Exactly like your screenshot!** 📊

---

### **SUPER SIMPLE DIAGRAM:**

```
Your PC (Main Computer)
├─ MySQL Database (taskflow)
│  ├─ users table (16 users)
│  ├─ projects table (3 projects)
│  ├─ tasks table (30 tasks)
│  └─ ... more tables
│
└─ phpMyAdmin running on http://localhost/phpmyadmin

        ↓ (Network Connection)

Other PC (Team Member's Computer)
├─ phpMyAdmin installed
│
└─ Access: http://192.168.1.100/phpmyadmin
   └─ See SAME database as Your PC! ✅
```

---

### **Step-by-Step Setup for Network Access:**

#### **QUICK CHECKLIST:**

**On Your PC:**
- [ ] MySQL installed
- [ ] Database "taskflow" created
- [ ] my.ini updated (bind-address=0.0.0.0)
- [ ] MySQL service restarted
- [ ] Your IP address written down (ipconfig)

**On Other PC:**
- [ ] phpMyAdmin installed
- [ ] Browser open
- [ ] Go to: http://YOUR_IP/phpmyadmin
- [ ] Login successful
- [ ] See your database!

---

### **Common Issues & Fixes:**

**Problem: Can't connect to database from other PC**

```bash
# Solution 1: Check firewall
# Windows Defender → Firewall → Allow MySQL

# Solution 2: Check MySQL is running
# Services → MySQL80 → Right-click → Start

# Solution 3: Test your IP
# On your PC: cmd → ipconfig
# Make sure you have correct IP (not 127.0.0.1)
```

**Problem: Login fails on other PC**

```
Check:
- Username: root
- Password: (correct MySQL password)
- Host: YOUR_PC_IP (not localhost)
- Port: 3306
```

---

## Option D: Cloud Database (Advanced)

If you want worldwide access (not just local network):

1. Use AWS RDS, Azure, or Digital Ocean
2. Create MySQL database in cloud
3. Access from ANY device ANYWHERE
4. More secure and professional

---

### **My Recommendation for Your Project:**

✅ **Use MySQL + phpMyAdmin**  
✅ **Setup network access (Option C)**  
✅ **All team members can see same database**  
✅ **Perfect for collaboration**  
✅ **Professional setup**

---

### **Option C: Use phpMyAdmin** (Visual Interface)

1. Download phpMyAdmin: https://www.phpmyadmin.net/
2. Extract to a folder
3. Open in browser: `http://localhost/phpmyadmin`
4. Login with your MySQL credentials
5. View and edit data visually

---

## Troubleshooting

### **Problem: "php command not found"**

**Solution:** PHP not installed or not in PATH
```bash
# Check if installed:
php --version

# If not:
# Download from https://www.php.net/downloads
# Install and restart terminal
```

---

### **Problem: "composer command not found"**

**Solution:** Composer not installed
```bash
# Check if installed:
composer --version

# If not:
# Download from https://getcomposer.org/download/
# Install and restart terminal
```

---

### **Problem: "git command not found"**

**Solution:** Git not installed
```bash
# Install from https://git-scm.com/download
# Restart terminal
```

---

### **Problem: "Cannot find module 'vendor'"**

**Solution:** Dependencies not installed
```bash
composer install
```

---

### **Problem: "Database connection error"**

**Solution:** Database not created
```bash
# Create database:
touch database/database.sqlite

# Run migrations:
php artisan migrate
```

---

### **Problem: "Login doesn't work"**

**Solution:** Database not seeded
```bash
php artisan db:seed
```

---

### **Problem: "Port 8000 already in use"**

**Solution:** Use different port
```bash
php artisan serve --port=8001
# Then visit: http://localhost:8001
```

---

### **Problem: "The provided credentials do not match"**

**Solution:** Make sure you seeded the database
```bash
php artisan db:seed
```

Then use:
- Username: `admin`
- Password: `password`

---

## Demo Accounts

After seeding, you can login with these accounts:

### **Admin Account** 👑
```
Username: admin
Password: password
Access: Full system access
```

### **Project Manager** 📊
```
Username: sarah (or michael, emma)
Password: password
Access: Project management
```

### **Team Member** 👥
```
Username: alex (or jessica, david, etc.)
Password: password
Access: Task management
```

---

## Quick Reference Commands

**Copy and paste these in your terminal:**

```bash
# Clone project
git clone https://github.com/ktadena547638-create/IT9aL.git
cd IT9aL/task-management-system

# Setup
composer install
copy .env.example .env  (or: cp .env.example .env on Mac/Linux)
php artisan key:generate
type nul > database/database.sqlite  (or: touch database/database.sqlite on Mac/Linux)

# Initialize
php artisan migrate
php artisan db:seed

# Run
php artisan serve
```

Then open browser to: **http://localhost:8000**

---

## Common Tasks

### **Stop the Server**
```bash
Press Ctrl + C in the terminal
```

### **Start Server Again**
```bash
php artisan serve
```

### **Clear Cache**
```bash
php artisan cache:clear
```

### **Reset Database**
```bash
php artisan migrate:rollback
php artisan migrate
php artisan db:seed
```

---

## Next Steps

Once running, you can:

✅ Create projects  
✅ Add tasks  
✅ Assign team members  
✅ Track progress  
✅ View activity logs  
✅ Manage users  
✅ Generate reports  

---

## Getting Help

If something goes wrong:

1. **Check the error message** - It usually tells you what's wrong
2. **Google the error** - Paste the error in Google
3. **Check Prerequisites** - Make sure PHP, Composer, Git are installed
4. **Run commands in order** - Don't skip steps
5. **Make sure paths are correct** - Use `cd` to navigate properly

---

## Quick Setup Checklist

Before you start, make sure you have:

- [ ] PHP 8.2+ installed
- [ ] Composer installed
- [ ] Git installed
- [ ] Text editor (VS Code, Notepad++, etc.)
- [ ] Internet connection
- [ ] Admin access to your computer

---

## You're All Set! 🎉

Now you can:
- Run TaskFlow on any device
- Share with team members
- Access the database
- Manage your tasks
- Collaborate on projects

**Happy tasking!** 🚀

---

*Last Updated: May 7, 2026*  
*For the latest version, visit: https://github.com/ktadena547638-create/IT9aL*
