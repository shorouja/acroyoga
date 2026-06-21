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
- Migration workflow: PostgreSQL 17 via Docker locally (`docker-compose.yml`), `make:migration` now generates correct SQL
- Caddyfile in repo (`infra/Caddyfile`), copied to server on every deploy — rebuild-safe
- JWT auth: `lexik/jwt-authentication-bundle` v3.2, RS256 keypair, `POST /api/login`, API routes protected

## Immediate

- [ ] Register endpoint: `POST /api/register` — create user with hashed password
- [ ] Migrate server schema: pull new auth config to server, run `lexik:jwt:generate-keypair --env=prod`

## Mid-Term
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
