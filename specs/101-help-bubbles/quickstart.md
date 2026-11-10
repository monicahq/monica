# Quickstart: Help bubbles

Prerequisites: Herd serving the checkout at `https://erica.test`, assets built (`bun run build`), signed in.

## Automated

```bash
php artisan test --compact tests/Feature/Views/Components/HelpTest.php tests/Unit/ViewModels/Docs
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

Expected: all pass; the coverage test names any `<x-help` without a guide or with an unknown one.

## By hand, on `https://erica.test/settings/account/profile`

| Check | Expected |
| --- | --- |
| Point at the "?" beside **Email address** | Bubble opens; moving into it keeps it open; leaving both closes it shortly after |
| Tab to the mark | Focus ring visible, nothing opens |
| Enter | Opens; Tab reaches "Read the guide" |
| Escape from inside | Closes, focus back on the mark |
| VoiceOver on the mark | "Help about the email address, button, collapsed", then "expanded" |
| Follow "Read the guide" | `/docs/en/your-account-and-security/your-profile` in a new tab; the form keeps its text |
| 320 px wide, and 200 % zoom | Bubble readable, no horizontal scroll |
| Dark theme | Glyph and text readable |
| System reduced motion on | Bubble appears without fading |
| Open the API key form, tick through the sensitive fields bubble | Same behaviour, guide is the API keys page |
