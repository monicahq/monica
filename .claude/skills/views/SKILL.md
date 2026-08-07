---
name: views
description: Conventions for defining views. Use when the user wants to create or modify views, including Blade templates and view composers.
---

# Views

## General

- View names MUST be kebab-case, and views MUST be stored in the `resources/views` folder.
- Views MUST be grouped by major domains of the application, like `account`, `operate`, `grow`, and they MUST match the controller folder structure.
- View partials MUST be prefixed with an underscore, and MUST be stored in the same folder as the view that uses them.
- You MUST NOT put domain logic in a view.
- You MUST NOT compute data in a view. You MUST compute data in a viewmodel, that is passed to the controller, and pass it to the view.
- You SHOULD create and maintain PHPDoc blocks at the top of every view file.
- You MUST create and maintain PHPDoc blocks for components.
- When passing variables to a partial using `@include`, you MUST explicitly pass all required variables. Relying on the parent scope is not enough, even though Blade makes those variables reachable, because a partial that names what it needs can be read on its own.

```blade
{{-- Good: the partial says what it needs. --}}
@include('app.settings.account.security._change-password', ['viewModel' => $viewModel])

{{-- Bad: it happens to work, and nothing says why. --}}
@include('app.settings.account.security._change-password')
```

  A partial that needs no variable of its own, such as one built only out of copy and routes, is included without a second argument.

- You MUST make sure each view is responsive.

## Example
```
<?php
/**
 * @var \App\Models\User $user
 * @var \Illuminate\Support\Collection<int, \App\Models\Post> $posts
 */
?>
```

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

- You MUST write a component class for every component, in a subfolder when needed (for instance, all the buttons are in `resources/views/components`).
- As soon as you notice that a piece of view is repeated in multiple places, you MUST create a component for it.
