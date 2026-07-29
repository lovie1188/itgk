# SoftSam Portal - RS-CIT Certificate Management System

A comprehensive, production-grade web application for managing RS-CIT (Rajasthan State Certificate in Information Technology) certificates, learner results, and ITGK (Information Technology Gyan Kendra) data, integrated natively with Google Sheets.

---

## 🚀 Features

*   **Dashboard Analytics**: Real-time stats and visual widgets for available, transit, and issued certificate packets.
*   **Google Sheets Integration**: Implements a bidirectionally synced active overlay. Reads and updates ranges for packet statuses and learner results.
*   **Dynamic Locations**: Fetches available physical locations from `misc!J2:J18` spreadsheet range to populate form fields dynamically.
*   **Upgraded Acknowledgement Engine**: Generates single-copy printable receipts showing Packet No, Certificate From/To, and Course/Exam details alongside comprehensive ITGK master data.
*   **Premium Toasts & Loaders**: User actions are backed by asynchronously triggered Bootstrap Toast notifications and modal overlays to prevent duplicate submissions.
*   **SSO & Role-Based Access Control**: Hierarchical role gating (`GUEST < PARTNER < COORDINATOR < EMPLOYEE < ADMIN < SUPERADMIN`) with database and session sync.

---

## 📋 Requirements

*   PHP 8.0+
*   MySQL 5.7+ / MariaDB 10.3+
*   Composer
*   XAMPP/WAMP/LAMP stack (for local development)

---

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/lovie1188/certificate.git
cd certificate
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
Copy the example environment file:
```bash
cp .env.example .env
```
Configure your database, app URLs, and Google Sheets credentials inside `.env`.

### 4. Run Database Setup
Initialize tables and seed mock designations/offices:
```bash
http://localhost/certificate/setup
```

---

## 🏗️ Architecture & File Structure

```
certificate/
├── app/
│   ├── Controllers/       # HTTP Request Handlers
│   │   ├── Api/           # RESTful API Controllers
│   │   ├── CertificateController.php  # Certificate & Acknowledgement controller
│   │   ├── ProfileController.php      # User profile handlers
│   │   └── *.php
│   ├── Core/              # Framework Core (Router, DB Singleton, Request handlers)
│   ├── Helpers/           # Utility classes (CSRF, session managers)
│   ├── Middleware/        # Pipeline filters (Auth, CSRF guards)
│   ├── Models/            # Database representation models
│   ├── Services/          # Service layer
│   │   ├── AuthService.php            # Active session & designation synchronization
│   │   └── GoogleSheetService.php     # Google Sheets connection wrapper & range fetchers
│   └── Views/             # HTML Templates
│       ├── layouts/       # Main wrapper layout
│       └── pages/         # Page templates (list.php, profile.php, etc.)
├── assets/                # Client-side static resources (CSS, JS, images)
├── config/                # Framework settings and Google API client credentials
├── routes/                # Endpoint mapping rules
├── storage/               # Logs, session variables, and media uploads
└── .gitignore             # Git exclusion rules
```

---

## 🤝 Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/my-feature`
5. Submit a Pull Request.

---

**Built with ❤️ by Raksha E Services Team**

[rakhsaeservices.co.in](https://rakhsaeservices.co.in)
