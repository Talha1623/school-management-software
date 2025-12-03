# Teacher Self Attendance API Documentation

**Base URL:** `https://school.ritpk.com/api/teacher`

---

## 🔐 Authentication

All endpoints (except login) require authentication token in header:
```
Authorization: Bearer {your_token}
```

---

## 📋 API Endpoints

### 1. Teacher Login
**POST** `/api/teacher/login`

**Request Body:**
```json
{
  "email": "teacher@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/login`

---

### 2. Get Teacher Profile (Complete Details)
**GET** `/api/teacher/profile`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "teacher": {
      "id": 1,
      "name": "John Doe",
      "email": "teacher@example.com",
      "emp_id": "EMP001",
      "designation": "teacher",
      "role": "teacher",
      "campus": "Main Campus",
      "phone": "1234567890",
      "whatsapp": "1234567890",
      "father_husband_name": "Father Name",
      "gender": "Male",
      "photo": "https://school.ritpk.com/storage/staff/photos/photo.jpg",
      "qualification": "Masters in Education",
      "joining_date": "2020-01-01",
      "assigned_subjects": [
        {
          "subject_id": 5,
          "subject_name": "Mathematics",
          "campus": "Main Campus",
          "class": "5th",
          "section": "A"
        },
        {
          "subject_id": 6,
          "subject_name": "Mathematics",
          "campus": "Main Campus",
          "class": "6th",
          "section": "B"
        }
      ],
      "total_subjects": 2,
      "classes_taught": ["5th", "6th"],
      "sections_taught": ["A", "B"]
    },
    "has_dashboard_access": true
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Profile Fields:**
- **Basic Info:** id, name, email, emp_id, designation, role, campus
- **Contact:** phone, whatsapp
- **Personal:** father_husband_name, gender, photo (full URL)
- **Professional:** qualification, joining_date
- **Assigned Subjects:** Complete list of subjects teacher teaches
- **Summary:** total_subjects, classes_taught, sections_taught

**Full URL:** `https://school.ritpk.com/api/teacher/profile`

---

### 3. Get Teacher Dashboard (Statistics with Assigned Classes)
**GET** `/api/teacher/dashboard`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "assigned_classes": ["5th", "6th", "7th"],
    "statistics": {
      "total_students": 30,
      "boys": 15,
      "girls": 15,
      "attendance_percentage": 85.5,
      "present_today": 25,
      "absent_today": 5
    },
    "latest_admissions": [
      {
        "id": 1,
        "student_name": "John Doe",
        "admission_date": "2025-12-01"
      }
    ]
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Dashboard Data:**
- **Assigned Classes:** List of classes assigned to teacher (from subjects table)
- **Statistics:** Total students, boys, girls count (only from assigned classes)
- **Attendance:** Today's attendance percentage, present/absent count
- **Latest Admissions:** Recent 12 students from assigned classes

**Note:** Dashboard shows only students from teacher's assigned classes. If no classes assigned, statistics will be 0.

**Full URL:** `https://school.ritpk.com/api/teacher/dashboard`

---

