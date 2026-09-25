# Data model: Basic animal template

No table or column changes. Rows written per account, in this order, in one transaction.

| Row | Values |
| --- | --- |
| `value_lists` | `key` `animal_sexes`, `is_system`, items `female`, `male`, `unknown` (only if missing) |
| `entity_types` | slug `animals`, "Animal", "Animals", "Pets and other animals you want to remember.", icon `pet`, colour `#9a6a24`, active, last position |
| `templates` | slug `basic-animal`, `name_translation_key` "Basic animal", `is_system`, active |
| `structures` | `applies_to` `entity`, `entity_type_id` the type above |
| `structure_versions` | version 1, published |
| `structure_version_pages` | `general`, "General" |
| `sections` + `section_versions` | four, version 1 published, R6 slugs |
| `fields` | below |
| `vault_default_templates` | per vault without one: `applies_to` `entity`, the type, version 1 |

## Fields

| Section | Key | Type | Notes |
| --- | --- | --- | --- |
| Animal details | `species` | text | |
| | `breed` | text | |
| | `sex` | select | `animal_sexes`, no default |
| | `birthday` | recurring_date | highlighted, like Basic contact's |
| | `adoption_date` | recurring_date | |
| | `microchip_number` | text | not unique |
| People | `caregivers` | repeater | newest last |
| | `caregiver_person` | relation → contact | child, required, `distinct_in_list` |
| | `caregiver_role` | text | child |
| Care | `food_and_routine` | textarea | |
| | `health_and_care` | textarea | label "Health and care notes" |
| Notes | `animal_notes` | textarea | label "Notes" |

Every top-level field `is_required = false`. No `field_values` or `field_value_rows` are written.
