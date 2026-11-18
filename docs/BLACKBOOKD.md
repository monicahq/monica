# BlackBookd product plan

BlackBookd is a self-hosted fork of [Monica](https://github.com/monicahq/monica) v5 (Laravel 12, Inertia) for a single queer adult user and the **GayHook** agent. It keeps Monica’s vault/contact architecture and AGPL-3.0 lineage; private relationship/health/dating data lives in an opt-in module behind `accounts.blackbookd_enabled`.

## Architecture fit

| Layer | Approach |
|-------|----------|
| Data | New `blackbookd_*` tables only; no upstream schema edits beyond three account flags |
| Code | `app/Domains/BlackBookd/*` isolated domain |
| UI | Vault tab + settings page; no changes to core Monica modules |
| API | Sanctum + `read`/`write` abilities, same as existing vault API |
| Privacy | Module off by default; dashboard blur option; JSON export; no third-party calls |

## MVP (this PR)

1. **Encounters** — datetime, metro `location_label`, how met (app/venue/friend + apps), activity tags, protection, substances, rating, private/consent notes, many contacts.
2. **Contact sexual-health profile** — HIV/U=U, PrEP, last test (reported), kinks, boundaries, relationship type.
3. **Owner health log** — STI tests, PrEP, doxyPEP, vaccines; STI entries can schedule a 90-day reminder on your vault self-contact via Monica reminders.
4. **Partner notification helper** — contacts with encounters in configurable lookback from a positive test date.
5. **Dating pipeline (GayHook)** — per-contact row keyed by handle: FetLife/apps, role/looking for, stage board, last message, next step, red flags, phone/boundary flags, long-term fit 1–10; quiet query and weekly roundup API.
6. **REST API** — full CRUD + pipeline upsert-by-handle; documented in `BLACKBOOKD-API.md`.

## Phased later

- Contact module template for profiles/encounters inline on contact page
- Calendar sync for planned meets
- Encrypted-at-rest field level encryption
- DAV/CardDAV export of non-sensitive pipeline fields
- Multi-vault rollup for GayHook without vault id
- Automated FetLife import (manual/API only in MVP)

## Data model summary

- `blackbookd_encounters` + `blackbookd_encounter_contact`
- `blackbookd_contact_profiles` (1:1 contact)
- `blackbookd_health_log_entries`
- `blackbookd_dating_pipelines` (1:1 contact, unique `vault_id` + `handle`)

## Docker

Build from repo dev Dockerfile (same as upstream Monica beta):

```bash
docker build -f scripts/docker/Dockerfile -t blackbookd:local .
```

Production images: follow [monicahq/docker](https://github.com/monicahq/docker); this fork uses the in-repo `scripts/docker/Dockerfile` for dev/CI.

## Upstream merge strategy

Keep BlackBookd changes in `app/Domains/BlackBookd`, one migration, thin hooks (`routes/*`, `VaultIndexViewHelper`, `SettingsIndexViewHelper`, `bootstrap/app.php`). Rebase onto `monicahq/monica` main by replaying the migration and domain folder; resolve route registration conflicts only.