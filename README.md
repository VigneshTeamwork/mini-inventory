# Store Billing — Order & Inventory Mini-System

A Laravel 12 app for a retail counter. It records customer orders against a product catalog and keeps stock correct, even when orders arrive at the same moment.

- **JSON API:** create an order, get a customer's order history, list low-stock products.
- **Queued confirmation email:** sent with the bill as a PDF, through the `log` mailer, so no SMTP server is needed.
- **No overselling:** row locks plus a conditional decrement. This is proven by a multi-process race test.
- **Counter screen:** follows the [wireframe](docs/wireframe.png) (Blade and plain JS, no build step).
- **Tests:** 116 Pest tests, covering unit, feature, architecture and real concurrency.

The full requirements and design notes are in [prd.md](prd.md).

---

## 1. Requirements

| | Version used |
|---|---|
| PHP | 8.2+ (tested on 8.2.12), with extensions `pdo_mysql`, `mbstring`, `gd` and `dom` |
| Composer | 2.x |
| Database | MySQL 8 or MariaDB 10.4+ (tested on MariaDB 10.4.32 from XAMPP) |
| Node | **Not required.** No front-end build step. |

## 2. Setup

```bash
git clone <repo-url> store-billing && cd store-billing
composer install
cp .env.example .env
php artisan key:generate
```

Create the databases. The second one is only needed for the MySQL test run.

```sql
CREATE DATABASE store_billing      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE store_billing_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`.env.example` already targets `root@127.0.0.1:3306` with an empty password (the XAMPP default). Adjust the `DB_*` values if yours differ. Then run:

```bash
php artisan migrate:fresh --seed      # schema + 20 products, 10 customers, 3 sample orders
php artisan queue:work                # terminal 1: processes confirmation emails
php artisan serve                     # terminal 2: http://127.0.0.1:8000
```

On XAMPP, prefix the commands with `C:\xampp\php\php.exe` if `php` is not on your PATH.

### Configuration

| Key | Default | Purpose |
|---|---|---|
| `LOW_STOCK_THRESHOLD` | `10` | Products with stock **strictly below** this are "low" |
| `BILL_PDF_ATTACHMENT` | `true` | Attach the PDF bill to the confirmation email |
| `QUEUE_CONNECTION` | `database` | Jobs go to the `jobs` table |
| `MAIL_MAILER` | `log` | Emails are written to `storage/logs/laravel.log` |
| `APP_TIMEZONE` | `Asia/Kolkata` | Timestamps on bills |

## 3. Using it

Open **http://127.0.0.1:8000**.

1. Type an email. Try `thomas@example.com`, a seeded customer: the name fills in automatically. A new email asks for a name.
2. Pick products and quantities. Line totals, tax and the grand total update as you go.
3. Optionally enter **Amount Given by Customer**. The page shows the balance and its breakdown into notes and coins.
4. Click **Generate Bill**. The bill page opens with print and PDF download buttons, and a confirmation job is queued.
5. Try ordering 3 × **Eggs (12)** (only 2 in stock). The error appears on that line and nothing is saved.

The **Low Stock Alert** panel lists products below the threshold and refreshes after each attempt.

## 4. API

All endpoints are under `/api` and return JSON, even if the client doesn't send `Accept: application/json`. Money values are 2-decimal **strings**.

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/api/orders` | Create an order |
| `GET` | `/api/customers/{email}/orders` | Order history, newest first, paginated (`?per_page=1..50`, default 15) |
| `GET` | `/api/products/low-stock` | Products below the threshold (`?threshold=N` overrides the config) |
| `GET` | `/api/products` | Catalog (used by the counter dropdown) |
| `GET` | `/api/customers/lookup?email=` | Name auto-fill; `404` if the customer is new |

### Create an order

```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
        "customer": { "email": "thomas@example.com", "name": "Thomas" },
        "items": [ { "product_id": 1, "quantity": 2 }, { "product_id": 2, "quantity": 5 } ],
        "amount_tendered": 200
      }'
```

`201 Created`:

```json
{
  "data": {
    "order_number": "ORD-20261028-000004",
    "status": "confirmed",
    "placed_at": "2026-10-28T13:51:30+05:30",
    "customer": { "id": 1, "name": "Thomas", "email": "thomas@example.com" },
    "items": [
      { "product_id": 1, "code": "COL-100", "name": "Colgate Toothpaste", "unit_price": "50.00", "tax_percent": "8.00",
        "quantity": 2, "line_subtotal": "100.00", "line_tax": "8.00", "line_total": "108.00" },
      { "product_id": 2, "code": "PAR-010", "name": "Parle-G Biscuit", "unit_price": "10.00", "tax_percent": "8.00",
        "quantity": 5, "line_subtotal": "50.00", "line_tax": "4.00", "line_total": "54.00" }
    ],
    "subtotal": "150.00", "tax_total": "12.00", "grand_total": "162.00",
    "amount_tendered": "200.00", "change_due": "38.00",
    "change_breakdown": [ {"denomination": 20, "count": 1}, {"denomination": 10, "count": 1},
                          {"denomination": 5, "count": 1},  {"denomination": 2, "count": 1}, {"denomination": 1, "count": 1} ],
    "bill_url": "http://127.0.0.1:8000/orders/ORD-20261028-000004"
  }
}
```

