<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\UserProfile;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePermissions();

        Jetstream::deleteUsersUsing(DeleteUser::class);
        Jetstream::inertia()->whenRendering('Profile/Show', new UserProfile);
        Jetstream::inertia()->whenRendering('API/Index', function ($request, $data) {
            // @codeCoverageIgnoreStart
            $data['layoutData'] = VaultIndexViewHelper::layoutData();

            return $data;
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * Configure the roles and permissions that are available within the application.
     *
     * @return void
     */
    protected function configurePermissions()
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'read',
            'write',
        ]);
    }
}
