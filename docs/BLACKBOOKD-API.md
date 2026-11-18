# BlackBookd REST API

Authentication matches Monica v5: **Laravel Sanctum** personal access tokens with `read` and/or `write` abilities. Base URL: `https://your-host/api`.

Enable the module first (`PUT /api/blackbookd/settings`). All vault routes return **403** when `blackbookd_enabled` is false.

## Account settings

```bash
# Read
curl -s -H "Authorization: Bearer $TOKEN" \
  https://your-host/api/blackbookd/settings

# Enable module
curl -s -X PUT -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"blackbookd_enabled":true,"blackbookd_blur_dashboard":true,"blackbookd_partner_lookback_days":90}' \
  https://your-host/api/blackbookd/settings

# Export JSON (read token)
curl -s -H "Authorization: Bearer $TOKEN" \
  https://your-host/api/blackbookd/export -o blackbookd-export.json
```

## Encounters

```bash
VAULT_ID=...
CONTACT_ID=...

curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "occurred_at":"2025-11-01T21:30:00Z",
    "location_label":"Portland metro",
    "how_met_category":"app",
    "how_met_app":"grindr",
    "protection_used":["condom","prep"],
    "activity_tags":["kissing"],
    "rating":4,
    "contact_ids":["'"$CONTACT_ID"'"]
  }' \
  https://your-host/api/vaults/$VAULT_ID/blackbookd/encounters
```

List: `GET /api/vaults/{vault}/blackbookd/encounters?limit=25`  
Update: `PUT /api/vaults/{vault}/blackbookd/encounters/{id}`  
Delete: `DELETE /api/vaults/{vault}/blackbookd/encounters/{id}`

## Contact sexual-health profile

```bash
curl -s -X PUT -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "hiv_status":"negative",
    "on_prep":true,
    "last_test_date":"2025-10-01",
    "relationship_type":"fwb",
    "boundaries":"No marks"
  }' \
  https://your-host/api/vaults/$VAULT_ID/blackbookd/contacts/$CONTACT_ID/profile
```

List all profiles in vault: `GET /api/vaults/{vault}/blackbookd/contact-profiles`

## Health log

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "entry_type":"sti_test",
    "occurred_on":"2025-11-01",
    "payload":{"tests_run":["hiv","syphilis"],"results":{"hiv":"negative"}},
    "schedule_sti_reminder":true
  }' \
  https://your-host/api/vaults/$VAULT_ID/blackbookd/health-logs
```

## Dating pipeline (GayHook)

Upsert by handle (creates contact if missing):

```bash
curl -s -X PUT -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "handle":"pdx_pup_example",
    "app_source":"fetlife",
    "metro_location":"Portland",
    "role":"sub",
    "looking_for":"play partner",
    "stage":"talking",
    "last_message_from":"them",
    "last_message_summary":"Asked about coffee",
    "last_message_at":"2025-11-17T22:00:00Z",
    "next_step":"Draft reply",
    "phone_numbers_exchanged":"no",
    "long_term_fit_score":8,
    "long_term_fit_reason":"Shared kink overlap"
  }' \
  https://your-host/api/vaults/$VAULT_ID/blackbookd/dating-pipelines/by-handle
```

Quiet for follow-up nudges:

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://your-host/api/vaults/$VAULT_ID/blackbookd/dating-pipelines/quiet?days=7"
```

Weekly roundup:

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://your-host/api/vaults/$VAULT_ID/blackbookd/dating-pipelines/weekly-roundup?quiet_days=7"
```

CRUD: `GET|PUT|DELETE /api/vaults/{vault}/blackbookd/dating-pipelines/{id}`

### Stage values

`first_contact_draft_ready`, `talking`, `asked_to_text`, `meeting_planned`, `met`, `dating`, `paused`, `ended_boundary_held`

## Partner notification

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://your-host/api/vaults/$VAULT_ID/blackbookd/partner-notification?positive_test_date=2025-11-01&lookback_days=90"
```

Returns contacts with encounters in the lookback window before the test date.