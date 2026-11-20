# Data model: Richer supplied animal template

No table or column changes. Everything below is rows written by `CreateDefaultTemplate` into existing
tables, described by enum cases. Names in `code` are the stable identities; labels are translation keys.

## Template

| Enum case | Slug | Label | Applies to | Kind | Vault default |
| --- | --- | --- | --- | --- | --- |
| `DefaultTemplateEnum::Animal` | `animal` | Animal | Entity | `animals`, reused when held | No: Basic animal stays the default |

Description: "Remember the animals that matter, their habits and the people who look after them."

## Pages

| Enum case | Stored key | Label | Sections, in order |
| --- | --- | --- | --- |
| `DefaultPageEnum::AnimalProfile` | `profile` | Profile | AnimalIdentity, AnimalLinkedPeople, AnimalCharacter, AnimalPhotosAndNotes |
| `DefaultPageEnum::AnimalDailyLife` | `daily-life` | Daily life and care | AnimalFoodAndHabits, AnimalHealth, AnimalIdentification |

## Sections and fields

Field keys marked *shared* already exist and are reused (R7). Every other key is new.

| Section case (slug) | Label | Field key | Kind | Label in this section | Highlighted |
| --- | --- | --- | --- | --- | --- |
| `AnimalIdentity` (`animal-identity`) | Identity | `species` *shared* | Text | Species | Yes |
| | | `breed` *shared* | Text | Breed or mix | Yes |
| | | `sex` *shared* | Select, Animal sexes | Sex | No |
| | | `birthday` *shared* | Recurring date, year optional | Date of birth | Yes |
| | | `adoption_date` *shared* | Recurring date | Adoption date | No |
| | | `colour_and_markings` | Textarea | Colour and distinctive marks | No |
| `AnimalLinkedPeople` (`animal-linked-people`) | People | `caregivers` *shared* | Repeater | People linked to the animal | No |
| | | ↳ `caregiver_person` *shared* | Relation, contact | Person | No |
| | | ↳ `caregiver_role` *shared* | Text | Role | No |
| `AnimalCharacter` (`animal-character`) | Character and preferences | `character` | Textarea | Character | No |
| | | `likes` | Textarea | What they like | No |
| | | `dislikes_and_fears` | Textarea | What they dislike or fear | No |
| | | `approach_advice` | Textarea | How to approach them | No |
| `AnimalPhotosAndNotes` (`animal-photos-and-notes`) | Photos and notes | `photo` *shared* | Repeater | Photos | No |
| | | ↳ `image` *shared* | Image | Image | No |
| | | ↳ `caption` *shared* | Text | Caption | No |
| | | `notes` *shared* | Repeater, newest first | Free-form notes | No |
| | | ↳ `note` *shared* | Textarea | Note | No |
| `AnimalFoodAndHabits` (`animal-food-and-habits`) | Food and habits | `usual_food` | Textarea | Usual food | No |
| | | `feeding_times_and_amounts` | Textarea | Times and amounts | No |
| | | `favourite_treats` | Textarea | Favourite treats | No |
| | | `routine_and_activities` | Textarea | Routine and activities | No |
| | | `pet_sitting_instructions` | Textarea | Pet sitting instructions | No |
| `AnimalHealth` (`animal-health`) | Health and care | `allergies` *shared* | Textarea | Known allergies and intolerances | No |
| | | `health_issues` | Textarea | Health issues to know about | No |
| | | `treatments_and_instructions` | Textarea | Treatments and instructions | No |
| | | `neuter_status` | Select, Neuter statuses | Neutered or spayed | No |
| | | `other_usual_care` | Textarea | Other usual care | No |
| `AnimalIdentification` (`animal-identification`) | Identification and documents | `microchip_number` *shared* | Text | Microchip number | No |
| | | `other_identification` | Text | Other identification | No |
| | | `insurance` | Text | Insurance | No |
| | | `insurance_policy_number` | Text | Policy number | No |
| | | `document` *shared* | Repeater | Documents | No |
| | | ↳ `file` *shared* | File | File | No |
| | | ↳ `description` *shared* | Text | Description | No |

Every field is optional. Section icons and colours follow the existing animal sections: identity
`pet`/amber, people `people`/blue, character `heart`/plum, photos and notes `memory`/violet, food
`food`/amber, health `health`/teal, identification `file`/slate.

## Value list

| Enum case | Key | Label | Items (`NeuterStatusEnum`) |
| --- | --- | --- | --- |
| `ValueListEnum::NeuterStatuses` | `neuter_statuses` | Neuter statuses | Yes, No, Unknown |

Description: "Whether an animal is neutered or spayed". Supplied, editable like Animal sexes.

## What moves with an animal from Basic animal

| Basic animal field | Follows to | Rule that matches it |
| --- | --- | --- |
| species, breed, sex, birthday, adoption_date | Identity | key and type, other section |
| microchip_number | Identification and documents | key and type, other section |
| caregivers and its rows | People | key and type, children under their repeater |
| food_and_routine, health_and_care, animal_notes | nothing | set aside, recoverable |

## Rules the backfill applies, per account

1. Animal already supplied, soft deleted included: nothing.
2. A template or section of the account's own with an Animal slug, soft deleted included: warning, nothing.
3. The `animals` kind soft deleted: warning, nothing.
4. Otherwise: the Neuter statuses list if missing, then Animal on the existing kind (or a new one when
   the account never had it). No vault default is written.
