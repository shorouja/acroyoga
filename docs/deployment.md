# Deployment

## Infrastructure

| What | Detail |
|---|---|
| VPS | netcup, Debian 13, IP `152.53.198.190` |
| Domain | danielschwabe.com |
| Webserver | Caddy (automatic HTTPS via Let's Encrypt) |
| PHP | PHP-FPM 8.4, Unix socket `/run/php/php8.4-fpm.sock` |
| Database | PostgreSQL 17, DB `acroyoga`, user `acro_user` |
| Deploy user | `deploy` (no shell login, limited sudo) |
| Repo on server | `/var/www/acroyoga` (cloned via HTTPS) |

## Branching strategy

```
master  ← protected, auto-deploys to server on push
  └── dev  ← protected, merges via PR only
        └── feature/*  ← all work branches cut from here
```

Flow: `feature/*` → PR → `dev` → PR → `master` → auto-deploy.

Always use `--base dev` when creating feature PRs with `gh pr create`.

## Auto-deploy pipeline

Defined in `.github/workflows/deploy-api.yml`. Triggers on push to `master` when files under `api/**` change.

Steps the pipeline runs on the server via SSH:
1. `git pull origin master`
2. `composer install --no-dev --optimize-autoloader`
3. `doctrine:migrations:migrate --no-interaction --env=prod`
4. `cache:clear --env=prod`
5. `sudo systemctl reload php8.4-fpm`

## SSH keys

Two separate key pairs:

**GitHub Actions → server** (`acroyoga_actions`)
- Private key stored as `DEPLOY_SSH_KEY` in GitHub repo secrets
- Public key in `/home/deploy/.ssh/authorized_keys` on server

**Server → GitHub** (`github_deploy`)
- Generated on the server, added as a deploy key on the GitHub repo
- Configured in `/home/deploy/.ssh/config` on the server
- Used by `git pull` inside the deploy script

## Server environment

Server-only, never committed:

```
# /var/www/acroyoga/api/.env.local
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<generated>
DATABASE_URL=pgsql://acro_user:<password>@localhost:5432/acroyoga?serverVersion=17&charset=utf8
```

## Caddyfile

```
# /etc/caddy/Caddyfile
danielschwabe.com, www.danielschwabe.com {
    handle_path /api* {
        root * /var/www/acroyoga/api/public
        php_fastcgi unix//run/php/php8.4-fpm.sock
        file_server
    }
    root * /var/www/acroyoga/frontend
    file_server
}
```

## Sudoers

`deploy` user can reload php-fpm without a password:

```
# /etc/sudoers.d/deploy-fpm  (mode 0440)
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.4-fpm
```

## Local dev

| What | Detail |
|---|---|
| PHP | 8.4 via winget (`PHP.PHP.8.4`) |
| Composer | Official Windows installer |
| Symfony CLI | GitHub release → `C:\tools\symfony.exe` |
| Database | SQLite, `api/var/data_dev.db` (gitignored) |

Local env file (gitignored):

```
# api/.env.local
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=local_dev_secret_not_for_production
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_dev.db"
```

Verify setup: `php bin/console list`
