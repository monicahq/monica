# Database performance best practices

## Eager load relationships before iterating

When code accesses a relationship on many models, eager load the relationship with `with()`. Eager loading avoids the N+1 query pattern: one initial query plus one relationship query per model. Lazy loading is reasonable when the code may not need the relationship or when only one model is involved.

Lazy-loaded version:

```php
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}
```

Eager-loaded version:

```php
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name;
}
```

Constrain an eager load when the code does not need the large columns. Include the related model's primary key and every column Eloquent needs to match the relationship. In this example, `users.id` and `posts.user_id` match posts to users, and selecting `posts.id` keeps each related model's primary key:

```php
$users = User::with(['posts' => function ($query) {
    $query->select('id', 'user_id', 'title')
          ->where('published', true)
          ->latest()
          ->limit(10);
}])->get();
```

## Prevent lazy loading in development

Enable lazy-loading prevention in `AppServiceProvider::boot()` to catch N+1 queries during development.

```php
public function boot(): void
{
    Model::preventLazyLoading(! app()->isProduction());
}
```

With prevention enabled, accessing an unloaded relationship throws a `LazyLoadingViolationException` by default. To customize violation handling, use `handleLazyLoadingViolationUsing()`.

## Select only the columns you need

When omitting large text, binary, or JSON columns gives a meaningful benefit, select only the columns the operation needs.

All columns:

```php
$posts = Post::with('author')->get();
```

Selected columns:

```php
$posts = Post::select('id', 'title', 'user_id', 'created_at')
    ->with(['author:id,name,avatar'])
    ->get();
```

When you limit the selected columns, keep every key Eloquent needs for matching. A `belongsTo` relationship needs its foreign key on the parent query and the owner's key on the related query. A `hasMany` relationship needs the parent's local key and the related model's foreign key.

## Process large data sets incrementally

When loading the entire result set would exceed the application's practical memory budget, use chunking or lazy iteration.

Loads the complete result set:

```php
$users = User::all();
foreach ($users as $user) {
    $user->notify(new WeeklyDigest);
}
```

Processes bounded chunks:

```php
User::where('subscribed', true)->chunk(200, function ($users) {
    foreach ($users as $user) {
        $user->notify(new WeeklyDigest);
    }
});
```

Use `chunkById()` when updates can change which rows match the query. Standard `chunk()` uses offset pagination, so result positions can shift as rows change:

```php
User::where('active', false)->chunkById(200, function ($users) {
    $users->each->delete();
});
```

For read-only iteration that uses only model attributes, `cursor()` runs one query and hydrates one model at a time. Some database drivers still buffer the raw results. Use `lazy()` when you need to eager load relationships in chunks. Use `lazyById()` or `chunkById()` when updates can change which rows match the query. See the collection rules for detailed tradeoffs.

## Add indexes for measured query patterns

Design indexes around frequent, performance-sensitive query patterns. A column's presence in `WHERE`, `ORDER BY`, `JOIN`, or `GROUP BY` does not by itself justify an index. Selectivity, write cost, existing indexes, and the database query plan all matter.

Schema without an application-specific query index:

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('status');
    $table->timestamps();
});
```

Schema optimized for `WHERE status = ? ORDER BY created_at`:

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('status');
    $table->timestamps();
    $table->index(['status', 'created_at']);
});
```

Use production-like data and the database's query-plan tools to confirm the column order of a composite index and whether the index helps. Before you add an index for a foreign key, check whether the database already created one.

## Count relationships without loading them

Use `withCount()` when you need only the relationship counts. Loading and hydrating every related model wastes memory.

Loads related models:

```php
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->comments->count();
}
```

Selects relationship counts:

```php
$posts = Post::withCount('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_count;
}
```

Conditional counting:

```php
$posts = Post::withCount([
    'comments',
    'comments as approved_comments_count' => function ($query) {
        $query->where('approved', true);
    },
])->get();
```

## Keep queries out of Blade templates

Prepare data before rendering a Blade template, for example in a controller, a query service, or a view composer. Queries outside the template stay visible and testable.

Query in the template:

```blade
@foreach (User::all() as $user)
    {{ $user->profile->name }}
@endforeach
```

Data prepared before rendering:

```php
// Controller
$users = User::with('profile')->get();

return view('users.index', compact('users'));
```

```blade
@foreach ($users as $user)
    {{ $user->profile->name }}
@endforeach
```
