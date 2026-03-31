# CONTEXT.md — C2 by MDC21 Agency

> Pega este archivo completo al inicio de cualquier chat de IA antes de escribir código.
> La IA debe leerlo completo y confirmar que lo ha entendido antes de empezar.
> Versión: 4.1 · Fecha: Marzo 2026 · Estado: CÓDIGO COMPLETO — Pipeline testeado en local, solo falta infra + credenciales

---

## QUÉ ESTAMOS CONSTRUYENDO

**C2** es una **fábrica automatizada de webs**. Un socio crea un activo (dominio + vertical + contexto), y los agentes de IA construyen la web completa, la despliegan y la operan. El contenido SEO es secundario — el objetivo principal es generar y publicar webs funcionales de forma autónoma.

**C2 NO es** un gestor de contenido ni un CMS. Es una plataforma que:
- **Genera webs completas** a partir de un contexto (calculadoras, comparadores, herramientas verticales)
- **Diseña automáticamente** (colores, tipografía, layout) según el vertical y tono
- **Despliega y opera** las webs con 8 agentes de IA en background
- **Monitorea** rendimiento, uptime y métricas de todo el portafolio

### Flujo principal
```
Socio crea activo en /admin (dominio + contexto)
    → Setup automático (políticas + prompts)
    → Web Builder Agent genera la web completa
    → Policy Brand valida todo el contenido
    → QA testea (Pest + Lighthouse + Playwright)
    → Build & Release deploya a staging
    → Socio aprueba (N3) → producción
    → Operación continua (Orquestador cada 12h + Infra cada hora)
```

### Infraestructura
Todo en un solo servidor (C2 + webs generadas):
```
/var/www/c2-mdc21/            ← Panel de control
/var/www/calculahipoteca/     ← Web generada por agentes
/var/www/comparaenergia/      ← Web generada por agentes
...
Nginx → cada dominio apunta a su carpeta
PostgreSQL + Redis compartidos
Certbot → SSL automático por dominio
```

### Lo que el socio hace manualmente
1. Compra el dominio
2. Apunta DNS a la IP del servidor
3. Crea el activo en /admin con el contexto
4. Aprueba el deploy a producción (N3)
5. Abre el dashboard 30min/día para supervisar y aprobar

---

## STACK TÉCNICO — VERSIONES EXACTAS INSTALADAS

### BACKEND
| Paquete | Versión | Uso |
|---|---|---|
| PHP | 8.5.3 | Runtime |
| Laravel Framework | ^12.0 | Core |
| Laravel Horizon | ^5.45 | Dashboard de colas, monitoreo de jobs en tiempo real |
| Laravel Reverb | ^1.8 | WebSockets nativos, logs de agentes en vivo |
| Laravel Pennant | ^1.21 | Feature flags por activo, ganadores A/B sin deploy |
| Laravel Pulse | ^1.7 | Rendimiento en producción: jobs lentos, queries, caché |
| Laravel Telescope | * (dev only) | Debug local únicamente, nunca a producción |
| Laravel Tinker | ^2.10.1 | REPL |
| Laravel Pint | ^1.29 | Code style automático, corre en CI antes de merge |
| Pest PHP | ^3.8 | Tests unitarios, estándar del proyecto (no PHPUnit) |
| Spatie Activity Log | ^4.12 | Audit trail de todas las acciones de agentes y humanos |
| Spatie Laravel PDF | ^2.4 | PDFs descargables con resultados de calculadoras |
| predis/predis | * | Cliente Redis |
| PostgreSQL | 16.4 | Base de datos principal, JSONB siempre |
| Redis | 7 (Homebrew) | Colas Horizon + caché + sesiones |

### FRONTEND
| Paquete | Versión | Uso |
|---|---|---|
| Vue | ^3.5.0 | Componentes reactivos custom |
| @inertiajs/vue3 | ^2.0.0 | Bridge Laravel-Vue sin API REST |
| TypeScript | ^5.7.0 | Tipado strict mode obligatorio |
| Tailwind CSS | ^4.0.0 | Estilos |
| echarts | ^5.6.0 | Gráficas del dashboard: scores, tráfico, ingresos |
| vue-i18n | ^11.3.0 | Internacionalización es/en |
| Vite | ^7.0.7 | Bundler, HMR en desarrollo |
| @vitejs/plugin-vue | ^6.0.0 | Plugin Vue para Vite 7 |
| vue-tsc | ^2.0.0 | Type checking de Vue |

### ADMIN PANEL
| Paquete | Versión | Uso |
|---|---|---|
| Filament | ^3.3 | Panel en `/admin`, CRUD, cola aprobaciones N3, audit trail visual |

### CI/CD
| Herramienta | Uso |
|---|---|
| GitHub Actions | Pipeline: Pint → Pest → deploy staging (en develop) |
| Laravel Forge | Gestión deploys y Nginx sobre EC2 |
| PHP en CI | 8.4 (compatible con lock generado para 8.4+) |

