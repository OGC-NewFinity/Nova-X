# Nova-X — Cursor Development Rules

This document provides strict rules for Cursor Agents during Nova-X plugin development.

## ✅ Scope of Action

- Cursor may only execute clearly scoped prompts.
- Avoid bulk changes unless explicitly requested.
- Always provide a summary of actions upon completion.

## 📁 File Handling Rules

- **Do not overwrite** existing files without confirmation.
- When creating new files:
  - Follow `/inc/`, `/admin/`, `/assets/`, `/templates/`, `/docs/` structure.
  - Class names must follow: `Nova_X_ClassName` (PascalCase)
  - File names must use: `class-nova-x-name.php` (kebab-case)

## 📚 Documentation Behavior

- Never create a new `/Documents/` folder again.
- All documentation belongs in `/docs/` only.
- Use PascalCase for filenames: `01_EXECUTIVE_SUMMARY.md`, `02_TECH_ARCHITECTURE.md`

## 🚫 Forbidden Actions

- Do not install third-party packages or dependencies.
- Never add sample data, demo scripts, or placeholder templates unless prompted.
- Avoid modifying class files unless explicitly asked.

## ⚙️ Safety & Linting

- All code must pass PHP linting.
- Use `ABSPATH` guard for every PHP file.
- Use `sanitize_`, `esc_` functions for all input/output.

## 🧪 Testing Guidelines

- Use simulated dummy content for API calls unless testing is enabled.
- Do not commit untested logic into generator or provider files.

---

_This rule set is enforced for consistent, safe, and professional development._
