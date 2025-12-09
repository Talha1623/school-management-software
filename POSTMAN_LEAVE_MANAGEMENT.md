# Leave Management API Documentation

**Base URL:** `https://school.ritpk.com/api/teacher`

---

## 🔐 Authentication

All endpoints require authentication token in header:
```
Authorization: Bearer {your_token}
```

---

## 📋 API Endpoints

### 1. Create Leave Application

**POST** `/api/teacher/leave/create`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "leave_reason": "Medical emergency",
  "from_date": "2025-12-10",
  "to_date": "2025-12-12"
}
```

**Request Body Fields:**
- `leave_reason` (required, string, max 255 characters): Reason for leave
- `from_date` (required, date, format: YYYY-MM-DD): Start date of leave
- `to_date` (required, date, format: YYYY-MM-DD, must be >= from_date): End date of leave

**Response (Success - 201):**
```json
{
  "success": true,
  "message": "Leave application created successfully.",
  "data": {
    "leave": {
      "id": 1,
      "staff_id": 5,
      "staff_name": "John Doe",
      "leave_reason": "Medical emergency",
      "from_date": "2025-12-10",
      "to_date": "2025-12-12",
      "status": "Pending",
      "created_at": "2025-12-08 14:30:00"
    }
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Validation Error - 422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "leave_reason": [
      "Leave reason is required."
    ],
    "from_date": [
      "From date is required."
    ],
    "to_date": [
      "To date must be equal to or after from date."
    ]
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can create leave applications.",
  "token": null
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/leave/create`

---

### 2. Get Leave Applications List

**GET** `/api/teacher/leave/list`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Method:** GET only

**Query Parameters (Optional):**
- `status` (string): Filter by status (Pending, Approved, Rejected, Cancelled)
- `per_page` (integer): Number of records per page (10, 25, 50, 100). Default: 10
- `page` (integer): Page number for pagination

**Example GET Requests:**
```
GET /api/teacher/leave/list
GET /api/teacher/leave/list?status=Pending
GET /api/teacher/leave/list?status=Pending&per_page=25&page=1
```

**Note:** Complete list of all leave applications will be returned. Date filtering has been removed.

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Leave applications retrieved successfully.",
  "data": {
    "leaves": [
      {
        "id": 1,
        "leave_reason": "Medical emergency",
        "from_date": "2025-12-10",
        "to_date": "2025-12-12",
        "status": "Pending",
        "remarks": null,
        "created_at": "2025-12-08 14:30:00",
        "updated_at": "2025-12-08 14:30:00"
      },
      {
        "id": 2,
        "leave_reason": "Family function",
        "from_date": "2025-12-15",
        "to_date": "2025-12-16",
        "status": "Approved",
        "remarks": "Approved by admin",
        "created_at": "2025-12-05 10:20:00",
        "updated_at": "2025-12-06 11:15:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 25,
      "total": 50,
      "from": 1,
      "to": 25
    }
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access leave applications.",
  "token": null
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/leave/list`

---

### 3. Cancel Leave Application

**POST** `/api/teacher/leave/{id}/cancel`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): Leave application ID

**Description:** Cancels a pending leave application. Only pending leaves can be cancelled. Approved or rejected leaves cannot be cancelled.

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Leave application cancelled successfully.",
  "data": {
    "leave": {
      "id": 1,
      "leave_reason": "Medical emergency",
      "from_date": "2025-12-10",
      "to_date": "2025-12-12",
      "status": "Cancelled",
      "updated_at": "2025-12-08 15:30:00"
    }
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Leave Not Found - 404):**
```json
{
  "success": false,
  "message": "Leave application not found.",
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Already Cancelled - 400):**
```json
{
  "success": false,
  "message": "Leave application is already cancelled.",
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Cannot Cancel Approved - 400):**
```json
{
  "success": false,
  "message": "Cannot cancel an approved leave application.",
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Cannot Cancel Rejected - 400):**
```json
{
  "success": false,
  "message": "Cannot cancel a rejected leave application.",
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can cancel leave applications.",
  "token": null
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/leave/{id}/cancel`

**Example:**
```
POST /api/teacher/leave/1/cancel
```

---

## 📝 Important Notes

### Required Fields for Create Leave:
- ✅ `leave_reason` - Required
- ✅ `from_date` - Required (format: YYYY-MM-DD)
- ✅ `to_date` - Required (format: YYYY-MM-DD, must be >= from_date)

### Optional Fields:
- ❌ `remarks` - Not available in API (only for admin use)

### Status Values:
- `Pending` - Default status when leave is created (can be cancelled)
- `Approved` - Leave approved by admin (cannot be cancelled)
- `Rejected` - Leave rejected by admin (cannot be cancelled)
- `Cancelled` - Leave cancelled by teacher (only pending leaves can be cancelled)

### Date Format:
- All dates must be in `YYYY-MM-DD` format (e.g., "2025-12-10")
- `to_date` must be equal to or after `from_date`

### Validation Rules:
1. `leave_reason`: Required, string, maximum 255 characters
2. `from_date`: Required, valid date in YYYY-MM-DD format
3. `to_date`: Required, valid date in YYYY-MM-DD format, must be >= from_date

### Response Format:
- All successful responses include `success: true`
- All responses include `token` field (null if authentication fails)
- Error responses include `message` and `errors` (for validation errors)

### Authentication:
- All endpoints require Bearer token in Authorization header
- Token is obtained from `/api/teacher/login` endpoint
- Token never expires (unless manually revoked)

---

## 🔄 Example Flow

1. **Login** to get token:
   ```
   POST /api/teacher/login
   Body: { "email": "teacher@example.com", "password": "password" }
   ```

2. **Create Leave Application**:
   ```
   POST /api/teacher/leave/create
   Headers: { "Authorization": "Bearer {token}" }
   Body: {
     "leave_reason": "Medical emergency",
     "from_date": "2025-12-10",
     "to_date": "2025-12-12"
   }
   ```

3. **Get Leave Applications List**:
   ```
   GET /api/teacher/leave/list?status=Pending
   Headers: { "Authorization": "Bearer {token}" }
   ```

4. **Cancel Leave Application** (if pending):
   ```
   POST /api/teacher/leave/1/cancel
   Headers: { "Authorization": "Bearer {token}" }
   ```

---

## ⚠️ Error Codes

- `200` - Success
- `201` - Created successfully
- `400` - Bad request (e.g., already cancelled, cannot cancel approved/rejected)
- `403` - Access denied (not a teacher)
- `404` - Leave application not found
- `422` - Validation error
- `500` - Server error

---

## 📱 Postman Collection

### Create Leave Application
```
Method: POST
URL: https://school.ritpk.com/api/teacher/leave/create
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json
Body (raw JSON):
{
  "leave_reason": "Medical emergency",
  "from_date": "2025-12-10",
  "to_date": "2025-12-12"
}
```

### Get Leave Applications List
```
Method: GET
URL: https://school.ritpk.com/api/teacher/leave/list?status=Pending&per_page=25
Headers:
  Authorization: Bearer {token}
```

### Cancel Leave Application
```
Method: POST
URL: https://school.ritpk.com/api/teacher/leave/1/cancel
Headers:
  Authorization: Bearer {token}
```

---

# Homework Diary API Documentation

**Base URL:** `https://school.ritpk.com/api/teacher`

---

## 📚 Get Subjects with Homework for Class and Section

**GET** `/api/teacher/homework-diary/subjects-with-homework`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (Required):**
- `class` (required, string): Class name (e.g., "five", "5th", "Five")
- `section` (required, string): Section name (e.g., "A", "B", "C")
- `date` (optional, date, format: YYYY-MM-DD): Filter homework by specific date. If not provided, returns all homework entries for each subject.

**Description:** 
Yeh API ek specific class aur section ke liye sirf un subjects ko list karta hai jo us class aur section ko assign kiye gaye hain, aur har subject ke saath uska homework bhi return karta hai.

**Example:** Agar Class 5 Section A ko 3 subjects assign hain (Mathematics, English, Science), to sirf wo 3 subjects list honge with their homework. Baaki classes ke subjects nahi aayenge.

**Example GET Requests:**

1. **Get all subjects with all homework entries:**
```
GET /api/teacher/homework-diary/subjects-with-homework?class=five&section=A
```

2. **Get subjects with homework for specific date:**
```
GET /api/teacher/homework-diary/subjects-with-homework?class=five&section=A&date=2025-12-10
```

**Response (Success - 200) - Without Date Filter:**
```json
{
  "success": true,
  "message": "Subjects with homework retrieved successfully for class: five, section: A",
  "data": {
    "class": "five",
    "section": "A",
    "date_filter": null,
    "subjects": [
      {
        "id": 1,
        "subject_name": "Mathematics",
        "campus": "Main Campus",
        "class": "five",
        "section": "A",
        "teacher": "John Doe",
        "homework": [
          {
            "id": 10,
            "homework_content": "Complete exercises 1-5 on page 25",
            "date": "2025-12-10",
            "created_at": "2025-12-10 14:30:00"
          },
          {
            "id": 11,
            "homework_content": "Solve problems 6-10",
            "date": "2025-12-09",
            "created_at": "2025-12-09 15:20:00"
          }
        ],
        "homework_count": 2
      },
      {
        "id": 2,
        "subject_name": "English",
        "campus": "Main Campus",
        "class": "five",
        "section": "A",
        "teacher": "Jane Smith",
        "homework": [
          {
            "id": 12,
            "homework_content": "Read chapter 3 and write summary",
            "date": "2025-12-10",
            "created_at": "2025-12-10 16:00:00"
          }
        ],
        "homework_count": 1
      },
      {
        "id": 3,
        "subject_name": "Science",
        "campus": "Main Campus",
        "class": "five",
        "section": "A",
        "teacher": "Bob Johnson",
        "homework": [],
        "homework_count": 0
      }
    ],
    "total_subjects": 3
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Success - 200) - With Date Filter:**
```json
{
  "success": true,
  "message": "Subjects with homework retrieved successfully for class: five, section: A, date: 2025-12-10",
  "data": {
    "class": "five",
    "section": "A",
    "date_filter": "2025-12-10",
    "subjects": [
      {
        "id": 1,
        "subject_name": "Mathematics",
        "campus": "Main Campus",
        "class": "five",
        "section": "A",
        "teacher": "John Doe",
        "homework": [
          {
            "id": 10,
            "homework_content": "Complete exercises 1-5 on page 25",
            "date": "2025-12-10",
            "created_at": "2025-12-10 14:30:00"
          }
        ],
        "homework_count": 1
      },
      {
        "id": 2,
        "subject_name": "English",
        "campus": "Main Campus",
        "class": "five",
        "section": "A",
        "teacher": "Jane Smith",
        "homework": [
          {
            "id": 12,
            "homework_content": "Read chapter 3 and write summary",
            "date": "2025-12-10",
            "created_at": "2025-12-10 16:00:00"
          }
        ],
        "homework_count": 1
      },
      {
        "id": 3,
        "subject_name": "Science",
        "campus": "Main Campus",
        "class": "five",
        "section": "A",
        "teacher": "Bob Johnson",
        "homework": [],
        "homework_count": 0
      }
    ],
    "total_subjects": 3
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (No Subjects Found - 200):**
```json
{
  "success": true,
  "message": "No subjects found for class: five, section: A",
  "data": {
    "class": "five",
    "section": "A",
    "subjects": [],
    "total_subjects": 0
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Validation Error - 422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "class": [
      "The class field is required."
    ],
    "section": [
      "The section field is required."
    ],
    "date": [
      "The date does not match the format Y-m-d."
    ]
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access this endpoint.",
  "token": null
}
```

**Full URL Examples:**
- All homework: `https://school.ritpk.com/api/teacher/homework-diary/subjects-with-homework?class=five&section=A`
- Specific date: `https://school.ritpk.com/api/teacher/homework-diary/subjects-with-homework?class=five&section=A&date=2025-12-10`

**Important Notes:**
- ✅ `class` - Required parameter
- ✅ `section` - Required parameter
- ⚪ `date` - Optional parameter. If provided, returns homework only for that date. If not provided, returns all homework entries for each subject.
- Sirf us class aur section ke subjects return hote hain jo assign kiye gaye hain
- Har subject ke saath uska homework array mein return hota hai
- Agar kisi subject ka homework nahi hai, to empty array return hota hai
- Homework entries date ke basis par descending order mein sorted hote hain (newest first)

---

# Teacher Profile & Change Password API Documentation

**Base URL:** `https://school.ritpk.com/api/teacher`

---

## 📋 API Endpoints

### 1. Get Personal Details

**GET** `/api/teacher/personal-details`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Description:** 
Teacher ki personal details return karta hai - father name, email, phone, id card, gender

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Personal details retrieved successfully",
  "data": {
    "father_name": "Father Name",
    "email": "teacher@example.com",
    "phone": "1234567890",
    "id_card": "EMP001",
    "gender": "Male"
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/personal-details`

---

### 2. Change Password

**POST** `/api/teacher/change-password`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "current_password": "oldpassword123",
  "new_password": "newpassword123",
  "confirm_password": "newpassword123"
}
```

**Request Body Fields:**
- `current_password` (required, string): Current password
- `new_password` (required, string, min: 6 characters): New password
- `confirm_password` (required, string, must match new_password): Confirm new password

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Password changed successfully.",
  "data": {},
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Validation Error - 422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "current_password": ["Current password is required."],
    "new_password": ["New password must be at least 6 characters."],
    "confirm_password": ["Confirm password must match new password."]
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Incorrect Current Password - 400):**
```json
{
  "success": false,
  "message": "Current password is incorrect.",
  "token": null
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/change-password`

---

