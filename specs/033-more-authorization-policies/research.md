# Research: More Authorization Policies

**Input**: [spec.md](spec.md). Everything 028 established about how an ability is declared, asked and proven is settled in [its research](../028-authorization-policies/research.md) and not revisited here.

## 1. Where each rule lives today

**Decision**: eleven abilities across nine policy classes, eight of them new.

| Ability | Screen holds it at | The work refuses at |
| --- | --- | --- |
| `RelationshipTypePolicy::delete` | `RelationshipTypeFormViewModel` 170 | `DestroyRelationshipType` 44, 48 |
| `ValueListPolicy::delete` | `ValueListsViewModel` 32 | `DestroyValueList` 40 |
| `ValueListPolicy::addItem` | `ValueListsViewModel` 31 | `CreateValueListItem` 56 |
| `ValueListItemPolicy::delete` | `ValueListsViewModel` 39 | `DestroyValueListItem` 41 |
| `EntityTypePolicy::delete` | `EntityTypeFormViewModel` 169 | `DestroyEntityType` 39 |
| `TemplatePolicy::share` | `TemplateBuilderViewModel` 57 | `ExportTemplate` 41, plus three guards, see 2 |
| `TemplateVersionPagePolicy::delete` | `TemplateBuilderViewModel` 146 | `DestroyTemplateVersionPage` 46, 54 |
| `UserPolicy::removePassword` | `SecurityViewModel` 98 | `DestroyUserPassword` 36, 42, 46, 52 |
| `UserPolicy::disconnectProvider` | `SecurityViewModel` 148 | `DisconnectProviderFromUser` 36, 42 |
| `ContactPolicy::absorb` | `ContactMergeViewModel` 70 | `PreviewContactMerge` 75 |
| `MarketplaceListingVersionPolicy::decide` | `ModerationSubmissionViewModel` 86 | `Approve…` 54, `Reject…` 63 |

**Rationale**: each row is one rule written twice, which is the defect FR-001 names. `ContactPolicy` is the only one that exists already; it gains `absorb()` and its `create()` gains the context section 6 describes. Corrected on 2026-09-14: line 116 of `RelationshipTypeFormViewModel` was listed here as a second copy of the delete rule. It is not. It answers whether the direction may be changed, mirrors `UpdateRelationshipType::directionIsFrozen()`, and is a twelfth duplication that FR-007 does not take.

**Alternatives considered**: giving each screen a shared helper instead of a policy, rejected because the work would still hold its own copy and nothing would keep the two in step.

## 2. The refusals that arrive by address

**Decision**: three guards join `TemplatePolicy::share`, answering as they do now.

`TemplateShareController` 17 and `TemplateExportController` 20 and 32 each ask whether a published version exists, which is half of what the builder asks at `TemplateBuilderViewModel` 57 and what `ExportTemplate` 41 refuses. The policy returns a refusal carrying "not found" rather than an explanation, so the response is byte for byte what it is today.

**Rationale**: FR-004, and the clarification behind it. This is the only rule in the set written three times, and the third copy is here.

**Out of scope**: the five guards reading whether a version is still editable, in `SectionImport…`, `SectionExport…` and `SectionShareController`. They govern sharing a *section*, which FR-007 does not list, and they already read one shared source on the enum.

## 3. How a refusal reads on a screen of many rows

**Decision**: the text a row already carries is its reason; a reason is added only where none exists.

A value already prints how much it is used, which is exactly why it cannot be deleted, so the refused control points at that text rather than repeating it. A value list prints only how many values it holds and says nothing about the product having supplied it, so that list needs a reason written.

**Rationale**: FR-003, and the clarification behind it. Fifty rows each repeating a near identical sentence is worse for a screen reader than for anybody, which Principle IX does not allow.

## 4. What asking per row costs

**Decision**: nothing measurable. No new query is introduced.

`is_system`, `usage_count` and `entity_count` are columns on the record, so a policy reading them touches no database. `RelationshipType::relationshipCount()` does query, but it is asked once on a form for one record, exactly as today. Nothing in scope asks an ability per row over a query.

**Rationale**: this was left open by `/speckit-clarify` as a planning question. It is answered, and no requirement follows from it.

## 5. The API

**Decision**: two endpoints change which refusal they catch, and nothing else.

`Api\EntityTypeController` 113 and `Api\TemplateVersionPageController` 73 catch the refusal types this feature removes, and turn them into a 422 naming the field. They keep doing exactly that, catching the framework's refusal instead. `Api\TemplateController` 107 is untouched: deleting a template is not in FR-007.

**Rationale**: Principle X, and 028's FR-008 through FR-002. No route, payload or status changes, which is why `contracts/` is omitted.

## 6. The rule 028 left split

**Decision**: `ContactPolicy::create` takes what it is missing as context.

The ceiling counts neither the contact representing somebody nor a partial contact, and `Account::contactCount()` knows that while the ability does not, so `CreateContact` still returns early on both before asking. Passing the two as context moves the whole rule into the one home FR-005 wants.

**Rationale**: no screen offers a partial contact today, so nothing is visibly wrong. The first one that does would be told the ceiling refuses when it does not.
