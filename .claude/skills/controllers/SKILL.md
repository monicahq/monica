---
name: controllers
description: Conventions for writing thin HTTP controllers that validate input and delegate to Actions. Use when creating or editing controllers in app/Http/Controllers (web, API, or marketing).
---

# Controllers

## Folder convention

- Controllers MUST be split into logical categories: `App` for the logged in app, `Api` for the JSON API.
- Within these folders, controllers MUST be grouped by major domains of the application, like `Account`, `Operate`, `Grow`, and so on.

## Rules

- You MUST read the other controllers in the project for reference, and follow the same structure and conventions.
- Controllers MUST be as thin as possible, and the business logic MUST live in actions.
- Controllers MUST be testable.

## Things to do

- You MUST only use these methods: `index`, `new`, `create`, `show`, `edit`, `update`, `destroy`
- You MUST use as fewer parameters as possible:
  - For `index`, use `$request` only.
  - For `new` and `create`, use `$request` only.
  - For `show`, `edit`, `update`, and `destroy`, use `$request` and request route attributes set in the middlewares to keep, if possible, only the `$request` parameter.
- You MUST not add private methods to a controller, even if code is repeated multiple times.
- You MUST not put domain logic in the controller - use Action when you need to execute a domain logic.
- You MUST use Form Requests for create and update operations, or whenever validation or authorization is non-trivial. You SHOULD prefer inline validation for simple endpoints.
- You MUST use `$request->attributes->get('vault')` to get values from middlewares
- You MUST use `$request->user()` — never `Auth::user()`
- You SHOULD read route parameters with `$request->route()->parameter('gender')`
- You MUST validate the request data, and you MUST NOT sanitize it. In the validation, you MUST NOT check if an object exists by checking if the id exists in the database - this is done in Actions. You MUST make sure the validation rules match the fields of the model, as defined in the migration (ie length of a given string).
- You MUST instantiate the Action and call `->execute()`, passing `user: $request->user()` and the validated data with named arguments.
- You MUST prepare the data for the view in a ViewModel, and pass it to the view.
- You MUST type-hint the return of every method (`View`, `RedirectResponse`, `JsonResponse`, `AnonymousResourceCollection`, `Response`).
- You MUST use `return redirect()->route('home.index')` instead of `return to_route('home.index')`.

### If it's a web controller

- You MUST return views, not JSON.
- You MUST always pass validated data to the action.
- You MUST NOT compact data to a view. Instead, you MUST pass an array with keys that represent what the data is, like `['journals' => $journals]` instead of `compact('journals')`.
- You MUST wrap scoped `findOrFail()` lookups in a try/catch on `ModelNotFoundException` and `abort(404)`.
- You MUST redirect with `to_route(...)->with('status', __('Changes saved'))` after a mutation.

### If it's an API controller

- You MUST return an API Resource (e.g. `GenderResource`), never raw arrays/JSON.
- You MUST set the status code explicitly: `200` for show/update, `201` for create via `->response()->setStatusCode(...)`, and `response()->noContent(204)` for destroy.
- For `index`, you MUST return `Resource::collection($paginated)` and paginate, clamping `per_page` to `config('app.maximum_items_per_page')`.
- You MUST let scoped `findOrFail()` throw, with no try/catch (the framework returns 404).
