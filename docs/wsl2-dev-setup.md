# Local Dev Environment — WSL2 Full Toolchain

**Decided 2026-07-05.** Run the *entire* local dev stack — PHP 8.4, Composer,
Symfony CLI, Docker, PostgreSQL 17 — inside a WSL2 Linux distro, not on Windows
and not via Docker Desktop.

**Why:**
- Windows PHP has no `pdo_pgsql`; Linux PHP gets it from a single `apt` package.
- Docker Engine running natively inside WSL2 avoids the Docker Desktop VM overhead.
- Keeping the repo on the Linux filesystem (`~/dev/...`, not `/mnt/d/...`) avoids
  the slow Windows↔WSL9P filesystem bridge that makes Composer, PHPUnit, and
  file-watchers crawl.
- The distro mirrors the **Debian 13** production server, so "works locally"
  means much more (dev/prod parity).

> **Before you start — check the Windows clone.** As of 2026-07-05 all work is
> pushed and `dev`/`master` are aligned, so nothing is stranded. Before you stop
> using the Windows copy at `d:\dev\acroyoga`, run `git status` there and push any
> uncommitted work or local-only branches.

## Progress checklist

Time estimates are first-run wall-clock (hands-on + downloads). Setup total:
**~60–90 min if smooth; budget 1.5–2 hrs** with troubleshooting. Step 11 is a
separate work item, not setup.

- [ ] **1. Install WSL2 + Debian** — `wsl --install -d Debian`, reboot, create user _(10–20 min, low)_
- [ ] **2. Enable systemd** — `/etc/wsl.conf` + `wsl --shutdown` _(~5 min, trivial)_
- [ ] **3. Base packages + Git + GitHub SSH key** — keygen → add on github.com → `ssh -T` _(10–15 min, low-med; manual web step)_
- [ ] **4. Docker Engine in WSL2** — repo, install, `usermod -aG docker`, restart, `hello-world` _(10–15 min, ⚠️ med; group change needs a restart)_
- [ ] **5. PHP 8.4 + `pdo_pgsql`** via Sury repo _(5–10 min, low-med — the payoff step)_
- [ ] **6. Composer + Symfony CLI** _(~5 min, low)_
- [ ] **7. Clone repo onto ext4 (`~/dev/…`) + `composer install`** _(5–15 min, low)_
- [ ] **8. `.env.local` + `lexik:jwt:generate-keypair`** _(~5 min, low)_
- [ ] **9. `docker compose up -d db` + `doctrine:schema:create`** _(~5 min, low; first image pull ~1–2 min)_
- [ ] **10. Verify** — `dbal:run-sql "SELECT version()"`, DDL dump, `phpunit` _(~5 min, low)_
- [ ] **12. VS Code Remote-WSL** — install extension, `code .` _(~5 min, low)_
- [ ] **11. Regenerate Postgres migration** — *separate work item, ~30–60 min:* `make:migration` on empty local Postgres → review DDL → reconcile with prod's already-recorded version

**Likely time sinks:** systemd not starting after step 2; Docker "cannot connect" until the post-`usermod` restart; Sury key/repo quirks on a newer Debian base; forgetting to add the GitHub SSH key before the clone.

---

## 0. Prerequisites

- Windows 11 (you're on 11 Pro — good).
- Admin rights for the one-time WSL install.
- ~5 GB free for the distro + images.

This guide uses **Debian** (closest to the prod server). Ubuntu works too — where
it differs, the Ubuntu variant is noted inline. Note the WSL Debian image may be
Debian 12 (bookworm); that's fine — PHP 8.4 comes from the Sury repo regardless of
base version.

---

## 1. Install WSL2 + Debian

In an **elevated PowerShell** (Run as Administrator):

```powershell
wsl --install -d Debian
```

This enables the WSL2 feature and installs Debian. Reboot if prompted. On first
launch of the Debian terminal you'll create a Linux username + password (this
account gets `sudo`).

