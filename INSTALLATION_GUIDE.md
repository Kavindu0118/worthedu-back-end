# Installation Guide - Learning LMS

A comprehensive guide to installing and setting up the Learning LMS application with Laravel backend and React frontend.

## Table of Contents
- [Prerequisites](#prerequisites)
- [System Requirements](#system-requirements)
- [Installation Steps](#installation-steps)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have the following software installed on your system:

### Required Software

1. **PHP 8.2 or higher**
   - Download: https://www.php.net/downloads
   - For Windows: https://windows.php.net/download/
   - Verify installation: `php -v`

2. **Composer** (PHP Dependency Manager)
   - Download: https://getcomposer.org/download/
   - Verify installation: `composer -V`

3. **Node.js 18.x or higher** (includes npm)
   - Download: https://nodejs.org/
   - Verify installation: 
     ```bash
     node -v
     npm -v
     ```

4. **Database** (Choose one)
   - MySQL 8.0+ (recommended)
   - PostgreSQL 13+
   - SQLite (for development)

5. **Git**
   - Download: https://git-scm.com/downloads
   - Verify installation: `git --version`

### Optional (Recommended)

- **WAMP/XAMPP/MAMP** (for local web server environment)
- **MySQL Workbench** (for database management)
- **Postman** (for API testing)

---

## System Requirements

### Minimum Requirements
- **OS**: Windows 10/11, macOS 10.15+, or Linux (Ubuntu 20.04+)
- **RAM**: 4 GB
- **Storage**: 2 GB free space
- **Processor**: Dual-core 2.0 GHz

### Recommended Requirements
- **RAM**: 8 GB or higher
- **Storage**: 5 GB free space
- **Processor**: Quad-core 2.5 GHz or higher

---

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/Kavindu0118/worthedu-back-end.git learning-lms
cd learning-lms
```

Or if you already have the project:
```bash
cd c:\wamp64\www\learning-lms
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install all required Laravel packages and dependencies.

**Common Issues:**
- If you encounter memory limit errors, run: `php -d memory_limit=-1 $(which composer) install`
- Make sure all PHP extensions are enabled (see Configuration section)

### 3. Install Node.js Dependencies

```bash
npm install
```

This will install Vite, Tailwind CSS, Axios, and other frontend dependencies.

### 4. Environment Configuration

#### Create Environment File

Copy the example environment file:

**Windows:**
```bash
copy .env.example .env
```

**Mac/Linux:**
```bash
cp .env.example .env
```

#### Configure Environment Variables

Open the `.env` file and update the following settings:

```env
# Application Settings
APP_NAME="Learning LMS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration (MySQL Example)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=learning_lms
DB_USERNAME=root
DB_PASSWORD=your_password

# For SQLite (Development)
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Stripe Configuration (if using payments)
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

This will generate a unique encryption key for your application.

### 6. Database Setup

#### Create Database

**For MySQL:**
```sql
CREATE DATABASE learning_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

You can create this using:
- MySQL Workbench
- phpMyAdmin (included with WAMP/XAMPP)
- Command line: `mysql -u root -p`

**For SQLite:**
```bash
touch database/database.sqlite
```

#### Run Migrations

```bash
php artisan migrate
```

This will create all necessary database tables.

#### Seed Database (Optional)

```bash
php artisan db:seed
```

This will populate the database with sample data (if seeders are configured).

### 7. Storage Link

Create a symbolic link for file storage:

```bash
php artisan storage:link
```

This allows public access to uploaded files.

### 8. Set Permissions (Mac/Linux)

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**For Windows with WAMP:**
- Ensure the `storage` and `bootstrap/cache` directories have write permissions
- Right-click folders → Properties → Security → Edit

---

## Configuration

### PHP Configuration

Ensure the following PHP extensions are enabled in your `php.ini` file:

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=curl
extension=fileinfo
extension=tokenizer
extension=xml
extension=ctype
extension=json
extension=bcmath
extension=gd
extension=zip
```

**To find your php.ini file:**
```bash
php --ini
```

### Node.js Configuration

The project uses Vite for frontend asset bundling. Configuration is in `vite.config.js`.

### Stripe Configuration (Optional)

If you're using payment features:

1. Sign up at https://stripe.com
2. Get your API keys from the Dashboard
3. Add keys to `.env` file
4. Set up webhook endpoint for payment notifications

---

## Running the Application

### Development Mode

You need to run both the Laravel backend and Vite frontend server.

#### Option 1: Using Separate Terminals

**Terminal 1 - Laravel Backend:**
```bash
php artisan serve
```
The backend will run at: http://localhost:8000

**Terminal 2 - Vite Frontend:**
```bash
npm run dev
```
The frontend assets will be served with hot module replacement.

#### Option 2: Using Concurrently (Recommended)

Add this script to `package.json`:
```json
"scripts": {
    "dev": "vite",
    "build": "vite build",
    "serve": "concurrently \"php artisan serve\" \"npm run dev\""
}
```

Then run:
```bash
npm run serve
```

### Production Build

#### Build Frontend Assets

```bash
npm run build
```

This compiles and minifies assets for production.

#### Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Set Environment to Production

Update `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

#### Run with Web Server

Configure your web server (Apache/Nginx) to point to the `public` directory.

**Apache Example (.htaccess is included):**
```apache
DocumentRoot /path/to/learning-lms/public
```

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/learning-lms/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Troubleshooting

### Common Issues and Solutions

#### 1. "php artisan serve" fails

**Error:** Address already in use

**Solution:**
```bash
# Use a different port
php artisan serve --port=8001
```

#### 2. Database Connection Error

**Error:** SQLSTATE[HY000] [1045] Access denied for user

**Solution:**
- Verify database credentials in `.env`
- Ensure MySQL service is running
- Test connection: `mysql -u root -p`

#### 3. Vite/Node Errors

**Error:** Cannot find module 'vite'

**Solution:**
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

#### 4. Permission Denied (Linux/Mac)

**Solution:**
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
```

#### 5. Class not found errors

**Solution:**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
php artisan config:clear
```

#### 6. Assets not loading

**Solution:**
```bash
# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Rebuild assets
npm run build
```

#### 7. Stripe Integration Issues

**Solution:**
- Verify API keys in `.env`
- Check webhook endpoint is accessible
- Test in Stripe Dashboard test mode first

### Clearing Cache

```bash
# Clear all Laravel caches
php artisan optimize:clear

# Or individually:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Issues

```bash
# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback
```

### Debugging Tips

1. **Enable Debug Mode:**
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

2. **Check Laravel Logs:**
   - Location: `storage/logs/laravel.log`

3. **Check PHP Error Log:**
   - Location varies by system
   - Check `php.ini` for `error_log` setting

4. **Test API Endpoints:**
   - Use Postman or curl
   - Check `routes/api.php` for available routes

5. **Database Query Debugging:**
   ```php
   DB::enableQueryLog();
   // Your query here
   dd(DB::getQueryLog());
   ```

---

## Additional Resources

### Laravel Documentation
- Official Docs: https://laravel.com/docs/12.x
- Laracasts: https://laracasts.com

### Vite Documentation
- Official Docs: https://vitejs.dev
- Laravel Vite: https://laravel.com/docs/12.x/vite

### Tailwind CSS
- Official Docs: https://tailwindcss.com/docs

### Stripe Documentation
- Official Docs: https://stripe.com/docs

---

## Getting Help

If you encounter issues not covered in this guide:

1. Check the `storage/logs/laravel.log` file
2. Review browser console for frontend errors
3. Consult Laravel documentation
4. Check GitHub issues: https://github.com/Kavindu0118/worthedu-back-end/issues

---

## Next Steps

After successful installation:

1. **Review the API Documentation** (if available in `docs/` folder)
2. **Configure User Roles and Permissions**
3. **Set up Email Configuration** (in `.env`)
4. **Configure File Upload Limits** (in `php.ini` and Laravel config)
5. **Set up Backup System** (consider Laravel Backup package)
6. **Configure Queue Workers** (for background jobs)
7. **Set up Monitoring and Logging** (for production)

---

## Development Workflow

### Starting Development

```bash
# 1. Start Laravel server
php artisan serve

# 2. In another terminal, start Vite
npm run dev

# 3. Access the application
# Open: http://localhost:8000
```

### Before Committing Code

```bash
# Run tests
php artisan test

# Check code style (if configured)
./vendor/bin/pint

# Build production assets
npm run build
```

---

**Version:** 1.0.0  
**Last Updated:** January 31, 2026  
**Maintainer:** Kavindu0118
