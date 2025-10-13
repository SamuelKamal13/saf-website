# Database Setup Instructions

## Prerequisites

- MySQL 5.7+ or MariaDB
- XAMPP, WAMP, or standalone MySQL server

## Setup Steps

### 1. Start MySQL Server

If using XAMPP/WAMP:

- Open XAMPP/WAMP Control Panel
- Start Apache and MySQL services

### 2. Import Database Schema

There are two ways to import the database:

#### Option A: Using phpMyAdmin

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click on "Import" tab
3. Choose the file: `database/schema.sql`
4. Click "Go" to execute

#### Option B: Using MySQL Command Line

```bash
mysql -u root -p < database/schema.sql
```

### 3. Verify Database Creation

1. Login to MySQL:
   ```bash
   mysql -u root -p
   ```
2. Check database:
   ```sql
   SHOW DATABASES;
   USE attendance_app;
   SHOW TABLES;
   ```

## Database Structure

### Tables Created:

1. **users** - User accounts and profiles
2. **events** - Church events (Mass, Tasbeha, Meeting, Activities)
3. **attendance** - Attendance records
4. **announcements** - Church announcements
5. **reflections** - Spiritual reflections/meditations
6. **sessions** - Authentication tokens

### Default Credentials:

- **Email:** admin@church.com
- **Password:** admin123
- **Role:** admin

⚠️ **Important:** Change the default admin password after first login!

## Views

The schema includes two views for reporting:

- `v_user_attendance_stats` - User attendance statistics
- `v_event_attendance_summary` - Event attendance summaries

## Sample Data

The schema includes sample data for:

- 1 Admin user
- 4 Sample events
- 1 Sample announcement
- 1 Sample reflection

## Database Configuration

Update the database connection settings in your PHP files:

- **Host:** localhost
- **Database:** attendance_app
- **Username:** root
- **Password:** (your MySQL password)

## Backup Recommendations

Regular backup is recommended:

```bash
mysqldump -u root -p attendance_app > backup_$(date +%Y%m%d).sql
```

## Troubleshooting

### Connection Issues

- Verify MySQL is running
- Check username and password
- Ensure port 3306 is not blocked

### Import Errors

- Check MySQL version compatibility
- Verify file encoding (UTF-8)
- Check for syntax errors in SQL file

## Security Notes

1. Use strong passwords for all users
2. Change default admin credentials
3. Use prepared statements in PHP
4. Enable HTTPS for production
5. Regularly update MySQL/MariaDB
