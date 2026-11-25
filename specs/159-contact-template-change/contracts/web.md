# Contract: web

The layout is the design `Edit Contact.dc.html` (claude.ai/design project `d4c00f28-…`), read on
2026-11-25. Research R2 describes it and the one deviation.

## `GET vaults/{vault}/contacts/{contact}/edit`

The existing `contacts.edit`, two optional query parameters: `template` (a template id) and `version` (a
structure version id of that template).

The "Template" panel, after "Change the name", is shown when the account offers at least one contact
template beside the current version (another template, or another published version of the current one).
It holds the label "Template used for :name", the help bubble (`guide="contacts.change-template"`), the
picker, the "Read more" disclosure and, with `template`, the confirmation block.

The picker's menu lists the offered templates in the account's order (those
`ContactFormViewModel::templates()` offers) and, for each, its published versions, latest first. Each line
is a link to `contacts.edit?template=…&version=…` with `x-target="template-change"`. The current version
is the checked line.

The confirmation (`id="template-change"`, `x-merge="update"`) appears when `template` and `version` name
another version than the current one. It is a `section` labelled by its `h3` title, which takes the focus
through `x-autofocus`. It holds the sentence, "No data will be lost.", the preview lists of research R6
(fields with their section, then reminders), and a footer with "Cancel" (back to `contacts.edit`, focus
returns to the trigger) and "Change template", a form `PUT` to `contacts.template.update` with `template`
and `structure_version_id`. A refused template shows its sentence instead, and "Change template" stays
with `aria-disabled` pointing at it. While nothing is pending, the same container holds the "Read more"
line and the consequences.

The page header no longer carries the "Current template" link.

## `PUT vaults/{vault}/contacts/{contact}/template`

Route name `contacts.template.update`, `App\Vaults\ContactTemplateController@update`. Redirects to
`contacts.show` with the status "`:name` uses the `:template` template now." The contact page already says
when answers are hidden (`ContactViewModel::setAsideLine()`), so the flash adds no second sentence. A
refusal returns back with the action's sentence on `template`. A template of another account, or a
contact reached through another vault, answers 404.

## Version history

An entry for a published version of another active template offers Revert, posting to
`contacts.template.update`. A turned off template says "This template is turned off, so nothing can be put
back on it."
