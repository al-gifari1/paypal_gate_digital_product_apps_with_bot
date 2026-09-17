# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-09-17

### Added
- **Enterprise Pluggable Payment Gateway Engine (`app/Core/Payment`)**:
  - Implemented Strategy & Adapter pattern with `PaymentGatewayInterface` contract.
  - Created type-safe DTOs: `ChargeRequest`, `ChargeResponse`, `WebhookResult`.
  - Dynamic provider registry and runtime factory via `GatewayManager`.
  - Zero-Trust cryptographic validation helper `ZeroTrustVerifier` with timing-attack safe comparisons (`hash_equals`) and replay prevention.
- **Fride.io Payment Provider Driver (`app/Gateways/Providers/Fride`)**:
  - Full API integration supporting Crypto (USDT, TRX, BTC), SBP, and Bank Cards.
  - Complete `FrideClient` for invoice creation (`POST /invoice/create`) and status polling (`GET /invoice/getInfo`).
  - Production `WebhookValidator` with exact HMAC-SHA256 signature verification matching official Fride.io algorithms.
  - Full developer technical specification in `docs/FRIDE_IO_API_SPEC.md`.
- **PayPal Provider Driver (`app/Gateways/Providers/Paypal`)**:
  - Refactored PayPal payment capture and webhook handling into a pluggable driver implementing `PaymentGatewayInterface`.
- **Dynamic Storefront Gateway Selector**:
  - Automatic rendering of active and configured payment gateways on `/checkout/{slug}`.
  - Graceful fallback routing when a specific provider is disabled or unsupported.
- **Unified Webhook Dispatcher**:
  - Single pluggable route `/payment/webhook/{gateway}` dynamically delegating validation and fulfillment to the respective driver.
- **Admin Gateway Console (`/admin/settings`)**:
  - Integrated dedicated Fride.io settings tab: enable/disable toggle, Merchant ID, API key, Webhook Secret, and live webhook URL display.

## [2.1.0] - 2026-09-17

### Added
- **Hostinger Cloud Production Deployment**:
  - Full automated deployment of Abi Store to Hostinger LiteSpeed Edge server (`https://buybestbd.com/abi`).
  - Active global Telegram Webhook registered to `https://buybestbd.com/abi/telegram/webhook`.
  - Ultra-fast responses (< 100ms) with zero local worker or VPN dependencies.
- **Subfolder Architecture Engine**:
  - Dynamic base-directory resolution and URI stripping in `Router.php`.
  - Added global `url($path)` and `SettingsService::url($path)` helpers for portable URL generation across storefront and admin views.
  - Automatic base-path prefixing for relative internal redirects in `View::redirect()`.
- **LiteSpeed & Custom Session Engine**:
  - Project-scoped session handler writing to `storage/sessions` with `0775` permissions, overcoming shared-hosting cPanel session path limitations.
- **Hardened Subfolder Security Rules**:
  - High-security Apache/LiteSpeed `.htaccess` rules denying direct HTTP access to `app/`, `config/`, `database/`, `storage/`, `views/`, and `.sqlite` files.
- **Payment Gateway Architecture Notice**:
  - Codebase prepared for modular multi-gateway integration (PayPal + Fride.io checkout).

### Fixed
- Fixed product thumbnail images resolving against root domain by wrapping image paths in `url()` helper.
- Fixed Telegram inline keyboard `web_app` validation error by only appending `web_app` button when running on genuine HTTPS environments.

## [2.0.0] - 2026-09-17