Confirm you're on WSL **2** (not 1):

```powershell
wsl -l -v      # VERSION column must show 2
```

---

## 2. Enable systemd inside WSL2

systemd lets `systemctl` manage the Docker daemon. Inside the Debian shell:

```bash
sudo tee /etc/wsl.conf >/dev/null <<'EOF'
[boot]
systemd=true
EOF
```

Then, back in **PowerShell**, fully restart the distro:

```powershell
wsl --shutdown
```

Reopen Debian and verify:

```bash
systemctl is-system-running    # "running" or "degraded" is fine; "offline" means systemd didn't start
```

---

## 3. Base packages + Git + GitHub access

```bash
sudo apt update && sudo apt -y upgrade
sudo apt -y install ca-certificates curl gnupg lsb-release git unzip
```

Set up SSH access to GitHub (so clones/pushes work from WSL):

```bash
ssh-keygen -t ed25519 -C "daniel.schwabe@dexpro.de"    # accept defaults
cat ~/.ssh/id_ed25519.pub
```

Add that public key at <https://github.com/settings/keys>. Test:

```bash
ssh -T git@github.com    # expect "Hi <user>! You've successfully authenticated"
```

Set your Git identity:

```bash
git config --global user.name "Daniel Schwabe"
git config --global user.email "daniel.schwabe@dexpro.de"
```

---

## 4. Install Docker Engine (native, inside WSL2)

Docker's official Debian repo (for Ubuntu, swap `debian` → `ubuntu` in both the
key URL and the repo line):

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null

sudo apt update
sudo apt -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Run Docker without `sudo`, and start it:

```bash
sudo usermod -aG docker "$USER"
sudo systemctl enable --now docker
```

Apply the group change: run `wsl --shutdown` in PowerShell and reopen, then:

```bash
docker run --rm hello-world     # should pull and print a success message
```

---

## 5. Install PHP 8.4 + extensions (this is the fix for `pdo_pgsql`)

Add the Sury PHP repo (skip if your base distro already ships PHP 8.4, e.g. Debian
13/trixie — for Ubuntu use `ppa:ondrej/php` via `add-apt-repository` instead):

```bash
curl -fsSL https://packages.sury.org/php/apt.gpg | sudo tee /etc/apt/trusted.gpg.d/sury-php.gpg >/dev/null
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/sury-php.list >/dev/null
sudo apt update
```

Install PHP 8.4 CLI + the extensions this project needs (`pgsql` is the one that
was missing on Windows; `sqlite3` keeps the current SQLite test path working too):

```bash
sudo apt -y install \
  php8.4-cli php8.4-common php8.4-pgsql php8.4-sqlite3 \
  php8.4-mbstring php8.4-xml php8.4-intl php8.4-curl php8.4-zip php8.4-opcache
```

Confirm the driver is present:

```bash
php -v
php -m | grep -E 'pdo_pgsql|pdo_sqlite'    # both should print
```

---

## 6. Install Composer + Symfony CLI

**Composer** (system-wide):

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

**Symfony CLI:**

```bash
curl -sS https://get.symfony.com/cli/installer | bash
sudo mv ~/.symfony5/bin/symfony /usr/local/bin/symfony
symfony version
```

---

## 7. Clone the repo into the Linux filesystem

Do **not** work out of `/mnt/d/dev/acroyoga` — that's the Windows disk over the
slow 9P bridge. Clone fresh into ext4:

```bash
mkdir -p ~/dev && cd ~/dev
git clone git@github.com:shorouja/acroyoga.git
cd acroyoga
```

Install PHP dependencies:

```bash
cd api
composer install
```

---

## 8. Environment files

Create `api/.env.local` (gitignored) with the local dev settings. The
`DATABASE_URL` credentials must match the `db` service in the root
`docker-compose.yml`:

