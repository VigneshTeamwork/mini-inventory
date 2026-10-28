# PRD — Store Order & Inventory Mini-System

| | |
|---|---|
| **Product** | Store Billing — counter order entry with live inventory |
| **Platform** | Laravel 12 (PHP 8.2), MySQL 8 / MariaDB 10.4+ (XAMPP), Blade UI |
| **Source brief** | `Laravel_Developer_Mini_Task.pdf` (Mallow Technologies take-home) |
| **Wireframe** | [`docs/wireframe.png`](docs/wireframe.png) |
| **Timebox** | 3 days elapsed |
| **Status** | Draft v1.0 — 2026-10-28 |

---

## 1. Summary

A small Laravel app that lets a retail counter record a customer's order against a product catalog. It also keeps stock correct. The cashier enters the customer's email (and name, if the customer is new) and adds product lines. The system works out the subtotal, the tax for each product and the grand total. It deducts stock atomically and returns the bill. A queued job then sends an order-confirmation email; a log/fake mailer is acceptable. The system also lists products that are running low.

The evaluation centres on **data modelling, validation, service-layer design, queued processing, edge-case tests and concurrency-safe stock deduction**, not on visual polish.

## 2. Goals & Non-Goals

### Goals
1. A normalized schema for products, customers, orders and order lines, with the supporting tables it needs.
2. A JSON API to create an order, fetch a customer's order history and list low-stock products.
3. **No overselling** under concurrent requests. If one unit is left and two orders arrive together, exactly one succeeds.
4. Order confirmation is handled asynchronously through Laravel's queue.
5. Automated tests that cover the happy path and the edge cases.
6. A thin Blade screen that follows the wireframe, so the flow can be demoed in the screen recording.

### Non-Goals (out of scope)
- Authentication, roles or multi-store / multi-tenant support. The app is assumed to be a trusted internal counter tool.
- Real SMTP delivery, payment gateways, refunds, returns, order editing or cancellation.
- Product/customer CRUD screens. Data comes from seeders; a read-only product list is exposed for the UI.
- Pixel-perfect design.

## 3. Users & Primary Flow

**Persona: counter cashier.** They are fast and keyboard-driven, and they need to see straight away when an item can't be sold.

```
Enter customer email ──► name auto-fills if customer exists (else type name)
        │
Add product rows (dropdown + qty) ──► line totals & running totals update
        │
(optional) enter amount given ──► balance + change denominations shown
        │
"Generate Bill" ──► POST /api/orders
        ├─ 201 → bill shown on page, stock reduced, confirmation job queued
        └─ 422 → inline error on the offending line (e.g. "Only 1 left")
```

The low-stock alert panel is always visible on the screen.

## 4. Functional Requirements

Priority: **P0** = required by the brief · **P1** = shown in the wireframe or strongly implied · **P2** = nice to have if time permits.

### FR-1 Data model & seeding (P0)
- FR-1.1 Products have a name, a **unique code**, a unit price, a tax percentage and stock on hand.
- FR-1.2 Customers have a name and a **unique email**.
- FR-1.3 An order has one customer and ≥1 product lines (product + quantity). Subtotal, tax and grand total are computed and stored.
- FR-1.4 Factories and seeders create sample products, including some below the low-stock threshold, and sample customers.

### FR-2 Create order — `POST /api/orders` (P0)
- FR-2.1 Accepts `customer.email`, `customer.name` and `items[] {product_id, quantity}`. It also accepts an optional `amount_tendered` (P1).
- FR-2.2 Validation is done by a FormRequest (see §7.1).
- FR-2.3 The customer is resolved by email (case-insensitive, trimmed). If no match exists, a customer is created; `name` is then required.
- FR-2.4 Stock availability is validated **inside the locking transaction**. The FormRequest pre-check is advisory only.
- FR-2.5 Line subtotal, line tax and line total are computed per line, using each product's tax %. Order totals are sums of the line values (§6).
- FR-2.6 Stock is deducted and the order and its lines are written in **one DB transaction**. Any failure rolls back everything.
- FR-2.7 Returns `201 Created` with the full order, including lines and the customer.
- FR-2.8 If any line has insufficient stock, the API returns `422` with the failing line(s), and nothing is persisted.

