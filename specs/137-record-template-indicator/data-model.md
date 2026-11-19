# Data Model: Template named on the edit screen of a record

No schema change. The feature reads what exists:

```text
Contact.structure_version_id → StructureVersion.structure_id → Structure.owner (morph) → Template
```

- `Template.name` is the accessor that falls back to the translated `name_translation_key` for a
  template the product supplied.
- `Template` is routed by its slug (`settings.templateBuilder.index`).
- Deleting a template is refused while any record uses one of its versions, so a contact in
  production always reaches one. A structure whose owner is soft deleted reads as null.
