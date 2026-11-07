# Validation and forms best practices

## Extract validation when it improves the boundary

Use a form request when validation or authorization is substantial, reused, or clearer outside the controller. Inline `$request->validate()` remains appropriate for a small, endpoint-specific rule set.

```php
public function store(StorePostRequest $request): RedirectResponse
{
    $post = Post::create($request->validated());

    return redirect()->route('posts.show', $post);
}
```

A form request's `authorize()` method can enforce access to the operation. Validation checks the shape and values of the input. Validation alone does not authorize the user.

## Prefer readable rule syntax

Array syntax combines with rule objects and avoids delimiter problems. Prefer array syntax in new code when it improves readability, and keep the local style consistent.

```php
'email' => ['required', 'email', Rule::unique('users')],
```

String syntax remains valid for simple rules:

```php
'email' => 'required|email|unique:users',
```

## Use only the validated data you intend

Use `validated()` or `safe()` instead of `$request->all()` when passing request data onward. When the validation rules also cover control fields or nested data, select only the fields intended for the operation.

Unsafe:

```php
Post::create($request->all());
```

Preferred:

```php
$post = Post::create($request->safe()->only(['title', 'body']));
```

Validated data is not automatically safe for mass assignment. Keep model `$fillable` or `$guarded` rules aligned with the operation, and never add a sensitive attribute to validation only to make mass assignment convenient.

## Express conditional rules clearly

Use conditional rules such as `Rule::when()`, `required_if`, or `exclude_unless` when they make the condition explicit. Choose the simplest form that remains easy to test.

```php
'company_name' => [
    'string',
    'max:255',
    Rule::when(
        $this->input('account_type') === 'business',
        ['required'],
        ['nullable'],
    ),
],
```

## Add cross-field validation after base rules

Use a form request's `after()` method for validation that depends on multiple fields or application state. Skip expensive queries when prerequisite fields already failed validation.

```php
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($validator->errors()->hasAny(['product_id', 'quantity'])) {
                return;
            }

            $stock = Product::find($this->integer('product_id'))?->stock;

            if ($stock !== null && $this->integer('quantity') > $stock) {
                $validator->errors()->add('quantity', 'Not enough stock.');
            }
        },
    ];
}
```

Validation against mutable state does not prevent a race between validation and persistence. Enforce inventory, uniqueness, and similar invariants with database constraints, atomic updates, or a database transaction as appropriate.
