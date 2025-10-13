# Attendance Tracker API Documentation

## Overview

RESTful API for the Attendance and Absence Tracking App.

## Base URL

```
http://localhost/attendance_api/api/
```

## Authentication

Most endpoints require authentication using Bearer token in the header:

```
Authorization: Bearer {token}
```

## API Endpoints

### 1. User Authentication

#### Register User

- **Endpoint:** `POST /register.php`
- **Auth Required:** No
- **Body:**
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "phone": "1234567890" (optional)
  }
  ```
- **Response:** User object with barcode_id

#### Login

- **Endpoint:** `POST /login.php`
- **Auth Required:** No
- **Body:**
  ```json
  {
    "email": "john@example.com",
    "password": "password123"
  }
  ```
- **Response:** User object + authentication token

#### Get User Data

- **Endpoint:** `GET /get_user.php?user_id={id}`
- **Auth Required:** Yes
- **Parameters:**
  - `user_id` (optional, defaults to authenticated user)
- **Response:** User data + attendance statistics

### 2. Attendance Management

#### Record Attendance

- **Endpoint:** `POST /attendance.php`
- **Auth Required:** Yes (or provide user_barcode_id)
- **Body:**
  ```json
  {
    "event_barcode": "MASS_20251012_001",
    "user_barcode_id": "USER_123ABC" (optional if authenticated)
  }
  ```
- **Response:** Attendance record

#### Get Attendance Records

- **Endpoint:** `GET /get_attendance.php`
- **Auth Required:** Yes
- **Parameters:**
  - `user_id` (optional)
  - `event_id` (optional)
  - `from_date` (optional, format: YYYY-MM-DD)
  - `to_date` (optional, format: YYYY-MM-DD)
- **Response:** List of attendance records

### 3. Events

#### Get Events

- **Endpoint:** `GET /get_events.php`
- **Auth Required:** No
- **Parameters:**
  - `type` (optional: mass, tasbeha, meeting, activity)
  - `from_date` (optional)
  - `to_date` (optional)
  - `limit` (optional, default: 100)
- **Response:** List of events

### 4. Announcements

#### Get Announcements

- **Endpoint:** `GET /get_announcements.php`
- **Auth Required:** No
- **Parameters:**
  - `limit` (optional, default: 50)
- **Response:** List of announcements

#### Add Announcement

- **Endpoint:** `POST /add_announcement.php`
- **Auth Required:** Yes (admin or servant only)
- **Body:**
  ```json
  {
    "title": "Important Notice",
    "content": "This is an important announcement",
    "author": "Admin" (optional),
    "is_pinned": false (optional)
  }
  ```
- **Response:** Created announcement

### 5. Reflections

#### Get Reflections

- **Endpoint:** `GET /get_reflections.php`
- **Auth Required:** No
- **Parameters:**
  - `category` (optional)
  - `limit` (optional, default: 50)
- **Response:** List of reflections

#### Add Reflection

- **Endpoint:** `POST /add_reflection.php`
- **Auth Required:** Yes (admin or servant only)
- **Body:**
  ```json
  {
    "title": "Daily Meditation",
    "content": "Reflection content here...",
    "author": "Father John",
    "category": "Faith" (optional),
    "image_url": "https://..." (optional)
  }
  ```
- **Response:** Created reflection

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description"
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict
- `500` - Server Error

## Setup Instructions

### 1. Configure Database

Edit `config/database.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_app');
```

### 2. Place API Folder

Copy the `api` folder to your web server:

- XAMPP: `C:\xampp\htdocs\attendance_api\`
- WAMP: `C:\wamp64\www\attendance_api\`

### 3. Update Flutter App

Update `lib/utils/constants.dart`:

```dart
static const String baseUrl = 'http://localhost/attendance_api/api';
```

### 4. Test API

Use Postman or curl to test endpoints:

```bash
curl -X POST http://localhost/attendance_api/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@church.com","password":"admin123"}'
```

## Security Notes

- Always use HTTPS in production
- Change default admin password
- Implement rate limiting
- Validate all inputs
- Use prepared statements (already implemented)
- Keep PHP and MySQL updated

## Troubleshooting

### CORS Issues

CORS headers are already set in `config/database.php`. If issues persist:

- Check web server configuration
- Ensure headers are not overridden

### Database Connection Errors

- Verify MySQL is running
- Check database credentials
- Ensure database exists
- Check file permissions

### Token Authentication Fails

- Check if token is being sent in header
- Verify token hasn't expired (24 hour default)
- Check sessions table in database
