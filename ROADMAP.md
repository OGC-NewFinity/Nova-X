# Nova-X | AI Theme Architect - Technical Specification & Roadmap

## 1. Project Overview
Nova-X is a specialized WordPress plugin designed to act as an AI-driven theme architect. It utilizes LLMs (OpenAI/Anthropic) to generate, modify, and preview WordPress theme components in real-time using Tailwind CSS.

## 2. Infrastructure Framework
- **Environment:** Localhost (LocalWP) development flowing into Hostinger production.
- **Backend:** PHP 7.4+, WordPress Core APIs.
- **Frontend:** React-based dashboard integrated into the WP Admin.
- **CSS Engine:** Tailwind CSS via CDN for development, compiled for production.
- **AI Integration:** Secure constant-based API handling (NOVA_X_API_KEY).

## 3. Detailed Development Roadmap

### Phase 1: Core Engine Optimization
- [ ] **Secure API Bridge:** Implement robust error handling for API timeouts and invalid keys.
- [ ] **Environment Detection:** Logic to switch between development (Local) and production (Live) modes.
- [ ] **File System Access:** Secure methods to read/write to the active theme directory.

### Phase 2: AI Orchestration & Prompting
- [ ] **System Prompt Engineering:** Define the "Architect Role" (e.g., "You are a senior WP developer using Tailwind...").
- [ ] **Context Injection:** Automatically include `header.php`, `footer.php`, and `style.css` in AI prompts for consistency.
- [ ] **Token Management:** Monitor usage to prevent overflow during large code generations.

### Phase 3: Advanced Dashboard (UI/UX)
- [ ] **Main Panel:** Integrated chat interface with code-highlighting.
- [ ] **Live Preview Frame:** An `iframe` sandbox to render generated components without breaking the admin UI.
- [ ] **History Log:** A local database table to save previous AI versions for easy rollback.

### Phase 4: Feature Module Set
- [ ] **Smart Section Generator:** Pre-defined prompts for Heros, Sliders, and Grid layouts.
- [ ] **Global Style Manager:** AI-driven modification of `tailwind.config.js` settings.
- [ ] **Dynamic Content Injection:** Using AI to generate placeholder text and images.

### Phase 5: Production & Deployment
- [ ] **Code Sanitization:** Use `wp_kses` and custom logic to ensure AI doesn't inject malicious scripts.
- [ ] **Export Module:** One-click packaging of the plugin for production deployment.
- [ ] **Performance Audit:** Minification of assets and lazy-loading of AI components.

## 4. Planning Diagram (Technical Workflow)
1. **Request:** User types "Add a blue hero section with a signup form."
2. **Analysis:** Nova-X reads current theme styles and sends a structured prompt to OpenAI.
3. **Drafting:** AI returns a JSON object containing HTML (with Tailwind classes) and necessary PHP.
4. **Simulation:** The code is rendered in the Dashboard Live Preview.
5. **Execution:** Upon "Save," the code is appended to the theme's template files.

---
*Generated for Nova-X Project | Version 1.0.0*