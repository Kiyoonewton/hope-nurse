# Online Examination System

## Technology Stack

| Technology | Version |
|------------|---------|
| PHP | 7.4+ |
| MySQL | 5.7+ |
| Bootstrap | 4.5.2 |
| jQuery | 3.5.1 |
| HTML5 / CSS3 | - |
| Font Awesome | 5.15.4 |

---

## Setup Instructions

**1. Configure Database** - Edit `config/database.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'exam_system');
```

**2. Import Database:**
```bash
mysql -u your_username -p exam_system < database/exam_system.sql
```

**3. Run Application:**
```bash
php -S localhost:8000
```
Access: `http://localhost:8000/`

---

## Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Student | `student1` | `student123` |
| Student | `student2` | `student123` |

---

## Features Completed

**Core Features**
- User Authentication (Login/Register/Logout)
- Role-based Access Control (Admin/Student)
- Exam CRUD Operations
- Question CRUD (5 types: Multiple Choice, Multiple Select, True/False, Short Answer, Fill Blank)
- Timed Exam Taking with Auto-submit
- Answer Auto-save
- Exam Scoring & Results Review
- Student Management
- Configurable Passing Percentage

**Security Features**
- Password Hashing (bcrypt)
- CSRF Protection
- SQL Injection Prevention (Prepared Statements)
- XSS Prevention
- Session Security
- Input Validation
- Protected Routes
- Retake Prevention

**Bonus Features**
- Analytics Dashboard
- Tab Switch Detection (Anti-Cheating)
- Page Refresh Resilience
- Responsive Design

---

## Database Files

| File | Purpose |
|------|---------|
| `database/exam_system.sql` | Main schema + seed data |
| `database/fix_passwords.sql` | Password migration |

**Tables:** `users`, `exams`, `questions`, `question_options`, `exam_attempts`, `student_answers`

---

## Contact

kiyoonewton41@gmail.com
