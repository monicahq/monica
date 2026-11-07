# Configuration best practices

## Read environment variables in configuration files

Call `env()` only from configuration files. After you cache the configuration, Laravel does not load the application's `.env` file, so read configuration values in application code through `config()`.

Incorrect:

```php
$key = env('API_KEY');
```

Correct:

```php
// config/services.php
return [
    'key' => env('API_KEY'),
];

// Application code
$key = config('services.key');
```

## Protect production secrets

Do not commit plaintext production secrets. Laravel can encrypt an environment file so you can store the encrypted file safely. Deployment platforms can also supply secrets through their native secret stores.

Incorrect:

```bash
# A plaintext .env file committed to the repository
STRIPE_SECRET=<your-stripe-secret>
AWS_SECRET_ACCESS_KEY=<your-aws-secret>
```

Encrypted environment file:

```bash
php artisan env:encrypt --env=production --readable
php artisan env:decrypt --env=production
```

For hosted deployments, consider the platform's native secret store, such as AWS Secrets Manager or Vault, and inject secrets at runtime.

## Use `App::environment()` for environment checks

Incorrect:

```php
if (env('APP_ENV') === 'production') {
    // ...
}
```

Correct:

```php
if (app()->isProduction()) {
    // ...
}

if (App::environment('production')) {
    // ...
}
```

## Name repeated domain values

Use an enum or class constant when a domain value is repeated or represents a constrained set. A one-off string literal does not always need a named constant.

```php
// Repeated literal
return $this->type === 'normal';

// Named domain value
return $this->type === self::TYPE_NORMAL;
```

If the application supports localization, put user-facing strings in language files and retrieve them with `__()`. Simple literals are reasonable for applications that intentionally do not support multiple languages.

```php
// In a localized application
return back()->with('message', __('app.article_added'));
```
