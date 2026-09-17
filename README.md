# 💎 Abi Store — Automated Digital Goods & Telegram Bot Commerce Platform

[![SemVer](https://img.shields.io/badge/SemVer-2.1.0-blue.svg)](https://semver.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg)](https://php.net)
[![Database](https://img.shields.io/badge/Database-SQLite%203-003B57.svg)](https://sqlite.org)
[![Telegram Bot](https://img.shields.io/badge/Telegram%20Bot-Webhook%20Ready-2CA5E0.svg)](https://core.telegram.org/bots/api)
[![Design](https://img.shields.io/badge/UI%2FUX-AntiGravity%20v3.2%20Solidism-007AAD.svg)](https://github.com)

**Abi Store** is an enterprise-grade digital product storefront and automated Telegram Bot fulfillment engine built with PHP 8.2+, SQLite, and modern AntiGravity v3 High-Contrast Solidism architecture. It features instant automated delivery, integrated PayPal Commerce gateway, Telegram Webhook/Mini App capabilities, and an autonomous LLM-friendly REST API.

---

## 🌟 Key Features

### 🛍️ Storefront & E-Commerce Engine
- **Instant Automated Delivery**: Licenses, serials, and cryptographic download tokens dispatched immediately upon payment.
- **Adaptive Mini App Mode**: Full Telegram Web App SDK compatibility; auto-expands seamlessly inside Telegram Desktop and Mobile clients.
- **Dual Display Layouts**: Smooth switching between Grid and Compact List views.
- **Zero-Dependency Core**: Extremely fast (10-15ms TTFB), runs effortlessly on shared hosting, LiteSpeed, Nginx, or Docker.

### 🤖 24/7 Telegram Bot Subsystem (`@abi_store_bot`)
- **Global Webhook & Long-Polling**: Supports dual-mode operation—high-throughput production Webhook (<100ms latency) and developer CLI worker (`bot_worker.php`).
- **Interactive Inline Menus**: Dynamic product catalog browsing, instant PayPal checkout links, order tracking, and customer support.
- **Zero Local Dependencies**: Operates autonomously on cloud hosting without requiring persistent local machines or tunnels.

### 🛡️ Radar Control Panel (`/admin`)
- **Real-Time Financial & Sales Telemetry**: Gross revenue, order completion ratios, key pool exhaustion warnings.
- **Products & License Key Manager**: Bulk import/export of cryptographic serials with zero-reuse locking.
- **Transaction Audit Ledger**: Complete PayPal transaction history with capture IDs and payer verification.
- **System Settings Console**: Live webhook toggling, PayPal Live/Sandbox switcher, and bot diagnostics.

### 🧠 Autonomous REST API (v1)
- **Google AIP-158 & OpenAPI 3.1 Compliant**: Built for integration with autonomous LLM agents (Claude, ChatGPT, Gemini, LangChain, n8n).
- **Zero-Trust Security**: Timing-attack safe authentication via `X-API-Key` or `Bearer` tokens.
- **Developer Console (`/admin/api`)**: In-browser API explorer and ready-to-use LLM prompt snippets.

---

## 📂 Architecture & Directory Structure

```text
paypal_gate_digital_product_apps_with_bot/
├── .agents/                # Local agent rules & SemVer manifest
├── app/
│   ├── Controllers/        # Storefront, Admin, API & Webhook handlers
│   ├── Core/               # Router, HttpClient, Session, Security, View
│   ├── Middleware/         # AuthMiddleware & ApiKeyMiddleware
│   └── Services/           # TelegramBot, Delivery, PayPal, Settings
├── config/                 # Global application constants & autoloader
├── database/               # SQLite database & schema migrations
├── docs/                   # API documentation & deployment blueprints
├── public/                 # Web root (index.php, CSS, JS, SVG assets)
├── storage/                # Logs, session files, and digital product uploads
├── views/                  # Storefront & Admin templates
├── bot_worker.php          # CLI long-polling worker
├── CHANGELOG.md            # Verified release notes
├── VERSION                 # Current SemVer version string
└── README.md               # Documentation
```

---

## 🚀 Quick Start & Installation

### 1. Requirements
- PHP 8.2 or PHP 8.3
- `pdo_sqlite`, `curl`, `openssl` extensions
- Apache, LiteSpeed, or Nginx with URL rewriting enabled

### 2. Local Setup
```bash
# Clone repository
git clone https://github.com/al-gifari1/paypal_gate_digital_product_apps_with_bot.git
cd paypal_gate_digital_product_apps_with_bot

# Start local server
php -S 0.0.0.0:8080 -t public
```

### 3. Telegram Webhook Setup
In production, point your bot to the live webhook URL:
```bash
curl -s "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://yourdomain.com/telegram/webhook"
```

---

## 💳 Payment Gateway Roadmap
- **PayPal Business Commerce**: Automated capture, IPN/Webhook fulfillment, and instant delivery.
- **Fride.io Integration**: Modular gateway adapter architecture in progress for alternative checkout routes.

---

## 📄 License
Proprietary & Confidential &copy; 2026 Abi Store. All rights reserved.
