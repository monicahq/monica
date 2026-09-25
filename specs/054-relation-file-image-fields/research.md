# Research: Relation, file and image fields in the template builder

## R1. A relation has an input

- **Decision**: add `Relation` to `FieldTypeEnum::hasAnInput()`. `canBeFilledIn()`, `inputFields()`,
  `EntityTypePolicy::addEntity`, `FieldValueRules`, `SetFieldValues`, `x-field-repetitions` and the
  contact page's `isEditable` then include it with no change of their own, so Caregivers is drawn
  and an Animal default is no longer refused (FR-007).
- **Rationale**: one allow-list decides "a screen can fill this in"; a second flag would drift from it.
- **Alternatives**: a relation-only branch in each caller (thirteen callers).

## R2. A relation value is the record's uuid, checked at validation

- **Decision**: the form and the API send the uuid of the contact or entity. `FieldValueRules::for()`
  takes the vault and gives a relation `Rule::exists` on `contacts.uuid` or `entities.uuid` scoped to
  that vault, and to `entity_type_id` for an entity target. `SetFieldValues::saveOne()` resolves the
  uuid and passes `relatedContact` or `relatedEntity` to `SetFieldValue`, whose guards stay.
- **Rationale**: the API already names records by uuid; a stale or foreign uuid is what somebody
  typed, so it is refused beside its field as a 422 or a form error rather than as an uncaught exception.
- **Alternatives**: integer ids (never exposed by the API); a new exception in four controllers.

## R3. The control is a new component, `x-record-picker`

- **Decision**: a search input, a live region, a list of candidate buttons with `aria-pressed`, the
  chosen name with a Clear button, and a hidden input holding the uuid. Typing asks a new route,
  `vaults/{vault}/fields/{field}/choices?query=` (`FieldChoiceController@index`), for the list, which
  replaces itself through `x-merge`, the way `relationships/_candidates` does. At most 25 candidates,
  ordered by `sort_name`; contacts through `matchingSearch`, entities through a new
  `Entity::scopeMatchingSearch`. Alpine state in `resources/js/record-picker.js`.
- **Rationale**: an endpoint scales to any vault and needs nothing threaded through view models;
  the pattern and its copy already ship. It is a keyboard and screen reader contract, so it is a
  component from its first use (`components.md`).
- **Alternatives**: every candidate embedded in the page (a vault of thousands, repeated per row); a
  native `<select>` (no search by name).
- **Not done**: the relationship form keeps its own candidate list. It is one mode of a three mode form
  bound to `relationshipForm()` state, and moving it is a separate refactoring.

## R4. `x-field-input` takes the vault

- **Decision**: a `vault` prop, forwarded by `x-field-repetitions`, passed at its three call sites
  (contact section, entity form, life event fields). The chosen name of a stored value comes from
  `relatedContact` / `relatedEntity`, eager loaded where the values are.
- **Field route binding**: `Field` is scoped by its controller; `FieldChoiceController` returns 404
  unless the field is a relation of the signed in account.

## R5. The target is chosen when the field is added

- **Decision**: the field type screen shows a `relation_target` select (`contact`, `entity:ID`) when
  Relation is picked, listing active entity types only. The controller validates it inline (the entity
  type exists, is in the account and is active) and passes `relationTarget` and `relationEntityType`.
  The parsing of `entity:ID` moves from `ReplaceLifeEventTypeFields::pointAt()` to
  `FieldSettingsCatalogue`, which both callers use. The field API refuses an inactive type at validation; `CreateField` does not, since the life event editor may still name one.

## R6. Fields inside a repeater

- **Decision**: `ChildFieldController@new` on
  `settings/account/templates/{template}/sections/{structureVersionSection}/fields/{field}/fields/new`,
  reusing the `field-type` view with Repeater left out, and posting a `parent_id` to the existing
  `settings.fields.create`, so creation is written once. After
  `MakeSectionVersionEditable` the parent is found again by `key`, as `FieldController::editable()`
  does. The inspector of a selected repeater links to it. `UpdateFieldPosition` already orders among
  siblings, so `_field.blade.php` allows dragging children.
- **Alternatives**: a `parent` query string on `settings.fields.new` (`code-style.md` avoids query strings).

## R7. The inspector keeps what it does not show

- **Decision**: `FieldController@update` passes the field's current `default_value`, `validation`,
  `is_unique`, `is_identifying` and `options` to `UpdateField`, replacing only `accepted_mime_types`
  for a file or image field. It shows the relation target read-only, with "inactive" when the type is.
- **Rationale**: today every inspector save nulls those five, which wipes `distinct_in_list` on
  Caregivers > Person and the shipped accepted kinds. This is a live defect, fixed here because FR-004
  cannot work without it.

## R8. Accepted kinds are validated in the actions

- **Decision**: `CreateField` and `UpdateField` refuse a kind not matching the mime pattern, more
  than 20 kinds, or a non `image/` kind on an image field, with `AcceptedFileKindIsNotValid`, caught
  by the web and API controllers. The pattern and limit move from `StructureFileReader` to `Field`.
- **Uploads**: `FieldValueRules::fileRules()` already adds `mimetypes:`, and every locale translates
  its message, which names the kinds. FR-009 needs a test only.
