# XeGhepTuyen-Fgroup — Đặt xe ghép liên tỉnh (DATN WD50)

Monorepo gồm 2 ứng dụng độc lập:

```text
DATN_WD50/
├── backend/    # Laravel 13 REST API  → deploy Laravel Cloud
├── frontend/   # Vue 3 + Vite (4 SPA) → deploy Vercel
├── docs/       # PRD, kiến trúc, tài liệu dự án
└── .github/    # CI: backend.yml + frontend.yml (path-filtered)
```

Kiến trúc deploy:

```text
Frontend Vue 3 + Vite (Vercel)  https://<app>.vercel.app
        │  HTTPS REST API (Sanctum Bearer token, CORS whitelist)
        ▼
Backend Laravel (Laravel Cloud) https://<app>.laravel.cloud
        │
        ▼
MySQL · Redis (cache/queue/session) · Reverb WebSocket · Storage
```

Frontend là **4 SPA** phục vụ theo prefix URL: `/` (khách), `/driver`, `/operator`, `/admin` — mỗi SPA một HTML entry (Vite MPA + rewrites trong `frontend/vercel.json`).

## Yêu cầu môi trường

- PHP ≥ 8.3, Composer 2
- Node.js ≥ 22, npm
- MySQL 8 (hoặc DB Laravel Cloud), Redis (cache/queue/session)

## Chạy local

### Backend (http://localhost:8000)

```bash
cd backend
composer install
cp .env.example .env        # điền DB/Redis của bạn
php artisan key:generate
php artisan migrate --seed  # seed bắt buộc để có vai trò RBAC admin
composer dev                # serve + queue + schedule + pail
# hoặc tối thiểu: php artisan serve
# WebSocket (tùy chọn): php artisan reverb:start
```

### Frontend (http://localhost:5173)

```bash
cd frontend
npm install
cp .env.example .env        # VITE_API_BASE_URL=http://localhost:8000
npm run dev
```

Mở http://localhost:5173 (khách) · /driver · /operator · /admin.

## Biến môi trường

- `backend/.env` — DB, Redis, Reverb, SePay/MoMo/VNPay, **`FRONTEND_URL`** (origin FE cho CORS + trang kết quả thanh toán). Không bao giờ commit `.env`.
- `frontend/.env` — chỉ biến `VITE_*` (được nhúng vào bundle công khai — **không đặt secret**): `VITE_API_BASE_URL` (origin backend, không kèm `/api`), `VITE_MAPBOX_TOKEN` (public token), `VITE_REVERB_*`.

## Typed routes (Wayfinder)

FE gọi API qua route object sinh tự động tại `frontend/src/{actions,routes,wayfinder}` (đã commit). Khi backend đổi route, chạy:

```bash
cd backend && composer wayfinder   # generate thẳng sang ../frontend/src
```

rồi commit các file thay đổi. Không sửa tay các thư mục sinh tự động này.

## Test & chất lượng

```bash
# Backend
cd backend
composer lint:check   # Pint
php artisan test      # Pest

# Frontend
cd frontend
npm run lint:check    # ESLint
npm run format:check  # Prettier
npm run types:check   # vue-tsc
npm run test          # Vitest (unit/contract)
npm run test:e2e      # Playwright (tự bật Vite dev server, API mock in-test)
```

CI GitHub Actions chạy pipeline tương ứng khi `backend/**` hoặc `frontend/**` thay đổi.

## Build & Deploy

### Backend → Laravel Cloud

- **Application root: `backend`** (cấu hình trong dashboard Laravel Cloud — phải verify vì repo là monorepo).
- Env production tối thiểu: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<api-domain>`, `FRONTEND_URL=https://<fe-domain>`, `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=error`, DB/Redis do Cloud cấp, `REVERB_*` nếu bật WebSocket.
- Deploy command: `php artisan migrate --force && php artisan optimize`.
- Queue worker: `php artisan queue:work --queue=high,notifications,default --sleep=3 --tries=3 --timeout=90` — **`high` phải đứng đầu và không được bỏ sót**: hủy vé quá hạn (`ExpireUnpaidBookingJob`), hoàn tiền (`ProcessRefundJob`), giải phóng ghế giữ tạm (`ExpireLockedSeatsJob`) đều chạy ở queue này. Scheduler: bật scheduler của Cloud (`trips:auto-resolve` + dọn ghế theo lịch).
- Sau deploy kiểm tra: `https://<api-domain>/api/public/health` và `/up`.

### Frontend → Vercel

- Import repo, **Root Directory: `frontend`**, Framework Preset: Vite, Build `npm run build`, Output `dist` (rewrites đọc từ `frontend/vercel.json`).
- Environment Variables: `VITE_API_BASE_URL=https://<api-domain>`, `VITE_MAPBOX_TOKEN`, `VITE_REVERB_*` (nếu dùng realtime).
- Sau deploy: refresh `/admin/...` không được 404 (rewrites), console không lỗi CORS.

## Xử lý lỗi thường gặp

**CORS bị chặn** — origin FE chưa nằm trong whitelist: đặt đúng `FRONTEND_URL` (không dấu `/` cuối) trong `backend/.env`, xem `backend/config/cors.php`. Preview Vercel: dùng `FRONTEND_URL_PATTERN` (regex có kiểm soát), không mở `*`.

**401 hàng loạt sau login** — token Bearer lưu `localStorage` theo portal (`admin_token`, `driver_token`…); kiểm tra `VITE_API_BASE_URL` trỏ đúng backend và request có header `Authorization`.

**CSRF 419** — không áp dụng: API dùng Bearer token, không dùng session/cookie; nếu gặp 419 nghĩa là đang gọi nhầm route web.

**Refresh trang 404** — local: middleware `multi-spa-fallback` trong `frontend/vite.config.ts`; production: `frontend/vercel.json` rewrites.

**Realtime không chạy** — cần `php artisan reverb:start` (local) và `VITE_REVERB_APP_KEY` khớp `REVERB_APP_KEY`; FE tự tắt WebSocket nếu key trống.