```dotenv
# api/.env.local
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=local_dev_secret_not_for_production
DATABASE_URL="postgresql://acro_user:local_dev_password@127.0.0.1:5432/acroyoga?serverVersion=17&charset=utf8"
JWT_PASSPHRASE=local_dev_jwt_passphrase
```

Generate the JWT keypair (needed for `POST /api/login`; `POST /api/register`
works without it):

```bash
php bin/console lexik:jwt:generate-keypair
```

---

## 9. Start PostgreSQL + build the schema

From the **repo root** (uses the project's `db` service, not the api/ recipe file):

```bash
cd ~/dev/acroyoga
docker compose up -d db
```

Build the local schema **from entity metadata**, not from the migration:

```bash
cd api
php bin/console doctrine:schema:create
```

> ⚠️ **Do NOT run `doctrine:migrations:migrate` locally yet.** The committed
> migration (`Version20260621182226`) is SQLite-dialect (`AUTOINCREMENT`, `CLOB`)
> and will error on PostgreSQL. Use `doctrine:schema:create` until that migration
> is regenerated for Postgres (ROADMAP → Mid-Term). Regenerating it is exactly
> what this WSL2 env unblocks — see step 11.

---

## 10. Verify the toolchain end-to-end

```bash
cd ~/dev/acroyoga/api

# 1. PHP can actually reach Postgres through pdo_pgsql:
php bin/console dbal:run-sql "SELECT version()"        # prints "PostgreSQL 17.x ..."

# 2. Symfony generates dialect-correct Postgres DDL (read-only, just prints):
php bin/console doctrine:schema:create --dump-sql      # SERIAL / TEXT, not AUTOINCREMENT / CLOB
```

Run the functional tests (they currently target SQLite via `api/.env.test`, so
this proves the app works; optional Postgres-test parity is noted below):

```bash
php bin/console --env=test doctrine:schema:create
php bin/phpunit
```

---

## 11. Regenerate the Postgres migration (the payoff)

With a real local Postgres, you can finally produce a correct migration. Against a
**fresh, empty** database, `make:migration` diffs the entities and emits full
Postgres DDL:

```bash
# fresh empty DB
docker compose down -v && docker compose up -d db      # from repo root; -v wipes the volume
cd api
php bin/console make:migration                          # generates a Postgres-dialect migration
```

Review the generated file, then reconcile with the roadmap item (the old
SQLite-dialect migration is replaced, and prod's already-applied version is
handled). That reconciliation is its own task — this guide only gets you the
environment that makes it possible.

---

## 12. VS Code (Remote-WSL)

1. Install the **WSL** extension in Windows VS Code (once).
2. From the repo inside WSL: `code .` — VS Code reopens attached to the distro,
   editing files on ext4 with the Linux PHP/tools. The integrated terminal is the
   Debian shell.

---

## Gotchas & notes

- **Keep the repo on ext4** (`~/dev/acroyoga`). Editing via `/mnt/d/...` reintroduces
  the exact lag WSL2 is meant to remove.
- **Two compose files exist.** Use the root `docker-compose.yml` (service `db`) — it
  matches `.env.local`. The Symfony recipe's `api/compose.yaml` (service `database`,
  `postgres:16-alpine`, `app/app` defaults) is unconfigured for this project; ignore
  it locally.
- **Never run the SQLite migration on Postgres** until it's regenerated (step 11).
- **Docker daemon on shell start:** with systemd enabled it starts automatically;
  if `docker` ever reports "cannot connect", run `sudo systemctl start docker`.
- **Optional Postgres test parity:** to run functional tests against Postgres instead
  of SQLite, override `DATABASE_URL` in `api/.env.test.local` to a Postgres DSN (the
  `when@test` config appends `_test`, giving `acroyoga_test`). Not required — the
  suite passes on SQLite today.
- **Windows clone:** once WSL2 is your dev env, do all Git work inside WSL. The old
  `d:\dev\acroyoga` copy can be archived after its outstanding branch is pushed.
```
