# Quickstart: validate contact relationships over the API and MCP

## Automated

```bash
php artisan test --compact tests/Feature/Controllers/Api/ContactRelationshipControllerTest.php
php artisan test --compact --filter=ContactRelationship tests/Unit/Mcp/Tools
php artisan test --compact tests/Feature/Mcp tests/Feature/Controllers/App/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

## By hand, the request that failed

With an assistant connected to a local instance (see `/docs/mcp`), in a vault holding Alexis Saettler
and Aurore:

1. Ask: "Alexis Saettler and Aurore are partners."
   Expected: the assistant calls `list-relationship-types`, finds both contacts, then
   `create-contact-relationship`. Both contacts' Relationships tabs show Partner.
2. Ask: "Who is Aurore related to?" Expected: `list-contact-relationships` returns Alexis as partner.
3. Ask: "They split up on 2026-01-15." Expected: `update-contact-relationship` with status `ended`
   and the date; the start date, if any, is kept.
4. Ask to remove it. Expected: the client asks first (destructive); the link leaves both tabs and both
   contacts stay.
5. **Settings → Logs** lists the three changes as made by the assistant.

## Refusals to try

| Ask | Expected wording |
| --- | --- |
| Link Aurore to herself | "Somebody cannot be in a relationship with themselves." |
| Use a switched off type | "That relationship type can no longer be chosen." |
| End before it started | "The end cannot fall before the start." |
