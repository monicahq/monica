# Research: Name a template copy before it is made

## R1. Ask before creating, or allow editing the slug afterwards

**Decision**: ask for the name and the slug before the copy exists.

**Rationale**: the slug is the route key of a template (`#[RouteKey('slug')]`) and its address in the API. The edit form already declares it fixed "so old links keep working", and `UpdateTemplate` never writes it. Asking first gives the customer exactly what they asked for without opening a way to break links.

**Alternatives considered**: making the slug editable on the edit form. Rejected: every bookmarked URL, API client and MCP history entry using the old slug would break, and principle VI forbids changing what somebody relies on.

## R2. A screen of its own, or the existing template form in a third mode

**Decision**: a new screen, `templates/copy.blade.php`, with its own `TemplateCopyViewModel`.

**Rationale**: `form.blade.php` and `TemplateFormViewModel` already branch on create against edit for the title, the description, the action, the method, the cancel link, the slug and the whole "What it describes" section. A copy needs none of the description, applies to and entity type fields, so a third mode would add a branch to almost every method. The copy form is two inputs; a small screen of its own is simpler to read and to test.

**Alternatives considered**:
- A dialog over the list of templates. Rejected: a refused submission must show the error and keep the values (FR-007), which means re-rendering the dialog open after a redirect. A screen gets that for free from `old()` and `$errors`, the same as the creation form.
- A third mode of `TemplateFormViewModel`. Rejected for the reason above.

The Alpine behaviour of the slug following the name is repeated in two files (the creation form and the copy form). It carries no ARIA or keyboard contract, so the three file bar of `.claude/rules/components.md` applies and no component is extracted.

## R3. How the prefilled slug follows the name without replacing the suggestion

**Decision**: prefill the slug with the free slug the copy would get today, and make it follow the name only on `@input` of the name field while the slug is untouched.

**Rationale**: the creation form uses `x-effect="if (! slugTouched) slug = window.slugify(name)"`. `x-effect` runs once on initialisation, so on the copy form it would immediately replace `copy-of-basic-contact-2` with `copy-of-basic-contact`, a slug that is taken, and a person who changes nothing would be refused. Reacting to input on the name field keeps the suggestion until the person types a name.

`slugTouched` starts false even though the slug holds a value, because that value is a suggestion. After a refused submission it starts true, because the value is then what the person typed (`old('slug')` is set).

**Alternatives considered**: an empty slug with the suggestion as a placeholder. Rejected: a placeholder is not a value, is poorly announced by screen readers, and FR-002 asks for the slug to be shown.

## R4. Where a taken slug is refused

**Decision**: the controller validates the slug with the same rules and messages as `TemplateController::slugRules()` before calling the action.

**Rationale**: `DuplicateTemplate::slug()` passes the wanted slug through `SlugBuilder::build()`, which adds a number when it is taken. That is right for the API, which documents "made unique within your account" (FR-012), but on the screen it would silently give the person a slug they did not choose, which is the complaint. Refusing in the controller keeps the action and the API as they are.

The rules are repeated in `TemplateCopyController` rather than shared with `TemplateController`: five short lines, and the project prefers repeating short code inline over a helper (`CLAUDE.md`).

`Rule::unique('templates', 'slug')` does not filter soft deleted rows, so a slug held by a deleted template is refused, which the spec's edge case requires.

**Alternatives considered**: a strict flag on `DuplicateTemplate` that throws instead of suffixing. Rejected: it adds a mode to the action for a rule validation already expresses.

## R5. Computing the suggested slug

**Decision**: `TemplateCopyViewModel::values()` builds it with `SlugBuilder::build(wanted: null, derivedFrom: <suggested name>, maxLength: Template::SLUG_LENGTH, whenEmpty: 'template', isTaken: ...)`, the same call `DuplicateTemplate::slug()` makes, including `withTrashed()` in the lookup.

**Rationale**: the suggestion must be the slug the copy would get today (FR-002). The lookup closure is already repeated in `CreateTemplate`, `ImportTemplate` and `DuplicateTemplate`; one more copy in a view model is consistent with them.

## R6. Translatable name of a supplied template

**Decision**: the suggested name is `__('Copy of :name', ['name' => $template->name])`, the wording the action already uses, so the string exists in every locale.

**Rationale**: once the person confirms, the name is sent explicitly and stored as typed with `name_translation_key` null, which is already what the action does.

## R7. What else the change reaches (definition of done)

- **API and MCP**: no change. `resources/docs/api/11-templates.php` already documents `name` and `slug` on `templates-duplicate-create`.
- **Help centre**: `docs/portal/en/9-templates-and-customisation/3-build-your-own-template.md` Step 1 says choosing Copy opens the builder of "Copy of Basic contact", and Step 2 renames it afterwards while "the slug stays as it was". Step 1 now names the copy `Friends` on the copy form; Step 2 is removed or folded in. The screenshot of the list stays valid; a screenshot of the copy form may be added.
- **Help bubble**: none. The slug help text on the field is enough, and the creation form has none on its slug either.
- **What's new**: one entry, written with the `changelog` skill.
- **Translations**: new strings in `lang/*.json`, written by Sonnet subagents with the `translations` skill.
