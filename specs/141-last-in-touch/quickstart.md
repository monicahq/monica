# Quickstart: verifying Last in touch

This feature adds migrations. The agent never runs them against the local database; you run
`php artisan migrate` yourself before testing by hand.

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CreateLifeEventTest.php tests/Unit/Actions/UpdateLifeEventTest.php tests/Unit/Actions/DestroyLifeEventTest.php
```

```bash
php artisan test --compact --filter=LastInTouch
```

`php artisan monica:localize --check` is expected to fail until translations are run (research R7).

## By hand, at monica.test, in English

1. Open a contact with no life event: **Last in touch** reads "never".
2. Record a Meal with that contact dated last week: the header reads "1 week ago", and the
   contact list's **Last spoke** column shows the same date.
3. Record a Move dated today: the header does not change.
4. In Settings, Life event types, open Move: the switch reads "Updates “Last in touch”" and is off.
   Turn it on and save: after the queue runs, the contact reads "today".
5. Delete the Move: the header returns to "1 week ago".
6. Record a Call dated tomorrow: nothing changes today; tomorrow, after the 02:15 job, it reads "today".
