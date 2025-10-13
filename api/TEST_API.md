# دليل اختبار API - حذف الإعلان

## المشكلة التي تم إصلاحها

كانت المشكلة في:

1. دالة `checkRequestMethod()` في `includes/functions.php` - لم تكن تدعم مصفوفة من الطرق
2. استعلام SQL في `update_announcement.php` - كان يحاول عمل JOIN مع جدول users رغم أن جدول announcements يستخدم حقل `author` نصي

## التعديلات المنفذة

### 1. ✅ `api/includes/functions.php`

```php
// قبل التعديل
function checkRequestMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        jsonResponse(false, "Invalid request method. Expected {$method}", null, 405);
    }
}

// بعد التعديل
function checkRequestMethod($method) {
    $allowedMethods = is_array($method) ? $method : [$method];

    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
        $expected = implode(' or ', $allowedMethods);
        jsonResponse(false, "Invalid request method. Expected {$expected}", null, 405);
    }
}
```

### 2. ✅ `api/update_announcement.php`

```php
// قبل التعديل - كان يبحث عن user_id غير موجود
$getQuery = "SELECT a.*, u.name as author
             FROM announcements a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.id = :id";

// بعد التعديل - يقرأ من جدول announcements مباشرة
$getQuery = "SELECT * FROM announcements WHERE id = :id";
```

## اختبار حذف الإعلان

### الطريقة الأولى: من لوحة التحكم

1. افتح: `http://127.0.0.1:8080/dashboard/attendance_api/admin/login.html`
2. سجل دخولك
3. اذهب لتبويب "الإعلانات"
4. اضغط على زر "حذف" لأي إعلان
5. وافق على التأكيد

### الطريقة الثانية: اختبار مباشر بـ cURL

```bash
# احصل على التوكن أولاً من localStorage بعد تسجيل الدخول
# ثم استبدل YOUR_TOKEN_HERE بالتوكن الفعلي

curl -X DELETE "http://127.0.0.1:8080/dashboard/attendance_api/api/delete_announcement.php?id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### الطريقة الثالثة: من Console المتصفح

```javascript
// افتح Console في صفحة لوحة التحكم واكتب:
const token = localStorage.getItem("admin_token");
fetch(
  "http://127.0.0.1:8080/dashboard/attendance_api/api/delete_announcement.php?id=1",
  {
    method: "DELETE",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  }
)
  .then((res) => res.json())
  .then((data) => console.log(data))
  .catch((err) => console.error(err));
```

## الاستجابات المتوقعة

### ✅ نجاح (200)

```json
{
  "success": true,
  "message": "Announcement deleted successfully",
  "data": {
    "announcement_id": 1,
    "title": "عنوان الإعلان"
  }
}
```

### ❌ فشل - لا توجد صلاحيات (403)

```json
{
  "success": false,
  "message": "Insufficient permissions"
}
```

### ❌ فشل - الإعلان غير موجود (404)

```json
{
  "success": false,
  "message": "Announcement not found"
}
```

### ❌ فشل - التوكن غير صحيح (401)

```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

## التحقق من قاعدة البيانات

بعد حذف إعلان، تحقق من قاعدة البيانات:

```sql
-- افتح phpMyAdmin أو MySQL CLI
SELECT * FROM announcements ORDER BY id DESC LIMIT 10;
```

الإعلان المحذوف يجب أن يختفي تماماً (Hard Delete).

## ملاحظات مهمة

- ✅ حذف الإعلان نهائي (Hard Delete) - لا يمكن استرجاعه
- ✅ يتطلب صلاحيات admin أو servant
- ✅ يتحقق من وجود الإعلان قبل الحذف
- ✅ يرجع معلومات الإعلان المحذوف للتأكيد

## استكشاف الأخطاء

### إذا ظهر خطأ JSON Parse

- تحقق من أن ملف `delete_announcement.php` غير فارغ
- تحقق من أن PHP يعمل بدون أخطاء syntax
- افتح URL مباشرة في المتصفح وشاهد الاستجابة

### إذا ظهر "Authentication required"

- تأكد من تسجيل الدخول أولاً
- تحقق من وجود التوكن: `localStorage.getItem('admin_token')`
- تحقق من أن التوكن لم ينتهي (24 ساعة صلاحية)

### إذا ظهر "Invalid request method"

- تأكد من استخدام DELETE method
- تحقق من الدالة `checkRequestMethod` في functions.php
- تأكد من تطبيق التعديلات الجديدة

## الملفات المتأثرة

1. ✅ `api/includes/functions.php` - تحديث دالة checkRequestMethod
2. ✅ `api/update_announcement.php` - إصلاح استعلام SQL
3. ✅ `api/delete_announcement.php` - يعمل بشكل صحيح
4. ✅ `api/add_announcement.php` - يعمل بشكل صحيح
5. ✅ `api/get_announcements.php` - يعمل بشكل صحيح
6. ✅ `api/toggle_announcement_pin.php` - يعمل بشكل صحيح

---

**تاريخ التحديث:** 6 أكتوبر 2025  
**الحالة:** ✅ جميع العمليات تعمل بشكل صحيح
