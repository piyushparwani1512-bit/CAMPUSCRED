# 🌐 Complete Step-by-Step Deployment Guide for InfinityFree

This guide walks you through deploying **CampusCred** onto [InfinityFree](https://www.infinityfree.com/) (100% free web hosting with PHP 8+ and MySQL).

---

## 📋 What You Need Before You Start
1. The pre-packaged project ZIP: [`campuscred_complete_project.zip`](file:///c:/zz/htdocs/campuscred/campuscred_complete_project.zip) (Only 140 KB).
2. A free account at [infinityfree.com](https://www.infinityfree.com/).

---

## Step 1: Create a Free InfinityFree Hosting Account

1. Go to [https://www.infinityfree.com/](https://www.infinityfree.com/) and register or log in.
2. In the Client Area dashboard, click **"Create Account"**.
3. Choose **Domain Type**:
   * Select **"Subdomain"** (e.g., enter `mycampuscred` and pick domain `.infinityfreeapp.com` or `.epizy.com`).
   * Example URL: `http://mycampuscred.infinityfreeapp.com/`.
4. Click **"Check Availability"** → then click **"Create Account"**.
5. Wait ~30 seconds for the hosting account to be provisioned.

---

## Step 2: Create Your MySQL Database in vPanel

> [!IMPORTANT]
> On InfinityFree, databases **must** be created through the Control Panel first.

1. In your InfinityFree account page, click the green button **"Control Panel"** (vPanel).
   *(If prompted with an "I Approve" agreement on first visit, click "I Approve")*.
2. Scroll down to the **Databases** section and click **"MySQL Databases"**.
3. In the **"Create New Database"** box:
   * Type a database name suffix, e.g. `campuscred`.
   * Click **"Create Database"**.
4. You will see your newly created database listed with a name like:
   `if0_38123456_campuscred` (or `epiz_38123456_campuscred`).

---

## Step 3: Find Your MySQL Connection Details

On your InfinityFree Account Details page (or inside the MySQL Databases screen in vPanel), locate:

| Setting | Example InfinityFree Value | Note |
| :--- | :--- | :--- |
| **MySQL Hostname** | `sql200.infinityfree.com` | **NEVER** use `localhost` on InfinityFree! |
| **Database Name** | `if0_38123456_campuscred` | The full name with your user prefix |
| **MySQL Username** | `if0_38123456` | Your vPanel account username |
| **MySQL Password** | `••••••••••••` | Your hosting account password (click "Show" in Account Details) |

---

## Step 4: Upload Project Files to `htdocs`

### Method A: Web File Manager (Fastest — 1 Minute)
1. In your InfinityFree Account Details page, click **"File Manager"** (Monsta FTP opens in your browser).
2. Double-click to open the **`htdocs`** folder.
3. If there are default files (such as `index2.html` or `DO NOT UPLOAD HERE`), select them and click **Delete**.
4. In the bottom toolbar of the File Manager, click the **Upload** icon and select **"Upload Zip"** (or click the arrow next to Upload → Upload Archive).
5. Select [`campuscred_complete_project.zip`](file:///c:/zz/htdocs/campuscred/campuscred_complete_project.zip) from your computer.
6. Monsta FTP will automatically upload and **extract all files directly into `htdocs/`**!

### Method B: FTP Client (FileZilla)
If you prefer FileZilla:
* **Host:** `ftpupload.net` (Port `21`)
* **Username & Password:** Your InfinityFree hosting credentials
* Upload the extracted project files directly into the `/htdocs/` directory.

---

## Step 5: Run the 1-Click Web Installer (`install.php`)

Now open your web browser and visit:
```text
http://YOUR-SUBDOMAIN.infinityfreeapp.com/install.php
```
*(e.g., `http://mycampuscred.infinityfreeapp.com/install.php`)*

1. You will see the **CampusCred Automated Web Installer**.
2. Enter your 4 InfinityFree database values:
   * **MySQL Host Name:** `sqlxxx.infinityfree.com`
   * **MySQL Database Name:** `if0_xxxxxxxx_campuscred`
   * **MySQL User Name:** `if0_xxxxxxxx`
   * **MySQL Password:** Your hosting account password
3. Click **"Test Connection & Install CampusCred"**.

### What happens automatically:
* ✅ Tests the connection to your remote InfinityFree MySQL server.
* ✅ Writes the configuration to `config.php`.
* ✅ Creates all 5 database tables (`users`, `activities`, `recruiter_bookmarks`, `recruitment_inquiries`, `verification_logs`).
* ✅ Seeds pre-configured demo accounts and tamper-evident verified certificates.
* ✅ Confirms directory permissions for certificate uploads.

---

## Step 6: Log In and Test!

Click **"Go to CampusCred Login"** or visit:
```text
http://YOUR-SUBDOMAIN.infinityfreeapp.com/login.php
```

Log in with any of the pre-seeded demo accounts:

| Role | Email | Password | What You Can Test |
| :--- | :--- | :--- | :--- |
| **🏢 Company Recruiter** | `recruiter@techcorp.com` | `Recruiter@123` | Search candidates by skill/department, view cryptographic proofs, inspect certificates, send interview invites |
| **🎓 Student** | `aravind@skit.ac.in` | `Student@123` | View portfolio, manage skills tags, view company job offers, download certificates |
| **🛡️ Administrator** | `admin@skit.ac.in` | `Admin@123` | Verification queues, issue SHA-256 certificates, generate PDF transcripts, view analytics |

---

## 🔒 Optional: Enable Free SSL (HTTPS) on InfinityFree

InfinityFree provides **Free SSL certificates** with 1-click renewal:
1. In the InfinityFree Client Area, click **"Free SSL Certificates"**.
2. Click **"New SSL Certificate"** and choose your subdomain.
3. Select the recommended provider (**Let's Encrypt** or **GoGetSSL**).
4. Click **"Setup CNAME records automatically"**.
5. Once verified (~5-10 minutes), click **"Install SSL Certificate Automatically"**.
6. Your website is now fully secured at `https://YOUR-SUBDOMAIN.infinityfreeapp.com/`!
