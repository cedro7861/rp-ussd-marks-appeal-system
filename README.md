# USSD-Based Marks Appeal and Module Management System

This project is a USSD-based system for **Rwanda Polytechnic** that allows students to check their marks, submit appeals, and enables administrators to manage modules, marks, and appeals via USSD. The system is built using **PHP**, **MySQL**, and **Africa's Talking USSD API**.

---

## Features

### Student Functions
- Check marks by module
- Submit a marks appeal
- View appeal status

### Admin Functions
- Manage Modules (Add, Update, Delete)
- Manage Marks (Add, Delete)
- Manage Appeals (View, Update status, Delete)
- Displays IDs from the database to avoid invalid inputs

---

## Database Tables

### 1. `students`
- `student_id` (PK)
- `name`
- `phone`

### 2. `modules`
- `module_id` (PK)
- `module_name`

### 3. `marks`
- `mark_id` (PK)
- `student_id` (FK)
- `module_id` (FK)
- `mark`

### 4. `appeals`
- `appeal_id` (PK)
- `student_id` (FK)
- `module_id` (FK)
- `reason`
- `status_id` (FK)

### 5. `appeal_status`
- `status_id` (PK)
- `status_name` (e.g., Pending, Under Review, Resolved)

---

##  How It Works

1. Africa's Talking USSD gateway sends requests to your `index.php`.
2. The system parses the `text` input using `*` as a delimiter.
3. Based on input and role (Student or Admin), it returns USSD menu responses.
4. Menus are dynamic and updated based on the data in the database.

---

##  Sample USSD Flow (Admin)

Dial: *123#

CON Admin Menu:

Manage Appeals

Manage Marks

Manage Modules

Exit

2
CON Marks:

Add Mark

Delete Mark

Back

Exit


---

## 🧾 Setup Instructions

1. Clone this repo and place it in your server directory (`htdocs` or `www`).
2. Create a MySQL database and import the tables (`students`, `modules`, `marks`, `appeals`, `appeal_status`).
3. Update your `db.php` with correct DB credentials:
   ```php
   $conn = new PDO("mysql:host=localhost;dbname=your_db", "username", "password");
