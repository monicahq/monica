# Policies

One class per model under `app/Policies`, discovered by Laravel without registration. A policy answers
whether somebody may do one thing, and carries the reason when the answer is no.

## A policy exists to stop a rule being written twice

Do not add a policy because a rule feels like authorization. Add one when a rule is stated in two
places: once where a screen decides whether to offer a control, once where the action refuses. That
duplication is the defect; the policy is the fix. A rule written once, in the action, stays in the
action.

The screen asks the ability. The action asks the ability. Controllers do not: they keep the shape of
the refusal and nothing else.

## How to ask

```php
$user->authorize('delete', $record);   // refuses, carrying the policy's wording
$user->inspect('delete', $record);     // the answer and its reason, for a screen
$user->can('delete', $record);         // a bare boolean, for a screen that needs no reason
```

Never `Gate::authorize()` on its own. It asks about the signed in session, and an action runs for its
own `$this->user`: a job, a command and a device synchronising over CardDAV all have to get the same
answer. `User::authorize()` and `User::inspect()` wrap `Gate::forUser($this)` for exactly that reason.

A policy method typed `User $user` refuses when nobody is signed in, which is what we want everywhere.
Do not type it `?User`.

## The wording belongs to the reader, not to the old exception

When you move a rule into a policy, take the wording **the reader already sees**, which is usually the
controller's flash copy, not the message the action's exception carried. In `specs/033-more-authorization-policies` the two differed on all
five capabilities: the controllers wrote their own translated copy and never used the exception's
message at all. Copying the exception's wording would have silently changed five shipped strings.

Where a controller distinguished two refusals by wording, the policy keeps that distinction and the
controller collapses to one `catch` reading `$e->getMessage()`.

## No bespoke refusal type

A capability moving into a policy loses its exception class. `Response::deny($message)` and
`AuthorizationException` carry the wording, so a dedicated type adds nothing. Delete it and every
`use` of it.

The controller keeps answering as it did: `back()->withErrors()` on a form, 422 on the API,
`Forbidden` over CardDAV. Only the type it catches changes. A refusal that hides something answers as
not found, with `Response::denyAsNotFound()`.

## Every ability is proven three ways

Permitted, refused, and refused with nobody signed in.
`tests/Unit/Policies/PolicyCoverageTest.php` globs `app/Policies/*.php`, reflects the public methods,
and fails the suite when the matching `tests/Unit/Policies/<Name>Test.php` does not mention each
ability by name and does not hold a test whose name contains `nobody_is_signed_in`. A policy shipped
without tests cannot pass unnoticed.

## A refused control stays on the screen

It is rendered, marked `aria-disabled="true"`, not activable, and `aria-describedby` points at its
reason. Hiding it tells nobody anything.

Where the row already prints the reason, point at that text rather than writing a second sentence.
Fifty rows repeating a near identical sentence is worse for a screen reader than for anybody else.
Where it prints none, write one.

## What is not an ability

- **Validation.** What somebody typed. No screen can ask it in advance.
- **A third party refusing.** Stripe declining is not authorization.
- **A state found while the work runs.** It is not a standing property of the record.
- **Scoping.** Reaching a record is settled by route model binding and by the `account_id` check at the
  top of the action, before anything runs. A policy can only refuse after the record is loaded, so it
  adds to that and never replaces it. Leave those checks exactly where they are.
- **A control that is substituted, not withheld.** The value list screen swaps Delete for Stop
  offering. That is the right affordance, not a hidden control, and it stays.

## Never grant everything to administrators

Laravel documents `Gate::before` and a policy `before()` as the way to let an administrator do
anything. That is precisely wrong here. The administrator capacity governs the instance, meaning
moderation, accounts and storage, and never the contents of somebody else's vaults. It stays a
middleware over its route group.

## Naming

Methods use camelCase and the default CRUD words, with `view` in place of `show`. Beyond those, name
the ability after the thing somebody does: `share`, `absorb`, `addItem`, `removePassword`.
