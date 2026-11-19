# Data model: Four supplied contact templates

No table changes. Everything below is rows written by `CreateDefaultTemplate` and `CreateDefaultValueLists`, declared in the enums ([research.md](research.md) R1, R2, R7). Labels and kinds are in [spec.md](spec.md). Page enum cases are one per template page (`EssentialsEveryday`, `FriendsEveryday`…).

## Templates (`DefaultTemplateEnum`)

| Case | Slug | Vault default | Pages (key) |
| --- | --- | --- | --- |
| BasicContact | `basic-contact` | yes | unchanged |
| BasicAnimal | `basic-animal` | yes | unchanged |
| Essentials | `essentials` | no | `everyday` |
| FriendsAndFamily | `friends-and-family` | no | `everyday`, `tastes-and-attentions` |
| ProfessionalNetwork | `professional-network` | no | `professional-profile`, `relationship` |
| FullPortrait | `full-portrait` | no | `profile`, `daily-life`, `memories` |

## Sections (`DefaultSectionEnum`), slugs in page order

| Template | Section slugs |
| --- | --- |
| Essentials | `essentials-landmarks`, `essentials-contact-details`, `essentials-how-we-met`, `essentials-notes` |
| Friends and family | `friends-landmarks`, `friends-contact-details`, `friends-how-we-met`, `friends-interests`, `friends-at-the-table`, `friends-gift-ideas`, `friends-notes` |
| Professional network | `professional-current-role`, `professional-contact-details`, `professional-career`, `professional-first-meeting`, `professional-common-ground`, `professional-documents`, `professional-notes` |
| Full portrait | `portrait-landmarks`, `portrait-contact-details`, `portrait-how-we-met`, `portrait-work-and-studies`, `portrait-tastes-and-habits`, `portrait-communication`, `portrait-attentions`, `portrait-photos`, `portrait-documents`, `portrait-notes` |

## Field keys (`DefaultFieldEnum`)

Reused from Basic contact: `category`, `gender`, `birthday`, `emails`/`email`, `phones`/`phone`, `addresses`/`address`, `website`, `notes`/`note`, `document`/`file`/`description`, `photo`/`image`/`caption`.

| New key | Type | Value list | Children |
| --- | --- | --- | --- |
| `pronouns` | text | | |
| `languages_spoken`, `languages_to_use` | multiselect | `languages` | |
| `preferred_channel` | select | `communication_channels` | |
| `met_on` | date | | |
| `met_where` | text | | |
| `met_context` | textarea | | |
| `introduced_by` | relation (contact) | | |
| `hobbies` | multiselect | `hobbies` | |
| `industry` | select | `industries` | |
| `expertise` | multiselect | `areas_of_expertise` | |
| `job_role`, `organisation` | text | | |
| `work_address` | address | `address_labels` (label list) | |
| `professional_profile` | url | | |
| `passions`, `favourite_dishes`, `favourite_drinks`, `dietary_preferences`, `allergies`, `shared_interests`, `topics_to_discuss`, `how_to_help`, `cultural_tastes`, `habits`, `availability` | textarea | | |
| `gift_ideas` | repeater | | `gift_idea` text, `gift_idea_link` url, `gift_idea_note` textarea |
| `outing_ideas` | repeater | | `outing_idea` text, `outing_idea_link` url, `outing_idea_note` textarea |
| `experiences` | repeater | | `experience_organisation`, `experience_role` text, `experience_started_on`, `experience_ended_on` date |
| `education` | repeater | | `school`, `field_of_study` text, `education_started_on`, `education_ended_on` date |
| `online_profiles` | repeater | | `online_profile_label` text, `online_profile_link` url |

Unchanged rules: `category` and `birthday` highlighted; `email` and `phone` identifying; `notes` newest first, every other repeater newest last; nothing required. `isKeptOffTheCard()`: `gift_idea_link`, `outing_idea_link` (R6).

## Value lists (`ValueListEnum`)

| Key | Items |
| --- | --- |
| `hobbies` | reading, music, cinema, sport, cooking, travel, gardening, photography, art, games, hiking, crafts, dancing, theatre, volunteering |
| `communication_channels` | phone_call, text_message, email, instant_messaging, video_call, in_person, post |
| `industries` | agriculture, arts_and_culture, construction, consulting, education, energy, finance, health, hospitality, information_technology, legal, manufacturing, media, nonprofit, public_sector, real_estate, retail, transport |
| `areas_of_expertise` | none |
