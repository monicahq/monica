# Research: Skip the list of vaults

## R1. Where the value is stored

- **Decision**: a `shows_vault_list` boolean on `users`, default `true`.
- **Rationale**: every value on the Preferences screen (`locale`, `time_format`, `uses_readable_addresses`) is a column of `users`, and the code beside the change wins over the spec's first wording.
- **Rejected**: a column on `accounts` (no preference lives there); a Pennant flag (a flag decides when a capability is ready, never a preference).

## R2. How the switch saves

- **Decision**: its own route `PUT settings/account/preferences/vault-list`, its own controller and action `UpdateVaultListVisibility`, as the time zone does (`TimezoneController`, `UpdateTimezone`).
- **Rationale**: `UpdatePreferences` rebuilds the ordering keys of the whole account and may resend every record to search. A switch saving at once must not pay for that.
- **Rejected**: adding the field to `UpdatePreferences` through `_carried-values`.

## R3. Where the redirect happens

- **Decision**: in `VaultController::index`, before the view model is built. When the preference is off and the account has a default vault, reflash the session and redirect to `vaults.show`.
- **Rationale**: every way to the list (`HomeController` after sign in, `x-product-link`, the settings and support sidebars, the redirects after creating or deleting a vault) arrives at this one action. One check covers all of them.
- **Reflash**: the redirect after a vault is created or deleted carries a flash message. Without `reflash()`, the extra hop would consume it.
- **Rejected**: changing each link (seven call sites, and the next link would forget it); a middleware (one route only).

## R4. How the deletion is refused

- **Decision**: `VaultPolicy::delete(User $user, Vault $vault)`, denied when `! $user->shows_vault_list && $vault->is_default`. `DestroyVault` asks `$this->user->authorize('delete', $this->vault)` after its account check. `VaultSettingsViewModel::deletionRefusal()` asks `inspect()`.
- **Rationale**: the rule is stated twice, once where the screen disables the control and once where the action refuses. `.claude/rules/policies.md` makes that a policy.
- **Wording**: `You cannot delete your default vault while the list of vaults is turned off. Turn the list back on in Preferences first.`
- **Surfaces**: the web controller catches `AuthorizationException` and goes `back()->withErrors()`. The API controller throws `ValidationException::withMessages(['vault' => ...])` (422), as `AccountExportController` does.

## R5. The disabled control

- **Decision**: in `app/vaults/settings.blade.php`, when `deletionRefusal()` is not null, render `x-button.danger` with `aria-disabled="true"` and `aria-describedby="vault-delete-refusal"`, and no modal. The refusal paragraph links to `settings.preferences.index`.
- **Rationale**: the same shape as the export refusal on the same screen and the delete refusal in the life event type form.

## R6. The API and the MCP server

- **Decision**: `PUT api/v1/me/vault-list` taking `shows_vault_list`, answered with `UserResource`, which gains the attribute. No MCP tool.
- **Rationale**: the four `me/*` endpoints that set a preference (`me/timezone`, `me/contact-list-view`, `me/welcome`, `me/full-access-offer`) have no MCP tool, and no MCP tool deletes a vault. The definition of done asks for a tool per endpoint; the code beside the change has none, and that wins. Flagged for the reviewer.

## R7. The log

- **Decision**: a new `UserActionEnum::VaultListVisibilityUpdate`, labelled `Changed whether the list of vaults is shown`, as `UserTimezoneUpdate` is its own case.

## R8. Help and documentation

- A section "Vaults" on the Preferences screen with one row and an `x-help` pointing at `account.preferences#the-list-of-vaults`.
- `docs/portal/en/10-your-account-and-security/15-preferences.md` gains the section; `docs/portal/en/6-vaults/4-delete-a-vault.md` gains one paragraph linking to it with `@doc`. The API reference gains the endpoint and the refusal on `DELETE vaults/{vault}`.