### Added
- **Enterprise System REST API (v1) Subsystem**:
  - Full Google AIP-compliant API (`/api/v1/`) built for autonomous LLM agents (ChatGPT, Claude, Gemini, LangChain, n8n) and external integrations.
  - **Zero-Trust Security & Authentication**:
    - Support for `X-API-Key` and `Authorization: Bearer` token headers.
    - Timing-attack resistant validation using PHP `hash_equals()`.
    - Cryptographically secure 64-character token (`sk_live_...`) with instant revocation and regeneration in Admin Console.
  - **Google AIP-158 Structured Error Envelopes**:
    - Unified JSON format: `{"error": {"code": 4xx/5xx, "message": "...", "status": "STATUS_STRING"}}`.
  - **Core REST Endpoints**:
    - `GET /api/v1/products`: Paginated catalog with sales and stock telemetry.
    - `GET /api/v1/products/{id}`: Detailed single product analytics.
    - `POST /api/v1/products`: AI agent product creator endpoint (supports name, price, currency, description, product_type, image_url, and initial license keys).
    - `PUT /api/v1/products/{id}`: Idempotent product field updates.
    - `DELETE /api/v1/products/{id}`: Product removal and asset cleanup.
    - `GET /api/v1/products/{id}/cards`: Inventory availability checks.
    - `POST /api/v1/products/{id}/cards`: Bulk license key importer.
    - `GET /api/v1/stats`: Real-time financial telemetry (revenue, completed orders, low-stock alerts).
    - `GET /api/v1/orders`: Recent sales ledger with delivery state.
    - `GET /api/v1/openapi.json`: Automated OpenAPI 3.1.0 JSON specification for zero-config Custom GPT Actions.
  - **Admin Developer Console (`/admin/api`)**:
    - New sidebar navigation route under "Automation & Developer".
    - Live Interactive API Request Runner with response viewer and latency counter.
    - Master key visibility toggle and 1-click clipboard copy.
    - Pre-configured LLM prompt recipes and cURL snippets library.
  - **Router & HTTP Verb Upgrades**:
    - Added native `PUT` and `DELETE` route registration in `app/Core/Router.php`.
    - Added HTTP Method spoofing support via `X-HTTP-Method-Override` and `_method`.

## [1.6.1] - 2026-09-17

### Fixed
- **Currency Dropdown & Price Group Architecture (`/admin/products/create`)**:
  - Removed cramped native currency selector from the price input field.
  - Provided a dedicated, first-class `currency` dropdown (`select.form-control`) alongside `price` in `.settings-form-grid`.
  - Integrated into the Anti-Gravity V3 custom tactile dropdown system with 180° rotating chevron, solid dark background (`#1e293b`), selected checkmark, and full mobile touch optimization.
  - Linked currency changes directly to the Real-Time Live Preview card (`$XX.XX USD/EUR/GBP`).
  - Reorganized Cover Media section into a clean side-by-side grid of Local Upload Dropzone and Direct Image URL.

## [1.6.0] - 2026-09-17

### Added
- **Anti-Gravity V3 Product Creator Redesign (`/admin/products/create`)**:
  - Replaced cramped single-panel form with a dual-column interactive creator hub:
    - **Interactive Delivery Pipeline Choice Cards**: Added tactical 2-option choice cards (`Downloadable Asset` vs `License / Serial Pool`) with active cyan glow and automatic field swapping.
    - **Real-Time Live Storefront Preview**: Interactive card mockup mirroring storefront catalog appearance that updates live as the admin types the product title, price, description, and uploads cover images.
    - **Cover Media Management**: Dual support for local file upload (drag & drop zone) or direct image URL.
    - **Real-Time License Key Counter**: Dynamically tallies and formats key inventory (`X Keys Ready`) as items are pasted into the inventory pool.
    - **File Upload Protection Badges**: Visual confirmation badge displaying uploaded file name, size, and zero-trust storage indicators.
    - **Elevated Sticky Action Footer**: Fast access to cancel or publish CTA on any screen position.
    - **Mobile Responsive Layout**: Auto-adapts to 1 column on mobile/tablets with touch targets `>= 44px`.

## [1.5.1] - 2026-09-17

### Added
- **Theme-Aligned Tactical Scrollbars (Admin & Storefront)**:
  - Custom themed horizontal and vertical scrollbars across both Admin Panel (`radar_admin.css`) and Public Storefront (`storefront.css`).
  - Styled track with dark solid theme background (`#0f172a` / `#0b1120`).
  - Styled thumb with brand accent blue (`#0284c7`) and active/hover glow cyan (`#38bdf8`).
  - Supports both standard CSS (`scrollbar-color`, `scrollbar-width: thin`) for Firefox and WebKit pseudoelements (`::-webkit-scrollbar`, `::-webkit-scrollbar-thumb`, `::-webkit-scrollbar-track`) for Chromium, Safari, Edge, and Telegram WebApp.
  - Dedicated enhanced contrast on data table horizontal wrappers (`.data-table-wrapper`).

## [1.5.0] - 2026-09-17

