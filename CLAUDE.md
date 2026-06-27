# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

XeGhep.vn — an intercity ride-sharing / bus-booking platform (xe ghép tuyến, Hà Nội ↔ Hải Phòng). Laravel 13 (PHP 8.3) backend + four separate Vue 3 / TypeScript SPAs. UI strings and domain comments are in Vietnamese.

## Commands

```bash
composer dev          # Run everything: php serve + queue:listen + pail logs + vite (concurrently)
npm run dev           # Vite dev server only
npm run build         # Production build (build:ssr for SSR)

composer test         # config:clear + pint --test + php artisan test  (full check)
php artisan test                          # Pest feature/unit suite (sqlite :memory:)
php artisan test --filter=SomeTest        # Single test / class
php artisan dusk                          # Browser tests (needs real MySQL `xeghep_testing` + running app)
php artisan dusk --filter=AuthTest        # Single Dusk test

composer lint         # Pint (PHP) — composer lint:check for dry-run
npm run lint          # ESLint --fix  (lint:check = no fix)
npm run format        # Prettier over resources/
npm run types:check   # vue-tsc --noEmit
composer ci:check     # eslint + prettier + types + full test (mirrors CI)
```

DB is MySQL (`DATN_WD50` locally per `.env`). PHPUnit/Pest tests run on sqlite `:memory:`; Dusk uses MySQL `xeghep_testing` (see `.env.dusk.local`).

## Architecture

This started from `laravel/vue-starter-kit` but was **re-architected into four standalone Vue SPAs** that talk to the backend purely over JSON APIs. **Inertia is mostly vestigial** — only the `/settings` and Fortify auth scaffolding still use it (root view `app.blade.php`, `HandleInertiaRequests`). New feature work is API + SPA, not Inertia pages.

### Four portals
`customer`, `driver`, `operator`, `admin`. Each portal is a complete, isolated front+back vertical slice:

| Layer | Pattern (one per portal) |
|-------|--------------------------|
| Blade shell | `resources/views/{portal}.blade.php` — empty `#app`, loads the entry |
| Vite entry | `resources/js/entries/{portal}.ts` — `createApp` + Pinia + Vue Router |
| Router | `resources/js/router/{portal}.router.ts` + `.routes.ts` (with nav guard) |
| Stores | `resources/js/stores/{portal}.store.ts` + `{portal}.auth.store.ts` (Pinia) |
| API module | `resources/js/api/{portal}.api.ts` (wraps `api/client.ts`) |
| Layout/pages | `resources/js/layouts/{Portal}Layout.vue`, `resources/js/pages/{portal}/` |
| Controllers | `app/Http/Controllers/{Portal}/` |
| API routes | `routes/api_{portal}.php` (prefixed `/api/{portal}`) |

`web.php` serves each SPA via catch-all routes (`/admin/{any?}`, `/operator/{any?}`, `/driver/{any?}`, and a final customer catch-all that excludes `/api/`). Vue Router (`createWebHistory`) handles client-side routing within each portal.

### API routing
API route files are **not** in `web.php`/`api.php` — they are registered in `bootstrap/app.php`'s `then:` closure, each under `api/{public,customer,driver,operator,admin}` with `throttle:60,1`. To add an endpoint, edit the matching `routes/api_*.php` file.

### Auth (token-based Sanctum, per portal)
All four portals authenticate with Sanctum bearer tokens, **not** session cookies. `config/auth.php` defines four guards (`customer`/`driver`/`operator`/`admin`) all sharing the single `User` model — **role is enforced in controllers**, not by the guard. The frontend stores tokens in `localStorage` keyed by portal (`customer_token`, `admin_token`, …); `api/client.ts` auto-detects the active portal from the URL path, attaches the right token, and on `401` redirects to `/{portal}/login`.

### Backend layering
`Controller → Service → Repository → Model`. Controllers are thin HTTP adapters; business logic lives in `app/Services/` (e.g. `BookingService`, `PaymentService`, `WalletService`, `TripService`). Data access for the hot paths goes through `app/Repositories/` (with `Contracts/` interfaces). Domain constants are PHP enums in `app/Enums/` (`BookingStatus`, `TripStatus`, `PaymentMethod`, `UserRole`, …) — prefer these over magic strings.

### API response contract
Controllers return `JsonResponse` shaped `{ success: bool, data: ..., message?: ... }`. The frontend `apiClient` **unwraps `.data`** and surfaces `.message`/`.error`, so endpoints must keep this envelope. Errors are thrown as domain exceptions (`app/Exceptions/`, e.g. `SeatNotAvailableException`, `InsufficientBalanceException`) and mapped to HTTP codes in the controller `try/catch`.

### Events / queue / realtime
Domain events in `app/Events/` (`BookingConfirmed`, `TripStarted`, `DriverLocationUpdated`, …) with `app/Listeners/`. Async work is queued (`QUEUE_CONNECTION=database`, jobs in `app/Jobs/`, e.g. `SendSmsNotificationJob` on the `notifications` queue) — `composer dev` runs a `queue:listen`. Realtime uses Laravel Echo + Pusher on the frontend; broadcast auth channels are in `routes/channels.php` (e.g. presence channel `trips.{tripId}`). Scheduled work is in `routes/console.php` — `trips:auto-resolve` runs every 10 minutes (cancels overdue trips / refunds, settles forgotten trips).

### Wayfinder
`resources/js/actions/`, `routes/`, `wayfinder/` are **generated** by `@laravel/vite-plugin-wayfinder` from PHP controllers/routes — don't hand-edit; they regenerate on build/dev.

## Domain notes
See `app/Models/` for the schema. Core flow: customers search Trips → book Seats → pay (MoMo / VNPay / wallet / cash) → driver does QR check-in → trip completes → revenue is realized and operators get Payouts. Key cross-cutting rules (cash vs QR payment, expired-trip handling, vehicle↔driver model, revenue realization) are documented in `docs/03-architecture.md` and the project memory index (`MEMORY.md`).
