# Self Attendance API - Postman URLs

## Base URL
```
https://school.ritpk.com/api/teacher
```

---

## 1. Mark Self Attendance (with Class & Section)

### POST Request
```
POST https://school.ritpk.com/api/teacher/self-attendance/mark
```

### Headers
```
Authorization: Bearer {your_token_here}
Content-Type: application/json
```

### Request Body (JSON)
```json
{
    "attendance_date": "2025-12-04",
    "status": "Present",
    "class": "Ninth",
    "section": "A",
    "start_time": "09:00:00",
    "end_time": "17:00:00",
    "remarks": "Optional remarks here"
}
```

### Example with GET (Query Parameters)
```
GET https://school.ritpk.com/api/teacher/self-attendance/mark?attendance_date=2025-12-04&status=Present&class=Ninth&section=A&start_time=09:00:00&end_time=17:00:00
```

### Response
```json
{
    "success": true,
    "message": "Self-attendance marked successfully",
    "data": {
        "attendance": {
            "id": 1,
            "staff_id": 3,
            "name": "talha",
            "emp_id": "EMP001",
            "designation": "teacher",
            "attendance_date": "2025-12-04",
            "status": "Present",
            "class": "Ninth",
            "section": "A",
            "start_time": "09:00:00",
            "end_time": "17:00:00",
            "campus": "icms",
            "remarks": "Optional remarks here"
        }
    },
    "token": "your_token_here"
}
```

---

## 2. Check Today's Self Attendance

### GET Request
```
GET https://school.ritpk.com/api/teacher/self-attendance/check
```

### Headers
```
Authorization: Bearer {your_token_here}
```

### Query Parameters (Optional)
```
?date=2025-12-04
```

### Full URL Example
```
GET https://school.ritpk.com/api/teacher/self-attendance/check?date=2025-12-04
```

### Response
```json
{
    "success": true,
    "message": "Attendance status retrieved successfully",
    "data": {
        "teacher": {
            "id": 3,
            "name": "talha",
            "emp_id": "EMP001",
            "designation": "teacher",
            "campus": "icms"
        },
        "date": "2025-12-04",
        "is_marked": true,
        "attendance": {
            "id": 1,
            "status": "Present",
            "class": "Ninth",
            "section": "A",
            "start_time": "09:00:00",
            "end_time": "17:00:00",
            "remarks": "Optional remarks",
            "marked_at": "2025-12-04 09:30:00"
        }
    },
    "token": "your_token_here"
}
```

---

## 3. Check-In API

### POST Request
```
POST https://school.ritpk.com/api/teacher/self-attendance/check-in
```

### Headers
```
Authorization: Bearer {your_token_here}
Content-Type: application/json
```

### Request Body (JSON)
```json
{
    "attendance_date": "2025-12-04",
    "start_time": "15:00:00",
    "status": "Present",
    "class": "Ninth",
    "section": "A",
    "remarks": "Optional remarks here"
}
```

### Example with GET (Query Parameters)
```
GET https://school.ritpk.com/api/teacher/self-attendance/check-in?attendance_date=2025-12-04&start_time=15:00:00&status=Present&class=Ninth&section=A
```

### Response
```json
{
    "success": true,
    "message": "Check-in successful",
    "data": {
        "attendance": {
            "id": 1,
            "staff_id": 3,
            "name": "talha",
            "emp_id": "EMP001",
            "designation": "teacher",
            "attendance_date": "2025-12-04",
            "status": "Present",
            "class": "Ninth",
            "section": "A",
            "start_time": "15:00:00",
            "end_time": null,
            "campus": "icms",
            "remarks": "Optional remarks here"
        }
    }
}
```

### Important Notes
- **Required Fields:** `attendance_date`, `start_time`
- **Optional Fields:** `status`, `class`, `section`, `remarks`
- **Validation:** Agar already check-in ho chuka hai to error: "You have already checked in today"
- **Time Format:** `HH:MM:SS` (e.g., `15:00:00`) or `HH:MM` (e.g., `15:00`)

---

## 4. Check-Out API