`422` when stock is short. The error is keyed to the offending line:

```json
{
  "message": "Only 2 units of Eggs (12) (EGG-012) available; 3 requested.",
  "errors": { "items.0.quantity": ["Only 2 units of Eggs (12) (EGG-012) available; 3 requested."] }
}
```

The other responses are `422` for validation errors, `404` for an unknown customer in the history endpoint, and `503` if a lock wait still times out after 3 transaction retries.

### History and low stock

```bash
curl "http://127.0.0.1:8000/api/customers/thomas%40example.com/orders?per_page=5"
curl "http://127.0.0.1:8000/api/products/low-stock"
curl "http://127.0.0.1:8000/api/products/low-stock?threshold=20"
```

## 5. Queue and confirmation email

`CreateOrderAction` dispatches `SendOrderConfirmation` with `->afterCommit()`, so the job is only queued once the order is committed. A rolled-back order never sends an email. With `php artisan queue:work` running, each job:

- sends `OrderConfirmationMail` (a markdown email with the PDF bill attached) through the `log` mailer;
- writes `order.confirmation_sent {order_number, email, grand_total}` to `storage/logs/laravel.log`.

The job has `tries = 3` and a backoff of 10s, 30s and 60s. Failures go to `failed_jobs` (`php artisan queue:failed`). The API never waits for the job.

## 6. Concurrency (no overselling)

All of this happens inside `CreateOrderAction::execute()`, in one `DB::transaction(..., attempts: 3)`:

1. `SELECT … FOR UPDATE` locks the product rows **in ascending id order**. Two orders for the same product are serialized, and orders that share products can't deadlock.
2. Stock is re-checked against the **locked** rows. The FormRequest check is only fast feedback. A test simulates stock changing between validation and the lock.
3. Stock is decremented with `UPDATE … SET stock = stock - ? WHERE id = ? AND stock >= ?`, and the action asserts that exactly 1 row changed. This is a second safety net, and the column is `UNSIGNED` as a third.
4. The customer, order, lines and stock ledger rows are written. Any exception rolls back all of it.

**See it happen:** the seeded "Instant Coffee 100g" (id 20) has 1 unit left.

```bash
php artisan orders:race 20 --processes=5
```

Example output (which process wins varies from run to run):

```
Instant Coffee 100g (COF-100): 1 in stock. Launching 5 parallel orders of 1...
| 1 | rejected | {"status":"rejected","message":"Instant Coffee 100g (COF-100) is out of stock; 1 requested."} |
| 2 | created  | {"status":"created","order_number":"ORD-20261028-000004"}                                    |
...
1 created, 4 rejected, 0 errors. Final stock: 0.
```

`orders:race` starts N separate PHP processes (`orders:place`). Each one boots and then waits for a shared start instant, so the orders really do hit the database at the same moment.

## 7. Tests

```bash
php artisan test         # fast: SQLite in memory; the 3 concurrency tests are skipped
composer test:mysql      # full: runs everything against store_billing_test, including concurrency
```

Both runs pass: **116 tests, 455 assertions** on MariaDB.

