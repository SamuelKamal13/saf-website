# Database Setup for Windows (XAMPP/WAMP)

## Method 1: Using phpMyAdmin (Easiest)

### Step 1: Start XAMPP

1. Open XAMPP Control Panel
2. Click "Start" for Apache
3. Click "Start" for MySQL
4. Wait for both to show green "Running" status

### Step 2: Access phpMyAdmin

1. Open your browser
2. Go to: http://localhost/phpmyadmin
3. You should see the phpMyAdmin interface

### Step 3: Import Database

1. Click on "Import" tab at the top
2. Click "Choose File" button
3. Navigate to your project folder:
   ```
   d:\Projects\osret_elkdes_oghostinos\database\schema.sql
   ```
4. Select the `schema.sql` file
5. Scroll down and click "Go" button
6. Wait for success message: "Import has been successfully finished"

### Step 4: Verify Database

1. Click on "attendance_app" in the left sidebar
2. You should see 6 tables:
   - announcements
   - attendance
   - events
   - reflections
   - sessions
   - users
3. Click on "users" table
4. Click "Browse" - you should see the default admin user

### Step 5: Test Default Admin

- Email: admin@church.com
- Password: admin123

---

## Method 2: Using MySQL Command Line (If Available)

### Find MySQL in XAMPP:

```powershell
# Navigate to XAMPP MySQL bin directory
cd C:\xampp\mysql\bin

# Run MySQL command
.\mysql.exe -u root -p

# When prompted, press Enter (default XAMPP has no password)

# Then run:
source d:/Projects/osret_elkdes_oghostinos/database/schema.sql

# Exit MySQL
exit
```

---

## Method 3: Using PowerShell with Full Path

```powershell
# Use full path to MySQL executable
& "C:\xampp\mysql\bin\mysql.exe" -u root -p < d:\Projects\osret_elkdes_oghostinos\database\schema.sql
```

---

## Method 4: Create Database Manually via phpMyAdmin

If import doesn't work, you can create manually:

1. **Create Database:**

   - Go to phpMyAdmin
   - Click "New" in left sidebar
   - Database name: `attendance_app`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

2. **Run SQL Script:**
   - Click on "attendance_app" database
   - Click "SQL" tab
   - Open `database/schema.sql` in a text editor
   - Copy ALL the content
   - Paste into the SQL query box
   - Click "Go"

---

## Troubleshooting

### Issue: phpMyAdmin not loading

**Solution:**

- Make sure Apache and MySQL are running in XAMPP
- Try: http://127.0.0.1/phpmyadmin
- Check if port 80 is not blocked by another program

### Issue: Import file too large

**Solution:**

- Use Method 4 (manual SQL paste)
- Or increase upload size in php.ini

### Issue: MySQL Access Denied

**Solution:**

- Default XAMPP has no password, just use: `-u root` (no `-p`)
- Or in phpMyAdmin, use username: `root`, password: (leave empty)

### Issue: Database already exists

**Solution:**

- Drop existing database first:
  ```sql
  DROP DATABASE IF EXISTS attendance_app;
  ```
- Then import again

---

## Verify Setup is Working

After import, run this SQL query in phpMyAdmin SQL tab:

```sql
-- Check if tables exist
SHOW TABLES FROM attendance_app;

-- Check admin user
SELECT * FROM attendance_app.users WHERE role = 'admin';

-- Check sample data
SELECT COUNT(*) as event_count FROM attendance_app.events;
SELECT COUNT(*) as announcement_count FROM attendance_app.announcements;
```

You should see:

- 6 tables
- 1 admin user
- 4 events
- 1 announcement

---

## Next Steps After Database Setup

1. ✅ Verify database is created
2. ✅ Verify default admin exists
3. ✅ Copy API folder to XAMPP
4. ✅ Test API endpoint
5. ✅ Run Flutter app

---

## Quick Test API After Database Setup

Open browser and go to:

```
http://localhost/attendance_api/api/get_announcements.php
```

You should see JSON response with the welcome announcement.

---

**Need Help?**

- Make sure XAMPP is installed
- MySQL service must be running (green in XAMPP)
- Apache service must be running (green in XAMPP)
