---
name: pennant-development
description: "Use when working with Laravel Pennant the official Laravel feature flag package. Trigger whenever the query mentions Pennant by name or involves feature flags or feature toggles in a Laravel project. Tasks include defining feature flags checking whether features are active creating class based features in `app/Features` using Blade `@feature` directives scoping flags to users or teams building custom Pennant storage drivers protecting routes with feature flags testing feature flags with Pest or PHPUnit and implementing A B testing or gradual rollouts with feature flags. Do not trigger for generic Laravel configuration authorization policies authentication or non Pennant feature management systems."
license: MIT
metadata:
  author: laravel
---

# Pennant Features

## Documentation

Use `search-docs` for detailed Pennant patterns and documentation.

## Basic Usage

### Defining Features

<!-- Defining Features -->
```php
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
```

### Checking Features

<!-- Checking Features -->
```php
if (Feature::active('new-dashboard')) {
    // Feature is active
}

// With scope
if (Feature::for($user)->active('new-dashboard')) {
    // Feature is active for this user
}
```

### Blade Directive

<!-- Blade Directive -->
```blade
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature
```

### Activating / Deactivating

<!-- Activating Features -->
```php
Feature::activate('new-dashboard');
Feature::for($user)->activate('new-dashboard');
```

## Verification

1. Check feature flag is defined
2. Test with different scopes/users

## Common Pitfalls

- Forgetting to scope features for specific users/entities
- Not following existing naming conventions
