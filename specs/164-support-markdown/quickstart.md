# Quickstart: checking Markdown in support requests

## Run

```bash
php artisan test --compact tests/Unit/Helpers/SupportMarkdownTest.php
php artisan test --compact --filter=Support
composer test:parallel
```

## By hand

1. Sign in as a regular user, open **Support**, and send a request whose body is:

   ````text
   # Steps
   1. Open the vault
   2. Click **Save**

   ```
   Error: reminder skipped

   at 09:00
   ```

   <script>alert(1)</script> [guide](https://example.com) [bad](javascript:alert(1))
   ![pixel](https://example.com/pixel.gif)
   ````

2. On the request screen: "Steps" is a small heading, the list is a list, "Save" is bold, the code
   block keeps its blank line and wraps on a narrow window, `<script>alert(1)</script>` reads as text,
   "guide" opens in a new tab, "bad" is not a link, "pixel" is a link, and the network panel shows no
   request to example.com.
3. As an administrator, open it in **Administration > Support**: the same rendering. The list on the
   left shows the last line as plain words.
4. Reply with a list and a link, and add an internal note. Read both in the administration thread and
   the reply in the user's thread.
5. Open the reply email in the local mail catcher: `<script>` reads as text and no image loads.
6. `GET /api/v1/support-requests/{id}` with an API key: `body` is the text as written.
