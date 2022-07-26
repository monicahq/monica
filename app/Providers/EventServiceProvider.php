<?php

namespace App\Providers;

use App\Contact\ManageDocuments\Events\FileDeleted;
use App\Contact\ManageDocuments\Listeners\DeleteFileInStorage;
use App\Listeners\LocaleUpdatedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        LocaleUpdated::class => [
            LocaleUpdatedListener::class,
        ],
        FileDeleted::class => [
            DeleteFileInStorage::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
