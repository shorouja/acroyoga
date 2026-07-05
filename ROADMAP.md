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
- Full data model migration committed (`Version20260621182226`) — but SQLite-dialect; see Mid-Term regeneration item (local Postgres/Docker is documented in `deployment.md` but not actually installed on the dev machine as of 2026-07-05)
- Caddyfile in repo (`infra/Caddyfile`), copied to server on every deploy — rebuild-safe
- JWT auth: `lexik/jwt-authentication-bundle` v3.2, RS256 keypair, `POST /api/login`, API routes protected
- Register endpoint: `POST /api/register` — validates input, hashes password, returns 201 + user; functional tests added

## Immediate

- [ ] **Deploy pipeline swallows failures** — `.github/workflows/deploy-api.yml` uses `appleboy/ssh-action` with a multi-line `script:` and no `set -e`, so the job's exit code is only the *last* command (`systemctl reload`). Every earlier failure is invisible and CI stays green. Confirmed in logs: run #18 (2026-06-21) reported ✅ while `git pull` failed ("untracked working tree files would be overwritten") and `migrations:migrate` reported "no registered migrations" — nothing was applied. **Fix:** prefix the script with `set -euo pipefail` (or set `script_stop: true` on the action) so any failed step fails the deploy. This is a prerequisite for trusting the migration item below — right now a failed migration on prod would report success.
- [ ] Migrate server: add sudoers entries for caddy, run `lexik:jwt:generate-keypair --env=prod`, add `JWT_PASSPHRASE` to `.env.local`

## Mid-Term
- [ ] Regenerate migrations for PostgreSQL — the initial migration (`Version20260621182226`) is SQLite-dialect (`AUTOINCREMENT`, `CLOB`) and cannot replay on Postgres. **Current prod state (inferred from deploy logs 2026-07-05):** the version is recorded as executed in the server's `doctrine_migration_versions` and today's `migrate` was a clean no-op ("Already at the latest version") — but since the SQL can't run on Postgres, the schema was almost certainly built manually on the server (`doctrine:schema:create`) and the version marked applied by hand after #18's failed pull. So prod works *now*, but a fresh-server rebuild via `migrate` would fail, and the next locally-generated migration will be SQLite-dialect too. **Blocker:** no way to generate correct Postgres DDL locally — dev machine has no Docker and no `pdo_pgsql` (despite `deployment.md` documenting Docker Postgres). **Options:** (a) **[chosen]** stand up the WSL2 full-toolchain dev env (see item below), then `make:migration` against real Postgres inside WSL2; (b) generate server-side via `doctrine:schema:create --dump-sql --env=prod` (read-only, prints DDL) and commit a hand-assembled Postgres migration; (c) confirm actual prod schema first via read-only SSH inspection. Do the pipeline fix (Immediate) first so a bad migration can't deploy green.
- [ ] **Local dev env → WSL2 full toolchain** (decided 2026-07-05) — move PHP 8.4 + Composer + Symfony CLI + Docker (daemon inside WSL2, not Docker Desktop) + Postgres 17 all into a WSL2 Debian/Ubuntu distro; VS Code connects via Remote-WSL. Rationale: solves the missing `pdo_pgsql` for free (Linux PHP), keeps all file I/O in the Linux filesystem (no Windows↔WSL crossing lag), and mirrors the Debian 13 prod server for dev/prod parity. Unblocks the Postgres-migration regeneration above and lets functional tests run against real Postgres locally. Then update `deployment.md` Local dev section (currently documents a Windows/Docker-Desktop setup that isn't actually installed).
- [ ] Frontend: choose framework (React / Vue / Angular), scaffold into `frontend/`
- [ ] Caddyfile: add `try_files` fallback for SPA routing
- [ ] CORS: configure `nelmio/cors-bundle` for frontend origin
- [ ] GitHub Actions: add frontend deploy job
- [ ] Server provisioning: consider a full bootstrap script (`infra/setup.sh`) that configures Caddyfile, sudoers, and PHP-FPM from scratch — valuable if the VPS is ever rebuilt or replicated (currently handled by docs + pipeline steps)

## Operations
- [ ] PostgreSQL backups: `pg_dump` + cron + offsite (Backblaze B2 or S3)
- [ ] Uptime monitoring (UptimeRobot free tier)
- [ ] `unattended-upgrades` for automatic OS security patches

## Security & Compliance
- [ ] Transactional email: Brevo / Postmark + SPF/DKIM/DMARC DNS records
- [ ] Rate limiting on registration endpoint
- [ ] GDPR: Impressum, Datenschutzerklärung, user data deletion
- [ ] Auth at scale: evaluate Auth0 / Keycloak once self-managed JWT keys become operational overhead (not needed until multi-server or team access control is required)
