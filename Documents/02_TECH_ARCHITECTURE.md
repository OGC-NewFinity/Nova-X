# 02 — TECHNICAL ARCHITECTURE  
## 🏗️ System Architecture & Logic Flow

📄 **Document Version:** 1.0.0  
🧱 **Environment:** Docker-Local  
🧠 **Architecture Style:** Decoupled · Modular · High-Performance  
⚙️ **Design Principle:** Zero-latency UX, maximum isolation  

---

## 🎯 Technical Objective

The Nova-X technical architecture is designed to establish a **decoupled, high-performance execution model** that optimizes communication between:

- Local **Docker-based WordPress environments**
- External **AI Service Providers**
- A **React-powered Admin Interface**

All while preserving a **native, zero-latency WordPress Admin experience**.

> _No blocking calls. No UI freeze. No architectural shortcuts._

---

## 🧩 Core Architectural Layers

Nova-X is engineered with a **strict separation of concerns**, ensuring long-term stability, maintainability, and horizontal scalability.


::contentReference[oaicite:0]{index=0}


### 🖥️ Frontend Layer
- React-driven **Single Page Application (SPA)**
- Styled with **scoped Tailwind CSS**
- Delivers a **native SaaS-grade experience** inside WP Admin

---

### 🔁 Controller Layer
- PHP-based **REST API handlers**
- Acts as the secure bridge between UI and server logic
- Fully asynchronous communication model

---

### 🧠 Intelligence Layer
- Proprietary **Prompt Engineering Engine**
- Converts raw user intent into **structured, deterministic AI requests**
- Abstracts AI complexity away from the user

---

### 💾 Storage Layer
- Custom **MariaDB tables**
- Optimized for:
  - AI request logging
  - Usage tracking
  - Design template persistence  
- Prevents bloating of WordPress core tables

---

## 🛠️ Component Specifications

### 🔁 Execution Flow  
**User UI ➜ REST Controller ➜ AI Engine ➜ WordPress Core Injection**


::contentReference[oaicite:1]{index=1}


---

### 1️⃣ AI Orchestrator  
**`class-nova-x-openai.php`**

- Manages all external AI API handshakes  
- Secure API key transmission  
- Token-based streaming for real-time output  
- Centralized error handling and retry logic

---

### 2️⃣ REST Gateway  
**`class-nova-x-rest.php`**

- Defines secure, custom REST endpoints  
- Enables asynchronous communication with the React dashboard  
- Enforces authentication, validation, and permission checks

---

### 3️⃣ The Generator  
**`class-nova-x-generator.php`**

- Core execution engine  
- Converts AI-generated text and JSON into:
  - WordPress posts
  - Pages
  - Metadata
  - Block-based layouts  
- Writes **directly into WordPress Core**—no intermediaries

---

## ⚡ Technical Stack & Dependencies

| Technology | Implementation |
|---------|----------------|
| **PHP** | 8.2+ (Strict typing, Docker-optimized) |
| **JavaScript** | React 18+ with WordPress dependency management |
| **CSS Framework** | Tailwind CSS 3.4 (Scoped, conflict-safe) |
| **API Protocols** | REST API (data), SSE (real-time streaming) |

---

## 💾 Database Architecture

Nova-X adopts a **clean-slate persistence strategy** to maintain performance and observability.

### 📊 Custom Tables

- **`_novax_usage_logs`**  
  Tracks AI credit consumption and request timestamps in real time

- **`_novax_architect_templates`**  
  Stores AI-generated JSON layouts for instant reuse and pattern injection

- **`_novax_settings`**  
  Encrypted storage for API keys and global brand identity configuration

---

## 🔒 Security & Data Integrity

### 🔐 Encryption
- API keys stored using **AES-256 encryption**
- Encrypted at rest within the local database

### 🛡️ Validation
- Full compliance with WordPress security standards:
  - Nonces
  - Input sanitization
  - Capability checks

### 🧩 Isolation
- Namespaced PHP, JS, and CSS assets  
- Zero collision risk with themes or third-party plugins

---

## 📈 Performance Optimization (Docker)


::contentReference[oaicite:2]{index=2}


### ⚙️ Async Workers
- Background processing for bulk generation
- Prevents PHP execution timeouts

### 🧠 Object Caching
- Native support for Redis / Memcached  
- Automatically detected within the Docker stack

### 📦 Asset Bundling
- Minified, production-ready JS & CSS  
- Served directly from the local filesystem  
- Zero CDN dependency, maximum speed

---

## 🧠 Architectural Takeaway

Nova-X is not layered for convenience.  
It is layered for **control**.

Each component is isolated.  
Each flow is deterministic.  
Each bottleneck is engineered out.

> _Fast locally. Scalable globally. Stable by design._

---
