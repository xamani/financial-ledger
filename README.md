# Financial Ledger (Laravel)

یک API نمونه برای پیاده‌سازی «دفترکل مالی (Ledger)» که روی سفارش‌ها (Orders)، کیف‌پول‌ها (Wallets) و تراکنش‌ها (Transactions) می‌نشیند و امکان گزارش‌گیری تجمیعی و نموداری از جریان‌های مالی را فراهم می‌کند.

این پروژه شامل یک جریان پرداخت Mock هم هست: سفارش ساخته می‌شود، یک لینک پرداخت نمایشی برمی‌گردد، و با ارسال callback بانک (success/failed) تراکنش‌های دفترکل ایجاد می‌شوند.

## قابلیت‌ها

- ساخت سفارش `pending` و تکمیل پرداخت با callback (ایمن از نظر همزمانی با `lockForUpdate`)
- ثبت دفترکل تراکنش‌ها با `flow` (ورودی/خروجی) و `type` (مثل `platform_commission`, `post_cost`, ...)
- ساخت و به‌روزرسانی کیف‌پول‌های سیستمی (بر اساس `slug`) و کیف‌پول کاربر
- برداشت از کیف‌پول (ثبت تراکنش `withdrawal` با `flow=out`)
- گزارش تجمیعی بین بازه تاریخ و گزارش نموداری (day/month) به‌صورت سری زمانی
- مستندات OpenAPI/Swagger با `darkaonline/l5-swagger`

## پشتهٔ فنی

- PHP `^8.2` + Laravel `^12`
- دیتابیس: SQLite (پیش‌فرض در `.env.example`) یا MySQL (کانتینر در `docker-compose.yml`)
- Vite + Tailwind (برای فرانت/asset pipeline؛ پروژه تمرکز اصلی‌اش API است)

## اجرای سریع (بدون Docker)

پیش‌نیاز: PHP 8.2، Composer، Node.js + npm

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
php artisan serve
```

اگر می‌خواهید همه‌چیز را با یک دستور بالا بیاورید، اسکریپت `composer dev` هم وجود دارد:

```bash
composer run dev
```

## اجرای سریع (Docker)

پیش‌نیاز: Docker + Docker Compose

نکته: برای اجرا با Docker باید دیتابیس روی MySQL باشد (در `.env` تنظیم شده). اگر از `.env.example` کپی می‌کنید، مقدار `DB_CONNECTION` را به `mysql` تغییر دهید و `DB_HOST=db` را ست کنید.

```bash
make build
make up
make composer-install
make key
make fresh-db
```

- اپلیکیشن: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081` (رمز `root`)

## مستندات API (Swagger)

- UI: `http://localhost:8080/api/documentation`
- خروجی JSON: `public/api-docs/api-docs.json`

در صورت نیاز به تولید مجدد:

```bash
php artisan l5-swagger:generate
```

```bash
make artisan-l5-swagger:generate    
```

## مسیرهای اصلی API

پایهٔ همهٔ مسیرها: `/api`

- Orders
  - `POST /orders` ساخت سفارش `pending`
  - `GET /orders/{order}` نمایش یک سفارش
  - `POST /orders/{order}/pay` دریافت لینک پرداخت Mock
  - `POST /orders/callback` callback بانک (موفق/ناموفق)
- Reports
  - `GET /financial-reports?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`
  - `GET /financial-reports/chart?start_date=...&end_date=...&granularity=day|month`
- Transactions
  - `GET /transactions` (فیلترها: `wallet_id`, `order_id`, `type`, `flow`, `start_date`, `end_date`, `per_page`)
  - `GET /transactions/{transaction}` نمایش یک تراکنش
- Wallets
  - `POST /wallets/withdraw` برداشت از کیف‌پول

## صفحات UI (برای تست API)

برای تست سریع endpointها یک UI ساده هم اضافه شده که درخواست‌ها را به صورت مستقیم به `/api/*` ارسال می‌کند.

نکته: این پنل با کمک ابزارهای هوش مصنوعی (AI) توسعه یافته است.

- صفحهٔ اصلی + منو: `GET /ui`
- Orders:
  - `GET /ui/orders/create` → `POST /api/orders`
  - `GET /ui/orders/{order}` → اکشن‌های `POST /api/orders/{order}/pay` و `POST /api/orders/callback`
- Transactions:
  - `GET /ui/transactions` → `GET /api/transactions`
  - `GET /ui/transactions/{transaction}` → `GET /api/transactions/{transaction}`
- Reports:
  - `GET /ui/reports/summary` → `GET /api/financial-reports`
  - `GET /ui/reports/chart` → `GET /api/financial-reports/chart`
- Wallets:
  - `GET /ui/wallets/withdraw` → `POST /api/wallets/withdraw`

## تنظیم درصدهای تقسیم مبلغ (Breakdown)

محاسبهٔ سهم‌ها از طریق `App\\Services\\FinancialCalculatorService` انجام می‌شود و با env قابل تنظیم است:

- `FIN_PLATFORM_COMMISSION_PERCENT` (پیش‌فرض: 15)
- `FIN_POST_COST_PERCENT` (پیش‌فرض: 30)
- `FIN_TEMPORARY_WALLET_PERCENT` (پیش‌فرض: 5)
- `FIN_INSURANCE_PERCENT` (پیش‌فرض: 5)

نکته: تمام مبالغ به‌صورت عدد صحیح (minor unit) و بدون اعشار مدیریت می‌شوند (`decimal:0`).

## تست‌ها

```bash
composer test
```

در حالت Docker:

```bash
make test
```

## Makefile (Docker)

برای ساده‌سازی کار با Docker یک `Makefile` هم در پروژه وجود دارد:

- `make build` ساخت imageها (با `--pull`)
- `make up` بالا آوردن سرویس‌ها (`docker compose up -d`)
- `make down` خاموش کردن سرویس‌ها
- `make restart` ری‌استارت با build
- `make logs` نمایش لاگ‌ها
- `make sh` ورود به کانتینر اپ
- `make composer-install` اجرای `composer install` داخل کانتینر
- `make key` اجرای `php artisan key:generate` داخل کانتینر
- `make fresh-db` اجرای `php artisan migrate:fresh --seed` داخل کانتینر
- `make test` اجرای تست‌ها داخل کانتینر

اگر روی سیستم‌تان دستور `docker compose` موجود نیست، باید Docker Compose را نصب/فعال کنید.

## پیشنهاد بهبود / Roadmap

چند بهبود منطقی برای کامل‌تر شدن پروژه:

- **سیستم احراز هویت**: اضافه کردن Auth برای API (مثلاً Laravel Sanctum) و محافظت از endpointها + رول/پرمیژن برای گزارش‌ها/عملیات کیف‌پول.
- **اتصال به پروایدر بانکی واقعی**: تعریف abstraction برای Payment Provider (مثلاً `PaymentProviderInterface`) و پیاده‌سازی یک Adapter برای PSP واقعی (به‌همراه webhook verification, signature, idempotency, retry).
- **ذخیره درصدها در دیتابیس + تاریخچه تغییرات**: انتقال تنظیمات breakdown از env به DB (مثلاً جدول `financial_settings`) و نگهداری history (audit trail) از تغییرات (timestamp, actor, old/new) تا گزارش‌ها قابل پیگیری باشند.
- **پنل مدیریت**: یک UI/endpoint مدیریتی برای تغییر درصدها با validation (جمع درصدها ≤ 100) و مشاهده تاریخچه.
  - نکته: این پنل مدیریت با کمک ابزارهای هوش مصنوعی (AI) توسعه یافته است.

## لایسنس

MIT
