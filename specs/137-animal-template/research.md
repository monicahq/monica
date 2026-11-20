# Research: Richer supplied animal template

Every decision below was taken from the code on `main` (`c26712c9`) and from the record page commit
`6d12e152` this work is stacked on.

## R1. Where the work is stacked

- **Decision**: Branch `2026-11-19-animal-template` from the local branch holding `6d12e152`
  ("feat(templates): record page and empty list"), and open its pull request against that branch.
- **Rationale**: The header of FR-015 needs a page for one animal. On `main` an animal can be
  created and listed only (`routes/web.php`: `entities.index`, `entities.new`, `entities.create`), as
  spec 053 left viewing and editing out of scope. `6d12e152` adds `EntityViewModel`,
  `resources/views/app/vaults/entities/show.blade.php` and the edit form.
- **Alternatives considered**: Building the record page here, rejected because it already exists.
  Leaving the header out, rejected by the answer recorded in the spec.

## R2. A new supplied template, not a changed Basic animal

- **Decision**: Add `DefaultTemplateEnum::Animal` (`animal`) beside `BasicAnimal`, with its own
  pages and sections. `BasicAnimal` and its enum cases stay, untouched.
- **Rationale**: `CreateDefaultTemplate::alreadySupplied()` and every backfill tell a supplied
  template by its slug. Changing what `basic-animal` holds would make new accounts differ from old
  ones under the same slug, and the backfill could never tell an account that has Animal from one
  that has Basic animal. Principle VI forbids changing Basic animal in place in any case.
- **Alternatives considered**: Editing `DefaultSectionEnum::AnimalDetails` and friends, rejected for
  the reasons above.

## R3. Same Animals kind for both templates

- **Decision**: `CreateDefaultTemplate::createEntityType()` reuses the account's entity type with the
  slug the template asks for (`animals`) when it exists, including when it is deactivated, and
  creates it only otherwise. A soft deleted type counts as a refusal: the backfill skips the account.
- **Rationale**: Today the method always creates the type, which is right for a new account and
  would give a backfilled account a second Animals kind (FR-005). Reusing a soft deleted type would
  bring back something the person deleted.
- **Alternatives considered**: A separate code path in the backfill, rejected because new accounts
  and backfilled ones must go through the same path (FR-011).

## R4. New accounts and new vaults

- **Decision**: `CreateAccount::seedTheAccount()` keeps queuing Basic animal and adds `SupplyAnimalTemplate`
  to the chain after it. `DefaultTemplateEnum::Animal::isVaultDefault()` is false. `CreateVault` is
  not touched: it keeps starting a vault's animals on Basic animal.
- **Rationale**: Animal is offered beside Basic animal, never in its place (clarification of
  2026-11-19). Running the same job at sign up and in the backfill keeps one path, as
  `SupplyContactTemplates` does.
- **Alternatives considered**: Animal as the default of new accounts, rejected by the person asking.

## R5. The backfill

- **Decision**: A queued job `SupplyAnimalTemplate`, run in the account creation chain and dispatched per user on the `low` queue by a new
  migration `2026_11_19_000001_backfill_supplied_animal_template.php`, exactly as
  `2026_11_18_000004_backfill_supplied_contact_templates.php` dispatches `SupplyContactTemplates`.
  The job creates the Neutered statuses value list, then calls `CreateDefaultTemplate` for Animal,
  unless the account holds a template or section of its own with one of Animal's slugs, or has
  deleted its Animals kind, in which case it logs a warning and adds nothing.
- **Rationale**: Same mechanism as the last backfill, already run in production. `CreateDefaultTemplate`
  is idempotent through `alreadySupplied()`, which counts soft deleted templates, so a rerun or a
  template deleted after the backfill adds nothing (FR-011). `seedTheVaultDefaults()` uses
  Animal is not a vault default, so no `vault_default_templates` row is written at all (FR-004).
- **Alternatives considered**: An Artisan command like `monica:seed-animal-template`, rejected: the
  project moved to the migration pattern with 133, and a command has to be remembered at deployment.
  `SeedAnimalTemplateCommand` stays as it is; it skips any account holding the `animals` kind, so it
  never adds Basic animal to an account created after this release.

## R6. Neutered or spayed

- **Decision**: A new supplied value list `ValueListEnum::NeuterStatuses` (`neuter_statuses`), whose
  items come from a new `NeuterStatusEnum` (Yes, No, Unknown), created for new accounts by
  `CreateDefaultValueLists` with no arguments and for existing ones by the backfill.
- **Rationale**: A select field in a supplied template always reads its choices from a value list
  (`DefaultFieldEnum::valueList()`, every `Select` case has one). `AnimalSexEnum` is the precedent.
