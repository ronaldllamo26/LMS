# QueueSense: AI-Assisted Smart Queue & Multi-Step Journey Management

[![Version](https://img.shields.io/badge/Version-1.2.0--Stable-green.svg)](https://github.com/ronaldllamo26/LMS)
[![Tech Stack](https://img.shields.io/badge/Stack-PHP%208.x%20%7C%20MySQL%20%7C%20Bootstrap-orange.svg)]()
[![Institution](https://img.shields.io/badge/Institution-Bestlink%20College%20of%20the%20Philippines-red.svg)](https://bcp.edu.ph/)

## 📌 Project Overview
**QueueSense** is a next-generation queue management system specifically engineered for **Bestlink College of the Philippines (BCP)**. Beyond simple queueing, it introduces the **Smart Reservation Engine**, which allows students to secure positions in multiple service queues (e.g., Registrar to Cashier) simultaneously, ensuring a seamless, automated handover from one office to the next.

The system features a pixel-perfect replica of the official BCP Student Management System (SMS) aesthetic, providing an intuitive and institutional experience.

## 🚀 Key Innovation: Smart Reservation & Journey Flow
The most powerful feature of QueueSense is its ability to handle **Multi-Step Journeys**:
- **Automated Handover:** Once a student is marked "Done" at their first stop (e.g., Registrar), the system automatically activates their reserved "Pending" ticket at the next stop (e.g., Cashier).
- **Absolute Synchronization:** Uses a database-backed sync engine to ensure journey progress is consistent across devices and server sessions.
- **Queue Protection:** Students secure their spot in the next queue the moment they confirm their journey, preventing "cutters" and optimizing office throughput.

## ✨ Core Features
- **BCP Institutional Auth:** Secure login for Students and Staff using institutional ID formats.
- **Real-Time Dashboard:** Live queue status and position tracking via AJAX polling.
- **AI-Assisted Wait Time:** Intelligent wait time estimation based on historical data.
- **Dynamic Profile Management:** Integrated avatar upload with precision cropping tool.
- **Staff Control Center:** Specialized interface for office staff to call, serve, and complete student requests.
- **AI Analytics:** Administrative insights for institutional resource planning.

## 🛠️ Technology Stack
- **Frontend:** HTML5, CSS3 (BCP SMS Design System), Bootstrap 5.3, JavaScript (ES6), Cropper.js
- **Backend:** PHP 8.x (Modular Architecture)
- **Database:** MySQL 8.0
- **Server Environment:** Apache (XAMPP / Localhost)

## 📂 System Structure
```text
queuesense/
├── admin/              # Global system analytics & management
├── api/                # Real-time synchronization endpoints
├── assets/             # Institutional branding & uploads
├── includes/           # Sync Engine, Auth Logic, and Core Functions
├── modules/            
│   ├── auth/           # Login & Profile Management
│   └── queue/          # Smart Journey & Ticket Interface
├── staff/              # Window Operator Dashboards
└── config.php          # System configuration
```

## 📝 Quick Start
1. **Clone & Setup:**
   ```bash
   git clone https://github.com/ronaldllamo26/LMS.git
   ```
2. **Database:** Import `database/queuesense.sql` into a MySQL database named `queuesense_db`.
3. **Configure:** Update `config.php` with your local DB credentials.
4. **Run:** Access the system via `http://localhost/lms/queuesense/`.

## 🎓 Academic Information
- **Title:** QueueSense: An AI-Assisted Smart Queue and Multi-Step Journey Management System
- **Developer:** Ronald Llamo (4th Year Student)
- **Institution:** Bestlink College of the Philippines

---
*Built with precision for the Bestlink College of the Philippines community.*