### ANALÍTICA Y MONITOREO (pendiente instalar)
- Umami — analytics self-hosted de activos, GDPR compliant
- Prometheus + Grafana — métricas operativas
- Sentry — errores de agentes en producción
- Uptime Kuma — uptime de todas las webs cada 5 minutos

### INTELIGENCIA ARTIFICIAL (pendiente configurar, keys en .env)
- Claude API — `claude-sonnet-4-5` Batch (Orquestador 12h) + `claude-haiku-4-5` (Policy)
- Prompt Caching activo: 90% ahorro tokens entrada
- OpenAI API — `gpt-4o` (research SERP) + `gpt-4o-mini` Batch (contenido, 50% dto)
- OpenAI Batch API Polling — Laravel Queues para procesar respuestas diferidas

### NOTIFICACIONES (pendiente configurar)
- Telegram Bot API — canal principal, grupos separados: infra · contenido · negocio/leads
- Resend — email secundario + drip campaigns

### INTEGRACIONES (pendiente configurar, keys en .env)
- Cloudflare API — gestión DNS de cada activo
- GitHub API (Octokit) — estado deploys, CI y branches
- Google Search Console API — posiciones, CTR, indexación
- SEMrush API — keywords, backlinks, oportunidades
- Uptime Kuma API — datos de disponibilidad al dashboard

### SEGURIDAD (producción)
- AWS Secrets Manager — todas las API keys, rotación automática cada 90 días
- Nunca poner API keys reales en `.env` de producción

### INFRAESTRUCTURA PRODUCCIÓN (pendiente)
- AWS EC2 — servidor principal de C2
- AWS RDS — PostgreSQL gestionado, backups automáticos
- AWS ElastiCache — Redis gestionado
- AWS S3 — PDFs generados, assets, backups
- Cloudflare — DNS + SSL + CDN + DDoS de activos y de C2

---

## ESTRUCTURA DE CARPETAS — ESTADO REAL

