run-script runner

This folder contains backend maintenance scripts that are intended to be executed manually or by the secure runner.

Runner: `backend/public/run-script.php`

Purpose:
- Provide a simple, whitelisted HTTP endpoint to execute small maintenance scripts on the server without exposing a full admin UI.

Security:
- The runner requires an admin secret via the `X-Admin-Key` header. Set `ADMIN_RUN_KEY` in the backend `.env.production` (or the server environment) and keep it secret.
- The runner only allows scripts named in its whitelist. Do NOT add untrusted scripts.

Usage (example):
1. Set `ADMIN_RUN_KEY` in `backend/.env.production` or your server's environment.
2. Call the runner from a terminal or CI server:

curl -X POST \
  -H "X-Admin-Key: your_secret_here" \
  -F "script=init-database" \
  https://your-site.example.com/frontpage/public/run-script.php

Allowed scripts (by default):
- init-database
- create_projects_table
- import_projects

If you need additional scripts, update `backend/public/run-script.php` whitelist carefully.
