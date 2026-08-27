# Contract: the per-locale typography declaration

**Spec**: [spec.md](../spec.md) | **Requirements**: FR-001, FR-002, FR-003, FR-017

Where a locale states how its copy is written, and what the check may assume about what it finds
there.

## Location

`config('monica.locales.{locale}.typography')`, inside the entry that locale already has in
`config/monica.php`.

## Shape

```php
'typography' => [
    'apostrophe' => string,
    'space_before' => array<string, string>,
    'quotes' => array{open: string, close: string, inner_space?: string},
    'forbidden_words' => list<string>,
],
```

A locale names what it uses, never what it refuses. Declaring `apostrophe` makes every other
apostrophe wrong for that language; declaring `quotes` makes every other quotation mark wrong for it.
There is no forbidden list to keep in step, and a locale cannot forbid what it requires.

## Guarantees the check may rely on

- Every key is optional. A missing key means that rule is not checked for that locale.
- An absent `typography` key and an empty array behave identically: the locale has no rules, and every
  value it holds passes. This is what FR-017 requires, and it is what keeps the other eight locales
  green while nobody has written their rules.
- A locale that declares rules is checked against exactly those rules and no others. There is no
  inherited default and no fallback to French.
- The characters are data. The check compares against what it is given and knows nothing about French.
  Giving German a `space_before` of `[]` and a `forbidden_quotes` of `['«', '»']` would work without a
  line changing in the command.

## Guarantees the config owes

- `space_before` keys are single punctuation characters, and its values are single space characters.
- `quotes.inner_space` is written only by a language that puts a space inside its quotation marks.
  French is the only one, and a locale that omits it is not checked for it.
- Invisible characters are written as escape sequences (`"\u{202F}"`), never pasted literally, so the
  config stays reviewable.

## What the shape cannot say

- **That a mark takes no space before it.** `space_before` requires a space; it cannot require the
  absence of one. Eight of the nine languages want that, French being the exception.
- **That a sentence must contain something.** Spanish opens a question with `¿`, and no key expresses
  a requirement to contain a character rather than to avoid one.

## Adding a tenth locale

Nothing to do. A new locale with no `typography` key passes, and the `add-locale` skill carries a step
saying that writing its typography is optional and how to do it.

## Adding rules to one of the existing eight

Write the array, write the document section, done. No change to the command, no new test beyond the
locale's own. That is SC-009.
