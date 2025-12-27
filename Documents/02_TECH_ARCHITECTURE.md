# 02_TECH_ARCHITECTURE.md

## 🏗️ SYSTEM ARCHITECTURE & LOGIC FLOW
> **Technical Objective:** To establish a decoupled, high-performance architecture that optimizes communication between the local Docker environment and external AI Service Providers.

---

## 🧩 CORE ARCHITECTURAL DIAGRAM
The following flow illustrates the request-response lifecycle between the WordPress backend and the AI Orchestrator.



```mermaid
graph TD
    subgraph Client_Layer [Frontend - React/Tailwind]
        A[Dashboard UI] --> B[State Manager]
        B --> C[WP REST API Client]
    end

    subgraph Core_Logic [Plugin Engine - PHP OOP]
        C --> D[Request Router]
        D --> E{Security & License Check}
        E -- Authorized --> F[AI Orchestrator]
        E -- Denied --> G[Error Handler]
    end

    subgraph External_Cloud [AI & Billing Services]
        F --> H[OpenAI/Claude API]
        F --> I[Licensing Server]
    end

    subgraph Data_Storage [Local MariaDB]
        F --> J[(Custom DB Tables)]
        J --> K[Usage Logs]
        J --> L[Generated Assets]
    end
🛠️ COMPONENT SPECIFICATIONSComponentResponsibilityTechnical ImplementationRequest ControllerHandles AJAX/REST routing.NovaX_REST_ControllerAI OrchestratorManages prompts and model selection.NovaX_AI_EnginePattern GeneratorConverts AI JSON to Gutenberg Blocks.NovaX_ArchitectAsset ManagerSyncs AI images to Media Library.NovaX_Media_ForgeUsage MonitorTracks local token consumption.NovaX_Usage_Tracker💾 DATABASE SCHEMA (CUSTOM TABLES)To ensure zero bloat in wp_options, Nova-X utilizes high-speed custom tables.1. {prefix}_novax_logsPurpose: Tracks every AI interaction for user auditing.Structure: ID, user_id, model_used, tokens_in, tokens_out, timestamp.2. {prefix}_novax_templatesPurpose: Stores AI-generated design patterns for reuse.Structure: template_id, category, raw_json_data, created_at.🔒 SECURITY & DATA INTEGRITYAPI Key Isolation: API keys are stored as encrypted environment variables in Docker, never exposed in the browser.Request Validation: Every transaction is shielded by WordPress Nonces and current_user_can('manage_options') checks.Content Sanitization: Multi-pass scrubbing using wp_kses() before any AI data is saved to the database.🚀 DOCKER & PERFORMANCE OPTIMIZATIONAsync Processing: Long-running AI tasks (e.g., bulk posting) are offloaded to background workers.Local Caching: AI responses are cached locally to reduce API costs and improve UI latency.Isolated Assets: React components and Tailwind CSS are scoped to the nova-x namespace to prevent theme conflicts.Document Version: 1.0.0 | Environment: Docker-Local
**Would you like me to proceed with 03_FEATURE_SPECIFICATIONS.md in this same visual st