```
c2-mdc21/
├── .github/workflows/ci.yml          # Pint → Pest → deploy staging
├── app/
│   ├── Filament/
│   │   ├── Resources/                # 10 recursos CRUD (40 archivos)
│   │   │   ├── AgentRunResource.php
│   │   │   ├── ApprovalResource.php
│   │   │   ├── ArtifactResource.php
│   │   │   ├── BlogPostResource.php
│   │   │   ├── EditorialCalendarResource.php
│   │   │   ├── ExperimentResource.php
│   │   │   ├── LeadResource.php
│   │   │   ├── NicheConfigResource.php
│   │   │   ├── PolicyResource.php
│   │   │   └── PromptVersionResource.php
│   │   └── Widgets/
│   │       ├── AgentRunsOverviewWidget.php   # Stats agentes en dashboard
│   │       ├── PendingApprovalsWidget.php    # Cola N3 con Aprobar/Denegar
│   │       └── NicheScoresWidget.php         # Portafolio con Score Compuesto
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php             # Base abstracto
│   │   │   ├── DashboardController.php    # Stats, agentes, aprobaciones, timeline, scores
│   │   │   ├── AgentRunController.php     # Perfiles por agente, métricas, filtros, historial
│   │   │   ├── ApprovalController.php     # Cola N3, approve/deny con audit trail, gobernanza
│   │   │   ├── NicheConfigController.php  # Portafolio con scores y distribución
│   │   │   ├── ContentController.php      # Editorial board, filtros por estado/activo
│   │   │   └── LeadController.php         # Pipeline leads (oculto, futuro)
│   │   └── Middleware/
│   │       └── HandleInertiaRequests.php   # Comparte auth + flash a todas las páginas
│   ├── Jobs/Agents/
│   │   ├── BaseAgentJob.php                  # Abstracto: registra en agent_runs
│   │   ├── OrchestratorAgentJob.php          # Claude Sonnet Batch, ciclo 12h
│   │   ├── PolicyBrandAgentJob.php           # Claude Haiku, filtro transversal
│   │   ├── SeoContentAgentJob.php            # gpt-4o + gpt-4o-mini Batch
│   │   ├── DistributionAgentJob.php          # Claude Sonnet + gpt-4o, N3 siempre
│   │   ├── EngagementRetentionAgentJob.php   # gpt-4o-mini Batch
│   │   ├── MonetizationLeadsAgentJob.php     # Claude Sonnet + gpt-4o
│   │   ├── BuildReleaseAgentJob.php          # Script puro, sin IA
│   │   ├── InfraReliabilityAgentJob.php      # Script puro, cada hora
│   │   └── QAExperimentationAgentJob.php     # Playwright + Lighthouse + Pest
│   ├── Models/                        # 11 modelos con HasUuids + casts
│   │   ├── AgentRun.php
│   │   ├── Approval.php
│   │   ├── Artifact.php
│   │   ├── BlogPost.php
│   │   ├── EditorialCalendar.php
│   │   ├── Experiment.php
│   │   ├── Lead.php
│   │   ├── NicheConfig.php
│   │   ├── Policy.php
│   │   ├── PromptVersion.php
│   │   └── User.php
│   ├── Events/
│   │   ├── AgentRunUpdated.php        # Broadcast cuando un agente completa/falla
│   │   ├── ApprovalCreated.php        # Broadcast cuando se crea una aprobación N3
│   │   └── InfraStatusUpdated.php     # Broadcast estado de infra
│   ├── Providers/
│   │   ├── AppServiceProvider.php     # Singletons: Claude, ChatGPT, Rate, Prompt, Score, Telegram
│   │   ├── Filament/AdminPanelProvider.php
│   │   ├── HorizonServiceProvider.php
│   │   └── TelescopeServiceProvider.php
│   └── Services/
│       ├── AI/
│       │   ├── ClaudeService.php       # message() + batchMessage() + Prompt Caching
│       │   ├── ChatGPTService.php      # message() + batch() + getBatchStatus()
│       │   └── RateLimiterService.php  # attempt() con reintentos automáticos
│       ├── PromptRegistry.php          # Carga prompts activos desde DB con cache Redis
│       ├── ScoreComposite.php          # Calcula score 0-100 con 6 dimensiones ponderadas
│       └── TelegramService.php        # send() + infraAlert() + contentNotify() + approvalNeeded()
├── config/
│   ├── activitylog.php
│   ├── services.php                   # Claude, OpenAI, Telegram, Cloudflare, GitHub, SEMrush
│   └── ...
│   │   └── composables/
│   │       └── useRealtimeDashboard.ts    # WebSocket listener para dashboard en vivo
├── database/migrations/               # 20 migraciones ejecutadas
│   ├── 0001_01_01_* (3 Laravel base)
│   ├── 2026_03_16_000001_create_agent_runs_table.php
│   ├── 2026_03_16_000002_create_approvals_table.php
│   ├── 2026_03_16_000003_create_prompt_versions_table.php
│   ├── 2026_03_16_000004_create_policies_table.php
│   ├── 2026_03_16_000005_create_experiments_table.php
│   ├── 2026_03_16_000006_create_artifacts_table.php
│   ├── 2026_03_16_000007_create_blog_posts_table.php
│   ├── 2026_03_16_000008_create_leads_table.php
│   ├── 2026_03_16_000009_create_editorial_calendar_table.php
│   ├── 2026_03_16_000010_create_niche_configs_table.php
│   ├── 2026_03_16_203037_create_telescope_entries_table.php
│   ├── 2026_03_17_074227_create_activity_log_table.php
│   ├── 2026_03_17_074228_add_event_column_to_activity_log_table.php
│   ├── 2026_03_17_074229_add_batch_uuid_column_to_activity_log_table.php
│   ├── 2026_03_17_074306_create_features_table.php        # Pennant
│   └── 2026_03_17_074311_create_pulse_tables.php          # Pulse
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── app.ts                     # Entry point Inertia + Vue 3 + i18n
│   │   ├── bootstrap.ts               # Axios con tipos TS
│   │   ├── i18n.ts                    # createI18n con es/en
│   │   ├── Components/
│   │   │   ├── StatsCard.vue              # Card de métrica reutilizable
│   │   │   ├── ApprovalRow.vue            # Fila aprobación con aprobar/denegar inline
│   │   │   ├── AgentStatusGrid.vue        # Grid 3x3 agentes expandible con descripción
│   │   │   ├── AgentProfileCard.vue       # Card detallada por agente (métricas, capa, modelo)
│   │   │   ├── ActivityTimeline.vue       # Feed cronológico agentes + aprobaciones
│   │   │   └── Charts/
│   │   │       ├── AgentActivityChart.vue # Bar chart ECharts — actividad semanal
│   │   │       ├── ScoreGaugeChart.vue    # Gauge ECharts — score portafolio
│   │   │       └── LeadsPieChart.vue      # Donut ECharts — pipeline leads (futuro)
│   │   ├── Layouts/
│   │   │   └── AppLayout.vue              # Sidebar + topbar + responsive + flash
│   │   ├── Pages/
│   │   │   ├── Dashboard.vue              # 5 métricas, grid agentes, gráficas, aprobaciones, timeline
│   │   │   ├── AgentRuns/Index.vue        # Perfiles por capa, filtros, errores, historial
│   │   │   ├── Approvals/Index.vue        # Urgencia, gobernanza, audit trail, filtros
│   │   │   ├── NicheConfigs/Index.vue     # Portafolio cards/tabla, score, detalle
│   │   │   ├── Content/Index.vue          # Editorial board, tabs estado, E-E-A-T
│   │   │   └── Leads/Index.vue            # Pipeline leads (oculto, futuro)
│   │   └── types/
│   │       └── index.d.ts             # Tipos TS: AgentRun, Approval, NicheConfig, etc.
│   └── lang/
│       ├── es/app.json                # Traducciones español
│       └── en/app.json                # Traducciones inglés
├── tests/
│   ├── Feature/ExampleTest.php        # GET /admin/login → 200
│   └── Unit/ExampleTest.php
├── database/seeders/
│   ├── DemoSeeder.php                     # Nichos, agent_runs, aprobaciones, leads
│   └── ContentSeeder.php                  # 11 artículos de prueba
├── CONTEXT.md                         # Este archivo
├── tsconfig.json                      # TypeScript strict mode
└── vite.config.js                     # Laravel + Vue + Tailwind CSS 4
```

