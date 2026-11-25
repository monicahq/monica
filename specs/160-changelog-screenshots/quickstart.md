# Quickstart: Screenshots in What's new

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/ChangelogEntriesTest.php tests/Unit/ViewModels/ChangelogViewModelTest.php tests/Feature/Controllers/Api/ChangelogEntryControllerTest.php tests/Feature/Views/Components/ScreenshotGalleryTest.php
php artisan monica:localize --check
composer test:parallel
```

`it_finds_no_problem_in_the_shipped_entries` proves the real `changelog/` and `public/changelog/` agree.

## By hand

1. Capture two parts of a screen with Iris as the changelog skill says, convert them without resizing: `magick shot.png -quality 82 public/changelog/<id>-1.webp`.
2. Add `screenshot_1:` and `screenshot_2:` to the entry, run `php artisan monica:localize`.
3. `bun run build`, open What's new signed in, then signed out with What's new public.

| Check | Expected |
| --- | --- |
| Entry with one screenshot | preview at its natural size and proportions, "View full screen" in its corner, caption under it |
| Entry with two | grid of previews keeping their proportions, caption under each |
| Tab to a preview, Enter | viewer opens, focus on Close, "1 of 2" shown |
| Right arrow twice | second, then first again |
| Escape | viewer closes, focus back on the preview |
| Click beside the image / on it | closes / stays open |
| VoiceOver on a preview | "View full screen, <description>", heard once |
| Reduced motion on | no fade |
| Phone width | no horizontal scroll; viewer uses the whole screen |
| Delete one image, run the test above | it names the entry and the missing file |
| `GET /api/v1/changelog-entries` | `screenshots` listed with url and description |