### FR-3 Customer order history — `GET /api/customers/{email}/orders` (P0)
- FR-3.1 Returns the customer and their orders, newest first, each with its lines.
- FR-3.2 Email matching is case-insensitive. An unknown email returns `404`.
- FR-3.3 Results are paginated (default 15, `per_page` max 50).

### FR-4 Low-stock products — `GET /api/products/low-stock` (P0)
- FR-4.1 Returns products where `stock_on_hand < threshold`, ordered by stock ascending.
- FR-4.2 The threshold comes from `config('inventory.low_stock_threshold')`, which is set by the `LOW_STOCK_THRESHOLD` env variable (default `10`).
- FR-4.3 The optional query parameter `?threshold=N` (integer, 0–100000) overrides the default for that request.

### FR-5 Order confirmation job (P0)
- FR-5.1 After an order is created, `SendOrderConfirmation` (implementing `ShouldQueue`) is dispatched **after the transaction commits** (`afterCommit`).
- FR-5.2 The job loads the order and sends an `OrderConfirmationMail` mailable through the `log` mailer (`MAIL_MAILER=log`). It also writes a structured log line: `order_number`, `email`, `grand_total`.
- FR-5.3 Queue driver: `database`. Settings: `tries = 3`, `backoff = [10, 30, 60]`. Failures go to `failed_jobs`.
- FR-5.4 (P1) A PDF of the bill is attached to the email, generated with `barryvdh/laravel-dompdf`, as the wireframe says ("emails PDF to customer").
- FR-5.5 The API response never waits on the job.

### FR-6 Concurrency safety (P0)
Two simultaneous requests for the last unit must result in exactly **one `201` and one `422`**, with final stock `0` and never negative. The design is in §8.

### FR-7 Tests (P0)
Feature and unit tests cover order creation and at least the insufficient-stock edge case. The full matrix is in §10.

### FR-8 Counter UI (P1, from the wireframe)
- FR-8.1 Single page `GET /`, titled "Store Billing — New Order".
- FR-8.2 **Customer**: an email input. On blur it calls `GET /api/customers/lookup?email=` and auto-fills the name if the customer exists.
- FR-8.3 **Products**: rows of product dropdown + qty, showing price and line total. A row can be added with "+ Add Product" and removed. The dropdown is fed by `GET /api/products`.
- FR-8.4 **Payment panel**: shows subtotal, tax and grand total, plus an "Amount Given by Customer" input. It shows the **Balance to Return** and its breakdown into notes and coins (§6.3).
- FR-8.5 **Low Stock Alert panel**: populated from the low-stock endpoint, listing name and units left. It refreshes after each order.
- FR-8.6 **Generate Bill** posts the order and then shows the bill on the page (`/orders/{order_number}`), with a print-friendly layout.
- FR-8.7 Totals shown on the client are previews only; **the server is the source of truth**. The page renders the totals returned by the API.
- FR-8.8 Stack: Blade + plain JS (or Alpine.js) + a light CSS framework. No SPA build is required.

### FR-9 Supporting endpoints (P1)
| Endpoint | Purpose |
|---|---|
| `GET /api/products` | Product list for the dropdown (id, code, name, price, tax %, stock) |
| `GET /api/customers/lookup?email=` | Name auto-fill; `404` if not found |
| `GET /orders/{order_number}` (web) | Bill view |
| `GET /orders/{order_number}/pdf` (web, P2) | Download the bill PDF |

### FR-10 Stock ledger (P2)
Every stock change writes a row to `stock_movements`, giving an audit trail (§5).

## 5. Data Model

All money is held in `DECIMAL(12,2)`; calculations use integer paise internally (§6). Stock is an `UNSIGNED INT`, so the database itself rejects negative stock as a last line of defence.

