# Quickstart: Banner offering full access to free accounts

## Prerequisites

- The users create migration was edited, so a local database is rebuilt, never patched:
  `php artisan migrate:fresh --seed`.
- `monica.self_hosted` is false, and the account has a storage ceiling (it is metered).

## Automated

```bash
php artisan test --compact --filter='FullAccessOffer|UserTest|ShellTest|AccountLayoutTest'
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

`ShellTest` and `AccountLayoutTest` are new files under `tests/Feature/Views/Layouts`.

## By hand

| Step | Expected |
| --- | --- |
| Sign in as a free account, open the vault list | Banner above the list; its link opens Billing |
| Open a vault, a contact, a settings screen | Banner on each |
| Open Billing, the admin space, the marketplace | No banner |
| Tab to the dismiss control, press Enter | Same screen, no banner; reload, sign in elsewhere: still none |
| Give the account full access, as an administrator | Banner gone on the next screen |
| Set `monica.self_hosted` to true | No banner for anybody |
| Screen reader on the vault list | A named region, its sentence, the link, the control |
| 320 px wide, and zoomed to 200% | Text wraps, nothing scrolls sideways |
| API: `PUT api/me/full-access-offer` with a token | 200, `full_access_offer_dismissed_at` set |
