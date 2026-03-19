# Developer Local Runbook

## Prerequisites

- PHP 8.2+
- Composer 2
- Node.js 20+
- npm
- MySQL 8+ or compatible

## Setup

1. Copy `.env.example` to `.env`
2. Set:
   - `APP_URL=http://127.0.0.1:8000`
   - MySQL connection values
3. Install dependencies:
   - `composer install`
   - `npm install`
4. Generate key if needed:
   - `php artisan key:generate`
5. Run database setup:
   - `php artisan migrate`
   - `php artisan db:seed`
6. Build assets:
   - `npm run build`
7. Start the app:
   - `php artisan serve --host=127.0.0.1 --port=8000`

## Local Verification Checklist

- `GET /api/v1/bootstrap`
- `GET /api/v1/locale/en`
- homepage SPA boot
- login/register pages
- product listing and product detail
- cart and checkout
- blog and CMS pages
- account pages after login
- shop listing/storefront pages

## Seeded Demo Accounts

- `admin@kidanstore.test` / `secret123`
- `seller@kidanstore.test` / `secret123`
- `customer@kidanstore.test` / `secret123`
- `delivery@kidanstore.test` / `secret123`

## Important Runtime Notes

- The SPA stores bearer tokens in `localStorage` as `shopAccessToken`.
- The frontend runtime boot contract is `GET /api/v1/bootstrap`.
- Current-user hydration contract is `GET /api/v1/user/info`.
- Online payment flows still hand off through web routes at `/payment/{gateway}/pay`.
- Offline wallet/re-payment flows may use the API alias at `/api/v1/payment/{gateway}/pay`.

## Intentional Legacy Bridges Still Present

- `GET /api/v1/auth/logout` remains as a temporary compatibility alias for the current frontend.
- Some public payment callback route names stay legacy for provider continuity.
- `routes/admin.php` still contains legacy admin surfaces, but optional addon routes are now guarded so missing packages do not break basic tooling.
