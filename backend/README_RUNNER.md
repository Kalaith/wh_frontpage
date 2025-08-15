Run-script runner setup

This project includes a secure, whitelisted HTTP runner at `backend/public/run-script.php`.

Setup:
1. Add the following to your production environment file `backend/.env.production` (or set as a system environment variable on the server):

ADMIN_RUN_KEY=some-long-random-secret

2. Deploy the project (the runner file is included in `backend/public/`).

Usage example (from your CI or local machine):

curl -X POST \
  -H "X-Admin-Key: some-long-random-secret" \
  -F "script=init-database" \
  https://your-site.example.com/frontpage/public/run-script.php

The runner only allows a short whitelist of scripts. See `backend/scripts/README.md` for the default whitelist and details.

Security notes:
- Keep the secret out of source control and rotate it if leaked.
- Restrict access to this endpoint by IP (server firewall) if possible.
- Avoid adding any scripts to the whitelist that can be abused by an attacker.
