# Quickstart: Product changelog

1. `php artisan migrate`, sign in, and see "What's new" in the top bar with the count of unseen entries.
2. Add `changelog/test-fix.md` with a fresh ULID (`php artisan tinker --execute 'echo Str::ulid();'`),
   `type: fix` and `published_at` of now. Reload any page: the badge grows by one, with no command run.
3. Open What's new: today's group shows the feature first and the fix under "1 fix"; both carry "New".
   Reload: no "New", no badge.
4. Switch to "Last 3 months": the four figures and the days change together.
5. Vote up on the fix, then down, then down again: one vote, then none.
6. Post a public anonymous comment and a private one. Sign in as another person: one comment, shown
   as "Anonymous", count 1.
7. As an administrator, open Admin space › Changelog: both comments under To review with their real
   author. Reply to the private one (an email is sent), hide the public one, check What's new.
8. `php artisan monica:localize --check` fails until the test entry is translated; delete it.
9. `php artisan test --compact --filter=Changelog`, `vendor/bin/phpstan analyse --no-progress`.
