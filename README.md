# 🎓 Student Quarterly Grading System (SQGS)

A web-based **Student Quarterly Grading System** built with PHP and MySQL, fully modernized for **PHP 8.2**, cloud-ready for **Render** (Docker containerized) and **Aiven Cloud MySQL** (enforced SSL & custom 5-digit ports).

---

## 🚀 Live Application & Quick Access

- **Live Application URL:** `https://sqgs-grading-system.onrender.com` *(Replace with your Render deployment URL)*
- **1-Click Database Setup Tool:** `https://sqgs-grading-system.onrender.com/db_setup.php`
- **Admin Login Page:** `https://sqgs-grading-system.onrender.com/login.php`

---

## 🔑 Default Administrator Credentials

| Role | Username | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Primary Administrator** | `admin` | `admin123` | Full Access (CRUD on all modules) |
| **Secondary User** | `user1` | `user1` | Staff / Teacher Access |

---

## ☁️ Aiven Cloud MySQL Configuration

Aiven Cloud MySQL enforces SSL (`require_secure_transport=ON`) and operates on dynamic 5-digit ports. This project has native support for both individual environment variables and full Service URIs.

### Environment Variables for Render

In your Render Service Dashboard -> **Environment Variables**, configure:

| Key | Description | Example / Recommended Value |
| :--- | :--- | :--- |
| `DB_SERVER` | Aiven MySQL Host | `mysql-xxxx-project.l.aivencloud.com` |
| `DB_PORT` | Custom 5-Digit Port | `26284` |
| `DB_USERNAME` | Database User | `avnadmin` |
| `DB_PASSWORD` | Database Password | *(Your Aiven Password)* |
| `DB_NAME` | Database Name | `defaultdb` |
| `DB_SSL` | Enable SSL encryption | `true` |

*(Optional)* Or provide the full connection URI as `DATABASE_URL`:
```env
DATABASE_URL=mysql://avnadmin:your_password@mysql-xxxx-project.l.aivencloud.com:26284/defaultdb?ssl-mode=REQUIRED
```

---

## 🛠️ 1-Click Database Import (`db_setup.php`)

Once deployed on Render, initialize your database in seconds:
1. Open `https://<your-render-url>/db_setup.php` in your browser.
2. The tool automatically verifies:
   - SSL Handshake & cipher negotiation
   - Host, port, and MySQL 8.x server version
   - Current table status (9 required tables)
3. Click **"1-Click Import Database (sqgs_db.sql)"**.
4. The tool cleanly parses the SQL dump, executes all DDL/DML with foreign key checks safely disabled, verifies tables and the `admin` user, and displays a direct **"Go to Live Login"** button.

---

## 🐳 Render Deployment Architecture

This project includes enterprise-grade fixes for running PHP on Render:

1. **Dockerized Environment (`Dockerfile`):**
   - Base image: `php:8.2-apache`
   - Extensions: `mysqli`, `pdo_mysql`, `gd`, `zip`
   - Modules: Apache `mod_rewrite`, `mod_headers`
2. **Reverse Proxy & Dynamic Port Fix (`start.sh`):**
   - Binds dynamically to Render's assigned `$PORT` (e.g. 10000).
   - Configures `UseCanonicalName Off`, `UseCanonicalPhysicalPort Off`, and `ServerName localhost` to eliminate the infamous `:10000` internal port leaking in redirects.
   - Enables `AllowOverride All` for `.htaccess` execution.
3. **Smart Trailing Slash Rules (`.htaccess`):**
   - Clean URL handling and directory redirect port-stripping.
   - Denies direct browser access to `.sql` dumps and backup files.
4. **Render Blueprint (`render.yaml`):**
   - Pre-configured Infrastructure-as-Code for 1-click Render blueprint deployments.

---

## 📁 Project Structure

```text
├── Actions.php               # Backend API controller (login, CRUD, session)
├── DBConnection.php          # Aiven SSL MySQLi database connection handler
├── Dockerfile                # Production Dockerfile (PHP 8.2 + Apache)
├── README.md                 # Complete documentation & credentials
├── db_setup.php              # 1-Click visual installer & DB health check
├── index.php                 # Main authenticated dashboard router
├── login.php                 # Login interface with direct routing
├── render.yaml               # Render Infrastructure-as-Code blueprint
├── start.sh                  # Apache startup script & reverse proxy patch
├── .htaccess                 # Reverse proxy trailing slash & security rules
├── database/
│   └── sqgs_db.sql           # Complete MySQL database schema & sample data
├── css/                      # Bootstrap & stylesheet assets
├── js/                       # jQuery & scripts
├── select2/                  # Select2 library
├── DataTables/               # DataTables library
└── Font-Awesome-master/      # FontAwesome icon assets
```

---

## 🔒 Saved Admin Credentials (Summary)

```text
==================================================
ADMIN LOGIN DETAILS:
Login URL: /login.php
Username: admin
Password: admin123
==================================================
```
