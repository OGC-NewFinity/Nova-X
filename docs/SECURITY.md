# Security Policy

## Defense-in-Depth for AI-Native WordPress Infrastructure

**Document Version:** 1.0.0  
**Environment:** Docker-Local  
**Security Posture:** Zero-Trust · Least Privilege · Local-First  
**Compliance Baseline:** WordPress Standards + Modern Cryptography  

---

## Security Architecture Overview

Nova-X implements a **defense-in-depth security model** engineered to protect:
- User data
- API credentials
- AI-generated assets

—both **at rest inside the local Docker environment** and **in transit to external AI providers**.  
All controls align with **strict WordPress security standards** and **modern encryption protocols**.

> _Secure by default. Private by design. Auditable by necessity._

---

## Data Encryption & Storage

### API Key Protection
- External service keys (OpenAI, Claude, Stripe) stored using **AES-256 encryption**
- Encrypted at rest within the local **MariaDB** database

### Environment Isolation
- Docker environments support encrypted **`.env` injection**
- Sensitive credentials can bypass database storage entirely
- Never exposed in the UI or logs

### Database Prefixing
- All Nova-X tables use **unique prefixes**
- Prevents unauthorized SQL injection and cross-table leakage

---

## Authentication & Access Control

### Capability Shielding
- All administrative endpoints restricted to users with **`manage_options`**
- Enforces least-privilege access

### Nonce Validation
- Every REST and AJAX request requires a **valid WordPress Nonce**
- Mitigates **CSRF** attacks across the dashboard

### Session Management
- AI session tokens are:
  - Short-lived
  - Bound to user ID and site URL
- Prevents replay and session hijacking

---

## Content Sanitization & Integrity

### Multi-Pass Scrubbing
- AI-generated HTML processed through **`wp_kses`**
- Removes:
  - Malicious scripts
  - Unauthorized tags
- Sanitized before database persistence

### Input Filtering
- User prompts and settings sanitized using:
  - `sanitize_text_field`
  - `absint` (numeric values)
- No raw input reaches execution layers

### Media Validation
- DALL·E 3 images validated for **MIME-type integrity**
- Verified before registration in the WordPress Media Library

---

## User Privacy & Data Sovereignty

### Telemetry Opt-Out
- Users fully control anonymized usage telemetry
- Opt-out available at any time

### No-Logging Policy
- Prompts and generated content remain **local**
- **No user-generated text** is stored on:
  - Licensing servers
  - Update servers

### GDPR Compliance
- Built-in tools to:
  - Export AI usage logs
  - Delete historical data
- Full alignment with data subject rights

---

## External Communication Security

### SSL/TLS Enforcement
- All external communication enforced over **HTTPS**
- Minimum **TLS 1.2+**
- Applies to:
  - AI Orchestrators
  - Billing Gateways

### IP Restriction
- Licensing server supports **IP whitelisting**
- Ensures only authorized environments validate professional keys

---

## Audit & Compliance

### Real-Time Logging
- Every AI request logged locally with:
  - Timestamp
  - User ID
  - Token count
- Enables administrative auditing and cost analysis

### Security Patching
- Automated vulnerability scanning during development
- Keeps PHP and React dependencies:
  - Up to date
  - Free from known exploits

---

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

---

## Reporting a Vulnerability

If you discover a security vulnerability in Nova-X, please report it responsibly:

1. **Do not** open a public GitHub issue
2. Email security concerns to the development team
3. Include:
   - Description of the vulnerability
   - Steps to reproduce (if applicable)
   - Potential impact assessment
   - Suggested fix (if available)

We will acknowledge receipt within 48 hours and provide an estimated timeline for resolution. Critical vulnerabilities will be addressed with high priority.

**What to expect:**
- We will investigate all reports thoroughly
- We will keep you informed of our progress
- We will credit you in the security advisory (if desired)
- We will work with you to understand and resolve the issue

---

## Security Takeaway

Nova-X treats security as **infrastructure**, not an afterthought.

Local-first data control.  
Encrypted-by-default credentials.  
Auditable, compliant, and resilient.

> _Your data stays yours. Your system stays trusted._

---

