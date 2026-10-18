# Contract: the email layout

What a notification hands the layout, and where each part lands. Files are under
`resources/views/vendor/mail/html/` unless named otherwise.

## Regions

| `MailMessage` part | Region | Drawn by |
| --- | --- | --- |
| `greeting`, or the default heading | Heading | `vendor/notifications/email.blade.php` (Markdown `#`) |
| `introLines` | Sentences | same, Markdown paragraphs |
| `actionText`, `actionUrl`, `level` | Button | `button.blade.php`, `color` is `primary`, `success` or `error` |
| `panel` | Panel | `panel.blade.php` |
| `outroLines` | Closing sentences | same, Markdown paragraphs |
| `salutation`, or "Thanks," and the name | Sign off | same |
| `displayableActionUrl`, `actionUrl` | Fallback strip, inside the card | `subcopy.blade.php` |
| `config('app.name')` | Header bar with the mark | `header.blade.php` |
| `config('app.name')`, the year | Footer under the card | `footer.blade.php` |

`layout.blade.php` owns the page, the card, the order of the regions, the head declarations and the
dark stylesheet. `message.blade.php` keeps passing header, subcopy and footer into it; the layout
puts the header and strip inside the card and the footer outside it.

Nothing on `MailMessage` changes. A notification that worked before renders in the new layout as is.

## Head

- `<html lang>` from the current locale (already the framework's behaviour).
- `color-scheme` and `supported-color-schemes` metas set to `light dark`.
- One `<style>` block: the narrow screen rules and the dark rules, `!important`, under
  `@media (prefers-color-scheme: dark)`, repeated under `[data-ogsc]` / `[data-ogsb]` for Outlook.

## Class hooks the dark stylesheet aims at

| Class | Element |
| --- | --- |
| `email-page` | the outer wrapper |
| `email-card` | the card |
| `email-header` | the header bar |
| `email-content` | the content cell; its `h1`, `p`, `strong`, `a` are reached through it |
| `email-button`, `email-button-success`, `email-button-error` | the button cell and link |
| `email-panel` | the panel |
| `email-strip` | the fallback strip |
| `email-footer` | the footer |
| `email-mark-light`, `email-mark-dark` | the two mark images, one hidden per mode |

These are hooks, not assertions: tests do not name them (`.claude/rules/testing.md`).

## Images

`public/images/email/mark-light.png` and `mark-dark.png`, linked with `asset()`. Header: 28 by 25,
`alt` the product name. Footer: 16 by 15, `alt=""`.
