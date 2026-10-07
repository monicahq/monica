# Contract: vCard 4 names

## Export

Kim Min-ji, displayed as 김민지, with a transliteration and a pronunciation:

```text
N;ALTID=1;PROP-ID=8f…a1;LANGUAGE=ko;SCRIPT=Hang:김;민지;;;
FN;ALTID=1;PROP-ID=8f…a1;LANGUAGE=ko;SCRIPT=Hang;PREF=1:김민지
N;ALTID=1;PROP-ID=3c…07;LANGUAGE=ko;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim;Min-ji;;;
FN;ALTID=1;PROP-ID=3c…07;LANGUAGE=ko;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim Min-ji
N;ALTID=1;PROP-ID=d2…5e;LANGUAGE=ko;SCRIPT=Latn;PHONETIC=x-rr:Gim;Minji;;;
NICKNAME:…
```

- `PROP-ID` is the representation uuid, shared by its `N` and `FN`.
- A contact holding one plain written name (no language, no script) exports `N` and `FN` as before, without parameters.
- A `FN` that only repeats the parts is not kept on import; a family first reading sets the name order instead.
- Sabre validates `N` and `FN` as single properties; a card whose alternatives share one `ALTID` is validated on its first pair and kept whole.
- A contact with none writes `FN:` with the translated Unnamed contact and an empty `N`, as today.
- vCard 3.0 targets receive only the `PREF=1` pair, stripped of `ALTID` and `PROP-ID` (research R7).

## Import

| Card received | Effect |
| --- | --- |
| One `N` (and its `FN`) | The representation its `PROP-ID` names, else the displayed one, is updated; no other is touched |
| Several `N` | Full set reconciled: matched rows updated, unmatched created, missing deleted |
| New contact | Every pair created; `FN;PREF=1` displayed, else first non phonetic |
| `N` with `PHONETIC` | Kind phonetic, system = the token |
| `X-NAME-KIND=transliteration` | Kind transliteration |
| `SORT-AS`, unknown parameters | Kept on the kept card, copied back on export by `PROP-ID` |

Pairing `N` with `FN`: same `PROP-ID`, else same `LANGUAGE` and `SCRIPT`, else order of appearance.
An `FN` with no `N` becomes a representation holding only `formatted_name`.

## Fixtures

`tests/Fixtures/Dav/multilingual-names.vcf` (three pairs, one phonetic) and
`tests/Fixtures/Dav/single-name-client.vcf` (one plain `N` for a contact Monica holds three names for).