| Suite | What it covers |
|---|---|
| `tests/Unit` | Paise maths and half-up tax rounding (boundary cases, float drift), change breakdown (₹22.80 → 20 + 2 + 1, rounding boundary), order-number format, duplicate-line merging; **architecture rules** (controllers don't use `DB`, services are pure, DTOs are final and readonly, jobs are queued, no debug or insecure calls) |
| `tests/Feature/Orders` | Happy path with exact totals, stock, ledger and queued job; new vs returning customer (name not overwritten, case-insensitive email); duplicate lines; exact-stock order; price snapshot; **insufficient stock**; one short line rejects the whole order; out of stock; combined duplicate quantity over stock; **stale pre-check caught under the lock**; **mid-transaction failure rolls back stock, customer and order**; **job only queued after commit**; cash below total; 20 malformed-payload cases |
| `tests/Feature/*` | History (ordering, isolation, pagination, `+`/case in emails, 404), low stock (strict `<`, config default, per-request override, invalid input), lookup, job and mail (retries, log mailer end to end, PDF attachment toggle), counter, bill and PDF pages |
| `tests/Concurrency` | Real parallel processes on MariaDB: 1 unit with 5 buyers gives exactly 1 sale; 3 units with 8 buyers gives exactly 3; 4 first orders from one new email give 1 customer |

**The race tests were checked against the bug they guard.** With `lockForUpdate()` and the conditional `WHERE stock >= ?` temporarily removed, both stock race tests fail. With them back, they pass.

Code style is checked with `vendor/bin/pint`.

## 8. Design decisions

- **Thin controllers.** Each controller validates (FormRequest), calls one action, and returns an API Resource. All write logic lives in `App\Actions\CreateOrderAction`. Pricing rules are pure classes (`OrderCalculator`, `ChangeCalculator`) with no database access, so they are unit-tested in isolation.
- **Money in integer paise.** Values are stored as `DECIMAL(12,2)`, calculated as integers, and serialized as strings. Tax is rounded half-up **per line**, so the bill's lines always add up to its totals.
- **Snapshots on order lines.** Each line stores the product code, name, price and tax % at the time of sale, so later catalog edits never change past bills.
- **Supporting tables.** `stock_movements` is an append-only stock ledger with seed and sale rows; `jobs` and `failed_jobs` back the queue.
- **One error format.** Insufficient stock uses the same `{message, errors}` envelope as validation errors, keyed to `items.N.quantity`, so the UI can show the error on the right row.
- **Strict Eloquent outside production** (`Model::shouldBeStrict()`), so N+1 queries and mass-assignment mistakes fail loudly in tests.
- **No SPA build.** The counter is Blade plus about 200 lines of plain JS, so the project runs with Composer only. Totals shown in the browser are previews; the server's numbers are what get saved.

```
app/
├── Actions/CreateOrderAction.php        transaction, locks, stock deduction, persistence
├── Services/{OrderCalculator, ChangeCalculator}.php
├── DTOs/{OrderData, OrderLineData, LineAmounts, OrderTotals}.php
├── Http/Controllers/{Api, Web}/  Http/Requests/  Http/Resources/
├── Jobs/SendOrderConfirmation.php   Mail/OrderConfirmationMail.php
├── Exceptions/InsufficientStockException.php
├── Console/Commands/{PlaceOrderCommand, RaceOrdersCommand}.php
└── Models/{Product, Customer, Order, OrderItem, StockMovement}.php
```

## 9. Assumptions

The brief leaves these points open. Each is a decision I made and can change:

1. **No authentication.** It's an internal counter tool. The API is rate-limited to 120 requests per minute per IP.
2. **Prices are tax-exclusive.** Tax is a per-product percentage, rounded half-up per line. The currency is INR (₹), as in the wireframe.
3. **Returning customers keep their stored name.** The name in the request is ignored once the email exists, and is required only for a new email. Emails are trimmed and lowercased.
4. **Repeated product lines are merged.** Stock is checked against the combined quantity, and errors point at the first occurrence.
5. **"Below the threshold" means a strict `<`.** With the default of 10, a product with 10 units is not listed. The threshold can be overridden per request.
6. **An unknown email returns `404`** in the history endpoint; a known customer with no orders gets an empty list.
7. **Insufficient stock returns `422`** with validation-style errors, rather than `409`, so clients handle one format.
8. **Every order is a cash sale with status `confirmed`.** `amount_tendered` is optional and, if given, must be at least the grand total. Change is rounded to the nearest rupee for the denomination breakdown (₹22.80 → ₹23 = 20 + 2 + 1, matching the wireframe).
9. **The wireframe's numbers are illustrative.** Its subtotal (₹210) doesn't match the rows shown (₹150). I followed its conventions (line totals include tax) but all totals come from the data.
10. **Email is simulated** with the `log` mailer. "Emails PDF to customer" from the wireframe is implemented as a PDF attachment (dompdf), which can be turned off with `BILL_PDF_ATTACHMENT=false`.
11. **The order number** is `ORD-{date}-{id}`. It is readable and unique, but it is sequential, so it reveals order volume; that's fine for an internal tool.
12. **Quantity limits:** each line takes 1–1000 units, and an order takes 1–50 lines.

## 10. Known limitations and next steps

- Locking needs InnoDB. SQLite ignores `FOR UPDATE`, which is why the concurrency suite only runs on MySQL/MariaDB.
- There is no product or customer CRUD UI, and no order cancellation or restocking flow. The `stock_movements` ledger is designed to support them.
- The counter UI has no automated browser test; the Blade pages are covered by HTTP feature tests. A Dusk or Playwright test would be the next addition.
- A high-traffic deployment would use Redis for the queue and cache, run Horizon, and have the history endpoint read from a replica.

## 11. Submission notes

- **AI prompt log:** screenshots of the prompts used are in [`/prompts`](prompts/).
- **Walkthrough video:** _link to be added_.
