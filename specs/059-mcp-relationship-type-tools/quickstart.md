# Quickstart: MCP tools for relationship types

## Automated

```bash
php artisan test --compact tests/Unit/Mcp/Tools --filter=Relationship
php artisan test --compact tests/Feature/Mcp
php artisan test --compact tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php tests/Feature/Controllers/App/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Expected: all pass. `McpCatalogueTest` proves each new tool names a route that exists and is
registered, that the three reading tools are read only and the two delete tools destructive.
`McpDocsViewModelTest` proves the page lists exactly the registered tools.

## Scenarios the tests cover

Refer to [contracts/mcp-tools.md](contracts/mcp-tools.md) for inputs.

1. Create **Godparent** in Family with roles godparent and godchild, `is_symmetric: false`: in
   Family, active, journalled with the MCP source.
2. Create **Teammate** with only `role_a`: symmetric, `role_b` equals `role_a`.
3. Create with `is_symmetric: false` and no `role_b`: refused, nothing written.
4. Create or rename to a name another type uses: refused with `Another type in this account already
   goes by that name.`
5. Update only `role_b` of an asymmetric type: name, category and `role_a` unchanged, still active.
6. Switch `is_symmetric` on a type that relationships use: refused with `This type keeps the direction
   it was written with.`
7. Switch a type off, then off again: one journal row. Move it to position 1 in its category.
8. Delete a type Michael created and nobody uses: gone. Delete a supplied type, or one Jim and Pam's
   relationship uses: refused with the policy's sentence.
9. Create category **Sports**, rename it, list its types, move it, switch it off and on.
10. Rename a supplied category: refused. Delete one holding types: refused with `The category still
    holds types and stays.` Delete an empty one Michael created: gone.
11. Any tool given another account's type or category, including as `relationship_category_id`: the
    not found sentence.

## By hand (optional)

With an API key and a connected assistant, ask it to "add a godparent relationship", then open
**Settings → Relationship types** and **Settings → Logs**.