### POST Request
```
POST https://school.ritpk.com/api/teacher/self-attendance/check-out
```

### Headers
```
Authorization: Bearer {your_token_here}
Content-Type: application/json
```

### Request Body (JSON)
```json
{
    "attendance_date": "2025-12-04",
    "end_time": "16:00:00",
    "remarks": "Optional remarks here"
}
```

### Example with GET (Query Parameters)
```
GET https://school.ritpk.com/api/teacher/self-attendance/check-out?attendance_date=2025-12-04&end_time=16:00:00
```

### Response
```json
{
    "success": true,
    "message": "Check-out successful",
    "data": {
        "attendance": {
            "id": 1,
            "staff_id": 3,
            "name": "talha",
            "emp_id": "EMP001",
            "designation": "teacher",
            "attendance_date": "2025-12-04",
            "status": "Present",
            "class": "Ninth",
            "section": "A",
            "end_time": "16:00:00",
            "campus": "icms",
            "remarks": "Optional remarks here",
            "total_hours": 1,
            "total_minutes": 0,
            "total_time": "1 hours 0 minutes"
        }
    }
}
```

### Important Notes
- **Required Fields:** `attendance_date`, `end_time`
- **Optional Fields:** `remarks`
- **Validation:** 
  - Check-in pehle hona chahiye, warna error: "Check-in is required first"
  - End time start time se baad hona chahiye
  - Agar already check-out ho chuka hai to error: "You have already checked out today"
- **Time Format:** `HH:MM:SS` (e.g., `16:00:00`) or `HH:MM` (e.g., `16:00`)
- **Response:** 
  - Sirf `end_time` return hota hai, `start_time` nahi
  - `total_hours`: Check-in se check-out tak total hours
  - `total_minutes`: Check-in se check-out tak total minutes
  - `total_time`: Formatted string (e.g., "1 hours 0 minutes")

### Example Flow
1. **Check-In at 3 PM:**
```json
POST /api/teacher/self-attendance/check-in
{
    "attendance_date": "2025-12-04",
    "start_time": "15:00:00"
}
```

2. **Check-Out at 4 PM:**
```json
POST /api/teacher/self-attendance/check-out
{
    "attendance_date": "2025-12-04",
    "end_time": "16:00:00"
}
```

---

## 5. Get Self Attendance History

### GET Request
```
GET https://school.ritpk.com/api/teacher/self-attendance/history
```

### Headers
```
Authorization: Bearer {your_token_here}
```

### Query Parameters (Optional)
```
?start_date=2025-12-01&end_date=2025-12-31&status=Present&per_page=30
```

### Full URL Examples
```
GET https://school.ritpk.com/api/teacher/self-attendance/history
GET https://school.ritpk.com/api/teacher/self-attendance/history?start_date=2025-12-01&end_date=2025-12-31
GET https://school.ritpk.com/api/teacher/self-attendance/history?status=Present&per_page=50
```

### Response
```json
{
    "success": true,
    "message": "Self-attendance history retrieved successfully",
    "data": {
        "teacher": {
            "id": 3,
            "name": "talha",
            "emp_id": "EMP001",
            "designation": "teacher",
            "campus": "icms"
        },
        "statistics": {
            "total_days": 20,
            "present_days": 18,
            "absent_days": 2,
            "attendance_percentage": 90.0
        },
        "attendances": [
            {
                "id": 1,
                "attendance_date": "2025-12-04",
                "status": "Present",
                "class": "Ninth",
                "section": "A",
                "start_time": "09:00:00",
                "end_time": "17:00:00",
                "campus": "icms",
                "remarks": "Optional remarks",
                "marked_at": "2025-12-04 09:30:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 30,
            "total": 20
        }
    },
    "token": "your_token_here"
}
```

---

## Status Values
- `Present`
- `Absent`
- `Holiday`
- `Sunday`
- `Leave`
- `N/A`

---

## Notes
- `class` and `section` are **optional** fields
- If not provided, they will be saved as `null`
- Both GET and POST methods are supported for marking attendance
- Time format: `HH:MM:SS` (e.g., `09:00:00`) or `HH:MM` (e.g., `09:00`)

