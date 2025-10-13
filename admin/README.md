# Admin Web Panel

## Overview

The Admin Web Panel provides a web-based interface for managing the Attendance Tracker application.

## Features

### Dashboard

- View statistics (users, events, attendance, announcements)
- Recent activity overview
- Quick access to all modules

### Events Management

- View all events
- Add new events with QR codes
- Edit existing events
- Delete events
- Filter by event type

### Attendance Records

- View all attendance records
- Filter by user, event, date range
- Export attendance data
- View attendance statistics

### Announcements Management

- Create new announcements
- Pin important announcements
- Delete announcements
- View all announcements

### Reflections Management

- Add spiritual reflections
- Categorize reflections
- Add images to reflections
- Delete reflections

## Installation

### Step 1: Copy to Web Server

Copy the `admin` folder to your XAMPP htdocs directory:

```
D:\xampp\htdocs\dashboard\attendance_api\admin\
```

### Step 2: Access the Panel

Open in your browser:

```
http://127.0.0.1:8080/dashboard/attendance_api/admin/
```

### Step 3: Authentication

When prompted, enter your authentication token. You can get this by logging in through the mobile app or API.

For testing, you can use the admin account:

- Email: admin@church.com
- Password: admin123

## Configuration

### Update API URL

If your API is on a different server or port, update the API_BASE_URL in `js/admin.js`:

```javascript
const API_BASE_URL = "http://your-server:port/api";
```

## Usage

### Adding an Event

1. Click on the "Events" tab
2. Click "+ Add Event" button
3. Fill in the event details:
   - Name (required)
   - Type (mass, tasbeha, meeting, activity)
   - Date and time
   - Barcode/QR code
   - Description (optional)
   - Location (optional)
4. Click "Save Event"

### Adding an Announcement

1. Click on the "Announcements" tab
2. Click "+ Add Announcement" button
3. Enter:
   - Title (required)
   - Content (required)
   - Check "Pin this announcement" if important
4. Click "Save Announcement"

### Adding a Reflection

1. Click on the "Reflections" tab
2. Click "+ Add Reflection" button
3. Enter:
   - Title (required)
   - Content (required)
   - Category (Prayer, Bible Study, Saints, etc.)
   - Image URL (optional)
4. Click "Save Reflection"

### Viewing Attendance

1. Click on the "Attendance" tab
2. View all attendance records
3. Use filters to narrow down results
4. Click "Export" to download data (requires implementation)

## Security Notes

⚠️ **Important Security Considerations:**

1. **Authentication**: Currently uses a simple token-based authentication. For production:

   - Implement proper login page
   - Use secure session management
   - Add CSRF protection

2. **Access Control**:

   - Only admin users should access this panel
   - Implement role-based permissions

3. **HTTPS**:

   - Use HTTPS in production
   - Never transmit credentials over HTTP

4. **API Security**:
   - Validate all inputs on the server
   - Use prepared statements (already implemented)
   - Rate limit API requests

## Troubleshooting

### Cannot access the panel

- Check that XAMPP Apache is running
- Verify the correct URL (check port number)
- Clear browser cache

### API requests failing

- Check API_BASE_URL in admin.js
- Verify API endpoints are working
- Check browser console for errors
- Ensure authentication token is valid

### Modals not closing

- Click outside the modal
- Press the X button
- Check browser console for JavaScript errors

## Browser Compatibility

Tested on:

- Chrome 90+
- Firefox 88+
- Edge 90+
- Safari 14+

## Future Enhancements

Planned features:

- User management interface
- Advanced filtering and search
- Real-time notifications
- Bulk operations
- Custom reports
- Data visualization (charts/graphs)
- Email notifications
- Backup and restore

## Support

For issues or questions:

1. Check the browser console for errors
2. Verify API endpoints are working
3. Review the main project README.md
4. Check XAMPP error logs

## File Structure

```
admin/
├── css/
│   └── admin.css          # Stylesheet
├── js/
│   └── admin.js           # JavaScript logic
├── index.html             # Main panel page
└── README.md             # This file
```

## API Endpoints Used

The admin panel interacts with these API endpoints:

- GET `/get_events.php` - Retrieve events
- GET `/get_attendance.php` - Retrieve attendance
- GET `/get_announcements.php` - Retrieve announcements
- POST `/add_announcement.php` - Add announcement
- GET `/get_reflections.php` - Retrieve reflections
- POST `/add_reflection.php` - Add reflection

## Notes

- The panel is responsive and works on tablets
- All data is fetched in real-time from the API
- Changes are immediately reflected in the mobile app
- Authentication token is stored in localStorage
- All forms include validation

---

**Admin Panel Version 1.0**  
_Part of the Attendance Tracker System_
