# Research: Authorization Policies and Gates

## 1. Which capabilities are genuinely duplicated

The specification named four. A survey of the code found one of them already correct.

| Capability | Screen side | Action side | Duplicated |
| --- | --- | --- | --- |
| Vault ceiling | `VaultFormViewModel::ceilingMessage()` | `CreateVault::refuseAboveTheCeiling()` | Yes, the whole rule and a second message |
| Contact ceiling | `ContactFormViewModel::ceilingMessage()` | `CreateContact::refuseAboveTheCeiling()` | Yes, identically |
| Me contact delete | `ContactEditViewModel::mayBeDeleted()` | `DestroyContact::validate()` | Yes, one boolean each |
| Frozen template version | `TemplateBuilderViewModel::isEditable()` | controllers and actions | **No** |

**Decision**: scope is the first three. The frozen template version leaves the plan.

**Rationale**: the frozen version already has exactly one home, `VersionStatusEnum::isEditable()`, and
every caller delegates to it. The builder screen already honours it: `_inspector.blade.php` writes
`:disabled="! $viewModel->isEditable()"` and `builder.blade.php` line 86 already explains the frozen
state. Wrapping a working single source in a policy would add a layer and remove nothing, against
Principle II. User Story 2 of the specification asserts the screen "still drew the control", which the
code contradicts, so the specification is the side that is wrong. Keeping it for uniformity was
considered and rejected as abstraction with no defect behind it.

## 2. Mechanism: policy classes, discovered by convention

**Decision**: one policy class per model under `app/Policies`, no manual registration.

**Rationale**: Laravel 13.8 resolves `App\Models\Vault` to `App\Policies\VaultPolicy` by replacing
`\Models\` with `\Policies\` (`Gate::guessPolicyName`, framework line 737). The project has no
`AuthServiceProvider` and needs none. Three methods are required: `VaultPolicy::create(User $user)`,
`ContactPolicy::create(User $user)` and `ContactPolicy::delete(User $user, Contact $contact)`. The two
`create` methods take no model instance, which is the documented shape for a creation ability.

**Alternatives considered**: gates as closures in `AppServiceProvider`, rejected by the clarification
that chose policies and the constitution amendment that goes with it.

## 3. Keeping the explanation while moving the rule

**Decision**: the policy returns `Response::deny($message)`; the action authorizes and lets the
framework raise the refusal.

```php
$this->user->authorize('create', Vault::class);
```

**Rationale**: `Gate::authorize()` (framework line 390) is `inspect()->authorize()`, and
`Response::authorize()` (line 148) throws an `AuthorizationException` carrying the message the policy
wrote. The action therefore states neither the rule nor the reason, which is what FR-001 and FR-004 ask
for. `User::authorize()` wraps `Gate::forUser($this)` (line 872) so the ability is answered about the
action's own user rather than the session, which FR-006 requires for jobs, commands and CardDAV. The
three exceptions that only carried these refusals, `TooManyVaults`, `TooManyContacts` and
`CannotDeleteTheMeContact`, are deleted.

The six catch sites keep the shape of the refusal the product gives today: the web form still returns
`back()->withErrors()` with the input, and the API still returns 422 with the explanation on the field.
Only the type they catch changes.

**Alternatives considered**: keeping the three exceptions, rejected because the message they carry now
comes from the policy and the type adds nothing a `catch (AuthorizationException)` does not. Letting the
exception render as a 403 with no catch, rejected because it loses the form input and moves the API from
422 to 403, against SC-003 and SC-005.

**Known cost**: `catch (AuthorizationException)` is wider than `catch (TooManyVaults)`. If one of these
actions ever asks a second ability, its refusal lands on the field the catch names. The `try` blocks hold
a single action call and none of the three nests an action that asks a policy, so nothing is wrong today.

## 4. How the screen asks

**Decision**: the view model calls the same gate and returns `$response->message()`; no `@can` in Blade.

**Rationale**: every screen in this codebase reads from its view model, and the project has zero `@can`
directives. Asking the gate inside `ceilingMessage()` and `mayBeDeleted()` satisfies FR-002 while
leaving the Blade conventions alone. `@can` stays available for screens written later.

## 5. Refusal status for this starting set

**Decision**: all three refuse with their explanation. None uses `Response::denyAsNotFound()`.

**Rationale**: none of the three hides existence. An account knows its own vault count, and a user
knows the contact representing them exists. `denyAsNotFound()` (framework line 96) is confirmed present
and is what a later record scoped ability must use, which the plan records so it is not forgotten.

## 6. Cost of unifying the two ceiling messages

The screen and the action word the ceiling differently today: the action adds "Full access lifts the
ceiling." and the screen does not. One message means one wording, so copy changes and
`php artisan monica:localize` must run with the new strings translated in all 31 locales. The strings are
counted phrases and keep `trans_choice()` with `:count` in every segment.
