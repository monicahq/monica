# Quickstart: Email the administrators about support requests

## Automated

```bash
php artisan test --compact tests/Unit/Notifications/SupportRequestOpenedTest.php tests/Unit/Notifications/SupportMessageAddedTest.php tests/Unit/Actions/OpenSupportRequestTest.php tests/Unit/Actions/AddSupportMessageTest.php
composer test:parallel
php artisan monica:localize --check
```

All pass.

## By hand

Prerequisites: `MONICA_SUPPORT_ENABLED=true`, mail sent to the log or to a local catcher, a queue
worker running, two active administrators and one regular user.

| Step | Expected |
| --- | --- |
| As the user, open a request with a file attached | Each administrator gets "New support request MON-…" with the category, "From <name>, account <id>", the body, the attachment sentence and a button opening the request in the admin space. The user still gets their copy. |
| Mark it solved as an administrator, then add a message as the user | Each administrator gets "New message in support request MON-…" with the reopened sentence. |
| Reply and add a note as an administrator | No administrator email. |
| Set an administrator's language to French, repeat the first step | That administrator's email is in French, the user's words unchanged. |
| Set `MONICA_SUPPORT_ENABLED=false` and open a request through the API | Not found, and no email. |
| As an administrator, open the emails sent log in the account settings | Both emails are listed. |
