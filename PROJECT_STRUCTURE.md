# Project Structure

```
hope-nurse/
│
├── admin/                          # Admin Panel Pages
│   ├── index.php                   # Admin dashboard with statistics
│   ├── exams.php                   # Create, edit, delete exams
│   ├── questions.php               # Manage questions for exams
│   └── students.php                # Manage student accounts
│
├── api/                            # AJAX API Endpoints
│   ├── get-question-options.php    # Fetch options for a question
│   └── save-answer.php             # Auto-save student answers
│
├── assets/                         # Static Assets
│   ├── css/
│   │   └── style.css               # Custom CSS styles (2500+ lines)
│   └── js/
│       └── questions.js            # Question management JavaScript
│
├── auth/                           # Authentication Pages
│   ├── login.php                   # User login page
│   ├── register.php                # Student registration page
│   └── logout.php                  # Logout handler
│
├── config/                         # Configuration Files
│   └── database.php                # Database connection settings
│
├── database/                       # Database Files
│   ├── exam_system.sql             # Main database schema + seed data
│   ├── fix_passwords.sql           # Password fix migration
│   └── migration_fix_student_answers.sql  # Answer table migration
│
├── includes/                       # Shared PHP Includes
│   ├── functions.php               # Helper functions (formatting, validation)
│   ├── session.php                 # Session management & authentication
│   └── favicon.php                 # Favicon handler
│
├── student/                        # Student Panel Pages
│   ├── index.php                   # Student dashboard (available exams)
│   ├── exam-instructions.php       # Pre-exam instructions page
│   ├── take-exam.php               # Exam taking interface with timer
│   ├── submit-exam.php             # Exam submission & grading
│   ├── exam-review.php             # Review submitted exam answers
│   ├── my-results.php              # View personal exam history
│   └── results.php                 # Admin: View all student results
│
├── index.php                       # Landing page (redirects to login)
├── README.md                       # Project documentation
└── PROJECT_STRUCTURE.md            # This file
```

## Directory Descriptions

### `/admin` - Admin Panel
Contains all administrative functions for exam managers:
- **index.php** - Dashboard showing total exams, questions, students, and recent activity
- **exams.php** - Full CRUD for exams (title, duration, passing %, status, retake settings)
- **questions.php** - Manage questions with 5 types (multiple choice, multiple select, true/false, short answer, fill blank)
- **students.php** - View, create, edit, delete, and toggle student account status

### `/api` - AJAX Endpoints
RESTful endpoints for asynchronous operations:
- **save-answer.php** - POST endpoint to auto-save answers during exam (prevents data loss)
- **get-question-options.php** - GET endpoint to fetch question options for editing

### `/assets` - Static Files
Frontend resources:
- **css/style.css** - Complete custom styling with CSS variables, responsive design, animations
- **js/questions.js** - Question form handling, validation, dynamic option management

### `/auth` - Authentication
User authentication flow:
- **login.php** - Login form with username/email support, role-based redirect
- **register.php** - Student self-registration with validation
- **logout.php** - Session destruction and redirect

### `/config` - Configuration
Application settings:
- **database.php** - MySQL connection (host, port, user, password, database name)

### `/database` - Database Files
SQL scripts for database setup:
- **exam_system.sql** - Complete schema with 6 tables + seed data (admin & sample students)
- **fix_passwords.sql** - Migration to fix password hashes
- **migration_fix_student_answers.sql** - Schema updates for answer tracking

### `/includes` - Shared Components
Reusable PHP code:
- **functions.php** - Helper functions: `formatDate()`, `formatDuration()`, `sanitizeInput()`, `generateCSRFToken()`, badge helpers
- **session.php** - Session start, authentication checks (`requireLogin()`, `requireAdmin()`, `requireStudent()`)
- **favicon.php** - Dynamic favicon generation

### `/student` - Student Panel
Student-facing exam functionality:
- **index.php** - Dashboard listing available/active exams with attempt status
- **exam-instructions.php** - Pre-exam page with rules, duration, question count
- **take-exam.php** - Main exam interface with countdown timer, question navigation, auto-save
- **submit-exam.php** - Handles submission, calculates score, stores results
- **exam-review.php** - Post-exam review showing correct/incorrect answers
- **my-results.php** - Personal exam history with pass/fail status
- **results.php** - (Admin access) View all student results with filters

## File Count Summary

| Directory | Files | Description |
|-----------|-------|-------------|
| `/admin` | 4 | Admin panel pages |
| `/api` | 2 | AJAX endpoints |
| `/assets/css` | 1 | Stylesheets |
| `/assets/js` | 1 | JavaScript |
| `/auth` | 3 | Authentication |
| `/config` | 1 | Configuration |
| `/database` | 3 | SQL scripts |
| `/includes` | 3 | Shared PHP |
| `/student` | 7 | Student pages |
| Root | 3 | Entry + docs |
| **Total** | **28** | **All files** |

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | Admin and student accounts |
| `exams` | Exam metadata |
| `questions` | Questions linked to exams |
| `question_options` | Options for multiple choice questions |
| `exam_attempts` | Student exam attempts |
| `student_answers` | Individual answers per attempt |
