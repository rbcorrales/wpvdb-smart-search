# AGENTS.md - wpvdb-smart-search

Agent guidance for this repository. General project information belongs in `README.md`; keep this file focused on implementation rules that help future agents avoid bad edits.

## Boundaries

- Do not add credentials, tokens, application passwords, site-specific hostnames, or private deployment details.
- Keep the public REST response shape stable unless the React client is updated in the same change.
- Shared search algorithms belong in `https://github.com/rbcorrales/wpvdb-search`. This plugin should stay focused on the Smart Search page UI, public REST adapter, examples, and prewarm command.
- Do not add new dense, sparse, ranking, or schema behavior here if it can live in `wpvdb-search`.

## Runtime Contracts

- The plugin slug is `wpvdb-smart-search`.
- The public page stays at `/smart-search/`.
- The REST namespace stays `wpvdb-smart-search/v1`.
- Display text uses the `wpvdb-smart-search` text domain.
- The server returns ranked rows; the React client may group rows by post but should not re-rank them.
- Search execution is delegated to `WPVDB_Search\Search`.

## Development Notes

- Build and lint commands are defined in `package.json` and `composer.json`; prefer those scripts instead of inventing ad hoc commands.
- Keep `/smart-search` served without requiring a rewrite flush.
- Keep asset cache busting based on `filemtime()` unless the build or release flow changes.
- Preserve dark mode through CSS custom properties and the `data-theme` attribute.
