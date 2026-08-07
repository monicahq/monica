---
name: actions
description: Actions are what the user does within an application. Use when working with writing, editing or deleting an action.
---

# Actions

## Rules

- You MUST read the other actions in the project for reference, and follow the same structure and conventions.
- You MUST put all business logic in an action, and nowhere else.
- You MUST make every action fully testable.
- You MUST log what the user did whenever an action does something on behalf of a user.
- You MUST use Eloquent in an action, unless it is genuinely not possible.
- You MUST make as few DB queries as possible.
- You MUST wrap critical pieces of code in a transaction, and the action MUST throw an exception when it fails.

## Structure

- You MUST write one action per class, with a single public `execute()` method that returns the affected model.
- You MUST pass inputs through the constructor using promoted `readonly` properties (`User`, then the `Vault`/model, then the data).
- `execute()` MUST only orchestrate small private steps in order: `sanitize()`, `validate()`, the work (`create()`/`update()`/`destroy()`), then `log()`.
- You MUST sanitize strings with `TextSanitizer`.
- You MUST throw `ModelNotFoundException` from `validate()` when the user is not in the vault or lacks the role.
- You MUST dispatch `LogUserAction` on the `low` queue, along with any other loggers.

## Action naming conventions

- Action names MUST represent what a user wants to do, or what the system needs to do.
- The verb SHOULD follow the appropriate RESTful method name when possible, like `CreateXX`, `UpdateXX` or `DestroyXX`.

## Checklist

- You MUST sanitize the data first.
- You MUST validate the data: permissions, existence of related models, link to account, and so on.
- You MUST do what the action is supposed to do.
- You MUST add the case to `UserActionEnum` when it is a user action.
- You MUST log the action.
- You MUST write a test for the action, covering all edge cases.
