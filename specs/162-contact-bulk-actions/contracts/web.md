# Contract: web routes

Declared next to `contacts.create` in `routes/web.php`, before `contacts.show`, whose `{contact}`
segment would otherwise take `deletion`.

| Method and path | Name | Controller | Answers |
| --- | --- | --- | --- |
| `GET vaults/{vault}/contacts/deletion?contacts[]=…` | `contacts.deletion.show` | `ContactDeletionController@show` | The confirmation dialog, as an Alpine Ajax fragment |
| `DELETE vaults/{vault}/contacts` | `contacts.deletion.destroy` | `ContactDeletionController@destroy` | Back to the list, `status` naming how many were deleted and kept |
| `PUT vaults/{vault}/contacts/categories` | `contacts.categories.update` | `ContactCategoryController@update` | Back to the list, flashing `contactCategoryChange` for the Undo notice |

The body of `PUT` is `contacts[i][contact]` and `contacts[i][categories][]`, the shape of the API, so
the Undo form posts the previous categories to the same route.

## Hooks the view tests may assert

- `x-data="contactSelection("` on the list, `role="group"` with its `aria-label`.
- One checkbox per row named after the person, the heading checkbox with `aria-checked="mixed"`.
- The menu through `x-actions-menu`, the confirmation through `x-modal.warning`.
