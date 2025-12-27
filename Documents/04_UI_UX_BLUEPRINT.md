# 04_UI_UX_BLUEPRINT.md

## 🎨 DESIGN PHILOSOPHY: THE SAAS EXPERIENCE
> **UX Objective:** To create a high-speed, intuitive interface within the WordPress Admin that feels like a modern standalone application. Nova-X leverages Tailwind CSS and React to provide a fluid, "zero-refresh" user experience.

---

## 🗺️ INTERFACE HIERARCHY
The Nova-X dashboard is organized into four primary zones for maximum workflow efficiency.

* **Zone 1: Global Navigation:** A sidebar for switching between the Architect, Content Forge, and Settings modules.
* **Zone 2: The Command Center:** The primary workspace where AI prompts are entered and processed.
* **Zone 3: Live Preview Pane:** A real-time viewport showing generated Gutenberg blocks or content drafts.
* **Zone 4: Resource Bar:** A footer or header element tracking token usage and system connectivity status.

---

## 🛠️ VISUAL DESIGN TOKENS
To ensure a professional aesthetic, Nova-X utilizes a strict set of design tokens within the Tailwind configuration.

| Token | Value / Purpose | Implementation |
| :--- | :--- | :--- |
| **Primary Color** | Dark Mode Slate / Indigo Accent | `bg-slate-900`, `text-indigo-400` |
| **Typography** | Inter / System Sans-Serif | `font-sans` |
| **Spacing** | 4px Grid System | `p-4`, `m-2`, `gap-4` |
| **Radius** | Soft Rounded Edges | `rounded-lg` |
| **Transitions** | Fast Ease-In-Out (150ms) | `transition-all duration-150` |

---

## 🔄 THE USER JOURNEY (ONBOARDING)
[Visual Flow: Welcome ➡️ API Setup ➡️ Site Definition ➡️ First Generation]

### 1. **Handshake & Auth**
The user enters their encrypted API key; the system performs a real-time connection test via `class-nova-x-rest.php`.

### 2. **Niche Definition**
A guided wizard where the user defines the site's brand voice, target audience, and primary color palette.

### 3. **The First "Bake"**
Nova-X generates a sample landing page section to demonstrate the synergy between **The Architect** and **Media Lab**.

---

## ⚡ INTERACTIVITY SPECIFICATIONS

### **AI Streaming UI**
Instead of a static loading spinner, Nova-X utilizes **Server-Sent Events (SSE)** to stream AI responses directly into the UI, providing immediate visual feedback.

### **Gutenberg Live-Sync**
A "Send to Editor" button that instantly migrates the AI-generated JSON patterns from the React dashboard to the native WordPress Page Editor.

### **Responsive Workbench**
The dashboard is fully responsive, allowing developers to manage AI content generation from tablets or mobile devices in the local Docker environment.

---

## 📉 STATE MANAGEMENT & FEEDBACK
* **Success Notifications:** Toasts appearing in the top-right corner for completed generations.
* **Error Handling:** Clear, non-technical explanations for API timeouts or token exhaustion.
* **Progress Indicators:** Skeleton loaders and progress bars during multi-image generation or bulk posting.

---

## 🔒 ADMIN INTEGRATION
* **Native Feel:** The dashboard is wrapped in the standard WordPress admin menu while maintaining its own unique Tailwind-scoped styling.
* **Menu Architecture:** High-level "Nova-X" menu with sub-pages for "Dashboard," "Architect," "Content," and "License."

---
*Document Version: 1.0.0 | Environment: Docker-Local*