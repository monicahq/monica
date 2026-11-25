# Data model: Screenshots in What's new

No table changes. A screenshot is a front matter line and a file.

## In the entry

```markdown
---
id: 01M8A1B2C3D4E5F6G7H8J9K0MN
title: Screenshots in What's new
type: feature
published_at: 2026-11-26T09:00:00Z
screenshot_1: An entry of What's new with two screenshots under its description
screenshot_2: A screenshot open full screen, with the previous and next buttons
---
```

| Rule | Refused when |
| --- | --- |
| Key | matches `screenshot_<n>` with `n` from 1 to 6; `screenshot_7` or more is refused |
| Order | `n` runs 1, 2, 3 without a gap |
| Description | empty after trimming |
| File | `public/changelog/<id>-<n>.webp` is missing, is not a WebP image, or is over 1 MB |
| Folder | holds a file no valid entry declares |

## `ChangelogScreenshot`

| Field | Type | From |
| --- | --- | --- |
| `position` | int | `n` |
| `description` | string | the value, an English translation key |
| `file` | string | `<id>-<n>.webp` |
| `width`, `height` | int | `getimagesize()` |

`url()` returns `asset("changelog/{file}")`.

## In the API

`attributes.screenshots` on a `changelog_entry`, in position order:

```json
[
  {
    "position": 1,
    "url": "https://app.example/changelog/01M8A1B2C3D4E5F6G7H8J9K0MN-1.webp",
    "description": "An entry of What's new with two screenshots under its description",
    "width": 1600,
    "height": 1000
  }
]
```

`description` is translated into the reader's locale, as `title` is. An entry without screenshots returns `[]`.
