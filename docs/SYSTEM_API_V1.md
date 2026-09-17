# System REST API (v1) Specification & AI Agent Guide

Welcome to the **Digital Vault & PayPal Platform REST API (v1)**. This API is built strictly adhering to **Google API Design Standards (AIP-121, AIP-131, AIP-132, AIP-158, AIP-180)** and is designed for autonomous **LLM Agents (ChatGPT, Claude, Gemini)**, **Custom GPT Actions**, **n8n workflows**, and automated inventory scripts.

---

## 🔐 1. Authentication (Zero-Trust Model)

All API endpoints require authentication via your **Master API Secret Key** (`sk_live_...`).

You can pass the API key using either of the following HTTP headers:

### Primary Header:
```http
X-API-Key: YOUR_API_SECRET_KEY
```

### Alternative Standard Header:
```http
Authorization: Bearer YOUR_API_SECRET_KEY
```

> [!TIP]
> You can retrieve, inspect, test, and regenerate your Master API Key inside the Admin Console at `/admin/api`.

---

## 🌐 2. Base Endpoint & OpenAPI Spec

- **Base URL:** `http://localhost:8080/api/v1` (or your production public HTTPS domain)
- **OpenAPI 3.1.0 Specification:** `GET /api/v1/openapi.json` (Publicly accessible for Custom GPT Actions & Swagger import)

---

## 📦 3. Products Endpoints

### 3.1 List Products
`GET /api/v1/products`

#### Query Parameters:
| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | `1` | Page number |
| `page_size`| integer | `20` | Items per page (max 100) |
| `type` | string | `null` | Filter by `downloadable_file` or `card_license` |
| `is_active`| integer | `null` | Filter by `1` (active) or `0` (hidden) |
| `search` | string | `null` | Keyword search in name and description |

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Python Algo Trading Bot",
      "slug": "python-algo-trading-bot",
      "description": "High performance automated trading engine.",
      "price": 49.99,
      "currency": "USD",
      "product_type": "card_license",
      "image_path": "https://example.com/cover.png",
      "is_active": true,
      "available_cards": 12,
      "sales_count": 5
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "page_size": 20,
    "total_pages": 1
  }
}
```

---

### 3.2 Create Product (AI / LLM Action)
`POST /api/v1/products`

#### Request Headers:
```http
Content-Type: application/json
X-API-Key: sk_live_...
```

#### Request Payload:
```json
{
  "name": "VIP Signals Telegram Bot License",
  "price": 29.99,
  "currency": "USD",
  "description": "Instant 30-day VIP trading channel license key.",
  "product_type": "card_license",
  "image_url": "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=600",
  "is_active": true,
  "initial_keys": [
    "VIP-KEY-9999-AAAA",
    "VIP-KEY-9999-BBBB",
    "VIP-KEY-9999-CCCC"
  ]
}
```

#### Success Response (`201 CREATED`):
```json
{
  "status": "success",
  "data": {
    "id": 2,
    "name": "VIP Signals Telegram Bot License",
    "slug": "vip-signals-telegram-bot-license",
    "price": 29.99,
    "currency": "USD",
    "product_type": "card_license",
    "image_path": "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=600",
    "is_active": true,
    "available_cards": 3,
    "message": "Product created successfully."
  }
}
```

---

### 3.3 Get Single Product
`GET /api/v1/products/{id}`

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "id": 2,
    "name": "VIP Signals Telegram Bot License",
    "slug": "vip-signals-telegram-bot-license",
    "price": 29.99,
    "currency": "USD",
    "product_type": "card_license",
    "available_cards": 3,
    "assigned_cards": 0,
    "total_sales": 0,
    "total_revenue": 0.0
  }
}
```

---

### 3.4 Update Product
`PUT /api/v1/products/{id}`

#### Request Payload:
```json
{
  "price": 24.99,
  "description": "Updated limited-time promotional pricing!"
}
```

---

### 3.5 Delete Product
`DELETE /api/v1/products/{id}`

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "deleted": true,
    "id": 2,
    "message": "Product #2 was successfully removed."
  }
}
```

---

## 🔑 4. Digital Cards & License Inventory Endpoints

### 4.1 Check Card Inventory Status
`GET /api/v1/products/{id}/cards`  
*(Optional: `?include_available=1` to list available keys)*

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "product_id": 2,
    "product_name": "VIP Signals Telegram Bot License",
    "total_cards": 3,
    "available_cards": 3,
    "assigned_cards": 0
  }
}
```

---

### 4.2 Bulk Append Keys to Product Pool (AI Action)
`POST /api/v1/products/{id}/cards`

#### Request Payload:
```json
{
  "keys": [
    "VIP-KEY-8888-1111",
    "VIP-KEY-8888-2222",
    "VIP-KEY-8888-3333"
  ]
}
```

#### Success Response (`201 CREATED`):
```json
{
  "status": "success",
  "data": {
    "product_id": 2,
    "inserted_count": 3,
    "available_cards_now": 6,
    "message": "Successfully imported 3 keys into inventory pool."
  }
}
```

---

## 📊 5. Business Telemetry & Orders

### 5.1 Storefront Telemetry
`GET /api/v1/stats`

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "currency": "USD",
    "revenue": {
      "gross_revenue": 149.95,
      "completed_orders": 5,
      "pending_orders": 1,
      "total_orders": 6
    },
    "products": {
      "total": 3,
      "active": 3,
      "license_products": 2,
      "downloadable_products": 1
    },
    "inventory_pool": {
      "total_keys": 25,
      "available_keys": 20,
      "assigned_keys": 5
    },
    "alerts": {
      "low_stock_products_count": 1,
      "low_stock_products": [
        {
          "id": 1,
          "name": "Pro Serial Pack",
          "available_count": 2
        }
      ]
    }
  }
}
```

---

### 5.2 List Recent Customer Orders
`GET /api/v1/orders?page=1&page_size=20&status=completed`

---

## 🤖 6. LLM Agent Integration Guide

### Step 1: Connect to Custom GPT
1. In ChatGPT, navigate to **Explore GPTs > Create**.
2. Go to **Configure > Actions > Create new action**.
3. Under **Schema**, choose **Import from URL** and enter:
   `https://your-domain.com/api/v1/openapi.json`
4. Under **Authentication**:
   - Type: **API Key**
   - Auth Type: **Custom**
   - Header Name: `X-API-Key`
   - Value: Paste your Master API key.

### Step 2: System Prompt Recipe
```markdown
You are the autonomous e-commerce store manager for our digital products platform.
- When instructed to list or create products, use the 'createProduct' tool.
- Write compelling, high-converting product descriptions.
- For license items, format keys into the 'initial_keys' array.
- Always check 'getStoreStats' periodically to detect low-stock inventory.
```
