# 02_TECH_ARCHITECTURE.md

## 🏗️ SYSTEM ARCHITECTURE & LOGIC FLOW
> **Technical Objective:** To establish a decoupled, high-performance architecture that optimizes communication between the local Docker environment and external AI Service Providers while maintaining a zero-latency WordPress admin experience.

---

## 🧩 CORE ARCHITECTURAL LAYERS
Nova-X is engineered with a modular separation of concerns to ensure stability and scalability.

* **Frontend Layer:** React-driven Single Page Application (SPA) styled with Tailwind CSS for a native SaaS feel.
* **Controller Layer:** PHP-based REST API handlers managing the bridge between the UI and the server logic.
* **Intelligence Layer:** Proprietary prompt-engineering engine that formats user intent into structured AI requests.
* **Storage Layer:** Custom MariaDB tables optimized for tracking AI logs and design templates without bloating core WP tables.

---

## 🛠️ COMPONENT SPECIFICATIONS
[Visual Flow: User UI ➡️ REST Controller ➡️ AI Engine ➡️ WP Core Injection]

### 1. **AI Orchestrator (`class-nova-x-openai.php`)**
Manages all external API handshakes, handling secure key transmission and token streaming.

### 2. **REST Gateway (`class-nova-x-rest.php`)**
Provides secure custom endpoints for the React dashboard to communicate with the PHP backend asynchronously.

### 3. **The Generator (`class-nova-x-generator.php`)**
The logic engine that converts AI text and JSON responses into native WordPress posts, pages, and metadata.

---

## ⚡ TECHNICAL STACK & DEPENDENCIES

| Technology | Implementation |
| :--- | :--- |
| **PHP Version** | 8.2+ (Strictly typed for local Docker environment) |
| **JavaScript** | React 18+ with WordPress Dependency Management |
| **CSS Framework** | Tailwind CSS 3.4 (Scoped to avoid theme conflicts) |
| **API Protocols** | REST API for data, SSE for real-time text streaming |

---

## 💾 DATABASE ARCHITECTURE
Nova-X utilizes a clean-slate approach to data persistence to maintain high performance.

* **`_novax_usage_logs`**: Tracks real-time credit consumption and request timestamps.
* **`_novax_architect_templates`**: Stores AI-generated JSON layouts for instant pattern injection.
* **`_novax_settings`**: Encrypted storage for API keys and global brand identity configuration.

---

## 🔒 SECURITY & DATA INTEGRITY

* **Encryption:** API keys are stored using AES-256 encryption within the local database.
* **Validation:** 100% adherence to WordPress security protocols (Nonces, Sanitization, and Capability checks).
* **Isolation:** All plugin assets are namespace-protected to prevent styling or variable collisions with third-party themes.

---

## 📈 PERFORMANCE OPTIMIZATION (DOCKER)

* **Async Workers:** Background processing for bulk content generation to prevent PHP timeouts.
* **Object Caching:** Native integration with Redis/Memcached if available in the Docker stack.
* **Asset Bundling:** Minified, production-ready JS/CSS bundles served via the local file system for maximum speed.

---
*Document Version: 1.0.0 | Environment: Docker-Local*