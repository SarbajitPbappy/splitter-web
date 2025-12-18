# Splitter Web Application

A full-stack web application for expense splitting and personal finance tracking, built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- User authentication with JWT tokens
- Group management (Trip and Bachelor Mess groups)
- Expense tracking with multiple split types (Equal, Unequal, Shares)
- Meal tracking for Bachelor Mess
- Analytics and settlement calculations
- PDF report generation

## Project Structure

```
splitter-web/
├── backend/              # PHP backend API
│   ├── api/             # REST API endpoints
│   ├── classes/         # PHP classes
│   ├── config/          # Configuration files
│   ├── includes/        # Shared includes
│   └── uploads/         # File uploads
├── frontend/            # Frontend application
│   ├── assets/          # CSS, JS, images
│   └── *.html           # HTML pages
├── database/            # Database schema
└── composer.json        # PHP dependencies
```

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache web server (or Nginx)
- Composer

### Installation

1. **Clone the repository**
   ```bash
   cd /Volumes/Sarbajit/University/WEB
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up the database**
   - See [DATABASE_SETUP.md](DATABASE_SETUP.md) for detailed step-by-step instructions
   - Quick commands:
     ```bash
     # Access MySQL
     mysql -u root -p
     
     # In MySQL prompt:
     CREATE DATABASE IF NOT EXISTS splitter_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     USE splitter_db;
     exit;
     
     # Import schema from terminal
     mysql -u root -p splitter_db < database/schema.sql
     ```

4. **Configure the application**
   - Edit `backend/config/config.php` and `backend/config/database.php`
   - Update database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'splitter_db');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```
   - Set a secure JWT secret key in `backend/config/config.php`

5. **Set file permissions**
   ```bash
   chmod 755 backend/uploads/receipts
   ```

6. **Configure web server**
   - Point document root to the project directory
   - Ensure mod_rewrite is enabled (for .htaccess)
   - Configure virtual host if needed

### Configuration

#### Database Configuration

Edit `backend/config/database.php` with your MySQL credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'splitter_db');
define('DB_USER', 'root');
define('DB_PASS', 'root1234');
```

#### JWT Secret Key

Edit `backend/config/config.php` and set a strong secret key:

```php
define('JWT_SECRET', 'your-very-long-random-secret-key-here');
```

For production, use environment variables:
```php
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'default-secret');
```

### Running the Application

1. Start your web server (Apache/Nginx)
2. Start MySQL server
3. Access the application in your browser:
   - http://localhost/frontend/index.html
   - Or configure a virtual host and use your domain

### API Endpoints

All API endpoints are located under `/backend/api/`:

- **Authentication**: `/backend/api/auth/`
  - POST `/register.php` - User registration
  - POST `/login.php` - User login
  - POST `/logout.php` - User logout
  - GET `/verify_token.php` - Token verification

- **Users**: `/backend/api/users/`
  - GET `/get_profile.php` - Get user profile
  - PUT `/update_profile.php` - Update profile

- **Groups**: `/backend/api/groups/`
  - POST `/create.php` - Create group
  - GET `/list.php` - List user groups
  - GET `/get.php` - Get group details
  - POST `/invite.php` - Invite member
  - PUT `/close.php` - Close group

- **Expenses**: `/backend/api/expenses/`
  - POST `/create.php` - Create expense
  - GET `/list.php` - List expenses
  - GET `/get.php` - Get expense details
  - DELETE `/delete.php` - Delete expense

- **Meals**: `/backend/api/meals/`
  - POST `/add.php` - Add meal
  - GET `/list.php` - List meals
  - GET `/calculate.php` - Calculate meal costs

- **Analytics**: `/backend/api/analytics/`
  - GET `/dashboard.php` - Dashboard analytics
  - GET `/settlement.php` - Settlement calculations
  - GET `/reports.php` - Report data

- **PDF**: `/backend/api/pdf/`
  - GET `/generate.php` - Generate PDF report

### Security Considerations

- All passwords are hashed using PHP's `password_hash()` function
- SQL queries use prepared statements to prevent SQL injection
- Input validation and sanitization on all user inputs
- JWT tokens for authentication
- File upload validation for receipts
- CORS headers configured

### Troubleshooting

**Database connection errors:**
- Check database credentials in `backend/config/database.php`
- Ensure MySQL server is running
- Verify database exists

**JWT errors:**
- Ensure JWT_SECRET is set in config
- Check that firebase/php-jwt is installed via composer

**File upload errors:**
- Check directory permissions on `backend/uploads/receipts`
- Verify upload_max_filesize and post_max_size in php.ini

**API returns 500 errors:**
- Check PHP error logs
- Enable error display in `backend/config/config.php` (development only)
- Verify all dependencies are installed

### Development Notes

- The application uses vanilla JavaScript (no frameworks)
- Chart.js is loaded via CDN for analytics charts
- Responsive design using CSS Grid and Flexbox
- All API responses follow JSON format: `{"success": true/false, "data": {}, "message": ""}`

### License

MIT License