### `products`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| code | varchar(32) | **unique**, uppercase SKU, e.g. `COL-100` |
| name | varchar(150) | |
| price | decimal(12,2) | ≥ 0, price per unit, tax-exclusive |
| tax_percent | decimal(5,2) | 0–100 |
| stock_on_hand | int unsigned | default 0, **indexed** (for the low-stock query) |
| timestamps | | |

### `customers`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar(150) | |
| email | varchar(191) | **unique**, stored lowercased |
| timestamps | | |

### `orders`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_number | varchar(32) | **unique**, human-readable `ORD-{Ymd}-{id:06}`, e.g. `ORD-20261028-000042`. Set from the id right after insert, inside the same transaction, so it is nullable at DB level only. |
| customer_id | FK → customers | `restrictOnDelete`, indexed |
| subtotal | decimal(12,2) | Σ line subtotals |
| tax_total | decimal(12,2) | Σ line taxes |
| grand_total | decimal(12,2) | subtotal + tax_total |
| amount_tendered | decimal(12,2) null | P1, cash given |
| change_due | decimal(12,2) null | P1 |
| status | varchar(20) | `confirmed` (the only status in scope; the column is there for later use) |
| placed_at | timestamp | indexed with customer_id, for history ordering |
| timestamps | | |

### `order_items`
Price, tax and name are **snapshotted** here, so later catalog changes never alter past bills.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | FK → orders | `cascadeOnDelete` |
| product_id | FK → products | `restrictOnDelete` |
| product_code | varchar(32) | snapshot |
| product_name | varchar(150) | snapshot |
| unit_price | decimal(12,2) | snapshot |
| tax_percent | decimal(5,2) | snapshot |
| quantity | int unsigned | ≥ 1 |
| line_subtotal | decimal(12,2) | unit_price × qty |
| line_tax | decimal(12,2) | |
| line_total | decimal(12,2) | line_subtotal + line_tax |
| | | **unique (order_id, product_id)** — the same product appears once per order |

### `stock_movements` (P2, supporting/audit)
| Column | Type | Notes |
|---|---|---|
| id, product_id (FK), order_id (FK, null) | | |
| change | int | negative for a sale |
| balance_after | int unsigned | |
| reason | varchar(30) | `sale`, `seed`, `adjustment` |
| created_at | | |

### Framework tables
`jobs`, `job_batches`, `failed_jobs`, `cache`, `sessions` (Laravel 12 defaults).

### Eloquent relationships
- `Customer hasMany Order`
- `Order belongsTo Customer`; `Order hasMany OrderItem`
- `OrderItem belongsTo Order`; `OrderItem belongsTo Product`
- `Product hasMany OrderItem`; `Product hasMany StockMovement`
- Money columns use `decimal:2` casts. The order route key is `order_number`.

## 6. Business Rules

### 6.1 Totals
- Prices are **tax-exclusive**.
- Per line: `line_subtotal = unit_price × qty`; `line_tax = round_half_up(line_subtotal × tax_percent / 100, 2)`; `line_total = line_subtotal + line_tax`.
- Order: `subtotal = Σ line_subtotal`, `tax_total = Σ line_tax`, `grand_total = subtotal + tax_total`.
- Calculations are done in **integer paise** to avoid float drift, then converted back to decimals for storage. Tax is rounded per line, so the order totals always equal the sum of the lines on the bill.
- Currency is INR (₹), as in the wireframe.

### 6.2 Customer resolution
- The email is trimmed and lowercased before lookup and storage.
- If the email exists, the existing customer is used and **their stored name is not overwritten** by the request.
- If the email is new, `name` is required and a customer is created inside the same transaction. A unique-email race is handled with `firstOrCreate` and a retry on duplicate key.

### 6.3 Payment & change (P1)
- `amount_tendered` is optional. When given, it must be ≥ `grand_total`.
- `change_due = amount_tendered − grand_total`.
- **Change breakdown** (shown in the wireframe): `change_due` is rounded to the nearest rupee for cash, then split greedily into INR denominations `[500, 200, 100, 50, 20, 10, 5, 2, 1]`. For example, ₹22.80 rounds to ₹23, which is 1×20 + 1×2 + 1×1. This matches the wireframe.

