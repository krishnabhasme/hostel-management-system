# Sipna College Hostel Management System

A modern, responsive, and robust **Hostel Management System** developed as a 3rd-year DBMS Mini Project. It features a complete relational database backend built strictly according to the system's Entity-Relationship (ER) Diagram, dynamic real-time occupancy and fee tracking, and a clean user interface.

---

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [Technologies Used](#technologies-used)
3. [System Architecture & ER Diagram](#system-architecture--er-diagram)
4. [Features](#features)
5. [Project Structure](#project-structure)
6. [Prerequisites & XAMPP Setup](#prerequisites--xampp-setup)
7. [Database Setup](#database-setup)
8. [Running the Application](#running-the-application)
9. [Default Credentials](#default-credentials)
10. [License & Credits](#license--credits)

---

## 📌 Project Overview
The **Sipna College Hostel Management System** simplifies hostel operations for administrators and wardens. The system tracks student allocations across blocks and rooms, enforces bed capacity constraints, manages fee payments with printable receipts, and resolves student maintenance complaint tickets.

---

## 🛠️ Technologies Used
- **Backend**: PHP 8.x (using PHP PDO with secure prepared statements)
- **Database**: MySQL / MariaDB (relational schema with primary keys, foreign keys, and ON DELETE CASCADE constraints)
- **Frontend**: HTML5, CSS3, Tailwind CSS & Bootstrap 5 design tokens, Material Symbols Icons
- **Scripting**: Vanilla JavaScript (ES6) for modal dialogs, search filters, and receipt printing
- **Local Environment**: XAMPP (Apache + MySQL)

---

## 🗄️ System Architecture & ER Diagram

The database schema strictly adheres to the 7 core relational entities:

| Table | Primary Key | Foreign Keys | Key Attributes |
| :--- | :--- | :--- | :--- |
| **`ADMIN`** | `username` (PK) | — | `password`, `full_name` |
| **`BLOCKS`** | `block_id` (PK) | — | `block_name`, `type`, `warden_name`, `warden_contact` |
| **`ROOMS`** | `room_id` (PK) | `block_id` → `BLOCKS.block_id` | `room_number`, `room_type`, `capacity`, `fee`, `status` |
| **`STUDENTS`** | `student_id` (PK) | — | `name`, `email`, `contact`, `gender`, `department`, `year`, `address`, `guardian_name`, `guardian_contact`, `status` |
| **`ALLOCATIONS`** | `allocation_id` (PK) | `student_id` → `STUDENTS.student_id`,<br>`room_id` → `ROOMS.room_id` | `bed`, `allocation_date`, `status` |
| **`FEES`** | `receipt_no` (PK) | `student_id` → `STUDENTS.student_id` | `amount`, `payment_date`, `payment_method`, `status` |
| **`COMPLAINTS`** | `ticket_no` (PK) | `student_id` → `STUDENTS.student_id` | `category`, `subject`, `description`, `priority`, `status`, `created_at` |

---

## ✨ Features
- **Dashboard Overview**: Live KPI cards showing Total Students, Available Rooms, Bed Occupancy Percentage, Pending Fees, and Active Complaints.
- **Hostel Blocks Management**: Add, view, and delete hostel blocks (e.g., Boys / Girls blocks) with warden contact information and live room count.
- **Rooms Management**: Create rooms linked to specific blocks, manage capacities (Single, Double, Triple), set annual fees, filter by block/type/status.
- **Student Directory & Profile Dossier**: Register students with academic and guardian details; dedicated student dossier displaying room allocation, complete fee transaction history, and maintenance complaints.
- **Room Allocation Engine**: Select from unallocated active students and rooms with free beds; automatically updates room occupancy and bed status; one-click revoke/discharge.
- **Fee Management & Printable Receipt**: Record fee payments, calculate collections and pending dues, and view/print formatted fee receipts.
- **Complaint Ticket Resolution**: File student complaints with categories (Plumbing, Electrical, IT Support, etc.), prioritize urgency, and update ticket progress (`Open` → `In Progress` → `Resolved`).
- **Secure Authentication**: Session-based admin login with flash notifications.

---

## 📂 Project Structure
```text
project/
├── .gitignore               # Ignored files, logs, and IDE caches
├── README.md                # Project documentation
├── database.sql             # SQL script to create database, tables & sample data
├── index.php                # Entry redirect to dashboard or login
├── login.php                # Admin authentication portal
├── logout.php               # Destroys session and logs out
├── dashboard.php            # Main administrative analytics dashboard
├── hostel_blocks.php        # Hostel block management
├── rooms.php                # Room configuration & capacity tracking
├── students.php             # Student registration and directory
├── student_profile.php      # Student dossier (personal, room, fees, complaints)
├── allocations.php          # Student room & bed allocation
├── fees.php                 # Fee payment logging & receipt modal
├── complaints.php           # Maintenance ticket tracking
├── export.php               # Helper redirect handler
├── settings.php             # Helper redirect handler
├── includes/
│   ├── db.php               # PDO database connection
│   ├── functions.php        # Helper utilities, session auth, flash messages
│   ├── header.php          # Shared HTML <head>, stylesheets & tokens
│   ├── footer.php          # Shared scripts & footer closing tags
│   ├── sidebar.php         # Left responsive navigation sidebar
│   └── topbar.php          # Top search bar and user profile indicator
├── css/                     # CSS stylesheets & theme variables
├── js/                      # JavaScript helpers
└── images/                  # Institutional logos and graphic assets
```

---

## 💻 Prerequisites & XAMPP Setup

1. **Install XAMPP**:
   - Download and install [XAMPP for Windows](https://www.apachefriends.org/) (PHP 8.0 or higher recommended).
2. **Start Services**:
   - Open the **XAMPP Control Panel**.
   - Start both **Apache** and **MySQL**.

---

## 🗄️ Database Setup

1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab at the top menu.
3. Click **Choose File** and select `database.sql` from the project directory.
4. Click **Import** (or **Go**) at the bottom.
5. Alternatively, via MySQL command line:
   ```bash
   mysql -u root -p < database.sql
   ```
   *(By default, XAMPP MySQL user is `root` with no password).*

---

## 🚀 Running the Application

1. Clone or copy this repository into your XAMPP `htdocs` directory:
   ```text
   C:\xampp\htdocs\project\
   ```
2. Open your web browser and visit:
   ```
   http://localhost/project/
   ```
3. You will be redirected to the Admin Login page.

---

## 🔑 Default Credentials

- **Username**: `admin`
- **Password**: `admin123`

---

## 📝 DBMS Academic Relevance
- Demonstrates relational database design and normalization (1NF, 2NF, 3NF).
- Strict adherence to Primary Key (PK) and Foreign Key (FK) constraints with referential integrity (`ON DELETE CASCADE`).
- Uses parameterized PDO queries to ensure protection against SQL injection attacks.
- Real-time aggregation queries (`COUNT`, `SUM`, `COALESCE`, `LEFT JOIN`, `INNER JOIN`) to model real-world hostel operations.
