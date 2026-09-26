# Contract: Routes and Console

All three admin routes sit in the `administrator` group of `routes/web.php`. A visitor, a regular
user and a deactivated administrator get not found, as for the rest of the admin space.

| Verb | URL | Name | Controller | Password confirmation |
| --- | --- | --- | --- | --- |
| POST | `admin/configuration/telegram/registration` | `admin.configuration.telegram.registration.create` | `TelegramRegistrationController@create` | yes |
| POST | `admin/configuration/telegram/check` | `admin.configuration.telegram.check.create` | `TelegramRegistrationCheckController@create` | no, it changes nothing |
| POST | `admin/configuration/telegram/secret` | `admin.configuration.telegram.secret.create` | `TelegramWebhookSecretController@create` | yes |

None takes input. Every one redirects to `admin.configuration.index`.

Nothing is added to `routes/api.php`, to the MCP server or to the API documentation (research R9).

| Outcome | Response |
| --- | --- |
| Registered | flash `status`, and `status_description` naming the address |
| Registration refused (prerequisite, local address, Telegram refusal, unreachable) | `back()->withErrors(['telegram' => $message])` |
| Checked | flash `telegram_check`: `{url: ?string, ok: bool, matches: bool, lastError: ?string}` |
| Check failed (unreachable) | `back()->withErrors(['telegram' => $message])` |
| Secret generated | flash `status` |

## Incoming Telegram address (changed)

`POST telegram/webhook`, name `telegram.webhook`, declared whenever the integration is installed.

| Condition, in order | Response |
| --- | --- |
| no token in effect | 404 |
| no secret in effect, or header does not match | 403 |
| otherwise | unchanged from today |

## Console

`php artisan monica:setup-telegram` keeps its name and output shape. It calls the same action as
the screen with no administrator, prints the action's refusal as an error and exits 1, or prints the
registered address and exits 0.
