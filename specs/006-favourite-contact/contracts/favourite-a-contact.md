# Contract: Favourite a Contact

Two interfaces. The web route ships in this feature. The API route does not, and its shape is fixed
here so that the later work inherits a decision instead of retaking it. See research decision 1.

## Web route (ships in this feature)

```text
PUT vaults/{vault}/contacts/{contact}/favourite
```

| Property | Value |
|----------|-------|
| Route name | `contacts.favourite.update` |
| Controller | `App\Http\Controllers\App\Vaults\ContactFavouriteController@update` |
| Middleware | `web`, `auth`, `active`, `set.locale`, the group the other contact routes already sit in |
| Placement | `routes/web.php`, in the contact block, beside `contacts.avatar.update` |
| Binding | `{vault}` and `{contact}` by uuid, as everywhere else |

### Request

| Field | Type | Rules | Meaning |
|-------|------|-------|---------|
| `favourite` | boolean | `['required', 'boolean']` | The state the caller wants, not a toggle. `1` marks, `0` clears. |

Submitted by `<x-form method="put">`, so the browser sends `POST` with `_method=put` and the csrf
token. The form targets the star's own region with `x-target`, which is a refinement: without
JavaScript the same request is an ordinary post.

### Responses

| Case | Response |
|------|----------|
| Marked | `302` to `contacts.show` for that vault and contact |
| Cleared | `302` to `contacts.show` for that vault and contact |
| `favourite` missing or not a boolean | `422` |
| Contact in another account | `404` |
| Contact reached through the wrong vault | `404` |
| Not signed in | `302` to the sign in screen |

No flash status is set. The star itself is the feedback, and a toast for a mark the user can see
change under their finger is noise. This is a deliberate departure from `TemplateActivationController`,
which flashes a status because it redirects to a different screen where the change is otherwise
invisible.

### Side effects

- `contacts.is_favourite` set to the submitted value.
- One activity entry, `contact_favourited` or `contact_unfavourited`, carrying the contact's name,
  queued on `low`.
- Nothing else. No snapshot, no version, no notification, no cache invalidation.

### Accessible contract for the control

The button is part of the contract, because FR-004 through FR-006 are testable properties of the
rendered markup rather than of the route.

| Property | Value |
|----------|-------|
| Element | `<button type="submit">` inside the form. Never a link, and never a bare `<span>`. |
| Pressed state | `aria-pressed="true"` when the contact is a favourite, `"false"` when not |
| Accessible name | "Remove :name from favourites" when marked, "Add :name to favourites" when not. Both translated. |
| Glyph | `star-filled` when marked, `star` when not. Shape, not tint, carries the state. |
| Focus | The project's standard visible ring. The glyph itself stays `aria-hidden`, since the button is named. |
| Target size | At least 24 by 24 CSS pixels, per WCAG 2.2 AA 2.5.8. The existing chip is 32 by 32. |

## API route (does not ship in this feature)

Recorded so the contacts API inherits it.

```text
PUT /contacts/{contact}/favourite
```

| Property | Value |
|----------|-------|
| Authentication | Bearer token, as every other API route |
| Body | `{"favourite": true}` |
| `200` | The contact resource, carrying `is_favourite` |
| `404` | A contact in another account, indistinguishable from one that does not exist |
| `422` | `favourite` missing or not a boolean |

It calls the same two actions with no logic of its own, which is the condition the constitution
attaches to shipping a capability before its API exists. Blocked on the contacts resource being
defined, which is not this feature.
