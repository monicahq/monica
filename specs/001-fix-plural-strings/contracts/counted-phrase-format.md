# Contract: How a phrase that shows a number is written

This is the contract every contributor and coding agent follows, and the one the check enforces. It is
the source for the rule that goes into the Translations section of `.claude/rules/code-style.md`.

## The three invariants

### 1. A phrase containing `:count` is requested through `trans_choice`

```php
// Right
trans_choice(':count person|:count people', $count)

// Wrong: one wording for every number
__(':count people', ['count' => $count])

// Wrong: English grammar imposed on nine languages
$count === 1 ? __('1 person') : __(':count people', ['count' => $count])
```

`trans_choice` supplies `count` itself. Passing it again is harmless but unnecessary. Other
placeholders are passed as usual:

```php
trans_choice(':count value written in :label|:count values written in :label', $count, ['label' => $label])
```

### 2. Every segment interpolates `:count`

```php
// Right
trans_choice(':count person|:count people', $count)

// Wrong: renders "1 personne" to a French reader at a count of zero
trans_choice('1 person|:count people', $count)
```

French groups zero with the singular, so the singular segment is what a French reader sees at zero. A
literal digit in that segment is a bug that English can never reveal.

### 3. A phrase whose wording does not vary says so with `[0,*]`

```php
// Right: one wording, declared as covering every number
trans_choice('[0,*]:count of :total shown.', $count, ['total' => $total])

// Wrong: the prefix is printed to the reader, because __() does not strip it
__('[0,*]:count of :total shown.', ['count' => $count, 'total' => $total])

// Wrong: silence is not a declaration, and this is indistinguishable from a missing segment
__(':count of :total shown.', ['count' => $count, 'total' => $total])
```

Declare only after checking. "It reads fine at one in English" is not the test: `1 lines` and
`1 published something this week` both read wrongly, and a phrase that is invariant in English can
still inflect elsewhere.

## How many segments each locale gets

Derived at check time from the framework, never written down twice.

| Locale | Segments |
|---|---|
| en, de_DE, es_ES, fr_FR, it_IT, nl_NL, pt_BR, pt_PT | 2 |
| tr_TR | 1 |

A locale is given exactly what its language requires. Turkish selects segment 0 at every number, so a
second Turkish segment is text no reader can reach and the check rejects it.

## What a translator sees

One entry per phrase, holding the whole sentence:

```json
{
  ":count person|:count people": ":count personne|:count personnes",
  "[0,*]:count of :total shown.": "[0,*]:count sur :total affichés."
}
```

The `[0,*]` prefix is kept in the translated value. It is stripped at render time, not at translation
time.

## Empty states are not counted phrases

A screen that says something else entirely when there is nothing keeps saying it. Zero reaches the
counted phrase only where no such wording exists.

```php
if ($vault->contact_count === 0) {
    return __('Nobody in it yet');
}

return trans_choice(':count person|:count people', $vault->contact_count);
```

This is not a violation of invariant 1. The comparison is against zero, choosing between an empty state
and a counted phrase, and it never picks between two wordings of the same sentence.
