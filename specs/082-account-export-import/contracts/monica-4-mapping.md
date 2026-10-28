# Contract: reading a Monica 4 file

Accepted when `version` is `1.0-preview.1`. Each row says where a value lands; anything not listed is counted in the report as not imported. Notes are written in the importer's locale.

## A contact

| Monica 4 | Monica |
| --- | --- |
| `first_name`, `middle_name`, `last_name`, `nickname` | `CreateContact` name parts |
| `is_starred`, `is_partial` | `isFavourite`, `isPartial` |
| `is_active` false | `ArchiveContact` after everything else is written |
| `gender` → instance gender `type` | `gender` item: `M` man, `F` woman, `U` prefer_not_to_say, anything else other |
| `birthdate` | `birthday`; `is_year_unknown` sets year unknown. `is_age_based` leaves the field empty and writes the note "Born around :year" |
| `description` | `description` |
| `job`, `company`, `food_preferences`, `tags` | One note each: "Job: …", "Company: …", "Food preferences: …", "Tags: a, b" |
| `avatar.avatar_photo` | `UpdateContactAvatar` with that photo. Other avatar sources are dropped |
| `is_dead` with `deceased_date` | Life event of the account's own "Death" nature |
| `first_met_date`, `first_met_through` | Life event of the account's own "First meeting" nature, the introducer as participant |
| `stay_in_touch_frequency` (days) | Weekly reminder, interval `round(days / 7)` within 1 to 99 |
| `vcard`, `distant_etag`, view and consultation counters | Not imported |

## What a contact holds

| Monica 4 | Monica |
| --- | --- |
| `contact_field` of type `email` / `phone` | `emails.email` / `phones.phone` rows, `mailto:` and `tel:` stripped |
| Other `contact_field` | `website` row when the value starts with `http`, otherwise a note "Kind: value" |
| `address` | `addresses.address` row: street, city, province, postal code, country; `name` becomes the label wording |
| `note` | `notes.note` row |
| `photo` / `document` uuids | `photos.image` / `documents.file` rows, the file decoded at read time |
| `reminder` | `CreateContactReminder`: `one_time` Once, `week` Weekly, `month` Monthly, `year` Yearly, interval `frequency_number`; starts at the next occurrence from today. A past `one_time` is left out. `inactive` is deactivated after creation |
| `life_event` | Life event of the nature in the table below, `name` as title, `note` as narrative |
| `call` | `call` life event, `content` as narrative |
| `conversation` | `message` life event, its messages as the narrative, one line each |
| `activity` uuid | One `time_together` life event per activity, every contact referring to it as participant, `summary` as title |
| `pet` | Entity of the Basic animal template: name, Species from `category`, the contact as caregiver |
| `gift`, `debt`, `task` | One note each, starting "Gift", "Debt" or "Task" and listing its properties |

## Natures and relationship types

| Monica 4 key | Monica key |
| --- | --- |
| `new_job`, `marriage`, `home_improvement`, `achievement_or_award` | `new_job`, `marriage`, `home_improvement`, `achievement` |
| `new_child`, `moved`, `travel`, `holidays`, `end_of_relationship` | `birth`, `move`, `trip`, `holiday`, `separation` |
| `bought_a_home`, `new_vehicle` | `major_purchase` |
| `surgery`, `dentist` / `new_sport` / `new_hobby`, `new_instrument` | `hospital_stay`, `medical_appointment` / `exercise` / `hobby` |
| Any other nature | The account's own nature, named from the key, in the matching category |
| `parent`/`child`, `sibling`, `grandparent`/`grandchild`, `uncle`/`nephew`, `cousin` | `parent_child`, `sibling`, `grandparent_grandchild`, `aunt_uncle_niece_nephew`, `cousin` |
| `partner`, `date`, `lover` / `spouse` / `friend`, `bestfriend` / `colleague` | `partner` / `spouse` / `friend` / `colleague` |
| Any other type (`godfather`, `ex`, `mentor`) | The account's own type, named from the Monica 4 pair, created once; the reverse row is skipped (R6) |

## The account

| Monica 4 | Monica |
| --- | --- |
| `user.properties.me_contact` | Preselected as Me (FR-004) |
| `journal_entries` of type `entry` | `reflection` life event with Me, title and post as narrative |
| `journal_entries` of type `day` | A note on Me: "Day rated :rate", with its comment |
| `audit_logs`, `modules`, `reminder_rules`, `addressbook`, `addressbook_subscription`, other users, `google2fa_secret` | Not imported, counted |
