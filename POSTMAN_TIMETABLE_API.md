# Timetable Management API Documentation

**Base URL:** `https://school.ritpk.com/api/teacher`

---

## 🔐 Authentication

All endpoints require authentication token in header:
```
Authorization: Bearer {your_token}
```

---

## 📋 Important Notes

### Teacher's Assigned Subjects:
- API automatically filters timetable based on **teacher's assigned subjects**
- Teacher ko sirf unhi classes/sections ka timetable dikhega jahan unko subject assign hai
- Example: Agar teacher ko "Mathematics" assign hai "5th Class, Section A" mein, to sirf wahi periods dikhenge

### Response Format:
- **Flat List**: All periods in a single array (sorted by day and time)
- **Grouped by Day**: Periods grouped by day (Monday, Tuesday, etc.) for easy display

---

## 📋 API Endpoints

### 1. Get Filter Options

**GET** `/api/teacher/timetable/filter-options`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Description:** Returns campuses, classes, sections, subjects, and days available for the logged-in teacher (filtered by teacher's assigned subjects).

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully.",
  "data": {
    "campuses": [
      "Main Campus",
      "ICMS"
    ],
    "classes": [
      "5th",
      "6th",
      "9th"
    ],
    "subjects": [
      "Mathematics",
      "English",
      "Science"
    ],
    "days": [
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
      "Sunday"
    ]
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access timetable.",
  "token": null
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/timetable/filter-options`

---

### 2. Get Sections by Class

**GET/POST** `/api/teacher/timetable/sections`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body (POST) or Query Parameter (GET):**
```json
{
  "class": "5th"
}
```

**Query Parameter (GET):**
```
GET /api/teacher/timetable/sections?class=5th
```

**Description:** Returns sections available for a specific class (filtered by teacher's assigned subjects).

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Sections retrieved successfully.",
  "data": {
    "sections": [
      "A",
      "B",
      "C"
    ]
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
    "class": [
      "The class field is required."
    ]
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/timetable/sections`

---

### 3. Get Timetable

**GET/POST** `/api/teacher/timetable/list`

**Alternative URL with Date:** `/api/teacher/timetable/list/{day}/{month}/{year}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**URL Parameters (Alternative Format):**
- `day` (integer): Day of the month (1-31, e.g., 5, 15, 25)
- `month` (integer): Month number (1-12, e.g., 1 for January, 12 for December)
- `year` (integer): Year (2000-2100, e.g., 2025)

**Query Parameters (Optional):**
- `campus` (string): Filter by campus name (case-insensitive)
- `class` (string): Filter by class name (case-insensitive)
- `section` (string): Filter by section name (case-insensitive)
- `subject` (string): Filter by subject name (case-insensitive)
- `day` (string): Filter by day (Monday, Tuesday, etc., case-insensitive)
- `month` (integer): Month number (1-12) - used with year to calculate dates
- `year` (integer): Year (2000-2100) - used with month to calculate dates

**Example GET Requests:**
```
GET /api/teacher/timetable/list
GET /api/teacher/timetable/list?class=5th&section=A&day=Monday
GET /api/teacher/timetable/list/5/5/2000
GET /api/teacher/timetable/list/15/4/2025
GET /api/teacher/timetable/list?month=4&year=2025
```

**Example POST Request:**
```json
{
  "class": "5th",
  "section": "A",
  "day": "Monday",
  "month": 4,
  "year": 2025
}
```

**Description:** Returns timetable filtered by teacher's assigned subjects. Automatically shows only periods where teacher has assigned subjects. If `day`, `month`, and `year` are provided in URL (e.g., `/5/5/2000`), the exact date will be used. If only `month` and `year` are provided, the date will be calculated for each timetable entry based on its day name.

**Response (Success - 200) - Without Date:**
```json
{
  "success": true,
  "message": "Timetable retrieved successfully.",
  "data": {
    "teacher": {
      "name": "John Doe",
      "emp_id": "EMP001"
    },
    "timetable": [
      {
        "id": 1,
        "campus": "Main Campus",
        "class": "5th",
        "section": "A",
        "subject": "Mathematics",
        "day": "Monday",
        "starting_time": "09:00:00",
        "ending_time": "10:00:00",
        "starting_time_formatted": "09:00 AM",
        "ending_time_formatted": "10:00 AM"
      }
    ],
    "timetable_by_day": {
      "Monday": [
        {
          "id": 1,
          "campus": "Main Campus",
          "class": "5th",
          "section": "A",
          "subject": "Mathematics",
          "day": "Monday",
          "starting_time": "09:00:00",
          "ending_time": "10:00:00",
          "starting_time_formatted": "09:00 AM",
          "ending_time_formatted": "10:00 AM"
        }
      ]
    },
    "total_periods": 1
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Success - 200) - With Date (when day/month/year provided):**
```json
{
  "success": true,
  "message": "Timetable retrieved successfully.",
  "data": {
    "teacher": {
      "name": "John Doe",
      "emp_id": "EMP001"
    },
    "timetable": [
      {
        "id": 1,
        "campus": "Main Campus",
        "class": "5th",
        "section": "A",
        "subject": "Mathematics",
        "day": "Monday",
        "day_number": 5,
        "date": "2000-05-05",
        "date_formatted": "05 May 2000",
        "starting_time": "09:00:00",
        "ending_time": "10:00:00",
        "starting_time_formatted": "09:00 AM",
        "ending_time_formatted": "10:00 AM"
      }
    ],
    "timetable_by_day": {
      "Monday": [
        {
          "id": 1,
          "campus": "Main Campus",
          "class": "5th",
          "section": "A",
          "subject": "Mathematics",
          "day": "Monday",
          "day_number": 5,
          "date": "2000-05-05",
          "date_formatted": "05 May 2000",
          "starting_time": "09:00:00",
          "ending_time": "10:00:00",
          "starting_time_formatted": "09:00 AM",
          "ending_time_formatted": "10:00 AM"
        }
      ]
    },
    "total_periods": 1,
    "day": 5,
    "month": 5,
    "year": 2000
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (No Assigned Subjects - 200):**
```json
{
  "success": true,
  "message": "No timetable found. You have no assigned subjects.",
  "data": {
    "timetable": [],
    "timetable_by_day": []
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access timetable.",
  "token": null
}
```

**Full URLs:**
- Basic: `https://school.ritpk.com/api/teacher/timetable/list`
- With Filters: `https://school.ritpk.com/api/teacher/timetable/list?class=5th&section=A&day=Monday`
- With Date (URL): `https://school.ritpk.com/api/teacher/timetable/list/5/5/2000`
- With Date (URL): `https://school.ritpk.com/api/teacher/timetable/list/15/4/2025`
- With Date (Query): `https://school.ritpk.com/api/teacher/timetable/list?month=4&year=2025`

---

## 📝 Field Descriptions

### Timetable Fields:
- `id` (integer): Unique timetable ID
- `campus` (string, nullable): Campus name
- `class` (string): Class name (e.g., "5th", "6th")
- `section` (string): Section name (e.g., "A", "B")
- `subject` (string): Subject name
- `day` (string): Day of the week (Monday, Tuesday, etc.)
- `day_number` (integer, optional): Day of the month (1-31) (included when month/year provided)
- `date` (string, optional): Actual date in YYYY-MM-DD format (included when month/year provided)
- `date_formatted` (string, optional): Formatted date (e.g., "07 Apr 2025") (included when month/year provided)
- `starting_time` (time): Period start time (format: HH:MM:SS)
- `ending_time` (time): Period end time (format: HH:MM:SS)
- `starting_time_formatted` (string): Formatted start time (e.g., "09:00 AM")
- `ending_time_formatted` (string): Formatted end time (e.g., "10:00 AM")

### Response Structure:
- `timetable`: Flat array of all periods (sorted by day and time)
- `timetable_by_day`: Object with days as keys, containing arrays of periods for each day
- `total_periods`: Total number of periods
- `month` (optional): Month number (when month/year provided)
- `year` (optional): Year (when month/year provided)

---

## 🔄 Example Flow

1. **Login** to get token:
   ```
   POST /api/teacher/login
   Body: { "email": "teacher@example.com", "password": "password" }
   ```

2. **Get Filter Options**:
   ```
   GET /api/teacher/timetable/filter-options
   Headers: { "Authorization": "Bearer {token}" }
   ```

3. **Get Sections for a Class**:
   ```
   GET /api/teacher/timetable/sections?class=5th
   Headers: { "Authorization": "Bearer {token}" }
   ```

4. **Get Timetable** (all periods):
   ```
   GET /api/teacher/timetable/list
   Headers: { "Authorization": "Bearer {token}" }
   ```

5. **Get Timetable with Filters** (specific class and section):
   ```
   GET /api/teacher/timetable/list?class=5th&section=A
   Headers: { "Authorization": "Bearer {token}" }
   ```

6. **Get Timetable for Specific Day**:
   ```
   GET /api/teacher/timetable/list?day=Monday
   Headers: { "Authorization": "Bearer {token}" }
   ```

---

## ⚠️ Important Behavior

### Automatic Filtering:
- API **automatically filters** timetable based on teacher's assigned subjects
- Teacher ko sirf unhi periods ka timetable dikhega jahan unko subject assign hai
- Example: Agar teacher ko "Mathematics" assign hai "5th Class, Section A" mein, to sirf Mathematics periods dikhenge

### Filter Combinations:
- Multiple filters can be combined (campus, class, section, subject, day)
- All filters are case-insensitive
- Filters work together (AND logic)

### Time Format:
- `starting_time` and `ending_time`: Database format (HH:MM:SS)
- `starting_time_formatted` and `ending_time_formatted`: Human-readable format (h:mm AM/PM)

### Day Ordering:
- Timetable is automatically sorted by day (Monday first, Sunday last)
- Within each day, periods are sorted by starting time

---

## ⚠️ Error Codes

- `200` - Success
- `403` - Access denied (not a teacher)
- `422` - Validation error
- `500` - Server error

---

## 📱 Postman Collection

### Get Filter Options
```
Method: GET
URL: https://school.ritpk.com/api/teacher/timetable/filter-options
Headers:
  Authorization: Bearer {token}
```

### Get Sections by Class
```
Method: GET
URL: https://school.ritpk.com/api/teacher/timetable/sections?class=5th
Headers:
  Authorization: Bearer {token}
```

### Get Timetable (All)
```
Method: GET
URL: https://school.ritpk.com/api/teacher/timetable/list
Headers:
  Authorization: Bearer {token}
```

### Get Timetable (Filtered)
```
Method: GET
URL: https://school.ritpk.com/api/teacher/timetable/list?class=5th&section=A&day=Monday
Headers:
  Authorization: Bearer {token}
```

### Get Timetable with Date (URL Parameters - day/month/year)
```
Method: GET
URL: https://school.ritpk.com/api/teacher/timetable/list/5/5/2000
Headers:
  Authorization: Bearer {token}
```

### Get Timetable with Date (Query Parameters)
```
Method: GET
URL: https://school.ritpk.com/api/teacher/timetable/list?month=4&year=2025
Headers:
  Authorization: Bearer {token}
```

---

## 🎯 Use Cases

### 1. Display Full Weekly Timetable:
- Call `/timetable/list` without filters
- Use `timetable_by_day` to display day-wise
- Shows all periods where teacher has assigned subjects

### 2. Display Schedule for a Specific Date:
- Get day, month, and year (e.g., 5, 5, 2000)
- Call `/timetable/list/5/5/2000`
- Display all periods for that specific date

### 3. Display Schedule for a Month:
- Get month and year (e.g., 4, 2025)
- Call `/timetable/list?month=4&year=2025`
- Display all periods for the month with actual dates calculated for each day

### 3. Display Specific Class/Section Timetable:
- Call `/timetable/list?class=5th&section=A`
- Shows all periods for that specific class and section

### 4. Display Subject-wise Schedule:
- Call `/timetable/list?subject=Mathematics`
- Shows all Mathematics periods across all classes

---

## 📊 Response Examples

### Example 1: Full Timetable
```json
{
  "success": true,
  "message": "Timetable retrieved successfully.",
  "data": {
    "teacher": {
      "name": "John Doe",
      "emp_id": "EMP001"
    },
    "timetable": [
      {
        "id": 1,
        "campus": "Main Campus",
        "class": "5th",
        "section": "A",
        "subject": "Mathematics",
        "day": "Monday",
        "starting_time": "09:00:00",
        "ending_time": "10:00:00",
        "starting_time_formatted": "09:00 AM",
        "ending_time_formatted": "10:00 AM"
      }
    ],
    "timetable_by_day": {
      "Monday": [
        {
          "id": 1,
          "campus": "Main Campus",
          "class": "5th",
          "section": "A",
          "subject": "Mathematics",
          "day": "Monday",
          "starting_time": "09:00:00",
          "ending_time": "10:00:00",
          "starting_time_formatted": "09:00 AM",
          "ending_time_formatted": "10:00 AM"
        }
      ]
    },
    "total_periods": 1
  }
}
```

### Example 2: Filtered by Day
```json
{
  "success": true,
  "message": "Timetable retrieved successfully.",
  "data": {
    "teacher": {
      "name": "John Doe",
      "emp_id": "EMP001"
    },
    "timetable": [
      {
        "id": 1,
        "campus": "Main Campus",
        "class": "5th",
        "section": "A",
        "subject": "Mathematics",
        "day": "Monday",
        "starting_time": "09:00:00",
        "ending_time": "10:00:00",
        "starting_time_formatted": "09:00 AM",
        "ending_time_formatted": "10:00 AM"
      }
    ],
    "timetable_by_day": {
      "Monday": [
        {
          "id": 1,
          "campus": "Main Campus",
          "class": "5th",
          "section": "A",
          "subject": "Mathematics",
          "day": "Monday",
          "starting_time": "09:00:00",
          "ending_time": "10:00:00",
          "starting_time_formatted": "09:00 AM",
          "ending_time_formatted": "10:00 AM"
        }
      ]
    },
    "total_periods": 1
  }
}
```