### Added
- **Anti-Gravity V3 Settings Hub Redesign (`/admin/settings`)**:
  - Replaced cluttered monolithic form and inline grid layout with a modular, tabbed dashboard architecture.
  - **System Readiness KPI Matrix**: Added real-time status summary cards at top displaying Storefront Identity & Currency, PayPal Gateway Status (Live/Sandbox with credential check), and Telegram Bot status.
  - **Tabbed Navigation**:
    - `General & Branding`: Storefront title, currency ISO code, public application URL with 1-click copy.
    - `PayPal Gateway`: Environment switcher (Sandbox/Live), Client ID, Client Secret with show/hide password toggle, Webhook ID, and auto-generated copyable Webhook endpoint URL.
    - `Telegram Bot Engine`: Bot API token with show/hide toggle, Admin Chat ID for sale notifications.
    - `Diagnostics & CLI`: Independent diagnostic suite with bot connectivity ping test, Telegram webhook register/delete actions, and local CLI polling worker command with 1-click copy.
  - **Interactive UX Features**: Password visibility toggle buttons (`.password-toggle-btn`), 1-click clipboard copy buttons with feedback (`.btn-copy-code`), and elevated sticky save action footer.
  - **Mobile Responsive Layout**: Touch-friendly tab scrolling (`-webkit-overflow-scrolling: touch`), auto-adapting single-column layout on mobile, touch targets `>= 44px`, and zero horizontal overflow.

## [1.4.1] - 2026-09-17

### Fixed
- Enhanced select and dropdown elements (`select.form-control`, `.table-filter-select`) for mobile screens:
  - Added custom SVG chevron arrow with high-contrast slate stroke.
  - Enforced `font-size: 16px !important` and `min-height: 46px` on mobile viewports (`<= 960px`) to eliminate iOS/Safari intrusive auto-zooming.
  - Set `width: 100%`, `max-width: 100%`, and comfortable touch padding for both form selects and table filter dropdowns.
  - Styled `<option>` items with solid dark theme surfaces (`--surface-bg`).

## [1.4.0] - 2026-09-17

### Added
- **Admin Mobile Header Architecture**:
  - Tactical hamburger drawer toggle button (`#mobileMenuToggle`) on mobile screens.
  - Brand & Dynamic Module Breadcrumb (`NEXUS > Mission Control`, `Products Manager`, etc.) with responsive text truncation.
  - Compact status pill cluster: Mini PayPal Live/Sandbox indicator (`• LIVE` / `• SBOX`), Telegram bot online status icon, and Live Storefront shortcut button.
  - Compact icon-mode for user shield and logout buttons on narrow viewports.
- **Mobile Slide Drawer (`#radarSidebar`)**:
  - Full-screen slide-in navigation drawer with dedicated close button `[X]` inside brand header.
  - High-contrast dark backdrop overlay (`#radarBackdrop`) with click-to-dismiss and keyboard `Escape` trap.
  - Automatic drawer dismissal upon clicking any navigation link on mobile.
- **Admin Telegram Mini App Mobile Bottom Dock (`.radar-mobile-bottom-nav`)**:
  - Fixed bottom navigation bar with 5 thumb-friendly actions: `Radar`, `Products`, `Orders`, `Cards`, and `More` (which triggers the full drawer).
  - Safe area inset support (`env(safe-area-inset-bottom)`).
  - Telegram WebApp SDK initialization (`expand()`, dark theme header color `#0f172a`).

### Fixed
- Fixed admin layout horizontal blowout (`scrollWidth === clientWidth`) by enforcing `minmax(0, 1fr)` on `.dashboard-grid` and `.kpi-row`.
- Contained `.data-table-wrapper` with smooth horizontal touch-scrolling and `min-width: 0`.

## [1.3.3] - 2026-09-17

### Fixed
- Fixed **Send Inquiry** submit button CSS styling: introduced dedicated `.form-submit-btn` class with proper elevation, tactile feedback, and dimensions.
- Corrected desktop stretch defect where the button previously expanded across the entire form with left-aligned text; button now enforces architectural `align-self: flex-start` on desktop and full-width centered alignment on mobile viewports.
- Enhanced Telegram launch button with `.channel-action-btn`.

## [1.3.2] - 2026-09-17

