# School Banking System - Setup Guide

This is a simple school banking system that allows students to manage their payments and administrators to manage student accounts.

## Prerequisites

1. XAMPP (or similar local server stack)
   - Download from: https://www.apachefriends.org/
   - Install with default settings

## Installation Steps

### 1. Set Up XAMPP

1. Install XAMPP on your machine
2. Start XAMPP Control Panel
3. Start Apache and MySQL services
   - Click "Start" next to Apache
   - Click "Start" next to MySQL

### 2. Project Setup

1. Navigate to XAMPP's htdocs directory:

   - Windows: `C:\xampp\htdocs`
   - Mac: `/Applications/XAMPP/xamppfiles/htdocs`
   - Linux: `/opt/lampp/htdocs`

2. Create a new folder called `self_service_system`

3. Copy all project files into this folder:
   - `index.html`
   - `login.php`
   - `db.php`
   - `create_admin.php`
   - `admin_dashboard.php`
   - `student_dashboard.php`

### 3. Database Setup

1. Open your web browser and go to: `http://localhost/phpmyadmin`

2. Create a new database:

   - Click "New" on the left sidebar
   - Enter database name: `school_banking_system`
   - Click "Create"

3. Create the users table:

   ```sql
   CREATE TABLE users (
       id INT AUTO_INCREMENT PRIMARY KEY,
       username VARCHAR(50) UNIQUE NOT NULL,
       password VARCHAR(255) NOT NULL,
       role ENUM('admin', 'student') NOT NULL
   );
   ```

4. Create the admin user:
   - Go to: `http://localhost/self_service_system/create_admin.php`
   - This will create an admin account with:
     - Username: `admin`
     - Password: `admin123`

### 4. Accessing the System

1. Open your web browser and go to:
   `http://localhost/self_service_system/`

2. Login with admin credentials:
   - Username: `admin`
   - Password: `admin123`

## File Structure

- `index.html` - Login page
- `login.php` - Handles login authentication
- `db.php` - Database connection configuration
- `create_admin.php` - Creates initial admin user
- `admin_dashboard.php` - Admin interface
- `student_dashboard.php` - Student interface

## Features

### Admin Features

- Create student accounts
- Manage student fees
- View all transactions

### Student Features

- Check balance
- View payment history
- Make payments

## Troubleshooting

1. If you see a blank page:

   - Check if Apache and MySQL are running in XAMPP
   - Verify file permissions
   - Check error logs in XAMPP

2. If login fails:

   - Verify database connection in `db.php`
   - Check if the users table exists
   - Ensure admin user was created

3. If you get database connection errors:
   - Verify MySQL is running
   - Check database name in `db.php`
   - Ensure database user credentials are correct

## Security Notes

- This is a basic implementation for demonstration
- In production:
  - Use HTTPS
  - Implement proper password policies
  - Add input validation
  - Use environment variables for sensitive data
  - Implement proper session management
  - Add CSRF protection

## Support

For any issues or questions, please contact the system administrator.
