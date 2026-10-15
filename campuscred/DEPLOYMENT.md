# 🚀 CampusCred - Production Deployment Guide

CampusCred is built on a portable, modern PHP 8+ and MySQL stack. It is ready for instant deployment across multiple environments:

---

## 1. 🖥️ Current Active Deployment (XAMPP on Local Machine)

Your local production environment is **already running and live**:
* **Localhost Access:** [http://localhost/campuscred/](http://localhost/campuscred/)
* **Local Network (LAN / Wi-Fi) Access:** `http://192.168.2.82/campuscred/`  
  *(Any smartphone, tablet, or laptop connected to the same Wi-Fi can open this link directly to test)*

### 1-Click Server Verification & Launch:
Run the included launcher in the project root:
```bat
deploy.bat
```
This batch script automatically:
1. Verifies that **Apache** (`httpd.exe`) is running.
2. Verifies that **MySQL** (`mysqld.exe`) is active.
3. Runs database health checks and schema migrations.
4. Detects your local network IP and launches your browser.

---

## 2. 🐳 Containerized Deployment (Docker & Docker Compose)

The repository includes a ready-to-run container stack ([`Dockerfile`](file:///c:/zz/htdocs/campuscred/Dockerfile) and [`docker-compose.yml`](file:///c:/zz/htdocs/campuscred/docker-compose.yml)).

### Quick Start:
```bash
docker compose up -d
```

### What this starts:
* **Web App Container:** PHP 8.2 + Apache with `mod_rewrite`, GD library, and security headers running on **Port 8080**.
* **Database Container:** MySQL 8.0 with persistent storage volume (`db_data`) and automatic initialization from `database.sql` on **Port 3307**.

Open your browser at:
```text
http://localhost:8080/
```

To stop:
```bash
docker compose down
```

---

## 3. 🌐 Cloud Deployment (Render / Railway / Fly.io)

### Deploying to Render.com:
1. Push this project to a GitHub repository.
2. In Render dashboard, click **New +** → **Web Service**.
3. Select your repository.
4. Under **Environment**, choose **Docker**.
5. Create a managed MySQL database in Render or use a free remote MySQL service (e.g. PlanetScale, Aiven, or Clever Cloud).
6. Under **Environment Variables**, add:
   * `DB_HOST`: Your remote MySQL host
   * `DB_NAME`: Your database name
   * `DB_USER`: Your database username
   * `DB_PASS`: Your database password
   * `BASE_URL`: Leave empty or set to `/`
7. Click **Create Web Service**. CampusCred will automatically initialize all tables and seed demo accounts on boot!

---

## 4. 🏢 Shared Hosting / cPanel (InfinityFree, Hostinger, 000webhost, cPanel)

You can deploy the complete project on any PHP/cPanel web host in 5 steps:

1. **Upload Code:**
   * Download [`campuscred_complete_project.zip`](file:///c:/zz/htdocs/campuscred/campuscred_complete_project.zip).
   * In your hosting cPanel **File Manager**, upload the ZIP to the `public_html` directory and click **Extract**.

2. **Create MySQL Database:**
   * Go to **cPanel → MySQL Databases**.
   * Create a new database (e.g., `user_campuscred`) and user, then assign all privileges.

3. **Import Database Tables:**
   * Open **phpMyAdmin** from cPanel.
   * Select your new database and click the **Import** tab.
   * Choose `database.sql` from the extracted folder and click **Import**.

4. **Update Database Credentials:**
   * Open `config.php` in the File Manager editor.
   * Update the credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'your_database_name');
     define('DB_USER', 'your_database_user');
     define('DB_PASS', 'your_database_password');
     ```

5. **Verify File Permissions:**
   * Ensure the `uploads/` folder has permission `755` (or `777` if required by your host) so students and admins can upload certificate proofs.

---

## 5. 🌍 Instant Public Testing via Cloudflare Tunnel or Ngrok

If you want to demo your live local server to anyone in the world over an encrypted public HTTPS link:

### Option A: Using Cloudflare Tunnel (Zero Account / 100% Free)
Download `cloudflared` from [cloudflare.com](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/) and run:
```bash
cloudflared tunnel --url http://localhost:80
```
Cloudflare will output a public URL such as `https://random-words.trycloudflare.com/campuscred/`.

### Option B: Using Ngrok
```bash
ngrok http 80
```
Ngrok will generate a secure `https://xxxx.ngrok-free.app/campuscred/` URL.

---

## 🔑 Pre-Configured Demo Credentials

| Role | Email | Password | Access Highlights |
| :--- | :--- | :--- | :--- |
| **🏢 Company Recruiter** | `recruiter@techcorp.com` | `Recruiter@123` | Talent pool search, inspect candidate certificates & hashes, hiring pipeline, interview offers |
| **🎓 Student** | `aravind@skit.ac.in` | `Student@123` | Upload achievements, view verified credentials, manage skills tags, view company offers |
| **🛡️ Administrator** | `admin@skit.ac.in` | `Admin@123` | Approve/reject submissions, cryptographic certificate issuance, institutional reports, analytics |
