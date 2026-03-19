# Kidan Ecommerce Rebuild

Laravel 10 + Vue 3 storefront rebuild of the Kidan ecommerce platform.

## What This Repo Contains

- rebuilt API-first backend through Phase 10 hardening
- embedded Vue storefront under `resources/js`
- SPA boot via `GET /api/v1/bootstrap`
- bearer-token auth with Sanctum-compatible personal access tokens
- catalog, checkout, content, account, benefits, shops, delivery, and payment flows rebuilt for the current frontend contract

## Core Docs

- [Backend Rebuild Plan](docs/backend-rebuild-plan.md)
- [Frontend Compatibility Checklist](docs/frontend-compatibility-checklist.md)
- [Phase 2 Auth And Bootstrap Contract](docs/phase-2-auth-bootstrap-contract.md)
- [Developer Local Runbook](docs/developer-local-runbook.md)
- [Rebuilt API Overview](docs/rebuilt-api-overview.md)

## Local Development

1. Copy `.env.example` to `.env`
2. Configure MySQL and app URL
3. Install backend dependencies:
   - `composer install`
4. Install frontend dependencies:
   - `npm install`
5. Run migrations:
   - `php artisan migrate`
6. Seed demo data:
   - `php artisan db:seed`
7. Build frontend assets:
   - `npm run build`
8. Start Laravel:
   - `php artisan serve --host=127.0.0.1 --port=8000`

## Useful Local URLs

- Storefront: `http://127.0.0.1:8000`
- Bootstrap: `http://127.0.0.1:8000/api/v1/bootstrap`
- Locale: `http://127.0.0.1:8000/api/v1/locale/en`

## Demo Credentials Seeded

- Admin: `admin@kidanstore.test` / `secret123`
- Seller: `seller@kidanstore.test` / `secret123`
- Customer: `customer@kidanstore.test` / `secret123`
- Delivery Boy: `delivery@kidanstore.test` / `secret123`

## Notes

- The rebuild preserves a few intentional legacy public bridges where the SPA or payment providers still depend on those paths.
- Unsupported or disabled payment gateways now fail explicitly during initialization.
- Payment provider callbacks are persisted idempotently through the rebuilt payment tables.
