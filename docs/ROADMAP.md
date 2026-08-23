# Roadmap — Acroyoga Platform

## Done
- VPS provisioned (netcup, Debian 13)
- SSH hardening: key-only auth, no root login, UFW, Fail2ban
- Caddy + automatic HTTPS (Let's Encrypt)
- PostgreSQL: `acroyoga` DB, `acro_user`, scram-sha-256
- Symfony 7 + API Platform 3 + Doctrine scaffold
- PHP-FPM via Unix socket, routing confirmed
- Monorepo setup with master/dev/feature branching strategy
- Branch protection on `master` and `dev`
- GitHub Actions auto-deploy on push to `master` (api/**)
- Server running from `/var/www/acroyoga`, Caddyfile updated
- `APP_ENV=prod` + `APP_SECRET` set on server
- PHP 8.4 + Composer + Symfony CLI installed locally
- `make:entity` verified working locally
- Full data model: entities, enums, repositories, migration
- `/api/docs` live with all 10 resources
- Full data model migration — regenerated for PostgreSQL (`Version20260822180806`, replacing the SQLite-dialect `Version20260621182226`) in the WSL2 dev env; applies cleanly on Postgres 17 and `schema:validate` passes (2026-08-22)
- WSL2 full-toolchain local dev env stood up (Debian 13, PHP 8.4 + pdo_pgsql, Docker Engine, Postgres 17) — dev/prod parity; see `docs/wsl2-dev-setup.md`
- Caddyfile in repo (`infra/Caddyfile`), copied to server on every deploy — rebuild-safe
- JWT auth: `lexik/jwt-authentication-bundle` v3.2, RS256 keypair, `POST /api/login`, API routes protected
- Register endpoint: `POST /api/register` — validates input, hashes password, returns 201 + user; functional tests added
- Frontend MVP scaffolded into `frontend/` — React 19 + Vite + TS + Tailwind SPA: register/login (JWT), read-only exercise/skill library over API Platform Hydra; 16 Vitest+RTL tests (#38)
- Frontend deployed to prod (danielschwabe.com) — build-on-server pipeline (`npm ci && npm run build` in the deploy SSH script), Caddy `try_files` for SPA deep links; live + smoke-tested green 2026-08-23 (#40)
- Prod JWT verified working end-to-end — `POST /api/login` returns proper 401 on bad creds (keypair present on prod), so auth is operational live

## Immediate

- [x] **Deploy pipeline swallows failures** — FIXED (PR #29 → master `b132492`): added `script_stop: true` + `set -euo pipefail` to `deploy-api.yml`, so the first failed command now aborts the deploy instead of reporting green.
- [x] **Prod migration-state reconciliation** — DONE 2026-08-22 (#36). Reconciled prod's `doctrine_migration_versions` (removed old `20260621182226` row, inserted `DoctrineMigrations\Version20260822180806`), then merged dev→master. Deploy went green; migrate reported `[OK] Already at the latest version` — clean no-op, no schema change. First truthful green deploy since the fail-loud fix (#29). Fresh-server rebuilds via `migrate` now work.
- [x] Migrate server: sudoers for caddy + JWT keypair on prod — DONE. Prod `POST /api/login` returns a proper 401 on bad creds (not 500), confirming the RS256 keypair + `JWT_PASSPHRASE` are in place and auth is operational live (verified 2026-08-23).
- [ ] **Legal (Germany): Impressum + Datenschutzerklärung** — required for a public, live site collecting registrations. Interim mitigation under consideration: replace the public site with a maintenance view to remove the data-collection surface until the legal pages exist.

## Mid-Term
- [x] Regenerate migrations for PostgreSQL — DONE 2026-08-22 (#34). Replaced the SQLite-dialect `Version20260621182226` with `Version20260822180806`, generated via `doctrine:migrations:diff` against real Postgres 17 in the new WSL2 env; `migrate` applies cleanly (41 queries, 11 tables) and `schema:validate` passes. **Follow-up:** prod migration-state reconciliation before deploy — tracked as an Immediate gate above.
- [x] **Local dev env → WSL2 full toolchain** — DONE 2026-08-22. Debian 13 (trixie) WSL2 distro, systemd, user `hausmeister` (passwordless sudo); PHP 8.4 + `pdo_pgsql`, Composer, Symfony CLI, native Docker Engine, Postgres 17 via `docker compose`. Repo cloned to ext4 at `~/dev/acroyoga`. Mirrors the Debian 13 prod server. Guide: `docs/wsl2-dev-setup.md`. **Remaining polish:** GitHub SSH auth inside WSL (currently pull/push done from Windows side); update `deployment.md` Local dev section to describe WSL2 instead of the never-installed Windows/Docker-Desktop setup.
- [x] Frontend: choose framework, scaffold into `frontend/` — DONE (#38). React 19 + Vite + TS + Tailwind.
- [x] Caddyfile: add `try_files` fallback for SPA routing — DONE (#40). `try_files {path} /index.html`.
- [x] CORS: N/A — frontend is served same-origin by Caddy (Vite proxy in dev), so no `nelmio/cors-bundle` needed.
- [x] GitHub Actions: add frontend deploy job — DONE (#40). Build-on-server in the existing deploy workflow (triggers on `frontend/**`).
- [ ] Server provisioning: consider a full bootstrap script (`infra/setup.sh`) that configures Caddyfile, sudoers, and PHP-FPM from scratch — valuable if the VPS is ever rebuilt or replicated (currently handled by docs + pipeline steps)

## Operations
- [~] PostgreSQL backups: daily local `pg_dump` + cron (`infra/backup.sh`, 14-day retention) + manual pull to a personal machine (`infra/pull-backups.sh`); runbook `docs/backups.md`. **Local-only for now** — cloud offsite (B2/S3) + gpg encryption is a documented TODO. Server-side cron install is a one-time manual step (see runbook).
- [ ] Uptime monitoring (UptimeRobot free tier)
- [ ] `unattended-upgrades` for automatic OS security patches

## Security & Compliance
- [ ] Transactional email: Brevo / Postmark + SPF/DKIM/DMARC DNS records
- [ ] Rate limiting on registration endpoint
- [ ] GDPR: Impressum, Datenschutzerklärung, user data deletion
- [ ] Auth at scale: evaluate Auth0 / Keycloak once self-managed JWT keys become operational overhead (not needed until multi-server or team access control is required)
