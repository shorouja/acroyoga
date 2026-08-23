#!/usr/bin/env bash
#
# Daily PostgreSQL backup for the acroyoga prod database.
#
# Dumps the `acroyoga` DB, gzips it into a dated file, and prunes dumps older
# than the retention window. Designed to be run from cron (see docs/backups.md).
#
# Run as an account that has passwordless `sudo -u postgres` (pg_dump uses the
# local socket + peer auth, so no DB password is needed). The backup directory
# is owned by that account so backups can later be pulled off-box over rsync
# without root (see infra/pull-backups.sh).
#
set -euo pipefail

DB_NAME="${ACRO_DB_NAME:-acroyoga}"
BACKUP_DIR="${ACRO_BACKUP_DIR:-/var/backups/acroyoga}"
RETENTION_DAYS="${ACRO_RETENTION_DAYS:-14}"

mkdir -p "$BACKUP_DIR"

DUMP="$BACKUP_DIR/${DB_NAME}-$(date +%F).sql.gz"

# pg_dump runs as the postgres system user (peer auth on the local socket);
# gzip runs as the calling user and writes the dated dump.
sudo -u postgres pg_dump "$DB_NAME" | gzip > "$DUMP"

# TODO (offsite, when moving beyond local): upload "$DUMP" to Backblaze B2 / S3
# here, and gpg-encrypt it FIRST — dumps contain user PII and must not leave
# your own infrastructure in plaintext. e.g.:
#   gpg --encrypt --recipient <you> "$DUMP" && rclone copy "$DUMP.gpg" b2:acroyoga-backups/

# Retention: drop local dumps older than the window.
find "$BACKUP_DIR" -name "${DB_NAME}-*.sql.gz" -mtime "+${RETENTION_DAYS}" -delete

echo "backup written: $DUMP"
