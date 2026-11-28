---
id: 01M8H4XYWG568SMA2MVJYG7N25
title: An item with an impossible date is now left out with a clear reason when you import from Monica Classic
type: fix
area: Import
published_at: 2026-11-28T13:18:51Z
---
Until now, when a file from Monica Classic held a date of 0000-00-00, the import either refused the item without saying why or wrote a birthday in the year -1. Now a life event, a call, a conversation, an activity or a journal entry with such a date is left out, and the import report says its date is not a valid calendar date. A contact with such a birthday is imported without the birthday.
