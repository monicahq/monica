# Blade and view best practices

## Use `$attributes->merge()` in component templates

Use the component attribute bag so callers can add attributes. `merge()` combines default attributes with caller-provided values. Class values receive special merging behavior.

```blade
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

## Use `@pushOnce` for per-component scripts

If a component renders repeatedly, `@push` adds its script on every render. Use a consistently named `@pushOnce` block to add that content once per rendered response.

## Prefer components for explicit interfaces

Use a Blade component when a reusable interface benefits from explicit props, an attribute bag, or slots. An include still suits a small partial that intentionally uses the current view data. Pass an explicit data array when implicit variable sharing would hide the partial's dependencies.

## Share compatible view data with a view composer

Use a view composer to centralize the data that one or more named Blade views need every time Laravel renders them. Keep the composer compatible with every view it targets, and avoid broad wildcards when views require different data shapes. A view composer runs when Laravel renders the matching view. It does not supply data to JSON, streamed, or other non-view responses.

## Return Blade fragments for partial rendering

A route can return either a full view or a named fragment for clients such as htmx or Turbo.

```php
return view('dashboard', compact('users'))
    ->fragmentIf($request->hasHeader('HX-Request'), 'user-list');
```

## Share parent component props with `@aware`

Use `@aware` when a nested component needs a prop explicitly passed to an ancestor component. `@aware` does not expose an ancestor's default prop value unless that value was passed through the attribute bag.