- **Alternatives considered**: Choices stored in the field's `options`, rejected because the select
  field type reads only a value list. The spec's assumption that the three answers are not editable
  is relaxed: the list is editable like Animal sexes, which costs nothing and matches the precedent.
  The spec's Assumptions line is updated accordingly.

## R7. Field identities

- **Decision**: Reuse the existing `DefaultFieldEnum` cases for the seven shared concepts
  (`species`, `breed`, `sex`, `birthday`, `adoption_date`, `microchip_number`, `caregivers` with
  its two children), and for concepts the contact templates already name the same way: `photo`
  (image, caption), `document` (file, description), `notes` (repeatable note) and `allergies`. Every
  other field is a new case. The new labels go through `DefaultSectionEnum::fieldLabel()` overrides,
  as the contact templates do.
- **Rationale**: FR-006, and `MoveRecordToStructureVersion` matches on section, key and type, then on
  key and type anywhere in the target version. The seven shared concepts therefore follow an animal
  moved from Basic animal even though Microchip number changes section.
- **Alternatives considered**: New keys for photo, document and notes, rejected: they are the same
  concept as in the contact templates, and one concept keeps one key.

## R8. Highlighted fields per section

- **Decision**: Add `DefaultSectionEnum::isHighlighted(DefaultFieldEnum $field): bool`, which reads a
  per section override and falls back to `DefaultFieldEnum::isHighlighted()`. The Animal identity
  section highlights Species, Breed and Birthday. `CreateDefaultTemplate::createFields()` calls it in
  place of the field's own method.
- **Rationale**: Species and Breed are the same enum cases in Basic animal, where they must stay
  unhighlighted (FR-015). `fieldLabel()` already solves the same problem for labels.
- **Alternatives considered**: New cases for Species and Breed, rejected because it would break FR-006.

## R9. The header of an animal's page

- **Decision**: `EntityViewModel::headerFacts()` returns the first three highlighted fields of the
  record's version with their formatted value or "Not recorded", in template order. `show.blade.php`
  renders them under the title as the contact page does, without the last contact and reminders
  entries, which an animal does not have.
- **Rationale**: Same rule as `ContactViewModel::headerFacts()`, so a highlighted field means the same
  thing on both pages. The fact row stands in two files after this change, under the three file bar
  of `.claude/rules/components.md`, and carries no behavioural contract, so it is not extracted.
- **Alternatives considered**: A shared component, deferred until a third page shows facts.

## R10. API, MCP and documentation

- **Decision**: No new endpoint or tool; one optional parameter on an existing one (R12). Help centre pages `docs/portal/en/9-templates-and-customisation/9-profile-types.md`
  and `docs/portal/en/3-people/6-profiles.md` describe Animal. A changelog entry announces it.
- **Rationale**: Templates, their structure and records are already readable and writable through the
  API and MCP (principle X). The template's content is data, not a capability.

## R11. Picking the template when adding an animal

- **Decision**: The form that adds a record of a kind offers the account's active templates of that
  kind whose structure has a published version, as links under "What this animal starts from", the
  vault's default first and marked current. A link reloads `entities.new` with the chosen template's
  slug as an optional last segment (`vaults/{vault}/entities/{entityType}/new/{template?}`), because
  the form draws that version's fields and has to be drawn again. The choice is not shown when only one
  template is on offer. When the vault has no default for the kind, the first template on offer is
  taken instead of answering not found.
- **Rationale**: The contact form picks a template with a select because it draws no field of the
  template. The entity form draws them all, so a choice has to reload the form; links work without
  JavaScript and announce the current choice with `aria-current`. A path segment follows the routing
  rule against query strings and matches the optional `{page?}` segment of `entities.show`.
- **Alternatives considered**: A select that reloads on change, rejected because a control that
  navigates on change breaks WCAG 3.2.2. Drawing every template's fields and switching with Alpine,
  rejected for its weight.

## R12. CreateEntity accepts a chosen version

- **Decision**: `CreateEntity` takes the given structure version when there is one, as `CreateContact`
  does, and refuses it unless it is in the account (`ModelNotFoundException`), its structure applies to
  the entity type being created (`ModelNotFoundException`), and its template is active
  (`ModelNotFoundException`). A version that is no longer the published one of its template throws
  `EntityTemplateChanged`, which keeps the meaning it has today: the form is stale. Without a version
  it takes the vault default, as today.
- **Rationale**: Today the action refuses any version but the vault default, which is exactly what
  stops a choice. The rule lives in the action, so the web form, the API and the MCP tool get it
  together (principles I and X).
- **API and MCP**: `POST api/v1/vaults/{vault}/entity-types/{entityType}/entities` gains an optional
  `structure_version_id`, as the contact endpoint has; the `create-entity` MCP tool's schema names it;
  the API reference and the API changelog say so.
