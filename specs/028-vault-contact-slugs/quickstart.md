# Quickstart: Readable vault and contact addresses

How to see the feature work and how to prove it. Entities are in [data-model.md](data-model.md), the API surface in [contracts/addressing-a-vault-or-a-contact.md](contracts/addressing-a-vault-or-a-contact.md).

## Prepare

```bash
php artisan migrate:fresh --seed --quiet   # required: the slug columns are added to the create migrations, so a database that predates this change must be rebuilt
bun run build
```

## See it

1. Sign in and open a vault. The address reads `/vaults/<the vault name>` rather than 36 characters.
2. Open a contact inside it. The address names the person.
3. Rename that contact. You land on the new address, and the previous one answers 404.
4. Paste the contact's uuid in place of its name. The same screen opens.
5. Create a second contact with the name the first one used to have. It is given a number, and the old address still answers 404 rather than opening them.
6. Name a contact in a script of its own, `田中太郎`. The address carries it.
7. Turn readable addresses off under `settings/account/preferences`. Every address the product builds goes back to uuids, and the readable ones you saved still answer.
8. Merge a contact into another, then open the absorbed contact's address. You are told which contact absorbed it and offered a way through, rather than being sent there.

## Prove it

```bash
php artisan test --compact tests/Unit/Helpers/SlugBuilderTest.php
php artisan test --compact tests/Unit/Models/VaultTest.php tests/Unit/Models/ContactTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults
php artisan test --compact tests/Feature/Controllers/Api
php artisan test --compact --filter=readable
```

What has to be green, and which requirement each one holds up:

| Proof | Requirement |
| --- | --- |
| A vault and a contact answer to the readable name and to the uuid, signed in and through the API | FR-003, FR-004 |
| Another account's address, and an address naming the wrong vault of your own, are not found in both forms | FR-011, SC-003 |
| A rename rebuilds the name, the reader is taken to the new address, and the previous one is not found | FR-005 |
| A name a record has released is refused to every later record in the scope | FR-006, SC-003 |
| A name in a script with no latin letters keeps its characters, and reaches its record encoded or not | FR-007, FR-012, SC-004 |
| A name of only punctuation, emoji, or characters that steer direction, still yields an address, stripped of them | FR-013 |
| A vault or contact named "New", "Favourites" or "Archived" is still reachable, and the route table still matches the reserved list | FR-008 |
| With the preference off every built address carries a uuid, and every readable address still answers | FR-014, SC-005 |
| The whole suite that passed before this change passes after it | SC-002 |

## Before opening the pull request

```bash
php artisan monica:localize          # then translate what it reports, in every locale
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

The pull request must say that `spec.md` is 147 lines against the constitution's 120 line cap, and why.
