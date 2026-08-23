# Database Backups

PostgreSQL backups for the `acroyoga` prod database. Current strategy: **daily
local dumps on the server**, pulled to a personal machine occasionally. Cloud
offsite (Backblaze B2 / S3) is planned later — see [Going offsite](#going-offsite-later).

| | |
|---|---|
| What | `pg_dump` of the `acroyoga` DB, gzipped |
| Where (server) | `/var/backups/acroyoga/acroyoga-YYYY-MM-DD.sql.gz` |
| Schedule | Daily 03:30 (cron) |
| Retention | 14 days on the server (`ACRO_RETENTION_DAYS`) |
| Offsite | Manual pull to your machine (`infra/pull-backups.sh`); cloud = TODO |
| Encryption | None yet — plaintext gzip (server → your machine). Add gpg before any cloud/offsite that leaves your infrastructure (dumps contain user PII). |

## One-time server setup

Run once on the prod server, as the admin account that already has passwordless
`sudo` (the deploy user). Replace `ADMIN_USER` with that account name.

```bash
# 1. Backup dir, owned by the admin account so dumps can be pulled without root.
sudo mkdir -p /var/backups/acroyoga
sudo chown ADMIN_USER:ADMIN_USER /var/backups/acroyoga
sudo chmod 700 /var/backups/acroyoga

# 2. Make the script executable (it arrives via `git pull` at /var/www/acroyoga).
chmod +x /var/www/acroyoga/infra/backup.sh

# 3. Smoke-test it once.
/var/www/acroyoga/infra/backup.sh
ls -lh /var/backups/acroyoga/

# 4. Install the daily cron job (runs as the admin user).
sudo tee /etc/cron.d/acroyoga-backup >/dev/null <<'CRON'
# m h dom mon dow user  command
30 3 * * * ADMIN_USER /var/www/acroyoga/infra/backup.sh >> /var/log/acroyoga-backup.log 2>&1
CRON
sudo touch /var/log/acroyoga-backup.log
sudo chown ADMIN_USER:ADMIN_USER /var/log/acroyoga-backup.log
```

The admin account needs passwordless `sudo -u postgres pg_dump`. It typically
already has broad sudo (used for `systemctl reload caddy` in the deploy). If not,
add a sudoers rule permitting `sudo -u postgres pg_dump`.

## Pulling backups to your machine

Run locally (WSL2 recommended) whenever you want an off-box copy:

```bash
ACRO_SSH=ADMIN_USER@danielschwabe.com ./infra/pull-backups.sh
```

- Pulls only dumps you don't already have into `~/acroyoga-backups/`.
- Override the destination with `ACRO_LOCAL_DIR=/some/path`.
- If the server backup dir is root-owned instead of admin-owned, uncomment the
  `--rsync-path="sudo rsync"` line in `pull-backups.sh`.

Just the latest dump, no rsync:

```bash
scp ADMIN_USER@danielschwabe.com:/var/backups/acroyoga/acroyoga-$(date +%F).sql.gz .
```

### From Windows (PowerShell)

Windows OpenSSH already has the working `deploy` key (it's what VSCode Remote-SSH
uses), but Windows has no `rsync` — use `scp`. Create the destination folder
first, or scp errors with `open local ... Unknown error`:

```powershell
mkdir "$HOME\acroyoga-backups" -Force
# All dumps (dataset is tiny, so re-copying everything is fine):
scp deploy@danielschwabe.com:/var/backups/acroyoga/* "$HOME\acroyoga-backups\"
dir "$HOME\acroyoga-backups"
```

To use the WSL `rsync` path instead, copy your Windows SSH key + config into WSL
once (see below), then `infra/pull-backups.sh` works from WSL.

### Enabling the WSL rsync path (one-time)

The Windows SSH key lives on the C: drive, mounted in WSL at
`/mnt/c/Users/<you>/.ssh/`. Copy the key + config into WSL and fix permissions
(SSH refuses keys that are group/world-readable):

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
WIN_SSH=/mnt/c/Users/DanielArbeit/.ssh          # adjust to your Windows user
cp "$WIN_SSH"/config ~/.ssh/ 2>/dev/null || true
cp "$WIN_SSH"/id_* ~/.ssh/ 2>/dev/null           # copies both private + .pub keys
chmod 600 ~/.ssh/id_* ~/.ssh/config 2>/dev/null
chmod 644 ~/.ssh/*.pub 2>/dev/null || true
ssh deploy@danielschwabe.com "echo ok"           # verify: should print ok
```

Then the rsync pull works from WSL and only transfers new dumps:

```bash
ACRO_SSH=deploy@danielschwabe.com ./infra/pull-backups.sh
```

## Restoring (test this — an untested backup is not a backup)

Restore into a **scratch database** first to verify a dump, never straight over
prod:

```bash
# On the server, into a throwaway DB:
sudo -u postgres createdb acroyoga_restore_test
gunzip -c /var/backups/acroyoga/acroyoga-2026-08-23.sql.gz \
  | sudo -u postgres psql acroyoga_restore_test
# ... inspect, then drop it:
sudo -u postgres dropdb acroyoga_restore_test
```

To restore for real over prod (destructive — be sure):

```bash
sudo systemctl stop php8.4-fpm            # stop the app writing
sudo -u postgres dropdb acroyoga
sudo -u postgres createdb -O acro_user acroyoga
gunzip -c <dump>.sql.gz | sudo -u postgres psql acroyoga
sudo systemctl start php8.4-fpm
```

## Going offsite later

When moving to Backblaze B2 / S3:

1. Add a `gpg --encrypt` step in `infra/backup.sh` (marked with a `TODO` there) —
   PII must not leave your infrastructure in plaintext.
2. Add an upload step (`rclone copy` / `aws s3 cp`) after encryption.
3. Set a bucket lifecycle rule for offsite retention (keep local retention too).