---

## BASE DE DATOS — 19 MIGRACIONES EJECUTADAS

### Tablas core (10)
```
agent_runs          # toda ejecución de agente: agent_type, status, input(jsonb),
                    # output(jsonb), metadata(jsonb), error, started_at, finished_at
approvals           # cola N3: action, level(N1/N2/N3), status, requested_by,
                    # decided_by, reason, decision_note, context(jsonb), decided_at
prompt_versions     # versiones de prompts: agent_type, version, model,
                    # prompt_text, metrics(jsonb), is_active
policies            # reglas Policy & Brand: scope, tipo, contenido, asset_id, activo(bool)
experiments         # A/B tests: asset_id, variantes(jsonb), métrica, resultados(jsonb), ganador
artifacts           # artefactos generados: type, path, metadata(jsonb), agent_run_id
blog_posts          # E-E-A-T: title, slug, content, status, author, sources(jsonb),
                    # methodology, niche_config_id, published_at
leads               # score, asset_id, provider, status pipeline, data(jsonb)
editorial_calendar  # canal, draft, estado, publisher_id asignado
niche_configs       # dominio, vertical, CPL, colores, config(jsonb), is_active
```

### Tablas adicionales instaladas
```
activity_log        # Spatie Activity Log (3 migraciones)
features            # Laravel Pennant
pulse_*             # Laravel Pulse (múltiples tablas)
telescope_entries   # Laravel Telescope (dev only)
users / cache / jobs # Laravel base
```

### Convenciones obligatorias
- **UUID como PK** en todas las tablas — nunca autoincrement
- **JSONB** para outputs de agentes, configs y métricas
- Todo Job registra en `agent_runs` al iniciar y al finalizar

---

## LOS 9 AGENTES — JOBS IMPLEMENTADOS

### CAPA 0 — NÚCLEO

**OrchestratorAgentJob** (`queue: agents`)
- IA: Claude Sonnet Batch · ciclo 12h via Scheduler
- Lee métricas de todos los activos (Umami + tablas propias)
- Calcula Score Compuesto via `ScoreComposite` (6 dimensiones ponderadas)
- Clasifica acciones en N1/N2/N3
- N3 → inserta en `approvals` y notifica por Telegram/email
- Genera reporte P&L semanal automático

**PolicyBrandAgentJob** (`queue: agents`)
- IA: Claude Haiku · se llama ANTES de cualquier contenido externo
- Consulta tabla `policies` filtrada por asset_id
- Devuelve `approved/rejected` + razón
- Si `rejected` → notifica al humano para override o descarte
- El override queda en audit trail (Spatie Activity Log)

### CAPA 1 — FUNCIONALES

**SeoContentAgentJob** (`queue: agents`, timeout: 600s)
- IA: gpt-4o (research SERP) + gpt-4o-mini Batch (artículos)
- E-E-A-T obligatorio: autoría, fuentes, metodología, fecha
- Policy valida cada artículo antes de pasar a borrador
- Publicación siempre manual (N3)

**DistributionAgentJob** (`queue: agents`)
- IA: Claude Sonnet + gpt-4o · NUNCA publica solo
- Prepara texto listo para copiar + dónde + por qué ahora + riesgo reputacional
- Policy valida + envía al publicador humano
- Publicador copia manualmente desde su cuenta real (N3 siempre)

**EngagementRetentionAgentJob** (`queue: agents`)
- IA: gpt-4o-mini Batch
- Tasks: `newsletter` | `faq` | `drip` | `pdf` | `ab_test`
- Newsletter → Policy valida → publicador humano copia y lanza (N3)
- Drip emails automáticos vía Resend (N2, solo tras activación humana inicial)
- PDFs vía Spatie Laravel PDF
- A/B tests con Pennant → ganador requiere confirmación humana

**MonetizationLeadsAgentJob** (`queue: agents`)
- IA: Claude Sonnet + gpt-4o
- Tasks: `score_leads` | `find_providers` | `generate_proposal`
- Scoring: score > 70 → auto · 40-70 → revisión humana · < 40 → descarte
- Propuestas CPL → Policy valida → humano aprueba y envía él mismo (N3)

