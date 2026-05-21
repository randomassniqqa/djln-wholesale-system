# TaskFlow Setup Guide for Another PC

This guide shows how to run the project on a different PC, laptop, or lab machine.

## Recommended option: Docker

If Docker is available, use it. It matches the production-style environment more closely.

1. Clone the repository.
2. Open the project folder.
3. Create a valid `.env` file.
4. Make sure `APP_KEY` and database settings are set.
5. Build and run the container.

Example:

```bash
docker build -t taskflow .
docker run -p 8080:8080 --env-file .env taskflow
```

## Native option: Windows / Linux / laptop setup

### Prerequisites
- PHP 8.2 or newer
- Composer
- Node.js 18+ or 22+
- PostgreSQL, MySQL, or SQLite

### Install steps

1. Clone the project.

```bash
git clone <your-repo-url>
cd task-management-system
```

2. Install PHP packages.

```bash
composer install
```

3. Create the environment file.

```bash
copy .env.example .env
```

4. Generate the app key.

```bash
php artisan key:generate
```

5. Configure your database in `.env`.

For SQLite:
```env
DB_CONNECTION=sqlite
```

For PostgreSQL:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=taskflow
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

For MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=your_password
```

6. Run migrations.

```bash
php artisan migrate
```

7. Seed demo data if needed.

```bash
php artisan db:seed
```

8. Install frontend dependencies and build assets.

```bash
npm install
npm run build
```

9. Start the app.

```bash
php artisan serve
```

10. Open the app in a browser.

```text
http://127.0.0.1:8000
```

## If the app does not load correctly
- Run `php artisan optimize:clear`
- Run `php artisan view:clear`
- Recheck database credentials in `.env`
- Rebuild frontend assets with `npm run build`

## Notes for lab PCs
- Docker is best if installation is allowed.
- If Docker is blocked, SQLite is the fastest fallback.
- Do not reuse the production `.env` on a different machine unless you know the database is reachable.
