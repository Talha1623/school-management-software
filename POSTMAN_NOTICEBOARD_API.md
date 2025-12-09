# Noticeboard API Documentation

**Base URL:** `https://school.ritpk.com/api/teacher`

---

## 🔐 Authentication

All endpoints require authentication token in header:
```
Authorization: Bearer {your_token}
```

---

## 📋 Important Notes

### Show On Feature:
- **Super Admin** can set `show_on` to "Yes" or "No" when creating/editing notices
- **Staff/Teachers** can only see notices where `show_on = 'Yes'`
- If `show_on = 'No'`, the notice will NOT be visible to staff through the API
- This allows Super Admin to control which notices are visible to staff

---

## 📋 API Endpoints

### 1. Get Noticeboard List

**GET** `/api/teacher/noticeboard/list`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (Optional):**
- `campus` (string): Filter by campus name (case-insensitive)
- `search` (string): Search in title, campus, or notice content
- `start_date` (date, format: YYYY-MM-DD): Filter notices from this date onwards
- `end_date` (date, format: YYYY-MM-DD): Filter notices up to this date
- `per_page` (integer): Number of records per page (10, 25, 30, 50, 100). Default: 30
- `page` (integer): Page number for pagination

**Note:** This API automatically filters to show only notices where `show_on = 'Yes'`. Notices with `show_on = 'No'` will NOT be returned.

**Example GET Request:**
```
GET /api/teacher/noticeboard/list?campus=Main Campus&per_page=25&page=1
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Noticeboard list retrieved successfully",
  "data": {
    "noticeboards": [
      {
        "id": 1,
        "campus": "Main Campus",
        "title": "Holiday Notice",
        "notice": "School will be closed on 25th December",
        "date": "2025-12-20",
        "date_formatted": "20 Dec 2025",
        "image": "https://school.ritpk.com/storage/noticeboards/image.jpg",
        "show_on": "Yes",
        "created_at": "2025-12-08 10:30:00",
        "updated_at": "2025-12-08 10:30:00"
      },
      {
        "id": 2,
        "campus": "Branch Campus",
        "title": "Parent-Teacher Meeting",
        "notice": "PTM scheduled for next week",
        "date": "2025-12-15",
        "date_formatted": "15 Dec 2025",
        "image": null,
        "show_on": "Yes",
        "created_at": "2025-12-05 14:20:00",
        "updated_at": "2025-12-05 14:20:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 30,
      "total": 75,
      "from": 1,
      "to": 30
    }
  }
}
```

**Response (Access Denied - 403):**
```json
{
  "success": false,
  "message": "Access denied. Only teachers can view noticeboard."
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/noticeboard/list`

---

### 2. Get Single Noticeboard

**GET** `/api/teacher/noticeboard/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Note:** This API automatically filters to show only notices where `show_on = 'Yes'`. If a notice has `show_on = 'No'`, it will return 404.

**Example GET Request:**
```
GET /api/teacher/noticeboard/1
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Noticeboard retrieved successfully",
  "data": {
    "noticeboard": {
      "id": 1,
      "campus": "Main Campus",
      "title": "Holiday Notice",
      "notice": "School will be closed on 25th December",
      "date": "2025-12-20",
      "date_formatted": "20 Dec 2025",
      "image": "https://school.ritpk.com/storage/noticeboards/image.jpg",
      "show_on": "Yes",
      "created_at": "2025-12-08 10:30:00",
      "updated_at": "2025-12-08 10:30:00"
    }
  }
}
```

**Response (Not Found - 404):**
```json
{
  "success": false,
  "message": "Noticeboard not found"
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/noticeboard/1`

---

### 3. Get Filter Options

**GET** `/api/teacher/noticeboard/filter-options`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully",
  "data": {
    "campuses": [
      "Main Campus",
      "Branch Campus",
      "ICMS"
    ],
    "show_on_options": [
      "Yes",
      "No"
    ]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/noticeboard/filter-options`

---

## 📝 Field Descriptions

### Noticeboard Fields:
- `id` (integer): Unique noticeboard ID
- `campus` (string, nullable): Campus name
- `title` (string, required): Notice title
- `notice` (text, nullable): Notice content/description
- `date` (date, required): Notice date (format: YYYY-MM-DD)
- `image` (string, nullable): Full URL to notice image (if uploaded)
- `show_on` (string): Visibility status - "Yes" or "No"
  - **"Yes"**: Notice is visible to staff through API
  - **"No"**: Notice is NOT visible to staff through API (only Super Admin can see)
- `created_at` (datetime): Creation timestamp
- `updated_at` (datetime): Last update timestamp

---

## 🔄 Example Flow

1. **Login** to get token:
   ```
   POST /api/teacher/login
   Body: { "email": "teacher@example.com", "password": "password" }
   ```

2. **Get Noticeboard List** (only shows notices with show_on = 'Yes'):
   ```
   GET /api/teacher/noticeboard/list?campus=Main Campus
   Headers: { "Authorization": "Bearer {token}" }
   ```

3. **Get Single Notice** (only if show_on = 'Yes'):
   ```
   GET /api/teacher/noticeboard/1
   Headers: { "Authorization": "Bearer {token}" }
   ```

---

## ⚠️ Important Behavior

### Show On Filtering:
- **API automatically filters** to show only notices where `show_on = 'Yes'`
- Notices with `show_on = 'No'` are **completely hidden** from staff
- This filtering happens automatically - you don't need to pass any parameter
- Super Admin controls visibility through the web interface

### Image URLs:
- If an image is uploaded, the `image` field contains the full URL
- Image URL format: `https://school.ritpk.com/storage/noticeboards/{filename}`
- If no image is uploaded, `image` will be `null`

### Date Format:
- All dates are in `YYYY-MM-DD` format
- `date_formatted` provides a human-readable format (e.g., "20 Dec 2025")

---

## ⚠️ Error Codes

- `200` - Success
- `403` - Access denied (not a teacher)
- `404` - Noticeboard not found (or show_on = 'No')
- `500` - Server error

---

## 📱 Postman Collection

### Get Noticeboard List
```
Method: GET
URL: https://school.ritpk.com/api/teacher/noticeboard/list?campus=Main Campus&per_page=25
Headers:
  Authorization: Bearer {token}
```

### Get Single Noticeboard
```
Method: GET
URL: https://school.ritpk.com/api/teacher/noticeboard/1
Headers:
  Authorization: Bearer {token}
```

### Get Filter Options
```
Method: GET
URL: https://school.ritpk.com/api/teacher/noticeboard/filter-options
Headers:
  Authorization: Bearer {token}
```

---

## 🎯 Super Admin Features (Web Interface)

Super Admin can:
1. **Create Notice**: Add new notice with Campus, Title, Notice, Date, Image, and Show On
2. **Edit Notice**: Update existing notice details
3. **Delete Notice**: Remove notice permanently
4. **Control Visibility**: Set `show_on` to "Yes" to make it visible to staff, or "No" to hide it

**Note:** Only notices with `show_on = 'Yes'` will be visible to staff through the API.

