# Quickstart: Error 500 page

## Automated

```bash
php artisan test --compact tests/Feature/Views/Errors/ErrorPageTest.php
php artisan monica:localize --check
```

Expected: every case passes, including the 500 case of the provider, the case asserting the exception
message is absent, and the case rendering with no database connection.

## By hand

1. Set `APP_DEBUG=false` in `.env` and add a temporary route throwing a `RuntimeException`, or open any
   route after stopping the database.
2. Open it in a light scheme: the daylight illustration, the heading, the line, "Try again" and
   "Return home". Check the "Try again" link points at the same address.
3. Switch the theme to dark: only the night illustration is shown, text and buttons stay legible.
4. Narrow the window to 375 px: no horizontal scrolling, the illustration scales down.
5. Tab through the page: the instance name, "Try again", "Return home", Docs, Legal, each with a
   visible focus ring.
6. Switch the account to French and repeat step 2: every word is French.
7. Send the same request with `Accept: application/json`: the answer is a JSON error, not the page.
8. Remove the temporary route and set `APP_DEBUG` back.
