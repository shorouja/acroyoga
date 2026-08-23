#!/usr/bin/env bash
#
# Pull acroyoga DB backups from the prod server to this machine.
#
# Run this LOCALLY (e.g. in WSL2) whenever you want an off-box copy — "every
# now and then". rsync only transfers dumps you don't already have, so it is
# safe and cheap to re-run.
#
# Server host + SSH user are read from $ACRO_SSH (never hardcoded, so no server
# details land in the repo). The SSH account must be able to read the backup
# dir; if it is root-owned, uncomment the --rsync-path line below to read via sudo.
#
# Usage:
#   ACRO_SSH=youruser@danielschwabe.com ./infra/pull-backups.sh
#
set -euo pipefail

: "${ACRO_SSH:?Set ACRO_SSH=user@host, e.g. ACRO_SSH=admin@danielschwabe.com}"

REMOTE_DIR="${ACRO_REMOTE_DIR:-/var/backups/acroyoga}"
DEST="${ACRO_LOCAL_DIR:-$HOME/acroyoga-backups}"

mkdir -p "$DEST"

rsync -avz --ignore-existing \
  "$ACRO_SSH:$REMOTE_DIR/" "$DEST/"
  # If the remote backup dir is root-owned, read it via sudo instead:
  # --rsync-path="sudo rsync" \

echo "backups pulled to: $DEST"
ls -lh "$DEST"
