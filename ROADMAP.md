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
- `APP_ENV=prod` set on server

## Immediate

### Local dev setup (prerequisite for entity work)
- [x] Regenerate `APP_SECRET`
- [ ] Install PHP 8.4 locally (winget)
- [ ] Install Composer locally (winget)
- [ ] Install Symfony CLI locally (winget)
- [ ] `composer install` in `api/` locally
- [ ] Local `api/.env.local` with SQLite for dev DB
- [ ] Verify with `php bin/console list`

### Data model
- [ ] Core entities: `User`, `Skill`, `Exercise`, `ExerciseGroup`, `ExerciseGroupItem`
- [ ] Progress entities: `UserSkillLevel`, `UserExerciseProgress`
- [ ] Partnership entities: `Partnership`, `PartnershipProgress`, `SessionLog`
- [ ] Enums: `Role`, `Difficulty`, `ProgressStatus`, `PartnershipStatus`, `SkillCategory`
- [ ] Run migrations on server, verify at `/api/docs`

### Auth
- [ ] `make:user` + JWT (`lexik/jwt-authentication-bundle`)

## Mid-Term
- [ ] Frontend: choose framework (React / Vue / Angular), scaffold into `frontend/`
- [ ] Caddyfile: add `try_files` fallback for SPA routing
- [ ] CORS: configure `nelmio/cors-bundle` for frontend origin
- [ ] GitHub Actions: add frontend deploy job

## Operations
- [ ] PostgreSQL backups: `pg_dump` + cron + offsite (Backblaze B2 or S3)
- [ ] Uptime monitoring (UptimeRobot free tier)
- [ ] `unattended-upgrades` for automatic OS security patches

## Security & Compliance
- [ ] Transactional email: Brevo / Postmark + SPF/DKIM/DMARC DNS records
- [ ] Rate limiting on registration endpoint
- [ ] GDPR: Impressum, Datenschutzerklärung, user data deletion
