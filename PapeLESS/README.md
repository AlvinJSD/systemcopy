# PapeLESS – OJT Document Tracking System
### Polytechnic University of the Philippines – Sto. Tomas Campus

A capstone-level, paperless internship management system built with HTML5, CSS3, Bootstrap 5, PHP, and MySQL.

---

## 📁 Project Structure

```
PapeLESS/
├── index.php                     ← Landing page (Login + Student Signup)
├── .htaccess                     ← Apache security rules
├── config/
│   └── database.php              ← DB connection & constants
├── includes/
│   └── functions.php             ← All helper functions
├── auth/
│   └── auth_handler.php          ← Login / Signup / Logout
├── components/
│   ├── header.php                ← Topbar + Sidebar shell
│   ├── footer.php                ← Closing tags + JS
│   ├── nav_student.php           ← Student sidebar links
│   ├── nav_adviser.php           ← Adviser sidebar links
│   └── nav_coordinator.php       ← Coordinator sidebar links
├── student/
│   ├── dashboard.php             ← Student home + survey popup
│   ├── submissions.php           ← Upload & track documents
│   ├── submission_handler.php    ← AJAX: file upload
│   ├── survey.php                ← Daily well-being survey
│   ├── survey_handler.php        ← AJAX: save survey
│   ├── messages.php              ← Message adviser
│   ├── announcements.php         ← View announcements
│   ├── guidelines.php            ← OJT guidelines & templates
│   ├── profile.php               ← Edit profile & internship info
│   └── notifications.php         ← All notifications
├── adviser/
│   ├── dashboard.php             ← Adviser home
│   ├── students.php              ← Manage assigned students
│   ├── student_profile.php       ← Full student view
│   ├── submissions.php           ← Review files
│   ├── submission_handler.php    ← AJAX: approve/reject
│   ├── surveys.php               ← Monitor well-being
│   ├── messages.php              ← Message students
│   ├── announcements.php         ← View announcements
│   ├── reports.php               ← Generate reports
│   ├── profile.php               ← Edit profile
│   └── notifications.php         ← All notifications
├── coordinator/
│   ├── dashboard.php             ← System-wide analytics
│   ├── advisers.php              ← Create/manage advisers
│   ├── adviser_handler.php       ← AJAX: adviser CRUD
│   ├── students.php              ← View all students
│   ├── announcements.php         ← Post announcements
│   ├── announcement_handler.php  ← AJAX: announcement CRUD
│   ├── reports.php               ← System reports (print)
│   ├── settings.php              ← System settings
│   ├── profile.php               ← Edit profile
│   └── notifications.php         ← All notifications
├── api/
│   └── notifications.php         ← Mark notifications read
├── assets/
│   ├── css/main.css              ← All styles
│   ├── js/main.js                ← All scripts
│   ├── images/                   ← Logo & placeholders
│   └── uploads/submissions/      ← Student uploaded files
└── database/
    └── papeless.sql              ← Full schema + sample data
```

---

## ⚙️ Setup Instructions

### Requirements
- **XAMPP** (PHP 8.0+ | Apache | MySQL)
- Web browser (Chrome, Firefox, Edge)

### Step 1 – Copy Files
Place the entire `PapeLESS/` folder inside:
```
C:\xampp\htdocs\PapeLESS\
```

### Step 2 – Import Database
1. Start **XAMPP** → Start **Apache** and **MySQL**
2. Open your browser and go to: `http://localhost/phpmyadmin`
3. Click **New** → Create database named: `papeless_db`
4. Select `papeless_db` → Click **Import** tab
5. Choose file: `PapeLESS/database/papeless.sql`
6. Click **Go** / **Import**

### Step 3 – Configure (if needed)
Open `config/database.php` and verify:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // your MySQL password if set
define('DB_NAME', 'papeless_db');
define('BASE_URL', 'http://localhost/PapeLESS');
```

### Step 4 – Create Upload Folders
The system auto-creates upload folders, but you can manually create:
```
PapeLESS/assets/uploads/submissions/
PapeLESS/assets/uploads/attachments/
PapeLESS/assets/uploads/profile_photos/
```
Ensure these folders have **write permissions**.

### Step 5 – Access the System
Open your browser: `http://localhost/PapeLESS`

---

## 🔑 Demo Accounts

| Role        | Email                                  | Password    |
|-------------|----------------------------------------|-------------|
| Coordinator | coordinator@papeless.edu.ph            | Admin@123   |
| Adviser     | adviser.bsit3@papeless.edu.ph          | Adviser@123 |
| Adviser     | adviser.bsit4@papeless.edu.ph          | Adviser@123 |
| Student     | jose.mercado@student.edu.ph            | Student@123 |
| Student     | andres.deleon@student.edu.ph           | Student@123 |

---

## 🧩 Features by Role

### 👩‍🎓 Student
- Register with personal, academic, and internship info
- Account pending until adviser approves
- Upload OJT documents (PDF, DOCX, images, ZIP)
- Track submission status (Pending → Approved / Needs Revision)
- Daily well-being survey popup
- Message adviser
- View announcements & guidelines

### 👨‍🏫 Adviser
- Approve or reject student registrations
- View all assigned students with full profiles
- Review and approve/reject submitted documents with comments
- Monitor daily well-being survey responses
- Alert notifications for students needing consultation
- Message students
- Generate student performance reports

### 🧑‍💼 Coordinator
- Full system control
- Create, edit, delete adviser accounts
- Assign advisers by program and year level
- View all students system-wide
- Post/manage announcements (with file attachments)
- System-wide analytics dashboard
- Generate printable reports (students, advisers, surveys)
- Manage system settings (academic year, deadline, file size)

---

## 🔐 Security Features
- BCrypt password hashing (cost 12)
- CSRF token protection on all forms
- SQL injection prevention (PDO prepared statements)
- Session timeout (1 hour)
- File upload validation (extension + MIME type)
- Role-based authentication
- Secure session cookie settings

---

## 🛠️ Tech Stack
| Layer       | Technology                    |
|-------------|-------------------------------|
| Frontend    | HTML5, CSS3, Bootstrap 5.3    |
| Icons       | Bootstrap Icons 1.11          |
| Backend     | PHP 8.0+                      |
| Database    | MySQL 8.0 (via PDO)           |
| Server      | Apache (XAMPP)                |
| Fonts       | Google Fonts – Inter          |

---

## 👥 Developers
PUP Sto. Tomas Campus – Capstone Project  
Academic Year 2024-2025

---

*PapeLESS – Going paperless, one document at a time.*
