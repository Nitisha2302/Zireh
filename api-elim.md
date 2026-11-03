# Elim API (`Elimapi`) — Complete Reference & Project Implementation Guide

> **Official Sources**:
> - Documentation Portal: https://elim.asia/en/docs/
> - Step-by-Step Guides: https://elim.asia/en/guides/
> - OpenAPI / Swagger Reference: https://openapi.elim.asia/api (`https://openapi.elim.asia/api-json`)
> - Account & API Key Dashboard: https://in.elim.asia

---

## Table of Contents

1. [Base Configuration, Headers & Rate Limits](#1-base-configuration-headers--rate-limits)
2. [Authentication & Account API (`/v1/auth/*`, `/v1/me/*`)](#2-authentication--account-api-v1auth-v1me)
3. [Utility API (`/v1/utils/*`)](#3-utility-api-v1utils)
4. [Product API v2 (`/v2/products/*`) — Current & Recommended](#4-product-api-v2-v2products--current--recommended)
5. [Product API v1 (`/v1/products/*`) — Legacy Reference & v1→v2 Migration](#5-product-api-v1-v1products--legacy-reference--v1v2-migration)
6. [Order API (`/v1/orders/*`)](#6-order-api-v1orders)
7. [Purchasing Wallet & Order Payment API (`/v1/purchasing/*`)](#7-purchasing-wallet--order-payment-api-v1purchasing)
8. [Webhooks API (`/v1/webhooks/*`) & HMAC Verification](#8-webhooks-api-v1webhooks--hmac-verification)
9. [Elim CLI Reference](#9-elim-cli-reference)
10. [Global Error Codes & Troubleshooting](#10-global-error-codes--troubleshooting)
11. [Cargo-Admin Project Integration Audit & Implementation Blueprint](#11-cargo-admin-project-integration-audit--implementation-blueprint)

---

## 1. Base Configuration, Headers & Rate Limits

### 1.1 Base URL & Environment
| Environment | Base URL | Notes |
| :--- | :--- | :--- |
| **Production** | `https://openapi.elim.asia` | Single unified environment (no separate sandbox; new accounts include 200 free trial requests). |

### 1.2 Required HTTP Headers
All requests must use **HTTPS** and send:

| Header | Value | Notes |
| :--- | :--- | :--- |
| `Content-Type` | `application/json` | Required for all JSON `POST`/`PATCH` endpoints (`multipart/form-data` for `upload-image`). |
| `Accept` | `application/json` | Expect JSON responses. |
| `Authorization` *or* `x-api-key` | `Bearer <access_token>` *or* `<api_key>` | Every protected endpoint accepts either a JWT Bearer token or an `x-api-key` header. |

### 1.3 Supported Marketplaces (`platform` parameter)
| Marketplace | API `platform` Value | Status | Internal Project Code (`cargo-admin`) |
| :--- | :--- | :--- | :--- |
| **Taobao / Tmall** | `taobao` | Active (Full API support) | `taobao` |
| **1688 (Alibaba China)** | `alibaba` | Active (Full API support) | `1688` (mapped to `alibaba` in `AbstractElimProductService::elimPlatform()`) |

> **Note**: In `POST /v1/products/upload-image` (Legacy v1), `platform` description mentions `taobao` or `1688`, whereas in all v2 Product endpoints and Order endpoints, the enum is strictly `taobao` or `alibaba`. Always send `alibaba` for 1688.

### 1.4 Quotas & Rate Limits
| Limit Type | Value |
| :--- | :--- |
| **Max Request Body Size** | `50 MB` (`upload-image` preprocesses JPEG/PNG/WebP up to `10 MiB` input) |
| **Default Rate Limit (Paid Plans)** | `100 requests / 60 seconds` |
| **Free Plan Rate Limit** | `30 requests / 60 seconds` (Image search is disabled on Free plan) |
| **Product API Endpoints** | Up to `300 requests / 60 seconds` |
| **Order Preview (`POST /v1/orders/preview`)** | `20 requests / 60 seconds` |
| **Order Create / Cancel (`POST /v1/orders`, `POST /v1/orders/:id/cancel`)** | `10 requests / 60 seconds` |
| **Order List / Detail / Stats (`GET /v1/orders*`)** | `30 requests / 60 seconds` |
| **Order Logistic Detail (`GET /v1/orders/:id/logistic-detail`)** | `20 requests / 60 seconds` |
| **Link Unshorten (`GET /v1/utils/unshorten`)** | `20 requests / 60 seconds` (Free call — does not deduct plan quota) |
| **Purchasing Wallet Queries (`GET /v1/purchasing/wallet*`)** | Free calls — do not deduct plan quota |

#### Rate Limit Response Headers
Every response from `https://openapi.elim.asia` includes:
- `X-RateLimit-Limit`: Maximum requests allowed in the current window
- `X-RateLimit-Remaining`: Remaining requests in the current window
- `X-RateLimit-Reset`: Window reset time

---

## 2. Authentication & Account API (`/v1/auth/*`, `/v1/me/*`)

Elim supports two authentication mechanisms:
1. **Access Token (JWT)**: `Authorization: Bearer <access_token>` (obtained via `/v1/auth/login`, valid for **90 days**, refreshable via `/v1/auth/refresh`).
2. **API Key**: `x-api-key: <your_api_key>` (generated in the Elim Portal at https://in.elim.asia; ideal for server-to-server integration without session/token refresh management).

### 2.1 Endpoint Summary
| Method | Endpoint | Auth Required | Summary |
| :--- | :--- | :--- | :--- |
| `POST` | `/v1/auth/login` | No | Authenticate with `email` + `password`; returns `access_token`, `refresh_token`, `expires_in`, `user`. |
| `POST` | `/v1/auth/refresh` | Yes (`Bearer <refresh_token>`) | Issue a new `access_token` and `refresh_token` using the refresh token in the `Authorization` header. |
| `POST` | `/v1/auth/change-password` | Yes (`Bearer` or `x-api-key`) | Change password for the authenticated account. |
| `GET` | `/v1/me/plan-status` | Yes (`Bearer` or `x-api-key`) | Retrieve subscription plan details, monthly request usage, remaining quota, and credit balance. |

---

### 2.2 `POST /v1/auth/login`
**Request Body (`LoginDto`)**:
```json
{
  "email": "user@example.com",
  "password": "your_password"
}
```

**Response `200 OK` (`AccessTokenResponse`)**:
```json
{
  "user": {},
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 1755747599721,
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```
- `expires_in`: Token expiration timestamp in **milliseconds** since Unix epoch (tokens are valid for 90 days).

---

### 2.3 `POST /v1/auth/refresh`
**Headers**:
```http
Authorization: Bearer <refresh_token>
Accept: application/json
```
**Response `200 OK` (`AccessTokenResponse`)**: Same structure as `/v1/auth/login`.

---

### 2.4 `POST /v1/auth/change-password`
**Request Body (`ChangePasswordDto`)**:
```json
{
  "oldPassword": "current_password",
  "newPassword": "new_password_min_8_chars",
  "confirmPassword": "new_password_min_8_chars"
}
```
**Response `200 OK`**: Password changed successfully.

---

### 2.5 `GET /v1/me/plan-status`
Returns active subscription status, request quota usage, overage pricing, and credit balance.
- **Deduction order**: Every successful billable request deducts from `remaining_quota` on the active subscription plan. Once `remaining_quota` reaches `0`, requests automatically deduct from `credit_balance`. If both are exhausted, the API returns `400 Bad Request`.

**Response `200 OK` (`PlanStatusResponseDto`)**:
```json
{
  "subscription": {
    "plan": {
      "name": "Pro",
      "monthly_price": 200000,
      "included_requests": 10000,
      "overage_unit_price": 0.001
    },
    "status": "active",
    "current_period_start": "2026-06-01T00:00:00.000Z",
    "current_period_end": "2026-07-01T00:00:00.000Z"
  },
  "usage": {
    "period_start": "2026-06-01T00:00:00.000Z",
    "period_end": "2026-07-01T00:00:00.000Z",
    "total_requests": 5000,
    "overage_requests": 200,
    "included_limit": 10000,
    "usage_percentage": 52,
    "remaining_quota": 5000
  },
  "credit_balance": 1000,
  "days_remaining": 15
}
```
- **Response `404 Not Found`**: No active subscription or usage summary found.

---

## 3. Utility API (`/v1/utils/*`)

### 3.1 `GET /v1/utils/unshorten`
Expands shortened Taobao/Tmall/1688 share links (such as `https://e.tb.cn/h.hPY04k4RPfAco8F?tk=0CCI430IUqd` or `qr.1688.com`) into their canonical destination URL and extracts the product ID.
- **Cost**: **Free call** (does not deduct quota).
- **Rate Limit**: `20 requests / 60 seconds`.

**Query Parameters**:
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `url` | `string` | Yes | Shortened Taobao or 1688 URL to expand. |

**Example Request**:
```http
GET /v1/utils/unshorten?url=https%3A%2F%2Fe.tb.cn%2Fh.hPY04k4RPfAco8F%3Ftk%3D0CCI430IUqd
Authorization: Bearer <access_token>
```

**Response `200 OK` (`UnshortenResponseDto`)**:
```json
{
  "success": true,
  "final_url": "https://detail.tmall.com/item.htm?id=734467086498",
  "product_id": "734467086498"
}
```
*(If unshortening fails, `success` is `false`, `final_url` and `product_id` are `null`, and `error` contains the message, e.g. `"Invalid URL format"`).*

---

## 4. Product API v2 (`/v2/products/*`) — Current & Recommended

All Product API v2 endpoints use `POST`, require authentication (`Bearer` or `x-api-key`), and return `snake_case` response fields.

### 4.1 Endpoint Summary
| Method | Endpoint | Taobao | 1688 (`alibaba`) | Description |
| :--- | :--- | :---: | :---: | :--- |
| `POST` | `/v2/products/search` | Yes | Yes | Search products by keyword, sort, and platform-specific filters. |
| `POST` | `/v2/products/search-by-image` | Yes | Yes | Search products by Alibaba image URL (`imgUrl`), uploaded `imgId`, or `imageHandle`. |
| `POST` | `/v2/products/upload-image` | Yes | Yes | Upload JPEG/PNG/WebP image (`multipart/form-data`) to obtain `imageId` & `imageHandle`. |
| `POST` | `/v2/products/find` | Yes | Yes | Get product details (price, stock, SKUs, attributes, images). |
| `POST` | `/v2/products/detail` | Yes | Yes | Get full product details (recommended when fuller 1688 data is needed). |
| `POST` | `/v2/products/shipping-fee` | Yes | No | Calculate domestic China shipping fee (`post_fee` in CNY) for a Taobao item. |

---

### 4.2 `POST /v2/products/search` — Keyword Search

**Request Body (`SearchProductsV2Dto`)**:
```json
{
  "platform": "taobao",
  "q": "cotton t-shirt",
  "lang": "ru",
  "sort": "SALE_QTY_DESC",
  "page": 1,
  "size": 20,
  "keywordTranslate": true,
  "filter": {
    "priceRange": {
      "min": 10,
      "max": 200
    },
    "shopId": "12345678"
  }
}
```

#### Request Parameters
| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `platform` | `string` | **Yes** | `"taobao"` or `"alibaba"` |
| `q` | `string` | No | Search keyword |
| `lang` | `string` | No | Title translation language: `"vi"`, `"en"`, `"ru"`, `"ko"`, `"ja"`. Translated title is returned in `title_en`; original Chinese stays in `title`. |
| `sort` | `string` | No | `"PRICE_ASC"`, `"PRICE_DESC"`, `"SALE_QTY_ASC"`, `"SALE_QTY_DESC"` |
| `filter` | `object` | No | `ProductFilterV2Dto` object (see filter table below; note `camelCase` keys in v2) |
| `page` | `number` | No | Page number (`>= 1`, default `1`) |
| `size` | `number` | No | Page size (`20` to `40`, default `20`) |
| `keywordTranslate` | `boolean` | No | Automatically translate `q` from the target `lang` before querying |

#### `filter` Object Properties (`ProductFilterV2Dto`)
| Filter Key | Type | Taobao | 1688 (`alibaba`) | Description / Allowed Values |
| :--- | :--- | :---: | :---: | :--- |
| `filter.priceRange` | `{ min: number, max: number }` | Yes | Yes | Price range in CNY, e.g. `{ "min": 0, "max": 100 }` |
| `filter.shopId` | `string \| number` | Yes | Yes | Same seller filter (Taobao: numeric shop ID; 1688: alphanumeric `shopId`) |
| `filter.sellerLevel` | `string` | No | Yes | `"L1"` (5★), `"L2"` (4.5–5★), `"L3"` (4–4.5★), `"L4"` (<4★) |
| `filter.isFactory` | `boolean` | No | Yes | Only super-factory products |
| `filter.ffmOutbound` | `string` | No | Yes | Fast outbound rate: `"FFMO1"` (24h = 95%), `"FFMO2"`, `"FFMO3"` (24h > 99%), `"FFMO4"` (48h = 95%), `"FFMO5"`, `"FFMO6"` (48h > 99%) |
| `filter.ffmDelivery` | `string` | No | Yes | Fast delivery: `"FFMD0"` (Same day), `"FFMD1"` (Within 24h), `"FFMD2"` (Within 48h) |
| `filter.allowReturn` | `boolean` | No | Yes | 7-day no-reason return |
| `filter.allowDropship` | `boolean` | No | Yes | Supports dropshipping |
| `filter.freeshipForDropship` | `boolean` | No | Yes | Supports dropshipping + free shipping |
| `filter.newArrival` | `string` | No | Yes | Newly updated products: `"7DAY"` or `"30DAY"` |
| `filter.platformPick` | `boolean` | No | Yes | 1688 platform-selected domestic sources |
| `filter.globalPick` | `boolean` | No | Yes | 1688 platform-selected international sources |
| `filter.dropshipPick` | `boolean` | No | Yes | 1688 platform-selected dropship sources |
| `filter.categoryId` | `string \| number` | No | Yes | Filter by category ID |
| `filter.categoryIdList` | `Array<string \| number>` | No | Yes | Filter by list of category IDs |
| `filter.sellerOpenId` | `string` | No | Yes | Search within a single 1688 seller store (`q` becomes in-store keyword) |

#### Search Response Structure (`200 OK`)
Returns `{ "paginate": { "total", "current", "size" }, "items": [...] }` (note: `paginate` may be omitted if upstream does not return pagination metadata).

- **Taobao Item Schema (`TaobaoProductListItemV2ResponseDto`)**:
  ```json
  {
    "paginate": { "total": 1000, "current": 1, "size": 20 },
    "items": [
      {
        "id": "734467086498",
        "mi_id": "00001MIID_A",
        "origin_product_link": "http://item.taobao.com/item.htm?id=734467086498&mi_id=00001MIID_A",
        "title": "纯棉T恤男",
        "title_en": "Men's Cotton T-Shirt",
        "price": 39.9,
        "promotion_price": 29.9,
        "coupon_price": 25.9,
        "seller_type": "merchant",
        "seller_name": "某某旗舰店",
        "img_url": "https://img.alicdn.com/...",
        "translated_image_url": null,
        "link": "https://item.taobao.com/item.htm?id=734467086498",
        "tags": [],
        "promotion_displays": [
          {
            "type_name": "限时折扣",
            "promotion_info_list": [
              { "activity_code": "ACT1", "promotion_name": "满减" }
            ]
          }
        ],
        "quantity": 500,
        "language": "ru",
        "shop_dsr": "4.9",
        "delivery_times": "24shipping",
        "shipping_origin": "广东广州"
      }
    ]
  }
  ```

- **1688 Item Schema (`AlibabaProductListItemV2ResponseDto`)**:
  ```json
  {
    "paginate": { "total": 500, "current": 1, "size": 20 },
    "items": [
      {
        "id": "734467086498",
        "title": "纯棉T恤批发",
        "title_en": "Wholesale Cotton T-Shirt",
        "price": 18.5,
        "promotion_price": 15.5,
        "retail_price": 22.0,
        "wholesale_price": 16.0,
        "dropship_price": 18.5,
        "seller_type": "factory",
        "seller_name": "广州某某服饰有限公司",
        "img_url": "https://cbu01.alicdn.com/...",
        "white_image": "https://cbu01.alicdn.com/...",
        "aigc_image_url": null,
        "link": "https://detail.1688.com/offer/734467086498.html",
        "promotion_url": null,
        "sales_volume": 3200,
        "sales_volume_7d": "450",
        "retention_rate": "0.35",
        "level": 5,
        "moq": 2,
        "category_id": "311",
        "top_category_id": "10166",
        "second_category_id": "311",
        "third_category_id": "1031910",
        "is_jxhy": true,
        "is_one_psale": true,
        "is_patent_product": false,
        "is_select": true,
        "offer_identities": ["tp_member", "factory"],
        "create_date": "2025-10-01",
        "modify_date": "2026-05-12",
        "promotion_model": { "has_promotion": true, "promotion_type": "discount" },
        "review": { "composite_score": 4.8 },
        "shipping_summary": {
          "shipping_time_guarantee": "48h",
          "perfect_fulfillment_rate_30d": "0.99",
          "pickup_within_24h_rate_30d": "0.98",
          "quality_return_rate_30d": "0.01",
          "perfect_fulfillment_rate_7d": "0.99",
          "pickup_within_24h_rate_7d": "0.98",
          "delayed_shipping_rate_7d": "0.00"
        },
        "trade_info": {
          "add_cart_count_7d": "120",
          "pay_buyer_count_7d": "45",
          "add_cart_count_30d": "510",
          "pay_buyer_count_30d": "180"
        },
        "company_address": null,
        "offer_data_info": null
      }
    ]
  }
  ```

---

### 4.3 `POST /v2/products/upload-image` — Upload Image for Search

Uploads a local image file to Alibaba's server and returns `imageId` and `imageHandle` for `POST /v2/products/search-by-image`.
- **Content-Type**: `multipart/form-data`

| Form Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `file` | `binary` | **Yes** | JPEG, PNG, or WebP image (max 10 MiB input; preprocessed by Elim server). |
| `platform` | `string` | **Yes** | `"taobao"` or `"alibaba"` |

**Response `200 OK` (`ImageRefV2ResponseDto`)**:
```json
{
  "imageId": "image-123",
  "imageHandle": "9306061e-45c4-4da0-b13a-fd947228c95e"
}
```

---

### 4.4 `POST /v2/products/search-by-image` — Search Products by Image

> **Note**: Image search is not available on the Free plan.

You must provide at least one image reference:
1. `imgUrl`: Direct Alibaba CDN URL (`*.alicdn.com`).
2. `imageHandle` or `imgId`: Returned from `POST /v2/products/upload-image`.

**Request Body (`SearchProductsByImageV2Dto`)**:
```json
{
  "platform": "taobao",
  "imgUrl": "https://img.alicdn.com/bao/uploaded/i3/694223667/O1CN01zSjKgp1cxXRQZsDLA_!!694223667.jpg",
  "lang": "ru",
  "sort": "PRICE_ASC",
  "page": 1,
  "size": 20,
  "keyword": "cotton",
  "keywordTranslate": true,
  "filter": {
    "priceRange": { "min": 10, "max": 300 },
    "categoryId": "50000436"
  }
}
```

#### Parameters
| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `platform` | `string` | **Yes** | `"taobao"` or `"alibaba"` |
| `imgUrl` | `string` | Conditional* | Alibaba CDN image URL (`*.alicdn.com`) |
| `imgId` | `string` | Conditional* | `imageId` returned from `/v2/products/upload-image` |
| `imageHandle` | `string` | Conditional* | Opaque handle returned from `/v2/products/upload-image` |
| `lang` | `string` | No | `"vi"`, `"en"`, `"ru"`, `"ko"`, `"ja"` |
| `sort` | `string` | No | `"PRICE_ASC"`, `"PRICE_DESC"`, `"SALE_QTY_ASC"`, `"SALE_QTY_DESC"` |
| `filter` | `object` | No | Supports `filter.priceRange` and `filter.categoryId` |
| `page` | `number` | No | Page number (`>= 1`, default `1`) |
| `size` | `number` | No | Items per page (`1` to `20`, default `20`) |
| `keyword` | `string` | No | Additional keyword to refine visual search |
| `keywordTranslate` | `boolean` | No | Translate `keyword` |

**Response `200 OK`**: Same paginated structure (`TaobaoProductPageV2ResponseDto` or `AlibabaProductPageV2ResponseDto`) as `/v2/products/search`.

---

### 4.5 `POST /v2/products/find` & `POST /v2/products/detail` — Product Details

Both endpoints share the exact same request body and return a single product object (`TaobaoProductV2ResponseDto` or `AlibabaProductV2ResponseDto`). `POST /v2/products/detail` returns fuller 1688 metadata and is recommended when querying 1688 (`alibaba`).

**Request Body (`FindProductV2Dto`)**:
```json
{
  "platform": "taobao",
  "id": "https://item.taobao.com/item.htm?id=734467086498",
  "lang": "ru"
}
```

#### Accepted Formats for `id`
| Platform | Accepted `id` Values | Rejected `id` Values |
| :--- | :--- | :--- |
| **Taobao (`taobao`)** | 1. `mi_id` (TaoWorld ID, e.g. `00001Fyt...`)<br>2. Full Taobao/Tmall URL containing `mi_id` or numeric `id=` (e.g. `https://item.taobao.com/item.htm?id=734467086498`)<br>3. Short link `https://e.tb.cn/h.xxx?tk=...` (sent upstream as `tao_password`)<br>4. Taobao App share text (`tao_password` / 淘口令) | Bare numeric `item_id` alone (e.g. `"734467086498"`) or placeholder `id=0` without `mi_id`. *(Note: `TaobaoService::findIdentifier()` in `cargo-admin` automatically converts bare numeric IDs to `https://item.taobao.com/item.htm?id={id}`!)* |
| **1688 (`alibaba`)** | 1688 `offerId` (e.g. `"734467086498"`) | — |

#### Pricing & `quote_type` Rules
- **Taobao**: Prefer `promotion_price` (or `coupon_price` if applicable); if `null`, fall back to `price`.
- **1688 (`alibaba`)**: Price application depends on `quote_type`:
  - `direct`: Use product-level `promotion_price ?? price`.
  - `by_sku`: Each variant in `skus[]` has its own `promotion_price`, `price`, `retail_price`, `dropship_price`.
  - `by_volume`: Tiered quantity pricing in `price_range[]` (`moq`, `price`, `promotion_price`), subject to product `moq`.

#### 1688 `extra_info` Flags
| Key | Meaning |
| :--- | :--- |
| `isOnePsale` | Supports 1-piece dropshipping |
| `isSupportMix` | Supports mixed-SKU wholesale batching |
| `isOnePsaleFreePostage` | Supports dropshipping + free domestic shipping |
| `noReason7DReturn` | Allows 7-day no-reason return |
| `1688_yx` | 1688 curated/selected source (`is_jxhy`) |

#### Response Schema — Taobao (`TaobaoProductV2ResponseDto`)
```json
{
  "id": "734467086498",
  "mp_id": "678901234",
  "mi_id": "00001MIIDDETAIL",
  "origin_product_link": "http://item.taobao.com/item.htm?id=734467086498&mi_id=00001MIIDDETAIL",
  "original_product_url": "https://item.taobao.com/item.htm?id=734467086498",
  "title": "纯棉T恤",
  "title_en": "Cotton T-Shirt",
  "description": "<p>HTML description...</p>",
  "quote_type": "by_sku",
  "price": 39.9,
  "promotion_price": 29.9,
  "coupon_price": 25.9,
  "quantity": 200,
  "unit": "件",
  "shop_id": "12345678",
  "shop_name": "某某旗舰店",
  "seller_nick": "某某卖家",
  "seller_type": "merchant",
  "moq": 1,
  "batch_number": null,
  "category_id": "50000436",
  "category_name": "T恤",
  "category_path": "男装 > T恤",
  "img_urls": ["https://img.alicdn.com/..."],
  "translated_image_url": null,
  "property_images": [
    { "properties": "1627207:28341", "image_url": "https://img.alicdn.com/..." }
  ],
  "video_urls": {
    "thumbnail_url": "https://...",
    "detail_url": "https://..."
  },
  "status": "active",
  "item_type": "HAVE_MATERIAL",
  "tags": [],
  "language": "ru",
  "promotion_displays": [],
  "price_range": [
    { "moq": 1, "price": 39.9, "promotion_price": 29.9, "product_price_type": null, "system_currency_price": null }
  ],
  "sku_price_ranges": [],
  "skus": [
    {
      "id": "5578084256927",
      "mp_sku_id": "99887766",
      "price": 39.9,
      "promotion_price": 29.9,
      "coupon_price": null,
      "delivery_fee": 0,
      "quantity": 50,
      "status": "active",
      "img_url": "https://img.alicdn.com/...",
      "options": [
        {
          "name": "颜色",
          "value": "黑色",
          "name_en": "Color",
          "value_en": "Black",
          "prop_id": 1627207,
          "value_id": 28341,
          "value_desc_origin": "黑色"
        }
      ]
    }
  ],
  "attributes": [
    {
      "name": "材质",
      "value": "棉",
      "name_en": "Material",
      "value_en": "Cotton",
      "prop_id": 20000,
      "value_id": 30000
    }
  ]
}
```

#### Response Schema — 1688 (`AlibabaProductV2ResponseDto`)
Includes all core fields above plus 1688-specific fields:
- `description_en`, `unit_en`, `white_image`, `translated_img_urls[]`, `translated_white_image`
- `sold`, `review` (`{ [metric]: number }`), `level`, `is_jxhy`, `is_select` (`"true"` / `"false"`), `product_cargo_number`, `promotion_url`, `seller_shop_url`, `create_date`
- `top_category_id`, `second_category_id`, `third_category_id`
- `selling_point[]`, `offer_identities[]`, `certificate_list[]` (`certificate_name`, `certificate_code`, `certificate_photo_list[]`)
- `shipping_info[]` (`id`, `weight`, `width`, `length`, `height`, `address`, `shipping_time_guarantee`, `pkg_size_source`, `official_length`, `official_width`, `official_height`, `official_weight`, `ai_weight`, `ai_weight_accuracy`)
- `fenxiao_sale_info` (`one_piece_free_postage`, `start_quantity`, `one_piece_price`, `offer_price`)
- `seller_mix_setting` (`general_hunpi`, `mix_amount`, `mix_number`)
- `channel_price` (`channel_sku_price_list[{ sku_id, current_price }]`)
- `promotion_model` (`has_promotion`, `promotion_type`)
- `skus[]` (`AlibabaSkuV2ResponseDto`):
  - `id`, `spec_id` (**used as `sku_ref` when creating 1688 orders!**), `price`, `promotion_price`, `retail_price`, `dropship_price`, `quantity`, `status`, `img_url`, `cargo_number`, `pf_jxhy_price`, `fenxiao_price_info`, `options[]` (`name`, `value`, `name_en`, `value_en`, `img_url`, `attribute_id`, `sku_image_url_trans`).

---

### 4.6 `POST /v2/products/shipping-fee` — Calculate Taobao Domestic Shipping Fee

Calculates the domestic shipping fee (`post_fee` in CNY) for a Taobao product to a receiving address. *(Taobao only).*

**Request Body (`TaobaoShippingFeeV2Dto`)**:
```json
{
  "platform": "taobao",
  "id": "https://item.taobao.com/item.htm?id=734467086498",
  "addressInfo": {
    "country": "CN",
    "state": "广东省",
    "city": "广州市",
    "district": "天河区"
  }
}
```

**Response `200 OK` (`ProductShippingFeeV2ResponseDto`)**:
```json
{
  "item_resource": "taobao",
  "item_id": "734467086498",
  "mi_id": "00001MIID...",
  "post_fee": 8.0,
  "currency": "CNY"
}
```

---

## 5. Product API v1 (`/v1/products/*`) — Legacy Reference & v1→v2 Migration

> **Deprecation Warning**: Product API v1 (`/v1/products/*`) is marked legacy and only runs in parallel for a transitional period. New and existing integrations should migrate to `/v2/products/*`.

| Feature | Product API v1 (Legacy) | Product API v2 (Current) |
| :--- | :--- | :--- |
| **Keyword Search** | `POST /v1/products/search` | `POST /v2/products/search` |
| **Image Search** | `POST /v1/products/search-img` (`img_url`, `img_id`) | `POST /v2/products/search-by-image` (`imgUrl`, `imgId`, `imageHandle`) |
| **Upload Image** | `POST /v1/products/upload-image` (`platform: taobao \| 1688`) | `POST /v2/products/upload-image` (`platform: taobao \| alibaba`) |
| **Product Find / Detail** | `POST /v1/products/find`, `POST /v1/products/detail` (deprecated) | `POST /v2/products/find`, `POST /v2/products/detail` |
| **Shipping Fee** | Not supported | `POST /v2/products/shipping-fee` |
| **Supported `lang` Values** | `vi`, `en` only | `vi`, `en`, `ru`, `ko`, `ja` |
| **Translated Title Key** | `titleEn` (`camelCase`) | `title_en` (`snake_case`) |
| **Filter Keys** | `snake_case` (`price_range`, `shop_id`, `seller_level`, `is_factory`, `ffm_outbound`, `ffm_delivery`, `allow_return`, `allow_dropship`, `freeship_for_dropship`, `new_arrival`, `platform_pick`, `global_pick`, `dropship_pick`) | `camelCase` (`priceRange`, `shopId`, `sellerLevel`, `isFactory`, `ffmOutbound`, `ffmDelivery`, `allowReturn`, `allowDropship`, `freeshipForDropship`, `newArrival`, `platformPick`, `globalPick`, `dropshipPick`, `categoryId`, `categoryIdList`, `sellerOpenId`) |
| **Wholesale Price Key** | `whosesale_price` (typo in v1 schema) | `wholesale_price` (fixed in v2) |

---

## 6. Order API (`/v1/orders/*`)

Elim acts as a proxy purchasing & fulfillment technology layer:
- Your system collects payment from end customers (in `TJS` in `cargo-admin`) and settles with Elim via the **Elim Purchasing Wallet** (denominated in `CNY`).
- Every order in Elim tracks **three independent status fields**:
  1. `status`: Marketplace order progress on Taobao / 1688.
  2. `payment_status`: Settlement progress between your Elim account and Elim's purchasing wallet.
  3. `platform_payment_status`: Actual upstream payment execution result on Taobao / 1688.

### 6.1 Order Status State Machines

#### 1. Marketplace Status (`status`)
```text
creating ──► pending_payment ──► paid ──► shipped ──► completed
                   └──► cancelled
```
| `status` Value | Meaning |
| :--- | :--- |
| `creating` | Order just initiated, waiting for Taobao/1688 to confirm creation |
| `pending_payment` | Marketplace created the order, awaiting payment |
| `paid` | Order paid on the marketplace side |
| `shipped` | Goods shipped from the seller in China, in domestic transit |
| `completed` | Order completed, receipt confirmed |
| `cancelled` | Order has been cancelled |
| `unknown` | Could not read status from the marketplace *(Stop payment flow and inspect/sync before paying!)* |

#### 2. Elim Wallet Payment Status (`payment_status`)
```text
unpaid ──► requested ──► approved ──► paid ──► refunded
  ▲            │
  └────────────┴──► rejected
```
*(Or direct transition `unpaid` ──► `paid` when calling `POST /v1/purchasing/orders/:id/confirm`)*

| `payment_status` Value | Meaning | Effect on Elim Purchasing Wallet |
| :--- | :--- | :--- |
| `unpaid` | Default when order is created; no payment requested yet | Not affected |
| `requested` | Payment request submitted via `/request-payment` | Held in `frozen_balance` |
| `approved` | Elim approved the request and is paying the marketplace | Still held in `frozen_balance` |
| `paid` | Payment completed | Deducted from `balance` |
| `rejected` | Elim rejected the request (`rejection_reason` explains why) | Released from `frozen_balance` back to `available_balance` |
| `refunded` | A paid order was refunded to the Elim wallet | Credited back to `balance` (`order_refund` transaction) |

#### 3. Platform Payment Result (`platform_payment_status`)
| `platform_payment_status` Value | Meaning |
| :--- | :--- |
| `pending` | Sending payment instruction to Taobao/1688 |
| `success` | Marketplace recorded the payment |
| `failed` | Marketplace reported an error — Elim handles it manually or resets `payment_status` to `unpaid` |
| `unknown` | Result not yet determined |

---

### 6.2 Order Endpoints Summary
| Method | Endpoint | Rate Limit | Description |
| :--- | :--- | :---: | :--- |
| `POST` | `/v1/orders/preview` | `20 / 60s` | Preview order totals (`CNY`), domestic shipping fee, and check `unavailable_items` — no order created, returns 10-min `preview_token`. |
| `POST` | `/v1/orders` | `10 / 60s` | Create a real order on Taobao or 1688 (`status = pending_payment`, `payment_status = unpaid`). |
| `GET` | `/v1/orders` | `30 / 60s` | List orders (paginated, filterable). |
| `GET` | `/v1/orders/stats` | `30 / 60s` | Aggregate order statistics by date range / status. |
| `GET` | `/v1/orders/:id` | `30 / 60s` | Get order detail by `ORD...` ID — **auto-syncs latest status from Taobao/1688 on every call**. |
| `POST` | `/v1/orders/:id/refresh` | — | Force re-fetch order from upstream marketplace and overwrite stored status/amount/order data. |
| `POST` | `/v1/orders/:id/cancel` | `10 / 60s` | Cancel an order while `status` is `creating` or `pending_payment`. |
| `GET` | `/v1/orders/:id/logistic-detail` | `20 / 60s` | Domestic China package tracking (`?package_id=&lang=vi`). |

---

### 6.3 `POST /v1/orders/preview` & `POST /v1/orders`

Both endpoints accept the same request payload structure.

**Request Body**:
```json
{
  "platform": "alibaba",
  "receiver_address": {
    "name": "Nguyen Van A",
    "phone": "02812345678",
    "mobile": "13800138000",
    "address": "广州市天河区体育西路123号",
    "province": "广东省",
    "city": "广州市",
    "area": "天河区",
    "town": "天河南街道"
  },
  "line_items": [
    {
      "product_ref": "734467086498",
      "sku_ref": "5578084256927",
      "quantity": 2,
      "price": 15.5
    }
  ],
  "preview_token": "c5f75ec1-9f8d-4bf6-9de2-048d50fef349",
  "idempotency_key": "checkout-2026-08-25-001",
  "client_order_id": "CARGO-10045",
  "remark": "Note for the seller",
  "promotion_id": "PROMO123"
}
```

#### Request Fields
| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `platform` | `string` | **Yes** | `"taobao"` or `"alibaba"` |
| `receiver_address` | `Address` | **Yes** | Valid China warehouse delivery address (`name`, `phone`, `mobile`, `address` required; `province`, `city`, `area` recommended; `town` optional). |
| `warehouse_address` | `Address` | No | Optional dispatch warehouse address. |
| `line_items` | `LineItem[]` | **Yes** | Array of items to order (see below). |
| `preview_token` | `string` | No | Returned by `/v1/orders/preview` (single-use, expires in 10 minutes). Pass on `POST /v1/orders` to lock in previewed pricing. |
| `idempotency_key` | `string` | Recommended | Anti-duplicate checkout key. Repeating a request with the same key returns the existing order. |
| `client_order_id` | `string` | No | Your own system's order code (auto-generated if omitted). |
| `promotion_id` | `string` | No | Optional promotion code. |
| `remark` | `string` | No | Buyer note for the seller. |

#### `line_items[]` Item Structure
| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `product_ref` | `string` | **Yes*** | Product ID (`id` / `mi_id` / `offerId`) or product URL from search/detail. |
| `sku_ref` | `string` | **Yes*** | Variant identifier: for **Taobao** use `skus[].id`; for **1688 (`alibaba`)** use `skus[].spec_id`. |
| `quantity` | `number` | **Yes** | Quantity to purchase. |
| `price` | `number` | No | Unit price in CNY (recommended so preview returns exact figures). |

> *Backward Compatibility Note*: Legacy keys `mp_id` + `id` (or `mp_skuid`) are still accepted by Elim, but `product_ref` + `sku_ref` is the primary documented contract.

#### Preview Response (`POST /v1/orders/preview` → `201 Created` / `200 OK`)
```json
{
  "success": true,
  "preview_token": "c5f75ec1-9f8d-4bf6-9de2-048d50fef349",
  "currency": "CNY",
  "total_product_amount": 31.0,
  "total_post_fee": 8.0,
  "total_amount": 39.0,
  "shops": [
    {
      "seller_id": "b2b-2201234567",
      "seller_name": "广州某某贸易有限公司",
      "post_fee": 8.0,
      "shop_amount": 31.0,
      "items": [
        {
          "product_id": "734467086498",
          "sku_id": "5578084256927",
          "quantity": 2,
          "unit_price": 15.5,
          "total_price": 31.0
        }
      ]
    }
  ],
  "unavailable_items": []
}
```
- **Critical Rule**: Always inspect `unavailable_items[]`. If non-empty, block order creation and prompt the user to remove/update out-of-stock or invalid SKU items.

#### Create Order Response (`POST /v1/orders` → `201 Created`)
```json
{
  "status": "pending_payment",
  "data": {
    "id": "ORD0000000123",
    "order_list": [
      {
        "success": true,
        "amount": 39.0,
        "post_fee": 8.0,
        "lines": [
          {
            "sub_id": "E2101234567890",
            "product_id": "734467086498",
            "sku_id": "5578084256927",
            "quantity": 2,
            "amount": 31.0
          }
        ]
      }
    ]
  }
}
```
- `data.id`: Internal Elim order ID (`ORD` + 10 digits, e.g. `ORD0000000123`). Store this as `elim_order_id` and use it for all subsequent calls.
- `status`: `"pending_payment"` on success. If `"unknown"`, halt automatic payment and re-check via `GET /v1/orders/:id`.

---

### 6.4 `GET /v1/orders` — List Orders

**Query Parameters**:
| Parameter | Type | Description |
| :--- | :--- | :--- |
| `page` | `number` | Page number (default `1`) |
| `size` | `number` | Page size (default `20`) |
| `platform` | `string` | `"taobao"` or `"alibaba"` (omit for both) |
| `status` | `string` | Filter by platform status (`creating`, `pending_payment`, `paid`, `shipped`, `completed`, `cancelled`, `unknown`) |
| `payment_status` | `string` | Filter by Elim payment status (`unpaid`, `requested`, `approved`, `paid`, `rejected`, `refunded`) |
| `internal_order_id` | `string` | Filter by `ORD...` ID |
| `platform_order_id` | `string` | Filter by upstream Taobao/1688 order ID |
| `order_id` | `number[]` | Filter by numeric order IDs |
| `client_order_id` | `string` | Filter by your own `client_order_id` |
| `created_from` | `string` | ISO-8601 start timestamp (`2026-01-01T00:00:00.000Z`) |
| `created_to` | `string` | ISO-8601 end timestamp (`2026-01-31T23:59:59.999Z`) |

**Response `200 OK`**:
```json
{
  "total": 1,
  "page": 1,
  "size": 20,
  "items": [
    {
      "id": "ORD0000000123",
      "platform": "alibaba",
      "status": "paid",
      "payment_status": "paid",
      "total_amount_cny": 39.0,
      "total_amount": 39.0,
      "products": [
        {
          "id": "734467086498",
          "name": "某某商品",
          "price": 15.5,
          "quantity": 2,
          "img_urls": ["https://..."]
        }
      ],
      "created_at": "2026-04-09T10:30:00.000Z"
    }
  ]
}
```

---

### 6.5 `GET /v1/orders/stats` — Order Statistics
**Query Parameters**: `page`, `size`, `from` (ISO date), `to` (ISO date), `status`, `payment_status`.
Returns aggregated order statistics and a paginated order sample.

---

### 6.6 `GET /v1/orders/:id` & `POST /v1/orders/:id/refresh` — Order Detail & Force Sync
- `GET /v1/orders/:id` automatically syncs the latest status from Taobao/1688 before responding.
- `POST /v1/orders/:id/refresh` forces an upstream re-fetch and overwrites stored status/amount/order data (`201 Created`, or `400` if not placed upstream yet, `502` if upstream sync failed).

**Platform Differences in `GET /v1/orders/:id` Response**:
| Marketplace | Items Array Key | Domestic China Logistics Data |
| :--- | :--- | :--- |
| **1688 (`alibaba`)** | `products[]` | Embedded directly in `logistics.logistics_info[]` (or you can also call `GET /v1/orders/:id/logistic-detail` with optional `package_id`) |
| **Taobao (`taobao`)** | `line_items[]` | Must call `GET /v1/orders/:id/logistic-detail?package_id={package_id}` using the integer `package_id` from the Taobao order detail response |

---

### 6.7 `POST /v1/orders/:id/cancel` — Cancel Order
- Only allowed when `status` is `creating` or `pending_payment`.
- If the order has no upstream marketplace order ID yet, returns:
  ```json
  {
    "success": true,
    "message": "Internal order cancelled, no external order to cancel"
  }
  ```

---

### 6.8 `GET /v1/orders/:id/logistic-detail` — Domestic China Package Tracking
Retrieves domestic China courier tracking steps for an order's packages.

**Path & Query Parameters**:
| Parameter | In | Type | Required | Description |
| :--- | :--- | :--- | :---: | :--- |
| `id` | `path` | `string` | **Yes** | Internal Elim order ID (`ORD0000000123`) |
| `package_id` | `query` | `string` | Conditional | **Taobao**: Required `package_id` (from order detail). **1688**: Optional; filters by `package_id` or `tracking_number` (omit to return all packages). |
| `lang` | `query` | `string` | No | Optional `"vi"` to add `desc_vi` and `status_desc_vi` translations alongside original tracking text. |

**Example Request**:
```http
GET /v1/orders/ORD0000000123/logistic-detail?package_id=12345678901234567890
Authorization: Bearer <access_token>
```

---

## 7. Purchasing Wallet & Order Payment API (`/v1/purchasing/*`)

### 7.1 Purchasing Wallet Endpoints
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/v1/purchasing/wallet` | Get current CNY purchasing wallet balances (`balance`, `frozen_balance`, `available_balance`). Free call. |
| `GET` | `/v1/purchasing/wallet/transactions` | Get paginated wallet transaction history (`deposit`, `order_freeze`, `order_unfreeze`, `order_deduction`, `order_refund`, `admin_adjustment`). Free call. |
| `GET` | `/v1/purchasing/exchange-rates` | Get active Elim exchange rates (`source_currency`, `target_currency`, `rate`, `markup_percent`, `effective_rate`). |

#### `GET /v1/purchasing/wallet` Response (`200 OK`)
```json
{
  "balance": 1670.30,
  "frozen_balance": 286.50,
  "available_balance": 1383.80,
  "total_deposited": 5000.00,
  "total_spent": 3329.70,
  "updated_at": "2026-06-19T00:00:00.000Z"
}
```
- Always compare `available_balance` (`balance - frozen_balance`) against the order amount in CNY.

#### `GET /v1/purchasing/wallet/transactions`
**Query Parameters**:
- `page` (default `1`), `size` (default `20`)
- `type`: `"deposit" | "order_deduction" | "order_refund" | "admin_adjustment" | "order_freeze" | "order_unfreeze"`
- `date_from`, `date_to`: ISO date/timestamp strings

**Response `200 OK`**:
```json
{
  "total": 1,
  "page": 1,
  "size": 20,
  "items": [
    {
      "id": 101,
      "user_id": 42,
      "type": "order_deduction",
      "status": "completed",
      "amount_cny": "286.50",
      "source_currency": null,
      "source_amount": null,
      "exchange_rate_id": null,
      "exchange_rate_value": null,
      "service_fee_cny": "5.00",
      "reference_type": "order",
      "reference_id": "ORD0000000123",
      "idempotency_key": null,
      "note": null,
      "balance_before": "1670.30",
      "balance_after": "1383.80",
      "created_at": "2026-06-19T00:00:00.000Z"
    }
  ]
}
```

---

### 7.2 Order Payment Endpoints (`/v1/purchasing/orders/:id/*`)
`:id` is the `ORD...` internal order ID.

| Method | Endpoint | Transition (`payment_status`) | Description |
| :--- | :--- | :---: | :--- |
| `POST` | `/v1/purchasing/orders/:id/request-payment` | `unpaid` → `requested` | Reserves `frozen_amount` in CNY in the purchasing wallet and submits the order for Elim approval. |
| `POST` | `/v1/purchasing/orders/:id/cancel-payment-request` | `requested` → `unpaid` | Withdraws a pending payment request and unfreezes the reserved CNY balance. |
| `POST` | `/v1/purchasing/orders/:id/confirm` | `unpaid` → `paid` | Direct payment (where enabled on account) — deducts CNY balance immediately with no manual approval step. |
| `GET` | `/v1/purchasing/orders/:id/payment` | — | Retrieve details of the order's payment record. |

#### `POST /v1/purchasing/orders/:id/request-payment` Response (`201 Created`)
```json
{
  "success": true,
  "order_id": "ORD0000000123",
  "frozen_amount": 301.50,
  "goods_amount_cny": 286.50,
  "shipping_fee_cny": 0,
  "service_fee_cny": 15.00
}
```

#### `POST /v1/purchasing/orders/:id/confirm` Response (`200/201`)
```json
{
  "order_id": "ORD0000000123",
  "goods_amount_cny": 281.50,
  "shipping_fee_cny": 0,
  "service_fee_cny": 5.00,
  "total_amount_cny": 286.50,
  "payment_status": "paid",
  "wallet_balance_after": 1383.80
}
```

#### Insufficient Balance Error (`400 Bad Request`)
Returned by `/request-payment` or `/confirm` when `available_balance < required`:
```json
{
  "error": "insufficient_balance",
  "current_balance": 120.00,
  "frozen_balance": 30.00,
  "available_balance": 90.00,
  "required": 286.50,
  "deficit": 196.50
}
```

---

## 8. Webhooks API (`/v1/webhooks/*`) & HMAC Verification

Instead of polling `GET /v1/orders/:id`, you can register an HTTPS webhook endpoint so Elim notifies your server whenever an order's `status` or `payment_status` changes.

### 8.1 Subscribable Events
| Event Name | Triggered When |
| :--- | :--- |
| `order.updated` | `status` changes (marketplace sync, scheduled sync, or order cancellation) |
| `payment.updated` | `payment_status` changes (`requested`, request withdrawn, `approved`, `rejected`, `paid`, platform payment failure, or `refunded`) |

### 8.2 Webhook Management Endpoints
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/v1/webhooks` | List registered webhook subscriptions |
| `POST` | `/v1/webhooks` | Create a webhook subscription — **returns `secret` only once** |
| `PATCH` | `/v1/webhooks/:id` | Update `url`, `events`, or `is_active` |
| `DELETE` | `/v1/webhooks/:id` | Delete a webhook subscription |
| `POST` | `/v1/webhooks/:id/rotate-secret` | Rotate signing secret — **returns the new `secret` only once** |
| `GET` | `/v1/webhooks/:id/deliveries` | List recent webhook delivery attempts and HTTP responses |
| `POST` | `/v1/webhooks/:id/test` | Trigger a test webhook delivery to the registered `url` |

**Create Subscription Payload (`POST /v1/webhooks`)**:
```json
{
  "url": "https://your-domain.com/api/v1/webhooks/elim",
  "events": ["order.updated", "payment.updated"],
  "is_active": true
}
```

### 8.3 Incoming Webhook Delivery Format

#### Delivery Headers
| Header | Example Value | Description |
| :--- | :--- | :--- |
| `Content-Type` | `application/json` | JSON payload |
| `User-Agent` | `elim-api-webhooks/1.0` | Elim webhook sender identifier |
| `X-Elim-Event` | `order.updated` or `payment.updated` | Event type |
| `X-Elim-Delivery` | `dlv_...` | Unique delivery attempt ID (changes on retry) |
| `X-Elim-Timestamp` | `1750291200` | Unix timestamp in seconds |
| `X-Elim-Signature` | `sha256=8f4e...` | HMAC-SHA256 hex digest of `"{X-Elim-Timestamp}.{raw_json_body}"` |

#### Delivery Payload Body
```json
{
  "id": "evt_4a1f2c...",
  "event": "payment.updated",
  "created_at": "2026-06-19T00:00:00.000Z",
  "data": {
    "order": {
      "id": "ORD0000000123",
      "platform": "taobao",
      "order_id": "123456789",
      "status": "paid",
      "payment_status": "paid",
      "platform_payment_status": "success",
      "total_amount_cny": 123.45,
      "updated_at": "2026-06-19T00:00:00.000Z"
    }
  }
}
```

### 8.4 PHP / Laravel HMAC-SHA256 Verification Implementation
- **Signing String**: `"{X-Elim-Timestamp}.{raw_json_body}"` (use `$request->getContent()`, never re-encode parsed JSON).
- **Replay Protection**: Reject requests where `abs(time() - (int) $timestamp) > 300` (5 minutes).
- **Out-of-Order Protection**: Compare `data.order.updated_at` against your stored timestamp so older delayed retries do not overwrite newer state.

```php
public function verifyElimWebhook(Request $request, string $secret): bool
{
    $timestamp = (string) $request->header('X-Elim-Timestamp', '');
    $signature = (string) $request->header('X-Elim-Signature', '');
    $rawBody   = $request->getContent();

    if ($timestamp === '' || $signature === '' || ! ctype_digit($timestamp)) {
        return false;
    }

    // Reject timestamps older/newer than 5 minutes (300 seconds)
    if (abs(time() - (int) $timestamp) > 300) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

    return hash_equals($expected, $signature);
}
```

### 8.5 Webhook Retry Policy
- **Success**: Any `2xx` HTTP response within `10 seconds`.
- **Retries**: Up to **3 total attempts** on timeout, network error, or non-`2xx` status (Retry 1 after `60s`, Retry 2 after `300s`).

---

## 9. Elim CLI Reference

Elim provides an official open-source CLI (`github.com/alvintran/elim-cli`, requires Node.js `>= 16`):
```bash
npm install -g elim-cli
elim --version
elim config # Prompts for API key and stores it locally
```

| Command | Description |
| :--- | :--- |
| `elim search "<keyword>" --min-price 10 --max-price 100 --lang en --json` | Search products by keyword (`--json`, `--columns name,price`) |
| `elim search-img --url "<alicdn_url>"` | Search products by Alibaba image URL |
| `elim search-img --id "<image_id>"` | Search products by uploaded image ID |
| `elim find <product_id>` | Fetch product details |
| `elim upload-image <file_path>` | Upload an image to get an `image_id` |
| `elim unshorten "<short_url>"` | Expand a shortened `e.tb.cn` / Taobao / 1688 link |

---

## 10. Global Error Codes & Troubleshooting

Error responses follow the standard JSON format:
```json
{
  "statusCode": 422,
  "message": "LINE_ITEM_SKU_NOT_RESOLVABLE",
  "error": "Unprocessable Entity"
}
```

| HTTP Status | Error / Situation | Resolution |
| :---: | :--- | :--- |
| `400` | `insufficient_balance` | Purchasing wallet `available_balance` is lower than `required`. Top up `deficit` CNY and retry. |
| `400` | `Cannot request payment: current status is "..."` | Order is no longer in `payment_status = unpaid`. |
| `400` | `Invalid platform ID format` | Verify `product_ref` / `sku_ref` and `platform` (`taobao` vs `alibaba`). |
| `400` | Quota exhausted and no Credits left | Upgrade subscription plan or top up Credits on https://in.elim.asia. |
| `401` | `Unauthorized` / JWT expired | Refresh JWT via `POST /v1/auth/refresh`, re-login via `/v1/auth/login`, or check `x-api-key`. |
| `403` | `Forbidden` | Account locked, insufficient permissions, or attempting to access an order owned by another account. |
| `404` | `Not Found` | Invalid `ORD...` ID, package not found, or no active subscription on `/v1/me/plan-status`. |
| `409` | `Conflict` | Payment for this order is already under review (`requested` / `approved`). |
| `422` | `LINE_ITEM_SKU_NOT_RESOLVABLE` / `LINE_ITEM_PRODUCT_NOT_RESOLVABLE` | Invalid/stale `product_ref` or `sku_ref` (Taobao). Re-fetch fresh product detail via `/v2/products/find`. |
| `429` | `Too Many Requests` | Rate limit exceeded. Respect `X-RateLimit-Reset` / wait ~60s and pace requests. |
| `502` | `Bad Gateway` | Upstream Taobao/1688 did not respond in time. Retry read/sync; use `idempotency_key` before retrying order creation. |

---

## 11. Cargo-Admin Project Integration Audit & Implementation Blueprint

This section maps the official Elim API specification directly to our Laravel codebase (`/mnt/e/Herd/cargo-admin`).

### 11.1 Current Project Files Involved
- **Config & Settings**:
  - `app/Support/Elim/ElimApiConfig.php`
  - `app/Support/Elim/ElimWarehouseAddress.php`
  - `app/Livewire/Admin/Settings/ElimApiSettingsPage.php`
  - `app/Livewire/Admin/Settings/ElimWarehouseSettingsPage.php`
- **Auth & HTTP Client**:
  - `app/Services/Elim/ElimAuthService.php`
  - `app/Services/Elim/ElimApiClient.php`
  - `app/Services/Elim/ElimApiLogger.php`
- **Product Catalog**:
  - `app/Services/Elim/AbstractElimProductService.php`
  - `app/Services/Elim/TaobaoService.php`
  - `app/Services/Elim/Alibaba1688Service.php`
  - `app/Support/Elim/ProductNormalizer.php`
- **Order & Purchasing Wallet**:
  - `app/Services/Elim/ElimOrderApiService.php`
  - `app/Services/Elim/ElimDemoCheckoutService.php`
  - `app/Services/Order/OrderCheckoutService.php`
  - `app/Services/Order/CustomerOrderLifecycleService.php`

---

### 11.2 Feature-by-Feature Gap Analysis & Fixes Required for Implementation

#### Gap 1: Upgrade Product Search & Detail from Legacy `v1` to `v2`
- **Current State (`app/Services/Elim/AbstractElimProductService.php`)**:
  - `search()` calls `POST /v1/products/search` (Line 30).
  - `find()` calls `POST /v1/products/find` (Line 55).
  - `searchByImage()` and `uploadImage()` already call `/v2/products/search-by-image` and `/v2/products/upload-image` (Lines 85, 93).
- **Why Upgrade**:
  1. Legacy `/v1/products/search` and `/v1/products/find` only support `lang: vi | en`. When our users browse in Russian (`ru`), `v1` fails or ignores `ru`, whereas `/v2/products/search`, `/v2/products/find`, and `/v2/products/detail` natively support `lang: vi | en | ru | ko | ja`!
  2. `v2` supports `keywordTranslate: true` so Russian/English search queries are automatically translated for Taobao/1688.
  3. For 1688 (`alibaba`), `POST /v2/products/detail` returns fuller 1688 metadata (`shipping_info`, `fenxiao_sale_info`, `spec_id`, etc.) than `/find`.
  4. In `v2`, `uploadImage()` returns `{ "imageId": "...", "imageHandle": "..." }` and `search-by-image` accepts `imageHandle` in addition to `imgId` and `imgUrl`.
  5. In `v2`, `filter` keys use `camelCase` (`priceRange`, `shopId`, `sellerLevel`, `isFactory`, `categoryId`, etc.) instead of `v1`'s `snake_case`.

#### Gap 2: Fix Order Preview Response Parsing (`OrderCheckoutService::parsePreviewResponse`)
- **Current State (`app/Services/Order/OrderCheckoutService.php` Lines 119–130)**:
  ```php
  'goods_subtotal_cny' => (float) ($data['goods_amount_cny'] ?? $data['goods_amount'] ?? $data['goods_subtotal'] ?? 0),
  'shipping_fee_cny' => (float) ($data['shipping_fee_cny'] ?? $data['shipping_fee'] ?? 0),
  ```
- **Official Elim `/v1/orders/preview` Response**:
  Returns `total_product_amount`, `total_post_fee`, `total_amount`, `preview_token`, `shops[]`, and `unavailable_items[]`.
- **Implementation Fix**:
  Update `parsePreviewResponse()` to also read `total_product_amount` and `total_post_fee` (and extract `preview_token` so it can be forwarded to `POST /v1/orders`):
  ```php
  'goods_subtotal_cny' => (float) ($data['total_product_amount'] ?? $data['goods_amount_cny'] ?? $data['goods_amount'] ?? $data['goods_subtotal'] ?? 0),
  'shipping_fee_cny' => (float) ($data['total_post_fee'] ?? $data['shipping_fee_cny'] ?? $data['shipping_fee'] ?? 0),
  'total_amount_cny' => (float) ($data['total_amount'] ?? $data['total_amount_cny'] ?? 0),
  'preview_token' => $data['preview_token'] ?? null,
  ```

#### Gap 3: Send `product_ref` + `sku_ref`, `preview_token`, `idempotency_key`, and `client_order_id` on Order Creation
- **Current State (`app/Support/Elim/ProductNormalizer.php` Lines 146–159)**:
  `toElimLineItem()` only builds legacy `{ "mp_id": ..., "id": ..., "quantity": ..., "price": ... }`.
- **Official Contract**:
  - `line_items[]` should include `product_ref` (product `id` / `mi_id` / `offerId`) and `sku_ref` (Taobao `skus[].id`, 1688 `skus[].spec_id`), optionally keeping `mp_id` and `id` for backward compatibility.
  - `POST /v1/orders` should include `preview_token` (from preview response), `idempotency_key` (e.g. UUID or deterministic user+cart hash), and `client_order_id`.

#### Gap 4: Fix Domestic Logistics Endpoint URL (`ElimOrderApiService::logisticDetail`)
- **Current State (`app/Services/Elim/ElimOrderApiService.php` Lines 23–26)**:
  ```php
  public function logisticDetail(int $packageId): array
  {
      return $this->client->get('/v1/orders/'.$packageId.'/logistic-detail');
  }
  ```
- **Bug**: The path parameter `:id` in `/v1/orders/:id/logistic-detail` must be the **`ORD...` internal order ID** (`$order->elim_order_id`), while `package_id` is a **query parameter**!
- **Implementation Fix**:
  ```php
  public function logisticDetail(string $elimOrderId, int|string|null $packageId = null, ?string $lang = null): array
  {
      $query = array_filter([
          'package_id' => $packageId !== null ? (string) $packageId : null,
          'lang' => $lang,
      ], fn (mixed $v): bool => $v !== null && $v !== '');

      return $this->client->get('/v1/orders/'.urlencode($elimOrderId).'/logistic-detail', $query);
  }
  ```

#### Gap 5: Support Both Payment Modes (`/confirm` vs `/request-payment`) & Order Refresh
- **Current State (`app/Services/Elim/ElimOrderApiService.php`)**:
  Implements `confirmPayment()` (`POST /v1/purchasing/orders/{id}/confirm`).
- **Additional Official Endpoints to Add to `ElimOrderApiService`**:
  - `requestPayment(string $elimOrderId)` → `POST /v1/purchasing/orders/{id}/request-payment`
  - `cancelPaymentRequest(string $elimOrderId)` → `POST /v1/purchasing/orders/{id}/cancel-payment-request`
  - `paymentDetail(string $elimOrderId)` → `GET /v1/purchasing/orders/{id}/payment`
  - `refreshOrder(string $elimOrderId)` → `POST /v1/orders/{id}/refresh`
  - `listOrders(array $query = [])` → `GET /v1/orders`
  - `orderStats(array $query = [])` → `GET /v1/orders/stats`

#### Gap 6: Add Webhook Receiver (`/api/v1/webhooks/elim`) & Admin Webhook Management
- **Why**: Currently `cargo-admin` only updates Elim order statuses when a user or admin manually triggers `syncFromElim()`.
- **Implementation Plan**:
  1. Add `POST /api/v1/webhooks/elim` controller endpoint that verifies `X-Elim-Timestamp` and `X-Elim-Signature` (`sha256=` HMAC-SHA256 over `"{timestamp}.{rawBody}"`).
  2. Handle `order.updated` and `payment.updated` events by looking up `CustomerOrder::where('elim_order_id', $payload['data']['order']['id'])->first()`, checking `updated_at`, and updating `status` (via `OrderStatus::mapFromElimStatus()`), `payment_status`, and `elim_detail_snapshot`.
  3. Add `/v1/webhooks` CRUD methods in `ElimWebhookService` so Super Admin can register/test/rotate the webhook secret from `/admin/settings/elim-api`.

#### Gap 7: Optional Static `x-api-key` Support & Plan Quota Widget (`GET /v1/me/plan-status`)
- Allow `ElimApiConfig` and `ElimApiSettingsPage` to optionally store an `elim_api_key`. When configured, `ElimApiClient` can send `x-api-key: <key>` directly without needing `/v1/auth/login` or `/v1/auth/refresh` round-trips.
- Add `GET /v1/me/plan-status` and `GET /v1/purchasing/wallet` status cards to the Admin Elim Settings page so administrators can monitor their remaining monthly request quota, credit balance, and CNY purchasing wallet balance in real time.
