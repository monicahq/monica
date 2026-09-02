# Quickstart: running and checking the reminder rule

What the spec's acceptance scenarios do not carry: the commands. The scenarios themselves are in
[spec.md](spec.md) and are not repeated here.

## Get the table in place

`create_fields_table` is edited rather than added to, so the database has to be rebuilt rather than
migrated forward.

```bash
php artisan migrate:fresh --seed
```

## Drive it by hand

```bash
composer dev
```

Sign in as the seeded account, `michael.scott@dundermifflin.com` / `password`, then create a vault and
a contact. Open a vault, open a contact, and edit a block holding a date. The supplied contact template carries
one: **Birthday**, a recurring date in the **Personal details** section. Tick **Add a reminder**, and
the options appear under it. Save, then reopen the block: everything reads back. Untick, save, and the
date stays while the rule goes.

A plain `date` field is the other half of FR-001 and the template does not supply one, so add a
`date` field to a draft section version and publish it before walking that path.

The two paths worth walking deliberately, because neither is on the happy route:

- A date **inside a repeater**, which gets its own rule per repetition. Add a repeater with a date to
  a draft section version, publish it, move a record onto it.
- The **quiet** states. Archive the contact from the record header, and the rule says so; unarchive,
  and it is back. Then remove the field from the structure in a new template version and move the
  record onto it, and the rule says the field is gone and stays put.

## Check it

```bash
php artisan test --compact tests/Unit/Actions/CreateReminderTest.php
php artisan test --compact --filter=reminder
composer test
```

## Before opening the pull request

```bash
php artisan monica:localize          # then translate everything it reports, in all nine locales
php artisan monica:localize --check  # counted phrases and French typography
vendor/bin/pint --dirty --format agent
```
