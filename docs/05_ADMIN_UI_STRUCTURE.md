# Admin UI Structure

## Design Philosophy: The SaaS Experience

**Document Version:** 1.0.0  
**Environment:** Docker-Local  
**UX Objective:** SaaS-grade speed inside WordPress  
**Interaction Model:** Zero-refresh · Real-time · Deterministic  

---

## UX Objective

Nova-X is designed to **feel like a modern standalone SaaS**—while living entirely inside the WordPress Admin.

By combining **React** and **Tailwind CSS**, the interface delivers:
- Instant feedback
- Fluid navigation
- Zero page reloads

> _WordPress shell. SaaS soul._

---

## Interface Hierarchy

The Nova-X dashboard is structured into **four high-efficiency zones**, each optimized for a specific cognitive task.

### Zone 1 — Global Navigation
- Persistent sidebar  
- Fast switching between:
  - Architect
  - Content Forge
  - Media Lab
  - Settings  
- Minimal depth, zero clutter

---

### Zone 2 — The Command Center
- Primary workspace  
- AI prompts are authored, refined, and executed here  
- Designed for focus, not distraction

---

### Zone 3 — Live Preview Pane
- Real-time visualization of:
  - Gutenberg blocks
  - Content drafts
- No guessing. What you see is what ships.

---

### Zone 4 — Resource Bar
- Header or footer element  
- Displays:
  - Token consumption
  - API connectivity
  - System health status  
- Always visible, never intrusive

---

## Visual Design Tokens

Nova-X enforces a **strict design token system** via Tailwind configuration to maintain visual coherence and performance.

| Token | Value / Purpose | Implementation |
|----|----|----|
| **Primary Color** | Dark mode slate with indigo accent | `bg-slate-900`, `text-indigo-400` |
| **Typography** | Inter / System Sans-Serif | `font-sans` |
| **Spacing** | 4px grid system | `p-4`, `m-2`, `gap-4` |
| **Radius** | Soft rounded edges | `rounded-lg` |
| **Transitions** | Fast ease-in-out (150ms) | `transition-all duration-150` |

> _Design tokens are law. Consistency scales._

---

## The User Journey (Onboarding)

### Visual Flow  
**Welcome ➜ API Setup ➜ Site Definition ➜ First Generation**

---

### 1. Handshake & Authentication
- User enters encrypted API key  
- Real-time connection test executed via `class-nova-x-rest.php`  
- Immediate success or failure feedback

---

### 2. Niche Definition
- Guided wizard experience  
- User defines:
  - Brand voice
  - Target audience
  - Primary color palette  
- Stored as global context for all generations

---

### 3. The First "Bake"
- Nova-X generates a sample landing section  
- Demonstrates synergy between:
  - **The Architect**
  - **Media Lab**  
- Instant "aha" moment

---

## Interactivity Specifications

### AI Streaming UI
- No static spinners  
- Uses **Server-Sent Events (SSE)**  
- AI output streams live into the interface  
- Perceived speed > raw speed

---

### Gutenberg Live-Sync
- **"Send to Editor"** action  
- Instantly migrates AI-generated JSON patterns  
- From React dashboard → Native WP Editor  
- Zero export/import friction

---

### Responsive Workbench
- Fully responsive dashboard  
- Usable on:
  - Desktop
  - Tablet
  - Mobile  
- Works seamlessly in local Docker environments

---

## State Management & Feedback

### Success Notifications
- Toast messages (top-right)  
- Clear confirmation for completed actions

### Error Handling
- Human-readable explanations  
- No stack traces, no jargon  
- Covers API timeouts, token exhaustion, and connectivity issues

### Progress Indicators
- Skeleton loaders  
- Progress bars for:
  - Multi-image generation
  - Bulk posting operations

---

## Admin Integration

### Native Feel
- Wrapped inside standard WordPress Admin shell  
- Retains Nova-X's own **Tailwind-scoped identity**

### Menu Architecture
- Top-level **"Nova-X"** menu  
- Sub-pages:
  - Dashboard
  - Architect
  - Content
  - License  

Clean. Predictable. Scalable.

---

## UX Takeaway

Nova-X doesn't look like WordPress.  
It **outperforms** it.

Every pixel serves speed.  
Every interaction reduces friction.  
Every workflow respects the user's time.

> _If it feels fast, it is fast. If it feels simple, it's engineered._

---

