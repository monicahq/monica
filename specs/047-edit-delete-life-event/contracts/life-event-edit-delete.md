# Contract: editing, deleting and restoring a life event

Routes sit in the vault contact group of `routes/web.php`, before `contacts.show`. Each controller
checks `$contact->isReachableThrough($user, $vault)` and that the contact participates in the life
event, answering 404 otherwise.

| Route name | Method, URL under `vaults/{vault}/contacts/{contact}/` | Controller | Answers |
| --- | --- | --- | --- |
| `contacts.lifeEvents.edit` | GET `life-events/{lifeEvent}/edit` | `ContactLifeEventController@edit` | the contact page, panel in edit mode |
| `contacts.lifeEvents.update` | PUT `life-events/{lifeEvent}` | `ContactLifeEventController@update` | redirect to `contacts.show`, flash `status`, `lifeEventEdited` |
| `contacts.lifeEvents.destroy` | DELETE `life-events/{lifeEvent}` | `ContactLifeEventController@destroy` | redirect, flash `lifeEventDeleted` and its sentence |
| `contacts.lifeEventRestoration.update` | PUT `life-events/{lifeEventUuid}/restoration` | `ContactLifeEventRestorationController@update` | redirect, flash `status` |

`contacts.lifeEventFields.index` (046) accepts `?lifeEvent={uuid}` to draw the fields and values of
that life event’s own version.

## PUT fields

Those of `contacts.lifeEvents.create` (046 contract), plus `kept_files[]`: ids of file fields left
open. Custom fields are validated against the life event’s version when the type is unchanged, the new
type’s published version otherwise.

## Wording

| Where | Copy |
| --- | --- |
| Panel title and caption | "Edit life event", "Change anything, then save" / "Choose a different type" |
| Submit | "Save changes" |
| Entry badge | "Edited" (while editing, the panel stands in place of the entry) |
| Entry actions | "Edit", "Delete", named "Edit :name", "Delete :name" |
| Confirmation | "Delete this life event?", "It is removed from the timeline of :names. The people stay.", "Delete life event", "Keep it" |
| After update | "Changes saved. :type, :date, is on the timeline of :names." |
| After delete | ":type, :date, is deleted from the timeline of :names." with "Undo" |
| After restore | ":type is back on the timeline." |

## Markup contract

- The confirmation is `role="alertdialog"` with `aria-labelledby` on its question.
- Edit is a link (`<a>`, it navigates); Delete, Keep it and Undo are buttons.
- Alpine hooks: `x-target="contact-timeline"` on the edit link and all three forms.
