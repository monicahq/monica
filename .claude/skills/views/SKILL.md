---
name: views
description: Conventions for defining views. Use when the user wants to create or modify views, including Blade templates.
---

# Views

## General

- View names MUST be kebab-case, and views MUST be stored in the `resources/views` folder.
- Views MUST be grouped by major domains of the application (`admin`, `auth`, `marketplace`, `settings`, `vaults`), and they MUST match the controller folder structure.
- View partials MUST be prefixed with an underscore, and MUST be stored in the same folder as the view that uses them.
- You MUST NOT put domain logic in a view.
- You MUST NOT compute data in a view. The controller builds a view model and hands it to the view,
  and the view only reads it.
- A `@php` block is for presentation and nothing else: naming a set of classes, aliasing a view model
  call into a local variable, building a route, mapping a state to a tone and a label. It MUST NOT
  query the database, call an Action, or decide anything the product cares about. 30 views and 23
  components hold one, and every one of them turns a value that was already computed into what the
  screen shows. The same rule applies inside a component.
- Every view and component opens with the `{{-- --}}` line and `@var` block described in
  [code-style.md](../../rules/code-style.md).
- When passing variables to a partial using `@include`, you MUST explicitly pass all required variables. Relying on the parent scope is not enough, even though Blade makes those variables reachable, because a partial that names what it needs can be read on its own.

```blade
{{-- Good: the partial says what it needs. --}}
@include('app.settings.account.security._change-password', ['viewModel' => $viewModel])

{{-- Bad: it happens to work, and nothing says why. --}}
@include('app.settings.account.security._change-password')
```

  A partial that needs no variable of its own, such as one built only out of copy and routes, is included without a second argument.

- You MUST make sure each view is responsive.


## Accessibility

- You MUST make sure that your views are accessible following the best practices.
- You MUST use semantic HTML elements when possible.
- You MUST NOT use a clickable <div> or <span>. Use a <button> for actions and an <a> for navigation.
- Placeholder text MUST NOT be used as the only label for a form control.
- Icon-only buttons and links MUST have an explicit aria-label.
- Decorative icons and images MUST be hidden from assistive technologies using aria-hidden="true" or an empty alt="".
- Meaningful images MUST have concise, descriptive alt text.
- You MUST NOT add ARIA attributes when native HTML already provides the correct semantics.
- When ARIA is necessary, all referenced IDs, such as aria-labelledby, aria-describedby, and aria-controls, MUST point to existing unique elements.

## CSS

- Instead of using margin and padding between elements, you MUST use `space-x` or `space-y` in div to create consistent spacing between child elements.

## Blade components

- Components are anonymous. You MUST write the blade file in `resources/views/components`, declare
  what it takes with `@props([...])`, and document it in a `{{-- --}}` block at the top with one
  `@var` per prop. A component class belongs in `app/View/Components`
  only for a layout (`Shell`, `AccountLayout`, `AdminLayout`, `GuestLayout`, `MarketplaceLayout`).
- Every template, a component included, MUST open with a one line `{{-- --}}` comment saying what the
  view is, before the `@var` block. The rule and its examples are in
  [code-style.md](../../rules/code-style.md).
- Extract a component when the same markup stands in three files, or in two when it carries an
  accessibility contract. The rule is in [components.md](../../rules/components.md).
