# Translations

How each language punctuates and addresses its reader. One section per configured locale.

The strings themselves and the `monica:localize` workflow are in the
[translations skill](../skills/translations/SKILL.md). This file is only about how a translated value
is written once you are writing it.

A rule here is enforced. Every locale declares its rules in its own entry of `config/monica.php`,
under `typography`, and `php artisan monica:localize --check` refuses a value that breaks one. A
locale with nothing declared is not checked, and adding rules for one is a matter of writing this
section and that array, and nothing else.

A locale names the characters it uses, never the ones it refuses. Declaring `apostrophe` makes every
other apostrophe wrong for that language, and declaring `quotes` makes every other quotation mark
wrong for it. There is no list of forbidden characters to keep in step, and no way to forbid what you
require: German closes with `“`, which is exactly why it cannot also be told to refuse it.

A span between backticks is code, and code is written the same way in every language. The check skips
it, the same way it skips a `:placeholder`. This is what lets a sentence carry a JSON sample, whose
straight quotes and unspaced colons no language may repunctuate without breaking it.

Every rule is written so that a single value either satisfies it or does not, without reference to
any other string. That is what lets the check name the value and the rule rather than report a count.

## French (`fr_FR`)

### The apostrophe

French always uses the typographic apostrophe `’`, U+2019. The straight apostrophe `'`, U+0027, is
never correct in French copy.

```text
l’image, aujourd’hui, qu’il
```

### The space before a double punctuation mark

French writes a space before `:`, `;`, `!` and `?`. That space is typographic rather than a word
break, so it must be one that cannot be broken across a line. Otherwise the mark is pushed onto the
next line on its own, which is what a reader sees as a mistake.

Which space depends on the mark, following the Imprimerie nationale:

| Mark | Space before it |
| --- | --- |
| `:` | U+00A0, the full non-breaking space |
| `;` | U+202F, the narrow non-breaking space |
| `!` | U+202F |
| `?` | U+202F |

Both are invisible in an editor and neither can be told from an ordinary space by looking, which is
why they are named here by code point and written in `config/monica.php` as `"\u{00A0}"` and
`"\u{202F}"` rather than pasted. In `lang/fr_FR.json` they are stored as the characters themselves,
because the file is written with unescaped Unicode so that accented letters stay readable.

A colon that opens a placeholder is not punctuation and takes no space. `:name`, `:count` and `:app`
are left exactly as they are, and `Supprimer :name ?` needs a space before the `?` and none before
the `:`.

### Quotation

French quotes with the guillemets `«` and `»`, with a narrow non-breaking space U+202F inside each of
them. Straight double quotes `"` and curly double quotes `“` `”` are never correct in French copy.

```text
Supprimer « :name » ?
```

The spaces shown inside the guillemets above are U+202F.

### Addressing the reader

French addresses the reader as *vous*, everywhere, and never as *tu*.

The check refuses `tu`, `toi` and `tes`. It deliberately says nothing about `ton` and `ta`, which are
also ordinary French words, `un ton` being a shade. A rule that refused those would sooner or later
block a correct string, and there is no way to accept one: the check keeps no list of exceptions.
Writing *vous* is the rule; the check catches only the part of it that can be caught without ever
being wrong.

### What these rules do not cover

- **How a number or a date is written.** That is a per-user setting resolved elsewhere, through
  `date_format` and `number_format`. Nothing here touches it.
- **Which wording a phrase takes at which number.** That is the counted-phrase rule, and it lives in
  [code-style.md](code-style.md) with the rest of `trans_choice()`.
- **Word choice, sentence length and tone.** The translations skill asks for the plain, calm register
  of the English source, and that is where it stays.
- **Content a user wrote.** It carries the language its author wrote it in and is never touched.

## English (`en`)

The typographic apostrophe `’`, and `“…”` for quotation.

English is the source language: its strings are the keys, written in the code rather than translated.
The check skips the source locale entirely, so these rules describe what the English strings already do
and enforce nothing today. Making them bite would mean failing the build over an apostrophe typed in a
`__()` call, which is a decision about how the code is written rather than about how it is translated.

## German (`de_DE`)

The typographic apostrophe `’`, rare in German but real (`geht’s`, `Hans’ Buch`).

Quotation is `„…“`, opening low and closing high. No other language here does that, so a German value
holding `“…”` is quoting the English way.

## Spanish (`es_ES`)

Quotation is `«…»`, the comillas latinas the RAE prefers. Spanish has no apostrophe rule because
Spanish has no apostrophes.

