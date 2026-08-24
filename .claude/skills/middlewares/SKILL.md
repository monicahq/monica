---
name: middlewares
description: Conventions for defining middlewares. Use when the user wants to create or modify middlewares, including route groups, middleware, and naming conventions.
---

# Middlewares

- A middleware is for something that applies to a whole route group. The application has three,
  aliased in `bootstrap/app.php`: `set.locale` on every group, `active` on the signed in groups, and
  `administrator` on the admin group, which refuses with a 404 anybody who does not run the instance.
- You MUST register a new middleware in `bootstrap/app.php`, alias it in dotted lower case
  (`set.locale`), and apply it to a group in the routes file rather than to single routes.
- You MUST NOT resolve a model in a middleware. Route parameters are resolved by implicit route
  model binding, and the controller then refuses what the user cannot reach:

```
public function show(Request $request, Vault $vault, Contact $contact): View
{
    abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
    ...
}
```

- The model itself refuses another account first: eleven models override `getRouteKeyName()` to
  return `uuid`, and six override `resolveRouteBinding()` to scope the lookup to
  `auth()->user()?->account_id`. The controller then refuses the wrong vault, and the Action checks
  the account again in its `validate()`. An account carries no role, so nothing else is a permission
  check inside it. The one capacity is the administrator, which is instance wide and belongs to the
  `administrator` middleware, never to a controller or an Action.
- You MUST type-hint the closure in a docblock: `@param Closure(Request): (Response) $next`.
