# CampusCred — Student Participation, Achievement & Recruiter Talent Management System

> **Universal Full-Stack Web Application for Higher Education Institutions, Technical Universities, and Corporate Recruiters.**
> Centralized Digital Repository, Tamper-Evident QR Verification, and Candidate Discovery Engine.

---

## 🌟 Key Modules & Features

### 1. 🏢 Corporate Recruiter & Talent Acquisition Portal (`recruiter/`)
- **Direct Candidate Discovery**: Browse verified students, filter by academic department, minimum verified achievements, and technical skill tags (e.g., Python, React, IoT, ROS, AWS, Algorithms).
- **Inspect All Achievements & Certificates**: View verified records with competition standing (Winner, Runner-up, Finalist, Presenter), project descriptions, and cryptographic verification seals.
- **Interactive Certificate Document Inspector**: One-click preview of student certificate proofs (PDFs, JPGs, PNGs) in a responsive modal viewer or open/download directly.
- **Hiring Pipeline & Shortlist Tracker**: Organize candidates across recruitment stages (`Saved`, `Screening`, `Interviewing`, `Offered`, `Archived`) with private notes and CSV export.
- **Direct Interview Invites & Offers**: Dispatch interview requests, internships, and full-time job offers directly to candidates.
- **Instant Credential Verifier**: Enter candidate roll IDs or verification UUIDs to confirm authentic institutional endorsement.

### 2. 🎓 Student Achievement & Career Showcase (`student/`)
- **Achievement Submission & Multi-Format Proofs**: Submit academic, technical, cultural, sports, and extracurricular achievements with certificate upload (PDF/JPG/PNG).
- **Official Certified Portfolio & Transcript**: Generate AICTE/Institutional standard achievement transcript with live tamper-evident QR code and PDF export.
- **Shareable Public Recruiter URL**: Shareable link (`public_portfolio.php?id=...`) with QR code for resume or LinkedIn showcase.
- **Career Profile & Skill Tags**: Customize career headline, skill tags, CGPA, GitHub, and LinkedIn links.
- **Recruiter Inquiries Inbox**: Review incoming interview invitations and job offers from verified companies.

### 3. 🛡️ Administrative Verification & Audit Panel (`admin/`)
- **Review Workflow**: Inspect pending student achievement submissions, examine uploaded certificate scans, approve genuine entries, or reject with explanatory feedback.
- **Cryptographic Hashing & Verification UUID**: Automatically computes SHA-256 certificate hashes and assigns unique verification UUIDs.
- **Student Profile Registry**: View consolidated participation metrics, download department reports, and filter by batch/year.
- **Audit Trail & System Logs**: Complete timestamped audit history tracking all status transitions.

### 4. 🌐 General Institutional Use & Easy Rebranding
- Fully configurable for **ANY** university, college, institute, polytechnic, or school.
- Customize via `config.php`:
  - `COLLEGE_NAME`: Full legal name of your institution.
  - `COLLEGE_SHORT_NAME`: Abbreviated name (e.g., "SKIT Jaipur", "MIT", "IIT Delhi", "Stanford").
  - `PROJECT_CODE`: Institutional accreditation code.
  - `APP_NAME`: Platform branding name.
  - `UPLOAD_DIR`: Secure local upload repository.
  - `DB_*`: Host, database name, user, password.

---

## 🔑 Pre-Configured Demo Accounts

| Role | Name | Email / Roll ID | Password | Access Panel |
| :--- | :--- | :--- | :--- | :--- |
| **Corporate Recruiter** | Pooja Malhotra (TechCorp Labs) | `recruiter@techcorp.com` | `Recruiter@123` | Recruiter Talent Hub |
| **Student (CSE - Winner)** | Aravind Swaminathan | `aravind@skit.ac.in` (SKIT/2023/CSE/014) | `Student@123` | Student Dashboard & Portfolio |
| **Student (IT - Finalist)** | Priya Sharma | `priya.sharma@skit.ac.in` (SKIT/2022/IT/088) | `Student@123` | Student Dashboard & Portfolio |
| **Student (ECE - Robotics)** | Rohit Verma | `rohit.verma@skit.ac.in` (SKIT/2024/ECE/105) | `Student@123` | Student Dashboard & Portfolio |
| **Institutional Admin** | Dr. Rajesh Sharma | `admin@skit.ac.in` (SKIT/ADM/001) | `Admin@123` | Administrative Center |

*Tip: The login page includes 1-Click Demo Login buttons for instant access!*

---

## 🚀 Quick Setup & Installation (XAMPP / Apache / PHP 8+)

1. **Extract Archive**:
   Extract all contents into your local web root directory:
   ```text
   C:\xampp\htdocs\campuscred\
   ```
2. **Start Services**:
   Open **XAMPP Control Panel** and start both **Apache** and **MySQL**.
3. **Open in Browser**:
   Navigate to:
   ```text
   http://localhost/campuscred/
   ```
   *The system features automatic schema migration (`auto_migrate_schema` in `db.php`). The database, tables, and demo seed data are initialized automatically on first visit!*
4. **Database Reset & Health Check**:
   To inspect database health or reset demo data at any time, open:
   ```text
   http://localhost/campuscred/setup.php
   ```
5. **Download Project ZIP**:
   To download or package the entire codebase at any time, visit:
   ```text
   http://localhost/campuscred/download_project.php
   ```

---

## 💻 Tech Stack
- **Backend**: Native PHP 8.0+ (PDO prepared statements, CSRF protection, session management, RBAC).
- **Database**: MySQL / MariaDB (InnoDB, foreign keys, UTF8mb4, indexes).
- **Frontend**: Bootstrap 5.3.3, Bootstrap Icons 1.11.3.
- **Client Scripts**: Chart.js 4.4, jsPDF 2.5, QRCode.js (offline bundled).
- **Storage**: MIME-type validated file upload streamer (`certificate.php`).
