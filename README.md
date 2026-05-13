# WPVDB Smart Search

WPVDB Smart Search adds a public Smart Search page and REST endpoint for wpvdb dense, sparse, and hybrid search.

It provides a React UI and a public, rate-limited REST adapter for trying dense, sparse, and hybrid search over content indexed by wpvdb.

## Requirements

- WordPress with [`wpvdb`](https://github.com/rbcorrales/wpvdb) and [`wpvdb-search`](https://github.com/rbcorrales/wpvdb-search) installed and configured.
- PHP 8.0 or newer.
- MariaDB with native vector support for dense search.
- Bun for JavaScript dependency management and builds.
- Composer for PHP development tooling.

## What This Plugin Owns

- The `/smart-search/` public page.
- The `wpvdb-smart-search/v1` REST namespace.
- The React UI, browser assets, and public REST adapter.
- Demo examples, placeholders, and UI copy.
- A WP-CLI prewarm command for example query embeddings.

Search execution lives in `wpvdb-search`.

## Development

Install dependencies with Bun and Composer, then use the scripts declared in `package.json` and `composer.json` for build, lint, fix, and release tasks.

The generated asset, dependency, and release directories are intentionally ignored by git.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