### 6.4 Line items
- Each line needs a `quantity` that is an integer from 1 to 1000.
- If the same `product_id` appears more than once, its quantities are **merged** before the stock check. The merged quantity is validated against stock.
- An order may have 1–50 lines.

### 6.5 Low stock
- "Below threshold" means a strict `<`. With threshold 10, a product with 9 units is listed and one with 10 is not.

## 7. API Specification

All endpoints are under `/api`, use JSON and the default `throttle:api`, and return Laravel's standard validation error envelope.

### 7.1 `POST /api/orders`

**Request**
```json
{
  "customer": { "email": "thomas@example.com", "name": "Thomas" },
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 2, "quantity": 5 }
  ],
  "amount_tendered": 200.00
}
```

**Validation (`StoreOrderRequest`)**
| Field | Rules |
|---|---|
| customer.email | required, email:rfc, max:191 |
| customer.name | required_without existing customer · string · max:150 (checked in `after()` hook) |
| items | required, array, min:1, max:50 |
| items.*.product_id | required, integer, exists:products,id |
| items.*.quantity | required, integer, min:1, max:1000 |
| amount_tendered | nullable, numeric, min:0, ≥ computed grand total (checked in service) |

`prepareForValidation()` normalizes the email. Duplicate product lines are merged by `OrderData::fromArray()`, keeping the index of the first occurrence for error keys. The `after()` hook does the new-customer name check and the advisory stock pre-check.

**201 Created**
```json
{
  "data": {
    "order_number": "ORD-20261028-000042",
    "status": "confirmed",
    "placed_at": "2026-10-28T10:15:00+05:30",
    "customer": { "id": 7, "name": "Thomas", "email": "thomas@example.com" },
    "items": [
      { "product_id": 1, "code": "COL-100", "name": "Colgate Toothpaste",
        "unit_price": "50.00", "tax_percent": "8.00", "quantity": 2,
        "line_subtotal": "100.00", "line_tax": "8.00", "line_total": "108.00" },
      { "product_id": 2, "code": "PAR-010", "name": "Parle-G Biscuit",
        "unit_price": "10.00", "tax_percent": "8.00", "quantity": 5,
        "line_subtotal": "50.00", "line_tax": "4.00", "line_total": "54.00" }
    ],
    "subtotal": "150.00",
    "tax_total": "12.00",
    "grand_total": "162.00",
    "amount_tendered": "200.00",
    "change_due": "38.00",
    "change_breakdown": [ { "denomination": 20, "count": 1 }, { "denomination": 10, "count": 1 },
                          { "denomination": 5, "count": 1 },  { "denomination": 2, "count": 1 },
                          { "denomination": 1, "count": 1 } ]
  }
}
```
Money is serialized as **strings**, so precision is not lost in JSON.

**422 Insufficient stock** (the `errors` shape is the same whether the pre-check or the locked check catches it)
```json
{
  "message": "Insufficient stock for one or more items.",
  "errors": {
    "items.1.quantity": ["Only 1 unit of Parle-G Biscuit (PAR-010) available; 5 requested."]
  }
}
```
When the FormRequest pre-check catches it, `message` is Laravel's standard summary (the first error, plus "and N more errors"). Clients should read `errors`.

**Other errors:** `422` for validation errors, and `503` if the lock wait times out after retries ("Please retry").

### 7.2 `GET /api/customers/{email}/orders`
- `{email}` is URL-encoded and constrained by `where('email', '[^/]+')`.
- `200`: `{ "data": [ OrderResource... ], "customer": {...}, "links": {...}, "meta": {...} }`. This is the standard Laravel paginated resource collection, with the customer added alongside.
- `404`: `{ "message": "Customer not found." }`.
- Lines are eager-loaded (`with('items')`) to avoid N+1 queries.

### 7.3 `GET /api/products/low-stock?threshold=10`
- `200`: `{ "data": [ {id, code, name, stock_on_hand} ... ], "meta": { "threshold": 10 } }`.
- `422` if `threshold` is not a non-negative integer.