Spanish also opens a question with `¿` and an exclamation with `¡`, which the check cannot express: it
knows how to refuse a character and how to require a space before one, but not how to require that a
sentence contains something. The shipped file gets this right in all 34 of its questions, unenforced.

## Portuguese, Portugal (`pt_PT`)

Quotation is `«…»`, the aspas angulares.

## Portuguese, Brazil (`pt_BR`)

Quotation is `“…”`. This is the one place the two Portuguese locales deliberately differ, and it is
why they are two locales rather than one.

## Dutch (`nl_NL`)

The typographic apostrophe `’`, which Dutch leans on heavily: plurals of vowel-final nouns (`auto’s`,
`pagina’s`), genitives (`Anna’s`), elisions (`’s ochtends`).

Quotation is `“…”`.

## Italian (`it_IT`)

The typographic apostrophe `’`. Italian elides constantly (`l’amico`, `un’altra`, `dell’anno`), so
this is the rule that touches the most Italian strings by far.

Quotation is `«…»`, the virgolette caporali.

## Turkish (`tr_TR`)

The typographic apostrophe `’`, which Turkish uses grammatically rather than decoratively: it
separates a suffix from the proper noun it attaches to (`Monica’dan`, `5 MB’a`).

Quotation is `“…”`.

## The glossary

A product noun is translated once per language and then never again. The same English word must reach
the reader as the same word on every screen, which is what ASD-STE100 calls one word for one meaning,
and what a reader notices immediately when it is broken. Sixteen nouns carry the product, and around
800 of the 2,206 strings hold at least one of them.

Fill a column when you translate a language. A blank cell is a language nobody has fixed the words
for yet, and the first person to translate it decides.

| English | Strings | zh_CN | ja_JP | fr_FR | de_DE | es_ES | it_IT | nl_NL | pt_PT | pt_BR | tr_TR |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| template | 127 | 模板 | テンプレート | | | | | | | |
| account | 119 | 账户 | アカウント | | | | | | | |
| section | 107 | 区块 | セクション | | | | | | | |
| vault | 87 | 保险库 | 保管庫 | | | | | | | |
| field | 76 | 字段 | フィールド | | | | | | | |
| reminder | 76 | 提醒 | リマインダー | | | | | | | |
| contact | 59 | 联系人 | 連絡先 | | | | | | | |
| passkey | 49 | 通行密钥 | パスキー | | | | | | | |
| relationship | 35 | 关系 | 関係 | | | | | | | |
| value list | 26 | 选项列表 | 選択リスト | | | | | | | |
| marketplace | 17 | 市场 | マーケットプレイス | | | | | | | |
| note | 14 | 备注 | メモ | | | | | | | |
| share | 10 | 分享 | 共有 | | | | | | | |
| API key | 10 | API 密钥 | APIキー | | | | | | | |
| group | 5 | 群组 | グループ | | | | | | | |
| activity | 2 | 活动 | アクティビティ | | | | | | | |

Add a column for a language rather than a row for a word. A word that is not a product noun does not
belong here: this table exists to stop one concept acquiring two names, not to hold a dictionary.

## Which languages a native speaker has read

A locale ships whether or not somebody who reads it has checked it, because holding a translation
back helps nobody who would rather read it than read English. What must not happen is losing track of
which is which. This table is that record, and it is the only place the answer lives.

| Locale | Read by a native speaker | Notes |
| --- | --- | --- |
| en | Source language | The strings are the keys, written in the code |
| zh_CN | No | One wording for every number. Declares no typography |
| ja_JP | No | One wording for every number. Declares no typography |
| fr_FR | No | Typography declared in full |
| de_DE | No | |
| es_ES | No | |
| it_IT | No | |
| nl_NL | No | |
| pt_PT | No | |
| pt_BR | No | |
| tr_TR | No | |

Add a row when a locale is registered. Change a No to a Yes only when somebody who reads the language
has read the file, and say in the notes what they covered.

## Writing rules for a language that has none

1. Write its section above, saying what the rule is and why, in the shape the French section uses.
2. Declare it in that locale's entry of `config/monica.php`, under `typography`. The keys are
   `apostrophe`, `quotes`, `space_before` and `forbidden_words`, all optional, and a rule you do not
   declare is not checked. Name what the language uses; what it refuses follows from that.
3. Correct whatever the check then reports, because a rule the shipped file breaks is a rule nobody
   is following.

Nothing in the command needs changing. It knows no language, only what a locale declares.
