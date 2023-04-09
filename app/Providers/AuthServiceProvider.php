<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\Journal;
use App\Models\Post;
use App\Models\SliceOfLife;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('administrator', function (User $user): bool {
            return $user->is_account_administrator;
        });

        Gate::define('vault-viewer', function (User $user, $vault): bool {
            return $user->vaults()
                ->wherePivotIn('vault_id', [static::id($vault)])
                ->exists();
        });

        Gate::define('vault-editor', function (User $user, $vault): bool {
            return $user->vaults()
                ->wherePivotIn('vault_id', [static::id($vault)])
                ->wherePivot('permission', '<=', 200)
                ->exists();
        });

        Gate::define('vault-manager', function (User $user, $vault): bool {
            return $user->vaults()
                ->wherePivotIn('vault_id', [static::id($vault)])
                ->wherePivot('permission', '<=', 100)
                ->exists();
        });

        Gate::define('contact-owner', function (User $user, $vault, $contact): bool {
            if ($contact instanceof Contact) {
                return $contact->vault_id === static::id($vault);
            }

            return Contact::where([
                'id' => static::id($contact),
                'vault_id' => static::id($vault),
            ])->exists();
        });

        Gate::define('group-owner', function (User $user, $vault, $group): bool {
            if ($group instanceof Group) {
                return $group->vault_id === static::id($vault);
            }

            return Group::where([
                'id' => static::id($group),
                'vault_id' => static::id($vault),
            ])->exists();
        });

        Gate::define('journal-owner', function (User $user, $vault, $journal): bool {
            if ($journal instanceof Journal) {
                return $journal->vault_id === static::id($vault);
            }

            return Journal::where([
                'id' => static::id($journal),
                'vault_id' => static::id($vault),
            ])->exists();
        });

        Gate::define('post-owner', function (User $user, $journal, $post): bool {
            if ($post instanceof Post) {
                return $post->journal_id === static::id($journal);
            }

            return Post::where([
                'id' => static::id($post),
                'journal_id' => static::id($journal),
            ])->exists();
        });

        Gate::define('sliceOfLife-owner', function (User $user, $journal, $sliceOfLife): bool {
            if ($sliceOfLife instanceof SliceOfLife) {
                return $sliceOfLife->journal_id === static::id($journal);
            }

            return SliceOfLife::where([
                'id' => static::id($sliceOfLife),
                'journal_id' => static::id($journal),
            ])->exists();
        });
    }

    protected static function id($model)
    {
        return is_subclass_of($model, Model::class) ? $model->getKey() : $model;
    }
}