### CAPA 2 — OPERATIVOS (sin IA, scripts puros)

**BuildReleaseAgentJob** (`queue: agents-ops`)
- GitHub Actions + Forge
- Staging automático (N2) · Producción siempre N3
- Rollback automático en < 2 min si health check falla
- Si deniega → abre issue en GitHub con el feedback

**InfraReliabilityAgentJob** (`queue: agents-ops`)
- Scheduler cada hora
- INFO → log · WARNING → Telegram+email · CRITICAL → bloquea deploys
- NUNCA modifica DNS/Cloudflare solo → solo propone al humano
- Deploys se desbloquean tras confirmación humana de cierre

**QAExperimentationAgentJob** (`queue: agents-ops`, timeout: 600s)
- Tasks: `qa` | `evaluate_ab`
- Playwright valida links, formularios y renders
- Lighthouse CI bloquea si Performance < 60 o LCP > 4s
- Pest PHP corre todos los tests unitarios
- Override humano queda en audit trail con nombre del aprobador
- Ganador A/B → confirmación humana → aplica en producción via Pennant

---

## SERVICES IMPLEMENTADOS

### ClaudeService
```php
app(ClaudeService::class)->message(prompt, model?, maxTokens?, systemPrompt[], useCache?)
app(ClaudeService::class)->batchMessage(prompt, model?, maxTokens?)
app(ClaudeService::class)->extractText(response)
```
- Prompt Caching activo (`cache_control: ephemeral`) para 90% ahorro en tokens de entrada
- Rate limiting via `RateLimiterService`

### ChatGPTService
```php
app(ChatGPTService::class)->message(prompt, model?, maxTokens?, systemPrompt?)
app(ChatGPTService::class)->batch(requests[], model?)     // OpenAI Batch API
app(ChatGPTService::class)->getBatchStatus(batchId)       // Polling
app(ChatGPTService::class)->extractText(response)
```

### RateLimiterService
```php
app(RateLimiterService::class)->attempt('claude', callback, maxAttempts, decaySeconds)
app(RateLimiterService::class)->availableIn('openai')
app(RateLimiterService::class)->clear('claude')
```
- Providers: `claude`, `claude-batch`, `openai`, `openai-batch`

### PromptRegistry
```php
app(PromptRegistry::class)->get('orchestrator')           // PromptVersion activo
app(PromptRegistry::class)->getPromptText('seo_content')
app(PromptRegistry::class)->getModel('policy_brand', fallback)
app(PromptRegistry::class)->recordMetrics('orchestrator', metrics[])
app(PromptRegistry::class)->invalidate('seo_content')
```
- Cache Redis de 5 minutos por agente

### ScoreComposite
```php
app(ScoreComposite::class)->calculate(nicheConfigId, metrics[])  // Score 0-100
app(ScoreComposite::class)->getLatest(nicheConfigId)             // Desde Redis
app(ScoreComposite::class)->classify(score)                       // excellent/good/average/poor/critical
app(ScoreComposite::class)->detectAlerts(nicheConfigId, metrics[])
```
- 6 dimensiones: `traffic(0.25)` + `engagement(0.20)` + `revenue(0.20)` + `quality(0.15)` + `trend(0.10)` + `core_web_vitals(0.10)`

---

## FILAMENT WIDGETS DEL DASHBOARD

**AgentRunsOverviewWidget** (sort: 1) — StatsOverview
- Agentes ejecutando ahora · Completados hoy · Fallidos hoy · En cola

**PendingApprovalsWidget** (sort: 2, full width) — TableWidget
- Cola N3 con botones inline Aprobar/Denegar
- Registra `decided_by` + `decided_at` en cada decisión

**NicheScoresWidget** (sort: 3, full width) — TableWidget
- Portafolio de activos activos con Score Compuesto desde Redis
- Badge coloreado por clasificación (verde/azul/amarillo/rojo)

---

## VARIABLES DE ENTORNO — `.env.example` COMPLETO

```env
APP_NAME="C2 MDC21"
APP_URL=http://c2-mdc21.test
APP_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1 / DB_PORT=5432 / DB_DATABASE=c2

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=reverb
REDIS_CLIENT=predis

REVERB_APP_ID / REVERB_APP_KEY / REVERB_APP_SECRET / REVERB_HOST / REVERB_PORT / REVERB_SCHEME

CLAUDE_API_KEY / CLAUDE_MODEL_ORCHESTRATOR=claude-sonnet-4-5 / CLAUDE_MODEL_POLICY=claude-haiku-4-5
OPENAI_API_KEY / OPENAI_MODEL_RESEARCH=gpt-4o / OPENAI_MODEL_CONTENT=gpt-4o-mini

TELEGRAM_BOT_TOKEN / TELEGRAM_GROUP_INFRA / TELEGRAM_GROUP_CONTENT / TELEGRAM_GROUP_NEGOCIO
RESEND_API_KEY

AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_DEFAULT_REGION=eu-west-1 / AWS_BUCKET
CLOUDFLARE_API_TOKEN / GITHUB_TOKEN
GOOGLE_SEARCH_CONSOLE_KEY / SEMRUSH_API_KEY
SENTRY_LARAVEL_DSN
```

