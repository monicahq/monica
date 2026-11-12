# Web contract: a possession's tags

All three are behind the signed in `web` group, like `possessions.featured.update`. `{vault}` and
`{possession}` bind through `ResolvesModelInVault`, `{possessionTag}` through `ResolvesModelInAccount`.
The controller refuses a possession of another vault with `abort_unless(..., 404)`.

| Method | URL | Name | Input | Answer |
| --- | --- | --- | --- | --- |
| PUT | `vaults/{vault}/possessions/{possession}/tags/{possessionTag}` | `possessions.tags.update` | none | `back()` with status "Added “:name”." |
| DELETE | `vaults/{vault}/possessions/{possession}/tags/{possessionTag}` | `possessions.tags.destroy` | none | `back()` with status "Removed “:name” from this possession." |
| POST | `vaults/{vault}/possessions/{possession}/tags` | `possessions.tags.create` | `name`: required, string, max 50 | `back()` with status "Created “:name” and added it." |

Refusals:

- A tag or possession of another account, or a tag deleted meanwhile: 404.
- `name` empty or too long: validation error on `name`, shown in the panel.
- `name` matching a tag of the account ignoring case: `CreatePossessionTag` refuses it; the
  controller does not look it up instead, since the panel never offers creation for an existing name.

Alpine Ajax targets: `possession-tag-chips`, `possession-tag-options`, `possession-tag-count` (R4).
