# Admin Panel Login System

## 🔐 Authentication Setup Complete

The admin panel now has a complete authentication system using the existing PHP login API.

## 📂 Files

- **`login.html`** - Login page with Coptic theme
- **`index.html`** - Main admin panel (requires authentication)
- **`js/admin.js`** - JavaScript with authentication checks

## 🚀 How to Access

### 1. **Open the Login Page**

Navigate to: `http://localhost/admin/login.html` (or your local path)

### 2. **Login Credentials**

You need to login with an existing user account from your database.

**Default Admin Account** (if you created one):

- Email: `admin@example.com`
- Password: (your password)

**Or any user with admin/servant role**

### 3. **First Time Setup**

If you don't have an admin account yet, you need to create one:

#### Option A: Register through the Flutter app

1. Open your Flutter app
2. Register a new user
3. Manually update the database to make them an admin:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'your-email@example.com';
```

#### Option B: Create directly in database

Run this SQL in your database (replace with your details):

```sql
INSERT INTO users (name, email, password, role, barcode_id, is_active)
VALUES (
    'Admin User',
    'admin@example.com',
    MD5('your-password'),  -- Change this to your password
    'admin',
    'ADMIN_001',
    1
);
```

**Note:** The password is hashed using MD5 for legacy support. For better security, you can use bcrypt:

```sql
-- Using bcrypt (recommended)
INSERT INTO users (name, email, password, role, barcode_id, is_active)
VALUES (
    'Admin User',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'admin',
    'ADMIN_001',
    1
);
```

## ✅ Features

### Login Page

- ✅ Arabic interface with RTL support
- ✅ Coptic Orthodox theme (Red & Gold)
- ✅ Email and password validation
- ✅ "Remember Me" option
- ✅ Loading spinner during login
- ✅ Success/Error messages in Arabic
- ✅ Auto-redirect if already logged in
- ✅ Responsive design

### Admin Panel

- ✅ Automatic redirect to login if not authenticated
- ✅ Token verification on page load
- ✅ Display admin name in header
- ✅ Auto-logout on token expiration
- ✅ Secure API requests with Bearer token
- ✅ Session management

## 🔒 Security Features

1. **JWT Token Authentication**

   - Token generated on successful login
   - Token stored in localStorage
   - Token sent with every API request
   - Token validated on server side

2. **Session Management**

   - Token expires after configured hours (default: 24 hours)
   - Old tokens deleted on new login
   - Auto-logout on token expiration

3. **Role-Based Access**

   - Admin: Full access to all features
   - Servant: Can view and manage data
   - Member: Limited to own data

4. **Password Security**
   - Supports both MD5 (legacy) and bcrypt
   - Server-side validation
   - No plain text passwords stored

## 📊 User Roles

### Admin (`role = 'admin'`)

- ✅ View all users
- ✅ View all events
- ✅ View all attendance
- ✅ View all announcements
- ✅ View all reflections
- ✅ Full CRUD operations

### Servant (`role = 'servant'`)

- ✅ View all users
- ✅ View all events
- ✅ View all attendance
- ✅ Limited CRUD operations

### Member (`role = 'member'`)

- ✅ View only own data
- ❌ Cannot access admin panel

## 🔄 Workflow

```
1. User opens admin panel (index.html)
   ↓
2. Check if token exists in localStorage
   ↓
3. If NO token → Redirect to login.html
   ↓
4. User enters credentials
   ↓
5. POST request to /api/login.php
   ↓
6. Server validates credentials
   ↓
7. Server generates token (24-hour expiry)
   ↓
8. Token stored in localStorage
   ↓
9. Redirect to admin panel
   ↓
10. Admin panel loads with authenticated API calls
    ↓
11. All API requests include: Authorization: Bearer {token}
    ↓
12. Server validates token on each request
```

## 🛠️ Troubleshooting

### Issue: "Authentication required"

**Solution:** Make sure you're logged in. The token might have expired.

### Issue: "Invalid or expired token"

**Solution:** Your session expired. Click logout and login again.

### Issue: Can't login with correct credentials

**Solution:**

1. Check if user exists in database
2. Verify password is correct
3. Check if `is_active = 1`
4. Verify API URL in login.html matches your server

### Issue: Redirects to login immediately

**Solution:**

1. Clear browser cache
2. Clear localStorage: `localStorage.clear()` in browser console
3. Try hard refresh: Ctrl + F5

### Issue: Database connection error

**Solution:**

1. Make sure MySQL/MariaDB is running
2. Check XAMPP Apache and MySQL are both started
3. Verify database credentials in `api/config/database.php`

## 🔧 Configuration

### Token Expiry

To change token expiration time, edit `api/config/database.php`:

```php
define('TOKEN_EXPIRY_HOURS', 24); // Change to desired hours
```

### API Base URL

If your API is at a different location, update in both files:

**login.html:**

```javascript
const API_BASE_URL = "http://127.0.0.1:8080/dashboard/attendance_api/api";
```

**admin.js:**

```javascript
const API_BASE_URL = "http://127.0.0.1:8080/dashboard/attendance_api/api";
```

## 📝 Testing

### Test Login Flow

1. Open `login.html`
2. Enter credentials
3. Click "تسجيل الدخول"
4. Should see "تم تسجيل الدخول بنجاح!"
5. Should redirect to `index.html`
6. Should see admin name in header
7. Should see dashboard with data

### Test Logout

1. Click "تسجيل الخروج" button
2. Should see confirmation dialog
3. Click OK
4. Should redirect to `login.html`
5. localStorage should be cleared

### Test Session Expiry

1. Login successfully
2. In browser console: `localStorage.setItem("admin_token", "invalid_token")`
3. Refresh page
4. Should be redirected to login

## 🎨 Customization

### Change Theme Colors

Edit the CSS variables in `login.html` or `admin.css`:

```css
/* Coptic Red */
background: linear-gradient(135deg, #8b0000 0%, #800020 100%);

/* Coptic Gold */
color: #d4af37;
```

### Change Logo

Replace the ⛪ emoji in `login.html`:

```html
<div class="login-logo">⛪</div>
```

## 🔐 Production Deployment

Before deploying to production:

1. ✅ Use HTTPS (SSL certificate)
2. ✅ Change default passwords
3. ✅ Update API_BASE_URL to production domain
4. ✅ Enable CORS properly
5. ✅ Set secure cookie flags
6. ✅ Implement rate limiting
7. ✅ Add input sanitization
8. ✅ Enable SQL injection protection
9. ✅ Use environment variables for sensitive data
10. ✅ Regular security audits

## 📞 Support

For issues or questions:

1. Check browser console for errors (F12)
2. Check network tab for API responses
3. Check PHP error logs
4. Verify database connection

---

**System:** Coptic Orthodox Church Attendance System  
**Version:** 2.0 with Authentication  
**Language:** Arabic (العربية)  
**Theme:** Coptic Red & Gold
