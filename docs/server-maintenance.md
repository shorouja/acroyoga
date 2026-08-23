# Server Maintenance

Operational upkeep for the prod server (Debian 13, netcup). See also
[backups.md](backups.md) and [deployment.md](deployment.md).

## Automatic security updates (unattended-upgrades)

**Policy:** security updates only, auto-applied daily, with an automatic reboot
at 04:00 when a patch (kernel/libc) requires one. Feature/bugfix updates and
third-party repos (Caddy via cloudsmith, PostgreSQL) are **left manual** — on a
single box with no staging, security-only is the low-risk choice, and you keep
control over everything else.

### Install

```bash
sudo apt update
sudo apt install -y unattended-upgrades apt-listchanges
```

### Configure

Debian's default `/etc/apt/apt.conf.d/50unattended-upgrades` already restricts
to the security origins, so we don't edit it. A small drop-in adds the reboot
policy (later-numbered files override earlier ones, so `52…` wins over `50…`):

```bash
sudo tee /etc/apt/apt.conf.d/52acroyoga-unattended >/dev/null <<'CONF'
// Reboot automatically when an update needs it, at a low-traffic hour.
Unattended-Upgrade::Automatic-Reboot "true";
Unattended-Upgrade::Automatic-Reboot-Time "04:00";
// Clean up dependencies/kernels left behind by upgrades.
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Remove-Unused-Kernel-Packages "true";
CONF
```

Enable the daily schedule (update lists + run the upgrade):

```bash
sudo tee /etc/apt/apt.conf.d/20auto-upgrades >/dev/null <<'CONF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::Unattended-Upgrade "1";
APT::Periodic::AutocleanInterval "7";
CONF
```

### Verify

Dry-run — shows which origins are allowed and what would be upgraded, without
changing anything:

```bash
sudo unattended-upgrades --dry-run --debug 2>&1 | grep -iE "allowed origins|checking|pkgs that|reboot" | head
```

You want to see the Debian security origin in the "Allowed origins" list and no
errors. Confirm the timers are active:

```bash
systemctl status apt-daily.timer apt-daily-upgrade.timer --no-pager | grep -E "Active|Trigger"
```

### What it does / doesn't do

- **Does:** apply Debian security patches daily; reboot at 04:00 only if a patch
  requires it; tidy unused deps/kernels.
- **Doesn't:** touch Caddy, PostgreSQL, or non-security Debian updates — run those
  manually (`sudo apt update && sudo apt upgrade`) during a maintenance window.
- **No email alerts** (no mail server yet). Watch `/var/log/unattended-upgrades/`
  and rely on uptime monitoring to catch a rare bad reboot.

### Caddy repo key note

Caddy is installed from the cloudsmith apt repo, whose GPG signing key **expires
periodically**. If `apt update` warns about a bad/expired Caddy signing key,
refresh it:

```bash
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
  | sudo gpg --dearmor --yes -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
sudo apt update
```

(Last refreshed 2026-08-23 after the key expired 2025-12-28.)
