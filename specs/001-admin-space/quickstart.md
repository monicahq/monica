# Quickstart: validating the Admin Space

**Feature**: `001-admin-space` | **Date**: 2026-08-21

How to prove the feature works, by hand and by suite. Shapes and endpoints are in
`data-model.md` and `contracts/`, and are not repeated here.

## Prerequisites

- The repository on branch `2026-08-20-admin-space`, dependencies installed
  (`composer install`, `npm install`).
- A database configured in `.env`. The suite must pass on SQLite, MySQL and PostgreSQL.
- A queue worker for the paths that dispatch jobs, or `QUEUE_CONNECTION=sync` while validating
  by hand. The administrator record and the decision emails are queued.

## Setup

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Designate the first administrator, which is the only step that cannot be done in the interface:

```bash
php artisan monica:make-administrator michael.scott@dundermifflin.com
```

It prints a confirmation. Running it for an address nobody holds prints an error and changes
nothing.

## Scenario 1: the space refuses everybody else (FR-002, SC-005)

1. Signed out, open `/admin`. Expect a `404`, not a sign in screen.
2. Signed in as a user with no capacity, open `/admin`, `/admin/accounts`, `/admin/logs`.
   Expect a `404` each time, indistinguishable from `/admin/nonsense`.
3. Signed in as the administrator, open `/admin`. Expect the overview.

## Scenario 2: moderate a submission (Story 1)

1. As a regular user, build a template and submit it to the marketplace.
2. As the administrator, open `/admin/moderation`. Expect the submission listed with author,
   category, submitted date and waiting time.
3. Open it. Expect the description and the payload of the version.
4. Approve it. Expect the listing installable in `/marketplace`, `moderated_by` set to the
   administrator's name, and the author to receive the email (check `EmailSent` or the log of
   the mailer).
5. Submit a second listing and reject it with no reason. Expect the rejection refused and the
   reason asked for.
6. Reject it with a reason. Expect the listing unpublished, the reason readable by the author,
   and a new submission still possible afterwards.
7. Approve the same version twice, in two tabs. Expect the second attempt refused, stating the
   decision that stands.
8. Approve a second version of a listing somebody already installed. Expect their installation
   untouched, still on the version they installed.

## Scenario 3: look up an account (Story 2)

1. Create an account with a vault and a few contacts.
2. Search its full email address in `/admin/accounts`. Expect one result, openable.
3. Search a partial name and a partial email. Expect matches. Search nonsense. Expect a stated
   empty result, not a blank screen.
4. Open the account. Expect every fact in FR-019 and FR-020, counts only for vaults and
   contacts, and no link that leads to a contact, a field value or a file.
5. Set `storage_limit_in_bytes` to null on that account. Expect the screen to read unlimited,
   not zero.
6. Soft delete the user. Expect the screen to still render, with the deletion stated.

## Scenario 4: act on an account (Story 3)

1. Deactivate the user while they are signed in elsewhere. Expect a warning first, and their
   next request to fail as not signed in.
2. Reactivate. Expect them able to sign in, with everything they owned intact.
3. Lower the storage limit below usage. Expect a warning, no file deleted, and the next upload
   refused.
4. Grant lifetime access. Expect it reflected immediately and attributed.
5. Open `/admin/logs`. Expect every one of the above with who, what, which account and when.
6. Try to deactivate your own user. Expect a refusal with an explanation.
7. Change your own storage limit. Expect it applied and recorded, since a single administrator
   instance must not be stuck.

## Scenario 5: manage administrators (Story 4)

1. Open `/admin/administrators`. Expect everybody holding the capacity.
2. Grant it to a second user by email. Expect a warning, then that user able to reach `/admin`.
3. Grant it to an address nobody holds. Expect a refusal saying no such user exists.
4. From the second administrator, withdraw the first. Expect a warning, then the first
   administrator to get a `404` on the screen they already had open.
5. Try to withdraw your own capacity. Expect a refusal with an explanation.
6. With one administrator left, try to withdraw them. Expect a refusal stating an instance must
   keep at least one.
7. Deactivate an administrator. Expect the list to show them as unable to administer.

## Scenario 6: the overview (Story 5)

1. Seed a known number of accounts, users and pending submissions.
2. Open `/admin`. Expect every figure to match, and each to state the period it covers.
3. On a fresh instance with no data, expect zeros that read as an empty instance.
4. Click the waiting count. Expect the moderation queue in one step.

## Suite

```bash
./vendor/bin/phpunit
```

Targeted while working:

```bash
./vendor/bin/phpunit --filter Administrator
```

Expected coverage before the feature is done, per the constitution:

- Unit tests for every new action, the two changed moderation actions, both new models, the new
  enum, the new job, the two new mailables, the console command and every new view model.
- Feature tests for every controller in `contracts/routes.md`, each covering the `404` for a
  non administrator, the happy path, and the refusals the spec names.
- A test that no screen of the admin space exposes vault content (FR-021, SC-006).

## Gates

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
php artisan monica:localize --check
```

The last one must report nothing missing in any of the nine locales (FR-034, SC-008). Run
`php artisan monica:localize` without `--check` first, then translate everything it reports.
