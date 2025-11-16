# MFA Web App (PHP + MySQL) — password + TOTP

## Overview

This is a sample Multi-Factor Authentication (MFA) web application built with:

- PHP 7.4+ / 8.x (no composer libraries required)
- MySQL (mysqli)
- Bootstrap 5 + vanilla JS
- TOTP (RFC6238) implemented in `app/totp.php`
- QR generation via Google Charts API (server returns otpauth URI for QR)

**Features**

- Registration with password-strength check and TOTP enrollment.
- Login: password step + TOTP step (or backup codes).
- Backup codes generation (10 single-use codes, stored hashed).
- Account lockout on repeated failures (configurable).
- Admin dashboard to view users & auth logs.
- CSRF protection and prepared statements across DB queries.
- TOTP secrets encrypted with `APP_SECRET` via openssl.

## Quick Setup (XAMPP / LAMP)

1. Clone or copy project into your web server (for XAMPP place inside `htdocs`).
2. Point document root to `/mfa-app/public/`. Alternatively, use `.htaccess` or virtual host.
3. Copy `.env.example` to `.env` and set values (DB credentials, APP_SECRET).
   - `APP_SECRET` should be a secure random string (32+ characters).
   - In development, `USE_SECURE_COOKIES=0`. In production set to 1 and enable HTTPS.
4. Import database:
   - Option A: Import `sql/mfa_schema.sql`. Then run `php utils/generate_seed_hashes.php` to create seeded admin/test user with proper hashed passwords (script will instruct).
   - Option B: Run the included `utils/seed.php` script (see file) which will create users properly using `.env` settings.
5. Ensure PHP `openssl` extension is enabled.
6. Start server and visit `http://localhost/register.php` to register or `http://localhost/login.php` to login with seeded users.
7. For HTTPS locally: generate a self-signed cert and enable it in Apache; set `USE_SECURE_COOKIES=1` after enabling HTTPS.

## Generating seeded password hashes

To create the admin/test users with proper password hashing:

- Run `php utils/generate_seed_hashes.php` (this will output SQL with proper password_hash values to paste into `sql/mfa_schema.sql` or it will optionally insert into DB).

## Important Security Notes

- **APP_SECRET** is used to encrypt TOTP secrets. Keep it out of version control.
- Change seeded passwords immediately and revoke existing backup codes.
- Use HTTPS in production. For local dev, secure cookies are optional; never use insecure config in production.
- Rotate APP_SECRET carefully—re-enrollment of TOTP required.
- Session cookies use `httponly` and optionally `secure` flags (see `app/session.php`).

## Files & Purpose (short)

- `public/*.php` — routes and views.
- `app/config.php` — loads `.env`, constants.
- `app/db.php` — mysqli connection helper.
- `app/totp.php` — TOTP implementation (RFC 6238).
- `app/csrf.php` — CSRF token helper.
- `app/auth.php` — auth helpers: login flow, logs.
- `app/qr.php` — generates otpauth URI / QR image endpoint.
- `admin/*` — admin pages.
- `sql/mfa_schema.sql` — schema + seed placeholder.
- `assets/*` — css/js.

## Testing checklist (manual)

1. Register a user: fill required details. On success you see QR image and manual code.
2. Add TOTP account in Google Authenticator / Authy using QR.
3. Login with username + password (step 1), then TOTP (step 2) — should succeed, redirect to dashboard.
4. Try invalid TOTP — expect "totp-fail" log and failed attempt increment.
5. Use backup code shown earlier — it should log in and mark backup code used.
6. Exceed `MAX_FAILED_ATTEMPTS` (default 5) and confirm account locked; admin can unlock.
7. Regenerate TOTP (requires current password + TOTP) — old codes become invalid, new QR shown.
8. Admin: login and view `users.php` and `logs.php` entries; revoke remembered devices.

## Troubleshooting

- Missing OpenSSL -> enable `php_openssl` in php.ini.
- DB connection errors -> verify `.env` DB settings and import schema.
- If QR not visible, check outgoing HTTP to Google Charts (or construct QR offline if needed).

## Notes about WebAuthn/FIDO2

This project focuses on password + TOTP. WebAuthn integration requires browser APIs and server-side FIDO libraries (e.g., `web-auth/webauthn-lib`). I left hooks and comments where one could add WebAuthn registration/verification flows (e.g., in `dashboard.php` and `auth.php`).

---
