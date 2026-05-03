# QueueSense: AI-Assisted Smart Queue & Crowd Flow Management System

[![Version](https://img.shields.io/badge/Version-1.0.0--Alpha-blue.svg)](https://github.com/ronaldllamo26/LMS)
[![Tech Stack](https://img.shields.io/badge/Stack-PHP%20%7C%20MySQL%20%7C%20Bootstrap-orange.svg)]()
[![Institution](https://img.shields.io/badge/Institution-Bestlink%20College%20of%20the%20Philippines-red.svg)](https://bcp.edu.ph/)

## 📌 Project Overview
**QueueSense** is a specialized queue management system designed for educational institutions, specifically tailored for the **Bestlink College of the Philippines (BCP)**. It aims to eliminate long physical lines and optimize student service flow through real-time ticket monitoring and AI-assisted wait time predictions.

The system is built to mirror the official BCP Student Management System (SMS) aesthetic, ensuring a seamless and native user experience for students and staff alike.

## 🚀 Core Features
- **BCP Integrated Auth:** Specialized login system for Students (ID-based) and Staff/Admin (Secure Password-based).
- **Live Queue Monitoring:** Real-time status updates using AJAX polling (no page refreshes required).
- **AI-Assisted Wait Time Prediction:** Intelligent estimation of wait times based on historical service data and current queue length.
- **Dynamic Service Windows:** Support for multiple service counters (e.g., Registrar Windows 1-5).
- **Mobile-First Design:** Fully responsive interface optimized for students accessing the system via smartphones.
- **Priority Lane Support:** Specialized handling for PWD and Senior Citizen students.

## 🛠️ Technology Stack
- **Frontend:** HTML5, CSS3 (Vanilla + BCP SMS Custom Theme), Bootstrap 5.3, JavaScript (ES6)
- **Backend:** PHP 8.x (Procedural/Modular Architecture)
- **Database:** MySQL 8.0
- **Server:** Apache (XAMPP Environment)

## 📂 System Architecture
```text
queuesense/
├── admin/              # Administrative Dashboard & Analytics
├── api/                # JSON API endpoints for live updates
├── assets/             # CSS, Images (BCP Branding), JS
├── database/           # SQL Schema & Seed Data
├── includes/           # Core logic, DB connection, and Auth checks
├── modules/            
│   ├── auth/           # Login, Logout, QR Authentication
│   └── queue/          # Student-side queue selection & ticket view
├── staff/              # Staff/Window Operator dashboard
├── config.php          # Global system configuration
└── index.php           # Landing / Entry point
```

## 📝 Setup Instructions
1. **Clone the repository:**
   ```bash
   git clone https://github.com/ronaldllamo26/LMS.git
   ```
2. **Database Setup:**
   - Create a database named `queuesense_db` in PHPMyAdmin.
   - Import `database/queuesense.sql`.
3. **Configuration:**
   - Edit `config.php` to match your local database credentials (default: root/no password).
4. **Access:**
   - Navigate to `http://localhost/lms/queuesense/` in your browser.

## 🎓 Capstone Information
- **Title:** QueueSense: An AI-Assisted Smart Queue and Crowd Flow Management System for Educational Institutions
- **Developer:** Ronald Llamo (4th Year Student)
- **Institution:** Bestlink College of the Philippines

---
*Developed with a focus on institutional efficiency and modern UX standards.*
