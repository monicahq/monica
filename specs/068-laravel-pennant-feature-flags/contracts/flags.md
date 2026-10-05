# Contracts: Feature flags

`{flag}` is the stored name from `#[Name]`. A name no class declares answers 404 on every route and
fails the commands with exit code 1.

## Admin routes (`routes/administrator.php`, inside `administrator`)

| Method | URI | Name | Answers |
| --- | --- | --- | --- |
| GET | `admin/flags` | `admin.flags.index` | Every declared flag and whether it is on for every account |
| POST | `admin/flags/{flag}/activation` | `admin.flags.activation.create` | On for every account, back to the list |
| DELETE | `admin/flags/{flag}/activation` | `admin.flags.activation.destroy` | Off for every account, back to the list |
| POST | `admin/accounts/{account}/flags/{flag}/activation` | `admin.accounts.flags.activation.create` | On for that account, back to `admin.accounts.show` |
| DELETE | `admin/accounts/{account}/flags/{flag}/activation` | `admin.accounts.flags.activation.destroy` | Off for that account, back to `admin.accounts.show` |

- The two "every account" routes sit in the `password.confirm` group, beside the instance configuration.
- `{account}` takes `whereNumber`, like the routes beside it.
- `Flags` joins the admin menu between `Configuration` and `Administrators`.
- `admin.accounts.show` gains a panel listing each declared flag for that account.

## Commands

```text
monica:list-flags
monica:switch-flag {flag} {state : on or off} {--email= : One account} {--every-account}
```

- `monica:list-flags` prints one line per declared flag: its name and its state for every account.
  With nothing declared it says so and exits 0.
- `monica:switch-flag` needs exactly one of `--email` or `--every-account`. `--email` matches
  regardless of case, like `monica:make-administrator`.
- Success prints what changed and exits 0. A missing account, an undeclared flag, a state other
  than `on` or `off`, or neither or both options exit 1 with one line saying which.

## Middleware

`flag:{name}` (`EnsureFlagIsOn`): 404 when the flag is off for the signed in account, or for every account when nobody is signed in (`FlagState::isOn()`).

## Administrator log

| Case | Account | Description |
| --- | --- | --- |
| `FlagActivated` | the account | Activated the flag :flag for :name |
| `FlagDeactivated` | the account | Deactivated the flag :flag for :name |
| `FlagActivatedForEveryAccount` | null | Activated the flag :flag for every account |
| `FlagDeactivatedForEveryAccount` | null | Deactivated the flag :flag for every account |
