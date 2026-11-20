# Data Model: Withhold the visitor's network address from monitoring

Nothing is stored and no migration is written. The one structure involved belongs to the monitoring
package and is changed in memory, before it is sent.

## Request trace (`Laravel\Nightwatch\Records\Request`)

| Field | Before | After |
| --- | --- | --- |
| `ip` | The client address, resolved through the trusted proxies | `''` |
| `headers` | Every request header, credentials masked by the package | The same, without `X-Forwarded-For`, `X-Real-IP`, `Forwarded`, `CF-Connecting-IP`, `True-Client-IP` |
| `url` | Tokens, signatures, search terms and SSO codes masked | Unchanged |
| user | `['id' => …]` | Unchanged |

Header names are matched case insensitively, since `HeaderBag` normalises them.
