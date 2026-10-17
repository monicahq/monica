# Quickstart: Paginate long lists

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/PageSizeTest.php tests/Feature/Views/Components/PaginationTest.php tests/Feature/Views/Components/ListPanelTest.php
php artisan test --compact tests/Unit/ViewModels tests/Feature/Controllers/App
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Then the whole suite, since `x-list-panel` changes every screen that uses it.

| Scenario | Where | Expected |
| --- | --- | --- |
| 3,000 contacts, People list opens | `ContactsViewModelTest` | 50 rows, total 3,000, `?page=2` next, every row once across pages |
| `?size=100&page=7` on People | `ContactControllerTest` | rows 601 to 700, filter and sort kept in the links |
| `?size=all` | `PageSizeTest`, `EntitiesViewModelTest` | one page holding every row, no page links |
| `?size=7` or `?page=0` | `PageSizeTest`, a controller test | default size, first page, never an error |
| Merge picker with `?query=dwi` | `ContactMergeViewModelTest` | only matching contacts, 20 a page |
| 200 notes on one contact | `ContactFieldRowsViewModelTest` | 10 newest first, `?page=2` next; 30 phone numbers in position order |
| Section saved while 200 notes exist | `SetFieldValuesTest` or `ContactSectionControllerTest` | every note still there |
| 60 answered reminders | `ContactRemindersViewModelTest` | 25 on the History filter, chip counts 60 |
| 120 delivery events | `ReminderHistoryTest` | page 1 holds the 50 latest, `hasMore`, page 3 holds the oldest 20 |
| 30 notifications | `NotificationControllerTest` | 25 then Load more |
| Filtered admin accounts, page 2 | `AccountControllerTest` | filter kept, `aria-current` on 2 |
| Wrong vault | each controller test | 404 on page 2 as on page 1 |

## By eye

Open the People list with more than one page, in light and dark, LTR and RTL (`dir="rtl"` in the inspector if no RTL locale is configured), at 375 px and 1440 px, zoomed to 200%.

- Tab reaches Previous, each number, Next, the size select; focus visible on each; the page never scrolls sideways.
- Change the size: page 1 at that size, filter and sort kept, the select shows the size.
- On a Load more list: after Load more, focus is on the new Load more link; a screen reader (Orca or VoiceOver) announces the rows added; on the last page it lands on the end of list line.
- Edit a section holding 30 notes and save without changing anything: all 30 remain.
