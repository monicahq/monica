# Research: Relationship types in account settings

Eight decisions. Everything else follows the Entity types feature without a choice being made.

## 1. The API waits for its own feature

**Decision**: no `Api` controller here. The follow-up is `relationship-types-api`, and it must land before the relationship record ships, so Principle X is satisfied for the capability as a whole rather than for this screen alone.

**Rationale**: the nine actions hold every rule, so the API controllers will add transport and nothing else. Contacts, entity types and templates were all built this way.

**Rejected**: shipping both at once. It settles the API shape for a vocabulary whose only consumer is not designed yet.

## 2. The usage count is zero, and the guards are written anyway

**Decision**: `RelationshipType::relationshipCount()` returns `0` with a one-line comment saying the relationship record does not exist yet. `UpdateRelationshipType` and `DestroyRelationshipType` call it exactly as they will when it counts rows.

**Rationale**: spec FR-032. The day `relationships` exists, one method body changes and both refusals start biting, with no change to the controllers, the screens or the copy.

**Rejected**: leaving the guard out. It would have to be threaded back through two actions and two view models later, and the copy explaining it would have to be written and translated then instead of now.

## 3. Direction is a boolean, not an enum

**Decision**: `is_symmetric`. A symmetric type writes the same value into `role_a` and `role_b`.

**Rationale**: `spec/models/025` says so, and it keeps the read path free of a branch: every relationship reads `role_a` from A and `role_b` from B whatever the type.

**Rejected**: storing one role for a symmetric type and nulling the other. Every reader would need the branch.

## 4. Seeding runs at account creation, and nowhere else

**Decision**: a `CreateDefaultRelationshipTypes` action, called from `CreateAccount` beside `CreateDefaultValueLists`. No backfill migration.

**Rationale**: the product is not in production, so there is no account anywhere that predates this feature. A backfill would be a migration that runs against nothing, kept forever, and read by the next person as evidence of a history that never happened.

**Rejected**: a backfill migration on the model of `2026_09_02_000004_backfill_notification_preferences.php`. Written first, then removed for the reason above. A local database that predates the feature is rebuilt with `php artisan migrate:fresh`.

## 5. Categories reorder with buttons, types with the drag handle

**Decision**: a category carries an up button and a down button, as the design draws it. A type carries the drag handle alone.

**Rationale**: the buttons were first put on type rows as well, because `Alpine.data('sortableRows')` is pointer-only and dragging alone fails WCAG 2.1.1 and 2.5.7. They were removed on the owner's instruction: the drag and drop component is the answer for a type. This leaves reordering types as the one thing on this screen that cannot be done from the keyboard, which Principle IX otherwise forbids, and it is recorded here rather than left to be rediscovered.

**Rejected**: keyboard-driven drag on the existing helper. It is a bigger change to shared JavaScript than two buttons, and it would need its own announcements. It stays the way to close the gap without adding controls the design does not have.

## 6. The design's classes become Tailwind utilities

**Decision**: the `mn-*` classes of the design bundle are not ported. Each maps to an existing Blade component: `mn-panel` to `x-panel`, `mn-badge` to `x-badge`, `mn-control` to `x-input` and `x-select`, `mn-notice` to `x-notice`, `mn-btn` to `x-button`, `mn-field` and `mn-label` to `x-form` and `x-label`, the radio rows to `x-option-row`, the dialogs to `x-modal`.

**Rationale**: the design system in this repository is utility strings in Blade, and `app.css` holds tokens and the palette only.

**Rejected**: importing the bundle's CSS. It would be a second design system beside the first.

## 7. The category is a record, not a column

**Decision**: `relationship_categories` is its own table, per `spec/models/024`.

**Rationale**: categories are named, ordered, created and deleted by the user, and supplied ones carry a stable key. A string column on the type would make renaming a category a write across every row and would have nowhere to keep the key.

**Rejected**: an enum. It cannot be extended by a user at runtime, which Principle VII forbids for anything outside the nine fixed concepts.

## 8. A supplied name is a translation key until the user rewrites it

**Decision**: `name`, `role_a` and `role_b` are nullable, each beside a `*_translation_key` column, and the model resolves `$value ?? __($key)`. Two enums implementing `App\Contracts\Translatable`, `RelationshipCategoryEnum` and `RelationshipTypeEnum`, hold the supplied wording.

**Rationale**: Principle V requires everything the product ships with to read in all nine locales, and `LocalizeCommand` only collects enum strings from classes implementing `Translatable`. `ValueList` and `ValueListItem` already solve exactly this, so this copies them rather than inventing a second answer. The moment a user renames a supplied type, the column holds their words and the key is ignored.

**Rejected**: storing the English string at seed time. A French account would read Parent / Child forever, and `monica:localize` would never see the words.
