# 🚀 Self-Hosted PHP REST API – Authentication & User Management

A production-ready REST API built in PHP for complete user authentication and account lifecycle management.
Designed for real deployment, security, and learning by building real systems.

> **📖 Full Documentation:** [**Read the Wiki →**](https://github.com/Guruprasanth-M/API-dev/wiki)

---

## 📸 Overview

<img width="1920" height="1080" alt="API Overview" src="https://github.com/user-attachments/assets/1fe70ea7-421e-414c-9fe5-f7061a2fce39" />
<img width="1919" height="1034" alt="Database Schema" src="https://github.com/user-attachments/assets/d801ef2e-7c96-49d8-b71b-221b36762bec" />
<img width="1915" height="1011" alt="Production Deployment" src="https://github.com/user-attachments/assets/3af9eff1-b416-4611-a915-df6838f4c140" />

---

## ✨ Features

### 🔐 Authentication
- User signup & login
- Email verification before access
- Secure password hashing (bcrypt)
- Account blocking support

### 🔑 Token System
- Access tokens (configurable expiry)
- Refresh tokens for session renewal
- Token rotation & revocation
- Server-side session management

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
- Auto-running database migrations

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
git clone https://github.com/Guruprasanth-M/API-dev.git
cd API-dev
composer install

# Configure
cp .env.sample .env
# Edit .env with your database & email settings
```

> See [Environment Variables](https://github.com/Guruprasanth-M/API-dev/wiki/Environment-Variables) wiki page for all configuration options.

---

## 📡 API Endpoints

### Authentication

| Method | Endpoint | Description | Auth | Wiki |
|--------|----------|-------------|:----:|------|
| `POST` | `/signup` | Register a new user | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/Signup) |
| `POST` | `/login` | Login with username/email + password | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/Login) |
| `POST` | `/logout` | Terminate session | Yes | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/Logout) |
| `POST` | `/refresh` | Refresh expired access token | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/Refresh) |

### Email Verification

| Method | Endpoint | Description | Auth | Wiki |
|--------|----------|-------------|:----:|------|
| `POST` | `/verify` | Verify email with token | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/Verify) |
| `POST` | `/resendverification` | Resend verification email | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/ResendVerification) |

### Password Reset

| Method | Endpoint | Description | Auth | Wiki |
|--------|----------|-------------|:----:|------|
| `POST` | `/requestpasswordreset` | Request reset token | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/RequestPasswordReset) |
| `POST` | `/resetpassword` | Reset password with token | No | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/ResetPassword) |

### User

| Method | Endpoint | Description | Auth | Wiki |
|--------|----------|-------------|:----:|------|
| `POST` | `/userexists` | Check if user exists | Optional | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/UserExists) |
| `POST` | `/isloggedin` | Check auth status | Yes | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/IsLoggedIn) |
| `POST` | `/about` | Get user profile + API info | Yes | [Docs](https://github.com/Guruprasanth-M/API-dev/wiki/About) |

---

## 🔐 Authentication Flow

```
Signup → Verify Email → Login → Use API (Bearer Token) → Refresh → Logout
```

```bash
# 1. Register
curl -X POST https://your-domain.com/api/signup \
  -d "username=johndoe&password=secret123&email=john@example.com&phone=9876543210"

# 2. Verify email (token from email)
curl -X POST https://your-domain.com/api/verify \
  -d "token=<verification_token>"

# 3. Login
curl -X POST https://your-domain.com/api/login \
  -d "username=johndoe&password=secret123"

# 4. Use access token on protected routes
curl -X POST https://your-domain.com/api/about \
  -H "Authorization: Bearer <access_token>"

# 5. Refresh when token expires
curl -X POST https://your-domain.com/api/refresh \
  -d "refresh_token=<refresh_token>"
```

> See [Authentication Overview](https://github.com/Guruprasanth-M/API-dev/wiki/Authentication-Overview) for the full token lifecycle.

---

## 🧪 Response Format

All endpoints return consistent JSON:

```json
{
  "status": "SUCCESS | FAILED | UNAUTHORIZED",
  "msg": "Readable message",
  "error": "Details if any"
}
```

> See [Error Handling](https://github.com/Guruprasanth-M/API-dev/wiki/Error-Handling) for every possible error response.

---

## 📖 Documentation (Wiki)

| Page | Description |
|------|-------------|
| [Home](https://github.com/Guruprasanth-M/API-dev/wiki) | Quick start & endpoint index |
| [Authentication Overview](https://github.com/Guruprasanth-M/API-dev/wiki/Authentication-Overview) | Token types, session lifecycle, security |
| [Error Handling](https://github.com/Guruprasanth-M/API-dev/wiki/Error-Handling) | All error responses with HTTP codes |
| [Database Schema](https://github.com/Guruprasanth-M/API-dev/wiki/Database-Schema) | Full `users` + `sessions` table schema |
| [Architecture](https://github.com/Guruprasanth-M/API-dev/wiki/Architecture) | Project structure & request lifecycle |
| [Services Reference](https://github.com/Guruprasanth-M/API-dev/wiki/Services-Reference) | All service classes & methods |
| [Environment Variables](https://github.com/Guruprasanth-M/API-dev/wiki/Environment-Variables) | `.env` configuration reference |

---

## 🏗️ Project Structure

```
api/
├── htdocs/
│   ├── index.php          # API entry point
│   └── web.php            # API dashboard (HTML)
├── src/
│   ├── Core/              # REST handler, Router, Base Controller
│   ├── Controllers/       # One controller per endpoint (auto-discovered)
│   ├── Store/             # Business logic (Auth, Session, User, Email)
│   ├── Services/          # Utilities (Validation, Password, Token, Response)
│   └── Database/          # Connection, Migrations
├── .env                   # Configuration
└── composer.json
```

> See [Architecture](https://github.com/Guruprasanth-M/API-dev/wiki/Architecture) for the full request lifecycle and how to add new endpoints.

---

## 👨‍💻 Author

**Guruprasanth M**  
