# Changes Log - Mail Config Moved to Env

Date: 2026-02-07
Workspace: /Applications/XAMPP/xamppfiles/htdocs/ampmkp

## Summary
Moved mailing configuration out of hardcoded constants and into environment variables, then wired mail sender identity and festival admin target to env-backed constants.

## File Updates

### /Applications/XAMPP/xamppfiles/htdocs/ampmkp/includes/mail_config.php
1. Added `getMailEnv(string $key, ?string $default = null): ?string` helper.
2. `getMailEnv` now reads values from:
   - `/Applications/XAMPP/xamppfiles/htdocs/ampmkp/.env`
   - process environment (`getenv`) as fallback
   - provided default as final fallback
3. Replaced hardcoded SMTP constants with env-backed constants:
   - `SMTP_HOST` <= `MAIL_SMTP_HOST`
   - `SMTP_PORT` <= `MAIL_SMTP_PORT`
   - `SMTP_USERNAME` <= `MAIL_SMTP_USERNAME`
   - `SMTP_PASSWORD` <= `MAIL_SMTP_PASSWORD`
   - `SMTP_ENCRYPTION` <= `MAIL_SMTP_ENCRYPTION`
4. Replaced hardcoded email constants with env-backed constants:
   - `ADMIN_EMAIL` <= `MAIL_ADMIN_EMAIL`
   - `SALES_EMAIL` <= `MAIL_SALES_EMAIL` (defaults to `ADMIN_EMAIL`)
   - `NOREPLY_EMAIL` <= `MAIL_NOREPLY_EMAIL` (defaults to `ADMIN_EMAIL`)
   - `ADMIN_ORDERS_URL` <= `MAIL_ADMIN_ORDERS_URL`
5. Added new constant:
   - `MAIL_FROM_NAME` <= `MAIL_FROM_NAME`
6. Updated PHPMailer sender display name usage:
   - `setFrom(NOREPLY_EMAIL, 'Angel Marketplace')` -> `setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME)`
   - `addReplyTo(NOREPLY_EMAIL, 'Angel Marketplace')` -> `addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME)`
7. Removed hardcoded festival inbox target:
   - `$targetInbox = 'admin@angelmarketplace.org';`
   - replaced with `$targetInbox = ADMIN_EMAIL;`

Validation:
- `php -l /Applications/XAMPP/xamppfiles/htdocs/ampmkp/includes/mail_config.php`
- Result: `No syntax errors detected`

### /Applications/XAMPP/xamppfiles/htdocs/ampmkp/.env.example
Added a new **Mail Configuration** section with placeholders/defaults:
- `MAIL_SMTP_HOST`
- `MAIL_SMTP_PORT`
- `MAIL_SMTP_USERNAME`
- `MAIL_SMTP_PASSWORD`
- `MAIL_SMTP_ENCRYPTION`
- `MAIL_ADMIN_EMAIL`
- `MAIL_SALES_EMAIL`
- `MAIL_NOREPLY_EMAIL`
- `MAIL_FROM_NAME`
- `MAIL_ADMIN_ORDERS_URL`

### /Applications/XAMPP/xamppfiles/htdocs/ampmkp/.env (local only)
Appended local runtime mail keys so current environment continues working after code refactor.

Note:
- `.env` is local/non-versioned in this repository.

## Staging State
`changes.md` is intentionally left unstaged as requested.
