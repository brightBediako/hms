# Production deployment guide — Hotel Management System (HMS)

## Architecture rule

**Document root must be `public/`** (not the project root).

| Correct | Wrong |
|---------|--------|
| `/var/www/hms/public` | `/var/www/hms` |
| `C:\...\hms\public` | `C:\...\hms` |

If the vhost points at the project root, `.env`, `scripts/`, and source can leak. Root `.htaccess` is a safety net only — do not rely on it.

---

## 1. Server requirements

- PHP 8.1+ with extensions: `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl`
- MySQL 8+ / MariaDB 10.4+
- Apache with `mod_rewrite` + `AllowOverride` (or Nginx equivalent)
- TLS certificate (HTTPS)

---

## 2. Deploy steps

1. Upload/clone the app **outside** any broader web tree if possible; expose only `public/`.
2. Install PHP deps (optional today; project has a fallback autoloader):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Build CSS if you changed Tailwind sources:
   ```bash
   npm ci
   npm run build:css
   ```
4. Create `.env` from `.env.production.example`, then generate a key:
   ```bash
   cp .env.production.example .env
   # Windows XAMPP:
   c:\xampp\php\php.exe scripts\generate_app_key.php
   # Linux/macOS (if php is on PATH):
   php scripts/generate_app_key.php
   ```
   Paste the printed `APP_KEY=...` into `.env`.
5. Create a dedicated MySQL user (not `root`) with rights only on the `hms` database.
6. Import schema (and baseline seed for roles/permissions/settings only):
   ```bash
   mysql -u hms_app -p hms < db/hms_schema.sql
   mysql -u hms_app -p hms < db/hms_seed_data.sql
   ```
7. Ensure writable dirs (owner = web user):
   - `storage/uploads`
   - `storage/backups`
   - `storage/logs`
8. Create the system admin from `.env` (`SYSTEM_ADMIN_*`):
   ```bash
   php scripts/ensure_system_admin.php
   ```
9. Keep only production utilities under `scripts/` (`generate_app_key.php`, `ensure_system_admin.php`, `wipe_database.php`). HTTP access is denied by `scripts/.htaccess`.
10. Point the site document root to `…/hms/public` and enable HTTPS.
11. Hit `/health` — expect `{"status":"ok",…}` with **no** database name in production.

---

## 3. Production `.env` checklist

| Key | Required value |
|-----|----------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://…` (no trailing path unless app lives in a subfolder) |
| `APP_KEY` | Long random string (`php scripts/generate_app_key.php`) |
| `DB_*` | Dedicated user + strong password |
| `SESSION_SECURE` | `true` on HTTPS |
| `TRUST_PROXY` | `true` only behind a trusted TLS-terminating proxy |
| `SYSTEM_ADMIN_NAME` | Bootstrap admin display name |
| `SYSTEM_ADMIN_EMAIL` | Bootstrap admin login email |
| `SYSTEM_ADMIN_PASSWORD` | Bootstrap admin password |

Local XAMPP can keep `APP_ENV=local` and `APP_DEBUG=true` in `.env`.

---

## 4. Security features already in place

- CSRF on state-changing routes
- Session: HttpOnly, SameSite=Lax, Secure when HTTPS / `SESSION_SECURE`
- RBAC permission checks in controllers
- Uploads & backups under `storage/` denied by `.htaccess`; downloaded via authenticated controllers
- Error pages hide stack traces when `APP_DEBUG=false`; errors log to `storage/logs/app-YYYY-MM-DD.log`
- No demo credentials on the login screen
- System admin is created from `SYSTEM_ADMIN_*` via `scripts/ensure_system_admin.php`
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, HSTS when `APP_URL` is https
- `/health` returns minimal status in production

---

## 5. Backups

- Use **Settings → Backups** for on-demand SQL dumps, or schedule `mysqldump` on the server.
- Also back up `storage/uploads` (guest docs, expense receipts) — not included in SQL dumps.
- Keep off-server copies. See `docs/restore.md`.

---

## 6. Post-go-live smoke test

- [ ] HTTPS loads login without demo password hint
- [ ] Login with production admin
- [ ] Create reservation + optional payment
- [ ] Front desk check-in / check-out
- [ ] Billing + payment
- [ ] Manual backup download works
- [ ] `/health` returns `ok` without env/DB details
- [ ] Direct URL to `/../.env` or `/scripts/` returns 403/404

---

## 7. Local vs production

| | Local (XAMPP) | Production |
|--|---------------|------------|
| URL | `http://localhost/hms/public` | `https://hotel.example.com` |
| Docroot | Often `htdocs` + `/hms/public` path | Vhost → `…/hms/public` |
| Debug | `true` | `false` |
| DB user | `root` OK for local only | Dedicated `hms_app` |
