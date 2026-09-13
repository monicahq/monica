# Research: Immediate family block

## D1. Where the selection lives

**Decision**: two query scopes on `Contact`, `scopeChildrenOf()` and `scopeCurrentPartnersOf()`, each returning a `Contact` builder.

**Rationale**: Principle X wants the API to answer the same question, and view models must not be read outside Blade, so a selection kept in one would drift from the endpoint. `Contact::scopeInGrouping()` already carries this exact argument in its own docblock, for the same reason. Returning a builder keeps ordering and future pagination at the call site.

**Alternatives**: a method on `ContactViewModel` (unreachable from the API); scopes on `Relationship` (returns rows, and the block wants distinct people); a dedicated query class (a class for two `whereIn`s).

## D2. Query shape

Both scopes narrow `Contact` by `account_id` and `vault_id`, then by `whereIn('id', <subquery over relationships>)`. Distinctness comes free from the `IN`; soft deletes are excluded on both sides by the models' global scopes. Type membership is a nested subquery over `relationship_types` filtered on `system_behavior`, never on name, key or category.

| List | Subquery |
|---|---|
| Children | `contact_b_id` where `contact_a_id` is the viewed contact, parentage behaviour, any status |
| Current partner | `contact_b_id` where A is the contact, **or** `contact_a_id` where B is the contact, romantic behaviour, `status = current` |

`relationships_side_a_index` and `relationships_side_b_index` already cover both directions on `(vault_id, contact_side, relationship_type_id, deleted_at)`, so SC-003 needs no migration. Ordering is `sort_key`, the locale-aware column the people list already sorts on.

## D3. The age, without an N+1

**Decision**: one `FieldValue` query for every listed contact at once, matched on `field_key = 'birthday'`, keyed by `valuable_id`. Age is `value_date->age`.

**Rationale**: `field_values.field_key` is copied at write time, so no template join is needed and a contact whose template never had the field simply has no row. The filters are `valuable_type = Contact::class`, `field_value_row_id is null`, `attached()`, `year_is_unknown = false`. That last one is what keeps `FieldValue::PLACEHOLDER_YEAR` out of an age, which the constant's own docblock forbids.

**Caveat**: a field key is unique per section version, not per account, so a field somebody invents and also keys `birthday` would match. `ReminderRow` already identifies the birthday this way; the alternative is resolving the field through the contact's template version per contact, which is the N+1 this avoids.

## D4. Presentation

**Decision**: `App\ViewModels\Vaults\ImmediateFamilyBlock`, built by `ContactViewModel::immediateFamily()`.

**Rationale**: `ContactViewModel` is already 777 lines, and `CLAUDE.md` says a rich page keeps one main view model and delegates its blocks to small objects beside it. `ContactReminderBlock` is that precedent, in the same namespace.

## D5. Markup

`x-panel` with `icon="family"`, `:flush="true"`, an `action` slot for the way to the relationship form, `x-caption` for each list heading, `x-contact-avatar` at 30, and `x-icon name="gift"` beside an age. All three glyphs the drawing uses (`family`, `gift`, `relationship`) exist in `icon.blade.php`.

The person row appears twice in one file. `.claude/rules/components.md` sets the bar at three files, so it stays inline and no component is extracted.

**Empty**: when both lists hold nobody the block shows one explanatory body and the way to add, as the drawing's third card does. When either holds somebody both headings show, the empty one carrying its own *No … recorded* line.

## D6. Counted phrases

The heading is `__('Children')` and `__('Current partner')`, with the digit beside it. The digit's accessible name goes through `trans_choice(':count child|:count children', $n)`, which is what `code-style.md` requires of any phrase holding `:count`. No pinned wording, so nothing has to guard zero.

## D7. Deferred API coverage

Principle X is satisfied by D1 rather than by an endpoint in this feature: the rules sit in two scopes an `Api\ContactController` method can call, so adding the endpoint later is transport and a resource, nothing more. The spec puts the endpoint out of scope and the user approved that boundary.
