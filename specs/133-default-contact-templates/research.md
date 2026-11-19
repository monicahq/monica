# Research: Four supplied contact templates

## R1. One seeding job for every supplied template

**Decision**: `DefaultTemplateEnum` gains `Essentials`, `FriendsAndFamily`, `ProfessionalNetwork`, `FullPortrait`, and `CreateDefaultTemplate` seeds them unchanged in shape. Pages, sections and fields stay in `DefaultPageEnum`, `DefaultSectionEnum`, `DefaultFieldEnum`.
**Rejected**: a template definition file (a second format beside the enums, and the import format is meant for people).

## R2. The enum value is the key dictionary

**Decision**: a `DefaultFieldEnum` case is one concept; its value is the field key everywhere it appears. A concept shared with Basic contact reuses Basic contact's case (`birthday`, `emails`, `email`, `phones`, `phone`, `addresses`, `address`, `website`, `category`, `gender`, `notes`, `note`, `document`, `file`, `description`, `photo`, `image`, `caption`). A label that differs between templates for one concept ("Place or context", "Event or context", "Place") is given by `DefaultSectionEnum::fieldLabel()`, which falls back to `DefaultFieldEnum::label()`. Keys are listed in [data-model.md](data-model.md).
**Why**: `DetectDuplicateContacts` groups by `field_key`, `ContactAges` and `VaultReminderDates` look for `birthday`, and R5 matches on key. One word, one meaning.

## R3. Sections are per template, never shared

**Decision**: each template gets its own sections, slugged `<template>-<block>` (`essentials-contact-details`). Basic contact's sections are not reused.
**Why**: with R5, carrying values no longer depends on sharing a section, and a shared section would make editing Notes in one template change four. Basic contact and Basic animal already each own a Notes section.

## R4. Vault defaults name Basic contact explicitly

**Decision**: `DefaultTemplateEnum::isVaultDefault()` is true for `BasicContact` and `BasicAnimal` only. Three places that take "the first system contact template" ask for the vault default slugs instead: `CreateVault::seedTheContactDefault()`, `DestroyTemplate::fallBackToTheSystemTemplate()`, `VaultFormViewModel::suppliedStructureVersionId()`. `CreateDefaultTemplate::seedTheVaultDefaults()` runs only when `isVaultDefault()`.
**Why**: none of the three orders its query, so with five system contact templates the default would be arbitrary. A vault without a contact default would otherwise receive a new template through `firstOrCreate` during the backfill.

## R5. A value follows the same key elsewhere in the template

**Decision**: in `MoveRecordToStructureVersion::followTheFieldsIntoTheNewVersion()`, a held field first looks for `section|key|type` (today). Failing that, it looks for `key|type` among the carried top-level fields, and follows only when exactly one matches. A child field follows the child of the same `key|type` under the field its parent followed to, never on its own.
**Why**: Contact details differ between templates, so they are different sections, and today emails would be set aside moving from Basic contact to Essentials. Ambiguity sets the value aside (FR-023). Moves that succeed today are untouched because the first lookup is unchanged.

## R6. The gift and outing links stay off the address book

**Decision**: `OwnedFields::propertyFor()` maps every URL field to the vCard `URL` property today, children included. Fields whose key is in `DefaultFieldEnum::isKeptOffTheCard()` (`gift_idea_link`, `outing_idea_link`) map to nothing.
**Why**: otherwise a phone receives gift ideas as websites, and a website written on the phone fills an empty gift idea on sync. Website, Professional profile and the online profile links still sync.
**Rejected**: excluding every URL inside a repeating group (online profiles would stop syncing).

## R7. New value lists

**Decision**: `ValueListEnum` gains `Hobbies`, `CommunicationChannels`, `Industries`, `AreasOfExpertise`, with item enums `HobbyEnum`, `CommunicationChannelEnum`, `IndustryEnum`; `AreasOfExpertise` returns no items. `CreateDefaultValueLists(lists: [...])` already adds only the lists an account lacks, which covers existing accounts.

## R8. Account creation

**Decision**: Basic contact stays inline in `CreateAccount` (the user's own contact is written on it). The four templates join the queued `seedTheAccount()` chain after Basic animal, one `CreateDefaultTemplate` each, preceded by the four value lists in `CreateDefaultValueLists`.
**Why**: about 200 rows per account nobody needs during sign up (SC-006).

## R9. Backfill

**Decision**: one migration, as `backfill_possession_types` does, that walks accounts with `chunkById` and dispatches one queued `SupplyContactTemplates` job per account on the `low` queue. The job calls `CreateDefaultValueLists` for the four lists, then `CreateDefaultTemplate` for each template.
**Why**: the user asked for a migration, and the work runs on every deploy target without a manual command. Queuing keeps the deploy short whatever the number of accounts.
**Retry safety**: `CreateDefaultTemplate::alreadySupplied()` (system + slug, trashed included) skips a template already supplied or deleted.
**Slug held by the person**: if one of the account's own templates or sections holds a supplied slug, the job skips that template and logs a warning, as `monica:seed-animal-template` skips. Supplied section slugs carry the template prefix, so this is expected to be rare.
**Rejected**: an artisan command (`SeedAnimalTemplateCommand`), which somebody has to remember to run.

## R10. English wording against existing translations

**Decision**: where the English label already has a French translation other than the one the spec asks for, a distinct English wording is used: "Free-form notes" (Notes libres), "Job role" and "Current job role" (Fonction, Fonction actuelle), since "Job title" already reads "Intitulé du poste".

## R11. Documentation and changelog

**Decision**: `docs/portal/en/9-templates-and-customisation/1-introduction.md` lists the supplied templates; a changelog entry announces them to every account.
