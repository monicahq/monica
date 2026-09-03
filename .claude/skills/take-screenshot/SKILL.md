---
name: take-screenshot
description: Use this skill whenever the task requires taking, inspecting, or validating screenshots of a website or web application. Screenshots must be taken with Iris.
---

# Screenshot skill instructions

Use [Iris](https://github.com/brijr/iris) for all website screenshots and visual
UI verification.

Iris is the camera. Do not introduce Playwright, Puppeteer, Selenium, or custom
browser automation solely to take screenshots.

## Taking screenshots

For a standard desktop screenshot:

```bash
iris http://localhost:3000 --scale 1 -o /tmp/screenshot.png
```

For a full-page screenshot:

```bash
iris --full http://localhost:3000 --scale 1 -o /tmp/screenshot.png
```

For a specific element:

```bash
iris http://localhost:3000 \
  --selector '#target' \
  --padding 24 \
  --scale 1 \
  -o /tmp/screenshot.png
```

For mobile:

```bash
iris --size iphone http://localhost:3000 --scale 1 -o /tmp/screenshot.png
```

For dark mode:

```bash
iris --dark http://localhost:3000 --scale 1 -o /tmp/screenshot.png
```

If the page depends on asynchronously rendered content, wait for a meaningful
element instead of adding arbitrary delays:

```bash
iris http://localhost:3000 \
  --wait-for '[data-page-ready]' \
  --scale 1 \
  -o /tmp/screenshot.png
```

Use `--wait` only when waiting for a selector is not sufficient.

## Workflow

When visual validation is required:

1. Make sure the application is running.
2. Take the smallest screenshot that adequately validates the work.
3. Prefer an element screenshot when checking a specific component.
4. Prefer a normal viewport screenshot when checking page composition.
5. Use `--full` only when the entire page matters.
6. Open and inspect the resulting image.
7. Compare what is visible against the requested design or expected behavior.
8. If something is wrong, make the necessary changes and capture a new
   screenshot.
9. Continue until the screenshot confirms the requested result.

Do not claim that a visual change works without inspecting the screenshot.

## Screenshot sizes

Use these Iris presets when appropriate:

* `desktop` for the standard desktop view.
* `iphone` for the mobile phone view.
* `ipad` for the tablet view.

Use explicit dimensions when the task requires a particular viewport:

```bash
iris --size 1280x720 http://localhost:3000 --scale 1 -o /tmp/screenshot.png
```

Default to `--scale 1` for agent verification because it keeps screenshots
smaller while preserving enough detail for UI review. Use a higher scale only
when additional pixel density is useful.

## Output

Store temporary screenshots outside the repository unless the user explicitly
asks for them to be committed or saved:

```text
/tmp/screenshot.png
/tmp/screenshot-mobile.png
/tmp/screenshot-dark.png
```

Use descriptive names when taking multiple screenshots.

Do not add generated screenshots to Git or store them anywhere in the
repository.

Screenshots taken for a pull request stay in `/tmp` and are uploaded directly
to the pull request with GitHub CLI, as the
[pull requests skill](../pull-requests/SKILL.md) describes.

## Useful Iris options

```text
--full             Capture the full page
--selector <CSS>   Capture the first matching element
--padding <PX>     Add padding around an element capture
--size <SIZE>      desktop, iphone, ipad, or WxH
--dark             Emulate prefers-color-scheme: dark
--wait-for <CSS>   Wait until an element exists
--wait <MS>        Additional settle time
--scale <N>        Device scale factor
--timeout <SECS>   Capture timeout
--json             Return structured capture information
-o, --out <PATH>   Output path
```

When debugging a failed capture, use `--json`:

```bash
iris http://localhost:3000 \
  --scale 1 \
  --json \
  -o /tmp/screenshot.png
```

Inspect the reported status rather than assuming the image was successfully
created.

## Rules

* Always use Iris for screenshots.
* Do not use browser automation just to capture an image.
* Do not use screenshots as a substitute for automated functional tests.
* Do use screenshots to verify layout, spacing, typography, responsive
  behavior, visual regressions, and the final appearance of UI changes.
* Prefer targeted screenshots over unnecessarily large full-page captures.
* Always inspect the resulting image before drawing conclusions from it.
