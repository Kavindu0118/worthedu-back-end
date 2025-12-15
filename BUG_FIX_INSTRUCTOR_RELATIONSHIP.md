# Bug Fix: Instructor Relationship Error

## 🐛 Issue
**Error:** `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'id' in 'field list'`

**Affected Endpoint:** `GET /api/learner/courses`

**Console Error:**
```
Failed to load resource: the server responded with a status of 500 (Internal Server Error)
Failed to fetch courses: Error: Failed to fetch courses
```

---

## 🔍 Root Cause

The `instructors` table uses `instructor_id` as the primary key instead of the default `id`. The Instructor model and controllers were trying to access instructor data incorrectly:

1. **Database Schema Issue:**
   - Table: `instructors` uses `instructor_id` as primary key
   - Model: `Instructor` was not configured with custom primary key
   - Relationships: Code was trying to load `instructor:id,name,email` but:
     - `id` column doesn't exist (should be `instructor_id`)
     - `name` and `email` are in the `users` table, not `instructors` table

2. **Relationship Structure:**
   ```
   Course → belongsTo → Instructor → belongsTo → User
                         (has instructor_id)      (has name, email)
   ```

---

## ✅ Solution

### 1. Updated Instructor Model
**File:** `app/Models/Instructor.php`

Added primary key specification:
```php
class Instructor extends Model
{
    use HasFactory;
    
    // Specify the primary key column name
    protected $primaryKey = 'instructor_id';
    
    // ... rest of the code
}
```

### 2. Updated LearnerCourseController
**File:** `app/Http/Controllers/LearnerCourseController.php`

#### index() Method - Get Enrolled Courses
**Before:**
```php
->with(['course' => function($q) {
    $q->select('id', 'title', 'description', 'thumbnail', 'category', 'level', 'duration', 'instructor_id')
      ->with('instructor:id,name,email');
}]);
```

**After:**
```php
->with(['course' => function($q) {
    $q->select('id', 'title', 'description', 'thumbnail', 'category', 'level', 'duration', 'instructor_id')
      ->with(['instructor.user:id,name,email']);
}]);
```

**Response Builder - Before:**
```php
'instructor' => $course->instructor ? [
    'id' => $course->instructor->id,
    'name' => $course->instructor->name,
] : null,
```

**Response Builder - After:**
```php
'instructor' => $course->instructor && $course->instructor->user ? [
    'id' => $course->instructor->user->id,
    'name' => $course->instructor->user->name,
] : null,
```

#### available() Method - Get Available Courses
**Before:**
```php
$query = Course::whereNotIn('id', $enrolledCourseIds)
    ->where('status', 'published')
    ->with('instructor:id,name,email');
```

**After:**
```php
$query = Course::whereNotIn('id', $enrolledCourseIds)
    ->where('status', 'published')
    ->with(['instructor.user:id,name,email']);
```

**Response - Updated to access instructor.user**

#### show() Method - Get Course Details
**Before:**
```php
$course = Course::with([
    'instructor:id,name,email,bio',
    'modules' => function($q) {
        $q->orderBy('order_index');
    }
])->findOrFail($id);
```

**After:**
```php
$course = Course::with([
    'instructor.user:id,name,email',
    'modules' => function($q) {
        $q->orderBy('order_index');
    }
])->findOrFail($id);
```

**Response:**
```php
'instructor' => $course->instructor && $course->instructor->user ? [
    'id' => $course->instructor->user->id,
    'name' => $course->instructor->user->name,
    'email' => $course->instructor->user->email,
] : null,
```

### 3. Updated PaymentController
**File:** `app/Http/Controllers/PaymentController.php`

**Before:**
```php
'instructor' => $payment->enrollment->course->instructor->name ?? 'N/A',
```

**After:**
```php
'instructor' => $payment->enrollment->course->instructor->user->name ?? 'N/A',
```

---

## 🧪 Testing

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Test the Endpoint
```bash
curl -X GET "http://127.0.0.1:8000/api/learner/courses" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Course Title",
      "description": "Course description",
      "thumbnail": "http://127.0.0.1:8000/storage/thumbnails/course.jpg",
      "category": "programming",
      "level": "beginner",
      "duration": "8 weeks",
      "instructor": {
        "id": 1,
        "name": "John Doe"
      },
      "enrollment": {
        "id": 1,
        "status": "active",
        "progress": 45.5,
        "enrolled_at": "2025-12-01T10:00:00.000000Z",
        "last_accessed": "2025-12-10T14:30:00.000000Z",
        "completed_at": null
      }
    }
  ]
}
```

---

## 📝 Key Changes Summary

1. **Instructor Model:** Added `protected $primaryKey = 'instructor_id';`
2. **Eager Loading:** Changed from `'instructor:columns'` to `'instructor.user:columns'`
3. **Data Access:** Changed from `$instructor->name` to `$instructor->user->name`
4. **Null Checks:** Added double null checks: `$instructor && $instructor->user`

---

## ⚠️ Important Notes

### Database Relationships
The correct relationship chain is:
```
Course (instructor_id)
  └── Instructor (instructor_id, user_id)
        └── User (id, name, email)
```

### Why This Happened
The `instructors` table was designed with:
- Primary key: `instructor_id` (not `id`)
- Foreign key: `user_id` (links to users table)
- The `users` table stores `name` and `email`, not the `instructors` table

### Lesson Learned
When a model uses a custom primary key:
1. Always specify it in the model with `protected $primaryKey`
2. Always check relationships to ensure correct foreign key references
3. When eager loading relationships, use dot notation for nested relationships
4. Test with actual database queries to verify column existence

---

## ✅ Status
**Fixed:** All instructor-related endpoints now work correctly
**Tested:** Courses are loading without errors
**Impact:** Resolves 500 errors on:
- `GET /api/learner/courses`
- `GET /api/learner/courses/available`
- `GET /api/learner/courses/{id}`
- Payment receipt generation

---

**Fixed Date:** December 10, 2025
**Files Modified:** 3 (Instructor.php, LearnerCourseController.php, PaymentController.php)
