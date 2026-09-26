# Quickstart: MCP tools for templates, sections and fields

## Run the checks

```bash
php artisan test --compact tests/Unit/Mcp tests/Feature/Mcp
php artisan test --compact tests/Feature/Controllers/Api/StructureVersionSectionControllerTest.php \
  tests/Feature/Controllers/Api/FieldControllerTest.php tests/Unit/ViewModels/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

## Walk through US1 by hand

With a key from Settings → Security and access → API keys, against `/api/mcp`, call in order:

1. `list-templates`: pick a template uuid.
2. `create-template-draft` with `template`: note the returned `version_number`.
3. `add-template-section` with `template`, `version`, `name: "Hobbies"`: note the returned `id`.
4. `create-template-field` twice on that `section`: `type: text, label: Hobby`, then
   `type: date, label: Started`.
5. `set-template-field-position` to put Started first.
6. `get-template-version`: the draft shows Hobbies with Started, then Hobby.
7. `publish-template-version` with a `change_note`: the version becomes published.
8. `update-template-section` on the published version: refused with the frozen version message.

Settings → Logs shows each change with the key's name as its source.

## Check the page

Open `/docs/mcp`: "Available tools" lists 32 tools, the template groups badged as changing data, and
"Edit a template" walks through the sequence above. `/docs/mcp.md` carries the same.
