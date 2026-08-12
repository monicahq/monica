## General

- Do not tell me I am right all the time. Be critical. We're equals. Try to be neutral and objective.
- Do not excessively use emojis.

## Coding instructions

- Write code as simply as possible - do not over-engineer so anyone can understand it.
- Do not extract a private method just because a few lines are repeated twice. If the repeated code is short and not business critical, repeat it inline rather than naming and hiding it behind a helper. Extract only when the logic is non-trivial, reused in several places, or its own concept worth naming.
- Always follow the Laravel best practices and how we structure our codebase.
- If you are unsure about a specific implementation, ask for clarification before proceeding.
- When you write tests, read the testing rules in `rules/testing.md` and follow them.
- When writing code, follow the coding standards in `rules/code-style.md`.
- ALWAYS warn users before making a destructive action in the UI.

## Tech Stack & Architecture

- Backend: PHP 8.4+ / latest version of Laravel
- Frontend: Blade / Tailwind CSS / Alpine Ajax / Alpine.js

## Application structure

- `app/Actions`: one class per user action, holding the business logic. Controllers stay thin and delegate here. Most of the app lives in this folder.
- `app/Models`: Eloquent models.
- `app/Http/Controllers`: split into `App` (the logged in app), `Api` (the JSON API) and `Marketing` (the public site and docs).
- `app/Http/Middleware`: route middleware, including the role gates. `app/Http/Resources`: API transformers.
- `app/Jobs`: queued jobs. `app/Mail`: mailables. `app/Enums`: enums. `app/Helpers`: helpers.
- `app/ViewModels`: assemble what one screen shows, out of copy, configuration and URLs. They ask the database nothing, read no file, and end up in Blade and nowhere else. `MarketingFeatures` (the mega menu and the features hub), `MarketingFaq`, `MarketingLanguages` (the footer picker) and `MarketingSeo` (the tags in the head). A web controller may build one and hand it to its view; what decides is where it ends up, not who constructs it.
- `app/View/Components`: the layout components (app, guest).
- `resources/views`: `app` for the logged in screens, `components` for shared UI, `layouts` and `partials` for the shell, `mail` for emails.
- `resources/css` and `resources/js`: the Tailwind theme and the Alpine setup.
- `routes`: `web.php` (logged in), `auth.php`, `api.php`, `marketing.php`, `console.php`.
- `database`: `migrations`, `factories`, `seeders`, plus `data` for seed files such as countries.
- `lang`: one JSON file per locale.
- `tests`: `Unit` (models, actions, jobs), `Feature` (controllers), `Browser` (Pest browser tests).

## Guidelines for git and Github

- You MUST create a new branch when doing a new task, unless stated otherwise, based off of main branch. Make sure main is always up-to-date.
- Branche names MUST be of the format YYYY-MM-DD-{name}.
- You MUST follow conventional commits for commit messages.
- NEVER mention Claude Code in PR descriptions, PR comments, or issue comments.

## Guidelines for writing a commit message

- You MUST provide a simple sentence when submitting a PR. Do NOT write `feat: allow deleting the last vault of an account` - instead, write `feat: delete last vault in account`.

DO NOT
```
feat: draw the top bar controls so they read against the bar
```

DO
```
feat: better header buttons
```

## Writing something

Never use dashes (— or -) as punctuation in documentation or README files. Rephrase sentences using periods, commas, or parentheses instead.


## Checklist for each feature or change

- You MUST run `php artisan monica:localize` and translate what it reports as missing, in every configured locale. Never edit `lang/*.json` keys by hand.
- You MUST make sure what you wrote is covered by a test
