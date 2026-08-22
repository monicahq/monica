# Quickstart: validating correct plural phrasing

Runnable checks that prove the feature works. Implementation belongs in `tasks.md`; this file is how
you tell whether it landed.

## Prerequisites

```bash
composer install
cp -n .env.example .env && php artisan key:generate   # if .env is absent
```

Nothing here needs a database except the view model tests, which use `RefreshDatabase`.

## 1. Reproduce the defect before fixing it

The fastest proof that the bug is real, with no database:

```bash
php artisan tinker --execute="app()->setLocale('en'); echo __(':count people', ['count' => 1]);"
# 1 people
php artisan tinker --execute="echo __('Opened :count times', ['count' => 1]);"
# Opened 1 times
php artisan tinker --execute="echo __('Remember me for :count days', ['count' => 1]);"
# Remember me for 1 days
```

## 2. Confirm the mechanism, and the three findings behind the design

Segments needed per configured locale, probed from the framework (research D3):

```bash
php artisan tinker --execute="
\$s = new Illuminate\Translation\MessageSelector();
foreach (array_keys(config('monica.locales')) as \$l) {
  \$i = []; foreach (range(0, 200) as \$n) { \$i[\$s->getPluralIndex(\$l, \$n)] = true; }
  echo str_pad(\$l, 8), ' -> ', max(array_keys(\$i)) + 1, PHP_EOL;
}"
```

Expected: 2 for every locale except `tr_TR`, which is 1.

Why every segment must interpolate `:count`, in French (research D2):

```bash
php artisan tinker --execute="
\$s = new Illuminate\Translation\MessageSelector();
echo 'literal: ', \$s->choose('1 personne|:count personnes', 0, 'fr_FR'), PHP_EOL;
echo 'count:   ', \$s->choose(':count personne|:count personnes', 0, 'fr_FR'), PHP_EOL;"
```

Expected: `literal: 1 personne` (the defect) and `count: :count personne`, which renders "0 personne".

Why a declared phrase must go through `trans_choice` (research D4):

```bash
php artisan tinker --execute="
echo 'trans_choice: ', trans_choice('[0,*]:count of :total shown.', 3, ['total' => 12]), PHP_EOL;
echo '__():         ', __('[0,*]:count of :total shown.', ['count' => 3, 'total' => 12]), PHP_EOL;"
```

Expected: `3 of 12 shown.` and `[0,*]3 of 12 shown.` The second is why the rule is uniform.

## 3. The check catches both bad patterns

```bash
php artisan monica:localize --check
```

Expected once the feature has landed: exit 0, nine locales with nothing missing.

Then prove it fails. Introduce each pattern in a scratch file under `app/`, run the check, and remove
it:

```bash
# Pattern one: a number with no plural wordings
printf '<?php\nclass Scratch { public function a(): string { return __(":count widgets", ["count" => 1]); } }\n' > app/Scratch.php
php artisan monica:localize --check; echo "exit: $?"
# Expected: non zero, naming ":count widgets" and app/Scratch.php

# Pattern two: a wording chosen by comparing a count to one
printf '<?php\nclass Scratch { public function a(int $n): string { return $n === 1 ? __("1 widget") : __(":count widgets", ["count" => $n]); } }\n' > app/Scratch.php
php artisan monica:localize --check; echo "exit: $?"
# Expected: non zero, naming app/Scratch.php

# A declared phrase is accepted
printf '<?php\nclass Scratch { public function a(int $n): string { return trans_choice("[0,*]:count of :total shown.", $n, ["total" => 9]); } }\n' > app/Scratch.php
php artisan monica:localize --check; echo "exit: $?"
# Expected: 0

rm app/Scratch.php
php artisan monica:localize --check
```

## 4. Every counted phrase reads correctly at zero, one, two and a large number

The walk SC-001 measures. Run the suite first:

```bash
php artisan test --filter=Plural
php artisan test tests/Unit/ViewModels
php artisan test tests/Unit/Console/Commands/LocalizeCommandTest.php
php artisan test tests/Unit/Models/LogTest.php
```

Then spot check the two locales whose rules differ from English (FR-013):

```bash
php artisan tinker --execute="
app()->setLocale('fr_FR');
foreach ([0, 1, 2] as \$n) { echo \$n, ' -> ', trans_choice(':count person|:count people', \$n), PHP_EOL; }
app()->setLocale('tr_TR');
foreach ([1, 5] as \$n) { echo \$n, ' -> ', trans_choice(':count person|:count people', \$n), PHP_EOL; }"
```

Expected under French, once translated: `0 personne`, `1 personne`, `2 personnes`. Under Turkish: the
same wording at both numbers.

## 5. Screens still read

The counted phrases people meet first:

```bash
php artisan serve
```

| Screen | What to look at | With a count of one |
|---|---|---|
| Vault dashboard | The headcount | A singular, never "1 people" |
| Vault list | The chip under each vault | "1 person", and "Nobody in it yet" at zero |
| Contact share links | The visit count | "Opened 1 time" |
| Login | The remember me label, with `MONICA_REMEMBER_DURATION_DAYS=1` | "Remember me for 1 day" |
| Settings, templates | The template and type counts | A singular |
| Changelog on a vault | A sweep or a set aside entry | A singular |

## 6. The gates the constitution requires

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
php artisan monica:localize --check
```

All four clean is the definition of done for this change.

## 7. The written rule is present

```bash
grep -A 12 '## Translations' .claude/rules/code-style.md
```

Expected: the three invariants from
[contracts/counted-phrase-format.md](./contracts/counted-phrase-format.md), stated in the same terms
the check enforces, so following the written rule is enough to pass it.

```bash
grep '\[0,\*\]' lang/en.json
```

Expected: a short list, every phrase that claims its wording does not depend on its number, readable in
one sitting as SC-010 requires.