---

## REGLAS DE GOBERNANZA — NUNCA SE ROMPEN

| Nivel | Tipo | Ejemplos |
|---|---|---|
| **N1** | Automático | análisis, propuestas internas, borradores, tests, scoring de leads |
| **N2** | Semiautomático | publicar en blog propio, staging, drip emails, crear desde template |
| **N3** | Humano siempre | redes sociales, contacto externo, DNS, producción, kill/pivot |

1. Policy & Brand se ejecuta **antes** de cualquier contenido que salga al exterior
2. Posts en redes y newsletters: agente prepara, humano copia y publica manualmente
3. La plataforma **nunca** ve una API ni un bot publicando — ve una persona real
4. Deploy a producción: siempre aprobación humana con QA report completo
5. DNS: nunca lo ejecuta un agente de forma autónoma
6. Kill o pivot de activo: aprobación de los 4 socios
7. Toda acción de agente queda registrada en `agent_runs`
8. Todo override humano queda en audit trail (Spatie Activity Log) con nombre del aprobador
9. API keys en AWS Secrets Manager en producción, nunca en `.env` de producción
10. Rate limiting activo en **todas** las llamadas a APIs de IA (Claude + OpenAI)

---

## RUTAS INERTIA — FRONTEND VUE

| Método | Ruta | Controller | Página |
|---|---|---|---|
| GET | `/` | DashboardController | Dashboard.vue |
| GET | `/agent-runs` | AgentRunController@index | AgentRuns/Index.vue |
| GET | `/approvals` | ApprovalController@index | Approvals/Index.vue |
| POST | `/approvals/{id}/approve` | ApprovalController@approve | — |
| POST | `/approvals/{id}/deny` | ApprovalController@deny | — |
| GET | `/assets` | NicheConfigController@index | NicheConfigs/Index.vue |
| GET | `/content` | ContentController@index | Content/Index.vue |
| GET | `/leads` | LeadController@index | Leads/Index.vue (oculto) |

Todas las rutas requieren `auth` middleware. Guests redirigen a `/admin/login` (Filament).

### Middleware Inertia
- `HandleInertiaRequests` — comparte `auth.user` + `flash.success/error` a todas las páginas
- Root view: `resources/views/app.blade.php`

### Sidebar navegación (AppLayout.vue)
Dashboard · Agentes · Aprobaciones · Activos · Contenido
*(Leads comentado hasta implementación futura)*

---

## CONVENCIONES DEL PROYECTO

| Área | Convención |
|---|---|
| PHP | PSR-12, Laravel Pint formatea automático |
| TypeScript | strict mode, interfaces para todos los tipos en `types/index.d.ts` |
| Tests | Pest PHP, un test por funcionalidad core |
| Commits | `MDC21-XX: descripción en imperativo` |
| Ramas | `main` (producción) · `develop` (integración) · `feature/nombre` |
| Modelos | UUID como PK siempre, nunca autoincrement (`HasUuids`) |
| JSONB | Para outputs de agentes, configs de nichos y métricas |
| Jobs | Todo agente corre como Job en cola (`agents` o `agents-ops`), nunca síncrono |
| Secretos | AWS Secrets Manager en producción, `.env` solo en local |
| Rate limit | Siempre configurado antes de llamar a Claude o OpenAI |
| i18n | Siempre añadir clave en `es/app.json` y `en/app.json` |

---

## ESTADO ACTUAL DEL PROYECTO

