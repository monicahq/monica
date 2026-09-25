# Research: Basic animal template

## R1. One seeding job for both supplied templates

**Decision**: `CreateDefaultTemplate` takes `supplied: DefaultTemplateEnum` (default `BasicContact`).
`BasicAnimal` adds the entity type first when `appliesTo()->needsEntityType()`. `CreateAccount`
chains the job twice. Basic contact's content and order are untouched.

**Rejected**: a second job copying 200 lines (two copies of the versioning rules to keep in step).

## R2. How the supplied type and template are recognised

**Decision**: the template by `is_system = true` and slug `basic-animal`; the type through that
template's `structure.entity_type_id`. No column is added. Type and template are written in one
transaction, so a type never exists without its template.

**Rejected**: a `key` column on `entity_types` (a create migration edit, which only reaches a rebuilt
database, and the backfill exists for databases that are not rebuilt).

## R3. The type's names

**Decision**: `name_singular`, `name_plural` and `description` are written with `__()` in the
account user's locale, as `CreateAccount` names the vault `__('Personal')`. `entity_types` has no
translation key columns. Slug `animals`, icon `pet`, colour `#9a6a24`.

**Consequence**: `pet` joins `EntityType::ICONS`, or editing the type refuses its own icon.

## R4. Retry safety

**Decision**: the job returns early when the supplied template already exists for the account
(R2). Everything else stays in its one transaction. Vault defaults use `firstOrCreate` on
`(vault_id, applies_to, entity_type_id)`, so a default somebody chose is never replaced.

**Note**: Basic contact moves from `updateOrCreate` to `firstOrCreate` too; on a new account nothing
has been chosen, so the outcome is the same.

## R5. Sex choices

**Decision**: `ValueListEnum::AnimalSexes` (`animal_sexes`) with a new `AnimalSexEnum`
(`female`, `male`, `unknown`). New accounts get it from `SeedValueLists`. `CreateDefaultValueLists`
gains `lists: list<ValueListEnum>` (default every case) and creates only the keys the account lacks
when given one, which the backfill uses. A select field needs a value list; it has no inline choices.

## R6. Keys

Page stored as `general` like every one-page template (`StructureVersionPage::SUPPLIED_KEY`), from the enum case `AnimalGeneral` whose value keeps it apart from Basic contact's page. Sections
`animal-details`, `animal-people`, `animal-care`, `animal-notes`: section slugs are unique per
account, and Basic contact already holds `notes`. Fields: `species`, `breed`, `sex`, `birthday`
(reusing `DefaultFieldEnum::Birthday`), `adoption_date`, `microchip_number`, `caregivers`,
`caregiver_person`, `caregiver_role`, `food_and_routine`, `health_and_care`, `animal_notes`.

## R7. Caregivers

**Decision**: Person is `relation` to `RelationTargetEnum::Contact`, `is_required`, with
`options = ['distinct_in_list' => true]`. `SetFieldValue::refuseADuplicate()` refuses a second value
of the same field on the same record when that option is set. The account and vault checks already
in `validateTheRelation()` apply unchanged.

**Gap closed**: `SetFieldValues::saveTheRepetitions()` checks required fields at the top level only.
A row with anything filled now refuses a required child left empty (`FieldIsRequired`), counting children without an input, such as a relation, since `childrenOf()` leaves those out. No supplied
template had a required child before, so only user templates that asked for it change.

## R8. Examples in the brief

"Dog, Cat, Rabbit" and "Owner, Foster carer, Pet sitter" are left out: `placeholder` and
`help_text` have no translation key, so a supplied hint would be frozen in one language.

## R9. Backfill

**Decision**: `monica:seed-animal-template {--account=}`, every account when omitted. Per account:
the missing list (R5), then the job run synchronously, then the vault defaults. It skips and says so
when `basic-animal`, `animals` or one of the R6 section slugs is already held by the user.