### 7.4 Supporting
- `GET /api/products` returns `ProductResource` collection.
- `GET /api/customers/lookup?email=` returns `200 {data: {name, email}}`, or `404`.

## 8. Concurrency Design (no overselling)

**Approach: a pessimistic row lock inside one transaction.**

```php
DB::transaction(function () use ($data) {
    $products = Product::whereIn('id', $ids)
        ->orderBy('id')          // consistent lock order prevents deadlocks
        ->lockForUpdate()        // SELECT ... FOR UPDATE
        ->get()->keyBy('id');

    // re-check stock against the locked rows → throw InsufficientStockException
    // decrement stock, create customer (if new), order, order_items, stock_movements
}, attempts: 3);             // retry on deadlock
```

- Rows are locked in **ascending `id` order**, so two orders sharing products cannot deadlock each other.
- The check and the deduction run against **locked** rows. The FormRequest stock check gives fast feedback but is not trusted.
- `InsufficientStockException` is thrown inside the closure, which rolls the transaction back. The exception's own `render()` method turns it into a `422`, and its `report()` method logs it as a warning. A deadlock or lock-wait timeout that survives the retries is rendered as `503` in `bootstrap/app.php` → `withExceptions`.
- A new customer is created inside a savepoint. If two first-time orders race on the same email, the loser catches the unique-key violation and re-reads the winner's row with a locking read.
- Safety nets: the `UNSIGNED` stock column, and decrementing via `UPDATE products SET stock_on_hand = stock_on_hand - ? WHERE id = ? AND stock_on_hand >= ?`, which asserts 1 affected row.
- The job is dispatched with `afterCommit()`, so a rolled-back order never sends an email.
- **Requires InnoDB (MySQL/MariaDB).** SQLite ignores `FOR UPDATE`. The fast test suite may use SQLite, but the concurrency test runs on MySQL (§10.3).

*Alternative considered:* an optimistic conditional `UPDATE … WHERE stock >= qty` with no `SELECT FOR UPDATE`. It is just as safe and needs fewer locks. It was not chosen as the primary approach because a pessimistic lock reads more clearly in review and gives exact per-line error messages. The conditional `UPDATE` is still kept as the safety net.

## 9. Architecture (the Laravel way)

```
app/
├── Http/
│   ├── Controllers/Api/{OrderController, CustomerOrderController, ProductController, CustomerLookupController}
│   ├── Controllers/Web/{CounterController, BillController}
│   ├── Requests/{StoreOrderRequest, LowStockRequest, CustomerOrdersRequest}
│   └── Resources/{OrderResource, OrderItemResource, CustomerResource, ProductResource}
├── Actions/CreateOrderAction.php          # transaction + locking + persistence
├── Services/
│   ├── OrderCalculator.php               # pure: lines → totals (paise math)
│   └── ChangeCalculator.php              # pure: change → denominations
├── DTOs/{OrderData, OrderLineData, LineAmounts, OrderTotals}.php   # readonly value objects
├── Exceptions/InsufficientStockException.php
├── Jobs/SendOrderConfirmation.php         # ShouldQueue, dispatched afterCommit
├── Mail/OrderConfirmationMail.php         # markdown mailable + PDF attachment
├── Support/{Money, OrderNumber}.php
├── Console/Commands/{PlaceOrderCommand, RaceOrdersCommand}.php   # orders:place, orders:race
└── Models/{Product, Customer, Order, OrderItem, StockMovement}
config/inventory.php                       # low_stock_threshold, max_lines, max_quantity, attach_pdf_bill
database/{migrations, factories, seeders}
resources/views/{counter, bills/show, bills/pdf, mail/orders/confirmation}.blade.php
tests/{Unit, Feature, Concurrency}
```

- Controllers stay thin: validate (FormRequest), call the Action, return a Resource.
- Calculators are pure classes with no DB access, so they are unit-testable.
- No repository layer; Eloquent is used directly in the Action, which avoids over-engineering at this scale.

## 10. Testing Strategy

Framework: **Pest** (PHPUnit-compatible). It uses `RefreshDatabase`, `Queue::fake()`, `Mail::fake()` and model factories (states `lowStock()` and `outOfStock()`).

