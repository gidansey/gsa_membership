# 🇬🇭 GSA Membership Management System

**Empowering Science Through Organized Membership**

A comprehensive PHP/MySQL-based web application for managing member data, dues, events, and communication for the Ghana Science Association (GSA).

> This system is designed to manage all aspects of GSA membership registration, profiles, dues tracking, event participation, and administrative controls.

---

## 📁 Project Structure

```
GSA Membership Management System/
├── assets/                   # Static files (CSS, images, etc.)
│   ├── styles.css           # Custom styles
│   └── gsa_logo.svg         # Association logo
│
├── includes/                # Shared PHP includes and functions
│   ├── auth_check.php       # Session/authentication checker
│   ├── header.php           # Reusable HTML <head> section
│   ├── db_connect.php       # Database connection (shared)
│   ├── timeout.php          # Session timeout handler
│   ├── functions.php        # Utility functions
│   └── footer.php           # Reusable footer content
│
├── dashboards/              # Role-based dashboards
│   ├── admin_dashboard.php         # Admin overview and controls
│   ├── secretariat_dashboard.php   # Secretariat operations
│   ├── branch_dashboard.php        # Branch-level management
│   ├── member_dashboard.php        # Individual member portal
│
├── secretariat/             # Secretariat tools
│   └── generate_letter.php  # Membership letter generation
│
├── index.php                # Login/landing page
├── forgot_password.php      # Request password reset
├── reset_password.php       # Reset via token/email
├── logged_out.php           # Post-logout screen
├── logout.php               # Logout handler
└── README.md                # Project documentation
```

---

## 🛠️ Requirements

### ✔️ Technologies Used

* PHP (8.0+ recommended)
* MySQL / MariaDB
* HTML5, CSS3 (Vanilla)
* Chart.js (for dashboards)
* AJAX (for real-time features)
* Optional: PHPMailer (for email notifications)

### 📦 Installation Prerequisites

* Apache/Nginx server with PHP
* MySQL server
* Composer (optional for PHP libraries)
* Git (for version control)

---

## 🚀 Getting Started

### 1. **Clone the Repository**

```bash
git clone https://github.com/your-username/gsa-membership-system.git
cd gsa-membership-system
```

### 2. **Set Up the Database**

* Create a new MySQL database: `gsa_membership`
* Import your SQL schema dump:

```sql
SOURCE path/to/gsa_membership.sql;
```

### 3. **Configure Database Connection**

Edit `includes/db_connect.php`:

```php
<?php
$host = "localhost";
$user = "";
$password = "";
$database = "gsa_membership";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### 4. **Launch Locally**

Move the project folder to your server directory (e.g. `htdocs/` or `/var/www/html/`)

Visit:

```
http://localhost/gsa-membership-system/
```

---

## 🔐 User Roles

| Role              | Description                                     |
| ----------------- | ----------------------------------------------- |
| **Admin**         | Full access to all modules, users, settings     |
| **Secretariat**   | Handles member registration, verification, dues |
| **Branch Leader** | Manages members within their branch             |
| **Member**        | Views personal dashboard, pays dues, events     |

---

## 📌 Key Features

* ✅ Member registration & ID generation
* ✅ Branch-based access & filtering
* ✅ Role-based dashboards
* ✅ Membership dues tracking
* ✅ Event registration & feedback
* ✅ Automated membership letter generation
* ✅ Secure login with password reset
* ✅ Admin management of users, audit logs, and notifications

---

## 📂 Deployment Notes

* Rename `db_connect.php` credentials in **all folders**.
* Ensure file permissions allow PHP to write PDFs or send emails.
* Secure sensitive folders (e.g. `/includes/`) using `.htaccess`.

---

## 🌐 Hosting Options

* **Frontend (HTML/CSS)**: GitHub Pages (static)
* **Backend (PHP/MySQL)**:

  * [Render](https://render.com)
  * [Vercel](https://vercel.com) *(via API)*
  * [Heroku](https://heroku.com)
  * [InfinityFree](https://infinityfree.net/)
  * [000WebHost](https://www.000webhost.com/)
  * [Hostinger](https://hostinger.com)

### Render Hosting Setup:

1. Sign up at Render
2. Create a new Web Service
3. Connect your GitHub repo
4. Set build command: `N/A`
5. Start command: `php -S 0.0.0.0:10000 -t .`
6. Add necessary environment variables
7. Link MySQL database

---

## 🔧 Customization

* Update branding via `assets/styles.css`
* Replace logo at `assets/gsa_logo.svg`
* Modify dashboard views in `dashboards/`

---

## 📈 Roadmap

* [ ] Mobile responsiveness
* [ ] Centralized notification center
* [ ] Google Forms integration
* [ ] Membership card PDF generation
* [ ] Branch performance reports

---

## 🤝 Contributing

1. Fork the repository
2. Create a branch: `git checkout -b feature/new-module`
3. Commit: `git commit -m "Add new module"`
4. Push: `git push origin feature/new-module`
5. Open a Pull Request

---

## 🛠️ Tech Stack

* PHP 7.4+
* MySQL 5.7+
* HTML5/CSS3
* JavaScript (Chart.js)
* AJAX

---

## 🚪 Demo Credentials

```
Admin: daniel.gidi / 1Qaz@2wsx*
Secretariat: dangidi / abcD@1234.
Branch Leader: dandee / abcd.1234
Member: dandee / abcd.1234
```

---

## ⚙️ CI/CD with GitHub Actions

**`.github/workflows/php.yml`**

```yaml
name: PHP CI

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Set up PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
    - name: Check Syntax
      run: find . -name "*.php" -exec php -l {} \;
```

---

## 📝 .gitignore

```gitignore
/vendor/
/node_modules/
/config.php
*.log
.env
.DS_Store
.idea/
.vscode/
```

---

## 👤 Author & Maintainer

**Daniel Kojo Gidi**
Tech Vantage Solutions
“Empowering Business Through Innovative IT Solutions”
📧 [GitHub](https://github.com/gidansey)
📧 [daniel.gidi@st.gimpa.edu.gh](mailto:daniel.gidi@st.gimpa.edu.gh)
📧 [gidansey@gmail.com](mailto:gidansey@gmail.com)
📧 [techvantagegh@gmail.com](mailto:techvantagegh@gmail.com)

---

## 🙌 Acknowledgments

Built for the Ghana Science Association to streamline member services and coordination.

---

## 📜 License

MIT License — see `LICENSE` for full details.
**Proprietary to the Ghana Science Association (GSA)**. Do not distribute without permission.
