# Migrations

Doctrine Migrations tracks schema changes as versioned PHP files in `api/migrations/`.

## How it works

`make:migration` diffs your entity classes against the current database and generates a new file containing only the incremental SQL. The filename is a timestamp (`Version20260621182226`) used to order migrations chronologically.

Doctrine keeps a `doctrine_migration_versions` table in the database. On each `doctrine:migrations:migrate` run it checks which versions are already recorded and skips them — running migrate twice is safe.

Each migration has two methods:
- `up()` — applied going forward
- `down()` — rolls back one version

## SQLite locally, PostgreSQL on the server

> ⚠️ **Dialect caveat — read this.** A migration file is **not** automatically
> portable across databases. `make:migration` writes raw SQL strings via
> `$this->addSql('CREATE TABLE … AUTOINCREMENT … CLOB …')`, and Doctrine executes
> those strings **verbatim** — it does *not* translate `AUTOINCREMENT`→`SERIAL` or
> `CLOB`→`TEXT` at runtime. Runtime dialect translation only happens for the
> Schema-builder API (`$schema->createTable(...)`), which `make:migration` does not use.
>
> **Consequence:** a migration generated while connected to **SQLite** contains
> SQLite-only SQL and **will fail** if `doctrine:migrations:migrate` is run against
> **PostgreSQL** (and vice-versa). The migration's dialect is fixed at generation
> time by whichever database `make:migration` diffed against.

Local dev currently runs on **SQLite** (`api/var/data_dev.db`) — the machine has no
Docker/`pdo_pgsql`, so `make:migration` here emits SQLite SQL. Production is
**PostgreSQL 17**. Because of the caveat above, the committed migration
(`Version20260621182226`, SQLite-dialect) does **not** replay on the Postgres server.

Two ways to keep the server schema correct until this is reconciled:
- **Regenerate migrations against PostgreSQL** so `migrations:migrate` produces
  Postgres SQL (tracked as a roadmap follow-up: "Regenerate migrations for PostgreSQL").
- **Build schema from entity metadata** with `doctrine:schema:create` — this reads the
  entity mappings and emits DDL for whatever database is connected, so it is
  dialect-correct on either. This is what the test suite uses (see below).

## Workflow for schema changes

1. Edit the entity in `api/src/Entity/`
2. `php bin/console make:migration` — generates the diff file
3. `php bin/console doctrine:migrations:migrate` — applies it locally
4. Commit the migration file alongside the entity change
5. The deploy pipeline runs `doctrine:migrations:migrate --env=prod` on the server automatically

## Test database

Functional tests (`api/tests/`) use a separate database configured in `api/.env.test`
(currently SQLite at `var/test.db`, since local runs on SQLite). The schema is built
from entity metadata rather than migrations — this sidesteps the dialect caveat above:

```bash
cd api
php bin/console --env=test doctrine:schema:create   # dialect-correct from entities
php bin/phpunit
```

## Resetting the local dev database

If you need a clean slate locally:

```bash
rm api/var/data_dev.db
php bin/console doctrine:migrations:migrate --no-interaction
```