### 10.1 Feature — create order
| # | Scenario | Expectation |
|---|---|---|
| 1 | Happy path, 2 lines, existing customer | 201; totals correct; stock decremented; 1 order + 2 items; job pushed |
| 2 | New email + name | Customer created |
| 3 | Existing email, different name | Customer reused, name unchanged |
| 4 | **Qty > stock** | 422 `items.N.quantity`; stock unchanged; no order; no job |
| 5 | **Multi-line, one line short** | Whole order rolled back; other lines' stock unchanged |
| 6 | Qty == stock exactly | 201; stock becomes 0 |
| 7 | Duplicate product_id lines whose total exceeds stock | 422 |
| 8 | Product out of stock (0) | 422 |
| 9 | Unknown product_id / qty 0 / negative / decimal / empty items / >50 lines | 422 |
| 10 | New email without name | 422 `customer.name` |
| 11 | Invalid email | 422 |
| 12 | `amount_tendered` < grand total | 422; nothing persisted |
| 13 | Price snapshot: change product price after order | Past order totals unchanged |
| 14 | Email case-insensitivity (`Thomas@Example.com`) | Same customer |
| 15 | **Stale pre-check**: stock reduced between validation and Action (simulated) | Action throws; 422; no oversell |

### 10.2 Feature — other endpoints
- History: newest first, items included, pagination, mixed-case email, unknown → 404, other customers' orders excluded.
- Low stock: default from config, `?threshold` override, strict `<` boundary, sorted, invalid threshold → 422.
- Lookup: found → name; not found → 404.
- Job: `Queue::fake()` asserts it is pushed with the right order. Running `handle()` with `Mail::fake()` asserts the mail is sent to the customer. A rolled-back order dispatches nothing.

### 10.3 Concurrency
- `tests/Concurrency/ConcurrentOrdersTest.php` is its own test suite. It needs MySQL/MariaDB and is skipped on SQLite. Run it with `composer test:mysql`.
  - The data is committed for real (`migrate:fresh` around each test), so the other processes can see it.
  - `php artisan orders:race {product} --processes=N` uses `Process::pool()` to launch N `orders:place` processes. Each process boots, waits for a shared start instant, then calls `CreateOrderAction` on its own DB connection.
  - Cases: 1 unit with 5 buyers gives exactly 1 created and 4 rejected; 3 units with 8 buyers gives exactly 3 created; 4 first-time orders from the same new email create exactly 1 customer.
  - **Verified to fail without the protections.** With `lockForUpdate()` and the conditional decrement removed, the two stock tests fail.
- `orders:race` doubles as the demo for the screen recording.

### 10.4 Unit
- `OrderCalculator`: rounding (e.g. ₹33.33 × 3 @ 18%, the half-up boundary), 0% tax, fractional rates, multi-line sums, float drift.
- `ChangeCalculator`: ₹22.80 gives [20, 2, 1]; ₹0 gives []; the 49/50-paise rounding boundary; every denomination.
- `Money` / `OrderNumber` / `OrderData`: paise conversion, formatting, order-number format, duplicate-line merging.
- Architecture rules (Pest arch): no debug calls or insecure functions; controllers don't use `DB`; services are pure; DTOs are final and readonly; jobs are queued; requests extend FormRequest.

**Target:** all P0 paths covered. `php artisan test` (SQLite) and `composer test:mysql` (MariaDB, including concurrency) are both green.

## 11. Seed Data

- **Products (~20)**, including the wireframe items: Colgate Toothpaste ₹50 / 8%, Parle-G Biscuit ₹10 / 8%, Bread (4 left), Milk 1L (9 left), Eggs (12) (2 left), plus one with 0 stock and a mix of 0%, 5%, 12% and 18% tax rates.
- **Customers (~10)**, including `thomas@example.com`.
- **Orders (optional, ~5)** for the seeded customers, so the history endpoint returns data straight away. They are created through `CreateOrderAction`, so stock stays consistent.
- `php artisan migrate:fresh --seed` rebuilds everything.

## 12. Non-Functional Requirements

