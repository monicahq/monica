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
        'App\Events\Contact\ContactCreated' => [
            'App\Listeners\Contact\SetAvatarColor',
            'App\Listeners\Contact\LogContactCreatedEvent',
        ],
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
        'App\Events\Activity\ActivityCreated' => [
            'App\Listeners\Activity\IncreaseActivitiesStatistics',
            'App\Listeners\Activity\LogActivityCreatedEvent',
            'App\Listeners\Activity\IncrementNumberOfActivities',
        ],
        'App\Events\Activity\ActivityUpdated' => [
            'App\Listeners\Activity\UpdateActivitiesStatistics',
            'App\Listeners\Activity\LogActivityUpdatedEvent',
        ],
        'App\Events\Activity\ActivityDeleted' => [
            'App\Listeners\Activity\DecreaseActivitiesStatistics',
            'App\Listeners\Activity\RemoveAllActivityEvents',
            'App\Listeners\Activity\DecreaseNumberOfActivities',
        ],
        'App\Events\Kid\KidCreated' => [
            'App\Listeners\Kid\LogKidCreatedEvent',
            'App\Listeners\Kid\IncrementNumberOfKids',
        ],
        'App\Events\Kid\KidUpdated' => [
            'App\Listeners\Kid\LogKidUpdatedEvent',
        ],
        'App\Events\Kid\KidDeleted' => [
            'App\Listeners\Kid\RemoveAllKidEvents',
            'App\Listeners\Kid\DecreaseNumberOfKids',
        ],
        'App\Events\Note\NoteCreated' => [
            'App\Listeners\Note\LogNoteCreatedEvent',
            'App\Listeners\Note\IncrementNumberOfNotes',
        ],
        'App\Events\Note\NoteDeleted' => [
            'App\Listeners\Note\RemoveAllNoteEvents',
            'App\Listeners\Note\DecreaseNumberOfNotes',
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
