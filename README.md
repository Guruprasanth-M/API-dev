# 🚀 Self-Hosted PHP REST API – Authentication & User Management

A production-ready REST API built in PHP for complete user authentication and account lifecycle management.
Designed for real deployment, security, and learning by building real systems.

---

## ✨ Features

### 🔐 Authentication
- User signup & login
- Email verification before access
- Secure password hashing (bcrypt)
- Account blocking support

### 🔑 Token System
- Access tokens (1 hour)
- Refresh tokens (30 days)
- Token rotation & revocation
- Stateless session handling

### 📧 Email
- Gmail SMTP integration
- Verification emails
- Password reset flow
- Expiring email tokens

### 🛡️ Security
- Prepared statements (SQL injection protection)
- HTTPS/TLS ready
- No sensitive data in logs
- Clean, predictable JSON responses

### ☁️ Deployment Ready
- Apache2 with URL rewriting
- Custom domain + SSL
- Persistent MySQL connections
- Optimized for production use

---

## ⚙️ Requirements

- PHP 8.3+
- MySQL 5.7+
- Composer
- Apache2 with mod_rewrite

---

## 🚀 Quick Start

```bash
# Clone & install
git clone <repo>
cd api
composer install

# Configure
cp .env.sample .env
# Edit .env with your database & email settings

# Run migrations
php -r "require 'src/load.php'; Migration::run(Database::getConnection());"

# Start server
php -S localhost:8000
```

---

## 🧪 Response Format

All endpoints return consistent JSON:

```json
{
  "status": "SUCCESS | FAILED | UNAUTHORIZED",
  "msg": "Readable message",
  "error": "Details if any",
  "code": 200
}
```
## 📸 Overview

<img width="1920" height="1080" alt="API Overview" src="https://github.com/user-attachments/assets/1fe70ea7-421e-414c-9fe5-f7061a2fce39" />
<img width="1919" height="1034" alt="Database Schema" src="https://github.com/user-attachments/assets/d801ef2e-7c96-49d8-b71b-221b36762bec" />
<img width="1915" height="1011" alt="Production Deployment" src="https://github.com/user-attachments/assets/3af9eff1-b416-4611-a915-df6838f4c140" />

---

## 👨‍💻 Author

**Guruprasanth M**  
