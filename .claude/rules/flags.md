# Flags

A flag keeps a capability that is still being built out of sight, account by account, until it is
released to everybody. Laravel Pennant stores the answers. Specification 068 has the reasoning.

## One class per flag, in `app/Features`

Name it with `#[Name('kebab-case')]`, so the stored name survives a rename of the class. Its resolver
takes the account and reads the answer stored for every account:

```php
#[Name('shared-albums')]
class SharedAlbums
{
    public function resolve(Account $account): bool
    {
        return Feature::for(null)->active(self::class);
    }
}
```

`AppServiceProvider::configureFlags()` discovers the folder, so the admin screen and
`monica:list-flags` name a flag before anybody has been asked about it.

## Ask through the account, never through the default scope

```php
$account->features()->active('shared-albums');
```

`Feature::active()` alone asks about whoever is signed in, and in a job, a command or a CardDAV
request that is nobody. Naming the account gives every surface the same answer, for the same reason
`User::authorize()` wraps `Gate::forUser()`.

A route group behind a flag takes the `flag:shared-albums` middleware. It answers not found, because
a capability that is off does not exist for that person. On a page that needs no sign in, a visitor
sees what every account sees. A view model or a component that has to know asks
`FlagState::isOn('shared-albums', $account)`, which gives the same two answers.

## Never read the plan or the deployment

A flag decides when a capability is ready, never who deserves it. Paying unlocks no feature, and
hosted and self hosted run the same product. A resolver that reads a subscription, `config('monica.self_hosted')`
or anything else about the account is a per plan entitlement, which the constitution forbids.

## A flag dies with its release

The pull request that releases a capability to everybody deletes the class, removes every check of
it, and runs `php artisan pennant:purge --except-registered` in its deployment notes. A flag left in
place forever is a second version of the product that nobody tests.

## Switching

`monica:switch-flag {flag} {on|off} --email=...|--every-account`, or the Flags screen of the admin
space. Both go through the same four actions and both are written in the administrator log. The
latest switch wins: switching for every account replaces what was chosen for one.
