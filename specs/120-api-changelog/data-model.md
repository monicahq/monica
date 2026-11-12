# Data model: API changelog

No table. A release is one file, `resources/docs/api-changelog/YYYY-MM-DD.php`, the contract every
future entry is written against.

```php
return [
    'date' => '2026-11-04',                  // required, equals the file name
    'note' => 'One sentence about the release.', // optional
    'entries' => [                           // required, at least one
        [
            'id' => 'contact-nickname',      // required, [a-z0-9-]+, unique across every release
            'kind' => 'Added',               // required: Added, Changed, Deprecated or Removed
            'group' => 'Contacts',           // required, the 'name' of a file in resources/docs/api
            'title' => 'Adds nickname to the contact object',
            'summary' => '...',              // required
            'why' => '...',                  // required, "Why it changed"
            'breaking' => false,             // optional, default false
            'impact' => '...',               // required when breaking
            'changes' => [                   // required, at least one
                [
                    'field' => 'nickname',
                    'change' => 'Added',     // Added, Changed, Deprecated or Removed
                    'endpoints' => ['GET /vaults/{vault}/contacts/{contact}'], // [] reads "All endpoints"
                ],
            ],
            'example' => [                   // optional
                'label' => 'GET contacts/{contact}',
                'before' => '{ ... }',       // optional
                'after' => '{ ... }',        // required inside an example
            ],
        ],
    ],
];
```

## Derived values

| Value | Rule |
| --- | --- |
| Visible | `group` is among `ApiDocsViewModel::groups()` names (research R2) |
| Endpoint count | distinct endpoints over `changes`; zero reads "All endpoints" |
| Latest | the newest visible release |
| Same release | the other visible entries of the entry's release |
| Endpoint link | `ApiDocsViewModel::sectionFor()`, or text when `null` (R3) |

## Classes

- `ApiChangelogEntry` (`app/ViewModels/Docs`): readonly properties built from one entry array, plus
  `date` of its release, `endpoints()`, `endpointsOf(int $change)`, `endpointCount()`, `hasBefore()`.
- `ApiChangelogViewModel`: `releases(): list<array{date: CarbonImmutable, note: ?string, groups:
  list<array{name: string, entries: list<ApiChangelogEntry>}>}>`, `entry(string $id): ?ApiChangelogEntry`,
  `sameRelease(ApiChangelogEntry): list<ApiChangelogEntry>`, `problems(): list<string>`,
  `markdown(?ApiChangelogEntry $entry = null): string`, which hands the writing to
  `ApiChangelogMarkdown`, as `ApiDocsViewModel` does with `ApiDocsMarkdown`.
