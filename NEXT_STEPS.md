# Next Steps - Application Setup Guide

Database is connected! Follow these steps to complete the setup:

## Step 1: Install PHP Dependencies

Install the required PHP packages using Composer:

```bash
cd /Volumes/Sarbajit/University/WEB
composer install
```

This will install:
- `firebase/php-jwt` - For JWT token management
- `tecnickcom/tcpdf` - For PDF generation

**If you don't have Composer installed:**
```bash
# Install Composer (macOS)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Or via Homebrew
brew install composer
```

## Step 2: Set JWT Secret Key

Edit `backend/config/config.php` and set a secure JWT secret key:

```php
define('JWT_SECRET', 'your-very-long-random-secret-key-change-this-in-production');
```

**Generate a random secret:**
```bash
# Generate a random 32-character secret
openssl rand -hex 32
```

Or use this online tool: https://www.lastpass.com/features/password-generator

**Important:** Use a long, random string for production!

## Step 3: Set File Permissions

Make sure the uploads directory is writable:

```bash
chmod 755 backend/uploads/receipts
```

Or if that doesn't work:
```bash
chmod -R 775 backend/uploads
```

## Step 4: Verify Database Configuration

Double-check your database settings in `backend/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'splitter_db');
define('DB_USER', 'root');
define('DB_PASS', 'root1234');  // Your MySQL password
```

## Step 5: Start Your Web Server

### Option A: Using PHP Built-in Server (Development)

```bash
cd /Volumes/Sarbajit/University/WEB
php -S localhost:8000
```

Then access: `http://localhost:8000/frontend/index.html`

### Option B: Using Apache/MAMP (macOS)

If you have MAMP or Apache installed:

1. **MAMP:**
   - Start MAMP
   - Set Document Root to: `/Volumes/Sarbajit/University/WEB`
   - Access: `http://localhost:8888/frontend/index.html` (or your MAMP port)

2. **Apache:**
   - Configure your virtual host to point to the project directory
   - Or use the default `htdocs` and copy files there

### Option C: Using Nginx

Configure Nginx to serve the project directory.

## Step 6: Test the Application

1. **Open in Browser:**
   ```
   http://localhost:8000/frontend/index.html
   ```
   (Adjust port if using different server)

2. **Register a New User:**
   - Click "Sign up" or go to `/frontend/register.html`
   - Fill in name, email, and password
   - Password must be at least 8 characters with uppercase, lowercase, and number

3. **Login:**
   - Use the credentials you just created
   - You should be redirected to the dashboard

4. **Create a Group:**
   - Click "Create Group"
   - Choose type (Trip or Bachelor Mess)
   - Add group name and description

## Step 7: Quick Test Checklist

- [ ] Database connection works
- [ ] Composer dependencies installed
- [ ] JWT secret key is set
- [ ] Web server is running
- [ ] Can access login page
- [ ] Can register a new user
- [ ] Can login successfully
- [ ] Can create a group
- [ ] Can add an expense

## Troubleshooting

### Error: "Class 'Firebase\JWT\JWT' not found"
**Solution:** Run `composer install` to install dependencies

### Error: "Database connection failed"
**Solution:** 
- Check MySQL is running
- Verify credentials in `backend/config/database.php`
- Test connection: `mysql -u root -p splitter_db`

### Error: "JWT secret key not set"
**Solution:** Set JWT_SECRET in `backend/config/config.php`

### Error: "Permission denied" on file uploads
**Solution:** 
```bash
chmod -R 775 backend/uploads
# Or
chmod 755 backend/uploads/receipts
```

### Pages show "404 Not Found"
**Solution:**
- Check your web server document root points to the project directory
- Ensure `.htaccess` file exists (for Apache)
- Try accessing files directly: `http://localhost:8000/frontend/login.html`

### CORS errors in browser console
**Solution:** CORS headers are already configured in `backend/config/config.php`. If issues persist, check your web server configuration.

## What's Next?

Once everything is working:

1. ✅ Create your first user account
2. ✅ Create a group (Trip or Bachelor Mess)
3. ✅ Add expenses with different split types
4. ✅ Invite members to your group
5. ✅ Track meals (for Bachelor Mess groups)
6. ✅ View analytics and settlements
7. ✅ Generate PDF reports

## Development Tips

- **View PHP Errors:** Check `backend/config/config.php` - error display is enabled for development
- **API Testing:** You can test API endpoints directly:
  ```bash
  curl -X POST http://localhost:8000/backend/api/auth/register.php \
    -H "Content-Type: application/json" \
    -d '{"name":"Test User","email":"test@example.com","password":"Test1234"}'
  ```
- **Database Queries:** Use phpMyAdmin or MySQL Workbench to inspect your data

## Production Deployment Checklist

Before deploying to production:

- [ ] Set `display_errors = 0` in `backend/config/config.php`
- [ ] Use environment variables for sensitive data (DB credentials, JWT secret)
- [ ] Use HTTPS
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Configure proper CORS origins (not `*`)
- [ ] Set up database backups
- [ ] Review and harden security settings
- [ ] Set up error logging
- [ ] Configure proper PHP settings (memory_limit, max_execution_time, etc.)

## Need Help?

- Check the main README: `README_WEB.md`
- Check database setup: `DATABASE_SETUP.md`
- Review error logs in your web server
- Check browser console for JavaScript errors
- Check PHP error logs

