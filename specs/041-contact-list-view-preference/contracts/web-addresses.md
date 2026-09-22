# Contract: the People list addresses

The signed in application, `routes/web.php`. Field names and the resolution rule are in
[data-model.md](../data-model.md).

## The route

```php
Route::get('vaults/{vault}/contacts/{filter?}', [ContactController::class, 'index'])
    ->whereIn('filter', ContactGroupingEnum::values())
    ->name('contacts.index');
```

One route, as today. The only change is that `whereIn` now takes all four values from the enum instead
of the two spelled out by hand, so `all` and `names-only` stop falling through to `contacts.show`.

It stays declared above `vaults/{vault}/contacts/{contact}/{page?}`, which is what makes the literal
segments win.

## What each address answers

| Address | Answer |
| --- | --- |
| `vaults/{vault}/contacts` | `302` to the address of the remembered view. Records nothing. |
| `vaults/{vault}/contacts/all` | Everybody. |
| `vaults/{vault}/contacts/favourites` | The favourites. |
| `vaults/{vault}/contacts/archived` | The archived. |
| `vaults/{vault}/contacts/names-only` | The people written down as a first name only. |
| anything else in that position | Falls through to `contacts.show`, and `404` when no contact answers to it. |

Each of the four accepts `sort`, `direction`, `query` and `page` in the query string. A choice the
address does not state is taken from the recorded view, then from the default.

## The redirect

`vaults/{vault}/contacts` resolves the recorded view and redirects to it, stating all three choices:

```
GET  /vaults/scranton/contacts
302  /vaults/scranton/contacts/favourites?sort=last-spoke&direction=desc
```

A user who has recorded nothing is sent to `/vaults/{vault}/contacts/all?sort=name&direction=asc`.

`query` and `page` are not carried into the redirect. A search is not part of the view, and a page
number means nothing until the list is known.

## Recording

`ContactController@index` records the choices the address states, through
`UpdateContactListView`, when all of the following hold.

- The address states a grouping, so the redirect branch never records.
- `$request->prefetch()` is false. A browser preloading a link on hover must not change what the reader
  sees next time.
- At least one of the three resolved values differs from what is recorded. Paging through a long list
  therefore writes once, not once per page.

## The address bar follows a chip

The chips and both forms are Alpine Ajax targets, which fetch the fragment and swap it in. Alpine Ajax
leaves the address bar alone unless the target names a history strategy, so each of them carries
`x-target.push` rather than `x-target`. Without it the view changes and the address does not, which
makes the address unshareable and the recorded view invisible.

`x-target.push` is what the reminders screen and the life event types rail already use for the same
job. The Load more link keeps a plain `x-target`: it appends to the view already open rather than
opening another one.

## Addresses the product builds

`ContactsViewModel` states the grouping in every address it builds, Everybody included. Its private
`segmentFor()`, which returned null for Everybody, is deleted.

`RelationshipsBlock` is not touched. It already builds
`route('contacts.index', ['vault' => ..., 'filter' => 'names-only'])`, and that address begins working.

Callers that mean "my People list", and keep the bare address on purpose: the vault sidebar, the
breadcrumb, and the redirect after a contact is deleted.
