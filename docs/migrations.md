# Migrations

Doctrine Migrations tracks schema changes as versioned PHP files in `api/migrations/`.

## How it works

`make:migration` diffs your entity classes against the current database and generates a new file containing only the incremental SQL. The filename is a timestamp (`Version20260621182226`) used to order migrations chronologically.

Doctrine keeps a `doctrine_migration_versions` table in the database. On each `doctrine:migrations:migrate` run it checks which versions are already recorded and skips them — running migrate twice is safe.

Each migration has two methods:
- `up()` — applied going forward
- `down()` — rolls back one version

## SQLite locally, PostgreSQL on the server

The migration PHP class is platform-agnostic. Doctrine emits the correct SQL dialect at runtime based on the connected database — `AUTOINCREMENT` becomes `SERIAL`, `CLOB` becomes `TEXT`, etc. The same migration file runs on both.

## Workflow for schema changes

1. Edit the entity in `api/src/Entity/`
2. `php bin/console make:migration` — generates the diff file
3. `php bin/console doctrine:migrations:migrate` — applies it locally
4. Commit the migration file alongside the entity change
5. The deploy pipeline runs `doctrine:migrations:migrate --env=prod` on the server automatically

## Resetting the local dev database

If you need a clean slate locally:

```bash
rm api/var/data_dev.db
php bin/console doctrine:migrations:migrate --no-interaction
```
