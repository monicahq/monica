# Quickstart: Manage relationships

How to see the feature working and prove it does. Requirements are in [spec.md](spec.md); decisions in [research.md](research.md).

## Prerequisites

```bash
composer install && bun install
php artisan migrate:fresh --seed
bun run dev          # or composer run dev
```

The seed gives an account with a vault of contacts and the five supplied relationship categories.

## Walk it

1. Sign in, open a vault, open a contact.
2. **Add** two or three relationships through **Add a relationship**, picking natures from different categories, and make one of them a name only rather than an existing person.
3. Open the **Relationships** tab. Each category you used appears with its own heading and count. Categories you did not use do not appear. Each row names the person, their role as read from this contact's side, and their age when it is known.
4. Follow a row for somebody with a record: their contact page opens. The name-only row leads nowhere and says so.
5. Press **Manage** on one group. Edit and Delete appear on that group's rows only.
6. **Edit** a row. Change the nature, swap the direction, mark it ended, give it dates, save. Open the other person's contact: it reads the new roles from their side.
7. **Delete** a row. The warning appears first. Confirm: the row goes, the count drops, and the link is gone from the other person too. The other person's record is still there.
8. Open the contact **Overview**. A partner marked ended in step 6 is no longer counted under Current partner.

## Prove it

```bash
# the whole feature
php artisan test --compact --filter=Relationship

# one target at a time
vendor/bin/phpunit tests/Unit/Actions/UpdateRelationshipTest.php

# nothing else broke: the panel from 030 and the form from 029 share this code
php artisan test --compact
```

## Before it is done

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G      # CI runs level 5
php artisan monica:localize                       # then translate everything it reports
php artisan monica:localize --check               # typography and counted phrases
```

## What should fail

Each of these is a test, and each must refuse rather than half-work:

| Try | Expect |
|---|---|
| An end date before the start date | Refused, with a message, nothing changed |
| A parent change making somebody their own ancestor | Refused, with a message, nothing changed |
| Another account's relationship, by URL | 404, as though it did not exist |
| The right relationship through the wrong vault in the URL | 404 |
| Confirming the same removal twice, by going back | 404, not a 500 |
| Saving a link whose nature the account has since withdrawn | Saves, and keeps reading that nature |