### Fixed
- Fixed `/contact` page mobile layout: replaced desktop 2-column inline grid (`1.5fr 1fr`) with responsive `.contact-layout-grid` and `.contact-form-col`/`.contact-sidebar-col`.
- Standardized `.subpage-header` across all subpages (`/contact`, `/about`, `/privacy`, `/terms`, `/refund`) to eliminate header text squashing on mobile.
- Enforced full-width inputs, textareas, and tactile `Send Inquiry` submit button for mobile viewports.
- Enhanced Support Channels card with direct 24/7 Telegram Bot launch button and solid Anti-Gravity UI v3 complaint cards.

## [1.3.1] - 2026-09-17

### Fixed
- Fixed header layout break on mobile screens (<= 768px): separated desktop navigation into `.desktop-nav` and dedicated compact `.mobile-header-bot-btn.mobile-only`.
- Completely eradicated horizontal scroll blowout across all viewport widths without using `overflow-x: hidden` on `body` (strictly adhering to Anti-Gravity UI v3 True Responsiveness rule).
- Corrected FontAwesome free icon for mobile bottom dock Warranty link (`fa-shield-halved`).
- Verified zero horizontal overflow (`scrollWidth === clientWidth`) in Chrome DevTools mobile emulation.

## [1.3.0] - 2026-09-17

### Added
- Native Telegram Mini App Mobile Bottom Navigation Dock (`Shop`, `About`, `Help`, `Warranty`, `Bot`).
- Telegram WebApp integration: dynamic `BackButton` handling for subpages, safe area insets (`env(safe-area-inset-bottom)`), and user ID propagation.
- Mobile-first responsive optimizations for List Mode (compact 85px thumbnail horizontal cards).
- 16px font-size constraints on mobile form inputs to prevent WebKit/Safari auto-zoom.

### Changed
- Cleaned and streamlined typography and hero text for crisp mobile readability.
- Re-architected top header for small screens, hiding redundant text buttons and maintaining distraction-free header.

## [1.2.0] - 2026-09-17

### Added
- Brand Identity established: **NEXUS VAULT** (*Enterprise Digital Asset & Automated License Gateway*).
- Official Brand & Compliance Subpages:
  - `/about`: Company philosophy, architecture, and security standards.
  - `/contact`: Direct Help Desk inquiry form with automated SQLite logging and Telegram Admin notification.
  - `/privacy`: PayPal Merchant-compliant Data & Privacy policy.
  - `/terms`: Terms of Service & Software License Agreement.
  - `/refund`: 14-Day Buyer Guarantee and Replacement/Refund policy.
- Navigation and footer links for all subpages in storefront layout.

### Changed
- Corrected and balanced layout spacing gap between Hero Section and Available Catalog using `.catalog-section`.

## [1.1.0] - 2026-09-17

### Added
- Product Cover Image support in database schema, admin upload form, and storefront catalog.
- Dual View Mode switcher on Storefront: **Grid Mode** and **List Mode** with instant client-side toggle and persistent `localStorage` preference.
- Rich product thumbnails in Admin Products ledger.
- Dedicated hero cover image presentation on Product Details view.

### Changed
- Removed bolt icon from Hero banner per user instruction.
- Perfected layout spacing, padding, margins, and component gaps across all screen sizes.

## [1.0.0] - 2026-09-17

### Added
- Modular Raw PHP 8.5+ architecture with zero third-party dependencies.
- SQLite database layer with WAL mode and foreign key enforcement.
- Product showcase storefront (Telegram Mini App friendly & mobile-first).
- Product details page and PayPal Checkout integration (Orders v2 API).
- Thank you page with secure expiring signed download token generator.
- Interactive Telegram Bot engine supporting commands: `/start`, `/shop`, `/order`, `/deposit`, `/payment`.
- Telegram Bot CLI polling worker (`bot_worker.php`) and webhook listener endpoint.
- Mission-control Admin Panel inspired by `Telegram/ninja_otp/webapp/radar/index.html` with Anti-Gravity v3 UI standards.
- Admin modules: Realtime Dashboard, Products Manager, Transactions Ledger, Cards/License Key Manager, Order Manager, and System Settings.
- Dynamic system settings for PayPal credentials (Live/Sandbox) and Telegram Bot token/chat configuration.