| Area | Requirement |
|---|---|
| Correctness | No negative stock and no partial orders, under any interleaving |
| Performance | Order creation < 200 ms p95 locally (≤ 50 lines); history uses eager loading and indexes |
| Reliability | Job retries 3×; failures logged to `failed_jobs`; API unaffected by mail failure |
| Security | Mass-assignment guarded (`$fillable`); all input validated; Eloquent/bindings only (no raw interpolation); CSRF on web routes; API throttled |
| Observability | Structured logs for order created, stock rejected and mail sent |
| Code quality | PSR-12 via Laravel Pint; strict types; typed properties and return types |
| Portability | Runs on XAMPP (PHP 8.2 + MariaDB) and any MySQL 8 host |

## 13. Assumptions (to be copied into README)

1. There is no authentication; the app is an internal counter tool.
2. Prices are tax-exclusive. Tax is per product, computed and rounded half-up per line. Currency is INR.
3. An existing customer's name is **not** overwritten by the name in an order request.
4. Duplicate product lines in one request are merged.
5. "Below threshold" means a strict `<`. The default of 10 is configurable through env and can be overridden per request.
6. An unknown email in the history endpoint returns `404`, not an empty list.
7. Insufficient stock returns `422`, with the same error shape as validation errors, so clients handle one format.
8. All orders are cash orders marked `confirmed`. `amount_tendered` is optional, and the change breakdown rounds to the nearest rupee.
9. The sample numbers in the wireframe are illustrative. Its subtotal (₹210) doesn't match the visible rows (₹150), so totals always come from the data. The wireframe does show line totals that include tax (2 × ₹50 → ₹108 ⇒ 8%), and that convention is followed.
10. The email is simulated with the `log` mailer. The PDF attachment is P1.
11. The order history is paginated (15 per page).

## 14. Deliverables

- [ ] Private GitHub repo with the full Laravel app, shared with the reviewers.
- [ ] `README.md` containing: requirements, setup (`composer install`, `.env`, `key:generate`, `migrate --seed`, `queue:work`, `serve`), API examples (curl/Postman collection), how to run the tests (including the concurrency group), assumptions (§13) and design decisions (§8, §9).
- [ ] `/prompts` folder with screenshots of the AI prompts used, or the screenshots embedded in the README.
- [ ] A 5–10 min **narrated** screen recording covering the UI flow, the insufficient-stock error, the queue worker processing the job (and `laravel.log` showing the mail), the race demo, and a test run.
- [ ] (Optional) A Postman/Bruno collection in `/docs`.

## 15. Delivery Plan (3 days)

| Day | Work |
|---|---|
| **Day 1** | Laravel 12 install, MySQL config, migrations, models and relationships, factories and seeders, `OrderCalculator` with unit tests, `CreateOrderAction` with locking, `POST /api/orders` with FormRequest and Resource, create-order feature tests |
| **Day 2** | History, low-stock, products and lookup endpoints with tests; queued job and mailable; concurrency test and race demo command; exception rendering |
| **Day 3** | Blade counter UI and bill view (and the PDF, P1); change calculator; Pint; README and assumptions; prompt screenshots; recording; final review |

## 16. Acceptance Criteria

- [ ] `php artisan migrate:fresh --seed` succeeds on a clean DB.
- [ ] `POST /api/orders` with valid data returns 201, with correct totals and reduced stock.
- [ ] Ordering more than the available stock returns 422 and leaves the DB unchanged.
- [ ] Two concurrent orders for the last unit produce exactly 1 success, and final stock is 0.
- [ ] `GET /api/customers/{email}/orders` returns that customer's orders, newest first.
- [ ] `GET /api/products/low-stock` respects the env threshold and the query override.
- [ ] Creating an order pushes a job. Running `queue:work` writes the confirmation mail to `storage/logs/laravel.log`.
- [ ] `php artisan test` passes, covering at least the scenarios in §10.1 #1, #4, #5 and #15.
- [ ] Controllers contain no business logic; the logic lives in the Action and Service classes.
- [ ] The README documents setup, assumptions and design decisions.