### ✅ COMPLETADO — Cimientos (MDC21-01)
- [x] Repositorio GitHub con ramas `main` y `develop`
- [x] Entorno local: DBngin PostgreSQL 16.4 + Redis via Homebrew + PHP 8.5.3
- [x] Laravel 12 instalado con PostgreSQL + Redis (predis)
- [x] Filament 3.3 instalado (panel en `/admin`, color índigo)
- [x] Pest PHP 3.8 + Laravel Pint 1.29 + Telescope (solo dev)
- [x] GitHub Actions CI/CD (`ci.yml`: Pint → Pest → deploy staging en `develop`)
- [x] Laravel Horizon 5.45 instalado y configurado
- [x] Laravel Reverb 1.8 instalado, broadcasting configurado
- [x] Laravel Pennant 1.21 instalado, tabla `features` migrada
- [x] Laravel Pulse 1.7 instalado, tablas migradas
- [x] Spatie Activity Log 4.12 instalado, tablas migradas
- [x] Spatie Laravel PDF 2.4 instalado
- [x] 19 migraciones ejecutadas en PostgreSQL local
- [x] 7 modelos Eloquent con `HasUuids`, casts JSONB (AgentRun, Approval, Artifact, NicheConfig, Policy, PromptVersion, User)
- [x] 3 recursos Filament (Activos, Políticas, Prompts)
- [x] `app/Services/AI/ClaudeService.php` — message + batch + Prompt Caching
- [x] `app/Services/AI/ChatGPTService.php` — message + batch + polling
- [x] `app/Services/AI/RateLimiterService.php` — rate limiting por provider
- [x] `app/Services/PromptRegistry.php` — prompts activos desde DB con cache
- [x] `app/Services/ScoreComposite.php` — score 0-100 con 6 dimensiones
- [x] `app/Jobs/Agents/BaseAgentJob.php` — registro automático en `agent_runs`
- [x] 8 Jobs de agentes implementados + WebBuilder (10 total, 2 desactivados)
- [x] `config/services.php` — Claude, OpenAI, Telegram, AdSense, Cloudflare, GitHub
- [x] `AppServiceProvider` con singletons de todos los services

### ✅ COMPLETADO — Panel de Control (MDC21-02 a MDC21-09)
- [x] Inertia.js v3 + HandleInertiaRequests middleware + auth redirect a Filament login
- [x] AppLayout con sidebar (Dashboard, Agentes, Aprobaciones, Webs), topbar, responsive
- [x] Font Inter, light theme profesional (paleta zinc)
- [x] **Dashboard** — métricas de webs (live/building/staging/failed), pipeline visual, 6 agentes core, gráfica ECharts, aprobaciones, timeline
- [x] **Agentes** — perfiles por capa, métricas, filtros, errores, historial paginado
- [x] **Aprobaciones** — urgencia, gobernanza N1/N2/N3, audit trail, filtros
- [x] **Webs** — portafolio con build_status, detalle expandible
- [x] 7 componentes Vue (StatsCard, ApprovalRow, AgentStatusGrid, AgentProfileCard, ActivityTimeline, AgentActivityChart, ScoreGaugeChart)
- [x] 4 Controllers Inertia (Dashboard, AgentRun, Approval, NicheConfig)
- [x] WebSockets: Laravel Echo + Reverb, 3 Events broadcast, composable useRealtimeDashboard
- [x] i18n es/en, Filament limpio (solo Activos, Políticas, Prompts)

### ✅ COMPLETADO — Agentes (MDC21-04/05/11)
Pipeline de creación de webs (8 agentes activos):
- [x] **OrchestratorAgentJob** — cada 12h, detecta activos nuevos, calcula scores, planifica
- [x] **WebBuilderAgentJob** — genera site.config.json → copia template → npm build → Nginx
- [x] **PolicyBrandAgentJob** — Claude Haiku + fallback rule-based, valida contenido
- [x] **QAExperimentationAgentJob** — Pest + Pint + performance check
- [x] **BuildReleaseAgentJob** — staging auto / producción N3
- [x] **InfraReliabilityAgentJob** — DB, Redis, disco, uptime webs, cada hora
- [x] **SeoContentAgentJob** — genera artículos SEO (post-lanzamiento)
- [x] **DistributionAgentJob** — prepara posts redes, siempre N3 (post-lanzamiento)
- [x] Agentes Engagement y Monetization existen pero desactivados (futuro)
- [x] Scheduler: Orchestrator 2x/día + InfraReliability hourly + quick 15min
- [x] BaseAgentJob con broadcast automático (WebSockets)

### ✅ COMPLETADO — Fábrica de Webs (MDC21-11/12)
- [x] Template Vue base (`stubs/web-template/`) — app standalone Vue 3 + Vite 7 + Tailwind 4
- [x] site.config.json como motor: diseño, páginas, herramientas, contenido, SEO, ads, blog
- [x] `WebConfigTemplateService` — genera configs completos por vertical sin API key
- [x] `NginxConfigService` — auto-config dominio + SSL con certbot
- [x] `AssetSetupService` — auto-crea políticas + prompts + dispara WebBuilder
- [x] Formulario Filament: contexto rico, build_status badge, botón Reconstruir
- [x] Migración: build_status (pending/building/staging/live/failed) + build_metadata

Template web incluye:
- [x] DynamicPage — renderiza cualquier página desde config.pages[].sections[]
- [x] Componentes: Hero, Features, FAQ, CTA, Content, Header, Footer, CookieConsent
- [x] CalculatorTool con 4 fórmulas (hipoteca francesa, ahorro energía, TAE, ROI solar)
- [x] LeadFormTool (preparado para futuro)
- [x] Blog: BlogListPage + BlogArticlePage con fuentes y disclaimer
- [x] AdSense auto ads (useAdsense.ts, inyecta desde config.ads.adsense_id)
- [x] SEO: useSeo.ts (meta tags, OG, canonical, Schema JSON-LD: WebSite, FAQPage, SoftwareApplication)
- [x] sitemap.xml + robots.txt generados automáticamente en el build
- [x] Cookie consent RGPD
- [x] 3 variantes de estilo: modern_clean, bold_gradient, corporate
- [x] Router dinámico + tema dinámico via CSS custom properties

