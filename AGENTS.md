# Local preview notes

- This checkout lacks the original `app/` implementation, so the minimal DB connection and session guard needed by the included dashboard and login live under `app/`.
- `script.sql` initializes MariaDB on a fresh volume; `.base44/seed.php` runs after DB health and creates one local demo account/company idempotently. Login: `demo@localhost.test` / `demo123` (local preview only; never deploy these credentials).
- Run `docker compose -f docker-compose.base44.yml up -d --build`; verify with `docker compose -f docker-compose.base44.yml ps`, `curl -I localhost:3000/login.php`, and a browser login to the dashboard.
- PHP's development server reads mounted PHP files on each request; no compilation is needed. Other linked pages and the original installer refer to files not present in this checkout and are not part of the working preview.
