---
name: controllers
description: Conventions for writing thin HTTP controllers that validate input and delegate to Actions. Use when creating or editing controllers in app/Http/Controllers (web, API, or marketing).
---

# Controllers

## Folder convention

- Controllers MUST be split into logical categories: `App` for the logged in app, `Api` for the JSON API.
- Within these folders, controllers MUST be grouped by major domains of the application, like `Vaults`, `Settings`, `Marketplace`, `Admin`, and so on.

## Rules

- You MUST read the other controllers in the project for reference, and follow the same structure and conventions.
- Controllers MUST be as thin as possible, and the business logic MUST live in actions.
- Controllers MUST be testable.

## Things to do

- You MUST only use these methods: `index`, `new`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- You MUST keep the signature to `$request` followed by the models the URL names, resolved by
  implicit route model binding, in the order the URL names them, then the optional plain parameters:
  `index(Request $request, Vault $vault, ?string $filter = null)`,
  `show(Request $request, Vault $vault, Contact $contact)`.
- You MUST not put domain logic in the controller - use Action when you need to execute a domain logic.
- You MUST NOT put domain logic in a private method of a controller. A private method that
  assembles a validation rule set, reads a value out of the request or builds a redirect is fine;
  anything that decides or writes something belongs to an Action.
- You MUST validate inline, with `$request->validate([...])` in the method. That is what nearly every
  controller does. A Form Request is for the case where the same rule set is shared by
  several endpoints or is long enough to bury the method; the application has two,
  `SaveFieldValuesRequest` and `SaveLifeEventRequest`. When the rules are long and used once, put them in a private
  `rules()` method of the controller, as `ContactShareLinkController` does.
- You MUST let implicit route model binding hand you the model, and you MUST then check it is
  reachable by the user: `abort_unless($contact->isReachableThrough($request->user(), $vault), 404)`.
  No middleware resolves models in this application, and `$request->attributes` is never used.
- You MUST use `$request->user()` — never `Auth::user()`
- You SHOULD take a plain route parameter as a typed argument (`?string $page = null`).
  `$request->route()->parameter('gender')` is the last resort.
- You MUST validate the request data, and you MUST NOT sanitize it. In the validation, you MUST NOT check if an object exists by checking if the id exists in the database - this is done in Actions. You MUST make sure the validation rules match the fields of the model, as defined in the migration (ie length of a given string).
- You MUST pass everything to the Action through its constructor, with named arguments, then call
  `->execute()` with none: `(new CreateContact(user: $request->user(), vault: $vault, firstName: $validated['first_name']))->execute()`.
- You MUST prepare the data for the view in a ViewModel, and pass it to the view.
- You MUST type-hint the return of every method (`View`, `RedirectResponse`, `JsonResponse`, `AnonymousResourceCollection`, `Response`).
- You MUST use `return redirect()->route('home.index')` instead of `return to_route('home.index')`.

### If it's a web controller

- You MUST return views, not JSON.
- You MUST always pass validated data to the action.
- You MUST NOT compact data to a view. Instead, you MUST pass an array with keys that represent what the data is, like `['journals' => $journals]` instead of `compact('journals')`.
- You MUST refuse anything the user cannot reach with `abort_unless(..., 404)`. A scoped
  `findOrFail()` is rare here; when you write one, wrap it in a try/catch on
  `ModelNotFoundException` and `abort(404)`.
- You MUST redirect with `redirect()->route(...)->with('status', __('Changes saved'))` after a
  mutation.
- A failed `$request->validate()` redirects back with the errors and the input on its own, and you
  write nothing. When an Action refuses the work instead, catch its exception and return
  `back()->withErrors(['field' => $e->getMessage()])`, adding `->withInput()` when the form has to be
  repopulated. Use
  `->with('error', __('...'))` for a message that belongs to no field.
- The view MUST then show `$errors` and repopulate every input with `old('field_name')`.


### If it's an API controller

- You MUST return an API Resource (e.g. `GenderResource`), never raw arrays/JSON.
- You MUST set the status code explicitly: `200` for show/update, `201` for create via `->response()->setStatusCode(...)`, and `response()->noContent(204)` for destroy.
- For `index`, you MUST return `Resource::collection($paginated)` and paginate, clamping `per_page` to `config('monica.api_maximum_items_per_page')`.
- You MUST let scoped `findOrFail()` throw, with no try/catch (the framework returns 404).