### 4. Mark Self Attendance
**POST** `/api/teacher/self-attendance/mark`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "attendance_date": "2025-12-22",
  "status": "Present",
  "start_time": "09:00:00",
  "end_time": "17:00:00",
  "remarks": "Optional remarks here"
}
```

**Status Values:** `Present`, `Absent`, `Holiday`, `Sunday`, `Leave`, `N/A`

**Time Format:** `HH:MM:SS` (e.g., `09:00:00`) or `HH:MM` (e.g., `09:00`) - both accepted

**Response:**
```json
{
  "success": true,
  "message": "Self-attendance marked successfully",
  "data": {
    "attendance": {
      "id": 1,
      "staff_id": 5,
      "name": "John Doe",
      "emp_id": "EMP001",
      "designation": "teacher",
      "attendance_date": "2025-12-22",
      "status": "Present",
      "start_time": "09:00:00",
      "end_time": "17:00:00",
      "campus": "Main Campus",
      "remarks": "Optional remarks here"
    }
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/self-attendance/mark`

---

### 4. Check Today's Attendance
**GET** `/api/teacher/self-attendance/check`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `date` - Check specific date (format: YYYY-MM-DD). Default: today

**Example:**
```
GET /api/teacher/self-attendance/check?date=2025-12-22
```

**Response:**
```json
{
  "success": true,
  "message": "Attendance status retrieved successfully",
  "data": {
    "teacher": {
      "id": 5,
      "name": "John Doe",
      "emp_id": "EMP001",
      "designation": "teacher",
      "campus": "Main Campus"
    },
    "date": "2025-12-22",
    "is_marked": true,
    "attendance": {
      "id": 1,
      "status": "Present",
      "start_time": "09:00:00",
      "end_time": "17:00:00",
      "remarks": "Optional remarks",
      "marked_at": "2025-12-22 09:30:00"
    }
  }
}
```

**Full URLs:**
- Today: `https://school.ritpk.com/api/teacher/self-attendance/check`
- Specific Date: `https://school.ritpk.com/api/teacher/self-attendance/check?date=2025-12-22`

---

### 5. Get Attendance History
**GET** `/api/teacher/self-attendance/history`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `start_date` - Start date (format: YYYY-MM-DD)
- `end_date` - End date (format: YYYY-MM-DD)
- `status` - Filter by status (Present, Absent, etc.)
- `per_page` - Records per page (10, 25, 30, 50, 100). Default: 30

**Examples:**
```
GET /api/teacher/self-attendance/history
GET /api/teacher/self-attendance/history?start_date=2025-12-01&end_date=2025-12-31
GET /api/teacher/self-attendance/history?status=Present&per_page=50
```

**Response:**
```json
{
  "success": true,
  "message": "Self-attendance history retrieved successfully",
  "data": {
    "teacher": {
      "id": 5,
      "name": "John Doe",
      "emp_id": "EMP001",
      "designation": "teacher",
      "campus": "Main Campus"
    },
    "statistics": {
      "total_days": 30,
      "present_days": 25,
      "absent_days": 3,
      "attendance_percentage": 83.33
    },
    "attendances": [
      {
        "id": 1,
        "attendance_date": "2025-12-22",
        "status": "Present",
        "start_time": "09:00:00",
        "end_time": "17:00:00",
        "campus": "Main Campus",
        "remarks": null,
        "marked_at": "2025-12-22 09:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 30,
      "total": 30,
      "from": 1,
      "to": 30
    }
  }
}
```

**Full URLs:**
- All: `https://school.ritpk.com/api/teacher/self-attendance/history`
- With Filters: `https://school.ritpk.com/api/teacher/self-attendance/history?start_date=2025-12-01&end_date=2025-12-31&status=Present`

---

## 📊 Attendance Report API

### 1. Get Monthly Attendance Report (Same as Web)
**GET/POST** `/api/teacher/attendance-report/monthly`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json (for POST)
```

**Method 1: GET Request (Query Parameters)**
```
GET /api/teacher/attendance-report/monthly?campus=icms&class=Ninth&section=A&month=12&year=2025
```

**Method 2: POST Request (Request Body)**
```
POST /api/teacher/attendance-report/monthly
```

**Request Body (for POST):**
```json
{
  "campus": "icms",
  "class": "Ninth",
  "section": "A",
  "month": "12",
  "year": 2025
}
```

**Parameters (Required):**
- `campus` - Campus name (required)
- `class` - Class name (required)
- `section` - Section name (optional)
- `month` - Month number (01-12, required)
- `year` - Year (YYYY, required)

**Response:**
```json
{
  "success": true,
  "message": "Monthly attendance report retrieved successfully",
  "data": {
    "header": {
      "campus": "Main Campus",
      "class": "5th",
      "section": "A",
      "month": "December",
      "year": 2025,
      "month_number": "12",
      "days_in_month": 31
    },
    "summary": {
      "total_students": 30,
      "total_present_days": 600,
      "total_absent_days": 60
    },
    "students": [
      {
        "roll_number": "ST001",
        "student_id": 1,
        "student_name": "John Doe",
        "surname_caste": "Khan",
        "father_name": "Father Name",
        "present_days": 20,
        "absent_days": 2,
        "daily_attendance": {
          "1": "P",
          "2": "P",
          "3": "A",
          "4": "P",
          "5": "H",
          "6": "S",
          "7": "P",
          "8": "L",
          ...
          "31": "P"
        }
      }
    ]
  }
}
```

**Status Codes:**
- `P` = Present
- `A` = Absent
- `H` = Holiday
- `S` = Sunday
- `L` = Leave
- Empty string = No attendance marked

**Note:** Teacher can only access reports for their assigned classes/sections (from subjects table).

**Full URLs:**
- GET: `https://school.ritpk.com/api/teacher/attendance-report/monthly?campus=icms&class=Ninth&section=A&month=12&year=2025`
- POST: `https://school.ritpk.com/api/teacher/attendance-report/monthly` (with body parameters)

---

### 2. Get Attendance Report Filter Options
**GET** `/api/teacher/attendance-report/filter-options`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `class` - Get sections for specific class

**Example:**
```
GET /api/teacher/attendance-report/filter-options
GET /api/teacher/attendance-report/filter-options?class=5th
```

**Response:**
```json
{
  "success": true,
  "message": "Attendance report filter options retrieved successfully",
  "data": {
    "campuses": ["Main Campus", "Branch Campus"],
    "classes": ["5th", "6th", "7th"],
    "sections": ["A", "B", "C"],
    "months": {
      "01": "January",
      "02": "February",
      "03": "March",
      "04": "April",
      "05": "May",
      "06": "June",
      "07": "July",
      "08": "August",
      "09": "September",
      "10": "October",
      "11": "November",
      "12": "December"
    },
    "years": [2020, 2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028, 2029, 2030]
  }
}
```

**Note:** Returns only campuses, classes, and sections that the teacher has access to (from assigned subjects).

**Full URL:** `https://school.ritpk.com/api/teacher/attendance-report/filter-options`

---

### 6. Logout
**POST** `/api/teacher/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logout successful",
  "token": null
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/logout`

---

## 📝 Example cURL Requests

### Login
```bash
curl -X POST https://school.ritpk.com/api/teacher/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teacher@example.com",
    "password": "password123"
  }'
```

### Mark Attendance
```bash
curl -X POST https://school.ritpk.com/api/teacher/self-attendance/mark \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "attendance_date": "2025-12-22",
    "status": "Present",
    "start_time": "09:00:00",
    "end_time": "17:00:00",
    "remarks": "On time"
  }'
```

### Check Today's Attendance
```bash
curl -X GET https://school.ritpk.com/api/teacher/self-attendance/check \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get History
```bash
curl -X GET "https://school.ritpk.com/api/teacher/self-attendance/history?start_date=2025-12-01&end_date=2025-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🔒 Error Responses

### Unauthenticated (401)
```json
{
  "success": false,
  "message": "Unauthenticated",
  "token": null
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "status": ["The status field is required."]
  }
}
```

### Access Denied (403)
```json
{
  "success": false,
  "message": "Access denied. Only teachers can mark self-attendance."
}
```

---

## 📌 Important Notes

1. **Token Required**: All endpoints except login require Bearer token in Authorization header
2. **One Entry Per Day**: A teacher can only mark one attendance per day (can update same day)
3. **Status Values**: Must be one of: `Present`, `Absent`, `Holiday`, `Sunday`, `Leave`, `N/A`
4. **Date Format**: Always use `YYYY-MM-DD` format (e.g., `2025-12-22`)
5. **Teacher Only**: Only users with `designation = "teacher"` can access these endpoints

---

## 🧪 Testing

You can test these APIs using:
- **Postman**
- **cURL** (command line)
- **Thunder Client** (VS Code extension)
- **Insomnia**
- **Any HTTP client**

---

---

## 📅 Academic Calendar Events API

### 1. Create Event (Add New Event)
**POST** `/api/teacher/events/create`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "event_title": "Eid Holiday",
  "event_details": "Eid ul Fitr holiday - school will be closed",
  "event_type": "holiday",
  "event_date": "2025-04-10"
}
```

**Event Type Values:** `holiday`, `event`, `other` (optional)

**Response:**
```json
{
  "success": true,
  "message": "Event created successfully",
  "data": {
    "event": {
      "id": 1,
      "event_title": "Eid Holiday",
      "event_details": "Eid ul Fitr holiday - school will be closed",
      "event_type": "holiday",
      "event_date": "2025-04-10",
      "created_at": "2025-12-22 10:30:00"
    }
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/events/create`

---

### 2. Get Events List
**GET** `/api/teacher/events/list`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `year` - Filter by year (e.g., 2025)
- `month` - Filter by month (1-12)
- `event_type` - Filter by type (holiday, event, other)
- `start_date` - Start date filter (YYYY-MM-DD)
- `end_date` - End date filter (YYYY-MM-DD)
- `search` - Search in title or details
- `per_page` - Records per page (10, 25, 30, 50, 100). Default: 30

**Examples:**
```
GET /api/teacher/events/list
GET /api/teacher/events/list?year=2025&month=4
GET /api/teacher/events/list?event_type=holiday&per_page=50
GET /api/teacher/events/list?start_date=2025-01-01&end_date=2025-12-31
GET /api/teacher/events/list?search=eid
```

**Response:**
```json
{
  "success": true,
  "message": "Events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "event_title": "Eid Holiday",
        "event_details": "Eid ul Fitr holiday",
        "event_type": "holiday",
        "event_date": "2025-04-10",
        "created_at": "2025-12-22 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 30,
      "total": 30,
      "from": 1,
      "to": 30
    }
  }
}
```

**Full URLs:**
- All: `https://school.ritpk.com/api/teacher/events/list`
- With Filters: `https://school.ritpk.com/api/teacher/events/list?year=2025&month=4&event_type=holiday`

---

### 3. Get Single Event
**GET** `/api/teacher/events/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Event retrieved successfully",
  "data": {
    "event": {
      "id": 1,
      "event_title": "Eid Holiday",
      "event_details": "Eid ul Fitr holiday",
      "event_type": "holiday",
      "event_date": "2025-04-10",
      "created_at": "2025-12-22 10:30:00",
      "updated_at": "2025-12-22 10:30:00"
    }
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/events/1`

---

### 4. Update Event
**PUT** `/api/teacher/events/{id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "event_title": "Eid Holiday (Updated)",
  "event_details": "Updated details",
  "event_type": "holiday",
  "event_date": "2025-04-11"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Event updated successfully",
  "data": {
    "event": {
      "id": 1,
      "event_title": "Eid Holiday (Updated)",
      "event_details": "Updated details",
      "event_type": "holiday",
      "event_date": "2025-04-11",
      "updated_at": "2025-12-22 11:00:00"
    }
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/events/1`

---

### 5. Delete Event
**DELETE** `/api/teacher/events/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Event deleted successfully"
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/events/1`

---

### 6. Get Calendar View (Events by Month)
**GET** `/api/teacher/events/calendar/view`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `year` - Year for calendar (default: current year)

**Example:**
```
GET /api/teacher/events/calendar/view?year=2025
```

**Response:**
```json
{
  "success": true,
  "message": "Calendar view retrieved successfully",
  "data": {
    "year": 2025,
    "total_events": 12,
    "events_by_month": {
      "1": [
        {
          "id": 1,
          "event_title": "New Year Holiday",
          "event_details": "School closed",
          "event_type": "holiday",
          "event_date": "2025-01-01"
        }
      ],
      "4": [
        {
          "id": 2,
          "event_title": "Eid Holiday",
          "event_details": "Eid ul Fitr",
          "event_type": "holiday",
          "event_date": "2025-04-10"
        }
      ]
    }
  }
}
```

**Full URLs:**
- Current Year: `https://school.ritpk.com/api/teacher/events/calendar/view`
- Specific Year: `https://school.ritpk.com/api/teacher/events/calendar/view?year=2025`

---

## 📝 Example cURL Requests for Events

### Create Event
```bash
curl -X POST https://school.ritpk.com/api/teacher/events/create \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "event_title": "Eid Holiday",
    "event_details": "Eid ul Fitr holiday",
    "event_type": "holiday",
    "event_date": "2025-04-10"
  }'
```

### Get Events List
```bash
curl -X GET "https://school.ritpk.com/api/teacher/events/list?year=2025&month=4" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Calendar View
```bash
curl -X GET "https://school.ritpk.com/api/teacher/events/calendar/view?year=2025" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Update Event
```bash
curl -X PUT https://school.ritpk.com/api/teacher/events/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "event_title": "Updated Event",
    "event_details": "Updated details",
    "event_type": "event",
    "event_date": "2025-04-15"
  }'
```

### Delete Event
```bash
curl -X DELETE https://school.ritpk.com/api/teacher/events/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

---

## 📚 Homework Diary API

### Important: Teacher-Subject Filtering
- Teachers can only access homework for their **assigned subjects**
- Subject assignment is based on `subjects.teacher` = `staff.name` (case-insensitive)
- All endpoints automatically filter by logged-in teacher's subjects

---

### 1. Get Filter Options
**GET** `/api/teacher/homework-diary/filter-options`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully",
  "data": {
    "campuses": ["Main Campus", "Branch Campus"],
    "classes": ["5th", "6th", "7th"]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/filter-options`

---

### 2. Get Sections by Class
**GET** `/api/teacher/homework-diary/sections?class=5th`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Sections retrieved successfully",
  "data": {
    "sections": ["A", "B", "C"]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/sections?class=5th`

---

### 3. Get My Subjects (Only Assigned Subjects)
**GET** `/api/teacher/homework-diary/my-subjects`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `campus` - Filter by campus
- `class` - Filter by class
- `section` - Filter by section

**Example:**
```
GET /api/teacher/homework-diary/my-subjects?campus=Main Campus&class=5th&section=A
```

**Response:**
```json
{
  "success": true,
  "message": "Subjects retrieved successfully",
  "data": {
    "teacher": {
      "name": "John Doe",
      "emp_id": "EMP001"
    },
    "subjects": [
      {
        "id": 5,
        "subject_name": "Mathematics",
        "campus": "Main Campus",
        "class": "5th",
        "section": "A",
        "teacher": "John Doe"
      }
    ]
  }
}
```

**Full URLs:**
- All: `https://school.ritpk.com/api/teacher/homework-diary/my-subjects`
- Filtered: `https://school.ritpk.com/api/teacher/homework-diary/my-subjects?campus=Main Campus&class=5th&section=A`

---

### 4. Get Homework Entries
**GET** `/api/teacher/homework-diary/entries`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Required):**
- `campus` - Campus name
- `class` - Class name
- `section` - Section name
- `date` - Date (YYYY-MM-DD)

**Example:**
```
GET /api/teacher/homework-diary/entries?campus=Main Campus&class=5th&section=A&date=2025-12-22
```

**Response:**
```json
{
  "success": true,
  "message": "Homework entries retrieved successfully",
  "data": {
    "campus": "Main Campus",
    "class": "5th",
    "section": "A",
    "date": "2025-12-22",
    "entries": [
      {
        "id": 1,
        "subject_id": 5,
        "subject_name": "Mathematics",
        "homework_content": "Complete exercise 1-5 from page 25",
        "date": "2025-12-22",
        "has_homework": true
      },
      {
        "id": null,
        "subject_id": 6,
        "subject_name": "English",
        "homework_content": null,
        "date": null,
        "has_homework": false
      }
    ]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/entries?campus=Main Campus&class=5th&section=A&date=2025-12-22`

---

### 5. Create/Update Single Homework Entry
**POST** `/api/teacher/homework-diary/create`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "campus": "Main Campus",
  "class": "5th",
  "section": "A",
  "date": "2025-12-22",
  "subject_id": 5,
  "homework_content": "Complete exercise 1-5 from page 25"
}
```

**Note:** 
- If `homework_content` is empty, existing entry will be deleted
- Subject must be assigned to logged-in teacher

**Response:**
```json
{
  "success": true,
  "message": "Homework created successfully",
  "data": {
    "homework": {
      "id": 1,
      "subject_id": 5,
      "subject_name": "Mathematics",
      "homework_content": "Complete exercise 1-5 from page 25",
      "campus": "Main Campus",
      "class": "5th",
      "section": "A",
      "date": "2025-12-22"
    }
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/create`

---

### 6. Create/Update Bulk Homework Entries
**POST** `/api/teacher/homework-diary/create-bulk`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "campus": "Main Campus",
  "class": "5th",
  "section": "A",
  "date": "2025-12-22",
  "diaries": [
    {
      "subject_id": 5,
      "homework_content": "Complete exercise 1-5"
    },
    {
      "subject_id": 6,
      "homework_content": "Write essay on topic X"
    },
    {
      "subject_id": 7,
      "homework_content": ""
    }
  ]
}
```

**Note:** Empty `homework_content` will delete existing entry

**Response:**
```json
{
  "success": true,
  "message": "Homework diary processed successfully. 2 new entries created. 1 entries deleted (empty content).",
  "data": {
    "saved": 2,
    "updated": 0,
    "deleted": 1,
    "errors": []
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/create-bulk`

---

### 7. Get Homework List (with filters)
**GET** `/api/teacher/homework-diary/list`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `campus` - Filter by campus
- `class` - Filter by class
- `section` - Filter by section
- `date` - Filter by specific date
- `start_date` - Start date filter
- `end_date` - End date filter
- `subject_id` - Filter by subject
- `per_page` - Records per page (10, 25, 30, 50, 100). Default: 30

**Example:**
```
GET /api/teacher/homework-diary/list?class=5th&start_date=2025-12-01&end_date=2025-12-31
```

**Response:**
```json
{
  "success": true,
  "message": "Homework list retrieved successfully",
  "data": {
    "homework": [
      {
        "id": 1,
        "subject_id": 5,
        "subject_name": "Mathematics",
        "homework_content": "Complete exercise 1-5",
        "campus": "Main Campus",
        "class": "5th",
        "section": "A",
        "date": "2025-12-22",
        "created_at": "2025-12-22 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 30,
      "total": 30,
      "from": 1,
      "to": 30
    }
  }
}
```

**Full URLs:**
- All: `https://school.ritpk.com/api/teacher/homework-diary/list`
- Filtered: `https://school.ritpk.com/api/teacher/homework-diary/list?class=5th&start_date=2025-12-01&end_date=2025-12-31`

---

### 8. Update Homework Entry
**PUT** `/api/teacher/homework-diary/{id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "homework_content": "Updated homework content"
}
```

**Note:** If `homework_content` is empty, entry will be deleted

**Response:**
```json
{
  "success": true,
  "message": "Homework updated successfully",
  "data": {
    "homework": {
      "id": 1,
      "subject_id": 5,
      "subject_name": "Mathematics",
      "homework_content": "Updated homework content",
      "date": "2025-12-22"
    }
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/1`

---

### 9. Delete Homework Entry
**DELETE** `/api/teacher/homework-diary/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Homework deleted successfully"
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/1`

---

## 📝 Example cURL Requests for Homework Diary

### Get My Subjects
```bash
curl -X GET "https://school.ritpk.com/api/teacher/homework-diary/my-subjects?class=5th&section=A" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Homework Entries
```bash
curl -X GET "https://school.ritpk.com/api/teacher/homework-diary/entries?campus=Main Campus&class=5th&section=A&date=2025-12-22" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Create Single Homework
```bash
curl -X POST https://school.ritpk.com/api/teacher/homework-diary/create \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "campus": "Main Campus",
    "class": "5th",
    "section": "A",
    "date": "2025-12-22",
    "subject_id": 5,
    "homework_content": "Complete exercise 1-5"
  }'
```

### Create Bulk Homework
```bash
curl -X POST https://school.ritpk.com/api/teacher/homework-diary/create-bulk \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "campus": "Main Campus",
    "class": "5th",
    "section": "A",
    "date": "2025-12-22",
    "diaries": [
      {
        "subject_id": 5,
        "homework_content": "Complete exercise 1-5"
      },
      {
        "subject_id": 6,
        "homework_content": "Write essay"
      }
    ]
  }'
```

---

## ⚠️ Important Notes for Homework Diary API

1. **Teacher-Subject Filtering**: All endpoints automatically filter by logged-in teacher's assigned subjects
2. **Subject Assignment**: Subject must have `teacher` field matching logged-in teacher's `name` (case-insensitive)
3. **Empty Content**: If `homework_content` is empty, existing entry will be deleted
4. **Validation**: You can only add/update/delete homework for your assigned subjects
5. **Error Response**: If subject not assigned to teacher, returns 403 error

---

**Base URL:** `https://school.ritpk.com/api/teacher`

**Last Updated:** December 22, 2025