### ✅ COMPLETADO — Tests + CI (MDC21-03/07)
- [x] 44 tests Pest, 195 assertions
- [x] Tests: rutas, dashboard, agentes, aprobaciones, InfraReliability, PolicyBrand, ScoreComposite, BaseAgentJob, AssetSetup, WebBuilder
- [x] phpunit.xml con PostgreSQL (c2_testing), withoutVite() para CI
- [x] GitHub Actions CI verde (Pint → Pest)

### ✅ COMPLETADO — Services + Infra code
- [x] `ClaudeService` — message + batch + Prompt Caching
- [x] `ChatGPTService` — message + batch + polling
- [x] `RateLimiterService` — rate limiting por provider
- [x] `PromptRegistry` — prompts activos desde DB con cache Redis
- [x] `ScoreComposite` — score 0-100 con 6 dimensiones ponderadas
- [x] `TelegramService` — listo para token (send, infraAlert, contentNotify, approvalNeeded)
- [x] `WebConfigTemplateService` — genera configs por 7 verticales (Hipotecas, Energía, Seguros, Préstamos, Solar, Ciberseguridad, Contabilidad)
- [x] `NginxConfigService` — deploy + SSL + rollback
- [x] Migración fix: activity_log causer_id/subject_id bigint → uuid
- [x] Nginx stub en `stubs/nginx/site.conf.stub`

### ✅ COMPLETADO — Dark Theme + Auth + Sincronización (MDC21-16/17)
- [x] Login propio en C2 (`/login`) — dark, no redirect a Filament
- [x] Dark theme completo en todo el frontend Inertia
- [x] Navegación C2 ↔ Filament (sidebar → Config, Filament → Panel de Control)
- [x] Filament dark mode forzado
- [x] Logout en sidebar de C2
- [x] Orchestrator detecta activos pending y dispara WebBuilder automáticamente
- [x] types/index.d.ts sincronizado (sin modelos muertos, con build_status, web_builder)
- [x] AgentRunController: 8 agentes con layers/models/queues correctos
- [x] PromptVersionResource: 8 agentes en dropdown/filtros/badges
- [x] Filament: campo estilo visual (modern_clean/bold_gradient/corporate)
- [x] 52 tests, 210 assertions, CI verde

### ✅ COMPLETADO — Feedback Loop + Deploy (MDC21-18 a 22)
- [x] Sites path local: `storage/app/sites/{domain}/` en dev, `/var/www/{domain}/` en producción
- [x] Nginx skip en local (isAvailable() = false cuando APP_ENV != production)
- [x] Horizon config: colas agents + agents-ops
- [x] Pipeline testeado end-to-end en local: WebBuilder → PolicyBrand → QA → BuildRelease
- [x] Aprobar deploy: local → live directo, producción → Nginx + SSL + health check
- [x] Health check en producción: HTTP GET + verifica body > 100 bytes
- [x] Si health check falla → build_status = failed + N3 con detalles
- [x] **Feedback loop**: al denegar un deploy, feedback obligatorio se guarda y re-dispara WebBuilder
- [x] WebBuilder incluye feedback del socio en el prompt de Claude para regenerar
- [x] Link "Ver preview" en aprobaciones de deploy
- [x] El pipeline se repite hasta que el socio apruebe

### ⬜ PENDIENTE — Solo necesita infra + credenciales
- [ ] Servidor AWS (EC2) con Nginx + PostgreSQL + Redis + Laravel Forge
- [ ] API keys: `CLAUDE_API_KEY` + `OPENAI_API_KEY`
- [ ] `ADSENSE_CLIENT_ID` para monetización con ads
- [ ] `TELEGRAM_BOT_TOKEN` + IDs de 3 grupos
- [ ] `php artisan horizon` + `php artisan reverb:start` en producción
- [ ] Primer dominio real → DNS → crear activo en Filament → deploy

### ⬜ PENDIENTE — Código (próximas sesiones)
- [ ] Analytics (Umami self-hosted)
- [ ] Playwright + Lighthouse CI reales en QA agent
- [ ] Conectar Forge para deploys reales en BuildRelease agent

---

## CÓMO USAR ESTE ARCHIVO

1. Pega este archivo completo al inicio de cualquier chat nuevo de IA
2. Pide a la IA que confirme que lo ha leído y entendido
3. Dile exactamente qué quieres construir a continuación
4. La IA sabrá qué existe, qué convenciones seguir y qué no tocar

Actualiza **ESTADO ACTUAL** tras cada sesión de trabajo.
