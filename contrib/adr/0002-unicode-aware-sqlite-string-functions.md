# 0002. Unicode-aware string functions on SQLite

- **Status:** Accepted
- **Date:** 2026-10-25
- **Applies to:** `api/src/Doctrine/**`, `api/src/Filter/Handler/Database/**`, any DQL/SQL using string functions

## Context

SQLite is the production database by default (`DATABASE_URL=sqlite:///…` in `build/base/*Dockerfile`). Its
built-in `LOWER()` and `UPPER()` only change ASCII letters. Case-insensitive name filters compare
`LOWER(column)` with a value lowercased in PHP (`mb_strtolower`), so on SQLite a stop like "Żabianka" was never
found case-insensitively, not even when typed exactly. MySQL and Postgres lowercase Unicode correctly.

## Considered options

1. **Replace `LOWER()` on SQLite at connection time** with a deterministic, NULL-safe PHP function
   (`Pdo\Sqlite::createFunction`) in a DBAL driver middleware. Queries stay the same on every database; the
   cost is a PHP callback per evaluated row, negligible at our table sizes.
2. **A normalised search column** (e.g. `name_search`, lowercased on import) with migrations for all three
   engines. Indexable and engine-independent, but every importer and every searched field must maintain it.
3. **Lowercase in PHP only** and compare with `LIKE` relying on SQLite's ASCII-only case folding. Doesn't fix
   non-ASCII letters.
4. **ICU extension for SQLite.** Not available in the stock PHP images.

## Decision

We will make SQLite's string functions behave like MySQL's and Postgres' by registering Unicode-aware
replacements on the native connection in `App\Doctrine\SqliteUnicodeMiddleware`. Today that is `LOWER()` only.
Code keeps writing portable DQL (`LOWER(…)`) and doesn't branch on the platform.

## Consequences

- Name filters ignore case for all letters on every supported database (pinned by the stop-search scenarios).
- `LOWER()` on SQLite is computed in PHP: no index can be used for it (there was none anyway, as the filters use
  leading wildcards), and it only works through connections made by the application (not the `sqlite3` CLI).
- If more string functions are needed (`UPPER()`), they are added to the same middleware.
- Requires PHP ≥ 8.4 `Pdo\Sqlite`; on a plain `PDO` connection the middleware does nothing and the
  Polish-letter scenarios fail.

## Compliance

- SQL/DQL that needs case-insensitive matching uses `LOWER()` and does not branch on the database platform.
- SQLite-specific function replacements live in `api/src/Doctrine/SqliteUnicodeMiddleware.php` (and its driver),
  registered for the SQLite native connection only; they must keep `NULL` → `NULL` and be deterministic.
- A replacement function is covered by a Behat scenario using non-ASCII data.
