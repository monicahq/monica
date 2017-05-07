<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Reminder\ReminderCreated' => [
            'App\Listeners\Reminder\LogReminderCreatedEvent',
            'App\Listeners\Reminder\IncrementNumberOfReminders',
        ],
        'App\Events\Reminder\ReminderDeleted' => [
            'App\Listeners\Reminder\RemoveAllReminderEvents',
            'App\Listeners\Reminder\DecreaseNumberOfReminders',
        ],
        'App\Events\Gift\GiftCreated' => [
            'App\Listeners\Gift\LogGiftCreatedEvent',
            'App\Listeners\Gift\IncrementNumberOfGifts',
        ],
        'App\Events\Gift\GiftDeleted' => [
            'App\Listeners\Gift\RemoveAllGiftEvents',
            'App\Listeners\Gift\DecreaseNumberOfGifts',
        ],
        'App\Events\Task\TaskCreated' => [
            'App\Listeners\Task\LogTaskCreatedEvent',
            'App\Listeners\Task\IncrementNumberOfTasks',
        ],
        'App\Events\Task\TaskUpdated' => [
            'App\Listeners\Task\ChangeNumberOfTasks',
        ],
        'App\Events\Task\TaskDeleted' => [
            'App\Listeners\Task\RemoveAllTaskEvents',
            'App\Listeners\Task\DecreaseNumberOfTasks',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
